import React, { useState, useEffect } from "react";
import Form from "./components/Form";
import Table from "./components/Table";
import Modal from "./components/Modal";
import Message from "./components/Message";
import {
  loadSubmissions,
  deleteSubmission,
  clearSubmissions,
} from "./services/api";
import useSettings from "./hooks/usseSettings";
import {
  calculateTip,
  calculateCashOut,
  calculateTotals,
} from "./utils/calculator";
import { formatCurrency } from "./utils/formatters";

/**
 * Main GastroRechner Component
 */
const App = () => {
  // State for form data
  const [formData, setFormData] = useState({
    name: "",
    totalSales: "",
    salesCash: "",
    flowCashReceived: false,
  });

  // State for submissions
  const [submissions, setSubmissions] = useState([]);

  // Get settings from custom hook
  const { settings } = useSettings();

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

  // Get container attributes
  const container = document.getElementById("gastro-rechner-root");
  const title = container?.dataset?.title || "Gastro-Rechner";
  const showName = container?.dataset?.show_name !== "false";

  // Handle both old and new theme formats
  let theme = container?.dataset?.theme || "light-cold";

  // For backward compatibility with old theme values
  if (theme === "light") theme = "light-cold";
  if (theme === "dark") theme = "dark-cold";

  // Load submissions on mount
  useEffect(() => {
    fetchSubmissions();
  }, []);

  // Fetch submissions from server
  const fetchSubmissions = async () => {
    setLoading(true);
    try {
      const data = await loadSubmissions();
      setSubmissions(data || []);
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
      const data = await deleteSubmission(id);

      if (data.success) {
        setMessage({ type: "success", text: "Entry deleted successfully!" });
        fetchSubmissions();
      } else {
        setMessage({
          type: "error",
          text: data.message || "Failed to delete. Please try again.",
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
      const data = await clearSubmissions();

      if (data.success) {
        setMessage({
          type: "success",
          text: "All entries cleared successfully!",
        });
        setSubmissions([]);
      } else {
        setMessage({
          type: "error",
          text: data.message || "Failed to clear entries. Please try again.",
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

  // Reset form after submission
  const resetForm = () => {
    setFormData({
      name:
        settings.isLoggedIn && settings.currentUser ? settings.currentUser : "",
      totalSales: "",
      salesCash: "",
      flowCashReceived: false,
    });
    setEditMode(false);
    setEditId(null);
  };

  return (
    <div className={`gastro-rechner-container theme-${theme}`}>
      <div className="gastro-header">
        <h2 className="gastro-rechner-title">{title}</h2>
        <div className="gastro-user-section">
          {settings.isLoggedIn ? (
            <div className="gastro-user-info">
              <span className="gastro-username">
                <i className="fas fa-user"></i> {settings.currentUser}
              </span>
              <a
                href={
                  window.gastroRechnerData?.logoutUrl ||
                  "/wp-login.php?action=logout"
                }
                className="gastro-logout-btn"
              >
                <i className="fas fa-sign-out-alt"></i> Logout
              </a>
            </div>
          ) : (
            <a
              href={window.gastroRechnerData?.loginUrl || "/wp-login.php"}
              className="gastro-login-btn"
            >
              <i className="fas fa-sign-in-alt"></i> Login
            </a>
          )}
        </div>
      </div>

      {/* Message display */}
      {message && (
        <Message message={message} onClose={() => setMessage(null)} />
      )}

      {/* Sales form - only show to users with canSubmit permission */}
      {settings.canSubmit ? (
        <Form
          formData={formData}
          setFormData={setFormData}
          settings={settings}
          editMode={editMode}
          loading={loading}
          setLoading={setLoading}
          setMessage={setMessage}
          resetForm={resetForm}
          fetchSubmissions={fetchSubmissions}
          editId={editId}
          showName={showName}
        />
      ) : settings.isLoggedIn ? (
        <div className="gastro-info-message">
          <p>You need author or administrator privileges to submit entries.</p>
        </div>
      ) : (
        <div className="gastro-info-message">
          <p>Please log in with appropriate privileges to submit entries.</p>
        </div>
      )}

      {/* Submissions table - only show if submissions exist or user is logged in */}
      {(submissions.length > 0 || settings.isLoggedIn) && (
        <Table
          submissions={submissions}
          settings={settings}
          loading={loading}
          sortConfig={sortConfig}
          setSortConfig={setSortConfig}
          handleEdit={handleEdit}
          handleView={handleView}
          handleDelete={handleDelete}
          handleClearAll={handleClearAll}
        />
      )}

      {/* Modal for viewing submission details */}
      {showModal && modalData && (
        <Modal
          modalData={modalData}
          settings={settings}
          closeModal={closeModal}
          handleEdit={handleEdit}
          handleDelete={handleDelete}
        />
      )}
    </div>
  );
};

export default App;
