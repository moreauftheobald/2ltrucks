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

global $sonf, $db;

// Load translation files required by the page
$langs->load("admin");
$langs->load("lltrucks@lltrucks");

if (!$user->admin) accessforbidden();

$action = GETPOST('action', 'aZ09');

$form = New Form($db);

/*
 *	Actions NOTIFY_PLUS_EMAIL_FROM
 */

if ($action == 'setvalue' && $user->admin)
{
    dolibarr_set_const($db, "LLTRUCKS_PRICE_COEF", GETPOST("LLTRUCKS_PRICE_COEF"), 'chaine', 0, '', $conf->entity);
    dolibarr_set_const($db, "LLTRUCKS_STATUT_BEFORE_CHECK", GETPOST("LLTRUCKS_STATUT_BEFORE_CHECK"), 'chaine', 0, '', $conf->entity);
    dolibarr_set_const($db, "LLTRUCKS_STATUT_AFTER_CHECK", GETPOST("LLTRUCKS_STATUT_AFTER_CHECK"), 'chaine', 0, '', $conf->entity);
    dolibarr_set_const($db, "NOTIFY_PLUS_EMAIL_FROM", GETPOST("LLTRUCKS_STATUT_AFTER_CHECK"), 'chaine', 0, '', 0);
}

$sql = "SELECT code, label "; 
$sql.= "FROM llx_operationorder_status "; 
$sql.= "WHERE status = 1 " ;
$sql.= "AND entity = " . $conf->entity;


$resql = $db->query($sql);
if ($resql)
{
	while ($obj = $db->fetch_object($resql))
	{
		$TOR[$obj->code] = $obj->label;
	}
}
/*
 * View
 */
llxHeader('', $langs->trans("admin2ltrucks"), '');

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("admin2ltrucks"), $linkback, 'title_setup');

$head = lltrucksAdminPrepareHead();
dol_fiche_head(
		$head,
		'settings',
		'administration du module 2ltrucks',
		-1,
		"lltrucks@lltrucks"
		);

print '<br>';
print '<form method="post" action="lltrucks_setup.php">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="setvalue">';

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Name").'</td>';
print '<td>'.$langs->trans("Value").'</td>';
print "</tr>\n";


print '<tr class="oddeven">';
print '<td width="300px">'.$langs->trans("pricecoef").'</td>';
print '<td><input class="right maxwidth=300" type="number" name="LLTRUCKS_PRICE_COEF" value="'.$conf->global->LLTRUCKS_PRICE_COEF.'"></td></tr>';

print '<tr class="oddeven">';
print '<td width="300px">'.$langs->trans("statusbeforeorcheck").'</td>';
print '<td>' . $form->selectArray('LLTRUCKS_STATUT_BEFORE_CHECK', $TOR, $conf->global->LLTRUCKS_STATUT_BEFORE_CHECK, 0,0,0,'',0,0,0,'','',1) . '</td></tr>';

print '<tr class="oddeven">';
print '<td width="300px">'.$langs->trans("Statusafterorcheck").'</td>';
print '<td>' . $form->selectArray('LLTRUCKS_STATUT_AFTER_CHECK', $TOR, $conf->global->LLTRUCKS_STATUT_AFTER_CHECK, 0,0,0,'',0,0,0,'','',1) . '</td></tr>';

print '<tr class="oddeven">';
print '<td width="300px">'.$langs->trans("defaultmailadress").'</td>';
print '<td><input class="right maxwidth=500" name="NOTIFY_PLUS_EMAIL_FROM" value="'.$conf->global->NOTIFY_PLUS_EMAIL_FROM.'"></td></tr>';


print '</table>';

print '<div class="center"><br><input type="submit" class="button" value="'.$langs->trans("Modify").'"></div>';

print '</form><br><br>';


// End of page
llxFooter();
$db->close();
