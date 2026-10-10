<?php

require dirname(__DIR__, 2).'/vendor/autoload.php';

use Symfony\Component\Process\Process;

$fixture = sys_get_temp_dir().'/prototipo-queue-test-'.bin2hex(random_bytes(8));
mkdir($fixture.'/framework', 0777, true);
mkdir($fixture.'/framework/views');
mkdir($fixture.'/logs');
$database = $fixture.'/queue.sqlite';
$pdo = new PDO('sqlite:'.$database);
$pdo->exec('CREATE TABLE jobs (id INTEGER PRIMARY KEY, queue TEXT, payload TEXT, attempts INTEGER, reserved_at INTEGER, available_at INTEGER, created_at INTEGER)');
foreach (['provider_catalogs', 'provider_catalog_snapshots', 'provider_catalog_items'] as $table) {
    $pdo->exec("CREATE TABLE $table (id INTEGER PRIMARY KEY)");
}
$environment = [
    'APP_ENV' => 'testing', 'APP_CONFIG_CACHE' => $fixture.'/config.php',
    'LARAVEL_STORAGE_PATH' => $fixture, 'DB_CONNECTION' => 'sqlite', 'DB_DATABASE' => $database,
    'DB_QUEUE_CONNECTION' => 'sqlite', 'DB_QUEUE_TABLE' => 'jobs', 'DB_QUEUE' => 'default',
    'QUEUE_CONNECTION' => 'database', 'DB_QUEUE_RETRY_AFTER' => '90', 'CACHE_STORE' => 'array',
    'LOG_CHANNEL' => 'stderr',
];
$script = dirname(__DIR__, 2).'/scripts/queue-cron.php';
$run = function (array $arguments) use ($script, $environment): string {
    $process = new Process([PHP_BINARY, $script, ...$arguments], dirname(__DIR__, 2), $environment);
    $process->setTimeout(20);
    $process->mustRun();

    return $process->getOutput();
};
$expect = function (bool $condition, string $message): void {
    if (! $condition) {
        throw new RuntimeException($message);
    }
};
try {
    $before = json_decode($run(['--status']), true, 512, JSON_THROW_ON_ERROR);
    $expect($before['worker'] === null, 'El diagnóstico debe identificar que el worker nunca se ejecutó.');
    $expect(! file_exists($fixture.'/framework/queue-cron-state.json'), 'El diagnóstico no debe simular una ejecución.');
    $pdo->exec("INSERT INTO jobs VALUES (1, 'default', '{}', 0, NULL, 0, 0)");
    $pending = json_decode($run(['--status']), true, 512, JSON_THROW_ON_ERROR);
    $expect($pending['pending_or_reserved_jobs'] === 1, 'El diagnóstico debe contar trabajos pendientes.');
    $pdo->exec('DELETE FROM jobs');
    $output = $run([]);
    $expect(str_contains($output, 'iniciado') && str_contains($output, 'finalizado'), 'La ejecución debe registrar inicio y finalización.');
    $after = json_decode($run(['--status']), true, 512, JSON_THROW_ON_ERROR);
    $expect($after['worker']['finished_at'] !== null && $after['worker']['exit_code'] === 0, 'La ejecución real debe guardar su resultado.');
    $expect($after['pending_or_reserved_jobs'] === 0, 'La cola aislada debe estar vacía.');
    echo "PASS: diagnóstico sin actividad, conteo de pendientes y ejecución real del worker en SQLite aislado.\n";
} finally {
    unset($pdo);
    // Only remove the known files created by this fixture; no recursive deletion.
    foreach (glob($fixture.'/framework/*') as $file) {
        if (is_file($file)) {
            unlink($file);
        }
    }
    unlink($database);
    rmdir($fixture.'/framework/views');
    rmdir($fixture.'/framework');
    rmdir($fixture.'/logs');
    rmdir($fixture);
}
