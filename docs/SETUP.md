# Setup Guide - Payment API

## Quick Setup

### 1. Clone Repository (if needed)

```bash
git clone https://github.com/your-username/payment-api.git
cd payment-api
```

### 2. Environment Configuration

#### Copy Environment File

```bash
# Navigate to application directory
cd application

# Windows PowerShell
Copy-Item .env.example .env

# Linux/Mac
cp .env.example .env
```

#### Update `.env` File

Edit `application/.env` with your configuration:

```env
###> symfony/framework-bundle ###
APP_ENV=dev
APP_DEBUG=1
APP_SECRET=your-secret-key-change-in-production
###< symfony/framework-bundle ###

###> doctrine/doctrine-bundle ###
# For Docker (default)
DATABASE_URL="mysql://admin:admin@123@host.docker.internal:3306/payment-api?serverVersion=8.0&charset=utf8mb4"

# For local MySQL
# DATABASE_URL="mysql://root:password@127.0.0.1:3306/payment_api?serverVersion=8.0&charset=utf8mb4"
###< doctrine/doctrine-bundle ###

###> symfony/messenger ###
MESSENGER_TRANSPORT_DSN=redis://redis:6379/messages
###< symfony/messenger ###

###> Redis Configuration ###
REDIS_URL=redis://redis:6379
###< Redis Configuration ###

###> lexik/jwt-authentication-bundle ###
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
JWT_PASSPHRASE=your-strong-passphrase-change-in-production
###< lexik/jwt-authentication-bundle ###
```

**Important Environment Variables:**

| Variable | Description | Default |
|----------|-------------|---------|
| `APP_ENV` | Environment (dev/prod) | `dev` |
| `APP_SECRET` | Application secret key | Random string |
| `DATABASE_URL` | MySQL connection string | See `.env.example` |
| `REDIS_URL` | Redis connection string | `redis://redis:6379` |
| `MESSENGER_TRANSPORT_DSN` | Message queue transport | `redis://redis:6379/messages` |
| `JWT_PASSPHRASE` | JWT encryption passphrase | Change in production |

### 3. Generate JWT Keys

#### Option A: Using PowerShell Script (Windows Recommended)

```powershell
cd application
.\generate-jwt-keys.ps1
```

This script uses .NET cryptography to generate RSA 4096-bit keys.

#### Option B: Using OpenSSL (Linux/Mac/Git Bash)

```bash
cd application

# Create JWT directory
mkdir -p config/jwt

# Generate private key (RSA 4096-bit)
openssl genpkey -algorithm RSA -out config/jwt/private.pem -pkeyopt rsa_keygen_bits:4096

# Generate public key
openssl rsa -pubout -in config/jwt/private.pem -out config/jwt/public.pem

# Set permissions (Linux/Mac only)
chmod 600 config/jwt/private.pem
chmod 644 config/jwt/public.pem
```

#### Verify Keys Generated

```bash
# Check files exist
ls -la config/jwt/

# Should show:
# private.pem (3000+ bytes)
# public.pem  (800+ bytes)
```

### 4. Install PHP Dependencies

```bash
# Inside application directory
composer install --ignore-platform-reqs
```

**Note**: `--ignore-platform-reqs` is used because some extensions (like redis) will be available in Docker containers.

### 5. Start Docker Services

```bash
# Return to project root
cd ..

# Start all services
docker-compose up -d
```

**Services Started:**
- `php-application`: PHP 8.2 with Apache (Port 7000)
- `redis`: Redis cache and message queue (Port 6379)
- `messenger-worker`: Background transaction processor

**Verify Services:**

```bash
docker-compose ps

# Expected output:
# NAME                STATUS              PORTS
# php-application     Up                  0.0.0.0:7000->80/tcp
# redis               Up                  0.0.0.0:6379->6379/tcp
# messenger-worker    Up                  
```

### 6. Create Database Schema

```bash
# Run migrations inside Docker container
docker exec -it php-application php bin/console doctrine:migrations:migrate --no-interaction

# Verify schema
docker exec -it php-application php bin/console doctrine:schema:validate
```

**Expected Output:**
```
[OK] The database schema is in sync with the mapping files.
```

**Tables Created:**
- `users` - User accounts with authentication
- `accounts` - Bank accounts with balances
- `transactions` - Fund transfer records
- `messenger_messages` - Message queue table

### 6.1. Connect to MySQL with MySQL Workbench

The Docker Compose setup includes a MySQL 8.0 container with persistent data storage.

**MySQL Connection Details:**

| Setting | Value |
|---------|-------|
| **Hostname** | `localhost` or `127.0.0.1` |
| **Port** | `3306` |
| **Username** | `payment_user` |
| **Password** | `payment_pass` |
| **Database** | `payment-api` |

**Alternative Root Access:**
- **Username:** `root`
- **Password:** `root123`

**MySQL Workbench Setup Steps:**

1. Open MySQL Workbench
2. Click the **+** button next to "MySQL Connections"
3. Fill in connection details:
   ```
   Connection Name: Payment API MySQL
   Hostname: 127.0.0.1
   Port: 3306
   Username: payment_user
   ```
4. Click **Store in Vault** and enter password: `payment_pass`
5. Click **Test Connection** to verify
6. Click **OK** to save

**Data Persistence:**

Data is stored in a Docker volume named `mysql-data`. Your data will persist even when you:
- Stop containers: `docker-compose stop`
- Remove containers: `docker-compose down`
- Restart your computer

**To completely remove database (including data):**
```bash
docker-compose down -v  # The -v flag removes volumes
```

### 7. Verify Installation

#### Check Application Health

```bash
# Health check endpoint
curl http://localhost:7000/health

# Expected response:
# {"status":"healthy","timestamp":"2025-11-15T...","checks":{"database":"ok","redis":"ok"}}
```

#### Check Redis Connection

```bash
docker exec -it php-application php bin/console messenger:stats

# Should show queue statistics:
#  ------------------------ ------- 
#   Transport                Count
#  ------------------------ -------
#   async                    0
#   transaction_processing   0
#   failed                   0
#  ------------------------ -------
```

### 8. Test Basic API Functionality

```bash
# Register a test user
curl -X POST http://localhost:7000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "Test123!",
    "firstName": "Test",
    "lastName": "User"
  }'

# Login to get JWT token
curl -X POST http://localhost:7000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "Test123!"
  }'

# Save the token from the response
```

### Dockerfile (PHP Application)

Key features in our Dockerfile:
- PHP 8.2.28 with Apache
- PHP extensions: `pdo_mysql`, `bcmath`, `opcache`, `pcntl`, `redis`, `apcu`
- MySQL 8 for database interactions
- Composer for dependency management
- Node.js and Yarn for asset management

## Database Configuration

### Connection from Docker to Host MySQL

Update `application/.env`:
```env
DATABASE_URL="mysql://payment_user:payment_pass@mysql:3306/payment-api?serverVersion=8.0&charset=utf8mb4"
```

## Common Commands

### Docker Management

```bash
# Start services
docker-compose up -d

# Stop services
docker-compose down

# Restart services
docker-compose restart

# View logs (all services)
docker-compose logs -f

# View logs (specific service)
docker-compose logs -f php-application
docker-compose logs -f messenger-worker

# Rebuild containers
docker-compose up -d --build

# Scale messenger workers
docker-compose up -d --scale messenger-worker=3

# Remove all containers and volumes (⚠️ deletes data)
docker-compose down -v
```

### Application Commands

```bash
# Execute commands inside PHP container
docker exec -it php-application bash

# Clear Symfony cache
docker exec -it php-application php bin/console cache:clear

# Warm up cache
docker exec -it php-application php bin/console cache:warmup

# Create database
docker exec -it php-application php bin/console doctrine:database:create

# Run migrations
docker exec -it php-application php bin/console doctrine:migrations:migrate

# Generate migration
docker exec -it php-application php bin/console make:migration

# Validate schema
docker exec -it php-application php bin/console doctrine:schema:validate

# Check messenger stats
docker exec -it php-application php bin/console messenger:stats

# Consume messages (manual)
docker exec -it php-application php bin/console messenger:consume transaction_processing -vv
```

### Testing Commands

```bash
# Run all tests
docker exec -it php-application vendor/bin/phpunit

# Run unit tests only
docker exec -it php-application vendor/bin/phpunit tests/Service

# Run integration tests
docker exec -it php-application vendor/bin/phpunit tests/Integration

# Run specific test file
docker exec -it php-application vendor/bin/phpunit tests/Service/FundTransferServiceTest.php

# Run with code coverage (HTML report)
docker exec -it php-application vendor/bin/phpunit --coverage-html coverage

# View coverage report (after generating HTML coverage)
# symlink coverage to public directory
docker exec -it php-application ln -s /var/www/html/coverage /var/www/html/public/coverage

# Open application/coverage/index.html in your browser
```

**Note**: Code coverage reports are generated in `application/coverage/` directory.

## Troubleshooting

### Port 7000 Already in Use

**Error**: `Bind for 0.0.0.0:7000 failed: port is already allocated`

**Solution**: Change port in `docker-compose.yml`:
```yaml
php-application:
  ports:
    - "8000:80"  # Changed from 7000
```

### Cache Corruption

**Error**: `Failed to open stream: No such file or directory` in cache

**Solution**:
```bash
# Clear cache completely
docker exec -it php-application rm -rf var/cache/*
docker exec -it php-application php bin/console cache:warmup
```

### Messenger Worker Not Processing

**Check worker is running**:
```bash
docker ps | grep messenger-worker

# View worker logs
docker logs messenger-worker -f

# Restart worker
docker-compose restart messenger-worker
```

## Production Deployment

### Environment Configuration

Update `application/.env` for production:

```env
APP_ENV=prod
APP_DEBUG=0
APP_SECRET=<generate-strong-random-string>
DATABASE_URL=<production-database-url>
REDIS_URL=<production-redis-url>
JWT_PASSPHRASE=<strong-production-passphrase>
```

### Performance Optimization

```bash
# Optimize Composer autoloader
composer install --no-dev --optimize-autoloader --classmap-authoritative

# Warm up production cache
php bin/console cache:warmup --env=prod

# Clear dev cache
php bin/console cache:clear --env=prod
```

### Process Management (Supervisor)

Example Supervisor configuration for messenger workers:

```ini
[program:messenger-worker]
command=php /var/www/html/bin/console messenger:consume transaction_processing --time-limit=3600
user=www-data
numprocs=3
startsecs=0
autostart=true
autorestart=true
process_name=%(program_name)s_%(process_num)02d
```

## Additional Resources

- [API Examples](./API_EXAMPLES.md) - Complete API usage guide with examples
- [README](../README.md) - Project overview and documentation index
- [Postman Collection](../postman_collection.json) - Import into Postman for testing
