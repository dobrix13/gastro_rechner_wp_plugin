import React from "react";
import { submitData, updateSubmission } from "../services/api";
import { calculateTip, calculateCashOut } from "../utils/calculator";
import { formatCurrency } from "../utils/formatters";

/**
 * Form Component for data entry
 */
const Form = ({
  formData,
  setFormData,
  settings,
  editMode,
  loading,
  setLoading,
  setMessage,
  resetForm,
  fetchSubmissions,
  editId,
  showName,
}) => {
  // Handle form input changes
  const handleInputChange = (e) => {
    const { name, value, type, checked } = e.target;
    setFormData({
      ...formData,
      [name]: type === "checkbox" ? checked : value,
    });
  };

  // Handle form submission
  const handleSubmit = async (e) => {
    e.preventDefault();

    // Validate form
    if (!formData.totalSales || !formData.salesCash) {
      setMessage({
        type: "error",
        text: "Please fill in all required fields.",
      });
      return;
    }

    // Set name from WordPress user if logged in, otherwise use "Guest"
    const submissionName = settings.isLoggedIn ? settings.currentUser : "Guest";
    const dataToSubmit = {
      ...formData,
      name: submissionName,
    };

    setLoading(true);
    setMessage(null);

    const teamTip = calculateTip(formData.totalSales, settings.tipFactor);

    try {
      // Submit the form data
      const result = editMode
        ? await updateSubmission(editId, dataToSubmit, teamTip)
        : await submitData(dataToSubmit, teamTip);

      if (result.success) {
        // Reset form and reload submissions
        resetForm();
        fetchSubmissions();
      } else {
        setMessage({
          type: "error",
          text: result.message || "Failed to submit. Please try again.",
        });
      }
    } catch (error) {
      console.error("Submission error:", error);
      setMessage({
        type: "error",
        text: "An error occurred. Please try again.",
      });
    } finally {
      setLoading(false);
    }
  };

  return (
    <form
      id="gastro-rechner-form"
      onSubmit={handleSubmit}
      className="gastro-form"
    >
      <h3>{editMode ? "Bearbeiten" : "Abrechnen"}</h3>

      {/* Name field removed - will use WordPress user name automatically */}

      <div className="gastro-form-group">
        <label htmlFor="totalSales">Umsatz (€)</label>
        <input
          type="number"
          id="totalSales"
          name="totalSales"
          step="0.01"
          min="0"
          value={formData.totalSales}
          onChange={handleInputChange}
          required
        />
      </div>

      <div className="gastro-form-group">
        <label htmlFor="salesCash">Bargeld (€)</label>
        <input
          type="number"
          id="salesCash"
          name="salesCash"
          step="0.01"
          value={formData.salesCash}
          onChange={handleInputChange}
          required
        />
      </div>

      {settings.showFlowCashToggle && (
        <div className="gastro-form-group toggle">
          <label htmlFor="flowCashReceived">
            <span>Wechselgeld? ({settings.flowCash} €)</span>
            <div className="gastro-toggle">
              <input
                type="checkbox"
                id="flowCashReceived"
                name="flowCashReceived"
                checked={formData.flowCashReceived}
                onChange={handleInputChange}
              />
              <span className="gastro-toggle-slider"></span>
            </div>
          </label>
        </div>
      )}

      {formData.totalSales && formData.salesCash && (
        <div className="gastro-calculation-preview">
          <p>
            <strong>Team Tip ({settings.tipFactor}%):</strong>{" "}
            {calculateTip(formData.totalSales, settings.tipFactor)} €
          </p>
          <p>
            <strong>Cash Out:</strong>{" "}
            {calculateCashOut(
              formData.totalSales,
              formData.salesCash,
              calculateTip(formData.totalSales, settings.tipFactor),
              formData.flowCashReceived,
              settings.flowCash,
              settings.tipFactor
            )}{" "}
            €
          </p>
        </div>
      )}

      <div className="gastro-form-actions">
        <button type="submit" className="gastro-btn primary" disabled={loading}>
          {loading ? "Processing..." : editMode ? "Update Entry" : "Submit"}
        </button>

        {editMode && (
          <button
            type="button"
            className="gastro-btn secondary"
            onClick={resetForm}
          >
            Cancel
          </button>
        )}
      </div>
    </form>
  );
};

export default Form;
