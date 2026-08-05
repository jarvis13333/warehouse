<?php
// Utility helpers

require_once __DIR__ . '/icons.php';

function app_config(): array {
    static $config = null;
    if ($config === null) {
        $config = require dirname(__DIR__) . '/config.php';
    }
    return $config;
}

function base_url(string $path = ''): string {
    $cfg = app_config();
    $base = rtrim($cfg['base_url'] ?? '', '/');
    $path = '/' . ltrim($path, '/');
    return $base . $path;
}

function redirect(string $path): void {
    $url = $path;
    if (strpos($path, 'http') !== 0 && strpos($path, '/') === 0) {
        $url = base_url($path);
    }
    header('Location: ' . $url);
    exit;
}

function e(?string $str): string {
    return htmlspecialchars((string)$str, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function get_param(string $key, $default = null) {
    return $_GET[$key] ?? $_POST[$key] ?? $default;
}

function int_param(string $key, int $default = 0): int {
    return isset($_GET[$key]) ? (int)$_GET[$key] : (isset($_POST[$key]) ? (int)$_POST[$key] : $default);
}

function copyright_notice(): string {
    return 'Copyright © 2026 Jarvis Ng. All Rights Reserved.';
}

function copyright_footer_html(string $class = 'site-footer'): string {
    return '<footer class="' . e($class) . '"><p>' . e(copyright_notice()) . '</p></footer>';
}

/** @return array{page:int,per_page:int,total:int,total_pages:int,offset:int,from:int,to:int} */
function list_pagination_meta(int $total, int $page, int $perPage = 25): array {
    $perPage = max(1, $perPage);
    $totalPages = max(1, (int)ceil($total / $perPage));
    $page = min(max(1, $page), $totalPages);
    $offset = ($page - 1) * $perPage;
    return [
        'page' => $page,
        'per_page' => $perPage,
        'total' => $total,
        'total_pages' => $totalPages,
        'offset' => $offset,
        'from' => $total === 0 ? 0 : $offset + 1,
        'to' => min($offset + $perPage, $total),
    ];
}

function build_page_url(array $params): string {
    $filtered = [];
    foreach ($params as $key => $value) {
        if ($value === '' || $value === null) {
            continue;
        }
        $filtered[$key] = $value;
    }
    return base_url('/index.php?' . http_build_query($filtered));
}

function empty_state_html(string $title, string $message = '', string $iconName = 'package'): string {
    $msg = $message !== ''
        ? '<p class="empty-state-desc">' . e($message) . '</p>'
        : '';
    return '<div class="empty-state">'
        . '<div class="empty-state-icon">' . icon($iconName) . '</div>'
        . '<p class="empty-state-title">' . e($title) . '</p>'
        . $msg
        . '</div>';
}

/** @param array<string,mixed> $queryParams */
function list_footer_html(array $meta, array $queryParams, string $noun = 'items'): string {
    if ($meta['total'] === 0) {
        return '';
    }
    $info = sprintf(
        'Showing %d–%d of %d %s',
        $meta['from'],
        $meta['to'],
        $meta['total'],
        $noun
    );
    $nav = '';
    if ($meta['total_pages'] > 1) {
        $links = [];
        $prev = $meta['page'] - 1;
        $next = $meta['page'] + 1;
        if ($meta['page'] > 1) {
            $queryParams['p'] = $prev;
            $links[] = '<a class="pagination-link" href="' . e(build_page_url($queryParams)) . '">Previous</a>';
        } else {
            $links[] = '<span class="pagination-link disabled">Previous</span>';
        }
        $links[] = '<span class="pagination-current">Page ' . $meta['page'] . ' of ' . $meta['total_pages'] . '</span>';
        if ($meta['page'] < $meta['total_pages']) {
            $queryParams['p'] = $next;
            $links[] = '<a class="pagination-link" href="' . e(build_page_url($queryParams)) . '">Next</a>';
        } else {
            $links[] = '<span class="pagination-link disabled">Next</span>';
        }
        $nav = '<nav class="pagination" aria-label="Pagination">' . implode('', $links) . '</nav>';
    }
    return '<div class="list-footer"><span class="pagination-info">' . e($info) . '</span>' . $nav . '</div>';
}

