#!/usr/bin/env php
<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

use App\Models\AuditLog;
use App\Models\Site;
use App\Models\SiteCheck;

if (!function_exists('curl_init')) {
    fwrite(STDERR, "The cURL extension is required to run site checks.\n");
    exit(1);
}

$options = getopt('', ['limit::', 'site-id::', 'timeout::', 'stale-hours::', 'status::', 'include-inactive', 'help']);

if (isset($options['help'])) {
    echo "Usage: php scripts/check_sites.php [--limit=50] [--site-id=123] [--timeout=20] [--stale-hours=168] [--status=flagged] [--include-inactive]\n";
    exit(0);
}

$limit = max(1, (int) ($options['limit'] ?? 50));
$siteId = isset($options['site-id']) ? max(1, (int) $options['site-id']) : null;
$timeout = max(5, (int) ($options['timeout'] ?? 20));
$staleHours = max(1, (int) ($options['stale-hours'] ?? 168));
$includeInactive = array_key_exists('include-inactive', $options);
$statusFilter = isset($options['status']) ? trim((string) $options['status']) : null;

if ($statusFilter === '') {
    $statusFilter = null;
}

$db = db();
$siteModel = new Site($db);
$siteCheckModel = new SiteCheck($db);
$auditLog = new AuditLog($db);

$sites = $siteModel->sitesDueForHttpCheck($limit, $siteId, $includeInactive, $staleHours, $statusFilter);

if (!$sites) {
    echo "No sites due for checking.\n";
    exit(0);
}

echo "Checking " . count($sites) . " site(s)...\n";

$ok = 0;
$warn = 0;
$fail = 0;

foreach ($sites as $site) {
    $result = perform_http_check((string) $site['url'], $timeout);

    $siteCheckModel->recordHttpStatus((int) $site['id'], $result['result_status'], [
        'http_status' => $result['http_status'],
        'final_url' => $result['final_url'],
        'redirect_url' => $result['redirect_url'],
        'response_time_ms' => $result['response_time_ms'],
        'error_message' => $result['error_message'],
        'checked_url' => $site['url'],
    ]);

    $siteModel->syncStatusFromCheck((int) $site['id'], $result['result_status']);
    $auditLog->log(null, 'site', (int) $site['id'], 'http_checked', [
        'result_status' => $result['result_status'],
        'http_status' => $result['http_status'],
        'final_url' => $result['final_url'],
        'redirect_url' => $result['redirect_url'],
        'response_time_ms' => $result['response_time_ms'],
        'error_message' => $result['error_message'],
    ]);

    if ($result['result_status'] === 'ok') {
        $ok++;
    } elseif ($result['result_status'] === 'warn') {
        $warn++;
    } else {
        $fail++;
    }

    echo sprintf(
        "[%s] #%d %s | %s | HTTP %s | %s ms\n",
        strtoupper($result['result_status']),
        (int) $site['id'],
        $site['title'],
        $site['url'],
        $result['http_status'] !== null ? (string) $result['http_status'] : '-',
        (string) $result['response_time_ms']
    );

    if (!empty($result['error_message'])) {
        echo "    Error: " . $result['error_message'] . "\n";
    }
    if (!empty($result['redirect_url'])) {
        echo "    Redirect: " . $result['redirect_url'] . "\n";
    }
}

echo "\nSummary: OK {$ok}, WARN {$warn}, FAIL {$fail}\n";

function perform_http_check(string $url, int $timeout): array
{
    $startedAt = microtime(true);

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_NOBODY => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 5,
        CURLOPT_CONNECTTIMEOUT => $timeout,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_USERAGENT => 'DirectoryBot/0.1 (+local-maintenance-check)',
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_HEADER => false,
    ]);

    curl_exec($ch);

    $error = curl_errno($ch) ? curl_error($ch) : null;
    $httpStatus = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $finalUrl = (string) curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    $responseTimeMs = (int) round((microtime(true) - $startedAt) * 1000);
    curl_close($ch);

    $redirectUrl = null;
    if ($finalUrl !== '' && normalize_url($finalUrl) !== normalize_url($url)) {
        $redirectUrl = $finalUrl;
    }

    $resultStatus = derive_result_status($httpStatus, $error);

    return [
        'result_status' => $resultStatus,
        'http_status' => $httpStatus > 0 ? $httpStatus : null,
        'final_url' => $finalUrl !== '' ? $finalUrl : $url,
        'redirect_url' => $redirectUrl,
        'response_time_ms' => $responseTimeMs,
        'error_message' => $error,
    ];
}

function derive_result_status(int $httpStatus, ?string $error): string
{
    if ($error !== null && $error !== '') {
        return 'fail';
    }

    if ($httpStatus >= 200 && $httpStatus < 300) {
        return 'ok';
    }

    if (($httpStatus >= 300 && $httpStatus < 400) || in_array($httpStatus, [401, 403, 405, 429], true)) {
        return 'warn';
    }

    if ($httpStatus === 0) {
        return 'fail';
    }

    if ($httpStatus >= 400) {
        return 'fail';
    }

    return 'warn';
}
