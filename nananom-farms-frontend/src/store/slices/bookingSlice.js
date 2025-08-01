import { createSlice, createAsyncThunk } from '@reduxjs/toolkit';
import { 
  getAllBookings, 
  createBooking, 
  updateBooking, 
  getBookingStats,
  getUpcomingBookings
} from '../../services/appointmentService';

// Async thunks
export const fetchBookingsAsync = createAsyncThunk(
  'bookings/fetchBookings',
  async (filters = {}, { rejectWithValue }) => {
    try {
      const response = await getAllBookings(filters);
      return response;
    } catch (error) {
      return rejectWithValue(error.message);
    }
  }
);

export const createBookingAsync = createAsyncThunk(
  'bookings/createBooking',
  async (bookingData, { rejectWithValue }) => {
    try {
      const response = await createBooking(bookingData);
      return response;
    } catch (error) {
      return rejectWithValue(error.message);
    }
  }
);

export const updateBookingAsync = createAsyncThunk(
  'bookings/updateBooking',
  async ({ bookingId, updates }, { rejectWithValue }) => {
    try {
      const response = await updateBooking(bookingId, updates);
      return { bookingId, updates, response };
    } catch (error) {
      return rejectWithValue(error.message);
    }
  }
);

export const fetchBookingStatsAsync = createAsyncThunk(
  'bookings/fetchStats',
  async (_, { rejectWithValue }) => {
    try {
      const response = await getBookingStats();
      return response;
    } catch (error) {
      return rejectWithValue(error.message);
    }
  }
);

export const fetchUpcomingBookingsAsync = createAsyncThunk(
  'bookings/fetchUpcoming',
  async (days = 7, { rejectWithValue }) => {
    try {
      const response = await getUpcomingBookings(days);
      return response;
    } catch (error) {
      return rejectWithValue(error.message);
    }
  }
);

const initialState = {
  bookings: [],
  upcomingBookings: [],
  stats: {
    total: 0,
    pending: 0,
    confirmed: 0,
    completed: 0,
    cancelled: 0,
  },
  pagination: {
    page: 1,
    limit: 10,
    total: 0,
    pages: 0,
  },
  loading: false,
  error: null,
  success: null,
  filters: {
    status: '',
    assigned_to: '',
    date_from: '',
    date_to: '',
  },
};

const bookingSlice = createSlice({
  name: 'bookings',
  initialState,
  reducers: {
    clearError: (state) => {
      state.error = null;
    },
    clearSuccess: (state) => {
      state.success = null;
    },
    setFilters: (state, action) => {
      state.filters = { ...state.filters, ...action.payload };
    },
    clearFilters: (state) => {
      state.filters = {
        status: '',
        assigned_to: '',
        date_from: '',
        date_to: '',
      };
    },
    setPage: (state, action) => {
      state.pagination.page = action.payload;
    },
  },
  extraReducers: (builder) => {
    builder
      // Fetch Bookings
      .addCase(fetchBookingsAsync.pending, (state) => {
        state.loading = true;
        state.error = null;
      })
      .addCase(fetchBookingsAsync.fulfilled, (state, action) => {
        state.loading = false;
        state.bookings = action.payload.bookings || [];
        state.pagination = action.payload.pagination || state.pagination;
      })
      .addCase(fetchBookingsAsync.rejected, (state, action) => {
        state.loading = false;
        state.error = action.payload;
      })
      
      // Create Booking
      .addCase(createBookingAsync.pending, (state) => {
        state.loading = true;
        state.error = null;
      })
      .addCase(createBookingAsync.fulfilled, (state, action) => {
        state.loading = false;
        state.success = action.payload.message || 'Booking submitted successfully';
      })
      .addCase(createBookingAsync.rejected, (state, action) => {
        state.loading = false;
        state.error = action.payload;
      })
      
      // Update Booking
      .addCase(updateBookingAsync.pending, (state) => {
        state.loading = true;
        state.error = null;
      })
      .addCase(updateBookingAsync.fulfilled, (state, action) => {
        state.loading = false;
        const { bookingId, updates } = action.payload;
        
        // Update the booking in the list
        const index = state.bookings.findIndex(b => b.id === bookingId);
        if (index !== -1) {
          state.bookings[index] = { ...state.bookings[index], ...updates };
        }
        
        state.success = action.payload.response.message || 'Booking updated successfully';
      })
      .addCase(updateBookingAsync.rejected, (state, action) => {
        state.loading = false;
        state.error = action.payload;
      })
      
      // Fetch Stats
      .addCase(fetchBookingStatsAsync.pending, (state) => {
        state.loading = true;
        state.error = null;
      })
      .addCase(fetchBookingStatsAsync.fulfilled, (state, action) => {
        state.loading = false;
        state.stats = action.payload;
      })
      .addCase(fetchBookingStatsAsync.rejected, (state, action) => {
        state.loading = false;
        state.error = action.payload;
      })
      
      // Fetch Upcoming Bookings
      .addCase(fetchUpcomingBookingsAsync.pending, (state) => {
        state.loading = true;
        state.error = null;
      })
      .addCase(fetchUpcomingBookingsAsync.fulfilled, (state, action) => {
        state.loading = false;
        state.upcomingBookings = action.payload.bookings || [];
      })
      .addCase(fetchUpcomingBookingsAsync.rejected, (state, action) => {
        state.loading = false;
        state.error = action.payload;
      });
  },
});

export const { 
  clearError, 
  clearSuccess, 
  setFilters, 
  clearFilters, 
  setPage 
} = bookingSlice.actions;

// Selectors
export const selectBookings = (state) => state.bookings.bookings;
export const selectUpcomingBookings = (state) => state.bookings.upcomingBookings;
export const selectBookingStats = (state) => state.bookings.stats;
export const selectBookingPagination = (state) => state.bookings.pagination;
export const selectBookingFilters = (state) => state.bookings.filters;
export const selectBookingLoading = (state) => state.bookings.loading;
export const selectBookingError = (state) => state.bookings.error;
export const selectBookingSuccess = (state) => state.bookings.success;

export default bookingSlice.reducer;