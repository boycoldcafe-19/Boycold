(function () {
    // Exported reports are created in the browser, so capture the user action
    // here and persist it through the authenticated admin API.
    const exportButtons = [
        ['exportDashboardBtn', 'dashboard'],
        ['exportAnalyticsBtn', 'analytics'],
        ['exportForecastBtn', 'forecast'],
        ['exportInventoryBtn', 'inventory']
    ];

    exportButtons.forEach(([buttonId, report]) => {
        const button = document.getElementById(buttonId);
        if (!button) return;

        button.addEventListener('click', () => {
            fetch('admin_data_api.php?action=activity_export', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ report }),
                keepalive: true,
                credentials: 'same-origin'
            }).catch(() => {
                // Activity logging is supplementary; never interrupt an export.
            });
        });
    });

    const sidebar = document.getElementById('sidebar');
    const header = document.querySelector('.top-header');
    if (!sidebar || !header) return;

    const menuButton = document.createElement('button');
    menuButton.type = 'button';
    menuButton.className = 'admin-mobile-menu';
    menuButton.setAttribute('aria-label', 'Open navigation menu');
    menuButton.setAttribute('aria-expanded', 'false');
    menuButton.innerHTML = '<i class="fa-solid fa-bars" aria-hidden="true"></i>';
    header.insertBefore(menuButton, header.firstChild);

    const backdrop = document.createElement('div');
    backdrop.className = 'admin-sidebar-backdrop';
    document.body.appendChild(backdrop);

    function setOpen(open) {
        sidebar.classList.toggle('admin-sidebar-open', open);
        backdrop.classList.toggle('open', open);
        menuButton.setAttribute('aria-expanded', String(open));
        menuButton.setAttribute('aria-label', open ? 'Close navigation menu' : 'Open navigation menu');
        menuButton.innerHTML = `<i class="fa-solid fa-${open ? 'xmark' : 'bars'}" aria-hidden="true"></i>`;
        document.body.style.overflow = open ? 'hidden' : '';
    }

    menuButton.addEventListener('click', function () {
        setOpen(!sidebar.classList.contains('admin-sidebar-open'));
    });
    backdrop.addEventListener('click', function () {
        setOpen(false);
    });
    sidebar.querySelectorAll('a').forEach(function (link) {
        link.addEventListener('click', function () {
            setOpen(false);
        });
    });
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') setOpen(false);
    });
    window.addEventListener('resize', function () {
        if (window.innerWidth > 900) setOpen(false);
    });
})();
