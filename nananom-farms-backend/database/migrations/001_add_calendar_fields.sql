-- Migration script to add calendar fields to enquiries table
-- This script adds preferred_date and preferred_time columns to the enquiries table

-- Add preferred_date column
ALTER TABLE enquiries ADD COLUMN preferred_date DATE;

-- Add preferred_time column
ALTER TABLE enquiries ADD COLUMN preferred_time TIME;

-- Create indexes for better performance on the new columns
CREATE INDEX IF NOT EXISTS idx_enquiries_preferred_date ON enquiries(preferred_date);
CREATE INDEX IF NOT EXISTS idx_enquiries_status_date ON enquiries(status, preferred_date);
