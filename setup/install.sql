CREATE TABLE `%prefix%noti_events` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `site_id` bigint(20) NOT NULL,
  `post_id` bigint(20) NOT NULL,
  `first_occurrence_at` timestamp NOT NULL,
  `last_occurrence_at` timestamp NULL DEFAULT NULL,
  `sealed_at` timestamp NULL DEFAULT NULL,
  `counter` int(11) NOT NULL DEFAULT '1',
  `hash` varchar(8) NOT NULL DEFAULT '',
  `status` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `aggregate` (`site_id`,`hash`,`post_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `%prefix%noti_eventmeta` (
  `meta_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `event_id` bigint(20) NOT NULL,
  `meta_key` varchar(25) NOT NULL DEFAULT '',
  `meta_value` longtext,
  PRIMARY KEY (`meta_id`),
  UNIQUE KEY `event_id` (`event_id`,`meta_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `%prefix%noti_subscribers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `site_id` bigint(20) NOT NULL,
  `post_id` bigint(20) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `is_subscribed` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `subscription` (`site_id`,`user_id`,`post_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
