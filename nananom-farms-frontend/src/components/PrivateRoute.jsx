// src/components/PrivateRoute.jsx
import React, { useEffect, useState } from 'react';
import { Navigate, Outlet } from 'react-router-dom';
import { isAuthenticated, getUserRole } from '../services/auth';

const PrivateRoute = ({ roles = [] }) => {
  const [isLoading, setIsLoading] = useState(true);
  const [isAuth, setIsAuth] = useState(false);
  const [userRole, setUserRole] = useState(null);

  useEffect(() => {
    // Add a small delay to ensure localStorage is properly read
    const timer = setTimeout(() => {
      const authenticated = isAuthenticated();
      const role = getUserRole();
      
      setIsAuth(authenticated);
      setUserRole(role);
      setIsLoading(false);
    }, 50);

    return () => clearTimeout(timer);
  }, []);

  // Show loading state while checking authentication
  if (isLoading) {
    return (
      <div className="flex items-center justify-center min-h-screen bg-[#DAD7CD]">
        <div className="text-center">
          <div className="w-8 h-8 border-4 border-[#086920] border-t-transparent rounded-full animate-spin mx-auto mb-4"></div>
          <p className="text-[#2F2F2F]">Loading...</p>
        </div>
      </div>
    );
  }

  if (!isAuth) {
    // User is not authenticated, redirect to login page
    return <Navigate to="/login" replace />;
  }

  // If roles are specified, check if user has required role
  if (roles.length > 0) {
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