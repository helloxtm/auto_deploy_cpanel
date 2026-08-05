<?php
declare(strict_types=1);

/**
 * GitHub webhook auto-deploy for Home JOB (plain PHP, no Laravel).
 * Configure webhook URL to this file and set the same secret in GitHub.
 */
$secret = 'add your key here';

$signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? ($_SERVER['HTTP_X_HUB_SIGNATURE'] ?? '');
$payload = file_get_contents('php://input');

if ($signature === '') {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    die('No signature found.');
}

[$algo, $hash] = explode('=', $signature, 2) + ['', ''];
$algo = strtolower($algo);
if (!in_array($algo, ['sha1', 'sha256'], true)) {
    http_response_code(403);
    die('Unsupported signature algorithm.');
}

$expected = hash_hmac($algo, (string)$payload, $secret);
if (!hash_equals($hash, $expected)) {
    http_response_code(403);
    die('Invalid signature.');
}

$repoDir = dirname(__DIR__); // project root (parent of this file's folder if placed at root)
// Prefer current directory when auto_deploy.php lives in public_html root:
if (is_file(__DIR__ . '/.git/config') || is_dir(__DIR__ . '/.git')) {
    $repoDir = __DIR__;
} elseif (is_file(dirname(__DIR__) . '/.git/config') || is_dir(dirname(__DIR__) . '/.git')) {
    $repoDir = dirname(__DIR__);
}

$commands = [
    'cd ' . escapeshellarg($repoDir),
    'git fetch origin main',
    'git reset --hard origin/main',
    'git clean -fd -e api/data -e api/logs',
];

$output = [];
foreach ($commands as $command) {
    $result = shell_exec($command . ' 2>&1');
    $output[] = '$ ' . $command;
    $output[] = (string)$result;
}

header('Content-Type: text/plain; charset=utf-8');
echo implode("\n", $output);
