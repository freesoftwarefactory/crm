CREATE TABLE IF NOT EXISTS `crm_contact` (
	`id` char(20) not null,
	`user_name` char(20) not null,
  	`creation_date` bigint(20) DEFAULT NULL,
  	`mod_date` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `crm_contact_rel` (
	`id` int(11) NOT NULL AUTO_INCREMENT,
  	`creation_date` bigint(20) DEFAULT NULL,
  	`mod_date` bigint(20) DEFAULT NULL,
	`contact_id` char(20) not null,
	`target_id` char(20) not null,
	`relname` char(50),
	`meta` char(255),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `crm_contact_meta` (
	`id` int(11) NOT NULL AUTO_INCREMENT,
  	`creation_date` bigint(20) DEFAULT NULL,
  	`mod_date` bigint(20) DEFAULT NULL,
	`contact_id` char(20) not null,
	`meta_name` char(50),
	`meta_value` char(255),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

