CREATE TABLE IF NOT EXISTS `pto` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `person` varchar(128) NOT NULL,
  `added` int(11) NOT NULL,
  `hours` float unsigned NOT NULL,
  `hours_daily` text NOT NULL,
  `details` varchar(255) NOT NULL,
  `start` int(11) NOT NULL,
  `end` int(11) NOT NULL,
  PRIMARY KEY  (`id`,`person`,`added`),
  KEY `hours` (`hours`),
  KEY `start` (`start`),
  KEY `end` (`end`),
  FULLTEXT KEY `person` (`person`),
  FULLTEXT KEY `details` (`details`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

/*ALTER TABLE `pto` ADD COLUMN `hours_daily` text NOT NULL after `hours`;*/

