-- Add KYC verification columns to college_accounts
ALTER TABLE `college_accounts`
  ADD COLUMN `designation` varchar(100) DEFAULT NULL AFTER `contact_person`,
  ADD COLUMN `website` varchar(255) DEFAULT NULL AFTER `phone`,
  ADD COLUMN `state_id` int(11) DEFAULT NULL AFTER `website`,
  ADD COLUMN `city` varchar(100) DEFAULT NULL AFTER `state_id`,
  ADD COLUMN `established_year` year DEFAULT NULL AFTER `city`,
  ADD COLUMN `affiliation_details` text DEFAULT NULL AFTER `established_year`,
  ADD COLUMN `pan_number` varchar(20) DEFAULT NULL AFTER `affiliation_details`,
  ADD COLUMN `aadhar_number` varchar(20) DEFAULT NULL AFTER `pan_number`,
  ADD COLUMN `gst_number` varchar(30) DEFAULT NULL AFTER `aadhar_number`,
  ADD COLUMN `pan_doc` varchar(255) DEFAULT NULL AFTER `gst_number`,
  ADD COLUMN `aadhar_doc` varchar(255) DEFAULT NULL AFTER `pan_doc`,
  ADD COLUMN `gst_doc` varchar(255) DEFAULT NULL AFTER `aadhar_doc`,
  ADD COLUMN `affiliation_doc` varchar(255) DEFAULT NULL AFTER `gst_doc`;
