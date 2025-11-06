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
 * \file    whatsapp/css/whatsapp.css.php
 * \ingroup whatsapp
 * \brief   CSS file for module Whatsapp.
 */

//if (! defined('NOREQUIREUSER')) define('NOREQUIREUSER','1');	// Not disabled because need to load personalized language
//if (! defined('NOREQUIREDB'))   define('NOREQUIREDB','1');	// Not disabled. Language code is found on url.
if (!defined('NOREQUIRESOC')) {
	define('NOREQUIRESOC', '1');
}
//if (! defined('NOREQUIRETRAN')) define('NOREQUIRETRAN','1');	// Not disabled because need to do translations
if (!defined('NOCSRFCHECK')) {
	define('NOCSRFCHECK', 1);
}
if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', 1);
}
if (!defined('NOLOGIN')) {
	define('NOLOGIN', 1); // File must be accessed by logon page so without login
}
//if (! defined('NOREQUIREMENU'))   define('NOREQUIREMENU',1);  // We need top menu content
if (!defined('NOREQUIREHTML')) {
	define('NOREQUIREHTML', 1);
}
if (!defined('NOREQUIREAJAX')) {
	define('NOREQUIREAJAX', '1');
}

session_cache_limiter('public');
// false or '' = keep cache instruction added by server
// 'public'  = remove cache instruction added by server
// and if no cache-control added later, a default cache delay (10800) will be added by PHP.

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

require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

// Load user to have $user->conf loaded (not done by default here because of NOLOGIN constant defined) and load permission if we need to use them in CSS
/*if (empty($user->id) && ! empty($_SESSION['dol_login'])) {
	$user->fetch('',$_SESSION['dol_login']);
	$user->getrights();
}*/


// Define css type
header('Content-type: text/css');
// Important: Following code is to cache this file to avoid page request by browser at each Dolibarr page access.
// You can use CTRL+F5 to refresh your browser cache.
if (empty($dolibarr_nocache)) {
	header('Cache-Control: max-age=10800, public, must-revalidate');
} else {
	header('Cache-Control: no-cache');
}

?>
.wa-btn {
  position: fixed !important; /* Posición fija en la ventana */
  bottom: 30px !important; /* Distancia desde abajo */
  right: 30px !important; /* Distancia desde la derecha */
  z-index: 1000 !important; /* Asegura que esté por encima de otros elementos */
  display: block !important; /* Cambiado de inline-flex a block */
  text-decoration: none !important;
  transition: all 0.3s ease !important; /* Transición suave */
}

.wa-btn-inner {
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
  height: 60px !important; /* Ligeramente más grande */
  width: 60px !important; /* Forma circular, mismo ancho que alto */
  background: linear-gradient(150deg, #128C7E, #25D366) !important;
  color: white !important;
  font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
  font-size: 1.5em !important;
  font-weight: 600 !important;
  padding: 5px !important;
  border-radius: 50% !important; /* Forma circular */
  box-shadow: 0 4px 15px rgba(18, 140, 126, 0.4) !important;
  transition: transform 0.3s, box-shadow 0.3s !important;
}

.wa-btn-inner img {
  width: 100px !important;
  height: 100px !important;
  margin-right: 0 !important; /* Eliminamos el margen ya que ahora es circular */
}

/* Efecto hover */
.wa-btn:hover .wa-btn-inner {
  transform: scale(1.1) !important; /* Efecto de escala en lugar de translateY */
  box-shadow: 0 6px 20px rgba(18, 140, 126, 0.5) !important;
}

/* Efecto al hacer clic */
.wa-btn:active .wa-btn-inner {
  transform: scale(0.95) !important;
  box-shadow: 0 2px 10px rgba(18, 140, 126, 0.3) !important;
}

/* Opcional: Efecto de rebote para llamar más la atención */
@keyframes bounce {
  0%, 20%, 50%, 80%, 100% {transform: translateY(0);}
  40% {transform: translateY(-10px);}
  60% {transform: translateY(-5px);}
}

.wa-btn {
  animation: bounce 2s ease infinite;
  animation-delay: 5s; /* Comienza después de 5 segundos */
}

/* Versión con texto (opcional) */
.wa-btn-with-text .wa-btn-inner {
  width: auto !important;
  border-radius: 30px !important;
  padding: 5px 20px !important;
}

.wa-btn-with-text .wa-btn-inner img {
  margin-right: 10px !important;
}

/* Media query para dispositivos móviles */
@media (max-width: 768px) {
  .wa-btn {
    bottom: 20px !important;
    right: 20px !important;
  }

  .wa-btn-inner {
    height: 55px !important;
    width: 55px !important;
  }
}

/* Nuevo diseño del botón flotante de WhatsApp con opciones */
.wa-floating-container {
  position: fixed !important;
  bottom: 30px !important;
  right: 30px !important;
  z-index: 1000 !important;
  transition: all 0.3s ease !important;
}

.wa-main-button {
  position: relative !important;
}

.wa-btn-inner {
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
  height: 60px !important;
  width: 60px !important;
  background: linear-gradient(150deg, #128C7E, #25D366) !important;
  color: white !important;
  border-radius: 50% !important;
  box-shadow: 0 4px 15px rgba(18, 140, 126, 0.4) !important;
  transition: transform 0.3s, box-shadow 0.3s !important;
  cursor: pointer !important;
  position: relative !important;
}

.wa-btn-inner img {
  width: 100px !important;
  height: 100px !important;
}

/* Badge para contador de mensajes */
.wa-badge {
  position: absolute !important;
  top: -5px !important;
  right: -5px !important;
  background-color: #ff3b30 !important;
  color: white !important;
  font-size: 12px !important;
  font-weight: bold !important;
  padding: 2px 6px !important;
  border-radius: 50% !important;
  box-shadow: 0 2px 5px rgba(0,0,0,0.2) !important;
  z-index: 1 !important;
}

.wa-badge-small {
  background-color: #ff3b30 !important;
  color: white !important;
  font-size: 10px !important;
  font-weight: bold !important;
  padding: 1px 5px !important;
  border-radius: 10px !important;
  margin-left: 5px !important;
}

/* Opciones del botón */
.wa-options {
  position: absolute !important;
  bottom: 70px !important;
  right: 0 !important;
  display: flex !important;
  flex-direction: column !important;
  opacity: 0 !important;
  visibility: hidden !important;
  transition: all 0.3s ease !important;
  transform: translateY(10px) !important;
  background: white !important;
  border-radius: 10px !important;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2) !important;
  overflow: hidden !important;
}

.wa-options.active {
  opacity: 1 !important;
  visibility: visible !important;
  transform: translateY(0) !important;
}

.wa-option {
  padding: 10px 15px !important;
  color: #075E54 !important;
  text-decoration: none !important;
  font-size: 14px !important;
  display: flex !important;
  align-items: center !important;
  transition: background 0.2s ease !important;
}

.wa-option i {
  margin-right: 10px !important;
  font-size: 16px !important;
}

.wa-option:hover {
  background: #f0f0f0 !important;
}

/* Animación de deslizamiento */
@keyframes slideUp {
  from {
    transform: translateY(20px);
    opacity: 0;
  }
  to {
    transform: translateY(0);
    opacity: 1;
  }
}

.wa-options.active {
  animation: slideUp 0.3s ease;
}

/* Estilos para el modal de chat de WhatsApp */
.wa-modal {
  display: none;
  position: fixed;
  z-index: 2000;
  left: 0;
  top: 0;
  width: 100%;
  height: 100%;
  overflow: auto;
  background-color: rgba(0,0,0,0.6);
  opacity: 0;
  animation: fadeIn 0.3s forwards;
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

.wa-modal-content {
  background-color: #f0f0f0;
  margin: 5% auto;
  width: 80%;
  max-width: 700px;
  height: 70vh;
  border-radius: 12px;
  box-shadow: 0 5px 25px rgba(0,0,0,0.3);
  display: flex;
  flex-direction: column;
  overflow: hidden;
  animation: slideUp 0.4s ease-out;
}

/* Modal de chat mejorado ajustable a pantalla */
.wa-modal {
  display: none;
  position: fixed;
  z-index: 2000;
  left: 0;
  top: 0;
  width: 100%;
  height: 100%;
  overflow: hidden;
  background-color: rgba(0,0,0,0.6);
  opacity: 0;
  animation: fadeIn 0.3s forwards;
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

.wa-modal-content {
  background-color: #f0f0f0;
  margin: 2% auto;
  width: 85%;
  height: 85vh;
  max-width: 1200px;
  border-radius: 15px;
  box-shadow: 0 8px 32px rgba(0,0,0,0.3);
  display: flex;
  flex-direction: column;
  overflow: hidden;
  animation: slideUp 0.4s ease-out;
  position: relative; /* Importante para hacer arrastrable */
  transition: all 0.3s ease;
}

/* Modo de pantalla completa para el modal */
.wa-modal-fullscreen {
  position: fixed !important;
  width: 98% !important;
  height: 96vh !important;
  max-width: none !important;
  top: 1% !important;
  left: 1% !important;
  margin: 0 !important;
  border-radius: 8px !important;
  z-index: 2100 !important;
}

@keyframes slideUp {
  from { transform: translateY(50px); opacity: 0; }
  to { transform: translateY(0); opacity: 1; }
}

/* Cabecera del chat con mango para arrastrar */
.wa-chat-header {
  background-color: #075E54;
  color: white;
  padding: 15px 20px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  cursor: move; /* Indicador visual de que se puede arrastrar */
  user-select: none; /* Evitar selección de texto al arrastrar */
  z-index: 2101; /* Asegurar que esté por encima del contenido */
}

.wa-chat-controls {
  display: flex;
  align-items: center;
  z-index: 2102; /* Por encima de la cabecera para que los botones sean clickeables */
}

.wa-resize-modal {
  color: white;
  font-size: 20px;
  margin-right: 20px;
  cursor: pointer;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 30px;
  height: 30px;
}

.wa-resize-modal:hover,
.wa-close:hover {
  transform: scale(1.2);
  background-color: rgba(255,255,255,0.1);
  border-radius: 50%;
}

/* Resto de estilos... */

.wa-chat-avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  margin-right: 15px;
  object-fit: cover;
}

.wa-chat-header h3 {
  margin: 0;
  font-size: 18px;
  font-weight: 600;
}

.wa-chat-header p {
  margin: 3px 0 0;
  font-size: 13px;
  opacity: 0.8;
}

.wa-close {
  color: white;
  font-size: 28px;
  font-weight: bold;
  cursor: pointer;
  transition: all 0.2s;
}

.wa-close:hover {
  transform: scale(1.2);
}

/* Cuerpo del chat mejorado para pantallas más grandes */
.wa-chat-body {
  flex-grow: 1;
  background: url('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABkAAAAZCAYAAADE6YVjAAAABmJLR0QA/wD/AP+gvaeTAAAACXBIWXMAAAsTAAALEwEAmpwYAAAAB3RJTUUH4QQQEwkySS9ZWwAAAB1pVFh0Q29tbWVudAAAAAAAQ3JlYXRlZCB3aXRoIEdJTVBkLmUHAAAANUlEQVRIx2NgYGD4z4AdMFJHPUwZGUwZGXDxpHjqYcpIeII6J3oWDbrBoNmNabgBQ0XUExEAnfIhjZ4zTLkAAAAASUVORK5CYII=');
  padding: 25px;
  overflow-y: auto;
  position: relative;
}

/* Mensajes en el chat mejorados */
.wa-message {
  margin-bottom: 20px;
  display: flex;
  max-width: 90% !important; /* Aumentado para más espacio */
}

/* Específicamente aumentamos el ancho para mensajes entrantes */
.wa-incoming {
  justify-content: flex-start;
  max-width: 95% !important; /* Aumentamos el ancho para mensajes entrantes */
}

.wa-incoming .wa-message-content {
  background-color: white;
  max-width: 100% !important; /* Permitimos que use todo el ancho disponible */
}

/* Mejoramos el objeto vinculado para mostrar más contenido */
.wa-message-linked-object {
  position: absolute !important;
  top: 10px !important;
  right: 15px !important;
  font-size: 12px !important;
  background-color: rgba(0, 0, 0, 0.05) !important;
  border-radius: 8px !important;
  padding: 3px 8px !important;
  max-width: 80% !important; /* Aumentado para mostrar más contenido */
  white-space: nowrap !important;
  overflow: hidden !important;
  text-overflow: ellipsis !important;
  z-index: 10 !important;
}

.wa-message-linked-object a {
  color: #075E54 !important;
  text-decoration: none !important;
  font-weight: 600 !important;
  display: inline-flex !important;
  align-items: center !important;
}

.wa-message-linked-object a:hover {
  text-decoration: underline !important;
}

.wa-message-linked-object a i {
  margin-right: 5px !important;
}

/* Aseguramos suficiente espacio para el objeto vinculado */
.wa-message-content-with-link {
  padding-top: 30px !important; /* Aumentado el espacio superior */
}

.wa-message-content {
  max-width: 100%;
  min-width:70% !important;
  padding: 12px 18px;
  border-radius: 12px;
  position: relative;
  box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.wa-message-content p {
  margin: 0;
  padding: 0;
  word-wrap: break-word;
  font-size: 15px;
  line-height: 1.5;
}

.wa-message-time {
  font-size: 12px;
  opacity: 0.7;
  margin-top: 6px;
  display: block;
  text-align: right;
}

.wa-incoming {
  justify-content: flex-start;
}

.wa-incoming .wa-message-content {
  background-color: white;
}

.wa-outgoing {
  justify-content: flex-end;
  margin-left: auto;
}

.wa-outgoing .wa-message-content {
  background-color: #DCF8C6;
}

/* Media queries para dispositivos móviles mejorados */
@media (max-width: 768px) {
  .wa-modal-content {
    width: 90%;
    height: 90vh;
    margin: 5% auto;
  }

  .wa-modal-fullscreen {
    width: 100% !important;
    height: 100vh !important;
    margin: 0 !important;
    border-radius: 0 !important;
  }

  .wa-chat-body {
    padding: 15px;
  }

  .wa-message {
    max-width: 95%;
  }
}

/* Estilos para indicadores de carga y errores */
.wa-chat-loader {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 100%;
  color: #555;
}

.wa-spinner {
  width: 40px;
  height: 40px;
  border: 4px solid rgba(0, 0, 0, 0.1);
  border-left-color: #075E54;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-bottom: 15px;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.wa-no-messages {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 100%;
  color: #888;
  font-style: italic;
  text-align: center;
}

.wa-error {
  background-color: #ffdddd;
  color: #d83030;
  padding: 15px;
  border-radius: 8px;
  text-align: center;
  margin: 20px;
}

/* Media query para dispositivos móviles */
@media (max-width: 768px) {
  .wa-floating-container {
    bottom: 20px !important;
    right: 20px !important;
  }

  .wa-btn-inner {
    height: 55px !important;
    width: 55px !important;
  }

  .wa-modal-content {
    width: 95%;
    height: 80vh;
    margin: 10% auto;
  }

  .wa-option {
    min-width: auto !important;
    padding: 6px 12px !important;
  }
}

/* Efecto al hover y al clic */
.wa-main-button:hover .wa-btn-inner {
  transform: scale(1.1) !important;
  box-shadow: 0 6px 20px rgba(18, 140, 126, 0.5) !important;
}

.wa-main-button:active .wa-btn-inner {
  transform: scale(0.95) !important;
  box-shadow: 0 2px 10px rgba(18, 140, 126, 0.3) !important;
}

/* Animación de rebote más sutil */
@keyframes subtleBounce {
  0%, 20%, 50%, 80%, 100% {transform: translateY(0);}
  40% {transform: translateY(-5px);}
  60% {transform: translateY(-3px);}
}

.wa-floating-container {
  animation: subtleBounce 3s ease;
  animation-delay: 2s;
}

/* Nuevo diseño profesional de WhatsApp FAB */
.wa-floating-container {
  position: fixed !important;
  bottom: 30px !important;
  right: 30px !important;
  z-index: 1000 !important;
}

.wa-main-button {
  width: 60px !important;
  height: 60px !important;
  border-radius: 50% !important;
  background: linear-gradient(135deg, #25D366 0%, #128C7E 100%) !important;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25) !important;
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
  cursor: pointer !important;
  transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1) !important;
  position: relative !important;
  z-index: 1001 !important;
}

.wa-main-button img {
  width: 32px !important;
  height: 32px !important;
  transition: transform 0.3s ease !important;
}

.wa-main-button.active img {
  transform: rotate(45deg) !important;
}

.wa-main-button:hover {
  transform: scale(1.05) !important;
  box-shadow: 0 6px 16px rgba(0, 0, 0, 0.3) !important;
}

.wa-badge {
  position: absolute !important;
  top: -6px !important;
  right: -6px !important;
  background: #F44336 !important;
  color: white !important;
  border-radius: 50% !important;
  width: 22px !important;
  height: 22px !important;
  font-size: 12px !important;
  font-weight: bold !important;
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
  box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2) !important;
  z-index: 1002 !important;
  border: 2px solid #fff !important;
}

.wa-badge-small {
  background: #F44336 !important;
  color: white !important;
  border-radius: 10px !important;
  padding: 1px 6px !important;
  font-size: 11px !important;
  margin-left: 5px !important;
  font-weight: bold !important;
}

/* Opciones del FAB */
.wa-options {
  position: absolute !important;
  bottom: 70px !important;
  right: 5px !important;
  display: flex !important;
  flex-direction: column !important;
  visibility: hidden !important;
  opacity: 0 !important;
  transition: all 0.3s !important;
  z-index: 1000 !important;
}

.wa-option {
  display: flex !important;
  align-items: center !important;
  padding: 10px !important;
  margin-bottom: 12px !important;
  background: white !important;
  border-radius: 30px !important;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2) !important;
  transition: transform 0.2s, box-shadow 0.2s !important;
  overflow: hidden !important;
  text-decoration: none !important;
  position: relative !important;
  transform: translateY(20px) !important;
  opacity: 0 !important;
}

.wa-option-icon {
  width: 38px !important;
  height: 38px !important;
  border-radius: 50% !important;
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
  margin-right: 10px !important;
}

.wa-option:nth-child(1) .wa-option-icon {
  background: linear-gradient(135deg, #25D366 0%, #128C7E 100%) !important;
}

.wa-option:nth-child(2) .wa-option-icon {
  background: linear-gradient(135deg, #34B7F1 0%, #0077B5 100%) !important;
}

.wa-option-icon i {
  color: white !important;
  font-size: 18px !important;
}

.wa-option-text {
  color: #333 !important;
  font-weight: 500 !important;
  font-size: 14px !important;
  white-space: nowrap !important;
}

.wa-option:hover {
  transform: translateX(-5px) !important;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.25) !important;
}

/* Animación para las opciones */
.wa-options.active {
  visibility: visible !important;
  opacity: 1 !important;
}

.wa-options.active .wa-option {
  opacity: 1 !important;
  transform: translateY(0) !important;
}

.wa-options.active .wa-option:nth-child(1) {
  transition-delay: 0.05s !important;
}

.wa-options.active .wa-option:nth-child(2) {
  transition-delay: 0.1s !important;
}

/* Animación de ondas para el botón principal */
.wa-ripple {
  position: absolute !important;
  width: 120px !important;
  height: 120px !important;
  border-radius: 50% !important;
  background-color: rgba(37, 211, 102, 0.4) !important;
  top: 50% !important;
  left: 50% !important;
  transform: translate(-50%, -50%) scale(0) !important;
  animation: ripple-effect 1.5s infinite !important;
  z-index: -1 !important;
}

@keyframes ripple-effect {
  0% {
    transform: translate(-50%, -50%) scale(0);
    opacity: 1;
  }
  100% {
    transform: translate(-50%, -50%) scale(1);
    opacity: 0;
  }
}

/* Estilos para la animación de pulso */
.wa-pulse {
  animation: pulse 2s infinite !important;
  box-shadow: 0 0 0 rgba(18, 140, 126, 0.4) !important;
}

@keyframes pulse {
  0% {
    transform: scale(0.95);
    box-shadow: 0 0 0 0 rgba(37, 211, 102, 0.7);
  }
  70% {
    transform: scale(1);
    box-shadow: 0 0 0 15px rgba(37, 211, 102, 0);
  }
  100% {
    transform: scale(0.95);
    box-shadow: 0 0 0 0 rgba(37, 211, 102, 0);
  }
}

/* Media query para dispositivos móviles */
@media (max-width: 768px) {
  .wa-floating-container {
    bottom: 20px !important;
    right: 20px !important;
  }

  .wa-main-button {
    width: 50px !important;
    height: 50px !important;
  }

  .wa-main-button img {
    width: 28px !important;
    height: 28px !important;
  }

  .wa-option {
    padding: 8px !important;
    margin-bottom: 10px !important;
  }

  .wa-option-icon {
    width: 32px !important;
    height: 32px !important;
  }

  .wa-option-text {
    font-size: 12px !important;
  }
}

/* Diseño FAB de WhatsApp ultra moderno */
.wa-floating-container {
  position: fixed !important;
  bottom: 30px !important;
  right: 30px !important;
  z-index: 1000 !important;
}

.wa-main-button {
  width: 70px !important;
  height: 70px !important;
  border-radius: 50% !important;
  background: linear-gradient(45deg, #25D366 0%, #128C7E 100%) !important;
  box-shadow: 0 6px 16px rgba(0, 0, 0, 0.3), 0 0 0 6px rgba(37, 211, 102, 0.1) !important;
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
  cursor: pointer !important;
  transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275) !important;
  position: relative !important;
  z-index: 1001 !important;
  overflow: visible !important; /* Cambiado de hidden a visible para que el badge no se recorte */
}

.wa-main-button:before {
  content: '' !important;
  position: absolute !important;
  top: 0 !important;
  left: 0 !important;
  width: 100% !important;
  height: 100% !important;
  background: radial-gradient(circle at center, rgba(255,255,255,0.2) 0%, rgba(255,255,255,0) 70%) !important;
  opacity: 0 !important;
  transition: opacity 0.4s ease !important;
}

.wa-main-button:hover:before {
  opacity: 1 !important;
}

.wa-main-button img {
  width: 60px !important; /* Aumentado de 40px a 60px */
  height: 60px !important; /* Aumentado de 40px a 60px */
  transition: transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275) !important;
  filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2)) !important;
}

.wa-main-button.active img {
  transform: rotate(45deg) scale(1.1) !important;
}

.wa-main-button:hover {
  transform: translateY(-5px) scale(1.05) !important;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.4), 0 0 0 10px rgba(37, 211, 102, 0.15) !important;
}

.wa-main-button:active {
  transform: translateY(0) scale(0.95) !important;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25), 0 0 0 5px rgba(37, 211, 102, 0.1) !important;
}

/* Badge moderno y prominente */
.wa-badge {
  position: absolute !important;
  top: -10px !important; /* Ajustado de -8px a -10px para posicionarlo más arriba */
  right: -10px !important; /* Ajustado de -8px a -10px para posicionarlo más a la derecha */
  background: linear-gradient(45deg, #FF5252 0%, #FF1744 100%) !important;
  color: white !important;
  border-radius: 20px !important; /* Aumentado para mayor redondez */
  min-width: 26px !important;
  height: 26px !important;
  font-size: 14px !important;
  font-weight: bold !important;
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
  box-shadow: 0 3px 10px rgba(255, 23, 68, 0.5) !important;
  z-index: 1002 !important;
  border: 2px solid #fff !important;
  padding: 2px 7px !important; /* Aumentado el padding horizontal */
  letter-spacing: -0.3px !important;
  transform: scale(1) !important;
  animation: badgePulse 2s infinite !important;
}

@keyframes badgePulse {
  0% {
    transform: scale(1);
    box-shadow: 0 3px 10px rgba(255, 23, 68, 0.5);
  }
  50% {
    transform: scale(1.15); /* Más pronunciado para llamar la atención */
    box-shadow: 0 4px 14px rgba(255, 23, 68, 0.7);
  }
  100% {
    transform: scale(1);
    box-shadow: 0 3px 10px rgba(255, 23, 68, 0.5);
  }
}

.wa-badge-small {
  background: linear-gradient(45deg, #FF5252 0%, #FF1744 100%) !important;
  color: white !important;
  border-radius: 14px !important;
  padding: 3px 9px !important; /* Aumentado para mejor visibilidad */
  font-size: 12px !important;
  margin-left: 8px !important;
  font-weight: bold !important;
  box-shadow: 0 2px 5px rgba(255, 23, 68, 0.4) !important;
}

/* Efecto de ondas mejorado */
.wa-ripple {
  position: absolute !important;
  width: 160px !important;
  height: 160px !important;
  border-radius: 50% !important;
  background-color: rgba(37, 211, 102, 0.3) !important;
  top: 50% !important;
  left: 50% !important;
  transform: translate(-50%, -50%) scale(0) !important;
  animation: ripple-effect 2s infinite cubic-bezier(0.25, 0.8, 0.25, 1) !important;
  z-index: -1 !important;
}

.wa-ripple:nth-child(2) {
  animation-delay: 0.5s !important;
}

@keyframes ripple-effect {
  0% {
    transform: translate(-50%, -50%) scale(0);
    opacity: 1;
  }
  50% {
    opacity: 0.5;
  }
  100% {
    transform: translate(-50%, -50%) scale(1);
    opacity: 0;
  }
}

/* Opciones completamente rediseñadas */
.wa-options {
  padding: 15px !important;
  position: absolute !important;
  bottom: 80px !important;
  right: 10px !important;
  display: flex !important;
  flex-direction: column !important;
  visibility: hidden !important;
  opacity: 0 !important;
  transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275) !important;
  z-index: 1000 !important;

}

.wa-option {
  display: flex !important;
  align-items: center !important;
  padding: 14px !important;
  margin-bottom: 16px !important;
  background: white !important;
  border-radius: 16px !important;
  box-shadow: 0 6px 16px rgba(0, 0, 0, 0.15), 0 -1px 0 rgba(255, 255, 255, 0.8) inset !important;
  transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275) !important;
  overflow: hidden !important;
  text-decoration: none !important;
  position: relative !important;
  transform: translateY(25px) translateX(25px) !important;
  opacity: 0 !important;
}

.wa-option:before {
  content: '' !important;
  position: absolute !important;
  top: 0 !important;
  left: 0 !important;
  width: 100% !important;
  height: 100% !important;
  background: linear-gradient(135deg, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 50%) !important;
  z-index: 1 !important;
}

.wa-option-icon {
  width: 48px !important;
  height: 48px !important;
  border-radius: 12px !important;
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
  margin-right: 14px !important;
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15) !important;
  position: relative !important;
  overflow: hidden !important;
  flex-shrink: 0 !important;
}

.wa-option-icon:before {
  content: '' !important;
  position: absolute !important;
  top: 0 !important;
  left: 0 !important;
  width: 100% !important;
  height: 100% !important;
  background: linear-gradient(135deg, rgba(255,255,255,0.25) 0%, rgba(255,255,255,0) 60%) !important;
  z-index: 1 !important;
}

.wa-option:nth-child(1) .wa-option-icon {
  background: linear-gradient(135deg, #25D366 0%, #128C7E 100%) !important;
}

.wa-option:nth-child(2) .wa-option-icon {
  background: linear-gradient(135deg, #4776E6 0%, #8E54E9 100%) !important;
}

.wa-option-icon i {
  color: white !important;
  font-size: 22px !important;
  z-index: 2 !important;
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2) !important;
}

.wa-option-text {
  color: #333 !important;
  font-weight: 600 !important;
  font-size: 15px !important;
  white-space: nowrap !important;
  letter-spacing: -0.3px !important;
  flex-grow: 1 !important;
}

.wa-option:hover {
  transform: translateX(-8px) !important;
  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2), 0 -1px 0 rgba(255, 255, 255, 0.8) inset !important;
  background: linear-gradient(135deg, #ffffff 0%, #f5f5f5 100%) !important;
  z-index: 1 !important;
}

.wa-option:active {
  transform: translateX(-5px) scale(0.98) !important;
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1), 0 -1px 0 rgba(255, 255, 255, 0.8) inset !important;
}

/* Animación para las opciones */
.wa-options.active {
  visibility: visible !important;
  opacity: 1 !important;
}

.wa-options.active .wa-option {
  opacity: 1 !important;
  transform: translateY(0) translateX(0) !important;
}

.wa-options.active .wa-option:nth-child(1) {
  transition-delay: 0.05s !important;
}

.wa-options.active .wa-option:nth-child(2) {
  transition-delay: 0.15s !important;
}

/* Pulse animation modified */
.wa-pulse {
  position: relative !important;
}

.wa-pulse:before, .wa-pulse:after {
  content: '' !important;
  position: absolute !important;
  width: 100% !important;
  height: 100% !important;
  top: 0 !important;
  left: 0 !important;
  background-color: rgba(37, 211, 102, 0.4) !important;
  border-radius: 50% !important;
  z-index: -1 !important;
}

.wa-pulse:before {
  animation: pulse-wave 2s infinite !important;
}

.wa-pulse:after {
  animation: pulse-wave 2s 0.7s infinite !important;
}

@keyframes pulse-wave {
  0% {
    transform: scale(0.9);
    opacity: 0.8;
  }
  50% {
    transform: scale(1.4);
    opacity: 0;
  }
  100% {
    transform: scale(0.9);
    opacity: 0;
  }
}

/* Responsive styles */
@media (max-width: 768px) {
  .wa-floating-container {
    bottom: 25px !important;
    right: 25px !important;
  }

  .wa-main-button {
    width: 65px !important;
    height: 65px !important;
  }

  .wa-main-button img {
    width: 60px !important;
    height: 60px !important;
  }

  .wa-options {
    width: 200px !important;
    bottom: 75px !important;
  }

  .wa-option {
    padding: 12px !important;
    margin-bottom: 14px !important;
  }

  .wa-option-icon {
    width: 40px !important;
    height: 40px !important;
    margin-right: 12px !important;
  }

  .wa-option-text {
    font-size: 14px !important;
  }
}

/* Super pequeños dispositivos */
@media (max-width: 480px) {
  .wa-options {
    width: 180px !important;
  }

  .wa-option-icon {
    width: 36px !important;
    height: 36px !important;
  }

  .wa-option-icon i {
    font-size: 18px !important;
  }
}

/* Estilos para la etiqueta de instancia */
.wa-message-info {
  display: flex !important;
  justify-content: flex-end !important;
  align-items: center !important;
  margin-top: 6px !important;
  font-size: 12px !important;
}

.wa-instance-container {
  display: flex !important;
  justify-content: flex-start !important;
  margin-bottom: 6px !important;
}

.wa-instance-tag {
  background: linear-gradient(135deg, #075E54 0%, #128C7E 100%) !important;
  color: white !important;
  padding: 2px 6px !important;
  border-radius: 8px !important;
  font-size: 9px !important;
  font-weight: bold !important;
  display: inline-block !important;
  text-transform: uppercase !important;
  letter-spacing: 0.5px !important;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2) !important;
  margin-bottom: 4px !important;
  align-self: flex-start !important;
}

.wa-incoming .wa-instance-tag {
  background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%) !important;
}

.wa-message-time {
  opacity: 0.7 !important;
}

/* Ajuste para mantener alineados los elementos cuando no hay instancia */
.wa-message-info {
  min-height: 16px !important;
}

/* Mensajes en el chat mejorados con mayor ancho */
.wa-message {
  margin-bottom: 20px;
  display: flex;
  max-width: 90% !important; /* Aumentado de 85% a 90% */
}

.wa-message-content {
  max-width: 100%;
  padding: 15px 20px !important; /* Aumentado el padding para más espacio interno */
  border-radius: 12px;
  position: relative;
  box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.wa-message-content p {
  margin: 0;
  padding: 0;
  word-wrap: break-word;
  font-size: 15px;
  line-height: 1.5;
  overflow-wrap: break-word !important; /* Garantiza que el texto largo se ajuste */
  hyphens: auto !important; /* Permite división de palabras largas */
}

/* Estilos para la etiqueta de instancia - mejorado */
.wa-instance-container {
  display: flex !important;
  justify-content: flex-start !important;
  margin-bottom: 8px !important;
  width: 100% !important;
}

.wa-instance-tag {
  background: linear-gradient(135deg, #075E54 0%, #128C7E 100%) !important;
  color: white !important;
  padding: 3px 8px !important;
  border-radius: 8px !important;
  font-size: 10px !important;
  font-weight: bold !important;
  display: inline-block !important;
  text-transform: uppercase !important;
  letter-spacing: 0.5px !important;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2) !important;
  margin-bottom: 5px !important;
  align-self: flex-start !important;
  max-width: calc(50% - 10px) !important; /* Limitamos el ancho para evitar solapamientos */
  overflow: hidden !important;
  text-overflow: ellipsis !important;
  white-space: nowrap !important;
  z-index: 5 !important;
}

/* Estilos para el objeto vinculado - posición optimizada */
.wa-message-linked-object {
  position: absolute !important;
  top: 10px !important;
  right: 15px !important;
  font-size: 12px !important;
  background-color: rgba(0, 0, 0, 0.05) !important;
  border-radius: 8px !important;
  padding: 3px 8px !important;
  max-width: 50% !important; /* Ajustamos el ancho máximo para evitar solapamientos */
  white-space: nowrap !important;
  overflow: hidden !important;
  text-overflow: ellipsis !important;
  z-index: 10 !important;
}

/* Espacio extra entre instancia y contenido para evitar solapamientos */
.wa-message-content.wa-message-content-with-link {
  padding-top: 35px !important; /* Más espacio superior cuando hay objeto vinculado */
}

.wa-message-info {
  display: flex !important;
  justify-content: flex-end !important;
  align-items: center !important;
  margin-top: 8px !important; /* Aumentado de 6px a 8px */
  font-size: 12px !important;
}

.wa-instance-container {
  display: flex !important;
  justify-content: flex-start !important;
  margin-bottom: 8px !important; /* Aumentado de 6px a 8px */
  width: 100% !important; /* Asegura que tome todo el ancho disponible */
}

.wa-instance-tag {
  background: linear-gradient(135deg, #075E54 0%, #128C7E 100%) !important;
  color: white !important;
  padding: 3px 8px !important; /* Aumentado de 2px 6px a 3px 8px */
  border-radius: 8px !important;
  font-size: 10px !important; /* Aumentado de 9px a 10px */
  font-weight: bold !important;
  display: inline-block !important;
  text-transform: uppercase !important;
  letter-spacing: 0.5px !important;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2) !important;
  margin-bottom: 5px !important; /* Aumentado de 4px a 5px */
  align-self: flex-start !important;
  max-width: 100% !important; /* Evita que se salga del contenedor padre */
  overflow: hidden !important;
  text-overflow: ellipsis !important;
  white-space: nowrap !important;
}

.wa-incoming .wa-instance-tag {
  background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%) !important;
}

.wa-message-time {
  opacity: 0.7 !important;
  margin-left: auto !important; /* Empuja el tiempo hacia la derecha */
}

/* Media queries para dispositivos móviles con tamaños ajustados */
@media (max-width: 768px) {
  .wa-modal-content {
    width: 95% !important; /* Aumentado de 90% a 95% */
    height: 90vh !important;
    margin: 5% auto !important;
  }

  .wa-chat-body {
    padding: 15px !important;
  }

  .wa-message {
    max-width: 98% !important; /* Aumentado de 95% a 98% para móviles */
  }
}

/* Cabecera del chat mejorada con mejor alineación de elementos */
.wa-chat-header {
  background: linear-gradient(135deg, #075E54 0%, #128C7E 100%) !important;
  color: white !important;
  padding: 15px 20px !important;
  display: flex !important;
  justify-content: space-between !important;
  align-items: center !important;
  cursor: move !important;
  user-select: none !important;
  z-index: 2101 !important;
  border-radius: 12px 12px 0 0 !important;
}

.wa-chat-header-info {
  display: flex !important;
  align-items: center !important;
  gap: 15px !important; /* Espacio consistente entre la imagen y el texto */
  flex: 1 !important;
}

.wa-chat-avatar {
  width: 45px !important;
  height: 45px !important;
  border-radius: 50% !important;
  object-fit: cover !important;
  border: 2px solid rgba(255, 255, 255, 0.3) !important;
  box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2) !important;
  flex-shrink: 0 !important; /* Evita que la imagen se comprima */
}

.wa-chat-header-info div {
  display: flex !important;
  flex-direction: column !important;
  justify-content: center !important;
}

.wa-chat-header h3 {
  margin: 0 !important;
  font-size: 18px !important;
  font-weight: 600 !important;
  letter-spacing: -0.3px !important;
  line-height: 1.3 !important;
}

.wa-chat-header p {
  margin: 3px 0 0 !important;
  font-size: 13px !important;
  opacity: 0.85 !important;
  line-height: 1.2 !important;
}

.wa-chat-controls {
  display: flex !important;
  align-items: center !important;
  gap: 15px !important;
  z-index: 2102 !important;
}

.wa-resize-modal, .wa-close {
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
  width: 34px !important;
  height: 34px !important;
  border-radius: 50% !important;
  background-color: rgba(255, 255, 255, 0.1) !important;
  transition: all 0.2s ease !important;
  cursor: pointer !important;
}

.wa-resize-modal i {
  font-size: 16px !important;
}

.wa-close {
  font-size: 22px !important;
  line-height: 1 !important;
}

.wa-resize-modal:hover, .wa-close:hover {
  background-color: rgba(255, 255, 255, 0.2) !important;
  transform: scale(1.1) !important;
}

/* Media queries para dispositivos móviles */
@media (max-width: 768px) {
  .wa-chat-header {
    padding: 12px 15px !important;
  }

  .wa-chat-avatar {
    width: 40px !important;
    height: 40px !important;
  }

  .wa-chat-header-info {
    gap: 10px !important;
  }

  .wa-chat-header h3 {
    font-size: 16px !important;
  }

  .wa-chat-header p {
    font-size: 12px !important;
  }

  .wa-resize-modal, .wa-close {
    width: 30px !important;
    height: 30px !important;
  }
}

/* Badge para el icono en getNomUrl - Versión mejorada */
.wa-icon-container {
  display: inline-block !important;
  position: relative !important;
  margin-right: 10px !important;
  vertical-align: middle !important;
  line-height: 0 !important; /* Importante: establece line-height en 0 para evitar espacios adicionales */
  z-index: 1 !important; /* Asegura que el contenedor sea posicionable */
}

.wa-icon-nomurl {
  cursor: pointer !important;
  font-size: 2em !important;
  color: green !important;
  display: inline-block !important;
  vertical-align: middle !important;
  position: relative !important; /* Asegura posicionamiento correcto */
  line-height: 1 !important; /* Mantiene la altura de línea consistente */
  z-index: 1 !important;
  margin: 0 !important;
  padding: 0 !important;
}

.wa-icon-badge {
  position: absolute !important;
  top: -7px !important;
  right: -10px !important;
  cursor: pointer !important;
  background: linear-gradient(45deg, #FF5252 0%, #FF1744 100%) !important;
  color: white !important;
  border-radius: 50% !important;
  min-width: 16px !important;
  height: 16px !important;
  font-size: 14px !important;
  font-weight: bold !important;
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
  box-shadow: 0 1px 3px rgba(0,0,0,0.3) !important;
  border: 1.5px solid #fff !important;
  padding: 2px !important;
  z-index: 10 !important; /* Mayor z-index para estar por encima */
  transform: translate(0, 0) !important; /* Asegura posicionamiento correcto en todos los navegadores */
  text-align: center !important;
  line-height: 1 !important;
}

/* Fix para algunos navegadores móviles */
@media screen and (max-width: 767px) {
  .wa-icon-badge {
    top: -4px !important;
    right: -6px !important;
    min-width: 14px !important;
    height: 14px !important;
    font-size: 9px !important;
  }
}

/* Estilos para el objeto vinculado */
.wa-message-linked-object {
  position: absolute !important;
  top: 10px !important;
  right: 15px !important;
  font-size: 12px !important;
  background-color: rgba(0, 0, 0, 0.05) !important;
  border-radius: 8px !important;
  padding: 3px 8px !important;
  max-width: 60% !important;
  white-space: nowrap !important;
  overflow: hidden !important;
  text-overflow: ellipsis !important;
  z-index: 10 !important;
}

.wa-message-linked-object a {
  color: #075E54 !important;
  text-decoration: none !important;
  font-weight: 600 !important;
}

.wa-message-linked-object a:hover {
  text-decoration: underline !important;
}

.wa-outgoing .wa-message-linked-object {
  background-color: rgba(0, 0, 0, 0.07) !important;
}

.wa-outgoing .wa-message-linked-object a {
  color: #056162 !important;
}

/* Ajusta la posición del contenido del mensaje cuando hay objeto vinculado */
.wa-message-content-with-link {
  padding-top: 20px !important; /* Deja espacio para el objeto vinculado */
}

/* Asegura que la hora siga apareciendo abajo */
.wa-message-info {
  margin-top: 8px !important;
}
