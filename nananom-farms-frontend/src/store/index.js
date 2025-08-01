import { configureStore } from '@reduxjs/toolkit';
import authReducer from './slices/authSlice';
import enquiryReducer from './slices/enquirySlice';
import bookingReducer from './slices/bookingSlice';
import agentReducer from './slices/agentSlice';
import dashboardReducer from './slices/dashboardSlice';
import uiReducer from './slices/uiSlice';

export const store = configureStore({
  reducer: {
    auth: authReducer,
    enquiries: enquiryReducer,
    bookings: bookingReducer,
    agents: agentReducer,
    dashboard: dashboardReducer,
    ui: uiReducer,
  },
  middleware: (getDefaultMiddleware) =>
    getDefaultMiddleware({
      serializableCheck: {
        // Ignore these action types
        ignoredActions: ['persist/PERSIST'],
      },
    }),
});

export default store;