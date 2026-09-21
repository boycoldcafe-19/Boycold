<?php
require_once __DIR__ . '/admin_guard.php';
require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../config/inventory_service.php';

// Ensure inventory schema exists
boycold_ensure_inventory_schema($connect);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="admin-css/inventory.css">
    <link rel="stylesheet" href="admin-css/dashboard.css">
    <link rel="stylesheet" href="admin-css/admin-sidebar.css">
    <link rel="stylesheet" href="admin-css/admin-responsive.css">
    <link rel="icon" href="/public/assets/icons/LOGO 2.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Afacad:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Gaegu:wght@400;700&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>
    <title>BoyCold - Inventory</title>
</head>

<body>
    <div class="app-shell">

        <!-- SIDEBAR -->
        <aside class="sidebar" id="sidebar">

            <div class="sidebar-brand">
                <span class="brand-mark" aria-hidden="true">
                    <img src="../img/ChatGPT Image Jun 23, 2026, 09_22_57 PM 1.png" alt="">
                </span>
                <span class="brand-text">
                    <span class="brand-name">B<span class="special-letter">o</span><span class="special-letter-2">y</span>C<span class="special-letter">o</span>LD CAFE</span>
                    <span class="brand-sub">Administration Panel</span>
                </span>
            </div>

            <nav class="sidebar-nav">
                <div class="nav-top">
                    <ul>
                        <li>
                            <a href="dashboard.php">
                                <span class="nav-icon1"><svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M0.5 5C0.367392 5 0.240215 4.94732 0.146447 4.85355C0.0526785 4.75979 0 4.63261 0 4.5V0.5C0 0.367392 0.0526785 0.240215 0.146447 0.146447C0.240215 0.0526785 0.367392 0 0.5 0H4.5C4.63261 0 4.75979 0.0526785 4.85355 0.146447C4.94732 0.240215 5 0.367392 5 0.5V4.5C5 4.63261 4.94732 4.75979 4.85355 4.85355C4.75979 4.94732 4.63261 5 4.5 5H0.5ZM7.5 5C7.36739 5 7.24021 4.94732 7.14645 4.85355C7.05268 4.75979 7 4.63261 7 4.5V0.5C7 0.367392 7.05268 0.240215 7.14645 0.146447C7.24021 0.0526785 7.36739 0 7.5 0H11.5C11.6326 0 11.7598 0.0526785 11.8536 0.146447C11.9473 0.240215 12 0.367392 12 0.5V4.5C12 4.63261 11.9473 4.75979 11.8536 4.85355C11.7598 4.94732 11.6326 5 11.5 5H7.5ZM0.5 12C0.367392 12 0.240215 11.9473 0.146447 11.8536C0.0526785 11.7598 0 11.6326 0 11.5V7.5C0 7.36739 0.0526785 7.24021 0.146447 7.14645C0.240215 7.05268 0.367392 7 0.5 7H4.5C4.63261 7 4.75979 7.05268 4.85355 7.14645C4.94732 7.24021 5 7.36739 5 7.5V11.5C5 11.6326 4.94732 11.7598 4.85355 11.8536C4.75979 11.9473 4.63261 12 4.5 12H0.5ZM7.5 12C7.36739 12 7.24021 11.9473 7.14645 11.8536C7.05268 11.7598 7 11.6326 7 11.5V7.5C7 7.36739 7.05268 7.24021 7.14645 7.14645C7.24021 7.05268 7.36739 7 7.5 7H11.5C11.6326 7 11.7598 7.05268 11.8536 7.14645C11.9473 7.24021 12 7.36739 12 7.5V11.5C12 11.6326 11.9473 11.7598 11.8536 11.8536C11.7598 11.9473 11.6326 12 11.5 12H7.5Z" fill="currentColor"/></svg></span>
                                <span class="nav-label">Dashboard</span>
                                <i class="fa-solid fa-chevron-right nav-chevron"></i>
                            </a>
                        </li>
                        <li>
                            <a href="orders.php">
                                <span class="nav-icon"><svg width="19" height="22" viewBox="0 0 19 22" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M14.8882 1H3.31469C2.03632 1 1 2.03632 1 3.31469V18.3602C1 19.6386 2.03632 20.6749 3.31469 20.6749H14.8882C16.1665 20.6749 17.2029 19.6386 17.2029 18.3602V3.31469C17.2029 2.03632 16.1665 1 14.8882 1Z" stroke="currentColor" stroke-width="2"/><path d="M5.62939 6.78662H12.5735M5.62939 11.416H12.5735M5.62939 16.0454H10.2588" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>
                                <span class="nav-label">Orders</span>
                                <i class="fa-solid fa-chevron-right nav-chevron"></i>
                            </a>
                        </li>
                        <li>
                            <a href="data-analytics.php">
                                <span class="nav-icon2"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M15.8601 4.39V19.39C15.8601 21.06 17.0001 22 18.2501 22C19.3901 22 20.6401 21.21 20.6401 19.39V4.5C20.6401 2.96 19.5001 2 18.2501 2C17.0001 2 15.8601 3.06 15.8601 4.39ZM9.61011 12V19.39C9.61011 21.07 10.7701 22 12.0001 22C13.1401 22 14.3901 21.21 14.3901 19.39V12.11C14.3901 10.57 13.2501 9.61 12.0001 9.61C10.7501 9.61 9.61011 10.67 9.61011 12ZM5.75011 17.23C7.07011 17.23 8.14011 18.3 8.14011 19.61C8.14011 20.2439 7.88831 20.8518 7.44009 21.3C6.99188 21.7482 6.38398 22 5.75011 22C5.11624 22 4.50833 21.7482 4.06012 21.3C3.61191 20.8518 3.36011 20.2439 3.36011 19.61C3.36011 18.3 4.43011 17.23 5.75011 17.23Z" fill="white"/></svg></span>
                                <span class="nav-label">Data Analytics</span>
                                <i class="fa-solid fa-chevron-right nav-chevron"></i>
                            </a>
                        </li>
                        <li>
                            <a href="forecasting.php">
                                <span class="nav-icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M21.3751 6C21.0698 6.00008 20.7692 6.0747 20.4993 6.21737C20.2294 6.36005 19.9984 6.56647 19.8264 6.81869C19.6545 7.07092 19.5467 7.36132 19.5124 7.66468C19.4782 7.96803 19.5185 8.27516 19.6299 8.55938L15.6845 12.5048C15.2447 12.3317 14.7556 12.3317 14.3157 12.5048L11.4953 9.68438C11.6069 9.40009 11.6475 9.09283 11.6134 8.78931C11.5792 8.48579 11.4715 8.1952 11.2995 7.94281C11.1275 7.69042 10.8964 7.48387 10.6264 7.34113C10.3563 7.19839 10.0555 7.12377 9.75011 7.12377C9.44467 7.12377 9.14386 7.19839 8.87384 7.34113C8.60381 7.48387 8.37274 7.69042 8.20073 7.94281C8.02872 8.1952 7.92096 8.48579 7.88684 8.78931C7.85272 9.09283 7.89327 9.40009 8.00495 9.68438L3.30948 14.3798C2.90848 14.2225 2.46554 14.2081 2.05514 14.339C1.64474 14.4698 1.29192 14.738 1.056 15.0984C0.82007 15.4588 0.715432 15.8895 0.759675 16.3179C0.803918 16.7464 0.994344 17.1466 1.29893 17.4512C1.60352 17.7558 2.0037 17.9462 2.43218 17.9904C2.86065 18.0347 3.2913 17.93 3.6517 17.6941C4.0121 17.4582 4.28028 17.1054 4.41114 16.695C4.542 16.2846 4.52757 15.8416 4.37026 15.4406L9.06573 10.7452C9.50556 10.9183 9.99466 10.9183 10.4345 10.7452L13.2549 13.5656C13.1433 13.8499 13.1027 14.1572 13.1368 14.4607C13.171 14.7642 13.2787 15.0548 13.4507 15.3072C13.6227 15.5596 13.8538 15.7661 14.1238 15.9089C14.3939 16.0516 14.6947 16.1262 15.0001 16.1262C15.3055 16.1262 15.6063 16.0516 15.8764 15.9089C16.1464 15.7661 16.3775 15.5596 16.5495 15.3072C16.7215 15.0548 16.8293 14.7642 16.8634 14.4607C16.8975 14.1572 16.8569 13.8499 16.7453 13.5656L20.6907 9.62016C20.9475 9.72102 21.2233 9.76399 21.4986 9.74601C21.7738 9.72803 22.0417 9.64953 22.2832 9.51613C22.5246 9.38272 22.7336 9.19768 22.8953 8.97421C23.0571 8.75073 23.1675 8.49433 23.2187 8.22329C23.2699 7.95225 23.2607 7.67324 23.1918 7.40616C23.1228 7.13907 22.9957 6.8905 22.8197 6.67816C22.6436 6.46582 22.4228 6.29495 22.1731 6.17773C21.9234 6.0605 21.651 5.99982 21.3751 6Z" fill="white"/></svg></span>
                                <span class="nav-label">Forecasting</span>
                                <i class="fa-solid fa-chevron-right nav-chevron"></i>
                            </a>
                        </li>
                        <li>
                            <a href="inventory.php" class="active">
                                <span class="nav-icon"><svg width="30" height="30" viewBox="0 0 30 30" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M25.9126 20.6502V9.48774C25.9136 9.34411 25.8793 9.20245 25.8126 9.07524C25.7205 8.87674 25.5611 8.71729 25.3626 8.62524L15.3626 4.15024C15.2409 4.09499 15.1088 4.06641 14.9751 4.06641C14.8414 4.06641 14.7093 4.09499 14.5876 4.15024L4.5876 8.62524C4.42677 8.70617 4.29077 8.82903 4.19397 8.98084C4.09716 9.13265 4.04314 9.30778 4.0376 9.48774V20.5127C4.04694 20.6918 4.10252 20.8653 4.19891 21.0165C4.2953 21.1676 4.42922 21.2912 4.5876 21.3752L14.5876 25.8502C14.7086 25.908 14.841 25.9379 14.9751 25.9379C15.1092 25.9379 15.2416 25.908 15.3626 25.8502L25.3626 21.3752C25.507 21.3091 25.6327 21.2083 25.7287 21.0818C25.8247 20.9553 25.8878 20.8071 25.9126 20.6502ZM5.9126 10.9252L14.0376 14.5752V23.5502L5.9126 19.9127V10.9252ZM15.9126 14.5752L24.0376 10.9252V19.9127L15.9126 23.5502V14.5752ZM15.0001 6.02524L22.7126 9.48774L15.0001 12.9377L7.2876 9.48774L15.0001 6.02524Z" fill="white"/></svg></span>
                                <span class="nav-label">Inventory</span>
                                <i class="fa-solid fa-chevron-right nav-chevron"></i>
                            </a>
                        </li>
                        <li>
                            <a href="mapping.php">
                                <span class="nav-icon"><svg width="27" height="27" viewBox="0 0 27 27" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M23.1154 4.13114C22.9044 3.92024 22.6183 3.80176 22.32 3.80176C22.0217 3.80176 21.7356 3.92024 21.5246 4.13114L15.4901 10.1656H2.25V12.4156H2.259C2.50425 18.4119 7.443 23.1988 13.5 23.1988C19.557 23.1988 24.4958 18.4119 24.741 12.4156H24.75V10.1656H18.6716L23.1154 5.72189C23.3263 5.51092 23.4448 5.22483 23.4448 4.92652C23.4448 4.62821 23.3263 4.34211 23.1154 4.13114ZM15.9491 12.4156H22.4888C22.3733 14.7218 21.3759 16.8954 19.7029 18.4869C18.0298 20.0783 15.8091 20.9658 13.5 20.9658C11.1909 20.9658 8.97019 20.0783 7.29713 18.4869C5.62406 16.8954 4.62667 14.7218 4.51125 12.4156H15.9491Z" fill="white"/></svg></span>
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
                                <span class="nav-icon"><svg width="22" height="18" viewBox="0 0 22 18" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M0.75 8.75C0.75 4.979 0.75 3.093 1.922 1.922C3.094 0.751 4.979 0.75 8.75 0.75H12.75C16.521 0.75 18.407 0.75 19.578 1.922C20.749 3.094 20.75 4.979 20.75 8.75C20.75 12.521 20.75 14.407 19.578 15.578C18.406 16.749 16.521 16.75 12.75 16.75H8.75C4.979 16.75 3.093 16.75 1.922 15.578C0.751 14.406 0.75 12.521 0.75 8.75Z" stroke="currentColor" stroke-width="1.5"/><path d="M8.75 12.75H4.75M12.75 12.75H11.25M0.75 6.75H20.75" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg></span>
                                <span class="nav-label">Loyalty Card</span>
                                <i class="fa-solid fa-chevron-right nav-chevron"></i>
                            </a>
                        </li>
                        <li>
                            <a href="activitylog.php">
                                <span class="nav-icon">
                                    <svg width="25" height="25" viewBox="0 0 25 25" fill="none"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path
                                            d="M12.4962 3.38333C12.5214 3.63063 12.4473 3.8778 12.2902 4.07049C12.1332 4.26317 11.906 4.38559 11.6587 4.41083C10.1302 4.56979 8.67811 5.15855 7.47051 6.1089C6.26292 7.05926 5.3493 8.3323 4.8355 9.78056C4.32169 11.2288 4.22874 12.793 4.5674 14.2919C4.90605 15.7909 5.66247 17.1632 6.749 18.2498C7.83554 19.3365 9.20773 20.0931 10.7066 20.432C12.2055 20.7709 13.7697 20.6781 15.218 20.1645C16.6663 19.6509 17.9395 18.7375 18.89 17.53C19.8406 16.3226 20.4295 14.8705 20.5887 13.3421C20.5995 13.2182 20.6348 13.0978 20.6926 12.9877C20.7504 12.8776 20.8295 12.7801 20.9253 12.7009C21.0211 12.6217 21.1317 12.5623 21.2507 12.5262C21.3697 12.4902 21.4946 12.4781 21.6183 12.4908C21.742 12.5035 21.8619 12.5407 21.9711 12.6001C22.0803 12.6596 22.1765 12.7402 22.2543 12.8372C22.332 12.9342 22.3897 13.0457 22.4239 13.1652C22.4582 13.2847 22.4683 13.4099 22.4537 13.5333C22.2584 15.4145 21.5341 17.2019 20.3646 18.6883C19.195 20.1747 17.6283 21.2992 15.8458 21.9316C14.0633 22.564 12.1382 22.6783 10.2934 22.2613C8.44859 21.8444 6.75973 20.9132 5.42251 19.5756C4.0853 18.2381 3.15448 16.5491 2.73793 14.7042C2.32137 12.8593 2.43614 10.9341 3.06893 9.15182C3.70172 7.3695 4.82662 5.80298 6.31327 4.63381C7.79992 3.46463 9.58745 2.74067 11.4687 2.54583C11.716 2.52068 11.9632 2.59478 12.1559 2.75183C12.3485 2.90888 12.471 3.13603 12.4962 3.38333ZM13.7712 3.30083C13.7998 3.18094 13.8518 3.06787 13.9242 2.9681C13.9966 2.86832 14.0879 2.7838 14.193 2.71936C14.298 2.65492 14.4148 2.61184 14.5365 2.59257C14.6583 2.57331 14.7826 2.57824 14.9025 2.60708C15.3758 2.72041 15.8354 2.86625 16.2812 3.04458C16.5058 3.14092 16.6839 3.32118 16.7776 3.54694C16.8712 3.7727 16.873 4.02611 16.7826 4.25316C16.6921 4.48021 16.5165 4.66296 16.2933 4.76246C16.0701 4.86196 15.8168 4.87035 15.5875 4.78583C15.225 4.64166 14.8508 4.52333 14.465 4.43083C14.2233 4.37273 14.0147 4.22106 13.8848 4.00917C13.7549 3.79728 13.7133 3.5425 13.7712 3.30083ZM21.9562 8.71708C21.8642 8.48601 21.6842 8.30095 21.4557 8.20261C21.2273 8.10427 20.9692 8.10071 20.7381 8.1927C20.507 8.2847 20.322 8.46472 20.2236 8.69316C20.1253 8.92161 20.1217 9.17976 20.2137 9.41083C20.3579 9.77416 20.4766 10.1483 20.57 10.5333C20.628 10.7752 20.7797 10.9841 20.9917 11.1141C21.0967 11.1784 21.2133 11.2215 21.335 11.2408C21.4566 11.26 21.5808 11.2552 21.7006 11.2265C21.8203 11.1977 21.9332 11.1457 22.0329 11.0733C22.1325 11.001 22.2169 10.9097 22.2813 10.8047C22.3457 10.6997 22.3887 10.5831 22.408 10.4614C22.4273 10.3398 22.4224 10.2156 22.3937 10.0958C22.2813 9.62629 22.1351 9.1655 21.9562 8.71708ZM17.8625 4.90708C18.0257 4.71963 18.2568 4.60472 18.5048 4.58761C18.7528 4.5705 18.9975 4.65259 19.185 4.81583C19.5691 5.15 19.9266 5.51125 20.2575 5.89958C20.4186 6.08904 20.4978 6.33475 20.4778 6.58265C20.4577 6.83055 20.34 7.06034 20.1506 7.22145C19.9611 7.38257 19.7154 7.46183 19.4675 7.44179C19.2196 7.42174 18.9898 7.30404 18.8287 7.11458C18.5584 6.79918 18.266 6.50344 17.9537 6.22958C17.7663 6.06629 17.6513 5.83523 17.6342 5.58722C17.6171 5.33922 17.6992 5.09457 17.8625 4.90708ZM12.5 7.18708C12.5 6.93844 12.4012 6.69998 12.2254 6.52417C12.0496 6.34835 11.8111 6.24958 11.5625 6.24958C11.3138 6.24958 11.0754 6.34835 10.8995 6.52417C10.7237 6.69998 10.625 6.93844 10.625 7.18708V13.4371C10.625 13.9558 11.045 14.3746 11.5625 14.3746H15.3125C15.5611 14.3746 15.7996 14.2758 15.9754 14.1C16.1512 13.9242 16.25 13.6857 16.25 13.4371C16.25 13.1884 16.1512 12.95 15.9754 12.7742C15.7996 12.5984 15.5611 12.4996 15.3125 12.4996H12.5V7.18708Z"
                                            fill="white" />
                                    </svg>
                                </span>
                                <span class="nav-label">Activity Log</span>
                                <i class="fa-solid fa-chevron-right nav-chevron"></i>
                            </a>
                        </li></li>
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
                            <a href="pos-settings.php">
                                <span class="nav-icon">
                                    <i class="fa-solid fa-gear"></i>
                                </span>
                                <span class="nav-label">POS Settings</span>
                                <i class="fa-solid fa-chevron-right nav-chevron"></i>
                            </a>
                        </li>
                        <li>
                            <a href="adminsettings.php">
                                <span class="nav-icon">
                                    <i class="fa-solid fa-gear"></i>
                                </span>
                                <span class="nav-label">Admin Settings</span>
                                <i class="fa-solid fa-chevron-right nav-chevron"></i>
                            </a>
                        </li>
                        <li>
                            <a href="logout.php" class="logout-link">
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
                <div class="notif-wrap"><button class="icon-btn" id="notifBtn" type="button" aria-label="Inventory warnings" aria-expanded="false"><i class="fa-solid fa-triangle-exclamation"></i></button></div>
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
            <div class="inventory-content">

                <div class="inventory-header">
                    <div class="inventory-heading">
                        <h1 class="inventory-title">Ingredients</h1>
                        <p class="inventory-subtitle">Manage all ingredients used in the cafe.</p>
                    </div>
                    <div class="inventory-header-controls">
                        <label class="inventory-filter">
                            <span>Branch</span>
                            <select id="inventoryBranchSelect" aria-label="Filter inventory by branch">
                                <option value="">Loading branches...</option>
                            </select>
                        </label>
                        <button type="button" class="export-btn" id="exportBtn">
                        <i class="fa-solid fa-download"></i>
                        <span>Export report</span>
                    </button>
                    </div>
                </div>

                <div class="inventory-stock-warning" id="inventoryStockWarning" hidden role="status">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <div>
                        <strong>Stock warning</strong>
                        <p id="inventoryStockWarningText"></p>
                    </div>
                </div>

                <div class="inventory-tabs" role="tablist">
                    <div class="inventory-tab-group">
                        <button type="button" class="inventory-tab active" data-tab="ingredients" role="tab" aria-selected="true">Ingredients</button>
                        <button type="button" class="inventory-tab" data-tab="stock-in" role="tab" aria-selected="false">Stock In</button>
                        <button type="button" class="inventory-tab" data-tab="stock-history" role="tab" aria-selected="false">Stock History</button>
                    </div>
                </div>

                <div class="inventory-panel" id="ingredients" data-panel="ingredients">
                    <div class="inventory-search-row">
                        <div class="inventory-search">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            <input type="text" id="ingredientSearch" placeholder="Search ingredients...">
                        </div>
                        <button type="button" class="add-ingredient-btn" id="addIngredientBtn">
                            <i class="fa-solid fa-plus"></i>
                            Add Ingredients
                        </button>
                    </div>

                    <div class="table-wrap">
                        <table class="ingredients-table">
                            <thead>
                                <tr>
                                    <th>Ingredient</th>
                                    <th>Category</th>
                                    <th>Current Stock</th>
                                    <th>Unit</th>
                                    <th>Minimum Stock</th>
                                    <th>Servings Left</th>
                                    <th>Ingredient Sufficiency</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="ingredientsTableBody">
                                <tr>
                                    <td class="ing-name">Coffee Beans</td>
                                    <td>Coffee</td>
                                    <td>5,000</td>
                                    <td>g</td>
                                    <td>1,000</td>
                                    <td>25</td>
                                    <td><span class="status-pill in-stock">SUFFICIENT</span></td>
                                    <td class="ing-actions">
                                        <button class="icon-btn delete-btn" aria-label="Delete Coffee Beans"><i class="fa-solid fa-trash"></i></button>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="ing-name">Fresh Milk</td>
                                    <td>Dairy</td>
                                    <td>10,000</td>
                                    <td>L</td>
                                    <td>3,000</td>
                                    <td>50</td>
                                    <td><span class="status-pill in-stock">SUFFICIENT</span></td>
                                    <td class="ing-actions">
                                        <button class="icon-btn delete-btn" aria-label="Delete Fresh Milk"><i class="fa-solid fa-trash"></i></button>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="ing-name">Matcha Powder</td>
                                    <td>Powder</td>
                                    <td>1,200</td>
                                    <td>g</td>
                                    <td>1,000</td>
                                    <td>12</td>
                                    <td><span class="status-pill in-stock">SUFFICIENT</span></td>
                                    <td class="ing-actions">
                                        <button class="icon-btn delete-btn" aria-label="Delete Matcha Powder"><i class="fa-solid fa-trash"></i></button>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="ing-name">Clear Cup (22 oz)</td>
                                    <td>Packaging</td>
                                    <td>100</td>
                                    <td>pcs</td>
                                    <td>50</td>
                                    <td>100</td>
                                    <td><span class="status-pill in-stock">SUFFICIENT</span></td>
                                    <td class="ing-actions">
                                        <button class="icon-btn delete-btn" aria-label="Delete Clear Cup 22 oz"><i class="fa-solid fa-trash"></i></button>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="ing-name">Clear Cup (16 oz)</td>
                                    <td>Packaging</td>
                                    <td>100</td>
                                    <td>pcs</td>
                                    <td>50</td>
                                    <td>100</td>
                                    <td><span class="status-pill in-stock">SUFFICIENT</span></td>
                                    <td class="ing-actions">
                                        <button class="icon-btn delete-btn" aria-label="Delete Clear Cup 16 oz"><i class="fa-solid fa-trash"></i></button>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="ing-name">Chocolate Chips</td>
                                    <td>Syrup</td>
                                    <td>450</td>
                                    <td>ml</td>
                                    <td>100</td>
                                    <td>45</td>
                                    <td><span class="status-pill in-stock">SUFFICIENT</span></td>
                                    <td class="ing-actions">
                                        <button class="icon-btn delete-btn" aria-label="Delete Chocolate Chips"><i class="fa-solid fa-trash"></i></button>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="ing-name">Chocolate Syrup</td>
                                    <td>Syrup</td>
                                    <td>450</td>
                                    <td>ml</td>
                                    <td>100</td>
                                    <td>45</td>
                                    <td><span class="status-pill in-stock">SUFFICIENT</span></td>
                                    <td class="ing-actions">
                                        <button class="icon-btn delete-btn" aria-label="Delete Chocolate Syrup"><i class="fa-solid fa-trash"></i></button>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="ing-name">Condensed Milk</td>
                                    <td>Dairy</td>
                                    <td>390</td>
                                    <td>g</td>
                                    <td>100</td>
                                    <td>39</td>
                                    <td><span class="status-pill in-stock">SUFFICIENT</span></td>
                                    <td class="ing-actions">
                                        <button class="icon-btn delete-btn" aria-label="Delete Condensed Milk"><i class="fa-solid fa-trash"></i></button>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="ing-name">Caramel Syrup</td>
                                    <td>Syrup</td>
                                    <td>450</td>
                                    <td>ml</td>
                                    <td>100</td>
                                    <td>45</td>
                                    <td><span class="status-pill in-stock">SUFFICIENT</span></td>
                                    <td class="ing-actions">
                                        <button class="icon-btn delete-btn" aria-label="Delete Caramel Syrup"><i class="fa-solid fa-trash"></i></button>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="ing-name">Waffle Batter</td>
                                    <td>Mixes</td>
                                    <td>500</td>
                                    <td>g</td>
                                    <td>100</td>
                                    <td>50</td>
                                    <td><span class="status-pill in-stock">SUFFICIENT</span></td>
                                    <td class="ing-actions">
                                        <button class="icon-btn delete-btn" aria-label="Delete Waffle Batter"><i class="fa-solid fa-trash"></i></button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <p class="table-empty" id="ingredientsEmpty" hidden>No ingredients match your search.</p>
                    </div>
                </div>

                <div class="inventory-panel" id="stockIn" data-panel="stock-in" hidden>
                    <div class="inventory-search-row">
                        <div class="inventory-search">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            <input type="text" id="stockInSearch" placeholder="Search ingredients...">
                        </div>
                        <div class="stock-in-actions">
                            <p class="stock-in-status" id="stockInStatus" role="status" hidden>Stock In recorded.</p>
                            <button type="button" class="cancel-btn" id="cancelStockInBtn">Cancel</button>
                            <button type="button" class="save-btn" id="saveStockInBtn">Save Stock In</button>
                        </div>
                    </div>

                    <div class="table-wrap">
                        <table class="ingredients-table stock-in-table">
                            <thead>
                                <tr>
                                    <th>Ingredient</th>
                                    <th>Stock</th>
                                    <th>Category</th>
                                    <th>Unit</th>
                                    <th>Total</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="stockInTableBody">
                            </tbody>
                        </table>
                        <p class="table-empty" id="stockInEmpty" hidden>No ingredients match your search.</p>
                    </div>

                    <div class="stock-in-footer">
                        <div class="stock-summary">
                            <span class="stock-summary-item">Total Items: <strong id="stockTotalItems">0</strong></span>
                            <span class="stock-summary-item">Grand Total: <strong id="stockGrandTotal">0</strong> <em>(Total Units)</em></span>
                        </div>
                    </div>
                </div>
                <div class="inventory-panel" id="stockHistory" data-panel="stock-history" hidden>
                    <div class="stock-history-controls">
                        <div class="inventory-search">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            <input type="text" id="stockHistorySearch" placeholder="Search transaction type...">
                        </div>
                    </div>

                    <div class="table-wrap">
                        <table class="ingredients-table stock-history-table">
                            <thead>
                                <tr>
                                    <th>Ingredient</th>
                                    <th>Transaction Type</th>
                                    <th>Quantity</th>
                                    <th>Current Stock</th>
                                    <th>Date &amp; Time</th>
                                </tr>
                            </thead>
                            <tbody id="stockHistoryTableBody">
                            </tbody>
                        </table>
                        <p class="table-empty" id="stockHistoryEmpty" hidden>No transactions match your search.</p>
                    </div>
                </div>
                <!-- Add Ingredients Modal -->
                <div class="modal-overlay" id="addIngredientModalOverlay" hidden>
                    <div class="modal-box">
                        <h2 class="modal-title">Add Ingredients</h2>

                        <div class="modal-field">
                            <label for="newIngredientName">Ingredient Items</label>
                            <input type="text" id="newIngredientName" placeholder="Enter ingredient name">
                        </div>

                        <div class="modal-field">
                            <label for="newIngredientCategory">Category</label>
                            <select id="newIngredientCategory">
                                <option value="">Select category</option>
                                <option value="Coffee">Coffee</option>
                                <option value="Dairy">Dairy</option>
                                <option value="Powder">Powder</option>
                                <option value="Packaging">Packaging</option>
                                <option value="Syrup">Syrup</option>
                                <option value="Mixes">Mixes</option>
                            </select>
                        </div>

                        <div class="modal-field">
                            <label for="newIngredientUnit">Units</label>
                            <input type="text" id="newIngredientUnit" placeholder="e.g. g, ml, pcs, shot">
                        </div>

                        <div class="modal-field">
                            <label for="newIngredientMinStock">Minimum Stocks</label>
                            <input type="number" id="newIngredientMinStock" value="5" min="5">
                        </div>

                        <div class="modal-field">
                            <label for="newIngredientCurrentStock">Current Stocks</label>
                            <input type="number" id="newIngredientCurrentStock" placeholder="Enter the current stocks" min="0">
                        </div>

                        <div class="modal-actions">
                            <button type="button" class="cancel-btn" id="cancelAddIngredientBtn">Cancel</button>
                            <button type="button" class="save-btn" id="saveAddIngredientBtn">Save Ingredients</button>
                        </div>
                    </div>
                </div>

                <!-- Add Stock Modal -->
                <div class="modal-overlay" id="addStockModalOverlay" hidden>
                    <div class="modal-box">
                        <h2 class="modal-title">Add Stock</h2>

                        <div class="modal-field">
                            <label for="addStockIngredient">Ingredient Items</label>
                            <select id="addStockIngredient">
                                <option value="">Select ingredient</option>
                            </select>
                        </div>

                        <div class="modal-field">
                            <label for="addStockCategory">Category</label>
                            <input type="text" id="addStockCategory" readonly>
                        </div>

                        <div class="modal-field">
                            <label for="addStockUnit">Units</label>
                            <input type="text" id="addStockUnit" readonly>
                        </div>

                        <div class="modal-field">
                            <label for="addStockQuantity">Stock Quantity</label>
                            <input type="number" id="addStockQuantity" value="5" min="0">
                        </div>

                        <div class="modal-actions">
                            <button type="button" class="cancel-btn" id="cancelAddStockBtn">Cancel</button>
                            <button type="button" class="save-btn" id="saveAddStockBtn">Save Stock</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        document.querySelectorAll('.sidebar-nav a').forEach(link => {
            link.addEventListener('click', function(e) {
                const href = this.getAttribute('href');
                if (href === '#') e.preventDefault();
                document.querySelectorAll('.sidebar-nav a').forEach(l => l.classList.remove('active'));
                this.classList.add('active');
            });
        });
        (function () {
            const sidebar = document.getElementById("sidebar");
            const backdrop = document.getElementById("sidebarBackdrop");
            const mq = window.matchMedia("(max-width: 860px)");

            function openSidebar() {
                if (!mq.matches) return;
                sidebar.classList.add("expanded");
                backdrop.classList.add("show");
            }

            function closeSidebar() {
                sidebar.classList.remove("expanded");
                backdrop.classList.remove("show");
            }

            sidebar.addEventListener("click", () => {
                if (mq.matches && !sidebar.classList.contains("expanded")) {
                    openSidebar();
                }
            });

            sidebar.querySelectorAll(".sidebar-nav a").forEach((link) => {
                link.addEventListener("click", () => {
                    if (mq.matches) closeSidebar();
                });
            });

            backdrop.addEventListener("click", closeSidebar);
            mq.addEventListener("change", closeSidebar);
        })();
        (function () {
            const tabs = document.querySelectorAll(".inventory-tab");
            const panels = document.querySelectorAll(".inventory-panel");
            const addIngredientBtn = document.getElementById("addIngredientBtn");
            const searchInput = document.getElementById("ingredientSearch");
            const emptyState = document.getElementById("ingredientsEmpty");
            const branchSelect = document.getElementById("inventoryBranchSelect");
            const exportButton = document.getElementById("exportBtn");
            let selectedBranchId = "";

            // ADD THIS BLOCK - it is missing entirely right now
            tabs.forEach((tab) => {
                tab.addEventListener("click", () => {
                    tabs.forEach((t) => {
                        t.classList.remove("active");
                        t.setAttribute("aria-selected", "false");
                    });
                    tab.classList.add("active");
                    tab.setAttribute("aria-selected", "true");

                    const target = tab.dataset.tab;
                    panels.forEach((panel) => {
                        panel.hidden = panel.dataset.panel !== target;
                    });

                    addIngredientBtn.style.display = target === "ingredients" ? "":"none";
                });
            });
            const addIngredientOverlay = document.getElementById("addIngredientModalOverlay");
            const cancelAddIngredientBtn = document.getElementById("cancelAddIngredientBtn");
            const saveAddIngredientBtn = document.getElementById("saveAddIngredientBtn");
            const ingredientsTableBody = document.getElementById("ingredientsTableBody");
            let editingIngredient = null;

            function updateStockWarning(items) {
                const warning = document.getElementById("inventoryStockWarning");
                const warningText = document.getElementById("inventoryStockWarningText");
                const lowStockItems = (items || []).filter((item) => {
                    const status = String(item?.sufficiency_status ?? '').trim().toLowerCase();
                    if (status === 'sufficient') return false;
                    if (status === 'low' || status === 'insufficient') return true;
                    const stock = Number(item?.stock ?? 0);
                    const minStock = Number(item?.min_stock ?? 0);
                    return stock <= minStock;
                });

                if (!warning || !warningText) return;
                warning.hidden = lowStockItems.length === 0;
                if (!lowStockItems.length) {
                    warningText.textContent = "";
                    return;
                }

                const names = lowStockItems.slice(0, 5).map((item) => item.name || 'Ingredient').join(", ");
                const remaining = lowStockItems.length - Math.min(lowStockItems.length, 5);
                warningText.textContent = `${lowStockItems.length} ingredient${lowStockItems.length === 1 ? "" : "s"} need attention: ${names}${remaining > 0 ? ` and ${remaining} more` : ""}.`;
            }

            function renderIngredients(items) {
                ingredientsTableBody.innerHTML = "";
                updateStockWarning(items);

                if (!Array.isArray(items) || items.length === 0) {
                    emptyState.hidden = false;
                    emptyState.textContent = "No ingredients match your search.";
                    return;
                }

                items.forEach((item) => {
                    const name = item.name || "Unnamed Ingredient";
                    const category = item.category || "Uncategorized";
                    const unit = item.unit || "pcs";
                    const stock = Number(item.stock || 0);
                    const minStock = Number(item.min_stock || 0);
                    const servingsLeft = item.servings_left === null || item.servings_left === undefined ? "-" : Number(item.servings_left).toLocaleString();
                    const sufficiency = item.sufficiency_status || (stock <= 0 ? "insufficient" : stock <= minStock ? "low" : "sufficient");
                    const statusClass = sufficiency === "insufficient" ? "out-of-stock" : sufficiency === "low" ? "low-stock" : "in-stock";
                    const statusLabel = sufficiency === "insufficient" ? "INSUFFICIENT" : sufficiency === "low" ? "LOW" : "SUFFICIENT";

                    const row = document.createElement("tr");
                    row.innerHTML = `
                        <td class="ing-name">${name}</td>
                        <td>${category}</td>
                        <td>${stock.toLocaleString()}</td>
                        <td>${unit}</td>
                        <td>${minStock.toLocaleString()}</td>
                        <td>${servingsLeft}</td>
                        <td><span class="status-pill ${statusClass}">${statusLabel}</span></td>
                        <td class="ing-actions">
                            <button class="icon-btn delete-btn" data-ingredient-id="${item.id}" aria-label="Delete ${name}"><i class="fa-solid fa-trash"></i></button>
                        </td>
                    `;
                    ingredientsTableBody.appendChild(row);
                });

                emptyState.hidden = true;
            }

            async function loadBranches() {
                const response = await fetch('admin_data_api.php?action=branches', { cache: 'no-store' });
                const result = await response.json();
                if (!response.ok || !result.success) throw new Error(result.error || 'Branches could not be loaded');
                branchSelect.innerHTML = '';
                result.branches.forEach((branch) => {
                    const option = document.createElement('option');
                    option.value = branch.id;
                    option.textContent = `${branch.branch_code} / ${branch.branch_name}`;
                    branchSelect.appendChild(option);
                });
                selectedBranchId = String(result.branches[0]?.id || '');
                branchSelect.value = selectedBranchId;
                window.currentInventoryBranchId = selectedBranchId;
            }

            async function loadIngredients() {
                const params = new URLSearchParams({ action: 'ingredients', branch_id: selectedBranchId, view: 'all' });
                const response = await fetch(`admin_data_api.php?${params.toString()}`, { cache: 'no-store' });
                const result = await response.json();
                if (!response.ok || !result.success) throw new Error(result.error || 'Ingredients could not be loaded');
                window.currentIngredients = result.ingredients || [];
                renderIngredients(result.ingredients || []);
            }

            async function reloadInventoryViews() {
                await loadIngredients();
                window.dispatchEvent(new Event("inventoryBranchChanged"));
            }

            const EXPORT_BRAND_MAROON = [105, 39, 39];

            function loadLogoDataUrl() {
                return new Promise((resolve) => {
                    const img = new Image();
                    img.crossOrigin = 'anonymous';
                    img.onload = () => {
                        try {
                            const canvas = document.createElement('canvas');
                            canvas.width = img.naturalWidth;
                            canvas.height = img.naturalHeight;
                            const ctx = canvas.getContext('2d');
                            ctx.drawImage(img, 0, 0);
                            resolve(canvas.toDataURL('image/png'));
                        } catch (err) {
                            resolve(null);
                        }
                    };
                    img.onerror = () => resolve(null);
                    img.src = new URL('../img/LOGO.png', document.baseURI).href;
                });
            }

            async function generateInventoryPdfReport() {
                const { jsPDF } = window.jspdf;
                const doc = new jsPDF('p', 'mm', 'a4');
                const pageWidth = doc.internal.pageSize.getWidth();
                const marginX = 15;
                const now = new Date();
                const generatedOn = now.toLocaleString('en-US', {
                    year: 'numeric', month: 'long', day: 'numeric',
                    hour: '2-digit', minute: '2-digit'
                });

                const logoDataUrl = await loadLogoDataUrl();
                const logoSize = 18;
                if (logoDataUrl) {
                    doc.addImage(logoDataUrl, 'PNG', marginX, 12, logoSize, logoSize);
                }

                doc.setTextColor(20, 20, 20);
                doc.setFont('helvetica', 'bold');
                doc.setFontSize(16);
                doc.text('BOYCOLD CAFE', marginX + logoSize + 6, 19);

                doc.setFont('helvetica', 'normal');
                doc.setFontSize(9.5);
                doc.setTextColor(110, 110, 110);
                doc.text('Administration Panel', marginX + logoSize + 6, 25);

                doc.setFont('helvetica', 'bold');
                doc.setFontSize(12);
                doc.setTextColor(...EXPORT_BRAND_MAROON);
                doc.text('INVENTORY REPORT', pageWidth - marginX, 18, { align: 'right' });

                doc.setFont('helvetica', 'normal');
                doc.setFontSize(9);
                doc.setTextColor(110, 110, 110);
                doc.text(`Generated: ${generatedOn}`, pageWidth - marginX, 24, { align: 'right' });

                doc.setDrawColor(...EXPORT_BRAND_MAROON);
                doc.setLineWidth(0.8);
                doc.line(marginX, 34, pageWidth - marginX, 34);

                const branchLabel = branchSelect.selectedOptions[0]?.textContent || 'All Branches';
                const query = searchInput.value.trim().toLowerCase();
                const items = (window.currentIngredients || []).filter((item) => (item.name || '').toLowerCase().includes(query));

                doc.setFontSize(9.5);
                doc.setTextColor(60, 60, 60);
                doc.setFont('helvetica', 'bold');
                doc.text('Branch:', marginX, 41);
                doc.setFont('helvetica', 'normal');
                doc.text(branchLabel.trim(), marginX + 18, 41);

                doc.setFont('helvetica', 'bold');
                doc.text('Prepared By:', marginX, 46.5);
                doc.setFont('helvetica', 'normal');
                doc.text('Admin', marginX + 24, 46.5);

                const tableRows = items.map((item) => [
                    item.name || '',
                    item.category || 'Uncategorized',
                    item.stock?.toLocaleString() || '0',
                    item.unit || 'pcs',
                    item.min_stock?.toLocaleString() || '0',
                    item.servings_left === null || item.servings_left === undefined ? '-' : Number(item.servings_left).toLocaleString(),
                    item.sufficiency_status === 'insufficient' ? 'INSUFFICIENT' : item.sufficiency_status === 'low' ? 'LOW' : 'SUFFICIENT'
                ]);

                doc.autoTable({
                    startY: 56,
                    margin: { left: marginX, right: marginX },
                    head: [['Ingredient', 'Category', 'Current Stock', 'Unit', 'Min Stock', 'Servings Left', 'Status']],
                    body: tableRows,
                    styles: {
                        font: 'helvetica', fontSize: 7.5, cellPadding: 2.5,
                        lineColor: [196, 193, 193], lineWidth: 0.1, valign: 'middle'
                    },
                    headStyles: { fillColor: EXPORT_BRAND_MAROON, textColor: [255,255,255], fontStyle: 'bold', halign: 'left' },
                    alternateRowStyles: { fillColor: [247, 245, 243] },
                    didDrawPage: (data) => {
                        const pageCount = doc.internal.getNumberOfPages();
                        doc.setFontSize(8);
                        doc.setTextColor(140, 140, 140);
                        doc.text(`Page ${doc.internal.getCurrentPageInfo().pageNumber} of ${pageCount}`, pageWidth - marginX, doc.internal.pageSize.getHeight() - 10, { align: 'right' });
                        doc.text('BoyCold Cafe - Internal Document', marginX, doc.internal.pageSize.getHeight() - 10);
                    }
                });

                const fileDate = now.toISOString().slice(0, 10);
                doc.save(`inventory-report_${fileDate}.pdf`);
            }

            exportButton.addEventListener('click', async () => {
                const btn = exportButton;
                const originalHtml = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Generating...';

                try {
                    await generateInventoryPdfReport();
                } catch (error) {
                    console.error('Inventory PDF export failed:', error);
                    alert('Unable to generate the inventory report. Please refresh the page and try again.');
                } finally {
                    btn.disabled = false;
                    btn.innerHTML = originalHtml;
                }
            });
            branchSelect.addEventListener('change', async () => {
                selectedBranchId = branchSelect.value;
                window.currentInventoryBranchId = selectedBranchId;
                await reloadInventoryViews();
            });
            loadBranches().then(loadIngredients).catch((error) => {
                console.error('Unable to load inventory branches:', error);
                emptyState.hidden = false;
                emptyState.textContent = 'Unable to load inventory branches from the database.';
            });
            ingredientsTableBody.addEventListener("click", (event) => {
                const deleteButton = event.target.closest(".delete-btn");
                if (!deleteButton) return;
                const ingredient = window.currentIngredients?.find((item) => String(item.id) === deleteButton.dataset.ingredientId);
                if (!ingredient) return;
                if (confirm(`Are you sure you want to delete "${ingredient.name}"?`)) {
                    // Handle delete logic here
                    console.log("Delete ingredient:", ingredient);
                }
            });

            addIngredientBtn.addEventListener("click", () => {
                addIngredientOverlay.hidden = false;
            });

            cancelAddIngredientBtn.addEventListener("click", () => {
                addIngredientOverlay.hidden = true;
            });

            addIngredientOverlay.addEventListener("click", (e) => {
                if (e.target === addIngredientOverlay) addIngredientOverlay.hidden = true;
            });

            saveAddIngredientBtn.addEventListener("click", () => {
                const name = document.getElementById("newIngredientName").value.trim();
                const category = document.getElementById("newIngredientCategory").value;
                const unit = document.getElementById("newIngredientUnit").value.trim();
                const minStock = Number(document.getElementById("newIngredientMinStock").value) || 0;
                const currentStock = Number(document.getElementById("newIngredientCurrentStock").value) || 0;

                if (!name || !category || !unit) {
                    alert("Please fill in ingredient name, category, and units.");
                    return;
                }

                fetch('admin_data_api.php?action=ingredient_create', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        name,
                        category,
                        unit,
                        min_stock: minStock,
                        stock: currentStock,
                        branch_id: window.currentInventoryBranchId || '1'
                    })
                }).then(response => response.json()).then(result => {
                    if (!result.success) throw new Error(result.error || 'Ingredient could not be saved');

                    let statusClass = "in-stock";
                let statusLabel = "SUFFICIENT";
                let servingsLeft = "-";

                if (currentStock <= 0) {
                    statusClass = "out-of-stock";
                    statusLabel = "INSUFFICIENT";
                } else if (currentStock <= minStock) {
                    statusClass = "low-stock";
                    statusLabel = "LOW";
                }

                    const row = document.createElement("tr");
                row.innerHTML = `
                    <td class="ing-name">${name}</td>
                    <td>${category}</td>
                    <td>${currentStock.toLocaleString()}</td>
                    <td>${unit}</td>
                    <td>${minStock.toLocaleString()}</td>
                    <td>${servingsLeft}</td>
                    <td><span class="status-pill ${statusClass}">${statusLabel}</span></td>
                    <td class="ing-actions">
                        <button class="icon-btn delete-btn" aria-label="Delete ${name}"><i class="fa-solid fa-trash"></i></button>
                    </td>
                `;
                    ingredientsTableBody.appendChild(row);

                    document.getElementById("newIngredientName").value = "";
                document.getElementById("newIngredientCategory").value = "";
                document.getElementById("newIngredientUnit").value = "";
                document.getElementById("newIngredientMinStock").value = "";
                document.getElementById("newIngredientCurrentStock").value = "";
                    addIngredientOverlay.hidden = true;
                }).catch(error => alert(error.message));
            });

            searchInput.addEventListener("input", () => {
                const query = searchInput.value.trim().toLowerCase();
                let visibleCount = 0;

                document.querySelectorAll("#ingredientsTableBody tr").forEach((row) => {
                    const name = row.querySelector(".ing-name")?.textContent.toLowerCase() || "";
                    const matches = name.includes(query);
                    row.style.display = matches ? "" : "none";
                    if (matches) visibleCount++;
                });

                emptyState.hidden = visibleCount !== 0;
            });
        })();
        
        (function () {
            let INGREDIENT_CATALOG = [];

            const tbody = document.getElementById("stockInTableBody");
            const searchInput = document.getElementById("stockInSearch");
            const emptyState = document.getElementById("stockInEmpty");
            const totalItemsEl = document.getElementById("stockTotalItems");
            const grandTotalEl = document.getElementById("stockGrandTotal");
            const cancelBtn = document.getElementById("cancelStockInBtn");
            const saveBtn = document.getElementById("saveStockInBtn");
            const statusEl = document.getElementById("stockInStatus");

            const addStockOverlay = document.getElementById("addStockModalOverlay");
            const cancelAddStockBtn = document.getElementById("cancelAddStockBtn");
            const saveAddStockBtn = document.getElementById("saveAddStockBtn");
            const addStockIngredientSelect = document.getElementById("addStockIngredient");
            const addStockCategoryInput = document.getElementById("addStockCategory");
            const addStockUnitInput = document.getElementById("addStockUnit");
            const addStockQuantityInput = document.getElementById("addStockQuantity");

            let initialRowsHTML = "";

            function formatNumber(n) {
                return Number(n || 0).toLocaleString('en-US', {
                    minimumFractionDigits: 0,
                    maximumFractionDigits: 3
                });
            }

            function rowStockValue(row) {
                const input = row.querySelector(".stock-input");
                return input ? Number(input.value) || 0 : 0;
            }

            function updateRowTotal(row) {
                const value = rowStockValue(row);
                const unit = row.dataset.unit || "";
                row.querySelector(".stock-total").textContent = `${formatNumber(value)}${unit ? " " + unit : ""}`;
            }

            function recalcSummary() {
                const rows = Array.from(tbody.querySelectorAll("tr"));
                const grandTotal = rows.reduce((sum, row) => sum + rowStockValue(row), 0);
                totalItemsEl.textContent = rows.length;
                grandTotalEl.textContent = formatNumber(grandTotal);
            }

            function applyFilter() {
                const query = searchInput.value.trim().toLowerCase();
                let visibleCount = 0;

                tbody.querySelectorAll("tr").forEach((row) => {
                    const nameEl = row.querySelector(".ing-name, .ing-select");
                    const name = (nameEl.tagName === "SELECT" ? nameEl.value : nameEl.textContent).toLowerCase();
                    const matches = name.includes(query);
                    row.style.display = matches ? "" : "none";
                    if (matches) visibleCount++;
                });

                emptyState.hidden = visibleCount !== 0;
            }

            function attachRowHandlers(row) {
                row.querySelector(".stock-input").addEventListener("input", () => {
                    updateRowTotal(row);
                    recalcSummary();
                });

                row.querySelector(".delete-btn").addEventListener("click", () => {
                    row.remove();
                    recalcSummary();
                    applyFilter();
                });
            }

            function renderStockRows(items) {
                INGREDIENT_CATALOG = items.map((item) => ({
                    id: Number(item.id),
                    name: item.name,
                    category: item.category || "Uncategorized",
                    unit: item.unit || "pcs",
                    stock: Number(item.stock || 0)
                }));
                tbody.innerHTML = "";
                INGREDIENT_CATALOG.forEach((item) => {
                    const row = document.createElement("tr");
                    row.dataset.id = item.id;
                    row.dataset.currentStock = Number(item.stock || 0);
                    row.dataset.category = item.category;
                    row.dataset.unit = item.unit;
                    const currentStock = Number(item.stock || 0);
                    row.innerHTML = `
                        <td class="ing-name">${item.name}</td>
                        <td><input type="number" class="stock-input" value="${currentStock}" min="0" step="0.001" aria-label="${item.name} stock quantity"></td>
                        <td class="stock-category">${item.category}</td>
                        <td class="stock-unit">${item.unit}</td>
                        <td class="stock-total">${formatNumber(currentStock)} ${item.unit}</td>
                        <td class="ing-actions">
                            <button class="icon-btn delete-btn" aria-label="Remove ${item.name} row"><i class="fa-solid fa-trash"></i></button>
                        </td>
                    `;
                    tbody.appendChild(row);
                    attachRowHandlers(row);
                });
                initialRowsHTML = tbody.innerHTML;
                recalcSummary();
                applyFilter();
            }

            function loadStockIngredients() {
                const branchId = window.currentInventoryBranchId || '1';
                fetch(`admin_data_api.php?action=ingredients&branch_id=${encodeURIComponent(branchId)}&view=all`, { cache: 'no-store' })
                    .then((response) => response.json())
                    .then((result) => {
                        if (!result.success) throw new Error(result.error || 'Ingredients could not be loaded');
                        renderStockRows(result.ingredients || []);
                        addStockIngredientSelect.innerHTML = '<option value="">Select ingredient</option>';
                        INGREDIENT_CATALOG.forEach((item) => {
                            const opt = document.createElement("option");
                            opt.value = item.id;
                            opt.textContent = item.name;
                            addStockIngredientSelect.appendChild(opt);
                        });
                    })
                    .catch((error) => {
                        console.error('Unable to load Stock In ingredients:', error);
                        emptyState.hidden = false;
                        emptyState.textContent = 'Unable to load ingredients from the database.';
                    });
            }

            window.addEventListener('inventoryBranchChanged', loadStockIngredients);

            // --- Populate Add Stock modal dropdown ---
            addStockIngredientSelect.addEventListener("change", () => {
                const chosen = INGREDIENT_CATALOG.find((item) => String(item.id) === addStockIngredientSelect.value);
                addStockCategoryInput.value = chosen ? chosen.category : "";
                addStockUnitInput.value = chosen ? chosen.unit : "";
            });

            cancelAddStockBtn.addEventListener("click", () => {
                addStockOverlay.hidden = true;
            });

            addStockOverlay.addEventListener("click", (e) => {
                if (e.target === addStockOverlay) addStockOverlay.hidden = true;
            });

            saveAddStockBtn.addEventListener("click", () => {
                const chosen = INGREDIENT_CATALOG.find((item) => String(item.id) === addStockIngredientSelect.value);
                if (!chosen) {
                    alert("Please select an ingredient.");
                    return;
                }
                const quantity = Number(addStockQuantityInput.value) || 0;

                const row = document.createElement("tr");
                row.dataset.id = chosen.id;
                row.dataset.currentStock = 0;
                row.dataset.category = chosen.category;
                row.dataset.unit = chosen.unit;
                row.innerHTML = `
                    <td class="ing-name">${chosen.name}</td>
                    <td><input type="number" class="stock-input" value="${quantity}" min="0" step="1" aria-label="${chosen.name} stock quantity"></td>
                    <td class="stock-category">${chosen.category}</td>
                    <td class="stock-unit">${chosen.unit}</td>
                    <td class="stock-total">${quantity.toLocaleString()} ${chosen.unit}</td>
                    <td class="ing-actions">
                        <button class="icon-btn delete-btn" aria-label="Remove ${chosen.name} row"><i class="fa-solid fa-trash"></i></button>
                    </td>
                `;
                tbody.appendChild(row);
                attachRowHandlers(row);
                recalcSummary();
                applyFilter();

                addStockIngredientSelect.value = "";
                addStockCategoryInput.value = "";
                addStockUnitInput.value = "";
                addStockQuantityInput.value = "5";
                addStockOverlay.hidden = true;
            });

            cancelBtn.addEventListener("click", () => {
                searchInput.value = "";
                loadStockIngredients();
                statusEl.hidden = true;
            });

            saveBtn.addEventListener("click", () => {
                const items = Array.from(tbody.querySelectorAll('tr')).map(row => ({
                    id: Number(row.dataset.id) || 0,
                    name: row.querySelector('.ing-name')?.textContent.trim(),
                    quantity: Math.max(0, rowStockValue(row) - Number(row.dataset.currentStock || 0))
                })).filter(item => item.name && item.quantity > 0);

                if (!items.length) {
                    statusEl.textContent = 'No stock changes to save.';
                    statusEl.hidden = false;
                    return;
                }

                fetch('admin_data_api.php?action=stock_in', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ items, branch_id: window.currentInventoryBranchId || '1' })
                }).then(response => response.json()).then(result => {
                    if (!result.success) throw new Error(result.error || 'Stock could not be saved');
                    statusEl.textContent = 'Stock In recorded.';
                    statusEl.hidden = false;
                    loadStockIngredients();
                    clearTimeout(saveBtn._statusTimer);
                    saveBtn._statusTimer = setTimeout(() => { statusEl.hidden = true; }, 2500);
                }).catch(error => alert(error.message));
            });

            searchInput.addEventListener("input", applyFilter);

            loadStockIngredients();
        })();
        (function () {
            const searchInput = document.getElementById("stockHistorySearch");
            const filterSelect = document.getElementById("stockHistoryFilter");
            const tableBody = document.getElementById("stockHistoryTableBody");
            const emptyState = document.getElementById("stockHistoryEmpty");

            function escapeHtml(value) {
                return String(value ?? '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            function renderHistory(history) {
                tableBody.innerHTML = (history || []).map((item) => {
                    const isStockIn = item.movement_type === 'stock_in';
                    const label = isStockIn ? 'Stock In' : 'Order Deduction';
                    const icon = isStockIn ? 'fa-arrow-up' : 'fa-arrow-down';
                    const sign = isStockIn ? '+' : '-';
                    const quantity = Number(item.quantity || 0).toLocaleString('en-US', { maximumFractionDigits: 3 });
                    const reference = item.reference || (item.order_id ? `Order #${item.order_id}` : '');
                    const source = item.source ? String(item.source).toUpperCase() : '';
                    const products = item.product_name || '';
                    const referenceText = [reference, source, products].filter(Boolean).join(' - ');
                    const created = new Date(String(item.created_at).replace(' ', 'T'));
                    const date = Number.isNaN(created.getTime()) ? item.created_at : created.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
                    const time = Number.isNaN(created.getTime()) ? '' : created.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
                    return `<tr data-ingredient="${escapeHtml(item.name)}" data-type="${escapeHtml(item.movement_type)}" data-reference="${escapeHtml(referenceText)}">
                        <td class="ing-name">${escapeHtml(item.name)}</td>
                        <td>
                            <span class="transaction-pill ${isStockIn ? 'stock-in' : 'deduction'}"><i class="fa-solid ${icon}"></i> ${label}</span>
                            ${referenceText ? `<div class="stock-history-reference">${escapeHtml(referenceText)}</div>` : ''}
                        </td>
                        <td class="${isStockIn ? 'qty-positive' : 'qty-negative'}">${sign}${quantity} ${escapeHtml(item.unit)}</td>
                        <td>${Number(item.resulting_stock || 0).toLocaleString('en-US', { maximumFractionDigits: 3 })} ${escapeHtml(item.unit || '')}</td>
                        <td class="stock-history-date">${date}<br><span>${time}</span></td>
                    </tr>`;
                }).join('');

                const names = [...new Set((history || []).map((item) => item.name))].sort();
                filterSelect.innerHTML = '<option value="">All Ingredients</option>';
                names.forEach((name) => {
                    filterSelect.insertAdjacentHTML('beforeend', `<option value="${name}">${name}</option>`);
                });
                applyFilter();
            }

            function applyFilter() {
                const query = searchInput.value.trim().toLowerCase();
                const ingredientFilter = filterSelect.value;
                let visibleCount = 0;

                tableBody.querySelectorAll("tr").forEach((row) => {
                    const ingredientName = row.querySelector(".ing-name").textContent.trim().toLowerCase();
                    const type = row.querySelector(".transaction-pill").textContent.trim().toLowerCase();
                    const reference = (row.dataset.reference || '').toLowerCase();
                    const ingredient = row.dataset.ingredient;

                    const matchesQuery = ingredientName.includes(query) || type.includes(query) || reference.includes(query);
                    const matchesFilter = !ingredientFilter || ingredient === ingredientFilter;
                    const visible = matchesQuery && matchesFilter;

                    row.style.display = visible ? "" : "none";
                    if (visible) visibleCount++;
                });

                emptyState.hidden = visibleCount !== 0;
            }

            function loadStockHistory() {
                const branchId = window.currentInventoryBranchId || '1';
                return fetch(`admin_data_api.php?action=stock_history&branch_id=${encodeURIComponent(branchId)}`, { cache: 'no-store' })
                .then((response) => response.json())
                .then((result) => {
                    if (!result.success) throw new Error(result.error || 'Stock history could not be loaded');
                    renderHistory(result.history);
                })
                .catch((error) => {
                    console.error('Unable to load stock history:', error);
                    emptyState.hidden = false;
                    emptyState.textContent = 'Unable to load stock history from the database.';
                });
            }

            window.addEventListener('inventoryBranchChanged', loadStockHistory);
            loadStockHistory();

            searchInput.addEventListener("input", applyFilter);
            filterSelect.addEventListener("change", applyFilter);
        })();

    </script>
    <script src="admin-js/admin-responsive.js"></script>
    <script src="admin-js/inventory-warning.js"></script>
    <script src="admin-js/logout-modal.js"></script>
</body>

</html>
