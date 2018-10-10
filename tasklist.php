<?php
require_once("config.php");
include(TEMPLATE.DS."header.php");
?>

<div class="container well" id="nameFormDiv">
	<form id="nameForm" class="form-horizontal">
    <div class="form-group">
      <label class="control-label col-sm-2" for="name">Your name:</label>
      <div class="col-sm-10">
        <input type="name" class="form-control" placeholder="Michael Jackson" id="personName" value="John Smith">
      </div>
    </div>
    <div class="form-group">        
      <div class="col-sm-offset-2 col-sm-10">
        <button type="button" class="btn btn-default" onclick="getTasks()">Submit</button>
      </div>
    </div>
  </form>
  <div id="tasksTable"></div>
</div>




<?php include(TEMPLATE.DS."footer.php")?>