<?php

require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';



/**
 *		Class to manage agenda events (actions)
 */
class WhatsappUtils extends CommonObject
{
	/**
	 *      Constructor
	 *
	 *      @param      DoliDB		$db      Database handler
	 */
	public function __construct(DoliDB $db)
	{
		$this->db = $db;
	}

	/**
	 *  Send reminders by WhatsApp
	 *  CAN BE A CRON TASK
	 *
	 *  @return int         0 if OK, <>0 if KO (this function is used also by cron so only 0 is OK)
	 */
	public function sendWhAPIReminder()
	{
		global $conf, $langs, $user;
		dol_include_once('/comm/action/class/actioncomm.class.php');
		dol_include_once('/whatsapp/lib/whatsapp.lib.php');
		dol_include_once('/whatsapp/class/reminderschedule.class.php');

		$error = 0;
		$this->output = '';
		$this->error = '';
		$nbMessagesSent = 0;
		$errorsMsg = array();

		// Verificamos si el módulo está habilitado
		if (empty($conf->whatsapp->enabled)) {
			$langs->load("whatsapp");
			$this->output = $langs->trans('ModuleNotEnabled', $langs->transnoentitiesnoconv("Whatsapp"));
			return 0;
		}

		$now = dol_now();
		$actioncomm = new ActionComm($this->db);

		dol_syslog(__METHOD__, LOG_DEBUG);

		// ===== PHASE 1: Send from new schedule table =====
		$scheduleObj = new ReminderSchedule($this->db);
		$pendingSchedules = $scheduleObj->fetchPendingToSend();

		if (is_array($pendingSchedules) && count($pendingSchedules) > 0) {
			foreach ($pendingSchedules as $pending) {
				$result = $this->sendReminderForActionComm($pending->fk_actioncomm, $actioncomm, $errorsMsg);
				if ($result > 0) {
					$nbMessagesSent++;
					// Mark schedule as sent
					$sched = new ReminderSchedule($this->db);
					$sched->id = $pending->schedule_id;
					$sched->updateStatus(ReminderSchedule::STATUS_SENT);
				} else {
					// Mark schedule as error
					$sched = new ReminderSchedule($this->db);
					$sched->id = $pending->schedule_id;
					$sched->updateStatus(ReminderSchedule::STATUS_ERROR, end($errorsMsg));
				}
			}
		}

		// ===== PHASE 1.5: Retry failed schedules =====
		$maxRetries = !empty($conf->global->WHATSAPP_REMINDER_MAX_RETRIES) ? (int) $conf->global->WHATSAPP_REMINDER_MAX_RETRIES : 3;
		$retrySchedules = $scheduleObj->fetchRetryable($maxRetries);

		if (is_array($retrySchedules) && count($retrySchedules) > 0) {
			foreach ($retrySchedules as $retry) {
				$result = $this->sendReminderForActionComm($retry->fk_actioncomm, $actioncomm, $errorsMsg);
				if ($result > 0) {
					$nbMessagesSent++;
					$sched = new ReminderSchedule($this->db);
					$sched->id = $retry->schedule_id;
					$sched->updateStatus(ReminderSchedule::STATUS_SENT);
				} else {
					// updateStatus with STATUS_ERROR increments retry_count
					$sched = new ReminderSchedule($this->db);
					$sched->id = $retry->schedule_id;
					$sched->updateStatus(ReminderSchedule::STATUS_ERROR, end($errorsMsg));
				}
			}
		}

		// ===== PHASE 2: Legacy - Send from extrafields (backward compatibility) =====

		$this->db->begin();

		// Seleccionamos todos los eventos de tipo recordatorio WhatsApp pendientes (solo legacy sin schedule rows)
		$sql = "SELECT ac.id AS rowid FROM " . MAIN_DB_PREFIX . "actioncomm ac ";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "actioncomm_extrafields ace ON ace.fk_object=ac.id ";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "whatsapp_reminder_schedule wrs ON wrs.fk_actioncomm=ac.id ";
		$sql .= " WHERE ac.code = 'AC_SEND_WHATSAPP_NOTIFY'";
		$sql .= " AND (ace.whatsapp_message_sent IS NULL OR ace.whatsapp_message_sent = 0)";
		$sql .= " AND ace.whatsapp_notification_datetime IS NOT NULL";
		$sql .= " AND ace.whatsapp_notification_datetime <= '" . $this->db->idate($now) . "'";
		$sql .= " AND ac.entity IN (" . getEntity('actioncomm') . ")";
		$sql .= " AND wrs.rowid IS NULL"; // Exclude events that have schedule entries (handled in Phase 1)

		$resql = $this->db->query($sql);

		if ($resql) {
			while ($obj = $this->db->fetch_object($resql)) {
				// Cargamos el evento
				$res = $actioncomm->fetch($obj->rowid);
				if ($res < 0) {
					$error++;
					$errorsMsg[] = "Failed to load actioncomm with ID: " . $obj->rowid;
					continue;
				}

				$originObject = dolGetElementObject($actioncomm->fk_element, $actioncomm->elementtype);


				if (!$error) {
					// PREPARAR MENSAJE WHATSAPP
					$errormesg = '';

					// Obtenemos el contenido del mensaje desde la nota privada del evento o una plantilla
					$messageContent = $actioncomm->note_private;

					if (empty($messageContent)) {
						$error++;
						$errorsMsg[] = "No message content found for ID=" . $actioncomm->id;
						continue;
					}

					// Hacer sustituciones en el contenido del mensaje
					$substitutionarray = getCommonSubstitutionArray($langs, 0, '', $actioncomm);
					complete_substitutions_array($substitutionarray, $langs, $actioncomm);
					$messageContent = make_substitutions($messageContent, $substitutionarray);

					//Si $originObject tamen aplicar sus sustituciones
					if ($originObject) {
						$substitutionarray = getCommonSubstitutionArray($langs, 0, '', $originObject);
						complete_substitutions_array($substitutionarray, $langs, $originObject);
						$messageContent = make_substitutions($messageContent, $substitutionarray);
					}
					/* var_dump($messageContent);
					die(); */
					// Obtenemos el número de teléfono del destinatario
					$phoneNumber = '';
					if (!empty($actioncomm->contact_id)) {
						// Si hay un contacto asociado, usamos su número
						require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';
						$contact = new Contact($this->db);
						$res = $contact->fetch($actioncomm->contact_id);
						if ($res > 0) {
							$phoneNumber = $contact->phone_mobile;
							if (empty($phoneNumber)) {
								$phoneNumber = $contact->phone;
							}
						}
					} elseif (!empty($actioncomm->socid)) {
						// Si hay una empresa asociada, usamos su número
						require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
						$societe = new Societe($this->db);
						$res = $societe->fetch($actioncomm->socid);
						if ($res > 0) {
							$phoneNumber = $societe->phone;
						}
					}

					// Verificamos si tenemos un número válido
					if (empty($phoneNumber)) {
						$error++;
						$errorsMsg[] = "No valid phone number found for ID=" . $actioncomm->id;
					} else {
						// Formateamos el número si es necesario (eliminar espacios, añadir prefijo, etc.)
						$phoneNumber = preg_replace('/\s+/', '', $phoneNumber);
						if (substr($phoneNumber, 0, 1) !== '+') {
							// Añadimos el prefijo del país si no lo tiene
							$defaultCountryCode = !empty($conf->global->WHATSAPP_DEFAULT_COUNTRY_CODE) ? $conf->global->WHATSAPP_DEFAULT_COUNTRY_CODE : '34';
							$phoneNumber = '+' . $defaultCountryCode . $phoneNumber;
						}

						// Enviamos el mensaje de WhatsApp usando la nueva función unificada
						$result = sendWhapiText(null, $phoneNumber, $messageContent);

						if ($result) {
							$nbMessagesSent++;
							// Actualizar el campo extrafields para marcar como enviado
							$actioncomm->fetch_optionals();
							$actioncomm->array_options['options_whatsapp_message_sent'] = dol_now();
							$actioncomm->insertExtraFields();
						} else {
							$error++;
							$errorsMsg[] = "Error sending WhatsApp message to: " . $phoneNumber;
							continue;
						}
					}
				}

				if ($error) {
					/* 	// Si hay error, actualizamos el estado para indicar el error
					$actioncomm->fetch_optionals();
					$actioncomm->array_options['options_whatsapp_status'] = 'ERROR';
					$actioncomm->array_options['options_whatsapp_error'] = dol_trunc($errormesg, 128, 'right', 'UTF-8', 1);
					$actioncomm->updateExtraFields();
 */
					$errorsMsg[] = $errormesg;
					$error = 0; // Reseteamos para continuar con el siguiente mensaje
				}
			}
		} else {
			$error++;
			$this->error = $this->db->lasterror();
		}

		// También eliminamos registros antiguos que ya han sido enviados (más de 30 días)
		if (!$error) {
			$sql = "UPDATE " . MAIN_DB_PREFIX . "actioncomm_extrafields";
			$sql .= " SET whatsapp_message_sent = NULL"; // Resetear para poder enviar nuevamente
			$sql .= " WHERE whatsapp_sent_date < '" . $this->db->idate($now - (3600 * 24 * 30)) . "'";
			$sql .= " AND whatsapp_message_sent = 1";
			$resql = $this->db->query($sql);

			if (!$resql) {
				$errorsMsg[] = 'Failed to reset old WhatsApp reminders';
				// No marcamos error para no hacer rollback
			}
		}

		if (!$error) {
			$this->output = 'Number of WhatsApp messages sent: ' . $nbMessagesSent;
			$this->db->commit();
			return 0;
		} else {
			$this->db->commit(); // Hacemos commit incluso con error para guardar el estado
			$this->error = 'Number of WhatsApp messages sent: ' . $nbMessagesSent . ', ' . (!empty($errorsMsg) ? join(', ', $errorsMsg) : $error);
			return $error;
		}
	}

	/**
	 * Send a WhatsApp reminder for a specific ActionComm
	 * Shared logic used by both schedule-based and legacy sending
	 *
	 * @param int        $actioncommId  ID of the ActionComm
	 * @param ActionComm $actioncomm    Reusable ActionComm object
	 * @param array      $errorsMsg     Reference to errors array
	 * @return int 1 if sent OK, 0 if error
	 */
	private function sendReminderForActionComm($actioncommId, &$actioncomm, &$errorsMsg)
	{
		global $conf, $langs;

		$res = $actioncomm->fetch($actioncommId);
		if ($res < 0) {
			$errorsMsg[] = "Failed to load actioncomm with ID: " . $actioncommId;
			return 0;
		}

		$originObject = dolGetElementObject($actioncomm->fk_element, $actioncomm->elementtype);

		$messageContent = $actioncomm->note_private;
		if (empty($messageContent)) {
			$errorsMsg[] = "No message content found for ID=" . $actioncomm->id;
			return 0;
		}

		// Apply substitutions
		$substitutionarray = getCommonSubstitutionArray($langs, 0, '', $actioncomm);
		complete_substitutions_array($substitutionarray, $langs, $actioncomm);
		$messageContent = make_substitutions($messageContent, $substitutionarray);

		if ($originObject) {
			$substitutionarray = getCommonSubstitutionArray($langs, 0, '', $originObject);
			complete_substitutions_array($substitutionarray, $langs, $originObject);
			$messageContent = make_substitutions($messageContent, $substitutionarray);
		}

		// Get phone number
		$phoneNumber = '';
		if (!empty($actioncomm->contact_id)) {
			require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';
			$contact = new Contact($this->db);
			$res = $contact->fetch($actioncomm->contact_id);
			if ($res > 0) {
				$phoneNumber = $contact->phone_mobile;
				if (empty($phoneNumber)) {
					$phoneNumber = $contact->phone;
				}
			}
		} elseif (!empty($actioncomm->socid)) {
			require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
			$societe = new Societe($this->db);
			$res = $societe->fetch($actioncomm->socid);
			if ($res > 0) {
				$phoneNumber = $societe->phone;
			}
		}

		if (empty($phoneNumber)) {
			$errorsMsg[] = "No valid phone number found for ID=" . $actioncomm->id;
			return 0;
		}

		// Format phone number
		$phoneNumber = preg_replace('/\s+/', '', $phoneNumber);
		if (substr($phoneNumber, 0, 1) !== '+') {
			$defaultCountryCode = !empty($conf->global->WHATSAPP_DEFAULT_COUNTRY_CODE) ? $conf->global->WHATSAPP_DEFAULT_COUNTRY_CODE : '34';
			$phoneNumber = '+' . $defaultCountryCode . $phoneNumber;
		}

		// If the queue mode is forced, enqueue instead of sending immediately so the
		// cron throttles delivery (anti-SPAM). The reminder is considered handled.
		if (!empty($conf->global->WHATSAPP_FORCE_QUEUE)) {
			dol_include_once('/whatsapp/class/whatsappqueue.class.php');
			$qid = WhatsappQueue::enqueueText($phoneNumber, $messageContent, array(
				'fk_soc' => !empty($actioncomm->socid) ? (int) $actioncomm->socid : null,
				'fk_contact' => !empty($actioncomm->contact_id) ? (int) $actioncomm->contact_id : null,
				'fk_actioncomm' => (int) $actioncomm->id,
				'source_module' => 'whatsapp:reminder',
			));
			if ($qid > 0) {
				return 1;
			}
			$errorsMsg[] = "Failed to enqueue WhatsApp reminder for ID=" . $actioncomm->id;
			return 0;
		}

		$result = sendWhapiText(null, $phoneNumber, $messageContent);
		if ($result) {
			return 1;
		}

		$errorsMsg[] = "Error sending WhatsApp message to: " . $phoneNumber;
		return 0;
	}

	/**
	 *  Process the WhatsApp message queue. Sends up to N messages per run and
	 *  enforces a minimum window between batches so the underlying Baileys
	 *  connection is not flagged as SPAM.
	 *
	 *  CAN BE A CRON TASK. Cron parameters (positional, comma separated):
	 *      processQueue(maxToSend, windowSeconds, jitterSeconds)
	 *  Example "1, 150, 30" => 1 message at most every 150-180 seconds.
	 *  Empty arguments fall back to the setup constants and then to defaults.
	 *
	 *  @param  int|string $maxToSend      Max messages to send per run (N). 0/empty => WHATSAPP_QUEUE_MAX_PER_RUN or 1
	 *  @param  int|string $windowSeconds  Minimum seconds between batches (X). 0/empty => WHATSAPP_QUEUE_WINDOW_SECONDS or 150
	 *  @param  int|string $jitterSeconds  Random extra seconds added to the window. empty => WHATSAPP_QUEUE_JITTER_SECONDS or 0
	 *  @return int                        0 if OK (also when nothing was sent), <>0 if KO
	 */
	public function processQueue($maxToSend = 0, $windowSeconds = 0, $jitterSeconds = 0)
	{
		global $conf, $langs, $user;

		dol_include_once('/whatsapp/lib/whatsapp.lib.php');
		dol_include_once('/whatsapp/class/whatsappqueue.class.php');

		$this->output = '';
		$this->error = '';

		if (empty($conf->whatsapp->enabled)) {
			$langs->load("whatsapp");
			$this->output = $langs->trans('ModuleNotEnabled', $langs->transnoentitiesnoconv("Whatsapp"));
			return 0;
		}

		// Resolve effective settings: cron arg > setup constant > hardcoded default
		$maxToSend = (int) $maxToSend;
		if ($maxToSend <= 0) {
			$maxToSend = !empty($conf->global->WHATSAPP_QUEUE_MAX_PER_RUN) ? (int) $conf->global->WHATSAPP_QUEUE_MAX_PER_RUN : 1;
		}
		$windowSeconds = (int) $windowSeconds;
		if ($windowSeconds <= 0) {
			$windowSeconds = isset($conf->global->WHATSAPP_QUEUE_WINDOW_SECONDS) && $conf->global->WHATSAPP_QUEUE_WINDOW_SECONDS !== '' ? (int) $conf->global->WHATSAPP_QUEUE_WINDOW_SECONDS : 150;
		}
		$jitterSeconds = (int) $jitterSeconds;
		if ($jitterSeconds <= 0) {
			$jitterSeconds = !empty($conf->global->WHATSAPP_QUEUE_JITTER_SECONDS) ? (int) $conf->global->WHATSAPP_QUEUE_JITTER_SECONDS : 0;
		}
		$maxRetries = !empty($conf->global->WHATSAPP_QUEUE_MAX_RETRIES) ? (int) $conf->global->WHATSAPP_QUEUE_MAX_RETRIES : 3;
		$backoffSeconds = !empty($conf->global->WHATSAPP_QUEUE_BACKOFF_SECONDS) ? (int) $conf->global->WHATSAPP_QUEUE_BACKOFF_SECONDS : 300;

		$now = dol_now();

		// Cadence guard: ensure at least "window" (+ optional jitter) seconds elapsed
		// since the last batch, so we send at most N messages every X seconds.
		$lastSent = !empty($conf->global->WHATSAPP_QUEUE_LAST_SENT) ? (int) $conf->global->WHATSAPP_QUEUE_LAST_SENT : 0;
		$required = $windowSeconds + ($jitterSeconds > 0 ? mt_rand(0, $jitterSeconds) : 0);
		if ($lastSent > 0 && ($now - $lastSent) < $required) {
			$this->output = 'Throttled: waiting for the send window (last send ' . ($now - $lastSent) . 's ago, window ' . $windowSeconds . 's). Nothing sent this run.';
			return 0;
		}

		$queue = new WhatsappQueue($this->db);
		$rows = $queue->fetchSendable($maxToSend, $maxRetries);
		if (!is_array($rows)) {
			$this->error = 'Failed to fetch queue: ' . $queue->error;
			return 1;
		}
		if (count($rows) == 0) {
			$this->output = 'Queue empty: no message ready to send.';
			return 0;
		}

		$sent = 0;
		$failed = 0;
		$retried = 0;
		$skipped = 0;
		$attempted = false;
		$details = array();

		foreach ($rows as $row) {
			// Claim atomically so a concurrent run cannot send the same message twice
			$claimed = $row->claim(is_object($user) ? (int) $user->id : 0);
			if ($claimed !== 1) {
				$skipped++;
				continue;
			}

			$info = $this->dispatchQueueRow($row);
			$attempted = true;

			if (!empty($info['success'])) {
				$row->markSent($info['http_code']);
				$sent++;
			} elseif (!empty($info['permanent'])) {
				$row->markFailed($info['error'], $info['http_code']);
				$failed++;
				$details[] = '#' . $row->id . ' permanent: ' . $info['error'];
			} else {
				$row->markRetry($info['error'], $info['http_code'], $backoffSeconds, $maxRetries);
				if ((int) $row->status == WhatsappQueue::STATUS_FAILED) {
					$failed++;
					$details[] = '#' . $row->id . ' gave up after ' . $row->retry_count . ' retries: ' . $info['error'];
				} else {
					$retried++;
					$details[] = '#' . $row->id . ' retry ' . $row->retry_count . ': ' . $info['error'];
				}
			}

			// Remove the spool snapshot once the message reaches a terminal state
			if (in_array((int) $row->status, array(WhatsappQueue::STATUS_SENT, WhatsappQueue::STATUS_FAILED))) {
				$row->cleanupSpool();
			}
		}

		// Update the window anchor only if we actually contacted the API this run
		if ($attempted) {
			dolibarr_set_const($this->db, 'WHATSAPP_QUEUE_LAST_SENT', dol_now(), 'chaine', 0, '', $conf->entity);
		}

		$this->output = 'WhatsApp queue processed. Sent: ' . $sent . ', failed: ' . $failed . ', scheduled for retry: ' . $retried . ', skipped(locked): ' . $skipped . '.';
		if (!empty($details)) {
			$this->output .= ' [' . join(' | ', $details) . ']';
		}
		return 0;
	}

	/**
	 *  Send a single queue row through the WhatsApp API and return a structured
	 *  result. Validates pre-conditions (phone, file on disk) and treats missing
	 *  files / invalid input as permanent failures so they are not retried forever.
	 *
	 *  @param  WhatsappQueue $row  Queue row already loaded
	 *  @return array               Structured result (see whatsappApiPost())
	 */
	private function dispatchQueueRow($row)
	{
		$info = array('success' => false, 'http_code' => 0, 'error' => '', 'permanent' => false, 'content' => null);

		$phone = WhatsappQueue::normalizePhone($row->recipient_phone);
		if (empty($phone)) {
			$info['error'] = 'No valid recipient phone number';
			$info['permanent'] = true;
			return $info;
		}

		switch ($row->message_type) {
			case WhatsappQueue::TYPE_TEXT:
				if (trim((string) $row->message_content) === '') {
					$info['error'] = 'Empty text message content';
					$info['permanent'] = true;
					return $info;
				}
				return whatsappApiPost('/api/v1/messages/send-text', array(
					'number' => $phone,
					'text' => $row->message_content,
				));

			case WhatsappQueue::TYPE_DOCUMENT:
				if (empty($row->media_path) || !is_file($row->media_path)) {
					$info['error'] = 'File not found on disk: ' . $row->media_path;
					$info['permanent'] = true; // do not keep retrying a missing file
					return $info;
				}
				$content = @file_get_contents($row->media_path);
				if ($content === false) {
					$info['error'] = 'Unable to read file on disk: ' . $row->media_path;
					$info['permanent'] = true;
					return $info;
				}
				$mediatype = in_array($row->media_mediatype, array('image', 'video', 'document')) ? $row->media_mediatype : 'document';
				$mediaPayload = array(
					'number' => $phone,
					'mediatype' => $mediatype,
					'fileName' => $row->media_filename ? $row->media_filename : basename($row->media_path),
					'media' => base64_encode($content),
				);
				// Text stored with the document is sent as caption (text + file in a single message)
				if (trim((string) $row->message_content) !== '') {
					$mediaPayload['caption'] = $row->message_content;
				}
				return whatsappApiPost('/api/v1/messages/send-media', $mediaPayload);

			case WhatsappQueue::TYPE_AUDIO:
				if (empty($row->media_path) || !is_file($row->media_path)) {
					$info['error'] = 'Audio file not found on disk: ' . $row->media_path;
					$info['permanent'] = true;
					return $info;
				}
				$content = @file_get_contents($row->media_path);
				if ($content === false) {
					$info['error'] = 'Unable to read audio file on disk: ' . $row->media_path;
					$info['permanent'] = true;
					return $info;
				}
				return whatsappApiPost('/api/v1/messages/send-audio', array(
					'number' => $phone,
					'audio' => base64_encode($content),
					'asVoiceNote' => true,
				));

			default:
				$info['error'] = 'Unknown message type: ' . $row->message_type;
				$info['permanent'] = true;
				return $info;
		}
	}
}
