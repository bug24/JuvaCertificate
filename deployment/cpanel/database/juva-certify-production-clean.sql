
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

LOCK TABLES `approvals` WRITE;
/*!40000 ALTER TABLE `approvals` DISABLE KEYS */;
/*!40000 ALTER TABLE `approvals` ENABLE KEYS */;
UNLOCK TABLES;
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

LOCK TABLES `audit_logs` WRITE;
/*!40000 ALTER TABLE `audit_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `audit_logs` ENABLE KEYS */;
UNLOCK TABLES;
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

LOCK TABLES `audit_trail` WRITE;
/*!40000 ALTER TABLE `audit_trail` DISABLE KEYS */;
/*!40000 ALTER TABLE `audit_trail` ENABLE KEYS */;
UNLOCK TABLES;
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

LOCK TABLES `auth_attempts` WRITE;
/*!40000 ALTER TABLE `auth_attempts` DISABLE KEYS */;
/*!40000 ALTER TABLE `auth_attempts` ENABLE KEYS */;
UNLOCK TABLES;
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

LOCK TABLES `auth_tokens` WRITE;
/*!40000 ALTER TABLE `auth_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `auth_tokens` ENABLE KEYS */;
UNLOCK TABLES;
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

LOCK TABLES `certificate_branding_settings` WRITE;
/*!40000 ALTER TABLE `certificate_branding_settings` DISABLE KEYS */;
INSERT INTO `certificate_branding_settings` VALUES (1,NULL,NULL,NULL,NULL,NULL,0,NULL,'52 Rumuolumeni Road, Port Harcourt, Rivers State','127 Trans Amadi, Port Harcourt, Opposite Schlumberger Nig. Ltd.','+234 806 516 4945 / +234 706 961 2375','juvaoil@gmail.com','www.juvaoil.com','2026-07-14 07:59:36');
/*!40000 ALTER TABLE `certificate_branding_settings` ENABLE KEYS */;
UNLOCK TABLES;
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

LOCK TABLES `certificate_revisions` WRITE;
/*!40000 ALTER TABLE `certificate_revisions` DISABLE KEYS */;
/*!40000 ALTER TABLE `certificate_revisions` ENABLE KEYS */;
UNLOCK TABLES;
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

LOCK TABLES `certificate_sequences` WRITE;
/*!40000 ALTER TABLE `certificate_sequences` DISABLE KEYS */;
/*!40000 ALTER TABLE `certificate_sequences` ENABLE KEYS */;
UNLOCK TABLES;
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

LOCK TABLES `certificates` WRITE;
/*!40000 ALTER TABLE `certificates` DISABLE KEYS */;
/*!40000 ALTER TABLE `certificates` ENABLE KEYS */;
UNLOCK TABLES;
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

LOCK TABLES `certification_categories` WRITE;
/*!40000 ALTER TABLE `certification_categories` DISABLE KEYS */;
INSERT INTO `certification_categories` VALUES (1,'CCU','CCUVIS','CCU Visual Inspection','Visual examination certificate for cargo carrying units, lifting sets, shackles, load-test records and MPI/visual inspection details.',6,'Certificate of Visual Examination','ccu_visual','ccu-visual-v1',NULL,2,'JUVA-CCU','Certificate Number','#0F4C81',1,'active','2026-06-27 16:26:22','2026-07-25 16:13:46'),(2,'MPI','MPI','Magnetic Particle Inspection','Magnetic particle and general non-destructive examination.',12,'NDE Report',NULL,NULL,NULL,1,'JUVA-MPI','Report Number','#4447d7',1,'inactive','2026-06-27 16:26:22','2026-07-25 11:55:57'),(3,'SHK','SHKACC','Shackles and Accessories','Thorough examination of shackles, pins and related lifting accessories.',6,'Report of Thorough Examination of Lifting Equipment','rigging_accessory','rigging-accessory-v1',NULL,1,'JUVA-SHK','Report Number','#18794E',1,'inactive','2026-06-27 16:26:22','2026-07-25 16:13:46'),(4,'FLT','FLT','Forklift Inspection','Visual, functional and safety inspection of forklift equipment.',12,'Plant Certificate',NULL,NULL,NULL,1,'JUVA-FLT','Certificate Number','#f3b700',1,'inactive','2026-06-27 16:26:22','2026-07-25 11:54:32'),(5,'LEG-C','LEGC','Legacy Inspection C','Imported from legacy inspection category C. Original sub-categories are preserved on imported inspection records.',6,'Legacy Certificate',NULL,NULL,NULL,1,'JOSL-C','Legacy Certificate Number','#515151',1,'legacy','2026-06-27 16:26:51','2026-07-24 19:11:04'),(6,'LEG-B','LEGB','Legacy Inspection B','Imported from legacy inspection category B. Original sub-categories are preserved on imported inspection records.',6,'Legacy Certificate',NULL,NULL,NULL,1,'JOSL-B','Legacy Certificate Number','#515151',1,'legacy','2026-06-27 16:26:51','2026-07-24 19:11:04'),(7,'LEG-D','LEGD','Legacy Inspection D','Imported from legacy inspection category D. Original sub-categories are preserved on imported inspection records.',6,'Legacy Certificate',NULL,NULL,NULL,1,'JOSL-D','Legacy Certificate Number','#515151',1,'legacy','2026-06-27 16:26:51','2026-07-24 19:11:04'),(8,'CHBLK','CHBLK','Chain Block','Thorough examination of chain blocks and associated operating components.',6,'Report of Thorough Examination of Lifting Equipment','chain_block','chain-block-v1',NULL,2,'JUVA-CHBLK','Report Number','#7A1F1F',1,'active','2026-07-09 17:36:10','2026-07-25 16:13:46'),(9,'LVHST','LVHST','Lever Hoist','Dedicated thorough examination certificate for lever hoists.',6,'Certificate of Thorough Examination','lever_hoist','lever-hoist-v1',NULL,2,'JUVA-LVHST','Certificate Number','#7A1F1F',1,'active','2026-07-09 17:36:10','2026-07-25 16:13:46'),(10,'FWS','FLTWBSL','Flat Webbing Sling','Dedicated thorough examination certificate for flat webbing slings.',6,'Certificate of Thorough Examination','flat_webbing_sling','flat-webbing-sling-v1',NULL,2,'JUVA-FWS','Certificate Number','#E67E22',1,'active','2026-07-09 17:36:10','2026-07-25 16:13:46'),(11,'ERS','ERWSLNG','Endless Round Webbing Sling','Dedicated thorough examination certificate for endless round webbing slings.',6,'Certificate of Thorough Examination','endless_round_webbing_sling','endless-round-webbing-sling-v1',NULL,2,'JUVA-ERS','Certificate Number','#E67E22',1,'active','2026-07-09 17:36:10','2026-07-25 16:13:46'),(12,'WRS','WRSLNG','Wire Rope Sling','Thorough examination of wire rope slings, fittings and rope condition.',6,'Report of Thorough Examination of Lifting Equipment','rigging_accessory','rigging-accessory-v1',NULL,1,'JUVA-WRS','Report Number','#1E8449',1,'inactive','2026-07-09 17:36:11','2026-07-25 16:13:46'),(13,'EYEBLT','EYEBLT','Eye Bolt','Dedicated landscape register certificate for thorough examination of eye bolts.',6,'Certificate of Thorough Examination','eye_bolt','eye-bolt-landscape-v1',NULL,2,'JUVA-EYEBLT','Certificate Number','#1565C0',1,'active','2026-07-09 17:36:11','2026-07-25 16:13:46'),(14,'HOOK','HOOK','Hook','Dedicated landscape register certificate for thorough examination of hooks.',6,'Certificate of Thorough Examination','hook','hook-landscape-v1',NULL,2,'JUVA-HOOK','Certificate Number','#1565C0',1,'active','2026-07-09 17:36:11','2026-07-25 16:13:46'),(15,'HCLMP','HCLAMP','Horizontal Clamp','Thorough examination of horizontal lifting clamps and gripping surfaces.',6,'Report of Thorough Examination of Lifting Equipment','sling_clamp_general','clamp-general-v1',NULL,1,'JUVA-HCLMP','Report Number','#455A64',1,'inactive','2026-07-09 17:36:11','2026-07-25 16:13:46'),(16,'VCLMP','VCLAMP','Vertical Clamp','Thorough examination of vertical lifting clamps and jaw condition.',6,'Report of Thorough Examination of Lifting Equipment','sling_clamp_general','clamp-general-v1',NULL,1,'JUVA-VCLMP','Report Number','#455A64',1,'inactive','2026-07-09 17:36:11','2026-07-25 16:13:46'),(17,'UCLMP','UCLAMP','Universal Clamp','Thorough examination of universal clamps and locking assemblies.',6,'Report of Thorough Examination of Lifting Equipment','sling_clamp_general','clamp-general-v1',NULL,1,'JUVA-UCLMP','Report Number','#455A64',1,'inactive','2026-07-09 17:36:11','2026-07-25 16:13:46'),(18,'LFMAG','LFTMAG','Lifting Magnet','Thorough examination of lifting magnets, face condition and holding test results.',6,'Report of Thorough Examination of Lifting Equipment','sling_clamp_general','magnet-general-v1',NULL,1,'JUVA-LFMAG','Report Number','#2E7D32',1,'inactive','2026-07-09 17:36:12','2026-07-25 16:13:46'),(19,'PLTTRK','PLTTRK','Pallet Truck','Functional and safety inspection of pallet trucks and hydraulic assemblies.',12,'Report of Thorough Examination of Lifting Equipment','sling_clamp_general','pallet-truck-v1',NULL,1,'JUVA-PLTTRK','Report Number','#8E44AD',1,'inactive','2026-07-09 17:36:12','2026-07-25 16:13:46'),(20,'MPISB','MPISBAR','MPI / NDT Spreader Bar','Dedicated visual and magnetic particle inspection certificate for spreader bars and lifting beams.',6,'Visual/Magnetic Particle Inspection Certificate','mpi_spreader_bar','mpi-spreader-bar-v1',NULL,2,'JUVA-MPISB','Report Number','#334E75',1,'active','2026-07-09 17:36:12','2026-07-25 16:13:46'),(21,'GENLIFTACC','GLACC','General Lifting Accessories','Controlled landscape certificate for lifting accessories without an approved dedicated category. Use a dedicated category whenever one exists.',6,'General Lifting Accessories Thorough Examination','general_lifting_accessory','general-lifting-accessory-v1',NULL,1,'JUVA-GENLIFTACC','Certificate Number','#285943',1,'active','2026-07-24 18:11:05','2026-07-25 16:13:46'),(22,'GENTHREXAM','GTEX','General Thorough Examination','Controlled portrait thorough-examination certificate for unusual equipment without an approved dedicated category.',6,'General Certificate of Thorough Examination','general_thorough_examination','general-thorough-examination-v1',NULL,1,'JUVA-GENTHREXAM','Certificate Number','#3D5366',1,'active','2026-07-24 18:11:05','2026-07-25 16:13:46');
/*!40000 ALTER TABLE `certification_categories` ENABLE KEYS */;
UNLOCK TABLES;
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

LOCK TABLES `clients` WRITE;
/*!40000 ALTER TABLE `clients` DISABLE KEYS */;
/*!40000 ALTER TABLE `clients` ENABLE KEYS */;
UNLOCK TABLES;
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

LOCK TABLES `code_param_desc` WRITE;
/*!40000 ALTER TABLE `code_param_desc` DISABLE KEYS */;
/*!40000 ALTER TABLE `code_param_desc` ENABLE KEYS */;
UNLOCK TABLES;
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

LOCK TABLES `code_param_tab` WRITE;
/*!40000 ALTER TABLE `code_param_tab` DISABLE KEYS */;
/*!40000 ALTER TABLE `code_param_tab` ENABLE KEYS */;
UNLOCK TABLES;
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

LOCK TABLES `department_tab` WRITE;
/*!40000 ALTER TABLE `department_tab` DISABLE KEYS */;
/*!40000 ALTER TABLE `department_tab` ENABLE KEYS */;
UNLOCK TABLES;
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

LOCK TABLES `email_notifications` WRITE;
/*!40000 ALTER TABLE `email_notifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `email_notifications` ENABLE KEYS */;
UNLOCK TABLES;
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

LOCK TABLES `equipment` WRITE;
/*!40000 ALTER TABLE `equipment` DISABLE KEYS */;
/*!40000 ALTER TABLE `equipment` ENABLE KEYS */;
UNLOCK TABLES;
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

LOCK TABLES `folder_grouping` WRITE;
/*!40000 ALTER TABLE `folder_grouping` DISABLE KEYS */;
/*!40000 ALTER TABLE `folder_grouping` ENABLE KEYS */;
UNLOCK TABLES;
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

LOCK TABLES `form_fields` WRITE;
/*!40000 ALTER TABLE `form_fields` DISABLE KEYS */;
INSERT INTO `form_fields` VALUES (1,1,'client_name','Client Name',NULL,NULL,'text',1,1,0,NULL,NULL,NULL,NULL,10,'2026-06-27 16:26:22'),(2,2,'client_name','Client Name',NULL,NULL,'text',1,1,0,NULL,NULL,NULL,NULL,10,'2026-06-27 16:26:22'),(3,3,'client_name','Client Name',NULL,NULL,'text',1,1,0,NULL,NULL,NULL,NULL,10,'2026-06-27 16:26:22'),(4,4,'client_name','Client Name',NULL,NULL,'text',1,1,0,NULL,NULL,NULL,NULL,10,'2026-06-27 16:26:22'),(5,1,'equipment_id','Equipment ID / Tag Number',NULL,NULL,'text',1,1,0,NULL,NULL,NULL,NULL,20,'2026-06-27 16:26:22'),(6,2,'equipment_id','Equipment ID / Tag Number',NULL,NULL,'text',1,1,0,NULL,NULL,NULL,NULL,20,'2026-06-27 16:26:22'),(7,3,'equipment_id','Equipment ID / Tag Number',NULL,NULL,'text',1,1,0,NULL,NULL,NULL,NULL,20,'2026-06-27 16:26:22'),(8,4,'equipment_id','Equipment ID / Tag Number',NULL,NULL,'text',1,1,0,NULL,NULL,NULL,NULL,20,'2026-06-27 16:26:22'),(9,1,'inspection_date','Inspection Date',NULL,NULL,'date',1,1,0,NULL,NULL,NULL,NULL,30,'2026-06-27 16:26:22'),(10,2,'inspection_date','Inspection Date',NULL,NULL,'date',1,1,0,NULL,NULL,NULL,NULL,30,'2026-06-27 16:26:22'),(11,3,'inspection_date','Inspection Date',NULL,NULL,'date',1,1,0,NULL,NULL,NULL,NULL,30,'2026-06-27 16:26:22'),(12,4,'inspection_date','Inspection Date',NULL,NULL,'date',1,1,0,NULL,NULL,NULL,NULL,30,'2026-06-27 16:26:22'),(13,1,'swl_capacity','SWL / Capacity',NULL,NULL,'text',0,1,0,NULL,NULL,NULL,NULL,40,'2026-06-27 16:26:22'),(14,2,'swl_capacity','SWL / Capacity',NULL,NULL,'text',0,1,0,NULL,NULL,NULL,NULL,40,'2026-06-27 16:26:22'),(15,3,'swl_capacity','SWL / Capacity',NULL,NULL,'text',0,1,0,NULL,NULL,NULL,NULL,40,'2026-06-27 16:26:22'),(16,4,'swl_capacity','SWL / Capacity',NULL,NULL,'text',0,1,0,NULL,NULL,NULL,NULL,40,'2026-06-27 16:26:22'),(17,1,'remarks','Remarks',NULL,NULL,'textarea',0,1,0,NULL,NULL,NULL,NULL,50,'2026-06-27 16:26:22'),(18,2,'remarks','Remarks',NULL,NULL,'textarea',0,1,0,NULL,NULL,NULL,NULL,50,'2026-06-27 16:26:22'),(19,3,'remarks','Remarks',NULL,NULL,'textarea',0,1,0,NULL,NULL,NULL,NULL,50,'2026-06-27 16:26:22'),(20,4,'remarks','Remarks',NULL,NULL,'textarea',0,1,0,NULL,NULL,NULL,NULL,50,'2026-06-27 16:26:22'),(32,8,'legacy_certificate_no','Legacy Certificate Number',NULL,NULL,'text',1,1,0,NULL,NULL,NULL,NULL,10,'2026-06-27 16:26:51'),(33,9,'legacy_certificate_no','Legacy Certificate Number',NULL,NULL,'text',1,1,0,NULL,NULL,NULL,NULL,10,'2026-06-27 16:26:51'),(34,10,'legacy_certificate_no','Legacy Certificate Number',NULL,NULL,'text',1,1,0,NULL,NULL,NULL,NULL,10,'2026-06-27 16:26:51'),(35,8,'legacy_title','Legacy Inspection Title',NULL,NULL,'text',0,1,0,NULL,NULL,NULL,NULL,20,'2026-06-27 16:26:51'),(36,9,'legacy_title','Legacy Inspection Title',NULL,NULL,'text',0,1,0,NULL,NULL,NULL,NULL,20,'2026-06-27 16:26:51'),(37,10,'legacy_title','Legacy Inspection Title',NULL,NULL,'text',0,1,0,NULL,NULL,NULL,NULL,20,'2026-06-27 16:26:51'),(38,8,'legacy_safe_for_use','Safe For Use',NULL,NULL,'text',0,1,0,NULL,NULL,NULL,NULL,30,'2026-06-27 16:26:51'),(39,9,'legacy_safe_for_use','Safe For Use',NULL,NULL,'text',0,1,0,NULL,NULL,NULL,NULL,30,'2026-06-27 16:26:51'),(40,10,'legacy_safe_for_use','Safe For Use',NULL,NULL,'text',0,1,0,NULL,NULL,NULL,NULL,30,'2026-06-27 16:26:51'),(41,8,'legacy_standard','Reference Standard',NULL,NULL,'text',0,1,0,NULL,NULL,NULL,NULL,40,'2026-06-27 16:26:51'),(42,9,'legacy_standard','Reference Standard',NULL,NULL,'text',0,1,0,NULL,NULL,NULL,NULL,40,'2026-06-27 16:26:51'),(43,10,'legacy_standard','Reference Standard',NULL,NULL,'text',0,1,0,NULL,NULL,NULL,NULL,40,'2026-06-27 16:26:51'),(44,8,'legacy_remarks','Legacy Remarks',NULL,NULL,'textarea',0,1,0,NULL,NULL,NULL,NULL,50,'2026-06-27 16:26:51'),(45,9,'legacy_remarks','Legacy Remarks',NULL,NULL,'textarea',0,1,0,NULL,NULL,NULL,NULL,50,'2026-06-27 16:26:51'),(46,10,'legacy_remarks','Legacy Remarks',NULL,NULL,'textarea',0,1,0,NULL,NULL,NULL,NULL,50,'2026-06-27 16:26:51'),(47,11,'legacy_certificate_no','Legacy Certificate Number',NULL,NULL,'text',1,1,0,NULL,NULL,NULL,NULL,10,'2026-07-06 07:39:22'),(48,11,'legacy_title','Legacy Inspection Title',NULL,NULL,'text',0,1,0,NULL,NULL,NULL,NULL,20,'2026-07-06 07:39:22'),(49,11,'legacy_safe_for_use','Safe For Use',NULL,NULL,'text',0,1,0,NULL,NULL,NULL,NULL,30,'2026-07-06 07:39:22'),(50,11,'legacy_standard','Reference Standard',NULL,NULL,'text',0,1,0,NULL,NULL,NULL,NULL,40,'2026-07-06 07:39:22'),(51,11,'legacy_remarks','Legacy Remarks',NULL,NULL,'textarea',0,1,0,NULL,NULL,NULL,NULL,50,'2026-07-06 07:39:22'),(52,12,'client_name','Client Name','Client or asset owner shown on the certificate.',NULL,'text',1,1,0,'summary',NULL,NULL,NULL,10,'2026-07-09 17:36:10'),(53,12,'client_address','Client Address','Site or office address where required by the certificate.',NULL,'textarea',0,1,0,'summary',NULL,NULL,NULL,20,'2026-07-09 17:36:10'),(54,12,'inspection_location','Inspection Location','Location where the examination was performed.',NULL,'text',0,1,0,'summary',NULL,NULL,NULL,30,'2026-07-09 17:36:10'),(55,12,'equipment_description','Equipment Description','Describe the inspected equipment as it should appear on the certificate.',NULL,'textarea',1,1,0,'equipment',NULL,NULL,NULL,40,'2026-07-09 17:36:10'),(56,12,'equipment_identifier','Equipment ID / Tag Number','Tag, asset number, serial or ID used by the client.',NULL,'text',1,1,0,'equipment',NULL,NULL,NULL,50,'2026-07-09 17:36:10'),(57,12,'manufacturer','Manufacturer','Original manufacturer where known.',NULL,'text',0,1,0,'equipment',NULL,NULL,NULL,60,'2026-07-09 17:36:10'),(58,12,'date_of_manufacture','Date of Manufacture','Manufacture date shown on the certificate where available.',NULL,'date',0,1,0,'equipment',NULL,NULL,NULL,70,'2026-07-09 17:36:10'),(59,12,'date_of_previous_examination','Date of Previous Examination','Previous thorough examination date.',NULL,'date',0,1,0,'examination',NULL,NULL,NULL,80,'2026-07-09 17:36:10'),(60,12,'date_of_current_examination','Date of Current Examination','Current examination date as shown on the PDF.',NULL,'date',1,1,0,'examination',NULL,NULL,NULL,90,'2026-07-09 17:36:10'),(61,12,'safe_working_load','SWL / WLL / Capacity','Rated safe working load, working load limit or capacity.',NULL,'text',0,1,0,'examination',NULL,NULL,NULL,100,'2026-07-09 17:36:10'),(62,12,'result_summary','Result Summary','Overall condition and decision statement.',NULL,'pass_fail',1,1,0,'decision',NULL,NULL,NULL,110,'2026-07-09 17:36:10'),(63,12,'defects_affecting_safety','Defects Affecting Safety','Defects found that affect safe use.',NULL,'textarea',0,1,0,'decision',NULL,NULL,NULL,120,'2026-07-09 17:36:10'),(64,12,'repairs_or_tests_required','Repairs / Tests Required','Repairs, further tests or conditions before return to service.',NULL,'textarea',0,1,0,'decision',NULL,NULL,NULL,130,'2026-07-09 17:36:10'),(65,12,'additional_remarks','Additional Remarks','Any extra remarks to appear on the certificate.',NULL,'textarea',0,1,0,'remarks',NULL,NULL,NULL,140,'2026-07-09 17:36:10'),(66,12,'inspector_name','Competent Person / Inspector Name',NULL,NULL,'text',1,1,0,'signoff',NULL,NULL,NULL,150,'2026-07-09 17:36:10'),(67,12,'inspector_qualification','Inspector Qualification',NULL,NULL,'text',0,1,0,'signoff',NULL,NULL,NULL,160,'2026-07-09 17:36:10'),(68,12,'next_due_date','Next Due Date',NULL,NULL,'date',1,1,0,'signoff',NULL,NULL,NULL,170,'2026-07-09 17:36:10'),(69,12,'ccu_number','CCU / Container Number',NULL,NULL,'text',1,1,0,'equipment',NULL,NULL,NULL,180,'2026-07-09 17:36:10'),(70,12,'unit_type','Unit Type',NULL,NULL,'text',1,1,0,'equipment',NULL,NULL,NULL,190,'2026-07-09 17:36:10'),(71,12,'owner_operator','Owner / Operator',NULL,NULL,'text',0,1,0,'equipment',NULL,NULL,NULL,200,'2026-07-09 17:36:10'),(72,12,'tare_weight','Tare Weight',NULL,NULL,'text',0,1,0,'equipment',NULL,NULL,NULL,210,'2026-07-09 17:36:10'),(73,12,'gross_weight','Gross Weight',NULL,NULL,'text',0,1,0,'equipment',NULL,NULL,NULL,220,'2026-07-09 17:36:10'),(74,12,'markings_plate_details','Markings / Plate Details',NULL,NULL,'textarea',0,1,0,'decision',NULL,NULL,NULL,230,'2026-07-09 17:36:10'),(286,23,'client_name','Client Name','Client or asset owner shown on the certificate.',NULL,'text',1,1,0,'summary',NULL,NULL,NULL,10,'2026-07-09 17:36:11'),(287,23,'client_address','Client Address','Site or office address where required by the certificate.',NULL,'textarea',0,1,0,'summary',NULL,NULL,NULL,20,'2026-07-09 17:36:11'),(288,23,'inspection_location','Inspection Location','Location where the examination was performed.',NULL,'text',0,1,0,'summary',NULL,NULL,NULL,30,'2026-07-09 17:36:11'),(289,23,'equipment_description','Equipment Description','Describe the inspected equipment as it should appear on the certificate.',NULL,'textarea',1,1,0,'equipment',NULL,NULL,NULL,40,'2026-07-09 17:36:11'),(290,23,'equipment_identifier','Equipment ID / Tag Number','Tag, asset number, serial or ID used by the client.',NULL,'text',1,1,0,'equipment',NULL,NULL,NULL,50,'2026-07-09 17:36:11'),(291,23,'manufacturer','Manufacturer','Original manufacturer where known.',NULL,'text',0,1,0,'equipment',NULL,NULL,NULL,60,'2026-07-09 17:36:11'),(292,23,'date_of_manufacture','Date of Manufacture','Manufacture date shown on the certificate where available.',NULL,'date',0,1,0,'equipment',NULL,NULL,NULL,70,'2026-07-09 17:36:11'),(293,23,'date_of_previous_examination','Date of Previous Examination','Previous thorough examination date.',NULL,'date',0,1,0,'examination',NULL,NULL,NULL,80,'2026-07-09 17:36:11'),(294,23,'date_of_current_examination','Date of Current Examination','Current examination date as shown on the PDF.',NULL,'date',1,1,0,'examination',NULL,NULL,NULL,90,'2026-07-09 17:36:11'),(295,23,'safe_working_load','SWL / WLL / Capacity','Rated safe working load, working load limit or capacity.',NULL,'text',0,1,0,'examination',NULL,NULL,NULL,100,'2026-07-09 17:36:11'),(296,23,'result_summary','Result Summary','Overall condition and decision statement.',NULL,'pass_fail',1,1,0,'decision',NULL,NULL,NULL,110,'2026-07-09 17:36:11'),(297,23,'defects_affecting_safety','Defects Affecting Safety','Defects found that affect safe use.',NULL,'textarea',0,1,0,'decision',NULL,NULL,NULL,120,'2026-07-09 17:36:11'),(298,23,'repairs_or_tests_required','Repairs / Tests Required','Repairs, further tests or conditions before return to service.',NULL,'textarea',0,1,0,'decision',NULL,NULL,NULL,130,'2026-07-09 17:36:11'),(299,23,'additional_remarks','Additional Remarks','Any extra remarks to appear on the certificate.',NULL,'textarea',0,1,0,'remarks',NULL,NULL,NULL,140,'2026-07-09 17:36:11'),(300,23,'inspector_name','Competent Person / Inspector Name',NULL,NULL,'text',1,1,0,'signoff',NULL,NULL,NULL,150,'2026-07-09 17:36:11'),(301,23,'inspector_qualification','Inspector Qualification',NULL,NULL,'text',0,1,0,'signoff',NULL,NULL,NULL,160,'2026-07-09 17:36:11'),(302,23,'next_due_date','Next Due Date',NULL,NULL,'date',1,1,0,'signoff',NULL,NULL,NULL,170,'2026-07-09 17:36:12'),(303,23,'jaw_opening_range','Jaw Opening Range',NULL,NULL,'text',0,1,0,'equipment',NULL,NULL,NULL,180,'2026-07-09 17:36:12'),(304,23,'lock_condition','Locking Mechanism Condition',NULL,NULL,'pass_fail',1,1,0,'inspection',NULL,NULL,NULL,190,'2026-07-09 17:36:12'),(305,23,'body_condition','Body Condition',NULL,NULL,'pass_fail',1,1,0,'inspection',NULL,NULL,NULL,200,'2026-07-09 17:36:12'),(368,27,'client_name','Certificate Client Name Override',NULL,NULL,'text',0,1,0,'client',NULL,NULL,NULL,10,'2026-07-11 13:11:16'),(369,27,'client_address','Certificate Client Address Override',NULL,NULL,'textarea',0,1,0,'client',NULL,NULL,NULL,20,'2026-07-11 13:11:16'),(370,27,'location_line_1','Location of Inspection - Line 1',NULL,NULL,'text',1,1,0,'client',NULL,NULL,NULL,30,'2026-07-11 13:11:16'),(371,27,'location_line_2','Location of Inspection - Line 2',NULL,NULL,'text',0,1,0,'client',NULL,NULL,NULL,40,'2026-07-11 13:11:16'),(372,27,'customer_order_number','Customer Order Number',NULL,NULL,'text',1,1,0,'metadata',NULL,NULL,NULL,50,'2026-07-11 13:11:16'),(375,27,'unit_id_number','Unit ID Number',NULL,NULL,'text',1,1,0,'unit',NULL,NULL,NULL,100,'2026-07-11 13:11:16'),(376,27,'asset_number','Asset Number',NULL,NULL,'text',0,1,0,'unit',NULL,NULL,NULL,110,'2026-07-11 13:11:16'),(377,27,'unit_quantity','Unit Quantity',NULL,NULL,'number',1,1,0,'unit',NULL,NULL,NULL,120,'2026-07-11 13:11:16'),(378,27,'description_of_unit','Description of Unit',NULL,NULL,'textarea',1,1,0,'unit',NULL,NULL,NULL,130,'2026-07-11 13:11:16'),(379,27,'unit_tare_weight','Tare Weight',NULL,NULL,'text',0,1,0,'unit',NULL,NULL,NULL,140,'2026-07-11 13:11:16'),(380,27,'unit_swl_payload','SWL / Payload',NULL,NULL,'text',1,1,0,'unit',NULL,NULL,NULL,150,'2026-07-11 13:11:16'),(381,27,'unit_gross_weight','Gross Weight',NULL,NULL,'text',1,1,0,'unit',NULL,NULL,NULL,160,'2026-07-11 13:11:16'),(382,27,'sling_id','Sling ID',NULL,NULL,'text',1,1,0,'sling',NULL,NULL,NULL,200,'2026-07-11 13:11:16'),(383,27,'sling_quantity','Sling Quantity',NULL,NULL,'number',1,1,0,'sling',NULL,NULL,NULL,210,'2026-07-11 13:11:16'),(384,27,'sling_quantity_unit','Sling Quantity Unit',NULL,NULL,'text',1,1,0,'sling',NULL,NULL,NULL,220,'2026-07-11 13:11:16'),(385,27,'sling_details','Sling Details',NULL,NULL,'textarea',1,1,0,'sling',NULL,NULL,NULL,230,'2026-07-11 13:11:16'),(386,27,'sling_tare_na','Sling Tare / N/A',NULL,NULL,'text',0,1,0,'sling',NULL,NULL,NULL,240,'2026-07-11 13:11:16'),(387,27,'sling_swl','Sling SWL',NULL,NULL,'text',1,1,0,'sling',NULL,NULL,NULL,250,'2026-07-11 13:11:16'),(388,27,'sling_gross_na','Sling Gross / N/A',NULL,NULL,'text',0,1,0,'sling',NULL,NULL,NULL,260,'2026-07-11 13:11:16'),(389,27,'load_unit_id','Load Test - Unit ID',NULL,NULL,'text',1,1,0,'load_test',NULL,NULL,NULL,300,'2026-07-11 13:11:16'),(390,27,'load_tested_by','Load Test - Tested By',NULL,NULL,'text',1,1,0,'load_test',NULL,NULL,NULL,310,'2026-07-11 13:11:16'),(391,27,'load_test_certificate_number','Load Test Certificate Number',NULL,NULL,'text',1,1,0,'load_test',NULL,NULL,NULL,320,'2026-07-11 13:11:16'),(392,27,'load_proof_load_test','Proof Load Test',NULL,NULL,'text',1,1,0,'load_test',NULL,NULL,NULL,330,'2026-07-11 13:11:16'),(393,27,'load_test_date','Load Test Date',NULL,NULL,'date',1,1,0,'load_test',NULL,NULL,NULL,340,'2026-07-11 13:11:16'),(394,27,'sling_test_sling_id','Sling Test - Sling ID',NULL,NULL,'text',1,1,0,'load_test',NULL,NULL,NULL,350,'2026-07-11 13:11:16'),(395,27,'sling_manufacturer','Sling Manufacturer',NULL,NULL,'text',1,1,0,'load_test',NULL,NULL,NULL,360,'2026-07-11 13:11:16'),(396,27,'sling_oem_certificate_number','Sling OEM Certificate Number',NULL,NULL,'text',1,1,0,'load_test',NULL,NULL,NULL,370,'2026-07-11 13:11:16'),(397,27,'sling_proof_load_test','Sling Proof Load Test / Dash',NULL,NULL,'text',0,1,0,'load_test',NULL,NULL,NULL,380,'2026-07-11 13:11:16'),(398,27,'sling_test_date','Sling Test Date',NULL,NULL,'date',1,1,0,'load_test',NULL,NULL,NULL,390,'2026-07-11 13:11:16'),(399,27,'standards','Standards',NULL,NULL,'text',1,1,0,'inspection',NULL,NULL,NULL,400,'2026-07-11 13:11:16'),(400,27,'equipment_type','Equipment Type',NULL,NULL,'text',1,1,0,'inspection',NULL,NULL,NULL,410,'2026-07-11 13:11:16'),(401,27,'contrast_media','Contrast Media',NULL,NULL,'text',1,1,0,'inspection',NULL,NULL,NULL,420,'2026-07-11 13:11:16'),(402,27,'test_procedure','Test Procedure',NULL,NULL,'text',1,1,0,'inspection',NULL,NULL,NULL,430,'2026-07-11 13:11:16'),(403,27,'pole_spacing','Pole Spacing',NULL,NULL,'text',0,1,0,'inspection',NULL,NULL,NULL,440,'2026-07-11 13:11:16'),(404,27,'indicator','Indicator',NULL,NULL,'text',0,1,0,'inspection',NULL,NULL,NULL,450,'2026-07-11 13:11:16'),(405,27,'technique','Technique',NULL,NULL,'text',1,1,0,'inspection',NULL,NULL,NULL,460,'2026-07-11 13:11:16'),(406,27,'test_method','Test Method',NULL,NULL,'text',1,1,0,'inspection',NULL,NULL,NULL,470,'2026-07-11 13:11:16'),(407,27,'test_result','Test Result',NULL,NULL,'textarea',1,1,0,'inspection',NULL,NULL,NULL,480,'2026-07-11 13:11:16'),(409,27,'demagnetization','Demagnetization',NULL,NULL,'text',0,1,0,'inspection',NULL,NULL,NULL,500,'2026-07-11 13:11:16'),(410,27,'colour_code','Colour Code',NULL,NULL,'text',0,1,0,'inspection',NULL,NULL,NULL,510,'2026-07-11 13:11:16'),(411,27,'inspector_name','Inspector Name Override',NULL,NULL,'text',0,1,0,'signatures',NULL,NULL,NULL,600,'2026-07-11 13:11:16'),(412,27,'inspector_qualifications','Inspector Qualifications',NULL,NULL,'textarea',1,1,0,'signatures',NULL,NULL,NULL,610,'2026-07-11 13:11:16'),(413,27,'authenticator_name','Authenticator Name',NULL,NULL,'text',1,1,0,'signatures',NULL,NULL,NULL,620,'2026-07-11 13:11:16'),(414,27,'authenticator_qualifications','Authenticator Qualifications',NULL,NULL,'textarea',1,1,0,'signatures',NULL,NULL,NULL,630,'2026-07-11 13:11:16'),(415,13,'client_name','Client Name','Client or asset owner shown on the certificate.',NULL,'text',1,1,0,'summary',NULL,NULL,NULL,10,'2026-07-11 13:11:16'),(416,13,'client_address','Client Address','Site or office address where required by the certificate.',NULL,'textarea',0,1,0,'summary',NULL,NULL,NULL,20,'2026-07-11 13:11:16'),(417,13,'inspection_location','Inspection Location','Location where the examination was performed.',NULL,'text',0,1,0,'summary',NULL,NULL,NULL,30,'2026-07-11 13:11:16'),(418,13,'equipment_description','Equipment Description','Describe the inspected equipment as it should appear on the certificate.',NULL,'textarea',1,1,0,'equipment',NULL,NULL,NULL,40,'2026-07-11 13:11:16'),(419,13,'equipment_identifier','Equipment ID / Tag Number','Tag, asset number, serial or ID used by the client.',NULL,'text',1,1,0,'equipment',NULL,NULL,NULL,50,'2026-07-11 13:11:16'),(420,13,'manufacturer','Manufacturer','Original manufacturer where known.',NULL,'text',0,1,0,'equipment',NULL,NULL,NULL,60,'2026-07-11 13:11:16'),(421,13,'date_of_manufacture','Date of Manufacture','Manufacture date shown on the certificate where available.',NULL,'date',0,1,0,'equipment',NULL,NULL,NULL,70,'2026-07-11 13:11:16'),(422,13,'date_of_previous_examination','Date of Previous Examination','Previous thorough examination date.',NULL,'date',0,1,0,'examination',NULL,NULL,NULL,80,'2026-07-11 13:11:16'),(423,13,'date_of_current_examination','Date of Current Examination','Current examination date as shown on the PDF.',NULL,'date',1,1,0,'examination',NULL,NULL,NULL,90,'2026-07-11 13:11:16'),(424,13,'safe_working_load','SWL / WLL / Capacity','Rated safe working load, working load limit or capacity.',NULL,'text',0,1,0,'examination',NULL,NULL,NULL,100,'2026-07-11 13:11:16'),(425,13,'result_summary','Result Summary','Overall condition and decision statement.',NULL,'pass_fail',1,1,0,'decision',NULL,NULL,NULL,110,'2026-07-11 13:11:16'),(426,13,'defects_affecting_safety','Defects Affecting Safety','Defects found that affect safe use.',NULL,'textarea',0,1,0,'decision',NULL,NULL,NULL,120,'2026-07-11 13:11:16'),(427,13,'repairs_or_tests_required','Repairs / Tests Required','Repairs, further tests or conditions before return to service.',NULL,'textarea',0,1,0,'decision',NULL,NULL,NULL,130,'2026-07-11 13:11:16'),(428,13,'additional_remarks','Additional Remarks','Any extra remarks to appear on the certificate.',NULL,'textarea',0,1,0,'remarks',NULL,NULL,NULL,140,'2026-07-11 13:11:16'),(429,13,'inspector_name','Competent Person / Inspector Name',NULL,NULL,'text',1,1,0,'signoff',NULL,NULL,NULL,150,'2026-07-11 13:11:16'),(430,13,'inspector_qualification','Inspector Qualification',NULL,NULL,'text',0,1,0,'signoff',NULL,NULL,NULL,160,'2026-07-11 13:11:16'),(431,13,'next_due_date','Next Due Date',NULL,NULL,'date',1,1,0,'signoff',NULL,NULL,NULL,170,'2026-07-11 13:11:16'),(432,13,'chain_block_model','Chain Block Model',NULL,NULL,'text',0,1,0,'equipment',NULL,NULL,NULL,180,'2026-07-11 13:11:17'),(433,13,'chain_size','Chain Size',NULL,NULL,'text',0,1,0,'equipment',NULL,NULL,NULL,190,'2026-07-11 13:11:17'),(434,13,'lifting_height','Lifting Height',NULL,NULL,'text',0,1,0,'equipment',NULL,NULL,NULL,200,'2026-07-11 13:11:17'),(435,13,'brake_test_result','Brake Test Result',NULL,NULL,'pass_fail',1,1,0,'inspection',NULL,NULL,NULL,210,'2026-07-11 13:11:17'),(436,13,'operational_test_result','Operational Test Result',NULL,NULL,'pass_fail',1,1,0,'inspection',NULL,NULL,NULL,220,'2026-07-11 13:11:17'),(437,14,'client_name','Client Name','Client or asset owner shown on the certificate.',NULL,'text',1,1,0,'summary',NULL,NULL,NULL,10,'2026-07-11 13:11:17'),(438,14,'client_address','Client Address','Site or office address where required by the certificate.',NULL,'textarea',0,1,0,'summary',NULL,NULL,NULL,20,'2026-07-11 13:11:17'),(439,14,'inspection_location','Inspection Location','Location where the examination was performed.',NULL,'text',0,1,0,'summary',NULL,NULL,NULL,30,'2026-07-11 13:11:17'),(440,14,'equipment_description','Equipment Description','Describe the inspected equipment as it should appear on the certificate.',NULL,'textarea',1,1,0,'equipment',NULL,NULL,NULL,40,'2026-07-11 13:11:17'),(441,14,'equipment_identifier','Equipment ID / Tag Number','Tag, asset number, serial or ID used by the client.',NULL,'text',1,1,0,'equipment',NULL,NULL,NULL,50,'2026-07-11 13:11:17'),(442,14,'manufacturer','Manufacturer','Original manufacturer where known.',NULL,'text',0,1,0,'equipment',NULL,NULL,NULL,60,'2026-07-11 13:11:17'),(443,14,'date_of_manufacture','Date of Manufacture','Manufacture date shown on the certificate where available.',NULL,'date',0,1,0,'equipment',NULL,NULL,NULL,70,'2026-07-11 13:11:17'),(444,14,'date_of_previous_examination','Date of Previous Examination','Previous thorough examination date.',NULL,'date',0,1,0,'examination',NULL,NULL,NULL,80,'2026-07-11 13:11:17'),(445,14,'date_of_current_examination','Date of Current Examination','Current examination date as shown on the PDF.',NULL,'date',1,1,0,'examination',NULL,NULL,NULL,90,'2026-07-11 13:11:17'),(446,14,'safe_working_load','SWL / WLL / Capacity','Rated safe working load, working load limit or capacity.',NULL,'text',0,1,0,'examination',NULL,NULL,NULL,100,'2026-07-11 13:11:17'),(447,14,'result_summary','Result Summary','Overall condition and decision statement.',NULL,'pass_fail',1,1,0,'decision',NULL,NULL,NULL,110,'2026-07-11 13:11:17'),(448,14,'defects_affecting_safety','Defects Affecting Safety','Defects found that affect safe use.',NULL,'textarea',0,1,0,'decision',NULL,NULL,NULL,120,'2026-07-11 13:11:17'),(449,14,'repairs_or_tests_required','Repairs / Tests Required','Repairs, further tests or conditions before return to service.',NULL,'textarea',0,1,0,'decision',NULL,NULL,NULL,130,'2026-07-11 13:11:17'),(450,14,'additional_remarks','Additional Remarks','Any extra remarks to appear on the certificate.',NULL,'textarea',0,1,0,'remarks',NULL,NULL,NULL,140,'2026-07-11 13:11:17'),(451,14,'inspector_name','Competent Person / Inspector Name',NULL,NULL,'text',1,1,0,'signoff',NULL,NULL,NULL,150,'2026-07-11 13:11:17'),(452,14,'inspector_qualification','Inspector Qualification',NULL,NULL,'text',0,1,0,'signoff',NULL,NULL,NULL,160,'2026-07-11 13:11:17'),(453,14,'next_due_date','Next Due Date',NULL,NULL,'date',1,1,0,'signoff',NULL,NULL,NULL,170,'2026-07-11 13:11:17'),(454,14,'lever_hoist_model','Lever Hoist Model',NULL,NULL,'text',0,1,0,'equipment',NULL,NULL,NULL,180,'2026-07-11 13:11:17'),(455,14,'chain_size','Chain Size',NULL,NULL,'text',0,1,0,'equipment',NULL,NULL,NULL,190,'2026-07-11 13:11:17'),(456,14,'pull_lift_height','Lift Height',NULL,NULL,'text',0,1,0,'equipment',NULL,NULL,NULL,200,'2026-07-11 13:11:17'),(457,14,'pawl_brake_condition','Pawl / Brake Condition',NULL,NULL,'pass_fail',1,1,0,'inspection',NULL,NULL,NULL,210,'2026-07-11 13:11:17'),(458,14,'handle_operation_result','Handle Operation Result',NULL,NULL,'pass_fail',1,1,0,'inspection',NULL,NULL,NULL,220,'2026-07-11 13:11:17'),(459,15,'client_name','Client Name','Client or asset owner shown on the certificate.',NULL,'text',1,1,0,'summary',NULL,NULL,NULL,10,'2026-07-11 13:11:17'),(460,15,'client_address','Client Address','Site or office address where required by the certificate.',NULL,'textarea',0,1,0,'summary',NULL,NULL,NULL,20,'2026-07-11 13:11:17'),(461,15,'inspection_location','Inspection Location','Location where the examination was performed.',NULL,'text',0,1,0,'summary',NULL,NULL,NULL,30,'2026-07-11 13:11:17'),(462,15,'equipment_description','Equipment Description','Describe the inspected equipment as it should appear on the certificate.',NULL,'textarea',1,1,0,'equipment',NULL,NULL,NULL,40,'2026-07-11 13:11:17'),(463,15,'equipment_identifier','Equipment ID / Tag Number','Tag, asset number, serial or ID used by the client.',NULL,'text',1,1,0,'equipment',NULL,NULL,NULL,50,'2026-07-11 13:11:17'),(464,15,'manufacturer','Manufacturer','Original manufacturer where known.',NULL,'text',0,1,0,'equipment',NULL,NULL,NULL,60,'2026-07-11 13:11:17'),(465,15,'date_of_manufacture','Date of Manufacture','Manufacture date shown on the certificate where available.',NULL,'date',0,1,0,'equipment',NULL,NULL,NULL,70,'2026-07-11 13:11:17'),(466,15,'date_of_previous_examination','Date of Previous Examination','Previous thorough examination date.',NULL,'date',0,1,0,'examination',NULL,NULL,NULL,80,'2026-07-11 13:11:17'),(467,15,'date_of_current_examination','Date of Current Examination','Current examination date as shown on the PDF.',NULL,'date',1,1,0,'examination',NULL,NULL,NULL,90,'2026-07-11 13:11:17'),(468,15,'safe_working_load','SWL / WLL / Capacity','Rated safe working load, working load limit or capacity.',NULL,'text',0,1,0,'examination',NULL,NULL,NULL,100,'2026-07-11 13:11:17'),(469,15,'result_summary','Result Summary','Overall condition and decision statement.',NULL,'pass_fail',1,1,0,'decision',NULL,NULL,NULL,110,'2026-07-11 13:11:17'),(470,15,'defects_affecting_safety','Defects Affecting Safety','Defects found that affect safe use.',NULL,'textarea',0,1,0,'decision',NULL,NULL,NULL,120,'2026-07-11 13:11:17'),(471,15,'repairs_or_tests_required','Repairs / Tests Required','Repairs, further tests or conditions before return to service.',NULL,'textarea',0,1,0,'decision',NULL,NULL,NULL,130,'2026-07-11 13:11:17'),(472,15,'additional_remarks','Additional Remarks','Any extra remarks to appear on the certificate.',NULL,'textarea',0,1,0,'remarks',NULL,NULL,NULL,140,'2026-07-11 13:11:17'),(473,15,'inspector_name','Competent Person / Inspector Name',NULL,NULL,'text',1,1,0,'signoff',NULL,NULL,NULL,150,'2026-07-11 13:11:17'),(474,15,'inspector_qualification','Inspector Qualification',NULL,NULL,'text',0,1,0,'signoff',NULL,NULL,NULL,160,'2026-07-11 13:11:17'),(475,15,'next_due_date','Next Due Date',NULL,NULL,'date',1,1,0,'signoff',NULL,NULL,NULL,170,'2026-07-11 13:11:17'),(476,15,'sling_color_code','Color Code',NULL,NULL,'text',0,1,0,'equipment',NULL,NULL,NULL,180,'2026-07-11 13:11:17'),(477,15,'effective_length','Effective Length',NULL,NULL,'text',0,1,0,'equipment',NULL,NULL,NULL,190,'2026-07-11 13:11:17'),(478,15,'sling_width','Sling Width / Ply',NULL,NULL,'text',0,1,0,'equipment',NULL,NULL,NULL,200,'2026-07-11 13:11:17'),(479,15,'tag_present','Tag / Label Present',NULL,NULL,'checkbox',1,1,0,'inspection',NULL,NULL,NULL,210,'2026-07-11 13:11:17'),(480,15,'stitching_condition','Stitching Condition',NULL,NULL,'pass_fail',1,1,0,'inspection',NULL,NULL,NULL,220,'2026-07-11 13:11:17'),(481,16,'client_name','Client Name','Client or asset owner shown on the certificate.',NULL,'text',1,1,0,'summary',NULL,NULL,NULL,10,'2026-07-11 13:11:17'),(482,16,'client_address','Client Address','Site or office address where required by the certificate.',NULL,'textarea',0,1,0,'summary',NULL,NULL,NULL,20,'2026-07-11 13:11:17'),(483,16,'inspection_location','Inspection Location','Location where the examination was performed.',NULL,'text',0,1,0,'summary',NULL,NULL,NULL,30,'2026-07-11 13:11:17'),(484,16,'equipment_description','Equipment Description','Describe the inspected equipment as it should appear on the certificate.',NULL,'textarea',1,1,0,'equipment',NULL,NULL,NULL,40,'2026-07-11 13:11:17'),(485,16,'equipment_identifier','Equipment ID / Tag Number','Tag, asset number, serial or ID used by the client.',NULL,'text',1,1,0,'equipment',NULL,NULL,NULL,50,'2026-07-11 13:11:17'),(486,16,'manufacturer','Manufacturer','Original manufacturer where known.',NULL,'text',0,1,0,'equipment',NULL,NULL,NULL,60,'2026-07-11 13:11:17'),(487,16,'date_of_manufacture','Date of Manufacture','Manufacture date shown on the certificate where available.',NULL,'date',0,1,0,'equipment',NULL,NULL,NULL,70,'2026-07-11 13:11:17'),(488,16,'date_of_previous_examination','Date of Previous Examination','Previous thorough examination date.',NULL,'date',0,1,0,'examination',NULL,NULL,NULL,80,'2026-07-11 13:11:17'),(489,16,'date_of_current_examination','Date of Current Examination','Current examination date as shown on the PDF.',NULL,'date',1,1,0,'examination',NULL,NULL,NULL,90,'2026-07-11 13:11:17'),(490,16,'safe_working_load','SWL / WLL / Capacity','Rated safe working load, working load limit or capacity.',NULL,'text',0,1,0,'examination',NULL,NULL,NULL,100,'2026-07-11 13:11:17'),(491,16,'result_summary','Result Summary','Overall condition and decision statement.',NULL,'pass_fail',1,1,0,'decision',NULL,NULL,NULL,110,'2026-07-11 13:11:17'),(492,16,'defects_affecting_safety','Defects Affecting Safety','Defects found that affect safe use.',NULL,'textarea',0,1,0,'decision',NULL,NULL,NULL,120,'2026-07-11 13:11:17'),(493,16,'repairs_or_tests_required','Repairs / Tests Required','Repairs, further tests or conditions before return to service.',NULL,'textarea',0,1,0,'decision',NULL,NULL,NULL,130,'2026-07-11 13:11:17'),(494,16,'additional_remarks','Additional Remarks','Any extra remarks to appear on the certificate.',NULL,'textarea',0,1,0,'remarks',NULL,NULL,NULL,140,'2026-07-11 13:11:17'),(495,16,'inspector_name','Competent Person / Inspector Name',NULL,NULL,'text',1,1,0,'signoff',NULL,NULL,NULL,150,'2026-07-11 13:11:17'),(496,16,'inspector_qualification','Inspector Qualification',NULL,NULL,'text',0,1,0,'signoff',NULL,NULL,NULL,160,'2026-07-11 13:11:17'),(497,16,'next_due_date','Next Due Date',NULL,NULL,'date',1,1,0,'signoff',NULL,NULL,NULL,170,'2026-07-11 13:11:17'),(498,16,'sling_color_code','Color Code',NULL,NULL,'text',0,1,0,'equipment',NULL,NULL,NULL,180,'2026-07-11 13:11:17'),(499,16,'circumference','Circumference / Effective Length',NULL,NULL,'text',0,1,0,'equipment',NULL,NULL,NULL,190,'2026-07-11 13:11:17'),(500,16,'cover_condition','Cover Condition',NULL,NULL,'pass_fail',1,1,0,'inspection',NULL,NULL,NULL,200,'2026-07-11 13:11:17'),(501,16,'internal_yarn_condition','Internal Yarn Damage Signs',NULL,NULL,'pass_fail',0,1,0,'inspection',NULL,NULL,NULL,210,'2026-07-11 13:11:17'),(502,17,'client_name','Client Name','Client or asset owner shown on the certificate.',NULL,'text',1,1,0,'summary',NULL,NULL,NULL,10,'2026-07-11 13:11:17'),(503,17,'client_address','Client Address','Site or office address where required by the certificate.',NULL,'textarea',0,1,0,'summary',NULL,NULL,NULL,20,'2026-07-11 13:11:17'),(504,17,'inspection_location','Inspection Location','Location where the examination was performed.',NULL,'text',0,1,0,'summary',NULL,NULL,NULL,30,'2026-07-11 13:11:17'),(505,17,'equipment_description','Equipment Description','Describe the inspected equipment as it should appear on the certificate.',NULL,'textarea',1,1,0,'equipment',NULL,NULL,NULL,40,'2026-07-11 13:11:17'),(506,17,'equipment_identifier','Equipment ID / Tag Number','Tag, asset number, serial or ID used by the client.',NULL,'text',1,1,0,'equipment',NULL,NULL,NULL,50,'2026-07-11 13:11:17'),(507,17,'manufacturer','Manufacturer','Original manufacturer where known.',NULL,'text',0,1,0,'equipment',NULL,NULL,NULL,60,'2026-07-11 13:11:17'),(508,17,'date_of_manufacture','Date of Manufacture','Manufacture date shown on the certificate where available.',NULL,'date',0,1,0,'equipment',NULL,NULL,NULL,70,'2026-07-11 13:11:17'),(509,17,'date_of_previous_examination','Date of Previous Examination','Previous thorough examination date.',NULL,'date',0,1,0,'examination',NULL,NULL,NULL,80,'2026-07-11 13:11:17'),(510,17,'date_of_current_examination','Date of Current Examination','Current examination date as shown on the PDF.',NULL,'date',1,1,0,'examination',NULL,NULL,NULL,90,'2026-07-11 13:11:17'),(511,17,'safe_working_load','SWL / WLL / Capacity','Rated safe working load, working load limit or capacity.',NULL,'text',0,1,0,'examination',NULL,NULL,NULL,100,'2026-07-11 13:11:17'),(512,17,'result_summary','Result Summary','Overall condition and decision statement.',NULL,'pass_fail',1,1,0,'decision',NULL,NULL,NULL,110,'2026-07-11 13:11:17'),(513,17,'defects_affecting_safety','Defects Affecting Safety','Defects found that affect safe use.',NULL,'textarea',0,1,0,'decision',NULL,NULL,NULL,120,'2026-07-11 13:11:17'),(514,17,'repairs_or_tests_required','Repairs / Tests Required','Repairs, further tests or conditions before return to service.',NULL,'textarea',0,1,0,'decision',NULL,NULL,NULL,130,'2026-07-11 13:11:17'),(515,17,'additional_remarks','Additional Remarks','Any extra remarks to appear on the certificate.',NULL,'textarea',0,1,0,'remarks',NULL,NULL,NULL,140,'2026-07-11 13:11:17'),(516,17,'inspector_name','Competent Person / Inspector Name',NULL,NULL,'text',1,1,0,'signoff',NULL,NULL,NULL,150,'2026-07-11 13:11:17'),(517,17,'inspector_qualification','Inspector Qualification',NULL,NULL,'text',0,1,0,'signoff',NULL,NULL,NULL,160,'2026-07-11 13:11:17'),(518,17,'next_due_date','Next Due Date',NULL,NULL,'date',1,1,0,'signoff',NULL,NULL,NULL,170,'2026-07-11 13:11:17'),(519,17,'rope_diameter','Rope Diameter',NULL,NULL,'text',0,1,0,'equipment',NULL,NULL,NULL,180,'2026-07-11 13:11:17'),(520,17,'rope_construction','Rope Construction',NULL,NULL,'text',0,1,0,'equipment',NULL,NULL,NULL,190,'2026-07-11 13:11:17'),(521,17,'leg_count','Leg Count',NULL,NULL,'number',0,1,0,'equipment',NULL,NULL,NULL,200,'2026-07-11 13:11:17'),(522,17,'termination_type','Ferrule / Splice Type',NULL,NULL,'text',0,1,0,'equipment',NULL,NULL,NULL,210,'2026-07-11 13:11:17'),(523,18,'client_name','Client Name','Client or asset owner shown on the certificate.',NULL,'text',1,1,0,'summary',NULL,NULL,NULL,10,'2026-07-11 13:11:17'),(524,18,'client_address','Client Address','Site or office address where required by the certificate.',NULL,'textarea',0,1,0,'summary',NULL,NULL,NULL,20,'2026-07-11 13:11:17'),(525,18,'inspection_location','Inspection Location','Location where the examination was performed.',NULL,'text',0,1,0,'summary',NULL,NULL,NULL,30,'2026-07-11 13:11:17'),(526,18,'equipment_description','Equipment Description','Describe the inspected equipment as it should appear on the certificate.',NULL,'textarea',1,1,0,'equipment',NULL,NULL,NULL,40,'2026-07-11 13:11:17'),(527,18,'equipment_identifier','Equipment ID / Tag Number','Tag, asset number, serial or ID used by the client.',NULL,'text',1,1,0,'equipment',NULL,NULL,NULL,50,'2026-07-11 13:11:17'),(528,18,'manufacturer','Manufacturer','Original manufacturer where known.',NULL,'text',0,1,0,'equipment',NULL,NULL,NULL,60,'2026-07-11 13:11:17'),(529,18,'date_of_manufacture','Date of Manufacture','Manufacture date shown on the certificate where available.',NULL,'date',0,1,0,'equipment',NULL,NULL,NULL,70,'2026-07-11 13:11:17'),(530,18,'date_of_previous_examination','Date of Previous Examination','Previous thorough examination date.',NULL,'date',0,1,0,'examination',NULL,NULL,NULL,80,'2026-07-11 13:11:17'),(531,18,'date_of_current_examination','Date of Current Examination','Current examination date as shown on the PDF.',NULL,'date',1,1,0,'examination',NULL,NULL,NULL,90,'2026-07-11 13:11:17'),(532,18,'safe_working_load','SWL / WLL / Capacity','Rated safe working load, working load limit or capacity.',NULL,'text',0,1,0,'examination',NULL,NULL,NULL,100,'2026-07-11 13:11:17'),(533,18,'result_summary','Result Summary','Overall condition and decision statement.',NULL,'pass_fail',1,1,0,'decision',NULL,NULL,NULL,110,'2026-07-11 13:11:17'),(534,18,'defects_affecting_safety','Defects Affecting Safety','Defects found that affect safe use.',NULL,'textarea',0,1,0,'decision',NULL,NULL,NULL,120,'2026-07-11 13:11:17'),(535,18,'repairs_or_tests_required','Repairs / Tests Required','Repairs, further tests or conditions before return to service.',NULL,'textarea',0,1,0,'decision',NULL,NULL,NULL,130,'2026-07-11 13:11:17'),(536,18,'additional_remarks','Additional Remarks','Any extra remarks to appear on the certificate.',NULL,'textarea',0,1,0,'remarks',NULL,NULL,NULL,140,'2026-07-11 13:11:17'),(537,18,'inspector_name','Competent Person / Inspector Name',NULL,NULL,'text',1,1,0,'signoff',NULL,NULL,NULL,150,'2026-07-11 13:11:17'),(538,18,'inspector_qualification','Inspector Qualification',NULL,NULL,'text',0,1,0,'signoff',NULL,NULL,NULL,160,'2026-07-11 13:11:17'),(539,18,'next_due_date','Next Due Date',NULL,NULL,'date',1,1,0,'signoff',NULL,NULL,NULL,170,'2026-07-11 13:11:17'),(540,18,'accessory_type','Accessory Type',NULL,NULL,'text',1,1,0,'equipment',NULL,NULL,NULL,180,'2026-07-11 13:11:17'),(541,18,'size_marking','Size / Marking',NULL,NULL,'text',0,1,0,'equipment',NULL,NULL,NULL,190,'2026-07-11 13:11:17'),(542,18,'pin_condition','Pin / Thread Condition',NULL,NULL,'pass_fail',1,1,0,'inspection',NULL,NULL,NULL,200,'2026-07-11 13:11:17'),(543,18,'body_distortion_check','Body Distortion Check',NULL,NULL,'pass_fail',1,1,0,'inspection',NULL,NULL,NULL,210,'2026-07-11 13:11:17'),(544,19,'client_name','Client Name','Client or asset owner shown on the certificate.',NULL,'text',1,1,0,'summary',NULL,NULL,NULL,10,'2026-07-11 13:11:17'),(545,19,'client_address','Client Address','Site or office address where required by the certificate.',NULL,'textarea',0,1,0,'summary',NULL,NULL,NULL,20,'2026-07-11 13:11:17'),(546,19,'inspection_location','Inspection Location','Location where the examination was performed.',NULL,'text',0,1,0,'summary',NULL,NULL,NULL,30,'2026-07-11 13:11:17'),(547,19,'equipment_description','Equipment Description','Describe the inspected equipment as it should appear on the certificate.',NULL,'textarea',1,1,0,'equipment',NULL,NULL,NULL,40,'2026-07-11 13:11:17'),(548,19,'equipment_identifier','Equipment ID / Tag Number','Tag, asset number, serial or ID used by the client.',NULL,'text',1,1,0,'equipment',NULL,NULL,NULL,50,'2026-07-11 13:11:17'),(549,19,'manufacturer','Manufacturer','Original manufacturer where known.',NULL,'text',0,1,0,'equipment',NULL,NULL,NULL,60,'2026-07-11 13:11:17'),(550,19,'date_of_manufacture','Date of Manufacture','Manufacture date shown on the certificate where available.',NULL,'date',0,1,0,'equipment',NULL,NULL,NULL,70,'2026-07-11 13:11:17'),(551,19,'date_of_previous_examination','Date of Previous Examination','Previous thorough examination date.',NULL,'date',0,1,0,'examination',NULL,NULL,NULL,80,'2026-07-11 13:11:17'),(552,19,'date_of_current_examination','Date of Current Examination','Current examination date as shown on the PDF.',NULL,'date',1,1,0,'examination',NULL,NULL,NULL,90,'2026-07-11 13:11:17'),(553,19,'safe_working_load','SWL / WLL / Capacity','Rated safe working load, working load limit or capacity.',NULL,'text',0,1,0,'examination',NULL,NULL,NULL,100,'2026-07-11 13:11:17'),(554,19,'result_summary','Result Summary','Overall condition and decision statement.',NULL,'pass_fail',1,1,0,'decision',NULL,NULL,NULL,110,'2026-07-11 13:11:17'),(555,19,'defects_affecting_safety','Defects Affecting Safety','Defects found that affect safe use.',NULL,'textarea',0,1,0,'decision',NULL,NULL,NULL,120,'2026-07-11 13:11:17'),(556,19,'repairs_or_tests_required','Repairs / Tests Required','Repairs, further tests or conditions before return to service.',NULL,'textarea',0,1,0,'decision',NULL,NULL,NULL,130,'2026-07-11 13:11:17'),(557,19,'additional_remarks','Additional Remarks','Any extra remarks to appear on the certificate.',NULL,'textarea',0,1,0,'remarks',NULL,NULL,NULL,140,'2026-07-11 13:11:17'),(558,19,'inspector_name','Competent Person / Inspector Name',NULL,NULL,'text',1,1,0,'signoff',NULL,NULL,NULL,150,'2026-07-11 13:11:17'),(559,19,'inspector_qualification','Inspector Qualification',NULL,NULL,'text',0,1,0,'signoff',NULL,NULL,NULL,160,'2026-07-11 13:11:17'),(560,19,'next_due_date','Next Due Date',NULL,NULL,'date',1,1,0,'signoff',NULL,NULL,NULL,170,'2026-07-11 13:11:17'),(561,19,'thread_size','Thread Size',NULL,NULL,'text',0,1,0,'equipment',NULL,NULL,NULL,180,'2026-07-11 13:11:17'),(562,19,'shank_length','Shank Length',NULL,NULL,'text',0,1,0,'equipment',NULL,NULL,NULL,190,'2026-07-11 13:11:17'),(563,19,'shoulder_seating_condition','Shoulder Seating Condition',NULL,NULL,'pass_fail',1,1,0,'inspection',NULL,NULL,NULL,200,'2026-07-11 13:11:17'),(564,19,'thread_condition','Thread Condition',NULL,NULL,'pass_fail',1,1,0,'inspection',NULL,NULL,NULL,210,'2026-07-11 13:11:17'),(565,20,'client_name','Client Name','Client or asset owner shown on the certificate.',NULL,'text',1,1,0,'summary',NULL,NULL,NULL,10,'2026-07-11 13:11:17'),(566,20,'client_address','Client Address','Site or office address where required by the certificate.',NULL,'textarea',0,1,0,'summary',NULL,NULL,NULL,20,'2026-07-11 13:11:17'),(567,20,'inspection_location','Inspection Location','Location where the examination was performed.',NULL,'text',0,1,0,'summary',NULL,NULL,NULL,30,'2026-07-11 13:11:17'),(568,20,'equipment_description','Equipment Description','Describe the inspected equipment as it should appear on the certificate.',NULL,'textarea',1,1,0,'equipment',NULL,NULL,NULL,40,'2026-07-11 13:11:17'),(569,20,'equipment_identifier','Equipment ID / Tag Number','Tag, asset number, serial or ID used by the client.',NULL,'text',1,1,0,'equipment',NULL,NULL,NULL,50,'2026-07-11 13:11:17'),(570,20,'manufacturer','Manufacturer','Original manufacturer where known.',NULL,'text',0,1,0,'equipment',NULL,NULL,NULL,60,'2026-07-11 13:11:17'),(571,20,'date_of_manufacture','Date of Manufacture','Manufacture date shown on the certificate where available.',NULL,'date',0,1,0,'equipment',NULL,NULL,NULL,70,'2026-07-11 13:11:17'),(572,20,'date_of_previous_examination','Date of Previous Examination','Previous thorough examination date.',NULL,'date',0,1,0,'examination',NULL,NULL,NULL,80,'2026-07-11 13:11:18'),(573,20,'date_of_current_examination','Date of Current Examination','Current examination date as shown on the PDF.',NULL,'date',1,1,0,'examination',NULL,NULL,NULL,90,'2026-07-11 13:11:18'),(574,20,'safe_working_load','SWL / WLL / Capacity','Rated safe working load, working load limit or capacity.',NULL,'text',0,1,0,'examination',NULL,NULL,NULL,100,'2026-07-11 13:11:18'),(575,20,'result_summary','Result Summary','Overall condition and decision statement.',NULL,'pass_fail',1,1,0,'decision',NULL,NULL,NULL,110,'2026-07-11 13:11:18'),(576,20,'defects_affecting_safety','Defects Affecting Safety','Defects found that affect safe use.',NULL,'textarea',0,1,0,'decision',NULL,NULL,NULL,120,'2026-07-11 13:11:18'),(577,20,'repairs_or_tests_required','Repairs / Tests Required','Repairs, further tests or conditions before return to service.',NULL,'textarea',0,1,0,'decision',NULL,NULL,NULL,130,'2026-07-11 13:11:18'),(578,20,'additional_remarks','Additional Remarks','Any extra remarks to appear on the certificate.',NULL,'textarea',0,1,0,'remarks',NULL,NULL,NULL,140,'2026-07-11 13:11:18'),(579,20,'inspector_name','Competent Person / Inspector Name',NULL,NULL,'text',1,1,0,'signoff',NULL,NULL,NULL,150,'2026-07-11 13:11:18'),(580,20,'inspector_qualification','Inspector Qualification',NULL,NULL,'text',0,1,0,'signoff',NULL,NULL,NULL,160,'2026-07-11 13:11:18'),(581,20,'next_due_date','Next Due Date',NULL,NULL,'date',1,1,0,'signoff',NULL,NULL,NULL,170,'2026-07-11 13:11:18'),(582,20,'hook_type','Hook Type',NULL,NULL,'text',0,1,0,'equipment',NULL,NULL,NULL,180,'2026-07-11 13:11:18'),(583,20,'throat_opening','Throat Opening',NULL,NULL,'text',0,1,0,'equipment',NULL,NULL,NULL,190,'2026-07-11 13:11:18'),(584,20,'latch_condition','Latch Condition',NULL,NULL,'pass_fail',1,1,0,'inspection',NULL,NULL,NULL,200,'2026-07-11 13:11:18'),(585,20,'wear_distortion_check','Wear / Distortion Check',NULL,NULL,'pass_fail',1,1,0,'inspection',NULL,NULL,NULL,210,'2026-07-11 13:11:18'),(586,21,'client_name','Client Name','Client or asset owner shown on the certificate.',NULL,'text',1,1,0,'summary',NULL,NULL,NULL,10,'2026-07-11 13:11:18'),(587,21,'client_address','Client Address','Site or office address where required by the certificate.',NULL,'textarea',0,1,0,'summary',NULL,NULL,NULL,20,'2026-07-11 13:11:18'),(588,21,'inspection_location','Inspection Location','Location where the examination was performed.',NULL,'text',0,1,0,'summary',NULL,NULL,NULL,30,'2026-07-11 13:11:18'),(589,21,'equipment_description','Equipment Description','Describe the inspected equipment as it should appear on the certificate.',NULL,'textarea',1,1,0,'equipment',NULL,NULL,NULL,40,'2026-07-11 13:11:18'),(590,21,'equipment_identifier','Equipment ID / Tag Number','Tag, asset number, serial or ID used by the client.',NULL,'text',1,1,0,'equipment',NULL,NULL,NULL,50,'2026-07-11 13:11:18'),(591,21,'manufacturer','Manufacturer','Original manufacturer where known.',NULL,'text',0,1,0,'equipment',NULL,NULL,NULL,60,'2026-07-11 13:11:18'),(592,21,'date_of_manufacture','Date of Manufacture','Manufacture date shown on the certificate where available.',NULL,'date',0,1,0,'equipment',NULL,NULL,NULL,70,'2026-07-11 13:11:18'),(593,21,'date_of_previous_examination','Date of Previous Examination','Previous thorough examination date.',NULL,'date',0,1,0,'examination',NULL,NULL,NULL,80,'2026-07-11 13:11:18'),(594,21,'date_of_current_examination','Date of Current Examination','Current examination date as shown on the PDF.',NULL,'date',1,1,0,'examination',NULL,NULL,NULL,90,'2026-07-11 13:11:18'),(595,21,'safe_working_load','SWL / WLL / Capacity','Rated safe working load, working load limit or capacity.',NULL,'text',0,1,0,'examination',NULL,NULL,NULL,100,'2026-07-11 13:11:18'),(596,21,'result_summary','Result Summary','Overall condition and decision statement.',NULL,'pass_fail',1,1,0,'decision',NULL,NULL,NULL,110,'2026-07-11 13:11:18'),(597,21,'defects_affecting_safety','Defects Affecting Safety','Defects found that affect safe use.',NULL,'textarea',0,1,0,'decision',NULL,NULL,NULL,120,'2026-07-11 13:11:18'),(598,21,'repairs_or_tests_required','Repairs / Tests Required','Repairs, further tests or conditions before return to service.',NULL,'textarea',0,1,0,'decision',NULL,NULL,NULL,130,'2026-07-11 13:11:18'),(599,21,'additional_remarks','Additional Remarks','Any extra remarks to appear on the certificate.',NULL,'textarea',0,1,0,'remarks',NULL,NULL,NULL,140,'2026-07-11 13:11:18'),(600,21,'inspector_name','Competent Person / Inspector Name',NULL,NULL,'text',1,1,0,'signoff',NULL,NULL,NULL,150,'2026-07-11 13:11:18'),(601,21,'inspector_qualification','Inspector Qualification',NULL,NULL,'text',0,1,0,'signoff',NULL,NULL,NULL,160,'2026-07-11 13:11:18'),(602,21,'next_due_date','Next Due Date',NULL,NULL,'date',1,1,0,'signoff',NULL,NULL,NULL,170,'2026-07-11 13:11:18'),(603,21,'jaw_opening_range','Jaw Opening Range',NULL,NULL,'text',0,1,0,'equipment',NULL,NULL,NULL,180,'2026-07-11 13:11:18'),(604,21,'plate_thickness_range','Plate Thickness Range',NULL,NULL,'text',0,1,0,'equipment',NULL,NULL,NULL,190,'2026-07-11 13:11:18'),(605,21,'lock_condition','Locking Mechanism Condition',NULL,NULL,'pass_fail',1,1,0,'inspection',NULL,NULL,NULL,200,'2026-07-11 13:11:18'),(606,22,'client_name','Client Name','Client or asset owner shown on the certificate.',NULL,'text',1,1,0,'summary',NULL,NULL,NULL,10,'2026-07-11 13:11:18'),(607,22,'client_address','Client Address','Site or office address where required by the certificate.',NULL,'textarea',0,1,0,'summary',NULL,NULL,NULL,20,'2026-07-11 13:11:18'),(608,22,'inspection_location','Inspection Location','Location where the examination was performed.',NULL,'text',0,1,0,'summary',NULL,NULL,NULL,30,'2026-07-11 13:11:18'),(609,22,'equipment_description','Equipment Description','Describe the inspected equipment as it should appear on the certificate.',NULL,'textarea',1,1,0,'equipment',NULL,NULL,NULL,40,'2026-07-11 13:11:18'),(610,22,'equipment_identifier','Equipment ID / Tag Number','Tag, asset number, serial or ID used by the client.',NULL,'text',1,1,0,'equipment',NULL,NULL,NULL,50,'2026-07-11 13:11:18'),(611,22,'manufacturer','Manufacturer','Original manufacturer where known.',NULL,'text',0,1,0,'equipment',NULL,NULL,NULL,60,'2026-07-11 13:11:18'),(612,22,'date_of_manufacture','Date of Manufacture','Manufacture date shown on the certificate where available.',NULL,'date',0,1,0,'equipment',NULL,NULL,NULL,70,'2026-07-11 13:11:18'),(613,22,'date_of_previous_examination','Date of Previous Examination','Previous thorough examination date.',NULL,'date',0,1,0,'examination',NULL,NULL,NULL,80,'2026-07-11 13:11:18'),(614,22,'date_of_current_examination','Date of Current Examination','Current examination date as shown on the PDF.',NULL,'date',1,1,0,'examination',NULL,NULL,NULL,90,'2026-07-11 13:11:18'),(615,22,'safe_working_load','SWL / WLL / Capacity','Rated safe working load, working load limit or capacity.',NULL,'text',0,1,0,'examination',NULL,NULL,NULL,100,'2026-07-11 13:11:18'),(616,22,'result_summary','Result Summary','Overall condition and decision statement.',NULL,'pass_fail',1,1,0,'decision',NULL,NULL,NULL,110,'2026-07-11 13:11:18'),(617,22,'defects_affecting_safety','Defects Affecting Safety','Defects found that affect safe use.',NULL,'textarea',0,1,0,'decision',NULL,NULL,NULL,120,'2026-07-11 13:11:18'),(618,22,'repairs_or_tests_required','Repairs / Tests Required','Repairs, further tests or conditions before return to service.',NULL,'textarea',0,1,0,'decision',NULL,NULL,NULL,130,'2026-07-11 13:11:18'),(619,22,'additional_remarks','Additional Remarks','Any extra remarks to appear on the certificate.',NULL,'textarea',0,1,0,'remarks',NULL,NULL,NULL,140,'2026-07-11 13:11:18'),(620,22,'inspector_name','Competent Person / Inspector Name',NULL,NULL,'text',1,1,0,'signoff',NULL,NULL,NULL,150,'2026-07-11 13:11:18'),(621,22,'inspector_qualification','Inspector Qualification',NULL,NULL,'text',0,1,0,'signoff',NULL,NULL,NULL,160,'2026-07-11 13:11:18'),(622,22,'next_due_date','Next Due Date',NULL,NULL,'date',1,1,0,'signoff',NULL,NULL,NULL,170,'2026-07-11 13:11:18'),(623,22,'jaw_opening_range','Jaw Opening Range',NULL,NULL,'text',0,1,0,'equipment',NULL,NULL,NULL,180,'2026-07-11 13:11:18'),(624,22,'lock_condition','Locking Mechanism Condition',NULL,NULL,'pass_fail',1,1,0,'inspection',NULL,NULL,NULL,190,'2026-07-11 13:11:18'),(625,22,'jaw_tooth_condition','Jaw / Tooth Condition',NULL,NULL,'pass_fail',1,1,0,'inspection',NULL,NULL,NULL,200,'2026-07-11 13:11:18'),(626,28,'client_name','Client Name','Client or asset owner shown on the certificate.',NULL,'text',1,1,0,'summary',NULL,NULL,NULL,10,'2026-07-11 13:11:18'),(627,28,'client_address','Client Address','Site or office address where required by the certificate.',NULL,'textarea',0,1,0,'summary',NULL,NULL,NULL,20,'2026-07-11 13:11:18'),(628,28,'inspection_location','Inspection Location','Location where the examination was performed.',NULL,'text',0,1,0,'summary',NULL,NULL,NULL,30,'2026-07-11 13:11:18'),(629,28,'equipment_description','Equipment Description','Describe the inspected equipment as it should appear on the certificate.',NULL,'textarea',1,1,0,'equipment',NULL,NULL,NULL,40,'2026-07-11 13:11:18'),(630,28,'equipment_identifier','Equipment ID / Tag Number','Tag, asset number, serial or ID used by the client.',NULL,'text',1,1,0,'equipment',NULL,NULL,NULL,50,'2026-07-11 13:11:18'),(631,28,'manufacturer','Manufacturer','Original manufacturer where known.',NULL,'text',0,1,0,'equipment',NULL,NULL,NULL,60,'2026-07-11 13:11:18'),(632,28,'date_of_manufacture','Date of Manufacture','Manufacture date shown on the certificate where available.',NULL,'date',0,1,0,'equipment',NULL,NULL,NULL,70,'2026-07-11 13:11:18'),(633,28,'date_of_previous_examination','Date of Previous Examination','Previous thorough examination date.',NULL,'date',0,1,0,'examination',NULL,NULL,NULL,80,'2026-07-11 13:11:18'),(634,28,'date_of_current_examination','Date of Current Examination','Current examination date as shown on the PDF.',NULL,'date',1,1,0,'examination',NULL,NULL,NULL,90,'2026-07-11 13:11:18'),(635,28,'safe_working_load','SWL / WLL / Capacity','Rated safe working load, working load limit or capacity.',NULL,'text',0,1,0,'examination',NULL,NULL,NULL,100,'2026-07-11 13:11:18'),(636,28,'result_summary','Result Summary','Overall condition and decision statement.',NULL,'pass_fail',1,1,0,'decision',NULL,NULL,NULL,110,'2026-07-11 13:11:18'),(637,28,'defects_affecting_safety','Defects Affecting Safety','Defects found that affect safe use.',NULL,'textarea',0,1,0,'decision',NULL,NULL,NULL,120,'2026-07-11 13:11:18'),(638,28,'repairs_or_tests_required','Repairs / Tests Required','Repairs, further tests or conditions before return to service.',NULL,'textarea',0,1,0,'decision',NULL,NULL,NULL,130,'2026-07-11 13:11:18'),(639,28,'additional_remarks','Additional Remarks','Any extra remarks to appear on the certificate.',NULL,'textarea',0,1,0,'remarks',NULL,NULL,NULL,140,'2026-07-11 13:11:18'),(640,28,'inspector_name','Competent Person / Inspector Name',NULL,NULL,'text',1,1,0,'signoff',NULL,NULL,NULL,150,'2026-07-11 13:11:18'),(641,28,'inspector_qualification','Inspector Qualification',NULL,NULL,'text',0,1,0,'signoff',NULL,NULL,NULL,160,'2026-07-11 13:11:18'),(642,28,'next_due_date','Next Due Date',NULL,NULL,'date',1,1,0,'signoff',NULL,NULL,NULL,170,'2026-07-11 13:11:18'),(643,28,'jaw_opening_range','Jaw Opening Range',NULL,NULL,'text',0,1,0,'equipment',NULL,NULL,NULL,180,'2026-07-11 13:11:18'),(644,28,'lock_condition','Locking Mechanism Condition',NULL,NULL,'pass_fail',1,1,0,'inspection',NULL,NULL,NULL,190,'2026-07-11 13:11:18'),(645,28,'body_condition','Body Condition',NULL,NULL,'pass_fail',1,1,0,'inspection',NULL,NULL,NULL,200,'2026-07-11 13:11:18'),(646,24,'client_name','Client Name','Client or asset owner shown on the certificate.',NULL,'text',1,1,0,'summary',NULL,NULL,NULL,10,'2026-07-11 13:11:18'),(647,24,'client_address','Client Address','Site or office address where required by the certificate.',NULL,'textarea',0,1,0,'summary',NULL,NULL,NULL,20,'2026-07-11 13:11:18'),(648,24,'inspection_location','Inspection Location','Location where the examination was performed.',NULL,'text',0,1,0,'summary',NULL,NULL,NULL,30,'2026-07-11 13:11:18'),(649,24,'equipment_description','Equipment Description','Describe the inspected equipment as it should appear on the certificate.',NULL,'textarea',1,1,0,'equipment',NULL,NULL,NULL,40,'2026-07-11 13:11:18'),(650,24,'equipment_identifier','Equipment ID / Tag Number','Tag, asset number, serial or ID used by the client.',NULL,'text',1,1,0,'equipment',NULL,NULL,NULL,50,'2026-07-11 13:11:18'),(651,24,'manufacturer','Manufacturer','Original manufacturer where known.',NULL,'text',0,1,0,'equipment',NULL,NULL,NULL,60,'2026-07-11 13:11:18'),(652,24,'date_of_manufacture','Date of Manufacture','Manufacture date shown on the certificate where available.',NULL,'date',0,1,0,'equipment',NULL,NULL,NULL,70,'2026-07-11 13:11:18'),(653,24,'date_of_previous_examination','Date of Previous Examination','Previous thorough examination date.',NULL,'date',0,1,0,'examination',NULL,NULL,NULL,80,'2026-07-11 13:11:18'),(654,24,'date_of_current_examination','Date of Current Examination','Current examination date as shown on the PDF.',NULL,'date',1,1,0,'examination',NULL,NULL,NULL,90,'2026-07-11 13:11:18'),(655,24,'safe_working_load','SWL / WLL / Capacity','Rated safe working load, working load limit or capacity.',NULL,'text',0,1,0,'examination',NULL,NULL,NULL,100,'2026-07-11 13:11:18'),(656,24,'result_summary','Result Summary','Overall condition and decision statement.',NULL,'pass_fail',1,1,0,'decision',NULL,NULL,NULL,110,'2026-07-11 13:11:18'),(657,24,'defects_affecting_safety','Defects Affecting Safety','Defects found that affect safe use.',NULL,'textarea',0,1,0,'decision',NULL,NULL,NULL,120,'2026-07-11 13:11:18'),(658,24,'repairs_or_tests_required','Repairs / Tests Required','Repairs, further tests or conditions before return to service.',NULL,'textarea',0,1,0,'decision',NULL,NULL,NULL,130,'2026-07-11 13:11:18'),(659,24,'additional_remarks','Additional Remarks','Any extra remarks to appear on the certificate.',NULL,'textarea',0,1,0,'remarks',NULL,NULL,NULL,140,'2026-07-11 13:11:18'),(660,24,'inspector_name','Competent Person / Inspector Name',NULL,NULL,'text',1,1,0,'signoff',NULL,NULL,NULL,150,'2026-07-11 13:11:18'),(661,24,'inspector_qualification','Inspector Qualification',NULL,NULL,'text',0,1,0,'signoff',NULL,NULL,NULL,160,'2026-07-11 13:11:18'),(662,24,'next_due_date','Next Due Date',NULL,NULL,'date',1,1,0,'signoff',NULL,NULL,NULL,170,'2026-07-11 13:11:18'),(663,24,'rated_capacity','Rated Capacity',NULL,NULL,'text',1,1,0,'equipment',NULL,NULL,NULL,180,'2026-07-11 13:11:18'),(664,24,'contact_face_condition','Contact Face Condition',NULL,NULL,'pass_fail',1,1,0,'inspection',NULL,NULL,NULL,190,'2026-07-11 13:11:18'),(665,24,'holding_test_result','Holding / Proof Test Result',NULL,NULL,'pass_fail',1,1,0,'inspection',NULL,NULL,NULL,200,'2026-07-11 13:11:18'),(666,25,'client_name','Client Name','Client or asset owner shown on the certificate.',NULL,'text',1,1,0,'summary',NULL,NULL,NULL,10,'2026-07-11 13:11:18'),(667,25,'client_address','Client Address','Site or office address where required by the certificate.',NULL,'textarea',0,1,0,'summary',NULL,NULL,NULL,20,'2026-07-11 13:11:18'),(668,25,'inspection_location','Inspection Location','Location where the examination was performed.',NULL,'text',0,1,0,'summary',NULL,NULL,NULL,30,'2026-07-11 13:11:18'),(669,25,'equipment_description','Equipment Description','Describe the inspected equipment as it should appear on the certificate.',NULL,'textarea',1,1,0,'equipment',NULL,NULL,NULL,40,'2026-07-11 13:11:18'),(670,25,'equipment_identifier','Equipment ID / Tag Number','Tag, asset number, serial or ID used by the client.',NULL,'text',1,1,0,'equipment',NULL,NULL,NULL,50,'2026-07-11 13:11:18'),(671,25,'manufacturer','Manufacturer','Original manufacturer where known.',NULL,'text',0,1,0,'equipment',NULL,NULL,NULL,60,'2026-07-11 13:11:18'),(672,25,'date_of_manufacture','Date of Manufacture','Manufacture date shown on the certificate where available.',NULL,'date',0,1,0,'equipment',NULL,NULL,NULL,70,'2026-07-11 13:11:18'),(673,25,'date_of_previous_examination','Date of Previous Examination','Previous thorough examination date.',NULL,'date',0,1,0,'examination',NULL,NULL,NULL,80,'2026-07-11 13:11:18'),(674,25,'date_of_current_examination','Date of Current Examination','Current examination date as shown on the PDF.',NULL,'date',1,1,0,'examination',NULL,NULL,NULL,90,'2026-07-11 13:11:18'),(675,25,'safe_working_load','SWL / WLL / Capacity','Rated safe working load, working load limit or capacity.',NULL,'text',0,1,0,'examination',NULL,NULL,NULL,100,'2026-07-11 13:11:18'),(676,25,'result_summary','Result Summary','Overall condition and decision statement.',NULL,'pass_fail',1,1,0,'decision',NULL,NULL,NULL,110,'2026-07-11 13:11:18'),(677,25,'defects_affecting_safety','Defects Affecting Safety','Defects found that affect safe use.',NULL,'textarea',0,1,0,'decision',NULL,NULL,NULL,120,'2026-07-11 13:11:18'),(678,25,'repairs_or_tests_required','Repairs / Tests Required','Repairs, further tests or conditions before return to service.',NULL,'textarea',0,1,0,'decision',NULL,NULL,NULL,130,'2026-07-11 13:11:18'),(679,25,'additional_remarks','Additional Remarks','Any extra remarks to appear on the certificate.',NULL,'textarea',0,1,0,'remarks',NULL,NULL,NULL,140,'2026-07-11 13:11:19'),(680,25,'inspector_name','Competent Person / Inspector Name',NULL,NULL,'text',1,1,0,'signoff',NULL,NULL,NULL,150,'2026-07-11 13:11:19'),(681,25,'inspector_qualification','Inspector Qualification',NULL,NULL,'text',0,1,0,'signoff',NULL,NULL,NULL,160,'2026-07-11 13:11:19'),(682,25,'next_due_date','Next Due Date',NULL,NULL,'date',1,1,0,'signoff',NULL,NULL,NULL,170,'2026-07-11 13:11:19'),(683,25,'fork_length','Fork Length',NULL,NULL,'text',0,1,0,'equipment',NULL,NULL,NULL,180,'2026-07-11 13:11:19'),(684,25,'hydraulic_condition','Hydraulic Condition',NULL,NULL,'pass_fail',1,1,0,'inspection',NULL,NULL,NULL,190,'2026-07-11 13:11:19'),(685,25,'wheel_roller_condition','Wheel / Roller Condition',NULL,NULL,'pass_fail',1,1,0,'inspection',NULL,NULL,NULL,200,'2026-07-11 13:11:19'),(686,25,'functional_test_result','Functional Test Result',NULL,NULL,'pass_fail',1,1,0,'inspection',NULL,NULL,NULL,210,'2026-07-11 13:11:19'),(687,26,'client_name','Client Name','Client or asset owner shown on the certificate.',NULL,'text',1,1,0,'summary',NULL,NULL,NULL,10,'2026-07-11 13:11:19'),(688,26,'client_address','Client Address','Site or office address where required by the certificate.',NULL,'textarea',0,1,0,'summary',NULL,NULL,NULL,20,'2026-07-11 13:11:19'),(689,26,'inspection_location','Inspection Location','Location where the examination was performed.',NULL,'text',0,1,0,'summary',NULL,NULL,NULL,30,'2026-07-11 13:11:19'),(690,26,'equipment_description','Equipment Description','Describe the inspected equipment as it should appear on the certificate.',NULL,'textarea',1,1,0,'equipment',NULL,NULL,NULL,40,'2026-07-11 13:11:19'),(691,26,'equipment_identifier','Equipment ID / Tag Number','Tag, asset number, serial or ID used by the client.',NULL,'text',1,1,0,'equipment',NULL,NULL,NULL,50,'2026-07-11 13:11:19'),(692,26,'manufacturer','Manufacturer','Original manufacturer where known.',NULL,'text',0,1,0,'equipment',NULL,NULL,NULL,60,'2026-07-11 13:11:19'),(693,26,'date_of_manufacture','Date of Manufacture','Manufacture date shown on the certificate where available.',NULL,'date',0,1,0,'equipment',NULL,NULL,NULL,70,'2026-07-11 13:11:19'),(694,26,'date_of_previous_examination','Date of Previous Examination','Previous thorough examination date.',NULL,'date',0,1,0,'examination',NULL,NULL,NULL,80,'2026-07-11 13:11:19'),(695,26,'date_of_current_examination','Date of Current Examination','Current examination date as shown on the PDF.',NULL,'date',1,1,0,'examination',NULL,NULL,NULL,90,'2026-07-11 13:11:19'),(696,26,'safe_working_load','SWL / WLL / Capacity','Rated safe working load, working load limit or capacity.',NULL,'text',0,1,0,'examination',NULL,NULL,NULL,100,'2026-07-11 13:11:19'),(697,26,'result_summary','Result Summary','Overall condition and decision statement.',NULL,'pass_fail',1,1,0,'decision',NULL,NULL,NULL,110,'2026-07-11 13:11:19'),(698,26,'defects_affecting_safety','Defects Affecting Safety','Defects found that affect safe use.',NULL,'textarea',0,1,0,'decision',NULL,NULL,NULL,120,'2026-07-11 13:11:19'),(699,26,'repairs_or_tests_required','Repairs / Tests Required','Repairs, further tests or conditions before return to service.',NULL,'textarea',0,1,0,'decision',NULL,NULL,NULL,130,'2026-07-11 13:11:19'),(700,26,'additional_remarks','Additional Remarks','Any extra remarks to appear on the certificate.',NULL,'textarea',0,1,0,'remarks',NULL,NULL,NULL,140,'2026-07-11 13:11:19'),(701,26,'inspector_name','Competent Person / Inspector Name',NULL,NULL,'text',1,1,0,'signoff',NULL,NULL,NULL,150,'2026-07-11 13:11:19'),(702,26,'inspector_qualification','Inspector Qualification',NULL,NULL,'text',0,1,0,'signoff',NULL,NULL,NULL,160,'2026-07-11 13:11:19'),(703,26,'next_due_date','Next Due Date',NULL,NULL,'date',1,1,0,'signoff',NULL,NULL,NULL,170,'2026-07-11 13:11:19'),(704,26,'drawing_reference','Drawing / Reference Number',NULL,NULL,'text',0,1,0,'equipment',NULL,NULL,NULL,180,'2026-07-11 13:11:19'),(705,26,'inspection_standard','Inspection Standard',NULL,NULL,'text',1,1,0,'inspection',NULL,NULL,NULL,190,'2026-07-11 13:11:19'),(706,26,'ndt_method','NDT Method',NULL,NULL,'select',1,1,0,'inspection',NULL,'[\"MPI\",\"DPI\",\"UT\",\"VT\"]',NULL,200,'2026-07-11 13:11:19'),(707,26,'acceptance_criteria','Acceptance Criteria',NULL,NULL,'textarea',0,1,0,'inspection',NULL,NULL,NULL,210,'2026-07-11 13:11:19'),(708,29,'report_date','Date of Report','Defaults to issue date when left blank.',NULL,'date',0,1,0,'certificate',NULL,NULL,NULL,10,'2026-07-13 07:30:59'),(709,29,'premises_address','Address of Premises Where Examination Was Made',NULL,NULL,'textarea',1,1,0,'client',NULL,NULL,NULL,20,'2026-07-13 07:30:59'),(710,29,'equipment_type','Equipment','Normally CHAIN BLOCK for this category.',NULL,'text',1,1,0,'equipment',NULL,NULL,NULL,30,'2026-07-13 07:30:59'),(711,29,'equipment_description','Description',NULL,NULL,'textarea',1,1,0,'equipment',NULL,NULL,NULL,40,'2026-07-13 07:30:59'),(712,29,'manufacturer','Manufacturer',NULL,NULL,'text',0,1,0,'equipment',NULL,NULL,NULL,50,'2026-07-13 07:30:59'),(713,29,'equipment_id_number','ID Number','Defaults to the selected equipment asset code.',NULL,'text',0,1,0,'equipment',NULL,NULL,NULL,60,'2026-07-13 07:30:59'),(714,29,'asset_number','Asset Number',NULL,NULL,'text',0,1,0,'equipment',NULL,NULL,NULL,70,'2026-07-13 07:30:59'),(715,29,'chain_dimensions','Chain Dimensions',NULL,NULL,'text',1,1,0,'equipment',NULL,NULL,NULL,80,'2026-07-13 07:30:59'),(716,29,'standard','Standard',NULL,NULL,'text',1,1,0,'equipment',NULL,NULL,NULL,90,'2026-07-13 07:30:59'),(717,29,'safe_working_load','Safe Working Load',NULL,NULL,'text',1,1,0,'equipment',NULL,NULL,NULL,100,'2026-07-13 07:30:59'),(718,29,'date_of_manufacture','Date of Manufacture',NULL,NULL,'date',0,1,0,'equipment',NULL,NULL,NULL,110,'2026-07-13 07:30:59'),(719,29,'date_of_last_examination','Date of Last Thorough Examination',NULL,NULL,'date',1,1,0,'equipment',NULL,NULL,NULL,120,'2026-07-13 07:30:59'),(720,29,'first_examination_new_site','First Examination After Installation or Assembly at a New Site?',NULL,NULL,'select',1,1,0,'checklist',NULL,'[\"Yes\",\"No\"]',NULL,200,'2026-07-13 07:30:59'),(721,29,'installed_correctly','If Yes, Has the Equipment Been Installed Correctly?',NULL,NULL,'select',1,1,0,'checklist',NULL,'[\"Yes\",\"No\"]',NULL,210,'2026-07-13 07:30:59'),(722,29,'exam_within_6_months','Examination Carried Out Within 6 Months?',NULL,NULL,'select',1,1,0,'checklist',NULL,'[\"Yes\",\"No\"]',NULL,220,'2026-07-13 07:30:59'),(723,29,'exam_within_12_months','Examination Carried Out Within 12 Months?',NULL,NULL,'select',1,1,0,'checklist',NULL,'[\"Yes\",\"No\"]',NULL,230,'2026-07-13 07:30:59'),(724,29,'exam_scheme','Examination Carried Out in Accordance With an Examination Scheme?',NULL,NULL,'select',1,1,0,'checklist',NULL,'[\"Yes\",\"No\"]',NULL,240,'2026-07-13 07:30:59'),(725,29,'exceptional_circumstances','Examination Carried Out After Exceptional Circumstances?',NULL,NULL,'select',1,1,0,'checklist',NULL,'[\"Yes\",\"No\"]',NULL,250,'2026-07-13 07:30:59'),(726,29,'defect_description','Identification and Description of Defects','Enter NONE when no defects were found.',NULL,'textarea',1,1,0,'defects',NULL,NULL,NULL,300,'2026-07-13 07:30:59'),(727,29,'defect_immediate_danger','Is the Defect of Immediate Danger?',NULL,NULL,'select',0,1,0,'defects',NULL,'[\"Yes\",\"No\"]',NULL,310,'2026-07-13 07:30:59'),(728,29,'defect_future_danger','Could the Defect Become Dangerous?',NULL,NULL,'select',0,1,0,'defects',NULL,'[\"Yes\",\"No\"]',NULL,320,'2026-07-13 07:30:59'),(729,29,'action_due_date','Date by Which Action Must Be Taken',NULL,NULL,'date',0,1,0,'defects',NULL,NULL,NULL,330,'2026-07-13 07:30:59'),(730,29,'repair_required','Repair, Renewal or Alteration Required',NULL,NULL,'textarea',0,1,0,'defects',NULL,NULL,NULL,340,'2026-07-13 07:30:59'),(731,29,'tests_carried_out','Tests Carried Out',NULL,NULL,'textarea',1,1,0,'defects',NULL,NULL,NULL,350,'2026-07-13 07:30:59'),(732,29,'observations','Observations / Additional Comments',NULL,NULL,'textarea',0,1,0,'defects',NULL,NULL,NULL,360,'2026-07-13 07:30:59'),(733,29,'fit_for_purpose','Is This Equipment Fit for Purpose?',NULL,NULL,'select',1,1,0,'fitness',NULL,'[\"Yes\",\"No\"]',NULL,400,'2026-07-13 07:30:59'),(734,29,'inspector_name_snapshot','Inspector Name on Certificate','Defaults to the selected inspector.',NULL,'text',0,1,0,'signoff',NULL,NULL,NULL,500,'2026-07-13 07:30:59'),(735,29,'inspector_qualification_snapshot','Inspector Qualifications on Certificate','Defaults to the selected inspector profile.',NULL,'text',0,1,0,'signoff',NULL,NULL,NULL,510,'2026-07-13 07:30:59'),(736,29,'inspector_signature_name','Inspector Signature / Signatory Mark',NULL,NULL,'text',1,1,0,'signoff',NULL,NULL,NULL,520,'2026-07-13 07:30:59'),(737,29,'authenticator_name','Authenticator Name',NULL,NULL,'text',1,1,0,'signoff',NULL,NULL,NULL,530,'2026-07-13 07:30:59'),(738,29,'authenticator_qualification','Authenticator Qualifications',NULL,NULL,'text',1,1,0,'signoff',NULL,NULL,NULL,540,'2026-07-13 07:30:59'),(739,29,'authenticator_signature_name','Authenticator Signature / Signatory Mark',NULL,NULL,'text',1,1,0,'signoff',NULL,NULL,NULL,550,'2026-07-13 07:30:59'),(740,30,'client_name','Certificate Client Name Override',NULL,NULL,'text',0,1,0,'client',NULL,NULL,NULL,10,'2026-07-13 17:26:29'),(741,30,'client_address','Certificate Client Address Override',NULL,NULL,'textarea',0,1,0,'client',NULL,NULL,NULL,20,'2026-07-13 17:26:29'),(742,30,'location_line_1','Location of Inspection - Line 1',NULL,NULL,'text',1,1,0,'client',NULL,NULL,NULL,30,'2026-07-13 17:26:29'),(743,30,'location_line_2','Location of Inspection - Line 2',NULL,NULL,'text',0,1,0,'client',NULL,NULL,NULL,40,'2026-07-13 17:26:29'),(744,30,'customer_order_number','Customer Order Number',NULL,NULL,'text',1,1,0,'metadata',NULL,NULL,NULL,50,'2026-07-13 17:26:29'),(745,30,'unit_id_number','Unit ID Number',NULL,NULL,'text',1,1,0,'unit',NULL,NULL,NULL,100,'2026-07-13 17:26:29'),(746,30,'asset_number','Asset Number',NULL,NULL,'text',0,1,0,'unit',NULL,NULL,NULL,110,'2026-07-13 17:26:29'),(747,30,'unit_quantity','Unit Quantity',NULL,NULL,'number',1,1,0,'unit',NULL,NULL,NULL,120,'2026-07-13 17:26:29'),(748,30,'description_of_unit','Description of Unit',NULL,NULL,'textarea',1,1,0,'unit',NULL,NULL,NULL,130,'2026-07-13 17:26:29'),(749,30,'unit_tare_weight','Tare Weight',NULL,NULL,'text',0,1,0,'unit',NULL,NULL,NULL,140,'2026-07-13 17:26:29'),(750,30,'unit_swl_payload','SWL / Payload',NULL,NULL,'text',1,1,0,'unit',NULL,NULL,NULL,150,'2026-07-13 17:26:29'),(751,30,'unit_gross_weight','Gross Weight',NULL,NULL,'text',1,1,0,'unit',NULL,NULL,NULL,160,'2026-07-13 17:26:29'),(752,30,'sling_id','Sling ID',NULL,NULL,'text',1,1,0,'sling',NULL,NULL,NULL,200,'2026-07-13 17:26:29'),(753,30,'sling_quantity','Sling Quantity',NULL,NULL,'number',1,1,0,'sling',NULL,NULL,NULL,210,'2026-07-13 17:26:29'),(754,30,'sling_quantity_unit','Sling Quantity Unit',NULL,NULL,'text',1,1,0,'sling',NULL,NULL,NULL,220,'2026-07-13 17:26:29'),(755,30,'sling_details','Sling Details',NULL,NULL,'textarea',1,1,0,'sling',NULL,NULL,NULL,230,'2026-07-13 17:26:29'),(756,30,'sling_tare_na','Sling Tare / N/A',NULL,NULL,'text',0,1,0,'sling',NULL,NULL,NULL,240,'2026-07-13 17:26:29'),(757,30,'sling_swl','Sling SWL',NULL,NULL,'text',1,1,0,'sling',NULL,NULL,NULL,250,'2026-07-13 17:26:29'),(758,30,'sling_gross_na','Sling Gross / N/A',NULL,NULL,'text',0,1,0,'sling',NULL,NULL,NULL,260,'2026-07-13 17:26:29'),(759,30,'load_unit_id','Load Test - Unit ID',NULL,NULL,'text',1,1,0,'load_test',NULL,NULL,NULL,300,'2026-07-13 17:26:29'),(760,30,'load_tested_by','Load Test - Tested By',NULL,NULL,'text',1,1,0,'load_test',NULL,NULL,NULL,310,'2026-07-13 17:26:29'),(761,30,'load_test_certificate_number','Load Test Certificate Number',NULL,NULL,'text',1,1,0,'load_test',NULL,NULL,NULL,320,'2026-07-13 17:26:29'),(762,30,'load_proof_load_test','Proof Load Test',NULL,NULL,'text',1,1,0,'load_test',NULL,NULL,NULL,330,'2026-07-13 17:26:29'),(763,30,'load_test_date','Load Test Date',NULL,NULL,'date',1,1,0,'load_test',NULL,NULL,NULL,340,'2026-07-13 17:26:29'),(764,30,'sling_test_sling_id','Sling Test - Sling ID',NULL,NULL,'text',1,1,0,'load_test',NULL,NULL,NULL,350,'2026-07-13 17:26:29'),(765,30,'sling_manufacturer','Sling Manufacturer',NULL,NULL,'text',1,1,0,'load_test',NULL,NULL,NULL,360,'2026-07-13 17:26:29'),(766,30,'sling_oem_certificate_number','Sling OEM Certificate Number',NULL,NULL,'text',1,1,0,'load_test',NULL,NULL,NULL,370,'2026-07-13 17:26:29'),(767,30,'sling_proof_load_test','Sling Proof Load Test / Dash',NULL,NULL,'text',0,1,0,'load_test',NULL,NULL,NULL,380,'2026-07-13 17:26:29'),(768,30,'sling_test_date','Sling Test Date',NULL,NULL,'date',1,1,0,'load_test',NULL,NULL,NULL,390,'2026-07-13 17:26:29'),(769,30,'standards','Standards',NULL,NULL,'text',1,1,0,'inspection',NULL,NULL,NULL,400,'2026-07-13 17:26:29'),(770,30,'equipment_type','Equipment Type',NULL,NULL,'text',1,1,0,'inspection',NULL,NULL,NULL,410,'2026-07-13 17:26:29'),(771,30,'contrast_media','Contrast Media',NULL,NULL,'text',1,1,0,'inspection',NULL,NULL,NULL,420,'2026-07-13 17:26:29'),(772,30,'test_procedure','Test Procedure',NULL,NULL,'text',1,1,0,'inspection',NULL,NULL,NULL,430,'2026-07-13 17:26:29'),(773,30,'pole_spacing','Pole Spacing',NULL,NULL,'text',0,1,0,'inspection',NULL,NULL,NULL,440,'2026-07-13 17:26:29'),(774,30,'indicator','Indicator',NULL,NULL,'text',0,1,0,'inspection',NULL,NULL,NULL,450,'2026-07-13 17:26:29'),(775,30,'technique','Technique',NULL,NULL,'text',1,1,0,'inspection',NULL,NULL,NULL,460,'2026-07-13 17:26:29'),(776,30,'test_method','Test Method',NULL,NULL,'text',1,1,0,'inspection',NULL,NULL,NULL,470,'2026-07-13 17:26:29'),(777,30,'test_result','Test Result',NULL,NULL,'textarea',1,1,0,'inspection',NULL,NULL,NULL,480,'2026-07-13 17:26:29'),(778,30,'demagnetization','Demagnetization',NULL,NULL,'text',0,1,0,'inspection',NULL,NULL,NULL,500,'2026-07-13 17:26:29'),(779,30,'colour_code','Colour Code',NULL,NULL,'text',0,1,0,'inspection',NULL,NULL,NULL,510,'2026-07-13 17:26:29'),(780,30,'inspector_name','Inspector Name Override',NULL,NULL,'text',0,1,0,'signatures',NULL,NULL,NULL,600,'2026-07-13 17:26:29'),(781,30,'inspector_qualifications','Inspector Qualifications',NULL,NULL,'textarea',1,1,0,'signatures',NULL,NULL,NULL,610,'2026-07-13 17:26:29'),(782,30,'authenticator_name','Authenticator Name',NULL,NULL,'text',1,1,0,'signatures',NULL,NULL,NULL,620,'2026-07-13 17:26:29'),(783,30,'authenticator_qualifications','Authenticator Qualifications',NULL,NULL,'textarea',1,1,0,'signatures',NULL,NULL,NULL,630,'2026-07-13 17:26:29'),(784,31,'client_name','Certificate Client Name Override',NULL,NULL,'text',0,1,0,'client',NULL,NULL,NULL,10,'2026-07-13 17:26:32'),(785,31,'client_address','Certificate Client Address Override',NULL,NULL,'textarea',0,1,0,'client',NULL,NULL,NULL,20,'2026-07-13 17:26:32'),(786,31,'location_line_1','Location of Inspection - Line 1',NULL,NULL,'text',1,1,0,'client',NULL,NULL,NULL,30,'2026-07-13 17:26:32'),(787,31,'location_line_2','Location of Inspection - Line 2',NULL,NULL,'text',0,1,0,'client',NULL,NULL,NULL,40,'2026-07-13 17:26:32'),(788,31,'customer_order_number','Customer Order Number',NULL,NULL,'text',1,1,0,'metadata',NULL,NULL,NULL,50,'2026-07-13 17:26:32'),(789,31,'unit_id_number','Unit ID Number',NULL,NULL,'text',1,1,0,'unit',NULL,NULL,NULL,100,'2026-07-13 17:26:32'),(790,31,'asset_number','Asset Number',NULL,NULL,'text',0,1,0,'unit',NULL,NULL,NULL,110,'2026-07-13 17:26:32'),(791,31,'unit_quantity','Unit Quantity',NULL,NULL,'number',1,1,0,'unit',NULL,NULL,NULL,120,'2026-07-13 17:26:32'),(792,31,'description_of_unit','Description of Unit',NULL,NULL,'textarea',1,1,0,'unit',NULL,NULL,NULL,130,'2026-07-13 17:26:32'),(793,31,'unit_tare_weight','Tare Weight',NULL,NULL,'text',0,1,0,'unit',NULL,NULL,NULL,140,'2026-07-13 17:26:32'),(794,31,'unit_swl_payload','SWL / Payload',NULL,NULL,'text',1,1,0,'unit',NULL,NULL,NULL,150,'2026-07-13 17:26:32'),(795,31,'unit_gross_weight','Gross Weight',NULL,NULL,'text',1,1,0,'unit',NULL,NULL,NULL,160,'2026-07-13 17:26:32'),(796,31,'sling_id','Sling ID',NULL,NULL,'text',1,1,0,'sling',NULL,NULL,NULL,200,'2026-07-13 17:26:32'),(797,31,'sling_quantity','Sling Quantity',NULL,NULL,'number',1,1,0,'sling',NULL,NULL,NULL,210,'2026-07-13 17:26:32'),(798,31,'sling_quantity_unit','Sling Quantity Unit',NULL,NULL,'text',1,1,0,'sling',NULL,NULL,NULL,220,'2026-07-13 17:26:32'),(799,31,'sling_details','Sling Details',NULL,NULL,'textarea',1,1,0,'sling',NULL,NULL,NULL,230,'2026-07-13 17:26:32'),(800,31,'sling_tare_na','Sling Tare / N/A',NULL,NULL,'text',0,1,0,'sling',NULL,NULL,NULL,240,'2026-07-13 17:26:32'),(801,31,'sling_swl','Sling SWL',NULL,NULL,'text',1,1,0,'sling',NULL,NULL,NULL,250,'2026-07-13 17:26:32'),(802,31,'sling_gross_na','Sling Gross / N/A',NULL,NULL,'text',0,1,0,'sling',NULL,NULL,NULL,260,'2026-07-13 17:26:32'),(803,31,'load_unit_id','Load Test - Unit ID',NULL,NULL,'text',1,1,0,'load_test',NULL,NULL,NULL,300,'2026-07-13 17:26:32'),(804,31,'load_tested_by','Load Test - Tested By',NULL,NULL,'text',1,1,0,'load_test',NULL,NULL,NULL,310,'2026-07-13 17:26:32'),(805,31,'load_test_certificate_number','Load Test Certificate Number',NULL,NULL,'text',1,1,0,'load_test',NULL,NULL,NULL,320,'2026-07-13 17:26:32'),(806,31,'load_proof_load_test','Proof Load Test',NULL,NULL,'text',1,1,0,'load_test',NULL,NULL,NULL,330,'2026-07-13 17:26:32'),(807,31,'load_test_date','Load Test Date',NULL,NULL,'date',1,1,0,'load_test',NULL,NULL,NULL,340,'2026-07-13 17:26:32'),(808,31,'sling_test_sling_id','Sling Test - Sling ID',NULL,NULL,'text',1,1,0,'load_test',NULL,NULL,NULL,350,'2026-07-13 17:26:32'),(809,31,'sling_manufacturer','Sling Manufacturer',NULL,NULL,'text',1,1,0,'load_test',NULL,NULL,NULL,360,'2026-07-13 17:26:32'),(810,31,'sling_oem_certificate_number','Sling OEM Certificate Number',NULL,NULL,'text',1,1,0,'load_test',NULL,NULL,NULL,370,'2026-07-13 17:26:32'),(811,31,'sling_proof_load_test','Sling Proof Load Test / Dash',NULL,NULL,'text',0,1,0,'load_test',NULL,NULL,NULL,380,'2026-07-13 17:26:32'),(812,31,'sling_test_date','Sling Test Date',NULL,NULL,'date',1,1,0,'load_test',NULL,NULL,NULL,390,'2026-07-13 17:26:32'),(813,31,'standards','Standards',NULL,NULL,'text',1,1,0,'inspection',NULL,NULL,NULL,400,'2026-07-13 17:26:32'),(814,31,'equipment_type','Equipment Type',NULL,NULL,'text',1,1,0,'inspection',NULL,NULL,NULL,410,'2026-07-13 17:26:32'),(815,31,'contrast_media','Contrast Media',NULL,NULL,'text',1,1,0,'inspection',NULL,NULL,NULL,420,'2026-07-13 17:26:32'),(816,31,'test_procedure','Test Procedure',NULL,NULL,'text',1,1,0,'inspection',NULL,NULL,NULL,430,'2026-07-13 17:26:32'),(817,31,'pole_spacing','Pole Spacing',NULL,NULL,'text',0,1,0,'inspection',NULL,NULL,NULL,440,'2026-07-13 17:26:32'),(818,31,'indicator','Indicator',NULL,NULL,'text',0,1,0,'inspection',NULL,NULL,NULL,450,'2026-07-13 17:26:32'),(819,31,'technique','Technique',NULL,NULL,'text',1,1,0,'inspection',NULL,NULL,NULL,460,'2026-07-13 17:26:32'),(820,31,'test_method','Test Method',NULL,NULL,'text',1,1,0,'inspection',NULL,NULL,NULL,470,'2026-07-13 17:26:32'),(821,31,'test_result','Test Result',NULL,NULL,'textarea',1,1,0,'inspection',NULL,NULL,NULL,480,'2026-07-13 17:26:32'),(822,31,'demagnetization','Demagnetization',NULL,NULL,'text',0,1,0,'inspection',NULL,NULL,NULL,500,'2026-07-13 17:26:32'),(823,31,'colour_code','Colour Code',NULL,NULL,'text',0,1,0,'inspection',NULL,NULL,NULL,510,'2026-07-13 17:26:32'),(824,31,'inspector_name','Inspector Name Override',NULL,NULL,'text',0,1,0,'signatures',NULL,NULL,NULL,600,'2026-07-13 17:26:32'),(825,31,'inspector_qualifications','Inspector Qualifications',NULL,NULL,'textarea',1,1,0,'signatures',NULL,NULL,NULL,610,'2026-07-13 17:26:32'),(826,31,'authenticator_name','Authenticator Name',NULL,NULL,'text',1,1,0,'signatures',NULL,NULL,NULL,620,'2026-07-13 17:26:32'),(827,31,'authenticator_qualifications','Authenticator Qualifications',NULL,NULL,'textarea',1,1,0,'signatures',NULL,NULL,NULL,630,'2026-07-13 17:26:32'),(828,32,'report_date','Date of Report','Defaults to the thorough examination date when left blank.',NULL,'date',0,1,0,'certificate',NULL,NULL,NULL,10,'2026-07-14 08:00:35'),(829,32,'premises_address','Address of Premises Where Examination Was Made',NULL,NULL,'textarea',1,1,0,'client',NULL,NULL,NULL,20,'2026-07-14 08:00:35'),(830,32,'equipment_type','Equipment Type','Enter the equipment type shown on this certificate.',NULL,'text',1,1,0,'equipment',NULL,NULL,NULL,30,'2026-07-14 08:00:35'),(831,32,'length','Length',NULL,NULL,'text',1,1,0,'equipment',NULL,NULL,NULL,40,'2026-07-14 08:00:35'),(832,32,'equipment_description','Description',NULL,NULL,'textarea',1,1,0,'equipment',NULL,NULL,NULL,50,'2026-07-14 08:00:35'),(833,32,'manufacturer','Manufacturer',NULL,NULL,'text',1,1,0,'equipment',NULL,NULL,NULL,60,'2026-07-14 08:00:35'),(834,32,'equipment_id_number','ID Number','Defaults to the linked equipment asset code when left blank.',NULL,'text',0,1,0,'equipment',NULL,NULL,NULL,70,'2026-07-14 08:00:35'),(835,32,'asset_number','Asset Number',NULL,NULL,'text',0,1,0,'equipment',NULL,NULL,NULL,80,'2026-07-14 08:00:35'),(836,32,'standard','Standard',NULL,NULL,'text',1,1,0,'equipment',NULL,NULL,NULL,90,'2026-07-14 08:00:35'),(837,32,'safe_working_load','Safe Working Load',NULL,NULL,'text',1,1,0,'equipment',NULL,NULL,NULL,100,'2026-07-14 08:00:35'),(838,32,'date_of_manufacture','Date of Manufacture',NULL,NULL,'date',0,1,0,'equipment',NULL,NULL,NULL,110,'2026-07-14 08:00:35'),(839,32,'date_of_last_examination','Date of Last Thorough Examination',NULL,NULL,'date',0,1,0,'equipment',NULL,NULL,NULL,120,'2026-07-14 08:00:35'),(840,32,'first_examination_after_installation','First Examination After Installation or Assembly at a New Site?',NULL,NULL,'select',1,1,0,'checklist',NULL,'[\"Yes\",\"No\"]',NULL,200,'2026-07-14 08:00:35'),(841,32,'installed_correctly','If Yes, Has the Equipment Been Installed Correctly?',NULL,NULL,'select',1,1,0,'checklist',NULL,'[\"Yes\",\"No\"]',NULL,210,'2026-07-14 08:00:35'),(842,32,'examined_within_six_months','Examination Carried Out Within 6 Months?',NULL,NULL,'select',1,1,0,'checklist',NULL,'[\"Yes\",\"No\"]',NULL,220,'2026-07-14 08:00:35'),(843,32,'examined_within_twelve_months','Examination Carried Out Within 12 Months?',NULL,NULL,'select',1,1,0,'checklist',NULL,'[\"Yes\",\"No\"]',NULL,230,'2026-07-14 08:00:35'),(844,32,'examined_under_scheme','Examination Carried Out Under an Examination Scheme?',NULL,NULL,'select',1,1,0,'checklist',NULL,'[\"Yes\",\"No\"]',NULL,240,'2026-07-14 08:00:35'),(845,32,'examined_after_exceptional_circumstances','Examination Carried Out After Exceptional Circumstances?',NULL,NULL,'select',1,1,0,'checklist',NULL,'[\"Yes\",\"No\"]',NULL,250,'2026-07-14 08:00:35'),(846,32,'defect_description','Defect Identification and Description','Enter NONE when no defect exists.',NULL,'textarea',1,1,0,'defects',NULL,NULL,NULL,300,'2026-07-14 08:00:35'),(847,32,'defect_immediate_danger','Is the Defect of Immediate Danger?',NULL,NULL,'select',0,1,0,'defects',NULL,'[\"Yes\",\"No\"]',NULL,310,'2026-07-14 08:00:35'),(848,32,'defect_future_danger','Could the Defect Become Dangerous?',NULL,NULL,'select',0,1,0,'defects',NULL,'[\"Yes\",\"No\"]',NULL,320,'2026-07-14 08:00:35'),(849,32,'action_due_date','Date by Which Corrective Action Must Be Completed',NULL,NULL,'date',0,1,0,'defects',NULL,NULL,NULL,330,'2026-07-14 08:00:35'),(850,32,'repair_required','Repair, Renewal or Alteration Required','Enter NONE where no action is required.',NULL,'textarea',1,1,0,'results',NULL,NULL,NULL,340,'2026-07-14 08:00:35'),(851,32,'tests_carried_out','Tests Carried Out','Enter NONE where no tests were carried out.',NULL,'textarea',1,1,0,'results',NULL,NULL,NULL,350,'2026-07-14 08:00:35'),(852,32,'observations','Observations / Additional Comments','Enter NONE where there are no additional observations.',NULL,'textarea',0,1,0,'results',NULL,NULL,NULL,360,'2026-07-14 08:00:35'),(853,32,'fit_for_purpose','Is This Equipment Fit for Purpose?',NULL,NULL,'select',1,1,0,'fitness',NULL,'[\"Yes\",\"No\"]',NULL,400,'2026-07-14 08:00:35'),(854,32,'inspector_name_snapshot','Inspector Name on Certificate','Defaults to the assigned inspector profile.',NULL,'text',0,1,0,'signoff',NULL,NULL,NULL,500,'2026-07-14 08:00:35'),(855,32,'inspector_qualification_snapshot','Inspector Qualifications on Certificate','Defaults to the assigned inspector profile.',NULL,'text',0,1,0,'signoff',NULL,NULL,NULL,510,'2026-07-14 08:00:35'),(856,32,'authenticator_name','Authenticator Name','Uses a matching active user profile signature when available.',NULL,'text',0,1,0,'signoff',NULL,NULL,NULL,520,'2026-07-14 08:00:35'),(857,32,'authenticator_qualification','Authenticator Qualifications',NULL,NULL,'text',0,1,0,'signoff',NULL,NULL,NULL,530,'2026-07-14 08:00:35'),(858,33,'colour_code','Colour Code',NULL,NULL,'text',1,1,0,'certificate',NULL,NULL,NULL,10,'2026-07-16 21:57:52'),(859,33,'standard','Standard','Example: BS EN 13889.',NULL,'text',1,1,0,'certificate',NULL,NULL,NULL,20,'2026-07-16 21:57:52'),(860,33,'inspector_name_snapshot','Inspector Name on Certificate','Defaults to the assigned inspector.',NULL,'text',0,1,0,'signoff',NULL,NULL,NULL,100,'2026-07-16 21:57:52'),(861,33,'inspector_qualification_snapshot','Inspector Qualifications on Certificate','Defaults to the inspector profile.',NULL,'text',0,1,0,'signoff',NULL,NULL,NULL,110,'2026-07-16 21:57:52'),(862,33,'authenticator_name','Authenticator Name',NULL,NULL,'text',0,1,0,'signoff',NULL,NULL,NULL,120,'2026-07-16 21:57:52'),(863,33,'authenticator_qualification','Authenticator Qualifications',NULL,NULL,'text',0,1,0,'signoff',NULL,NULL,NULL,130,'2026-07-16 21:57:52'),(864,33,'defect_observation_sheet_attached','Defect / Observation Sheet Attached?',NULL,NULL,'select',1,1,0,'signoff',NULL,'[\"Yes\",\"No\"]',NULL,140,'2026-07-16 21:57:52'),(865,34,'report_date','Date of Report','Defaults to the thorough examination date when left blank.',NULL,'date',0,1,0,'certificate',NULL,NULL,NULL,10,'2026-07-17 11:48:31'),(866,34,'equipment_type','Equipment Type','Defaults to Flat Webbing Sling.',NULL,'text',1,1,0,'equipment',NULL,NULL,NULL,20,'2026-07-17 11:48:31'),(867,34,'sling_length','Sling Length',NULL,NULL,'text',1,1,0,'equipment',NULL,NULL,NULL,30,'2026-07-17 11:48:31'),(868,34,'sling_width','Sling Width',NULL,NULL,'text',1,1,0,'equipment',NULL,NULL,NULL,40,'2026-07-17 11:48:31'),(869,34,'number_of_plies','Number of Layers / Ply',NULL,NULL,'number',0,1,0,'equipment',NULL,NULL,NULL,50,'2026-07-17 11:48:31'),(870,34,'equipment_description','Description',NULL,NULL,'textarea',1,1,0,'equipment',NULL,NULL,NULL,60,'2026-07-17 11:48:31'),(871,34,'manufacturer','Manufacturer',NULL,NULL,'text',1,1,0,'equipment',NULL,NULL,NULL,70,'2026-07-17 11:48:31'),(872,34,'identification_number','Identification Number','Defaults to the linked equipment asset code when left blank.',NULL,'text',1,1,0,'equipment',NULL,NULL,NULL,80,'2026-07-17 11:48:31'),(873,34,'asset_number','Asset Number',NULL,NULL,'text',0,1,0,'equipment',NULL,NULL,NULL,90,'2026-07-17 11:48:31'),(874,34,'standard','Standard',NULL,NULL,'text',1,1,0,'equipment',NULL,NULL,NULL,100,'2026-07-17 11:48:31'),(875,34,'safe_working_load','Safe Working Load / Working Load Limit',NULL,NULL,'text',1,1,0,'equipment',NULL,NULL,NULL,110,'2026-07-17 11:48:31'),(876,34,'date_of_manufacture','Date of Manufacture',NULL,NULL,'date',0,1,0,'equipment',NULL,NULL,NULL,120,'2026-07-17 11:48:31'),(877,34,'last_thorough_examination_date','Date of Last Thorough Examination',NULL,NULL,'date',0,1,0,'equipment',NULL,NULL,NULL,130,'2026-07-17 11:48:31'),(878,34,'first_examination_after_installation','First Examination After Installation or Assembly at a New Site?',NULL,NULL,'select',1,1,0,'checklist',NULL,'[\"Yes\",\"No\"]',NULL,200,'2026-07-17 11:48:31'),(879,34,'installed_correctly','If Yes, Has the Equipment Been Installed Correctly?',NULL,NULL,'select',1,1,0,'checklist',NULL,'[\"Yes\",\"No\"]',NULL,210,'2026-07-17 11:48:31'),(880,34,'examined_within_six_months','Examination Carried Out Within 6 Months?',NULL,NULL,'select',1,1,0,'checklist',NULL,'[\"Yes\",\"No\"]',NULL,220,'2026-07-17 11:48:31'),(881,34,'examined_within_twelve_months','Examination Carried Out Within 12 Months?',NULL,NULL,'select',1,1,0,'checklist',NULL,'[\"Yes\",\"No\"]',NULL,230,'2026-07-17 11:48:31'),(882,34,'examined_under_scheme','Examination Carried Out Under an Examination Scheme?',NULL,NULL,'select',1,1,0,'checklist',NULL,'[\"Yes\",\"No\"]',NULL,240,'2026-07-17 11:48:31'),(883,34,'examined_after_exceptional_circumstances','Examination Carried Out After Exceptional Circumstances?',NULL,NULL,'select',1,1,0,'checklist',NULL,'[\"Yes\",\"No\"]',NULL,250,'2026-07-17 11:48:31'),(884,34,'defect_description','Defect Identification and Description','Enter NONE when no defect exists.',NULL,'textarea',1,1,0,'defects',NULL,NULL,NULL,300,'2026-07-17 11:48:31'),(885,34,'defect_immediate_danger','Is the Defect of Immediate Danger?',NULL,NULL,'select',0,1,0,'defects',NULL,'[\"Yes\",\"No\"]',NULL,310,'2026-07-17 11:48:31'),(886,34,'defect_future_danger','Could the Defect Become Dangerous?',NULL,NULL,'select',0,1,0,'defects',NULL,'[\"Yes\",\"No\"]',NULL,320,'2026-07-17 11:48:31'),(887,34,'action_due_date','Date by Which Corrective Action Must Be Completed',NULL,NULL,'date',0,1,0,'defects',NULL,NULL,NULL,330,'2026-07-17 11:48:31'),(888,34,'repair_required','Repair, Renewal or Alteration Required','Enter NONE where no action is required.',NULL,'textarea',1,1,0,'results',NULL,NULL,NULL,340,'2026-07-17 11:48:31'),(889,34,'tests_carried_out','Tests Carried Out','Enter NONE where no tests were carried out.',NULL,'textarea',1,1,0,'results',NULL,NULL,NULL,350,'2026-07-17 11:48:31'),(890,34,'observations','Observations / Additional Comments','Enter NONE where there are no additional observations.',NULL,'textarea',0,1,0,'results',NULL,NULL,NULL,360,'2026-07-17 11:48:31'),(891,34,'fit_for_purpose','Is This Equipment Fit for Purpose?',NULL,NULL,'select',1,1,0,'fitness',NULL,'[\"Yes\",\"No\"]',NULL,400,'2026-07-17 11:48:31'),(892,34,'inspector_name_snapshot','Inspector Name on Certificate','Defaults to the assigned inspector profile.',NULL,'text',0,1,0,'signoff',NULL,NULL,NULL,500,'2026-07-17 11:48:31'),(893,34,'inspector_qualification_snapshot','Inspector Qualifications on Certificate','Defaults to the assigned inspector profile.',NULL,'text',0,1,0,'signoff',NULL,NULL,NULL,510,'2026-07-17 11:48:31'),(894,34,'authenticator_name','Authenticator Name','Uses a matching active user profile signature when available.',NULL,'text',0,1,0,'signoff',NULL,NULL,NULL,520,'2026-07-17 11:48:31'),(895,34,'authenticator_qualification','Authenticator Qualifications',NULL,NULL,'text',0,1,0,'signoff',NULL,NULL,NULL,530,'2026-07-17 11:48:31'),(896,35,'report_date','Date of Report','Defaults to the thorough examination date when left blank.',NULL,'date',0,1,0,'certificate',NULL,NULL,NULL,5,'2026-07-18 15:52:59'),(897,35,'colour_code','Colour Code',NULL,NULL,'text',1,1,0,'certificate',NULL,NULL,NULL,10,'2026-07-18 15:52:59'),(898,35,'standard','Standard','Example: BS EN 13889.',NULL,'text',1,1,0,'certificate',NULL,NULL,NULL,20,'2026-07-18 15:52:59'),(899,35,'inspector_name_snapshot','Inspector Name on Certificate','Defaults to the assigned inspector.',NULL,'text',0,1,0,'signoff',NULL,NULL,NULL,100,'2026-07-18 15:52:59'),(900,35,'inspector_qualification_snapshot','Inspector Qualifications on Certificate','Defaults to the inspector profile.',NULL,'text',0,1,0,'signoff',NULL,NULL,NULL,110,'2026-07-18 15:52:59'),(901,35,'authenticator_name','Authenticator Name',NULL,NULL,'text',0,1,0,'signoff',NULL,NULL,NULL,120,'2026-07-18 15:52:59'),(902,35,'authenticator_qualification','Authenticator Qualifications',NULL,NULL,'text',0,1,0,'signoff',NULL,NULL,NULL,130,'2026-07-18 15:52:59'),(903,35,'defect_observation_sheet_attached','Defect / Observation Sheet Attached?',NULL,NULL,'select',1,1,0,'signoff',NULL,'[\"Yes\",\"No\"]',NULL,140,'2026-07-18 15:52:59'),(904,36,'report_date','Date of Report','Defaults to the thorough examination date.',NULL,'date',0,1,0,'certificate',NULL,NULL,NULL,10,'2026-07-23 19:26:28'),(905,36,'premises_address','Premises Examined',NULL,NULL,'textarea',1,1,0,'client',NULL,NULL,NULL,20,'2026-07-23 19:26:28'),(906,36,'equipment_type','Equipment Name','Enter LEVER HOIST or the precise equipment title.',NULL,'text',1,1,0,'equipment',NULL,NULL,NULL,30,'2026-07-23 19:26:28'),(907,36,'equipment_description','Description',NULL,NULL,'textarea',1,1,0,'equipment',NULL,NULL,NULL,50,'2026-07-23 19:26:28'),(908,36,'manufacturer','Manufacturer',NULL,NULL,'text',1,1,0,'equipment',NULL,NULL,NULL,60,'2026-07-23 19:26:28'),(909,36,'equipment_id_number','Identification Number','Defaults to the linked equipment asset code.',NULL,'text',0,1,0,'equipment',NULL,NULL,NULL,70,'2026-07-23 19:26:28'),(910,36,'chain_dimensions','Chain Dimension',NULL,NULL,'text',1,1,0,'equipment',NULL,NULL,NULL,75,'2026-07-23 19:26:28'),(911,36,'standard','Standard',NULL,NULL,'text',1,1,0,'equipment',NULL,NULL,NULL,90,'2026-07-23 19:26:28'),(912,36,'safe_working_load','Safe Working Load',NULL,NULL,'text',1,1,0,'equipment',NULL,NULL,NULL,100,'2026-07-23 19:26:28'),(913,36,'date_of_manufacture','Date of Manufacture',NULL,NULL,'date',0,1,0,'equipment',NULL,NULL,NULL,110,'2026-07-23 19:26:28'),(914,36,'date_of_last_examination','Date of Last Thorough Examination',NULL,NULL,'date',0,1,0,'equipment',NULL,NULL,NULL,120,'2026-07-23 19:26:28'),(915,36,'first_examination_after_installation','First Examination After Installation or Assembly?',NULL,NULL,'select',1,1,0,'checklist',NULL,'[\"Yes\",\"No\"]',NULL,200,'2026-07-23 19:26:28'),(916,36,'installed_correctly','If Yes, Has the Equipment Been Installed Correctly?',NULL,NULL,'select',1,1,0,'checklist',NULL,'[\"Yes\",\"No\"]',NULL,210,'2026-07-23 19:26:28'),(917,36,'examined_within_six_months','Examination Carried Out Within 6 Months?',NULL,NULL,'select',1,1,0,'checklist',NULL,'[\"Yes\",\"No\"]',NULL,220,'2026-07-23 19:26:28'),(918,36,'examined_within_twelve_months','Examination Carried Out Within 12 Months?',NULL,NULL,'select',1,1,0,'checklist',NULL,'[\"Yes\",\"No\"]',NULL,230,'2026-07-23 19:26:28'),(919,36,'examined_under_scheme','Examination Carried Out According to Written Scheme?',NULL,NULL,'select',1,1,0,'checklist',NULL,'[\"Yes\",\"No\"]',NULL,240,'2026-07-23 19:26:28'),(920,36,'examined_after_exceptional_circumstances','Examination Carried Out After Exceptional Circumstances?',NULL,NULL,'select',1,1,0,'checklist',NULL,'[\"Yes\",\"No\"]',NULL,250,'2026-07-23 19:26:28'),(921,36,'defect_description','Identification of Defect','Enter NONE when no defect exists.',NULL,'textarea',1,1,0,'defects',NULL,NULL,NULL,300,'2026-07-23 19:26:28'),(922,36,'defect_immediate_danger','Is the Defect of Immediate Danger?',NULL,NULL,'select',0,1,0,'defects',NULL,'[\"Yes\",\"No\"]',NULL,310,'2026-07-23 19:26:28'),(923,36,'defect_future_danger','Could the Defect Become Dangerous?',NULL,NULL,'select',0,1,0,'defects',NULL,'[\"Yes\",\"No\"]',NULL,320,'2026-07-23 19:26:28'),(924,36,'future_danger_description','Not Yet Dangerous Defect Description','Enter NONE when there is no future danger.',NULL,'textarea',0,1,0,'defects',NULL,NULL,NULL,325,'2026-07-23 19:26:28'),(925,36,'action_due_date','Yes By Date',NULL,NULL,'date',0,1,0,'defects',NULL,NULL,NULL,330,'2026-07-23 19:26:28'),(926,36,'repair_required','Repair / Renewal / Alteration','Enter NONE where no action is required.',NULL,'textarea',1,1,0,'results',NULL,NULL,NULL,340,'2026-07-23 19:26:28'),(927,36,'tests_carried_out','Particulars of Tests','Enter NONE where no tests were carried out.',NULL,'textarea',1,1,0,'results',NULL,NULL,NULL,350,'2026-07-23 19:26:28'),(928,36,'observations','Additional Comments',NULL,NULL,'textarea',0,1,0,'results',NULL,NULL,NULL,360,'2026-07-23 19:26:28'),(929,36,'fit_for_purpose','Is This Equipment Fit for Purpose?',NULL,NULL,'select',1,1,0,'fitness',NULL,'[\"Yes\",\"No\"]',NULL,400,'2026-07-23 19:26:28'),(930,36,'inspector_name_snapshot','Inspector Name','Defaults to assigned inspector.',NULL,'text',0,1,0,'signoff',NULL,NULL,NULL,500,'2026-07-23 19:26:28'),(931,36,'inspector_qualification_snapshot','Inspector Qualification','Defaults to inspector profile.',NULL,'text',0,1,0,'signoff',NULL,NULL,NULL,510,'2026-07-23 19:26:28'),(932,36,'authenticator_name','Approver Name',NULL,NULL,'text',0,1,0,'signoff',NULL,NULL,NULL,520,'2026-07-23 19:26:28'),(933,36,'authenticator_qualification','Approver Qualification',NULL,NULL,'text',0,1,0,'signoff',NULL,NULL,NULL,530,'2026-07-23 19:26:28'),(934,37,'report_date','Date of Report',NULL,NULL,'date',1,1,0,'certificate',NULL,NULL,NULL,10,'2026-07-24 16:09:04'),(935,37,'premises_address','Address of Premises Where Examination Was Made',NULL,NULL,'textarea',1,1,0,'client',NULL,NULL,NULL,20,'2026-07-24 16:09:04'),(936,37,'item_inspected','Item Inspected',NULL,NULL,'text',1,1,0,'item',NULL,NULL,NULL,30,'2026-07-24 16:09:04'),(937,37,'serial_number','Serial Number',NULL,NULL,'text',1,1,0,'item',NULL,NULL,NULL,40,'2026-07-24 16:09:04'),(938,37,'material_type','Type of Material',NULL,NULL,'text',1,1,0,'item',NULL,NULL,NULL,50,'2026-07-24 16:09:04'),(939,37,'inspection_area_surface_condition','Areas Inspected / Surface Condition',NULL,NULL,'textarea',1,1,0,'item',NULL,NULL,NULL,60,'2026-07-24 16:09:04'),(940,37,'standard_used','Standard Used',NULL,NULL,'textarea',1,1,0,'item',NULL,NULL,NULL,70,'2026-07-24 16:09:04'),(941,37,'acceptance_limits','Acceptance Limits',NULL,NULL,'textarea',1,1,0,'item',NULL,NULL,NULL,80,'2026-07-24 16:09:04'),(942,37,'safe_working_load','Safe Working Load',NULL,NULL,'text',1,1,0,'item',NULL,NULL,NULL,90,'2026-07-24 16:09:04'),(943,37,'dimension','Dimension',NULL,NULL,'text',1,1,0,'item',NULL,NULL,NULL,100,'2026-07-24 16:09:04'),(944,37,'magnetic_particle_equipment','Magnetic Particle Equipment','Select every item used.',NULL,'checkbox',1,1,0,'matrix',NULL,'[\"Coil\",\"Prods\",\"Yoke\",\"UV Light\"]',NULL,200,'2026-07-24 16:09:04'),(945,37,'magnetic_particle_medium','Magnetic Particle Medium','Select every applicable medium.',NULL,'checkbox',1,1,0,'matrix',NULL,'[\"Dry\",\"Visible\",\"Wet\",\"Fluorescent\"]',NULL,210,'2026-07-24 16:09:04'),(946,37,'magnetizing_current','Magnetizing Current','Select every current used.',NULL,'checkbox',1,1,0,'matrix',NULL,'[\"AC\",\"HWDC\",\"DC\"]',NULL,220,'2026-07-24 16:09:04'),(947,37,'magnetizing_process','Magnetizing Process','Select the process used.',NULL,'checkbox',1,1,0,'matrix',NULL,'[\"Continuous\",\"Residual\"]',NULL,230,'2026-07-24 16:09:04'),(948,37,'dye_penetrant','Dye Penetrant','Enter N/A or the product and batch/reference.',NULL,'text',0,1,0,'matrix',NULL,NULL,NULL,240,'2026-07-24 16:09:04'),(949,37,'dye_developer','Dye Developer','Enter N/A or the product and batch/reference.',NULL,'text',0,1,0,'matrix',NULL,NULL,NULL,250,'2026-07-24 16:09:04'),(950,37,'dye_solvent_cleaner','Dye Solvent / Cleaner','Enter N/A or the product and batch/reference.',NULL,'text',0,1,0,'matrix',NULL,NULL,NULL,260,'2026-07-24 16:09:04'),(951,37,'nde_procedure_reference','NDE Procedure Reference Number',NULL,NULL,'text',1,1,0,'nde',NULL,NULL,NULL,300,'2026-07-24 16:09:04'),(952,37,'inspection_method','Method',NULL,NULL,'text',1,1,0,'nde',NULL,NULL,NULL,310,'2026-07-24 16:09:04'),(953,37,'remarks','Remarks',NULL,NULL,'textarea',1,1,0,'nde',NULL,NULL,NULL,320,'2026-07-24 16:09:04'),(954,37,'equipment_safe_for_use','Is This Equipment Safe for Use?',NULL,NULL,'checkbox',1,1,0,'decision',NULL,NULL,NULL,400,'2026-07-24 16:09:04'),(955,37,'inspector_name_snapshot','Inspector Name on Certificate','Defaults to the assigned inspector.',NULL,'text',0,1,0,'signoff',NULL,NULL,NULL,500,'2026-07-24 16:09:04'),(956,37,'inspector_qualification_snapshot','Inspector Qualifications on Certificate','Defaults to the inspector profile.',NULL,'text',0,1,0,'signoff',NULL,NULL,NULL,510,'2026-07-24 16:09:04'),(957,37,'authenticator_name','Authenticator Name',NULL,NULL,'text',0,1,0,'signoff',NULL,NULL,NULL,520,'2026-07-24 16:09:04'),(958,37,'authenticator_qualification','Authenticator Qualifications',NULL,NULL,'text',0,1,0,'signoff',NULL,NULL,NULL,530,'2026-07-24 16:09:04'),(959,38,'colour_code','Colour Code',NULL,NULL,'text',1,1,0,'certificate',NULL,NULL,NULL,10,'2026-07-24 18:11:05'),(960,38,'standard','Standard',NULL,NULL,'text',1,1,0,'certificate',NULL,NULL,NULL,20,'2026-07-24 18:11:05'),(961,38,'inspector_name_snapshot','Inspector Name on Certificate','Defaults to the assigned inspector.',NULL,'text',0,1,0,'signoff',NULL,NULL,NULL,100,'2026-07-24 18:11:05'),(962,38,'inspector_qualification_snapshot','Inspector Qualifications on Certificate','Defaults to the inspector profile.',NULL,'text',0,1,0,'signoff',NULL,NULL,NULL,110,'2026-07-24 18:11:05'),(963,38,'authenticator_name','Authenticator Name',NULL,NULL,'text',0,1,0,'signoff',NULL,NULL,NULL,120,'2026-07-24 18:11:05'),(964,38,'authenticator_qualification','Authenticator Qualifications',NULL,NULL,'text',0,1,0,'signoff',NULL,NULL,NULL,130,'2026-07-24 18:11:05'),(965,38,'defect_observation_sheet_attached','Defect / Observation Sheet Attached?',NULL,NULL,'select',1,1,0,'signoff',NULL,'[\"Yes\",\"No\"]',NULL,140,'2026-07-24 18:11:05'),(966,39,'report_date','Date of Report','Defaults to examination date.',NULL,'date',0,1,0,'certificate',NULL,NULL,NULL,10,'2026-07-24 18:11:05'),(967,39,'premises_address','Inspection Premises',NULL,NULL,'textarea',1,1,0,'client',NULL,NULL,NULL,20,'2026-07-24 18:11:05'),(968,39,'equipment_type','Equipment Type','Enter the actual equipment type.',NULL,'text',1,1,0,'equipment',NULL,NULL,NULL,30,'2026-07-24 18:11:05'),(969,39,'equipment_description','Description',NULL,NULL,'textarea',1,1,0,'equipment',NULL,NULL,NULL,40,'2026-07-24 18:11:05'),(970,39,'manufacturer','Manufacturer',NULL,NULL,'text',1,1,0,'equipment',NULL,NULL,NULL,50,'2026-07-24 18:11:05'),(971,39,'equipment_id_number','Identification Number',NULL,NULL,'text',1,1,0,'equipment',NULL,NULL,NULL,60,'2026-07-24 18:11:05'),(972,39,'asset_number','Asset Number',NULL,NULL,'text',0,1,0,'equipment',NULL,NULL,NULL,70,'2026-07-24 18:11:05'),(973,39,'chain_dimensions','Dimensions or Specifications',NULL,NULL,'text',0,1,0,'equipment',NULL,NULL,NULL,80,'2026-07-24 18:11:05'),(974,39,'standard','Standard',NULL,NULL,'text',1,1,0,'equipment',NULL,NULL,NULL,90,'2026-07-24 18:11:05'),(975,39,'safe_working_load','Safe Working Load',NULL,NULL,'text',1,1,0,'equipment',NULL,NULL,NULL,100,'2026-07-24 18:11:05'),(976,39,'date_of_manufacture','Date of Manufacture',NULL,NULL,'date',0,1,0,'equipment',NULL,NULL,NULL,110,'2026-07-24 18:11:05'),(977,39,'date_of_last_examination','Date of Last Thorough Examination',NULL,NULL,'date',0,1,0,'equipment',NULL,NULL,NULL,120,'2026-07-24 18:11:05'),(978,39,'first_examination_after_installation','First Examination After Installation or Assembly?',NULL,NULL,'select',1,1,0,'checklist',NULL,'[\"Yes\",\"No\"]',NULL,200,'2026-07-24 18:11:05'),(979,39,'installed_correctly','If Yes, Installed Correctly?',NULL,NULL,'select',1,1,0,'checklist',NULL,'[\"Yes\",\"No\"]',NULL,210,'2026-07-24 18:11:06'),(980,39,'examined_within_six_months','Examined Within Six Months?',NULL,NULL,'select',1,1,0,'checklist',NULL,'[\"Yes\",\"No\"]',NULL,220,'2026-07-24 18:11:06'),(981,39,'examined_within_twelve_months','Examined Within Twelve Months?',NULL,NULL,'select',1,1,0,'checklist',NULL,'[\"Yes\",\"No\"]',NULL,230,'2026-07-24 18:11:06'),(982,39,'examined_under_scheme','In Accordance With an Examination Scheme?',NULL,NULL,'select',1,1,0,'checklist',NULL,'[\"Yes\",\"No\"]',NULL,240,'2026-07-24 18:11:06'),(983,39,'examined_after_exceptional_circumstances','After Exceptional Circumstances?',NULL,NULL,'select',1,1,0,'checklist',NULL,'[\"Yes\",\"No\"]',NULL,250,'2026-07-24 18:11:06'),(984,39,'defect_description','Defect Description','Enter NONE when no defect exists.',NULL,'textarea',1,1,0,'defects',NULL,NULL,NULL,300,'2026-07-24 18:11:06'),(985,39,'defect_immediate_danger','Immediate Danger?',NULL,NULL,'select',0,1,0,'defects',NULL,'[\"Yes\",\"No\"]',NULL,310,'2026-07-24 18:11:06'),(986,39,'defect_future_danger','Could Become Dangerous?',NULL,NULL,'select',0,1,0,'defects',NULL,'[\"Yes\",\"No\"]',NULL,320,'2026-07-24 18:11:06'),(987,39,'action_due_date','Corrective-action Date',NULL,NULL,'date',0,1,0,'defects',NULL,NULL,NULL,330,'2026-07-24 18:11:06'),(988,39,'repair_required','Repair, Renewal or Alteration Required','Enter NONE where no action is required.',NULL,'textarea',1,1,0,'results',NULL,NULL,NULL,340,'2026-07-24 18:11:06'),(989,39,'tests_carried_out','Tests Carried Out',NULL,NULL,'textarea',1,1,0,'results',NULL,NULL,NULL,350,'2026-07-24 18:11:06'),(990,39,'observations','Observations / Comments',NULL,NULL,'textarea',0,1,0,'results',NULL,NULL,NULL,360,'2026-07-24 18:11:06'),(991,39,'fit_for_purpose','Fit for Purpose?',NULL,NULL,'select',1,1,0,'fitness',NULL,'[\"Yes\",\"No\"]',NULL,400,'2026-07-24 18:11:06'),(992,39,'inspector_name_snapshot','Inspector','Defaults to the assigned inspector.',NULL,'text',0,1,0,'signoff',NULL,NULL,NULL,500,'2026-07-24 18:11:06'),(993,39,'inspector_qualification_snapshot','Inspector Qualifications',NULL,NULL,'text',0,1,0,'signoff',NULL,NULL,NULL,510,'2026-07-24 18:11:06'),(994,39,'authenticator_name','Authenticator',NULL,NULL,'text',0,1,0,'signoff',NULL,NULL,NULL,520,'2026-07-24 18:11:06'),(995,39,'authenticator_qualification','Authenticator Qualifications',NULL,NULL,'text',0,1,0,'signoff',NULL,NULL,NULL,530,'2026-07-24 18:11:06');
/*!40000 ALTER TABLE `form_fields` ENABLE KEYS */;
UNLOCK TABLES;
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

LOCK TABLES `form_repeatable_columns` WRITE;
/*!40000 ALTER TABLE `form_repeatable_columns` DISABLE KEYS */;
INSERT INTO `form_repeatable_columns` VALUES (1,1,'check_item','Check Item','text',1,NULL,NULL,NULL,1,0,10,'2026-07-09 17:36:10','2026-07-09 17:36:10'),(2,1,'status','Status','pass_fail',1,NULL,NULL,NULL,1,0,20,'2026-07-09 17:36:10','2026-07-09 17:36:10'),(3,1,'remarks','Remarks','textarea',0,NULL,NULL,NULL,1,0,30,'2026-07-09 17:36:10','2026-07-09 17:36:10'),(21,5,'shackle_id','Shackle ID','text',1,NULL,NULL,NULL,1,0,10,'2026-07-11 13:11:16','2026-07-11 13:11:16'),(22,5,'quantity','Quantity','number',1,NULL,NULL,NULL,1,0,20,'2026-07-11 13:11:16','2026-07-11 13:11:16'),(23,5,'quantity_unit','Quantity Unit','text',1,NULL,NULL,NULL,1,0,30,'2026-07-11 13:11:16','2026-07-11 13:11:16'),(24,5,'shackle_description','Shackle Description','textarea',1,NULL,NULL,NULL,1,0,40,'2026-07-11 13:11:16','2026-07-11 13:11:16'),(25,5,'tare_na','Tare / N/A','text',0,NULL,NULL,NULL,1,0,50,'2026-07-11 13:11:16','2026-07-11 13:11:16'),(26,5,'swl','SWL','text',1,NULL,NULL,NULL,1,0,60,'2026-07-11 13:11:16','2026-07-11 13:11:16'),(27,5,'gross_na','Gross / N/A','text',0,NULL,NULL,NULL,1,0,70,'2026-07-11 13:11:16','2026-07-11 13:11:16'),(28,6,'serial_number','Serial / Tag Number','text',0,NULL,NULL,NULL,1,0,10,'2026-07-11 13:11:17','2026-07-11 13:11:17'),(29,6,'description','Description','text',1,NULL,NULL,NULL,1,0,20,'2026-07-11 13:11:17','2026-07-11 13:11:17'),(30,6,'wll','SWL / WLL','text',0,NULL,NULL,NULL,1,0,30,'2026-07-11 13:11:17','2026-07-11 13:11:17'),(31,6,'date_examined','Date Examined','date',0,NULL,NULL,NULL,1,0,40,'2026-07-11 13:11:17','2026-07-11 13:11:17'),(32,6,'remarks','Remarks','textarea',0,NULL,NULL,NULL,1,0,50,'2026-07-11 13:11:17','2026-07-11 13:11:17'),(33,6,'status','Status','pass_fail',1,NULL,NULL,NULL,1,0,60,'2026-07-11 13:11:17','2026-07-11 13:11:17'),(34,7,'serial_number','Serial / ID Number','text',0,NULL,NULL,NULL,1,0,10,'2026-07-11 13:11:17','2026-07-11 13:11:17'),(35,7,'description','Description','text',1,NULL,NULL,NULL,1,0,20,'2026-07-11 13:11:17','2026-07-11 13:11:17'),(36,7,'swl_wll','SWL / WLL','text',0,NULL,NULL,NULL,1,0,30,'2026-07-11 13:11:17','2026-07-11 13:11:17'),(37,7,'manufacturer','Manufacturer','text',0,NULL,NULL,NULL,1,0,40,'2026-07-11 13:11:17','2026-07-11 13:11:17'),(38,7,'remarks','Remarks','textarea',0,NULL,NULL,NULL,1,0,50,'2026-07-11 13:11:17','2026-07-11 13:11:17'),(39,7,'status','Status','pass_fail',1,NULL,NULL,NULL,1,0,60,'2026-07-11 13:11:17','2026-07-11 13:11:17'),(40,8,'zone','Zone / Location','text',1,NULL,NULL,NULL,1,0,10,'2026-07-11 13:11:19','2026-07-11 13:11:19'),(41,8,'method','Method','select',1,'[\"MPI\",\"DPI\",\"UT\",\"VT\"]',NULL,NULL,1,0,20,'2026-07-11 13:11:19','2026-07-11 13:11:19'),(42,8,'finding','Finding','textarea',1,NULL,NULL,NULL,1,0,30,'2026-07-11 13:11:19','2026-07-11 13:11:19'),(43,8,'severity','Severity','select',1,'[\"Acceptable\",\"Monitor\",\"Reject\"]',NULL,NULL,1,0,40,'2026-07-11 13:11:19','2026-07-11 13:11:19'),(44,8,'disposition','Disposition','text',0,NULL,NULL,NULL,1,0,50,'2026-07-11 13:11:19','2026-07-11 13:11:19'),(45,9,'shackle_id','Shackle ID','text',1,NULL,NULL,NULL,1,0,10,'2026-07-13 17:26:29','2026-07-13 17:26:29'),(46,9,'quantity','Quantity','number',1,NULL,NULL,NULL,1,0,20,'2026-07-13 17:26:29','2026-07-13 17:26:29'),(47,9,'quantity_unit','Quantity Unit','text',1,NULL,NULL,NULL,1,0,30,'2026-07-13 17:26:29','2026-07-13 17:26:29'),(48,9,'shackle_description','Shackle Description','textarea',1,NULL,NULL,NULL,1,0,40,'2026-07-13 17:26:29','2026-07-13 17:26:29'),(49,9,'tare_na','Tare / N/A','text',0,NULL,NULL,NULL,1,0,50,'2026-07-13 17:26:29','2026-07-13 17:26:29'),(50,9,'swl','SWL','text',1,NULL,NULL,NULL,1,0,60,'2026-07-13 17:26:29','2026-07-13 17:26:29'),(51,9,'gross_na','Gross / N/A','text',0,NULL,NULL,NULL,1,0,70,'2026-07-13 17:26:29','2026-07-13 17:26:29'),(52,10,'shackle_id','Shackle ID','text',1,NULL,NULL,NULL,1,0,10,'2026-07-13 17:26:32','2026-07-13 17:26:32'),(53,10,'quantity','Quantity','number',1,NULL,NULL,NULL,1,0,20,'2026-07-13 17:26:32','2026-07-13 17:26:32'),(54,10,'quantity_unit','Quantity Unit','text',1,NULL,NULL,NULL,1,0,30,'2026-07-13 17:26:32','2026-07-13 17:26:32'),(55,10,'shackle_description','Shackle Description','textarea',1,NULL,NULL,NULL,1,0,40,'2026-07-13 17:26:32','2026-07-13 17:26:32'),(56,10,'tare_na','Tare / N/A','text',0,NULL,NULL,NULL,1,0,50,'2026-07-13 17:26:32','2026-07-13 17:26:32'),(57,10,'swl','SWL','text',1,NULL,NULL,NULL,1,0,60,'2026-07-13 17:26:32','2026-07-13 17:26:32'),(58,10,'gross_na','Gross / N/A','text',0,NULL,NULL,NULL,1,0,70,'2026-07-13 17:26:32','2026-07-13 17:26:32'),(59,11,'serial_number','S/N','text',0,NULL,NULL,NULL,1,0,10,'2026-07-16 21:57:52','2026-07-16 21:57:52'),(60,11,'identification_number','Identification Number','text',1,NULL,NULL,NULL,1,0,20,'2026-07-16 21:57:52','2026-07-16 21:57:52'),(61,11,'description','Description','text',1,NULL,NULL,NULL,1,0,30,'2026-07-16 21:57:52','2026-07-16 21:57:52'),(62,11,'working_load_limit','WLL or SWL','text',1,NULL,NULL,NULL,1,0,40,'2026-07-16 21:57:52','2026-07-16 21:57:52'),(63,11,'last_thorough_examination_date','Last Thorough Examination','date',0,NULL,NULL,NULL,1,0,50,'2026-07-16 21:57:52','2026-07-16 21:57:52'),(64,11,'manufacturer','Manufacturer','text',1,NULL,NULL,NULL,1,0,60,'2026-07-16 21:57:52','2026-07-16 21:57:52'),(65,11,'next_thorough_examination_date','Next Thorough Examination','date',1,NULL,NULL,NULL,1,0,70,'2026-07-16 21:57:52','2026-07-16 21:57:52'),(66,11,'reason_for_examination_code','Reason Code','select',1,'[\"A\",\"B\",\"C\",\"D\",\"E\"]',NULL,NULL,1,0,80,'2026-07-16 21:57:52','2026-07-16 21:57:52'),(67,11,'test_details','Test Details','text',1,NULL,NULL,NULL,1,0,90,'2026-07-16 21:57:52','2026-07-16 21:57:52'),(68,11,'status_code','Status','select',1,'[\"ND\",\"SDR\",\"NF\",\"OBS\"]',NULL,NULL,1,0,100,'2026-07-16 21:57:52','2026-07-16 21:57:52'),(69,11,'safe_to_use','Safe to Use','select',1,'[\"Yes\",\"No\"]',NULL,NULL,1,0,110,'2026-07-16 21:57:52','2026-07-16 21:57:52'),(70,12,'serial_number','S/N','text',0,NULL,NULL,NULL,1,0,10,'2026-07-18 15:52:59','2026-07-18 15:52:59'),(71,12,'identification_number','Identification Number','text',1,NULL,NULL,NULL,1,0,20,'2026-07-18 15:52:59','2026-07-18 15:52:59'),(72,12,'description','Description','text',1,NULL,NULL,NULL,1,0,30,'2026-07-18 15:52:59','2026-07-18 15:52:59'),(73,12,'working_load_limit','WLL or SWL','text',1,NULL,NULL,NULL,1,0,40,'2026-07-18 15:52:59','2026-07-18 15:52:59'),(74,12,'last_thorough_examination_date','Last Thorough Examination','date',0,NULL,NULL,NULL,1,0,50,'2026-07-18 15:52:59','2026-07-18 15:52:59'),(75,12,'manufacturer','Manufacturer','text',1,NULL,NULL,NULL,1,0,60,'2026-07-18 15:52:59','2026-07-18 15:52:59'),(76,12,'next_thorough_examination_date','Next Thorough Examination','date',1,NULL,NULL,NULL,1,0,70,'2026-07-18 15:52:59','2026-07-18 15:52:59'),(77,12,'reason_for_examination_code','Reason Code','select',1,'[\"A\",\"B\",\"C\",\"D\",\"E\"]',NULL,NULL,1,0,80,'2026-07-18 15:52:59','2026-07-18 15:52:59'),(78,12,'test_details','Test Details','text',1,NULL,NULL,NULL,1,0,90,'2026-07-18 15:52:59','2026-07-18 15:52:59'),(79,12,'status_code','Status','select',1,'[\"ND\",\"SDR\",\"NF\",\"OBS\"]',NULL,NULL,1,0,100,'2026-07-18 15:52:59','2026-07-18 15:52:59'),(80,12,'safe_to_use','Safe to Use','select',1,'[\"Yes\",\"No\"]',NULL,NULL,1,0,110,'2026-07-18 15:52:59','2026-07-18 15:52:59'),(81,13,'serial_number','S/N','text',0,NULL,NULL,NULL,1,0,10,'2026-07-24 18:11:05','2026-07-24 18:11:05'),(82,13,'identification_number','Identification Number','text',1,NULL,NULL,NULL,1,0,20,'2026-07-24 18:11:05','2026-07-24 18:11:05'),(83,13,'description','Equipment / Accessory Type and Description','text',1,NULL,NULL,NULL,1,0,30,'2026-07-24 18:11:05','2026-07-24 18:11:05'),(84,13,'working_load_limit','WLL or SWL','text',1,NULL,NULL,NULL,1,0,40,'2026-07-24 18:11:05','2026-07-24 18:11:05'),(85,13,'last_thorough_examination_date','Last Thorough Examination','date',0,NULL,NULL,NULL,1,0,50,'2026-07-24 18:11:05','2026-07-24 18:11:05'),(86,13,'manufacturer','Manufacturer','text',1,NULL,NULL,NULL,1,0,60,'2026-07-24 18:11:05','2026-07-24 18:11:05'),(87,13,'next_thorough_examination_date','Next Thorough Examination','date',1,NULL,NULL,NULL,1,0,70,'2026-07-24 18:11:05','2026-07-24 18:11:05'),(88,13,'reason_for_examination_code','Reason Code','select',1,'[\"A\",\"B\",\"C\",\"D\",\"E\"]',NULL,NULL,1,0,80,'2026-07-24 18:11:05','2026-07-24 18:11:05'),(89,13,'test_details','Details of Test','text',1,NULL,NULL,NULL,1,0,90,'2026-07-24 18:11:05','2026-07-24 18:11:05'),(90,13,'status_code','Status Code','select',1,'[\"ND\",\"SDR\",\"NF\",\"OBS\"]',NULL,NULL,1,0,100,'2026-07-24 18:11:05','2026-07-24 18:11:05'),(91,13,'safe_to_use','Safe to Use','select',1,'[\"Yes\",\"No\"]',NULL,NULL,1,0,110,'2026-07-24 18:11:05','2026-07-24 18:11:05');
/*!40000 ALTER TABLE `form_repeatable_columns` ENABLE KEYS */;
UNLOCK TABLES;
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

LOCK TABLES `form_repeatable_sections` WRITE;
/*!40000 ALTER TABLE `form_repeatable_sections` DISABLE KEYS */;
INSERT INTO `form_repeatable_sections` VALUES (1,12,'ccu_checklist','CCU Visual Checklist','Record the structural and marking checks performed on the CCU.',4,20,'inspection_table',10,'2026-07-09 17:36:10','2026-07-09 17:36:10'),(5,27,'shackle_details','Shackle Details','Add each shackle ID separately as shown on the CCU certificate.',1,12,'shackle_table',10,'2026-07-11 13:11:16','2026-07-11 13:11:16'),(6,17,'wire_rope_components','Wire Rope Sling Components','Capture rope legs, end fittings or sub-components inspected on the sling.',1,12,'inspection_table',10,'2026-07-11 13:11:17','2026-07-11 13:11:17'),(7,18,'accessory_items','Accessory Inspection Items','Use when one certificate covers multiple shackles or accessories.',1,20,'inspection_table',10,'2026-07-11 13:11:17','2026-07-11 13:11:17'),(8,26,'ndt_findings','NDT Findings','Capture the spreader bar zones inspected and the corresponding findings.',1,20,'inspection_table',10,'2026-07-11 13:11:19','2026-07-11 13:11:19'),(9,30,'shackle_details','Shackle Details','Add each shackle ID separately as shown on the CCU certificate.',1,12,'shackle_table',10,'2026-07-13 17:26:29','2026-07-13 17:26:29'),(10,31,'shackle_details','Shackle Details','Add each shackle ID separately as shown on the CCU certificate.',1,12,'shackle_table',10,'2026-07-13 17:26:32','2026-07-13 17:26:32'),(11,33,'eye_bolt_items','Eye Bolt Examination Register','Add one row for each eye bolt covered by this certificate. Maximum six rows per one-page certificate.',1,6,'equipment_register',200,'2026-07-16 21:57:52','2026-07-16 21:57:52'),(12,35,'hook_items','Hook Examination Register','Add one row for each hook covered by this certificate. Maximum six rows per one-page certificate.',1,6,'equipment_register',200,'2026-07-18 15:52:59','2026-07-18 15:52:59'),(13,38,'eye_bolt_items','General Lifting Accessory Register','Add each accessory separately. Use a dedicated category where one exists.',1,6,'equipment_register',200,'2026-07-24 18:11:05','2026-07-24 18:11:05');
/*!40000 ALTER TABLE `form_repeatable_sections` ENABLE KEYS */;
UNLOCK TABLES;
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

LOCK TABLES `form_templates` WRITE;
/*!40000 ALTER TABLE `form_templates` DISABLE KEYS */;
INSERT INTO `form_templates` VALUES (1,1,'CCU Thorough Examination Form',1,'archived',1,1,0,0,0,'image/jpeg,image/png,image/webp,application/pdf',0,0,0,0,'2026-06-27 16:26:22','2026-07-09 18:36:10'),(2,2,'Magnetic Particle Inspection Form',1,'active',1,1,0,0,0,'image/jpeg,image/png,image/webp,application/pdf',0,0,0,0,'2026-06-27 16:26:22','2026-06-27 16:26:22'),(3,3,'Shackle Inspection Form',1,'archived',1,1,0,0,0,'image/jpeg,image/png,image/webp,application/pdf',0,0,0,0,'2026-06-27 16:26:22','2026-07-09 18:36:11'),(4,4,'Forklift Inspection Form',1,'active',1,1,0,0,0,'image/jpeg,image/png,image/webp,application/pdf',0,0,0,0,'2026-06-27 16:26:22','2026-06-27 16:26:22'),(8,6,'Legacy Inspection B Form',1,'active',1,1,0,0,0,'image/jpeg,image/png,image/webp,application/pdf',0,0,0,0,'2026-06-27 16:26:51','2026-06-27 16:26:51'),(9,5,'Legacy Inspection C Form',1,'archived',1,1,0,0,0,'image/jpeg,image/png,image/webp,application/pdf',0,0,0,0,'2026-06-27 16:26:51','2026-07-06 08:39:22'),(10,7,'Legacy Inspection D Form',1,'active',1,1,0,0,0,'image/jpeg,image/png,image/webp,application/pdf',0,0,0,0,'2026-06-27 16:26:51','2026-06-27 16:26:51'),(11,5,'Legacy Inspection C Form',2,'active',1,1,0,0,0,'image/jpeg,image/png,image/webp,application/pdf',0,0,0,0,'2026-07-06 07:39:22','2026-07-06 07:39:22'),(12,1,'CCU Visual Inspection Form',2,'archived',1,1,0,0,0,'image/jpeg,image/png,image/webp,application/pdf',0,0,0,0,'2026-07-09 17:36:10','2026-07-11 14:11:16'),(13,8,'Chain Block Form',1,'archived',1,1,0,0,0,'image/jpeg,image/png,image/webp,application/pdf',0,0,0,0,'2026-07-09 17:36:10','2026-07-13 07:30:59'),(14,9,'Lever Hoist Form',1,'archived',1,1,0,0,0,'image/jpeg,image/png,image/webp,application/pdf',0,0,0,0,'2026-07-09 17:36:10','2026-07-23 19:26:28'),(15,10,'Flat Webbing Sling Form',1,'archived',1,1,0,0,0,'image/jpeg,image/png,image/webp,application/pdf',0,0,0,0,'2026-07-09 17:36:10','2026-07-17 11:48:31'),(16,11,'Endless Round Webbing Sling Form',1,'archived',1,1,0,0,0,'image/jpeg,image/png,image/webp,application/pdf',0,0,0,0,'2026-07-09 17:36:10','2026-07-14 08:00:35'),(17,12,'Wire Rope Sling Form',1,'active',1,1,0,0,0,'image/jpeg,image/png,image/webp,application/pdf',0,0,0,0,'2026-07-09 17:36:11','2026-07-09 17:36:11'),(18,3,'Shackles and Accessories Form',2,'active',1,1,0,0,0,'image/jpeg,image/png,image/webp,application/pdf',0,0,0,0,'2026-07-09 17:36:11','2026-07-09 17:36:11'),(19,13,'Eye Bolt Form',1,'archived',1,1,0,0,0,'image/jpeg,image/png,image/webp,application/pdf',0,0,0,0,'2026-07-09 17:36:11','2026-07-16 21:57:51'),(20,14,'Hook Form',1,'archived',1,1,0,0,0,'image/jpeg,image/png,image/webp,application/pdf',0,0,0,0,'2026-07-09 17:36:11','2026-07-18 15:52:58'),(21,15,'Horizontal Clamp Form',1,'active',1,1,0,0,0,'image/jpeg,image/png,image/webp,application/pdf',0,0,0,0,'2026-07-09 17:36:11','2026-07-09 17:36:11'),(22,16,'Vertical Clamp Form',1,'active',1,1,0,0,0,'image/jpeg,image/png,image/webp,application/pdf',0,0,0,0,'2026-07-09 17:36:11','2026-07-09 17:36:11'),(23,17,'Universal Clamp Form',1,'archived',1,1,0,0,0,'image/jpeg,image/png,image/webp,application/pdf',0,0,0,0,'2026-07-09 17:36:11','2026-07-11 14:11:18'),(24,18,'Lifting Magnet Form',1,'active',1,1,0,0,0,'image/jpeg,image/png,image/webp,application/pdf',0,0,0,0,'2026-07-09 17:36:12','2026-07-09 17:36:12'),(25,19,'Pallet Truck Form',1,'active',1,1,0,0,0,'image/jpeg,image/png,image/webp,application/pdf',0,0,0,0,'2026-07-09 17:36:12','2026-07-09 17:36:12'),(26,20,'MPI / NDT Spreader Bar Form',1,'archived',1,1,0,0,0,'image/jpeg,image/png,image/webp,application/pdf',0,0,0,0,'2026-07-09 17:36:12','2026-07-24 17:09:04'),(27,1,'CCU Visual Inspection Form',3,'archived',1,1,0,0,0,'image/jpeg,image/png,image/webp,application/pdf',0,0,0,0,'2026-07-11 13:11:16','2026-07-13 18:26:29'),(28,17,'Universal Clamp Form',2,'active',1,1,0,0,0,'image/jpeg,image/png,image/webp,application/pdf',0,0,0,0,'2026-07-11 13:11:18','2026-07-11 13:11:18'),(29,8,'Chain Block Thorough Examination',2,'active',1,1,0,0,0,'image/jpeg,image/png,image/webp,application/pdf',0,0,0,0,'2026-07-13 07:30:59','2026-07-13 07:30:59'),(30,1,'CCU Visual Inspection Form',4,'archived',1,1,0,0,0,'image/jpeg,image/png,image/webp,application/pdf',0,0,0,0,'2026-07-13 17:26:29','2026-07-13 18:26:32'),(31,1,'CCU Visual Inspection Form',5,'active',1,1,0,0,0,'image/jpeg,image/png,image/webp,application/pdf',0,0,0,0,'2026-07-13 17:26:32','2026-07-13 17:26:32'),(32,11,'Endless Round Webbing Sling Thorough Examination',2,'active',1,1,0,0,0,'image/jpeg,image/png,image/webp,application/pdf',0,0,0,0,'2026-07-14 08:00:35','2026-07-14 08:00:35'),(33,13,'Eye Bolt Thorough Examination Register',2,'active',1,1,0,0,0,'image/jpeg,image/png,image/webp,application/pdf',0,0,0,0,'2026-07-16 21:57:51','2026-07-16 21:57:51'),(34,10,'Flat Webbing Sling Thorough Examination',2,'active',1,1,0,0,0,'image/jpeg,image/png,image/webp,application/pdf',0,0,0,0,'2026-07-17 11:48:31','2026-07-17 11:48:31'),(35,14,'Hook Thorough Examination Register',2,'active',1,1,0,0,0,'image/jpeg,image/png,image/webp,application/pdf',0,0,0,0,'2026-07-18 15:52:59','2026-07-18 15:52:59'),(36,9,'Lever Hoist Thorough Examination',2,'active',1,1,0,0,0,'image/jpeg,image/png,image/webp,application/pdf',0,0,0,0,'2026-07-23 19:26:28','2026-07-23 19:26:28'),(37,20,'MPI / NDT Spreader Bar Form',2,'active',1,1,1,0,0,'image/jpeg,image/png,image/webp,application/pdf',0,0,0,0,'2026-07-24 16:09:04','2026-07-24 16:09:04'),(38,21,'General Lifting Accessories Form',1,'active',1,1,0,0,0,'image/jpeg,image/png,image/webp,application/pdf',0,0,0,0,'2026-07-24 18:11:05','2026-07-24 18:11:05'),(39,22,'General Thorough Examination Form',1,'active',1,1,0,0,0,'image/jpeg,image/png,image/webp,application/pdf',0,0,0,0,'2026-07-24 18:11:05','2026-07-24 18:11:05');
/*!40000 ALTER TABLE `form_templates` ENABLE KEYS */;
UNLOCK TABLES;
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

LOCK TABLES `inspection_attachments` WRITE;
/*!40000 ALTER TABLE `inspection_attachments` DISABLE KEYS */;
/*!40000 ALTER TABLE `inspection_attachments` ENABLE KEYS */;
UNLOCK TABLES;
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

LOCK TABLES `inspection_comments` WRITE;
/*!40000 ALTER TABLE `inspection_comments` DISABLE KEYS */;
/*!40000 ALTER TABLE `inspection_comments` ENABLE KEYS */;
UNLOCK TABLES;
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

LOCK TABLES `inspection_items` WRITE;
/*!40000 ALTER TABLE `inspection_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `inspection_items` ENABLE KEYS */;
UNLOCK TABLES;
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

LOCK TABLES `inspection_values` WRITE;
/*!40000 ALTER TABLE `inspection_values` DISABLE KEYS */;
/*!40000 ALTER TABLE `inspection_values` ENABLE KEYS */;
UNLOCK TABLES;
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

LOCK TABLES `inspections` WRITE;
/*!40000 ALTER TABLE `inspections` DISABLE KEYS */;
/*!40000 ALTER TABLE `inspections` ENABLE KEYS */;
UNLOCK TABLES;
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

LOCK TABLES `juva_client_master` WRITE;
/*!40000 ALTER TABLE `juva_client_master` DISABLE KEYS */;
/*!40000 ALTER TABLE `juva_client_master` ENABLE KEYS */;
UNLOCK TABLES;
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

LOCK TABLES `juva_equip_insp_check_list` WRITE;
/*!40000 ALTER TABLE `juva_equip_insp_check_list` DISABLE KEYS */;
/*!40000 ALTER TABLE `juva_equip_insp_check_list` ENABLE KEYS */;
UNLOCK TABLES;
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

LOCK TABLES `juva_equipment_insp_calibration` WRITE;
/*!40000 ALTER TABLE `juva_equipment_insp_calibration` DISABLE KEYS */;
/*!40000 ALTER TABLE `juva_equipment_insp_calibration` ENABLE KEYS */;
UNLOCK TABLES;
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

LOCK TABLES `juva_equipment_inspt_ccu` WRITE;
/*!40000 ALTER TABLE `juva_equipment_inspt_ccu` DISABLE KEYS */;
/*!40000 ALTER TABLE `juva_equipment_inspt_ccu` ENABLE KEYS */;
UNLOCK TABLES;
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

LOCK TABLES `juva_equipment_inspt_excavator` WRITE;
/*!40000 ALTER TABLE `juva_equipment_inspt_excavator` DISABLE KEYS */;
/*!40000 ALTER TABLE `juva_equipment_inspt_excavator` ENABLE KEYS */;
UNLOCK TABLES;
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

LOCK TABLES `juva_equipment_inspt_gen_nde` WRITE;
/*!40000 ALTER TABLE `juva_equipment_inspt_gen_nde` DISABLE KEYS */;
/*!40000 ALTER TABLE `juva_equipment_inspt_gen_nde` ENABLE KEYS */;
UNLOCK TABLES;
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

LOCK TABLES `juva_equipment_inspt_master` WRITE;
/*!40000 ALTER TABLE `juva_equipment_inspt_master` DISABLE KEYS */;
/*!40000 ALTER TABLE `juva_equipment_inspt_master` ENABLE KEYS */;
UNLOCK TABLES;
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

LOCK TABLES `juva_equipment_inspt_mpi` WRITE;
/*!40000 ALTER TABLE `juva_equipment_inspt_mpi` DISABLE KEYS */;
/*!40000 ALTER TABLE `juva_equipment_inspt_mpi` ENABLE KEYS */;
UNLOCK TABLES;
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

LOCK TABLES `juva_equipment_inspt_shackle` WRITE;
/*!40000 ALTER TABLE `juva_equipment_inspt_shackle` DISABLE KEYS */;
/*!40000 ALTER TABLE `juva_equipment_inspt_shackle` ENABLE KEYS */;
UNLOCK TABLES;
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

LOCK TABLES `juva_equipment_inspt_sub` WRITE;
/*!40000 ALTER TABLE `juva_equipment_inspt_sub` DISABLE KEYS */;
/*!40000 ALTER TABLE `juva_equipment_inspt_sub` ENABLE KEYS */;
UNLOCK TABLES;
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

LOCK TABLES `juva_equipment_master` WRITE;
/*!40000 ALTER TABLE `juva_equipment_master` DISABLE KEYS */;
/*!40000 ALTER TABLE `juva_equipment_master` ENABLE KEYS */;
UNLOCK TABLES;
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

LOCK TABLES `legacy_inspection_details` WRITE;
/*!40000 ALTER TABLE `legacy_inspection_details` DISABLE KEYS */;
/*!40000 ALTER TABLE `legacy_inspection_details` ENABLE KEYS */;
UNLOCK TABLES;
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

LOCK TABLES `legacy_migration_runs` WRITE;
/*!40000 ALTER TABLE `legacy_migration_runs` DISABLE KEYS */;
/*!40000 ALTER TABLE `legacy_migration_runs` ENABLE KEYS */;
UNLOCK TABLES;
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

LOCK TABLES `message_master_sub` WRITE;
/*!40000 ALTER TABLE `message_master_sub` DISABLE KEYS */;
/*!40000 ALTER TABLE `message_master_sub` ENABLE KEYS */;
UNLOCK TABLES;
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

LOCK TABLES `message_master_tab` WRITE;
/*!40000 ALTER TABLE `message_master_tab` DISABLE KEYS */;
/*!40000 ALTER TABLE `message_master_tab` ENABLE KEYS */;
UNLOCK TABLES;
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

LOCK TABLES `otp_challenges` WRITE;
/*!40000 ALTER TABLE `otp_challenges` DISABLE KEYS */;
/*!40000 ALTER TABLE `otp_challenges` ENABLE KEYS */;
UNLOCK TABLES;
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

LOCK TABLES `remember_tokens` WRITE;
/*!40000 ALTER TABLE `remember_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `remember_tokens` ENABLE KEYS */;
UNLOCK TABLES;
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

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'Super Administrator','super-admin','[\"*\"]','2026-06-27 16:26:22','2026-06-27 16:26:22'),(2,'Operations Admin','operations-admin','[\"dashboard.view\",\"clients.view\",\"clients.manage\",\"equipment.view\",\"equipment.manage\",\"categories.view\",\"categories.create\",\"categories.edit\",\"categories.change_status\",\"categories.manage\",\"inspections.view\",\"inspections.create\",\"inspections.edit\",\"certificates.view\",\"verification.view\",\"reports.view\",\"audit.view\",\"users.manage\",\"roles.view\"]','2026-06-27 16:26:22','2026-07-24 19:11:04'),(3,'Inspector','inspector','[\"dashboard.view\",\"clients.view\",\"equipment.view\",\"categories.view\",\"inspections.view\",\"inspections.create\",\"inspections.edit\",\"certificates.view\",\"verification.view\"]','2026-06-27 16:26:22','2026-06-27 16:26:22'),(4,'Reviewer / Approver','reviewer','[\"dashboard.view\",\"clients.view\",\"equipment.view\",\"categories.view\",\"inspections.view\",\"inspections.review\",\"certificates.view\",\"certificates.issue\",\"verification.view\"]','2026-06-27 16:26:22','2026-06-27 16:26:22'),(5,'Client User','client','[\"certificates.own.view\",\"certificates.own.download\",\"verification.view\"]','2026-06-27 16:26:22','2026-06-27 16:26:22');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;
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

LOCK TABLES `system_configuration` WRITE;
/*!40000 ALTER TABLE `system_configuration` DISABLE KEYS */;
/*!40000 ALTER TABLE `system_configuration` ENABLE KEYS */;
UNLOCK TABLES;
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

LOCK TABLES `system_configuration_add` WRITE;
/*!40000 ALTER TABLE `system_configuration_add` DISABLE KEYS */;
/*!40000 ALTER TABLE `system_configuration_add` ENABLE KEYS */;
UNLOCK TABLES;
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

LOCK TABLES `user_access` WRITE;
/*!40000 ALTER TABLE `user_access` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_access` ENABLE KEYS */;
UNLOCK TABLES;
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

LOCK TABLES `user_profile` WRITE;
/*!40000 ALTER TABLE `user_profile` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_profile` ENABLE KEYS */;
UNLOCK TABLES;
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

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
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

LOCK TABLES `verification_logs` WRITE;
/*!40000 ALTER TABLE `verification_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `verification_logs` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

