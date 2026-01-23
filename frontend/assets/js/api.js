/**
 * API Service Wrapper
 * Centralizes all backend communication configurations
 */

const CONFIG = {
    // Ensure this path matches your actual backend folder structure
    BASE_URL: "http://localhost/Hospital_Management_System/backend/index.php"
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
            "Authorization": `Bearer ${this.getToken()}`
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

            // Handle Token Expiry (401 Unauthorized)
            if (response.status === 401) {
                alert("Session expired. Please login again.");
                localStorage.clear();
                window.location.href = "/Hospital_Management_System/frontend/pages/auth/login.html";
                return null;
            }

            // Handle Forbidden Access (403)
            if (response.status === 403) {
                const err = await response.json();
                alert(err.message || "You do not have permission to perform this action.");
                return null;
            }

            return await response.json();

        } catch (error) {
            console.error("API Error:", error);
            alert("Network error. Please check your connection.");
            return null;
        }
    },

    // Shorthand methods
    get: (endpoint) => Api.request(endpoint, "GET"),
    post: (endpoint, data) => Api.request(endpoint, "POST", data),
    put: (endpoint, data) => Api.request(endpoint, "PUT", data),
    delete: (endpoint) => Api.request(endpoint, "DELETE")
};