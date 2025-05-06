import React from "react";
import { createRoot } from "react-dom/client";
import App from "./app";
import "./styles.css";

// Render the app when the DOM is ready
document.addEventListener("DOMContentLoaded", () => {
  const container = document.getElementById("gastro-rechner-root");
  if (container) {
    const root = createRoot(container);
    root.render(<App />);
  }
});
