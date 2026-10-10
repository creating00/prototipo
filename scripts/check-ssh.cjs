const net = require('node:net');

function connectOnce(host, port, timeoutMs) {
    return new Promise((resolve) => {
        const socket = net.createConnection({ host, port });
        const finish = (result) => {
            socket.destroy();
            resolve(result);
        };
        socket.setTimeout(timeoutMs, () => finish('ETIMEDOUT'));
        socket.once('connect', () => finish(null));
        socket.once('error', (error) => finish(error.code || 'UNKNOWN'));
    });
}

async function checkSsh({ host, port, attempts = 3, timeoutMs = 15000, delayMs = 2000, log = console.log }) {
    if (typeof host !== 'string' || !host || /[\s/:]/.test(host) && net.isIP(host) !== 6) {
        log('SSH_HOST falta o tiene un formato inválido. Usá el host/IP de SSH de hPanel, sin protocolo ni puerto.');
        return false;
    }
    if (!/^[0-9]+$/.test(String(port)) || Number(port) < 1 || Number(port) > 65535) {
        log('SSH_PORT falta o es inválido. Copiá el puerto SSH de hPanel en el secreto de GitHub.');
        return false;
    }
    for (let attempt = 1; attempt <= attempts; attempt++) {
        log(`Comprobando conectividad TCP de SSH: intento ${attempt}/${attempts}…`);
        const failure = await connectOnce(host, Number(port), timeoutMs);
        if (!failure) {
            log('Puerto SSH accesible desde el runner. La autenticación se verificará en el paso de deploy.');
            return true;
        }
        const reasons = {
            ETIMEDOUT: 'El servidor no respondió: revisá host, puerto, acceso SSH habilitado y reglas de red.',
            ECONNREFUSED: 'Conexión rechazada: revisá el puerto y si el servicio SSH está activo.',
            ENOTFOUND: 'No se pudo resolver SSH_HOST. Revisá el host o IP configurado.',
            EAI_AGAIN: 'Falló temporalmente la resolución DNS del host SSH.',
            ENETUNREACH: 'La red del servidor no es accesible desde el runner.',
            EHOSTUNREACH: 'El servidor SSH no es accesible desde el runner.',
        };
        log(reasons[failure] || 'No se pudo conectar al puerto SSH. Revisá el acceso del servidor.');
        if (attempt < attempts) await new Promise((resolve) => setTimeout(resolve, delayMs));
    }
    log('Deploy detenido antes de ejecutar comandos remotos. No se muestran los valores de los secretos.');
    return false;
}

module.exports = { checkSsh };
if (require.main === module) {
    checkSsh({ host: process.env.SSH_HOST, port: process.env.SSH_PORT })
        .then((ok) => { process.exitCode = ok ? 0 : 1; })
        .catch(() => { console.error('Falló la comprobación SSH.'); process.exitCode = 1; });
}
