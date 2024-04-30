<?php
/* Copyright (C) 2007-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2022 SuperAdmin <marcello.gribaudo@opigi.com> *
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *  \file       invoice_tab.php
 *  \ingroup    manufacturingproduction
 *  \brief      Manage costcenter in customer invoice
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

require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
require_once dol_buildpath('/manufacturingproduction/class/journal.class.php');
require_once dol_buildpath('/manufacturingproduction/class/costcenter.class.php');
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/invoice.lib.php';

// Load translation files required by the page
$langs->loadLangs(array("manufacturingproduction@manufacturingproduction", "bills", "companies"));

// Get parameters
$id = GETPOST('id', 'int');
$ref        = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');
$cancel     = GETPOST('cancel', 'aZ09');
$backtopage = GETPOST('backtopage', 'alpha');
$lineid = GETPOST('lineid', 'int');

// Initialize technical objects
$object = new Facture($db);
$journalstatic= new Journal($db);

// Load object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be include, not include_once  // Must be include, not include_once. Include fetch and fetch_thirdparty but not fetch_optionals
if ($id > 0 || !empty($ref)) {
    $upload_dir = $conf->mrp->multidir_output[$object->entity]."/".$object->id;
}

// Security check - Protection if external user
//if ($user->socid > 0) accessforbidden();
//if ($user->socid > 0) $socid = $user->socid;
$isdraft = (($object->status == $object::STATUS_DRAFT) ? 1 : 0);
$result = restrictedArea($user, 'facture', $object->id);

$permissiontoadd = $user->rights->manufacturingproduction->all->write && (isset($object->status) && $object->status == $object::STATUS_DRAFT);; 
$permissiontodelete = $user->rights->manufacturingproduction->all->delete && (isset($object->status) && $object->status == $object::STATUS_DRAFT);

/*
 * Actions
 */
// Add line
if ($action == 'addline' && $permissiontoadd) {
    $journalstatic->AddJournalLine($object, $object->id);
}

// Update line
if ($action == 'updateline' && !$cancel && $permissiontoadd) {
    $journalstatic->UpdateJournalLine($lineid);
}

// Delete line
if ($action == 'confirm_deleteline' && $permissiontodelete) {
    $journalstatic->DeleteJournalLine($lineid);
}

/*
 * View
 */

$form = new Form($db);
$formproject = new FormProjets($db);

//$help_url='EN:Customers_Orders|FR:Commandes_Clients|ES:Pedidos de clientes';
$help_url = '';
$title = $langs->trans('InvoiceCustomer')." - ".$langs->trans('CostCenters');
llxHeader('', $title, $help_url);

if ($id > 0 || !empty($ref)) {
    $object->fetch_thirdparty();

    $head = facture_prepare_head($object);

    print dol_get_fiche_head($head, 'costcenter', $langs->trans("ManufacturingOrder"), -1, $object->picto);

    $formconfirm = '';
    // Confirmation to delete line
    if ($action == 'deleteline') {
        $formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id.'&lineid='.$lineid, $langs->trans('DeleteLine'), $langs->trans('ConfirmDeleteLine'), 'confirm_deleteline', '', 0, 1);
    }

    // Print form confirm
    print $formconfirm;


//    $linkback = '<a href="'.dol_buildpath('/mrp/mo_list.php', 1).'?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';
    $linkback = '<a href="'.DOL_URL_ROOT.'/compta/facture/list.php?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';

    $morehtmlref = '<div class="refidno">';
    // Ref customer
    //$morehtmlref.=$form->editfieldkey("RefCustomer", 'ref_client', $object->ref_client, $object, 0, 'string', '', 0, 1);
    //$morehtmlref.=$form->editfieldval("RefCustomer", 'ref_client', $object->ref_client, $object, 0, 'string', '', null, null, '', 1);
    // Thirdparty
    $morehtmlref .= $langs->trans('ThirdParty').' : '.(is_object($object->thirdparty) ? $object->thirdparty->getNomUrl(1) : '');
    // Project
    if (!empty($conf->project->enabled)) {
        $langs->load("projects");
        $morehtmlref .= '<br>'.$langs->trans('Project').' ';
        if ($permissiontoadd) {
            if ($action != 'classify') {
                    $morehtmlref .= '<a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?action=classify&token='.newToken().'&id='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('SetProject')).'</a> : ';
            }
            if ($action == 'classify') {
                //$morehtmlref.=$form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->fk_soc, $object->fk_project, 'projectid', 0, 0, 1, 1);
                $morehtmlref .= '<form method="post" action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'">';
                $morehtmlref .= '<input type="hidden" name="action" value="classin">';
                $morehtmlref .= '<input type="hidden" name="token" value="'.newToken().'">';
                $morehtmlref .= $formproject->select_projects($object->fk_soc, $object->fk_project, 'projectid', 0, 0, 1, 0, 1, 0, 0, '', 1);
                $morehtmlref .= '<input type="submit" class="button valignmiddle" value="'.$langs->trans("Modify").'">';
                $morehtmlref .= '</form>';
            } else {
                $morehtmlref .= $form->form_project($_SERVER['PHP_SELF'].'?id='.$object->id, $object->fk_soc, $object->fk_project, 'none', 0, 0, 0, 1);
            }
        } else {
            if (!empty($object->fk_project)) {
                $proj = new Project($db);
                $proj->fetch($object->fk_project);
                $morehtmlref .= ' : '.$proj->getNomUrl();
            } else {
                $morehtmlref .= '';
            }
        }
    }
    $morehtmlref .= '</div>';

    dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);


    print '<form name="addproduct" id="addproduct" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.(($action != 'editline') ? '' : '').'" method="POST">
        <input type="hidden" name="token" value="' . newToken().'">
        <input type="hidden" name="action" value="' . (($action != 'editline') ? 'addline' : 'updateline').'">
        <input type="hidden" name="mode" value="">
                <input type="hidden" name="page_y" value="">
        <input type="hidden" name="id" value="' . $object->id.'">
        ';


    print '<div class="div-table-responsive-no-min">';
    print '<table id="tablelines" class="noborder noshadow" width="100%">';
   

    $totalAmount =  $journalstatic->printJournalLines($object, $object->id, $action, $permissiontoadd, $permissiontodelete);
    
    // Total Amount Line
    print '<tr class="liste_total">';
    print '<td>'.$langs->trans('Total').'</td>';
    print '<td colspan="3"></td>';
    print '<td class="linecolqty right">'.price($totalAmount).'</td>';
    if ($totalAmount <> $object->total_ttc) {
        print '<td colspan="2" class="right amountremaintopay">('.price($object->total_ttc-$totalAmount, 0, '', 1, -1, 2).')</td>';
    };
    print '</tr>';
    

    // Form to add new line
    if ($object->status == 0 && $permissiontoadd && $action != 'selectlines') {
        if ($action != 'editline') {
            $journalstatic->CreateJournalLine();
        }
    }

    if (!empty($object->lines) || ($object->status == $object::STATUS_DRAFT && $permissiontoadd && $action != 'selectlines' && $action != 'editline')) {
        print '</table>';
    }
    print '</div>';

    print "</form>\n";

	print dol_get_fiche_end();
}

// End of page
llxFooter();
$db->close();
