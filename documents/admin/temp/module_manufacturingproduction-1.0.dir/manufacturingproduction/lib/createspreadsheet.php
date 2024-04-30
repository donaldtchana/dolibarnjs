<?php

//require_once DOL_DOCUMENT_ROOT.'/core/modules/export/modules_export.php';
require_once DOL_DOCUMENT_ROOT.'/includes/phpoffice/phpspreadsheet/src/autoloader.php';
require_once DOL_DOCUMENT_ROOT.'/includes/Psr/autoloader.php';
//require_once PHPEXCELNEW_PATH.'Spreadsheet.php';


/*ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING);
*/

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;  
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

//$filename = 'tmp_'.$filename;

function getNexperiod($date, $criteria) {
    switch ($criteria) {
        case 'weekly': 
            $date = strtotime("next sunday midnight", strtotime($date));
            $out = strtotime("+1 day", $date);
            break;
        case 'monthly': 
            $date = date("Y-m-t", strtotime($date));
            $out = strtotime("+1 day", strtotime($date));
            break;
        case 'quarterly': 
            $current_quarter = ceil(date("m", strtotime($date)) / 3);
            $date = date('Y-m-t', strtotime(date('Y', strtotime($date)) . '-' . (($current_quarter * 3)) . '-1'));
            $out = strtotime("+1 day", strtotime($date));            
            break;
        case 'annual': 
            $date = strtotime("last day of december", strtotime($date));
            $out = strtotime("+1 day", $date);            
            break;
    }
    return $out;
}
function getPeriodDesc($date, $criteria) {
    global $langs;
    switch ($criteria) {
        case 'weekly': 
            $out = strtotime("next sunday midnight", strtotime($date));
            if (date('w', strtotime($date)) != 1)
                $date = strtotime("last monday midnight", strtotime($date));
            $out= dol_print_date($date, 'day') .'-'.dol_print_date($out, 'day');
            break;
        case 'monthly': 
            $out = date("m", strtotime($date));
            $out = date('F', mktime(0, 0, 0, $out, 10));
            $out = $langs->trans($out).' '. date("Y", strtotime($date));
            break;
        case 'quarterly': 
            $current_quarter = ceil(date("m", strtotime($date)) / 3);
            $out = $current_quarter.'°'.$langs->trans('Quarter').' '.date("Y", strtotime($date));
            break;
        case 'annual': 
            $out = date("Y", strtotime($date));            
            break;
    }
    return $out;
}

if ($type == 'costcenter') {
    $colproject = 'A';
    $colcod = 'B';
    $colcostcenter= 'C';
} else {
    $colcod = 'A';
    $colcostcenter= 'B';
    $colproject = 'C';
}

$spreadsheet = new Spreadsheet();
$worksheet = $spreadsheet->getActiveSheet();

// Titles with filters and col dimension
$worksheet->setCellValue('A1', html_entity_decode($langs->trans("AnalyticalAccounting")).' '.$langs->trans("Period").': '.dol_print_date($dtstart, 'day').' - '.dol_print_date($dtend, 'day'));
$worksheet->setCellValue('F1', $langs->trans('Date').' '.dol_print_date(dol_now(), 'dayhour'));
$worksheet->setCellValue('A2', printProjectFilter($projectFrom, $projectTo).' - '. 
                               printCostCenterFilter($costcenterFrom, $costcenterTo).' - '. 
                               $langs->trans('DateTotalization').': '.$langs->trans(ucfirst($totalizePeriod)));
$worksheet->getStyle('A1:L2')->getFont()->setSize(11)->setBold(true)->getColor()->setRGB('e84357');

$worksheet->setCellValue($colcod.'4', html_entity_decode($langs->trans('Code')));
$worksheet->setCellValue($colcostcenter.'4', html_entity_decode($langs->trans('CostCenter')));
$worksheet->setCellValue($colproject.'4', html_entity_decode($langs->trans('Project')));
$worksheet->setCellValue('D4', html_entity_decode($langs->trans('Date')));
$worksheet->setCellValue('E4', html_entity_decode($langs->trans('Origin')));
$worksheet->setCellValue('F4', html_entity_decode($langs->trans('Qty')));
$worksheet->setCellValue('G4', html_entity_decode($langs->trans('Cost')));
$worksheet->setCellValue('H4', html_entity_decode($langs->trans('Revenue')));
if ($detail=='journaldetail') {
    $worksheet->setCellValue('I4', html_entity_decode($langs->trans('Label')));
    $lastcol = 'I';
} else {
    $lastcol = 'H';
}    

$worksheet->getStyle('B4:H4')->getAlignment()->setHorizontal('center');

$worksheet->getStyle('A4:'.$lastcol.'4')->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);
$worksheet->getStyle('A4:'.$lastcol.'4')->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);
$worksheet->getStyle('A4:'.$lastcol.'4')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('1bbcc4');
$worksheet->getStyle('A4:'.$lastcol.'4')->getFont()->setSize(11)->setBold(true)->getColor()->setRGB('ffffff');

$worksheet->getColumnDimension($colcod)->setWidth('12');
$worksheet->getColumnDimension($colcostcenter)->setWidth('35');
$worksheet->getColumnDimension($colproject)->setWidth('35');
$worksheet->getColumnDimension('D')->setWidth('14');
if ($detail=='journaldetail') {
    $worksheet->getColumnDimension('I')->setWidth('80');
    $worksheet->getStyle('I4:I4')->getAlignment()->setHorizontal('center');
}

$nextPeriod = 0;
$periodDesc = '';
$totalLastRow = 5;
$printDetail = ($detail == 'flat' || $detail=='journaldetail');
$subTotals = array(0, 0);

// Create the totals array
$aTotals = array();
$aLevel = 0;
if ($detail != 'flat') {
    if ($totalizePeriod !='none') {
        $aTotals[$aLevel]['name'] = 'Period';
        $aTotals[$aLevel]['bgcolor'] = 'ffffb3';
        $aLevel++;
    }
    
    if ($detail=='analytical' || $detail=='journaldetail') {
        $aTotals[$aLevel]['name'] = 'Group1';
        $aTotals[$aLevel]['bgcolor'] = '9df5aa';
        $aLevel++;
    }

    /*if ($level=='subdetail')  {
        $aTotals[$aLevel]['name'] = 'Subdetail';
        $aTotals[$aLevel]['bgcolor'] = 'ffffcc';
        $aLevel++;
        $aTotals[$aLevel]['name'] = 'Detail';
        $aTotals[$aLevel]['bgcolor'] = 'ffff80';
        $aLevel++;
        $aTotals[$aLevel]['name'] = 'Master';
        $aTotals[$aLevel]['bgcolor'] = 'e6e600';
        $aLevel++;
    } else*/
    if ($level=='detail')  {
        $aTotals[$aLevel]['name'] = 'Detail';
        $aTotals[$aLevel]['bgcolor'] = 'ffff80';
        $aLevel++;
        $aTotals[$aLevel]['name'] = 'Master';
        $aTotals[$aLevel]['bgcolor'] = 'e6e600';
        $aLevel++;
    } elseif ($level=='master')  {
        $aTotals[$aLevel]['name'] = 'Master';
        $aTotals[$aLevel]['bgcolor'] = 'e6e600';
        $aLevel++;
    }
    
    $aTotals[$aLevel]['name'] = 'Group2';
    $aTotals[$aLevel]['bgcolor'] = 'eddaf2';
    $aLevel++;       
}
$aTotals[$aLevel]['name'] = 'Global';
$aTotals[$aLevel]['bgcolor'] = 'ffffff';

for ($i = 0; $i < count($aTotals); $i++) {
    if ($i==0)
        $aTotals[$i]['LastRow'] = 5;
    else
        $aTotals[$i]['RowsToTotal'] = '';
    $aTotals[$i]['LastKey'] = '|';
}


$num = $db->num_rows($resql);
$i = 0;
$row = $i+5;
if ($num) {
    while ($i < $num) {
        $obj = $db->fetch_object($resql);

        if ($printPeriodTot && $nextPeriod == 0) {
            $nextPeriod = getNexperiod($obj->date, $totalizePeriod);
            if ($i==0)
                $periodDesc = getPeriodDesc($obj->date, $totalizePeriod);
        }
        
        if ($detail != 'flat') {
            include 'totals.inc.php';
        }
        
        if ($printDetail)  { // ($detail=='journaldetail') {
            // Journal row
            $worksheet->setCellValue($colcod.$row, trim($obj->master.'.'.$obj->detail.'.'.$obj->sub_detail, '.'));
            $worksheet->setCellValue($colcostcenter.$row, html_entity_decode($obj->label));
            if ($obj->projectid)
                $worksheet->setCellValue($colproject.$row, html_entity_decode($obj->ref.'-'.$obj->title));
            else 
                $worksheet->setCellValue($colproject.$row, '***');
            $worksheet->setCellValue('D'.$row, dol_print_date($obj->date, 'day'));
            $worksheet->setCellValue('E'.$row, $langs->trans(printOriginDesc($obj->origin)));
            $worksheet->setCellValue('F'.$row, $obj->qty);
            if ($obj->type == 1) {
                $worksheet->setCellValue('G'.$row, $obj->qty*$obj->amount);
            } else {
                $worksheet->setCellValue('H'.$row, $obj->qty*$obj->amount);
            }
            $worksheet->setCellValue('I'.$row, html_entity_decode($obj->description));
            $row++;
        } else {
            if ($obj->type == 1) {
                $subTotals[0] += $obj->qty*$obj->amount;
            } else {
                $subTotals[1] += $obj->qty*$obj->amount;
            }
        }
        /*$perodTotalToPrint = True;*/
       $i++;
    }
}


$forceTotal = true;
include 'totals.inc.php';

// General totals
$totalRow = (string)$row;
$worksheet->getStyle('E'.($row-1).':H'.($row-1))->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);
$worksheet->getStyle('G4:H'.$totalRow)->getNumberFormat()->setFormatCode('#,##0.00');    
$worksheet->getStyle('D'.$totalRow.':H'.$totalRow)->getFont()->setBold(true);

// Redim columns
foreach (array('E', 'G','H') as $col) {
   $worksheet->getColumnDimension($col)->setAutoSize(true);
}

// Save the spreadsheet
$dirname= dol_buildpath('/manufacturingproduction').'/export/'.$user->id;
dol_mkdir($dirname);
$filename=html_entity_decode($langs->trans("AnalyticalAccounting"))." ".date('Y_m_d').($model == 'csv'? '.csv': '.xlsx');
if ($model == 'csv')
    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Csv($spreadsheet);
else 
    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

$writer->save($dirname."/".$filename);


// Upload the file
$f = fopen($dirname."/".$filename, "r");
$fr = fread($f, filesize($dirname."/".$filename));
fclose($f);
unlink($dirname."/".$filename);

$type = ($model== 'csv')?'csv':'vnd.ms-excel';
header('Content-Type: application/'.$type.';charset=UTF-8"');
header('Content-Disposition: attachment; filename="'.$filename.'";');
print $fr;
exit;


