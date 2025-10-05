<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Categoria;
use Illuminate\Auth\Access\HandlesAuthorization;

class CategoriaPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Categoria');
    }

    public function view(AuthUser $authUser, Categoria $categoria): bool
    {
        return $authUser->can('View:Categoria');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Categoria');
    }

    public function update(AuthUser $authUser, Categoria $categoria): bool
    {
        return $authUser->can('Update:Categoria');
    }

    public function delete(AuthUser $authUser, Categoria $categoria): bool
    {
        return $authUser->can('Delete:Categoria');
    }

    public function restore(AuthUser $authUser, Categoria $categoria): bool
    {
        return $authUser->can('Restore:Categoria');
    }

    public function forceDelete(AuthUser $authUser, Categoria $categoria): bool
    {
        return $authUser->can('ForceDelete:Categoria');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Categoria');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Categoria');
    }

    public function replicate(AuthUser $authUser, Categoria $categoria): bool
    {
        return $authUser->can('Replicate:Categoria');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Categoria');
    }

}