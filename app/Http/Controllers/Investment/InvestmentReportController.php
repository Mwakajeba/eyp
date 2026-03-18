<?php

namespace App\Http\Controllers\Investment;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InvestmentReportController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware(['auth', 'company.scope', 'require.branch']);
    }

    /**
     * Display the Investment Reports index page.
     *
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        // Placeholder data - will be replaced with actual data in Phase 5
        $user = Auth::user();
        
        return view('investments.reports.index', compact('user'));
    }
}

