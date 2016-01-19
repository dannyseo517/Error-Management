<!-- This script will aggregate data for the amchart -->

<?php
	
	$connection = new MongoClient();
	$db = $connection->grav_errors;
	$collection = $db->errors;
		
	$collection2 = $db->aggregatederr;
	$cursor = $collection->find();
	
	//delete whats already inside to prevent continuously adding
	$response = $collection2->drop();
	
	//date initialization
	$startdate = $_GET['startdate'];
	$enddate = $_GET['enddate'];
	$dm = $_GET['searchstring'];

	foreach($cursor as $document){ 
		//if time range search
		if($document['Timestamp'] < $enddate && $document['Timestamp'] > $startdate){
			$domain = $document['DomainName'];
			$count = 0;
			$error_type;
			if(strpos($document['ErrorType'], 'Warning') !== false){
				$error_type = "Warning";
				$count = 1;
			}else if(strpos($document['EmailSubject'], 'Handled Prod') !== false){
				$error_type = "Handled Prod";
				$count = 1;
			}else{
				$error_type = "Prod";
				$count = 1;
			}
			
			//if domain name search
			if($dm != ""){
				if(strpos($domain, $dm) !== false){
					$collection2->insert(array("domain"=>$domain,
								   "error_type" => $error_type,
								   "count" => $count,));
				}
			}else{
				$collection2->insert(array("domain"=>$domain,
								   "error_type" => $error_type,
								   "count" => $count,));
			}
		}
		//if not time range search
		if($startdate == ""){
			
			$domain = $document['DomainName'];
			$count = 0;
			$error_type;
			if(strpos($document['ErrorType'], 'Warning') !== false){
				$error_type = "Warning";
				$count = 1;
			}else if(strpos($document['EmailSubject'], 'Handled Prod') !== false){
				$error_type = "Handled Prod";
				$count = 1;
			}else{
				$error_type = "Prod";
				$count = 1;
			}
			//if domain name search
			if($dm != ""){
				if(strpos($domain, $dm) !== false){
					$collection2->insert(array("domain"=>$domain,
								   "error_type" => $error_type,
								   "count" => $count,));
				}
			}else{
				$collection2->insert(array("domain"=>$domain,
								   "error_type" => $error_type,
								   "count" => $count,));
			}
		}
	}

	$aggr_col = $collection2->aggregate(
		[
			[ '$group' => [ '_id' => ['error_type' => '$error_type'], 
							'count' => [ '$sum' => '$count' ] 
						  ] 
			],
		]
		
	);

	
	$prefix = '';
	echo "[\n";
	$row = 0;
	foreach($aggr_col as $aggr){ 
		foreach($aggr as $doc){
		
		echo $prefix . " {\n";
		echo '  "type":  "'. $doc['_id']['error_type'] . '",' . "\n";
		echo '  "count": '. $doc['count']  . "," . "\n";
        
		echo " }";
		$prefix = ",\n";

		}
	}
	echo "\n]";
	//$response = $collection2->drop();
?>