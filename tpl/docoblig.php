<?php
require '../config.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
dol_include_once('operationorder/class/operationorder.class.php');
dol_include_once('operationorder/class/operationorderaction.class.php');
dol_include_once('operationorder/lib/operationorder.lib.php');
dol_include_once('dolifleet/class/vehicule.class.php');

global $db, $conf, $langs, $user;

if (! is_object($outputlangs)) $outputlangs=$langs;
// For backward compatibility with FPDF, force output charset to ISO, because FPDF expect text to be encoded in ISO
if (! empty($conf->global->MAIN_USE_FPDF)) $outputlangs->charset_output='ISO-8859-1';

// Translations
$outputlangs->loadLangs(array("main", "dict", "companies", "bills", "products", "orders", "deliveries", "stocks","lltrucks@lltrucks"));

$orid = GETPOST('orid', 'int');
$action = GETPOST('action', 'alpha');
$lineid=GETPOST('lineid');
$step = GETPOST('step');
$steptodo = GETPOST('steptodo');
$currentzone = GETPOST('currentzone');
if(empty($step)) $step = '0';

$object = new OperationOrder($db);

$res=0;
if(!empty($orid)){
	$res= $object->fetch($orid, true);
	$docans = unserialize($object->array_options['options_doc_ans']);
	dol_include_once('/lltrucks/class/dictionarydocoblig.class.php');
	
	$obj = new dictionarydocobliglist($db);
	$docspec = $obj->fetchAll(1,false, array('doccode'=> $docans[$step-1],'docactive'=>1));
	$docspec = $docspec[0];
	
	$zonedone = $docans[$docspec->doccode]['zonnedone'];
	if(empty($zonedone)) $zonedone = array();
}




$vehicule = new doliFleetVehicule($db);
$res = $vehicule->fetch($object->array_options['options_fk_dolifleet_vehicule']);
if($res<=0) {
	$vehicule->ref = $langs->trans('VehiculeNotFound');
}else{
	$vehicule->getActivities($object->date_operation_order, $object->date_operation_order);
}

$obj= new dictionarydocobligzone($db);
$doczone = $obj->fetchAll(0,false, array('fk_c_docoblig_list'=> $docspec->id,'zoneactive'=>1 ));
if(empty($docans[$docspec->doccode]['ans'])){
    $docans[$docspec->doccode]['ref'] = $docspec->fk_product;
    foreach($doczone as $zone){
    	$obj= new dictionarydocobligvalue($db);
    	$docvalue= $obj->fetchAll(0,false, array('fk_c_docoblig_zone'=> $zone->id,'valactive'=>1 ));

    	if($zone->zonetype == 0 && !in_array($zone->id,$zonedone)){
    		foreach($docvalue as$value){
    			if(!empty($value->valposx)){
    				$posx = $zone->zoneposx + $value->valposx;
    			}else{
    				$posx = $zone->zoneposx;
    			}
		
    			if(!empty($value->valposy)){
    				$posy = $zone->zoneposy + $value->valposy;
    			}else{
    				$posy = $zone->zoneposy;
    			}
		
    			if($value->valtype == 0){
    				eval('$out='. $value->value .";");
    			}else{
    				$out = $value->value;
    			}
		
    			$out=trim(str_replace(array("\n", "\r","&nbsp;"), ' ',strip_tags($out)));
		          
    			if(strlen($out) >> $value->valmax_len){
    				$out=substr($out,0,$value->valmax_len);
    			}
    			$docans['ans'][$docspec->doccode][]= array($out,$posx,$posy,$value->vallon,$zone->zonepage,$zone->zonesize);
    		}
    		$docans[$docspec->doccode]['zonnedone'][]= $zone->id;
    		$zonedone = $docans[$docspec->doccode]['zonnedone'];
    	}elseif ($zone->zonetype == 1 && !in_array($zone->id,$zonedone)){
    		$r =0;
    		foreach($object->lines as $line){
    			$w=0;
    			foreach($docvalue as $value){
    				$posx = $zone->zoneposx + $value->valposx;
    				if($line->product->array_options['options_doc_obl']<>$docspec->doccode){
    					
    					if($value->valtype == 0){
    						eval('$out='. $value->value .";");
    					}else{
    						$out = $value->value;
    					}
    					$out=trim(str_replace(array("\n", "\r","&nbsp;"), ' ',strip_tags($out)));
					
    					$out = $outputlangs->convToOutputCharset($out);
    					
    					$nbline = ceil(strlen($out)/$value->valmax_len);
					
    					for($i=1; $i<= $nbline;$i++){
    						if($i>$w) $w ++;
    						$aff = substr($out,0+($value->valmax_len*($i-1)),$value->valmax_len);
    						$posy = $zone->zoneposy + ($value->valposy+($zone->zonestep*($r+$i)));
    						$docans['ans'][$docspec->doccode][]= array($out,$posx,$posy,$value->vallon,$zone->zonepage,$zone->zonesize);
    					}
    				}
    			}
    			$r+=$w;
    		}
    		$docans[$docspec->doccode]['zonnedone'][]= $zone->id;
    		$zonedone = $docans[$docspec->doccode]['zonnedone'];
    	}elseif($zone->zonetype == 2 && !in_array($zone->id,$zonedone) && $zone->id == $currentzone){
    		foreach($docvalue as $value){
    			if($value->valtype <> 2){
    				if(!empty($value->valposx)){
    					$posx = $zone->zoneposx + $value->valposx;
    				}else{
    					$posx = $zone->zoneposx;
    				}
				
				    if(!empty($value->valposy)){
    					$posy = $zone->zoneposy + $value->valposy;
    				}else{
    					$posy = $zone->zoneposy;
    				}
    				$out = GETPOST("V_" . $value->id);
				
    				$out=trim(str_replace(array("\n", "\r","&nbsp;"), ' ',strip_tags($out)));
				
    				if(strlen($out)>$value->valmax_len){
    					$out=substr($out,0,$value->valmax_len);
    				}
    				$docans['ans'][$docspec->doccode][]= array($out,$posx,$posy,$value->vallon,$zone->zonepage,$zone->zonesize);
    			}
		    }
		    $docans[$docspec->doccode]['zonnedone'][]= $zone->id;
		    $docans[$docspec->doccode]['currentzone'][]= $zone->id;
		    $zonedone = $docans[$docspec->doccode]['zonnedone'];
		    
    	}
    }
}
$object->array_options['options_doc_ans'] = serialize($docans);
$object->update($user,1);

if ($action == 'next')
{
	$step ++;
	header('Location: docoblig.php?step= ' .$step . '&orid='.$orid);
}

top_htmlhead('', '');



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

$object = new OperationOrder($db);

$res=0;
if(!empty($orid)){
	$res= $object->fetch($orid, true);
}

if($res>0){
	if ($action == 'valid'){
	    $object->array_options['options_doc_ans'] = serialize($docans);
		$object->array_options['options_orcheck'] = 1;
		$object->update($user,1);
		$now=dol_now();
		
		$sql = "INSERT INTO llx_operationorderhistory(date_creation,fk_operationorder,title, description, fk_user_creat, entity) VALUES ('";
		$sql.= $object->db->idate($now) ."'," .$object->id . ", 'validation Doc',''" . $user->id . "," . $object->entity . ")";
		
		$object->db->query($sql);
		
		?>
		<script type="text/javascript">
		$(document).ready(function () {
			window.parent.$('#orcheck').dialog('close');
			window.parent.$('#orcheck').remove();
		});
		</script>
		<?php
	}	
	
	print load_fiche_titre($langs->trans("Doc Obligatoires"), $linkback, 'title_setup');
	foreach($doczone as $zone){
	    
	    if($zone->zonetype == 2 && !in_array($zone->id,$zonedone)){
				$obj= new dictionarydocobligvalue($db);
				$docvalue= $obj->fetchAll(0,false, array('fk_c_docoblig_zone'=> $zone->id,'valactive'=>1 ));
				$posylist=array();
				$sql = "SELECT valposy FROM ". MAIN_DB_PREFIX. "docoblig_value WHERE fk_c_docoblig_zone =" . $zone->id . " GROUP BY valposy";
				$resql = $db->query($sql);
			
				if ($resql){
					while ($value =$db->fetch_object($resql)){
						$posylist[] = $value->valposy;
					}
				}
				
				print '<br>';
				sort($posylist);
				print '<table class="noborder centpercent">';
				print '<form method="post" action="docoblig.php?">';
				print '<input type="hidden" name="token" value="'.newToken().'">';
				print '<input type="hidden" name="orid" value="'.$orid . '">';
				print '<input type="hidden" name="step" value="'.$step . '">';
				print '<input type="hidden" name="currentzone" value="'.$zone->id . '">';
				print '<input type="hidden" name="steptodo" value="'.$steptodo . '">';
				$ligne = 1;
				foreach($posylist as $posy){
					$sql = "SELECT * FROM ". MAIN_DB_PREFIX. "docoblig_value WHERE fk_c_docoblig_zone =" . $zone->id . " AND valposy = " .$posy;
					$resql = $db->query($sql);
					if ($resql){
						if($ligne == 1){
							print '<tr class="liste_titre">';
						}else{
							print '<tr class="oddeven">';
						}
						while ($value =$db->fetch_object($resql)){
						    print '<td>';
							if($value->valtype == 2){
								print $langs->trans($value->value);
							}elseif($value->valtype == 3){
								print printyesno($value);							
							}elseif($value->valtype == 1){
							    print '<input class="left maxwidth=' .$value->vallon .'" name="V_' . $value->rowid . '" value="">';
							}Else{
								print $value-value;
							}
							print '</td>';
						}
						print '</tr>';
						$ligne++;
					}
				
				}
				print '</table>';
				print '<div class="center"><br><input type="submit" class="button" value="'.$langs->trans("record").'"></div>';
				print '</form>';
				break;
			}
	}
	
	$zonetotreat = count($doczone);
	$zonetreated = count($zonedone);
	if($zonetreated == $zonetotreat){
		if($steptodo == $step){
			$chekor = 2;
		}else{
			$chekor = 1;
		}
	}else{
		$chekor = 0;
	}
			
	$actionUrl = $_SERVER["PHP_SELF"].'?orid='.$orid.'&amp;step=' .$step .'&amp;action=';

	print '<div class="tabsAction">'."\n";
	print dolGetButtonAction($langs->trans("Cancel"), '', 'default', $actionUrl . 'cancel', '', 1);
	if(count($docoblig)>0 && $step < count($docoblig)+1){
		print dolGetButtonAction($langs->trans("next"), '', 'default', $actionUrl . 'next', '', $chekor == 1);
	}else{
		print dolGetButtonAction($langs->trans("valid"), '', 'default', $actionUrl . 'valid', '', $chekor == 2);
	}
	print '</div>'."\n";

llxFooter();
}

function printyesno($value){
	if(!is_array($value)){
		
		$form=new Form($db);
		$list = array('Non' => 'Non', 'Oui'=>'Oui');
		$res = $form->selectarray("V_" . $value->rowid,$list,0);
		return $res;
	}else{
		return 0;
	}
	
	
}
?>
