<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AssetsController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'company.scope', 'require.branch']);
    }

    public function index()
    {
        return view('assets.index');
    }

    public function registry()
    {
        return view('assets.sections.registry');
    }

    public function capitalization()
    {
        return view('assets.sections.capitalization');
    }

    public function depreciation()
    {
        return view('assets.sections.depreciation');
    }

    public function movements()
    {
        return view('assets.sections.movements');
    }

    public function revaluationImpairment()
    {
        return view('assets.sections.revaluation_impairment');
    }

    public function disposal()
    {
        return view('assets.sections.disposal');
    }

    public function maintenance()
    {
        return view('assets.sections.maintenance');
    }

    public function opening()
    {
        return view('assets.sections.opening');
    }

    public function audit()
    {
        return view('assets.sections.audit');
    }

    public function integrations()
    {
        return view('assets.sections.integrations');
    }

    public function settings()
    {
        return view('assets.sections.settings');
    }
}


