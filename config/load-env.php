<?php
declare(strict_types=1);

function load_env_file(string $path): array
{
    if (!is_readable($path)) {
        throw new RuntimeException('Server configuration file is not readable.');
    }

    $values = [];
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0 || strpos($line, '=') === false) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        $value = trim($value, " \t\n\r\0\x0B\"'");
        $values[$key] = $value;
    }

    return $values;
}

function env_value(array $env, string $key, ?string $default = null): ?string
{
    return array_key_exists($key, $env) ? $env[$key] : $default;
}

function app_env(): array
{
    static $env;
    if ($env === null) {
        $env = load_env_file('/home/aaspaced/cred/.env');
    }
    return $env;
}
