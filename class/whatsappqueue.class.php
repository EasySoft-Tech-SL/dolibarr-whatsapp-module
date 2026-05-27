<?php
/* Copyright (C) 2025 Alberto Luque Rivas <aluquerivasdev@gmail.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file        class/whatsappqueue.class.php
 * \ingroup     whatsapp
 * \brief       CRUD class and reusable API for the WhatsApp message queue.
 *
 * The queue lets you defer outgoing WhatsApp messages (text, document/PDF, audio)
 * so a cron task can send them slowly (1 or N every X minutes) and avoid being
 * flagged as SPAM by the underlying Baileys connection. It can be filled from this
 * module or from any other module through the static enqueue* helpers.
 */

require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';

/**
 * Class for WhatsappQueue
 */
class WhatsappQueue extends CommonObject
{
	/**
	 * @var string ID of module.
	 */
	public $module = 'whatsapp';

	/**
	 * @var string ID to identify managed object.
	 */
	public $element = 'whatsappqueue';

	/**
	 * @var string Name of table without prefix where object is stored.
	 */
	public $table_element = 'whatsapp_message_queue';

	/**
	 * @var int  Does this object support multicompany module ? 1=Test with field entity
	 */
	public $ismultientitymanaged = 1;

	/**
	 * @var int  Does object support extrafields ? 0=No, 1=Yes
	 */
	public $isextrafieldmanaged = 0;

	/**
	 * @var string Picto.
	 */
	public $picto = 'fa-paper-plane';

	// Status constants
	const STATUS_PENDING = 0;     // Waiting in the queue, ready to be picked
	const STATUS_PROCESSING = 1;  // Claimed by a cron run, send in progress
	const STATUS_SENT = 2;        // Sent successfully
	const STATUS_ERROR = 3;       // Transient error, will be retried
	const STATUS_FAILED = 4;      // Permanent error or max retries reached, no more retries
	const STATUS_CANCELLED = 5;   // Manually cancelled

	// Message type constants
	const TYPE_TEXT = 'text';
	const TYPE_DOCUMENT = 'document';
	const TYPE_AUDIO = 'audio';

	/** @var string Last error produced by the static enqueue* API (for diagnostics) */
	public static $lastError = '';

	/**
	 * @var array Fields definition.
	 */
	public $fields = array(
		'rowid'           => array('type' => 'integer', 'label' => 'TechnicalID', 'enabled' => 1, 'position' => 1, 'notnull' => 1, 'visible' => 0, 'noteditable' => 1, 'index' => 1, 'comment' => "Id"),
		'ref'             => array('type' => 'varchar(128)', 'label' => 'Ref', 'enabled' => 1, 'position' => 5, 'notnull' => 1, 'visible' => 1, 'noteditable' => 1, 'default' => '(PROV)', 'index' => 1, 'searchall' => 1, 'showoncombobox' => 1, 'comment' => "Reference of object"),
		'entity'          => array('type' => 'integer', 'label' => 'Entity', 'enabled' => 1, 'position' => 6, 'notnull' => 1, 'visible' => 0, 'index' => 1),
		'status'          => array('type' => 'integer', 'label' => 'Status', 'enabled' => 1, 'position' => 10, 'notnull' => 1, 'visible' => 1, 'index' => 1, 'arrayofkeyval' => array(0 => 'WhatsappQueueStatusPending', 1 => 'WhatsappQueueStatusProcessing', 2 => 'WhatsappQueueStatusSent', 3 => 'WhatsappQueueStatusError', 4 => 'WhatsappQueueStatusFailed', 5 => 'WhatsappQueueStatusCancelled')),
		'message_type'    => array('type' => 'varchar(16)', 'label' => 'WhatsappQueueType', 'enabled' => 1, 'position' => 20, 'notnull' => 1, 'visible' => 1, 'arrayofkeyval' => array('text' => 'WhatsappQueueTypeText', 'document' => 'WhatsappQueueTypeDocument', 'audio' => 'WhatsappQueueTypeAudio')),
		'recipient_phone' => array('type' => 'varchar(32)', 'label' => 'WhatsappQueuePhone', 'enabled' => 1, 'position' => 30, 'notnull' => 1, 'visible' => 1, 'searchall' => 1, 'css' => 'nowraponall'),
		'recipient_name'  => array('type' => 'varchar(255)', 'label' => 'WhatsappQueueRecipient', 'enabled' => 1, 'position' => 35, 'notnull' => 0, 'visible' => 1, 'searchall' => 1, 'csslist' => 'tdoverflowmax150'),
		'message_content' => array('type' => 'text', 'label' => 'WhatsappQueueContent', 'enabled' => 1, 'position' => 40, 'notnull' => 0, 'visible' => 1, 'csslist' => 'tdoverflowmax200', 'cssview' => 'wordbreak'),
		'media_filename'  => array('type' => 'varchar(255)', 'label' => 'WhatsappQueueFile', 'enabled' => 1, 'position' => 45, 'notnull' => 0, 'visible' => 1, 'csslist' => 'tdoverflowmax150'),
		'media_path'      => array('type' => 'varchar(512)', 'label' => 'WhatsappQueueFilePath', 'enabled' => 1, 'position' => 46, 'notnull' => 0, 'visible' => -2, 'cssview' => 'wordbreak'),
		'media_mediatype' => array('type' => 'varchar(32)', 'label' => 'WhatsappQueueMediaType', 'enabled' => 1, 'position' => 47, 'notnull' => 0, 'visible' => -1),
		'priority'        => array('type' => 'integer', 'label' => 'Priority', 'enabled' => 1, 'position' => 50, 'notnull' => 1, 'visible' => -1, 'default' => 50),
		'date_scheduled'  => array('type' => 'datetime', 'label' => 'WhatsappQueueScheduled', 'enabled' => 1, 'position' => 55, 'notnull' => 0, 'visible' => 1, 'help' => 'WhatsappQueueScheduledHelp'),
		'retry_count'     => array('type' => 'integer', 'label' => 'WhatsappQueueRetries', 'enabled' => 1, 'position' => 60, 'notnull' => 1, 'visible' => 1, 'default' => 0, 'css' => 'center'),
		'max_retries'     => array('type' => 'integer', 'label' => 'WhatsappQueueMaxRetries', 'enabled' => 1, 'position' => 61, 'notnull' => 0, 'visible' => -2, 'help' => 'WhatsappQueueMaxRetriesHelp'),
		'last_error'      => array('type' => 'varchar(255)', 'label' => 'WhatsappQueueLastError', 'enabled' => 1, 'position' => 65, 'notnull' => 0, 'visible' => 1, 'csslist' => 'tdoverflowmax200', 'cssview' => 'wordbreak'),
		'http_code'       => array('type' => 'integer', 'label' => 'WhatsappQueueHttpCode', 'enabled' => 1, 'position' => 66, 'notnull' => 0, 'visible' => -1),
		'next_retry_date' => array('type' => 'datetime', 'label' => 'WhatsappQueueNextRetry', 'enabled' => 1, 'position' => 67, 'notnull' => 0, 'visible' => -1),
		'date_sent'       => array('type' => 'datetime', 'label' => 'WhatsappQueueSentDate', 'enabled' => 1, 'position' => 70, 'notnull' => 0, 'visible' => 1),
		'source_module'   => array('type' => 'varchar(64)', 'label' => 'WhatsappQueueSource', 'enabled' => 1, 'position' => 75, 'notnull' => 0, 'visible' => -1),
		'instance'        => array('type' => 'varchar(255)', 'label' => 'WhatsappInstance', 'enabled' => 1, 'position' => 76, 'notnull' => 0, 'visible' => -1),
		'fk_soc'          => array('type' => 'integer:Societe:societe/class/societe.class.php', 'label' => 'ThirdParty', 'enabled' => 1, 'position' => 80, 'notnull' => 0, 'visible' => -1, 'index' => 1),
		'fk_contact'      => array('type' => 'integer:Contact:contact/class/contact.class.php', 'label' => 'Contact', 'enabled' => 1, 'position' => 81, 'notnull' => 0, 'visible' => -1),
		'fk_actioncomm'   => array('type' => 'integer:ActionComm:comm/action/class/actioncomm.class.php', 'label' => 'Event', 'enabled' => 1, 'position' => 82, 'notnull' => 0, 'visible' => -1),
		'date_processing' => array('type' => 'datetime', 'label' => 'WhatsappQueueProcessingDate', 'enabled' => 1, 'position' => 85, 'notnull' => 0, 'visible' => -2),
		'date_creation'   => array('type' => 'datetime', 'label' => 'DateCreation', 'enabled' => 1, 'position' => 500, 'notnull' => 1, 'visible' => -2),
		'tms'             => array('type' => 'timestamp', 'label' => 'DateModification', 'enabled' => 1, 'position' => 501, 'notnull' => 0, 'visible' => -2),
		'fk_user_creat'   => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'UserAuthor', 'enabled' => 1, 'position' => 510, 'notnull' => 0, 'visible' => -2, 'foreignkey' => 'user.rowid'),
		'fk_user_modif'   => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'UserModif', 'enabled' => 1, 'position' => 511, 'notnull' => 0, 'visible' => -2),
		'import_key'      => array('type' => 'varchar(14)', 'label' => 'ImportId', 'enabled' => 1, 'position' => 1000, 'notnull' => 0, 'visible' => -2),
	);

	public $rowid;
	public $ref;
	public $entity;
	public $status;
	public $message_type;
	public $recipient_phone;
	public $recipient_name;
	public $message_content;
	public $media_filename;
	public $media_path;
	public $media_mediatype;
	public $priority;
	public $date_scheduled;
	public $retry_count;
	public $max_retries;
	public $last_error;
	public $http_code;
	public $next_retry_date;
	public $date_sent;
	public $source_module;
	public $instance;
	public $fk_soc;
	public $fk_contact;
	public $fk_actioncomm;
	public $date_processing;
	public $date_creation;
	public $tms;
	public $fk_user_creat;
	public $fk_user_modif;
	public $import_key;

	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct(DoliDB $db)
	{
		global $conf, $langs;

		$this->db = $db;

		if (empty($conf->global->MAIN_SHOW_TECHNICAL_ID) && isset($this->fields['rowid']) && !empty($this->fields['ref'])) {
			$this->fields['rowid']['visible'] = 0;
		}
		if (empty($conf->multicompany->enabled) && isset($this->fields['entity'])) {
			$this->fields['entity']['enabled'] = 0;
		}

		// Translate labels of arrayofkeyval
		if (is_object($langs)) {
			foreach ($this->fields as $key => $val) {
				if (!empty($val['arrayofkeyval']) && is_array($val['arrayofkeyval'])) {
					foreach ($val['arrayofkeyval'] as $key2 => $val2) {
						$this->fields[$key]['arrayofkeyval'][$key2] = $langs->trans($val2);
					}
				}
			}
		}
	}

	/**
	 * Create object into database
	 *
	 * @param  User $user      User that creates
	 * @param  bool $notrigger false=launch triggers after, true=disable triggers
	 * @return int             <0 if KO, Id of created object if OK
	 */
	public function create(User $user, $notrigger = false)
	{
		if (empty($this->ref) || $this->ref == '(PROV)') {
			$this->ref = $this->getNextRef();
		}
		if (empty($this->status) && $this->status !== 0) {
			$this->status = self::STATUS_PENDING;
		}
		if (empty($this->priority)) {
			$this->priority = 50;
		}
		return $this->createCommon($user, $notrigger);
	}

	/**
	 * Generate a unique reference for a queued message
	 *
	 * @return string Reference
	 */
	public function getNextRef()
	{
		return 'WQ' . dol_print_date(dol_now(), '%y%m%d%H%M%S') . substr(md5(uniqid('', true)), 0, 4);
	}

	/**
	 * Load object in memory from the database
	 *
	 * @param int    $id   Id object
	 * @param string $ref  Ref
	 * @return int         <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetch($id, $ref = null)
	{
		return $this->fetchCommon($id, $ref);
	}

	/**
	 * Load list of objects in memory from the database.
	 *
	 * @param  string $sortorder  Sort Order
	 * @param  string $sortfield  Sort field
	 * @param  int    $limit      Limit
	 * @param  int    $offset     Offset
	 * @param  array  $filter     Filter array
	 * @param  string $filtermode Filter mode (AND or OR)
	 * @return array|int          int <0 if KO, array of pages if OK
	 */
	public function fetchAll($sortorder = '', $sortfield = '', $limit = 0, $offset = 0, array $filter = array(), $filtermode = 'AND')
	{
		dol_syslog(__METHOD__, LOG_DEBUG);

		$records = array();

		$sql = "SELECT ";
		$sql .= $this->getFieldList('t');
		$sql .= " FROM " . MAIN_DB_PREFIX . $this->table_element . " as t";
		if (isset($this->ismultientitymanaged) && $this->ismultientitymanaged == 1) {
			$sql .= " WHERE t.entity IN (" . getEntity($this->element) . ")";
		} else {
			$sql .= " WHERE 1 = 1";
		}
		$sqlwhere = array();
		if (count($filter) > 0) {
			foreach ($filter as $key => $value) {
				if ($key == 't.rowid') {
					$sqlwhere[] = $key . " = " . ((int) $value);
				} elseif (in_array($this->fields[$key]['type'], array('date', 'datetime', 'timestamp'))) {
					$sqlwhere[] = $key . " = '" . $this->db->idate($value) . "'";
				} elseif ($key == 'customsql') {
					$sqlwhere[] = $value;
				} elseif (strpos($value, '%') === false) {
					$sqlwhere[] = $key . " IN (" . $this->db->sanitize($this->db->escape($value)) . ")";
				} else {
					$sqlwhere[] = $key . " LIKE '%" . $this->db->escape($value) . "%'";
				}
			}
		}
		if (count($sqlwhere) > 0) {
			$sql .= " AND (" . implode(" " . $filtermode . " ", $sqlwhere) . ")";
		}

		if (!empty($sortfield)) {
			$sql .= $this->db->order($sortfield, $sortorder);
		}
		if (!empty($limit)) {
			$sql .= $this->db->plimit($limit, $offset);
		}

		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < ($limit ? min($limit, $num) : $num)) {
				$obj = $this->db->fetch_object($resql);
				$record = new self($this->db);
				$record->setVarsFromFetchObj($obj);
				$records[$record->id] = $record;
				$i++;
			}
			$this->db->free($resql);
			return $records;
		} else {
			$this->errors[] = 'Error ' . $this->db->lasterror();
			dol_syslog(__METHOD__ . ' ' . join(',', $this->errors), LOG_ERR);
			return -1;
		}
	}

	/**
	 * Update object into database
	 *
	 * @param  User $user      User that modifies
	 * @param  bool $notrigger false=launch triggers after, true=disable triggers
	 * @return int             <0 if KO, >0 if OK
	 */
	public function update(User $user, $notrigger = false)
	{
		return $this->updateCommon($user, $notrigger);
	}

	/**
	 * Delete object in database
	 *
	 * @param User $user       User that deletes
	 * @param bool $notrigger  false=launch triggers after, true=disable triggers
	 * @return int             <0 if KO, >0 if OK
	 */
	public function delete(User $user, $notrigger = false)
	{
		$this->cleanupSpool();
		return $this->deleteCommon($user, $notrigger);
	}

	/* ------------------------------------------------------------------ */
	/* Public reusable API (callable from this or any other module)        */
	/* ------------------------------------------------------------------ */

	/**
	 * Enqueue a text message.
	 *
	 * @param string $phone   Recipient phone number (with or without prefix)
	 * @param string $text    Message body
	 * @param array  $options Optional fields: recipient_name, fk_soc, fk_contact, fk_actioncomm,
	 *                        date_scheduled (timestamp), priority, instance, source_module,
	 *                        max_retries, entity, fk_user_author (User object or id)
	 * @return int            >0 rowid of queued message, <0 if KO
	 */
	public static function enqueueText($phone, $text, $options = array())
	{
		$options['message_content'] = $text;
		return self::enqueue(self::TYPE_TEXT, $phone, $options);
	}

	/**
	 * Enqueue a document/PDF/image/video message.
	 *
	 * @param string $phone     Recipient phone number
	 * @param string $filepath  Absolute path to the file on disk
	 * @param string $filename  File name shown to the recipient (defaults to basename)
	 * @param string $mediatype 'document', 'image' or 'video'
	 * @param string $caption   Optional caption sent with the file
	 * @param array  $options   See enqueueText()
	 * @return int              >0 rowid, <0 if KO
	 */
	public static function enqueueDocument($phone, $filepath, $filename = '', $mediatype = 'document', $caption = '', $options = array())
	{
		$options['media_path'] = $filepath;
		$options['media_filename'] = $filename ? $filename : basename($filepath);
		$options['media_mediatype'] = $mediatype;
		$options['message_content'] = $caption;
		return self::enqueue(self::TYPE_DOCUMENT, $phone, $options);
	}

	/**
	 * Enqueue an audio / voice note message.
	 *
	 * @param string $phone         Recipient phone number
	 * @param string $filepath      Absolute path to the audio file on disk
	 * @param string $transcription Optional transcription stored as content
	 * @param array  $options       See enqueueText()
	 * @return int                  >0 rowid, <0 if KO
	 */
	public static function enqueueAudio($phone, $filepath, $transcription = '', $options = array())
	{
		$options['media_path'] = $filepath;
		$options['media_filename'] = basename($filepath);
		$options['message_content'] = $transcription;
		return self::enqueue(self::TYPE_AUDIO, $phone, $options);
	}

	/**
	 * Generic enqueue. Builds a queue row and stores it.
	 *
	 * @param string $type    One of TYPE_TEXT, TYPE_DOCUMENT, TYPE_AUDIO
	 * @param string $phone   Recipient phone number
	 * @param array  $data    Fields to set (see enqueueText())
	 * @return int            >0 rowid, <0 if KO
	 */
	public static function enqueue($type, $phone, $data = array())
	{
		global $db, $user, $conf;

		self::$lastError = '';

		if (empty($phone)) {
			self::$lastError = 'Empty recipient phone number';
			return -1;
		}

		$queueUser = $user;
		if (isset($data['fk_user_author'])) {
			if (is_object($data['fk_user_author'])) {
				$queueUser = $data['fk_user_author'];
			} elseif ((int) $data['fk_user_author'] > 0) {
				require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
				$queueUser = new User($db);
				$queueUser->fetch((int) $data['fk_user_author']);
			}
			unset($data['fk_user_author']);
		}
		if (!is_object($queueUser)) {
			require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
			$queueUser = new User($db);
		}

		$obj = new self($db);
		$obj->message_type = $type;
		$obj->recipient_phone = $phone;
		$obj->status = self::STATUS_PENDING;
		$obj->retry_count = 0;
		$obj->priority = isset($data['priority']) ? (int) $data['priority'] : 50;
		$obj->entity = isset($data['entity']) ? (int) $data['entity'] : (isset($conf->entity) ? $conf->entity : 1);

		$assignable = array('message_content', 'recipient_name', 'media_path', 'media_filename', 'media_mediatype', 'instance', 'source_module', 'fk_soc', 'fk_contact', 'fk_actioncomm', 'max_retries', 'date_scheduled');
		foreach ($assignable as $field) {
			if (isset($data[$field]) && $data[$field] !== '') {
				$obj->$field = $data[$field];
			}
		}

		// Optional snapshot: copy the media file into the queue spool so a later
		// regeneration/deletion of the source (e.g. an invoice PDF) does not affect the send.
		$spooledPath = null;
		if (!empty($data['copy_to_spool']) && !empty($obj->media_path)) {
			$spooled = self::spoolCopy($obj->media_path);
			if ($spooled !== false) {
				$obj->media_path = $spooled;
				$spooledPath = $spooled;
				if (empty($obj->media_filename)) {
					$obj->media_filename = basename((string) $data['media_path']);
				}
			}
		}

		$res = $obj->create($queueUser);
		if ($res > 0) {
			return $obj->id;
		}

		// create failed: drop the spool snapshot we just made so it does not stay orphaned
		if ($spooledPath !== null && self::isSpoolPath($spooledPath) && is_file($spooledPath)) {
			@unlink($spooledPath);
		}

		self::$lastError = !empty($obj->error) ? $obj->error : ((!empty($obj->errors) && is_array($obj->errors)) ? implode(', ', $obj->errors) : 'Unknown error creating queue row');
		dol_syslog('WhatsappQueue::enqueue create failed: ' . self::$lastError, LOG_ERR);
		return $res;
	}

	/* ------------------------------------------------------------------ */
	/* Processing helpers used by the cron task                            */
	/* ------------------------------------------------------------------ */

	/**
	 * Fetch the next sendable messages (pending ready + retryable errors ready),
	 * ordered by priority then schedule. Returns full objects.
	 *
	 * @param int $limit             Max number of rows to return
	 * @param int $defaultMaxRetries Default max retries when row has none set
	 * @return WhatsappQueue[]|int   Array of objects or -1 if KO
	 */
	public function fetchSendable($limit = 1, $defaultMaxRetries = 3)
	{
		$now = dol_now();
		$nowSql = "'" . $this->db->idate($now) . "'";
		$results = array();

		$sql = "SELECT " . $this->getFieldList('t');
		$sql .= " FROM " . MAIN_DB_PREFIX . $this->table_element . " as t";
		$sql .= " WHERE t.entity IN (" . getEntity($this->element) . ")";
		$sql .= " AND (";
		$sql .= "   (t.status = " . self::STATUS_PENDING . " AND (t.date_scheduled IS NULL OR t.date_scheduled <= " . $nowSql . "))";
		$sql .= "   OR (t.status = " . self::STATUS_ERROR . " AND (t.next_retry_date IS NULL OR t.next_retry_date <= " . $nowSql . ")";
		$sql .= "       AND t.retry_count < COALESCE(t.max_retries, " . ((int) $defaultMaxRetries) . "))";
		$sql .= " )";
		$sql .= " ORDER BY t.priority ASC, COALESCE(t.date_scheduled, t.date_creation) ASC";
		$sql .= $this->db->plimit((int) $limit, 0);

		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->error = $this->db->lasterror();
			return -1;
		}
		while ($obj = $this->db->fetch_object($resql)) {
			$record = new self($this->db);
			$record->setVarsFromFetchObj($obj);
			$results[] = $record;
		}
		$this->db->free($resql);
		return $results;
	}

	/**
	 * Atomically claim this row for processing. Prevents two concurrent cron
	 * runs from sending the same message twice.
	 *
	 * @param int $userid User id doing the claim
	 * @return int 1 if claimed, 0 if already taken by another run, <0 if KO
	 */
	public function claim($userid = 0)
	{
		$sql = "UPDATE " . MAIN_DB_PREFIX . $this->table_element;
		$sql .= " SET status = " . self::STATUS_PROCESSING;
		$sql .= ", date_processing = '" . $this->db->idate(dol_now()) . "'";
		if ($userid > 0) {
			$sql .= ", fk_user_modif = " . ((int) $userid);
		}
		$sql .= " WHERE rowid = " . ((int) $this->id);
		$sql .= " AND status IN (" . self::STATUS_PENDING . ", " . self::STATUS_ERROR . ")";

		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->error = $this->db->lasterror();
			return -1;
		}
		if ($this->db->affected_rows($resql) == 1) {
			$this->status = self::STATUS_PROCESSING;
			return 1;
		}
		return 0;
	}

	/**
	 * Mark this row as sent successfully.
	 *
	 * @param int $httpCode HTTP code returned by the API
	 * @return int >0 if OK, <0 if KO
	 */
	public function markSent($httpCode = 0)
	{
		$sql = "UPDATE " . MAIN_DB_PREFIX . $this->table_element;
		$sql .= " SET status = " . self::STATUS_SENT;
		$sql .= ", date_sent = '" . $this->db->idate(dol_now()) . "'";
		$sql .= ", last_error = NULL, next_retry_date = NULL";
		$sql .= ", http_code = " . ($httpCode ? (int) $httpCode : "NULL");
		$sql .= " WHERE rowid = " . ((int) $this->id);
		if ($this->db->query($sql)) {
			$this->status = self::STATUS_SENT;
			return 1;
		}
		return -1;
	}

	/**
	 * Mark this row as permanently failed (no more retries).
	 *
	 * @param string $error    Error message
	 * @param int    $httpCode HTTP code
	 * @return int >0 if OK, <0 if KO
	 */
	public function markFailed($error = '', $httpCode = 0)
	{
		$sql = "UPDATE " . MAIN_DB_PREFIX . $this->table_element;
		$sql .= " SET status = " . self::STATUS_FAILED;
		$sql .= ", last_error = '" . $this->db->escape(dol_trunc($error, 250)) . "'";
		$sql .= ", http_code = " . ($httpCode ? (int) $httpCode : "NULL");
		$sql .= ", next_retry_date = NULL";
		$sql .= " WHERE rowid = " . ((int) $this->id);
		if ($this->db->query($sql)) {
			$this->status = self::STATUS_FAILED;
			return 1;
		}
		return -1;
	}

	/**
	 * Register a transient failure: increment retry counter and either schedule a
	 * retry (status ERROR with next_retry_date) or give up (status FAILED) when the
	 * maximum number of retries has been reached.
	 *
	 * @param string $error          Error message
	 * @param int    $httpCode       HTTP code
	 * @param int    $backoffSeconds Base backoff in seconds (multiplied by retry number)
	 * @param int    $maxRetries     Effective max retries
	 * @return int >0 if OK, <0 if KO
	 */
	public function markRetry($error, $httpCode, $backoffSeconds, $maxRetries)
	{
		$newRetry = (int) $this->retry_count + 1;

		$sql = "UPDATE " . MAIN_DB_PREFIX . $this->table_element;
		$sql .= " SET retry_count = " . $newRetry;
		$sql .= ", last_error = '" . $this->db->escape(dol_trunc($error, 250)) . "'";
		$sql .= ", http_code = " . ($httpCode ? (int) $httpCode : "NULL");

		$newStatus = ($newRetry >= $maxRetries) ? self::STATUS_FAILED : self::STATUS_ERROR;
		if ($newStatus == self::STATUS_FAILED) {
			$sql .= ", status = " . self::STATUS_FAILED;
			$sql .= ", next_retry_date = NULL";
		} else {
			$nextTry = dol_now() + ($backoffSeconds * $newRetry);
			$sql .= ", status = " . self::STATUS_ERROR;
			$sql .= ", next_retry_date = '" . $this->db->idate($nextTry) . "'";
		}
		$sql .= " WHERE rowid = " . ((int) $this->id);

		if ($this->db->query($sql)) {
			$this->retry_count = $newRetry;
			$this->status = $newStatus;
			return 1;
		}
		return -1;
	}

	/**
	 * Cancel a queued (not yet sent) message.
	 *
	 * @param User $user User performing the action
	 * @return int >0 if OK, 0 if not cancellable, <0 if KO
	 */
	public function cancel($user)
	{
		if (!in_array((int) $this->status, array(self::STATUS_PENDING, self::STATUS_ERROR))) {
			return 0;
		}
		$this->status = self::STATUS_CANCELLED;
		$res = $this->update($user);
		if ($res > 0) {
			$this->cleanupSpool();
		}
		return $res;
	}

	/**
	 * Requeue a failed / cancelled / error message: reset to pending and clear
	 * error state so the cron picks it up again.
	 *
	 * @param User $user User performing the action
	 * @return int >0 if OK, 0 if not requeable, <0 if KO
	 */
	public function requeue($user)
	{
		if ((int) $this->status == self::STATUS_PROCESSING) {
			return 0;
		}
		$this->status = self::STATUS_PENDING;
		$this->retry_count = 0;
		$this->last_error = '';
		$this->http_code = null;
		$this->next_retry_date = null;
		$this->date_sent = null;
		return $this->update($user);
	}

	/**
	 * Get a count of rows per status (for the dashboard).
	 *
	 * @return array Associative array status => count, plus 'total'
	 */
	public function getCountsByStatus()
	{
		$counts = array(
			self::STATUS_PENDING => 0,
			self::STATUS_PROCESSING => 0,
			self::STATUS_SENT => 0,
			self::STATUS_ERROR => 0,
			self::STATUS_FAILED => 0,
			self::STATUS_CANCELLED => 0,
			'total' => 0,
		);

		$sql = "SELECT status, COUNT(rowid) as nb FROM " . MAIN_DB_PREFIX . $this->table_element;
		$sql .= " WHERE entity IN (" . getEntity($this->element) . ")";
		$sql .= " GROUP BY status";

		$resql = $this->db->query($sql);
		if ($resql) {
			while ($obj = $this->db->fetch_object($resql)) {
				$st = (int) $obj->status;
				if (isset($counts[$st])) {
					$counts[$st] = (int) $obj->nb;
				}
				$counts['total'] += (int) $obj->nb;
			}
			$this->db->free($resql);
		}
		return $counts;
	}

	/**
	 * Normalize a phone number: strip spaces and add the default country prefix
	 * when missing. Mirrors the logic used by the reminder sender.
	 *
	 * @param string $phone Raw phone number
	 * @return string Normalized phone number
	 */
	public static function normalizePhone($phone)
	{
		global $conf;

		// Remove spaces and usual separators
		$phone = preg_replace('/[\s()\.\-]+/', '', (string) $phone);
		if ($phone === '') {
			return '';
		}

		$defaultCountryCode = !empty($conf->global->WHATSAPP_DEFAULT_COUNTRY_CODE) ? $conf->global->WHATSAPP_DEFAULT_COUNTRY_CODE : '34';

		// The API expects the number as "<countrycode><number>" WITHOUT a leading '+'.
		// Normalize international notations to plain digits.
		if (substr($phone, 0, 1) === '+') {
			$phone = substr($phone, 1);
		} elseif (substr($phone, 0, 2) === '00') {
			$phone = substr($phone, 2);
		}

		// Keep digits only
		$phone = preg_replace('/\D+/', '', $phone);
		if ($phone === '') {
			return '';
		}

		// Prepend the default country code ONLY when the number does not already start with it
		// (avoids turning an already-international number like "34666..." into "3434666...").
		if (strpos($phone, $defaultCountryCode) !== 0) {
			$phone = $defaultCountryCode . $phone;
		}

		return $phone;
	}

	/**
	 * Directory where snapshot copies of media files are stored while queued.
	 *
	 * @return string Absolute path
	 */
	public static function getSpoolDir()
	{
		global $conf;
		return $conf->whatsapp->dir_output . '/queue';
	}

	/**
	 * Copy a file into the queue spool so a later change of the original does not
	 * affect the queued send (snapshot). Best-effort.
	 *
	 * @param string $srcPath Source file path
	 * @return string|false   New spool path, or false on failure
	 */
	public static function spoolCopy($srcPath)
	{
		if (empty($srcPath) || !is_file($srcPath)) {
			return false;
		}
		require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
		$dir = self::getSpoolDir();
		dol_mkdir($dir);
		$dest = $dir . '/' . dol_now() . '_' . substr(md5(uniqid('', true)), 0, 6) . '_' . dol_sanitizeFileName(basename($srcPath));
		$res = dol_copy($srcPath, $dest, 0, 1);
		return ($res > 0) ? $dest : false;
	}

	/**
	 * Whether a path lives inside the queue spool directory.
	 *
	 * @param string $path Path to test
	 * @return bool
	 */
	public static function isSpoolPath($path)
	{
		if (empty($path)) {
			return false;
		}
		$spool = str_replace('\\', '/', self::getSpoolDir());
		return strpos(str_replace('\\', '/', $path), $spool) === 0;
	}

	/**
	 * Delete this row's media file when it is a spool snapshot (best-effort).
	 * Files that are not in the spool (live paths provided by callers) are never touched.
	 *
	 * @return void
	 */
	public function cleanupSpool()
	{
		if (self::isSpoolPath($this->media_path) && is_file($this->media_path)) {
			@unlink($this->media_path);
		}
	}

	/**
	 * Return the status label.
	 *
	 * @param int $mode 0=long, 3=short picto, 5=long+picto, 6=picto+long
	 * @return string
	 */
	public function getLibStatut($mode = 0)
	{
		return $this->LibStatut($this->status, $mode);
	}

	/**
	 * Return a given status label as a coloured badge.
	 *
	 * @param int $status Status id
	 * @param int $mode   Display mode
	 * @return string
	 */
	public function LibStatut($status, $mode = 0)
	{
		global $langs;

		if (empty($this->labelStatus)) {
			$this->labelStatus = array(
				self::STATUS_PENDING    => $langs->transnoentitiesnoconv('WhatsappQueueStatusPending'),
				self::STATUS_PROCESSING => $langs->transnoentitiesnoconv('WhatsappQueueStatusProcessing'),
				self::STATUS_SENT       => $langs->transnoentitiesnoconv('WhatsappQueueStatusSent'),
				self::STATUS_ERROR      => $langs->transnoentitiesnoconv('WhatsappQueueStatusError'),
				self::STATUS_FAILED     => $langs->transnoentitiesnoconv('WhatsappQueueStatusFailed'),
				self::STATUS_CANCELLED  => $langs->transnoentitiesnoconv('WhatsappQueueStatusCancelled'),
			);
		}

		$statusType = 'status0';
		switch ((int) $status) {
			case self::STATUS_PENDING:
				$statusType = 'status1';
				break;
			case self::STATUS_PROCESSING:
				$statusType = 'status3';
				break;
			case self::STATUS_SENT:
				$statusType = 'status4';
				break;
			case self::STATUS_ERROR:
				$statusType = 'status7';
				break;
			case self::STATUS_FAILED:
				$statusType = 'status8';
				break;
			case self::STATUS_CANCELLED:
				$statusType = 'status9';
				break;
		}

		$label = isset($this->labelStatus[(int) $status]) ? $this->labelStatus[(int) $status] : $langs->trans('Unknown');
		return dolGetStatus($label, $label, '', $statusType, $mode);
	}

	/**
	 * Return a link to the object card.
	 *
	 * @param int    $withpicto 0=No picto, 1=Include picto, 2=Only picto
	 * @param string $option    Link target option ('nolink', ...)
	 * @param int    $notooltip 1=Disable tooltip
	 * @param string $morecss   More css on link
	 * @return string
	 */
	public function getNomUrl($withpicto = 0, $option = '', $notooltip = 0, $morecss = '')
	{
		global $langs;

		$result = '';
		$url = dol_buildpath('/whatsapp/messagequeue_card.php', 1) . '?id=' . $this->id;

		$label = img_picto('', $this->picto) . ' <u>' . $langs->trans("WhatsappQueueMessage") . '</u>';
		$label .= '<br><b>' . $langs->trans('Ref') . ':</b> ' . $this->ref;

		$linkclose = '';
		if (empty($notooltip)) {
			$linkclose .= ' title="' . dol_escape_htmltag($label, 1) . '"';
			$linkclose .= ' class="classfortooltip' . ($morecss ? ' ' . $morecss : '') . '"';
		} elseif ($morecss) {
			$linkclose = ' class="' . $morecss . '"';
		}

		if ($option == 'nolink' || empty($url)) {
			$linkstart = '<span';
			$linkend = '</span>';
		} else {
			$linkstart = '<a href="' . $url . '"';
			$linkend = '</a>';
		}
		$linkstart .= $linkclose . '>';

		$result .= $linkstart;
		if ($withpicto) {
			$result .= img_object(($notooltip ? '' : $label), ($this->picto ? $this->picto : 'generic'), ($notooltip ? '' : 'class="classfortooltip"'), 0, 0, $notooltip ? 0 : 1);
		}
		if ($withpicto != 2) {
			$result .= $this->ref;
		}
		$result .= $linkend;

		return $result;
	}
}
