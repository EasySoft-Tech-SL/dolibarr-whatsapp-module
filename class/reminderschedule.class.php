<?php
/* Copyright (C) 2025 Alberto SuperAdmin <aluquerivasdev@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';

/**
 * Class ReminderSchedule - Manages scheduled WhatsApp reminder dates
 */
class ReminderSchedule extends CommonObject
{
	public $table_element = 'whatsapp_reminder_schedule';
	public $element = 'reminderschedule';

	// Status constants
	const STATUS_DISABLED = 0;
	const STATUS_PENDING = 1;
	const STATUS_SENT = 2;
	const STATUS_ERROR = 3;

	public $rowid;
	public $fk_actioncomm;
	public $reminder_date;
	public $status;
	public $date_sent;
	public $error_msg;
	public $retry_count;
	public $fk_webhook_log;
	public $date_creation;
	public $tms;

	/**
	 * Constructor
	 * @param DoliDB $db Database handler
	 */
	public function __construct(DoliDB $db)
	{
		$this->db = $db;
	}

	/**
	 * Create a schedule entry
	 * @param User $user User creating
	 * @return int >0 if OK, <0 if KO
	 */
	public function create($user)
	{
		$now = dol_now();
		$sql = "INSERT INTO " . MAIN_DB_PREFIX . $this->table_element . " (";
		$sql .= "fk_actioncomm, reminder_date, status, date_creation";
		$sql .= ") VALUES (";
		$sql .= " " . ((int) $this->fk_actioncomm) . ",";
		$sql .= " '" . $this->db->idate($this->reminder_date) . "',";
		$sql .= " " . ((int) $this->status) . ",";
		$sql .= " '" . $this->db->idate($now) . "'";
		$sql .= ")";

		$resql = $this->db->query($sql);
		if ($resql) {
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX . $this->table_element);
			$this->rowid = $this->id;
			return $this->id;
		} else {
			$this->error = $this->db->lasterror();
			return -1;
		}
	}

	/**
	 * Fetch a schedule entry
	 * @param int $id Row ID
	 * @return int >0 if OK, <0 if KO
	 */
	public function fetch($id)
	{
		$sql = "SELECT rowid, fk_actioncomm, reminder_date, status, date_sent, error_msg, retry_count, fk_webhook_log, date_creation, tms";
		$sql .= " FROM " . MAIN_DB_PREFIX . $this->table_element;
		$sql .= " WHERE rowid = " . ((int) $id);

		$resql = $this->db->query($sql);
		if ($resql) {
			$obj = $this->db->fetch_object($resql);
			if ($obj) {
				$this->id = $obj->rowid;
				$this->rowid = $obj->rowid;
				$this->fk_actioncomm = $obj->fk_actioncomm;
				$this->reminder_date = $this->db->jdate($obj->reminder_date);
				$this->status = $obj->status;
				$this->date_sent = $this->db->jdate($obj->date_sent);
				$this->error_msg = $obj->error_msg;
				$this->retry_count = (int) $obj->retry_count;
				$this->fk_webhook_log = $obj->fk_webhook_log ? (int) $obj->fk_webhook_log : null;
				$this->date_creation = $this->db->jdate($obj->date_creation);
				$this->tms = $this->db->jdate($obj->tms);
				return 1;
			}
			return 0;
		}
		$this->error = $this->db->lasterror();
		return -1;
	}

	/**
	 * Update status of a schedule entry
	 * @param int $status New status
	 * @param string $error_msg Error message (optional)
	 * @return int >0 if OK, <0 if KO
	 */
	public function updateStatus($status, $error_msg = '')
	{
		$sql = "UPDATE " . MAIN_DB_PREFIX . $this->table_element;
		$sql .= " SET status = " . ((int) $status);
		if ($status == self::STATUS_SENT) {
			$sql .= ", date_sent = '" . $this->db->idate(dol_now()) . "'";
		}
		if (!empty($error_msg)) {
			$sql .= ", error_msg = '" . $this->db->escape(dol_trunc($error_msg, 250)) . "'";
		}
		if ($status == self::STATUS_ERROR) {
			$sql .= ", retry_count = retry_count + 1";
		}
		$sql .= " WHERE rowid = " . ((int) $this->id);

		$resql = $this->db->query($sql);
		if ($resql) {
			$this->status = $status;
			return 1;
		}
		$this->error = $this->db->lasterror();
		return -1;
	}

	/**
	 * Delete a schedule entry
	 * @return int >0 if OK, <0 if KO
	 */
	public function delete()
	{
		$sql = "DELETE FROM " . MAIN_DB_PREFIX . $this->table_element;
		$sql .= " WHERE rowid = " . ((int) $this->id);

		$resql = $this->db->query($sql);
		if ($resql) {
			return 1;
		}
		$this->error = $this->db->lasterror();
		return -1;
	}

	/**
	 * Get all schedule entries for an actioncomm
	 * @param int $fk_actioncomm ActionComm ID
	 * @return array|int Array of ReminderSchedule objects or -1 if KO
	 */
	public function fetchAllByActionComm($fk_actioncomm)
	{
		$results = array();
		$sql = "SELECT rowid, fk_actioncomm, reminder_date, status, date_sent, error_msg, retry_count, fk_webhook_log, date_creation, tms";
		$sql .= " FROM " . MAIN_DB_PREFIX . $this->table_element;
		$sql .= " WHERE fk_actioncomm = " . ((int) $fk_actioncomm);
		$sql .= " ORDER BY reminder_date ASC";

		$resql = $this->db->query($sql);
		if ($resql) {
			while ($obj = $this->db->fetch_object($resql)) {
				$line = new self($this->db);
				$line->id = $obj->rowid;
				$line->rowid = $obj->rowid;
				$line->fk_actioncomm = $obj->fk_actioncomm;
				$line->reminder_date = $this->db->jdate($obj->reminder_date);
				$line->status = $obj->status;
				$line->date_sent = $this->db->jdate($obj->date_sent);
				$line->error_msg = $obj->error_msg;
				$line->retry_count = (int) $obj->retry_count;
				$line->fk_webhook_log = $obj->fk_webhook_log ? (int) $obj->fk_webhook_log : null;
				$line->date_creation = $this->db->jdate($obj->date_creation);
				$line->tms = $this->db->jdate($obj->tms);
				$results[] = $line;
			}
			return $results;
		}
		$this->error = $this->db->lasterror();
		return -1;
	}

	/**
	 * Get pending schedules ready to be sent
	 * @return array|int Array of objects or -1 if KO
	 */
	public function fetchPendingToSend()
	{
		$now = dol_now();
		$results = array();

		$sql = "SELECT rs.rowid as schedule_id, rs.fk_actioncomm, rs.reminder_date";
		$sql .= " FROM " . MAIN_DB_PREFIX . $this->table_element . " rs";
		$sql .= " JOIN " . MAIN_DB_PREFIX . "actioncomm ac ON ac.id = rs.fk_actioncomm";
		$sql .= " WHERE rs.status = " . self::STATUS_PENDING;
		$sql .= " AND rs.reminder_date <= '" . $this->db->idate($now) . "'";
		$sql .= " AND ac.code = 'AC_SEND_WHATSAPP_NOTIFY'";
		$sql .= " AND ac.entity IN (" . getEntity('actioncomm') . ")";
		$sql .= " ORDER BY rs.reminder_date ASC";

		$resql = $this->db->query($sql);
		if ($resql) {
			while ($obj = $this->db->fetch_object($resql)) {
				$results[] = $obj;
			}
			return $results;
		}
		$this->error = $this->db->lasterror();
		return -1;
	}

	/**
	 * Delete all pending (not yet sent) schedule entries for an actioncomm
	 * @param int $fk_actioncomm ActionComm ID
	 * @return int Number of deleted rows or -1 if KO
	 */
	public function deletePendingByActionComm($fk_actioncomm)
	{
		$sql = "DELETE FROM " . MAIN_DB_PREFIX . $this->table_element;
		$sql .= " WHERE fk_actioncomm = " . ((int) $fk_actioncomm);
		$sql .= " AND status = " . self::STATUS_PENDING;

		$resql = $this->db->query($sql);
		if ($resql) {
			return $this->db->affected_rows($resql);
		}
		$this->error = $this->db->lasterror();
		return -1;
	}

	/**
	 * Generate recurrent schedule dates
	 *
	 * @param int    $fk_actioncomm  ActionComm ID
	 * @param int    $baseDate       Base date timestamp
	 * @param int    $frequency      Frequency number (e.g., 1, 2, 6)
	 * @param string $unit           Frequency unit: 'week', 'month', 'year'
	 * @param int    $occurrences    Number of occurrences to generate
	 * @param User   $user           User object
	 * @return int Number of entries created or -1 if KO
	 */
	public function generateSchedules($fk_actioncomm, $baseDate, $frequency, $unit, $occurrences, $user)
	{
		$created = 0;
		$error = 0;

		$this->db->begin();

		for ($i = 0; $i < $occurrences; $i++) {
			$date = $this->calculateDate($baseDate, $frequency, $unit, $i);

			$schedule = new self($this->db);
			$schedule->fk_actioncomm = $fk_actioncomm;
			$schedule->reminder_date = $date;
			$schedule->status = self::STATUS_PENDING;

			$result = $schedule->create($user);
			if ($result > 0) {
				$created++;
			} else {
				$error++;
				$this->error = $schedule->error;
				break;
			}
		}

		if ($error) {
			$this->db->rollback();
			return -1;
		}

		$this->db->commit();
		return $created;
	}

	/**
	 * Calculate a date based on base + (frequency * unit * index)
	 *
	 * @param int    $baseDate   Base timestamp
	 * @param int    $frequency  Frequency number
	 * @param string $unit       'week', 'month', 'year'
	 * @param int    $index      Occurrence index (0-based)
	 * @return int Timestamp
	 */
	private function calculateDate($baseDate, $frequency, $unit, $index)
	{
		$modifier = $frequency * $index;
		if ($modifier == 0) {
			return $baseDate;
		}

		$dateObj = new DateTime();
		$dateObj->setTimestamp($baseDate);

		switch ($unit) {
			case 'week':
				$dateObj->modify('+' . ($modifier * 7) . ' days');
				break;
			case 'month':
				$dateObj->modify('+' . $modifier . ' months');
				break;
			case 'year':
				$dateObj->modify('+' . $modifier . ' years');
				break;
		}

		return $dateObj->getTimestamp();
	}

	/**
	 * Get status label
	 * @param int $status Status code
	 * @return string Translated label
	 */
	public static function getStatusLabel($status)
	{
		global $langs;
		$langs->load('whatsapp@whatsapp');
		switch ((int) $status) {
			case self::STATUS_DISABLED:
				return $langs->trans('WhatsappScheduleDisabled');
			case self::STATUS_PENDING:
				return $langs->trans('WhatsappSchedulePending');
			case self::STATUS_SENT:
				return $langs->trans('WhatsappScheduleSent');
			case self::STATUS_ERROR:
				return $langs->trans('WhatsappScheduleError');
			default:
				return $langs->trans('Unknown');
		}
	}

	/**
	 * Get status CSS badge class
	 * @param int $status Status code
	 * @return string CSS class
	 */
	public static function getStatusBadgeClass($status)
	{
		switch ((int) $status) {
			case self::STATUS_DISABLED:
				return 'badge badge-status4'; // grey
			case self::STATUS_PENDING:
				return 'badge badge-status1'; // yellow/orange
			case self::STATUS_SENT:
				return 'badge badge-status6'; // green
			case self::STATUS_ERROR:
				return 'badge badge-status8'; // red
			default:
				return 'badge badge-status0';
		}
	}

	/**
	 * Delete all modifiable (pending + disabled) schedule entries for an actioncomm
	 * Keeps sent and error entries as history.
	 * @param int $fk_actioncomm ActionComm ID
	 * @return int Number of deleted rows or -1 if KO
	 */
	public function deleteModifiableByActionComm($fk_actioncomm)
	{
		$sql = "DELETE FROM " . MAIN_DB_PREFIX . $this->table_element;
		$sql .= " WHERE fk_actioncomm = " . ((int) $fk_actioncomm);
		$sql .= " AND status IN (" . self::STATUS_PENDING . ", " . self::STATUS_DISABLED . ")";

		$resql = $this->db->query($sql);
		if ($resql) {
			return $this->db->affected_rows($resql);
		}
		$this->error = $this->db->lasterror();
		return -1;
	}

	/**
	 * Update the webhook log reference for this schedule
	 * @param int $fk_webhook_log WebhookLog ID
	 * @return int >0 if OK, <0 if KO
	 */
	public function updateWebhookLog($fk_webhook_log)
	{
		$sql = "UPDATE " . MAIN_DB_PREFIX . $this->table_element;
		$sql .= " SET fk_webhook_log = " . ((int) $fk_webhook_log);
		$sql .= " WHERE rowid = " . ((int) $this->id);

		$resql = $this->db->query($sql);
		if ($resql) {
			$this->fk_webhook_log = (int) $fk_webhook_log;
			return 1;
		}
		$this->error = $this->db->lasterror();
		return -1;
	}

	/**
	 * Fetch error schedules eligible for retry
	 * @param int $maxRetries Maximum number of retries allowed
	 * @return array|int Array of objects or -1 if KO
	 */
	public function fetchRetryable($maxRetries = 3)
	{
		$now = dol_now();
		$results = array();

		$sql = "SELECT rs.rowid as schedule_id, rs.fk_actioncomm, rs.reminder_date, rs.retry_count";
		$sql .= " FROM " . MAIN_DB_PREFIX . $this->table_element . " rs";
		$sql .= " JOIN " . MAIN_DB_PREFIX . "actioncomm ac ON ac.id = rs.fk_actioncomm";
		$sql .= " WHERE rs.status = " . self::STATUS_ERROR;
		$sql .= " AND rs.retry_count < " . ((int) $maxRetries);
		$sql .= " AND ac.code = 'AC_SEND_WHATSAPP_NOTIFY'";
		$sql .= " AND ac.entity IN (" . getEntity('actioncomm') . ")";
		$sql .= " ORDER BY rs.reminder_date ASC";

		$resql = $this->db->query($sql);
		if ($resql) {
			while ($obj = $this->db->fetch_object($resql)) {
				$results[] = $obj;
			}
			return $results;
		}
		$this->error = $this->db->lasterror();
		return -1;
	}

	/**
	 * Update the reminder_date of a modifiable schedule entry
	 * @param int $newDate New timestamp for the reminder
	 * @return int >0 if OK, <0 if KO
	 */
	public function updateReminderDate($newDate)
	{
		if (!in_array($this->status, array(self::STATUS_PENDING, self::STATUS_DISABLED))) {
			$this->error = 'Cannot update date of a non-modifiable schedule';
			return -2;
		}

		$sql = "UPDATE " . MAIN_DB_PREFIX . $this->table_element;
		$sql .= " SET reminder_date = '" . $this->db->idate($newDate) . "'";
		$sql .= " WHERE rowid = " . ((int) $this->id);

		$resql = $this->db->query($sql);
		if ($resql) {
			$this->reminder_date = $newDate;
			return 1;
		}
		$this->error = $this->db->lasterror();
		return -1;
	}

	/**
	 * Get counts per status for a given actioncomm
	 * @param int $fk_actioncomm ActionComm ID
	 * @return array Associative array with status => count and 'overdue' key
	 */
	public function getCountsByStatus($fk_actioncomm)
	{
		$now = dol_now();
		$counts = array(
			self::STATUS_DISABLED => 0,
			self::STATUS_PENDING => 0,
			self::STATUS_SENT => 0,
			self::STATUS_ERROR => 0,
			'overdue' => 0,
			'total' => 0
		);

		$sql = "SELECT status, reminder_date FROM " . MAIN_DB_PREFIX . $this->table_element;
		$sql .= " WHERE fk_actioncomm = " . ((int) $fk_actioncomm);

		$resql = $this->db->query($sql);
		if ($resql) {
			while ($obj = $this->db->fetch_object($resql)) {
				$status = (int) $obj->status;
				if (isset($counts[$status])) {
					$counts[$status]++;
				}
				$counts['total']++;
				$reminderDate = $this->db->jdate($obj->reminder_date);
				if ($status == self::STATUS_PENDING && $reminderDate < $now) {
					$counts['overdue']++;
				}
			}
			return $counts;
		}
		$this->error = $this->db->lasterror();
		return $counts;
	}
}
