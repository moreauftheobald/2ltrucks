<?php

require '../config.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/project.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';

$object = new Project($db);
$open = GETPOST('open');

include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php';  // Must be include, not include_once
if(! empty($conf->global->PROJECT_ALLOW_COMMENT_ON_PROJECT) && method_exists($object, 'fetchComments') && empty($object->comments)) $object->fetchComments();

// Security check
$socid=0;
//if ($user->socid > 0) $socid = $user->socid;    // For external user, no check is done on company because readability is managed by public status of project and assignement.
$result = restrictedArea($user, 'projet', $id, 'projet&project');

$langs->loadlangs(array('users', 'projects'));
$form = new Form($db);
$formother = new FormOther($db);
$userstatic = new User($db);
$companystatic = new Societe($db);
$contactstatic = new Contact($db);
$task = new Task($db);

$arrayofcss = array('/includes/jsgantt/jsgantt.css');

if (!empty($conf->use_javascript_ajax))
{
    $arrayofjs = array(
        '/includes/jsgantt/jsgantt.js',
        '/projet/jsgantt_language.js.php?lang='.$langs->defaultlang
    );
}

$$title = $langs->trans("Gantt");
if (!empty($conf->global->MAIN_HTML_TITLE) && preg_match('/projectnameonly/', $conf->global->MAIN_HTML_TITLE) && $object->name) $title = ($object->ref ? $object->ref.' '.$object->name.' - ' : '').$langs->trans("Gantt");
$help_url = "EN:Module_Projects|FR:Module_Projets|ES:M&oacute;dulo_Proyectos";
llxHeader("", $title, $help_url, '', 0, 0, $arrayofjs, $arrayofcss);

if (($id > 0 && is_numeric($id)) || !empty($ref))
{
    $userWrite = $object->restrictedProjectArea($user, 'write');
    
    }

$tasksarray=$task->getTasksArray(0, 0, ($object->id ? $object->id : $id), $socid, 0);

if (count($tasksarray)>0)
{
    // Show Gant diagram from $taskarray using JSGantt
    
    $dateformat=$langs->trans("FormatDateShortJQuery");			// Used by include ganttchart.inc.php later
    $datehourformat=$langs->trans("FormatDateShortJQuery").' '.$langs->trans("FormatHourShortJQuery");	// Used by include ganttchart.inc.php later
    $array_contacts=array();
    $tasks=array();
    $task_dependencies=array();
    $taskcursor=0;
    foreach($tasksarray as $key => $val)	// Task array are sorted by "project, position, dateo"
    {
        $task->fetch($val->id, '');
        $idparent = ($val->fk_parent ? $val->fk_parent : '-'.$val->fk_project);	// If start with -, id is a project id
        
        $tasks[$taskcursor]['task_id']=$val->id;
        $tasks[$taskcursor]['task_alternate_id']=($taskcursor+1);		// An id that has same order than position (requird by ganttchart)
        $tasks[$taskcursor]['task_project_id']=$val->fk_project;
        $tasks[$taskcursor]['task_parent']=$idparent;
        $tasks[$taskcursor]['task_ref']=$val->ref;
        $tasks[$taskcursor]['task_open']=1;
        if(strlen($val->ref)==3){
            $tasks[$taskcursor]['task_open']=0;
        }
        if($val->id == $open){
            $tasks[$taskcursor]['task_open']=1;
        }
        $tasks[$taskcursor]['task_is_group'] = 0;
        $tasks[$taskcursor]['task_position'] = $val->rang;
        $tasks[$taskcursor]['task_planned_workload'] = $val->planned_workload;
        
        if ($val->fk_parent != 0 && $task->hasChildren()> 0){
            $tasks[$taskcursor]['task_is_group']=1;
        }
        elseif ($task->hasChildren()> 0) {
            $tasks[$taskcursor]['task_is_group'] = 1;
        }
        $tasks[$taskcursor]['task_milestone']='0';
        $tasks[$taskcursor]['task_percent_complete']=$val->progress;
        //$tasks[$taskcursor]['task_name']=$task->getNomUrl(1);
        //print dol_print_date($val->date_start).dol_print_date($val->date_end).'<br>'."\n";
        $tasks[$taskcursor]['task_name']=$val->ref.' - '.$val->label;
        $tasks[$taskcursor]['task_start_date']=$val->date_start;
        $tasks[$taskcursor]['task_end_date']=$val->date_end;
        
        if($val->progress == 0){
            if($val->date_start <dol_now()){
                $tasks[$taskcursor]['task_css'] = 'gtaskred';
            }Else{
                $tasks[$taskcursor]['task_css'] = 'gtaskblue';
            }
        }elseif($val->progress <100){
            if($val->date_start >dol_now()  && $val->date_end <dol_now()){
                $tasks[$taskcursor]['task_css'] = 'gtaskred';
            }Else{
                $tasks[$taskcursor]['task_css'] = 'gtaskgreen';
            }
        }else{
            $tasks[$taskcursor]['task_css'] = 'gtaskpurple';
        }
        
        //$tasks[$taskcursor]['task_color']='b4d1ea';
        $tasks[$taskcursor]['task_dependency']=$task->array_options['options_depends'];
        
        $idofusers=$task->getListContactId('internal');
        $idofcontacts=$task->getListContactId('external');
        $s='';
        if (count($idofusers)>0)
        {
            $s.=$langs->trans("Internals").': ';
            $i=0;
            foreach($idofusers as $valid)
            {
                $userstatic->fetch($valid);
                if ($i) $s.=', ';
                $s.=$userstatic->login;
                $i++;
            }
        }
        //if (count($idofusers)>0 && (count($idofcontacts)>0)) $s.=' - ';
        if (count($idofcontacts)>0)
        {
            if ($s) $s.=' - ';
            $s.=$langs->trans("Externals").': ';
            $i=0;
            $contactidfound=array();
            foreach($idofcontacts as $valid)
            {
                if (empty($contactidfound[$valid]))
                {
                    $res = $contactstatic->fetch($valid);
                    if ($res > 0)
                    {
                        if ($i) $s.=', ';
                        $s.=$contactstatic->getFullName($langs);
                        $contactidfound[$valid]=1;
                        $i++;
                    }
                }
            }
        }
        //if ($s) $tasks[$taskcursor]['task_resources']='<a href="'.DOL_URL_ROOT.'/projet/tasks/contact.php?id='.$val->id.'&withproject=1" title="'.dol_escape_htmltag($s).'">'.$langs->trans("List").'</a>';
        /* For JSGanttImproved */
        //if ($s) $tasks[$taskcursor]['task_resources']=implode(',',$idofusers);
        $tasks[$taskcursor]['task_resources'] = $s;
        //print "xxx".$val->id.$tasks[$taskcursor]['task_resources'];
        $tasks[$taskcursor]['note'] = $task->note_public;
        
        $taskcursor++;
    }
    
    // Search parent to set task_parent_alternate_id (requird by ganttchart)
    foreach ($tasks as $tmpkey => $tmptask)
    {
        foreach ($tasks as $tmptask2)
        {
            if ($tmptask2['task_id'] == $tmptask['task_parent'])
            {
                $tasks[$tmpkey]['task_parent_alternate_id'] = $tmptask2['task_alternate_id'];
                break;
            }
        }
        if (empty($tasks[$tmpkey]['task_parent_alternate_id'])) $tasks[$tmpkey]['task_parent_alternate_id'] = $tasks[$tmpkey]['task_parent'];
    }
    foreach ($tasks as $tmpkey => $tmptask)
    {
        $dependsarray=explode(',',$tmptask['task_dependency']);
        //if(!empty($dependsarray[0])){
        //    print_r($dependsarray);exit;
        //}
        foreach ($tasks as $tmptask2)
        {
            if (!empty($dependsarray[0]) && in_array($tmptask2['task_ref'], $dependsarray))
            {
                $tasks[$tmpkey]['task_depends_alternate_id'] .= $tmptask2['task_alternate_id'] .'FS,' ;
            }
        }
        $tasks[$tmpkey]['task_depends_alternate_id'] = substr($tasks[$tmpkey]['task_depends_alternate_id'],0,-1);
      }
   //print_r($tasks);exit;
   print "\n";
    
    if (!empty($conf->use_javascript_ajax))
    {
        //var_dump($_SESSION);
        
        // How the date for data are formated (format used bu jsgantt)
        $dateformatinput = 'yyyy-mm-dd';
        // How the date for data are formated (format used by dol_print_date)
        $dateformatinput2 = 'standard';
        //var_dump($dateformatinput);
        //var_dump($dateformatinput2);
       //print_r($tasks[3]);exit;
        print '<br>';
        
        print '<div class="div-table-responsive">';
        
        print '<div id="tabs" class="gantt" style="width: 80vw;">'."\n";
        include_once DOL_DOCUMENT_ROOT.'/custom/lltrucks/tpl/ganttchart.inc.php';
        print '</div>'."\n";
        
        print '</div>';
    }
    else
    {
        $langs->load("admin");
        print $langs->trans("AvailableOnlyIfJavascriptAndAjaxNotDisabled");
    }
}
else
{
    print '<div class="opacitymedium">'.$langs->trans("NoTasks").'</div>';
}
?>
<script type="text/javascript">
function edittask(id,parent) {
	$div = $('<div id="taskdlg"  title="<?php print $langs->trans('mise a jour des taches'); ?>"><iframe width="100%" height="100%" frameborder="0" src="<?php print dol_buildpath('/lltrucks/tpl/taskdlg.php?id=',1); ?>'+id +'"></iframe></div>');
	$div.dialog({
		modal:true
		,width:"600px"
		,height:$(window).height() - 500
		,close:function() {document.location.href='<?php echo dol_buildpath('/lltrucks/tpl/gant_total.php',2).'?id=1&open=';?>'+parent;}
		//,close:function() {document.location.reload(true);}
	});
};


</script>

<?php php;
// End of page
llxFooter();
$db->close();

?>