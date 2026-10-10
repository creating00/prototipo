#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")/.."
project_path="$(pwd -P)"
php_binary="$(php -r 'echo PHP_BINARY;')"
"$php_binary" scripts/queue-cron.php --check

# Cron interprets percent and newline characters before the shell sees the command.
if [[ "$project_path$php_binary" == *%* || "$project_path$php_binary" == *$'\n'* ]]; then
    echo 'La ruta no es compatible con el registro automático de cron.' >&2
    exit 1
fi
printf -v cron_command '%q %q >> %q 2>&1' "$php_binary" "$project_path/scripts/queue-cron.php" "$project_path/storage/logs/queue-cron.log"
printf -v cron_marker '# prototipo-queue:%s' "$project_path"
cron_entry="* * * * * $cron_command $cron_marker"

if ! command -v crontab >/dev/null 2>&1; then
    echo 'ATENCIÓN: este hosting no permite registrar cron por SSH. Configurá en hPanel un cron personalizado cada minuto:'
    printf '%q %q\n' "$php_binary" "$project_path/scripts/queue-cron.php"
    echo 'La cola no queda activa hasta configurar ese cron en hPanel.'
    exit 0
fi

cron_file="$(mktemp)"
cron_error="$(mktemp)"
trap 'rm -f "$cron_file" "$cron_error"' EXIT
if ! LC_ALL=C crontab -l > "$cron_file" 2> "$cron_error"; then
    if ! grep -qi 'no crontab for' "$cron_error"; then
        cat "$cron_error" >&2
        echo 'No se pudo leer cron. Configurá en hPanel este comando cada minuto:' >&2
        printf '%q %q\n' "$php_binary" "$project_path/scripts/queue-cron.php" >&2
        exit 1
    fi
fi
# Replace only this application's entry, preserving all other scheduled tasks.
existing_cron="$(grep -Fv -- "$cron_marker" "$cron_file" || true)"
{ if [[ -n "$existing_cron" ]]; then printf '%s\n' "$existing_cron"; fi; printf '%s\n' "$cron_entry"; } > "$cron_file"
crontab "$cron_file"
echo 'Cron de cola registrado cada minuto; las entradas de otros proyectos se conservaron.'
