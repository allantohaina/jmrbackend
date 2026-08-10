<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
// Root route - disabled, returns 403
$routes->get('/', static function () {
    return service('response')->setStatusCode(403)->setBody('Forbidden');
});

    // Public routes (no authentication required)
    $routes->group('api', ['namespace' => 'App\Controllers'], function($routes) {
        $routes->options('(:any)', static function () {
            return service('response')->setStatusCode(204);
        });

        $routes->get('exchange-rates', 'ExchangeRate::index');

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

    // Production Checklists
    $routes->group('checklists', ['filter' => 'auth'], function($routes) {
        $routes->post('/', 'Checklists::create');
        $routes->post('initialize', 'Checklists::initialize');
        $routes->post('initialize-command', 'Checklists::initializeCommand');
        $routes->post('initialize-delivery', 'Checklists::initializeDelivery');
        $routes->get('(:segment)', 'Checklists::show/$1');
        $routes->put('(:segment)', 'Checklists::update/$1');
        $routes->delete('(:segment)/value', 'Checklists::removeValue');
        $routes->get('project/(:segment)', 'Checklists::project/$1');
    });

    // Production Workflows
    $routes->group('workflows', ['filter' => 'auth'], function($routes) {
        $routes->get('/', 'ProductionWorkflows::index');
        $routes->post('/', 'ProductionWorkflows::create');
        $routes->get('(:segment)', 'ProductionWorkflows::show/$1');
        $routes->put('(:segment)', 'ProductionWorkflows::update/$1');
        $routes->post('(:segment)/transition', 'ProductionWorkflows::transition/$1');
    });

    // Quotes
    $routes->group('quotes', function($routes) {
        $routes->post('/', 'Quotes::create', ['filter' => 'optionalauth']);
        $routes->get('share/(:any)', 'Quotes::share/$1'); // Public share by hash
        $routes->group('', ['filter' => 'auth'], function($routes) {
            $routes->get('notifications', 'Quotes::notifications');
            $routes->put('notifications/(:segment)/read', 'Quotes::markNotificationRead/$1');
            $routes->post('(:segment)/confirm', 'Quotes::confirm/$1');
            $routes->post('(:segment)/convert-to-commande', 'Quotes::convertToCommande/$1');
            $routes->get('/', 'Quotes::index');
            $routes->get('(:segment)', 'Quotes::show/$1');
            $routes->put('(:segment)/status', 'Quotes::updateStatus/$1');
            $routes->put('(:segment)/sign', 'Quotes::sign/$1');
        });
    });

    $routes->group('notifications', ['filter' => 'auth'], function($routes) {
        $routes->get('/', 'Notifications::index');
        $routes->put('read-all', 'Notifications::markAllRead');
        $routes->put('(:segment)/read', 'Notifications::markRead/$1');
    });

    $routes->group('admin', ['filter' => 'admin'], function($routes) {
        $routes->get('bans', 'Bans::index');
        $routes->get('bans/(:segment)', 'Bans::show/$1');
        $routes->get('bans/user/(:segment)', 'Bans::userBans/$1');
        $routes->post('bans', 'Bans::create');
        $routes->delete('bans/(:segment)', 'Bans::delete/$1');

        $routes->get('blacklist', 'Blacklist::index');
        $routes->post('blacklist', 'Blacklist::create');
        $routes->delete('blacklist/(:segment)', 'Blacklist::delete/$1');
    });

    // Site content (public read, admin write)
    $routes->get('content', 'Content::index');
    $routes->put('content/(:any)', 'Content::update/$1', ['filter' => 'auth']);

    // Production Assemblages
    $routes->group('assemblages', ['filter' => 'auth'], function($routes) {
        $routes->get('/', 'Assemblages::index');
        $routes->get('(:segment)', 'Assemblages::show/$1');
        $routes->post('/', 'Assemblages::create');
        $routes->put('(:segment)', 'Assemblages::update/$1');
        $routes->delete('(:segment)', 'Assemblages::delete/$1');
    });

    // Commandes (Orders)
    $routes->group('commandes', ['filter' => 'auth'], function($routes) {
        $routes->get('/', 'Commandes::index');
        $routes->get('(:segment)', 'Commandes::show/$1');
        $routes->post('/', 'Commandes::create');
        $routes->put('(:segment)', 'Commandes::update/$1');
        $routes->delete('(:segment)', 'Commandes::delete/$1');
        $routes->put('(:segment)/sign', 'Commandes::sign/$1');
    });

    // Achats (Purchases)
    $routes->group('achats', ['filter' => 'auth'], function($routes) {
        $routes->get('/', 'Achats::index');
        $routes->get('(:segment)', 'Achats::show/$1');
        $routes->post('/', 'Achats::create');
        $routes->put('(:segment)', 'Achats::update/$1');
        $routes->delete('(:segment)', 'Achats::delete/$1');
    });

    // Bons de livraison (Delivery Notes)
    $routes->group('bon-livraison', ['filter' => 'auth'], function($routes) {
        $routes->get('/', 'BonLivraison::index');
        $routes->get('(:segment)', 'BonLivraison::show/$1');
        $routes->post('/', 'BonLivraison::create');
        $routes->put('(:segment)', 'BonLivraison::update/$1');
        $routes->delete('(:segment)', 'BonLivraison::delete/$1');
        $routes->put('(:segment)/sign', 'BonLivraison::sign/$1');
    });

    // Produits (catalogue)
    $routes->group('produits', ['filter' => 'auth'], function($routes) {
        $routes->get('/', 'Produits::index');
        $routes->get('categories', 'Produits::categories');
        $routes->get('(:segment)', 'Produits::show/$1');
        $routes->post('/', 'Produits::create');
        $routes->put('(:segment)', 'Produits::update/$1');
        $routes->delete('(:segment)', 'Produits::delete/$1');
    });

    // Demandes client
    $routes->group('demandes-client', ['filter' => 'auth'], function($routes) {
        $routes->get('/', 'DemandesClient::index');
        $routes->get('pending-count', 'DemandesClient::pendingCount');
        $routes->get('(:segment)', 'DemandesClient::show/$1');
        $routes->post('/', 'DemandesClient::create');
        $routes->put('(:segment)', 'DemandesClient::update/$1');
        $routes->put('(:segment)/refuse', 'DemandesClient::refuse/$1');
    });

    // Attachments (pièces jointes)
    $routes->group('attachments', ['filter' => 'auth'], function($routes) {
        $routes->get('/', 'Attachments::index');
        $routes->get('(:segment)', 'Attachments::show/$1');
        $routes->post('/', 'Attachments::create');
        $routes->delete('(:segment)', 'Attachments::delete/$1');
    });

    // Admin reset (protected by secret key, not auth)
    $routes->post('admin/reset-password', 'AdminReset::resetPassword');
    $routes->get('admin/users', 'AdminReset::listUsers');
});
