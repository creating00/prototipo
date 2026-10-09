param(
    [string]$LaragonPath = 'C:\laragon',
    [ValidateRange(1024, 65535)][int]$Port = 8000,
    [ValidateRange(1024, 65535)][int]$VitePort = 5173,
    [switch]$NoBrowser,
    [switch]$NoQueue
)

$ErrorActionPreference = 'Stop'
$projectPath = Split-Path -Parent $PSScriptRoot
$logPath = Join-Path $projectPath 'storage\logs\local-launcher'
$ownedProcesses = @()
$oldPath = $env:PATH
$oldAppUrl = $env:APP_URL
$oldLocation = Get-Location
$appUrl = "http://127.0.0.1:$Port"

function Test-LocalPort([int]$Number) {
    $client = New-Object System.Net.Sockets.TcpClient
    try {
        $pending = $client.ConnectAsync('127.0.0.1', $Number)
        return ($pending.Wait(500) -and $client.Connected)
    } catch {
        return $false
    } finally {
        $client.Dispose()
    }
}

function Find-Binary([string]$Folder, [string]$RelativeBinary) {
    $root = Join-Path $LaragonPath "bin\$Folder"
    $candidates = @(Get-ChildItem -LiteralPath $root -Directory |
        Sort-Object Name -Descending |
        ForEach-Object { Join-Path $_.FullName $RelativeBinary } |
        Where-Object { Test-Path -LiteralPath $_ -PathType Leaf })
    if (!$candidates.Count) {
        throw "No se encontro $RelativeBinary en $root."
    }
    return $candidates[0]
}

function Start-LocalProcess([string]$Executable, [string[]]$Arguments, [string]$Name, [string]$Directory) {
    return Start-Process -FilePath $Executable -ArgumentList $Arguments -WorkingDirectory $Directory `
        -WindowStyle Hidden -PassThru `
        -RedirectStandardOutput (Join-Path $logPath "$Name.log") `
        -RedirectStandardError (Join-Path $logPath "$Name-error.log")
}

function Wait-LocalService([int]$Number, [System.Diagnostics.Process]$Process, [string]$Name) {
    $deadline = (Get-Date).AddSeconds(45)
    while ((Get-Date) -lt $deadline) {
        if ($Process.HasExited) {
            throw "$Name se cerro. Revise los logs en $logPath."
        }
        if (Test-LocalPort $Number) { return }
        Start-Sleep -Milliseconds 300
    }
    throw "$Name no respondio en el puerto $Number. Revise $logPath."
}

try {
    Set-Location -LiteralPath $projectPath
    foreach ($required in @('.env', 'vendor\autoload.php', 'node_modules\vite\bin\vite.js')) {
        if (!(Test-Path -LiteralPath (Join-Path $projectPath $required))) {
            throw "Falta $required. Prepare .env e instale las dependencias con composer install y npm install."
        }
    }
    if ($Port -eq $VitePort) { throw 'Laravel y Vite necesitan puertos distintos.' }
    foreach ($number in @($Port, $VitePort)) {
        if (Test-LocalPort $number) {
            throw "El puerto $number ya esta ocupado. Cierre el servidor anterior o elija otro puerto."
        }
    }

    New-Item -ItemType Directory -Path $logPath -Force | Out-Null
    $php = Find-Binary 'php' 'php.exe'
    $node = Find-Binary 'nodejs' 'node.exe'
    $env:PATH = "$(Split-Path $php);$(Split-Path $node);$oldPath"
    $env:APP_URL = $appUrl
    & $php -r "exit(version_compare(PHP_VERSION, '8.2.0', '>=') ? 0 : 1);"
    if ($LASTEXITCODE -ne 0) { throw 'Se requiere PHP 8.2 o superior.' }

    $databaseJson = & $php (Join-Path $PSScriptRoot 'laragon-database.php')
    if ($LASTEXITCODE -ne 0) { throw 'No se pudo leer la configuracion de Laravel.' }
    $database = $databaseJson | ConvertFrom-Json
    if ($database.driver -ne 'mysql' -or $database.host -notin @('127.0.0.1', 'localhost')) {
        throw 'Este lanzador requiere una conexion MySQL local configurada en Laravel.'
    }
    $databasePort = [int]$database.port
    if ($databasePort -in @($Port, $VitePort)) { throw 'La base y la web necesitan puertos distintos.' }

    if (!(Test-LocalPort $databasePort)) {
        $mysql = Find-Binary 'mysql' 'bin\mysqld.exe'
        $mysqlRoot = Split-Path (Split-Path $mysql)
        $mysqlConfig = Join-Path $mysqlRoot 'my.ini'
        if (!(Test-Path -LiteralPath $mysqlConfig)) { throw "Falta $mysqlConfig." }
        $configText = Get-Content -LiteralPath $mysqlConfig -Raw
        $dataMatch = [regex]::Match($configText, '(?im)^\s*datadir\s*=\s*"?([^"\r\n]+)')
        if (!$dataMatch.Success -or !(Test-Path -LiteralPath $dataMatch.Groups[1].Value.Trim() -PathType Container)) {
            throw 'El datadir de MySQL no existe. Revise my.ini; no se inicializara una base nueva.'
        }
        Write-Host "Iniciando MySQL de Laragon en el puerto $databasePort..."
        $mysqlProcess = Start-LocalProcess $mysql @("--defaults-file=`"$mysqlConfig`"", "--port=$databasePort") 'mysql' $mysqlRoot
        Wait-LocalService $databasePort $mysqlProcess 'MySQL'
    } else {
        Write-Host "Reutilizando MySQL activo en el puerto $databasePort."
    }

    & $php (Join-Path $PSScriptRoot 'laragon-database.php') check
    if ($LASTEXITCODE -ne 0) { throw 'La base configurada no esta disponible. Revise nombre, usuario y clave en .env.' }
    Write-Host "Base conectada: $($database.database)"

    # Use Laravel's router directly, avoiding artisan serve child processes.
    $router = Join-Path $projectPath 'vendor\laravel\framework\src\Illuminate\Foundation\resources\server.php'
    $web = Start-LocalProcess $php @('-S', "127.0.0.1:$Port", "`"$router`"") 'web' (Join-Path $projectPath 'public')
    $ownedProcesses += $web
    Wait-LocalService $Port $web 'Laravel'

    $viteScript = Join-Path $projectPath 'node_modules\vite\bin\vite.js'
    $vite = Start-LocalProcess $node @("`"$viteScript`"", '--host', '127.0.0.1', '--port', "$VitePort", '--strictPort') 'vite' $projectPath
    $ownedProcesses += $vite
    Wait-LocalService $VitePort $vite 'Vite'

    if (!$NoQueue) {
        $queue = Start-LocalProcess $php @('artisan', 'queue:work', '--tries=1', '--sleep=1') 'queue' $projectPath
        $ownedProcesses += $queue
    }

    Write-Host "App lista: $appUrl"
    Write-Host "Logs: $logPath"
    Write-Host 'Mantenga esta consola abierta. Ctrl+C detiene la web, Vite y la cola; MySQL permanece disponible.'
    if (!$NoBrowser) { Start-Process $appUrl }
    while ($true) {
        foreach ($process in $ownedProcesses) {
            if ($process.HasExited) { throw "Un servidor se cerro. Revise los logs en $logPath." }
        }
        Start-Sleep -Seconds 1
    }
} catch {
    Write-Host "Error: $($_.Exception.Message)" -ForegroundColor Red
    exit 1
} finally {
    foreach ($process in $ownedProcesses) {
        if (!$process.HasExited) { Stop-Process -Id $process.Id -ErrorAction SilentlyContinue }
    }
    $hotFile = Join-Path $projectPath 'public\hot'
    if ($vite -and $vite.HasExited -and (Test-Path -LiteralPath $hotFile)) {
        $hotUrl = (Get-Content -LiteralPath $hotFile -Raw).Trim()
        if ($hotUrl -eq "http://127.0.0.1:$VitePort") {
            Remove-Item -LiteralPath $hotFile
        }
    }
    $env:PATH = $oldPath
    $env:APP_URL = $oldAppUrl
    Set-Location -LiteralPath $oldLocation.Path
}
