<!-- BOOTSTRAP -->
<script type="text/javascript" src="//cdn.jsdelivr.net/jquery/1/jquery.min.js"></script>
<script type="text/javascript" src="//cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
<link rel="stylesheet" type="text/css" href="//maxcdn.bootstrapcdn.com/bootstrap/3.3.1/css/bootstrap.min.css" />
<script type="text/javascript" src="//maxcdn.bootstrapcdn.com/bootstrap/3.3.1/js/bootstrap.min.js"></script>
<link rel="stylesheet" type="text/css" href="styles/preference_style.css" />


<div class="container">
	<!-- nav bar -->
	<div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
		<ul class="nav navbar-nav">
			<li id="preference">
				<form class="navbar-form navbar-left" role="search"
				method="POST" action="index.php" name="back">
					<input type="submit" class="btn btn-default" name="back" value="Back"/>
				</form>
			</li>
		</ul>
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
	<!-- navbar end -->

	<!-- Jumbotron start-->
	<div style="background:transparent !important" class="jumbotron">
		<h1>Gravit-e User Preference</h1> 
    </div> 
	<!-- Jumbotron end-->
</div>

<!-- SQLite initialization -->
<?php

if(isset($_SESSION['user_name'])) {
	ob_start();
	$db_type = "sqlite";
	$db_sqlite_path = "./users.db";
	$db_connection = null;
	$domainName = "";
	$msg = "";
	try {
		$this->db_connection = new PDO($this->db_type . ':' . $this->db_sqlite_path);
	} catch (PDOException $e) {
		$this->feedback = "PDO database connection problem: " . $e->getMessage();
	} catch (Exception $e) {
		$this->feedback = "General problem: " . $e->getMessage();
	}
	
	//Get User id
	$sql = 'SELECT user_id, user_name
					   FROM users
					   WHERE user_name = :user_name';
	$query = $this->db_connection->prepare($sql);
	$query->bindValue(':user_name', $_SESSION['user_name']);
	$query->execute();
	$row = $query->fetch();
	$user_id = $row['user_id'];
	
	//Get count from userpreference
	$sql = 'SELECT count(*)
			FROM userPreference
			WHERE user_id = :user_id';
	$query = $this->db_connection->prepare($sql);
	$query->bindValue(':user_id', $user_id);
	$query->execute();
	$row = $query->fetch();
	$counter = $row[0];
	
	//get count from domain
	$sql = 'SELECT count(*)
			FROM domain';
	$query = $this->db_connection->prepare($sql);
	$query->execute();
	$row = $query->fetch();
	$counter_domain = $row[0];
	
	//make preference change to userPreference database
	if(isset($_POST['apply'])){
		header("index.php");
		for($i=0; $i<$counter; $i++){
			$domain = $_POST['domainValue'] [$i];
			if($_POST['proderror'] [$i] == "1"){
				$proderrorValue = 1;	
			}
			else if($_POST['proderror'] [$i] == "0"){
				$proderrorValue = 0;
			}
			if($_POST['hproderror'] [$i] == "1"){
				$hproderrorValue = 1;
			}
			else if($_POST['hproderror'] [$i] == "0"){
				$hproderrorValue = 0;
			}
			if($_POST['warning'] [$i] == "1"){
				$warningValue = 1;
			}
			else if($_POST['warning'] [$i] == "0"){
				$warningValue = 0;
			}
			$sql = 'UPDATE userPreference 
					SET prod_error = :perror, hprod_error = :hperror, warning = :warning
					WHERE user_id = :user_id AND domain_name = :domain_name';
			$query = $this->db_connection->prepare($sql);
			$query->bindValue(':perror', $proderrorValue);
			$query->bindValue(':hperror', $hproderrorValue);
			$query->bindValue(':warning', $warningValue);
			$query->bindValue(':user_id', $user_id);
			$query->bindValue(':domain_name', $domain);
			$query->execute();		
		}	
	}
	
	//make preference change to domain database
	if(isset($_POST['domainmute_apply'])){
		header("index.php");
		for($i=0; $i<$counter_domain; $i++){
			$domain = $_POST['domainValue'] [$i];
			if($_POST['proderror'] [$i] == "1"){
				$proderrorValue = 1;	
			}
			else if($_POST['proderror'] [$i] == "0"){
				$proderrorValue = 0;
			}
			if($_POST['hproderror'] [$i] == "1"){
				$hproderrorValue = 1;
			}
			else if($_POST['hproderror'] [$i] == "0"){
				$hproderrorValue = 0;
			}
			if($_POST['warning'] [$i] == "1"){
				$warningValue = 1;
			}
			else if($_POST['warning'] [$i] == "0"){
				$warningValue = 0;
			}
			$sql = 'UPDATE domain 
					SET prod_mute = :perror, hprod_mute = :hperror, warning_mute = :warning
					WHERE domain_name = :domain_name';
			$query = $this->db_connection->prepare($sql);
			$query->bindValue(':perror', $proderrorValue);
			$query->bindValue(':hperror', $hproderrorValue);
			$query->bindValue(':warning', $warningValue);
			$query->bindValue(':domain_name', $domain);
			$query->execute();		
		}	
	}
	
	//delete preference from database
	if(isset($_POST['delete'])){
		for($i=0; $i<$counter; $i++){
			if($_POST['sel'] [$i] == "1"){
				$domain = $_POST['domainValue'] [$i];
				$msg .= $domain . "<br>";
				$sql = 'DELETE FROM userPreference
						WHERE domain_name = :getDomain
						AND user_id = :userid';
				$deleteQuery = $this -> db_connection->prepare($sql);
				$deleteQuery->bindValue(':getDomain', $domain);
				$deleteQuery->bindValue(':userid', $user_id);
				$deleteQuery->execute();
			}
		}
	}
	
	//globally mute domains for all users
	if(isset($_POST['domainmute'])){
		
		for($i=0; $i<$counter_domain; $i++){
			if($_POST['sel'] [$i] == "1"){
				$domain = $_POST['domainValue'] [$i];
				$msg .= $domain . "<br>";
				$sql = 'DELETE FROM domain
						WHERE domain_name = :getDomain';
				$deleteQuery = $this -> db_connection->prepare($sql);
				$deleteQuery->bindValue(':getDomain', $domain);
				$deleteQuery->execute();
			}
		}
	}
		
	if(isset($_POST['globalmute'])){
		$domain_string=$_POST['domainText'];
		//echo $domain_string;
		$sql = 'SELECT domain_name
				FROM domain
				WHERE domain_name = :domain_string';
		$query = $this->db_connection->prepare($sql);
		$query->bindValue(':domain_string', $domain_string);
        $query->execute();
		$row = $query->fetch();
		if($row['domain_name'] == $domain_string){
			echo "Domain already exists in your preference.";
		}else{
			$sql = 'INSERT INTO domain (domain_name, prod_mute, hprod_mute, warning_mute)
						VALUES(:domain_name, :prod_mute, :hprod_mute, :warning_mute)';
			$query = $this->db_connection->prepare($sql);
			$query->bindValue(':domain_name', $domain_string);
			$query->bindValue(':prod_mute', 1);
			$query->bindValue(':hprod_mute', 1);
			$query->bindValue(':warning_mute', 1);
			$query->execute();
		}
		
	}
	if(isset($_POST['mute'])){
		$sql = 'SELECT mute
				FROM users 
				WHERE user_id = :user_id';
		$query = $this->db_connection->prepare($sql);
		$query->bindValue(':user_id', $user_id);
		$query->execute();
		$row = $query->fetch();
		if($row['mute'] == 0){ 
			$user_mute_vaariable = 1; 
		}else{
			$user_mute_vaariable = 0;
		}
		
		$sql = 'UPDATE users 
			SET mute = :mutevalue
			WHERE user_id = :user_id';
		$query = $this->db_connection->prepare($sql);
		$query->bindValue(':user_id', $user_id);
		$query->bindValue(':mutevalue', $user_mute_vaariable);
		$query->execute();
	}
	
	//Get User id
	$sql = 'SELECT user_id, mute
					   FROM users
					   WHERE user_id = :user_id';
	$query = $this->db_connection->prepare($sql);
	$query->bindValue(':user_id', $user_id);
	$query->execute();
	$row = $query->fetch();
	$user_mute = $row['mute'];

	if(isset($_POST['add'])){
		$domain_string=$_POST['domainText'];
		//echo $domain_string;
		$sql = 'SELECT domain_name
				FROM userPreference
				WHERE user_id = :user_id AND domain_name = :domain_string';
		$query = $this->db_connection->prepare($sql);
		$query->bindValue(':domain_string', $domain_string);
        $query->bindValue(':user_id', $user_id);
        $query->execute();
		$row = $query->fetch();
		if($row['domain_name'] == $domain_string){
			echo "Domain already exists in your preference.";
		}
		else{
			$sql = 'INSERT INTO userPreference (user_id, domain_name, prod_error, hprod_error, warning)
						VALUES(:user_id, :domain_name, :prod_error, :hprod_error, :warning)';
			$query = $this->db_connection->prepare($sql);
			$query->bindValue(':user_id', $user_id);
			$query->bindValue(':domain_name', $domain_string);
			$query->bindValue(':prod_error', 0);
			$query->bindValue(':hprod_error', 0);
			$query->bindValue(':warning', 0);
			$query->execute();
			
			//this refresh will allow the domain name drop down to populate right away
		}
	}
	
?>
<!--SQlite end -->


<div class="container">
	<!-- Form Start-->
	<?php if(isset($_POST['apply'])){ ?>
		<div class="alert alert-success">
			<strong>Success!</strong> Changes have been made.
		</div>
	<?php } ?>
	
	<?php if(isset($_POST['delete'])){ ?>
		<div class="alert alert-success">
			<strong>Success! Removed following domains<br></strong> 
			<i> <?php echo $msg;?> </i>
		</div>
	<?php } ?>
	
	<?php if(isset($_POST['domainmute'])){ ?>
		<div class="alert alert-success">
			<strong>Success! Removed following domains<br></strong> 
			<i> <?php echo $msg;?> </i>
		</div>
	<?php } ?>
	<?php if(isset($_POST['domainmute_apply'])){ ?>
		<div class="alert alert-success">
			<strong>Success!</strong> Changes have been made.
		</div>
	<?php } ?>
	<form method="POST" action="index.php?action=userPreference" name="mutePost">
		<div class="row" style= "margin-bottom: 20px">
			<div class="form-inline">
				<div class="col-xs-12">
					<label for="mute" style="margin-right:20px">
						<?php 
							$sql = 'SELECT user_id, mute
								FROM users
								WHERE user_id = :user_id';
							$query = $this->db_connection->prepare($sql);
							$query->bindValue(':user_id', $user_id);
							$query->execute();
							$row = $query->fetch();
							$user_mute = $row['mute'];
							if($user_mute == 0){ 
								echo "Your account is currently receiving error notifications";
							}else{
								echo "Error notifications have been temporarily muted for your account";} 
						?>
					</label>
					<input type="submit" style="margin-right: 20px; width: 300px;" id="mute"
						name="mute" 
						<?php echo "value ="; 
							if($user_mute == 0){ 
								echo "Mute class='btn btn-danger'";
							}else{
								echo "Unmute class='btn btn-primary'";} 
						?>/>
				</div>
			</div>
		</div>
	</form>
		
	<!-- Form end-->

	<div>

  <!-- Nav tabs -->
  <ul class="nav nav-tabs" role="tablist">
	<li role="presentation" <?php if(!isset($_POST['globalmute'])){echo 'class="active"';}?>>
		<a href="#home" aria-controls="home" role="tab" data-toggle="tab">Preference</a>
	</li>
    <li role="presentation" <?php if(isset($_POST['globalmute'])){echo 'class="active"';} ?>>
		<a href="#profile" aria-controls="profile" role="tab" data-toggle="tab">Globally Muted Domains</a>
	</li>
  </ul>

  <!-- Tab panes -->
	<div class="tab-content">
		<div role="tabpanel" class="tab-pane <?php if(!isset($_POST['globalmute'])){echo "active";} ?>" 
		  id="home" style="margin-top: 20px;">
			<form method="POST" action="index.php?action=userPreference" name="addDomain">
				<!-- add domain to userPreference and domain table -->
				<label> Add Notification Ovveride </label>
				<div class="row" style="margin-bottom: 20px;">
					<div class="col-xs-6">
						<input style="width: 100%;" name='domainText' type='text'
							   class ="form-control" placeholder="Add Domain Name" autofocus>
					</div>
					<input type="submit" style="margin-right: 20px;" 
						   class="btn btn-default col-xs-2" name="add" value="Add"/>
				</div>
			</form>
		<form method="POST" action="index.php?action=userPreference" name="prefForm">
			<table id="pref_table" class="table table-striped table-hover">
			<thead>
			  <tr>
				<th class="center_row" style="width: 5%;">sel</th>
				<th style="width: 20%;">Domain Name</th>
				<th class="center_row" style="width: 25%;">Production Error</th>
				<th class="center_row" style="width: 25%;">Handled Produc Error</th>
				<th class="center_row" style="width: 25%;">Warning</th>
			  </tr>
			</thead>
			
			<tbody>
			<!-- SQLite fetch user preference-->
			<?php 
				$sql = 'SELECT domain_name, prod_error, hprod_error, warning
						FROM userPreference
						WHERE user_id = :user_id';
				$query = $this->db_connection->prepare($sql);
				$query->bindValue(':user_id', $user_id);
				$query->execute();
				$index = 0;
				while($row = $query->fetch()){
					$domainName = $row['domain_name'];
					$proderror = $row['prod_error'];
					$hproderror = $row['hprod_error'];
					$warning = $row['warning'];
			?>
			
				<tr>
					<td class="center_row" name="sel">
						<input type="hidden" name="sel[<?php echo $index ?>]" value="0">
						<input type="checkbox" name="sel[<?php echo $index ?>]" value="1">
						 
					</td>
					
					<td name="dname"><?php echo $domainName;?>
						<input type="hidden" name="domainValue[<?php echo $index ?>]" 
							value=<?php echo $domainName ?> />
					</td>
					<td class="center_row">
						<input type="hidden" name="proderror[<?php echo $index ?>]" value="0">
						<input type="checkbox" name="proderror[<?php echo $index ?>]" value="1" 
							<?php if($proderror==1){echo 'checked';}?>/>
					</td>
					<td class="center_row">
						<input type="hidden" name="hproderror[<?php echo $index ?>]" value="0">
						<input type="checkbox" name="hproderror[<?php echo $index ?>]" value="1" 
							<?php if($hproderror==1){echo 'checked';}?>/>
					</td>
					<td class="center_row">
						<input type="hidden" name="warning[<?php echo $index ?>]" value="0">
						<input type="checkbox" name="warning[<?php echo $index ?>]" value="1" 
							<?php if($warning==1){echo 'checked';}?>/>
					</td>
				</tr>
				<?php $index++;}?>
			</tbody>	
			</table>
			<!--SQlite Fetch end. End of table-->
			<input type="submit" 
				   class="btn btn-success col-xs-2" 
				   style="margin-right: 20px;" name="apply" value="Apply"/>
			
			<input type="submit" class="btn btn-danger col-xs-2" 
				   style="margin-right: 20px;" name="delete" value="Delete"/>
							
		</form>
		</div>
		
		<!-- mute panel-->
		<div role="tabpanel" class="tab-pane 
		  <?php if(isset($_POST['globalmute'])){echo "active";} ?>" 
		  id="profile" style="margin-top: 20px;">
			<form method="POST" action="index.php?action=userPreference" name="addDomain">
				<!-- add domain to userPreference and domain table -->
				<label> Add Domain to mute globally </label>
				<div class="row" style="margin-bottom: 20px;">
					<div class="col-xs-6">
						<input style="width: 100%;" name='domainText' type='text'
							   class ="form-control" placeholder="Add Domain Name" autofocus>
					</div>
					<input type="submit" style="margin-right: 20px;" 
						   class="btn btn-default col-xs-2" name="globalmute" value="Add"/>
				</div>
			</form>
			<form method="POST" action="index.php?action=userPreference" name="prefForm">
				<table id="pref_table" class="table table-striped">
					<thead>
						<tr>
							<th class="center_row" style="width: 5%;">sel</th>
							<th style="width: 20%;">Domain Name</th>
							<th class="center_row" style="width: 25%;">Production Error</th>
							<th class="center_row" style="width: 25%;">Handled Produc Error</th>
							<th class="center_row" style="width: 25%;">Warning</th>
						</tr>
					</thead>
					<tbody>
					<!-- SQLite fetch user preference-->
					<?php 
						$sql = 'SELECT domain_name, prod_mute, hprod_mute, warning_mute
								FROM domain';
						$query = $this->db_connection->prepare($sql);
						$query->execute();
						$index = 0;
						while($row = $query->fetch()){
							$domainName = $row['domain_name'];
							$proderror = $row['prod_mute'];
							$hproderror = $row['hprod_mute'];
							$warning = $row['warning_mute'];
					?>
						<tr>
							<td class="center_row" name="sel">
								<input type="hidden" name="sel[<?php echo $index ?>]" value="0">
								<input type="checkbox" name="sel[<?php echo $index ?>]" value="1">
								 
							</td>
							
							<td name="dname"><?php echo $domainName;?>
								<input type="hidden" name="domainValue[<?php echo $index ?>]" 
									value=<?php echo $domainName ?> />
							</td>
							<td class="center_row">
								<input type="hidden" name="proderror[<?php echo $index ?>]" value="0">
								<input type="checkbox" name="proderror[<?php echo $index ?>]" value="1" 
									<?php if($proderror==1){echo 'checked';}?>/>
							</td>
							<td class="center_row">
								<input type="hidden" name="hproderror[<?php echo $index ?>]" value="0">
								<input type="checkbox" name="hproderror[<?php echo $index ?>]" value="1" 
									<?php if($hproderror==1){echo 'checked';}?>/>
							</td>
							<td class="center_row">
								<input type="hidden" name="warning[<?php echo $index ?>]" value="0">
								<input type="checkbox" name="warning[<?php echo $index ?>]" value="1" 
									<?php if($warning==1){echo 'checked';}?>/>
							</td>						
						</tr>
					<?php $index++;}?>
					</tbody>
				</table>
					<input type="submit" class="btn btn-success col-xs-2" name="domainmute_apply" 
						style="margin-right: 20px;" value= "Apply"/>
					<input type="submit" class="btn btn-warning col-xs-2" name="domainmute" 
						value= "Remove"/>
			</form>
		</div>
	</div>

</div>
	
 
<?php	
}
?>
</div>

