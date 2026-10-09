<?php

// Read the same resolved configuration as Laravel, without exposing credentials.
require dirname(__DIR__).'/vendor/autoload.php';
$app = require dirname(__DIR__).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    $connection = $app['db']->connection();
    if (($argv[1] ?? '') === 'check') {
        $connection->select('SELECT 1');
        exit(0);
    }

    echo json_encode([
        'driver' => $connection->getConfig('driver'),
        'host' => $connection->getConfig('host'),
        'port' => $connection->getConfig('port'),
        'database' => $connection->getConfig('database'),
    ], JSON_THROW_ON_ERROR);
} catch (Throwable $exception) {
    // Connection errors can contain credentials; keep them out of terminal/logs.
    fwrite(STDERR, "No se pudo conectar con la base configurada en Laravel. Revise .env y storage/logs/laravel.log.\n");
    exit(1);
}
