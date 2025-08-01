import React, { useEffect } from 'react';
import { useAppSelector } from '../../hooks/useAppDispatch';
import { selectUserName, selectUserRole, logoutAsync } from '../../store/slices/authSlice';
import { useAppDispatch } from '../../hooks/useAppDispatch';

const AdminDashboard = () => {
  const dispatch = useAppDispatch();
  const userName = useAppSelector(selectUserName);
  const userRole = useAppSelector(selectUserRole);

  const handleLogout = () => {
    dispatch(logoutAsync());
  };

  return (
    <div className="min-h-screen bg-[#DAD7CD] p-6">
      <div className="max-w-6xl mx-auto">
        {/* Header */}
        <div className="bg-white rounded-lg shadow-lg p-6 mb-6">
          <div className="flex justify-between items-center">
            <div>
              <h1 className="text-3xl font-bold text-[#086920]">Admin Dashboard</h1>
              <p className="text-[#2F2F2F] mt-2">
                Welcome back, <span className="font-semibold">{userName}</span>! 
                You are logged in as <span className="font-semibold text-[#086920]">{userRole}</span>
              </p>
            </div>
            <button
              onClick={handleLogout}
              className="bg-red-500 text-white px-4 py-2 rounded-lg hover:bg-red-600 transition-colors"
            >
              Logout
            </button>
          </div>
        </div>

        {/* Quick Stats */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
          <div className="bg-white rounded-lg shadow-lg p-6">
            <h3 className="text-lg font-semibold text-[#2F2F2F] mb-2">Total Enquiries</h3>
            <p className="text-3xl font-bold text-[#086920]">0</p>
            <p className="text-sm text-gray-600">Pending: 0</p>
          </div>
          
          <div className="bg-white rounded-lg shadow-lg p-6">
            <h3 className="text-lg font-semibold text-[#2F2F2F] mb-2">Total Bookings</h3>
            <p className="text-3xl font-bold text-[#086920]">0</p>
            <p className="text-sm text-gray-600">Upcoming: 0</p>
          </div>
          
          <div className="bg-white rounded-lg shadow-lg p-6">
            <h3 className="text-lg font-semibold text-[#2F2F2F] mb-2">Support Agents</h3>
            <p className="text-3xl font-bold text-[#086920]">0</p>
            <p className="text-sm text-gray-600">Active: 0</p>
          </div>
          
          <div className="bg-white rounded-lg shadow-lg p-6">
            <h3 className="text-lg font-semibold text-[#2F2F2F] mb-2">System Status</h3>
            <p className="text-3xl font-bold text-green-600">Online</p>
            <p className="text-sm text-gray-600">All systems operational</p>
          </div>
        </div>

        {/* Quick Actions */}
        <div className="bg-white rounded-lg shadow-lg p-6 mb-6">
          <h2 className="text-2xl font-bold text-[#2F2F2F] mb-4">Quick Actions</h2>
          <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
            <button className="bg-[#086920] text-white p-4 rounded-lg hover:bg-[#065018] transition-colors">
              <h3 className="font-semibold mb-2">View Enquiries</h3>
              <p className="text-sm opacity-90">Manage customer enquiries</p>
            </button>
            
            <button className="bg-[#086920] text-white p-4 rounded-lg hover:bg-[#065018] transition-colors">
              <h3 className="font-semibold mb-2">View Bookings</h3>
              <p className="text-sm opacity-90">Manage service bookings</p>
            </button>
            
            <button className="bg-[#086920] text-white p-4 rounded-lg hover:bg-[#065018] transition-colors">
              <h3 className="font-semibold mb-2">Manage Agents</h3>
              <p className="text-sm opacity-90">Add, edit, or remove agents</p>
            </button>
          </div>
        </div>

        {/* Recent Activity */}
        <div className="bg-white rounded-lg shadow-lg p-6">
          <h2 className="text-2xl font-bold text-[#2F2F2F] mb-4">Recent Activity</h2>
          <div className="space-y-4">
            <div className="flex items-center p-4 bg-gray-50 rounded-lg">
              <div className="w-3 h-3 bg-green-500 rounded-full mr-4"></div>
              <div>
                <p className="font-medium text-[#2F2F2F]">Admin login successful</p>
                <p className="text-sm text-gray-600">Just now</p>
              </div>
            </div>
            
            <div className="flex items-center p-4 bg-gray-50 rounded-lg">
              <div className="w-3 h-3 bg-blue-500 rounded-full mr-4"></div>
              <div>
                <p className="font-medium text-[#2F2F2F]">System initialized</p>
                <p className="text-sm text-gray-600">Dashboard loaded</p>
              </div>
            </div>
          </div>
        </div>

        {/* Navigation Links */}
        <div className="mt-6 text-center">
          <a 
            href="/" 
            className="text-[#086920] hover:text-[#FFB703] underline transition-colors"
          >
            ← Back to Home
          </a>
        </div>
      </div>
    </div>
  );
};

export default AdminDashboard;