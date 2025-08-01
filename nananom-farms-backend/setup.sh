#!/bin/bash

echo "Nananom Farms Backend Setup"
echo "=========================="

# Check if PHP is installed
if ! command -v php &> /dev/null; then
    echo "❌ PHP is not installed. Please install PHP 7.4 or higher."
    exit 1
fi

# Check if Composer is installed
if ! command -v composer &> /dev/null; then
    echo "❌ Composer is not installed. Please install Composer."
    exit 1
fi

echo "✅ PHP and Composer found"

# Install dependencies
echo "Installing dependencies..."
composer install

# Create .env file if it doesn't exist
if [ ! -f .env ]; then
    echo "Creating .env file from template..."
    cp .env.example .env
    echo "✅ .env file created. Please edit it with your database credentials."
else
    echo "✅ .env file already exists"
fi

# Check if MySQL is available
if command -v mysql &> /dev/null; then
    echo "✅ MySQL client found"
    echo "To set up the database, run:"
    echo "mysql -u root -p < database/schema.sql"
else
    echo "⚠️  MySQL client not found. Please import database/schema.sql manually."
fi

echo ""
echo "Setup complete! Next steps:"
echo "1. Edit .env file with your database credentials"
echo "2. Import database/schema.sql to create tables"
echo "3. Configure your web server to point to this directory"
echo "4. Test the API using test_api.php"
echo ""
echo "Default admin credentials:"
echo "Username: admin"
echo "Password: admin123"
echo ""
echo "⚠️  IMPORTANT: Change these credentials in production!"