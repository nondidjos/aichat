<?php

use App\Models\User;
use Laravel\Dusk\Browser;

test('login page loads', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/auth/login')
                ->assertSee('Log in');
    });
});

test('user can visit register', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/auth/register')
                ->assertSee('Register');
    });
});

test('authenticated user can access dashboard', function () {
    $user = User::factory()->create();

    $this->browse(function (Browser $browser) use ($user) {
        $browser->loginAs($user)
                ->visit('/dashboard')
                ->assertAuthenticated();
    });
});
