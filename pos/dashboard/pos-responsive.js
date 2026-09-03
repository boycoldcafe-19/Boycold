(function () {
    const sidebar = document.getElementById('sidebar');
    const header = document.querySelector('.top-header');
    if (!sidebar || !header) return;

    const menuButton = document.createElement('button');
    menuButton.type = 'button';
    menuButton.className = 'pos-mobile-menu';
    menuButton.setAttribute('aria-label', 'Open navigation menu');
    menuButton.setAttribute('aria-expanded', 'false');
    menuButton.innerHTML = '<i class="fa-solid fa-bars" aria-hidden="true"></i>';
    header.insertBefore(menuButton, header.firstChild);

    const backdrop = document.createElement('div');
    backdrop.className = 'pos-sidebar-backdrop';
    backdrop.addEventListener('click', closeSidebar);
    document.body.appendChild(backdrop);

    function setSidebar(open) {
        sidebar.classList.toggle('pos-sidebar-open', open);
        backdrop.classList.toggle('open', open);
        menuButton.setAttribute('aria-expanded', String(open));
        menuButton.setAttribute('aria-label', open ? 'Close navigation menu' : 'Open navigation menu');
        menuButton.innerHTML = `<i class="fa-solid fa-${open ? 'xmark' : 'bars'}" aria-hidden="true"></i>`;
        document.body.style.overflow = open ? 'hidden' : '';
    }

    function closeSidebar() {
        setSidebar(false);
    }

    menuButton.addEventListener('click', () => {
        setSidebar(!sidebar.classList.contains('pos-sidebar-open'));
    });

    sidebar.querySelectorAll('a').forEach((link) => link.addEventListener('click', closeSidebar));
    window.addEventListener('resize', () => {
        if (window.innerWidth > 900) closeSidebar();
    });
})();
