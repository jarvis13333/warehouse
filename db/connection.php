<?php
// PDO connection singleton

require_once __DIR__ . '/../lib/utils.php';

function get_db(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    $config = require dirname(__DIR__) . '/config.php';
    $db = $config['db'];
    $dsn = "mysql:host={$db['host']};dbname={$db['name']};charset={$db['charset']}";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    try {
        $pdo = new PDO($dsn, $db['user'], $db['pass'], $options);
    } catch (PDOException $e) {
        if ($e->getCode() == 1049) {
            // Database doesn't exist
            if (php_sapi_name() !== 'cli') {
                http_response_code(500);
                header('Content-Type: text/html; charset=utf-8');
                die("
                <!DOCTYPE html>
                <html lang='en'>
                <head>
                    <meta charset='UTF-8'>
                    <title>Database not initialized</title>
                    <style>
                        body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; }
                        .error { background: #fee; border: 2px solid #f00; padding: 20px; border-radius: 5px; }
                        .site-footer { margin-top: 24px; text-align: center; color: #6b7280; font-size: 13px; }
                    </style>
                </head>
                <body>
                    <div class='error'>
                        <h2>Database not initialized</h2>
                        <p>Database <strong>{$db['name']}</strong> does not exist.</p>
                        <p>Create the database in phpMyAdmin, then import <strong>db/rebuild_full_en.sql</strong> to set up tables and demo data.</p>
                    </div>
                    " . copyright_footer_html() . "
                </body>
                </html>
                ");
            } else {
                die("Error: database {$db['name']} does not exist. Import db/rebuild_full_en.sql via phpMyAdmin.\n");
            }
        }
        throw $e;
    }
    return $pdo;
}


