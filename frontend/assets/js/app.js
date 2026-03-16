const CONFIG = {
    // UPDATED: Use your network IP so other devices can connect
    BASE_URL: `${window.location.origin}/Hospital_Management_System/backend/index.php`,
    BACKEND_ROOT: `${window.location.origin}/Hospital_Management_System/backend`,
    WS_URL: `ws://${window.location.hostname}:8090`
};

const LOGIN_PAGE_PATH = "/Hospital_Management_System/frontend/pages/auth/login.html";
const TOKEN_EXPIRY_GRACE_MS = 5000;
const SESSION_WATCHDOG_INTERVAL_MS = 15000;
let __isLoggingOut = false;
let __sessionWatchdogTimer = null;

function decodeJwtPayload(token) {
    if (!token || typeof token !== "string" || typeof atob !== "function") return null;
    const parts = token.split(".");
    if (parts.length < 2) return null;

    try {
        const raw = parts[1].replace(/-/g, "+").replace(/_/g, "/");
        const padded = raw + "=".repeat((4 - (raw.length % 4)) % 4);
        const decoded = atob(padded);
        try {
            const escaped = Array.from(decoded, (ch) => `%${ch.charCodeAt(0).toString(16).padStart(2, "0")}`).join("");
            return JSON.parse(decodeURIComponent(escaped));
        } catch (unicodeErr) {
            return JSON.parse(decoded);
        }
    } catch (e) {
        return null;
    }
}

function getTokenExpiryMs(token = localStorage.getItem("hms_token")) {
    const payload = decodeJwtPayload(token);
    const exp = Number(payload?.exp);
    if (!Number.isFinite(exp) || exp <= 0) return null;
    return exp * 1000;
}

function isTokenExpired(token = localStorage.getItem("hms_token")) {
    if (!token) return true;
    const expiryMs = getTokenExpiryMs(token);
    if (!Number.isFinite(expiryMs)) return true;
    return (Date.now() + TOKEN_EXPIRY_GRACE_MS) >= expiryMs;
}

function redirectToLogin() {
    if (window.location.pathname.endsWith("/frontend/pages/auth/login.html")) return;
    window.location.href = LOGIN_PAGE_PATH;
}

const Api = {
    getToken: () => localStorage.getItem("hms_token"),

    async request(endpoint, method = "GET", body = null) {
        const tokenAtRequest = Api.getToken();
        const isLogoutRequest = endpoint === "/auth/logout";
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
                if (isLogoutRequest || __isLoggingOut) {
                    return null;
                }
                console.warn("Session expired. Logging out.");
                logout({ reason: "session_expired", skipApi: true });
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
    red: { rank: 3, label: "Critical", hex: "#ef4444", action: "Immediate Nurse In Charge Review" }
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
const __nativeConfirm = window.confirm ? window.confirm.bind(window) : null;
const __nativePrompt = window.prompt ? window.prompt.bind(window) : null;
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

function ensureConfirmModal() {
    if (document.getElementById('appConfirmModal')) return;
    const modalHtml = `
<div class="modal fade" id="appConfirmModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header">
        <h5 class="modal-title" id="appConfirmTitle">Please confirm</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div id="appConfirmMessage" class="text-muted"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" id="appConfirmCancel" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" id="appConfirmOk">Confirm</button>
      </div>
    </div>
  </div>
</div>`;
    document.body.insertAdjacentHTML('beforeend', modalHtml);
}

function ensurePromptModal() {
    if (document.getElementById('appPromptModal')) return;
    const modalHtml = `
<div class="modal fade" id="appPromptModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header">
        <h5 class="modal-title" id="appPromptTitle">Input required</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div id="appPromptMessage" class="text-muted mb-2"></div>
        <input type="text" class="form-control" id="appPromptInput" autocomplete="off">
        <div id="appPromptError" class="text-danger small mt-2"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" id="appPromptCancel" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" id="appPromptOk">Confirm</button>
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

async function smartConfirm(message, options = {}) {
    const opts = typeof options === 'string' ? { title: options } : (options || {});
    if (typeof bootstrap === 'undefined') {
        return __nativeConfirm ? __nativeConfirm(String(message || 'Are you sure?')) : true;
    }
    ensureConfirmModal();

    const modalEl = document.getElementById('appConfirmModal');
    const titleEl = document.getElementById('appConfirmTitle');
    const msgEl = document.getElementById('appConfirmMessage');
    const okBtn = document.getElementById('appConfirmOk');
    const cancelBtn = document.getElementById('appConfirmCancel');
    const title = opts.title || 'Please confirm';
    const confirmText = opts.confirmText || 'Confirm';
    const cancelText = opts.cancelText || 'Cancel';
    const isDanger = opts.danger === true;

    titleEl.textContent = title;
    msgEl.textContent = String(message || '');
    okBtn.textContent = confirmText;
    cancelBtn.textContent = cancelText;
    okBtn.className = `btn ${isDanger ? 'btn-danger' : 'btn-primary'}`;

    return new Promise((resolve) => {
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        let done = false;

        const onShown = () => okBtn.focus();
        const onConfirm = () => {
            settle(true);
            modal.hide();
        };
        const onHidden = () => settle(false);
        const cleanup = () => {
            okBtn.removeEventListener('click', onConfirm);
            modalEl.removeEventListener('hidden.bs.modal', onHidden);
            modalEl.removeEventListener('shown.bs.modal', onShown);
        };
        const settle = (value) => {
            if (done) return;
            done = true;
            cleanup();
            resolve(value);
        };

        okBtn.addEventListener('click', onConfirm);
        modalEl.addEventListener('hidden.bs.modal', onHidden);
        modalEl.addEventListener('shown.bs.modal', onShown);
        modal.show();
    });
}

async function smartPrompt(message, options = {}) {
    const opts = typeof options === 'string' ? { title: options } : (options || {});
    const defaultValue = String(opts.defaultValue || '');
    if (typeof bootstrap === 'undefined') {
        return __nativePrompt ? __nativePrompt(String(message || 'Enter value:'), defaultValue) : null;
    }
    ensurePromptModal();

    const modalEl = document.getElementById('appPromptModal');
    const titleEl = document.getElementById('appPromptTitle');
    const msgEl = document.getElementById('appPromptMessage');
    const inputEl = document.getElementById('appPromptInput');
    const errorEl = document.getElementById('appPromptError');
    const okBtn = document.getElementById('appPromptOk');
    const cancelBtn = document.getElementById('appPromptCancel');
    const title = opts.title || 'Input required';
    const confirmText = opts.confirmText || 'Confirm';
    const cancelText = opts.cancelText || 'Cancel';
    const required = opts.required !== false;
    const validate = typeof opts.validate === 'function' ? opts.validate : null;

    titleEl.textContent = title;
    msgEl.textContent = String(message || '');
    inputEl.placeholder = String(opts.placeholder || '');
    inputEl.value = defaultValue;
    errorEl.textContent = '';
    okBtn.textContent = confirmText;
    cancelBtn.textContent = cancelText;

    return new Promise((resolve) => {
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        let done = false;

        const onShown = () => {
            inputEl.focus();
            inputEl.select();
        };
        const submit = () => {
            const value = String(inputEl.value || '').trim();
            if (required && !value) {
                errorEl.textContent = opts.requiredMessage || 'This field is required.';
                return;
            }
            if (validate) {
                const result = validate(value);
                if (result !== true) {
                    errorEl.textContent = typeof result === 'string' ? result : 'Invalid value.';
                    return;
                }
            }
            settle(value);
            modal.hide();
        };
        const onEnter = (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                submit();
            }
        };
        const onHidden = () => settle(null);
        const cleanup = () => {
            okBtn.removeEventListener('click', submit);
            inputEl.removeEventListener('keydown', onEnter);
            modalEl.removeEventListener('hidden.bs.modal', onHidden);
            modalEl.removeEventListener('shown.bs.modal', onShown);
        };
        const settle = (value) => {
            if (done) return;
            done = true;
            cleanup();
            resolve(value);
        };

        okBtn.addEventListener('click', submit);
        inputEl.addEventListener('keydown', onEnter);
        modalEl.addEventListener('hidden.bs.modal', onHidden);
        modalEl.addEventListener('shown.bs.modal', onShown);
        modal.show();
    });
}

if (window.alert) {
    window.alert = (msg) => notify(msg, 'warning');
}
window.smartConfirm = smartConfirm;
window.smartPrompt = smartPrompt;

const __sessionFxState = { styleId: "hmsSessionFxStyles", overlayId: "hmsSessionFx" };

function ensureSessionFxStyles() {
    if (document.getElementById(__sessionFxState.styleId)) return;
    const style = document.createElement("style");
    style.id = __sessionFxState.styleId;
    style.textContent = `
        #${__sessionFxState.overlayId} {
            position: fixed;
            inset: 0;
            z-index: 4000;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            opacity: 0;
            transition: opacity .25s ease;
            background: radial-gradient(circle at 20% 20%, rgba(30, 64, 175, 0.18), transparent 48%), radial-gradient(circle at 80% 80%, rgba(22, 163, 74, 0.2), transparent 45%), rgba(2, 6, 23, 0.55);
            backdrop-filter: blur(2px);
        }
        #${__sessionFxState.overlayId}.is-logout {
            background: radial-gradient(circle at 20% 20%, rgba(249, 115, 22, 0.2), transparent 48%), radial-gradient(circle at 80% 80%, rgba(190, 24, 93, 0.2), transparent 45%), rgba(2, 6, 23, 0.62);
        }
        #${__sessionFxState.overlayId}.is-visible { opacity: 1; }
        #${__sessionFxState.overlayId} .session-fx__card {
            width: min(92vw, 500px);
            border-radius: 22px;
            border: 1px solid rgba(148, 163, 184, 0.35);
            background: linear-gradient(165deg, rgba(255, 255, 255, 0.96), rgba(241, 245, 249, 0.96));
            box-shadow: 0 25px 60px -35px rgba(15, 23, 42, 0.75);
            padding: 28px 26px;
            display: flex;
            gap: 16px;
            align-items: center;
            transform: translateY(12px) scale(.97);
            opacity: 0;
            transition: transform .28s ease, opacity .28s ease;
            position: relative;
            overflow: hidden;
        }
        #${__sessionFxState.overlayId}.is-visible .session-fx__card {
            transform: translateY(0) scale(1);
            opacity: 1;
        }
        #${__sessionFxState.overlayId}.is-exit .session-fx__card {
            transform: translateY(-8px) scale(.985);
            opacity: 0;
        }
        #${__sessionFxState.overlayId} .session-fx__halo {
            position: absolute;
            width: 220px;
            height: 220px;
            border-radius: 50%;
            right: -72px;
            top: -82px;
            background: radial-gradient(circle, rgba(34, 197, 94, 0.24), rgba(34, 197, 94, 0));
            animation: hms-session-pulse 2.1s ease-in-out infinite;
        }
        #${__sessionFxState.overlayId}.is-logout .session-fx__halo {
            background: radial-gradient(circle, rgba(249, 115, 22, 0.22), rgba(249, 115, 22, 0));
        }
        #${__sessionFxState.overlayId} .session-fx__icon {
            width: 56px;
            height: 56px;
            border-radius: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.45rem;
            color: #0f172a;
            background: linear-gradient(155deg, rgba(34, 197, 94, 0.25), rgba(59, 130, 246, 0.25));
            border: 1px solid rgba(22, 163, 74, 0.35);
            flex: 0 0 56px;
        }
        #${__sessionFxState.overlayId}.is-logout .session-fx__icon {
            background: linear-gradient(155deg, rgba(249, 115, 22, 0.25), rgba(190, 24, 93, 0.25));
            border-color: rgba(249, 115, 22, 0.45);
        }
        #${__sessionFxState.overlayId} .session-fx__title {
            margin: 0;
            color: #0f172a;
            font-weight: 800;
            font-size: 1.25rem;
            letter-spacing: .01em;
        }
        #${__sessionFxState.overlayId} .session-fx__sub {
            margin-top: 4px;
            color: #475569;
            font-size: .95rem;
        }
        #${__sessionFxState.overlayId} .session-fx__bar {
            margin-top: 12px;
            height: 4px;
            border-radius: 999px;
            background: rgba(148, 163, 184, 0.24);
            overflow: hidden;
        }
        #${__sessionFxState.overlayId} .session-fx__bar > span {
            display: block;
            width: 100%;
            height: 100%;
            transform-origin: left center;
            animation: hms-session-progress 1.25s linear forwards;
            background: linear-gradient(90deg, #16a34a 0%, #2563eb 100%);
        }
        #${__sessionFxState.overlayId}.is-logout .session-fx__bar > span {
            background: linear-gradient(90deg, #f97316 0%, #be185d 100%);
        }
        @keyframes hms-session-pulse {
            0%, 100% { transform: scale(1); opacity: .8; }
            50% { transform: scale(1.08); opacity: 1; }
        }
        @keyframes hms-session-progress {
            from { transform: scaleX(0); }
            to { transform: scaleX(1); }
        }
    `;
    document.head.appendChild(style);
}

function getSessionFirstName(name) {
    const trimmed = String(name || "").trim();
    if (!trimmed) return "";
    return trimmed.split(/\s+/)[0];
}

async function showSessionIntro(mode = "login", options = {}) {
    if (typeof document === "undefined" || !document.body) return;
    ensureSessionFxStyles();

    const existing = document.getElementById(__sessionFxState.overlayId);
    if (existing) existing.remove();

    const normalizedMode = String(mode || "").toLowerCase() === "logout" ? "logout" : "login";
    const firstName = getSessionFirstName(options.name);
    const title = String(options.title || (normalizedMode === "logout"
        ? `Goodbye${firstName ? `, ${firstName}` : ""}`
        : `Welcome${firstName ? `, ${firstName}` : ""}`));
    const subtitle = String(options.subtitle || (normalizedMode === "logout"
        ? "Closing your session securely"
        : "Preparing your dashboard"));
    const iconClass = String(options.iconClass || (normalizedMode === "logout"
        ? "bi bi-box-arrow-right"
        : "bi bi-check2-circle"));
    const holdMs = Number.isFinite(options.minDurationMs) ? Math.max(650, Number(options.minDurationMs)) : 1300;
    const exitMs = 320;

    const overlay = document.createElement("div");
    overlay.id = __sessionFxState.overlayId;
    overlay.className = normalizedMode === "logout" ? "is-logout" : "is-login";
    overlay.innerHTML = `
        <div class="session-fx__card" role="status" aria-live="polite">
            <div class="session-fx__halo"></div>
            <div class="session-fx__icon"><i class="${iconClass}"></i></div>
            <div class="session-fx__copy">
                <h2 class="session-fx__title"></h2>
                <div class="session-fx__sub"></div>
                <div class="session-fx__bar"><span></span></div>
            </div>
        </div>
    `;
    const titleEl = overlay.querySelector(".session-fx__title");
    const subtitleEl = overlay.querySelector(".session-fx__sub");
    if (titleEl) titleEl.textContent = title;
    if (subtitleEl) subtitleEl.textContent = subtitle;

    document.body.appendChild(overlay);
    requestAnimationFrame(() => overlay.classList.add("is-visible"));

    await new Promise((resolve) => setTimeout(resolve, holdMs));
    overlay.classList.add("is-exit");
    await new Promise((resolve) => setTimeout(resolve, exitMs));
    overlay.remove();
}

window.showSessionIntro = showSessionIntro;

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

const STAFF_NOTIFICATION_TIMEOUT_MS = 5000;
const STAFF_REMINDER_INTERVAL_MS = 60000;
const STAFF_REMINDER_COOLDOWN_MS = 5 * 60 * 1000;
const STAFF_NOTIFICATION_ROLES = new Set([
    "doctor",
    "nurse_in_charge",
    "nurse",
    "nurse_aid",
    "pharmacist",
    "senior_pharmacist",
    "receptionist"
]);

const STAFF_NOTIFICATION_STATE = {
    started: false,
    inFlight: false,
    role: "",
    userId: 0,
    timer: null,
    realtimeTimer: null,
    realtimeListener: null,
    lastRealtimeProbeAt: 0,
    lastPendingSignature: "",
    assignedKeys: new Set()
};

function notifyStaffPopup(message, type = "info") {
    if (!message) return;
    notify(message, { type, timeoutMs: STAFF_NOTIFICATION_TIMEOUT_MS });
}
window.notifyStaffPopup = notifyStaffPopup;

function getCurrentStaffSession() {
    const token = localStorage.getItem("hms_token");
    if (!token) return null;
    const userJson = localStorage.getItem("hms_user");
    if (!userJson) return null;
    try {
        const user = JSON.parse(userJson);
        const role = normalizeRole(user?.role);
        const userId = Number(user?.id || 0);
        if (!role || !Number.isFinite(userId) || userId <= 0) return null;
        return { role, userId };
    } catch (e) {
        return null;
    }
}

function asArrayOrNull(value) {
    return Array.isArray(value) ? value : null;
}

function normalizeStatusValue(status) {
    return String(status || "").trim().toLowerCase().replace(/[\s-]+/g, "_");
}

function uniqueNames(values) {
    const seen = new Set();
    const out = [];
    (Array.isArray(values) ? values : []).forEach((value) => {
        const name = String(value || "").trim();
        if (!name) return;
        const key = name.toLowerCase();
        if (seen.has(key)) return;
        seen.add(key);
        out.push(name);
    });
    return out;
}

function getReminderStorageKey(role, userId) {
    return `hms_pending_reminder_${role}_${userId}`;
}

async function collectDoctorReminderSnapshot() {
    const [queueRaw, tasksRaw, apptsRaw] = await Promise.all([
        Api.get("/doctor/waiting_list"),
        Api.get("/doctor/task_list"),
        Api.get("/doctor/appointments")
    ]);
    const queue = asArrayOrNull(queueRaw);
    const tasks = asArrayOrNull(tasksRaw);
    const appointments = asArrayOrNull(apptsRaw);
    if (!queue || !tasks || !appointments) return null;

    const waitingCount = queue.length;
    const openTasks = tasks.filter((t) => !["done", "cancelled"].includes(normalizeStatusValue(t?.status)));
    const openTaskCount = openTasks.length;
    const activeAppointments = appointments.filter((a) => !["completed", "cancelled"].includes(normalizeStatusValue(a?.status)));
    const appointmentCount = activeAppointments.length;

    const assignedKeys = new Set();
    queue.forEach((row) => {
        const queueId = Number(row?.queue_id || 0);
        if (Number.isFinite(queueId) && queueId > 0) assignedKeys.add(`queue:${queueId}`);
    });
    activeAppointments.forEach((row) => {
        const appointmentId = Number(row?.id || 0);
        if (Number.isFinite(appointmentId) && appointmentId > 0) assignedKeys.add(`appointment:${appointmentId}`);
    });

    const assignedNames = uniqueNames(
        queue.map((row) => row?.full_name).concat(activeAppointments.map((row) => row?.full_name))
    );
    const parts = [];
    if (waitingCount) parts.push(`${waitingCount} waiting for consultation`);
    if (openTaskCount) parts.push(`${openTaskCount} open task${openTaskCount === 1 ? "" : "s"}`);
    if (appointmentCount) parts.push(`${appointmentCount} active appointment${appointmentCount === 1 ? "" : "s"}`);

    return {
        pendingCount: waitingCount + openTaskCount + appointmentCount,
        pendingParts: parts,
        pendingSignature: `w${waitingCount}|t${openTaskCount}|a${appointmentCount}|k${assignedKeys.size}`,
        assignedKeys,
        assignedNames
    };
}

async function collectNurseReminderSnapshot(userId) {
    const [urgentRaw, admissionRaw, tasksRaw, escRaw] = await Promise.all([
        Api.get("/nurse/urgent_care_list"),
        Api.get("/nurse/admission_waiting_list"),
        Api.get("/nurse/task_list"),
        Api.get("/nurse/escalation_list")
    ]);
    const urgent = asArrayOrNull(urgentRaw);
    const admission = asArrayOrNull(admissionRaw);
    const tasks = asArrayOrNull(tasksRaw);
    const escalations = asArrayOrNull(escRaw);
    if (!urgent || !admission || !tasks || !escalations) return null;

    const urgentCount = urgent.length;
    const admissionCount = admission.length;
    const openTasks = tasks.filter((t) => !["done", "cancelled"].includes(normalizeStatusValue(t?.status)));
    const openTaskCount = openTasks.length;
    const openEscalations = escalations.filter((e) => normalizeStatusValue(e?.status) !== "closed");
    const openEscCount = openEscalations.length;

    const myPatientTasks = openTasks.filter((t) => Number(t?.assigned_to || 0) === userId && Number(t?.patient_id || 0) > 0);

    const assignedKeys = new Set();
    urgent.forEach((row) => {
        const queueId = Number(row?.queue_id || 0);
        if (Number.isFinite(queueId) && queueId > 0) assignedKeys.add(`urgent:${queueId}`);
    });
    admission.forEach((row) => {
        const queueId = Number(row?.queue_id || 0);
        if (Number.isFinite(queueId) && queueId > 0) assignedKeys.add(`admission:${queueId}`);
    });
    myPatientTasks.forEach((row) => {
        const taskId = Number(row?.id || 0);
        if (Number.isFinite(taskId) && taskId > 0) assignedKeys.add(`task:${taskId}`);
    });

    const assignedNames = uniqueNames(
        urgent.map((row) => row?.full_name)
            .concat(admission.map((row) => row?.full_name))
            .concat(myPatientTasks.map((row) => row?.patient_name))
    );
    const parts = [];
    if (urgentCount) parts.push(`${urgentCount} urgent care`);
    if (admissionCount) parts.push(`${admissionCount} admission waiting`);
    if (openTaskCount) parts.push(`${openTaskCount} open task${openTaskCount === 1 ? "" : "s"}`);
    if (openEscCount) parts.push(`${openEscCount} escalation${openEscCount === 1 ? "" : "s"}`);

    return {
        pendingCount: urgentCount + admissionCount + openTaskCount + openEscCount,
        pendingParts: parts,
        pendingSignature: `u${urgentCount}|a${admissionCount}|t${openTaskCount}|e${openEscCount}|k${assignedKeys.size}`,
        assignedKeys,
        assignedNames
    };
}

async function collectNurseAidReminderSnapshot(userId) {
    const [triageRaw, admittedRaw, tasksRaw] = await Promise.all([
        Api.get("/nurse_aid/triage_queue"),
        Api.get("/nurse_aid/admitted_patients"),
        Api.get("/nurse_aid/task_list")
    ]);
    const triage = asArrayOrNull(triageRaw);
    const admitted = asArrayOrNull(admittedRaw);
    const tasks = asArrayOrNull(tasksRaw);
    if (!triage || !admitted || !tasks) return null;

    const triageCount = triage.length;
    const now = Date.now();
    const overdue = admitted.filter((row) => {
        const last = row?.last_vitals_at ? new Date(row.last_vitals_at).getTime() : 0;
        return !last || (now - last) >= 4 * 60 * 60 * 1000;
    }).length;
    const openTasks = tasks.filter((t) => !["done", "cancelled"].includes(normalizeStatusValue(t?.status)));
    const openTaskCount = openTasks.length;
    const myPatientTasks = openTasks.filter((t) => Number(t?.assigned_to || 0) === userId && Number(t?.patient_id || 0) > 0);

    const assignedKeys = new Set();
    triage.forEach((row) => {
        const queueId = Number(row?.queue_id || 0);
        if (Number.isFinite(queueId) && queueId > 0) assignedKeys.add(`triage:${queueId}`);
    });
    myPatientTasks.forEach((row) => {
        const taskId = Number(row?.id || 0);
        if (Number.isFinite(taskId) && taskId > 0) assignedKeys.add(`task:${taskId}`);
    });

    const assignedNames = uniqueNames(triage.map((row) => row?.full_name).concat(myPatientTasks.map((row) => row?.patient_name)));
    const parts = [];
    if (triageCount) parts.push(`${triageCount} triage pending`);
    if (overdue) parts.push(`${overdue} vitals overdue`);
    if (openTaskCount) parts.push(`${openTaskCount} open task${openTaskCount === 1 ? "" : "s"}`);

    return {
        pendingCount: triageCount + overdue + openTaskCount,
        pendingParts: parts,
        pendingSignature: `t${triageCount}|o${overdue}|k${openTaskCount}|a${assignedKeys.size}`,
        assignedKeys,
        assignedNames
    };
}

async function collectPharmacyReminderSnapshot() {
    const [pendingRaw, requestsRaw] = await Promise.all([
        Api.get("/pharmacy/pending"),
        Api.get("/pharmacy/nurse_requests")
    ]);
    const pending = asArrayOrNull(pendingRaw);
    const requests = asArrayOrNull(requestsRaw);
    if (!pending || !requests) return null;

    const pendingCount = pending.length;
    const requestCount = requests.filter((r) => ["pending", "ready"].includes(normalizeStatusValue(r?.status))).length;
    const assignedKeys = new Set();
    pending.forEach((row) => {
        const itemId = Number(row?.id || 0);
        if (Number.isFinite(itemId) && itemId > 0) assignedKeys.add(`rx:${itemId}`);
    });
    requests.forEach((row) => {
        const reqId = Number(row?.id || 0);
        if (Number.isFinite(reqId) && reqId > 0) assignedKeys.add(`req:${reqId}`);
    });

    const assignedNames = uniqueNames(
        pending.map((row) => row?.full_name || row?.patient_name)
            .concat(requests.map((row) => row?.patient_name || row?.full_name))
    );
    const parts = [];
    if (pendingCount) parts.push(`${pendingCount} prescription${pendingCount === 1 ? "" : "s"} pending`);
    if (requestCount) parts.push(`${requestCount} nurse request${requestCount === 1 ? "" : "s"}`);

    return {
        pendingCount: pendingCount + requestCount,
        pendingParts: parts,
        pendingSignature: `p${pendingCount}|r${requestCount}|k${assignedKeys.size}`,
        assignedKeys,
        assignedNames
    };
}

async function collectReceptionReminderSnapshot() {
    const [queueRaw, appointmentsRaw] = await Promise.all([
        Api.get("/reception/queue_list"),
        Api.get("/reception/appointments")
    ]);
    const queue = asArrayOrNull(queueRaw);
    const appointments = asArrayOrNull(appointmentsRaw);
    if (!queue || !appointments) return null;

    const queueCount = queue.length;
    const pendingAppointments = appointments.filter((row) => {
        const status = normalizeStatusValue(row?.status);
        return ["scheduled", "confirmed", "checked_in"].includes(status);
    });
    const appointmentCount = pendingAppointments.length;

    const assignedKeys = new Set();
    queue.forEach((row) => {
        const queueId = Number(row?.queue_id || 0);
        if (Number.isFinite(queueId) && queueId > 0) assignedKeys.add(`queue:${queueId}`);
    });

    const assignedNames = uniqueNames(queue.map((row) => row?.full_name));
    const parts = [];
    if (queueCount) parts.push(`${queueCount} active queue`);
    if (appointmentCount) parts.push(`${appointmentCount} appointment${appointmentCount === 1 ? "" : "s"} pending`);

    return {
        pendingCount: queueCount + appointmentCount,
        pendingParts: parts,
        pendingSignature: `q${queueCount}|a${appointmentCount}|k${assignedKeys.size}`,
        assignedKeys,
        assignedNames
    };
}

async function collectStaffReminderSnapshot(role, userId) {
    if (role === "doctor" || role === "nurse_in_charge") return collectDoctorReminderSnapshot();
    if (role === "nurse") return collectNurseReminderSnapshot(userId);
    if (role === "nurse_aid") return collectNurseAidReminderSnapshot(userId);
    if (role === "pharmacist" || role === "senior_pharmacist") return collectPharmacyReminderSnapshot();
    if (role === "receptionist") return collectReceptionReminderSnapshot();
    return null;
}

function buildPendingReminderMessage(snapshot) {
    const parts = Array.isArray(snapshot?.pendingParts) ? snapshot.pendingParts.filter(Boolean) : [];
    if (!parts.length) return "";
    return `Pending reminder: ${parts.join(" | ")}.`;
}

function buildAssignmentReminderMessage(snapshot, addedCount) {
    const names = Array.isArray(snapshot?.assignedNames) ? snapshot.assignedNames.slice(0, 3) : [];
    if (names.length) {
        const more = addedCount > names.length ? ` +${addedCount - names.length} more` : "";
        return `New patient assignment: ${names.join(", ")}${more}.`;
    }
    return `New patient assignment received (${addedCount}).`;
}

async function runStaffReminderCheck(reason = "interval") {
    const session = getCurrentStaffSession();
    if (!session) return;
    if (!STAFF_NOTIFICATION_ROLES.has(session.role)) return;
    if (STAFF_NOTIFICATION_STATE.inFlight) return;

    STAFF_NOTIFICATION_STATE.inFlight = true;
    try {
        STAFF_NOTIFICATION_STATE.role = session.role;
        STAFF_NOTIFICATION_STATE.userId = session.userId;

        const snapshot = await collectStaffReminderSnapshot(session.role, session.userId);
        if (!snapshot) return;

        if (reason !== "initial") {
            let addedAssignments = 0;
            snapshot.assignedKeys.forEach((key) => {
                if (!STAFF_NOTIFICATION_STATE.assignedKeys.has(key)) addedAssignments++;
            });
            if (addedAssignments > 0) {
                notifyStaffPopup(buildAssignmentReminderMessage(snapshot, addedAssignments), "info");
            }
        }

        STAFF_NOTIFICATION_STATE.assignedKeys = snapshot.assignedKeys;
        const pendingCount = Number(snapshot.pendingCount || 0);
        const pendingSignature = String(snapshot.pendingSignature || "");
        const reminderKey = getReminderStorageKey(session.role, session.userId);
        const now = Date.now();
        const lastReminderAt = Number(localStorage.getItem(reminderKey) || "0");
        const pendingChanged = pendingSignature !== STAFF_NOTIFICATION_STATE.lastPendingSignature;
        const reminderDue = (now - lastReminderAt) >= STAFF_REMINDER_COOLDOWN_MS;
        const shouldNotifyPending = pendingCount > 0 && (reason === "initial" || pendingChanged || reminderDue);

        if (shouldNotifyPending) {
            const msg = buildPendingReminderMessage(snapshot);
            if (msg) notifyStaffPopup(msg, "warning");
            localStorage.setItem(reminderKey, String(now));
        } else if (pendingCount <= 0) {
            localStorage.removeItem(reminderKey);
        }

        STAFF_NOTIFICATION_STATE.lastPendingSignature = pendingSignature;
    } catch (e) {
        // ignore reminder errors
    } finally {
        STAFF_NOTIFICATION_STATE.inFlight = false;
    }
}

function queueStaffRealtimeCheck() {
    const now = Date.now();
    if (now - STAFF_NOTIFICATION_STATE.lastRealtimeProbeAt < 1500) return;
    STAFF_NOTIFICATION_STATE.lastRealtimeProbeAt = now;
    if (STAFF_NOTIFICATION_STATE.realtimeTimer) {
        clearTimeout(STAFF_NOTIFICATION_STATE.realtimeTimer);
    }
    STAFF_NOTIFICATION_STATE.realtimeTimer = setTimeout(() => {
        runStaffReminderCheck("realtime");
    }, 350);
}

function setupStaffNotifications() {
    if (STAFF_NOTIFICATION_STATE.started) return;
    const session = getCurrentStaffSession();
    if (!session) return;
    if (!STAFF_NOTIFICATION_ROLES.has(session.role)) return;

    STAFF_NOTIFICATION_STATE.started = true;
    STAFF_NOTIFICATION_STATE.role = session.role;
    STAFF_NOTIFICATION_STATE.userId = session.userId;
    STAFF_NOTIFICATION_STATE.realtimeListener = () => queueStaffRealtimeCheck();
    window.addEventListener("hms:realtime", STAFF_NOTIFICATION_STATE.realtimeListener);
    STAFF_NOTIFICATION_STATE.timer = setInterval(() => runStaffReminderCheck("interval"), STAFF_REMINDER_INTERVAL_MS);
    runStaffReminderCheck("initial");
}

document.addEventListener("DOMContentLoaded", () => {
    setupAutoRefresh();
    setupRealtime();
    setupBroadcastChannel();
    setupStaffNotifications();
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
    const isEventArg = !!(event && typeof event === "object" && typeof event.type === "string");
    const opts = !isEventArg && event && typeof event === "object" ? event : {};
    const clickEvent = isEventArg ? event : null;
    const skipApi = opts.skipApi === true;
    const reason = String(opts.reason || "").trim();
    const logoutReason = reason || ((clickEvent && clickEvent.type === "click") ? "manual_logout" : "");
    const token = localStorage.getItem("hms_token");
    let user = null;
    try {
        user = JSON.parse(localStorage.getItem("hms_user") || "null");
    } catch (e) {
        user = null;
    }

    if (__isLoggingOut) return;

    // Only ask for confirmation if the user clicked the button manually
    if (clickEvent && clickEvent.type === 'click') {
        const confirmed = await smartConfirm("Are you sure you want to log out?", {
            title: "Logout",
            confirmText: "Logout",
            cancelText: "Stay",
            danger: true
        });
        if (!confirmed) return;
    }

    __isLoggingOut = true;

    try {
        if (!skipApi) {
            if (user && user.id) {
                await Api.post('/auth/logout', { user_id: user.id, token });
            }
        }
    } catch (e) {
        console.warn('Logout API call failed', e);
    }

    const introTitle = logoutReason === "session_expired" ? "Session Ended" : "";
    const introSubtitle = logoutReason === "session_expired"
        ? "Your session timed out. Please sign in again."
        : "Closing your session securely";
    try {
        await showSessionIntro("logout", {
            name: user?.full_name || user?.username || "",
            title: introTitle,
            subtitle: introSubtitle,
            minDurationMs: clickEvent ? 1250 : 900
        });
    } catch (e) {
        // ignore animation failures
    }

    if (logoutReason) {
        try {
            sessionStorage.setItem("hms_logout_reason", logoutReason);
        } catch (e) {
            // ignore storage errors
        }
    }

    // Clear all local session data
    localStorage.clear();

    // Redirect to login using absolute path
    redirectToLogin();
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
document.addEventListener("DOMContentLoaded", setupSessionWatchdog);
document.addEventListener("DOMContentLoaded", ensureItTicketWidget);
function normalizeRole(role) {
    return String(role || "")
        .trim()
        .toLowerCase()
        .replace(/[\s-]+/g, "_");
}

function enforceSessionTimeout() {
    if (__isLoggingOut) return;
    const token = localStorage.getItem("hms_token");
    if (!token) return;
    if (!isTokenExpired(token)) return;
    logout({ reason: "session_expired", skipApi: true });
}

function setupSessionWatchdog() {
    if (__sessionWatchdogTimer) return;
    enforceSessionTimeout();
    __sessionWatchdogTimer = window.setInterval(enforceSessionTimeout, SESSION_WATCHDOG_INTERVAL_MS);
    window.addEventListener("focus", enforceSessionTimeout);
    document.addEventListener("visibilitychange", () => {
        if (!document.hidden) enforceSessionTimeout();
    });
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
        redirectToLogin();
        return;
    }

    if (isTokenExpired(token)) {
        logout({ reason: "session_expired", skipApi: true });
        return;
    }

    // B. Check if Role is Allowed
    let user;
    try {
        user = JSON.parse(userJson);
    } catch (e) {
        logout({ reason: "invalid_session", skipApi: true });
        return;
    }

    const normalizedUserRole = normalizeRole(user.role);
    const rolesArray = (Array.isArray(allowedRoles) ? allowedRoles : [allowedRoles]).map(normalizeRole);

    if (!rolesArray.includes(normalizedUserRole)) {
        notify("⛔ Access Denied: You do not have permission.", "error");

        // Redirect to their correct dashboard based on role
        if(normalizedUserRole === 'doctor' || normalizedUserRole === 'nurse_in_charge') window.location.href = "../doctor/dashboard.html";
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
