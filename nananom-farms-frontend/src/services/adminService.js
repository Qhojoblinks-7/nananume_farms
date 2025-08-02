// src/services/adminService.js
import { get } from './api';

/**
 * Get admin dashboard statistics
 * @returns {Promise<object>} Dashboard stats including enquiries, bookings, agents, etc.
 */
export const getAdminDashboardStats = async () => {
  try {
    console.log('📊 Fetching admin dashboard stats...');
    
    // Fetch all required data in parallel
    const [enquiries, bookings, agents] = await Promise.all([
      get('/api/enquiries'),
      get('/api/bookings'),
      get('/api/agents')
    ]);

    // Calculate statistics
    const stats = {
      totalEnquiries: enquiries?.length || 0,
      pendingEnquiries: enquiries?.filter(e => e.status === 'pending').length || 0,
      totalBookings: bookings?.length || 0,
      upcomingBookings: bookings?.filter(b => b.status === 'confirmed').length || 0,
      totalAgents: agents?.agents?.length || 0,
      activeAgents: agents?.agents?.filter(a => a.is_active).length || 0,
      systemStatus: 'Online'
    };

    console.log('✅ Admin dashboard stats calculated:', stats);
    return stats;
  } catch (error) {
    console.error('❌ Error fetching admin dashboard stats:', error);
    throw error;
  }
};

/**
 * Get recent enquiries for admin dashboard
 * @param {number} limit - Number of recent enquiries to fetch
 * @returns {Promise<Array>} Recent enquiries
 */
export const getRecentEnquiries = async (limit = 5) => {
  try {
    console.log('📝 Fetching recent enquiries...');
    const enquiries = await get('/api/enquiries');
    
    // Sort by creation date and take the most recent
    const recent = enquiries
      ?.sort((a, b) => new Date(b.created_at) - new Date(a.created_at))
      ?.slice(0, limit) || [];
    
    console.log('✅ Recent enquiries fetched:', recent.length);
    return recent;
  } catch (error) {
    console.error('❌ Error fetching recent enquiries:', error);
    throw error;
  }
};

/**
 * Get recent bookings for admin dashboard
 * @param {number} limit - Number of recent bookings to fetch
 * @returns {Promise<Array>} Recent bookings
 */
export const getRecentBookings = async (limit = 5) => {
  try {
    console.log('📅 Fetching recent bookings...');
    const bookings = await get('/api/bookings');
    
    // Sort by booking date and take the most recent
    const recent = bookings
      ?.sort((a, b) => new Date(b.booking_date) - new Date(a.booking_date))
      ?.slice(0, limit) || [];
    
    console.log('✅ Recent bookings fetched:', recent.length);
    return recent;
  } catch (error) {
    console.error('❌ Error fetching recent bookings:', error);
    throw error;
  }
};

/**
 * Get all support agents for admin dashboard
 * @returns {Promise<Array>} All support agents
 */
export const getAllAgents = async () => {
  try {
    console.log('👥 Fetching all agents...');
    const response = await get('/api/agents');
    
    console.log('✅ Agents fetched:', response?.agents?.length || 0);
    return response?.agents || [];
  } catch (error) {
    console.error('❌ Error fetching agents:', error);
    throw error;
  }
};

/**
 * Get system status and health check
 * @returns {Promise<object>} System status information
 */
export const getSystemStatus = async () => {
  try {
    console.log('🔍 Checking system status...');
    
    // Try to fetch a simple endpoint to check if system is online
    await get('/api/enquiries');
    
    const status = {
      status: 'Online',
      timestamp: new Date().toISOString(),
      message: 'All systems operational'
    };
    
    console.log('✅ System status check passed');
    return status;
  } catch (error) {
    console.error('❌ System status check failed:', error);
    return {
      status: 'Offline',
      timestamp: new Date().toISOString(),
      message: 'System unavailable'
    };
  }
};