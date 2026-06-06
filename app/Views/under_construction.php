<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Site en construction</title>
    <style>
        :root {
            color-scheme: light;
            --bg: #f7f3ec;
            --ink: #202020;
            --muted: #62615d;
            --accent: #8b1e3f;
            --panel: #ffffff;
            --line: #ded8cf;
        }

        * {
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            margin: 0;
            display: grid;
            place-items: center;
            padding: 24px;
            background:
                linear-gradient(135deg, rgba(139, 30, 63, 0.08), transparent 38%),
                var(--bg);
            color: var(--ink);
            font-family: Arial, Helvetica, sans-serif;
        }

        main {
            width: min(100%, 560px);
            padding: 40px;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: var(--panel);
            box-shadow: 0 18px 45px rgba(32, 32, 32, 0.08);
            text-align: center;
        }

        .mark {
            width: 56px;
            height: 56px;
            display: inline-grid;
            place-items: center;
            margin-bottom: 22px;
            border-radius: 50%;
            background: var(--accent);
            color: #ffffff;
            font-size: 26px;
            font-weight: 700;
        }

        h1 {
            margin: 0 0 12px;
            font-size: 32px;
            line-height: 1.15;
            letter-spacing: 0;
        }

        p {
            margin: 0;
            color: var(--muted);
            font-size: 16px;
            line-height: 1.6;
        }

        @media (max-width: 520px) {
            main {
                padding: 30px 22px;
            }

            h1 {
                font-size: 26px;
            }
        }
    </style>
</head>
<body>
    <main>
        <div class="mark" aria-hidden="true">J</div>
        <h1>Site en construction</h1>
        <p>Nous préparons cette page. Merci de revenir bientôt.</p>
    </main>
</body>
</html>
