<?php
declare(strict_types=1);

require_once __DIR__ . '/load-env.php';

function database(): PDO
{
    static $pdo;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $env = app_env();
    $host = env_value($env, 'DB_HOST', 'localhost');
    $name = env_value($env, 'DB_NAME', 'aaspaced_adm');
    $user = env_value($env, 'DB_USER', 'aaspaced_ng');
    $pass = env_value($env, 'DB_PASS', env_value($env, 'DB_PASSWORD', env_value($env, 'MYSQL_PASSWORD', '')));

    $pdo = new PDO(
        "mysql:host={$host};dbname={$name};charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );

    return $pdo;
}
