# Nananom Farms API - Quick Overview

## Quick Start

1. **Setup**: Run `./setup.sh` or follow manual setup in README.md
2. **Database**: Import `database/schema.sql`
3. **Configure**: Edit `.env` file with your credentials
4. **Test**: Run `php test_api.php`

## API Endpoints Summary

### Public Endpoints (No Authentication Required)
- `POST /api/enquiries` - Submit enquiry
- `POST /api/bookings` - Book service

### Admin Endpoints
- `POST /api/admin/auth` - Login/Update password
- `GET /api/enquiries` - View all enquiries
- `PUT /api/enquiries` - Update enquiry status
- `GET /api/bookings` - View all bookings
- `PUT /api/bookings` - Update booking status
- `GET /api/agents` - View all agents
- `PUT /api/agents` - Update agent details
- `DELETE /api/agents` - Delete agent

### Agent Endpoints
- `POST /api/agent/auth` - Register/Login
- `GET /api/enquiries` - View assigned enquiries
- `PUT /api/enquiries` - Update assigned enquiries
- `GET /api/bookings` - View assigned bookings
- `PUT /api/bookings` - Update assigned bookings

## Authentication

- **Admin**: Credentials stored in `.env` file
- **Agents**: Self-registration with email/password
- **JWT Tokens**: 24-hour expiration for all authenticated requests

## Database Tables

1. **admin** - Single admin user
2. **support_agents** - Agent accounts and details
3. **enquiries** - User enquiries with status tracking
4. **bookings** - Service bookings with scheduling

## Security Features

- JWT token authentication
- Password hashing (bcrypt)
- SQL injection prevention
- CORS protection
- Environment variable configuration
- Role-based access control

## File Structure

```
nananom-farms-backend/
├── api/
│   ├── admin/auth.php
│   ├── agent/auth.php
│   ├── enquiries.php
│   ├── bookings.php
│   └── agents.php
├── config/
│   └── database.php
├── database/
│   └── schema.sql
├── includes/
│   └── auth.php
├── .env.example
├── .htaccess
├── composer.json
├── index.php
├── setup.sh
├── test_api.php
└── README.md
```

## Default Credentials

**Admin:**
- Username: `admin`
- Password: `admin123`

**⚠️ Change these in production!**