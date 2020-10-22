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
    	
     	if($action == 'TICKET_ASSIGNED' && !empty($object->fk_user_assign)){
        	global $user, $conf,$langs,$mysoc;
        	
        	$langs->load("other");
        	$langs->load("lltrucks@lltrucks");
	        
        	$application = 'Dolibarr';
        	if (! empty($conf->global->MAIN_APPLICATION_TITLE)) $application = $conf->global->MAIN_APPLICATION_TITLE;
        	$replyto = $user->email;
        	if (empty($user->email)) $replyto = $conf->global->NOTIFY_PLUS_EMAIL_FROM;
        	
        	$subject = '['.$mysoc->name.'] '. $langs->trans("DolibarrNotification") . $langs->trans("tikketassigned");
        	
        	$replyto = $user->email;
        	if (empty($user->email)) $replyto = $conf->global->NOTIFY_PLUS_EMAIL_FROM;
	        
        	
        	
        	dol_include_once('/user/class/user.class.php');
        	$userto = new User($this->db);
        	$userto->fetch($object->fk_user_assign);
        	$sendto = $userto->email;
        	if(empty($sendto)) return 0;
        	
        	
        	
        	$message = '<div class=WordSection1>';
        	$message.= '<p class=MsoNormal>Bonjour,<o:p></o:p></p>';
        	$message.= '<p class=MsoNormal><o:p>&nbsp;</o:p></p>';
        	$message.= '<p class=MsoNormal>Vous recevez ce message car ';
        	$message.= $user->lastname . ' ' . $user->firstname;
        	$message.= 'vous a transféré laresponsabilité d’un ticket d’assistance&nbsp;:';
        	var_dump($message);
        	exit;
        	
        	$message.= $objet->getNomUrl();
        	$message.= '<o:p></o:p></p>';
        	$message.= '<p class=MsoNormal><o:p>&nbsp;</o:p></p>';
        	$message.= $user->signature;
        	
        	
        	
        	
        	
        	require_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
        	$mailfile = new CMailFile(
        			$subject,
        			$sendto,
        			$replyto,
        			$message,
        			$filename_list,
        			$mimetype_list,
        			$mimefilename_list,
        			'',
        			'',
        			0,
        			1,
        			'',
        			'',
        			$trackid,
        			'',
        			'notification' 
        			);
	        
        	if ($mailfile->sendfile()){
        		return 1;
        	}else{
        		return 0;
        	}

        }
   	}
   	
}

	