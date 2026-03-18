<?php

namespace App\Http\Controllers\Assets\Hfs;

use App\Http\Controllers\Controller;
use App\Models\Assets\HfsRequest;
use App\Models\BankAccount;
use App\Services\Assets\Hfs\HfsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Vinkla\Hashids\Facades\Hashids;

class HfsDisposalController extends Controller
{
    protected $hfsService;

    public function __construct(HfsService $hfsService)
    {
        $this->hfsService = $hfsService;
    }

    /**
     * Show the form for creating a disposal record
     */
    public function create($hfsId)
    {
        $decodedId = Hashids::decode($hfsId)[0] ?? $hfsId;
        $hfsRequest = HfsRequest::with(['hfsAssets.asset', 'disposal'])->findOrFail($decodedId);

        if ($hfsRequest->status !== 'approved') {
            return redirect()
                ->route('assets.hfs.requests.show', $hfsId)
                ->with('error', 'Disposal can only be recorded for approved HFS requests.');
        }

        if ($hfsRequest->disposal) {
            return redirect()
                ->route('assets.hfs.requests.show', $hfsId)
                ->with('error', 'Disposal already recorded for this HFS request.');
        }

        $user = Auth::user();
        $bankAccounts = BankAccount::with('chartAccount')
            ->whereHas('chartAccount.accountClassGroup', function ($query) use ($user) {
                $query->where('company_id', $user->company_id);
            })
            ->orderBy('name')
            ->get();

        $encodedId = Hashids::encode($hfsRequest->id);

        return view('assets.hfs.disposals.create', compact('hfsRequest', 'bankAccounts', 'encodedId'));
    }

    /**
     * Store a newly created disposal record
     */
    public function store(Request $request, $hfsId)
    {
        $decodedId = Hashids::decode($hfsId)[0] ?? $hfsId;
        $hfsRequest = HfsRequest::findOrFail($decodedId);

        if ($hfsRequest->status !== 'approved') {
            return redirect()
                ->back()
                ->with('error', 'Disposal can only be recorded for approved HFS requests.');
        }

        $validated = $request->validate([
            'disposal_date' => 'required|date',
            'sale_proceeds' => 'required|numeric|min:0',
            'sale_currency' => 'nullable|string|max:3',
            'currency_rate' => 'nullable|numeric|min:0',
            'costs_sold' => 'nullable|numeric|min:0',
            'buyer_name' => 'nullable|string|max:255',
            'buyer_contact' => 'nullable|string|max:255',
            'buyer_address' => 'nullable|string',
            'invoice_number' => 'nullable|string|max:255',
            'receipt_number' => 'nullable|string|max:255',
            'settlement_reference' => 'nullable|string|max:255',
            'bank_account_id' => 'nullable|exists:bank_accounts,id',
            'vat_type' => 'nullable|in:no_vat,exclusive,inclusive',
            'vat_rate' => 'nullable|numeric|min:0|max:100',
            'vat_amount' => 'nullable|numeric|min:0',
            'withholding_tax_enabled' => 'nullable|boolean',
            'withholding_tax_rate' => 'nullable|numeric|min:0|max:100',
            'withholding_tax_type' => 'nullable|in:percentage,fixed',
            'withholding_tax' => 'nullable|numeric|min:0',
            'is_partial_sale' => 'nullable|boolean',
            'partial_sale_percentage' => 'nullable|numeric|min:0|max:100',
            'notes' => 'nullable|string',
            'attachments' => 'nullable|array',
        ]);

        try {
            $result = $this->hfsService->processSale($hfsRequest, $validated);

            return redirect()
                ->route('assets.hfs.requests.show', Hashids::encode($hfsRequest->id))
                ->with('success', 'Disposal recorded successfully. ' . 
                    ($result['gain_loss'] >= 0 ? 'Gain' : 'Loss') . ' of ' . 
                    number_format(abs($result['gain_loss']), 2) . ' recognized.');

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Failed to record disposal: ' . $e->getMessage());
        }
    }
}
