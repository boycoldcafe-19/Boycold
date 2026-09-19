(() => {
    // Remove any state left by the retired online redemption flow.
    sessionStorage.removeItem('boycold_free_drink_flow');

    let overlay = document.getElementById('freeDrinkOverlay');
    const createdOverlay = !overlay;
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.id = 'freeDrinkOverlay';
        overlay.className = 'freedrink-overlay';
        overlay.innerHTML = `
            <div class="freedrink-modal" role="dialog" aria-modal="true" aria-labelledby="freeDrinkNavTitle">
                <button type="button" class="freedrink-close" aria-label="Close free drink message">&times;</button>
                <div class="freedrink-img"><img src="../picture/icon2.png" alt="Free drink"></div>
                <h2 id="freeDrinkNavTitle">Free Drink Reward</h2>
                <p>Redeem it by having your loyalty QR code scanned at any Boycold Cafe branch POS.</p>
                <button type="button" class="freedrink-btn">Got it</button>
            </div>`;
        document.body.appendChild(overlay);
    }

    const closeButton = overlay.querySelector('.freedrink-close');
    const viewButton = overlay.querySelector('.freedrink-btn');

    function closeModal() {
        overlay.classList.remove('open');
        overlay.style.display = 'none';
    }

    function openModal() {
        if (!createdOverlay && typeof window.openFreeDrinkModal === 'function') {
            window.openFreeDrinkModal();
            return;
        }
        overlay.style.display = 'flex';
        overlay.classList.add('open');
    }

    closeButton?.addEventListener('click', closeModal);
    overlay.addEventListener('click', (event) => {
        if (event.target === overlay) closeModal();
    });
    if (createdOverlay) {
        viewButton?.addEventListener('click', () => {
            closeModal();
        });
    }
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') closeModal();
    });

    fetch('../api/get_loyalty_data.php', { credentials: 'same-origin', cache: 'no-store' })
        .then((response) => response.json())
        .then((result) => {
            if (result.success && result.show_reward_popup === true) {
                if (sessionStorage.getItem('boycold_direct_order') === null) {
                    openModal();
                }
            }
        })
        .catch(() => {});
})();
