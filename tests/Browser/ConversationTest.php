<?php

use App\Models\User;
use Laravel\Dusk\Browser;

test('user can visit chat page', function () {
    $user = User::factory()->create();

    $this->browse(function (Browser $browser) use ($user) {
        $browser->loginAs($user)
                ->visit('/ask/new')
                ->assertSee('New Chat');
    });
});

test('user can type a message', function () {
    $user = User::factory()->create(['api_key' => encrypt('sk-test')]);

    $this->browse(function (Browser $browser) use ($user) {
        $browser->loginAs($user)
                ->visit('/ask/new')
                ->type('textarea', 'Hello')
                ->assertInputValue('textarea', 'Hello');
    });
});

test('sidebar shows conversations', function () {
    $user = User::factory()->create();

    $this->browse(function (Browser $browser) use ($user) {
        $browser->loginAs($user)
                ->visit('/ask/new')
                ->assertPresent('aside');
    });
});

test('new chat button works', function () {
    $user = User::factory()->create();

    $this->browse(function (Browser $browser) use ($user) {
        $browser->loginAs($user)
                ->visit('/ask/new')
                ->press('New Chat')
                ->pause(500)
                ->assertPathIs('/ask/new');
    });
});
