<?php

use App\Models\User;
use Laravel\Dusk\Browser;

test('user can access api key settings', function () {
    $user = User::factory()->create();

    $this->browse(function (Browser $browser) use ($user) {
        $browser->loginAs($user)
                ->visit('/settings/api-key')
                ->assertSee('API Key');
    });
});

test('user can type api key', function () {
    $user = User::factory()->create();

    $this->browse(function (Browser $browser) use ($user) {
        $browser->loginAs($user)
                ->visit('/settings/api-key')
                ->type('input', 'sk-test-key')
                ->assertInputValue('input', 'sk-test-key');
    });
});

test('save button exists', function () {
    $user = User::factory()->create();

    $this->browse(function (Browser $browser) use ($user) {
        $browser->loginAs($user)
                ->visit('/settings/api-key')
                ->assertPresent('button');
    });
});
