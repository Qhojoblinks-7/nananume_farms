# 🚀 Nananom Farms Deployment Guide

Complete guide for deploying the Nananom Farms application in development and production environments.

## 📋 Table of Contents

- [Prerequisites](#prerequisites)
- [Development Deployment](#development-deployment)
- [Production Deployment](#production-deployment)
- [Environment Configuration](#environment-configuration)
- [Database Setup](#database-setup)
- [Security Considerations](#security-considerations)
- [Monitoring & Logging](#monitoring--logging)
- [Troubleshooting](#troubleshooting)

## 🔧 Prerequisites

### System Requirements
- **Operating System:** Linux (Ubuntu 20.04+), macOS, or Windows
- **PHP:** 8.0 or higher
- **Node.js:** 16.0 or higher
- **Composer:** Latest version
- **Git:** Latest version
- **Web Server:** Apache or Nginx (production)
- **SSL Certificate:** For HTTPS (production)

### Required PHP Extensions
```bash
# Install required PHP extensions
sudo apt install php8.4-cli php8.4-fpm php8.4-mysql php8.4-sqlite3 \
php8.4-json php8.4-mbstring php8.4-xml php8.4-curl php8.4-zip
```

### Required Node.js Packages
```bash
# Install Node.js and npm
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt-get install -y nodejs
```

## 🛠 Development Deployment

### 1. Clone Repository
```bash
git clone https://github.com/Qhojoblinks-7/nananume_farms.git
cd nananume_farms
```

### 2. Backend Setup
```bash
cd nananom-farms-backend

# Install PHP dependencies
composer install

# Create environment file
cp .env.example .env

# Edit environment variables
nano .env

# Make setup script executable
chmod +x setup.sh
./setup.sh
```

### 3. Frontend Setup
```bash
cd ../nananom-farms-frontend

# Install Node.js dependencies
npm install

# Create environment file
cp .env.example .env

# Edit environment variables
nano .env

# Make setup script executable
chmod +x setup.sh
./setup.sh
```

### 4. Start Development Servers
```bash
# Terminal 1: Backend Server
cd nananom-farms-backend
php -S localhost:8000

# Terminal 2: Frontend Server
cd nananom-farms-frontend
npm run dev
```

### 5. Verify Installation
- **Frontend:** http://localhost:5173
- **Backend API:** http://localhost:8000
- **Admin Login:** admin / admin123

## 🌐 Production Deployment

### Option 1: Traditional Web Server (Apache/Nginx)

#### Apache Configuration

1. **Install Apache and PHP:**
```bash
sudo apt update
sudo apt install apache2 php8.4 libapache2-mod-php8.4
sudo systemctl enable apache2
sudo systemctl start apache2
```

2. **Configure Virtual Host:**
```apache
# /etc/apache2/sites-available/nananom-farms.conf
<VirtualHost *:80>
    ServerName your-domain.com
    ServerAlias www.your-domain.com
    DocumentRoot /var/www/nananom-farms
    
    # Backend API
    Alias /api /var/www/nananom-farms/nananom-farms-backend
    <Directory /var/www/nananom-farms/nananom-farms-backend>
        AllowOverride All
        Require all granted
    </Directory>
    
    # Frontend
    Alias / /var/www/nananom-farms/nananom-farms-frontend/dist
    <Directory /var/www/nananom-farms/nananom-farms-frontend/dist>
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/nananom-farms_error.log
    CustomLog ${APACHE_LOG_DIR}/nananom-farms_access.log combined
</VirtualHost>
```

3. **Enable Site:**
```bash
sudo a2ensite nananom-farms.conf
sudo a2enmod rewrite
sudo systemctl reload apache2
```

#### Nginx Configuration

1. **Install Nginx and PHP-FPM:**
```bash
sudo apt update
sudo apt install nginx php8.4-fpm
sudo systemctl enable nginx php8.4-fpm
sudo systemctl start nginx php8.4-fpm
```

2. **Configure Nginx:**
```nginx
# /etc/nginx/sites-available/nananom-farms
server {
    listen 80;
    server_name your-domain.com www.your-domain.com;
    root /var/www/nananom-farms/nananom-farms-frontend/dist;
    index index.html;

    # Frontend routes
    location / {
        try_files $uri $uri/ /index.html;
    }

    # Backend API
    location /api/ {
        alias /var/www/nananom-farms/nananom-farms-backend/;
        try_files $uri $uri/ /index.php?$query_string;
        
        location ~ \.php$ {
            fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
            fastcgi_index index.php;
            fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
            include fastcgi_params;
        }
    }

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
    add_header Content-Security-Policy "default-src 'self' http: https: data: blob: 'unsafe-inline'" always;
}
```

3. **Enable Site:**
```bash
sudo ln -s /etc/nginx/sites-available/nananom-farms /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

### Option 2: Docker Deployment

#### Docker Compose Setup

1. **Create docker-compose.yml:**
```yaml
version: '3.8'

services:
  # Backend API
  backend:
    build:
      context: ./nananom-farms-backend
      dockerfile: Dockerfile
    ports:
      - "8000:80"
    environment:
      - DB_HOST=db
      - DB_NAME=nananom_farms
      - DB_USER=root
      - DB_PASS=password
      - JWT_SECRET=your-super-secret-jwt-key
      - ADMIN_USERNAME=admin
      - ADMIN_PASSWORD=admin123
    volumes:
      - ./nananom-farms-backend:/var/www/html
      - ./logs:/var/log/apache2
    depends_on:
      - db

  # Frontend
  frontend:
    build:
      context: ./nananom-farms-frontend
      dockerfile: Dockerfile
    ports:
      - "3000:80"
    environment:
      - VITE_API_BASE_URL=http://localhost:8000/api
    depends_on:
      - backend

  # Database (MySQL for production)
  db:
    image: mysql:8.0
    environment:
      MYSQL_ROOT_PASSWORD: password
      MYSQL_DATABASE: nananom_farms
    volumes:
      - mysql_data:/var/lib/mysql
      - ./nananom-farms-backend/database/schema.sql:/docker-entrypoint-initdb.d/schema.sql
    ports:
      - "3306:3306"

volumes:
  mysql_data:
```

2. **Backend Dockerfile:**
```dockerfile
# nananom-farms-backend/Dockerfile
FROM php:8.4-apache

# Install dependencies
RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    unzip \
    git \
    && docker-php-ext-install zip pdo pdo_mysql

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . .

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Enable Apache modules
RUN a2enmod rewrite

# Copy Apache configuration
COPY apache.conf /etc/apache2/sites-available/000-default.conf

EXPOSE 80

CMD ["apache2-foreground"]
```

3. **Frontend Dockerfile:**
```dockerfile
# nananom-farms-frontend/Dockerfile
FROM node:18-alpine as build

WORKDIR /app

# Copy package files
COPY package*.json ./

# Install dependencies
RUN npm ci --only=production

# Copy source code
COPY . .

# Build application
RUN npm run build

# Production stage
FROM nginx:alpine

# Copy built application
COPY --from=build /app/dist /usr/share/nginx/html

# Copy nginx configuration
COPY nginx.conf /etc/nginx/conf.d/default.conf

EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]
```

4. **Deploy with Docker:**
```bash
# Build and start services
docker-compose up -d

# View logs
docker-compose logs -f

# Stop services
docker-compose down
```

### Option 3: Cloud Deployment

#### AWS Deployment

1. **EC2 Instance Setup:**
```bash
# Launch EC2 instance (Ubuntu 20.04)
# Connect via SSH
ssh -i your-key.pem ubuntu@your-ec2-ip

# Install dependencies
sudo apt update
sudo apt install apache2 php8.4 libapache2-mod-php8.4 git composer
```

2. **Deploy Application:**
```bash
# Clone repository
git clone https://github.com/Qhojoblinks-7/nananume_farms.git
cd nananume_farms

# Setup backend
cd nananom-farms-backend
composer install --no-dev
cp .env.example .env
# Edit .env with production values

# Setup frontend
cd ../nananom-farms-frontend
npm install
npm run build
cp .env.example .env
# Edit .env with production API URL

# Move to web directory
sudo cp -r nananom-farms-backend /var/www/
sudo cp -r nananom-farms-frontend/dist /var/www/nananom-farms-frontend
```

3. **Configure Apache:**
```bash
# Create virtual host configuration
sudo nano /etc/apache2/sites-available/nananom-farms.conf
# Add configuration from Apache section above

# Enable site
sudo a2ensite nananom-farms.conf
sudo a2enmod rewrite
sudo systemctl reload apache2
```

#### Heroku Deployment

1. **Backend Deployment:**
```bash
# Create Heroku app
heroku create nananom-farms-backend

# Add PHP buildpack
heroku buildpacks:set heroku/php

# Set environment variables
heroku config:set JWT_SECRET=your-production-secret
heroku config:set ADMIN_USERNAME=admin
heroku config:set ADMIN_PASSWORD=secure-password

# Deploy
git push heroku main
```

2. **Frontend Deployment:**
```bash
# Create Heroku app
heroku create nananom-farms-frontend

# Add Node.js buildpack
heroku buildpacks:set heroku/nodejs

# Set environment variables
heroku config:set VITE_API_BASE_URL=https://your-backend-app.herokuapp.com

# Deploy
git push heroku main
```

## ⚙️ Environment Configuration

### Production Environment Variables

#### Backend (.env)
```env
# Database Configuration
DB_HOST=localhost
DB_NAME=nananom_farms
DB_USER=nananom_user
DB_PASS=secure_database_password

# Admin Credentials (CHANGE THESE!)
ADMIN_USERNAME=your_admin_username
ADMIN_PASSWORD=your_very_secure_password

# JWT Configuration
JWT_SECRET=your-super-secret-jwt-key-change-this-in-production
JWT_EXPIRY=86400

# Application Configuration
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

# Security
CORS_ALLOWED_ORIGINS=https://your-domain.com,https://www.your-domain.com
RATE_LIMIT_ENABLED=true
RATE_LIMIT_REQUESTS=1000
RATE_LIMIT_WINDOW=3600

# Logging
LOG_LEVEL=error
LOG_FILE=/var/log/nananom-farms/app.log
```

#### Frontend (.env)
```env
# API Configuration
VITE_API_BASE_URL=https://your-domain.com/api

# Application Configuration
VITE_APP_NAME=Nananom Farms
VITE_APP_VERSION=1.0.0
VITE_APP_ENV=production

# Analytics (optional)
VITE_GOOGLE_ANALYTICS_ID=GA_MEASUREMENT_ID
```

## 🗄️ Database Setup

### SQLite (Development)
```bash
# Database is automatically created
# Located at: nananom-farms-backend/database/nananom_farms.db
```

### MySQL (Production)
```bash
# Install MySQL
sudo apt install mysql-server

# Secure installation
sudo mysql_secure_installation

# Create database and user
sudo mysql -u root -p
```

```sql
CREATE DATABASE nananom_farms;
CREATE USER 'nananom_user'@'localhost' IDENTIFIED BY 'secure_password';
GRANT ALL PRIVILEGES ON nananom_farms.* TO 'nananom_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

```bash
# Import schema
mysql -u nananom_user -p nananom_farms < nananom-farms-backend/database/schema.sql
```

### PostgreSQL (Alternative)
```bash
# Install PostgreSQL
sudo apt install postgresql postgresql-contrib

# Create database and user
sudo -u postgres psql
```

```sql
CREATE DATABASE nananom_farms;
CREATE USER nananom_user WITH PASSWORD 'secure_password';
GRANT ALL PRIVILEGES ON DATABASE nananom_farms TO nananom_user;
\q
```

## 🔒 Security Considerations

### SSL/HTTPS Setup
```bash
# Install Certbot
sudo apt install certbot python3-certbot-apache

# Get SSL certificate
sudo certbot --apache -d your-domain.com -d www.your-domain.com

# Auto-renewal
sudo crontab -e
# Add: 0 12 * * * /usr/bin/certbot renew --quiet
```

### Firewall Configuration
```bash
# Configure UFW
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable
```

### File Permissions
```bash
# Set proper permissions
sudo chown -R www-data:www-data /var/www/nananom-farms
sudo chmod -R 755 /var/www/nananom-farms
sudo chmod -R 644 /var/www/nananom-farms/nananom-farms-backend/.env
```

### Security Headers
```apache
# Add to Apache configuration
Header always set X-Frame-Options "SAMEORIGIN"
Header always set X-XSS-Protection "1; mode=block"
Header always set X-Content-Type-Options "nosniff"
Header always set Referrer-Policy "strict-origin-when-cross-origin"
Header always set Content-Security-Policy "default-src 'self'"
```

## 📊 Monitoring & Logging

### Application Logging
```php
// Add to PHP files
error_log("Application log message", 3, "/var/log/nananom-farms/app.log");
```

### Log Rotation
```bash
# Create logrotate configuration
sudo nano /etc/logrotate.d/nananom-farms
```

```
/var/log/nananom-farms/*.log {
    daily
    missingok
    rotate 30
    compress
    delaycompress
    notifempty
    create 644 www-data www-data
}
```

### Health Check Endpoint
```php
// Add to backend
// health.php
<?php
header('Content-Type: application/json');

$health = [
    'status' => 'healthy',
    'timestamp' => date('c'),
    'version' => '1.0.0',
    'database' => 'connected',
    'services' => [
        'api' => 'running',
        'database' => 'connected'
    ]
];

echo json_encode($health);
?>
```

### Monitoring Script
```bash
#!/bin/bash
# monitor.sh

# Check application health
curl -f http://localhost/health.php > /dev/null 2>&1
if [ $? -ne 0 ]; then
    echo "Application is down!" | mail -s "Nananom Farms Alert" admin@your-domain.com
fi

# Check disk space
DISK_USAGE=$(df / | tail -1 | awk '{print $5}' | sed 's/%//')
if [ $DISK_USAGE -gt 80 ]; then
    echo "Disk space is running low: ${DISK_USAGE}%" | mail -s "Disk Space Alert" admin@your-domain.com
fi
```

## 🔧 Troubleshooting

### Common Issues

#### 1. Permission Denied
```bash
# Fix file permissions
sudo chown -R www-data:www-data /var/www/nananom-farms
sudo chmod -R 755 /var/www/nananom-farms
```

#### 2. Database Connection Failed
```bash
# Check database service
sudo systemctl status mysql

# Test connection
mysql -u nananom_user -p -e "SELECT 1;"
```

#### 3. API Not Responding
```bash
# Check PHP-FPM status
sudo systemctl status php8.4-fpm

# Check Apache/Nginx logs
sudo tail -f /var/log/apache2/error.log
sudo tail -f /var/log/nginx/error.log
```

#### 4. Frontend Build Issues
```bash
# Clear npm cache
npm cache clean --force

# Reinstall dependencies
rm -rf node_modules package-lock.json
npm install
```

### Debug Mode
```bash
# Enable debug mode (development only)
export APP_DEBUG=true
export LOG_LEVEL=debug
```

### Performance Optimization
```bash
# Enable OPcache
sudo apt install php8.4-opcache
sudo systemctl restart php8.4-fpm

# Enable Gzip compression
sudo a2enmod deflate
sudo systemctl reload apache2
```

---

**For more information, see the main [README.md](README.md) and [API_DOCUMENTATION.md](API_DOCUMENTATION.md) files.**