# Consultar y comparar catálogos web

Desde **Proveedores → Catálogos web y comparación de precios**, consultá la página configurada de un proveedor, revisá el resultado y presioná **Procesar** para conservarlo. Los productos procesados quedan disponibles en los combos de comparación de todos los proveedores, con imagen y fecha cuando existen. Cada nueva consulta reemplaza la vista previa; el historial procesado se conserva. No hay sincronización periódica.

## Puesta en marcha

1. Usá PHP compatible con Laravel 12, con cURL, DOM y las extensiones habituales del proyecto.
2. Ejecutá `php artisan migrate` para agregar `providers.catalog_url`, `provider_catalogs`, `provider_catalog_snapshots` y `provider_catalog_items`.
3. Configurá una cola asíncrona, por ejemplo `QUEUE_CONNECTION=database`. Ejecutá `php artisan queue:work --tries=1 --timeout=65`; el `retry_after` debe superar 65 segundos (el valor existente es 90). `composer run dev` ya inicia un listener.
4. Compilá con `npm run build`.
5. Editá el proveedor y guardá su URL. El acceso requiere `providers.view` y `product_provider_prices.view`. Consultar exige además la autorización `update` del proveedor, con las restricciones existentes de sucursal y modo consolidado. Administradores conservan las reglas globales del proyecto.

Una cola `sync` o `null` no permite iniciar consultas. Si un worker no finaliza en dos minutos, la pantalla informa el problema y permite reintentar. Las respuestas de trabajos vencidos o reemplazados no sobrescriben la consulta vigente. Cambiar la URL invalida la vista previa; conserva el historial procesado.

Los deploys de testing y master integran el reinicio de workers y el registro de cron para hosting compartido. Consultá [Cola de catálogos en Hostinger](deploy-queue.md) para los requisitos y el paso de hPanel cuando el hosting no permite registrar cron por SSH.

## Consultar y procesar

1. Seleccioná el proveedor: la selección actualiza la pantalla, sin botón «Ver».
2. Presioná **Consultar ahora**. Al terminar, revisá los productos publicados y el mensaje de alcance o error. Esto todavía no incorpora productos al historial de comparación.
3. Presioná **Procesar**. Se guarda una copia completa del resultado obtenido, su URL, proveedor, fecha de consulta, fecha de procesamiento y usuario. Si el resultado es parcial, el historial conserva esa advertencia. Un error, una consulta pendiente o una respuesta sin productos no se puede procesar.
4. Abrí cualquier registro de **Historial procesado** para revisar sus productos y datos de origen. Procesar dos veces la misma consulta no duplica el registro; consultar nuevamente y procesar crea otro registro, aunque los precios no hayan cambiado.

`provider_catalogs` contiene solamente la última vista previa por proveedor. `provider_catalog_snapshots` conserva cada consulta procesada y `provider_catalog_items` conserva sus productos y datos publicados en JSON. El guardado es transaccional: si un producto no se puede guardar, no queda un historial incompleto. No se importan productos al inventario interno ni se procesan automáticamente las consultas existentes.

## Fuentes verificadas el 9 de octubre de 2026

Se consultaron las URLs públicas sin credenciales y luego se ejecutaron los adaptadores mediante el cliente HTTP seguro de Laravel. Los conteos son evidencia de esa evaluación; pueden cambiar en origen.

| Fuente | Extracción verificada | Alcance y límites |
| --- | --- | --- |
| [i2C](https://www.i2cmayorista.com/) | 20 productos en JSON-LD, con precio y moneda | Solo la página solicitada, hasta 500 productos; sin categorías ni paginación. La disponibilidad del JSON-LD puede diferir de la tarjeta visual. |
| [UNV](https://unvarg.com/search/?q=lenovo) | 2 productos en JSON-LD | Mantiene la búsqueda de la URL; no representa todo el catálogo. |
| [Fast Cheap](https://www.fast-cheap.com.ar/) | 59 tarjetas públicas con nombre, código, precio e imagen cuando existen | Adaptador específico del tema Kapee/WooCommerce. La Store API devolvió 403 y no se utiliza. Solo tarjetas de la página, sin fichas individuales. El símbolo `$` queda sin moneda. Rangos de precio quedan sin importe único. |
| [S&R](https://syrcelulares.com/) | 400 productos de la Store API pública | Solo URL principal; hasta cuatro páginas de 100 productos. La API declaró 611 productos durante la evaluación: el resultado es parcial. Los atributos y variantes se conservan en los datos de origen; no se inventa precio de cada variante. |
| [Google Sheets](https://docs.google.com/spreadsheets/d/1c39kYssBH8TWE4JPZ8c4xy9OJ4RnsCme/edit?gid=1972998278) | 565 pares de nombre y precio en CSV | Solo este documento y pestaña. Formato específico de bloques con celdas adyacentes producto/precio; hasta 1000 productos. No se interpreta `$` como ARS o USD. Otros bloques o pestañas requieren otro adaptador. |
| [Dux](https://catalogo.duxsoftware.com.ar/byteriojauno?productSearch=&page=1) | 96 productos en cuatro páginas, con nombre, SKU, precio, moneda `PESOS`, stock e imágenes cuando existen | Adaptador específico de los registros JSON de Next.js Flight embebidos en el HTML. No ejecuta JavaScript ni consulta APIs privadas. Sigue solo enlaces publicados de la misma búsqueda y catálogo, hasta cuatro páginas. Resultado parcial, sin detalles de variantes. El campo `url` de Dux es una imagen: el enlace de origen lleva a la página del catálogo, sin inventar un permalink de producto. |

No se garantiza cobertura completa de una tienda a partir de su portada o de una página de búsqueda. La pantalla distingue consulta en cola, listado obtenido, listado parcial, error e integración pendiente; muestra fecha, alcance y motivo. Una extracción parcial de S&R conserva las páginas obtenidas si falla una posterior.

## Comparación

Seleccioná entre dos y ocho productos de al menos dos proveedores. Los combos agrupan todos los productos procesados por proveedor, muestran miniatura, código, precio, moneda y fecha de consulta, y permiten buscar por nombre, código o proveedor. La lista carga de a 50 productos al desplazarte: no se limita a la primera página. Las consultas anteriores también aparecen, identificadas por fecha. Revisá los enlaces de origen, modelo, variante, presentación y condiciones; confirmá la equivalencia explícitamente. La confirmación aplica a esa comparación y no crea asociaciones internas permanentes.

No se asigna equivalencia por nombre, SKU interno o similitud. Toda extracción indica **requiere revisión**. La comparación agrupa por moneda publicada; no convierte monedas ni normaliza impuestos, descuentos o cantidades. Sin moneda, solo se muestra el importe publicado con la advertencia correspondiente. Las selecciones apuntan a productos del historial: una nueva consulta no las invalida. La comparación muestra la fecha y el registro de origen; los precios guardados pueden haber cambiado en la web del proveedor.

Los campos ausentes quedan nulos y se muestran como **No informado**. Se conserva el objeto estructurado original o los datos de la tarjeta/celda en **Todos los datos publicados por la fuente**. Las imágenes se muestran en la vista previa, el historial, los combos y la comparación. El navegador carga la URL publicada cuando corresponde; el servidor no descarga ni almacena archivos de imagen. Si no hay imagen o falla su carga en el combo, se muestra «Sin imagen».

## Extender y verificar

Los adaptadores implementan `App\Services\Catalogs\CatalogAdapter` y devuelven `CatalogResult`. Registrá los hosts explícitos en `CatalogRegistry`; usá `CatalogHttpClient` para todas las solicitudes. Agregá pruebas de formato, campos ausentes, errores, límites y alcance antes de declarar soporte.

El cliente rechaza credenciales, IP literales, puertos no estándar y direcciones DNS privadas/reservadas. Valida cada redirección y fija la IP pública en cURL para evitar DNS rebinding; no utiliza proxy, sesión ni cookies. Límites: 2 MB por respuesta incluso descomprimida, tres redirecciones, tres segundos de conexión, ocho por solicitud y 50 para el conjunto de solicitudes de un adaptador. Los jobs tienen un intento y 65 segundos. No descarga imágenes, no ejecuta JavaScript y no elude CAPTCHA ni autenticación. Dux se decodifica como JSON, nunca mediante `eval`; si cambia el formato, se informa un error y se conservan las páginas previas obtenidas durante esa consulta.

Verificación local:

```sh
php vendor/bin/pint app/Services/Catalogs app/Jobs/FetchProviderCatalog.php app/Http/Controllers/Web/ProviderCatalogController.php app/Rules/CatalogUrl.php app/Models/ProviderCatalog.php tests/Feature/ProviderCatalogTest.php database/migrations/2026_10_09_000001_add_provider_web_catalogs.php
php artisan test tests/Feature/ProviderCatalogTest.php tests/Feature/ProviderCatalogHistoryTest.php tests/Feature/DuxCatalogTest.php
composer run test
npm run build
```

Resultado inicial de implementación: 34 pruebas del módulo aprobadas (71 assertions), Pint aprobado para los archivos nuevos, Vite compilado y vistas Blade compiladas. La suite completa inicial obtuvo 127 pruebas aprobadas y dos fallos en `ExampleTest` (302 frente a 200) y `InterBranchOrderTest` (costo 0 frente a 2500). Ambos fallos se reprodujeron con los archivos originales de HEAD y sin esta migración: son previos al módulo.

La incorporación posterior de Dux verificó 96 productos en cuatro páginas mediante el cliente Laravel seguro. Las pruebas de Dux y del módulo sumaron 40 casos aprobados (102 assertions), incluyendo transporte fragmentado de los registros JSON, ausencia de datos, límites de paginación, enlaces ajenos y fallos de páginas posteriores. Los resultados antiguos de «integración pendiente» se reemplazan al solicitar una nueva consulta. Si usás un worker persistente, reinicialo después de actualizar el código; `queue:listen` carga los cambios en el siguiente trabajo.

La incorporación del historial tiene 53 pruebas aprobadas (191 assertions), incluyendo permisos, guardado atómico e idempotente, conservación frente a nuevas consultas o cambios de URL, productos de varios proveedores, paginación y tratamiento seguro de enlaces e imágenes. La suite general obtuvo 146 pruebas aprobadas y los mismos dos fallos previos en `ExampleTest` e `InterBranchOrderTest`.

Para retirar el módulo, revertí sus rutas, enlaces, regla de validación y registro Vite junto con los archivos nuevos y sus migraciones. Revertir la migración del historial elimina las consultas procesadas y sus productos: respaldalos si necesitás conservarlos. Los productos internos y sus precios de compra no se modifican.
