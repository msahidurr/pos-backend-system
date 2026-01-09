# Laravel 11 Multi-Tenant POS & Inventory Management System

A production-ready RESTful API backend for managing point-of-sale transactions, inventory, and business analytics across multiple tenants.

## Features

### Core Functionality
- **Multi-Tenancy**: Complete data isolation with tenant-based access control via `X-Tenant-ID` header
- **Authentication**: Secure registration and login with Laravel Sanctum token-based authentication
- **Role-Based Access Control**: Admin, Manager, and Staff roles with granular permissions
- **Product Management**: Full CRUD operations for products with stock tracking
- **Order Management**: Complete order lifecycle management with automatic inventory updates
- **Inventory Tracking**: Real-time inventory transactions, stock adjustments, and low-stock alerts
- **Reporting**: Comprehensive analytics including daily sales, top products, inventory summary, and payment method breakdown

### Security
- Input validation and sanitization
- SQL injection prevention with parameterized queries
- CORS configuration for API access
- Rate limiting to prevent abuse
- Secure password hashing with bcrypt
- Tenant isolation with strict authorization checks

## Requirements

- PHP 8.2+
- Laravel 11
- MySQL 8.0+
- Composer

## Installation

### 1. Clone Repository
```bash
git clone <repository-url>
cd pos-system
```

### 2. Install Dependencies
```bash
composer install
```

### 3. Environment Setup
```bash
cp .env.example .env
php artisan key:generate
```

### 4. Database Configuration
Edit `.env` file:
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=pos_system
DB_USERNAME=root
DB_PASSWORD=your_password
```

### 5. Run Migrations
```bash
php artisan migrate
```

### 6. Start Server
```bash
php artisan serve
```

The API will be available at `http://localhost:8000/api`

## API Structure

All API requests require:
- `Authorization: Bearer {token}` (header) - Except for `/register` and `/login`
- `X-Tenant-ID: {tenant_id}` (header) - Required for all authenticated requests

## Authentication Endpoints

### Register New Tenant & Admin User
```
POST /api/register
Content-Type: application/json

{
  "tenant_name": "My Store",
  "tenant_slug": "my-store",
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123",
  "password_confirmation": "password123"
}
```

### Login
```
POST /api/login
Content-Type: application/json

{
  "email": "john@example.com",
  "password": "password123"
}
```

Response:
```json
{
  "message": "Login successful",
  "tenant_id": 1,
  "user": { ... },
  "token": "..."
}
```

### Get Current User
```
GET /api/me
Authorization: Bearer {token}
X-Tenant-ID: {tenant_id}
```

### Logout
```
POST /api/logout
Authorization: Bearer {token}
X-Tenant-ID: {tenant_id}
```

## Product Management

### List Products
```
GET /api/products?status=active&low_stock=true&search=SKU&page=1
Authorization: Bearer {token}
X-Tenant-ID: {tenant_id}
```

### Create Product
```
POST /api/products
Authorization: Bearer {token}
X-Tenant-ID: {tenant_id}
Content-Type: application/json

{
  "sku": "PROD001",
  "name": "Product Name",
  "description": "Description",
  "cost_price": 10.00,
  "selling_price": 15.00,
  "quantity_on_hand": 100,
  "reorder_level": 20
}
```

### Get Product
```
GET /api/products/{id}
Authorization: Bearer {token}
X-Tenant-ID: {tenant_id}
```

### Update Product
```
PUT /api/products/{id}
Authorization: Bearer {token}
X-Tenant-ID: {tenant_id}
Content-Type: application/json

{
  "name": "Updated Name",
  "selling_price": 20.00
}
```

### Delete Product
```
DELETE /api/products/{id}
Authorization: Bearer {token}
X-Tenant-ID: {tenant_id}
```

## Order Management

### List Orders
```
GET /api/orders?status=completed&from_date=2024-01-01&to_date=2024-01-31&page=1
Authorization: Bearer {token}
X-Tenant-ID: {tenant_id}
```

### Create Order
```
POST /api/orders
Authorization: Bearer {token}
X-Tenant-ID: {tenant_id}
Content-Type: application/json

{
  "customer_name": "Jane Smith",
  "customer_contact": "555-1234",
  "items": [
    {
      "product_id": 1,
      "quantity": 5
    },
    {
      "product_id": 2,
      "quantity": 3
    }
  ],
  "discount_amount": 5.00,
  "tax_amount": 2.50,
  "payment_method": "cash",
  "payment_received": true
}
```

### Get Order
```
GET /api/orders/{id}
Authorization: Bearer {token}
X-Tenant-ID: {tenant_id}
```

### Cancel Order
```
POST /api/orders/{id}/cancel
Authorization: Bearer {token}
X-Tenant-ID: {tenant_id}
```

## Inventory Management

### Adjust Stock
```
POST /api/inventory/adjust-stock
Authorization: Bearer {token}
X-Tenant-ID: {tenant_id}
Content-Type: application/json

{
  "product_id": 1,
  "adjustment_quantity": 10,
  "reason": "Stock count correction"
}
```

### Get Inventory Transactions
```
GET /api/inventory/transactions?product_id=1&type=adjustment&page=1
Authorization: Bearer {token}
X-Tenant-ID: {tenant_id}
```

### Low Stock Alerts
```
GET /api/inventory/low-stock-alerts
Authorization: Bearer {token}
X-Tenant-ID: {tenant_id}
```

## Reports

### Daily Sales Report
```
GET /api/reports/daily-sales?from=2024-01-01&to=2024-01-31
Authorization: Bearer {token}
X-Tenant-ID: {tenant_id}
```

### Top Products Report
```
GET /api/reports/top-products?limit=10&from=2024-01-01&to=2024-01-31
Authorization: Bearer {token}
X-Tenant-ID: {tenant_id}
```

### Inventory Summary Report
```
GET /api/reports/inventory-summary
Authorization: Bearer {token}
X-Tenant-ID: {tenant_id}
```

### Sales by Payment Method Report
```
GET /api/reports/sales-by-payment-method?from=2024-01-01&to=2024-01-31
Authorization: Bearer {token}
X-Tenant-ID: {tenant_id}
```

## Error Handling

The API returns consistent error responses:

### Validation Error (422)
```json
{
  "message": "Validation failed",
  "errors": {
    "email": ["The email field is required."]
  }
}
```

### Authentication Error (401)
```json
{
  "error": "Unauthenticated"
}
```

### Authorization Error (403)
```json
{
  "error": "You must be a manager to perform this action."
}
```

### Not Found (404)
```json
{
  "error": "Resource not found"
}
```

### Rate Limited (429)
```json
{
  "error": "Too many requests. Please try again later."
}
```

### Server Error (500)
```json
{
  "error": "An error occurred"
}
```

## Database Schema

### Tenants Table
- id (PK)
- name (unique)
- slug (unique)
- domain (nullable, unique)
- metadata (JSON)
- timestamps

### Users Table
- id (PK)
- tenant_id (FK)
- name
- email (unique)
- password
- role (enum: admin, manager, staff)
- is_active (boolean)
- timestamps

### Products Table
- id (PK)
- tenant_id (FK)
- sku (unique)
- name
- description
- cost_price (decimal)
- selling_price (decimal)
- quantity_on_hand (integer)
- reorder_level (integer)
- status (enum: active, inactive, discontinued)
- timestamps

### Orders Table
- id (PK)
- tenant_id (FK)
- order_no (unique)
- customer_name
- customer_contact
- subtotal (decimal)
- tax_amount (decimal)
- discount_amount (decimal)
- total_amount (decimal)
- status (enum: pending, completed, cancelled)
- payment_method (enum: cash, card, check, online)
- payment_received (boolean)
- created_by (FK to users)
- timestamps

### Order Items Table
- id (PK)
- order_id (FK)
- product_id (FK)
- quantity (integer)
- unit_price (decimal)
- line_total (decimal)
- timestamps

### Inventory Transactions Table
- id (PK)
- tenant_id (FK)
- product_id (FK)
- type (enum: purchase, sale, adjustment, return)
- quantity (integer)
- reference_no
- notes
- created_by (FK to users)
- timestamps

## Best Practices

- Always include the `X-Tenant-ID` header in all authenticated requests
- Store the API token securely on the client side
- Use pagination for list endpoints to handle large datasets
- Implement proper error handling and retry logic in client applications
- Log all important API interactions for audit trails
- Regularly backup your database
- Keep environment variables secure and never commit to version control

## Support

For issues or questions, please open an issue in the repository or contact the development team.

## License

This project is licensed under the MIT License.
