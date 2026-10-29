[CmdletBinding()]
param(
    [string]$Version = '1.0.0',
    [string]$OutputDirectory = (Join-Path $env:TEMP 'CardapioPrinterAgent-packages')
)

$ErrorActionPreference = 'Stop'

if ($Version -notmatch '^\d+\.\d+\.\d+([.-][0-9A-Za-z.-]+)?$') {
    throw 'Informe uma versão no formato X.Y.Z, por exemplo 1.0.0.'
}

$projectPath = Join-Path $PSScriptRoot 'CardapioPrinterAgent.csproj'
$timestamp = Get-Date -Format 'yyyyMMdd-HHmmssfff'
$buildDirectory = Join-Path $OutputDirectory "build-$Version-$timestamp"
$publishDirectory = Join-Path $buildDirectory 'publish'
$packageDirectory = Join-Path $buildDirectory 'package'
$zipPath = Join-Path $OutputDirectory "CardapioPrinterAgent-win-x64-$Version-$timestamp.zip"

New-Item -ItemType Directory -Path $OutputDirectory -Force | Out-Null
if (Test-Path -LiteralPath $buildDirectory) {
    throw "A pasta temporária do pacote já existe: $buildDirectory"
}
New-Item -ItemType Directory -Path $publishDirectory | Out-Null
New-Item -ItemType Directory -Path $packageDirectory | Out-Null

$publishArguments = @(
    'publish', $projectPath,
    '-c', 'Release',
    '-r', 'win-x64',
    '--self-contained', 'true',
    '-p:PublishSingleFile=true',
    '-p:IncludeNativeLibrariesForSelfExtract=true',
    '-p:DebugSymbols=false',
    '-p:DebugType=None',
    "-p:Version=$Version",
    '-o', $publishDirectory
)

& dotnet @publishArguments
if ($LASTEXITCODE -ne 0) {
    throw "dotnet publish falhou com código $LASTEXITCODE."
}

$publishedExecutable = Join-Path $publishDirectory 'CardapioPrinterAgent.exe'
if (-not (Test-Path -LiteralPath $publishedExecutable -PathType Leaf)) {
    throw 'O executável publicado não foi encontrado.'
}

Copy-Item -LiteralPath $publishedExecutable -Destination $packageDirectory
Copy-Item -LiteralPath (Join-Path $PSScriptRoot 'Install-PrinterAgent.ps1') -Destination $packageDirectory
Copy-Item -LiteralPath (Join-Path $PSScriptRoot 'Uninstall-PrinterAgent.ps1') -Destination $packageDirectory
Copy-Item -LiteralPath (Join-Path $PSScriptRoot 'Instalar.bat') -Destination $packageDirectory
Copy-Item -LiteralPath (Join-Path $PSScriptRoot 'Desinstalar.bat') -Destination $packageDirectory
Copy-Item -LiteralPath (Join-Path $PSScriptRoot 'README.md') -Destination $packageDirectory

$executableHash = (Get-FileHash -LiteralPath (Join-Path $packageDirectory 'CardapioPrinterAgent.exe') -Algorithm SHA256).Hash
$newline = [Environment]::NewLine
[System.IO.File]::WriteAllText(
    (Join-Path $packageDirectory 'SHA256SUMS.txt'),
    $executableHash + '  CardapioPrinterAgent.exe' + $newline,
    [System.Text.Encoding]::ASCII)

if (Test-Path -LiteralPath $zipPath) {
    throw "O pacote já existe: $zipPath"
}

Compress-Archive -Path (Join-Path $packageDirectory '*') -DestinationPath $zipPath -CompressionLevel Optimal
$packageHash = (Get-FileHash -LiteralPath $zipPath -Algorithm SHA256).Hash
[System.IO.File]::WriteAllText(
    "$zipPath.sha256",
    $packageHash + '  ' + (Split-Path -Leaf $zipPath) + $newline,
    [System.Text.Encoding]::ASCII)

$packageSizeMb = [Math]::Round((Get-Item -LiteralPath $zipPath).Length / 1MB, 1)
Write-Host "PACOTE=$zipPath"
Write-Host "SHA256=$packageHash"
Write-Host "TAMANHO_MB=$packageSizeMb"
