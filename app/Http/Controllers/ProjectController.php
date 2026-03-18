<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ProjectController extends Controller
{
    /**
     * Display the Project Management dashboard.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('projects.index');
    }
}



