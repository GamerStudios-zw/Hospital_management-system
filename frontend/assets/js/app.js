function logout() {
    localStorage.clear();
    window.location.href = "../../index.html";
}

function protectPage(expectedRole) {
    const token = localStorage.getItem("hms_token");
    const role = localStorage.getItem("hms_role");

    if (!token || role !== expectedRole) {
        logout();
    }
}
