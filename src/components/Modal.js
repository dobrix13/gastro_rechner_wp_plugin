import React from "react";
import { calculateCashOut } from "../utils/calculator";
import { formatCurrency, formatDateTime } from "../utils/formatters";

/**
 * Modal Component for displaying submission details
 */
const Modal = ({
  modalData,
  settings,
  closeModal,
  handleEdit,
  handleDelete,
}) => {
  // Convert flow_cash_received to a proper boolean
  const flowCashReceived = Boolean(parseInt(modalData.flow_cash_received));

  // Calculate the cash out amount
  const cashOut = calculateCashOut(
    modalData.total_sales,
    modalData.sales_cash,
    modalData.team_tip,
    flowCashReceived,
    modalData.flow_cash || settings.flowCash,
    settings.tipFactor
  );

  return (
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
            {formatCurrency(modalData.total_sales)} €
          </p>
          <p>
            <strong>Cash Sales:</strong> {formatCurrency(modalData.sales_cash)}{" "}
            €
          </p>
          <p>
            <strong>Team Tip ({settings.tipFactor}%):</strong>{" "}
            {formatCurrency(modalData.team_tip)} €
          </p>
          <p>
            <strong>Wechselgeld?:</strong>{" "}
            {flowCashReceived
              ? `Yes (${formatCurrency(
                  modalData.flow_cash || settings.flowCash
                )} €)`
              : "No"}
          </p>
          <p>
            <strong>Cash Out:</strong> {cashOut} €
          </p>
          <p>
            <strong>Date/Time:</strong> {formatDateTime(modalData.timestamp)}
          </p>
        </div>
        <div className="gastro-modal-footer">
          <button className="gastro-btn secondary" onClick={closeModal}>
            <i className="fas fa-times"></i> Close
          </button>

          {/* Only show edit button if user is admin or owns the entry */}
          {(settings.isAdmin || settings.userId == modalData.user_id) && (
            <button
              className="gastro-btn primary"
              onClick={() => {
                closeModal();
                handleEdit(modalData);
              }}
            >
              <i className="fas fa-edit"></i> Edit
            </button>
          )}

          {/* Only show delete button if user is admin or owns the entry */}
          {(settings.isAdmin || settings.userId == modalData.user_id) && (
            <button
              className="gastro-btn danger"
              onClick={() => {
                closeModal();
                handleDelete(modalData.id);
              }}
            >
              <i className="fas fa-trash"></i> Delete
            </button>
          )}
        </div>
      </div>
    </div>
  );
};

export default Modal;
