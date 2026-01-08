<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Auth\Access\Response;
use App\Models\User;

class AuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->registerPolicies();

        // Authorization gate for manager role
        gate('isManager', function (User $user) {
            return $user->isManager()
                ? Response::allow()
                : Response::deny('You must be a manager to perform this action.');
        });

        // Authorization gate for admin role
        gate('isAdmin', function (User $user) {
            return $user->isAdmin()
                ? Response::allow()
                : Response::deny('You must be an admin to perform this action.');
        });
    }
}
