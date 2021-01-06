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
        
         if($action == 'OPERATIONORDERDET_DELETE'){
            global $user;
            if($object->product_type == 0){
                dol_include_once('operationorder/class/operationorder.class.php');
                $operation_order = new OperationOrder($object->db);
                $operation_order->fetch($object->fk_operation_order);
                $TLineQtyUsed = $operation_order->getAlreadyUsedQtyLines();
                $TLastLinesByProduct = $operation_order->getLastLinesByProduct();
                $qtyUsed = $object->getQtyUsed($TLineQtyUsed, $TLastLinesByProduct);
                if($qtyUsed<>0){
                    dol_include_once('/product/stock/class/mouvementstock.class.php');
                    $mvt = new MouvementStock($object->db);
                    $mvt->origin =  $operation_order;
                    $result = $mvt->reception(
                        $user,
                        $object->fk_product,
                        $object->fk_warehouse,
                        $qtyUsed,
                        0,
                        'Suppression ligne OR ' . $operation_order->ref
                        );
                }
            }
         }
    	    	    	   	    	    	
     	if($action == 'SUPPLIER_PRODUCT_BUYPRICE_UPDATE' && $conf->entity == 1){
     		global $newprice;
     		     		
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
        	global $mysoc;
        	
        	//require '../htdocs/main.inc.php';
        	require_once DOL_DOCUMENT_ROOT. '/core/class/CMailFile.class.php';
        	require_once DOL_DOCUMENT_ROOT. '/core/lib/files.lib.php';
        	require_once DOL_DOCUMENT_ROOT. '/user/class/user.class.php';
        	        	
        	$langs->load("other");
        	$langs->load("lltrucks@lltrucks");
	        
        	$application = 'Dolibarr';
        	if (! empty($conf->global->MAIN_APPLICATION_TITLE)) $application = $conf->global->MAIN_APPLICATION_TITLE;
        	$replyto = $user->email;
        	if (empty($user->email)) $replyto = $conf->global->NOTIFY_PLUS_EMAIL_FROM;
        	
        	$subject = '['.$mysoc->name.'] '. $langs->trans("DolibarrNotification") . $langs->trans("tikketassigned");
        	       	
        	$userto = new User($this->db);
        	$userto->fetch($object->fk_user_assign);
        	$sendto = $userto->email;
        	if(empty($sendto)) return 0;
        	       	    	
        	$message = '<div class=WordSection1>';
        	$message.= '<p class=MsoNormal>Bonjour,<o:p></o:p></p>';
        	$message.= '<p class=MsoNormal><o:p>&nbsp;</o:p></p>';
        	$message.= '<p class=MsoNormal>Vous recevez ce message car&nbsp ';
        	$message.=  $user->firstname . ' ' . $user->lastname;
        	$message.= ' vous a transféré la responsabilité d’un ticket d’assistance&nbsp;:';
        	$message.= DOL_URL_ROOT . $object->getNomUrl();
        	$message.= '<o:p></o:p></p>';
        	$message.= '<p class=MsoNormal><o:p>&nbsp;</o:p></p>';
        	$message.= $user->signature;
        	
         	$filename_list = array();
         	$mimefilename_list= array();
         	$mimetype_list = array();
        	
        	$upload_dir = $upload_dir = DOL_DATA_ROOT . "/ticket/".dol_sanitizeFileName($object->ref);
        	$filearray = dol_dir_list($upload_dir, "files", 0, '', '\.meta$', $sortfield, (strtolower($sortorder) == 'desc' ? SORT_DESC : SORT_ASC), 1);
        	foreach ($filearray as $file){
        		$filename_list[] = $file['fullname'];
        		$mimefilename_list[] = $file['name'];
        	}
        	        	
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
        	include_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
        	if ($mailfile->sendfile()){
        		return 1;
        	}else{
        		return 0;
        	}

        }

        if($action == 'ORDER_CREATE' && $object->lines[0]->origin == 'supplierorderdet'){
   			global $mysoc;
   			
   			$extrafields = new ExtraFields($this->db);
   			   			
   			dol_include_once('/core/class/CMailFile.class.php');
   			dol_include_once('/core/lib/files.lib.php');
   			dol_include_once('/user/class/user.class.php');
   			   			
   			$langs->load("other");
   			$langs->load("lltrucks@lltrucks");
   		
   			$application = 'Dolibarr';
   			if (! empty($conf->global->MAIN_APPLICATION_TITLE)) $application = $conf->global->MAIN_APPLICATION_TITLE;
   			$replyto = $user->email;
   			if (empty($user->email)) $replyto = $conf->global->NOTIFY_PLUS_EMAIL_FROM;
   		
   			$subject = '['.$mysoc->name.'] '. $langs->trans("DolibarrNotification") . $langs->trans("ordercreated");
			
   			$sql = 'SELECT ef.fk_user_ticket FROM ' . MAIN_DB_PREFIX . 'entity AS e INNER JOIN ' . MAIN_DB_PREFIX . 'entity_extrafields as ef on ef.fk_object = e.rowid WHERE e.rowid = 1';
   			
   			$res = $this->db->query($sql);
   			$obj = $this->db->fetch_object($res);
   			  			
   		 	$userto = new User($this->db);
   		 	$userto->fetch($obj->fk_user_ticket);
   			$sendto = $userto->email;
   			if(empty($sendto)) return 0;
			
   			$message = '<div class=WordSection1>';
   			$message.= '<p class=MsoNormal>Bonjour,<o:p></o:p></p>';
   			$message.= '<p class=MsoNormal><o:p>&nbsp;</o:p></p>';
	   		$message.= '<p class=MsoNormal>Vous recevez ce message car&nbsp ';
   			$message.=  $user->firstname . ' ' . $user->lastname;
   			$message.= ' Viens de créer une commande client a votre intention&nbsp;:';
	   		$message.= DOL_URL_ROOT . $object->getNomUrl();
	   		$message.= '<o:p></o:p></p>';
	   		$message.= '<p class=MsoNormal><o:p>&nbsp;</o:p></p>';
	   		   		   		
	   		$message.= $user->signature;
	   		$message = dol_nl2br($message);
   			   		
   			$filename_list = array();
   			$mimefilename_list= array();
   			$mimetype_list = array();
   		   		
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
   		
   		if(($action == 'OPERATIONORDERDET_CREATE'||$action == 'OPERATIONORDERDET_MODIFY'||$action == 'OPERATIONORDERDET_DELETE'||$action == 'OPERATIONORDER_MODIFY') && $object->array_options['options_orcheck'] == 1){
   			   			
   			$sql = "UPDATE " .  MAIN_DB_PREFIX . "operationorder_extrafields SET orcheck = 0 WHERE fk_object = " . $object->id;
   			$this->db->query($sql);
   			   			   			
   			return 1;
   		}
   		
   		if($action == 'TICKET_CREATE' && empty($conf->global->TICKET_DISABLE_CUSTOMER_MAILS) && empty($object->context['disableticketemail']) && $object->notify_tiers_at_create){
   		    
   		    global $mysoc;
   		    $contactid =  GETPOST('contactid', 'int');
   		    $extrafields = new ExtraFields($this->db);
   		    $object->context['disableticketemail'] = 1;
   		    $object->loadCacheTypesTickets();
   		    foreach ($object->cache_types_tickets as $key=>$code){
   		        if($code['code'] == $object->type_code) $type_label =$code['label'];
   		    }
   		    $object->loadCacheSeveritiesTickets();
   		    foreach ($object->cache_severity_tickets as $key=>$type){
   		        if($type['code'] == $object->severity_code) $severity_label =$type['label'];
   		    }
   		    $extrafields->fetch_name_optionals_label($object->table_element);
   		     		    
   		    if(!empty($contactid)){
   		        
   		        require_once DOL_DOCUMENT_ROOT. '/contact/class/contact.class.php';
       		    require_once DOL_DOCUMENT_ROOT. '/core/class/CMailFile.class.php';
       		    require_once DOL_DOCUMENT_ROOT. '/core/lib/files.lib.php';
      		    require_once DOL_DOCUMENT_ROOT. '/user/class/user.class.php';
      		    
      		    $langs->load("ticket");
       		    $langs->load("other");
      		    $langs->load("lltrucks@lltrucks");
   		    
       		    $application = 'Dolibarr';
       		    if (! empty($conf->global->MAIN_APPLICATION_TITLE)) $application = $conf->global->MAIN_APPLICATION_TITLE;
      		    $replyto = $user->email;
       		    if (empty($user->email)) $replyto = $conf->global->NOTIFY_PLUS_EMAIL_FROM;
   		        
       		    $subject = '['.$mysoc->name.'][' . $object->severity_code . '] '.$langs->transnoentities('TicketNewEmailSubject', $object->ref, $object->track_id);
   		    
   		   
       		    $userto = new Contact($this->db);
     		    $userto->fetch($contactid);
      		    $sendto = $userto->email;
      		    
       		    if(empty($sendto)) return 0;
       		    $url_public_ticket = ($conf->global->TICKET_URL_PUBLIC_INTERFACE ? $conf->global->TICKET_URL_PUBLIC_INTERFACE.'/view.php' : dol_buildpath('/public/ticket/view.php', 2));
       		    $url_public_ticket.= '?track_id='.$object->track_id . '&email=' . $userto->email . '&action=view_ticket';
       		    $message.= '<p class=MsoNormal>Bonjour,<o:p></o:p></p>';
      		    $message.= '<p class=MsoNormal>Vous recevez ce message car&nbsp ';
      		    $message.=  $user->firstname . ' ' . $user->lastname;
      		    $message.= ' a créé un ticket d’assistance vous concernant&nbsp;';
      		    $message.= ' Vous pouvez le consulter en suivant le lien suivant:';
      		    $message.= $url_public_ticket;
       		    $message.= '</p>';
       		    $message .= '<ul><li>'.$langs->trans('Title').' : '.$object->subject.'</li>';
       		    $message .= '<li>'.$langs->trans('Type').' : '.$type_label.'</li>';
       		    $message .= '<li>'.$langs->trans('Severity').' : '.$severity_label.'</li>';
       		    $message .= '<li>'.$langs->trans('Vehicule').' : '.$extrafields->showOutputField('fk_vehicule', $object->array_options['options_fk_vehicule']).'</li>';
       		    $message .= '<li>'.$langs->trans('Atelier').' : '.$extrafields->showOutputField('fk_entity', $object->array_options['options_fk_entity']).'</li>';
       		    $message .= '<li>'.$langs->trans('Dispo du').' : '.$extrafields->showOutputField('date_d', $object->array_options['options_date_d']);
       		    $message .= ' au :' . $extrafields->showOutputField('date_f', $object->array_options['options_date_f']) . '</li>';
       		    $message .= '</ul>';
       		    $message .= '<p>'.$langs->trans('Message').' : <br>'.$object->message.'</p>';
      		    $message.= $user->signature;
   		        $message = dol_nl2br($message);
      		    
       		    $filename_list = array();
       		    $mimefilename_list= array();
       		    $mimetype_list = array();
   		        
      		    $upload_dir = $upload_dir = DOL_DATA_ROOT . "/ticket/".dol_sanitizeFileName($object->ref);
       		    $filearray = dol_dir_list($upload_dir, "files", 0, '', '\.meta$', $sortfield, (strtolower($sortorder) == 'desc' ? SORT_DESC : SORT_ASC), 1);
       		    foreach ($filearray as $file){
       		        $filename_list[] = $file['fullname'];
      		        $mimefilename_list[] = $file['name'];
       		    }
   		    
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
       		        
       		        $sql = "SELECT fk_socpeople, c.email ";
       		        $sql.= "FROM " . MAIN_DB_PREFIX . "societe_contacts AS sc ";
       		        $sql.= "INNER JOIN " . MAIN_DB_PREFIX . "socpeople AS c ON c.rowid = sc.fk_socpeople ";
       		        $sql.= "WHERE sc.fk_soc =" .  $object->fk_soc . " ";
       		        $sql.= "AND sc.fk_c_type_contact = 157 ";
       		        $sql.= "AND c.statut = 1 ";
       		        $sql.= "AND c.email IS NOT NULL ";
       		        $sql.= "AND c.no_email =0";
       		        
       		        $res = $object->db->query($sql);
       		        if($res){
      		            while($obj = $this->db->fetch_object($res)){
       		                $sendto = $obj->email;
       		                
       		                if(empty($sendto)) return 0;
       		                $url_public_ticket= '';
       		                if(!empty($conf->global->EACCESS_ROOT_URL))
       		                {
       		                    $url_public_ticket= $conf->global->EACCESS_ROOT_URL;
       		                    if(substr($this->rootUrl, -1) !== '/') $this->rootUrl .= '/';
       		                }
       		                else
       		                {
       		                    $url_public_ticket = dol_buildpath('/externalaccess/www/',2);
       		                }
       		                
       		                $url_public_ticket.= '?controller=tickets&track_id='.$object->track_id ;
       		                $message = '<div class=WordSection1>';
       		                $message.= '<p class=MsoNormal>Bonjour,<o:p></o:p></p>';
       		                $message.= '<p class=MsoNormal>Vous recevez ce message car&nbsp ';
       		                $message.=  $user->firstname . ' ' . $user->lastname;
       		                $message.= ' a créé un ticket d’assistance vous concernant&nbsp;';
       		                $message.= ' Vous pouvez le consulter en suivant le lien suivant:';
       		                $message.= $url_public_ticket;
       		                $message.= '</p>';       		                
       		                $message .= '<ul><li>'.$langs->trans('Title').' : '.$object->subject.'</li>';
       		                $message .= '<li>'.$langs->trans('Type').' : '.$type_label.'</li>';
       		                $message .= '<li>'.$langs->trans('Severity').' : '.$severity_label.'</li>';
       		                $message .= '<li>'.$langs->trans('Vehicule').' : '.$extrafields->showOutputField('fk_vehicule', $object->array_options['options_fk_vehicule']).'</li>';
       		                $message .= '<li>'.$langs->trans('Atelier').' : '.$extrafields->showOutputField('fk_entity', $object->array_options['options_fk_entity']).'</li>';
       		                $message .= '<li>'.$langs->trans('Dispo du').' : '.$extrafields->showOutputField('date_d', $object->array_options['options_date_d']);
       		                $message .= ' au :' . $extrafields->showOutputField('date_f', $object->array_options['options_date_f']) . '</li>';
       		                $message .= '</ul>';
       		                $message .= '<p>'.$langs->trans('Message').' : <br>'.$object->message.'</p>';
       		                $message.= $user->signature;
       		                
       		                $filename_list = array();
       		                $mimefilename_list= array();
       		                $mimetype_list = array();
       		                
       		                $upload_dir = $upload_dir = DOL_DATA_ROOT . "/ticket/".dol_sanitizeFileName($object->ref);
       		                $filearray = dol_dir_list($upload_dir, "files", 0, '', '\.meta$', $sortfield, (strtolower($sortorder) == 'desc' ? SORT_DESC : SORT_ASC), 1);
       		                foreach ($filearray as $file){
       		                    $filename_list[] = $file['fullname'];
       		                    $mimefilename_list[] = $file['name'];
       		                }
       		                
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
       		                }
       		            }
       		        }else{
       		            return 0;
       		        }
       		        
       		        return 1;
       		    }else{
       		       
       		        return 0;
      		    }
   		    }
   		}
	}
}

	