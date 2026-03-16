# JMR Textile Backend

Backend API for JMR Textile, built with CodeIgniter 4.

## Overview
This service provides:
- JWT authentication (register, login, refresh, logout)
- User profile management
- Admin user management
- Legal endpoints (privacy, terms, cookies, etc.)
- Audit/history endpoints
- Authenticated file upload endpoints

## Tech Stack
- PHP 8.2+
- CodeIgniter 4.7
- Composer
- PostgreSQL (default app DB)
- SQLite in-memory for automated tests

## Quick Start
1. Install dependencies:
```bash
composer install
```
2. Create your environment file:
```bash
# PowerShell
Copy-Item env .env

# Bash
cp env .env
```
3. Configure `.env` (at minimum):
- `CI_ENVIRONMENT`
- `app.baseURL`
- `database.default.*`
- `JWT_SECRET_KEY`
4. Run migrations:
```bash
php spark migrate
```
5. Start the local server:
```bash
php spark serve
```

Local API base URL:
```text
http://localhost:8080/api
```

## Main Routes
Public/Auth:
- `POST /api/users/register`
- `POST /api/users/login`
- `POST /api/users/refresh`
- `POST /api/users/logout`

Authenticated user:
- `GET /api/users/profile`
- `PUT /api/users/profile`
- `DELETE /api/users/profile`

Admin:
- `GET /api/users`
- `GET /api/users/{id}`
- `PUT /api/users/{id}`
- `DELETE /api/users/{id}`
- `GET /api/admin/history/audit`
- `GET /api/admin/history/tokens`
- `GET /api/admin/history/projects`

Legal:
- `GET /api/legal/privacy`
- `GET /api/legal/terms`
- `GET /api/legal/cookies`
- `GET /api/legal/disclaimer`
- `GET /api/legal/accessibility`
- `GET /api/legal/legal-notice`
- `POST /api/legal/consent`
- `POST /api/legal/data-request`

Uploads (auth required):
- `POST /api/uploads/image`
- `POST /api/uploads/document`

## Useful Commands
```bash
php spark migrate:status
composer test
```

## Project Structure
- `app/Controllers` HTTP controllers
- `app/Application` business services
- `app/Models` persistence layer
- `app/History` audit/history logging
- `app/Filters` auth, admin, firewall, rate limiting
- `app/Config` runtime configuration

## Documentation
- `API_DOCUMENTATION.md`
- `GETTING_STARTED.md`

## Production Notes
- Set `CI_ENVIRONMENT=production`
- Use a strong `JWT_SECRET_KEY`
- Restrict CORS origins in `app/Config/Cors.php`
- Enable HTTPS and secure proxy settings
- Keep debug output disabled in production
