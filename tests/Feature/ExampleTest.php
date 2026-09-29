<?php

test('returns a successful response', function () {
    $this->get(route('home'))->assertOk();
});
