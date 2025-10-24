param(
    [string]$BaseUrl = "http://localhost:8080"
)

$healthUrl = "$BaseUrl/api/health"
Write-Host "[Health] Consultando $healthUrl" -ForegroundColor Cyan

try {
    $resp = Invoke-WebRequest -Uri $healthUrl -UseBasicParsing -Headers @{ 'Accept' = 'application/json' }
    $status = $resp.StatusCode
    $json = $resp.Content | ConvertFrom-Json
    Write-Host "Status HTTP: $status"
    if ($json) {
        Write-Host "success: $($json.success)"
        if ($json.data) {
            Write-Host "status: $($json.data.status)"
            Write-Host "php_version: $($json.data.php_version)"
            Write-Host "sqlite_enabled: $($json.data.sqlite_enabled)"
            Write-Host "time: $($json.data.time)"
        }
    } else {
        Write-Host "Resposta não é JSON:"
        Write-Host $resp.Content
    }
    if ($status -eq 200) {
        Write-Host "Health OK" -ForegroundColor Green
        exit 0
    } else {
        Write-Host "Health com status $status" -ForegroundColor Yellow
        exit 1
    }
} catch {
    Write-Host "Falha ao consultar saúde: $_" -ForegroundColor Red
    exit 1
}