<?php

/**
 * Launch Step 13 CI audit wrapper.
 * Fails on any advisory not listed in the waived set (see docs/launch/COMPOSER_ADVISORIES.md).
 */

$waivedIds = [
    'PKSA-m5cs-t1y6-qpcs',
    'PKSA-3r5d-mb8f-1qw9',
    'PKSA-mdq4-51ck-6kdq',
];

$waivedTitles = [
    'Temporary Signed URL Path Confusion',
    'CRLF injection in default email rule',
    'Laravel CRLF injection in default email rule',
];

$waivedCves = [
    'CVE-2026-48019',
];

$root = dirname(__DIR__);
$jsonPath = $root.DIRECTORY_SEPARATOR.'storage'.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'ci-composer-audit.json';
@mkdir(dirname($jsonPath), 0777, true);

$cmd = 'composer audit --abandoned=ignore --format=json';
if (PHP_OS_FAMILY === 'Windows') {
    $cmd .= ' > '.escapeshellarg($jsonPath).' 2> '.escapeshellarg($jsonPath.'.err');
} else {
    $cmd .= ' > '.escapeshellarg($jsonPath).' 2> '.escapeshellarg($jsonPath.'.err');
}

$exitCode = 0;
passthru($cmd, $exitCode);

$raw = is_file($jsonPath) ? (string) file_get_contents($jsonPath) : '';
$err = is_file($jsonPath.'.err') ? (string) file_get_contents($jsonPath.'.err') : '';
@unlink($jsonPath);
@unlink($jsonPath.'.err');

$raw = trim($raw);
if ($raw === '') {
    // Network blips should not false-green; CI runners have Packagist access.
    fwrite(STDERR, "composer audit produced no JSON output (exit {$exitCode}).\n{$err}\n");
    exit(1);
}

$data = json_decode($raw, true);
if (! is_array($data)) {
    fwrite(STDERR, "Failed to decode composer audit JSON.\n");
    exit(1);
}

$blocking = [];
foreach ($data['advisories'] ?? [] as $package => $items) {
    foreach ($items as $item) {
        $id = (string) ($item['advisoryId'] ?? '');
        $title = (string) ($item['title'] ?? '');
        $cve = (string) ($item['cve'] ?? '');

        $isWaived = in_array($id, $waivedIds, true)
            || in_array($title, $waivedTitles, true)
            || in_array($cve, $waivedCves, true);

        if ($isWaived) {
            fwrite(STDOUT, "WAIVED {$package}: {$title} ({$id})\n");
            continue;
        }

        $blocking[] = sprintf('%s: %s (%s)', $package, $title, $id !== '' ? $id : $cve);
    }
}

if ($blocking === []) {
    fwrite(STDOUT, "Composer audit OK (only documented waivers remain).\n");
    exit(0);
}

fwrite(STDERR, "Blocking advisories:\n- ".implode("\n- ", $blocking)."\n");
exit(1);
