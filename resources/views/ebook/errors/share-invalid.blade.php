<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Denied</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-1: #f0f9ff;
            --bg-2: #f1f5f9;
            --card: #ffffff;
            --text: #0f172a;
            --muted: #475569;
            --accent: #0ea5e9;
            --danger: #b91c1c;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            font-family: "Outfit", sans-serif;
            color: var(--text);
            background:
                radial-gradient(circle at 18% 15%, #dbeafe 0, transparent 35%),
                radial-gradient(circle at 84% 82%, #e2e8f0 0, transparent 35%),
                linear-gradient(135deg, var(--bg-1), var(--bg-2));
            padding: 20px;
        }
        .card {
            width: min(560px, 100%);
            background: var(--card);
            border: 1px solid #e2e8f0;
            border-radius: 18px;
            padding: 28px;
            box-shadow: 0 20px 55px rgba(2, 6, 23, 0.14);
        }
        .badge {
            display: inline-block;
            font-size: 13px;
            font-weight: 600;
            color: #fff;
            background: var(--danger);
            border-radius: 999px;
            padding: 7px 12px;
            margin-bottom: 12px;
        }
        h1 {
            margin: 0 0 10px;
            font-size: 30px;
            line-height: 1.15;
        }
        p {
            margin: 0;
            color: var(--muted);
            font-size: 17px;
            line-height: 1.6;
        }
        .actions {
            margin-top: 22px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .btn {
            text-decoration: none;
            border-radius: 10px;
            padding: 10px 15px;
            font-size: 15px;
            font-weight: 600;
            transition: transform 0.2s ease;
        }
        .btn-primary {
            background: var(--accent);
            color: #fff;
        }
        .btn-secondary {
            background: #f8fafc;
            color: #0f172a;
            border: 1px solid #cbd5e1;
        }
        .btn:hover { transform: translateY(-1px); }
    </style>
</head>
<body>
    <section class="card">
        <span class="badge">Access Denied</span>
        <h1>This Share Link Is Invalid</h1>
        <p>This link is disabled, deleted, or incorrect. Ask the sender to share a valid link again.</p>
        <div class="actions">
            <a class="btn btn-primary" href="/">Go to Login</a>
            <a class="btn btn-secondary" href="javascript:history.back()">Go Back</a>
        </div>
    </section>
</body>
</html>
