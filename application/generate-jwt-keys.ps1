# Generate RSA key pair for JWT authentication using .NET
$scriptPath = Split-Path -Parent $MyInvocation.MyCommand.Path
$keyDir = Join-Path $scriptPath "config\jwt"

# Create directory if it doesn't exist
if (!(Test-Path $keyDir)) {
    New-Item -ItemType Directory -Path $keyDir -Force | Out-Null
}

Write-Host "Generating RSA key pair for JWT authentication..." -ForegroundColor Green

# Generate RSA key pair using .NET
Add-Type -AssemblyName System.Security

# Create RSA provider with 4096-bit key
$rsa = [System.Security.Cryptography.RSA]::Create(4096)

# Export private key in PEM format
$privateKeyPem = "-----BEGIN PRIVATE KEY-----`n"
$privateKeyBytes = $rsa.ExportPkcs8PrivateKey()
$privateKeyBase64 = [Convert]::ToBase64String($privateKeyBytes, [System.Base64FormattingOptions]::InsertLineBreaks)
$privateKeyPem += $privateKeyBase64
$privateKeyPem += "`n-----END PRIVATE KEY-----`n"

# Export public key in PEM format
$publicKeyPem = "-----BEGIN PUBLIC KEY-----`n"
$publicKeyBytes = $rsa.ExportSubjectPublicKeyInfo()
$publicKeyBase64 = [Convert]::ToBase64String($publicKeyBytes, [System.Base64FormattingOptions]::InsertLineBreaks)
$publicKeyPem += $publicKeyBase64
$publicKeyPem += "`n-----END PUBLIC KEY-----`n"

# Save keys to files
$privateKeyPath = Join-Path $keyDir "private.pem"
$publicKeyPath = Join-Path $keyDir "public.pem"

[System.IO.File]::WriteAllText($privateKeyPath, $privateKeyPem)
[System.IO.File]::WriteAllText($publicKeyPath, $publicKeyPem)

Write-Host "✓ Private key saved to: $privateKeyPath" -ForegroundColor Green
Write-Host "✓ Public key saved to: $publicKeyPath" -ForegroundColor Green
Write-Host "`nJWT keys generated successfully!" -ForegroundColor Cyan
Write-Host "`nNext steps:" -ForegroundColor Yellow
Write-Host "1. Update your .env file with:" -ForegroundColor White
Write-Host "   JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem"
Write-Host "   JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem"
Write-Host "   JWT_PASSPHRASE=your-secret-passphrase"
Write-Host "2. Run: docker-compose up -d"
Write-Host "3. Run: docker exec -it php-application bin/console doctrine:migrations:migrate"
