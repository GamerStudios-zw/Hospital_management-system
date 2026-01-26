const API_BASE_URL = "http://localhost/Hospital_Management_System/backend/index.php";

/**
 * Log the user out by clearing storage and redirecting
 */
function logout() {
    // 1. Optional: Call backend to invalidate token (if you implement blocklist)
    // fetch(`${API_BASE_URL}/auth/logout`, { method: 'POST' });

    // 2. Clear Local Storage
    localStorage.removeItem("hms_token");
    localStorage.removeItem("hms_role");
    localStorage.removeItem("hms_user");

    // 3. Redirect to Login
    window.location.href = "/Hospital_Management_System/frontend/pages/auth/login.html";
}

/**
 * Protect a page to ensure only specific roles can access it
 * @param {string|string[]} allowedRoles - Single role string or array of allowed roles
 */
function protectPage(allowedRoles) {
    const token = localStorage.getItem("hms_token");
    const userRole = localStorage.getItem("hms_role");

    // 1. Check if token exists
    if (!token || !userRole) {
        alert("You must be logged in to view this page.");
        window.location.href = "/Hospital_Management_System/frontend/pages/auth/login.html";
        return;
    }

    // 2. Check if the user's role is allowed
    // Convert single string to array for easier checking
    const rolesArray = Array.isArray(allowedRoles) ? allowedRoles : [allowedRoles];

    if (!rolesArray.includes(userRole)) {
        alert("Access Denied: You do not have permission to view this page.");
        // Redirect to their correct dashboard or logout
        logout(); 
    }
}

/**
 * Helper to make Authenticated API Requests
 * Use this instead of plain fetch() for protected backend routes
 */
async function authFetch(endpoint, options = {}) {
    const token = localStorage.getItem("hms_token");

    // Ensure headers exist
    options.headers = options.headers || {};
    
    // Add Authorization Header
    options.headers["Authorization"] = `Bearer ${token}`;
    options.headers["Content-Type"] = "application/json";

    const response = await fetch(`${API_BASE_URL}${endpoint}`, options);
    
    // If token is expired (401), force logout
    if (response.status === 401) {
        logout();
        return null;
    }

    return response;
}
// ... (Keep your existing CONFIG and Api code at the top) ...

/* ==========================================================
   GLOBAL HELPER FUNCTIONS (Add this to the bottom of app.js)
   ========================================================== */

/**
 * Global Logout Function
 * Clears session and redirects to home
 */
function logout() {
    if(confirm("Are you sure you want to log out?")) {
        localStorage.removeItem("hms_token");
        localStorage.removeItem("hms_user");
        localStorage.removeItem("hms_role");

        // Redirect to login page
        window.location.href = "../../index.html";
    }
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
        alert("Please log in to access this page.");
        window.location.href = "../auth/login.html";
        return;
    }

    // 2. Check Role
    const user = JSON.parse(userJson);
    if (!allowedRoles.includes(user.role)) {
        alert("⛔ Access Denied: You do not have permission.");
        // Redirect back to their correct dashboard based on their actual role
        if(user.role === 'doctor') window.location.href = "../doctor/dashboard.html";
        else if(user.role === 'nurse') window.location.href = "../nurse/dashboard.html";
        else if(user.role === 'admin') window.location.href = "../admin/dashboard.html";
        else window.location.href = "../auth/login.html";
    }
}