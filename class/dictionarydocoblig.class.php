<?php
/* Copyright (C) 2020 ATM Consulting <support@atm-consulting.fr>
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

if (!class_exists('SeedObject'))
{
	/**
	 * Needed if $form->showLinkedObjectBlock() is call or for session timeout on our module page
	 */
	define('INC_FROM_DOLIBARR', true);
	require_once dirname(__FILE__).'/../config.php';
}

class dictionarydocobliglist extends SeedObject
{
	public $table_element = 'docoblig_list';
	
	public $element = 'docoblig_list';
	
	public $fields = array(
	        'doccode' => array('type'=>'varchar(30)', 'label'=>'code', 'enabled'=>1, 'position'=>10, 'notnull'=>0, 'visible'=>1),
	        'doclabel' => array('type'=>'varchar(255)', 'label'=>'label', 'enabled'=>1, 'position'=>20, 'notnull'=>0, 'visible'=>1,'showoncombobox' => 1),
			'fk_product' => array ('type' => 'integer:Product:product/class/product.class.php:1','required' => 1,'label' => 'Product',	'enabled' => 1,'position' => 35,'notnull' => -1,'visible' => -1,'index' => 1),
	        'docactive' =>  array('type'=>'int', 'label'=>'active', 'enabled'=>1, 'position'=>1000, 'notnull'=>1, 'visible'=>1, 'arrayofkeyval'=> array(0 => 'Inactive',1 => 'active')),
	);
	
	/**
	 * OperationOrderDet constructor.
	 * @param DoliDB    $db    Database connector
	 */
	public function __construct($db)
	{
		$this->db = $db;
		
		$this->init();
	}
	
	public function getNomUrl($getnomurlparam = '')
	{
		return $this->doclabel;
	}
	
	public function showOutputFieldQuick($key, $moreparam = '', $keysuffix = '', $keyprefix = '', $morecss = ''){
	    return $this->showOutputField($this->fields[$key], $key, $this->{$key}, $moreparam, $keysuffix, $keyprefix, $morecss);
	}
	
	public function showInputField($val, $key, $value, $moreparam = '', $keysuffix = '', $keyprefix = '', $morecss = 0, $nonewbutton = 0)
	{
	    global $user;
	    if ($key == 'time_planned_f')
	    {
	        $out = '<input  name="'.$keyprefix.$key.$keysuffix.'" id="'.$keyprefix.$key.$keysuffix.'" value="'.convertSecondToTime($value).'" >';
	    }
	    else
	    {
	        if ($key == 'fk_c_operationorder_type') {
	            $nonewbutton=!($user->admin);
	        }
	        if ($key == 'fk_soc') {
	            $nonewbutton=!($user->rights->societe->creer);
	        }
	        $out = parent::showInputField($val, $key, $value, $moreparam, $keysuffix, $keyprefix, $morecss, $nonewbutton);
	    }
	    
	    return $out;
	}
}

class dictionarydocobligzone extends SeedObject
{
	public $table_element = 'docoblig_zone';
	
	public $element = 'docoblig_zone';
	
	public $fields = array(
	        'zonelabel' => array('type'=>'varchar(255)', 'label'=>'label', 'enabled'=>1, 'position'=>20, 'notnull'=>0, 'visible'=>1,'showoncombobox' => 1),
			'fk_c_docoblig_list' => array('type'=>'integer:dictionarydocobliglist:/lltrucks/class/dictionarydocoblig.class.php:1:active =1)', 'label'=>'DocobligList', 'enabled'=>1, 'position'=>90, 'visible'=>1,),
	        'zonetype' =>  array('type'=>'int', 'label'=>'type', 'enabled'=>1, 'position'=>1000, 'notnull'=>1, 'visible'=>1, 'arrayofkeyval'=> array(0 => 'Zone Fixe',1 => 'Zone de liste des ligne OR',2=>'Zone de saisie libre'),'showoncombobox' => 1),
	        'zonepage' =>array('type'=>'integer', 'label'=>'page', 'enabled'=>1, 'position'=>10, 'notnull'=>0, 'visible'=>1),
	        'zoneposx' => array('type'=>'real', 'label'=>'posx', 'enabled'=>1, 'position'=>10, 'notnull'=>0, 'visible'=>1),
	        'zoneposy' => array('type'=>'real', 'label'=>'posy', 'enabled'=>1, 'position'=>10, 'notnull'=>0, 'visible'=>1),
	        'zonestep' => array('type'=>'real', 'label'=>'step', 'enabled'=>1, 'position'=>10, 'notnull'=>0, 'visible'=>1),
	        'zonesize' => array('type'=>'real', 'label'=>'size', 'enabled'=>1, 'position'=>10, 'notnull'=>0, 'visible'=>1),
	        'zoneactive' =>  array('type'=>'int', 'label'=>'active', 'enabled'=>1, 'position'=>1000, 'notnull'=>1, 'visible'=>1, 'arrayofkeyval'=> array(0 => 'Inactive',1 => 'active'))
	);
	
	/**
	 * OperationOrderDet constructor.
	 * @param DoliDB    $db    Database connector
	 */
	public function __construct($db)
	{
		$this->db = $db;
		
		$this->init();
	}
	
	public function getNomUrl($getnomurlparam = '')
	{
		return $this->zonelabel;
	}
	
	public function showOutputFieldQuick($key, $moreparam = '', $keysuffix = '', $keyprefix = '', $morecss = ''){
	    return $this->showOutputField($this->fields[$key], $key, $this->{$key}, $moreparam, $keysuffix, $keyprefix, $morecss);
	}
	
	public function showInputField($val, $key, $value, $moreparam = '', $keysuffix = '', $keyprefix = '', $morecss = 0, $nonewbutton = 0)
	{
	    global $user;
	    if ($key == 'time_planned_f')
	    {
	        $out = '<input  name="'.$keyprefix.$key.$keysuffix.'" id="'.$keyprefix.$key.$keysuffix.'" value="'.convertSecondToTime($value).'" >';
	    }
	    else
	    {
	        if ($key == 'fk_c_operationorder_type') {
	            $nonewbutton=!($user->admin);
	        }
	        if ($key == 'fk_soc') {
	            $nonewbutton=!($user->rights->societe->creer);
	        }
	        $out = parent::showInputField($val, $key, $value, $moreparam, $keysuffix, $keyprefix, $morecss, $nonewbutton);
	    }
	    
	    return $out;
	}
}

class dictionarydocobligvalue extends SeedObject
{
	public $table_element = 'docoblig_value';
	
	public $element = 'docoblig_value';
	
	public $fields = array(
	        'fk_c_docoblig_zone' => array('type'=>'integer:dictionarydocobligzone:/lltrucks/class/dictionarydocoblig.class.php:1:active=1)', 'label'=>'DocobligZone', 'enabled'=>1, 'position'=>90, 'visible'=>1,'notnull'=>1),
	        'valtype' =>  array('type'=>'int', 'label'=>'type', 'enabled'=>1, 'position'=>1000, 'notnull'=>1, 'visible'=>1, 'arrayofkeyval'=> array(0 => 'Calculée',1 => 'texte Fixe',2=>'traduction',3=>'Oui / non'),'showoncombobox' => 1),
            'valposx' => array('type'=>'real', 'label'=>'posx', 'enabled'=>1, 'position'=>10, 'notnull'=>1, 'visible'=>1),
	        'valposy' => array('type'=>'real', 'label'=>'posy', 'enabled'=>1, 'position'=>10, 'notnull'=>1, 'visible'=>1),
	        'value' => array('type'=>'varchar(255)', 'label'=>'valiue', 'enabled'=>1, 'position'=>20, 'notnull'=>0, 'visible'=>1,'showoncombobox' => 1),
	        'valmax_len'=>array('type'=>'integer', 'label'=>'maxlen', 'enabled'=>1, 'position'=>10, 'notnull'=>1, 'visible'=>1),
	        'vallon' => array('type'=>'integer', 'label'=>'lon', 'enabled'=>1, 'position'=>10, 'notnull'=>1, 'visible'=>1),
	        'valactive' =>  array('type'=>'int', 'label'=>'active', 'enabled'=>1, 'position'=>1000, 'notnull'=>1, 'visible'=>1, 'arrayofkeyval'=> array(0 => 'Inactive',1 => 'active'))
	);
	
	/**
	 * OperationOrderDet constructor.
	 * @param DoliDB    $db    Database connector
	 */
	public function __construct($db)
	{
		$this->db = $db;
		
		$this->init();
	}
	
	public function getNomUrl($getnomurlparam = '')
	{
		return $this->label;
	}
	
	public function showOutputFieldQuick($key, $moreparam = '', $keysuffix = '', $keyprefix = '', $morecss = ''){
	    return $this->showOutputField($this->fields[$key], $key, $this->{$key}, $moreparam, $keysuffix, $keyprefix, $morecss);
	}
	
	public function showInputField($val, $key, $value, $moreparam = '', $keysuffix = '', $keyprefix = '', $morecss = 0, $nonewbutton = 0)
	{
	    global $user;
	    if ($key == 'time_planned_f')
	    {
	        $out = '<input  name="'.$keyprefix.$key.$keysuffix.'" id="'.$keyprefix.$key.$keysuffix.'" value="'.convertSecondToTime($value).'" >';
	    }
	    else
	    {
	        if ($key == 'fk_c_operationorder_type') {
	            $nonewbutton=!($user->admin);
	        }
	        if ($key == 'fk_soc') {
	            $nonewbutton=!($user->rights->societe->creer);
	        }
	        $out = parent::showInputField($val, $key, $value, $moreparam, $keysuffix, $keyprefix, $morecss, $nonewbutton);
	    }
	    
	    return $out;
	}
}
