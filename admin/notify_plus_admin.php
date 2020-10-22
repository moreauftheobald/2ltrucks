<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2015 Laurent Destailleur  <eldy@users.sourceforge.org>
 * Copyright (C) 2013      Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2015      Bahfir Abbes         <contact@dolibarrpar.org>
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
 *	    \file       htdocs/admin/notification.php
 *		\ingroup    notification
 *		\brief      Page to setup notification module
 */

require '../config.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/lltrucks/core/triggers/interface_49_modlltrucks_lltruckstrigger.class.php';
dol_include_once('lltrucks/lib/lltrucks.lib.php');

// Load translation files required by the page
$langs->loadLangs(array('admin', 'other', 'orders', 'propal', 'bills', 'errors', 'mails','ticket','lltrucks@lltrucks'));

// Security check
if (!$user->admin)
  accessforbidden();

$action = GETPOST('action', 'aZ09');


/*
 * Actions
 */

if ($action == 'setvalue' && $user->admin)
{
	$result = dolibarr_set_const($db, "NOTIFY_PLUS_EMAIL_FROM", $_POST["email_from"], 'chaine', 0, '', $conf->entity);
	$result = dolibarr_set_const($db, "NOTIFY_PLUS_EVENT_FETCH", $_POST["email_from"], 'chaine', 0, '', 0);
    if ($result < 0) $error++;
    
  	if (!$error)
    {
    	setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
    }
    else
	{
		setEventMessages($langs->trans("Error"), null, 'errors');
    }
}



/*
 *	View
 */

$form = new Form($db);

llxHeader('', $langs->trans("NotificationSetup"));

$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("NotificationSetup"), $linkback, 'title_setup');

$head = lltrucksAdminPrepareHead();
dol_fiche_head(
		$head,
		'notify_plus',
		'administration du module 2ltrucks',
		-1,
		"lltrucks@lltrucks"
		);

print '<span class="opacitymedium">';
print $langs->trans("NotificationsDesc").'<br>';
print $langs->trans("NotificationsDescUser").'<br>';
if (!empty($conf->societe->enabled)) print $langs->trans("NotificationsDescContact").'<br>';
print $langs->trans("NotificationsDescGlobal").'<br>';
print '</span>';
print '<br>';

print '<form method="post" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="setvalue">';

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameter").'</td>';
print '<td>'.$langs->trans("Value").'</td>';
print "</tr>\n";

print '<tr class="oddeven"><td>';
print $langs->trans("NotificationEMailFrom").'</td>';
print '<td>';
print '<input size="32" type="email" name="NOTIFY_PLUS_EMAIL_FROM" value="'.$conf->global->NOTIFY_PLUS_EMAIL_FROM.'">';
if (!empty($conf->global->NOTIFY_PLUS_EMAIL_FROM) && !isValidEmail($conf->global->NOTIFY_PLUS_EMAIL_FROM)) print ' '.img_warning($langs->trans("ErrorBadEMail"));

print '<tr class="oddeven"><td>';
print $langs->trans("eventfetched").'</td>';
print '<td>';
print '<input size="200" type="string" name="NOTIFY_PLUS_EVENT_FETCH" value="'.$conf->global->NOTIFY_PLUS_EVENT_FETCH.'">';
print '</td>';
print '</tr>';



print '</table>';

print '<br><br>';


// Notification per contacts
$title = $langs->trans("ListOfNotificationsPerUser");
if (!empty($conf->societe->enabled)) $title = $langs->trans("ListOfNotificationsPerUserOrContact");
print load_fiche_titre($title, '', '');

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Label").'</td>';
print "</tr>\n";

// // Load array of available notifications
$notificationtrigger = new Interfacelltruckstrigger($db);
$listofnotifiedevents = $notificationtrigger->getListOfManagedEvents();

print '<tr class="oddeven">';
print '<td>';

$i = 0;
foreach ($listofnotifiedevents as $notifiedevent)
{
    $label = $langs->trans("Notify_".$notifiedevent['code']); //!=$langs->trans("Notify_".$notifiedevent['code'])?$langs->trans("Notify_".$notifiedevent['code']):$notifiedevent['label'];
    $elementLabel = $langs->trans(ucfirst($notifiedevent['elementtype']));

    if ($notifiedevent['elementtype'] == 'order_supplier') $elementLabel = $langs->trans('SupplierOrder');
    elseif ($notifiedevent['elementtype'] == 'propal') $elementLabel = $langs->trans('Proposal');
    elseif ($notifiedevent['elementtype'] == 'facture') $elementLabel = $langs->trans('Bill');
    elseif ($notifiedevent['elementtype'] == 'commande') $elementLabel = $langs->trans('Order');
    elseif ($notifiedevent['elementtype'] == 'ficheinter') $elementLabel = $langs->trans('Intervention');
    elseif ($notifiedevent['elementtype'] == 'shipping') $elementLabel = $langs->trans('Shipping');
    elseif ($notifiedevent['elementtype'] == 'expensereport') $elementLabel = $langs->trans('ExpenseReport');

    if ($i) print ', ';
    print $label;

    $i++;
}
print '</td></tr>';

print '</table>';
print '<div class="opacitymedium">';
print '* '.$langs->trans("GoOntoUserCardToAddMore").'<br>';
if (!empty($conf->societe->enabled)) print '** '.$langs->trans("GoOntoContactCardToAddMore").'<br>';
print '</div>';
print '<br><br>';

print '<br>';

print '<div class="center"><input type="submit" class="button" value="'.$langs->trans("Save").'"></div>';

print '</form>';

llxFooter();
$db->close();
