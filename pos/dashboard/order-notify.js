(function () {
    if (window.__boycoldOrderNotifyReady) return;
    window.__boycoldOrderNotifyReady = true;

    const ORDER_API = '../online-orders-api.php';
    const ORDER_POPUP = '../order-popup.php';
    const POLL_INTERVAL = 5000;

    function isPopupSoundMuted() {
        return localStorage.getItem('boycold_pos_muted') === 'true';
    }

    console.log('[Order Notify] Initialized, polling every', POLL_INTERVAL, 'ms');

    let latestOrderId = 0;
    let isInitialPoll = true;
    let popupSound = null;
    let popupSoundGain = null;
    let popupSoundInterval = null;

    function ensurePopupHost() {
        let popupHost = document.getElementById('popupHost');
        if (!popupHost) {
            popupHost = document.createElement('div');
            popupHost.id = 'popupHost';
            popupHost.style.display = 'none';
            document.body.appendChild(popupHost);
        }
        return popupHost;
    }

    function createPopupSound() {
        if (!popupSound) {
            const AudioCtx = window.AudioContext || window.webkitAudioContext;
            if (!AudioCtx) return null;
            popupSound = new AudioCtx();
            popupSoundGain = popupSound.createGain();
            popupSoundGain.gain.value = 1;
            popupSoundGain.connect(popupSound.destination);
        }
        return popupSound;
    }

    // Stop any notes that are already queued as soon as the POS is muted.
    // Clearing the interval alone leaves the current two-note chime audible.
    function setPopupSoundOutput(isMuted) {
        if (!popupSound || !popupSoundGain) return;

        const now = popupSound.currentTime;
        popupSoundGain.gain.cancelScheduledValues(now);
        popupSoundGain.gain.setValueAtTime(isMuted ? 0.0001 : 1, now);
    }

    function playPopupSoundTick() {
        if (isPopupSoundMuted()) {
            stopPopupSound();
            return;
        }

        const ctx = createPopupSound();
        if (!ctx) return;
        if (ctx.state === 'suspended') {
            ctx.resume().catch(() => {});
        }

        const now = ctx.currentTime;
        [0, 0.22].forEach((offset, index) => {
            const oscillator = ctx.createOscillator();
            const gain = ctx.createGain();
            oscillator.type = 'triangle';
            oscillator.frequency.value = index === 0 ? 880 : 1175;
            gain.gain.setValueAtTime(0.0001, now + offset);
            gain.gain.exponentialRampToValueAtTime(0.1, now + offset + 0.02);
            gain.gain.exponentialRampToValueAtTime(0.0001, now + offset + 0.18);
            oscillator.connect(gain);
            gain.connect(popupSoundGain);
            oscillator.start(now + offset);
            oscillator.stop(now + offset + 0.2);
        });
    }

    function startPopupSound() {
        if (isPopupSoundMuted()) {
            stopPopupSound();
            return;
        }
        if (popupSoundInterval) return;
        setPopupSoundOutput(false);
        playPopupSoundTick();
        popupSoundInterval = setInterval(playPopupSoundTick, 1100);
    }

    function stopPopupSound() {
        setPopupSoundOutput(true);
        if (!popupSoundInterval) return;
        clearInterval(popupSoundInterval);
        popupSoundInterval = null;
    }

    window.addEventListener('boycold:mute-toggle', (event) => {
        const isMuted = typeof event.detail?.muted === 'boolean'
            ? event.detail.muted
            : isPopupSoundMuted();
        if (isMuted) {
            stopPopupSound();
            return;
        }

        const popupHost = document.getElementById('popupHost');
        if (popupHost && popupHost.style.display !== 'none' && popupHost.innerHTML.trim()) {
            startPopupSound();
        }
    });

    // The custom event above covers the current POS page. This keeps an
    // already-open popup quiet when the sound button is changed in another
    // POS tab/window as well.
    window.addEventListener('storage', (event) => {
        if (event.key !== 'boycold_pos_muted') return;

        if (event.newValue === 'true') {
            stopPopupSound();
            return;
        }

        const popupHost = document.getElementById('popupHost');
        if (popupHost && popupHost.style.display !== 'none' && popupHost.innerHTML.trim()) {
            startPopupSound();
        }
    });

    window.orderPopupSoundControl = {
        start: startPopupSound,
        stop: stopPopupSound
    };

    document.addEventListener('click', () => {
        if (popupSoundInterval && popupSound?.state === 'suspended') {
            popupSound.resume().catch(() => {});
        }
    });

    function findNotificationButton() {
        const existingButton = document.getElementById('notifBtn');
        if (existingButton?.dataset.inventoryAlert === 'true') return null;
        return existingButton ||
            document.querySelector('.top-header .icon-btn, header.top-header .icon-btn');
    }

    function buildNotificationItem(title, subtitle, iconClass) {
        const item = document.createElement('div');
        item.className = 'notif-item unread';
        item.innerHTML = `
            <div class="notif-icon ${iconClass}"><i class="fa-solid fa-bag-shopping"></i></div>
            <div class="notif-content">
                <p class="notif-item-title"></p>
                <p class="notif-item-sub"></p>
            </div>
            <div class="notif-time">
                <span class="notif-time-main">Now</span>
                <span class="notif-time-sub">Just now</span>
            </div>
        `;
        item.querySelector('.notif-item-title').textContent = title;
        item.querySelector('.notif-item-sub').textContent = subtitle;
        return item;
    }

    function buildDropdownMarkup() {
        return `
            <div class="notif-header">
                <span class="notif-title">Notifications</span>
                <a href="#" class="notif-mark-read" id="markAllRead">Mark all as read</a>
            </div>
            <div class="notif-list" id="notifList"><div class="notif-empty">Loading notifications...</div></div>
            <a href="pos-online.php" class="notif-footer">
                View all notifications <i class="fa-solid fa-chevron-right"></i>
            </a>
        `;
    }

    function ensureNotificationUi() {
        const existingBtn = document.getElementById('notifBtn');
        const existingDropdown = document.getElementById('notifDropdown');
        const shouldBindDropdown = !(existingBtn && existingDropdown) || existingBtn.dataset.orderNotifyBound !== 'true';

        const notifBtn = findNotificationButton();
        if (!notifBtn) return null;

        if (!notifBtn.id) notifBtn.id = 'notifBtn';
        notifBtn.setAttribute('aria-label', 'Notifications');
        notifBtn.setAttribute('type', 'button');

        let notifBadge = notifBtn.querySelector('.icon-badge');
        if (!notifBadge) {
            notifBadge = document.createElement('span');
            notifBadge.className = 'icon-badge';
            notifBadge.textContent = '0';
            notifBadge.style.display = 'none';
            notifBtn.appendChild(notifBadge);
        }
        if (!notifBadge.id) notifBadge.id = 'notifBadge';

        let notifWrap = notifBtn.closest('.notif-wrap');
        if (!notifWrap) {
            notifWrap = document.createElement('div');
            notifWrap.className = 'notif-wrap';
            notifBtn.parentNode.insertBefore(notifWrap, notifBtn);
            notifWrap.appendChild(notifBtn);
        }

        let notifDropdown = document.getElementById('notifDropdown');
        if (!notifDropdown) {
            notifDropdown = document.createElement('div');
            notifDropdown.className = 'notif-dropdown';
            notifDropdown.id = 'notifDropdown';
            notifDropdown.innerHTML = buildDropdownMarkup();
            notifWrap.appendChild(notifDropdown);
        }

        return {
            btn: notifBtn,
            dropdown: notifDropdown,
            badge: document.getElementById('notifBadge'),
            list: document.getElementById('notifList'),
            markAllRead: document.getElementById('markAllRead'),
            shouldBindDropdown
        };
    }

    function bindGeneratedDropdown(ui) {
        if (!ui || !ui.shouldBindDropdown || ui.btn.dataset.orderNotifyBound === 'true') return;

        ui.btn.addEventListener('click', (event) => {
            event.stopPropagation();
            ui.dropdown.classList.toggle('open');
        });

        document.addEventListener('click', (event) => {
            if (!ui.dropdown.contains(event.target) && !ui.btn.contains(event.target)) {
                ui.dropdown.classList.remove('open');
            }
        });

        ui.markAllRead?.addEventListener('click', (event) => {
            event.preventDefault();
            ui.list?.querySelectorAll('.notif-item.unread').forEach(item => {
                item.classList.remove('unread');
            });
            if (ui.badge) ui.badge.style.display = 'none';
        });

        ui.btn.dataset.orderNotifyBound = 'true';
    }

    function addNotification(order) {
        const ui = ensureNotificationUi();
        if (!ui?.badge || !ui?.list) return;

        ui.list.querySelector('.notif-empty')?.remove();
        const item = buildNotificationItem(
            'New online order received',
            `Order #${order.id}`,
            'notif-icon-bag'
        );
        ui.list.insertBefore(item, ui.list.firstChild);

        ui.badge.style.display = 'flex';
        ui.badge.textContent = (parseInt(ui.badge.textContent || '0', 10) + 1).toString();
    }

    function renderNotifications(notifications) {
        const ui = ensureNotificationUi();
        if (!ui?.list) return;

        ui.list.innerHTML = '';
        if (!notifications.length) {
            ui.list.innerHTML = '<div class="notif-empty">No new notifications</div>';
            return;
        }

        notifications.forEach((order) => {
            const item = buildNotificationItem(
                order.status === 'pending' ? 'New online order received' : 'Online order updated',
                `Order #${order.id}`,
                'notif-icon-bag'
            );
            const date = new Date(String(order.created_at || '').replace(' ', 'T'));
            if (!Number.isNaN(date.getTime())) {
                item.querySelector('.notif-time-main').textContent = date.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' }).toLowerCase();
                item.querySelector('.notif-time-sub').textContent = date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
            }
            ui.list.appendChild(item);
        });
    }

    async function refreshNotificationList() {
        try {
            const res = await fetch(`${ORDER_API}?action=notifications`, { cache: 'no-store' });
            const data = await res.json();
            if (data.success && Array.isArray(data.notifications)) {
                renderNotifications(data.notifications);
            }
        } catch (err) {
            console.error('Notification list refresh failed', err);
        }
    }

    function showPopup(orderId) {
        if (!orderId) return;
        const popupHost = ensurePopupHost();
        const orderIdText = String(orderId);
        const alreadyShowing = Array.from(popupHost.querySelectorAll('.order-popup-frame'))
            .some(frame => frame.dataset.orderId === orderIdText);
        if (alreadyShowing) return;

        const iframe = document.createElement('iframe');
        iframe.src = `${ORDER_POPUP}?order_id=${encodeURIComponent(orderIdText)}`;
        iframe.dataset.orderId = orderIdText;
        iframe.className = 'order-popup-frame';
        popupHost.innerHTML = '';
        popupHost.appendChild(iframe);
        popupHost.style.display = 'block';
        startPopupSound();
    }

    function closePopup() {
        const popupHost = ensurePopupHost();
        stopPopupSound();
        popupHost.innerHTML = '';
        popupHost.style.display = 'none';
    }

    // New-order polling intentionally returns only pending/confirmed rows, so
    // it cannot tell us when an already-open popup was cancelled by the
    // customer. Check that one order separately and close the popup at once.
    async function closePopupIfCustomerCancelled() {
        const popupFrame = document.querySelector('#popupHost .order-popup-frame[data-order-id]');
        const orderId = Number(popupFrame?.dataset.orderId);
        if (!Number.isInteger(orderId) || orderId <= 0) return false;

        try {
            const res = await fetch(
                `${ORDER_API}?action=payment_status&order_id=${encodeURIComponent(orderId)}`,
                { cache: 'no-store' }
            );
            const data = await res.json();
            if (!data.success || String(data.order_status).toLowerCase() !== 'cancelled') {
                return false;
            }

            closePopup();
            refreshOrderCount();
            refreshNotificationList();
            return true;
        } catch (err) {
            console.error('Failed to check the open order popup status', err);
            return false;
        }
    }

    async function refreshOnlineOrdersTable() {
        // Only pos-online.php has this table. On any other page (menu,
        // history, shift, etc.) there's nothing to refresh, so bail out.
        const ordersBody = document.getElementById('ordersBody');
        if (!ordersBody) return;

        try {
            const res = await fetch(window.location.href, { cache: 'no-store' });
            const html = await res.text();
            const freshDoc = new DOMParser().parseFromString(html, 'text/html');
            const freshBody = freshDoc.getElementById('ordersBody');
            if (!freshBody) return;

            ordersBody.innerHTML = freshBody.innerHTML;

            // Re-apply whichever tab filter (All / Confirmed / Preparing / etc.)
            // was active before the refresh, so the new row respects it.
            const activeTab = document.querySelector('.order-tabs a.active');
            const filter = activeTab?.dataset.filter || 'all';
            if (typeof window.applyStatusFilter === 'function') {
                window.applyStatusFilter(filter);
            }
        } catch (err) {
            console.error('Failed to refresh online orders table', err);
        }
    }

    function updateOrderCountBadges(count) {
        const normalizedCount = Math.max(0, Number(count) || 0);
        document.querySelectorAll('.nav-badge, .icon-badge').forEach((badge) => {
            if (badge.closest('[data-inventory-alert="true"]')) return;
            badge.textContent = String(normalizedCount);
            badge.style.display = normalizedCount > 0 ? 'flex' : 'none';
        });
    }

    async function refreshOrderCount() {
        try {
            const res = await fetch(`${ORDER_API}?action=counts`, { cache: 'no-store' });
            const data = await res.json();
            if (data.success) updateOrderCountBadges(data.pending_count);
        } catch (err) {
            console.error('Online order count refresh failed', err);
        }
    }

    window.addEventListener('message', (event) => {
        const type = event.data?.type;
        if (['orderAccepted', 'orderCancelled', 'closeOrderPopup', 'orderUpdated'].includes(type)) {
            closePopup();
        }
        if (['orderAccepted', 'orderCancelled', 'orderUpdated'].includes(type)) {
            refreshOnlineOrdersTable();
            refreshOrderCount();
        }
    });

    async function pollOnlineOrders() {
        try {
            const res = await fetch(`${ORDER_API}?last_order_id=${encodeURIComponent(latestOrderId)}`);
            const data = await res.json();
            console.log('[Order Notify] Poll response:', data);
            if (!data.success || !Array.isArray(data.orders)) return;

            if (isInitialPoll) {
                latestOrderId = data.latest_order_id || latestOrderId;
                isInitialPoll = false;
                console.log('[Order Notify] Initial poll, latest_order_id:', latestOrderId);
                return;
            }

            if (data.orders.length > 0) {
                latestOrderId = data.latest_order_id || latestOrderId;
                console.log('[Order Notify] Found new orders:', data.orders.length);
                data.orders.forEach((order) => {
                    console.log('[Order Notify] Showing popup for order:', order.id);
                    showPopup(order.id);
                    addNotification(order);
                });
            }

            await closePopupIfCustomerCancelled();

            // Keep the open POS list in sync when a customer cancels QRPh
            // from the user checkout page, including a popup that was open
            // when the customer cancelled the order.
            await refreshOnlineOrdersTable();
        } catch (err) {
            console.error('Online order poll failed', err);
        }
    }

    bindGeneratedDropdown(ensureNotificationUi());
    setInterval(pollOnlineOrders, POLL_INTERVAL);
    setInterval(refreshOrderCount, POLL_INTERVAL);
    setInterval(refreshNotificationList, POLL_INTERVAL);
    pollOnlineOrders();
    refreshOrderCount();
    refreshNotificationList();
})();
