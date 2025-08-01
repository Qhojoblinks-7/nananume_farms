#!/bin/bash

echo "Nananom Farms Frontend Setup"
echo "============================"

# Check if Node.js is installed
if ! command -v node &> /dev/null; then
    echo "❌ Node.js is not installed. Please install Node.js 16 or higher."
    exit 1
fi

# Check if npm is installed
if ! command -v npm &> /dev/null; then
    echo "❌ npm is not installed. Please install npm."
    exit 1
fi

echo "✅ Node.js and npm found"

# Install dependencies
echo "Installing dependencies..."
npm install

# Create .env file if it doesn't exist
if [ ! -f .env ]; then
    echo "Creating .env file from template..."
    cp .env.example .env
    echo "✅ .env file created. Please edit it with your backend API URL."
else
    echo "✅ .env file already exists"
fi

echo ""
echo "Setup complete! Next steps:"
echo "1. Edit .env file with your backend API URL"
echo "2. Start the development server: npm run dev"
echo "3. Make sure your backend is running on the configured URL"
echo ""
echo "Default backend URL: http://localhost:8000/nananom-farms-backend"
echo "Frontend will run on: http://localhost:5173"