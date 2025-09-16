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
 * \file    lib/whatsapp_templates.lib.php
 * \ingroup whatsapp
 * \brief   Library files with common functions for Templates
 */

/**
 * Prepare array of tabs for Templates
 *
 * @param	Templates	$object		Templates
 * @return 	array					Array of tabs
 */
function templatesPrepareHead($object)
{
	global $db, $langs, $conf;

	$langs->load("whatsapp@whatsapp");

	$showtabofpagecontact = 1;
	$showtabofpagenote = 1;
	$showtabofpagedocument = 1;
	$showtabofpageagenda = 1;

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/whatsapp/templates_card.php", 1) . '?id=' . $object->id;
	$head[$h][1] = $langs->trans("Card");
	$head[$h][2] = 'card';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	//$this->tabs = array(
	//	'entity:+tabname:Title:@whatsapp:/whatsapp/mypage.php?id=__ID__'
	//); // to add new tab
	//$this->tabs = array(
	//	'entity:-tabname:Title:@whatsapp:/whatsapp/mypage.php?id=__ID__'
	//); // to remove a tab
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'templates@whatsapp');

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'templates@whatsapp', 'remove');

	return $head;
}

function getWhatsappTemplatesArray($element=null)
{
	global $db;

	$sql = "SELECT * FROM llx_whatsapp_templates WHERE 1=1 AND type_template = 'all'";
	if($element){
		$sql.=" OR type_template = '".$db->escape($element)."'";
	}
	$sql.=" ORDER BY type_template ASC";
	$resql = $db->query($sql);
	if ($resql) {
		$whatsappTemplates = array();
		while ($obj = $db->fetch_object($resql)) {
			$whatsappTemplates[$obj->rowid] = $obj;
		}
		return $whatsappTemplates;
	} else {
		return false;
	}

}
