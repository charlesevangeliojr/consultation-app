-- ConSync fictional demo data only (MySQL / MariaDB).
-- Apply Laravel migrations BEFORE importing. No database/schema is created here.
-- Demo password: DemoConsult!2026. Public testing credentials, not production accounts.
-- Existing matching records are preserved. IDs are resolved by email/name, not copied.
-- Dates are a snapshot; use DemoConsultationSeeder for relative future dates on a fresh install.
-- See docs/demo-data.md for credentials and import instructions.

SET @DEMO_PREVIOUS_SQL_MODE = @@SESSION.sql_mode;
SET SESSION sql_mode = 'NO_BACKSLASH_ESCAPES';
START TRANSACTION;

INSERT INTO `roles` (`name`, `is_super_admin`, `created_at`, `updated_at`)
SELECT 'Super Admin', 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP WHERE NOT EXISTS (SELECT 1 FROM `roles` WHERE `is_super_admin` = 1);

INSERT INTO `roles` (`name`, `is_super_admin`, `created_at`, `updated_at`)
SELECT 'Teacher', 0, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP WHERE NOT EXISTS (SELECT 1 FROM `roles` WHERE `name` = 'Teacher');

INSERT INTO `roles` (`name`, `is_super_admin`, `created_at`, `updated_at`)
SELECT 'Student', 0, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP WHERE NOT EXISTS (SELECT 1 FROM `roles` WHERE `name` = 'Student');

INSERT INTO `grade_levels` (`name`, `created_at`, `updated_at`)
SELECT 'Grade 10', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP WHERE NOT EXISTS (SELECT 1 FROM `grade_levels` WHERE `name` = 'Grade 10');

INSERT INTO `grade_levels` (`name`, `created_at`, `updated_at`)
SELECT 'Grade 11', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP WHERE NOT EXISTS (SELECT 1 FROM `grade_levels` WHERE `name` = 'Grade 11');

INSERT INTO `grade_levels` (`name`, `created_at`, `updated_at`)
SELECT 'Grade 12', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP WHERE NOT EXISTS (SELECT 1 FROM `grade_levels` WHERE `name` = 'Grade 12');

INSERT INTO `sections` (`grade_level_id`, `name`, `created_at`, `updated_at`)
SELECT (SELECT `id` FROM `grade_levels` WHERE `name` = 'Grade 10' LIMIT 1), 'Newton', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP WHERE NOT EXISTS (SELECT 1 FROM `sections` WHERE `grade_level_id` = (SELECT `id` FROM `grade_levels` WHERE `name` = 'Grade 10' LIMIT 1) AND `name` = 'Newton');

INSERT INTO `sections` (`grade_level_id`, `name`, `created_at`, `updated_at`)
SELECT (SELECT `id` FROM `grade_levels` WHERE `name` = 'Grade 10' LIMIT 1), 'Galileo', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP WHERE NOT EXISTS (SELECT 1 FROM `sections` WHERE `grade_level_id` = (SELECT `id` FROM `grade_levels` WHERE `name` = 'Grade 10' LIMIT 1) AND `name` = 'Galileo');

INSERT INTO `sections` (`grade_level_id`, `name`, `created_at`, `updated_at`)
SELECT (SELECT `id` FROM `grade_levels` WHERE `name` = 'Grade 11' LIMIT 1), 'Einstein', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP WHERE NOT EXISTS (SELECT 1 FROM `sections` WHERE `grade_level_id` = (SELECT `id` FROM `grade_levels` WHERE `name` = 'Grade 11' LIMIT 1) AND `name` = 'Einstein');

INSERT INTO `sections` (`grade_level_id`, `name`, `created_at`, `updated_at`)
SELECT (SELECT `id` FROM `grade_levels` WHERE `name` = 'Grade 11' LIMIT 1), 'Curie', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP WHERE NOT EXISTS (SELECT 1 FROM `sections` WHERE `grade_level_id` = (SELECT `id` FROM `grade_levels` WHERE `name` = 'Grade 11' LIMIT 1) AND `name` = 'Curie');

INSERT INTO `sections` (`grade_level_id`, `name`, `created_at`, `updated_at`)
SELECT (SELECT `id` FROM `grade_levels` WHERE `name` = 'Grade 12' LIMIT 1), 'Tesla', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP WHERE NOT EXISTS (SELECT 1 FROM `sections` WHERE `grade_level_id` = (SELECT `id` FROM `grade_levels` WHERE `name` = 'Grade 12' LIMIT 1) AND `name` = 'Tesla');

INSERT INTO `sections` (`grade_level_id`, `name`, `created_at`, `updated_at`)
SELECT (SELECT `id` FROM `grade_levels` WHERE `name` = 'Grade 12' LIMIT 1), 'Darwin', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP WHERE NOT EXISTS (SELECT 1 FROM `sections` WHERE `grade_level_id` = (SELECT `id` FROM `grade_levels` WHERE `name` = 'Grade 12' LIMIT 1) AND `name` = 'Darwin');

INSERT INTO `subjects` (`name`, `created_at`, `updated_at`)
SELECT 'Mathematics', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP WHERE NOT EXISTS (SELECT 1 FROM `subjects` WHERE `name` = 'Mathematics');

INSERT INTO `subjects` (`name`, `created_at`, `updated_at`)
SELECT 'Science', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP WHERE NOT EXISTS (SELECT 1 FROM `subjects` WHERE `name` = 'Science');

INSERT INTO `subjects` (`name`, `created_at`, `updated_at`)
SELECT 'English', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP WHERE NOT EXISTS (SELECT 1 FROM `subjects` WHERE `name` = 'English');

INSERT INTO `subjects` (`name`, `created_at`, `updated_at`)
SELECT 'Filipino', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP WHERE NOT EXISTS (SELECT 1 FROM `subjects` WHERE `name` = 'Filipino');

INSERT INTO `subjects` (`name`, `created_at`, `updated_at`)
SELECT 'Computer Studies', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP WHERE NOT EXISTS (SELECT 1 FROM `subjects` WHERE `name` = 'Computer Studies');

INSERT INTO `users` (`name`, `email`, `password`, `role_id`, `created_at`, `updated_at`)
SELECT 'Demo School Administrator', 'demo.admin@example.com', '$2y$12$Ko8jUnbOBVhcXBFRF4TDT.sLjBxeB9bfYLOAHpxGC7.QjIeR4A5F.', (SELECT `id` FROM `roles` WHERE `is_super_admin` = 1 LIMIT 1), CURRENT_TIMESTAMP, CURRENT_TIMESTAMP WHERE NOT EXISTS (SELECT 1 FROM `users` WHERE `email` = 'demo.admin@example.com');

INSERT INTO `users` (`name`, `email`, `password`, `role_id`, `created_at`, `updated_at`)
SELECT 'Alex Mendoza', 'demo.student1@example.com', '$2y$12$USpb4gqqe5EKrmSrTBQykO03I2aa6PBlG7o4RKC/GZ58QkRnveSky', (SELECT `id` FROM `roles` WHERE `name` = 'Student' LIMIT 1), CURRENT_TIMESTAMP, CURRENT_TIMESTAMP WHERE NOT EXISTS (SELECT 1 FROM `users` WHERE `email` = 'demo.student1@example.com');

INSERT INTO `users` (`name`, `email`, `password`, `role_id`, `created_at`, `updated_at`)
SELECT 'Bea Garcia', 'demo.student2@example.com', '$2y$12$VOWIVdSXAqdnWNyBA.N86e4QaB9Y1mCoHj2uDDz52MC5SCtn0isse', (SELECT `id` FROM `roles` WHERE `name` = 'Student' LIMIT 1), CURRENT_TIMESTAMP, CURRENT_TIMESTAMP WHERE NOT EXISTS (SELECT 1 FROM `users` WHERE `email` = 'demo.student2@example.com');

INSERT INTO `users` (`name`, `email`, `password`, `role_id`, `created_at`, `updated_at`)
SELECT 'Carlo Ramos', 'demo.student3@example.com', '$2y$12$CbzBCEpDXfpkvMWMupshn.6Bg5mgRGHV7tjk0VRhfVc8O6nUjKX0m', (SELECT `id` FROM `roles` WHERE `name` = 'Student' LIMIT 1), CURRENT_TIMESTAMP, CURRENT_TIMESTAMP WHERE NOT EXISTS (SELECT 1 FROM `users` WHERE `email` = 'demo.student3@example.com');

INSERT INTO `users` (`name`, `email`, `password`, `role_id`, `created_at`, `updated_at`)
SELECT 'Diana Flores', 'demo.student4@example.com', '$2y$12$ppsEgltz2iZXBSYJV2o3QO.YaIRmKzNQRS2MgYvgJ/Vv84/e7/KWm', (SELECT `id` FROM `roles` WHERE `name` = 'Student' LIMIT 1), CURRENT_TIMESTAMP, CURRENT_TIMESTAMP WHERE NOT EXISTS (SELECT 1 FROM `users` WHERE `email` = 'demo.student4@example.com');

INSERT INTO `users` (`name`, `email`, `password`, `role_id`, `created_at`, `updated_at`)
SELECT 'Maria Santos', 'demo.teacher1@example.com', '$2y$12$5NV6pSY2XDsMED2wzQ3w6ObzgTntdqzYG7rnoHXauQxz64fX/dV8y', (SELECT `id` FROM `roles` WHERE `name` = 'Teacher' LIMIT 1), CURRENT_TIMESTAMP, CURRENT_TIMESTAMP WHERE NOT EXISTS (SELECT 1 FROM `users` WHERE `email` = 'demo.teacher1@example.com');

INSERT INTO `users` (`name`, `email`, `password`, `role_id`, `created_at`, `updated_at`)
SELECT 'Daniel Reyes', 'demo.teacher2@example.com', '$2y$12$96gXsoHym9cAmIqZtqrHoeIUmgOq01d4g8XvRldY4X9.YJBkAYnWy', (SELECT `id` FROM `roles` WHERE `name` = 'Teacher' LIMIT 1), CURRENT_TIMESTAMP, CURRENT_TIMESTAMP WHERE NOT EXISTS (SELECT 1 FROM `users` WHERE `email` = 'demo.teacher2@example.com');

INSERT INTO `users` (`name`, `email`, `password`, `role_id`, `created_at`, `updated_at`)
SELECT 'Angela Cruz', 'demo.teacher3@example.com', '$2y$12$4WIaH2atEV39BVxV2s5feeGEnSnjNXlwfPEVbfoF2HIphUo.Ze1EC', (SELECT `id` FROM `roles` WHERE `name` = 'Teacher' LIMIT 1), CURRENT_TIMESTAMP, CURRENT_TIMESTAMP WHERE NOT EXISTS (SELECT 1 FROM `users` WHERE `email` = 'demo.teacher3@example.com');

INSERT INTO `teachers` (`user_id`, `consultation_approved`, `slot_minutes`, `auto_approve_bookings`, `show_student_names`, `created_at`, `updated_at`)
SELECT (SELECT `id` FROM `users` WHERE `email` = 'demo.teacher1@example.com' LIMIT 1), 1, 30, 0, 0, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP WHERE NOT EXISTS (SELECT 1 FROM `teachers` WHERE `user_id` = (SELECT `id` FROM `users` WHERE `email` = 'demo.teacher1@example.com' LIMIT 1));

INSERT INTO `teachers` (`user_id`, `consultation_approved`, `slot_minutes`, `auto_approve_bookings`, `show_student_names`, `created_at`, `updated_at`)
SELECT (SELECT `id` FROM `users` WHERE `email` = 'demo.teacher2@example.com' LIMIT 1), 1, 20, 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP WHERE NOT EXISTS (SELECT 1 FROM `teachers` WHERE `user_id` = (SELECT `id` FROM `users` WHERE `email` = 'demo.teacher2@example.com' LIMIT 1));

INSERT INTO `teachers` (`user_id`, `consultation_approved`, `slot_minutes`, `auto_approve_bookings`, `show_student_names`, `created_at`, `updated_at`)
SELECT (SELECT `id` FROM `users` WHERE `email` = 'demo.teacher3@example.com' LIMIT 1), 0, 45, 0, 0, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP WHERE NOT EXISTS (SELECT 1 FROM `teachers` WHERE `user_id` = (SELECT `id` FROM `users` WHERE `email` = 'demo.teacher3@example.com' LIMIT 1));

INSERT INTO `subject_teacher` (`teacher_id`, `subject_id`) SELECT (SELECT `id` FROM `teachers` WHERE `user_id` = (SELECT `id` FROM `users` WHERE `email` = 'demo.teacher1@example.com' LIMIT 1) LIMIT 1), (SELECT `id` FROM `subjects` WHERE `name` = 'Mathematics' LIMIT 1) WHERE NOT EXISTS (SELECT 1 FROM `subject_teacher` WHERE `teacher_id` = (SELECT `id` FROM `teachers` WHERE `user_id` = (SELECT `id` FROM `users` WHERE `email` = 'demo.teacher1@example.com' LIMIT 1) LIMIT 1) AND `subject_id` = (SELECT `id` FROM `subjects` WHERE `name` = 'Mathematics' LIMIT 1));

INSERT INTO `subject_teacher` (`teacher_id`, `subject_id`) SELECT (SELECT `id` FROM `teachers` WHERE `user_id` = (SELECT `id` FROM `users` WHERE `email` = 'demo.teacher1@example.com' LIMIT 1) LIMIT 1), (SELECT `id` FROM `subjects` WHERE `name` = 'Science' LIMIT 1) WHERE NOT EXISTS (SELECT 1 FROM `subject_teacher` WHERE `teacher_id` = (SELECT `id` FROM `teachers` WHERE `user_id` = (SELECT `id` FROM `users` WHERE `email` = 'demo.teacher1@example.com' LIMIT 1) LIMIT 1) AND `subject_id` = (SELECT `id` FROM `subjects` WHERE `name` = 'Science' LIMIT 1));

INSERT INTO `subject_teacher` (`teacher_id`, `subject_id`) SELECT (SELECT `id` FROM `teachers` WHERE `user_id` = (SELECT `id` FROM `users` WHERE `email` = 'demo.teacher2@example.com' LIMIT 1) LIMIT 1), (SELECT `id` FROM `subjects` WHERE `name` = 'English' LIMIT 1) WHERE NOT EXISTS (SELECT 1 FROM `subject_teacher` WHERE `teacher_id` = (SELECT `id` FROM `teachers` WHERE `user_id` = (SELECT `id` FROM `users` WHERE `email` = 'demo.teacher2@example.com' LIMIT 1) LIMIT 1) AND `subject_id` = (SELECT `id` FROM `subjects` WHERE `name` = 'English' LIMIT 1));

INSERT INTO `subject_teacher` (`teacher_id`, `subject_id`) SELECT (SELECT `id` FROM `teachers` WHERE `user_id` = (SELECT `id` FROM `users` WHERE `email` = 'demo.teacher2@example.com' LIMIT 1) LIMIT 1), (SELECT `id` FROM `subjects` WHERE `name` = 'Filipino' LIMIT 1) WHERE NOT EXISTS (SELECT 1 FROM `subject_teacher` WHERE `teacher_id` = (SELECT `id` FROM `teachers` WHERE `user_id` = (SELECT `id` FROM `users` WHERE `email` = 'demo.teacher2@example.com' LIMIT 1) LIMIT 1) AND `subject_id` = (SELECT `id` FROM `subjects` WHERE `name` = 'Filipino' LIMIT 1));

INSERT INTO `subject_teacher` (`teacher_id`, `subject_id`) SELECT (SELECT `id` FROM `teachers` WHERE `user_id` = (SELECT `id` FROM `users` WHERE `email` = 'demo.teacher3@example.com' LIMIT 1) LIMIT 1), (SELECT `id` FROM `subjects` WHERE `name` = 'Computer Studies' LIMIT 1) WHERE NOT EXISTS (SELECT 1 FROM `subject_teacher` WHERE `teacher_id` = (SELECT `id` FROM `teachers` WHERE `user_id` = (SELECT `id` FROM `users` WHERE `email` = 'demo.teacher3@example.com' LIMIT 1) LIMIT 1) AND `subject_id` = (SELECT `id` FROM `subjects` WHERE `name` = 'Computer Studies' LIMIT 1));

INSERT INTO `students` (`user_id`, `contact_number`, `birthday`, `gender`, `address`, `status`, `lrn`, `grade_level_id`, `section_id`, `created_at`, `updated_at`)
SELECT (SELECT `id` FROM `users` WHERE `email` = 'demo.student1@example.com' LIMIT 1), '+639170000001', '2008-05-15', 'Male', 'Demo address, Quezon City (fictional)', 'active', '990000000001', (SELECT `id` FROM `grade_levels` WHERE `name` = 'Grade 11' LIMIT 1), (SELECT `id` FROM `sections` WHERE `grade_level_id` = (SELECT `id` FROM `grade_levels` WHERE `name` = 'Grade 11' LIMIT 1) AND `name` = 'Einstein' LIMIT 1), CURRENT_TIMESTAMP, CURRENT_TIMESTAMP WHERE NOT EXISTS (SELECT 1 FROM `students` WHERE `user_id` = (SELECT `id` FROM `users` WHERE `email` = 'demo.student1@example.com' LIMIT 1));

INSERT INTO `students` (`user_id`, `contact_number`, `birthday`, `gender`, `address`, `status`, `lrn`, `grade_level_id`, `section_id`, `created_at`, `updated_at`)
SELECT (SELECT `id` FROM `users` WHERE `email` = 'demo.student2@example.com' LIMIT 1), '+639170000002', '2008-05-15', 'Female', 'Demo address, Quezon City (fictional)', 'active', '990000000002', (SELECT `id` FROM `grade_levels` WHERE `name` = 'Grade 11' LIMIT 1), (SELECT `id` FROM `sections` WHERE `grade_level_id` = (SELECT `id` FROM `grade_levels` WHERE `name` = 'Grade 11' LIMIT 1) AND `name` = 'Curie' LIMIT 1), CURRENT_TIMESTAMP, CURRENT_TIMESTAMP WHERE NOT EXISTS (SELECT 1 FROM `students` WHERE `user_id` = (SELECT `id` FROM `users` WHERE `email` = 'demo.student2@example.com' LIMIT 1));

INSERT INTO `students` (`user_id`, `contact_number`, `birthday`, `gender`, `address`, `status`, `lrn`, `grade_level_id`, `section_id`, `created_at`, `updated_at`)
SELECT (SELECT `id` FROM `users` WHERE `email` = 'demo.student3@example.com' LIMIT 1), '+639170000003', '2008-05-15', 'Male', 'Demo address, Quezon City (fictional)', 'active', '990000000003', (SELECT `id` FROM `grade_levels` WHERE `name` = 'Grade 12' LIMIT 1), (SELECT `id` FROM `sections` WHERE `grade_level_id` = (SELECT `id` FROM `grade_levels` WHERE `name` = 'Grade 12' LIMIT 1) AND `name` = 'Tesla' LIMIT 1), CURRENT_TIMESTAMP, CURRENT_TIMESTAMP WHERE NOT EXISTS (SELECT 1 FROM `students` WHERE `user_id` = (SELECT `id` FROM `users` WHERE `email` = 'demo.student3@example.com' LIMIT 1));

INSERT INTO `students` (`user_id`, `contact_number`, `birthday`, `gender`, `address`, `status`, `lrn`, `grade_level_id`, `section_id`, `created_at`, `updated_at`)
SELECT (SELECT `id` FROM `users` WHERE `email` = 'demo.student4@example.com' LIMIT 1), '+639170000004', '2008-05-15', 'Female', 'Demo address, Quezon City (fictional)', 'active', '990000000004', (SELECT `id` FROM `grade_levels` WHERE `name` = 'Grade 10' LIMIT 1), (SELECT `id` FROM `sections` WHERE `grade_level_id` = (SELECT `id` FROM `grade_levels` WHERE `name` = 'Grade 10' LIMIT 1) AND `name` = 'Newton' LIMIT 1), CURRENT_TIMESTAMP, CURRENT_TIMESTAMP WHERE NOT EXISTS (SELECT 1 FROM `students` WHERE `user_id` = (SELECT `id` FROM `users` WHERE `email` = 'demo.student4@example.com' LIMIT 1));

INSERT INTO `consultation_availabilities` (`teacher_id`, `date`, `starts_at`, `ends_at`, `slot_minutes`, `location`, `cancelled_at`, `created_at`, `updated_at`)
SELECT (SELECT `id` FROM `users` WHERE `email` = 'demo.teacher1@example.com' LIMIT 1), '2026-10-15', '09:00:00', '11:00:00', 30, 'Demo Room 201', NULL, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP WHERE NOT EXISTS (SELECT 1 FROM `consultation_availabilities` WHERE `teacher_id` = (SELECT `id` FROM `users` WHERE `email` = 'demo.teacher1@example.com' LIMIT 1) AND `date` = '2026-10-15' AND `starts_at` = '09:00:00' AND `ends_at` = '11:00:00');

INSERT INTO `consultation_availabilities` (`teacher_id`, `date`, `starts_at`, `ends_at`, `slot_minutes`, `location`, `cancelled_at`, `created_at`, `updated_at`)
SELECT (SELECT `id` FROM `users` WHERE `email` = 'demo.teacher1@example.com' LIMIT 1), '2026-10-16', '09:00:00', '11:00:00', 30, 'Demo Room 201', NULL, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP WHERE NOT EXISTS (SELECT 1 FROM `consultation_availabilities` WHERE `teacher_id` = (SELECT `id` FROM `users` WHERE `email` = 'demo.teacher1@example.com' LIMIT 1) AND `date` = '2026-10-16' AND `starts_at` = '09:00:00' AND `ends_at` = '11:00:00');

INSERT INTO `consultation_availabilities` (`teacher_id`, `date`, `starts_at`, `ends_at`, `slot_minutes`, `location`, `cancelled_at`, `created_at`, `updated_at`)
SELECT (SELECT `id` FROM `users` WHERE `email` = 'demo.teacher1@example.com' LIMIT 1), '2026-10-17', '09:00:00', '11:00:00', 30, 'Demo Room 201', NULL, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP WHERE NOT EXISTS (SELECT 1 FROM `consultation_availabilities` WHERE `teacher_id` = (SELECT `id` FROM `users` WHERE `email` = 'demo.teacher1@example.com' LIMIT 1) AND `date` = '2026-10-17' AND `starts_at` = '09:00:00' AND `ends_at` = '11:00:00');

INSERT INTO `consultation_availabilities` (`teacher_id`, `date`, `starts_at`, `ends_at`, `slot_minutes`, `location`, `cancelled_at`, `created_at`, `updated_at`)
SELECT (SELECT `id` FROM `users` WHERE `email` = 'demo.teacher2@example.com' LIMIT 1), '2026-10-15', '13:00:00', '15:00:00', 20, 'Demo Faculty Office', NULL, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP WHERE NOT EXISTS (SELECT 1 FROM `consultation_availabilities` WHERE `teacher_id` = (SELECT `id` FROM `users` WHERE `email` = 'demo.teacher2@example.com' LIMIT 1) AND `date` = '2026-10-15' AND `starts_at` = '13:00:00' AND `ends_at` = '15:00:00');

INSERT INTO `consultation_availabilities` (`teacher_id`, `date`, `starts_at`, `ends_at`, `slot_minutes`, `location`, `cancelled_at`, `created_at`, `updated_at`)
SELECT (SELECT `id` FROM `users` WHERE `email` = 'demo.teacher2@example.com' LIMIT 1), '2026-10-16', '13:00:00', '15:00:00', 20, 'Demo Faculty Office', NULL, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP WHERE NOT EXISTS (SELECT 1 FROM `consultation_availabilities` WHERE `teacher_id` = (SELECT `id` FROM `users` WHERE `email` = 'demo.teacher2@example.com' LIMIT 1) AND `date` = '2026-10-16' AND `starts_at` = '13:00:00' AND `ends_at` = '15:00:00');

INSERT INTO `consultation_availabilities` (`teacher_id`, `date`, `starts_at`, `ends_at`, `slot_minutes`, `location`, `cancelled_at`, `created_at`, `updated_at`)
SELECT (SELECT `id` FROM `users` WHERE `email` = 'demo.teacher2@example.com' LIMIT 1), '2026-10-17', '13:00:00', '15:00:00', 20, 'Demo Faculty Office', NULL, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP WHERE NOT EXISTS (SELECT 1 FROM `consultation_availabilities` WHERE `teacher_id` = (SELECT `id` FROM `users` WHERE `email` = 'demo.teacher2@example.com' LIMIT 1) AND `date` = '2026-10-17' AND `starts_at` = '13:00:00' AND `ends_at` = '15:00:00');

INSERT INTO `consultation_availabilities` (`teacher_id`, `date`, `starts_at`, `ends_at`, `slot_minutes`, `location`, `cancelled_at`, `created_at`, `updated_at`)
SELECT (SELECT `id` FROM `users` WHERE `email` = 'demo.teacher1@example.com' LIMIT 1), '2026-10-15', '15:00:00', '16:00:00', 30, 'Demo Room 201', '2026-10-14 09:34:38', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP WHERE NOT EXISTS (SELECT 1 FROM `consultation_availabilities` WHERE `teacher_id` = (SELECT `id` FROM `users` WHERE `email` = 'demo.teacher1@example.com' LIMIT 1) AND `date` = '2026-10-15' AND `starts_at` = '15:00:00' AND `ends_at` = '16:00:00');

INSERT INTO `consultation_appointments` (`availability_id`, `teacher_id`, `student_id`, `starts_at`, `ends_at`, `topic`, `status`, `created_at`, `updated_at`)
SELECT (SELECT `id` FROM `consultation_availabilities` WHERE `teacher_id` = (SELECT `id` FROM `users` WHERE `email` = 'demo.teacher1@example.com' LIMIT 1) AND `date` = '2026-10-15' AND `starts_at` = '09:00:00' AND `ends_at` = '11:00:00' LIMIT 1), (SELECT `id` FROM `users` WHERE `email` = 'demo.teacher1@example.com' LIMIT 1), (SELECT `id` FROM `users` WHERE `email` = 'demo.student1@example.com' LIMIT 1), '2026-10-15 09:00:00', '2026-10-15 09:30:00', 'Help understanding algebra', 'pending', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP WHERE NOT EXISTS (SELECT 1 FROM `consultation_appointments` WHERE `availability_id` = (SELECT `id` FROM `consultation_availabilities` WHERE `teacher_id` = (SELECT `id` FROM `users` WHERE `email` = 'demo.teacher1@example.com' LIMIT 1) AND `date` = '2026-10-15' AND `starts_at` = '09:00:00' AND `ends_at` = '11:00:00' LIMIT 1) AND `student_id` = (SELECT `id` FROM `users` WHERE `email` = 'demo.student1@example.com' LIMIT 1) AND `starts_at` = '2026-10-15 09:00:00');

INSERT INTO `consultation_appointments` (`availability_id`, `teacher_id`, `student_id`, `starts_at`, `ends_at`, `topic`, `status`, `created_at`, `updated_at`)
SELECT (SELECT `id` FROM `consultation_availabilities` WHERE `teacher_id` = (SELECT `id` FROM `users` WHERE `email` = 'demo.teacher1@example.com' LIMIT 1) AND `date` = '2026-10-15' AND `starts_at` = '09:00:00' AND `ends_at` = '11:00:00' LIMIT 1), (SELECT `id` FROM `users` WHERE `email` = 'demo.teacher1@example.com' LIMIT 1), (SELECT `id` FROM `users` WHERE `email` = 'demo.student2@example.com' LIMIT 1), '2026-10-15 09:30:00', '2026-10-15 10:00:00', 'Science project feedback', 'confirmed', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP WHERE NOT EXISTS (SELECT 1 FROM `consultation_appointments` WHERE `availability_id` = (SELECT `id` FROM `consultation_availabilities` WHERE `teacher_id` = (SELECT `id` FROM `users` WHERE `email` = 'demo.teacher1@example.com' LIMIT 1) AND `date` = '2026-10-15' AND `starts_at` = '09:00:00' AND `ends_at` = '11:00:00' LIMIT 1) AND `student_id` = (SELECT `id` FROM `users` WHERE `email` = 'demo.student2@example.com' LIMIT 1) AND `starts_at` = '2026-10-15 09:30:00');

INSERT INTO `consultation_appointments` (`availability_id`, `teacher_id`, `student_id`, `starts_at`, `ends_at`, `topic`, `status`, `created_at`, `updated_at`)
SELECT (SELECT `id` FROM `consultation_availabilities` WHERE `teacher_id` = (SELECT `id` FROM `users` WHERE `email` = 'demo.teacher2@example.com' LIMIT 1) AND `date` = '2026-10-15' AND `starts_at` = '13:00:00' AND `ends_at` = '15:00:00' LIMIT 1), (SELECT `id` FROM `users` WHERE `email` = 'demo.teacher2@example.com' LIMIT 1), (SELECT `id` FROM `users` WHERE `email` = 'demo.student3@example.com' LIMIT 1), '2026-10-15 13:00:00', '2026-10-15 13:20:00', 'English essay review', 'confirmed', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP WHERE NOT EXISTS (SELECT 1 FROM `consultation_appointments` WHERE `availability_id` = (SELECT `id` FROM `consultation_availabilities` WHERE `teacher_id` = (SELECT `id` FROM `users` WHERE `email` = 'demo.teacher2@example.com' LIMIT 1) AND `date` = '2026-10-15' AND `starts_at` = '13:00:00' AND `ends_at` = '15:00:00' LIMIT 1) AND `student_id` = (SELECT `id` FROM `users` WHERE `email` = 'demo.student3@example.com' LIMIT 1) AND `starts_at` = '2026-10-15 13:00:00');

INSERT INTO `consultation_appointments` (`availability_id`, `teacher_id`, `student_id`, `starts_at`, `ends_at`, `topic`, `status`, `created_at`, `updated_at`)
SELECT (SELECT `id` FROM `consultation_availabilities` WHERE `teacher_id` = (SELECT `id` FROM `users` WHERE `email` = 'demo.teacher2@example.com' LIMIT 1) AND `date` = '2026-10-15' AND `starts_at` = '13:00:00' AND `ends_at` = '15:00:00' LIMIT 1), (SELECT `id` FROM `users` WHERE `email` = 'demo.teacher2@example.com' LIMIT 1), (SELECT `id` FROM `users` WHERE `email` = 'demo.student4@example.com' LIMIT 1), '2026-10-15 13:20:00', '2026-10-15 13:40:00', 'Filipino presentation preparation', 'confirmed', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP WHERE NOT EXISTS (SELECT 1 FROM `consultation_appointments` WHERE `availability_id` = (SELECT `id` FROM `consultation_availabilities` WHERE `teacher_id` = (SELECT `id` FROM `users` WHERE `email` = 'demo.teacher2@example.com' LIMIT 1) AND `date` = '2026-10-15' AND `starts_at` = '13:00:00' AND `ends_at` = '15:00:00' LIMIT 1) AND `student_id` = (SELECT `id` FROM `users` WHERE `email` = 'demo.student4@example.com' LIMIT 1) AND `starts_at` = '2026-10-15 13:20:00');

INSERT INTO `consultation_appointments` (`availability_id`, `teacher_id`, `student_id`, `starts_at`, `ends_at`, `topic`, `status`, `created_at`, `updated_at`)
SELECT (SELECT `id` FROM `consultation_availabilities` WHERE `teacher_id` = (SELECT `id` FROM `users` WHERE `email` = 'demo.teacher1@example.com' LIMIT 1) AND `date` = '2026-10-15' AND `starts_at` = '15:00:00' AND `ends_at` = '16:00:00' LIMIT 1), (SELECT `id` FROM `users` WHERE `email` = 'demo.teacher1@example.com' LIMIT 1), (SELECT `id` FROM `users` WHERE `email` = 'demo.student1@example.com' LIMIT 1), '2026-10-15 15:00:00', '2026-10-15 15:30:00', 'Demo: teacher cancelled this availability', 'rejected', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP WHERE NOT EXISTS (SELECT 1 FROM `consultation_appointments` WHERE `availability_id` = (SELECT `id` FROM `consultation_availabilities` WHERE `teacher_id` = (SELECT `id` FROM `users` WHERE `email` = 'demo.teacher1@example.com' LIMIT 1) AND `date` = '2026-10-15' AND `starts_at` = '15:00:00' AND `ends_at` = '16:00:00' LIMIT 1) AND `student_id` = (SELECT `id` FROM `users` WHERE `email` = 'demo.student1@example.com' LIMIT 1) AND `starts_at` = '2026-10-15 15:00:00');

COMMIT;
SET SESSION sql_mode = @DEMO_PREVIOUS_SQL_MODE;
