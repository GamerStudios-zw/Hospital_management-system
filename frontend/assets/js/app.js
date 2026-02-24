const CONFIG = {
    // UPDATED: Use your network IP so other devices can connect
    BASE_URL: `${window.location.origin}/Hospital_Management_System/backend/index.php`,
    BACKEND_ROOT: `${window.location.origin}/Hospital_Management_System/backend`,
    WS_URL: `ws://${window.location.hostname}:8090`
};

const Api = {
    getToken: () => localStorage.getItem("hms_token"),

    async request(endpoint, method = "GET", body = null) {
        const tokenAtRequest = Api.getToken();
        const headers = {
            "Content-Type": "application/json",
            "Authorization": "Bearer " + tokenAtRequest
        };

        const config = { method, headers };
        if (body) config.body = JSON.stringify(body);

        try {
            const response = await fetch(`${CONFIG.BASE_URL}${endpoint}`, config);

            // Handle Unauthorized (Session Expired)
            if (response.status === 401) {
                const currentToken = Api.getToken();
                if (currentToken && tokenAtRequest && currentToken !== tokenAtRequest) {
                    console.warn("Stale 401 ignored due to newer session token.");
                    return null;
                }
                console.warn("Session expired. Logging out.");
                logout();
                return null;
            }
            if (response.status === 503) {
                showMaintenanceOverlay();
                return null;
            }
            if (response.status === 403) {
                if (!window.__accessDeniedAlertShown) {
                    window.__accessDeniedAlertShown = true;
                    notify("Access denied. You do not have permission for this action.", "error");
                }
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

function parseBpValue(bp) {
    const m = String(bp || "").trim().match(/^(\d{2,3})\s*\/\s*(\d{2,3})$/);
    if (!m) return null;
    return { sys: Number(m[1]), dia: Number(m[2]) };
}

const VITAL_SEVERITY_META = {
    green: { rank: 0, label: "Normal", hex: "#22c55e", action: "Routine monitoring" },
    yellow: { rank: 1, label: "Mild Concern", hex: "#facc15", action: "Monitor closely" },
    orange: { rank: 2, label: "High Risk", hex: "#f97316", action: "Urgent review required" },
    red: { rank: 3, label: "Critical", hex: "#ef4444", action: "Immediate Doctor Review" }
};

function getSeverityMeta(level, labelOverride = null) {
    const key = String(level || "").trim().toLowerCase();
    const base = VITAL_SEVERITY_META[key] || VITAL_SEVERITY_META.green;
    return {
        ...base,
        level: key || "green",
        label: labelOverride || base.label
    };
}

function asBadgeStyle(hex) {
    const color = hex || "#22c55e";
    const bgMap = {
        "#22c55e": "#dcfce7",
        "#facc15": "#fef9c3",
        "#f97316": "#ffedd5",
        "#ef4444": "#fee2e2"
    };
    const background = bgMap[color.toLowerCase()] || "#f8fafc";
    return `background:${background};color:${color};border:1px solid ${color};`;
}

function escapeRiskHtml(value) {
    return String(value ?? "").replace(/[&<>"']/g, (ch) => ({
        "&": "&amp;",
        "<": "&lt;",
        ">": "&gt;",
        "\"": "&quot;",
        "'": "&#39;"
    }[ch]));
}

function levelKeywords(level) {
    const key = String(level || "").toLowerCase();
    if (key === "red") return /(critical|crisis|severe|hypotension)/i;
    if (key === "orange") return /(high|stage 2|severe)/i;
    if (key === "yellow") return /(mild|stage 1|elevated)/i;
    return /(normal)/i;
}

function getRiskTriggersFromReasons(level, reasons) {
    const list = Array.isArray(reasons) ? reasons.filter(Boolean).map((item) => String(item)) : [];
    const nonNormal = list.filter((item) => !/normal/i.test(item));
    const key = String(level || "").toLowerCase();

    if (key === "green") {
        if (!list.length) return [];
        return ["All measured vitals within normal range"];
    }

    const pool = nonNormal.length ? nonNormal : list;
    if (!pool.length) return [];

    const matcher = levelKeywords(key);
    const narrowed = pool.filter((item) => matcher.test(item));
    return narrowed.length ? narrowed : pool;
}

function classifyVitalsRisk(v) {
    const source = v || {};
    if (source && typeof source.risk_level === "string") {
        const meta = getSeverityMeta(source.risk_level, source.risk_label || null);
        const sourceReasons = Array.isArray(source.risk_reasons) ? source.risk_reasons : [];
        const sourceTriggers = Array.isArray(source.risk_triggers)
            ? source.risk_triggers.filter(Boolean).map((item) => String(item))
            : getRiskTriggersFromReasons(meta.level, sourceReasons);
        return {
            level: meta.level,
            label: meta.label,
            rank: Number.isFinite(Number(source.risk_priority)) ? Number(source.risk_priority) : meta.rank,
            hex: source.risk_hex || meta.hex,
            action: source.risk_action || meta.action,
            reasons: sourceReasons,
            triggers: sourceTriggers,
            badgeStyle: asBadgeStyle(source.risk_hex || meta.hex)
        };
    }

    const temp = Number(v.temperature);
    const pulse = Number(v.pulse);
    const spo2 = Number(v.spo2);
    const bp = parseBpValue(v.bp);
    const reasons = [];
    const levels = [];

    if (Number.isFinite(temp)) {
        if (temp >= 39.5) { levels.push("red"); reasons.push("Critical fever (>= 39.5 deg C)"); }
        else if (temp >= 38.5 && temp <= 39.4) { levels.push("orange"); reasons.push("High fever (38.5-39.4 deg C)"); }
        else if (temp >= 37.5 && temp <= 38.4) { levels.push("yellow"); reasons.push("Mild fever (37.5-38.4 deg C)"); }
        else if (temp < 35) { levels.push("red"); reasons.push("Severe hypothermia (< 35.0 deg C)"); }
        else if (temp >= 35 && temp <= 36.0) { levels.push("yellow"); reasons.push("Mild hypothermia (35.0-36.0 deg C)"); }
        else levels.push("green");
    }

    if (Number.isFinite(pulse)) {
        if (pulse > 130) { levels.push("red"); reasons.push("Critical tachycardia (> 130 bpm)"); }
        else if (pulse >= 121 && pulse <= 130) { levels.push("orange"); reasons.push("High tachycardia (121-130 bpm)"); }
        else if (pulse >= 101 && pulse <= 120) { levels.push("yellow"); reasons.push("Mild tachycardia (101-120 bpm)"); }
        else if (pulse < 50) { levels.push("red"); reasons.push("Critical bradycardia (< 50 bpm)"); }
        else if (pulse >= 50 && pulse <= 59) { levels.push("yellow"); reasons.push("Mild bradycardia (50-59 bpm)"); }
        else levels.push("green");
    }

    if (bp) {
        if (bp.sys >= 180 || bp.dia >= 120) { levels.push("red"); reasons.push("Hypertensive crisis"); }
        else if (bp.sys < 90 || bp.dia < 60) { levels.push("red"); reasons.push("Hypotension"); }
        else if ((bp.sys >= 140 && bp.sys <= 179) || (bp.dia >= 90 && bp.dia <= 119)) { levels.push("orange"); reasons.push("Hypertension stage 2"); }
        else if ((bp.sys >= 130 && bp.sys <= 139) || (bp.dia >= 80 && bp.dia <= 89)) { levels.push("yellow"); reasons.push("Hypertension stage 1"); }
        else if ((bp.sys >= 120 && bp.sys <= 129) && bp.dia < 80) { levels.push("yellow"); reasons.push("Elevated blood pressure"); }
        else levels.push("green");
    }

    if (Number.isFinite(spo2)) {
        if (spo2 < 85) { levels.push("red"); reasons.push("Critical hypoxia (SpO2 < 85%)"); }
        else if (spo2 >= 85 && spo2 <= 89) { levels.push("orange"); reasons.push("Severe hypoxia (SpO2 85-89%)"); }
        else if (spo2 >= 90 && spo2 <= 94) { levels.push("yellow"); reasons.push("Mild hypoxia (SpO2 90-94%)"); }
        else levels.push("green");
    }

    let level = "green";
    if (levels.includes("red")) level = "red";
    else if (levels.includes("orange")) level = "orange";
    else if (levels.includes("yellow")) level = "yellow";

    const meta = getSeverityMeta(level);
    const triggers = getRiskTriggersFromReasons(level, reasons);
    return {
        level,
        label: meta.label,
        rank: meta.rank,
        hex: meta.hex,
        action: meta.action,
        reasons,
        triggers,
        badgeStyle: asBadgeStyle(meta.hex)
    };
}

function renderVitalsRiskBadge(v) {
    const r = classifyVitalsRisk(v || {});
    const criticalIcon = r.level === "red" ? '<i class="bi bi-exclamation-triangle-fill me-1"></i>' : "";
    const triggerText = (Array.isArray(r.triggers) && r.triggers.length) ? ` | Triggered by: ${r.triggers.join("; ")}` : "";
    const title = escapeRiskHtml(`${r.action}${triggerText}`);
    return `<span class="badge" style="${r.badgeStyle}" title="${title}">${criticalIcon}${r.label}</span>`;
}

function renderVitalsRiskTriggers(v, options = {}) {
    const risk = classifyVitalsRisk(v || {});
    const triggers = Array.isArray(risk.triggers) ? risk.triggers : [];
    if (!triggers.length) return "";
    const className = options.className || "small text-muted mt-1";
    const prefix = options.prefix || "Triggered by";
    const text = triggers.join("; ");
    return `<div class="${escapeRiskHtml(className)}" title="${escapeRiskHtml(text)}">${escapeRiskHtml(prefix)}: ${escapeRiskHtml(text)}</div>`;
}

// Global notification modal (replaces browser alerts)
const __nativeAlert = window.alert ? window.alert.bind(window) : null;
const __bannerState = { containerId: 'appBannerContainer', styleId: 'appBannerStyles' };

function ensureBannerStyles() {
    if (document.getElementById(__bannerState.styleId)) return;
    const style = document.createElement('style');
    style.id = __bannerState.styleId;
    style.textContent = `
        #${__bannerState.containerId} {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 2000;
            display: flex;
            flex-direction: column;
            gap: 10px;
            max-width: 360px;
        }
        .app-banner {
            border-radius: 12px;
            padding: 12px 14px;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.15);
            background: #ffffff;
            border: 1px solid #e2e8f0;
            color: #0f172a;
            font-size: 0.92rem;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }
        .app-banner .dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-top: 5px;
            flex: 0 0 10px;
        }
        .app-banner.info .dot { background: #3b82f6; }
        .app-banner.success .dot { background: #10b981; }
        .app-banner.warning .dot { background: #f59e0b; }
        .app-banner.error .dot { background: #ef4444; }
    `;
    document.head.appendChild(style);
}

function ensureBannerContainer() {
    if (document.getElementById(__bannerState.containerId)) return;
    ensureBannerStyles();
    const container = document.createElement('div');
    container.id = __bannerState.containerId;
    document.body.appendChild(container);
}

function showBanner(message, options = {}) {
    if (!message) return;
    ensureBannerContainer();
    const type = options.type || 'info';
    const timeoutMs = Number.isFinite(options.timeoutMs) ? options.timeoutMs : 10000;
    const container = document.getElementById(__bannerState.containerId);
    const banner = document.createElement('div');
    banner.className = `app-banner ${type}`;
    banner.innerHTML = `<span class="dot"></span><div>${message}</div>`;
    container.appendChild(banner);
    setTimeout(() => {
        banner.remove();
    }, timeoutMs);
}

function notify(message, options = {}) {
    if (!message) return;
    const opts = typeof options === 'string' ? { type: options } : options;
    const type = opts.type || 'info';
    const timeoutMs = Number.isFinite(opts.timeoutMs) ? opts.timeoutMs : 10000;
    if (typeof showBanner === 'function') {
        showBanner(message, { type, timeoutMs });
        return;
    }
    if (typeof showNotification === 'function') {
        showNotification(message, { type, title: opts.title });
        return;
    }
    if (__nativeAlert) __nativeAlert(message);
}
function ensureNotificationModal() {
    if (document.getElementById('appNotifyModal')) return;
    const modalHtml = `
<div class="modal fade" id="appNotifyModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header">
        <h5 class="modal-title" id="appNotifyTitle">Notification</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div id="appNotifyMessage" class="text-muted"></div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-primary" data-bs-dismiss="modal">OK</button>
      </div>
    </div>
  </div>
</div>`;
    document.body.insertAdjacentHTML('beforeend', modalHtml);
}

function showNotification(message, options = {}) {
    if (typeof bootstrap === 'undefined') {
        if (__nativeAlert) __nativeAlert(message);
        return;
    }
    ensureNotificationModal();
    const title = options.title || 'Notification';
    const type = options.type || 'info';
    const modalEl = document.getElementById('appNotifyModal');
    const titleEl = document.getElementById('appNotifyTitle');
    const msgEl = document.getElementById('appNotifyMessage');
    titleEl.textContent = title;
    msgEl.textContent = message;

    titleEl.className = 'modal-title';
    if (type === 'success') titleEl.classList.add('text-success');
    if (type === 'error') titleEl.classList.add('text-danger');
    if (type === 'warning') titleEl.classList.add('text-warning');

    const modal = new bootstrap.Modal(modalEl);
    modal.show();
}

if (window.alert) {
    window.alert = (msg) => notify(msg, 'warning');
}

function ensureChangePasswordModal() {
    if (document.getElementById('changePasswordModal')) return;
    const modalHtml = `
<div class="modal fade" id="changePasswordModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Change Password</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="changePasswordForm">
          <div class="mb-3">
            <label class="form-label">Current Password</label>
            <input type="password" class="form-control" id="currentPasswordInput" required>
          </div>
          <div class="mb-3">
            <label class="form-label">New Password</label>
            <input type="password" class="form-control" id="newPasswordInput" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Confirm New Password</label>
            <input type="password" class="form-control" id="confirmPasswordInput" required>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-primary" onclick="submitChangePassword()">Update Password</button>
      </div>
    </div>
  </div>
</div>`;
    document.body.insertAdjacentHTML('beforeend', modalHtml);
}

function openChangePasswordModal() {
    ensureChangePasswordModal();
    document.getElementById('currentPasswordInput').value = '';
    document.getElementById('newPasswordInput').value = '';
    document.getElementById('confirmPasswordInput').value = '';
    const modal = new bootstrap.Modal(document.getElementById('changePasswordModal'));
    modal.show();
}

async function submitChangePassword() {
    const current = document.getElementById('currentPasswordInput').value;
    const next = document.getElementById('newPasswordInput').value;
    const confirm = document.getElementById('confirmPasswordInput').value;

    if (!current || !next) return notify("Please fill in all fields.", "warning");
    if (next !== confirm) return notify("New passwords do not match.", "warning");
    if (next.length < 6) return notify("New password must be at least 6 characters.", "warning");

    const result = await Api.post('/users', {
        action: 'change_password',
        current_password: current,
        new_password: next
    });
    if (result) {
        notify("Password updated successfully.", "success");
        const modalEl = document.getElementById('changePasswordModal');
        const modalInstance = bootstrap.Modal.getInstance(modalEl);
        if (modalInstance) modalInstance.hide();
    }
}

// Auto-refresh helper: calls known page loaders if present
const AUTO_REFRESH_INTERVAL_MS = 10000;
let __autoRefreshTimers = [];
let __broadcastChannel = null;

function getRefreshCandidates() {
    return [
        "loadUsers",
        "loadAllShifts",
        "loadQueue",
        "loadDirectory",
        "loadStaffTable",
        "loadLogs",
        "loadSettings",
        "loadAppointments",
        "loadPending",
        "loadInventory",
        "loadHistory",
        "loadRoster",
        "loadBeds",
        "loadTriageQueue",
        "loadMyShifts",
        "loadMyPatients",
        "loadReports",
        "loadMonitorData",
        "loadAdminStats",
        "loadStaffPerformance",
        "loadTasks",
        "loadEscalations",
        "loadDischarges",
        "loadHandover",
        "loadTimeline",
        "loadAdmissionWaiting",
        "loadReceptionAnalytics",
        "loadNurseAnalytics",
        "loadDoctorAnalytics",
        "loadPharmacyAnalytics",
        "loadNurseRequests",
        "loadLogAnalytics",
        "loadInteractionRules",
        "loadControlled",
        "loadRefills",
        "loadSuppliers",
        "loadPOs",
        "loadQuarantine",
        "loadAdjustments",
        "loadClaims",
        "loadQueue",
        "loadDirectory"
    ];
}

function triggerAutoRefresh() {
    const candidates = getRefreshCandidates();
    candidates.forEach((name) => {
        const fn = window[name];
        if (typeof fn === "function") {
            try { fn(); } catch (e) { /* ignore */ }
        }
    });
}

function setupAutoRefresh() {
    const candidates = getRefreshCandidates();

    const start = () => {
        if (__autoRefreshTimers.length) return;
        candidates.forEach((name) => {
            const fn = window[name];
            if (typeof fn === "function") {
                __autoRefreshTimers.push(setInterval(fn, AUTO_REFRESH_INTERVAL_MS));
            }
        });
        triggerAutoRefresh();
    };

    start();
}

function setupRealtime() {
    if (!CONFIG.WS_URL) return;
    let socket = null;
    let retryMs = 1000;

    const connect = () => {
        socket = new WebSocket(CONFIG.WS_URL);

        socket.addEventListener("open", () => {
            retryMs = 1000;
        });

        socket.addEventListener("message", (evt) => {
            let detail = { event: "refresh", payload: {} };
            try {
                const parsed = JSON.parse(evt?.data || "{}");
                if (parsed && typeof parsed === "object") {
                    detail = {
                        event: parsed.event || "refresh",
                        payload: parsed.payload || {}
                    };
                }
            } catch (e) {
                // ignore malformed socket payloads
            }

            triggerAutoRefresh();
            try {
                window.dispatchEvent(new CustomEvent("hms:realtime", { detail }));
            } catch (e) {
                // ignore event dispatch failures
            }
        });

        socket.addEventListener("close", () => {
            setTimeout(connect, retryMs);
            retryMs = Math.min(retryMs * 2, 10000);
        });
    };

    connect();
}

document.addEventListener("DOMContentLoaded", () => {
    setupAutoRefresh();
    setupRealtime();
    setupBroadcastChannel();
});

// Cross-tab refresh triggers (e.g., discharge updates)
window.addEventListener("storage", (e) => {
    if (!e || !e.key) return;
    if (e.key === "hms_discharge_event") {
        triggerAutoRefresh();
    }
});

function setupBroadcastChannel() {
    if (typeof BroadcastChannel === "undefined") return;
    if (__broadcastChannel) return;
    __broadcastChannel = new BroadcastChannel("hms_events");
    __broadcastChannel.onmessage = () => {
        triggerAutoRefresh();
    };
}

function broadcastEvent(name, payload = {}) {
    if (!__broadcastChannel) setupBroadcastChannel();
    if (!__broadcastChannel) return;
    try {
        __broadcastChannel.postMessage({ name, payload, ts: Date.now() });
    } catch (e) {
        // ignore
    }
}
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
        const token = localStorage.getItem('hms_token');
        if (userJson) {
            const user = JSON.parse(userJson);
            if (user && user.id) {
                // Fire-and-forget logout POST
                await Api.post('/auth/logout', { user_id: user.id, token });
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

// Graceful logout on tab close / navigation away
function setupAutoLogoutOnClose() {
    const sendLogoutBeacon = () => {
        try {
            const token = localStorage.getItem('hms_token');
            const userJson = localStorage.getItem('hms_user');
            const user = userJson ? JSON.parse(userJson) : null;
            if (!token || !user || !user.id) return;
            const url = `${CONFIG.BASE_URL}/auth/logout`;
            const payload = JSON.stringify({ user_id: user.id, token });
            if (navigator.sendBeacon) {
                navigator.sendBeacon(url, new Blob([payload], { type: 'application/json' }));
            } else {
                fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: payload, keepalive: true });
            }
        } catch (e) {
            // ignore
        }
    };

    window.addEventListener('pagehide', sendLogoutBeacon);
    window.addEventListener('beforeunload', sendLogoutBeacon);
}

document.addEventListener("DOMContentLoaded", setupAutoLogoutOnClose);
document.addEventListener("DOMContentLoaded", ensureItTicketWidget);
function normalizeRole(role) {
    return String(role || "")
        .trim()
        .toLowerCase()
        .replace(/[\s-]+/g, "_");
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

    const normalizedUserRole = normalizeRole(user.role);
    const rolesArray = (Array.isArray(allowedRoles) ? allowedRoles : [allowedRoles]).map(normalizeRole);

    if (!rolesArray.includes(normalizedUserRole)) {
        notify("⛔ Access Denied: You do not have permission.", "error");

        // Redirect to their correct dashboard based on role
        if(normalizedUserRole === 'doctor') window.location.href = "../doctor/dashboard.html";
        else if(normalizedUserRole === 'nurse') window.location.href = "../nurse/dashboard.html";
        else if(normalizedUserRole === 'nurse_aid') window.location.href = "../nurse_aid/dashboard.html";
        else if(normalizedUserRole === 'admin') window.location.href = "../admin/dashboard.html";
        else if(normalizedUserRole === 'receptionist') window.location.href = "../reception/dashboard.html";
        else if(normalizedUserRole === 'pharmacist' || normalizedUserRole === 'senior_pharmacist') window.location.href = "../pharmacy/dashboard.html";
        else if(normalizedUserRole === 'it_support') window.location.href = "../it/itdashboard.html";
        else logout();
    }
}

/**
 * 4B. IT TICKET WIDGET (All authenticated users)
 */
function ensureItTicketWidget() {
    const token = localStorage.getItem("hms_token");
    const userJson = localStorage.getItem("hms_user");
    if (!token || !userJson) return;
    if (document.getElementById("itTicketFab")) return;

    const fab = document.createElement("button");
    fab.id = "itTicketFab";
    fab.className = "btn btn-primary shadow";
    fab.style.position = "fixed";
    fab.style.right = "20px";
    fab.style.bottom = "20px";
    fab.style.zIndex = "999";
    fab.innerHTML = '<i class="bi bi-life-preserver me-1"></i>IT Ticket';
    fab.addEventListener("click", openItTicketModal);
    document.body.appendChild(fab);

    const modalHtml = `
<div class="modal fade" id="itTicketModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Submit IT Ticket</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">Title</label>
          <input type="text" class="form-control" id="itTicketTitle" placeholder="Short summary">
        </div>
        <div class="mb-3">
          <label class="form-label">Description</label>
          <textarea class="form-control" id="itTicketDesc" rows="4" placeholder="Describe the issue"></textarea>
        </div>
        <div class="mb-3">
          <label class="form-label">Priority</label>
          <select class="form-select" id="itTicketPriority">
            <option value="normal">Normal</option>
            <option value="low">Low</option>
            <option value="high">High</option>
            <option value="urgent">Urgent</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-primary" onclick="submitItTicket()">Submit</button>
      </div>
    </div>
  </div>
</div>`;
    document.body.insertAdjacentHTML('beforeend', modalHtml);
}

function openItTicketModal() {
    const modalEl = document.getElementById('itTicketModal');
    if (!modalEl || typeof bootstrap === 'undefined') return;
    new bootstrap.Modal(modalEl).show();
}

async function submitItTicket() {
    const title = (document.getElementById('itTicketTitle') || {}).value || '';
    const description = (document.getElementById('itTicketDesc') || {}).value || '';
    const priority = (document.getElementById('itTicketPriority') || {}).value || 'normal';
    if (!title.trim() || !description.trim()) return notify("Title and description are required.", "warning");
    const res = await Api.post('/it/ticket_create', { title: title.trim(), description: description.trim(), priority });
    if (res) {
        document.getElementById('itTicketTitle').value = '';
        document.getElementById('itTicketDesc').value = '';
        const modalEl = document.getElementById('itTicketModal');
        const modal = bootstrap.Modal.getInstance(modalEl);
        if (modal) modal.hide();
        notify("Ticket submitted to IT Support.", "success");
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
        notify("Please fill in all fields.", "warning");
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
        notify("✅ User Created Successfully! Username: " + userData.username + " | Password: " + password, "success");
        location.reload();
    }
}

function showMaintenanceOverlay() {
    if (document.getElementById('maintenanceOverlay')) return;
    const overlay = document.createElement('div');
    overlay.id = 'maintenanceOverlay';
    overlay.style.position = 'fixed';
    overlay.style.inset = '0';
    overlay.style.background = 'rgba(15, 35, 58, 0.85)';
    overlay.style.color = '#fff';
    overlay.style.zIndex = '2000';
    overlay.style.display = 'flex';
    overlay.style.alignItems = 'center';
    overlay.style.justifyContent = 'center';
    overlay.innerHTML = `
        <div style="max-width: 520px; text-align: center; padding: 24px; background: #1f3c5c; border-radius: 12px;">
            <h4 style="margin-bottom: 10px;">Maintenance Mode</h4>
            <p style="margin-bottom: 16px;">The system is temporarily unavailable. Please try again later.</p>
            <button id="maintenanceLogoutBtn" style="border: 0; padding: 10px 16px; border-radius: 8px; background: #e44a3c; color: #fff;">Logout</button>
        </div>
    `;
    document.body.appendChild(overlay);
    const btn = document.getElementById('maintenanceLogoutBtn');
    if (btn) btn.onclick = () => logout();
}
