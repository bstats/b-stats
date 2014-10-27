CREATE TABLE IF NOT EXISTS `boards` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `shortname` varchar(5) COLLATE utf8_unicode_ci NOT NULL,
  `longname` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `worksafe` tinyint(4) NOT NULL,
  `pages` int(11) NOT NULL,
  `perpage` int(11) NOT NULL,
  `privilege` int(11) NOT NULL DEFAULT '0',
  `swf_board` tinyint(4) NOT NULL DEFAULT '0',
  `is_archive` tinyint(1) NOT NULL DEFAULT '1',
  `last_crawl` int(11) NOT NULL DEFAULT '0',
  `group` int(11) NOT NULL,
  PRIMARY KEY (`id`)
  UNIQUE KEY `shortname` (`shortname`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;