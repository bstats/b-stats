CREATE TABLE IF NOT EXISTS `%BOARD%_post` (
  `doc_id` bigint(20) UNSIGNED NOT NULL,
  `no` bigint(20) UNSIGNED NOT NULL,
  `resto` bigint(20) UNSIGNED NOT NULL,
  `time` bigint(20) NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `trip` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sub` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `capcode` enum('none','mod','admin','admin_highlight','developer','founder') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'none',
  `country` varchar(4) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `com` varchar(10000) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tim` bigint(20) UNSIGNED DEFAULT NULL,
  `filename` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ext` varchar(5) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fsize` int(11) UNSIGNED DEFAULT NULL,
  `md5` binary(16) DEFAULT NULL,
  `w` int(11) UNSIGNED DEFAULT NULL,
  `h` int(11) UNSIGNED DEFAULT NULL,
  `filedeleted` tinyint(1) DEFAULT NULL,
  `spoiler` tinyint(1) DEFAULT NULL,
  `tag` varchar(6) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `deleted` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=Aria DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `%BOARD%_post`
  ADD PRIMARY KEY (`doc_id`),
  ADD UNIQUE KEY `no` (`no`),
  ADD KEY `resto` (`resto`);
  
ALTER TABLE `%BOARD%_post`
  MODIFY `doc_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;
  
CREATE TABLE IF NOT EXISTS `%BOARD%_deleted` (
  `doc_id` bigint(20) UNSIGNED NOT NULL,
  `no` bigint(20) UNSIGNED NOT NULL,
  `resto` bigint(20) UNSIGNED NOT NULL,
  `time` bigint(20) NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `trip` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sub` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `capcode` enum('none','mod','admin','admin_highlight','developer','founder') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'none',
  `country` varchar(4) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `com` varchar(10000) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tim` bigint(20) UNSIGNED DEFAULT NULL,
  `filename` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ext` varchar(5) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fsize` int(11) UNSIGNED DEFAULT NULL,
  `md5` binary(16) DEFAULT NULL,
  `w` int(11) UNSIGNED DEFAULT NULL,
  `h` int(11) UNSIGNED DEFAULT NULL,
  `filedeleted` tinyint(1) DEFAULT NULL,
  `spoiler` tinyint(1) DEFAULT NULL,
  `tag` varchar(6) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `deleted` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=Aria DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `%BOARD%_thread` (
  `threadid` bigint(20) UNSIGNED NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '0',
  `sticky` tinyint(1) NOT NULL DEFAULT '0',
  `closed` tinyint(1) NOT NULL DEFAULT '0',
  `archived` tinyint(1) NOT NULL DEFAULT '0',
  `custom_spoiler` int(10) UNSIGNED DEFAULT NULL,
  `replies` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `images` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `last_modified` bigint(20) DEFAULT NULL,
  `last_crawl` bigint(20) DEFAULT NULL
) ENGINE=Aria DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `%BOARD%_thread`
  ADD PRIMARY KEY (`threadid`);