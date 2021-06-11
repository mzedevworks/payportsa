ALTER TABLE `payments` CHANGE `amount` `amount` INT NULL DEFAULT '0';

ALTER TABLE `payment_transmission_records` CHANGE `transmission_number` `transmission_number` VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL, CHANGE `transmission_status` `transmission_status` VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL, CHANGE `generation_number` `generation_number` VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL, CHANGE `sequence_number` `sequence_number` VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL, CHANGE `user_set_status` `user_set_status` VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL, CHANGE `combined_status` `combined_status` VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;
ALTER TABLE `transmission_records` CHANGE `transmission_number` `transmission_number` VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL, CHANGE `transmission_status` `transmission_status` VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL, CHANGE `generation_number` `generation_number` VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL, CHANGE `sequence_number` `sequence_number` VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL, CHANGE `user_set_status` `user_set_status` VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL, CHANGE `combined_status` `combined_status` VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;

CREATE TABLE `avs_enquiries` (
  `id` int(11) NOT NULL,
  `firm_id` int(11) NOT NULL,
  `avs_type` enum('individual','business') NOT NULL,
  `beneficiary_id_number` varchar(200) DEFAULT NULL COMMENT 'company reg number',
  `beneficiary_initial` varchar(50) DEFAULT NULL,
  `beneficiary_last_name` varchar(100) DEFAULT NULL COMMENT 'company_name',
  `bank_name` varchar(150) DEFAULT NULL,
  `branch_code` varchar(50) DEFAULT NULL,
  `bank_account_number` varchar(50) DEFAULT NULL,
  `bank_account_type` varchar(50) DEFAULT NULL,
  `avs_status` enum('pending','sucessful','failed','rejected') DEFAULT NULL,
  `sequence_number` int(11) DEFAULT NULL,
  `user_set_number` int(11) DEFAULT NULL,
  `avs_json_result` text,
  `created_on` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `creation_type` enum('single','batch') NOT NULL DEFAULT 'single'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `avs_enquiries`
--
ALTER TABLE `avs_enquiries`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `avs_enquiries`
--
ALTER TABLE `avs_enquiries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;



CREATE TABLE `avs_transmission_records` (
  `id` int(11) NOT NULL,
  `transmission_number` varchar(50) DEFAULT NULL,
  `transmission_status` varchar(50) DEFAULT NULL,
  `generation_number` varchar(50) DEFAULT NULL,
  `sequence_number` varchar(50) DEFAULT NULL,
  `user_set_status` varchar(50) DEFAULT NULL,
  `combined_status` varchar(50) DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `transmission_date` date NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `avs_transmission_records`
--
ALTER TABLE `avs_transmission_records`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `avs_transmission_records`
--
ALTER TABLE `avs_transmission_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

CREATE TABLE `temporary_avs_enquiries` (
  `id` int(10) UNSIGNED NOT NULL,
  `avs_type` enum('individual','business') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dataset` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `errorset` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `added_by` int(11) NOT NULL,
  `file_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_deleted` int(11) NOT NULL DEFAULT '0',
  `deleted_by` int(11) DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `temporary_avs_enquiries`
--
ALTER TABLE `temporary_avs_enquiries`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `temporary_avs_enquiries`
--
ALTER TABLE `temporary_avs_enquiries`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
COMMIT;

CREATE TABLE `avs_batches` (
  `id` int(11) NOT NULL,
  `firm_id` int(11) NOT NULL,
  `batch_name` varchar(200) NOT NULL,
  `batch_type` enum('individual','business') NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `status` enum('pending','sent','processed','') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `avs_batches`
--


--
-- Indexes for dumped tables
--

--
-- Indexes for table `avs_batches`
--
ALTER TABLE `avs_batches`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `avs_batches`
--
ALTER TABLE `avs_batches`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
COMMIT;


DROP TABLE IF EXISTS `avs_enquiries`;
CREATE TABLE `avs_enquiries` (
  `id` int(11) NOT NULL,
  `firm_id` int(11) NOT NULL,
  `avs_batch_id` int(11) DEFAULT NULL,
  `avs_type` enum('individual','business') NOT NULL,
  `beneficiary_id_number` varchar(200) DEFAULT NULL COMMENT 'company reg number',
  `beneficiary_initial` varchar(50) DEFAULT NULL,
  `beneficiary_last_name` varchar(100) DEFAULT NULL COMMENT 'company_name',
  `bank_name` varchar(150) DEFAULT NULL,
  `branch_code` varchar(50) DEFAULT NULL,
  `bank_account_number` varchar(50) DEFAULT NULL,
  `bank_account_type` varchar(50) DEFAULT NULL,
  `avs_status` enum('pending','sucessful','failed','rejected') DEFAULT NULL,
  `sequence_number` int(11) DEFAULT NULL,
  `user_set_number` int(11) DEFAULT NULL,
  `avs_json_result` text,
  `created_on` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `creation_type` enum('single','batch') NOT NULL DEFAULT 'single'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `avs_enquiries`
--
ALTER TABLE `avs_enquiries`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `avs_enquiries`
--
ALTER TABLE `avs_enquiries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;


ALTER TABLE `avs_enquiries`  ADD `avs_transmission_id` INT NULL  AFTER `avs_batch_id`;

ALTER TABLE `avs_enquiries`  ADD `avs_reffrence` VARCHAR(100) NULL  AFTER `avs_status`,  ADD `avs_transmission_number` VARCHAR(50) NULL COMMENT 'as per absa '  AFTER `avs_reffrence`;

ALTER TABLE `avs_enquiries`  ADD `transmission_status` INT NOT NULL DEFAULT '0' COMMENT '0=pending,1=transmitted,2=accepted,3=rejected'  AFTER `sequence_number`,  ADD `transaction_status` INT NULL COMMENT '0=pending,1=sucess,2=failed, 3=dispute status of actual tranmission '  AFTER `transmission_status`;
DROP TABLE IF EXISTS `avs_transmission_errors`;
CREATE TABLE `avs_transmission_errors` (
  `id` int(11) UNSIGNED NOT NULL,
  `transmission_record_id` int(11) UNSIGNED NOT NULL,
  `avs_enquiry_id` int(11) DEFAULT NULL,
  `error_code` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `error_message` varchar(300) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `avs_transmission_errors`
--
ALTER TABLE `avs_transmission_errors`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `avs_transmission_errors`
--
ALTER TABLE `avs_transmission_errors`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;
COMMIT;

ALTER TABLE `tempory_employee`  ADD `upload_type` ENUM('salaried','creditor') NOT NULL  AFTER `file_name`;


ALTER TABLE `bank_details`  ADD `avs_bank_code`  INT(10) NULL DEFAULT NULL  AFTER `is_cheque`,  ADD `is_realtime_avs` ENUM('yes','no') NOT NULL DEFAULT 'no'  AFTER `avs_bank_code`,  ADD `is_batch_avs` ENUM('yes','no') NOT NULL DEFAULT 'no'  AFTER `is_realtime_avs`;


ALTER TABLE `batches` CHANGE `batch_status` `batch_status` ENUM('pending','approved','sent','processed','cancelled') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'pending,approved,sent,processed,cancelled';

ALTER TABLE `batches`  ADD `created_by` INT NULL DEFAULT NULL  AFTER `batch_status`;

--
ALTER TABLE `collections`  ADD `date_of_failure` DATETIME NULL  AFTER `tranx_error_id`;

CREATE TABLE `output_files` ( `id` INT NOT NULL AUTO_INCREMENT ,  `file_type` ENUM('collection','payment') NOT NULL ,  `output_file_path` VARCHAR(300) NOT NULL ,  `receiving_date` DATETIME NOT NULL ,    PRIMARY KEY  (`id`)) ENGINE = InnoDB;
ALTER TABLE `output_files`  ADD `transaction_count` INT NULL  AFTER `receiving_date`,  ADD `transaction_amount` INT NULL  AFTER `transaction_count`;
CREATE TABLE `output_file_transactions` ( `id` INT NOT NULL AUTO_INCREMENT ,  `output_file_id` INT NOT NULL ,  `target_transaction_id` INT NOT NULL COMMENT 'either collection id or payment id' ,  `tranx_amount` INT NOT NULL ,    PRIMARY KEY  (`id`)) ENGINE = InnoDB;

--

ALTER TABLE `customers`  ADD `action_date_choice` ENUM('pre','post') NULL DEFAULT 'pre'  AFTER `recurring_start_date`;
--

ALTER TABLE `transmission_records`  ADD `reply_file` VARCHAR(150) NULL  AFTER `transmission_date`,  ADD `reason_of_fail` VARCHAR(400) NULL  AFTER `reply_file`,  ADD `error_code` VARCHAR(10) NULL  AFTER `reason_of_fail`;
ALTER TABLE `payment_transmission_records`  ADD `reply_file` VARCHAR(150) NULL  AFTER `transmission_date`,  ADD `reason_of_fail` VARCHAR(400) NULL  AFTER `reply_file`,  ADD `error_code` VARCHAR(10) NULL  AFTER `reason_of_fail`