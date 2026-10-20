-- Non-destructive MySQL/MariaDB setup for hosts without Artisan/SSH.
-- Select the application's existing database in phpMyAdmin before importing.
-- No accounts, credentials, sessions, VAPID keys, or sample records are inserted.
-- Later artisan migrate runs recognize these matching tables and retain them.

CREATE TABLE IF NOT EXISTS `push_subscriptions` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `endpoint_hash` CHAR(64) NOT NULL,
    `endpoint` TEXT NOT NULL,
    `public_key` TEXT NOT NULL,
    `auth_token` TEXT NOT NULL,
    `vapid_key_hash` CHAR(64) NOT NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `push_subscriptions_endpoint_hash_unique` (`endpoint_hash`),
    KEY `push_subscriptions_user_id_index` (`user_id`),
    CONSTRAINT `push_subscriptions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `account_notifications` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `title` VARCHAR(120) NOT NULL,
    `body` VARCHAR(500) NOT NULL,
    `url` VARCHAR(255) NOT NULL,
    `read_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `account_notifications_user_id_read_at_index` (`user_id`, `read_at`),
    CONSTRAINT `account_notifications_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
