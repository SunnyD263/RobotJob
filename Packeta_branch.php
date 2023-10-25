<?php
set_time_limit(300);
$startTime = microtime(true);

//update branch ID
require 'fileWork.php';
try
{
    $RowInsert=0;
    $BranchUpd=0;
    $File = new KN_file_get_contents("https://www.zasilkovna.cz/api/v4/1abc3717ad2818601a4e46f242f9caa6/branch.json?lang=cs");
    $text= $File->json();
    $Result = json_decode($text,true);
    $records = $Result['data'];
    
    if (!isset($Connection)){$Connection = new PDOConnect("DPD_DB");} 
        foreach ($records as $record) 
        {
            $BranchUpd++;
            $ID=$record['id']; 
            $Branch = $record['place'];
            $Street = $record['street'];
            $City = $record['city'];
            $ZIP =  $record['zip'];
            $SQL=  "SELECT count([ID]) FROM [DPD_DB].[dbo].[PCKbranch] where ([ID] = :ID)";
            $params = array(':ID' => $ID);
            $stmt = $Connection->select($SQL, $params);
            $Counter = $stmt["count"];    
             if ($Counter == 0)
                {                
                $data = array('ID' => $ID, 'Branch' => $Branch, 'Street' => $Street,'City' => $City,'ZIP' => $ZIP);
                $Connection->insert('PCKbranch', $data);
                $RowInsert++;
                }

        }    
}
catch (Exception $e) 
{
    echo "Error: " . $e->getMessage()."/n";
}
$endTime = microtime(true);
$executionTime = $endTime - $startTime;
echo "Script time : ".$executionTime."sec <br>";
echo "Updated branchs : ".$BranchUpd."<br>";
echo "Insert records : ".$RowInsert."<br>";
?>