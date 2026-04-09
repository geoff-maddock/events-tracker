/*
  INIT - Insert Rows Only - Events Tracker - EXTENDED

 Navicat Premium Data Transfer

 Source Server         : arcane.city
 Source Server Type    : MySQL
 Source Server Version : 80017
 Source Host           : 127.0.0.1:3306
 Source Schema         : events_tracker

 Target Server Type    : MySQL
 Target Server Version : 80017
 File Encoding         : 65001

 Date: 25/11/2019 01:45:02
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
-- Records of menus
-- ----------------------------
INSERT INTO `menus` VALUES (1, 'About', 'about', '', 0, 3, '2019-09-19 10:22:29', '2019-09-25 23:50:43');

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
-- Records of tags
-- ----------------------------
INSERT INTO `tags` VALUES (1, 'Jungle', 1, '2016-02-25 07:54:14', '2025-08-13 20:51:51', 'jungle', NULL, 1, 1);
INSERT INTO `tags` VALUES (2, 'Club Music', 1, '2016-02-25 07:54:14', '2025-08-13 20:51:51', 'club-music', NULL, 1, 1);
INSERT INTO `tags` VALUES (3, 'Footwork', 1, '2016-02-25 07:54:14', '2025-08-13 20:51:51', 'footwork', NULL, 1, 1);
INSERT INTO `tags` VALUES (4, 'Hardcore', 1, '2016-02-25 18:43:41', '2025-08-13 20:51:51', 'hardcore', NULL, 1, 1);
INSERT INTO `tags` VALUES (5, 'Uk Garage', 1, '2016-02-25 22:33:58', '2025-08-13 20:51:51', 'uk-garage', NULL, 1, 1);
INSERT INTO `tags` VALUES (7, 'Bass', 1, '2016-02-26 02:13:43', '2025-08-13 20:51:51', 'bass', NULL, 1, 1);
INSERT INTO `tags` VALUES (8, 'Trap', 1, '2016-02-26 02:13:43', '2025-08-13 20:51:51', 'trap', NULL, 1, 1);
INSERT INTO `tags` VALUES (9, 'Grime', 1, '2016-02-26 02:13:43', '2025-08-13 20:51:51', 'grime', NULL, 1, 1);
INSERT INTO `tags` VALUES (10, 'Techno', 1, '2016-02-26 02:18:57', '2025-08-13 20:51:51', 'techno', NULL, 1, 1);
INSERT INTO `tags` VALUES (11, 'Rave', 1, '2016-02-26 02:18:57', '2025-08-13 20:51:51', 'rave', NULL, 1, 1);
INSERT INTO `tags` VALUES (12, 'Noise', 1, '2016-02-26 19:07:58', '2025-08-13 20:51:51', 'noise', NULL, 1, 1);
INSERT INTO `tags` VALUES (13, 'Rock', 1, '2016-02-26 19:07:58', '2025-08-13 20:51:51', 'rock', NULL, 1, 1);
INSERT INTO `tags` VALUES (14, 'Houseparty', 1, '2016-02-26 19:07:58', '2025-08-13 20:51:51', 'house-party', NULL, 1, 1);
INSERT INTO `tags` VALUES (15, 'Dubstep', 1, '2016-02-26 20:33:14', '2025-08-13 20:51:51', 'dubstep', NULL, 1, 1);
INSERT INTO `tags` VALUES (16, 'Modular Synth', 1, '2016-02-26 23:35:30', '2025-08-13 20:51:51', 'modular-synth', NULL, 1, 1);
INSERT INTO `tags` VALUES (17, 'Industrial', 1, '2016-02-26 23:35:30', '2025-08-13 20:51:51', 'industrial', NULL, 1, 1);
INSERT INTO `tags` VALUES (18, 'Ambient', 1, '2016-02-27 19:02:15', '2025-08-13 20:51:52', 'ambient', NULL, 1, 1);
INSERT INTO `tags` VALUES (19, 'Metal', 1, '2016-02-27 19:02:15', '2025-08-13 20:51:52', 'metal', NULL, 1, 1);
INSERT INTO `tags` VALUES (20, 'Coffee', 1, '2016-02-27 19:03:14', '2025-08-13 20:51:52', 'coffee', NULL, 1, 1);
INSERT INTO `tags` VALUES (21, 'Drum And Bass', 1, '2016-02-29 06:47:50', '2025-08-13 20:51:52', 'drum-and-bass', NULL, 1, 1);
INSERT INTO `tags` VALUES (22, 'Experimental', 1, '2016-02-29 08:06:39', '2025-08-13 20:51:52', 'experimental', NULL, 1, 1);
INSERT INTO `tags` VALUES (23, 'Dance', 1, '2016-02-29 08:08:25', '2025-08-13 20:51:52', 'dance', NULL, 1, 1);
INSERT INTO `tags` VALUES (24, 'Electronic', 1, '2016-02-29 08:45:26', '2025-08-13 20:51:52', 'electronic', NULL, 1, 1);
INSERT INTO `tags` VALUES (25, 'Deathrock', 1, '2016-02-29 16:45:08', '2025-08-13 20:51:52', 'deathrock', NULL, 1, 1);
INSERT INTO `tags` VALUES (26, 'Post-punk', 1, '2016-02-29 16:45:08', '2025-08-13 20:51:52', 'post-punk', NULL, 1, 1);
INSERT INTO `tags` VALUES (27, 'Minimal Wave', 1, '2016-02-29 16:45:08', '2025-08-13 20:51:52', 'minimal-wave', NULL, 1, 1);
INSERT INTO `tags` VALUES (28, 'House', 1, '2016-02-29 21:14:28', '2025-08-13 20:51:52', 'house', NULL, 1, 1);
INSERT INTO `tags` VALUES (29, 'Disco', 1, '2016-02-29 21:14:28', '2025-08-13 20:51:52', 'disco', NULL, 1, 1);
INSERT INTO `tags` VALUES (30, 'Funk', 1, '2016-02-29 21:20:03', '2025-08-13 20:51:52', 'funk', NULL, 1, 1);
INSERT INTO `tags` VALUES (31, 'Food', 1, '2016-02-29 21:20:03', '2025-08-13 20:51:52', 'food', NULL, 1, 1);
INSERT INTO `tags` VALUES (32, 'Idm', 1, '2016-02-29 22:55:41', '2025-08-13 20:51:52', 'idm', NULL, 1, 1);
INSERT INTO `tags` VALUES (33, 'Live Sets', 1, '2016-03-01 17:16:57', '2025-08-13 20:51:52', 'live-sets', NULL, 1, 1);
INSERT INTO `tags` VALUES (34, 'Cmu', 1, '2016-03-01 20:09:45', '2025-08-13 20:51:52', 'cmu', NULL, 1, 1);
INSERT INTO `tags` VALUES (35, 'Hip Hop', 1, '2016-03-01 20:16:28', '2025-08-13 20:51:52', 'hip-hop', NULL, 1, 1);
INSERT INTO `tags` VALUES (36, 'Halloween', 1, '2016-03-01 22:29:27', '2025-08-13 20:51:52', 'halloween', NULL, 1, 1);
INSERT INTO `tags` VALUES (37, 'Balkan', 1, '2016-03-02 06:47:18', '2025-08-13 20:51:52', 'balkan', NULL, 1, 1);
INSERT INTO `tags` VALUES (38, 'Bhangra', 1, '2016-03-02 06:47:19', '2025-08-13 20:51:52', 'bhangra', NULL, 1, 1);
INSERT INTO `tags` VALUES (39, 'World Music', 1, '2016-03-02 06:47:19', '2025-08-13 20:51:52', 'world-music', NULL, 1, 1);
INSERT INTO `tags` VALUES (40, 'Breakcore', 1, '2016-03-02 06:58:32', '2025-08-13 20:51:53', 'breakcore', NULL, 1, 1);
INSERT INTO `tags` VALUES (41, 'Edm', 1, '2016-03-03 20:47:14', '2025-08-13 20:51:53', 'edm', NULL, 1, 1);
INSERT INTO `tags` VALUES (42, 'Punk', 1, '2016-03-06 00:42:40', '2025-08-13 20:51:53', 'punk', NULL, 1, 1);
INSERT INTO `tags` VALUES (43, 'Weed', 1, '2016-03-06 00:43:09', '2025-08-13 20:51:53', 'weed', NULL, 1, 1);
INSERT INTO `tags` VALUES (44, 'French', 1, '2016-03-06 00:46:24', '2025-08-13 20:51:53', 'french', NULL, 1, 1);
INSERT INTO `tags` VALUES (45, 'Hardstyle', 1, '2016-03-06 00:49:34', '2025-08-13 20:51:53', 'hardstyle', NULL, 1, 1);
INSERT INTO `tags` VALUES (46, 'Jumpstyle', 1, '2016-03-06 00:50:52', '2025-08-13 20:51:53', 'jumpstyle', NULL, 1, 1);
INSERT INTO `tags` VALUES (48, 'Diy', 1, '2016-03-06 01:13:22', '2025-08-13 20:51:53', 'diy', NULL, 1, 1);
INSERT INTO `tags` VALUES (51, 'Fetish', 1, '2016-03-06 01:21:20', '2025-08-13 20:51:53', 'fetish', NULL, 1, 1);
INSERT INTO `tags` VALUES (52, 'Witch House', 1, '2016-03-07 20:51:51', '2025-08-13 20:51:53', 'witch-house', NULL, 1, 1);
INSERT INTO `tags` VALUES (53, 'Museum', 1, '2016-03-07 20:55:03', '2025-08-13 20:51:53', 'museum', NULL, 1, 1);
INSERT INTO `tags` VALUES (54, 'Art', 1, '2016-03-07 20:55:58', '2025-08-13 20:51:53', 'art', NULL, 1, 1);
INSERT INTO `tags` VALUES (55, 'Pop', 1, '2016-03-10 19:01:51', '2025-08-13 20:51:53', 'pop', NULL, 1, 1);
INSERT INTO `tags` VALUES (56, 'Anything Goes', 1, '2016-03-10 19:01:51', '2025-08-13 20:51:53', 'anthing-goes', NULL, 1, 1);
INSERT INTO `tags` VALUES (57, 'Goth', 1, '2016-03-10 20:09:16', '2025-08-13 20:51:53', 'goth', NULL, 1, 1);
INSERT INTO `tags` VALUES (58, 'Synthwave', 1, '2016-03-10 20:09:16', '2025-08-13 20:51:53', 'synthwave', NULL, 1, 1);
INSERT INTO `tags` VALUES (59, 'Ebm', 1, '2016-03-14 22:12:41', '2025-08-13 20:51:53', 'ebm', NULL, 1, 1);
INSERT INTO `tags` VALUES (60, 'Synth-pop', 1, '2016-03-14 22:12:41', '2025-08-13 20:51:53', 'synth-pop', NULL, 1, 1);
INSERT INTO `tags` VALUES (61, 'Cumbia', 1, '2016-03-14 22:33:06', '2025-08-13 20:51:53', 'cumbia', NULL, 1, 1);
INSERT INTO `tags` VALUES (62, 'Soul', 1, '2016-03-17 00:43:00', '2025-08-13 20:51:54', 'soul', NULL, 1, 1);
INSERT INTO `tags` VALUES (63, 'Dancehall', 1, '2016-03-17 00:51:00', '2025-08-13 20:51:54', 'dancehall', NULL, 1, 1);
INSERT INTO `tags` VALUES (64, 'Reggae', 1, '2016-03-17 00:51:00', '2025-08-13 20:51:54', 'reggae', NULL, 1, 1);
INSERT INTO `tags` VALUES (65, 'Breaks', 1, '2016-03-17 23:18:00', '2025-08-13 20:51:54', 'breaks', NULL, 1, 1);
INSERT INTO `tags` VALUES (66, 'Turntablism', 1, '2016-03-17 23:18:00', '2025-08-13 20:51:54', 'turntablism', NULL, 1, 1);
INSERT INTO `tags` VALUES (67, 'Film', 1, '2016-03-18 15:45:00', '2025-08-13 20:51:54', 'film', NULL, 1, 1);
INSERT INTO `tags` VALUES (69, 'R&b', 1, '2016-03-21 21:37:00', '2025-08-13 20:51:54', 'rnb', NULL, 1, 1);
INSERT INTO `tags` VALUES (70, 'Live Visuals', 1, '2016-03-21 22:45:00', '2025-08-13 20:51:54', 'live-visuals', NULL, 1, 1);
INSERT INTO `tags` VALUES (71, 'Moombahton', 1, '2016-03-22 19:43:00', '2025-08-13 20:51:54', 'moombahton', NULL, 1, 1);
INSERT INTO `tags` VALUES (72, 'Bounce', 1, '2016-03-22 19:43:00', '2025-08-13 20:51:54', 'bounce', NULL, 1, 1);
INSERT INTO `tags` VALUES (73, 'Downtempo', 1, '2016-03-22 21:10:00', '2025-08-13 20:51:54', 'downtempo', NULL, 1, 1);
INSERT INTO `tags` VALUES (74, 'Post-rock', 1, '2016-03-22 21:18:00', '2025-08-13 20:51:54', 'post-rock', NULL, 1, 1);
INSERT INTO `tags` VALUES (75, 'Dub', 1, '2016-03-22 21:18:00', '2025-08-13 20:51:54', 'dub', NULL, 1, 1);
INSERT INTO `tags` VALUES (76, 'Avant', 1, '2016-03-22 21:39:00', '2025-08-13 20:51:54', 'avant', NULL, 1, 1);
INSERT INTO `tags` VALUES (77, 'Mashup', 1, '2016-03-23 01:44:00', '2025-08-13 20:51:54', 'mashup', NULL, 1, 1);
INSERT INTO `tags` VALUES (78, 'Electro', 1, '2016-03-24 18:07:00', '2025-08-13 20:51:54', 'electro', NULL, 1, 1);
INSERT INTO `tags` VALUES (79, 'Indie Dance', 1, '2016-03-24 21:38:00', '2025-08-13 20:51:54', 'indie-dance', NULL, 1, 1);
INSERT INTO `tags` VALUES (80, 'Britpop', 1, '2016-03-24 21:44:00', '2025-08-13 20:51:54', 'britpop', NULL, 1, 1);
INSERT INTO `tags` VALUES (81, 'Games', 1, '2016-03-28 18:34:00', '2025-08-13 20:51:54', 'games', NULL, 1, 1);
INSERT INTO `tags` VALUES (82, 'Horror', 1, '2016-03-28 18:34:00', '2025-08-13 20:51:54', 'horror', NULL, 1, 1);
INSERT INTO `tags` VALUES (83, 'Wpts', 1, '2016-03-28 20:26:00', '2025-08-13 20:51:54', 'wpts', NULL, 1, 1);
INSERT INTO `tags` VALUES (84, 'College Radio', 1, '2016-03-28 20:26:00', '2025-08-13 20:51:54', 'college-radio', NULL, 1, 1);
INSERT INTO `tags` VALUES (85, 'Indie', 1, '2016-03-28 22:17:00', '2025-08-13 20:51:55', 'indie', NULL, 1, 1);
INSERT INTO `tags` VALUES (86, 'Rhythmic Noise', 1, '2016-03-30 16:46:00', '2025-08-13 20:51:55', 'rhythmic-noise', NULL, 1, 1);
INSERT INTO `tags` VALUES (87, 'Happy Hardcore', 1, '2016-03-31 18:20:00', '2025-08-13 20:51:55', 'happy-hardcore', NULL, 1, 1);
INSERT INTO `tags` VALUES (88, 'Trance', 1, '2016-03-31 20:00:00', '2025-08-13 20:51:55', 'trance', NULL, 1, 1);
INSERT INTO `tags` VALUES (89, 'Psytrance', 1, '2016-03-31 20:36:00', '2025-08-13 20:51:55', 'psytrance', NULL, 1, 1);
INSERT INTO `tags` VALUES (90, 'Renegade', 1, '2016-04-04 07:16:00', '2025-08-13 20:51:55', 'renegade', NULL, 1, 1);
INSERT INTO `tags` VALUES (91, 'Ragga-jungle', 1, '2016-04-04 07:26:00', '2025-08-13 20:51:55', 'ragga-jungle', NULL, 1, 1);
INSERT INTO `tags` VALUES (92, 'Record Store', 1, '2016-04-04 16:20:00', '2025-08-13 20:51:55', 'record-store', NULL, 1, 1);
INSERT INTO `tags` VALUES (93, 'Free-jazz', 1, '2016-04-04 17:19:00', '2025-08-13 20:51:55', 'free-jazz', NULL, 1, 1);
INSERT INTO `tags` VALUES (94, 'Improv', 1, '2016-04-04 17:19:00', '2025-08-13 20:51:55', 'improv', NULL, 1, 1);
INSERT INTO `tags` VALUES (95, 'Jazz', 1, '2016-04-04 21:36:00', '2025-08-13 20:51:55', 'jazz', NULL, 1, 1);
INSERT INTO `tags` VALUES (96, 'Laptop Battle', 1, '2016-04-06 00:31:00', '2025-08-13 20:51:55', 'laptop-battle', NULL, 1, 1);
INSERT INTO `tags` VALUES (97, 'Speedcore', 1, '2016-04-06 00:31:00', '2025-08-13 20:51:55', 'speedcore', NULL, 1, 1);
INSERT INTO `tags` VALUES (98, 'Benefit', 1, '2016-04-06 02:02:00', '2025-08-13 20:51:55', 'benefit', NULL, 1, 1);
INSERT INTO `tags` VALUES (99, 'Drag', 1, '2016-04-06 02:02:00', '2025-08-13 20:51:55', 'drag', NULL, 1, 1);
INSERT INTO `tags` VALUES (100, 'Acid', 1, '2022-04-27 23:06:18', '2025-08-13 20:51:55', 'acid', NULL, 1, 1);
INSERT INTO `tags` VALUES (101, 'Outdoors', 1, '2016-05-02 19:08:00', '2025-08-13 20:51:55', 'outdoors', NULL, 1, 1);
INSERT INTO `tags` VALUES (102, 'Internet Radio', 1, '2016-05-02 19:19:00', '2025-08-13 20:51:55', 'internet-radio', NULL, 1, 1);
INSERT INTO `tags` VALUES (103, 'Vinyl', 1, '2016-05-02 19:57:00', '2025-08-13 20:51:55', 'vinyl', NULL, 1, 1);
INSERT INTO `tags` VALUES (104, 'No Wave', 1, '2016-05-09 14:49:00', '2025-08-13 20:51:55', 'no-wave', NULL, 1, 1);
INSERT INTO `tags` VALUES (105, 'Boogie', 1, '2016-05-16 19:10:00', '2025-08-13 20:51:55', 'boogie', NULL, 1, 1);
INSERT INTO `tags` VALUES (106, 'Performance Art', 1, '2016-05-16 19:30:00', '2025-08-13 20:51:55', 'performance-art', NULL, 1, 1);
INSERT INTO `tags` VALUES (107, 'Italo Disco', 1, '2016-05-18 16:34:00', '2025-08-13 20:51:55', 'italo-disco', NULL, 1, 1);
INSERT INTO `tags` VALUES (108, 'Gabber', 1, '2016-06-01 20:42:00', '2025-08-13 20:51:55', 'gabber', NULL, 1, 1);
INSERT INTO `tags` VALUES (109, 'Rollerskating', 1, '2016-06-01 22:30:00', '2025-08-13 20:51:55', 'rollerskating', NULL, 1, 1);
INSERT INTO `tags` VALUES (110, 'Dance Party', 1, '2016-06-07 21:10:00', '2025-08-13 20:51:56', 'dance-party', NULL, 1, 1);
INSERT INTO `tags` VALUES (111, 'Chiptune', 1, '2016-06-09 17:16:00', '2025-08-13 20:51:56', 'chiptune', NULL, 1, 1);
INSERT INTO `tags` VALUES (112, 'Glitch-hop', 1, '2016-06-09 21:14:00', '2025-08-13 20:51:56', 'glitch-hop', NULL, 1, 1);
INSERT INTO `tags` VALUES (113, 'Braindance', 1, '2016-06-13 15:45:00', '2025-08-13 20:51:56', 'brandance', NULL, 1, 1);
INSERT INTO `tags` VALUES (114, 'Uk Hardcore', 1, '2016-06-14 16:38:00', '2025-08-13 20:51:56', 'uk-hardcore', NULL, 1, 1);
INSERT INTO `tags` VALUES (115, 'Nye', 1, '2016-06-17 20:23:00', '2025-08-13 20:51:56', 'nye', NULL, 1, 1);
INSERT INTO `tags` VALUES (116, 'Twerk', 1, '2016-06-20 16:53:00', '2025-08-13 20:51:56', 'twerk', NULL, 1, 1);
INSERT INTO `tags` VALUES (118, 'Chillwave', 1, '2016-06-27 16:33:00', '2025-08-13 20:51:56', 'chillwave', NULL, 1, 1);
INSERT INTO `tags` VALUES (119, 'Ska', 1, '2016-07-06 04:31:00', '2025-08-13 20:51:56', 'ska', NULL, 1, 1);
INSERT INTO `tags` VALUES (120, 'Folk', 1, '2016-07-12 16:55:00', '2025-08-13 20:51:56', 'folk', NULL, 1, 1);
INSERT INTO `tags` VALUES (121, 'Country', 1, '2016-07-12 16:55:00', '2025-08-13 20:51:56', 'country', NULL, 1, 1);
INSERT INTO `tags` VALUES (122, 'Eighties', 1, '2016-07-12 20:06:00', '2025-08-13 20:51:56', 'eighties', NULL, 1, 1);
INSERT INTO `tags` VALUES (123, 'Workshop', 1, '2016-09-07 15:22:00', '2025-08-13 20:51:56', 'workshop', NULL, 1, 1);
INSERT INTO `tags` VALUES (124, 'Black Metal', 1, '2016-09-12 16:43:00', '2025-08-13 20:51:56', 'black-metal', NULL, 1, 1);
INSERT INTO `tags` VALUES (125, 'Code', 1, '2016-09-12 22:49:00', '2025-08-13 20:51:56', 'code', NULL, 1, 1);
INSERT INTO `tags` VALUES (126, 'Virtual Reality', 1, '2016-09-12 22:49:00', '2025-08-13 20:51:56', 'virtual-reality', NULL, 1, 1);
INSERT INTO `tags` VALUES (127, 'Triphop', 1, '2016-12-14 19:48:00', '2025-08-13 20:51:56', 'triphop', NULL, 1, 1);
INSERT INTO `tags` VALUES (128, 'Restaurant', 1, '2016-12-16 17:00:00', '2025-08-13 20:51:56', 'restaurant', NULL, 1, 1);
INSERT INTO `tags` VALUES (129, 'Lecture', 1, '2017-01-23 23:36:00', '2025-08-13 20:51:56', 'lecture', NULL, 1, 1);
INSERT INTO `tags` VALUES (130, 'Activism', 1, '2017-01-31 20:43:00', '2025-08-13 20:51:56', 'activism', NULL, 1, 1);
INSERT INTO `tags` VALUES (131, 'Speaker', 1, '2017-02-02 18:02:00', '2025-08-13 20:51:56', 'speaker', NULL, 1, 1);
INSERT INTO `tags` VALUES (132, 'Death Metal', 1, '2017-02-02 20:02:00', '2025-08-13 20:51:57', 'death-metal', NULL, 1, 1);
INSERT INTO `tags` VALUES (133, 'Ethereal', 1, '2017-02-06 23:02:00', '2025-08-13 20:51:57', 'ethereal', NULL, 1, 1);
INSERT INTO `tags` VALUES (134, 'Queer', 1, '2017-02-07 19:06:00', '2025-08-13 20:51:57', 'queer', NULL, 1, 1);
INSERT INTO `tags` VALUES (135, 'Neofolk', 1, '2017-02-08 02:09:00', '2025-08-13 20:51:57', 'neofolk', NULL, 1, 1);
INSERT INTO `tags` VALUES (136, 'Classical', 1, '2017-02-13 21:38:00', '2025-08-13 20:51:57', 'classical', NULL, 1, 1);
INSERT INTO `tags` VALUES (137, 'Darkwave', 1, '2017-02-14 00:11:00', '2025-08-13 20:51:57', 'darkwave', NULL, 1, 1);
INSERT INTO `tags` VALUES (138, 'Silent Disco', 1, '2017-02-15 13:23:00', '2025-08-13 20:51:57', 'silent-disco', NULL, 1, 1);
INSERT INTO `tags` VALUES (140, 'Brass Bands', 1, '2017-02-15 14:37:00', '2025-08-13 20:51:57', 'brass-bands', NULL, 1, 1);
INSERT INTO `tags` VALUES (141, 'Animals', 1, '2017-02-20 12:49:00', '2025-08-13 20:51:57', 'animals', NULL, 1, 1);
INSERT INTO `tags` VALUES (143, 'Psychedelic', 1, '2017-02-23 12:43:00', '2025-08-13 20:51:57', 'psychedelic', NULL, 1, 1);
INSERT INTO `tags` VALUES (145, 'Glitch', 1, '2017-02-23 12:43:00', '2025-08-13 20:51:57', 'glitch', NULL, 1, 1);
INSERT INTO `tags` VALUES (149, 'Church', 1, '2017-03-01 14:15:00', '2025-08-13 20:51:57', 'church', NULL, 1, 1);
INSERT INTO `tags` VALUES (150, 'Bar', 1, '2017-03-30 13:35:00', '2025-08-13 20:51:57', 'bar', NULL, 1, 1);
INSERT INTO `tags` VALUES (151, 'Puppets', 1, '2017-04-17 17:19:00', '2025-08-13 20:51:57', 'puppets', NULL, 1, 1);
INSERT INTO `tags` VALUES (152, 'Market', 1, '2017-04-17 17:23:00', '2025-08-13 20:51:57', 'market', NULL, 1, 1);
INSERT INTO `tags` VALUES (155, 'Park', 1, '2017-05-04 12:23:00', '2025-08-13 20:51:57', 'park', NULL, 1, 1);
INSERT INTO `tags` VALUES (156, 'Afrobeat', 1, '2017-05-11 14:53:00', '2025-10-10 15:34:16', 'afrobeat', 'test', 1, 1);
INSERT INTO `tags` VALUES (158, 'Ritual', 1, '2017-06-03 12:51:00', '2025-08-13 20:51:57', 'ritual', NULL, 1, 1);
INSERT INTO `tags` VALUES (159, 'Open Mic', 1, '2017-06-18 21:08:00', '2025-08-13 20:51:57', 'open-mic', NULL, 1, 1);
INSERT INTO `tags` VALUES (160, 'Family friendly', 1, '2017-06-23 12:59:00', '2025-08-13 20:51:57', 'family-friendly', NULL, 1, 1);
INSERT INTO `tags` VALUES (161, 'Garden', 1, '2017-07-17 17:06:00', '2025-08-13 20:51:57', 'garden', NULL, 1, 1);
INSERT INTO `tags` VALUES (163, 'Livecoding', 1, '2017-07-25 14:21:00', '2025-08-13 20:51:57', 'livecoding', NULL, 1, 1);
INSERT INTO `tags` VALUES (164, 'Library', 1, '2017-08-05 10:52:00', '2025-08-13 20:51:57', 'library', NULL, 1, 1);
INSERT INTO `tags` VALUES (165, 'Class', 1, '2017-08-09 10:19:00', '2025-08-13 20:51:57', 'class', NULL, 1, 1);
INSERT INTO `tags` VALUES (166, 'Video', 1, '2017-08-14 15:26:00', '2025-08-13 20:51:58', 'video', NULL, 1, 1);
INSERT INTO `tags` VALUES (169, 'New Wave', 1, '2017-08-22 16:12:00', '2025-08-13 20:51:58', 'new-wave', NULL, 1, 1);
INSERT INTO `tags` VALUES (170, 'Development', 1, '2017-09-27 12:44:00', '2025-08-13 20:51:58', 'development', NULL, 1, 1);
INSERT INTO `tags` VALUES (171, 'Surf', 1, '2017-10-16 16:25:00', '2025-08-13 20:51:58', 'surf', NULL, 1, 1);
INSERT INTO `tags` VALUES (172, 'Computers', 1, '2017-11-03 14:09:00', '2025-08-13 20:51:58', 'computers', NULL, 1, 1);
INSERT INTO `tags` VALUES (173, 'Grindcore', 1, '2017-11-21 02:38:00', '2025-08-13 20:51:58', 'grindcore', NULL, 1, 1);
INSERT INTO `tags` VALUES (174, 'Crafts', 1, '2017-11-29 10:43:00', '2025-08-13 20:51:58', 'crafts', NULL, 1, 1);
INSERT INTO `tags` VALUES (176, 'Azonto', 1, '2017-12-11 16:13:00', '2025-08-13 20:51:58', 'azonto', NULL, 1, 1);
INSERT INTO `tags` VALUES (177, 'Kudoro', 1, '2017-12-11 16:13:00', '2025-08-13 20:51:58', 'kuduro', NULL, 1, 1);
INSERT INTO `tags` VALUES (178, 'Soca', 1, '2017-12-11 16:13:00', '2025-08-13 20:51:58', 'soca', NULL, 1, 1);
INSERT INTO `tags` VALUES (179, 'Brazilian', 1, '2017-12-11 16:40:00', '2025-08-13 20:51:58', 'brazilian', NULL, 1, 1);
INSERT INTO `tags` VALUES (180, 'Baile Funk', 1, '2017-12-11 16:40:00', '2025-08-13 20:51:58', 'baile-funk', NULL, 1, 1);
INSERT INTO `tags` VALUES (181, 'Minimal', 1, '2017-12-12 18:18:00', '2025-08-13 20:51:58', 'minimal', NULL, 1, 1);
INSERT INTO `tags` VALUES (182, 'Groovy', 1, '2017-12-12 18:18:00', '2025-08-13 20:51:58', 'groovy', NULL, 1, 1);
INSERT INTO `tags` VALUES (183, 'Soulful', 1, '2017-12-12 18:18:00', '2025-08-13 20:51:58', 'soulful', NULL, 1, 1);
INSERT INTO `tags` VALUES (184, 'Comics', 1, '2017-12-14 21:10:00', '2025-08-13 20:51:58', 'comics', NULL, 1, 1);
INSERT INTO `tags` VALUES (185, 'Beer', 1, '2017-12-18 16:00:00', '2025-08-13 20:51:58', 'beer', NULL, 1, 1);
INSERT INTO `tags` VALUES (186, 'Emo', 1, '2017-12-21 01:56:00', '2025-08-13 20:51:58', 'emo', NULL, 1, 1);
INSERT INTO `tags` VALUES (187, 'Karaoke', 1, '2017-12-21 01:56:00', '2025-08-13 20:51:58', 'karaoke', NULL, 1, 1);
INSERT INTO `tags` VALUES (188, 'Storytelling', 1, '2018-01-08 11:48:00', '2025-08-13 20:51:58', 'storytelling', NULL, 1, 1);
INSERT INTO `tags` VALUES (189, 'Deep House', 1, '2018-01-13 01:33:00', '2025-08-13 20:51:58', 'deep house', NULL, 1, 1);
INSERT INTO `tags` VALUES (190, 'Doom Metal', 1, '2018-01-26 12:13:00', '2025-08-13 20:51:58', 'doom-metal', NULL, 1, 1);
INSERT INTO `tags` VALUES (191, 'Stoner Rock', 1, '2018-01-26 12:13:00', '2025-08-13 20:51:58', 'stoner-rock', NULL, 1, 1);
INSERT INTO `tags` VALUES (193, 'Drone', 1, '2018-01-26 12:44:00', '2025-08-13 20:51:58', 'drone', NULL, 1, 1);
INSERT INTO `tags` VALUES (195, 'Guitar', 1, '2018-02-12 12:17:00', '2025-08-13 20:51:59', 'guitar', NULL, 1, 1);
INSERT INTO `tags` VALUES (197, 'Garage', 1, '2018-02-26 14:09:00', '2025-08-13 20:51:59', 'garage', NULL, 1, 1);
INSERT INTO `tags` VALUES (198, 'Trash', 1, '2018-02-26 14:09:00', '2025-08-13 20:51:59', 'trash', NULL, 1, 1);
INSERT INTO `tags` VALUES (199, 'Glam', 1, '2018-02-26 14:09:00', '2025-08-13 20:51:59', 'glam', NULL, 1, 1);
INSERT INTO `tags` VALUES (200, 'Shoegaze', 1, '2018-02-27 12:56:00', '2025-08-13 20:51:59', 'shoegaze', NULL, 1, 1);
INSERT INTO `tags` VALUES (201, 'Rap', 1, '2018-03-26 17:53:00', '2025-08-13 20:51:59', 'rap', NULL, 1, 1);
INSERT INTO `tags` VALUES (202, 'Comedy', 1, '2018-03-29 11:44:00', '2025-08-13 20:51:59', 'comedy', NULL, 1, 1);
INSERT INTO `tags` VALUES (203, 'Thrash', 1, '2018-03-30 02:34:00', '2025-08-13 20:51:59', 'thrash', NULL, 1, 1);
INSERT INTO `tags` VALUES (206, 'Twee', 1, '2018-03-30 15:51:00', '2025-08-13 20:51:59', 'twee', NULL, 1, 1);
INSERT INTO `tags` VALUES (207, 'Indiepop', 1, '2018-03-30 15:51:00', '2025-08-13 20:51:59', 'indiepop', NULL, 1, 1);
INSERT INTO `tags` VALUES (209, 'Bluegrass', 1, '2018-04-03 11:10:00', '2025-08-13 20:51:59', 'bluegrass', NULL, 1, 1);
INSERT INTO `tags` VALUES (211, 'Poetry', 1, '2018-08-07 11:29:09', '2025-08-13 20:51:59', 'poetry', NULL, 1, 1);
INSERT INTO `tags` VALUES (214, 'Cover Bands', 1, '2018-10-08 17:25:53', '2025-08-13 20:51:59', 'cover-bands', NULL, 1, 1);
INSERT INTO `tags` VALUES (215, 'Wave', 1, '2018-10-15 21:52:34', '2025-08-13 20:51:59', 'wave', NULL, 1, 1);
INSERT INTO `tags` VALUES (216, 'Krautrock', 1, '2018-11-16 17:52:55', '2025-08-13 20:51:59', 'krautrock', NULL, 1, 1);
INSERT INTO `tags` VALUES (217, 'Prog', 1, '2018-11-16 18:09:40', '2025-08-13 20:51:59', 'prog', NULL, 1, 1);
INSERT INTO `tags` VALUES (218, 'Theater', 1, '2018-11-27 11:05:53', '2025-08-13 20:51:59', 'theater', NULL, 1, 1);
INSERT INTO `tags` VALUES (219, 'Musical', 1, '2018-11-27 11:05:53', '2025-08-13 20:51:59', 'musical', NULL, 1, 1);
INSERT INTO `tags` VALUES (223, 'Digital Hardcore', 1, '2019-02-01 16:01:53', '2025-08-13 20:51:59', 'digital-hardcore', NULL, 1, 1);
INSERT INTO `tags` VALUES (224, 'Music', 1, '2019-04-12 15:36:21', '2025-08-13 20:51:59', 'music', NULL, 1, 1);
INSERT INTO `tags` VALUES (225, 'Talk', 1, '2019-05-06 10:20:49', '2025-08-13 20:51:59', 'talk', NULL, 1, 1);
INSERT INTO `tags` VALUES (226, 'Demo', 1, '2019-05-06 10:20:49', '2025-08-13 20:51:59', 'demo', NULL, 1, 1);
INSERT INTO `tags` VALUES (227, 'Piano', 1, '2019-08-24 13:40:36', '2025-08-13 20:51:59', 'piano', NULL, 1, 1);
INSERT INTO `tags` VALUES (228, 'Noise Rock', 1, '2019-10-14 10:14:11', '2025-08-13 20:51:59', 'noise-rock', NULL, 1, 1);
INSERT INTO `tags` VALUES (229, 'Dj', 1, '2019-10-21 21:40:19', '2025-08-13 20:51:59', 'dj', NULL, 1, 1);
INSERT INTO `tags` VALUES (230, 'Vintage', 1, '2019-10-30 11:33:47', '2025-08-13 20:52:00', 'vintage', NULL, 1, 1);
INSERT INTO `tags` VALUES (231, 'Shop', 1, '2019-10-30 11:33:47', '2025-08-13 20:52:00', 'shop', NULL, 1, 1);
INSERT INTO `tags` VALUES (232, 'Demoscene', 1, '2019-11-04 00:30:48', '2025-08-13 20:52:00', 'demoscene', NULL, 1, 1);
INSERT INTO `tags` VALUES (233, 'Coldwave', 1, '2019-11-04 13:42:50', '2025-08-13 20:52:00', 'coldwave', NULL, 1, 1);
INSERT INTO `tags` VALUES (234, 'Christmas', 1, '2019-11-08 14:08:25', '2025-08-13 20:52:00', 'christmas', NULL, 1, 1);
INSERT INTO `tags` VALUES (235, 'Spice', 1, '2019-11-11 13:13:36', '2025-08-13 20:52:00', 'spice', NULL, 1, 1);
INSERT INTO `tags` VALUES (237, 'Admin', 1, '2020-01-02 17:40:31', '2025-08-13 20:52:00', 'admin', NULL, 1, 1);
INSERT INTO `tags` VALUES (238, 'Vogue', 1, '2020-01-08 12:50:17', '2025-08-13 20:52:00', 'vogue', NULL, 1, 1);
INSERT INTO `tags` VALUES (239, 'B-movies', 1, '2020-01-15 18:04:00', '2025-08-13 20:52:00', 'b-movies', NULL, 1, 1);
INSERT INTO `tags` VALUES (240, 'Beats', 1, '2020-01-28 17:50:52', '2025-08-13 20:52:00', 'beats', NULL, 1, 1);
INSERT INTO `tags` VALUES (241, 'Pop Punk', 1, '2020-03-02 13:03:31', '2025-08-13 20:52:00', 'pop-punk', NULL, 1, 1);
INSERT INTO `tags` VALUES (242, 'Immersive', 1, '2020-09-02 10:18:36', '2025-08-13 20:52:00', 'immersive', NULL, 1, 1);
INSERT INTO `tags` VALUES (243, 'Yoga', 1, '2020-11-03 14:38:59', '2025-08-13 20:52:00', 'yoga', NULL, 1, 1);
INSERT INTO `tags` VALUES (244, 'Mixology', 1, '2021-04-20 18:19:16', '2025-08-13 20:52:00', 'mixology', NULL, 1, 1);
INSERT INTO `tags` VALUES (245, 'Soft Rock', 1, '2021-05-17 10:53:40', '2025-08-13 20:52:00', 'soft-rock', NULL, 1, 1);
INSERT INTO `tags` VALUES (246, 'Vendors', 1, '2021-05-21 11:02:39', '2025-08-13 20:52:00', 'vendors', NULL, 1, 1);
INSERT INTO `tags` VALUES (247, 'Bikes', 1, '2021-05-22 22:22:48', '2025-08-13 20:52:00', 'bikes', NULL, 1, 1);
INSERT INTO `tags` VALUES (248, 'Ballet', 1, '2021-05-24 10:46:31', '2025-08-13 20:52:00', 'ballet', NULL, 1, 1);
INSERT INTO `tags` VALUES (249, 'Symphony', 1, '2021-05-24 10:46:31', '2025-08-13 20:52:00', 'symphony', NULL, 1, 1);
INSERT INTO `tags` VALUES (250, 'Ballroom', 1, '2021-06-24 10:34:02', '2025-08-13 20:52:00', 'ballroom', NULL, 1, 1);
INSERT INTO `tags` VALUES (251, 'Multimedia', 1, '2021-07-09 10:35:45', '2025-08-13 20:52:00', 'multimedia', NULL, 1, 1);
INSERT INTO `tags` VALUES (252, 'Power Pop', 1, '2021-07-13 19:19:04', '2025-08-13 20:52:01', 'power-pop', NULL, 1, 1);
INSERT INTO `tags` VALUES (253, 'Sports', 1, '2021-07-26 13:19:11', '2025-08-13 20:52:01', 'sports', NULL, 1, 1);
INSERT INTO `tags` VALUES (254, 'Tech House', 1, '2021-08-02 08:37:37', '2025-08-13 20:52:01', 'tech-house', NULL, 1, 1);
INSERT INTO `tags` VALUES (255, 'Instrumental', 1, '2021-08-04 13:55:13', '2025-08-13 20:52:01', 'instrumental', NULL, 1, 1);
INSERT INTO `tags` VALUES (256, 'Alternative', 1, '2021-08-07 10:06:48', '2025-08-13 20:52:01', 'alternative', NULL, 1, 1);
INSERT INTO `tags` VALUES (257, 'Grunge', 1, '2021-08-07 10:42:06', '2025-08-13 20:52:01', 'grunge', NULL, 1, 1);
INSERT INTO `tags` VALUES (258, 'Rockabilly', 1, '2021-08-07 10:50:32', '2025-08-13 20:52:01', 'rockabilly', NULL, 1, 1);
INSERT INTO `tags` VALUES (259, 'Club', 1, '2021-08-07 12:28:14', '2025-08-13 20:52:01', 'club', NULL, 1, 1);
INSERT INTO `tags` VALUES (260, 'Hyperpop', 1, '2021-08-30 21:03:02', '2025-08-13 20:52:01', 'hyperpop', NULL, 1, 1);
INSERT INTO `tags` VALUES (261, 'Chamber', 1, '2021-09-07 11:42:01', '2025-08-13 20:52:01', 'chamber', NULL, 1, 1);
INSERT INTO `tags` VALUES (262, 'Hard Dance', 1, '2021-09-08 22:21:18', '2025-08-13 20:52:01', 'hard-dance', NULL, 1, 1);
INSERT INTO `tags` VALUES (263, 'Magic', 1, '2021-10-24 12:34:20', '2025-08-13 20:52:01', 'magic', NULL, 1, 1);
INSERT INTO `tags` VALUES (264, 'Circus', 1, '2021-10-24 12:34:20', '2025-08-13 20:52:01', 'circus', NULL, 1, 1);
INSERT INTO `tags` VALUES (265, 'Lowfi', 1, '2021-10-28 13:33:00', '2025-08-13 20:52:02', 'lowfi', NULL, 1, 1);
INSERT INTO `tags` VALUES (266, 'Anime', 1, '2021-11-12 18:18:37', '2025-08-13 20:52:02', 'anime', NULL, 1, 1);
INSERT INTO `tags` VALUES (267, 'Ninties', 1, '2021-11-15 15:38:32', '2025-08-13 20:52:02', 'ninties', NULL, 1, 1);
INSERT INTO `tags` VALUES (268, 'Alt-country', 1, '2021-11-15 19:58:46', '2025-08-13 20:52:02', 'alt-country', NULL, 1, 1);
INSERT INTO `tags` VALUES (269, 'Crust', 1, '2021-11-16 19:54:52', '2025-08-13 20:52:02', 'crust', NULL, 1, 1);
INSERT INTO `tags` VALUES (270, 'Dark', 1, '2021-11-16 19:54:52', '2025-08-13 20:52:02', 'dark', NULL, 1, 1);
INSERT INTO `tags` VALUES (271, 'Acoustic', 1, '2021-11-16 19:54:52', '2025-08-13 20:52:02', 'acoustic', NULL, 1, 1);
INSERT INTO `tags` VALUES (272, 'Dream Pop', 1, '2021-11-24 12:44:01', '2025-08-13 20:52:02', 'dream-pop', NULL, 1, 1);
INSERT INTO `tags` VALUES (273, 'Latin', 1, '2022-01-14 15:50:32', '2025-08-13 20:52:02', 'latin', NULL, 1, 1);
INSERT INTO `tags` VALUES (278, 'Polka', 1, '2022-03-03 23:05:47', '2025-08-13 20:52:02', 'polka', NULL, 1, 1);
INSERT INTO `tags` VALUES (291, 'Festival', 1, '2022-03-28 09:53:32', '2025-08-13 20:52:02', 'festival', NULL, 1, 1);
INSERT INTO `tags` VALUES (294, 'Improvised Music', 1, '2022-04-19 00:53:41', '2025-08-13 20:52:02', 'improvised-music', NULL, 1, 1);
INSERT INTO `tags` VALUES (295, 'Jam', 1, '2022-04-19 00:53:41', '2025-08-13 20:52:02', 'jam', NULL, 1, 1);
INSERT INTO `tags` VALUES (296, 'Minimalism', 1, '2022-04-19 09:57:44', '2025-08-13 20:52:02', 'minimalism', NULL, 1, 1);
INSERT INTO `tags` VALUES (297, 'Meditation', 1, '2022-04-19 09:57:44', '2025-08-13 20:52:03', 'meditation', NULL, 1, 1);
INSERT INTO `tags` VALUES (298, 'Tea', 1, '2022-04-19 10:45:16', '2025-08-13 20:52:03', 'tea', NULL, 1, 1);
INSERT INTO `tags` VALUES (299, 'Politics', 1, '2022-04-23 11:40:20', '2025-08-13 20:52:03', 'politics', NULL, 1, 1);
INSERT INTO `tags` VALUES (300, 'Socialism', 1, '2022-04-23 11:40:20', '2025-08-13 20:52:03', 'socialism', NULL, 1, 1);
INSERT INTO `tags` VALUES (301, 'Wine', 1, '2022-05-15 15:40:54', '2025-08-13 20:52:03', 'wine', NULL, 1, 1);
INSERT INTO `tags` VALUES (302, 'Production', 1, '2022-05-19 15:58:44', '2025-08-13 20:52:03', 'production', NULL, 1, 1);
INSERT INTO `tags` VALUES (303, 'Youtube', 1, '2022-05-23 17:47:25', '2025-08-13 20:52:03', 'youtube', NULL, 1, 1);
INSERT INTO `tags` VALUES (307, 'Oddities', 1, '2022-05-31 19:31:29', '2025-08-13 20:52:03', 'oddities', NULL, 1, 1);
INSERT INTO `tags` VALUES (308, 'Kink', 1, '2022-06-25 19:31:23', '2025-08-13 20:52:03', 'kink', NULL, 1, 1);
INSERT INTO `tags` VALUES (309, 'Exercise', 1, '2022-07-31 21:18:27', '2025-08-13 20:52:03', 'exercise', NULL, 1, 1);
INSERT INTO `tags` VALUES (311, 'Noisecore', 1, '2022-08-26 16:56:10', '2025-08-13 20:52:03', 'noisecore', NULL, 1, 1);
INSERT INTO `tags` VALUES (312, 'Weird', 1, '2022-08-30 15:38:55', '2025-08-13 20:52:03', 'weird', NULL, 1, 1);
INSERT INTO `tags` VALUES (321, 'Cocktails', 1, '2022-09-28 11:54:44', '2025-08-13 20:52:03', 'cocktails', NULL, 1, 1);
INSERT INTO `tags` VALUES (322, 'Ukg', 1, '2022-10-24 10:35:05', '2025-08-13 20:52:03', 'ukg', NULL, 1, 1);
INSERT INTO `tags` VALUES (328, 'Alt', 1, '2022-11-26 15:40:09', '2025-08-13 20:52:03', 'alt', NULL, 1, 1);
INSERT INTO `tags` VALUES (329, 'Death', 1, '2022-11-26 15:40:09', '2025-08-13 20:52:03', 'death', NULL, 1, 1);
INSERT INTO `tags` VALUES (331, 'Books', 1, '2022-11-28 12:39:03', '2025-08-13 20:52:04', 'books', NULL, 1, 1);
INSERT INTO `tags` VALUES (332, 'Doom', 1, '2022-11-28 23:39:30', '2025-08-13 20:52:04', 'doom', NULL, 1, 1);
INSERT INTO `tags` VALUES (333, 'Throat Singing', 1, '2022-11-29 16:42:10', '2025-08-13 20:52:04', 'throat-singing', NULL, 1, 1);
INSERT INTO `tags` VALUES (334, 'K-pop', 1, '2022-12-05 21:14:26', '2025-08-13 20:52:04', 'k-pop', NULL, 1, 1);
INSERT INTO `tags` VALUES (335, 'Rocksteady', 1, '2022-12-09 18:09:07', '2025-08-13 20:52:04', 'rocksteady', NULL, 1, 1);
INSERT INTO `tags` VALUES (336, 'Monster Jam', 1, '2022-12-13 14:34:46', '2025-08-13 20:52:04', 'monster-jam', NULL, 1, 1);
INSERT INTO `tags` VALUES (337, '2000s', 1, '2022-12-19 21:17:22', '2025-08-13 20:52:04', '2000s', NULL, 1, 1);
INSERT INTO `tags` VALUES (338, '80s', 1, '2023-01-11 01:00:49', '2025-08-13 20:52:04', '80s', NULL, 1, 1);
INSERT INTO `tags` VALUES (339, 'Zines', 1, '2023-03-08 11:16:43', '2025-08-13 20:52:04', 'zines', NULL, 1, 1);
INSERT INTO `tags` VALUES (344, 'Synth', 1, '2023-04-14 09:51:06', '2025-08-13 20:52:04', 'synth', NULL, 1, 1);
INSERT INTO `tags` VALUES (345, 'Movie', 1, '2023-04-14 10:53:50', '2025-08-13 20:52:04', 'movie', NULL, 1, 1);
INSERT INTO `tags` VALUES (346, 'Sludge', 1, '2023-04-14 10:53:50', '2025-08-13 20:52:04', 'sludge', NULL, 1, 1);
INSERT INTO `tags` VALUES (352, 'Occult', 1, '2023-04-23 19:17:19', '2025-08-13 20:52:04', 'occult', NULL, 1, 1);
INSERT INTO `tags` VALUES (353, 'Live Music', 1, '2023-04-26 12:43:02', '2025-08-13 20:52:04', 'live-music', NULL, 1, 1);
INSERT INTO `tags` VALUES (354, 'Fun', 1, '2023-04-26 12:43:02', '2025-08-13 20:52:04', 'fun', NULL, 1, 1);
INSERT INTO `tags` VALUES (355, 'Indierock', 1, '2023-05-03 16:26:30', '2025-08-13 20:52:05', 'indierock', NULL, 1, 1);
INSERT INTO `tags` VALUES (357, 'Grind', 1, '2023-05-03 16:34:46', '2025-08-13 20:52:05', 'grind', NULL, 1, 1);
INSERT INTO `tags` VALUES (359, 'Club Cafe', 1, '2023-05-06 11:54:59', '2025-08-13 20:52:05', 'club-cafe', NULL, 1, 1);
INSERT INTO `tags` VALUES (418, 'Standup Comedy', 1, '2023-06-26 09:14:15', '2025-08-13 20:52:05', 'standup-comedy', NULL, 1, 1);
INSERT INTO `tags` VALUES (419, 'Blues', 1, '2023-07-05 13:14:49', '2025-08-13 20:52:05', 'blues', NULL, 1, 1);
INSERT INTO `tags` VALUES (420, 'Roots', 1, '2023-07-05 13:14:49', '2025-08-13 20:52:05', 'roots', NULL, 1, 1);
INSERT INTO `tags` VALUES (435, 'Artrock', 1, '2023-07-10 15:16:43', '2025-08-13 20:52:05', 'artrock', NULL, 1, 1);
INSERT INTO `tags` VALUES (436, 'Bedroompop', 1, '2023-07-10 15:16:43', '2025-08-13 20:52:05', 'bedroompop', NULL, 1, 1);
INSERT INTO `tags` VALUES (437, 'Lounge', 1, '2023-11-08 14:49:54', '2025-08-13 20:52:05', 'lounge', NULL, 1, 1);
INSERT INTO `tags` VALUES (442, 'Cinema', 3, '2023-11-21 16:23:13', '2025-08-13 20:52:05', 'cinema', NULL, 1, 1);
INSERT INTO `tags` VALUES (443, '45s', 1, '2023-11-28 23:27:56', '2025-08-13 20:52:05', '45s', NULL, 1, 1);
INSERT INTO `tags` VALUES (444, 'Furry', 3, '2023-12-07 13:32:23', '2025-08-13 20:52:05', 'furry', NULL, 1, 1);
INSERT INTO `tags` VALUES (445, 'BDSM', 3, '2023-12-07 13:32:50', '2025-08-13 20:52:05', 'bdsm', NULL, 1, 1);
INSERT INTO `tags` VALUES (446, 'Halftime', 1, '2023-12-23 13:11:19', '2025-08-13 20:52:05', 'halftime', NULL, 1, 1);
INSERT INTO `tags` VALUES (453, 'Flute', 1, '2024-01-03 17:31:49', '2025-08-13 20:52:05', 'flute', NULL, 1, 1);
INSERT INTO `tags` VALUES (455, 'Deep Dubstep', 1, '2024-01-03 17:31:49', '2025-08-13 20:52:05', 'deep-dubstep', NULL, 1, 1);
INSERT INTO `tags` VALUES (457, 'Meet Up', 1, '2024-01-05 13:25:39', '2025-08-13 20:52:06', 'meet-up', NULL, 1, 1);
INSERT INTO `tags` VALUES (458, 'Networking', 1, '2024-01-05 13:25:39', '2025-08-13 20:52:06', 'networking', NULL, 1, 1);
INSERT INTO `tags` VALUES (513, 'Oi', 1, '2024-01-31 14:22:52', '2025-08-13 20:52:06', 'oi', NULL, 1, 1);
INSERT INTO `tags` VALUES (530, 'DIVE BAR', 3, '2024-04-21 11:35:09', '2025-08-13 20:52:06', 'dive-bar', NULL, 1, 1);
INSERT INTO `tags` VALUES (531, 'Dungeon Synth', 1, '2024-06-11 10:45:21', '2025-08-13 20:52:06', 'dungeon-synth', NULL, 1, 1);
INSERT INTO `tags` VALUES (532, 'Health', 3, '2024-08-07 17:55:56', '2025-08-13 20:52:06', 'health', NULL, 1, 1);
INSERT INTO `tags` VALUES (533, 'Carnival', 3, '2024-08-07 17:56:02', '2025-08-13 20:52:06', 'carnival', NULL, 1, 1);
INSERT INTO `tags` VALUES (534, 'Paranormal', 3, '2024-08-13 13:14:38', '2025-08-13 20:52:06', 'paranormal', NULL, 1, 1);
INSERT INTO `tags` VALUES (535, 'Fitness', 3, '2024-08-22 10:37:26', '2025-08-13 20:52:06', 'fitness', NULL, 1, 1);
INSERT INTO `tags` VALUES (536, 'Amapiano', 3, '2024-08-26 16:39:17', '2025-08-13 20:52:06', 'amapiano', NULL, 1, 1);
INSERT INTO `tags` VALUES (537, 'Deep Tech', 3, '2024-09-13 10:56:10', '2025-08-13 20:52:06', 'deep-tech', NULL, 1, 1);
INSERT INTO `tags` VALUES (538, 'Dembow', 1, '2025-02-20 22:36:02', '2025-08-13 20:52:06', 'dembow', NULL, 1, 1);
INSERT INTO `tags` VALUES (539, 'Reggaeton', NULL, '2025-02-20 22:36:27', '2025-08-13 20:52:06', 'reggaeton', NULL, 1, 1);
INSERT INTO `tags` VALUES (540, 'Opera', 3, '2025-02-27 13:51:42', '2025-08-13 20:52:06', 'opera', NULL, 1, 1);
INSERT INTO `tags` VALUES (541, 'Audiovisual', 3, '2025-03-23 14:34:07', '2025-08-13 20:52:06', 'audiovisual', NULL, 1, 1);
INSERT INTO `tags` VALUES (542, 'Readings', 3, '2025-03-25 00:13:50', '2025-08-13 20:52:07', 'readings', NULL, 1, 1);
INSERT INTO `tags` VALUES (543, 'Jersey Club', 1, '2025-03-25 09:16:48', '2025-08-13 20:52:07', 'jersey-club', NULL, 1, 1);
INSERT INTO `tags` VALUES (544, 'Folk Punk', 3, '2025-04-08 15:46:44', '2025-08-13 20:52:07', 'folk-punk', NULL, 1, 1);
INSERT INTO `tags` VALUES (545, 'Clown', 4, '2025-06-02 15:01:52', '2025-08-13 20:52:07', 'clown', NULL, 1, 1);
INSERT INTO `tags` VALUES (546, 'Blessed Be', 4, '2025-06-11 21:16:39', '2025-08-13 20:52:07', 'blessed-be', NULL, 1, 1);
INSERT INTO `tags` VALUES (548, 'Hot Dogs', 3, '2025-07-01 07:40:49', '2025-08-13 20:52:07', 'hot-dogs', NULL, 1, 1);
INSERT INTO `tags` VALUES (549, 'BYOB', 3, '2025-07-01 07:40:58', '2025-08-13 20:52:07', 'byob', NULL, 1, 1);
INSERT INTO `tags` VALUES (550, 'Cornhole', 3, '2025-07-01 07:41:09', '2025-08-13 20:52:07', 'cornhole', NULL, 1, 1);
INSERT INTO `tags` VALUES (551, 'Pot Luck', 3, '2025-07-01 07:44:05', '2025-08-13 20:52:07', 'pot-luck', NULL, 1, 1);
INSERT INTO `tags` VALUES (552, 'Deconstructed Club', 1, '2025-07-03 19:14:17', '2025-08-13 20:52:07', 'deconstructed-club', NULL, 1, 1);
INSERT INTO `tags` VALUES (553, 'Taxidermy', 1, '2025-07-10 16:48:56', '2025-08-13 20:52:07', 'taxidermy', NULL, 1, 1);
INSERT INTO `tags` VALUES (554, 'Education', 1, '2025-07-10 16:50:10', '2025-08-13 20:52:07', 'education', NULL, 1, 1);
INSERT INTO `tags` VALUES (555, 'Hands-On', 1, '2025-07-10 16:50:17', '2025-08-13 20:52:07', 'hands-on', NULL, 1, 1);
INSERT INTO `tags` VALUES (556, 'Curiosity', 1, '2025-07-10 16:50:22', '2025-08-13 20:52:07', 'curiosity', NULL, 1, 1);
INSERT INTO `tags` VALUES (557, 'Sculpture', 1, '2025-07-10 16:50:28', '2025-08-13 20:52:07', 'sculpture', NULL, 1, 1);
INSERT INTO `tags` VALUES (558, 'Beatdown', 1, '2025-07-11 16:39:46', '2025-08-13 20:52:08', 'beatdown', NULL, 1, 1);
INSERT INTO `tags` VALUES (562, 'City Pop', 3, '2025-08-12 14:13:00', '2025-08-13 20:52:08', 'city-pop', NULL, 1, 1);
INSERT INTO `tags` VALUES (563, 'Nu-Disco', 1, '2025-08-20 10:59:28', '2025-11-20 20:31:59', 'nu-disco', NULL, 1, NULL);
INSERT INTO `tags` VALUES (564, 'Dayparty', 3, '2025-08-25 17:39:12', '2025-11-20 20:31:59', 'dayparty', NULL, 1, NULL);
INSERT INTO `tags` VALUES (565, 'Sober', 3, '2025-09-01 11:08:04', '2025-11-20 20:31:59', 'sober', NULL, 1, NULL);
INSERT INTO `tags` VALUES (568, 'Plants', 3, '2025-09-29 23:45:28', '2025-11-20 20:31:59', 'plants', NULL, 1, NULL);
INSERT INTO `tags` VALUES (569, 'Skateboarding', 3, '2025-10-13 14:36:59', '2025-11-20 20:31:59', 'skateboarding', NULL, 1, NULL);
INSERT INTO `tags` VALUES (572, 'Sci-fi', 1, '2025-11-20 10:53:56', '2025-11-20 20:31:50', 'sci-fi', NULL, 1, 1);
INSERT INTO `tags` VALUES (576, 'Metalcore', 3, '2025-12-10 19:06:07', '2025-12-10 19:06:07', 'metalcore', NULL, 1, 1);
INSERT INTO `tags` VALUES (577, 'Slam', 1, '2025-12-10 14:22:45', '2025-12-10 14:22:45', 'slam', NULL, NULL, NULL);
INSERT INTO `tags` VALUES (578, 'Horrorcore', 1, '2025-12-11 15:13:06', '2025-12-11 15:13:06', 'horrorcore', NULL, NULL, NULL);
INSERT INTO `tags` VALUES (582, 'Afrobeats', 1, '2026-01-20 14:40:25', '2026-01-20 14:40:25', 'afrobeats', NULL, NULL, NULL);
INSERT INTO `tags` VALUES (583, 'Caribbean', 1, '2026-01-20 14:40:27', '2026-01-20 14:40:27', 'caribbean', NULL, NULL, NULL);
INSERT INTO `tags` VALUES (584, 'Lynchian', 4, '2026-01-22 21:46:42', '2026-01-22 21:46:42', 'lynchian', NULL, NULL, NULL);
INSERT INTO `tags` VALUES (585, 'University', 3, '2026-02-06 14:04:32', '2026-02-06 14:04:32', 'university', 'Related to one of the local universities', NULL, NULL);
INSERT INTO `tags` VALUES (589, 'Screamo', 1, '2026-04-01 22:40:00', '2026-04-01 22:40:00', 'screamo', NULL, NULL, NULL);
INSERT INTO `tags` VALUES (590, 'Riddm', 1, '2026-04-02 19:30:29', '2026-04-02 19:30:29', 'riddm', 'An electronic subgenre of dubstep', NULL, NULL);
INSERT INTO `tags` VALUES (591, 'Riddim', NULL, '2026-04-02 19:31:22', '2026-04-02 19:31:22', 'riddim', 'a subgenre of dubstep', NULL, NULL);

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
