ALTER TABLE `checklist_template_items`
ADD `reminder_frequency_hours` INT NULL DEFAULT NULL COMMENT 'Reminder frequency in hours. NULL or 0 means no reminders.' AFTER `is_required`;

ALTER TABLE `meeting_checklists`
ADD `last_reminder_sent_at` DATETIME NULL DEFAULT NULL AFTER `notes`;
