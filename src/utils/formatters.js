/**
 * Formatting utility functions
 */

/**
 * Format currency values with 2 decimal places
 *
 * @param {number|string} value - The value to format
 * @returns {string} - Formatted number with 2 decimal places
 */
export const formatCurrency = (value) => {
  return parseFloat(value).toFixed(2);
};

/**
 * Format timestamp to date and time
 *
 * @param {string} timestamp - Timestamp string from the database
 * @returns {string} - Formatted date and time string
 */
export const formatDateTime = (timestamp) => {
  return new Date(timestamp).toLocaleString();
};

/**
 * Format timestamp to time only
 *
 * @param {string} timestamp - Timestamp string from the database
 * @returns {string} - Formatted time string (HH:MM)
 */
export const formatTime = (timestamp) => {
  return new Date(timestamp).toLocaleTimeString([], {
    hour: "2-digit",
    minute: "2-digit",
  });
};
