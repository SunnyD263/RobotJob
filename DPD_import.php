<?php
// alls ftp DPD id from 3 to 8
for ($i = 3; $i < 9 ; $i++) 
{
if (!isset($Connection)){$Connection = new PDOConnect("Setup");} 
$SQL=  "SELECT * FROM [Setup].[dbo].[RobotJob_FTP] WHERE [ID] = :ID ";
$params = array('ID'=> $i);
$stmt = $Connection->select($SQL,$params);
$count = $stmt['count'];
if($count !== 0)
    {
    $Jobs = $stmt['rows'][0]['Name'];
    $Import_file = $stmt['rows'][0]['UserName'];
    $DestinationPath = "\\\\10.47.17.20\\pmi-dbo\\SQL_script\\Robotjob\\".$Jobs."\\";
    if (!is_dir($DestinationPath)) 
        {
            if (!mkdir($DestinationPath, 0777, true)) {
                return false;
            }
        }
    }
$FTPfile = new FTP($i); 
$FTPfile->FTP_download($DestinationPath,"STATUSDATA_".$Import_file."_D");
}
// alls folders DPD id from 3 to 8
for ($i = 3; $i < 9 ; $i++) 
    {
    unset($Connection);  
    if (!isset($Connection)){$Connection = new PDOConnect("Setup");} 
    $SQL=  "SELECT * FROM [Setup].[dbo].[RobotJob_FTP] WHERE [ID] = :ID ";
    $params = array('ID'=> $i);
    $stmt = $Connection->select($SQL,$params);
    $count = $stmt['count'];
    if($count !== 0)
        {
        $Job_name = $stmt['rows'][0]['Name'];
        $Import_file = $stmt['rows'][0]['UserName'];
        $DestinationPath = "\\\\10.47.17.20\\pmi-dbo\\SQL_script\\Robotjob\\".$Job_name."\\";
        $AftImportPath = "\\\\10.47.17.20\\pmi-dbo\\SQL_script\\Robotjob\\".$Job_name."\\imported\\";

        if (!is_dir($AftImportPath)) 
            {
            if (!mkdir($AftImportPath, 0777, true)) 
                {
                return false;
                }
            }
        }  
    unset($Connection);  
    $files = scandir($DestinationPath);
    foreach ($files as $file) 
        {
        if ($file == "." || $file == ".." || $file == "imported" ) {continue;}    
        $fileName = $file;
        $check = file_exists($AftImportPath.$fileName);
        //not import if exists in impoted folders
        if ($check) 
            {
            unlink($DestinationPath.$fileName);
            } 
        else 
            {
            $txt = file_get_contents($DestinationPath.$fileName);
            $lines = explode("\n", $txt);

            for ($j = 2; $j < count($lines); $j++) 
                {
                $line = $lines[$j];
                $values = explode(';', $line); 
                $PARCELNO = trim($values[0]);
                if($PARCELNO !== "") 
                    {
                    $SCAN_CODE = trim($values[1]);
                    $DT = date("Y-m-d H:i:s", strtotime(substr($values[4], 0, 4) . '-' . substr($values[4], 4, 2) . '-' . substr($values[4], 6, 2) . ' ' . substr($values[4], 8, 2) . ':' . substr($values[4], 10, 2) . ':' . substr($values[4], 12, 2)));
                    $Service = trim($values[8]);
                    $ZIP = trim($values[10]);
                    $Reference = trim($values[15]);
                    $Customer = trim($values[17]);
                    $Source =  $Job_name;
                    $KN = 'Import';

                    if ($SCAN_CODE != '07' && $SCAN_CODE!= '18') 
                        {
                        $Field = array('t','R','v','z');
                        if (in_array(substr(trim($Reference), 0, 1),$Field))
                            {$Reference= substr(trim($Reference), 1, 8);}
                        elseif(strlen($Reference)== 8)
                            {
                            $Reference = $Reference;
                            }
                        else 
                            {
                            $Reference = substr($Reference, 0, 15);
                            }   
                                            
                        if (!isset($Connection)){$Connection = new PDOConnect("DPD_DB");} 
                        $data = array('PARCELNO' => $PARCELNO, 'SCAN_CODE' => $SCAN_CODE,'EVENT_DATE_TIME' => $DT,'Service' => $Service,'ZIP' => $ZIP ,'Reference' => $Reference, 'Customer' => $Customer, 'Source' => $Source, 'KN' => $KN);
                        $Connection->insert("PMIdb", $data);
                        }
                    if (!is_dir($AftImportPath)) 
                        {
                        if (!mkdir($AftImportPath, 0777, true)) 
                            {
                            return false;
                            }
                        }
                    }
                }
            rename($DestinationPath.$fileName, $AftImportPath.$fileName);
            }
        }
    }
    $SQL = "WITH CTE AS (SELECT [ID],[PARCELNO],[SCAN_CODE],[EVENT_DATE_TIME],[SERVICE],[ZIP],[REFERENCE],[KN],ROW_NUMBER() OVER (PARTITION BY [PARCELNO],[SCAN_CODE],[EVENT_DATE_TIME],[SERVICE],[REFERENCE] ORDER BY [KN] DESC, [EVENT_DATE_TIME] ASC, ID ASC) row_num FROM dbo.PMIdB) DELETE FROM CTE WHERE row_num > 1 and KN <> 'Inbound'";
    $stmt = $Connection->execute($SQL);
    ?>