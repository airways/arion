<?php
logger()->debug('===== '.$_SERVER['REQUEST_URI'].' =====');

// Public controllers
$routes->group(['namespace' => 'app\controllers'], function($routes)
{
    $routes->get('^$', 'Items::index', 'root');
    $routes->get('^/$', 'Items::index', 'root');
    $routes->get('/auth/login', 'Auth::login', 'auth.login');
    $routes->post('/auth/login', 'Auth::login', 'auth.login.post');

    $routes->get('/auth/forgot_password', 'Auth::forgot_password', 'auth.forgot_password');
    $routes->post('/auth/forgot_password', 'Auth::forgot_password', 'auth.forgot_password.post');

    $routes->get('/auth/register', 'Auth::register', 'auth.register');
    $routes->post('/auth/register', 'Auth::register', 'auth.register.post');

    $routes->get('/auth/logout', 'Auth::logout', 'auth.logout');
    $routes->post('/auth/logout', 'Auth::logout', 'auth.logout.post');

    $routes->get('/emailchecker/check', 'EmailChecker::check');
});

// Authenticated controllers
$routes->group(['before' => 'authenticated', 'namespace' => 'app\controllers'], function($routes)
{
    $routes->get('/items', 'Items::index', 'items.index');
    $routes->get('/items/{itemType}/create', 'Items::create', 'items.create');
    $routes->get('/items/{itemType}', 'Items::view', 'items.view');
    $routes->post('/items/{itemType}', 'Items::edit', 'items.edit');
    //$routes->get('/items/{itemType}/(?P<filters>[^*]*)', 'Items::view', 'items.view.filtered');
    //$routes->post('/items/{itemType}/(?P<filters>[^*]*)', 'Items::edit', 'items.edit.filtered');
    $routes->get('/mailbox', 'Items::index', 'mailbox');
    //$routes->get('/mailbox/(?P<filters>[^*]*)', 'Mailbox::index', 'mailbox.filtered');
});
