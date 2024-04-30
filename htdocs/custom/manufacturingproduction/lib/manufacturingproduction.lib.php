<?php
/* Copyright (C) 2022 SuperAdmin <marcello.gribaudo@opigi.com>
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
 * \file    manufacturingproduction/lib/manufacturingproduction.lib.php
 * \ingroup manufacturingproduction
 * \brief   Library files with common functions for ManufacturingProduction
 */

/**
 * Prepare admin pages header
 *
 * @return array
 */
function manufacturingproductionAdminPrepareHead() {
    global $langs, $conf;

    $langs->load("manufacturingproduction@manufacturingproduction");

    $h = 0;
    $head = array();

    $head[$h][0] = dol_buildpath("/manufacturingproduction/admin/setup.php", 1);
    $head[$h][1] = $langs->trans("Settings");
    $head[$h][2] = 'settings';
    $h++;

    /*
    $head[$h][0] = dol_buildpath("/manufacturingproduction/admin/myobject_extrafields.php", 1);
    $head[$h][1] = $langs->trans("ExtraFields");
    $head[$h][2] = 'myobject_extrafields';
    $h++;
    */

    $head[$h][0] = dol_buildpath("/manufacturingproduction/admin/about.php", 1);
    $head[$h][1] = $langs->trans("About");
    $head[$h][2] = 'about';
    $h++;

    // Show more tabs from modules
    // Entries must be declared in modules descriptor with line
    //$this->tabs = array(
    //	'entity:+tabname:Title:@manufacturingproduction:/manufacturingproduction/mypage.php?id=__ID__'
    //); // to add new tab
    //$this->tabs = array(
    //	'entity:-tabname:Title:@manufacturingproduction:/manufacturingproduction/mypage.php?id=__ID__'
    //); // to remove a tab
    complete_head_from_modules($conf, $langs, null, $head, $h, 'manufacturingproduction@manufacturingproduction');

    complete_head_from_modules($conf, $langs, null, $head, $h, 'manufacturingproduction@manufacturingproduction', 'remove');

    return $head;
}


function select_costcenter($selected = '', $htmlname = 'costcenterid', $showempty = '', $multiple = '', $onlymoveable = false)  {
    global $db;

    $out = '';
    $sql = "SELECT s.rowid, s.label FROM ".MAIN_DB_PREFIX."manufacturingproduction_costcenter AS s";
    $sql .= " WHERE s.entity IN (".getEntity('societe').")";
    if ($onlymoveable)
        $sql .= " AND moveable";
    $sql .= $db->order("master, detail, sub_detail", "ASC");

    $resql = $db->query($sql);
    if ($resql) {
        $out .= '<select id="'.$htmlname.'" name="'.$htmlname.($multiple ? '[]' : '').'" '.($multiple ? 'multiple' : '').'>'."\n";
        if ($showempty) {
            $out .= '<option value="-1">&nbsp;</option>'."\n";
        }

        $num = $db->num_rows($resql);
        $i = 0;
        if ($num) {
            while ($i < $num) {
                $obj = $db->fetch_object($resql);
                if ($obj->rowid == $selected) {
                    $out .= '<option value="'.$obj->rowid.'" selected>'.$obj->label.'</option>';
                } else {
                    $out .= '<option value="'.$obj->rowid.'" >'.$obj->label.'</option>';
                }
                $i++;
            }
        }
        $out .= '</select>'."\n";
    } else {
        dol_print_error($db);
    }

    return $out;
}

function save_costcenter($id, $tableName, $fk_costcenter, $qty)  {
    global $db;

    $sql = 'UPDATE '.MAIN_DB_PREFIX.$tableName. ' SET';
    $sql.= ' fk_costcenter='.$fk_costcenter.',';
    $sql.= ' qty='. (int) $qty;
    $sql.= ' WHERE rowid ='. (int) $id;
    $resql = $db->query($sql);

    if (!$resql) {
        setEventMessage($db->lasterror(), 'errors');
    }

}

function add_costcenter($bomid, $tableName, $fk_costcenter, $qty)  {
    global $db;

    $sql = 'INSERT INTO  '.MAIN_DB_PREFIX.$tableName. ' (fk_bom, fk_costcenter, qty) VALUES(';
    $sql.= $bomid.',';
    $sql.= $fk_costcenter.',';
    $sql.= $qty.')';
    $resql = $db->query($sql);

    if (!$resql) {
        setEventMessage($db->lasterror(), 'errors');
    }

}

function delete_costcenter($id, $tableName)  {
    global $db;

    $sql = 'DELETE FROM '.MAIN_DB_PREFIX.$tableName;
    $sql.= ' WHERE rowid ='. (int) $id;
    $resql = $db->query($sql);

    if (!$resql) {
        setEventMessage($db->lasterror(), 'errors');
    }

}

function printCostCenteLines($object, $id, $action, $tableName, $permissiontoedit, $permissiontodelete) {
    
    global $langs, $db;
    
    // Title line
    print "<thead>\n";

    print '<tr class="liste_titre nodrag nodrop">';
    // Description
    print '<td class="linecoldescription">'.$langs->trans('CostCenter');
    print '</td>';

    // Qty
    print '<td class="linecolqty right">'.$langs->trans('Qty').'</td>';

    print '<td class="linecoledit"></td>'; // No width to allow autodim

    print '<td class="linecoldelete" style="width: 10px"></td>';

    print "</tr>\n";
    print "</thead>\n";

    $sql = 'SELECT rowid, fk_costcenter, qty FROM '.MAIN_DB_PREFIX.$tableName.' AS c';
    $sql.= ' WHERE fk_bom ='. (int) $id;
    $resql = $db->query($sql);

    if ($resql) {
        
        $selected = GETPOST('lineid', 'int');
        $num = $resql->num_rows;

        // Loop on all the sub-BOM lines if they exist

        while ($obj = $db->fetch_object($resql)) {
            $tmpcostcenter = new CostCenter($db);
            $tmpcostcenter->fetch($obj->fk_costcenter);

            if ($object->status == 0 && $action == 'editline' && $selected == $obj->rowid) {
                // Edit a line
                print '<tr class="oddeven tredited">';
                
                print '<td>';
                print '<div id="line_'.$obj->rowid.'"></div>';
                print '<input type="hidden" name="lineid" value="'.$obj->rowid.'">';
                //print $tmpcostcenter->getNomUrl(1);
                print select_costcenter($obj->fk_costcenter,'costcenter', '', '', true);
                
                print '</td>';
                
                print '<td class="nobottom linecolqty right">';
                print '<input size="3" type="text" class="flat right" name="qty" id="qty" value="'.$obj->qty.'">';
                print '</td>';
                
                print '<td class="nobottom linecoledit center valignmiddle" colspan="2">';
                print '<input type="submit" class="button buttongen margintoponly marginbottomonly button-save" id="savelinebutton" name="save" value="'.$langs->trans("Save").'">';
                print '<input type="submit" class="button buttongen margintoponly marginbottomonly button-cancel" id="cancellinebutton" name="cancel" value="'.$langs->trans("Cancel").'">';
                print '</td>';
                
                print '</tr>';
                
            } else {
                // View a line
                print '<tr id="row-'.$obj->rowid.'" class="drag drop oddeven">';

                print '<td class="linecoldescription minwidth300imp">';
                print '<div id="line_'.$line->id.'"></div>';
                print $tmpcostcenter->getNomUrl(1);
                print ' - '.$tmpcostcenter->label;
                print '</td>';


                print '<td class="linecolqty nowrap right">';
                echo price($obj->qty, 0, '', 0, 0); // Yes, it is a quantity, not a price, but we just want the formating role of function price
                print '</td>';

                if ($object->status == 0 && $action != 'selectlines') {
                    print '<td class="linecoledit center">';
                    if ($permissiontoedit) {
                        print '<a class="editfielda reposition" href="'.$_SERVER["PHP_SELF"].'?id='.$id.'&action=editline&token='.newToken().'&lineid='.$obj->rowid.'">'.img_edit().'</a>';
                    }
                    print '</td>';

                    print '<td class="linecoldelete center">';
                    if ($permissiontodelete) {
                        print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?id='.$id.'&action=deleteline&token='.newToken().'&lineid='.$obj->rowid.'">';
                        print img_delete();
                        print '</a>';
                    }
                    print '</td>';
                } else {
                    print '<td colspan="3"></td>';
                }


                print '</tr>';
            }
        }

    }

}

function CreateCostCenterLine() {
    
    global $langs;
    
    print '<tr class="pair nodrag nodrop nohoverpair liste_titre_create">';
    
    print '<td class="bordertop nobottom linecoldescription minwidth500imp">';
    print $langs->trans("CostCenter").'&nbsp;';
    print select_costcenter(-1,'costcenter', True, '', true);
    print '</td>';
    
    print '<td class="bordertop nobottom linecolqty right"><input type="text" size="2" name="qty" id="qty" class="flat right" value="'.(GETPOSTISSET("qty") ? GETPOST("qty", 'alpha', 2) : 1).'">';
    print '</td>';
    
    print '<td class="bordertop nobottom linecoledit center valignmiddle" colspan="2">';
    print '<input type="submit" class="button button-add" name="addline" id="addline" value="'.$langs->trans('Add').'">';
    print '</td>';
    print '</tr>';
}

function GetOrderMaterialCostCenter($order_id) {
    global $db;

    $sql = 'SELECT e.materialcostcenter FROM '.MAIN_DB_PREFIX.'mrp_mo AS m';
    $sql .= ' INNER JOIN '.MAIN_DB_PREFIX.'bom_bom AS b ON b.rowid=m.fk_bom';
    $sql .= ' INNER JOIN '.MAIN_DB_PREFIX.'bom_bom_extrafields AS e ON e.fk_object=b.rowid';
    $sql.= ' WHERE m.rowid='. (int) $order_id;
    $resql = $db->query($sql);

    if ($resql) {
       $obj = $db->fetch_object($resql);
       return $obj->materialcostcenter;
    } else {
        return -1;
    }
    
}

function ConvertFields($db, &$objfields, $langs) {
    
    // Cost center label
    if ($objfields->fk_costcenter) {
        $sql  = 'SELECT label FROM '.MAIN_DB_PREFIX.'manufacturingproduction_costcenter';
        $sql .= ' WHERE rowid='.$objfields->fk_costcenter;
        $result = $db->query($sql);
        $obj = $db->fetch_object($result);
        $objfields->fk_costcenter = $obj->label;
    }
    
    // Project title
    if ($objfields->fk_project) {
        $sql  = 'SELECT title FROM '.MAIN_DB_PREFIX.'projet';
        $sql .= ' WHERE rowid='.$objfields->fk_project;
        $result = $db->query($sql);
        $obj = $db->fetch_object($result);
        $objfields->fk_project = $obj->title;
    }
    
    // Linkecd object
    if ($objfields->origin && $objfields->fk_linked_id) {
        switch ($objfields->origin) {
            case 2:
                $tablename = 'mrp_mo';
                $labelname = 'CONCAT(ref, " ", IFNULL(label, ""))'; 

        }
        $sql  = 'SELECT '.$labelname.' as title FROM '.MAIN_DB_PREFIX.$tablename;
        $sql .= ' WHERE rowid='.$objfields->fk_linked_id;
        $result = $db->query($sql);
        $obj = $db->fetch_object($result);
        $objfields->origin = $obj->title;
        
    }


    // Cost or revenue
    if ($objfields->type == 1)
        $objfields->type = $langs->trans('Cost');
    else
        $objfields->type = $langs->trans('Revenue');
}

    
function UpdateJournal($costcenter, $date, $project, $origin, $linked_id, $qty, $amount) {
     global $db, $conf;

    $date = date('Y-m-d',$date);
    
    $sql = 'SELECT rowid, qty FROM '.MAIN_DB_PREFIX.'manufacturingproduction_journal';
    $sql.= ' WHERE fk_costcenter="'. $costcenter . '" AND date="'.$date.'" AND origin='.$origin.' AND fk_project='.$project;
    if ($linked_id)
        $sql.= ' AND fk_linked_id='.$linked_id;
    
    $resql = $db->query($sql);
    if ($resql) {
        $num = $db->num_rows($resql);
        if ($num > 1) {
            return -$num;
        } elseif ($num == 1) {
            $obj = $db->fetch_object($resql);
            if ($obj->qty == -$qty) {
                // The qty to decrase is equal to the actual qty, delete the row
                $sql = 'DELETE FROM '.MAIN_DB_PREFIX.'manufacturingproduction_journal';
                $sql.= ' WHERE fk_costcenter="'. $costcenter . '" AND date="'.$date.'" AND origin='.$origin.' AND fk_project='.$project;
                if ($linked_id)
                    $sql.= ' AND fk_linked_id='.$linked_id;
            } else {
                // Update the journal row
                $sql = 'UPDATE '.MAIN_DB_PREFIX.'manufacturingproduction_journal SET';
                $sql .= ' qty=qty+'.$qty.',';
                $sql .= ' amount=amount+'.$amount;
                $sql.= ' WHERE fk_costcenter="'. $costcenter . '" AND date="'.$date.'" AND origin='.$origin.' AND fk_project='.$project;
                if ($linked_id)
                    $sql.= ' AND fk_linked_id='.$linked_id;
            }
            $resql = $db->query($sql);
    
        } else {
            $sql = 'INSERT INTO '.MAIN_DB_PREFIX.'manufacturingproduction_journal (entity, label, fk_costcenter, fk_project, type, origin, fk_linked_id, date, qty, amount) VALUES(';
            $sql .= '1,';
            $sql .= '"'.$conf->global->MANUFACTURINGPRODUCTION_USER_DEFAULT_DESC.'",';
            $sql .= $costcenter.',';            
            $sql .= $project.',';
            $sql .= '1,';
            $sql .= $origin.',';
            $sql .= ($linked_id ? $linked_id : 'null').',';
            $sql .= '"'.$date.'",';            
            $sql .= $qty.',';
            $sql .= $amount;
            $sql.= ')';
            $resql = $db->query($sql);
        }
        if ($resql) {
            return 1;
        } else {
            return -1;
        }
    } else {
       return -1;
    }
   
}

function CostCenterDesc($id, $includeCode=false) {
    global $db;
    $sql = "SELECT label, CONCAT(master, '.', detail, '.', sub_detail) as code FROM ".MAIN_DB_PREFIX."manufacturingproduction_costcenter";
    $sql .= " WHERE rowid=".$id;
    $resql = $db->query($sql);
    $obj = $db->fetch_object($resql);  
    if ($includeCode)
        return array($obj->code, $obj->label);
    else
        return $obj->label;
}

function CostCenterDescByCode($id) {
    global $db;
    $sql = "SELECT label FROM ".MAIN_DB_PREFIX."manufacturingproduction_costcenter";
    $sql .= " WHERE CONCAT(master,IFNULL(detail,''),IFNULL(sub_detail,''))=".$id;
    $resql = $db->query($sql);
    $obj = $db->fetch_object($resql);  
    return $obj->label;
}


function ProjectDesc($id) {
    global $db;
    if ($id) {
        $sql = "SELECT title FROM ".MAIN_DB_PREFIX."projet";
        $sql .= " WHERE rowid=".$id;
        $resql = $db->query($sql);
        $obj = $db->fetch_object($resql);  
        return $obj->title;
    } else {
        return '***';
    }
}

function printCostCenterFilter($from, $to) {
  global $db, $langs;  
  $out = $langs->trans("CostCenter").': ';
  if ($from == -1 && $to == -1) 
       $out .= $langs->trans('Alls');
  else {
      $out .= $langs->trans("From").' "'.CostCenterDesc($from).'" '.$langs->trans('to').' "'.CostCenterDesc($to).'"';
  }  
  return $out;
}

function printProjectFilter($from, $to) {
  global $langs;  
  $out = $langs->trans("Project").': ';
  if (!$from && !$to) 
       $out .= $langs->trans('Alls');
  else {
      $out .= $langs->trans("From").' "'.ProjectDesc($from).'" '.$langs->trans('to').' "'.ProjectDesc($to).'"';
  }  
  return $out;
}

function printOriginDesc($type) {
    switch ($type) {
        case 1:
            $out = 'User';
            break;
        case 2:
            $out = 'Production';
            break;
        case 3:
            $out = 'Invoices';
            break;
        case 4:
            $out = 'SuppliersInvoices';
            break;
        default: 
           $out = '';
    }
    return $out;
}