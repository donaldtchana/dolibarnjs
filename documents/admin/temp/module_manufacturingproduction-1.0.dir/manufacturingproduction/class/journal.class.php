<?php
/* Copyright (C) 2017  Laurent Destailleur <eldy@users.sourceforge.net>
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
 * \file        class/journal.class.php
 * \ingroup     manufacturingproduction
 * \brief       This file is a CRUD class file for Journal (Create/Read/Update/Delete)
 */

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
require_once dol_buildpath('/manufacturingproduction/lib/manufacturingproduction.lib.php');

//require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
//require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';

/**
 * Class for Journal
 */
class Journal extends CommonObject {
    /**
     * @var string ID of module.
     */
    public $module = 'manufacturingproduction';

    /**
     * @var string ID to identify managed object.
     */
    public $element = 'journal';

    /**
     * @var string Name of table without prefix where object is stored. This is also the key used for extrafields management.
     */
    public $table_element = 'manufacturingproduction_journal';

    /**
     * @var int  Does this object support multicompany module ?
     * 0=No test on entity, 1=Test with field entity, 'field@table'=Test with link by field@table
     */
    public $ismultientitymanaged = 1;

    /**
     * @var int  Does object support extrafields ? 0=No, 1=Yes
     */
    public $isextrafieldmanaged = 0;

    /**
     * @var string String with name of icon for journal. Must be a 'fa-xxx' fontawesome code (or 'fa-xxx_fa_color_size') or 'journal@manufacturingproduction' if picto is file 'img/object_journal.png'.
     */
    public $picto = 'journal@manufacturingproduction';
    public $pictoC = 'journalC@manufacturingproduction';
    public $pictoR = 'journalR@manufacturingproduction';

    public $originsarray = array('1'=>'Staff', '2'=>'Production', '3'=>'Invoice', '4'=>'SupplierInvoice', '5'=>'Material');

    // BEGIN MODULEBUILDER PROPERTIES
    /**
     * @var array  Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
     */
    public $fields=array();
    public $rowid;
    public $entity;
    public $label;
    public $amount;
    public $qty;
    public $type;
    public $fk_costcenter;
    public $fk_linked_id;
    public $fk_project;
    public $origin;
    public $date;
    // END MODULEBUILDER PROPERTIES


    /**
     * Constructor
     *
     * @param DoliDb $db Database handler
     */
    public function __construct(DoliDB $db) {
        global $conf, $langs;

        $this->db = $db;

        if (empty($conf->global->MAIN_SHOW_TECHNICAL_ID) && isset($this->fields['rowid']) && !empty($this->fields['ref'])) {
                $this->fields['rowid']['visible'] = 0;
        }
        if (empty($conf->multicompany->enabled) && isset($this->fields['entity'])) {
                $this->fields['entity']['enabled'] = 0;
        }

        
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
        $this->fields=array(
                'rowid' => array('type'=>'integer', 'label'=>'TechnicalID', 'enabled'=>'1', 'position'=>1, 'notnull'=>1, 'visible'=>1, 'noteditable'=>'1', 'index'=>1, 'css'=>'left', 'comment'=>"Id"),
                'entity' => array('type'=>'integer', 'label'=>'Entity', 'enabled'=>'1', 'position'=>10, 'notnull'=>1, 'visible'=>0, 'default'=>'1', 'index'=>1,),
                'label' => array('type'=>'varchar(255)', 'label'=>'Label', 'enabled'=>'1', 'position'=>30, 'notnull'=>1, 'visible'=>1, 'searchall'=>1, 'css'=>'minwidth300', 'cssview'=>'wordbreak', 'showoncombobox'=>'2', 'validate'=>'1',),
                'fk_costcenter' => array('type'=>'sellist:manufacturingproduction_costcenter:label:rowid::moveable=1:master', 'label'=>'CostCenter', 'enabled'=>'1', 'position'=>33, 'notnull'=>1, 'visible'=>1,),
                //'fk_costcenter' => array('type'=>'integer:CostCenter:custom/manufacturingproduction/class/costcenter.class.php:1', 'label'=>'CostCenter', 'enabled'=>1, 'visible'=>-1, 'position'=>33),
                'fk_project' => array('type'=>'integer:Project:projet/class/project.class.php:1:fk_statut=1', 'label'=>'Project', 'enabled'=>1, 'visible'=>-1, 'position'=>35),
                'type' => array('type'=>'select', 'label'=>'Type', 'enabled'=>'1', 'noteditable' => 1, 'position'=>37, 'notnull'=>1, 'visible'=>1, 'default'=>'0', 'arrayofkeyval'=>array('1'=>'Cost', '2'=>'Revenue'),),
                'origin' => array('type'=>'select', 'label'=>'Origin', 'enabled'=>'1', 'position'=>40, 'notnull'=>1, 'visible'=>1, 'arrayofkeyval'=>$this->originsarray,),
                'fk_linked_id' => array('type'=>'integer', 'label'=>'Entity', 'enabled'=>'1', 'position'=>45, 'notnull'=>1, 'visible'=>0, 'default'=>null, 'index'=>1,),
                'date' => array('type'=>'date', 'label'=>'Date', 'enabled'=>'1', 'position'=>50, 'notnull'=>1, 'visible'=>1,),
                'qty' => array('type'=>'real', 'label'=>'Qty', 'enabled'=>'1', 'position'=>60, 'notnull'=>1, 'visible'=>1, 'isameasure'=>'1', 'css'=>'maxwidth75imp', 'validate'=>'1',),
                'amount' => array('type'=>'price', 'label'=>'Amount', 'enabled'=>'1', 'position'=>70, 'notnull'=>1, 'visible'=>1, 'default'=>'null', 'isameasure'=>'1', 'validate'=>'1',),
        );

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
    }

    /**
     * Create object into database
     *
     * @param  User $user      User that creates
     * @param  bool $notrigger false=launch triggers after, true=disable triggers
     * @return int             <0 if KO, Id of created object if OK
     */
    public function create(User $user, $notrigger = false) {
        $costType = $this->getCostcenterType($this->fk_costcenter);
        if ($costType > 0) {
            $this->type = $costType;
            $resultcreate = $this->createCommon($user, $notrigger);
            return $resultcreate;
        } else {
            $this->errors[] = 'Error '.$this->db->lasterror();
            return -1;
        }
    }

    /**
     * Load object in memory from the database
     *
     * @param int    $id   Id object
     * @param string $ref  Ref
     * @return int         <0 if KO, 0 if not found, >0 if OK
     */
    public function fetch($id, $ref = null) {
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
    public function fetchAll($sortorder = '', $sortfield = '', $limit = 0, $offset = 0, array $filter = array(), $filtermode = 'AND') {
        global $conf;

        dol_syslog(__METHOD__, LOG_DEBUG);

        $records = array();

        $sql = "SELECT ";
        $sql .= $this->getFieldList('t');
        $sql .= " FROM ".MAIN_DB_PREFIX.$this->table_element." as t";
        if (isset($this->ismultientitymanaged) && $this->ismultientitymanaged == 1) {
                $sql .= " WHERE t.entity IN (".getEntity($this->element).")";
        } else {
                $sql .= " WHERE 1 = 1";
        }
        // Manage filter
        $sqlwhere = array();
        if (count($filter) > 0) {
                foreach ($filter as $key => $value) {
                        if ($key == 't.rowid') {
                                $sqlwhere[] = $key." = ".((int) $value);
                        } elseif (in_array($this->fields[$key]['type'], array('date', 'datetime', 'timestamp'))) {
                                $sqlwhere[] = $key." = '".$this->db->idate($value)."'";
                        } elseif ($key == 'customsql') {
                                $sqlwhere[] = $value;
                        } elseif (strpos($value, '%') === false) {
                                $sqlwhere[] = $key." IN (".$this->db->sanitize($this->db->escape($value)).")";
                        } else {
                                $sqlwhere[] = $key." LIKE '%".$this->db->escape($value)."%'";
                        }
                }
        }
        if (count($sqlwhere) > 0) {
                $sql .= " AND (".implode(" ".$filtermode." ", $sqlwhere).")";
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
                $this->errors[] = 'Error '.$this->db->lasterror();
                dol_syslog(__METHOD__.' '.join(',', $this->errors), LOG_ERR);

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
    public function update(User $user, $notrigger = false) {
        
        $costType = $this->getCostcenterType($this->fk_costcenter);
        if ($costType > 0) {
            $this->type = $costType;
            return $this->updateCommon($user, $notrigger);
        } else {
            $this->errors[] = 'Error '.$this->db->lasterror();
            return -1;
        }
    }

    /**
     * Delete object in database
     *
     * @param User $user       User that deletes
     * @param bool $notrigger  false=launch triggers after, true=disable triggers
     * @return int             <0 if KO, >0 if OK
     */
    public function delete(User $user, $notrigger = false) {
        return $this->deleteCommon($user, $notrigger);
        //return $this->deleteCommon($user, $notrigger, 1);
    }

    /**
     *	Validate object
     *
     *	@param		User	$user     		User making status change
     *  @param		int		$notrigger		1=Does not execute triggers, 0= execute triggers
     *	@return  	int						<=0 if OK, 0=Nothing done, >0 if KO
     */
    public function validate($user, $notrigger = 0) {
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
    public function getNomUrl($withpicto = 0, $option = '', $notooltip = 0, $morecss = '', $save_lastsearch_value = -1) {
        global $conf, $langs, $hookmanager;

        if (!empty($conf->dol_no_mouse_hover)) {
            $notooltip = 1; // Force disable tooltips
        }

        $result = '';

        $label = img_picto('', $this->picto).' <u>'.$langs->trans("Journal").'</u>';
        if (isset($this->status)) {
            $label .= ' '.$this->getLibStatut(5);
        }
        $label .= '<br>';
        $label .= '<b>'.$langs->trans('Ref').':</b> '.$this->ref;

        $url = dol_buildpath('/manufacturingproduction/journal_card.php', 1).'?id='.$this->id;
        if ($_GET['id'])
            $url .= '&origin='.$_GET['id'];

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
                $label = $langs->trans("ShowJournal");
                $linkclose .= ' alt="'.dol_escape_htmltag($label, 1).'"';
            }
            $linkclose .= ' title="'.dol_escape_htmltag($label, 1).'"';
            $linkclose .= ' class="classfortooltip'.($morecss ? ' '.$morecss : '').'"';
        } else {
            $linkclose = ($morecss ? ' class="'.$morecss.'"' : '');
        }

        if ($option == 'nolink' || empty($url)) {
            $linkstart = '<span';
        } else {
            $linkstart = '<a href="'.$url.'"';
        }
        $linkstart .= $linkclose.'>';
        if ($option == 'nolink' || empty($url)) {
            $linkend = '</span>';
        } else {
            $linkend = '</a>';
        }

        $result .= $linkstart;

        if (empty($this->showphoto_on_popup)) {
            if ($withpicto) {
                if ($this->type == 1)
                    $result .= img_object(($notooltip ? '' : $label), ($this->picto ? $this->pictoC : 'generic'), ($notooltip ? (($withpicto != 2) ? 'class="paddingright"' : '') : 'class="'.(($withpicto != 2) ? 'paddingright ' : '').'classfortooltip"'), 0, 0, $notooltip ? 0 : 1);
                else 
                    $result .= img_object(($notooltip ? '' : $label), ($this->picto ? $this->pictoR : 'generic'), ($notooltip ? (($withpicto != 2) ? 'class="paddingright"' : '') : 'class="'.(($withpicto != 2) ? 'paddingright ' : '').'classfortooltip"'), 0, 0, $notooltip ? 0 : 1);
            }
        } else {
            if ($withpicto) {
                require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

                list($class, $module) = explode('@', $this->picto);
                $upload_dir = $conf->$module->multidir_output[$conf->entity]."/$class/".dol_sanitizeFileName($this->ref);
                $filearray = dol_dir_list($upload_dir, "files");
                $filename = $filearray[0]['name'];
                if (!empty($filename)) {
                    $pospoint = strpos($filearray[0]['name'], '.');

                    $pathtophoto = $class.'/'.$this->ref.'/thumbs/'.substr($filename, 0, $pospoint).'_mini'.substr($filename, $pospoint);
                    if (empty($conf->global->{strtoupper($module.'_'.$class).'_FORMATLISTPHOTOSASUSERS'})) {
                        $result .= '<div class="floatleft inline-block valignmiddle divphotoref"><div class="photoref"><img class="photo'.$module.'" alt="No photo" border="0" src="'.DOL_URL_ROOT.'/viewimage.php?modulepart='.$module.'&entity='.$conf->entity.'&file='.urlencode($pathtophoto).'"></div></div>';
                    } else {
                        $result .= '<div class="floatleft inline-block valignmiddle divphotoref"><img class="photouserphoto userphoto" alt="No photo" border="0" src="'.DOL_URL_ROOT.'/viewimage.php?modulepart='.$module.'&entity='.$conf->entity.'&file='.urlencode($pathtophoto).'"></div>';
                    }

                    $result .= '</div>';
                } else {
                    $result .= img_object(($notooltip ? '' : $label), ($this->picto ? $this->picto : 'generic'), ($notooltip ? (($withpicto != 2) ? 'class="paddingright"' : '') : 'class="'.(($withpicto != 2) ? 'paddingright ' : '').'classfortooltip"'), 0, 0, $notooltip ? 0 : 1);
                }
            }
        }

        if ($withpicto != 2) {
            $result .= $this->ref;
        }

        $result .= $linkend;
        //if ($withpicto != 2) $result.=(($addlabel && $this->label) ? $sep . dol_trunc($this->label, ($addlabel > 1 ? $addlabel : 0)) : '');

        global $action, $hookmanager;
        $hookmanager->initHooks(array('journaldao'));
        $parameters = array('id'=>$this->id, 'getnomurl' => &$result);
        $reshook = $hookmanager->executeHooks('getNomUrl', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
        if ($reshook > 0) {
                $result = $hookmanager->resPrint;
        } else {
                $result .= $hookmanager->resPrint;
        }

        return $result;
    }

    /**
     *  Return the label of the status
     *
     *  @param  int		$mode          0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
     *  @return	string 			       Label of status
     */
    public function getLabelStatus($mode = 0)
    {
            return $this->LibStatut($this->status, $mode);
    }

    /**
     *  Return the label of the status
     *
     *  @param  int		$mode          0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
     *  @return	string 			       Label of status
     */
    public function getLibStatut($mode = 0) {
        return 0;
    }

    /***********************************************************************************************************************************************/
    
    /**
     * Get cost center type (Cost/Revenge)
     *
     * @param  int $costId      Cost Center id
     * @return int             <0 if KO, >0 if OK
     */
    public function getCostcenterType($costId) {
        // Get type from the selected cost cente
        $sql = "SELECT type FROM ".MAIN_DB_PREFIX."manufacturingproduction_costcenter";
        $sql .= " WHERE entity IN (".getEntity($this->element).")";
        $sql .= " AND rowid=".$costId;
            
        $resql = $this->db->query($sql);
        if ($resql) {
            $obj = $this->db->fetch_object($resql);
            return $obj->type;
        } else {
            return -1;
        }
        
    }
    
    /**
     * Get the url of the origin object
     *
     * @param  int $costId      Cost Center id
     * @return int             <0 if KO, >0 if OK
     */
    public function getOriginUrl() {
        switch ($this->origin) {
            case 1:
                $classname = 'User';
                $classpath = 'user/class/user.class.php';
                break;
            case 2:
                $classname = 'Mo';
                $classpath = 'mrp/class/mo.class.php';
                break;
            case 3:
                $classname = 'Facture';
                $classpath = 'compta/facture/class/facture.class.php';
                break;
            case 4:
                $classname = 'FactureFournisseur';
                $classpath = 'fourn/class/fournisseur.facture.class.php';
                break;
            default:
                return '';
        }
        
        dol_include_once($classpath);
        if ($classname && class_exists($classname)) {
            $object = new $classname($this->db);
            if ($object->element === 'product') {	// Special cas for product because default valut of fetch are wrong
                $result = $object->fetch($value, '', '', '', 0, 1, 1);
            } else {
                $result = $object->fetch($this->fk_linked_id);
            }
            if ($result > 0) {
                $value = $object->getNomUrl(1);
            } else {
                $value = '';
            }
        }
    
        return $value;
        
    }

    
    /**
     * Get value of the origin object
     *
     * @param  object $object       the object 
     * @return int                  the corrisponding value
     */
    private function getOrigin($object) {
        $type = get_class($object);
        switch ($type) {
            case 'User':
                return 1;
                break;
            case 'Mo':
                return 2;
                break;
            case 'Facture':
                return 3;
                break;
            case 'FactureFournisseur':
                return 4;
                break;
            default: 
               return -1;
        }
    }

    /**
     * Print all Journal lines associared with te object
     *  @param  object  $object                     calling object
     *  @param  int     $id                         calling object's id 
     *  @param  string  $action                     $action in the form
     *  @param  boolean $permissiontoedit           user permission for edit lines 
     *  @param  boolean $permissiontodelete         user permission for delete lines
     * 
     * @return void
     */
    public function printJournalLines($object, $id, $action, $permissiontoedit, $permissiontodelete, $getProject = false) {
        global $langs, $db, $form, $user, $conf;
        
        if ($getProject && !empty($conf->project->enabled)) {
            $projectStatic = new Project($db);
        }
        

        $totalAmouny = 0;
        $out = '
        <!-- script to auto show attributes select tags if a variant was selected -->
        <script>
            
            function getCostCenterAmount(id) {
                $.ajax({
                    url : "ajax/costcenter_amount.php",
                    type : "GET",
                    data : {
                        "id" : id
                    },
                    dataType:"json",
                    success : function(data) {  
                        data = parseFloat(data).toFixed(2);
                        $("#lineamount").val(data);
                    },
                    error : function(request,error) {
                        alert("Request: "+JSON.stringify(request));
                    }
                });            
            }

            jQuery(document).ready(function () {
                jQuery("#linecostcenter").change(function () {
                    var id = this.value;
                    getCostCenterAmount(id);
                });
                
                //getCostCenterAmount($("select[name=linecostcenter] option").filter(":selected").val());
                
            })
        </script>';
        print $out;
        
        // Title line
        print "<thead>\n";

        print '<tr class="liste_titre nodrag nodrop">';
        
        // Date
        print '<td class="linecoldescription">'.$langs->trans('Label').'</td>';

        // Cost Center
        print '<td class="linecoldescription">'.$langs->trans('CostCenter');
        print '</td>';
        
        // Project
        if ($getProject && !empty($conf->project->enabled)) {
            print '<td class="linecoldescription">'.$langs->trans('Project');
            print '</td>';
        }

        // Date
        print '<td class="linecolqty center">'.$langs->trans('Date').'</td>';

        // Qty
        print '<td class="linecolqty right">'.$langs->trans('Qty').'</td>';

        // Amount
        if ($user->rights->manufacturingproduction->journal->viewamounts)
            print '<td class="linecolqty right">'.$langs->trans('Amount').'</td>';
        
        print '<td class="linecoledit"></td>'; // No width to allow autodim

        print '<td class="linecoldelete" style="width: 10px"></td>';

        print "</tr>\n";
        print "</thead>\n";

        $sql = 'SELECT rowid, label, fk_costcenter, fk_project, date, qty, amount FROM '.MAIN_DB_PREFIX.'manufacturingproduction_journal AS c';
        $sql.= ' WHERE origin ='.$this->getOrigin($object).' and fk_linked_id='.(int) $id ;
        $resql = $db->query($sql);

        if ($resql) {

            $selected = GETPOST('lineid', 'int');
            $num = $resql->num_rows;

            // Loop on all the lines if they exist

            while ($obj = $db->fetch_object($resql)) {
                $tmpcostcenter = new CostCenter($db);
                $tmpcostcenter->fetch($obj->fk_costcenter);

                if ($object->status == 0 && $action == 'editline' && $selected == $obj->rowid) {
                    // Edit a line
                    print '<tr class="oddeven tredited">';

                    // Label
                    print '<td class="bordertop nobottom linecoldescription minwidth500imp">';
                    print '<div id="line_'.$obj->rowid.'"></div>';
                    print '<input type="hidden" name="lineid" value="'.$obj->rowid.'">';
                    print '<input type="text" size="40" name="linelabel" id="linelabel" class="flat left" value="'.$obj->label.'">';
                    print '</td>';

                    // Cost Center
                    print '<td class="bordertop nobottom linecoldescription minwidth500imp">';
                    print select_costcenter($obj->fk_costcenter,'linecostcenter', false);
                    print '</td>';
                    
                    // Project
                    if ($getProject && !empty($conf->project->enabled)) {
                        print '<td class="bordertop nobottom linecoldescription minwidth500imp">';
                        print $form->selectProjectsList($obj->fk_project, 'lineproject'); 
                        print '</td>';
                    }

                    // Date
                    print '<td class="bordertop nobottom center">';
                    print $form->selectDate($obj->date, 'linedate'); 
                    print '</td>';

                    // Qty
                    print '<td class="bordertop nobottom linecolqty right">';
                    print '<input type="text" size="2" name="lineqty" id="lineqty" class="flat right" value="'.price($obj->qty).'">';
                    print '</td>';

                    // Amount
                    if ($user->rights->manufacturingproduction->journal->viewamounts) {
                        print '<td class="bordertop nobottom linecolqty right">';
                        print '<input type="text" size="4" name="lineamount" id="lineamount" class="flat right" value="'.price($obj->amount).'">';
                        print '</td>';
                    }

                    // Buttons
                    print '<td class="nobottom linecoledit center valignmiddle" colspan="2">';
                    print '<input type="submit" class="button buttongen margintoponly marginbottomonly button-save" id="savelinebutton" name="save" value="'.$langs->trans("Save").'">';
                    print '<input type="submit" class="button buttongen margintoponly marginbottomonly button-cancel" id="cancellinebutton" name="cancel" value="'.$langs->trans("Cancel").'">';
                    print '</td>';

                    print '</tr>';

                } else {
                    // View a line
                    print '<tr id="row-'.$obj->rowid.'" class="drag drop oddeven">';

                    // Label
                    print '<td class="linecoldescription minwidth300imp">';
                    print '<div id="line_'.$line->id.'"></div>';
                    print $obj->label;
                    print '</td>';

                    // Cost Center
                    print '<td class="linecoldescription minwidth300imp">';
                    print '<div id="line_'.$line->id.'"></div>';
                    print $tmpcostcenter->getNomUrl(1);
                    print ' - '.$tmpcostcenter->label;
                    print '</td>';
                    
                    if ($getProject && !empty($conf->project->enabled)) {
                        print '<td class="linecoldescription minwidth300imp">';
                        $result = $projectStatic->fetch($obj->fk_project);
                        if ($result > 0) {
                            print $projectStatic->getNomUrl(1);
                        }
                        print '</td>';
                    }
                    

                    // Date
                    print '<td class="linecolqty nowrap center">';
                    print dol_print_date($obj->date,'day');
                    print '</td>';

                    // Qty
                    print '<td class="linecolqty nowrap right">';
                    echo price($obj->qty, 0, '', 0, 0); // Yes, it is a quantity, not a price, but we just want the formating role of function price
                    print '</td>';

                    // Amount
                    if ($user->rights->manufacturingproduction->journal->viewamounts) {
                        print '<td class="linecolqty nowrap right">';
                        echo price($obj->amount); 
                        print '</td>';
                    }

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
                if ($tmpcostcenter->type == 2)
                    $totalAmouny += ($obj->qty * $obj->amount);
                else
                    $totalAmouny -= ($obj->qty * $obj->amount);
            }

        }
        
        return $totalAmouny;;

    }
    
    /**
     * Print the row to insert a journal line
     * 
     * @return void
     */
    public function CreateJournalLine($getProject = false) {
        global $langs, $form, $user, $conf;

        print '<tr class="pair nodrag nodrop nohoverpair liste_titre_create">';
        
        // Label
        print '<td class="bordertop nobottom linecoldescription minwidth500imp">';
        print '<input type="text" size="40" name="linelabel" id="linelabel" class="flat left" value="">';
        print '</td>';
        
        // Cost Center
        print '<td class="bordertop nobottom linecoldescription minwidth500imp">';
        print select_costcenter(-1,'linecostcenter', false);
        print '</td>';
        
        // Cost Center
        if ($getProject && !empty($conf->project->enabled)) {
            print '<td class="bordertop nobottom linecoldescription minwidth500imp">';
            print $form->selectProjectsList($obj->fk_project, 'lineproject'); 
            print '</td>';
        }

        // Date
        print '<td class="bordertop nobottom center">';
        print $form->selectDate('', 'linedate'); 
        print '</td>';

        // Qty
        print '<td class="bordertop nobottom linecolqty right">';
        print '<input type="text" size="2" name="lineqty" id="lineqty" class="flat right" value="1">';
        print '</td>';

        // Amount
        if ($user->rights->manufacturingproduction->journal->viewamounts) {
            print '<td class="bordertop nobottom linecolqty right">';
            print '<input type="text" size="4" name="lineamount" id="lineamount" class="flat right" value="0">';
            print '</td>';
        } else 
            print '<input type="hidden" name="lineamount" id="lineamount">';

        print '<td class="bordertop nobottom linecoledit center valignmiddle" colspan="2">';
        print '<input type="submit" class="button button-add" name="addline" id="addline" value="'.$langs->trans('Add').'">';
        print '</td>';
        print '</tr>';        
    }

    /**
     * Insert a row in the journal
     * 
     * @return void
     */
    public function AddJournalLine($object, $object_id)  {
        global $db;

        $label = GETPOST("linelabel", 'alpha');
        $fk_costcenter =  GETPOST("linecostcenter", 'int');
        $qty =  GETPOST("lineqty", 'alpha');
        $amount =  GETPOST("lineamount", 'alpha');
        $project_id = GETPOST("lineproject", 'int') ? GETPOST("lineproject", 'int') : $object->fk_project;
        $date = dol_mktime(0, 0, 0, $_POST['linedatemonth'], $_POST['linedateday'], $_POST['linedateyear']);

        $sql = 'INSERT INTO  '.MAIN_DB_PREFIX.$this->table_element. ' (entity, label, fk_costcenter, fk_project, type, origin, fk_linked_id, date, qty, amount) VALUES(';
        $sql.= getEntity($this->element).',';
        $sql.= '"'.$label.'",';
        $sql.= $fk_costcenter.',';
        $sql.= ($project_id?$project_id:'Null') .',';
        $sql.= $this->getCostCenterType($fk_costcenter).',';
        $sql.= $this->getOrigin($object).',';
        $sql.= $object_id.',';
        $sql.= '"'.$db->idate($date).'",';
        $sql.= price2num($qty).',';
        $sql.= price2num($amount).')';
        $resql = $db->query($sql);

        if (!$resql) {
            setEventMessage($db->lasterror(), 'errors');
        }

    }
    
    /**
     * Update a row in the journal
     * 
     * @return void
     */
    public function UpdateJournalLine($rowid)  {
        global $db;

        $label = GETPOST("linelabel", 'alpha');
        $fk_costcenter =  GETPOST("linecostcenter", 'int');
        $qty =  GETPOST("lineqty", 'alpha');
        $amount =  GETPOST("lineamount", 'alpha');
        //$project_id = $object->fk_project;
        $date = dol_mktime(0, 0, 0, $_POST['linedatemonth'], $_POST['linedateday'], $_POST['linedateyear']);
        $fk_project = GETPOST("lineproject", 'int');

        $sql = 'UPDATE '.MAIN_DB_PREFIX.$this->table_element. ' SET';
        $sql.= ' label="'.$label.'",';
        $sql.= ' fk_costcenter='.$fk_costcenter.',';
        $sql.= ' fk_project='.($fk_project ? $fk_project : 'NULL').',';
        $sql.= 'date="'.$db->idate($date).'",';
        $sql.= ' qty='.price2num($qty);
        if ($amount)
            $sql.= ', amount='.price2num($amount);
        $sql.= ' WHERE rowid='.$rowid;        
        $resql = $db->query($sql);

        if (!$resql) {
            setEventMessage($db->lasterror(), 'errors');
        }

    }
    
    /**
     * Update a row in the journal
     * 
     * @return void
     */
    public function DeleteJournalLine($rowid)  {
        global $db;
        
        $sql = 'DELETE FROM '.MAIN_DB_PREFIX.$this->table_element;
        $sql.= ' WHERE rowid='.$rowid;        
        $resql = $db->query($sql);

        if (!$resql) {
            setEventMessage($db->lasterror(), 'errors');
        }

    
    
        
    }
}

