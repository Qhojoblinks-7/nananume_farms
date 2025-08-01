import { createSlice } from '@reduxjs/toolkit';

const initialState = {
  // Modal states
  modals: {
    loginModal: false,
    registerModal: false,
    enquiryModal: false,
    bookingModal: false,
    confirmModal: false,
    editModal: false,
  },
  
  // Notification states
  notifications: [],
  
  // Sidebar states
  sidebar: {
    isOpen: false,
    activeTab: 'dashboard',
  },
  
  // Theme and appearance
  theme: {
    mode: 'light', // 'light' or 'dark'
    primaryColor: '#10B981', // Green color
  },
  
  // Loading states for different sections
  loading: {
    global: false,
    auth: false,
    enquiries: false,
    bookings: false,
    agents: false,
    dashboard: false,
  },
  
  // Form states
  forms: {
    enquiryForm: {
      isSubmitting: false,
      errors: {},
    },
    bookingForm: {
      isSubmitting: false,
      errors: {},
    },
    loginForm: {
      isSubmitting: false,
      errors: {},
    },
    registerForm: {
      isSubmitting: false,
      errors: {},
    },
  },
};

const uiSlice = createSlice({
  name: 'ui',
  initialState,
  reducers: {
    // Modal actions
    openModal: (state, action) => {
      const modalName = action.payload;
      if (state.modals.hasOwnProperty(modalName)) {
        state.modals[modalName] = true;
      }
    },
    
    closeModal: (state, action) => {
      const modalName = action.payload;
      if (state.modals.hasOwnProperty(modalName)) {
        state.modals[modalName] = false;
      }
    },
    
    closeAllModals: (state) => {
      Object.keys(state.modals).forEach(key => {
        state.modals[key] = false;
      });
    },
    
    // Notification actions
    addNotification: (state, action) => {
      const notification = {
        id: Date.now(),
        timestamp: new Date().toISOString(),
        ...action.payload,
      };
      state.notifications.unshift(notification);
      
      // Keep only the latest 10 notifications
      if (state.notifications.length > 10) {
        state.notifications = state.notifications.slice(0, 10);
      }
    },
    
    removeNotification: (state, action) => {
      const notificationId = action.payload;
      state.notifications = state.notifications.filter(
        notification => notification.id !== notificationId
      );
    },
    
    clearNotifications: (state) => {
      state.notifications = [];
    },
    
    // Sidebar actions
    toggleSidebar: (state) => {
      state.sidebar.isOpen = !state.sidebar.isOpen;
    },
    
    openSidebar: (state) => {
      state.sidebar.isOpen = true;
    },
    
    closeSidebar: (state) => {
      state.sidebar.isOpen = false;
    },
    
    setActiveTab: (state, action) => {
      state.sidebar.activeTab = action.payload;
    },
    
    // Theme actions
    toggleTheme: (state) => {
      state.theme.mode = state.theme.mode === 'light' ? 'dark' : 'light';
    },
    
    setTheme: (state, action) => {
      state.theme.mode = action.payload;
    },
    
    setPrimaryColor: (state, action) => {
      state.theme.primaryColor = action.payload;
    },
    
    // Loading actions
    setLoading: (state, action) => {
      const { section, isLoading } = action.payload;
      if (state.loading.hasOwnProperty(section)) {
        state.loading[section] = isLoading;
      }
    },
    
    setGlobalLoading: (state, action) => {
      state.loading.global = action.payload;
    },
    
    // Form actions
    setFormSubmitting: (state, action) => {
      const { formName, isSubmitting } = action.payload;
      if (state.forms[formName]) {
        state.forms[formName].isSubmitting = isSubmitting;
      }
    },
    
    setFormErrors: (state, action) => {
      const { formName, errors } = action.payload;
      if (state.forms[formName]) {
        state.forms[formName].errors = errors;
      }
    },
    
    clearFormErrors: (state, action) => {
      const formName = action.payload;
      if (state.forms[formName]) {
        state.forms[formName].errors = {};
      }
    },
    
    // Reset UI state
    resetUI: (state) => {
      state.modals = initialState.modals;
      state.notifications = [];
      state.sidebar = initialState.sidebar;
      state.loading = initialState.loading;
      state.forms = initialState.forms;
    },
  },
});

export const {
  // Modal actions
  openModal,
  closeModal,
  closeAllModals,
  
  // Notification actions
  addNotification,
  removeNotification,
  clearNotifications,
  
  // Sidebar actions
  toggleSidebar,
  openSidebar,
  closeSidebar,
  setActiveTab,
  
  // Theme actions
  toggleTheme,
  setTheme,
  setPrimaryColor,
  
  // Loading actions
  setLoading,
  setGlobalLoading,
  
  // Form actions
  setFormSubmitting,
  setFormErrors,
  clearFormErrors,
  
  // Reset action
  resetUI,
} = uiSlice.actions;

// Selectors
export const selectModals = (state) => state.ui.modals;
export const selectNotifications = (state) => state.ui.notifications;
export const selectSidebar = (state) => state.ui.sidebar;
export const selectTheme = (state) => state.ui.theme;
export const selectLoading = (state) => state.ui.loading;
export const selectForms = (state) => state.ui.forms;

// Specific selectors
export const selectIsModalOpen = (modalName) => (state) => state.ui.modals[modalName];
export const selectIsSidebarOpen = (state) => state.ui.sidebar.isOpen;
export const selectActiveTab = (state) => state.ui.sidebar.activeTab;
export const selectThemeMode = (state) => state.ui.theme.mode;
export const selectPrimaryColor = (state) => state.ui.theme.primaryColor;
export const selectIsLoading = (section) => (state) => state.ui.loading[section];
export const selectIsFormSubmitting = (formName) => (state) => state.ui.forms[formName]?.isSubmitting;
export const selectFormErrors = (formName) => (state) => state.ui.forms[formName]?.errors;

export default uiSlice.reducer;