SET FOREIGN_KEY_CHECKS = 0;

UPDATE `config` SET `value`='2.6.1' WHERE `name`='thelia_version';
UPDATE `config` SET `value`='2' WHERE `name`='thelia_major_version';
UPDATE `config` SET `value`='6' WHERE `name`='thelia_minus_version';
UPDATE `config` SET `value`='1' WHERE `name`='thelia_release_version';
UPDATE `config` SET `value`='' WHERE `name`='thelia_extra_version';

# ======================================================================================================================
# Add value IN config_i18n
# ======================================================================================================================

ALTER TABLE config_i18n ADD COLUMN `value` TEXT NOT NULL AFTER `locale`;

UPDATE config_i18n AS dest
INNER JOIN config AS src ON dest.id = src.id
SET dest.value = src.value;

INSERT INTO `config_i18n` (`id`, `locale`, `value`)
SELECT c.`id`, l.`locale`, c.`value`
FROM `config` c
JOIN `lang` l
LEFT JOIN `config_i18n` ci
ON ci.`id` = c.`id` AND ci.`locale` = l.`locale`
WHERE ci.`id` IS NULL;

ALTER TABLE config DROP COLUMN `value`;

# ======================================================================================================================
# Add file IN product_image_i18n
# ======================================================================================================================

ALTER TABLE product_image_i18n ADD COLUMN `file` VARCHAR(255) NOT NULL AFTER `locale`;

UPDATE product_image_i18n AS dest
INNER JOIN product_image AS src ON dest.id = src.id
SET dest.file = src.file;

ALTER TABLE product_image DROP COLUMN `file`;

# ======================================================================================================================
# Add file IN category_image_i18n
# ======================================================================================================================

ALTER TABLE category_image_i18n ADD COLUMN `file` VARCHAR(255) NOT NULL AFTER `locale`;

UPDATE category_image_i18n AS dest
INNER JOIN category_image AS src ON dest.id = src.id
SET dest.file = src.file;

ALTER TABLE category_image DROP COLUMN `file`;

# ======================================================================================================================
# Add file IN folder_image_i18n
# ======================================================================================================================

ALTER TABLE folder_image_i18n ADD COLUMN `file` VARCHAR(255) NOT NULL AFTER `locale`;

UPDATE folder_image_i18n AS dest
    INNER JOIN folder_image AS src ON dest.id = src.id
    SET dest.file = src.file;

ALTER TABLE folder_image DROP COLUMN `file`;

# ======================================================================================================================
# Add file IN content_image_i18n
# ======================================================================================================================

ALTER TABLE content_image_i18n ADD COLUMN `file` VARCHAR(255) NOT NULL AFTER `locale`;

UPDATE content_image_i18n AS dest
INNER JOIN content_image AS src ON dest.id = src.id
SET dest.file = src.file;

ALTER TABLE content_image DROP COLUMN `file`;

# ======================================================================================================================
# Add file IN module_image_i18n
# ======================================================================================================================

ALTER TABLE module_image_i18n ADD COLUMN `file` VARCHAR(255) NOT NULL AFTER `locale`;

UPDATE module_image_i18n AS dest
    INNER JOIN module_image AS src ON dest.id = src.id
    SET dest.file = src.file;

ALTER TABLE module_image DROP COLUMN `file`;

# ======================================================================================================================
# Add file IN brand_image_i18n
# ======================================================================================================================

ALTER TABLE brand_image_i18n ADD COLUMN `file` VARCHAR(255) NOT NULL AFTER `locale`;

UPDATE brand_image_i18n AS dest
    INNER JOIN brand_image AS src ON dest.id = src.id
    SET dest.file = src.file;

ALTER TABLE brand_image DROP COLUMN `file`;

# ======================================================================================================================
# End of changes
# ======================================================================================================================


SET FOREIGN_KEY_CHECKS = 1;
