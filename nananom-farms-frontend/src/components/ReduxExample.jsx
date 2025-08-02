import React, { useEffect } from 'react';
import { useAppDispatch } from '../hooks/useAppDispatch';
import { useAppSelector } from '../hooks/useAppSelector';
import { 
  selectIsAuthenticated, 
  selectUserRole, 
  selectUserName,
  selectAuthLoading,
  selectAuthError,
  logoutAsync 
} from '../store/slices/authSlice';
import { 
  selectEnquiries, 
  selectEnquiryLoading,
  fetchEnquiriesAsync 
} from '../store/slices/enquirySlice';
import { 
  selectNotifications,
  addNotification,
  removeNotification 
} from '../store/slices/uiSlice';

const ReduxExample = () => {
  const dispatch = useAppDispatch();
  
  // Auth selectors
  const isAuthenticated = useAppSelector(selectIsAuthenticated);
  const userRole = useAppSelector(selectUserRole);
  const userName = useAppSelector(selectUserName);
  const authLoading = useAppSelector(selectAuthLoading);
  const authError = useAppSelector(selectAuthError);
  
  // Enquiry selectors
  const enquiries = useAppSelector(selectEnquiries);
  const enquiryLoading = useAppSelector(selectEnquiryLoading);
  
  // UI selectors
  const notifications = useAppSelector(selectNotifications);
  
  useEffect(() => {
    // Fetch enquiries when component mounts
    if (isAuthenticated) {
      dispatch(fetchEnquiriesAsync());
    }
  }, [dispatch, isAuthenticated]);
  
  const handleLogout = () => {
    dispatch(logoutAsync());
  };
  
  const handleAddNotification = () => {
    dispatch(addNotification({
      type: 'success',
      title: 'Success!',
      message: 'This is a test notification',
    }));
  };
  
  const handleRemoveNotification = (notificationId) => {
    dispatch(removeNotification(notificationId));
  };
  
  if (authLoading) {
    return <div>Loading...</div>;
  }
  
  if (authError) {
    return <div>Error: {authError}</div>;
  }
  
  return (
    <div className="p-6 max-w-4xl mx-auto">
      <h1 className="text-2xl font-bold mb-6">Redux with Hooks Example</h1>
      
      {/* Auth Section */}
      <div className="bg-white rounded-lg shadow p-6 mb-6">
        <h2 className="text-xl font-semibold mb-4">Authentication State</h2>
        <div className="space-y-2">
          <p><strong>Authenticated:</strong> {isAuthenticated ? 'Yes' : 'No'}</p>
          <p><strong>Role:</strong> {userRole || 'None'}</p>
          <p><strong>Username:</strong> {userName || 'None'}</p>
        </div>
        {isAuthenticated && (
          <button
            onClick={handleLogout}
            className="mt-4 bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600"
          >
            Logout
          </button>
        )}
      </div>
      
      {/* Enquiries Section */}
      <div className="bg-white rounded-lg shadow p-6 mb-6">
        <h2 className="text-xl font-semibold mb-4">Enquiries</h2>
        {enquiryLoading ? (
          <p>Loading enquiries...</p>
        ) : (
          <div>
            <p><strong>Total Enquiries:</strong> {enquiries.length}</p>
            <div className="mt-4 space-y-2">
              {enquiries.slice(0, 3).map((enquiry) => (
                <div key={enquiry.id} className="border p-3 rounded">
                  <p><strong>Subject:</strong> {enquiry.subject}</p>
                  <p><strong>Status:</strong> {enquiry.status}</p>
                  <p><strong>From:</strong> {enquiry.full_name}</p>
                </div>
              ))}
            </div>
          </div>
        )}
      </div>
      
      {/* Notifications Section */}
      <div className="bg-white rounded-lg shadow p-6 mb-6">
        <h2 className="text-xl font-semibold mb-4">Notifications</h2>
        <button
          onClick={handleAddNotification}
          className="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 mb-4"
        >
          Add Test Notification
        </button>
        <div className="space-y-2">
          {notifications.map((notification) => (
            <div
              key={notification.id}
              className={`p-3 rounded border-l-4 ${
                notification.type === 'success' 
                  ? 'border-green-500 bg-green-50' 
                  : 'border-red-500 bg-red-50'
              }`}
            >
              <div className="flex justify-between items-start">
                <div>
                  <p className="font-semibold">{notification.title}</p>
                  <p className="text-sm">{notification.message}</p>
                </div>
                <button
                  onClick={() => handleRemoveNotification(notification.id)}
                  className="text-gray-500 hover:text-gray-700"
                >
                  ×
                </button>
              </div>
            </div>
          ))}
        </div>
      </div>
      
      {/* Redux State Debug */}
      <div className="bg-gray-100 rounded-lg p-6">
        <h2 className="text-xl font-semibold mb-4">Redux State Debug</h2>
        <details>
          <summary className="cursor-pointer font-medium">Click to view state</summary>
          <pre className="mt-2 text-sm bg-white p-4 rounded overflow-auto">
            {JSON.stringify({
              auth: {
                isAuthenticated,
                userRole,
                userName,
                loading: authLoading,
                error: authError,
              },
              enquiries: {
                count: enquiries.length,
                loading: enquiryLoading,
              },
              notifications: {
                count: notifications.length,
              },
            }, null, 2)}
          </pre>
        </details>
      </div>
    </div>
  );
};

export default ReduxExample;