# Abrir la app sin el panel de Laragon

Desde la raiz del repositorio, ejecutar `iniciar-web.bat` (tambien funciona con doble clic).
El lanzador usa PHP, Node y MySQL de `C:\laragon`; no abre `laragon.exe` ni necesita Apache.

La conexion se obtiene de la configuracion efectiva de Laravel, incluyendo su cache de configuracion. Actualmente apunta a `prototipo_local` en `127.0.0.1:3306`. Reutiliza MySQL si ya escucha en ese puerto; de lo contrario lo inicia con el `my.ini` de Laragon y su directorio de datos existente. Comprueba la conexion a la base antes de arrancar la web.

Arranca Laravel en `http://127.0.0.1:8000`, Vite en el puerto `5173` y un worker de cola. Abre la web en el navegador predeterminado. Mantener la consola abierta; `Ctrl+C` detiene los procesos de la app iniciados por este script. MySQL queda activo para otras aplicaciones.

Opciones desde PowerShell:

```powershell
.\iniciar-web.bat -NoBrowser
.\iniciar-web.bat -Port 8080 -VitePort 5174
.\iniciar-web.bat -LaragonPath D:\laragon -NoQueue
```

Requiere `.env`, dependencias PHP (`composer install`), dependencias frontend (`npm install`) y una base ya preparada. No crea bases, ejecuta migraciones ni modifica credenciales. Si cambia `.env` y hay configuracion cacheada, ejecutar `php artisan config:clear` usando el PHP de Laragon.

Los errores y salidas de cada proceso quedan en `storage/logs/local-launcher/`. Si un puerto web esta ocupado, cerrar el proceso anterior o elegir otro. La version PHP encontrada debe ser 8.2 o superior; el lanzador busca ejecutables instalados y omite carpetas que contienen solamente fuentes.
