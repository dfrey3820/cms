<?php

namespace Dsc\Cms\Controllers\Admin;

use Dsc\Cms\Models\Page;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class PageController extends Controller
{
    public function index()
    {
        $pages = Page::all();
        return Inertia::render('Admin/Pages/Index', ['pages' => $pages]);
    }

    public function create()
    {
        return Inertia::render('Admin/Pages/Create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required',
            'slug' => 'required|unique:pages',
            'content' => 'nullable',
            'blocks' => 'nullable|array',
        ]);

        Page::create($request->all());

        return redirect()->route('cms.admin.pages.index');
    }

    public function edit(Page $page)
    {
        return Inertia::render('Admin/Pages/Edit', ['page' => $page]);
    }

    public function update(Request $request, Page $page)
    {
        $request->validate([
            'title' => 'required',
            'slug' => 'required|unique:pages,slug,' . $page->id,
            'content' => 'nullable',
            'blocks' => 'nullable|array',
        ]);

        $page->update($request->all());

        return redirect()->route('cms.admin.pages.index');
    }

    public function destroy(Page $page)
    {
        $page->delete();
        return redirect()->route('cms.admin.pages.index');
    }
}