INSERT INTO `data_categories` (`id`, `name`) VALUES
(1, 'NCT'),
(2, 'Annotations'),
(4, 'PubMed'),
(6, 'EudraCT'),
(7, 'isrctn'),
(8, 'Products'), 
(9, 'Areas');

INSERT INTO `data_fields` (`id`, `name`, `type`, `category`) VALUES
(1, 'nct_id', 'int', 1),
(2, 'download_date', 'date', 1),
(3, 'brief_title', 'text', 1),
(4, 'acronym', 'varchar', 1),
(5, 'official_title', 'text', 1),
(6, 'lead_sponsor', 'varchar', 1),
(7, 'source', 'varchar', 1),
(8, 'has_dmc', 'bool', 1),
(9, 'brief_summary', 'text', 1),
(10, 'detailed_description', 'text', 1),
(13, 'overall_status', 'enum', 1),
(14, 'why_stopped', 'varchar', 1),
(15, 'start_date', 'date', 1),
(16, 'end_date', 'date', 1),
(17, 'completion_date', 'date', 1),
(18, 'completion_date_type', 'enum', 1),
(19, 'primary_completion_date', 'date', 1),
(20, 'primary_completion_date_type', 'enum', 1),
(21, 'study_type', 'enum', 1),
(22, 'study_design', 'varchar', 1),
(23, 'number_of_arms', 'int', 1),
(24, 'number_of_groups', 'int', 1),
(25, 'enrollment', 'int', 1),
(26, 'enrollment_type', 'enum', 1),
(27, 'biospec_retention', 'enum', 1),
(28, 'biospec_descr', 'text', 1),
(29, 'study_pop', 'text', 1),
(30, 'sampling_method', 'enum', 1),
(31, 'criteria', 'text', 1),
(32, 'gender', 'enum', 1),
(33, 'minimum_age', 'int', 1),
(34, 'maximum_age', 'int', 1),
(35, 'healthy_volunteers', 'bool', 1),
(36, 'contact_name', 'varchar', 1),
(39, 'contact_degrees', 'varchar', 1),
(40, 'contact_phone', 'varchar', 1),
(41, 'contact_phone_ext', 'varchar', 1),
(42, 'contact_email', 'varchar', 1),
(43, 'backup_name', 'varchar', 1),
(46, 'backup_degrees', 'varchar', 1),
(47, 'backup_phone', 'varchar', 1),
(48, 'backup_phone_ext', 'varchar', 1),
(49, 'backup_email', 'varchar', 1),
(50, 'verification_date', 'date', 1),
(51, 'lastchanged_date', 'date', 1),
(52, 'firstreceived_date', 'date', 1),
(53, 'responsible_party_name_title', 'varchar', 1),
(54, 'responsible_party_organization', 'varchar', 1),
(55, 'org_study_id', 'varchar', 1),
(56, 'phase', 'enum', 1),
(58, 'nct_alias', 'varchar', 1),
(59, 'condition', 'varchar', 1),
(60, 'collaborator', 'varchar', 1),
(61, 'secondary_id', 'varchar', 1),
(62, 'oversight_authority', 'varchar', 1),
(63, 'rank', 'int', 1),
(64, 'arm_group_label', 'varchar', 1),
(65, 'arm_group_type', 'enum', 1),
(66, 'arm_group_description', 'text', 1),
(67, 'intervention_type', 'enum', 1),
(68, 'intervention_name', 'varchar', 1),
(69, 'intervention_other_name', 'varchar', 1),
(70, 'intervention_description', 'text', 1),
(71, 'link_url', 'varchar', 1),
(72, 'link_description', 'varchar', 1),
(73, 'primary_outcome_measure', 'text', 1),
(74, 'primary_outcome_timeframe', 'text', 1),
(75, 'primary_outcome_safety_issue', 'bool', 1),
(76, 'secondary_outcome_measure', 'text', 1),
(77, 'secondary_outcome_timeframe', 'text', 1),
(78, 'secondary_outcome_safety_issue', 'bool', 1),
(79, 'reference_citation', 'varchar', 1),
(80, 'reference_PMID', 'int', 1),
(81, 'results_reference_citation', 'varchar', 1),
(82, 'results_reference_PMID', 'int', 1),
(83, 'location_name', 'varchar', 1),
(84, 'location_city', 'varchar', 1),
(85, 'location_state', 'varchar', 1),
(86, 'location_zip', 'varchar', 1),
(87, 'location_country', 'varchar', 1),
(88, 'location_status', 'enum', 1),
(89, 'location_contact_name', 'varchar', 1),
(92, 'location_contact_degrees', 'varchar', 1),
(93, 'location_contact_phone', 'varchar', 1),
(94, 'location_contact_phone_ext', 'varchar', 1),
(95, 'location_contact_email', 'varchar', 1),
(96, 'location_backup_name', 'varchar', 1),
(97, 'location_backup_degrees', 'varchar', 1),
(98, 'location_backup_phone', 'varchar', 1),
(99, 'location_backup_phone_ext', 'varchar', 1),
(100, 'location_backup_email', 'varchar', 1),
(101, 'investigator_name', 'varchar', 1),
(102, 'investigator_degrees', 'varchar', 1),
(103, 'investigator_role', 'enum', 1),
(104, 'overall_official_name', 'varchar', 1),
(105, 'overall_official_degrees', 'varchar', 1),
(106, 'overall_official_role', 'enum', 1),
(107, 'overall_official_affiliation', 'varchar', 1),
(108, 'analyst_comments', 'text', 2),
(109, 'management_team_comments', 'text', 2),
(124, 'PMID', 'int', 4),
(125, 'date_created', 'date', 4),
(126, 'date_completed', 'date', 4),
(127, 'date_revised', 'date', 4),
(129, 'owner', 'enum', 4),
(130, 'status', 'enum', 4),
(131, 'article_pubmodel', 'enum', 4),
(132, 'article_journal_issn', 'varchar', 4),
(133, 'article_journal_issn_type', 'enum', 4),
(134, 'article_journal_issue_citedmedium', 'enum', 4),
(135, 'article_journal_issue_volume', 'varchar', 4),
(136, 'article_journal_issue', 'varchar', 4),
(137, 'article_journal_issue_pubdate', 'date', 4),
(138, 'article_journal_title', 'varchar', 4),
(139, 'article_journal_ISOAbbreviation', 'varchar', 4),
(140, 'article_title', 'varchar', 4),
(141, 'article_pagination', 'varchar', 4),
(144, 'article_abstract', 'text', 4),
(145, 'article_abstract_copyright', 'varchar', 4),
(146, 'article_affiliation', 'varchar', 4),
(147, 'article_authorlist_complete', 'bool', 4),
(148, 'article_author_name', 'varchar', 4),
(149, 'article_author_nameid', 'varchar', 4),
(151, 'article_author_nameid_source', 'enum', 4),
(152, 'article_author_valid', 'bool', 4),
(153, 'article_language', 'varchar', 4),
(154, 'article_databanklist_complete', 'bool', 4),
(155, 'article_databank_name', 'varchar', 4),
(156, 'article_databank_accession_number', 'varchar', 4),
(157, 'article_grantlist_complete', 'bool', 4),
(158, 'article_grant_id', 'varchar', 4),
(159, 'article_grant_acronym', 'varchar', 4),
(160, 'article_grant_agency', 'varchar', 4),
(161, 'article_grant_country', 'varchar', 4),
(162, 'article_publication_type', 'varchar', 4),
(163, 'article_vernacular_title', 'varchar', 4),
(164, 'article_date', 'date', 4),
(165, 'medline_journal_country', 'varchar', 4),
(166, 'medline_journal_TA', 'varchar', 4),
(167, 'medline_journal_nlmuniqueid', 'varchar', 4),
(168, 'medline_journal_issnlinking', 'varchar', 4),
(169, 'chemical_registrynumber', 'varchar', 4),
(170, 'chemical_name', 'varchar', 4),
(171, 'citation_subset', 'varchar', 4),
(172, 'commentscorrections_reftype', 'enum', 4),
(173, 'commentscorrections_refsource', 'varchar', 4),
(174, 'commentscorrections_PMID', 'int', 4),
(175, 'commentscorrections_note', 'varchar', 4),
(176, 'gene_symbol', 'varchar', 4),
(177, 'meshheading_descriptor_majortopic', 'bool', 4),
(178, 'meshheading_descriptor', 'varchar', 4),
(179, 'meshheading_qualifier_majortopic', 'bool', 4),
(180, 'meshheading_qualifier', 'varchar', 4),
(181, 'number_of_references', 'int', 4),
(182, 'personal_name_subject', 'varchar', 4),
(183, 'other_id', 'varchar', 4),
(184, 'other_id_source', 'enum', 4),
(185, 'other_abstract', 'text', 4),
(186, 'other_abstract_type', 'enum', 4),
(187, 'other_abstract_copyright', 'varchar', 4),
(188, 'keyword', 'varchar', 4),
(189, 'keyword_majortopic', 'bool', 4),
(190, 'keyword_owner', 'enum', 4),
(191, 'space_flight_mission', 'varchar', 4),
(192, 'investigator_valid', 'bool', 4),
(193, 'investigator_name', 'varchar', 4),
(194, 'investigator_nameid', 'varchar', 4),
(195, 'investigator_affiliation', 'varchar', 4),
(196, 'general_note', 'text', 4),
(197, 'general_note_owner', 'enum', 4),

(200, 'member_state', 'varchar', 6),
(201, 'eudract_number', 'varchar', 6),
(202, 'full_title', 'text', 6),
(203, 'trial_title', 'varchar', 6),
(204, 'brief_title', 'varchar', 6),
(205, 'sponsor_code', 'varchar', 6),
(206, 'isrctn', 'varchar', 6),
(207, 'usnct', 'varchar', 6),
(208, 'utrn', 'varchar', 6),
(209, 'other_name', 'varchar', 6),
(210, 'other_identifier', 'varchar', 6),
(211, 'partof_pip', 'varchar', 6),
(212, 'decision_pip', 'varchar', 6),
(220, 'sponsor_name', 'varchar', 6),
(221, 'sponsor_country', 'varchar', 6),
(222, 'sponsor_status', 'varchar', 6),
(223, 'support_org', 'varchar', 6),
(224, 'support_org_country', 'varchar', 6),
(225, 'contact_name', 'varchar', 6),
(226, 'contact_function', 'varchar', 6),
(227, 'contact_address', 'varchar', 6),
(228, 'contact_city', 'varchar', 6),
(229, 'contact_postal', 'varchar', 6),
(230, 'contact_country', 'varchar', 6),
(231, 'contact_telephone', 'varchar', 6),
(232, 'contact_fax', 'varchar', 6),
(233, 'contact_email', 'varchar', 6),
(240, 'imp_role', 'varchar', 6),
(242, 'imp_marketing', 'bool', 6),
(243, 'imp_trade_name', 'varchar', 6),
(244, 'imp_market_holder', 'varchar', 6),
(245, 'imp_market_country', 'varchar', 6),
(246, 'imp_orphan_com', 'bool', 6),
(247, 'imp_orphan_drug', 'varchar', 6),
(248, 'imp_product_name', 'varchar', 6),
(249, 'imp_product_code', 'varchar', 6),
(250, 'imp_product_form', 'varchar', 6),
(251, 'imp_paed_form', 'varchar', 6),
(252, 'imp_admin_route', 'varchar', 6),
(253, 'imp_inn', 'varchar', 6),
(254, 'imp_cas', 'varchar', 6),
(255, 'imp_sponsor', 'varchar', 6),
(256, 'imp_name', 'varchar', 6),
(257, 'imp_ev_code', 'varchar', 6),
(259, 'imp_conc_unit', 'varchar', 6),
(260, 'imp_conc_type', 'varchar', 6),
(261, 'imp_conc_number', 'varchar', 6),
(262, 'imp_chemical_orgin', 'bool', 6),
(263, 'imp_biol_orgin', 'bool', 6),
(264, 'imp_atimp', 'bool', 6),
(265, 'imp_somatic_cell', 'bool', 6),
(266, 'imp_gene_therapy', 'bool', 6),
(267, 'imp_tissue_eng', 'bool', 6),
(268, 'imp_combo_atimp', 'bool', 6),
(270, 'imp_cat', 'bool', 6),
(271, 'imp_therapy', 'bool', 6),
(272, 'imp_radio_mp', 'bool', 6),
(273, 'imp_immuno_mp', 'bool', 6),
(274, 'imp_plasma_mp', 'bool', 6),
(275, 'imp_extract_mp', 'bool', 6),
(276, 'imp_recomb_mp', 'bool', 6),
(277, 'imp_organism_mp', 'bool', 6),
(278, 'imp_herbal_mp', 'bool', 6),
(279, 'imp_homeo_mp', 'bool', 6),
(280, 'imp_another_mp', 'bool', 6),
(281, 'imp_other_mp', 'varchar', 6),
(282, 'placebo_trial', 'bool', 6),
(283, 'placebo_form', 'varchar', 6),
(284, 'placebo_route', 'varchar', 6),
(301, 'trial_being_invest', 'text', 6),
(302, 'trial_laymen', 'text', 6),
(303, 'trial_dra_condition', 'text', 6),
(304, 'trial_dra_version', 'varchar', 6),
(305, 'trial_dra_level', 'varchar', 6),
(306, 'trial_dra_code', 'varchar', 6),
(307, 'trial_dra_term', 'text', 6),
(308, 'trial_dra_class', 'text', 6),
(309, 'trial_rare', 'bool', 6),
(310, 'trial_objective', 'text', 6),
(311, 'trial_sec_objective', 'text', 6),
(312, 'trial_substudy', 'bool', 6),
(313, 'trial_full_title', 'text', 6),
(314, 'trial_inclusion', 'text', 6),
(315, 'trial_exclusion', 'text', 6),
(316, 'trial_primary_end', 'text', 6),
(317, 'trial_primary_timepoint', 'text', 6),
(318, 'trial_secondary_end', 'text', 6),
(319, 'trial_secondary_timepoint', 'text', 6),
(320, 'trial_scope_diagnosis', 'bool', 6),
(321, 'trial_scope_prophylaxis', 'bool', 6),
(322, 'trial_scope_therapy', 'bool', 6),
(323, 'trial_scope_safety', 'bool', 6),
(324, 'trial_scope_efficacy', 'bool', 6),
(325, 'trial_scope_pharmacokinectic', 'bool', 6),
(326, 'trial_scope_pharmacodynamic', 'bool', 6),
(327, 'trial_scope_pharmacoeconomic', 'bool', 6),
(328, 'trial_scope_others', 'bool', 6),
(329, 'trial_scope_other_descr', 'text', 6),
(331, 'trial_type_human_pharma', 'bool', 6),
(332, 'trial_type_human_admin', 'bool', 6),
(333, 'trial_type_bioequivalence', 'bool', 6),
(334, 'trial_type_other', 'bool', 6),
(335, 'trial_type_other_desc', 'text', 6),
(336, 'trial_type_thera_exploratory', 'bool', 6),
(337, 'trial_type_thera_confirm', 'bool', 6),
(338, 'trial_type_thera_use', 'bool', 6),
(339, 'trial_design_controlled', 'bool', 6),
(340, 'trial_design_randomised', 'bool', 6),
(341, 'trial_design_open', 'bool', 6),
(342, 'trial_design_single', 'bool', 6),
(343, 'trial_design_double', 'bool', 6),
(344, 'trial_design_parallel', 'bool', 6),
(345, 'trial_design_cross', 'bool', 6),
(346, 'trial_design_other', 'bool', 6),
(347, 'trial_design_descrip', 'text', 6),
(348, 'trial_compar_mp', 'bool', 6),
(349, 'trial_compar_placebo', 'bool', 6),
(350, 'trial_compar_other', 'bool', 6),
(351, 'trial_compar_descr', 'text', 6),
(352, 'trial_compar_numb_arms', 'varchar', 6),
(353, 'trial_compar_single_site', 'bool', 6),
(354, 'trial_compar_multiple_site', 'bool', 6),
(355, 'trial_compar_numb_anticipated', 'varchar', 6),
(356, 'trial_compar_member_states', 'bool', 6),
(357, 'trial_compar_numb_anticipated_eea', 'varchar', 6),
(358, 'trial_outside_inside', 'bool', 6),
(359, 'trial_outside_completely', 'bool', 6),
(360, 'trial_datamonitor', 'bool', 6),
(361, 'trial_last_subject', 'text', 6),
(363, 'trial_msc_years', 'varchar', 6),
(364, 'trial_msc_months', 'varchar', 6),
(365, 'trial_msc_days', 'varchar', 6),
(366, 'trial_iac_years', 'varchar', 6),
(367, 'trial_iac_months', 'varchar', 6),
(368, 'trial_iac_days', 'varchar', 6),
(400, 'populat_age_have_u18', 'bool', 6),
(401, 'populat_age_numb_u18', 'varchar', 6),
(402, 'populat_age_have_utero', 'bool', 6),
(403, 'populat_age_numb_utero', 'varchar', 6),
(404, 'populat_age_have_preterm', 'bool', 6),
(405, 'populat_age_numb_preterm', 'varchar', 6),
(406, 'populat_age_have_newborn', 'bool', 6),
(407, 'populat_age_numb_newborn', 'varchar', 6),
(408, 'populat_age_have_toddler', 'bool', 6),
(409, 'populat_age_numb_toddler', 'varchar', 6),
(410, 'populat_age_have_children', 'bool', 6),
(411, 'populat_age_numb_children', 'varchar', 6),
(412, 'populat_age_have_adolescent', 'bool', 6),
(413, 'populat_age_numb_adolescent', 'varchar', 6),
(414, 'populat_age_have_adults', 'bool', 6),
(415, 'populat_age_numb_adults', 'varchar', 6),
(416, 'populat_age_have_elderly', 'bool', 6),
(417, 'populat_age_numb_elderly', 'varchar', 6),
(418, 'populat_gender_male', 'bool', 6),
(419, 'populat_gender_female', 'bool', 6),
(420, 'populat_group_healthy', 'bool', 6),
(421, 'populat_group_patients', 'bool', 6),
(422, 'populat_group_vulnerable', 'bool', 6),
(423, 'populat_group_women_noncontr', 'bool', 6),
(424, 'populat_group_women_usecontr', 'bool', 6),
(425, 'populat_group_pregnant', 'bool', 6),
(426, 'populat_group_nursing', 'bool', 6),
(427, 'populat_group_emergency', 'bool', 6),
(428, 'populat_group_incapable_consent', 'bool', 6),
(429, 'populat_group_incapable_consent_details', 'text', 6),
(430, 'populat_group_others', 'bool', 6),
(431, 'populat_group_others_details', 'text', 6),
(432, 'populat_planned_memberstate', 'varchar', 6),
(433, 'populat_planned_multination', 'varchar', 6),
(434, 'populat_planned_eea', 'varchar', 6),
(435, 'populat_planned_wholetrial', 'varchar', 6),
(436, 'populat_planned_after', 'text', 6),
(438, 'populat_investigate_name', 'varchar', 6),
(439, 'populat_investigate_country', 'varchar', 6),
(440, 'populat_committee_third', 'text', 6),
(441, 'populat_committee_country', 'varchar', 6),
(500, 'review_authority', 'varchar', 6),
(501, 'review_authority_decision', 'date', 6),
(502, 'review_ethics_opinion', 'varchar', 6),
(503, 'review_ethics_reason', 'varchar', 6),
(504, 'review_ethics_date', 'date', 6),
(505, 'end_status', 'varchar', 6),
(506, 'end_date', 'date', 6),
(507, 'trial_therapy', 'varchar', 6),
(508, 'trial_scope_bioe', 'bool', 6),
(509, 'trial_scope_dose', 'bool', 6),
(510, 'trial_scope_pharmacogenetic', 'bool', 6),
(511, 'trial_scope_pharmacogenomic', 'bool', 6),
(512, 'competent_authority', 'varchar', 6),
(513, 'clinical_trial_type', 'varchar', 6),
(514, 'trial_status', 'varchar', 6),
(199, 'eudract_id', 'varchar', 6),
(515, 'keyword', 'varchar', 1),
(516, 'study_id_org_name','varchar',1),
(517, 'study_id_org_full_name','varchar',1),
(519, 'is_fda_regulated','bool',1),
(520, 'is_section_801','bool',1),
(521, 'delayed_posting','bool',1),
(522, 'arm_group_other_name','varchar',1),


(700, 'isrctn_id', 'varchar', 7),
(701, 'clinicaltrials_id', 'varchar', 7),
(702, 'public_title', 'varchar', 7),
(703, 'scientific_title', 'varchar', 7),
(704, 'acronym', 'varchar', 7),
(705, 'serial', 'varchar', 7),
(706, 'hypothesis', 'text', 7),
(707, 'lay_summary', 'text', 7),
(708, 'ethics_approval', 'varchar', 7),
(709, 'study_design', 'varchar', 7),

(710, 'countries_recruitment', 'varchar', 7),
(711, 'disease_domain', 'varchar', 7),
(712, 'inclusion_criteria', 'text', 7),
(713, 'exclusion_criteria', 'text', 7),
(714, 'anticipate_start_date', 'date', 7),
(715, 'anticipate_end_date', 'date', 7),
(716, 'status_trial', 'varchar', 7),
(717, 'patient_info', 'varchar', 7),
(718, 'target_number', 'varchar', 7),
(719, 'interventions', 'text', 7),

(720, 'primary_outcome', 'text', 7),
(721, 'secondary_outcome', 'text', 7),
(722, 'funding_source', 'varchar', 7),
(723, 'trial_website', 'varchar', 7),
(724, 'publications', 'varchar', 7),
(725, 'contact_name', 'varchar', 7),
(726, 'contact_address', 'varchar', 7),
(727, 'contact_city', 'varchar', 7),
(728, 'contact_zip', 'varchar', 7),
(729, 'contact_country', 'varchar', 7),

(730, 'contact_tel', 'varchar', 7),
(731, 'contact_fax', 'varchar', 7),
(732, 'contact_email', 'varchar', 7),
(733, 'sponsor_name', 'varchar', 7),
(734, 'sponsor_address', 'varchar', 7),
(735, 'sponsor_city', 'varchar', 7),
(736, 'sponsor_zip', 'varchar', 7),
(737, 'sponsor_country', 'varchar', 7),
(738, 'sponsor_website', 'varchar', 7),
(739, 'sponsor_email', 'varchar', 7),

(740, 'sponsor_tel', 'varchar', 7),
(741, 'sponsor_fax', 'varchar', 7),
(742, 'date_applied', 'date', 7),
(743, 'last_edit', 'date', 7),
(744, 'assigned_date', 'date', 7);


INSERT INTO `data_enumvals` (`id`, `field`, `value`) VALUES
(165, 186, 'AAMC'),
(44, 65, 'Active Comparator'),
(6, 13, 'Active, not recruiting'),
(64, 88, 'Active, not recruiting'),
(15, 18, 'Actual'),
(17, 20, 'Actual'),
(22, 26, 'Actual'),
(166, 186, 'AIDS'),
(16, 18, 'Anticipated'),
(18, 20, 'Anticipated'),
(23, 26, 'Anticipated'),
(182, 13, 'Approved for marketing'),
(158, 184, 'ARPL'),
(11, 13, 'Available'),
(57, 67, 'Behavioral'),
(186, 67, 'Biological'),
(55, 67, 'Biological/Vaccine'),
(31, 32, 'both'),
(49, 65, 'Case'),
(153, 172, 'Cites'),
(162, 184, 'CLML'),
(138, 172, 'CommentIn'),
(137, 172, 'CommentOn'),
(7, 13, 'Completed'),
(65, 88, 'Completed'),
(118, 130, 'Completed'),
(50, 65, 'Control'),
(159, 184, 'CPC'),
(161, 184, 'CPFH'),
(54, 67, 'Device'),
(188, 67, 'Dietary Supplement'),
(53, 67, 'Drug'),
(127, 131, 'Electronic'),
(129, 133, 'Electronic'),
(128, 131, 'Electronic-Print'),
(5, 13, 'Enrolling by invitation'),
(63, 88, 'Enrolling by invitation'),
(140, 172, 'ErratumFor'),
(139, 172, 'ErratumIn'),
(21, 21, 'Expanded Access'),
(43, 65, 'Experimental'),
(52, 65, 'Exposure Comparison'),
(30, 32, 'female'),
(56, 67, 'Genetic'),
(116, 129, 'HMD'),
(181, 197, 'HMD'),
(115, 129, 'HSR'),
(180, 197, 'HSR'),
(121, 130, 'In-Data-Review'),
(119, 130, 'In-Process'),
(160, 184, 'IND'),
(131, 134, 'Internet'),
(19, 21, 'Interventional'),
(136, 151, 'ISO'),
(114, 129, 'KIE'),
(155, 184, 'KIE'),
(167, 186, 'KIE'),
(174, 190, 'KIE'),
(179, 197, 'KIE'),
(29, 32, 'male'),
(123, 130, 'MEDLINE'),
(191, 21, 'N/A'),
(32, 56, 'N/A'),
(112, 129, 'NASA'),
(154, 184, 'NASA'),
(169, 186, 'NASA'),
(172, 190, 'NASA'),
(177, 197, 'NASA'),
(133, 151, 'NCBI'),
(135, 151, 'NISO'),
(111, 129, 'NLM'),
(164, 184, 'NLM'),
(171, 190, 'NLM'),
(176, 197, 'NLM'),
(47, 65, 'No Intervention'),
(12, 13, 'No Longer Available'),
(207, 13, 'No longer recruiting'),
(208, 88, 'No longer recruiting'),
(28, 30, 'Non-Probability Sample'),
(24, 27, 'None Retained'),
(3, 13, 'Not yet recruiting'),
(61, 88, 'Not yet recruiting'),
(117, 129, 'NOTNLM'),
(175, 190, 'NOTNLM'),
(163, 184, 'NRCBL'),
(20, 21, 'Observational'),
(124, 130, 'OLDMEDLINE'),
(150, 172, 'OriginalReportIn'),
(48, 65, 'Other'),
(60, 67, 'Other'),
(141, 172, 'PartialRetractionIn'),
(142, 172, 'PartialRetractionOf'),
(184, 56, 'Phase 0'),
(185, 56, 'Phase 0/Phase 1'),
(200, 56, 'Phase 1'),
(201, 56, 'Phase 1a'),
(202, 56, 'Phase 1b'),
(203, 56, 'Phase 1a/1b'),
(204, 56, 'Phase 1c'),
(205, 56, 'Phase 1/Phase 2'),
(206, 56, 'Phase 1b/2'),
(209, 56, 'Phase 1b/2a'),
(210, 56, 'Phase 2'),
(211, 56, 'Phase 2a'),
(213, 56, 'Phase 2a/2b'),
(214, 56, 'Phase 2a/b'),
(215, 56, 'Phase 2b'),
(216, 56, 'Phase 2/Phase 3'),
(217, 56, 'Phase 2b/3'),
(218, 56, 'Phase 3'),
(219, 56, 'Phase 3a'),
(220, 56, 'Phase 3b'),
(221, 56, 'Phase 3/Phase 4'),
(222, 56, 'Phase 3b/4'),
(223, 56, 'Phase 4'),
(113, 129, 'PIP'),
(156, 184, 'PIP'),
(168, 186, 'PIP'),
(173, 190, 'PIP'),
(178, 197, 'PIP'),
(45, 65, 'Placebo Comparator'),
(157, 184, 'POP'),
(68, 103, 'Principal Investigator'),
(71, 106, 'Principal Investigator'),
(125, 131, 'Print'),
(130, 133, 'Print'),
(132, 134, 'Print'),
(126, 131, 'Print-Electronic'),
(27, 30, 'Probability Sample'),
(187, 67, 'Procedure'),
(122, 130, 'Publisher'),
(134, 151, 'Publisher'),
(170, 186, 'Publisher'),
(120, 130, 'PubMed-not-MEDLINE'),
(58, 67, 'Radiation'),
(4, 13, 'Recruiting'),
(62, 88, 'Recruiting'),
(152, 172, 'ReprintIn'),
(151, 172, 'ReprintOf'),
(143, 172, 'RepublishedFrom'),
(144, 172, 'RepublishedIn'),
(146, 172, 'RetractionIn'),
(145, 172, 'RetractionOf'),
(25, 27, 'Samples With DNA'),
(26, 27, 'Samples Without DNA'),
(46, 65, 'Sham Comparator'),
(194, 103, 'Study Chair'),
(189, 106, 'Study Chair'),
(195, 103, 'Study Director'),
(190, 106, 'Study Director'),
(69, 103, 'Sub-Investigator'),
(72, 106, 'Sub-Investigator'),
(149, 172, 'SummaryForPatientsIn'),
(8, 13, 'Suspended'),
(66, 88, 'Suspended'),
(193, 13, 'Temporarily not available'),
(9, 13, 'Terminated'),
(67, 88, 'Terminated'),
(51, 65, 'Treatment Comparison'),
(147, 172, 'UpdateIn'),
(148, 172, 'UpdateOf'),
(10, 13, 'Withdrawn'),
(192, 88, 'Withdrawn'),
(183, 13, 'Withheld'),
(307, 67, 'Behavior'),
(308, 67, 'Vaccine'),
(309, 67, 'Gene Transfer'),
(310, 67, 'Therapy');





INSERT INTO `settings` (`name`, `value`) VALUES
('results_per_page', '50');

INSERT INTO `users` (`id`, `username`, `password`, `fingerprint`, `email`, `userlevel`) VALUES
(1, 'root', 'aa2a8ecbebcc6d8113fd12ea4e9d96f5999f4f9e44f8e12f', NULL, 'root@example.com', 'root'),
(2, 'user', '290799c2acc02a8de9a714868f7ae14003ea57d05b07c596', NULL, 'user@example.com', 'admin');

INSERT INTO `user_permissions` (`id`, `name`, `type`, `level`) VALUES
(1, 'search', 'readonly', 1),
(2, 'heatmap', 'readonly', 1),
(3, 'heatmap', 'contained', 2),
(4, 'heatmap', 'admin', 3),
(5, 'heatmap', 'admin', 4),
(10, 'trial_tracker', 'readonly', 1),
(11, 'trial_tracker', 'contained', 2),
(12, 'trial_tracker', 'admin', 3),
(13, 'trial_tracker', 'admin', 4),
(14, 'update_scan', 'readonly', 1),
(15, 'update_scan', 'contained', 2),
(16, 'update_scan', 'admin', 3),
(17, 'update_scan', 'admin', 4),
(18, 'xml_import', 'editing', 1),
(19, 'user_management', 'admin', 1),
(20, 'field_editor', 'admin', 1),
(21, 'field_editor', 'core', 2),
(22, 'scheduler', 'contained', 1),
(23, 'scheduler', 'admin', 2),
(24, 'scheduler', 'admin', 3),
(25, 'editing', 'editing', 1),
(26, 'editing', 'editing', 2),
(27, 'editing', 'editing', 3),
(28, 'editing', 'core', 4),
(29, 'settings', 'admin', 1);