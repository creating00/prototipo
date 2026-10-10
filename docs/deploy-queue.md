# Cola de catálogos en Hostinger

Los workflows de `testing` y `master` ejecutan migraciones y actualizan las cachés antes de reiniciar la cola con `php artisan queue:restart` y ejecutar `bash scripts/install-queue-cron.sh`. Testing ya compila y sube Vite; este cambio no modifica el mecanismo de assets de master.

El instalador verifica la conexión `database`, las tablas necesarias y las extensiones del PHP de consola. Usá `QUEUE_CONNECTION=database` y `DB_QUEUE_RETRY_AFTER=90` o mayor en el `.env` de cada ambiente. No sobrescribe ese archivo ni cambia secretos. Si la configuración es incompatible, el deploy falla con el motivo.

Si el hosting permite `crontab` por SSH, registra una entrada cada minuto para el worker. Repetir el deploy reemplaza únicamente la entrada de ese proyecto y conserva las tareas existentes. Testing y master tienen bloqueos y registros independientes por su ruta.

## Si cron se administra desde hPanel

Cuando `crontab` no está disponible, el deploy imprime el comando exacto con las rutas absolutas del PHP y del proyecto. Configurá una sola vez un **cron personalizado cada minuto** en hPanel con ese comando. La cola no queda activa hasta completar este paso. No dupliques la entrada si ya está registrada por SSH.

Podés obtener las rutas desde SSH, dentro del proyecto:

```bash
php -r 'echo PHP_BINARY, " ", realpath("scripts/queue-cron.php"), PHP_EOL;'
```

El comando tendrá la forma:

```text
/ruta/absoluta/php /ruta/absoluta/proyecto/scripts/queue-cron.php
```

Hostinger documenta el registro desde [hPanel → Cron Jobs](https://www.hostinger.com/support/1583465-how-to-set-up-a-cron-job-at-hostinger/). Usá el PHP de consola compatible con las dependencias del proyecto; puede diferir de la versión seleccionada para la web.

## Ejecución y diagnóstico

`scripts/queue-cron.php` solo admite CLI. Arranca `queue:work --stop-when-empty --max-time=55 --sleep=1 --tries=1 --timeout=65`, con la conexión y cola configuradas en Laravel. No consulta proveedores por iniciativa propia: procesa trabajos enviados al pulsar «Consultar ahora». Un bloqueo de archivo evita solapar dos ejecuciones de cron del mismo proyecto y se libera incluso si el proceso termina inesperadamente.

El límite `--max-time` se evalúa entre trabajos: un trabajo iniciado antes del límite puede terminar después. Con cron cada minuto, una consulta puede esperar hasta el próximo tick. Si hay muchas consultas acumuladas, puede superar el límite de espera de dos minutos de la pantalla; para esa carga conviene un worker permanente administrado con Supervisor o systemd. El cron no reemplaza esa administración en un VPS. El timeout PHP de trabajos requiere `pcntl`; la extracción HTTP conserva sus límites propios aunque esa extensión no esté disponible.

Si ya existe un worker permanente en el servidor, usá una sola estrategia y no registres también este cron. Laravel explica el reinicio y la supervisión de workers en su [documentación de colas](https://laravel.com/docs/12.x/queues#queue-workers-and-deployment).

Verificá desde SSH:

```bash
php scripts/queue-cron.php --check
crontab -l
php artisan queue:failed
```

La entrada instalada por SSH escribe en `storage/logs/queue-cron.log`; un cron de hPanel muestra su salida en el panel. Revisá también `storage/logs/laravel.log`. Después de activar el cron, iniciá una nueva consulta: una consulta ya vencida no se recupera automáticamente.

## Verificación y reversión

La comprobación local valida sintaxis PHP y Bash, la configuración real con `--check` sin procesar trabajos y un harness aislado:

```bash
bash tests/Deployment/queue-cron.sh
```

El harness verifica instalación repetida sin duplicados, conservación de otros cron, rutas con espacios, configuración inválida, permisos denegados y un usuario sin cron previo. No accede al hosting ni modifica el cron real.

Para revertir esta integración, retirás las llamadas nuevas de ambos workflows y la entrada identificada con `# prototipo-queue:` de cada ambiente (o el cron de hPanel), junto con los scripts. Las consultas y el historial de la base de datos se conservan. Si reemplazás cron por Supervisor, activá primero el servicio para continuar procesando las consultas.
