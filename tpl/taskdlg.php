<?php
require '../config.php';
dol_include_once('projet/class/task.class.php');
dol_include_once('core/class/html.form.class.php');
dol_include_once('core/class/html.formother.class.php');
$form = new Form($db);
$formother = new FormOther($db);
global $db, $conf, $langs, $user;

$langs->load("lltrucks@lltrucks");

$id = GETPOST('id', 'int');
$action = GETPOST('action', 'alpha');
$canmodif=array();
$object = new Task($db);

top_htmlhead('', '');

if(!empty($id)){
    $res = $object->fetch($id);
    $canmodif = $object->getListContactId();
    if(!in_array($user->id,$canmodif) && $user->admin) $canmodif[]=$user->id;
}
if($action =='submit' && in_array($user->id,$canmodif)){
    $object->progress = GETPOST('progress', 'int');
    $object->date_start = dol_mktime(00, 00, 01, $_POST['dsmonth'], $_POST['dsday'], $_POST['dsyear']);
    $object->date_end = dol_mktime(23, 59, 59, $_POST['demonth'], $_POST['deday'], $_POST['deyear']);
    $res = $object->update($user);
    if($res>0){
        
        if($object->fk_task_parent){
            $parent = new Task($db);
            $parent->fetch($object->fk_task_parent);
            //print_r($object->progress);exit;
            if($object->progress >0 && $parent->progress == 0){
                $parent->progress = 5;
                $parent->update($user);
            }
        }
        
        ?>
		<script type="text/javascript">
		$(document).ready(function () {
			window.parent.$('#taskdlg').dialog('close');
			window.parent.$('#taskdlg').remove();
		});
		</script>
		<?php
    }
    
}
if(!empty($res) && in_array($user->id,$canmodif)){
   
	print load_fiche_titre($langs->trans("Mise a jour"), $linkback, 'title_setup');
	print '<FORM method="post" action="taskdlg.php">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="id" value="'.$id . '">';
	print '<input type="hidden" name="action" value="submit">';
	print '<TABLE class="noborder centpercent">';
	print '<TR><TD>tache :</TD>';
	print '<TD>' .$object->label .'</TD></TR>';
	print '<TR><TD>'.$langs->trans("DateStart").':</TD>';
	print '<TD>' . $form->selectDate($object->date_start, 'ds', 0, 0, 0, '', 1, 1).'</TD></TR>';
	print '<TR><TD>'.$langs->trans("DateEnd").':</TD>';
	print '<TD>' . $form->selectDate($object->date_end  , 'de', 0, 0, 0, '', 1, 1).'</TD></TR>';
	print '<TR><TD>'.$langs->trans("ProgressDeclared").':</td><td>';
	print $formother->select_percent($object->progress, 'progress', 0, 5, 0, 100, 1) .'</TD></TR>';
	print '</TABLE>';
	print '<div class="center"><br><input type="submit" class="button" value="'.$langs->trans("record").'"></div>';
	print '</FORM>';
	
}else{
    print load_fiche_titre($langs->trans("Mise a jour"), $linkback, 'title_setup');
    print '<TABLE class="noborder centpercent">';
    print '<TR><TD>tache :</TD>';
    print '<TD>' .$object->label .'</TD></TR>';
    print '<TR><TD>'.$langs->trans("DateStart").':</TD>';
    print '<TD>' . dol_print_date($object->date_start, 'day').'</TD></TR>';
    print '<TR><TD>'.$langs->trans("DateEnd").':</TD>';
    print '<TD>' . dol_print_date($object->date_end, 'day').'</TD></TR>';
    print '<TR><TD>'.$langs->trans("ProgressDeclared").':</td>';
    print '<TD>' . $object->progress . ' % </TD></TR>';
    print '</TABLE>';
}
llxFooter();
?>
