CREATE TABLE `banned_hashes` (
  `hash` binary(16) NOT NULL,
  PRIMARY KEY (`hash`)
) ENGINE=Aria DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `bans` (
  `ip` varchar(44) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reason` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expires` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`ip`)
) ENGINE=Aria DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `boards` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `shortname` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL,
  `longname` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `worksafe` tinyint(4) NOT NULL,
  `pages` int(11) NOT NULL,
  `perpage` int(11) NOT NULL,
  `privilege` int(11) NOT NULL DEFAULT '0',
  `swf_board` tinyint(4) NOT NULL DEFAULT '0',
  `is_archive` tinyint(1) NOT NULL DEFAULT '1',
  `first_crawl` int(11) NOT NULL,
  `last_crawl` int(11) NOT NULL DEFAULT '0',
  `group` int(11) NOT NULL,
  `hidden` tinyint(1) NOT NULL DEFAULT '0',
  `archive_time` int(11) NOT NULL DEFAULT '30',
  PRIMARY KEY (`id`),
  UNIQUE KEY `shortname` (`shortname`)
) ENGINE=Aria DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `logins` (
  `uid` int(11) NOT NULL,
  `time` int(11) NOT NULL,
  `ip` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
   KEY `uid` (`uid`)
) ENGINE=Aria DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `news` (
  `article_id` int(11) NOT NULL AUTO_INCREMENT,
  `author_id` int(11) NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `time` int(11) NOT NULL,
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `update` int(11) NOT NULL,
  PRIMARY KEY (`article_id`)
) ENGINE=Aria DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `reports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL,
  `ip` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `board` varchar(6) COLLATE utf8mb4_unicode_ci NOT NULL,
  `time` int(11) NOT NULL,
  `no` int(11) NOT NULL,
  `threadid` int(11) NOT NULL,
  `archived` TINYINT(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `no` (`no`,`uid`)
) ENGINE=Aria DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `request` (
  `ip` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reason` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `username` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` binary(16) NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `time` int(11) NOT NULL,
  `accepted` tinyint(1) NOT NULL,
  PRIMARY KEY (`ip`)
) ENGINE=Aria DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `users` (
  `uid` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `username` varchar(250) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` binary(16) NOT NULL,
  `privilege` tinyint(4) NOT NULL,
  `theme` varchar(20) CHARACTER SET utf8mb4 NOT NULL,
  PRIMARY KEY (`uid`),
  UNIQUE KEY `username` (`username`)
) ENGINE=Aria DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;