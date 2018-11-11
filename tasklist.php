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
  $tasks = getRowsOfQuery("SELECT n.nodeID, n.txt, n.status, n.duration, n.RACI,n.ID
		FROM nodes AS n
		LEFT JOIN persons AS rp
			ON n.responsiblePersonID=rp.ID 
		LEFT JOIN processes AS p
			ON n.processID=p.ID
		LEFT JOIN projects
			ON p.projectID=projects.ID
		WHERE rp.personName='".$username."'
    ORDER BY n.status DESC, n.priority DESC");
  if (count($tasks)>1){
    $innerhtml.=getTableHeader(array("ID","Name","Status","Duration","RACI"),"tasksTable");
    for ($i=0;$i<count($tasks)-1;$i++) {
      $curTask=explode(",",$tasks[$i]);
      $innerhtml.="<tr>";
      //-1 because n.ID will not be displayed
      for ($n=0;$n<count($curTask)-1;$n++) {
        $innerhtml.="<td>";
        switch ($n) {
          case 3:
            $innerhtml.='<form action="'.htmlspecialchars($_SERVER["PHP_SELF"]).'" method="post">'.
                '<input type="number" name="taskDur'.$curTask[count($curTask)-1].'" min="1" value="'.($curTask[3]==NULL?"1":$curTask[3]).'">'.
                ' <input type="submit" class="btn btn-default" value="Submit"></form>';
            break;
          case 4:
            $innerhtml.=getRACItext($curTask[4]);
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