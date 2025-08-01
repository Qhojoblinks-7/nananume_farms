import { createSlice, createAsyncThunk } from '@reduxjs/toolkit';
import { 
  getAllEnquiries, 
  createEnquiry, 
  updateEnquiry, 
  getEnquiryStats 
} from '../../services/enquiryService';

// Async thunks
export const fetchEnquiriesAsync = createAsyncThunk(
  'enquiries/fetchEnquiries',
  async (filters = {}, { rejectWithValue }) => {
    try {
      const response = await getAllEnquiries(filters);
      return response;
    } catch (error) {
      return rejectWithValue(error.message);
    }
  }
);

export const createEnquiryAsync = createAsyncThunk(
  'enquiries/createEnquiry',
  async (enquiryData, { rejectWithValue }) => {
    try {
      const response = await createEnquiry(enquiryData);
      return response;
    } catch (error) {
      return rejectWithValue(error.message);
    }
  }
);

export const updateEnquiryAsync = createAsyncThunk(
  'enquiries/updateEnquiry',
  async ({ enquiryId, updates }, { rejectWithValue }) => {
    try {
      const response = await updateEnquiry(enquiryId, updates);
      return { enquiryId, updates, response };
    } catch (error) {
      return rejectWithValue(error.message);
    }
  }
);

export const fetchEnquiryStatsAsync = createAsyncThunk(
  'enquiries/fetchStats',
  async (_, { rejectWithValue }) => {
    try {
      const response = await getEnquiryStats();
      return response;
    } catch (error) {
      return rejectWithValue(error.message);
    }
  }
);

const initialState = {
  enquiries: [],
  stats: {
    total: 0,
    pending: 0,
    in_progress: 0,
    resolved: 0,
    closed: 0,
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
  },
};

const enquirySlice = createSlice({
  name: 'enquiries',
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
      };
    },
    setPage: (state, action) => {
      state.pagination.page = action.payload;
    },
  },
  extraReducers: (builder) => {
    builder
      // Fetch Enquiries
      .addCase(fetchEnquiriesAsync.pending, (state) => {
        state.loading = true;
        state.error = null;
      })
      .addCase(fetchEnquiriesAsync.fulfilled, (state, action) => {
        state.loading = false;
        state.enquiries = action.payload.enquiries || [];
        state.pagination = action.payload.pagination || state.pagination;
      })
      .addCase(fetchEnquiriesAsync.rejected, (state, action) => {
        state.loading = false;
        state.error = action.payload;
      })
      
      // Create Enquiry
      .addCase(createEnquiryAsync.pending, (state) => {
        state.loading = true;
        state.error = null;
      })
      .addCase(createEnquiryAsync.fulfilled, (state, action) => {
        state.loading = false;
        state.success = action.payload.message || 'Enquiry submitted successfully';
        // Optionally refresh the enquiries list
        // You might want to add the new enquiry to the list or refresh
      })
      .addCase(createEnquiryAsync.rejected, (state, action) => {
        state.loading = false;
        state.error = action.payload;
      })
      
      // Update Enquiry
      .addCase(updateEnquiryAsync.pending, (state) => {
        state.loading = true;
        state.error = null;
      })
      .addCase(updateEnquiryAsync.fulfilled, (state, action) => {
        state.loading = false;
        const { enquiryId, updates } = action.payload;
        
        // Update the enquiry in the list
        const index = state.enquiries.findIndex(e => e.id === enquiryId);
        if (index !== -1) {
          state.enquiries[index] = { ...state.enquiries[index], ...updates };
        }
        
        state.success = action.payload.response.message || 'Enquiry updated successfully';
      })
      .addCase(updateEnquiryAsync.rejected, (state, action) => {
        state.loading = false;
        state.error = action.payload;
      })
      
      // Fetch Stats
      .addCase(fetchEnquiryStatsAsync.pending, (state) => {
        state.loading = true;
        state.error = null;
      })
      .addCase(fetchEnquiryStatsAsync.fulfilled, (state, action) => {
        state.loading = false;
        state.stats = action.payload;
      })
      .addCase(fetchEnquiryStatsAsync.rejected, (state, action) => {
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
} = enquirySlice.actions;

// Selectors
export const selectEnquiries = (state) => state.enquiries.enquiries;
export const selectEnquiryStats = (state) => state.enquiries.stats;
export const selectEnquiryPagination = (state) => state.enquiries.pagination;
export const selectEnquiryFilters = (state) => state.enquiries.filters;
export const selectEnquiryLoading = (state) => state.enquiries.loading;
export const selectEnquiryError = (state) => state.enquiries.error;
export const selectEnquirySuccess = (state) => state.enquiries.success;

export default enquirySlice.reducer;