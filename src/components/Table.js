import React from "react";
import { jsPDF } from "jspdf";
import "jspdf-autotable";
import { calculateCashOut, calculateTotals } from "../utils/calculator";
import {
  formatCurrency,
  formatDateTime,
  formatTime,
} from "../utils/formatters";

/**
 * Table Component for displaying submissions
 */
const Table = ({
  submissions,
  settings,
  loading,
  sortConfig,
  setSortConfig,
  handleEdit,
  handleView,
  handleDelete,
  handleClearAll,
}) => {
  // Function to get class names for sortable headers
  const getColumnClass = (key) => {
    if (sortConfig.key === key) {
      return `sort-${sortConfig.direction}`;
    }
    return "";
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

  // Function to export submissions to PDF
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

      // Calculate totals
      const totals = calculateTotals(submissions, settings);

      // Prepare table data
      const tableData = getSortedSubmissions().map((submission) => {
        // Convert flow_cash_received to a proper boolean for display and calculations
        const flowCashReceived = Boolean(
          parseInt(submission.flow_cash_received)
        );

        const cashOut = calculateCashOut(
          submission.total_sales,
          submission.sales_cash,
          submission.team_tip,
          flowCashReceived,
          submission.flow_cash || settings.flowCash,
          settings.tipFactor
        );

        // Format date with full date and time for PDF export
        const fullDateTime = formatDateTime(submission.timestamp);

        return [
          submission.name,
          `${formatCurrency(submission.total_sales)} €`,
          `${formatCurrency(submission.sales_cash)} €`,
          `${formatCurrency(submission.team_tip)} €`,
          flowCashReceived ? "Yes" : "No",
          `${cashOut} €`,
          fullDateTime,
        ];
      });

      // Add totals row if available
      if (totals) {
        tableData.push([
          "TOTALS",
          `${formatCurrency(totals.totalSales)} €`,
          `${formatCurrency(totals.salesCash)} €`,
          `${formatCurrency(totals.teamTip)} €`,
          "-",
          `${formatCurrency(totals.cashOut)} €`,
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

      return fileName;
    } catch (error) {
      console.error("PDF Export error:", error);
      throw error;
    }
  };

  const totals = calculateTotals(submissions, settings);

  return (
    <div className="gastro-table-container">
      <h3>Abrechnungen</h3>

      {submissions.length > 0 ? (
        <>
          <div className="gastro-table">
            <div className="gastro-table-header">
              <div
                className={`gastro-table-cell name ${getColumnClass("name")}`}
                onClick={() => handleSort("name")}
              >
                Name
              </div>
              <div
                className={`gastro-table-cell number ${getColumnClass(
                  "total_sales"
                )}`}
                onClick={() => handleSort("total_sales")}
              >
                Umsatz (€)
              </div>
              <div
                className={`gastro-table-cell number ${getColumnClass(
                  "sales_cash"
                )}`}
                onClick={() => handleSort("sales_cash")}
              >
                Bargeld (€)
              </div>
              <div
                className={`gastro-table-cell number ${getColumnClass(
                  "team_tip"
                )}`}
                onClick={() => handleSort("team_tip")}
              >
                Team-Tip (€)
              </div>
              <div className="gastro-table-cell toggle">Wechselgeld</div>
              <div
                className={`gastro-table-cell number ${getColumnClass(
                  "cash_out"
                )}`}
                onClick={() => handleSort("cash_out")}
              >
                Cash Out (€)
              </div>
              <div
                className={`gastro-table-cell ${getColumnClass("timestamp")}`}
                onClick={() => handleSort("timestamp")}
              >
                Time
              </div>
              <div className="gastro-table-cell actions">Actions</div>
            </div>

            <div className="gastro-table-rows">
              {getSortedSubmissions().map((submission) => {
                // Convert flow_cash_received to a proper boolean for display and calculations
                const flowCashReceived = Boolean(
                  parseInt(submission.flow_cash_received)
                );

                const cashOut = calculateCashOut(
                  submission.total_sales,
                  submission.sales_cash,
                  submission.team_tip,
                  flowCashReceived,
                  submission.flow_cash || settings.flowCash,
                  settings.tipFactor
                );

                // Format time
                const timeString = formatTime(submission.timestamp);

                return (
                  <div
                    key={submission.id}
                    className="gastro-table-row"
                    onClick={() => handleView(submission)}
                  >
                    <div className="gastro-table-cell name" data-label="Name">
                      {submission.name}
                    </div>
                    <div
                      className="gastro-table-cell number"
                      data-label="Umsatz (€)"
                    >
                      {formatCurrency(submission.total_sales)} €
                    </div>
                    <div
                      className="gastro-table-cell number"
                      data-label="Bargeld (€)"
                    >
                      {formatCurrency(submission.sales_cash)} €
                    </div>
                    <div
                      className="gastro-table-cell number"
                      data-label="Team-Tip (€)"
                    >
                      {formatCurrency(submission.team_tip)} €
                    </div>
                    <div
                      className="gastro-table-cell toggle"
                      data-label="Wechselgeld"
                    >
                      {flowCashReceived
                        ? `Yes (${formatCurrency(
                            submission.flow_cash || settings.flowCash
                          )} €)`
                        : "No"}
                    </div>
                    <div
                      className="gastro-table-cell number"
                      data-label="Cash Out (€)"
                    >
                      {cashOut} €
                    </div>
                    <div className="gastro-table-cell" data-label="Time">
                      {timeString}
                    </div>
                    <div
                      className="gastro-table-cell actions"
                      onClick={(e) => e.stopPropagation()}
                    >
                      <div className="gastro-actions">
                        <button
                          onClick={() => handleView(submission)}
                          className="gastro-action-btn view"
                          title="View Details"
                        >
                          <i className="fas fa-eye"></i>
                        </button>

                        {/* Only show edit button if user is admin or owns the entry */}
                        {(settings.isAdmin ||
                          settings.userId == submission.user_id) && (
                          <button
                            onClick={() => handleEdit(submission)}
                            className="gastro-action-btn edit"
                            title="Edit"
                          >
                            <i className="fas fa-edit"></i>
                          </button>
                        )}

                        {/* Only show delete button if user is admin or owns the entry */}
                        {(settings.isAdmin ||
                          settings.userId == submission.user_id) && (
                          <button
                            onClick={() => handleDelete(submission.id)}
                            className="gastro-action-btn delete"
                            title="Delete"
                          >
                            <i className="fas fa-trash"></i>
                          </button>
                        )}
                      </div>
                    </div>
                  </div>
                );
              })}
            </div>

            {totals && (
              <div className="gastro-table-footer">
                <div className="gastro-table-cell name" data-label="Totals">
                  <strong>Totals</strong>
                </div>
                <div
                  className="gastro-table-cell number"
                  data-label="Umsatz (€)"
                >
                  <strong>{formatCurrency(totals.totalSales)} €</strong>
                </div>
                <div
                  className="gastro-table-cell number"
                  data-label="Bargeld (€)"
                >
                  <strong>{formatCurrency(totals.salesCash)} €</strong>
                </div>
                <div
                  className="gastro-table-cell number"
                  data-label="Team-Tip (€)"
                >
                  <strong>{formatCurrency(totals.teamTip)} €</strong>
                </div>
                <div
                  className="gastro-table-cell toggle"
                  data-label="Wechselgeld"
                >
                  -
                </div>
                <div
                  className="gastro-table-cell number"
                  data-label="Cash Out (€)"
                >
                  <strong>{formatCurrency(totals.cashOut)} €</strong>
                </div>
                <div className="gastro-table-cell actions" colSpan="2">
                  {settings.isAdmin && (
                    <button
                      className="clear-all-footer"
                      onClick={handleClearAll}
                      disabled={loading}
                    >
                      <i className="fas fa-trash"></i> Clear All
                    </button>
                  )}
                </div>
              </div>
            )}
          </div>

          <div className="gastro-table-actions">
            <button
              className="gastro-btn primary"
              onClick={() => {
                try {
                  const fileName = handleExportToPdf();
                  alert(`PDF successfully exported as "${fileName}"`);
                } catch (error) {
                  alert("Failed to export PDF. Please try again.");
                }
              }}
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
  );
};

export default Table;
