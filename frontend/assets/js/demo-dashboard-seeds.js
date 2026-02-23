(() => {
    "use strict";

    const params = new URLSearchParams(window.location.search);
    if (params.get("demoSeeds") === "0") return;

    const role = detectRole(window.location.pathname || "");
    if (!role) return;

    const DEMO = {
        admin: {
            metrics: {
                statTotalStaff: "48",
                totalDoctors: "14",
                activeNurses: "19",
                statStaffPresent: "41",
                statUtilization: "85%",
                pharmacyPending: "18",
                pharmacyDispensed: "126",
                nursePending: "9",
                nurseVitals: "67",
                receptionRegistered: "33",
                receptionAdmitted: "21"
            },
            tables: {
                usersTableBody: [
                    ["Dr. A. Ncube", "doctor", "an.01", "active", "Today 08:12"],
                    ["Nurse T. Dube", "nurse", "td.14", "active", "Today 07:48"],
                    ["Pharm. L. Zhou", "pharmacy", "lz.03", "active", "Today 08:02"],
                    ["Reception J. Moyo", "receptionist", "jm.09", "active", "Today 07:30"],
                    ["IT R. Banda", "it", "rb.22", "active", "Today 08:25"],
                    ["Nurse Aid K. Zulu", "nurse_aid", "kz.11", "active", "Today 07:54"]
                ],
                adminShiftTableBody: [
                    ["Dr. A. Ncube", "doctor", "Day", "07:00", "15:00"],
                    ["Nurse T. Dube", "nurse", "Day", "07:00", "19:00"],
                    ["Nurse B. Chari", "nurse", "Night", "19:00", "07:00"],
                    ["Pharm. L. Zhou", "pharmacy", "Day", "08:00", "17:00"],
                    ["Reception J. Moyo", "reception", "Day", "06:30", "15:30"]
                ],
                adminLogsTableBody: [
                    ["2026-02-23 08:02", "login", "success", "admin", "1", "auth", "web", "ok"],
                    ["2026-02-23 08:10", "create_user", "success", "admin", "1", "users", "web", "ok"],
                    ["2026-02-23 08:15", "queue_update", "success", "reception", "4", "queue", "web", "ok"],
                    ["2026-02-23 08:20", "dispense", "success", "pharmacy", "6", "rx", "web", "ok"],
                    ["2026-02-23 08:24", "triage_start", "success", "nurse_aid", "7", "triage", "web", "ok"]
                ]
            }
        },
        doctor: {
            metrics: { statWaiting: "11" },
            tables: {
                queueTableBody: [
                    ["P-00124", "M. Ndlovu", "High", "08:05"],
                    ["P-00151", "R. Chuma", "Medium", "08:14"],
                    ["P-00166", "T. Banda", "Low", "08:21"],
                    ["P-00179", "S. Moyo", "Medium", "08:33"],
                    ["P-00182", "L. Dube", "High", "08:37"]
                ],
                appointmentTable: [
                    ["08:30", "A. Phiri", "Follow-up", "Room 2", "Booked", "Check-in pending"],
                    ["09:00", "C. Mbewe", "Review", "Room 3", "Booked", "Confirmed"],
                    ["09:30", "B. Tembo", "Consult", "Room 1", "Booked", "Confirmed"],
                    ["10:15", "R. Muleya", "Procedure", "Room 4", "Booked", "Prep required"]
                ],
                directoryTableBody: [
                    ["P-00124", "M. Ndlovu", "M", "31", "None"],
                    ["P-00151", "R. Chuma", "F", "27", "Penicillin"],
                    ["P-00166", "T. Banda", "M", "44", "N/A"],
                    ["P-00179", "S. Moyo", "F", "35", "Latex"],
                    ["P-00182", "L. Dube", "M", "52", "Sulfa"]
                ],
                tasksTable: [
                    ["Lab review", "M. Ndlovu", "High", "Open", "08:50"],
                    ["ECG follow-up", "R. Chuma", "Medium", "Open", "09:20"],
                    ["Medication update", "T. Banda", "Low", "Open", "10:10"],
                    ["Family briefing", "L. Dube", "Medium", "Open", "10:40"]
                ],
                escalationsTable: [
                    ["R. Chuma", "Respiratory distress", "Open", "08:18"],
                    ["L. Dube", "Unstable BP trend", "Open", "08:41"],
                    ["S. Moyo", "Pain not controlled", "Open", "09:03"]
                ],
                dischargeTable: [
                    ["A. Phiri", "Ward B", "Improved", "Ready", "Today"],
                    ["T. Banda", "Ward C", "Stable", "Pending", "Today"],
                    ["C. Mbewe", "Ward A", "Recovered", "Ready", "Today"]
                ],
                handoverTable: [
                    ["Dr. K. Maseko", "Day", "Patient load heavy in Room 2", "08:00"],
                    ["Dr. P. Zhou", "Night", "Monitor hypertensive cases", "07:30"]
                ],
                rosterTableBody: [
                    ["Mon", "07:00", "15:00", "OPD", "Confirmed"],
                    ["Tue", "07:00", "15:00", "ER", "Confirmed"],
                    ["Wed", "15:00", "23:00", "Ward", "Confirmed"]
                ]
            }
        },
        it: {
            metrics: {
                metricUptime: "24d 07h",
                metricActiveUsers: "37",
                metricQueueCount: "15",
                metricStockAlerts: "4 expired / 12 expiring"
            },
            tables: {
                errorsTable: [
                    ["08:02", "Queue API timeout", "medium", "recovered"],
                    ["08:11", "Email relay delay", "low", "open"],
                    ["08:17", "Disk usage warning", "high", "open"],
                    ["08:23", "Token refresh mismatch", "medium", "investigating"]
                ],
                recentTicketsTable: [
                    ["IT-2401", "Printer offline in OPD", "medium", "open"],
                    ["IT-2402", "Pharmacy scanner lag", "high", "open"],
                    ["IT-2403", "Nurse station WiFi", "low", "resolved"],
                    ["IT-2404", "Audit query timeout", "medium", "open"]
                ],
                ticketsTable: [
                    ["IT-2397", "Reception PC freeze", "high", "in_progress", "B. Tech"],
                    ["IT-2398", "Barcode mismatch", "medium", "open", "R. Banda"],
                    ["IT-2399", "Shift roster sync", "low", "resolved", "K. Moyo"],
                    ["IT-2400", "Report export delay", "medium", "open", "L. Mbewe"]
                ],
                auditTable: [
                    ["2026-02-23 08:04", "admin", "admin", "update_settings", "success", "settings #1", "web"],
                    ["2026-02-23 08:09", "john.reception", "receptionist", "queue_update", "success", "queue #183", "web"],
                    ["2026-02-23 08:13", "betty.nurse", "nurse", "admit_patient", "success", "admission #55", "web"],
                    ["2026-02-23 08:19", "lee.pharm", "pharmacist", "dispense", "success", "prescription #412", "web"]
                ]
            }
        },
        nurse: {
            metrics: {
                wardTotal: "64",
                wardOccupied: "49",
                wardAvailable: "13",
                wardOther: "2"
            },
            tables: {
                historyTableBody: [
                    ["P-00124", "M. Ndlovu", "Temp 37.4C", "08:10"],
                    ["P-00151", "R. Chuma", "BP 148/96", "08:16"],
                    ["P-00166", "T. Banda", "Pulse 102", "08:22"],
                    ["P-00179", "S. Moyo", "SpO2 94%", "08:30"]
                ],
                urgentCareTable: [
                    ["R. Chuma", "Respiratory distress", "High", "Open"],
                    ["L. Dube", "Unstable BP", "High", "Open"],
                    ["S. Moyo", "Pain crisis", "Medium", "Open"]
                ],
                tasksTable: [
                    ["IV line check", "R. Chuma", "High", "Open", "09:00"],
                    ["Medication round", "Ward B", "Medium", "Open", "09:30"],
                    ["Discharge prep", "A. Phiri", "Low", "Open", "10:00"]
                ],
                admissionTable: [
                    ["P-00201", "C. Zulu", "Dr. Ncube", "Ward A", "Pending", "08:35"],
                    ["P-00204", "B. Nyoni", "Dr. Zhou", "Ward C", "Pending", "08:42"],
                    ["P-00206", "M. Tembo", "Dr. Maseko", "Ward B", "Pending", "08:55"]
                ],
                escalationsTable: [
                    ["R. Chuma", "Oxygen saturation dropped", "Open", "08:19"],
                    ["L. Dube", "Chest pain complaint", "Open", "08:44"],
                    ["C. Zulu", "Dizziness after admission", "Open", "09:12"]
                ],
                dischargeTable: [
                    ["A. Phiri", "Ward B", "Stable", "Today"],
                    ["C. Mbewe", "Ward A", "Recovered", "Today"],
                    ["T. Banda", "Ward C", "Improved", "Tomorrow"]
                ],
                handoverTable: [
                    ["Day Shift", "Watch bed 12 vitals every hour", "08:00"],
                    ["Night Shift", "Follow up pain management ward C", "07:30"]
                ],
                rosterBody: [
                    ["Mon", "07:00", "19:00", "Ward A", "Confirmed"],
                    ["Tue", "07:00", "19:00", "Ward B", "Confirmed"],
                    ["Wed", "19:00", "07:00", "Ward C", "Confirmed"]
                ]
            }
        },
        nurse_aid: {
            metrics: { statPending: "14" },
            tables: {
                triageTable: [
                    ["P-00301", "J. Muleya", "Chest pain", "High", "08:04"],
                    ["P-00305", "S. Daka", "Headache", "Medium", "08:12"],
                    ["P-00309", "L. Mumba", "Fever", "Medium", "08:21"],
                    ["P-00313", "A. Kapwepwe", "Weakness", "Low", "08:29"]
                ],
                historyTableBody: [
                    ["P-00271", "N. Zulu", "Temp 37.2C", "07:40"],
                    ["P-00274", "M. Chitambo", "BP 132/86", "07:50"],
                    ["P-00276", "R. Banda", "Pulse 91", "08:00"]
                ],
                dailyVitalsTable: [
                    ["Ward A", "22", "20", "2", "91%"],
                    ["Ward B", "18", "16", "2", "89%"],
                    ["Ward C", "15", "14", "1", "93%"]
                ],
                dischargeTable: [
                    ["A. Phiri", "Ward B", "Ready", "Today"],
                    ["C. Mbewe", "Ward A", "Ready", "Today"],
                    ["T. Banda", "Ward C", "Pending review", "Tomorrow"]
                ]
            }
        },
        pharmacy: {
            metrics: { statPending: "24" },
            tables: {
                nurseRequestsTable: [
                    ["RQ-1201", "Ward A", "Paracetamol 1g", "18", "Normal", "08:11"],
                    ["RQ-1202", "Ward C", "Ceftriaxone", "12", "Urgent", "08:17"],
                    ["RQ-1203", "Ward B", "Salbutamol", "9", "Normal", "08:23"]
                ],
                pendingTable: [
                    ["RX-4801", "M. Ndlovu", "Amoxicillin", "500mg", "3", "Pending"],
                    ["RX-4802", "R. Chuma", "Losartan", "50mg", "2", "Pending"],
                    ["RX-4803", "T. Banda", "Metformin", "850mg", "1", "Pending"],
                    ["RX-4804", "L. Dube", "Atorvastatin", "20mg", "1", "Pending"]
                ],
                inventoryTableBody: [
                    ["Paracetamol", "B-1102", "420", "boxes", "2027-05-31", "OK"],
                    ["Amoxicillin", "B-2291", "180", "boxes", "2026-10-12", "OK"],
                    ["Insulin", "B-3340", "54", "vials", "2026-06-18", "Low"],
                    ["Syringes 5ml", "B-7701", "690", "packs", "2028-01-30", "OK"],
                    ["Aspirin", "B-1419", "36", "boxes", "2026-04-02", "Low"]
                ],
                historyTable: [
                    ["2026-02-23 08:00", "Dispensed", "RX-4801", "M. Ndlovu", "L. Zhou"],
                    ["2026-02-23 08:06", "Stock In", "PO-118", "Amoxicillin", "L. Zhou"],
                    ["2026-02-23 08:14", "Adjustment", "ADJ-44", "Insulin", "L. Zhou"],
                    ["2026-02-23 08:19", "Claim Update", "CLM-203", "RX-4776", "S. Daka"]
                ],
                interactionTable: [
                    ["Warfarin", "Aspirin", "major", "Monitor bleeding risk"],
                    ["Metformin", "Contrast Dye", "major", "Hold before imaging"],
                    ["Amoxicillin", "Methotrexate", "moderate", "Monitor toxicity"]
                ],
                controlledRequests: [
                    ["CR-901", "Morphine", "2", "Approved", "08:07"],
                    ["CR-902", "Diazepam", "4", "Pending", "08:15"],
                    ["CR-903", "Codeine", "6", "Approved", "08:27"]
                ],
                controlledTable: [
                    ["Morphine", "S8", "22", "Safe A", "2026-02-23"],
                    ["Diazepam", "S4", "95", "Safe B", "2026-02-23"],
                    ["Codeine", "S6", "130", "Safe A", "2026-02-23"]
                ],
                refillTable: [
                    ["RF-7001", "A. Phiri", "Losartan", "Due", "Today"],
                    ["RF-7002", "R. Chuma", "Insulin", "Due", "Today"],
                    ["RF-7003", "L. Dube", "Atorvastatin", "Upcoming", "Tomorrow"]
                ],
                poTable: [
                    ["PO-119", "MedSupply Ltd", "2026-02-22", "Pending", "12 items"],
                    ["PO-120", "CarePharm", "2026-02-23", "Partial", "8 items"],
                    ["PO-121", "ZedMed", "2026-02-23", "Approved", "5 items"]
                ],
                quarantineTable: [
                    ["Q-51", "Insulin", "B-3301", "8", "Damaged pack", "Open"],
                    ["Q-52", "Amoxicillin", "B-2109", "16", "Expiry check", "Open"]
                ],
                adjustTable: [
                    ["ADJ-44", "Insulin", "-2", "Broken vial", "2026-02-23 08:14"],
                    ["ADJ-45", "Paracetamol", "+20", "Count correction", "2026-02-23 08:20"],
                    ["ADJ-46", "Syringes", "-5", "Damaged box", "2026-02-23 08:28"]
                ],
                claimTable: [
                    ["CLM-203", "RX-4776", "MediAid", "submitted", "2026-02-23"],
                    ["CLM-204", "RX-4782", "NationalCare", "approved", "2026-02-23"],
                    ["CLM-205", "RX-4791", "MediAid", "pending", "2026-02-23"]
                ],
                rosterTableBody: [
                    ["Mon", "08:00", "17:00", "Dispensary", "Confirmed"],
                    ["Tue", "08:00", "17:00", "Inventory", "Confirmed"],
                    ["Wed", "12:00", "20:00", "Dispensary", "Confirmed"]
                ]
            }
        },
        reception: {
            metrics: {
                statToday: "52",
                statPending: "14"
            },
            tables: {
                flowTable: [
                    ["Morning", "31", "22", "9"],
                    ["Afternoon", "21", "16", "5"],
                    ["Evening", "14", "9", "5"]
                ],
                directoryTable: [
                    ["P-00124", "M. Ndlovu", "555-0140", "31", "Regular"],
                    ["P-00151", "R. Chuma", "555-0194", "27", "Urgent"],
                    ["P-00166", "T. Banda", "555-0177", "44", "Regular"],
                    ["P-00179", "S. Moyo", "555-0118", "35", "Urgent"]
                ],
                appointmentsTable: [
                    ["A-2301", "08:30", "A. Phiri", "Dr. Ncube", "booked"],
                    ["A-2302", "09:00", "C. Mbewe", "Dr. Zhou", "booked"],
                    ["A-2303", "09:30", "B. Tembo", "Dr. Maseko", "checked_in"],
                    ["A-2304", "10:00", "R. Muleya", "Dr. Ncube", "booked"]
                ],
                queueTableBody: [
                    ["Q-181", "M. Ndlovu", "Normal", "Dr. Ncube", "Waiting"],
                    ["Q-182", "R. Chuma", "Urgent", "Dr. Zhou", "Waiting"],
                    ["Q-183", "T. Banda", "Normal", "Dr. Maseko", "In service"],
                    ["Q-184", "S. Moyo", "Urgent", "Dr. Ncube", "Waiting"]
                ],
                handoverTable: [
                    ["Morning", "Desk 2 printer unstable", "08:00"],
                    ["Night", "Queue peaked at 06:40", "07:20"]
                ],
                shiftTableBody: [
                    ["Mon 23 Feb", "Reception", "06:30", "15:30", "Confirmed"],
                    ["Tue 24 Feb", "Reception", "06:30", "15:30", "Confirmed"],
                    ["Wed 25 Feb", "Reception", "10:30", "19:30", "Confirmed"]
                ]
            }
        }
    };

    function detectRole(pathname) {
        const p = pathname.toLowerCase();
        if (p.includes("/pages/admin/")) return "admin";
        if (p.includes("/pages/doctor/")) return "doctor";
        if (p.includes("/pages/it/")) return "it";
        if (p.includes("/pages/nurse_aid/")) return "nurse_aid";
        if (p.includes("/pages/nurse/")) return "nurse";
        if (p.includes("/pages/pharmacy/")) return "pharmacy";
        if (p.includes("/pages/reception/")) return "reception";
        return "";
    }

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;");
    }

    function isPlaceholderRow(row) {
        const singleCell = row.children.length === 1 ? row.children[0] : null;
        if (!singleCell) return false;
        const text = (singleCell.textContent || "").trim().toLowerCase();
        if (!text) return true;
        return text.includes("no ") || text.includes("failed") || text.includes("loading");
    }

    function detectColumnCount(tbody) {
        const table = tbody.closest("table");
        const heads = table ? table.querySelectorAll("thead th").length : 0;
        if (heads > 0) return heads;
        const row = tbody.querySelector("tr");
        return row ? Math.max(1, row.children.length) : 1;
    }

    function appendDemoRows(tbodyId, demoRows, minRows) {
        const tbody = document.getElementById(tbodyId);
        if (!tbody || !Array.isArray(demoRows) || demoRows.length === 0) return;

        const currentRows = Array.from(tbody.querySelectorAll("tr"));
        if (currentRows.length === 1 && isPlaceholderRow(currentRows[0])) {
            tbody.innerHTML = "";
        }

        const currentCount = tbody.querySelectorAll("tr").length;
        if (currentCount >= minRows) return;

        const needed = minRows - currentCount;
        const cols = detectColumnCount(tbody);
        const toAdd = demoRows.slice(0, needed);
        const html = toAdd.map((row) => {
            const cells = [];
            for (let i = 0; i < cols; i += 1) {
                cells.push(`<td>${escapeHtml(row[i] ?? "")}</td>`);
            }
            return `<tr>${cells.join("")}</tr>`;
        }).join("");
        tbody.insertAdjacentHTML("beforeend", html);
    }

    function setMetricIfWeak(id, value) {
        const el = document.getElementById(id);
        if (!el) return;
        const raw = (el.textContent || "").trim();
        const weak = !raw || raw === "0" || raw === "--" || raw === "0%" || raw === "0 / 0";
        const queueLike = /^0\s+expired\s*\/\s*0\s+expiring$/i.test(raw);
        if (weak || queueLike) {
            el.textContent = String(value);
        }
    }

    function applyDemoSeeds() {
        const roleDemo = DEMO[role];
        if (!roleDemo) return;

        const metricEntries = Object.entries(roleDemo.metrics || {});
        metricEntries.forEach(([id, value]) => setMetricIfWeak(id, value));

        const tableEntries = Object.entries(roleDemo.tables || {});
        tableEntries.forEach(([tbodyId, rows]) => appendDemoRows(tbodyId, rows, 6));
    }

    document.addEventListener("DOMContentLoaded", () => {
        let runs = 0;
        const interval = setInterval(() => {
            applyDemoSeeds();
            runs += 1;
            if (runs >= 8) clearInterval(interval);
        }, 2500);
        setTimeout(applyDemoSeeds, 1200);
    });
})();
