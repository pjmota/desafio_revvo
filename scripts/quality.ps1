param(
    [string]$BaseUrl = "http://localhost:8080"
)

Write-Host "[Qualidade] Verificando ambiente e execução básica..." -ForegroundColor Cyan

# PHP versão e módulos principais
Write-Host "\n[INFO] php -v"
php -v
Write-Host "\n[INFO] php -m (checar sqlite)"
php -m | Select-String -Pattern "sqlite|pdo_sqlite"

# Lint em todos os arquivos PHP
Write-Host "\n[Lint] php -l em todos os arquivos .php" -ForegroundColor Cyan
$root = Resolve-Path "$PSScriptRoot/.."
$phpFiles = Get-ChildItem -Path $root -Recurse -Filter '*.php'
$lintFails = 0
foreach ($f in $phpFiles) {
    $out = & php -l $f.FullName
    if ($LASTEXITCODE -ne 0) { $lintFails++ }
    Write-Host $out
}
Write-Host "[Lint] Concluído. Falhas: $lintFails"

# Rodar smoke tests
Write-Host "\n[Tests] Executando tests/api_smoke.php em $BaseUrl" -ForegroundColor Cyan
$env:BASE_URL = $BaseUrl
& php "$root/tests/api_smoke.php"
$smokeExit = $LASTEXITCODE
Write-Host "[Tests] Exit code: $smokeExit"

# Checagem PSR-12 (PHP-CS-Fixer dry-run)
Write-Host "\n[Style] Checando PSR-12 com PHP-CS-Fixer (dry-run)" -ForegroundColor Cyan
$csFixerBat = Join-Path $root 'vendor\bin\php-cs-fixer.bat'
$psr12Issues = -1
if (Test-Path $csFixerBat) {
    $targets = @('app','inc','admin','public','router.php','tests')
    $targetPaths = @()
    foreach ($t in $targets) { $targetPaths += (Join-Path $root $t) }
    $csOutput = & "$csFixerBat" fix $targetPaths --dry-run --diff --verbose --ansi --rules=@PSR12 --using-cache=no --no-interaction 2>&1
    $psr12Issues = ($csOutput | Select-String -Pattern 'would be fixed' -SimpleMatch).Count
    $csExit = $LASTEXITCODE
    $label = if ($psr12Issues -gt 0) { 'ATENÇÃO' } else { 'OK' }
    Write-Host "[Style] Saída (exit=$csExit, issues=$psr12Issues): $label"
    $csOutput | ForEach-Object { Write-Host $_ }
} else {
    Write-Host "PHP-CS-Fixer não encontrado em $csFixerBat. Rode 'php composer.phar require --dev friendsofphp/php-cs-fixer'" -ForegroundColor Yellow
    $psr12Issues = -1
}

# Mostrar últimos logs
Write-Host "\n[Logs] Últimas 20 linhas de data/logs/app.log" -ForegroundColor Cyan
$logPath = Join-Path $root 'data/logs/app.log'
if (Test-Path $logPath) {
    Get-Content -Path $logPath -Tail 20 | ForEach-Object { Write-Host $_ }
} else {
    Write-Host "Sem arquivo de log em $logPath"
}

# Resumo
Write-Host "\n[Resumo]" -ForegroundColor Cyan
Write-Host "Lint falhas: $lintFails"
Write-Host "Smoke tests exit: $smokeExit"
Write-Host "PSR-12 issues: $psr12Issues"
if ($lintFails -eq 0 -and $smokeExit -eq 0 -and ($psr12Issues -le 0)) {
    Write-Host "Status geral: OK" -ForegroundColor Green
} else {
    Write-Host "Status geral: ATENÇÃO" -ForegroundColor Yellow
}