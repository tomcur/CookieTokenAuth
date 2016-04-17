<?php
use Cake\Routing\Router;

Router::plugin(
    'Beskhue/CookieTokenAuth',
    ['path' => '/auth'], 
    function ($routes) {
        $routes->connect('/cookie-token-auth', ['controller' => 'CookieTokenAuth']);
    }
);