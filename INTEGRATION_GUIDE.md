# Nananom Farms - Frontend & Backend Integration Guide

## 🎯 Overview

This guide explains how to connect the React frontend with the PHP backend for the Nananom Farms project.

## 📁 Project Structure

```
nananom-farms/
├── nananom-farms-backend/     # PHP Backend API
│   ├── api/                   # API endpoints
│   ├── config/               # Database configuration
│   ├── database/             # MySQL schema
│   ├── includes/             # Authentication helpers
│   └── setup.sh              # Backend setup script
└── nananom-farms-frontend/    # React Frontend
    ├── src/
    │   ├── services/         # API service layer
    │   ├── components/       # React components
    │   └── pages/           # Page components
    └── setup.sh             # Frontend setup script
```

## 🚀 Quick Setup

### 1. Backend Setup

```bash
cd nananom-farms-backend
./setup.sh
```

**Manual steps:**
1. Install dependencies: `composer install`
2. Create `.env` file: `cp .env.example .env`
3. Configure database credentials in `.env`
4. Import database: `mysql -u root -p < database/schema.sql`

### 2. Frontend Setup

```bash
cd nananom-farms-frontend
./setup.sh
```

**Manual steps:**
1. Install dependencies: `npm install`
2. Create `.env` file: `cp .env.example .env`
3. Configure backend URL in `.env`

## 🔧 Configuration

### Backend Environment (.env)

```env
# Database Configuration
DB_HOST=localhost
DB_NAME=nananom_farms
DB_USER=root
DB_PASSWORD=your_password

# Admin Credentials
ADMIN_USERNAME=admin
ADMIN_PASSWORD=admin123

# JWT Secret
JWT_SECRET=your_super_secret_jwt_key
```

### Frontend Environment (.env)

```env
VITE_API_BASE_URL=http://localhost:8000/nananom-farms-backend
VITE_APP_NAME=Nananom Farms
```

## 🔌 API Integration

### Authentication Flow

#### Admin Login
```javascript
import { loginAdmin } from '../services/auth';

const handleAdminLogin = async (credentials) => {
  try {
    const response = await loginAdmin({
      username: 'admin',
      password: 'admin123'
    });
    // Redirect to admin dashboard
  } catch (error) {
    // Handle error
  }
};
```

#### Agent Registration
```javascript
import { registerAgent } from '../services/auth';

const handleAgentRegistration = async (agentData) => {
  try {
    const response = await registerAgent({
      username: 'agent1',
      email: 'agent@example.com',
      password: 'password123',
      full_name: 'John Doe',
      phone: '+1234567890',
      region: 'North Region'
    });
    // Redirect to agent dashboard
  } catch (error) {
    // Handle error
  }
};
```

#### Agent Login
```javascript
import { loginAgent } from '../services/auth';

const handleAgentLogin = async (credentials) => {
  try {
    const response = await loginAgent({
      username: 'agent1',
      password: 'password123'
    });
    // Redirect to agent dashboard
  } catch (error) {
    // Handle error
  }
};
```

### Enquiries Management

#### Create Enquiry (Public)
```javascript
import { createEnquiry } from '../services/enquiryService';

const handleSubmitEnquiry = async (enquiryData) => {
  try {
    const response = await createEnquiry({
      full_name: 'John Doe',
      email: 'john@example.com',
      contact: '+1234567890',
      company: 'ABC Corp',
      subject: 'General Inquiry',
      message: 'I would like to know more about your services.'
    });
    // Show success message
  } catch (error) {
    // Handle error
  }
};
```

#### Get Enquiries (Admin/Agent)
```javascript
import { getAllEnquiries } from '../services/enquiryService';

const fetchEnquiries = async () => {
  try {
    const response = await getAllEnquiries({
      status: 'pending',
      page: 1,
      limit: 10
    });
    // Update state with enquiries
  } catch (error) {
    // Handle error
  }
};
```

#### Update Enquiry (Admin/Agent)
```javascript
import { updateEnquiry } from '../services/enquiryService';

const handleUpdateEnquiry = async (enquiryId, updates) => {
  try {
    const response = await updateEnquiry(enquiryId, {
      status: 'in_progress',
      assigned_to: 2
    });
    // Show success message
  } catch (error) {
    // Handle error
  }
};
```

### Bookings Management

#### Create Booking (Public)
```javascript
import { createBooking } from '../services/appointmentService';

const handleSubmitBooking = async (bookingData) => {
  try {
    const response = await createBooking({
      full_name: 'Jane Smith',
      email: 'jane@example.com',
      contact: '+1234567890',
      company: 'XYZ Ltd',
      service_type: 'Farm Consultation',
      booking_date: '2024-01-15',
      booking_time: '14:00:00',
      additional_notes: 'Prefer afternoon appointment'
    });
    // Show success message
  } catch (error) {
    // Handle error
  }
};
```

#### Get Bookings (Admin/Agent)
```javascript
import { getAllBookings } from '../services/appointmentService';

const fetchBookings = async () => {
  try {
    const response = await getAllBookings({
      status: 'pending',
      date_from: '2024-01-01',
      page: 1,
      limit: 10
    });
    // Update state with bookings
  } catch (error) {
    // Handle error
  }
};
```

### Agent Management (Admin Only)

#### Get Agents
```javascript
import { getAllAgents } from '../services/agentService';

const fetchAgents = async () => {
  try {
    const response = await getAllAgents({
      is_active: true,
      region: 'North',
      page: 1,
      limit: 10
    });
    // Update state with agents
  } catch (error) {
    // Handle error
  }
};
```

#### Update Agent
```javascript
import { updateAgent } from '../services/agentService';

const handleUpdateAgent = async (agentId, updates) => {
  try {
    const response = await updateAgent(agentId, {
      is_active: false,
      region: 'South Region'
    });
    // Show success message
  } catch (error) {
    // Handle error
  }
};
```

## 🛡️ Security Features

### JWT Authentication
- Tokens expire after 24 hours
- Automatic logout on token expiration
- Role-based access control

### Role-Based Access
- **Admin**: Full access to all features
- **Agent**: Access to assigned enquiries/bookings
- **Public**: Can submit enquiries and bookings

### CORS Configuration
- Backend configured to accept requests from frontend
- Secure headers implemented

## 🚀 Running the Application

### Development Mode

1. **Start Backend:**
   ```bash
   cd nananom-farms-backend
   # Use your preferred PHP server
   php -S localhost:8000
   ```

2. **Start Frontend:**
   ```bash
   cd nananom-farms-frontend
   npm run dev
   ```

3. **Access Application:**
   - Frontend: http://localhost:5173
   - Backend API: http://localhost:8000/nananom-farms-backend

### Production Deployment

1. **Backend:**
   - Deploy to web server (Apache/Nginx)
   - Configure environment variables
   - Set up SSL certificate

2. **Frontend:**
   - Build: `npm run build`
   - Deploy to static hosting
   - Update API URL in environment

## 🔍 Testing

### Backend Testing
```bash
cd nananom-farms-backend
php test_api.php
```

### Frontend Testing
```bash
cd nananom-farms-frontend
npm run lint
npm test  # if tests are configured
```

## 🐛 Troubleshooting

### Common Issues

1. **CORS Errors:**
   - Ensure backend CORS headers are configured
   - Check API URL in frontend environment

2. **Authentication Errors:**
   - Verify JWT secret is configured
   - Check token expiration
   - Ensure proper role assignments

3. **Database Connection:**
   - Verify database credentials
   - Check MySQL service is running
   - Ensure database schema is imported

4. **API Endpoint Errors:**
   - Verify backend is running
   - Check endpoint URLs match
   - Review request/response format

### Debug Mode

Enable debug mode in backend `.env`:
```env
APP_DEBUG=true
```

## 📚 Additional Resources

- [Backend API Documentation](./nananom-farms-backend/README.md)
- [Frontend Component Documentation](./nananom-farms-frontend/README.md)
- [Database Schema](./nananom-farms-backend/database/schema.sql)

## 🤝 Support

For issues or questions:
1. Check the troubleshooting section
2. Review API documentation
3. Check browser console for errors
4. Verify backend logs for issues