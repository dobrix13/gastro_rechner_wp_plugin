/**
 * API service functions for handling AJAX requests
 */

/**
 * Load all submissions from the server
 *
 * @returns {Promise<Array>} - Array of submissions
 */
export const loadSubmissions = async () => {
  try {
    const response = await fetch(
      `${window.gastroRechnerData.ajaxUrl}?action=gastro_rechner_get_submissions&nonce=${window.gastroRechnerData.nonce}`
    );
    const data = await response.json();

    if (data.success) {
      return data.data || [];
    } else {
      throw new Error(data.data?.message || "Failed to load submissions");
    }
  } catch (error) {
    console.error("Failed to load submissions:", error);
    throw error;
  }
};

/**
 * Submit new data to the server
 *
 * @param {Object} formData - The form data to submit
 * @param {string} teamTip - Calculated team tip value
 * @returns {Promise<Object>} - Response data
 */
export const submitData = async (formData, teamTip) => {
  try {
    const submissionData = new FormData();
    submissionData.append("action", "gastro_rechner_submit");
    submissionData.append("nonce", window.gastroRechnerData.nonce);
    submissionData.append("name", formData.name);
    submissionData.append("totalSales", formData.totalSales);
    submissionData.append("salesCash", formData.salesCash);
    submissionData.append("teamTip", teamTip);
    submissionData.append(
      "flowCashReceived",
      formData.flowCashReceived ? "1" : "0"
    );

    const response = await fetch(window.gastroRechnerData.ajaxUrl, {
      method: "POST",
      body: submissionData,
      credentials: "same-origin",
    });

    const data = await response.json();
    return {
      success: data.success,
      message: data.data?.message,
      submissionId: data.data?.submissionId,
    };
  } catch (error) {
    console.error("Submission error:", error);
    throw error;
  }
};

/**
 * Update existing submission
 *
 * @param {number} id - The ID of the submission to update
 * @param {Object} formData - The form data to submit
 * @param {string} teamTip - Calculated team tip value
 * @returns {Promise<Object>} - Response data
 */
export const updateSubmission = async (id, formData, teamTip) => {
  try {
    const submissionData = new FormData();
    submissionData.append("action", "gastro_rechner_update_submission");
    submissionData.append("nonce", window.gastroRechnerData.nonce);
    submissionData.append("id", id);
    submissionData.append("name", formData.name);
    submissionData.append("totalSales", formData.totalSales);
    submissionData.append("salesCash", formData.salesCash);
    submissionData.append("teamTip", teamTip);
    submissionData.append(
      "flowCashReceived",
      formData.flowCashReceived ? "1" : "0"
    );

    const response = await fetch(window.gastroRechnerData.ajaxUrl, {
      method: "POST",
      body: submissionData,
      credentials: "same-origin",
    });

    const data = await response.json();
    return {
      success: data.success,
      message: data.data?.message,
    };
  } catch (error) {
    console.error("Update submission error:", error);
    throw error;
  }
};

/**
 * Delete a submission
 *
 * @param {number} id - The ID of the submission to delete
 * @returns {Promise<Object>} - Response data
 */
export const deleteSubmission = async (id) => {
  try {
    const formData = new FormData();
    formData.append("action", "gastro_rechner_delete_submission");
    formData.append("nonce", window.gastroRechnerData.nonce);
    formData.append("id", id);

    const response = await fetch(window.gastroRechnerData.ajaxUrl, {
      method: "POST",
      body: formData,
      credentials: "same-origin",
    });

    const data = await response.json();
    return {
      success: data.success,
      message: data.data?.message,
    };
  } catch (error) {
    console.error("Delete error:", error);
    throw error;
  }
};

/**
 * Clear all submissions
 *
 * @returns {Promise<Object>} - Response data
 */
export const clearSubmissions = async () => {
  try {
    const formData = new FormData();
    formData.append("action", "gastro_rechner_clear_submissions");
    formData.append("nonce", window.gastroRechnerData.nonce);

    const response = await fetch(window.gastroRechnerData.ajaxUrl, {
      method: "POST",
      body: formData,
      credentials: "same-origin",
    });

    const data = await response.json();
    return {
      success: data.success,
      message: data.data?.message,
    };
  } catch (error) {
    console.error("Clear all error:", error);
    throw error;
  }
};
