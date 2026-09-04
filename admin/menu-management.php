<?php require_once __DIR__ . '/admin_guard.php'; ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="admin-css/menu-management.css">
    <link rel="stylesheet" href="admin-css/admin-responsive.css">
    <link rel="icon" href="../img/LOGO 2.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Afacad:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Gaegu:wght@400;700&display=swap" rel="stylesheet">
    <title>BoyCold - Menu Management</title>
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
                            <a href="menu-management.php" class="active">
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
                        <li>
                            <a href="feedback.php">
                                <span class="nav-icon"><i class="fa-solid fa-star"></i></span>
                                <span class="nav-label">Feedback &amp; Reviews</span>
                                <i class="fa-solid fa-chevron-right nav-chevron"></i>
                            </a>
                        </li>
                    </ul>

                    <div class="sidebar-divider"></div>

                    <ul>
                        <li>
                            <a href="adminsettings.php">
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
                <button class="profile-btn" aria-label="Admin profile">
                    <div class="profile-avatar">
                        <svg width="36" height="36" viewBox="0 0 36 36" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M8.75737 25.5989C10.0298 24.6259 11.452 23.8589 13.0238 23.298C14.5957 22.7372 16.2424 22.4562 17.964 22.4552C19.6855 22.4542 21.3322 22.7352 22.9041 23.298C24.4759 23.8609 25.8981 24.6279 27.1705 25.5989C28.0438 24.576 28.7239 23.4158 29.211 22.1184C29.698 20.821 29.941 19.4363 29.94 17.9642C29.94 14.6458 28.7738 11.82 26.4415 9.48666C24.1092 7.15332 21.2833 5.98715 17.964 5.98815C14.6446 5.98915 11.8187 7.15582 9.48641 9.48815C7.15408 11.8205 5.98791 14.6458 5.98791 17.9642C5.98791 19.4363 6.23142 20.821 6.71845 22.1184C7.20547 23.4158 7.88512 24.576 8.75737 25.5989ZM14.2409 17.9447C13.2299 16.9358 12.7244 15.6947 12.7244 14.2217C12.7244 12.7486 13.2299 11.5071 14.2409 10.4971C15.2519 9.48715 16.4929 8.98216 17.964 8.98216C19.435 8.98216 20.6765 9.48765 21.6885 10.4986C22.7005 11.5096 23.2055 12.7506 23.2035 14.2217C23.2015 15.6927 22.6965 16.9343 21.6885 17.9462C20.6805 18.9582 19.439 19.4632 17.964 19.4612C16.4889 19.4592 15.2474 18.9542 14.2394 17.9462M17.964 32.9343C15.8931 32.9343 13.947 32.541 12.1256 31.7546C10.3043 30.9682 8.71995 29.9018 7.37264 28.5555C6.02534 27.2092 4.95897 25.6249 4.17354 23.8025C3.38811 21.9802 2.9949 20.0341 2.9939 17.9642C2.9929 15.8943 3.38611 13.9482 4.17354 12.1259C4.96096 10.3035 6.02733 8.71919 7.37264 7.37288C8.71795 6.02658 10.3023 4.96021 12.1256 4.17378C13.949 3.38735 15.8951 2.99414 17.964 2.99414C20.0328 2.99414 21.9789 3.38735 23.8023 4.17378C25.6256 4.96021 27.21 6.02658 28.5553 7.37288C29.9006 8.71919 30.9675 10.3035 31.7559 12.1259C32.5443 13.9482 32.937 15.8943 32.934 17.9642C32.931 20.0341 32.5378 21.9802 31.7544 23.8025C30.9709 25.6249 29.9046 27.2092 28.5553 28.5555C27.206 29.9018 25.6216 30.9687 23.8023 31.7561C21.9829 32.5435 20.0368 32.9363 17.964 32.9343Z" fill="black"/>
                        </svg>
                    </div>
                    <div class="profile-info">
                        <span class="profile-role">Admin</span>
                    </div>
                </button>
            </div>
            <div class="menu-management-content">

                <!-- Header Row: Title & Action Button -->
                <div class="menu-header-row">
                    <div class="menu-heading">
                        <h1 class="menu-title">Menu Management</h1>
                        <p class="menu-subtitle">Manage your menu items, prices and availability.</p>
                    </div>
                    <button type="button" class="add-item-btn" id="addNewItemBtn">
                        <i class="fa-solid fa-plus"></i> Add New Item
                    </button>
                </div>

                <!-- Category Bar (POS-like pill navigation) -->
                <nav class="category-bar" aria-label="Menu categories" id="categoryBar">
                    <div class="cat-pills-wrap" id="catPillsWrap">
                        <a href="#" data-filter="coffee" class="cat-pill active">Coffee<span class="cat-delete-btn"
                                aria-label="Delete category">&times;</span></a>
                        <a href="#" data-filter="non-coffee" class="cat-pill">Non-Coffee<span class="cat-delete-btn"
                                aria-label="Delete category">&times;</span></a>
                        <a href="#" data-filter="matcha-fusion" class="cat-pill">Matcha Fusion<span
                                class="cat-delete-btn" aria-label="Delete category">&times;</span></a>
                        <a href="#" data-filter="smoothie" class="cat-pill">Smoothie<span class="cat-delete-btn"
                                aria-label="Delete category">&times;</span></a>
                        <a href="#" data-filter="frappe-series" class="cat-pill">Frappe Series<span
                                class="cat-delete-btn" aria-label="Delete category">&times;</span></a>
                        <a href="#" data-filter="rice-meal" class="cat-pill">Rice Meal<span class="cat-delete-btn"
                                aria-label="Delete category">&times;</span></a>
                        <a href="#" data-filter="light-snack" class="cat-pill">Light Snack<span class="cat-delete-btn"
                                aria-label="Delete category">&times;</span></a>
                        <a href="#" data-filter="pasta" class="cat-pill">Pasta<span class="cat-delete-btn"
                                aria-label="Delete category">&times;</span></a>
                        <a href="#" data-filter="waffle" class="cat-pill">Waffles<span class="cat-delete-btn"
                                aria-label="Delete category">&times;</span></a>
                        <a href="#" data-filter="quesadilla" class="cat-pill">Quesadilla<span class="cat-delete-btn"
                                aria-label="Delete category">&times;</span></a>

                        <!-- Ghost pill: click to type a new category name (only shown while editing) -->
                        <a href="#" class="cat-pill cat-add-ghost" id="addCategoryGhost" style="display:none;">
                            <i class="fa-solid fa-plus"></i> New Category
                        </a>
                        <button class="cat-add" id="addCategoryBtn" aria-label="Edit categories">
                            <i class="fa-solid fa-plus"></i>
                        </button>

                        <div class="cat-edit-actions" id="catEditActions" style="display:none;">
                            <button type="button" class="cat-save-btn" id="catSaveBtn"><i class="fa-solid fa-check"></i>
                                Save</button>
                            <button type="button" class="cat-cancel-btn" id="catCancelBtn"><i
                                    class="fa-solid fa-xmark"></i> Cancel</button>
                        </div>
                    </div>
                </nav>

                <div class="menu-divider"></div>

                <!-- Search Toolbar + View Toggle -->
                <div class="menu-toolbar">
                    <div class="menu-search-box">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="text" id="menuSearch" placeholder="Search menu item...">
                    </div>
                    <button class="view-toggle-btn" id="viewToggle" aria-label="Toggle View Mode">
                        <i class="fa-solid fa-bars"></i>
                    </button>
                </div>

                <!-- Products Section (inside white container card) -->
                <div class="menu-container-card">
                    <section class="menu-section">
                        <div class="menu-content">

                            <!-- List View Header (Visible when List View is active) -->
                            <div class="product-list-header" id="productListHeader">
                                <span>Item</span>
                                <span>Name</span>
                                <span>Price</span>
                                <span>Servings</span>
                                <span>Cups</span>
                                <span>Status</span>
                                <span>Action</span>
                            </div>

                            <!-- Product Grid / Cards Container -->
                            <div class="product-grid" id="productGrid">

                                <!-- ================= COFFEE ================= -->
                                <div class="product-card" data-category="coffee" data-id="americano">
                                    <div class="card-actions">
                                        <button type="button" class="grid-action-btn" aria-label="More actions"
                                            title="More actions">
                                            <i class="fa-solid fa-ellipsis-vertical"></i>
                                        </button>
                                        <div class="grid-delete-menu">
                                            <button type="button" class="grid-delete-option btn-delete"
                                                aria-label="Delete product">
                                                <i class="fa-solid fa-trash"></i> Delete
                                            </button>
                                        </div>
                                        <button type="button" class="grid-edit-btn btn-edit" aria-label="Edit product"
                                            title="Edit">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <div class="list-action-buttons">
                                            <button type="button" class="list-action-btn btn-edit"
                                                aria-label="Edit product" title="Edit">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>
                                            <button type="button" class="list-action-btn btn-delete"
                                                aria-label="Delete product" title="Delete">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-image">
                                        <div class="card-image-placeholder">
                                            <img src="../../../img/Americano.png" alt="Americano">
                                        </div>
                                    </div>
                                    <div class="card-info">
                                        <div class="card-mid">
                                            <p class="card-name">Americano</p>
                                        </div>
                                        <div class="card-footer">
                                            <p class="card-price">₱89.00</p>
                                            <div class="drink-stock">
                                                <p class="drink-status available">
                                                    <span class="status-dot"></span> Available
                                                </p>
                                                <p class="drink-ingredient">
                                                    Ingredients: <span>Sufficient</span>
                                                </p>
                                                <p class="drink-cups">
                                                    Cups: <span class="cups-value">40 pcs</span>
                                                </p>
                                                <p class="drink-servings">
                                                    <span class="servings-value">25</span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="product-card" data-category="coffee" data-id="cafe-latte">
                                    <div class="card-actions">
                                        <button type="button" class="grid-action-btn" aria-label="More actions"
                                            title="More actions">
                                            <i class="fa-solid fa-ellipsis-vertical"></i>
                                        </button>
                                        <div class="grid-delete-menu">
                                            <button type="button" class="grid-delete-option btn-delete"
                                                aria-label="Delete product">
                                                <i class="fa-solid fa-trash"></i> Delete
                                            </button>
                                        </div>
                                        <button type="button" class="grid-edit-btn btn-edit" aria-label="Edit product"
                                            title="Edit">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <div class="list-action-buttons">
                                            <button type="button" class="list-action-btn btn-edit"
                                                aria-label="Edit product" title="Edit">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>
                                            <button type="button" class="list-action-btn btn-delete"
                                                aria-label="Delete product" title="Delete">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-image">
                                        <div class="card-image-placeholder">
                                            <img src="../../../img/Cafe Latte.png" alt="Cafe Latte">
                                        </div>
                                    </div>
                                    <div class="card-info">
                                        <div class="card-mid">
                                            <p class="card-name">Cafe Latte</p>
                                        </div>
                                        <div class="card-footer">
                                            <p class="card-price">₱109.00</p>
                                            <div class="drink-stock">
                                                <p class="drink-status available">
                                                    <span class="status-dot"></span> Available
                                                </p>
                                                <p class="drink-ingredient">
                                                    Ingredients: <span>Sufficient</span>
                                                </p>
                                                <p class="drink-cups">
                                                    Cups: <span class="cups-value">40 pcs</span>
                                                </p>
                                                <p class="drink-servings">
                                                    <span class="servings-value">30</span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="product-card" data-category="coffee" data-id="spanish-latte">
                                    <div class="card-actions">
                                        <button type="button" class="grid-action-btn" aria-label="More actions"
                                            title="More actions">
                                            <i class="fa-solid fa-ellipsis-vertical"></i>
                                        </button>
                                        <div class="grid-delete-menu">
                                            <button type="button" class="grid-delete-option btn-delete"
                                                aria-label="Delete product">
                                                <i class="fa-solid fa-trash"></i> Delete
                                            </button>
                                        </div>
                                        <button type="button" class="grid-edit-btn btn-edit" aria-label="Edit product"
                                            title="Edit">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <div class="list-action-buttons">
                                            <button type="button" class="list-action-btn btn-edit"
                                                aria-label="Edit product" title="Edit">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>
                                            <button type="button" class="list-action-btn btn-delete"
                                                aria-label="Delete product" title="Delete">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-image">
                                        <div class="card-image-placeholder">
                                            <img src="../../../img/Spanish Latte.png" alt="Spanish Latte">
                                        </div>
                                    </div>
                                    <div class="card-info">
                                        <div class="card-mid">
                                            <p class="card-name">Spanish Latte</p>
                                        </div>
                                        <div class="card-footer">
                                            <p class="card-price">₱129.00</p>
                                            <div class="drink-stock">
                                                <p class="drink-status available">
                                                    <span class="status-dot"></span> Available
                                                </p>
                                                <p class="drink-ingredient">
                                                    Ingredients: <span>Sufficient</span>
                                                </p>
                                                <p class="drink-cups">
                                                    Cups: <span class="cups-value">40 pcs</span>
                                                </p>
                                                <p class="drink-servings">
                                                    <span class="servings-value">20</span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="product-card" data-category="coffee" data-id="sea-salt-latte">
                                    <div class="card-actions">
                                        <button type="button" class="grid-action-btn" aria-label="More actions"
                                            title="More actions">
                                            <i class="fa-solid fa-ellipsis-vertical"></i>
                                        </button>
                                        <div class="grid-delete-menu">
                                            <button type="button" class="grid-delete-option btn-delete"
                                                aria-label="Delete product">
                                                <i class="fa-solid fa-trash"></i> Delete
                                            </button>
                                        </div>
                                        <button type="button" class="grid-edit-btn btn-edit" aria-label="Edit product"
                                            title="Edit">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <div class="list-action-buttons">
                                            <button type="button" class="list-action-btn btn-edit"
                                                aria-label="Edit product" title="Edit">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>
                                            <button type="button" class="list-action-btn btn-delete"
                                                aria-label="Delete product" title="Delete">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-image">
                                        <div class="card-image-placeholder">
                                            <img src="../../../img/Sea salt Latte.png" alt="Sea Salt Latte">
                                        </div>
                                    </div>
                                    <div class="card-info">
                                        <div class="card-mid">
                                            <p class="card-name">Sea Salt Latte</p>
                                        </div>
                                        <div class="card-footer">
                                            <p class="card-price">₱149.00</p>
                                            <div class="drink-stock">
                                                <p class="drink-status available">
                                                    <span class="status-dot"></span> Available
                                                </p>
                                                <p class="drink-ingredient">
                                                    Ingredients: <span>Sufficient</span>
                                                </p>
                                                <p class="drink-cups">
                                                    Cups: <span class="cups-value">40 pcs</span>
                                                </p>
                                                <p class="drink-servings">
                                                    <span class="servings-value">22</span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="product-card" data-category="coffee" data-id="french-vanilla">
                                    <div class="card-actions">
                                        <button type="button" class="grid-action-btn" aria-label="More actions"
                                            title="More actions">
                                            <i class="fa-solid fa-ellipsis-vertical"></i>
                                        </button>
                                        <div class="grid-delete-menu">
                                            <button type="button" class="grid-delete-option btn-delete"
                                                aria-label="Delete product">
                                                <i class="fa-solid fa-trash"></i> Delete
                                            </button>
                                        </div>
                                        <button type="button" class="grid-edit-btn btn-edit" aria-label="Edit product"
                                            title="Edit">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <div class="list-action-buttons">
                                            <button type="button" class="list-action-btn btn-edit"
                                                aria-label="Edit product" title="Edit">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>
                                            <button type="button" class="list-action-btn btn-delete"
                                                aria-label="Delete product" title="Delete">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-image">
                                        <div class="card-image-placeholder">
                                            <img src="../../../img/Franch Vanilla.png" alt="French Vanilla">
                                        </div>
                                    </div>
                                    <div class="card-info">
                                        <div class="card-mid">
                                            <p class="card-name">French Vanilla</p>
                                        </div>
                                        <div class="card-footer">
                                            <p class="card-price">₱149.00</p>
                                            <div class="drink-stock">
                                                <p class="drink-status available">
                                                    <span class="status-dot"></span> Available
                                                </p>
                                                <p class="drink-ingredient">
                                                    Ingredients: <span>Sufficient</span>
                                                </p>
                                                <p class="drink-cups">
                                                    Cups: <span class="cups-value">40 pcs</span>
                                                </p>
                                                <p class="drink-servings">
                                                    <span class="servings-value">18</span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="product-card" data-category="coffee" data-id="white-mocha">
                                    <div class="card-actions">
                                        <button type="button" class="grid-action-btn" aria-label="More actions"
                                            title="More actions">
                                            <i class="fa-solid fa-ellipsis-vertical"></i>
                                        </button>
                                        <div class="grid-delete-menu">
                                            <button type="button" class="grid-delete-option btn-delete"
                                                aria-label="Delete product">
                                                <i class="fa-solid fa-trash"></i> Delete
                                            </button>
                                        </div>
                                        <button type="button" class="grid-edit-btn btn-edit" aria-label="Edit product"
                                            title="Edit">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <div class="list-action-buttons">
                                            <button type="button" class="list-action-btn btn-edit"
                                                aria-label="Edit product" title="Edit">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>
                                            <button type="button" class="list-action-btn btn-delete"
                                                aria-label="Delete product" title="Delete">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-image">
                                        <div class="card-image-placeholder">
                                            <img src="../../../img/White Mocha.png" alt="White Mocha">
                                        </div>
                                    </div>
                                    <div class="card-info">
                                        <div class="card-mid">
                                            <p class="card-name">White Mocha</p>
                                        </div>
                                        <div class="card-footer">
                                            <p class="card-price">₱129.00</p>
                                            <div class="drink-stock">
                                                <p class="drink-status available">
                                                    <span class="status-dot"></span> Available
                                                </p>
                                                <p class="drink-ingredient">
                                                    Ingredients: <span>Sufficient</span>
                                                </p>
                                                <p class="drink-cups">
                                                    Cups: <span class="cups-value">40 pcs</span>
                                                </p>
                                                <p class="drink-servings">
                                                    <span class="servings-value">15</span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="product-card" data-category="coffee" data-id="mont-blanc">
                                    <div class="card-actions">
                                        <button type="button" class="grid-action-btn" aria-label="More actions"
                                            title="More actions">
                                            <i class="fa-solid fa-ellipsis-vertical"></i>
                                        </button>
                                        <div class="grid-delete-menu">
                                            <button type="button" class="grid-delete-option btn-delete"
                                                aria-label="Delete product">
                                                <i class="fa-solid fa-trash"></i> Delete
                                            </button>
                                        </div>
                                        <button type="button" class="grid-edit-btn btn-edit" aria-label="Edit product"
                                            title="Edit">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <div class="list-action-buttons">
                                            <button type="button" class="list-action-btn btn-edit"
                                                aria-label="Edit product" title="Edit">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>
                                            <button type="button" class="list-action-btn btn-delete"
                                                aria-label="Delete product" title="Delete">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-image">
                                        <div class="card-image-placeholder">
                                            <img src="../../../img/Mont Blanc.png" alt="Mont Blanc">
                                        </div>
                                    </div>
                                    <div class="card-info">
                                        <div class="card-mid">
                                            <p class="card-name">Mont Blanc</p>
                                        </div>
                                        <div class="card-footer">
                                            <p class="card-price">₱179.00</p>
                                            <div class="drink-stock">
                                                <p class="drink-status available">
                                                    <span class="status-dot"></span> Available
                                                </p>
                                                <p class="drink-ingredient">
                                                    Ingredients: <span>Sufficient</span>
                                                </p>
                                                <p class="drink-cups">
                                                    Cups: <span class="cups-value">40 pcs</span>
                                                </p>
                                                <p class="drink-servings">
                                                    <span class="servings-value">20</span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="product-card" data-category="coffee" data-id="horchata">
                                    <div class="card-actions">
                                        <button type="button" class="grid-action-btn" aria-label="More actions"
                                            title="More actions">
                                            <i class="fa-solid fa-ellipsis-vertical"></i>
                                        </button>
                                        <div class="grid-delete-menu">
                                            <button type="button" class="grid-delete-option btn-delete"
                                                aria-label="Delete product">
                                                <i class="fa-solid fa-trash"></i> Delete
                                            </button>
                                        </div>
                                        <button type="button" class="grid-edit-btn btn-edit" aria-label="Edit product"
                                            title="Edit">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <div class="list-action-buttons">
                                            <button type="button" class="list-action-btn btn-edit"
                                                aria-label="Edit product" title="Edit">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>
                                            <button type="button" class="list-action-btn btn-delete"
                                                aria-label="Delete product" title="Delete">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-image">
                                        <div class="card-image-placeholder">
                                            <img src="../../../img/Coffee Horchata.png" alt="Horchata">
                                        </div>
                                    </div>
                                    <div class="card-info">
                                        <div class="card-mid">
                                            <p class="card-name">Horchata</p>
                                        </div>
                                        <div class="card-footer">
                                            <p class="card-price">₱189.00</p>
                                            <div class="drink-stock">
                                                <p class="drink-status available">
                                                    <span class="status-dot"></span> Available
                                                </p>
                                                <p class="drink-ingredient">
                                                    Ingredients: <span>Sufficient</span>
                                                </p>
                                                <p class="drink-cups">
                                                    Cups: <span class="cups-value">40 pcs</span>
                                                </p>
                                                <p class="drink-servings">
                                                    <span class="servings-value">18</span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="product-card" data-category="coffee" data-id="caramel-macchiato">
                                    <div class="card-actions">
                                        <button type="button" class="grid-action-btn" aria-label="More actions"
                                            title="More actions">
                                            <i class="fa-solid fa-ellipsis-vertical"></i>
                                        </button>
                                        <div class="grid-delete-menu">
                                            <button type="button" class="grid-delete-option btn-delete"
                                                aria-label="Delete product">
                                                <i class="fa-solid fa-trash"></i> Delete
                                            </button>
                                        </div>
                                        <button type="button" class="grid-edit-btn btn-edit" aria-label="Edit product"
                                            title="Edit">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <div class="list-action-buttons">
                                            <button type="button" class="list-action-btn btn-edit"
                                                aria-label="Edit product" title="Edit">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>
                                            <button type="button" class="list-action-btn btn-delete"
                                                aria-label="Delete product" title="Delete">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-image">
                                        <div class="card-image-placeholder">
                                            <img src="../../../img/Caramel Macchiato.png" alt="Caramel Macchiato">
                                        </div>
                                    </div>
                                    <div class="card-info">
                                        <div class="card-mid">
                                            <p class="card-name">Caramel Macchiato</p>
                                        </div>
                                        <div class="card-footer">
                                            <p class="card-price">₱159.00</p>
                                            <div class="drink-stock">
                                                <p class="drink-status available">
                                                    <span class="status-dot"></span> Available
                                                </p>
                                                <p class="drink-ingredient">
                                                    Ingredients: <span>Sufficient</span>
                                                </p>
                                                <p class="drink-cups">
                                                    Cups: <span class="cups-value">40 pcs</span>
                                                </p>
                                                <p class="drink-servings">
                                                    <span class="servings-value">15</span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="product-card" data-category="coffee" data-id="salted-caramel">
                                    <div class="card-actions">
                                        <button type="button" class="grid-action-btn" aria-label="More actions"
                                            title="More actions">
                                            <i class="fa-solid fa-ellipsis-vertical"></i>
                                        </button>
                                        <div class="grid-delete-menu">
                                            <button type="button" class="grid-delete-option btn-delete"
                                                aria-label="Delete product">
                                                <i class="fa-solid fa-trash"></i> Delete
                                            </button>
                                        </div>
                                        <button type="button" class="grid-edit-btn btn-edit" aria-label="Edit product"
                                            title="Edit">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <div class="list-action-buttons">
                                            <button type="button" class="list-action-btn btn-edit"
                                                aria-label="Edit product" title="Edit">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>
                                            <button type="button" class="list-action-btn btn-delete"
                                                aria-label="Delete product" title="Delete">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-image">
                                        <div class="card-image-placeholder">
                                            <img src="../../../img/Salted Caramel.png" alt="Salted Caramel">
                                        </div>
                                    </div>
                                    <div class="card-info">
                                        <div class="card-mid">
                                            <p class="card-name">Salted Caramel</p>
                                        </div>
                                        <div class="card-footer">
                                            <p class="card-price">₱159.00</p>
                                            <div class="drink-stock">
                                                <p class="drink-status available">
                                                    <span class="status-dot"></span> Available
                                                </p>
                                                <p class="drink-ingredient">
                                                    Ingredients: <span>Sufficient</span>
                                                </p>
                                                <p class="drink-cups">
                                                    Cups: <span class="cups-value">40 pcs</span>
                                                </p>
                                                <p class="drink-servings">
                                                    <span class="servings-value">18</span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="product-card" data-category="coffee" data-id="einspanner-latte">
                                    <div class="card-actions">
                                        <button type="button" class="grid-action-btn" aria-label="More actions"
                                            title="More actions">
                                            <i class="fa-solid fa-ellipsis-vertical"></i>
                                        </button>
                                        <div class="grid-delete-menu">
                                            <button type="button" class="grid-delete-option btn-delete"
                                                aria-label="Delete product">
                                                <i class="fa-solid fa-trash"></i> Delete
                                            </button>
                                        </div>
                                        <button type="button" class="grid-edit-btn btn-edit" aria-label="Edit product"
                                            title="Edit">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <div class="list-action-buttons">
                                            <button type="button" class="list-action-btn btn-edit"
                                                aria-label="Edit product" title="Edit">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>
                                            <button type="button" class="list-action-btn btn-delete"
                                                aria-label="Delete product" title="Delete">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-image">
                                        <div class="card-image-placeholder">
                                            <img src="../../../img/SC-Einspanner Latte _ 149 1.png"
                                                alt="Einspanner Latte">
                                        </div>
                                    </div>
                                    <div class="card-info">
                                        <div class="card-mid">
                                            <p class="card-name">Einspanner Latte</p>
                                        </div>
                                        <div class="card-footer">
                                            <p class="card-price">₱169.00</p>
                                            <div class="drink-stock">
                                                <p class="drink-status available">
                                                    <span class="status-dot"></span> Available
                                                </p>
                                                <p class="drink-ingredient">
                                                    Ingredients: <span>Sufficient</span>
                                                </p>
                                                <p class="drink-cups">
                                                    Cups: <span class="cups-value">40 pcs</span>
                                                </p>
                                                <p class="drink-servings">
                                                    <span class="servings-value">15</span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="product-card" data-category="coffee" data-id="ocean-mist">
                                    <div class="card-actions">
                                        <button type="button" class="grid-action-btn" aria-label="More actions"
                                            title="More actions">
                                            <i class="fa-solid fa-ellipsis-vertical"></i>
                                        </button>
                                        <div class="grid-delete-menu">
                                            <button type="button" class="grid-delete-option btn-delete"
                                                aria-label="Delete product">
                                                <i class="fa-solid fa-trash"></i> Delete
                                            </button>
                                        </div>
                                        <button type="button" class="grid-edit-btn btn-edit" aria-label="Edit product"
                                            title="Edit">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <div class="list-action-buttons">
                                            <button type="button" class="list-action-btn btn-edit"
                                                aria-label="Edit product" title="Edit">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>
                                            <button type="button" class="list-action-btn btn-delete"
                                                aria-label="Delete product" title="Delete">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-image">
                                        <div class="card-image-placeholder">
                                            <img src="../../../img/Ocean mist.png" alt="Ocean Mist">
                                        </div>
                                    </div>
                                    <div class="card-info">
                                        <div class="card-mid">
                                            <p class="card-name">Ocean Mist</p>
                                        </div>
                                        <div class="card-footer">
                                            <p class="card-price">₱189.00</p>
                                            <div class="drink-stock">
                                                <p class="drink-status available">
                                                    <span class="status-dot"></span> Available
                                                </p>
                                                <p class="drink-ingredient">
                                                    Ingredients: <span>Sufficient</span>
                                                </p>
                                                <p class="drink-cups">
                                                    Cups: <span class="cups-value">40 pcs</span>
                                                </p>
                                                <p class="drink-servings">
                                                    <span class="servings-value">12</span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="product-card" data-category="coffee" data-id="cheesecake-latte">
                                    <div class="card-actions">
                                        <button type="button" class="grid-action-btn" aria-label="More actions"
                                            title="More actions">
                                            <i class="fa-solid fa-ellipsis-vertical"></i>
                                        </button>
                                        <div class="grid-delete-menu">
                                            <button type="button" class="grid-delete-option btn-delete"
                                                aria-label="Delete product">
                                                <i class="fa-solid fa-trash"></i> Delete
                                            </button>
                                        </div>
                                        <button type="button" class="grid-edit-btn btn-edit" aria-label="Edit product"
                                            title="Edit">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <div class="list-action-buttons">
                                            <button type="button" class="list-action-btn btn-edit"
                                                aria-label="Edit product" title="Edit">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>
                                            <button type="button" class="list-action-btn btn-delete"
                                                aria-label="Delete product" title="Delete">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-image">
                                        <div class="card-image-placeholder">
                                            <img src="../../../img/Cheesecake Latte.png" alt="Cheesecake Latte">
                                        </div>
                                    </div>
                                    <div class="card-info">
                                        <div class="card-mid">
                                            <p class="card-name">Cheesecake Latte</p>
                                        </div>
                                        <div class="card-footer">
                                            <p class="card-price">₱179.00</p>
                                            <div class="drink-stock">
                                                <p class="drink-status available">
                                                    <span class="status-dot"></span> Available
                                                </p>
                                                <p class="drink-ingredient">
                                                    Ingredients: <span>Sufficient</span>
                                                </p>
                                                <p class="drink-cups">
                                                    Cups: <span class="cups-value">40 pcs</span>
                                                </p>
                                                <p class="drink-servings">
                                                    <span class="servings-value">15</span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="product-card" data-category="coffee" data-id="creme-brulee">
                                    <div class="card-actions">
                                        <button type="button" class="grid-action-btn" aria-label="More actions"
                                            title="More actions">
                                            <i class="fa-solid fa-ellipsis-vertical"></i>
                                        </button>
                                        <div class="grid-delete-menu">
                                            <button type="button" class="grid-delete-option btn-delete"
                                                aria-label="Delete product">
                                                <i class="fa-solid fa-trash"></i> Delete
                                            </button>
                                        </div>
                                        <button type="button" class="grid-edit-btn btn-edit" aria-label="Edit product"
                                            title="Edit">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <div class="list-action-buttons">
                                            <button type="button" class="list-action-btn btn-edit"
                                                aria-label="Edit product" title="Edit">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>
                                            <button type="button" class="list-action-btn btn-delete"
                                                aria-label="Delete product" title="Delete">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-image">
                                        <div class="card-image-placeholder">
                                            <img src="../../../img/ChatGPT Image Aug 29, 2026, 12_20_12 AM.png"
                                                alt="Creme Brulee">
                                        </div>
                                    </div>
                                    <div class="card-info">
                                        <div class="card-mid">
                                            <p class="card-name">Creme Brulee</p>
                                        </div>
                                        <div class="card-footer">
                                            <p class="card-price">₱199.00</p>
                                            <div class="drink-stock">
                                                <p class="drink-status available">
                                                    <span class="status-dot"></span> Available
                                                </p>
                                                <p class="drink-ingredient">
                                                    Ingredients: <span>Sufficient</span>
                                                </p>
                                                <p class="drink-cups">
                                                    Cups: <span class="cups-value">40 pcs</span>
                                                </p>
                                                <p class="drink-servings">
                                                    <span class="servings-value">12</span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="product-card" data-category="coffee" data-id="dark-mocha">
                                    <div class="card-actions">
                                        <button type="button" class="grid-action-btn" aria-label="More actions"
                                            title="More actions">
                                            <i class="fa-solid fa-ellipsis-vertical"></i>
                                        </button>
                                        <div class="grid-delete-menu">
                                            <button type="button" class="grid-delete-option btn-delete"
                                                aria-label="Delete product">
                                                <i class="fa-solid fa-trash"></i> Delete
                                            </button>
                                        </div>
                                        <button type="button" class="grid-edit-btn btn-edit" aria-label="Edit product"
                                            title="Edit">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <div class="list-action-buttons">
                                            <button type="button" class="list-action-btn btn-edit"
                                                aria-label="Edit product" title="Edit">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>
                                            <button type="button" class="list-action-btn btn-delete"
                                                aria-label="Delete product" title="Delete">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-image">
                                        <div class="card-image-placeholder">
                                            <img src="../../../img/Dark Mocha.png" alt="Dark Mocha">
                                        </div>
                                    </div>
                                    <div class="card-info">
                                        <div class="card-mid">
                                            <p class="card-name">Dark Mocha</p>
                                        </div>
                                        <div class="card-footer">
                                            <p class="card-price">₱199.00</p>
                                            <div class="drink-stock">
                                                <p class="drink-status available">
                                                    <span class="status-dot"></span> Available
                                                </p>
                                                <p class="drink-ingredient">
                                                    Ingredients: <span>Sufficient</span>
                                                </p>
                                                <p class="drink-cups">
                                                    Cups: <span class="cups-value">40 pcs</span>
                                                </p>
                                                <p class="drink-servings">
                                                    <span class="servings-value">20</span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="product-card" data-category="coffee" data-id="biscoff-creamy-latte">
                                    <div class="card-actions">
                                        <button type="button" class="grid-action-btn" aria-label="More actions"
                                            title="More actions">
                                            <i class="fa-solid fa-ellipsis-vertical"></i>
                                        </button>
                                        <div class="grid-delete-menu">
                                            <button type="button" class="grid-delete-option btn-delete"
                                                aria-label="Delete product">
                                                <i class="fa-solid fa-trash"></i> Delete
                                            </button>
                                        </div>
                                        <button type="button" class="grid-edit-btn btn-edit" aria-label="Edit product"
                                            title="Edit">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <div class="list-action-buttons">
                                            <button type="button" class="list-action-btn btn-edit"
                                                aria-label="Edit product" title="Edit">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>
                                            <button type="button" class="list-action-btn btn-delete"
                                                aria-label="Delete product" title="Delete">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-image">
                                        <div class="card-image-placeholder">
                                            <img src="../../../img/Biscoff Creamy Latte.png" alt="Biscoff Creamy Latte">
                                        </div>
                                    </div>
                                    <div class="card-info">
                                        <div class="card-mid">
                                            <p class="card-name">Biscoff Creamy Latte</p>
                                        </div>
                                        <div class="card-footer">
                                            <p class="card-price">₱199.00</p>
                                            <div class="drink-stock">
                                                <p class="drink-status available">
                                                    <span class="status-dot"></span> Available
                                                </p>
                                                <p class="drink-ingredient">
                                                    Ingredients: <span>Sufficient</span>
                                                </p>
                                                <p class="drink-cups">
                                                    Cups: <span class="cups-value">40 pcs</span>
                                                </p>
                                                <p class="drink-servings">
                                                    <span class="servings-value">16</span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- ================= NON-COFFEE ================= -->
                                <div class="product-card" data-category="non-coffee" data-id="strawberry-milk">
                                    <div class="card-actions">
                                        <button type="button" class="grid-action-btn" aria-label="More actions"
                                            title="More actions">
                                            <i class="fa-solid fa-ellipsis-vertical"></i>
                                        </button>
                                        <div class="grid-delete-menu">
                                            <button type="button" class="grid-delete-option btn-delete"
                                                aria-label="Delete product">
                                                <i class="fa-solid fa-trash"></i> Delete
                                            </button>
                                        </div>
                                        <button type="button" class="grid-edit-btn btn-edit" aria-label="Edit product"
                                            title="Edit">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <div class="list-action-buttons">
                                            <button type="button" class="list-action-btn btn-edit"
                                                aria-label="Edit product" title="Edit">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>
                                            <button type="button" class="list-action-btn btn-delete"
                                                aria-label="Delete product" title="Delete">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-image">
                                        <div class="card-image-placeholder">
                                            <img src="../../../img/Strawberry Milk.png" alt="Strawberry Milk">
                                        </div>
                                    </div>
                                    <div class="card-info">
                                        <div class="card-mid">
                                            <p class="card-name">Strawberry Milk</p>
                                        </div>
                                        <div class="card-footer">
                                            <p class="card-price">₱89.00</p>
                                            <div class="drink-stock">
                                                <p class="drink-status available">
                                                    <span class="status-dot"></span> Available
                                                </p>
                                                <p class="drink-ingredient">
                                                    Ingredients: <span>Sufficient</span>
                                                </p>
                                                <p class="drink-cups">
                                                    Cups: <span class="cups-value">40 pcs</span>
                                                </p>
                                                <p class="drink-servings">
                                                    <span class="servings-value">25</span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="product-card" data-category="non-coffee" data-id="blueberry-milk">
                                    <div class="card-actions">
                                        <button type="button" class="grid-action-btn" aria-label="More actions"
                                            title="More actions">
                                            <i class="fa-solid fa-ellipsis-vertical"></i>
                                        </button>
                                        <div class="grid-delete-menu">
                                            <button type="button" class="grid-delete-option btn-delete"
                                                aria-label="Delete product">
                                                <i class="fa-solid fa-trash"></i> Delete
                                            </button>
                                        </div>
                                        <button type="button" class="grid-edit-btn btn-edit" aria-label="Edit product"
                                            title="Edit">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <div class="list-action-buttons">
                                            <button type="button" class="list-action-btn btn-edit"
                                                aria-label="Edit product" title="Edit">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>
                                            <button type="button" class="list-action-btn btn-delete"
                                                aria-label="Delete product" title="Delete">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-image">
                                        <div class="card-image-placeholder">
                                            <img src="../../../img/Blueberry Milk.png" alt="Blueberry Milk">
                                        </div>
                                    </div>
                                    <div class="card-info">
                                        <div class="card-mid">
                                            <p class="card-name">Blueberry Milk</p>
                                        </div>
                                        <div class="card-footer">
                                            <p class="card-price">₱89.00</p>
                                            <div class="drink-stock">
                                                <p class="drink-status available">
                                                    <span class="status-dot"></span> Available
                                                </p>
                                                <p class="drink-ingredient">
                                                    Ingredients: <span>Sufficient</span>
                                                </p>
                                                <p class="drink-cups">
                                                    Cups: <span class="cups-value">40 pcs</span>
                                                </p>
                                                <p class="drink-servings">
                                                    <span class="servings-value">25</span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="product-card" data-category="non-coffee" data-id="milky-oreo">
                                    <div class="card-actions">
                                        <button type="button" class="grid-action-btn" aria-label="More actions"
                                            title="More actions">
                                            <i class="fa-solid fa-ellipsis-vertical"></i>
                                        </button>
                                        <div class="grid-delete-menu">
                                            <button type="button" class="grid-delete-option btn-delete"
                                                aria-label="Delete product">
                                                <i class="fa-solid fa-trash"></i> Delete
                                            </button>
                                        </div>
                                        <button type="button" class="grid-edit-btn btn-edit" aria-label="Edit product"
                                            title="Edit">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <div class="list-action-buttons">
                                            <button type="button" class="list-action-btn btn-edit"
                                                aria-label="Edit product" title="Edit">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>
                                            <button type="button" class="list-action-btn btn-delete"
                                                aria-label="Delete product" title="Delete">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-image">
                                        <div class="card-image-placeholder">
                                            <img src="../../../img/Milky Oreo.png" alt="Milky Oreo">
                                        </div>
                                    </div>
                                    <div class="card-info">
                                        <div class="card-mid">
                                            <p class="card-name">Milky Oreo</p>
                                        </div>
                                        <div class="card-footer">
                                            <p class="card-price">₱99.00</p>
                                            <div class="drink-stock">
                                                <p class="drink-status available">
                                                    <span class="status-dot"></span> Available
                                                </p>
                                                <p class="drink-ingredient">
                                                    Ingredients: <span>Sufficient</span>
                                                </p>
                                                <p class="drink-cups">
                                                    Cups: <span class="cups-value">40 pcs</span>
                                                </p>
                                                <p class="drink-servings">
                                                    <span class="servings-value">30</span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="product-card" data-category="non-coffee" data-id="choco-berry">
                                    <div class="card-actions">
                                        <button type="button" class="grid-action-btn" aria-label="More actions"
                                            title="More actions">
                                            <i class="fa-solid fa-ellipsis-vertical"></i>
                                        </button>
                                        <div class="grid-delete-menu">
                                            <button type="button" class="grid-delete-option btn-delete"
                                                aria-label="Delete product">
                                                <i class="fa-solid fa-trash"></i> Delete
                                            </button>
                                        </div>
                                        <button type="button" class="grid-edit-btn btn-edit" aria-label="Edit product"
                                            title="Edit">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <div class="list-action-buttons">
                                            <button type="button" class="list-action-btn btn-edit"
                                                aria-label="Edit product" title="Edit">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>
                                            <button type="button" class="list-action-btn btn-delete"
                                                aria-label="Delete product" title="Delete">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-image">
                                        <div class="card-image-placeholder">
                                            <img src="../../../img/Choco Berry.png" alt="Choco Berry">
                                        </div>
                                    </div>
                                    <div class="card-info">
                                        <div class="card-mid">
                                            <p class="card-name">Choco Berry</p>
                                        </div>
                                        <div class="card-footer">
                                            <p class="card-price">₱129.00</p>
                                            <div class="drink-stock">
                                                <p class="drink-status available">
                                                    <span class="status-dot"></span> Available
                                                </p>
                                                <p class="drink-ingredient">
                                                    Ingredients: <span>Sufficient</span>
                                                </p>
                                                <p class="drink-cups">
                                                    Cups: <span class="cups-value">40 pcs</span>
                                                </p>
                                                <p class="drink-servings">
                                                    <span class="servings-value">20</span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="product-card" data-category="non-coffee" data-id="choco-banana-pudding">
                                    <div class="card-actions">
                                        <button type="button" class="grid-action-btn" aria-label="More actions"
                                            title="More actions">
                                            <i class="fa-solid fa-ellipsis-vertical"></i>
                                        </button>
                                        <div class="grid-delete-menu">
                                            <button type="button" class="grid-delete-option btn-delete"
                                                aria-label="Delete product">
                                                <i class="fa-solid fa-trash"></i> Delete
                                            </button>
                                        </div>
                                        <button type="button" class="grid-edit-btn btn-edit" aria-label="Edit product"
                                            title="Edit">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <div class="list-action-buttons">
                                            <button type="button" class="list-action-btn btn-edit"
                                                aria-label="Edit product" title="Edit">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>
                                            <button type="button" class="list-action-btn btn-delete"
                                                aria-label="Delete product" title="Delete">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-image">
                                        <div class="card-image-placeholder">
                                            <img src="../../../img/Choco Banna Pudding.png" alt="Choco Banana Pudding">
                                        </div>
                                    </div>
                                    <div class="card-info">
                                        <div class="card-mid">
                                            <p class="card-name">Choco Banana Pudding</p>
                                        </div>
                                        <div class="card-footer">
                                            <p class="card-price">₱209.00</p>
                                            <div class="drink-stock">
                                                <p class="drink-status available">
                                                    <span class="status-dot"></span> Available
                                                </p>
                                                <p class="drink-ingredient">
                                                    Ingredients: <span>Sufficient</span>
                                                </p>
                                                <p class="drink-cups">
                                                    Cups: <span class="cups-value">40 pcs</span>
                                                </p>
                                                <p class="drink-servings">
                                                    <span class="servings-value">12</span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="product-card" data-category="non-coffee" data-id="choco-vanilla-cookie">
                                    <div class="card-actions">
                                        <button type="button" class="grid-action-btn" aria-label="More actions"
                                            title="More actions">
                                            <i class="fa-solid fa-ellipsis-vertical"></i>
                                        </button>
                                        <div class="grid-delete-menu">
                                            <button type="button" class="grid-delete-option btn-delete"
                                                aria-label="Delete product">
                                                <i class="fa-solid fa-trash"></i> Delete
                                            </button>
                                        </div>
                                        <button type="button" class="grid-edit-btn btn-edit" aria-label="Edit product"
                                            title="Edit">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <div class="list-action-buttons">
                                            <button type="button" class="list-action-btn btn-edit"
                                                aria-label="Edit product" title="Edit">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>
                                            <button type="button" class="list-action-btn btn-delete"
                                                aria-label="Delete product" title="Delete">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-image">
                                        <div class="card-image-placeholder">
                                            <img src="../../../img/Choco Vanilla Cookie.png" alt="Choco Vanilla Cookie">
                                        </div>
                                    </div>
                                    <div class="card-info">
                                        <div class="card-mid">
                                            <p class="card-name">Choco Vanilla Cookie</p>
                                        </div>
                                        <div class="card-footer">
                                            <p class="card-price">₱139.00</p>
                                            <div class="drink-stock">
                                                <p class="drink-status available">
                                                    <span class="status-dot"></span> Available
                                                </p>
                                                <p class="drink-ingredient">
                                                    Ingredients: <span>Sufficient</span>
                                                </p>
                                                <p class="drink-cups">
                                                    Cups: <span class="cups-value">40 pcs</span>
                                                </p>
                                                <p class="drink-servings">
                                                    <span class="servings-value">18</span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- ================= MATCHA FUSION ================= -->
                                <div class="product-card" data-category="matcha-fusion" data-id="strawberry-matcha">
                                    <div class="card-actions">
                                        <button type="button" class="grid-action-btn" aria-label="More actions"
                                            title="More actions">
                                            <i class="fa-solid fa-ellipsis-vertical"></i>
                                        </button>
                                        <div class="grid-delete-menu">
                                            <button type="button" class="grid-delete-option btn-delete"
                                                aria-label="Delete product">
                                                <i class="fa-solid fa-trash"></i> Delete
                                            </button>
                                        </div>
                                        <button type="button" class="grid-edit-btn btn-edit" aria-label="Edit product"
                                            title="Edit">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <div class="list-action-buttons">
                                            <button type="button" class="list-action-btn btn-edit"
                                                aria-label="Edit product" title="Edit">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>
                                            <button type="button" class="list-action-btn btn-delete"
                                                aria-label="Delete product" title="Delete">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-image">
                                        <div class="card-image-placeholder">
                                            <img src="../../../img/Strawberry Matcha.png" alt="Strawberry Matcha">
                                        </div>
                                    </div>
                                    <div class="card-info">
                                        <div class="card-mid">
                                            <p class="card-name">Strawberry Matcha</p>
                                        </div>
                                        <div class="card-footer">
                                            <p class="card-price">₱155.00</p>
                                            <div class="drink-stock">
                                                <p class="drink-status available">
                                                    <span class="status-dot"></span> Available
                                                </p>
                                                <p class="drink-ingredient">
                                                    Ingredients: <span>Sufficient</span>
                                                </p>
                                                <p class="drink-cups">
                                                    Cups: <span class="cups-value">40 pcs</span>
                                                </p>
                                                <p class="drink-servings">
                                                    <span class="servings-value">18</span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="product-card" data-category="matcha-fusion" data-id="matcha-banana-pudding">
                                    <div class="card-actions">
                                        <button type="button" class="grid-action-btn" aria-label="More actions"
                                            title="More actions">
                                            <i class="fa-solid fa-ellipsis-vertical"></i>
                                        </button>
                                        <div class="grid-delete-menu">
                                            <button type="button" class="grid-delete-option btn-delete"
                                                aria-label="Delete product">
                                                <i class="fa-solid fa-trash"></i> Delete
                                            </button>
                                        </div>
                                        <button type="button" class="grid-edit-btn btn-edit" aria-label="Edit product"
                                            title="Edit">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <div class="list-action-buttons">
                                            <button type="button" class="list-action-btn btn-edit"
                                                aria-label="Edit product" title="Edit">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>
                                            <button type="button" class="list-action-btn btn-delete"
                                                aria-label="Delete product" title="Delete">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-image">
                                        <div class="card-image-placeholder">
                                            <img src="../../../img/Matcha banana Pudding.png"
                                                alt="Matcha Banana Pudding">
                                        </div>
                                    </div>
                                    <div class="card-info">
                                        <div class="card-mid">
                                            <p class="card-name">Matcha Banana Pudding</p>
                                        </div>
                                        <div class="card-footer">
                                            <p class="card-price">₱225.00</p>
                                            <div class="drink-stock">
                                                <p class="drink-status available">
                                                    <span class="status-dot"></span> Available
                                                </p>
                                                <p class="drink-ingredient">
                                                    Ingredients: <span>Sufficient</span>
                                                </p>
                                                <p class="drink-cups">
                                                    Cups: <span class="cups-value">40 pcs</span>
                                                </p>
                                                <p class="drink-servings">
                                                    <span class="servings-value">10</span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="product-card" data-category="matcha-fusion" data-id="biscoff-matcha">
                                    <div class="card-actions">
                                        <button type="button" class="grid-action-btn" aria-label="More actions"
                                            title="More actions">
                                            <i class="fa-solid fa-ellipsis-vertical"></i>
                                        </button>
                                        <div class="grid-delete-menu">
                                            <button type="button" class="grid-delete-option btn-delete"
                                                aria-label="Delete product">
                                                <i class="fa-solid fa-trash"></i> Delete
                                            </button>
                                        </div>
                                        <button type="button" class="grid-edit-btn btn-edit" aria-label="Edit product"
                                            title="Edit">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <div class="list-action-buttons">
                                            <button type="button" class="list-action-btn btn-edit"
                                                aria-label="Edit product" title="Edit">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>
                                            <button type="button" class="list-action-btn btn-delete"
                                                aria-label="Delete product" title="Delete">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-image">
                                        <div class="card-image-placeholder">
                                            <img src="../../../img/Biscoff Matcha 1.png" alt="Biscoff Matcha">
                                        </div>
                                    </div>
                                    <div class="card-info">
                                        <div class="card-mid">
                                            <p class="card-name">Biscoff Matcha</p>
                                        </div>
                                        <div class="card-footer">
                                            <p class="card-price">₱205.00</p>
                                            <div class="drink-stock">
                                                <p class="drink-status available">
                                                    <span class="status-dot"></span> Available
                                                </p>
                                                <p class="drink-ingredient">
                                                    Ingredients: <span>Sufficient</span>
                                                </p>
                                                <p class="drink-cups">
                                                    Cups: <span class="cups-value">40 pcs</span>
                                                </p>
                                                <p class="drink-servings">
                                                    <span class="servings-value">16</span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="product-card" data-category="matcha-fusion" data-id="mango-matcha">
                                    <div class="card-actions">
                                        <button type="button" class="grid-action-btn" aria-label="More actions"
                                            title="More actions">
                                            <i class="fa-solid fa-ellipsis-vertical"></i>
                                        </button>
                                        <div class="grid-delete-menu">
                                            <button type="button" class="grid-delete-option btn-delete"
                                                aria-label="Delete product">
                                                <i class="fa-solid fa-trash"></i> Delete
                                            </button>
                                        </div>
                                        <button type="button" class="grid-edit-btn btn-edit" aria-label="Edit product"
                                            title="Edit">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <div class="list-action-buttons">
                                            <button type="button" class="list-action-btn btn-edit"
                                                aria-label="Edit product" title="Edit">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>
                                            <button type="button" class="list-action-btn btn-delete"
                                                aria-label="Delete product" title="Delete">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-image">
                                        <div class="card-image-placeholder">
                                            <img src="../../../img/Mango matcha.png" alt="Mango Matcha">
                                        </div>
                                    </div>
                                    <div class="card-info">
                                        <div class="card-mid">
                                            <p class="card-name">Mango Matcha</p>
                                        </div>
                                        <div class="card-footer">
                                            <p class="card-price">₱145.00</p>
                                            <div class="drink-stock">
                                                <p class="drink-status available">
                                                    <span class="status-dot"></span> Available
                                                </p>
                                                <p class="drink-ingredient">
                                                    Ingredients: <span>Sufficient</span>
                                                </p>
                                                <p class="drink-cups">
                                                    Cups: <span class="cups-value">40 pcs</span>
                                                </p>
                                                <p class="drink-servings">
                                                    <span class="servings-value">18</span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="product-card" data-category="matcha-fusion" data-id="sea-salt-matcha">
                                    <div class="card-actions">
                                        <button type="button" class="grid-action-btn" aria-label="More actions"
                                            title="More actions">
                                            <i class="fa-solid fa-ellipsis-vertical"></i>
                                        </button>
                                        <div class="grid-delete-menu">
                                            <button type="button" class="grid-delete-option btn-delete"
                                                aria-label="Delete product">
                                                <i class="fa-solid fa-trash"></i> Delete
                                            </button>
                                        </div>
                                        <button type="button" class="grid-edit-btn btn-edit" aria-label="Edit product"
                                            title="Edit">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <div class="list-action-buttons">
                                            <button type="button" class="list-action-btn btn-edit"
                                                aria-label="Edit product" title="Edit">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>
                                            <button type="button" class="list-action-btn btn-delete"
                                                aria-label="Delete product" title="Delete">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-image">
                                        <div class="card-image-placeholder">
                                            <img src="../../../img/Seasalt Matcha.png" alt="Sea Salt Matcha">
                                        </div>
                                    </div>
                                    <div class="card-info">
                                        <div class="card-mid">
                                            <p class="card-name">Sea Salt Matcha</p>
                                        </div>
                                        <div class="card-footer">
                                            <p class="card-price">₱95.00</p>
                                            <div class="drink-stock">
                                                <p class="drink-status available">
                                                    <span class="status-dot"></span> Available
                                                </p>
                                                <p class="drink-ingredient">
                                                    Ingredients: <span>Sufficient</span>
                                                </p>
                                                <p class="drink-cups">
                                                    Cups: <span class="cups-value">40 pcs</span>
                                                </p>
                                                <p class="drink-servings">
                                                    <span class="servings-value">22</span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="product-card" data-category="matcha-fusion" data-id="matcha-freddo">
                                    <div class="card-actions">
                                        <button type="button" class="grid-action-btn" aria-label="More actions"
                                            title="More actions">
                                            <i class="fa-solid fa-ellipsis-vertical"></i>
                                        </button>
                                        <div class="grid-delete-menu">
                                            <button type="button" class="grid-delete-option btn-delete"
                                                aria-label="Delete product">
                                                <i class="fa-solid fa-trash"></i> Delete
                                            </button>
                                        </div>
                                        <button type="button" class="grid-edit-btn btn-edit" aria-label="Edit product"
                                            title="Edit">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <div class="list-action-buttons">
                                            <button type="button" class="list-action-btn btn-edit"
                                                aria-label="Edit product" title="Edit">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>
                                            <button type="button" class="list-action-btn btn-delete"
                                                aria-label="Delete product" title="Delete">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-image">
                                        <div class="card-image-placeholder">
                                            <img src="../../../img/Matcha Freddo.png" alt="Matcha Freddo">
                                        </div>
                                    </div>
                                    <div class="card-info">
                                        <div class="card-mid">
                                            <p class="card-name">Matcha Freddo</p>
                                        </div>
                                        <div class="card-footer">
                                            <p class="card-price">₱99.00</p>
                                            <div class="drink-stock">
                                                <p class="drink-status available">
                                                    <span class="status-dot"></span> Available
                                                </p>
                                                <p class="drink-ingredient">
                                                    Ingredients: <span>Sufficient</span>
                                                </p>
                                                <p class="drink-cups">
                                                    Cups: <span class="cups-value">40 pcs</span>
                                                </p>
                                                <p class="drink-servings">
                                                    <span class="servings-value">22</span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="product-card" data-category="matcha-fusion" data-id="matcha-latte">
                                    <div class="card-actions">
                                        <button type="button" class="grid-action-btn" aria-label="More actions"
                                            title="More actions">
                                            <i class="fa-solid fa-ellipsis-vertical"></i>
                                        </button>
                                        <div class="grid-delete-menu">
                                            <button type="button" class="grid-delete-option btn-delete"
                                                aria-label="Delete product">
                                                <i class="fa-solid fa-trash"></i> Delete
                                            </button>
                                        </div>
                                        <button type="button" class="grid-edit-btn btn-edit" aria-label="Edit product"
                                            title="Edit">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <div class="list-action-buttons">
                                            <button type="button" class="list-action-btn btn-edit"
                                                aria-label="Edit product" title="Edit">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>
                                            <button type="button" class="list-action-btn btn-delete"
                                                aria-label="Delete product" title="Delete">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-image">
                                        <div class="card-image-placeholder">
                                            <img src="../../../img/Matcha Latte.png" alt="Matcha Latte">
                                        </div>
                                    </div>
                                    <div class="card-info">
                                        <div class="card-mid">
                                            <p class="card-name">Matcha Latte</p>
                                        </div>
                                        <div class="card-footer">
                                            <p class="card-price">₱85.00</p>
                                            <div class="drink-stock">
                                                <p class="drink-status available">
                                                    <span class="status-dot"></span> Available
                                                </p>
                                                <p class="drink-ingredient">
                                                    Ingredients: <span>Sufficient</span>
                                                </p>
                                                <p class="drink-cups">
                                                    Cups: <span class="cups-value">40 pcs</span>
                                                </p>
                                                <p class="drink-servings">
                                                    <span class="servings-value">24</span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="product-card" data-category="matcha-fusion" data-id="ube-matcha">
                                    <div class="card-actions">
                                        <button type="button" class="grid-action-btn" aria-label="More actions"
                                            title="More actions">
                                            <i class="fa-solid fa-ellipsis-vertical"></i>
                                        </button>
                                        <div class="grid-delete-menu">
                                            <button type="button" class="grid-delete-option btn-delete"
                                                aria-label="Delete product">
                                                <i class="fa-solid fa-trash"></i> Delete
                                            </button>
                                        </div>
                                        <button type="button" class="grid-edit-btn btn-edit" aria-label="Edit product"
                                            title="Edit">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <div class="list-action-buttons">
                                            <button type="button" class="list-action-btn btn-edit"
                                                aria-label="Edit product" title="Edit">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>
                                            <button type="button" class="list-action-btn btn-delete"
                                                aria-label="Delete product" title="Delete">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-image">
                                        <div class="card-image-placeholder">
                                            <img src="../../../img/Ube matcha.png" alt="Ube Matcha">
                                        </div>
                                    </div>
                                    <div class="card-info">
                                        <div class="card-mid">
                                            <p class="card-name">Ube Matcha</p>
                                        </div>
                                        <div class="card-footer">
                                            <p class="card-price">₱145.00</p>
                                            <div class="drink-stock">
                                                <p class="drink-status available">
                                                    <span class="status-dot"></span> Available
                                                </p>
                                                <p class="drink-ingredient">
                                                    Ingredients: <span>Sufficient</span>
                                                </p>
                                                <p class="drink-cups">
                                                    Cups: <span class="cups-value">40 pcs</span>
                                                </p>
                                                <p class="drink-servings">
                                                    <span class="servings-value">18</span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="product-card" data-category="matcha-fusion" data-id="cheesecake-matcha">
                                    <div class="card-actions">
                                        <button type="button" class="grid-action-btn" aria-label="More actions"
                                            title="More actions">
                                            <i class="fa-solid fa-ellipsis-vertical"></i>
                                        </button>
                                        <div class="grid-delete-menu">
                                            <button type="button" class="grid-delete-option btn-delete"
                                                aria-label="Delete product">
                                                <i class="fa-solid fa-trash"></i> Delete
                                            </button>
                                        </div>
                                        <button type="button" class="grid-edit-btn btn-edit" aria-label="Edit product"
                                            title="Edit">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <div class="list-action-buttons">
                                            <button type="button" class="list-action-btn btn-edit"
                                                aria-label="Edit product" title="Edit">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>
                                            <button type="button" class="list-action-btn btn-delete"
                                                aria-label="Delete product" title="Delete">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-image">
                                        <div class="card-image-placeholder">
                                            <img src="../../../img/Cheesecake Matcha.png" alt="Cheesecake Matcha">
                                        </div>
                                    </div>
                                    <div class="card-info">
                                        <div class="card-mid">
                                            <p class="card-name">Cheesecake Matcha</p>
                                        </div>
                                        <div class="card-footer">
                                            <p class="card-price">₱155.00</p>
                                            <div class="drink-stock">
                                                <p class="drink-status available">
                                                    <span class="status-dot"></span> Available
                                                </p>
                                                <p class="drink-ingredient">
                                                    Ingredients: <span>Sufficient</span>
                                                </p>
                                                <p class="drink-cups">
                                                    Cups: <span class="cups-value">40 pcs</span>
                                                </p>
                                                <p class="drink-servings">
                                                    <span class="servings-value">15</span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- ================= SMOOTHIE ================= -->
                                <div class="product-card" data-category="smoothie" data-id="strawberry-smoothie">
                                    <div class="card-actions">
                                        <button type="button" class="grid-action-btn" aria-label="More actions"
                                            title="More actions">
                                            <i class="fa-solid fa-ellipsis-vertical"></i>
                                        </button>
                                        <div class="grid-delete-menu">
                                            <button type="button" class="grid-delete-option btn-delete"
                                                aria-label="Delete product">
                                                <i class="fa-solid fa-trash"></i> Delete
                                            </button>
                                        </div>
                                        <button type="button" class="grid-edit-btn btn-edit" aria-label="Edit product"
                                            title="Edit">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <div class="list-action-buttons">
                                            <button type="button" class="list-action-btn btn-edit"
                                                aria-label="Edit product" title="Edit">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>
                                            <button type="button" class="list-action-btn btn-delete"
                                                aria-label="Delete product" title="Delete">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-image">
                                        <div class="card-image-placeholder">
                                            <img src="../../../img/Strawberry Smoothie 1.png" alt="Strawberry">
                                        </div>
                                    </div>
                                    <div class="card-info">
                                        <div class="card-mid">
                                            <p class="card-name">Strawberry</p>
                                        </div>
                                        <div class="card-footer">
                                            <p class="card-price">₱85.00</p>
                                            <div class="drink-stock">
                                                <p class="drink-status available">
                                                    <span class="status-dot"></span> Available
                                                </p>
                                                <p class="drink-ingredient">
                                                    Ingredients: <span>Sufficient</span>
                                                </p>
                                                <p class="drink-cups">
                                                    Cups: <span class="cups-value">40 pcs</span>
                                                </p>
                                                <p class="drink-servings">
                                                    <span class="servings-value">20</span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="product-card" data-category="smoothie" data-id="berry-mango">
                                    <div class="card-actions">
                                        <button type="button" class="grid-action-btn" aria-label="More actions"
                                            title="More actions">
                                            <i class="fa-solid fa-ellipsis-vertical"></i>
                                        </button>
                                        <div class="grid-delete-menu">
                                            <button type="button" class="grid-delete-option btn-delete"
                                                aria-label="Delete product">
                                                <i class="fa-solid fa-trash"></i> Delete
                                            </button>
                                        </div>
                                        <button type="button" class="grid-edit-btn btn-edit" aria-label="Edit product"
                                            title="Edit">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <div class="list-action-buttons">
                                            <button type="button" class="list-action-btn btn-edit"
                                                aria-label="Edit product" title="Edit">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>
                                            <button type="button" class="list-action-btn btn-delete"
                                                aria-label="Delete product" title="Delete">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-image">
                                        <div class="card-image-placeholder">
                                            <img src="../../../img/Berry Mango 1.png" alt="Berry Mango">
                                        </div>
                                    </div>
                                    <div class="card-info">
                                        <div class="card-mid">
                                            <p class="card-name">Berry Mango</p>
                                        </div>
                                        <div class="card-footer">
                                            <p class="card-price">₱99.00</p>
                                            <div class="drink-stock">
                                                <p class="drink-status available">
                                                    <span class="status-dot"></span> Available
                                                </p>
                                                <p class="drink-ingredient">
                                                    Ingredients: <span>Sufficient</span>
                                                </p>
                                                <p class="drink-cups">
                                                    Cups: <span class="cups-value">40 pcs</span>
                                                </p>
                                                <p class="drink-servings">
                                                    <span class="servings-value">19</span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="product-card" data-category="smoothie" data-id="tropical-matcha-yogurt">
                                    <div class="card-actions">
                                        <button type="button" class="grid-action-btn" aria-label="More actions"
                                            title="More actions">
                                            <i class="fa-solid fa-ellipsis-vertical"></i>
                                        </button>
                                        <div class="grid-delete-menu">
                                            <button type="button" class="grid-delete-option btn-delete"
                                                aria-label="Delete product">
                                                <i class="fa-solid fa-trash"></i> Delete
                                            </button>
                                        </div>
                                        <button type="button" class="grid-edit-btn btn-edit" aria-label="Edit product"
                                            title="Edit">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <div class="list-action-buttons">
                                            <button type="button" class="list-action-btn btn-edit"
                                                aria-label="Edit product" title="Edit">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>
                                            <button type="button" class="list-action-btn btn-delete"
                                                aria-label="Delete product" title="Delete">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-image">
                                        <div class="card-image-placeholder">
                                            <img src="../../../img/Tropical Matcha Yogurt 1.png"
                                                alt="Tropical Matcha Yogurt">
                                        </div>
                                    </div>
                                    <div class="card-info">
                                        <div class="card-mid">
                                            <p class="card-name">Tropical Matcha Yogurt</p>
                                        </div>
                                        <div class="card-footer">
                                            <p class="card-price">₱109.00</p>
                                            <div class="drink-stock">
                                                <p class="drink-status available">
                                                    <span class="status-dot"></span> Available
                                                </p>
                                                <p class="drink-ingredient">
                                                    Ingredients: <span>Sufficient</span>
                                                </p>
                                                <p class="drink-cups">
                                                    Cups: <span class="cups-value">40 pcs</span>
                                                </p>
                                                <p class="drink-servings">
                                                    <span class="servings-value">16</span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="product-card" data-category="smoothie" data-id="ube-yogurt">
                                    <div class="card-actions">
                                        <button type="button" class="grid-action-btn" aria-label="More actions"
                                            title="More actions">
                                            <i class="fa-solid fa-ellipsis-vertical"></i>
                                        </button>
                                        <div class="grid-delete-menu">
                                            <button type="button" class="grid-delete-option btn-delete"
                                                aria-label="Delete product">
                                                <i class="fa-solid fa-trash"></i> Delete
                                            </button>
                                        </div>
                                        <button type="button" class="grid-edit-btn btn-edit" aria-label="Edit product"
                                            title="Edit">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <div class="list-action-buttons">
                                            <button type="button" class="list-action-btn btn-edit"
                                                aria-label="Edit product" title="Edit">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>
                                            <button type="button" class="list-action-btn btn-delete"
                                                aria-label="Delete product" title="Delete">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-image">
                                        <div class="card-image-placeholder">
                                            <img src="../../../img/Ube yogurt 1.png" alt="Ube Yogurt">
                                        </div>
                                    </div>
                                    <div class="card-info">
                                        <div class="card-mid">
                                            <p class="card-name">Ube Yogurt</p>
                                        </div>
                                        <div class="card-footer">
                                            <p class="card-price">₱199.00</p>
                                            <div class="drink-stock">
                                                <p class="drink-status available">
                                                    <span class="status-dot"></span> Available
                                                </p>
                                                <p class="drink-ingredient">
                                                    Ingredients: <span>Sufficient</span>
                                                </p>
                                                <p class="drink-cups">
                                                    Cups: <span class="cups-value">40 pcs</span>
                                                </p>
                                                <p class="drink-servings">
                                                    <span class="servings-value">12</span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="product-card" data-category="smoothie" data-id="blueberry">
                                    <div class="card-actions">
                                        <button type="button" class="grid-action-btn" aria-label="More actions"
                                            title="More actions">
                                            <i class="fa-solid fa-ellipsis-vertical"></i>
                                        </button>
                                        <div class="grid-delete-menu">
                                            <button type="button" class="grid-delete-option btn-delete"
                                                aria-label="Delete product">
                                                <i class="fa-solid fa-trash"></i> Delete
                                            </button>
                                        </div>
                                        <button type="button" class="grid-edit-btn btn-edit" aria-label="Edit product"
                                            title="Edit">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <div class="list-action-buttons">
                                            <button type="button" class="list-action-btn btn-edit"
                                                aria-label="Edit product" title="Edit">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>
                                            <button type="button" class="list-action-btn btn-delete"
                                                aria-label="Delete product" title="Delete">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-image">
                                        <div class="card-image-placeholder">
                                            <img src="../../../img/Blueberry.png" alt="Blueberry">
                                        </div>
                                    </div>
                                    <div class="card-info">
                                        <div class="card-mid">
                                            <p class="card-name">Blueberry</p>
                                        </div>
                                        <div class="card-footer">
                                            <p class="card-price">₱85.00</p>
                                            <div class="drink-stock">
                                                <p class="drink-status available">
                                                    <span class="status-dot"></span> Available
                                                </p>
                                                <p class="drink-ingredient">
                                                    Ingredients: <span>Sufficient</span>
                                                </p>
                                                <p class="drink-cups">
                                                    Cups: <span class="cups-value">40 pcs</span>
                                                </p>
                                                <p class="drink-servings">
                                                    <span class="servings-value">22</span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="product-card" data-category="smoothie" data-id="mango-graham">
                                    <div class="card-actions">
                                        <button type="button" class="grid-action-btn" aria-label="More actions"
                                            title="More actions">
                                            <i class="fa-solid fa-ellipsis-vertical"></i>
                                        </button>
                                        <div class="grid-delete-menu">
                                            <button type="button" class="grid-delete-option btn-delete"
                                                aria-label="Delete product">
                                                <i class="fa-solid fa-trash"></i> Delete
                                            </button>
                                        </div>
                                        <button type="button" class="grid-edit-btn btn-edit" aria-label="Edit product"
                                            title="Edit">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <div class="list-action-buttons">
                                            <button type="button" class="list-action-btn btn-edit"
                                                aria-label="Edit product" title="Edit">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>
                                            <button type="button" class="list-action-btn btn-delete"
                                                aria-label="Delete product" title="Delete">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-image">
                                        <div class="card-image-placeholder">
                                            <img src="../../../img/Mango Graham.png" alt="Mango Graham">
                                        </div>
                                    </div>
                                    <div class="card-info">
                                        <div class="card-mid">
                                            <p class="card-name">Mango Graham</p>
                                        </div>
                                        <div class="card-footer">
                                            <p class="card-price">₱85.00</p>
                                            <div class="drink-stock">
                                                <p class="drink-status available">
                                                    <span class="status-dot"></span> Available
                                                </p>
                                                <p class="drink-ingredient">
                                                    Ingredients: <span>Sufficient</span>
                                                </p>
                                                <p class="drink-cups">
                                                    Cups: <span class="cups-value">40 pcs</span>
                                                </p>
                                                <p class="drink-servings">
                                                    <span class="servings-value">20</span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- ================= FRAPPE SERIES ================= -->
                                <div class="product-card" data-category="frappe-series" data-id="hershey-delight">
                                    <div class="card-actions">
                                        <button type="button" class="grid-action-btn" aria-label="More actions"
                                            title="More actions">
                                            <i class="fa-solid fa-ellipsis-vertical"></i>
                                        </button>
                                        <div class="grid-delete-menu">
                                            <button type="button" class="grid-delete-option btn-delete"
                                                aria-label="Delete product">
                                                <i class="fa-solid fa-trash"></i> Delete
                                            </button>
                                        </div>
                                        <button type="button" class="grid-edit-btn btn-edit" aria-label="Edit product"
                                            title="Edit">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <div class="list-action-buttons">
                                            <button type="button" class="list-action-btn btn-edit"
                                                aria-label="Edit product" title="Edit">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>
                                            <button type="button" class="list-action-btn btn-delete"
                                                aria-label="Delete product" title="Delete">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-image">
                                        <div class="card-image-placeholder">
                                            <img src="../../../img/FRP-Hershey Delight _ 95 1.png"
                                                alt="Hershey Delight">
                                        </div>
                                    </div>
                                    <div class="card-info">
                                        <div class="card-mid">
                                            <p class="card-name">Hershey Delight</p>
                                        </div>
                                        <div class="card-footer">
                                            <p class="card-price">₱129.00</p>
                                            <div class="drink-stock">
                                                <p class="drink-status available">
                                                    <span class="status-dot"></span> Available
                                                </p>
                                                <p class="drink-ingredient">
                                                    Ingredients: <span>Sufficient</span>
                                                </p>
                                                <p class="drink-cups">
                                                    Cups: <span class="cups-value">40 pcs</span>
                                                </p>
                                                <p class="drink-servings">
                                                    <span class="servings-value">18</span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="product-card" data-category="frappe-series" data-id="ube-frappe">
                                    <div class="card-actions">
                                        <button type="button" class="grid-action-btn" aria-label="More actions"
                                            title="More actions">
                                            <i class="fa-solid fa-ellipsis-vertical"></i>
                                        </button>
                                        <div class="grid-delete-menu">
                                            <button type="button" class="grid-delete-option btn-delete"
                                                aria-label="Delete product">
                                                <i class="fa-solid fa-trash"></i> Delete
                                            </button>
                                        </div>
                                        <button type="button" class="grid-edit-btn btn-edit" aria-label="Edit product"
                                            title="Edit">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <div class="list-action-buttons">
                                            <button type="button" class="list-action-btn btn-edit"
                                                aria-label="Edit product" title="Edit">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>
                                            <button type="button" class="list-action-btn btn-delete"
                                                aria-label="Delete product" title="Delete">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-image">
                                        <div class="card-image-placeholder">
                                            <img src="../../../img/Ube Frappe 1.png" alt="Ube Frappe">
                                        </div>
                                    </div>
                                    <div class="card-info">
                                        <div class="card-mid">
                                            <p class="card-name">Ube Frappe</p>
                                        </div>
                                        <div class="card-footer">
                                            <p class="card-price">₱149.00</p>
                                            <div class="drink-stock">
                                                <p class="drink-status available">
                                                    <span class="status-dot"></span> Available
                                                </p>
                                                <p class="drink-ingredient">
                                                    Ingredients: <span>Sufficient</span>
                                                </p>
                                                <p class="drink-cups">
                                                    Cups: <span class="cups-value">40 pcs</span>
                                                </p>
                                                <p class="drink-servings">
                                                    <span class="servings-value">16</span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="product-card" data-category="frappe-series" data-id="oreo-frappe">
                                    <div class="card-actions">
                                        <button type="button" class="grid-action-btn" aria-label="More actions"
                                            title="More actions">
                                            <i class="fa-solid fa-ellipsis-vertical"></i>
                                        </button>
                                        <div class="grid-delete-menu">
                                            <button type="button" class="grid-delete-option btn-delete"
                                                aria-label="Delete product">
                                                <i class="fa-solid fa-trash"></i> Delete
                                            </button>
                                        </div>
                                        <button type="button" class="grid-edit-btn btn-edit" aria-label="Edit product"
                                            title="Edit">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <div class="list-action-buttons">
                                            <button type="button" class="list-action-btn btn-edit"
                                                aria-label="Edit product" title="Edit">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>
                                            <button type="button" class="list-action-btn btn-delete"
                                                aria-label="Delete product" title="Delete">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-image">
                                        <div class="card-image-placeholder">
                                            <img src="../../../img/Oreo Frappe.png" alt="Oreo Frappe">
                                        </div>
                                    </div>
                                    <div class="card-info">
                                        <div class="card-mid">
                                            <p class="card-name">Oreo Frappe</p>
                                        </div>
                                        <div class="card-footer">
                                            <p class="card-price">₱139.00</p>
                                            <div class="drink-stock">
                                                <p class="drink-status available">
                                                    <span class="status-dot"></span> Available
                                                </p>
                                                <p class="drink-ingredient">
                                                    Ingredients: <span>Sufficient</span>
                                                </p>
                                                <p class="drink-cups">
                                                    Cups: <span class="cups-value">40 pcs</span>
                                                </p>
                                                <p class="drink-servings">
                                                    <span class="servings-value">20</span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="product-card" data-category="frappe-series" data-id="matcha-frappe">
                                    <div class="card-actions">
                                        <button type="button" class="grid-action-btn" aria-label="More actions"
                                            title="More actions">
                                            <i class="fa-solid fa-ellipsis-vertical"></i>
                                        </button>
                                        <div class="grid-delete-menu">
                                            <button type="button" class="grid-delete-option btn-delete"
                                                aria-label="Delete product">
                                                <i class="fa-solid fa-trash"></i> Delete
                                            </button>
                                        </div>
                                        <button type="button" class="grid-edit-btn btn-edit" aria-label="Edit product"
                                            title="Edit">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <div class="list-action-buttons">
                                            <button type="button" class="list-action-btn btn-edit"
                                                aria-label="Edit product" title="Edit">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>
                                            <button type="button" class="list-action-btn btn-delete"
                                                aria-label="Delete product" title="Delete">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-image">
                                        <div class="card-image-placeholder">
                                            <img src="../../../img/Matcha Frappe.png" alt="Matcha Frappe">
                                        </div>
                                    </div>
                                    <div class="card-info">
                                        <div class="card-mid">
                                            <p class="card-name">Matcha Frappe</p>
                                        </div>
                                        <div class="card-footer">
                                            <p class="card-price">₱129.00</p>
                                            <div class="drink-stock">
                                                <p class="drink-status available">
                                                    <span class="status-dot"></span> Available
                                                </p>
                                                <p class="drink-ingredient">
                                                    Ingredients: <span>Sufficient</span>
                                                </p>
                                                <p class="drink-cups">
                                                    Cups: <span class="cups-value">40 pcs</span>
                                                </p>
                                                <p class="drink-servings">
                                                    <span class="servings-value">18</span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="product-card" data-category="frappe-series" data-id="java-chips">
                                    <div class="card-actions">
                                        <button type="button" class="grid-action-btn" aria-label="More actions"
                                            title="More actions">
                                            <i class="fa-solid fa-ellipsis-vertical"></i>
                                        </button>
                                        <div class="grid-delete-menu">
                                            <button type="button" class="grid-delete-option btn-delete"
                                                aria-label="Delete product">
                                                <i class="fa-solid fa-trash"></i> Delete
                                            </button>
                                        </div>
                                        <button type="button" class="grid-edit-btn btn-edit" aria-label="Edit product"
                                            title="Edit">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <div class="list-action-buttons">
                                            <button type="button" class="list-action-btn btn-edit"
                                                aria-label="Edit product" title="Edit">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>
                                            <button type="button" class="list-action-btn btn-delete"
                                                aria-label="Delete product" title="Delete">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-image">
                                        <div class="card-image-placeholder">
                                            <img src="../../../img/Java Chips.png" alt="Java Chips">
                                        </div>
                                    </div>
                                    <div class="card-info">
                                        <div class="card-mid">
                                            <p class="card-name">Java Chips</p>
                                        </div>
                                        <div class="card-footer">
                                            <p class="card-price">₱149.00</p>
                                            <div class="drink-stock">
                                                <p class="drink-status available">
                                                    <span class="status-dot"></span> Available
                                                </p>
                                                <p class="drink-ingredient">
                                                    Ingredients: <span>Sufficient</span>
                                                </p>
                                                <p class="drink-cups">
                                                    Cups: <span class="cups-value">40 pcs</span>
                                                </p>
                                                <p class="drink-servings">
                                                    <span class="servings-value">16</span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="product-card" data-category="frappe-series" data-id="cheesecake-frappe">
                                    <div class="card-actions">
                                        <button type="button" class="grid-action-btn" aria-label="More actions"
                                            title="More actions">
                                            <i class="fa-solid fa-ellipsis-vertical"></i>
                                        </button>
                                        <div class="grid-delete-menu">
                                            <button type="button" class="grid-delete-option btn-delete"
                                                aria-label="Delete product">
                                                <i class="fa-solid fa-trash"></i> Delete
                                            </button>
                                        </div>
                                        <button type="button" class="grid-edit-btn btn-edit" aria-label="Edit product"
                                            title="Edit">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <div class="list-action-buttons">
                                            <button type="button" class="list-action-btn btn-edit"
                                                aria-label="Edit product" title="Edit">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>
                                            <button type="button" class="list-action-btn btn-delete"
                                                aria-label="Delete product" title="Delete">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-image">
                                        <div class="card-image-placeholder">
                                            <img src="../../../img/Cheesecake Frappe.png" alt="Cheesecake Frappe">
                                        </div>
                                    </div>
                                    <div class="card-info">
                                        <div class="card-mid">
                                            <p class="card-name">Cheesecake Frappe</p>
                                        </div>
                                        <div class="card-footer">
                                            <p class="card-price">₱159.00</p>
                                            <div class="drink-stock">
                                                <p class="drink-status available">
                                                    <span class="status-dot"></span> Available
                                                </p>
                                                <p class="drink-ingredient">
                                                    Ingredients: <span>Sufficient</span>
                                                </p>
                                                <p class="drink-cups">
                                                    Cups: <span class="cups-value">40 pcs</span>
                                                </p>
                                                <p class="drink-servings">
                                                    <span class="servings-value">14</span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="product-card" data-category="frappe-series" data-id="black-forrest">
                                    <div class="card-actions">
                                        <button type="button" class="grid-action-btn" aria-label="More actions"
                                            title="More actions">
                                            <i class="fa-solid fa-ellipsis-vertical"></i>
                                        </button>
                                        <div class="grid-delete-menu">
                                            <button type="button" class="grid-delete-option btn-delete"
                                                aria-label="Delete product">
                                                <i class="fa-solid fa-trash"></i> Delete
                                            </button>
                                        </div>
                                        <button type="button" class="grid-edit-btn btn-edit" aria-label="Edit product"
                                            title="Edit">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <div class="list-action-buttons">
                                            <button type="button" class="list-action-btn btn-edit"
                                                aria-label="Edit product" title="Edit">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>
                                            <button type="button" class="list-action-btn btn-delete"
                                                aria-label="Delete product" title="Delete">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-image">
                                        <div class="card-image-placeholder">
                                            <img src="../../../img/Gemini_Generated_Image_op5lniop5lniop5l 1.png"
                                                alt="Black Forrest">
                                        </div>
                                    </div>
                                    <div class="card-info">
                                        <div class="card-mid">
                                            <p class="card-name">Black Forrest</p>
                                        </div>
                                        <div class="card-footer">
                                            <p class="card-price">₱169.00</p>
                                            <div class="drink-stock">
                                                <p class="drink-status available">
                                                    <span class="status-dot"></span> Available
                                                </p>
                                                <p class="drink-ingredient">
                                                    Ingredients: <span>Sufficient</span>
                                                </p>
                                                <p class="drink-cups">
                                                    Cups: <span class="cups-value">40 pcs</span>
                                                </p>
                                                <p class="drink-servings">
                                                    <span class="servings-value">12</span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="product-card" data-category="frappe-series" data-id="biscoff-frappe">
                                    <div class="card-actions">
                                        <button type="button" class="grid-action-btn" aria-label="More actions"
                                            title="More actions">
                                            <i class="fa-solid fa-ellipsis-vertical"></i>
                                        </button>
                                        <div class="grid-delete-menu">
                                            <button type="button" class="grid-delete-option btn-delete"
                                                aria-label="Delete product">
                                                <i class="fa-solid fa-trash"></i> Delete
                                            </button>
                                        </div>
                                        <button type="button" class="grid-edit-btn btn-edit" aria-label="Edit product"
                                            title="Edit">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <div class="list-action-buttons">
                                            <button type="button" class="list-action-btn btn-edit"
                                                aria-label="Edit product" title="Edit">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>
                                            <button type="button" class="list-action-btn btn-delete"
                                                aria-label="Delete product" title="Delete">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-image">
                                        <div class="card-image-placeholder">
                                            <img src="../../../img/Biscoff frappe.png" alt="Biscoff Frappe">
                                        </div>
                                    </div>
                                    <div class="card-info">
                                        <div class="card-mid">
                                            <p class="card-name">Biscoff Frappe</p>
                                        </div>
                                        <div class="card-footer">
                                            <p class="card-price">₱169.00</p>
                                            <div class="drink-stock">
                                                <p class="drink-status available">
                                                    <span class="status-dot"></span> Available
                                                </p>
                                                <p class="drink-ingredient">
                                                    Ingredients: <span>Sufficient</span>
                                                </p>
                                                <p class="drink-cups">
                                                    Cups: <span class="cups-value">40 pcs</span>
                                                </p>
                                                <p class="drink-servings">
                                                    <span class="servings-value">20</span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- ================= RICE MEAL ================= -->
                                <div class="product-card" data-category="rice-meal" data-id="honey-gochujang-katsu">
                                    <div class="card-actions">
                                        <button type="button" class="grid-action-btn" aria-label="More actions"
                                            title="More actions">
                                            <i class="fa-solid fa-ellipsis-vertical"></i>
                                        </button>
                                        <div class="grid-delete-menu">
                                            <button type="button" class="grid-delete-option btn-delete"
                                                aria-label="Delete product">
                                                <i class="fa-solid fa-trash"></i> Delete
                                            </button>
                                        </div>
                                        <button type="button" class="grid-edit-btn btn-edit" aria-label="Edit product"
                                            title="Edit">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <div class="list-action-buttons">
                                            <button type="button" class="list-action-btn btn-edit"
                                                aria-label="Edit product" title="Edit">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>
                                            <button type="button" class="list-action-btn btn-delete"
                                                aria-label="Delete product" title="Delete">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-image">
                                        <div class="card-image-placeholder">
                                            <img src="../../../img/Honey Gochujang Katsu 1.png"
                                                alt="Honey Gochujang Katsu">
                                        </div>
                                    </div>
                                    <div class="card-info">
                                        <div class="card-mid">
                                            <p class="card-name">Honey Gochujang Katsu</p>
                                        </div>
                                        <div class="card-footer">
                                            <p class="card-price">₱219.00</p>
                                            <div class="drink-stock">
                                                <p class="drink-status available">
                                                    <span class="status-dot"></span> Available
                                                </p>
                                                <p class="drink-ingredient">
                                                    Ingredients: <span>Sufficient</span>
                                                </p>
                                                <p class="drink-cups">
                                                    Cups: <span class="cups-value">40 pcs</span>
                                                </p>
                                                <p class="drink-servings">
                                                    <span class="servings-value">12</span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="product-card" data-category="rice-meal" data-id="dak-galbi">
                                    <div class="card-actions">
                                        <button type="button" class="grid-action-btn" aria-label="More actions"
                                            title="More actions">
                                            <i class="fa-solid fa-ellipsis-vertical"></i>
                                        </button>
                                        <div class="grid-delete-menu">
                                            <button type="button" class="grid-delete-option btn-delete"
                                                aria-label="Delete product">
                                                <i class="fa-solid fa-trash"></i> Delete
                                            </button>
                                        </div>
                                        <button type="button" class="grid-edit-btn btn-edit" aria-label="Edit product"
                                            title="Edit">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <div class="list-action-buttons">
                                            <button type="button" class="list-action-btn btn-edit"
                                                aria-label="Edit product" title="Edit">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>
                                            <button type="button" class="list-action-btn btn-delete"
                                                aria-label="Delete product" title="Delete">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-image">
                                        <div class="card-image-placeholder">
                                            <img src="../../../img/Dak galbi 1.png" alt="Dak Galbi">
                                        </div>
                                    </div>
                                    <div class="card-info">
                                        <div class="card-mid">
                                            <p class="card-name">Dak Galbi</p>
                                        </div>
                                        <div class="card-footer">
                                            <p class="card-price">₱199.00</p>
                                            <div class="drink-stock">
                                                <p class="drink-status available">
                                                    <span class="status-dot"></span> Available
                                                </p>
                                                <p class="drink-ingredient">
                                                    Ingredients: <span>Sufficient</span>
                                                </p>
                                                <p class="drink-cups">
                                                    Cups: <span class="cups-value">40 pcs</span>
                                                </p>
                                                <p class="drink-servings">
                                                    <span class="servings-value">15</span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="product-card" data-category="rice-meal" data-id="salted-egg-fish-fillet">
                                    <div class="card-actions">
                                        <button type="button" class="grid-action-btn" aria-label="More actions"
                                            title="More actions">
                                            <i class="fa-solid fa-ellipsis-vertical"></i>
                                        </button>
                                        <div class="grid-delete-menu">
                                            <button type="button" class="grid-delete-option btn-delete"
                                                aria-label="Delete product">
                                                <i class="fa-solid fa-trash"></i> Delete
                                            </button>
                                        </div>
                                        <button type="button" class="grid-edit-btn btn-edit" aria-label="Edit product"
                                            title="Edit">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <div class="list-action-buttons">
                                            <button type="button" class="list-action-btn btn-edit"
                                                aria-label="Edit product" title="Edit">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>
                                            <button type="button" class="list-action-btn btn-delete"
                                                aria-label="Delete product" title="Delete">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-image">
                                        <div class="card-image-placeholder">
                                            <img src="../../../img/Salted egg Fish fillet.png"
                                                alt="Salted Egg Fish Fillet">
                                        </div>
                                    </div>
                                    <div class="card-info">
                                        <div class="card-mid">
                                            <p class="card-name">Salted Egg Fish Fillet</p>
                                        </div>
                                        <div class="card-footer">
                                            <p class="card-price">₱229.00</p>
                                            <div class="drink-stock">
                                                <p class="drink-status available">
                                                    <span class="status-dot"></span> Available
                                                </p>
                                                <p class="drink-ingredient">
                                                    Ingredients: <span>Sufficient</span>
                                                </p>
                                                <p class="drink-cups">
                                                    Cups: <span class="cups-value">40 pcs</span>
                                                </p>
                                                <p class="drink-servings">
                                                    <span class="servings-value">10</span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- ================= LIGHT SNACK ================= -->
                                <div class="product-card" data-category="light-snack" data-id="cheezy-fries">
                                    <div class="card-actions">
                                        <button type="button" class="grid-action-btn" aria-label="More actions"
                                            title="More actions">
                                            <i class="fa-solid fa-ellipsis-vertical"></i>
                                        </button>
                                        <div class="grid-delete-menu">
                                            <button type="button" class="grid-delete-option btn-delete"
                                                aria-label="Delete product">
                                                <i class="fa-solid fa-trash"></i> Delete
                                            </button>
                                        </div>
                                        <button type="button" class="grid-edit-btn btn-edit" aria-label="Edit product"
                                            title="Edit">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <div class="list-action-buttons">
                                            <button type="button" class="list-action-btn btn-edit"
                                                aria-label="Edit product" title="Edit">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>
                                            <button type="button" class="list-action-btn btn-delete"
                                                aria-label="Delete product" title="Delete">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-image">
                                        <div class="card-image-placeholder">
                                            <img src="../../../img/Cheezy Fries.png" alt="Cheezy Fries">
                                        </div>
                                    </div>
                                    <div class="card-info">
                                        <div class="card-mid">
                                            <p class="card-name">Cheezy Fries</p>
                                        </div>
                                        <div class="card-footer">
                                            <p class="card-price">₱129.00</p>
                                            <div class="drink-stock">
                                                <p class="drink-status available">
                                                    <span class="status-dot"></span> Available
                                                </p>
                                                <p class="drink-ingredient">
                                                    Ingredients: <span>Sufficient</span>
                                                </p>
                                                <p class="drink-cups">
                                                    Cups: <span class="cups-value">40 pcs</span>
                                                </p>
                                                <p class="drink-servings">
                                                    <span class="servings-value">35</span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="product-card" data-category="light-snack"
                                    data-id="fries-and-chicken-tenders">
                                    <div class="card-actions">
                                        <button type="button" class="grid-action-btn" aria-label="More actions"
                                            title="More actions">
                                            <i class="fa-solid fa-ellipsis-vertical"></i>
                                        </button>
                                        <div class="grid-delete-menu">
                                            <button type="button" class="grid-delete-option btn-delete"
                                                aria-label="Delete product">
                                                <i class="fa-solid fa-trash"></i> Delete
                                            </button>
                                        </div>
                                        <button type="button" class="grid-edit-btn btn-edit" aria-label="Edit product"
                                            title="Edit">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <div class="list-action-buttons">
                                            <button type="button" class="list-action-btn btn-edit"
                                                aria-label="Edit product" title="Edit">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>
                                            <button type="button" class="list-action-btn btn-delete"
                                                aria-label="Delete product" title="Delete">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-image">
                                        <div class="card-image-placeholder">
                                            <img src="../../../img/Fries and Chicken tenders.png"
                                                alt="Fries & Chicken Tenders">
                                        </div>
                                    </div>
                                    <div class="card-info">
                                        <div class="card-mid">
                                            <p class="card-name">Fries & Chicken Tenders</p>
                                        </div>
                                        <div class="card-footer">
                                            <p class="card-price">₱219.00</p>
                                            <div class="drink-stock">
                                                <p class="drink-status available">
                                                    <span class="status-dot"></span> Available
                                                </p>
                                                <p class="drink-ingredient">
                                                    Ingredients: <span>Sufficient</span>
                                                </p>
                                                <p class="drink-cups">
                                                    Cups: <span class="cups-value">40 pcs</span>
                                                </p>
                                                <p class="drink-servings">
                                                    <span class="servings-value">18</span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="product-card" data-category="light-snack" data-id="onion-rings">
                                    <div class="card-actions">
                                        <button type="button" class="grid-action-btn" aria-label="More actions"
                                            title="More actions">
                                            <i class="fa-solid fa-ellipsis-vertical"></i>
                                        </button>
                                        <div class="grid-delete-menu">
                                            <button type="button" class="grid-delete-option btn-delete"
                                                aria-label="Delete product">
                                                <i class="fa-solid fa-trash"></i> Delete
                                            </button>
                                        </div>
                                        <button type="button" class="grid-edit-btn btn-edit" aria-label="Edit product"
                                            title="Edit">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <div class="list-action-buttons">
                                            <button type="button" class="list-action-btn btn-edit"
                                                aria-label="Edit product" title="Edit">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>
                                            <button type="button" class="list-action-btn btn-delete"
                                                aria-label="Delete product" title="Delete">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-image">
                                        <div class="card-image-placeholder">
                                            <img src="../../../img/Onion rings.png" alt="Onion Rings">
                                        </div>
                                    </div>
                                    <div class="card-info">
                                        <div class="card-mid">
                                            <p class="card-name">Onion Rings</p>
                                        </div>
                                        <div class="card-footer">
                                            <p class="card-price">₱129.00</p>
                                            <div class="drink-stock">
                                                <p class="drink-status available">
                                                    <span class="status-dot"></span> Available
                                                </p>
                                                <p class="drink-ingredient">
                                                    Ingredients: <span>Sufficient</span>
                                                </p>
                                                <p class="drink-cups">
                                                    Cups: <span class="cups-value">40 pcs</span>
                                                </p>
                                                <p class="drink-servings">
                                                    <span class="servings-value">25</span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="product-card" data-category="light-snack" data-id="nachos">
                                    <div class="card-actions">
                                        <button type="button" class="grid-action-btn" aria-label="More actions"
                                            title="More actions">
                                            <i class="fa-solid fa-ellipsis-vertical"></i>
                                        </button>
                                        <div class="grid-delete-menu">
                                            <button type="button" class="grid-delete-option btn-delete"
                                                aria-label="Delete product">
                                                <i class="fa-solid fa-trash"></i> Delete
                                            </button>
                                        </div>
                                        <button type="button" class="grid-edit-btn btn-edit" aria-label="Edit product"
                                            title="Edit">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <div class="list-action-buttons">
                                            <button type="button" class="list-action-btn btn-edit"
                                                aria-label="Edit product" title="Edit">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>
                                            <button type="button" class="list-action-btn btn-delete"
                                                aria-label="Delete product" title="Delete">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-image">
                                        <div class="card-image-placeholder">
                                            <img src="../../../img/Nachos.png" alt="Nachos">
                                        </div>
                                    </div>
                                    <div class="card-info">
                                        <div class="card-mid">
                                            <p class="card-name">Nachos</p>
                                        </div>
                                        <div class="card-footer">
                                            <p class="card-price">₱179.00</p>
                                            <div class="drink-stock">
                                                <p class="drink-status available">
                                                    <span class="status-dot"></span> Available
                                                </p>
                                                <p class="drink-ingredient">
                                                    Ingredients: <span>Sufficient</span>
                                                </p>
                                                <p class="drink-cups">
                                                    Cups: <span class="cups-value">40 pcs</span>
                                                </p>
                                                <p class="drink-servings">
                                                    <span class="servings-value">16</span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- ================= PASTA ================= -->
                                <div class="product-card" data-category="pasta" data-id="chicken-alfredo">
                                    <div class="card-actions">
                                        <button type="button" class="grid-action-btn" aria-label="More actions"
                                            title="More actions">
                                            <i class="fa-solid fa-ellipsis-vertical"></i>
                                        </button>
                                        <div class="grid-delete-menu">
                                            <button type="button" class="grid-delete-option btn-delete"
                                                aria-label="Delete product">
                                                <i class="fa-solid fa-trash"></i> Delete
                                            </button>
                                        </div>
                                        <button type="button" class="grid-edit-btn btn-edit" aria-label="Edit product"
                                            title="Edit">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <div class="list-action-buttons">
                                            <button type="button" class="list-action-btn btn-edit"
                                                aria-label="Edit product" title="Edit">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>
                                            <button type="button" class="list-action-btn btn-delete"
                                                aria-label="Delete product" title="Delete">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-image">
                                        <div class="card-image-placeholder">
                                            <img src="../../../img/Chicken Alfredo 1.png" alt="Chicken Alfredo">
                                        </div>
                                    </div>
                                    <div class="card-info">
                                        <div class="card-mid">
                                            <p class="card-name">Chicken Alfredo</p>
                                        </div>
                                        <div class="card-footer">
                                            <p class="card-price">₱239.00</p>
                                            <div class="drink-stock">
                                                <p class="drink-status available">
                                                    <span class="status-dot"></span> Available
                                                </p>
                                                <p class="drink-ingredient">
                                                    Ingredients: <span>Sufficient</span>
                                                </p>
                                                <p class="drink-cups">
                                                    Cups: <span class="cups-value">40 pcs</span>
                                                </p>
                                                <p class="drink-servings">
                                                    <span class="servings-value">18</span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="product-card" data-category="pasta" data-id="chicken-pesto">
                                    <div class="card-actions">
                                        <button type="button" class="grid-action-btn" aria-label="More actions"
                                            title="More actions">
                                            <i class="fa-solid fa-ellipsis-vertical"></i>
                                        </button>
                                        <div class="grid-delete-menu">
                                            <button type="button" class="grid-delete-option btn-delete"
                                                aria-label="Delete product">
                                                <i class="fa-solid fa-trash"></i> Delete
                                            </button>
                                        </div>
                                        <button type="button" class="grid-edit-btn btn-edit" aria-label="Edit product"
                                            title="Edit">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <div class="list-action-buttons">
                                            <button type="button" class="list-action-btn btn-edit"
                                                aria-label="Edit product" title="Edit">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>
                                            <button type="button" class="list-action-btn btn-delete"
                                                aria-label="Delete product" title="Delete">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-image">
                                        <div class="card-image-placeholder">
                                            <img src="../../../img/Chicken Pesto 1.png" alt="Chicken Pesto">
                                        </div>
                                    </div>
                                    <div class="card-info">
                                        <div class="card-mid">
                                            <p class="card-name">Chicken Pesto</p>
                                        </div>
                                        <div class="card-footer">
                                            <p class="card-price">₱239.00</p>
                                            <div class="drink-stock">
                                                <p class="drink-status available">
                                                    <span class="status-dot"></span> Available
                                                </p>
                                                <p class="drink-ingredient">
                                                    Ingredients: <span>Sufficient</span>
                                                </p>
                                                <p class="drink-cups">
                                                    Cups: <span class="cups-value">40 pcs</span>
                                                </p>
                                                <p class="drink-servings">
                                                    <span class="servings-value">16</span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="product-card" data-category="pasta" data-id="aglio-olio">
                                    <div class="card-actions">
                                        <button type="button" class="grid-action-btn" aria-label="More actions"
                                            title="More actions">
                                            <i class="fa-solid fa-ellipsis-vertical"></i>
                                        </button>
                                        <div class="grid-delete-menu">
                                            <button type="button" class="grid-delete-option btn-delete"
                                                aria-label="Delete product">
                                                <i class="fa-solid fa-trash"></i> Delete
                                            </button>
                                        </div>
                                        <button type="button" class="grid-edit-btn btn-edit" aria-label="Edit product"
                                            title="Edit">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <div class="list-action-buttons">
                                            <button type="button" class="list-action-btn btn-edit"
                                                aria-label="Edit product" title="Edit">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>
                                            <button type="button" class="list-action-btn btn-delete"
                                                aria-label="Delete product" title="Delete">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-image">
                                        <div class="card-image-placeholder">
                                            <img src="../../../img/Aglio olio sardines 1.png" alt="Aglio Olio">
                                        </div>
                                    </div>
                                    <div class="card-info">
                                        <div class="card-mid">
                                            <p class="card-name">Aglio Olio</p>
                                        </div>
                                        <div class="card-footer">
                                            <p class="card-price">₱239.00</p>
                                            <div class="drink-stock">
                                                <p class="drink-status available">
                                                    <span class="status-dot"></span> Available
                                                </p>
                                                <p class="drink-ingredient">
                                                    Ingredients: <span>Sufficient</span>
                                                </p>
                                                <p class="drink-cups">
                                                    Cups: <span class="cups-value">40 pcs</span>
                                                </p>
                                                <p class="drink-servings">
                                                    <span class="servings-value">14</span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="product-card" data-category="pasta" data-id="carbonara">
                                    <div class="card-actions">
                                        <button type="button" class="grid-action-btn" aria-label="More actions"
                                            title="More actions">
                                            <i class="fa-solid fa-ellipsis-vertical"></i>
                                        </button>
                                        <div class="grid-delete-menu">
                                            <button type="button" class="grid-delete-option btn-delete"
                                                aria-label="Delete product">
                                                <i class="fa-solid fa-trash"></i> Delete
                                            </button>
                                        </div>
                                        <button type="button" class="grid-edit-btn btn-edit" aria-label="Edit product"
                                            title="Edit">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <div class="list-action-buttons">
                                            <button type="button" class="list-action-btn btn-edit"
                                                aria-label="Edit product" title="Edit">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>
                                            <button type="button" class="list-action-btn btn-delete"
                                                aria-label="Delete product" title="Delete">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-image">
                                        <div class="card-image-placeholder">
                                            <img src="../../../img/Carbonara 1.png" alt="Carbonara">
                                        </div>
                                    </div>
                                    <div class="card-info">
                                        <div class="card-mid">
                                            <p class="card-name">Carbonara</p>
                                        </div>
                                        <div class="card-footer">
                                            <p class="card-price">₱249.00</p>
                                            <div class="drink-stock">
                                                <p class="drink-status available">
                                                    <span class="status-dot"></span> Available
                                                </p>
                                                <p class="drink-ingredient">
                                                    Ingredients: <span>Sufficient</span>
                                                </p>
                                                <p class="drink-cups">
                                                    Cups: <span class="cups-value">40 pcs</span>
                                                </p>
                                                <p class="drink-servings">
                                                    <span class="servings-value">20</span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- ================= WAFFLES ================= -->
                                <div class="product-card" data-category="waffle" data-id="waffle-biscoff">
                                    <div class="card-actions">
                                        <button type="button" class="grid-action-btn" aria-label="More actions"
                                            title="More actions">
                                            <i class="fa-solid fa-ellipsis-vertical"></i>
                                        </button>
                                        <div class="grid-delete-menu">
                                            <button type="button" class="grid-delete-option btn-delete"
                                                aria-label="Delete product">
                                                <i class="fa-solid fa-trash"></i> Delete
                                            </button>
                                        </div>
                                        <button type="button" class="grid-edit-btn btn-edit" aria-label="Edit product"
                                            title="Edit">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <div class="list-action-buttons">
                                            <button type="button" class="list-action-btn btn-edit"
                                                aria-label="Edit product" title="Edit">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>
                                            <button type="button" class="list-action-btn btn-delete"
                                                aria-label="Delete product" title="Delete">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-image">
                                        <div class="card-image-placeholder">
                                            <img src="../../../img/Biscoff waffle.png" alt="Lolly Waffle Biscoff">
                                        </div>
                                    </div>
                                    <div class="card-info">
                                        <div class="card-mid">
                                            <p class="card-name">Lolly Waffle Biscoff</p>
                                        </div>
                                        <div class="card-footer">
                                            <p class="card-price">₱119.00</p>
                                            <div class="drink-stock">
                                                <p class="drink-status available">
                                                    <span class="status-dot"></span> Available
                                                </p>
                                                <p class="drink-ingredient">
                                                    Ingredients: <span>Sufficient</span>
                                                </p>
                                                <p class="drink-cups">
                                                    Cups: <span class="cups-value">40 pcs</span>
                                                </p>
                                                <p class="drink-servings">
                                                    <span class="servings-value">20</span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="product-card" data-category="waffle" data-id="waffle-chocolate">
                                    <div class="card-actions">
                                        <button type="button" class="grid-action-btn" aria-label="More actions"
                                            title="More actions">
                                            <i class="fa-solid fa-ellipsis-vertical"></i>
                                        </button>
                                        <div class="grid-delete-menu">
                                            <button type="button" class="grid-delete-option btn-delete"
                                                aria-label="Delete product">
                                                <i class="fa-solid fa-trash"></i> Delete
                                            </button>
                                        </div>
                                        <button type="button" class="grid-edit-btn btn-edit" aria-label="Edit product"
                                            title="Edit">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <div class="list-action-buttons">
                                            <button type="button" class="list-action-btn btn-edit"
                                                aria-label="Edit product" title="Edit">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>
                                            <button type="button" class="list-action-btn btn-delete"
                                                aria-label="Delete product" title="Delete">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-image">
                                        <div class="card-image-placeholder">
                                            <img src="../../../img/Chocolate waffle.png" alt="Lolly Waffle Chocolate">
                                        </div>
                                    </div>
                                    <div class="card-info">
                                        <div class="card-mid">
                                            <p class="card-name">Lolly Waffle Chocolate</p>
                                        </div>
                                        <div class="card-footer">
                                            <p class="card-price">₱89.00</p>
                                            <div class="drink-stock">
                                                <p class="drink-status available">
                                                    <span class="status-dot"></span> Available
                                                </p>
                                                <p class="drink-ingredient">
                                                    Ingredients: <span>Sufficient</span>
                                                </p>
                                                <p class="drink-cups">
                                                    Cups: <span class="cups-value">40 pcs</span>
                                                </p>
                                                <p class="drink-servings">
                                                    <span class="servings-value">22</span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="product-card" data-category="waffle" data-id="waffle-matcha">
                                    <div class="card-actions">
                                        <button type="button" class="grid-action-btn" aria-label="More actions"
                                            title="More actions">
                                            <i class="fa-solid fa-ellipsis-vertical"></i>
                                        </button>
                                        <div class="grid-delete-menu">
                                            <button type="button" class="grid-delete-option btn-delete"
                                                aria-label="Delete product">
                                                <i class="fa-solid fa-trash"></i> Delete
                                            </button>
                                        </div>
                                        <button type="button" class="grid-edit-btn btn-edit" aria-label="Edit product"
                                            title="Edit">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <div class="list-action-buttons">
                                            <button type="button" class="list-action-btn btn-edit"
                                                aria-label="Edit product" title="Edit">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>
                                            <button type="button" class="list-action-btn btn-delete"
                                                aria-label="Delete product" title="Delete">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-image">
                                        <div class="card-image-placeholder">
                                            <img src="../../../img/Matcha waffle.png" alt="Lolly Waffle Matcha">
                                        </div>
                                    </div>
                                    <div class="card-info">
                                        <div class="card-mid">
                                            <p class="card-name">Lolly Waffle Matcha</p>
                                        </div>
                                        <div class="card-footer">
                                            <p class="card-price">₱89.00</p>
                                            <div class="drink-stock">
                                                <p class="drink-status available">
                                                    <span class="status-dot"></span> Available
                                                </p>
                                                <p class="drink-ingredient">
                                                    Ingredients: <span>Sufficient</span>
                                                </p>
                                                <p class="drink-cups">
                                                    Cups: <span class="cups-value">40 pcs</span>
                                                </p>
                                                <p class="drink-servings">
                                                    <span class="servings-value">20</span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="product-card" data-category="waffle" data-id="waffle-strawberry">
                                    <div class="card-actions">
                                        <button type="button" class="grid-action-btn" aria-label="More actions"
                                            title="More actions">
                                            <i class="fa-solid fa-ellipsis-vertical"></i>
                                        </button>
                                        <div class="grid-delete-menu">
                                            <button type="button" class="grid-delete-option btn-delete"
                                                aria-label="Delete product">
                                                <i class="fa-solid fa-trash"></i> Delete
                                            </button>
                                        </div>
                                        <button type="button" class="grid-edit-btn btn-edit" aria-label="Edit product"
                                            title="Edit">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <div class="list-action-buttons">
                                            <button type="button" class="list-action-btn btn-edit"
                                                aria-label="Edit product" title="Edit">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>
                                            <button type="button" class="list-action-btn btn-delete"
                                                aria-label="Delete product" title="Delete">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-image">
                                        <div class="card-image-placeholder">
                                            <img src="../../../img/Strawberry waffle.png" alt="Lolly Waffle Strawberry">
                                        </div>
                                    </div>
                                    <div class="card-info">
                                        <div class="card-mid">
                                            <p class="card-name">Lolly Waffle Strawberry</p>
                                        </div>
                                        <div class="card-footer">
                                            <p class="card-price">₱89.00</p>
                                            <div class="drink-stock">
                                                <p class="drink-status available">
                                                    <span class="status-dot"></span> Available
                                                </p>
                                                <p class="drink-ingredient">
                                                    Ingredients: <span>Sufficient</span>
                                                </p>
                                                <p class="drink-cups">
                                                    Cups: <span class="cups-value">40 pcs</span>
                                                </p>
                                                <p class="drink-servings">
                                                    <span class="servings-value">20</span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="product-card" data-category="waffle" data-id="waffle-oreo">
                                    <div class="card-actions">
                                        <button type="button" class="grid-action-btn" aria-label="More actions"
                                            title="More actions">
                                            <i class="fa-solid fa-ellipsis-vertical"></i>
                                        </button>
                                        <div class="grid-delete-menu">
                                            <button type="button" class="grid-delete-option btn-delete"
                                                aria-label="Delete product">
                                                <i class="fa-solid fa-trash"></i> Delete
                                            </button>
                                        </div>
                                        <button type="button" class="grid-edit-btn btn-edit" aria-label="Edit product"
                                            title="Edit">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <div class="list-action-buttons">
                                            <button type="button" class="list-action-btn btn-edit"
                                                aria-label="Edit product" title="Edit">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>
                                            <button type="button" class="list-action-btn btn-delete"
                                                aria-label="Delete product" title="Delete">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-image">
                                        <div class="card-image-placeholder">
                                            <img src="../../../img/Oreo waffle.png" alt="Lolly Waffle Oreo">
                                        </div>
                                    </div>
                                    <div class="card-info">
                                        <div class="card-mid">
                                            <p class="card-name">Lolly Waffle Oreo</p>
                                        </div>
                                        <div class="card-footer">
                                            <p class="card-price">₱89.00</p>
                                            <div class="drink-stock">
                                                <p class="drink-status available">
                                                    <span class="status-dot"></span> Available
                                                </p>
                                                <p class="drink-ingredient">
                                                    Ingredients: <span>Sufficient</span>
                                                </p>
                                                <p class="drink-cups">
                                                    Cups: <span class="cups-value">40 pcs</span>
                                                </p>
                                                <p class="drink-servings">
                                                    <span class="servings-value">20</span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="product-card" data-category="waffle" data-id="waffle-tiramisu">
                                    <div class="card-actions">
                                        <button type="button" class="grid-action-btn" aria-label="More actions"
                                            title="More actions">
                                            <i class="fa-solid fa-ellipsis-vertical"></i>
                                        </button>
                                        <div class="grid-delete-menu">
                                            <button type="button" class="grid-delete-option btn-delete"
                                                aria-label="Delete product">
                                                <i class="fa-solid fa-trash"></i> Delete
                                            </button>
                                        </div>
                                        <button type="button" class="grid-edit-btn btn-edit" aria-label="Edit product"
                                            title="Edit">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <div class="list-action-buttons">
                                            <button type="button" class="list-action-btn btn-edit"
                                                aria-label="Edit product" title="Edit">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>
                                            <button type="button" class="list-action-btn btn-delete"
                                                aria-label="Delete product" title="Delete">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-image">
                                        <div class="card-image-placeholder">
                                            <img src="../../../img/tiramisu waffle.png" alt="Lolly Waffle Tiramisu">
                                        </div>
                                    </div>
                                    <div class="card-info">
                                        <div class="card-mid">
                                            <p class="card-name">Lolly Waffle Tiramisu</p>
                                        </div>
                                        <div class="card-footer">
                                            <p class="card-price">₱89.00</p>
                                            <div class="drink-stock">
                                                <p class="drink-status available">
                                                    <span class="status-dot"></span> Available
                                                </p>
                                                <p class="drink-ingredient">
                                                    Ingredients: <span>Sufficient</span>
                                                </p>
                                                <p class="drink-cups">
                                                    Cups: <span class="cups-value">40 pcs</span>
                                                </p>
                                                <p class="drink-servings">
                                                    <span class="servings-value">18</span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- ================= QUESADILLA ================= -->
                                <div class="product-card" data-category="quesadilla" data-id="chicken-quesadilla">
                                    <div class="card-actions">
                                        <button type="button" class="grid-action-btn" aria-label="More actions"
                                            title="More actions">
                                            <i class="fa-solid fa-ellipsis-vertical"></i>
                                        </button>
                                        <div class="grid-delete-menu">
                                            <button type="button" class="grid-delete-option btn-delete"
                                                aria-label="Delete product">
                                                <i class="fa-solid fa-trash"></i> Delete
                                            </button>
                                        </div>
                                        <button type="button" class="grid-edit-btn btn-edit" aria-label="Edit product"
                                            title="Edit">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <div class="list-action-buttons">
                                            <button type="button" class="list-action-btn btn-edit"
                                                aria-label="Edit product" title="Edit">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>
                                            <button type="button" class="list-action-btn btn-delete"
                                                aria-label="Delete product" title="Delete">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-image">
                                        <div class="card-image-placeholder">
                                            <img src="../../../img/Chicken Quesadilla.png" alt="Chicken Quesadilla">
                                        </div>
                                    </div>
                                    <div class="card-info">
                                        <div class="card-mid">
                                            <p class="card-name">Chicken Quesadilla</p>
                                        </div>
                                        <div class="card-footer">
                                            <p class="card-price">₱179.00</p>
                                            <div class="drink-stock">
                                                <p class="drink-status available">
                                                    <span class="status-dot"></span> Available
                                                </p>
                                                <p class="drink-ingredient">
                                                    Ingredients: <span>Sufficient</span>
                                                </p>
                                                <p class="drink-cups">
                                                    Cups: <span class="cups-value">40 pcs</span>
                                                </p>
                                                <p class="drink-servings">
                                                    <span class="servings-value">20</span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="product-card" data-category="quesadilla" data-id="beef-quesadilla">
                                    <div class="card-actions">
                                        <button type="button" class="grid-action-btn" aria-label="More actions"
                                            title="More actions">
                                            <i class="fa-solid fa-ellipsis-vertical"></i>
                                        </button>
                                        <div class="grid-delete-menu">
                                            <button type="button" class="grid-delete-option btn-delete"
                                                aria-label="Delete product">
                                                <i class="fa-solid fa-trash"></i> Delete
                                            </button>
                                        </div>
                                        <button type="button" class="grid-edit-btn btn-edit" aria-label="Edit product"
                                            title="Edit">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <div class="list-action-buttons">
                                            <button type="button" class="list-action-btn btn-edit"
                                                aria-label="Edit product" title="Edit">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>
                                            <button type="button" class="list-action-btn btn-delete"
                                                aria-label="Delete product" title="Delete">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-image">
                                        <div class="card-image-placeholder">
                                            <img src="../../../img/Beef Quesadilla.png" alt="Beef Quesadilla">
                                        </div>
                                    </div>
                                    <div class="card-info">
                                        <div class="card-mid">
                                            <p class="card-name">Beef Quesadilla</p>
                                        </div>
                                        <div class="card-footer">
                                            <p class="card-price">₱179.00</p>
                                            <div class="drink-stock">
                                                <p class="drink-status available">
                                                    <span class="status-dot"></span> Available
                                                </p>
                                                <p class="drink-ingredient">
                                                    Ingredients: <span>Sufficient</span>
                                                </p>
                                                <p class="drink-cups">
                                                    Cups: <span class="cups-value">40 pcs</span>
                                                </p>
                                                <p class="drink-servings">
                                                    <span class="servings-value">22</span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- No Results Message -->
                            <div class="menu-no-data" id="noMenuMessage" style="display: none;">
                                <i class="fa-solid fa-mug-hot"></i>
                                <p>No menu items found</p>
                            </div>
                        </div>
                    </section>
                </div><!-- /.menu-container-card -->

            </div>
        </div>
    </div>


    <!-- ================= ADD PRODUCT MODAL ================= -->
    <div class="modal-overlay" id="addProductModal">
        <div class="modal-box">
            <div class="modal-header">
                <div>
                    <h2>Add New Menu Item</h2>
                    <p>Enter the details below to add a new product to your menu.</p>
                </div>
                <button class="modal-close" id="closeAddModal" aria-label="Close">&times;</button>
            </div>

            <div class="modal-body">
                <!-- Left Column -->
                <div class="modal-col">
                    <div class="panel">
                        <h3>General Information</h3>
                        <div class="field">
                            <label>Product Name <span class="required">*</span></label>
                            <input type="text" id="productName" placeholder="e.g. Iced Caramel Macchiato">
                        </div>
                        <div class="field">
                            <label>Category <span class="required">*</span></label>
                            <select id="productCategory">
                                <option value="" disabled selected>Select Category</option>
                                <option value="coffee">Coffee</option>
                                <option value="non-coffee">Non-Coffee</option>
                                <option value="special-coffee">Special Coffee</option>
                                <option value="matcha-fusion">Matcha Fusion</option>
                                <option value="smoothie">Smoothie</option>
                                <option value="frappe-series">Frappe Series</option>
                                <option value="rice-meal">Rice Meal</option>
                                <option value="light-snack">Light Snack</option>
                                <option value="pasta">Pasta</option>
                                <option value="waffle">Waffles</option>
                                <option value="quesadilla">Quesadilla</option>
                            </select>
                        </div>
                        <div class="field">
                            <label>Product Code / SKU <span class="optional">(Optional)</span></label>
                            <input type="text" id="productCode" placeholder="e.g. COF-001">
                        </div>
                    </div>

                    <div class="panel">
                        <h3>Pricing & Stock</h3>
                        <div class="settings-row">
                            <div class="field">
                                <label>Selling Price <span class="required">*</span></label>
                                <div class="price-input">
                                    <span>₱</span>
                                    <input type="number" id="sellingPrice" placeholder="0.00" min="0" step="0.01">
                                </div>
                            </div>
                            <div class="field">
                                <label>Cost Price <span class="optional">(Optional)</span></label>
                                <div class="price-input">
                                    <span>₱</span>
                                    <input type="number" id="costPrice" placeholder="0.00" min="0" step="0.01">
                                </div>
                            </div>
                        </div>
                        <div class="settings-row">
                            <div class="field">
                                <label>Cup Stock <span class="optional">(pcs)</span></label>
                                <input type="number" id="stockQty" placeholder="40" min="0">
                            </div>
                            <div class="field">
                                <label>Initial Servings</label>
                                <input type="number" id="servingsQty" placeholder="25" min="0">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column -->
                <div class="modal-col">
                    <div class="panel">
                        <h3>Product Media</h3>
                        <div class="image-upload-row">
                            <div class="upload-box" id="uploadBox">
                                <i class="fa-solid fa-cloud-arrow-up"></i>
                                <p class="upload-title">Drop your image here</p>
                                <p class="upload-sub">Supports PNG, JPG (max 2MB)</p>
                                <label class="choose-file-btn" for="productImageInput">
                                    <i class="fa-solid fa-upload"></i> Browse File
                                </label>
                                <input type="file" id="productImageInput" accept="image/*" style="display:none;">
                            </div>
                            <div class="image-preview-box" id="imagePreviewBox" style="display:none;">
                                <img id="imagePreview" src="" alt="Preview">
                                <button type="button" class="image-delete-btn" id="imageDeleteBtn"
                                    aria-label="Remove image">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="panel">
                        <div class="panel-header-row">
                            <h3>Add-ons / Modifiers</h3>
                            <button type="button" class="btn-addon" id="addAddonBtn">
                                <i class="fa-solid fa-plus"></i> Add
                            </button>
                        </div>
                        <div class="addon-columns">
                            <span>Add-on Name</span>
                            <span>Price</span>
                        </div>
                        <div class="addon-list" id="addonList">
                            <div class="addon-row">
                                <input type="text" class="addon-name" placeholder="e.g. Extra Espresso Shot"
                                    value="Extra Espresso Shot">
                                <div class="addon-price-wrap">
                                    <div class="price-input"><span>₱</span><input type="number" class="addon-price"
                                            value="30.00" step="0.01"></div>
                                    <button class="addon-remove" type="button" aria-label="Remove add-on"><i
                                            class="fa-solid fa-xmark"></i></button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="panel toggle-field">
                        <div class="toggle-row">
                            <label class="switch">
                                <input type="checkbox" id="productStatus" checked>
                                <span class="slider"></span>
                            </label>
                            <span id="statusLabel">Active (Available on Menu)</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-cancel" id="cancelAddBtn">Cancel</button>
                <button type="button" class="btn-save" id="saveProductBtn">Save Product</button>
            </div>
        </div>
    </div>

    <!-- ================= EDIT PRODUCT MODAL ================= -->
    <div class="modal-overlay" id="editProductModal">
        <div class="modal-box">
            <div class="modal-header">
                <div>
                    <h2>Edit Menu Item</h2>
                    <p>Edit the product name, category, price, status and stock levels.</p>
                </div>
                <button class="modal-close" id="closeEditModal" aria-label="Close">&times;</button>
            </div>

            <div class="modal-body">
                <div class="modal-col">
                    <div class="panel">
                        <h3>Item Details</h3>
                        <input type="hidden" id="editCardId">
                        <div class="field">
                            <label>Product Name <span class="required">*</span></label>
                            <input type="text" id="editProductName">
                        </div>
                        <div class="field">
                            <label>Category <span class="required">*</span></label>
                            <select id="editProductCategory">
                                <option value="coffee">Coffee</option>
                                <option value="non-coffee">Non-Coffee</option>
                                <option value="special-coffee">Special Coffee</option>
                                <option value="matcha-fusion">Matcha Fusion</option>
                                <option value="smoothie">Smoothie</option>
                                <option value="frappe-series">Frappe Series</option>
                                <option value="rice-meal">Rice Meal</option>
                                <option value="light-snack">Light Snack</option>
                                <option value="pasta">Pasta</option>
                                <option value="waffle">Waffles</option>
                                <option value="quesadilla">Quesadilla</option>
                            </select>
                        </div>
                    </div>

                    <div class="panel">
                        <h3>Price & Availability</h3>
                        <div class="settings-row">
                            <div class="field">
                                <label>Selling Price <span class="required">*</span></label>
                                <div class="price-input">
                                    <span>₱</span>
                                    <input type="number" id="editSellingPrice" step="0.01">
                                </div>
                            </div>
                            <div class="field">
                                <label>Status</label>
                                <select id="editAvailabilityStatus">
                                    <option value="available">Available</option>
                                    <option value="low">Low Stock</option>
                                    <option value="unavailable">Unavailable</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-col">
                    <div class="panel">
                        <h3>Product Image</h3>
                        <div class="edit-image-upload">
                            <div class="edit-image-preview" id="editImagePreviewBox">
                                <img id="editImagePreview" src="" alt="Product image preview">
                            </div>
                            <div class="edit-image-actions">
                                <label class="choose-file-btn" for="editProductImageInput">
                                    <i class="fa-solid fa-image"></i> Change Image
                                </label>
                                <input type="file" id="editProductImageInput" accept="image/*" style="display:none;">
                                <button type="button" class="image-remove-edit-btn" id="editImageRemoveBtn"
                                    aria-label="Remove selected image" title="Remove selected image">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>
                            <p class="edit-image-note">PNG or JPG, maximum 2MB.</p>
                        </div>
                    </div>

                    <div class="panel">
                        <h3>Inventory Stock</h3>
                        <div class="field">
                            <label>Ingredients Sufficiency</label>
                            <select id="editIngredientsStatus">
                                <option value="Sufficient">Sufficient</option>
                                <option value="Low">Low</option>
                                <option value="Insufficient">Insufficient</option>
                            </select>
                        </div>
                        <div class="settings-row">
                            <div class="field">
                                <label>Cups Stock</label>
                                <input type="number" id="editCupsQty" min="0">
                            </div>
                            <div class="field">
                                <label>Servings Stock</label>
                                <input type="number" id="editServingsQty" min="0">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-cancel" id="cancelEditBtn">Cancel</button>
                <button type="button" class="btn-save" id="saveEditProductBtn">Update Product</button>
            </div>
        </div>
    </div>


    <!-- ================= DELETE PRODUCT CONFIRMATION MODAL ================= -->
    <div class="modal-overlay" id="deleteProductModal">
        <div class="delete-modal-box" role="dialog" aria-modal="true" aria-labelledby="deleteModalTitle">
            <div class="delete-modal-icon">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
            <div class="delete-modal-content">
                <h2 id="deleteModalTitle">Delete Menu Item?</h2>
                <p>Are you sure you want to delete <strong id="deleteProductName">this product</strong>?</p>
                <p class="delete-warning">This action cannot be undone.</p>
            </div>
            <div class="delete-modal-actions">
                <button type="button" class="btn-cancel" id="cancelDeleteBtn">Cancel</button>
                <button type="button" class="btn-delete-confirm" id="confirmDeleteBtn">
                    <i class="fa-solid fa-trash"></i> Delete
                </button>
            </div>
        </div>
    </div>
    <div class="logout-modal" id="logoutModal">

        <div class="logout-modal-box">

            <button class="logout-close" id="logoutClose" aria-label="Close">
                <i class="fa-solid fa-xmark"></i>
            </button>

            <div class="logout-logo">
                <img src="../img/LOGO.png" alt="BoyCold Cafe">
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

    <!-- SCRIPTS -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {


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

            // ================= CATEGORY BAR: filtering + editing =================
            const categoryBar = document.getElementById('categoryBar');
            const catPillsWrap = document.getElementById('catPillsWrap');
            const addCategoryBtn = document.getElementById('addCategoryBtn');
            const catEditActions = document.getElementById('catEditActions');
            const catSaveBtn = document.getElementById('catSaveBtn');
            const catCancelBtn = document.getElementById('catCancelBtn');
            const menuSearch = document.getElementById('menuSearch');
            const noMenuMessage = document.getElementById('noMenuMessage');

            let currentCategory = 'coffee';
            let isEditingCategories = false;
            let catPillsSnapshot = null;
            let draggedPill = null;

            function getAddCategoryGhost() {
                return document.getElementById('addCategoryGhost');
            }

            function filterProducts() {
                const query = menuSearch ? menuSearch.value.trim().toLowerCase() : '';
                let visibleCount = 0;

                const allCards = document.querySelectorAll('.product-card');
                allCards.forEach(card => {
                    const category = (card.getAttribute('data-category') || '').trim().toLowerCase();
                    const name = card.querySelector('.card-name') ? card.querySelector('.card-name').textContent.toLowerCase() : '';
                    const price = card.querySelector('.card-price') ? card.querySelector('.card-price').textContent.toLowerCase() : '';
                    const status = card.querySelector('.drink-status') ? card.querySelector('.drink-status').textContent.toLowerCase() : '';

                    const matchesCategory = currentCategory === 'all' || category === currentCategory;
                    const matchesSearch = !query || name.includes(query) || price.includes(query) || status.includes(query);

                    if (matchesCategory && matchesSearch) {
                        card.style.display = '';
                        visibleCount++;
                    } else {
                        card.style.display = 'none';
                    }
                });

                if (noMenuMessage) {
                    noMenuMessage.style.display = visibleCount === 0 ? 'flex' : 'none';
                }
            }

            if (menuSearch) {
                menuSearch.addEventListener('input', filterProducts);
            }

            function getCatPills() {
                return Array.from(catPillsWrap.querySelectorAll('.cat-pill:not(.cat-add-ghost)'));
            }

            function getPillLabel(pill) {
                const clone = pill.cloneNode(true);
                const del = clone.querySelector('.cat-delete-btn');
                if (del) del.remove();
                return clone.textContent.trim();
            }

            function addCategoryOptionToSelects(value, label) {
                ['productCategory', 'editProductCategory'].forEach(id => {
                    const select = document.getElementById(id);
                    if (select && !select.querySelector(`option[value="${value}"]`)) {
                        const opt = document.createElement('option');
                        opt.value = value;
                        opt.textContent = label;
                        select.appendChild(opt);
                    }
                });
            }

            function enterCategoryEditMode() {
                isEditingCategories = true;
                catPillsSnapshot = catPillsWrap.innerHTML;
                categoryBar.classList.add('editing-mode');
                getCatPills().forEach(p => { p.draggable = true; });

                const ghost = getAddCategoryGhost();
                if (ghost) ghost.style.display = 'inline-flex';

                if (addCategoryBtn) addCategoryBtn.style.display = 'none';
                if (catEditActions) catEditActions.style.display = 'flex';
            }

            function exitCategoryEditMode(commit) {
                const activeInput = catPillsWrap.querySelector('input');
                if (activeInput) activeInput.blur();

                if (!commit && catPillsSnapshot !== null) {
                    catPillsWrap.innerHTML = catPillsSnapshot;
                } else {
                    getCatPills().forEach(p => { p.draggable = false; });
                    const ghost = getAddCategoryGhost();
                    if (ghost) {
                        ghost.classList.remove('new-cat-input');
                        ghost.innerHTML = '<i class="fa-solid fa-plus"></i> New Category';
                    }
                }

                const pills = getCatPills();
                if (pills.length) {
                    let activePill = pills.find(p => p.classList.contains('active'));
                    if (!activePill) {
                        activePill = pills[0];
                        activePill.classList.add('active');
                    }
                    currentCategory = (activePill.getAttribute('data-filter') || '').toLowerCase();
                }

                isEditingCategories = false;
                catPillsSnapshot = null;
                draggedPill = null;
                categoryBar.classList.remove('editing-mode');

                const ghost = getAddCategoryGhost();
                if (ghost) ghost.style.display = 'none';

                if (addCategoryBtn) addCategoryBtn.style.display = 'flex';
                if (catEditActions) catEditActions.style.display = 'none';
                filterProducts();
            }

            function startNewCategoryInput() {
                const ghost = getAddCategoryGhost();
                if (!ghost || ghost.querySelector('input')) return;
                ghost.classList.add('new-cat-input');
                ghost.innerHTML = '';
                const input = document.createElement('input');
                input.type = 'text';
                input.placeholder = 'Category name';
                ghost.appendChild(input);
                input.focus();

                let committed = false;
                function commit() {
                    if (committed) return;
                    committed = true;
                    const val = input.value.trim();
                    if (val) {
                        const filterVal = (val.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '')) || ('cat-' + Date.now());
                        const newPill = document.createElement('a');
                        newPill.href = '#';
                        newPill.className = 'cat-pill';
                        newPill.setAttribute('data-filter', filterVal);
                        newPill.draggable = true;
                        newPill.innerHTML = `${val}<span class="cat-delete-btn" aria-label="Delete category">&times;</span>`;
                        catPillsWrap.insertBefore(newPill, ghost);
                        addCategoryOptionToSelects(filterVal, val);
                    }
                    ghost.classList.remove('new-cat-input');
                    ghost.innerHTML = '<i class="fa-solid fa-plus"></i> New Category';
                }

                input.addEventListener('blur', commit);
                input.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter') { e.preventDefault(); input.blur(); }
                    if (e.key === 'Escape') { input.value = ''; input.blur(); }
                });
            }

            function startRenamePill(pill) {
                if (pill.querySelector('input')) return;
                const label = getPillLabel(pill);
                pill.innerHTML = '';
                const input = document.createElement('input');
                input.type = 'text';
                input.className = 'cat-rename-input';
                input.value = label;
                pill.appendChild(input);
                input.focus();
                input.select();

                let committed = false;
                function commit() {
                    if (committed) return;
                    committed = true;
                    const val = input.value.trim() || label;
                    pill.innerHTML = `${val}<span class="cat-delete-btn" aria-label="Delete category">&times;</span>`;
                }
                input.addEventListener('blur', commit);
                input.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter') { e.preventDefault(); input.blur(); }
                    if (e.key === 'Escape') { input.value = label; input.blur(); }
                });
            }

            function removeCategoryPill(pill) {
                if (getCatPills().length <= 1) return;
                pill.remove();
            }

            catPillsWrap.addEventListener('click', (e) => {
                const delBtn = e.target.closest('.cat-delete-btn');
                if (delBtn) {
                    e.preventDefault();
                    e.stopPropagation();
                    const pill = delBtn.closest('.cat-pill');
                    if (pill) removeCategoryPill(pill);
                    return;
                }

                const ghost = e.target.closest('.cat-add-ghost');
                if (ghost) {
                    e.preventDefault();
                    startNewCategoryInput();
                    return;
                }

                const pill = e.target.closest('.cat-pill');
                if (!pill) return;
                e.preventDefault();

                if (isEditingCategories) {
                    startRenamePill(pill);
                    return;
                }

                getCatPills().forEach(p => p.classList.remove('active'));
                pill.classList.add('active');
                currentCategory = (pill.getAttribute('data-filter') || '').toLowerCase();
                filterProducts();
            });

            // Drag & drop reordering
            catPillsWrap.addEventListener('dragstart', (e) => {
                const pill = e.target.closest('.cat-pill');
                if (!isEditingCategories || !pill || pill.classList.contains('cat-add-ghost')) {
                    e.preventDefault();
                    return;
                }
                draggedPill = pill;
                e.dataTransfer.effectAllowed = 'move';
                try { e.dataTransfer.setData('text/plain', ''); } catch (err) { }
                setTimeout(() => pill.classList.add('dragging'), 0);
            });

            catPillsWrap.addEventListener('dragend', (e) => {
                const pill = e.target.closest('.cat-pill');
                if (pill) pill.classList.remove('dragging');
                getCatPills().forEach(p => p.classList.remove('drag-over'));
                draggedPill = null;
            });

            catPillsWrap.addEventListener('dragover', (e) => {
                if (!isEditingCategories || !draggedPill) return;
                const pill = e.target.closest('.cat-pill');
                if (!pill || pill === draggedPill || pill.classList.contains('cat-add-ghost')) return;
                e.preventDefault();
                pill.classList.add('drag-over');
            });

            catPillsWrap.addEventListener('dragleave', (e) => {
                const pill = e.target.closest('.cat-pill');
                if (pill) pill.classList.remove('drag-over');
            });

            catPillsWrap.addEventListener('drop', (e) => {
                if (!isEditingCategories || !draggedPill) return;
                const pill = e.target.closest('.cat-pill');
                if (!pill || pill === draggedPill || pill.classList.contains('cat-add-ghost')) return;
                e.preventDefault();
                pill.classList.remove('drag-over');
                const pills = getCatPills();
                const draggedIndex = pills.indexOf(draggedPill);
                const targetIndex = pills.indexOf(pill);
                if (draggedIndex < targetIndex) {
                    pill.after(draggedPill);
                } else {
                    pill.before(draggedPill);
                }
            });

            addCategoryBtn?.addEventListener('click', () => enterCategoryEditMode());
            catSaveBtn?.addEventListener('click', () => exitCategoryEditMode(true));
            catCancelBtn?.addEventListener('click', () => exitCategoryEditMode(false));

            // ================= GRID / LIST VIEW TOGGLE =================
            const viewToggle = document.getElementById("viewToggle");
            const productGrid = document.querySelector(".product-grid");
            const header = document.querySelector(".product-list-header");
            const icon = viewToggle.querySelector("i");

            function escapeProductText(value) {
                return String(value ?? '').replace(/[&<>'"]/g, character => ({
                    '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;'
                }[character]));
            }

            function normalizeProductCategory(value) {
                const category = String(value ?? '').trim().toLowerCase();
                return category === 'waffles' ? 'waffle' : category;
            }

            function renderDatabaseProducts(products) {
                if (!productGrid) return;
                productGrid.innerHTML = products.map(product => {
                    const name = escapeProductText(product.product_name);
                    const category = escapeProductText(normalizeProductCategory(product.category || 'uncategorized'));
                    const image = escapeProductText(product.image || '/img/LOGO 2.png');
                    const status = Number(product.is_available) ? 'available' : 'unavailable';
                    const statusLabel = Number(product.is_available) ? 'Available' : 'Unavailable';
                    return `<div class="product-card" data-category="${category}" data-id="${Number(product.id)}">
                        <div class="card-actions">
                            <button type="button" class="grid-action-btn" aria-label="More actions"><i class="fa-solid fa-ellipsis-vertical"></i></button>
                            <div class="grid-delete-menu"><button type="button" class="grid-delete-option btn-delete" aria-label="Delete product"><i class="fa-solid fa-trash"></i> Delete</button></div>
                            <button type="button" class="grid-edit-btn btn-edit" aria-label="Edit product"><i class="fa-solid fa-pen-to-square"></i></button>
                            <div class="list-action-buttons"><button type="button" class="list-action-btn btn-edit" aria-label="Edit product"><i class="fa-solid fa-pen-to-square"></i></button><button type="button" class="list-action-btn btn-delete" aria-label="Delete product"><i class="fa-solid fa-trash"></i></button></div>
                        </div>
                        <div class="card-image"><div class="card-image-placeholder"><img src="${image}" alt="${name}"></div></div>
                        <div class="card-info"><div class="card-mid"><p class="card-name">${name}</p></div>
                            <div class="card-footer"><p class="card-price">₱${Number(product.price).toFixed(2)}</p><div class="drink-stock">
                                <p class="drink-status ${status}"><span class="status-dot"></span> ${statusLabel}</p>
                                <p class="drink-ingredient">Ingredients: <span>Sufficient</span></p>
                                <p class="drink-cups">Cups: <span class="cups-value">40 pcs</span></p><p class="drink-servings"><span class="servings-value">0</span></p>
                            </div></div>
                        </div>
                    </div>`;
                }).join('');
                syncProductActionVisibility();
                filterProducts();
            }

            fetch('admin_data_api.php?action=products')
                .then(response => response.json())
                .then(result => {
                    if (!result.success) throw new Error(result.error || 'Products could not be loaded');
                    renderDatabaseProducts(result.products);
                })
                .catch(error => console.error(error));

            function syncProductActionVisibility() {
                const isListView = productGrid.classList.contains("list-view");

                productGrid.querySelectorAll(".grid-edit-btn").forEach(btn => {
                    // Use the hidden property as a hard guarantee that the Grid-only
                    // bottom-right edit button cannot appear in List View.
                    btn.hidden = isListView;
                    btn.setAttribute("aria-hidden", isListView ? "true" : "false");
                    btn.style.display = isListView ? "none" : "flex";
                });

                productGrid.querySelectorAll(".grid-action-btn, .grid-delete-menu").forEach(el => {
                    el.style.display = isListView ? "none" : "";
                });

                productGrid.querySelectorAll(".list-action-buttons").forEach(el => {
                    el.style.display = isListView ? "flex" : "none";
                });
            }

            viewToggle.addEventListener("click", () => {
                const isListView = productGrid.classList.toggle("list-view");
                header.classList.toggle("active", isListView);

                if (isListView) {
                    icon.classList.remove("fa-bars");
                    icon.classList.add("fa-table-cells-large");
                } else {
                    icon.classList.remove("fa-table-cells-large");
                    icon.classList.add("fa-bars");
                }

                syncProductActionVisibility();
            });

            // Keep action buttons correct on initial load as well.
            syncProductActionVisibility();

            // Also apply the same rule to products created dynamically.
            const actionVisibilityObserver = new MutationObserver(() => {
                syncProductActionVisibility();
            });
            if (productGrid) {
                actionVisibilityObserver.observe(productGrid, { childList: true, subtree: true });
            }

            // Modal Controllers & Handlers
            const addProductModal = document.getElementById('addProductModal');
            const addNewItemBtn = document.getElementById('addNewItemBtn');
            const closeAddModal = document.getElementById('closeAddModal');
            const cancelAddBtn = document.getElementById('cancelAddBtn');
            const saveProductBtn = document.getElementById('saveProductBtn');

            if (addNewItemBtn) addNewItemBtn.addEventListener('click', () => addProductModal?.classList.add('open'));
            if (closeAddModal) closeAddModal.addEventListener('click', () => addProductModal?.classList.remove('open'));
            if (cancelAddBtn) cancelAddBtn.addEventListener('click', () => addProductModal?.classList.remove('open'));

            // Image Upload Preview
            const productImageInput = document.getElementById('productImageInput');
            const imagePreview = document.getElementById('imagePreview');
            const imagePreviewBox = document.getElementById('imagePreviewBox');
            const imageDeleteBtn = document.getElementById('imageDeleteBtn');

            if (productImageInput && imagePreview && imagePreviewBox) {
                productImageInput.addEventListener('change', (e) => {
                    const file = e.target.files[0];
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = (ev) => {
                            imagePreview.src = ev.target.result;
                            imagePreviewBox.style.display = 'flex';
                        };
                        reader.readAsDataURL(file);
                    }
                });

                imageDeleteBtn?.addEventListener('click', () => {
                    productImageInput.value = '';
                    imagePreview.src = '';
                    imagePreviewBox.style.display = 'none';
                });
            }

            // Save Product Item
            if (saveProductBtn) {
                saveProductBtn.addEventListener('click', () => {
                    const nameInput = document.getElementById('productName');
                    const categorySelect = document.getElementById('productCategory');
                    const priceInput = document.getElementById('sellingPrice');
                    const stockQtyInput = document.getElementById('stockQty');
                    const servingsQtyInput = document.getElementById('servingsQty');

                    if (!nameInput.value.trim() || !categorySelect.value || !priceInput.value) {
                        alert('Please fill in all required fields (Name, Category, Price).');
                        return;
                    }

                    const name = nameInput.value.trim();
                    const category = categorySelect.value;
                    const price = parseFloat(priceInput.value).toFixed(2);
                    const cups = stockQtyInput.value ? stockQtyInput.value + ' pcs' : '40 pcs';
                    const servings = servingsQtyInput.value ? servingsQtyInput.value : '25';
                    const imgSrc = imagePreview?.src || '../../../img/Americano.png';

                    fetch('admin_data_api.php?action=product_create', {
                        method: 'POST', headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ product_name: name, category, price, image: imgSrc, is_available: document.getElementById('productStatus').checked ? 1 : 0 })
                    }).then(response => response.json()).then(result => {
                        if (!result.success) throw new Error(result.error || 'Product could not be saved');
                        window.location.reload();
                    }).catch(error => alert(error.message));
                    return;

                    const newCard = document.createElement('div');
                    newCard.className = 'product-card';
                    newCard.setAttribute('data-category', category);
                    newCard.setAttribute('data-id', id);
                    newCard.innerHTML = `
                        <div class="card-actions">
                            <button type="button" class="grid-action-btn" aria-label="More actions" title="More actions">
                                <i class="fa-solid fa-ellipsis-vertical"></i>
                            </button>
                            <div class="grid-delete-menu">
                                <button type="button" class="grid-delete-option btn-delete" aria-label="Delete product">
                                    <i class="fa-solid fa-trash"></i> Delete
                                </button>
                            </div>
                            <button type="button" class="grid-edit-btn btn-edit" aria-label="Edit product" title="Edit">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </button>
                            <div class="list-action-buttons">
                                <button type="button" class="list-action-btn btn-edit" aria-label="Edit product" title="Edit">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </button>
                                <button type="button" class="list-action-btn btn-delete" aria-label="Delete product" title="Delete">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-image">
                            <div class="card-image-placeholder">
                                <img src="${imgSrc}" alt="${name}">
                            </div>
                        </div>
                        <div class="card-info">
                            <div class="card-mid">
                                <p class="card-name">${name}</p>
                            </div>
                            <div class="card-footer">
                                <p class="card-price">₱${price}</p>
                                <div class="drink-stock">
                                    <p class="drink-status available">
                                        <span class="status-dot"></span> Available
                                    </p>
                                    <p class="drink-ingredient">
                                        Ingredients: <span>Sufficient</span>
                                    </p>
                                    <p class="drink-cups">
                                        Cups: <span class="cups-value">${cups}</span>
                                    </p>
                                    <p class="drink-servings">
                                        <span class="servings-value">${servings}</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    `;

                    if (productGrid) productGrid.appendChild(newCard);
                    filterProducts();
                    addProductModal?.classList.remove('open');

                    nameInput.value = '';
                    categorySelect.value = '';
                    priceInput.value = '';
                    if (imagePreview) imagePreview.src = '';
                    if (imagePreviewBox) imagePreviewBox.style.display = 'none';
                });
            }

            // ================= PRODUCT ACTIONS / EDIT / DELETE =================
            const editProductModal = document.getElementById('editProductModal');
            const closeEditModal = document.getElementById('closeEditModal');
            const cancelEditBtn = document.getElementById('cancelEditBtn');
            const saveEditProductBtn = document.getElementById('saveEditProductBtn');
            const editProductImageInput = document.getElementById('editProductImageInput');
            const editImagePreview = document.getElementById('editImagePreview');
            const editImageRemoveBtn = document.getElementById('editImageRemoveBtn');
            let editImageChanged = false;
            let editImageRemoved = false;

            const deleteProductModal = document.getElementById('deleteProductModal');
            const cancelDeleteBtn = document.getElementById('cancelDeleteBtn');
            const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
            const deleteProductName = document.getElementById('deleteProductName');

            let currentEditingCard = null;
            let currentDeletingCard = null;

            function closeActionMenus() {
                document.querySelectorAll('.product-card.action-menu-open').forEach(card => {
                    card.classList.remove('action-menu-open');
                });
            }

            // Edit-product image upload / preview
            if (editProductImageInput) {
                editProductImageInput.addEventListener('change', (e) => {
                    const file = e.target.files?.[0];
                    if (!file) return;

                    if (!file.type.startsWith('image/')) {
                        alert('Please select an image file.');
                        editProductImageInput.value = '';
                        return;
                    }

                    if (file.size > 2 * 1024 * 1024) {
                        alert('Image must be 2MB or smaller.');
                        editProductImageInput.value = '';
                        return;
                    }

                    const reader = new FileReader();
                    reader.onload = (ev) => {
                        if (editImagePreview) editImagePreview.src = ev.target.result;
                        editImageChanged = true;
                        editImageRemoved = false;
                    };
                    reader.readAsDataURL(file);
                });
            }

            editImageRemoveBtn?.addEventListener('click', () => {
                if (editImagePreview) editImagePreview.src = '';
                if (editProductImageInput) editProductImageInput.value = '';
                editImageChanged = false;
                editImageRemoved = true;
            });

            function resetEditImageState() {
                editImageChanged = false;
                editImageRemoved = false;
                if (editProductImageInput) editProductImageInput.value = '';
            }

            function openEditModal(card) {
                if (!card) return;

                currentEditingCard = card;

                const name = card.querySelector('.card-name')?.textContent.trim() || '';
                const price = (card.querySelector('.card-price')?.textContent || '')
                    .replace('₱', '')
                    .trim();
                const category = (card.getAttribute('data-category') || '').trim().toLowerCase();
                const statusEl = card.querySelector('.drink-status');
                const ingredientsEl = card.querySelector('.drink-ingredient span');
                const cupsVal = (card.querySelector('.cups-value')?.textContent || '40')
                    .replace('pcs', '')
                    .trim();
                const servingsVal = card.querySelector('.servings-value')?.textContent.trim() || '25';

                document.getElementById('editProductName').value = name;
                document.getElementById('editSellingPrice').value = price;
                document.getElementById('editProductCategory').value = category;

                const currentImage = card.querySelector('.card-image img');
                if (editImagePreview) editImagePreview.src = currentImage?.src || '';
                resetEditImageState();

                let statusVal = 'available';
                if (statusEl?.classList.contains('low')) statusVal = 'low';
                else if (statusEl?.classList.contains('unavailable')) statusVal = 'unavailable';
                document.getElementById('editAvailabilityStatus').value = statusVal;

                document.getElementById('editIngredientsStatus').value =
                    ingredientsEl?.textContent.trim() || 'Sufficient';
                document.getElementById('editCupsQty').value = cupsVal;
                document.getElementById('editServingsQty').value = servingsVal;

                closeActionMenus();
                editProductModal?.classList.add('open');
            }

            function openDeleteModal(card) {
                if (!card || !deleteProductModal) return;

                currentDeletingCard = card;
                const name = card.querySelector('.card-name')?.textContent.trim() || 'this product';

                if (deleteProductName) {
                    deleteProductName.textContent = name;
                }

                closeActionMenus();
                deleteProductModal.classList.add('open');
            }

            function closeDeleteModal() {
                deleteProductModal?.classList.remove('open');
                currentDeletingCard = null;
            }

            // Close Edit modal
            if (closeEditModal) {
                closeEditModal.addEventListener('click', () => {
                    editProductModal?.classList.remove('open');
                    currentEditingCard = null;
                    resetEditImageState();
                });
            }

            if (cancelEditBtn) {
                cancelEditBtn.addEventListener('click', () => {
                    editProductModal?.classList.remove('open');
                    currentEditingCard = null;
                    resetEditImageState();
                });
            }

            // Save changes from Edit modal
            if (saveEditProductBtn) {
                saveEditProductBtn.addEventListener('click', () => {
                    if (!currentEditingCard) return;

                    const newName = document.getElementById('editProductName').value.trim();
                    const priceValue = parseFloat(
                        document.getElementById('editSellingPrice').value || 0
                    );
                    const newCategory = document.getElementById('editProductCategory').value;
                    const newStatus = document.getElementById('editAvailabilityStatus').value;
                    const newIngredients = document.getElementById('editIngredientsStatus').value;
                    const newCups = document.getElementById('editCupsQty').value || '0';
                    const newServings = document.getElementById('editServingsQty').value || '0';

                    if (!newName) {
                        alert('Please enter a product name.');
                        return;
                    }

                    if (!Number.isFinite(priceValue) || priceValue < 0) {
                        alert('Please enter a valid selling price.');
                        return;
                    }

                    const newPrice = priceValue.toFixed(2);

                    const productId = currentEditingCard.dataset.id;
                    fetch('admin_data_api.php?action=product_update', {
                        method: 'POST', headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ id: productId, product_name: newName, category: newCategory, price: priceValue, is_available: newStatus === 'unavailable' ? 0 : 1 })
                    }).then(response => response.json()).then(result => {
                        if (!result.success) throw new Error(result.error || 'Product could not be updated');
                        window.location.reload();
                    }).catch(error => alert(error.message));
                    return;

                    currentEditingCard.querySelector('.card-name').textContent = newName;
                    currentEditingCard.querySelector('.card-price').textContent = `₱${newPrice}`;
                    currentEditingCard.setAttribute('data-category', newCategory);

                    const image = currentEditingCard.querySelector('.card-image img');
                    if (image) {
                        image.alt = newName;
                        if (editImageChanged && editImagePreview?.src) {
                            image.src = editImagePreview.src;
                        } else if (editImageRemoved) {
                            image.src = '';
                        }
                    }

                    const statusEl = currentEditingCard.querySelector('.drink-status');
                    if (statusEl) {
                        statusEl.className = `drink-status ${newStatus}`;

                        let label = 'Available';
                        if (newStatus === 'low') label = 'Low Stock';
                        else if (newStatus === 'unavailable') label = 'Unavailable';

                        statusEl.innerHTML =
                            `<span class="status-dot"></span> ${label}`;
                    }

                    const ingredientSpan = currentEditingCard.querySelector('.drink-ingredient span');
                    if (ingredientSpan) ingredientSpan.textContent = newIngredients;

                    const cupsSpan = currentEditingCard.querySelector('.cups-value');
                    if (cupsSpan) cupsSpan.textContent = `${newCups} pcs`;

                    const servingsSpan = currentEditingCard.querySelector('.servings-value');
                    if (servingsSpan) servingsSpan.textContent = newServings;

                    filterProducts();
                    editProductModal?.classList.remove('open');
                    currentEditingCard = null;
                    resetEditImageState();
                });
            }

            // Delete confirmation
            if (cancelDeleteBtn) {
                cancelDeleteBtn.addEventListener('click', closeDeleteModal);
            }

            if (confirmDeleteBtn) {
                confirmDeleteBtn.addEventListener('click', () => {
                    if (!currentDeletingCard) return;

                    fetch('admin_data_api.php?action=product_delete', {
                        method: 'POST', headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ id: currentDeletingCard.dataset.id })
                    }).then(response => response.json()).then(result => {
                        if (!result.success) throw new Error(result.error || 'Product could not be deleted');
                        currentDeletingCard.remove();
                        filterProducts();
                    }).catch(error => alert(error.message));
                    currentDeletingCard = null;
                    deleteProductModal?.classList.remove('open');
                });
            }

            // Product action delegation works for both existing and newly added cards.
            if (productGrid) {
                productGrid.addEventListener('click', (e) => {
                    const card = e.target.closest('.product-card');
                    if (!card) return;

                    const gridActionBtn = e.target.closest('.grid-action-btn');
                    const editBtn = e.target.closest('.btn-edit');
                    const deleteBtn = e.target.closest('.btn-delete');
                    const gridDeleteOption = e.target.closest('.grid-delete-option');

                    // Grid view: clicking the 3 dots first reveals the Delete action.
                    // It does not open the confirmation modal until Delete is clicked.
                    if (gridActionBtn) {
                        e.preventDefault();
                        e.stopPropagation();
                        const menu = card.querySelector('.grid-delete-menu');
                        const isOpen = card.classList.contains('action-menu-open');
                        closeActionMenus();
                        if (!isOpen && menu) {
                            card.classList.add('action-menu-open');
                        }
                        return;
                    }

                    if (gridDeleteOption) {
                        e.preventDefault();
                        e.stopPropagation();
                        openDeleteModal(card);
                        return;
                    }

                    if (editBtn) {
                        e.preventDefault();
                        e.stopPropagation();
                        openEditModal(card);
                        return;
                    }

                    if (deleteBtn) {
                        e.preventDefault();
                        e.stopPropagation();
                        openDeleteModal(card);
                        return;
                    }
                });
            }

            // Close grid action menus when clicking elsewhere.
            document.addEventListener('click', (e) => {
                if (!e.target.closest('.card-actions')) {
                    closeActionMenus();
                }
            });

            // Close modals when clicking their dark backdrop.
            if (editProductModal) {
                editProductModal.addEventListener('click', (e) => {
                    if (e.target === editProductModal) {
                        editProductModal.classList.remove('open');
                        currentEditingCard = null;
                    }
                });
            }

            if (deleteProductModal) {
                deleteProductModal.addEventListener('click', (e) => {
                    if (e.target === deleteProductModal) {
                        closeDeleteModal();
                    }
                });
            }

            // Escape closes the active modal/menu.
            document.addEventListener('keydown', (e) => {
                if (e.key !== 'Escape') return;

                closeActionMenus();

                if (deleteProductModal?.classList.contains('open')) {
                    closeDeleteModal();
                }

                if (editProductModal?.classList.contains('open')) {
                    editProductModal.classList.remove('open');
                    currentEditingCard = null;
                }
            });

            filterProducts();
        });
    </script>
    <script src="admin-js/admin-responsive.js"></script>
</body>

</html>



