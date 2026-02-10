// Global API Configuration
const API_BASE_URL = "http://localhost/Hospital_Management_System/backend/index.php";
const notify = window.notify || function(message, options = {}) {
    if (window.showBanner) return window.showBanner(message, options);
    if (window.showNotification) return window.showNotification(message, options);
    if (window.alert) return window.alert(message);
};

async function login(event) {
    // Prevent the form from refreshing the page
    if(event) event.preventDefault();

    const usernameInput = document.getElementById("email"); // Assuming ID is email/username
    const passwordInput = document.getElementById("password");
    const roleInput = document.getElementById("role"); // Optional if your login doesn't ask for role explicitly

    const username = usernameInput ? usernameInput.value : "";
    const password = passwordInput ? passwordInput.value : "";

    if (!username || !password) {
        notify("Please enter both username/email and password.");
        return;
    }

    const loginData = {
        username: username,
        password: password
    };

    try {
        const response = await fetch(`${API_BASE_URL}/auth/login`, {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify(loginData)
        });

        const result = await response.json();

        if (response.ok && result.status === "success") {
            // 1. Save Token and User Data
            localStorage.setItem("hms_token", result.data.token);
            localStorage.setItem("hms_user", JSON.stringify(result.data.user));
            localStorage.setItem("hms_role", result.data.user.role);

            // 2. Redirect based on Role
            const role = result.data.user.role;
            switch(role) {
                case 'doctor':
                    window.location.href = "../../pages/doctor/dashboard.html"; // Update path as needed
                    break;
                case 'nurse':
                    window.location.href = "../../pages/nurse/dashboard.html";
                    break;
                case 'pharmacist':
                    window.location.href = "../../pages/pharmacy/dashboard.html";
                    break;
                case 'receptionist':
                    window.location.href = "../../pages/reception/dashboard.html";
                    break;
                case 'admin':
                    window.location.href = "../../pages/admin/dashboard.html";
                    break;
                default:
                    window.location.href = "../../index.html";
            }
        } else {
            notify(result.message || "Login failed. Please check credentials.");
        }

    } catch (error) {
        console.error("Login Error:", error);
        notify("Unable to connect to the server.");
    }
}

// Attach listener if the form exists
const loginForm = document.getElementById("loginForm");
if (loginForm) {
    loginForm.addEventListener("submit", login);
}
