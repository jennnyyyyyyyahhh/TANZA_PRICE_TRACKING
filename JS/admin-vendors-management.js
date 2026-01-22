 // Toggle status button
        document.querySelectorAll('.toggle-status-btn').forEach(button => {
            button.addEventListener('click', function() {
                const userId = parseInt(this.dataset.userId);
                let currentStatus = this.dataset.status.toLowerCase().trim();

                if (!userId || !currentStatus) return;

                let newStatus = currentStatus === 'active' ? 'inactive' : 'active';

                fetch('php/update_status.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ id: userId, status: newStatus })
                })
                .then(res => res.json())
                .then(data => {
                    if(data.success) {
                        this.dataset.status = newStatus;
                        this.querySelector('i').className = newStatus === 'active' ? 'fas fa-pause' : 'fas fa-play';

                        const badge = this.closest('tr').querySelector('.status-badge');
                        badge.textContent = newStatus;
                        badge.className = 'status-badge ' + newStatus;

                        const suspendBtn = this.closest('tr').querySelector('.suspend-btn');
                        if (suspendBtn) suspendBtn.disabled = newStatus === 'suspended';

                        // Show notification
                        showNotif(`Status updated to "${newStatus}"`, '#4caf50');
                    } else {
                        showNotif(`Error: ${data.message}`, '#f44336');
                    }
                })
                .catch(err => showNotif(`AJAX failed: ${err}`, '#f44336'));
            });
        });

        // Suspend button
        document.querySelectorAll('.suspend-btn').forEach(button => {
            button.addEventListener('click', function() {
                const userId = parseInt(this.dataset.userId);
                const newStatus = 'suspended';

                if (!userId) return;

                fetch('php/update_status.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ id: userId, status: newStatus })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        const badge = this.closest('tr').querySelector('.status-badge');
                        badge.textContent = newStatus;
                        badge.className = 'status-badge ' + newStatus;

                        this.disabled = true;

                        const toggleBtn = this.closest('tr').querySelector('.toggle-status-btn');
                        if (toggleBtn) {
                            toggleBtn.dataset.status = newStatus;
                            toggleBtn.querySelector('i').className = 'fas fa-play';
                            toggleBtn.disabled = false;
                        }

                        // Show notification
                        showNotif(`User suspended successfully`, '#ff9800');
                    } else {
                        showNotif(`Error: ${data.message}`, '#f44336');
                    }
                })
                .catch(err => showNotif(`AJAX failed: ${err}`, '#f44336'));
            });
        });


        function showNotif(message, color = '#4caf50') {
            const notif = document.getElementById('notif');
            const notifMsg = document.getElementById('notifMsg');
            
            notif.style.background = color;
            notifMsg.textContent = message;
            notif.style.display = 'block';
            
            // Fade in
            setTimeout(() => notif.style.opacity = 1, 50);
            
            // Auto hide after 3 seconds
            setTimeout(() => {
                notif.style.opacity = 0;
                setTimeout(() => notif.style.display = 'none', 300);
            }, 3000);
        }

        // Close button
        document.getElementById('notifClose').addEventListener('click', () => {
            const notif = document.getElementById('notif');
            notif.style.opacity = 0;
            setTimeout(() => notif.style.display = 'none', 300);
        });