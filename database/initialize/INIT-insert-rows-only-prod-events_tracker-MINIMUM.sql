/*
 INIT - Insert Rows Only - Events Tracker - MINIMUM

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
-- Records of actions - ENHANCED seed
-- ----------------------------
INSERT INTO `actions` VALUES (1, 'Create', NULL, NULL, NULL, NULL, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00');
INSERT INTO `actions` VALUES (2, 'Update', NULL, NULL, NULL, NULL, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00');
INSERT INTO `actions` VALUES (3, 'Delete', NULL, NULL, NULL, NULL, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00');
INSERT INTO `actions` VALUES (4, 'Login', NULL, NULL, NULL, NULL, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00');
INSERT INTO `actions` VALUES (5, 'Logout', NULL, NULL, NULL, NULL, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00');
INSERT INTO `actions` VALUES (6, 'Follow', NULL, NULL, NULL, NULL, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00');
INSERT INTO `actions` VALUES (7, 'Unfollow', NULL, NULL, NULL, NULL, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00');
INSERT INTO `actions` VALUES (8, 'Attending', NULL, NULL, NULL, NULL, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00');
INSERT INTO `actions` VALUES (9, 'Unattending', NULL, NULL, NULL, NULL, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00');
INSERT INTO `actions` VALUES (10, 'Activate', 'User', NULL, 'Activate user', NULL, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00');
INSERT INTO `actions` VALUES (11, 'Suspend', 'User', NULL, 'Suspend user', NULL, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00');
INSERT INTO `actions` VALUES (12, 'Reminder', 'User', NULL, 'Email reminder', NULL, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00');
INSERT INTO `actions` VALUES (13, 'Impersonate', NULL, NULL, NULL, NULL, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00');
INSERT INTO `actions` VALUES (14, 'Failed Login', NULL, NULL, NULL, NULL, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00');

-- ----------------------------
-- Records of content_types - ENHANCED
-- ----------------------------
INSERT INTO `content_types` VALUES (1, 'Plain Text', '0000-00-00 00:00:00', '0000-00-00 00:00:00');
INSERT INTO `content_types` VALUES (2, 'HTML', '0000-00-00 00:00:00', '0000-00-00 00:00:00');

-- ----------------------------
-- Records of entity_statuses - ENHANCED
-- ----------------------------
INSERT INTO `entity_statuses` VALUES (1, 'Draft', '2016-02-25 07:54:14', '2016-02-25 07:54:14');
INSERT INTO `entity_statuses` VALUES (2, 'Active', '2016-02-25 07:54:14', '2016-02-25 07:54:14');
INSERT INTO `entity_statuses` VALUES (3, 'Inactive', '2016-02-25 07:54:14', '2016-02-25 07:54:14');

-- ----------------------------
-- Records of entity_types - ENHANCED
-- ----------------------------
INSERT INTO `entity_types` VALUES (1, 'Space', 'space', 'Space for events', '2016-02-25 07:54:14', '2016-02-25 07:54:14');
INSERT INTO `entity_types` VALUES (2, 'Group', 'group', 'Collection of individuals', '2016-02-25 07:54:14', '2016-02-25 07:54:14');
INSERT INTO `entity_types` VALUES (3, 'Individual', 'individual', 'Single individual', '2016-02-25 07:54:14', '2016-02-25 07:54:14');
INSERT INTO `entity_types` VALUES (4, 'Interest', 'interest', 'Interest or topic', '2016-02-25 07:54:14', '2016-02-25 07:54:14');

-- ----------------------------
-- Records of event_statuses - ENHANCED
-- ----------------------------
INSERT INTO `event_statuses` VALUES (1, 'Draft', '2016-02-25 07:54:14', '2016-02-25 07:54:14');
INSERT INTO `event_statuses` VALUES (2, 'Proposal', '2016-02-25 07:54:14', '2016-02-25 07:54:14');
INSERT INTO `event_statuses` VALUES (3, 'Approved', '2016-02-25 07:54:14', '2016-02-25 07:54:14');
INSERT INTO `event_statuses` VALUES (4, 'Happening Now', '2016-02-25 07:54:14', '2016-02-25 07:54:14');
INSERT INTO `event_statuses` VALUES (5, 'Past', '2016-02-25 07:54:14', '2016-02-25 07:54:14');
INSERT INTO `event_statuses` VALUES (6, 'Rejected', '2016-02-25 07:54:14', '2016-02-25 07:54:14');
INSERT INTO `event_statuses` VALUES (7, 'Cancelled', '2016-02-25 07:54:14', '2016-02-25 07:54:14');

-- ----------------------------
-- Records of groups
-- ----------------------------
INSERT INTO `groups` VALUES (1, 'admin', 'Admin', 100, '2017-05-19 01:57:45', '2017-05-19 01:57:45', '');
INSERT INTO `groups` VALUES (2, 'super_admin', 'Super Admin', 999, '2017-06-20 12:53:25', '2017-06-20 12:53:25', 'Super admin');

-- ----------------------------
-- Records of occurrence_days - Enhanced
-- ----------------------------
INSERT INTO `occurrence_days` VALUES (1, 'Sunday', '2016-02-25 07:54:14', '2016-02-25 07:54:14');
INSERT INTO `occurrence_days` VALUES (2, 'Monday', '2016-02-25 07:54:14', '2016-02-25 07:54:14');
INSERT INTO `occurrence_days` VALUES (3, 'Tuesday', '2016-02-25 07:54:15', '2016-02-25 07:54:15');
INSERT INTO `occurrence_days` VALUES (4, 'Wednesday', '2016-02-25 07:54:15', '2016-02-25 07:54:15');
INSERT INTO `occurrence_days` VALUES (5, 'Thursday', '2016-02-25 07:54:15', '2016-02-25 07:54:15');
INSERT INTO `occurrence_days` VALUES (6, 'Friday', '2016-02-25 07:54:15', '2016-02-25 07:54:15');
INSERT INTO `occurrence_days` VALUES (7, 'Saturday', '2016-02-25 07:54:15', '2016-02-25 07:54:15');

-- ----------------------------
-- Records of occurrence_types - Enhanced
-- ----------------------------
INSERT INTO `occurrence_types` VALUES (1, 'No Schedule', '2016-02-25 07:54:14', '2016-02-25 07:54:14');
INSERT INTO `occurrence_types` VALUES (2, 'Weekly', '2016-02-25 07:54:14', '2016-02-25 07:54:14');
INSERT INTO `occurrence_types` VALUES (3, 'Biweekly', '2016-02-25 07:54:14', '2016-02-25 07:54:14');
INSERT INTO `occurrence_types` VALUES (4, 'Monthly', '2016-02-25 07:54:14', '2016-02-25 07:54:14');
INSERT INTO `occurrence_types` VALUES (5, 'Bimonthly', '2016-02-25 07:54:14', '2016-02-25 07:54:14');
INSERT INTO `occurrence_types` VALUES (6, 'Yearly', '2016-02-25 07:54:14', '2016-02-25 07:54:14');

-- ----------------------------
-- Records of occurrence_weeks - Enhanced
-- ----------------------------
INSERT INTO `occurrence_weeks` VALUES (1, 'First', '2016-02-25 07:54:15', '2016-02-25 07:54:15');
INSERT INTO `occurrence_weeks` VALUES (2, 'Second', '2016-02-25 07:54:15', '2016-02-25 07:54:15');
INSERT INTO `occurrence_weeks` VALUES (3, 'Third', '2016-02-25 07:54:15', '2016-02-25 07:54:15');
INSERT INTO `occurrence_weeks` VALUES (4, 'Fourth', '2016-02-25 07:54:15', '2016-02-25 07:54:15');
INSERT INTO `occurrence_weeks` VALUES (5, 'Last', '2016-02-25 07:54:15', '2016-02-25 07:54:15');

-- ----------------------------
-- Records of permissions - Enhanced
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
-- Records of relation_status - Enhanced
-- ----------------------------
INSERT INTO `relation_status` VALUES (1, 'Request');
INSERT INTO `relation_status` VALUES (2, 'Pending');
INSERT INTO `relation_status` VALUES (3, 'Confirmed');

-- ----------------------------
-- Records of relation_type - Enhanced
-- ----------------------------
INSERT INTO `relation_type` VALUES (1, 'User');
INSERT INTO `relation_type` VALUES (2, 'Entity');
INSERT INTO `relation_type` VALUES (3, 'Group');

-- ----------------------------
-- Records of response_types - Enhanced
-- ----------------------------
INSERT INTO `response_types` VALUES (1, 'Attending', '2016-03-16 14:59:30', '2016-03-16 14:59:33');
INSERT INTO `response_types` VALUES (2, 'Interested', '2016-03-16 14:59:53', '2016-03-16 14:59:57');
INSERT INTO `response_types` VALUES (3, 'Uninterested', '2016-03-16 15:00:09', '2016-03-16 15:00:14');
INSERT INTO `response_types` VALUES (4, 'Cannot Attend', '2016-03-16 15:00:34', '2016-03-16 15:00:37');

-- ----------------------------
-- Records of review_types - Enhanced
-- ----------------------------
INSERT INTO `review_types` VALUES (1, 'Informational', '2016-11-29 00:00:00', '2016-11-29 00:00:00');
INSERT INTO `review_types` VALUES (2, 'Positive', '2016-11-29 00:00:00', '2016-11-29 00:00:00');
INSERT INTO `review_types` VALUES (3, 'Neutral', '2016-11-29 00:00:00', '2016-11-29 00:00:00');
INSERT INTO `review_types` VALUES (4, 'Negative', '2016-11-29 00:00:00', '2016-11-29 00:00:00');

-- ----------------------------
-- Records of roles - Minimum
-- ----------------------------
INSERT INTO `roles` VALUES (1, 'Venue', 'venue', 'Public site for events', '2016-02-25 07:54:14', '2016-02-25 07:54:14');

-- ----------------------------
-- Records of user_statuses - Enhanced
-- ----------------------------
INSERT INTO `user_statuses` VALUES (1, 'Pending', 0, '2016-02-25 07:54:14', '2016-02-25 07:54:14');
INSERT INTO `user_statuses` VALUES (2, 'Active', 1, '2016-02-25 07:54:14', '2016-02-25 07:54:14');
INSERT INTO `user_statuses` VALUES (3, 'Suspended', 0, '2016-02-25 07:54:14', '2016-02-25 07:54:14');
INSERT INTO `user_statuses` VALUES (4, 'Banned', 0, '2017-04-18 12:54:30', '0000-00-00 00:00:00');
INSERT INTO `user_statuses` VALUES (5, 'Deleted', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00');

-- ----------------------------
-- Records of visibilities - enhanced
-- ----------------------------
INSERT INTO `visibilities` VALUES (1, 'Proposal', '2016-02-25 07:54:14', '2016-02-25 07:54:14');
INSERT INTO `visibilities` VALUES (2, 'Private', '2016-02-25 07:54:14', '2016-02-25 07:54:14');
INSERT INTO `visibilities` VALUES (3, 'Public', '2016-02-25 07:54:14', '2016-02-25 07:54:14');
INSERT INTO `visibilities` VALUES (4, 'Guarded', '0000-00-00 00:00:00', '0000-00-00 00:00:00');
INSERT INTO `visibilities` VALUES (5, 'Cancelled', '0000-00-00 00:00:00', '0000-00-00 00:00:00');

SET FOREIGN_KEY_CHECKS = 1;
