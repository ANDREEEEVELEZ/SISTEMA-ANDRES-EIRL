<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Asistencia;
use Illuminate\Auth\Access\HandlesAuthorization;

class AsistenciaPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Asistencia');
    }

    public function view(AuthUser $authUser, Asistencia $asistencia): bool
    {
        return $authUser->can('View:Asistencia');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Asistencia');
    }

    public function update(AuthUser $authUser, Asistencia $asistencia): bool
    {
        return $authUser->can('Update:Asistencia');
    }

    public function delete(AuthUser $authUser, Asistencia $asistencia): bool
    {
        return $authUser->can('Delete:Asistencia');
    }

    public function restore(AuthUser $authUser, Asistencia $asistencia): bool
    {
        return $authUser->can('Restore:Asistencia');
    }

    public function forceDelete(AuthUser $authUser, Asistencia $asistencia): bool
    {
        return $authUser->can('ForceDelete:Asistencia');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Asistencia');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Asistencia');
    }

    public function replicate(AuthUser $authUser, Asistencia $asistencia): bool
    {
        return $authUser->can('Replicate:Asistencia');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Asistencia');
    }

}