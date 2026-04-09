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
-- Records of access_types
-- ----------------------------
INSERT INTO `access_types` VALUES (1, 'Admin', 'Admin', 'administrator', 100, '2020-12-01 07:49:36', NULL);
INSERT INTO `access_types` VALUES (2, 'Owner', 'Owner', 'owner', 10, '2020-12-01 07:50:10', '2020-12-01 07:50:49');
INSERT INTO `access_types` VALUES (3, 'Member', 'Member', 'member', 5, '2020-12-01 07:50:37', NULL);
INSERT INTO `access_types` VALUES (4, 'Follower', 'Follower', 'follower', 2, '2020-12-01 07:51:09', NULL);
INSERT INTO `access_types` VALUES (5, 'Blocked', 'Blocked', 'blocked', 0, '2020-12-01 07:51:28', NULL);

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
-- Records of content_types
-- ----------------------------
INSERT INTO `content_types` VALUES (1, 'Plain Text', NULL, NULL);
INSERT INTO `content_types` VALUES (2, 'HTML', NULL, NULL);

-- ----------------------------
-- Records of entity_statuses
-- ----------------------------
INSERT INTO `entity_statuses` VALUES (1, 'Draft', '2016-02-25 07:54:14', '2016-02-25 07:54:14');
INSERT INTO `entity_statuses` VALUES (2, 'Active', '2016-02-25 07:54:14', '2016-02-25 07:54:14');
INSERT INTO `entity_statuses` VALUES (3, 'Inactive', '2016-02-25 07:54:14', '2016-02-25 07:54:14');
INSERT INTO `entity_statuses` VALUES (4, 'Unlisted', '2024-05-22 21:04:32', '2024-05-22 21:04:32');

-- ----------------------------
-- Records of entity_types
-- ----------------------------
INSERT INTO `entity_types` VALUES (1, 'Space', 'space', 'Space for events', '2016-02-25 07:54:14', '2016-02-25 07:54:14');
INSERT INTO `entity_types` VALUES (2, 'Group', 'group', 'Collection of individuals', '2016-02-25 07:54:14', '2016-02-25 07:54:14');
INSERT INTO `entity_types` VALUES (3, 'Individual', 'individual', 'Single individual', '2016-02-25 07:54:14', '2016-02-25 07:54:14');
INSERT INTO `entity_types` VALUES (4, 'Interest', 'interest', 'Interest or topic', '2016-02-25 07:54:14', '2016-02-25 07:54:14');

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
-- Records of forums
-- ----------------------------
INSERT INTO `forums` VALUES (1, 'Forum', 'forum', 'General forum', 3, 0, 1, 1, 1, '2017-06-05 13:50:27', '2017-06-05 13:50:27');

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
-- Records of groups
-- ----------------------------
INSERT INTO `groups` VALUES (1, 'admin', 'Admin', 100, '2017-05-19 01:57:45', '2017-05-19 01:57:45', '');
INSERT INTO `groups` VALUES (2, 'super_admin', 'Super Admin', 999, '2017-06-20 12:53:25', '2017-06-20 12:53:25', 'Super admin');

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
-- Records of occurrence_types
-- ----------------------------
INSERT INTO `occurrence_types` VALUES (1, 'No Schedule', '2016-02-25 07:54:14', '2016-02-25 07:54:14');
INSERT INTO `occurrence_types` VALUES (2, 'Weekly', '2016-02-25 07:54:14', '2016-02-25 07:54:14');
INSERT INTO `occurrence_types` VALUES (3, 'Biweekly', '2016-02-25 07:54:14', '2016-02-25 07:54:14');
INSERT INTO `occurrence_types` VALUES (4, 'Monthly', '2016-02-25 07:54:14', '2016-02-25 07:54:14');
INSERT INTO `occurrence_types` VALUES (5, 'Bimonthly', '2016-02-25 07:54:14', '2016-02-25 07:54:14');
INSERT INTO `occurrence_types` VALUES (6, 'Yearly', '2016-02-25 07:54:14', '2016-02-25 07:54:14');

-- ----------------------------
-- Records of occurrence_weeks
-- ----------------------------
INSERT INTO `occurrence_weeks` VALUES (1, 'First', '2016-02-25 07:54:15', '2016-02-25 07:54:15');
INSERT INTO `occurrence_weeks` VALUES (2, 'Second', '2016-02-25 07:54:15', '2016-02-25 07:54:15');
INSERT INTO `occurrence_weeks` VALUES (3, 'Third', '2016-02-25 07:54:15', '2016-02-25 07:54:15');
INSERT INTO `occurrence_weeks` VALUES (4, 'Fourth', '2016-02-25 07:54:15', '2016-02-25 07:54:15');
INSERT INTO `occurrence_weeks` VALUES (5, 'Last', '2016-02-25 07:54:15', '2016-02-25 07:54:15');

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
-- Records of response_types
-- ----------------------------
INSERT INTO `response_types` VALUES (1, 'Attending', 'General attending response', '2016-03-16 14:59:30', '2020-12-08 09:34:05');
INSERT INTO `response_types` VALUES (2, 'Interested', 'Planning on attending', '2016-03-16 14:59:53', '2020-12-08 09:34:34');
INSERT INTO `response_types` VALUES (3, 'Interested Unable', 'Interested but unable to attend', '2016-03-16 15:00:09', '2020-12-08 09:34:40');
INSERT INTO `response_types` VALUES (4, 'Uninterested', 'Uninterested in attending', '2016-03-16 15:00:34', '2020-12-08 09:34:42');
INSERT INTO `response_types` VALUES (5, 'Confirmed', 'Confirmed attendance', '2020-12-08 09:32:28', '2020-12-08 09:34:56');
INSERT INTO `response_types` VALUES (6, 'Ignore', 'Ignore events', '2020-12-10 00:17:23', '2020-12-10 00:17:23');

-- ----------------------------
-- Records of review_types
-- ----------------------------
INSERT INTO `review_types` VALUES (1, 'Informational', '2016-11-29 00:00:00', '2016-11-29 00:00:00');
INSERT INTO `review_types` VALUES (2, 'Positive', '2016-11-29 00:00:00', '2016-11-29 00:00:00');
INSERT INTO `review_types` VALUES (3, 'Neutral', '2016-11-29 00:00:00', '2016-11-29 00:00:00');
INSERT INTO `review_types` VALUES (4, 'Negative', '2016-11-29 00:00:00', '2016-11-29 00:00:00');

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
-- Records of tag_types
-- ----------------------------
INSERT INTO `tag_types` VALUES (1, 'Genre', '2016-02-25 07:54:14', '2016-02-25 07:54:14');
INSERT INTO `tag_types` VALUES (2, 'Region', '2016-02-25 07:54:14', '2016-02-25 07:54:14');
INSERT INTO `tag_types` VALUES (3, 'Category', '2016-02-25 07:54:14', '2016-02-25 07:54:14');
INSERT INTO `tag_types` VALUES (4, 'Topics', '2016-02-25 07:54:14', '2016-02-25 07:54:14');
INSERT INTO `tag_types` VALUES (5, 'Reaction', '2016-02-25 07:54:14', '2016-02-25 07:54:14');

-- ----------------------------
-- Records of user_statuses
-- ----------------------------
INSERT INTO `user_statuses` VALUES (1, 'Pending', 0, '2016-02-25 07:54:14', '2016-02-25 07:54:14');
INSERT INTO `user_statuses` VALUES (2, 'Active', 1, '2016-02-25 07:54:14', '2016-02-25 07:54:14');
INSERT INTO `user_statuses` VALUES (3, 'Suspended', 0, '2016-02-25 07:54:14', '2016-02-25 07:54:14');
INSERT INTO `user_statuses` VALUES (4, 'Banned', 0, '2017-04-18 12:54:30', '2017-04-18 12:54:30');
INSERT INTO `user_statuses` VALUES (5, 'Deleted', 0, '2017-04-18 12:54:30', '2017-04-18 12:54:30');

-- ----------------------------
-- Records of visibilities
-- ----------------------------
INSERT INTO `visibilities` VALUES (1, 'Proposal', '2016-02-25 07:54:14', '2016-02-25 07:54:14');
INSERT INTO `visibilities` VALUES (2, 'Private', '2016-02-25 07:54:14', '2016-02-25 07:54:14');
INSERT INTO `visibilities` VALUES (3, 'Public', '2016-02-25 07:54:14', '2016-02-25 07:54:14');
INSERT INTO `visibilities` VALUES (4, 'Guarded', '2016-02-25 07:54:14', '2016-02-25 07:54:14');
INSERT INTO `visibilities` VALUES (5, 'Cancelled', '2016-02-25 07:54:14', '2016-02-25 07:54:14');

SET FOREIGN_KEY_CHECKS = 1;
