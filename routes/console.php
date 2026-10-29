<?php

use App\Models\User;
use App\Services\TonieSync;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

Artisan::command('tonies:sync', function (TonieSync $sync) {
    $result = $sync->run();
    $this->info("Synced {$result['owned']} owned Tonies and {$result['catalog']} store Tonies ({$result['missing']} we don't have).");
})->purpose('Refresh owned Tonies from the Toniecloud and the catalog from us.tonies.com');

Artisan::command('user:create', function () {
    $user = User::updateOrCreate(
        ['email' => text('Email', required: true, validate: ['email' => 'email'])],
        [
            'name' => text('Name', required: true),
            'password' => password('Password', required: true, validate: ['password' => 'min:8']),
        ],
    );
    $this->info("User {$user->email} can now log in.");
})->purpose('Create (or reset the password of) the account that manages the wishlist');

Schedule::command('tonies:sync')->daily();
