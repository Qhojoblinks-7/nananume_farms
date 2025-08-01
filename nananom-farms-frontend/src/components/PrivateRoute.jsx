// src/components/PrivateRoute.jsx
import React from 'react';
import { Navigate, Outlet } from 'react-router-dom';
import { isAuthenticated, getUserRole } from '../services/auth';

const PrivateRoute = ({ roles = [] }) => {
  if (!isAuthenticated()) {
    // User is not authenticated, redirect to login page
    return <Navigate to="/login" replace />;
  }

  // If roles are specified, check if user has required role
  if (roles.length > 0) {
    const userRole = getUserRole();
    if (!userRole || !roles.includes(userRole)) {
      // User doesn't have required role, redirect to appropriate dashboard or home
      if (userRole === 'admin') {
        return <Navigate to="/admin/dashboard" replace />;
      } else if (userRole === 'agent') {
        return <Navigate to="/agent/dashboard" replace />;
      } else {
        return <Navigate to="/" replace />;
      }
    }
  }

  // User is authenticated and has required role, render the protected component
  return <Outlet />;
};

export default PrivateRoute;