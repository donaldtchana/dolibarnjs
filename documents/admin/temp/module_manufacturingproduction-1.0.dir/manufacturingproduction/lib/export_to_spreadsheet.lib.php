<?php
/* Copyright (C) 2019 Marcello Gribaudo <marcello.gribaudo@gmail.com>
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file    export_to_excel.lib.php
 * \ingroup 
 * \brief   Library files for export a class to excel
 */

function SheetFieldType($type) {
    switch ($type) {
        case 16:
        case 1:
        case 2:
        case 9:
        case 3:
        case 8:
        case 4:
        case 5:
        case 246:
            return 'Float';
            break;  
        case 10:
        case 12:
        case 7:
        case 11:
        case 13:
            return 'DateTime';
            break;  
        default;
            return 'Text';
            break;
    }
    
}
function getColName($num) {
    $numeric = $num % 26;
    $letter = chr(65 + $numeric);
    $num2 = intval($num / 26);
    if ($num2 > 0) {
        return getColName($num2 - 1) . $letter;
    } else {
        return $letter;
    }
}


/**
 * Create a spreadsheet from the sql and the fields of the list 
 *
 * @param  string   $doc_type   document type (excell2007 or csv)
 * @param  string   $doc_name   document name
 * @param  string   $doc_folder document folder for temporary files
 * @param  array    $fields     array with fields (if you want full functionallity, add the "type" and "ismeasure" element to the standard array
 * @param  string   $sql        sql string 
 * @return int             sphreadsheet
 */

function ExportToSpeadsheet($doc_type, $doc_name, $doc_folder, $fields, $sql) {
    global $db, $langs;

    $langs->loadLangs(array("exports"));

    $result = $db->query($sql);    
    $arows = array();
    $num = $db->num_rows($result);
    $i = 0;

    // Create array with fields type
    $aColumns = array();
    while ($column_info = $result->fetch_field()){
        $aColumns[$column_info->name] = $column_info->type;
    }    
    
    
    // Load data into $arows array
    while ($i < $num) {
        $obj = $db->fetch_object($result);
        if (function_exists('ConvertFields'))
            ConvertFields($db, $obj, $langs);
        
        $line = array();
        foreach($fields as $key => $val) {
            
            $fieldname = trim(substr($key, strpos($key, '.') + 1));
            if (! empty($fields[$key]['checked'])) {
                if ($fields[$key]['type'] == 'date' || $aColumns[$fieldname] == 10) // Date
                    $line[$fieldname] =  dol_print_date($obj->$fieldname, 'day');
                elseif ($fields[$key]['type'] == 'datetime' || $fields[$key]['type'] == 'timestamp' || $aColumns[$fieldname] == 12 || $aColumns[$fieldname] == 7) //  datetime or timestamp
                    $line[$fieldname] =  dol_print_date($obj->$fieldname, 'dayhour');
                elseif ($fields[$key]['type'] == 'price')
                    $line[$fieldname] =  price($obj->$fieldname);
                else
                    $line[$fieldname] =  $obj->$fieldname;
            }
        }
        $arows[] = $line;
        
        $i++;
    }
    
    // Create array eith labels, position and type
    $i = 1;
    foreach($fields as $key => $val) {
        if (! empty($fields[$key]['checked'])) {
            $fieldname = trim(substr($key, strpos($key, '.') + 1));
            $alabels[$fieldname] = html_entity_decode($langs->trans($val['label']));
            $asorted[$fieldname] = $i;
            $atypes[$fieldname] = SheetFieldType($aColumns[$fieldname]);
            $i++;
        }
    }

    // Export to the spreadsheet using proper class
    $filename = DOL_DOCUMENT_ROOT . "/core/modules/export/export_".$doc_type.".modules.php";
    $classname = "Export".$doc_type;
    require_once $filename;
    $objsheet = new $classname($db);

    $filename = $doc_name.'.'.$objsheet->getDriverExtension();
    $dirname = DOL_DOCUMENT_ROOT.'/'.$doc_folder;

    $outputlangs=dol_clone($langs);

    dol_mkdir($dirname);
    $result=$objsheet->open_file($dirname."/".$filename, $outputlangs);
    if ($result >= 0) {
        // Header
        $objsheet->write_header($outputlangs);

        // Title
        $objsheet->write_title($alabels, $asorted, $outputlangs, $atypes);

        // Rows
        foreach ($arows as $line){
            if ($doc_type !== 'csv') {
                // For Excel formats 
                foreach ($fields as $key => $value) {
                    if (! empty($fields[$key]['checked'])) {
                        $fieldname = trim(substr($key, strpos($key, '.') + 1));
                        if (in_array($value['type'], array('price', 'integer')) || strpos($value['type'], 'double') !== false ) {
                            if ($langs->transnoentitiesnoconv("SeparatorDecimal") == ',')
                                $line[$fieldname] = floatval(str_replace(' ', '', str_replace(',', '.', str_replace('.', '', $line[$fieldname]))));
                            else 
                                $line[$fieldname] = floatval(str_replace(',', '', $line[$fieldname]));
                            //$line[$fieldname] = price($line[$fieldname]);
                        }
                    }
                }
            } else {
                foreach ($fields as $key => $value) {
                    if (! empty($fields[$key]['checked'])) {
                        $fieldname = trim(substr($key, strpos($key, '.') + 1));
                        if (in_array($value['type'], array('price', 'integer')) || strpos($value['type'], 'double') !== false ) {
                            $price = str_replace(' ', '', $line[$fieldname]);
                            $line[$fieldname] = $price;
                        }
                    }
                }
            }
            $objsheet->write_record($asorted, (object)$line, $outputlangs, $atypes);
        }
        
        // Totals
        $i= 0;
        foreach ($fields as $key => $value) {
            if (! empty($fields[$key]['checked'])) {
                $fieldname = trim(substr($key, strpos($key, '.') + 1));
                if ((! empty($fields[$key]['isameasure']))) {
                    $col = getColName($i);
                    $line[$fieldname] = '=SUM('.$col.'2:'.$col.(count($arows)+1).')';
                } else
                    $line[$fieldname] = '';
                $i++;
            }
        }
        $objsheet->write_record($asorted, (object)$line, $outputlangs, $atypes);
        

        // Footer
        $objsheet->write_footer($outputlangs);

        // Close file
        $objsheet->close_file();

        $f = fopen($dirname."/".$filename, "r");
        $fr = fread($f, filesize($dirname."/".$filename));
        fclose($f);
        unlink($dirname."/".$filename);

        $type = ($doc_type== 'csv')?'csv':'vnd.ms-excel';
        $extension = ($doc_type == 'csv')?'csv':(($doc_type == 'excel')?'xls':'xlsx');
        header('Content-Type: application/'.$type.';charset=UTF-8"');
        header('Content-Disposition: attachment; filename="'.$filename.'";');
        print $fr;
        exit;
    }
    
}
