<!-- BOOTSTRAP -->
<script type="text/javascript" src="//cdn.jsdelivr.net/jquery/1/jquery.min.js"></script>
<script type="text/javascript" src="//cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
<!-- Include Date Range Picker -->
<script type="text/javascript" src="//cdn.jsdelivr.net/bootstrap.daterangepicker/2/daterangepicker.js"></script>
<link rel="stylesheet" type="text/css" href="//cdn.jsdelivr.net/bootstrap.daterangepicker/2/daterangepicker.css" />
<link rel="stylesheet" type="text/css" href="//maxcdn.bootstrapcdn.com/bootstrap/3.3.1/css/bootstrap.min.css" />
<!-- Stylesheet -->
<link rel="stylesheet" type="text/css" href="styles/database_style.css" />
<script type="text/javascript" src="//maxcdn.bootstrapcdn.com/bootstrap/3.3.1/js/bootstrap.min.js"></script>

<!-- chart scripts -->
<script src="chartScripts/amcharts/amcharts.js" type="text/javascript"></script>
<script src="chartScripts/amcharts/serial.js" type="text/javascript"></script>
<script src="http://www.amcharts.com/lib/3/plugins/dataloader/dataloader.min.js" type="text/javascript"></script>

<?php
	if(isset($_SESSION['user_name'])) {
		date_default_timezone_set('America/Los_Angeles');
		
		$connection = new MongoClient();
		$db = $connection->grav_errors;
		$collection = $db->errors;
		
		
		if(isset($_POST['search'])){
			$search_string=$_POST['filter'];
			if(isset($_POST['checkdate'])){
				$date_input = $_POST['daterangepicker'];
				$date = explode(" - ", $date_input);
				$start_date = strtotime($date[0]);
				$end_date = strtotime('+1 day', strtotime($date[1]));
				$searchQuery = array(
				'$and' => array(
					array(
						'DomainName' => new MongoRegex("/$search_string/i"),
						),
					array(
						"Timestamp" =>array('$gt'=> $start_date, '$lt' => $end_date),
						),
					)
				);
			}else{
				$searchQuery = array('DomainName' => new MongoRegex("/$search_string/i"));
			}
			
			
			
			$cursor = $collection->find($searchQuery);
		}else{
			$current_date =  date("m/d/Y");
			$begin = strtotime($current_date);
			$end = strtotime('+1 day', $begin);
			$searchQuery = array("Timestamp" =>array('$gt'=> $begin, '$lt' => $end));
			$cursor = $collection->find($searchQuery);
		}
?>

<div class="container">
	
	<!-- nav bar -->
	<div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
		<ul class="nav pull-right">
			<li id="preference">
				<form class="navbar-form navbar-right" role="search"
					  method="POST" action="index.php?action=userPreference" name="userPreference">
					<input type="submit" class="btn btn-default" name="userPreference" value="Preference"/>
				</form>
			</li>
		</ul>
		<ul class="nav pull-right">				
			<li id="preference">
				<form class="navbar-form navbar-right" role="search"
				method="POST" action="index.php?action=logout" name="logout">
					<label style = "margin-right: 15px;">Hello <?php echo $_SESSION['user_name']?>! </label>
					<input type="submit" class="btn btn-default" name="logout" value="Logout"/>
				</form>
			</li>			
		</ul>
	</div>
	<!-- /.navbar-collapse -->


	<div style="background:transparent !important" class="jumbotron">
		<h1>Gravit-e Error Database</h1> 
    </div> 
</div>
						
<div class='container'>

	<nav class="navbar navbar-default">
		<div class="container-fluid">
			<div class="navbar-header">
				<form class="navbar-form navbar-left" name = "search_form" method="POST" action="index.php">
				
					<input type="checkbox" name="checkdate" >
					Date Range: <input type="text" name="daterangepicker"
									   style="margin-right: 20px;"
									   id = <?php echo date('m/d/Y - m/d/Y');?>/>
					Search: <input name='filter' type='text'>
					<input class ="btn btn-success"type="submit" name="search" value="search">
				</form>
			</div>
		</div>
	</nav>
	<div style="height:600px;overflow:auto;">
		<table class='table table-striped table-bordered table-hover'>
		<div class="container-fluid">
			<thead>
				<tr>
					<th>Date</th>
					<th>Domain Name</th>
					<th>Error Message</th>
				</tr>
			</thead>
		<tbody>
	

<?php
	//try to add data to table
	try{
        foreach($cursor as $document){ 
?>
			
            <tr class = 'popup' id = <?php echo $document['_id']?> data-target="#addModal">
				<td>  <?php echo Date('m/d/Y H:i:s', $document["Timestamp"]); ?> </td>
				<td> <?php echo $document["DomainName"]; ?> </td>
				<td> <?php $msg = $document['ErrorMessage']; echo substr($msg, 0, 70);?> </td>
            </tr>
			
			
			<div class="modal" id= <?php echo $document['_id']?> tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
				<div class="modal-dialog">
					<div class="modal-content">
						<div class="modal-header">
								<div id = "errorDetails" class="modal-body">
									<?php
										print_r($document["EmailMessage"]);
									?>
								</div>
							</div>
						<div class="modal-footer">
							<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
						</div>
					</div>
				</div>
			</div>

<?php }
	}catch(MongoException $mongoException){
		print $mongoException;
		exit;
    }
?>

	</tbody>
	</div>
	</table>
	</div>
</div>



<!-- Script -->
<script type="text/javascript">

	
	$(function() {
		var now = new Date();
		$('input[name="daterangepicker"]').daterangepicker();
		
		
	});
	
	$('.popup').on('click', function() {
		var getIdFromRow = $(this).attr('id');
		$('#'+getIdFromRow).modal('show');
		
		
    });

</script>
<script type="text/javascript">

</script>

<?php		
	}else {
		header("location: index.php");

		exit;
	}
?>