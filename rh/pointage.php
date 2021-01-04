<?php

// Libraries
require('../config.php');

// Translations
$langs->load('lltrucks@lltrucks');

 // Access control
//var_dump($user->rights->absence->myactions->visualiserCompteur); exit;
if (! $user->rights->absence->myactions->visualiserCompteur) {
 accessforbidden();
 }
 
// Appel des class
dol_include_once('/core/class/html.formother.class.php');
dol_include_once('core/class/html.form.class.php');
dol_include_once('user/class/user.class.php');
dol_include_once('core/lib/date.lib.php');
dol_include_once('custom/absence/class/absence.class.php');

/*
 * Actions
 */
$fk_user = $user->id;
_fiche($fk_user);

/*
 * View
 */

//pr�paration de la requ�te avec un filtre qui r�cup�re les donn�es s�l�ctionn�es dans le tableau LLTRUCKS_EXCLUDE_IMPRO
$filter = '';
$x = array();
$x = json_decode($conf->global->LLTRUCKS_EXCLUDE_IMPRO);
if (is_array($x)){
	foreach ($x as $key => $val){
		$filter .= '"'.$val.'",';
	}
	//On enl�ve le dernier caract�re de $filter ie la derni�re virgule
	$filter=substr($filter, 0, -1);
}

$action=GETPOST("action");
if ($action=='search'){
	$sql= 'SELECT u.rowid as rowid, MIN(op.task_datehour_d) as min_date_d, MAX(op.task_datehour_f) as max_date_f, ';
	$sql .= ' SUM(op.task_duration) as total_duration,';
	$sql .= ' CONCAT (u.lastname, " ", u.firstname) as name,';
	$sql .= ' DAY (op.task_datehour_d) as jour,';
	$sql .= ' MONTH (op.task_datehour_d) as mois,';
	$sql .= ' TIMEDIFF(MAX(op.task_datehour_f), MIN(op.task_datehour_d)) as amplitude,';
	$sql .= ' DAYOFWEEK(MIN(op.task_datehour_d)) as jour_semaine';
	$sql.= ' FROM `'.MAIN_DB_PREFIX.'user` as u';
	$sql.= ' INNER JOIN `'.MAIN_DB_PREFIX.'operationordertasktime` as op ON op.fk_user = u.rowid';
	$sql .= ' WHERE u.rowid ='. GETPOST("user");
	$sql .= ' AND MONTH (op.task_datehour_d)='. GETPOST("month");
	$sql .= ' AND YEAR (op.task_datehour_d)='. GETPOST("year");
	$sql .= ' AND op.label NOT IN ('.$filter.') ';
	$sql .= ' GROUP BY u.rowid,  YEAR (op.task_datehour_d), MONTH (op.task_datehour_d), DAY (op.task_datehour_d)';
	$sql .= ' ORDER BY op.task_datehour_d';
	$resql=$db->query($sql);
	
	if ($resql){
		
		$sql= 'SELECT p.fk_object as fk_object, ';
		$sql .= ' (TIME_TO_SEC(TIMEDIFF (p.lundi_heurefam, p.lundi_heuredam))+TIME_TO_SEC(TIMEDIFF (p.lundi_heurefpm, p.lundi_heuredpm))) as "2",';
		$sql .= ' (TIME_TO_SEC(TIMEDIFF (p.mardi_heurefam, p.mardi_heuredam))+TIME_TO_SEC(TIMEDIFF (p.mardi_heurefpm, p.mardi_heuredpm))) as "3",';
		$sql .= ' (TIME_TO_SEC(TIMEDIFF (p.mercredi_heurefam, p.mercredi_heuredam))+TIME_TO_SEC(TIMEDIFF(p.mercredi_heurefpm, p.mercredi_heuredpm))) as "4",';
		$sql .= ' (TIME_TO_SEC(TIMEDIFF (p.jeudi_heurefam, p.jeudi_heuredam))+TIME_TO_SEC(TIMEDIFF(p.jeudi_heurefpm, p.jeudi_heuredpm))) as "5",';
		$sql .= ' (TIME_TO_SEC(TIMEDIFF (p.vendredi_heurefam, p.vendredi_heuredam))+TIME_TO_SEC(TIMEDIFF(p.vendredi_heurefpm, p.vendredi_heuredpm))) as "6",';
		$sql .= ' (TIME_TO_SEC(TIMEDIFF (p.samedi_heurefam, p.samedi_heuredam))+TIME_TO_SEC(TIMEDIFF(p.samedi_heurefpm, p.samedi_heuredpm))) as "7",';
		$sql .= ' (TIME_TO_SEC(TIMEDIFF (p.dimanche_heurefam, p.dimanche_heuredam))+TIME_TO_SEC(TIMEDIFF(p.dimanche_heurefpm, p.dimanche_heuredpm))) as "1"';
		$sql.= ' FROM `'.MAIN_DB_PREFIX.'operationorderuserplanning` as p';
		$sql.= ' INNER JOIN `'.MAIN_DB_PREFIX.'user` as u ON u.rowid=p.fk_object';
		$sql.= ' WHERE p.object_type = "user"';
		$sql .= ' AND u.rowid ='. GETPOST("user");
		$resql2=$db->query($sql);
		$planning = $db->fetch_array($resql2);
		
		print '<div class="div-table-responsive">';
		print '<table class="tagtable liste'.($moreforfilter ? " listwithfilterbefore" : "").'">';
		print '<thead>';
		print '<tr class="liste_titre_filter" >';
		print ' <th class="liste_titre" colspan="2" >' . $langs->trans ( 'Day' ) . '</th>';
		print ' <th class="liste_titre" >' . $langs->trans ( 'DateD�but' ) . '</th>';
		print ' <th class="liste_titre" >' . $langs->trans ( 'DateFin' ) . '</th>';
		print ' <th class="liste_titre" >' . $langs->trans ( 'Amplitude' ) . '</th>';
		print ' <th class="liste_titre" >' . $langs->trans ( 'Duree' ) . '</th>';
		print ' <th class="liste_titre" >' . $langs->trans ( 'Htheorique' ) . '</th>';
		print ' <th class="liste_titre" >' . $langs->trans ( 'Hsup' ) . '</th>';
		print '</tr>';
		print '</thead>';
		
		print '<tbody>';
		
		//pr�paration de 2 tableaux pour convertir avec str_replace les jours anglais rendus par le format date_format par des mots fran�ais lors de l'affichage
		$jourAnglais = array("Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun");
		$jourFrancais = array("Lundi", "Mardi", "Mercredi", "Jeudi", "Vendredi", "Samedi", "Dimanche");
		
		$totalEffW = 0;
		$totalPrevuW = 0;
		$totalHSupW = 0;
		$totalEffM = 0;
		$totalPrevuM = 0;
		$totalHSupM = 0;
		$jourprec = 0;
		$hSup = 0;
		$tab = array();
		$L = new DateTime( GETPOST('year').'-'.GETPOST("month").'-01' );
		$dernierJour = $L->format('t');
		
		for ($i=1; $i<=$dernierJour; $i++){
			$L= new DateTime ( GETPOST('year').'-'.GETPOST("month").'-'.$i);
			$jourSemaine = $L->format('w');
			//DayOfWeek en SQL donne Dimanche = 1; Le format w donne Dimanche = 0 => +1 pour ajuster
			$tab[$i]['planning']=$planning[$jourSemaine+1];
			$tab[$i]['jour_semaine'] = $jourSemaine+1;
			$tab[$i]['min_date_d'] = $L->format('Y-m-d 00:00:00');
			$tab[$i]['max_date_f'] = $L->format('Y-m-d 00:00:00');
			$tab[$i]['amplitude'] = $L->format('Y-m-d 00:00:00');
			$tab[$i]['total_duration'] = 0;
			$tab[$i]['hSup'] = $tab[$i]['planning']*-1;
			$tab[$i]['hSupAff'] = $tab[$i]['planning'];
			$tab[$i]['sign']="-";
		}
		
		while ($object = $db->fetch_object($resql)){			
			$tab[$object->jour]['jour_semaine'] = $object->jour_semaine;
			$tab[$object->jour]['min_date_d'] = $object->min_date_d;
			$tab[$object->jour]['max_date_f'] = $object->max_date_f;
			$tab[$object->jour]['amplitude'] = $object->amplitude;
			$tab[$object->jour]['total_duration'] = $object->total_duration;
			$tab[$object->jour]['hSup'] = $tab[$object->jour]['total_duration']-$tab[$object->jour]['planning'];
			$tab[$object->jour]['hSupAff']=abs($tab[$object->jour]['hSup']);
			if ($tab[$object->jour]['hSup']<0){
				$tab[$object->jour]['sign']="-";
			}else{
				$tab[$object->jour]['sign']="+";
			}
		}		
		ksort($tab);
		
		foreach ($tab as $key=>$valjour){
			$joursemaine = "";
			$jour = "";
			$pointage = "";
			$ampli = "";
			$duree = "";			
			$totalEffM += $valjour['total_duration'];
			$totalPrevuM += ($planning[$jourprec]);
			$totalHSupM += $valjour['hSup'];
			
			// affichage total semaine quand on repart sur une autre semaine (Dimanche= 1) 
			if ($valjour['jour_semaine']< $jourprec){
				if($totalHSupW<0){
					$sign = "-";
				}else{
					$sign= "+";
				}
				print '<tr >';
				print ' <td bgcolor="#0771ad" colspan="5" style="font-weight : bold">Total de la semaine</td>';
				print ' <td bgcolor="#0771ad"  align="center" style="font-weight : bold">'.convertSecondToTime(intval($totalEffW),'allhourmin').'</td>';
				print ' <td bgcolor="#0771ad" align="center" style="font-weight : bold">'.convertSecondToTime(intval($totalPrevuW),'allhourmin').'</td>';
				print ' <td bgcolor="#0771ad" align="center" style="font-weight : bold">'.$sign.convertSecondToTime(intval(abs($totalHSupW)),'allhourmin').'</td>';
				print '</tr>';
				$totalEffW = 0;
				$totalPrevuW = 0;
				$totalHSupW = 0;				
			}
			//mise � jour de la valeur de jourprec avant la boucle suivante
			$jourprec = $valjour['jour_semaine'];
			//calcul des totaux
			$totalEffW += $valjour['total_duration'];
			$totalHSupW += $valjour['hSup'];
			$totalPrevuW += ($planning[$jourprec]);
			//Utilisation de la methode date_format de la class DateTime pour avoir H:i
			$joursemaine = new DateTime($valjour['min_date_d']);
			$jour = date_format($joursemaine, 'D');
			$pointage = new DateTime($valjour['max_date_f']);		
			$ampli=$joursemaine->diff($pointage);
			//Convertion de "total_duration" qui est en secondes en un format heure
			$duree = convertSecondToTime(intval($valjour['total_duration']),'allhourmin');
									
			print '<tr>';
			print ' <td  align="center"> ' . str_replace($jourAnglais,$jourFrancais,$jour). '</td>';
			print ' <td  align="center"> ' . $key. '</td>';
			print ' <td align="center"> '. date_format($joursemaine, 'H:i'). '</td>';
			print ' <td align="center"> '. date_format($pointage, 'H:i'). '</td>';
			if ($ampli->d>=1){
				print ' <td align="center"> '. $ampli->format('%a jour(s) %H:%I'). '</td>';
			}else{
				print ' <td align="center"> '. $ampli->format('%H:%I'). '</td>';
			}
			print ' <td align="center"> '. $duree. '</td>';
			print ' <td align="center">'.convertSecondToTime(intval($valjour['planning']),'allhourmin').'</td>';
			print ' <td align="center">'.$valjour['sign'].convertSecondToTime(intval($valjour['hSupAff']),'allhourmin').'</td>';
			print '</tr>';			
		}
		
		// affichage total derni�re semaine		
			if($totalHSupW<0){
				$sign = "-";
			}else{
				$sign= "+";
			}
		print '<tr >';
		print ' <td bgcolor="#0771ad" colspan="5" style="font-weight : bold">Total de la semaine</td>';
		print ' <td bgcolor="#0771ad" align="center" style="font-weight : bold">'.convertSecondToTime(intval($totalEffW),'allhourmin').'</td>';
		print ' <td bgcolor="#0771ad" align="center" style="font-weight : bold">'.convertSecondToTime(intval($totalPrevuW),'allhourmin').'</td>';
		print ' <td bgcolor="#0771ad" align="center" style="font-weight : bold">'.$sign.convertSecondToTime(intval(abs($totalHSupW)),'allhourmin').'</td>';
		print '</tr>';
		
		//affichage total mois
			if($totalHSupM<0){
				$sign = "-";
			}else{
				$sign= "+";
			}
		print '<tr >';
		print ' <td bgcolor="#0771ad" colspan="5" style="font-weight : bold">Total du mois</td>';
		print ' <td bgcolor="#0771ad" align="center" style="font-weight : bold">'.convertSecondToTime(intval($totalEffM),'allhourmin').'</td>';
		print ' <td bgcolor="#0771ad" align="center" style="font-weight : bold">'.convertSecondToTime(intval($totalPrevuM),'allhourmin').'</td>';
		print ' <td bgcolor="#0771ad" align="center" style="font-weight : bold">'.$sign.convertSecondToTime(intval(abs($totalHSupM)),'allhourmin').'</td>';
		print '</tr>';		
		print '</tbody>';
		print '</table>';
		print '</div>';		
	}
}

llxFooter();


function _fiche($fk_user) {
	global $db,$langs,$conf;
	
	llxHeader();
	
	$sql= 'SELECT DISTINCT u.rowid as rowid, u.lastname as lastname, u.firstname as firstname ';
	$sql.= ' FROM `'.MAIN_DB_PREFIX.'user` as u';
	$sql.= ' INNER JOIN `'.MAIN_DB_PREFIX.'usergroup_user` as us ON u.rowid=us.fk_user';
	$sql.= ' INNER JOIN `'.MAIN_DB_PREFIX.'usergroup` as g ON g.rowid=us.fk_usergroup';
	$sql .= ' WHERE g.rowid IN (7, 8, 9) ';
	$sql .= ' ORDER BY u.lastname';	
	$resql=$db->query($sql);
	$userlist = array();
	
	if ($resql){
		while ( $object = $db->fetch_object ( $resql ) ) {
			$userlist[$object->rowid] = $object->lastname . " " . $object->firstname;
		}
	}
	
	
	$page_name = "Pointage";
	print load_fiche_titre($langs->trans($page_name), $linkback, "title_externalaccess@externalaccess");
	
	print '<form role="form" autocomplete="off" class="form" method="post"  action="'.$_SERVER['PHP_SELF'].'" >';
	
	$form = new Form($db);
	$formother = new FormOther($db);
	
	$selectmonth = __get('month', date('m', strtotime('-1month')  ) ,'integer');
	$selectyear = __get('year', date('Y', strtotime('-1month')  ),'integer');
	
	print '<label for="month">'.$langs->transnoentities('Month').'</label>';
	print $formother->select_month($selectmonth,'month',1);
	
	print '<label for="year">'.$langs->transnoentities('Year').'</label>';
	print $formother->select_year( $selectyear,'year',1, 20, 1);
	
	print '<label for="user">'.$langs->transnoentities('Name').'</label>';
	print $form->selectarray('user', $userlist, GETPOST('user'), 0, 0, 0, '', 0, 0, 0, '', '',0);
	
	print '<button type="submit" class="btn btn-success btn-strong" name="action" value="search" >'.$langs->transnoentities('PointageBtnSubmitSearch').'</button>';
	
	print '</form>';
	
	
	
}



