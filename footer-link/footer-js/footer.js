const nav = document.getElementById('mainNav');

        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            const isOpen = sidebar.classList.toggle('open');
            overlay.classList.toggle('open', isOpen);
            nav.classList.toggle('sidebar-open', isOpen);
        }

        function closeSidebar() {
            document.getElementById('sidebar').classList.remove('open');
            document.getElementById('sidebarOverlay').classList.remove('open');
            nav.classList.remove('sidebar-open');
        }

        function toggleFaq(btn) {
            const answer = btn.nextElementSibling;
            const isOpen = btn.classList.contains('open');

            document.querySelectorAll('.faq-question').forEach(function (question) {
                question.classList.remove('open');
            });
            document.querySelectorAll('.faq-answer').forEach(function (faqAnswer) {
                faqAnswer.classList.remove('open');
            });

            if (!isOpen && answer && answer.classList.contains('faq-answer')) {
                btn.classList.add('open');
                answer.classList.add('open');
            }
        }

        // Nav avatar dropdown
        function toggleAvatarDropdown() {
            document.getElementById('avatarDropdown').classList.toggle('open');
        }
        document.addEventListener('click', function (e) {
            const wrap = document.querySelector('.avatar-dropdown-wrap');
            if (wrap && !wrap.contains(e.target)) {
                const dd = document.getElementById('avatarDropdown');
                if (dd) dd.classList.remove('open');
            }
        });