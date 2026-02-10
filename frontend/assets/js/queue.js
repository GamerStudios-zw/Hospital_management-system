/**
 * QUEUE MANAGEMENT SCRIPT
 * Requires: api.js to be loaded first
 */
const notify = window.notify || function(message, options = {}) {
    if (window.showBanner) return window.showBanner(message, options);
    if (window.showNotification) return window.showNotification(message, options);
    if (window.alert) return window.alert(message);
};

document.addEventListener("DOMContentLoaded", () => {
    loadQueue();

    // Auto-refresh queue every 30 seconds
    setInterval(loadQueue, 30000);

    // Attach listener to "Add to Queue" form if it exists
    const queueForm = document.getElementById("addToQueueForm");
    if (queueForm) {
        queueForm.addEventListener("submit", addToQueue);
    }
});

/**
 * 1. Fetch and Render the Queue Table
 */
async function loadQueue() {
    const tableBody = document.getElementById("queueTableBody");

    // Show loading state (optional)
    // tableBody.innerHTML = '<tr><td colspan="5" class="text-center">Loading...</td></tr>';

    const data = await Api.get('/queue/list');

    if (!data) return;

    tableBody.innerHTML = ""; // Clear existing rows

    if (data.length === 0) {
        tableBody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">No patients in queue</td></tr>';
        return;
    }

    data.forEach((visit, index) => {
        const row = document.createElement("tr");

        // Define Status Badge Color
        let badgeClass = "bg-secondary";
        if (visit.status === 'waiting') badgeClass = "bg-warning text-dark";
        if (visit.status === 'triaged') badgeClass = "bg-info text-dark";
        if (visit.status === 'in_consultation') badgeClass = "bg-primary";

        row.innerHTML = `
            <td>${index + 1}</td>
            <td class="fw-bold">${visit.patient_name}</td>
            <td>${visit.chief_complaint}</td>
            <td><span class="badge ${badgeClass}">${visit.status.replace('_', ' ').toUpperCase()}</span></td>
            <td>
                ${renderActions(visit)}
            </td>
        `;
        tableBody.appendChild(row);
    });
}

/**
 * 2. Helper to render buttons based on user role/status
 */
function renderActions(visit) {
    const userRole = localStorage.getItem("hms_role");

    // RECEPTIONIST ACTIONS
    if (userRole === 'receptionist' || userRole === 'admin') {
        if (visit.status === 'waiting') {
            return `<button class="btn btn-sm btn-outline-danger" onclick="cancelVisit(${visit.id})">Cancel</button>`;
        }
    }

    // NURSE ACTIONS
    if (userRole === 'nurse' || userRole === 'admin') {
        if (visit.status === 'waiting') {
            return `<button class="btn btn-sm btn-success" onclick="updateStatus(${visit.id}, 'triaged')">
                        <i class="bi bi-check-circle"></i> Vitals Taken
                    </button>`;
        }
    }

    // DOCTOR ACTIONS
    if (userRole === 'doctor' || userRole === 'admin') {
        if (visit.status === 'triaged') {
            return `<button class="btn btn-sm btn-primary" onclick="startConsultation(${visit.id})">
                        Call Patient
                    </button>`;
        }
    }

    return '<span class="text-muted">-</span>';
}

/**
 * 3. Add a new Patient to the Queue
 */
async function addToQueue(event) {
    event.preventDefault();

    const patientId = document.getElementById("patientSelect").value;
    const complaint = document.getElementById("chiefComplaint").value;

    if (!patientId || !complaint) {
        notify("Please select a patient and enter a complaint.");
        return;
    }

    const payload = {
        patient_id: patientId,
        chief_complaint: complaint
    };

    const result = await Api.post('/queue/add', payload);

    if (result) {
        notify("Patient added to queue successfully.");
        document.getElementById("addToQueueForm").reset();

        // Close modal if you are using one
        const modalEl = document.getElementById('addQueueModal');
        if(modalEl) {
            const modal = bootstrap.Modal.getInstance(modalEl);
            modal.hide();
        }

        loadQueue(); // Refresh list immediately
    }
}

/**
 * 4. Update Status (e.g., Nurse marks as Triaged)
 */
async function updateStatus(visitId, newStatus) {
    if(!confirm("Are you sure you want to update this patient's status?")) return;

    const payload = {
        visit_id: visitId,
        status: newStatus
    };

    const result = await Api.put('/queue/update', payload);

    if (result) {
        loadQueue(); // Refresh table
    }
}

/**
 * 5. Doctor Actions (Start Consultation)
 */
async function startConsultation(visitId) {
    const doctorData = JSON.parse(localStorage.getItem("hms_user"));

    const payload = {
        visit_id: visitId,
        doctor_id: doctorData.id
    };

    const result = await Api.post('/doctor/start', payload);

    if (result) {
        // Redirect doctor to the Consultation Page
        window.location.href = `../doctor/consultation.html?visit_id=${visitId}`;
    }
}
