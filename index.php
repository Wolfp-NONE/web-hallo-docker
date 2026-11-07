<?php
declare(strict_types=1);

/**
 * Menampilkan identitas EC2 yang sedang melayani request.
 * Metadata diambil menggunakan IMDSv2.
 */

function getEc2Metadata(string $path): string
{
    $tokenHandle = curl_init('http://169.254.169.254/latest/api/token');

    curl_setopt_array($tokenHandle, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => 'PUT',
        CURLOPT_HTTPHEADER => [
            'X-aws-ec2-metadata-token-ttl-seconds: 21600',
        ],
        CURLOPT_CONNECTTIMEOUT => 2,
        CURLOPT_TIMEOUT => 3,
    ]);

    $token = curl_exec($tokenHandle);
    curl_close($tokenHandle);

    if (!is_string($token) || $token === '') {
        return 'Metadata unavailable';
    }

    $url = 'http://169.254.169.254/latest/meta-data/' . ltrim($path, '/');
    $handle = curl_init($url);

    curl_setopt_array($handle, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'X-aws-ec2-metadata-token: ' . $token,
        ],
        CURLOPT_CONNECTTIMEOUT => 2,
        CURLOPT_TIMEOUT => 3,
    ]);

    $result = curl_exec($handle);
    curl_close($handle);

    return is_string($result) && $result !== ''
        ? $result
        : 'Unavailable';
}

$instanceId = getEc2Metadata('instance-id');
$privateIp  = getEc2Metadata('local-ipv4');
$az         = getEc2Metadata('placement/availability-zone');
$type       = getEc2Metadata('instance-type');
$hostname   = gethostname() ?: 'Unknown';
$requestId  = bin2hex(random_bytes(6));
$serverTime = date('Y-m-d H:i:s T');

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>AWS EC2 Load Balancer Test</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            color: #e5edf7;
            background: #0b1220;
        }
        header {
            padding: 28px 18px;
            text-align: center;
            background: #111d32;
            border-bottom: 1px solid #263650;
        }
        header h1 { margin: 0 0 8px; font-size: 25px; }
        header p { margin: 0; color: #9fb0c8; }
        main { width: min(850px, calc(100% - 32px)); margin: 38px auto; }
        .card {
            padding: 28px;
            background: #152238;
            border: 1px solid #2a3c58;
            border-radius: 16px;
            box-shadow: 0 18px 45px rgba(0,0,0,.25);
        }
        .status {
            display: inline-block;
            padding: 8px 12px;
            color: #86efac;
            background: #123b2a;
            border: 1px solid #216644;
            border-radius: 999px;
            font-size: 13px;
            font-weight: bold;
        }
        .instance {
            margin: 22px 0;
            padding: 22px;
            background: #0d1728;
            border: 1px solid #29415f;
            border-radius: 12px;
        }
        .instance small { color: #9fb0c8; letter-spacing: .08em; }
        .instance h2 {
            margin: 10px 0;
            color: #4ade80;
            font-size: clamp(19px, 4vw, 28px);
            overflow-wrap: anywhere;
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 13px;
        }
        .item {
            padding: 15px;
            background: #1d2d45;
            border-radius: 10px;
            min-width: 0;
        }
        .item strong { display: block; margin-bottom: 8px; color: #9fb0c8; font-size: 13px; }
        .item span { overflow-wrap: anywhere; }
        .actions { margin-top: 22px; display: flex; gap: 12px; flex-wrap: wrap; }
        button, a.button {
            display: inline-block;
            border: 0;
            border-radius: 9px;
            padding: 12px 17px;
            color: white;
            background: #2563eb;
            text-decoration: none;
            font-size: 14px;
            cursor: pointer;
        }
        button:hover, a.button:hover { background: #1d4ed8; }
        footer { padding: 20px; text-align: center; color: #8192aa; font-size: 12px; }
        @media (max-width: 580px) {
            .grid { grid-template-columns: 1fr; }
            .card { padding: 19px; }
        }
    </style>
</head>
<body>
<header>
    <h1>AWS EC2 Load Balancer Test</h1>
    <p>Monitoring request pada EC2 Private</p>
</header>

<main>
    <section class="card">
        <span class="status">● WEB SERVER ONLINE</span>
        <h3>Request ini dilayani oleh:</h3>

        <div class="instance">
            <small>EC2 INSTANCE ID</small>
            <h2><?= h($instanceId) ?></h2>
            <div>Hostname: <?= h($hostname) ?></div>
        </div>

        <div class="grid">
            <div class="item">
                <strong>Private IP</strong>
                <span><?= h($privateIp) ?></span>
            </div>
            <div class="item">
                <strong>Availability Zone</strong>
                <span><?= h($az) ?></span>
            </div>
            <div class="item">
                <strong>Instance Type</strong>
                <span><?= h($type) ?></span>
            </div>
            <div class="item">
                <strong>PHP / Web Server</strong>
                <span><?= h($_SERVER['SERVER_SOFTWARE'] ?? 'Apache') ?></span>
            </div>
            <div class="item">
                <strong>Request ID</strong>
                <span><?= h($requestId) ?></span>
            </div>
            <div class="item">
                <strong>Waktu server</strong>
                <span><?= h($serverTime) ?></span>
            </div>
        </div>

        <div class="actions">
            <button type="button" onclick="window.location.reload()">Refresh untuk tes</button>
            <a class="button" href="/health.php" target="_blank" rel="noopener">Tes health check</a>
        </div>
    </section>
</main>

<footer>AWS EC2 Private Load Balancing Test</footer>
</body>
</html>
