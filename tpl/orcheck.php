<?php
require '../config.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
dol_include_once('operationorder/class/operationorder.class.php');
dol_include_once('operationorder/class/operationorderaction.class.php');
dol_include_once('operationorder/lib/operationorder.lib.php');

global $db, $conf, $langs, $user;

$langs->load("lltrucks@lltrucks");

$orid = GETPOST('orid', 'int');
$action = GETPOST('action', 'alpha');
$lineid=GETPOST('lineid');
$qty=GETPOST('fact');
if ($action == 'cancel')
{
	?>
	<script type="text/javascript">
		$(document).ready(function () {
			window.parent.$('#orcheck').dialog('close');
			window.parent.$('#orcheck').remove();
		});
	</script>
	<?php
}
$lineupdated = new operationorderdet($db);
$object = new OperationOrder($db);

$res=0;
if(!empty($orid)){
	$res= $object->fetch($orid, true);
}
if($res>0){
	if(!empty($lineid)){
		$lineupdated->fetch($lineid,true);
	}
	
	if ($action == 'editline' && is_object($lineupdated))
	{
		$result = $object->updateline(
				$lineid, 
				$lineupdated->description, 
				$qty, 
				$lineupdated->price, 
				$lineupdated->fk_warehouse, 
				$lineupdated->pc,
				$lineupdated->time_planned, 
				$lineupdated->time_spent,
				$lineupdated->fk_product, 
				$lineupdated->info_bits, 
				$lineupdated->date_start, 
				$lineupdated->date_end, 
				$lineupdated->type, 
				$lineupdated->fk_parent_line, 
				$lineupdated->product->label, 
				$lineupdated->special_code, 
				$lineupdated->array_options);
			
			if ($result >= 0) {
												
				unset($_POST['fact']);
				unset($_POST['action']);
				unset($_POST['lineid']);
								
				
			} else {
				setEventMessages($object->error, $object->errors, 'errors');
			}
	}elseif($action== 'deletline' && is_object($lineupdated)){
						
			$result = $object->removeChild($user, 'OperationOrderDet', $lineupdated->fk_parent_line);
			if ($result)
			{
				if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) {
					$ret = $object->fetch($object->id); // Reload to get new records
					$object->generateDocument($object->modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
				}
				$object->setTimePlannedT();
						
				header('Location: '.$_SERVER["PHP_SELF"].'?orid='.$object->id);
				exit;
			}
			else
			{
				setEventMessages($object->error, $object->errors, 'errors');
			}
	}elseif ($action=='debit-stock' && is_object($lineupdated)){
		dol_include_once('/operationorder/class/operationorder.class.php');
		require_once DOL_DOCUMENT_ROOT . '/product/stock/class/mouvementstock.class.php';
				
		$mvt = new MouvementStock($db);
		$mvt->origin = $object;
		
		if(GETPOST("debit") >0){
			$result = $mvt->livraison(
					$user,
					$lineupdated->product->id,
					$lineupdated->fk_warehouse,
					abs(GETPOST("debit")),
					0,
					$langs->trans('productUsedForOorder', $object->ref) . 'Debit manuel'
					);
			
			
		}else{
			$result = $mvt->reception(
					$user,
					$lineupdated->product->id,
					$lineupdated->fk_warehouse,
					abs(GETPOST("debit")),
					0,
					$langs->trans('productNotUsedForOorder', $object->ref) . 'Debit manuel'
					);
			}
		if ($result > 0)
		{
			$oOHistory = new OperationOrderHistory($db);
			$oOHistory->stockMvt($Oobject, $lineupdated->product, GETPOST("debit")*-1);
		}
	}elseif($action == 'deletline-piece' && is_object($lineupdated)){
		$result = $object->removeChild($user, 'OperationOrderDet', $lineid);
		if ($result)
		{
			if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) {
				$ret = $object->fetch($object->id); // Reload to get new records
				$object->generateDocument($object->modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
			}
			$object->setTimePlannedT();
			
			header('Location: '.$_SERVER["PHP_SELF"].'?orid='.$object->id);
			exit;
		}
		else
		{
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}elseif ($action == 'valid'){
		?>
		<script type="text/javascript">
			$(document).ready(function () {
			window.parent.$('#orcheck').dialog('close');
			window.parent.$('#orcheck').remove();
			});
		</script>
		<?php
	}
	
	top_htmlhead('', '');
		
	if($action == 'dialog-supplier-order' && !empty($lineid) && !empty($user->rights->fournisseur->commande->creer) ){
		print _displayDialogSupplierOrder($lineid);
	}

	print load_fiche_titre($langs->trans("controleMO"), $linkback, 'title_setup');
	print '<br>';
	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans("operation").'</td>';
	print '<td>'.$langs->trans("qty").'</td>';
	print '<td>'.$langs->trans("hprevues").'</td>';
	print '<td>'.$langs->trans("hpointée").'</td>';
	print '<td>'.$langs->trans("facture").'</td>';
	print '<td>'.$langs->trans("action").'</td>';
	print "</tr>\n";
	$totqty = 0;
	$totplaned = 0;
	$totspent = 0;
	$totfact= 0;
	
	foreach ($object->lines as $line){
		
		if($line->product->array_options['options_or_scan']){
			if(!empty($line->fk_parent_line)){
				foreach ($object->lines as $linep){
					if($linep->id == $line->fk_parent_line) $desc = $linep->product->label;
				}
			}else{
				$desc = $line->product->label . ' - ' . $line->desc;
			
			}
			print '<form method="post" action="orcheck.php">';
			print '<input type="hidden" name="token" value="'.newToken().'">';
			print '<input type="hidden" name="orid" value="'.$orid . '">';
			print '<input type="hidden" name="lineid" value="'.$line->id . '">' ;
			print '<tr class="oddeven">';
			print '<td>'.$desc.'</td>';
			print '<td>'.price($line->qty) .'</td>';
			print '<td>'.price(($line->time_planned)/3600) .'</td>';
			print '<td>'.round($line->time_spent/3600,2) .'</td>';
			print '<td><input class="right maxwidth=30" type="text" name="fact" value="'.($line->total_ht/$line->product->price).'"></td>';
			print '<td>';
			print '<div class="center"><input type="submit" class="button" value="'.$langs->trans("Modify").'" formaction="orcheck.php?action=editline"><input type="submit" class="button" value="'.$langs->trans("delete").'" formaction="orcheck.php?action=deletline"></div>';
			print '</td>';
			print '</tr>';
			print '</form>';
			$totqty +=  $line->qty;
			$totplaned +=  $line->time_planned/3600;
			$totspent +=  $line->time_spent/3600;
			$totfact +=  ($line->total_ht/$line->product->price);
		}
	}
	$mo_stat = 0;
	$mo_stat_label = 'MO a controler';
	
	$coef_mo = round((($totfact-$totspent)/$totplaned) *100,2);
	if($conf->global->LLTRUCKS_MO_COEF_MIN<$coef_mo && $coef_mo<$conf->global->LLTRUCKS_MO_COEF_MAX){
		$mo_stat = 1;
		$mo_stat_label = 'Check OR MO OK';
	}
	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans("totmo").'</td>';
	print '<td>'.price($totqty).'</td>';
	print '<td>'.price($totplaned).'</td>';
	print '<td>'.round($totspent,2).'</td>';
	print '<td>'.price($totfact).'</td>';
	print '<td>Ecart MO faite / MO facturée ='.$coef_mo . '% ' . $mo_stat_label .  '</td>';
	print "</tr>\n";
	
	print '</table>';
	
	print '<br><br>';
	
	print load_fiche_titre($langs->trans("controlepart"), $linkback, 'title_setup');
	print '<br>';
	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans("Product").'</td>';
	print '<td>'.$langs->trans("qtyprev").'</td>';
	print '<td>'.$langs->trans("qtydebit").'</td>';
	print '<td>'.$langs->trans("price").'</td>';
	print '<td>'.$langs->trans("totalivoiced").'</td>';
	print '<td>'.$langs->trans("mouvstock").'</td>';
	print '<td>'.$langs->trans("action").'</td>';
	print "</tr>\n";
	
	$pt_stat = 0;
	$nb_part = 0;
	foreach ($object->lines as $line){
		
		if($line->product->type == 0){
			$TLineQtyUsed = $object->getAlreadyUsedQtyLines();
			$TLastLinesByProduct = $object->getLastLinesByProduct();
			$qtyUsed = price($line->getQtyUsed($TLineQtyUsed, $TLastLinesByProduct));
			
			$coef_part = round(($qtyUsed-$line->qty)*100,2);
			
			$qtyadjust = 0;
			if($qtyUsed<$line->qty){
				$qtyadjust= ($line->qty-$qtyUsed)*-1;
			}
			$nb_part++;
			print '<form method="post" action="orcheck.php">';
			print '<input type="hidden" name="token" value="'.newToken().'">';
			print '<input type="hidden" name="orid" value="'.$orid . '">';
			print '<input type="hidden" name="lineid" value="'.$line->id . '">' ;
			print '<tr class="oddeven">';
			print '<td>'.$line->product->label.'</td>';
			print '<td>'.price($line->qty) .'</td>';
			print '<td>'.$qtyUsed .'</td>';
			print '<td>'.price($line->price) .'</td>';
			print '<td>' .price($line->total_ht) . '</td>';
			if($conf->global->LLTRUCKS_PT_COEF_MIN<$coef_part && $coef_par<$conf->global->LLTRUCKS_PT_COEF_MAX){
				print '<td colspan="2"> controle piece OK </td>';
				$pt_stat++;
			}else{
				$pt_stat=0;
				print '<td>';
				print '<div class="center">';
				print '<input class="right maxwidth=30" type="text" name="debit" value="'.$qtyadjust.'">';
				print  '<input type="submit" class="button" value="'.$langs->trans("debit").'" formaction="orcheck.php?action=debit-stock">';
				print '</td>';
				print '<td>';
				if($qtyUsed==0){
					print '<input type="submit" class="button" value="'.$langs->trans("notdone").'" formaction="orcheck.php?action=deletline-piece"></div>';
				}Else{
					print 'impossible de supprimerune ligne avec des piece débitées';
				}
				print '</td>';
				print '</tr>';
				print '</form>';
			}
		}
	}

	print '</table>';
	
	print '<br><br>';
	
	print load_fiche_titre($langs->trans("controlext"), $linkback, 'title_setup');
	print '<br>';
	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans("Product").'</td>';
	print '<td>'.$langs->trans("desc").'</td>';
	print '<td>'.$langs->trans("totalivoiced").'</td>';
	print '<td>'.$langs->trans("ordered").'</td>';
	print '<td>'.$langs->trans("orderedto").'</td>';
	print '<td>'.$langs->trans("action").'</td>';
	print "</tr>\n";
	$ext_stat = 0;
	$nb_ext = 0;
	foreach ($object->lines as $line){
		
		if($line->product->array_options['options_oorder_available_for_supplier_order']){
			
			$line->fetchObjectLinked();
			if(!empty($line->linkedObjects['order_supplier'])){
				$supplieroder = array_values($line->linkedObjects['order_supplier'])[0];
				$supplieroder->fetch_thirdparty();
			}
			$nb_ext++;
			print '<form method="post" action="orcheck.php">';
			print '<input type="hidden" name="token" value="'.newToken().'">';
			print '<input type="hidden" name="orid" value="'.$orid . '">';
			print '<input type="hidden" name="lineid" value="'.$line->id . '">' ;
			print '<tr class="oddeven">';
			print '<td>'.$line->product->label.'</td>';
			print '<td>'.$line->description .'</td>';
			print '<td>' .price($line->total_ht) . '</td>';
			
			if(empty($supplieroder)){
				$ext_stat = 0;
				print '<td colspan ="3"> Veuillez emettre le bon de commande avant de cloturer cet OR</td>';
			}Else{
				$ext_stat++;
				print '<td>'.$supplieroder->getNomUrl(1) . ' '  . $supplieroder->getLibStatut(3).'</td>';
				print '<td>' .$supplieroder->thirdparty->getNomUrl(1). '</td>';
				print '<td></td>';
				
			}
			print '</tr>';
			print '</form>';
		}
	}
	
	print '</table>';
	
	if($mo_stat == 1 && $pt_stat == $nb_part && $ext_stat == $nb_ext){
		$chekor = 1;
	}else{
		$chekor = 0;
	}
	$chekor = 1;
	$actionUrl = $_SERVER["PHP_SELF"].'?orid='.$orid.'&amp;action=';
	
	print '<div class="tabsAction">'."\n";
	print dolGetButtonAction($langs->trans("Cancel"), '', 'default', $actionUrl . 'cancel', '', 1);
	print dolGetButtonAction($langs->trans("valid"), '', 'default', $actionUrl . 'valid', '', $chekor == 1);
	print '</div>'."\n";
	
	print $chekor;
	
	llxFooter();
}
?>
