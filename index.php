<!DOCTYPE html>
<html lang="cs">
    <head>
        <title>RobotJob</title>
        <meta charset="UTF-8">
        <meta name="author" content="Jan Sonbol" />
        <meta name="description" content="RobotJob" />

        <link rel="stylesheet" type="text/css" href="style.css" />
        <link rel="icon" type="image/png" href="images/kn.png"/>
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
        <script
            src="https://code.jquery.com/jquery-3.6.4.js"
            integrity="sha256-a9jBBRygX1Bh5lt8GZjXDzyOB+bWve9EiO7tROUtj/E="
            crossorigin="anonymous">
        </script>
        <meta http-equiv="refresh" content="30">
    </head>
    <body>
        <header>
        <h1>RobotJob</h1>
        </header>   

<?php
set_time_limit(3600);
session_start();
require "projectfunc.php";
if (!isset($_SESSION['currentDir'])){Find_Dir();} 
require $_SESSION['currentDir'].'\navigation.php'; 
require $_SESSION['currentDir']."\SQLconn.php"; 
require $_SESSION['currentDir']."\job.php";
require $_SESSION['currentDir']."\send_email.php"; 



If ($_SERVER["REQUEST_METHOD"] == "GET") 
{
    //open default form main.php
    if (isset($_GET["On"]))
    {
    if (!isset($Connection)){$Connection = new PDOConnect("Setup");}
    $ID= $_GET["On"];
    $SQL = "UPDATE [dbo].[RobotJob_overview] SET [Active_job] = 0 WHERE [ID] = :ID";
    $params = array('ID' =>  $ID);
    $stmt = $Connection->update($SQL, $params);
    header("Location: index.php");
    }
    elseif(isset($_GET["Off"]))
    {
    if (!isset($Connection)){$Connection = new PDOConnect("Setup");}
    $ID= $_GET["Off"];
    $SQL = "UPDATE [dbo].[RobotJob_overview] SET [Active_job] = 1 WHERE [ID] = :ID";
    $params = array('ID' =>  $ID);
    $stmt = $Connection->update($SQL, $params);
    header("Location: index.php");   
    }
    else
    {}

 
}
if (!isset($Connection)){$Connection = new PDOConnect("Setup");} 
$Now= date("Y-m-d H:i:s");
$SQL=  "SELECT TOP 1 * FROM [Setup].[dbo].[RobotJob_View] WHERE [Start_job] < :Start_job and [Active_job] = 1 ORDER BY 'Start_job' ASC";
$params = array('Start_job'=> $Now);
$stmt = $Connection->select($SQL,$params);
$count = $stmt['count'];
if($count !== 0)
{
$rows = $stmt['rows']; 
if ($rows[0]["Cycles"] < 3)
    {        
    $SQL = "UPDATE [dbo].[RobotJob_overview] SET [Cycles]= :Cycles WHERE [ID] = :ID";
    $params = array('Cycles' => $rows[0]["Cycles"] + 1,'ID' =>  $rows[0]["ID"]); 
    $Connection->update($SQL, $params);
    echo "Start job: " . $rows[0]["Job_name"] . " at " . date("Y-m-d H:i:s");
    echo "<br>";
    Do_job($rows[0]["ID"],$rows[0]["Job_name"],$rows[0]["Start_job"],$rows[0]["Frequency"],$rows[0]["Frequency_value"],$rows[0]["Import_way"],$rows[0]["Import_file"], 
    $rows[0]["Imp_FTP_ID"],$rows[0]["Import_path"], $rows[0]["Export_way"],$rows[0]["Export_file"],$rows[0]["Exp_FTP_ID"],$rows[0]["Export_path"],$rows[0]["Email"],
    $rows[0]["Email_To"],$rows[0]["Email_Cc"], $rows[0]["Email_Subject"],$rows[0]["Email_Body"],$rows[0]["Email_Attach"]);

    $SQL = "UPDATE [dbo].[RobotJob_overview] SET [Cycles]= :Cycles WHERE [ID] = :ID";
    $params = array('Cycles' => 0 ,'ID' =>  $rows[0]["ID"]); 
    $Connection->update($SQL, $params);

    }
else 
    {
        $SQL = "UPDATE [dbo].[RobotJob_overview] SET [Cycles]= :Cycles, [Active_job] = 'False' WHERE [ID] = :ID";
        $params = array('Cycles' => 0,'ID' =>  $rows[0]["ID"]); 
        $Connection->update($SQL, $params);
        Send_email(1, '', '', $rows[0]["Job_name"] . ' disabled after 3 attempts','','');
    }
}


$SQL=  "SELECT * FROM [Setup].[dbo].[RobotJob_View]  ORDER BY 'Active_job' desc, 'Start_job'";
$stmt = $Connection->select($SQL);
$count = $stmt['count'];

$columnNames = ['Status','Job_name','Start_job','Last_run','Frequency','Cycles','Import_way','Import_file','Import_FTP','Import_path','Export_way','Export_file','Export_FTP','Export_path','Email','Email_To','Email_Cc','Email_Subject','Email_Body','Email_Attach'];
echo '<table>';
if($count !== 0)
{
$rows = $stmt['rows']; 
    echo '<tr>';
    for ($i = 0; $i < count($columnNames); $i++) 
    {            
        echo '<th>' . $columnNames[$i] . '</th>';            
    }
    foreach ($rows as $row) 
    {
        echo '<tr>';
        foreach ($row as $key => $value) 
        {
            if ($key == "ID")
            {
            $LocButtonID = $value;
            }
            elseif($key=='Imp_FTP_ID' or $key=='Exp_FTP_ID' or $key=='Cycles')
            {
            }
            elseif ($key == "Active_job")
            {   
            echo "<td>"; 
            echo "<form method='GET'>";
            if ($value == 1) {$LocButton ='On';} else {$LocButton='Off';}
            echo "<button class='button_row' type='submit' name='".$LocButton."' id='' value='".$LocButtonID."' >$LocButton</button>";
            echo "</form>";
            echo "</td>";
            }
            elseif($key == "Email")
            {
            if($value == 1){$value = 'On';}
            else {$value = 'Off';}
            echo "<td>" . $value . '</td>';
            }
            else
            {
            echo "<td>" . $value . '</td>';
            }
        }
        echo '</tr>';
    }

}
echo "</table>";
echo "<br>";
?>
</body>


