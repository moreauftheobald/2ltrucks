<?php
/* Copyright (C) 2002-2007      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010      Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012      Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2010           Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2013-2016      Jean-François Ferry  <hello@librethic.io>
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
 *  \file       htdocs/ticket/document.php
 *  \ingroup    ticket
 *  \brief      files linked to a ticket
 */

require '../config.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/ticket.lib.php';
require_once DOL_DOCUMENT_ROOT.'/ticket/class/ticket.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT."/core/lib/company.lib.php";
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';

// Load translation files required by the page
$langs->loadLangs(array("companies", "other", "ticket", "mails"));

$id       = GETPOST('id', 'int');
$ref      = GETPOST('ref', 'alpha');
$track_id = GETPOST('track_id', 'alpha');
$action   = GETPOST('action', 'alpha');
$confirm  = GETPOST('confirm', 'alpha');

// Security check
if (!$user->rights->ticket->read) {
    accessforbidden();
}

// Get parameters
$sortfield = GETPOST("sortfield", 'alpha');
$sortorder = GETPOST("sortorder", 'alpha');
$page = GETPOST("page", 'int');
if (empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1
$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortorder) $sortorder = "ASC";
if (!$sortfield) $sortfield = "position_name";

$object = new Ticket($db);
$result = $object->fetch($id, $ref, $track_id);

if ($result < 0) {
	setEventMessages($object->error, $object->errors, 'errors');
} else {
	$upload_dir = $conf->ticket->dir_output = DOL_DATA_ROOT . "/ticket/".dol_sanitizeFileName($object->ref);
}


/*
 * Actions
 */

include_once DOL_DOCUMENT_ROOT.'/core/actions_linkedfiles.inc.php';



/*
 * View
 */

$form = new Form($db);

$title = $langs->trans("ticket").' - '.$langs->trans("Files");
$help_url = '';
llxHeader('', $title, $help_url);

if ($object->id)
{
	/*
	 * Show tabs
	 */
	$head = ticket_prepare_head($object);
	
	dol_fiche_head($head, 'documents', $langs->trans("Ticket"), 0, 'documents');
	
	
	// Build file list
	$filearray = dol_dir_list($upload_dir, "files", 0, '', '(\.meta|_preview.*\.png)$', $sortfield, (strtolower($sortorder) == 'desc' ?SORT_DESC:SORT_ASC), 1);
	$totalsize = 0;
	foreach ($filearray as $key => $file)
	{
		$totalsize += $file['size'];
	}
	
	// Object card
	// ------------------------------------------------------------
	$linkback = '<a href="'.dol_buildpath('/ticket/list.php', 1).'?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';
	
	dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);
	
	print '<div class="fichecenter">';
	
	print '<div class="underbanner clearboth"></div>';
	print '<table class="border centpercent tableforfield">';
	
	// Number of files
	print '<tr><td class="titlefield">'.$langs->trans("NbOfAttachedFiles").'</td><td colspan="3">'.count($filearray).'</td></tr>';
	
	// Total size
	print '<tr><td>'.$langs->trans("TotalSizeOfAttachedFiles").'</td><td colspan="3">'.$totalsize.' '.$langs->trans("bytes").'</td></tr>';
	
	print '</table>';
	
	print '</div>';
	
	dol_fiche_end();
	
	$modulepart = 'ticket';
	//$permission = $user->rights->operationorder->operationorder->write;
	$permission = 1;
	//$permtoedit = $user->rights->operationorder->operationorder->write;
	$permtoedit = 1;
	$param = '&id='.$object->id;
	
	//$relativepathwithnofile='operationorder/' . dol_sanitizeFileName($object->id).'/';
	$relativepathwithnofile = 'ticket/'.dol_sanitizeFileName($object->ref).'/';
	
	include_once DOL_DOCUMENT_ROOT.'/core/tpl/document_actions_post_headers.tpl.php';
}
else
{
    accessforbidden('', 0, 1);
}

// End of page
llxFooter();
$db->close();
