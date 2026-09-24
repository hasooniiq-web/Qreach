<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Vehicle;

/**
 * Ownership enforced server-side, always (section 9): a customer may only
 * ever act on their own vehicles, regardless of what the client sends.
 */
class VehiclePolicy
{
    public function view(User $user, Vehicle $vehicle): bool
    {
        return $user->id === $vehicle->user_id;
    }

    public function update(User $user, Vehicle $vehicle): bool
    {
        return $user->id === $vehicle->user_id;
    }

    public function delete(User $user, Vehicle $vehicle): bool
    {
        return $user->id === $vehicle->user_id;
    }
}
