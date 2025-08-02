import React, { useState, useEffect } from 'react';
import { useDispatch, useSelector } from 'react-redux';
import { fetchCalendarEnquiriesAsync, selectCalendarEnquiries, selectEnquiryLoading } from '../store/slices/enquirySlice';

const CalendarView = () => {
  const dispatch = useDispatch();
  const calendarEnquiries = useSelector(selectCalendarEnquiries);
  const loading = useSelector(selectEnquiryLoading);
  
  const [currentDate, setCurrentDate] = useState(new Date());
  const [selectedDate, setSelectedDate] = useState(null);
  const [selectedEnquiries, setSelectedEnquiries] = useState([]);

  // Get the first day of the month for the current view
  const getFirstDayOfMonth = (date) => {
    return new Date(date.getFullYear(), date.getMonth(), 1);
  };

  // Get the last day of the month for the current view
  const getLastDayOfMonth = (date) => {
    return new Date(date.getFullYear(), date.getMonth() + 1, 0);
  };

  // Get the days of the week for the calendar header
  const getDaysOfWeek = () => {
    return ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
  };

  // Get the days to display in the calendar grid
  const getCalendarDays = () => {
    const firstDay = getFirstDayOfMonth(currentDate);
    const lastDay = getLastDayOfMonth(currentDate);
    
    // Get the day of the week for the first day (0 = Sunday, 1 = Monday, etc.)
    const firstDayOfWeek = firstDay.getDay();
    
    // Get the last day of the previous month
    const prevMonthLastDay = new Date(currentDate.getFullYear(), currentDate.getMonth(), 0).getDate();
    
    const days = [];
    
    // Add days from previous month
    for (let i = firstDayOfWeek - 1; i >= 0; i--) {
      days.push({
        date: new Date(currentDate.getFullYear(), currentDate.getMonth() - 1, prevMonthLastDay - i),
        isCurrentMonth: false
      });
    }
    
    // Add days from current month
    for (let i = 1; i <= lastDay.getDate(); i++) {
      days.push({
        date: new Date(currentDate.getFullYear(), currentDate.getMonth(), i),
        isCurrentMonth: true
      });
    }
    
    // Add days from next month to fill the grid
    const totalCells = 42; // 6 rows * 7 days
    const remainingDays = totalCells - days.length;
    for (let i = 1; i <= remainingDays; i++) {
      days.push({
        date: new Date(currentDate.getFullYear(), currentDate.getMonth() + 1, i),
        isCurrentMonth: false
      });
    }
    
    return days;
  };

  // Format date as YYYY-MM-DD for API calls
  const formatDateForAPI = (date) => {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
  };

  // Format date for display
  const formatDate = (date) => {
    return date.toLocaleDateString('en-US', { 
      weekday: 'short', 
      month: 'short', 
      day: 'numeric' 
    });
  };

  // Check if a date has enquiries
  const hasEnquiries = (date) => {
    return calendarEnquiries.some(enquiry => {
      if (!enquiry.preferred_date) return false;
      const enquiryDate = new Date(enquiry.preferred_date);
      return enquiryDate.toDateString() === date.toDateString();
    });
  };

  // Get enquiries for a specific date
  const getEnquiriesForDate = (date) => {
    return calendarEnquiries.filter(enquiry => {
      if (!enquiry.preferred_date) return false;
      const enquiryDate = new Date(enquiry.preferred_date);
      return enquiryDate.toDateString() === date.toDateString();
    });
  };

  // Handle date selection
  const handleDateSelect = (date) => {
    setSelectedDate(date);
    const enquiries = getEnquiriesForDate(date);
    setSelectedEnquiries(enquiries);
  };

  // Navigate to previous month
  const prevMonth = () => {
    setCurrentDate(new Date(currentDate.getFullYear(), currentDate.getMonth() - 1, 1));
  };

  // Navigate to next month
  const nextMonth = () => {
    setCurrentDate(new Date(currentDate.getFullYear(), currentDate.getMonth() + 1, 1));
  };

  // Navigate to today
  const goToToday = () => {
    setCurrentDate(new Date());
    setSelectedDate(new Date());
    const enquiries = getEnquiriesForDate(new Date());
    setSelectedEnquiries(enquiries);
  };

  // Fetch calendar enquiries when the month changes
  useEffect(() => {
    const startDate = formatDateForAPI(getFirstDayOfMonth(currentDate));
    const endDate = formatDateForAPI(getLastDayOfMonth(currentDate));
    
    dispatch(fetchCalendarEnquiriesAsync({ startDate, endDate }));
  }, [currentDate, dispatch]);

  // Initialize with today's date
  useEffect(() => {
    goToToday();
  }, []);

  const daysOfWeek = getDaysOfWeek();
  const calendarDays = getCalendarDays();

  return (
    <div className="bg-[#FFFFFF] rounded-lg shadow-lg border border-[#FFB703] p-6">
      <div className="flex justify-between items-center mb-6">
        <h2 className="text-2xl font-bold text-[#086920]">Enquiry Calendar</h2>
        <div className="flex space-x-2">
          <button 
            onClick={prevMonth}
            className="px-4 py-2 bg-[#FFB703] text-[#2F2F2F] rounded-lg hover:bg-[#086920] hover:text-[#FFFFFF] transition-colors"
          >
            &larr; Prev
          </button>
          <button 
            onClick={goToToday}
            className="px-4 py-2 bg-[#086920] text-[#FFFFFF] rounded-lg hover:bg-[#FFB703] hover:text-[#2F2F2F] transition-colors"
          >
            Today
          </button>
          <button 
            onClick={nextMonth}
            className="px-4 py-2 bg-[#FFB703] text-[#2F2F2F] rounded-lg hover:bg-[#086920] hover:text-[#FFFFFF] transition-colors"
          >
            Next &rarr;
          </button>
        </div>
      </div>
      
      <div className="mb-4 text-center">
        <h3 className="text-xl font-semibold text-[#2F2F2F]">
          {currentDate.toLocaleDateString('en-US', { month: 'long', year: 'numeric' })}
        </h3>
      </div>
      
      {loading ? (
        <div className="flex justify-center items-center h-64">
          <div className="w-10 h-10 border-4 border-t-[#086920] border-b-[#FFB703] border-l-[#086920] border-r-[#FFB703] rounded-full animate-spin"></div>
        </div>
      ) : (
        <div className="grid grid-cols-7 gap-1">
          {daysOfWeek.map((day) => (
            <div key={day} className="text-center font-bold text-[#086920] py-2">
              {day}
            </div>
          ))}
          
          {calendarDays.map((day, index) => {
            const isToday = day.date.toDateString() === new Date().toDateString();
            const isSelected = selectedDate && day.date.toDateString() === selectedDate.toDateString();
            const hasEnq = hasEnquiries(day.date);
            
            return (
              <div 
                key={index}
                onClick={() => handleDateSelect(day.date)}
                className={`min-h-20 p-1 border border-[#DAD7CD] cursor-pointer transition-colors
                  ${day.isCurrentMonth ? 'bg-[#FFFFFF]' : 'bg-[#F0F0F0] text-gray-400'}
                  ${isToday ? 'border-2 border-[#086920]' : ''}
                  ${isSelected ? 'bg-[#B7EFC5]' : ''}
                  ${hasEnq ? 'bg-[#FFB703] bg-opacity-20' : ''}
                  hover:bg-[#B7EFC5]`}
              >
                <div className="text-right text-sm font-medium">
                  {day.date.getDate()}
                </div>
                {hasEnq && (
                  <div className="text-xs text-[#086920] font-bold mt-1">
                    {getEnquiriesForDate(day.date).length} enquiry(s)
                  </div>
                )}
              </div>
            );
          })}
        </div>
      )}
      
      {selectedDate && (
        <div className="mt-6 pt-4 border-t border-[#FFB703]">
          <h3 className="text-lg font-semibold text-[#2F2F2F] mb-3">
            Enquiries for {formatDate(selectedDate)}
          </h3>
          {selectedEnquiries.length === 0 ? (
            <p className="text-[#2F2F2F]">No enquiries scheduled for this date.</p>
          ) : (
            <div className="space-y-3">
              {selectedEnquiries.map((enquiry) => (
                <div key={enquiry.id} className="p-3 bg-[#DAD7CD] rounded-lg">
                  <div className="flex justify-between items-start">
                    <div>
                      <h4 className="font-semibold text-[#086920]">{enquiry.subject}</h4>
                      <p className="text-sm text-[#2F2F2F]">{enquiry.full_name} ({enquiry.email})</p>
                      {enquiry.preferred_time && (
                        <p className="text-sm text-[#2F2F2F]">Time: {enquiry.preferred_time}</p>
                      )}
                    </div>
                    <span className={`px-2 py-1 rounded-full text-xs font-medium ${
                      enquiry.status === 'pending' ? 'bg-[#FFB703] text-[#2F2F2F]' :
                      enquiry.status === 'in_progress' ? 'bg-[#B7EFC5] text-[#2F2F2F]' :
                      enquiry.status === 'resolved' ? 'bg-[#086920] text-[#FFFFFF]' :
                      'bg-[#2F2F2F] text-[#FFFFFF]'
                    }`}>
                      {enquiry.status}
                    </span>
                  </div>
                  <p className="text-sm text-[#2F2F2F] mt-2 line-clamp-2">{enquiry.message}</p>
                </div>
              ))}
            </div>
          )}
        </div>
      )}
    </div>
  );
};

export default CalendarView;
