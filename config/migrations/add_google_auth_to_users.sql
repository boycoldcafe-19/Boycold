ALTER TABLE `users`
    ADD COLUMN `google_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `password`,
    ADD COLUMN `auth_provider` enum('local','google') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'local' AFTER `google_id`,
    ADD UNIQUE KEY `uq_users_google_id` (`google_id`);
