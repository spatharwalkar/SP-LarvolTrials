CREATE TABLE IF NOT EXISTS `clinical_study` (
  `larvol_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `institution_type` ENUM( 'industry_lead_sponsor', 'industry_collaborator', 'coop', 'other', 'owner_sponsored') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT 'other',
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
  `shared` int(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `FK_user` (`user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `schedule` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(31) COLLATE utf8_unicode_ci NOT NULL,
  `fetch` enum('none','nct','eudract','isrctn','nct_new') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'none',
  `runtimes` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'binary flags for when the item runs -- one for each of the 24 hours of the day and the 7 days of the week',
  `lastrun` datetime NOT NULL COMMENT 'time generated by PHP, not the MySQL server!',
  `emails` text COLLATE utf8_unicode_ci,
  `format` enum('xlsx','doc') COLLATE utf8_unicode_ci NOT NULL,
  `selected` varchar(1) COLLATE utf8_unicode_ci DEFAULT NULL,
  `LI_sync` smallint(5) unsigned DEFAULT NULL COMMENT 'bit mask entry products and areas scheudler',
  `calc_HM` tinyint(4) unsigned DEFAULT NULL,
  `upm_status` tinyint(4) unsigned DEFAULT NULL,
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
  `start_date` date DEFAULT NULL,
  `start_date_type` enum('anticipated','actual') COLLATE utf8_unicode_ci NOT NULL,
  `end_date` date DEFAULT NULL,
  `end_date_type` enum('anticipated','actual') COLLATE utf8_unicode_ci NOT NULL,
  `last_update` date NOT NULL,
  `product` int(10) unsigned DEFAULT NULL,
  `status` enum('Upcoming','Occurred','Pending','Cancelled') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'Upcoming',
  `redtag` int(10) unsigned DEFAULT NULL,
  `condition` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product` (`product`),
  KEY `redtag` (`redtag`)
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
set @found := false;
select true into @found from upm where 
product=new.product and event_description = new.event_description and event_link = new.event_link ;

if @found then
signal sqlstate '45000' set message_text = '<br>Duplicate UPM entry!<br>';
end if;

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
  `field` enum('event_type','event_description','event_link','result_link','corresponding_trial','start_date','start_date_type','end_date','end_date_type','last_update','product','area','status', 'larvol_id', 'redtag', 'new') COLLATE utf8_unicode_ci NOT NULL,
  `old_value` text COLLATE utf8_unicode_ci,
  `new_value` text COLLATE utf8_unicode_ci,
  `user` int(10) unsigned DEFAULT NULL,
  KEY `user` (`user`),
  KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


CREATE TABLE IF NOT EXISTS `upm_areas` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `upm_id` int(10) unsigned DEFAULT NULL,
  `area_id` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `upm_id` (`upm_id`),
  KEY `area_id` (`area_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 ROW_FORMAT=COMPACT AUTO_INCREMENT=1 ;

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
  `intervention_name` BLOB DEFAULT NULL,
  `intervention_name_negate` BLOB DEFAULT NULL,
  `created` datetime NOT NULL,
  `expiry` date DEFAULT NULL,
  `last_referenced` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `intervention_name` (`intervention_name`(500),`intervention_name_negate`(500))
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
`item_id` INT(11) NULL DEFAULT NULL  ,
 PRIMARY  KEY (  `update_id`  )  ) ENGINE  = InnoDB  DEFAULT CHARSET  = utf8 COLLATE  = utf8_unicode_ci;
 
 CREATE TABLE `entities` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `LI_id` varchar(63) COLLATE utf8_unicode_ci DEFAULT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `class` enum('Product','Area','Disease','Institution','MOA','Biomarker','MOA_Category','Therapeutic_Area') CHARACTER SET latin1 NOT NULL,
  `description` text COLLATE utf8_unicode_ci,
  `display_name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `client_name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `category` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `comments` text CHARACTER SET latin1,
  `product_type` varchar(255) CHARACTER SET latin1 DEFAULT NULL,
  `licensing_mode` varchar(255) CHARACTER SET latin1 DEFAULT NULL,
  `administration_mode` varchar(255) CHARACTER SET latin1 DEFAULT NULL,
  `discontinuation_status` varchar(255) CHARACTER SET latin1 DEFAULT NULL,
  `discontinuation_status_comment` varchar(255) CHARACTER SET latin1 DEFAULT NULL,
  `is_key` tinyint(1) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `searchdata` text COLLATE utf8_unicode_ci COMMENT 'contains regex',
  `company` varchar(127) COLLATE utf8_unicode_ci DEFAULT NULL,
  `brand_names` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `generic_names` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `code_names` varchar(127) COLLATE utf8_unicode_ci DEFAULT NULL,
  `search_name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `approvals` varchar(255) CHARACTER SET latin1 DEFAULT NULL,
  `xml` text CHARACTER SET latin1,
  `mesh_name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name` ASC, `class` ASC),
  UNIQUE KEY `LI_id` (`LI_id` ASC, `class` ASC) 
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;


CREATE VIEW products AS
SELECT id, LI_id, name, description, category, comments, product_type, licensing_mode, administration_mode, 
discontinuation_status, discontinuation_status_comment, is_key,is_active, created, modified, searchdata, 
company, brand_names, generic_names, code_names, search_name, approvals, xml
FROM entities
WHERE class='Product';

CREATE VIEW areas AS 
SELECT id, LI_id, name, display_name, description, category, searchdata ,if(class='Disease',1,0) as coverage_area
FROM entities
WHERE class='Area' or class='Disease';


CREATE TABLE IF NOT EXISTS `rpt_masterhm` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(127) COLLATE utf8_unicode_ci NOT NULL,
  `user` int(10) unsigned DEFAULT NULL,
  `footnotes` text COLLATE utf8_unicode_ci,
  `description` text COLLATE utf8_unicode_ci,
  `category` varchar(128) COLLATE utf8_unicode_ci DEFAULT NULL,
  `shared` tinyint(1) NOT NULL DEFAULT '0',
  `total` tinyint(1) NOT NULL DEFAULT '0',
  `dtt` tinyint(1) NOT NULL DEFAULT '0',
  `display_name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  CONSTRAINT `rpt_masterhm_pk` PRIMARY KEY (`id`),
  CONSTRAINT `rpt_masterhm_fk_1` FOREIGN KEY (`user`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `rpt_masterhm_headers` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `report` int(10) unsigned NOT NULL,
  `num` int(3) unsigned NOT NULL,
  `type` enum('row','column') COLLATE utf8_unicode_ci NOT NULL,
  `type_id` int(10) unsigned NULL COMMENT 'matches the id from the products/areas table',
  `display_name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `category` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `tag` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  CONSTRAINT `rpt_masterhm_headers_pk` PRIMARY KEY (`id`),
  CONSTRAINT `rpt_masterhm_headers_fk_1` FOREIGN KEY (`report`) REFERENCES `rpt_masterhm` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `area_trials` (
  `area` int(10) unsigned NOT NULL,
  `trial` int(10) unsigned NOT NULL,
  PRIMARY KEY (`area`,`trial`),
  KEY `trial` (`trial`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `entity_trials` (
  `entity` int(10) unsigned NOT NULL,
  `trial` int(10) unsigned NOT NULL,
  PRIMARY KEY (`entity`,`trial`),
  KEY `trial` (`trial`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `entity_mesh_trials` 
(
  `entity` int(10) unsigned NOT NULL,
  `trial` int(10) unsigned NOT NULL,
  PRIMARY KEY (`entity`,`trial`),
  KEY `trial` (`trial`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT;

CREATE TABLE IF NOT EXISTS `data_history` (
  `larvol_id` int(10) unsigned NOT NULL,
  `brief_title_prev` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `brief_title_lastchanged` datetime DEFAULT NULL,
  `acronym_prev` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `acronym_lastchanged` datetime DEFAULT NULL,
  `official_title_prev` text COLLATE utf8_unicode_ci,
  `official_title_lastchanged` datetime DEFAULT NULL,
  `lead_sponsor_prev` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `lead_sponsor_lastchanged` datetime DEFAULT NULL,
  `collaborator_prev` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `collaborator_lastchanged` datetime DEFAULT NULL,
  `institution_type_prev` enum('industry_lead_sponsor','industry_collaborator','coop','other','owner_sponsored') COLLATE utf8_unicode_ci DEFAULT 'other',
  `institution_type_lastchanged` datetime DEFAULT NULL,
  `source_prev` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `source_lastchanged` datetime DEFAULT NULL,
  `has_dmc_prev` tinyint(1) DEFAULT NULL,
  `has_dmc_lastchanged` datetime DEFAULT NULL,
  `brief_summary_prev` text COLLATE utf8_unicode_ci,
  `brief_summary_lastchanged` datetime DEFAULT NULL,
  `detailed_description_prev` text COLLATE utf8_unicode_ci,
  `detailed_description_lastchanged` datetime DEFAULT NULL,
  `overall_status_prev` text COLLATE utf8_unicode_ci DEFAULT NULL,
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
  `study_pop_prev` text COLLATE utf8_unicode_ci,
  `study_pop_lastchanged` datetime DEFAULT NULL,
  `sampling_method_prev` enum('Probability Sample','Non-Probability Sample') COLLATE utf8_unicode_ci DEFAULT NULL,
  `sampling_method_lastchanged` datetime DEFAULT NULL,
  `criteria_prev` text COLLATE utf8_unicode_ci,
  `criteria_lastchanged` datetime DEFAULT NULL,
  `inclusion_criteria_prev` text COLLATE utf8_unicode_ci,
  `inclusion_criteria_lastchanged` datetime DEFAULT NULL,
  `exclusion_criteria_prev` text COLLATE utf8_unicode_ci,
  `exclusion_criteria_lastchanged` datetime DEFAULT NULL,
  `gender_prev` enum('Male','Female','Both') COLLATE utf8_unicode_ci DEFAULT NULL,
  `gender_lastchanged` datetime DEFAULT NULL,
  `minimum_age_prev` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `minimum_age_lastchanged` datetime DEFAULT NULL,
  `maximum_age_prev` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `maximum_age_lastchanged` datetime DEFAULT NULL,
  `healthy_volunteers_prev` tinyint(1) DEFAULT NULL,
  `healthy_volunteers_lastchanged` datetime DEFAULT NULL,
  `verification_date_prev` date DEFAULT NULL,
  `verification_date_lastchanged` datetime DEFAULT NULL,
  `lastchanged_date_prev` date DEFAULT NULL,
  `lastchanged_date_lastchanged` datetime DEFAULT NULL,
  `firstreceived_date_prev` date DEFAULT NULL,
  `firstreceived_date_lastchanged` datetime DEFAULT NULL,
  `org_study_id_prev` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `org_study_id_lastchanged` datetime DEFAULT NULL,
  `phase_prev` enum('N/A','0','0/1','1','1a','1b','1a/1b','1c','1/2','1b/2','1b/2a','2','2a','2a/2b','2b','2/3','2b/3','3','3a','3b','3/4','3b/4','4') COLLATE utf8_unicode_ci DEFAULT 'N/A',
  `phase_lastchanged` datetime DEFAULT NULL,
  `condition_prev` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `condition_lastchanged` datetime DEFAULT NULL,
  `secondary_id_prev` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `secondary_id_lastchanged` datetime DEFAULT NULL,
  `arm_group_label_prev` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `arm_group_label_lastchanged` datetime DEFAULT NULL,
  `arm_group_type_prev` enum('Experimental','Active Comparator','Placebo Comparator','Sham Comparator','No Intervention','Other','Case','Control','Treatment Comparison','Exposure Comparison') COLLATE utf8_unicode_ci DEFAULT NULL,
  `arm_group_type_lastchanged` datetime DEFAULT NULL,
  `arm_group_description_prev` text COLLATE utf8_unicode_ci,
  `arm_group_description_lastchanged` datetime DEFAULT NULL,
  `intervention_type_prev` enum('Behavioral','Drug','Device','Biological','Biological/Vaccine','Vaccine','Genetic','Radiation','Procedure','Procedure/Surgery','Procedure/Surgery Dietary Supplement','Dietary Supplement','Gene Transfer','Therapy','Other') COLLATE utf8_unicode_ci DEFAULT NULL,
  `intervention_type_lastchanged` datetime DEFAULT NULL,
  `intervention_name_prev` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `intervention_name_lastchanged` datetime DEFAULT NULL,
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
  `location_city_prev` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `location_city_lastchanged` datetime DEFAULT NULL,
  `location_state_prev` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `location_state_lastchanged` datetime DEFAULT NULL,
  `location_zip_prev` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `location_zip_lastchanged` datetime DEFAULT NULL,
  `location_country_prev` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `location_country_lastchanged` datetime DEFAULT NULL,
  `region_prev` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `region_lastchanged` datetime DEFAULT NULL,
  `keyword_prev` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `keyword_lastchanged` datetime DEFAULT NULL,
  `is_fda_regulated_prev` tinyint(1) DEFAULT NULL,
  `is_fda_regulated_lastchanged` datetime DEFAULT NULL,
  `is_section_801_prev` tinyint(1) DEFAULT NULL,
  `is_section_801_lastchanged` datetime DEFAULT NULL,
  `ages_prev` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `ages_lastchanged` datetime DEFAULT NULL,
  PRIMARY KEY (`larvol_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE IF NOT EXISTS `data_manual` (
  `larvol_id` int(10) unsigned NOT NULL,
  `source_id` VARCHAR(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `brief_title` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `acronym` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `official_title` text COLLATE utf8_unicode_ci,
  `lead_sponsor` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `collaborator` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `institution_type` enum('industry_lead_sponsor','industry_collaborator','coop','other','owner_sponsored') COLLATE utf8_unicode_ci DEFAULT NULL,
  `source` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `has_dmc` tinyint(1) DEFAULT NULL,
  `brief_summary` text COLLATE utf8_unicode_ci,
  `detailed_description` text COLLATE utf8_unicode_ci,
  `overall_status` enum('Not yet recruiting','Recruiting','Enrolling by invitation','Active, not recruiting','Completed','Suspended','Terminated','Withdrawn','Available','No Longer Available','Approved for marketing','Withheld','Temporarily Not Available', 'Ongoing','Not Authorized','Prohibited') COLLATE utf8_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT NULL,
  `why_stopped` text COLLATE utf8_unicode_ci,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `study_type` enum('Interventional','Observational','Expanded Access','N/A') COLLATE utf8_unicode_ci DEFAULT NULL,
  `study_design` text COLLATE utf8_unicode_ci,
  `number_of_arms` int(10) unsigned DEFAULT NULL,
  `number_of_groups` int(10) unsigned DEFAULT NULL,
  `enrollment` int(10) unsigned DEFAULT NULL,
  `enrollment_type` enum('Actual','Anticipated') COLLATE utf8_unicode_ci DEFAULT NULL,
  `study_pop` text COLLATE utf8_unicode_ci,
  `sampling_method` enum('Probability Sample','Non-Probability Sample') COLLATE utf8_unicode_ci DEFAULT NULL,
  `criteria` text COLLATE utf8_unicode_ci,
  `gender` enum('Male','Female','Both') COLLATE utf8_unicode_ci DEFAULT NULL,
  `minimum_age` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `maximum_age` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `healthy_volunteers` tinyint(1) DEFAULT NULL,
  `verification_date` date DEFAULT NULL,
  `lastchanged_date` date DEFAULT NULL,
  `firstreceived_date` date DEFAULT NULL,
  `org_study_id` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `phase` enum('N/A','0','0/1','1','1a','1b','1a/1b','1c','1/2','1b/2','1b/2a','2','2a','2a/2b','2b','2/3','2b/3','3','3a','3b','3/4','3b/4','4') COLLATE utf8_unicode_ci DEFAULT NULL,
  `condition` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `secondary_id` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `arm_group_label` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `arm_group_type` enum('Experimental','Active Comparator','Placebo Comparator','Sham Comparator','No Intervention','Other','Case','Control','Treatment Comparison','Exposure Comparison') COLLATE utf8_unicode_ci DEFAULT NULL,
  `arm_group_description` text COLLATE utf8_unicode_ci,
  `intervention_type` enum('Behavioral','Drug','Device','Biological','Biological/Vaccine','Vaccine','Genetic','Radiation','Procedure','Procedure/Surgery','Procedure/Surgery Dietary Supplement','Dietary Supplement','Gene Transfer','Therapy','Other') COLLATE utf8_unicode_ci DEFAULT NULL,
  `intervention_name` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `intervention_description` text COLLATE utf8_unicode_ci,
  `primary_outcome_measure` text COLLATE utf8_unicode_ci,
  `primary_outcome_timeframe` text COLLATE utf8_unicode_ci,
  `primary_outcome_safety_issue` text COLLATE utf8_unicode_ci,
  `secondary_outcome_measure` text COLLATE utf8_unicode_ci,
  `secondary_outcome_timeframe` text COLLATE utf8_unicode_ci,
  `secondary_outcome_safety_issue` text COLLATE utf8_unicode_ci,
  `location_name` text COLLATE utf8_unicode_ci,
  `location_city` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `location_state` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `location_zip` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `location_country` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `region` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `keyword` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `is_fda_regulated` tinyint(1) DEFAULT NULL,
  `is_section_801` tinyint(1) DEFAULT NULL,
   `is_sourceless` tinyint(1) DEFAULT NULL,
   `ages` text COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`larvol_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE IF NOT EXISTS `data_nct` (
  `larvol_id` int(10) unsigned NOT NULL,
  `nct_id` int(10) unsigned NOT NULL,
  `download_date` date DEFAULT NULL,
  `brief_title` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `acronym` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `official_title` text COLLATE utf8_unicode_ci,
  `lead_sponsor` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `lead_sponsor_class` enum('NIH','U.S. Fed','Industry','Other') COLLATE utf8_unicode_ci DEFAULT NULL,
  `collaborator` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `collaborator_class` enum('NIH','U.S. Fed','Industry','Other') COLLATE utf8_unicode_ci DEFAULT NULL,
  `source` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `has_dmc` tinyint(1) DEFAULT NULL,
  `brief_summary` text COLLATE utf8_unicode_ci,
  `detailed_description` text COLLATE utf8_unicode_ci,
  `overall_status` enum('Not yet recruiting','Recruiting','Enrolling by invitation','Active, not recruiting','Completed','Suspended','Terminated','Withdrawn','Available','No Longer Available','Approved for marketing','No longer recruiting','Withheld','Temporarily Not Available', 'Ongoing','Not Authorized','Prohibited') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'Not yet recruiting',
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
  `minimum_age` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `maximum_age` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `healthy_volunteers` tinyint(1) DEFAULT NULL,
  `contact_name` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `contact_phone` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `contact_phone_ext` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `contact_email` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `backup_name` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `backup_phone` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `backup_phone_ext` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `backup_email` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `verification_date` date DEFAULT NULL,
  `lastchanged_date` date DEFAULT NULL,
  `firstreceived_date` date DEFAULT NULL,
  `responsible_party_name_title` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `responsible_party_organization` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `org_study_id` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `phase` text COLLATE utf8_unicode_ci NOT NULL,
  `nct_alias` int(10) unsigned DEFAULT NULL,
  `condition` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `secondary_id` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `oversight_authority` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `rank` int(11) DEFAULT NULL,
  `arm_group_label` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `arm_group_type` enum('Experimental','Active Comparator','Placebo Comparator','Sham Comparator','No Intervention','Other','Case','Control','Treatment Comparison','Exposure Comparison') COLLATE utf8_unicode_ci DEFAULT NULL,
  `arm_group_description` text COLLATE utf8_unicode_ci,
  `intervention_type` enum('Behavioral','Drug','Device','Biological','Biological/Vaccine','Vaccine','Genetic','Radiation','Procedure','Procedure/Surgery','Procedure/Surgery Dietary Supplement','Dietary Supplement','Gene Transfer','Therapy','Other') COLLATE utf8_unicode_ci DEFAULT NULL,
  `intervention_name` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `intervention_other_name` text COLLATE utf8_unicode_ci DEFAULT NULL,
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
  `reference_PMID` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `results_reference_citation` text COLLATE utf8_unicode_ci,
  `results_reference_PMID` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `location_name` text COLLATE utf8_unicode_ci,
  `location_city` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `location_state` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `location_zip` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `location_country` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `location_status` enum('Not yet recruiting','Recruiting','Enrolling by invitation','Active, not recruiting','Completed','Suspended','Terminated','Withdrawn','Available','No Longer Available','Approved for marketing','No longer recruiting','Withheld','Temporarily Not Available') COLLATE utf8_unicode_ci DEFAULT NULL,
  `location_contact_name` text COLLATE utf8_unicode_ci,
  `location_contact_phone` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `location_contact_phone_ext` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `location_contact_email` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `location_backup_name` text COLLATE utf8_unicode_ci,
  `location_backup_phone` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `location_backup_phone_ext` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `location_backup_email` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `investigator_name` text COLLATE utf8_unicode_ci,
  `investigator_role` enum('Principal Investigator','Sub-Investigator','Study Chair','Study Director') COLLATE utf8_unicode_ci DEFAULT NULL,
  `overall_official_name` text COLLATE utf8_unicode_ci,
  `overall_official_role` enum('Principal Investigator','Sub-Investigator','Study Chair','Study Director') COLLATE utf8_unicode_ci DEFAULT NULL,
  `overall_official_affiliation` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `keyword` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `is_fda_regulated` tinyint(1) DEFAULT NULL,
  `is_section_801` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`nct_id`),
  KEY `overall_status` (`overall_status`),
  KEY `enrollment` (`enrollment`),
  KEY `lastchanged_date` (`lastchanged_date`),
  KEY `firstreceived_date` (`firstreceived_date`),
  KEY `phase` (`phase`(2)),
  KEY `condition` (`condition`(31)),
  KEY `intervention_name` (`intervention_name`(31)),
  KEY `larvol_id` (`larvol_id`)
  
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE IF NOT EXISTS `data_trials` (
  `larvol_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `source_id` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `brief_title` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `acronym` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `official_title` text COLLATE utf8_unicode_ci,
  `lead_sponsor` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `collaborator` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `institution_type` enum('industry_lead_sponsor','industry_collaborator','coop','other','owner_sponsored') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'other',
  `source` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `has_dmc` tinyint(1) DEFAULT NULL,
  `brief_summary` text COLLATE utf8_unicode_ci,
  `detailed_description` text COLLATE utf8_unicode_ci,
  `overall_status` enum('Not yet recruiting','Recruiting','Enrolling by invitation','Active, not recruiting','Completed','Suspended','Terminated','Withdrawn','Available','No Longer Available','Approved for marketing','No longer recruiting','Withheld','Temporarily Not Available','Ongoing','Not Authorized','Prohibited') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'Not yet recruiting',
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
  `study_pop` text COLLATE utf8_unicode_ci,
  `sampling_method` enum('Probability Sample','Non-Probability Sample') COLLATE utf8_unicode_ci DEFAULT NULL,
  `criteria` text COLLATE utf8_unicode_ci,
  `inclusion_criteria` text COLLATE utf8_unicode_ci,
  `exclusion_criteria` text COLLATE utf8_unicode_ci,
  `gender` enum('Male','Female','Both') COLLATE utf8_unicode_ci DEFAULT NULL,
  `minimum_age` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `maximum_age` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `healthy_volunteers` tinyint(1) DEFAULT NULL,
  `verification_date` date DEFAULT NULL,
  `lastchanged_date` date DEFAULT NULL,
  `firstreceived_date` date DEFAULT NULL,
  `org_study_id` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `phase` enum('N/A','0','0/1','1','1a','1b','1a/1b','1c','1/2','1b/2','1b/2a','2','2a','2a/2b','2b','2/3','2b/3','3','3a','3b','3/4','3b/4','4') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'N/A',
  `condition` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `secondary_id` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `arm_group_label` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `arm_group_type` enum('Experimental','Active Comparator','Placebo Comparator','Sham Comparator','No Intervention','Other','Case','Control','Treatment Comparison','Exposure Comparison') COLLATE utf8_unicode_ci DEFAULT NULL,
  `arm_group_description` text COLLATE utf8_unicode_ci,
  `intervention_type` enum('Behavioral','Drug','Device','Biological','Biological/Vaccine','Vaccine','Genetic','Radiation','Procedure','Procedure/Surgery','Procedure/Surgery Dietary Supplement','Dietary Supplement','Gene Transfer','Therapy','Other') COLLATE utf8_unicode_ci DEFAULT NULL,
  `intervention_name` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `intervention_description` text COLLATE utf8_unicode_ci,
  `primary_outcome_measure` text COLLATE utf8_unicode_ci,
  `primary_outcome_timeframe` text COLLATE utf8_unicode_ci,
  `primary_outcome_safety_issue` text COLLATE utf8_unicode_ci,
  `secondary_outcome_measure` text COLLATE utf8_unicode_ci,
  `secondary_outcome_timeframe` text COLLATE utf8_unicode_ci,
  `secondary_outcome_safety_issue` text COLLATE utf8_unicode_ci,
  `location_name` text COLLATE utf8_unicode_ci,
  `location_city` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `location_state` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `location_zip` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `location_country` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `region` text COLLATE utf8_unicode_ci NOT NULL,
  `keyword` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `is_fda_regulated` tinyint(1) DEFAULT NULL,
  `is_section_801` tinyint(1) DEFAULT NULL,
  `viewcount` int(10) unsigned NOT NULL DEFAULT '0',
  `ages` text COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`larvol_id`),
  UNIQUE KEY `source_id` (`source_id`),
  KEY `overall_status` (`overall_status`),
  KEY `enrollment` (`enrollment`),
  KEY `lastchanged_date` (`lastchanged_date`),
  KEY `firstreceived_date` (`firstreceived_date`),
  KEY `phase` (`phase`),
  KEY `condition` (`condition`(31)),
  KEY `intervention_name` (`intervention_name`(31)),
  KEY `institution_type` (`institution_type`),
  KEY `is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE IF NOT EXISTS `product_trials` (
  `product` int(10) unsigned NOT NULL,
  `trial` int(10) unsigned NOT NULL,
  PRIMARY KEY (`product`,`trial`),
  KEY `trial` (`trial`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `rpt_masterhm_cells` (
  `entity1` int(10) unsigned NOT NULL COMMENT 'Foreign key to entities table ID',
  `entity2` int(10) unsigned NOT NULL COMMENT 'Foreign key to entities table ID',
  `count_total` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Total trials with this product and area',
  `count_total_prev` int(10) unsigned DEFAULT NULL,
  `count_active` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Count of only active trials',
  `count_active_prev` int(10) unsigned DEFAULT NULL,
  `count_active_indlead` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Count of only active trials with institution type of industry_lead_sponsor',
  `count_active_indlead_prev` int(10) unsigned DEFAULT NULL,
  `count_active_owner_sponsored` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Count of active trials with institution type of owner_sponsored',
  `count_active_owner_sponsored_prev` int(10) unsigned DEFAULT NULL,
  `count_lastchanged` datetime DEFAULT NULL,
  `bomb` enum('none','small','large') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'none' COMMENT 'Analysts'' indication of bomb',
  `bomb_lastchanged` datetime DEFAULT NULL,
  `bomb_auto` enum('none','small','large') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'none' COMMENT 'Indication that LT suggests a bomb for this cell',
  `bomb_explain` text COLLATE utf8_unicode_ci COMMENT 'Analysts'' explanation of bomb',
  `highest_phase` enum('N/A','0','0/1','1','1a','1b','1a/1b','1c','1/2','1b/2','1b/2a','2','2a','2a/2b','2b','2/3','2b/3','3','3a','3b','3/4','3b/4','4') COLLATE utf8_unicode_ci NULL DEFAULT NULL,
  `highest_phase_prev` enum('N/A','0','0/1','1','1a','1b','1a/1b','1c','1/2','1b/2','1b/2a','2','2a','2a/2b','2b','2/3','2b/3','3','3a','3b','3/4','3b/4','4') COLLATE utf8_unicode_ci DEFAULT NULL,
  `highest_phase_lastchanged` datetime DEFAULT NULL,
  `phase4_override` tinyint(1) NOT NULL DEFAULT '0',
  `phase4_override_lastchanged` datetime DEFAULT NULL,
  `phase_explain` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `phase_explain_lastchanged` datetime DEFAULT NULL,
  `filing` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `filing_lastchanged` datetime DEFAULT NULL,
  `last_update` datetime DEFAULT NULL,
  `last_calc` datetime DEFAULT NULL,
  `viewcount` int(10) unsigned NOT NULL DEFAULT '0',
  `not_yet_recruiting`	int(10) unsigned NOT NULL DEFAULT '0' ,
  `recruiting`	int(10) unsigned NOT NULL DEFAULT '0' ,
  `enrolling_by_invitation`	int(10) unsigned NOT NULL DEFAULT '0' ,
  `active_not_recruiting`	int(10) unsigned NOT NULL DEFAULT '0' ,
  `completed`	int(10) unsigned NOT NULL DEFAULT '0' ,
  `suspended`	int(10) unsigned NOT NULL DEFAULT '0' ,
  `terminated`	int(10) unsigned NOT NULL DEFAULT '0' ,
  `withdrawn`	int(10) unsigned NOT NULL DEFAULT '0' ,
  `available`	int(10) unsigned NOT NULL DEFAULT '0' ,
  `no_longer_available`	int(10) unsigned NOT NULL DEFAULT '0' ,
  `approved_for_marketing`	int(10) unsigned NOT NULL DEFAULT '0' ,
  `no_longer_recruiting`	int(10) unsigned NOT NULL DEFAULT '0' ,
  `withheld`	int(10) unsigned NOT NULL DEFAULT '0' ,
  `temporarily_not_available`	int(10) unsigned NOT NULL DEFAULT '0' ,
  `ongoing`	int(10) unsigned NOT NULL DEFAULT '0' ,
  `not_authorized`	int(10) unsigned NOT NULL DEFAULT '0' ,
  `prohibited`	int(10) unsigned NOT NULL DEFAULT '0' ,
  `new_trials` int(10) unsigned NOT NULL DEFAULT '0' ,
  `not_yet_recruiting_active` int(10) unsigned NOT NULL DEFAULT '0', 
  `recruiting_active` int(10) unsigned NOT NULL DEFAULT '0', 
  `enrolling_by_invitation_active`  int(10) unsigned NOT NULL DEFAULT '0',
  `active_not_recruiting_active`  int(10) unsigned NOT NULL DEFAULT '0',
  `completed_active`  int(10) unsigned NOT NULL DEFAULT '0',
  `suspended_active`  int(10) unsigned NOT NULL DEFAULT '0',
  `terminated_active`  int(10) unsigned NOT NULL DEFAULT '0',
  `withdrawn_active`  int(10) unsigned NOT NULL DEFAULT '0',
  `available_active`  int(10) unsigned NOT NULL DEFAULT '0',
  `no_longer_available_active`  int(10) unsigned NOT NULL DEFAULT '0',
  `approved_for_marketing_active`  int(10) unsigned NOT NULL DEFAULT '0',
  `no_longer_recruiting_active`  int(10) unsigned NOT NULL DEFAULT '0',
  `withheld_active`  int(10) unsigned NOT NULL DEFAULT '0',
  `temporarily_not_available_active`  int(10) unsigned NOT NULL DEFAULT '0',
  `ongoing_active`  int(10) unsigned NOT NULL DEFAULT '0',
  `not_authorized_active`  int(10) unsigned NOT NULL DEFAULT '0',
  `prohibited_active` int(10) unsigned NOT NULL DEFAULT '0',
  
  `not_yet_recruiting_active_indlead`  int(10) unsigned NOT NULL DEFAULT '0',
  `recruiting_active_indlead`  int(10) unsigned NOT NULL DEFAULT '0',
  `enrolling_by_invitation_active_indlead`  int(10) unsigned NOT NULL DEFAULT '0',
  `active_not_recruiting_active_indlead`  int(10) unsigned NOT NULL DEFAULT '0',
  `completed_active_indlead`  int(10) unsigned NOT NULL DEFAULT '0',
  `suspended_active_indlead`  int(10) unsigned NOT NULL DEFAULT '0',
  `terminated_active_indlead`  int(10) unsigned NOT NULL DEFAULT '0',
  `withdrawn_active_indlead`  int(10) unsigned NOT NULL DEFAULT '0',
  `available_active_indlead`  int(10) unsigned NOT NULL DEFAULT '0',
  `no_longer_available_active_indlead`  int(10) unsigned NOT NULL DEFAULT '0',
  `approved_for_marketing_active_indlead`  int(10) unsigned NOT NULL DEFAULT '0',
  `no_longer_recruiting_active_indlead`  int(10) unsigned NOT NULL DEFAULT '0',
  `withheld_active_indlead`  int(10) unsigned NOT NULL DEFAULT '0',
  `temporarily_not_available_active_indlead`  int(10) unsigned NOT NULL DEFAULT '0',
  `ongoing_active_indlead`  int(10) unsigned NOT NULL DEFAULT '0',
  `not_authorized_active_indlead`  int(10) unsigned NOT NULL DEFAULT '0',
  `prohibited_active_indlead` int(10) unsigned NOT NULL DEFAULT '0',

  `not_yet_recruiting_active_owner_sponsored`  int(10) unsigned NOT NULL DEFAULT '0',
  `recruiting_active_owner_sponsored`  int(10) unsigned NOT NULL DEFAULT '0',
  `enrolling_by_invitation_active_owner_sponsored`  int(10) unsigned NOT NULL DEFAULT '0',
  `active_not_recruiting_active_owner_sponsored`  int(10) unsigned NOT NULL DEFAULT '0',
  `completed_active_owner_sponsored`  int(10) unsigned NOT NULL DEFAULT '0',
  `suspended_active_owner_sponsored`  int(10) unsigned NOT NULL DEFAULT '0',
  `terminated_active_owner_sponsored`  int(10) unsigned NOT NULL DEFAULT '0',
  `withdrawn_active_owner_sponsored`  int(10) unsigned NOT NULL DEFAULT '0',
  `available_active_owner_sponsored`  int(10) unsigned NOT NULL DEFAULT '0',
  `no_longer_available_active_owner_sponsored`  int(10) unsigned NOT NULL DEFAULT '0',
  `approved_for_marketing_active_owner_sponsored`  int(10) unsigned NOT NULL DEFAULT '0',
  `no_longer_recruiting_active_owner_sponsored`  int(10) unsigned NOT NULL DEFAULT '0',
  `withheld_active_owner_sponsored`  int(10) unsigned NOT NULL DEFAULT '0',
  `temporarily_not_available_active_owner_sponsored`  int(10) unsigned NOT NULL DEFAULT '0',
  `ongoing_active_owner_sponsored`  int(10) unsigned NOT NULL DEFAULT '0',
  `not_authorized_active_owner_sponsored`  int(10) unsigned NOT NULL DEFAULT '0',
  `prohibited_active_owner_sponsored` int(10) unsigned NOT NULL DEFAULT '0',  
  
  `preclinical` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`entity1`,`entity2`),
  KEY `entity2` (`entity2`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `nctids` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nctid` varchar(15) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_UNIQUE` (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `data_eudract`(
	`larvol_id` int(10) NOT NULL,
	`national_competent_authority` TEXT NULL,
	`trial_type` TEXT NULL,
	`trial_status` TEXT NULL,
	`start_date` date NULL,
	`firstreceived_date` date NULL,
	`member_state_concerned` TEXT NULL,
	`eudract_id` TEXT NULL,
	`full_title` TEXT NULL,
	`lay_title` TEXT NULL,
	`abbr_title` TEXT NULL,
	`sponsor_protocol_code` TEXT NULL,
	`isrctn_id` TEXT NULL,
	`nct_id` TEXT NULL,
	`who_urtn` TEXT NULL,
	`other_name` TEXT NULL,
	`other_id` TEXT NULL,
	`is_pip` tinyint(1) NULL,
	`pip_emad_number` TEXT NULL,
	`sponsor_name` TEXT NULL,
	`sponsor_country` TEXT NULL,
	`sponsor_status` TEXT NULL,
	`support_org_name` TEXT NULL,
	`support_org_country` TEXT NULL,
	`contact_org_name` TEXT NULL,
	`contact_point_func_name` TEXT NULL,
	`street_address` TEXT NULL,
	`city` TEXT NULL,
	`postcode` TEXT NULL,
	`country` TEXT NULL,
	`phone` TEXT NULL,
	`fax` TEXT NULL,
	`email` TEXT NULL,
	`imp_role` TEXT NULL,
	`imp_auth` tinyint(1) NULL,
	`imp_trade_name` TEXT NULL,
	`marketing_auth_holder` TEXT NULL,
	`marketing_auth_country` TEXT NULL,
	`imp_orphan` tinyint(1) NULL,
	`imp_orphan_number` TEXT NULL,
	`product_name` TEXT NULL,
	`product_code` TEXT NULL,
	`product_pharm_form` TEXT NULL,
	`product_paediatric_form` TEXT NULL,
	`product_route` TEXT NULL,
	`inn` TEXT NULL,
	`cas` TEXT NULL,
	`sponsor_code` TEXT NULL,
	`other_desc_name` TEXT NULL,
	`ev_code` TEXT NULL,
	`concentration_unit` TEXT NULL,
	`concentration_type` TEXT NULL,
	`concentration_number` float NULL,
	`imp_active_chemical` tinyint(1) NULL,
	`imp_active_bio` TEXT NULL,
	`type_at` tinyint(1) NULL,
	`type_somatic_cell` tinyint(1) NULL,
	`type_gene` tinyint(1) NULL,
	`type_tissue` tinyint(1) NULL,
	`type_combo_at` tinyint(1) NULL,
	`type_cat_class` tinyint(1) NULL,
	`type_cat_number` TEXT NULL,
	`type_combo_device_not_at` tinyint(1) NULL,
	`type_radio` tinyint(1) NULL,
	`type_immune` tinyint(1) NULL,
	`type_plasma` tinyint(1) NULL,
	`type_extract` tinyint(1) NULL,
	`type_recombinant` tinyint(1) NULL,
	`type_gmo` tinyint(1) NULL,
	`type_herbal` tinyint(1) NULL,
	`type_homeopathic` tinyint(1) NULL,
	`type_other` tinyint(1) NULL,
	`type_other_name` TEXT NULL,
	`placebo_used` tinyint(1) NULL,
	`placebo_form` TEXT NULL,
	`placebo_route` TEXT NULL,
	`condition` TEXT NULL,
	`lay_condition` TEXT NULL,
	`therapeutic_area` TEXT NULL,
	`dra_version` TEXT NULL,
	`dra_level` TEXT NULL,
	`dra_code` TEXT NULL,
	`dra_organ_class` TEXT NULL,
	`dra_rare` tinyint(1) NULL,
	`main_objective` TEXT NULL,
	`secondary_objective` TEXT NULL,
	`has_sub_study` tinyint(1) NULL,
	`sub_studies` TEXT NULL,
	`inclusion_criteria` TEXT NULL,
	`exclusion_criteria` TEXT NULL,
	`primary_endpoint` TEXT NULL,
	`primary_endpoint_timeframe` TEXT NULL,
	`secondary_endpoint` TEXT NULL,
	`secondary_endpoint_timeframe` TEXT NULL,
	`scope_diagnosis` TEXT NULL,
	`scope_prophylaxis` TEXT NULL,
	`scope_therapy` TEXT NULL,
	`scope_safety` TEXT NULL,
	`scope_efficacy` TEXT NULL,
	`scope_pharmacokinectic` TEXT NULL,
	`scope_pharmacodynamic` TEXT NULL,
	`scope_bioequivalence` TEXT NULL,
	`scope_dose_response` TEXT NULL,
	`scope_pharmacogenetic` TEXT NULL,
	`scope_pharmacogenomic` TEXT NULL,
	`scope_pharmacoeconomic` TEXT NULL,
	`scope_other` TEXT NULL,
	`scope_other_description` TEXT NULL,
	`tp_phase1_human_pharmacology` TEXT NULL,
	`tp_first_administration_humans` TEXT NULL,
	`tp_bioequivalence_study` TEXT NULL,
	`tp_other` TEXT NULL,
	`tp_other_description` TEXT NULL,
	`tp_phase2_explatory` TEXT NULL,
	`tp_phase3_confirmatory` TEXT NULL,
	`tp_phase4_use` TEXT NULL,
	`design_controlled` tinyint(1) NULL,
	`design_randomised` tinyint(1) NULL,
	`design_open` tinyint(1) NULL,
	`design_single_blind` tinyint(1) NULL,
	`design_double_blind` tinyint(1) NULL,
	`design_parallel_group` tinyint(1) NULL,
	`design_crossover` tinyint(1) NULL,
	`design_other` tinyint(1) NULL,
	`design_other_description` TEXT NULL,
	`comp_other_products` TEXT NULL,
	`comp_placebo` TEXT NULL,
	`comp_other` TEXT NULL,
	`comp_descr` TEXT NULL,
	`comp_number_arms` TEXT NULL,
	`single_site` tinyint(1) NULL,
	`multi_site` tinyint(1) NULL,
	`number_of_sites` int(10) NULL,
	`multiple_member_state` tinyint(1) NULL,
	`number_sites_eea` int(10) NULL,
	`eea_both_inside_outside` tinyint(1) NULL,
	`eea_outside_only` tinyint(1) NULL,
	`eea_inside_outside_regions` TEXT NULL,
	`has_data_mon_comm` tinyint(1) NULL,
	`definition_of_end` TEXT NULL,
	`dur_est_member_years` int(10) NULL,
	`dur_est_member_months` int(10) NULL,
	`dur_est_member_days` int(10) NULL,
	`dur_est_all_years` int(10) NULL,
	`dur_est_all_months` int(10) NULL,
	`dur_est_all_days` int(10) NULL,
	`age_has_under18` tinyint(1) NULL,
	`age_number_under18` int(10) NULL,
	`age_has_in_utero` tinyint(1) NULL,
	`age_number_in_utero` int(10) NULL,
	`age_has_preterm_newborn` tinyint(1) NULL,
	`age_number_preterm_newborn` int(10) NULL,
	`age_has_newborn` tinyint(1) NULL,
	`age_number_newborn` int(10) NULL,
	`age_has_infant_toddler` tinyint(1) NULL,
	`age_number_infant_toddler` int(10) NULL,
	`age_has_children` tinyint(1) NULL,
	`age_number_children` int(10) NULL,
	`age_has_adolescent` tinyint(1) NULL,
	`age_number_adolescent` int(10) NULL,
	`age_has_adult` tinyint(1) NULL,
	`age_number_adult` int(10) NULL,
	`age_has_elderly` tinyint(1) NULL,
	`age_number_elderly` int(10) NULL,
	`gender_female` tinyint(1) NULL,
	`gender_male` tinyint(1) NULL,
	`subjects_healthy_volunteers` tinyint(1) NULL,
	`subjects_patients` tinyint(1) NULL,
	`subjects_vulnerable` tinyint(1) NULL,
	`subjects_childbearing_no_contraception` tinyint(1) NULL,
	`subjects_childbearing_with_contraception` tinyint(1) NULL,
	`subjects_pregnant` tinyint(1) NULL,
	`subjects_nursing` tinyint(1) NULL,
	`subjects_emergency` tinyint(1) NULL,
	`subjects_incapable_consent` tinyint(1) NULL,
	`subjects_incapable_consent_details` TEXT NULL,
	`subjects_other` tinyint(1) NULL,
	`subjects_other_details` TEXT NULL,
	`enrollment_memberstate` int(10) NULL,
	`enrollment_intl_eea` int(10) NULL,
	`enrollment_intl_all` int(10) NULL,
	`aftercare` TEXT NULL,
	`inv_network_org` TEXT NULL,
	`inv_network_country` TEXT NULL,
	`committee_third_first_auth` TEXT NULL,
	`committee_first_auth_third` TEXT NULL,
	`review_decision` TEXT NULL,
	`review_decision_date` date NULL,
	`review_opinion` TEXT NULL,
	`review_opinion_reason` TEXT NULL,
	`review_opinion_date` date NULL,
	`end_status` TEXT NULL,
	`end_date_global` TEXT NULL,
	PRIMARY KEY (`eudract_id`(255)),
	KEY `larvol_id` (`larvol_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=DYNAMIC;


CREATE TABLE IF NOT EXISTS `redtags` (
   `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(63) COLLATE utf8_unicode_ci NOT NULL,
  `type` enum('Clinical','Clinical data','Clinical New Trial','New Trial','Clinical Trial status','Trial status','Clinical Enrollment status','Enrollment status','Clinical Other','Other','Regulatory','Regulatory FDA event','FDA event','Regulatory Non-US regulatory','Non-US regulatory','Regulatory Other','Reimbursement','Reimbursement US reimbursement','US reimbursement','Reimbursement NICE','Commercial','Commercial Sales','Sales','Commercial Licensing / partnership','Licensing / partnership','Commercial Patent','Patent','Commercial Launch','Launch','Commercial Launch Non-US','Launch Non-US','Commercial Other','Other Preclinical') COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `upm_trials` (
  `upm_id` int(10) unsigned NOT NULL,
  `larvol_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`upm_id`,`larvol_id`),
  KEY `upm_id` (`upm_id`),
  KEY `trial` (`larvol_id`),
  CONSTRAINT `upm_trials_ibfk_1` FOREIGN KEY (`upm_id`) REFERENCES `upm` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `upm_trials_ibfk_2` FOREIGN KEY (`larvol_id`) REFERENCES `data_trials` (`larvol_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE VIEW institutions AS
SELECT id, LI_id, name, category as 'type', display_name, is_active, created, modified, search_name as search_terms, client_name, xml 
FROM entities
WHERE class='Institution';

CREATE TABLE IF NOT EXISTS `products_institutions` (
 `product` int(10) unsigned NOT NULL,
 `institution` int(10) unsigned NOT NULL,
 PRIMARY KEY (`product`,`institution`),
 KEY `institution` (`institution`),
 KEY `product` (`product`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE VIEW moas AS
SELECT id, LI_id, name, display_name, is_active, created, modified, xml 	
FROM entities
WHERE class='MOA';


CREATE TABLE IF NOT EXISTS `products_moas` (
 `product` int(10) unsigned NOT NULL,
 `moa` int(10) unsigned NOT NULL,
 PRIMARY KEY (`product`,`moa`),
 KEY `moa` (`moa`),
 KEY `product` (`product`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


CREATE TABLE `entity_relations` (
  `parent` int(10) unsigned NOT NULL,
  `child` int(10) unsigned NOT NULL,
  PRIMARY KEY (`parent`,`child`),
  KEY `parent` (`parent`),
  KEY `child` (`child`),
  CONSTRAINT `entity_relations_fk1` FOREIGN KEY (`parent`) REFERENCES `entities` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `entity_relations_fk2` FOREIGN KEY (`child`) REFERENCES `entities` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;

ALTER TABLE `products_moas`
  ADD CONSTRAINT `products_moas_ibfk_1` FOREIGN KEY (`product`) REFERENCES `entities` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `products_moas_ibfk_2` FOREIGN KEY (`moa`) REFERENCES `entities` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `products_institutions`
  ADD CONSTRAINT `products_institutions_ibfk_2` FOREIGN KEY (`institution`) REFERENCES `entities` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `products_institutions_ibfk_1` FOREIGN KEY (`product`) REFERENCES `entities` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
	
ALTER TABLE `rpt_masterhm_cells`
  ADD CONSTRAINT `rpt_masterhm_cells_ibfk_2` FOREIGN KEY (`entity2`) REFERENCES `entities` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `rpt_masterhm_cells_ibfk_1` FOREIGN KEY (`entity1`) REFERENCES `entities` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

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
  ADD CONSTRAINT `FK_product` FOREIGN KEY (`product`) REFERENCES `entities` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `upm_ibfk_2` FOREIGN KEY (`redtag`) REFERENCES `redtags` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `area_trials`
  ADD CONSTRAINT `area_trials_ibfk_1` FOREIGN KEY (`area`) REFERENCES `entities` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `area_trials_ibfk_2` FOREIGN KEY (`trial`) REFERENCES `data_trials` (`larvol_id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `entity_trials`
  ADD CONSTRAINT `entity_trials_ibfk_1` FOREIGN KEY (`entity`) REFERENCES `entities` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `entity_trials_ibfk_2` FOREIGN KEY (`trial`) REFERENCES `data_trials` (`larvol_id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `data_history`
  ADD CONSTRAINT `data_history_ibfk_1` FOREIGN KEY (`larvol_id`) REFERENCES `data_trials` (`larvol_id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `data_manual`
  ADD CONSTRAINT `data_manual_ibfk_1` FOREIGN KEY (`larvol_id`) REFERENCES `data_trials` (`larvol_id`) ON UPDATE CASCADE;

ALTER TABLE `data_nct`
  ADD CONSTRAINT `data_nct_ibfk_2` FOREIGN KEY (`larvol_id`) REFERENCES `data_trials` (`larvol_id`) ON UPDATE CASCADE;

ALTER TABLE `product_trials`
  ADD CONSTRAINT `product_trials_ibfk_1` FOREIGN KEY (`product`) REFERENCES `entities` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `product_trials_ibfk_2` FOREIGN KEY (`trial`) REFERENCES `data_trials` (`larvol_id`) ON DELETE CASCADE ON UPDATE CASCADE;
  
ALTER TABLE `upm_areas`
  ADD CONSTRAINT `upm_areas_ibfk_2` FOREIGN KEY (`area_id`) REFERENCES `entities` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `upm_areas_ibfk_1` FOREIGN KEY (`upm_id`) REFERENCES `upm` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `entity_mesh_trials`  
  ADD CONSTRAINT `entity_mesh_trials_ibfk_1` FOREIGN KEY (`entity`) REFERENCES `entities` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `entity_mesh_trials_ibfk_2` FOREIGN KEY (`trial`) REFERENCES `data_trials` (`larvol_id`) ON DELETE CASCADE ON UPDATE CASCADE;
