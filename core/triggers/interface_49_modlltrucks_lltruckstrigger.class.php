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

require_once DOL_DOCUMENT_ROOT.'/core/triggers/dolibarrtriggers.class.php';


class Interfacelltruckstrigger extends DolibarrTriggers
{

    private $db;
    
    
    public $picto = 'email';
    
    public $listofmanagedevents = array(
    		'ORDER_CREATE',
    		'TICKET_MODIFY',
    		'TICKET_ASSIGNED',
    		'TICKET_CLOSE',
    		'TICKET_SENTBYMAIL',
    		'BILL_VALIDATE',
    		'BILL_PAYED',
    		'ORDER_VALIDATE',
    		'PROPAL_VALIDATE',
    		'PROPAL_CLOSE_SIGNED',
    		'FICHINTER_VALIDATE',
    		'FICHINTER_ADD_CONTACT',
    		'ORDER_SUPPLIER_VALIDATE',
    		'ORDER_SUPPLIER_APPROVE',
    		'ORDER_SUPPLIER_REFUSE',
    		'SHIPPING_VALIDATE',
    		'EXPENSE_REPORT_VALIDATE',
    		'EXPENSE_REPORT_APPROVE',
    		'HOLIDAY_VALIDATE',
    		'HOLIDAY_APPROVE'
    );

    /**
     * Constructor
     *
     * 	@param		DoliDB		$db		Database handler
     */
    public function __construct($db)
    {
        $this->db = $db;

        $this->name = preg_replace('/^Interface/i', '', get_class($this));
        $this->family = "demo";
        $this->description = "Triggers of this module are empty functions."
            . "They have no effect."
            . "They are provided for tutorial purpose only.";
        // 'development', 'experimental', 'dolibarr' or version
        $this->version = 'development';
        $this->picto = 'lltrucks@lltrucks';
    }

    /**
     * Trigger name
     *
     * 	@return		string	Name of trigger file
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Trigger description
     *
     * 	@return		string	Description of trigger file
     */
    public function getDesc()
    {
        return $this->description;
    }

    /**
     * Trigger version
     *
     * 	@return		string	Version of trigger file
     */
    public function getVersion()
    {
        global $langs;
        $langs->load("admin");

        if ($this->version == 'development') {
            return $langs->trans("Development");
        } elseif ($this->version == 'experimental')

                return $langs->trans("Experimental");
        elseif ($this->version == 'dolibarr') return DOL_VERSION;
        elseif ($this->version) return $this->version;
        else {
            return $langs->trans("Unknown");
        }
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
    	if (empty($conf->notification->enabled)) return 0; // Module not active, we do nothing
    	
    	require_once DOL_DOCUMENT_ROOT.'/lltrucks/class/notify_plus.class.php';
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
}

	