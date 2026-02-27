<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Link Expired</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-1: #fefce8;
            --bg-2: #ffedd5;
            --card: #ffffff;
            --text: #0f172a;
            --muted: #475569;
            --accent: #f59e0b;
            --warn: #ea580c;
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
                radial-gradient(circle at 12% 14%, #fef3c7 0, transparent 32%),
                radial-gradient(circle at 80% 84%, #fed7aa 0, transparent 33%),
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
            background: var(--warn);
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
        <span class="badge">Link Expired</span>
        <h1>This Share Link Is Expired</h1>
        <p>The link is no longer valid because its expiry date has passed. Please request a fresh link.</p>
        <div class="actions">
            <a class="btn btn-primary" href="/">Go to Login</a>
            <a class="btn btn-secondary" href="javascript:history.back()">Go Back</a>
        </div>
    </section>
</body>
</html>
