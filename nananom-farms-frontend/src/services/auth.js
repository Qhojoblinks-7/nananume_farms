// src/services/auth.js
import { post } from './api';

const TOKEN_KEY = 'jwtToken';
const USER_ROLE_KEY = 'userRole';
const USER_ID_KEY = 'userId';
const USER_NAME_KEY = 'userName';

/**
 * Stores the JWT token, user role, and user ID in localStorage upon successful login.
 * @param {string} token - The JWT token received from the backend.
 * @param {string} role - The role of the authenticated user (e.g., 'admin', 'agent').
 * @param {string} userId - The unique ID of the authenticated user.
 * @param {string} userName - The username of the authenticated user.
 */
export const storeAuthData = (token, role, userId, userName) => {
  localStorage.setItem(TOKEN_KEY, token);
  localStorage.setItem(USER_ROLE_KEY, role);
  localStorage.setItem(USER_ID_KEY, userId);
  localStorage.setItem(USER_NAME_KEY, userName);
  console.log('Auth data stored:', { token: token ? '***' : 'N/A', role, userId, userName });
};

/**
 * Retrieves the JWT token from localStorage.
 * @returns {string | null} The JWT token if found, otherwise null.
 */
export const getToken = () => {
  return localStorage.getItem(TOKEN_KEY);
};

/**
 * Retrieves the user role from localStorage.
 * @returns {string | null} The user role if found, otherwise null.
 */
export const getUserRole = () => {
  return localStorage.getItem(USER_ROLE_KEY);
};

/**
 * Retrieves the user ID from localStorage.
 * @returns {string | null} The user ID if found, otherwise null.
 */
export const getUserId = () => {
  return localStorage.getItem(USER_ID_KEY);
};

/**
 * Retrieves the username from localStorage.
 * @returns {string | null} The username if found, otherwise null.
 */
export const getUserName = () => {
  return localStorage.getItem(USER_NAME_KEY);
};

/**
 * Checks if a user is currently authenticated (has a token).
 * @returns {boolean} True if a token exists, false otherwise.
 */
export const isAuthenticated = () => {
  return !!getToken();
};

/**
 * Admin login - sends credentials to the backend and stores auth data on success.
 * @param {object} credentials - Admin credentials (username, password)
 * @returns {Promise<object>} A promise that resolves with user data.
 * @throws {Error} If login fails.
 */
export const loginAdmin = async (credentials) => {
  try {
    const response = await post('/api/admin/auth', {
      action: 'login',
      username: credentials.username,
      password: credentials.password
    }, {}, false);

    if (response && response.success && response.token && response.user) {
      const { token, user } = response;
      storeAuthData(token, 'admin', user.id, user.username);
      return {
        token,
        role: 'admin',
        userId: user.id,
        userName: user.username,
        user
      };
    } else {
      throw new Error('Login response missing required data.');
    }
  } catch (error) {
    console.error('Admin login failed:', error.message);
    throw error;
  }
};

/**
 * Support Agent login - sends credentials to the backend and stores auth data on success.
 * @param {object} credentials - Agent credentials (username/email, password)
 * @returns {Promise<object>} A promise that resolves with user data.
 * @throws {Error} If login fails.
 */
export const loginAgent = async (credentials) => {
  try {
    const response = await post('/api/agent/auth', {
      action: 'login',
      email: credentials.username, // Backend expects 'email' field
      password: credentials.password
    }, {}, false);

    if (response && response.success && response.token && response.user) {
      const { token, user } = response;
      storeAuthData(token, 'agent', user.id, user.email);
      return {
        token,
        role: 'agent',
        userId: user.id,
        userName: user.email, // Use email as username for agents
        user
      };
    } else {
      throw new Error('Login response missing required data.');
    }
  } catch (error) {
    console.error('Agent login failed:', error.message);
    throw error;
  }
};

/**
 * Support Agent registration.
 * @param {object} userData - Agent data (username, email, password, full_name, phone, region)
 * @returns {Promise<object>} - Response data from the API
 */
export const registerAgent = async (userData) => {
  try {
    const response = await post('/api/agent/auth', {
      action: 'register',
      ...userData
    }, {}, false);
    
    if (response && response.success && response.token && response.user) {
      const { token, user } = response;
      storeAuthData(token, 'agent', user.id, user.email);
      return {
        token,
        role: 'agent',
        userId: user.id,
        userName: user.email, // Use email as username for agents
        user
      };
    }
    
    return response;
  } catch (error) {
    console.error('Error during agent registration:', error.message);
    throw error;
  }
};

/**
 * Admin password update.
 * @param {object} passwordData - Password data (current_password, new_password)
 * @returns {Promise<object>} - Response data from the API
 */
export const updateAdminPassword = async (passwordData) => {
  try {
    const response = await post('/api/admin/auth', {
      action: 'update_password',
      current_password: passwordData.current_password,
      new_password: passwordData.new_password
    });
    return response;
  } catch (error) {
    console.error('Error updating admin password:', error.message);
    throw error;
  }
};

/**
 * Handles user logout.
 * Removes token and user data from client-side storage.
 * @returns {Promise<void>}
 */
export const logoutUser = async () => {
  // Clear client-side storage immediately for responsiveness
  localStorage.removeItem(TOKEN_KEY);
  localStorage.removeItem(USER_ROLE_KEY);
  localStorage.removeItem(USER_ID_KEY);
  localStorage.removeItem(USER_NAME_KEY);
  console.log('User logged out. Auth data cleared client-side.');
};

/**
 * Universal login function that determines login type based on credentials
 * @param {object} credentials - User credentials
 * @param {string} loginType - 'admin' or 'agent'
 * @returns {Promise<object>} - Login response
 */
export const loginUser = async (credentials, loginType = 'agent') => {
  if (loginType === 'admin') {
    return await loginAdmin(credentials);
  } else {
    return await loginAgent(credentials);
  }
};