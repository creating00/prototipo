# Un pedido con destinos por producto

En `/web/orders/create-branch`, una sucursal de origen puede enviar productos a distintas sucursales conservando un único número de pedido. El origen corresponde a la sucursal activa del usuario.

## Uso

1. Seleccionar una sucursal activa autorizada y abrir **Crear Pedido**.
2. Seleccionar la provincia de destino. Se muestran provincias con sucursales vigentes distintas del origen.
3. Buscar un producto del origen y asignar una sucursal de destino en su renglón.
4. Para otro destino del mismo producto, agregarlo nuevamente después de elegir el primero. Para otra provincia, cambiar el filtro antes de agregar el siguiente producto. Los renglones existentes conservan sus destinos.
5. Guardar. Esto registra la solicitud y permite editarla, sin mover inventario.
6. Desde **Pedidos entre Sucursales**, abrir `/web/orders/purchases/{id}/details`. La tabla muestra la sucursal de destino por producto. El origen también encuentra sus pedidos en este listado y guardar un pedido nuevo abre directamente este detalle.
7. Ejecutar **Enviar al stock** desde ese detalle con un usuario autorizado sobre el origen. El sistema descuenta del origen y acredita las cantidades en cada destino. No requiere convertir el pedido en venta.

## Reglas de inventario y acceso

- Las cantidades del mismo producto se suman para comprobar el stock del origen.
- Todos los movimientos se ejecutan en una transacción. Un fallo revierte el pedido completo.
- Un pedido procesado queda bloqueado para edición, cancelación y eliminación; tampoco puede enviarse nuevamente.
- Antes de enviar, el origen autorizado puede usar **Cancelar pedido** en el detalle, indicando un motivo obligatorio. Se registran usuario y fecha, se conservan los renglones y se notifica a los participantes sin mover inventario. Un pedido cancelado del nuevo tipo no puede editarse, enviarse ni eliminarse.
- Estos pedidos no se convierten en una venta con un único cliente. Su acción de inventario es **Enviar al stock**.
- Los destinatarios consultan sus renglones y totales; la transferencia completa se ejecuta desde el origen autorizado.
- Cada sucursal recibe una notificación con sus cantidades después de confirmar la transacción.
- Los pedidos anteriores conservan su circuito de recepción y conversión.

## Verificación y despliegue

La migración es `2026_10_09_000000_add_destinations_to_orders.php`. Su reversión se bloquea si existen pedidos del nuevo tipo para evitar perder sus destinos.

La auditoría de cancelación requiere también `2026_10_09_000001_add_cancellation_audit_to_orders.php`, que agrega motivo, fecha y usuario. El formulario envía un POST con protección CSRF a `/web/orders/{id}/cancel`; la API exige `cancellation_reason` para los pedidos con destinos por producto.

Con una versión de PHP compatible con las dependencias instaladas:

```sh
php artisan migrate --path=database/migrations/2026_10_09_000000_add_destinations_to_orders.php
php artisan test --filter=MultiDestinationOrderTest
npm run build
```

Las regresiones comprueban cantidades acumuladas, reversión completa, repetición del envío, destinos inválidos, permisos, edición y notificaciones por sucursal.
