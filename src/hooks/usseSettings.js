import { useState, useEffect } from "react";

/**
 * Custom hook for managing application settings
 *
 * Loads and provides access to settings from WordPress
 *
 * @returns {Object} The settings object and loading state
 */
const useSettings = () => {
  // State for settings
  const [settings, setSettings] = useState({
    flowCash: 100,
    tipFactor: 2,
    showFlowCashToggle: true,
    currentUser: "",
    isLoggedIn: false,
    defaultColorScheme: "light-cold",
  });

  const [loading, setLoading] = useState(true);

  // Load settings from WordPress on mount
  useEffect(() => {
    loadSettings();
  }, []);

  // Function to load settings from WordPress
  const loadSettings = () => {
    if (window.gastroRechnerData) {
      const wordpressSettings = window.gastroRechnerData.settings || {};
      setSettings(wordpressSettings);
    }
    setLoading(false);
  };

  return { settings, loading };
};

export default useSettings;
