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
 *
 * Library javascript to enable Browser notifications
 */

if (!defined('NOREQUIREUSER')) {
	define('NOREQUIREUSER', '1');
}
if (!defined('NOREQUIREDB')) {
	define('NOREQUIREDB', '1');
}
if (!defined('NOREQUIRESOC')) {
	define('NOREQUIRESOC', '1');
}
if (!defined('NOCSRFCHECK')) {
	define('NOCSRFCHECK', 1);
}
if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', 1);
}
if (!defined('NOLOGIN')) {
	define('NOLOGIN', 1);
}
if (!defined('NOREQUIREMENU')) {
	define('NOREQUIREMENU', 1);
}
if (!defined('NOREQUIREHTML')) {
	define('NOREQUIREHTML', 1);
}
if (!defined('NOREQUIREAJAX')) {
	define('NOREQUIREAJAX', '1');
}


/**
 * file    whatsapp/js/whatsapp.js.php
 * ingroup whatsapp
 * brief   JavaScript file for module Whatsapp.
 */

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
	$res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
}
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
	$i--; $j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) {
	$res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/../main.inc.php")) {
	$res = @include substr($tmp, 0, ($i + 1))."/../main.inc.php";
}
// Try main.inc.php using relative path
if (!$res && file_exists("../../main.inc.php")) {
	$res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
	$res = @include "../../../main.inc.php";
}
if (!$res) {
	die("Include of main fails");
}

// Define js type
header('Content-Type: application/javascript');
// Important: Following code is to cache this file to avoid page request by browser at each Dolibarr page access.
// You can use CTRL+F5 to refresh your browser cache.
if (empty($dolibarr_nocache)) {
	header('Cache-Control: max-age=3600, public, must-revalidate');
} else {
	header('Cache-Control: no-cache');
}

$langs->load("whatsapp@whatsapp");
?>

/* Javascript library of module Whatsapp */

$(document).ready(function() {
				// Variables para la animación
				var waMainButton = $(".wa-main-button");
				var waOptions = $(".wa-options");
				var isActive = false;

				// Función para alternar las opciones
				function toggleOptions() {
					if (isActive) {
						waOptions.removeClass("active");
						waMainButton.removeClass("active");
					} else {
						waOptions.addClass("active");
						waMainButton.addClass("active");
					}
					isActive = !isActive;
				}

				// Asignar evento click al botón principal
				waMainButton.click(function() {
					toggleOptions();
				});

				// Ocultar opciones si se hace clic fuera
				$(document).click(function(e) {
					if (!$(e.target).closest(".wa-floating-container").length && isActive) {
						toggleOptions();
					}
				});

				// Crear el modal de chat dinámicamente y añadirlo al DOM
				if ($('#wa-chat-modal').length === 0) {
					var modalHtml = '<div id="wa-chat-modal" class="wa-modal">' +
						'<div class="wa-modal-content" id="wa-modal-draggable">' +
							'<div class="wa-chat-header">' +
								'<div class="wa-chat-header-info">' +
									'<img src="<?php echo dol_buildpath('/whatsapp/img/whatsapp.png', 1); ?>" alt="WhatsApp" class="wa-chat-avatar">' +
									'<div>' +
										'<h3><?php echo $langs->trans("WhatsAppHistory"); ?></h3>' +
										'<p><span id="wa-chat-contact-name"></span></p>' +
									'</div>' +
								'</div>' +
								'<div class="wa-chat-controls">' +
									'<span class="wa-resize-modal" id="wa-toggle-fullscreen"><i class="fas fa-expand"></i></span>' +
									'<span class="wa-close">&times;</span>' +
								'</div>' +
							'</div>' +
							'<div class="wa-chat-body" id="wa-chat-body">' +
								'<div class="wa-chat-loader">' +
									'<div class="wa-spinner"></div>' +
									'<p><?php echo $langs->trans("LoadingMessages"); ?>...</p>' +
								'</div>' +
							'</div>' +
						'</div>' +
					'</div>';

					$('body').append(modalHtml);
				}

				// Variables para controlar el estado del modal
				var $modal = $("#wa-modal-draggable");
				var $header = $modal.find(".wa-chat-header");
				var isFullScreen = false;
				var originalPosition = {};
				var originalSize = {};

				// Inicializar arrastre del modal (mejorado)
				function initDragModal() {
					if (!$modal || !$header) return;

					// Guardar posición y tamaño original para referencia
					originalSize = {
						width: $modal.width(),
						height: $modal.height()
					};

					var isDragging = false;
					var startX, startY, startLeft, startTop;

					// Mouse down en la cabecera inicia el arrastre
					$header.on("mousedown", function(e) {
						if (isFullScreen) return; // No arrastrar en modo pantalla completa
						if ($(e.target).closest(".wa-resize-modal, .wa-close").length > 0) return; // No iniciar arrastre en botones

						isDragging = true;
						startX = e.clientX;
						startY = e.clientY;
						startLeft = $modal.position().left;
						startTop = $modal.position().top;

						// Eliminar cualquier transición para un arrastre más fluido
						$modal.css("transition", "none");

						e.preventDefault();
					});

					// Movimiento del mouse cuando se está arrastrando
					$(document).on("mousemove", function(e) {
						if (!isDragging) return;

						var newLeft = startLeft + (e.clientX - startX);
						var newTop = startTop + (e.clientY - startY);

						// Asegurar que el modal no salga de la pantalla
						newLeft = Math.max(0, Math.min(window.innerWidth - $modal.width(), newLeft));
						newTop = Math.max(0, Math.min(window.innerHeight - 100, newTop));

						$modal.css({
							left: newLeft + "px",
							top: newTop + "px",
							margin: 0
						});
					});

					// Fin del arrastre
					$(document).on("mouseup", function() {
						if (isDragging) {
							isDragging = false;

							// Guardar posición actual
							originalPosition = {
								left: $modal.position().left,
								top: $modal.position().top
							};

							// Restaurar transiciones
							$modal.css("transition", "");
						}
					});
				}

				// Inicializar arrastre
				initDragModal();

				// Manejar cambio a pantalla completa
				$("#wa-toggle-fullscreen").click(function() {
					var $icon = $(this).find("i");

					// Alternar modo pantalla completa
					if (isFullScreen) {
						// Volver a modo normal
						$modal.removeClass("wa-modal-fullscreen");
						$icon.removeClass("fa-compress").addClass("fa-expand");

						// Restaurar posición anterior si existe
						if (originalPosition.left !== undefined && originalPosition.top !== undefined) {
							$modal.css({
								left: originalPosition.left + "px",
								top: originalPosition.top + "px",
								margin: 0
							});
						} else {
							// Si no hay posición guardada, centrar en pantalla
							$modal.css({
								left: "",
								top: "",
								margin: "2% auto"
							});
						}
					} else {
						// Guardar la posición actual antes de maximizar
						if ($modal.css("margin") === "0px" || $modal.css("margin") === "0px 0px") {
							originalPosition = {
								left: $modal.position().left,
								top: $modal.position().top
							};
						}

						// Cambiar a modo pantalla completa
						$modal.addClass("wa-modal-fullscreen");
						$icon.removeClass("fa-expand").addClass("fa-compress");

						// Centrar en pantalla en modo pantalla completa
						$modal.css({
							left: "",
							top: "",
							margin: "1% auto"
						});
					}

					isFullScreen = !isFullScreen;
				});

				// Modal de historial
				$(".wa-show-history").click(function(e) {
					e.preventDefault();
					var objectId = $(this).data("id");
					var objectType = $(this).data("type");
					var socid = $(this).data("socid") || 0;

					// Cerrar el menú de opciones
					if (isActive) {
						toggleOptions();
					}

					// Mostrar modal en posición predeterminada
					$modal.css({
						left: "",
						top: "",
						margin: "2% auto"
					}).show();
					$("#wa-chat-modal").show();

					// Restablecer el estado de pantalla completa
					if (isFullScreen) {
						$("#wa-toggle-fullscreen").click();
					}

					// Cargar mensajes con AJAX
					$.ajax({
						url: "<?php echo dol_buildpath('/whatsapp/ajax/getMessages.php', 1); ?>",
						type: "POST",
						data: {
							id: objectId,
							type: objectType,
							context: 'whatsapp_modal', // Añadimos el contexto para evitar redundancia
							socid: socid
						},
						dataType: "json",
						success: function(data) {
							var chatBody = $("#wa-chat-body");
							chatBody.html(""); // Limpiar contenedor

							if (data.success) {
								$("#wa-chat-contact-name").text(data.contactName);

								if (data.messages && data.messages.length > 0) {
									// Mostrar mensajes
									$.each(data.messages, function(i, msg) {
										console.log(msg);
										// Construir la etiqueta de instancia si existe
										var instanceTag = "";
										if (msg.array_options?.options_whatsapp_server_instance_name && msg.array_options?.options_whatsapp_server_instance_name?.trim() !== "") {
											instanceTag = '<div class="wa-instance-container"><div class="wa-instance-tag">' + msg.array_options?.options_whatsapp_server_instance_name + '</div></div>';
										}

										// Construir la información del objeto vinculado
										var linkedObjectHtml = "";
										if (msg.linkedobject_html && msg.linkedobject_html.trim() !== "") {
											linkedObjectHtml = '<div class="wa-message-linked-object">' + msg.linkedobject_html + '</div>';
										} else if (msg.linkedobject && msg.linkedobject.trim() !== "") {
											linkedObjectHtml = '<div class="wa-message-linked-object">' + msg.linkedobject + '</div>';
										}

										// Añadir clase adicional al contenido si hay objeto vinculado
										var contentClass = linkedObjectHtml ? "wa-message-content-with-link" : "";

										var messageHtml = '<div class="wa-message ' +
											(msg.direction === "outgoing" ? "wa-outgoing" : "wa-incoming") + '">' +
											'<div class="wa-message-content ' + contentClass + '">' +
											(instanceTag ? instanceTag : '') +
											(linkedObjectHtml ? linkedObjectHtml : '') + // Objeto vinculado en la parte superior
											'<p>' + msg.content + '</p>' +
											'<div class="wa-message-info">' +
											'<span class="wa-message-time">' + msg.date + '</span>' +
											'</div>' +
											'</div></div>';

										chatBody.append(messageHtml);
									});

									// Hacer scroll hasta el final
									chatBody.scrollTop(chatBody[0].scrollHeight);
								} else {
									// No hay mensajes
									chatBody.html('<div class="wa-no-messages">' +
										'<p><?php  $langs->trans("NoMessagesYet");?></p>' +
										'</div>');
								}
							} else {
								chatBody.html('<div class="wa-error">' +
									'<p>' + data.error + '</p>' +
									'</div>');
							}
						},
						error: function() {
							$("#wa-chat-body").html('<div class="wa-error">' +
								'<p><?php  $langs->trans("ErrorLoadingMessages");?></p>' +
								'</div>');
						}
					});
				});

				// Cerrar modal
				$(".wa-close").click(function() {
					$("#wa-chat-modal").hide();
					originalPosition = {}; // Reiniciar posición guardada
				});

				// Cerrar modal si se hace clic fuera
				$(window).click(function(e) {
					if ($(e.target).is("#wa-chat-modal")) {
						$("#wa-chat-modal").hide();
						originalPosition = {}; // Reiniciar posición guardada
						// Si está en pantalla completa, volver a modo normal
						if (isFullScreen) {
							$("#wa-toggle-fullscreen").click();
						}
					}
							});
});

