<?php 
require_once("config.php");
include(TEMPLATE.DS."header.php");
?>

<div class="container">
	<div class="well">
		<h1>Line manager</h1>
		<hr>
		<div id='infoBox'></div>
		<div class="row">
			<div class="col-sm-3 form-inline">
				<label for="personName"><b>Person name:</b></label>
				<input type="text" class="form-control" id="personName">
			</div>
			<div class="col-sm-3 form-inline">
				<label for="profession"><b>Profession:</b></label>
				<input list='professions' class="form-control" id="profession">
				<datalist id="professions">
					<?php
					$professions = getRowsOfQuery("SELECT professionName p FROM professions GROUP BY p");
					for ($i=0; $i < count($professions)-1; $i++) {
						echo "<option value='{$professions[$i]}'>";
					}
					?>
				</datalist>
			</div>
			<div class="col-sm-2 form-inline">
				<label for="seniority"><b>Seniority:</b></label>
				<select class="form-control" id="seniority">
					<option></option>
					<option>junior</option>
					<option>senior</option>
					<option>professional</option>
				</select>
			</div>
			<div class="col-sm-4 form-inline">
				<button class="btn btn-primary btn-lg" onclick="addPerson()">
					<span class="glyphicon glyphicon-plus"></span> Add person
				</button> 
				<button class="btn btn-danger btn-lg" onclick="removePerson()">
				<span class="glyphicon glyphicon-trash"></span> Remove person
				</button>
			</div>
		</div>
		<br>
		<?php 
		$persons = getRowsOfQuery("SELECT personName,professionName,seniority,per.ID FROM persons per
		JOIN professions prof ON per.professionID=prof.ID");
		echo getTableHeader(["Person name","Profession","Seniority"],"personsTable");
		for ($i=0; $i < count($persons)-1; $i++) { 
			$curPerson = explode("|",$persons[$i]);
			$curID = $curPerson[3];
			echo "<tr id='person$curID' onclick='choosePerson($curID)'>";
			for ($j=0; $j < 3; $j++) { 
				echo "<td>".$curPerson[$j]."</td>";
			}
			echo "</tr>";
		}
		echo "</tbody></table></div>";
		?>
	</div>
</div>

<?php include(TEMPLATE.DS."footer.php") ?>