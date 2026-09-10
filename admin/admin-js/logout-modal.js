(function () {
    function addStyles() {
        if (document.getElementById('sharedLogoutModalStyles')) return;

        const style = document.createElement('style');
        style.id = 'sharedLogoutModalStyles';
        style.textContent = `
            .shared-logout-modal {
                position: fixed;
                inset: 0;
                z-index: 10000;
                display: none;
                align-items: center;
                justify-content: center;
                padding: 15px;
                background: rgba(0, 0, 0, 0.55);
            }
            .shared-logout-modal.is-open { display: flex; }
            .shared-logout-dialog {
                position: relative;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                width: 480px;
                max-width: calc(100vw - 30px);
                min-height: 235px;
                padding: 26px 55px 40px;
                border: 4px solid #692727;
                border-radius: 25px;
                background: #fff;
                color: #050505;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.18);
            }
            .shared-logout-logo {
                display: flex;
                align-items: center;
                justify-content: center;
                width: 105px;
                height: 105px;
                margin-bottom: 20px;
            }
            .shared-logout-logo img {
                width: 100%;
                height: 100%;
                object-fit: contain;
            }
            .shared-logout-dialog h2 {
                margin: 0;
                color: #050505;
                font-family: 'Afacad', sans-serif;
                font-size: 29px;
                font-weight: 700;
                line-height: 1;
                text-align: center;
            }
            .shared-logout-actions {
                display: flex;
                justify-content: center;
                width: 100%;
                gap: 67px;
                margin-top: 38px;
            }
            .shared-logout-actions button {
                width: 171px;
                height: 40px;
                border-radius: 25px;
                font: inherit;
                font-size: 18px;
                font-weight: 700;
                cursor: pointer;
            }
            .shared-logout-no {
                border: 3px solid #111;
                background: #fff;
                color: #111;
            }
            .shared-logout-no:hover { background: #f5f5f5; }
            .shared-logout-yes {
                border: 3px solid #692727;
                background: #692727;
                color: #fff;
            }
            .shared-logout-yes:hover { background: #571f1f; }
            .shared-logout-close { display: none; }
            @media (max-width: 560px) {
                .shared-logout-dialog { padding: 24px 20px 30px; }
                .shared-logout-dialog h2 { font-size: 24px; }
                .shared-logout-actions { gap: 14px; margin-top: 28px; }
                .shared-logout-actions button { width: 100%; }
            }
        `;
        document.head.appendChild(style);
    }

    function init() {
        const logoutLinks = document.querySelectorAll('.logout-link:not(#logoutBtn)');
        if (!logoutLinks.length) return;

        addStyles();

        const modal = document.createElement('div');
        modal.className = 'shared-logout-modal';
        modal.setAttribute('role', 'dialog');
        modal.setAttribute('aria-modal', 'true');
        modal.setAttribute('aria-labelledby', 'sharedLogoutTitle');
        modal.setAttribute('aria-hidden', 'true');
        modal.innerHTML = `
            <div class="shared-logout-dialog" tabindex="-1">
                <button type="button" class="shared-logout-close" aria-label="Close">&times;</button>
                <div class="shared-logout-logo"><img src="../img/LOGO.png" alt="BoyCold Cafe"></div>
                <h2 id="sharedLogoutTitle">Are you sure you want to log<br>out your account?</h2>
                <div class="shared-logout-actions">
                    <button type="button" class="shared-logout-no">No</button>
                    <button type="button" class="shared-logout-yes">Yes</button>
                </div>
            </div>
        `;
        document.body.appendChild(modal);

        const dialog = modal.querySelector('.shared-logout-dialog');
        const closeButton = modal.querySelector('.shared-logout-close');
        const noButton = modal.querySelector('.shared-logout-no');
        const yesButton = modal.querySelector('.shared-logout-yes');
        let trigger = null;

        const close = () => {
            modal.classList.remove('is-open');
            modal.setAttribute('aria-hidden', 'true');
            trigger?.focus();
        };
        const logout = () => {
            const target = trigger?.getAttribute('href') || logoutLinks[0].getAttribute('href') || 'logout.php';
            window.location.href = target;
        };

        logoutLinks.forEach(link => link.addEventListener('click', event => {
            event.preventDefault();
            trigger = link;
            modal.classList.add('is-open');
            modal.setAttribute('aria-hidden', 'false');
            dialog.focus();
        }));

        closeButton.addEventListener('click', close);
        noButton.addEventListener('click', close);
        yesButton.addEventListener('click', logout);
        modal.addEventListener('click', event => {
            if (event.target === modal) close();
        });
        document.addEventListener('keydown', event => {
            if (event.key === 'Escape' && modal.classList.contains('is-open')) close();
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
