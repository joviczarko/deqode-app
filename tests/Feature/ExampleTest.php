<?php

test('the application redirects root to the tenant panel', function () {
    $this->get('/')->assertRedirect('/app');
});
