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
 * \file    whatsapp/class/actions_whatsapp.class.php
 * \ingroup whatsapp
 * \brief   Example hook overload.
 *
 * Put detailed description here.
 */

/**
 * Class ActionsWhatsapp
 */
class ActionsWhatsapp
{
	/**
	 * @var DoliDB Database handler.
	 */
	public $db;

	/**
	 * @var string Error code (or message)
	 */
	public $error = '';

	/**
	 * @var array Errors
	 */
	public $errors = array();


	/**
	 * @var array Hook results. Propagated to $hookmanager->resArray for later reuse
	 */
	public $results = array();

	/**
	 * @var string String displayed by executeHook() immediately after return
	 */
	public $resprints;

	/**
	 * @var int		Priority of hook (50 is used if value is not defined)
	 */
	public $priority;


	/**
	 * Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}


	/**
	 * Execute action
	 *
	 * @param	array			$parameters		Array of parameters
	 * @param	CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param	string			$action      	'add', 'update', 'view'
	 * @return	int         					<0 if KO,
	 *                           				=0 if OK but we want to process standard actions too,
	 *                            				>0 if OK and we want to replace standard actions.
	 */
	public function getNomUrl($parameters, &$object, &$action)
	{
		global $db, $langs, $conf, $user;

		// Verificar si el contexto es 'whatsapp_modal' para evitar redundancia
		$context = isset($parameters['context']) ? $parameters['context'] : '';
		if ($context === 'whatsapp_modal') {
			// Si estamos en un contexto específico de WhatsApp, no mostramos el icono
			return 0;
		}

		if ($conf->global->WHATSAPP_SHOW_ON_GETNOMURL
			/* $object instanceof Societe
			&& $object->id > 0
			&& $object->fetch_optionals() > 0
			&& !empty($object->array_options['options_whatsapp_phone_prefix'])
			&& !empty($object->array_options['options_whatsapp_phone_number']) */
			/* true */) {
			dol_include_once('/whatsapp/lib/whatsapp.lib.php');
			$whatsAppMessages = getObjectWhatsappMessages($object->id, $object->element, ['AC_SEND_WHATSAPP', 'AC_RECEIVE_WHATSAPP']);
			$messageCount = count($whatsAppMessages);
			$html = '';
			// Implementación mejorada con estructura HTML más limpia
			$html .= '<span class="wa-icon-container wa-show-history" data-id="' . $object->id . '" data-type="' . $object->element . '" title="' . $langs->trans("WhatsAppHistory") . '">';
			$html .= '<i class="fab fa-whatsapp wa-icon-nomurl"></i>';

			// Solo muestra el badge si hay mensajes
			if ($messageCount > 0) {
				$html .= '<span class="wa-icon-badge">' . $messageCount . '</span>';
			}

			$html .= '</span>';
			$html .= $parameters['getnomurl'];
			$this->resprints = $html;
			return 1;
		}
		return 0;
	}

	/**
	 * Overloading the doActions function : replacing the parent's function with the one below
	 *
	 * @param   array           $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          $action         Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function doActions($parameters, &$object, &$action, $hookmanager)
	{
		global $conf, $user, $langs;

		$error = 0; // Error counter


		/* print_r($parameters); print_r($object); echo "action: " . $action; */
		if (in_array('globalcard', explode(':', $parameters['context'])) && $action == 'confirm_send_whatsapp_form') {
			dol_include_once('/core/class/html.form.class.php');
			dol_include_once('/whatsapp/lib/whatsapp_templates.lib.php');
			dol_include_once('/whatsapp/lib/whatsapp.lib.php');

			$subject = GETPOST('topic', 'restricthtml');
			$content = GETPOST('content', 'restricthtml');
			$whatsappNumber = GETPOST('whatsapp_number', 'alpha');
			$templateId = GETPOST('whatsapp_template', 'int');
			$sendSubject = GETPOST('send_subject', 'alpha') == 'on';
			$sendAsAudio = GETPOST('send_as_audio', 'alpha') == 'on';
			$whatsappPdfFiles = GETPOST('whatsapp_pdf_files', 'array');

			// Validar que el número de WhatsApp no esté vacío
			if (empty($whatsappNumber) || $whatsappNumber == '') {
				setEventMessages($langs->trans('PhoneNumberAreRequired'), null, 'errors');
				$url = $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&token=' . newToken() . '&action=send_whatsapp_form';
				$url .= '&whatsapp_template=' . $templateId;
				$url .= '&topic=' . urlencode($subject);
				$url .= '&content=' . urlencode($content);
				$url .= '&send_subject=' . ($sendSubject ? 'on' : 'off');

				echo '
				<script type="text/javascript">
					window.location.href = "' . $url . '";
				</script>';
				exit;
			}

			//Si el contenido es vacio o nulo error
			if (empty($content) or $content == '') {
				setEventMessages($langs->trans('ContentAreRequired'), null, 'errors');
				//Header back
				//redirect with javascript $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&token=' . newToken() . '&action=send_whatsapp_form'
				$url = $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&token=' . newToken() . '&action=send_whatsapp_form';
				$url .= '&whatsapp_template=' . $templateId;
				$url .= '&topic=' . urlencode($subject);
				$url .= '&content=' . urlencode($content);
				$url .= '&whatsapp_number=' . $whatsappNumber;
				$url .= '&send_subject=' . ($sendSubject ? 'on' : 'off');

				echo '
				<script type="text/javascript">
					window.location.href = "' . $url . '";
				</script>';
				exit;
			}



			//Load subtitutions array
			$substitutionArray = getCommonSubstitutionArray($langs, 0, null, $object); // Note: On email templated edition, this is null because it is related to all type of objects
			$parameters = [];
			complete_substitutions_array($substitutionArray, $langs, $object, $parameters);
			$subjecttosend = '';
			$contenttosend = '';
			if ($sendSubject) {
				$subjecttosend = make_substitutions($subject, $substitutionArray, $langs);
			}
			$contenttosend = make_substitutions($content, $substitutionArray, $langs);
			//Convertir texto compatible con whstapp usando la clase de conversion
			dol_include_once('/whatsapp/includes/class/HtmlToWhatsApp.class.php');
			if ($sendSubject) {
				$subjecttosend = HtmlToWhatsApp::convert($subjecttosend);
			}
			$contenttosend = HtmlToWhatsApp::convert($contenttosend);

			//Array de de ficheros
			$pdfOK = array();
			if (is_array($whatsappPdfFiles) && count($whatsappPdfFiles) > 0) {
				foreach ($whatsappPdfFiles as $file) {
					//comprueba si el fichero existe sino continue
					if (!file_exists($file)) {
						continue;
					}

					$pdfOK[] = $file;
				}
			}

			/*
		Envio del mensaje:
			1 - Comprobar que el telefono de destino es correcto
			2 - Se suele enviar en dos partes, la primera seria not ade audio o texto y la segunda el pdf
			2 - Si es nota de audio se manda nota de audio y luego los ficheros
			3 - Si es texto hay dos formas de funcionar:
					1- Si tiene asunto s envia un card a whastapp
					2- Si no tiene asunto se envia un mensaje normal
		*/

			// Paso 1: Enviar el mensaje según el tipo seleccionado
			$error = 0;

			// Comenzamos el envío
			if ($sendAsAudio) {
				dol_include_once('/whatsapp/includes/TextToSpeech.php');
				$audioBase64 = null;
				$text = htmlToPlainText($contenttosend);

				$fileName =  'TMPAUDIO' . date('YmdHis') . '.mp3';
				$outputFile = dol_buildpath('/whatsapp/tmp/audio/') . $fileName; // Ruta del archivo de salida

				// Configuración de voz y velocidad
				$ttsOptions = [
					'voice' => 'es-ES',  // Voz española
					'speed' => 1         // Velocidad normal (0=lento, 1=rápido)
				];
				// Opción para limpiar el directorio de audios antes de crear uno nuevo
				$clearAudioDirectory = true; // Cambiar a false para mantener archivos anteriores
				if (TextToSpeech::googleTranslateTTSLong($text, 'es', $outputFile, $ttsOptions, $clearAudioDirectory)) {

					//Convierte el archivo de audio a base64 si existe
					if (file_exists($outputFile)) {
						$audioData = file_get_contents($outputFile);
						$audioBase64 = base64_encode($audioData);
						// Elimina el archivo temporal después de convertirlo
						unlink($outputFile);
						/* var_dump($res);
						var_dump($outputFile);
						die(); */
					}
				}

				if ($audioBase64) {
					// Enviar el audio como nota de voz
					$messageResult = sendWhapiAudio(
						$object,
						$whatsappNumber,
						$audioBase64,
						$text
					);
				} else {
					$error++;
					setEventMessages($langs->trans("WhatsappAudioGenerationError"), null, 'errors');
				}
			} else {
				// Enviar como texto
				$finalMessage = $contenttosend;

				// Si tiene asunto, combinamos asunto y mensaje en uno solo
				if ($sendSubject && !empty($subjecttosend)) {
					$finalMessage = "*Asunto:* " . $subjecttosend . "\n\n" . $contenttosend;
				}

				// Enviamos un solo mensaje con la función simplificada
				$messageResult = sendWhapiText(
					$object,
					$whatsappNumber,
					$finalMessage
				);

				// La función sendWhapiText ya maneja los errores automáticamente
				if (!$messageResult) {
					$error++;
				}
			}

			// Paso 3: Enviar los archivos PDF adjuntos
			if (count($pdfOK) > 0 && !$error) {
				foreach ($pdfOK as $file) {
					// Convertir el archivo a base64
					$fileContent = file_get_contents($file);
					$fileBase64 = base64_encode($fileContent);

					// Extraer el nombre del archivo
					$fileName = basename($file);

					// Opciones para el envío del PDF
					$options = [
						'fileName' => $fileName,
						'caption' => $fileName // Podríamos usar el asunto como caption también
					];

					// Enviar el PDF
					$pdfResult = sendWhapiDocument(
						$object,
						$whatsappNumber,
						$fileBase64,
						$fileName,
						'document',
						$options
					);
				}
			}

			// Mostrar mensaje de éxito o error
			if (!$error) {
				// Mostrar mensaje de éxito o error
				/*
				$actionmsg = $contenttosend;
				//Si incluye nota de audio indicalo en $actionmsg
				if ($sendAsAudio) $actionmsg .= '<hr><br>' . $langs->trans('SendAsAudio', $langs->trans('Yes'));
				//Si incluye pdfs indicalo en $actionmsg
				if (count($pdfOK) > 0) {
					$actionmsg .= '<hr><br><strong>' . $langs->trans('IncludePDFFiles', $langs->trans('Yes')) . '</strong>';
					$actionmsg .= '<ul>';
					foreach ($pdfOK as $file) {
						// Extraer el nombre del archivo
						$fileName = basename($file);
						$actionmsg .= '<li>' . $fileName . '</li>';
					}
					$actionmsg .= '</ul>';
				}

				$object->actionmsg2 = $langs->trans('WhatsappMessageFromSubject', $subjecttosend);
				$object->actionmsg = $actionmsg;
				$object->actiontypecode = 'AC_SEND_WHATSAPP';

				$object->call_trigger('SEND_WHATSAPP', $user); */
				setEventMessage($langs->trans("WhatsappMessageSent"));
			}

			// Redireccionar de vuelta a la página del objeto
			$url = $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&token=' . newToken();
			echo '<script type="text/javascript">window.location.href = "' . $url . '";</script>';
			exit;
		}

		/* 	if (!$error) {
			$this->results = array('myreturn' => 999);
			$this->resprints = 'A text to show';
			return 0; // or return 1 to replace standard code
		} else {
			$this->errors[] = 'Error message';
			return -1;
		} */
	}


	/**
	 * Overloading the doMassActions function : replacing the parent's function with the one below
	 *
	 * @param   array           $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          $action         Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function doMassActions($parameters, &$object, &$action, $hookmanager)
	{
		global $conf, $user, $langs;

		$error = 0; // Error counter

		/* print_r($parameters); print_r($object); echo "action: " . $action; */
		if (in_array($parameters['currentcontext'], array('somecontext1', 'somecontext2'))) {		// do something only for the context 'somecontext1' or 'somecontext2'
			foreach ($parameters['toselect'] as $objectid) {
				// Do action on each object id
			}
		}

		if (!$error) {
			$this->results = array('myreturn' => 999);
			$this->resprints = 'A text to show';
			return 0; // or return 1 to replace standard code
		} else {
			$this->errors[] = 'Error message';
			return -1;
		}
	}

	public function printCommonFooter()
	{
		global $conf, $user, $langs, $object, $action, $hookmanager;
		dol_include_once('/whatsapp/lib/whatsapp.lib.php');
		$whatConf = getWhatsappConf();
		if ($user->rights->whatsapp->read && $whatConf && $object->id > 0) {
			// Obtener cantidad de mensajes para este objeto
			$whatsAppMessages = getObjectWhatsappMessages($object->id, $object->element, ['AC_SEND_WHATSAPP', 'AC_RECEIVE_WHATSAPP'], true, ($object->fk_soc ? $object->fk_soc : 0));
			$messageCount = count($whatsAppMessages);

			print '
			<div class="wa-floating-container">
				<!-- Botón principal de WhatsApp con efectos mejorados -->
				<div class="wa-main-button wa-pulse">
					<div class="wa-ripple"></div>
					<div class="wa-ripple"></div>
					<!-- Icono mejorado -->
					<img src="' . dol_buildpath('/whatsapp/img/whatsapp.png', 1) . '" alt="WhatsApp">
					<!-- Badge rediseñado para mejor visibilidad -->
					<div class="wa-badge">' . ($messageCount > 0 ? $messageCount : '0') . '</div>
				</div>

				<!-- Opciones del FAB con diseño moderno -->
				<div class="wa-options">
					<a href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&token=' . newToken() . '&action=send_whatsapp_form#whatsapp_number" class="wa-option">
						<div class="wa-option-icon">
							<i class="fas fa-paper-plane"></i>
						</div>
						<span class="wa-option-text">' . $langs->trans("WhatsappSendMessage") . '</span>
					</a>
					<a href="#" class="wa-option wa-show-history" data-id="' . $object->id . '" data-type="' . $object->element . '" ' . ($object->fk_soc ? 'data-socid="' . $object->fk_soc . '"' : '') . '>
						<div class="wa-option-icon">
							<i class="fas fa-history"></i>
						</div>
						<span class="wa-option-text">
							' . $langs->trans("MessageHistory") . '
							<span class="wa-badge-small">' . ($messageCount > 0 ? $messageCount : '0') . '</span>
						</span>
					</a>
				</div>
			</div>
			';
		}
		if ($user->rights->whatsapp->read && $action == 'send_whatsapp_form') {
			dol_include_once('/core/class/html.form.class.php');
			dol_include_once('/whatsapp/lib/whatsapp_templates.lib.php');
			//si object tiene la funcion de fetch_thirdparty lanzala
			$whastappNumber = '';
			if ($object instanceof Societe) {

				$whastappNumber = str_replace(' ', '', ($object->array_options['options_whatsapp_phone_prefix'] . $object->array_options['options_whatsapp_phone_number']));
			} else {
				if (method_exists($object, 'fetch_thirdparty')) {
					$object->fetch_thirdparty();
					if ($object->thirdparty) {
						$object->thirdparty->fetch_optionals();
						$whastappNumber = str_replace(' ', '', ($object->thirdparty->array_options['options_whatsapp_phone_prefix'] . $object->thirdparty->array_options['options_whatsapp_phone_number']));
						if (empty($whastappNumber) || $whastappNumber == '') {
							$whastappNumber = str_replace(' ', '', ($conf->global->MAIN_WHATSAPP_PHONE_PREFIX . $object->thirdparty->phone));
						}
					}
				}
			}
			//Load sustitutions
			//Load subtitutions array
			$substitutionArray = getCommonSubstitutionArray($langs, 0, null, $object); // Note: On email templated edition, this is null because it is related to all type of objects
			$parameters = [];
			complete_substitutions_array($substitutionArray, $langs, $object, $parameters);
			//Load templates of elements
			$whatsappTemplatesArray = getWhatsappTemplatesArray($object->element ?? null);
			$templates = array_column($whatsappTemplatesArray, 'label', 'rowid');


			$form = new Form($this->db);
			require_once DOL_DOCUMENT_ROOT . '/core/class/doleditor.class.php';
			$doleditor = new DolEditor('whatsapp_template_preview', '');
			$editor =  $doleditor->Create(1);

			$upload_dir = $conf->{$object->element}->multidir_output[$conf->entity];
			$objectref =  dol_sanitizeFileName($object->ref);
			$fullpath = $upload_dir . '/' . $objectref;

			/* $upload_dir = $conf->hospederia->multidir_output[$object->entity ? $object->entity : $conf->entity] . "/reserva/" . get_exdir(0, 0, 0, 1, $object); */
			$fileArray = [];
			$hookmanager->initHooks(['whatsappDao']);
			$reshook = $hookmanager->executeHooks('getObjectDirFullPath', array(), $object, $action); // Note that $action and $object may have been modified by some hooks
			if ($reshook > 0) {
				$fullpath = $hookmanager->resPrint;
			}
			// Build file list

			$fileArray = dol_dir_list($fullpath, "files", 0, '\.pdf$',   '(\.meta|_preview.*\.png)$', $sortfield, (strtolower($sortorder) == 'desc' ? SORT_DESC : SORT_ASC), 1);

			if (count($fileArray) > 0) {
				$fileArray = array_column($fileArray, 'name', 'fullname');
			}




			$formquestion = array(
				/* array('type' => 'other', 'name' => 'socid', 'label' => $langs->trans("SelectThirdParty"), 'value' => $form->select_company($object->socid, 'socid', '(s.client=1 OR s.client=2 OR s.client=3)', 1)),
				array('type' => 'date', 'name' => 'newdate', 'label' => $langs->trans("Date"), 'value' => dol_now()) */
				array('type' => 'text', 'name' =>
				'whatsapp_number', 'label' => '<b>' . $langs->trans('WhatsAppPhoneNumber') . '</b>', 'moreattr' => 'required', 'morecss' => 'fieldrequired', 'value' => GETPOST('whatsapp_number', 'alpha') ? GETPOST('whatsapp_number', 'alpha') : $whastappNumber),
				//plantillas
				array('type' => 'select', 'name' => 'whatsapp_template', 'label' => $langs->trans("WhatsappTemplates"), 'default' => GETPOST('whatsapp_template', 'int'), 'morecss' => 'minwidth500', 'values' => $templates),
				array('type' => 'checkbox', 'label' => $form->textwithpicto($langs->trans('DoSendSubject'), $langs->trans('DoSendSubjectTooltip')), 'value' => true, 'name' => 'send_subject'),
				array('type' => 'text', 'name' => 'topic', 'label' => $langs->trans("Topic"), 'morecss' => 'minwidth500', 'value' => GETPOST('topic', 'alpha')),
				array('type' => 'other', 'label' => $langs->trans("Content"), 'value' => $editor),
				array('type' => 'onecolumn', 'value' => '
				<div class="tagtr" id="audio_div" style="display:none !important;">
					<div class="tagtd">' . $langs->trans("Audio") . '</div>
					<div class="tagtd" style="display:flex !important;flex-direction:column !important;">

					<div id="audio_example" class="audio_example"></div><div class="butAction" id="generate_audio">' . $form->textwithpicto($langs->trans('GenerateAudioExample'), $langs->trans('GenerateAudioExampleTooltip')) . '</div>
					</div>
				</div>
				'),
				array('type' => 'checkbox', 'label' => $form->textwithpicto($langs->trans('SendAsAudio'), $langs->trans('SendAsAudioTooltip')), 'value' => false, 'name' => 'send_as_audio'),
				(count($fileArray) > 0 ?
					//select con los datos
					array('type' => 'select', 'name' => 'whatsapp_pdf_files', 'label' => $langs->trans("IncludePDFFiles"), 'morecss' => 'minwidth500', 'values' => $fileArray, 'moreattr' => 'multiple')
					: array()
				),

				array('type' => 'hidden', 'name' => 'content'),
				array('type' => 'other', 'value' => '
						<script>
							// Guardar los arrays como objetos JSON
							var whatsappTemplates = ' . json_encode($whatsappTemplatesArray) . ';
							var substitutionArray = ' . json_encode($substitutionArray) . ';

							$(function() {
								// Cache de los elementos DOM para mejor rendimiento
								var $templateSelect = $("#whatsapp_template");
								var $contentPreview = $("#whatsapp_template_preview");
								var $contentField = $("#content");
								var $topicField = $("#topic");

								// Detectar si CKEDITOR está disponible y activo
								var EDITOR = null;
								var hasCKEditor = false;

								// Esperar un momento para que CKEDITOR se inicialice si existe
								setTimeout(function() {
									if (typeof CKEDITOR !== "undefined" && CKEDITOR.instances && CKEDITOR.instances.whatsapp_template_preview) {
										EDITOR = CKEDITOR.instances.whatsapp_template_preview;
										hasCKEditor = true;

										// Cuando el contenido del editor cambie, actualiza el campo oculto
										EDITOR.on("change", function() {
											$contentField.val(EDITOR.getData());
										});
									}
								}, 500);

								// Función optimizada para aplicar sustituciones
								function applySubstitutions(text) {
									if (!text) return "";

									// Reemplazar todas las variables en una sola pasada
									return text.replace(/__([A-Z0-9_]+)__/g, function(match) {
										return substitutionArray[match] !== undefined ? substitutionArray[match] : match;
									});
								}

								// Función para obtener el contenido actual (compatible con textarea y CKEditor)
								function getCurrentContent() {
									if (hasCKEditor && EDITOR) {
										return EDITOR.getData();
									} else {
										return $contentPreview.val();
									}
								}

								// Función para establecer el contenido (compatible con textarea y CKEditor)
								function setContent(content) {
									if (hasCKEditor && EDITOR) {
										EDITOR.setData(content);
									} else {
										$contentPreview.val(content);
									}
									// Actualizar también el campo oculto
									$contentField.val(content);
								}

								// Si no hay CKEditor, sincronizar manualmente el textarea con el campo oculto
								if (!hasCKEditor) {
									$contentPreview.on("input change", function() {
										$contentField.val($(this).val());
									});
								}

								// Función para actualizar el contenido de la plantilla
								function updateTemplateContent() {
									var selectedTemplateId = $templateSelect.val();
									var selectedTemplate = whatsappTemplates[selectedTemplateId] || {};

									// Obtener contenido y tema, con valores por defecto vacíos
									var rawContent = selectedTemplate.content || "";
									var rawTopic = selectedTemplate.topic || "";

									// Aplicar sustituciones
									var processedContent = applySubstitutions(rawContent);
									var processedTopic = applySubstitutions(rawTopic);

									// Actualizar los campos usando la función compatible
									setContent(processedContent);
									$topicField.val(processedTopic);

									// Generar audio si hay contenido
									if(processedContent != "" && processedContent != null && processedContent.length > 0){
										generateAudio(processedContent);
									}
								}

								// Función para generar audio
								function generateAudio(text){
									// Llama a la url de conversión de texto a audio
									$.ajax({
										url: "' . dol_buildpath('/whatsapp/ajax/textToAudio.php', 1) . '",
										type: "POST",
										data: { text: text },
										dataType: "json",
										success: function(response) {
											if (response.status === "success") {
												// Crear el elemento de audio
												var audio = new Audio("data:audio/mpeg;base64," + response.audio);
												audio.controls = true;
												audio.id = "audio_player";
												$("#audio_example").html(audio);
											} else {
												console.error("Error al generar el audio:", response.message);
											}
										},
										error: function(xhr, status, error) {
											console.error("Error en la solicitud AJAX:", error);
										}
									});
								}

								// Asignar evento change al selector de plantillas
								$templateSelect.on("change", updateTemplateContent);

								// Inicialización: seleccionar primera opción si no hay selección
								if (!$templateSelect.val() && $templateSelect.find("option").length) {
									$templateSelect.val($templateSelect.find("option:first").val()).trigger("change");
								} else {
									// O inicializar con la plantilla ya seleccionada
									setTimeout(function() {
										updateTemplateContent();
									}, 600);
								}

								// Botón de generar audio - compatible con ambos modos
								$("#generate_audio").on("click", function() {
									var content = getCurrentContent();
									if (content != "" && content != null && content.length > 0) {
										generateAudio(content);
									} else {
										$.jnotify("' . $langs->trans('NoContentToGenerateAudio') . '", "error");
									}
								});

								// Toggle del div de audio cuando se activa el checkbox
								$("#send_as_audio").on("change", function() {
									if ($(this).is(":checked")) {
										$("#audio_div").show();
									} else {
										$("#audio_div").hide();
									}
								});

								// Cambiar el nombre del atributo para enviar como array
								$("#whatsapp_pdf_files").attr("name", "whatsapp_pdf_files[]");
							});
						</script>')

			);
			// Ask confirmatio to clone
			$formconfirm = $form->formconfirm(
				$_SERVER["PHP_SELF"] . '?id=' . $object->id,
				$langs->trans('WhatsappSendMessage'),
				$langs->trans('WhatsappConfirmSendMessage', $object->ref),
				'confirm_send_whatsapp_form',
				$formquestion,
				'yes',
				0,
				($_SESSION['dol_screenheight'] > 0 ? $_SESSION['dol_screenheight'] * 0.65 : 800),
				($_SESSION['dol_screenwidth'] > 0 ? $_SESSION['dol_screenwidth'] * 0.50 : 800),

			);

			print  '<div style="width:100% !important;text-align:left !important">' . $formconfirm . '</div>';
			/* return 1; */
		}
	}


	/* Add here any other hooked methods... */
	public function printTopRightMenu($parameters)
	{
		global $langs, $conf, $user;
		dol_include_once('/whatsapp/lib/whatsapp.lib.php');
		// Verificar permisos básicos - solo mostrar el icono a usuarios con permiso de lectura
		if (empty($user->rights->whatsapp->read)) {
			return 0;
		}

		$langs->load("whatsapp@whatsapp");
		// Procesar acción de logout si viene del dropdown
		$action = GETPOST('action', 'aZ09');

		$html = '';
		$qrImageData = '';
		$isConnected = false;

		// Determinar si el usuario tiene permisos de administración para el desplegable
		$hasAdminRights = !empty($user->rights->whatsapp->admin);
		$whatConf = getWhatsappConf();

		// Verificar si la configuración está completa
		if ($whatConf) {
			// Verificar estado de la conexión
			try {

				$instanceStatus = getInstanceStatus($whatConf['server_token'], $whatConf['server_url']);
				$isConnected = $instanceStatus['connected'] ?? false;
				/* $instanceStatus = true;
				$isConnected =  true; */
			} catch (\Throwable $th) {
			}

			// Acciones
			if ($action == 'whatsapp_logout' && !empty($user->rights->whatsapp->admin) && $isConnected) {
				$result = logoutInstance();
				if ($result) {
					setEventMessages($langs->trans("WhatsappLogoutSuccess"), null, 'mesgs');
				} else {
					setEventMessages($langs->trans("WhatsappLogoutError"), null, 'errors');
				}
				// Redirección por javascript a la página anterior y borrar el history
				echo '<script type="text/javascript">
                window.location.href = "' . $_SERVER['PHP_SELF'] . '";
            </script>';
				exit;
			}

			if ($isConnected) {
				$iconClass = "fab fa-whatsapp text-success";
				$statusText = $langs->trans("WhatsappConnected");
				$statusTextConnection = $langs->trans("WhatsappConnectedToInstance", $instanceStatus['instance_name'] ?? '');
				$statusBadge = '<span class="badge badge-status4 badge-status" style="background-color:#25D366;color:white;font-size:10px;padding:3px 6px;border-radius:3px;margin-left:4px;vertical-align:middle;">' . $statusText . '</span>';
			} else {
				$iconClass = "fab fa-whatsapp text-danger";
				$statusText = $langs->trans("WhatsappDisconnected", $instanceStatus['instance_name'] ?? '');
				$statusTextConnection = $statusText;
				$statusBadge = '<span class="badge badge-status8 badge-status" style="background-color:#FF4136;color:white;font-size:10px;padding:3px 6px;border-radius:3px;margin-left:4px;vertical-align:middle;">' . $langs->trans("WhatsappDisconnected") . '</span>';
			}

			// Contenido para el dropdown
			$dropdownContent = '';

			// Cabecera del dropdown
			$dropdownContent .= '
			<style>.aversion{display:none !important}</style>
        <div style="padding:15px;background:linear-gradient(135deg,#25D366 0%,#128C7E 100%);color:white;text-align:center;">
            <h4 style="margin:0 0 10px 0;font-size:1em;font-weight:900;">' . $langs->trans("Estado de WhatsApp") . '</h4>
            <span style="padding:4px 8px;background:rgba(255,255,255,0.25);color:white;border-radius:4px;font-size:1em;display:inline-block;">' . $statusTextConnection . '</span>
        </div>';

			// Cuerpo del dropdown
			$dropdownContent .= '<div style="padding:15px;background:white;">';

			if (!$isConnected) {
				$dropdownContent .= '<div style="margin:10px auto;text-align:center;background:#f8f9fa;padding:15px;border-radius:8px;max-width:250px;">';

				// Obtener QR Code
				try {
					$qrCode = $instanceStatus['qr_code'] ?? null;
					if ($qrCode) {
						$qrImageData = $qrCode;
						$dropdownContent .= '<img src="' . $qrImageData . '" alt="QR Code" style="max-width:100%;height:auto;">';
						$dropdownContent .= '<div style="margin-top:8px;font-size:12px;color:#666;">' . $langs->trans("Escanea el código QR para conectar") . '</div>';
					}
				} catch (Exception $e) {
					$dropdownContent .= '<div style="color:#dc3545;padding:10px;border:1px solid #dc3545;border-radius:4px;">
                    <i class="fa fa-exclamation-circle"></i> ' . $langs->trans("Error al obtener código QR") . ':<br>' . $e->getMessage() . '
                </div>';
				}

				$dropdownContent .= '</div>';
			} else {
				// Información adicional cuando está conectado
				$dropdownContent .= '
            <div style="margin:8px 0;display:flex;align-items:center;"><i class="fa fa-check-circle text-success" style="margin-right:10px;width:20px;text-align:center;"></i> ' . $langs->trans("WhatsApp está listo para usar") . '</div>
            <div style="margin:8px 0;display:flex;align-items:center;"><i class="fa fa-info-circle" style="margin-right:10px;width:20px;text-align:center;"></i> ' . $langs->trans("Información de conexión") . '</div>';
			}

			$dropdownContent .= '</div>';

			// Pie del dropdown con botones
			$dropdownContent .= '<div style="background:#f8f9fa;padding:15px;border-top:1px solid #eee;text-align:center;display:flex;justify-content:space-between;">';

			if ($user->admin) {
				$dropdownContent .= '<a href="' . dol_buildpath('whatsapp/admin/setup.php', 1) . '" class="button-top-menu-dropdown butAction" style="margin:0 5px;"><i class="fa fa-cog"></i> ' . $langs->trans("Configuración") . '</a>';
			} else {
				$dropdownContent .= '<div></div>';
			}

			if ($isConnected && $user->admin) {
				$dropdownContent .= '<a href="' . $_SERVER["PHP_SELF"] . '?action=whatsapp_logout&token=' . newToken() . '" class="button-top-menu-dropdown butActionDelete" style="background-color:#ff6961;margin:0 5px;color:#d63031;"><i class="fa fa-sign-out-alt"></i> ' . $langs->trans("Desconexión") . '</a>';
			} else {
				$dropdownContent .= '<div></div>';
			}

			$dropdownContent .= '</div>';
		} else {
			// No configurado
			$iconClass = "fab fa-whatsapp text-muted";
			$statusText = $langs->trans("No configurado");
			$statusBadge = '<span class="badge badge-status0 badge-status" style="background-color:#AAAAAA;color:white;font-size:11px;padding:3px 6px;border-radius:3px;margin-left:4px;vertical-align:middle;">' . $statusText . '</span>';

			// Contenido del dropdown para WhatsApp no configurado
			$dropdownContent = '
        <div style="padding:15px;background:linear-gradient(135deg,#25D366 0%,#128C7E 100%);color:white;text-align:center;">
            <h4 style="margin:0 0 10px 0;font-size:16px;font-weight:600;">' . $langs->trans("Estado de WhatsApp") . '</h4>
            <span style="padding:4px 8px;background:rgba(255,255,255,0.25);color:white;border-radius:4px;font-size:12px;display:inline-block;">' . $statusText . '</span>
        </div>
        <div style="padding:15px;background:white;">
            <div style="margin:8px 0;display:flex;align-items:center;">
                <i class="fa fa-exclamation-triangle text-warning" style="margin-right:10px;width:20px;text-align:center;"></i> ' . $langs->trans("WhatsApp necesita configuración") . '
            </div>
        </div>';

			if ($user->admin) {
				$dropdownContent .= '
            <div style="background:#f8f9fa;padding:15px;border-top:1px solid #eee;text-align:center;">
                <a href="' . dol_buildpath('whatsapp/admin/setup.php', 1) . '" class="button-top-menu-dropdown" style="background-color:#128C7E;color:white;padding:6px 12px;border-radius:4px;text-decoration:none;display:inline-block;"><i class="fa fa-cog"></i> ' . $langs->trans("Configurar") . '</a>
            </div>';
			}
		}

		// Generar HTML del dropdown al estilo Dolibarr con las mejoras
		if ($hasAdminRights) {
			$html .= '
        <div class="inline-block">
            <div class="classfortooltip inline-block login_block_elem" style="padding: 0px; padding-right: 3px !important;" title="' . $langs->trans("Estado de WhatsApp") . '">
                <div id="whatsapp-menu-dropdown" class="dropdown inline-block">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown" style="display:inline-flex; align-items:center; height:100%; padding:0 3px;">
                        <span class="' . $iconClass . ' atoplogin valignmiddle" style="font-size:16px;"></span>' . $statusBadge . '
                    </a>
                    <div class="dropdown-menu dropdown-menu-right">
                        ' . $dropdownContent . '
                    </div>
                </div>
            </div>
        </div>';

			// Agregar script para manejar el comportamiento del dropdown
			$html .= '
        <script>
        $(document).ready(function() {
            $("#whatsapp-menu-dropdown > a.dropdown-toggle").click(function(e) {
                e.preventDefault();
                if($(this).parent().hasClass("open")) {
                    $(this).parent().removeClass("open");
                } else {
                    $(".dropdown").removeClass("open");
                    $(this).parent().addClass("open");
                }
                return false;
            });

            // Cerrar al hacer clic fuera del dropdown
            $(document).click(function(e) {
                if(!$(e.target).closest("#whatsapp-menu-dropdown").length) {
                    $("#whatsapp-menu-dropdown").removeClass("open");
                }
            });

            // Evitar que el dropdown se cierre al hacer clic dentro
            $("#whatsapp-menu-dropdown .dropdown-menu").click(function(e) {
                e.stopPropagation();
            });

            // Auto-refrescar el QR code cada 30 segundos si está desconectado
            var isConnected = ' . ($isConnected ? 'true' : 'false') . ';
            if (!isConnected) {
                setInterval(function() {
                    if ($("#whatsapp-menu-dropdown").hasClass("open")) {
                        window.location.reload();
                    }
                }, 30000);
            }
        });
        </script>';
		} else {
			// Si solo tiene permisos de lectura, mostrar solo el icono con estado (sin dropdown)
			$html .= '
        <div class="inline-block">
            <div class="classfortooltip inline-block login_block_elem" style="padding: 0px; padding-right: 3px !important;" title="' . $langs->trans("Estado de WhatsApp") . '">
                <div style="display:inline-flex; align-items:center; height:100%; padding:0 3px;">
                    <span class="' . $iconClass . ' atoplogin valignmiddle" style="font-size:16px;"></span>' . $statusBadge . '
                </div>
            </div>
        </div>';
		}

		$this->resprints = $html;
		return 0;
	}
}
