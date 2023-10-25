<?php
require "projectfunc.php";
if (!isset($_SESSION['currentDir'])){Find_Dir();} 
require $_SESSION['currentDir'].'\navigation.php'; 
require $_SESSION['currentDir']."\SQLconn.php"; 
require $_SESSION['currentDir']."\job.php";
require $_SESSION['currentDir']."\send_email.php"; 



if (!isset($Connection)){$Connection = new PDOConnect("Setup");} 
$SQL=  "SELECT TOP 1 * FROM [Setup].[dbo].[RobotJob_View] WHERE [ID] = :ID ORDER BY 'Start_job' ASC";
$params = array('ID'=> 6);
$stmt = $Connection->select($SQL,$params);
$count = $stmt['count'];
if($count !== 0)
{
$rows = $stmt['rows']; 
Do_job($rows[0]["ID"],$rows[0]["Job_name"],$rows[0]["Start_job"],$rows[0]["Frequency"],$rows[0]["Frequency_value"],$rows[0]["Import_way"],$rows[0]["Import_file"], 
$rows[0]["Imp_FTP_ID"],$rows[0]["Import_path"], $rows[0]["Export_way"],$rows[0]["Export_file"],$rows[0]["Exp_FTP_ID"],$rows[0]["Export_path"],$rows[0]["Email"],
$rows[0]["Email_To"],$rows[0]["Email_Cc"], $rows[0]["Email_Subject"],$rows[0]["Email_Body"],$rows[0]["Email_Attach"]);
}

// if (!isset($Connection)){$Connection = new PDOConnect("DPD_DB");} 
// Paketa_test(2742073847);

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
                                $ZIP =trim($zipResult["rows"][0]["ZIP"]);   
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
                    $Counter= $CounterResult["count"];
                    
                    //checking every parcelnum row in Db => $Counter == 0 then insert new row
                    if ($Counter == 0)
                    {
                        $ZIP = "";
                        if ($Branch != null or $Branch !=  "0")
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



?>