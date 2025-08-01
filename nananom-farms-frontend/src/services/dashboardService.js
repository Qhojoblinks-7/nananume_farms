// src/services/dashboardService.js
import { get } from './api';
import { getEnquiryStats } from './enquiryService';
import { getBookingStats, getUpcomingBookings } from './appointmentService';
import { getAgentStats } from './agentService';

// Get comprehensive dashboard data
export const getDashboardData = async () => {
  try {
    const [enquiryStats, bookingStats, agentStats, upcomingBookings] = await Promise.all([
      getEnquiryStats(),
      getBookingStats(),
      getAgentStats(),
      getUpcomingBookings(7) // Next 7 days
    ]);

    return {
      enquiries: enquiryStats,
      bookings: bookingStats,
      agents: agentStats,
      upcomingBookings: upcomingBookings.bookings || [],
      summary: {
        totalEnquiries: enquiryStats.total,
        totalBookings: bookingStats.total,
        totalAgents: agentStats.total,
        pendingEnquiries: enquiryStats.pending,
        pendingBookings: bookingStats.pending,
        upcomingBookings: upcomingBookings.bookings?.length || 0
      }
    };
  } catch (error) {
    console.error('Error fetching dashboard data:', error.message);
    throw error;
  }
};

// Get recent activity for dashboard
export const getRecentActivity = async () => {
  try {
    const [recentEnquiries, recentBookings] = await Promise.all([
      get('/api/enquiries?limit=5'),
      get('/api/bookings?limit=5')
    ]);

    const activities = [];

    // Add recent enquiries
    if (recentEnquiries.enquiries) {
      recentEnquiries.enquiries.forEach(enquiry => {
        activities.push({
          id: enquiry.id,
          type: 'enquiry',
          title: enquiry.subject,
          description: enquiry.message.substring(0, 100) + '...',
          status: enquiry.status,
          date: enquiry.created_at,
          user: enquiry.full_name
        });
      });
    }

    // Add recent bookings
    if (recentBookings.bookings) {
      recentBookings.bookings.forEach(booking => {
        activities.push({
          id: booking.id,
          type: 'booking',
          title: booking.service_type,
          description: `Booking for ${booking.booking_date}`,
          status: booking.status,
          date: booking.created_at,
          user: booking.full_name
        });
      });
    }

    // Sort by date (most recent first)
    activities.sort((a, b) => new Date(b.date) - new Date(a.date));

    return activities.slice(0, 10); // Return top 10 activities
  } catch (error) {
    console.error('Error fetching recent activity:', error.message);
    throw error;
  }
};