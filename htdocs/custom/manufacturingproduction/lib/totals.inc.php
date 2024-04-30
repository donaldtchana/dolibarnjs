<?php

$count = count($aTotals)-($forceTotal ? 0 : 1);
$isBreak = false;
for ($t = $count; $t >= 0 ; $t--) {
    
    // Test if there is a total break
    if ($forceTotal)
        $isBreak = true;
    else {
        switch ($aTotals[$t]['name']) {
            case 'Period':
                $isBreak = $nextPeriod == 0 || ($nextPeriod <= strtotime($obj->date));
                break;
            case 'Group2':
                if ($aTotals[$t]['LastKey'] == '|') {
                    $isBreak = false;
                    $aTotals[$t]['LastKey'] = ($type == 'costcenter' ? $obj->centercostid : $obj->projectid);
                } elseif ($type == 'costcenter') {
                    $isBreak = $obj->centercostid != $aTotals[$t]['LastKey'];
                } else {
                    $isBreak = $obj->projectid != $aTotals[$t]['LastKey'];
                }
                break;
            case 'Group1':
                if ($aTotals[$t]['LastKey'] == '|') {
                    $isBreak = false;
                    $aTotals[$t]['LastKey'] = $type == 'costcenter' ? $obj->projectid : $obj->centercostid;
                } elseif ($type == 'costcenter') 
                    $isBreak = $obj->projectid != $aTotals[$t]['LastKey'];
                else
                    $isBreak = $obj->centercostid != $aTotals[$t]['LastKey'];
                break;
            case 'Master':
                if ($aTotals[$t]['LastKey'] == '|') {
                    $isBreak = false;
                    $aTotals[$t]['LastKey'] = $obj->master;
                } else {
                    $isBreak = $obj->master != $aTotals[$t]['LastKey'];
                }
                break;
            case 'Detail':
                if ($aTotals[$t]['LastKey'] == '|') {
                    $isBreak = false;
                    $aTotals[$t]['LastKey'] = $obj->master.$obj->detail;
                } else {
                    $isBreak = $obj->master.$obj->detail != $aTotals[$t]['LastKey'];
                }
                break;
        }
    }
    if ($isBreak)
        break;
}    
    
if ($isBreak) {
    for ($tt = 0; $tt <= $t; $tt++) {
    
        // Print the total description
        switch ($aTotals[$tt]['name']) {
            case 'Period':
                $worksheet->setCellValue('C'.$row, $langs->trans('Total').': '.$periodDesc);
                $nextPeriod = getNexperiod($obj->date, $totalizePeriod);
                $periodDesc = getPeriodDesc($obj->date, $totalizePeriod);
                break;
            case 'Group1':
                if ($type == 'costcenter') {
                    $worksheet->setCellValue($colproject.$row, ProjectDesc($aTotals[$tt]['LastKey']));  
                    $aTotals[$tt]['LastKey'] = $obj->projectid;
                } else {
                    $worksheet->setCellValue($colcostcenter.$row, CostCenterDesc($aTotals[$tt]['LastKey']));  
                    $aTotals[$tt]['LastKey'] = $obj->centercostid;
                }
                $nextPeriod = 0;
                break;
            case 'Group2':
                if ($type == 'project') {
                    $worksheet->setCellValue($colproject.$row, ProjectDesc($aTotals[$tt]['LastKey']));  
                    $aTotals[$tt]['LastKey'] = $obj->projectid;
                } else {
                    $worksheet->setCellValue($colcostcenter.$row, CostCenterDesc($aTotals[$tt]['LastKey']));  
                    $aTotals[$tt]['LastKey'] = $obj->centercostid;
                }
                $nextPeriod = 0;
                break;
            case 'Master':
                $worksheet->setCellValue('B'.$row, CostCenterDescByCode($aTotals[$tt]['LastKey']));  
                $aTotals[$tt]['LastKey'] = $obj->master;
                $nextPeriod = 0;
                break;
            case 'Detail':
                $worksheet->setCellValue('B'.$row, CostCenterDescByCode($aTotals[$tt]['LastKey']));  
                $aTotals[$tt]['LastKey'] = $obj->master.$obj->detail;
                $nextPeriod = 0;
                break;
            case 'Global':
                $worksheet->setCellValue('E'.$row, $langs->trans('Total'));
                //$worksheet->getStyle('A'.$row.':H'.$row)->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);
                break;

        }
        
        // Print the total values
        if ($tt==0) {
            if ($printDetail) {
                $worksheet->setCellValue('G'.$row, '=SUM(G'.$totalLastRow.':G'.($row-1).')');
                $worksheet->setCellValue('H'.$row, '=SUM(H'.$totalLastRow.':H'.($row-1).')');
            } else {
                $worksheet->setCellValue('G'.$row, $subTotals[0]);
                $worksheet->setCellValue('H'.$row, $subTotals[1]);
                $subTotals = array(0, 0);
            }
        } else {
            $CostToSum = ''; $RevenueToSum = '';
            $aTotals[$tt]['RowsToTotal'] = rtrim($aTotals[$tt]['RowsToTotal'], ',');
            $subPeriods = explode(',', $aTotals[$tt]['RowsToTotal']);
            foreach ($subPeriods as $subtotal) {
                $CostToSum .= 'G'.$subtotal.'+';
                $RevenueToSum .= 'H'.$subtotal.'+';
            }
            $CostToSum = '='.rtrim($CostToSum, '+');
            $RevenueToSum = '='.rtrim($RevenueToSum, '+');
            $worksheet->setCellValue('G'.$row, $CostToSum);
            $worksheet->setCellValue('H'.$row, $RevenueToSum);
            $aTotals[$tt]['RowsToTotal'] = '';
        }
        $totalLastRow = $row+1;

        // Set the total font and background color
        if ($tt!=0 || $detail=='journaldetail') {
            $worksheet->getStyle($colcod.$row.':H'.$row)->getFont()->setBold(true);
            $worksheet->getStyle('A'.$row.':H'.$row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB($aTotals[$tt]['bgcolor']);
         }
         
         // Totalize row in previous total level
        $aTotals[$tt+1]['RowsToTotal'] .= (string)$row.',';
        
        $row++;
    }
}
