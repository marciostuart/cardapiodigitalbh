[CmdletBinding(SupportsShouldProcess = $true, ConfirmImpact = 'Low')]
param(
    [switch]$NoLaunch
)

$ErrorActionPreference = 'Stop'

if (-not [Environment]::Is64BitOperatingSystem) {
    throw 'Este instalador requer Windows 64 bits.'
}

$packageDirectory = Split-Path -Parent $MyInvocation.MyCommand.Path
$sourceExecutable = Join-Path $packageDirectory 'CardapioPrinterAgent.exe'
$manifestPath = Join-Path $packageDirectory 'SHA256SUMS.txt'

if (-not (Test-Path -LiteralPath $sourceExecutable -PathType Leaf)) {
    throw 'CardapioPrinterAgent.exe não foi encontrado. Extraia o ZIP inteiro antes de instalar.'
}

$manifestText = [System.IO.File]::ReadAllText($manifestPath)
if ($manifestText -match '^\s*(?<hash>[A-Fa-f0-9]{64})\s+CardapioPrinterAgent\.exe\s*$') {
    $expectedHash = $Matches['hash'].ToUpperInvariant()
} else {
    throw 'O manifesto SHA256SUMS.txt está ausente ou inválido.'
}

$sourceStream = [System.IO.File]::OpenRead($sourceExecutable)
$sha256 = [System.Security.Cryptography.SHA256]::Create()
try {
    $sourceHash = [BitConverter]::ToString($sha256.ComputeHash($sourceStream)).Replace('-', '')
} finally {
    $sha256.Dispose()
    $sourceStream.Dispose()
}
if ($sourceHash -ne $expectedHash) {
    throw 'O executável não confere com o checksum do pacote.'
}

if (Get-Process -Name 'CardapioPrinterAgent' -ErrorAction SilentlyContinue) {
    throw 'Feche o agente pelo ícone ao lado do relógio (Sair) e execute o instalador novamente.'
}

$installDirectory = Join-Path $env:LOCALAPPDATA 'Programs\Cardapio Digital BH\Agente de Impressao'
$destinationExecutable = Join-Path $installDirectory 'CardapioPrinterAgent.exe'
$runKeyPath = 'HKCU:\Software\Microsoft\Windows\CurrentVersion\Run'
$previousAutoStart = (Get-ItemProperty -Path $runKeyPath -Name CardapioPrinterAgent -ErrorAction SilentlyContinue).CardapioPrinterAgent

if (-not $PSCmdlet.ShouldProcess($installDirectory, 'Instalar ou atualizar o agente de impressão')) {
    return
}

New-Item -ItemType Directory -Path $installDirectory -Force | Out-Null
$stagedExecutable = Join-Path $installDirectory ("CardapioPrinterAgent.{0}.tmp" -f [Guid]::NewGuid().ToString('N'))
$backupExecutable = Join-Path $installDirectory 'CardapioPrinterAgent.previous.exe'

try {
    Copy-Item -LiteralPath $sourceExecutable -Destination $stagedExecutable
    $stagedHash = (Get-FileHash -LiteralPath $stagedExecutable -Algorithm SHA256).Hash
    if ($stagedHash -ne $expectedHash) {
        throw 'A cópia temporária não confere com o checksum do pacote.'
    }

    if (Test-Path -LiteralPath $destinationExecutable) {
        if (Test-Path -LiteralPath $backupExecutable) {
            Remove-Item -LiteralPath $backupExecutable -Force
        }
        [System.IO.File]::Replace($stagedExecutable, $destinationExecutable, $backupExecutable, $true)
        Remove-Item -LiteralPath $backupExecutable -Force
    } else {
        [System.IO.File]::Move($stagedExecutable, $destinationExecutable)
    }
} finally {
    if (Test-Path -LiteralPath $stagedExecutable) {
        Remove-Item -LiteralPath $stagedExecutable -Force
    }
}

if ($previousAutoStart) {
    Set-ItemProperty -Path $runKeyPath -Name CardapioPrinterAgent -Value ('"{0}"' -f $destinationExecutable)
}

$programsDirectory = Join-Path ([Environment]::GetFolderPath('Programs')) 'Cardapio Digital BH'
New-Item -ItemType Directory -Path $programsDirectory -Force | Out-Null
$shortcutPath = Join-Path $programsDirectory 'Agente de Impressao.lnk'
$shell = New-Object -ComObject WScript.Shell
try {
    $shortcut = $shell.CreateShortcut($shortcutPath)
    $shortcut.TargetPath = $destinationExecutable
    $shortcut.WorkingDirectory = $installDirectory
    $shortcut.IconLocation = "$destinationExecutable,0"
    $shortcut.Save()
} finally {
    [void][Runtime.InteropServices.Marshal]::FinalReleaseComObject($shell)
}

Copy-Item -LiteralPath (Join-Path $packageDirectory 'Uninstall-PrinterAgent.ps1') -Destination $installDirectory -Force
Copy-Item -LiteralPath (Join-Path $packageDirectory 'Desinstalar.bat') -Destination $installDirectory -Force

Write-Host 'Agente instalado. A configuração de pareamento existente foi preservada.'
if (-not $NoLaunch) {
    Start-Process -FilePath $destinationExecutable
}
