 document.addEventListener("DOMContentLoaded", () => {
  const bell = document.getElementById("notificationBell");
  const badge = document.getElementById("notificationBadge");
  const dropdown = document.getElementById("notificationDropdown");

  async function loadNotifications() {
    const res = await fetch("/php/fetch_notifications.php");
    const data = await res.json();
    dropdown.innerHTML = "";

    // Header
    const header = document.createElement("div");
    header.className = "px-3 py-3 bg-gradient bg-primary text-white d-flex justify-content-between align-items-center";
    header.innerHTML = `
      <h6 class="m-0 fw-semibold">Notifications</h6>
      <div>
        <button class="btn btn-sm btn-light me-2" id="markAllBtn">Mark all as read</button>
        <button class="btn btn-sm btn-danger fw-bold px-2" id="closeDropdown">&times;</button>
      </div>`;
    dropdown.appendChild(header);

    // Empty
    if (data.length === 0) {
      badge.textContent = "";
      dropdown.innerHTML += `
        <div class="text-center py-5 bg-light">
          <i class="fas fa-bell-slash fa-2x text-muted mb-2"></i>
          <p class="text-muted">No notifications yet.</p>
        </div>`;
      return;
    }

    const readList = JSON.parse(localStorage.getItem("readNotifications") || "[]");
    let unreadCount = 0;
    const body = document.createElement("div");
    body.className = "list-group list-group-flush";

    data.forEach((n) => {
      const isRead = readList.includes(n.message);
      const item = document.createElement("div");
      item.className = `list-group-item border-0 py-3 ${isRead ? "bg-white" : "bg-light border-start border-4 border-primary fw-semibold"}`;
      item.innerHTML = `
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="text-dark small">${n.message}</div>
            <div class="text-muted small">${new Date(n.time).toLocaleString()}</div>
          </div>
          ${!isRead ? '<span class="badge bg-primary rounded-circle" style="width:10px;height:10px;"></span>' : ""}
        </div>`;

      item.style.cursor = "pointer";
      item.addEventListener("click", (e) => {
        e.preventDefault();
        markAsRead(n.message);
        alert(n.message);
      });

      item.addEventListener("mouseenter", () => (item.style.backgroundColor = "#f8f9fa"));
      item.addEventListener("mouseleave", () => (item.style.backgroundColor = isRead ? "white" : "#f1f5ff"));

      body.appendChild(item);
      if (!isRead) unreadCount++;
    });

    dropdown.appendChild(body);
    badge.textContent = unreadCount > 0 ? unreadCount : "";

    const footer = document.createElement("div");
    footer.className = "text-center text-muted small py-2 bg-light";
    footer.textContent = "Updated just now";
    dropdown.appendChild(footer);

    document.getElementById("markAllBtn").addEventListener("click", markAllAsRead);
    document.getElementById("closeDropdown").addEventListener("click", () => {
      dropdown.style.display = "none";
    });
  }

  function markAsRead(message) {
    let readList = JSON.parse(localStorage.getItem("readNotifications") || "[]");
    if (!readList.includes(message)) {
      readList.push(message);
      localStorage.setItem("readNotifications", JSON.stringify(readList));
    }
    loadNotifications();
  }

  function markAllAsRead() {
    fetch("/php/fetch_notifications.php")
      .then((res) => res.json())
      .then((data) => {
        const messages = data.map((n) => n.message);
        localStorage.setItem("readNotifications", JSON.stringify(messages));
        loadNotifications();
      });
  }

  bell.addEventListener("click", () => {
    dropdown.style.display = dropdown.style.display === "block" ? "none" : "block";
    dropdown.classList.toggle("fade-in");
  });

  document.addEventListener("click", (e) => {
    if (!e.target.closest("#notificationContainer")) {
      dropdown.style.display = "none";
    }
  });

  loadNotifications();
  setInterval(loadNotifications, 30000);
});

