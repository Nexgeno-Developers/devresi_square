-- The app writes these property columns; they were never in a migration.
-- phpMyAdmin: select the site database, then run this in the SQL tab.
-- If a column already exists, skip that line and continue.

ALTER TABLE `properties` ADD `epc_required` tinyint(1) NULL;
ALTER TABLE `properties` ADD `youtube_url` text NULL;
ALTER TABLE `properties` ADD `instagram_url` text NULL;
ALTER TABLE `properties` ADD `nearest_places` longtext NULL;
