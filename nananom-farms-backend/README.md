# Nananom Farms Backend API

A simple PHP backend API for Nananom Farms with MySQL database, supporting admin authentication, support agent management, and user enquiries/bookings.

## Features

- **Admin Authentication**: Single admin with credentials stored in environment variables
- **Support Agent Management**: Agents can register, login, and manage enquiries/bookings
- **User Enquiries**: Public API for users to submit enquiries
- **Service Bookings**: Public API for users to book services
- **JWT Authentication**: Secure token-based authentication
- **Role-based Access Control**: Different permissions for admin and agents

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- Composer (for dependencies)

## Installation

1. **Clone or download the backend files**
   ```bash
   cd nananom-farms-backend
   ```

2. **Install dependencies**
   ```bash
   composer install
   ```

3. **Set up the database**
   ```bash
   mysql -u root -p < database/schema.sql
   ```

4. **Configure environment variables**
   ```bash
   cp .env.example .env
   ```
   
   Edit `.env` file with your database credentials and security settings:
   ```env
   DB_HOST=localhost
   DB_NAME=nananom_farms
   DB_USER=your_db_user
   DB_PASSWORD=your_db_password
   
   ADMIN_USERNAME=admin
   ADMIN_PASSWORD=your_secure_password
   
   JWT_SECRET=your_super_secret_jwt_key
   ```

5. **Set up web server**
   - Point your web server to the `nananom-farms-backend` directory
   - Ensure `.htaccess` is enabled (for Apache)
   - Make sure PHP has write permissions for logs (if any)

## API Endpoints

### Authentication

#### Admin Login
```
POST /api/admin/auth
Content-Type: application/json

{
    "action": "login",
    "username": "admin",
    "password": "admin123"
}
```

#### Admin Password Update
```
POST /api/admin/auth
Authorization: Bearer <admin_token>
Content-Type: application/json

{
    "action": "update_password",
    "current_password": "old_password",
    "new_password": "new_password"
}
```

#### Support Agent Registration
```
POST /api/agent/auth
Content-Type: application/json

{
    "action": "register",
    "username": "agent1",
    "email": "agent@example.com",
    "password": "password123",
    "full_name": "John Doe",
    "phone": "+1234567890",
    "region": "North Region"
}
```

#### Support Agent Login
```
POST /api/agent/auth
Content-Type: application/json

{
    "action": "login",
    "username": "agent1",
    "password": "password123"
}
```

### Enquiries

#### Create Enquiry (Public)
```
POST /api/enquiries
Content-Type: application/json

{
    "full_name": "John Doe",
    "email": "john@example.com",
    "contact": "+1234567890",
    "company": "ABC Corp",
    "subject": "General Inquiry",
    "message": "I would like to know more about your services."
}
```

#### Get Enquiries (Admin/Agent)
```
GET /api/enquiries?status=pending&page=1&limit=10
Authorization: Bearer <token>
```

#### Update Enquiry (Admin/Agent)
```
PUT /api/enquiries
Authorization: Bearer <token>
Content-Type: application/json

{
    "enquiry_id": 1,
    "status": "in_progress",
    "assigned_to": 2
}
```

### Bookings

#### Create Booking (Public)
```
POST /api/bookings
Content-Type: application/json

{
    "full_name": "Jane Smith",
    "email": "jane@example.com",
    "contact": "+1234567890",
    "company": "XYZ Ltd",
    "service_type": "Farm Consultation",
    "booking_date": "2024-01-15",
    "booking_time": "14:00:00",
    "additional_notes": "Prefer afternoon appointment"
}
```

#### Get Bookings (Admin/Agent)
```
GET /api/bookings?status=pending&date_from=2024-01-01&page=1&limit=10
Authorization: Bearer <token>
```

#### Update Booking (Admin/Agent)
```
PUT /api/bookings
Authorization: Bearer <token>
Content-Type: application/json

{
    "booking_id": 1,
    "status": "confirmed",
    "assigned_to": 2
}
```

### Agent Management (Admin Only)

#### Get Agents
```
GET /api/agents?is_active=true&region=North&page=1&limit=10
Authorization: Bearer <admin_token>
```

#### Update Agent
```
PUT /api/agents
Authorization: Bearer <admin_token>
Content-Type: application/json

{
    "agent_id": 1,
    "is_active": false,
    "region": "South Region",
    "phone": "+1234567890"
}
```

#### Delete Agent
```
DELETE /api/agents
Authorization: Bearer <admin_token>
Content-Type: application/json

{
    "agent_id": 1
}
```

## Security Features

- **JWT Tokens**: Secure authentication with 24-hour expiration
- **Password Hashing**: Bcrypt password hashing for agents
- **Environment Variables**: Sensitive data stored in .env file
- **CORS Protection**: Configured for cross-origin requests
- **SQL Injection Prevention**: Prepared statements throughout
- **Role-based Access**: Different permissions for admin and agents

## Database Schema

The database includes the following tables:
- `admin`: Single admin user with embedded credentials
- `support_agents`: Support agent accounts and details
- `enquiries`: User enquiries with status tracking
- `bookings`: Service bookings with scheduling

## Error Handling

All API endpoints return consistent JSON responses:

**Success Response:**
```json
{
    "success": true,
    "data": {...}
}
```

**Error Response:**
```json
{
    "error": "Error message"
}
```

## Development

To run in development mode:

1. Set `APP_ENV=development` in `.env`
2. Ensure error reporting is enabled
3. Use a local development server or XAMPP/WAMP

## Production Deployment

For production:

1. Set `APP_ENV=production` in `.env`
2. Change all default passwords and secrets
3. Use HTTPS
4. Configure proper database credentials
5. Set up proper file permissions
6. Consider using a reverse proxy (nginx)

## Support

For issues or questions, please refer to the API documentation or contact the development team.