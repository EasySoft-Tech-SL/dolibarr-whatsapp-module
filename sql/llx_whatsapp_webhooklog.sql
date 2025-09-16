-- Copyright (C) 2025 Alberto SuperAdmin <aluquerivasdev@gmail.com>
--
-- This program is free software: you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation, either version 3 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program.  If not, see https://www.gnu.org/licenses/.


CREATE TABLE llx_whatsapp_webhooklog(
	-- BEGIN MODULEBUILDER FIELDS
	rowid integer AUTO_INCREMENT PRIMARY KEY NOT NULL, 
	event VARCHAR(255) NOT NULL,
	instance VARCHAR(255) NOT NULL,
	data LONGTEXT NULL DEFAULT NULL COLLATE 'utf8mb4_bin',
	destination VARCHAR(255) NULL DEFAULT NULL,
	date_time DATETIME NULL DEFAULT NULL,
	sender VARCHAR(255) NULL DEFAULT NULL,
	server_url VARCHAR(255) NULL DEFAULT NULL,
	apikey VARCHAR(255) NULL DEFAULT NULL,
	timestamp DATETIME NULL DEFAULT NULL,
	INDEX idx_event (event) USING BTREE,
	INDEX idx_instance (instance) USING BTREE,
	INDEX idx_sender (sender) USING BTREE,
	INDEX idx_timestamp (timestamp) USING BTREE,
	CONSTRAINT data CHECK (json_valid(data))
) ENGINE=innodb;
