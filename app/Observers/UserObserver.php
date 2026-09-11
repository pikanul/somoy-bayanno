<?php

namespace App\Observers;

use App\Models\User;
use Illuminate\Support\Facades\Log;

class UserObserver
{
    public function created(User $user): void
    {
        $this->log('created', $user);
    }

    public function updated(User $user): void
    {
        $this->log('updated', $user, array_values(array_diff(array_keys($user->getChanges()), [
            'password',
            'remember_token',
        ])));
    }

    public function deleted(User $user): void
    {
        $this->log('deleted', $user);
    }

    public function restored(User $user): void
    {
        $this->log('restored', $user);
    }

    private function log(string $event, User $user, array $changed = []): void
    {
        Log::info("User model {$event}", [
            'actor_id' => auth()->id(),
            'target_user_id' => $user->getKey(),
            'changed' => $changed,
        ]);
    }
}
