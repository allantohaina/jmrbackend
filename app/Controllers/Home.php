<?php

namespace App\Controllers;

class Home extends BaseController
{
    /**
     * Root route is disabled - returns 403.
     * All API routes are under /api/ prefix.
     */
    public function index()
    {
        return $this->response->setStatusCode(403)->setBody('Forbidden');
    }
}
