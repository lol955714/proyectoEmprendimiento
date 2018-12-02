CREATE TABLE IF NOT EXISTS `#__jsn_pagebuilder3_config` (
	`name` varchar( 255 ) NOT NULL ,
	`value` text NOT NULL ,
	UNIQUE KEY `name` ( `name` )
)  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `#__jsn_pagebuilder3_messages` (
	`msg_id` int(11) NOT NULL AUTO_INCREMENT,
	`msg_screen` varchar(150) DEFAULT NULL,
	`published` tinyint(1) DEFAULT 1,
	`ordering` int(11) DEFAULT 0,
	PRIMARY KEY (`msg_id`),
	UNIQUE KEY `message` (`msg_screen`,`ordering`)
)  DEFAULT CHARSET=utf8;


CREATE TABLE IF NOT EXISTS `#__jsn_pagebuilder3_pages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `page_hash` VARCHAR(15) NOT NULL,
  `data` LONGTEXT,
  `style` LONGTEXT,
  `updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `page_hash` (`page_hash`)
) DEFAULT CHARSET=utf8;


CREATE TABLE IF NOT EXISTS `#__jsn_pagebuilder3_revisions` ( 
	`id` INT(11) NOT NULL AUTO_INCREMENT, 
	`page_hash` VARCHAR(15) NOT NULL, 
	`status` VARCHAR(15) DEFAULT 'normal', 
	`revision_data` MEDIUMTEXT NULL DEFAULT NULL , 
	`revision_html` MEDIUMTEXT NULL DEFAULT NULL , 
	`updated` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP , 
	PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;