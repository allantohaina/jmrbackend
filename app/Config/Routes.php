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
        $routes->get('exchange-rates', 'ExchangeRate::index');

        // Suivi de commande public + lien de paiement public
        $routes->post('public/suivi-commande', 'PublicController::suiviCommande', ['filter' => 'ratelimit:auth']);
        $routes->get('public/lien-paiement/(:any)', 'PublicController::lienInfo/$1');
        $routes->post('public/lien-paiement/(:any)/payer', 'PublicController::lienPayer/$1', ['filter' => 'ratelimit:auth']);

        // Avis publics (liste approuvés)
        $routes->get('produits/(:segment)/avis', 'Avis::publicList/$1');

        $routes->post('users/register', 'Users::register', ['filter' => 'ratelimit:auth']);
        $routes->post('users/login', 'Users::login', ['filter' => 'ratelimit:login']);
        $routes->post('users/refresh', 'Users::refresh', ['filter' => 'ratelimit:auth']);
        $routes->post('users/logout', 'Users::logout', ['filter' => 'ratelimit:auth']);

    $routes->group('legal', function($routes) {
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
            $routes->get('clients-revenue', 'Users::clientsWithRevenue');
            $routes->post('worker', 'Users::createWorker');
            $routes->post('import-csv', 'Users::importCSV');
            $routes->get('(:segment)', 'Users::show/$1');
            $routes->put('(:segment)', 'Users::update/$1');
            $routes->delete('(:segment)', 'Users::delete/$1');
            $routes->put('(:segment)/privilege', 'Users::togglePrivilege/$1');
        });
    });

    $routes->group('admin/history', ['filter' => ['auth', 'admin']], function($routes) {
        $routes->get('audit', 'History::audit');
        $routes->get('tokens', 'History::tokens');
        $routes->get('projects', 'History::projects');
    });

    $routes->group('uploads', ['filter' => 'auth'], function($routes) {
        // Site and business images are only accepted from the backoffice.
        $routes->post('image', 'Uploads::image', ['filter' => ['admin', 'ratelimit:auth']]);
        $routes->post('document', 'Uploads::document', ['filter' => 'ratelimit:auth']);
    });

    // Production Checklists
    $routes->group('checklists', ['filter' => ['auth', 'staff']], function($routes) {
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
    $routes->group('workflows', ['filter' => ['auth', 'staff']], function($routes) {
        $routes->get('/', 'ProductionWorkflows::index');
        $routes->post('/', 'ProductionWorkflows::create');
        $routes->get('kanban', 'ProductionWorkflows::kanban');
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
            $routes->post('(:segment)/notify', 'Quotes::notify/$1');
            $routes->post('(:segment)/confirm', 'Quotes::confirm/$1');
            $routes->post('(:segment)/convert-to-commande', 'Quotes::convertToCommande/$1');
            $routes->get('/', 'Quotes::index');
            $routes->get('(:segment)', 'Quotes::show/$1');
            $routes->put('(:segment)/status', 'Quotes::updateStatus/$1');
            $routes->put('(:segment)', 'Quotes::update/$1');
            $routes->put('(:segment)/sign', 'Quotes::sign/$1');
            $routes->delete('(:segment)', 'Quotes::remove/$1');
        });
    });

    // Quote Checkpoints
    $routes->group('quote-checkpoints', ['filter' => ['auth', 'staff']], function($routes) {
        $routes->get('/', 'QuoteCheckpoints::index');
        $routes->get('(:segment)', 'QuoteCheckpoints::show/$1');
        $routes->post('/', 'QuoteCheckpoints::create');
        $routes->put('(:segment)/validate', 'QuoteCheckpoints::validateCheckpoint/$1');
        $routes->delete('(:segment)', 'QuoteCheckpoints::delete/$1');
    });

    // Quote Addons
    $routes->group('quote-addons', ['filter' => ['auth', 'staff']], function($routes) {
        $routes->get('/', 'QuoteAddons::index');
        $routes->get('(:segment)', 'QuoteAddons::show/$1');
        $routes->post('/', 'QuoteAddons::create');
        $routes->put('(:segment)/status', 'QuoteAddons::updateStatus/$1');
        $routes->delete('(:segment)', 'QuoteAddons::delete/$1');
    });

    // Payments
    $routes->group('payments', ['filter' => 'auth'], function($routes) {
        $routes->get('/', 'PaymentsController::index');
        $routes->get('(:segment)', 'PaymentsController::show/$1');
        $routes->put('(:segment)/status', 'PaymentsController::updateStatus/$1');
    });

    $routes->post('quotes/(:segment)/payments', 'PaymentsController::submitForQuote/$1', ['filter' => 'auth']);

    $routes->group('notifications', ['filter' => 'auth'], function($routes) {
        $routes->get('/', 'Notifications::index');
        $routes->put('read-all', 'Notifications::markAllRead');
        $routes->put('(:segment)/read', 'Notifications::markRead/$1');
    });

    $routes->group('push-subscriptions', ['filter' => 'auth'], function($routes) {
        $routes->get('/', 'PushSubscriptions::index');
        $routes->post('/', 'PushSubscriptions::create');
        $routes->post('test', 'PushSubscriptions::test');
        $routes->delete('(:segment)', 'PushSubscriptions::remove/$1');
    });

    $routes->group('admin', ['filter' => ['auth', 'admin']], function($routes) {
        $routes->get('bans', 'Bans::index');
        $routes->get('bans/(:segment)', 'Bans::show/$1');
        $routes->get('bans/user/(:segment)', 'Bans::userBans/$1');
        $routes->post('bans', 'Bans::create');
        $routes->delete('bans/(:segment)', 'Bans::delete/$1');

        $routes->get('blacklist', 'Blacklist::index');
        $routes->post('blacklist', 'Blacklist::create');
        $routes->delete('blacklist/(:segment)', 'Blacklist::delete/$1');

        $routes->post('truncate', 'AdminData::truncateTestData', ['filter' => ['auth', 'admin']]);
    });

    // Site content (public read, admin write)
    $routes->get('content', 'Content::index');
    $routes->post('content/publish', 'Content::publish', ['filter' => ['auth', 'admin']]);
    $routes->get('content/history', 'Content::history', ['filter' => ['auth', 'admin']]);
    $routes->post('content/history/(:num)/restore', 'Content::restore/$1', ['filter' => ['auth', 'admin']]);
    $routes->put('content/(:any)', 'Content::update/$1', ['filter' => ['auth', 'admin']]);
    $routes->delete('content/(:any)', 'Content::remove/$1', ['filter' => ['auth', 'admin']]);

    // Production Assemblages
    $routes->group('assemblages', ['filter' => ['auth', 'staff']], function($routes) {
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
        $routes->post('/', 'Commandes::create', ['filter' => 'staff']);
        $routes->put('(:segment)', 'Commandes::update/$1', ['filter' => 'staff']);
        $routes->delete('(:segment)', 'Commandes::delete/$1', ['filter' => 'staff']);
        $routes->put('(:segment)/sign', 'Commandes::sign/$1', ['filter' => 'staff']);
        $routes->get('(:segment)/recu', 'CommandesExtras::recuData/$1', ['filter' => 'staff']);
        $routes->get('(:segment)/recu.pdf', 'CommandesExtras::recuPdf/$1', ['filter' => 'staff']);
        $routes->post('(:segment)/lien-paiement', 'CommandesExtras::lienPaiement/$1', ['filter' => 'staff']);
        $routes->get('(:segment)/qr-data', 'CommandesExtras::qrData/$1', ['filter' => 'staff']);
    });

    // Achats (Purchases)
    $routes->group('achats', ['filter' => ['auth', 'staff']], function($routes) {
        $routes->get('/', 'Achats::index');
        $routes->get('(:segment)', 'Achats::show/$1');
        $routes->post('/', 'Achats::create');
        $routes->put('(:segment)', 'Achats::update/$1');
        $routes->delete('(:segment)', 'Achats::delete/$1');
    });

    // Bons de livraison (Delivery Notes)
    $routes->group('bon-livraison', ['filter' => ['auth', 'staff']], function($routes) {
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
        $routes->post('/', 'Produits::create', ['filter' => 'staff']);
        $routes->put('(:segment)', 'Produits::update/$1', ['filter' => 'staff']);
        $routes->delete('(:segment)', 'Produits::delete/$1', ['filter' => 'staff']);
        $routes->post('(:segment)/avis', 'Avis::submit/$1');
    });

    // Matières premières + stock
    $routes->group('matieres', ['filter' => ['auth', 'staff']], function($routes) {
        $routes->get('/', 'Matieres::index');
        $routes->get('alertes', 'Matieres::alertes');
        $routes->post('/', 'Matieres::create');
        $routes->post('mouvements', 'Matieres::mouvement');
        $routes->get('(:segment)', 'Matieres::show/$1');
        $routes->put('(:segment)', 'Matieres::update/$1');
        $routes->delete('(:segment)', 'Matieres::delete/$1');
    });

    // Avis clients (modération)
    $routes->group('avis', ['filter' => ['auth', 'staff']], function($routes) {
        $routes->get('/', 'Avis::index');
        $routes->put('(:segment)/statut', 'Avis::updateStatut/$1', ['filter' => 'admin']);
    });

    // Statistiques (admin)
    $routes->get('stats/dashboard', 'Stats::dashboard', ['filter' => ['auth', 'admin']]);

    // Exports CSV (staff)
    $routes->group('exports', ['filter' => ['auth', 'staff']], function($routes) {
        $routes->get('devis', 'Exports::devis');
        $routes->get('commandes', 'Exports::commandes');
        $routes->get('paiements', 'Exports::paiements');
    });

    // Points fidélité (client)
    $routes->get('moi/points', 'Points::mine', ['filter' => 'auth']);

    // Demandes client
    $routes->group('demandes-client', ['filter' => 'auth'], function($routes) {
        $routes->get('/', 'DemandesClient::index');
        $routes->get('pending-count', 'DemandesClient::pendingCount');
        $routes->get('(:segment)', 'DemandesClient::show/$1');
        $routes->post('/', 'DemandesClient::create');
        $routes->put('(:segment)', 'DemandesClient::update/$1', ['filter' => 'staff']);
        $routes->put('(:segment)/refuse', 'DemandesClient::refuse/$1', ['filter' => 'staff']);
    });

    // Attachments (pièces jointes)
    $routes->group('attachments', ['filter' => 'auth'], function($routes) {
        $routes->get('/', 'Attachments::index');
        $routes->get('(:segment)', 'Attachments::show/$1');
        $routes->post('/', 'Attachments::create');
        $routes->delete('(:segment)', 'Attachments::delete/$1');
    });

    // Admin reset (protected by secret key + ratelimit, not auth)
    $routes->post('admin/reset-password', 'AdminReset::resetPassword', ['filter' => 'ratelimit:auth']);
    $routes->get('admin/users', 'AdminReset::listUsers', ['filter' => 'ratelimit:auth']);

    // Catch-all OPTIONS for CORS preflight - must be last in api group
    $routes->options('(:any)', static function () {
        return service('response')->setStatusCode(204);
    });
});

    // Public file serving for uploaded reference files (images / PDF / CSV)
    // Files are stored in writable/uploads and referenced by base_url('uploads/...').
    $routes->get('uploads/(:any)', 'Uploads::serve/$1');
