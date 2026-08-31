$ErrorActionPreference = 'Stop'
Set-Location (Join-Path $PSScriptRoot '../..')
if (-not (Test-Path 'node_modules/@playwright/test')) {
    throw 'Playwright belum terpasang. Jalankan .\\scripts\\e2e\\install-playwright.ps1 terlebih dahulu.'
}
npx playwright test @args
