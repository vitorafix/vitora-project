<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Post;
use Illuminate\Auth\Access\HandlesAuthorization;

class PostPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view posts');
    }

    public function view(User $user, Post $post): bool
    {
        return $user->hasPermissionTo('view post') || $user->id === $post->user_id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create post');
    }

    public function update(User $user, Post $post): bool
    {
        // ادمین یا صاحب پست با مجوز ویرایش
        return $user->hasRole('admin') || ($user->id === $post->user_id && $user->hasPermissionTo('edit post'));
    }

    public function delete(User $user, Post $post): bool
    {
        return $user->hasRole('admin') || ($user->id === $post->user_id && $user->hasPermissionTo('delete post'));
    }

    public function restore(User $user, Post $post): bool
    {
        return $user->hasRole('admin');
    }

    public function forceDelete(User $user, Post $post): bool
    {
        return $user->hasRole('admin');
    }
}
