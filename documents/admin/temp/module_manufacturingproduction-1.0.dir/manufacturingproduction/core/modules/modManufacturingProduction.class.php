<?php
/* Copyright (C) 2004-2018  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2018-2019  Nicolas ZABOURI         <info@inovea-conseil.com>
 * Copyright (C) 2019-2020  Frédéric France         <frederic.france@netlogic.fr>
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
 * 	\defgroup   manufacturingproduction     Module ManufacturingProduction
 *  \brief      ManufacturingProduction module descriptor.
 *
 *  \file       htdocs/manufacturingproduction/core/modules/modManufacturingProduction.class.php
 *  \ingroup    manufacturingproduction
 *  \brief      Description and activation file for module ManufacturingProduction
 */
include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';

/**
 *  Description and activation class for module ManufacturingProduction
 */
class modManufacturingProduction extends DolibarrModules {
    /**
     * Constructor. Define names, constants, directories, boxes, permissions
     *
     * @param DoliDB $db Database handler
     */
    public function __construct($db) {
        global $langs, $conf;
        $this->db = $db;
        $this->numero = 2208210; // TODO Go on page https://wiki.dolibarr.org/index.php/List_of_modules_id to reserve an id number for your module
        $this->rights_class = 'manufacturingproduction';

        // Family can be 'base' (core modules),'crm','financial','hr','projects','products','ecm','technic' (transverse modules),'interface' (link with external tools),'other','...'
        // It is used to group modules by family in module setup page
        $this->family = "products";
        $this->module_position = '90';
        $this->name = preg_replace('/^mod/i', '', get_class($this));
        $this->description = "ManufacturingProductionDescription";
            $this->descriptionlong = "ManufacturingProductionDescription";
        $this->editor_name = 'Marcello Gribaudo';
        $this->editor_url = 'http://www.opigi.com';
        $this->version = '1.0';
        $this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
        $this->picto = 'manufacturingproduction@manufacturingproduction';

        // Define some features supported by module (triggers, login, substitutions, menus, css, etc...)
        $this->module_parts = array(
                'triggers' => 1,
                'login' => 0,
                'substitutions' => 0,
                'menus' => 0,
                'tpl' => 0,
                'barcode' => 0,
                'models' => 1,
                'printing' => 0,
                'theme' => 0,
                'css' => array(),
                'js' => array(),
                'hooks' => array('projecttasktime'),
                'moduleforexternal' => 0,
        );

        $this->dirs = array("/manufacturingproduction/temp");
        $this->config_page_url = array("setup.php@manufacturingproduction");

        // Dependencies
        $this->hidden = false;
        $this->depends = array();
        $this->requiredby = array(); // List of module class names as string to disable if this one is disabled. Example: array('modModuleToDisable1', ...)
        $this->conflictwith = array(); // List of module class names as string this module is in conflict with. Example: array('modModuleToDisable1', ...)
        $this->langfiles = array("manufacturingproduction@manufacturingproduction");

        // Prerequisites
        $this->phpmin = array(5, 6); // Minimum version of PHP required by module
        $this->need_dolibarr_version = array(11, -3); // Minimum version of Dolibarr required by module

        // Messages at activation
        $this->warnings_activation = array(); // Warning to show when we activate module. array('always'='text') or array('FR'='textfr','MX'='textmx'...)
        $this->warnings_activation_ext = array(); // Warning to show when we activate an external module. array('always'='text') or array('FR'='textfr','MX'='textmx'...)

        // Constants
        // List of particular constants to add when module is enabled (key, 'chaine', value, desc, visible, 'current' or 'allentities', deleteonunactive)
        // Example: $this->const=array(1 => array('MANUFACTURINGPRODUCTION_MYNEWCONST1', 'chaine', 'myvalue', 'This is a constant to add', 1),
        //                             2 => array('MANUFACTURINGPRODUCTION_MYNEWCONST2', 'chaine', 'myvalue', 'This is another constant to add', 0, 'current', 1)
        // );
        $this->const = array();

        // Some keys to add into the overwriting translation tables
        /*$this->overwrite_translation = array(
                'en_US:ParentCompany'=>'Parent company or reseller',
                'fr_FR:ParentCompany'=>'Maison mère ou revendeur'
        )*/

        if (!isset($conf->manufacturingproduction) || !isset($conf->manufacturingproduction->enabled)) {
            $conf->manufacturingproduction = new stdClass();
            $conf->manufacturingproduction->enabled = 0;
        }

        // Array to add new pages in new tabs
        $this->tabs = array('bom:+production:ProductionTab:manufacturingproduction@manufacturingproduction:$user->rights->manufacturingproduction->all->read:/manufacturingproduction/bom_tab.php?id=__ID__',
                            'mo@mrp:+costcenter:ProductionTab:manufacturingproduction@manufacturingproduction:$user->rights->manufacturingproduction->all->read:/manufacturingproduction/mo_tab.php?id=__ID__',
                            'invoice:+costcenter:CostCenters:manufacturingproduction@manufacturingproduction:$user->rights->manufacturingproduction->all->read:/manufacturingproduction/invoice_tab.php?id=__ID__',
                            'supplier_invoice:+costcenter:CostCenters:manufacturingproduction@manufacturingproduction:$user->rights->manufacturingproduction->all->read:/manufacturingproduction/invoice_supplier_tab.php?id=__ID__',
                            'project:+costcenter:CostCenters:manufacturingproduction@manufacturingproduction:$user->rights->manufacturingproduction->all->read:/manufacturingproduction/project_tab.php?id=__ID__',
                            'user:+costcenter:CostCenters:manufacturingproduction@manufacturingproduction:$user->rights->manufacturingproduction->all->read:/manufacturingproduction/user_tab.php?id=__ID__',);
        
//        $this->tabs[] = array('data'=>'bom:+production:production:manufacturingproduction@manufacturingproduction:$user->rights->manufacturingproduction->read:/manufacturingproduction/bomtab.php?id=__ID__');  					// To add a new tab identified by code tabname1
//        $this->tabs[] = array('data'=>'order:+production:production:manufacturingproduction@manufacturingproduction:$user->rights->manufacturingproduction->read:/manufacturingproduction/bomtab.php?id=__ID__');  					// To add a new tab identified by code tabname1
        // Example:
        // $this->tabs[] = array('data'=>'objecttype:+tabname2:SUBSTITUTION_Title2:mylangfile@manufacturingproduction:$user->rights->othermodule->read:/manufacturingproduction/mynewtab2.php?id=__ID__',  	// To add another new tab identified by code tabname2. Label will be result of calling all substitution functions on 'Title2' key.
        // $this->tabs[] = array('data'=>'objecttype:-tabname:NU:conditiontoremove');                                                     										// To remove an existing tab identified by code tabname
        //
        // Where objecttype can be
        // 'categories_x'	  to add a tab in category view (replace 'x' by type of category (0=product, 1=supplier, 2=customer, 3=member)
        // 'contact'          to add a tab in contact view
        // 'contract'         to add a tab in contract view
        // 'group'            to add a tab in group view
        // 'intervention'     to add a tab in intervention view
        // 'invoice'          to add a tab in customer invoice view
        // 'invoice_supplier' to add a tab in supplier invoice view
        // 'member'           to add a tab in fundation member view
        // 'opensurveypoll'	  to add a tab in opensurvey poll view
        // 'order'            to add a tab in customer order view
        // 'order_supplier'   to add a tab in supplier order view
        // 'payment'		  to add a tab in payment view
        // 'payment_supplier' to add a tab in supplier payment view
        // 'product'          to add a tab in product view
        // 'propal'           to add a tab in propal view
        // 'project'          to add a tab in project view
        // 'stock'            to add a tab in stock view
        // 'thirdparty'       to add a tab in third party view
        // 'user'             to add a tab in user view

        // Dictionaries
        $this->dictionaries = array();

        // Boxes/Widgets
        // Add here list of php file(s) stored in manufacturingproduction/core/boxes that contains a class to show a widget.
        $this->boxes = array();

        // Cronjobs (List of cron jobs entries to add when module is enabled)
        // unit_frequency must be 60 for minute, 3600 for hour, 86400 for day, 604800 for week
        $this->cronjobs = array();

        // Permissions provided by this module
        $this->rights = array();
        $r = 0;
        // Add here entries to declare new permissions
        /* BEGIN MODULEBUILDER PERMISSIONS */
        $this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 1); // Permission id (must not be already used)
        $this->rights[$r][1] = 'Read objects of ManufacturingProduction'; // Permission label
        $this->rights[$r][4] = 'all';
        $this->rights[$r][5] = 'read'; // In php code, permission will be checked by test if ($user->rights->manufacturingproduction->read)
        $r++;
        $this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 1); // Permission id (must not be already used)
        $this->rights[$r][1] = 'Create/Update objects of ManufacturingProduction'; // Permission label
        $this->rights[$r][4] = 'all';
        $this->rights[$r][5] = 'write'; // In php code, permission will be checked by test if ($user->rights->manufacturingproduction->write)
        $r++;
        $this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 1); // Permission id (must not be already used)
        $this->rights[$r][1] = 'Delete objects of ManufacturingProduction'; // Permission label
        $this->rights[$r][4] = 'all';
        $this->rights[$r][5] = 'delete'; // In php code, permission will be checked by test if ($user->rights->manufacturingproduction->delete)
        $r++;

        $this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 1); // Permission id (must not be already used)
        $this->rights[$r][1] = 'Read objects of ManufacturingProduction Costcenter'; // Permission label
        $this->rights[$r][4] = 'costcenter';
        $this->rights[$r][5] = 'read'; // In php code, permission will be checked by test if ($user->rights->manufacturingproduction->costcenter->read)
        $r++;
        $this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 1); // Permission id (must not be already used)
        $this->rights[$r][1] = 'Create/Update objects of ManufacturingProduction Costcenter'; // Permission label
        $this->rights[$r][4] = 'costcenter';
        $this->rights[$r][5] = 'write'; // In php code, permission will be checked by test if ($user->rights->manufacturingproduction->costcenter->write)
        $r++;
        $this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 1); // Permission id (must not be already used)
        $this->rights[$r][1] = 'Delete objects of ManufacturingProduction Costcenter'; // Permission label
        $this->rights[$r][4] = 'costcenter';
        $this->rights[$r][5] = 'delete'; // In php code, permission will be checked by test if ($user->rights->manufacturingproduction->costcenter->delete)
        $r++;
        $this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 1); // Permission id (must not be already used)
        $this->rights[$r][1] = 'View journal amounts'; // Permission label
        $this->rights[$r][4] = 'journal';
        $this->rights[$r][5] = 'viewamounts'; // In php code, permission will be checked by test if ($user->rights->manufacturingproduction->costcenter->delete)
        $r++;
        
        
        /* END MODULEBUILDER PERMISSIONS */

        // Main menu entries to add
        $this->menu = array();
        $r = 0;
        // Add here entries to declare new menus
        $this->menu[$r++] = array(
            'fk_menu'=>'', // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
            'type'=>'top', // This is a Top menu entry
            'titre'=>'ModuleManufacturingProductionName',
            'prefix' => img_picto('', $this->picto, 'class="paddingright pictofixedwidth valignmiddle"'),
            'mainmenu'=>'manufacturingproduction',
            'leftmenu'=>'',
            'url'=>'/manufacturingproduction/journal_list.php',
            'langs'=>'manufacturingproduction@manufacturingproduction', // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
            'position'=>1000 + $r,
            'enabled'=>'$conf->manufacturingproduction->enabled', // Define condition to show or hide menu entry. Use '$conf->manufacturingproduction->enabled' if entry must be visible if module is enabled.
            'perms'=>'$user->rights->manufacturingproduction->all->read', 
            'target'=>'',
            'user'=>2, // 0=Menu for internal users, 1=external users, 2=both
        );
        
        $this->menu[$r++]=array(
            // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
            'fk_menu'=>'fk_mainmenu=manufacturingproduction',
            // This is a Left menu entry
            'type'=>'left',
            'titre'=>'ListJournal',
            'mainmenu'=>'manufacturingproduction',
            'leftmenu'=>'manufacturingproduction_journal',
            'url'=>'/manufacturingproduction/journal_list.php',
            // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
            'langs'=>'manufacturingproduction@manufacturingproduction',
            'position'=>1100+$r,
            // Define condition to show or hide menu entry. Use '$conf->manufacturingproduction->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
            'enabled'=>'$conf->manufacturingproduction->enabled',
            // Use 'perms'=>'$user->rights->manufacturingproduction->level1->level2' if you want your menu with a permission rules
            'perms'=>'$user->rights->manufacturingproduction->all->read', 
            'target'=>'',
            // 0=Menu for internal users, 1=external users, 2=both
            'user'=>2,
        );
        $this->menu[$r++]=array(
            // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
            'fk_menu'=>'fk_mainmenu=manufacturingproduction,fk_leftmenu=manufacturingproduction_journal',
            // This is a Left menu entry
            'type'=>'left',
            'titre'=>'NewJournal',
            'mainmenu'=>'manufacturingproduction',
            'leftmenu'=>'manufacturingproduction_journal',
            'url'=>'/manufacturingproduction/journal_card.php?action=create',
            // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
            'langs'=>'manufacturingproduction@manufacturingproduction',
            'position'=>1100+$r,
            // Define condition to show or hide menu entry. Use '$conf->manufacturingproduction->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
            'enabled'=>'$conf->manufacturingproduction->enabled',
            // Use 'perms'=>'$user->rights->manufacturingproduction->level1->level2' if you want your menu with a permission rules
            'perms'=>'$user->rights->manufacturingproduction->all->write', 
            'target'=>'',
            // 0=Menu for internal users, 1=external users, 2=both
            'user'=>2
        );
        $this->menu[$r++]=array(
            // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
            'fk_menu'=>'fk_mainmenu=manufacturingproduction',
            // This is a Left menu entry
            'type'=>'left',
            'titre'=>'ListCostCenter',
            'mainmenu'=>'manufacturingproduction',
            'leftmenu'=>'manufacturingproduction_costcenter',
            'url'=>'/manufacturingproduction/costcenter_list.php',
            // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
            'langs'=>'manufacturingproduction@manufacturingproduction',
            'position'=>1100+$r,
            // Define condition to show or hide menu entry. Use '$conf->manufacturingproduction->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
            'enabled'=>'$conf->manufacturingproduction->enabled',
            // Use 'perms'=>'$user->rights->manufacturingproduction->level1->level2' if you want your menu with a permission rules
            'perms'=>'$user->rights->manufacturingproduction->costcenter->read', 
            'target'=>'',
            // 0=Menu for internal users, 1=external users, 2=both
            'user'=>2,
        );
        $this->menu[$r++]=array(
            // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
            'fk_menu'=>'fk_mainmenu=manufacturingproduction,fk_leftmenu=manufacturingproduction_costcenter',
            // This is a Left menu entry
            'type'=>'left',
            'titre'=>'NewCostCenter',
            'mainmenu'=>'manufacturingproduction',
            'leftmenu'=>'manufacturingproduction_costcenter',
            'url'=>'/manufacturingproduction/costcenter_card.php?action=create',
            // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
            'langs'=>'manufacturingproduction@manufacturingproduction',
            'position'=>1100+$r,
            // Define condition to show or hide menu entry. Use '$conf->manufacturingproduction->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
            'enabled'=>'$conf->manufacturingproduction->enabled',
            // Use 'perms'=>'$user->rights->manufacturingproduction->level1->level2' if you want your menu with a permission rules
            'perms'=>'$user->rights->manufacturingproduction->costcenter->write', 
            'target'=>'',
            // 0=Menu for internal users, 1=external users, 2=both
            'user'=>2
        );

        $this->menu[$r++]=array(
            // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
            'fk_menu'=>'fk_mainmenu=manufacturingproduction',
            // This is a Left menu entry
            'type'=>'left',
            'titre'=>'AnalyticalAccounting',
            'mainmenu'=>'manufacturingproduction',
            'leftmenu'=>'manufacturingproduction_costcenter',
            'url'=>'/manufacturingproduction/analitical_accounting.php',
            // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
            'langs'=>'manufacturingproduction@manufacturingproduction',
            'position'=>1100+$r,
            // Define condition to show or hide menu entry. Use '$conf->manufacturingproduction->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
            'enabled'=>'$conf->manufacturingproduction->enabled',
            // Use 'perms'=>'$user->rights->manufacturingproduction->level1->level2' if you want your menu with a permission rules
            'perms'=>'$user->rights->manufacturingproduction->costcenter->read', 
            'target'=>'',
            // 0=Menu for internal users, 1=external users, 2=both
            'user'=>2,
        );
    }

    /**
     *  Function called when module is enabled.
     *  The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
     *  It also creates data directories
     *
     *  @param      string  $options    Options when enabling module ('', 'noboxes')
     *  @return     int             	1 if OK, 0 if KO
     */
    public function init($options = '') {
        global $conf, $langs;

        //$result = $this->_load_tables('/install/mysql/', 'manufacturingproduction');
        $result = $this->_load_tables('/manufacturingproduction/sql/');
        if ($result < 0) {
                return -1; // Do not activate module if error 'not allowed' returned when loading module SQL queries (the _load_table run sql with run_sql with the error allowed parameter set to 'default')
        }

        // Create extrafields during init
        include_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
        $extrafields = new ExtraFields($this->db);
        $result1=$extrafields->addExtraField('materialcostcenter', "MaterialCostCenter", 'sellist', 100,  '', 'bom_bom',   0, 0, '', 'a:1:{s:7:"options";a:1:{s:58:"manufacturingproduction_costcenter:label:rowid::moveable:0";N;}}', 1, '', 1, 'MaterialCostCenterTooltip', '', '0', 'manufacturingproduction@manufacturingproduction', '$conf->manufacturingproduction->enabled');
        $result1=$extrafields->addExtraField('costcenter', "CostCenter", 'sellist', 100,  '', 'user',   0, 0, '', 'a:1:{s:7:"options";a:1:{s:58:"manufacturingproduction_costcenter:label:rowid::moveable:0";N;}}', 1, '', 1, 'MaterialCostCenterTooltip', '', '0', 'manufacturingproduction@manufacturingproduction', '$conf->manufacturingproduction->enabled');

        //$result1=$extrafields->addExtraField('manufacturingproduction_myattr1', "New Attr 1 label", 'boolean', 1,  3, 'thirdparty',   0, 0, '', '', 1, '', 0, 0, '', '', 'manufacturingproduction@manufacturingproduction', '$conf->manufacturingproduction->enabled');
        //$result2=$extrafields->addExtraField('manufacturingproduction_myattr2', "New Attr 2 label", 'varchar', 1, 10, 'project',      0, 0, '', '', 1, '', 0, 0, '', '', 'manufacturingproduction@manufacturingproduction', '$conf->manufacturingproduction->enabled');
        //$result3=$extrafields->addExtraField('manufacturingproduction_myattr3', "New Attr 3 label", 'varchar', 1, 10, 'bank_account', 0, 0, '', '', 1, '', 0, 0, '', '', 'manufacturingproduction@manufacturingproduction', '$conf->manufacturingproduction->enabled');
        //$result4=$extrafields->addExtraField('manufacturingproduction_myattr4', "New Attr 4 label", 'select',  1,  3, 'thirdparty',   0, 1, '', array('options'=>array('code1'=>'Val1','code2'=>'Val2','code3'=>'Val3')), 1,'', 0, 0, '', '', 'manufacturingproduction@manufacturingproduction', '$conf->manufacturingproduction->enabled');
        //$result5=$extrafields->addExtraField('manufacturingproduction_myattr5', "New Attr 5 label", 'text',    1, 10, 'user',         0, 0, '', '', 1, '', 0, 0, '', '', 'manufacturingproduction@manufacturingproduction', '$conf->manufacturingproduction->enabled');

        // Permissions
        $this->remove($options);

        $sql = array();

        // Document templates
        $moduledir = dol_sanitizeFileName('manufacturingproduction');
        $myTmpObjects = array();
        $myTmpObjects['CostCenter'] = array('includerefgeneration'=>0, 'includedocgeneration'=>0);

        foreach ($myTmpObjects as $myTmpObjectKey => $myTmpObjectArray) {
            if ($myTmpObjectKey == 'CostCenter') {
                continue;
            }
        }

        return $this->_init($sql, $options);
    }

    /**
     *  Function called when module is disabled.
     *  Remove from database constants, boxes and permissions from Dolibarr database.
     *  Data directories are not deleted
     *
     *  @param      string	$options    Options when enabling module ('', 'noboxes')
     *  @return     int                 1 if OK, 0 if KO
     */
    public function remove($options = ''){
        $sql = array();
        return $this->_remove($sql, $options);
    }
}
