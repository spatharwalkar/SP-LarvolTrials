CREATE TABLE IF NOT EXISTS `clinical_study` (
  `larvol_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `institution_type` ENUM( 'industry_lead_sponsor', 'industry_collaborator', 'coop', 'other' ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT 'other',
  `import_time` datetime NOT NULL,
  `last_change` datetime NOT NULL,
  `region` varchar(63) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
  `inactive_date` date DEFAULT NULL,
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
  `event_type` enum('Clinical','Regulatory','Commercial','Pricing/Reimbursement','Other') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'Other',
  `event_description` text COLLATE utf8_unicode_ci NOT NULL,
  `event_link` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `result_link` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `product` varchar(127) COLLATE utf8_unicode_ci NOT NULL,
  `corresponding_trial` int(10) unsigned DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `start_date_type` enum('anticipated','actual') COLLATE utf8_unicode_ci NOT NULL,
  `end_date` date DEFAULT NULL,
  `end_date_type` enum('anticipated','actual') COLLATE utf8_unicode_ci NOT NULL,
  `last_update` date NOT NULL,
  PRIMARY KEY (`id`),
  KEY `product` (`product`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `upm_history` (
  `id` int(10) unsigned NOT NULL,
  `event_type` enum('Clinical','Regulatory','Commercial','Pricing/Reimbursement','Other') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'Other',
  `event_description` text COLLATE utf8_unicode_ci NOT NULL,
  `event_link` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `result_link` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `product` varchar(127) COLLATE utf8_unicode_ci NOT NULL,
  `corresponding_trial` int(10) unsigned DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `start_date_type` enum('anticipated','actual') COLLATE utf8_unicode_ci NOT NULL,
  `end_date` date DEFAULT NULL,
  `end_date_type` enum('anticipated','actual') COLLATE utf8_unicode_ci NOT NULL,
  `added` date NOT NULL,
  `superceded` date NOT NULL,
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
  `intervention_name` varbinary(255) NOT NULL,
  `created` datetime NOT NULL,
  `expiry` date DEFAULT NULL,
  `last_referenced` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY (`intervention_name`)
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
  `LI_id` varchar(63) COLLATE utf8_unicode_ci NOT NULL,
  `name` varchar(127) COLLATE utf8_unicode_ci NOT NULL COMMENT 'matches the fieldname in the quickfind schema',
  `searchdata` text COLLATE utf8_unicode_ci COMMENT 'contains regex',
  `company` varchar(127) COLLATE utf8_unicode_ci DEFAULT NULL,
  `brand names` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `generic names` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `code names` varchar(127) COLLATE utf8_unicode_ci DEFAULT NULL,
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
  ADD CONSTRAINT `upm_history_ibfk_1` FOREIGN KEY (`id`) REFERENCES `upm` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `user_grants`
  ADD CONSTRAINT `user_grants_ibfk_1` FOREIGN KEY (`user`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `user_grants_ibfk_2` FOREIGN KEY (`permission`) REFERENCES `user_permissions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
