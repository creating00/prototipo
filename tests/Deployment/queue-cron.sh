#!/usr/bin/env bash
set -euo pipefail

repository="$(cd "$(dirname "$0")/../.." && pwd -P)"
fixture="$(mktemp -d)"
trap 'rm -rf "$fixture"' EXIT
mkdir -p "$fixture/app with spaces/scripts" "$fixture/bin"
cp "$repository/scripts/install-queue-cron.sh" "$fixture/app with spaces/scripts/"
export MOCK_CRON_FILE="$fixture/crontab"
export MOCK_PHP_BINARY="$fixture/bin/php"
printf '0 3 * * * echo existing-task\n' > "$MOCK_CRON_FILE"
cat > "$fixture/bin/php" <<'PHP'
#!/usr/bin/env bash
if [[ "${1:-}" == '-r' ]]; then
    printf '%s' "$MOCK_PHP_BINARY"
else
    exit "${MOCK_PHP_STATUS:-0}"
fi
PHP
cat > "$fixture/bin/crontab" <<'CRON'
#!/usr/bin/env bash
if [[ "${MOCK_CRON_DENIED:-0}" == '1' ]]; then
    echo 'permission denied' >&2
    exit 1
fi
if [[ "$1" == '-l' ]]; then
    if [[ ! -f "$MOCK_CRON_FILE" ]]; then
        echo 'no crontab for testing' >&2
        exit 1
    fi
    cat "$MOCK_CRON_FILE"
else
    cp "$1" "$MOCK_CRON_FILE"
fi
CRON
chmod +x "$fixture/bin/php" "$fixture/bin/crontab"
export PATH="$fixture/bin:$PATH"
installer="$fixture/app with spaces/scripts/install-queue-cron.sh"
bash "$installer"
bash "$installer"
[[ "$(grep -c '# prototipo-queue:' "$MOCK_CRON_FILE")" == '1' ]]
grep -q 'existing-task' "$MOCK_CRON_FILE"
grep -q '^\* \* \* \* \* ' "$MOCK_CRON_FILE"
cp "$MOCK_CRON_FILE" "$fixture/before"
if MOCK_PHP_STATUS=1 bash "$installer"; then
    echo 'La configuración inválida no debe instalar cron.' >&2
    exit 1
fi
cmp "$MOCK_CRON_FILE" "$fixture/before"
if MOCK_CRON_DENIED=1 bash "$installer"; then
    echo 'Un cron inaccesible no debe figurar como instalado.' >&2
    exit 1
fi
cmp "$MOCK_CRON_FILE" "$fixture/before"
rm "$MOCK_CRON_FILE"
bash "$installer"
[[ "$(grep -c '# prototipo-queue:' "$MOCK_CRON_FILE")" == '1' ]]
echo 'PASS: cron idempotente, preservación de tareas, rutas con espacios, configuración inválida, acceso denegado y cron vacío.'
