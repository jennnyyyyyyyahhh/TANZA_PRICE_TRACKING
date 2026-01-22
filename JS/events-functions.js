  // ---------- Notification Functions ----------
        function showNotif(message, type = 'success') {
            const notif = document.getElementById('notif');
            const notifMsg = document.getElementById('notifMsg');
            const closeBtn = document.getElementById('notifClose');

            notifMsg.textContent = message;
            notif.style.backgroundColor = type === 'success' ? '#16a34a' : '#dc2626';

            notif.style.display = 'block';
            setTimeout(() => notif.style.opacity = '1', 10);

            // Automatically hide after 3 seconds
            const timer = setTimeout(() => hideNotif(true), 3000);

            // Manual close
            closeBtn.onclick = () => {
                clearTimeout(timer);
                hideNotif(true);
            };
        }

        function hideNotif(clearUrl = false) {
            const notif = document.getElementById('notif');
            notif.style.opacity = '0';
            setTimeout(() => {
                notif.style.display = 'none';
                if (clearUrl) {
                    const baseUrl = window.location.origin + window.location.pathname;
                    window.history.replaceState({}, document.title, baseUrl);
                }
            }, 300);
        }
        document.addEventListener("DOMContentLoaded", function() {
    const registerModal = new bootstrap.Modal(document.getElementById("registerModal"));
    const detailsModal = new bootstrap.Modal(document.getElementById("eventDetailsModal"));
    const form = document.getElementById("eventRegistrationForm");

    // Show registration modal
    document.querySelectorAll(".event-action-btn").forEach(btn => {
        btn.addEventListener("click", e => {
            e.preventDefault();
            const eventId = btn.closest(".event-card").dataset.eventId;
            document.getElementById("event_id").value = eventId;
            registerModal.show();
        });
    });

    // Handle form submission
    form.addEventListener("submit", async e => {
        e.preventDefault();
        const response = await fetch("php/register_event.php", {
            method: "POST",
            body: new FormData(form)
        });
        const result = await response.json();

        if (result.status === 'success') {
            showNotif(result.message, 'success');
            setTimeout(() => {
                registerModal.hide();
                location.reload();
            }, 1000);
        } else {
            showNotif(result.message || 'Something went wrong', 'error');
        }
    });

    // Show event details modal
    document.querySelectorAll(".event-action-link").forEach(link => {
        link.addEventListener("click", async e => {
            e.preventDefault();
            const eventId = link.closest(".event-card").dataset.eventId;

            const response = await fetch(`php/get_event_details.php?id=${eventId}`);
            const result = await response.json();

            if (result.status === 'success') {
                const e = result.data;
                document.getElementById("detailTitle").textContent = e.title;
                document.getElementById("detailType").textContent = e.type;
                document.getElementById("detailDate").textContent = e.date;
                document.getElementById("detailTime").textContent = e.start_time + " - " + e.end_time;
                document.getElementById("detailLocation").textContent = e.location;
                document.getElementById("detailDescription").textContent = e.description;
                document.getElementById("detailStatus").textContent = e.status;
                detailsModal.show();
            } else {
                alert(result.message || "Unable to fetch event details");
            }
        });
    });
});
