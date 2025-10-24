<?php
// Simple API smoke tests for standardized responses
// Usage: php tests/api_smoke.php
// Ensure dev server is running at BASE_URL

const BASE_URL = 'http://localhost:8080';
// Optionally include an auth cookie to test authenticated flows, e.g.:
// const COOKIE_HEADER = 'Cookie: jwt=YOUR_JWT_COOKIE_VALUE';
const COOKIE_HEADER = '';

function request(string $method, string $path, ?array $jsonBody = null): array {
    $headers = [
        'Accept: application/json'
    ];
    $content = null;

    if ($jsonBody !== null) {
        $content = json_encode($jsonBody, JSON_UNESCAPED_UNICODE);
        $headers[] = 'Content-Type: application/json';
    }
    if (COOKIE_HEADER !== '') {
        $headers[] = COOKIE_HEADER;
    }

    $opts = [
        'http' => [
            'method' => $method,
            'header' => implode("\r\n", $headers),
            'content' => $content ?? '',
            'ignore_errors' => true // capture body even on non-200
        ]
    ];

    $ctx = stream_context_create($opts);
    $url = rtrim(BASE_URL, '/') . $path;
    $body = @file_get_contents($url, false, $ctx);
    $status = 0;
    $respHeaders = $http_response_header ?? [];
    foreach ($respHeaders as $h) {
        if (preg_match('/^HTTP\/\d+\.\d+\s+(\d+)/', $h, $m)) {
            $status = (int)$m[1];
            break;
        }
    }
    $json = null;
    if ($body !== false && $body !== null) {
        $json = json_decode($body, true);
    }
    return [
        'status' => $status,
        'body' => $body,
        'json' => $json,
        'headers' => $respHeaders,
        'url' => $url,
    ];
}

function assertStatus(int $got, int $expect, string $label): bool {
    if ($got === $expect) {
        echo "[OK] $label: status $got\n";
        return true;
    }
    echo "[FAIL] $label: expected status $expect, got $got\n";
    return false;
}

function assertJsonHas(array $json, array $path, $label): bool {
    $cur = $json;
    foreach ($path as $key) {
        if (!is_array($cur) || !array_key_exists($key, $cur)) {
            echo "[FAIL] $label: missing key '" . implode('.', $path) . "'\n";
            return false;
        }
        $cur = $cur[$key];
    }
    echo "[OK] $label: has '" . implode('.', $path) . "'\n";
    return true;
}

function runTests(): int {
    $fails = 0;

    // 1) GET /api/homepage-courses (expect 401 without auth)
    $r1 = request('GET', '/api/homepage-courses');
    $fails += assertStatus($r1['status'], 401, 'GET /api/homepage-courses unauth') ? 0 : 1;
    if (is_array($r1['json'])) {
        $fails += assertJsonHas($r1['json'], ['success'], 'GET /api/homepage-courses body') ? 0 : 1;
        $fails += assertJsonHas($r1['json'], ['error','code'], 'GET /api/homepage-courses error.code') ? 0 : 1;
    } else {
        echo "[WARN] GET /api/homepage-courses: response is not JSON\n";
    }

    // 2) POST /api/homepage-courses (unauth)
    $r2 = request('POST', '/api/homepage-courses', ['course_id' => 1]);
    $fails += assertStatus($r2['status'], 401, 'POST /api/homepage-courses unauth') ? 0 : 1;

    // 3) GET /api/user/modal-state (unauth)
    $r3 = request('GET', '/api/user/modal-state');
    $fails += assertStatus($r3['status'], 401, 'GET /api/user/modal-state unauth') ? 0 : 1;

    // 4) POST /api/user/main-modal/close (unauth)
    $r4 = request('POST', '/api/user/main-modal/close', []);
    $fails += assertStatus($r4['status'], 401, 'POST /api/user/main-modal/close unauth') ? 0 : 1;

    // Optional authorized checks if COOKIE_HEADER set
    if (COOKIE_HEADER !== '') {
        echo "\n[INFO] Running authorized checks with provided cookie...\n";
        $r5 = request('GET', '/api/homepage-courses');
        if ($r5['status'] === 200 && is_array($r5['json'])) {
            $fails += assertJsonHas($r5['json'], ['success'], 'GET /api/homepage-courses auth success') ? 0 : 1;
            $fails += assertJsonHas($r5['json'], ['data','course_ids'], 'GET /api/homepage-courses data.course_ids') ? 0 : 1;
        } else {
            echo "[WARN] GET /api/homepage-courses auth: expected 200, got {$r5['status']}\n";
        }
    }

    echo "\nTests completed with $fails failure(s).\n";
    return $fails === 0 ? 0 : 1;
}

exit(runTests());