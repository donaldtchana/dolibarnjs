<?php
/* Copyright (C) 2017-2020  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2022 SuperAdmin <marcello.gribaudo@opigi.com>
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
 *  \file       bom_tab.php
 *  \ingroup    manufacturingproduction
 *  \brief      List if costcenter in bom
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
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) {
	$res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
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
if (!$res) {
	die("Include of main fails");
}

//require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/bom/class/bom.class.php';
require_once DOL_DOCUMENT_ROOT.'/bom/lib/bom.lib.php';
require_once dol_buildpath('/manufacturingproduction/lib/manufacturingproduction.lib.php');
require_once dol_buildpath('/manufacturingproduction/class/costcenter.class.php');


// Load translation files required by the page
$langs->loadLangs(array("manufacturingproduction@manufacturingproduction", "mrp", "other"));

// Get parameters
$id = GETPOST('id', 'int');
$ref        = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');
$contextpage = GETPOST('contextpage', 'aZ') ?GETPOST('contextpage', 'aZ') : 'bom_tabd'; // To manage different context of search
$backtopage = GETPOST('backtopage', 'alpha');
$lineid = GETPOST('lineid', 'int');
$cancel     = GETPOST('cancel', 'aZ09');

// Initialize technical objects
$object = new BOM($db);
$extrafields = new ExtraFields($db);

// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

if (empty($action) && empty($id) && empty($ref)) {
    $action = 'view';
}

// Load object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be include, not include_once.
if ($object->id > 0) {
    $object->calculateCosts();
}


$isdraft = (($object->status == $object::STATUS_DRAFT) ? 1 : 0);
$result = restrictedArea($user, 'bom', $object->id, 'bom_bom', '', '', 'rowid', $isdraft);

$permissiontoadd = $user->rights->manufacturingproduction->all->write && (isset($object->status) && $object->status == $object::STATUS_DRAFT);; 
$permissiontodelete = $user->rights->manufacturingproduction->all->delete && (isset($object->status) && $object->status == $object::STATUS_DRAFT);

/*
 * Actions
 */


$error = 0;

$backurlforlist = DOL_URL_ROOT.'/bom/bom_list.php';

if (empty($backtopage) || ($cancel && empty($id))) {
    if (empty($backtopage) || ($cancel && strpos($backtopage, '__ID__'))) {
        if (empty($id) && (($action != 'add' && $action != 'create') || $cancel)) {
            $backtopage = $backurlforlist;
        } else {
            $backtopage = DOL_URL_ROOT.'/bom/bom_card.php?id='.($id > 0 ? $id : '__ID__');
        }
    }
}

// Add line
if ($action == 'addline' && $permissiontoadd) {
    $costcenter = GETPOST('costcenter', 'int');
    $qty = GETPOST('qty', 'int');
    add_costcenter($object->id, 'manufacturingproduction_bom', $costcenter, $qty);
}

// Update line
if ($action == 'updateline' &&!$cancel && $permissiontoadd) {
    $rowid = GETPOST('lineid', 'int');
    $costcenter = GETPOST('costcenter', 'int');
    $qty = GETPOST('qty', 'int');
    save_costcenter($rowid, 'manufacturingproduction_bom', $costcenter, $qty);
}

// Delete line
if ($action == 'confirm_deleteline' && $permissiontodelete) {
    $rowid = GETPOST('lineid', 'int');
    delete_costcenter($rowid, 'manufacturingproduction_bom');
}


/*
 * View
 */

$form = new Form($db);
$formfile = new FormFile($db);


$title = $langs->trans('BOM');
llxHeader('', $title);

// Part to show record
if ($object->id > 0 && (empty($action) || ($action != 'edit' && $action != 'create'))) {
    $head = bomPrepareHead($object);
    print dol_get_fiche_head($head, 'production', $langs->trans("BillOfMaterials"), -1, 'bom');

    $formconfirm = '';

    // Confirmation to delete line
    if ($action == 'deleteline') {
        $formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id.'&lineid='.$lineid, $langs->trans('DeleteLine'), $langs->trans('ConfirmDeleteLine'), 'confirm_deleteline', '', 0, 1);
    }

    // Print form confirm
    print $formconfirm;


    // Object card
    // ------------------------------------------------------------
    $linkback = '<a href="'.DOL_URL_ROOT.'/bom/bom_list.php?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';

    $morehtmlref = '<div class="refidno">';
    $morehtmlref .= '</div>';


    dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);

    // BOM header 
    print '<div class="fichecenter">';
    print '<div class="fichehalfleft">';
    print '<div class="underbanner clearboth"></div>';
    print '<table class="border centpercent tableforfield">'."\n";

    // Common attributes
    $keyforbreak = 'duration';
    include DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_view.tpl.php';
    $object->calculateCosts();
    print '<tr><td>'.$form->textwithpicto($langs->trans("TotalCost"), $langs->trans("BOMTotalCost")).'</td><td><span class="amount">'.price($object->total_cost).'</span></td></tr>';
    print '<tr><td>'.$langs->trans("UnitCost").'</td><td>'.price($object->unit_cost).'</td></tr>';

    // Other attributes
    include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_view.tpl.php';

    print '</table>';
    print '</div>';
    print '</div>';

    print '<div class="clearboth"></div>';

    print dol_get_fiche_end();
    

    /*
     * Lines
     */

    print '<form name="addproduct" id="addproduct" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.(($action != 'editline') ? '' : '').'" method="POST">
        <input type="hidden" name="token" value="' . newToken().'">
        <input type="hidden" name="action" value="' . (($action != 'editline') ? 'addline' : 'updateline').'">
        <input type="hidden" name="mode" value="">
                <input type="hidden" name="page_y" value="">
        <input type="hidden" name="id" value="' . $object->id.'">
        ';


    print '<div class="div-table-responsive-no-min">';
    print '<table id="tablelines" class="noborder noshadow" width="100%">';
   

    printCostCenteLines($object, $object->id, $action, 'manufacturingproduction_bom', $permissiontoadd, $permissiontodelete);

    // Form to add new line
    if ($object->status == 0 && $permissiontoadd && $action != 'selectlines') {
        if ($action != 'editline') {
            CreateCostCenterLine();
        }
    }

    if (!empty($object->lines) || ($object->status == $object::STATUS_DRAFT && $permissiontoadd && $action != 'selectlines' && $action != 'editline')) {
        print '</table>';
    }
    print '</div>';

    print "</form>\n";

    mrpCollapseBomManagement();
}

// End of page
llxFooter();
$db->close();
