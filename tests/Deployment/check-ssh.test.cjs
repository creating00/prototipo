const { test } = require('node:test');
const assert = require('node:assert/strict');
const net = require('node:net');
const { checkSsh } = require('../../scripts/check-ssh.cjs');

test('detecta un puerto accesible sin imprimir el host ni el puerto', async () => {
    const server = net.createServer((socket) => socket.end());
    await new Promise((resolve) => server.listen(0, '127.0.0.1', resolve));
    const port = server.address().port;
    const messages = [];
    try {
        assert.equal(await checkSsh({ host: '127.0.0.1', port, log: (message) => messages.push(message) }), true);
        assert.equal(messages.some((message) => message.includes('127.0.0.1') || message.includes(String(port))), false);
        assert.match(messages.join('\n'), /autenticación/);
    } finally {
        await new Promise((resolve) => server.close(resolve));
    }
});

test('reintenta una conexión rechazada y devuelve un error claro', async () => {
    const server = net.createServer();
    await new Promise((resolve) => server.listen(0, '127.0.0.1', resolve));
    const port = server.address().port;
    await new Promise((resolve) => server.close(resolve));
    const messages = [];
    assert.equal(await checkSsh({ host: '127.0.0.1', port, attempts: 2, delayMs: 0, timeoutMs: 1000, log: (message) => messages.push(message) }), false);
    assert.equal(messages.filter((message) => message.includes('intento')).length, 2);
    assert.match(messages.join('\n'), /Conexión rechazada/);
    assert.match(messages.join('\n'), /Deploy detenido/);
});

test('rechaza configuración incompleta o URLs antes de conectarse', async () => {
    for (const [host, port] of [[undefined, '22'], ['https://example.com', '22'], ['example.com', ''], ['example.com', '65536'], ['example.com', '22x']]) {
        assert.equal(await checkSsh({ host, port, log: () => {} }), false);
    }
});
