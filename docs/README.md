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

## ✨ Key Features

### Core Functionality
✅ User registration and JWT authentication  
✅ Multi-account management per user  
✅ Secure fund transfers with validation  
✅ Transaction history and audit trail  
✅ Real-time balance tracking  
✅ Transaction statistics and reporting  

### Enterprise Features
✅ Async transaction processing (Redis queues)  
✅ Optimistic + Pessimistic database locking  
✅ Redis caching for performance  
✅ Workflow state machine (pending → processing → completed/failed)  
✅ Event-driven architecture with listeners  
✅ Automatic retry mechanism with exponential backoff  
✅ Comprehensive error handling and logging  
✅ Health check endpoints for monitoring  

## 🏗️ Technology Stack

| Component | Technology | Purpose |
|-----------|-----------|---------|
| **Language** | PHP 8.2 | Modern PHP with strict typing, attributes, readonly properties |
| **Framework** | Symfony 7.3 | Full-stack framework with DI, routing, security |
| **Database** | MySQL 8.0 | Primary data storage with ACID transactions |
| **Cache/Queue** | Redis | Caching layer + message queue transport |
| **ORM** | Doctrine | Entity management, migrations, locking |
| **Auth** | LexikJWT | JWT token generation and validation |
| **Workflow** | Symfony Workflow | Transaction state machine |
| **Messaging** | Symfony Messenger | Async message handling |
| **Testing** | PHPUnit | Unit and integration testing |
| **Containerization** | Docker + Docker Compose | Multi-service orchestration |

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

## 🚀 Quick Start

### Prerequisites
- Docker Desktop (Windows/Mac) or Docker Engine + Docker Compose (Linux)
- Git (optional)

### Installation

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

# 5. Start Docker services
cd ..
docker-compose up -d

# 6. Run database migrations
docker exec -it php-application php bin/console doctrine:migrations:migrate --no-interaction

# 7. Verify installation
curl http://localhost:7000/health
```

**Access the API**: http://localhost:7000

For detailed setup instructions, see **[Setup Guide](./SETUP.md)**.

## 📡 API Endpoints

### Authentication
- `POST /api/auth/register` - Register new user
- `POST /api/auth/login` - Login and get JWT token
- `GET /api/auth/me` - Get current user profile

### Accounts
- `POST /api/accounts` - Create new account
- `GET /api/accounts` - List user's accounts
- `GET /api/accounts/{accountNumber}` - Get account details
- `GET /api/accounts/{accountNumber}/balance` - Get account balance

### Transactions
- `POST /api/transactions/transfer` - Transfer funds
- `GET /api/transactions/{referenceNumber}` - Get transaction details
- `GET /api/transactions/account/{accountNumber}` - List account transactions
- `GET /api/transactions/account/{accountNumber}/statistics` - Get transaction stats

### Health & Monitoring
- `GET /health` - Overall health status
- `GET /health/live` - Liveness probe
- `GET /health/ready` - Readiness probe

For complete API documentation with examples, see **[docs/API_EXAMPLES.md](docs/API_EXAMPLES.md)**.

## 🧪 Testing

```bash
# Run all tests
docker exec -it php-application vendor/bin/phpunit

# Run unit tests
docker exec -it php-application vendor/bin/phpunit tests/Service

# Run integration tests
docker exec -it php-application vendor/bin/phpunit tests/Integration
```

**Test Coverage:**
- Unit tests for `FundTransferService`
- Integration tests for complete API flows
- Edge cases and error scenarios

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
└──────────┘    └────────┘
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

## 📊 Monitoring

### Health Checks
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

## 📚 Documentation

| Document | Description |
|----------|-------------|
| **[SETUP.md](./SETUP.md)** | Complete setup guide with all commands and configuration |
| **[API_EXAMPLES.md](./API_EXAMPLES.md)** | API usage examples with request/response samples |
| **[postman_collection.json](../postman_collection.json)** | Postman collection for API testing |

## 🔧 Configuration

### Environment Variables

Key settings in `application/.env`:

```env
# Application
APP_ENV=dev                    # dev, prod
APP_DEBUG=1                    # 0 in production

# Database
DATABASE_URL=mysql://admin:admin@123@host.docker.internal:3306/payment-api

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

### Optimization

```bash
# Optimize Composer autoloader
composer install --no-dev --optimize-autoloader

# Warm up cache
php bin/console cache:warmup --env=prod

# Enable OPcache (already in Dockerfile)
```
