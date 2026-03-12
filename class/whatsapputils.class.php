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

		$result = sendWhapiText(null, $phoneNumber, $messageContent);
		if ($result) {
			return 1;
		}

		$errorsMsg[] = "Error sending WhatsApp message to: " . $phoneNumber;
		return 0;
	}
}
