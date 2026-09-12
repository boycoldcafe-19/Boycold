<?php
require_once __DIR__ . '/admin_guard.php';

function boycold_feedback_photo_url($photo): string
{
    $photo = trim((string) $photo);
    if ($photo === '') {
        return '';
    }

    if (preg_match('#^(https?:)?//#i', $photo) || preg_match('#^data:#i', $photo)) {
        return $photo;
    }

    $normalized = str_replace('\\', '/', $photo);
    $normalized = preg_replace('#^\./+#', '', $normalized);
    $normalized = preg_replace('#^\.\./+#', '', $normalized);
    $normalized = ltrim($normalized, '/');

    if ($normalized === '') {
        return '';
    }

    return '/' . $normalized;
}

$reviews = [];
$reviewQuery = $connect->query("SELECT r.id, r.order_id, r.rating, r.review, r.created_at,
                                       CONCAT(u.firstname, ' ', u.lastname) AS customer_name,
                                       u.email
                                FROM order_reviews r
                                INNER JOIN users u ON u.id = r.user_id
                                ORDER BY r.created_at DESC, r.id DESC");
if ($reviewQuery) {
    while ($row = $reviewQuery->fetch_assoc()) $reviews[] = $row;
}

$reviewCount = count($reviews);
$averageRating = $reviewCount ? array_sum(array_column($reviews, 'rating')) / $reviewCount : 0;
$ratingCounts = array_fill(1, 5, 0);
foreach ($reviews as $review) $ratingCounts[(int)$review['rating']]++;

$reports = [];
$reportQuery = $connect->query("SELECT r.id, r.order_id, r.issue, r.details, r.photo_paths, r.created_at,
                                       CONCAT(u.firstname, ' ', u.lastname) AS customer_name, u.email
                                FROM order_reports r
                                INNER JOIN users u ON u.id = r.user_id
                                ORDER BY r.created_at DESC, r.id DESC");
if ($reportQuery) {
    while ($row = $reportQuery->fetch_assoc()) $reports[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="admin-css/feedback.css">
    <link rel="stylesheet" href="admin-css/admin-sidebar.css">
    <link rel="stylesheet" href="admin-css/admin-responsive.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Afacad:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Gaegu:wght@400;700&display=swap" rel="stylesheet">
    <title>BoyCold - Feedback & Reviews</title>
</head>
<body>
    <div class="feedback-shell">
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-brand">
                <span class="brand-mark" aria-hidden="true"><img src="../img/ChatGPT Image Jun 23, 2026, 09_22_57 PM 1.png" alt=""></span>
                <span class="brand-text"><span class="brand-name">B<span class="special-letter">o</span><span class="special-letter-2">y</span>C<span class="special-letter">o</span>LD CAFE</span><span class="brand-sub">Administration Panel</span></span>
            </div>
            <nav class="sidebar-nav" aria-label="Admin navigation">
                <div class="nav-top">
                    <ul>
                        <li><a href="dashboard.php"><span class="nav-icon1"><svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M0.5 5C0.367392 5 0.240215 4.94732 0.146447 4.85355C0.0526785 4.75979 0 4.63261 0 4.5V0.5C0 0.367392 0.0526785 0.240215 0.146447 0.146447C0.240215 0.0526785 0.367392 0 0.5 0H4.5C4.63261 0 4.75979 0.0526785 4.85355 0.146447C4.94732 0.240215 5 0.367392 5 0.5V4.5C5 4.63261 4.94732 4.75979 4.85355 4.85355C4.75979 4.94732 4.63261 5 4.5 5H0.5ZM7.5 5C7.36739 5 7.24021 4.94732 7.14645 4.85355C7.05268 4.75979 7 4.63261 7 4.5V0.5C7 0.367392 7.05268 0.240215 7.14645 0.146447C7.24021 0.0526785 7.36739 0 7.5 0H11.5C11.6326 0 11.7598 0.0526785 11.8536 0.146447C11.9473 0.240215 12 0.367392 12 0.5V4.5C12 4.63261 11.9473 4.75979 11.8536 4.85355C11.7598 4.94732 11.6326 5 11.5 5H7.5ZM0.5 12C0.367392 12 0.240215 11.9473 0.146447 11.8536C0.0526785 11.7598 0 11.6326 0 11.5V7.5C0 7.36739 0.0526785 7.24021 0.146447 7.14645C0.240215 7.05268 0.367392 7 0.5 7H4.5C4.63261 7 4.75979 7.05268 4.85355 7.14645C4.94732 7.24021 5 7.36739 5 7.5V11.5C5 11.6326 4.94732 11.7598 4.85355 11.8536C4.75979 11.9473 4.63261 12 4.5 12H0.5ZM7.5 12C7.36739 12 7.24021 11.9473 7.14645 11.8536C7.05268 11.7598 7 11.6326 7 11.5V7.5C7 7.36739 7.05268 7.24021 7.14645 7.14645C7.24021 7.05268 7.36739 7 7.5 7H11.5C11.6326 7 11.7598 7.05268 11.8536 7.146447C11.9473 7.24021 12 7.36739 12 7.5V11.5C12 11.6326 11.9473 11.7598 11.8536 11.8536C11.7598 11.9473 11.6326 12 11.5 12H7.5Z" fill="currentColor"/></svg></span><span class="nav-label">Dashboard</span><i class="fa-solid fa-chevron-right nav-chevron"></i></a></li>
                        <li><a href="orders.php"><span class="nav-icon"><svg width="19" height="22" viewBox="0 0 19 22" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M14.8882 1H3.31469C2.03632 1 1 2.03632 1 3.31469V18.3602C1 19.6386 2.03632 20.6749 3.31469 20.6749H14.8882C16.1665 20.6749 17.2029 19.6386 17.2029 18.3602V3.31469C17.2029 2.03632 16.1665 1 14.8882 1Z" stroke="currentColor" stroke-width="2"/><path d="M5.62939 6.78662H12.5735M5.62939 11.416H12.5735M5.62939 16.0454H10.2588" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span><span class="nav-label">Orders</span><i class="fa-solid fa-chevron-right nav-chevron"></i></a></li>
                        <li><a href="data-analytics.php"><span class="nav-icon2"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M15.8601 4.39V19.39C15.8601 21.06 17.0001 22 18.2501 22C19.3901 22 20.6401 21.21 20.6401 19.39V4.5C20.6401 2.96 19.5001 2 18.2501 2C17.0001 2 15.8601 3.06 15.8601 4.39ZM9.61011 12V19.39C9.61011 21.07 10.7701 22 12.0001 22C13.1401 22 14.3901 21.21 14.3901 19.39V12.11C14.3901 10.57 13.2501 9.61 12.0001 9.61C10.7501 9.61 9.61011 10.67 9.61011 12ZM5.75011 17.23C7.07011 17.23 8.14011 18.3 8.14011 19.61C8.14011 20.2439 7.88831 20.8518 7.44009 21.3C6.99188 21.7482 6.38398 22 5.75011 22C5.11624 22 4.50833 21.7482 4.06012 21.3C3.61191 20.8518 3.36011 20.2439 3.36011 19.61C3.36011 18.3 4.43011 17.23 5.75011 17.23Z" fill="white"/></svg></span><span class="nav-label">Data Analytics</span><i class="fa-solid fa-chevron-right nav-chevron"></i></a></li>
                        <li><a href="forecasting.php"><span class="nav-icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M21.3751 6C21.0698 6.00008 20.7692 6.0747 20.4993 6.21737C20.2294 6.36005 19.9984 6.56647 19.8264 6.81869C19.6545 7.07092 19.5467 7.36132 19.5124 7.66468C19.4782 7.96803 19.5185 8.27516 19.6299 8.55938L15.6845 12.5048C15.2447 12.3317 14.7556 12.3317 14.3157 12.5048L11.4953 9.68438C11.6069 9.40009 11.6475 9.09283 11.6134 8.78931C11.5792 8.48579 11.4715 8.1952 11.2995 7.94281C11.1275 7.69042 10.8964 7.48387 10.6264 7.34113C10.3563 7.19839 10.0555 7.12377 9.75011 7.12377C9.44467 7.12377 9.14386 7.19839 8.87384 7.34113C8.60381 7.48387 8.37274 7.69042 8.20073 7.94281C8.02872 8.1952 7.92096 8.48579 7.88684 8.78931C7.85272 9.09283 7.89327 9.40009 8.00495 9.68438L3.30948 14.3798C2.90848 14.2225 2.46554 14.2081 2.05514 14.339C1.64474 14.4698 1.29192 14.738 1.056 15.0984C0.82007 15.4588 0.715432 15.8895 0.759675 16.3179C0.803918 16.7464 0.994344 17.1466 1.29893 17.4512C1.60352 17.7558 2.0037 17.9462 2.43218 17.9904C2.86065 18.0347 3.2913 17.93 3.6517 17.6941C4.0121 17.4582 4.28028 17.1054 4.41114 16.695C4.542 16.2846 4.52757 15.8416 4.37026 15.4406L9.06573 10.7452C9.50556 10.9183 9.99466 10.9183 10.4345 10.7452L13.2549 13.5656C13.1433 13.8499 13.1027 14.1572 13.1368 14.4607C13.171 14.7642 13.2787 15.0548 13.4507 15.3072C13.6227 15.5596 13.8538 15.7661 14.1238 15.9089C14.3939 16.0516 14.6947 16.1262 15.0001 16.1262C15.3055 16.1262 15.6063 16.0516 15.8764 15.9089C16.1464 15.7661 16.3775 15.5596 16.5495 15.3072C16.7215 15.0548 16.8293 14.7642 16.8634 14.4607C16.8975 14.1572 16.8569 13.8499 16.7453 13.5656L20.6907 9.62016C20.9475 9.72102 21.2233 9.76399 21.4986 9.74601C21.7738 9.72803 22.0417 9.64953 22.2832 9.51613C22.5246 9.38272 22.7336 9.19768 22.8953 8.97421C23.0571 8.75073 23.1675 8.49433 23.2187 8.22329C23.2699 7.95225 23.2607 7.67324 23.1918 7.40616C23.1228 7.13907 22.9957 6.8905 22.8197 6.67816C22.6436 6.46582 22.4228 6.29495 22.1731 6.17773C21.9234 6.0605 21.651 5.99982 21.3751 6Z" fill="white"/></svg></span><span class="nav-label">Forecasting</span><i class="fa-solid fa-chevron-right nav-chevron"></i></a></li>
                        <li><a href="inventory.php"><span class="nav-icon"><svg width="30" height="30" viewBox="0 0 30 30" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M25.9126 20.6502V9.48774C25.9136 9.34411 25.8793 9.20245 25.8126 9.07524C25.7205 8.87674 25.5611 8.71729 25.3626 8.62524L15.3626 4.15024C15.2409 4.09499 15.1088 4.06641 14.9751 4.06641C14.8414 4.06641 14.7093 4.09499 14.5876 4.15024L4.5876 8.62524C4.42677 8.70617 4.29077 8.82903 4.19397 8.98084C4.09716 9.13265 4.04314 9.30778 4.0376 9.48774V20.5127C4.04694 20.6918 4.10252 20.8653 4.19891 21.0165C4.2953 21.1676 4.42922 21.2912 4.5876 21.3752L14.5876 25.8502C14.7086 25.908 14.841 25.9379 14.9751 25.9379C15.1092 25.9379 15.2416 25.908 15.3626 25.8502L25.3626 21.3752C25.507 21.3091 25.6327 21.2083 25.7287 21.0818C25.8247 20.9553 25.8878 20.8071 25.9126 20.6502ZM5.9126 10.9252L14.0376 14.5752V23.5502L5.9126 19.9127V10.9252ZM15.9126 14.5752L24.0376 10.9252V19.9127L15.9126 23.5502V14.5752ZM15.0001 6.02524L22.7126 9.48774L15.0001 12.9377L7.2876 9.48774L15.0001 6.02524Z" fill="white"/></svg></span><span class="nav-label">Inventory</span><i class="fa-solid fa-chevron-right nav-chevron"></i></a></li>
                        <li><a href="mapping.php"><span class="nav-icon"><svg width="27" height="27" viewBox="0 0 27 27" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M23.1154 4.13114C22.9044 3.92024 22.6183 3.80176 22.32 3.80176C22.0217 3.80176 21.7356 3.92024 21.5246 4.13114L15.4901 10.1656H2.25V12.4156H2.259C2.50425 18.4119 7.443 23.1988 13.5 23.1988C19.557 23.1988 24.4958 18.4119 24.741 12.4156H24.75V10.1656H18.6716L23.1154 5.72189C23.3263 5.51092 23.4448 5.22483 23.4448 4.92652C23.4448 4.62821 23.3263 4.34211 23.1154 4.13114ZM15.9491 12.4156H22.4888C22.3733 14.7218 21.3759 16.8954 19.7029 18.4869C18.0298 20.0783 15.8091 20.9658 13.5 20.9658C11.1909 20.9658 8.97019 20.0783 7.29713 18.4869C5.62406 16.8954 4.62667 14.7218 4.51125 12.4156H15.9491Z" fill="white"/></svg></span><span class="nav-label">Ingredients Mapping</span><i class="fa-solid fa-chevron-right nav-chevron"></i></a></li>
                    </ul>
                    <div class="sidebar-divider"></div>
                    <ul>
                        <li><a href="menu-management.php"><span class="nav-icon"><i class="fa-solid fa-bars"></i></span><span class="nav-label">Menu Management</span><i class="fa-solid fa-chevron-right nav-chevron"></i></a></li>
                        <li><a href="customers.php"><span class="nav-icon"><i class="fa-solid fa-users"></i></span><span class="nav-label">Customers</span><i class="fa-solid fa-chevron-right nav-chevron"></i></a></li>

                        <li><a href="loyalty-card.php"><span class="nav-icon"><svg width="22" height="18" viewBox="0 0 22 18" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M0.75 8.75C0.75 4.979 0.75 3.093 1.922 1.922C3.094 0.751 4.979 0.75 8.75 0.75H12.75C16.521 0.75 18.407 0.75 19.578 1.922C20.749 3.094 20.75 4.979 20.75 8.75C20.75 12.521 20.75 14.407 19.578 15.578C18.406 16.749 16.521 16.75 12.75 16.75H8.75C4.979 16.75 3.093 16.75 1.922 15.578C0.751 14.406 0.75 12.521 0.75 8.75Z" stroke="currentColor" stroke-width="1.5"/><path d="M8.75 12.75H4.75M12.75 12.75H11.25M0.75 6.75H20.75" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg></span><span class="nav-label">Loyalty Card</span><i class="fa-solid fa-chevron-right nav-chevron"></i></a></li>
                        <li><a href="feedback.php" class="active"><span class="nav-icon"><i class="fa-solid fa-star"></i></span><span class="nav-label">Feedback &amp; Reviews</span><i class="fa-solid fa-chevron-right nav-chevron"></i></a></li>
                    </ul>
                    <div class="sidebar-divider"></div>
                    <ul>
                        <li><a href="adminsettings.php"><span class="nav-icon"><i class="fa-solid fa-gear"></i></span><span class="nav-label">Settings</span><i class="fa-solid fa-chevron-right nav-chevron"></i></a></li>
                        <li><a href="logout.php" class="logout-link"><span class="nav-icon"><i class="fa-solid fa-right-from-bracket"></i></span><span class="nav-label">Log Out</span></a></li>
                    </ul>
                </div>
            </nav>
        </aside>
        <main class="feedback-main main-panel">
            <header class="feedback-header top-header">
                <div>
                    <h1>Feedback & Reviews</h1>
                    <p>See what customers think about their completed orders.</p>
                </div>
            </header>

            <section class="feedback-summary">
                <div class="rating-total">
                    <strong><?= number_format($averageRating, 1) ?></strong>
                    <span class="stars" aria-label="<?= number_format($averageRating, 1) ?> out of 5 stars">★★★★★</span>
                    <small><?= $reviewCount ?> <?= $reviewCount === 1 ? 'review' : 'reviews' ?></small>
                </div>
                <div class="rating-breakdown">
                    <?php for ($rating = 5; $rating >= 1; $rating--): ?>
                        <div class="rating-line"><span><?= $rating ?> <i class="fa-solid fa-star"></i></span><div><span style="width: <?= $reviewCount ? round(($ratingCounts[$rating] / $reviewCount) * 100) : 0 ?>%"></span></div><small><?= $ratingCounts[$rating] ?></small></div>
                    <?php endfor; ?>
                </div>
            </section>

            <section class="feedback-list-section problem-reports-section">
                <div class="section-heading"><h2>Problem Reports</h2><span><?= count($reports) ?> total</span></div>
                <?php if (!$reports): ?>
                    <div class="empty-feedback"><i class="fa-regular fa-flag"></i><p>No problem reports yet.</p><small>Reports submitted from orders will appear here.</small></div>
                <?php else: ?>
                    <div class="review-list">
                        <?php foreach ($reports as $report): ?>
                            <?php
                                $reportPhotos = [];
                                $storedPhotos = $report['photo_paths'] ?? '';
                                if (is_string($storedPhotos) && $storedPhotos !== '') {
                                    $decodedPhotos = json_decode($storedPhotos, true);
                                    if (is_array($decodedPhotos) && $decodedPhotos !== []) {
                                        $reportPhotos = $decodedPhotos;
                                    } else {
                                        $reportPhotos = [$storedPhotos];
                                    }
                                }
                            ?>
                            <article class="review-card problem-report-card">
                                <div class="review-card-head"><div><strong><?= htmlspecialchars($report['customer_name']) ?></strong><small><?= htmlspecialchars($report['email']) ?></small></div><span class="report-issue-label"><?= htmlspecialchars($report['issue']) ?></span></div>
                                <p><?= nl2br(htmlspecialchars($report['details'])) ?></p>
                                <?php if ($reportPhotos): ?>
                                    <div class="report-photo-list">
                                        <?php foreach ($reportPhotos as $photo): ?>
                                            <?php $photoUrl = boycold_feedback_photo_url($photo); if ($photoUrl === '') continue; ?>
                                            <button type="button" class="report-photo-button" data-photo="<?= htmlspecialchars($photoUrl) ?>">
                                                <img src="<?= htmlspecialchars($photoUrl) ?>" alt="Customer report attachment">
                                            </button>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                                <footer>Order #<?= (int)$report['order_id'] ?> · <?= htmlspecialchars(date('M d, Y g:i A', strtotime($report['created_at']))) ?></footer>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <section class="feedback-list-section">
                <div class="section-heading"><h2>Customer Reviews</h2><span><?= $reviewCount ?> total</span></div>
                <?php if (!$reviews): ?>
                    <div class="empty-feedback"><i class="fa-regular fa-comment-dots"></i><p>No reviews yet.</p><small>Reviews submitted after completed orders will appear here.</small></div>
                <?php else: ?>
                    <div class="review-list">
                        <?php foreach ($reviews as $review): ?>
                            <article class="review-card">
                                <div class="review-card-head"><div><strong><?= htmlspecialchars($review['customer_name']) ?></strong><small><?= htmlspecialchars($review['email']) ?></small></div><span class="stars small" aria-label="<?= (int)$review['rating'] ?> out of 5 stars"><?= str_repeat('★', (int)$review['rating']) . str_repeat('☆', 5 - (int)$review['rating']) ?></span></div>
                                <p><?= $review['review'] !== '' ? nl2br(htmlspecialchars($review['review'])) : '<em>No written comment.</em>' ?></p>
                                <footer>Order #<?= (int)$review['order_id'] ?> · <?= htmlspecialchars(date('M d, Y g:i A', strtotime($review['created_at']))) ?></footer>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        </main>
    </div>
    <div class="report-photo-modal" id="reportPhotoModal" hidden>
        <button type="button" class="report-photo-modal-close" id="reportPhotoModalClose" aria-label="Close attachment preview">&times;</button>
        <img id="reportPhotoModalImage" alt="Customer report attachment preview">
    </div>
    <script src="admin-js/admin-responsive.js"></script>
    <script src="admin-js/logout-modal.js"></script>
    <script>
        const reportPhotoModal = document.getElementById('reportPhotoModal');
        const reportPhotoModalImage = document.getElementById('reportPhotoModalImage');
        const closeReportPhotoModal = () => { reportPhotoModal.hidden = true; reportPhotoModalImage.removeAttribute('src'); };
        document.querySelectorAll('.report-photo-button').forEach(button => {
            button.addEventListener('click', () => {
                reportPhotoModalImage.src = button.dataset.photo;
                reportPhotoModal.hidden = false;
            });
        });
        document.getElementById('reportPhotoModalClose').addEventListener('click', closeReportPhotoModal);
        reportPhotoModal.addEventListener('click', event => { if (event.target === reportPhotoModal) closeReportPhotoModal(); });
    </script>
</body>
</html>
