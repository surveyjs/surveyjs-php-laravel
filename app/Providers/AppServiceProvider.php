<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\DevCommands;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Saving definitions (creator-load-save, lint-definition) and variable presets is an admin action.
        // Your real permission check goes here: roles, teams, form ownership…
        // The deny message is the { error } of the 403, which Creator shows (creator-load-save).
        Gate::define('edit-forms', fn (User $user) => $user->is_editor
            ? Response::allow()
            : Response::deny("You don't have editor rights: only editors can change forms"));

        // Private files (store-files) go through this check before GET /api/files/{id} returns them.
        // Real apps check ownership here, for example $file->owner_id === $user->id.
        Gate::define('read-file', fn (?User $user, object $file) => $user !== null);

        // `composer dev` serves with surveyjs:serve (the port check, the upload limits, the build line) in place of
        // `php artisan serve`, and also starts the fill-together and edit-together relays next to the queue and Vite
        DevCommands::artisan('surveyjs:serve', 'server');
        DevCommands::artisan('relay:fill', 'relay');
        DevCommands::artisan('relay:edit', 'edit-relay');
    }
}
