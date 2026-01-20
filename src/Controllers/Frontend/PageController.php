<?php

namespace Buni\Cms\Controllers\Frontend;

use Buni\Cms\Models\Page;
use Buni\Cms\Database\Seeders\CmsSeeder;
use Inertia\Inertia;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class PageController extends Controller
{
    public function show($slug = null)
    {
        // Check if CMS is installed (has users)
        if (!Schema::hasTable('users') || \App\Models\User::count() == 0) {
            return Inertia::render('Install');
        }

        $slug = $slug ?: 'home'; // Default to home page

        $page = Page::where('slug', $slug)->first();

        if (!$page) {
            abort(404);
        }

        return Inertia::render('Page', ['page' => $page]);
    }

    public function install(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        // Run seeder to create roles
        $seeder = new CmsSeeder();
        $seeder->run();

        // Create admin user
        $user = \App\Models\User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Assign super-admin role
        $user->assignRole('super-admin');

        // Create a default home page
        Page::create([
            'title' => 'Welcome to Buni CMS',
            'slug' => 'home',
            'content' => '<p>Welcome to your new CMS powered by Buni.</p>',
            'status' => 'published',
        ]);

        return redirect('/admin');
    }
}