<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Hr\FileType;
use Illuminate\Validation\Rule;

class FileTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = auth()->user();
        $fileTypes = FileType::where('company_id', $user->company_id)
            ->orderBy('name')
            ->paginate(15);

        return view('hr-payroll.file-types.index', compact('fileTypes'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('hr-payroll.file-types.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = auth()->user();
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('hr_file_types')->where(fn($q) => $q->where('company_id', $user->company_id))],
            'code' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'allowed_extensions' => ['nullable', 'string'],
            'max_file_size' => ['nullable', 'integer', 'min:1'],
            'is_required' => ['boolean'],
            'is_active' => ['boolean'],
        ]);

        // Process allowed extensions
        if (!empty($validated['allowed_extensions'])) {
            $extensions = array_map('trim', explode(',', $validated['allowed_extensions']));
            $validated['allowed_extensions'] = array_filter($extensions);
        } else {
            $validated['allowed_extensions'] = null;
        }

        $fileType = FileType::create(array_merge($validated, [
            'company_id' => $user->company_id,
        ]));

        return redirect()->route('hr.file-types.index')->with('success', 'File Type created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $user = auth()->user();
        $fileType = FileType::where('company_id', $user->company_id)->findOrFail($id);
        return view('hr-payroll.file-types.edit', compact('fileType'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $user = auth()->user();
        $fileType = FileType::where('company_id', $user->company_id)->findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('hr_file_types')->ignore($fileType->id)->where(fn($q) => $q->where('company_id', $user->company_id))],
            'code' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'allowed_extensions' => ['nullable', 'string'],
            'max_file_size' => ['nullable', 'integer', 'min:1'],
            'is_required' => ['boolean'],
            'is_active' => ['boolean'],
        ]);

        // Process allowed extensions
        if (!empty($validated['allowed_extensions'])) {
            $extensions = array_map('trim', explode(',', $validated['allowed_extensions']));
            $validated['allowed_extensions'] = array_filter($extensions);
        } else {
            $validated['allowed_extensions'] = null;
        }

        $fileType->update($validated);

        return redirect()->route('hr.file-types.index')->with('success', 'File Type updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $user = auth()->user();
        $fileType = FileType::where('company_id', $user->company_id)->findOrFail($id);

        // Check if file type has documents
        if ($fileType->documents()->count() > 0) {
            return redirect()->route('hr.file-types.index')
                ->with('error', 'Cannot delete file type "' . $fileType->name . '" because it has associated documents. Please reassign documents first.');
        }

        $fileType->delete();
        return redirect()->route('hr.file-types.index')->with('success', 'File Type deleted successfully.');
    }
}
