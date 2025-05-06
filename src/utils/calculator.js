/**
 * Calculator utility functions
 */

/**
 * Calculate team tip
 *
 * @param {number|string} totalSales - Total sales amount
 * @param {number|string} tipFactor - Tip factor percentage
 * @returns {string} - Formatted tip amount with 2 decimal places
 */
export const calculateTip = (totalSales, tipFactor) => {
  return (parseFloat(totalSales) * (parseFloat(tipFactor) / 100)).toFixed(2);
};

/**
 * Calculate cash out amount
 *
 * @param {number|string} totalSales - Total sales amount
 * @param {number|string} salesCash - Cash sales amount
 * @param {number|string} teamTip - Team tip amount
 * @param {boolean} flowCashReceived - Whether flow cash was received
 * @param {number|string} flowCash - Flow cash amount
 * @param {number|string} tipFactor - Tip factor percentage
 * @returns {string} - Formatted cash out amount with 2 decimal places
 */
export const calculateCashOut = (
  totalSales,
  salesCash,
  teamTip,
  flowCashReceived,
  flowCash,
  tipFactor
) => {
  // Use flow_cash value when checkbox is active, otherwise use 0
  const flowCashAmount = flowCashReceived ? parseFloat(flowCash) : 0;

  // Apply the formula: daily_sales * (tip_factor / 100) + sales_cash + flow_cash
  return (
    parseFloat(totalSales) * (parseFloat(tipFactor) / 100) +
    parseFloat(salesCash) +
    flowCashAmount
  ).toFixed(2);
};

/**
 * Calculate totals from all submissions
 *
 * @param {Array} submissions - Array of submission objects
 * @param {Object} settings - Settings object with tipFactor and flowCash
 * @returns {Object|null} - Object with totals or null if no submissions
 */
export const calculateTotals = (submissions, settings) => {
  if (!submissions.length) return null;

  const totals = submissions.reduce(
    (acc, submission) => {
      // Convert flow_cash_received to a proper boolean for calculations
      const flowCashReceived = Boolean(parseInt(submission.flow_cash_received));

      // Calculate cash out for this submission
      const cashOutAmount =
        parseFloat(submission.total_sales) * (settings.tipFactor / 100) +
        parseFloat(submission.sales_cash) +
        (flowCashReceived
          ? parseFloat(submission.flow_cash || settings.flowCash)
          : 0);

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
