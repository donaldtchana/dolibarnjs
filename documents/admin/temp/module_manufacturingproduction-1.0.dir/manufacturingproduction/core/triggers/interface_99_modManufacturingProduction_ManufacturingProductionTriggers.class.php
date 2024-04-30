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
 * \file    core/triggers/interface_99_modManufacturingProduction_ManufacturingProductionTriggers.class.php
 * \ingroup manufacturingproduction
 * \brief   Example trigger.
 *
 * Put detailed description here.
 *
 * \remarks You can create other triggers by copying this one.
 * - File name should be either:
 *      - interface_99_modManufacturingProduction_MyTrigger.class.php
 *      - interface_99_all_MyTrigger.class.php
 * - The file must stay in core/triggers
 * - The class name must be InterfaceMytrigger
 */

require_once DOL_DOCUMENT_ROOT.'/core/triggers/dolibarrtriggers.class.php';


/**
 *  Class of triggers for ManufacturingProduction module
 */
class InterfaceManufacturingProductionTriggers extends DolibarrTriggers {
    /**
     * Constructor
     *
     * @param DoliDB $db Database handler
     */
    public function __construct($db) {
        $this->db = $db;

        $this->name = preg_replace('/^Interface/i', '', get_class($this));
        $this->family = "demo";
        $this->description = "ManufacturingProduction triggers.";
        // 'development', 'experimental', 'dolibarr' or version
        $this->version = 'development';
        $this->picto = 'manufacturingproduction@manufacturingproduction';
    }

    /**
     * Trigger name
     *
     * @return string Name of trigger file
     */
    public function getName() {
        return $this->name;
    }

    /**
     * Trigger description
     *
     * @return string Description of trigger file
     */
    public function getDesc() {
        return $this->description;
    }


    /**
     * Function called when a Dolibarrr business event is done.
     * All functions "runTrigger" are triggered if file
     * is inside directory core/triggers
     *
     * @param string 		$action 	Event action code
     * @param CommonObject 	$object 	Object
     * @param User 			$user 		Object user
     * @param Translate 	$langs 		Object langs
     * @param Conf 			$conf 		Object conf
     * @return int              		<0 if KO, 0 if no triggered ran, >0 if OK
     */
    public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf) {
        if (empty($conf->manufacturingproduction) || empty($conf->manufacturingproduction->enabled)) {
            return 0; // If module is not enabled, we do nothing
        }

        // Put here code you want to execute when a Dolibarr business events occurs.
        // Data and type of action are stored into $object and $action

        // You can isolate code for each action in a separate method: this method should be named like the trigger in camelCase.
        // For example : COMPANY_CREATE => public function companyCreate($action, $object, User $user, Translate $langs, Conf $conf)
        $methodName = lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', strtolower($action)))));
        $callback = array($this, $methodName);
        if (is_callable($callback)) {
            dol_syslog(
                "Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id
            );

            return call_user_func($callback, $action, $object, $user, $langs, $conf);
        };

        // Or you can execute some code here
        switch ($action) {
            
            case 'MO_CREATE':
                // When creating a production order, generate the corrisponding centercost lines, exploding the bom

                require_once dol_buildpath('/manufacturingproduction/lib/manufacturingproduction.lib.php');                
                
                $label = $conf->global->MANUFACTURINGPRODUCTION_MO_DEFAULT_DESC;
                $label = str_replace('__MO_LABEL__', $object->label, $label);
                $label = str_replace('__MO_REF__', $object->ref, $label);
                if ($object->date_start_planned)
                    $label = str_replace('__MO_DATE__', dol_print_date($object->date_start_planned, 'day'), $label);
                else 
                    $label = str_replace('__MO_DATE__', '', $label);
                
                $idbom = $object->fk_bom;
                $id = $object->id;
                $project_id = $object->fk_project;
                $materiale_costcenter = GetOrderMaterialCostCenter($id);
                
                $sql  = 'SELECT b.fk_costcenter, b.qty, c.type, c.value FROM '.MAIN_DB_PREFIX.'manufacturingproduction_bom AS b';
                $sql .= ' INNER JOIN '.MAIN_DB_PREFIX.'manufacturingproduction_costcenter as c ON b.fk_costcenter=c.rowid';
                $sql .= ' WHERE b.fk_bom ='. (int) $idbom;
                $resql = $this->db->query($sql);

                if ($resql) {
                    
                    // For each line of bom create a journal line
                    while ($obj = $this->db->fetch_object($resql)) {
                        $sql1 = 'INSERT INTO  '.MAIN_DB_PREFIX.'manufacturingproduction_journal (entity, label, fk_costcenter, fk_project, type, origin, fk_linked_id, date, qty, amount) VALUES(';
                        $sql1.= getEntity($object->element).',';
                        $sql1.= '"'.$label.'",';
                        $sql1.= $obj->fk_costcenter.',';
                        $sql1.= ($project_id?$project_id:'Null') .',';
                        $sql1.= $obj->type.',';
                        $sql1.= '2,';
                        $sql1.= $id.',';
                        $sql1.= '"'.$this->db->idate(dol_now()).'",';
                        $sql1.= $obj->qty*$object->qty.',';
                        $sql1.= $obj->value.')';
                        $resql1 = $this->db->query($sql1);

                        if (!$resql1) {
                            setEventMessage($this->db->lasterror(), 'errors');
                            return -1;
                        }
                 
        
                    } 
                    
                    // Cretethe Material cost center Line (value will be updated on erch line creation)
                    if ($materiale_costcenter) {

                        require_once dol_buildpath('/manufacturingproduction/class/journal.class.php');
                        $journalstatic = new Journal($this->db);

                         $sql1 = 'INSERT INTO  '.MAIN_DB_PREFIX.'manufacturingproduction_journal (entity, label, fk_costcenter, fk_project, type, origin, fk_linked_id, date, qty, amount) VALUES(';
                         $sql1.= getEntity($object->element).',';
                         $sql1.= '"'.$label.'",';
                         $sql1.= $materiale_costcenter.',';
                         $sql1.= ($project_id?$project_id:'Null') .',';
                         $sql1.= $journalstatic->getCostcenterType($materiale_costcenter).',';
                         $sql1.= '2,';
                         $sql1.= $id.',';
                         $sql1.= '"'.$this->db->idate(dol_now()).'",';
                         $sql1.= $object->qty.',';
                         $sql1.= '0)';
                         $resql1 = $this->db->query($sql1);

                         if (!$resql1) {
                             setEventMessage($this->db->lasterror(), 'errors');
                             return -1;
                         }
                    }

                } else {
                    setEventMessage($this->db->lasterror(), 'errors');
                    return -1;
                    
                }
                break;

            case 'MOLINE_CREATE':
                // When a 'toconsume' order line, add to the costcenter journal line the value of the material
                
                if ($object->role == 'toconsume') {
    
                    require_once dol_buildpath('/manufacturingproduction/lib/manufacturingproduction.lib.php');                                

                    $materiale_costcenter = GetOrderMaterialCostCenter($object->fk_mo);
                    if ($materiale_costcenter && $object->qty > 0) {

                        require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
                        $tmpproduct = new Product($this->db);

                        $tmpproduct->fetch($object->fk_product);
                        $costprice = price2num((!empty($tmpproduct->cost_price)) ? $tmpproduct->cost_price : $tmpproduct->pmp);
                        if (empty($costprice)) {
                                require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.product.class.php';
                                $productFournisseur = new ProductFournisseur($this->db);
                                if ($productFournisseur->find_min_price_product_fournisseur($object->fk_product) > 0) {
                                        $costprice = $productFournisseur->fourn_unitprice;
                                } else {
                                        $costprice = 0;
                                }
                        }
                        if ($costprice <> 0) { 
                            $costprice = price2num($costprice * $object->qty);
                            $sql = 'UPDATE '.MAIN_DB_PREFIX.'manufacturingproduction_journal';
                            $sql .= ' SET amount=amount+'.$costprice;
                            $sql .= ' WHERE fk_linked_id='.$object->fk_mo.' AND origin=2 AND fk_costcenter='.$materiale_costcenter;
                            $resql = $this->db->query($sql);

                            if (!$resql) {
                                setEventMessage($this->db->lasterror(), 'errors');
                                return -1;
                            }
                        }
                    }
                }
                
                break;
                
            case 'MO_DELETE':
                // Delete the corrisponding centercost lines
                $sql = 'DELETE FROM '.MAIN_DB_PREFIX.'manufacturingproduction_journal';
                $sql.= ' WHERE origin=2 AND fk_linked_id='.$object->id;        
                $resql = $this->db->query($sql);

                if (!$resql) {
                    setEventMessage($this->db->lasterror(), 'errors');
                    return -1;
                }                
                break;
                
            case 'TASK_TIMESPENT_CREATE':
            case 'TASK_TIMESPENT_MODIFY':
            case 'TASK_TIMESPENT_DELETE':

                require_once dol_buildpath('/manufacturingproduction/lib/manufacturingproduction.lib.php');                
                
                $userstatic = new User($this->db);
                
                $project_id = $object->fk_project;
                $user_id = $object->timespent_fk_user;
                $date = $object->timespent_date;
                $method = $conf->global->MANUFACTURINGPRODUCTION_WORKHOUR_GROUPING;
                $old_hours = $object->timespent_old_duration/3600;
                $hours= $object->timespent_duration/3600;
                
                // Get the user cost center and corrwsponding value
                $user->fetch($user_id);
                $costcenter= $user->array_options['options_costcenter'];
                if (!$costcenter) {
                    $object->error = $langs->trans('NoCostcenterForUser', $user->lastname.' '.$user->firsname);
                    return -1;
                }
                $sql  = 'SELECT value FROM '.MAIN_DB_PREFIX.'manufacturingproduction_costcenter';
                $sql .= ' WHERE rowid ='. $costcenter;
                $resql = $this->db->query($sql);
                if ($resql) {
                    $obj = $this->db->fetch_object($resql);
                    $value = $obj->value;
                } else {
                    $object->error = $this->db->lasterror();
                    return -1;
                }
                
                // Get the date to be used in the llx_manufacturingproduction_journal
                // If teh date has been modified then get the original date 
                if ($action == 'TASK_TIMESPENT_MODIFY') {
                    $prev_date = strtotime($_SESSION["timespent_old_data"]["date"]);
                    $prev_user_id = $_SESSION["timespent_old_data"]["user"];
                }

                if (!$conf->global->MANUFACTURINGPRODUCTION_USER_DETAIL) {
                    $user_id = null;
                    $prev_user_id = null;
                }

                
                switch ($method) {
                    case 'W':
                        $dayToFriday = 5-date("w",$date);
                        $date_end = $date + (60*60*24*($dayToFriday==5 ? -2 : $dayToFriday));
                        if ($action == 'TASK_TIMESPENT_MODIFY') {
                            $dayToFriday = 5-date("w",$prev_date);
                            $prev_date_end = $prev_date + (60*60*24*($dayToFriday==5 ? -2 : $dayToFriday));
                        }
                        break;
                    case 'M':
                        $sdate = date('m/t/Y', $date);
                        $date_end = strtotime($sdate);
                        if ($action == 'TASK_TIMESPENT_MODIFY') {
                            $sdate = date('m/t/Y', $prev_date);
                            $prev_date_end = strtotime($sdate);
                        }
                        $sdate = date('m/01/Y', $date);
                        break;
                    default:
                        $date_end = $date;
                        if ($action == 'TASK_TIMESPENT_MODIFY') {
                            $prev_date_end = $prev_date;
                        }
                        break;
                }
                
                if ($action == 'TASK_TIMESPENT_MODIFY' &&
                    (date('d/m/Y', $prev_date_end) != date('d/m/Y', $date_end) || 
                    ($user_id != $prev_user_id)) ) {
                    // Decrease the time to the prevouous day total 
                    $ret = UpdateJournal($costcenter, $prev_date_end, $project_id, 1, $prev_user_id, -$old_hours, -($old_hours*$value));
                    if ($ret > 0)
                        UpdateJournal($costcenter, $date_end, $project_id, 1, $user_id, $hours, $hours*$value);
                } elseif ($action == 'TASK_TIMESPENT_MODIFY') {
                    $ret = UpdateJournal($costcenter, $date_end, $project_id, 1, $user_id, $hours-$old_hours, ($hours-$old_hours)*$value);
                } elseif ($action == 'TASK_TIMESPENT_CREATE') {
                    $ret = UpdateJournal($costcenter, $date_end, $project_id, 1, $user_id, $hours, $hours*$value);
                } elseif ($action == 'TASK_TIMESPENT_DELETE') {
                    $ret = UpdateJournal($costcenter, $date_end, $project_id, 1, $user_id, -$hours, -$hours*$value);
                }
                
                if ($ret == -1) {
                    $object->error = $this->db->lasterror();
                    return -1;
                } elseif ($ret < 0) {
                    $object->error = $langs->trans('MoreThanOneJournalLine', -$ret);
                    return -1;
                }
                break;

            default:
                dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
                break;
        }

        return 0;
    }
}
