(function () {
    if (window.__adminInventoryWarningReady) return;
    window.__adminInventoryWarningReady = true;

    const API_URL = 'inventory_api.php?action=get_inventory_status';
    const REFRESH_MS = 30000;

    function getWarnings(inventory, restockWarnings) {
        const stockWarnings = (inventory || []).filter((item) => {
            const stock = Number(item.stock || 0);
            const minimum = Number(item.min_stock || 0);
            // Mapped ingredients are evaluated from their live recipe serving
            // capacity below. An arbitrary max_stock percentage must not turn
            // an otherwise sufficient ingredient into a false warning.
            return !item.is_mapped && stock <= minimum;
        });
        const capacityWarnings = (restockWarnings || []).map((item) => ({
            ...item,
            stock: Number(item.stock || 0),
            min_stock: 0,
            max_stock: 0,
            is_capacity_warning: true,
        }));
        const warnings = [...stockWarnings, ...capacityWarnings];
        const uniqueWarnings = new Map();
        warnings.forEach((item) => {
            const key = `${String(item.name || '').trim().toLowerCase()}|${item.branch_id || 0}`;
            if (key !== '|') uniqueWarnings.set(key, item);
        });
        return [...uniqueWarnings.values()].sort((a, b) => Number(a.stock || 0) - Number(b.stock || 0));
    }

    function renderWarnings(inventory, restockWarnings) {
        const button = document.getElementById('notifBtn');
        if (!button) return;

        const warnings = getWarnings(inventory, restockWarnings);
        let badge = button.querySelector('.icon-badge');
        if (!badge) {
            badge = document.createElement('span');
            badge.className = 'icon-badge';
            button.appendChild(badge);
        }
        badge.textContent = String(warnings.length);
        badge.style.display = warnings.length ? 'flex' : 'none';
        button.classList.toggle('inventory-warning-active', warnings.length > 0);

        const container = button.closest('.notif-wrap');
        let dropdown = document.getElementById('notifDropdown');
        if (!dropdown) {
            dropdown = document.createElement('div');
            dropdown.id = 'notifDropdown';
            dropdown.className = 'notif-dropdown';
            container.appendChild(dropdown);
        }
        dropdown.innerHTML = '<div class="notif-header"><span class="notif-title">Inventory Warnings</span></div><div class="notif-list inventory-warning-list"></div><a href="inventory.php" class="notif-footer">Open inventory details <i class="fa-solid fa-chevron-right"></i></a>';

        const list = dropdown.querySelector('.inventory-warning-list');
        if (!warnings.length) {
            list.innerHTML = '<div class="notif-empty">All ingredients have sufficient stock.</div>';
            return;
        }

        warnings.forEach((item) => {
            const stock = Number(item.stock || 0);
            const minimum = Number(item.min_stock || 0);
            const label = item.status === 'critical'
                ? `Critical stock (${Number(item.remaining_servings || 0)} servings left)`
                : item.status === 'soon'
                    ? `Low stock (${Number(item.remaining_servings || 0)} servings left)`
                    : stock <= 0
                ? 'Out of stock'
                : stock <= minimum
                    ? 'Below minimum stock'
                    : 'Critical stock';
            const row = document.createElement('div');
            row.className = 'notif-item unread inventory-warning-item';
            row.innerHTML = '<div class="notif-icon notif-icon-warning"><i class="fa-solid fa-triangle-exclamation"></i></div><div class="notif-content"><p class="notif-item-title"></p><p class="notif-item-sub"></p></div>';
            row.querySelector('.notif-item-title').textContent = `${item.name || 'Ingredient'} (${item.branch_name || 'Unassigned'})`;
            row.querySelector('.notif-item-sub').textContent = `${label}: ${stock.toLocaleString()} ${item.unit || ''}`;
            list.appendChild(row);
        });
    }

    async function refreshWarnings() {
        try {
            const response = await fetch(API_URL, { cache: 'no-store' });
            const data = await response.json();
            if (data.success) renderWarnings(data.inventory, data.restock_warnings);
        } catch (error) {
            console.error('Admin inventory warning refresh failed:', error);
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        const button = document.getElementById('notifBtn');
        if (!button) return;
        button.setAttribute('aria-expanded', 'false');
        button.addEventListener('click', (event) => {
            event.stopPropagation();
            const dropdown = document.getElementById('notifDropdown');
            if (!dropdown) return;
            const isOpen = dropdown.classList.toggle('open');
            button.setAttribute('aria-expanded', String(isOpen));
        });
        document.addEventListener('click', (event) => {
            const dropdown = document.getElementById('notifDropdown');
            if (dropdown && !dropdown.contains(event.target) && !button.contains(event.target)) {
                dropdown.classList.remove('open');
                button.setAttribute('aria-expanded', 'false');
            }
        });
        refreshWarnings();
        setInterval(refreshWarnings, REFRESH_MS);
    });
})();
