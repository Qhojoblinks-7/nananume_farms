# 📚 Nananom Farms API Documentation

Complete API documentation for the Nananom Farms backend system.

## 📋 Table of Contents

- [Base URL](#base-url)
- [Authentication](#authentication)
- [Error Handling](#error-handling)
- [Admin Endpoints](#admin-endpoints)
- [Agent Endpoints](#agent-endpoints)
- [Enquiry Endpoints](#enquiry-endpoints)
- [Booking Endpoints](#booking-endpoints)
- [Agent Management Endpoints](#agent-management-endpoints)
- [Response Codes](#response-codes)

## 🌐 Base URL

```
Development: http://localhost:8000
Production: https://your-domain.com
```

## 🔐 Authentication

### JWT Token Format
All authenticated endpoints require a JWT token in the Authorization header:

```http
Authorization: Bearer <jwt_token>
```

### Token Structure
```json
{
  "user_id": 1,
  "role": "admin",
  "username": "admin",
  "iat": 1640995200,
  "exp": 1641081600
}
```

### Token Expiration
- **Default:** 24 hours
- **Refresh:** Re-login required

## ⚠️ Error Handling

### Standard Error Response
```json
{
  "error": "Error message description",
  "code": "ERROR_CODE",
  "details": {}
}
```

### Common Error Codes
- `400` - Bad Request
- `401` - Unauthorized
- `403` - Forbidden
- `404` - Not Found
- `500` - Internal Server Error

## 👨‍💼 Admin Endpoints

### Admin Authentication

#### Login
**Endpoint:** `POST /api/admin/auth`

**Description:** Authenticate admin user with embedded credentials

**Request Body:**
```json
{
  "action": "login",
  "username": "admin",
  "password": "admin123"
}
```

**Success Response (200):**
```json
{
  "success": true,
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
  "user": {
    "id": 1,
    "username": "admin",
    "email": "admin@nananomfarms.com",
    "full_name": "System Administrator",
    "user_type": "admin"
  }
}
```

**Error Response (401):**
```json
{
  "error": "Invalid credentials"
}
```

#### Update Password
**Endpoint:** `POST /api/admin/auth`

**Description:** Update admin password (requires authentication)

**Request Body:**
```json
{
  "action": "update_password",
  "current_password": "admin123",
  "new_password": "newSecurePassword123"
}
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Password updated successfully"
}
```

**Error Response (401):**
```json
{
  "error": "Current password is incorrect"
}
```

## 👥 Agent Endpoints

### Agent Authentication

#### Login
**Endpoint:** `POST /api/agent/auth`

**Description:** Authenticate support agent

**Request Body:**
```json
{
  "action": "login",
  "username": "agent_username",
  "password": "agent_password"
}
```

**Success Response (200):**
```json
{
  "success": true,
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
  "user": {
    "id": 2,
    "username": "agent_username",
    "email": "agent@example.com",
    "full_name": "Agent Name",
    "phone": "+1234567890",
    "region": "North Region",
    "is_active": true,
    "user_type": "agent"
  }
}
```

#### Registration
**Endpoint:** `POST /api/agent/auth`

**Description:** Register new support agent (admin only)

**Request Body:**
```json
{
  "action": "register",
  "username": "new_agent",
  "email": "newagent@example.com",
  "password": "securePassword123",
  "full_name": "New Agent Name",
  "phone": "+1234567890",
  "region": "South Region"
}
```

**Success Response (201):**
```json
{
  "success": true,
  "message": "Agent registered successfully",
  "agent": {
    "id": 3,
    "username": "new_agent",
    "email": "newagent@example.com",
    "full_name": "New Agent Name",
    "phone": "+1234567890",
    "region": "South Region",
    "is_active": true
  }
}
```

**Error Response (400):**
```json
{
  "error": "Username already exists"
}
```

## 📝 Enquiry Endpoints

### Create Enquiry (Public)
**Endpoint:** `POST /api/enquiries`

**Description:** Create new enquiry (no authentication required)

**Request Body:**
```json
{
  "full_name": "John Doe",
  "email": "john@example.com",
  "contact": "+1234567890",
  "company": "ABC Corporation",
  "subject": "Service Inquiry",
  "message": "I would like to know more about your farming services and pricing."
}
```

**Success Response (201):**
```json
{
  "success": true,
  "message": "Enquiry created successfully",
  "enquiry": {
    "id": 1,
    "full_name": "John Doe",
    "email": "john@example.com",
    "contact": "+1234567890",
    "company": "ABC Corporation",
    "subject": "Service Inquiry",
    "message": "I would like to know more about your farming services and pricing.",
    "status": "pending",
    "assigned_to": null,
    "created_at": "2024-01-15T10:30:00Z"
  }
}
```

### Get All Enquiries (Admin/Agent)
**Endpoint:** `GET /api/enquiries`

**Description:** Retrieve all enquiries with optional filters

**Headers:**
```http
Authorization: Bearer <jwt_token>
```

**Query Parameters:**
- `status` - Filter by status (pending, in_progress, resolved, closed)
- `assigned_to` - Filter by assigned agent ID
- `page` - Page number for pagination
- `limit` - Number of items per page

**Example Request:**
```http
GET /api/enquiries?status=pending&page=1&limit=10
Authorization: Bearer <jwt_token>
```

**Success Response (200):**
```json
{
  "success": true,
  "enquiries": [
    {
      "id": 1,
      "full_name": "John Doe",
      "email": "john@example.com",
      "contact": "+1234567890",
      "company": "ABC Corporation",
      "subject": "Service Inquiry",
      "message": "I would like to know more about your farming services.",
      "status": "pending",
      "assigned_to": null,
      "created_at": "2024-01-15T10:30:00Z",
      "updated_at": "2024-01-15T10:30:00Z"
    }
  ],
  "pagination": {
    "current_page": 1,
    "total_pages": 5,
    "total_items": 50,
    "items_per_page": 10
  },
  "stats": {
    "total": 50,
    "pending": 15,
    "in_progress": 20,
    "resolved": 10,
    "closed": 5
  }
}
```

### Get Enquiry by ID (Admin/Agent)
**Endpoint:** `GET /api/enquiries/{id}`

**Description:** Retrieve specific enquiry details

**Headers:**
```http
Authorization: Bearer <jwt_token>
```

**Success Response (200):**
```json
{
  "success": true,
  "enquiry": {
    "id": 1,
    "full_name": "John Doe",
    "email": "john@example.com",
    "contact": "+1234567890",
    "company": "ABC Corporation",
    "subject": "Service Inquiry",
    "message": "I would like to know more about your farming services.",
    "status": "pending",
    "assigned_to": null,
    "created_at": "2024-01-15T10:30:00Z",
    "updated_at": "2024-01-15T10:30:00Z"
  }
}
```

### Update Enquiry (Admin/Agent)
**Endpoint:** `PUT /api/enquiries/{id}`

**Description:** Update enquiry status and assignment

**Headers:**
```http
Authorization: Bearer <jwt_token>
Content-Type: application/json
```

**Request Body:**
```json
{
  "status": "in_progress",
  "assigned_to": 2
}
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Enquiry updated successfully",
  "enquiry": {
    "id": 1,
    "status": "in_progress",
    "assigned_to": 2,
    "updated_at": "2024-01-15T11:00:00Z"
  }
}
```

### Get Enquiry Statistics (Admin/Agent)
**Endpoint:** `GET /api/enquiries/stats`

**Description:** Get enquiry statistics and analytics

**Headers:**
```http
Authorization: Bearer <jwt_token>
```

**Success Response (200):**
```json
{
  "success": true,
  "stats": {
    "total": 150,
    "pending": 25,
    "in_progress": 45,
    "resolved": 60,
    "closed": 20,
    "this_month": 30,
    "last_month": 25,
    "avg_response_time": "2.5 hours"
  }
}
```

## 📅 Booking Endpoints

### Create Booking (Public)
**Endpoint:** `POST /api/bookings`

**Description:** Create new service booking (no authentication required)

**Request Body:**
```json
{
  "full_name": "Jane Smith",
  "email": "jane@example.com",
  "contact": "+1234567890",
  "company": "XYZ Inc",
  "service_type": "Farm Consultation",
  "booking_date": "2024-01-20",
  "booking_time": "14:00:00",
  "additional_notes": "First time customer, interested in organic farming"
}
```

**Success Response (201):**
```json
{
  "success": true,
  "message": "Booking created successfully",
  "booking": {
    "id": 1,
    "full_name": "Jane Smith",
    "email": "jane@example.com",
    "contact": "+1234567890",
    "company": "XYZ Inc",
    "service_type": "Farm Consultation",
    "booking_date": "2024-01-20",
    "booking_time": "14:00:00",
    "additional_notes": "First time customer, interested in organic farming",
    "status": "pending",
    "assigned_to": null,
    "created_at": "2024-01-15T10:30:00Z"
  }
}
```

### Get All Bookings (Admin/Agent)
**Endpoint:** `GET /api/bookings`

**Description:** Retrieve all bookings with optional filters

**Headers:**
```http
Authorization: Bearer <jwt_token>
```

**Query Parameters:**
- `status` - Filter by status (pending, confirmed, completed, cancelled)
- `assigned_to` - Filter by assigned agent ID
- `date_from` - Filter from date (YYYY-MM-DD)
- `date_to` - Filter to date (YYYY-MM-DD)
- `page` - Page number for pagination
- `limit` - Number of items per page

**Example Request:**
```http
GET /api/bookings?status=pending&date_from=2024-01-01&page=1&limit=10
Authorization: Bearer <jwt_token>
```

**Success Response (200):**
```json
{
  "success": true,
  "bookings": [
    {
      "id": 1,
      "full_name": "Jane Smith",
      "email": "jane@example.com",
      "contact": "+1234567890",
      "company": "XYZ Inc",
      "service_type": "Farm Consultation",
      "booking_date": "2024-01-20",
      "booking_time": "14:00:00",
      "additional_notes": "First time customer",
      "status": "pending",
      "assigned_to": null,
      "created_at": "2024-01-15T10:30:00Z",
      "updated_at": "2024-01-15T10:30:00Z"
    }
  ],
  "pagination": {
    "current_page": 1,
    "total_pages": 3,
    "total_items": 25,
    "items_per_page": 10
  },
  "stats": {
    "total": 25,
    "pending": 8,
    "confirmed": 12,
    "completed": 3,
    "cancelled": 2
  }
}
```

### Update Booking (Admin/Agent)
**Endpoint:** `PUT /api/bookings/{id}`

**Description:** Update booking status and assignment

**Headers:**
```http
Authorization: Bearer <jwt_token>
Content-Type: application/json
```

**Request Body:**
```json
{
  "status": "confirmed",
  "assigned_to": 2
}
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Booking updated successfully",
  "booking": {
    "id": 1,
    "status": "confirmed",
    "assigned_to": 2,
    "updated_at": "2024-01-15T11:00:00Z"
  }
}
```

### Get Upcoming Bookings (Admin/Agent)
**Endpoint:** `GET /api/bookings/upcoming`

**Description:** Get upcoming bookings for the next 30 days

**Headers:**
```http
Authorization: Bearer <jwt_token>
```

**Success Response (200):**
```json
{
  "success": true,
  "upcoming_bookings": [
    {
      "id": 1,
      "full_name": "Jane Smith",
      "service_type": "Farm Consultation",
      "booking_date": "2024-01-20",
      "booking_time": "14:00:00",
      "status": "confirmed",
      "assigned_to": 2
    }
  ],
  "total_upcoming": 15
}
```

### Get Booking Statistics (Admin/Agent)
**Endpoint:** `GET /api/bookings/stats`

**Description:** Get booking statistics and analytics

**Headers:**
```http
Authorization: Bearer <jwt_token>
```

**Success Response (200):**
```json
{
  "success": true,
  "stats": {
    "total": 150,
    "pending": 20,
    "confirmed": 80,
    "completed": 40,
    "cancelled": 10,
    "this_month": 25,
    "last_month": 20,
    "avg_completion_time": "3.2 days"
  }
}
```

## 👥 Agent Management Endpoints

### Get All Agents (Admin Only)
**Endpoint:** `GET /api/agents`

**Description:** Retrieve all support agents

**Headers:**
```http
Authorization: Bearer <jwt_token>
```

**Query Parameters:**
- `region` - Filter by region
- `is_active` - Filter by active status (true/false)
- `page` - Page number for pagination
- `limit` - Number of items per page

**Success Response (200):**
```json
{
  "success": true,
  "agents": [
    {
      "id": 2,
      "username": "agent_username",
      "email": "agent@example.com",
      "full_name": "Agent Name",
      "phone": "+1234567890",
      "region": "North Region",
      "is_active": true,
      "created_at": "2024-01-01T00:00:00Z",
      "updated_at": "2024-01-01T00:00:00Z"
    }
  ],
  "pagination": {
    "current_page": 1,
    "total_pages": 2,
    "total_items": 15,
    "items_per_page": 10
  },
  "stats": {
    "total": 15,
    "active": 12,
    "inactive": 3,
    "regions": ["North Region", "South Region", "East Region"]
  }
}
```

### Update Agent (Admin Only)
**Endpoint:** `PUT /api/agents/{id}`

**Description:** Update agent information

**Headers:**
```http
Authorization: Bearer <jwt_token>
Content-Type: application/json
```

**Request Body:**
```json
{
  "full_name": "Updated Agent Name",
  "phone": "+1234567890",
  "region": "South Region",
  "is_active": true
}
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Agent updated successfully",
  "agent": {
    "id": 2,
    "full_name": "Updated Agent Name",
    "phone": "+1234567890",
    "region": "South Region",
    "is_active": true,
    "updated_at": "2024-01-15T11:00:00Z"
  }
}
```

### Delete Agent (Admin Only)
**Endpoint:** `DELETE /api/agents/{id}`

**Description:** Delete support agent

**Headers:**
```http
Authorization: Bearer <jwt_token>
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Agent deleted successfully"
}
```

**Error Response (400):**
```json
{
  "error": "Cannot delete agent with assigned enquiries or bookings"
}
```

### Get Agent Statistics (Admin Only)
**Endpoint:** `GET /api/agents/stats`

**Description:** Get agent performance statistics

**Headers:**
```http
Authorization: Bearer <jwt_token>
```

**Success Response (200):**
```json
{
  "success": true,
  "stats": {
    "total_agents": 15,
    "active_agents": 12,
    "inactive_agents": 3,
    "top_performers": [
      {
        "agent_id": 2,
        "agent_name": "Agent Name",
        "enquiries_handled": 45,
        "bookings_managed": 23,
        "avg_response_time": "1.5 hours"
      }
    ],
    "regional_distribution": {
      "North Region": 5,
      "South Region": 4,
      "East Region": 3,
      "West Region": 3
    }
  }
}
```

## 📊 Response Codes

### Success Codes
- `200` - OK (Request successful)
- `201` - Created (Resource created successfully)
- `204` - No Content (Request successful, no content to return)

### Client Error Codes
- `400` - Bad Request (Invalid request data)
- `401` - Unauthorized (Authentication required)
- `403` - Forbidden (Insufficient permissions)
- `404` - Not Found (Resource not found)
- `409` - Conflict (Resource conflict)
- `422` - Unprocessable Entity (Validation error)

### Server Error Codes
- `500` - Internal Server Error (Server error)
- `502` - Bad Gateway (Gateway error)
- `503` - Service Unavailable (Service temporarily unavailable)

## 🔒 Security Considerations

### Rate Limiting
- **Public endpoints:** 100 requests per hour
- **Authenticated endpoints:** 1000 requests per hour
- **Admin endpoints:** 5000 requests per hour

### Input Validation
- All inputs are validated and sanitized
- SQL injection protection via prepared statements
- XSS protection via output encoding
- CSRF protection for state-changing operations

### Data Privacy
- Sensitive data is encrypted at rest
- Passwords are hashed using bcrypt
- JWT tokens have expiration times
- HTTPS required in production

## 📝 Examples

### Complete Authentication Flow
```bash
# 1. Admin Login
curl -X POST http://localhost:8000/api/admin/auth \
  -H "Content-Type: application/json" \
  -d '{
    "action": "login",
    "username": "admin",
    "password": "admin123"
  }'

# 2. Use token for authenticated requests
curl -X GET http://localhost:8000/api/enquiries \
  -H "Authorization: Bearer <jwt_token>"
```

### Create Enquiry and Update Status
```bash
# 1. Create enquiry (public)
curl -X POST http://localhost:8000/api/enquiries \
  -H "Content-Type: application/json" \
  -d '{
    "full_name": "John Doe",
    "email": "john@example.com",
    "contact": "+1234567890",
    "subject": "Service Inquiry",
    "message": "I need help with my farm."
  }'

# 2. Update status (admin/agent)
curl -X PUT http://localhost:8000/api/enquiries/1 \
  -H "Authorization: Bearer <jwt_token>" \
  -H "Content-Type: application/json" \
  -d '{
    "status": "in_progress",
    "assigned_to": 2
  }'
```

---

**For more information, see the main [README.md](README.md) file.**