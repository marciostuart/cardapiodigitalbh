[CmdletBinding(SupportsShouldProcess = $true, ConfirmImpact = 'Medium')]
param(
    [switch]$RemoveLocalData
)

$ErrorActionPreference = 'Stop'

if (Get-Process -Name 'CardapioPrinterAgent' -ErrorAction SilentlyContinue) {
    throw 'Feche o agente pelo ícone ao lado do relógio (Sair) e execute a desinstalação novamente.'
}

$programRoot = [System.IO.Path]::GetFullPath((Join-Path $env:LOCALAPPDATA 'Programs\Cardapio Digital BH'))
$installDirectory = [System.IO.Path]::GetFullPath((Join-Path $programRoot 'Agente de Impressao'))
if ([System.IO.Path]::GetDirectoryName($installDirectory) -ne $programRoot) {
    throw 'O caminho de instalação não confere; nada foi removido.'
}

$runKeyPath = 'HKCU:\Software\Microsoft\Windows\CurrentVersion\Run'
$programsDirectory = Join-Path ([Environment]::GetFolderPath('Programs')) 'Cardapio Digital BH'
$shortcutPath = Join-Path $programsDirectory 'Agente de Impressao.lnk'
$localDataDirectory = Join-Path $env:LOCALAPPDATA 'CardapioDigitalBH'

if ($PSCmdlet.ShouldProcess($installDirectory, 'Remover o executável instalado')) {
    Remove-ItemProperty -Path $runKeyPath -Name CardapioPrinterAgent -ErrorAction SilentlyContinue
    if (Test-Path -LiteralPath $shortcutPath) {
        Remove-Item -LiteralPath $shortcutPath -Force
    }
    if (Test-Path -LiteralPath $installDirectory) {
        Remove-Item -LiteralPath $installDirectory -Recurse -Force
    }
}

if ((Test-Path -LiteralPath $programsDirectory -PathType Container) -and
    -not (Get-ChildItem -LiteralPath $programsDirectory -Force | Select-Object -First 1)) {
    if ($PSCmdlet.ShouldProcess($programsDirectory, 'Remover pasta vazia do menu Iniciar')) {
        Remove-Item -LiteralPath $programsDirectory -Force
    }
}

if ($RemoveLocalData -and (Test-Path -LiteralPath $localDataDirectory)) {
    if ($PSCmdlet.ShouldProcess($localDataDirectory, 'Apagar pareamento e histórico local do agente')) {
        Remove-Item -LiteralPath $localDataDirectory -Recurse -Force
    }
} elseif (Test-Path -LiteralPath $localDataDirectory) {
    Write-Host 'Os dados locais foram preservados. Use -RemoveLocalData somente se deseja apagar pareamento e histórico.'
}

Write-Host 'Agente desinstalado.'
