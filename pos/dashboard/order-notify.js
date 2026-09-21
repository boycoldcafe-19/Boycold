(function () {
    if (window.__boycoldOrderNotifyReady) return;
    window.__boycoldOrderNotifyReady = true;

    const ORDER_API = '../online-orders-api.php';
    const ORDER_POPUP = '../order-popup.php';
    const POLL_INTERVAL = 5000;
    const POPUP_STATE_PREFIX = 'boycold_pos_order_popup_state_';

    function isPopupSoundMuted() {
        return localStorage.getItem('boycold_pos_muted') === 'true';
    }

    console.log('[Order Notify] Initialized, polling every', POLL_INTERVAL, 'ms');

    let latestOrderId = 0;
    let activeBranchId = 0;
    let activePopupSessionKey = '';
    let popupQueue = [];
    let activePopupOrderId = 0;
    let pollInFlight = false;
    let popupSound = null;
    let popupSoundGain = null;
    let popupSoundInterval = null;

    function popupStateKey() {
        return activeBranchId > 0
            ? `${POPUP_STATE_PREFIX}${activeBranchId}_${activePopupSessionKey || 'browser'}`
            : '';
    }

    function readPopupState() {
        const key = popupStateKey();
        if (!key) return { lastOrderId: 0, queue: [] };

        try {
            const saved = JSON.parse(sessionStorage.getItem(key) || '{}');
            const lastOrderId = Math.max(0, Number(saved.lastOrderId) || 0);
            const queue = Array.isArray(saved.queue)
                ? [...new Set(saved.queue.map(Number).filter((id) => Number.isInteger(id) && id > 0))]
                : [];
            return { lastOrderId, queue };
        } catch (error) {
            return { lastOrderId: 0, queue: [] };
        }
    }

    function savePopupState() {
        const key = popupStateKey();
        if (!key) return;

        try {
            sessionStorage.setItem(key, JSON.stringify({
                lastOrderId: latestOrderId,
                queue: popupQueue
            }));
        } catch (error) {
            // The POS can still show notifications if browser storage is unavailable.
        }
    }

    function useBranchPopupState(branchId, popupSessionKey) {
        const normalizedBranchId = Number(branchId);
        const normalizedSessionKey = String(popupSessionKey || 'browser');
        if (!Number.isInteger(normalizedBranchId) || normalizedBranchId <= 0
            || (activeBranchId === normalizedBranchId && activePopupSessionKey === normalizedSessionKey)) {
            return;
        }

        activeBranchId = normalizedBranchId;
        activePopupSessionKey = normalizedSessionKey;
        const savedState = readPopupState();
        latestOrderId = savedState.lastOrderId;
        popupQueue = savedState.queue;
        activePopupOrderId = 0;
    }

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
        if (popupSoundInterval) {
            clearInterval(popupSoundInterval);
            popupSoundInterval = null;
        }

        // A stopped interval does not stop oscillators already scheduled in
        // the Web Audio graph. Closing this context is the hard mute: no
        // pending popup note can continue after the Sound button is OFF.
        if (popupSound) {
            const audioContext = popupSound;
            popupSound = null;
            popupSoundGain = null;
            if (audioContext.state !== 'closed') {
                audioContext.close().catch(() => {});
            }
        }
    }

    function updateSoundToggleUI(isMuted) {
        const soundToggleBtn = document.getElementById('soundToggleBtn');
        const soundIcon = document.getElementById('soundIcon');
        if (!soundToggleBtn || !soundIcon) return;

        soundIcon.className = isMuted
            ? 'fa-solid fa-volume-xmark'
            : 'fa-solid fa-volume-high';
        soundToggleBtn.classList.toggle('muted', isMuted);
        soundToggleBtn.title = isMuted
            ? 'Sound Muted (Click to Unmute)'
            : 'Sound On (Click to Mute)';
        soundToggleBtn.setAttribute('aria-pressed', String(!isMuted));
    }

    function syncPopupSoundWithToggle(isMuted) {
        updateSoundToggleUI(isMuted);
        if (isMuted) {
            stopPopupSound();
            return;
        }

        const popupHost = document.getElementById('popupHost');
        if (popupHost && popupHost.style.display !== 'none' && popupHost.innerHTML.trim()) {
            startPopupSound();
        }
    }

    window.addEventListener('boycold:mute-toggle', (event) => {
        const isMuted = typeof event.detail?.muted === 'boolean'
            ? event.detail.muted
            : isPopupSoundMuted();
        syncPopupSoundWithToggle(isMuted);
    });

    // The custom event above covers the current POS page. This keeps an
    // already-open popup quiet when the sound button is changed in another
    // POS tab/window as well.
    window.addEventListener('storage', (event) => {
        if (event.key !== 'boycold_pos_muted') return;
        syncPopupSoundWithToggle(event.newValue === 'true');
    });

    // POS pages previously owned separate click handlers for this button.
    // Capture the click here so the saved state, button UI, and popup chime
    // always use one shared setting on every POS page.
    document.addEventListener('click', (event) => {
        const clickedElement = event.target instanceof Element ? event.target : null;
        const soundToggleBtn = clickedElement?.closest('#soundToggleBtn');
        if (!soundToggleBtn) return;

        event.preventDefault();
        event.stopImmediatePropagation();

        const isMuted = !isPopupSoundMuted();
        localStorage.setItem('boycold_pos_muted', String(isMuted));
        window.dispatchEvent(new CustomEvent('boycold:mute-toggle', {
            detail: { muted: isMuted }
        }));
    }, true);

    // Keep the initial icon in sync too, even on pages with their own older
    // sound-button markup.
    updateSoundToggleUI(isPopupSoundMuted());

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

    function renderNextPopup() {
        if (activePopupOrderId || !popupQueue.length) return;

        const orderId = popupQueue[0];
        const popupHost = ensurePopupHost();
        const iframe = document.createElement('iframe');
        iframe.src = `${ORDER_POPUP}?order_id=${encodeURIComponent(orderId)}`;
        iframe.dataset.orderId = String(orderId);
        iframe.className = 'order-popup-frame';
        iframe.title = `New online order #${orderId}`;
        iframe.addEventListener('error', () => {
            console.error('[Order Notify] Failed to load popup for order:', orderId);
            closePopup(orderId);
        });

        popupHost.replaceChildren(iframe);
        popupHost.style.display = 'block';
        activePopupOrderId = orderId;
        startPopupSound();
    }

    // Orders can arrive in one poll response. Keep every one in a queue so
    // replacing the iframe for a newer order can never discard an older one.
    function showPopup(orderId) {
        const normalizedOrderId = Number(orderId);
        if (!Number.isInteger(normalizedOrderId) || normalizedOrderId <= 0) return;

        if (!popupQueue.includes(normalizedOrderId)) {
            popupQueue.push(normalizedOrderId);
            savePopupState();
        }
        renderNextPopup();
    }

    function closePopup(orderId = activePopupOrderId, showNextPopup = true) {
        const normalizedOrderId = Number(orderId);
        if (Number.isInteger(normalizedOrderId) && normalizedOrderId > 0) {
            popupQueue = popupQueue.filter((queuedOrderId) => queuedOrderId !== normalizedOrderId);
        }

        const popupHost = ensurePopupHost();
        if (!activePopupOrderId || activePopupOrderId === normalizedOrderId) {
            activePopupOrderId = 0;
            stopPopupSound();
            popupHost.replaceChildren();
            popupHost.style.display = 'none';
        }

        savePopupState();
        if (showNextPopup) renderNextPopup();
    }

    // A customer can cancel QRPh while their order is in the visible popup or
    // waiting behind another popup. Remove cancelled orders from both places.
    async function removeCancelledPopupOrders() {
        const queuedOrderIds = [...popupQueue];
        if (!queuedOrderIds.length) return false;

        try {
            const checks = await Promise.all(queuedOrderIds.map(async (orderId) => {
                const res = await fetch(
                    `${ORDER_API}?action=payment_status&order_id=${encodeURIComponent(orderId)}`,
                    { cache: 'no-store', credentials: 'same-origin' }
                );
                if (!res.ok) return { orderId, cancelled: false };
                const data = await res.json();
                return {
                    orderId,
                    cancelled: data.success && String(data.order_status).toLowerCase() === 'cancelled'
                };
            }));
            const cancelledOrderIds = new Set(
                checks.filter((result) => result.cancelled).map((result) => result.orderId)
            );
            if (!cancelledOrderIds.size) return false;

            const activeWasCancelled = cancelledOrderIds.has(activePopupOrderId);
            popupQueue = popupQueue.filter((orderId) => !cancelledOrderIds.has(orderId));
            if (activeWasCancelled) {
                activePopupOrderId = 0;
                const popupHost = ensurePopupHost();
                stopPopupSound();
                popupHost.replaceChildren();
                popupHost.style.display = 'none';
            }
            savePopupState();
            renderNextPopup();
            refreshOrderCount();
            refreshNotificationList();
            return true;
        } catch (err) {
            console.error('Failed to check queued popup order statuses', err);
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
        if (!['orderAccepted', 'orderCancelled', 'closeOrderPopup', 'orderUpdated', 'trackOrder'].includes(type)) {
            return;
        }

        const activeFrame = document.querySelector('#popupHost .order-popup-frame');
        // Only the visible popup iframe is allowed to close this popup. This
        // prevents unrelated windows/pages from dismissing a queued order.
        if (activeFrame?.contentWindow && event.source !== activeFrame.contentWindow) return;

        const messageOrderId = Number(event.data?.orderId);
        if (messageOrderId > 0 && activePopupOrderId > 0 && messageOrderId !== activePopupOrderId) {
            if (['orderAccepted', 'orderCancelled', 'orderUpdated'].includes(type)) {
                refreshOnlineOrdersTable();
                refreshOrderCount();
            }
            return;
        }

        if (type === 'trackOrder') {
            try {
                const trackUrl = new URL(String(event.data?.trackUrl || ''), window.location.href);
                const targetOrderId = Number(trackUrl.searchParams.get('order_id'));
                const isPosStatusPage = /\/pos\/dashboard\/pos-status\.php$/i.test(trackUrl.pathname);

                // Never navigate the POS from data sent by another origin or
                // to a URL that does not belong to the visible order popup.
                if (event.origin !== window.location.origin
                    || !isPosStatusPage
                    || targetOrderId !== activePopupOrderId) {
                    return;
                }

                closePopup(activePopupOrderId, false);
                window.location.assign(`${trackUrl.pathname}${trackUrl.search}${trackUrl.hash}`);
            } catch (error) {
                console.error('Unable to open the tracked order status page', error);
            }
            return;
        }

        if (['orderAccepted', 'orderCancelled', 'closeOrderPopup', 'orderUpdated'].includes(type)) {
            closePopup(activePopupOrderId);
        }
        if (['orderAccepted', 'orderCancelled', 'orderUpdated'].includes(type)) {
            refreshOnlineOrdersTable();
            refreshOrderCount();
        }
    });

    async function pollOnlineOrders() {
        if (pollInFlight) return;
        pollInFlight = true;
        try {
            // no-store avoids stale Hostinger/proxy responses that make a new
            // order look like it never arrived at the POS.
            const res = await fetch(
                `${ORDER_API}?last_order_id=${encodeURIComponent(latestOrderId)}`,
                { cache: 'no-store', credentials: 'same-origin' }
            );
            if (!res.ok) throw new Error(`Order polling returned HTTP ${res.status}`);
            const data = await res.json();
            console.log('[Order Notify] Poll response:', data);
            if (!data.success || !Array.isArray(data.orders)) return;

            useBranchPopupState(
                data.branch_id ?? data.debug?.branch_id,
                data.popup_session_key
            );
            // A page reload should resume a popup that was already queued,
            // not silently discard it because no new rows were returned.
            renderNextPopup();
            const newOrders = data.orders.filter((order) => {
                const orderId = Number(order?.id);
                return Number.isInteger(orderId) && orderId > latestOrderId;
            });

            if (newOrders.length > 0) {
                latestOrderId = Math.max(
                    latestOrderId,
                    Number(data.latest_order_id) || 0,
                    ...newOrders.map((order) => Number(order.id) || 0)
                );
                savePopupState();
                console.log('[Order Notify] Found new orders:', newOrders.length);
                newOrders.forEach((order) => {
                    console.log('[Order Notify] Showing popup for order:', order.id);
                    showPopup(order.id);
                    addNotification(order);
                });
            }

            await removeCancelledPopupOrders();

            // Keep the open POS list in sync when a customer cancels QRPh
            // from the user checkout page, including a popup that was open
            // when the customer cancelled the order.
            await refreshOnlineOrdersTable();
        } catch (err) {
            console.error('Online order poll failed', err);
        } finally {
            pollInFlight = false;
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
