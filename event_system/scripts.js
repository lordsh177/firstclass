// --- API CALL FUNCTION ---
// keeps same API shape used previously (php/api_handler.php expects "action")
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

// --- LOGIN (keeps your existing login code) ---
document.addEventListener("DOMContentLoaded", () => {
  const loginForm = document.getElementById("login-form");
  if (loginForm) loginForm.addEventListener("submit", handleLogin);

  setupForgotPassword();

  // Attach global handlers used across pages
  attachGlobalHandlers();
});

async function handleLogin(event) {
  event.preventDefault();
  const email = document.getElementById("email").value.trim();
  const password = document.getElementById("password").value.trim();
  const message = document.getElementById("message");

  if (!email || !password) {
    message.textContent = "Please enter email and password.";
    message.className = "error-message";
    return;
  }

  message.textContent = "Logging in...";
  const result = await apiCall("login", { email, password });

  if (result.success) {
    message.className = "success-message";
    message.textContent = result.message || "Login successful.";
    // short pause so message shows then redirect
    setTimeout(() => (window.location.href = "dashboard.html"), 700);
  } else {
    message.className = "error-message";
    message.textContent = result.message || "Login failed. Please try again.";
  }
}

// --- FORGOT PASSWORD LOGIC ---
function setupForgotPassword() {
  const forgotBtn = document.getElementById("forgot-btn");
  const modal = document.getElementById("forgot-modal");
  const closeModal = document.getElementById("close-modal");
  const emailRecoveryBtn = document.getElementById("email-recovery-btn");
  const phoneRecoveryBtn = document.getElementById("phone-recovery-btn");
  const enterCodeBtn = document.getElementById("enter-code-btn");
  const msg = document.getElementById("forgot-message");

  if (!forgotBtn || !modal) return;

  forgotBtn.addEventListener("click", () => {
    modal.style.display = "flex";
    msg.textContent = "";
  });

  closeModal.addEventListener("click", () => {
    modal.style.display = "none";
    msg.textContent = "";
  });

  emailRecoveryBtn.addEventListener("click", async () => {
    const email = document.getElementById("email").value.trim();
    if (!email) {
      msg.textContent = "Please enter your email first.";
      msg.className = "error-message";
      return;
    }
    msg.textContent = "Sending recovery code via email...";
    const result = await apiCall("send_email_code", { email });
    msg.textContent = result.message;
    msg.className = result.success ? "success-message" : "error-message";
  });

  phoneRecoveryBtn.addEventListener("click", async () => {
    const email = document.getElementById("email").value.trim();
    if (!email) {
      msg.textContent = "Please enter your email first.";
      msg.className = "error-message";
      return;
    }
    msg.textContent = "Sending recovery code via phone...";
    const result = await apiCall("send_phone_code", { email });
    msg.textContent = result.message;
    msg.className = result.success ? "success-message" : "error-message";
  });

  enterCodeBtn.addEventListener("click", async () => {
    const code = prompt("Enter your recovery code:");
    if (!code) return;
    msg.textContent = "Verifying recovery code...";
    const result = await apiCall("verify_recovery_code", { code });
    msg.textContent = result.message;
    msg.className = result.success ? "success-message" : "error-message";
  });
}

// --- GLOBAL HELPERS USED BY PAGES ---
function attachGlobalHandlers() {
  // logout button
  const logoutBtn = document.getElementById("logout-btn");
  if (logoutBtn) logoutBtn.addEventListener("click", logout);

  // form clear handlers (buttons may call clearForm directly)
}

// clear form by id
function clearForm(formId) {
  const form = document.getElementById(formId);
  if (!form) return;
  form.reset();
  // also clear hidden id fields if any
  const hidden = form.querySelector("input[type='hidden']");
  if (hidden) hidden.value = "";
  const msg = form.querySelector("p#form-message");
  if (msg) {
    msg.textContent = "";
    msg.className = "";
  }
}

// logout
function logout() {
  // If you want to call server-side logout: apiCall("logout")
  window.location.href = "index.html";
}

/* -----------------------
   USERS (users.html)
   ----------------------- */
async function handleUserFormSubmit(e) {
  e.preventDefault();
  const form = document.getElementById("user-registration-form");
  if (!form) return;

  const id = document.getElementById("user-id").value || "";
  const name = document.getElementById("name").value.trim();
  const email = document.getElementById("email").value.trim();
  const contact = document.getElementById("contact").value.trim();
  const gender = document.getElementById("gender").value;
  const address = document.getElementById("address").value.trim();
  const state = document.getElementById("state").value.trim();
  const country = document.getElementById("country").value.trim();
  const messageEl = document.getElementById("form-message");

  if (!name || !email || !contact) {
    messageEl.textContent = "Please fill required fields.";
    messageEl.className = "error-message";
    return;
  }

  const action = id ? "update_user" : "create_user";
  const payload = { id, name, email, contact, gender, address, state, country };

  messageEl.textContent = "Saving...";
  const res = await apiCall(action, payload);

  if (res.success) {
    messageEl.textContent = res.message || "User saved.";
    messageEl.className = "success-message";
    form.reset();
    if (document.querySelector("#users-table")) loadUsers();
  } else {
    messageEl.textContent = res.message || "Failed to save.";
    messageEl.className = "error-message";
  }
}

async function loadUsers() {
  const tableBody = document.querySelector("#users-table tbody");
  if (!tableBody) return;
  tableBody.innerHTML = "<tr><td colspan='5'>Loading...</td></tr>";

  const res = await apiCall("get_users");
  if (!res.success) {
    tableBody.innerHTML = `<tr><td colspan='5' class="error-message">${res.message || "Could not load users."}</td></tr>`;
    return;
  }

  const users = res.data || [];
  if (users.length === 0) {
    tableBody.innerHTML = "<tr><td colspan='5'>No users found.</td></tr>";
    return;
  }

  tableBody.innerHTML = "";
  users.forEach((u) => {
    const tr = document.createElement("tr");
    tr.innerHTML = `
      <td>${u.id}</td>
      <td>${escapeHtml(u.name)}</td>
      <td>${escapeHtml(u.email)}</td>
      <td>${escapeHtml(u.contact)}</td>
      <td>
        <button class="btn-edit" data-id="${u.id}">Edit</button>
        <button class="btn-delete" data-id="${u.id}">Delete</button>
      </td>
    `;
    tableBody.appendChild(tr);
  });

  // attach button listeners
  tableBody.querySelectorAll(".btn-edit").forEach((btn) => {
    btn.addEventListener("click", async (ev) => {
      const id = ev.target.dataset.id;
      const r = await apiCall("get_user", { id });
      if (r.success && r.data) {
        const u = r.data;
        document.getElementById("user-id").value = u.id;
        document.getElementById("name").value = u.name;
        document.getElementById("email").value = u.email;
        document.getElementById("contact").value = u.contact;
        document.getElementById("gender").value = u.gender || "Male";
        document.getElementById("address").value = u.address || "";
        document.getElementById("state").value = u.state || "";
        document.getElementById("country").value = u.country || "";
        window.scrollTo({ top: 0, behavior: "smooth" });
      } else {
        alert(r.message || "Failed to load user.");
      }
    });
  });

  tableBody.querySelectorAll(".btn-delete").forEach((btn) => {
    btn.addEventListener("click", async (ev) => {
      const id = ev.target.dataset.id;
      if (!confirm("Delete this user?")) return;
      const r = await apiCall("delete_user", { id });
      if (r.success) {
        loadUsers();
      } else {
        alert(r.message || "Failed to delete.");
      }
    });
  });
}

function filterUsers(e) {
  const q = e.target.value.toLowerCase();
  document.querySelectorAll("#users-table tbody tr").forEach((tr) => {
    const name = tr.children[1] ? tr.children[1].textContent.toLowerCase() : "";
    const email = tr.children[2] ? tr.children[2].textContent.toLowerCase() : "";
    tr.style.display = name.includes(q) || email.includes(q) ? "" : "none";
  });
}

/* -----------------------
   EVENTS (events.html)
   ----------------------- */
async function handleEventFormSubmit(e) {
  e.preventDefault();
  const id = document.getElementById("event-id").value || "";
  const name = document.getElementById("event-name").value.trim();
  const date = document.getElementById("event-date").value;
  const location = document.getElementById("event-location").value.trim();
  const description = document.getElementById("event-description").value.trim();
  const messageEl = document.getElementById("form-message");

  if (!name || !date || !location) {
    messageEl.textContent = "Please fill required fields.";
    messageEl.className = "error-message";
    return;
  }

  const action = id ? "update_event" : "create_event";
  const res = await apiCall(action, { id, name, date, location, description });

  if (res.success) {
    messageEl.textContent = res.message || "Event saved.";
    messageEl.className = "success-message";
    document.getElementById("event-registration-form").reset();
    if (document.querySelector("#events-table")) loadEvents();
  } else {
    messageEl.textContent = res.message || "Failed to save event.";
    messageEl.className = "error-message";
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

  tableBody.innerHTML = "";
  events.forEach((ev) => {
    const tr = document.createElement("tr");
    tr.innerHTML = `
      <td>${ev.id}</td>
      <td>${escapeHtml(ev.name)}</td>
      <td>${escapeHtml(ev.date)}</td>
      <td>${escapeHtml(ev.location)}</td>
      <td>
        <button class="btn-edit-event" data-id="${ev.id}">Edit</button>
        <button class="btn-delete-event" data-id="${ev.id}">Delete</button>
      </td>
    `;
    tableBody.appendChild(tr);
  });

  tableBody.querySelectorAll(".btn-edit-event").forEach((btn) => {
    btn.addEventListener("click", async (e) => {
      const id = e.target.dataset.id;
      const r = await apiCall("get_event", { id });
      if (r.success && r.data) {
        const ev = r.data;
        document.getElementById("event-id").value = ev.id;
        document.getElementById("event-name").value = ev.name;
        document.getElementById("event-date").value = ev.date;
        document.getElementById("event-location").value = ev.location;
        document.getElementById("event-description").value = ev.description || "";
        window.scrollTo({ top: 0, behavior: "smooth" });
      } else {
        alert(r.message || "Failed to load event.");
      }
    });
  });

  tableBody.querySelectorAll(".btn-delete-event").forEach((btn) => {
    btn.addEventListener("click", async (e) => {
      const id = e.target.dataset.id;
      if (!confirm("Delete this event?")) return;
      const r = await apiCall("delete_event", { id });
      if (r.success) loadEvents();
      else alert(r.message || "Failed to delete.");
    });
  });
}

function filterEvents(e) {
  const q = e.target.value.toLowerCase();
  document.querySelectorAll("#events-table tbody tr").forEach((tr) => {
    const name = tr.children[1] ? tr.children[1].textContent.toLowerCase() : "";
    tr.style.display = name.includes(q) ? "" : "none";
  });
}

/* -----------------------
   ATTENDANCE (attendance.html)
   ----------------------- */
async function markAttendance(type) {
  const regId = document.getElementById("reg-id").value.trim();
  if (!regId) {
    setAttendanceMessage("Please enter a registration ID.", false);
    return;
  }

  const res = await apiCall("mark_attendance", { reg_id: regId, type }); // type = 'in' | 'out'
  setAttendanceMessage(res.message, res.success);
}

function setAttendanceMessage(text, ok) {
  const el = document.getElementById("attendance-message");
  if (!el) return;
  el.textContent = text;
  el.className = ok ? "success-message" : "error-message";
}

async function loadAttendance(filters = {}) {
  const cont = document.getElementById("view-attendance-container");
  if (!cont) return;
  // not implemented full UI yet; call API and console.log results for debugging
  const res = await apiCall("get_attendance", filters);
  if (res.success) {
    console.log("Attendance:", res.data);
  } else {
    console.warn("Could not load attendance:", res.message);
  }
}
// Load available events into dropdown (for attendance)
async function loadEventOptions() {
  const select = document.getElementById("event-select");
  if (!select) return;
  select.innerHTML = "<option>Loading events...</option>";

  const res = await apiCall("get_events");
  if (!res.success || !res.data) {
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

// Override markAttendance to use event_id
async function markAttendance(type) {
  const regId = document.getElementById("reg-id").value.trim();
  const eventId = document.getElementById("event-select").value;
  const message = document.getElementById("attendance-message");

  if (!eventId) {
    message.textContent = "Please select an event.";
    message.className = "error-message";
    return;
  }
  if (!regId) {
    message.textContent = "Please enter a registration ID.";
    message.className = "error-message";
    return;
  }

  message.textContent = "Processing...";
  const res = await apiCall("mark_attendance", {
    reg_id: regId,
    event_id: eventId,
    type,
  });

  message.textContent = res.message || "Action complete.";
  message.className = res.success ? "success-message" : "error-message";
  if (res.success) loadAttendanceRecords();
}

// Load attendance table
async function loadAttendanceRecords() {
  const tableBody = document.querySelector("#attendance-table tbody");
  if (!tableBody) return;
  tableBody.innerHTML = "<tr><td colspan='5'>Loading...</td></tr>";

  const res = await apiCall("get_attendance");
  if (!res.success || !res.data) {
    tableBody.innerHTML = "<tr><td colspan='5'>No records found.</td></tr>";
    return;
  }

  tableBody.innerHTML = "";
  res.data.forEach((r) => {
    const tr = document.createElement("tr");
    tr.innerHTML = `
      <td>${r.id}</td>
      <td>${escapeHtml(r.user_name)}</td>
      <td>${escapeHtml(r.event_name)}</td>
      <td>${escapeHtml(r.check_in || "")}</td>
      <td>${escapeHtml(r.check_out || "")}</td>
    `;
    tableBody.appendChild(tr);
  });
}

window.loadEventOptions = loadEventOptions;
window.loadAttendanceRecords = loadAttendanceRecords;


/* -----------------------
   AUDIT LOGS (audit.html)
   ----------------------- */
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

  tableBody.innerHTML = "";
  rows.forEach((r) => {
    const tr = document.createElement("tr");
    tr.innerHTML = `
      <td>${r.id}</td>
      <td>${escapeHtml(r.user)}</td>
      <td>${escapeHtml(r.action)}</td>
      <td>${escapeHtml(r.timestamp)}</td>
    `;
    tableBody.appendChild(tr);
  });
}

/* -----------------------
   PARTICIPANTS (participants.html)
   ----------------------- */
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

  tableBody.innerHTML = "";
  list.forEach((p) => {
    const tr = document.createElement("tr");
    tr.innerHTML = `
      <td>${p.id}</td>
      <td>${escapeHtml(p.name)}</td>
      <td>${escapeHtml(p.event_name || p.event)}</td>
      <td>${escapeHtml(p.status || "")}</td>
      <td>${escapeHtml(p.timestamp || "")}</td>
    `;
    tableBody.appendChild(tr);
  });
}

/* -----------------------
   REPORTS (reports.html)
   ----------------------- */
async function generateReport() {
  const from = document.getElementById("date-from").value;
  const to = document.getElementById("date-to").value;
  const resultsBody = document.querySelector("#reports-table tbody");
  if (!resultsBody) return;

  if (!from || !to) {
    alert("Choose a valid date range.");
    return;
  }

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

  resultsBody.innerHTML = "";
  rows.forEach((r) => {
    const tr = document.createElement("tr");
    tr.innerHTML = `
      <td>${escapeHtml(r.event_name)}</td>
      <td>${r.total_participants ?? 0}</td>
      <td>${r.checked_in ?? 0}</td>
      <td>${r.checked_out ?? 0}</td>
    `;
    resultsBody.appendChild(tr);
  });
}

/* -----------------------
   HISTORY (history.html)
   ----------------------- */
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

  tableBody.innerHTML = "";
  rows.forEach((r) => {
    const tr = document.createElement("tr");
    tr.innerHTML = `
      <td>${r.id}</td>
      <td>${escapeHtml(r.user_name)}</td>
      <td>${escapeHtml(r.event_name)}</td>
      <td>${escapeHtml(r.check_in || "")}</td>
      <td>${escapeHtml(r.check_out || "")}</td>
    `;
    tableBody.appendChild(tr);
  });
}

/* -----------------------
   Small utilities
   ----------------------- */
function escapeHtml(unsafe) {
  if (!unsafe && unsafe !== 0) return "";
  return String(unsafe)
    .replaceAll("&", "&amp;")
    .replaceAll("<", "&lt;")
    .replaceAll(">", "&gt;")
    .replaceAll('"', "&quot;")
    .replaceAll("'", "&#039;");
}

// Export handlers for inline event listeners in the HTML that call by name:
window.handleUserFormSubmit = handleUserFormSubmit;
window.loadUsers = loadUsers;
window.filterUsers = filterUsers;

window.handleEventFormSubmit = handleEventFormSubmit;
window.loadEvents = loadEvents;
window.filterEvents = filterEvents;

window.markAttendance = markAttendance;
window.loadAttendance = loadAttendance;

window.loadAuditLogs = loadAuditLogs;
window.loadParticipants = loadParticipants;
window.generateReport = generateReport;
window.loadHistory = loadHistory;

