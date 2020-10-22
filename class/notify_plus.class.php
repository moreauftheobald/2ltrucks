<?php
/* Copyright (C) 2003-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2014	   Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2018 	   Philippe Grand		<philippe.grand@atoo-net.com>
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
 *      \file       htdocs/core/class/notify.class.php
 *      \ingroup    notification
 *      \brief      File of class to manage notifications
 */
require_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';


/**
 *      Class to manage notifications
 */
class Notify_plus
{
	/**
	 * @var int ID
	 */
	public $id;
	public $element = 'notifyplus';

	/**
     * @var DoliDB Database handler.
     */
    public $db;

	/**
	 * @var string Error code (or message)
	 */
	public $error = '';

	/**
	 * @var string[] Error codes (or messages)
	 */
	public $errors = array();

	public $author;
	public $ref;
	public $date;
	public $duree;
	public $note;

	/**
     * @var int Project ID
     */
    public $fk_project;

	// Les codes actions sont definis dans la table llx_notify_def

	// codes actions supported are
	// @todo defined also into interface_50_modNotificiation_Notificiation.class.php
	public $arrayofnotifsupported = array();


	/**
	 *	Constructor
	 *
	 *	@param 		DoliDB		$db		Database handler
	 */
	public function __construct($db)
	{
		global $conf;
		$this->db = $db;
		$txt = str_replace( array( '<br>', '<br />', "\n", "\r" ), array( '', '', '', '' ), $conf->global->NOTIFY_PLUS_EVENT_FETCH);
		$this->arrayofnotifsupported =  explode(',',$txt);
	}


	/**
	 *  Return message that say how many notification (and to which email) will occurs on requested event.
	 *	This is to show confirmation messages before event is recorded.
	 *
	 * 	@param	string	$action		Id of action in llx_c_action_trigger
	 * 	@param	int		$socid		Id of third party
	 *  @param	Object	$object		Object the notification is about
	 *	@return	string				Message
	 */
	public function confirmMessage($action, $socid, $object)
	{
		global $langs;
		$langs->load("mails");

		$listofnotiftodo = $this->getNotificationsArray($action, $socid, $object, 0);

		$nb = -1;
		if (is_array($listofnotiftodo)) $nb = count($listofnotiftodo);
		if ($nb < 0)  $texte = img_object($langs->trans("Notifications"), 'email').' '.$langs->trans("ErrorFailedToGetListOfNotificationsToSend");
		if ($nb == 0) $texte = img_object($langs->trans("Notifications"), 'email').' '.$langs->trans("NoNotificationsWillBeSent");
   		if ($nb == 1) $texte = img_object($langs->trans("Notifications"), 'email').' '.$langs->trans("ANotificationsWillBeSent");
   		if ($nb >= 2) $texte = img_object($langs->trans("Notifications"), 'email').' '.$langs->trans("SomeNotificationsWillBeSent", $nb);

   		if (is_array($listofnotiftodo))
   		{
			$i = 0;
			foreach ($listofnotiftodo as $key => $val)
			{
				if ($i) $texte .= ', ';
				else $texte .= ' (';
				if ($val['isemailvalid']) $texte .= $val['email'];
				else $texte .= $val['emaildesc'];
				$i++;
			}
			if ($i) $texte .= ')';
   		}

		return $texte;
	}

	/**
	 * Return number of notifications activated for action code (and third party)
	 *
	 * @param	string	$notifcode		Code of action in llx_c_action_trigger (new usage) or Id of action in llx_c_action_trigger (old usage)
	 * @param	int		$socid			Id of third party or 0 for all thirdparties or -1 for no thirdparties
	 * @param	Object	$object			Object the notification is about (need it to check threshold value of some notifications)
	 * @param	int		$userid         Id of user or 0 for all users or -1 for no users
	 * @param   array   $scope          Scope where to search
	 * @return	array|int				<0 if KO, array of notifications to send if OK
	 */
	public function getNotificationsArray($notifcode, $socid = 0, $object = null, $userid = 0, $scope = array('thirdparty', 'user', 'global'))
	{
		global $conf, $user;

		$error = 0;
		$resarray = array();

		$valueforthreshold = 0;
		if (is_object($object)) $valueforthreshold = $object->total_ht;

		if ($userid >= 0 && in_array('user', $scope))
		{
			$sql = "SELECT a.code, c.email, c.rowid";
			$sql .= " FROM ".MAIN_DB_PREFIX.$this->element . "_def as n,";
			$sql .= " ".MAIN_DB_PREFIX."user as c,";
			$sql .= " ".MAIN_DB_PREFIX."c_action_trigger as a";
			$sql .= " WHERE n.fk_user = c.rowid";
			$sql .= " AND a.rowid = n.fk_action";
			if ($notifcode)
			{
				if (is_numeric($notifcode)) $sql .= " AND n.fk_action = ".$notifcode; // Old usage
				else $sql .= " AND a.code = '".$notifcode."'"; // New usage
			}
			$sql .= " AND c.entity IN (".getEntity('user').")";
			if ($userid > 0) $sql .= " AND c.rowid = ".$userid;

			dol_syslog(__METHOD__." ".$notifcode.", ".$socid."", LOG_DEBUG);

			$resql = $this->db->query($sql);
			if ($resql)
			{
				$num = $this->db->num_rows($resql);
				$i = 0;
				while ($i < $num)
				{
					$obj = $this->db->fetch_object($resql);
					if ($obj)
					{
						$newval2 = trim($obj->email);
						$isvalid = isValidEmail($newval2);
						if (empty($resarray[$newval2])) $resarray[$newval2] = array('type'=> 'touser', 'code'=>trim($obj->code), 'emaildesc'=>'User id '.$obj->rowid, 'email'=>$newval2, 'userid'=>$obj->rowid, 'isemailvalid'=>$isvalid);
					}
					$i++;
				}
			}else{
				$error++;
				$this->error = $this->db->lasterror();
			}
		}
		if ($error) return -1;
		return $resarray;
	}

	/**
	 *  Check if notification are active for couple action/company.
	 * 	If yes, send mail and save trace into llx_notify.
	 *
	 * 	@param	string	$notifcode			Code of action in llx_c_action_trigger (new usage) or Id of action in llx_c_action_trigger (old usage)
	 * 	@param	Object	$object				Object the notification deals on
	 *	@param 	array	$filename_list		List of files to attach (full path of filename on file system)
	 *	@param 	array	$mimetype_list		List of MIME type of attached files
	 *	@param 	array	$mimefilename_list	List of attached file name in message
	 *	@return	int							<0 if KO, or number of changes if OK
	 */
	public function send($notifcode, $object, $filename_list = array(), $mimetype_list = array(), $mimefilename_list = array())
	{
		global $user, $conf, $langs, $mysoc;
		global $hookmanager;
		global $dolibarr_main_url_root;
		global $action;

		if (!in_array($notifcode, $this->arrayofnotifsupported)) return 0;

		include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
		if (!is_object($hookmanager))
		{
			include_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
			$hookmanager = new HookManager($this->db);
		}
		$hookmanager->initHooks(array('notification'));

		dol_syslog(get_class($this)."::send notifcode=".$notifcode.", object=".$object->id);

		$langs->load("other");
		$langs->load("lltrucks@lltrucks");

		// Define $urlwithroot
		$urlwithouturlroot=preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
		$urlwithroot=$urlwithouturlroot.DOL_URL_ROOT;			// This is to use external domain name found into config file
		//$urlwithroot=DOL_MAIN_URL_ROOT;						// This is to use same domain name than current

		// Define some vars
		$application = 'Dolibarr';
		if (! empty($conf->global->MAIN_APPLICATION_TITLE)) $application = $conf->global->MAIN_APPLICATION_TITLE;
		$replyto = $user->email;
		if (empty($user->email)) $replyto = $conf->global->NOTIFY_PLUS_EMAIL_FROM;
		$object_type = '';
		$link = '';
		$num = 0;
        $error = 0;

		$oldref=(empty($object->oldref)?$object->ref:$object->oldref);
		$newref=(empty($object->newref)?$object->ref:$object->newref);

		$sql = '';

				
		// Check notification per user
		$sql.= "SELECT 'touserid' as type_target, c.email, c.rowid as cid, c.lastname, c.firstname, c.lang as default_lang,";
		$sql.= " a.rowid as adid, a.label, a.code, n.rowid, n.type, n.moreparam ";
		$sql.= " FROM ".MAIN_DB_PREFIX."user as c,";
		$sql.= " ".MAIN_DB_PREFIX."c_action_trigger as a,";
		$sql.= " ".MAIN_DB_PREFIX. "notifyplus_def as n";
		$sql.= " WHERE n.fk_user = c.rowid AND a.rowid = n.fk_action";
		$sql.= " AND c.statut = 1";
		if (is_numeric($notifcode)) $sql.= " AND n.fk_action = ".$notifcode;	// Old usage
		else $sql.= " AND a.code = '".$this->db->escape($notifcode)."'";	// New usage
	
		$result = $this->db->query($sql);
		
		if ($result)
		{
			$num = $this->db->num_rows($result);
			$projtitle='';
			
			if (! empty($object->fk_project))
			{
				require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
				$proj = new Project($this->db);
				$proj->fetch($object->fk_project);
				$projtitle='('.$proj->title.')';
			}

			if ($num > 0)
			{
				$i = 0;
				while ($i < $num && ! $error)	// For each notification couple defined (third party/actioncode)
				{
					
					
					$obj = $this->db->fetch_object($result);
					if(!empty($obj->moreparam)){
						$filter = explode(':',$obj->moreparam);
						switch ($filter[2]) {
							case '__USERID':
								$filter[2] = $user->id;
								break;
							case '__ID__':
								$filter[2] = $object->id;
								break;
							}
							Var_dump($object->$filter[0] . $filter[1]. $filter[2]);
							exit;
							if($object->$filter[0] == $filter[1]) $cont =1;  
					}

					$sendto = dolGetFirstLastname($obj->firstname, $obj->lastname) . " <".$obj->email.">";
					$notifcodedefid = $obj->adid;
					$trackid = '';
					if ($obj->type_target == 'tocontactid') $trackid = 'con'.$obj->id;
					if ($obj->type_target == 'touserid') $trackid = 'use'.$obj->id;

					if (dol_strlen($obj->email))
					{
						// Set output language
						$outputlangs = $langs;
						if ($obj->default_lang && $obj->default_lang != $langs->defaultlang)
						{
							$outputlangs = new Translate('', $conf);
							$outputlangs->setDefaultLang($obj->default_lang);
							$outputlangs->loadLangs(array("main", "other"));
						}

						$subject = '['.$mysoc->name.'] '.$outputlangs->transnoentitiesnoconv("DolibarrNotification").($projtitle ? ' '.$projtitle : '');
						
												
						if(method_exists($object,'getNomUrl')){
							$link=$object->getnomurl();
						}
						
						
						
						$object_type = $object->element;
						$dir_output = $conf->$object_type->dir_output;
						$mesg= $outputlangs->transnoentitiesnoconv("EMailText".$notifcode, $link);
						
						$ref = dol_sanitizeFileName($newref);
						$pdf_path = $dir_output."/".$ref."/".$ref.".pdf";
						if (!dol_is_file($pdf_path))
						{
							// We can't add PDF as it is not generated yet.
							$filepdf = '';
						}
						else
						{
							$filepdf = $pdf_path;
						}

						$message = $outputlangs->transnoentities("YouReceiveMailBecauseOfNotification", $application, $mysoc->name)."\n";
						$message .= $outputlangs->transnoentities("YouReceiveMailBecauseOfNotification2", $application, $mysoc->name)."\n";
						$message .= "\n";
						$message .= $mesg;

						$parameters = array('notifcode'=>$notifcode, 'sendto'=>$sendto, 'replyto'=>$replyto, 'file'=>$filename_list, 'mimefile'=>$mimetype_list, 'filename'=>$mimefilename_list);
						if (!isset($action)) $action = '';

						$reshook = $hookmanager->executeHooks('formatNotificationMessage', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
						if (empty($reshook))
						{
							if (!empty($hookmanager->resArray['subject'])) $subject .= $hookmanager->resArray['subject'];
							if (!empty($hookmanager->resArray['message'])) $message .= $hookmanager->resArray['message'];
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
							-1,
                            '',
                            '',
                            $trackid,
                            '',
                            'notification'
                        );

						if ($mailfile->sendfile())
						{
							if ($obj->type_target == 'touserid') {
	 							$sql = "INSERT INTO ".MAIN_DB_PREFIX.$this->element ." (daten, fk_action, fk_soc, fk_user, type, objet_type, type_target, objet_id, email)";
								$sql .= " VALUES ('".$this->db->idate(dol_now())."', ".$notifcodedefid.", ".($object->socid ? $object->socid : 'null').", ".$obj->cid.", '".$obj->type."', '".$object_type."', '".$obj->type_target."', ".$object->id.", '".$this->db->escape($obj->email)."')";
							}
							else {
								$sql = "INSERT INTO ".MAIN_DB_PREFIX.$this->element ." (daten, fk_action, fk_soc, fk_contact, type, objet_type, type_target, objet_id, email)";
								$sql .= " VALUES ('".$this->db->idate(dol_now())."', ".$notifcodedefid.", ".($object->socid ? $object->socid : 'null').", ".$obj->cid.", '".$obj->type."', '".$object_type."', '".$obj->type_target."', ".$object->id.", '".$this->db->escape($obj->email)."')";
							}
							if (!$this->db->query($sql))
							{
								dol_print_error($this->db);
							}
						}
						else
						{
							$error++;
							$this->errors[] = $mailfile->error;
						}
					}
					else
				    {
						dol_syslog("No notification sent for ".$sendto." because email is empty");
					}
					$i++;
				}
			}
			else
			{
				dol_syslog("No notification to thirdparty sent, nothing into notification setup for the thirdparty socid = ".$object->socid);
			}
		}
		else
		{
	   		$error++;
			$this->errors[] = $this->db->lasterror();
			dol_syslog("Failed to get list of notification to send ".$this->db->lasterror(), LOG_ERR);
	   		return -1;
		}

		
		if (!$error) return $num;
		else return -1 * $error;
	}
}
