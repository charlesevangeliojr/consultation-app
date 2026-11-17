-- Back up the database first. Adds reminder tables only; no existing records are changed.
CREATE TABLE IF NOT EXISTS `student_reminder_settings` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `minutes_before` SMALLINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  UNIQUE KEY `student_reminder_settings_user_id_unique` (`user_id`),
  CONSTRAINT `student_reminder_settings_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `consultation_reminders` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `appointment_id` BIGINT UNSIGNED NOT NULL,
  `sent_at` TIMESTAMP NOT NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  UNIQUE KEY `consultation_reminders_appointment_id_unique` (`appointment_id`),
  CONSTRAINT `consultation_reminders_appointment_id_foreign` FOREIGN KEY (`appointment_id`) REFERENCES `consultation_appointments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
