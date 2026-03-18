<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\AccountingDocument;
use App\Models\Hr\FileType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AccountingDocumentController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        if ($request->ajax()) {
            $query = AccountingDocument::with(['fileType', 'uploader'])
                ->where('company_id', $user->company_id)
                ->latest();

            return datatables($query)
                ->filter(function ($builder) use ($request) {
                    $search = $request->input('search.value');

                    if (!$search) {
                        return;
                    }

                    $builder->where(function ($q) use ($search) {
                        $q->where('description', 'like', "%{$search}%")
                            ->orWhere('file_name', 'like', "%{$search}%")
                            ->orWhereHas('fileType', function ($fileTypeQuery) use ($search) {
                                $fileTypeQuery->where('name', 'like', "%{$search}%");
                            })
                            ->orWhereHas('uploader', function ($uploaderQuery) use ($search) {
                                $uploaderQuery->where('name', 'like', "%{$search}%");
                            });
                    });
                })
                ->addColumn('file_type_name', function (AccountingDocument $document) {
                    return $document->fileType->name ?? 'N/A';
                })
                ->addColumn('file_display', function (AccountingDocument $document) {
                    return '<div>' . e($document->file_name) . '</div>'
                        . '<small class="text-muted">' . e($document->file_size_human) . '</small>';
                })
                ->addColumn('uploaded_by_name', function (AccountingDocument $document) {
                    return $document->uploader->name ?? 'N/A';
                })
                ->addColumn('uploaded_at', function (AccountingDocument $document) {
                    return optional($document->created_at)->format('d M Y H:i');
                })
                ->addColumn('actions', function (AccountingDocument $document) {
                    $viewUrl = route('accounting.documents.view', $document->id);
                    $editUrl = route('accounting.documents.edit', $document->id);
                    $deleteUrl = route('accounting.documents.destroy', $document->id);

                    return '<a href="' . e($viewUrl) . '" target="_blank" class="btn btn-sm btn-info me-1" title="View">'
                        . '<i class="bx bx-show"></i></a>'
                        . '<a href="' . e($editUrl) . '" class="btn btn-sm btn-warning me-1" title="Edit">'
                        . '<i class="bx bx-edit"></i></a>'
                        . '<button type="button" class="btn btn-sm btn-danger" title="Delete" onclick="deleteDocument(' . (int) $document->id . ', \'" . e($deleteUrl) . "\')">'
                        . '<i class="bx bx-trash"></i></button>';
                })
                ->rawColumns(['file_display', 'actions'])
                ->make(true);
        }

        $fileTypes = FileType::where('company_id', $user->company_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('accounting.documents.index', compact('fileTypes'));
    }

    public function store(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'file_type_id' => ['required', 'exists:hr_file_types,id'],
            'description' => ['required', 'string', 'max:2000'],
            'file' => ['required', 'file', 'max:10240'],
        ]);

        $fileType = FileType::where('company_id', $user->company_id)
            ->where('id', $validated['file_type_id'])
            ->where('is_active', true)
            ->firstOrFail();

        $file = $request->file('file');
        $this->validateFileAgainstType($fileType, $file);

        $storedName = time() . '_' . preg_replace('/\s+/', '_', $file->getClientOriginalName());
        $path = $file->storeAs('accounting-documents', $storedName, 'public');

        AccountingDocument::create([
            'company_id' => $user->company_id,
            'branch_id' => session('branch_id') ?? $user->branch_id,
            'file_type_id' => $fileType->id,
            'uploaded_by' => $user->id,
            'description' => $validated['description'],
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'file_extension' => strtolower((string) $file->getClientOriginalExtension()),
            'file_size' => (int) $file->getSize(),
            'mime_type' => $file->getMimeType(),
        ]);

        return redirect()->route('accounting.documents.index')->with('success', 'Document uploaded successfully.');
    }

    public function view(AccountingDocument $document)
    {
        $this->authorizeDocument($document);

        if (!Storage::disk('public')->exists($document->file_path)) {
            abort(404, 'File not found.');
        }

        return Storage::disk('public')->response(
            $document->file_path,
            $document->file_name,
            ['Content-Disposition' => 'inline; filename="' . $document->file_name . '"']
        );
    }

    public function edit(AccountingDocument $document): View
    {
        $this->authorizeDocument($document);
        $user = Auth::user();

        $fileTypes = FileType::where('company_id', $user->company_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('accounting.documents.edit', compact('document', 'fileTypes'));
    }

    public function update(Request $request, AccountingDocument $document): RedirectResponse
    {
        $this->authorizeDocument($document);
        $user = Auth::user();

        $validated = $request->validate([
            'file_type_id' => ['required', 'exists:hr_file_types,id'],
            'description' => ['required', 'string', 'max:2000'],
            'file' => ['nullable', 'file', 'max:10240'],
        ]);

        $fileType = FileType::where('company_id', $user->company_id)
            ->where('id', $validated['file_type_id'])
            ->where('is_active', true)
            ->firstOrFail();

        $updateData = [
            'file_type_id' => $fileType->id,
            'description' => $validated['description'],
        ];

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $this->validateFileAgainstType($fileType, $file);

            if (Storage::disk('public')->exists($document->file_path)) {
                Storage::disk('public')->delete($document->file_path);
            }

            $storedName = time() . '_' . preg_replace('/\s+/', '_', $file->getClientOriginalName());
            $path = $file->storeAs('accounting-documents', $storedName, 'public');

            $updateData['file_path'] = $path;
            $updateData['file_name'] = $file->getClientOriginalName();
            $updateData['file_extension'] = strtolower((string) $file->getClientOriginalExtension());
            $updateData['file_size'] = (int) $file->getSize();
            $updateData['mime_type'] = $file->getMimeType();
        }

        $document->update($updateData);

        return redirect()->route('accounting.documents.index')->with('success', 'Document updated successfully.');
    }

    public function destroy(AccountingDocument $document): RedirectResponse
    {
        $this->authorizeDocument($document);

        if (Storage::disk('public')->exists($document->file_path)) {
            Storage::disk('public')->delete($document->file_path);
        }

        $document->delete();

        return redirect()->route('accounting.documents.index')->with('success', 'Document deleted successfully.');
    }

    private function authorizeDocument(AccountingDocument $document): void
    {
        $user = Auth::user();

        if ((int) $document->company_id !== (int) $user->company_id) {
            abort(403, 'Unauthorized access to document.');
        }
    }

    private function validateFileAgainstType(FileType $fileType, $file): void
    {
        if ($fileType->allowed_extensions) {
            $extension = strtolower((string) $file->getClientOriginalExtension());
            $allowed = array_map('strtolower', $fileType->allowed_extensions);

            if (!in_array($extension, $allowed, true)) {
                throw ValidationException::withMessages([
                    'file' => ['File extension not allowed. Allowed extensions: ' . implode(', ', $allowed)],
                ]);
            }
        }

        if ($fileType->max_file_size) {
            $fileSizeKB = ((float) $file->getSize()) / 1024;
            if ($fileSizeKB > (float) $fileType->max_file_size) {
                throw ValidationException::withMessages([
                    'file' => ['File size exceeds maximum allowed size of ' . $fileType->max_file_size_human . '.'],
                ]);
            }
        }
    }
}
