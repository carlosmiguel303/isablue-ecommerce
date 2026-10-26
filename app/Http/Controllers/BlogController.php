<?php

namespace App\Http\Controllers;

use App\Models\Post;

class BlogController extends Controller
{
    public function index()
    {
        return view('blog.index', ['posts' => Post::public()->paginate(9)]);
    }

    public function show(string $slug)
    {
        $post = Post::public()->where('slug', $slug)->firstOrFail();

        return view('blog.show', ['post' => $post, 'more' => Post::public()->whereKeyNot($post->id)->limit(3)->get()]);
    }
}
