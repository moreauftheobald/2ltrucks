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
	$coef_mo = (($totfact-$totplaned)/$totplaned) *100;
	if($conf->global->LLTRUCKS_MO_COEF_MIN<$coef_mo<$conf->global->LLTRUCKS_MO_COEF_MAX){
		$mo_stat = 1;
		$mo_stat_label = 'Check OR MO OK';
	}
	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans("totmo").'</td>';
	print '<td>'.price($totqty).'</td>';
	print '<td>'.price($totplaned).'</td>';
	print '<td>'.round($totspent,2).'</td>';
	print '<td>'.price($totfact).'</td>';
	print '<td>'. $coef_mo . ' ' . $mo_stat_label .  '</td>';
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
		
	foreach ($object->lines as $line){
		
		if($line->product->type == 0){
			$TLineQtyUsed = $object->getAlreadyUsedQtyLines();
			$TLastLinesByProduct = $object->getLastLinesByProduct();
			$qtyUsed = price($line->getQtyUsed($TLineQtyUsed, $TLastLinesByProduct));
			
			$qtyadjust = 0;
			if($qtyUsed<$line->qty){
				$qtyadjust= ($line->qty-$qtyUsed)*-1;
			}
			
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
	
	foreach ($object->lines as $line){
		
		if($line->product->array_options['options_oorder_available_for_supplier_order']){
			
			$line->fetchObjectLinked();
			if(!empty($line->linkedObjects['order_supplier'])){
				$supplieroder = array_values($line->linkedObjects['order_supplier'])[0];
				$supplieroder->fetch_thirdparty();
			}
						
			print '<form method="post" action="orcheck.php">';
			print '<input type="hidden" name="token" value="'.newToken().'">';
			print '<input type="hidden" name="orid" value="'.$orid . '">';
			print '<input type="hidden" name="lineid" value="'.$line->id . '">' ;
			print '<tr class="oddeven">';
			print '<td>'.$line->product->label.'</td>';
			print '<td>'.$line->description .'</td>';
			print '<td>' .price($line->total_ht) . '</td>';
			
			if(empty($supplieroder)){
				print '<td></td>';
				print '<td></td>';
				print '<td>';
				print '		<div class="operation-order-det-element-element-action-btn">';
				Print '           <a class="button-xs" href="orcheck.php?action=dialog-supplier-order&lineid='.$line->id.'" ><i class="fa fa-plus"></i> '.$langs->trans('CreateSupplierOrder').'</a>';
				print '		</div>';
				print '</td>';
			}Else{
				print '<td>'.$supplieroder->getNomUrl(1) . ' '  . $supplieroder->getLibStatut(3).'</td>';
				print '<td>' .$supplieroder->thirdparty->getNomUrl(1). '</td>';
				print '<td></td>';
				
			}
			print '</tr>';
			print '</form>';
		}
	}
	
	print '</table>';
		
	llxFooter();
}


function _displayDialogSupplierOrder($lineid){
	global $langs, $user, $conf, $object, $form;
	
	$line= new OperationOrderDet($object->db);
	$res = $line->fetch($lineid);
	
	print '<div id="dialog-supplier-order" style="display: none;" >';
	print '<div id="dialog-supplier-order-item_'.$lineid.'" class="dialog-supplier-order-form-wrap" title="'.$line->ref.'" >';
	print '<div class="dialog-supplier-order-form-body" >';
	if($res>0) {
		// here the form
		
		
		// Ancors
		$actionUrl= '#item_' . $line->id;
		$outForm = '<form name="create-supplier-order-form" action="' . $_SERVER["PHP_SELF"] . $actionUrl . '" method="POST">' . "\n";
		$outForm.= '<input type="hidden" name="token" value="' . newToken() . '">' . "\n";
		$outForm.= '<input type="hidden" name="id" value="' . $object->id . '">' . "\n";
		$outForm.= '<input type="hidden" name="lineid" value="' . $line->id . '">' . "\n";
		$outForm.= '<input type="hidden" name="action" value="create-supplier-order">' . "\n";
		
		$outForm.= '<table class="table-full">';
		
		// Cette partie permet une evolution du formulaire de creation de commandes fournisseur
		$supplierOrder = new CommandeFournisseur($object->db);
		$supplierOrder->fields['fk_soc']['label']='Supplier';
		$TSupplierOrderFields = array('fk_soc');
		foreach($TSupplierOrderFields as $key){
			$outForm.=  getFieldCardOutputByOperationOrder($supplierOrder, $key, '', '', 'order_');
		}
		
		$supplierOrderLine = new CommandeFournisseurLigne($object->db);
		// Bon les champs sont pas définis... mais ils le serons un jour non ?
		$supplierOrderLine->fields=array(
		//'fk_product' => array ( 'type' => 'integer:Product:product/class/product.class.php:1', 'required' => 1, 'label' => 'Product', 'enabled' => 1, 'position' => 35, 'notnull' => -1, 'visible' => -1, 'index' => 1  ),
		'subprice' => array ( 'type' => 'real', 'label' => 'UnitPrice', 'enabled' => 1, 'position' => 40, 'notnull' => 1, 'required' => 1, 'visible' => 1  ),
		'desc' => array ( 'type' => 'html', 'label' => 'Description', 'enabled' => 1, 'position' => 40, 'notnull' => 1, 'visible' => 3  ),
		'qty' => array ( 'type' => 'real', 'required' => 1, 'label' => 'Qty', 'enabled' => 1, 'position' => 45, 'notnull' => 1, 'visible' => 1, 'isameasure' => '1', 'css' => 'maxwidth75imp'  ),
		'product_type' => array ( 'type' => 'select', 'required' => 1,'label' => 'ProductType', 'enabled' => 1, 'position' => 90, 'notnull' => 1, 'visible' => 1,  'arrayofkeyval' => array('0' =>"Product", '1'=>"Service") ),
		'tva_tx'  => array ( 'type' => 'real', 'required' => 1,'label' => 'TVA', 'enabled' => 1, 'position' => 90, 'notnull' => 1, 'visible' => 1, 'fieldCallBack' => '_showVatField'),
		);
		
		if(!empty($conf->global->OPODER_SUPPLIER_ORDER_LIMITED_TO_SERVICE)){
			$supplierOrderLine->fields['product_type']['visible'] = 0;
			$outForm.= '<input type="hidden" name="orderline_product_type" value="1">' . "\n";
		}
		
		//$TSupplierOrderLineFields = array('product_type', 'subprice', 'tva_tx', 'qty', 'desc');
		//Add same product as the poduct/service on the current line
		$TSupplierOrderLineFields = array('desc');
		
		$params = array(
				'OperationOrderDet' => $line
		);
		
		foreach($TSupplierOrderLineFields as $key){
			$outForm.=  getFieldCardOutputByOperationOrder($supplierOrderLine, $key, '', '', 'orderline_', '', '', $params);
		}
		
		$outForm.= '</table>';
		$outForm.= '</form>';
		
		
		print $outForm;
	}
	else{
		print $langs->trans('LineNotFound');
	}
	print '</div>';
	print '</div>';
	print '</div>';
	
	
	// MISE A JOUR AJAX DE L'ORDRE DES LIGNES
	print '
	<script type="text/javascript">
	$(function()
	{
		var cardUrl = "'.$_SERVER["PHP_SELF"].'?id='.$object->id.'";
		var itemHash = "#item_'.$line->id.'";
					
		var dialogBox = jQuery("#dialog-supplier-order");
		var width = $(window).width();
		var height = $(window).height();
		if(width > 700){ width = 700; }
		if(height > 600){ height = 600; }
		//console.log(height);
		dialogBox.dialog({
            autoOpen: true,
            resizable: true,
            //		height: height,
            title: "'.dol_escape_js($langs->transnoentitiesnoconv('CreateSupplierOrder')).'",
            width: width,
            modal: true,
            buttons: {
                "'.$langs->transnoentitiesnoconv('Create').'": function() {
                    dialogBox.find("form").submit();
                },
                "'.$langs->transnoentitiesnoconv('Cancel').'": function() {
                    dialogBox.dialog( "close" );
                }
            },
            close: function( event, ui ) {
                window.location.replace(cardUrl + itemHash);
            },
            open: function(){
                // center dialog verticaly on open
                $([document.documentElement, document.body]).animate({
                    scrollTop: $("#dialog-supplier-order").offset().top - 50 - $("#id-top").height()
                }, 300);
            }
		});
			
		dialogBox.dialog( "open" );
			
	});
	</script>';
}
?>
