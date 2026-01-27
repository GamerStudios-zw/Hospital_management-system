// FILE: assets/js/app.js

/**
 * 1. CONFIGURATION
 * Central location for your backend URL.
 */
const CONFIG = {
    // Ensure this matches your XAMPP folder name exactly
    BASE_URL: "http://localhost/Hospital_Management_System/backend/index.php"
};

/**
 * 2. API HANDLER
 * Used by Login, Contact Form, and Data Fetching
 */
const Api = {
    getToken: () => localStorage.getItem("hms_token"),

    async request(endpoint, method = "GET", body = null) {
        const headers = {
            "Content-Type": "application/json",
            "Authorization": `Bearer ${this.getToken()}`
        };

        const config = { method, headers };
        if (body) config.body = JSON.stringify(body);

        try {
            const response = await fetch(`${CONFIG.BASE_URL}${endpoint}`, config);

            // Handle Unauthorized (Session Expired)
            if (response.status === 401) {
                logout(); // Call the global logout function
                return null;
            }

            return await response.json();
        } catch (error) {
            console.error("API Error:", error);
            alert("Network error. Please check your connection or backend.");
            return null;
        }
    },

    get: (endpoint) => Api.request(endpoint, "GET"),
    post: (endpoint, data) => Api.request(endpoint, "POST", data)
};

/**
 * 3. GLOBAL LOGOUT
 * Clears session and redirects to the login page.
 */
function logout() {
    if(confirm("Are you sure you want to log out?")) {
        localStorage.removeItem("hms_token");
        localStorage.removeItem("hms_user");
        localStorage.removeItem("hms_role");

        // Go up two levels to find the login page
        // Adjust this if your folder structure is different
        window.location.href = "../../pages/auth/login.html";
    }
}

/**
 * 4. PAGE SECURITY (The "Bouncer")
 * Checks if the user is allowed to be on this page.
 */
function protectPage(allowedRoles) {
    const userJson = localStorage.getItem("hms_user");
    const token = localStorage.getItem("hms_token");

    // A. Check if logged in
    if (!userJson || !token) {
        alert("You must be logged in to view this page.");
        window.location.href = "../../pages/auth/login.html";
        return;
    }

    // B. Check if Role is Allowed
    let user;
    try {
        user = JSON.parse(userJson);
    } catch (e) {
        // If JSON is corrupt, log them out
        logout();
        return;
    }

    // Ensure allowedRoles is an array (even if you passed a single string)
    const rolesArray = Array.isArray(allowedRoles) ? allowedRoles : [allowedRoles];

    if (!rolesArray.includes(user.role)) {
        alert("⛔ Access Denied: You do not have permission.");

        // Redirect them to their CORRECT dashboard
        if(user.role === 'doctor') window.location.href = "../doctor/dashboard.html";
        else if(user.role === 'nurse') window.location.href = "../nurse/dashboard.html";
        else if(user.role === 'admin') window.location.href = "../admin/dashboard.html";
        else if(user.role === 'receptionist') window.location.href = "../reception/receptionist.html";
        else if(user.role === 'pharmacist') window.location.href = "../pharmacy/dashboard.html";
        else logout();
    }
}

/**
 * 5. ADMIN: ADD NEW USER
 * Handles the form submission from the Admin Dashboard
 */
async function submitNewUser() {
    // 1. Get Data from HTML Form
    const name = document.querySelector('#addUserForm input[type="text"]').value;
    const email = document.querySelector('#addUserForm input[type="email"]').value;
    const role = document.querySelector('#addUserForm select').value;
    const password = "Staff123!"; // Default temporary password

    // 2. Simple Validation
    if(!name || !email) {
        alert("Please fill in all fields.");
        return;
    }

    // 3. Prepare Data Object
    const userData = {
        full_name: name,
        email: email,
        username: email.split('@')[0], // Auto-generate username from email
        password: password,
        role: role
    };

    // 4. Send to Backend
    // Note: We use the existing Api.post helper we wrote earlier
    const result = await Api.post('/users/create', userData);

    if (result) {
        alert("✅ User Created Successfully!\nUsername: " + userData.username + "\nPassword: " + password);
        location.reload(); // Refresh page to see new user
    }
}