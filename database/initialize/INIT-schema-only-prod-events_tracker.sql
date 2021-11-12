/*
 Navicat Premium Data Transfer

 Source Server         : arcane.city
 Source Server Type    : MySQL
 Source Server Version : 80017
 Source Host           : 127.0.0.1:3306
 Source Schema         : events_tracker

 Target Server Type    : MySQL
 Target Server Version : 80017
 File Encoding         : 65001

 Date: 25/11/2019 01:44:16
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for access
-- ----------------------------
DROP TABLE IF EXISTS `access`;
CREATE TABLE `access`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NOT NULL,
  `object_id` int(10) UNSIGNED NOT NULL,
  `object_type_id` int(10) UNSIGNED NOT NULL,
  `access_type_id` int(10) UNSIGNED NOT NULL,
  `label` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `level` int(11) NOT NULL,
  `can_grant` tinyint(1) NOT NULL,
  `created_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for access_types
-- ----------------------------
DROP TABLE IF EXISTS `access_types`;
CREATE TABLE `access_types`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `label` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `description` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `level` int(11) NOT NULL,
  `created_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for actions
-- ----------------------------
DROP TABLE IF EXISTS `actions`;
CREATE TABLE `actions`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `object_table` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL,
  `child_object_table` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL,
  `description` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL,
  `changes` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL,
  `order` int(11) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 14 CHARACTER SET = utf8 COLLATE = utf8_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for activities
-- ----------------------------
DROP TABLE IF EXISTS `activities`;
CREATE TABLE `activities`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `object_table` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL,
  `object_name` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL,
  `object_id` int(11) NOT NULL,
  `child_object_table` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL,
  `child_object_name` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL,
  `child_object_id` int(11) NULL DEFAULT NULL,
  `message` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL,
  `changes` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL,
  `action_id` int(11) NULL DEFAULT NULL,
  `created_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  `ip_address` varchar(64) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `activities_user_id_index`(`user_id`) USING BTREE,
  INDEX `activities_object_id_index`(`object_id`) USING BTREE,
  INDEX `activities_action_id_index`(`action_id`) USING BTREE,
  INDEX `activities_object_table_index`(`object_table`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 4533 CHARACTER SET = utf8 COLLATE = utf8_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for alias_entity
-- ----------------------------
DROP TABLE IF EXISTS `alias_entity`;
CREATE TABLE `alias_entity`  (
  `entity_id` int(10) UNSIGNED NOT NULL,
  `alias_id` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  INDEX `alias_entity_alias_id_index`(`alias_id`) USING BTREE,
  INDEX `alias_entity_entity_id_index`(`entity_id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_unicode_ci ROW_FORMAT = Fixed;

-- ----------------------------
-- Table structure for aliases
-- ----------------------------
DROP TABLE IF EXISTS `aliases`;
CREATE TABLE `aliases`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(64) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `created_at` datetime(0) NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP(0),
  `updated_at` datetime(0) NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP(0),
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 13 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for attending_status
-- ----------------------------
DROP TABLE IF EXISTS `attending_status`;
CREATE TABLE `attending_status`  (
  `id` int(11) NOT NULL,
  `name` int(32) NULL DEFAULT NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `created_by` int(11) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP(0),
  `updated_by` int(11) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 1 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Fixed;

-- ----------------------------
-- Table structure for blog_entity
-- ----------------------------
DROP TABLE IF EXISTS `blog_entity`;
CREATE TABLE `blog_entity`  (
  `blog_id` int(10) UNSIGNED NOT NULL,
  `entity_id` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL
) ENGINE = InnoDB CHARACTER SET = utf8 COLLATE = utf8_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for blog_tag
-- ----------------------------
DROP TABLE IF EXISTS `blog_tag`;
CREATE TABLE `blog_tag`  (
  `blog_id` int(10) UNSIGNED NOT NULL,
  `tag_id` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL
) ENGINE = InnoDB CHARACTER SET = utf8 COLLATE = utf8_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for blogs
-- ----------------------------
DROP TABLE IF EXISTS `blogs`;
CREATE TABLE `blogs`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `slug` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `body` longtext CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `menu_id` int(10) UNSIGNED NULL DEFAULT NULL,
  `content_type_id` int(10) UNSIGNED NULL DEFAULT NULL,
  `visibility_id` int(10) UNSIGNED NULL DEFAULT NULL,
  `sort_order` int(11) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_admin` tinyint(1) NOT NULL DEFAULT 0,
  `allow_html` tinyint(1) NOT NULL DEFAULT 0,
  `created_by` int(11) NOT NULL DEFAULT 1,
  `updated_by` int(11) NULL DEFAULT NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 2 CHARACTER SET = utf8 COLLATE = utf8_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for comments
-- ----------------------------
DROP TABLE IF EXISTS `comments`;
CREATE TABLE `comments`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `message` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `response_to` int(10) UNSIGNED NULL DEFAULT NULL,
  `commentable_id` int(10) UNSIGNED NULL DEFAULT NULL,
  `commentable_type` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL,
  `created_by` int(11) NOT NULL DEFAULT 1,
  `updated_by` int(11) NULL DEFAULT NULL,
  `created_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `comments_response_to_foreign`(`response_to`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 96 CHARACTER SET = utf8 COLLATE = utf8_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for contact_entity
-- ----------------------------
DROP TABLE IF EXISTS `contact_entity`;
CREATE TABLE `contact_entity`  (
  `entity_id` int(10) UNSIGNED NOT NULL,
  `contact_id` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  INDEX `contact_entity_entity_id_index`(`entity_id`) USING BTREE,
  INDEX `contact_entity_contact_id_index`(`contact_id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_unicode_ci ROW_FORMAT = Fixed;

-- ----------------------------
-- Table structure for contacts
-- ----------------------------
DROP TABLE IF EXISTS `contacts`;
CREATE TABLE `contacts`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `email` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `phone` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `other` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `type` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `visibility_id` int(10) UNSIGNED NULL DEFAULT NULL,
  `created_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 22 CHARACTER SET = utf8 COLLATE = utf8_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for content_types
-- ----------------------------
DROP TABLE IF EXISTS `content_types`;
CREATE TABLE `content_types`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `created_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 3 CHARACTER SET = utf8 COLLATE = utf8_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for default_settings
-- ----------------------------
DROP TABLE IF EXISTS `default_settings`;
CREATE TABLE `default_settings`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL,
  `description` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL,
  `type` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL,
  `value` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL,
  `created_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for entities
-- ----------------------------
DROP TABLE IF EXISTS `entities`;
CREATE TABLE `entities`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `slug` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `short` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `entity_type_id` int(11) NULL DEFAULT NULL,
  `entity_status_id` int(11) NULL DEFAULT NULL,
  `created_by` int(11) NOT NULL DEFAULT 1,
  `updated_by` int(11) NULL DEFAULT NULL,
  `created_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  `facebook_username` varchar(64) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `twitter_username` varchar(64) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 354 CHARACTER SET = utf8 COLLATE = utf8_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for entity_attribute
-- ----------------------------
DROP TABLE IF EXISTS `entity_attribute`;
CREATE TABLE `entity_attribute`  (
  `id` int(11) NOT NULL DEFAULT 0,
  `entity_id` int(11) NOT NULL DEFAULT 0,
  `key` varchar(32) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL DEFAULT '',
  `value` varchar(128) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 1 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for entity_event
-- ----------------------------
DROP TABLE IF EXISTS `entity_event`;
CREATE TABLE `entity_event`  (
  `event_id` int(10) UNSIGNED NOT NULL,
  `entity_id` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  INDEX `entity_event_event_id_index`(`event_id`) USING BTREE,
  INDEX `entity_event_entity_id_index`(`entity_id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_unicode_ci ROW_FORMAT = Fixed;

-- ----------------------------
-- Table structure for entity_link
-- ----------------------------
DROP TABLE IF EXISTS `entity_link`;
CREATE TABLE `entity_link`  (
  `entity_id` int(10) UNSIGNED NOT NULL,
  `link_id` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  INDEX `entity_link_entity_id_index`(`entity_id`) USING BTREE,
  INDEX `entity_link_link_id_index`(`link_id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_unicode_ci ROW_FORMAT = Fixed;

-- ----------------------------
-- Table structure for entity_permission
-- ----------------------------
DROP TABLE IF EXISTS `entity_permission`;
CREATE TABLE `entity_permission`  (
  `entity_id` int(10) UNSIGNED NOT NULL,
  `permission_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  INDEX `entity_permission_entity_id_index`(`entity_id`) USING BTREE,
  INDEX `entity_permission_permission_id_index`(`permission_id`) USING BTREE,
  INDEX `entity_permission_user_id_index`(`user_id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_unicode_ci ROW_FORMAT = Fixed;

-- ----------------------------
-- Table structure for entity_photo
-- ----------------------------
DROP TABLE IF EXISTS `entity_photo`;
CREATE TABLE `entity_photo`  (
  `entity_id` int(10) UNSIGNED NOT NULL,
  `photo_id` int(10) UNSIGNED NOT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `is_approved` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  INDEX `entity_photo_entity_id_index`(`entity_id`) USING BTREE,
  INDEX `entity_photo_photo_id_index`(`photo_id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_unicode_ci ROW_FORMAT = Fixed;

-- ----------------------------
-- Table structure for entity_post
-- ----------------------------
DROP TABLE IF EXISTS `entity_post`;
CREATE TABLE `entity_post`  (
  `entity_id` int(10) UNSIGNED NULL DEFAULT NULL,
  `post_id` int(10) UNSIGNED NULL DEFAULT NULL,
  `created_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE = InnoDB CHARACTER SET = utf8 COLLATE = utf8_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for entity_profile
-- ----------------------------
DROP TABLE IF EXISTS `entity_profile`;
CREATE TABLE `entity_profile`  (
  `id` int(11) NOT NULL DEFAULT 0,
  `entity_id` int(11) NOT NULL DEFAULT 0,
  `description` text CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `status_id` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 1 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for entity_relation
-- ----------------------------
DROP TABLE IF EXISTS `entity_relation`;
CREATE TABLE `entity_relation`  (
  `id` int(11) NOT NULL DEFAULT 0,
  `relation_id` int(11) NOT NULL DEFAULT 0,
  `entity_id` int(11) NOT NULL DEFAULT 0,
  `target_id` int(11) NOT NULL DEFAULT 0,
  `rank` int(11) NOT NULL DEFAULT 0,
  `relation_status_id` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `entity_relation_relation_id_index`(`relation_id`) USING BTREE,
  INDEX `entity_relation_entity_id_index`(`entity_id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 1 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Fixed;

-- ----------------------------
-- Table structure for entity_role
-- ----------------------------
DROP TABLE IF EXISTS `entity_role`;
CREATE TABLE `entity_role`  (
  `role_id` int(10) UNSIGNED NOT NULL,
  `entity_id` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  INDEX `entity_role_role_id_index`(`role_id`) USING BTREE,
  INDEX `entity_role_entity_id_index`(`entity_id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_unicode_ci ROW_FORMAT = Fixed;

-- ----------------------------
-- Table structure for entity_series
-- ----------------------------
DROP TABLE IF EXISTS `entity_series`;
CREATE TABLE `entity_series`  (
  `series_id` int(10) UNSIGNED NOT NULL,
  `entity_id` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  INDEX `entity_series_series_id_index`(`series_id`) USING BTREE,
  INDEX `entity_series_entity_id_index`(`entity_id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_unicode_ci ROW_FORMAT = Fixed;

-- ----------------------------
-- Table structure for entity_statuses
-- ----------------------------
DROP TABLE IF EXISTS `entity_statuses`;
CREATE TABLE `entity_statuses`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `created_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 4 CHARACTER SET = utf8 COLLATE = utf8_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for entity_tag
-- ----------------------------
DROP TABLE IF EXISTS `entity_tag`;
CREATE TABLE `entity_tag`  (
  `entity_id` int(10) UNSIGNED NOT NULL,
  `tag_id` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  INDEX `entity_tag_entity_id_index`(`entity_id`) USING BTREE,
  INDEX `entity_tag_tag_id_index`(`tag_id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_unicode_ci ROW_FORMAT = Fixed;

-- ----------------------------
-- Table structure for entity_thread
-- ----------------------------
DROP TABLE IF EXISTS `entity_thread`;
CREATE TABLE `entity_thread`  (
  `entity_id` int(10) UNSIGNED NOT NULL,
  `thread_id` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  INDEX `entity_thread_entity_id_index`(`entity_id`) USING BTREE,
  INDEX `entity_thread_thread_id_index`(`thread_id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8 COLLATE = utf8_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for entity_type_permission
-- ----------------------------
DROP TABLE IF EXISTS `entity_type_permission`;
CREATE TABLE `entity_type_permission`  (
  `entity_id` int(10) UNSIGNED NOT NULL,
  `permission_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  INDEX `entity_type_permission_entity_id_index`(`entity_id`) USING BTREE,
  INDEX `entity_type_permission_permission_id_index`(`permission_id`) USING BTREE,
  INDEX `entity_type_permission_user_id_index`(`user_id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_unicode_ci ROW_FORMAT = Fixed;

-- ----------------------------
-- Table structure for entity_types
-- ----------------------------
DROP TABLE IF EXISTS `entity_types`;
CREATE TABLE `entity_types`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `slug` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `short` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `created_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 5 CHARACTER SET = utf8 COLLATE = utf8_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for event_attending
-- ----------------------------
DROP TABLE IF EXISTS `event_attending`;
CREATE TABLE `event_attending`  (
  `id` int(11) NOT NULL,
  `event_id` int(11) NULL DEFAULT NULL,
  `attending_status_id` int(3) NULL DEFAULT NULL,
  `attendance_confirmed` tinyint(3) NULL DEFAULT NULL,
  `attendance_liked` tinyint(3) NULL DEFAULT NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `created_by` int(11) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 1 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Fixed;

-- ----------------------------
-- Table structure for event_link
-- ----------------------------
DROP TABLE IF EXISTS `event_link`;
CREATE TABLE `event_link`  (
  `event_id` int(10) UNSIGNED NOT NULL,
  `link_id` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  INDEX `event_link_event_id_index`(`event_id`) USING BTREE,
  INDEX `event_link_link_id_index`(`link_id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_unicode_ci ROW_FORMAT = Fixed;

-- ----------------------------
-- Table structure for event_permission
-- ----------------------------
DROP TABLE IF EXISTS `event_permission`;
CREATE TABLE `event_permission`  (
  `event_id` int(10) UNSIGNED NOT NULL,
  `permission_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  INDEX `event_permission_event_id_index`(`event_id`) USING BTREE,
  INDEX `event_permission_permission_id_index`(`permission_id`) USING BTREE,
  INDEX `event_permission_user_id_index`(`user_id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_unicode_ci ROW_FORMAT = Fixed;

-- ----------------------------
-- Table structure for event_photo
-- ----------------------------
DROP TABLE IF EXISTS `event_photo`;
CREATE TABLE `event_photo`  (
  `event_id` int(10) UNSIGNED NOT NULL,
  `photo_id` int(10) UNSIGNED NOT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `is_approved` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  INDEX `event_photo_event_id_index`(`event_id`) USING BTREE,
  INDEX `event_photo_photo_id_index`(`photo_id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_unicode_ci ROW_FORMAT = Fixed;

-- ----------------------------
-- Table structure for event_responses
-- ----------------------------
DROP TABLE IF EXISTS `event_responses`;
CREATE TABLE `event_responses`  (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `event_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `response_type_id` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `event_responses_event_id_index`(`event_id`) USING BTREE,
  INDEX `event_responses_user_id_index`(`user_id`) USING BTREE,
  INDEX `event_responses_response_type_id_index`(`response_type_id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 906 CHARACTER SET = utf8 COLLATE = utf8_unicode_ci ROW_FORMAT = Fixed;

-- ----------------------------
-- Table structure for event_reviews
-- ----------------------------
DROP TABLE IF EXISTS `event_reviews`;
CREATE TABLE `event_reviews`  (
  `event_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `review_type_id` int(10) UNSIGNED NOT NULL,
  `attended` tinyint(1) NOT NULL,
  `confirmed` tinyint(1) NULL DEFAULT 0,
  `expectation` tinyint(4) NOT NULL,
  `rating` tinyint(4) NOT NULL,
  `review` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `created_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  INDEX `event_reviews_event_id_index`(`event_id`) USING BTREE,
  INDEX `event_reviews_user_id_index`(`user_id`) USING BTREE,
  INDEX `event_reviews_review_type_id_index`(`review_type_id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for event_role
-- ----------------------------
DROP TABLE IF EXISTS `event_role`;
CREATE TABLE `event_role`  (
  `id` int(11) NOT NULL,
  `name` int(32) NULL DEFAULT NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `created_by` int(11) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP(0),
  `updated_by` int(11) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 1 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Fixed;

-- ----------------------------
-- Table structure for event_role_user
-- ----------------------------
DROP TABLE IF EXISTS `event_role_user`;
CREATE TABLE `event_role_user`  (
  `id` int(11) NOT NULL,
  `name` int(32) NULL DEFAULT NULL,
  `user_id` int(32) NULL DEFAULT NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `created_by` int(11) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP(0),
  `updated_by` int(11) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 1 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Fixed;

-- ----------------------------
-- Table structure for event_statuses
-- ----------------------------
DROP TABLE IF EXISTS `event_statuses`;
CREATE TABLE `event_statuses`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `created_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 8 CHARACTER SET = utf8 COLLATE = utf8_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for event_tag
-- ----------------------------
DROP TABLE IF EXISTS `event_tag`;
CREATE TABLE `event_tag`  (
  `event_id` int(10) UNSIGNED NOT NULL,
  `tag_id` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  INDEX `event_tag_event_id_index`(`event_id`) USING BTREE,
  INDEX `event_tag_tag_id_index`(`tag_id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_unicode_ci ROW_FORMAT = Fixed;

-- ----------------------------
-- Table structure for event_template_photo
-- ----------------------------
DROP TABLE IF EXISTS `event_template_photo`;
CREATE TABLE `event_template_photo`  (
  `event_template_id` int(10) UNSIGNED NOT NULL,
  `photo_id` int(10) UNSIGNED NOT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `is_approved` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  INDEX `event_template_photo_event_template_id_index`(`event_template_id`) USING BTREE,
  INDEX `event_template_photo_photo_id_index`(`photo_id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_unicode_ci ROW_FORMAT = Fixed;

-- ----------------------------
-- Table structure for event_thread
-- ----------------------------
DROP TABLE IF EXISTS `event_thread`;
CREATE TABLE `event_thread`  (
  `event_id` int(10) UNSIGNED NOT NULL,
  `thread_id` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE = InnoDB CHARACTER SET = utf8 COLLATE = utf8_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for event_type_permission
-- ----------------------------
DROP TABLE IF EXISTS `event_type_permission`;
CREATE TABLE `event_type_permission`  (
  `event_type_id` int(10) UNSIGNED NOT NULL,
  `permission_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  INDEX `event_type_permission_event_type_id_index`(`event_type_id`) USING BTREE,
  INDEX `event_type_permission_permission_id_index`(`permission_id`) USING BTREE,
  INDEX `event_type_permission_user_id_index`(`user_id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_unicode_ci ROW_FORMAT = Fixed;

-- ----------------------------
-- Table structure for event_types
-- ----------------------------
DROP TABLE IF EXISTS `event_types`;
CREATE TABLE `event_types`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `created_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 16 CHARACTER SET = utf8 COLLATE = utf8_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for event_watcher
-- ----------------------------
DROP TABLE IF EXISTS `event_watcher`;
CREATE TABLE `event_watcher`  (
  `id` int(11) NOT NULL,
  `user_id` int(11) NULL DEFAULT NULL,
  `watch_type_id` tinyint(4) NULL DEFAULT NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `created_by` int(11) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  `updated_by` int(11) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 1 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Fixed;

-- ----------------------------
-- Table structure for events
-- ----------------------------
DROP TABLE IF EXISTS `events`;
CREATE TABLE `events`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `slug` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `short` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL,
  `description` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL,
  `visibility_id` int(10) UNSIGNED NULL DEFAULT NULL,
  `event_status_id` int(10) UNSIGNED NULL DEFAULT NULL,
  `event_type_id` int(11) NULL DEFAULT NULL,
  `is_benefit` tinyint(1) NOT NULL DEFAULT 0,
  `promoter_id` int(10) UNSIGNED NULL DEFAULT NULL,
  `venue_id` int(10) UNSIGNED NULL DEFAULT NULL,
  `attending` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `like` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `presale_price` decimal(5, 2) NULL DEFAULT NULL,
  `door_price` decimal(5, 2) NULL DEFAULT NULL,
  `soundcheck_at` timestamp(0) NULL DEFAULT NULL,
  `door_at` timestamp(0) NULL DEFAULT NULL,
  `start_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  `end_at` timestamp(0) NULL DEFAULT NULL,
  `min_age` tinyint(3) UNSIGNED NULL DEFAULT NULL,
  `series_id` int(10) UNSIGNED NULL DEFAULT NULL,
  `primary_link` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL,
  `ticket_link` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL,
  `created_by` int(11) NOT NULL DEFAULT 1,
  `updated_by` int(11) NULL DEFAULT NULL,
  `created_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  `cancelled_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `events_event_status_id_foreign`(`event_status_id`) USING BTREE,
  INDEX `events_promoter_id_foreign`(`promoter_id`) USING BTREE,
  INDEX `events_venue_id_foreign`(`venue_id`) USING BTREE,
  INDEX `events_visibility_id_foreign`(`visibility_id`) USING BTREE,
  INDEX `events_event_type_id_foreign`(`event_type_id`) USING BTREE,
  INDEX `events_created_by_foreign`(`created_by`) USING BTREE,
  INDEX `events_series_id_foreign`(`series_id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 2234 CHARACTER SET = utf8 COLLATE = utf8_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for follows
-- ----------------------------
DROP TABLE IF EXISTS `follows`;
CREATE TABLE `follows`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `object_type` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL,
  `object_id` int(10) UNSIGNED NULL DEFAULT NULL,
  `created_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `follows_user_id_index`(`user_id`) USING BTREE,
  INDEX `follows_object_type_index`(`object_type`) USING BTREE,
  INDEX `follows_object_id_index`(`object_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 228 CHARACTER SET = utf8 COLLATE = utf8_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for forums
-- ----------------------------
DROP TABLE IF EXISTS `forums`;
CREATE TABLE `forums`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `slug` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL,
  `visibility_id` int(10) UNSIGNED NULL DEFAULT NULL,
  `sort_order` int(11) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` int(11) NOT NULL DEFAULT 1,
  `updated_by` int(11) NULL DEFAULT NULL,
  `created_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 2 CHARACTER SET = utf8 COLLATE = utf8_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for group_permission
-- ----------------------------
DROP TABLE IF EXISTS `group_permission`;
CREATE TABLE `group_permission`  (
  `group_id` int(10) UNSIGNED NOT NULL,
  `permission_id` int(10) UNSIGNED NOT NULL,
  INDEX `group_permission_permission_id_foreign`(`permission_id`) USING BTREE,
  INDEX `group_permission_group_id_foreign`(`group_id`) USING BTREE,
  CONSTRAINT `group_permission_group_id_foreign` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `group_permission_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB CHARACTER SET = utf8 COLLATE = utf8_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for group_user
-- ----------------------------
DROP TABLE IF EXISTS `group_user`;
CREATE TABLE `group_user`  (
  `group_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  INDEX `group_user_group_id_foreign`(`group_id`) USING BTREE,
  CONSTRAINT `group_user_group_id_foreign` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB CHARACTER SET = utf8 COLLATE = utf8_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for groups
-- ----------------------------
DROP TABLE IF EXISTS `groups`;
CREATE TABLE `groups`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `label` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `level` int(11) NOT NULL,
  `created_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  `description` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 3 CHARACTER SET = utf8 COLLATE = utf8_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for likes
-- ----------------------------
DROP TABLE IF EXISTS `likes`;
CREATE TABLE `likes`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `object_type` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL,
  `object_id` int(10) UNSIGNED NULL DEFAULT NULL,
  `created_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 43 CHARACTER SET = utf8 COLLATE = utf8_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for link_user
-- ----------------------------
DROP TABLE IF EXISTS `link_user`;
CREATE TABLE `link_user`  (
  `user_id` int(10) UNSIGNED NOT NULL,
  `link_id` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  INDEX `link_user_user_id_index`(`user_id`) USING BTREE,
  INDEX `link_user_link_id_index`(`link_id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_unicode_ci ROW_FORMAT = Fixed;

-- ----------------------------
-- Table structure for links
-- ----------------------------
DROP TABLE IF EXISTS `links`;
CREATE TABLE `links`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `url` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL,
  `text` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL,
  `image` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL,
  `api` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL,
  `title` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL,
  `confirm` tinyint(1) NOT NULL DEFAULT 0,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 82 CHARACTER SET = utf8 COLLATE = utf8_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for location_types
-- ----------------------------
DROP TABLE IF EXISTS `location_types`;
CREATE TABLE `location_types`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `created_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 7 CHARACTER SET = utf8 COLLATE = utf8_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for locations
-- ----------------------------
DROP TABLE IF EXISTS `locations`;
CREATE TABLE `locations`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(64) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `slug` varchar(64) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `attn` varchar(64) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL,
  `address_one` varchar(128) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL,
  `address_two` varchar(128) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL,
  `city` varchar(128) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL,
  `neighborhood` varchar(64) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL,
  `state` varchar(64) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL,
  `postcode` varchar(32) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL,
  `country` varchar(64) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL,
  `latitude` decimal(11, 8) NULL DEFAULT NULL,
  `longitude` decimal(11, 8) NULL DEFAULT NULL,
  `location_type_id` int(10) UNSIGNED NOT NULL,
  `entity_id` int(10) UNSIGNED NOT NULL,
  `capacity` int(11) NOT NULL,
  `map_url` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL,
  `created_by` int(11) NOT NULL DEFAULT 1,
  `updated_by` int(11) NULL DEFAULT NULL,
  `created_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  `visibility_id` int(11) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `locations_location_type_id_index`(`location_type_id`) USING BTREE,
  INDEX `locations_entity_id_index`(`entity_id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 81 CHARACTER SET = utf8 COLLATE = utf8_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for menus
-- ----------------------------
DROP TABLE IF EXISTS `menus`;
CREATE TABLE `menus`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `slug` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `body` longtext CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `menu_parent_id` int(10) UNSIGNED NULL DEFAULT NULL,
  `visibility_id` int(10) UNSIGNED NULL DEFAULT NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 2 CHARACTER SET = utf8 COLLATE = utf8_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for migrations
-- ----------------------------
DROP TABLE IF EXISTS `migrations`;
CREATE TABLE `migrations`  (
  `migration` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE = MyISAM AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for object_types
-- ----------------------------
DROP TABLE IF EXISTS `object_types`;
CREATE TABLE `object_types`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL,
  `table` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL,
  `created_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for occurrence_days
-- ----------------------------
DROP TABLE IF EXISTS `occurrence_days`;
CREATE TABLE `occurrence_days`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `created_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 8 CHARACTER SET = utf8 COLLATE = utf8_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for occurrence_types
-- ----------------------------
DROP TABLE IF EXISTS `occurrence_types`;
CREATE TABLE `occurrence_types`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `created_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 7 CHARACTER SET = utf8 COLLATE = utf8_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for occurrence_weeks
-- ----------------------------
DROP TABLE IF EXISTS `occurrence_weeks`;
CREATE TABLE `occurrence_weeks`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `created_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 6 CHARACTER SET = utf8 COLLATE = utf8_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for password_resets
-- ----------------------------
DROP TABLE IF EXISTS `password_resets`;
CREATE TABLE `password_resets`  (
  `email` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `token` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `created_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  INDEX `password_resets_email_index`(`email`) USING BTREE,
  INDEX `password_resets_token_index`(`token`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for permissions
-- ----------------------------
DROP TABLE IF EXISTS `permissions`;
CREATE TABLE `permissions`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `label` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `description` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `level` int(11) NOT NULL,
  `created_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 25 CHARACTER SET = utf8 COLLATE = utf8_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for photo_series
-- ----------------------------
DROP TABLE IF EXISTS `photo_series`;
CREATE TABLE `photo_series`  (
  `series_id` int(10) UNSIGNED NOT NULL,
  `photo_id` int(10) UNSIGNED NOT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `is_approved` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  INDEX `photo_series_series_id_index`(`series_id`) USING BTREE,
  INDEX `photo_series_photo_id_index`(`photo_id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_unicode_ci ROW_FORMAT = Fixed;

-- ----------------------------
-- Table structure for photo_user
-- ----------------------------
DROP TABLE IF EXISTS `photo_user`;
CREATE TABLE `photo_user`  (
  `user_id` int(10) UNSIGNED NOT NULL,
  `photo_id` int(10) UNSIGNED NOT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `is_approved` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  INDEX `photo_user_user_id_index`(`user_id`) USING BTREE,
  INDEX `photo_user_photo_id_index`(`photo_id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_unicode_ci ROW_FORMAT = Fixed;

-- ----------------------------
-- Table structure for photos
-- ----------------------------
DROP TABLE IF EXISTS `photos`;
CREATE TABLE `photos`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `thumbnail` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `path` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `caption` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `is_public` tinyint(1) NOT NULL DEFAULT 1,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `is_event` tinyint(1) NOT NULL DEFAULT 0,
  `is_approved` tinyint(1) NOT NULL DEFAULT 0,
  `created_by` int(11) NOT NULL DEFAULT 1,
  `updated_by` int(11) NULL DEFAULT NULL,
  `created_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 2735 CHARACTER SET = utf8 COLLATE = utf8_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for post_tag
-- ----------------------------
DROP TABLE IF EXISTS `post_tag`;
CREATE TABLE `post_tag`  (
  `tag_id` int(10) UNSIGNED NOT NULL,
  `post_id` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  INDEX `post_tag_tag_id_index`(`tag_id`) USING BTREE,
  INDEX `post_tag_post_id_index`(`post_id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8 COLLATE = utf8_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for posts
-- ----------------------------
DROP TABLE IF EXISTS `posts`;
CREATE TABLE `posts`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `thread_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `slug` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL,
  `body` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `allow_html` tinyint(1) NOT NULL DEFAULT 0,
  `content_type_id` int(10) UNSIGNED NULL DEFAULT NULL,
  `visibility_id` int(10) UNSIGNED NULL DEFAULT NULL,
  `recipient_id` int(11) NULL DEFAULT NULL,
  `reply_to` int(11) NULL DEFAULT NULL,
  `likes` int(11) NOT NULL,
  `views` int(11) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` int(11) NOT NULL,
  `updated_by` int(11) NULL DEFAULT NULL,
  `created_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `posts_thread_id_index`(`thread_id`) USING BTREE,
  CONSTRAINT `posts_thread_id_foreign` FOREIGN KEY (`thread_id`) REFERENCES `threads` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 106 CHARACTER SET = utf8 COLLATE = utf8_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for profiles
-- ----------------------------
DROP TABLE IF EXISTS `profiles`;
CREATE TABLE `profiles`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `bio` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL,
  `alias` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL,
  `location` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL,
  `visibility_id` int(10) UNSIGNED NULL DEFAULT NULL,
  `facebook_username` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL,
  `twitter_username` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL,
  `created_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  `first_name` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL,
  `last_name` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL,
  `default_theme` varchar(32) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 239 CHARACTER SET = utf8 COLLATE = utf8_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for relation
-- ----------------------------
DROP TABLE IF EXISTS `relation`;
CREATE TABLE `relation`  (
  `id` int(11) NOT NULL DEFAULT 0,
  `name` varchar(64) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL DEFAULT '',
  `relation_type_id` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 1 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for relation_attribute
-- ----------------------------
DROP TABLE IF EXISTS `relation_attribute`;
CREATE TABLE `relation_attribute`  (
  `id` int(11) NOT NULL DEFAULT 0,
  `relation_type_id` int(11) NOT NULL DEFAULT 0,
  `key` varchar(128) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL DEFAULT '',
  `value` varchar(128) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 1 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for relation_status
-- ----------------------------
DROP TABLE IF EXISTS `relation_status`;
CREATE TABLE `relation_status`  (
  `id` int(11) NOT NULL DEFAULT 0,
  `name` varchar(16) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 1 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for relation_type
-- ----------------------------
DROP TABLE IF EXISTS `relation_type`;
CREATE TABLE `relation_type`  (
  `id` int(11) NOT NULL DEFAULT 0,
  `name` varchar(64) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 1 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for relations
-- ----------------------------
DROP TABLE IF EXISTS `relations`;
CREATE TABLE `relations`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `end_at` timestamp(0) NULL DEFAULT NULL,
  `created_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for response_types
-- ----------------------------
DROP TABLE IF EXISTS `response_types`;
CREATE TABLE `response_types`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `created_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 5 CHARACTER SET = utf8 COLLATE = utf8_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for review_types
-- ----------------------------
DROP TABLE IF EXISTS `review_types`;
CREATE TABLE `review_types`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `created_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 5 CHARACTER SET = utf8 COLLATE = utf8_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for roles
-- ----------------------------
DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `slug` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `short` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `created_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 8 CHARACTER SET = utf8 COLLATE = utf8_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for series
-- ----------------------------
DROP TABLE IF EXISTS `series`;
CREATE TABLE `series`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `slug` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `short` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL,
  `description` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL,
  `visibility_id` int(10) UNSIGNED NULL DEFAULT NULL,
  `event_type_id` int(10) UNSIGNED NULL DEFAULT NULL,
  `occurrence_type_id` int(10) UNSIGNED NULL DEFAULT NULL,
  `occurrence_week_id` int(10) UNSIGNED NULL DEFAULT NULL,
  `occurrence_day_id` int(10) UNSIGNED NULL DEFAULT NULL,
  `hold_date` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  `is_benefit` tinyint(1) NOT NULL DEFAULT 0,
  `promoter_id` int(10) UNSIGNED NULL DEFAULT NULL,
  `venue_id` int(10) UNSIGNED NULL DEFAULT NULL,
  `attending` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `like` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `presale_price` decimal(5, 2) NULL DEFAULT NULL,
  `door_price` decimal(5, 2) NULL DEFAULT NULL,
  `primary_link` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL,
  `ticket_link` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL,
  `founded_at` timestamp(0) NULL DEFAULT NULL,
  `cancelled_at` timestamp(0) NULL DEFAULT NULL,
  `soundcheck_at` timestamp(0) NULL DEFAULT NULL,
  `door_at` timestamp(0) NULL DEFAULT NULL,
  `start_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  `end_at` timestamp(0) NULL DEFAULT NULL,
  `length` int(11) NULL DEFAULT NULL,
  `min_age` tinyint(3) UNSIGNED NULL DEFAULT NULL,
  `created_by` int(11) NOT NULL DEFAULT 1,
  `updated_by` int(11) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `series_visibility_id_foreign`(`visibility_id`) USING BTREE,
  INDEX `series_event_type_id_foreign`(`event_type_id`) USING BTREE,
  INDEX `series_occurrence_type_id_foreign`(`occurrence_type_id`) USING BTREE,
  INDEX `series_occurrence_week_id_foreign`(`occurrence_week_id`) USING BTREE,
  INDEX `series_occurrence_day_id_foreign`(`occurrence_day_id`) USING BTREE,
  INDEX `series_promoter_id_foreign`(`promoter_id`) USING BTREE,
  INDEX `series_venue_id_foreign`(`venue_id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 105 CHARACTER SET = utf8 COLLATE = utf8_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for series_link
-- ----------------------------
DROP TABLE IF EXISTS `series_link`;
CREATE TABLE `series_link`  (
  `series_id` int(10) UNSIGNED NOT NULL,
  `link_id` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  INDEX `series_link_series_id_index`(`series_id`) USING BTREE,
  INDEX `series_link_link_id_index`(`link_id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_unicode_ci ROW_FORMAT = Fixed;

-- ----------------------------
-- Table structure for series_photo
-- ----------------------------
DROP TABLE IF EXISTS `series_photo`;
CREATE TABLE `series_photo`  (
  `series_id` int(10) UNSIGNED NOT NULL,
  `photo_id` int(10) UNSIGNED NOT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `is_approved` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  INDEX `series_photo_series_id_index`(`series_id`) USING BTREE,
  INDEX `series_photo_photo_id_index`(`photo_id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_unicode_ci ROW_FORMAT = Fixed;

-- ----------------------------
-- Table structure for series_tag
-- ----------------------------
DROP TABLE IF EXISTS `series_tag`;
CREATE TABLE `series_tag`  (
  `series_id` int(10) UNSIGNED NOT NULL,
  `tag_id` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  INDEX `series_tag_series_id_index`(`series_id`) USING BTREE,
  INDEX `series_tag_tag_id_index`(`tag_id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_unicode_ci ROW_FORMAT = Fixed;

-- ----------------------------
-- Table structure for series_thread
-- ----------------------------
DROP TABLE IF EXISTS `series_thread`;
CREATE TABLE `series_thread`  (
  `series_id` int(10) UNSIGNED NULL DEFAULT NULL,
  `thread_id` int(10) UNSIGNED NULL DEFAULT NULL,
  `created_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE = InnoDB CHARACTER SET = utf8 COLLATE = utf8_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for settings
-- ----------------------------
DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `name` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL,
  `description` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL,
  `type` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL,
  `value` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL,
  `created_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for social_facebook_accounts
-- ----------------------------
DROP TABLE IF EXISTS `social_facebook_accounts`;
CREATE TABLE `social_facebook_accounts`  (
  `user_id` int(11) NOT NULL,
  `provider_user_id` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `provider` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `created_at` datetime(0) NULL DEFAULT NULL,
  `updated_at` datetime(0) NULL DEFAULT NULL,
  PRIMARY KEY (`user_id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for tag_thread
-- ----------------------------
DROP TABLE IF EXISTS `tag_thread`;
CREATE TABLE `tag_thread`  (
  `tag_id` int(10) UNSIGNED NOT NULL,
  `thread_id` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  INDEX `tag_thread_tag_id_index`(`tag_id`) USING BTREE,
  INDEX `tag_thread_thread_id_index`(`thread_id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8 COLLATE = utf8_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for tag_types
-- ----------------------------
DROP TABLE IF EXISTS `tag_types`;
CREATE TABLE `tag_types`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `created_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 6 CHARACTER SET = utf8 COLLATE = utf8_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for tags
-- ----------------------------
DROP TABLE IF EXISTS `tags`;
CREATE TABLE `tags`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `tag_type_id` int(11) NULL DEFAULT NULL,
  `created_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 236 CHARACTER SET = utf8 COLLATE = utf8_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for thread_categories
-- ----------------------------
DROP TABLE IF EXISTS `thread_categories`;
CREATE TABLE `thread_categories`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `forum_id` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `thread_categories_forum_id_index`(`forum_id`) USING BTREE,
  CONSTRAINT `thread_categories_forum_id_foreign` FOREIGN KEY (`forum_id`) REFERENCES `forums` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 5 CHARACTER SET = utf8 COLLATE = utf8_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for threads
-- ----------------------------
DROP TABLE IF EXISTS `threads`;
CREATE TABLE `threads`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `forum_id` int(10) UNSIGNED NOT NULL,
  `thread_category_id` int(10) UNSIGNED NULL DEFAULT NULL,
  `name` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `slug` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL,
  `body` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `allow_html` tinyint(1) NOT NULL DEFAULT 0,
  `visibility_id` int(10) UNSIGNED NULL DEFAULT NULL,
  `recipient_id` int(11) NULL DEFAULT NULL,
  `sort_order` int(11) NOT NULL,
  `is_edittable` tinyint(1) NOT NULL DEFAULT 1,
  `likes` int(11) NOT NULL,
  `views` int(11) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` int(11) NOT NULL DEFAULT 1,
  `updated_by` int(11) NULL DEFAULT NULL,
  `created_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  `locked_at` timestamp(0) NULL DEFAULT NULL,
  `locked_by` int(11) NULL DEFAULT NULL,
  `event_id` int(11) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `threads_forum_id_index`(`forum_id`) USING BTREE,
  INDEX `created_by_index`(`created_by`) USING BTREE,
  CONSTRAINT `threads_forum_id_foreign` FOREIGN KEY (`forum_id`) REFERENCES `forums` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 189 CHARACTER SET = utf8 COLLATE = utf8_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for user_statuses
-- ----------------------------
DROP TABLE IF EXISTS `user_statuses`;
CREATE TABLE `user_statuses`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `can_login` tinyint(1) NULL DEFAULT NULL,
  `created_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 6 CHARACTER SET = utf8 COLLATE = utf8_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for users
-- ----------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `email` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `password` varchar(60) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `remember_token` varchar(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL,
  `created_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  `user_status_id` int(11) NULL DEFAULT 0,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `users_email_unique`(`email`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 422 CHARACTER SET = utf8 COLLATE = utf8_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for visibilities
-- ----------------------------
DROP TABLE IF EXISTS `visibilities`;
CREATE TABLE `visibilities`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `created_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 6 CHARACTER SET = utf8 COLLATE = utf8_unicode_ci ROW_FORMAT = Dynamic;

SET FOREIGN_KEY_CHECKS = 1;
