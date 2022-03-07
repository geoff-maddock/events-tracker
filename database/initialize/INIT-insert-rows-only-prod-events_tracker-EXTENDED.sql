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
-- Records of event_types - Enhanced
-- ----------------------------
INSERT INTO `event_types` VALUES (1, 'Art Opening', '2016-02-25 07:54:14', '2016-02-25 07:54:14');
INSERT INTO `event_types` VALUES (2, 'Concert', '2016-02-25 07:54:14', '2016-02-25 07:54:14');
INSERT INTO `event_types` VALUES (3, 'Festival', '2016-02-25 07:54:14', '2016-02-25 07:54:14');
INSERT INTO `event_types` VALUES (4, 'House Show', '2016-02-25 07:54:14', '2016-02-25 07:54:14');
INSERT INTO `event_types` VALUES (5, 'Club Night', '2016-02-25 07:54:14', '2016-02-25 07:54:14');
INSERT INTO `event_types` VALUES (6, 'Film Screening', '2016-03-18 11:43:02', '2016-03-18 11:43:07');
INSERT INTO `event_types` VALUES (7, 'Radio Show', '2016-03-18 11:43:19', '2016-03-18 11:43:21');
INSERT INTO `event_types` VALUES (8, 'Rave', '2016-03-28 18:20:51', '2016-03-28 18:20:54');
INSERT INTO `event_types` VALUES (9, 'Benefit', '2016-04-19 15:02:54', '0000-00-00 00:00:00');
INSERT INTO `event_types` VALUES (10, 'Renegade', '2016-06-16 11:01:36', '0000-00-00 00:00:00');
INSERT INTO `event_types` VALUES (11, 'Pop-up', '2016-06-16 11:01:50', '0000-00-00 00:00:00');
INSERT INTO `event_types` VALUES (12, 'Activism', '2017-01-31 15:40:50', '0000-00-00 00:00:00');
INSERT INTO `event_types` VALUES (13, 'Open Mic', '2017-01-31 15:40:50', '0000-00-00 00:00:00');
INSERT INTO `event_types` VALUES (14, 'Karaoke', '0000-00-00 00:00:00', '0000-00-00 00:00:00');
INSERT INTO `event_types` VALUES (15, 'Workshop', '0000-00-00 00:00:00', '0000-00-00 00:00:00');

-- ----------------------------
-- Records of forums - Enhanced
-- ----------------------------
INSERT INTO `forums` VALUES (1, 'Forum', 'forum', 'General forum', 3, 0, 1, 1, 1, '2017-06-05 13:50:27', '2017-06-05 13:50:27');

-- ----------------------------
-- Records of group_permission - ENHANCED
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
-- Records of groups
-- ----------------------------
INSERT INTO `groups` VALUES (1, 'admin', 'Admin', 100, '2017-05-19 01:57:45', '2017-05-19 01:57:45', '');
INSERT INTO `groups` VALUES (2, 'super_admin', 'Super Admin', 999, '2017-06-20 12:53:25', '2017-06-20 12:53:25', 'Super admin');

-- ----------------------------
-- Records of location_types - Enhanced
-- ----------------------------
INSERT INTO `location_types` VALUES (1, 'Public', '2016-02-25 07:54:14', '2016-02-25 07:54:14');
INSERT INTO `location_types` VALUES (2, 'Business', '2016-02-25 07:54:14', '2016-02-25 07:54:14');
INSERT INTO `location_types` VALUES (3, 'Home', '2016-02-25 07:54:14', '2016-02-25 07:54:14');
INSERT INTO `location_types` VALUES (4, 'Outdoor', '2016-02-25 07:54:14', '2016-02-25 07:54:14');
INSERT INTO `location_types` VALUES (5, 'Gallery', '2016-03-17 13:36:13', '2016-03-17 13:36:18');
INSERT INTO `location_types` VALUES (6, 'DIY', '2016-07-08 11:37:20', '0000-00-00 00:00:00');

-- ----------------------------
-- Records of menus - Enhanced
-- ----------------------------
INSERT INTO `menus` VALUES (1, 'About', 'about', '', 0, 3, '2019-09-19 10:22:29', '2019-09-25 23:50:43');

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
-- Records of relation - Enhanced
-- ----------------------------
INSERT INTO `relation` VALUES (1, 'Friend', 1);
INSERT INTO `relation` VALUES (2, 'Alias', 1);
INSERT INTO `relation` VALUES (3, 'Founder', 3);
INSERT INTO `relation` VALUES (4, 'Officer', 3);
INSERT INTO `relation` VALUES (5, 'Member', 3);
INSERT INTO `relation` VALUES (6, 'Fan', 3);

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
-- Records of roles - Enhanced
-- ----------------------------
INSERT INTO `roles` VALUES (1, 'Venue', 'venue', 'Public site for events', '2016-02-25 07:54:14', '2016-02-25 07:54:14');
INSERT INTO `roles` VALUES (2, 'Artist', 'artist', 'Visual artist', '2016-02-25 07:54:14', '2016-02-25 07:54:14');
INSERT INTO `roles` VALUES (3, 'Producer', 'producer', 'Music producer', '2016-02-25 07:54:14', '2016-02-25 07:54:14');
INSERT INTO `roles` VALUES (4, 'DJ', 'dj', 'DJ', '2016-02-25 07:54:14', '2016-02-25 07:54:14');
INSERT INTO `roles` VALUES (5, 'Promoter', 'promoter', 'Event promoter', '2016-02-25 07:54:14', '2016-02-25 07:54:14');
INSERT INTO `roles` VALUES (6, 'Shop', 'shop', 'Retail shop', '0000-00-00 00:00:00', '0000-00-00 00:00:00');
INSERT INTO `roles` VALUES (7, 'Band', 'band', 'Live band', '0000-00-00 00:00:00', '0000-00-00 00:00:00');

-- ----------------------------
-- Records of tag_types - Enhanced
-- ----------------------------
INSERT INTO `tag_types` VALUES (1, 'Genre', '2016-02-25 07:54:14', '2016-02-25 07:54:14');
INSERT INTO `tag_types` VALUES (2, 'Region', '2016-02-25 07:54:14', '2016-02-25 07:54:14');
INSERT INTO `tag_types` VALUES (3, 'Category', '2016-02-25 07:54:14', '2016-02-25 07:54:14');
INSERT INTO `tag_types` VALUES (4, 'Topics', '2016-02-25 07:54:14', '2016-02-25 07:54:14');
INSERT INTO `tag_types` VALUES (5, 'Reaction', '2016-02-25 07:54:14', '2016-02-25 07:54:14');

-- ----------------------------
-- Records of tags - Enhanced
-- ----------------------------
INSERT INTO `tags` VALUES (1, 'Jungle', 1, '2016-02-25 07:54:14', '2016-02-25 07:54:14');
INSERT INTO `tags` VALUES (2, 'Club Music', 1, '2016-02-25 07:54:14', '2016-02-25 07:54:14');
INSERT INTO `tags` VALUES (3, 'Footwork', 1, '2016-02-25 07:54:14', '2016-02-25 07:54:14');
INSERT INTO `tags` VALUES (4, 'Hardcore', 1, '2016-02-25 18:43:41', '2016-02-25 18:43:41');
INSERT INTO `tags` VALUES (5, 'Uk Garage', 1, '2016-02-25 22:33:58', '2016-02-25 22:33:58');
INSERT INTO `tags` VALUES (7, 'Bass', 1, '2016-02-26 02:13:43', '2016-02-26 02:13:43');
INSERT INTO `tags` VALUES (8, 'Trap', 1, '2016-02-26 02:13:43', '2016-02-26 02:13:43');
INSERT INTO `tags` VALUES (9, 'Grime', 1, '2016-02-26 02:13:43', '2016-02-26 02:13:43');
INSERT INTO `tags` VALUES (10, 'Techno', 1, '2016-02-26 02:18:57', '2016-02-26 02:18:57');
INSERT INTO `tags` VALUES (11, 'Rave', 1, '2016-02-26 02:18:57', '2016-02-26 02:18:57');
INSERT INTO `tags` VALUES (12, 'Noise', 1, '2016-02-26 19:07:58', '2016-02-26 19:07:58');
INSERT INTO `tags` VALUES (13, 'Rock', 1, '2016-02-26 19:07:58', '2016-02-26 19:07:58');
INSERT INTO `tags` VALUES (14, 'Houseparty', 1, '2016-02-26 19:07:58', '2016-02-26 19:07:58');
INSERT INTO `tags` VALUES (15, 'Dubstep', 1, '2016-02-26 20:33:14', '2016-02-26 20:33:14');
INSERT INTO `tags` VALUES (16, 'Modular Synth', 1, '2016-02-26 23:35:30', '2016-02-26 23:35:30');
INSERT INTO `tags` VALUES (17, 'Industrial', 1, '2016-02-26 23:35:30', '2016-02-26 23:35:30');
INSERT INTO `tags` VALUES (18, 'Ambient', 1, '2016-02-27 19:02:15', '2016-02-27 19:02:15');
INSERT INTO `tags` VALUES (19, 'Metal', 1, '2016-02-27 19:02:15', '2016-02-27 19:02:15');
INSERT INTO `tags` VALUES (20, 'Coffee', 1, '2016-02-27 19:03:14', '2016-02-27 19:03:14');
INSERT INTO `tags` VALUES (21, 'Drum And Bass', 1, '2016-02-29 06:47:50', '2016-02-29 06:47:50');
INSERT INTO `tags` VALUES (22, 'Experimental', 1, '2016-02-29 08:06:39', '2016-02-29 08:06:39');
INSERT INTO `tags` VALUES (23, 'Dance', 1, '2016-02-29 08:08:25', '2016-02-29 08:08:25');
INSERT INTO `tags` VALUES (24, 'Electronic', 1, '2016-02-29 08:45:26', '2016-02-29 08:45:26');
INSERT INTO `tags` VALUES (25, 'Deathrock', 1, '2016-02-29 16:45:08', '2016-02-29 16:45:08');
INSERT INTO `tags` VALUES (26, 'Post-punk', 1, '2016-02-29 16:45:08', '2016-02-29 16:45:08');
INSERT INTO `tags` VALUES (27, 'Minimal Wave', 1, '2016-02-29 16:45:08', '2016-02-29 16:45:08');
INSERT INTO `tags` VALUES (28, 'House', 1, '2016-02-29 21:14:28', '2016-02-29 21:14:28');
INSERT INTO `tags` VALUES (29, 'Disco', 1, '2016-02-29 21:14:28', '2016-02-29 21:14:28');
INSERT INTO `tags` VALUES (30, 'Funk', 1, '2016-02-29 21:20:03', '2016-02-29 21:20:03');
INSERT INTO `tags` VALUES (31, 'Food', 1, '2016-02-29 21:20:03', '2016-02-29 21:20:03');
INSERT INTO `tags` VALUES (32, 'Idm', 1, '2016-02-29 22:55:41', '2016-02-29 22:55:41');
INSERT INTO `tags` VALUES (33, 'Live Sets', 1, '2016-03-01 17:16:57', '2016-03-01 17:16:57');
INSERT INTO `tags` VALUES (34, 'Cmu', 1, '2016-03-01 20:09:45', '2016-03-01 20:09:45');
INSERT INTO `tags` VALUES (35, 'Hip Hop', 1, '2016-03-01 20:16:28', '2016-03-01 20:16:28');
INSERT INTO `tags` VALUES (36, 'Halloween', 1, '2016-03-01 22:29:27', '2016-03-01 22:29:27');
INSERT INTO `tags` VALUES (37, 'Balkan', 1, '2016-03-02 06:47:18', '2016-03-02 06:47:18');
INSERT INTO `tags` VALUES (38, 'Bhangra', 1, '2016-03-02 06:47:19', '2016-03-02 06:47:19');
INSERT INTO `tags` VALUES (39, 'World Music', 1, '2016-03-02 06:47:19', '2016-03-02 06:47:19');
INSERT INTO `tags` VALUES (40, 'Breakcore', 1, '2016-03-02 06:58:32', '2016-03-02 06:58:32');
INSERT INTO `tags` VALUES (41, 'Edm', 1, '2016-03-03 20:47:14', '2016-03-03 20:47:14');
INSERT INTO `tags` VALUES (42, 'Punk', 1, '2016-03-06 00:42:40', '2016-03-06 00:42:40');
INSERT INTO `tags` VALUES (43, 'Weed', 1, '2016-03-06 00:43:09', '2016-03-06 00:43:09');
INSERT INTO `tags` VALUES (44, 'French', 1, '2016-03-06 00:46:24', '2016-03-06 00:46:24');
INSERT INTO `tags` VALUES (45, 'Hardstyle', 1, '2016-03-06 00:49:34', '2016-03-06 00:49:34');
INSERT INTO `tags` VALUES (46, 'Jumpstyle', 1, '2016-03-06 00:50:52', '2016-03-06 00:50:52');
INSERT INTO `tags` VALUES (204, 'Dais Records', 1, '2018-03-30 15:25:00', '2018-03-30 15:25:00');
INSERT INTO `tags` VALUES (48, 'Diy', 1, '2016-03-06 01:13:22', '2016-03-06 01:13:22');
INSERT INTO `tags` VALUES (130, 'Activism', 1, '2017-01-31 20:43:00', '2017-01-31 20:43:00');
INSERT INTO `tags` VALUES (51, 'Fetish', 1, '2016-03-06 01:21:20', '2016-03-06 01:21:20');
INSERT INTO `tags` VALUES (52, 'Witch House', 1, '2016-03-07 20:51:51', '2016-03-07 20:51:51');
INSERT INTO `tags` VALUES (53, 'Museum', 1, '2016-03-07 20:55:03', '2016-03-07 20:55:03');
INSERT INTO `tags` VALUES (54, 'Art', 1, '2016-03-07 20:55:58', '2016-03-07 20:55:58');
INSERT INTO `tags` VALUES (55, 'Pop', 1, '2016-03-10 19:01:51', '2016-03-10 19:01:51');
INSERT INTO `tags` VALUES (56, 'Anything Goes', 1, '2016-03-10 19:01:51', '2016-03-10 19:01:51');
INSERT INTO `tags` VALUES (57, 'Goth', 1, '2016-03-10 20:09:16', '2016-03-10 20:09:16');
INSERT INTO `tags` VALUES (58, 'Synthwave', 1, '2016-03-10 20:09:16', '2016-03-10 20:09:16');
INSERT INTO `tags` VALUES (59, 'Ebm', 1, '2016-03-14 22:12:41', '2016-03-14 22:12:41');
INSERT INTO `tags` VALUES (60, 'Synth-pop', 1, '2016-03-14 22:12:41', '2016-03-14 22:12:41');
INSERT INTO `tags` VALUES (61, 'Cumbia', 1, '2016-03-14 22:33:06', '2016-03-14 22:33:06');
INSERT INTO `tags` VALUES (62, 'Soul', 1, '2016-03-17 00:43:00', '2016-03-17 00:43:00');
INSERT INTO `tags` VALUES (63, 'Dancehall', 1, '2016-03-17 00:51:00', '2016-03-17 00:51:00');
INSERT INTO `tags` VALUES (64, 'Reggae', 1, '2016-03-17 00:51:00', '2016-03-17 00:51:00');
INSERT INTO `tags` VALUES (65, 'Breaks', 1, '2016-03-17 23:18:00', '2016-03-17 23:18:00');
INSERT INTO `tags` VALUES (66, 'Turntablism', 1, '2016-03-17 23:18:00', '2016-03-17 23:18:00');
INSERT INTO `tags` VALUES (67, 'Film', 1, '2016-03-18 15:45:00', '2016-03-18 15:45:00');
INSERT INTO `tags` VALUES (131, 'Speaker', 1, '2017-02-02 18:02:00', '2017-02-02 18:02:00');
INSERT INTO `tags` VALUES (69, 'R&b', 1, '2016-03-21 21:37:00', '2016-03-21 21:37:00');
INSERT INTO `tags` VALUES (70, 'Live Visuals', 1, '2016-03-21 22:45:00', '2016-03-21 22:45:00');
INSERT INTO `tags` VALUES (71, 'Moombahton', 1, '2016-03-22 19:43:00', '2016-03-22 19:43:00');
INSERT INTO `tags` VALUES (72, 'Bounce', 1, '2016-03-22 19:43:00', '2016-03-22 19:43:00');
INSERT INTO `tags` VALUES (73, 'Downtempo', 1, '2016-03-22 21:10:00', '2016-03-22 21:10:00');
INSERT INTO `tags` VALUES (74, 'Post-rock', 1, '2016-03-22 21:18:00', '2016-03-22 21:18:00');
INSERT INTO `tags` VALUES (75, 'Dub', 1, '2016-03-22 21:18:00', '2016-03-22 21:18:00');
INSERT INTO `tags` VALUES (76, 'Avant', 1, '2016-03-22 21:39:00', '2016-03-22 21:39:00');
INSERT INTO `tags` VALUES (77, 'Mashup', 1, '2016-03-23 01:44:00', '2016-03-23 01:44:00');
INSERT INTO `tags` VALUES (78, 'Electro', 1, '2016-03-24 18:07:00', '2016-03-24 18:07:00');
INSERT INTO `tags` VALUES (79, 'Indie Dance', 1, '2016-03-24 21:38:00', '2016-03-24 21:38:00');
INSERT INTO `tags` VALUES (80, 'Britpop', 1, '2016-03-24 21:44:00', '2016-03-24 21:44:00');
INSERT INTO `tags` VALUES (81, 'Games', 1, '2016-03-28 18:34:00', '2016-03-28 18:34:00');
INSERT INTO `tags` VALUES (82, 'Horror', 1, '2016-03-28 18:34:00', '2016-03-28 18:34:00');
INSERT INTO `tags` VALUES (83, 'Wpts', 1, '2016-03-28 20:26:00', '2016-03-28 20:26:00');
INSERT INTO `tags` VALUES (84, 'College Radio', 1, '2016-03-28 20:26:00', '2016-03-28 20:26:00');
INSERT INTO `tags` VALUES (85, 'Indie', 1, '2016-03-28 22:17:00', '2016-03-28 22:17:00');
INSERT INTO `tags` VALUES (86, 'Rhythmic Noise', 1, '2016-03-30 16:46:00', '2016-03-30 16:46:00');
INSERT INTO `tags` VALUES (87, 'Happy Hardcore', 1, '2016-03-31 18:20:00', '2016-03-31 18:20:00');
INSERT INTO `tags` VALUES (88, 'Trance', 1, '2016-03-31 20:00:00', '2016-03-31 20:00:00');
INSERT INTO `tags` VALUES (89, 'Psytrance', 1, '2016-03-31 20:36:00', '2016-03-31 20:36:00');
INSERT INTO `tags` VALUES (90, 'Renegade', 1, '2016-04-04 07:16:00', '2016-04-04 07:16:00');
INSERT INTO `tags` VALUES (91, 'Ragga-jungle', 1, '2016-04-04 07:26:00', '2016-04-04 07:26:00');
INSERT INTO `tags` VALUES (92, 'Record Store', 1, '2016-04-04 16:20:00', '2016-04-04 16:20:00');
INSERT INTO `tags` VALUES (93, 'Free-jazz', 1, '2016-04-04 17:19:00', '2016-04-04 17:19:00');
INSERT INTO `tags` VALUES (94, 'Improv', 1, '2016-04-04 17:19:00', '2016-04-04 17:19:00');
INSERT INTO `tags` VALUES (95, 'Jazz', 1, '2016-04-04 21:36:00', '2016-04-04 21:36:00');
INSERT INTO `tags` VALUES (96, 'Laptop Battle', 1, '2016-04-06 00:31:00', '2016-04-06 00:31:00');
INSERT INTO `tags` VALUES (97, 'Speedcore', 1, '2016-04-06 00:31:00', '2016-04-06 00:31:00');
INSERT INTO `tags` VALUES (98, 'Benefit', 1, '2016-04-06 02:02:00', '2016-04-06 02:02:00');
INSERT INTO `tags` VALUES (99, 'Drag', 1, '2016-04-06 02:02:00', '2016-04-06 02:02:00');
INSERT INTO `tags` VALUES (100, 'Acid', 1, '2016-04-06 16:32:00', '2016-04-06 16:32:00');
INSERT INTO `tags` VALUES (101, 'Outdoors', 1, '2016-05-02 19:08:00', '2016-05-02 19:08:00');
INSERT INTO `tags` VALUES (102, 'Internet Radio', 1, '2016-05-02 19:19:00', '2016-05-02 19:19:00');
INSERT INTO `tags` VALUES (103, 'Vinyl', 1, '2016-05-02 19:57:00', '2016-05-02 19:57:00');
INSERT INTO `tags` VALUES (104, 'No Wave', 1, '2016-05-09 14:49:00', '2016-05-09 14:49:00');
INSERT INTO `tags` VALUES (105, 'Boogie', 1, '2016-05-16 19:10:00', '2016-05-16 19:10:00');
INSERT INTO `tags` VALUES (106, 'Performance Art', 1, '2016-05-16 19:30:00', '2016-05-16 19:30:00');
INSERT INTO `tags` VALUES (107, 'Italo Disco', 1, '2016-05-18 16:34:00', '2016-05-18 16:34:00');
INSERT INTO `tags` VALUES (108, 'Gabber', 1, '2016-06-01 20:42:00', '2016-06-01 20:42:00');
INSERT INTO `tags` VALUES (109, 'Rollerskating', 1, '2016-06-01 22:30:00', '2016-06-01 22:30:00');
INSERT INTO `tags` VALUES (110, 'Dance Party', 1, '2016-06-07 21:10:00', '2016-06-07 21:10:00');
INSERT INTO `tags` VALUES (111, 'Chiptune', 1, '2016-06-09 17:16:00', '2016-06-09 17:16:00');
INSERT INTO `tags` VALUES (112, 'Glitch-hop', 1, '2016-06-09 21:14:00', '2016-06-09 21:14:00');
INSERT INTO `tags` VALUES (113, 'Braindance', 1, '2016-06-13 15:45:00', '2016-06-13 15:45:00');
INSERT INTO `tags` VALUES (114, 'Uk Hardcore', 1, '2016-06-14 16:38:00', '2016-06-14 16:38:00');
INSERT INTO `tags` VALUES (115, 'Nye', 1, '2016-06-17 20:23:00', '2016-06-17 20:23:00');
INSERT INTO `tags` VALUES (116, 'Twerk', 1, '2016-06-20 16:53:00', '2016-06-20 16:53:00');
INSERT INTO `tags` VALUES (117, 'Special Event', 1, '2016-06-21 22:45:00', '2016-06-21 22:45:00');
INSERT INTO `tags` VALUES (118, 'Chillwave', 1, '2016-06-27 16:33:00', '2016-06-27 16:33:00');
INSERT INTO `tags` VALUES (119, 'Ska', 1, '2016-07-06 04:31:00', '2016-07-06 04:31:00');
INSERT INTO `tags` VALUES (120, 'Folk', 1, '2016-07-12 16:55:00', '2016-07-12 16:55:00');
INSERT INTO `tags` VALUES (121, 'Country', 1, '2016-07-12 16:55:00', '2016-07-12 16:55:00');
INSERT INTO `tags` VALUES (122, 'Eighties', 1, '2016-07-12 20:06:00', '2016-07-12 20:06:00');
INSERT INTO `tags` VALUES (123, 'Workshop', 1, '2016-09-07 15:22:00', '2016-09-07 15:22:00');
INSERT INTO `tags` VALUES (124, 'Black Metal', 1, '2016-09-12 16:43:00', '2016-09-12 16:43:00');
INSERT INTO `tags` VALUES (125, 'Code', 1, '2016-09-12 22:49:00', '2016-09-12 22:49:00');
INSERT INTO `tags` VALUES (126, 'Vr', 1, '2016-09-12 22:49:00', '2016-09-12 22:49:00');
INSERT INTO `tags` VALUES (127, 'Triphop', 1, '2016-12-14 19:48:00', '2016-12-14 19:48:00');
INSERT INTO `tags` VALUES (128, 'Restaurant', 1, '2016-12-16 17:00:00', '2016-12-16 17:00:00');
INSERT INTO `tags` VALUES (129, 'Lecture', 1, '2017-01-23 23:36:00', '2017-01-23 23:36:00');
INSERT INTO `tags` VALUES (132, 'Death Metal', 1, '2017-02-02 20:02:00', '2017-02-02 20:02:00');
INSERT INTO `tags` VALUES (133, 'Ethereal', 1, '2017-02-06 23:02:00', '2017-02-06 23:02:00');
INSERT INTO `tags` VALUES (134, 'Queer', 1, '2017-02-07 19:06:00', '2017-02-07 19:06:00');
INSERT INTO `tags` VALUES (135, 'Neofolk', 1, '2017-02-08 02:09:00', '2017-02-08 02:09:00');
INSERT INTO `tags` VALUES (136, 'Classical', 1, '2017-02-13 21:38:00', '2017-02-13 21:38:00');
INSERT INTO `tags` VALUES (137, 'Darkwave', 1, '2017-02-14 00:11:00', '2017-02-14 00:11:00');
INSERT INTO `tags` VALUES (138, 'Silent Disco', 1, '2017-02-15 13:23:00', '2017-02-15 13:23:00');
INSERT INTO `tags` VALUES (163, 'Livecoding', 1, '2017-07-25 14:21:00', '2017-07-25 14:21:00');
INSERT INTO `tags` VALUES (140, 'Brass Bands', 1, '2017-02-15 14:37:00', '2017-02-15 14:37:00');
INSERT INTO `tags` VALUES (141, 'Animals', 1, '2017-02-20 12:49:00', '2017-02-20 12:49:00');
INSERT INTO `tags` VALUES (143, 'Psychedelic', 1, '2017-02-23 12:43:00', '2017-02-23 12:43:00');
INSERT INTO `tags` VALUES (164, 'Library', 1, '2017-08-05 10:52:00', '2017-08-05 10:52:00');
INSERT INTO `tags` VALUES (145, 'Glitch', 1, '2017-02-23 12:43:00', '2017-02-23 12:43:00');
INSERT INTO `tags` VALUES (146, 'Wink', 1, '2017-02-23 12:43:00', '2017-02-23 12:43:00');
INSERT INTO `tags` VALUES (147, 'Cryoverb', 1, '2017-02-23 12:43:00', '2017-02-23 12:43:00');
INSERT INTO `tags` VALUES (148, 'Burn', 1, '2017-02-23 12:43:00', '2017-02-23 12:43:00');
INSERT INTO `tags` VALUES (149, 'Church', 1, '2017-03-01 14:15:00', '2017-03-01 14:15:00');
INSERT INTO `tags` VALUES (150, 'Bar', 1, '2017-03-30 13:35:00', '2017-03-30 13:35:00');
INSERT INTO `tags` VALUES (151, 'Puppets', 1, '2017-04-17 17:19:00', '2017-04-17 17:19:00');
INSERT INTO `tags` VALUES (152, 'Market', 1, '2017-04-17 17:23:00', '2017-04-17 17:23:00');
INSERT INTO `tags` VALUES (165, 'Class', 1, '2017-08-09 10:19:00', '2017-08-09 10:19:00');
INSERT INTO `tags` VALUES (161, 'Garden', 1, '2017-07-17 17:06:00', '2017-07-17 17:06:00');
INSERT INTO `tags` VALUES (155, 'Park', 1, '2017-05-04 12:23:00', '2017-05-04 12:23:00');
INSERT INTO `tags` VALUES (156, 'Afrobeat', 1, '2017-05-11 14:53:00', '2017-05-11 14:53:00');
INSERT INTO `tags` VALUES (157, 'Accoustic', 1, '2017-05-23 13:55:00', '2017-05-23 13:55:00');
INSERT INTO `tags` VALUES (158, 'Ritual', 1, '2017-06-03 12:51:00', '2017-06-03 12:51:00');
INSERT INTO `tags` VALUES (159, 'Open Mic', 1, '2017-06-18 21:08:00', '2017-06-18 21:08:00');
INSERT INTO `tags` VALUES (160, 'Family-friendly', 1, '2017-06-23 12:59:00', '2017-06-23 12:59:00');
INSERT INTO `tags` VALUES (166, 'Video', 1, '2017-08-14 15:26:00', '2017-08-14 15:26:00');
INSERT INTO `tags` VALUES (167, 'Rnb', 1, '2017-08-18 17:21:00', '2017-08-18 17:21:00');
INSERT INTO `tags` VALUES (202, 'Comedy', 1, '2018-03-29 11:44:00', '2018-03-29 11:44:00');
INSERT INTO `tags` VALUES (169, 'New Wave', 1, '2017-08-22 16:12:00', '2017-08-22 16:12:00');
INSERT INTO `tags` VALUES (170, 'Development', 1, '2017-09-27 12:44:00', '2017-09-27 12:44:00');
INSERT INTO `tags` VALUES (171, 'Surf', 1, '2017-10-16 16:25:00', '2017-10-16 16:25:00');
INSERT INTO `tags` VALUES (172, 'Computers', 1, '2017-11-03 14:09:00', '2017-11-03 14:09:00');
INSERT INTO `tags` VALUES (173, 'Grindcore', 1, '2017-11-21 02:38:00', '2017-11-21 02:38:00');
INSERT INTO `tags` VALUES (174, 'Crafts', 1, '2017-11-29 10:43:00', '2017-11-29 10:43:00');
INSERT INTO `tags` VALUES (211, 'Poetry', 1, '2018-08-07 11:29:09', '2018-08-07 11:29:09');
INSERT INTO `tags` VALUES (176, 'Azonto', 1, '2017-12-11 16:13:00', '2017-12-11 16:13:00');
INSERT INTO `tags` VALUES (177, 'Kudoro', 1, '2017-12-11 16:13:00', '2017-12-11 16:13:00');
INSERT INTO `tags` VALUES (178, 'Soca', 1, '2017-12-11 16:13:00', '2017-12-11 16:13:00');
INSERT INTO `tags` VALUES (179, 'Brazilian', 1, '2017-12-11 16:40:00', '2017-12-11 16:40:00');
INSERT INTO `tags` VALUES (180, 'Baile Funk', 1, '2017-12-11 16:40:00', '2017-12-11 16:40:00');
INSERT INTO `tags` VALUES (181, 'Minimal', 1, '2017-12-12 18:18:00', '2017-12-12 18:18:00');
INSERT INTO `tags` VALUES (182, 'Groovy', 1, '2017-12-12 18:18:00', '2017-12-12 18:18:00');
INSERT INTO `tags` VALUES (183, 'Soulful', 1, '2017-12-12 18:18:00', '2017-12-12 18:18:00');
INSERT INTO `tags` VALUES (184, 'Comics', 1, '2017-12-14 21:10:00', '2017-12-14 21:10:00');
INSERT INTO `tags` VALUES (185, 'Beer', 1, '2017-12-18 16:00:00', '2017-12-18 16:00:00');
INSERT INTO `tags` VALUES (186, 'Emo', 1, '2017-12-21 01:56:00', '2017-12-21 01:56:00');
INSERT INTO `tags` VALUES (187, 'Karaoke', 1, '2017-12-21 01:56:00', '2017-12-21 01:56:00');
INSERT INTO `tags` VALUES (188, 'Storytelling', 1, '2018-01-08 11:48:00', '2018-01-08 11:48:00');
INSERT INTO `tags` VALUES (189, 'Deep House', 1, '2018-01-13 01:33:00', '2018-01-13 01:33:00');
INSERT INTO `tags` VALUES (190, 'Doom Metal', 1, '2018-01-26 12:13:00', '2018-01-26 12:13:00');
INSERT INTO `tags` VALUES (191, 'Stoner Rock', 1, '2018-01-26 12:13:00', '2018-01-26 12:13:00');
INSERT INTO `tags` VALUES (214, 'Cover Bands', 1, '2018-10-08 17:25:53', '2018-10-08 17:25:53');
INSERT INTO `tags` VALUES (193, 'Drone', 1, '2018-01-26 12:44:00', '2018-01-26 12:44:00');
INSERT INTO `tags` VALUES (201, 'Rap', 1, '2018-03-26 17:53:00', '2018-03-26 17:53:00');
INSERT INTO `tags` VALUES (195, 'Guitar', 1, '2018-02-12 12:17:00', '2018-02-12 12:17:00');
INSERT INTO `tags` VALUES (203, 'Thrash', 1, '2018-03-30 02:34:00', '2018-03-30 02:34:00');
INSERT INTO `tags` VALUES (197, 'Garage', 1, '2018-02-26 14:09:00', '2018-02-26 14:09:00');
INSERT INTO `tags` VALUES (198, 'Trash', 1, '2018-02-26 14:09:00', '2018-02-26 14:09:00');
INSERT INTO `tags` VALUES (199, 'Glam', 1, '2018-02-26 14:09:00', '2018-02-26 14:09:00');
INSERT INTO `tags` VALUES (200, 'Shoegaze', 1, '2018-02-27 12:56:00', '2018-02-27 12:56:00');
INSERT INTO `tags` VALUES (206, 'Twee', 1, '2018-03-30 15:51:00', '2018-03-30 15:51:00');
INSERT INTO `tags` VALUES (207, 'Indiepop', 1, '2018-03-30 15:51:00', '2018-03-30 15:51:00');
INSERT INTO `tags` VALUES (208, 'Diy', 1, '2018-03-30 15:51:00', '2018-03-30 15:51:00');
INSERT INTO `tags` VALUES (209, 'Bluegrass', 1, '2018-04-03 11:10:00', '2018-04-03 11:10:00');
INSERT INTO `tags` VALUES (213, 'Treasure', 1, '2018-08-07 13:35:59', '2018-08-07 13:35:59');
INSERT INTO `tags` VALUES (215, 'Wave', 1, '2018-10-15 21:52:34', '2018-10-15 21:52:34');
INSERT INTO `tags` VALUES (216, 'Krautrock', 1, '2018-11-16 17:52:55', '2018-11-16 17:52:55');
INSERT INTO `tags` VALUES (217, 'Prog', 1, '2018-11-16 18:09:40', '2018-11-16 18:09:40');
INSERT INTO `tags` VALUES (218, 'Theater', 1, '2018-11-27 11:05:53', '2018-11-27 11:05:53');
INSERT INTO `tags` VALUES (219, 'Musical', 1, '2018-11-27 11:05:53', '2018-11-27 11:05:53');
INSERT INTO `tags` VALUES (220, 'Italy', 1, '2018-11-27 11:05:53', '2018-11-27 11:05:53');
INSERT INTO `tags` VALUES (221, 'Glitterbox', 1, '2018-11-27 11:05:53', '2018-11-27 11:05:53');
INSERT INTO `tags` VALUES (222, 'Synthpop', 1, '2018-12-22 23:07:52', '2018-12-22 23:07:52');
INSERT INTO `tags` VALUES (223, 'Digital Hardcore', 1, '2019-02-01 16:01:53', '2019-02-01 16:01:53');
INSERT INTO `tags` VALUES (224, 'Music', 1, '2019-04-12 15:36:21', '2019-04-12 15:36:21');
INSERT INTO `tags` VALUES (225, 'Talk', 1, '2019-05-06 10:20:49', '2019-05-06 10:20:49');
INSERT INTO `tags` VALUES (226, 'Demo', 1, '2019-05-06 10:20:49', '2019-05-06 10:20:49');
INSERT INTO `tags` VALUES (227, 'Piano', 1, '2019-08-24 13:40:36', '2019-08-24 13:40:36');
INSERT INTO `tags` VALUES (228, 'Noise Rock', 1, '2019-10-14 10:14:11', '2019-10-14 10:14:11');
INSERT INTO `tags` VALUES (229, 'Dj', 1, '2019-10-21 21:40:19', '2019-10-21 21:40:19');
INSERT INTO `tags` VALUES (230, 'Vintage', 1, '2019-10-30 11:33:47', '2019-10-30 11:33:47');
INSERT INTO `tags` VALUES (231, 'Shop', 1, '2019-10-30 11:33:47', '2019-10-30 11:33:47');
INSERT INTO `tags` VALUES (232, 'Demoscene', 1, '2019-11-04 00:30:48', '2019-11-04 00:30:48');
INSERT INTO `tags` VALUES (233, 'Coldwave', 1, '2019-11-04 13:42:50', '2019-11-04 13:42:50');
INSERT INTO `tags` VALUES (234, 'Christmas', 1, '2019-11-08 14:08:25', '2019-11-08 14:08:25');
INSERT INTO `tags` VALUES (235, 'Spice', 1, '2019-11-11 13:13:36', '2019-11-11 13:13:36');

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
