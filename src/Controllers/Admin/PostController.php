<?php

namespace Buni\Cms\Controllers\Admin;

use Buni\Cms\Models\Post;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class PostController extends Controller
{
    public function index(Request $request)
    {
        $query = Post::with('author');

        // Search functionality
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where('title', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%");
        }

        // Filter by status
        if ($request->has('status') && !empty($request->status)) {
            $query->where('status', $request->status);
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');
        $query->orderBy($sortBy, $sortDirection);

        $posts = $query->paginate($request->get('per_page', 15));

        return Inertia::render('Admin/Posts/Index', [
            'posts' => $posts,
            'filters' => $request->only(['search', 'status', 'sort_by', 'sort_direction', 'per_page'])
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Posts/Create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required',
            'slug' => 'required|unique:posts',
            'content' => 'nullable',
            'excerpt' => 'nullable',
            'status' => 'required|in:draft,published',
            'published_at' => 'nullable|date',
            'blocks' => 'nullable|array',
        ]);

        $data = $request->all();
        $data['author_id'] = auth()->id();

        Post::create($data);

        return redirect()->route('cms.admin.posts.index');
    }

    public function edit(Post $post)
    {
        return Inertia::render('Admin/Posts/Edit', ['post' => $post]);
    }

    public function update(Request $request, Post $post)
    {
        $request->validate([
            'title' => 'required',
            'slug' => 'required|unique:posts,slug,' . $post->id,
            'content' => 'nullable',
            'excerpt' => 'nullable',
            'status' => 'required|in:draft,published',
            'published_at' => 'nullable|date',
            'blocks' => 'nullable|array',
        ]);

        $post->update($request->all());

        return redirect()->route('cms.admin.posts.index');
    }

    public function destroy(Post $post)
    {
        $post->delete();
        return redirect()->route('cms.admin.posts.index');
    }

    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:posts,id'
        ]);

        Post::whereIn('id', $request->ids)->delete();

        return response()->json(['message' => 'Posts deleted successfully']);
    }
}