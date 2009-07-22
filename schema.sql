DROP TABLE IF EXISTS pto;
CREATE TABLE IF NOT EXISTS pto (
  id int(10) unsigned NOT NULL auto_increment,
  person varchar(128) NOT NULL,
  added int(11) NOT NULL,
  reason varchar(255) NOT NULL,
  `start` int(11) NOT NULL,
  `end` int(11) NOT NULL,
  PRIMARY KEY  (id,person,added),
  KEY `start` (`start`,`end`),
  FULLTEXT KEY person (person,reason)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
