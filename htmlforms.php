<?php
function DayOfWeek_combobox()
{
echo    "<div id='Day_Slct' style='display: none'>";
echo    "<label for='Day'>Select days:</label>";
echo    "<select name='Day[]' ID='Day' onchange='' multiple>";
$daysOfWeek = ["Mo", "Tu", "We", "Th", "Fr", "Sa", "Su"];
foreach ($daysOfWeek as $day) 
    {
    echo "<option id='" . $day . "'value='". $day ."' >". $day ."</option>";
    }
echo    "</select>";
echo    "</div>";
}

function MonthOfYear_combobox()
{
echo    "<div id='Month_Slct' style='display: none'>";
echo    "<label for='Month'>Select months:</label>";
echo    "<select name='Month[]' ID='Month' onchange='' multiple>";
$monthsOfYear = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul","Aug", "Sep", "Oct", "Nov", "Dec"];
foreach ($monthsOfYear as $month) 
    {
    echo "<option id='" . $month . "'value='". $month ."' >". $month ."</option>";
    }
echo    "</select>";
echo    "</div>";
}

function FTP_combobox($IE)
{
if (!isset($Connection)){$Connection = new PDOConnect("Setup");}
$SQL=  "SELECT [ID],[Name] FROM [Setup].[dbo].[RobotJob_FTP] ORDER BY [ID] ASC";
$stmt = $Connection->select($SQL);
$count = $stmt['count'];

if($IE === 1)
{
echo    "<div id='Imp_FTP_Slct' style='display: none'>";
echo    "<label for='Imp_FTP'>Select FTP:</label>";
echo    "<select name='Imp_FTP' ID='Imp_FTP' onchange=''>";
}
else
{
echo    "<div id='Exp_FTP_Slct' style='display: none'>";
echo    "<label for='Exp_FTP'>Select FTP:</label>";
echo    "<select name='Exp_FTP' ID='Exp_FTP' onchange=''>";   
} 
$firstrow = true;
if($count > 0)
    { 
    $rows = $stmt['rows'];
    foreach($rows as $row) 
        {
        $ID= $row['ID'];
        $FTP_Name = $row['Name'];
        if ($firstrow == true)
            {
            echo "<option id='" . $ID. "'value='" . $ID. "' selected>". $FTP_Name ."</option>";            
            $firstrow = false;
            }
        else
            {
            echo "<option id='" . $ID. "'value='" . $ID. "' >". $FTP_Name ."</option>";
            }
        }
    }
echo    "</select>";
echo    "</div>";
                
}
?>