import React, { useState, useEffect } from 'react';
import { useAppSelector } from '../../hooks/useAppSelector';
import { selectUserName, selectUserRole, logoutAsync } from '../../store/slices/authSlice';
import { useAppDispatch } from '../../hooks/useAppDispatch';
import { 
  getAdminDashboardStats, 
  getRecentEnquiries, 
  getRecentBookings, 
  getAllAgents,
  getSystemStatus 
} from '../../services/adminService';

const AdminDashboard = () => {
  const dispatch = useAppDispatch();
  const userName = useAppSelector(selectUserName);
  const userRole = useAppSelector(selectUserRole);

  // State for dashboard data
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [dashboardStats, setDashboardStats] = useState({
    totalEnquiries: 0,
    pendingEnquiries: 0,
    totalBookings: 0,
    upcomingBookings: 0,
    totalAgents: 0,
    activeAgents: 0,
    systemStatus: 'Checking...'
  });
  const [recentEnquiries, setRecentEnquiries] = useState([]);
  const [recentBookings, setRecentBookings] = useState([]);
  const [agents, setAgents] = useState([]);

  // Fetch dashboard data on component mount
  useEffect(() => {
    const fetchDashboardData = async () => {
      console.log('🏠 AdminDashboard: Fetching dashboard data...');
      setLoading(true);
      setError(null);

      try {
        // Fetch all data in parallel
        const [stats, enquiries, bookings, agentsList, systemStatus] = await Promise.all([
          getAdminDashboardStats(),
          getRecentEnquiries(5),
          getRecentBookings(5),
          getAllAgents(),
          getSystemStatus()
        ]);

        setDashboardStats({
          ...stats,
          systemStatus: systemStatus.status
        });
        setRecentEnquiries(enquiries);
        setRecentBookings(bookings);
        setAgents(agentsList);

        console.log('✅ AdminDashboard: Data loaded successfully');
      } catch (err) {
        console.error('❌ AdminDashboard: Error loading data:', err);
        setError('Failed to load dashboard data. Please try again.');
      } finally {
        setLoading(false);
      }
    };

    fetchDashboardData();
  }, []);

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

        {/* Loading State */}
        {loading && (
          <div className="bg-white rounded-lg shadow-lg p-6 mb-6">
            <div className="flex items-center justify-center">
              <div className="w-8 h-8 border-4 border-[#086920] border-t-transparent rounded-full animate-spin mr-4"></div>
              <p className="text-[#2F2F2F]">Loading dashboard data...</p>
            </div>
          </div>
        )}

        {/* Error State */}
        {error && (
          <div className="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6">
            <strong className="font-bold">Error:</strong> {error}
            <button 
              onClick={() => window.location.reload()} 
              className="ml-4 text-red-800 underline hover:text-red-900"
            >
              Retry
            </button>
          </div>
        )}

        {/* Quick Stats */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
          <div className="bg-white rounded-lg shadow-lg p-6">
            <h3 className="text-lg font-semibold text-[#2F2F2F] mb-2">Total Enquiries</h3>
            <p className="text-3xl font-bold text-[#086920]">{dashboardStats.totalEnquiries}</p>
            <p className="text-sm text-gray-600">Pending: {dashboardStats.pendingEnquiries}</p>
          </div>
          
          <div className="bg-white rounded-lg shadow-lg p-6">
            <h3 className="text-lg font-semibold text-[#2F2F2F] mb-2">Total Bookings</h3>
            <p className="text-3xl font-bold text-[#086920]">{dashboardStats.totalBookings}</p>
            <p className="text-sm text-gray-600">Upcoming: {dashboardStats.upcomingBookings}</p>
          </div>
          
          <div className="bg-white rounded-lg shadow-lg p-6">
            <h3 className="text-lg font-semibold text-[#2F2F2F] mb-2">Support Agents</h3>
            <p className="text-3xl font-bold text-[#086920]">{dashboardStats.totalAgents}</p>
            <p className="text-sm text-gray-600">Active: {dashboardStats.activeAgents}</p>
          </div>
          
          <div className="bg-white rounded-lg shadow-lg p-6">
            <h3 className="text-lg font-semibold text-[#2F2F2F] mb-2">System Status</h3>
            <p className={`text-3xl font-bold ${dashboardStats.systemStatus === 'Online' ? 'text-green-600' : 'text-red-600'}`}>
              {dashboardStats.systemStatus}
            </p>
            <p className="text-sm text-gray-600">
              {dashboardStats.systemStatus === 'Online' ? 'All systems operational' : 'System unavailable'}
            </p>
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

        {/* Recent Enquiries */}
        <div className="bg-white rounded-lg shadow-lg p-6 mb-6">
          <h2 className="text-2xl font-bold text-[#2F2F2F] mb-4">Recent Enquiries</h2>
          {recentEnquiries.length > 0 ? (
            <div className="space-y-4">
              {recentEnquiries.map((enquiry, index) => (
                <div key={enquiry.id || index} className="flex items-center p-4 bg-gray-50 rounded-lg">
                  <div className={`w-3 h-3 rounded-full mr-4 ${
                    enquiry.status === 'pending' ? 'bg-yellow-500' :
                    enquiry.status === 'in_progress' ? 'bg-blue-500' :
                    enquiry.status === 'resolved' ? 'bg-green-500' : 'bg-gray-500'
                  }`}></div>
                  <div className="flex-1">
                    <p className="font-medium text-[#2F2F2F]">{enquiry.subject}</p>
                    <p className="text-sm text-gray-600">
                      {enquiry.full_name} • {enquiry.email} • {new Date(enquiry.created_at).toLocaleDateString()}
                    </p>
                  </div>
                  <span className={`px-2 py-1 rounded-full text-xs font-medium ${
                    enquiry.status === 'pending' ? 'bg-yellow-100 text-yellow-800' :
                    enquiry.status === 'in_progress' ? 'bg-blue-100 text-blue-800' :
                    enquiry.status === 'resolved' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'
                  }`}>
                    {enquiry.status}
                  </span>
                </div>
              ))}
            </div>
          ) : (
            <p className="text-gray-600 text-center py-4">No recent enquiries</p>
          )}
        </div>

        {/* Recent Bookings */}
        <div className="bg-white rounded-lg shadow-lg p-6 mb-6">
          <h2 className="text-2xl font-bold text-[#2F2F2F] mb-4">Recent Bookings</h2>
          {recentBookings.length > 0 ? (
            <div className="space-y-4">
              {recentBookings.map((booking, index) => (
                <div key={booking.id || index} className="flex items-center p-4 bg-gray-50 rounded-lg">
                  <div className={`w-3 h-3 rounded-full mr-4 ${
                    booking.status === 'pending' ? 'bg-yellow-500' :
                    booking.status === 'confirmed' ? 'bg-green-500' :
                    booking.status === 'completed' ? 'bg-blue-500' : 'bg-gray-500'
                  }`}></div>
                  <div className="flex-1">
                    <p className="font-medium text-[#2F2F2F]">{booking.service_type}</p>
                    <p className="text-sm text-gray-600">
                      {booking.full_name} • {booking.booking_date} • {booking.contact}
                    </p>
                  </div>
                  <span className={`px-2 py-1 rounded-full text-xs font-medium ${
                    booking.status === 'pending' ? 'bg-yellow-100 text-yellow-800' :
                    booking.status === 'confirmed' ? 'bg-green-100 text-green-800' :
                    booking.status === 'completed' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800'
                  }`}>
                    {booking.status}
                  </span>
                </div>
              ))}
            </div>
          ) : (
            <p className="text-gray-600 text-center py-4">No recent bookings</p>
          )}
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
            
            {recentEnquiries.length > 0 && (
              <div className="flex items-center p-4 bg-gray-50 rounded-lg">
                <div className="w-3 h-3 bg-blue-500 rounded-full mr-4"></div>
                <div>
                  <p className="font-medium text-[#2F2F2F]">New enquiry received</p>
                  <p className="text-sm text-gray-600">
                    {recentEnquiries[0].subject} from {recentEnquiries[0].full_name}
                  </p>
                </div>
              </div>
            )}
            
            {recentBookings.length > 0 && (
              <div className="flex items-center p-4 bg-gray-50 rounded-lg">
                <div className="w-3 h-3 bg-purple-500 rounded-full mr-4"></div>
                <div>
                  <p className="font-medium text-[#2F2F2F]">New booking created</p>
                  <p className="text-sm text-gray-600">
                    {recentBookings[0].service_type} for {recentBookings[0].full_name}
                  </p>
                </div>
              </div>
            )}
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