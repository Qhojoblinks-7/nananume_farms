// src/components/PrivateRoute.jsx
import React from 'react';
import { Navigate, Outlet } from 'react-router-dom';
import { isAuthenticated, getUserRole } from '../services/auth';

const PrivateRoute = ({ roles = [] }) => {
  console.log('🛡️ PrivateRoute rendered:', { roles });

  // Check authentication immediately
  const authenticated = isAuthenticated();
  const userRole = getUserRole();
  
  console.log('🔍 PrivateRoute auth check results:', { authenticated, userRole, requiredRoles: roles });

  if (!authenticated) {
    console.log('🚫 PrivateRoute: User not authenticated, redirecting to login');
    // User is not authenticated, redirect to login page
    return <Navigate to="/login" replace />;
  }

  // If roles are specified, check if user has required role
  if (roles.length > 0) {
    console.log('🔐 PrivateRoute: Checking role requirements:', { userRole, requiredRoles: roles });
    
    if (!userRole || !roles.includes(userRole)) {
      console.log('❌ PrivateRoute: User role not authorized:', { userRole, requiredRoles: roles });
      
      // User doesn't have required role, redirect to appropriate dashboard or home
      if (userRole === 'admin') {
        console.log('🔄 PrivateRoute: Redirecting admin to admin dashboard');
        return <Navigate to="/admin/dashboard" replace />;
      } else if (userRole === 'agent') {
        console.log('🔄 PrivateRoute: Redirecting agent to agent dashboard');
        return <Navigate to="/agent/dashboard" replace />;
      } else {
        console.log('🔄 PrivateRoute: Redirecting unknown role to home');
        return <Navigate to="/" replace />;
      }
    }
  }

  console.log('✅ PrivateRoute: User authenticated and authorized, rendering protected component');
  // User is authenticated and has required role, render the protected component
  return <Outlet />;
};

export default PrivateRoute;