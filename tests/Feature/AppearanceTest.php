<?php

it('renders light mode by default', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertDontSee('class="dark"', escape: false);
});

it('renders dark mode when the user chose it', function () {
    $this->withUnencryptedCookie('appearance', 'dark')
        ->get(route('home'))
        ->assertOk()
        ->assertSee('class="dark"', escape: false);
});

it('ignores the former system preference and falls back to light mode', function () {
    $this->withUnencryptedCookie('appearance', 'system')
        ->get(route('home'))
        ->assertOk()
        ->assertDontSee('class="dark"', escape: false);
});
