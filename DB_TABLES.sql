
-- ----------------------------
-- Table structure for wpos_account
-- ----------------------------
DROP TABLE IF EXISTS `wpos_account`;
CREATE TABLE `wpos_account` (
  `id_account` varchar(32) NOT NULL DEFAULT '',
  `facebook_id` varchar(32) NOT NULL DEFAULT '',
  `facebook_user_access_token` varchar(255) NOT NULL DEFAULT '',
  `name` varchar(255) NOT NULL DEFAULT '',
  `email` varchar(255) NOT NULL DEFAULT '',
  `alternate_email` varchar(255) NOT NULL DEFAULT '',
  `alternate_password` varchar(255) NOT NULL DEFAULT '',
  `timezone` varchar(32) NOT NULL DEFAULT '',
  `tipping_provider` varchar(16) NOT NULL DEFAULT '',
  `wallet_address` varchar(64) NOT NULL DEFAULT '',
  `receive_notifications` enum('','true','false') NOT NULL DEFAULT 'true',
  `date_created` datetime NOT NULL,
  `last_update` datetime NOT NULL,
  `last_activity` datetime NOT NULL,
  PRIMARY KEY (`id_account`),
  KEY `facebook_id` (`facebook_id`),
  KEY `wallet_address` (`wallet_address`),
  KEY `alternate_login` (`alternate_email`,`alternate_password`),
  KEY `email` (`email`),
  KEY `alternate_email` (`alternate_email`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- ----------------------------
-- Table structure for wpos_account_extensions
-- ----------------------------
DROP TABLE IF EXISTS `wpos_account_extensions`;
CREATE TABLE `wpos_account_extensions` (
  `id_account` varchar(32) NOT NULL DEFAULT '',
  `created_from` varchar(255) NOT NULL DEFAULT '',
  `referer_website_key` varchar(32) NOT NULL DEFAULT '',
  `referer_button_website_key` varchar(32) NOT NULL DEFAULT '',
  `referer_button_id` varchar(32) NOT NULL DEFAULT '',
  `referer_button_referral_code` varchar(255) NOT NULL DEFAULT '',
  `account_class` enum('standard','vip','premium') NOT NULL DEFAULT 'standard',
  `reroute_to` varchar(32) NOT NULL DEFAULT '',
  `public_profile_data` mediumtext NOT NULL,
  PRIMARY KEY (`id_account`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- ----------------------------
-- Table structure for wpos_account_logins
-- ----------------------------
DROP TABLE IF EXISTS `wpos_account_logins`;
CREATE TABLE `wpos_account_logins` (
  `id_account` varchar(32) NOT NULL DEFAULT '',
  `login_date` datetime NOT NULL,
  `user_agent` varchar(255) NOT NULL DEFAULT '',
  `ip` varchar(20) NOT NULL DEFAULT '',
  `hostname` varchar(255) NOT NULL DEFAULT '',
  `country` varchar(100) NOT NULL DEFAULT '',
  `region` varchar(100) NOT NULL DEFAULT '',
  `city` varchar(100) NOT NULL DEFAULT '',
  `isp` varchar(255) NOT NULL DEFAULT ''
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- ----------------------------
-- Table structure for wpos_account_wallets
-- ----------------------------
DROP TABLE IF EXISTS `wpos_account_wallets`;
CREATE TABLE `wpos_account_wallets` (
  `id_account` varchar(32) NOT NULL DEFAULT '',
  `coin_name` varchar(64) NOT NULL DEFAULT '',
  `address` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id_account`,`coin_name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- ----------------------------
-- Table structure for wpos_coin_prices
-- ----------------------------
DROP TABLE IF EXISTS `wpos_coin_prices`;
CREATE TABLE `wpos_coin_prices` (
  `coin_name` varchar(64) NOT NULL DEFAULT '',
  `date` datetime NOT NULL,
  `price` double NOT NULL DEFAULT '0',
  `source` varchar(64) NOT NULL DEFAULT '',
  PRIMARY KEY (`coin_name`,`date`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- ----------------------------
-- Table structure for wpos_flags
-- ----------------------------
DROP TABLE IF EXISTS `wpos_flags`;
CREATE TABLE `wpos_flags` (
  `flag` varchar(255) NOT NULL DEFAULT '',
  `value` longtext NOT NULL,
  PRIMARY KEY (`flag`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- ----------------------------
-- Table structure for wpos_instagram_items
-- ----------------------------
DROP TABLE IF EXISTS `wpos_instagram_items`;
CREATE TABLE `wpos_instagram_items` (
  `item_id` varchar(255) NOT NULL DEFAULT '',
  `author_id` bigint(20) unsigned NOT NULL DEFAULT '0',
  `author_username` varchar(255) NOT NULL DEFAULT '',
  `monitor_start` datetime NOT NULL,
  `last_checked` datetime NOT NULL,
  `link` varchar(255) NOT NULL DEFAULT '',
  `thumbnail` varchar(255) NOT NULL DEFAULT '',
  `comment_count` int(10) unsigned NOT NULL DEFAULT '0',
  `last_comment_id` bigint(20) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`item_id`),
  KEY `by_author` (`author_username`,`item_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- ----------------------------
-- Table structure for wpos_instagram_users
-- ----------------------------
DROP TABLE IF EXISTS `wpos_instagram_users`;
CREATE TABLE `wpos_instagram_users` (
  `user_id` bigint(20) unsigned NOT NULL DEFAULT '0',
  `id_account` varchar(32) NOT NULL DEFAULT '',
  `user_name` varchar(32) NOT NULL DEFAULT '',
  `full_name` varchar(255) NOT NULL DEFAULT '',
  `access_token` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`user_name`),
  KEY `by_account` (`user_id`,`id_account`),
  KEY `by_user_name1` (`user_name`,`user_id`),
  KEY `by_user_name2` (`user_name`,`id_account`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- ----------------------------
-- Table structure for wpos_log
-- ----------------------------
DROP TABLE IF EXISTS `wpos_log`;
CREATE TABLE `wpos_log` (
  `op_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `entry_type` varchar(16) NOT NULL DEFAULT '',
  `from_handler` varchar(255) NOT NULL DEFAULT '',
  `entry_id` varchar(64) NOT NULL DEFAULT '',
  `action_type` varchar(16) NOT NULL DEFAULT '',
  `from_facebook_id` bigint(20) unsigned NOT NULL DEFAULT '0',
  `from_id_account` varchar(32) NOT NULL DEFAULT '',
  `message` varchar(255) NOT NULL DEFAULT '',
  `coin_name` varchar(64) NOT NULL DEFAULT '',
  `coins` double NOT NULL DEFAULT '0',
  `to_facebook_id` bigint(20) unsigned NOT NULL DEFAULT '0',
  `to_id_account` varchar(32) NOT NULL DEFAULT '',
  `state` varchar(16) NOT NULL DEFAULT '',
  `info` varchar(255) NOT NULL DEFAULT '',
  `date_analyzed` datetime NOT NULL,
  `api_call_message` varchar(255) NOT NULL DEFAULT '',
  `api_extended_info` text NOT NULL,
  `date_processed` datetime NOT NULL,
  PRIMARY KEY (`op_id`),
  KEY `by_account` (`from_id_account`),
  KEY `main` (`entry_id`,`from_id_account`,`to_id_account`,`coin_name`,`coins`),
  KEY `by_fb` (`entry_id`,`from_facebook_id`,`to_facebook_id`,`coin_name`,`coins`),
  KEY `table_data` (`from_id_account`,`entry_type`,`from_handler`,`entry_id`,`state`)
) ENGINE=InnoDB AUTO_INCREMENT=12075 DEFAULT CHARSET=latin1;

-- ----------------------------
-- Table structure for wpos_twitter
-- ----------------------------
DROP TABLE IF EXISTS `wpos_twitter`;
CREATE TABLE `wpos_twitter` (
  `twitter_id` bigint(20) unsigned NOT NULL DEFAULT '0',
  `id_account` varchar(32) NOT NULL DEFAULT '',
  `screen_name` varchar(32) NOT NULL DEFAULT '',
  `access_token` varchar(255) NOT NULL DEFAULT '',
  `token_secret` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`twitter_id`),
  KEY `by_account` (`twitter_id`,`id_account`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- ----------------------------
-- Table structure for wpos_website_button_log
-- ----------------------------
DROP TABLE IF EXISTS `wpos_website_button_log`;
CREATE TABLE `wpos_website_button_log` (
  `button_id` varchar(32) NOT NULL DEFAULT '',
  `record_type` enum('view','click','conversion') NOT NULL DEFAULT 'view',
  `record_date` datetime NOT NULL,
  `entry_id` varchar(255) NOT NULL DEFAULT '',
  `host_website` varchar(32) NOT NULL DEFAULT '',
  `referral_code` varchar(255) NOT NULL DEFAULT '',
  `target_account` varchar(32) NOT NULL DEFAULT '',
  `target_name` varchar(255) NOT NULL DEFAULT '',
  `target_email` varchar(255) NOT NULL DEFAULT '',
  `client_ip` varchar(100) NOT NULL DEFAULT '',
  `user_agent` varchar(255) NOT NULL DEFAULT '',
  `country` varchar(255) NOT NULL DEFAULT '',
  `region` varchar(255) NOT NULL DEFAULT '',
  `city` varchar(255) NOT NULL DEFAULT '',
  `isp` varchar(255) NOT NULL DEFAULT '',
  KEY `main` (`button_id`,`record_type`,`entry_id`,`host_website`,`referral_code`,`target_email`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- ----------------------------
-- Table structure for wpos_website_buttons
-- ----------------------------
DROP TABLE IF EXISTS `wpos_website_buttons`;
CREATE TABLE `wpos_website_buttons` (
  `button_id` varchar(32) NOT NULL DEFAULT '',
  `button_name` varchar(255) NOT NULL DEFAULT '',
  `website_public_key` varchar(32) NOT NULL DEFAULT '',
  `type` varchar(32) NOT NULL DEFAULT '',
  `color_scheme` varchar(32) NOT NULL DEFAULT '',
  `properties` text NOT NULL,
  `creation_date` datetime NOT NULL,
  `state` enum('enabled','disabled','deleted') NOT NULL DEFAULT 'enabled',
  PRIMARY KEY (`button_id`),
  KEY `by_website` (`website_public_key`,`button_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- ----------------------------
-- Table structure for wpos_websites
-- ----------------------------
DROP TABLE IF EXISTS `wpos_websites`;
CREATE TABLE `wpos_websites` (
  `id_account` varchar(32) NOT NULL DEFAULT '',
  `public_key` varchar(32) NOT NULL DEFAULT '',
  `secret_key` varchar(32) NOT NULL DEFAULT '',
  `name` varchar(255) NOT NULL DEFAULT '',
  `category` varchar(64) NOT NULL DEFAULT '',
  `description` varchar(1000) NOT NULL DEFAULT '',
  `main_url` varchar(255) NOT NULL DEFAULT '',
  `icon_url` varchar(255) NOT NULL DEFAULT '',
  `valid_urls` text NOT NULL,
  `allow_leeching` tinyint(3) unsigned NOT NULL DEFAULT '1',
  `leech_button_type` varchar(255) NOT NULL DEFAULT '',
  `leech_color_scheme` varchar(255) NOT NULL DEFAULT '',
  `banned_websites` text NOT NULL,
  `creation_date` datetime NOT NULL,
  `state` enum('enabled','disabled','deleted','locked') NOT NULL DEFAULT 'enabled',
  `published` tinyint(3) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`public_key`),
  KEY `by_category` (`category`),
  KEY `by_account` (`id_account`,`public_key`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- ----------------------------
-- Table structure for wpos_pulse_comments
-- ----------------------------
DROP TABLE IF EXISTS `wpos_pulse_comments`;
CREATE TABLE `wpos_pulse_comments` (
  `created` datetime NOT NULL,
  `id` varchar(32) NOT NULL DEFAULT '',
  `parent_post` varchar(32) NOT NULL DEFAULT '',
  `id_author` varchar(32) NOT NULL DEFAULT '',
  `content` text NOT NULL,
  `picture` varchar(255) NOT NULL DEFAULT '',
  `hidden` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `metadata` text NOT NULL,
  PRIMARY KEY (`parent_post`,`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- ----------------------------
-- Table structure for wpos_pulse_posts
-- ----------------------------
DROP TABLE IF EXISTS `wpos_pulse_posts`;
CREATE TABLE `wpos_pulse_posts` (
  `created` datetime NOT NULL,
  `id` varchar(32) NOT NULL DEFAULT '',
  `type` enum('text','link','video','photo','rain') NOT NULL DEFAULT 'text',
  `target_coin` varchar(16) NOT NULL DEFAULT '',
  `target_feed` varchar(255) NOT NULL DEFAULT '',
  `id_author` varchar(32) NOT NULL DEFAULT '',
  `caption` varchar(255) NOT NULL DEFAULT '',
  `content` text NOT NULL,
  `picture` varchar(255) NOT NULL DEFAULT '',
  `link` varchar(255) NOT NULL DEFAULT '',
  `signature` varchar(255) NOT NULL DEFAULT '',
  `edited` datetime NOT NULL,
  `edited_by` varchar(32) NOT NULL DEFAULT '',
  `last_update` datetime NOT NULL,
  `admin_notes` text NOT NULL,
  `hidden` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `metadata` text NOT NULL,
  `views` int(10) unsigned NOT NULL DEFAULT '0',
  `clicks` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `by_author` (`id_author`,`id`),
  KEY `by_coin` (`target_coin`,`id`),
  KEY `by_feed` (`target_feed`,`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- ----------------------------
-- Table structure for wpos_pulse_user_preferences
-- ----------------------------
DROP TABLE IF EXISTS `wpos_pulse_user_preferences`;
CREATE TABLE `wpos_pulse_user_preferences` (
  `id_account` varchar(32) NOT NULL DEFAULT '',
  `key` varchar(255) NOT NULL DEFAULT '',
  `value` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id_account`,`key`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- ----------------------------
-- Table structure for wpos_tip_batch_submissions
-- ----------------------------
DROP TABLE IF EXISTS `wpos_tip_batch_submissions`;
CREATE TABLE `wpos_tip_batch_submissions` (
  `batch_id` varchar(23) NOT NULL DEFAULT '',
  `recipient_facebook_id` bigint(20) unsigned NOT NULL DEFAULT '0',
  `recipient_name` varchar(255) NOT NULL DEFAULT '',
  `coin_name` varchar(16) NOT NULL DEFAULT '',
  `coin_amount` double NOT NULL DEFAULT '0',
  `date_created` datetime NOT NULL,
  `date_processed` datetime NOT NULL,
  `state` enum('pending','sent','failed') NOT NULL DEFAULT 'pending',
  `api_message` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`batch_id`,`recipient_facebook_id`),
  KEY `by_date` (`date_created`,`date_processed`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- ----------------------------
-- Table structure for wpos_tip_batches
-- ----------------------------
DROP TABLE IF EXISTS `wpos_tip_batches`;
CREATE TABLE `wpos_tip_batches` (
  `batch_id` varchar(23) NOT NULL DEFAULT '',
  `batch_title` varchar(255) NOT NULL DEFAULT '',
  `target_group_id` bigint(20) unsigned NOT NULL DEFAULT '0',
  `creator_facebook_id` bigint(20) unsigned NOT NULL DEFAULT '0',
  `creator_name` varchar(255) NOT NULL DEFAULT '',
  `using_bot_account` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `recipient_amount` int(10) unsigned NOT NULL DEFAULT '0',
  `recipient_type` enum('any','tipper','non_tipper') NOT NULL DEFAULT 'any',
  `coin_name` varchar(16) NOT NULL DEFAULT '',
  `coins_per_recipient_min` double unsigned NOT NULL DEFAULT '0',
  `coins_per_recipient_max` double unsigned NOT NULL DEFAULT '0',
  `state` enum('forging','active','finished','cancelled') NOT NULL DEFAULT 'forging',
  `cancellation_message` varchar(255) NOT NULL DEFAULT '',
  `date_created` datetime NOT NULL,
  `date_started` datetime NOT NULL,
  `date_finished` datetime NOT NULL,
  PRIMARY KEY (`batch_id`),
  KEY `batches_per_creator` (`creator_facebook_id`,`state`),
  KEY `state` (`state`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
