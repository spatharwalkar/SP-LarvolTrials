CREATE TABLE IF NOT EXISTS `clinical_study` (
  `larvol_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `institution_type` ENUM( 'industry_lead_sponsor', 'industry_collaborator', 'coop', 'other' ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT 'other',
  `import_time` datetime NOT NULL,
  `last_change` datetime NOT NULL,
  `region` varchar(63) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
  `inactive_date` date DEFAULT NULL,
  `inactive_date_lastchanged` DATE NULL DEFAULT NULL,
  `inactive_date_prev` DATE NULL DEFAULT NULL,
  `inclusion_criteria` TEXT NULL DEFAULT NULL ,
  `exclusion_criteria` TEXT NULL DEFAULT NULL ,
  PRIMARY KEY (`larvol_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `data_categories` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(127) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_names` (`name`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `data_cats_in_study` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `larvol_id` int(10) unsigned NOT NULL,
  `category` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cat_in_study_only_once` (`larvol_id`,`category`),
  KEY `FK_category` (`category`),
  KEY `FK_larvol_id` (`larvol_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `data_enumvals` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `field` int(10) unsigned NOT NULL,
  `value` varchar(63) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `block_duplicate_values_for_field` (`value`,`field`),
  KEY `FK_field` (`field`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `data_fields` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(63) COLLATE utf8_unicode_ci NOT NULL,
  `type` enum('int','varchar','text','date','enum','bool') COLLATE utf8_unicode_ci NOT NULL,
  `category` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_names_per_category` (`name`,`category`),
  KEY `FK_category` (`category`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `data_values` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `field` int(10) unsigned NOT NULL,
  `studycat` int(10) unsigned NOT NULL COMMENT 'reference to a relation between studies and categories rather than directly to the study, to ensure a field can only exist when the given study actually has the corresponding custom category attached to it',
  `val_int` int(11) DEFAULT NULL,
  `val_bool` tinyint(1) DEFAULT NULL,
  `val_varchar` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `val_date` date DEFAULT NULL,
  `val_enum` int(10) unsigned DEFAULT NULL,
  `val_text` text COLLATE utf8_unicode_ci,
  `added` datetime NOT NULL,
  `superceded` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `FK_field` (`field`),
  KEY `FK_val_enum` (`val_enum`),
  KEY `FK_studycat` (`studycat`),
  KEY `superceded` (`superceded`),
  KEY `val_int` (`val_int`,`field`),
  KEY `val_bool` (`val_bool`,`field`),
  KEY `val_varchar` (`val_varchar`,`field`),
  KEY `val_date` (`val_date`,`field`),
  KEY `val_enum` (`val_enum`,`field`),
  KEY `val_text` (`val_text`(255),`field`),
  KEY `added` (`added`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `progress` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user` int(10) unsigned NOT NULL,
  `what` enum('upload','parse','search','heatmap','competitor') COLLATE utf8_unicode_ci NOT NULL,
  `progress` int(11) NOT NULL DEFAULT '0',
  `max` int(11) NOT NULL,
  `lastUpdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `connected` tinyint(1) NOT NULL DEFAULT '0',
  `note` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `FK_user` (`user`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `reports_status` (
  `run_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `process_id` int(11) NOT NULL DEFAULT '0',
  `report_type` tinyint(3) unsigned NOT NULL,
  `type_id` int(10) unsigned NOT NULL,
  `status` tinyint(4) NOT NULL DEFAULT '0',
  `total` int(11) NOT NULL DEFAULT '0',
  `progress` int(11) DEFAULT NULL,
  `start_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `update_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `complete_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`run_id`,`report_type`,`type_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `rpt_heatmap` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(127) COLLATE utf8_unicode_ci NOT NULL,
  `user` int(10) unsigned DEFAULT NULL,
  `footnotes` text COLLATE utf8_unicode_ci,
  `description` text COLLATE utf8_unicode_ci,
  `searchdata` text COLLATE utf8_unicode_ci COMMENT 'serialized then base64_encoded POST data from the search page',
  `bomb` enum('N','Y') COLLATE utf8_unicode_ci NOT NULL,
  `backbone_agent` enum('N','Y') COLLATE utf8_unicode_ci NOT NULL,
  `count_only_active` enum('N','Y') COLLATE utf8_unicode_ci NOT NULL,
  `category` varchar(128) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `FK_user` (`user`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `rpt_heatmap_cells` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `report` int(10) unsigned NOT NULL,
  `row` tinyint(3) unsigned NOT NULL,
  `column` tinyint(3) unsigned NOT NULL,
  `searchdata` text COLLATE utf8_unicode_ci NOT NULL COMMENT 'serialized then base64_encoded POST data from the search page',
  PRIMARY KEY (`id`),
  KEY `FK_report` (`report`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `rpt_heatmap_headers` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `report` int(10) unsigned NOT NULL,
  `header` varchar(127) COLLATE utf8_unicode_ci NOT NULL,
  `num` tinyint(3) unsigned NOT NULL,
  `type` enum('row','column') COLLATE utf8_unicode_ci NOT NULL,
  `searchdata` text COLLATE utf8_unicode_ci COMMENT 'serialized then base64_encoded POST data from the search page',
  PRIMARY KEY (`id`),
  KEY `FK_report` (`report`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `rpt_trial_tracker` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(127) COLLATE utf8_unicode_ci NOT NULL,
  `user` int(10) unsigned DEFAULT NULL,
  `output_template` enum('Plain','Color A') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'Plain',
  `time` varchar(27) COLLATE utf8_unicode_ci NOT NULL,
  `edited` varchar(27) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `FK_user` (`user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `rpt_trial_tracker_trials` (
  `report` int(10) unsigned NOT NULL,
  `num` tinyint(3) unsigned NOT NULL,
  `nctid` int(10) unsigned NOT NULL,
  `tumor_type` enum('Breast','CRC','GIST','HCC','Multiple','NSCLC','Other','Ovary','RCC','Solid','Thyroid') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'Other',
  `patient_population` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `trials_details` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `randomized_controlled_trial` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `data_release` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  KEY `FK_report` (`report`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `rpt_update` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(127) COLLATE utf8_unicode_ci NOT NULL,
  `start` varchar(31) COLLATE utf8_unicode_ci DEFAULT NULL,
  `end` varchar(31) COLLATE utf8_unicode_ci DEFAULT NULL,
  `criteria` text COLLATE utf8_unicode_ci COMMENT 'serialized, base64_encoded postdata from the criteria section of the input form',
  `searchdata` text COLLATE utf8_unicode_ci COMMENT 'serialized, base64_encoded postdata from the criteria section of the input form',
  `getnew` tinyint(1) NOT NULL DEFAULT '1',
  `user` int(10) unsigned DEFAULT NULL,
  `footnotes` text COLLATE utf8_unicode_ci,
  `description` text COLLATE utf8_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `FK_user` (`user`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `saved_searches` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user` int(10) unsigned DEFAULT NULL,
  `searchdata` text COLLATE utf8_unicode_ci NOT NULL COMMENT 'serialized then base64_encoded POST data from the search page',
  `name` varchar(127) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_names` (`user`,`name`),
  KEY `FK_user` (`user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `schedule` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(31) COLLATE utf8_unicode_ci NOT NULL,
  `fetch` enum('none','nct','eudract','isrctn') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'none',
  `runtimes` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'binary flags for when the item runs -- one for each of the 24 hours of the day and the 7 days of the week',
  `lastrun` datetime NOT NULL COMMENT 'time generated by PHP, not the MySQL server!',
  `emails` text COLLATE utf8_unicode_ci,
  `format` enum('xlsx','doc') COLLATE utf8_unicode_ci NOT NULL,
  `selected` varchar(1) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `schedule_heatmaps` (
  `schedule` int(10) unsigned NOT NULL,
  `heatmap` int(10) unsigned NOT NULL,
  KEY `FK_heatmap` (`heatmap`),
  KEY `FK_schedule` (`schedule`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `schedule_updatescans` (
  `schedule` int(10) unsigned NOT NULL,
  `updatescan` int(10) unsigned NOT NULL,
  KEY `FK_updatescan` (`updatescan`),
  KEY `FK_schedule` (`schedule`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `settings` (
  `name` varchar(31) COLLATE utf8_unicode_ci NOT NULL,
  `value` varchar(127) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `updaters` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(63) COLLATE utf8_unicode_ci NOT NULL,
  `last_complete` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `update_status` (
  `update_id` int(10) unsigned NOT NULL,
  `process_id` int(11) NOT NULL DEFAULT '0',
  `start_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `end_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_days` tinyint(4) NOT NULL DEFAULT '0',
  `status` tinyint(4) NOT NULL DEFAULT '0',
  `add_items_total` int(11) NOT NULL DEFAULT '0',
  `add_items_progress` int(11) DEFAULT NULL,
  `add_items_start_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `add_items_complete_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `update_items_total` int(11) NOT NULL DEFAULT '0',
  `update_items_progress` int(11) DEFAULT NULL,
  `update_items_start_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `update_items_complete_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`update_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `upm` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `event_type` enum('Clinical','Clinical Data','Regulatory','Commercial','Pricing/Reimbursement','Other') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'Other',
  `event_description` text COLLATE utf8_unicode_ci NOT NULL,
  `event_link` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `result_link` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `corresponding_trial` int(10) unsigned DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `start_date_type` enum('anticipated','actual') COLLATE utf8_unicode_ci NOT NULL,
  `end_date` date DEFAULT NULL,
  `end_date_type` enum('anticipated','actual') COLLATE utf8_unicode_ci NOT NULL,
  `last_update` date NOT NULL,
  `product` int(10) unsigned DEFAULT NULL,
  `status` enum('Upcoming','Occurred','Pending','Cancelled') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'Upcoming',
  PRIMARY KEY (`id`),
  KEY `product` (`product`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DELIMITER $$
CREATE TRIGGER upm_status BEFORE UPDATE ON upm FOR EACH ROW
BEGIN
  IF NEW.result_link IS NOT NULL THEN
    IF NEW.end_date IS NULL THEN
      SET NEW.`status`='Cancelled';
    ELSE
      SET NEW.`status`='Occurred';
    END IF;
  ELSE
    IF NEW.end_date IS NULL THEN
      SET NEW.`status`='Cancelled';
    ELSEIF NEW.end_date<NOW() THEN
      SET NEW.`status`='Pending';
    ELSE
      SET NEW.`status`='Upcoming';
    END IF;
  END IF;
END;$$
DELIMITER ;

DELIMITER $$
CREATE TRIGGER upm_status2 BEFORE INSERT ON upm FOR EACH ROW
BEGIN
  IF NEW.result_link IS NOT NULL THEN
    IF NEW.end_date IS NULL THEN
      SET NEW.`status`='Cancelled';
    ELSE
      SET NEW.`status`='Occurred';
    END IF;
  ELSE
    IF NEW.end_date IS NULL THEN
      SET NEW.`status`='Cancelled';
    ELSEIF NEW.end_date<NOW() THEN
      SET NEW.`status`='Pending';
    ELSE
      SET NEW.`status`='Upcoming';
    END IF;
  END IF;
END;$$
DELIMITER ;

CREATE TABLE IF NOT EXISTS `upm_history` (
  `id` int(10) unsigned NOT NULL,
  `change_date` datetime NOT NULL,
  `field` enum('event_type','event_description','event_link','result_link','corresponding_trial','start_date','start_date_type','end_date','end_date_type','last_update','product','status') COLLATE utf8_unicode_ci NOT NULL,
  `old_value` text COLLATE utf8_unicode_ci,
  `new_value` text COLLATE utf8_unicode_ci,
  `user` int(10) unsigned DEFAULT NULL,
  UNIQUE KEY `user` (`user`),
  KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(31) COLLATE utf8_unicode_ci NOT NULL,
  `password` char(48) COLLATE utf8_unicode_ci NOT NULL COMMENT 'hash generated by php''s "tiger192,4" and salted against the username.',
  `fingerprint` char(48) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'A hash calculated the same as that of the password (but with no salt) that is derived from a concatenation of every bit of location info we can get about the user e.g. IP addresses, browser. Set to NULL when user clicks logout.',
  `email` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `userlevel` enum('user','admin','root') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'user',
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `user_grants` (
  `user` int(10) unsigned NOT NULL,
  `permission` int(10) unsigned NOT NULL,
  UNIQUE KEY `prevent_multivalued_grant` (`user`,`permission`),
  KEY `permission` (`permission`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `user_permissions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(31) COLLATE utf8_unicode_ci NOT NULL,
  `type` enum('readonly','contained','editing','admin','core') COLLATE utf8_unicode_ci NOT NULL,
  `level` int(10) unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `prevent_duplicate_permissions` (`name`,`level`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `rpt_ott_trials` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `result_set` text COLLATE utf8_unicode_ci NOT NULL,
  `created` datetime NOT NULL,
  `expiry` date DEFAULT NULL,
  `last_referenced` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `result_set` (`result_set`(300))
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `rpt_ott_header` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `header` varchar(127) COLLATE utf8_unicode_ci NOT NULL,
  `created` datetime NOT NULL,
  `expiry` date DEFAULT NULL,
  `last_referenced` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY (`header`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `rpt_ott_upm` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `intervention_name` BLOB NOT NULL,
  `created` datetime NOT NULL,
  `expiry` date DEFAULT NULL,
  `last_referenced` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `intervention_name` (`intervention_name`(1000))
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `rpt_ott_searchdata` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `result_set` text COLLATE utf8_unicode_ci NOT NULL,
  `created` datetime NOT NULL,
  `expiry` date DEFAULT NULL,
  `last_referenced` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `result_set` (`result_set`(300))
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `update_status_fullhistory` (  `update_id` int( 10  )  unsigned NOT  NULL ,
 `process_id` int( 11  )  NOT  NULL DEFAULT  '0',
 `start_time` timestamp NOT  NULL DEFAULT  '0000-00-00 00:00:00',
 `end_time` timestamp NOT  NULL DEFAULT  '0000-00-00 00:00:00',
 `updated_time` timestamp NOT  NULL DEFAULT  '0000-00-00 00:00:00',
 `updated_days` tinyint( 4  )  NOT  NULL DEFAULT  '0',
 `status` tinyint( 4  )  NOT  NULL DEFAULT  '0',
 `update_items_total` int( 11  )  NOT  NULL DEFAULT  '0',
`update_items_progress` int( 11 ) NOT NULL DEFAULT '0',
 `update_items_start_time` timestamp NOT  NULL DEFAULT  '0000-00-00 00:00:00',
 `update_items_complete_time` timestamp NOT  NULL DEFAULT  '0000-00-00 00:00:00',
 `current_nctid` int( 11  )  NOT  NULL DEFAULT  '0',
`max_nctid` INT( 11 ) NOT NULL DEFAULT '0',
`er_message` VARCHAR( 255 ) NOT NULL ,
`trial_type` VARCHAR( 255 ) NULL DEFAULT NULL ,
 PRIMARY  KEY (  `update_id`  )  ) ENGINE  = InnoDB  DEFAULT CHARSET  = utf8 COLLATE  = utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `products` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `LI_id` varchar(63) COLLATE utf8_unicode_ci NULL,
  `name` varchar(127) COLLATE utf8_unicode_ci NOT NULL COMMENT 'matches the fieldname in the quickfind schema',
  `comments` TEXT,
  `product_type` VARCHAR( 255 ) DEFAULT NULL,
  `licensing_mode` VARCHAR( 255 ) DEFAULT NULL,
  `administration_mode` VARCHAR( 255 ) DEFAULT NULL,
  `discontinuation_status` VARCHAR( 255 ) DEFAULT NULL,
  `discontinuation_status_comment` VARCHAR( 255 ) DEFAULT NULL,
  `is_key` BOOL DEFAULT NULL,
  `is_active` BOOL DEFAULT NULL,
  `created` DATETIME DEFAULT NULL,
  `modified` DATETIME DEFAULT NULL,
  `searchdata` text COLLATE utf8_unicode_ci COMMENT 'contains regex',
  `company` varchar(127) COLLATE utf8_unicode_ci DEFAULT NULL,
  `brand_names` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `generic_names` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `code_names` varchar(127) COLLATE utf8_unicode_ci DEFAULT NULL,
  `search_name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
 `approvals` VARCHAR( 255 ) DEFAULT NULL,
  `xml` TEXT,  
  PRIMARY KEY (`id`),
  UNIQUE KEY `LI_id` (`LI_id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `areas` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(127) COLLATE utf8_unicode_ci NOT NULL COMMENT 'matches the fieldname in the quickfind schema',
  `searchdata` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `rpt_masterhm` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(127) COLLATE utf8_unicode_ci NOT NULL,
  `user` int(10) unsigned DEFAULT NULL,
  `footnotes` text COLLATE utf8_unicode_ci,
  `description` text COLLATE utf8_unicode_ci,
  `category` varchar(128) COLLATE utf8_unicode_ci DEFAULT NULL,
  CONSTRAINT `rpt_masterhm_pk` PRIMARY KEY (`id`),
  CONSTRAINT `rpt_masterhm_fk_1` FOREIGN KEY (`user`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `rpt_masterhm_headers` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `report` int(10) unsigned NOT NULL,
  `num` tinyint(3) unsigned NOT NULL,
  `type` enum('product','area') COLLATE utf8_unicode_ci NOT NULL,
  `type_id` int(10) unsigned NULL COMMENT 'matches the id from the products/areas table',
  CONSTRAINT `rpt_masterhm_headers_pk` PRIMARY KEY (`id`),
  CONSTRAINT `rpt_masterhm_headers_fk_1` FOREIGN KEY (`report`) REFERENCES `rpt_masterhm` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `area_trials` (
  `area` int(10) unsigned NOT NULL,
  `trial` int(10) unsigned NOT NULL,
  PRIMARY KEY (`area`,`trial`),
  KEY `trial` (`trial`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `data_history` (
  `larvol_id` int(10) unsigned NOT NULL,
  `brief_title_prev` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `brief_title_lastchanged` datetime DEFAULT NULL,
  `acronym_prev` varchar(15) COLLATE utf8_unicode_ci DEFAULT NULL,
  `acronym_lastchanged` datetime DEFAULT NULL,
  `official_title_prev` text COLLATE utf8_unicode_ci,
  `official_title_lastchanged` datetime DEFAULT NULL,
  `lead_sponsor_prev` varchar(127) COLLATE utf8_unicode_ci DEFAULT NULL,
  `lead_sponsor_lastchanged` datetime DEFAULT NULL,
  `collaborator_prev` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `collaborator_lastchanged` datetime DEFAULT NULL,
  `institution_type_prev` enum('industry_lead_sponsor','industry_collaborator','coop','other') COLLATE utf8_unicode_ci DEFAULT 'other',
  `institution_type_lastchanged` datetime DEFAULT NULL,
  `source_prev` varchar(127) COLLATE utf8_unicode_ci DEFAULT NULL,
  `source_lastchanged` datetime DEFAULT NULL,
  `has_dmc_prev` tinyint(1) DEFAULT NULL,
  `has_dmc_lastchanged` datetime DEFAULT NULL,
  `brief_summary_prev` text COLLATE utf8_unicode_ci,
  `brief_summary_lastchanged` datetime DEFAULT NULL,
  `detailed_description_prev` text COLLATE utf8_unicode_ci,
  `detailed_description_lastchanged` datetime DEFAULT NULL,
  `overall_status_prev` enum('Not yet recruiting','Recruiting','Enrolling by invitation','Active, not recruiting','Completed','Suspended','Terminated','Withdrawn','Available','No Longer Available','Approved for marketing','No longer recruiting','Withheld','Temporarily Not Available') COLLATE utf8_unicode_ci DEFAULT 'Not yet recruiting',
  `overall_status_lastchanged` datetime DEFAULT NULL,
  `is_active_prev` tinyint(1) DEFAULT '1',
  `is_active_lastchanged` datetime DEFAULT NULL,
  `why_stopped_prev` text COLLATE utf8_unicode_ci,
  `why_stopped_lastchanged` datetime DEFAULT NULL,
  `start_date_prev` date DEFAULT NULL,
  `start_date_lastchanged` datetime DEFAULT NULL,
  `end_date_prev` date DEFAULT NULL,
  `end_date_lastchanged` datetime DEFAULT NULL,
  `study_type_prev` enum('Interventional','Observational','Expanded Access','N/A') COLLATE utf8_unicode_ci DEFAULT NULL,
  `study_type_lastchanged` datetime DEFAULT NULL,
  `study_design_prev` text COLLATE utf8_unicode_ci,
  `study_design_lastchanged` datetime DEFAULT NULL,
  `number_of_arms_prev` int(10) unsigned DEFAULT NULL,
  `number_of_arms_lastchanged` datetime DEFAULT NULL,
  `number_of_groups_prev` int(10) unsigned DEFAULT NULL,
  `number_of_groups_lastchanged` datetime DEFAULT NULL,
  `enrollment_prev` int(10) unsigned DEFAULT NULL,
  `enrollment_lastchanged` datetime DEFAULT NULL,
  `enrollment_type_prev` enum('Actual','Anticipated') COLLATE utf8_unicode_ci DEFAULT NULL,
  `enrollment_type_lastchanged` datetime DEFAULT NULL,
  `biospec_retention_prev` enum('None Retained','Samples With DNA','Samples Without DNA') COLLATE utf8_unicode_ci DEFAULT NULL,
  `biospec_retention_lastchanged` datetime DEFAULT NULL,
  `biospec_descr_prev` text COLLATE utf8_unicode_ci,
  `biospec_descr_lastchanged` datetime DEFAULT NULL,
  `study_pop_prev` text COLLATE utf8_unicode_ci,
  `study_pop_lastchanged` datetime DEFAULT NULL,
  `sampling_method_prev` enum('Probability Sample','Non-Probability Sample') COLLATE utf8_unicode_ci DEFAULT NULL,
  `sampling_method_lastchanged` datetime DEFAULT NULL,
  `criteria_prev` text COLLATE utf8_unicode_ci,
  `criteria_lastchanged` datetime DEFAULT NULL,
  `gender_prev` enum('Male','Female','Both') COLLATE utf8_unicode_ci DEFAULT NULL,
  `gender_lastchanged` datetime DEFAULT NULL,
  `minimum_age_prev` varchar(15) COLLATE utf8_unicode_ci DEFAULT NULL,
  `minimum_age_lastchanged` datetime DEFAULT NULL,
  `maximum_age_prev` varchar(15) COLLATE utf8_unicode_ci DEFAULT NULL,
  `maximum_age_lastchanged` datetime DEFAULT NULL,
  `healthy_volunteers_prev` tinyint(1) DEFAULT NULL,
  `healthy_volunteers_lastchanged` datetime DEFAULT NULL,
  `verification_date_prev` date DEFAULT NULL,
  `verification_date_lastchanged` datetime DEFAULT NULL,
  `lastchanged_date_prev` date DEFAULT NULL,
  `lastchanged_date_lastchanged` datetime DEFAULT NULL,
  `firstreceived_date_prev` date DEFAULT NULL,
  `firstreceived_date_lastchanged` datetime DEFAULT NULL,
  `responsible_party_name_title_prev` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `responsible_party_name_title_lastchanged` datetime DEFAULT NULL,
  `responsible_party_organization_prev` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `responsible_party_organization_lastchanged` datetime DEFAULT NULL,
  `org_study_id_prev` varchar(127) COLLATE utf8_unicode_ci DEFAULT NULL,
  `org_study_id_lastchanged` datetime DEFAULT NULL,
  `phase_prev` enum('N/A','0','0/1','1','1a','1b','1a/1b','1c','1/2','1b/2','1b/2a','2','2a','2a/2b','2b','2/3','2b/3','3','3a','3b','3/4','3b/4','4') COLLATE utf8_unicode_ci DEFAULT 'N/A',
  `phase_lastchanged` datetime DEFAULT NULL,
  `condition_prev` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `condition_lastchanged` datetime DEFAULT NULL,
  `secondary_id_prev` varchar(63) COLLATE utf8_unicode_ci DEFAULT NULL,
  `secondary_id_lastchanged` datetime DEFAULT NULL,
  `oversight_authority_prev` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `oversight_authority_lastchanged` datetime DEFAULT NULL,
  `arm_group_label_prev` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `arm_group_label_lastchanged` datetime DEFAULT NULL,
  `arm_group_type_prev` enum('Experimental','Active Comparator','Placebo Comparator','Sham Comparator','No Intervention','Other','Case','Control','Treatment Comparison','Exposure Comparison') COLLATE utf8_unicode_ci DEFAULT NULL,
  `arm_group_type_lastchanged` datetime DEFAULT NULL,
  `arm_group_description_prev` text COLLATE utf8_unicode_ci,
  `arm_group_description_lastchanged` datetime DEFAULT NULL,
  `intervention_type_prev` enum('Behavioral','Drug','Device','Biological','Biological/Vaccine','Vaccine','Genetic','Radiation','Procedure','Procedure/Surgery','Procedure/Surgery Dietary Supplement','Dietary Supplement','Gene Transfer','Therapy','Other') COLLATE utf8_unicode_ci DEFAULT NULL,
  `intervention_type_lastchanged` datetime DEFAULT NULL,
  `intervention_name_prev` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `intervention_name_lastchanged` datetime DEFAULT NULL,
  `intervention_other_name_prev` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `intervention_other_name_lastchanged` datetime DEFAULT NULL,
  `intervention_description_prev` text COLLATE utf8_unicode_ci,
  `intervention_description_lastchanged` datetime DEFAULT NULL,
  `primary_outcome_measure_prev` text COLLATE utf8_unicode_ci,
  `primary_outcome_measure_lastchanged` datetime DEFAULT NULL,
  `primary_outcome_timeframe_prev` text COLLATE utf8_unicode_ci,
  `primary_outcome_timeframe_lastchanged` datetime DEFAULT NULL,
  `primary_outcome_safety_issue_prev` text COLLATE utf8_unicode_ci,
  `primary_outcome_safety_issue_lastchanged` datetime DEFAULT NULL,
  `secondary_outcome_measure_prev` text COLLATE utf8_unicode_ci,
  `secondary_outcome_measure_lastchanged` datetime DEFAULT NULL,
  `secondary_outcome_timeframe_prev` text COLLATE utf8_unicode_ci,
  `secondary_outcome_timeframe_lastchanged` datetime DEFAULT NULL,
  `secondary_outcome_safety_issue_prev` text COLLATE utf8_unicode_ci,
  `secondary_outcome_safety_issue_lastchanged` datetime DEFAULT NULL,
  `location_name_prev` text COLLATE utf8_unicode_ci,
  `location_name_lastchanged` datetime DEFAULT NULL,
  `location_city_prev` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `location_city_lastchanged` datetime DEFAULT NULL,
  `location_state_prev` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `location_state_lastchanged` datetime DEFAULT NULL,
  `location_zip_prev` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `location_zip_lastchanged` datetime DEFAULT NULL,
  `location_country_prev` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `location_country_lastchanged` datetime DEFAULT NULL,
  `region_prev` varchar(255) COLLATE utf8_unicode_ci DEFAULT 'RestOfWorld',
  `region_lastchanged` datetime DEFAULT NULL,
  `location_status_prev` enum('Not yet recruiting','Recruiting','Enrolling by invitation','Active, not recruiting','Completed','Suspended','Terminated','Withdrawn','Available','No Longer Available','Approved for marketing','No longer recruiting','Withheld','Temporarily Not Available') COLLATE utf8_unicode_ci DEFAULT NULL,
  `location_status_lastchanged` datetime DEFAULT NULL,
  `investigator_name_prev` text COLLATE utf8_unicode_ci,
  `investigator_name_lastchanged` datetime DEFAULT NULL,
  `investigator_role_prev` enum('Principal Investigator','Sub-Investigator','Study Chair','Study Director') COLLATE utf8_unicode_ci DEFAULT NULL,
  `investigator_role_lastchanged` datetime DEFAULT NULL,
  `overall_official_name_prev` text COLLATE utf8_unicode_ci,
  `overall_official_name_lastchanged` datetime DEFAULT NULL,
  `overall_official_role_prev` enum('Principal Investigator','Sub-Investigator','Study Chair','Study Director') COLLATE utf8_unicode_ci DEFAULT NULL,
  `overall_official_role_lastchanged` datetime DEFAULT NULL,
  `overall_official_affiliation_prev` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `overall_official_affiliation_lastchanged` datetime DEFAULT NULL,
  `keyword_prev` varchar(127) COLLATE utf8_unicode_ci DEFAULT NULL,
  `keyword_lastchanged` datetime DEFAULT NULL,
  `is_fda_regulated_prev` tinyint(1) DEFAULT NULL,
  `is_fda_regulated_lastchanged` datetime DEFAULT NULL,
  `is_section_801_prev` tinyint(1) DEFAULT NULL,
  `is_section_801_lastchanged` datetime DEFAULT NULL,
  PRIMARY KEY (`larvol_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `data_manual` (
  `larvol_id` int(10) unsigned NOT NULL,
  `brief_title` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `acronym` varchar(15) COLLATE utf8_unicode_ci DEFAULT NULL,
  `official_title` text COLLATE utf8_unicode_ci,
  `lead_sponsor` varchar(127) COLLATE utf8_unicode_ci DEFAULT NULL,
  `collaborator` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `institution_type` enum('industry_lead_sponsor','industry_collaborator','coop','other') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'other',
  `source` varchar(127) COLLATE utf8_unicode_ci DEFAULT NULL,
  `has_dmc` tinyint(1) DEFAULT NULL,
  `brief_summary` text COLLATE utf8_unicode_ci,
  `detailed_description` text COLLATE utf8_unicode_ci,
  `overall_status` enum('Not yet recruiting','Recruiting','Enrolling by invitation','Active, not recruiting','Completed','Suspended','Terminated','Withdrawn','Available','No Longer Available','Approved for marketing','No longer recruiting','Withheld','Temporarily Not Available') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'Not yet recruiting',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `why_stopped` text COLLATE utf8_unicode_ci,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `study_type` enum('Interventional','Observational','Expanded Access','N/A') COLLATE utf8_unicode_ci DEFAULT NULL,
  `study_design` text COLLATE utf8_unicode_ci,
  `number_of_arms` int(10) unsigned DEFAULT NULL,
  `number_of_groups` int(10) unsigned DEFAULT NULL,
  `enrollment` int(10) unsigned DEFAULT NULL,
  `enrollment_type` enum('Actual','Anticipated') COLLATE utf8_unicode_ci DEFAULT NULL,
  `biospec_retention` enum('None Retained','Samples With DNA','Samples Without DNA') COLLATE utf8_unicode_ci DEFAULT NULL,
  `biospec_descr` text COLLATE utf8_unicode_ci,
  `study_pop` text COLLATE utf8_unicode_ci,
  `sampling_method` enum('Probability Sample','Non-Probability Sample') COLLATE utf8_unicode_ci DEFAULT NULL,
  `criteria` text COLLATE utf8_unicode_ci,
  `gender` enum('Male','Female','Both') COLLATE utf8_unicode_ci DEFAULT NULL,
  `minimum_age` varchar(15) COLLATE utf8_unicode_ci DEFAULT NULL,
  `maximum_age` varchar(15) COLLATE utf8_unicode_ci DEFAULT NULL,
  `healthy_volunteers` tinyint(1) DEFAULT NULL,
  `verification_date` date DEFAULT NULL,
  `lastchanged_date` date DEFAULT NULL,
  `firstreceived_date` date DEFAULT NULL,
  `responsible_party_name_title` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `responsible_party_organization` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `org_study_id` varchar(127) COLLATE utf8_unicode_ci DEFAULT NULL,
  `phase` enum('N/A','0','0/1','1','1a','1b','1a/1b','1c','1/2','1b/2','1b/2a','2','2a','2a/2b','2b','2/3','2b/3','3','3a','3b','3/4','3b/4','4') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'N/A',
  `condition` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `secondary_id` varchar(63) COLLATE utf8_unicode_ci DEFAULT NULL,
  `oversight_authority` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `arm_group_label` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `arm_group_type` enum('Experimental','Active Comparator','Placebo Comparator','Sham Comparator','No Intervention','Other','Case','Control','Treatment Comparison','Exposure Comparison') COLLATE utf8_unicode_ci DEFAULT NULL,
  `arm_group_description` text COLLATE utf8_unicode_ci,
  `intervention_type` enum('Behavioral','Drug','Device','Biological','Biological/Vaccine','Vaccine','Genetic','Radiation','Procedure','Procedure/Surgery','Procedure/Surgery Dietary Supplement','Dietary Supplement','Gene Transfer','Therapy','Other') COLLATE utf8_unicode_ci DEFAULT NULL,
  `intervention_name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `intervention_other_name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `intervention_description` text COLLATE utf8_unicode_ci,
  `primary_outcome_measure` text COLLATE utf8_unicode_ci,
  `primary_outcome_timeframe` text COLLATE utf8_unicode_ci,
  `primary_outcome_safety_issue` text COLLATE utf8_unicode_ci,
  `secondary_outcome_measure` text COLLATE utf8_unicode_ci,
  `secondary_outcome_timeframe` text COLLATE utf8_unicode_ci,
  `secondary_outcome_safety_issue` text COLLATE utf8_unicode_ci,
  `location_name` text COLLATE utf8_unicode_ci,
  `location_city` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `location_state` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `location_zip` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `location_country` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `region` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'RestOfWorld',
  `location_status` enum('Not yet recruiting','Recruiting','Enrolling by invitation','Active, not recruiting','Completed','Suspended','Terminated','Withdrawn','Available','No Longer Available','Approved for marketing','No longer recruiting','Withheld','Temporarily Not Available') COLLATE utf8_unicode_ci DEFAULT NULL,
  `investigator_name` text COLLATE utf8_unicode_ci,
  `investigator_role` enum('Principal Investigator','Sub-Investigator','Study Chair','Study Director') COLLATE utf8_unicode_ci DEFAULT NULL,
  `overall_official_name` text COLLATE utf8_unicode_ci,
  `overall_official_role` enum('Principal Investigator','Sub-Investigator','Study Chair','Study Director') COLLATE utf8_unicode_ci DEFAULT NULL,
  `overall_official_affiliation` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `keyword` varchar(127) COLLATE utf8_unicode_ci DEFAULT NULL,
  `is_fda_regulated` tinyint(1) DEFAULT NULL,
  `is_section_801` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`larvol_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `data_nct` (
  `larvol_id` int(10) unsigned NOT NULL,
  `nct_id` int(10) unsigned NOT NULL,
  `download_date` date DEFAULT NULL,
  `brief_title` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `acronym` varchar(15) COLLATE utf8_unicode_ci DEFAULT NULL,
  `official_title` text COLLATE utf8_unicode_ci,
  `lead_sponsor` varchar(127) COLLATE utf8_unicode_ci DEFAULT NULL,
  `lead_sponsor_class` enum('NIH','U.S. Fed','Industry','Other') COLLATE utf8_unicode_ci DEFAULT NULL,
  `collaborator` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `collaborator_class` enum('NIH','U.S. Fed','Industry','Other') COLLATE utf8_unicode_ci DEFAULT NULL,
  `source` varchar(127) COLLATE utf8_unicode_ci DEFAULT NULL,
  `has_dmc` tinyint(1) DEFAULT NULL,
  `brief_summary` text COLLATE utf8_unicode_ci,
  `detailed_description` text COLLATE utf8_unicode_ci,
  `overall_status` enum('Not yet recruiting','Recruiting','Enrolling by invitation','Active, not recruiting','Completed','Suspended','Terminated','Withdrawn','Available','No Longer Available','Approved for marketing','No longer recruiting','Withheld','Temporarily Not Available') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'Not yet recruiting',
  `why_stopped` text COLLATE utf8_unicode_ci,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `completion_date` date DEFAULT NULL,
  `completion_date_type` enum('Actual','Anticipated') COLLATE utf8_unicode_ci DEFAULT NULL,
  `primary_completion_date` date DEFAULT NULL,
  `primary_completion_date_type` enum('Actual','Anticipated') COLLATE utf8_unicode_ci DEFAULT NULL,
  `study_type` enum('Interventional','Observational','Expanded Access','N/A') COLLATE utf8_unicode_ci DEFAULT NULL,
  `study_design` text COLLATE utf8_unicode_ci,
  `number_of_arms` int(10) unsigned DEFAULT NULL,
  `number_of_groups` int(10) unsigned DEFAULT NULL,
  `enrollment` int(10) unsigned DEFAULT NULL,
  `enrollment_type` enum('Actual','Anticipated') COLLATE utf8_unicode_ci DEFAULT NULL,
  `biospec_retention` enum('None Retained','Samples With DNA','Samples Without DNA') COLLATE utf8_unicode_ci DEFAULT NULL,
  `biospec_descr` text COLLATE utf8_unicode_ci,
  `study_pop` text COLLATE utf8_unicode_ci,
  `sampling_method` enum('Probability Sample','Non-Probability Sample') COLLATE utf8_unicode_ci DEFAULT NULL,
  `criteria` text COLLATE utf8_unicode_ci,
  `gender` enum('Male','Female','Both') COLLATE utf8_unicode_ci DEFAULT NULL,
  `minimum_age` varchar(15) COLLATE utf8_unicode_ci DEFAULT NULL,
  `maximum_age` varchar(15) COLLATE utf8_unicode_ci DEFAULT NULL,
  `healthy_volunteers` tinyint(1) DEFAULT NULL,
  `contact_name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `contact_phone` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `contact_phone_ext` varchar(127) COLLATE utf8_unicode_ci DEFAULT NULL,
  `contact_email` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `backup_name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `backup_phone` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `backup_phone_ext` varchar(127) COLLATE utf8_unicode_ci DEFAULT NULL,
  `backup_email` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `verification_date` date DEFAULT NULL,
  `lastchanged_date` date DEFAULT NULL,
  `firstreceived_date` date DEFAULT NULL,
  `responsible_party_name_title` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `responsible_party_organization` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `org_study_id` varchar(127) COLLATE utf8_unicode_ci DEFAULT NULL,
  `phase` varchar(31) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'N/A',
  `nct_alias` int(10) unsigned DEFAULT NULL,
  `condition` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `secondary_id` varchar(63) COLLATE utf8_unicode_ci DEFAULT NULL,
  `oversight_authority` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `rank` int(11) DEFAULT NULL,
  `arm_group_label` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `arm_group_type` enum('Experimental','Active Comparator','Placebo Comparator','Sham Comparator','No Intervention','Other','Case','Control','Treatment Comparison','Exposure Comparison') COLLATE utf8_unicode_ci DEFAULT NULL,
  `arm_group_description` text COLLATE utf8_unicode_ci,
  `intervention_type` enum('Behavioral','Drug','Device','Biological','Biological/Vaccine','Vaccine','Genetic','Radiation','Procedure','Procedure/Surgery','Procedure/Surgery Dietary Supplement','Dietary Supplement','Gene Transfer','Therapy','Other') COLLATE utf8_unicode_ci DEFAULT NULL,
  `intervention_name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `intervention_other_name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `intervention_description` text COLLATE utf8_unicode_ci,
  `link_url` text COLLATE utf8_unicode_ci,
  `link_description` text COLLATE utf8_unicode_ci,
  `primary_outcome_measure` text COLLATE utf8_unicode_ci,
  `primary_outcome_timeframe` text COLLATE utf8_unicode_ci,
  `primary_outcome_safety_issue` text COLLATE utf8_unicode_ci,
  `secondary_outcome_measure` text COLLATE utf8_unicode_ci,
  `secondary_outcome_timeframe` text COLLATE utf8_unicode_ci,
  `secondary_outcome_safety_issue` text COLLATE utf8_unicode_ci,
  `reference_citation` text COLLATE utf8_unicode_ci,
  `reference_PMID` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `results_reference_citation` text COLLATE utf8_unicode_ci,
  `results_reference_PMID` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `location_name` text COLLATE utf8_unicode_ci,
  `location_city` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `location_state` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `location_zip` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `location_country` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `location_status` enum('Not yet recruiting','Recruiting','Enrolling by invitation','Active, not recruiting','Completed','Suspended','Terminated','Withdrawn','Available','No Longer Available','Approved for marketing','No longer recruiting','Withheld','Temporarily Not Available') COLLATE utf8_unicode_ci DEFAULT NULL,
  `location_contact_name` text COLLATE utf8_unicode_ci,
  `location_contact_phone` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `location_contact_phone_ext` varchar(127) COLLATE utf8_unicode_ci DEFAULT NULL,
  `location_contact_email` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `location_backup_name` text COLLATE utf8_unicode_ci,
  `location_backup_phone` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `location_backup_phone_ext` varchar(127) COLLATE utf8_unicode_ci DEFAULT NULL,
  `location_backup_email` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `investigator_name` text COLLATE utf8_unicode_ci,
  `investigator_role` enum('Principal Investigator','Sub-Investigator','Study Chair','Study Director') COLLATE utf8_unicode_ci DEFAULT NULL,
  `overall_official_name` text COLLATE utf8_unicode_ci,
  `overall_official_role` enum('Principal Investigator','Sub-Investigator','Study Chair','Study Director') COLLATE utf8_unicode_ci DEFAULT NULL,
  `overall_official_affiliation` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `keyword` varchar(127) COLLATE utf8_unicode_ci DEFAULT NULL,
  `is_fda_regulated` tinyint(1) DEFAULT NULL,
  `is_section_801` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`larvol_id`),
  UNIQUE KEY `nct_id` (`nct_id`),
  KEY `overall_status` (`overall_status`),
  KEY `enrollment` (`enrollment`),
  KEY `lastchanged_date` (`lastchanged_date`),
  KEY `firstreceived_date` (`firstreceived_date`),
  KEY `phase` (`phase`),
  KEY `condition` (`condition`),
  KEY `intervention_name` (`intervention_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `data_trials` (
  `larvol_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `source_id` varchar(63) COLLATE utf8_unicode_ci DEFAULT NULL,
  `brief_title` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `acronym` varchar(15) COLLATE utf8_unicode_ci DEFAULT NULL,
  `official_title` text COLLATE utf8_unicode_ci,
  `lead_sponsor` varchar(127) COLLATE utf8_unicode_ci DEFAULT NULL,
  `collaborator` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `institution_type` enum('industry_lead_sponsor','industry_collaborator','coop','other') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'other',
  `source` varchar(127) COLLATE utf8_unicode_ci DEFAULT NULL,
  `has_dmc` tinyint(1) DEFAULT NULL,
  `brief_summary` text COLLATE utf8_unicode_ci,
  `detailed_description` text COLLATE utf8_unicode_ci,
  `overall_status` enum('Not yet recruiting','Recruiting','Enrolling by invitation','Active, not recruiting','Completed','Suspended','Terminated','Withdrawn','Available','No Longer Available','Approved for marketing','No longer recruiting','Withheld','Temporarily Not Available') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'Not yet recruiting',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `why_stopped` text COLLATE utf8_unicode_ci,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `study_type` enum('Interventional','Observational','Expanded Access','N/A') COLLATE utf8_unicode_ci DEFAULT NULL,
  `study_design` text COLLATE utf8_unicode_ci,
  `number_of_arms` int(10) unsigned DEFAULT NULL,
  `number_of_groups` int(10) unsigned DEFAULT NULL,
  `enrollment` int(10) unsigned DEFAULT NULL,
  `enrollment_type` enum('Actual','Anticipated') COLLATE utf8_unicode_ci DEFAULT NULL,
  `biospec_retention` enum('None Retained','Samples With DNA','Samples Without DNA') COLLATE utf8_unicode_ci DEFAULT NULL,
  `biospec_descr` text COLLATE utf8_unicode_ci,
  `study_pop` text COLLATE utf8_unicode_ci,
  `sampling_method` enum('Probability Sample','Non-Probability Sample') COLLATE utf8_unicode_ci DEFAULT NULL,
  `criteria` text COLLATE utf8_unicode_ci,
  `gender` enum('Male','Female','Both') COLLATE utf8_unicode_ci DEFAULT NULL,
  `minimum_age` varchar(15) COLLATE utf8_unicode_ci DEFAULT NULL,
  `maximum_age` varchar(15) COLLATE utf8_unicode_ci DEFAULT NULL,
  `healthy_volunteers` tinyint(1) DEFAULT NULL,
  `verification_date` date DEFAULT NULL,
  `lastchanged_date` date DEFAULT NULL,
  `firstreceived_date` date DEFAULT NULL,
  `responsible_party_name_title` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `responsible_party_organization` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `org_study_id` varchar(127) COLLATE utf8_unicode_ci DEFAULT NULL,
  `phase` enum('N/A','0','0/1','1','1a','1b','1a/1b','1c','1/2','1b/2','1b/2a','2','2a','2a/2b','2b','2/3','2b/3','3','3a','3b','3/4','3b/4','4') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'N/A',
  `condition` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `secondary_id` varchar(63) COLLATE utf8_unicode_ci DEFAULT NULL,
  `oversight_authority` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `arm_group_label` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `arm_group_type` enum('Experimental','Active Comparator','Placebo Comparator','Sham Comparator','No Intervention','Other','Case','Control','Treatment Comparison','Exposure Comparison') COLLATE utf8_unicode_ci DEFAULT NULL,
  `arm_group_description` text COLLATE utf8_unicode_ci,
  `intervention_type` enum('Behavioral','Drug','Device','Biological','Biological/Vaccine','Vaccine','Genetic','Radiation','Procedure','Procedure/Surgery','Procedure/Surgery Dietary Supplement','Dietary Supplement','Gene Transfer','Therapy','Other') COLLATE utf8_unicode_ci DEFAULT NULL,
  `intervention_name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `intervention_other_name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `intervention_description` text COLLATE utf8_unicode_ci,
  `primary_outcome_measure` text COLLATE utf8_unicode_ci,
  `primary_outcome_timeframe` text COLLATE utf8_unicode_ci,
  `primary_outcome_safety_issue` text COLLATE utf8_unicode_ci,
  `secondary_outcome_measure` text COLLATE utf8_unicode_ci,
  `secondary_outcome_timeframe` text COLLATE utf8_unicode_ci,
  `secondary_outcome_safety_issue` text COLLATE utf8_unicode_ci,
  `location_name` text COLLATE utf8_unicode_ci,
  `location_city` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `location_state` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `location_zip` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `location_country` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `region` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'RestOfWorld',
  `location_status` enum('Not yet recruiting','Recruiting','Enrolling by invitation','Active, not recruiting','Completed','Suspended','Terminated','Withdrawn','Available','No Longer Available','Approved for marketing','No longer recruiting','Withheld','Temporarily Not Available') COLLATE utf8_unicode_ci DEFAULT NULL,
  `investigator_name` text COLLATE utf8_unicode_ci,
  `investigator_role` enum('Principal Investigator','Sub-Investigator','Study Chair','Study Director') COLLATE utf8_unicode_ci DEFAULT NULL,
  `overall_official_name` text COLLATE utf8_unicode_ci,
  `overall_official_role` enum('Principal Investigator','Sub-Investigator','Study Chair','Study Director') COLLATE utf8_unicode_ci DEFAULT NULL,
  `overall_official_affiliation` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `keyword` varchar(127) COLLATE utf8_unicode_ci DEFAULT NULL,
  `is_fda_regulated` tinyint(1) DEFAULT NULL,
  `is_section_801` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`larvol_id`),
  UNIQUE KEY `source_id` (`source_id`),
  KEY `overall_status` (`overall_status`),
  KEY `enrollment` (`enrollment`),
  KEY `lastchanged_date` (`lastchanged_date`),
  KEY `firstreceived_date` (`firstreceived_date`),
  KEY `phase` (`phase`),
  KEY `condition` (`condition`),
  KEY `intervention_name` (`intervention_name`),
  KEY `institution_type` (`institution_type`),
  KEY `is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `product_trials` (
  `product` int(10) unsigned NOT NULL,
  `trial` int(10) unsigned NOT NULL,
  PRIMARY KEY (`product`,`trial`),
  KEY `trial` (`trial`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `data_cats_in_study`
  ADD CONSTRAINT `data_cats_in_study_ibfk_1` FOREIGN KEY (`larvol_id`) REFERENCES `clinical_study` (`larvol_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `data_cats_in_study_ibfk_2` FOREIGN KEY (`category`) REFERENCES `data_categories` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `data_enumvals`
  ADD CONSTRAINT `data_enumvals_ibfk_1` FOREIGN KEY (`field`) REFERENCES `data_fields` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `data_fields`
  ADD CONSTRAINT `data_fields_ibfk_1` FOREIGN KEY (`category`) REFERENCES `data_categories` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `data_values`
  ADD CONSTRAINT `data_values_ibfk_1` FOREIGN KEY (`field`) REFERENCES `data_fields` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `data_values_ibfk_2` FOREIGN KEY (`studycat`) REFERENCES `data_cats_in_study` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `data_values_ibfk_3` FOREIGN KEY (`val_enum`) REFERENCES `data_enumvals` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `progress`
  ADD CONSTRAINT `progress_ibfk_1` FOREIGN KEY (`user`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `rpt_heatmap`
  ADD CONSTRAINT `rpt_heatmap_ibfk_1` FOREIGN KEY (`user`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `rpt_heatmap_cells`
  ADD CONSTRAINT `rpt_heatmap_cells_ibfk_1` FOREIGN KEY (`report`) REFERENCES `rpt_heatmap` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `rpt_heatmap_headers`
  ADD CONSTRAINT `rpt_heatmap_headers_ibfk_1` FOREIGN KEY (`report`) REFERENCES `rpt_heatmap` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `rpt_trial_tracker`
  ADD CONSTRAINT `rpt_trial_tracker_ibfk_1` FOREIGN KEY (`user`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `rpt_trial_tracker_trials`
  ADD CONSTRAINT `rpt_trial_tracker_trials_ibfk_1` FOREIGN KEY (`report`) REFERENCES `rpt_trial_tracker` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `rpt_update`
  ADD CONSTRAINT `rpt_update_ibfk_1` FOREIGN KEY (`user`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `saved_searches`
  ADD CONSTRAINT `saved_searches_ibfk_1` FOREIGN KEY (`user`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `schedule_heatmaps`
  ADD CONSTRAINT `schedule_heatmaps_ibfk_1` FOREIGN KEY (`schedule`) REFERENCES `schedule` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `schedule_heatmaps_ibfk_2` FOREIGN KEY (`heatmap`) REFERENCES `rpt_heatmap` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `schedule_updatescans`
  ADD CONSTRAINT `schedule_updatescans_ibfk_1` FOREIGN KEY (`schedule`) REFERENCES `schedule` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `schedule_updatescans_ibfk_2` FOREIGN KEY (`updatescan`) REFERENCES `rpt_update` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `upm_history`
  ADD CONSTRAINT `upm_history_ibfk_2` FOREIGN KEY (`user`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `upm_history_ibfk_1` FOREIGN KEY (`id`) REFERENCES `upm` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `user_grants`
  ADD CONSTRAINT `user_grants_ibfk_1` FOREIGN KEY (`user`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `user_grants_ibfk_2` FOREIGN KEY (`permission`) REFERENCES `user_permissions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `upm`
  ADD CONSTRAINT `FK_product` FOREIGN KEY (`product`) REFERENCES `products` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE; 

ALTER TABLE `area_trials`
  ADD CONSTRAINT `area_trials_ibfk_1` FOREIGN KEY (`area`) REFERENCES `areas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `area_trials_ibfk_2` FOREIGN KEY (`trial`) REFERENCES `clinical_study` (`larvol_id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `data_history`
  ADD CONSTRAINT `data_history_ibfk_1` FOREIGN KEY (`larvol_id`) REFERENCES `data_trials` (`larvol_id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `data_manual`
  ADD CONSTRAINT `data_manual_ibfk_1` FOREIGN KEY (`larvol_id`) REFERENCES `data_trials` (`larvol_id`) ON UPDATE CASCADE;

ALTER TABLE `data_nct`
  ADD CONSTRAINT `data_nct_ibfk_2` FOREIGN KEY (`larvol_id`) REFERENCES `data_trials` (`larvol_id`) ON UPDATE CASCADE;

ALTER TABLE `product_trials`
  ADD CONSTRAINT `product_trials_ibfk_1` FOREIGN KEY (`product`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `product_trials_ibfk_2` FOREIGN KEY (`trial`) REFERENCES `clinical_study` (`larvol_id`) ON DELETE CASCADE ON UPDATE CASCADE;
