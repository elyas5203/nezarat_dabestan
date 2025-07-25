CREATE TABLE IF NOT EXISTS `checklist_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `template_name` varchar(255) NOT NULL,
  `description` text,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `checklist_templates_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `checklist_template_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `template_id` int(11) NOT NULL,
  `item_text` varchar(255) NOT NULL,
  `item_order` int(11) NOT NULL DEFAULT '0',
  `is_required` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `template_id` (`template_id`),
  CONSTRAINT `checklist_template_items_ibfk_1` FOREIGN KEY (`template_id`) REFERENCES `checklist_templates` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `meeting_checklists` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `meeting_id` int(11) NOT NULL,
  `meeting_type` varchar(50) NOT NULL,
  `template_item_id` int(11) NOT NULL,
  `is_completed` tinyint(1) NOT NULL DEFAULT '0',
  `completed_by` int(11) DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `notes` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `meeting_item_unique` (`meeting_id`,`meeting_type`,`template_item_id`),
  KEY `template_item_id` (`template_item_id`),
  KEY `completed_by` (`completed_by`),
  CONSTRAINT `meeting_checklists_ibfk_1` FOREIGN KEY (`template_item_id`) REFERENCES `checklist_template_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `meeting_checklists_ibfk_2` FOREIGN KEY (`completed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `service_meetings` ADD `checklist_template_id` INT NULL DEFAULT NULL AFTER `created_at`;
ALTER TABLE `parent_meetings` ADD `checklist_template_id` INT NULL DEFAULT NULL AFTER `created_by`;
