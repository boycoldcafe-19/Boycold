(function () {
    if (window.__boycoldInventoryWarningReady) return;
    window.__boycoldInventoryWarningReady = true;

    const API_URL = '../api/pos_inventory_api.php?action=get_inventory';
    const REFRESH_MS = 30000;

    function findAlertButton() {
        const button = document.getElementById('notifBtn');
        if (!button) return null;
        button.dataset.inventoryAlert = 'true';
        button.setAttribute('aria-label', 'Inventory warnings');
        const icon = button.querySelector('i');
        if (icon) icon.className = 'fa-solid fa-triangle-exclamation';
        return button;
    }

    function getInventoryItems(inventory) {
        return Object.values(inventory || {}).filter((item) => {
            const current = Number(item.current || 0);
            const minimum = Number(item.min || 0);
            return current <= minimum || (Number(item.max || 0) > 0 && current / Number(item.max) <= 0.2);
        }).sort((a, b) => Number(a.current || 0) - Number(b.current || 0));
    }

    function ensureDropdown(button) {
        let dropdown = document.getElementById('notifDropdown');
        if (!dropdown) {
            dropdown = document.createElement('div');
            dropdown.id = 'notifDropdown';
            dropdown.className = 'notif-dropdown';
            const container = button.closest('.notif-wrap') || button.parentElement;
            container?.appendChild(dropdown);
        }
        return dropdown;
    }

    function renderWarnings(inventory) {
        const button = findAlertButton();
        if (!button) return;

        const warnings = getInventoryItems(inventory);
        let badge = button.querySelector('.icon-badge');
        if (!badge) {
            badge = document.createElement('span');
            badge.className = 'icon-badge';
            button.appendChild(badge);
        }
        badge.textContent = String(warnings.length);
        badge.style.display = warnings.length ? 'flex' : 'none';
        button.classList.toggle('inventory-warning-active', warnings.length > 0);

        const dropdown = ensureDropdown(button);
        dropdown.innerHTML = `
            <div class="notif-header"><span class="notif-title">Inventory Warnings</span></div>
            <div class="notif-list inventory-warning-list"></div>
            <a href="pos-menu.php" class="notif-footer">Open inventory details <i class="fa-solid fa-chevron-right"></i></a>
        `;
        const list = dropdown.querySelector('.inventory-warning-list');
        if (!warnings.length) {
            list.innerHTML = '<div class="notif-empty">All ingredients have sufficient stock.</div>';
            return;
        }
        warnings.forEach((item) => {
            const current = Number(item.current || 0);
            const minimum = Number(item.min || 0);
            const label = current <= 0 ? 'Out of stock' : current <= minimum ? 'Below minimum stock' : 'Critical stock';
            const row = document.createElement('div');
            row.className = 'notif-item unread inventory-warning-item';
            row.innerHTML = `<div class="notif-icon notif-icon-warning"><i class="fa-solid fa-triangle-exclamation"></i></div><div class="notif-content"><p class="notif-item-title"></p><p class="notif-item-sub"></p></div>`;
            row.querySelector('.notif-item-title').textContent = item.name || 'Ingredient';
            row.querySelector('.notif-item-sub').textContent = `${label}: ${current.toLocaleString()} ${item.unit || ''}`;
            list.appendChild(row);
        });
    }

    async function refreshWarnings() {
        try {
            const response = await fetch(API_URL, { cache: 'no-store' });
            const data = await response.json();
            if (data.success) renderWarnings(data.inventory);
        } catch (error) {
            console.error('Inventory warning refresh failed:', error);
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        const button = findAlertButton();
        if (!button) return;
        button.addEventListener('click', (event) => {
            event.stopPropagation();
            document.getElementById('notifDropdown')?.classList.toggle('open');
        });
        document.addEventListener('click', (event) => {
            const dropdown = document.getElementById('notifDropdown');
            if (dropdown && !dropdown.contains(event.target) && !button.contains(event.target)) dropdown.classList.remove('open');
        });
        refreshWarnings();
        setInterval(refreshWarnings, REFRESH_MS);
    });
})();
