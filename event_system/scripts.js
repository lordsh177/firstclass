/* ============================================================
   Event Attendance Management System
   scripts.js  —  Cleaned, organized, and refactored
   ============================================================ */

/* ============================================================
   API HANDLER
   ============================================================ */

/**
 * Generic API call handler
 * - Maintains compatibility with PHP backend (php/api_handler.php)
 * - Automatically appends "action" and any key-value data
 */
async function apiCall(action, data = {}) {
  const formData = new FormData();
  formData.append("action", action);

  for (const key in data) {
    if (data[key] !== undefined && data[key] !== null) {
      formData.append(key, data[key]);
    }
  }

  try {
    const response = await fetch("php/api_handler.php", {
      method: "POST",
      body: formData,
    });
    return await response.json();
  } catch (error) {
    console.error("API Error:", error);
    return { success: false, message: "Network or server issue." };
  }
}

/* ============================================================
   AUTHENTICATION (Login & Password Recovery)
   ============================================================ */

document.addEventListener("DOMContentLoaded", () => {
  const loginForm = document.getElementById("login-form");
  if (loginForm) loginForm.addEventListener("submit", handleLogin);

  setupForgotPassword();
  attachGlobalHandlers();
});

/** Handles user login */
async function handleLogin(event) {
  event.preventDefault();
  const email = document.getElementById("email").value.trim();
  const password = document.getElementById("password").value.trim();
  const message = document.getElementById("message");

  if (!email || !password) {
    displayMessage(message, "Please enter email and password.", false);
    return;
  }

  displayMessage(message, "Logging in...");
  const result = await apiCall("login", { email, password });

  if (result.success) {
    displayMessage(message, result.message || "Login successful.", true);
    setTimeout(() => (window.location.href = "dashboard.html"), 700);
  } else {
    displayMessage(message, result.message || "Login failed. Please try again.", false);
  }
}

/** Forgot password modal setup */
function setupForgotPassword() {
  const forgotBtn = document.getElementById("forgot-btn");
  const modal = document.getElementById("forgot-modal");
  if (!forgotBtn || !modal) return;

  const closeModal = document.getElementById("close-modal");
  const emailRecoveryBtn = document.getElementById("email-recovery-btn");
  const phoneRecoveryBtn = document.getElementById("phone-recovery-btn");
  const enterCodeBtn = document.getElementById("enter-code-btn");
  const msg = document.getElementById("forgot-message");

  forgotBtn.addEventListener("click", () => {
    modal.style.display = "flex";
    if (msg) { msg.textContent = ""; msg.className = ""; }
  });

  closeModal.addEventListener("click", () => {
    modal.style.display = "none";
    if (msg) { msg.textContent = ""; msg.className = ""; }
  });

  emailRecoveryBtn.addEventListener("click", async () => {
    const email = document.getElementById("email").value.trim();
    if (!email) return displayMessage(msg, "Please enter your email first.", false);
    displayMessage(msg, "Sending recovery code via email...");
    const res = await apiCall("send_email_code", { email });
    displayMessage(msg, res.message, res.success);
  });

  phoneRecoveryBtn.addEventListener("click", async () => {
    const email = document.getElementById("email").value.trim();
    if (!email) return displayMessage(msg, "Please enter your email first.", false);
    displayMessage(msg, "Sending recovery code via phone...");
    const res = await apiCall("send_phone_code", { email });
    displayMessage(msg, res.message, res.success);
  });

  enterCodeBtn.addEventListener("click", async () => {
    const code = prompt("Enter your recovery code:");
    if (!code) return;
    displayMessage(msg, "Verifying recovery code...");
    const res = await apiCall("verify_recovery_code", { code });
    displayMessage(msg, res.message, res.success);
  });
}

/* ============================================================
   GLOBAL HELPERS
   ============================================================ */

function attachGlobalHandlers() {
  const logoutBtn = document.getElementById("logout-btn");
  if (logoutBtn) logoutBtn.addEventListener("click", logout);
}

/** Clears a form and its messages */
function clearForm(formId) {
  const form = document.getElementById(formId);
  if (!form) return;
  form.reset();

  const hidden = form.querySelector("input[type='hidden']");
  if (hidden) hidden.value = "";

  const msg = form.querySelector("p#form-message");
  if (msg) msg.textContent = "";
}

/** Displays formatted messages */
function displayMessage(element, text, success = null) {
  if (!element) return;
  element.textContent = text;
  if (success === null) element.className = "";
  else element.className = success ? "success-message" : "error-message";
}

/** Logout (client-side redirect) */
function logout() {
  window.location.href = "index.html";
}

/* ============================================================
   USERS
   ============================================================ */

async function handleUserFormSubmit(e) {
  e.preventDefault();

  const form = document.getElementById("user-registration-form");
  const messageEl = document.getElementById("form-message");
  if (!form) return;

  const user = {
    id: document.getElementById("user-id").value || "",
    name: document.getElementById("name").value.trim(),
    email: document.getElementById("email").value.trim(),
    contact: document.getElementById("contact").value.trim(),
    gender: document.getElementById("gender").value,
    address: document.getElementById("address").value.trim(),
    state: document.getElementById("state").value.trim(),
    country: document.getElementById("country").value.trim(),
  };

  if (!user.name || !user.email || !user.contact)
    return displayMessage(messageEl, "Please fill required fields.", false);

  displayMessage(messageEl, "Saving...");
  const action = user.id ? "update_user" : "create_user";
  const res = await apiCall(action, user);

  displayMessage(messageEl, res.message || "User saved.", res.success);
  if (res.success) {
    form.reset();
    if (document.querySelector("#users-table")) loadUsers();
  }
}

async function loadUsers() {
  const tableBody = document.querySelector("#users-table tbody");
  if (!tableBody) return;
  tableBody.innerHTML = "<tr><td colspan='10'>Loading...</td></tr>";

  const res = await apiCall("get_users");
  if (!res.success) {
    tableBody.innerHTML = `<tr><td colspan='10' class="error-message">${res.message || "Could not load users."}</td></tr>`;
    return;
  }

  const users = res.data || [];
  if (users.length === 0) {
    tableBody.innerHTML = "<tr><td colspan='10'>No users found.</td></tr>";
    return;
  }

  tableBody.innerHTML = users
    .map(
      (u) => `
    <tr>
      <td>${u.id}</td>
      <td>${escapeHtml(u.reg_id)}</td>
      <td>${escapeHtml(u.name)}</td>
      <td>${escapeHtml(u.email)}</td>
      <td>${escapeHtml(u.contact)}</td>
      <td>${escapeHtml(u.gender)}</td>
      <td>${escapeHtml(u.address)}</td>
      <td>${escapeHtml(u.state)}</td>
      <td>${escapeHtml(u.country)}</td>
      <td>
        <button class="btn-edit" data-id="${u.id}">Edit</button>
        <button class="btn-delete" data-id="${u.id}">Delete</button>
      </td>
    </tr>`
    )
    .join("");

  // Edit/Delete event handlers
  tableBody.querySelectorAll(".btn-edit").forEach((btn) =>
  btn.addEventListener("click", (ev) => {
    const id = ev.target.dataset.id;
    window.location.href = `users.html?id=${id}`;  // Redirect to users.html with ID
  })
);

  tableBody.querySelectorAll(".btn-delete").forEach((btn) =>
    btn.addEventListener("click", async (ev) => {
      const id = ev.target.dataset.id;
      if (!confirm("Delete this user?")) return;
      const r = await apiCall("delete_user", { id });
      if (r.success) loadUsers();
      else alert(r.message || "Failed to delete.");
    })
  );
}

function filterUsers(e) {
  const q = e.target.value.toLowerCase();
  document.querySelectorAll("#users-table tbody tr").forEach((tr) => {
    const name = tr.children[2]?.textContent.toLowerCase() || ""; // note: column positions may vary
    const email = tr.children[3]?.textContent.toLowerCase() || "";
    tr.style.display = name.includes(q) || email.includes(q) ? "" : "none";
  });
}

/* ============================================================
   EVENTS
   ============================================================ */

async function handleEventFormSubmit(e) {
  e.preventDefault();
  const id = document.getElementById("event-id").value || "";
  const name = document.getElementById("event-name").value.trim();
  const date = document.getElementById("event-date").value;
  const location = document.getElementById("event-location").value.trim();
  const description = document.getElementById("event-description").value.trim();
  const msg = document.getElementById("form-message");

  if (!name || !date || !location) return displayMessage(msg, "Please fill required fields.", false);

  displayMessage(msg, "Saving...");
  const res = await apiCall(id ? "update_event" : "create_event", {
    id,
    name,
    date,
    location,
    description,
  });

  displayMessage(msg, res.message, res.success);
  if (res.success) {
    document.getElementById("event-registration-form").reset();
    if (document.querySelector("#events-table")) loadEvents();
  }
}

async function loadEvents() {
  const tableBody = document.querySelector("#events-table tbody");
  if (!tableBody) return;
  tableBody.innerHTML = "<tr><td colspan='5'>Loading...</td></tr>";

  const res = await apiCall("get_events");
  if (!res.success) {
    tableBody.innerHTML = `<tr><td colspan='5' class="error-message">${res.message || "Could not load events."}</td></tr>`;
    return;
  }

  const events = res.data || [];
  if (events.length === 0) {
    tableBody.innerHTML = "<tr><td colspan='5'>No events found.</td></tr>";
    return;
  }

  tableBody.innerHTML = events
    .map(
      (ev) => `
      <tr>
        <td>${ev.id}</td>
        <td>${escapeHtml(ev.name)}</td>
        <td>${escapeHtml(ev.date)}</td>
        <td>${escapeHtml(ev.location)}</td>
        <td>
          <button class="btn-edit-event" data-id="${ev.id}">Edit</button>
          <button class="btn-delete-event" data-id="${ev.id}">Delete</button>
        </td>
      </tr>`
    )
    .join("");

  tableBody.querySelectorAll(".btn-edit-event").forEach((btn) =>
    btn.addEventListener("click", async (e) => {
      const id = e.target.dataset.id;
      const r = await apiCall("get_event", { id });
      if (!r.success || !r.data) return alert(r.message || "Failed to load event.");
      const ev = r.data;
      document.getElementById("event-id").value = ev.id;
      document.getElementById("event-name").value = ev.name;
      document.getElementById("event-date").value = ev.date;
      document.getElementById("event-location").value = ev.location;
      document.getElementById("event-description").value = ev.description || "";
      window.scrollTo({ top: 0, behavior: "smooth" });
    })
  );

  tableBody.querySelectorAll(".btn-delete-event").forEach((btn) =>
    btn.addEventListener("click", async (e) => {
      const id = e.target.dataset.id;
      if (!confirm("Delete this event?")) return;
      const r = await apiCall("delete_event", { id });
      if (r.success) loadEvents();
      else alert(r.message || "Failed to delete.");
    })
  );
}

function filterEvents(e) {
  const q = e.target.value.toLowerCase();
  document.querySelectorAll("#events-table tbody tr").forEach((tr) => {
    const name = tr.children[1]?.textContent.toLowerCase() || "";
    tr.style.display = name.includes(q) ? "" : "none";
  });
}

/* ============================================================
   ATTENDANCE
   ============================================================ */

async function loadEventOptions() {
  const select = document.getElementById("event-select");
  if (!select) return;
  select.innerHTML = "<option>Loading events...</option>";

  const res = await apiCall("get_events");
  if (!res.success || !res.data?.length) {
    select.innerHTML = "<option disabled>No events found</option>";
    return;
  }

  select.innerHTML = '<option value="">Select an event...</option>';
  res.data.forEach((ev) => {
    const opt = document.createElement("option");
    opt.value = ev.id;
    opt.textContent = `${ev.name} (${ev.date})`;
    select.appendChild(opt);
  });
}

async function markAttendance(type) {
  const regId = document.getElementById("reg-id").value.trim();
  const eventId = document.getElementById("event-select").value;
  const msg = document.getElementById("attendance-message");

  if (!eventId) return displayMessage(msg, "Please select an event.", false);
  if (!regId) return displayMessage(msg, "Please enter a registration ID.", false);

  displayMessage(msg, "Processing...");
  const res = await apiCall("mark_attendance", { reg_id: regId, event_id: eventId, type });
  displayMessage(msg, res.message, res.success);
  if (res.success) loadAttendanceRecords();
}

async function loadAttendanceRecords() {
  const tableBody = document.querySelector("#attendance-table tbody");
  if (!tableBody) return;
  tableBody.innerHTML = "<tr><td colspan='5'>Loading...</td></tr>";

  const res = await apiCall("get_attendance");
  if (!res.success || !res.data?.length) {
    tableBody.innerHTML = "<tr><td colspan='5'>No records found.</td></tr>";
    return;
  }

  tableBody.innerHTML = res.data
    .map(
      (r) => `
      <tr>
        <td>${r.id}</td>
        <td>${escapeHtml(r.user_name)}</td>
        <td>${escapeHtml(r.event_name)}</td>
        <td>${escapeHtml(r.check_in || "")}</td>
        <td>${escapeHtml(r.check_out || "")}</td>
      </tr>`
    )
    .join("");
}

/* ============================================================
   AUDIT LOGS
   ============================================================ */

async function loadAuditLogs() {
  const tableBody = document.querySelector("#audit-table tbody");
  if (!tableBody) return;
  tableBody.innerHTML = "<tr><td colspan='4'>Loading...</td></tr>";

  const res = await apiCall("get_audit_logs");
  if (!res.success) {
    tableBody.innerHTML = `<tr><td colspan='4' class="error-message">${res.message || "Could not load logs."}</td></tr>`;
    return;
  }

  const rows = res.data || [];
  if (rows.length === 0) {
    tableBody.innerHTML = "<tr><td colspan='4'>No logs found.</td></tr>";
    return;
  }

  tableBody.innerHTML = rows
    .map(
      (r) => `
      <tr>
        <td>${r.id}</td>
        <td>${escapeHtml(r.user)}</td>
        <td>${escapeHtml(r.action)}</td>
        <td>${escapeHtml(r.timestamp)}</td>
      </tr>`
    )
    .join("");
}

/* ============================================================
   PARTICIPANTS
   ============================================================ */

async function loadParticipants() {
  const tableBody = document.querySelector("#participants-table tbody");
  if (!tableBody) return;
  tableBody.innerHTML = "<tr><td colspan='5'>Loading...</td></tr>";

  const res = await apiCall("get_participants");
  if (!res.success) {
    tableBody.innerHTML = `<tr><td colspan='5' class="error-message">${res.message || "Could not load participants."}</td></tr>`;
    return;
  }

  const list = res.data || [];
  if (list.length === 0) {
    tableBody.innerHTML = "<tr><td colspan='5'>No participants found.</td></tr>";
    return;
  }

  tableBody.innerHTML = list
    .map(
      (p) => `
      <tr>
        <td>${p.id}</td>
        <td>${escapeHtml(p.name)}</td>
        <td>${escapeHtml(p.event_name || p.event)}</td>
        <td>${escapeHtml(p.status || "")}</td>
        <td>${escapeHtml(p.timestamp || "")}</td>
      </tr>`
    )
    .join("");
}

function filterParticipants() {
  const q = document.getElementById("search-participant")?.value.toLowerCase() || "";
  document.querySelectorAll("#participants-table tbody tr").forEach((tr) => {
    const name = tr.children[1]?.textContent.toLowerCase() || "";
    const event = tr.children[2]?.textContent.toLowerCase() || "";
    tr.style.display = name.includes(q) || event.includes(q) ? "" : "none";
  });
}

/* ============================================================
   REPORTS
   ============================================================ */

async function generateReport() {
  const from = document.getElementById("date-from").value;
  const to = document.getElementById("date-to").value;
  const resultsBody = document.querySelector("#reports-table tbody");
  if (!resultsBody) return;

  if (!from || !to) return alert("Choose a valid date range.");

  resultsBody.innerHTML = "<tr><td colspan='4'>Generating...</td></tr>";
  const res = await apiCall("generate_report", { date_from: from, date_to: to });

  if (!res.success) {
    resultsBody.innerHTML = `<tr><td colspan='4' class="error-message">${res.message || "Could not generate report."}</td></tr>`;
    return;
  }

  const rows = res.data || [];
  if (rows.length === 0) {
    resultsBody.innerHTML = "<tr><td colspan='4'>No data for selected range.</td></tr>";
    return;
  }

  resultsBody.innerHTML = rows
    .map(
      (r) => `
      <tr>
        <td>${escapeHtml(r.event_name)}</td>
        <td>${r.total_participants ?? 0}</td>
        <td>${r.checked_in ?? 0}</td>
        <td>${r.checked_out ?? 0}</td>
      </tr>`
    )
    .join("");
}

/* ============================================================
   HISTORY
   ============================================================ */

async function loadHistory() {
  const tableBody = document.querySelector("#history-table tbody");
  if (!tableBody) return;
  tableBody.innerHTML = "<tr><td colspan='5'>Loading...</td></tr>";

  const res = await apiCall("get_history");
  if (!res.success) {
    tableBody.innerHTML = `<tr><td colspan='5' class="error-message">${res.message || "Could not load history."}</td></tr>`;
    return;
  }

  const rows = res.data || [];
  if (rows.length === 0) {
    tableBody.innerHTML = "<tr><td colspan='5'>No history found.</td></tr>";
    return;
  }

  tableBody.innerHTML = rows
    .map(
      (r) => `
      <tr>
        <td>${r.id}</td>
        <td>${escapeHtml(r.user_name)}</td>
        <td>${escapeHtml(r.event_name)}</td>
        <td>${escapeHtml(r.check_in || "")}</td>
        <td>${escapeHtml(r.check_out || "")}</td>
      </tr>`
    )
    .join("");
}
async function apiCall(action, data = {}) {
  const formData = new FormData();
  formData.append("action", action);

  Object.keys(data).forEach(key => {
    formData.append(key, data[key]);
  });

  const response = await fetch("php/api_handler.php", {
    method: "POST",
    body: formData
  });

  return response.json();
}


/* ============================================================
   UTILITIES
   ============================================================ */

function escapeHtml(unsafe) {
  if (unsafe === null || unsafe === undefined) return "";
  return String(unsafe)
    .replaceAll("&", "&amp;")
    .replaceAll("<", "&lt;")
    .replaceAll(">", "&gt;")
    .replaceAll('"', "&quot;")
    .replaceAll("'", "&#039;");
}

/* ============================================================
   EXPORTS (make functions available to HTML inline handlers)
   ============================================================ */

window.handleUserFormSubmit = handleUserFormSubmit;
window.loadUsers = loadUsers;
window.filterUsers = filterUsers;

window.handleEventFormSubmit = handleEventFormSubmit;
window.loadEvents = loadEvents;
window.filterEvents = filterEvents;

window.markAttendance = markAttendance;
window.loadEventOptions = loadEventOptions;
window.loadAttendanceRecords = loadAttendanceRecords;

window.loadAuditLogs = loadAuditLogs;
window.loadParticipants = loadParticipants;
window.filterParticipants = filterParticipants;

window.generateReport = generateReport;
window.loadHistory = loadHistory;
