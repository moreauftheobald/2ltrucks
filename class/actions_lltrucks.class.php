<?php
/* <one line to give the program's name and a brief idea of what it does.>
 * Copyright (C) 2015 ATM Consulting <support@atm-consulting.fr>
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
 * \file	class/actions_lltrucks.class.php
 * \ingroup lltrucks
 * \brief   This file is an example hook overload class file
 *		  Put some comments here
 */

/**
 * Class Actionslltrucks
 */
class Actionslltrucks
{
	/**
	 * @var array Hook results. Propagated to $hookmanager->resArray for later reuse
	 */
	public $results = array();

	/**
	 * @var string String displayed by executeHook() immediately after return
	 */
	public $resprints;

	/**
	 * @var array Errors
	 */
	public $errors = array();

	/**
	 * Constructor
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}

	/**
	 * printCommonFooter
	 *
	 * @param   array()		 $parameters	 Hook metadatas (context, etc...)
	 * @param   CommonObject	&$object		The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string		  &$action		Current action (if set). Generally create or edit or null
	 * @param   HookManager	 $hookmanager	Hook manager propagated to allow calling another hook
	 * @return  int							 < 0 on error, 0 on success, 1 to replace standard code
	 */
	function printCommonFooter($parameters, &$object, &$action, $hookmanager)
	{
		
	}
	
	/**
	* Overloading the doActions function : replacing the parent's function with the one below
	 *
	 * @param array()         $parameters     Hook metadatas (context, etc...)
	 * @param CommonObject $object      The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param string       $action      Current action (if set). Generally create or edit or null
	 * @param HookManager  $hookmanager Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	 public function doActions($parameters, &$object, &$action, $hookmanager)
	 {
	 	//if (in_array('pricesuppliercard', $contextArray ) &&  $conf->entity > 1 && !$user->admin)
	 	//{
	 	//	$object->cost_price = 0;
		//}	 	
	 }
	
	 /**
	 * Overloading the doActions function : replacing the parent's function with the one below
	 *
	 * @param array()         $parameters     Hook metadatas (context, etc...)
	 * @param CommonObject $object      The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param string       $action      Current action (if set). Generally create or edit or null
	 * @param HookManager  $hookmanager Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	 public function addMoreActionsButtons($parameters, &$object, &$action, $hookmanager)
	 {
	 	global $langs, $conf, $user;
	 	
	 	$langs->loadLangs(array('lltrucks@lltrucks'));
	 	
	 	$contextArray = explode(':', $parameters['context']);
	 	
	 	if (in_array('operationordercard', $contextArray ) && empty($action) && $object->objStatus->code == $conf->global->LLTRUCKS_STATUT_BEFORE_CHECK && !$object->array_options['options_orcheck'])
	 	{
	 		$sql = "SELECT rowid ";
	 		$sql.= "FROM llx_operationorder_status ";
	 		$sql.= "WHERE status = 1 " ;
	 		$sql.= "AND entity = " . $object->entity . " ";
	 		$sql.= "AND CODE = '" . $conf->global->LLTRUCKS_STATUT_AFTER_CHECK ."'";
	 		
	 		$resql = $this->db->query($sql);
	 		if ($resql)
	 		{
	 			$obj = $this->db->fetch_object($resql);
	 		}
	 		
	 		
	 		print '<div class="inline-block divButAction"><a href="javascript:orcheck()" class="butAction">' . $langs->trans('orcheck') . '</a></div>';
	 		if(!empty($obj)){
	 			?>
					<script type="text/javascript">
					$(document).ready(function () {
						$('a[href*="action=setStatus&fk_status=<?php print $obj->rowid ?>"].butAction').hide();
					});
					</script>
				<?php
	 		}
			?>
				<script type="text/javascript">
				function orcheck() {
					$div = $('<div id="orcheck"  title="<?php print $langs->trans('orcontrole'); ?>"><iframe width="100%" height="100%" frameborder="0" src="<?php print dol_buildpath('/lltrucks/tpl/orcheck.php?orid=' . $object->id, 1) ; ?>"></iframe></div>');
					$div.dialog({
						modal:true
						,width:"1600px"
						,height:$(window).height() - 200
						,close:function() {document.location.reload(true);}
					});
				};

				</script>
				<?php
		}
		
		if (in_array('pricesuppliercard', $contextArray ))
		{
			
			print 'ox';
			exit;
			
		}
		print 'aie';
		return 0;
	}
	 
	 
	 
	 
	public Function restrictedArea($parameters){
		global $conf, $langs, $user, $db;
		
		$langs->load('ll@lltrucks');
		
		$feature = $parameters['features'];
		
		if($feature = 'fournisseur'){
			$conf->mc->entities['commande_fournisseur'] = $conf->mc->entities['supplier_order'];
		}
		
		return 0;
	}
}
