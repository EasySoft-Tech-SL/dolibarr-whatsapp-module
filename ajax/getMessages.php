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
 * \file    whatsapp/ajax/getMessages.php
 * \ingroup whatsapp
 * \brief   Obtiene los mensajes de WhatsApp para mostrar en el modal del chat.
 */
if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', 1); // Disables token renewal
}
if (!defined('NOREQUIREMENU')) {
	define('NOREQUIREMENU', '1');
}
if (!defined('NOREQUIREHTML')) {
	define('NOREQUIREHTML', '1');
}
if (!defined('NOREQUIREAJAX')) {
	define('NOREQUIREAJAX', '1');
}
if (!defined('NOREQUIRESOC')) {
	define('NOREQUIRESOC', '1');
}
if (!defined('NOCSRFCHECK')) {
	define('NOCSRFCHECK', '1');
}
if (empty($_GET['keysearch']) && !defined('NOREQUIREHTML')) {
	define('NOREQUIREHTML', '1');
}

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
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1)) . "/../main.inc.php")) {
	$res = @include substr($tmp, 0, ($i + 1)) . "/../main.inc.php";
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

top_httphead('text/json');
require_once DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
dol_include_once('/whatsapp/lib/whatsapp.lib.php');

// Verificación de seguridad
if (!$user->rights->whatsapp->read) {
	$response = array(
		'success' => false,
		'error' => $langs->trans("NotEnoughPermissions")
	);
	echo json_encode($response);
	exit;
}

// Obtener parámetros
$objectId = GETPOST('id', 'int');
$objectType = GETPOST('type', 'alpha');
$socid = GETPOST('socid', 'int');

if (empty($objectId) || empty($objectType)) {
	$response = array(
		'success' => false,
		'error' => $langs->trans("MissingParameters")
	);
	echo json_encode($response);
	exit;
}

// Obtener nombre del contacto/tercero para mostrar en el encabezado del chat
$contactName = "";

// Identificar el tipo de objeto y obtener el nombre apropiado
switch ($objectType) {
	case 'societe':
		require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
		$object = new Societe($db);
		if ($object->fetch($objectId) > 0) {
			$contactName = $object->name;
		}
		break;
	case 'invoice':
	case 'facture':
		require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
		$object = new Facture($db);
		if ($object->fetch($objectId) > 0) {
			$contactName = $object->ref . ' - ' . $object->thirdparty->name;
		}
		break;
	case 'order':
	case 'commande':
		require_once DOL_DOCUMENT_ROOT . '/commande/class/commande.class.php';
		$object = new Commande($db);
		if ($object->fetch($objectId) > 0) {
			$contactName = $object->ref . ' - ' . $object->thirdparty->name;
		}
		break;
	case 'propal':
		require_once DOL_DOCUMENT_ROOT . '/comm/propal/class/propal.class.php';
		$object = new Propal($db);
		if ($object->fetch($objectId) > 0) {
			$contactName = $object->ref . ' - ' . $object->thirdparty->name;
		}
		break;
	case 'project':
	case 'projet':
		require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';
		$object = new Project($db);
		if ($object->fetch($objectId) > 0) {
			$contactName = $object->ref . ' - ' . $object->thirdparty->name;
		}
		break;
	// NUEVOS CASOS AÑADIDOS
	case 'shipping':
	case 'expedition':
		require_once DOL_DOCUMENT_ROOT . '/expedition/class/expedition.class.php';
		$object = new Expedition($db);
		if ($object->fetch($objectId) > 0) {
			$contactName = $langs->trans("Shipment") . ' ' . $object->ref . ' - ' . $object->thirdparty->name;
		}
		break;
	case 'delivery':
	case 'reception':
		require_once DOL_DOCUMENT_ROOT . '/reception/class/reception.class.php';
		$object = new Reception($db);
		if ($object->fetch($objectId) > 0) {
			$contactName = $langs->trans("Reception") . ' ' . $object->ref . ' - ' . $object->thirdparty->name;
		}
		break;
	case 'invoice_supplier':
		require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.facture.class.php';
		$object = new FactureFournisseur($db);
		if ($object->fetch($objectId) > 0) {
			$contactName = $langs->trans("SupplierInvoice") . ' ' . $object->ref . ' - ' . $object->thirdparty->name;
		}
		break;
	case 'order_supplier':
		require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.commande.class.php';
		$object = new CommandeFournisseur($db);
		if ($object->fetch($objectId) > 0) {
			$contactName = $langs->trans("SupplierOrder") . ' ' . $object->ref . ' - ' . $object->thirdparty->name;
		}
		break;
	case 'supplier_proposal':
		require_once DOL_DOCUMENT_ROOT . '/supplier_proposal/class/supplier_proposal.class.php';
		$object = new SupplierProposal($db);
		if ($object->fetch($objectId) > 0) {
			$contactName = $langs->trans("SupplierProposal") . ' ' . $object->ref . ' - ' . $object->thirdparty->name;
		}
		break;
	case 'contract':
		require_once DOL_DOCUMENT_ROOT . '/contrat/class/contrat.class.php';
		$object = new Contrat($db);
		if ($object->fetch($objectId) > 0) {
			$contactName = $langs->trans("Contract") . ' ' . $object->ref . ' - ' . $object->thirdparty->name;
		}
		break;
	case 'ticket':
		require_once DOL_DOCUMENT_ROOT . '/ticket/class/ticket.class.php';
		$object = new Ticket($db);
		if ($object->fetch($objectId) > 0) {
			$contactName = $langs->trans("Ticket") . ' ' . $object->ref . ' - ' . $object->thirdparty->name;
		}
		break;
	case 'expensereport':
		require_once DOL_DOCUMENT_ROOT . '/expensereport/class/expensereport.class.php';
		$object = new ExpenseReport($db);
		if ($object->fetch($objectId) > 0) {
			$contactName = $langs->trans("ExpenseReport") . ' ' . $object->ref . ' - ' . $object->thirdparty->name;
		}
		break;
	case 'intervention':
		require_once DOL_DOCUMENT_ROOT . '/fichinter/class/fichinter.class.php';
		$object = new Fichinter($db);
		if ($object->fetch($objectId) > 0) {
			$contactName = $langs->trans("Intervention") . ' ' . $object->ref . ' - ' . $object->thirdparty->name;
		}
		break;
	// CASO PARA PRODUCTOS Y SERVICIOS
	case 'product':
		require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
		$object = new Product($db);
		if ($object->fetch($objectId) > 0) {
			// Determinar si es un producto o un servicio
			if ($object->type == Product::TYPE_SERVICE) {
				$contactName = $langs->trans("Service") . ' ' . $object->ref . ' - ' . $object->label;
			} else {
				$contactName = $langs->trans("Product") . ' ' . $object->ref . ' - ' . $object->label;
			}
		}

		break;
	// Añadir soporte para contactos
	case 'socpeople':
	case 'contact':
		require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';
		$object = new Contact($db);
		if ($object->fetch($objectId) > 0) {
			$contactName = $object->getFullName($langs);
			if ($object->socid > 0) {
				$object->fetch_thirdparty();
				if ($object->thirdparty && $object->thirdparty->id > 0) {
					$contactName .= ' - ' . $object->thirdparty->name;
				}
			}
		}
		break;
	default:
		if ($socid) {
			require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
			$object = new Societe($db);
			if ($object->fetch($socid) > 0) {
				$contactName = $object->name;
			}
		} else {
			$contactName = $langs->trans("Unknown");
		}

		break;
}

// Obtener mensajes de WhatsApp
$whatsAppMessages = getObjectWhatsappMessages($objectId, $objectType, ['AC_SEND_WHATSAPP', 'AC_RECEIVE_WHATSAPP'], true, $socid);
$messages = array();

if (!empty($whatsAppMessages)) {
	foreach ($whatsAppMessages as $message) {
		$messageContent = $message->note_private ?: $message->label;

		// Determinar si el mensaje es saliente o entrante
		$direction = "outgoing";
		if ($message->code == 'AC_RECEIVE_WHATSAPP') {
			$direction = "incoming";
		}

		// Formatear información del objeto vinculado
		$linkedObjectInfo = "";
		$linkedObjectHtml = "";
		if (!empty($message->fk_element) && !empty($message->elementtype)) {
			// Inicializar variable para almacenar el objeto vinculado
			$linkedObj = null;

			// Cargar el objeto vinculado según su tipo
			switch ($message->elementtype) {
				case 'societe':
					require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
					$linkedObj = new Societe($db);
					if ($linkedObj->fetch($message->fk_element) > 0) {
						$objectLabel = $langs->trans("Company");
						$linkedObjectInfo = $objectLabel . ": " . $linkedObj->name;
						$url = DOL_URL_ROOT . '/societe/card.php?socid=' . $linkedObj->id;
						$linkedObjectHtml = '<a href="' . $url . '" target="_blank"><i class="fas fa-building"></i> ' . $objectLabel . ': ' . $linkedObj->name . '</a>';
					}
					break;
				case 'invoice':
					require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
					$linkedObj = new Facture($db);
					if ($linkedObj->fetch($message->fk_element) > 0) {
						$objectLabel = $langs->trans("Invoice");
						$linkedObjectInfo = $objectLabel . ": " . $linkedObj->ref;
						$url = DOL_URL_ROOT . '/compta/facture/card.php?facid=' . $linkedObj->id;
						$linkedObjectHtml = '<a href="' . $url . '" target="_blank"><i class="fas fa-file-invoice-dollar"></i> ' . $objectLabel . ': ' . $linkedObj->ref . '</a>';
					}
					break;
				case 'order':
					require_once DOL_DOCUMENT_ROOT . '/commande/class/commande.class.php';
					$linkedObj = new Commande($db);
					if ($linkedObj->fetch($message->fk_element) > 0) {
						$objectLabel = $langs->trans("Order");
						$linkedObjectInfo = $objectLabel . ": " . $linkedObj->ref;
						$url = DOL_URL_ROOT . '/commande/card.php?id=' . $linkedObj->id;
						$linkedObjectHtml = '<a href="' . $url . '" target="_blank"><i class="fas fa-shopping-cart"></i> ' . $objectLabel . ': ' . $linkedObj->ref . '</a>';
					}
					break;
				case 'propal':
					require_once DOL_DOCUMENT_ROOT . '/comm/propal/class/propal.class.php';
					$linkedObj = new Propal($db);
					if ($linkedObj->fetch($message->fk_element) > 0) {
						$objectLabel = $langs->trans("Proposal");
						$linkedObjectInfo = $objectLabel . ": " . $linkedObj->ref;
						$url = DOL_URL_ROOT . '/comm/propal/card.php?id=' . $linkedObj->id;
						$linkedObjectHtml = '<a href="' . $url . '" target="_blank"><i class="fas fa-file-signature"></i> ' . $objectLabel . ': ' . $linkedObj->ref . '</a>';
					}
					break;
				case 'project':
				case 'projet':
					require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';
					$linkedObj = new Project($db);
					if ($linkedObj->fetch($message->fk_element) > 0) {
						$objectLabel = $langs->trans("Project");
						$linkedObjectInfo = $objectLabel . ": " . $linkedObj->ref;
						$url = DOL_URL_ROOT . '/projet/card.php?id=' . $linkedObj->id;
						$linkedObjectHtml = '<a href="' . $url . '" target="_blank"><i class="fas fa-project-diagram"></i> ' . $objectLabel . ': ' . $linkedObj->ref . '</a>';
					}
					break;
				// NUEVOS CASOS AÑADIDOS
				case 'shipping':
				case 'expedition':
					require_once DOL_DOCUMENT_ROOT . '/expedition/class/expedition.class.php';
					$linkedObj = new Expedition($db);
					if ($linkedObj->fetch($message->fk_element) > 0) {
						$objectLabel = $langs->trans("Shipment");
						$linkedObjectInfo = $objectLabel . ": " . $linkedObj->ref;
						$url = DOL_URL_ROOT . '/expedition/card.php?id=' . $linkedObj->id;
						$linkedObjectHtml = '<a href="' . $url . '" target="_blank"><i class="fas fa-truck"></i> ' . $objectLabel . ': ' . $linkedObj->ref . '</a>';
					}
					break;
				case 'delivery':
				case 'reception':
					require_once DOL_DOCUMENT_ROOT . '/reception/class/reception.class.php';
					$linkedObj = new Reception($db);
					if ($linkedObj->fetch($message->fk_element) > 0) {
						$objectLabel = $langs->trans("Reception");
						$linkedObjectInfo = $objectLabel . ": " . $linkedObj->ref;
						$url = DOL_URL_ROOT . '/reception/card.php?id=' . $linkedObj->id;
						$linkedObjectHtml = '<a href="' . $url . '" target="_blank"><i class="fas fa-dolly"></i> ' . $objectLabel . ': ' . $linkedObj->ref . '</a>';
					}
					break;
				case 'invoice_supplier':
					require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.facture.class.php';
					$linkedObj = new FactureFournisseur($db);
					if ($linkedObj->fetch($message->fk_element) > 0) {
						$objectLabel = $langs->trans("SupplierInvoice");
						$linkedObjectInfo = $objectLabel . ": " . $linkedObj->ref;
						$url = DOL_URL_ROOT . '/fourn/facture/card.php?facid=' . $linkedObj->id;
						$linkedObjectHtml = '<a href="' . $url . '" target="_blank"><i class="fas fa-file-invoice"></i> ' . $objectLabel . ': ' . $linkedObj->ref . '</a>';
					}
					break;
				case 'order_supplier':
					require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.commande.class.php';
					$linkedObj = new CommandeFournisseur($db);
					if ($linkedObj->fetch($message->fk_element) > 0) {
						$objectLabel = $langs->trans("SupplierOrder");
						$linkedObjectInfo = $objectLabel . ": " . $linkedObj->ref;
						$url = DOL_URL_ROOT . '/fourn/commande/card.php?id=' . $linkedObj->id;
						$linkedObjectHtml = '<a href="' . $url . '" target="_blank"><i class="fas fa-shopping-basket"></i> ' . $objectLabel . ': ' . $linkedObj->ref . '</a>';
					}
					break;
				case 'supplier_proposal':
					require_once DOL_DOCUMENT_ROOT . '/supplier_proposal/class/supplier_proposal.class.php';
					$linkedObj = new SupplierProposal($db);
					if ($linkedObj->fetch($message->fk_element) > 0) {
						$objectLabel = $langs->trans("SupplierProposal");
						$linkedObjectInfo = $objectLabel . ": " . $linkedObj->ref;
						$url = DOL_URL_ROOT . '/supplier_proposal/card.php?id=' . $linkedObj->id;
						$linkedObjectHtml = '<a href="' . $url . '" target="_blank"><i class="fas fa-file-signature"></i> ' . $objectLabel . ': ' . $linkedObj->ref . '</a>';
					}
					break;
				case 'contract':
					require_once DOL_DOCUMENT_ROOT . '/contrat/class/contrat.class.php';
					$linkedObj = new Contrat($db);
					if ($linkedObj->fetch($message->fk_element) > 0) {
						$objectLabel = $langs->trans("Contract");
						$linkedObjectInfo = $objectLabel . ": " . $linkedObj->ref;
						$url = DOL_URL_ROOT . '/contrat/card.php?id=' . $linkedObj->id;
						$linkedObjectHtml = '<a href="' . $url . '" target="_blank"><i class="fas fa-handshake"></i> ' . $objectLabel . ': ' . $linkedObj->ref . '</a>';
					}
					break;
				case 'ticket':
					require_once DOL_DOCUMENT_ROOT . '/ticket/class/ticket.class.php';
					$linkedObj = new Ticket($db);
					if ($linkedObj->fetch($message->fk_element) > 0) {
						$objectLabel = $langs->trans("Ticket");
						$linkedObjectInfo = $objectLabel . ": " . $linkedObj->ref;
						$url = DOL_URL_ROOT . '/ticket/card.php?id=' . $linkedObj->id;
						$linkedObjectHtml = '<a href="' . $url . '" target="_blank"><i class="fas fa-ticket-alt"></i> ' . $objectLabel . ': ' . $linkedObj->ref . '</a>';
					}
					break;
				case 'expensereport':
					require_once DOL_DOCUMENT_ROOT . '/expensereport/class/expensereport.class.php';
					$linkedObj = new ExpenseReport($db);
					if ($linkedObj->fetch($message->fk_element) > 0) {
						$objectLabel = $langs->trans("ExpenseReport");
						$linkedObjectInfo = $objectLabel . ": " . $linkedObj->ref;
						$url = DOL_URL_ROOT . '/expensereport/card.php?id=' . $linkedObj->id;
						$linkedObjectHtml = '<a href="' . $url . '" target="_blank"><i class="fas fa-receipt"></i> ' . $objectLabel . ': ' . $linkedObj->ref . '</a>';
					}
					break;
				case 'intervention':
					require_once DOL_DOCUMENT_ROOT . '/fichinter/class/fichinter.class.php';
					$linkedObj = new Fichinter($db);
					if ($linkedObj->fetch($message->fk_element) > 0) {
						$objectLabel = $langs->trans("Intervention");
						$linkedObjectInfo = $objectLabel . ": " . $linkedObj->ref;
						$url = DOL_URL_ROOT . '/fichinter/card.php?id=' . $linkedObj->id;
						$linkedObjectHtml = '<a href="' . $url . '" target="_blank"><i class="fas fa-tools"></i> ' . $objectLabel . ': ' . $linkedObj->ref . '</a>';
					}
					break;
				// CASO PARA PRODUCTOS Y SERVICIOS
				case 'product':
					require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
					$linkedObj = new Product($db);
					if ($linkedObj->fetch($message->fk_element) > 0) {
						// Determinar si es un producto o un servicio
						if ($linkedObj->type == Product::TYPE_SERVICE) {
							$objectLabel = $langs->trans("Service");
							$icon = 'fas fa-concierge-bell';
						} else {
							$objectLabel = $langs->trans("Product");
							$icon = 'fas fa-cube';
						}
						$linkedObjectInfo = $objectLabel . ": " . $linkedObj->ref . ' - ' . $linkedObj->label;
						$url = DOL_URL_ROOT . '/product/card.php?id=' . $linkedObj->id;
						$linkedObjectHtml = '<a href="' . $url . '" target="_blank"><i class="' . $icon . '"></i> ' . $objectLabel . ': ' . $linkedObj->ref . ' - ' . $linkedObj->label . '</a>';
					}
					break;
				// Añadir soporte para contactos
				case 'socpeople':
				case 'contact':
					require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';
					$linkedObj = new Contact($db);
					if ($linkedObj->fetch($message->fk_element) > 0) {
						$objectLabel = $langs->trans("Contact");
						$linkedObjectInfo = $objectLabel . ": " . $linkedObj->getFullName($langs);
						$url = DOL_URL_ROOT . '/contact/card.php?id=' . $linkedObj->id;
						$linkedObjectHtml = '<a href="' . $url . '" target="_blank"><i class="fas fa-user"></i> ' . $objectLabel . ': ' . $linkedObj->getFullName($langs) . '</a>';
					}
					break;
				default:
					$linkedObjectInfo = $message->elementtype . " #" . $message->fk_element;
					$linkedObjectHtml = $linkedObjectInfo;
			}
		} else {
			if ($message->type_code == 'AC_SEND_WHATSAPP_NOTIFY') {
				$linkedObjectInfo = $langs->trans('ActionAC_SEND_WHATSAPP_NOTIFY');
				$linkedObjectHtml = '<span class="badge  badge-status1 badge-status">' . $langs->trans('ActionAC_SEND_WHATSAPP_NOTIFY') . '</span>';
				if ($message->array_options['options_whatsapp_message_sent']) {
					$linkedObjectHtml .= '  <span class="badge  badge-status4 badge-status">' . $langs->trans('WhatsappMessageSend', dol_print_date($message->array_options['options_whatsapp_message_sent'], 'dayhour')) . '</span>';
				} else {
					$linkedObjectHtml .= '  <span class="badge  badge-status8 badge-status">' . $langs->trans('WhatsappMessageNotSend') . '</span>';
				}
			}
		}

		$messages[] = array(
			'id' => $message->id,
			'content' => $messageContent,
			'date' => dol_print_date($message->datec, 'dayhour'),
			'direction' => $direction,
			'type' => $message->code, // Tipo de mensaje (texto, audio, PDF...)
			'array_options' => $message->array_options,
			'linkedobject' => $linkedObjectInfo, // Texto plano (para compatibilidad)
			'linkedobject_html' => $linkedObjectHtml, // HTML con getNomUrl si está disponible
		);
	}
}

// Preparar respuesta
$response = array(
	'success' => true,
	'contactName' => $contactName,
	'messages' => $messages
);

// Devolver datos en formato JSON
echo json_encode($response);
exit;
