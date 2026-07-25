
/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `approvals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `approvals` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `inspection_id` bigint(20) unsigned NOT NULL,
  `reviewer_id` bigint(20) unsigned NOT NULL,
  `decision` enum('approved','rejected','correction') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `comment` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_approval_inspection` (`inspection_id`),
  KEY `fk_approval_reviewer` (`reviewer_id`),
  CONSTRAINT `fk_approval_inspection` FOREIGN KEY (`inspection_id`) REFERENCES `inspections` (`id`),
  CONSTRAINT `fk_approval_reviewer` FOREIGN KEY (`reviewer_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `audit_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `action` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `entity_type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `entity_id` bigint(20) unsigned DEFAULT NULL,
  `legacy_id` bigint(20) unsigned DEFAULT NULL,
  `metadata_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip_hash` char(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_audit_entity` (`entity_type`,`entity_id`),
  KEY `idx_audit_legacy_id` (`legacy_id`),
  KEY `idx_audit_date` (`created_at`),
  KEY `fk_audit_user` (`user_id`),
  CONSTRAINT `fk_audit_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8513 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `audit_trail`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `audit_trail` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_ID` varchar(40) NOT NULL,
  `userName` varchar(120) NOT NULL,
  `access` varchar(120) NOT NULL,
  `deptmnt` varchar(150) NOT NULL,
  `action` varchar(150) NOT NULL,
  `param` tinytext NOT NULL,
  `method` varchar(20) NOT NULL,
  `scriptName` tinytext NOT NULL,
  `http_reff` tinytext NOT NULL,
  `date_time` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5889 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `auth_attempts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `auth_attempts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `scope` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `identifier_hash` char(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `success` tinyint(1) NOT NULL DEFAULT '0',
  `failure_reason` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip_hash` char(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_attempt_scope_identifier` (`scope`,`identifier_hash`,`created_at`),
  KEY `idx_attempt_created` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=267 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `auth_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `auth_tokens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `type` enum('invite','password_reset') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `token_hash` char(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `revoked_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `token_hash` (`token_hash`),
  KEY `idx_auth_tokens_user` (`user_id`),
  KEY `idx_auth_tokens_type` (`type`,`expires_at`),
  KEY `fk_auth_token_creator` (`created_by`),
  CONSTRAINT `fk_auth_token_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_auth_token_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `certificate_branding_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `certificate_branding_settings` (
  `id` tinyint(3) unsigned NOT NULL,
  `company_stamp_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `company_stamp_original_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `company_stamp_mime_type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `company_stamp_uploaded_at` datetime DEFAULT NULL,
  `company_stamp_uploaded_by` bigint(20) unsigned DEFAULT NULL,
  `company_stamp_is_active` tinyint(1) NOT NULL DEFAULT '0',
  `watermark_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `registered_address` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `operational_address` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `phone` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(190) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `website` varchar(190) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_branding_stamp_uploader` (`company_stamp_uploaded_by`),
  CONSTRAINT `fk_branding_stamp_uploader` FOREIGN KEY (`company_stamp_uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `certificate_revisions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `certificate_revisions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `certificate_id` bigint(20) unsigned NOT NULL,
  `revision` smallint(5) unsigned NOT NULL,
  `pdf_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `barcode_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pdf_hash` char(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `inspector_name_snapshot` varchar(160) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `inspector_qualifications_snapshot` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `inspector_signature_source` enum('inspection_upload','user_profile','none') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'none',
  `inspector_signature_path_snapshot` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `authenticator_name_snapshot` varchar(160) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `authenticator_qualifications_snapshot` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `authenticator_signature_source` enum('inspection_upload','user_profile','none') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'none',
  `authenticator_signature_path_snapshot` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `company_stamp_path_snapshot` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_certificate_revision` (`certificate_id`,`revision`),
  KEY `fk_cert_revision_user` (`created_by`),
  CONSTRAINT `fk_cert_revision_certificate` FOREIGN KEY (`certificate_id`) REFERENCES `certificates` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cert_revision_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=57 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `certificate_sequences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `certificate_sequences` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `client_id` bigint(20) unsigned NOT NULL,
  `category_id` bigint(20) unsigned NOT NULL,
  `last_number` int(10) unsigned NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_certificate_sequence_scope` (`client_id`,`category_id`),
  KEY `fk_certificate_sequence_category` (`category_id`),
  CONSTRAINT `fk_certificate_sequence_category` FOREIGN KEY (`category_id`) REFERENCES `certification_categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_certificate_sequence_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=47 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `certificates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `certificates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `inspection_id` bigint(20) unsigned NOT NULL,
  `certificate_number` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `verification_token` char(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `revision` smallint(5) unsigned NOT NULL DEFAULT '1',
  `pdf_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `barcode_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pdf_hash` char(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `issued_at` date NOT NULL,
  `expires_at` date DEFAULT NULL,
  `status` enum('valid','expired','revoked','superseded') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'valid',
  `revoked_at` datetime DEFAULT NULL,
  `revocation_reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `legacy_id` bigint(20) unsigned DEFAULT NULL,
  `is_legacy` tinyint(1) NOT NULL DEFAULT '0',
  `legacy_original_reference` varchar(190) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `legacy_mapping_status` enum('complete','partial','raw_only') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'complete',
  `legacy_payload_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `inspection_id` (`inspection_id`),
  UNIQUE KEY `certificate_number` (`certificate_number`),
  UNIQUE KEY `verification_token` (`verification_token`),
  KEY `idx_certificates_status` (`status`),
  KEY `idx_certificates_expires` (`expires_at`),
  KEY `idx_certificates_legacy` (`is_legacy`,`legacy_id`),
  CONSTRAINT `fk_certificate_inspection` FOREIGN KEY (`inspection_id`) REFERENCES `inspections` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1052 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `certification_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `certification_categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `short_code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(180) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `validity_months` smallint(5) unsigned NOT NULL DEFAULT '12',
  `certificate_template` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `template_family` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `layout_key` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `source_sample` varchar(190) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `schema_version` smallint(5) unsigned NOT NULL DEFAULT '1',
  `certificate_prefix` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `identifier_label` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Certificate / Inspection ID',
  `theme_color` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '#151515',
  `requires_review` tinyint(1) NOT NULL DEFAULT '1',
  `status` enum('active','inactive','legacy') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  UNIQUE KEY `uq_certification_categories_short_code` (`short_code`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `clients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `clients` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `registration_code` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `short_code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `contact_person` varchar(160) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(190) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `logo_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('active','review','inactive') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `legacy_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `registration_code` (`registration_code`),
  UNIQUE KEY `uq_clients_short_code` (`short_code`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `code_param_desc`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `code_param_desc` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tab_index` int(2) NOT NULL DEFAULT '0',
  `item_code` char(5) NOT NULL DEFAULT '',
  `item_desc` varchar(150) NOT NULL,
  `item_xdesc` text NOT NULL,
  `other_item` varchar(50) DEFAULT NULL,
  `status` char(2) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `tab_index_2` (`tab_index`,`item_code`),
  KEY `tab_index` (`tab_index`),
  CONSTRAINT `code_param_desc_ibfk_1` FOREIGN KEY (`tab_index`) REFERENCES `code_param_tab` (`tab_index`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1639 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `code_param_tab`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `code_param_tab` (
  `tab_index` int(2) NOT NULL AUTO_INCREMENT,
  `tab_name` varchar(150) NOT NULL DEFAULT '',
  `modeT` char(2) NOT NULL,
  `key_length` int(3) NOT NULL DEFAULT '0',
  `status` char(2) NOT NULL DEFAULT '1',
  PRIMARY KEY (`tab_index`)
) ENGINE=InnoDB AUTO_INCREMENT=63 DEFAULT CHARSET=latin1 COMMENT='General Codes Table Data Model (PDM)';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `department_tab`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `department_tab` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `client_ID` varchar(40) NOT NULL,
  `client_station` varchar(150) NOT NULL,
  `contact_person` varchar(100) NOT NULL,
  `phoneNo` char(15) NOT NULL,
  `e_mail` varchar(150) NOT NULL,
  `address` varchar(150) NOT NULL,
  `lga` char(4) NOT NULL,
  `state` char(4) NOT NULL,
  `signPath` varchar(150) NOT NULL,
  `status` char(2) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `client_ID` (`client_ID`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `email_notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `email_notifications` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `certificate_id` bigint(20) unsigned NOT NULL,
  `revision` smallint(5) unsigned NOT NULL,
  `event_type` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `recipient_email` varchar(190) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `recipient_name` varchar(190) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('pending','sending','sent','failed','cancelled') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `attempts` smallint(5) unsigned NOT NULL DEFAULT '0',
  `max_attempts` smallint(5) unsigned NOT NULL DEFAULT '5',
  `last_error` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `next_attempt_at` datetime DEFAULT NULL,
  `sent_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_email_notification_delivery` (`certificate_id`,`revision`,`event_type`,`recipient_email`),
  KEY `idx_email_notification_queue` (`status`,`next_attempt_at`),
  KEY `idx_email_notification_certificate` (`certificate_id`,`revision`),
  CONSTRAINT `fk_email_notification_certificate` FOREIGN KEY (`certificate_id`) REFERENCES `certificates` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `equipment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `equipment` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `client_id` bigint(20) unsigned NOT NULL,
  `asset_code` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `manufacturer` varchar(160) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `model` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `serial_number` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `safe_working_load` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `location` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `manufacture_date` date DEFAULT NULL,
  `reference_standard` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('active','out_of_service','retired') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `legacy_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_equipment_client_asset` (`client_id`,`asset_code`),
  KEY `idx_equipment_serial` (`serial_number`),
  CONSTRAINT `fk_equipment_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=45 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `folder_grouping`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `folder_grouping` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `gf_name` varchar(200) NOT NULL,
  `gf_ids` text NOT NULL,
  `status` char(2) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `form_fields`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `form_fields` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `template_id` bigint(20) unsigned NOT NULL,
  `field_key` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `label` varchar(180) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `help_text` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `placeholder_text` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `field_type` enum('text','textarea','number','date','select','checkbox','pass_fail','photo','signature') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_required` tinyint(1) NOT NULL DEFAULT '0',
  `appears_on_pdf` tinyint(1) NOT NULL DEFAULT '1',
  `editable_after_approval` tinyint(1) NOT NULL DEFAULT '0',
  `pdf_section` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `repeatable_group` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `options_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `validation_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `sort_order` smallint(5) unsigned NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_form_field_key` (`template_id`,`field_key`),
  CONSTRAINT `fk_form_field_template` FOREIGN KEY (`template_id`) REFERENCES `form_templates` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=996 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `form_repeatable_columns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `form_repeatable_columns` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `section_id` bigint(20) unsigned NOT NULL,
  `column_key` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `label` varchar(180) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `column_type` enum('text','textarea','number','date','select','checkbox','pass_fail') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'text',
  `is_required` tinyint(1) NOT NULL DEFAULT '0',
  `options_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `validation_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `placeholder_text` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `appears_on_pdf` tinyint(1) NOT NULL DEFAULT '1',
  `editable_after_approval` tinyint(1) NOT NULL DEFAULT '0',
  `sort_order` smallint(5) unsigned NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_repeatable_column_key` (`section_id`,`column_key`),
  CONSTRAINT `fk_repeatable_column_section` FOREIGN KEY (`section_id`) REFERENCES `form_repeatable_sections` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=92 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `form_repeatable_sections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `form_repeatable_sections` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `template_id` bigint(20) unsigned NOT NULL,
  `section_key` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `label` varchar(180) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `help_text` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `min_rows` smallint(5) unsigned NOT NULL DEFAULT '0',
  `max_rows` smallint(5) unsigned DEFAULT NULL,
  `pdf_section` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort_order` smallint(5) unsigned NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_repeatable_section_key` (`template_id`,`section_key`),
  CONSTRAINT `fk_repeatable_section_template` FOREIGN KEY (`template_id`) REFERENCES `form_templates` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `form_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `form_templates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `category_id` bigint(20) unsigned NOT NULL,
  `name` varchar(180) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `version` smallint(5) unsigned NOT NULL DEFAULT '1',
  `status` enum('draft','active','archived') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `show_inspector_signature` tinyint(1) NOT NULL DEFAULT '1',
  `show_authenticator_signature` tinyint(1) NOT NULL DEFAULT '1',
  `show_company_stamp` tinyint(1) NOT NULL DEFAULT '0',
  `requires_evidence` tinyint(1) NOT NULL DEFAULT '0',
  `minimum_evidence_files` smallint(5) unsigned NOT NULL DEFAULT '0',
  `allowed_evidence_types` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'image/jpeg,image/png,image/webp,application/pdf',
  `requires_inspection_photo` tinyint(1) NOT NULL DEFAULT '0',
  `requires_inspector_signature` tinyint(1) NOT NULL DEFAULT '0',
  `requires_authenticator_signature` tinyint(1) NOT NULL DEFAULT '0',
  `requires_company_stamp` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_form_template_version` (`category_id`,`version`),
  CONSTRAINT `fk_form_template_category` FOREIGN KEY (`category_id`) REFERENCES `certification_categories` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=40 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `inspection_attachments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inspection_attachments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `inspection_id` bigint(20) unsigned NOT NULL,
  `uploaded_by` bigint(20) unsigned NOT NULL,
  `attachment_type` enum('evidence','signature') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'evidence',
  `file_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `mime_type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_size` bigint(20) unsigned NOT NULL,
  `file_hash` char(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_attachment_user` (`uploaded_by`),
  KEY `idx_inspection_attachments_type` (`inspection_id`,`attachment_type`,`created_at`),
  CONSTRAINT `fk_attachment_inspection` FOREIGN KEY (`inspection_id`) REFERENCES `inspections` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_attachment_user` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `inspection_comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inspection_comments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `inspection_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `comment_type` enum('comment','correction','review','status') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'comment',
  `comment_text` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_inspection_comments_inspection` (`inspection_id`,`created_at`),
  KEY `fk_comment_user` (`user_id`),
  CONSTRAINT `fk_comment_inspection` FOREIGN KEY (`inspection_id`) REFERENCES `inspections` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_comment_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `inspection_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inspection_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `inspection_id` bigint(20) unsigned NOT NULL,
  `section_key` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `row_index` smallint(5) unsigned NOT NULL DEFAULT '0',
  `column_key` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `value_text` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_inspection_item_cell` (`inspection_id`,`section_key`,`row_index`,`column_key`),
  KEY `idx_inspection_items_lookup` (`inspection_id`,`section_key`,`row_index`),
  CONSTRAINT `fk_inspection_items_inspection` FOREIGN KEY (`inspection_id`) REFERENCES `inspections` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=227 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `inspection_values`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inspection_values` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `inspection_id` bigint(20) unsigned NOT NULL,
  `field_id` bigint(20) unsigned NOT NULL,
  `value_text` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_inspection_field` (`inspection_id`,`field_id`),
  KEY `fk_value_field` (`field_id`),
  CONSTRAINT `fk_value_field` FOREIGN KEY (`field_id`) REFERENCES `form_fields` (`id`),
  CONSTRAINT `fk_value_inspection` FOREIGN KEY (`inspection_id`) REFERENCES `inspections` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2054 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `inspections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inspections` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `reference` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `sequence_number` int(10) unsigned DEFAULT NULL,
  `client_id` bigint(20) unsigned NOT NULL,
  `equipment_id` bigint(20) unsigned NOT NULL,
  `category_id` bigint(20) unsigned NOT NULL,
  `form_template_id` bigint(20) unsigned NOT NULL,
  `inspector_id` bigint(20) unsigned NOT NULL,
  `inspection_date` date NOT NULL,
  `next_due_date` date DEFAULT NULL,
  `location` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `result` enum('pending','safe','unsafe','conditional') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `status` enum('draft','submitted','correction','approved','issued','revoked','expired') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `remarks` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `cloned_from_inspection_id` bigint(20) unsigned DEFAULT NULL,
  `renewal_source_certificate_id` bigint(20) unsigned DEFAULT NULL,
  `legacy_id` bigint(20) unsigned DEFAULT NULL,
  `is_legacy` tinyint(1) NOT NULL DEFAULT '0',
  `legacy_source_table` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `legacy_source_reference` varchar(190) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `legacy_mapping_status` enum('complete','partial','raw_only') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'complete',
  `legacy_payload_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `submitted_at` datetime DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `reference` (`reference`),
  UNIQUE KEY `uq_inspection_scope_sequence` (`client_id`,`category_id`,`sequence_number`),
  KEY `idx_inspections_status` (`status`),
  KEY `idx_inspections_due` (`next_due_date`),
  KEY `idx_inspections_legacy` (`is_legacy`,`legacy_id`),
  KEY `fk_inspection_equipment` (`equipment_id`),
  KEY `fk_inspection_category` (`category_id`),
  KEY `fk_inspection_template` (`form_template_id`),
  KEY `fk_inspection_inspector` (`inspector_id`),
  KEY `idx_inspection_clone_source` (`cloned_from_inspection_id`),
  KEY `idx_inspection_renewal_source` (`renewal_source_certificate_id`),
  CONSTRAINT `fk_inspection_category` FOREIGN KEY (`category_id`) REFERENCES `certification_categories` (`id`),
  CONSTRAINT `fk_inspection_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`),
  CONSTRAINT `fk_inspection_clone_source` FOREIGN KEY (`cloned_from_inspection_id`) REFERENCES `inspections` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_inspection_equipment` FOREIGN KEY (`equipment_id`) REFERENCES `equipment` (`id`),
  CONSTRAINT `fk_inspection_inspector` FOREIGN KEY (`inspector_id`) REFERENCES `users` (`id`),
  CONSTRAINT `fk_inspection_renewal_certificate` FOREIGN KEY (`renewal_source_certificate_id`) REFERENCES `certificates` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_inspection_template` FOREIGN KEY (`form_template_id`) REFERENCES `form_templates` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1073 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `juva_client_master`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `juva_client_master` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `regID` varchar(100) NOT NULL,
  `client_name` varchar(200) NOT NULL,
  `client_phone` varchar(20) NOT NULL,
  `client_email` varchar(100) NOT NULL,
  `client_logo` varchar(150) NOT NULL,
  `client_address` text NOT NULL,
  `status` char(4) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `regID` (`regID`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `juva_equip_insp_check_list`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `juva_equip_insp_check_list` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `inspID` int(10) unsigned NOT NULL,
  `inspComponent` varchar(150) NOT NULL,
  `inspVal` varchar(100) NOT NULL,
  `inspObservation` varchar(200) NOT NULL,
  `inspComment_Rec` text NOT NULL,
  `insp_date` date NOT NULL,
  `status` char(4) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `inspID` (`inspID`),
  CONSTRAINT `juva_equip_insp_check_list_ibfk_1` FOREIGN KEY (`inspID`) REFERENCES `juva_equipment_inspt_master` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=42 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `juva_equipment_insp_calibration`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `juva_equipment_insp_calibration` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `inspID` int(10) unsigned NOT NULL,
  `calib_title` varchar(150) NOT NULL,
  `calib_value` varchar(150) NOT NULL,
  `calib_status` varchar(150) NOT NULL,
  `calib_comment` text NOT NULL,
  `calib_date` date NOT NULL,
  `status` char(4) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `inspID` (`inspID`),
  CONSTRAINT `juva_equipment_insp_calibration_ibfk_1` FOREIGN KEY (`inspID`) REFERENCES `juva_equipment_inspt_master` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `juva_equipment_inspt_ccu`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `juva_equipment_inspt_ccu` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `clientID` int(10) unsigned NOT NULL,
  `inspMastID` int(10) unsigned NOT NULL,
  `ccu_slingID_no` varchar(100) NOT NULL,
  `ccu_SWL` varchar(100) NOT NULL,
  `ccu_sling_angle` varchar(100) NOT NULL,
  `ccu_sling_desc` varchar(150) NOT NULL,
  `ccu_sling_cert_no_date` varchar(150) NOT NULL,
  `ccu_load_date_tested` date NOT NULL,
  `ccu_load_proof_applied` varchar(100) NOT NULL,
  `ccu_load_comp_name` varchar(150) NOT NULL,
  `ccu_load_cert_no` varchar(100) NOT NULL,
  `ccu_shacle_ID_no` varchar(100) NOT NULL,
  `ccu_shacle_SWL` varchar(100) NOT NULL,
  `ccu_shackle_QTY` varchar(50) NOT NULL,
  `ccu_shacle_desc` varchar(200) NOT NULL,
  `ccu_shacle_cert_no_date` varchar(150) NOT NULL,
  `status` char(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `clientID` (`clientID`,`inspMastID`),
  KEY `inspMastID` (`inspMastID`),
  CONSTRAINT `juva_equipment_inspt_ccu_ibfk_1` FOREIGN KEY (`inspMastID`) REFERENCES `juva_equipment_inspt_master` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `juva_equipment_inspt_excavator`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `juva_equipment_inspt_excavator` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `clientID` int(10) unsigned NOT NULL,
  `inspMastID` int(10) unsigned NOT NULL,
  `visual_insp` varchar(250) NOT NULL,
  `funct_test` varchar(250) NOT NULL,
  `status` char(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `clientID` (`clientID`,`inspMastID`),
  KEY `inspMastID` (`inspMastID`),
  CONSTRAINT `juva_equipment_inspt_excavator_ibfk_1` FOREIGN KEY (`inspMastID`) REFERENCES `juva_equipment_inspt_master` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `juva_equipment_inspt_gen_nde`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `juva_equipment_inspt_gen_nde` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `clientID` int(10) unsigned NOT NULL,
  `inspMastID` int(10) unsigned NOT NULL,
  `procedure_ref_no` varchar(100) NOT NULL,
  `specification` varchar(150) NOT NULL,
  `material` varchar(150) NOT NULL,
  `equipment` varchar(150) NOT NULL,
  `medium` varchar(150) NOT NULL,
  `indicator` varchar(150) NOT NULL,
  `method` varchar(150) NOT NULL,
  `date_mpi_test` date NOT NULL,
  `ndt_tech` varchar(150) NOT NULL,
  `status` char(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `clientID` (`clientID`,`inspMastID`),
  KEY `inspMastID` (`inspMastID`),
  CONSTRAINT `juva_equipment_inspt_gen_nde_ibfk_1` FOREIGN KEY (`inspMastID`) REFERENCES `juva_equipment_inspt_master` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `juva_equipment_inspt_master`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `juva_equipment_inspt_master` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `equipID` int(10) unsigned NOT NULL,
  `insp_title` text NOT NULL,
  `clientID` int(10) unsigned NOT NULL,
  `inspCate` char(4) NOT NULL,
  `cert_no` varchar(150) NOT NULL,
  `insp_date` date NOT NULL,
  `insp_report_date` date NOT NULL,
  `insp_address_premises` text NOT NULL,
  `insp_date_last_examine` date NOT NULL,
  `insp_photo` varchar(150) NOT NULL,
  `insp_remark` text NOT NULL,
  `safe_not` char(10) NOT NULL,
  `insp_officer` varchar(200) NOT NULL,
  `insp_officer_position` varchar(200) NOT NULL,
  `next_insp_due` date NOT NULL,
  `insp_ref_standard` varchar(200) NOT NULL,
  `insp_category` char(4) NOT NULL,
  `status` char(4) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `equipID` (`equipID`),
  KEY `clientID` (`clientID`),
  CONSTRAINT `juva_equipment_inspt_master_ibfk_1` FOREIGN KEY (`equipID`) REFERENCES `juva_equipment_master` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=913 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `juva_equipment_inspt_mpi`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `juva_equipment_inspt_mpi` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `clientID` int(10) unsigned NOT NULL,
  `inspMastID` int(10) unsigned NOT NULL,
  `mpi_equipment` varchar(50) NOT NULL,
  `mpi_magnet` varchar(100) NOT NULL,
  `mpi_magnet_curr_ac` char(10) NOT NULL,
  `mpi_magnet_curr_dc` char(10) NOT NULL,
  `mpi_magnet_curr_hwdc` char(10) NOT NULL,
  `mpi_magnet_particle_coil` char(10) NOT NULL,
  `mpi_magnet_particle_prods` char(10) NOT NULL,
  `mpi_magnet_particle_yoke` char(10) NOT NULL,
  `mpi_magnet_particle_uv` char(10) NOT NULL,
  `mpi_magnet_proc_cont` char(10) NOT NULL,
  `mpi_magnet_proc_res` char(10) NOT NULL,
  `mpi_dye_penetrant` varchar(100) NOT NULL,
  `mpi_dye_developer` varchar(100) NOT NULL,
  `mpi_solvent_cleaner` varchar(100) NOT NULL,
  `status` char(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `clientID` (`clientID`,`inspMastID`),
  KEY `inspMastID` (`inspMastID`),
  CONSTRAINT `juva_equipment_inspt_mpi_ibfk_1` FOREIGN KEY (`inspMastID`) REFERENCES `juva_equipment_inspt_master` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `juva_equipment_inspt_shackle`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `juva_equipment_inspt_shackle` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `clientID` int(10) unsigned NOT NULL,
  `inspMastID` int(10) unsigned NOT NULL,
  `is_first_exam` varchar(50) NOT NULL,
  `equipment_con_standard` varchar(50) NOT NULL,
  `next_exam_date` varchar(100) NOT NULL,
  `ident_defect_part_detected` varchar(100) NOT NULL,
  `defect_imm_danger` varchar(100) NOT NULL,
  `defect_could_danger` varchar(100) NOT NULL,
  `repair_carried_particulars` varchar(100) NOT NULL,
  `test_carried_particulars` varchar(150) NOT NULL,
  `status` char(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `clientID` (`clientID`,`inspMastID`),
  KEY `inspMastID` (`inspMastID`),
  CONSTRAINT `juva_equipment_inspt_shackle_ibfk_1` FOREIGN KEY (`inspMastID`) REFERENCES `juva_equipment_inspt_master` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=876 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `juva_equipment_inspt_sub`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `juva_equipment_inspt_sub` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `clientID` int(10) unsigned NOT NULL,
  `inspMastID` int(10) unsigned NOT NULL,
  `equip_desc` varchar(200) NOT NULL,
  `man_make` varchar(150) NOT NULL,
  `model` varchar(150) NOT NULL,
  `man_year` year(4) NOT NULL,
  `sno_ident_no` varchar(100) NOT NULL,
  `asset_reg_no` varchar(100) NOT NULL,
  `tare_weight` varchar(100) NOT NULL,
  `safe_working_load` varchar(100) NOT NULL,
  `equip_location` text NOT NULL,
  `service_type` varchar(150) NOT NULL,
  `ref_standard` varchar(150) NOT NULL,
  `man_number` varchar(50) NOT NULL,
  `date_manufacture` date NOT NULL,
  `gross_weight` varchar(100) NOT NULL,
  `dimen_length` varchar(50) NOT NULL,
  `dimen_width` varchar(50) NOT NULL,
  `dimen_height` varchar(50) NOT NULL,
  `dimen_no_lifting` varchar(50) NOT NULL,
  `dimen_body_forklift_cond` varchar(100) NOT NULL,
  `type_material` varchar(150) NOT NULL,
  `area_insp_surf_condtn` varchar(150) NOT NULL,
  `accp_limit_no_crack_accpted` varchar(50) NOT NULL,
  `status` char(4) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `equipID` (`inspMastID`),
  CONSTRAINT `juva_equipment_inspt_sub_ibfk_1` FOREIGN KEY (`inspMastID`) REFERENCES `juva_equipment_inspt_master` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=896 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `juva_equipment_master`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `juva_equipment_master` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `equip_desc` varchar(200) NOT NULL,
  `man_make` varchar(150) NOT NULL,
  `model` varchar(150) NOT NULL,
  `man_year` year(4) NOT NULL,
  `sno_ident_no` varchar(100) NOT NULL,
  `asset_reg_no` varchar(100) NOT NULL,
  `tare_weight` varchar(100) NOT NULL,
  `safe_working_load` varchar(100) NOT NULL,
  `equip_location` text NOT NULL,
  `service_type` varchar(150) NOT NULL,
  `ref_standard` varchar(150) NOT NULL,
  `man_number` varchar(50) NOT NULL,
  `date_manufacture` date NOT NULL,
  `gross_weight` varchar(100) NOT NULL,
  `dimen_length` varchar(50) NOT NULL,
  `dimen_width` varchar(50) NOT NULL,
  `dimen_height` varchar(50) NOT NULL,
  `dimen_no_lifting` varchar(50) NOT NULL,
  `dimen_body_forklift_cond` varchar(100) NOT NULL,
  `type_material` varchar(150) NOT NULL,
  `area_insp_surf_condtn` varchar(150) NOT NULL,
  `accp_limit_no_crack_accpted` varchar(50) NOT NULL,
  `status` char(4) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `legacy_inspection_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `legacy_inspection_details` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `inspection_id` bigint(20) unsigned NOT NULL,
  `legacy_table` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `legacy_row_id` bigint(20) unsigned DEFAULT NULL,
  `detail_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `mapping_status` enum('partial','raw_only') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'raw_only',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_legacy_detail_inspection` (`inspection_id`),
  KEY `idx_legacy_detail_source` (`legacy_table`,`legacy_row_id`),
  CONSTRAINT `fk_legacy_detail_inspection` FOREIGN KEY (`inspection_id`) REFERENCES `inspections` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1972 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `legacy_migration_runs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `legacy_migration_runs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `source_name` varchar(190) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `clients_imported` int(10) unsigned NOT NULL DEFAULT '0',
  `equipment_imported` int(10) unsigned NOT NULL DEFAULT '0',
  `users_imported` int(10) unsigned NOT NULL DEFAULT '0',
  `inspections_imported` int(10) unsigned NOT NULL DEFAULT '0',
  `certificates_imported` int(10) unsigned NOT NULL DEFAULT '0',
  `details_imported` int(10) unsigned NOT NULL DEFAULT '0',
  `audit_imported` int(10) unsigned NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `message_master_sub`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `message_master_sub` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `MiD` int(10) unsigned NOT NULL,
  `RegID` varchar(50) NOT NULL,
  `Status` char(2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `RegID` (`RegID`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `message_master_tab`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `message_master_tab` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `CompanyDesignate` char(4) NOT NULL,
  `level` char(4) NOT NULL,
  `MessageTitle` varchar(150) NOT NULL,
  `MessageDetails` text NOT NULL,
  `MessageDate` date NOT NULL,
  `Status` char(2) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `otp_challenges`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `otp_challenges` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `challenge_token_hash` char(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `code_hash` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `purpose` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'login',
  `attempts` smallint(5) unsigned NOT NULL DEFAULT '0',
  `expires_at` datetime NOT NULL,
  `consumed_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `challenge_token_hash` (`challenge_token_hash`),
  KEY `idx_otp_user` (`user_id`),
  KEY `idx_otp_expiry` (`expires_at`),
  CONSTRAINT `fk_otp_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=50 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `remember_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `remember_tokens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `selector` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `validator_hash` char(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_agent_hash` char(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip_hash` char(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `expires_at` datetime NOT NULL,
  `revoked_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `selector` (`selector`),
  KEY `idx_remember_user` (`user_id`),
  KEY `idx_remember_expiry` (`expires_at`),
  CONSTRAINT `fk_remember_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `permissions_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `system_configuration`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `system_configuration` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `comp_name` varchar(100) NOT NULL,
  `prod_desc` varchar(200) NOT NULL,
  `software_ID` varchar(100) NOT NULL,
  `licence_code` varchar(150) NOT NULL,
  `expiry_date` date NOT NULL,
  `expiry_duratn` char(10) NOT NULL,
  `status` char(2) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `comp_name` (`comp_name`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `system_configuration_add`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `system_configuration_add` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `office` varchar(250) NOT NULL,
  `add` varchar(100) NOT NULL,
  `phoneNo` varchar(150) NOT NULL,
  `fax` varchar(100) NOT NULL,
  `status` char(2) NOT NULL,
  `email_1` varchar(100) NOT NULL,
  `email_2` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_access`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_access` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `regID` varchar(30) NOT NULL,
  `accessID` char(4) NOT NULL,
  `new` char(2) NOT NULL DEFAULT '0',
  `edit` char(2) NOT NULL DEFAULT '0',
  `delete` char(2) NOT NULL DEFAULT '0',
  `view` char(2) NOT NULL DEFAULT '0',
  `comment` char(2) NOT NULL DEFAULT '0',
  `report` char(2) NOT NULL DEFAULT '0',
  `sup_adm` char(2) NOT NULL,
  `trans_rev` char(2) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `regID` (`regID`),
  CONSTRAINT `user_access_ibfk_1` FOREIGN KEY (`regID`) REFERENCES `user_profile` (`regID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_profile`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_profile` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `regID` varchar(30) NOT NULL,
  `userID` varchar(30) NOT NULL,
  `userPass` varchar(50) NOT NULL,
  `title` char(2) NOT NULL,
  `SurName` varchar(20) NOT NULL,
  `otherName` varchar(30) NOT NULL,
  `phoneNo` char(20) NOT NULL,
  `eMail` varchar(40) NOT NULL,
  `address` varchar(70) NOT NULL,
  `lga` char(3) NOT NULL,
  `state` char(3) NOT NULL,
  `client` varchar(40) NOT NULL,
  `qualification` varchar(150) NOT NULL,
  `passportPath` varchar(150) NOT NULL,
  `status` char(2) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `regID` (`regID`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `role_id` bigint(20) unsigned NOT NULL,
  `client_id` bigint(20) unsigned DEFAULT NULL,
  `legacy_reg_id` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(160) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(190) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `username` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `phone` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `qualification` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `job_title` varchar(160) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `professional_memberships` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `certificate_signing_role` varchar(160) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `signature_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `signature_original_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `signature_mime_type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `signature_uploaded_at` datetime DEFAULT NULL,
  `signature_uploaded_by` bigint(20) unsigned DEFAULT NULL,
  `signature_is_active` tinyint(1) NOT NULL DEFAULT '0',
  `status` enum('active','invited','suspended','disabled') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'invited',
  `email_verified_at` datetime DEFAULT NULL,
  `invited_at` datetime DEFAULT NULL,
  `last_login_at` datetime DEFAULT NULL,
  `sessions_revoked_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `username` (`username`),
  KEY `idx_users_role` (`role_id`),
  KEY `idx_users_client` (`client_id`),
  KEY `idx_users_legacy_reg_id` (`legacy_reg_id`),
  KEY `idx_users_status` (`status`),
  KEY `fk_user_signature_uploader` (`signature_uploaded_by`),
  KEY `idx_users_sessions_revoked_at` (`sessions_revoked_at`),
  CONSTRAINT `fk_user_signature_uploader` FOREIGN KEY (`signature_uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_users_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`),
  CONSTRAINT `fk_users_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `verification_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `verification_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `certificate_id` bigint(20) unsigned DEFAULT NULL,
  `searched_reference` varchar(190) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `result_status` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `ip_hash` char(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent_hash` char(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `verified_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_verification_date` (`verified_at`),
  KEY `fk_verification_certificate` (`certificate_id`),
  CONSTRAINT `fk_verification_certificate` FOREIGN KEY (`certificate_id`) REFERENCES `certificates` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=167 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

