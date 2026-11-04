<?php
// error_type/403.php — custom forbidden page
http_response_code(403);
header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex">
    <title>403 — Permission Denied</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: #0f172a;
            color: #e2e8f0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .card {
            max-width: 480px;
            text-align: center;
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 16px;
            padding: 48px 32px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.4);
        }
        .code {
            font-size: 72px;
            font-weight: 800;
            letter-spacing: -2px;
            color: #f87171;
            line-height: 1;
            margin-bottom: 16px;
        }
        h1 {
            font-size: 22px;
            font-weight: 600;
            margin-bottom: 12px;
        }
        p {
            color: #94a3b8;
            font-size: 15px;
            line-height: 1.6;
            margin-bottom: 28px;
        }
        a {
            display: inline-block;
            padding: 12px 24px;
            background: #3b82f6;
            color: #fff;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
            font-size: 15px;
            transition: background 0.2s;
        }
        a:hover { background: #2563eb; }
    </style>
</head>
<body>
    <div class="card">
        <div class="code">403</div>
        <h1>Access Denied</h1>
        <p>Sorry, you don't have permission to view this page or directory.</p>
        <a href="/index.php">← Back to Home</a>
    </div>
</body>
</html>