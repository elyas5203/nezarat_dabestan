document.addEventListener('DOMContentLoaded', function() {
    // --- Feather Icons ---
    feather.replace();

    // --- Sidebar Toggle for Mobile ---
    const menuToggle = document.getElementById('menu-toggle');
    const sidebar = document.querySelector('.sidebar');
    const mainContent = document.querySelector('.main-content');

    if (menuToggle && sidebar && mainContent) {
        menuToggle.addEventListener('click', function() {
            sidebar.classList.toggle('active');
            mainContent.classList.toggle('shifted');
        });
    }

    // --- Sidebar Submenu Toggle ---
    const submenuLinks = document.querySelectorAll('.has-submenu > a');
    submenuLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const parentLi = this.parentElement;

            // Close other open submenus
            document.querySelectorAll('.has-submenu.open').forEach(openMenu => {
                if (openMenu !== parentLi) {
                    openMenu.classList.remove('open');
                    openMenu.querySelector('.submenu').style.maxHeight = null;
                }
            });

            // Toggle current submenu
            parentLi.classList.toggle('open');
            const submenu = this.nextElementSibling;
            if (submenu.style.maxHeight) {
                submenu.style.maxHeight = null;
            } else {
                submenu.style.maxHeight = submenu.scrollHeight + "px";
            }
        });
    });

    // --- Live Persian Date and Time ---
    const timeElement = document.getElementById('time');
    const dateElement = document.getElementById('date');

    function updateTime() {
        if (timeElement && dateElement) {
            const now = new Date();
            // Time
            timeElement.textContent = now.toLocaleTimeString('fa-IR', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
            // Date
            dateElement.textContent = new Intl.DateTimeFormat('fa-IR', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                weekday: 'long'
            }).format(now);
        }
    }

    updateTime();
    setInterval(updateTime, 1000);

    // --- Notifications ---
    const notificationIcon = document.getElementById('notification-icon');
    const notificationCount = document.getElementById('notification-count');
    const notificationDropdown = document.getElementById('notification-dropdown');
    const notificationList = document.getElementById('notification-list');

    function fetchNotifications() {
        fetch('/dabestan/includes/fetch_notifications.php')
            .then(response => response.json())
            .then(data => {
                if (data.error) return;

                if (data.unread_count > 0) {
                    notificationCount.textContent = data.unread_count;
                    notificationCount.style.display = 'block';
                } else {
                    notificationCount.style.display = 'none';
                }

                notificationList.innerHTML = '';
                if (data.notifications.length > 0) {
                    data.notifications.forEach(notif => {
                        const item = document.createElement('div');
                        item.className = 'notification-item';
                        item.innerHTML = `
                            <span>${notif.message}</span>
                            <small>${notif.created_at}</small>
                        `;
                        notificationList.appendChild(item);
                    });
                } else {
                    notificationList.innerHTML = '<div class="notification-item">هیچ اعلان جدیدی وجود ندارد.</div>';
                }
            })
            .catch(error => console.error('Error fetching notifications:', error));
    }

    if (notificationIcon) {
        fetchNotifications();
        setInterval(fetchNotifications, 30000);

        notificationIcon.addEventListener('click', function() {
            notificationDropdown.classList.toggle('show');
            if (notificationDropdown.classList.contains('show') && notificationCount.style.display === 'block') {
                fetch('/dabestan/includes/mark_notifications_read.php', { method: 'POST' })
                    .then(() => {
                        notificationCount.style.display = 'none';
                    });
            }
        });

        document.addEventListener('click', function(event) {
            if (!notificationIcon.contains(event.target) && !notificationDropdown.contains(event.target)) {
                notificationDropdown.classList.remove('show');
            }
        });
    }
});
