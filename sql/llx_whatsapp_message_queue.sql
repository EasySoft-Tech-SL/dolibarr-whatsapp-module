-- Copyright (C) 2025 Alberto Luque Rivas <aluquerivasdev@gmail.com>
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


CREATE TABLE llx_whatsapp_message_queue(
	rowid           integer AUTO_INCREMENT PRIMARY KEY NOT NULL,
	ref             varchar(128)  NOT NULL,
	entity          integer       DEFAULT 1 NOT NULL,
	recipient_phone varchar(32)   NOT NULL,
	recipient_name  varchar(255)  NULL,
	message_type    varchar(16)   DEFAULT 'text' NOT NULL,
	message_content text          NULL,
	media_path      varchar(512)  NULL,
	media_filename  varchar(255)  NULL,
	media_mediatype varchar(32)   NULL,
	instance        varchar(255)  NULL,
	priority        smallint      DEFAULT 50 NOT NULL,
	date_scheduled  datetime      NULL DEFAULT NULL,
	status          smallint      DEFAULT 0 NOT NULL,
	retry_count     smallint      DEFAULT 0 NOT NULL,
	max_retries     smallint      NULL DEFAULT NULL,
	last_error      varchar(255)  NULL DEFAULT NULL,
	http_code       integer       NULL DEFAULT NULL,
	next_retry_date datetime      NULL DEFAULT NULL,
	date_processing datetime      NULL DEFAULT NULL,
	date_sent       datetime      NULL DEFAULT NULL,
	source_module   varchar(64)   NULL DEFAULT NULL,
	fk_soc          integer       NULL DEFAULT NULL,
	fk_contact      integer       NULL DEFAULT NULL,
	fk_actioncomm   integer       NULL DEFAULT NULL,
	fk_user_creat   integer       NULL DEFAULT NULL,
	fk_user_modif   integer       NULL DEFAULT NULL,
	date_creation   datetime      NOT NULL,
	tms             timestamp     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	import_key      varchar(14)   NULL DEFAULT NULL
) ENGINE=innodb DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
