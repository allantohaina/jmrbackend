<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');

// Public routes (no authentication required)
$routes->group('api', ['namespace' => 'App\Controllers'], function($routes) {
    $routes->post('users/register', 'Users::register', ['filter' => 'ratelimit:auth']);
    $routes->post('users/login', 'Users::login', ['filter' => 'ratelimit:login']);
    $routes->post('users/refresh', 'Users::refresh', ['filter' => 'ratelimit:auth']);
    $routes->post('users/logout', 'Users::logout', ['filter' => 'ratelimit:auth']);

    $routes->group('legal', function($routes) {
        $routes->get('privacy', 'Legal::privacy');
        $routes->get('terms', 'Legal::terms');
        $routes->get('cookies', 'Legal::cookies');
        $routes->get('disclaimer', 'Legal::disclaimer');
        $routes->get('accessibility', 'Legal::accessibility');
        $routes->get('legal-notice', 'Legal::legalNotice');
        $routes->post('consent', 'Legal::consent');
        $routes->post('data-request', 'Legal::dataRequest');
    });
    
    // Protected routes (authentication required)
    $routes->group('users', ['filter' => 'auth'], function($routes) {
        $routes->get('profile', 'Users::profile');
        $routes->put('profile', 'Users::updateProfile');
        $routes->delete('profile', 'Users::deleteProfile');
        
        // Admin only routes
        $routes->group('', ['filter' => 'admin'], function($routes) {
            $routes->get('/', 'Users::index');
            $routes->get('(:segment)', 'Users::show/$1');
            $routes->put('(:segment)', 'Users::update/$1');
            $routes->delete('(:segment)', 'Users::delete/$1');
        });
    });

    $routes->group('admin/history', ['filter' => 'admin'], function($routes) {
        $routes->get('audit', 'History::audit');
        $routes->get('tokens', 'History::tokens');
        $routes->get('projects', 'History::projects');
    });

    $routes->group('uploads', ['filter' => 'auth'], function($routes) {
        $routes->post('image', 'Uploads::image', ['filter' => 'ratelimit:auth']);
        $routes->post('document', 'Uploads::document', ['filter' => 'ratelimit:auth']);
    });
});
