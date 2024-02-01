<?php
    session_start();


function PPL_import($ParcelNO='')
{
set_time_limit(7200);
$startTime = microtime(true);
$_SESSION["RowHunt"] = 0;
$_SESSION["CloseParcel"] = 0;
$_SESSION["RowInsert"]=0;

if (!isset($Connection)){$Connection = new PDOConnect("Setup");} 
$SQL=  "SELECT * FROM [Setup].[dbo].[RobotJob_API] WHERE [Company] = :Company ";
$params = array('Company'=> 'PPL');
$stmt = $Connection->select($SQL,$params);
$count = $stmt['count'];
if($count !== 0)
    {
    $rows =  $stmt['rows'];
    foreach ($rows as $row) 
        {
        $Source = $row["Company"];
        $Service = $row["Account"];
        $grant_type = $row["grant_type"];
        $client_id = $row["client_id"];
        $client_secret = base64_decode($row["client_secret"]);
        $scope = $row["scope"];
        $token = $row["Token"];
        $Format = $row["Notice"];
        unset($Connection);
        
        //connection setting
        if (!isset($Connection)){$Connection = new PDOConnect("DPD_DB");}

        If ($ParcelNO == '')
            {
            if ($Format == 'ParcelNo')
                {
                $SQL=  "SELECT [PARCELNO] FROM [DPD_DB].[dbo].[PD2] where len(PARCELNO) = 11 and [Update] IS null order by EVENT_DATE_TIME desc";
                $stmt = $Connection->select($SQL);
                $Counter = $stmt['count'];
                if($Counter!==0)
                    {
                    $rows = $stmt['rows'];
                    foreach ($rows as $row )
                        {
                        $ParcelID =$row['PARCELNO'];       
                        $apiUrl ="https://api.dhl.com/ecs/ppl/myapi2/shipment?Limit=1000&Offset=0&ShipmentNumbers=$ParcelID";
                        $options = PPL_conn($grant_type,$client_id,$client_secret,$scope,$token);
                        $context = stream_context_create($options);
                        $response= json_decode( file_get_contents($apiUrl, false, $context));
                        Importer($response,$Format,$Connection, $Service);                
                        }
                    }
                } 
            else
                {
                for ($i = 14; $i >= 1; $i--) 
                    {    
                    $currentDate = date('Y-m-d');
                    $TodayDate = date('Y-m-d', strtotime($currentDate . ' -'. $i - 1 .' day'));    
                    $yesterdayDate = date('Y-m-d', strtotime($currentDate . ' -'. $i .' day'));
                    $apiUrl ="https://api.dhl.com/ecs/ppl/myapi2/shipment?Limit=1000&Offset=0&DateFrom=$yesterdayDate&DateTo=$TodayDate";
                    $options = PPL_conn($grant_type,$client_id,$client_secret,$scope,$token);
                    $context = stream_context_create($options);
                    $response= json_decode( file_get_contents($apiUrl, false, $context));
                    Importer($response,$Format,$Connection,$Service);
                    }
                }
            }
        else
            {
            $apiUrl ="https://api.dhl.com/ecs/ppl/myapi2/shipment?Limit=1000&Offset=0&ShipmentNumbers=$ParcelNO";
            $options = PPL_conn($grant_type,$client_id,$client_secret,$scope,$token);
            $context = stream_context_create($options);
            $response= json_decode( file_get_contents($apiUrl, false, $context));
            Importer($response,$Format,$Connection, $Service);       
            }
        }   
    }

$endTime = microtime(true);
$executionTime = $endTime - $startTime;
echo "Script time: ".$executionTime."sec <br>";
echo "Updated parcels: ".$_SESSION["RowHunt"]."<br>";
echo "Open parcels: ".$_SESSION["RowHunt"]-$_SESSION["CloseParcel"]."<br>";
echo "Closed parcels: ".$_SESSION["CloseParcel"]."<br>";
echo "Insert records: ".$_SESSION["RowInsert"]."<br>";
unset($_SESSION["RowHunt"]);
unset($_SESSION["CloseParcel"]);
unset($_SESSION["RowInsert"]);
}

function Importer($response,$Format,$Connection,$Service)
    {           
    $_SESSION["RowHunt"]++;
    if ($response !== null) 
        {
        foreach($response as $key)
            {
            $ParcelID = $key->shipmentNumber;   
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
            if(isset($key->externalNumbers[0]->externalNumber))
                {
                $Reference = substr($key->externalNumbers[0]->externalNumber, 0, 15);
                }
            else
                {
                $Reference ="";
                }

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
                    if($ScanCode  == 400) 
                    //insert parcel 'Customer' => $Recepient value "Zpět odesilateli" for non-delivery
                        {
                        $data = array('PARCELNO' => $ParcelID, 'SCAN_CODE' => $ScanCode,'Service' => $Service,'EVENT_DATE_TIME' => $DateTime, 'ZIP' => $ZIP,'REFERENCE' => $Reference,'Customer' => $Recepient,'Source' => "PPL-".$Service, 'KN' => 'Import');
                        $Connection->insert('PMIdb', $data);
                        $_SESSION["RowInsert"]++;
                        }
                    else 
                        {
                    //insert rows to DB
                        $data = array('PARCELNO' => $ParcelID, 'SCAN_CODE' => $ScanCode,'Service' => $Service, 'EVENT_DATE_TIME' => $DateTime, 'ZIP' => $ZIP,'REFERENCE' => $Reference,'Source' => "PPL-".$Service, 'KN' => 'Import');
                        $Connection->insert('PMIdb', $data);
                        $_SESSION["RowInsert"]++;
                        } 
                    
                    If ($Format == 'ParcelNo' and $ScanCode  == 450 or $Format == 'ParcelNo' and $ScanCode  == 453)
                        {
                            $SQL=  "UPDATE [dbo].[PD2] SET [Update] = 1 where ([PARCELNO] = :PARCELNO)";
                            $params = array(':PARCELNO' => $ParcelID);  
                            $upd = $Connection->update($SQL,$params);
                            $_SESSION["CloseParcel"]++;
                        }         
                    }
                }
            }
        }
    }

    function PPL_conn($grantType,$clientID,$clientSecret,$scope,$tokenUrl)
    {
        
        $PPL = file_get_contents('http://localhost/ppl.txt');
        $items = explode(';', $PPL);
        $proxy = $items[2]; 
        
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
    return $options;
    }
?>
