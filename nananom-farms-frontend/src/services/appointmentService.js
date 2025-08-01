// src/services/appointmentService.js
import { get, post, put } from './api';

// Admin, Support Agent - Get all bookings
export const getAllBookings = async (filters = {}) => {
  try {
    const queryParams = new URLSearchParams();
    
    if (filters.status) queryParams.append('status', filters.status);
    if (filters.assigned_to) queryParams.append('assigned_to', filters.assigned_to);
    if (filters.date_from) queryParams.append('date_from', filters.date_from);
    if (filters.date_to) queryParams.append('date_to', filters.date_to);
    if (filters.page) queryParams.append('page', filters.page);
    if (filters.limit) queryParams.append('limit', filters.limit);
    
    const endpoint = `/api/bookings${queryParams.toString() ? `?${queryParams.toString()}` : ''}`;
    const data = await get(endpoint);
    return data;
  } catch (error) {
    console.error('Error fetching all bookings:', error.message);
    throw error;
  }
};

// Public - Book a new appointment (no authentication required)
export const createBooking = async (bookingData) => {
  try {
    const data = await post('/api/bookings', bookingData, {}, false); // false = no auth required
    return data;
  } catch (error) {
    console.error('Error creating booking:', error.message);
    throw error;
  }
};

// Admin, Support Agent - Update a booking (e.g., change status, assign agent)
export const updateBooking = async (bookingId, bookingData) => {
  try {
    const data = await put('/api/bookings', {
      booking_id: bookingId,
      ...bookingData
    });
    return data;
  } catch (error) {
    console.error('Error updating booking:', error.message);
    throw error;
  }
};

// Get booking statistics for dashboard
export const getBookingStats = async () => {
  try {
    const data = await get('/api/bookings?limit=1000'); // Get all for stats
    const bookings = data.bookings || [];
    
    const stats = {
      total: bookings.length,
      pending: bookings.filter(b => b.status === 'pending').length,
      confirmed: bookings.filter(b => b.status === 'confirmed').length,
      completed: bookings.filter(b => b.status === 'completed').length,
      cancelled: bookings.filter(b => b.status === 'cancelled').length
    };
    
    return stats;
  } catch (error) {
    console.error('Error fetching booking stats:', error.message);
    throw error;
  }
};

// Get upcoming bookings
export const getUpcomingBookings = async (days = 7) => {
  try {
    const today = new Date().toISOString().split('T')[0];
    const futureDate = new Date();
    futureDate.setDate(futureDate.getDate() + days);
    const futureDateStr = futureDate.toISOString().split('T')[0];
    
    const data = await get(`/api/bookings?date_from=${today}&date_to=${futureDateStr}&status=confirmed,pending`);
    return data;
  } catch (error) {
    console.error('Error fetching upcoming bookings:', error.message);
    throw error;
  }
};