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

require_once DOL_DOCUMENT_ROOT.'/core/triggers/dolibarrtriggers.class.php';



/**
 * 	\file		core/triggers/interface_99_modMyodule_CliTheobaldtrigger.class.php
 * 	\ingroup	clitheobald
 * 	\brief		Sample trigger
 * 	\remarks	You can create other triggers by copying this one
 * 				- File name should be either:
 * 					interface_99_modClitheobald_Mytrigger.class.php
 * 					interface_99_all_Mytrigger.class.php
 * 				- The file must stay in core/triggers
 * 				- The class name must be InterfaceMytrigger
 * 				- The constructor method must be named InterfaceMytrigger
 * 				- The name property name must be Mytrigger
 */

/**
 * Trigger class
 */

class Interfacelltruckstrigger
{

    private $db;
    
    
    public $picto = 'email';
    
    public $listofmanagedevents = array();

    /**
     * Constructor
     *
     * 	@param		DoliDB		$db		Database handler
     */
    public function __construct($db)
    {
    	global $conf;
        $this->db = $db;
        $this->listofmanagedevents = explode(',',$conf->global->NOTIFY_PLUS_EVENT_FETCH);
        print_r($this->listofmanagedevents);
        exit;
        $this->name = preg_replace('/^Interface/i', '', get_class($this));
        $this->picto = 'lltrucks@lltrucks';
    }

    /**
	 * Function called when a Dolibarrr business event is done.
	 * All functions "run_trigger" are triggered if file is inside directory htdocs/core/triggers
	 *
	 * @param string $action code
	 * @param Object $object
	 * @param User $user user
	 * @param Translate $langs langs
	 * @param conf $conf conf
	 * @return int <0 if KO, 0 if no triggered ran, >0 if OK
	 */
	function runTrigger($action, $object, $user, $langs, $conf) {
		//For 8.0 remove warning
		$result=$this->run_trigger($action, $object, $user, $langs, $conf);
		return $result;
	}


    /**
     * Function called when a Dolibarrr business event is done.
     * All functions "run_trigger" are triggered if file
     * is inside directory core/triggers
     *
     * 	@param		string		$action		Event action code
     * 	@param		Object		$object		Object
     * 	@param		User		$user		Object user
     * 	@param		Translate	$langs		Object langs
     * 	@param		conf		$conf		Object conf
     * 	@return		int						<0 if KO, 0 if no triggered ran, >0 if OK
     */
    public function run_trigger($action, $object, $user, $langs, $conf)
    {
        // Put here code you want to execute when a Dolibarr business events occurs.
        // Data and type of action are stored into $object and $action
        // Users
    	   	
    	dol_include_once('lltrucks/class/notify_plus.class.php');
    	$notify = new Notify_plus($this->db);
    	if (!in_array($action, $notify->arrayofnotifsupported)) return 0;
    	dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
    	$notify->send($action, $object);
    	
    	
    	

        if($action == 'SUPPLIER_PRODUCT_BUYPRICE_UPDATE' && $conf->entity == 1){
        	global $user, $newprice;
	        // THEO_PRICE_MAJORATION_PERCENT_ON_PRODUCT_PRICE_MODIFY
	        	        
	        dol_include_once('/product/class/product.class.php');
	        $produit = new Product($object->db);
	        $res = $produit->fetch($object->fk_product);
	        if($res>0){
	        	$price = $newprice/($conf->global->LLTRUCKS_PRICE_COEF/100);
	        	$produit->updatePrice($price, 'HT', $user);
	        	$produit->call_trigger('PRODUCT_PRICE_MODIFY', $user);
	        }
	        	
	        
	        
			return 1;
        }
   	}
   	
   	public function getListOfManagedEvents()
   	{
   		global $conf;
   		
   		$ret = array();
   		
   		$sql = "SELECT rowid, code, label, description, elementtype";
   		$sql .= " FROM ".MAIN_DB_PREFIX."c_action_trigger";
   		$sql .= $this->db->order("rang, elementtype, code");
   		dol_syslog("getListOfManagedEvents Get list of notifications", LOG_DEBUG);
   		$resql = $this->db->query($sql);
   		if ($resql)
   		{
   			$num = $this->db->num_rows($resql);
   			$i = 0;
   			while ($i < $num)
   			{
   				$obj = $this->db->fetch_object($resql);
   				
   				$qualified = 0;
   				// Check is this event is supported by notification module
   				if (in_array($obj->code, $this->listofmanagedevents)) $qualified = 1;
   				// Check if module for this event is active
   				if ($qualified)
   				{
   					//print 'xx'.$obj->code;
   					$element = $obj->elementtype;
   					
   					// Exclude events if related module is disabled
   					if ($element == 'order_supplier' && empty($conf->fournisseur->enabled)) $qualified = 0;
   					elseif ($element == 'invoice_supplier' && empty($conf->fournisseur->enabled)) $qualified = 0;
   					elseif ($element == 'withdraw' && empty($conf->prelevement->enabled)) $qualified = 0;
   					elseif ($element == 'shipping' && empty($conf->expedition->enabled)) $qualified = 0;
   					elseif ($element == 'member' && empty($conf->adherent->enabled)) $qualified = 0;
   					elseif ($element == 'ticket' && empty($conf->ticket->enabled)) $qualified = 0;
   					elseif (!in_array($element, array('order_supplier', 'invoice_supplier', 'withdraw', 'shipping', 'member', 'expensereport')) && empty($conf->$element->enabled)) $qualified = 0;
   				}
   				
   				if ($qualified)
   				{
   					$ret[] = array('rowid'=>$obj->rowid, 'code'=>$obj->code, 'label'=>$obj->label, 'description'=>$obj->description, 'elementtype'=>$obj->elementtype);
   				}
   				
   				$i++;
   			}
   		}
   		else dol_print_error($this->db);
   		
   		return $ret;
   	}
}

	