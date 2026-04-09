/*
 INIT - Insert Rows Only - Events Tracker - MINIMUM

 Source Server         : arcane.city
 Source Server Type    : MySQL
 Source Server Version : 80045
 Source Host           : localhost:3306
 Source Schema         : events_tracker

 Target Server Type    : MySQL
 Target Server Version : 80045
 File Encoding         : 65001

 Date: 09/04/2026 14:32:42
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for access_types
-- ----------------------------
DROP TABLE IF EXISTS `access_types`;
CREATE TABLE `access_types`  (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `label` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `level` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 6 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of access_types
-- ----------------------------
INSERT INTO `access_types` VALUES (1, 'Admin', 'Admin', 'administrator', 100, '2020-12-01 07:49:36', NULL);
INSERT INTO `access_types` VALUES (2, 'Owner', 'Owner', 'owner', 10, '2020-12-01 07:50:10', '2020-12-01 07:50:49');
INSERT INTO `access_types` VALUES (3, 'Member', 'Member', 'member', 5, '2020-12-01 07:50:37', NULL);
INSERT INTO `access_types` VALUES (4, 'Follower', 'Follower', 'follower', 2, '2020-12-01 07:51:09', NULL);
INSERT INTO `access_types` VALUES (5, 'Blocked', 'Blocked', 'blocked', 0, '2020-12-01 07:51:28', NULL);

-- ----------------------------
-- Table structure for actions
-- ----------------------------
DROP TABLE IF EXISTS `actions`;
CREATE TABLE `actions`  (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `object_table` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `child_object_table` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `description` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `changes` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `order` int NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 19 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of actions
-- ----------------------------
INSERT INTO `actions` VALUES (1, 'Create', NULL, NULL, NULL, NULL, 0, 1, NULL, NULL);
INSERT INTO `actions` VALUES (2, 'Update', NULL, NULL, NULL, NULL, 0, 1, NULL, NULL);
INSERT INTO `actions` VALUES (3, 'Delete', NULL, NULL, NULL, NULL, 0, 1, NULL, NULL);
INSERT INTO `actions` VALUES (4, 'Login', NULL, NULL, NULL, NULL, 0, 1, NULL, NULL);
INSERT INTO `actions` VALUES (5, 'Logout', NULL, NULL, NULL, NULL, 0, 1, NULL, NULL);
INSERT INTO `actions` VALUES (6, 'Follow', NULL, NULL, NULL, NULL, 0, 1, NULL, NULL);
INSERT INTO `actions` VALUES (7, 'Unfollow', NULL, NULL, NULL, NULL, 0, 1, NULL, NULL);
INSERT INTO `actions` VALUES (8, 'Attending', NULL, NULL, NULL, NULL, 0, 1, NULL, NULL);
INSERT INTO `actions` VALUES (9, 'Unattending', NULL, NULL, NULL, NULL, 0, 1, NULL, NULL);
INSERT INTO `actions` VALUES (10, 'Activate', 'User', NULL, 'Activate user', NULL, 0, 1, NULL, NULL);
INSERT INTO `actions` VALUES (11, 'Suspend', 'User', NULL, 'Suspend user', NULL, 0, 1, NULL, NULL);
INSERT INTO `actions` VALUES (12, 'Reminder', 'User', NULL, 'Email reminder', NULL, 0, 1, NULL, NULL);
INSERT INTO `actions` VALUES (13, 'Impersonate', NULL, NULL, NULL, NULL, 0, 1, NULL, NULL);
INSERT INTO `actions` VALUES (14, 'Failed Login', NULL, NULL, NULL, NULL, 0, 1, '2022-08-31 06:32:04', '2022-08-31 06:32:04');
INSERT INTO `actions` VALUES (15, 'Notification', 'User', NULL, 'Email notification', NULL, 0, 1, '2022-08-31 06:30:50', '2022-08-31 06:30:50');
INSERT INTO `actions` VALUES (16, 'Instagram Post', NULL, NULL, 'Post to instagram', NULL, 0, 1, '2025-02-10 21:16:11', '2025-02-10 21:16:11');
INSERT INTO `actions` VALUES (17, 'Password Reset Request', 'User', NULL, 'A password reset request email was sent', NULL, 0, 1, '2025-08-12 22:37:16', '2025-08-12 22:37:16');
INSERT INTO `actions` VALUES (18, 'Password Reset', 'User', NULL, 'A password was successfully reset', NULL, 0, 1, '2025-08-12 22:37:34', '2025-08-12 22:37:34');

-- ----------------------------
-- Table structure for activities
-- ----------------------------
DROP TABLE IF EXISTS `activities`;
CREATE TABLE `activities`  (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int UNSIGNED NULL DEFAULT NULL,
  `object_table` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `object_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `object_id` int NOT NULL,
  `child_object_table` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `child_object_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `child_object_id` int NULL DEFAULT NULL,
  `message` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `changes` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `action_id` int NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `ip_address` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `activities_user_id_index`(`user_id`) USING BTREE,
  INDEX `activities_object_id_index`(`object_id`) USING BTREE,
  INDEX `activities_action_id_index`(`action_id`) USING BTREE,
  INDEX `activities_object_table_index`(`object_table`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 87351 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for alias_entity
-- ----------------------------
DROP TABLE IF EXISTS `alias_entity`;
CREATE TABLE `alias_entity`  (
  `entity_id` int UNSIGNED NOT NULL,
  `alias_id` int UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE INDEX `unique_entity_alias`(`entity_id`, `alias_id`) USING BTREE,
  INDEX `alias_entity_alias_id_index`(`alias_id`) USING BTREE,
  INDEX `alias_entity_entity_id_index`(`entity_id`) USING BTREE,
  CONSTRAINT `alias_entity_alias_fk` FOREIGN KEY (`alias_id`) REFERENCES `aliases` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `alias_entity_entity_fk` FOREIGN KEY (`entity_id`) REFERENCES `entities` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for aliases
-- ----------------------------
DROP TABLE IF EXISTS `aliases`;
CREATE TABLE `aliases`  (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `created_at` datetime NULL DEFAULT NULL,
  `updated_at` datetime NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 95 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for blog_entity
-- ----------------------------
DROP TABLE IF EXISTS `blog_entity`;
CREATE TABLE `blog_entity`  (
  `blog_id` int UNSIGNED NOT NULL,
  `entity_id` int UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `blog_index`(`blog_id`) USING BTREE,
  INDEX `entity_index`(`entity_id`) USING BTREE,
  CONSTRAINT `blog_entity_blog_fk` FOREIGN KEY (`blog_id`) REFERENCES `blogs` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `blog_entity_entity_fk` FOREIGN KEY (`entity_id`) REFERENCES `entities` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for blog_tag
-- ----------------------------
DROP TABLE IF EXISTS `blog_tag`;
CREATE TABLE `blog_tag`  (
  `blog_id` int UNSIGNED NOT NULL,
  `tag_id` int UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `blog_tag_blog_fk`(`blog_id`) USING BTREE,
  INDEX `blog_tag_tag_fk`(`tag_id`) USING BTREE,
  CONSTRAINT `blog_tag_blog_fk` FOREIGN KEY (`blog_id`) REFERENCES `blogs` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `blog_tag_tag_fk` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for blogs
-- ----------------------------
DROP TABLE IF EXISTS `blogs`;
CREATE TABLE `blogs`  (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `body` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `menu_id` int UNSIGNED NULL DEFAULT NULL,
  `content_type_id` int UNSIGNED NULL DEFAULT NULL,
  `visibility_id` int UNSIGNED NULL DEFAULT NULL,
  `sort_order` int NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_admin` tinyint(1) NOT NULL DEFAULT 0,
  `allow_html` tinyint(1) NOT NULL DEFAULT 0,
  `created_by` int NOT NULL DEFAULT 1,
  `updated_by` int NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 3 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for click_tracks
-- ----------------------------
DROP TABLE IF EXISTS `click_tracks`;
CREATE TABLE `click_tracks`  (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `event_id` bigint UNSIGNED NULL DEFAULT NULL,
  `user_id` bigint UNSIGNED NULL DEFAULT NULL,
  `venue_id` bigint UNSIGNED NULL DEFAULT NULL,
  `promoter_id` bigint UNSIGNED NULL DEFAULT NULL,
  `tags` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `user_agent` varchar(512) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `referrer` varchar(512) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `country_code` varchar(2) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `city` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `clicked_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `click_tracks_event_id_index`(`event_id`) USING BTREE,
  INDEX `click_tracks_venue_id_index`(`venue_id`) USING BTREE,
  INDEX `click_tracks_promoter_id_index`(`promoter_id`) USING BTREE,
  INDEX `click_tracks_user_id_index`(`user_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 52087 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for comments
-- ----------------------------
DROP TABLE IF EXISTS `comments`;
CREATE TABLE `comments`  (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `message` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `response_to` int UNSIGNED NULL DEFAULT NULL,
  `commentable_id` int UNSIGNED NULL DEFAULT NULL,
  `commentable_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `created_by` int NOT NULL DEFAULT 1,
  `updated_by` int NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `comments_response_to_foreign`(`response_to`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 113 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for contact_entity
-- ----------------------------
DROP TABLE IF EXISTS `contact_entity`;
CREATE TABLE `contact_entity`  (
  `entity_id` int UNSIGNED NOT NULL,
  `contact_id` int UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `contact_entity_entity_id_index`(`entity_id`) USING BTREE,
  INDEX `contact_entity_contact_id_index`(`contact_id`) USING BTREE,
  CONSTRAINT `contact_entity_contact_fk` FOREIGN KEY (`contact_id`) REFERENCES `contacts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `contact_entity_entity_fk` FOREIGN KEY (`entity_id`) REFERENCES `entities` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for contacts
-- ----------------------------
DROP TABLE IF EXISTS `contacts`;
CREATE TABLE `contacts`  (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `phone` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `other` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `visibility_id` int UNSIGNED NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `contacts_visibility_index`(`visibility_id`) USING BTREE,
  CONSTRAINT `contacts_visibility_fk` FOREIGN KEY (`visibility_id`) REFERENCES `visibilities` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE = InnoDB AUTO_INCREMENT = 53 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for content_types
-- ----------------------------
DROP TABLE IF EXISTS `content_types`;
CREATE TABLE `content_types`  (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 3 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of content_types
-- ----------------------------
INSERT INTO `content_types` VALUES (1, 'Plain Text', NULL, NULL);
INSERT INTO `content_types` VALUES (2, 'HTML', NULL, NULL);

-- ----------------------------
-- Table structure for entities
-- ----------------------------
DROP TABLE IF EXISTS `entities`;
CREATE TABLE `entities`  (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `short` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `entity_type_id` int UNSIGNED NULL DEFAULT NULL,
  `entity_status_id` int UNSIGNED NULL DEFAULT NULL,
  `created_by` int NOT NULL DEFAULT 1,
  `updated_by` int NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `facebook_username` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `twitter_username` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `started_at` timestamp NULL DEFAULT NULL,
  `instagram_username` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `entities_entity_type_id_index`(`entity_type_id`) USING BTREE,
  INDEX `entities_entity_status_id_index`(`entity_status_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1516 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for entity_event
-- ----------------------------
DROP TABLE IF EXISTS `entity_event`;
CREATE TABLE `entity_event`  (
  `event_id` int UNSIGNED NOT NULL,
  `entity_id` int UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `entity_event_event_id_index`(`event_id`) USING BTREE,
  INDEX `entity_event_entity_id_index`(`entity_id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for entity_link
-- ----------------------------
DROP TABLE IF EXISTS `entity_link`;
CREATE TABLE `entity_link`  (
  `entity_id` int UNSIGNED NOT NULL,
  `link_id` int UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `entity_link_entity_id_index`(`entity_id`) USING BTREE,
  INDEX `entity_link_link_id_index`(`link_id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for entity_permission
-- ----------------------------
DROP TABLE IF EXISTS `entity_permission`;
CREATE TABLE `entity_permission`  (
  `entity_id` int UNSIGNED NOT NULL,
  `permission_id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `entity_permission_entity_id_index`(`entity_id`) USING BTREE,
  INDEX `entity_permission_permission_id_index`(`permission_id`) USING BTREE,
  INDEX `entity_permission_user_id_index`(`user_id`) USING BTREE,
  CONSTRAINT `entity_permission_entity_fk` FOREIGN KEY (`entity_id`) REFERENCES `entities` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `entity_permission_permission_fk` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for entity_photo
-- ----------------------------
DROP TABLE IF EXISTS `entity_photo`;
CREATE TABLE `entity_photo`  (
  `entity_id` int UNSIGNED NOT NULL,
  `photo_id` int UNSIGNED NOT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `is_approved` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `entity_photo_entity_id_index`(`entity_id`) USING BTREE,
  INDEX `entity_photo_photo_id_index`(`photo_id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for entity_post
-- ----------------------------
DROP TABLE IF EXISTS `entity_post`;
CREATE TABLE `entity_post`  (
  `entity_id` int UNSIGNED NULL DEFAULT NULL,
  `post_id` int UNSIGNED NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for entity_relation
-- ----------------------------
DROP TABLE IF EXISTS `entity_relation`;
CREATE TABLE `entity_relation`  (
  `id` int NOT NULL DEFAULT 0,
  `relation_id` int NOT NULL DEFAULT 0,
  `entity_id` int NOT NULL DEFAULT 0,
  `target_id` int NOT NULL DEFAULT 0,
  `rank` int NOT NULL DEFAULT 0,
  `relation_status_id` int NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `entity_relation_relation_id_index`(`relation_id`) USING BTREE,
  INDEX `entity_relation_entity_id_index`(`entity_id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for entity_role
-- ----------------------------
DROP TABLE IF EXISTS `entity_role`;
CREATE TABLE `entity_role`  (
  `role_id` int UNSIGNED NOT NULL,
  `entity_id` int UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `entity_role_role_id_index`(`role_id`) USING BTREE,
  INDEX `entity_role_entity_id_index`(`entity_id`) USING BTREE,
  CONSTRAINT `entity_role_role_fk` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for entity_series
-- ----------------------------
DROP TABLE IF EXISTS `entity_series`;
CREATE TABLE `entity_series`  (
  `series_id` int UNSIGNED NOT NULL,
  `entity_id` int UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `entity_series_series_id_index`(`series_id`) USING BTREE,
  INDEX `entity_series_entity_id_index`(`entity_id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for entity_statuses
-- ----------------------------
DROP TABLE IF EXISTS `entity_statuses`;
CREATE TABLE `entity_statuses`  (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 5 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of entity_statuses
-- ----------------------------
INSERT INTO `entity_statuses` VALUES (1, 'Draft', '2016-02-25 07:54:14', '2016-02-25 07:54:14');
INSERT INTO `entity_statuses` VALUES (2, 'Active', '2016-02-25 07:54:14', '2016-02-25 07:54:14');
INSERT INTO `entity_statuses` VALUES (3, 'Inactive', '2016-02-25 07:54:14', '2016-02-25 07:54:14');
INSERT INTO `entity_statuses` VALUES (4, 'Unlisted', '2024-05-22 21:04:32', '2024-05-22 21:04:32');

-- ----------------------------
-- Table structure for entity_tag
-- ----------------------------
DROP TABLE IF EXISTS `entity_tag`;
CREATE TABLE `entity_tag`  (
  `entity_id` int UNSIGNED NOT NULL,
  `tag_id` int UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `entity_tag_entity_id_index`(`entity_id`) USING BTREE,
  INDEX `entity_tag_tag_id_index`(`tag_id`) USING BTREE,
  CONSTRAINT `entity_tag_entity_fk` FOREIGN KEY (`entity_id`) REFERENCES `entities` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `entity_tag_tag_fk` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for entity_thread
-- ----------------------------
DROP TABLE IF EXISTS `entity_thread`;
CREATE TABLE `entity_thread`  (
  `entity_id` int UNSIGNED NOT NULL,
  `thread_id` int UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `entity_thread_entity_id_index`(`entity_id`) USING BTREE,
  INDEX `entity_thread_thread_id_index`(`thread_id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for entity_type_permission
-- ----------------------------
DROP TABLE IF EXISTS `entity_type_permission`;
CREATE TABLE `entity_type_permission`  (
  `entity_id` int UNSIGNED NOT NULL,
  `permission_id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `entity_type_permission_entity_id_index`(`entity_id`) USING BTREE,
  INDEX `entity_type_permission_permission_id_index`(`permission_id`) USING BTREE,
  INDEX `entity_type_permission_user_id_index`(`user_id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for entity_types
-- ----------------------------
DROP TABLE IF EXISTS `entity_types`;
CREATE TABLE `entity_types`  (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `short` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 5 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of entity_types
-- ----------------------------
INSERT INTO `entity_types` VALUES (1, 'Space', 'space', 'Space for events', '2016-02-25 07:54:14', '2016-02-25 07:54:14');
INSERT INTO `entity_types` VALUES (2, 'Group', 'group', 'Collection of individuals', '2016-02-25 07:54:14', '2016-02-25 07:54:14');
INSERT INTO `entity_types` VALUES (3, 'Individual', 'individual', 'Single individual', '2016-02-25 07:54:14', '2016-02-25 07:54:14');
INSERT INTO `entity_types` VALUES (4, 'Interest', 'interest', 'Interest or topic', '2016-02-25 07:54:14', '2016-02-25 07:54:14');

-- ----------------------------
-- Table structure for event_link
-- ----------------------------
DROP TABLE IF EXISTS `event_link`;
CREATE TABLE `event_link`  (
  `event_id` int UNSIGNED NOT NULL,
  `link_id` int UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `event_link_event_id_index`(`event_id`) USING BTREE,
  INDEX `event_link_link_id_index`(`link_id`) USING BTREE,
  CONSTRAINT `event_link_event_fk` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `event_link_link_fk` FOREIGN KEY (`link_id`) REFERENCES `links` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for event_permission
-- ----------------------------
DROP TABLE IF EXISTS `event_permission`;
CREATE TABLE `event_permission`  (
  `event_id` int UNSIGNED NOT NULL,
  `permission_id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `event_permission_event_id_index`(`event_id`) USING BTREE,
  INDEX `event_permission_permission_id_index`(`permission_id`) USING BTREE,
  INDEX `event_permission_user_id_index`(`user_id`) USING BTREE,
  CONSTRAINT `event_permission_event_fk` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `event_permission_permission_fk` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for event_photo
-- ----------------------------
DROP TABLE IF EXISTS `event_photo`;
CREATE TABLE `event_photo`  (
  `event_id` int UNSIGNED NOT NULL,
  `photo_id` int UNSIGNED NOT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `is_approved` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `event_photo_event_id_index`(`event_id`) USING BTREE,
  INDEX `event_photo_photo_id_index`(`photo_id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for event_responses
-- ----------------------------
DROP TABLE IF EXISTS `event_responses`;
CREATE TABLE `event_responses`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `event_id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `response_type_id` int UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `event_responses_event_id_index`(`event_id`) USING BTREE,
  INDEX `event_responses_user_id_index`(`user_id`) USING BTREE,
  INDEX `event_responses_response_type_id_index`(`response_type_id`) USING BTREE,
  CONSTRAINT `event_responses_event_id_fk` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `event_responses_response_type_id_fk` FOREIGN KEY (`response_type_id`) REFERENCES `response_types` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `event_responses_user_id_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB AUTO_INCREMENT = 2597 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for event_reviews
-- ----------------------------
DROP TABLE IF EXISTS `event_reviews`;
CREATE TABLE `event_reviews`  (
  `event_id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `review_type_id` int UNSIGNED NOT NULL,
  `attended` tinyint(1) NOT NULL,
  `confirmed` tinyint(1) NULL DEFAULT 0,
  `expectation` tinyint NULL DEFAULT NULL,
  `rating` tinyint NULL DEFAULT NULL,
  `review` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `id` int NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `event_reviews_event_id_index`(`event_id`) USING BTREE,
  INDEX `event_reviews_user_id_index`(`user_id`) USING BTREE,
  INDEX `event_reviews_review_type_id_index`(`review_type_id`) USING BTREE,
  CONSTRAINT `event_reviews_event_fk` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `event_reviews_review_type_fk` FOREIGN KEY (`review_type_id`) REFERENCES `review_types` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `event_reviews_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB AUTO_INCREMENT = 9 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for event_shares
-- ----------------------------
DROP TABLE IF EXISTS `event_shares`;
CREATE TABLE `event_shares`  (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `event_id` int UNSIGNED NULL DEFAULT NULL,
  `platform` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `platform_id` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `created_by` int NULL DEFAULT NULL,
  `created_at` datetime NULL DEFAULT NULL,
  `posted_at` datetime NULL DEFAULT NULL,
  `updated_at` datetime NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `event_shares_event_id_index`(`event_id`) USING BTREE,
  INDEX `event_shares_created_by_index`(`created_by`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 2580 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for event_statuses
-- ----------------------------
DROP TABLE IF EXISTS `event_statuses`;
CREATE TABLE `event_statuses`  (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 9 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of event_statuses
-- ----------------------------
INSERT INTO `event_statuses` VALUES (1, 'Draft', '2016-02-25 07:54:14', '2016-02-25 07:54:14');
INSERT INTO `event_statuses` VALUES (2, 'Proposal', '2016-02-25 07:54:14', '2016-02-25 07:54:14');
INSERT INTO `event_statuses` VALUES (3, 'Approved', '2016-02-25 07:54:14', '2016-02-25 07:54:14');
INSERT INTO `event_statuses` VALUES (4, 'Happening Now', '2016-02-25 07:54:14', '2016-02-25 07:54:14');
INSERT INTO `event_statuses` VALUES (5, 'Past', '2016-02-25 07:54:14', '2016-02-25 07:54:14');
INSERT INTO `event_statuses` VALUES (6, 'Rejected', '2016-02-25 07:54:14', '2016-02-25 07:54:14');
INSERT INTO `event_statuses` VALUES (7, 'Cancelled', '2016-02-25 07:54:14', '2016-02-25 07:54:14');
INSERT INTO `event_statuses` VALUES (8, 'Unlisted', '2024-05-22 21:04:51', '2024-05-22 21:04:51');

-- ----------------------------
-- Table structure for event_tag
-- ----------------------------
DROP TABLE IF EXISTS `event_tag`;
CREATE TABLE `event_tag`  (
  `event_id` int UNSIGNED NOT NULL,
  `tag_id` int UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `event_tag_event_id_index`(`event_id`) USING BTREE,
  INDEX `event_tag_tag_id_index`(`tag_id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for event_types
-- ----------------------------
DROP TABLE IF EXISTS `event_types`;
CREATE TABLE `event_types`  (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `short` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 32 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of event_types
-- ----------------------------
INSERT INTO `event_types` VALUES (1, 'Art Opening', 'art-opening', NULL, '2016-02-25 07:54:14', '2016-02-25 07:54:14');
INSERT INTO `event_types` VALUES (2, 'Concert', 'concert', NULL, '2016-02-25 07:54:14', '2016-02-25 07:54:14');
INSERT INTO `event_types` VALUES (3, 'Festival', 'festival', NULL, '2016-02-25 07:54:14', '2016-02-25 07:54:14');
INSERT INTO `event_types` VALUES (4, 'House Show', 'house-show', NULL, '2016-02-25 07:54:14', '2016-02-25 07:54:14');
INSERT INTO `event_types` VALUES (5, 'Club Night', 'club-night', NULL, '2016-02-25 07:54:14', '2016-02-25 07:54:14');
INSERT INTO `event_types` VALUES (6, 'Film Screening', 'film-screening', NULL, '2016-03-18 11:43:02', '2016-03-18 11:43:07');
INSERT INTO `event_types` VALUES (7, 'Radio Show', 'radio-show', NULL, '2016-03-18 11:43:19', '2016-03-18 11:43:21');
INSERT INTO `event_types` VALUES (8, 'Rave', 'rave', NULL, '2016-03-28 18:20:51', '2016-03-28 18:20:54');
INSERT INTO `event_types` VALUES (9, 'Benefit', 'benefit', NULL, '2016-04-19 15:02:54', '2016-03-28 18:20:54');
INSERT INTO `event_types` VALUES (10, 'Renegade', 'renegade', NULL, '2016-06-16 11:01:36', '2016-03-28 18:20:54');
INSERT INTO `event_types` VALUES (11, 'Pop-up', 'pop-up', NULL, '2016-06-16 11:01:50', '2016-03-28 18:20:54');
INSERT INTO `event_types` VALUES (12, 'Activism', 'activism', NULL, '2017-01-31 15:40:50', '2016-03-28 18:20:54');
INSERT INTO `event_types` VALUES (13, 'Open Mic', 'open-mic', NULL, '2016-03-28 18:20:54', '2016-03-28 18:20:54');
INSERT INTO `event_types` VALUES (14, 'Karaoke', 'karaoke', NULL, '2016-03-28 18:20:54', '2016-03-28 18:20:54');
INSERT INTO `event_types` VALUES (15, 'Workshop', 'workshop', NULL, '2016-03-28 18:20:54', '2016-03-28 18:20:54');
INSERT INTO `event_types` VALUES (16, 'Live Stream', 'live-stream', NULL, '2016-03-28 18:20:54', '2016-03-28 18:20:54');
INSERT INTO `event_types` VALUES (17, 'Drag Show', 'drag', NULL, '2022-03-15 19:36:26', '2022-03-15 19:36:26');
INSERT INTO `event_types` VALUES (18, 'Comedy', 'comedy', NULL, '2022-08-03 19:30:09', '2022-08-03 19:30:09');
INSERT INTO `event_types` VALUES (19, 'Theater', 'theater', NULL, '2025-06-09 15:32:10', '2025-06-09 15:32:10');
INSERT INTO `event_types` VALUES (20, 'Dance Performance', 'dance-performance', NULL, '2025-06-09 15:32:20', '2025-06-09 15:32:20');
INSERT INTO `event_types` VALUES (21, 'Taxidermy Workshop', 'taxidermy', NULL, '2025-07-10 16:49:56', '2025-07-10 16:49:56');
INSERT INTO `event_types` VALUES (22, 'Reading', 'reading', NULL, '2025-11-13 07:10:56', '2025-11-13 07:10:56');
INSERT INTO `event_types` VALUES (31, 'Lecture', 'leacture', NULL, '2026-01-29 03:41:24', '2026-01-29 03:41:24');

-- ----------------------------
-- Table structure for events
-- ----------------------------
DROP TABLE IF EXISTS `events`;
CREATE TABLE `events`  (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `short` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `visibility_id` int UNSIGNED NULL DEFAULT NULL,
  `event_status_id` int UNSIGNED NULL DEFAULT NULL,
  `event_type_id` int UNSIGNED NULL DEFAULT NULL,
  `is_benefit` tinyint(1) NOT NULL DEFAULT 0,
  `promoter_id` int UNSIGNED NULL DEFAULT NULL,
  `venue_id` int UNSIGNED NULL DEFAULT NULL,
  `attending` int UNSIGNED NOT NULL DEFAULT 0,
  `like` int UNSIGNED NOT NULL DEFAULT 0,
  `presale_price` decimal(5, 2) NULL DEFAULT NULL,
  `door_price` decimal(5, 2) NULL DEFAULT NULL,
  `soundcheck_at` timestamp NULL DEFAULT NULL,
  `door_at` timestamp NULL DEFAULT NULL,
  `start_at` timestamp NULL DEFAULT NULL,
  `end_at` timestamp NULL DEFAULT NULL,
  `min_age` tinyint UNSIGNED NULL DEFAULT NULL,
  `series_id` int UNSIGNED NULL DEFAULT NULL,
  `primary_link` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `ticket_link` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `created_by` int NOT NULL DEFAULT 1,
  `updated_by` int NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `do_not_repost` tinyint(1) NULL DEFAULT 0,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `idx_slug`(`slug`) USING BTREE,
  INDEX `events_event_status_id`(`event_status_id`) USING BTREE,
  INDEX `events_promoter_id`(`promoter_id`) USING BTREE,
  INDEX `events_venue_id`(`venue_id`) USING BTREE,
  INDEX `events_visibility_id`(`visibility_id`) USING BTREE,
  INDEX `events_event_type_id`(`event_type_id`) USING BTREE,
  INDEX `events_created_by`(`created_by`) USING BTREE,
  INDEX `events_series_id`(`series_id`) USING BTREE,
  INDEX `idx_start_at`(`start_at`) USING BTREE,
  INDEX `idx_visibility_start`(`visibility_id`, `start_at`) USING BTREE,
  INDEX `idx_venue_start`(`venue_id`, `start_at`) USING BTREE,
  CONSTRAINT `event_event_status_fk` FOREIGN KEY (`event_status_id`) REFERENCES `event_statuses` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `event_event_type_fk` FOREIGN KEY (`event_type_id`) REFERENCES `event_types` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `event_promoter_fk` FOREIGN KEY (`promoter_id`) REFERENCES `entities` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `event_series_fk` FOREIGN KEY (`series_id`) REFERENCES `series` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT,
  CONSTRAINT `event_venue_fk` FOREIGN KEY (`venue_id`) REFERENCES `entities` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `event_visibility_fk` FOREIGN KEY (`visibility_id`) REFERENCES `visibilities` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE = InnoDB AUTO_INCREMENT = 7876 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for follows
-- ----------------------------
DROP TABLE IF EXISTS `follows`;
CREATE TABLE `follows`  (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int UNSIGNED NOT NULL,
  `object_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `object_id` int UNSIGNED NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `follows_user_id_index`(`user_id`) USING BTREE,
  INDEX `follows_object_type_index`(`object_type`) USING BTREE,
  INDEX `follows_object_id_index`(`object_id`) USING BTREE,
  CONSTRAINT `follows_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB AUTO_INCREMENT = 978 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for forums
-- ----------------------------
DROP TABLE IF EXISTS `forums`;
CREATE TABLE `forums`  (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `visibility_id` int UNSIGNED NULL DEFAULT NULL,
  `sort_order` int NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` int NOT NULL DEFAULT 1,
  `updated_by` int NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `forums_visibility_index`(`visibility_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 2 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of forums
-- ----------------------------
INSERT INTO `forums` VALUES (1, 'Forum', 'forum', 'General forum', 3, 0, 1, 1, 1, '2017-06-05 13:50:27', '2017-06-05 13:50:27');

-- ----------------------------
-- Table structure for group_permission
-- ----------------------------
DROP TABLE IF EXISTS `group_permission`;
CREATE TABLE `group_permission`  (
  `group_id` int UNSIGNED NOT NULL,
  `permission_id` int UNSIGNED NOT NULL,
  INDEX `group_permission_permission_id_foreign`(`permission_id`) USING BTREE,
  INDEX `group_permission_group_id_foreign`(`group_id`) USING BTREE,
  CONSTRAINT `group_permission_group_id_foreign` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `group_permission_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of group_permission
-- ----------------------------
INSERT INTO `group_permission` VALUES (1, 18);
INSERT INTO `group_permission` VALUES (1, 16);
INSERT INTO `group_permission` VALUES (1, 14);
INSERT INTO `group_permission` VALUES (1, 19);
INSERT INTO `group_permission` VALUES (1, 20);
INSERT INTO `group_permission` VALUES (1, 21);
INSERT INTO `group_permission` VALUES (1, 17);
INSERT INTO `group_permission` VALUES (1, 11);
INSERT INTO `group_permission` VALUES (1, 12);

-- ----------------------------
-- Table structure for group_user
-- ----------------------------
DROP TABLE IF EXISTS `group_user`;
CREATE TABLE `group_user`  (
  `group_id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  INDEX `group_user_group_id_foreign`(`group_id`) USING BTREE,
  INDEX `group_user_user_fk`(`user_id`) USING BTREE,
  CONSTRAINT `group_user_group_fk` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `group_user_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for groups
-- ----------------------------
DROP TABLE IF EXISTS `groups`;
CREATE TABLE `groups`  (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `label` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `level` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 3 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of groups
-- ----------------------------
INSERT INTO `groups` VALUES (1, 'admin', 'Admin', 100, '2017-05-19 01:57:45', '2017-05-19 01:57:45', '');
INSERT INTO `groups` VALUES (2, 'super_admin', 'Super Admin', 999, '2017-06-20 12:53:25', '2017-06-20 12:53:25', 'Super admin');

-- ----------------------------
-- Table structure for likes
-- ----------------------------
DROP TABLE IF EXISTS `likes`;
CREATE TABLE `likes`  (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int UNSIGNED NOT NULL,
  `object_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `object_id` int UNSIGNED NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `like_user_index`(`user_id`) USING BTREE,
  INDEX `like_object_type_index`(`object_type`) USING BTREE,
  INDEX `like_object_id_index`(`object_id`) USING BTREE,
  CONSTRAINT `likes_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB AUTO_INCREMENT = 101 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for link_user
-- ----------------------------
DROP TABLE IF EXISTS `link_user`;
CREATE TABLE `link_user`  (
  `user_id` int UNSIGNED NOT NULL,
  `link_id` int UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `link_user_user_id_index`(`user_id`) USING BTREE,
  INDEX `link_user_link_id_index`(`link_id`) USING BTREE,
  CONSTRAINT `link_user_link_fk` FOREIGN KEY (`link_id`) REFERENCES `links` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `link_user_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for links
-- ----------------------------
DROP TABLE IF EXISTS `links`;
CREATE TABLE `links`  (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `text` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `image` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `api` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `confirm` tinyint(1) NOT NULL DEFAULT 0,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 563 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for location_types
-- ----------------------------
DROP TABLE IF EXISTS `location_types`;
CREATE TABLE `location_types`  (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 7 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of location_types
-- ----------------------------
INSERT INTO `location_types` VALUES (1, 'Public', '2016-02-25 07:54:14', '2016-02-25 07:54:14');
INSERT INTO `location_types` VALUES (2, 'Business', '2016-02-25 07:54:14', '2016-02-25 07:54:14');
INSERT INTO `location_types` VALUES (3, 'Home', '2016-02-25 07:54:14', '2016-02-25 07:54:14');
INSERT INTO `location_types` VALUES (4, 'Outdoor', '2016-02-25 07:54:14', '2016-02-25 07:54:14');
INSERT INTO `location_types` VALUES (5, 'Gallery', '2016-03-17 13:36:13', '2016-03-17 13:36:18');
INSERT INTO `location_types` VALUES (6, 'DIY', '2016-07-08 11:37:20', '2016-07-08 11:37:20');

-- ----------------------------
-- Table structure for locations
-- ----------------------------
DROP TABLE IF EXISTS `locations`;
CREATE TABLE `locations`  (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `attn` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `address_one` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `address_two` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `city` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `neighborhood` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `state` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `postcode` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `country` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `latitude` decimal(11, 8) NULL DEFAULT NULL,
  `longitude` decimal(11, 8) NULL DEFAULT NULL,
  `location_type_id` int UNSIGNED NOT NULL,
  `entity_id` int UNSIGNED NOT NULL,
  `capacity` int NULL DEFAULT NULL,
  `map_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `created_by` int NOT NULL DEFAULT 1,
  `updated_by` int NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `visibility_id` int NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `locations_location_type_id_index`(`location_type_id`) USING BTREE,
  INDEX `locations_entity_id_index`(`entity_id`) USING BTREE,
  CONSTRAINT `location_location_type_fk` FOREIGN KEY (`location_type_id`) REFERENCES `location_types` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB AUTO_INCREMENT = 267 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for menus
-- ----------------------------
DROP TABLE IF EXISTS `menus`;
CREATE TABLE `menus`  (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `body` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `menu_parent_id` int UNSIGNED NULL DEFAULT NULL,
  `visibility_id` int UNSIGNED NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `menu_visibility_fk`(`visibility_id`) USING BTREE,
  CONSTRAINT `menu_visibility_fk` FOREIGN KEY (`visibility_id`) REFERENCES `visibilities` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB AUTO_INCREMENT = 2 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for migrations
-- ----------------------------
DROP TABLE IF EXISTS `migrations`;
CREATE TABLE `migrations`  (
  `migration` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for occurrence_days
-- ----------------------------
DROP TABLE IF EXISTS `occurrence_days`;
CREATE TABLE `occurrence_days`  (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 8 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of occurrence_days
-- ----------------------------
INSERT INTO `occurrence_days` VALUES (1, 'Sunday');
INSERT INTO `occurrence_days` VALUES (2, 'Monday');
INSERT INTO `occurrence_days` VALUES (3, 'Tuesday');
INSERT INTO `occurrence_days` VALUES (4, 'Wednesday');
INSERT INTO `occurrence_days` VALUES (5, 'Thursday');
INSERT INTO `occurrence_days` VALUES (6, 'Friday');
INSERT INTO `occurrence_days` VALUES (7, 'Saturday');

-- ----------------------------
-- Table structure for occurrence_types
-- ----------------------------
DROP TABLE IF EXISTS `occurrence_types`;
CREATE TABLE `occurrence_types`  (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 7 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of occurrence_types
-- ----------------------------
INSERT INTO `occurrence_types` VALUES (1, 'No Schedule', '2016-02-25 07:54:14', '2016-02-25 07:54:14');
INSERT INTO `occurrence_types` VALUES (2, 'Weekly', '2016-02-25 07:54:14', '2016-02-25 07:54:14');
INSERT INTO `occurrence_types` VALUES (3, 'Biweekly', '2016-02-25 07:54:14', '2016-02-25 07:54:14');
INSERT INTO `occurrence_types` VALUES (4, 'Monthly', '2016-02-25 07:54:14', '2016-02-25 07:54:14');
INSERT INTO `occurrence_types` VALUES (5, 'Bimonthly', '2016-02-25 07:54:14', '2016-02-25 07:54:14');
INSERT INTO `occurrence_types` VALUES (6, 'Yearly', '2016-02-25 07:54:14', '2016-02-25 07:54:14');

-- ----------------------------
-- Table structure for occurrence_weeks
-- ----------------------------
DROP TABLE IF EXISTS `occurrence_weeks`;
CREATE TABLE `occurrence_weeks`  (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 6 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of occurrence_weeks
-- ----------------------------
INSERT INTO `occurrence_weeks` VALUES (1, 'First', '2016-02-25 07:54:15', '2016-02-25 07:54:15');
INSERT INTO `occurrence_weeks` VALUES (2, 'Second', '2016-02-25 07:54:15', '2016-02-25 07:54:15');
INSERT INTO `occurrence_weeks` VALUES (3, 'Third', '2016-02-25 07:54:15', '2016-02-25 07:54:15');
INSERT INTO `occurrence_weeks` VALUES (4, 'Fourth', '2016-02-25 07:54:15', '2016-02-25 07:54:15');
INSERT INTO `occurrence_weeks` VALUES (5, 'Last', '2016-02-25 07:54:15', '2016-02-25 07:54:15');

-- ----------------------------
-- Table structure for password_resets
-- ----------------------------
DROP TABLE IF EXISTS `password_resets`;
CREATE TABLE `password_resets`  (
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `password_resets_email_index`(`email`) USING BTREE,
  INDEX `password_resets_token_index`(`token`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for permissions
-- ----------------------------
DROP TABLE IF EXISTS `permissions`;
CREATE TABLE `permissions`  (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `label` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `level` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 25 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of permissions
-- ----------------------------
INSERT INTO `permissions` VALUES (1, 'show_user', 'Show User', 'Show User', 1, '2017-06-04 23:47:11', '2017-06-04 23:47:11');
INSERT INTO `permissions` VALUES (2, 'edit_user', 'Edit User', 'Edit User', 10, '2017-06-04 23:47:11', '2017-06-04 23:47:11');
INSERT INTO `permissions` VALUES (3, 'show_event', 'Show Event', 'Show Event', 1, '2017-06-04 23:47:11', '2017-06-04 23:47:11');
INSERT INTO `permissions` VALUES (4, 'edit_event', 'Edit Event', 'Edit Event', 10, '2017-06-04 23:47:11', '2017-06-04 23:47:11');
INSERT INTO `permissions` VALUES (5, 'show_tag', 'Show Tag', 'Show Tag', 1, '2017-06-04 23:47:11', '2017-06-04 23:47:11');
INSERT INTO `permissions` VALUES (6, 'edit_tag', 'Edit Tag', 'Edit Tag', 10, '2017-06-04 23:47:11', '2017-06-04 23:47:11');
INSERT INTO `permissions` VALUES (7, 'show_series', 'Show Series', 'Show Series', 1, '2017-06-04 23:47:11', '2017-06-04 23:47:11');
INSERT INTO `permissions` VALUES (8, 'edit_series', 'Edit Series', 'Edit Series', 10, '2017-06-04 23:47:11', '2017-06-04 23:47:11');
INSERT INTO `permissions` VALUES (9, 'show_entity', 'Show Entity', 'Show Entity', 1, '2017-06-04 23:47:11', '2017-06-04 23:47:11');
INSERT INTO `permissions` VALUES (10, 'edit_entity', 'Edit Entity', 'Edit Entity', 10, '2017-06-04 23:47:11', '2017-06-04 23:47:11');
INSERT INTO `permissions` VALUES (11, 'show_forum', 'Show Forum', 'Show Forum', 1, '2017-06-04 23:47:11', '2017-06-04 23:47:11');
INSERT INTO `permissions` VALUES (12, 'edit_forum', 'Edit Forum', 'Edit Forum', 10, '2017-06-04 23:47:11', '2017-06-04 23:47:11');
INSERT INTO `permissions` VALUES (13, 'show_permission', 'Show Permission', 'Show Permission', 1, '2017-06-04 23:47:11', '2017-06-04 23:47:11');
INSERT INTO `permissions` VALUES (14, 'edit_permission', 'Edit Permission', 'Edit Permission', 10, '2017-06-04 23:47:11', '2017-06-04 23:47:11');
INSERT INTO `permissions` VALUES (15, 'show_group', 'Show Group', 'Show Group', 1, '2017-06-04 23:47:11', '2017-06-04 23:47:11');
INSERT INTO `permissions` VALUES (16, 'edit_group', 'Edit Group', 'Edit Group', 10, '2017-06-04 23:47:11', '2017-06-04 23:47:11');
INSERT INTO `permissions` VALUES (17, 'show_activity', 'Show Activity', 'Show Activity', 1, '2017-06-04 23:47:11', '2017-06-04 23:47:11');
INSERT INTO `permissions` VALUES (18, 'show_admin', 'Show Admin', 'Show Admin', 100, '2017-06-04 23:47:11', '2017-06-04 23:47:11');
INSERT INTO `permissions` VALUES (19, 'trust_post', 'Trust Post', 'Trust Post', 90, '2017-06-04 23:47:11', '2017-06-04 23:47:11');
INSERT INTO `permissions` VALUES (20, 'trust_thread', 'Trust Thread', 'Trust Thread', 90, '2017-06-04 23:47:11', '2017-06-04 23:47:11');
INSERT INTO `permissions` VALUES (21, 'grant_access', 'Grant Access', 'Grant Access', 999, '2017-06-04 23:47:11', '2017-06-04 23:47:11');
INSERT INTO `permissions` VALUES (22, 'grant_event_ownership', 'Grant Event Ownership', 'Grant Event Ownership', 100, '2017-06-04 23:47:11', '2017-06-04 23:47:11');
INSERT INTO `permissions` VALUES (23, 'grant_entity_ownership', 'Grant Entity Ownership', 'Grant Entity Ownership', 100, '2017-06-04 23:47:11', '2017-06-04 23:47:11');
INSERT INTO `permissions` VALUES (24, 'grant_series_ownership', 'Grant Series Ownership', 'Grant Series Ownership', 100, '2017-06-04 23:47:11', '2017-06-04 23:47:11');

-- ----------------------------
-- Table structure for personal_access_tokens
-- ----------------------------
DROP TABLE IF EXISTS `personal_access_tokens`;
CREATE TABLE `personal_access_tokens`  (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `personal_access_tokens_token_unique`(`token`) USING BTREE,
  INDEX `personal_access_tokens_tokenable_type_tokenable_id_index`(`tokenable_type`, `tokenable_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 116 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for photo_series
-- ----------------------------
DROP TABLE IF EXISTS `photo_series`;
CREATE TABLE `photo_series`  (
  `series_id` int UNSIGNED NOT NULL,
  `photo_id` int UNSIGNED NOT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `is_approved` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `photo_series_series_id_index`(`series_id`) USING BTREE,
  INDEX `photo_series_photo_id_index`(`photo_id`) USING BTREE,
  CONSTRAINT `photo_series_photo_fk` FOREIGN KEY (`photo_id`) REFERENCES `photos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `photo_series_series_fk` FOREIGN KEY (`series_id`) REFERENCES `series` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for photo_user
-- ----------------------------
DROP TABLE IF EXISTS `photo_user`;
CREATE TABLE `photo_user`  (
  `user_id` int UNSIGNED NOT NULL,
  `photo_id` int UNSIGNED NOT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `is_approved` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `photo_user_user_id_index`(`user_id`) USING BTREE,
  INDEX `photo_user_photo_id_index`(`photo_id`) USING BTREE,
  CONSTRAINT `photo_user_photo_fk` FOREIGN KEY (`photo_id`) REFERENCES `photos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `photo_user_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for photos
-- ----------------------------
DROP TABLE IF EXISTS `photos`;
CREATE TABLE `photos`  (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `thumbnail` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `caption` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_public` tinyint(1) NOT NULL DEFAULT 1,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `is_event` tinyint(1) NOT NULL DEFAULT 0,
  `is_approved` tinyint(1) NOT NULL DEFAULT 0,
  `created_by` int NOT NULL DEFAULT 1,
  `updated_by` int NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 10717 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for post_tag
-- ----------------------------
DROP TABLE IF EXISTS `post_tag`;
CREATE TABLE `post_tag`  (
  `tag_id` int UNSIGNED NOT NULL,
  `post_id` int UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `post_tag_tag_id_index`(`tag_id`) USING BTREE,
  INDEX `post_tag_post_id_index`(`post_id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for posts
-- ----------------------------
DROP TABLE IF EXISTS `posts`;
CREATE TABLE `posts`  (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `thread_id` int UNSIGNED NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT '',
  `slug` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT '',
  `description` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `body` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `allow_html` tinyint(1) NOT NULL DEFAULT 0,
  `content_type_id` int UNSIGNED NULL DEFAULT NULL,
  `visibility_id` int UNSIGNED NULL DEFAULT NULL,
  `recipient_id` int NULL DEFAULT NULL,
  `reply_to` int NULL DEFAULT NULL,
  `likes` int NOT NULL DEFAULT 0,
  `views` int NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` int UNSIGNED NULL DEFAULT NULL,
  `updated_by` int UNSIGNED NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `posts_thread_id_index`(`thread_id`) USING BTREE,
  INDEX `posts_content_type_fk`(`content_type_id`) USING BTREE,
  INDEX `posts_visibility_id_fk`(`visibility_id`) USING BTREE,
  INDEX `posts_created_by_fk`(`created_by`) USING BTREE,
  INDEX `posts_updated_by_fk`(`updated_by`) USING BTREE,
  CONSTRAINT `posts_content_type_fk` FOREIGN KEY (`content_type_id`) REFERENCES `content_types` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `posts_created_by_fk` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `posts_thread_id_fk` FOREIGN KEY (`thread_id`) REFERENCES `threads` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `posts_updated_by_fk` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `posts_visibility_id_fk` FOREIGN KEY (`visibility_id`) REFERENCES `visibilities` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB AUTO_INCREMENT = 262 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for profiles
-- ----------------------------
DROP TABLE IF EXISTS `profiles`;
CREATE TABLE `profiles`  (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int UNSIGNED NOT NULL,
  `bio` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `alias` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `location` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `visibility_id` int UNSIGNED NULL DEFAULT NULL,
  `facebook_username` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `twitter_username` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `instagram_username` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `first_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `last_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `default_theme` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `setting_weekly_update` tinyint(1) NULL DEFAULT 1,
  `setting_daily_update` tinyint(1) NULL DEFAULT 1,
  `setting_instant_update` tinyint(1) NULL DEFAULT 1,
  `setting_forum_update` tinyint(1) NULL DEFAULT 1,
  `setting_public_profile` tinyint(1) NULL DEFAULT 0,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `profiles_user_fk`(`user_id`) USING BTREE,
  INDEX `profiles_visibility_fi`(`visibility_id`) USING BTREE,
  CONSTRAINT `profiles_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB AUTO_INCREMENT = 2572 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for response_types
-- ----------------------------
DROP TABLE IF EXISTS `response_types`;
CREATE TABLE `response_types`  (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 7 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of response_types
-- ----------------------------
INSERT INTO `response_types` VALUES (1, 'Attending', 'General attending response', '2016-03-16 14:59:30', '2020-12-08 09:34:05');
INSERT INTO `response_types` VALUES (2, 'Interested', 'Planning on attending', '2016-03-16 14:59:53', '2020-12-08 09:34:34');
INSERT INTO `response_types` VALUES (3, 'Interested Unable', 'Interested but unable to attend', '2016-03-16 15:00:09', '2020-12-08 09:34:40');
INSERT INTO `response_types` VALUES (4, 'Uninterested', 'Uninterested in attending', '2016-03-16 15:00:34', '2020-12-08 09:34:42');
INSERT INTO `response_types` VALUES (5, 'Confirmed', 'Confirmed attendance', '2020-12-08 09:32:28', '2020-12-08 09:34:56');
INSERT INTO `response_types` VALUES (6, 'Ignore', 'Ignore events', '2020-12-10 00:17:23', '2020-12-10 00:17:23');

-- ----------------------------
-- Table structure for review_types
-- ----------------------------
DROP TABLE IF EXISTS `review_types`;
CREATE TABLE `review_types`  (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 5 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of review_types
-- ----------------------------
INSERT INTO `review_types` VALUES (1, 'Informational', '2016-11-29 00:00:00', '2016-11-29 00:00:00');
INSERT INTO `review_types` VALUES (2, 'Positive', '2016-11-29 00:00:00', '2016-11-29 00:00:00');
INSERT INTO `review_types` VALUES (3, 'Neutral', '2016-11-29 00:00:00', '2016-11-29 00:00:00');
INSERT INTO `review_types` VALUES (4, 'Negative', '2016-11-29 00:00:00', '2016-11-29 00:00:00');

-- ----------------------------
-- Table structure for roles
-- ----------------------------
DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles`  (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `short` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 12 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of roles
-- ----------------------------
INSERT INTO `roles` VALUES (1, 'Venue', 'venue', 'Public site for events', '2016-02-25 07:54:14', '2016-02-25 07:54:14');
INSERT INTO `roles` VALUES (2, 'Artist', 'artist', 'Visual artist', '2016-02-25 07:54:14', '2016-02-25 07:54:14');
INSERT INTO `roles` VALUES (3, 'Producer', 'producer', 'Music producer', '2016-02-25 07:54:14', '2016-02-25 07:54:14');
INSERT INTO `roles` VALUES (4, 'DJ', 'dj', 'DJ', '2016-02-25 07:54:14', '2016-02-25 07:54:14');
INSERT INTO `roles` VALUES (5, 'Promoter', 'promoter', 'Event promoter', '2016-02-25 07:54:14', '2016-02-25 07:54:14');
INSERT INTO `roles` VALUES (6, 'Shop', 'shop', 'Retail shop', '2016-02-25 07:54:14', '2016-02-25 07:54:14');
INSERT INTO `roles` VALUES (7, 'Band', 'band', 'Live band', '2016-02-25 07:54:14', '2016-02-25 07:54:14');
INSERT INTO `roles` VALUES (8, 'Label', 'label', 'Label', '2021-12-07 06:29:44', '2021-12-07 06:29:44');
INSERT INTO `roles` VALUES (9, 'Instructor', 'instructor', 'Instructor', '2024-04-11 17:04:17', '2024-04-11 17:04:17');
INSERT INTO `roles` VALUES (10, 'Performer', 'performer', 'Vocalist, instrumentalist, dancer, etc.', '2024-07-16 18:02:31', '2024-07-16 18:02:31');
INSERT INTO `roles` VALUES (11, 'Visualist', 'visualist', 'Live visual performer / creator', '2024-12-03 18:34:58', '2024-12-03 18:34:58');

-- ----------------------------
-- Table structure for series
-- ----------------------------
DROP TABLE IF EXISTS `series`;
CREATE TABLE `series`  (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `short` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `description` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `visibility_id` int UNSIGNED NULL DEFAULT NULL,
  `event_type_id` int UNSIGNED NULL DEFAULT NULL,
  `occurrence_type_id` int UNSIGNED NULL DEFAULT NULL,
  `occurrence_week_id` int UNSIGNED NULL DEFAULT NULL,
  `occurrence_day_id` int UNSIGNED NULL DEFAULT NULL,
  `hold_date` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `is_benefit` tinyint(1) NOT NULL DEFAULT 0,
  `promoter_id` int UNSIGNED NULL DEFAULT NULL,
  `venue_id` int UNSIGNED NULL DEFAULT NULL,
  `attending` int UNSIGNED NOT NULL DEFAULT 0,
  `like` int UNSIGNED NOT NULL DEFAULT 0,
  `presale_price` decimal(5, 2) NULL DEFAULT NULL,
  `door_price` decimal(5, 2) NULL DEFAULT NULL,
  `primary_link` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `ticket_link` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `founded_at` timestamp NULL DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `soundcheck_at` timestamp NULL DEFAULT NULL,
  `door_at` timestamp NULL DEFAULT NULL,
  `start_at` timestamp NULL DEFAULT NULL,
  `end_at` timestamp NULL DEFAULT NULL,
  `length` int NULL DEFAULT NULL,
  `min_age` tinyint UNSIGNED NULL DEFAULT NULL,
  `created_by` int UNSIGNED NULL DEFAULT NULL,
  `updated_by` int UNSIGNED NULL DEFAULT NULL,
  `facebook_username` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `instagram_username` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `twitter_username` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `series_visibility_id_foreign`(`visibility_id`) USING BTREE,
  INDEX `series_event_type_id_foreign`(`event_type_id`) USING BTREE,
  INDEX `series_occurrence_type_id_foreign`(`occurrence_type_id`) USING BTREE,
  INDEX `series_occurrence_week_id_foreign`(`occurrence_week_id`) USING BTREE,
  INDEX `series_occurrence_day_id_foreign`(`occurrence_day_id`) USING BTREE,
  INDEX `series_promoter_id_foreign`(`promoter_id`) USING BTREE,
  INDEX `series_venue_id_foreign`(`venue_id`) USING BTREE,
  INDEX `series_created_by_fk`(`created_by`) USING BTREE,
  INDEX `series_updated_by_fk`(`updated_by`) USING BTREE,
  CONSTRAINT `series_created_by_fk` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `series_event_type_fk` FOREIGN KEY (`event_type_id`) REFERENCES `event_types` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `series_occurrence_day_fk` FOREIGN KEY (`occurrence_day_id`) REFERENCES `occurrence_days` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `series_occurrence_type_fk` FOREIGN KEY (`occurrence_type_id`) REFERENCES `occurrence_types` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `series_occurrence_week_fk` FOREIGN KEY (`occurrence_week_id`) REFERENCES `occurrence_weeks` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `series_promoter_fk` FOREIGN KEY (`promoter_id`) REFERENCES `entities` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `series_updated_by_fk` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `series_venue_fk` FOREIGN KEY (`venue_id`) REFERENCES `entities` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `series_visibility_fk` FOREIGN KEY (`visibility_id`) REFERENCES `visibilities` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB AUTO_INCREMENT = 144 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for series_link
-- ----------------------------
DROP TABLE IF EXISTS `series_link`;
CREATE TABLE `series_link`  (
  `series_id` int UNSIGNED NOT NULL,
  `link_id` int UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `series_link_series_id_index`(`series_id`) USING BTREE,
  INDEX `series_link_link_id_index`(`link_id`) USING BTREE,
  CONSTRAINT `series_link_link_fk` FOREIGN KEY (`link_id`) REFERENCES `links` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `series_link_series_fk` FOREIGN KEY (`series_id`) REFERENCES `series` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for series_tag
-- ----------------------------
DROP TABLE IF EXISTS `series_tag`;
CREATE TABLE `series_tag`  (
  `series_id` int UNSIGNED NOT NULL,
  `tag_id` int UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `series_tag_series_id_index`(`series_id`) USING BTREE,
  INDEX `series_tag_tag_id_index`(`tag_id`) USING BTREE,
  CONSTRAINT `series_tag_series_fk` FOREIGN KEY (`series_id`) REFERENCES `series` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `series_tag_tag_id` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for series_thread
-- ----------------------------
DROP TABLE IF EXISTS `series_thread`;
CREATE TABLE `series_thread`  (
  `series_id` int UNSIGNED NULL DEFAULT NULL,
  `thread_id` int UNSIGNED NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `series_thread_thread_index`(`thread_id`) USING BTREE,
  INDEX `series_thread_series_index`(`series_id`) USING BTREE,
  CONSTRAINT `series_thread_series_fk` FOREIGN KEY (`series_id`) REFERENCES `series` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `series_thread_thread_fk` FOREIGN KEY (`thread_id`) REFERENCES `threads` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for settings
-- ----------------------------
DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings`  (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `value` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `settings_user_index`(`user_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for social_facebook_accounts
-- ----------------------------
DROP TABLE IF EXISTS `social_facebook_accounts`;
CREATE TABLE `social_facebook_accounts`  (
  `user_id` int NOT NULL,
  `provider_user_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `provider` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `created_at` datetime NULL DEFAULT NULL,
  `updated_at` datetime NULL DEFAULT NULL,
  PRIMARY KEY (`user_id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for tag_thread
-- ----------------------------
DROP TABLE IF EXISTS `tag_thread`;
CREATE TABLE `tag_thread`  (
  `tag_id` int UNSIGNED NOT NULL,
  `thread_id` int UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `tag_thread_tag_id_index`(`tag_id`) USING BTREE,
  INDEX `tag_thread_thread_id_index`(`thread_id`) USING BTREE,
  CONSTRAINT `tag_thread_tag_fk` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `tag_thread_thread_fk` FOREIGN KEY (`thread_id`) REFERENCES `threads` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for tag_types
-- ----------------------------
DROP TABLE IF EXISTS `tag_types`;
CREATE TABLE `tag_types`  (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 6 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of tag_types
-- ----------------------------
INSERT INTO `tag_types` VALUES (1, 'Genre', '2016-02-25 07:54:14', '2016-02-25 07:54:14');
INSERT INTO `tag_types` VALUES (2, 'Region', '2016-02-25 07:54:14', '2016-02-25 07:54:14');
INSERT INTO `tag_types` VALUES (3, 'Category', '2016-02-25 07:54:14', '2016-02-25 07:54:14');
INSERT INTO `tag_types` VALUES (4, 'Topics', '2016-02-25 07:54:14', '2016-02-25 07:54:14');
INSERT INTO `tag_types` VALUES (5, 'Reaction', '2016-02-25 07:54:14', '2016-02-25 07:54:14');

-- ----------------------------
-- Table structure for tags
-- ----------------------------
DROP TABLE IF EXISTS `tags`;
CREATE TABLE `tags`  (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tag_type_id` int UNSIGNED NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `slug` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `created_by` int UNSIGNED NULL DEFAULT NULL,
  `updated_by` int UNSIGNED NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `tags_tag_type_index`(`tag_type_id`) USING BTREE,
  INDEX `tag_created_by_fk`(`created_by`) USING BTREE,
  INDEX `tag_updated_by_fk`(`updated_by`) USING BTREE,
  CONSTRAINT `tag_created_by_fk` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `tag_tag_type_fk` FOREIGN KEY (`tag_type_id`) REFERENCES `tag_types` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `tag_updated_by_fk` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE = InnoDB AUTO_INCREMENT = 592 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for thread_categories
-- ----------------------------
DROP TABLE IF EXISTS `thread_categories`;
CREATE TABLE `thread_categories`  (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `forum_id` int UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `thread_categories_forum_id_index`(`forum_id`) USING BTREE,
  CONSTRAINT `thread_categories_forum_id_foreign` FOREIGN KEY (`forum_id`) REFERENCES `forums` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 6 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for threads
-- ----------------------------
DROP TABLE IF EXISTS `threads`;
CREATE TABLE `threads`  (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `forum_id` int UNSIGNED NOT NULL,
  `thread_category_id` int UNSIGNED NULL DEFAULT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `body` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `allow_html` tinyint(1) NOT NULL DEFAULT 0,
  `visibility_id` int UNSIGNED NULL DEFAULT NULL,
  `recipient_id` int UNSIGNED NULL DEFAULT NULL,
  `sort_order` int NOT NULL DEFAULT 0,
  `is_edittable` tinyint(1) NOT NULL DEFAULT 1,
  `likes` int NOT NULL DEFAULT 0,
  `views` int NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` int UNSIGNED NULL DEFAULT 1,
  `updated_by` int UNSIGNED NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `locked_at` timestamp NULL DEFAULT NULL,
  `locked_by` int NULL DEFAULT NULL,
  `event_id` int UNSIGNED NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `threads_forum_id_index`(`forum_id`) USING BTREE,
  INDEX `created_by_index`(`created_by`) USING BTREE,
  INDEX `threads_thread_category_fk`(`thread_category_id`) USING BTREE,
  INDEX `threads_visibility_fk`(`visibility_id`) USING BTREE,
  INDEX `threads_recipient_fk`(`recipient_id`) USING BTREE,
  INDEX `threads_updated_by_fk`(`updated_by`) USING BTREE,
  INDEX `threads_event_fk`(`event_id`) USING BTREE,
  CONSTRAINT `threads_created_by_fk` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `threads_event_fk` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `threads_forum_id_foreign` FOREIGN KEY (`forum_id`) REFERENCES `forums` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `threads_recipient_fk` FOREIGN KEY (`recipient_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `threads_thread_category_fk` FOREIGN KEY (`thread_category_id`) REFERENCES `thread_categories` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `threads_updated_by_fk` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `threads_visibility_fk` FOREIGN KEY (`visibility_id`) REFERENCES `visibilities` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE = InnoDB AUTO_INCREMENT = 313 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for user_statuses
-- ----------------------------
DROP TABLE IF EXISTS `user_statuses`;
CREATE TABLE `user_statuses`  (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `can_login` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 6 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of user_statuses
-- ----------------------------
INSERT INTO `user_statuses` VALUES (1, 'Pending', 0, '2016-02-25 07:54:14', '2016-02-25 07:54:14');
INSERT INTO `user_statuses` VALUES (2, 'Active', 1, '2016-02-25 07:54:14', '2016-02-25 07:54:14');
INSERT INTO `user_statuses` VALUES (3, 'Suspended', 0, '2016-02-25 07:54:14', '2016-02-25 07:54:14');
INSERT INTO `user_statuses` VALUES (4, 'Banned', 0, '2017-04-18 12:54:30', '2017-04-18 12:54:30');
INSERT INTO `user_statuses` VALUES (5, 'Deleted', 0, '2017-04-18 12:54:30', '2017-04-18 12:54:30');

-- ----------------------------
-- Table structure for users
-- ----------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users`  (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `slug` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `user_status_id` int NULL DEFAULT 1,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `users_email_unique`(`email`) USING BTREE,
  INDEX `users_user_status_id`(`user_status_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 3511 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for visibilities
-- ----------------------------
DROP TABLE IF EXISTS `visibilities`;
CREATE TABLE `visibilities`  (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 6 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of visibilities
-- ----------------------------
INSERT INTO `visibilities` VALUES (1, 'Proposal', '2016-02-25 07:54:14', '2016-02-25 07:54:14');
INSERT INTO `visibilities` VALUES (2, 'Private', '2016-02-25 07:54:14', '2016-02-25 07:54:14');
INSERT INTO `visibilities` VALUES (3, 'Public', '2016-02-25 07:54:14', '2016-02-25 07:54:14');
INSERT INTO `visibilities` VALUES (4, 'Guarded', '2016-02-25 07:54:14', '2016-02-25 07:54:14');
INSERT INTO `visibilities` VALUES (5, 'Cancelled', '2016-02-25 07:54:14', '2016-02-25 07:54:14');

SET FOREIGN_KEY_CHECKS = 1;
