/**
 * API Service Wrapper
 * Centralizes all backend communication configurations
 */

const CONFIG = {
    // Ensure this path matches your actual backend folder structure
    BASE_URL: "http://localhost/Hospital_Management_System/backend/index.php"
};
const notify = window.notify || function(message, options = {}) {
    if (window.showBanner) return window.showBanner(message, options);
    if (window.showNotification) return window.showNotification(message, options);
    if (window.alert) return window.alert(message);
};

const Api = {
    /**
     * Helper to get the token from storage
     */
    getToken: () => localStorage.getItem("hms_token"),

    /**
     * Generic request handler
     */
    async request(endpoint, method = "GET", body = null) {
        const headers = {
            "Content-Type": "application/json",
            "Authorization": "Bearer " + Api.getToken()
        };

        const config = {
            method,
            headers
        };

        if (body) {
            config.body = JSON.stringify(body);
        }

        try {
            const response = await fetch(`${CONFIG.BASE_URL}${endpoint}`, config);

            // Strict session handling: force logout on 401
            if (response.status === 401) {
                notify("Session expired. Please login again.");
                localStorage.clear();
                window.location.href = "/Hospital_Management_System/frontend/pages/auth/login.html";
                return null;
            }

            // Handle Forbidden Access (403)
            if (response.status === 403) {
                const err = await response.json();
                notify(err.message || "You do not have permission to perform this action.");
                return null;
            }

            return await response.json();

        } catch (error) {
            console.error("API Error:", error);
            notify("Network error. Please check your connection.");
            return null;
        }
    },

    // Shorthand methods
    get: (endpoint) => Api.request(endpoint, "GET"),
    post: (endpoint, data) => Api.request(endpoint, "POST", data),
    put: (endpoint, data) => Api.request(endpoint, "PUT", data),
    delete: (endpoint) => Api.request(endpoint, "DELETE")
};

// ... (Keep your existing CONFIG and Api code at the top) ...

/* ==========================================================
   GLOBAL HELPER FUNCTIONS (Add this to the bottom of app.js)
   ========================================================== */

/**
 * Global Logout Function
 * Clears session and redirects to home
 */
async function logout() {
    if(!confirm("Are you sure you want to log out?")) return;

    try {
        // Attempt to notify backend; Api.post will attach Authorization header from localStorage
        await Api.post('/auth/logout', {});
    } catch (e) {
        console.warn('Logout API failed', e);
    }

    // Clear client-side session and redirect
    localStorage.removeItem("hms_token");
    localStorage.removeItem("hms_user");
    localStorage.removeItem("hms_role");

    // Redirect to login page
    window.location.href = "/Hospital_Management_System/frontend/pages/auth/login.html";
}

/**
 * Page Security / Access Control
 * Checks if user is logged in and has the correct role
 */
function protectPage(allowedRoles) {
    const userJson = localStorage.getItem("hms_user");
    const token = localStorage.getItem("hms_token");
    
    // 1. Check if logged in
    if (!userJson || !token) {
        notify("Please log in to access this page.");
        window.location.href = "../auth/login.html"; 
        return;
    }

    // 2. Check Role
    const user = JSON.parse(userJson);
    if (!allowedRoles.includes(user.role)) {
        notify("⛔ Access Denied: You do not have permission.");
        // Redirect back to their correct dashboard based on their actual role
        if(user.role === 'doctor') window.location.href = "../doctor/dashboard.html";
        else if(user.role === 'nurse') window.location.href = "../nurse/dashboard.html";
        else if(user.role === 'admin') window.location.href = "../admin/dashboard.html";
        else window.location.href = "../auth/login.html";
    }
}
