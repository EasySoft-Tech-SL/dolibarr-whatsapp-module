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


ALTER TABLE llx_whatsapp_message_queue ADD INDEX idx_whatsapp_message_queue_rowid (rowid);
ALTER TABLE llx_whatsapp_message_queue ADD INDEX idx_whatsapp_message_queue_ref (ref);
ALTER TABLE llx_whatsapp_message_queue ADD INDEX idx_whatsapp_message_queue_status (status);
ALTER TABLE llx_whatsapp_message_queue ADD INDEX idx_whatsapp_message_queue_fk_soc (fk_soc);
-- Composite index used by the queue processor to pick the next sendable rows fast
ALTER TABLE llx_whatsapp_message_queue ADD INDEX idx_whatsapp_message_queue_sendable (status, date_scheduled, next_retry_date);
