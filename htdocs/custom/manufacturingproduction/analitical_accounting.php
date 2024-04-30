<?php
/* Copyright (C) 2017 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *   	\file       journal_card.php
 *		\ingroup    manufacturingproduction
 *		\brief      Analitical Accounting
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

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
dol_include_once('/manufacturingproduction/class/journal.class.php');
require_once 'lib/manufacturingproduction.lib.php';
if (!empty($conf->project->enabled)) {
    require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
}


// Load translation files required by the page
$langs->loadLangs(array("manufacturingproduction@manufacturingproduction", "bills"));

// Get parameters
$action = GETPOST('action', 'aZ09');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : str_replace('_', '', basename(dirname(__FILE__)).basename(__FILE__, '.php')); // To manage different context of search
$dtstart = dol_mktime(0, 0, 0, GETPOST('dtstartmonth', 'int'), GETPOST('dtstartday', 'int'), GETPOST('dtstartyear', 'int'));
$dtend = dol_mktime(23, 59, 59, GETPOST('dtendmonth', 'int'), GETPOST('dtendday', 'int'), GETPOST('dtendyear', 'int'));
$costcenterFrom = GETPOST('costcenterFrom', 'int');
$costcenterTo = GETPOST('costcenterTo', 'int');
$projectFrom = GETPOST('projectFrom', 'int');
$projectTo = GETPOST('projectTo', 'int');
if (GETPOST('btnExcel', 'alpha'))
    $model = 'excel2007';
elseif (GETPOST('btncsv', 'alpha'))
    $model = 'csv';
elseif (GETPOST('btngraph', 'alpha'))
    $model = 'graph';
else
    $model = '';
$type = GETPOST('type', 'alpha');
if (!$type)
    $type = 'costcenter';
$detail = GETPOST('detail', 'alpha');
if (!$detail)
    $detail = 'syntetic';
$totalizePeriod = GETPOST('total', 'alpha');
if (!$totalizePeriod)
    $totalizePeriod = 'none';
$level = GETPOST('level', 'alpha');
if (!$level)
    $level = 'none';
$printPeriodTot = $totalizePeriod!='none';// && $detail!='syntetic'; 

// Initialize technical objects
$object = new Journal($db);

$permissiontoread = $user->rights->manufacturingproduction->all->read;
if (empty($conf->manufacturingproduction->enabled)) accessforbidden();
if (!$permissiontoread) accessforbidden();

// Create the report
if ($action == 'createSQL') {
    
    // Generate the SQL
    $sql = 'SELECT j.label AS description, j.date, j.origin, j.qty, j.amount, j.type, p.ref, p.title, p.rowid AS projectid, c.rowid AS centercostid, c.master, c.detail, c.sub_detail, c.label';
    $sql .= ' FROM '.MAIN_DB_PREFIX.'manufacturingproduction_journal AS j';
    $sql .= ' INNER JOIN '.MAIN_DB_PREFIX.'manufacturingproduction_costcenter AS c ON j.fk_costcenter=c.rowid';
    $sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'projet AS p ON j.fk_project=p.rowid';
    if ($object->ismultientitymanaged == 1) {
        $sql .= " WHERE j.entity IN (".getEntity($object->element).")";
    } else {
        $sql .= " WHERE 1 = 1";
    }    
    if ($dtstart)
        $sql .= " AND j.date >= '".$db->idate($dtstart)."'";
    if ($dtend)
        $sql .= " AND j.date <= '".$db->idate($dtend)."'";
    if ($costcenterFrom != -1) 
        $sql .= " AND fk_costcenter >=".$costcenterFrom;
    if ($costcenterTo  != -1) 
        $sql .= " AND fk_costcenter <=".$costcenterTo;
    if ($projectFrom > 0)
        $sql .= " AND fk_project >=".$projectFrom;
    if ($projectTo > 0) 
        $sql .= " AND fk_project <=".$projectTo;
    
    if ($type=='costcenter') {
        if ($detail!='syntetic')
            $sql .= " ORDER BY c.master, c.detail, c.sub_detail, p.ref, j.date";
        else
            $sql .= " ORDER BY c.master, c.detail, c.sub_detail, j.date";
    } else {
        if ($totalizePeriod=='none')
            $sql .= " ORDER BY p.ref, c.master, c.detail, c.sub_detail, j.date";
        else
            $sql .= " ORDER BY p.ref, j.date, c.master, c.detail, c.sub_detail";
    }
    $resql = $db->query($sql);
    if (!$resql) {
        dol_print_error($db);
    }    
}

if ($model=='excel2007' || $model=='csv') {
    include "lib/createspreadsheet.php";
}

/*
 * View
 *
 */

$form = new Form($db);
if (!empty($conf->project->enabled)) {
    $formproject = new FormProjets($db);
}


$title = $langs->trans("AnalyticalAccounting");
if ($model=='graph')
    $morejs=array("/manufacturingproduction/js/chart.min.js", "/manufacturingproduction/js/financial_chart.js","/manufacturingproduction/js/jspdf.min.js");
else
    $morejs='';
llxHeader('', $title, '', '','','',$morejs,'',0,0);

// Filter form

print load_fiche_titre($langs->trans("AnalyticalAccounting", $langs->transnoentitiesnoconv("AnalyticalAccounting")), '', 'object_'.$object->picto);

print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="createSQL">';

print dol_get_fiche_head(array(), '');
print '<table class="border centpercent tableforfieldcreate">'."\n";

// Period
print '<tr>';
print '<td class="bold">'.$langs->trans('Period').'</td>';
print '<td>'.$form->selectDate($dtstart, "dtstart", 0, 0, 1, '', 1, 1, 0, '', '', '', '', 1, '', $langs->trans('From')).'</td>';
print '<td>'.$form->selectDate($dtend, "dtend", 0, 0, 1, '', 1, 1, 0, '', '', '', '', 1, '', $langs->trans('To')).'</td>';
print '</tr>';

// Center Cost
print '<tr>';
print '<td class="bold">'.$langs->trans('CostCenter').'</td>';
print '<td>'.select_costcenter($costcenterFrom, 'costcenterFrom', 1).'</td>';
print '<td>'.select_costcenter($costcenterTo, 'costcenterTo', 1).'</td>';
print '</tr>';

print '<tr>';
print '<td class="bold">'.$langs->trans('Project').'</td>';
print '<td>'.$formproject->select_projects(-1, $projectFrom,'projectFrom', 0, 0, 1, 0, 1, 0, 0, '', 1).'</td>';
print '<td>'.$formproject->select_projects(-1, $projectTo,'projectTo', 0, 0, 1, 0, 1, 0, 0, '', 1).'</td>';
print '</tr>';

print '<tr></tr>';

print '<tr style="line-height:25px">';

// Type
print '<td style="vertical-align:top;">';
print '<div class="tagtr tdtop bold">'.$langs->trans('Type').':</div>';
print '<div class="tagtr">';
print '<input type="radio" class="flat" id="costcenter" name="type" value="costcenter"'.($type=='costcenter'? ' checked="checked' : '').'>'; 
print '<label for="costcenter">'.$langs->trans('CostCenter').'</label><br>';
print '</div>';

print '<div class="tagtr">';
print '<input type="radio" class="flat" id="project" name="type" value="project"'.($type=='project'? ' checked="checked' : '').'>'; 
print '<label for="analytica">'.$langs->trans('Project').'</label>';
print '</div>';
print '</td>';

// Detail
print '<td style="vertical-align:top;">';
print '<div class="tagtr tdtop bold">'.$langs->trans('ReportDetail').':</div>';
print '<div class="tagtr">';
print '<input type="radio" class="flat" id="syntetic" name="detail" value="syntetic"'.($detail=='syntetic'? ' checked="checked' : '').'>'; 
print '<label for="syntetic">'.$langs->trans('Syntetic').'</label><br>';
print '</div>';

print '<div class="tagtr">';
print '<input type="radio" class="flat" id="analytical" name="detail" value="analytical"'.($detail=='analytical'? ' checked="checked' : '').'>'; 
print '<label for="analytical">'.$langs->trans('Analytical').'</label>';
print '</div>';

print '<div class="tagtr">';
print '<input type="radio" class="flat" id="journaldetail" name="detail" value="journaldetail"'.($detail=='journaldetail'? ' checked="checked' : '').'>'; 
print '<label for="journaldetail">'.$langs->trans('JournalDetail').'</label>';
print '</div>';

print '<div class="tagtr">';
print '<input type="radio" class="flat" id="flat" name="detail" value="flat"'.($detail=='flat'? ' checked="checked' : '').'>'; 
print '<label for="journaldetail">'.$langs->trans('JournalFlat').'</label>';
print '</div>';
print '</td>';


// Totalization
print '<td style="vertical-align:top;">';
print '<div class="tagtr tdtop bold">'.$langs->trans('DateTotalization').':</div>';
print '<div class="tagtr">';
print '<input type="radio" class="flat" id="none" name="total" value="none"'.($totalizePeriod=='none'? ' checked="checked' : '').'>'; 
print '<label for="none">'.$langs->trans('None').'</label><br>';
print '</div>';

print '<div class="tagtr">';
print '<input type="radio" class="flat" id="weekly" name="total" value="weekly"'.($totalizePeriod=='weekly'? ' checked="checked' : '').'>'; 
print '<label for="analytica">'.$langs->trans('Weekly').'</label>';
print '</div>';

print '<div class="tagtr">';
print '<input type="radio" class="flat" id="monthly" name="total" value="monthly"'.($totalizePeriod=='monthly'? ' checked="checked' : '').'>'; 
print '<label for="analytica">'.$langs->trans('Monthly').'</label>';
print '</div>';

print '<div class="tagtr">';
print '<input type="radio" class="flat" id="quarterly" name="total" value="quarterly"'.($totalizePeriod=='quarterly'? ' checked="checked' : '').'>'; 
print '<label for="analytica">'.$langs->trans('Quarterly').'</label>';
print '</div>';

print '<div class="tagtr">';
print '<input type="radio" class="flat" id="annual" name="total" value="annual"'.($totalizePeriod=='annual'? ' checked="checked' : '').'>'; 
print '<label for="analytica">'.$langs->trans('Annual').'</label>';
print '</div>';

print '</td>';

// Level
print '<td style="vertical-align:top;">';
print '<div class="tagtr tdtop bold">'.$langs->trans('Level').':</div>';
print '<div class="tagtr">';
print '<input type="radio" class="flat" id="none" name="level" value="none"'.($level=='none'? ' checked="checked' : '').'>'; 
print '<label for="none">'.$langs->trans('None').'</label><br>';
print '</div>';

print '<div class="tagtr">';
print '<input type="radio" class="flat" id="master" name="level" value="master"'.($level=='master'? ' checked="checked' : '').'>'; 
print '<label for="master">'.$langs->trans('Master').'</label><br>';
print '</div>';

print '<div class="tagtr">';
print '<input type="radio" class="flat" id="detail" name="level" value="detail"'.($level=='detail'? ' checked="checked' : '').'>'; 
print '<label for="detail">'.$langs->trans('Detail').'</label><br>';
print '</div>';

/*print '<div class="tagtr">';
print '<input type="radio" class="flat" id="subdetail" name="level" value="subdetail"'.($level=='subdetail'? ' checked="checked' : '').'>'; 
print '<label for="subdetail">'.$langs->trans('Sub_detail').'</label><br>';
print '</div>';*/

print '</td>';





print '</tr>'."\n";
    

print '</td>';
print '</tr>';

print '</table>'."\n";

print '<div class="tabsAction">';
print '<div class="inline-block divButAction"><input type="submit" class="button button-save" name="btnExcel" value="'.$langs->trans('CreateReportExcel').'"></div>';
print '<div class="inline-block divButAction"><input type="submit" class="button button-save" name="btncsv" value="'.$langs->trans('CreateReportCsv').'"></div>';
print '<div class="inline-block divButAction"><input type="submit" class="button button-save" name="btngraph" value="'.$langs->trans('Graph').'"></div>';
print '</div>';

// Graph zone

print dol_get_fiche_end();

print '</form>';

if ($model=='graph') {
    include "lib/createchart.inc.php";
}


// End of page
llxFooter();
$db->close();
