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

        $routes->options('users/login', static function () {
            return service('response')->setStatusCode(204);
        });
        $routes->options('users/profile', static function () {
            return service('response')->setStatusCode(204);
        });
        $routes->options('users/logout', static function () {
            return service('response')->setStatusCode(204);
        });
        // Whitelisted CORS preflight — explicit, no catch-all
        $routes->options('users/register', static function () { return service('response')->setStatusCode(204); });
        $routes->options('users/refresh', static function () { return service('response')->setStatusCode(204); });
        $routes->options('users/worker', static function () { return service('response')->setStatusCode(204); });
        $routes->options('users/import-csv', static function () { return service('response')->setStatusCode(204); });
        $routes->options('users/clients-revenue', static function () { return service('response')->setStatusCode(204); });
        $routes->options('users/(:segment)', static function () { return service('response')->setStatusCode(204); });
        $routes->options('users/(:segment)/privilege', static function () { return service('response')->setStatusCode(204); });
        $routes->options('notifications', static function () { return service('response')->setStatusCode(204); });
        $routes->options('notifications/read-all', static function () { return service('response')->setStatusCode(204); });
        $routes->options('notifications/(:segment)/read', static function () { return service('response')->setStatusCode(204); });
        $routes->options('quotes', static function () { return service('response')->setStatusCode(204); });
        $routes->options('quotes/(:segment)', static function () { return service('response')->setStatusCode(204); });
        $routes->options('quotes/(:segment)/status', static function () { return service('response')->setStatusCode(204); });
        $routes->options('quotes/(:segment)/sign', static function () { return service('response')->setStatusCode(204); });
        $routes->options('quotes/share/(:any)', static function () { return service('response')->setStatusCode(204); });
        $routes->options('quotes/notifications', static function () { return service('response')->setStatusCode(204); });
        $routes->options('quotes/notifications/(:segment)/read', static function () { return service('response')->setStatusCode(204); });
        $routes->options('quotes/(:segment)/notify', static function () { return service('response')->setStatusCode(204); });
        $routes->options('quotes/(:segment)/confirm', static function () { return service('response')->setStatusCode(204); });
        $routes->options('quotes/(:segment)/convert-to-commande', static function () { return service('response')->setStatusCode(204); });
        $routes->options('quotes/(:segment)/payments', static function () { return service('response')->setStatusCode(204); });
        $routes->options('produits', static function () { return service('response')->setStatusCode(204); });
        $routes->options('produits/categories', static function () { return service('response')->setStatusCode(204); });
        $routes->options('produits/(:segment)', static function () { return service('response')->setStatusCode(204); });
        $routes->options('produits/(:segment)/avis', static function () { return service('response')->setStatusCode(204); });
        $routes->options('commandes', static function () { return service('response')->setStatusCode(204); });
        $routes->options('commandes/(:segment)', static function () { return service('response')->setStatusCode(204); });
        $routes->options('commandes/(:segment)/sign', static function () { return service('response')->setStatusCode(204); });
        $routes->options('commandes/(:segment)/recu', static function () { return service('response')->setStatusCode(204); });
        $routes->options('commandes/(:segment)/recu.pdf', static function () { return service('response')->setStatusCode(204); });
        $routes->options('commandes/(:segment)/lien-paiement', static function () { return service('response')->setStatusCode(204); });
        $routes->options('commandes/(:segment)/qr-data', static function () { return service('response')->setStatusCode(204); });
        $routes->options('matieres', static function () { return service('response')->setStatusCode(204); });
        $routes->options('matieres/(:segment)', static function () { return service('response')->setStatusCode(204); });
        $routes->options('matieres/alertes', static function () { return service('response')->setStatusCode(204); });
        $routes->options('matieres/mouvements', static function () { return service('response')->setStatusCode(204); });
        $routes->options('avis', static function () { return service('response')->setStatusCode(204); });
        $routes->options('avis/(:segment)/statut', static function () { return service('response')->setStatusCode(204); });
        $routes->options('exports/devis', static function () { return service('response')->setStatusCode(204); });
        $routes->options('exports/commandes', static function () { return service('response')->setStatusCode(204); });
        $routes->options('exports/paiements', static function () { return service('response')->setStatusCode(204); });
        $routes->options('moi/points', static function () { return service('response')->setStatusCode(204); });
        $routes->options('demandes-client', static function () { return service('response')->setStatusCode(204); });
        $routes->options('demandes-client/pending-count', static function () { return service('response')->setStatusCode(204); });
        $routes->options('demandes-client/(:segment)', static function () { return service('response')->setStatusCode(204); });
        $routes->options('demandes-client/(:segment)/refuse', static function () { return service('response')->setStatusCode(204); });
        $routes->options('push-subscriptions', static function () { return service('response')->setStatusCode(204); });
        $routes->options('push-subscriptions/test', static function () { return service('response')->setStatusCode(204); });
        $routes->options('push-subscriptions/(:segment)', static function () { return service('response')->setStatusCode(204); });
        $routes->options('quote-checkpoints', static function () { return service('response')->setStatusCode(204); });
        $routes->options('quote-checkpoints/(:segment)', static function () { return service('response')->setStatusCode(204); });
        $routes->options('quote-checkpoints/(:segment)/validate', static function () { return service('response')->setStatusCode(204); });
        $routes->options('quote-addons', static function () { return service('response')->setStatusCode(204); });
        $routes->options('quote-addons/(:segment)', static function () { return service('response')->setStatusCode(204); });
        $routes->options('quote-addons/(:segment)/status', static function () { return service('response')->setStatusCode(204); });
        $routes->options('payments', static function () { return service('response')->setStatusCode(204); });
        $routes->options('payments/(:segment)', static function () { return service('response')->setStatusCode(204); });
        $routes->options('payments/(:segment)/status', static function () { return service('response')->setStatusCode(204); });
        $routes->options('assemblages', static function () { return service('response')->setStatusCode(204); });
        $routes->options('assemblages/(:segment)', static function () { return service('response')->setStatusCode(204); });
        $routes->options('achats', static function () { return service('response')->setStatusCode(204); });
        $routes->options('achats/(:segment)', static function () { return service('response')->setStatusCode(204); });
        $routes->options('bon-livraison', static function () { return service('response')->setStatusCode(204); });
        $routes->options('bon-livraison/(:segment)', static function () { return service('response')->setStatusCode(204); });
        $routes->options('bon-livraison/(:segment)/sign', static function () { return service('response')->setStatusCode(204); });
        $routes->options('checklists', static function () { return service('response')->setStatusCode(204); });
        $routes->options('checklists/(:segment)', static function () { return service('response')->setStatusCode(204); });
        $routes->options('checklists/(:segment)/value', static function () { return service('response')->setStatusCode(204); });
        $routes->options('checklists/initialize', static function () { return service('response')->setStatusCode(204); });
        $routes->options('checklists/initialize-command', static function () { return service('response')->setStatusCode(204); });
        $routes->options('checklists/initialize-delivery', static function () { return service('response')->setStatusCode(204); });
        $routes->options('checklists/project/(:segment)', static function () { return service('response')->setStatusCode(204); });
        $routes->options('workflows', static function () { return service('response')->setStatusCode(204); });
        $routes->options('workflows/kanban', static function () { return service('response')->setStatusCode(204); });
        $routes->options('workflows/(:segment)', static function () { return service('response')->setStatusCode(204); });
        $routes->options('workflows/(:segment)/transition', static function () { return service('response')->setStatusCode(204); });
        $routes->options('stats/dashboard', static function () { return service('response')->setStatusCode(204); });
        $routes->options('attachments', static function () { return service('response')->setStatusCode(204); });
        $routes->options('attachments/(:segment)', static function () { return service('response')->setStatusCode(204); });
        $routes->options('uploads/image', static function () { return service('response')->setStatusCode(204); });
        $routes->options('uploads/document', static function () { return service('response')->setStatusCode(204); });
        $routes->options('content', static function () { return service('response')->setStatusCode(204); });
        $routes->options('content/publish', static function () { return service('response')->setStatusCode(204); });
        $routes->options('content/history', static function () { return service('response')->setStatusCode(204); });
        $routes->options('content/history/(:num)/restore', static function () { return service('response')->setStatusCode(204); });
        $routes->options('content/(:any)', static function () { return service('response')->setStatusCode(204); });
        $routes->options('admin/bans', static function () { return service('response')->setStatusCode(204); });
        $routes->options('admin/bans/(:segment)', static function () { return service('response')->setStatusCode(204); });
        $routes->options('admin/bans/user/(:segment)', static function () { return service('response')->setStatusCode(204); });
        $routes->options('admin/blacklist', static function () { return service('response')->setStatusCode(204); });
        $routes->options('admin/blacklist/(:segment)', static function () { return service('response')->setStatusCode(204); });
        $routes->options('admin/truncate', static function () { return service('response')->setStatusCode(204); });
        $routes->options('admin/history/audit', static function () { return service('response')->setStatusCode(204); });
        $routes->options('admin/history/tokens', static function () { return service('response')->setStatusCode(204); });
        $routes->options('admin/history/projects', static function () { return service('response')->setStatusCode(204); });
        $routes->options('admin/reset-password', static function () { return service('response')->setStatusCode(204); });
        $routes->options('admin/users', static function () { return service('response')->setStatusCode(204); });
        $routes->options('public/suivi-commande', static function () { return service('response')->setStatusCode(204); });
        $routes->options('public/lien-paiement/(:any)', static function () { return service('response')->setStatusCode(204); });
        $routes->options('public/lien-paiement/(:any)/payer', static function () { return service('response')->setStatusCode(204); });
        $routes->options('legal/consent', static function () { return service('response')->setStatusCode(204); });
        $routes->options('legal/data-request', static function () { return service('response')->setStatusCode(204); });
        $routes->options('exchange-rates', static function () { return service('response')->setStatusCode(204); });

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
});

    // Public file serving for uploaded reference files (images / PDF / CSV)
    // Files are stored in writable/uploads and referenced by base_url('uploads/...').
    $routes->get('uploads/(:any)', 'Uploads::serve/$1');
