<?php
// Basic configuration for database and app

return [
    'db' => [
        'host' => 'localhost',
        'name' => 'warehouse_db',
        'user' => 'root',
        'pass' => '',
        'charset' => 'utf8mb4',
    ],
    // 本地示例: '/warehouse/public' | 若部署在别的子目录，请改成对应路径（须以 /public 结尾）
    'base_url' => '/warehouse/public',
];


