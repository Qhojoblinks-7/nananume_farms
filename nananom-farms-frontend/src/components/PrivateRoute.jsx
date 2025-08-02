// src/components/PrivateRoute.jsx
import React, { useEffect, useState } from 'react';
import { Navigate, Outlet } from 'react-router-dom';
import { isAuthenticated, getUserRole } from '../services/auth';

const PrivateRoute = ({ roles = [] }) => {
  const [isLoading, setIsLoading] = useState(true);
  const [isAuth, setIsAuth] = useState(false);
  const [userRole, setUserRole] = useState(null);

  console.log('🛡️ PrivateRoute rendered:', { roles, isLoading, isAuth, userRole });

  useEffect(() => {
    console.log('🔄 PrivateRoute useEffect triggered');
    
    // Add a small delay to ensure localStorage is properly read
    const timer = setTimeout(() => {
      console.log('⏱️ PrivateRoute timer fired, checking authentication...');
      
      const authenticated = isAuthenticated();
      const role = getUserRole();
      
      console.log('🔍 PrivateRoute auth check results:', { authenticated, role, requiredRoles: roles });
      
      setIsAuth(authenticated);
      setUserRole(role);
      setIsLoading(false);
      
      console.log('✅ PrivateRoute state updated:', { authenticated, role, isLoading: false });
    }, 50);

    return () => {
      console.log('🧹 PrivateRoute cleanup - clearing timer');
      clearTimeout(timer);
    };
  }, [roles]);

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