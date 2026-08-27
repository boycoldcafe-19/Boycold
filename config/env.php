<?php

function loadProjectEnv(): array
{
    static $loaded = false;
    static $env = [];

    if ($loaded) {
        return $env;
    }

    $envFile = dirname(__DIR__) . '/.env';
    if (!is_readable($envFile)) {
        $loaded = true;
        return $env;
    }

    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') {
            continue;
        }

        $separator = strpos($line, '=');
        if ($separator === false) {
            continue;
        }

        $key = trim(substr($line, 0, $separator));
        $value = trim(substr($line, $separator + 1));
        $value = trim($value, " \t\"'");

        if ($key === '') {
            continue;
        }

        $env[$key] = $value;
        if (getenv($key) === false) {
            putenv($key . '=' . $value);
        }
    }

    $loaded = true;
    return $env;
}

function env(string $name, string $default = ''): string
{
    $fileEnv = loadProjectEnv();
    $value = getenv($name);

    if ($value !== false && $value !== '') {
        return trim((string) $value);
    }

    return trim((string) ($fileEnv[$name] ?? $default));
}