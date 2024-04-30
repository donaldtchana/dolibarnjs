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
 * \file        class/costcenter.class.php
 * \ingroup     manufacturingproduction
 * \brief       This file is a CRUD class file for CostCenter (Create/Read/Update/Delete)
 */

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
//require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
//require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';

/**
 * Class for CostCenter
 */
class CostCenter extends CommonObject {
    /**
     * @var string ID of module.
     */
    public $module = 'manufacturingproduction';

    /**
     * @var string ID to identify managed object.
     */
    public $element = 'costcenter';

    /**
     * @var string Name of table without prefix where object is stored. This is also the key used for extrafields management.
     */
    public $table_element = 'manufacturingproduction_costcenter';

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
     * @var string String with name of icon for costcenter. Must be a 'fa-xxx' fontawesome code (or 'fa-xxx_fa_color_size') or 'costcenter@manufacturingproduction' if picto is file 'img/object_costcenter.png'.
     */
    public $picto  = 'costcenter@manufacturingproduction';
    public $pictoC = 'costcenterC@manufacturingproduction';
    public $pictoR = 'costcenterR@manufacturingproduction';
    

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
	public $fields=array(
		'rowid' => array('type'=>'integer', 'label'=>'TechnicalID', 'enabled'=>'1', 'position'=>1, 'notnull'=>1, 'visible'=>0, 'noteditable'=>'1', 'index'=>1, 'css'=>'left', 'comment'=>"Id"),
		'entity' => array('type'=>'integer', 'label'=>'Entity', 'enabled'=>'1', 'position'=>10, 'notnull'=>1, 'visible'=>0, 'default'=>'1', 'index'=>1,),
		'label' => array('type'=>'varchar(255)', 'label'=>'Label', 'enabled'=>'1', 'position'=>30, 'notnull'=>1, 'visible'=>1, 'searchall'=>1, 'css'=>'minwidth300', 'cssview'=>'wordbreak', 'showoncombobox'=>'2', 'validate'=>'1',),
		'master' => array('type'=>'varchar(2)', 'label'=>'Master', 'enabled'=>'1', 'position'=>40, 'notnull'=>1, 'visible'=>1, 'searchall'=>1,),
		'detail' => array('type'=>'varchar(2)', 'label'=>'Detail', 'enabled'=>'1', 'position'=>50, 'notnull'=>0, 'visible'=>1, 'searchall'=>1,),
		'sub_detail' => array('type'=>'varchar(4)', 'label'=>'Sub_detail', 'enabled'=>'1', 'position'=>60, 'notnull'=>0, 'visible'=>1, 'searchall'=>1,),
		'moveable' => array('type'=>'boolean', 'label'=>'Moveable', 'enabled'=>'1', 'position'=>65, 'notnull'=>1, 'visible'=>1, 'noteditable'=>1),
		'type' => array('type'=>'select', 'label'=>'Type', 'enabled'=>'1', 'position'=>70, 'notnull'=>1, 'visible'=>1, 'default'=>'1', 'searchall'=>1, 'arrayofkeyval'=>array('1'=>'Cost', '2'=>'Revenue'),),
		'value' => array('type'=>'price', 'label'=>'Cost', 'enabled'=>'1', 'position'=>80, 'notnull'=>1, 'visible'=>1, 'searchall'=>1,),
	);
	public $rowid;
	public $entity;
	public $label;
	public $master;
	public $detail;
	public $sub_detail;
	public $type;
	public $value;
	// END MODULEBUILDER PROPERTIES


    // If this object has a subtable with lines

    // /**
    //  * @var string    Name of subtable line
    //  */
    // public $table_element_line = 'manufacturingproduction_costcenterline';

    // /**
    //  * @var string    Field with ID of parent key if this object has a parent
    //  */
    // public $fk_element = 'fk_costcenter';

    // /**
    //  * @var string    Name of subtable class that manage subtable lines
    //  */
    // public $class_element_line = 'CostCenterline';

    // /**
    //  * @var array	List of child tables. To test if we can delete object.
    //  */
    // protected $childtables = array();

    // /**
    //  * @var array    List of child tables. To know object to delete on cascade.
    //  *               If name matches '@ClassNAme:FilePathClass;ParentFkFieldName' it will
    //  *               call method deleteByParentField(parentId, ParentFkFieldName) to fetch and delete child object
    //  */
    // protected $childtablesoncascade = array('manufacturingproduction_costcenterdet');

    // /**
    //  * @var CostCenterLine[]     Array of subtable lines
    //  */
    // public $lines = array();



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
        if ($this->validate(true))
            $result = $this->createCommon($user, $notrigger);
        else 
            $result = -1;    
        return $result;
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
        if ($this->validate(false))
            return $this->updateCommon($user, $notrigger);
        else
            return -1;
    }

    /**
     * Delete object in database
     *
     * @param User $user       User that deletes
     * @param bool $notrigger  false=launch triggers after, true=disable triggers
     * @return int             <0 if KO, >0 if OK
     */
    public function delete(User $user, $notrigger = false) {
        
        $error = 0;
        
        if ($this->detail <> '' || !is_null($this->detail)) {
            $parentToMoveable = True;
             // If detail or subdetail chek if there are parent
            $sql  = 'SELECT * FROM '.MAIN_DB_PREFIX.$this->table_element;
            $sql .= ' WHERE rowid<>"'.$this->id.'"';
            $sql .= ' AND master="'.$this->master.'"';
            if ($this->detail != '')
                $sql .= ' AND detail="'.$this->detail.'"';
            $resql = $this->db->query($sql);
            if ($resql) {
                $num = $this->db->num_rows($resql);
                $is_sub_detail = $this->sub_detail != '' || !is_null($this->sub_detail);
                if (!$is_sub_detail && $num > 0) {
                    // There are child, cannot delete row
                    $this->errors[] = 'ThereAreSons';
                    $this->error = 'ThereAreSons';
                    $parentToMoveable = false;
                    $error++;
                } elseif ($num) {
                    // Search for a brother
                    $i = 0;
                    if ($num) {
                         
                        while ($i < $num) {
                            $obj = $this->db->fetch_object($resql);
                            $levelToTest = $is_sub_detail ? $obj->sub_detail : $obj->detail;
                            if (is_null($levelToTest))
                                $levelToTest = '';
                            if($levelToTest != '') {
                                $parentToMoveable = false;
                                break;
                            }
                            $i++;
                        }
                     }
                     
                }

                // If no others brother, set partent moveable to True 
                if ($parentToMoveable) {
                    $sql =  'UPDATE '.MAIN_DB_PREFIX.$this->table_element.' SET moveable=1';
                    $sql .= ' WHERE master="'.$this->master.'"';
                    if ($is_sub_detail)
                        $sql .= ' AND detail="'.$this->detail.'"';
                    $resql = $this->db->query($sql);
                }



            } else {
                $this->errors[] = $this->db->lasterror;
                $this->error = $this->db->lasterror;
                $error++;
            }
        }

            
        if (!$error)
            return $this->deleteCommon($user, $notrigger);
        else 
            return false;
        //return $this->deleteCommon($user, $notrigger, 1);
    }

    /**
     *	Validate object
     *
     *	@param		$isInsert	booleam     		the record in in insertion mode
     *	@return  	int						-1 if KO 1 if OK
     */
    private function validate($isInsert){
        $error = 0;
        
        // Check if there is an empty level
        if (($this->master == '' && $this->detail != '') || ($this->detail == '' && $this->sub_detail != ''))  {
            $this->errors[] = 'ParentLevelIsEmpty';
            $this->error = 'ParentLevelIsEmpty';
            $error++;
        }
        
        // If detail chek if the master exist and if the type is congrous
        if ($isInsert && $this->detail != '') {
            $sql  = 'SELECT  type FROM '.MAIN_DB_PREFIX.$this->table_element;
            $sql .= ' WHERE master="'.$this->master.'"';
            if ($this->sub_detail != '')
                $sql .= ' AND detail="'.$this->detail.'"';
            $resql = $this->db->query($sql);
            if ($resql) {
                $num = $this->db->num_rows($resql);
                if ($num == 0) {
                    $this->errors[] = 'ParentLevelMustExist';
                    $this->error = 'ParentLevelMustExist';
                    $error++;
                } else {
                    $obj = $this->db->fetch_object($resql);
                    if ($obj->type !== $this->type) {
                        $this->errors[] = 'TypeNotCongruous';
                        $this->error = 'TypeNotCongruous';
                        $error++;
                    }
                }
            } else {
                $this->errors[] = $this->db->lasterror;
                $this->error = $this->db->lasterror;
                $error++;
            }
            if ($error == 0) {
                $this->moveable = true;
                // Set to not moveables parents levels
                $sql  = 'UPDATE  '.MAIN_DB_PREFIX.$this->table_element;
                $sql .= ' SET moveable = false';
                $sql .= ' WHERE master="'.$this->master.'"';
                if ($this->sub_detail != '')
                    $sql .= ' AND detail="'.$this->detail.'" AND (sub_detail="" OR sub_detail IS NULL)';
                else
                    $sql .= ' AND detail="" OR detail IS NULL';
                $resql = $this->db->query($sql);
                
            }
        } elseif ($isInsert) 
            $this->moveable = true;
                
        if ($error)
            return false;
        else 
            return true;
    }

    /**
     *  Return the label of the status
     *
     *  @param  int		$mode          0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
     *  @return	string 			       Label of status
     */
    public function getLibStatut($mode = 0){
            return 0;
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

        if ($this->type == 1)
            $picto = $this->pictoC;
        else
            $picto = $this->pictoR;
            
        $label = img_picto('', $picto).' <u>'.$langs->trans("CostCenter").'</u>';
        
        $label .= '<br>';
        $label .= '<b>'.$langs->trans('Ref').':</b> '.$this->ref;

        $url = dol_buildpath('/manufacturingproduction/costcenter_card.php', 1).'?id='.$this->id;

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
                $label = $langs->trans("ShowCostCenter");
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
            $result .= img_object(($notooltip ? '' : $label), ($picto ? $picto : 'generic'), ($notooltip ? (($withpicto != 2) ? 'class="paddingright"' : '') : 'class="'.(($withpicto != 2) ? 'paddingright ' : '').'classfortooltip"'), 0, 0, $notooltip ? 0 : 1);
            }
        } else {
            if ($withpicto) {
                require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

                list($class, $module) = explode('@', $picto);
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
                    $result .= img_object(($notooltip ? '' : $label), ($picto ? $picto : 'generic'), ($notooltip ? (($withpicto != 2) ? 'class="paddingright"' : '') : 'class="'.(($withpicto != 2) ? 'paddingright ' : '').'classfortooltip"'), 0, 0, $notooltip ? 0 : 1);
                }
            }
        }

        if ($withpicto != 2) {
            $result .= $this->ref;
        }

        $result .= $linkend;
        //if ($withpicto != 2) $result.=(($addlabel && $this->label) ? $sep . dol_trunc($this->label, ($addlabel > 1 ? $addlabel : 0)) : '');

        global $action, $hookmanager;
        $hookmanager->initHooks(array('costcenterdao'));
        $parameters = array('id'=>$this->id, 'getnomurl' => &$result);
        $reshook = $hookmanager->executeHooks('getNomUrl', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
        if ($reshook > 0) {
                $result = $hookmanager->resPrint;
        } else {
                $result .= $hookmanager->resPrint;
        }

        return $result;
    }

}
