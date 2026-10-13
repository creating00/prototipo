# Consultar y comparar catálogos web

Desde **Proveedores → Catálogos web y comparación de precios**, consultá la página configurada de un proveedor, revisá el resultado y presioná **Procesar** para conservarlo. Los productos procesados quedan disponibles en los combos de comparación de todos los proveedores, con imagen y fecha cuando existen. Cada nueva consulta reemplaza la vista previa; el historial procesado se conserva. No hay sincronización periódica.

## Puesta en marcha

1. Usá PHP compatible con Laravel 12, con cURL, DOM y las extensiones habituales del proyecto.
2. Ejecutá `php artisan migrate` para agregar `providers.catalog_url`, `providers.catalog_urls`, `provider_catalogs`, `provider_catalog_snapshots` y `provider_catalog_items`. La migración de múltiples URLs conserva las vistas previas existentes y permite una por proveedor y URL.
3. Configurá una cola asíncrona, por ejemplo `QUEUE_CONNECTION=database`. Ejecutá `php artisan queue:work --tries=1 --timeout=65`; el `retry_after` debe superar 65 segundos (el valor existente es 90). `composer run dev` ya inicia un listener.
4. Compilá con `npm run build`.
5. Editá el proveedor y guardá su URL. El acceso requiere `providers.view` y `product_provider_prices.view`. Consultar exige además la autorización `update` del proveedor, con las restricciones existentes de sucursal y modo consolidado. Administradores conservan las reglas globales del proyecto.

Una cola `sync` o `null` no permite iniciar consultas. Si pasan dos minutos sin avance del worker, la pantalla informa el problema y permite reintentar. Una lectura por páginas puede durar más mientras registre actividad; si se interrumpe, conserva los productos obtenidos como listado parcial. Las respuestas de trabajos vencidos o reemplazados no sobrescriben la consulta vigente. Cambiar la URL invalida la vista previa; conserva el historial procesado.

Los deploys de testing y master integran el reinicio de workers y el registro de cron para hosting compartido. Consultá [Cola de catálogos en Hostinger](deploy-queue.md) para los requisitos y el paso de hPanel cuando el hosting no permite registrar cron por SSH.

## Consultar y procesar

En la edición del proveedor, conservá la **URL principal** y agregá las **URLs adicionales de categorías**, una por línea: hasta 20 URLs distintas en total. Por ejemplo, Fast Cheap puede tener módulos, baterías, baterías originales, JCID y flex. Cada URL se valida con las mismas reglas de seguridad que la principal. No se precargan URLs ni se inicia ninguna consulta al guardar el proveedor.

Solo los roles `admin` y `provincial_admin` pueden administrar las URLs de catálogo y usar **Limpiar todos los catálogos web**. Los botones y campos se ocultan para otros usuarios; las solicitudes directas web y API también rechazan estos cambios, incluso si el usuario tiene permisos generales de proveedores. Los demás datos del proveedor conservan sus permisos existentes.

Verificación de la restricción: 87 pruebas del módulo aprobadas (398 assertions), incluyendo 13 casos específicos de roles, creación y edición web/API, campos vacíos, solicitudes anónimas y conservación de URLs al editar otros datos. Pint y compilación Blade aprobados. La suite general obtuvo 233 pruebas aprobadas y los dos fallos previos de `ExampleTest` e `InterBranchOrderTest` (929 assertions).

En «Consultar catálogo», seleccioná primero el proveedor y luego **URL o categoría del proveedor**. «Consultar ahora» y «Procesar» actúan solamente sobre esa URL. La tabla de categorías muestra el estado y la cantidad de productos obtenidos por cada una. Consultar una categoría conserva las vistas previas de las demás. Quitar una URL elimina únicamente su vista previa: los registros procesados siguen en el historial.

Los paneles **Productos publicados** e **Historial procesado** están ocultos al entrar. Sus encabezados permiten mostrarlos u ocultarlos; el listado de productos muestra cinco por página. La elección se conserva al paginar o actualizar el avance, mientras una visita nueva comienza con ambos ocultos. Durante la lectura se muestra la cantidad de productos encontrados y, cuando existe, el total publicado por el proveedor.

**Limpiar todos los catálogos web** elimina las vistas previas, todos los productos procesados y todo el historial de todos los proveedores, incluidos los dados de baja. Solicita una confirmación y está restringido a `admin` y `provincial_admin`, manteniendo las autorizaciones existentes de acceso y eliminación; no está disponible en modo consolidado. La operación es transaccional y no se puede deshacer. Conserva proveedores, URLs configuradas e inventario interno. Invalida los tokens de consultas en curso: sus jobs pendientes o en ejecución ya no pueden restaurar los datos eliminados. No borra otros trabajos de la cola.

Verificación de limpieza y paneles: **90 pruebas del módulo aprobadas (375 assertions)**. Incluye permisos, confirmación obligatoria, modo consolidado, eliminación de registros de proveedores dados de baja, conservación de URLs e inventario, rollback completo ante un error y resultados tardíos de una consulta invalidada. `composer run test` obtuvo 183 pruebas aprobadas y los dos fallos previos de `ExampleTest` e `InterBranchOrderTest` (696 assertions). Pint y Vite aprobados.

Los combos reúnen productos procesados de todas las URLs, agrupados por proveedor, y muestran la URL consultada junto con la fecha. También permiten buscar por URL de categoría. Un mismo producto puede aparecer en categorías superpuestas o consultas anteriores: se conservan sus registros de origen, sin fusionarlo por nombre o código. Cada sitio conserva el alcance de su adaptador; varias URLs no garantizan obtener la tienda completa.

### Fast Cheap: cargar toda una categoría

«Consultar ahora» sigue los enlaces públicos del botón **Cargar más productos** de Fast Cheap. Cada trabajo de cola lee una página, actualiza la vista previa y encola la siguiente. La pantalla muestra páginas leídas y productos obtenidos; «Procesar» se habilita al terminar. El worker o cron debe seguir activo para ejecutar las continuaciones. No se generan 400 URLs a ciegas ni se utiliza la API bloqueada.

Se verifica que cada enlace conserve dominio, categoría, filtros y número consecutivo. La lectura se detiene ante un error, cambio de total, rango discontinuo, falta de enlace o repetición sin productos nuevos. El listado se marca completo únicamente cuando empieza en el primer producto, termina sin siguiente página y la cantidad de productos distintos coincide con el contador publicado. Consultar una URL que empieza en una página intermedia conserva el alcance parcial. Se eliminan repeticiones por URL y SKU exactos, sin asociar nombres similares entre proveedores.

Límites por consulta: 400 páginas, 20.000 productos y 10 MB de datos acumulados, además de los límites HTTP por página. Al alcanzar un límite o fallar una página, lo obtenido permanece disponible como parcial. Se necesitan las columnas `pagination` y `last_activity_at` de la migración `2026_10_10_000003_add_catalog_pagination_progress.php`: ejecutá `php artisan migrate --force` y `php artisan queue:restart` después de actualizar el servidor.

**Verificación directa del 10/10/2026:** la categoría [módulos](https://www.fast-cheap.com.ar/categorias/modulo/) se leyó con `FetchProviderCatalog` y el cliente seguro en una base SQLite temporal en memoria: **36 páginas, 849 productos distintos, 849 precios y 849 imágenes**, estado final `complete`. No se guardó esta prueba en la base del proyecto. El total puede cambiar; la comprobación acredita el listado de esa categoría durante la consulta, sin obtener fichas individuales ni asegurar un estado simultáneo de todos los precios.

Verificación final de paginación: **78 pruebas del módulo aprobadas (314 assertions)**, incluyendo una continuación serializada ejecutada desde la cola `database`, errores posteriores, entrega duplicada, compatibilidad con jobs anteriores al deploy, enlaces inesperados, límite de páginas y vencimiento por falta de actividad. Pint, compilación de Vite y compilación de Blade aprobados; migración aplicada en local. `composer run test` registró 169 pruebas aprobadas y los dos fallos previos de `ExampleTest` e `InterBranchOrderTest`; después se agregaron y aprobaron las pruebas de compatibilidad del payload y ejecución real de la continuación.

La migración utiliza `source_key` (SHA-256 de la URL) para mantener una vista previa por proveedor y URL sin indexar el texto largo de la dirección. Para revertirla, si hay varias vistas previas de un proveedor, debés retirarlas explícitamente primero; la reversión no descarta datos para forzar el antiguo índice único. El historial procesado conserva su URL de origen.

En la evaluación inicial del 10 de octubre de 2026 se verificaron las cinco URLs de categorías indicadas mediante `CatalogRegistry` y el cliente Laravel seguro, sin guardar productos: [módulos](https://www.fast-cheap.com.ar/categorias/modulo/) 24, [baterías](https://www.fast-cheap.com.ar/categorias/bateria/) 24, [baterías originales](https://www.fast-cheap.com.ar/categorias/bateria/bateria-original/) 24, [baterías JCID](https://www.fast-cheap.com.ar/categorias/bateria/bateria-jcid/) 22 y [flex](https://www.fast-cheap.com.ar/categorias/flex/) 24. Todos los registros obtenidos incluían precio e imagen. Esos conteos corresponden a la primera página, antes de incorporar la consulta por tandas; no acreditan cobertura completa de esas otras categorías.

Verificación de múltiples URLs: 66 pruebas del módulo aprobadas (249 assertions), Vite compilado y migración aplicada a la base local. La suite general obtuvo 159 pruebas aprobadas y los mismos dos fallos previos en `ExampleTest` e `InterBranchOrderTest`.

1. Seleccioná el proveedor: la selección actualiza la pantalla, sin botón «Ver».
2. Presioná **Consultar ahora**. Al terminar, revisá los productos publicados y el mensaje de alcance o error. Esto todavía no incorpora productos al historial de comparación.
3. Presioná **Procesar**. Se guarda una copia completa del resultado obtenido, su URL, proveedor, fecha de consulta, fecha de procesamiento y usuario. Si el resultado es parcial, el historial conserva esa advertencia. Un error, una consulta pendiente o una respuesta sin productos no se puede procesar.
4. Abrí cualquier registro de **Historial procesado** para revisar sus productos y datos de origen. Procesar dos veces la misma consulta no duplica el registro; consultar nuevamente y procesar crea otro registro, aunque los precios no hayan cambiado.

`provider_catalogs` contiene solamente la última vista previa por proveedor y URL, con su avance de paginación. `provider_catalog_snapshots` conserva cada consulta procesada y `provider_catalog_items` conserva sus productos y datos publicados en JSON. El guardado es transaccional: si un producto no se puede guardar, no queda un historial incompleto. No se importan productos al inventario interno ni se procesan automáticamente las consultas existentes.

### Dux: recorrer el catálogo o rubro por tandas

La consulta desde la pantalla usa un trabajo de cola por página y ya no se detiene en cuatro páginas. Sigue el enlace público siguiente conservando catálogo, `id_rubro`, búsqueda y los demás filtros. Verifica la página activa, la cantidad de tarjetas frente a los registros JSON extraídos y el control siguiente deshabilitado al finalizar. Los SKU se usan para detectar repeticiones dentro de esta consulta de Dux; no prueban equivalencia entre proveedores.

El límite es de 400 páginas, 20.000 productos y 10 MB acumulados. Un enlace inesperado, una repetición, un fallo de extracción o una interrupción dejan lo obtenido como parcial. Empezar en una página intermedia también queda parcial. El HTML examinado no publica un total estructurado de productos o páginas: se conservan esos valores nulos y no se calcula un total multiplicando páginas por 24.

**Verificación real del 13/10/2026:** [rubro 128087 de Byte Rioja](https://catalogo.duxsoftware.com.ar/byteriojauno?id_rubro=128087&page=1), leído con `FetchProviderCatalog` y el cliente seguro en SQLite temporal en memoria: **1.297 productos y SKU distintos en 55 páginas**, todos con precio y moneda `PESOS`; **787 con imagen publicada**. Las primeras 54 páginas publicaron 24 productos cada una y la última publicó uno, con el control siguiente deshabilitado. El estado final fue `complete`. No se guardaron productos en la base del proyecto y no se garantiza cobertura de otros rubros ni de la tienda completa.

Después del deploy, reiniciá workers con `php artisan queue:restart` y mantené activo el worker o cron. Esta corrección no agrega migraciones ni cambios de frontend. Solicitá **Consultar ahora** nuevamente y **Procesar** al terminar para guardar el resultado; los registros históricos de 96 productos se conservan. El método directo `DuxAdapter::fetch` mantiene su lectura acotada por compatibilidad; la pantalla y sus jobs usan `page` con continuaciones en la cola.

Verificación de la paginación de Dux: 57 pruebas de Dux, i2C y Fast Cheap aprobadas (255 assertions), incluyendo lectura más allá de cuatro páginas, conservación de rubro, enlaces inesperados, límite 400, errores posteriores, SKU repetidos y señales de final ausentes. Pint aprobado. La suite general obtuvo 252 pruebas aprobadas y los dos fallos previos de `ExampleTest` e `InterBranchOrderTest` (1026 assertions).

### i2C: búsquedas y Repuestos por páginas

Las URLs `/search/page/1/?q=modulos` y `/search/?q=modulos` permiten consultar la búsqueda por tandas. El adaptador sigue el enlace siguiente publicado, conserva la búsqueda y los demás filtros y verifica el contador de páginas. Se detiene en el final publicado o en la página 400 como máximo; también aplica los límites de 20.000 registros y 10 MB acumulados. No consulta páginas inexistentes para completar el límite.

También se verificaron `/repuestos/` y `/repuestos/page/N/`. La lectura utiliza la sección de categoría y exige que los enlaces siguientes conserven `/repuestos/` y todos los filtros. No habilita otras categorías ni subcategorías sin verificar su formato. Para obtener Repuestos desde el inicio, configurá `/repuestos/`; comenzar en `/repuestos/page/2/` deja el resultado parcial.

Los productos se extraen del JSON-LD correspondiente a las tarjetas de la grilla. Un cambio de contador, un enlace inesperado, productos repetidos entre páginas, una extracción incompleta o un fallo posterior dejan el resultado parcial y conservan lo obtenido. Una consulta que empieza en una página intermedia también queda parcial. El sitio publica cantidad de páginas, pero no un total de productos: ese total permanece sin informar.

**Verificación directa del 10/10/2026:** [búsqueda de módulos](https://www.i2cmayorista.com/search/page/1/?q=modulos), ejecutada con `FetchProviderCatalog` y el cliente seguro en SQLite temporal en memoria: **557 productos en 12 páginas**, todos con precio, moneda e imagen, estado final `complete`. La consulta posterior de [Repuestos](https://www.i2cmayorista.com/repuestos/) obtuvo **281 productos en seis páginas**, también con precio, moneda e imagen y estado `complete`. No se guardaron estas pruebas en la base del proyecto. Los resultados acreditan esos listados durante cada consulta; no garantizan cubrir toda la tienda. La portada y rutas distintas de búsquedas y Repuestos conservan el adaptador de página única y su alcance parcial. No se consultan fichas individuales; disponibilidad y precios del JSON-LD requieren verificar condiciones en origen.

Después de desplegar el código, ejecutá `php artisan queue:restart` y mantené activo el worker o cron. Esta incorporación no agrega migraciones. Solicitá una nueva consulta para reemplazar las vistas previas anteriores.

Verificación de esta incorporación: **109 pruebas del módulo aprobadas (443 assertions)**, incluyendo conservación de filtros, páginas intermedias, límite 400, cambios de navegación, fallos posteriores y ofertas múltiples. Pint aprobado. `composer run test` obtuvo **202 pruebas aprobadas y dos fallos previos** en `ExampleTest` e `InterBranchOrderTest` (764 assertions). No hubo cambios de frontend en esta incorporación.

Al incorporar Repuestos, las pruebas específicas de paginación de i2C y Fast Cheap sumaron **38 casos aprobados (166 assertions)**. La suite general obtuvo **208 pruebas aprobadas y los mismos dos fallos previos** (786 assertions); después de ajustar el selector a la sección de categoría publicada, se repitieron las pruebas específicas y la lectura real completa. Pint aprobado.

## Fuentes verificadas el 9, 10 y 13 de octubre de 2026

Se consultaron las URLs públicas sin credenciales y luego se ejecutaron los adaptadores mediante el cliente HTTP seguro de Laravel. Los conteos son evidencia de esa evaluación; pueden cambiar en origen.

| Fuente | Extracción verificada | Alcance y límites |
| --- | --- | --- |
| [i2C](https://www.i2cmayorista.com/) | 20 productos en la portada durante la evaluación inicial; 557 productos en 12 páginas de la búsqueda de módulos y 281 productos en seis páginas de Repuestos (10/10/2026), con precio, moneda e imagen | Búsquedas `/search/`, `/search/page/N/` y categoría `/repuestos/`, `/repuestos/page/N/` con paginación verificada, hasta la página 400. Otras rutas conservan lectura de una sola página, hasta 500 registros y alcance parcial. La disponibilidad del JSON-LD puede diferir de la tarjeta visual. |
| [UNV](https://unvarg.com/search/?q=lenovo) | 2 productos en JSON-LD | Mantiene la búsqueda de la URL; no representa todo el catálogo. |
| [Fast Cheap](https://www.fast-cheap.com.ar/) | 59 tarjetas de la portada en la evaluación inicial; posteriormente, 849 productos en 36 páginas de módulos (10/10/2026) | Adaptador específico del tema Kapee/WooCommerce. La consulta en cola sigue los enlaces de «Cargar más productos», verificando el contador de la categoría. La portada puede seguir siendo parcial. La Store API devolvió 403 y no se utiliza. No se consultan fichas individuales. El símbolo `$` queda sin moneda. Rangos de precio quedan sin importe único. |
| [S&R](https://syrcelulares.com/) | 400 productos de la Store API pública | Solo URL principal; hasta cuatro páginas de 100 productos. La API declaró 611 productos durante la evaluación: el resultado es parcial. Los atributos y variantes se conservan en los datos de origen; no se inventa precio de cada variante. |
| [Google Sheets](https://docs.google.com/spreadsheets/d/1c39kYssBH8TWE4JPZ8c4xy9OJ4RnsCme/edit?gid=1972998278) | 565 pares de nombre y precio en CSV | Solo este documento y pestaña. Formato específico de bloques con celdas adyacentes producto/precio; hasta 1000 productos. No se interpreta `$` como ARS o USD. Otros bloques o pestañas requieren otro adaptador. |
| [Dux](https://catalogo.duxsoftware.com.ar/byteriojauno?id_rubro=128087&page=1) | Evaluación inicial: 96 productos en cuatro páginas. Verificación del rubro 128087 (13/10): 1.297 productos en 55 páginas, con nombre, SKU, precio y moneda `PESOS`; 787 imágenes | Registros JSON de Next.js Flight embebidos en HTML, sin ejecutar JavaScript ni consultar APIs privadas. La pantalla sigue enlaces publicados mediante la cola, conservando catálogo y filtros, hasta 400 páginas. Verifica el final deshabilitado y detecta SKU repetidos. Sin detalles de variantes. El campo `url` de Dux es una imagen: el enlace de origen lleva a la página del catálogo, sin inventar un permalink de producto. |

No se garantiza cobertura completa de una tienda a partir de su portada o de una página de búsqueda. La pantalla distingue consulta en cola, listado obtenido, listado parcial, error e integración pendiente; muestra fecha, alcance y motivo. Una extracción parcial de S&R conserva las páginas obtenidas si falla una posterior.

## Comparación

El buscador **Buscar un producto en todos los catálogos** permite seleccionar un producto procesado y cargar candidatos en los ocho campos de comparación. Busca todas las palabras ingresadas en nombres y códigos; si no se ingresó texto, usa el nombre o código publicado del producto elegido. El producto seleccionado ocupa el primer campo, con su fecha original. Los candidatos de otros proveedores usan la consulta procesada más reciente de cada URL, conservando imágenes, precios y fechas. La consulta no accede a los sitios de proveedores ni guarda asociaciones.

Un único candidato por proveedor queda precargado **para revisión**; si hay varias coincidencias, el proveedor queda asignado y el combo ofrece sus candidatos sin seleccionar ninguno. Se muestran hasta ocho proveedores y veinte candidatos iniciales por proveedor, con aviso si hay más y posibilidad de refinar la búsqueda en el combo. Las coincidencias por nombre o código no prueban equivalencia: la confirmación manual sigue siendo obligatoria y se desmarca al cambiar productos o proveedores. Elegir otro producto en el buscador general reemplaza las selecciones; una búsqueda fallida conserva las anteriores.

Verificación del buscador general: 31 pruebas de búsqueda e historial aprobadas (201 assertions) y cuatro pruebas Node del módulo JavaScript con controles aislados, incluyendo respuestas atrasadas y cambios manuales durante una consulta. La suite general obtuvo 239 pruebas aprobadas y los dos fallos previos de `ExampleTest` e `InterBranchOrderTest` (967 assertions). Pint, Vite y compilación de Blade y rutas aprobados.

Cada campo de producto tiene un filtro **Filtrar proveedor**. Elegí un proveedor para buscar únicamente en sus productos procesados o conservá **Todos los proveedores con productos guardados**. El filtro incluye proveedores con historial aunque se haya retirado su URL o dado de baja al proveedor; excluye vistas previas sin procesar. Cambiarlo limpia la selección de ese campo y reinicia su búsqueda y paginación. Los otros productos elegidos se conservan.

Seleccioná entre dos y ocho productos de al menos dos proveedores. Los combos agrupan todos los productos procesados por proveedor, muestran miniatura, código, precio, moneda y fecha de consulta, y permiten buscar por nombre, código o proveedor. La lista carga de a 50 productos al desplazarte: no se limita a la primera página. Las consultas anteriores también aparecen, identificadas por fecha. Revisá los enlaces de origen, modelo, variante, presentación y condiciones; confirmá la equivalencia explícitamente. La confirmación aplica a esa comparación y no crea asociaciones internas permanentes.

No se asigna equivalencia por nombre, SKU interno o similitud. Toda extracción indica **requiere revisión**. La comparación agrupa por moneda publicada; no convierte monedas ni normaliza impuestos, descuentos o cantidades. Sin moneda, solo se muestra el importe publicado con la advertencia correspondiente. Las selecciones apuntan a productos del historial: una nueva consulta no las invalida. La comparación muestra la fecha y el registro de origen; los precios guardados pueden haber cambiado en la web del proveedor.

Los campos ausentes quedan nulos y se muestran como **No informado**. Se conserva el objeto estructurado original o los datos de la tarjeta/celda en **Todos los datos publicados por la fuente**. Las imágenes se muestran en la vista previa, el historial, los combos y la comparación. El navegador carga la URL publicada cuando corresponde; el servidor no descarga ni almacena archivos de imagen. Si no hay imagen o falla su carga en el combo, se muestra «Sin imagen».

## Extender y verificar

Los adaptadores implementan `App\Services\Catalogs\CatalogAdapter` y devuelven `CatalogResult`. Registrá los hosts explícitos en `CatalogRegistry`; usá `CatalogHttpClient` para todas las solicitudes. Agregá pruebas de formato, campos ausentes, errores, límites y alcance antes de declarar soporte.

Para lectura por tandas, implementá `PaginatedCatalogAdapter` y registrá únicamente los hosts y rutas verificados en `CatalogRegistry::paginatedAdapter`. `FetchProviderCatalog` coordina las continuaciones y conserva el avance. El modo `range` verifica rangos y total de productos; `numbered` verifica contador de páginas y final publicado. Los conteos ausentes quedan nulos: no se deduce un total de productos multiplicando páginas por tamaño de grilla.

El modo `linked`, usado por Dux, verifica números consecutivos, página activa y control de fin explícito, sin exigir un total de páginas ausente. `identity_mode` permite detectar los SKU repetidos de Dux aunque el enlace del producto sea la página de origen; los otros adaptadores conservan su identidad por URL/SKU.

El cliente rechaza credenciales, IP literales, puertos no estándar y direcciones DNS privadas/reservadas. Valida cada redirección y fija la IP pública en cURL para evitar DNS rebinding; no utiliza proxy, sesión ni cookies. Límites: 2 MB por respuesta incluso descomprimida, tres redirecciones, tres segundos de conexión, ocho por solicitud y 50 para el conjunto de solicitudes de un adaptador. Los jobs tienen un intento y 65 segundos. No descarga imágenes, no ejecuta JavaScript y no elude CAPTCHA ni autenticación. Dux se decodifica como JSON, nunca mediante `eval`; si cambia el formato, se informa un error y se conservan las páginas previas obtenidas durante esa consulta.

Verificación local:

```sh
php vendor/bin/pint app/Services/Catalogs app/Jobs/FetchProviderCatalog.php app/Http/Controllers/Web/ProviderCatalogController.php app/Rules/CatalogUrl.php app/Models/ProviderCatalog.php tests/Feature/ProviderCatalogTest.php database/migrations/2026_10_09_000001_add_provider_web_catalogs.php
php artisan test tests/Feature/ProviderCatalogTest.php tests/Feature/ProviderCatalogHistoryTest.php tests/Feature/ProviderCatalogUrlsTest.php tests/Feature/DuxCatalogTest.php tests/Feature/DuxCatalogPaginationTest.php tests/Feature/FastCheapPaginationTest.php tests/Feature/I2cCatalogPaginationTest.php
composer run test
node --test tests/Frontend/catalog-general-search.test.cjs
npm run build
```

Resultado inicial de implementación: 34 pruebas del módulo aprobadas (71 assertions), Pint aprobado para los archivos nuevos, Vite compilado y vistas Blade compiladas. La suite completa inicial obtuvo 127 pruebas aprobadas y dos fallos en `ExampleTest` (302 frente a 200) y `InterBranchOrderTest` (costo 0 frente a 2500). Ambos fallos se reprodujeron con los archivos originales de HEAD y sin esta migración: son previos al módulo.

La incorporación posterior de Dux verificó 96 productos en cuatro páginas mediante el cliente Laravel seguro. Las pruebas de Dux y del módulo sumaron 40 casos aprobados (102 assertions), incluyendo transporte fragmentado de los registros JSON, ausencia de datos, límites de paginación, enlaces ajenos y fallos de páginas posteriores. Los resultados antiguos de «integración pendiente» se reemplazan al solicitar una nueva consulta. Si usás un worker persistente, reinicialo después de actualizar el código; `queue:listen` carga los cambios en el siguiente trabajo.

La incorporación del historial tiene 53 pruebas aprobadas (191 assertions), incluyendo permisos, guardado atómico e idempotente, conservación frente a nuevas consultas o cambios de URL, productos de varios proveedores, paginación y tratamiento seguro de enlaces e imágenes. La suite general obtuvo 146 pruebas aprobadas y los mismos dos fallos previos en `ExampleTest` e `InterBranchOrderTest`.

Para retirar el módulo, revertí sus rutas, enlaces, regla de validación y registro Vite junto con los archivos nuevos y sus migraciones. Revertir la migración del historial elimina las consultas procesadas y sus productos: respaldalos si necesitás conservarlos. Los productos internos y sus precios de compra no se modifican.
