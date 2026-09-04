<?php
require_once __DIR__ . '/admin_guard.php';

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
                <span class="brand-mark" aria-hidden="true"><img src="/public/assets/icons/ChatGPT Image Jun 23, 2026, 09_22_57 PM 1.png" alt=""></span>
                <span class="brand-text"><span class="brand-name">BoyCold Cafe</span><span class="brand-sub">Administration Panel</span></span>
            </div>
            <nav class="sidebar-nav" aria-label="Admin navigation">
                <div class="nav-top">
                    <ul>
                        <li><a href="dashboard.php"><span class="nav-icon"><i class="fa-solid fa-table-cells-large"></i></span><span class="nav-label">Dashboard</span><i class="fa-solid fa-chevron-right nav-chevron"></i></a></li>
                        <li><a href="orders.php"><span class="nav-icon"><i class="fa-solid fa-receipt"></i></span><span class="nav-label">Orders</span><i class="fa-solid fa-chevron-right nav-chevron"></i></a></li>
                        <li><a href="data-analytics.php"><span class="nav-icon"><i class="fa-solid fa-chart-column"></i></span><span class="nav-label">Data Analytics</span><i class="fa-solid fa-chevron-right nav-chevron"></i></a></li>
                        <li><a href="forecasting.php"><span class="nav-icon"><i class="fa-solid fa-chart-line"></i></span><span class="nav-label">Forecasting</span><i class="fa-solid fa-chevron-right nav-chevron"></i></a></li>
                        <li><a href="inventory.php"><span class="nav-icon"><i class="fa-solid fa-box"></i></span><span class="nav-label">Inventory</span><i class="fa-solid fa-chevron-right nav-chevron"></i></a></li>
                        <li><a href="mapping.php"><span class="nav-icon"><i class="fa-solid fa-flask"></i></span><span class="nav-label">Ingredients Mapping</span><i class="fa-solid fa-chevron-right nav-chevron"></i></a></li>
                    </ul>
                    <div class="sidebar-divider"></div>
                    <ul>
                        <li><a href="menu-management.php"><span class="nav-icon"><i class="fa-solid fa-bars"></i></span><span class="nav-label">Menu Management</span><i class="fa-solid fa-chevron-right nav-chevron"></i></a></li>
                        <li><a href="customers.php"><span class="nav-icon"><i class="fa-solid fa-users"></i></span><span class="nav-label">Customers</span><i class="fa-solid fa-chevron-right nav-chevron"></i></a></li>
                        <li><a href="loyalty-card.php"><span class="nav-icon"><i class="fa-solid fa-id-card"></i></span><span class="nav-label">Loyalty Card</span><i class="fa-solid fa-chevron-right nav-chevron"></i></a></li>
                        <li><a href="feedback.php" class="active"><span class="nav-icon"><i class="fa-solid fa-star"></i></span><span class="nav-label">Feedback &amp; Reviews</span><i class="fa-solid fa-chevron-right nav-chevron"></i></a></li>
                    </ul>
                    <div class="sidebar-divider"></div>
                    <ul>
                        <li><a href="adminsettings.php"><span class="nav-icon"><i class="fa-solid fa-gear"></i></span><span class="nav-label">Settings</span><i class="fa-solid fa-chevron-right nav-chevron"></i></a></li>
                        <li><a href="adminlogin.php" class="logout-link"><span class="nav-icon"><i class="fa-solid fa-right-from-bracket"></i></span><span class="nav-label">Log Out</span></a></li>
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
                            <?php $reportPhotos = json_decode((string)$report['photo_paths'], true) ?: []; ?>
                            <article class="review-card problem-report-card">
                                <div class="review-card-head"><div><strong><?= htmlspecialchars($report['customer_name']) ?></strong><small><?= htmlspecialchars($report['email']) ?></small></div><span class="report-issue-label"><?= htmlspecialchars($report['issue']) ?></span></div>
                                <p><?= nl2br(htmlspecialchars($report['details'])) ?></p>
                                <?php if ($reportPhotos): ?><div class="report-photo-list"><?php foreach ($reportPhotos as $photo): ?><button type="button" class="report-photo-button" data-photo="../<?= htmlspecialchars(ltrim($photo, '/')) ?>"><img src="../<?= htmlspecialchars(ltrim($photo, '/')) ?>" alt="Customer report attachment"></button><?php endforeach; ?></div><?php endif; ?>
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
