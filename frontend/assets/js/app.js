// FILE: assets/js/app.js

/**
 * 1. CONFIGURATION
 * Central location for your backend URL.
 */
// FILE: assets/js/app.js

const CONFIG = {
    // UPDATED: Use your network IP so other devices can connect
    BASE_URL: "http://192.168.1.128/Hospital_Management_System/backend/index.php",
    BACKEND_ROOT: "http://192.168.1.128/Hospital_Management_System/backend"
};

/**
 * 2. API HANDLER
 * Handles all network requests with centralized error handling.
 */
const Api = {
    getToken: () => localStorage.getItem("hms_token"),

    async request(endpoint, method = "GET", body = null) {
        const headers = {
            "Content-Type": "application/json",
            "Authorization": "Bearer " + Api.getToken()
        };

        const config = { method, headers };
        if (body) config.body = JSON.stringify(body);

        try {
            const response = await fetch(`${CONFIG.BASE_URL}${endpoint}`, config);

            // Handle Unauthorized (Session Expired)
            if (response.status === 401) {
                console.warn("Session expired. Logging out.");
                logout();
                return null;
            }

            // Safety check for non-JSON responses (prevents DOCTYPE/HTML errors)
            const contentType = response.headers.get("content-type");
            if (!contentType || !contentType.includes("application/json")) {
                const text = await response.text();
                console.error("Server error (Non-JSON received):", text);
                return null;
            }

            return await response.json();
        } catch (error) {
            console.error("API Error:", error);
            return null;
        }
    },

    get: (endpoint) => Api.request(endpoint, "GET"),
    post: (endpoint, data) => Api.request(endpoint, "POST", data)
};

/**
 * 3. GLOBAL LOGOUT
 * Clears local session and redirects to the login page.
 */
async function logout(event) {
    // Only ask for confirmation if the user clicked the button manually
    if(event && event.type === 'click' && !confirm("Are you sure you want to log out?")) {
        return;
    }

    try {
        const userJson = localStorage.getItem('hms_user');
        if (userJson) {
            const user = JSON.parse(userJson);
            if (user && user.id) {
                // Fire-and-forget logout POST
                await Api.post('/auth/logout', { user_id: user.id });
            }
        }
    } catch (e) {
        console.warn('Logout API call failed', e);
    }

    // Clear all local session data
    localStorage.clear();

    // Redirect to login using absolute path
    window.location.href = "/Hospital_Management_System/frontend/pages/auth/login.html";
}

/**
 * 4. PAGE SECURITY (The "Bouncer")
 * Ensures only authorized users can access specific pages.
 */
function protectPage(allowedRoles) {
    const userJson = localStorage.getItem("hms_user");
    const token = localStorage.getItem("hms_token");

    // A. Check if logged in
    if (!userJson || !token) {
        window.location.href = "/Hospital_Management_System/frontend/pages/auth/login.html";
        return;
    }

    // B. Check if Role is Allowed
    let user;
    try {
        user = JSON.parse(userJson);
    } catch (e) {
        logout();
        return;
    }

    const rolesArray = Array.isArray(allowedRoles) ? allowedRoles : [allowedRoles];

    if (!rolesArray.includes(user.role)) {
        alert("⛔ Access Denied: You do not have permission.");

        // Redirect to their correct dashboard based on role
        if(user.role === 'doctor') window.location.href = "../doctor/dashboard.html";
        else if(user.role === 'nurse') window.location.href = "../nurse/dashboard.html";
        else if(user.role === 'admin') window.location.href = "../admin/dashboard.html";
        else if(user.role === 'receptionist') window.location.href = "../reception/dashboard.html";
        else if(user.role === 'pharmacist') window.location.href = "../pharmacy/dashboard.html";
        else logout();
    }
}

/**
 * 5. ADMIN: ADD NEW USER
 */
async function submitNewUser() {
    const nameInput = document.querySelector('#addUserForm input[type="text"]');
    const emailInput = document.querySelector('#addUserForm input[type="email"]');
    const roleSelect = document.querySelector('#addUserForm select');

    if(!nameInput || !emailInput || !roleSelect) return;

    const name = nameInput.value;
    const email = emailInput.value;
    const role = roleSelect.value;
    const password = "Staff123!";

    if(!name || !email) {
        alert("Please fill in all fields.");
        return;
    }

    const userData = {
        full_name: name,
        email: email,
        username: email.split('@')[0],
        password: password,
        role: role
    };

    const result = await Api.post('/users/create', userData);

    if (result) {
        alert("✅ User Created Successfully!\nUsername: " + userData.username + "\nPassword: " + password);
        location.reload();
    }
}