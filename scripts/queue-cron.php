<?php

// CLI entry point for the hosting cron; never expose this through a web route.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

require dirname(__DIR__).'/vendor/autoload.php';
$app = require dirname(__DIR__).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

try {
    $connection = config('queue.default');
    if (config("queue.connections.$connection.driver") !== 'database') {
        throw new RuntimeException('El worker cron requiere QUEUE_CONNECTION=database.');
    }
    if ((int) config("queue.connections.$connection.retry_after") <= 65) {
        throw new RuntimeException('DB_QUEUE_RETRY_AFTER debe ser mayor que 65; usá 90 o más.');
    }
    if (! extension_loaded('curl') || ! extension_loaded('dom')) {
        throw new RuntimeException('El PHP de cron requiere las extensiones curl y dom.');
    }
    $schema = Schema::connection(config("queue.connections.$connection.connection"));
    foreach ([config("queue.connections.$connection.table"), 'provider_catalogs', 'provider_catalog_snapshots', 'provider_catalog_items'] as $table) {
        if (! $schema->hasTable($table)) {
            throw new RuntimeException("Falta la tabla $table; ejecutá las migraciones antes de activar la cola.");
        }
    }
    if (in_array('--check', $argv, true)) {
        echo "Configuración de cola, extensiones y tablas verificadas.\n";
        exit(0);
    }
    // A file lock is released by the OS even when a worker crashes or is killed.
    $lock = fopen(storage_path('framework/queue-cron.lock'), 'c');
    if ($lock === false) {
        throw new RuntimeException('No se pudo abrir el bloqueo de cola; revisá los permisos de storage/framework.');
    }
    if (! flock($lock, LOCK_EX | LOCK_NB)) {
        fclose($lock);
        exit(0);
    }
    try {
        $status = Artisan::call('queue:work', [
            '--stop-when-empty' => true, '--max-time' => 55,
            '--sleep' => 1, '--tries' => 1, '--timeout' => 65,
        ]);
        echo Artisan::output();
    } finally {
        flock($lock, LOCK_UN);
        fclose($lock);
    }
    exit($status);
} catch (Throwable $exception) {
    fwrite(STDERR, 'Worker cron: '.$exception->getMessage().PHP_EOL);
    exit(1);
}
