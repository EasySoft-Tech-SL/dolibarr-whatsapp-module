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

ALTER TABLE llx_whatsapp_reminder_schedule ADD INDEX idx_wa_rs_fk_actioncomm (fk_actioncomm);
ALTER TABLE llx_whatsapp_reminder_schedule ADD INDEX idx_wa_rs_reminder_date_status (reminder_date, status);
ALTER TABLE llx_whatsapp_reminder_schedule ADD INDEX idx_wa_rs_fk_webhook_log (fk_webhook_log);
ALTER TABLE llx_whatsapp_reminder_schedule ADD CONSTRAINT fk_wa_rs_actioncomm FOREIGN KEY (fk_actioncomm) REFERENCES llx_actioncomm(id) ON DELETE CASCADE;
