<?php
/* Copyright (C) 2017  Laurent Destailleur <eldy@users.sourceforge.net>
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

/**
 * \file        class/templates.class.php
 * \ingroup     whatsapp
 * \brief       This file is a CRUD class file for Templates (Create/Read/Update/Delete)
 */

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';
//require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
//require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';

/**
 * Class for Templates
 */
class Templates extends CommonObject
{
	/**
	 * @var string ID of module.
	 */
	public $module = 'whatsapp';

	/**
	 * @var string ID to identify managed object.
	 */
	public $element = 'templates';

	/**
	 * @var string Name of table without prefix where object is stored. This is also the key used for extrafields management.
	 */
	public $table_element = 'whatsapp_templates';

	/**
	 * @var int  Does this object support multicompany module ?
	 * 0=No test on entity, 1=Test with field entity, 'field@table'=Test with link by field@table
	 */
	public $ismultientitymanaged = 0;

	/**
	 * @var int  Does object support extrafields ? 0=No, 1=Yes
	 */
	public $isextrafieldmanaged = 0;

	/**
	 * @var string String with name of icon for templates. Must be a 'fa-xxx' fontawesome code (or 'fa-xxx_fa_color_size') or 'templates@whatsapp' if picto is file 'img/object_templates.png'.
	 */
	public $picto = 'fa-whatsapp';




	/**
	 *  'type' field format:
	 *  	'integer', 'integer:ObjectClass:PathToClass[:AddCreateButtonOrNot[:Filter[:Sortfield]]]',
	 *  	'select' (list of values are in 'options'),
	 *  	'sellist:TableName:LabelFieldName[:KeyFieldName[:KeyFieldParent[:Filter[:Sortfield]]]]',
	 *  	'chkbxlst:...',
	 *  	'varchar(x)',
	 *  	'text', 'text:none', 'html',
	 *   	'double(24,8)', 'real', 'price',
	 *  	'date', 'datetime', 'timestamp', 'duration',
	 *  	'boolean', 'checkbox', 'radio', 'array',
	 *  	'mail', 'phone', 'url', 'password', 'ip'
	 *		Note: Filter can be a string like "(t.ref:like:'SO-%') or (t.date_creation:<:'20160101') or (t.nature:is:NULL)"
	 *  'label' the translation key.
	 *  'picto' is code of a picto to show before value in forms
	 *  'enabled' is a condition when the field must be managed (Example: 1 or '$conf->global->MY_SETUP_PARAM' or '!empty($conf->multicurrency->enabled)' ...)
	 *  'position' is the sort order of field.
	 *  'notnull' is set to 1 if not null in database. Set to -1 if we must set data to null if empty ('' or 0).
	 *  'visible' says if field is visible in list (Examples: 0=Not visible, 1=Visible on list and create/update/view forms, 2=Visible on list only, 3=Visible on create/update/view form only (not list), 4=Visible on list and update/view form only (not create). 5=Visible on list and view only (not create/not update). Using a negative value means field is not shown by default on list but can be selected for viewing)
	 *  'noteditable' says if field is not editable (1 or 0)
	 *  'default' is a default value for creation (can still be overwrote by the Setup of Default Values if field is editable in creation form). Note: If default is set to '(PROV)' and field is 'ref', the default value will be set to '(PROVid)' where id is rowid when a new record is created.
	 *  'index' if we want an index in database.
	 *  'foreignkey'=>'tablename.field' if the field is a foreign key (it is recommanded to name the field fk_...).
	 *  'searchall' is 1 if we want to search in this field when making a search from the quick search button.
	 *  'isameasure' must be set to 1 or 2 if field can be used for measure. Field type must be summable like integer or double(24,8). Use 1 in most cases, or 2 if you don't want to see the column total into list (for example for percentage)
	 *  'css' and 'cssview' and 'csslist' is the CSS style to use on field. 'css' is used in creation and update. 'cssview' is used in view mode. 'csslist' is used for columns in lists. For example: 'css'=>'minwidth300 maxwidth500 widthcentpercentminusx', 'cssview'=>'wordbreak', 'csslist'=>'tdoverflowmax200'
	 *  'help' is a 'TranslationString' to use to show a tooltip on field. You can also use 'TranslationString:keyfortooltiponlick' for a tooltip on click.
	 *  'showoncombobox' if value of the field must be visible into the label of the combobox that list record
	 *  'disabled' is 1 if we want to have the field locked by a 'disabled' attribute. In most cases, this is never set into the definition of $fields into class, but is set dynamically by some part of code.
	 *  'arrayofkeyval' to set a list of values if type is a list of predefined values. For example: array("0"=>"Draft","1"=>"Active","-1"=>"Cancel"). Note that type can be 'integer' or 'varchar'
	 *  'autofocusoncreate' to have field having the focus on a create form. Only 1 field should have this property set to 1.
	 *  'comment' is not used. You can store here any text of your choice. It is not used by application.
	 *	'validate' is 1 if need to validate with $this->validateField()
	 *  'copytoclipboard' is 1 or 2 to allow to add a picto to copy value into clipboard (1=picto after label, 2=picto after value)
	 *
	 *  Note: To have value dynamic, you can set value to 0 in definition and edit the value on the fly into the constructor.
	 */

	// BEGIN MODULEBUILDER PROPERTIES
	/**
	 * @var array  Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
	 */
	public $fields = array(
		'rowid' => array('type' => 'integer', 'label' => 'TechnicalID', 'enabled' => '1', 'position' => 1, 'notnull' => 1, 'visible' => 1, 'noteditable' => '1', 'index' => 1, 'css' => 'left', 'comment' => "Id"),
		'type_template' => array('type' => 'select', 'label' => 'TypeOfTemplate', 'enabled' => '1', 'default' => 'all', 'position' => 10, 'notnull' => 1, 'visible' => 1, 'searchall' => 1, 'css' => 'minwidth300', 'cssview' => 'wordbreak', 'help' => "Help text", 'showoncombobox' => '2', 'arrayofkeyval' => null,),
		'label' => array('type' => 'varchar(255)', 'label' => 'Label', 'enabled' => '1', 'position' => 30, 'notnull' => 1, 'visible' => 1, 'searchall' => 1, 'css' => 'minwidth300', 'cssview' => 'wordbreak', 'help' => "Help text", 'showoncombobox' => '2', 'validate' => '1',),
		'topic' => array('type' => 'varchar(255)', 'label' => 'Topic', 'enabled' => '1', 'position' => 30, 'notnull' => 1, 'visible' => 1, 'searchall' => 1, 'css' => 'minwidth300', 'cssview' => 'wordbreak', 'help' => "Help text", 'showoncombobox' => '2', 'validate' => '1',),
		'content' => array('type' => 'html', 'label' => 'Content', 'enabled' => '1', 'position' => 60, 'notnull' => 1, 'visible' => 3, 'validate' => '1',),
		'date_creation' => array('type' => 'datetime', 'label' => 'DateCreation', 'enabled' => '1', 'position' => 500, 'notnull' => 1, 'visible' => -2,),
		'tms' => array('type' => 'timestamp', 'label' => 'DateModification', 'enabled' => '1', 'position' => 501, 'notnull' => 0, 'visible' => -2,),
		'fk_user_creat' => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'UserAuthor', 'picto' => 'user', 'enabled' => '1', 'position' => 510, 'notnull' => 1, 'visible' => -2, 'foreignkey' => 'user.rowid',),
		'fk_user_modif' => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'UserModif', 'picto' => 'user', 'enabled' => '1', 'position' => 511, 'notnull' => -1, 'visible' => -2,),
	);
	public $rowid;
	public $type_template;
	public $label;
	public $topic;
	public $content;
	public $date_creation;
	public $tms;
	public $fk_user_creat;
	public $fk_user_modif;
	// END MODULEBUILDER PROPERTIES


	// If this object has a subtable with lines

	// /**
	//  * @var string    Name of subtable line
	//  */
	// public $table_element_line = 'whatsapp_templatesline';

	// /**
	//  * @var string    Field with ID of parent key if this object has a parent
	//  */
	// public $fk_element = 'fk_templates';

	// /**
	//  * @var string    Name of subtable class that manage subtable lines
	//  */
	// public $class_element_line = 'Templatesline';

	// /**
	//  * @var array	List of child tables. To test if we can delete object.
	//  */
	// protected $childtables = array();

	// /**
	//  * @var array    List of child tables. To know object to delete on cascade.
	//  *               If name matches '@ClassNAme:FilePathClass;ParentFkFieldName' it will
	//  *               call method deleteByParentField(parentId, ParentFkFieldName) to fetch and delete child object
	//  */
	// protected $childtablesoncascade = array('whatsapp_templatesdet');

	// /**
	//  * @var TemplatesLine[]     Array of subtable lines
	//  */
	// public $lines = array();

	public function getLibStatut($n)
	{
		return '';
	}

	/**
	 * Constructor
	 *
	 * @param DoliDb $db Database handler
	 */
	public function __construct(DoliDB $db)
	{
		global $conf, $langs, $user, $hookmanager;

		$this->db = $db;

		if (empty($conf->global->MAIN_SHOW_TECHNICAL_ID) && isset($this->fields['rowid']) && !empty($this->fields['ref'])) {
			$this->fields['rowid']['visible'] = 0;
		}
		if (empty($conf->multicompany->enabled) && isset($this->fields['entity'])) {
			$this->fields['entity']['enabled'] = 0;
		}

		// Example to show how to set values of fields definition dynamically
		/*if ($user->rights->whatsapp->templates->read) {
			$this->fields['myfield']['visible'] = 1;
			$this->fields['myfield']['noteditable'] = 0;
		}*/

		// Unset fields that are disabled
		foreach ($this->fields as $key => $val) {
			if (isset($val['enabled']) && empty($val['enabled'])) {
				unset($this->fields[$key]);
			}
		}

		// Translate some data of arrayofkeyval
		if (is_object($langs)) {
			foreach ($this->fields as $key => $val) {
				if (!empty($val['arrayofkeyval']) && is_array($val['arrayofkeyval'])) {
					foreach ($val['arrayofkeyval'] as $key2 => $val2) {
						$this->fields[$key]['arrayofkeyval'][$key2] = $langs->trans($val2);
					}
				}
			}
		}
		dol_include_once('/core/lib/functions.lib.php');

		//Load subtitutions array
		$tmparray = getCommonSubstitutionArray($langs, 2); // Note: On email templated edition, this is null because it is related to all type of objects
		$parameters = [];
		complete_substitutions_array($tmparray, $langs, null, $parameters);
		//recorrer el tmparray y meterlo como txto clave valor en los feilds helptopic y helpcontent

		$text = '<strong>' . $langs->trans('AvailableVariables') . ':</strong><br>';
		foreach ($tmparray as $key => $val) {
			$text .= $key . ' = ' . $val . "<br>";
		}
		$this->fields['topic']['help'] = $text;
		$this->fields['content']['help'] = $text;
		//Load elements types

		// We save list of template email Dolibarr can manage. This list can found by a grep into code on "->param['models']"
		$elementList = array();
		// Add all and none after the sort

		$elementList['all'] = '-- ' . dol_escape_htmltag($langs->trans("All")) . ' --';
		$elementList['user'] = img_picto('', 'user', 'class="pictofixedwidth"') . dol_escape_htmltag($langs->trans('WhatsappToUser'));
		if (!empty($conf->adherent->enabled) && !empty($user->rights->adherent->lire)) {
			$elementList['member'] = img_picto('', 'object_member', 'class="pictofixedwidth"') . dol_escape_htmltag($langs->trans('WhatsappToMember'));
		}
		if (!empty($conf->recruitment->enabled) && !empty($user->rights->recruitment->recruitmentjobposition->read)) {
			$elementList['recruitmentcandidature'] = img_picto('', 'recruitmentcandidature', 'class="pictofixedwidth"') . dol_escape_htmltag($langs->trans('RecruitmentCandidatures'));
		}
		if (!empty($conf->societe->enabled) && !empty($user->rights->societe->lire)) {
			$elementList['thirdparty'] = img_picto('', 'company', 'class="pictofixedwidth"') . dol_escape_htmltag($langs->trans('WhatsappToThirdparty'));
		}
		if (!empty($conf->project->enabled)) {
			$elementList['project'] = img_picto('', 'project', 'class="pictofixedwidth"') . dol_escape_htmltag($langs->trans('WhatsappToProject'));
		}
		if (!empty($conf->propal->enabled) && !empty($user->rights->propal->lire)) {
			$elementList['propal'] = img_picto('', 'propal', 'class="pictofixedwidth"') . dol_escape_htmltag($langs->trans('WhatsappToSendProposal'));
		}
		if (!empty($conf->commande->enabled) && !empty($user->rights->commande->lire)) {
			$elementList['order'] = img_picto('', 'order', 'class="pictofixedwidth"') . dol_escape_htmltag($langs->trans('WhatsappToSendOrder'));
		}
		if (isModEnabled('facture') && !empty($user->rights->facture->lire)) {
			$elementList['facture'] = img_picto('', 'bill', 'class="pictofixedwidth"') . dol_escape_htmltag($langs->trans('WhatsappToSendInvoice'));
		}
		if (!empty($conf->expedition->enabled)) {
			$elementList['shipping'] = img_picto('', 'dolly', 'class="pictofixedwidth"') . dol_escape_htmltag($langs->trans('WhatsappToSendShipment'));
		}
		if (!empty($conf->reception->enabled)) {
			$elementList['reception'] = img_picto('', 'dollyrevert', 'class="pictofixedwidth"') . dol_escape_htmltag($langs->trans('WhatsappToSendReception'));
		}
		if (!empty($conf->ficheinter->enabled)) {
			$elementList['fichinter'] = img_picto('', 'intervention', 'class="pictofixedwidth"') . dol_escape_htmltag($langs->trans('WhatsappToSendIntervention'));
		}
		if (!empty($conf->supplier_proposal->enabled)) {
			$elementList['supplier_proposal'] = img_picto('', 'propal', 'class="pictofixedwidth"') . dol_escape_htmltag($langs->trans('WhatsappToSendSupplierRequestForQuotation'));
		}
		if ((!empty($conf->fournisseur->enabled) && !empty($user->rights->fournisseur->commande->lire) && empty($conf->global->MAIN_USE_NEW_SUPPLIERMOD)) || (!empty($conf->supplier_order->enabled) && !empty($user->rights->supplier_order->lire))) {
			$elementList['order_supplier'] = img_picto('', 'order', 'class="pictofixedwidth"') . dol_escape_htmltag($langs->trans('WhatsappToSendSupplierOrder'));
		}
		if ((!empty($conf->fournisseur->enabled) && !empty($user->rights->fournisseur->facture->lire) && empty($conf->global->MAIN_USE_NEW_SUPPLIERMOD)) || (!empty($conf->supplier_invoice->enabled) && !empty($user->rights->supplier_invoice->lire))) {
			$elementList['invoice_supplier'] = img_picto('', 'bill', 'class="pictofixedwidth"') . dol_escape_htmltag($langs->trans('WhatsappToSendSupplierInvoice'));
		}
		if (!empty($conf->contrat->enabled) && !empty($user->rights->contrat->lire)) {
			$elementList['contract'] = img_picto('', 'contract', 'class="pictofixedwidth"') . dol_escape_htmltag($langs->trans('WhatsappToSendContract'));
		}
		if (!empty($conf->ticket->enabled) && !empty($user->rights->ticket->read)) {
			$elementList['ticket'] = img_picto('', 'ticket', 'class="pictofixedwidth"') . dol_escape_htmltag($langs->trans('WhatsappToTicket'));
		}
		if (!empty($conf->expensereport->enabled) && !empty($user->rights->expensereport->lire)) {
			$elementList['expensereport'] = img_picto('', 'trip', 'class="pictofixedwidth"') . dol_escape_htmltag($langs->trans('WhatsappToExpenseReport'));
		}
		if (isModEnabled('agenda')) {
			$elementList['actioncomm'] = img_picto('', 'action', 'class="pictofixedwidth"') . dol_escape_htmltag($langs->trans('WhatsappToSendEventPush'));
		}
		if (!empty($conf->eventorganization->enabled) && !empty($user->rights->eventorganization->read)) {
			$elementList['conferenceorbooth'] = img_picto('', 'action', 'class="pictofixedwidth"') . dol_escape_htmltag($langs->trans('WhatsappToSendEventOrganization'));
		}
		if (!empty($conf->partnership->enabled) && !empty($user->rights->partnership->read)) {
			$elementList['partnership'] = img_picto('', 'partnership', 'class="pictofixedwidth"') . dol_escape_htmltag($langs->trans('WhatsappToPartnership'));
		}
		$hookmanager->initHooks(array('whatsapptemplates'));

		$parameters = array('elementList' => $elementList);
		$reshook = $hookmanager->executeHooks('whatsappElementlist', $parameters); // Note that $action and $object may have been modified by some hooks
		if ($reshook == 0) {
			foreach ($hookmanager->resArray as $item => $value) {
				$elementList[$item] = $value;
			}
		}
		$this->fields['type_template']['arrayofkeyval'] = $elementList;
	}

	/**
	 * Create object into database
	 *
	 * @param  User $user      User that creates
	 * @param  bool $notrigger false=launch triggers after, true=disable triggers
	 * @return int             <0 if KO, Id of created object if OK
	 */
	public function create(User $user, $notrigger = false)
	{
		$resultcreate = $this->createCommon($user, $notrigger);

		//$resultvalidate = $this->validate($user, $notrigger);

		return $resultcreate;
	}



	/**
	 * Load object in memory from the database
	 *
	 * @param int    $id   Id object
	 * @param string $ref  Ref
	 * @return int         <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetch($id, $ref = null)
	{
		$result = $this->fetchCommon($id, $ref);
		return $result;
	}



	/**
	 * Load list of objects in memory from the database.
	 *
	 * @param  string      $sortorder    Sort Order
	 * @param  string      $sortfield    Sort field
	 * @param  int         $limit        limit
	 * @param  int         $offset       Offset
	 * @param  array       $filter       Filter array. Example array('field'=>'valueforlike', 'customurl'=>...)
	 * @param  string      $filtermode   Filter mode (AND or OR)
	 * @return array|int                 int <0 if KO, array of pages if OK
	 */
	public function fetchAll($sortorder = '', $sortfield = '', $limit = 0, $offset = 0, array $filter = array(), $filtermode = 'AND')
	{
		global $conf;

		dol_syslog(__METHOD__, LOG_DEBUG);

		$records = array();

		$sql = "SELECT ";
		$sql .= $this->getFieldList('t');
		$sql .= " FROM " . MAIN_DB_PREFIX . $this->table_element . " as t";
		if (isset($this->ismultientitymanaged) && $this->ismultientitymanaged == 1) {
			$sql .= " WHERE t.entity IN (" . getEntity($this->element) . ")";
		} else {
			$sql .= " WHERE 1 = 1";
		}
		// Manage filter
		$sqlwhere = array();
		if (count($filter) > 0) {
			foreach ($filter as $key => $value) {
				if ($key == 't.rowid') {
					$sqlwhere[] = $key . " = " . ((int) $value);
				} elseif (in_array($this->fields[$key]['type'], array('date', 'datetime', 'timestamp'))) {
					$sqlwhere[] = $key . " = '" . $this->db->idate($value) . "'";
				} elseif ($key == 'customsql') {
					$sqlwhere[] = $value;
				} elseif (strpos($value, '%') === false) {
					$sqlwhere[] = $key . " IN (" . $this->db->sanitize($this->db->escape($value)) . ")";
				} else {
					$sqlwhere[] = $key . " LIKE '%" . $this->db->escape($value) . "%'";
				}
			}
		}
		if (count($sqlwhere) > 0) {
			$sql .= " AND (" . implode(" " . $filtermode . " ", $sqlwhere) . ")";
		}

		if (!empty($sortfield)) {
			$sql .= $this->db->order($sortfield, $sortorder);
		}
		if (!empty($limit)) {
			$sql .= $this->db->plimit($limit, $offset);
		}

		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < ($limit ? min($limit, $num) : $num)) {
				$obj = $this->db->fetch_object($resql);

				$record = new self($this->db);
				$record->setVarsFromFetchObj($obj);

				$records[$record->id] = $record;

				$i++;
			}
			$this->db->free($resql);

			return $records;
		} else {
			$this->errors[] = 'Error ' . $this->db->lasterror();
			dol_syslog(__METHOD__ . ' ' . join(',', $this->errors), LOG_ERR);

			return -1;
		}
	}

	/**
	 * Update object into database
	 *
	 * @param  User $user      User that modifies
	 * @param  bool $notrigger false=launch triggers after, true=disable triggers
	 * @return int             <0 if KO, >0 if OK
	 */
	public function update(User $user, $notrigger = false)
	{
		return $this->updateCommon($user, $notrigger);
	}

	/**
	 * Delete object in database
	 *
	 * @param User $user       User that deletes
	 * @param bool $notrigger  false=launch triggers after, true=disable triggers
	 * @return int             <0 if KO, >0 if OK
	 */
	public function delete(User $user, $notrigger = false)
	{
		return $this->deleteCommon($user, $notrigger);
		//return $this->deleteCommon($user, $notrigger, 1);
	}


	/**
	 *  Return a link to the object card (with optionaly the picto)
	 *
	 *  @param  int     $withpicto                  Include picto in link (0=No picto, 1=Include picto into link, 2=Only picto)
	 *  @param  string  $option                     On what the link point to ('nolink', ...)
	 *  @param  int     $notooltip                  1=Disable tooltip
	 *  @param  string  $morecss                    Add more css on link
	 *  @param  int     $save_lastsearch_value      -1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
	 *  @return	string                              String with URL
	 */
	public function getNomUrl($withpicto = 0, $option = '', $notooltip = 0, $morecss = '', $save_lastsearch_value = -1)
	{
		global $conf, $langs, $hookmanager;

		if (!empty($conf->dol_no_mouse_hover)) {
			$notooltip = 1; // Force disable tooltips
		}

		$result = '';

		$label = img_picto('', $this->picto) . ' <u>' . $langs->trans("Templates") . '</u>';
		if (isset($this->status)) {
			$label .= ' ' . $this->getLibStatut(5);
		}
		$label .= '<br>';
		$label .= '<b>' . $langs->trans('Ref') . ':</b> ' . $this->ref;

		$url = dol_buildpath('/whatsapp/templates_card.php', 1) . '?id=' . $this->id;

		if ($option != 'nolink') {
			// Add param to save lastsearch_values or not
			$add_save_lastsearch_values = ($save_lastsearch_value == 1 ? 1 : 0);
			if ($save_lastsearch_value == -1 && preg_match('/list\.php/', $_SERVER["PHP_SELF"])) {
				$add_save_lastsearch_values = 1;
			}
			if ($url && $add_save_lastsearch_values) {
				$url .= '&save_lastsearch_values=1';
			}
		}

		$linkclose = '';
		if (empty($notooltip)) {
			if (!empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER)) {
				$label = $langs->trans("ShowTemplates");
				$linkclose .= ' alt="' . dol_escape_htmltag($label, 1) . '"';
			}
			$linkclose .= ' title="' . dol_escape_htmltag($label, 1) . '"';
			$linkclose .= ' class="classfortooltip' . ($morecss ? ' ' . $morecss : '') . '"';
		} else {
			$linkclose = ($morecss ? ' class="' . $morecss . '"' : '');
		}

		if ($option == 'nolink' || empty($url)) {
			$linkstart = '<span';
		} else {
			$linkstart = '<a href="' . $url . '"';
		}
		$linkstart .= $linkclose . '>';
		if ($option == 'nolink' || empty($url)) {
			$linkend = '</span>';
		} else {
			$linkend = '</a>';
		}

		$result .= $linkstart;

		if (empty($this->showphoto_on_popup)) {
			if ($withpicto) {
				$result .= img_object(($notooltip ? '' : $label), ($this->picto ? $this->picto : 'generic'), ($notooltip ? (($withpicto != 2) ? 'class="paddingright"' : '') : 'class="' . (($withpicto != 2) ? 'paddingright ' : '') . 'classfortooltip"'), 0, 0, $notooltip ? 0 : 1);
			}
		} else {
			if ($withpicto) {
				require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

				list($class, $module) = explode('@', $this->picto);
				$upload_dir = $conf->$module->multidir_output[$conf->entity] . "/$class/" . dol_sanitizeFileName($this->ref);
				$filearray = dol_dir_list($upload_dir, "files");
				$filename = $filearray[0]['name'];
				if (!empty($filename)) {
					$pospoint = strpos($filearray[0]['name'], '.');

					$pathtophoto = $class . '/' . $this->ref . '/thumbs/' . substr($filename, 0, $pospoint) . '_mini' . substr($filename, $pospoint);
					if (empty($conf->global->{strtoupper($module . '_' . $class) . '_FORMATLISTPHOTOSASUSERS'})) {
						$result .= '<div class="floatleft inline-block valignmiddle divphotoref"><div class="photoref"><img class="photo' . $module . '" alt="No photo" border="0" src="' . DOL_URL_ROOT . '/viewimage.php?modulepart=' . $module . '&entity=' . $conf->entity . '&file=' . urlencode($pathtophoto) . '"></div></div>';
					} else {
						$result .= '<div class="floatleft inline-block valignmiddle divphotoref"><img class="photouserphoto userphoto" alt="No photo" border="0" src="' . DOL_URL_ROOT . '/viewimage.php?modulepart=' . $module . '&entity=' . $conf->entity . '&file=' . urlencode($pathtophoto) . '"></div>';
					}

					$result .= '</div>';
				} else {
					$result .= img_object(($notooltip ? '' : $label), ($this->picto ? $this->picto : 'generic'), ($notooltip ? (($withpicto != 2) ? 'class="paddingright"' : '') : 'class="' . (($withpicto != 2) ? 'paddingright ' : '') . 'classfortooltip"'), 0, 0, $notooltip ? 0 : 1);
				}
			}
		}

		if ($withpicto != 2) {
			$result .= $this->ref;
		}

		$result .= $linkend;
		//if ($withpicto != 2) $result.=(($addlabel && $this->label) ? $sep . dol_trunc($this->label, ($addlabel > 1 ? $addlabel : 0)) : '');

		global $action, $hookmanager;
		$hookmanager->initHooks(array('templatesdao'));
		$parameters = array('id' => $this->id, 'getnomurl' => &$result);
		$reshook = $hookmanager->executeHooks('getNomUrl', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
		if ($reshook > 0) {
			$result = $hookmanager->resPrint;
		} else {
			$result .= $hookmanager->resPrint;
		}

		return $result;
	}

	/**
	 *	Load the info information in the object
	 *
	 *	@param  int		$id       Id of object
	 *	@return	void
	 */
	public function info($id)
	{
		$sql = "SELECT rowid,";
		$sql .= " date_creation as datec, tms as datem,";
		$sql .= " fk_user_creat, fk_user_modif";
		$sql .= " FROM " . MAIN_DB_PREFIX . $this->table_element . " as t";
		$sql .= " WHERE t.rowid = " . ((int) $id);

		$result = $this->db->query($sql);
		if ($result) {
			if ($this->db->num_rows($result)) {
				$obj = $this->db->fetch_object($result);

				$this->id = $obj->rowid;

				$this->user_creation_id = $obj->fk_user_creat;
				$this->user_modification_id = $obj->fk_user_modif;
				if (!empty($obj->fk_user_valid)) {
					$this->user_validation_id = $obj->fk_user_valid;
				}
				$this->date_creation     = $this->db->jdate($obj->datec);
				$this->date_modification = empty($obj->datem) ? '' : $this->db->jdate($obj->datem);
				if (!empty($obj->datev)) {
					$this->date_validation   = empty($obj->datev) ? '' : $this->db->jdate($obj->datev);
				}
			}

			$this->db->free($result);
		} else {
			dol_print_error($this->db);
		}
	}
}
