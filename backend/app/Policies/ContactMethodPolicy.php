<?php

namespace App\Policies;

use App\Models\ContactMethod;
use App\Models\User;

/** A customer may only change or remove their own contact methods. */
class ContactMethodPolicy
{
    public function update(User $user, ContactMethod $method): bool
    {
        return $user->id === $method->user_id;
    }

    public function delete(User $user, ContactMethod $method): bool
    {
        return $user->id === $method->user_id;
    }
}
