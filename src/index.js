import React, { useState, useEffect } from "react";
import { createRoot } from "react-dom/client";
import { jsPDF } from "jspdf";
import "jspdf-autotable";
import "./styles.css";

/**
 * Main GastroRechner Component
 */
const GastroRechner = () => {
  // State for form data
  const [formData, setFormData] = useState({
    name: "",
    totalSales: "",
    salesCash: "",
    flowCashReceived: false,
  });

  // State for submissions
  const [submissions, setSubmissions] = useState([]);

  // State for settings
  const [settings, setSettings] = useState({
    flowCash: 100,
    tipFactor: 2,
    showFlowCashToggle: true,
    currentUser: "",
    isLoggedIn: false,
  });

  // State for UI
  const [loading, setLoading] = useState(false);
  const [message, setMessage] = useState(null);
  const [showModal, setShowModal] = useState(false);
  const [modalData, setModalData] = useState(null);
  const [editMode, setEditMode] = useState(false);
  const [editId, setEditId] = useState(null);

  // Add state for table sorting
  const [sortConfig, setSortConfig] = useState({
    key: "timestamp",
    direction: "desc",
  });

  // Add function to generate class names for sortable headers
  const getColumnClass = (key) => {
    if (sortConfig.key === key) {
      return `sort-${sortConfig.direction}`;
    }
    return "";
  };

  // Get container attributes
  const container = document.getElementById("gastro-rechner-root");
  const title = container?.dataset?.title || "Gastro-Rechner";
  const showName = container?.dataset?.show_name !== "false";
  const theme = container?.dataset?.theme || "light";

  // Load settings from WordPress
  useEffect(() => {
    if (window.gastroRechnerData) {
      const wordpressSettings = window.gastroRechnerData.settings || settings;
      setSettings(wordpressSettings);

      // Pre-populate name field with logged-in user's name if available
      if (wordpressSettings.isLoggedIn && wordpressSettings.currentUser) {
        setFormData((prev) => ({
          ...prev,
          name: wordpressSettings.currentUser,
        }));
      }
    }
    loadSubmissions();
  }, []);

  // Load submissions from the server
  const loadSubmissions = async () => {
    setLoading(true);
    try {
      const response = await fetch(
        `${window.gastroRechnerData.ajaxUrl}?action=gastro_rechner_get_submissions&nonce=${window.gastroRechnerData.nonce}`
      );
      const data = await response.json();
      if (data.success) {
        setSubmissions(data.data || []);
      }
    } catch (error) {
      console.error("Failed to load submissions:", error);
      setMessage({
        type: "error",
        text: "Failed to load submissions. Please try again.",
      });
    } finally {
      setLoading(false);
    }
  };

  // Calculate derived values
  const calculateTip = (totalSales) => {
    return (parseFloat(totalSales) * (settings.tipFactor / 100)).toFixed(2);
  };

  // Fixed cash out calculation formula: daily_sales * (tip_factor / 100) + sales_cash + flow_cash
  // Update calculateCashOut function
  const calculateCashOut = (
    totalSales,
    salesCash,
    teamTip,
    flowCashReceived
  ) => {
    // Use flow_cash value when checkbox is active, otherwise use 0
    const flowCashAmount = flowCashReceived ? parseFloat(settings.flowCash) : 0;

    // Apply the new formula: daily_sales * (tip_factor / 100) + sales_cash + flow_cash
    return (
      parseFloat(totalSales) * (settings.tipFactor / 100) +
      parseFloat(salesCash) +
      flowCashAmount
    ).toFixed(2);
  };

  // Function to handle sorting
  const handleSort = (key) => {
    let direction = "asc";
    if (sortConfig.key === key && sortConfig.direction === "asc") {
      direction = "desc";
    }
    setSortConfig({ key, direction });
  };

  // Function to get sorted submissions
  const getSortedSubmissions = () => {
    if (!submissions.length) return [];

    const sortableSubmissions = [...submissions];

    return sortableSubmissions.sort((a, b) => {
      if (sortConfig.key === "name") {
        return sortConfig.direction === "asc"
          ? a.name.localeCompare(b.name)
          : b.name.localeCompare(a.name);
      }

      if (sortConfig.key === "timestamp") {
        return sortConfig.direction === "asc"
          ? new Date(a.timestamp) - new Date(b.timestamp)
          : new Date(b.timestamp) - new Date(a.timestamp);
      }

      // For numeric values
      const aValue = parseFloat(a[sortConfig.key]);
      const bValue = parseFloat(b[sortConfig.key]);

      return sortConfig.direction === "asc" ? aValue - bValue : bValue - aValue;
    });
  };

  // Handle form input changes
  const handleInputChange = (e) => {
    const { name, value, type, checked } = e.target;
    setFormData({
      ...formData,
      [name]: type === "checkbox" ? checked : value,
    });
  };

  // Removing the success message and confirmation display
  const handleSubmit = async (e) => {
    e.preventDefault();

    // Validate form
    if (!formData.name || !formData.totalSales || !formData.salesCash) {
      setMessage({
        type: "error",
        text: "Please fill in all required fields.",
      });
      return;
    }

    setLoading(true);
    setMessage(null);

    const teamTip = calculateTip(formData.totalSales);
    const cashOut = calculateCashOut(
      formData.totalSales,
      formData.salesCash,
      teamTip,
      formData.flowCashReceived
    );

    try {
      // Prepare form data for submission
      const submissionData = new FormData();
      submissionData.append(
        "action",
        editMode ? "gastro_rechner_update_submission" : "gastro_rechner_submit"
      );
      submissionData.append("nonce", window.gastroRechnerData.nonce);
      submissionData.append("name", formData.name);
      submissionData.append("totalSales", formData.totalSales);
      submissionData.append("salesCash", formData.salesCash);
      submissionData.append("teamTip", teamTip);
      submissionData.append(
        "flowCashReceived",
        formData.flowCashReceived ? "1" : "0"
      );

      if (editMode && editId) {
        submissionData.append("id", editId);
      }

      // Submit the form
      const response = await fetch(window.gastroRechnerData.ajaxUrl, {
        method: "POST",
        body: submissionData,
        credentials: "same-origin",
      });

      const data = await response.json();

      if (data.success) {
        // Remove success message and confirmation display
        // Just reset form and reload submissions without showing confirmation

        // Reset form
        setFormData({
          name:
            settings.isLoggedIn && settings.currentUser
              ? settings.currentUser
              : "",
          totalSales: "",
          salesCash: "",
          flowCashReceived: false,
        });

        // Reset edit mode
        setEditMode(false);
        setEditId(null);

        // Reload submissions
        loadSubmissions();
      } else {
        setMessage({
          type: "error",
          text: data.data.message || "Failed to submit. Please try again.",
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

  // Handle edit submission
  const handleEdit = (submission) => {
    setFormData({
      name: submission.name,
      totalSales: submission.total_sales.toString(),
      salesCash: submission.sales_cash.toString(),
      flowCashReceived: submission.flow_cash_received,
    });
    setEditMode(true);
    setEditId(submission.id);

    // Scroll to form
    document
      .getElementById("gastro-rechner-form")
      .scrollIntoView({ behavior: "smooth" });
  };

  // Handle view submission details
  const handleView = (submission) => {
    setModalData(submission);
    setShowModal(true);
  };

  // Handle delete submission
  const handleDelete = async (id) => {
    if (!confirm("Are you sure you want to delete this entry?")) {
      return;
    }

    setLoading(true);
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

      if (data.success) {
        setMessage({ type: "success", text: "Entry deleted successfully!" });
        loadSubmissions();
      } else {
        setMessage({
          type: "error",
          text: data.data.message || "Failed to delete. Please try again.",
        });
      }
    } catch (error) {
      console.error("Delete error:", error);
      setMessage({
        type: "error",
        text: "An error occurred. Please try again.",
      });
    } finally {
      setLoading(false);
    }
  };

  // Handle clear all records
  const handleClearAll = async () => {
    if (
      !confirm(
        "Are you sure you want to clear all entries? This cannot be undone."
      )
    ) {
      return;
    }

    setLoading(true);
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

      if (data.success) {
        setMessage({
          type: "success",
          text: "All entries cleared successfully!",
        });
        setSubmissions([]);
      } else {
        setMessage({
          type: "error",
          text:
            data.data.message || "Failed to clear entries. Please try again.",
        });
      }
    } catch (error) {
      console.error("Clear all error:", error);
      setMessage({
        type: "error",
        text: "An error occurred. Please try again.",
      });
    } finally {
      setLoading(false);
    }
  };

  // Close modal
  const closeModal = () => {
    setShowModal(false);
    setModalData(null);
  };

  // Calculate totals for the table footer using the updated formula
  const calculateTotals = () => {
    if (!submissions.length) return null;

    const totals = submissions.reduce(
      (acc, submission) => {
        // Use the new formula for cash out calculation
        const cashOutAmount =
          parseFloat(submission.total_sales) * (settings.tipFactor / 100) +
          parseFloat(submission.sales_cash) +
          (submission.flow_cash_received ? parseFloat(settings.flowCash) : 0);

        return {
          totalSales: acc.totalSales + parseFloat(submission.total_sales),
          salesCash: acc.salesCash + parseFloat(submission.sales_cash),
          teamTip: acc.teamTip + parseFloat(submission.team_tip),
          cashOut: acc.cashOut + cashOutAmount,
        };
      },
      { totalSales: 0, salesCash: 0, teamTip: 0, cashOut: 0 }
    );

    return totals;
  };

  const handleExportToPdf = () => {
    try {
      // Create a new PDF document
      const doc = new jsPDF();

      // Add title
      doc.setFontSize(18);
      doc.text("Gastro-Rechner: Submissions Report", 14, 22);

      // Add date
      doc.setFontSize(11);
      doc.text(`Generated: ${new Date().toLocaleString()}`, 14, 30);

      // Add settings info
      doc.setFontSize(10);
      doc.text(`Team Tip Factor: ${settings.tipFactor}%`, 14, 38);
      doc.text(`Flow Cash Amount: ${settings.flowCash} €`, 14, 43);

      // Prepare table data
      const tableData = getSortedSubmissions().map((submission) => {
        const flowCashReceived = Boolean(
          parseInt(submission.flow_cash_received)
        );
        const cashOut = (
          parseFloat(submission.total_sales) * (settings.tipFactor / 100) +
          parseFloat(submission.sales_cash) +
          (flowCashReceived
            ? parseFloat(submission.flow_cash || settings.flowCash)
            : 0)
        ).toFixed(2);

        // Format date with full date and time for PDF export
        const fullDateTime = new Date(submission.timestamp).toLocaleString();

        return [
          submission.name,
          `${parseFloat(submission.total_sales).toFixed(2)} €`,
          `${parseFloat(submission.sales_cash).toFixed(2)} €`,
          `${parseFloat(submission.team_tip).toFixed(2)} €`,
          flowCashReceived ? "Yes" : "No",
          `${cashOut} €`,
          fullDateTime,
        ];
      });

      // Add totals row if available
      if (totals) {
        tableData.push([
          "TOTALS",
          `${totals.totalSales.toFixed(2)} €`,
          `${totals.salesCash.toFixed(2)} €`,
          `${totals.teamTip.toFixed(2)} €`,
          "-",
          `${totals.cashOut.toFixed(2)} €`,
          "",
        ]);
      }

      // Create the table
      doc.autoTable({
        startY: 50,
        head: [
          [
            "Name",
            "Total Sales",
            "Cash Sales",
            "Team Tip",
            "Flow Cash",
            "Cash Out",
            "Date",
          ],
        ],
        body: tableData,
        theme: "striped",
        headStyles: {
          fillColor: [4, 25, 35], // Primary color in RGB
          textColor: [255, 255, 255],
          fontStyle: "bold",
        },
        footStyles: {
          fillColor: [4, 25, 35], // Primary color in RGB
          textColor: [255, 255, 255],
          fontStyle: "bold",
        },
        alternateRowStyles: {
          fillColor: [240, 240, 240],
        },
        margin: { top: 50 },
      });

      // Save the PDF with name that includes current date
      const fileName = `gastro-rechner-report-${
        new Date().toISOString().split("T")[0]
      }.pdf`;
      doc.save(fileName);

      setMessage({
        type: "success",
        text: `PDF successfully exported as "${fileName}"`,
      });
    } catch (error) {
      console.error("PDF Export error:", error);
      setMessage({
        type: "error",
        text: "Failed to export PDF. Please try again.",
      });
    }
  };

  const totals = calculateTotals();

  return (
    <div className={`gastro-rechner-container theme-${theme}`}>
      <h2 className="gastro-rechner-title">{title}</h2>

      {/* Message display */}
      {message && (
        <div className={`gastro-message ${message.type}`}>
          <p>{message.text}</p>
          <button className="gastro-close-btn" onClick={() => setMessage(null)}>
            ×
          </button>
        </div>
      )}

      {/* Sales form */}
      <form
        id="gastro-rechner-form"
        onSubmit={handleSubmit}
        className="gastro-form"
      >
        <h3>{editMode ? "Bearbeiten" : "Abrechnen"}</h3>

        {showName && (
          <div className="gastro-form-group">
            <label htmlFor="name">Nahme</label>
            <input
              type="text"
              id="name"
              name="name"
              value={formData.name}
              onChange={handleInputChange}
              disabled={settings.isLoggedIn}
            />
          </div>
        )}

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
              <strong>Team Tip ({settings.tipFactor}%):</strong>
              {calculateTip(formData.totalSales)} €
            </p>
            <p>
              <strong>Cash Out:</strong>
              {calculateCashOut(
                formData.totalSales,
                formData.salesCash,
                calculateTip(formData.totalSales),
                formData.flowCashReceived
              )}{" "}
              €
            </p>
          </div>
        )}

        <div className="gastro-form-actions">
          <button
            type="submit"
            className="gastro-btn primary"
            disabled={loading}
          >
            {loading ? "Processing..." : editMode ? "Update Entry" : "Submit"}
          </button>

          {editMode && (
            <button
              type="button"
              className="gastro-btn secondary"
              onClick={() => {
                setFormData({
                  name: "",
                  totalSales: "",
                  salesCash: "",
                  flowCashReceived: false,
                });
                setEditMode(false);
                setEditId(null);
              }}
            >
              Cancel
            </button>
          )}
        </div>
      </form>

      {/* Submissions table */}
      <div className="gastro-table-container">
        <h3>Abrechnungen</h3>

        {submissions.length > 0 ? (
          <>
            <table className="gastro-table">
              <thead>
                <tr>
                  <th
                    onClick={() => handleSort("name")}
                    className={getColumnClass("name")}
                  >
                    Name
                  </th>
                  <th
                    onClick={() => handleSort("total_sales")}
                    className={getColumnClass("total_sales")}
                  >
                    Umsatz (€)
                  </th>
                  <th
                    onClick={() => handleSort("sales_cash")}
                    className={getColumnClass("sales_cash")}
                  >
                    Bargeld (€)
                  </th>
                  <th
                    onClick={() => handleSort("team_tip")}
                    className={getColumnClass("team_tip")}
                  >
                    Team-Tip (€)
                  </th>
                  <th>Wechselgeld</th>
                  <th
                    onClick={() => handleSort("cash_out")}
                    className={getColumnClass("cash_out")}
                  >
                    Cash Out (€)
                  </th>
                  <th
                    onClick={() => handleSort("timestamp")}
                    className={getColumnClass("timestamp")}
                  >
                    Time
                  </th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                {getSortedSubmissions().map((submission) => {
                  // Convert flow_cash_received to a proper boolean for display and calculations
                  const flowCashReceived = Boolean(
                    parseInt(submission.flow_cash_received)
                  );

                  const cashOut = (
                    parseFloat(submission.total_sales) *
                      (settings.tipFactor / 100) +
                    parseFloat(submission.sales_cash) +
                    (flowCashReceived
                      ? parseFloat(submission.flow_cash || settings.flowCash)
                      : 0)
                  ).toFixed(2);

                  // Format time according to WordPress settings (time only)
                  const timestamp = new Date(submission.timestamp);
                  const timeString = timestamp.toLocaleTimeString([], {
                    hour: "2-digit",
                    minute: "2-digit",
                  });

                  return (
                    <tr
                      key={submission.id}
                      onClick={() => handleView(submission)}
                    >
                      <td>{submission.name}</td>
                      <td>{parseFloat(submission.total_sales).toFixed(2)} €</td>
                      <td>{parseFloat(submission.sales_cash).toFixed(2)} €</td>
                      <td>{parseFloat(submission.team_tip).toFixed(2)} €</td>
                      <td>
                        {flowCashReceived
                          ? `Yes (${parseFloat(
                              submission.flow_cash || settings.flowCash
                            ).toFixed(2)} €)`
                          : "No"}
                      </td>
                      <td>{cashOut} €</td>
                      <td>{timeString}</td>
                      <td
                        className="gastro-actions"
                        onClick={(e) => e.stopPropagation()}
                      >
                        <button
                          onClick={() => handleView(submission)}
                          className="gastro-action-btn view"
                          title="View Details"
                        >
                          <i className="fas fa-eye"></i>
                        </button>
                        <button
                          onClick={() => handleEdit(submission)}
                          className="gastro-action-btn edit"
                          title="Edit"
                        >
                          <i className="fas fa-edit"></i>
                        </button>
                        <button
                          onClick={() => handleDelete(submission.id)}
                          className="gastro-action-btn delete"
                          title="Delete"
                        >
                          <i className="fas fa-trash-alt"></i>
                        </button>
                      </td>
                    </tr>
                  );
                })}
              </tbody>
              {totals && (
                <tfoot>
                  <tr>
                    <td>
                      <strong>Totals</strong>
                    </td>
                    <td>
                      <strong>{totals.totalSales.toFixed(2)} €</strong>
                    </td>
                    <td>
                      <strong>{totals.salesCash.toFixed(2)} €</strong>
                    </td>
                    <td>
                      <strong>{totals.teamTip.toFixed(2)} €</strong>
                    </td>
                    <td>-</td>
                    <td>
                      <strong>{totals.cashOut.toFixed(2)} €</strong>
                    </td>
                    <td>-</td>
                    <td>
                      <button
                        className="clear-all-footer"
                        onClick={handleClearAll}
                        disabled={loading}
                      >
                        <i className="fas fa-trash"></i> Clear All
                      </button>
                    </td>
                  </tr>
                </tfoot>
              )}
            </table>

            <div className="gastro-table-actions">
              <button
                className="gastro-btn primary"
                onClick={handleExportToPdf}
                disabled={loading || submissions.length === 0}
              >
                <i className="fas fa-file-pdf"></i> Export to PDF
              </button>
            </div>
          </>
        ) : (
          <p className="gastro-no-data">No submissions yet.</p>
        )}
      </div>

      {/* Modal for viewing submission details */}
      {showModal && modalData && (
        <div className="gastro-modal-backdrop" onClick={closeModal}>
          <div className="gastro-modal" onClick={(e) => e.stopPropagation()}>
            <div className="gastro-modal-header">
              <h3>Abrechnung Details</h3>
              <button className="gastro-close-btn" onClick={closeModal}>
                ×
              </button>
            </div>
            <div className="gastro-modal-body">
              <p>
                <strong>Name:</strong> {modalData.name}
              </p>
              <p>
                <strong>Total Sales:</strong>{" "}
                {parseFloat(modalData.total_sales).toFixed(2)} €
              </p>
              <p>
                <strong>Cash Sales:</strong>{" "}
                {parseFloat(modalData.sales_cash).toFixed(2)} €
              </p>
              <p>
                <strong>Team Tip ({settings.tipFactor}%):</strong>{" "}
                {parseFloat(modalData.team_tip).toFixed(2)} €
              </p>
              <p>
                <strong>wechselgeld?:</strong>{" "}
                {modalData.flow_cash_received
                  ? `Yes (${parseFloat(
                      modalData.flow_cash || settings.flowCash
                    ).toFixed(2)} €)`
                  : "No"}
              </p>
              <p>
                <strong>Cash Out:</strong>
                {(
                  parseFloat(modalData.total_sales) *
                    (settings.tipFactor / 100) +
                  parseFloat(modalData.sales_cash) +
                  (modalData.flow_cash_received
                    ? parseFloat(modalData.flow_cash || settings.flowCash)
                    : 0)
                ).toFixed(2)}{" "}
                €
              </p>
              <p>
                <strong>Date/Time:</strong>{" "}
                {new Date(modalData.timestamp).toLocaleString()}
              </p>
            </div>
            <div className="gastro-modal-footer">
              <button className="gastro-btn secondary" onClick={closeModal}>
                <i className="fas fa-times"></i> Close
              </button>
              <button
                className="gastro-btn primary"
                onClick={() => {
                  closeModal();
                  handleEdit(modalData);
                }}
              >
                <i className="fas fa-edit"></i> Edit
              </button>
              <button
                className="gastro-btn danger"
                onClick={() => {
                  closeModal();
                  handleDelete(modalData.id);
                }}
              >
                <i className="fas fa-trash-alt"></i> Delete
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

// Render the app when the DOM is ready
document.addEventListener("DOMContentLoaded", () => {
  const container = document.getElementById("gastro-rechner-root");
  if (container) {
    const root = createRoot(container);
    root.render(<GastroRechner />);
  }
});
