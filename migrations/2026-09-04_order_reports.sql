-- Customer problem reports submitted from an order.
CREATE TABLE IF NOT EXISTS order_reports (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    order_id INT NOT NULL,
    user_id INT NOT NULL,
    issue VARCHAR(120) NOT NULL,
    details VARCHAR(500) NOT NULL DEFAULT '',
    photo_paths TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_order_reports_order (order_id),
    KEY idx_order_reports_user (user_id),
    KEY idx_order_reports_created_at (created_at),
    CONSTRAINT fk_order_report_order FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE,
    CONSTRAINT fk_order_report_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;