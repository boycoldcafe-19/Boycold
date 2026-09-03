<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BoyCold - POS</title>
    <link rel="stylesheet" href="dash-css/pos-loyalty.css">
    <link rel="icon" href="../img/LOGO 2.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Afacad:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Gaegu:wght@400;700&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jsqr/1.4.0/jsQR.js"></script>
</head>

<body>
    <script>
        document.body.classList.toggle(
            "dark-theme",
            (localStorage.getItem("boycold_theme") || "dark") === "dark"
        );
    </script>
    <div class="app-shell">

        <!-- SIDEBAR -->
        <aside class="sidebar" id="sidebar">

            <div class="sidebar-brand">
                <span class="brand-mark" aria-hidden="true">
                     <img class="logo-light" src="../img/icon2.png" alt="LOGO">
                     <img class="logo-dark" src="../img/ChatGPT Image Jul 1, 2026, 12_58_44 PM 1.png" alt="LOGO">
                </span>
                <span class="brand-text">
                    <span class="brand-name">B<span class="special-letter">o</span><span
                            class="special-letter-2">y</span>C<span class="special-letter">o</span>LD CAFE</span>
                    <span class="brand-sub">Point of Sale</span>
                </span>
            </div>

            <nav class="sidebar-nav">
                <ul>
                    <li>
                        <a href="pos-menu.php">
                            <span class="nav-icon1"><svg width="12" height="12" viewBox="0 0 12 12" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path
                                        d="M0.5 5C0.367392 5 0.240215 4.94732 0.146447 4.85355C0.0526785 4.75979 0 4.63261 0 4.5V0.5C0 0.367392 0.0526785 0.240215 0.146447 0.146447C0.240215 0.0526785 0.367392 0 0.5 0H4.5C4.63261 0 4.75979 0.0526785 4.85355 0.146447C4.94732 0.240215 5 0.367392 5 0.5V4.5C5 4.63261 4.94732 4.75979 4.85355 4.85355C4.75979 4.94732 4.63261 5 4.5 5H0.5ZM7.5 5C7.36739 5 7.24021 4.94732 7.14645 4.85355C7.05268 4.75979 7 4.63261 7 4.5V0.5C7 0.367392 7.05268 0.240215 7.14645 0.146447C7.24021 0.0526785 7.36739 0 7.5 0H11.5C11.6326 0 11.7598 0.0526785 11.8536 0.146447C11.9473 0.240215 12 0.367392 12 0.5V4.5C12 4.63261 11.9473 4.75979 11.8536 4.85355C11.7598 4.94732 11.6326 5 11.5 5H7.5ZM0.5 12C0.367392 12 0.240215 11.9473 0.146447 11.8536C0.0526785 11.7598 0 11.6326 0 11.5V7.5C0 7.36739 0.0526785 7.24021 0.146447 7.14645C0.240215 7.05268 0.367392 7 0.5 7H4.5C4.63261 7 4.75979 7.05268 4.85355 7.14645C4.94732 7.24021 5 7.36739 5 7.5V11.5C5 11.6326 4.94732 11.7598 4.85355 11.8536C4.75979 11.9473 4.63261 12 4.5 12H0.5ZM7.5 12C7.36739 12 7.24021 11.9473 7.14645 11.8536C7.05268 11.7598 7 11.6326 7 11.5V7.5C7 7.36739 7.05268 7.24021 7.14645 7.14645C7.24021 7.05268 7.36739 7 7.5 7H11.5C11.6326 7 11.7598 7.05268 11.8536 7.14645C11.9473 7.24021 12 7.36739 12 7.5V11.5C12 11.6326 11.9473 11.7598 11.8536 11.8536C11.7598 11.9473 11.6326 12 11.5 12H7.5Z"
                                        fill="currentColor" />
                                </svg></span>
                            <span class="nav-label">Menu</span>
                            <i class="fa-solid fa-chevron-right nav-chevron"></i>
                        </a>
                    </li>
                    <li>
                        <a href="pos-status.php">
                            <span class="nav-icon"><svg width="19" height="22" viewBox="0 0 19 22" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path
                                        d="M14.8882 1H3.31469C2.03632 1 1 2.03632 1 3.31469V18.3602C1 19.6386 2.03632 20.6749 3.31469 20.6749H14.8882C16.1665 20.6749 17.2029 19.6386 17.2029 18.3602V3.31469C17.2029 2.03632 16.1665 1 14.8882 1Z"
                                        stroke="currentColor" stroke-width="2" />
                                    <path d="M5.62939 6.78662H12.5735M5.62939 11.416H12.5735M5.62939 16.0454H10.2588"
                                        stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                                </svg></span>
                            <span class="nav-label">Order Status</span>
                            <i class="fa-solid fa-chevron-right nav-chevron"></i>
                        </a>
                    </li>
                    <li>
                        <a href="pos-online.php">
                            <span class="nav-icon2"><i class="fa-regular fa-bell"></i></span>
                            <span class="nav-label">Online Orders</span>
                            <span class="nav-badge">3</span>
                            <i class="fa-solid fa-chevron-right nav-chevron"></i>
                        </a>
                    </li>
                    <li>
                        <a href="pos-history.php">
                            <span class="nav-icon"><svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path
                                        d="M9.64456 19.2891C7.17984 19.2891 5.03232 18.4722 3.20199 16.8383C1.37167 15.2045 0.3222 13.1638 0.0535808 10.7162H2.2504C2.50044 12.5737 3.32666 14.1096 4.72905 15.3241C6.13144 16.5386 7.76994 17.1459 9.64456 17.1459C11.7342 17.1459 13.507 16.4183 14.963 14.963C16.419 13.5077 17.1466 11.7349 17.1459 9.64456C17.1452 7.55419 16.4175 5.78174 14.963 4.32719C13.5085 2.87265 11.7356 2.14466 9.64456 2.14324C8.4122 2.14324 7.26021 2.429 6.18859 3.00053C5.11698 3.57206 4.21503 4.35791 3.48276 5.35809H6.42971V7.50133H0V1.07162H2.14324V3.58992C3.05411 2.44686 4.16609 1.56278 5.47918 0.937666C6.79227 0.312555 8.18073 0 9.64456 0C10.9841 0 12.2389 0.254688 13.4092 0.764064C14.5794 1.27344 15.5974 1.9607 16.4633 2.82586C17.3291 3.69101 18.0168 4.70905 18.5261 5.87997C19.0355 7.05089 19.2898 8.30575 19.2891 9.64456C19.2884 10.9834 19.0341 12.2382 18.5261 13.4092C18.0182 14.5801 17.3306 15.5981 16.4633 16.4633C15.596 17.3284 14.5779 18.016 13.4092 18.5261C12.2404 19.0362 10.9855 19.2906 9.64456 19.2891ZM12.6451 14.1454L8.57294 10.0732V4.28647H10.7162V9.21591L14.1454 12.6451L12.6451 14.1454Z"
                                        fill="currentColor" />
                                </svg></span>
                            <span class="nav-label">Order History</span>
                            <i class="fa-solid fa-chevron-right nav-chevron"></i>
                        </a>
                    </li>
                    <li>
                        <a href="pos-settings.php">
                            <span class="nav-icon"><svg width="22" height="23" viewBox="0 0 22 23" fill="currentColor"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path
                                        d="M9.30036 22.2897C8.79884 22.2897 8.36716 22.1225 8.00533 21.7881C7.64349 21.4538 7.42505 21.0451 7.35001 20.5622L7.09925 18.7233C6.85778 18.6304 6.63043 18.519 6.41719 18.389C6.20395 18.2589 5.9948 18.1196 5.78974 17.971L4.06229 18.6954C3.59792 18.8998 3.13355 18.9183 2.66919 18.7512C2.20482 18.584 1.84261 18.2868 1.58257 17.8596L0.273048 15.5749C0.0130021 15.1477 -0.0612965 14.6926 0.0501518 14.2097C0.1616 13.7267 0.412359 13.3274 0.802427 13.0116L2.27912 11.8971C2.26054 11.7671 2.25125 11.6415 2.25125 11.5204V10.7681C2.25125 10.6478 2.26054 10.5226 2.27912 10.3926L0.802427 9.27807C0.412359 8.9623 0.1616 8.56294 0.0501518 8.08C-0.0612965 7.59706 0.0130021 7.14198 0.273048 6.71476L1.58257 4.43007C1.84261 4.00285 2.20482 3.70566 2.66919 3.53848C3.13355 3.37131 3.59792 3.38988 4.06229 3.59421L5.78974 4.31862C5.99406 4.17002 6.20767 4.03071 6.43056 3.90069C6.65346 3.77067 6.87636 3.65922 7.09925 3.56634L7.35001 1.72745C7.42431 1.24451 7.64275 0.835862 8.00533 0.501517C8.36791 0.167172 8.79958 0 9.30036 0H11.9194C12.4209 0 12.853 0.167172 13.2155 0.501517C13.5781 0.835862 13.7962 1.24451 13.8697 1.72745L14.1205 3.56634C14.362 3.65922 14.5897 3.77067 14.8037 3.90069C15.0177 4.03071 15.2264 4.17002 15.43 4.31862L17.1575 3.59421C17.6218 3.38988 18.0862 3.37131 18.5506 3.53848C19.0149 3.70566 19.3771 4.00285 19.6372 4.43007L20.9467 6.71476C21.2067 7.14198 21.281 7.59706 21.1696 8.08C21.0582 8.56294 20.8074 8.9623 20.4173 9.27807L18.9406 10.3926C18.9592 10.5226 18.9685 10.6481 18.9685 10.7692V11.5204C18.9685 11.6415 18.9499 11.7671 18.9128 11.8971L20.3895 13.0116C20.7795 13.3274 21.0303 13.7267 21.1417 14.2097C21.2532 14.6926 21.1789 15.1477 20.9188 15.5749L19.5815 17.8596C19.3214 18.2868 18.9592 18.584 18.4948 18.7512C18.0305 18.9183 17.5661 18.8998 17.1017 18.6954L15.43 17.971C15.2257 18.1196 15.0121 18.2589 14.7892 18.389C14.5663 18.519 14.3434 18.6304 14.1205 18.7233L13.8697 20.5622C13.7954 21.0451 13.5774 21.4538 13.2155 21.7881C12.8537 22.1225 12.4217 22.2897 11.9194 22.2897H9.30036ZM9.49539 20.0607H11.6965L12.0866 17.1073C12.6624 16.9587 13.1966 16.7406 13.6892 16.4531C14.1818 16.1656 14.632 15.8171 15.0399 15.4077L17.7983 16.5501L18.8849 14.6554L16.4888 12.8444C16.5816 12.5844 16.6467 12.3106 16.6838 12.023C16.721 11.7355 16.7395 11.4428 16.7395 11.1448C16.7395 10.8469 16.721 10.5545 16.6838 10.2677C16.6467 9.98094 16.5816 9.70677 16.4888 9.44524L18.8849 7.63421L17.7983 5.73959L15.0399 6.90979C14.6313 6.48257 14.181 6.1252 13.6892 5.83766C13.1973 5.55012 12.6631 5.33169 12.0866 5.18234L11.7244 2.22897H9.52325L9.13318 5.18234C8.55737 5.33094 8.02353 5.54938 7.53167 5.83766C7.03982 6.12594 6.58919 6.47403 6.17981 6.88193L3.42146 5.73959L2.33484 7.63421L4.73098 9.41738C4.63811 9.696 4.57309 9.97462 4.53594 10.2532C4.4988 10.5319 4.48022 10.8291 4.48022 11.1448C4.48022 11.442 4.4988 11.7299 4.53594 12.0086C4.57309 12.2872 4.63811 12.5658 4.73098 12.8444L2.33484 14.6554L3.42146 16.5501L6.17981 15.3799C6.58845 15.8071 7.03907 16.1648 7.53167 16.4531C8.02427 16.7414 8.55811 16.9595 9.13318 17.1073L9.49539 20.0607ZM10.6656 15.0455C11.7429 15.0455 12.6624 14.6647 13.4239 13.9032C14.1855 13.1416 14.5663 12.2222 14.5663 11.1448C14.5663 10.0675 14.1855 9.14804 13.4239 8.38648C12.6624 7.62492 11.7429 7.24414 10.6656 7.24414C9.56969 7.24414 8.64578 7.62492 7.89388 8.38648C7.14198 9.14804 6.76565 10.0675 6.76491 11.1448C6.76417 12.2222 7.14049 13.1416 7.89388 13.9032C8.64727 14.6647 9.57118 15.0455 10.6656 15.0455Z"
                                        fill="currentColor" />
                                </svg></span>
                            <span class="nav-label">POS Settings</span>
                            <i class="fa-solid fa-chevron-right nav-chevron"></i>
                        </a>
                    </li>
                </ul>

                <div class="sidebar-divider"></div>

                <ul>
                    <li>
                        <a href="pos-shift.php">
                            <span class="nav-icon"><svg width="23" height="23" viewBox="0 0 23 23" fill="currentColor"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path
                                        d="M6.94408 0C5.83907 0 4.77932 0.438964 3.99796 1.22033C3.2166 2.00169 2.77763 3.06144 2.77763 4.16645V9.16619C3.22165 8.93937 3.68681 8.75656 4.16645 8.62039V4.16645C4.16645 3.42978 4.45909 2.72327 4.98 2.20237C5.50091 1.68146 6.20741 1.38882 6.94408 1.38882H18.0546C18.7913 1.38882 19.4978 1.68146 20.0187 2.20237C20.5396 2.72327 20.8323 3.42978 20.8323 4.16645V15.277C20.8323 16.0137 20.5396 16.7202 20.0187 17.2411C19.4978 17.762 18.7913 18.0546 18.0546 18.0546H13.6007C13.4627 18.5398 13.2808 19.0027 13.0549 19.4434H18.0546C19.1596 19.4434 20.2194 19.0045 21.0007 18.2231C21.7821 17.4418 22.2211 16.382 22.2211 15.277V4.16645C22.2211 3.06144 21.7821 2.00169 21.0007 1.22033C20.2194 0.438964 19.1596 0 18.0546 0H6.94408ZM6.24968 22.2211C7.90719 22.2211 9.49682 21.5626 10.6689 20.3906C11.8409 19.2185 12.4994 17.6289 12.4994 15.9714C12.4994 14.3139 11.8409 12.7242 10.6689 11.5522C9.49682 10.3802 7.90719 9.72172 6.24968 9.72172C4.59216 9.72172 3.00253 10.3802 1.83049 11.5522C0.658446 12.7242 0 14.3139 0 15.9714C0 17.6289 0.658446 19.2185 1.83049 20.3906C3.00253 21.5626 4.59216 22.2211 6.24968 22.2211ZM6.24968 12.4994C6.43384 12.4994 6.61047 12.5725 6.7407 12.7027C6.87092 12.833 6.94408 13.0096 6.94408 13.1938V15.277H9.02731C9.21148 15.277 9.3881 15.3501 9.51833 15.4804C9.64856 15.6106 9.72172 15.7872 9.72172 15.9714C9.72172 16.1556 9.64856 16.3322 9.51833 16.4624C9.3881 16.5926 9.21148 16.6658 9.02731 16.6658H6.94408V18.749C6.94408 18.9332 6.87092 19.1098 6.7407 19.2401C6.61047 19.3703 6.43384 19.4434 6.24968 19.4434C6.06551 19.4434 5.88888 19.3703 5.75866 19.2401C5.62843 19.1098 5.55527 18.9332 5.55527 18.749V16.6658H3.47204C3.28787 16.6658 3.11125 16.5926 2.98102 16.4624C2.85079 16.3322 2.77763 16.1556 2.77763 15.9714C2.77763 15.7872 2.85079 15.6106 2.98102 15.4804C3.11125 15.3501 3.28787 15.277 3.47204 15.277H5.55527V13.1938C5.55527 13.0096 5.62843 12.833 5.75866 12.7027C5.88888 12.5725 6.06551 12.4994 6.24968 12.4994ZM13.8882 4.86086C13.8882 4.67669 13.815 4.50007 13.6848 4.36984C13.5546 4.23961 13.3779 4.16645 13.1938 4.16645C13.0096 4.16645 12.833 4.23961 12.7027 4.36984C12.5725 4.50007 12.4994 4.67669 12.4994 4.86086V9.02731C12.4994 9.21148 12.5725 9.3881 12.7027 9.51833C12.833 9.64856 13.0096 9.72172 13.1938 9.72172H15.9714C16.1556 9.72172 16.3322 9.64856 16.4624 9.51833C16.5926 9.3881 16.6658 9.21148 16.6658 9.02731C16.6658 8.84314 16.5926 8.66652 16.4624 8.53629C16.3322 8.40606 16.1556 8.3329 15.9714 8.3329H13.8882V4.86086Z"
                                        fill="currentColor" />
                                </svg></span>
                            <span class="nav-label">Open / Close Shift</span>
                            <i class="fa-solid fa-chevron-right nav-chevron"></i>
                        </a>
                    </li>
                    <li>
                        <a href="pos-loyalty.php" class="active">
                            <span class="nav-icon"><svg width="22" height="18" viewBox="0 0 22 18" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path
                                        d="M0.75 8.75C0.75 4.979 0.75 3.093 1.922 1.922C3.094 0.751 4.979 0.75 8.75 0.75H12.75C16.521 0.75 18.407 0.75 19.578 1.922C20.749 3.094 20.75 4.979 20.75 8.75C20.75 12.521 20.75 14.407 19.578 15.578C18.406 16.749 16.521 16.75 12.75 16.75H8.75C4.979 16.75 3.093 16.75 1.922 15.578C0.751 14.406 0.75 12.521 0.75 8.75Z"
                                        stroke="currentColor" stroke-width="1.5" />
                                    <path d="M8.75 12.75H4.75M12.75 12.75H11.25M0.75 6.75H20.75" stroke="currentColor"
                                        stroke-width="1.5" stroke-linecap="round" />
                                </svg></span>
                            <span class="nav-label">Loyalty Card</span>
                            <i class="fa-solid fa-chevron-right nav-chevron"></i>
                        </a>
                    </li>
                </ul>
            </nav>

            <div class="sidebar-footer">
                <a href="#" class="logout-link">
                    <span class="nav-icon"><i class="fa-solid fa-right-from-bracket"></i></span>
                    <span class="nav-label">Log Out</span>
                </a>
            </div>
        </aside>

        <!-- MAIN PANEL -->
        <div class="main-panel">

            <div class="top-header">
                <div class="shift-pill">
                    <span class="shift-dot"></span>
                    Open Shift
                </div>

                <div class="header-divider"></div>

                <div class="notif-wrap">
                    <button class="icon-btn" id="notifBtn" aria-label="Notifications">
                        <i class="fa-regular fa-bell"></i>
                        <span class="icon-badge" id="notifBadge">2</span>
                    </button>

                    <div class="notif-dropdown" id="notifDropdown">
                        <div class="notif-header">
                            <span class="notif-title">Notifications</span>
                            <a href="#" class="notif-mark-read" id="markAllRead">Mark all as read</a>
                        </div>

                        <div class="notif-list" id="notifList">
                            <div class="notif-item unread">
                                <div class="notif-icon notif-icon-bag"><i class="fa-solid fa-bag-shopping"></i></div>
                                <div class="notif-content">
                                    <p class="notif-item-title">New online order received</p>
                                    <p class="notif-item-sub">Order #0001</p>
                                </div>
                                <div class="notif-time">
                                    <span class="notif-time-main">10:30 am</span>
                                    <span class="notif-time-sub">Just now</span>
                                </div>
                            </div>

                            <div class="notif-item unread">
                                <div class="notif-icon notif-icon-card"><i class="fa-solid fa-credit-card"></i></div>
                                <div class="notif-content">
                                    <p class="notif-item-title">Payment Confirmed</p>
                                    <p class="notif-item-sub">Order #0003</p>
                                </div>
                                <div class="notif-time">
                                    <span class="notif-time-main">10:30 am</span>
                                    <span class="notif-time-sub">Just now</span>
                                </div>
                            </div>
                        </div>

                        <a href="notification.html" class="notif-footer">
                            View all notifications <i class="fa-solid fa-chevron-right"></i>
                        </a>
                    </div>
                </div>

                <div class="header-divider"></div>

                <button class="profile-btn">
                    <div class="profile-avatar">
                        <svg class="logo-light" width="36" height="36" viewBox="0 0 36 36" fill="none"
                            xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M8.75762 25.5987C10.0301 24.6256 11.4522 23.8587 13.0241 23.2978C14.5959 22.7369 16.2426 22.456 17.9642 22.455C19.6858 22.454 21.3325 22.7349 22.9043 23.2978C24.4762 23.8607 25.8983 24.6276 27.1708 25.5987C28.044 24.5757 28.7242 23.4156 29.2112 22.1181C29.6982 20.8207 29.9412 19.436 29.9403 17.964C29.9403 14.6456 28.7741 11.8197 26.4417 9.48641C24.1094 7.15308 21.2836 5.98691 17.9642 5.98791C14.6448 5.98891 11.819 7.15557 9.48666 9.48791C7.15432 11.8202 5.98815 14.6456 5.98815 17.964C5.98815 19.436 6.23167 20.8207 6.71869 22.1181C7.20572 23.4156 7.88536 24.5757 8.75762 25.5987ZM14.2411 17.9445C13.2302 16.9355 12.7247 15.6945 12.7247 14.2214C12.7247 12.7484 13.2302 11.5069 14.2411 10.4969C15.2521 9.48691 16.4931 8.98192 17.9642 8.98192C19.4353 8.98192 20.6768 9.48741 21.6888 10.4984C22.7007 11.5094 23.2057 12.7504 23.2037 14.2214C23.2017 15.6925 22.6967 16.934 21.6888 17.946C20.6808 18.958 19.4393 19.463 17.9642 19.461C16.4892 19.459 15.2476 18.954 14.2397 17.946M17.9642 32.934C15.8933 32.934 13.9472 32.5408 12.1259 31.7544C10.3045 30.9679 8.72019 29.9016 7.37289 28.5553C6.02558 27.209 4.95921 25.6246 4.17378 23.8023C3.38835 21.9799 2.99514 20.0338 2.99414 17.964C2.99314 15.8941 3.38636 13.948 4.17378 12.1256C4.96121 10.3033 6.02758 8.71895 7.37289 7.37264C8.71819 6.02633 10.3025 4.95996 12.1259 4.17354C13.9492 3.38711 15.8953 2.9939 17.9642 2.9939C20.0331 2.9939 21.9792 3.38711 23.8025 4.17354C25.6259 4.95996 27.2102 6.02633 28.5555 7.37264C29.9008 8.71895 30.9677 10.3033 31.7561 12.1256C32.5445 13.948 32.9373 15.8941 32.9343 17.964C32.9313 20.0338 32.5381 21.9799 31.7546 23.8023C30.9712 25.6246 29.9048 27.209 28.5555 28.5553C27.2062 29.9016 25.6219 30.9684 23.8025 31.7559C21.9832 32.5433 20.0371 32.936 17.9642 32.934Z"
                                fill="black" />
                        </svg>
                        <svg class="logo-dark" width="36" height="36" viewBox="0 0 36 36" fill="none"
                            xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M8.75762 25.5988C10.0301 24.6257 11.4522 23.8588 13.0241 23.2979C14.5959 22.737 16.2426 22.4561 17.9642 22.4551C19.6858 22.4541 21.3325 22.735 22.9043 23.2979C24.4762 23.8608 25.8983 24.6277 27.1708 25.5988C28.044 24.5758 28.7242 23.4157 29.2112 22.1183C29.6982 20.8209 29.9412 19.4361 29.9403 17.9641C29.9403 14.6457 28.7741 11.8199 26.4417 9.48653C24.1094 7.15319 21.2836 5.98702 17.9642 5.98802C14.6448 5.98902 11.819 7.15569 9.48666 9.48802C7.15432 11.8204 5.98815 14.6457 5.98815 17.9641C5.98815 19.4361 6.23167 20.8209 6.71869 22.1183C7.20572 23.4157 7.88536 24.5758 8.75762 25.5988ZM14.2411 17.9446C13.2302 16.9356 12.7247 15.6946 12.7247 14.2216C12.7247 12.7485 13.2302 11.507 14.2411 10.497C15.2521 9.48702 16.4931 8.98203 17.9642 8.98203C19.4353 8.98203 20.6768 9.48752 21.6888 10.4985C22.7007 11.5095 23.2057 12.7505 23.2037 14.2216C23.2017 15.6926 22.6967 16.9341 21.6888 17.9461C20.6808 18.9581 19.4393 19.4631 17.9642 19.4611C16.4892 19.4591 15.2476 18.9541 14.2397 17.9461M17.9642 32.9341C15.8933 32.9341 13.9472 32.5409 12.1259 31.7545C10.3045 30.9681 8.72019 29.9017 7.37289 28.5554C6.02558 27.2091 4.95921 25.6247 4.17378 23.8024C3.38835 21.98 2.99514 20.0339 2.99414 17.9641C2.99314 15.8942 3.38636 13.9481 4.17378 12.1257C4.96121 10.3034 6.02758 8.71906 7.37289 7.37275C8.71819 6.02645 10.3025 4.96008 12.1259 4.17365C13.9492 3.38722 15.8953 2.99401 17.9642 2.99401C20.0331 2.99401 21.9792 3.38722 23.8025 4.17365C25.6259 4.96008 27.2102 6.02645 28.5555 7.37275C29.9008 8.71906 30.9677 10.3034 31.7561 12.1257C32.5445 13.9481 32.9373 15.8942 32.9343 17.9641C32.9313 20.0339 32.5381 21.98 31.7546 23.8024C30.9712 25.6247 29.9048 27.2091 28.5555 28.5554C27.2062 29.9017 25.6219 30.9686 23.8025 31.756C21.9832 32.5434 20.0371 32.9361 17.9642 32.9341Z"
                                fill="white" />
                        </svg>
                    </div>
                    <span class="profile-name">Main Branch</span>
                </button>
            </div>

            <!-- LOYALTY CARD CONTENT -->
            <main class="loyalty-workspace">
                <section class="loyalty-page" aria-labelledby="loyalty-title">
                    <header class="loyalty-page-header">
                        <h1 id="loyalty-title">Virtual Loyalty Card</h1>
                        <p>Scan customer QR code to add a stamp to their loyalty card</p>
                    </header>

                    <div class="loyalty-top-grid">
                        <!-- QR SCANNER -->
                        <section class="loyalty-card-panel scanner-panel">
                            <h2>1. Scan Customer QR Code</h2>
                            <p class="panel-description">Use the scanner to find the customer's loyalty card.</p>

                            <div class="qr-scanner" id="qrScanner" aria-label="QR scanner preview">
                                <video id="qrVideo" class="qr-video" autoplay playsinline muted></video>
                                <canvas id="qrCanvas" class="qr-canvas" hidden></canvas>

                                <div class="qr-corner qr-corner-tl"></div>
                                <div class="qr-corner qr-corner-tr"></div>
                                <div class="qr-corner qr-corner-bl"></div>
                                <div class="qr-corner qr-corner-br"></div>

                                <div class="qr-scan-line" id="qrScanLine"></div>

                                <img class="qr-icon" id="qrIcon" src="../img/ChatGPT Image Jul 8, 2026, 01_01_59 AM 1.png" alt=""
                                    aria-hidden="true">

                                <div class="qr-overlay" id="qrOverlay">
                                    <i class="fa-solid fa-camera qr-overlay-icon"></i>
                                    <p id="qrOverlayText">Enable your camera to start scanning loyalty QR codes.</p>
                                    <button type="button" class="qr-enable-btn" id="qrEnableBtn">
                                        <i class="fa-solid fa-video"></i>
                                        Enable Camera
                                    </button>
                                </div>
                            </div>

                            <div class="qr-status-row">
                                <p class="qr-status" id="qrStatus"></p>
                                <button type="button" class="qr-stop-link" id="qrStopBtn" hidden>Stop Camera</button>
                            </div>
                        </section>

                        <!-- CUSTOMER CARD -->
                        <section class="loyalty-card-panel customer-loyalty-panel">
                            <div class="panel-title-row">
                                <h2>2. Customer Loyalty Card</h2>
                                <span class="card-active-badge">Card Active</span>
                            </div>

                            <div class="customer-summary">
                                <div class="customer-avatar">
                                    <i class="fa-solid fa-user"></i>
                                </div>
                                <div class="customer-details">
                                    <strong>Juan Dela Cruz</strong>
                                    <span>0912 345 6789</span>
                                    <small>Member since May 01, 2026</small>
                                </div>
                            </div>

                            <div class="stamps-heading">
                                <strong>Stamps Collected</strong>
                                <span id="stampsHeadingCount">8 / 10</span>
                            </div>

                            <div class="stamps-grid" id="stampsGrid" aria-label="8 of 10 stamps collected">
                                <span class="stamp filled"><i class="fa-solid fa-mug-hot"></i></span>
                                <span class="stamp filled"><i class="fa-solid fa-mug-hot"></i></span>
                                <span class="stamp filled"><i class="fa-solid fa-mug-hot"></i></span>
                                <span class="stamp filled"><i class="fa-solid fa-mug-hot"></i></span>
                                <span class="stamp filled"><i class="fa-solid fa-mug-hot"></i></span>
                                <span class="stamp filled"><i class="fa-solid fa-mug-hot"></i></span>
                                <span class="stamp filled"><i class="fa-solid fa-mug-hot"></i></span>
                                <span class="stamp filled"><i class="fa-solid fa-mug-hot"></i></span>
                                <span class="stamp empty"><span>9</span></span>
                                <span class="stamp empty"><span>10</span></span>
                            </div>

                            <div class="reward-progress">
                                <div class="reward-star"><i class="fa-solid fa-star"></i></div>
                                <div class="reward-progress-info">
                                    <span id="rewardMessage">2 stamps away from the free drink!</span>
                                    <div class="progress-track"><span id="progressBarFill"></span></div>
                                </div>
                                <strong id="rewardCountText">8 / 10</strong>
                            </div>
                        </section>
                    </div>

                    <div class="loyalty-bottom-grid">
                        <!-- HOW IT WORKS -->
                        <section class="loyalty-card-panel how-it-works-panel">
                            <h2>How It Works</h2>

                            <div class="how-step">
                                <span class="step-number">1</span>
                                <div>
                                    <strong>Make a purchase</strong>
                                    <span>Earn 1 stamp for every eligible purchase</span>
                                </div>
                            </div>

                            <div class="how-step">
                                <span class="step-number">2</span>
                                <div>
                                    <strong>Collect 10 stamps</strong>
                                    <span>Every stamp brings closer to free drink</span>
                                </div>
                            </div>

                            <div class="how-step">
                                <span class="step-number">3</span>
                                <div>
                                    <strong>Claim the reward</strong>
                                    <span>Show the full stamp card to redeem the free regular drink</span>
                                </div>
                            </div>
                        </section>

                        <!-- RECENT TRANSACTIONS -->
                        <section class="loyalty-card-panel transactions-panel">
                            <div class="transactions-header">
                                <h2>Recent Transactions</h2>
                                <a href="#">View all</a>
                            </div>

                            <div class="transactions-list" id="transactionsList">
                                <div class="transaction-item">
                                    <span class="transaction-icon"><i class="fa-solid fa-bag-shopping"></i></span>
                                    <div class="transaction-details">
                                        <strong>Purchase</strong>
                                        <span>May 05, 2026&nbsp; 8:30 am</span>
                                    </div>
                                    <strong class="transaction-stamp">+1 Stamp</strong>
                                </div>

                                <div class="transaction-item">
                                    <span class="transaction-icon"><i class="fa-solid fa-bag-shopping"></i></span>
                                    <div class="transaction-details">
                                        <strong>Purchase</strong>
                                        <span>May 02, 2026&nbsp; 8:30 am</span>
                                    </div>
                                    <strong class="transaction-stamp">+1 Stamp</strong>
                                </div>

                                <div class="transaction-item">
                                    <span class="transaction-icon"><i class="fa-solid fa-bag-shopping"></i></span>
                                    <div class="transaction-details">
                                        <strong>Purchase</strong>
                                        <span>May 01, 2026&nbsp; 8:30 am</span>
                                    </div>
                                    <strong class="transaction-stamp">+1 Stamp</strong>
                                </div>
                            </div>
                        </section>
                    </div>

                    <div class="loyalty-actions">
                        <button class="clear-stamp-btn" type="button" id="clearStampBtn">
                            <i class="fa-solid fa-rotate-left"></i>
                            Clear
                        </button>
                        <button class="add-stamp-btn" type="button" id="addStampBtn">
                            <i class="fa-regular fa-user"></i>
                            Add Stamp
                        </button>
                    </div>
                </section>
            </main>
        </div>
    </div>

    <script>
        // ---- Notification dropdown ----
        const notifBtn = document.getElementById('notifBtn');
        const notifDropdown = document.getElementById('notifDropdown');
        if (notifBtn && notifDropdown) {
            notifBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                notifDropdown.classList.toggle('open');
            });
            document.addEventListener('click', () => notifDropdown.classList.remove('open'));
        }

        // =========================================================
        // Live camera QR scanner (getUserMedia + jsQR)
        // =========================================================
        const qrScanner = document.getElementById('qrScanner');
        const qrVideo = document.getElementById('qrVideo');
        const qrCanvas = document.getElementById('qrCanvas');
        const qrIcon = document.getElementById('qrIcon');
        const qrOverlay = document.getElementById('qrOverlay');
        const qrOverlayText = document.getElementById('qrOverlayText');
        const qrEnableBtn = document.getElementById('qrEnableBtn');
        const qrStopBtn = document.getElementById('qrStopBtn');
        const qrStatus = document.getElementById('qrStatus');
        const qrCtx = qrCanvas.getContext('2d', { willReadFrequently: true });

        let mediaStream = null;
        let scanRafId = null;
        let scanPaused = false;

        function setStatus(text) {
            qrStatus.textContent = text || '';
        }

        // iPadOS/tablet Safari often ignores a strict facingMode constraint, and
        // camera labels are only readable *after* permission is granted — so we
        // request in stages and, once allowed, explicitly re-select the camera
        // whose label says "back"/"rear" if the first grant didn't give us that one.
        async function getBackCameraStream() {
            const idealRes = { width: { ideal: 1280 }, height: { ideal: 720 } };
            const attempts = [
                { video: { facingMode: { exact: 'environment' }, ...idealRes }, audio: false },
                { video: { facingMode: { ideal: 'environment' }, ...idealRes }, audio: false },
                { video: { ...idealRes }, audio: false },
                { video: true, audio: false }
            ];

            let stream = null;
            let lastErr = null;

            for (const constraints of attempts) {
                try {
                    stream = await navigator.mediaDevices.getUserMedia(constraints);
                    break;
                } catch (err) {
                    lastErr = err;
                    // Permission denial / insecure context won't be fixed by retrying
                    // with different constraints, so stop immediately.
                    if (err && (err.name === 'NotAllowedError' || err.name === 'SecurityError')) {
                        throw err;
                    }
                    // OverconstrainedError / NotFoundError -> fall through and try the next, looser constraint
                }
            }

            if (!stream) throw lastErr || new Error('Unable to access any camera.');

            // Confirm (or upgrade to) the actual rear/back camera once labels are available.
            try {
                const currentTrack = stream.getVideoTracks()[0];
                const currentSettings = currentTrack.getSettings ? currentTrack.getSettings() : {};

                if (currentSettings.facingMode !== 'environment') {
                    const devices = await navigator.mediaDevices.enumerateDevices();
                    const videoInputs = devices.filter(d => d.kind === 'videoinput');
                    const backCam = videoInputs.find(d => /back|rear|environment/i.test(d.label));

                    if (backCam && backCam.deviceId && backCam.deviceId !== currentSettings.deviceId) {
                        const upgraded = await navigator.mediaDevices.getUserMedia({
                            video: { deviceId: { exact: backCam.deviceId } },
                            audio: false
                        });
                        stream.getTracks().forEach(t => t.stop());
                        stream = upgraded;
                    }
                }
            } catch (e) {
                // If device selection fails for any reason, just keep the stream we already have.
            }

            return stream;
        }

        async function startCamera() {
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                qrOverlayText.textContent = 'Camera access is not supported in this browser.';
                return;
            }

            qrEnableBtn.disabled = true;
            qrEnableBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Requesting camera...';

            try {
                // Ask the browser for permission to use the camera (back camera preferred).
                mediaStream = await getBackCameraStream();

                qrVideo.srcObject = mediaStream;
                await qrVideo.play();

                qrScanner.classList.add('active', 'scanning');
                qrIcon.style.display = 'none';
                qrOverlay.style.display = 'none';
                qrStopBtn.hidden = false;
                setStatus('Point the camera at a customer\u2019s QR code.');

                scanPaused = false;
                requestAnimationFrame(scanLoop);
            } catch (err) {
                qrEnableBtn.disabled = false;
                qrEnableBtn.innerHTML = '<i class="fa-solid fa-video"></i> Enable Camera';

                if (err && err.name === 'NotAllowedError') {
                    qrOverlayText.textContent = 'Camera permission was denied. Go to Settings > Safari > Camera on this iPad/tablet, allow access, then try again.';
                } else if (err && err.name === 'NotFoundError') {
                    qrOverlayText.textContent = 'No camera was found on this device.';
                } else if (err && err.name === 'SecurityError') {
                    qrOverlayText.textContent = 'Camera access needs a secure (https://) connection. Please load this page over HTTPS.';
                } else if (err && err.name === 'OverconstrainedError') {
                    qrOverlayText.textContent = 'This device\u2019s back camera could not be started. Please try again.';
                } else {
                    qrOverlayText.textContent = 'Could not access the camera. Please try again.';
                }
                setStatus('');
            }
        }

        function stopCamera() {
            if (scanRafId) cancelAnimationFrame(scanRafId);
            scanRafId = null;

            if (mediaStream) {
                mediaStream.getTracks().forEach(track => track.stop());
                mediaStream = null;
            }

            qrVideo.pause();
            qrVideo.srcObject = null;

            qrScanner.classList.remove('active', 'scanning', 'success');
            qrIcon.style.display = '';
            qrOverlay.style.display = '';
            qrOverlayText.textContent = 'Enable your camera to start scanning loyalty QR codes.';
            qrEnableBtn.disabled = false;
            qrEnableBtn.innerHTML = '<i class="fa-solid fa-video"></i> Enable Camera';
            qrStopBtn.hidden = true;
            setStatus('');
        }

        function scanLoop() {
            if (!mediaStream) return;

            if (!scanPaused && qrVideo.readyState === qrVideo.HAVE_ENOUGH_DATA) {
                qrCanvas.width = qrVideo.videoWidth;
                qrCanvas.height = qrVideo.videoHeight;
                qrCtx.drawImage(qrVideo, 0, 0, qrCanvas.width, qrCanvas.height);

                const imageData = qrCtx.getImageData(0, 0, qrCanvas.width, qrCanvas.height);
                const code = window.jsQR ? jsQR(imageData.data, imageData.width, imageData.height, {
                    inversionAttempts: 'dontInvert'
                }) : null;

                if (code && code.data) {
                    handleQrDetected(code.data);
                }
            }

            scanRafId = requestAnimationFrame(scanLoop);
        }

        function handleQrDetected(data) {
            scanPaused = true;
            qrScanner.classList.remove('scanning');
            qrScanner.classList.add('success');
            setStatus(`Card found: ${data}`);

            // Brief pause so the same code isn't read multiple times in a row,
            // then resume scanning for the next customer.
            setTimeout(() => {
                qrScanner.classList.remove('success');
                if (mediaStream) {
                    qrScanner.classList.add('scanning');
                    setStatus('Point the camera at a customer\u2019s QR code.');
                }
                scanPaused = false;
            }, 2200);
        }

        qrEnableBtn.addEventListener('click', startCamera);
        qrStopBtn.addEventListener('click', stopCamera);

        // =========================================================
        // Stamp card: Add Stamp / Clear / auto-redeem at 10/10
        // =========================================================
        const TOTAL_LOYALTY_STAMPS = 10;
        const BASE_STAMPS = 8; // stamps this customer had when the card was scanned

        let currentStamps = BASE_STAMPS;
        const addedTransactions = []; // stack of transaction DOM elements created by "Add Stamp" this session

        const stampsGrid = document.getElementById('stampsGrid');
        const stampsHeadingCount = document.getElementById('stampsHeadingCount');
        const rewardMessage = document.getElementById('rewardMessage');
        const progressBarFill = document.getElementById('progressBarFill');
        const rewardCountText = document.getElementById('rewardCountText');
        const addStampBtn = document.getElementById('addStampBtn');
        const clearStampBtn = document.getElementById('clearStampBtn');
        const transactionsList = document.getElementById('transactionsList');

        function stampMarkup(count) {
            return Array.from({ length: TOTAL_LOYALTY_STAMPS }, (_, i) => {
                if (i < count) {
                    const isNewest = i === count - 1;
                    return `<span class="stamp filled${isNewest ? ' just-added' : ''}"><i class="fa-solid fa-mug-hot"></i></span>`;
                }
                return `<span class="stamp empty"><span>${i + 1}</span></span>`;
            }).join('');
        }

        function renderStampCard() {
            stampsGrid.innerHTML = stampMarkup(currentStamps);
            stampsGrid.setAttribute('aria-label', `${currentStamps} of ${TOTAL_LOYALTY_STAMPS} stamps collected`);
            stampsHeadingCount.textContent = `${currentStamps} / ${TOTAL_LOYALTY_STAMPS}`;
            rewardCountText.textContent = `${currentStamps} / ${TOTAL_LOYALTY_STAMPS}`;
            progressBarFill.style.width = `${(currentStamps / TOTAL_LOYALTY_STAMPS) * 100}%`;

            const remaining = TOTAL_LOYALTY_STAMPS - currentStamps;
            if (remaining <= 0) {
                rewardMessage.textContent = 'Free drink unlocked \u2014 ready to redeem!';
            } else if (remaining === 1) {
                rewardMessage.textContent = '1 stamp away from the free drink!';
            } else {
                rewardMessage.textContent = `${remaining} stamps away from the free drink!`;
            }

            // Add Stamp button flips into a Redeem action once the card is full.
            if (currentStamps >= TOTAL_LOYALTY_STAMPS) {
                addStampBtn.classList.add('is-redeem');
                addStampBtn.innerHTML = '<i class="fa-solid fa-gift"></i> Redeem Reward';
            } else {
                addStampBtn.classList.remove('is-redeem');
                addStampBtn.innerHTML = '<i class="fa-regular fa-user"></i> Add Stamp';
            }

            // Nothing to undo once we're back down to zero stamps.
            clearStampBtn.disabled = currentStamps === 0;
        }

        function addTransactionEntry(label, stampText, iconClass) {
            const now = new Date();
            const dateText = now.toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' });
            const timeText = now.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' }).toLowerCase();

            const item = document.createElement('div');
            item.className = 'transaction-item';
            item.innerHTML = `
                <span class="transaction-icon"><i class="fa-solid ${iconClass}"></i></span>
                <div class="transaction-details">
                    <strong>${label}</strong>
                    <span>${dateText}&nbsp; ${timeText}</span>
                </div>
                <strong class="transaction-stamp">${stampText}</strong>
            `;
            transactionsList.insertBefore(item, transactionsList.firstChild);
            return item;
        }

        addStampBtn.addEventListener('click', () => {
            if (currentStamps >= TOTAL_LOYALTY_STAMPS) {
                // Redeem: cash in the completed card and start a fresh cycle.
                addTransactionEntry('Reward Redeemed', 'Free Drink', 'fa-gift');
                currentStamps = 0;
                addedTransactions.length = 0; // nothing left to undo after a redeem
                renderStampCard();
                return;
            }

            currentStamps += 1;
            renderStampCard();
            const entry = addTransactionEntry('Purchase', '+1 Stamp', 'fa-bag-shopping');
            addedTransactions.push(entry);

            // Clear the pulse class after the animation plays once.
            const newest = stampsGrid.querySelector('.stamp.just-added');
            if (newest) {
                setTimeout(() => newest.classList.remove('just-added'), 600);
            }
        });

        clearStampBtn.addEventListener('click', () => {
            if (currentStamps <= 0) return;

            currentStamps -= 1;
            renderStampCard();

            // Undo the transaction entry that this stamp created, if it was
            // added during this session (pre-existing history is left alone).
            const lastAdded = addedTransactions.pop();
            if (lastAdded && lastAdded.parentNode) {
                lastAdded.parentNode.removeChild(lastAdded);
            }
        });

        renderStampCard();


        window.addEventListener('beforeunload', stopCamera);
        document.addEventListener('visibilitychange', () => {
            if (document.hidden && mediaStream) {
                scanPaused = true;
            } else if (!document.hidden && mediaStream) {
                scanPaused = false;
            }
        });
    </script>
</body>

</html>