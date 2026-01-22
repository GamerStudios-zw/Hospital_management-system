function login() {
    const role = document.getElementById("role").value;

    if (!role) {
        alert("Please select a role");
        return;
    }

    localStorage.setItem("hms_token", "demo-token");
    localStorage.setItem("hms_role", role);

    window.location.href = "../../index.html";
}
