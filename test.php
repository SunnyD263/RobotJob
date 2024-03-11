<?php
require "projectfunc.php";
if (!isset($_SESSION['currentDir'])){Find_Dir();} 
require $_SESSION['currentDir'].'\navigation.php'; 
require $_SESSION['currentDir']."\SQLconn.php"; 
require $_SESSION['currentDir']."\job.php";
require $_SESSION['currentDir']."\send_email.php"; 

if (!isset($Connection)){$Connection = new PDOConnect("Setup");} 
$SQL=  "SELECT TOP 1 * FROM [Setup].[dbo].[RobotJob_View] WHERE [ID] = :ID ORDER BY 'Start_job' ASC";
$params = array('ID'=> 3);
$stmt = $Connection->select($SQL,$params);
$count = $stmt['count'];
if($count !== 0)
    {
    $rows = $stmt['rows']; 
    Do_job($rows[0]["ID"],$rows[0]["Job_name"],$rows[0]["Start_job"],$rows[0]["Frequency"],$rows[0]["Frequency_value"],$rows[0]["Import_way"],$rows[0]["Import_file"], 
    $rows[0]["Imp_FTP_ID"],$rows[0]["Import_path"], $rows[0]["Export_way"],$rows[0]["Export_file"],$rows[0]["Exp_FTP_ID"],$rows[0]["Export_path"],$rows[0]["Email"],
    $rows[0]["Email_To"],$rows[0]["Email_Cc"], $rows[0]["Email_Subject"],$rows[0]["Email_Body"],$rows[0]["Email_Attach"]);
    }

//if (!isset($Connection)){$Connection = new PDOConnect("DPD_DB");} 
//Paketa_test(1070145590);
// if (!isset($Connection)){$Connection = new PDOConnect("DPD_DB");} 
// PPL(44686356794);

function Paketa_test($value)
{
set_time_limit(3600);
$startTime = microtime(true);
$txt = file_get_contents('http://localhost/proxy.txt');
$items = explode(';', $txt);
$parameters = [
    'proxy_host'     => $items[0],
    'proxy_port'     => $items[1],
     'stream_context' => stream_context_create(
        array(
            'ssl' => array(
                'verify_peer'       => false,
                'verify_peer_name'  => false,
            )
        )
    )
];
$RowHunt = 0;
$CloseParcel = 0;
$RowInsert=0;
try 
{   

    //connection setting
    if (!isset($Connection)){$Connection = new PDOConnect("DPD_DB");} // zavolání funkce a předání hodnot jako argumenty
    $client = new SoapClient("./soap.wsdl",$parameters); // initialize the client
    $pw = base64_decode(file_get_contents('http://localhost/packeta.txt'));

            $packetId = "Z".$value;
            $parcelNum =$value;        
            $RowHunt++;
            try         
            {     
                $PacketaData = $client->packetTracking($pw, $packetId);
                $CodeResult = json_encode($PacketaData);
                $Result = json_decode($CodeResult,true);
                $records = $Result['record'];
                
                // many items array
                if(key($Result['record']) == 0)
                {    
                    //checking every parcelnum row in Db => $Counter == 0 then insert new row
                    foreach ($records as $record)
                    {
                        $DateTime = date("Y-m-d H:i:s",strtotime($record['dateTime']));
                        $ScanCode = $record['statusCode']; 
                        $Branch =  $record['branchId'];
                        
                        $SQL=  "SELECT count([PARCELNO]) as Counter FROM [DPD_DB].[dbo].[PMIdb] where ([PARCELNO] = :parcelno) and ([EVENT_DATE_TIME] = :DaTi)";
                        $params = array(':parcelno' => $parcelNum,  ':DaTi' => $DateTime );
                        $CounterResult = $Connection->select($SQL,$params );
                        $Counter= $CounterResult["rows"][0]["Counter"];
                    
                    //checking every parcelnum row in Db => $Counter == 0 then insert new row
                        if ($Counter == 0)
                        {
                            // ZIP over branch ID
                            $ZIP = "";
                            if ($Branch != null or $Branch != "0")
                            {
                                $SQL=  "SELECT [ZIP] FROM [DPD_DB].[dbo].[PCKBranch] where ([ID] = :ID)";
                                $params = array(':ID' => $Branch);
                                $zipResult = $Connection->select($SQL,$params);
                                if ($zipResult["count"] !== 0)
                                {
                                $ZIP =trim($zipResult["rows"][0]["ZIP"]);   
                                }
                            }
                            //insert rows to DB
                            $data = array('PARCELNO' => $parcelNum, 'SCAN_CODE' => $ScanCode, 'EVENT_DATE_TIME' => $DateTime, 'ZIP' => $ZIP,'Source' => "Packeta", 'KN' => 'Import', 'Customer' => $Branch);
                            $Connection->insert('PMIdb', $data);
                            $RowInsert++;
                            
                            //Set field Update to 1 => next round dont check this palletnum
                            If ($ScanCode  == 7 or $ScanCode  == 10 or $ScanCode  == 11)
                            {
                                $SQL=  "UPDATE [dbo].[PD2] SET [Update] = 1 where ([PARCELNO] = :PARCELNO)";
                                $params = array(':PARCELNO' => $parcelNum);  
                                $upd = $Connection->update($SQL,$params);
                                $CloseParcel++;
                            }        
                        }                        
                    }
                }
                // one item array
                else
                {
                    $DateTime = date("Y-m-d H:i:s",strtotime($records['dateTime']));
                    $ScanCode = $records['statusCode']; 
                    $Branch =  $records['branchId'];

                    //checking every parcelnum row in Db => $Counter == 0 then insert new row                    
                    $SQL=  "SELECT count([PARCELNO]) as Counter FROM [DPD_DB].[dbo].[PMIdb] where ([PARCELNO] = :parcelno) and ([EVENT_DATE_TIME] = :DaTi)";
                    $params = array(':parcelno' => $parcelNum,  ':DaTi' => $DateTime );
                    $CounterResult = $Connection->select($SQL,$params );
                    $Counter= $CounterResult["rows"][0]["Counter"];
                    
                    //checking every parcelnum row in Db => $Counter == 0 then insert new row
                    if ($Counter == 0)
                    {
                        $ZIP = "";
                        if ($Branch !== null or $Branch !==  "0")
                        {
                            $SQL=  "SELECT [ZIP] FROM [DPD_DB].[dbo].[PCKBranch] where ([ID] = :ID)";
                            $params = array(':ID' => $Branch);
                            $zipResult = $Connection->select($SQL,$params);
                            $ZIP =trim($zipResult["rows"][0]["ZIP"]);  
                            $RowInsert++;
                        }
                        //insert rows to DB
                        $data = array('PARCELNO' => $parcelNum, 'SCAN_CODE' => $ScanCode, 'EVENT_DATE_TIME' => $DateTime, 'ZIP' => $ZIP,'Source' => "Packeta", 'KN' => 'Import', 'Customer' => $Branch);
                        $Connection->insert('PMIdb', $data);
                        
                        //Set field Update to 1 => next round dont check this palletnum
                        If ($ScanCode  == 7 or $ScanCode  == 10 or $ScanCode  == 11)
                        {
                            $SQL=  "UPDATE [dbo].[PD2] SET [Update] = 1 where ([PARCELNO] = :PARCELNO)";
                            $params = array(':PARCELNO' => $parcelNum);  
                            $upd = $Connection->update($SQL,$params);
                            $CloseParcel++;
                        }
                    }               
                }
            }           
            catch (SoapFault $e)
            {
            echo "Error SOAP connection: " . $e->getMessage() . "\n";
            }


    
}
catch (PDOException $exception) 
{
    echo "Db connect error: " . $e->getMessage() . "\n";
} 
catch (Exception $e) 
{
    echo "Error: " . $e->getMessage() . "\n";
}
$endTime = microtime(true);
$executionTime = $endTime - $startTime;
echo "Script time: ".$executionTime."sec <br>";
echo "Updated parcels: ".$RowHunt."<br>";
echo "Open parcels: ".$RowHunt-$CloseParcel."<br>";
echo "Closed parcels: ".$CloseParcel."<br>";
echo "Insert records: ".$RowInsert."<br>";
}

function PPL($ParcelNo = '')
{
    set_time_limit(3600);
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
        If ($ParcelNo == '')
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
                        $options = PPL_connect($grant_type,$client_id,$client_secret,$scope,$token);
                        $context = stream_context_create($options);
                        $response= json_decode( file_get_contents($apiUrl, false, $context));
                        Import($response,$Format,$Connection, $Service);                
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
                    $options = PPL_connect($grant_type,$client_id,$client_secret,$scope,$token);
                    $context = stream_context_create($options);
                    $response= json_decode( file_get_contents($apiUrl, false, $context));
                    Import($response,$Format,$Connection,$Service);
                    }
                }
            }
        else
            {
            $apiUrl ="https://api.dhl.com/ecs/ppl/myapi2/shipment?Limit=1000&Offset=0&ShipmentNumbers=$ParcelNo";
            $options = PPL_connect($grant_type,$client_id,$client_secret,$scope,$token);
            $context = stream_context_create($options);
            $response= json_decode( file_get_contents($apiUrl, false, $context));
            Import($response,$Format,$Connection, $Service);       
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
function Import($response,$Format,$Connection,$Service)
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
    function PPL_connect($grantType,$clientID,$clientSecret,$scope,$tokenUrl)
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