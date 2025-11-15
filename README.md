# Payment API

Enterprise-grade secure payment API system built with PHP 8.2, Symfony 7.3, MySQL, and Redis.

## 📚 Documentation

All documentation has been organized in the `docs/` folder:

- **[docs/README.md](docs/README.md)** - Complete project overview, features, and architecture
- **[docs/SETUP.md](docs/SETUP.md)** - Detailed setup instructions and configuration
- **[docs/API_EXAMPLES.md](docs/API_EXAMPLES.md)** - API reference with request/response examples

## 🚀 Quick Start

```bash
# 1. Clone repository
git clone https://github.com/your-username/payment-api.git
cd payment-api

# 2. Setup environment
cd application
cp .env.example .env

# 3. Generate JWT keys (Windows)
.\generate-jwt-keys.ps1

# 3. Generate JWT keys (Linux/Mac)
mkdir -p config/jwt
openssl genpkey -algorithm RSA -out config/jwt/private.pem -pkeyopt rsa_keygen_bits:4096
openssl rsa -pubout -in config/jwt/private.pem -out config/jwt/public.pem

# 4. Install dependencies
composer install --ignore-platform-reqs

# 5. Start services
cd ..
docker-compose up -d

# 6. Run migrations
docker exec -it php-application php bin/console doctrine:migrations:migrate --no-interaction

# 7. Verify installation
curl http://localhost:7000/health
```

**Access the API:** http://localhost:7000

For complete setup instructions, see **[docs/SETUP.md](docs/SETUP.md)**.

## 📡 API Endpoints

### Authentication
- `POST /api/auth/register` - Register new user
- `POST /api/auth/login` - Login and get JWT token
- `GET /api/auth/me` - Get current user profile

### Accounts
- `POST /api/accounts` - Create account
- `GET /api/accounts` - List user's accounts
- `GET /api/accounts/{accountNumber}` - Get account details
- `GET /api/accounts/{accountNumber}/balance` - Get balance

### Transactions
- `POST /api/transactions/transfer` - Transfer funds (async)
- `GET /api/transactions/{referenceNumber}` - Get transaction
- `GET /api/transactions/account/{accountNumber}` - List transactions
- `GET /api/transactions/account/{accountNumber}/statistics` - Get stats

### Health
- `GET /health` - Health check (database + Redis)
- `GET /health/live` - Liveness probe
- `GET /health/ready` - Readiness probe

For complete API documentation with examples, see **[docs/API_EXAMPLES.md](docs/API_EXAMPLES.md)**.

## ✨ Key Features

- ✅ Secure fund transfers with ACID compliance
- ✅ JWT authentication (RS256, 4096-bit keys)
- ✅ Async processing with Redis message queues
- ✅ Workflow state machine for transactions
- ✅ Event-driven architecture
- ✅ High-load capability with caching
- ✅ Comprehensive testing (unit + integration)
- ✅ Docker containerization

## 🏗️ Technology Stack

| Component | Technology |
|-----------|-----------|
| Language | PHP 8.2 |
| Framework | Symfony 7.3 |
| Database | MySQL 8.0 |
| Cache/Queue | Redis |
| ORM | Doctrine |
| Auth | LexikJWT |
| Container | Docker + Compose |

## 🧪 Testing

```bash
# Run all tests
docker exec -it php-application vendor/bin/phpunit

# Run specific test suite
docker exec -it php-application vendor/bin/phpunit tests/Service
```

## 📦 Postman Collection

Import **[postman_collection.json](postman_collection.json)** into Postman for pre-configured API tests with all 14 endpoints.
