<?php
/**
 * Custom error view - never exposes version info, source code, or debug details.
 * Returns a clean, minimal error page regardless of environment.
 */
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="robots" content="noindex">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Erreur</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f8f9fa;
            color: #333;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .error-container {
            text-align: center;
            padding: 3rem 2rem;
            max-width: 500px;
        }
        .error-code {
            font-size: 4rem;
            font-weight: 700;
            color: #dc3545;
            margin-bottom: 0.5rem;
        }
        .error-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #495057;
            margin-bottom: 1rem;
        }
        .error-message {
            font-size: 1rem;
            color: #6c757d;
            line-height: 1.6;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-code"><?= esc($exception->getCode() ?: 500) ?></div>
        <div class="error-title">Une erreur est survenue</div>
        <div class="error-message">
            <?php if (in_array($exception->getCode(), [404, 403, 401], true)): ?>
                La ressource demandée est introuvable.
            <?php else: ?>
                Veuillez réessayer plus tard.
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
