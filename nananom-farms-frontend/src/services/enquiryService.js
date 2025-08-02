// src/services/enquiryService.js
import { get, post, put } from './api';

// Admin, Support Agent - Get all enquiries
export const getAllEnquiries = async (filters = {}) => {
  try {
    const queryParams = new URLSearchParams();
    
    if (filters.status) queryParams.append('status', filters.status);
    if (filters.assigned_to) queryParams.append('assigned_to', filters.assigned_to);
    if (filters.page) queryParams.append('page', filters.page);
    if (filters.limit) queryParams.append('limit', filters.limit);
    
    const endpoint = `/api/enquiries${queryParams.toString() ? `?${queryParams.toString()}` : ''}`;
    const data = await get(endpoint);
    return data;
  } catch (error) {
    console.error('Error fetching all enquiries:', error.message);
    throw error;
  }
};

// Public - Submit a customer enquiry (no authentication required)
export const createEnquiry = async (enquiryData) => {
  try {
    const data = await post('/api/enquiries', enquiryData, {}, false); // false = no auth required
    return data;
  } catch (error) {
    console.error('Error creating enquiry:', error.message);
    throw error;
  }
};

// Get enquiries for calendar view
export const getEnquiriesForCalendar = async (startDate, endDate) => {
  try {
    const queryParams = new URLSearchParams({
      date_from: startDate,
      date_to: endDate
    });
    
    const endpoint = `/api/enquiries?${queryParams.toString()}`;
    const data = await get(endpoint);
    return data;
  } catch (error) {
    console.error('Error fetching calendar enquiries:', error.message);
    throw error;
  }
};

// Admin, Support Agent - Update an enquiry (e.g., change status, assign agent)
export const updateEnquiry = async (enquiryId, enquiryData) => {
  try {
    const data = await put('/api/enquiries', {
      enquiry_id: enquiryId,
      ...enquiryData
    });
    return data;
  } catch (error) {
    console.error('Error updating enquiry:', error.message);
    throw error;
  }
};

// Get enquiry statistics for dashboard
export const getEnquiryStats = async () => {
  try {
    const data = await get('/api/enquiries?limit=1000'); // Get all for stats
    const enquiries = data.enquiries || [];
    
    const stats = {
      total: enquiries.length,
      pending: enquiries.filter(e => e.status === 'pending').length,
      in_progress: enquiries.filter(e => e.status === 'in_progress').length,
      resolved: enquiries.filter(e => e.status === 'resolved').length,
      closed: enquiries.filter(e => e.status === 'closed').length
    };
    
    return stats;
  } catch (error) {
    console.error('Error fetching enquiry stats:', error.message);
    throw error;
  }
};