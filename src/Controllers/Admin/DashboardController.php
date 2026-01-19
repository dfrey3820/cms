<?php

namespace Dsc\Cms\Controllers\Admin;

use Inertia\Inertia;
use Illuminate\Routing\Controller;

class DashboardController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Dashboard');
    }
}