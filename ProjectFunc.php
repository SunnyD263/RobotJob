<?php
require 'vendor/autoload.php'; // Importovat knihovnu PhpSpreadsheet

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

function Do_job($ID, $Job_name, $Start_job, $Frequency, $Frequency_value, $Import_way, $Import_file, $Import_FTP, $Import_path, 
$Export_way, $Export_file, $Export_FTP, $Export_path, $Email, $Email_To, $Email_Cc, $Email_Subject, $Email_Body, $Email_Attach)
{

//import file
 $DestinationPath = "\\\\10.47.17.20\\pmi-dbo\\SQL_script\\Robotjob\\".$Job_name."\\";
switch ($Import_way) 
    {
    case "Nothing":
        break;
    case "Path":
        CopyFile($Import_path, $Job_name,$DestinationPath, $Import_file);
        break;
    case "FTP":
        if (!is_dir($DestinationPath)) {
            if (!mkdir($DestinationPath, 0777, true)) {
                return false;
            }
        }
        $FTPfile = new FTP($Import_FTP); 
        $FTPfile->FTP_download($DestinationPath,$Import_file);
        break;
    }

//import function by Job_name
    if($Import_way !== 'Nothing')
    {
    Imp_SQL_commands($Job_name,$DestinationPath,$Import_file);    
    }


//export file 
switch ($Export_way) 
    {
    case "Nothing":
        break;
    case "Path":
        //more days exports by Job_name
        $MoreDays=array("PMX_DSS_Time_export","PMX_DSS_VOL_export");
        if (in_array($Job_name, $MoreDays)){Exp_SQL_commands($Job_name,$Export_path,$Export_file);}
        else
            {
            $stmt =Exp_SQL_commands($Job_name);
            //file with time or not (test_2023_10_11.xlsx)
            $Counter = $stmt[0]["count"];
            if ($Counter !== 0)
                {
                if($stmt[1] !== "") 
                {   
                $Export_file = $Export_file . $stmt[1] . '.xlsx';
                }
                else
                {
                $Export_file = $Export_file. '.xlsx';    
                }

                if (!is_dir($Export_path)) {
                    if (!mkdir($Export_path, 0777, true)) {return false;}
                    }

                //jobs, which overwrite orginal file
                $Overwrite=array("EAN_export");
                if (!in_array($Job_name, $Overwrite))
                    {
                    if (!file_exists($Export_path.$Export_file))
                        {
                        $excelExporter = new ExcelExporter($stmt[0]["rows"], $Export_path.$Export_file);
                        // Name of columns export in number format as array
                        $excelExporter->exportToExcel($stmt[2],$stmt[3]);
                        }
                    else
                        {
                        if(unlink($Export_path.$Export_file))
                            {
                            $excelExporter = new ExcelExporter($stmt[0]["rows"], $Export_path.$Export_file);
                            // Name of columns export in number format as array
                            $excelExporter->exportToExcel($stmt[2],$stmt[3]);
                            }
                        }
                    }
                else
                    {
                    if (file_exists($Export_path.$Export_file))
                        {
                        if(unlink($Export_path.$Export_file))
                            {
                            $excelExporter = new ExcelExporter($stmt[0]["rows"], $Export_path.$Export_file);
                            // Name of columns export in number format as array
                            $excelExporter->exportToExcel($stmt[2],$stmt[3]);
                            }
                        }
                    }        
                }
            }
        break;
    case "FTP":
    if ($Counter !== 0)
        {
        $Now = date("dmY_His");
        $Export_file = $Export_file . $Now . '.xlsx';
        if($Export_path == '')
            {
            $Export_path =$DestinationPath;
            }
        $excelExporter = new ExcelExporter($stmt["rows"] , $Export_path.$Export_file);
        $excelExporter->exportToExcel();
        $FTPfile = new FTP($Export_FTP); 
        $FTPfile->FTP_upload($Export_path,$Export_file);
        }
        break;
    }
if(!isset($Counter)){$Counter = 1;}
if ($Counter !== 0)
    {
    //emails sender
    if ($Email == 1)
        {
        if($Export_path !== '' and $Export_file !== '')
            {
            $Email_Attach  = $Export_path.$Export_file;
            }
        Send_email(1, $Email_To, $Email_Cc, $Email_Subject, $Email_Body, $Email_Attach);
        }
    }

//next round time set
Next_round($ID,$Start_job,$Frequency,$Frequency_value);
}

//------------------------------------------------------------------------------------------------------------------------------------------------------------
function Next_round($ID,$Start_job,$Frequency,$Frequency_value)
{
if (!isset($Connection)){$Connection = new PDOConnect("Setup");}
switch ($Frequency)
    {
    case "Once":
        $Now= date("Y-m-d H:i:s");
        $SQL = "UPDATE [dbo].[RobotJob_overview] SET [Active_job] = 0 , [Last_run] = :Now WHERE [ID] = :ID";
        $params = array('Now' => $Now, 'ID' =>  $ID);
        $stmt = $Connection->update($SQL, $params);   
    break;
    case "Min":
        $Start_job = date("Y-m-d H:i:s", strtotime($Start_job));
        $Now= date("Y-m-d H:i:s");
            while ($Start_job < $Now )
            {
                $NewTime = ($Frequency_value * 60);
                $Start_job = date("Y-m-d H:i:s", strtotime($Start_job) + $NewTime );
            }
        $SQL = "UPDATE [dbo].[RobotJob_overview] SET [Start_job]= :Start_job, [Last_run] = :Now WHERE [ID] = :ID";
        $params = array('Start_job'=> $Start_job, 'Now' => $Now, 'ID' =>  $ID); 
        $stmt = $Connection->update($SQL, $params); 
    break;
    case "Hour":
        $Start_job = date("Y-m-d H:i:s", strtotime($Start_job));
        $Now= date("Y-m-d H:i:s");
        while ($Start_job < $Now )
            {
            $NewTime =   ($Frequency_value * 3600);              
            $Start_job = date("Y-m-d H:i:s", strtotime($Start_job) + $NewTime );
            }
        $SQL = "UPDATE [dbo].[RobotJob_overview] SET [Start_job]= :Start_job, [Last_run] = :Now WHERE [ID] = :ID";
        $params = array('Start_job'=> $Start_job, 'Now' => $Now, 'ID' =>  $ID); 
        $stmt = $Connection->update($SQL, $params);   
    break;
    case "Day":
        $Start_job = date("Y-m-d H:i:s", strtotime($Start_job));
        $Now= date("Y-m-d H:i:s");
        while ($Start_job < $Now )
            {
            $NewTime =   (86400 / $Frequency_value);      
            $Start_job = date("Y-m-d H:i:s", strtotime($Start_job) + $NewTime );
            }
        $SQL = "UPDATE [dbo].[RobotJob_overview] SET [Start_job]= :Start_job, [Last_run] = :Now WHERE [ID] = :ID";
        $params = array('Start_job'=> $Start_job, 'Now' => $Now, 'ID' =>  $ID); 
        $stmt = $Connection->update($SQL, $params);   
    break;
    case "Week":
        $Start_job = date("Y-m-d H:i:s", strtotime($Start_job));
        $Now= date("Y-m-d H:i:s");
        $array = explode(",", $Frequency_value);
        $Check = isDayInArray($Start_job, $array);
        while ($Start_job < $Now or $Check == false )
            {        
            $NewTime= 86400;
            $Start_job = date("Y-m-d H:i:s", strtotime($Start_job)+ $NewTime);
            $Check = isDayInArray($Start_job, $array);
            }
        $SQL = "UPDATE [dbo].[RobotJob_overview] SET [Start_job]= :Start_job, [Last_run] = :Now WHERE [ID] = :ID";
        $params = array('Start_job'=> $Start_job, 'Now' => $Now, 'ID' =>  $ID); 
        $stmt = $Connection->update($SQL, $params);  
    break;
    case "Month":
        $array = explode(",", $Frequency_value);
        $Now= date("Y-m-d H:i:s");
        $Now_month = date("M", strtotime($Start_job));
        $Job = new DateTime($Start_job);
        $Job->add(new DateInterval('P1M'));
        $Start_month= $Job->format('M');
        $Start_job = $Job->format('Y-m-d H:i:s');
        $Check = in_array($Start_month, $array);
        if($Start_month !== $Now_month and $Check == false)
            {
            while ($Check == false) 
                {
                $Job = new DateTime($Start_job);
                $Job->add(new DateInterval('P1M'));
                $Start_month = $Job->format('M');
                $Start_job = $Job->format('Y-m-d H:i:s');
                $Check = in_array($Start_month, $array);
                }
            }
        $SQL = "UPDATE [dbo].[RobotJob_overview] SET [Start_job]= :Start_job, [Last_run] = :Now WHERE [ID] = :ID";
        $params = array('Start_job'=> $Start_job, 'Now' => $Now, 'ID' =>  $ID); 
        $stmt = $Connection->update($SQL, $params);
    break;
    }
}

// day selection func
function isDayInArray($day, $dayArray) {
    $dayAbbreviation = substr(date("D", strtotime($day)), 0, 2); 
    return in_array($dayAbbreviation, $dayArray); 
}    
//month select func
function isMonthInArray($date, $monthArray) {
    $monthAbbreviation = substr(date("M", strtotime($date)), 0, 3); 
    return in_array($monthAbbreviation, $monthArray); 
}
//add value to array func
function AddToArray($existingArray, $rowIndex, $record) {
    if (!isset($existingArray[$rowIndex]) || !is_array($existingArray[$rowIndex])){
        $existingArray[$rowIndex] = array(); 
    }

    $existingArray[$rowIndex][] = $record;

    return $existingArray;
}
//find path for project *.php
function Find_Dir() {
    $_SESSION['currentDir'] = __DIR__;
}

//copy path/file func
function CopyFile($SourcePath, $Job_name,$DestinationPath, $FileName = "") {

    if (!is_dir($SourcePath)) {
        return false;
    }
    if (!is_dir($DestinationPath)) {
        if (!mkdir($DestinationPath, 0777, true)) {
            return false;
        }
    }
    $files = scandir($SourcePath);

    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            if (empty($FileName ) || strpos($file, $FileName ) !== false) {
                $SourceFile = $SourcePath . DIRECTORY_SEPARATOR . $file ;
                $DestinationFile = $DestinationPath . DIRECTORY_SEPARATOR . $file ;
                if (!copy($SourceFile, $DestinationFile)) {
                    return false;
                }
            }
        }
    }
    unlink($SourceFile);
    return true;
}

?>