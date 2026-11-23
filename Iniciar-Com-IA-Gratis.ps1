$ErrorActionPreference = 'Stop'
$php = if (Test-Path 'C:\xampp\php\php.exe') { 'C:\xampp\php\php.exe' } else { (Get-Command php).Source }
$previousKey = $env:OPENROUTER_API_KEY
try {
    if (-not $previousKey) {
        $secret = Read-Host 'Chave da API OpenRouter (entrada oculta)' -AsSecureString
        $pointer = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($secret)
        try { $env:OPENROUTER_API_KEY = [Runtime.InteropServices.Marshal]::PtrToStringBSTR($pointer) }
        finally { [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($pointer) }
    }
    if (-not $env:OPENROUTER_API_KEY) { throw 'Informe uma chave para iniciar o tutor gratuito.' }
    & $php -S 127.0.0.1:8000 -t (Join-Path $PSScriptRoot 'public')
} finally { $env:OPENROUTER_API_KEY = $previousKey }

