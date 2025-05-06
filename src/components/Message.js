import React from "react";

/**
 * Message Component for displaying notifications
 */
const Message = ({ message, onClose }) => {
  return (
    <div className={`gastro-message ${message.type}`}>
      <p>{message.text}</p>
      <button className="gastro-close-btn" onClick={onClose}>
        ×
      </button>
    </div>
  );
};

export default Message;
