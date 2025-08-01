import { createSlice, createAsyncThunk } from '@reduxjs/toolkit';
import { 
  getAllAgents, 
  updateAgent, 
  deleteAgent, 
  getAgentStats 
} from '../../services/agentService';

// Async thunks
export const fetchAgentsAsync = createAsyncThunk(
  'agents/fetchAgents',
  async (filters = {}, { rejectWithValue }) => {
    try {
      const response = await getAllAgents(filters);
      return response;
    } catch (error) {
      return rejectWithValue(error.message);
    }
  }
);

export const updateAgentAsync = createAsyncThunk(
  'agents/updateAgent',
  async ({ agentId, updates }, { rejectWithValue }) => {
    try {
      const response = await updateAgent(agentId, updates);
      return { agentId, updates, response };
    } catch (error) {
      return rejectWithValue(error.message);
    }
  }
);

export const deleteAgentAsync = createAsyncThunk(
  'agents/deleteAgent',
  async (agentId, { rejectWithValue }) => {
    try {
      const response = await deleteAgent(agentId);
      return { agentId, response };
    } catch (error) {
      return rejectWithValue(error.message);
    }
  }
);

export const fetchAgentStatsAsync = createAsyncThunk(
  'agents/fetchStats',
  async (_, { rejectWithValue }) => {
    try {
      const response = await getAgentStats();
      return response;
    } catch (error) {
      return rejectWithValue(error.message);
    }
  }
);

const initialState = {
  agents: [],
  stats: {
    total: 0,
    active: 0,
    inactive: 0,
    byRegion: {},
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
    is_active: '',
    region: '',
  },
};

const agentSlice = createSlice({
  name: 'agents',
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
        is_active: '',
        region: '',
      };
    },
    setPage: (state, action) => {
      state.pagination.page = action.payload;
    },
  },
  extraReducers: (builder) => {
    builder
      // Fetch Agents
      .addCase(fetchAgentsAsync.pending, (state) => {
        state.loading = true;
        state.error = null;
      })
      .addCase(fetchAgentsAsync.fulfilled, (state, action) => {
        state.loading = false;
        state.agents = action.payload.agents || [];
        state.pagination = action.payload.pagination || state.pagination;
      })
      .addCase(fetchAgentsAsync.rejected, (state, action) => {
        state.loading = false;
        state.error = action.payload;
      })
      
      // Update Agent
      .addCase(updateAgentAsync.pending, (state) => {
        state.loading = true;
        state.error = null;
      })
      .addCase(updateAgentAsync.fulfilled, (state, action) => {
        state.loading = false;
        const { agentId, updates } = action.payload;
        
        // Update the agent in the list
        const index = state.agents.findIndex(a => a.id === agentId);
        if (index !== -1) {
          state.agents[index] = { ...state.agents[index], ...updates };
        }
        
        state.success = action.payload.response.message || 'Agent updated successfully';
      })
      .addCase(updateAgentAsync.rejected, (state, action) => {
        state.loading = false;
        state.error = action.payload;
      })
      
      // Delete Agent
      .addCase(deleteAgentAsync.pending, (state) => {
        state.loading = true;
        state.error = null;
      })
      .addCase(deleteAgentAsync.fulfilled, (state, action) => {
        state.loading = false;
        const { agentId } = action.payload;
        
        // Remove the agent from the list
        state.agents = state.agents.filter(a => a.id !== agentId);
        
        state.success = action.payload.response.message || 'Agent deleted successfully';
      })
      .addCase(deleteAgentAsync.rejected, (state, action) => {
        state.loading = false;
        state.error = action.payload;
      })
      
      // Fetch Stats
      .addCase(fetchAgentStatsAsync.pending, (state) => {
        state.loading = true;
        state.error = null;
      })
      .addCase(fetchAgentStatsAsync.fulfilled, (state, action) => {
        state.loading = false;
        state.stats = action.payload;
      })
      .addCase(fetchAgentStatsAsync.rejected, (state, action) => {
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
} = agentSlice.actions;

// Selectors
export const selectAgents = (state) => state.agents.agents;
export const selectAgentStats = (state) => state.agents.stats;
export const selectAgentPagination = (state) => state.agents.pagination;
export const selectAgentFilters = (state) => state.agents.filters;
export const selectAgentLoading = (state) => state.agents.loading;
export const selectAgentError = (state) => state.agents.error;
export const selectAgentSuccess = (state) => state.agents.success;

export default agentSlice.reducer;