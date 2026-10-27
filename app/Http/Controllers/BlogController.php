<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;

class BlogController extends Controller
{
    public function index(Request $request)
    {
        $category = array_key_exists((string) $request->query('seccion'), Post::CATEGORIES) ? $request->query('seccion') : null;
        $posts = Post::public()->when($category, fn ($q) => $q->where('category', $category))->paginate(9)->withQueryString();

        return view('blog.index', ['posts' => $posts, 'category' => $category]);
    }

    public function show(string $slug)
    {
        $post = Post::public()->where('slug', $slug)->firstOrFail();

        return view('blog.show', ['post' => $post, 'more' => Post::public()->whereKeyNot($post->id)->limit(3)->get()]);
    }
}
