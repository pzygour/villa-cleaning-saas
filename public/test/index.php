<?php

declare(strict_types=1);

// DEV ONLY
$devMode = true;
if (!$devMode) {
    http_response_code(403);
    exit('Forbidden');
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Test Dashboard</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 30px;
            background: #f5f5f5;
            color: #222;
        }
        h1 {
            margin-bottom: 10px;
        }
        .card {
            background: #fff;
            padding: 20px;
            margin-bottom: 16px;
            border-radius: 8px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.08);
        }
        a.button {
            display: inline-block;
            padding: 10px 14px;
            background: #1f6feb;
            color: #fff;
            text-decoration: none;
            border-radius: 6px;
            margin-right: 8px;
            margin-bottom: 8px;
        }
        a.button:hover {
            background: #1558c0;
        }
        code {
            background: #eee;
            padding: 2px 6px;
            border-radius: 4px;
        }
    </style>
</head>
<body>
<h1>System Test Dashboard</h1>
<p>Use these tools only in local/dev environment.</p>

<div class="card">
    <h2>Workflow tests</h2>
    <a class="button" href="full-workflow.php">Full workflow test</a>
</div>

<div class="card">
    <h2>Suggested next pages</h2>
    <p>Create later if needed:</p>
    <ul>
        <li><code>cleaning.php</code></li>
        <li><code>requirements.php</code></li>
        <li><code>inventory.php</code></li>
        <li><code>laundry.php</code></li>
        <li><code>state.php</code></li>
    </ul>
</div>
</body>
</html>