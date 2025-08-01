# 🌾 Nananom Farms - Full Stack Application

A modern web application for Nananom Farms, featuring a React frontend with Redux state management and a PHP backend with SQLite database.

## 📋 Table of Contents

- [Overview](#overview)
- [Features](#features)
- [Tech Stack](#tech-stack)
- [Project Structure](#project-structure)
- [Quick Start](#quick-start)
- [Installation](#installation)
- [Configuration](#configuration)
- [API Documentation](#api-documentation)
- [Authentication](#authentication)
- [Database Schema](#database-schema)
- [Development](#development)
- [Deployment](#deployment)
- [Troubleshooting](#troubleshooting)
- [Contributing](#contributing)

## 🎯 Overview

Nananom Farms is a comprehensive web application designed to manage farm operations, customer enquiries, service bookings, and support agent management. The application features role-based access control with three user types:

- **Administrators**: Full system access and management
- **Support Agents**: Regional representatives with limited access
- **Public Users**: Can make enquiries and book services

## ✨ Features

### 🔐 Authentication & Authorization
- **Role-based access control** (Admin, Support Agent, Public)
- **JWT token authentication**
- **Secure password handling** with bcrypt
- **Session management**

### 👨‍💼 Admin Features
- **Dashboard** with system statistics
- **User management** (support agents)
- **Enquiry management** and assignment
- **Booking management** and scheduling
- **System configuration**

### 👥 Support Agent Features
- **Regional access** to enquiries and bookings
- **Customer interaction** management
- **Service booking** assistance
- **Status updates** and tracking

### 🌐 Public Features
- **Service enquiries** without registration
- **Booking requests** with contact information
- **Company information** submission
- **Real-time status** tracking

## 🛠 Tech Stack

### Frontend
- **React 18** - Modern UI framework
- **Vite** - Fast build tool
- **Redux Toolkit** - State management
- **React Router DOM** - Client-side routing
- **Tailwind CSS** - Utility-first CSS framework
- **Axios** - HTTP client

### Backend
- **PHP 8.4** - Server-side language
- **SQLite** - Lightweight database
- **JWT** - JSON Web Tokens for authentication
- **Composer** - Dependency management
- **PDO** - Database abstraction layer

### Development Tools
- **Git** - Version control
- **GitHub** - Code repository
- **ESLint** - Code linting
- **Prettier** - Code formatting

## 📁 Project Structure

```
nananom-farms/
├── nananom-farms-frontend/          # React frontend application
│   ├── src/
│   │   ├── components/              # Reusable UI components
│   │   ├── pages/                   # Page components
│   │   │   ├── Auth/               # Authentication pages
│   │   │   └── Dashboard/          # Dashboard pages
│   │   ├── store/                  # Redux store configuration
│   │   │   ├── slices/             # Redux slices
│   │   │   └── index.js            # Store setup
│   │   ├── services/               # API service functions
│   │   ├── hooks/                  # Custom React hooks
│   │   └── App.jsx                 # Main application component
│   ├── package.json                # Frontend dependencies
│   └── setup.sh                    # Frontend setup script
│
├── nananom-farms-backend/           # PHP backend application
│   ├── api/                        # API endpoints
│   │   ├── admin/                  # Admin-specific APIs
│   │   ├── agent/                  # Agent-specific APIs
│   │   ├── enquiries.php           # Enquiry management
│   │   └── bookings.php            # Booking management
│   ├── config/                     # Configuration files
│   │   └── database.php            # Database configuration
│   ├── includes/                   # Shared PHP files
│   │   └── auth.php                # Authentication utilities
│   ├── database/                   # Database files
│   │   ├── schema.sql              # MySQL schema (reference)
│   │   └── nananom_farms.db        # SQLite database
│   ├── vendor/                     # Composer dependencies
│   ├── .env                        # Environment variables
│   ├── composer.json               # PHP dependencies
│   └── setup.sh                    # Backend setup script
│
├── INTEGRATION_GUIDE.md            # Integration documentation
├── REDUX_GUIDE.md                  # Redux implementation guide
└── README.md                       # This file
```

## 🚀 Quick Start

### Prerequisites
- **Node.js** (v16 or higher)
- **PHP** (v8.0 or higher)
- **Composer** (for PHP dependencies)
- **Git**

### 1. Clone the Repository
```bash
git clone https://github.com/Qhojoblinks-7/nananume_farms.git
cd nananume_farms
```

### 2. Backend Setup
```bash
cd nananom-farms-backend
chmod +x setup.sh
./setup.sh
```

### 3. Frontend Setup
```bash
cd ../nananom-farms-frontend
chmod +x setup.sh
./setup.sh
```

### 4. Start Development Servers
```bash
# Terminal 1: Backend
cd nananom-farms-backend
php -S localhost:8000

# Terminal 2: Frontend
cd nananom-farms-frontend
npm run dev
```

### 5. Access the Application
- **Frontend**: http://localhost:5173
- **Backend API**: http://localhost:8000
- **Admin Login**: admin / admin123

## 📦 Installation

### Backend Installation

1. **Navigate to backend directory:**
   ```bash
   cd nananom-farms-backend
   ```

2. **Install PHP dependencies:**
   ```bash
   composer install
   ```

3. **Configure environment:**
   ```bash
   cp .env.example .env
   # Edit .env with your settings
   ```

4. **Start the server:**
   ```bash
   php -S localhost:8000
   ```

### Frontend Installation

1. **Navigate to frontend directory:**
   ```bash
   cd nananom-farms-frontend
   ```

2. **Install Node.js dependencies:**
   ```bash
   npm install
   ```

3. **Configure environment:**
   ```bash
   cp .env.example .env
   # Edit .env with your API URL
   ```

4. **Start development server:**
   ```bash
   npm run dev
   ```

## ⚙️ Configuration

### Backend Configuration (.env)

```env
# Database Configuration (SQLite)
DB_HOST=localhost
DB_NAME=nananom_farms
DB_USER=root
DB_PASS=

# Admin Credentials
ADMIN_USERNAME=admin
ADMIN_PASSWORD=admin123

# JWT Secret Key
JWT_SECRET=your-super-secret-jwt-key-change-this-in-production

# API Configuration
API_BASE_URL=http://localhost:8000/nananom-farms-backend
```

### Frontend Configuration (.env)

```env
# API Base URL
VITE_API_BASE_URL=http://localhost:8000/nananom-farms-backend
```

## 📚 API Documentation

### Authentication Endpoints

#### Admin Login
```http
POST /api/admin/auth
Content-Type: application/json

{
  "action": "login",
  "username": "admin",
  "password": "admin123"
}
```

#### Agent Login
```http
POST /api/agent/auth
Content-Type: application/json

{
  "action": "login",
  "username": "agent_username",
  "password": "agent_password"
}
```

#### Agent Registration
```http
POST /api/agent/auth
Content-Type: application/json

{
  "action": "register",
  "username": "new_agent",
  "email": "agent@example.com",
  "password": "password123",
  "full_name": "Agent Name",
  "phone": "+1234567890",
  "region": "North Region"
}
```

### Enquiry Endpoints

#### Create Enquiry (Public)
```http
POST /api/enquiries
Content-Type: application/json

{
  "full_name": "John Doe",
  "email": "john@example.com",
  "contact": "+1234567890",
  "company": "ABC Corp",
  "subject": "Service Inquiry",
  "message": "I would like to know more about your services."
}
```

#### Get All Enquiries (Admin/Agent)
```http
GET /api/enquiries
Authorization: Bearer <jwt_token>
```

#### Update Enquiry Status
```http
PUT /api/enquiries/{id}
Authorization: Bearer <jwt_token>
Content-Type: application/json

{
  "status": "in_progress",
  "assigned_to": 1
}
```

### Booking Endpoints

#### Create Booking (Public)
```http
POST /api/bookings
Content-Type: application/json

{
  "full_name": "Jane Smith",
  "email": "jane@example.com",
  "contact": "+1234567890",
  "company": "XYZ Inc",
  "service_type": "Consultation",
  "booking_date": "2024-01-15",
  "booking_time": "14:00:00",
  "additional_notes": "First time customer"
}
```

#### Get All Bookings (Admin/Agent)
```http
GET /api/bookings
Authorization: Bearer <jwt_token>
```

### Agent Management (Admin Only)

#### Get All Agents
```http
GET /api/agents
Authorization: Bearer <jwt_token>
```

#### Update Agent
```http
PUT /api/agents/{id}
Authorization: Bearer <jwt_token>
Content-Type: application/json

{
  "is_active": false,
  "region": "South Region"
}
```

## 🔐 Authentication

### JWT Token Structure
```json
{
  "user_id": 1,
  "role": "admin",
  "username": "admin",
  "iat": 1640995200,
  "exp": 1641081600
}
```

### Role-Based Access Control

#### Admin Permissions
- ✅ Full system access
- ✅ User management (agents)
- ✅ All enquiries and bookings
- ✅ System configuration
- ✅ Dashboard statistics

#### Support Agent Permissions
- ✅ Regional enquiries and bookings
- ✅ Customer interaction
- ✅ Status updates
- ❌ User management
- ❌ System configuration

#### Public User Permissions
- ✅ Create enquiries
- ✅ Create bookings
- ❌ View other data
- ❌ System access

## 🗄️ Database Schema

### Admin Table
```sql
CREATE TABLE admin (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT UNIQUE NOT NULL,
    email TEXT UNIQUE NOT NULL,
    password_hash TEXT NOT NULL,
    full_name TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

### Support Agents Table
```sql
CREATE TABLE support_agents (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT UNIQUE NOT NULL,
    email TEXT UNIQUE NOT NULL,
    password_hash TEXT NOT NULL,
    full_name TEXT NOT NULL,
    phone TEXT,
    region TEXT,
    is_active INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

### Enquiries Table
```sql
CREATE TABLE enquiries (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    full_name TEXT NOT NULL,
    email TEXT NOT NULL,
    contact TEXT NOT NULL,
    company TEXT,
    subject TEXT NOT NULL,
    message TEXT NOT NULL,
    status TEXT DEFAULT 'pending',
    assigned_to INTEGER,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (assigned_to) REFERENCES support_agents(id)
);
```

### Bookings Table
```sql
CREATE TABLE bookings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    full_name TEXT NOT NULL,
    email TEXT NOT NULL,
    contact TEXT NOT NULL,
    company TEXT,
    service_type TEXT NOT NULL,
    booking_date DATE NOT NULL,
    booking_time TIME,
    additional_notes TEXT,
    status TEXT DEFAULT 'pending',
    assigned_to INTEGER,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (assigned_to) REFERENCES support_agents(id)
);
```

## 🛠 Development

### Frontend Development

#### Redux Store Structure
```javascript
{
  auth: {
    token: string,
    user: object,
    loading: boolean,
    error: string
  },
  enquiries: {
    list: array,
    stats: object,
    loading: boolean,
    error: string
  },
  bookings: {
    list: array,
    upcoming: array,
    stats: object,
    loading: boolean,
    error: string
  },
  agents: {
    list: array,
    stats: object,
    loading: boolean,
    error: string
  },
  dashboard: {
    stats: object,
    recentActivity: array,
    loading: boolean,
    error: string
  },
  ui: {
    modals: object,
    notifications: array,
    sidebar: object,
    theme: string
  }
}
```

#### Custom Hooks
- `useAppDispatch()` - Typed dispatch hook
- `useAppSelector()` - Typed selector hook

#### Service Functions
- `auth.js` - Authentication services
- `enquiryService.js` - Enquiry management
- `bookingService.js` - Booking management
- `agentService.js` - Agent management
- `dashboardService.js` - Dashboard data

### Backend Development

#### API Structure
```
/api/
├── admin/
│   └── auth.php          # Admin authentication
├── agent/
│   └── auth.php          # Agent authentication
├── enquiries.php         # Enquiry management
├── bookings.php          # Booking management
└── agents.php            # Agent management
```

#### Authentication Flow
1. **Login Request** → Validate credentials
2. **Generate JWT** → Include user info and role
3. **Return Token** → Client stores in localStorage
4. **API Requests** → Include token in Authorization header
5. **Token Validation** → Verify signature and expiration
6. **Role Check** → Ensure proper permissions

## 🚀 Deployment

### Production Setup

#### Backend Deployment
1. **Upload files** to web server
2. **Install dependencies:**
   ```bash
   composer install --no-dev
   ```
3. **Configure environment:**
   ```bash
   cp .env.example .env
   # Update with production values
   ```
4. **Set permissions:**
   ```bash
   chmod 755 database/
   chmod 644 database/*.db
   ```

#### Frontend Deployment
1. **Build for production:**
   ```bash
   npm run build
   ```
2. **Upload dist/ folder** to web server
3. **Configure API URL** in production environment

### Environment Variables (Production)
```env
# Backend
JWT_SECRET=your-very-secure-production-secret
ADMIN_USERNAME=your-admin-username
ADMIN_PASSWORD=your-secure-admin-password

# Frontend
VITE_API_BASE_URL=https://your-domain.com/api
```

## 🔧 Troubleshooting

### Common Issues

#### 1. Admin Login Fails
**Problem:** 500 Internal Server Error
**Solution:**
- Check if `.env` file exists
- Verify admin credentials in `.env`
- Ensure database is created
- Check PHP error logs

#### 2. Frontend Can't Connect to Backend
**Problem:** CORS errors or connection refused
**Solution:**
- Verify backend server is running
- Check API URL in frontend `.env`
- Ensure CORS headers are set
- Check firewall settings

#### 3. Database Connection Issues
**Problem:** SQLite database not found
**Solution:**
- Ensure `database/` directory exists
- Check file permissions
- Verify database path in configuration

#### 4. JWT Token Issues
**Problem:** Authentication fails
**Solution:**
- Check JWT_SECRET in `.env`
- Verify token expiration
- Ensure proper Authorization header

### Debug Mode

#### Backend Debug
```php
// Add to PHP files for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

#### Frontend Debug
```javascript
// Add to React components
console.log('Debug info:', data);
```

### Logs
- **PHP Error Log:** Check server error logs
- **Browser Console:** Check for JavaScript errors
- **Network Tab:** Monitor API requests

## 🤝 Contributing

### Development Workflow
1. **Fork the repository**
2. **Create feature branch:**
   ```bash
   git checkout -b feature/your-feature-name
   ```
3. **Make changes** and test thoroughly
4. **Commit changes:**
   ```bash
   git commit -m "Add feature: description"
   ```
5. **Push to branch:**
   ```bash
   git push origin feature/your-feature-name
   ```
6. **Create pull request**

### Code Standards
- **PHP:** PSR-12 coding standards
- **JavaScript:** ESLint configuration
- **CSS:** Tailwind CSS classes
- **Git:** Conventional commit messages

### Testing
- **Frontend:** Test all user flows
- **Backend:** Test all API endpoints
- **Integration:** Test frontend-backend communication
- **Security:** Test authentication and authorization

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 📞 Support

For support and questions:
- **Email:** support@nananomfarms.com
- **Documentation:** [Project Wiki](https://github.com/Qhojoblinks-7/nananume_farms/wiki)
- **Issues:** [GitHub Issues](https://github.com/Qhojoblinks-7/nananume_farms/issues)

## 🙏 Acknowledgments

- **React Team** for the amazing frontend framework
- **PHP Community** for robust backend solutions
- **Tailwind CSS** for beautiful utility-first styling
- **Redux Toolkit** for excellent state management

---

**Nananom Farms** - Harnessing Growth Through Technology 🌾