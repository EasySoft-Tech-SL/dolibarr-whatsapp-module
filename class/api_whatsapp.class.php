<?php
/* Copyright (C) 2015   Jean-François Ferry     <jfefe@aternatik.fr>
 * Copyright (C) 2025 Alberto SuperAdmin <aluquerivasdev@gmail.com>
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

use Luracast\Restler\RestException;

dol_include_once('/whatsapp/class/webhooklog.class.php');



/**
 * \file    whatsapp/class/api_whatsapp.class.php
 * \ingroup whatsapp
 * \brief   File for API management of webhooklog.
 */

/**
 * API class for whatsapp webhooklog
 *
 * @access protected
 * @class  DolibarrApiAccess {@requires user,external}
 */
class WhatsappApi extends DolibarrApi
{
	/**
	 * @var WebhookLog $webhooklog {@type WebhookLog}
	 */
	public $webhooklog;

	/**
	 * Constructor
	 *
	 * @url     GET /
	 *
	 */
	public function __construct()
	{
		global $db;
		$this->db = $db;
		$this->webhooklog = new WebhookLog($this->db);
	}



	/**
	 * Create webhooklog object
	 *
	 * @param array $eventData   Request datas
	 * @return int  ID of webhooklog
	 *
	 * @throws RestException
	 *
	 * @url	POST register_webhook_logs/
	 */
	public function post($eventData = null)
	{
		global $conf, $langs;
		$langs->load("whatsapp@whatsapp");
		if (!DolibarrApiAccess::$user->rights->whatsapp->read) {
			throw new RestException(401);
		}
		if (!$conf->global->WHATSAPP_WEBHOOK_ALLOW) {
			throw new RestException(403, 'Webhook not allowed');
		}
		// Verificar que se recibió contenido
		if (empty($eventData)) {
			throw new RestException(400, 'No content received');
		}
		dol_include_once('/whatsapp/class/webhooklog.class.php');
		dol_include_once('/whatsapp/lib/whatsapp.lib.php');
		// Intentar decodificar el JSON


		// Añadir timestamp al evento
		$timestamp = date('Y-m-d H:i:s');
		$eventData['timestamp'] = $timestamp;
		if (isset($eventData['data']['messageTimestamp'])) {
			$eventData['data']['messageTimestamp'] = date('Y-m-d H:i:s', $eventData['data']['messageTimestamp']);
			$timestamp = $eventData['data']['messageTimestamp'];
		}

		// Extraer información relevante para WebhookLog
		$eventType = isset($eventData['event']) ? $eventData['event'] : 'desconocido';
		$instanceName = isset($eventData['instance']) ? $eventData['instance'] : 'desconocida';
		$phoneDestination = isset($eventData['data']['key']['remoteJid']) ? $eventData['data']['key']['remoteJid'] : '';
		$destination = isset($eventData['destination']) ? $eventData['destination'] : '';
		$serverUrl = isset($eventData['server_url']) ? $eventData['server_url'] : '';
		$apikey = isset($eventData['apikey']) ? $eventData['apikey'] : '';
		$fromMe = $eventData['data']['key']['fromMe'] ?? false;
		try {

			//VERIFICAMOS SI ES el $eventiType es messages.upsert creamos un evento de agenda
			if ($eventType == 'messages.upsert') {
				// Aquí puedes agregar la lógica para crear un evento de agenda
				// Por ejemplo, podrías llamar a una función que maneje esto
				// createAgendaEvent($eventData);

				//Primero buscamos el sender usando 34$phone@s.whatsapp.net la parte del telefono 34 66143134
				$phoneext = explode('@', $phoneDestination)[0];
				$phone = substr($phoneext, 2);
				$prefix = substr($phoneext, 0, 2);
				if (!empty($phone)) {
					$sql = "SELECT s.rowid FROM " . MAIN_DB_PREFIX . "societe s ";
					$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe_extrafields se ON s.rowid=se.fk_object ";
					$sql .= " WHERE REGEXP_REPLACE(s.phone, '[^0-9]', '') = '$phone' OR REGEXP_REPLACE(se.whatsapp_phone_number, '[^0-9]', '') = '$phone'";
					$sql .= " AND s.entity IN (" . getEntity('societe', 1) . ") LIMIT 1";
					$result = $this->db->query($sql);

					if ($result) {
						$object = $this->db->fetch_object($result);
						dol_include_once('/societe/class/societe.class.php');
						$societe = new Societe($this->db);
						if ($societe->fetch($object->rowid)) {
							$text = isset($eventData['data']['message']['conversation']) ? $eventData['data']['message']['conversation'] : '---';
							insertActionIntoAgenda(
								$societe,
								$langs->trans('WhatsappMessageReceived'),
								$text,
								$fromMe ? 'AC_SEND_WHATSAPP' : 'AC_RECEIVE_WHATSAPP',
								$societe->id,
								$societe->element,
								$timestamp,
								$instanceName
							);
						}
					}
				}
			}
			$webhookLog = new WebhookLog($this->db);

			// Llenar el objeto con datos del webhook
			$webhookLog->event = $eventType;
			$webhookLog->instance = $instanceName;
			$webhookLog->data = json_encode($eventData['data'] ?? '', JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
			$webhookLog->destination = $destination;
			$webhookLog->date_time = $timestamp;
			$webhookLog->sender = $sender;
			$webhookLog->server_url = $serverUrl;
			$webhookLog->apikey = $apikey;
			$webhookLog->timestamp = $timestamp;
			// Crear objeto WebhookLog en la base de datos (primer parámetro es usuario, segundo es notrigger)
			$result = $webhookLog->create(DolibarrApiAccess::$user);

			if ($result > 0) {
				return [
					'success' => true,
					'message' => "Evento '$eventType' de instancia '$instanceName' recibido y guardado correctamente",
					'id' => $result,
					'timestamp' => $timestamp
				];
			} else {
				throw new RestException(500, 'Error al guardar el webhook en la base de datos');
			}
		} catch (Exception $e) {
			// Manejar excepciones
			throw new RestException(500, 'Error al procesar el webhook: ' . $e->getMessage());
		}
	}
}
