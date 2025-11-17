<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2015      Jean-François Ferry	<jfefe@aternatik.fr>
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
 *	\file       whatsapp/whatsappindex.php
 *	\ingroup    whatsapp
 *	\brief      Home page of whatsapp top menu
 */
//if (! defined('NOREQUIREUSER')) define('NOREQUIREUSER', '1');
if (! defined('NOREQUIREMENU')) define('NOREQUIREMENU', '1');
if (! defined('NOCSRFCHECK')) define('NOCSRFCHECK', '1');
if (! defined('NOLOGIN')) define('NOLOGIN', '1');
// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
	$res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"] . "/main.inc.php";
}
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME'];
$tmp2 = realpath(__FILE__);
$i = strlen($tmp) - 1;
$j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
	$i--;
	$j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1)) . "/main.inc.php")) {
	$res = @include substr($tmp, 0, ($i + 1)) . "/main.inc.php";
}
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1))) . "/main.inc.php")) {
	$res = @include dirname(substr($tmp, 0, ($i + 1))) . "/main.inc.php";
}
// Try main.inc.php using relative path
if (!$res && file_exists("../main.inc.php")) {
	$res = @include "../main.inc.php";
}
if (!$res && file_exists("../../main.inc.php")) {
	$res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
	$res = @include "../../../main.inc.php";
}
if (!$res && file_exists("../../../../main.inc.php")) {
	$res = @include "../../../../main.inc.php";
}
if (!$res && file_exists("../../../../../main.inc.php")) {
	$res = @include "../../../../../main.inc.php";
}
if (!$res && file_exists("../../../../../../main.inc.php")) {
	$res = @include "../../../../../../main.inc.php";
}
if (!$res && file_exists("../../../../../../../main.inc.php")) {
	$res = @include "../../../../../../../main.inc.php";
}
if (!$res) {
	die("Include of main fails");
}
dol_include_once('/whatsapp/class/webhooklog.class.php');
dol_include_once('/whatsapp/lib/whatsapp.lib.php');
// Configuración básica
if (!empty($conf->global->WHATSAPP_WEBHOOK_USER_ID)) {
	$user->fetch($conf->global->WHATSAPP_WEBHOOK_USER_ID);
}


$debugMode = false; // Establecer en true para ver información de depuración
$langs->load("whatsapp@whatsapp");
if (!$conf->global->WHATSAPP_WEBHOOK_ALLOW) {
	log_error('Acceso denegado: WHATSAPP_WEBHOOK_ALLOW no está habilitado');
	http_response_code(403); // Forbidden
	die(json_encode(['error' => 'Acceso denegado']));
}
// Si estamos en modo de depuración, mostrar todos los errores
if ($debugMode) {
	ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
	error_reporting(E_ALL);
}

// Verificar si es una solicitud OPTIONS (CORS preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
	// Encabezados para CORS
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Methods: POST, OPTIONS');
	header('Access-Control-Allow-Headers: Content-Type, Authorization');
	header('Access-Control-Max-Age: 86400'); // 24 horas de caché
	exit(0);
}

// Verificar que sea una solicitud POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	log_error('Método no permitido: ' . $_SERVER['REQUEST_METHOD']);
	http_response_code(405); // Method Not Allowed
	die(json_encode(['error' => 'Método no permitido']));
}

// Obtener el cuerpo de la solicitud
$webhookContent = file_get_contents('php://input');

// Verificar que se recibió contenido
if (empty($webhookContent)) {
	log_error('No se recibió contenido');
	http_response_code(400);
	die(json_encode(['error' => 'No se recibió contenido']));
}

// Intentar decodificar el JSON
$eventData = json_decode($webhookContent, true);

// Verificar que el JSON sea válido
if (json_last_error() !== JSON_ERROR_NONE) {
	log_error('JSON inválido: ' . json_last_error_msg());
	http_response_code(400);
	die(json_encode(['error' => 'JSON inválido: ' . json_last_error_msg()]));
}

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
$sender = isset($eventData['sender']) ? $eventData['sender'] : '';
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
			$result = $db->query($sql);

			if ($result) {
				$object = $db->fetch_object($result);
				dol_include_once('/societe/class/societe.class.php');
				$societe = new Societe($db);
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












	$webhookLog = new WebhookLog($db);

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
	$result = $webhookLog->create($user);

	if ($result > 0) {
		// Si se guardó correctamente, devolver éxito
		http_response_code(200);
		header('Content-Type: application/json');
		header('Access-Control-Allow-Origin: *');
		echo json_encode([
			'success' => true,
			'message' => "Evento '$eventType' de instancia '$instanceName' recibido y guardado correctamente",
			'id' => $result,
			'timestamp' => $timestamp
		], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
	} else {
		// Si hubo un error al guardar, registrarlo
		log_error('Error al guardar en la base de datos: ' . $webhookLog->error);
		http_response_code(500);
		die(json_encode(['error' => 'Error al guardar en la base de datos']));
	}
} catch (Exception $e) {
	// Si ocurre una excepción, registrarla
	log_error('Excepción al procesar webhook: ' . $e->getMessage());
	http_response_code(500);
	die(json_encode(['error' => 'Error interno al procesar el webhook']));
}

// Función para registrar errores si está en modo de depuración
function log_error($message)
{
	global $debugMode;
	if ($debugMode) {
		$errorLog = 'webhook_errors.log';
		file_put_contents(
			$errorLog,
			date('Y-m-d H:i:s') . ' - ' . $message . PHP_EOL,
			FILE_APPEND
		);
	}
}
