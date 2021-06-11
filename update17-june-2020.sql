ALTER TABLE `collections`  ADD `collection_for` TINYINT NOT NULL DEFAULT '1' COMMENT '1=for merchants,2=for platform'  AFTER `customer_id`;
ALTER TABLE `firms`  ADD `setup_fee` FLOAT NULL DEFAULT '0'  AFTER `account_number`,  
ADD `setup_collection_date` DATE NULL  AFTER `setup_fee`,  
ADD `monthly_fee` FLOAT NULL DEFAULT '0'  AFTER `setup_collection_date`,  ADD `monthly_collection_date` DATE NULL  AFTER `monthly_fee`;
ALTER TABLE `firms`  ADD `next_collection_date` DATE NULL  AFTER `monthly_collection_date`;