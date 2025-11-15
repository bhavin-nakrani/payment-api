# API Examples

Complete API reference with request/response examples for the Payment API.

## 📋 Table of Contents

- [Quick Start](#quick-start)
- [Authentication](#authentication)
- [Account Management](#account-management)
- [Fund Transfers](#fund-transfers)
- [Transaction Queries](#transaction-queries)
- [Health & Monitoring](#health--monitoring)
- [Error Handling](#error-handling)
- [Postman Collection](#postman-collection)
- [Code Examples](#code-examples)

---

## Quick Start

**Base URL:** `http://localhost:7000`

**Authentication:** Most endpoints require JWT Bearer token in `Authorization` header:
```
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
```

**Response Format:** All responses are JSON with consistent structure:
```json
{
  "status": "success|error",
  "data": { ... },
  "message": "Descriptive message"
}
```

---

## Authentication

### 1. Register New User

**Endpoint:** `POST /api/auth/register`  
**Authentication:** None required  
**Description:** Create a new user account

**Request Body:**
```json
{
  "email": "john.doe@example.com",
  "password": "SecurePass123!",
  "name": "John Doe"
}
```

**Validation Rules:**
- `email`: Valid email format, unique
- `password`: Minimum 8 characters
- `name`: Not blank

**Response (201 Created):**
```json
{
  "status": "success",
  "data": {
    "id": "550e8400-e29b-41d4-a716-446655440000",
    "email": "john.doe@example.com",
    "name": "John Doe",
    "createdAt": "2024-01-15T10:30:00+00:00"
  },
  "message": "User registered successfully"
}
```

**cURL Example:**
```bash
curl -X POST http://localhost:7000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "email": "john.doe@example.com",
    "password": "SecurePass123!",
    "name": "John Doe"
  }'
```

**Error Response (400 Bad Request):**
```json
{
  "status": "error",
  "message": "Validation failed",
  "errors": {
    "email": ["This email is already registered"],
    "password": ["Password must be at least 8 characters"]
  }
}
```

---

### 2. Login

**Endpoint:** `POST /api/auth/login`  
**Authentication:** None required  
**Description:** Authenticate user and receive JWT token

**Request Body:**
```json
{
  "email": "john.doe@example.com",
  "password": "SecurePass123!"
}
```

**Response (200 OK):**
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJpYXQiOjE3MDUzMTg2MDAsImV4cCI6MTcwNTMyMjIwMCwidXNlcm5hbWUiOiJqb2huLmRvZUBleGFtcGxlLmNvbSJ9.a8dH3jF...",
  "user": {
    "id": "550e8400-e29b-41d4-a716-446655440000",
    "email": "john.doe@example.com",
    "name": "John Doe"
  }
}
```

**Token Details:**
- Algorithm: RS256 (RSA with SHA-256)
- Expiration: 1 hour from issuance
- Contains: User identifier and email

**cURL Example:**
```bash
curl -X POST http://localhost:7000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "john.doe@example.com",
    "password": "SecurePass123!"
  }'
```

**Error Response (401 Unauthorized):**
```json
{
  "status": "error",
  "message": "Invalid credentials"
}
```

---

### 3. Get Current User Profile

**Endpoint:** `GET /api/auth/me`  
**Authentication:** Required (Bearer token)  
**Description:** Get authenticated user's profile

**Request Headers:**
```
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
```

**Response (200 OK):**
```json
{
  "id": "550e8400-e29b-41d4-a716-446655440000",
  "email": "john.doe@example.com",
  "name": "John Doe",
  "createdAt": "2024-01-15T10:30:00+00:00"
}
```

**cURL Example:**
```bash
TOKEN="eyJ0eXAiOiJKV1QiLCJhbGc..."

curl -X GET http://localhost:7000/api/auth/me \
  -H "Authorization: Bearer $TOKEN"
```

---

## Account Management

### 4. Create Account

**Endpoint:** `POST /api/accounts`  
**Authentication:** Required (Bearer token)  
**Description:** Create a new account for authenticated user

**Request Body:**
```json
{
  "accountName": "Personal Savings",
  "initialBalance": 1000.00,
  "currency": "USD"
}
```

**Validation Rules:**
- `accountName`: Not blank, max 255 characters
- `initialBalance`: Positive decimal with 2 decimal places
- `currency`: 3-character ISO code (USD, EUR, GBP, etc.)

**Response (201 Created):**
```json
{
  "status": "success",
  "data": {
    "id": "660e8400-e29b-41d4-a716-446655440001",
    "accountNumber": "ACC-2024-0001-ABCD",
    "accountName": "Personal Savings",
    "balance": 1000.00,
    "currency": "USD",
    "status": "active",
    "createdAt": "2024-01-15T11:00:00+00:00"
  },
  "message": "Account created successfully"
}
```

**cURL Example:**
```bash
curl -X POST http://localhost:7000/api/accounts \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "accountName": "Personal Savings",
    "initialBalance": 1000.00,
    "currency": "USD"
  }'
```

**Error Response (400 Bad Request):**
```json
{
  "status": "error",
  "message": "Validation failed",
  "errors": {
    "initialBalance": ["Initial balance must be positive"],
    "currency": ["Invalid currency code"]
  }
}
```

---

### 5. List User Accounts

**Endpoint:** `GET /api/accounts`  
**Authentication:** Required (Bearer token)  
**Description:** Get all accounts belonging to authenticated user

**Response (200 OK):**
```json
{
  "status": "success",
  "data": [
    {
      "id": "660e8400-e29b-41d4-a716-446655440001",
      "accountNumber": "ACC-2024-0001-ABCD",
      "accountName": "Personal Savings",
      "balance": 1000.00,
      "currency": "USD",
      "status": "active",
      "createdAt": "2024-01-15T11:00:00+00:00"
    },
    {
      "id": "660e8400-e29b-41d4-a716-446655440002",
      "accountNumber": "ACC-2024-0002-EFGH",
      "accountName": "Business Account",
      "balance": 5000.00,
      "currency": "USD",
      "status": "active",
      "createdAt": "2024-01-15T12:30:00+00:00"
    }
  ],
  "message": "Accounts retrieved successfully"
}
```

**cURL Example:**
```bash
curl -X GET http://localhost:7000/api/accounts \
  -H "Authorization: Bearer $TOKEN"
```

---

### 6. Get Account Details

**Endpoint:** `GET /api/accounts/{accountNumber}`  
**Authentication:** Required (Bearer token)  
**Description:** Get specific account details

**Path Parameters:**
- `accountNumber`: Unique account identifier (e.g., `ACC-2024-0001-ABCD`)

**Response (200 OK):**
```json
{
  "status": "success",
  "data": {
    "id": "660e8400-e29b-41d4-a716-446655440001",
    "accountNumber": "ACC-2024-0001-ABCD",
    "accountName": "Personal Savings",
    "balance": 1000.00,
    "currency": "USD",
    "status": "active",
    "createdAt": "2024-01-15T11:00:00+00:00",
    "updatedAt": "2024-01-15T11:00:00+00:00"
  },
  "message": "Account retrieved successfully"
}
```

**cURL Example:**
```bash
curl -X GET http://localhost:7000/api/accounts/ACC-2024-0001-ABCD \
  -H "Authorization: Bearer $TOKEN"
```

**Error Response (404 Not Found):**
```json
{
  "status": "error",
  "message": "Account not found"
}
```

**Error Response (403 Forbidden):**
```json
{
  "status": "error",
  "message": "You do not have access to this account"
}
```

---

### 7. Get Account Balance

**Endpoint:** `GET /api/accounts/{accountNumber}/balance`  
**Authentication:** Required (Bearer token)  
**Description:** Get current account balance (cached for 300 seconds)

**Response (200 OK):**
```json
{
  "status": "success",
  "data": {
    "accountNumber": "ACC-2024-0001-ABCD",
    "balance": 750.00,
    "currency": "USD",
    "lastUpdated": "2024-01-15T14:30:00+00:00"
  },
  "message": "Balance retrieved successfully"
}
```

**cURL Example:**
```bash
curl -X GET http://localhost:7000/api/accounts/ACC-2024-0001-ABCD/balance \
  -H "Authorization: Bearer $TOKEN"
```

---

## Fund Transfers

### 8. Transfer Funds

**Endpoint:** `POST /api/transactions/transfer`  
**Authentication:** Required (Bearer token)  
**Description:** Transfer funds between accounts (async processing)

**Request Body:**
```json
{
  "fromAccountNumber": "ACC-2024-0001-ABCD",
  "toAccountNumber": "ACC-2024-0002-EFGH",
  "amount": 250.00,
  "description": "Payment for services"
}
```

**Validation Rules:**
- `fromAccountNumber`: Valid account number owned by user
- `toAccountNumber`: Valid account number (any user)
- `amount`: Positive decimal with 2 decimal places
- `description`: Optional, max 500 characters
- **Business Rule:** From and To accounts must use same currency
- **Business Rule:** Sufficient balance in from account

**Response (201 Created):**
```json
{
  "status": "success",
  "data": {
    "id": "770e8400-e29b-41d4-a716-446655440001",
    "referenceNumber": "TXN-2024-0001-WXYZ",
    "fromAccountNumber": "ACC-2024-0001-ABCD",
    "toAccountNumber": "ACC-2024-0002-EFGH",
    "amount": 250.00,
    "currency": "USD",
    "status": "pending",
    "description": "Payment for services",
    "createdAt": "2024-01-15T15:00:00+00:00"
  },
  "message": "Transaction initiated successfully"
}
```

**Processing Flow:**
1. Request validated
2. Transaction created with `pending` status
3. Message sent to Redis queue
4. HTTP 201 response returned immediately
5. Background worker processes transaction
6. Status changes to `processing` → `completed` or `failed`

**cURL Example:**
```bash
curl -X POST http://localhost:7000/api/transactions/transfer \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "fromAccountNumber": "ACC-2024-0001-ABCD",
    "toAccountNumber": "ACC-2024-0002-EFGH",
    "amount": 250.00,
    "description": "Payment for services"
  }'
```

**Error Response (400 Bad Request - Insufficient Funds):**
```json
{
  "status": "error",
  "message": "Insufficient funds",
  "details": {
    "available": 100.00,
    "required": 250.00
  }
}
```

**Error Response (400 Bad Request - Currency Mismatch):**
```json
{
  "status": "error",
  "message": "Currency mismatch between accounts",
  "details": {
    "fromCurrency": "USD",
    "toCurrency": "EUR"
  }
}
```

---

## Transaction Queries

### 9. Get Transaction Details

**Endpoint:** `GET /api/transactions/{referenceNumber}`  
**Authentication:** Required (Bearer token)  
**Description:** Get transaction details by reference number

**Path Parameters:**
- `referenceNumber`: Unique transaction identifier (e.g., `TXN-2024-0001-WXYZ`)

**Response (200 OK - Completed):**
```json
{
  "status": "success",
  "data": {
    "id": "770e8400-e29b-41d4-a716-446655440001",
    "referenceNumber": "TXN-2024-0001-WXYZ",
    "fromAccountNumber": "ACC-2024-0001-ABCD",
    "toAccountNumber": "ACC-2024-0002-EFGH",
    "amount": 250.00,
    "currency": "USD",
    "status": "completed",
    "description": "Payment for services",
    "createdAt": "2024-01-15T15:00:00+00:00",
    "completedAt": "2024-01-15T15:00:02+00:00"
  },
  "message": "Transaction retrieved successfully"
}
```

**Response (200 OK - Failed):**
```json
{
  "status": "success",
  "data": {
    "id": "770e8400-e29b-41d4-a716-446655440001",
    "referenceNumber": "TXN-2024-0001-WXYZ",
    "fromAccountNumber": "ACC-2024-0001-ABCD",
    "toAccountNumber": "ACC-2024-0002-EFGH",
    "amount": 250.00,
    "currency": "USD",
    "status": "failed",
    "failureReason": "Insufficient funds",
    "description": "Payment for services",
    "createdAt": "2024-01-15T15:00:00+00:00",
    "failedAt": "2024-01-15T15:00:01+00:00"
  },
  "message": "Transaction retrieved successfully"
}
```

**cURL Example:**
```bash
curl -X GET http://localhost:7000/api/transactions/TXN-2024-0001-WXYZ \
  -H "Authorization: Bearer $TOKEN"
```

**Transaction Status Values:**
- `pending`: Initial state, queued for processing
- `processing`: Being processed by background worker
- `completed`: Successfully completed
- `failed`: Failed (see `failureReason`)

---

### 10. List Account Transactions

**Endpoint:** `GET /api/transactions/account/{accountNumber}`  
**Authentication:** Required (Bearer token)  
**Description:** Get all transactions for specific account

**Query Parameters (optional):**
- `status`: Filter by status (pending, processing, completed, failed)
- `limit`: Number of results (default: 50, max: 100)
- `offset`: Pagination offset (default: 0)

**Response (200 OK):**
```json
{
  "status": "success",
  "data": {
    "accountNumber": "ACC-2024-0001-ABCD",
    "transactions": [
      {
        "id": "770e8400-e29b-41d4-a716-446655440001",
        "referenceNumber": "TXN-2024-0001-WXYZ",
        "type": "debit",
        "amount": 250.00,
        "currency": "USD",
        "status": "completed",
        "description": "Payment for services",
        "counterpartyAccount": "ACC-2024-0002-EFGH",
        "createdAt": "2024-01-15T15:00:00+00:00"
      },
      {
        "id": "770e8400-e29b-41d4-a716-446655440002",
        "referenceNumber": "TXN-2024-0002-QRST",
        "type": "credit",
        "amount": 500.00,
        "currency": "USD",
        "status": "completed",
        "description": "Received payment",
        "counterpartyAccount": "ACC-2024-0003-IJKL",
        "createdAt": "2024-01-15T16:30:00+00:00"
      }
    ],
    "pagination": {
      "total": 25,
      "limit": 50,
      "offset": 0
    }
  },
  "message": "Transactions retrieved successfully"
}
```

**cURL Example:**
```bash
# All transactions
curl -X GET "http://localhost:7000/api/transactions/account/ACC-2024-0001-ABCD" \
  -H "Authorization: Bearer $TOKEN"

# Filter by status
curl -X GET "http://localhost:7000/api/transactions/account/ACC-2024-0001-ABCD?status=completed&limit=10" \
  -H "Authorization: Bearer $TOKEN"
```

---

### 11. Get Transaction Statistics

**Endpoint:** `GET /api/transactions/account/{accountNumber}/statistics`  
**Authentication:** Required (Bearer token)  
**Description:** Get transaction statistics for account

**Query Parameters (optional):**
- `startDate`: Start date (YYYY-MM-DD)
- `endDate`: End date (YYYY-MM-DD)

**Response (200 OK):**
```json
{
  "status": "success",
  "data": {
    "accountNumber": "ACC-2024-0001-ABCD",
    "period": {
      "startDate": "2024-01-01",
      "endDate": "2024-01-31"
    },
    "summary": {
      "totalTransactions": 48,
      "totalDebitAmount": 2500.00,
      "totalCreditAmount": 3200.00,
      "netChange": 700.00,
      "averageTransactionAmount": 118.75
    },
    "byStatus": {
      "completed": 45,
      "failed": 2,
      "pending": 1
    },
    "byType": {
      "debit": 22,
      "credit": 26
    }
  },
  "message": "Statistics retrieved successfully"
}
```

**cURL Example:**
```bash
# Last 30 days statistics
curl -X GET "http://localhost:7000/api/transactions/account/ACC-2024-0001-ABCD/statistics?startDate=2024-01-01&endDate=2024-01-31" \
  -H "Authorization: Bearer $TOKEN"
```

---

## Health & Monitoring

### 12. Health Check

**Endpoint:** `GET /health`  
**Authentication:** None required  
**Description:** Check overall system health (database + Redis)

**Response (200 OK - Healthy):**
```json
{
  "status": "healthy",
  "timestamp": "2024-01-15T17:00:00+00:00",
  "checks": {
    "database": "ok",
    "redis": "ok"
  }
}
```

**Response (503 Service Unavailable - Unhealthy):**
```json
{
  "status": "unhealthy",
  "timestamp": "2024-01-15T17:00:00+00:00",
  "checks": {
    "database": "error",
    "redis": "ok"
  },
  "errors": {
    "database": "Connection timeout"
  }
}
```

**cURL Example:**
```bash
curl -X GET http://localhost:7000/health
```

**Use Case:** Monitoring, load balancer health checks

---

### 13. Liveness Probe

**Endpoint:** `GET /health/live`  
**Authentication:** None required  
**Description:** Check if application is alive

**Response (200 OK):**
```json
{
  "status": "alive"
}
```

**cURL Example:**
```bash
curl -X GET http://localhost:7000/health/live
```

**Use Case:** Kubernetes liveness probe, restart trigger

---

### 14. Readiness Probe

**Endpoint:** `GET /health/ready`  
**Authentication:** None required  
**Description:** Check if application is ready to serve traffic

**Response (200 OK - Ready):**
```json
{
  "status": "ready",
  "checks": {
    "database": true,
    "redis": true
  }
}
```

**Response (503 Service Unavailable - Not Ready):**
```json
{
  "status": "not_ready",
  "checks": {
    "database": false,
    "redis": true
  }
}
```

**cURL Example:**
```bash
curl -X GET http://localhost:7000/health/ready
```

**Use Case:** Kubernetes readiness probe, load balancer routing

---

## Error Handling

### Standard Error Response Format

All errors follow consistent structure:

```json
{
  "status": "error",
  "message": "Human-readable error message",
  "code": "ERROR_CODE",
  "errors": { ... }
}
```

### HTTP Status Codes

| Code | Meaning | Common Causes |
|------|---------|---------------|
| **400** | Bad Request | Validation errors, invalid input |
| **401** | Unauthorized | Missing/invalid JWT token |
| **403** | Forbidden | Insufficient permissions |
| **404** | Not Found | Resource doesn't exist |
| **409** | Conflict | Duplicate resource, concurrent modification |
| **422** | Unprocessable Entity | Business logic validation failed |
| **500** | Internal Server Error | Unexpected server error |
| **503** | Service Unavailable | Database/Redis connection failed |

### Common Error Scenarios

#### Invalid JWT Token
```json
{
  "status": "error",
  "message": "Invalid JWT Token",
  "code": "INVALID_TOKEN"
}
```

#### Expired JWT Token
```json
{
  "status": "error",
  "message": "Expired JWT Token",
  "code": "EXPIRED_TOKEN"
}
```

#### Validation Errors
```json
{
  "status": "error",
  "message": "Validation failed",
  "errors": {
    "email": ["This value is not a valid email"],
    "amount": ["This value should be greater than 0"]
  }
}
```

#### Insufficient Funds
```json
{
  "status": "error",
  "message": "Insufficient funds",
  "code": "INSUFFICIENT_FUNDS",
  "details": {
    "available": 100.00,
    "required": 250.00,
    "shortfall": 150.00
  }
}
```

#### Account Not Found
```json
{
  "status": "error",
  "message": "Account not found",
  "code": "ACCOUNT_NOT_FOUND"
}
```

#### Concurrent Modification (Optimistic Lock)
```json
{
  "status": "error",
  "message": "Account was modified by another transaction",
  "code": "CONCURRENT_MODIFICATION"
}
```

---

## Postman Collection

### Import Collection

Import the pre-configured Postman collection for instant API testing:

**File:** [`postman_collection.json`](../postman_collection.json)

**Import Steps:**
1. Open Postman
2. Click **Import** button
3. Select **Upload Files**
4. Choose `postman_collection.json`
5. Collection imported with all 14 endpoints

### Collection Features

✅ All 14 API endpoints pre-configured  
✅ Environment variables for base URL and tokens  
✅ Automated token management  
✅ Sample request bodies  
✅ Pre-request scripts  
✅ Test assertions  

### Environment Setup

Create Postman environment with:

```json
{
  "base_url": "http://localhost:7000",
  "token": "",
  "accountNumber": "",
  "referenceNumber": ""
}
```

Variables auto-populate from responses:
- `token` saved after login
- `accountNumber` saved after account creation
- `referenceNumber` saved after transfer

### Collection Structure

```
Payment API/
├── Authentication/
│   ├── Register
│   ├── Login
│   └── Get Current User
├── Accounts/
│   ├── Create Account
│   ├── List Accounts
│   ├── Get Account
│   └── Get Balance
├── Transactions/
│   ├── Transfer Funds
│   ├── Get Transaction
│   ├── List Account Transactions
│   └── Get Statistics
└── Health/
    ├── Health Check
    ├── Liveness Probe
    └── Readiness Probe
```

---

## Code Examples

### PHP (Guzzle)

```php
<?php
require 'vendor/autoload.php';

use GuzzleHttp\Client;

$client = new Client(['base_uri' => 'http://localhost:7000']);

// 1. Register
$response = $client->post('/api/auth/register', [
    'json' => [
        'email' => 'user@example.com',
        'password' => 'SecurePass123!',
        'name' => 'John Doe'
    ]
]);
echo $response->getBody();

// 2. Login
$response = $client->post('/api/auth/login', [
    'json' => [
        'email' => 'user@example.com',
        'password' => 'SecurePass123!'
    ]
]);
$data = json_decode($response->getBody(), true);
$token = $data['token'];

// 3. Create Account
$response = $client->post('/api/accounts', [
    'headers' => ['Authorization' => "Bearer $token"],
    'json' => [
        'accountName' => 'Savings',
        'initialBalance' => 1000.00,
        'currency' => 'USD'
    ]
]);
$account = json_decode($response->getBody(), true);

// 4. Transfer Funds
$response = $client->post('/api/transactions/transfer', [
    'headers' => ['Authorization' => "Bearer $token"],
    'json' => [
        'fromAccountNumber' => 'ACC-2024-0001-ABCD',
        'toAccountNumber' => 'ACC-2024-0002-EFGH',
        'amount' => 250.00,
        'description' => 'Payment'
    ]
]);
echo $response->getBody();
```

### Python (requests)

```python
import requests

base_url = 'http://localhost:7000'

# 1. Register
response = requests.post(f'{base_url}/api/auth/register', json={
    'email': 'user@example.com',
    'password': 'SecurePass123!',
    'name': 'John Doe'
})
print(response.json())

# 2. Login
response = requests.post(f'{base_url}/api/auth/login', json={
    'email': 'user@example.com',
    'password': 'SecurePass123!'
})
token = response.json()['token']

# 3. Create Account
headers = {'Authorization': f'Bearer {token}'}
response = requests.post(f'{base_url}/api/accounts', 
    headers=headers,
    json={
        'accountName': 'Savings',
        'initialBalance': 1000.00,
        'currency': 'USD'
    }
)
account = response.json()['data']

# 4. Transfer Funds
response = requests.post(f'{base_url}/api/transactions/transfer',
    headers=headers,
    json={
        'fromAccountNumber': 'ACC-2024-0001-ABCD',
        'toAccountNumber': 'ACC-2024-0002-EFGH',
        'amount': 250.00,
        'description': 'Payment'
    }
)
print(response.json())

# 5. Get Transaction Status
ref_number = response.json()['data']['referenceNumber']
response = requests.get(f'{base_url}/api/transactions/{ref_number}', headers=headers)
print(response.json())
```

### JavaScript (Node.js - Axios)

```javascript
const axios = require('axios');

const baseURL = 'http://localhost:7000';
const api = axios.create({ baseURL });

(async () => {
  try {
    // 1. Register
    const registerRes = await api.post('/api/auth/register', {
      email: 'user@example.com',
      password: 'SecurePass123!',
      name: 'John Doe'
    });
    console.log(registerRes.data);

    // 2. Login
    const loginRes = await api.post('/api/auth/login', {
      email: 'user@example.com',
      password: 'SecurePass123!'
    });
    const token = loginRes.data.token;
    
    // Set default auth header
    api.defaults.headers.common['Authorization'] = `Bearer ${token}`;

    // 3. Create Account
    const accountRes = await api.post('/api/accounts', {
      accountName: 'Savings',
      initialBalance: 1000.00,
      currency: 'USD'
    });
    console.log(accountRes.data);

    // 4. Transfer Funds
    const transferRes = await api.post('/api/transactions/transfer', {
      fromAccountNumber: 'ACC-2024-0001-ABCD',
      toAccountNumber: 'ACC-2024-0002-EFGH',
      amount: 250.00,
      description: 'Payment'
    });
    console.log(transferRes.data);

    // 5. Get Transaction Status
    const refNumber = transferRes.data.data.referenceNumber;
    const txnRes = await api.get(`/api/transactions/${refNumber}`);
    console.log(txnRes.data);

  } catch (error) {
    console.error('Error:', error.response?.data || error.message);
  }
})();
```

### cURL Complete Workflow

```bash
#!/bin/bash

BASE_URL="http://localhost:7000"

# 1. Register
curl -X POST "$BASE_URL/api/auth/register" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "user@example.com",
    "password": "SecurePass123!",
    "name": "John Doe"
  }'

# 2. Login and extract token
TOKEN=$(curl -s -X POST "$BASE_URL/api/auth/login" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "user@example.com",
    "password": "SecurePass123!"
  }' | jq -r '.token')

echo "Token: $TOKEN"

# 3. Create first account
ACCOUNT1=$(curl -s -X POST "$BASE_URL/api/accounts" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "accountName": "Primary Account",
    "initialBalance": 5000.00,
    "currency": "USD"
  }' | jq -r '.data.accountNumber')

echo "Account 1: $ACCOUNT1"

# 4. Create second account
ACCOUNT2=$(curl -s -X POST "$BASE_URL/api/accounts" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "accountName": "Savings Account",
    "initialBalance": 1000.00,
    "currency": "USD"
  }' | jq -r '.data.accountNumber')

echo "Account 2: $ACCOUNT2"

# 5. Transfer funds
TXREF=$(curl -s -X POST "$BASE_URL/api/transactions/transfer" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d "{
    \"fromAccountNumber\": \"$ACCOUNT1\",
    \"toAccountNumber\": \"$ACCOUNT2\",
    \"amount\": 250.00,
    \"description\": \"Savings transfer\"
  }" | jq -r '.data.referenceNumber')

echo "Transaction Reference: $TXREF"

# 6. Wait for async processing
sleep 2

# 7. Check transaction status
curl -s -X GET "$BASE_URL/api/transactions/$TXREF" \
  -H "Authorization: Bearer $TOKEN" | jq

# 8. Check balances
echo "Account 1 Balance:"
curl -s -X GET "$BASE_URL/api/accounts/$ACCOUNT1/balance" \
  -H "Authorization: Bearer $TOKEN" | jq

echo "Account 2 Balance:"
curl -s -X GET "$BASE_URL/api/accounts/$ACCOUNT2/balance" \
  -H "Authorization: Bearer $TOKEN" | jq

# 9. Get transaction statistics
curl -s -X GET "$BASE_URL/api/transactions/account/$ACCOUNT1/statistics" \
  -H "Authorization: Bearer $TOKEN" | jq
```

---

## Testing Async Processing

Since transfers are processed asynchronously, here's how to verify:

### 1. Initiate Transfer
```bash
curl -X POST http://localhost:7000/api/transactions/transfer \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "fromAccountNumber": "ACC-2024-0001-ABCD",
    "toAccountNumber": "ACC-2024-0002-EFGH",
    "amount": 100.00,
    "description": "Test transfer"
  }'
```

**Response:** Status will be `pending`

### 2. Check Queue (Optional)
```bash
docker exec -it php-application php bin/console messenger:stats
```

**Output:**
```
 async
------------
  2 queued
  0 processing
  15 succeeded
  0 failed
```

### 3. Poll Transaction Status
```bash
# Wait 1-2 seconds, then check
curl -X GET http://localhost:7000/api/transactions/TXN-2024-0001-WXYZ \
  -H "Authorization: Bearer $TOKEN"
```

**Response:** Status should be `completed` or `failed`

### 4. Verify Balances
```bash
curl -X GET http://localhost:7000/api/accounts/ACC-2024-0001-ABCD/balance \
  -H "Authorization: Bearer $TOKEN"
```

Balances should reflect the transfer.

---

## Rate Limiting (Future Enhancement)

To add rate limiting, install Symfony Rate Limiter:

```bash
composer require symfony/rate-limiter
```

Configure in `config/packages/rate_limiter.yaml`:

```yaml
framework:
    rate_limiter:
        api_login:
            policy: 'sliding_window'
            limit: 5
            interval: '1 minute'
        
        api_transfer:
            policy: 'token_bucket'
            limit: 10
            rate: { interval: '1 minute', amount: 2 }
```

Apply to controllers:

```php
use Symfony\Component\RateLimiter\RateLimiterFactory;

#[Route('/api/auth/login', methods: ['POST'])]
public function login(Request $request, RateLimiterFactory $apiLoginLimiter)
{
    $limiter = $apiLoginLimiter->create($request->getClientIp());
    if (!$limiter->consume(1)->isAccepted()) {
        return new JsonResponse(['error' => 'Too many requests'], 429);
    }
    // ... login logic
}
```

---

## Troubleshooting

### Issue: "Invalid JWT Token"
**Solution:** Ensure token is valid and not expired. Re-login to get new token.

### Issue: Transaction stuck in "pending"
**Solution:** 
1. Check messenger worker is running: `docker-compose ps`
2. Check logs: `docker-compose logs messenger-worker`
3. Process manually: `docker exec -it messenger-worker php bin/console messenger:consume async -vv`

### Issue: "Account not found"
**Solution:** Verify account number is correct and belongs to authenticated user.

### Issue: "Insufficient funds"
**Solution:** Check account balance before transfer. Account must have enough funds + buffer.

### Issue: 401 Unauthorized
**Solution:** 
1. Ensure `Authorization: Bearer <token>` header is present
2. Verify token hasn't expired (1 hour expiry)
3. Check token format is correct

---

## Additional Resources

- **[Setup Guide](./SETUP.md)** - Installation and configuration
- **[README](./README.md)** - Project overview and architecture
- **[Postman Collection](../postman_collection.json)** - Pre-configured API tests

---

**For support or questions, refer to the main [README](./README.md) or open an issue on GitHub.**
