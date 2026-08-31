$ErrorActionPreference = 'Stop'
Set-Location (Join-Path $PSScriptRoot '../..')
$Version = (Get-Content .playwright-version -Raw).Trim()
npm install --no-save --package-lock=false "@playwright/test@$Version"
npx playwright install --with-deps
