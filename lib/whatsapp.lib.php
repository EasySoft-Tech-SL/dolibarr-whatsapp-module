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

/**
 * \file    whatsapp/lib/whatsapp.lib.php
 * \ingroup whatsapp
 * \brief   Library files with common functions for Whatsapp
 */
dol_include_once('/core/lib/geturl.lib.php');
/**
 * Prepare admin pages header
 *
 * @return array
 */
function whatsappAdminPrepareHead()
{
	global $langs, $conf;

	$langs->load("whatsapp@whatsapp");

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/whatsapp/admin/setup.php", 1);
	$head[$h][1] = $langs->trans("WhapiSettings");
	$head[$h][2] = 'settings';
	$h++;
	$head[$h][0] = dol_buildpath("/whatsapp/admin/plan_usage.php", 1);
	$head[$h][1] = $langs->trans("PlanUsage");
	$head[$h][2] = 'plan_usage';
	$h++;
	/*
	$head[$h][0] = dol_buildpath("/whatsapp/admin/myobject_extrafields.php", 1);
	$head[$h][1] = $langs->trans("ExtraFields");
	$head[$h][2] = 'myobject_extrafields';
	$h++;
	*/

	$head[$h][0] = dol_buildpath("/whatsapp/admin/about.php", 1);
	$head[$h][1] = $langs->trans("About");
	$head[$h][2] = 'about';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	//$this->tabs = array(
	//	'entity:+tabname:Title:@whatsapp:/whatsapp/mypage.php?id=__ID__'
	//); // to add new tab
	//$this->tabs = array(
	//	'entity:-tabname:Title:@whatsapp:/whatsapp/mypage.php?id=__ID__'
	//); // to remove a tab
	complete_head_from_modules($conf, $langs, null, $head, $h, 'whatsapp@whatsapp');

	complete_head_from_modules($conf, $langs, null, $head, $h, 'whatsapp@whatsapp', 'remove');

	return $head;
}

function getWhatsappConf()
{
	global $conf, $db, $user;
	$user->fetch_optionals();
	$whatConf = array();
	$whatConf['server_url'] = $conf->global->WHATSAPP_SERVER_URL;
	$whatConf['server_token'] = $user->array_options['options_whatsapp_server_token'] ?? $conf->global->WHATSAPP_SERVER_TOKEN;
	//Si algun no existe return -1
	if (empty($whatConf['server_url']) || empty($whatConf['server_token'])) {
		return false;
	}
	return $whatConf;
}

/**
 * Convierte HTML a texto plano
 *
 * @param string $html El HTML a convertir
 * @return string El texto plano resultante
 */
function htmlToPlainText($html)
{
	// Preparación inicial del texto
	$html = trim($html);

	// Decodificar entidades HTML (como &eacute; &amp; etc.)
	$html = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');

	// Reemplazar elementos que deberían causar pausas con puntos
	$html = preg_replace('/<\/(p|div|h[1-6]|tr|li|article|section|blockquote)>/i', '. ', $html);

	// Reemplazar saltos de línea HTML con puntos
	$html = preg_replace('/<br\s*\/?>/i', '. ', $html);
	$html = preg_replace('/<hr\s*\/?>/i', '. ', $html);

	// Tratamiento especial para listas
	$html = preg_replace('/<li[^>]*>/i', '• ', $html);

	// Tratamiento especial para enlaces
	$html = preg_replace('/<a\s+[^>]*href=(["\'])(.*?)\1[^>]*>(.*?)<\/a>/i', '$3 (enlace)', $html);

	// Tratamiento para tablas (simplificado)
	$html = preg_replace('/<\/t[dh]>\s*<t[dh][^>]*>/i', ', ', $html);

	// Eliminar etiquetas de formato que no necesitan tratamiento especial
	$removeTags = array('strong', 'b', 'em', 'i', 'u', 'span', 'font', 'small', 'big', 'sub', 'sup');
	foreach ($removeTags as $tag) {
		$html = preg_replace('/<\/?' . $tag . '[^>]*>/i', '', $html);
	}

	// Reemplazar variables del sistema (como __VARIABLE__)
	$html = preg_replace('/__([A-Z0-9_]+)__/', 'enlace correspondiente', $html);

	// Eliminar todas las demás etiquetas HTML
	$html = strip_tags($html);

	// Limpieza de espacios
	$html = preg_replace('/\s+/', ' ', $html);

	// Arreglar problemas de puntuación
	$html = preg_replace('/\s+\./', '.', $html);  // Eliminar espacios antes de puntos
	$html = preg_replace('/\.{2,}/', '.', $html); // Múltiples puntos a uno solo

	// Asegurar que haya espacios después de puntuación
	$html = preg_replace('/([.,;:!?])(?!\s|$)/', '$1 ', $html);

	// Eliminar espacios antes de paréntesis y después de paréntesis de apertura
	$html = preg_replace('/\s+\(/', ' (', $html);
	$html = preg_replace('/\(\s+/', '(', $html);

	// Eliminar espacios después de paréntesis de cierre y antes de puntuación
	$html = preg_replace('/\)\s+([.,;:!?])/', ')$1', $html);

	// Asegurar que no haya múltiples puntos seguidos
	while (strpos($html, '. .') !== false) {
		$html = str_replace('. .', '.', $html);
	}
	//Elimina asteriscos o cualquier caracter epecial que usa whatsapp para marcar el texto
	$html = preg_replace('/[^\w\s.,;:!?()]/u', '', $html);
	// Limpieza final
	$html = trim($html);

	return $html;
}

//FUNCIONES API WHAPI
function getInstanceStatus($token, $server)
{
	$endpoint = rtrim($server, '/') . '/api/v1/instance/status';
	$apikeyheader = 'X-API-Key: ' . $token;
	$response = getURLContent(
		$endpoint,
		'GET',
		'',
		0,
		array($apikeyheader),
		array('http', 'https'),
		2  // Permite tanto URLs externas como locales
	);


	// Verificar si hubo errores de CURL
	if (!empty($response['curl_error_no'])) {
		return array(
			'success' => false,
			'error' => 'CURL Error: ' . $response['curl_error_msg'],
			'error_code' => $response['curl_error_no']
		);
	}

	// Verificar código HTTP
	if ($response['http_code'] != 200) {
		return array(
			'success' => false,
			'error' => 'HTTP Error: ' . $response['http_code'],
			'error_code' => $response['http_code'],
			'content' => isset($response['content']) ? $response['content'] : ''
		);
	}

	// Decodificar respuesta JSON
	$jsonData = json_decode($response['content'], true);

	// Verificar si el JSON es válido
	if ($jsonData === null || !isset($jsonData['success'])) {
		return array(
			'success' => false,
			'error' => 'Invalid JSON response',
			'content' => $response['content']
		);
	}

	// Si la respuesta es exitosa, retornar los datos
	if ($jsonData['success'] === true && isset($jsonData['data'])) {
		return $jsonData['data'];
	}

	// En caso de que success sea false en la respuesta
	return array(
		'success' => false,
		'error' => 'API returned success=false',
		'response' => $jsonData
	);
}

function logoutInstance()
{
	global $langs;

	// Obtener configuración automáticamente
	$whatConf = getWhatsappConf();
	if ($whatConf === false) {
		setEventMessages($langs->trans("WhatsappConfigurationMissing"), null, 'errors');
		return false;
	}

	$token = $whatConf['server_token'];
	$server = $whatConf['server_url'];
	$endpoint = rtrim($server, '/') . '/api/v1/instance/logout';
	$apikeyheader = 'X-API-Key: ' . $token;

	// Headers para POST
	$headers = array(
		$apikeyheader,
		'Content-Type: application/json'
	);

	try {
		$response = getURLContent(
			$endpoint,
			'POST',
			'{}', // Payload vacío para POST
			0,
			$headers,
			array('http', 'https'),
			2  // Permite tanto URLs externas como locales
		);

		// Verificar si hubo errores de CURL
		if (!empty($response['curl_error_no'])) {
			$errorMsg = 'CURL Error: ' . $response['curl_error_msg'];
			setEventMessages($langs->trans("ErrorLogoutInstance") . ': ' . $errorMsg, null, 'errors');
			return false;
		}

		// Verificar código HTTP
		if ($response['http_code'] != 200 && $response['http_code'] != 201) {
			// Decodificar respuesta para obtener mensaje de error específico
			$errorContent = '';
			if (isset($response['content'])) {
				$jsonError = json_decode($response['content'], true);
				if ($jsonError && isset($jsonError['error'])) {
					$errorContent = $jsonError['error'];
					// Si hay detalles adicionales, agregarlos
					if (isset($jsonError['details']['message'])) {
						$errorContent .= ' - ' . $jsonError['details']['message'];
					}
				} else {
					$errorContent = $response['content'];
				}
			}

			$errorMsg = 'HTTP Error ' . $response['http_code'] . ': ' . $errorContent;
			setEventMessages($langs->trans("ErrorLogoutInstance") . ': ' . $errorMsg, null, 'errors');
			return false;
		}

		// Decodificar respuesta JSON
		$jsonData = json_decode($response['content'], true);

		// Verificar si el JSON es válido
		if ($jsonData === null) {
			$errorMsg = 'Invalid JSON response';
			setEventMessages($langs->trans("ErrorLogoutInstance") . ': ' . $errorMsg, null, 'errors');
			return false;
		}

		// Verificar si la operación fue exitosa
		if (isset($jsonData['success']) && $jsonData['success'] === true) {
			setEventMessages($langs->trans("InstanceLogoutSuccessfully"), null, 'mesgs');
			return true;
		}

		// Si no fue exitoso
		$errorMsg = isset($jsonData['error']) ? $jsonData['error'] : 'API returned success=false';
		setEventMessages($langs->trans("ErrorLogoutInstance") . ': ' . $errorMsg, null, 'errors');
		return false;
	} catch (Exception $e) {
		$errorMsg = $e->getMessage();
		setEventMessages($langs->trans("ErrorLogoutInstance") . ': ' . $errorMsg, null, 'errors');
		return false;
	}
}

function sendWhapiText($object, $phone, $message, $options = array())
{
	global $langs;

	// Obtener configuración automáticamente
	$whatConf = getWhatsappConf();
	if ($whatConf === false) {
		setEventMessages($langs->trans("WhatsappConfigurationMissing"), null, 'errors');
		return false;
	}

	$token = $whatConf['server_token'];
	$server = $whatConf['server_url'];
	$endpoint = rtrim($server, '/') . '/api/v1/messages/send-text';
	$apikeyheader = 'X-API-Key: ' . $token;

	// Preparar payload base
	$payload = array(
		'number' => $phone,
		'text' => $message
	);

	// Agregar opciones adicionales si existen
	if (!empty($options)) {
		$payload = array_merge($payload, $options);
	}

	// Convertir payload a JSON
	$jsonPayload = json_encode($payload);

	// Headers adicionales para JSON
	$headers = array(
		$apikeyheader,
		'Content-Type: application/json',
		'Content-Length: ' . strlen($jsonPayload)
	);

	try {
		$response = getURLContent(
			$endpoint,
			'POSTALREADYFORMATED',  // Usar POSTALREADYFORMATED para enviar JSON tal como está
			$jsonPayload,
			0,
			$headers,
			array('http', 'https'),
			2  // Permite tanto URLs externas como locales
		);

		// Verificar si hubo errores de CURL
		if (!empty($response['curl_error_no'])) {
			$errorMsg = 'CURL Error: ' . $response['curl_error_msg'];
			setEventMessages($langs->trans("ErrorSendingMessage") . ': ' . $errorMsg, null, 'errors');
			return false;
		}

		// Verificar código HTTP
		if ($response['http_code'] != 200 && $response['http_code'] != 201) {
			// Decodificar respuesta para obtener mensaje de error específico
			$errorContent = '';
			if (isset($response['content'])) {
				$jsonError = json_decode($response['content'], true);
				if ($jsonError && isset($jsonError['error'])) {
					$errorContent = $jsonError['error'];
					// Si hay detalles adicionales, agregarlos
					if (isset($jsonError['details']['message'])) {
						$errorContent .= ' - ' . $jsonError['details']['message'];
					}
				} else {
					$errorContent = $response['content'];
				}
			}

			$errorMsg = 'HTTP Error ' . $response['http_code'] . ': ' . $errorContent;
			setEventMessages($langs->trans("ErrorSendingMessage") . ': ' . $errorMsg, null, 'errors');
			return false;
		}

		// Decodificar respuesta JSON
		$jsonData = json_decode($response['content'], true);

		// Verificar si el JSON es válido
		if ($jsonData === null) {
			$errorMsg = 'Invalid JSON response';
			setEventMessages($langs->trans("ErrorSendingMessage") . ': ' . $errorMsg, null, 'errors');
			return false;
		}

		$success = false;

		// Si la respuesta contiene success y es true, es exitoso
		if (isset($jsonData['success']) && $jsonData['success'] === true) {
			$success = true;
		}
		// Si la respuesta no contiene success pero el código HTTP es exitoso, asumir éxito
		elseif ($response['http_code'] == 200 || $response['http_code'] == 201) {
			$success = true;
		}

		// Si el envío fue exitoso
		if ($success) {
			// Registrar en la agenda si se proporciona objeto
			if ($object) {
				insertActionIntoAgenda(
					$object,
					$langs->trans('WhatsappMessageFromSubject'),
					$message,
					'AC_SEND_WHATSAPP',
					$object->id,
					$object->element
				);
			}

			// Mostrar mensaje de éxito
			setEventMessages($langs->trans("MessageSentSuccessfully"), null, 'mesgs');
			return true;
		}

		// Si no fue exitoso
		$errorMsg = 'API returned success=false or unexpected response';
		setEventMessages($langs->trans("ErrorSendingMessage") . ': ' . $errorMsg, null, 'errors');
		return false;
	} catch (Exception $e) {
		$errorMsg = $e->getMessage();
		setEventMessages($langs->trans("ErrorSendingMessage") . ': ' . $errorMsg, null, 'errors');
		return false;
	}
}

function sendWhapiAudio($object, $phone, $audioBase64, $transcription, $asVoiceNote = true, $options = array())
{
	global $langs;

	// Obtener configuración automáticamente
	$whatConf = getWhatsappConf();
	if ($whatConf === false) {
		setEventMessages($langs->trans("WhatsappConfigurationMissing"), null, 'errors');
		return false;
	}

	$token = $whatConf['server_token'];
	$server = $whatConf['server_url'];
	$endpoint = rtrim($server, '/') . '/api/v1/messages/send-audio';
	$apikeyheader = 'X-API-Key: ' . $token;

	// Preparar payload base
	$payload = array(
		'number' => $phone,
		'audio' => $audioBase64,
		'asVoiceNote' => $asVoiceNote
	);

	// Agregar opciones adicionales si existen
	if (!empty($options)) {
		$payload = array_merge($payload, $options);
	}

	// Convertir payload a JSON
	$jsonPayload = json_encode($payload);

	// Headers adicionales para JSON
	$headers = array(
		$apikeyheader,
		'Content-Type: application/json',
		'Content-Length: ' . strlen($jsonPayload)
	);

	try {
		$response = getURLContent(
			$endpoint,
			'POSTALREADYFORMATED',  // Usar POSTALREADYFORMATED para enviar JSON tal como está
			$jsonPayload,
			0,
			$headers,
			array('http', 'https'),
			2  // Permite tanto URLs externas como locales
		);

		// Verificar si hubo errores de CURL
		if (!empty($response['curl_error_no'])) {
			$errorMsg = 'CURL Error: ' . $response['curl_error_msg'];
			setEventMessages($langs->trans("ErrorSendingMessage") . ': ' . $errorMsg, null, 'errors');
			return false;
		}

		// Verificar código HTTP
		if ($response['http_code'] != 200 && $response['http_code'] != 201) {
			// Decodificar respuesta para obtener mensaje de error específico
			$errorContent = '';
			if (isset($response['content'])) {
				$jsonError = json_decode($response['content'], true);
				if ($jsonError && isset($jsonError['error'])) {
					$errorContent = $jsonError['error'];
					// Si hay detalles adicionales, agregarlos
					if (isset($jsonError['details']['message'])) {
						$errorContent .= ' - ' . $jsonError['details']['message'];
					}
				} else {
					$errorContent = $response['content'];
				}
			}

			$errorMsg = 'HTTP Error ' . $response['http_code'] . ': ' . $errorContent;
			setEventMessages($langs->trans("ErrorSendingMessage") . ': ' . $errorMsg, null, 'errors');
			return false;
		}

		// Decodificar respuesta JSON
		$jsonData = json_decode($response['content'], true);

		// Verificar si el JSON es válido
		if ($jsonData === null) {
			$errorMsg = 'Invalid JSON response';
			setEventMessages($langs->trans("ErrorSendingMessage") . ': ' . $errorMsg, null, 'errors');
			return false;
		}

		$success = false;

		// Si la respuesta contiene success y es true, es exitoso
		if (isset($jsonData['success']) && $jsonData['success'] === true) {
			$success = true;
		}
		// Si la respuesta no contiene success pero el código HTTP es exitoso, asumir éxito
		elseif ($response['http_code'] == 200 || $response['http_code'] == 201) {
			$success = true;
		}

		// Si el envío fue exitoso
		if ($success) {
			// Registrar en la agenda si se proporciona objeto
			if ($object) {
				insertActionIntoAgenda(
					$object,
					$langs->trans('WhatsappMessageAudioFromSubject'),
					$transcription,
					'AC_SEND_WHATSAPP_AUDIO',
					$object->id,
					$object->element
				);
			}

			// Mostrar mensaje de éxito
			setEventMessages($langs->trans("AudioMessageSentSuccessfully"), null, 'mesgs');
			return true;
		}

		// Si no fue exitoso
		$errorMsg = 'API returned success=false or unexpected response';
		setEventMessages($langs->trans("ErrorSendingMessage") . ': ' . $errorMsg, null, 'errors');
		return false;
	} catch (Exception $e) {
		$errorMsg = $e->getMessage();
		setEventMessages($langs->trans("ErrorSendingMessage") . ': ' . $errorMsg, null, 'errors');
		return false;
	}
}

function sendWhapiDocument($object, $phone, $media, $fileName, $mediatype = 'document', $options = array())
{
	global $langs;

	// Obtener configuración automáticamente
	$whatConf = getWhatsappConf();
	if ($whatConf === false) {
		setEventMessages($langs->trans("WhatsappConfigurationMissing"), null, 'errors');
		return false;
	}

	$token = $whatConf['server_token'];
	$server = $whatConf['server_url'];
	$endpoint = rtrim($server, '/') . '/api/v1/messages/send-media';
	$apikeyheader = 'X-API-Key: ' . $token;

	// Validar mediatype
	$validMediaTypes = ['image', 'video', 'document'];
	if (!in_array($mediatype, $validMediaTypes)) {
		setEventMessages($langs->trans("InvalidMediaType") . ': ' . $mediatype, null, 'errors');
		return false;
	}

	// Preparar payload base
	$payload = array(
		'number' => $phone,
		'mediatype' => $mediatype,
		'fileName' => $fileName,
		'media' => $media
	);

	// Agregar opciones adicionales si existen
	if (!empty($options)) {
		$payload = array_merge($payload, $options);
	}

	// Convertir payload a JSON
	$jsonPayload = json_encode($payload);

	// Headers adicionales para JSON
	$headers = array(
		$apikeyheader,
		'Content-Type: application/json',
		'Content-Length: ' . strlen($jsonPayload)
	);

	try {
		$response = getURLContent(
			$endpoint,
			'POSTALREADYFORMATED',  // Usar POSTALREADYFORMATED para enviar JSON tal como está
			$jsonPayload,
			0,
			$headers,
			array('http', 'https'),
			2  // Permite tanto URLs externas como locales
		);

		// Verificar si hubo errores de CURL
		if (!empty($response['curl_error_no'])) {
			$errorMsg = 'CURL Error: ' . $response['curl_error_msg'];
			setEventMessages($langs->trans("ErrorSendingMessage") . ': ' . $errorMsg, null, 'errors');
			return false;
		}

		// Verificar código HTTP
		if ($response['http_code'] != 200 && $response['http_code'] != 201) {
			// Decodificar respuesta para obtener mensaje de error específico
			$errorContent = '';
			if (isset($response['content'])) {
				$jsonError = json_decode($response['content'], true);
				if ($jsonError && isset($jsonError['error'])) {
					$errorContent = $jsonError['error'];
					// Si hay detalles adicionales, agregarlos
					if (isset($jsonError['details']['message'])) {
						$errorContent .= ' - ' . $jsonError['details']['message'];
					}
				} else {
					$errorContent = $response['content'];
				}
			}

			$errorMsg = 'HTTP Error ' . $response['http_code'] . ': ' . $errorContent;
			setEventMessages($langs->trans("ErrorSendingMessage") . ': ' . $errorMsg, null, 'errors');
			return false;
		}

		// Decodificar respuesta JSON
		$jsonData = json_decode($response['content'], true);

		// Verificar si el JSON es válido
		if ($jsonData === null) {
			$errorMsg = 'Invalid JSON response';
			setEventMessages($langs->trans("ErrorSendingMessage") . ': ' . $errorMsg, null, 'errors');
			return false;
		}

		$success = false;

		// Si la respuesta contiene success y es true, es exitoso
		if (isset($jsonData['success']) && $jsonData['success'] === true) {
			$success = true;
		}
		// Si la respuesta no contiene success pero el código HTTP es exitoso, asumir éxito
		elseif ($response['http_code'] == 200 || $response['http_code'] == 201) {
			$success = true;
		}

		// Si el envío fue exitoso
		if ($success) {
			// Registrar en la agenda si se proporciona objeto
			if ($object) {
				// Determinar el tipo de acción según el mediatype
				$actionCode = 'AC_SEND_WHATSAPP_PDF';
				$titleKey = 'WhatsappDocumentFromSubject';

				if ($mediatype === 'image') {
					$actionCode = 'AC_SEND_WHATSAPP_IMAGE';
					$titleKey = 'WhatsappImageFromSubject';
				} elseif ($mediatype === 'video') {
					$actionCode = 'AC_SEND_WHATSAPP_VIDEO';
					$titleKey = 'WhatsappVideoFromSubject';
				}

				insertActionIntoAgenda(
					$object,
					$langs->trans($titleKey),
					$fileName,
					$actionCode,
					$object->id,
					$object->element
				);
			}

			// Mostrar mensaje de éxito
			setEventMessages($langs->trans("DocumentSentSuccessfully"), null, 'mesgs');
			return true;
		}

		// Si no fue exitoso
		$errorMsg = 'API returned success=false or unexpected response';
		setEventMessages($langs->trans("ErrorSendingMessage") . ': ' . $errorMsg, null, 'errors');
		return false;
	} catch (Exception $e) {
		$errorMsg = $e->getMessage();
		setEventMessages($langs->trans("ErrorSendingMessage") . ': ' . $errorMsg, null, 'errors');
		return false;
	}
}

function insertActionIntoAgenda($object, $title, $body, $actiontypecode, $elementid, $elementtype, $date = null, $instanceName = null)
{
	global $langs, $user;
	$conf = getWhatsappConf();
	if ($conf === false) {
		return false;
	}
	$socid = null;
	if ($object instanceof Societe) {
		$socid = $object->id;
	} else {
		$socid = $object->socid ?? $object->fk_soc;
	}
	require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
	$actioncomm = new ActionComm($object->db);
	$actioncomm->type_code   = $actiontypecode; // Type of event ('AC_OTH', 'AC_OTH_AUTO', 'AC_XXX'...)
	$actioncomm->code        = $actiontypecode;
	$actioncomm->label       = $title;
	$actioncomm->note_private = $body;
	$actioncomm->datep       = $date ?? dol_now();
	$actioncomm->datef       = dol_now();
	$actioncomm->durationp   = 0;
	$actioncomm->percentage  = -1; // Not applicable
	$actioncomm->socid       = $socid;
	$actioncomm->contact_id = $object->contact_id; // deprecated, use ->socpeopleassigned instead
	$actioncomm->authorid    = $user->id; // User saving action
	$actioncomm->userownerid = $user->id; // Owner of action
	$actioncomm->fk_element  = $elementid;
	$actioncomm->elementtype = $elementtype;
	$actioncomm->elementmodule = 'whatsapp@whatsapp';
	$actioncomm->array_options['options_whatsapp_server_instance_name'] = $instanceName ?? $conf['server_instance_name'];
	$ret = $actioncomm->create($user);
}

function getObjectWhatsappMessages($fk_element, $elementtype, $code = ['AC_SEND_WHATSAPP'], $loadObject = true, $socid = 0)
{
	global $db;
	dol_include_once('/comm/action/class/actioncomm.class.php');
	switch ($elementtype) {
		case 'facture':
			$elementtype = 'invoice';
			break;
		case 'commande':
			$elementtype = 'order';
			break;
	}

	// Asegurarse de que $code sea un array
	if (!is_array($code)) {
		$code = [$code];
	}

	$sql = "SELECT ac.id as rowid,ac.* FROM " . MAIN_DB_PREFIX . "actioncomm ac ";
	$sql .= " WHERE (";

	$conditions = [];
	foreach ($code as $singleCode) {
		$conditions[] = "ac.code LIKE '%" . $db->escape($singleCode) . "%'";
	}

	$sql .= implode(" OR ", $conditions);
	$sql .= ") ";
	if ($elementtype == 'societe') {
		$sql .= "AND ac.fk_soc= " . intval($fk_element) . " ";
	} else {

		if ($socid > 0) {
			$sql .= " AND (ac.fk_soc = " . intval($socid) . ")";
		} else {
			$sql .= " AND ac.fk_element= " . intval($fk_element) . " AND ac.elementtype='" . $db->escape($elementtype) . "'";
		}
	}
	$sql .= " ORDER BY ac.datep,ac.id ASC";

	$resql = $db->query($sql);
	$data = array();
	if ($resql) {
		while ($obj = $db->fetch_object($resql)) {

			if ($loadObject) {
				$actioncomm = new ActionComm($db);
				$res = $actioncomm->fetch($obj->rowid);
				if ($res) {
					$actioncomm->fetch_optionals();
					$data[$actioncomm->id] = $actioncomm;
				}
			} else {
				$data[$obj->rowid] = $obj;
			}
		}
		return $data;
	} else {
		return [];
	}
}

/**
 * Return link url to an object
 *
 * @param 	int		$objectid		Id of record
 * @param 	string	$objecttype		Type of object ('invoice', 'order', 'expedition_bon', 'myobject@mymodule', ...)
 * @return	string					URL of link to object id/type
 */
function dolGetElementObject($objectid, $objecttype)
{
	global $db, $conf, $langs;

	$ret = '';
	$regs = array();

	// If we ask a resource form external module (instead of default path)
	if (preg_match('/^([^@]+)@([^@]+)$/i', $objecttype, $regs)) {
		$myobject = $regs[1];
		$module = $regs[2];
	} else {
		// Parse $objecttype (ex: project_task)
		$module = $myobject = $objecttype;
		if (preg_match('/^([^_]+)_([^_]+)/i', $objecttype, $regs)) {
			$module = $regs[1];
			$myobject = $regs[2];
		}
	}

	// Generic case for $classpath
	$classpath = $module . '/class';

	// Special cases, to work with non standard path
	if ($objecttype == 'facture' || $objecttype == 'invoice') {
		$langs->load('bills');
		$classpath = 'compta/facture/class';
		$module = 'facture';
		$myobject = 'facture';
	} elseif ($objecttype == 'commande' || $objecttype == 'order') {
		$langs->load('orders');
		$classpath = 'commande/class';
		$module = 'commande';
		$myobject = 'commande';
	} elseif ($objecttype == 'propal') {
		$langs->load('propal');
		$classpath = 'comm/propal/class';
	} elseif ($objecttype == 'supplier_proposal') {
		$langs->load('supplier_proposal');
		$classpath = 'supplier_proposal/class';
	} elseif ($objecttype == 'shipping') {
		$langs->load('sendings');
		$classpath = 'expedition/class';
		$myobject = 'expedition';
		$module = 'expedition_bon';
	} elseif ($objecttype == 'delivery') {
		$langs->load('deliveries');
		$classpath = 'delivery/class';
		$myobject = 'delivery';
		$module = 'delivery_note';
	} elseif ($objecttype == 'contract') {
		$langs->load('contracts');
		$classpath = 'contrat/class';
		$module = 'contrat';
		$myobject = 'contrat';
	} elseif ($objecttype == 'member') {
		$langs->load('members');
		$classpath = 'adherents/class';
		$module = 'adherent';
		$myobject = 'adherent';
	} elseif ($objecttype == 'cabinetmed_cons') {
		$classpath = 'cabinetmed/class';
		$module = 'cabinetmed';
		$myobject = 'cabinetmedcons';
	} elseif ($objecttype == 'fichinter') {
		$langs->load('interventions');
		$classpath = 'fichinter/class';
		$module = 'ficheinter';
		$myobject = 'fichinter';
	} elseif ($objecttype == 'project') {
		$langs->load('projects');
		$classpath = 'projet/class';
		$module = 'projet';
	} elseif ($objecttype == 'task') {
		$langs->load('projects');
		$classpath = 'projet/class';
		$module = 'projet';
		$myobject = 'task';
	} elseif ($objecttype == 'stock') {
		$classpath = 'product/stock/class';
		$module = 'stock';
		$myobject = 'stock';
	} elseif ($objecttype == 'inventory') {
		$classpath = 'product/inventory/class';
		$module = 'stock';
		$myobject = 'inventory';
	} elseif ($objecttype == 'mo') {
		$classpath = 'mrp/class';
		$module = 'mrp';
		$myobject = 'mo';
	} elseif ($objecttype == 'productlot') {
		$classpath = 'product/stock/class';
		$module = 'stock';
		$myobject = 'productlot';
	}

	// Generic case for $classfile and $classname
	$classfile = strtolower($myobject);
	$classname = ucfirst($myobject);
	//print "objecttype=".$objecttype." module=".$module." subelement=".$subelement." classfile=".$classfile." classname=".$classname." classpath=".$classpath;

	if ($objecttype == 'invoice_supplier') {
		$classfile = 'fournisseur.facture';
		$classname = 'FactureFournisseur';
		$classpath = 'fourn/class';
		$module = 'fournisseur';
	} elseif ($objecttype == 'order_supplier') {
		$classfile = 'fournisseur.commande';
		$classname = 'CommandeFournisseur';
		$classpath = 'fourn/class';
		$module = 'fournisseur';
	} elseif ($objecttype == 'supplier_proposal') {
		$classfile = 'supplier_proposal';
		$classname = 'SupplierProposal';
		$classpath = 'supplier_proposal/class';
		$module = 'supplier_proposal';
	} elseif ($objecttype == 'stock') {
		$classpath = 'product/stock/class';
		$classfile = 'entrepot';
		$classname = 'Entrepot';
	} elseif ($objecttype == 'facturerec') {
		$classpath = 'compta/facture/class';
		$classfile = 'facture-rec';
		$classname = 'FactureRec';
		$module = 'facture';
	}

	if (!empty($conf->$module->enabled)) {
		$res = dol_include_once('/' . $classpath . '/' . $classfile . '.class.php');
		if ($res) {
			if (class_exists($classname)) {
				$object = new $classname($db);
				$res = $object->fetch($objectid);
				if ($res > 0) {
					$ret = $object;
				} elseif ($res == 0) {
					$ret = false;
				}
				unset($object);
			} else {
				dol_syslog("Class with classname " . $classname . " is unknown even after the include", LOG_ERR);
			}
		}
	}
	return $ret;
}
