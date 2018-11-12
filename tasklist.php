<?php
require_once("config.php");
include(TEMPLATE.DS."header.php");
$username="John Smith";
//if post is set, update database then clear post
//avoiding repetitive form submission
if ($_POST) {
  // Execute code (such as database updates) here.
  foreach ($_POST as $key => $value) {
    if ($value!=-1){
      global $connection;
      $query = $connection->prepare("UPDATE nodes SET duration=? WHERE ID=?");
      $query->bind_param("ii",$value,intval(substr($key,7)));
      if ($query->execute()) {
      } else {
        echo "Error while updating records!";
      }
      
    }
  }
  // Redirect to this page.
  header("Location: " . $_SERVER['REQUEST_URI']);
  exit();
}

?>

<div class="container well">
  <h3>Showing tasks for: <?php echo $username; ?></h3>
  <?php
  $innerhtml="";
  //getting and setting the tasks table
  $tasks = getRowsOfQuery("SELECT n.nodeID, n.txt, n.description, concat(p.processName,' (',proj.projectName,')'),'under development',
     concat(prof.professionName,' (',prof.seniority,')'), n.status, n.RACI, n.duration,'under dev','under development.'
	 ,'under development..','under development...','under development....',n.priority,'sample log','blank','two buttons..',n.ID
		FROM nodes AS n
		LEFT JOIN persons AS rp
			ON n.responsiblePersonID=rp.ID 
		LEFT JOIN processes AS p
			ON n.processID=p.ID
		LEFT JOIN projects proj
			ON p.projectID=proj.ID
    LEFT JOIN professions prof
      ON prof.ID=n.professionID
		WHERE rp.personName='".$username."'
    ORDER BY n.status DESC, n.priority DESC");
  if (count($tasks)>1){
    $innerhtml.=getTableHeader(array("ID","Name","Description","Process (project)","Profession","Inputs",
	  "Status","RACI","Estimated duration","Planned start","Actual start","Planned finish","Actual finish",
	  "Last update at","Priority","Sytem message","Deliverable(s)","Start / Finish"),"tasksTable");
    for ($i=0;$i<count($tasks)-1;$i++) {
      $curTask=explode("|",$tasks[$i]);
      $innerhtml.="<tr>";
      //-1 because n.ID will not be displayed
      for ($n=0;$n<count($curTask)-1;$n++) {
        $innerhtml.="<td>";
        switch ($n) {
          case 3:
            $innerhtml.='<a href="manage.php?auth=u" data-toggle="tooltip" 
            	title="Create recommendation!">'.$curTask[$n]."</a>";break;
          case 6:
            $innerhtml.=getStatusText($curTask[$n]);break;
          case 7:
            $innerhtml.=getRACItext($curTask[$n]);
            break;
          case 8:
            $innerhtml.='<form action="'.htmlspecialchars($_SERVER["PHP_SELF"]).'" method="post">'.
                '<input type="number" name="taskDur'.$curTask[count($curTask)-1].'" min="1" value="'.($curTask[$n]==NULL?"1":$curTask[$n]).'">'.
                ' <input type="submit" class="btn btn-default" value="Submit"></form>';
            break;
          default:
            $innerhtml.=$curTask[$n];
        }
        $innerhtml.="</td>";
      }
      $innerhtml.="</tr>";
    }
    $innerhtml.="</tbody></table></div>";
  }

  echo $innerhtml;
    
    ?>

</div>


<?php include(TEMPLATE.DS."footer.php")?>