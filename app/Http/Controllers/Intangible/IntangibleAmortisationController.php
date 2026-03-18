<?php

namespace App\Http\Controllers\Intangible;

use App\Http\Controllers\Controller;
use App\Services\Intangible\IntangibleAmortisationService;
use Illuminate\Http\Request;

class IntangibleAmortisationController extends Controller
{
    protected IntangibleAmortisationService $service;

    public function __construct(IntangibleAmortisationService $service)
    {
        $this->service = $service;
    }

    /**
     * Show amortisation run screen.
     */
    public function index()
    {
        return view('intangible.amortisation.index');
    }

    /**
     * Process amortisation for selected month.
     */
    public function process(Request $request)
    {
        $request->validate([
            'period' => 'required|date',
        ]);

        $period = new \DateTime($request->input('period'));

        $result = $this->service->runForMonth($period);

        return redirect()
            ->route('assets.intangible.amortisation.index')
            ->with('success', "Amortisation run completed. Assets processed: {$result['processed']}, Total amount: {$result['total_amount']}.");
    }
}


