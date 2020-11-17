<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2011 Laurent Destailleur  <eldy@users.sourceforge.org>
 * Copyright (C) 2011-2013 Juanjo Menent		<jmenent@2byte.es>
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
 *   \file       htdocs/admin/clicktodial.php
 *   \ingroup    clicktodial
 *   \brief      Page to setup module clicktodial
 */

require '../config.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
dol_include_once('lltrucks/lib/lltrucks.lib.php');
dol_include_once('/lltrucks/class/dictionarydocoblig.class.php');

global $sonf, $db, $user;

// Load translation files required by the page
$langs->load("admin");
$langs->load("lltrucks@lltrucks");

if (!$user->admin) accessforbidden();

$action = GETPOST('action', 'aZ09');
//recuperation des vlaeur des doc dans le post
$docid = GETPOST('docid');
$doccode = GETPOST('doccode');
$doclabel = GETPOST('doclabel');
$fk_product = GETPOST('fk_product');
$docactive = GETPOST('docactive');

//recuperation des valeur de la zone dans le post
$zoneid = GETPOST('zoneid');
$zonelabel = GETPOST('zonelabel');
$fk_c_docoblig_list = GETPOST('fk_c_docoblig_list');
$zonetype = GETPOST('zonetype');
$zonepage = GETPOST('zonepage');
$zoneposx = GETPOST('zoneposx');
$zoneposy = GETPOST('zoneposy');
$zonestep = GETPOST('zonestep');
$zonesize = GETPOST('zonesize');
$zoneactive = GETPOST('zoneactive');

//recuperation des valeur de la valuedans le post
$valid = GETPOST('valid');
$fk_c_docoblig_zone = GETPOST('fk_c_docoblig_zone');
$valtype = GETPOST('valtype');
$valposx = GETPOST('valposx');
$valposy = GETPOST('valposy');
$value = GETPOST('value');
$valmax_len = GETPOST('valmax_len');
$vallon = GETPOST('vallon');
$valactive = GETPOST('valactive');

$form = New Form($db);

$obj = new dictionarydocobliglist($db);
$obj2= new dictionarydocobligzone($db);
$obj3= new dictionarydocobligvalue($db);

/*
 *	Actions NOTIFY_PLUS_EMAIL_FROM
 */

if ($action == 'setdoc' && $user->admin && !empty($docid))
{

    $obj->fetch($docid);
    $obj->doccode= $doccode;
    $obj->doclabel = $doclabel;
    $obj->fk_product = $fk_product;
    $obj->docactive = $docactive;
    $res = $obj->update($user);
    unset($_POST['docid']);
    unset($_POST['doccode']);
    unset($_POST['doclabel']);
    unset($_POST['fk_product']);
    unset($_POST['docactive']);
    unset($docid);
    unset($doccode);
    unset($doclabel);
    unset($fk_product);
    unset($docactive);

}elseif($action == 'createdoc' && $user->admin){
    $obj->doccode= $doccode;
    $obj->doclabel = $doclabel;
    $obj->fk_product = $fk_product;
    $obj->docactive = $docactive;
    $res = $obj->create($user);
    unset($_POST['docid']);
    unset($_POST['doccode']);
    unset($_POST['doclabel']);
    unset($_POST['fk_product']);
    unset($_POST['docactive']);
    unset($docid);
    unset($doccode);
    unset($doclabel);
    unset($fk_product);
    unset($docactive);

}elseif($action == 'deletedoc' && $user->admin && !empty($docid)){
    $obj->fetch($docid);
    $obj->delete($user);
    unset($_POST['docid']);
    unset($_POST['doccode']);
    unset($_POST['doclabel']);
    unset($_POST['fk_product']);
    unset($_POST['docactive']);
    unset($docid);
    unset($doccode);
    unset($doclabel);
    unset($fk_product);
    unset($docactive);

}elseif ($action == 'setzone' && !empty($docid) && !empty($zoneid) && $user->admin){
    $obj2->fetch($zoneid);
    $obj2->zonelabel= $zonelabel;
    $obj2->fk_c_docoblig_list = $docid;
    $obj2->zonetype = $zonetype;
    $obj2->zonepage = $zonepage;
    $obj2->zoneposx = $zoneposx;
    $obj2->zoneposy = $zoneposy;
    $obj2->zonestep = $zonestep;
    $obj2->zonesize = $zonesize;
    $obj2->zoneactive = $zoneactive;
    $res = $obj2->update($user);
    unset($_POST['zoneid']);
    unset($_POST['zonelabel']);
    unset($_POST['zonetype']);
    unset($_POST['fk_c_docoblig_list']);
    unset($_POST['zoneposx']);
    unset($_POST['zoneposy']);
    unset($_POST['zonestep']);
    unset($_POST['zonesize']);
    unset($_POST['zoneactive']);
    unset($zoneid);
    unset($zonelabel);
    unset($fk_c_docoblig_list);
    unset($zonetype);
    unset($zonepage);
    unset($zoneposx);
    unset($zoneposy);
    unset($zonestep);
    unset($zonesize);
    unset($zoneactive);
 
}elseif($action == 'createzone' && $user->admin && !empty($docid)){
    $obj2->zonelabel= $zonelabel;
    $obj2->fk_c_docoblig_list = $docid;
    $obj2->zonetype = $zonetype;
    $obj2->zonepage = $zonepage;
    $obj2->zoneposx = $zoneposx;
    $obj2->zoneposy = $zoneposy;
    $obj2->zonestep = $zonestep;
    $obj2->zonesize = $zonesize;
    $obj2->zoneactive = $zoneactive;
    $res = $obj2->create($user);
    unset($_POST['zoneid']);
    unset($_POST['zonelabel']);
    unset($_POST['zonetype']);
    unset($_POST['fk_c_docoblig_list']);
    unset($_POST['zoneposx']);
    unset($_POST['zoneposy']);
    unset($_POST['zonestep']);
    unset($_POST['zonesize']);
    unset($_POST['zoneactive']);
    unset($zoneid);
    unset($zonelabel);
    unset($fk_c_docoblig_list);
    unset($zonetype);
    unset($zonepage);
    unset($zoneposx);
    unset($zoneposy);
    unset($zonestep);
    unset($zonesize);
    unset($zoneactive);
   
    
}elseif($action == 'deletezone' && $user->admin && !empty($zoneid)){
    $obj2->fetch($zoneid);
    $obj2->delete($user);
    unset($_POST['zoneid']);
    unset($_POST['zonelabel']);
    unset($_POST['zonetype']);
    unset($_POST['fk_c_docoblig_list']);
    unset($_POST['zoneposx']);
    unset($_POST['zoneposy']);
    unset($_POST['zonestep']);
    unset($_POST['zonesize']);
    unset($_POST['zoneactive']);
    unset($zoneid);
    unset($zonelabel);
    unset($fk_c_docoblig_list);
    unset($zonetype);
    unset($zonepage);
    unset($zoneposx);
    unset($zoneposy);
    unset($zonestep);
    unset($zonesize);
    unset($zoneactive);
    
}elseif ($action == 'setval' && !empty($docid) && !empty($zoneid) && !empty($valid) && $user->admin){
        $obj3->fetch($valid);
        $obj3->fk_c_docoblig_zone = $zoneid;
        $obj3->valtype = $valtype;
        $obj3->valposx = $valposx;
        $obj3->valposy = $valposy;
        $obj3->value = $value;
        $obj3->valmax_len = $valmax_len;
        $obj3->vallon = $vallon;
        $obj3->valactive = $valactive;
        $res = $obj3->update($user);
        unset($_POST['valid']);
        unset($_POST['valtype']);
        unset($_POST['fk_c_docoblig_zone']);
        unset($_POST['valposx']);
        unset($_POST['valposy']);
        unset($_POST['value']);
        unset($_POST['valmax_len']);
        unset($_POST['vallon']);
        unset($_POST['valactive']);
        unset($valid);
        unset($fk_c_docoblig_zone);
        unset($valtype);
        unset($valposx);
        unset($valposy);
        unset($value);
        unset($valmax_len);
        unset($vallon);
        unset($valactive);
        
    
}elseif($action == 'createval' && !empty($docid) && !empty($zoneid) && $user->admin){
    $obj3->fk_c_docoblig_zone = $zoneid;
    $obj3->valtype = $valtype;
    $obj3->valposx = $valposx;
    $obj3->valposy = $valposy;
    $obj3->value = $value;
    $obj3->vamax_len = $valmax_len;
    $obj3->vallon = $vallon;
    $obj3->valactive = $valactive;
    $res = $obj3->create($user);
    unset($_POST['valid']);
    unset($_POST['valtype']);
    unset($_POST['fk_c_docoblig_zone']);
    unset($_POST['valposx']);
    unset($_POST['valposy']);
    unset($_POST['value']);
    unset($_POST['valmax_len']);
    unset($_POST['vallon']);
    unset($_POST['valactive']);
    unset($valid);
    unset($fk_c_docoblig_zone);
    unset($valtype);
    unset($valposx);
    unset($valposy);
    unset($value);
    unset($valmax_len);
    unset($vallon);
    unset($valactive);
    
    
}elseif($action == 'deleteval' && $user->admin && !empty($valid)){
    $obj3->fetch($valid);
    $obj3->delete($user);
    unset($_POST['valid']);
    unset($_POST['valtype']);
    unset($_POST['fk_c_docoblig_zone']);
    unset($_POST['valposx']);
    unset($_POST['valposy']);
    unset($_POST['value']);
    unset($_POST['valmax_len']);
    unset($_POST['vallon']);
    unset($_POST['valactive']);
    unset($valid);
    unset($fk_c_docoblig_zone);
    unset($valtype);
    unset($valposx);
    unset($valposy);
    unset($value);
    unset($valmax_len);
    unset($vallon);
    unset($valactive);
    
}

$docspec = $obj->fetchAll(0,false);

/*
 * View
 */
llxHeader('', $langs->trans("admin2ltrucks"), '');

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("admin2ltrucks"), $linkback, 'title_setup');

$head = lltrucksAdminPrepareHead();
dol_fiche_head(
		$head,
		'docs',
		'Parametrage Documents obligatoires',
		-1,
		"lltrucks@lltrucks"
		);

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("code").'</td>';
print '<td>'.$langs->trans("name").'</td>';
print '<td>'.$langs->trans("product").'</td>';
print '<td>'.$langs->trans("etat").'</td>';
print '<td>'.$langs->trans("action").'</td>';
print "</tr>\n";

if(!empty($docid)){
    $actiotodo = 'setdoc';
}else{
    $actiotodo = 'createdoc';
}
print '<form method="post" action="' . $_SERVER["PHP_SELF"] .'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="docid" value="' . $docid . '">';
print '<input type="hidden" name="action" value="' . $actiotodo . '">';
print '<tr class="oddeven">';
print '<td>'.$obj->showInputField(array(),'doccode',$doccode).'</td>';
print '<td>'.$obj->showInputField(array(),'doclabel',$doclabel).'</td>';
print '<td>'.$obj->showInputField(array(),'fk_product',$fk_product).'</td>';
print '<td>'.$obj->showInputField(array(),'docactive',$docactive).'</td>';
if(!empty($docid)){
    print '<td><input type="submit" class="button" value="'.$langs->trans("Save").'"></td>';
}else{
    print '<td><input type="submit" class="button" value="'.$langs->trans("Add").'"></td>';
}
print '</tr>';
print '</form>';

if(empty($docid)){
    foreach ($docspec as $spec){
        $actionurl = $_SERVER["PHP_SELF"] . '?docid='.$spec->id . '&doclabel=' .$spec->doclabel .'&fk_product=' . $spec->fk_product . '&docactive='.$spec->docactive . '&doccode=' . $spec->doccode;
        print '<tr class="oddeven">';
        print '<td>'.$spec->showOutputFieldQuick('doccode').'</td>';
        print '<td>'.$spec->showOutputFieldQuick('doclabel').'</td>';
        print '<td>'.$spec->showOutputFieldQuick('fk_product').'</td>';
        print '<td>'.$spec->showOutputFieldQuick('docactive').'</td>';
        print '<td>'. print_action_list($actionurl) .'</td>';
        print '</tr>';
    }
}
print '</table>';
if(!empty($docid)){
   
    $doczone = $obj2->fetchAll(0,false, array('fk_c_docoblig_list'=> $docid));
    
    print '<table class="noborder centpercent">';
    print '<tr class="liste_titre">';
    print '<td>'.$langs->trans("name").'</td>';
    print '<td>'.$langs->trans("Type").'</td>';
    print '<td>'.$langs->trans("page").'</td>';
    print '<td>'.$langs->trans("posx").'</td>';
    print '<td>'.$langs->trans("posy").'</td>';
    print '<td>'.$langs->trans("step").'</td>';
    print '<td>'.$langs->trans("size").'</td>';
    print '<td>'.$langs->trans("etat").'</td>';
    print '<td>'.$langs->trans("action").'</td>';
    print "</tr>\n";
    
    if(!empty($zoneid)){
        $actiotodo1 = 'setzone';
    }else{
        $actiotodo1 = 'createzone';
    }
    
    print '<form method="post" action="' . $_SERVER["PHP_SELF"] .'">';
    print '<input type="hidden" name="token" value="'.newToken().'">';
    print '<input type="hidden" name="docid" value="' . $docid . '">';
    print '<input type="hidden" name="action" value="' . $actiotodo1 . '">';
    print '<input type="hidden" name="zoneid" value="' . $zoneid . '">';
    print '<input type="hidden" name="doclabel" value="' . $doclabel . '">';
    print '<input type="hidden" name="docactive" value="' . $docactive . '">';
    print '<input type="hidden" name="doccode" value="' . $doccode . '">';
    print '<input type="hidden" name="fk_product" value="' . $fk_product . '">';
    print '<tr class="oddeven">';
    print '<td>'.$obj2->showInputField(array(),'zonelabel',$zonelabel).'</td>';
    print '<td>'.$obj2->showInputField(array(),'zonetype',$zonetype).'</td>';
    print '<td>'.$obj2->showInputField(array(),'zonepage',$zonepage).'</td>';
    print '<td>'.$obj2->showInputField(array(),'zoneposx',$zoneposx).'</td>';
    print '<td>'.$obj2->showInputField(array(),'zoneposy',$zoneposy).'</td>';
    print '<td>'.$obj2->showInputField(array(),'zonestep',$zonestep).'</td>';
    print '<td>'.$obj2->showInputField(array(),'zonesize',$zonesize).'</td>';
    print '<td>'.$obj2->showInputField(array(),'zoneactive',$zoneactive).'</td>';
    if(!empty($zoneid)){
        print '<td><input type="submit" class="button" value="'.$langs->trans("Save").'"></td>';
    }else{
        print '<td><input type="submit" class="button" value="'.$langs->trans("Add").'"></td>';
    }
    print '</tr>';
    print '</form>';
    
    if(empty($zoneid)){
        foreach ($doczone as $zone){
            $actionurl = $_SERVER["PHP_SELF"] . '?zoneid='.$zone->id . '&docid=' . $docid . '&zonelabel=' .$zone->zonelabel .'&fk_c_docoblig_list=' . $docid;
            $actionurl.= '&zonetype='.$zone->zonetype . '&zonepage=' . $zone->zonepage . '&zoneposx=' . $zone->zoneposx . '&zoneposy=' . $zone->zoneposy . '&zonestep=' .$zone->zonestep;
            $actionurl.= '&zoneactive=' .$zone->zoneactive . '&doclabel=' .$doclabel .'&fk_product=' . $fk_product . '&docactive='.$docactive . '&doccode=' . $doccode . '&zonesize=' . $zonesize;
            print '<tr class="oddeven">';
            print '<td>'.$zone->showOutputFieldQuick('zonelabel').'</td>';
            print '<td>'.$zone->showOutputFieldQuick('zonetype').'</td>';
            print '<td>'.$zone->showOutputFieldQuick('zonepage').'</td>';
            print '<td>'.$zone->showOutputFieldQuick('zoneposx').'</td>';
            print '<td>'.$zone->showOutputFieldQuick('zoneposy').'</td>';
            print '<td>'.$zone->showOutputFieldQuick('zonestep').'</td>';
            print '<td>'.$zone->showOutputFieldQuick('zonesize').'</td>';
            print '<td>'.$zone->showOutputFieldQuick('zoneactive').'</td>';
            print '<td>'. print_action_zone($actionurl) .'</td>';
            print '</tr>';
        }
    }
    print '</table>';
    if(!empty($zoneid)){
        $vallist = $obj3->fetchall(0,false,array('fk_c_docoblig_zone'=>$zoneid));
        
        print '<table class="noborder centpercent">';
        print '<tr class="liste_titre">';
        print '<td>'.$langs->trans("Type").'</td>';
        print '<td>'.$langs->trans("posx").'</td>';
        print '<td>'.$langs->trans("posy").'</td>';
        print '<td>'.$langs->trans("maxlen").'</td>';
        print '<td>'.$langs->trans("long").'</td>';
        print '<td>'.$langs->trans("value").'</td>';
        print '<td>'.$langs->trans("etat").'</td>';
        print '<td>'.$langs->trans("action").'</td>';
        print "</tr>\n";
        
        if(!empty($valid)){
            $actiotodo2 = 'setval';
        }else{
            $actiotodo2 = 'createval';
        }
        print '<form method="post" action="' . $_SERVER["PHP_SELF"]  .'">';
        print '<input type="hidden" name="token" value="'.newToken().'">';
        print '<input type="hidden" name="docid" value="' . $docid . '">';
        print '<input type="hidden" name="zoneid" value="' . $zoneid . '">';
        print '<input type="hidden" name="valid" value="' . $valid . '">';
        print '<input type="hidden" name="doclabel" value="' . $doclabel . '">';
        print '<input type="hidden" name="docactive" value="' . $docactive . '">';
        print '<input type="hidden" name="doccode" value="' . $doccode . '">';
        print '<input type="hidden" name="fk_product" value="' . $fk_product . '">';
        print '<input type="hidden" name="zonelabel" value="' . $zonelabel . '">';
        print '<input type="hidden" name="zonetype" value="' . $zonetype . '">';
        print '<input type="hidden" name="zoneposx" value="' . $zoneposx . '">';
        print '<input type="hidden" name="zoneposy" value="' . $zoneposy . '">';
        print '<input type="hidden" name="zonestep" value="' . $zonestep . '">';
        print '<input type="hidden" name="zonestep" value="' . $zonesize . '">';
        print '<input type="hidden" name="zoneactive" value="' . $zoneactive . '">';
        print '<input type="hidden" name="action" value="' . $actiotodo2 . '">';
        print '<tr class="oddeven">';
        print '<td>'.$obj3->showInputField(array(),'valtype',$valtype).'</td>';
        print '<td>'.$obj3->showInputField(array(),'valposx',$valposx).'</td>';
        print '<td>'.$obj3->showInputField(array(),'valposy',$valposy).'</td>';
        print '<td>'.$obj3->showInputField(array(),'valmax_len',$valmax_len).'</td>';
        print '<td>'.$obj3->showInputField(array(),'vallon',$vallon).'</td>';
        print '<td>'.$obj3->showInputField(array(),'value',$value).'</td>';
        print '<td>'.$obj3->showInputField(array(),'valactive',$valactive).'</td>';
        if(!empty($valid)){
            print '<td><input type="submit" class="button" value="'.$langs->trans("Save").'"></td>';
        }else{
            print '<td><input type="submit" class="button" value="'.$langs->trans("Add").'"></td>';
        }
        print '</tr>';
        print '</form>';
        print_r('ok'  . $valid);
        //exit;
        if(empty($valid)){
            foreach ($vallist as $val){
                $actionurl = $_SERVER["PHP_SELF"] . '?zoneid='.$zoneid . '&docid=' . $docid . '&zonelabel=' .$zonelabel .'&fk_c_docoblig_list=' . $docid . '&zonesize=' .$zonesize;
                $actionurl.= '&zonetype='.$zonetype . '&zonepage=' . $zonepage . '&zoneposx=' . $zoneposx . '&zoneposy=' . $zoneposy . '&zonestep=' .$zonestep;
                $actionurl.= '&zoneactive=' .$zoneactive . '&doclabel=' .$doclabel .'&fk_product=' . $fk_product . '&docactive='.$docactive . '&doccode=' . $doccode;
                $actionurl.= '&valtype=' .$val->valtype .'&fk_c_docoblig_zone=' . $zoneid . '&valposx='.$val->valposx . '&valposy=' . $val->valposy;
                $actionurl.= '&valmax_len=' .$val->valmax_len . '&vallon=' .$val->vallon .'&value=' . $val->value . '&valactive='.$val->valactive . '&valid='.$val->id;
              
                print '<tr class="oddeven">';
                print '<td>'.$val->showOutputFieldQuick('valtype').'</td>';
                print '<td>'.$val->showOutputFieldQuick('valposx').'</td>';
                print '<td>'.$val->showOutputFieldQuick('valposy').'</td>';
                print '<td>'.$val->showOutputFieldQuick('valmax_len').'</td>';
                print '<td>'.$val->showOutputFieldQuick('vallon').'</td>';
                print '<td>'.$val->showOutputFieldQuick('value').'</td>';
                print '<td>'.$val->showOutputFieldQuick('valactive').'</td>';
                print '<td>'. print_action_val($actionurl) .'</td>';
                print '</tr>';
            }
        }
        
    }
}
print '<br>';



// End of page
llxFooter();
$db->close();

function print_action_list($actionurl){
    
    $out = '<a class="reposition" href="' . $actionurl. '"><span class="fas fa-pencil-alt marginleftonly" style=" color: #444;" title="Modifier"></span></a>';
    $out.= ' ' ;
    $out.= '<a class="reposition" href="' . $actionurl .'&action=deletedoc"><span class="fas fa-trash marginleftonly pictodelete" style=" color: #444;" title="Supprimer"></span></a>';
    
    return $out;
    
}

function print_action_zone($actionurl){
    
    $out = '<a class="reposition" href="' . $actionurl. '"><span class="fas fa-pencil-alt marginleftonly" style=" color: #444;" title="Modifier"></span></a>';
    $out.= ' ' ;
    $out.= '<a class="reposition" href="' . $actionurl .'&action=deletezone"><span class="fas fa-trash marginleftonly pictodelete" style=" color: #444;" title="Supprimer"></span></a>';
    
    return $out;
    
}

function print_action_val($actionurl){
    
    $out = '<a class="reposition" href="' . $actionurl. '"><span class="fas fa-pencil-alt marginleftonly" style=" color: #444;" title="Modifier"></span></a>';
    $out.= ' ' ;
    $out.= '<a class="reposition" href="' . $actionurl .'&action=deleteval"><span class="fas fa-trash marginleftonly pictodelete" style=" color: #444;" title="Supprimer"></span></a>';
    
    return $out;
    
}

