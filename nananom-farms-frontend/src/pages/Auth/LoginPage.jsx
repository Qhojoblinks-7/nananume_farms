import React, { useState, useEffect } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { useAppDispatch, useAppSelector } from '../../hooks/useAppDispatch';
import { 
  loginAdminAsync, 
  loginAgentAsync, 
  selectAuthLoading, 
  selectAuthError, 
  selectAuthSuccess,
  clearError,
  clearSuccess 
} from '../../store/slices/authSlice';

const LoginPage = () => {
  const [loginType, setLoginType] = useState('agent'); // 'admin' or 'agent'
  const [credentials, setCredentials] = useState({
    username: '',
    password: ''
  });
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);
  
  const dispatch = useAppDispatch();
  const navigate = useNavigate();
  
  // Redux state
  const authLoading = useAppSelector(selectAuthLoading);
  const authError = useAppSelector(selectAuthError);
  const authSuccess = useAppSelector(selectAuthSuccess);

  // Clear errors when component mounts or login type changes
  useEffect(() => {
    dispatch(clearError());
    dispatch(clearSuccess());
  }, [dispatch, loginType]);

  // Handle navigation after successful login
  useEffect(() => {
    if (authSuccess && !authLoading) {
      // Navigate based on login type
      if (loginType === 'admin') {
        navigate('/admin/dashboard');
      } else {
        navigate('/agent/dashboard');
      }
    }
  }, [authSuccess, authLoading, loginType, navigate]);

  // Handle errors
  useEffect(() => {
    if (authError) {
      setError(authError);
    }
  }, [authError]);

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError('');
    setLoading(true);

    try {
      if (loginType === 'admin') {
        await dispatch(loginAdminAsync(credentials)).unwrap();
      } else {
        await dispatch(loginAgentAsync(credentials)).unwrap();
      }
    } catch (err) {
      setError(err.message || 'Login failed. Please try again.');
    } finally {
      setLoading(false);
    }
  };

  const handleInputChange = (e) => {
    const { name, value } = e.target;
    setCredentials(prev => ({
      ...prev,
      [name]: value
    }));
  };

  const isLoading = loading || authLoading;

  return (
    <div className="min-h-screen flex items-center justify-center bg-[#DAD7CD] p-4 font-sans">
      <div className="w-full max-w-md bg-[#DAD7CD] p-8 rounded-xl shadow-lg border-2 border-[#FFB703] animate-popIn">
        <h1 className="text-4xl font-extrabold text-center text-[#086920] mb-8">
          Welcome Back!
        </h1>

        {/* Login Type Selector */}
        <div className="mb-6">
          <label className="block text-base font-semibold text-[#2F2F2F] mb-3">
            Login as:
          </label>
          <div className="flex space-x-4">
            <button
              type="button"
              onClick={() => setLoginType('agent')}
              className={`flex-1 py-2 px-4 rounded-lg font-medium transition-all duration-300 ${
                loginType === 'agent'
                  ? 'bg-[#086920] text-white shadow-md'
                  : 'bg-white text-[#2F2F2F] border border-[#086920] hover:bg-gray-50'
              }`}
            >
              Support Agent
            </button>
            <button
              type="button"
              onClick={() => setLoginType('admin')}
              className={`flex-1 py-2 px-4 rounded-lg font-medium transition-all duration-300 ${
                loginType === 'admin'
                  ? 'bg-[#086920] text-white shadow-md'
                  : 'bg-white text-[#2F2F2F] border border-[#086920] hover:bg-gray-50'
              }`}
            >
              Administrator
            </button>
          </div>
        </div>

        {error && (
          <div className="bg-red-100 border border-red-400 text-red-700 px-5 py-3 rounded-lg relative mb-6 shadow-md" role="alert">
            <strong className="font-bold mr-2">Login Error:</strong>
            <span className="block sm:inline">{error}</span>
          </div>
        )}

        {authSuccess && (
          <div className="bg-green-100 border border-green-400 text-green-700 px-5 py-3 rounded-lg relative mb-6 shadow-md" role="alert">
            <strong className="font-bold mr-2">Success:</strong>
            <span className="block sm:inline">{authSuccess}</span>
          </div>
        )}

        <form className="space-y-6" onSubmit={handleSubmit}>
          <div>
            <label htmlFor="username" className="block text-base font-semibold text-[#2F2F2F] mb-2">
              {loginType === 'admin' ? 'Username' : 'Username or Email'}
            </label>
            <input
              type="text"
              id="username"
              name="username"
              className="mt-1 block w-full px-4 py-2 border border-[#086920] rounded-lg shadow-sm focus:ring-[#FFB703] focus:border-[#FFB703] text-[#2F2F2F] placeholder-[#086920] bg-white transition-all duration-300 ease-in-out hover:border-[#FFB703]"
              placeholder={loginType === 'admin' ? 'admin' : 'agent@example.com'}
              value={credentials.username}
              onChange={handleInputChange}
              required
            />
          </div>
          
          <div>
            <label htmlFor="password" className="block text-base font-semibold text-[#2F2F2F] mb-2">
              Password
            </label>
            <input
              type="password"
              id="password"
              name="password"
              className="mt-1 block w-full px-4 py-2 border border-[#086920] rounded-lg shadow-sm focus:ring-[#FFB703] focus:border-[#FFB703] text-[#2F2F2F] placeholder-[#086920] bg-white transition-all duration-300 ease-in-out hover:border-[#FFB703]"
              placeholder="••••••••"
              value={credentials.password}
              onChange={handleInputChange}
              required
            />
          </div>

          {/* Default Credentials Info */}
          {loginType === 'admin' && (
            <div className="bg-blue-50 border border-blue-200 text-blue-700 px-4 py-3 rounded-lg text-sm">
              <strong>Default Admin Credentials:</strong><br />
              Username: <code>admin</code><br />
              Password: <code>admin123</code>
            </div>
          )}

          <button
            type="submit"
            className="w-full bg-[#FFB703] text-[#2F2F2F] p-3 rounded-lg font-bold text-lg hover:bg-[#086920] hover:text-[#FFFFFF] transition-all duration-300 ease-in-out transform hover:scale-105 focus:outline-none focus:ring-2 focus:ring-[#086920] focus:ring-offset-2 disabled:opacity-60 disabled:cursor-not-allowed disabled:hover:scale-100"
            disabled={isLoading}
          >
            {isLoading ? (
              <span className="flex items-center justify-center">
                <svg className="animate-spin -ml-1 mr-3 h-5 w-5 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                  <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Logging In...
              </span>
            ) : (
              `Login as ${loginType === 'admin' ? 'Administrator' : 'Support Agent'}`
            )}
          </button>
        </form>

        <p className="mt-8 text-center text-base text-[#2F2F2F]">
          Don't have an account?{' '}
          <Link to="/register" className="font-semibold text-[#086920] hover:text-[#FFB703] hover:underline transition-colors duration-300">
            Register here
          </Link>
        </p>

        <p className="mt-4 text-center text-sm text-[#2F2F2F]">
          <Link to="/" className="font-semibold text-[#086920] hover:text-[#FFB703] hover:underline transition-colors duration-300">
            ← Back to Home
          </Link>
        </p>
      </div>
    </div>
  );
};

export default LoginPage;