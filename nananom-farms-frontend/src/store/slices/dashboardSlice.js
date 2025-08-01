import { createSlice, createAsyncThunk } from '@reduxjs/toolkit';
import { 
  getDashboardData, 
  getRecentActivity 
} from '../../services/dashboardService';

// Async thunks
export const fetchDashboardDataAsync = createAsyncThunk(
  'dashboard/fetchDashboardData',
  async (_, { rejectWithValue }) => {
    try {
      const response = await getDashboardData();
      return response;
    } catch (error) {
      return rejectWithValue(error.message);
    }
  }
);

export const fetchRecentActivityAsync = createAsyncThunk(
  'dashboard/fetchRecentActivity',
  async (_, { rejectWithValue }) => {
    try {
      const response = await getRecentActivity();
      return response;
    } catch (error) {
      return rejectWithValue(error.message);
    }
  }
);

const initialState = {
  dashboardData: {
    enquiries: {
      total: 0,
      pending: 0,
      in_progress: 0,
      resolved: 0,
      closed: 0,
    },
    bookings: {
      total: 0,
      pending: 0,
      confirmed: 0,
      completed: 0,
      cancelled: 0,
    },
    agents: {
      total: 0,
      active: 0,
      inactive: 0,
      byRegion: {},
    },
    upcomingBookings: [],
    summary: {
      totalEnquiries: 0,
      totalBookings: 0,
      totalAgents: 0,
      pendingEnquiries: 0,
      pendingBookings: 0,
      upcomingBookings: 0,
    },
  },
  recentActivity: [],
  loading: false,
  error: null,
};

const dashboardSlice = createSlice({
  name: 'dashboard',
  initialState,
  reducers: {
    clearError: (state) => {
      state.error = null;
    },
    updateDashboardData: (state, action) => {
      state.dashboardData = { ...state.dashboardData, ...action.payload };
    },
    addRecentActivity: (state, action) => {
      state.recentActivity.unshift(action.payload);
      // Keep only the latest 20 activities
      if (state.recentActivity.length > 20) {
        state.recentActivity = state.recentActivity.slice(0, 20);
      }
    },
  },
  extraReducers: (builder) => {
    builder
      // Fetch Dashboard Data
      .addCase(fetchDashboardDataAsync.pending, (state) => {
        state.loading = true;
        state.error = null;
      })
      .addCase(fetchDashboardDataAsync.fulfilled, (state, action) => {
        state.loading = false;
        state.dashboardData = action.payload;
      })
      .addCase(fetchDashboardDataAsync.rejected, (state, action) => {
        state.loading = false;
        state.error = action.payload;
      })
      
      // Fetch Recent Activity
      .addCase(fetchRecentActivityAsync.pending, (state) => {
        state.loading = true;
        state.error = null;
      })
      .addCase(fetchRecentActivityAsync.fulfilled, (state, action) => {
        state.loading = false;
        state.recentActivity = action.payload;
      })
      .addCase(fetchRecentActivityAsync.rejected, (state, action) => {
        state.loading = false;
        state.error = action.payload;
      });
  },
});

export const { 
  clearError, 
  updateDashboardData, 
  addRecentActivity 
} = dashboardSlice.actions;

// Selectors
export const selectDashboardData = (state) => state.dashboard.dashboardData;
export const selectRecentActivity = (state) => state.dashboard.recentActivity;
export const selectDashboardLoading = (state) => state.dashboard.loading;
export const selectDashboardError = (state) => state.dashboard.error;

// Specific selectors for dashboard components
export const selectEnquiryStats = (state) => state.dashboard.dashboardData.enquiries;
export const selectBookingStats = (state) => state.dashboard.dashboardData.bookings;
export const selectAgentStats = (state) => state.dashboard.dashboardData.agents;
export const selectUpcomingBookings = (state) => state.dashboard.dashboardData.upcomingBookings;
export const selectDashboardSummary = (state) => state.dashboard.dashboardData.summary;

export default dashboardSlice.reducer;