# 🐘 XAMPP Setup Guide for Nananom Farms

Complete guide for setting up and running the Nananom Farms project using XAMPP.

## 📋 Table of Contents

- [Prerequisites](#prerequisites)
- [XAMPP Installation](#xampp-installation)
- [Project Setup](#project-setup)
- [Database Configuration](#database-configuration)
- [Backend Setup](#backend-setup)
- [Frontend Setup](#frontend-setup)
- [Testing the Application](#testing-the-application)
- [Troubleshooting](#troubleshooting)
- [Production Considerations](#production-considerations)

## 🔧 Prerequisites

### Required Software
- **XAMPP** (Latest version) - Download from [Apache Friends](https://www.apachefriends.org/)
- **Node.js** (v16 or higher) - Download from [Node.js](https://nodejs.org/)
- **Git** - Download from [Git](https://git-scm.com/)
- **Composer** - Download from [Composer](https://getcomposer.org/)

### System Requirements
- **Windows:** Windows 7 or higher
- **macOS:** macOS 10.12 or higher
- **Linux:** Ubuntu 18.04 or higher
- **RAM:** Minimum 4GB (8GB recommended)
- **Storage:** At least 2GB free space

## 🚀 XAMPP Installation

### 1. Download and Install XAMPP

1. **Download XAMPP:**
   - Go to [https://www.apachefriends.org/](https://www.apachefriends.org/)
   - Download the latest version for your operating system
   - Choose the version with PHP 8.0 or higher

2. **Install XAMPP:**
   ```bash
   # Windows
   # Run the downloaded .exe file as administrator
   
   # macOS
   # Open the downloaded .dmg file and drag to Applications
   
   # Linux
   # Run the downloaded .run file
   chmod +x xampp-linux-*-installer.run
   sudo ./xampp-linux-*-installer.run
   ```

3. **Start XAMPP Services:**
   ```bash
   # Start XAMPP Control Panel
   # Click "Start" for Apache and MySQL services
   
   # Or via command line
   sudo /opt/lampp/lampp start  # Linux
   # Windows: Use XAMPP Control Panel
   # macOS: Use XAMPP Control Panel
   ```

### 2. Verify Installation

1. **Check Apache:**
   - Open browser and go to: `http://localhost`
   - You should see the XAMPP welcome page

2. **Check MySQL:**
   - Go to: `http://localhost/phpmyadmin`
   - Default credentials: `root` (no password)

3. **Check PHP:**
   - Go to: `http://localhost/dashboard/phpinfo.php`
   - Verify PHP version is 8.0 or higher

## 📁 Project Setup

### 1. Clone the Repository

```bash
# Navigate to XAMPP htdocs directory
cd /opt/lampp/htdocs  # Linux
cd C:\xampp\htdocs    # Windows
cd /Applications/XAMPP/htdocs  # macOS

# Clone the repository
git clone https://github.com/Qhojoblinks-7/nananume_farms.git
cd nananume_farms
```

### 2. Project Structure in XAMPP

```
📁 C:\xampp\htdocs\nanume_farms\  # Windows
📁 /opt/lampp/htdocs/nanume_farms/  # Linux
📁 /Applications/XAMPP/htdocs/nanume_farms/  # macOS
├── 📁 nananom-farms-backend/
│   ├── 📁 api/
│   ├── 📁 config/
│   ├── 📁 database/
│   ├── 📁 includes/
│   ├── 📄 .env
│   ├── 📄 composer.json
│   └── 📄 index.php
├── 📁 nananom-farms-frontend/
│   ├── 📁 src/
│   ├── 📄 package.json
│   └── 📄 vite.config.js
├── 📄 README.md
├── 📄 API_DOCUMENTATION.md
└── 📄 XAMPP_SETUP_GUIDE.md
```

## 🗄️ Database Configuration

### Option 1: Use SQLite (Recommended for Development)

The project is configured to use SQLite by default, which is perfect for XAMPP development:

1. **Database Location:**
   ```
   📁 nananom-farms-backend/database/nananom_farms.db
   ```

2. **Automatic Setup:**
   - The database will be created automatically when you first access the API
   - No additional configuration needed

### Option 2: Use MySQL (Alternative)

If you prefer MySQL with XAMPP:

1. **Create Database:**
   - Open phpMyAdmin: `http://localhost/phpmyadmin`
   - Click "New" to create a new database
   - Name it: `nananom_farms`

2. **Import Schema:**
   - Click on the `nananom_farms` database
   - Go to "Import" tab
   - Choose file: `nananom-farms-backend/database/schema.sql`
   - Click "Go" to import

3. **Update Configuration:**
   ```env
   # Edit nananom-farms-backend/.env
   DB_HOST=localhost
   DB_NAME=nananom_farms
   DB_USER=root
   DB_PASS=
   ```

## 🔧 Backend Setup

### 1. Install PHP Dependencies

```bash
# Navigate to backend directory
cd nananom-farms-backend

# Install Composer (if not already installed)
# Windows: Download composer-setup.exe from getcomposer.org
# Linux/macOS: curl -sS https://getcomposer.org/installer | php

# Install dependencies
composer install
```

### 2. Configure Environment

```bash
# Copy environment file
cp .env.example .env

# Edit .env file with your settings
nano .env  # Linux/macOS
notepad .env  # Windows
```

**Environment Configuration:**
```env
# Database Configuration (SQLite - recommended)
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
API_BASE_URL=http://localhost/nanume_farms/nananom-farms-backend
```

### 3. Test Backend

1. **Access API:**
   - Go to: `http://localhost/nanume_farms/nananom-farms-backend/`
   - You should see the API welcome message

2. **Test Database Connection:**
   - Go to: `http://localhost/nanume_farms/nananom-farms-backend/test_api.php`
   - Check if database connection is successful

3. **Test Admin Login:**
   ```bash
   # Using curl or Postman
   POST http://localhost/nanume_farms/nananom-farms-backend/api/admin/auth
   Content-Type: application/json
   
   {
     "action": "login",
     "username": "admin",
     "password": "admin123"
   }
   ```

## ⚛️ Frontend Setup

### 1. Install Node.js Dependencies

```bash
# Navigate to frontend directory
cd nananom-farms-frontend

# Install dependencies
npm install
```

### 2. Configure Environment

```bash
# Copy environment file
cp .env.example .env

# Edit .env file
nano .env  # Linux/macOS
notepad .env  # Windows
```

**Frontend Environment:**
```env
# API Base URL (point to XAMPP backend)
VITE_API_BASE_URL=http://localhost/nanume_farms/nananom-farms-backend
```

### 3. Start Development Server

```bash
# Start frontend development server
npm run dev
```

**Access Frontend:**
- Development server: `http://localhost:5173`
- Or build for production and serve via XAMPP

### 4. Build for Production (Optional)

```bash
# Build the application
npm run build

# Copy to XAMPP htdocs for serving via Apache
cp -r dist/* /opt/lampp/htdocs/nanume_farms/frontend/  # Linux
xcopy dist\* C:\xampp\htdocs\nanume_farms\frontend\ /E /I  # Windows
cp -r dist/* /Applications/XAMPP/htdocs/nanume_farms/frontend/  # macOS
```

## 🧪 Testing the Application

### 1. Backend API Testing

1. **Health Check:**
   ```
   GET http://localhost/nanume_farms/nananom-farms-backend/
   ```

2. **Admin Login:**
   ```
   POST http://localhost/nanume_farms/nananom-farms-backend/api/admin/auth
   {
     "action": "login",
     "username": "admin",
     "password": "admin123"
   }
   ```

3. **Create Enquiry:**
   ```
   POST http://localhost/nanume_farms/nananom-farms-backend/api/enquiries
   {
     "full_name": "John Doe",
     "email": "john@example.com",
     "contact": "+1234567890",
     "subject": "Test Enquiry",
     "message": "This is a test enquiry"
   }
   ```

### 2. Frontend Testing

1. **Access Application:**
   - Development: `http://localhost:5173`
   - Production: `http://localhost/nanume_farms/frontend/`

2. **Test Admin Login:**
   - Go to login page
   - Select "Administrator"
   - Use credentials: `admin` / `admin123`

3. **Test Public Features:**
   - Create enquiry without login
   - Book service without registration

## 🔧 Troubleshooting

### Common XAMPP Issues

#### 1. Port Conflicts
**Problem:** Apache or MySQL won't start
**Solution:**
```bash
# Check what's using the ports
netstat -ano | findstr :80    # Windows
lsof -i :80                   # Linux/macOS

# Change ports in XAMPP
# Edit /opt/lampp/etc/httpd.conf (Linux)
# Edit C:\xampp\apache\conf\httpd.conf (Windows)
# Change Listen 80 to Listen 8080
```

#### 2. Permission Issues
**Problem:** Can't write to database or logs
**Solution:**
```bash
# Linux/macOS
sudo chown -R daemon:daemon /opt/lampp/htdocs/nanume_farms
sudo chmod -R 755 /opt/lampp/htdocs/nanume_farms

# Windows
# Run XAMPP as Administrator
```

#### 3. PHP Extensions Missing
**Problem:** Composer or application errors
**Solution:**
```bash
# Enable required extensions in php.ini
# Edit /opt/lampp/etc/php.ini
extension=sqlite3
extension=pdo_sqlite
extension=openssl
extension=mbstring
extension=json
```

#### 4. CORS Issues
**Problem:** Frontend can't connect to backend
**Solution:**
```apache
# Add to /opt/lampp/etc/httpd.conf or .htaccess
Header always set Access-Control-Allow-Origin "*"
Header always set Access-Control-Allow-Methods "GET, POST, PUT, DELETE, OPTIONS"
Header always set Access-Control-Allow-Headers "Content-Type, Authorization"
```

### Database Issues

#### 1. SQLite Database Not Created
**Problem:** Database file doesn't exist
**Solution:**
```bash
# Check if database directory exists
ls -la nananom-farms-backend/database/

# Create directory if missing
mkdir -p nananom-farms-backend/database/

# Set permissions
chmod 755 nananom-farms-backend/database/
```

#### 2. MySQL Connection Failed
**Problem:** Can't connect to MySQL
**Solution:**
```bash
# Check MySQL service
sudo /opt/lampp/lampp status

# Restart MySQL
sudo /opt/lampp/lampp restart mysql

# Check credentials in .env file
```

### Frontend Issues

#### 1. API Connection Failed
**Problem:** Frontend can't reach backend
**Solution:**
```bash
# Check API URL in .env
VITE_API_BASE_URL=http://localhost/nanume_farms/nananom-farms-backend

# Test API directly
curl http://localhost/nanume_farms/nananom-farms-backend/
```

#### 2. Build Errors
**Problem:** npm run build fails
**Solution:**
```bash
# Clear cache and reinstall
rm -rf node_modules package-lock.json
npm install
npm run build
```

## 🚀 Production Considerations

### When Moving to Production

1. **Use MySQL Instead of SQLite:**
   ```sql
   -- Create production database
   CREATE DATABASE nananom_farms_prod;
   CREATE USER 'nananom_user'@'localhost' IDENTIFIED BY 'secure_password';
   GRANT ALL PRIVILEGES ON nananom_farms_prod.* TO 'nananom_user'@'localhost';
   ```

2. **Update Environment Variables:**
   ```env
   # Production settings
   APP_ENV=production
   APP_DEBUG=false
   JWT_SECRET=your-very-secure-production-secret
   ADMIN_USERNAME=your_admin_username
   ADMIN_PASSWORD=your_secure_password
   ```

3. **Security Headers:**
   ```apache
   # Add to Apache configuration
   Header always set X-Frame-Options "SAMEORIGIN"
   Header always set X-XSS-Protection "1; mode=block"
   Header always set X-Content-Type-Options "nosniff"
   ```

4. **SSL Certificate:**
   - Install SSL certificate for HTTPS
   - Update API URLs to use HTTPS

## 📋 Quick Reference

### URLs
- **XAMPP Dashboard:** `http://localhost/dashboard/`
- **phpMyAdmin:** `http://localhost/phpmyadmin`
- **Backend API:** `http://localhost/nanume_farms/nananom-farms-backend/`
- **Frontend Dev:** `http://localhost:5173`
- **Frontend Prod:** `http://localhost/nanume_farms/frontend/`

### Default Credentials
- **Admin Login:** `admin` / `admin123`
- **MySQL Root:** `root` (no password)

### Important Files
- **Backend Config:** `nananom-farms-backend/.env`
- **Frontend Config:** `nananom-farms-frontend/.env`
- **Database:** `nananom-farms-backend/database/nananom_farms.db`
- **Apache Config:** `/opt/lampp/etc/httpd.conf`
- **PHP Config:** `/opt/lampp/etc/php.ini`

### Useful Commands
```bash
# Start XAMPP
sudo /opt/lampp/lampp start

# Stop XAMPP
sudo /opt/lampp/lampp stop

# Restart XAMPP
sudo /opt/lampp/lampp restart

# Check status
sudo /opt/lampp/lampp status

# Install Composer dependencies
cd nananom-farms-backend && composer install

# Install Node.js dependencies
cd nananom-farms-frontend && npm install

# Start frontend dev server
cd nananom-farms-frontend && npm run dev
```

---

**🎉 You're now ready to develop with XAMPP!**

For more information, see the main [README.md](README.md) and [API_DOCUMENTATION.md](API_DOCUMENTATION.md) files.