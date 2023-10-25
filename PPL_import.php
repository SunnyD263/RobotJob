<?php
set_time_limit(3600);
$startTime = microtime(true);
$RowHunt = 0;
$CloseParcel = 0;
$RowInsert=0;

$PPL = file_get_contents('http://localhost/ppl.txt');
$items = explode(';', $PPL);
$clientID = $items[0];
$clientSecret = base64_decode($items[1]);
$proxy = $items[2];

$tokenUrl = "https://api.dhl.com/ecs/ppl/myapi2/login/getAccessToken";
$grantType = "client_credentials";
$scope = "myapi2";


$data = array(
    'grant_type' => $grantType,
    'client_id' => $clientID,
    'client_secret' => $clientSecret,
    'scope' => $scope
);

$options = array(
    'http' => array(
        'header'  =>    "Content-type: application/x-www-form-urlencoded\r\n" .
                        "Cache-Control: no-cache\r\n" .
                        "Accept-Encoding: gzip, deflate, br\r\n" .
                        "Host: api.dhl.com",

        'method'  => 'POST',
        'content' => http_build_query($data),
        'proxy' => $proxy,
        'request_fulluri' => true, 
    ),
);

$context = stream_context_create($options);
$response = file_get_contents($tokenUrl, false, $context);

if ($response === false) {
    die("Chyba při získávání tokenu.");
}

$tokenData = json_decode($response, true);

if (isset($tokenData['access_token'])) {
    $accessToken = $tokenData['access_token'];
} else {
    die("Chyba při získávání tokenu.");
}


$options = array(
    'http' => array(
        'header' => "Authorization: Bearer $accessToken\r\n".
                    "Content-type: application/x-www-form-urlencoded\r\n" .
                    "Cache-Control: no-cache\r\n" .
                    "Accept-Encoding: gzip, deflate, br\r\n" .
                    "Host: api.dhl.com",
        'method' => 'GET',
        'content' => json_encode($data),
        'proxy' => $proxy,
        'request_fulluri' => true
    )
);

//connection setting
if (!isset($Connection)){$Connection = new PDOConnect("DPD_DB");} 
//select packeta parcelnumbers 
$SQL=  "SELECT [PARCELNO] FROM [DPD_DB].[dbo].[PD2] where len(PARCELNO) = 11 and [Update] IS null order by EVENT_DATE_TIME desc";
$stmt = $Connection->select($SQL);
$Counter = $stmt['count'];
if($Counter!==0)
    {
    $rows = $stmt['rows'];
    foreach ($rows as $row )
        {
        $ParcelID =$row['PARCELNO'];       
        $RowHunt++;

        $apiUrl ="https://api.dhl.com/ecs/ppl/myapi2/shipment?Limit=1000&Offset=0&ShipmentNumbers=$ParcelID";
        $context = stream_context_create($options);
        $response= json_decode( file_get_contents($apiUrl, false, $context));
        if ($response !== null) 
            {
            foreach($response as $key)
                {
                $ZIP = $key->recipient->zipCode;
                $TaT = $key->trackAndTrace->events;
                if (isset($key->recipient->name2))
                    {
                    $Recepient=$key->recipient->name2;
                    }
                else
                    {
                    $Recepient=$key->recipient->name;
                    }
                $Reference = $key->externalNumbers[0]->externalNumber;
                foreach($TaT as $event)
                    {
                        $ScanCode = $event->statusId;
                        $DateTime =  date("Y-m-d H:i:s", strtotime($event->eventDate));

                        $SQL=  "SELECT count([PARCELNO]) as Counter FROM [DPD_DB].[dbo].[PMIdb] where ([PARCELNO] = :parcelno) and ([EVENT_DATE_TIME] = :DaTi)";
                        $params = array(':parcelno' => $ParcelID,  ':DaTi' => $DateTime );
                        $CounterResult = $Connection->select($SQL,$params );
                        $Counter= $CounterResult['rows'][0]['Counter'];

                    //checking every parcelnum row in Db => $Counter == 0 then insert new row
                    if ($Counter == 0)
                        {
                        if($ScanCode  == 400) //insert parcel delivery name
                            {
                            $data = array('PARCELNO' => $ParcelID, 'SCAN_CODE' => $ScanCode,'Service' => 'E-COM','EVENT_DATE_TIME' => $DateTime, 'ZIP' => $ZIP,'REFERENCE' => $Reference,'Customer' => $Recepient,'Source' => "PPL", 'KN' => 'Import');
                            $Connection->insert('PMIdb', $data);
                            $RowInsert++;
                            }
                        else 
                            {
                            //insert rows to DB
                            $data = array('PARCELNO' => $ParcelID, 'SCAN_CODE' => $ScanCode,'Service' => 'E-COM', 'EVENT_DATE_TIME' => $DateTime, 'ZIP' => $ZIP,'REFERENCE' => $Reference,'Source' => "PPL", 'KN' => 'Import');
                            $Connection->insert('PMIdb', $data);
                            $RowInsert++;
                            }
                            //Set field Update to 1 => next round dont check this palletnum
                        If ($ScanCode  == 450 or $ScanCode  == 453)
                            {
                                $SQL=  "UPDATE [dbo].[PD2] SET [Update] = 1 where ([PARCELNO] = :PARCELNO)";
                                $params = array(':PARCELNO' => $ParcelID);  
                                $upd = $Connection->update($SQL,$params);
                                $CloseParcel++;
                            }        
                        }
                    }
                }
            }
        }
    }
$endTime = microtime(true);
$executionTime = $endTime - $startTime;
echo "Script time: ".$executionTime."sec <br>";
echo "Updated parcels: ".$RowHunt."<br>";
echo "Open parcels: ".$RowHunt-$CloseParcel."<br>";
echo "Closed parcels: ".$CloseParcel."<br>";
echo "Insert records: ".$RowInsert."<br>";
?>
