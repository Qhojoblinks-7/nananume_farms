# Redux with Hooks - Implementation Guide

## 🎯 Overview

This guide explains how to use Redux Toolkit with React hooks for state management in the Nananom Farms frontend.

## 📁 Redux Structure

```
src/store/
├── index.js              # Main store configuration
└── slices/
    ├── authSlice.js      # Authentication state
    ├── enquirySlice.js   # Enquiries management
    ├── bookingSlice.js   # Bookings management
    ├── agentSlice.js     # Agent management
    ├── dashboardSlice.js # Dashboard data
    └── uiSlice.js        # UI state (modals, notifications, etc.)
```

## 🔧 Setup

### 1. Store Configuration

The main store is configured in `src/store/index.js`:

```javascript
import { configureStore } from '@reduxjs/toolkit';
import authReducer from './slices/authSlice';
import enquiryReducer from './slices/enquirySlice';
// ... other reducers

export const store = configureStore({
  reducer: {
    auth: authReducer,
    enquiries: enquiryReducer,
    bookings: bookingReducer,
    agents: agentReducer,
    dashboard: dashboardReducer,
    ui: uiReducer,
  },
});
```

### 2. Provider Setup

The app is wrapped with Redux Provider in `src/main.jsx`:

```javascript
import { Provider } from 'react-redux';
import store from './store/index.js';

ReactDOM.createRoot(document.getElementById('root')).render(
  <Provider store={store}>
    <App />
  </Provider>
);
```

## 🎣 Using Redux Hooks

### Custom Hooks

We provide custom hooks for better TypeScript support:

```javascript
// src/hooks/useAppDispatch.js
import { useDispatch } from 'react-redux';
export const useAppDispatch = () => useDispatch();

// src/hooks/useAppSelector.js
import { useSelector } from 'react-redux';
export const useAppSelector = useSelector;
```

### Basic Usage

```javascript
import { useAppDispatch, useAppSelector } from '../hooks/useAppDispatch';
import { selectIsAuthenticated, loginAdminAsync } from '../store/slices/authSlice';

const MyComponent = () => {
  const dispatch = useAppDispatch();
  const isAuthenticated = useAppSelector(selectIsAuthenticated);
  
  const handleLogin = () => {
    dispatch(loginAdminAsync({ username: 'admin', password: 'admin123' }));
  };
  
  return (
    <div>
      <p>Authenticated: {isAuthenticated ? 'Yes' : 'No'}</p>
      <button onClick={handleLogin}>Login</button>
    </div>
  );
};
```

## 📦 Available Slices

### 1. Auth Slice (`authSlice.js`)

**State:**
- `token` - JWT token
- `role` - User role (admin/agent)
- `userId` - User ID
- `userName` - Username
- `isAuthenticated` - Authentication status
- `loading` - Loading state
- `error` - Error messages
- `success` - Success messages

**Actions:**
```javascript
// Async thunks
loginAdminAsync(credentials)
loginAgentAsync(credentials)
registerAgentAsync(agentData)
updateAdminPasswordAsync(passwordData)
logoutAsync()

// Regular actions
clearError()
clearSuccess()
setCredentials(data)
```

**Selectors:**
```javascript
selectIsAuthenticated(state)
selectUserRole(state)
selectUserId(state)
selectUserName(state)
selectToken(state)
selectAuthLoading(state)
selectAuthError(state)
selectAuthSuccess(state)
```

### 2. Enquiry Slice (`enquirySlice.js`)

**State:**
- `enquiries` - Array of enquiries
- `stats` - Enquiry statistics
- `pagination` - Pagination info
- `filters` - Current filters
- `loading` - Loading state
- `error` - Error messages
- `success` - Success messages

**Actions:**
```javascript
// Async thunks
fetchEnquiriesAsync(filters)
createEnquiryAsync(enquiryData)
updateEnquiryAsync({ enquiryId, updates })
fetchEnquiryStatsAsync()

// Regular actions
setFilters(filters)
clearFilters()
setPage(page)
```

**Selectors:**
```javascript
selectEnquiries(state)
selectEnquiryStats(state)
selectEnquiryPagination(state)
selectEnquiryFilters(state)
selectEnquiryLoading(state)
selectEnquiryError(state)
selectEnquirySuccess(state)
```

### 3. Booking Slice (`bookingSlice.js`)

**State:**
- `bookings` - Array of bookings
- `upcomingBookings` - Upcoming bookings
- `stats` - Booking statistics
- `pagination` - Pagination info
- `filters` - Current filters
- `loading` - Loading state
- `error` - Error messages
- `success` - Success messages

**Actions:**
```javascript
// Async thunks
fetchBookingsAsync(filters)
createBookingAsync(bookingData)
updateBookingAsync({ bookingId, updates })
fetchBookingStatsAsync()
fetchUpcomingBookingsAsync(days)

// Regular actions
setFilters(filters)
clearFilters()
setPage(page)
```

**Selectors:**
```javascript
selectBookings(state)
selectUpcomingBookings(state)
selectBookingStats(state)
selectBookingPagination(state)
selectBookingFilters(state)
selectBookingLoading(state)
selectBookingError(state)
selectBookingSuccess(state)
```

### 4. Agent Slice (`agentSlice.js`)

**State:**
- `agents` - Array of agents
- `stats` - Agent statistics
- `pagination` - Pagination info
- `filters` - Current filters
- `loading` - Loading state
- `error` - Error messages
- `success` - Success messages

**Actions:**
```javascript
// Async thunks
fetchAgentsAsync(filters)
updateAgentAsync({ agentId, updates })
deleteAgentAsync(agentId)
fetchAgentStatsAsync()

// Regular actions
setFilters(filters)
clearFilters()
setPage(page)
```

**Selectors:**
```javascript
selectAgents(state)
selectAgentStats(state)
selectAgentPagination(state)
selectAgentFilters(state)
selectAgentLoading(state)
selectAgentError(state)
selectAgentSuccess(state)
```

### 5. Dashboard Slice (`dashboardSlice.js`)

**State:**
- `dashboardData` - Combined dashboard data
- `recentActivity` - Recent activities
- `loading` - Loading state
- `error` - Error messages

**Actions:**
```javascript
// Async thunks
fetchDashboardDataAsync()
fetchRecentActivityAsync()

// Regular actions
updateDashboardData(data)
addRecentActivity(activity)
```

**Selectors:**
```javascript
selectDashboardData(state)
selectRecentActivity(state)
selectDashboardLoading(state)
selectDashboardError(state)
selectEnquiryStats(state)
selectBookingStats(state)
selectAgentStats(state)
selectUpcomingBookings(state)
selectDashboardSummary(state)
```

### 6. UI Slice (`uiSlice.js`)

**State:**
- `modals` - Modal states
- `notifications` - Notification array
- `sidebar` - Sidebar state
- `theme` - Theme configuration
- `loading` - Loading states
- `forms` - Form states

**Actions:**
```javascript
// Modal actions
openModal(modalName)
closeModal(modalName)
closeAllModals()

// Notification actions
addNotification(notification)
removeNotification(id)
clearNotifications()

// Sidebar actions
toggleSidebar()
openSidebar()
closeSidebar()
setActiveTab(tab)

// Theme actions
toggleTheme()
setTheme(mode)
setPrimaryColor(color)

// Loading actions
setLoading({ section, isLoading })
setGlobalLoading(isLoading)

// Form actions
setFormSubmitting({ formName, isSubmitting })
setFormErrors({ formName, errors })
clearFormErrors(formName)
```

**Selectors:**
```javascript
selectModals(state)
selectNotifications(state)
selectSidebar(state)
selectTheme(state)
selectLoading(state)
selectForms(state)
selectIsModalOpen(modalName)(state)
selectIsSidebarOpen(state)
selectActiveTab(state)
selectThemeMode(state)
selectPrimaryColor(state)
selectIsLoading(section)(state)
selectIsFormSubmitting(formName)(state)
selectFormErrors(formName)(state)
```

## 🔄 Async Actions with createAsyncThunk

All API calls are handled using `createAsyncThunk`:

```javascript
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
```

**Usage:**
```javascript
const dispatch = useAppDispatch();

// Dispatch async action
dispatch(fetchEnquiriesAsync({ status: 'pending' }));

// Handle loading states
const loading = useAppSelector(selectEnquiryLoading);
const error = useAppSelector(selectEnquiryError);
```

## 📝 Component Examples

### Authentication Component

```javascript
import React, { useState } from 'react';
import { useAppDispatch, useAppSelector } from '../hooks/useAppDispatch';
import { 
  loginAdminAsync, 
  selectAuthLoading, 
  selectAuthError,
  clearError 
} from '../store/slices/authSlice';

const LoginForm = () => {
  const dispatch = useAppDispatch();
  const loading = useAppSelector(selectAuthLoading);
  const error = useAppSelector(selectAuthError);
  
  const [credentials, setCredentials] = useState({
    username: '',
    password: ''
  });
  
  const handleSubmit = (e) => {
    e.preventDefault();
    dispatch(clearError());
    dispatch(loginAdminAsync(credentials));
  };
  
  return (
    <form onSubmit={handleSubmit}>
      {error && <div className="error">{error}</div>}
      <input
        type="text"
        value={credentials.username}
        onChange={(e) => setCredentials({
          ...credentials,
          username: e.target.value
        })}
        placeholder="Username"
      />
      <input
        type="password"
        value={credentials.password}
        onChange={(e) => setCredentials({
          ...credentials,
          password: e.target.value
        })}
        placeholder="Password"
      />
      <button type="submit" disabled={loading}>
        {loading ? 'Logging in...' : 'Login'}
      </button>
    </form>
  );
};
```

### Enquiry List Component

```javascript
import React, { useEffect } from 'react';
import { useAppDispatch, useAppSelector } from '../hooks/useAppDispatch';
import { 
  fetchEnquiriesAsync,
  selectEnquiries,
  selectEnquiryLoading,
  selectEnquiryFilters,
  setFilters
} from '../store/slices/enquirySlice';

const EnquiryList = () => {
  const dispatch = useAppDispatch();
  const enquiries = useAppSelector(selectEnquiries);
  const loading = useAppSelector(selectEnquiryLoading);
  const filters = useAppSelector(selectEnquiryFilters);
  
  useEffect(() => {
    dispatch(fetchEnquiriesAsync(filters));
  }, [dispatch, filters]);
  
  const handleFilterChange = (newFilters) => {
    dispatch(setFilters(newFilters));
  };
  
  if (loading) return <div>Loading...</div>;
  
  return (
    <div>
      <div className="filters">
        <select
          value={filters.status}
          onChange={(e) => handleFilterChange({ status: e.target.value })}
        >
          <option value="">All Status</option>
          <option value="pending">Pending</option>
          <option value="in_progress">In Progress</option>
          <option value="resolved">Resolved</option>
        </select>
      </div>
      
      <div className="enquiries">
        {enquiries.map(enquiry => (
          <div key={enquiry.id} className="enquiry-item">
            <h3>{enquiry.subject}</h3>
            <p>{enquiry.message}</p>
            <span className={`status ${enquiry.status}`}>
              {enquiry.status}
            </span>
          </div>
        ))}
      </div>
    </div>
  );
};
```

### Notification Component

```javascript
import React from 'react';
import { useAppSelector } from '../hooks/useAppDispatch';
import { 
  selectNotifications,
  removeNotification 
} from '../store/slices/uiSlice';

const NotificationList = () => {
  const dispatch = useAppDispatch();
  const notifications = useAppSelector(selectNotifications);
  
  const handleRemove = (id) => {
    dispatch(removeNotification(id));
  };
  
  return (
    <div className="notifications">
      {notifications.map(notification => (
        <div
          key={notification.id}
          className={`notification ${notification.type}`}
        >
          <div className="notification-content">
            <h4>{notification.title}</h4>
            <p>{notification.message}</p>
          </div>
          <button
            onClick={() => handleRemove(notification.id)}
            className="close-btn"
          >
            ×
          </button>
        </div>
      ))}
    </div>
  );
};
```

## 🛠️ Best Practices

### 1. Use Selectors

Always use selectors to access state:

```javascript
// ✅ Good
const enquiries = useAppSelector(selectEnquiries);

// ❌ Bad
const enquiries = useAppSelector(state => state.enquiries.enquiries);
```

### 2. Handle Loading States

Always handle loading and error states:

```javascript
const loading = useAppSelector(selectEnquiryLoading);
const error = useAppSelector(selectEnquiryError);

if (loading) return <div>Loading...</div>;
if (error) return <div>Error: {error}</div>;
```

### 3. Clear Errors

Clear errors when starting new actions:

```javascript
const handleSubmit = () => {
  dispatch(clearError()); // Clear previous errors
  dispatch(createEnquiryAsync(data));
};
```

### 4. Use useEffect for Data Fetching

```javascript
useEffect(() => {
  if (isAuthenticated) {
    dispatch(fetchEnquiriesAsync());
  }
}, [dispatch, isAuthenticated]);
```

### 5. Optimize Re-renders

Use specific selectors to avoid unnecessary re-renders:

```javascript
// ✅ Good - only re-renders when enquiries change
const enquiries = useAppSelector(selectEnquiries);

// ❌ Bad - re-renders when any part of state changes
const state = useAppSelector(state => state);
```

## 🔍 Debugging

### Redux DevTools

The store is configured with Redux DevTools support. Install the browser extension to debug:

1. Install Redux DevTools Extension
2. Open browser DevTools
3. Go to Redux tab
4. View state changes and actions

### State Logging

Add logging to see state changes:

```javascript
const state = useAppSelector(state => state);
console.log('Current state:', state);
```

## 📚 Additional Resources

- [Redux Toolkit Documentation](https://redux-toolkit.js.org/)
- [React Redux Hooks](https://react-redux.js.org/api/hooks)
- [Redux DevTools](https://github.com/reduxjs/redux-devtools)

## 🚀 Example Component

See `src/components/ReduxExample.jsx` for a complete example of using Redux with hooks.