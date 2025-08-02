import { createSlice, createAsyncThunk } from '@reduxjs/toolkit';
import { 
  loginAdmin, 
  loginAgent, 
  registerAgent, 
  logoutUser, 
  updateAdminPassword,
  getToken,
  getUserRole,
  getUserId,
  getUserName,
  isAuthenticated
} from '../../services/auth';

// Async thunks
export const loginAdminAsync = createAsyncThunk(
  'auth/loginAdmin',
  async (credentials, { rejectWithValue }) => {
    try {
      const response = await loginAdmin(credentials);
      return response;
    } catch (error) {
      return rejectWithValue(error.message);
    }
  }
);

export const loginAgentAsync = createAsyncThunk(
  'auth/loginAgent',
  async (credentials, { rejectWithValue }) => {
    console.log('🔄 loginAgentAsync thunk started');
    try {
      const response = await loginAgent(credentials);
      console.log('✅ loginAgentAsync thunk completed successfully');
      return response;
    } catch (error) {
      console.error('❌ loginAgentAsync thunk failed:', error.message);
      return rejectWithValue(error.message);
    }
  }
);

export const registerAgentAsync = createAsyncThunk(
  'auth/registerAgent',
  async (agentData, { rejectWithValue }) => {
    try {
      const response = await registerAgent(agentData);
      return response;
    } catch (error) {
      return rejectWithValue(error.message);
    }
  }
);

export const updateAdminPasswordAsync = createAsyncThunk(
  'auth/updateAdminPassword',
  async (passwordData, { rejectWithValue }) => {
    try {
      const response = await updateAdminPassword(passwordData);
      return response;
    } catch (error) {
      return rejectWithValue(error.message);
    }
  }
);

export const logoutAsync = createAsyncThunk(
  'auth/logout',
  async (_, { rejectWithValue }) => {
    try {
      await logoutUser();
      return null;
    } catch (error) {
      return rejectWithValue(error.message);
    }
  }
);

// Initialize auth state from localStorage
const initializeAuthState = () => {
  const token = getToken();
  const role = getUserRole();
  const userId = getUserId();
  const userName = getUserName();
  
  return {
    token,
    role,
    userId,
    userName,
    isAuthenticated: isAuthenticated(),
  };
};

const initialState = {
  ...initializeAuthState(),
  loading: false,
  error: null,
  success: null,
};

const authSlice = createSlice({
  name: 'auth',
  initialState,
  reducers: {
    clearError: (state) => {
      state.error = null;
    },
    clearSuccess: (state) => {
      state.success = null;
    },
    setCredentials: (state, action) => {
      const { token, role, userId, userName } = action.payload;
      state.token = token;
      state.role = role;
      state.userId = userId;
      state.userName = userName;
      state.isAuthenticated = true;
    },
  },
  extraReducers: (builder) => {
    builder
      // Login Admin
      .addCase(loginAdminAsync.pending, (state) => {
        state.loading = true;
        state.error = null;
      })
      .addCase(loginAdminAsync.fulfilled, (state, action) => {
        state.loading = false;
        state.token = action.payload.token;
        state.role = action.payload.role;
        state.userId = action.payload.userId;
        state.userName = action.payload.userName;
        state.isAuthenticated = true;
        state.success = 'Admin login successful';
      })
      .addCase(loginAdminAsync.rejected, (state, action) => {
        state.loading = false;
        state.error = action.payload;
      })
      
      // Login Agent
      .addCase(loginAgentAsync.pending, (state) => {
        console.log('⏳ loginAgentAsync.pending - setting loading to true');
        state.loading = true;
        state.error = null;
      })
      .addCase(loginAgentAsync.fulfilled, (state, action) => {
        console.log('✅ loginAgentAsync.fulfilled - updating state with:', {
          hasToken: !!action.payload.token,
          role: action.payload.role,
          userId: action.payload.userId,
          userName: action.payload.userName
        });
        
        state.loading = false;
        state.token = action.payload.token;
        state.role = action.payload.role;
        state.userId = action.payload.userId;
        state.userName = action.payload.userName;
        state.isAuthenticated = true;
        state.success = 'Agent login successful';
        
        console.log('🎯 Redux state updated, success message set');
      })
      .addCase(loginAgentAsync.rejected, (state, action) => {
        console.error('❌ loginAgentAsync.rejected - setting error:', action.payload);
        state.loading = false;
        state.error = action.payload;
      })
      
      // Register Agent
      .addCase(registerAgentAsync.pending, (state) => {
        state.loading = true;
        state.error = null;
      })
      .addCase(registerAgentAsync.fulfilled, (state, action) => {
        state.loading = false;
        if (action.payload.token) {
          // Auto-login after registration
          state.token = action.payload.token;
          state.role = action.payload.role;
          state.userId = action.payload.userId;
          state.userName = action.payload.userName;
          state.isAuthenticated = true;
          state.success = 'Registration successful and logged in';
        } else {
          state.success = 'Registration successful';
        }
      })
      .addCase(registerAgentAsync.rejected, (state, action) => {
        state.loading = false;
        state.error = action.payload;
      })
      
      // Update Admin Password
      .addCase(updateAdminPasswordAsync.pending, (state) => {
        state.loading = true;
        state.error = null;
      })
      .addCase(updateAdminPasswordAsync.fulfilled, (state, action) => {
        state.loading = false;
        state.success = action.payload.message || 'Password updated successfully';
      })
      .addCase(updateAdminPasswordAsync.rejected, (state, action) => {
        state.loading = false;
        state.error = action.payload;
      })
      
      // Logout
      .addCase(logoutAsync.pending, (state) => {
        state.loading = true;
      })
      .addCase(logoutAsync.fulfilled, (state) => {
        state.loading = false;
        state.token = null;
        state.role = null;
        state.userId = null;
        state.userName = null;
        state.isAuthenticated = false;
        state.success = 'Logged out successfully';
      })
      .addCase(logoutAsync.rejected, (state, action) => {
        state.loading = false;
        state.error = action.payload;
      });
  },
});

export const { clearError, clearSuccess, setCredentials } = authSlice.actions;

// Selectors
export const selectAuth = (state) => state.auth;
export const selectIsAuthenticated = (state) => state.auth.isAuthenticated;
export const selectUserRole = (state) => state.auth.role;
export const selectUserId = (state) => state.auth.userId;
export const selectUserName = (state) => state.auth.userName;
export const selectToken = (state) => state.auth.token;
export const selectAuthLoading = (state) => state.auth.loading;
export const selectAuthError = (state) => state.auth.error;
export const selectAuthSuccess = (state) => state.auth.success;

export default authSlice.reducer;