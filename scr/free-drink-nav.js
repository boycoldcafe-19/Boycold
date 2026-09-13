(() => {
    const nav = document.getElementById('mainNav');
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
                <p>View your loyalty card and claim your free drink.</p>
                <button type="button" class="freedrink-btn">Claim Free Drinks</button>
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

    function addPersistentNavbarButton() {
        if (!nav || nav.querySelector('[data-free-drink-nav]')) return;
        const target = nav.querySelector('.nav-right-group');
        if (!target) return;

        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'nav-free-drink-btn';
        button.dataset.freeDrinkNav = 'true';
        button.setAttribute('aria-label', 'Open available free drink reward');
        button.title = 'Free Drinks reward available';
        button.innerHTML = '<i class="fa-solid fa-gift" aria-hidden="true"></i><span>Free Drinks</span>';
        button.addEventListener('click', openModal);
        target.insertBefore(button, target.firstElementChild);
    }

    closeButton?.addEventListener('click', closeModal);
    overlay.addEventListener('click', (event) => {
        if (event.target === overlay) closeModal();
    });
    if (createdOverlay) {
        viewButton?.addEventListener('click', () => {
            window.location.href = 'account.php';
        });
    }
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') closeModal();
    });

    fetch('../api/get_loyalty_data.php', { credentials: 'same-origin', cache: 'no-store' })
        .then((response) => response.json())
        .then((result) => {
            if (result.success && Number(result.loyalty_stamps) >= 10) {
                addPersistentNavbarButton();
                if (sessionStorage.getItem('boycold_free_drink_flow') !== '1'
                    && sessionStorage.getItem('boycold_direct_order') === null) {
                    openModal();
                }
            }
        })
        .catch(() => {});
})();
