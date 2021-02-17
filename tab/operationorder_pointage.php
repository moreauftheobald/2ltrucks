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

require '../config.php';
dol_include_once('operationorder/class/operationorder.class.php');
dol_include_once('operationorder/lib/operationorder.lib.php');
dol_include_once('product/class/product.class.php');

if(empty($user->rights->operationorder->read)) accessforbidden();

$langs->load('abricot@abricot');
$langs->load('operationorder@operationorder');
$langs->load('lltrucks@lltrucks');

$id = GETPOST('id', 'int');
$ref = GETPOST('ref');
$operationOrder = new OperationOrder($db);

if (!empty($id) || !empty($ref)) {
	$operationOrder->fetch($id, true, $ref);

	$result = restrictedArea($user, $operationOrder->element, $id, $operationOrder->table_element . '&' . $operationOrder->element);


	$status = new Operationorderstatus($db);
	$res = $status->fetchDefault($object->status, $object->entity);
	if ($res < 0) {
		setEventMessage($langs->trans('ErrorLoadingStatus'), 'errors');
	}
	$usercanread = $user->rights->operationorder->read;
	$usercancreate = $permissionnote = $permissiontoedit = $permissiontoadd = $permissiondellink = $operationOrder->userCan($user, 'edit'); // Used by the include of actions_setnotes.inc.php

}
$hookmanager->initHooks(array('operationorderpointagelist'));


/*
 * Actions
 */

$parameters=array();
$reshook=$hookmanager->executeHooks('doActions', $parameters, $object);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');


if (empty($reshook))
{
    // do action from GETPOST ...
}

/*
 * View
 */

llxHeader('', $langs->trans('OperationOrderPointageList'), '', '');

//$type = GETPOST('type');
//if (empty($user->rights->operationorder->all->read)) $type = 'mine';

if ($operationOrder->id > 0){
	$head = operationorder_prepare_head($operationOrder);
	$picto = 'operationorder@operationorder';
	dol_fiche_head($head, 'pointage', $langs->trans('Pointages'), -1, $picto);
	$linkback = '<a href="'.dol_buildpath('/operationorder/list.php', 1).'?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';
	$morehtmlref='<div class="refidno">';
	// Ref customer
	$morehtmlref.=$form->editfieldkey("RefCustomer", 'ref_client', $operationOrder->ref_client, $operationOrder, 0, 'string', '', 0, 1);
	$morehtmlref.=$form->editfieldval("RefCustomer", 'ref_client', $operationOrder->ref_client, $operationOrder, 0, 'string', '', null, null, '', 1);
	// Thirdparty
	$morehtmlref.='<br>'.$langs->trans('ThirdParty') . ' : ' . $operationOrder->thirdparty->getNomUrl(1);
	// Project
	if (! empty($conf->projet->enabled))
	{
		$langs->load("projects");
		$morehtmlref.='<br>'.$langs->trans('Project') . ' ';

		if (! empty($object->fk_project)) {
			$proj = new Project($db);
			$proj->fetch($operationOrder->fk_project);
			$morehtmlref.='<a href="'.DOL_URL_ROOT.'/projet/card.php?id=' . $operationOrder->fk_project . '" title="' . $langs->trans('ShowProject') . '">';
			$morehtmlref.=$proj->ref;
			$morehtmlref.='</a>';
		} else {
			$morehtmlref.='';
		}
	}
	$morehtmlref.='</div>';
	dol_banner_tab($operationOrder, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);
	print '<div class="underbanner clearboth"></div>';
}
	// TODO ajouter les champs de son objet que l'on souhaite afficher



$sql = 'SELECT p.rowid, p.fk_user as fk_user,p.task_datehour_d as date_d,p.task_datehour_f as date_f  ,(p.task_duration/3600) as duration, d.fk_product as fk_product, d.description as description';
$sql.= ' FROM '.MAIN_DB_PREFIX.'operationorderdet AS d ';
$sql.= ' INNER JOIN '.MAIN_DB_PREFIX.'operationordertasktime as p on p.fk_orDet = d.rowid ';
$sql.= ' WHERE  p.entity IN ('.getEntity('operationorder', 1).')';
//if ($type == 'mine') $sql.= ' AND t.fk_user = '.$user->id;
if ($operationOrder->id > 0) $sql.= ' AND d.fk_operation_order = '.$operationOrder->id;

//Var_dump($sql);
//exit;
$formcore = new TFormCore($_SERVER['PHP_SELF'], 'form_list_operationorderhistory', 'POST');

$nbLine = GETPOST('limit');
if (empty($nbLine)) $nbLine = !empty($user->conf->MAIN_SIZE_LISTE_LIMIT) ? $user->conf->MAIN_SIZE_LISTE_LIMIT : $conf->global->MAIN_SIZE_LISTE_LIMIT;


// List configuration
$listViewConfig = array(
    'view_type' => 'list' // default = [list], [raw], [chart]
    ,'allow-fields-select' => true
    ,'limit'=>array(
        'nbLine' => $nbLine
    )
    ,'list' => array(
        'title' => $langs->trans('OperationOrderPointageList')
        ,'image' => 'title_generic.png'
        ,'picto_precedent' => '<'
        ,'picto_suivant' => '>'
        ,'noheader' => 0
        ,'messageNothing' => $langs->trans('NoOperationOrderPointage')
        ,'picto_search' => img_picto('', 'search.png', '', 0)
        ,'massactions'=>array(

        )
        ,'param_url' => '&id='.GETPOST('id')
    )
    ,'subQuery' => array()
    ,'link' => array()
    ,'type' => array(
        'date_d' => 'datetime',
        'date_f' => 'datetime',
        'duration'=>'money'// [datetime], [hour], [money], [number], [integer]
    )
    ,'translate' => array()
    ,'hide' => array(
        'rowid' // important : rowid doit exister dans la query sql pour les checkbox de massaction
    )
    ,'title'=>array (
        'fk_user' => $langs->trans('Mecanicien'),
        'fk_product' => $langs->trans('Operation'),
        'description' => $langs->trans('Description'),
        'date_d' => $langs->trans('Debut'),
        'date_f' => $langs->trans('fin'),
        'duration' => $langs->trans('Duree'),
        'planning' => $langs->trans('lien vers planning')
    )
    ,'eval'=>array(
        'fk_user' => '_getUserNomURL(\'@fk_user@\')',
        'fk_product' => '_getProductNomURL(\'@fk_product@\')',
        'planning' => '_getUPlanningURL(\'@date_d@\')'
    )
    ,'sortfield'=>'rowid'
    ,'sortorder'=>'DESC'
);
if(!empty($operationOrder->id)) {
	unset($listViewConfig['title']['fk_operationorder']);
	unset($listViewConfig['search']['fk_operationorder']);
	unset($listViewConfig['eval']['fk_operationorder']);
}


$r = new Listview($db, 'operationorderPointage');

// Change view from hooks
$parameters=array('listViewConfig' => $listViewConfig);

print '<input type="hidden" name="id" value="'.GETPOST('id').'"/>';
echo $r->render($sql, $listViewConfig);

$parameters=array('sql'=>$sql);
$reshook=$hookmanager->executeHooks('printFieldListFooter', $parameters, $object);    // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;

$formcore->end_form();

llxFooter('');
$db->close();

function _getUserNomURL($fk_user) {
    global $db;
    if(!empty($fk_user)) {
        $userNomUrl = new User($db);
        $userNomUrl->fetch($fk_user);
        return $userNomUrl->getNomUrl();
    } else return '';
}


function _getUPlanningURL($date_d) {
    global $db;
    if(!empty($date_d)) {
        $dt_select = $db->jdate($date_d);
        $day = date('j',$dt_select);
        $month = date('n',$dt_select);
        $year = date('Y',$dt_select);
        $url = DOL_URL_ROOT.'/custom/operationorder/operationorder_orplanning.php?dateday='.$day.'&datemonth='.$month.'&dateyear='.$year.'&date='.$day.'/'.$month.'/'.$year;
        $linkstart = '<a href="'.$url.'">Planning</a>';
        return $linkstart;
    } else return '';
}

function _getProductNomURL($fk_product) {
    global $db;
    if(!empty($fk_product)) {
        $productNomUrl = new Product($db);
        $productNomUrl->fetch($fk_product);
        return $productNomUrl->getNomUrl();
    } else return 'error';
}