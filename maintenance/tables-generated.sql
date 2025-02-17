-- This file is automatically generated using maintenance/generateSchemaSql.php.
-- Source: maintenance/tables.json
-- Do not modify this file directly.
-- See https://www.mediawiki.org/wiki/Manual:Schema_changes
CREATE TABLE /*_*/site_identifiers (
  si_type VARBINARY(32) NOT NULL,
  si_key VARBINARY(32) NOT NULL,
  si_site INT UNSIGNED NOT NULL,
  INDEX site_ids_site (si_site),
  INDEX site_ids_key (si_key),
  PRIMARY KEY(si_type, si_key)
) /*$wgDBTableOptions*/;

CREATE TABLE /*_*/updatelog (
  ul_key VARCHAR(255) NOT NULL,
  ul_value BLOB DEFAULT NULL,
  PRIMARY KEY(ul_key)
) /*$wgDBTableOptions*/;
