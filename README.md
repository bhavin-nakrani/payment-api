# Payment API

Enterprise-grade secure payment API system built with PHP 8.2, Symfony 7.3, MySQL, and Redis for high-load transaction processing.

## 🎯 Overview

A production-ready payment API that demonstrates modern PHP development practices with:
- **Secure Fund Transfers** between accounts with ACID compliance
- **JWT Authentication** for secure API access
- **Async Processing** with Redis message queues
- **Workflow State Machine** for transaction lifecycle management
- **High-Load Capability** with caching, locking mechanisms, and horizontal scalability
- **Comprehensive Testing** (unit + integration tests)
- **Event-Driven Architecture** with message handlers and workflow listeners

## 📚 Documentation

- **[docs/SETUP.md](docs/SETUP.md)** - Detailed setup instructions and configuration
- **[docs/API_EXAMPLES.md](docs/API_EXAMPLES.md)** - API reference with request/response examples
- **[postman_collection.json](postman_collection.json)** - Postman collection for API testing

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

For detailed setup instructions, see **[docs/SETUP.md](docs/SETUP.md)**.

## 📦 Project Structure

```
payment-api/
├── application/               # Symfony application
│   ├── src/
│   │   ├── Controller/       # API endpoints (4 controllers)
│   │   │   ├── AuthController.php
│   │   │   ├── AccountController.php
│   │   │   ├── TransactionController.php
│   │   │   └── HealthController.php
│   │   ├── Entity/           # Database models (3 entities)
│   │   │   ├── User.php
│   │   │   ├── Account.php
│   │   │   └── Transaction.php
│   │   ├── Repository/       # Data access layer
│   │   ├── Service/          # Business logic
│   │   │   ├── FundTransferService.php
│   │   │   └── CacheService.php
│   │   ├── DTO/              # Request validation
│   │   ├── Message/          # Queue messages & events
│   │   ├── MessageHandler/   # Async handlers
│   │   └── EventListener/    # Workflow listeners
│   ├── config/               # Symfony configuration
│   ├── migrations/           # Database migrations
│   ├── tests/                # Unit & integration tests
│   └── templates/            # Twig templates
├── docs/                     # Documentation
│   ├── SETUP.md             # Setup guide
│   └── API_EXAMPLES.md      # API usage examples
├── docker-compose.yml        # Docker orchestration
├── Dockerfile                # PHP application container
├── postman_collection.json   # Postman API tests
└── README.md                # This file
```

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

## 🏛️ Architecture Highlights

### Workflow State Machine
Transactions follow a defined lifecycle:
```
┌─────────┐
│ pending │
└────┬────┘
     │ process
     ▼
┌────────────┐
│ processing │
└─────┬──────┘
      │ complete     │ fail
      ▼              ▼
┌──────────┐    ┌────────┐
│completed │    │ failed │
└────┬─────┘    └────────┘
     │ reverse
     ▼
┌──────────┐
│ reversed │
└──────────┘
```

### Event-Driven Architecture
```
Transfer Request
      │
      ▼
ProcessTransactionMessage ──► Redis Queue
      │
      ▼
ProcessTransactionMessageHandler
      │
      ├──► TransactionCompletedEvent (on success)
      └──► TransactionFailedEvent (on failure)
```

### Database Design
- **users**: Authentication with UUID primary keys
- **accounts**: Balance tracking with version field (optimistic locking)
- **transactions**: Complete audit trail with status tracking
- **messenger_messages**: Message queue persistence

### Locking Strategy
- **Optimistic Locking**: Version field on accounts prevents race conditions
- **Pessimistic Locking**: `PESSIMISTIC_WRITE` during transaction processing

### Caching Strategy
- Account data cached for 300 seconds
- User data cached for 600 seconds
- Cache-aside pattern with Redis

## 🔒 Security Features

- ✅ **JWT Authentication** (RS256, 4096-bit keys)
- ✅ **Password Hashing** (Bcrypt/Argon2)
- ✅ **Input Validation** (Symfony Validator)
- ✅ **SQL Injection Prevention** (Doctrine parameterized queries)
- ✅ **Access Control** (Users can only access their own data)
- ✅ **CSRF Protection** (Symfony Security)
- ✅ **Rate Limiting Ready** (Add Symfony Rate Limiter)

## ⚡ Performance Features

- **Async Processing**: Redis-backed message queues
- **Database Indexing**: Strategic indexes on frequently queried columns
- **Connection Pooling**: Doctrine connection management
- **Caching Layer**: Redis for frequently accessed data
- **Horizontal Scalability**: Stateless API design
- **Retry Mechanism**: 5 retries with exponential backoff

## 📊 Monitoring & Health Checks

### Health Endpoints
- `/health` - Database + Redis connectivity
- `/health/live` - Kubernetes liveness probe
- `/health/ready` - Kubernetes readiness probe

### Logging
Logs in `application/var/log/`:
- Transaction lifecycle events
- Failed transactions with reasons
- System errors and exceptions

## 🐳 Docker Services

| Service | Container | Port | Purpose |
|---------|-----------|------|---------|
| **php-application** | php:8.2-apache | 7000 | API server |
| **mysql** | mysql:8.0 | 3306 | Database |
| **redis** | redis:alpine | 6379 | Cache + Queue |
| **messenger-worker** | php:8.2-cli | - | Background processor |

```bash
# View running services
docker-compose ps

# View logs
docker-compose logs -f

# Scale workers for high load
docker-compose up -d --scale messenger-worker=5

# Restart services
docker-compose restart
```

## 🔧 Configuration

### Environment Variables

Key settings in `application/.env`:

```env
# Application
APP_ENV=dev                    # dev, prod
APP_DEBUG=1                    # 0 in production

# Database
DATABASE_URL=mysql://payment_user:payment_pass@mysql:3306/payment-api

# Redis
REDIS_URL=redis://redis:6379
MESSENGER_TRANSPORT_DSN=redis://redis:6379/messages

# JWT
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
JWT_PASSPHRASE=your-strong-passphrase
```

### Retry Strategy

Configure in `config/packages/messenger.yaml`:

```yaml
retry_strategy:
    max_retries: 5          # Maximum retry attempts
    delay: 2000             # Initial delay (ms)
    multiplier: 3           # Delay multiplier
    max_delay: 30000        # Maximum delay (ms)
```

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

# Run unit tests
docker exec -it php-application vendor/bin/phpunit tests/Service

# Run integration tests
docker exec -it php-application vendor/bin/phpunit tests/Integration

# Run with code coverage (HTML report)
docker exec -it php-application vendor/bin/phpunit --coverage-html coverage

# View coverage report
# Open http://localhost:7000/coverage/index.html in browser
```

**Test Coverage:**
- Unit tests for `FundTransferService`
- Integration tests for complete API flows
- Edge cases and error scenarios
- Code coverage reports with Xdebug

## 📦 Postman Collection

Import **[postman_collection.json](postman_collection.json)** into Postman for pre-configured API tests including:
- Complete authentication flow
- Account creation and management
- Fund transfers with workflow testing
- Transaction monitoring (pending → processing → completed/failed/reversed)
- All API endpoints with test scripts
