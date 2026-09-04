<?php
require_once __DIR__ . '/admin_guard.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="admin-css/adminsettings.css">
    <link rel="stylesheet" href="admin-css/admin-responsive.css">
    <link rel="icon" href="../img/LOGO 2.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Afacad:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Gaegu:wght@400;700&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>
    <title>BoyCold - Admin Settings</title>
</head>

<body>
    <div class="app-shell">

        <!-- SIDEBAR -->
        <aside class="sidebar" id="sidebar">

            <div class="sidebar-brand">
                <span class="brand-mark" aria-hidden="true">
                    <img src="/public/assets/icons/ChatGPT Image Jun 23, 2026, 09_22_57 PM 1.png" alt="">
                </span>
                <span class="brand-text">
                    <span class="brand-name">B<span class="special-letter">o</span><span
                            class="special-letter-2">y</span>C<span class="special-letter">o</span>LD CAFE</span>
                    <span class="brand-sub">Administration Panel</span>
                </span>
            </div>

            <nav class="sidebar-nav">
                <div class="nav-top">
                    <ul>
                        <li>
                            <a href="dashboard.php">
                                <span class="nav-icon1"><svg width="12" height="12" viewBox="0 0 12 12" fill="none"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path
                                            d="M0.5 5C0.367392 5 0.240215 4.94732 0.146447 4.85355C0.0526785 4.75979 0 4.63261 0 4.5V0.5C0 0.367392 0.0526785 0.240215 0.146447 0.146447C0.240215 0.0526785 0.367392 0 0.5 0H4.5C4.63261 0 4.75979 0.0526785 4.85355 0.146447C4.94732 0.240215 5 0.367392 5 0.5V4.5C5 4.63261 4.94732 4.75979 4.85355 4.85355C4.75979 4.94732 4.63261 5 4.5 5H0.5ZM7.5 5C7.36739 5 7.24021 4.94732 7.14645 4.85355C7.05268 4.75979 7 4.63261 7 4.5V0.5C7 0.367392 7.05268 0.240215 7.14645 0.146447C7.24021 0.0526785 7.36739 0 7.5 0H11.5C11.6326 0 11.7598 0.0526785 11.8536 0.146447C11.9473 0.240215 12 0.367392 12 0.5V4.5C12 4.63261 11.9473 4.75979 11.8536 4.85355C11.7598 4.94732 11.6326 5 11.5 5H7.5ZM0.5 12C0.367392 12 0.240215 11.9473 0.146447 11.8536C0.0526785 11.7598 0 11.6326 0 11.5V7.5C0 7.36739 0.0526785 7.24021 0.146447 7.14645C0.240215 7.05268 0.367392 7 0.5 7H4.5C4.63261 7 4.75979 7.05268 4.85355 7.14645C4.94732 7.24021 5 7.36739 5 7.5V11.5C5 11.6326 4.94732 11.7598 4.85355 11.8536C4.75979 11.9473 4.63261 12 4.5 12H0.5ZM7.5 12C7.36739 12 7.24021 11.9473 7.14645 11.8536C7.05268 11.7598 7 11.6326 7 11.5V7.5C7 7.36739 7.05268 7.24021 7.14645 7.14645C7.24021 7.05268 7.36739 7 7.5 7H11.5C11.6326 7 11.7598 7.05268 11.8536 7.14645C11.9473 7.24021 12 7.36739 12 7.5V11.5C12 11.6326 11.7598 11.8536 11.8536C11.7598 11.9473 11.6326 12 11.5 12H7.5Z"
                                            fill="currentColor" />
                                    </svg></span>
                                <span class="nav-label">Dashboard</span>
                                <i class="fa-solid fa-chevron-right nav-chevron"></i>
                            </a>
                        </li>
                        <li>
                            <a href="orders.php">
                                <span class="nav-icon"><svg width="19" height="22" viewBox="0 0 19 22" fill="none"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path
                                            d="M14.8882 1H3.31469C2.03632 1 1 2.03632 1 3.31469V18.3602C1 19.6386 2.03632 20.6749 3.31469 20.6749H14.8882C16.1665 20.6749 17.2029 19.6386 17.2029 18.3602V3.31469C17.2029 2.03632 16.1665 1 14.8882 1Z"
                                            stroke="currentColor" stroke-width="2" />
                                        <path
                                            d="M5.62939 6.78662H12.5735M5.62939 11.416H12.5735M5.62939 16.0454H10.2588"
                                            stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                                    </svg></span>
                                <span class="nav-label">Orders</span>
                                <i class="fa-solid fa-chevron-right nav-chevron"></i>
                            </a>
                        </li>
                        <li>
                            <a href="data-analytics.php">
                                <span class="nav-icon2"><svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path
                                            d="M15.8601 4.39V19.39C15.8601 21.06 17.0001 22 18.2501 22C19.3901 22 20.6401 21.21 20.6401 19.39V4.5C20.6401 2.96 19.5001 2 18.2501 2C17.0001 2 15.8601 3.06 15.8601 4.39ZM9.61011 12V19.39C9.61011 21.07 10.7701 22 12.0001 22C13.1401 22 14.3901 21.21 14.3901 19.39V12.11C14.3901 10.57 13.2501 9.61 12.0001 9.61C10.7501 9.61 9.61011 10.67 9.61011 12ZM5.75011 17.23C7.07011 17.23 8.14011 18.3 8.14011 19.61C8.14011 20.2439 7.88831 20.8518 7.44009 21.3C6.99188 21.7482 6.38398 22 5.75011 22C5.11624 22 4.50833 21.7482 4.06012 21.3C3.61191 20.8518 3.36011 20.2439 3.36011 19.61C3.36011 18.3 4.43011 17.23 5.75011 17.23Z"
                                            fill="white" />
                                    </svg></span>
                                <span class="nav-label">Data Analytics</span>
                                <i class="fa-solid fa-chevron-right nav-chevron"></i>
                            </a>
                        </li>
                        <li>
                            <a href="forecasting.php">
                                <span class="nav-icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path
                                            d="M21.3751 6C21.0698 6.00008 20.7692 6.0747 20.4993 6.21737C20.2294 6.36005 19.9984 6.56647 19.8264 6.81869C19.6545 7.07092 19.5467 7.36132 19.5124 7.66468C19.4782 7.96803 19.5185 8.27516 19.6299 8.55938L15.6845 12.5048C15.2447 12.3317 14.7556 12.3317 14.3157 12.5048L11.4953 9.68438C11.6069 9.40009 11.6475 9.09283 11.6134 8.78931C11.5792 8.48579 11.4715 8.1952 11.2995 7.94281C11.1275 7.69042 10.8964 7.48387 10.6264 7.34113C10.3563 7.19839 10.0555 7.12377 9.75011 7.12377C9.44467 7.12377 9.14386 7.19839 8.87384 7.34113C8.60381 7.48387 8.37274 7.69042 8.20073 7.94281C8.02872 8.1952 7.92096 8.48579 7.88684 8.78931C7.85272 9.09283 7.89327 9.40009 8.00495 9.68438L3.30948 14.3798C2.90848 14.2225 2.46554 14.2081 2.05514 14.339C1.64474 14.4698 1.29192 14.738 1.056 15.0984C0.82007 15.4588 0.715432 15.8895 0.759675 16.3179C0.803918 16.7464 0.994344 17.1466 1.29893 17.4512C1.60352 17.7558 2.0037 17.9462 2.43218 17.9904C2.86065 18.0347 3.2913 17.93 3.6517 17.6941C4.0121 17.4582 4.28028 17.1054 4.41114 16.695C4.542 16.2846 4.52757 15.8416 4.37026 15.4406L9.06573 10.7452C9.50556 10.9183 9.99466 10.9183 10.4345 10.7452L13.2549 13.5656C13.1433 13.8499 13.1027 14.1572 13.1368 14.4607C13.171 14.7642 13.2787 15.0548 13.4507 15.3072C13.6227 15.5596 13.8538 15.7661 14.1238 15.9089C14.3939 16.0516 14.6947 16.1262 15.0001 16.1262C15.3055 16.1262 15.6063 16.0516 15.8764 15.9089C16.1464 15.7661 16.3775 15.5596 16.5495 15.3072C16.7215 15.0548 16.8293 14.7642 16.8634 14.4607C16.8975 14.1572 16.8569 13.8499 16.7453 13.5656L20.6907 9.62016C20.9475 9.72102 21.2233 9.76399 21.4986 9.74601C21.7738 9.72803 22.0417 9.64953 22.2832 9.51613C22.5246 9.38272 22.7336 9.19768 22.8953 8.97421C23.0571 8.75073 23.1675 8.49433 23.2187 8.22329C23.2699 7.95225 23.2607 7.67324 23.1918 7.40616C23.1228 7.13907 22.9957 6.8905 22.8197 6.67816C22.6436 6.46582 22.4228 6.29495 22.1731 6.17773C21.9234 6.0605 21.651 5.99982 21.3751 6Z"
                                            fill="white" />
                                    </svg></span>
                                <span class="nav-label">Forecasting</span>
                                <i class="fa-solid fa-chevron-right nav-chevron"></i>
                            </a>
                        </li>
                        <li>
                            <a href="inventory.php">
                                <span class="nav-icon"><svg width="30" height="30" viewBox="0 0 30 30" fill="none"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path
                                            d="M25.9126 20.6502V9.48774C25.9136 9.34411 25.8793 9.20245 25.8126 9.07524C25.7205 8.87674 25.5611 8.71729 25.3626 8.62524L15.3626 4.15024C15.2409 4.09499 15.1088 4.06641 14.9751 4.06641C14.8414 4.06641 14.7093 4.09499 14.5876 4.15024L4.5876 8.62524C4.42677 8.70617 4.29077 8.82903 4.19397 8.98084C4.09716 9.13265 4.04314 9.30778 4.0376 9.48774V20.5127C4.04694 20.6918 4.10252 20.8653 4.19891 21.0165C4.2953 21.1676 4.42922 21.2912 4.5876 21.3752L14.5876 25.8502C14.7086 25.908 14.841 25.9379 14.9751 25.9379C15.1092 25.9379 15.2416 25.908 15.3626 25.8502L25.3626 21.3752C25.507 21.3091 25.6327 21.2083 25.7287 21.0818C25.8247 20.9553 25.8878 20.8071 25.9126 20.6502ZM5.9126 10.9252L14.0376 14.5752V23.5502L5.9126 19.9127V10.9252ZM15.9126 14.5752L24.0376 10.9252V19.9127L15.9126 23.5502V14.5752ZM15.0001 6.02524L22.7126 9.48774L15.0001 12.9377L7.2876 9.48774L15.0001 6.02524Z"
                                            fill="white" />
                                    </svg></span>
                                <span class="nav-label">Inventory</span>
                                <i class="fa-solid fa-chevron-right nav-chevron"></i>
                            </a>
                        </li>
                        <li>
                            <a href="mapping.php">
                                <span class="nav-icon"><svg width="27" height="27" viewBox="0 0 27 27" fill="none"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" clip-rule="evenodd"
                                            d="M23.1154 4.13114C22.9044 3.92024 22.6183 3.80176 22.32 3.80176C22.0217 3.80176 21.7356 3.92024 21.5246 4.13114L15.4901 10.1656H2.25V12.4156H2.259C2.50425 18.4119 7.443 23.1988 13.5 23.1988C19.557 23.1988 24.4958 18.4119 24.741 12.4156H24.75V10.1656H18.6716L23.1154 5.72189C23.3263 5.51092 23.4448 5.22483 23.4448 4.92652C23.4448 4.62821 23.3263 4.34211 23.1154 4.13114ZM15.9491 12.4156H22.4888C22.3733 14.7218 21.3759 16.8954 19.7029 18.4869C18.0298 20.0783 15.8091 20.9658 13.5 20.9658C11.1909 20.9658 8.97019 20.0783 7.29713 18.4869C5.62406 16.8954 4.62667 14.7218 4.51125 12.4156H15.9491Z"
                                            fill="white" />
                                    </svg></span>
                                <span class="nav-label">Ingredients Mapping</span>
                                <i class="fa-solid fa-chevron-right nav-chevron"></i>
                            </a>
                        </li>
                    </ul>

                    <div class="sidebar-divider"></div>

                    <ul>
                        <li>
                            <a href="menu-management.php">
                                <span class="nav-icon"><i class="fa-solid fa-bars"></i></span>
                                <span class="nav-label">Menu Management</span>
                                <i class="fa-solid fa-chevron-right nav-chevron"></i>
                            </a>
                        </li>
                        <li>
                            <a href="customers.php">
                                <span class="nav-icon"><i class="fa-solid fa-users"></i></span>
                                <span class="nav-label">Customers</span>
                                <i class="fa-solid fa-chevron-right nav-chevron"></i>
                            </a>
                        </li>
                        <li>
                            <a href="loyalty-card.php">
                                <span class="nav-icon"><svg width="22" height="18" viewBox="0 0 22 18" fill="none"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path
                                            d="M0.75 8.75C0.75 4.979 0.75 3.093 1.922 1.922C3.094 0.751 4.979 0.75 8.75 0.75H12.75C16.521 0.75 18.407 0.75 19.578 1.922C20.749 3.094 20.75 4.979 20.75 8.75C20.75 12.521 20.75 14.407 19.578 15.578C18.406 16.749 16.521 16.75 12.75 16.75H8.75C4.979 16.75 3.093 16.75 1.922 15.578C0.751 14.406 0.75 12.521 0.75 8.75Z"
                                            stroke="currentColor" stroke-width="1.5" />
                                        <path d="M8.75 12.75H4.75M12.75 12.75H11.25M0.75 6.75H20.75"
                                            stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                                    </svg></span>
                                <span class="nav-label">Loyalty Card</span>
                                <i class="fa-solid fa-chevron-right nav-chevron"></i>
                            </a>
                        </li>
                    </ul>

                    <div class="sidebar-divider"></div>

                    <ul>
                        <li>
                            <a href="adminsettings.php" class="active">
                                <span class="nav-icon">
                                    <i class="fa-solid fa-gear"></i>
                                </span>
                                <span class="nav-label">Settings</span>
                                <i class="fa-solid fa-chevron-right nav-chevron"></i>
                            </a>
                        </li>
                        <li>
                            <a href="#" class="logout-link" id="logoutBtn">
                                <span class="nav-icon">
                                    <i class="fa-solid fa-right-from-bracket"></i>
                                </span>
                                <span class="nav-label">Log Out</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>
        </aside>
        <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

        <!-- MAIN PANEL -->
        <div class="main-panel">

            <div class="top-header">

                <div class="notif-wrap" style="position: relative;">
                    <button class="header-action" id="notifBtn" aria-label="Notifications">
                        <i class="fa-regular fa-bell"></i>
                        <span>Notification</span>
                        <span class="header-badge" id="notifBadge">2</span>
                    </button>

                    <div class="notif-dropdown" id="notifDropdown">
                        <div class="notif-header">
                            <span class="notif-title">Notifications</span>
                            <a href="#" class="notif-mark-read" id="markAllRead">Mark all as read</a>
                        </div>

                        <div class="notif-list" id="notifList">
                            <div class="notif-item notif-error unread">
                                <div class="notif-icon notif-icon-error"><i class="fa-solid fa-xmark"></i></div>
                                <div class="notif-content">
                                    <p class="notif-item-title">Failed to Update Item</p>
                                    <p class="notif-item-sub">Failed to update item “Vanilla Syrup”</p>
                                    <span class="notif-relative-time">2 minutes ago</span>
                                </div>
                                <div class="notif-time">
                                    <span class="notif-time-main">10:30 am</span>
                                    <span class="notif-time-sub">Just now</span>
                                </div>
                            </div>

                            <div class="notif-item notif-success">
                                <div class="notif-icon notif-icon-success"><i class="fa-solid fa-bag-shopping"></i>
                                </div>
                                <div class="notif-content">
                                    <p class="notif-item-title">New Order Completed</p>
                                    <p class="notif-item-sub">Order #0003 has been completed successfully</p>
                                    <span class="notif-relative-time">2 minutes ago</span>
                                </div>
                                <div class="notif-time">
                                    <span class="notif-time-main">10:30 am</span>
                                    <span class="notif-time-sub">Just now</span>
                                </div>
                            </div>

                            <div class="notif-item notif-error unread">
                                <div class="notif-icon notif-icon-error"><i class="fa-solid fa-xmark"></i></div>
                                <div class="notif-content">
                                    <p class="notif-item-title">Failed to Update Item</p>
                                    <p class="notif-item-sub">Failed to update item “Vanilla Syrup”</p>
                                    <span class="notif-relative-time">2 minutes ago</span>
                                </div>
                                <div class="notif-time">
                                    <span class="notif-time-main">10:30 am</span>
                                    <span class="notif-time-sub">Just now</span>
                                </div>
                            </div>

                            <div class="notif-item notif-success">
                                <div class="notif-icon notif-icon-success"><i class="fa-solid fa-bag-shopping"></i>
                                </div>
                                <div class="notif-content">
                                    <p class="notif-item-title">New Order Completed</p>
                                    <p class="notif-item-sub">Order #0003 has been completed successfully</p>
                                    <span class="notif-relative-time">2 minutes ago</span>
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

                <div class="alert-wrap">
                    <button class="header-action" id="alertsBtn" aria-label="Alerts">

                        <svg width="34" height="34" viewBox="0 0 34 34" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M18.8402 4.45947L31.0718 25.6443C31.2583 25.9673 31.3564 26.3338 31.3564 26.7068C31.3565 27.0798 31.2583 27.4462 31.0718 27.7693C30.8853 28.0923 30.617 28.3606 30.294 28.5471C29.9709 28.7336 29.6045 28.8318 29.2315 28.8318H4.7685C4.39549 28.8318 4.02905 28.7336 3.70602 28.5471C3.38299 28.3606 3.11474 28.0923 2.92824 27.7693C2.74174 27.4462 2.64355 27.0798 2.64355 26.7068C2.64356 26.3338 2.74175 25.9673 2.92825 25.6443L15.1598 4.45947C15.9772 3.04281 18.0214 3.04281 18.8402 4.45947ZM17 6.93864L5.99533 25.9985H28.0047L17 6.93864ZM17 21.2498C17.3757 21.2498 17.7361 21.3991 18.0017 21.6647C18.2674 21.9304 18.4167 22.2907 18.4167 22.6665C18.4167 23.0422 18.2674 23.4025 18.0017 23.6682C17.7361 23.9339 17.3757 24.0831 17 24.0831C16.6243 24.0831 16.2639 23.9339 15.9983 23.6682C15.7326 23.4025 15.5833 22.6665 15.5833 22.6665C15.5833 22.2907 15.7326 21.9304 15.9983 21.6647C16.2639 21.3991 16.6243 21.2498 17 21.2498ZM17 11.3331C17.3757 11.3331 17.7361 11.4824 18.0017 11.7481C18.2674 12.0137 18.4167 12.3741 18.4167 12.7498V18.4165C18.4167 18.7922 18.2674 19.1525 18.0017 19.4182C17.7361 19.6839 17.3757 19.8331 17 19.8331C16.6243 19.8331 16.2639 19.6839 15.9983 19.4182C15.7326 19.1525 15.5833 18.7922 15.5833 18.4165V12.7498C15.5833 12.3741 15.7326 12.0137 15.9983 11.7481C16.2639 11.4824 16.6243 11.3331 17 11.3331Z"
                                fill="black" fill-opacity="0.8" />
                        </svg>
                        <span>Alerts</span>
                        <span class="header-badge">2</span>
                    </button>

                    <div class="alert-dropdown" id="alertsDropdown">
                        <div class="notif-header">
                            <span class="notif-title">Alerts</span>
                            <a href="#" class="notif-mark-read" id="markAllAlertsRead">Mark all as read</a>
                        </div>

                        <div class="notif-list alert-list">
                            <div class="notif-item alert-warning unread">
                                <div class="notif-icon alert-icon-warning"><i class="fa-solid fa-exclamation"></i></div>
                                <div class="notif-content">
                                    <p class="notif-item-title">Low Stock Alert</p>
                                    <p class="notif-item-sub">Caramel Syrup is running low</p>
                                    <span class="notif-relative-time">2 minutes ago</span>
                                </div>
                                <div class="notif-time">
                                    <span class="notif-time-main">10:30 am</span>
                                    <span class="notif-time-sub">Just now</span>
                                </div>
                            </div>

                            <div class="notif-item alert-warning">
                                <div class="notif-icon alert-icon-warning"><i class="fa-solid fa-exclamation"></i></div>
                                <div class="notif-content">
                                    <p class="notif-item-title">Low Stock Alert</p>
                                    <p class="notif-item-sub">Choco Drizzle is running low</p>
                                    <span class="notif-relative-time">2 minutes ago</span>
                                </div>
                                <div class="notif-time">
                                    <span class="notif-time-main">10:30 am</span>
                                    <span class="notif-time-sub">Just now</span>
                                </div>
                            </div>

                            <div class="notif-item alert-error unread">
                                <div class="notif-icon alert-icon-error"><i class="fa-solid fa-xmark"></i></div>
                                <div class="notif-content">
                                    <p class="notif-item-title">Item out of Stock</p>
                                    <p class="notif-item-sub">Vanilla Syrup is now out of stock</p>
                                    <span class="notif-relative-time">2 minutes ago</span>
                                </div>
                                <div class="notif-time">
                                    <span class="notif-time-main">10:30 am</span>
                                    <span class="notif-time-sub">Just now</span>
                                </div>
                            </div>

                            <div class="notif-item alert-error">
                                <div class="notif-icon alert-icon-error"><i class="fa-solid fa-xmark"></i></div>
                                <div class="notif-content">
                                    <p class="notif-item-title">Item out of Stock</p>
                                    <p class="notif-item-sub">Vanilla Syrup is now out of stock</p>
                                    <span class="notif-relative-time">2 minutes ago</span>
                                </div>
                                <div class="notif-time">
                                    <span class="notif-time-main">10:30 am</span>
                                    <span class="notif-time-sub">Just now</span>
                                </div>
                            </div>
                        </div>

                        <a href="alerts.html" class="notif-footer">
                            View all notifications <i class="fa-solid fa-chevron-right"></i>
                        </a>
                    </div>
                </div>

                <div class="header-divider"></div>


                <div class="profile-wrap" style="position: relative;">
                    <button class="profile-btn" id="profileBtn">
                        <div class="profile-avatar">
                            <svg width="36" height="36" viewBox="0 0 36 36" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M8.75737 25.5989C10.0298 24.6259 11.452 23.8589 13.0238 23.298C14.5957 22.7372 16.2424 22.4562 17.964 22.4552C19.6855 22.4542 21.3322 22.7352 22.9041 23.298C24.4759 23.8609 25.8981 24.6279 27.1705 25.5989C28.0438 24.576 28.7239 23.4158 29.211 22.1184C29.698 20.821 29.941 19.4363 29.94 17.9642C29.94 14.6458 28.7738 11.82 26.4415 9.48666C24.1092 7.15332 21.2833 5.98715 17.964 5.98815C14.6446 5.98915 11.8187 7.15582 9.48641 9.48815C7.15408 11.8205 5.98791 14.6458 5.98791 17.9642C5.98791 19.4363 6.23142 20.821 6.71845 22.1184C7.20547 23.4158 7.88512 24.576 8.75737 25.5989ZM14.2409 17.9447C13.2299 16.9358 12.7244 15.6947 12.7244 14.2217C12.7244 12.7486 13.2299 11.5071 14.2409 10.4971C15.2519 9.48715 16.4929 8.98216 17.964 8.98216C19.435 8.98216 20.6765 9.48765 21.6885 10.4986C22.7005 11.5096 23.2055 12.7506 23.2035 14.2217C23.2015 15.6927 22.6965 16.9343 21.6885 17.9462C20.6805 18.9582 19.439 19.4632 17.964 19.4612C16.4889 19.4592 15.2474 18.9542 14.2394 17.9462M17.964 32.9343C15.8931 32.9343 13.947 32.541 12.1256 31.7546C10.3043 30.9682 8.71995 29.9018 7.37264 28.5555C6.02534 27.2092 4.95897 25.6249 4.17354 23.8025C3.38811 21.9802 2.9949 20.0341 2.9939 17.9642C2.9929 15.8943 3.38611 13.9482 4.17354 12.1259C4.96096 10.3035 6.02733 8.71919 7.37264 7.37288C8.71795 6.02658 10.3023 4.96021 12.1256 4.17378C13.949 3.38735 15.8951 2.99414 17.964 2.99414C20.0328 2.99414 21.9789 3.38735 23.8023 4.17378C25.6256 4.96021 27.21 6.02658 28.5553 7.37288C29.9006 8.71919 30.9675 10.3035 31.7559 12.1259C32.5443 13.9482 32.937 15.8943 32.934 17.9642C32.931 20.0341 32.5378 21.9802 31.7544 23.8025C30.9709 25.6249 29.9046 27.2092 28.5553 28.5555C27.206 29.9018 25.6216 30.9687 23.8023 31.7561C21.9829 32.5435 20.0368 32.9363 17.964 32.9343Z"
                                    fill="black" />
                            </svg>
                        </div>
                        <div class="profile-info">
                            <span class="profile-name">Sta. Barbara Branch</span>
                            <span class="profile-role">Admin</span>
                        </div>
                        <i class="fa-solid fa-chevron-down profile-caret"></i>
                    </button>
                    <div class="notif-dropdown" id="profileDropdown" style="width: 200px; right: 0;">
                        <a href="#" class="notif-item" style="padding: 12px 16px; font-weight: 600;"><i
                                class="fa-regular fa-user" style="margin-right: 8px;"></i> Profile</a>
                        <a href="adminsettings.php" class="notif-item" style="padding: 12px 16px; font-weight: 600;"><i
                                class="fa-solid fa-gear" style="margin-right: 8px;"></i> Settings</a>
                        <a href="adminlogin.php" class="notif-item"
                            style="padding: 12px 16px; font-weight: 600; color: #e53935;"><i
                                class="fa-solid fa-right-from-bracket" style="margin-right: 8px;"></i> Log Out</a>
                    </div>
                </div>

            </div>

            <!-- SETTINGS CONTENT -->
            <div class="settings-content-wrapper">
                <div class="settings-header">
                    <h1>Settings</h1>
                    <p>Manage your account and system preference</p>
                </div>

                <!-- 1. ADMIN PROFILE CARD -->
                <div class="settings-card">
                    <div class="settings-card-header">
                        <div class="settings-card-title">
                            <h2>Admin Profile</h2>
                            <p>Update your account information</p>
                        </div>
                        <button class="btn-outline-maroon" id="editProfileBtn">Edit Profile</button>
                    </div>

                    <div class="profile-section-body">
                        <!-- Upload Image Box -->
                        <div class="upload-box">
                            <img id="profileImagePreview" class="preview-img" alt="Profile Preview">
                            <i class="fa-solid fa-cloud-arrow-up upload-icon"></i>
                            <span class="upload-title">Upload Image</span>
                            <span class="upload-sub">PNG, JPG, WEBP up to 5mb</span>
                            <input type="file" id="imageInput" accept="image/png, image/jpeg, image/webp"
                                style="display: none;">
                            <button class="choose-btn" onclick="document.getElementById('imageInput').click()">
                                <i class="fa-regular fa-image"></i> Choose Image
                            </button>
                        </div>

                        <!-- Info Fields -->
                        <div class="profile-info-fields">
                            <div class="info-group">
                                <label>Full Name</label>
                                <span class="info-value-text" id="nameText"><?= htmlspecialchars($adminAccount['employee_name'] ?: 'Admin', ENT_QUOTES, 'UTF-8') ?></span>
                                <input type="text" class="edit-field" id="nameInput" value="<?= htmlspecialchars($adminAccount['employee_name'] ?: 'Admin', ENT_QUOTES, 'UTF-8') ?>">
                            </div>
                            <div class="info-group">
                                <label>Email Address</label>
                                <span class="info-value-text" id="emailText"><?= htmlspecialchars($adminAccount['email'], ENT_QUOTES, 'UTF-8') ?></span>
                                <input type="email" class="edit-field" id="emailInput" value="<?= htmlspecialchars($adminAccount['email'], ENT_QUOTES, 'UTF-8') ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. CHANGE PASSWORD CARD -->
                <div class="settings-card">
                    <div class="settings-card-header">
                        <div class="settings-card-title">
                            <h2>Change Password</h2>
                            <p>Update your password regularly to keep your account secure.</p>
                        </div>
                        <button class="btn-outline-maroon" id="changePasswordBtn">Change Password</button>
                    </div>

                    <div class="password-grid">
                        <div class="field-group">
                            <label for="currentPassword">Current Password</label>
                            <input type="password" id="currentPassword" placeholder="Enter current password">
                        </div>
                        <div class="field-group">
                            <label for="newPassword">New Password</label>
                            <input type="password" id="newPassword" placeholder="Enter new password">
                        </div>
                        <div class="field-group">
                            <label for="confirmPassword">Confirm New Password</label>
                            <input type="password" id="confirmPassword" placeholder="Confirm new password">
                        </div>
                    </div>
                </div>

                <!-- 3. SYSTEM INFORMATION CARD -->
                <div class="settings-card">
                    <div class="settings-card-header" style="margin-bottom: 20px;">
                        <div class="settings-card-title">
                            <h2>System Information</h2>
                            <p>Configure general system preferences.</p>
                        </div>
                    </div>

                    <div class="system-info-grid">
                        <div class="info-group">
                            <label>Date Format</label>
                            <span class="info-value-text">May 31, 2025 (MMMM DD, YYYY)</span>
                        </div>
                        <div class="info-group">
                            <label>System Name</label>
                            <span class="info-value-text">Boycold Cafe Ordering System</span>
                        </div>
                        <div class="info-group">
                            <label>Version</label>
                            <span class="info-value-text">V1.0.0</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="logout-modal" id="logoutModal">

                <div class="logout-modal-box">

                    <button class="logout-close" id="logoutClose" aria-label="Close">
                        <i class="fa-solid fa-xmark"></i>
                    </button>

                    <div class="logout-logo">
                        <img src="/public/assets/icons/LOGO.png" alt="BoyCold Cafe">
                    </div>

                    <h2>
                        Are you sure you want to log<br>
                        out your account?
                    </h2>

                    <div class="logout-actions">

                        <button class="logout-no" id="logoutNo">
                            No
                        </button>

                        <button class="logout-yes" id="logoutYes">
                            Yes
                        </button>

                    </div>

                </div>

            </div>

        </div>
    </div>

    <!-- SCRIPT FOR DROPDOWNS AND INTERACTIVE FORM ACTIONS -->
    <script>
        // Header Dropdown functionality
        const notifBtn = document.getElementById('notifBtn');
        const notifDropdown = document.getElementById('notifDropdown');
        const alertsBtn = document.getElementById('alertsBtn');
        const alertsDropdown = document.getElementById('alertsDropdown');
        const profileBtn = document.getElementById('profileBtn');
        const profileDropdown = document.getElementById('profileDropdown');

        function closeHeaderDropdowns(except) {
            if (notifDropdown && except !== notifDropdown) notifDropdown.classList.remove('open');
            if (alertsDropdown && except !== alertsDropdown) alertsDropdown.classList.remove('open');
            if (profileDropdown && except !== profileDropdown) profileDropdown.classList.remove('open');
        }

        if (notifBtn && notifDropdown) {
            notifBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                const shouldOpen = !notifDropdown.classList.contains('open');
                closeHeaderDropdowns(notifDropdown);
                if (shouldOpen) notifDropdown.classList.add('open');
            });
        }

        if (alertsBtn && alertsDropdown) {
            alertsBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                const shouldOpen = !alertsDropdown.classList.contains('open');
                closeHeaderDropdowns(alertsDropdown);
                if (shouldOpen) alertsDropdown.classList.add('open');
            });
        }

        if (profileBtn && profileDropdown) {
            profileBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                const shouldOpen = !profileDropdown.classList.contains('open');
                closeHeaderDropdowns(profileDropdown);
                if (shouldOpen) profileDropdown.classList.add('open');
            });
        }

        window.addEventListener('click', function (e) {
            if (!e.target.closest('.notif-wrap') &&
                !e.target.closest('.alert-wrap') &&
                !e.target.closest('.profile-wrap')) {
                closeHeaderDropdowns(null);
            }
        });

        const markAllRead = document.getElementById('markAllRead');
        if (markAllRead) {
            markAllRead.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelectorAll('#notifList .unread').forEach(item => {
                    item.classList.remove('unread');
                });
                const badge = document.getElementById('notifBadge');
                if (badge) badge.textContent = '0';
            });
        }

        const markAllAlertsRead = document.getElementById('markAllAlertsRead');
        if (markAllAlertsRead) {
            markAllAlertsRead.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelectorAll('#alertsDropdown .unread').forEach(item => {
                    item.classList.remove('unread');
                });
                const badge = document.querySelector('#alertsBtn .header-badge');
                if (badge) badge.textContent = '0';
            });
        }

        const logoutBtn = document.getElementById("logoutBtn");
        const logoutModal = document.getElementById("logoutModal");
        const logoutNo = document.getElementById("logoutNo");
        const logoutYes = document.getElementById("logoutYes");
        const logoutClose = document.getElementById("logoutClose");

        // Helper function to close the logout modal and clear 'active' from logoutBtn
        function closeLogoutModal() {
            if (logoutModal) {
                logoutModal.classList.remove("show");
                logoutModal.blur();
            }
            if (logoutBtn) {
                logoutBtn.classList.remove("active");
            }
        }

        if (logoutBtn) {
            logoutBtn.addEventListener("click", function (e) {
                e.preventDefault();
                // Optionally toggle visual highlight only while modal is active:
                // logoutBtn.classList.add("active");
                if (logoutModal) {
                    logoutModal.classList.add("show");
                }
            });
        }

        if (logoutNo) {
            logoutNo.addEventListener("click", function () {
                closeLogoutModal();
            });
        }

        if (logoutClose) {
            logoutClose.addEventListener("click", function () {
                closeLogoutModal();
            });
        }

        if (logoutYes) {
            logoutYes.addEventListener("click", function () {
                window.location.href = "adminlogin.html";
            });
        }

        /* Close when clicking outside the popup modal */
        if (logoutModal) {
            logoutModal.addEventListener("click", function (e) {
                if (e.target === logoutModal) {
                    closeLogoutModal();
                }
            });
        }

        // Image Preview Handler
        const imageInput = document.getElementById('imageInput');
        const profileImagePreview = document.getElementById('profileImagePreview');

        imageInput.addEventListener('change', function (e) {
            const file = e.target.files[0];
            if (file) {
                if (file.size > 5 * 1024 * 1024) {
                    alert('File size exceeds 5MB limit.');
                    return;
                }
                const reader = new FileReader();
                reader.onload = function (event) {
                    profileImagePreview.src = event.target.result;
                    profileImagePreview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        });

        // Toggle Edit/Save Admin Profile
        const editProfileBtn = document.getElementById('editProfileBtn');
        const nameText = document.getElementById('nameText');
        const emailText = document.getElementById('emailText');
        const nameInput = document.getElementById('nameInput');
        const emailInput = document.getElementById('emailInput');
        let isEditingProfile = false;

        fetch('admin_data_api.php?action=settings_get')
            .then(response => response.json())
            .then(result => {
                if (!result.success) return;
                nameText.innerText = result.settings.full_name;
                emailText.innerText = result.settings.email;
                nameInput.value = result.settings.full_name;
                emailInput.value = result.settings.email;
                if (result.settings.avatar) profileImagePreview.src = result.settings.avatar;
            });

        editProfileBtn.addEventListener('click', function () {
            isEditingProfile = !isEditingProfile;
            if (isEditingProfile) {
                editProfileBtn.innerText = 'Save Profile';
                nameText.style.display = 'none';
                emailText.style.display = 'none';
                nameInput.style.display = 'block';
                emailInput.style.display = 'block';
            } else {
                if (!nameInput.value.trim() || !emailInput.value.trim()) {
                    alert('Fields cannot be left blank.');
                    isEditingProfile = true;
                    return;
                }
                fetch('admin_data_api.php?action=settings_profile', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ full_name: nameInput.value, email: emailInput.value })
                })
                    .then(response => response.json())
                    .then(result => {
                        if (!result.success) throw new Error(result.error || 'Profile update failed');
                        nameText.innerText = nameInput.value;
                        emailText.innerText = emailInput.value;
                        alert('Profile details updated successfully!');
                    })
                    .catch(error => {
                        alert(error.message);
                        isEditingProfile = true;
                    });

                nameText.style.display = 'block';
                emailText.style.display = 'block';
                nameInput.style.display = 'none';
                emailInput.style.display = 'none';
                editProfileBtn.innerText = 'Edit Profile';
            }
        });

        // Change Password Handler
        const changePasswordBtn = document.getElementById('changePasswordBtn');
        const currentPassword = document.getElementById('currentPassword');
        const newPassword = document.getElementById('newPassword');
        const confirmPassword = document.getElementById('confirmPassword');

        changePasswordBtn.addEventListener('click', function () {
            const currVal = currentPassword.value.trim();
            const newVal = newPassword.value.trim();
            const confirmVal = confirmPassword.value.trim();

            if (!currVal || !newVal || !confirmVal) {
                alert('Please fill out all password fields.');
                return;
            }

            if (newVal !== confirmVal) {
                alert('New passwords do not match!');
                return;
            }

            fetch('admin_data_api.php?action=settings_password', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    current_password: currVal,
                    new_password: newVal
                })
            })
                .then(response => response.json())
                .then(result => {
                    if (!result.success) throw new Error(result.error || 'Password update failed');
                    alert('Password changed successfully!');
                    currentPassword.value = '';
                    newPassword.value = '';
                    confirmPassword.value = '';
                })
                .catch(error => alert(error.message));
        });
    </script>
    <script src="admin-js/admin-responsive.js"></script>
</body>

</html>