# Pedido manual con destinos por producto

En `/web/orders/create-client`, la sucursal activa es el contexto de búsqueda, precios y registro del pedido. El cliente se conserva. La sucursal a la que se pide se registra como referencia (`target_branch_id`), sin consultar ni descontar su inventario.

1. Elegir una provincia con sucursales vigentes y una sucursal de referencia.
2. Buscar el producto con la información de la sucursal activa.
3. Asignar el destino por renglón. Puede incluir la sucursal activa si pertenece a la provincia seleccionada.
   Al seleccionar o cambiar el destino, **Stock destino** consulta su inventario actual. Sin destino muestra un guion; un fallo de consulta no se presenta como stock cero. La consulta también se realiza al recuperar renglones guardados o editar. Es informativa: precios y búsqueda siguen usando la sucursal activa y el stock no limita la cantidad pedida.
4. Para dividir un producto entre destinos, agregar otro renglón después de seleccionar el primero. Ejemplo: un módulo para Alta Córdoba y otro para General Paz, con Salta como referencia.
5. Guardar el pedido; no mueve stock y conserva un único número.
6. Desde `/web/orders/purchases/{id}/details`, ejecutar **Enviar al stock** con un usuario autorizado sobre la sucursal que registró el pedido.

El envío manual solo incrementa stock y actualiza el costo en cada destino. No requiere stock disponible ni descuenta el origen o la referencia. Todo el pedido se procesa en una transacción y un segundo envío se rechaza. Los destinatarios consultan sus productos y totales. El origen puede cancelar antes del envío, registrando motivo, usuario y fecha sin mover inventario.

La creación nueva usa `is_multi_destination` con `source=Manual`. Los traslados existentes con `source=Backoffice` mantienen su descuento del origen. Los pedidos manuales antiguos conservan su circuito anterior.

Aplicar `2026_10_09_000002_add_reference_branch_to_orders.php` junto con las migraciones anteriores de destinos y cancelación. El campo de referencia reemplaza el almacenamiento informal en notas para los nuevos pedidos manuales.
