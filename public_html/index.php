<?php

	define('APIKEYS_DB_PATH','../apikeys/apikeys.csv');

	require '../vendor/autoload.php';

$app = new \Slim\Slim();
$app->get('/hello/:name/:surname', function ($p1,$p2) {
	echo "What, " . $p1 . " " . $p2 . "<br>";
	echo hash('sha256', 'test');

});

$app->get('/hello/:name', function ($p1) use(&$aa){
	echo "hello, " . $p1 . " <br>";
	echo hash('sha256', 'Test.');
	$tt = aa();
	echo $tt;

});


$app->get('/php/details', 't'); 
function t(){
	phpinfo();
	
}

$app->get('/api/currenttime/', function () use($app){

	$head = array();
	$val = array();
	$ind = 0;
	if ($_SERVER['REQUEST_METHOD'] != 'GET'){
			$app->halt(405);
	}
	
		foreach (getallheaders() as $source => $value) {
		$head[$ind] = strval($source);
		$val[$ind] = strval($value);
		$ind++;
		}
	$q1 = $_GET['source'];
	if ($head[3]!="apikey") {
			$app->halt(400,json_encode(array('status' => 1,'message' => 'Please specify API key.')));
	}
	if ($q1 == "") {
			$app->halt(400,json_encode(array('status' => 2,'message' => 'Please specify source.')));
	}

	$s1 = retrieveSource($val[3]);
	$a1 = retrieveUserInfo($val[3]);
	if ($csv = $a1 === FALSE){
			$app->halt(401,json_encode(array('status' => 1,'message' => 'Invalid API key.')));
	}
	if ($s1 != $q1){
			$app->halt(401,json_encode(array('status' => 2,'message' => 'Invalid source.')));
	}
	
   	echo time();
   	
});



$app->get('/api/test', 'zz');

function zz() {
   $val1 = retrieveSharedSecret('ce4f472f-3535-43f5-834e-482be10cf2bb');
   echo $val1;
   
};

function retrieveUserInfo($apikey) {
	$fh = fopen('../apikeys/apikeys.csv','r');
	try {
		do {
			$csv = fgetcsv($fh);
			if (!strcmp($apikey,$csv[0])) {
				return $csv;
			}
		} while($csv !== FALSE);
	} finally {
		fclose($fh);
	}
	return FALSE;
}
function retrieveSharedSecret($apikey) {
    return retrieveUserInfo($apikey)[1];
}
function retrieveSource($apikey) {
    return retrieveUserInfo($apikey)[2];
}
function calculateFingerprint($apikey, $secret, $timestamp, $method, $resourceUri, $data){
        return hash('sha256', "$apikey,$secret,$timestamp,$method,$resourceUri,$data" );
}


function fp($apikey, $secret, $timestamp, $nric, $amount, $date, $source, $method, $resourceUri)
{
        $fp = calculateFingerprint($apikey, $secret, $timestamp, $method, $resourceUri, "nric={$nric}&amount={$amount}&date={$date}&source={$source}");
        return $fp;
}



$app->get('/api/getfp', 'ab');

	function ab() {
		$timestamp = time();
		$f = hash('sha256', "12345,123," .$timestamp.",POST,/api/updates/,nric=nn123&amount=1655&date=20150917&source=cruise" );
		echo $timestamp."->>".$f;
		
		 
};



$app->post('/api/updates/', function () use($app){
	
	if ($_SERVER['REQUEST_METHOD'] != 'GET'){
		$app->halt(405);
	}
	
		$apikey = $app->request->headers->get('apikey');
		if (!strlen($apikey)) {
			$app->halt(400,json_encode(array('status' => 0,'message' => 'Please specify API key')));
		}
		if (($csv = retrieveUserInfo($apiKey)) === FALSE) {
			$app->halt(401,json_encode(array('status' => 0,'message' => 'Invalid API key')));
		}

		$timestamp = $app->request->headers->get('timestamp');

		if (!strlen($timestamp)) {
			$app->halt(400,json_encode(array('status' => 2,'message' => 'Please specify Timestamp')));
		}
		
		$nric = $request->post('nric');
		
		if (!strlen($nric)) {
			$app->halt(400,json_encode(array('status' => 3,'message' => 'Please specify Nric')));
		}

		$amount = round($request->post('amount'),2);
		
		if (!strlen($amount)){
			$app->halt(400,json_encode(array('status' => 4,'message' => 'Please specify amount')));
			}
			
		$date = $request->post('date');
		if (!strlen($date)){			
			$app->halt(400,json_encode(array('status' => 5,'message' => 'Please specify date')));
			}
			
		$source = $request->post('source');
		if (!strlen($source)){						
			$app->halt(400,json_encode(array('status' => 6,'message' => 'Please specify source')));
			}

		
		$resourceUri = $app->request->getResourceUri();	
		$secret = retrieveSharedSecret($apiKey);	
		$timestamp =  intval($timestamp);
		
		$terms = 0;
		$tsB = $timestamp - 90;
		$tsA =  $timestamp + 90;


		$request = $app->request;
		
		
		
		
		
		$fp = fp($apikey, $secret, $timestamp, $nric, $amount, $date, $source, 'POST', $resourceUri);
		
		if ($fp == $fingerprint){
			
			if ($timestamp>=$tsB && $timestamp<=$tsA)
			{

				$d = $date[6] . $date[7];
				$m = $date[4] . $date[5];
				$y = $date[0] . $date[1] . $date[2] . $date[3];

				if ($d>31 || $m>12)
					{echo  "Error on date! \n";
					}
				if($d>=25)
					{
					if($m==12)
					{
						$y = $y + 1;
						$m = "01";
					}
					elseif($m<12)
					{$m = $m + 1;
					$m = "0". $m;}
					}

				$titleD =  $y.$m;
				$title = "misatravel_" . $source . "_" . $titleD;


				$fd = fopen("../ntuc_files/".$title . ".csv", "a");
				$arr = array($nric, $date, $amount);
				fputcsv($fd, $arr);
				fclose($fd);
				echo 0;
				}
			else
			$app->halt(401,json_encode(array('status' => 3,'message' => 'Invalid Timestamp')));

			}
			else if ($fp!=$fingerprint)
			{
			$app->halt(401,json_encode(array('status' => 2,'message' => 'Invalid fingerprint')));}
			
			else if ($nric[0]>=0 || $nric[8]>=0 || $nric[1]/$nric[1] != 1 )
			{
			$app->halt(401,json_encode(array('status' => 4,'message' => 'Invalid Nric format')));}
			
			else if (is_int($amount) === TRUE)
			{
			$app->halt(401,json_encode(array('status' => 5,'message' => 'Invalid price format')));}
			
			else if ((preg_match("/^[0-9]{4}(0[1-9]|1[0-2])(0[1-9]|[1-2][0-9]|3[0-1])$/",$date) === FALSE)
			{
			$app->halt(401,json_encode(array('status' => 6,'message' => 'Invalid date format')));}
			
			$s1 = retrieveSource($apikey);
			else if ($s1 != $source)
			{
			$app->halt(401,json_encode(array('status' => 7,'message' => 'Invalid source')));}
		


	});
	$app->run();


