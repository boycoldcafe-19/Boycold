<?php require_once __DIR__ . '/admin_guard.php'; ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="admin-css/mapping.css">
    <link rel="stylesheet" href="admin-css/dashboard.css">
    <link rel="stylesheet" href="admin-css/admin-sidebar.css">
    <link rel="stylesheet" href="admin-css/admin-responsive.css">
    <link rel="icon" href="../img/LOGO 2.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Afacad:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Gaegu:wght@400;700&display=swap" rel="stylesheet">
    <title>BoyCold - Ingredients Mapping</title>
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
                                        <!-- <path
                                            d="M0.5 5C0.367392 5 0.240215 4.94732 0.146447 4.85355C0.0526785 4.75979 0 4.63261 0 4.5V0.5C0 0.367392 0.0526785 0.240215 0.146447 0.146447C0.240215 0.0526785 0.367392 0 0.5 0H4.5C4.63261 0 4.75979 0.0526785 4.85355 0.146447C4.94732 0.240215 5 0.367392 5 0.5V4.5C5 4.63261 4.94732 4.75979 4.85355 4.85355C4.75979 4.94732 4.63261 5 4.5 5H0.5ZM7.5 5C7.36739 5 7.24021 4.94732 7.14645 4.85355C7.05268 4.75979 7 4.63261 7 4.5V0.5C7 0.367392 7.05268 0.240215 7.14645 0.146447C7.24021 0.0526785 7.36739 0 7.5 0H11.5C11.6326 0 11.7598 0.0526785 11.8536 0.146447C11.9473 0.240215 12 0.367392 12 0.5V4.5C12 4.63261 11.9473 4.75979 11.8536 4.85355C11.7598 4.94732 11.6326 5 11.5 5H7.5ZM0.5 12C0.367392 12 0.240215 11.9473 0.146447 11.8536C0.0526785 11.7598 0 11.6326 0 11.5V7.5C0 7.36739 0.0526785 7.24021 0.146447 7.14645C0.240215 7.05268 0.367392 7 0.5 7H4.5C4.63261 7 4.75979 7.05268 4.85355 7.14645C4.94732 7.24021 5 7.36739 5 7.5V11.5C5 11.6326 4.94732 11.7598 4.85355 11.8536C4.75979 11.9473 4.63261 12 4.5 12H0.5ZM7.5 12C7.36739 12 7.24021 11.9473 7.14645 11.8536C7.05268 11.7598 7 11.6326 7 11.5V7.5C7 7.36739 7.05268 7.24021 7.14645 7.14645C7.24021 7.05268 7.36739 7 7.5 7H11.5C11.6326 7 11.7598 7.05268 11.8536 7.14645C11.9473 7.24021 12 7.36739 12 7.5V11.5C12 11.6326 11.9473 11.7598 11.8536 11.8536C11.7598 11.9473 11.6326 12 11.5 12H7.5Z"
                                            fill="currentColor" /> -->
                                        <path
                                            d="M0.5 5C0.367392 5 0.240215 4.94732 0.146447 4.85355C0.0526785 4.75979 0 4.63261 0 4.5V0.5C0 0.367392 0.0526785 0.240215 0.146447 0.146447C0.240215 0.0526785 0.367392 0 0.5 0H4.5C4.63261 0 4.75979 0.0526785 4.85355 0.146447C4.94732 0.240215 5 0.367392 5 0.5V4.5C5 4.63261 4.94732 4.75979 4.85355 4.85355C4.75979 4.94732 4.63261 5 4.5 5H0.5ZM7.5 5C7.36739 5 7.24021 4.94732 7.14645 4.85355C7.05268 4.75979 7 4.63261 7 4.5V0.5C7 0.367392 7.05268 0.240215 7.14645 0.146447C7.24021 0.0526785 7.36739 0 7.5 0H11.5C11.6326 0 11.7598 0.0526785 11.8536 0.146447C11.9473 0.240215 12 0.367392 12 0.5V4.5C12 4.63261 11.9473 4.75979 11.8536 4.85355C11.7598 4.94732 11.6326 5 11.5 5H7.5ZM0.5 12C0.367392 12 0.240215 11.9473 0.146447 11.8536C0.0526785 11.7598 0 11.6326 0 11.5V7.5C0 7.36739 0.0526785 7.24021 0.146447 7.14645C0.240215 7.05268 0.367392 7 0.5 7H4.5C4.63261 7 4.75979 7.05268 4.85355 7.14645C4.94732 7.24021 5 7.36739 5 7.5V11.5C5 11.6326 4.94732 11.7598 4.85355 11.8536C4.75979 11.9473 4.63261 12 4.5 12H0.5ZM7.5 12C7.36739 12 7.24021 11.9473 7.14645 11.8536C7.05268 11.7598 7 11.6326 7 11.5V7.5C7 7.36739 7.05268 7.24021 7.14645 7.14645C7.24021 7.05268 7.36739 7 7.5 7H11.5C11.6326 7 11.7598 7.05268 11.8536 7.14645C11.9473 7.24021 12 7.36739 12 7.5V11.5C12 11.6326 11.9473 11.7598 11.8536 11.8536C11.7598 11.9473 11.6326 12 11.5 12H7.5Z"
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
                            <a href="mapping.php" class="active">
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
                            <a href="logout.php" class="logout-link" id="logoutBtn">
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
            <div class="page-content">

                <div class="page-header">
                    <h1 class="page-title">Ingredients Mapping</h1>
                    <p class="page-subtitle">Assign ingredients to each menu item and set the required quantity for
                        automatic inventory deduction</p>
                </div>

                <div class="mapping-grid">

                    <!-- LEFT: Select Menu Item -->
                    <section class="panel">
                        <div class="panel-header">
                            <span class="panel-title">Select Menu Item</span>
                        </div>
                        <div class="menu-search">
                            <input type="text" id="menuSearch" placeholder="Search menu item...">
                            <i class="fa-solid fa-magnifying-glass"></i>
                        </div>
                        <div class="menu-list" id="menuList"></div>
                        <button type="button" class="add-menu-item-btn" id="addMenuItemBtn">
                            <i class="fa-solid fa-plus"></i> Add New Menu Item
                        </button>
                    </section>

                    <!-- RIGHT: Map Ingredients -->
                    <section class="panel">
                        <div class="panel-header">
                            <span class="panel-title">Map Ingredients</span>
                            <label class="toggle-field">
                                Show inactive orders
                                <span class="switch">
                                    <input type="checkbox" id="showInactiveToggle">
                                    <span class="switch-slider"></span>
                                </span>
                            </label>
                        </div>
                        <div class="map-panel-body" id="mapPanel"></div>
                    </section>

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
                                        <li><a href="orders.php"><span class="nav-icon"><svg width="19" height="22" viewBox="0 0 19 22" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M14.8882 1H3.31469C2.03632 1 1 2.03632 1 3.31469V18.3602C1 19.6386 2.03632 20.6749 3.31469 20.6749H14.8882C16.1665 20.6749 17.2029 19.6386 17.2029 18.3602V3.31469C17.2029 2.03632 16.1665 1 14.8882 1Z" stroke="currentColor" stroke-width="2" /></svg></span><span class="nav-label">Orders</span><i class="fa-solid fa-chevron-right nav-chevron"></i></a></li>
                            Yes
                        </button>

                    </div>

                </div>

            </div>
        </div>
    </div>
    <script>
        document.querySelectorAll('.sidebar-nav a').forEach(link => {
            link.addEventListener('click', function (e) {
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
                    window.location.href = "logout.php";
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
        let MENU_ITEMS = [];
        const FALLBACK_MENU_ITEMS = [
            // Coffee
            { id: "americano", name: "Americano", price: 69, category: "Coffee", img: "Americano.png" },
            { id: "cafe-latte", name: "Cafe Latte", price: 85, category: "Coffee", img: "Cafe Latte.png" },
            { id: "spanish-latte", name: "Spanish Latte", price: 95, category: "Coffee", img: "Spanish Latte.png" },
            { id: "dirty-matcha", name: "Dirty Matcha", price: 119, category: "Coffee", img: "Dirty Matcha.png" },
            { id: "dark-mocha", name: "Dark Mocha", price: 139, category: "Coffee", img: "Dark Mocha.png" },
            { id: "white-mocha", name: "White Mocha", price: 129, category: "Coffee", img: "White Mocha.png" },
            { id: "french-vanilla", name: "French Vanilla", price: 135, category: "Coffee", img: "Franch Vanilla.png" },
            { id: "hazelnut-latte", name: "Hazelnut Latte", price: 135, category: "Coffee", img: "Hazelnut Latte.png" },

            // Non-Coffee
            { id: "strawberry-milk", name: "Strawberry Milk", price: 79, category: "Non-Coffee", img: "Strawberry Milk.png" },
            { id: "blueberry-milk", name: "Blueberry Milk", price: 79, category: "Non-Coffee", img: "Blueberry Milk.png" },
            { id: "milky-oreo", name: "Milky Oreo", price: 85, category: "Non-Coffee", img: "Milky Oreo.png" },
            { id: "white-cocoa", name: "White cocoa", price: 95, category: "Non-Coffee", img: "White cocoa.png" },
            { id: "choco-berry", name: "Choco Berry", price: 109, category: "Non-Coffee", img: "Choco Berry.png" },
            { id: "choco-vanilla-cookie", name: "Choco Vanilla Cookie", price: 129, category: "Non-Coffee", img: "Choco Vanilla Cookie.png" },
            { id: "choco-banana-pudding", name: "Choco Banana Pudding", price: 179, category: "Non-Coffee", img: "Choco Banna Pudding.png" },

            // Special Coffee
            { id: "sea-salt-latte", name: "Sea Salt Latte", price: 115, category: "Special Coffee", img: "Sea salt Latte.png" },
            { id: "salted-mango-dream", name: "Salted Mango Dream", price: 139, category: "Special Coffee", img: "Salted Mango Dream.png" },
            { id: "berry-caramel-bliss", name: "Berry Caramel Bliss", price: 139, category: "Special Coffee", img: "Berry Caramel Bliss.png" },
            { id: "caramel-macchiato", name: "Caramel Macchiato", price: 139, category: "Special Coffee", img: "Caramel Macchiato.png" },
            { id: "butterscotch-latte", name: "Butter Scotch Latte", price: 139, category: "Special Coffee", img: "Butter scotch latte.png" },
            { id: "salted-caramel", name: "Salted Caramel", price: 139, category: "Special Coffee", img: "Salted Caramel.png" },
            { id: "salted-macadamia", name: "Salted Macadamia", price: 139, category: "Special Coffee", img: "Salted Macadamia.png" },
            { id: "cheesecake-latte", name: "Cheesecake Latte", price: 149, category: "Special Coffee", img: "Cheesecake Latte.png" },
            { id: "einspanner-latte", name: "Einspanner Latte", price: 149, category: "Special Coffee", img: "Einspanner Latte.png" },
            { id: "biscoff-creamy-latte", name: "Biscoff Creamy Latte", price: 159, category: "Special Coffee", img: "Biscoff Creamy Latte.png" },
            { id: "nutella-hazelnut-latte", name: "Nutella Hazelnut Latte", price: 169, category: "Special Coffee", img: "Nutella Hazelnut latte.png" },
            { id: "tiramisu-latte", name: "Tiramisu Latte", price: 179, category: "Special Coffee", img: "Tiramisu Latte.png" },

            // Matcha Fusion
            { id: "pure-matcha", name: "Pure Matcha", price: 80, category: "Matcha Fusion", img: "Pure matcha.png" },
            { id: "matcha-latte", name: "Matcha Latte", price: 85, category: "Matcha Fusion", img: "Matcha Latte.png" },
            { id: "mango-matcha", name: "Mango Matcha", price: 89, category: "Matcha Fusion", img: "Mango matcha.png" },
            { id: "sea-salt-matcha", name: "Sea Salt Matcha", price: 95, category: "Matcha Fusion", img: "Seasalt Matcha.png" },
            { id: "matcha-freddo", name: "Matcha Freddo", price: 99, category: "Matcha Fusion", img: "Matcha Freddo.png" },
            { id: "choco-matcha", name: "Choco Matcha", price: 109, category: "Matcha Fusion", img: "Choco Matcha.png" },
            { id: "strawberry-matcha", name: "Strawberry Matcha", price: 115, category: "Matcha Fusion", img: "Strawberry Matcha.png" },
            { id: "cheesecake-matcha", name: "Cheesecake Matcha", price: 119, category: "Matcha Fusion", img: "Cheesecake Matcha.png" },
            { id: "lavender-matcha", name: "Lavander Matcha", price: 119, category: "Matcha Fusion", img: "Lavander Matcha.png" },
            { id: "matcha-banana-pudding", name: "Matcha Banana Pudding", price: 179, category: "Matcha Fusion", img: "Matcha banana Pudding.png" },

            // Fruit Shake
            { id: "mango-graham", name: "Mango Graham", price: 65, category: "Fruit Shake", img: "Mango graham.png" },
            { id: "strawberry-shake", name: "Strawberry Shake", price: 65, category: "Fruit Shake", img: "Strawberry shake.png" },
            { id: "blueberry-shake", name: "Blueberry Shake", price: 65, category: "Fruit Shake", img: "BLUEBERRY SHAKE 1.png" },
            { id: "mango-oreo", name: "Mango Oreo", price: 79, category: "Fruit Shake", img: "mango oreo.png" },
            { id: "berry-oreo", name: "Berry Oreo", price: 79, category: "Fruit Shake", img: "Berry Oreo.png" },
            { id: "berry-mango", name: "Berry Mango", price: 79, category: "Fruit Shake", img: "Berry mango.png" },

            // Frappe Series
            { id: "hershey-delight", name: "Hershey Delight", price: 95, category: "Frappe Series", img: "hershey delight.png" },
            { id: "oreo-frappe", name: "Oreo Frappe", price: 105, category: "Frappe Series", img: "Oreo Frappe.png" },
            { id: "matcha-frappe", name: "Matcha Frappe", price: 105, category: "Frappe Series", img: "Matcha Frappe.png" },
            { id: "java-chips", name: "Java Chips", price: 199, category: "Frappe Series", img: "Java Chips.png" },
            { id: "cheesecake-frappe", name: "Cheesecake Frappe", price: 129, category: "Frappe Series", img: "Cheesecake Frappe.png" },
            { id: "white-smore-frappe", name: "White Smore Frappe", price: 129, category: "Frappe Series", img: "white smores.png" },
            { id: "caramel-frappe", name: "Caramel Frappe", price: 139, category: "Frappe Series", img: "Caramel Frappe.png" },
            { id: "biscoff-frappe", name: "Biscoff Frappe", price: 139, category: "Frappe Series", img: "Biscoff frappe.png" },
            { id: "nuttela-hazelnut-frappe", name: "Nuttela Hazelnut Frappe", price: 149, category: "Frappe Series", img: "Nuttela Hazelnut Frappe.png" },

            // Snacks - waffles
            { id: "waffle-chocolate", name: "Lolly Waffle Chocolate", price: 69, category: "Snacks", img: "Chocolate waffle.png" },
            { id: "waffle-ube", name: "Lolly Waffle Ube", price: 65, category: "Snacks", img: "ube waffle.png" },
            { id: "waffle-matcha", name: "Lolly Waffle Matcha", price: 69, category: "Snacks", img: "Matcha waffle.png" },
            { id: "waffle-strawberry", name: "Lolly Waffle Strawberry", price: 69, category: "Snacks", img: "Strawberry waffle.png" },
            { id: "waffle-oreo", name: "Lolly Waffle Oreo", price: 65, category: "Snacks", img: "Oreo waffle.png" },
            { id: "waffle-tiramisu", name: "Lolly Waffle Tiramisu", price: 75, category: "Snacks", img: "tiramisu waffle.png" },
            { id: "waffle-biscoff", name: "Lolly Waffle Biscoff", price: 89, category: "Snacks", img: "Biscoff waffle.png" },

            // Snacks - bites & mains
            { id: "french-fries", name: "French Fries", price: 69, category: "Snacks", img: "Fries.png" },
            { id: "chicken-poppers", name: "Chicken Poppers", price: 79, category: "Snacks", img: "Chicken Poppers.png" },
            { id: "beef-nachos", name: "Beef Natchos", price: 149, category: "Snacks", img: "Beef Natchos.png" },
            { id: "fries-poppers", name: "Fries and Chicken Poppers", price: 99, category: "Snacks", img: "Chicken poppers and fries.png" },
            { id: "beef-quesadilla", name: "Beef Quesadilla", price: 149, category: "Snacks", img: "Beef Quesadilla.png" },
            { id: "chicken-quesadilla", name: "Chicken Quesadilla", price: 159, category: "Snacks", img: "Chicken Quesadilla.png" },
            { id: "messy-tuna-spinach", name: "Messy Tuna Spinach", price: 179, category: "Snacks", img: "Messy Tuna Spinach.png" },
        ];

        const IMG_BASE = "../";

        function resolveMenuImagePath(value) {
            const image = String(value || '').trim();
            if (!image) return "../img/default.png";
            if (/^(?:data:image\/|https?:\/\/|\/\/)/i.test(image) || image.startsWith('../')) return image;
            if (image.startsWith('/')) return `.${image}`;
            if (image.startsWith('uploads/') || image.startsWith('picture/') || image.startsWith('img/')) {
                return `../${image.replace(/^\.\//, '')}`;
            }
            return `../${image.replace(/^\.\//, '')}`;
        }

        async function loadProducts() {
            const response = await fetch('admin_data_api.php?action=products', { cache: 'no-store' });
            const result = await response.json();
            if (!response.ok || !result.success) {
                throw new Error(result.error || 'Products could not be loaded');
            }

            MENU_ITEMS = (result.products || []).map((product) => ({
                id: String(product.id),
                name: product.product_name,
                price: Number(product.price) || 0,
                category: product.category || 'Uncategorized',
                img: resolveMenuImagePath(product.image),
                available: Number(product.is_available) !== 0
            }));

            if (!MENU_ITEMS.length) throw new Error('No products found in the database');
            selectedItemId = MENU_ITEMS.some((item) => item.name.toLowerCase() === 'hershey delight')
                ? MENU_ITEMS.find((item) => item.name.toLowerCase() === 'hershey delight').id
                : MENU_ITEMS[0].id;
        }

        async function loadIngredients() {
            const response = await fetch('admin_data_api.php?action=ingredient_library', { cache: 'no-store' });
            const result = await response.json();
            if (!response.ok || !result.success) {
                throw new Error(result.error || 'Ingredients could not be loaded');
            }

            INGREDIENT_LIBRARY = (result.ingredients || []).map((ingredient) => ({
                name: ingredient.name,
                unit: ingredient.unit,
                cost: 0
            }));
            if (!INGREDIENT_LIBRARY.length) throw new Error('No ingredients found in the database');
        }

        /* Master ingredient list - used to populate the "Add Ingredients" dropdown
        with a known unit + cost per unit, so totals calculate automatically. */
        let INGREDIENT_LIBRARY = [
            { name: "Espresso Shot", unit: "shot", cost: 6 },
            { name: "Fresh Milk", unit: "g", cost: 6 },
            { name: "Condensed Milk", unit: "ml", cost: 6 },
            { name: "Chocolate Syrup", unit: "g", cost: 6 },
            { name: "Whipped Cream", unit: "g", cost: 6 },
            { name: "Matcha Powder", unit: "g", cost: 8 },
            { name: "Vanilla Syrup", unit: "ml", cost: 5 },
            { name: "Caramel Syrup", unit: "ml", cost: 5 },
            { name: "Hazelnut Syrup", unit: "ml", cost: 5 },
            { name: "Biscoff Spread", unit: "g", cost: 9 },
            { name: "Oreo Cookie", unit: "pcs", cost: 4 },
            { name: "Cheesecake Cream", unit: "g", cost: 7 },
            { name: "Tiramisu Powder", unit: "g", cost: 7 },
            { name: "Nutella", unit: "g", cost: 9 },
            { name: "Lavender Syrup", unit: "ml", cost: 6 },
            { name: "Graham Crackers", unit: "g", cost: 3 },
            { name: "Mango Puree", unit: "ml", cost: 5 },
            { name: "Strawberry Puree", unit: "ml", cost: 5 },
            { name: "Blueberry Puree", unit: "ml", cost: 5 },
            { name: "Sea Salt Cream", unit: "g", cost: 6 },
            { name: "Brown Sugar Syrup", unit: "ml", cost: 5 },
            { name: "Ice", unit: "g", cost: 1 },
            { name: "Cup 16oz", unit: "pcs", cost: 3 },
            { name: "Cup 22oz", unit: "pcs", cost: 4 },
            { name: "Straw", unit: "pcs", cost: 1 },
            { name: "Waffle Batter", unit: "g", cost: 5 },
            { name: "French Fries (frozen)", unit: "g", cost: 3 },
            { name: "Chicken Popper (frozen)", unit: "g", cost: 4 },
            { name: "Tortilla Wrap", unit: "pcs", cost: 6 },
            { name: "Beef Filling", unit: "g", cost: 8 },
            { name: "Chicken Filling", unit: "g", cost: 7 },
            { name: "Nacho Chips", unit: "g", cost: 4 },
            { name: "Tuna Filling", unit: "g", cost: 7 },
            { name: "Spinach", unit: "g", cost: 3 },
        ];

        /* In-memory mapping store: { menuItemId: [ {ingredient, unit, qty, cost} ] }
        Pre-seeded for Hershey Delight to mirror the current saved mapping. */
        const mappingStore = {};

        let selectedItemId = null;
        let showInactiveOnly = false;

        const peso = (n) => `₱ ${Number(n).toFixed(2)}`;

        function renderMenuList(filterText = "") {
            const list = document.getElementById("menuList");
            const term = filterText.trim().toLowerCase();
            const items = MENU_ITEMS.filter((m) => m.name.toLowerCase().includes(term));

            if (!items.length) {
                list.innerHTML = `<div class="menu-empty">No menu items match "${filterText}"</div>`;
                return;
            }

            list.innerHTML = items
                .map(
                    (m) => `
                <button type="button" class="menu-item ${m.id === selectedItemId ? "selected" : ""}" data-id="${m.id}">
                    <span class="menu-item-thumb"><img src="${resolveMenuImagePath(m.img)}" alt="${m.name}" loading="lazy"></span>
                    <span class="menu-item-info">
                        <span class="menu-item-name">${m.name}</span>
                        <span class="menu-item-price">${peso(m.price)}</span>
                    </span>
                </button>`
                )
                .join("");

            list.querySelectorAll(".menu-item").forEach((btn) => {
                btn.addEventListener("click", async () => {
                    selectedItemId = btn.dataset.id;
                    renderMenuList(document.getElementById("menuSearch").value);
                    renderMapPanel();
                    const item = MENU_ITEMS.find((menuItem) => menuItem.id === selectedItemId);
                    if (item) await loadMappingForItem(item.id, item.name);
                });
            });
        }

        async function loadMappingForItem(itemId, productName) {
            const response = await fetch(`admin_data_api.php?action=mapping_get&product_name=${encodeURIComponent(productName)}`, { cache: 'no-store' });
            const result = await response.json();
            if (!response.ok || !result.success) {
                throw new Error(result.error || 'Mapping could not be loaded');
            }

            mappingStore[itemId] = (result.mapping || []).map((row) => ({
                ingredient: row.ingredient,
                unit: row.unit,
                qty: row.amount,
                cost: 0,
                total: 0
            }));
            renderMapPanel();
        }

        function ingredientOptionsHTML(selectedName) {
            return INGREDIENT_LIBRARY.map(
                (ing) => `<option value="${ing.name}" ${ing.name === selectedName ? "selected" : ""}>${ing.name}</option>`
            ).join("");
        }

        function renderMapPanel() {
            const item = MENU_ITEMS.find((m) => m.id === selectedItemId);
            const panel = document.getElementById("mapPanel");
            if (!item) {
                panel.innerHTML = `<div class="map-empty">Select a menu item on the left to map its ingredients.</div>`;
                return;
            }

            const rows = mappingStore[item.id] || [];

            panel.innerHTML = `
                <div class="item-info-bar">
                    <div class="item-info-left">
                        <span class="item-info-thumb"><img src="${resolveMenuImagePath(item.img)}" alt="${item.name}"></span>
                        <span class="item-info-text">
                            <span class="item-info-name">${item.name}</span>
                            <span class="item-info-sub">Category: ${item.category}</span>
                        </span>
                    </div>
                    <div class="item-info-right">
                        <span class="item-info-price-label">Selling Price</span>
                        <span class="item-info-price">${peso(item.price)}</span>
                    </div>
                    <span class="status-badge active">Active</span>
                </div>

                <div class="ingredient-table">
                    <div class="ingredient-row ingredient-head">
                        <span>Ingredient</span>
                        <span>Unit</span>
                        <span>Qty Per Serving</span>
                        <span>Cost Per Unit</span>
                        <span>Total Cost</span>
                        <span></span>
                    </div>
                    <div id="ingredientRows">
                        ${rows.length
                    ? rows
                        .map(
                            (row, i) => `
                            <div class="ingredient-row" data-index="${i}">
                                <select class="ing-select">${ingredientOptionsHTML(row.ingredient)}</select>
                                <input class="ing-unit" type="text" value="${row.unit}" placeholder="e.g. ml">
                                <input class="ing-qty" type="number" min="0" step="any" value="${row.qty}" placeholder="0">
                                <span class="ing-cost">${peso(row.cost)}</span>
                                <span class="ing-total">${peso(row.total)}</span>
                                <button type="button" class="ing-delete" aria-label="Remove ingredient" data-index="${i}">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>`
                        )
                        .join("")
                    : `<div class="ingredient-empty">No ingredients mapped yet. Click "Add Ingredients" below to start.</div>`
                }
                    </div>
                </div>

                <button type="button" class="add-ingredients-btn" id="addIngredientBtn">
                    <i class="fa-solid fa-plus"></i> Add Ingredients
                </button>

                <div class="map-footer">
                    <div class="estimated-cost">
                        <span class="estimated-cost-label">Estimated Cost per serving</span>
                        <span class="estimated-cost-value" id="estimatedCost">${peso(estimateTotal(rows))}</span>
                    </div>
                    <button type="button" class="save-mapping-btn" id="saveMappingBtn">Save Mapping</button>
                </div>
            `;

            wireMapPanelEvents(item.id, item.name);
        }

        function estimateTotal(rows) {
            return rows.reduce((sum, r) => sum + (Number(r.total) || 0), 0);
        }

        function wireMapPanelEvents(itemId, productName) {
            const rows = mappingStore[itemId] || [];

            document.querySelectorAll("#ingredientRows .ingredient-row").forEach((rowEl) => {
                const idx = Number(rowEl.dataset.index);

                const select = rowEl.querySelector(".ing-select");
                const unitInput = rowEl.querySelector(".ing-unit");
                const qtyInput = rowEl.querySelector(".ing-qty");
                const costEl = rowEl.querySelector(".ing-cost");
                const totalEl = rowEl.querySelector(".ing-total");
                const delBtn = rowEl.querySelector(".ing-delete");

                select.addEventListener("change", () => {
                    const lib = INGREDIENT_LIBRARY.find((i) => i.name === select.value);
                    rows[idx].ingredient = select.value;
                    if (lib) {
                        rows[idx].cost = lib.cost;
                        if (!unitInput.value) {
                            rows[idx].unit = lib.unit;
                            unitInput.value = lib.unit;
                        }
                        costEl.textContent = peso(lib.cost);
                        recalcRow(idx, rows, qtyInput, totalEl);
                    }
                });

                unitInput.addEventListener("input", () => {
                    rows[idx].unit = unitInput.value;
                });

                qtyInput.addEventListener("input", () => {
                    rows[idx].qty = qtyInput.value;
                    recalcRow(idx, rows, qtyInput, totalEl);
                });

                delBtn.addEventListener("click", () => {
                    rows.splice(idx, 1);
                    renderMapPanel();
                });
            });

            document.getElementById("addIngredientBtn").addEventListener("click", () => {
                const defaultIng = INGREDIENT_LIBRARY[0];
                rows.push({ ingredient: defaultIng.name, unit: defaultIng.unit, qty: "", cost: defaultIng.cost, total: 0 });
                mappingStore[itemId] = rows;
                renderMapPanel();
            });

            document.getElementById("saveMappingBtn").addEventListener("click", () => {
                mappingStore[itemId] = rows;

                const incomplete = rows.filter(row => !(Number(row.qty) > 0));
                if (incomplete.length) {
                    alert(`Please enter a quantity for: ${incomplete.map(row => row.ingredient).join(", ")}. Rows without a quantity are not saved.`);
                    return;
                }

                const btn = document.getElementById("saveMappingBtn");
                fetch('admin_data_api.php?action=mapping_save', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ product_name: productName, rows: rows.map(row => ({ ingredient: row.ingredient, amount: row.qty })) })
                }).then(async response => {
                    const result = await response.json();
                    if (!response.ok) throw new Error(result.error || `Request failed (${response.status})`);
                    return result;
                }).then(result => {
                    if (!result.success) throw new Error(result.error || 'Mapping could not be saved');
                    const original = btn.textContent;
                    btn.textContent = "Saved";
                    btn.disabled = true;
                    setTimeout(() => { btn.textContent = original; btn.disabled = false; }, 1200);
                }).catch(error => alert(error.message || 'Request could not be completed'));
            });
        }

        function recalcRow(idx, rows, qtyInput, totalEl) {
            const qty = Number(qtyInput.value) || 0;
            const cost = Number(rows[idx].cost) || 0;
            const total = qty > 0 ? qty * cost : rows[idx].total;
            rows[idx].total = qty > 0 ? total : rows[idx].total;
            totalEl.textContent = peso(rows[idx].total);
            const estimatedEl = document.getElementById("estimatedCost");
            if (estimatedEl) estimatedEl.textContent = peso(estimateTotal(rows));
        }

        document.addEventListener("DOMContentLoaded", async () => {
            try {
                await loadProducts();
                await loadIngredients();
            } catch (error) {
                console.error('Unable to load products from the database:', error);
                MENU_ITEMS = [];
                INGREDIENT_LIBRARY = [];
                renderMenuList();
                renderMapPanel();
            }

            renderMenuList();
            renderMapPanel();

            const selectedMenuItem = MENU_ITEMS.find(item => item.id === selectedItemId);
            if (selectedMenuItem) {
                loadMappingForItem(selectedMenuItem.id, selectedMenuItem.name)
                    .catch((error) => console.error('Unable to load selected product mapping:', error));
            }

            document.getElementById("menuSearch").addEventListener("input", (e) => {
                renderMenuList(e.target.value);
            });

            document.getElementById("addMenuItemBtn").addEventListener("click", () => {
                alert("Open the Menu Management page to add a new menu item, then come back here to map its ingredients.");
            });

            const toggle = document.getElementById("showInactiveToggle");
            toggle.addEventListener("change", () => {
                showInactiveOnly = toggle.checked;
                document.getElementById("mapPanel").classList.toggle("show-inactive", showInactiveOnly);
            });
        });

    </script>
    <script src="admin-js/admin-responsive.js"></script>
    <script src="admin-js/inventory-warning.js"></script>
</body>

</html>
