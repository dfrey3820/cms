<?php

namespace Buni\Cms\Controllers\Frontend;

use Buni\Cms\Models\Page;
use Inertia\Inertia;
use Illuminate\Routing\Controller;

class PageController extends Controller
{
    public function show($slug = null)
    {
        $slug = $slug ?: 'home'; // Default to home page

        $page = Page::where('slug', $slug)->first();

        if (!$page) {
            abort(404);
        }

        return Inertia::render('Page', ['page' => $page]);
    }
}