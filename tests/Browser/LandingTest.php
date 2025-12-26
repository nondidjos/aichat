<?php

use Laravel\Dusk\Browser;

test('landing page loads', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/')
                ->assertSee('Assistant');
    });
});

test('landing page shows agents', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/')
                ->assertSee('Coder')
                ->assertSee('Creative')
                ->assertSee('Analyst');
    });
});

test('landing page has login link', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/')
                ->assertPresent('a[href*="login"]');
    });
});

test('logo is visible', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/')
                ->assertPresent('img');
    });
});
