<!DOCTYPE html>
<html lang="cs">

<head>
    <title>RobotJob</title>
    <meta charset="UTF-8">
    <meta name="author" content="Jan Sonbol" />
    <meta name="description" content="RobotJob" />
    <link rel="stylesheet" type="text/css" href="style.css" />
    <link rel="icon" type="image/png" href="images/kn.png" />
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">

    <script src="https://code.jquery.com/jquery-3.6.4.js"
        integrity="sha256-a9jBBRygX1Bh5lt8GZjXDzyOB+bWve9EiO7tROUtj/E=" crossorigin="anonymous">
    </script>
</head>

<body>
    <header>
        <h1>RobotJob</h1>
    </header>
    <?php
session_start();
require "projectfunc.php";
if (!isset($_SESSION['currentDir'])){Find_Dir();} 
require $_SESSION['currentDir'].'\navigation.php';
require $_SESSION['currentDir']."\SQLconn.php";
require $_SESSION['currentDir']."\htmlforms.php";

If ($_SERVER["REQUEST_METHOD"] == "GET") 
{
    //open default form main.php
    if (isset($_GET["MenuB"]))
    {
        if($_GET["MenuB"] == 'Save')
        {
        if (!isset($Connection)){$Connection = new PDOConnect("Setup");} 
        if($_GET["Imp_FTP"] == 1){ $_GET["Imp_FTP"] = '';}
        if($_GET["Exp_FTP"] == 1){ $_GET["Exp_FTP"] = '';}
        if($_GET["Email"] == 'Yes'){ $_GET["Email"] = 1;} else {$_GET["Email"] = 0;}
        if ($_GET["repeat"] == "") 
        {
        if(isset($_GET["Day"]))
            {
            $array = $_GET["Day"];
            }
        elseif(isset($_GET["Month"]))
            {
            $array = $_GET["Month"];
            }
        $Frequency=implode(",", $array);
        }
        else
        {
        $Frequency = $_GET["repeat"];
        }
        $FTP_Imp = intval($_GET["Imp_FTP"]);
        $FTP_Exp = intval($_GET["Exp_FTP"]); 
        $datetime = date("Y-m-d H:i:s", strtotime($_GET["DT"]));     
        $data = array('Job_name' => $_GET["job_name"],'Active_job'=> 1, 'Start_job' => $datetime, 'Frequency' => $_GET["Frequency"], 
        'Frequency_value' => $Frequency,'Import_way' => $_GET["Imp_loc"],'Import_FTP' => $FTP_Imp,'Import_path' => $_GET["Path_imp"],'Import_file' => $_GET["File_imp"],
        'Export_way' => $_GET["Exp_loc"],'Export_FTP' => $FTP_Exp,'Export_path' => $_GET["Path_exp"],'Export_file' => $_GET["File_exp"],'Email'=> $_GET["Email"],
        'Email_To'=>$_GET["Email_to"],'Email_Cc'=>$_GET["Email_cc"],'Email_Subject' => $_GET["Email_subject"],'Email_Body' =>$_GET["Email_body"],'Email_Attach'=> $_GET["Email_attach"]);
        $Connection->insert('RobotJob_overview', $data);
        header("Location: index.php");
        }
    }
}

echo    "<form method='GET'>";
echo    "<fieldset>";
echo    "<legend>Name</legend>";
echo    "<label for='job_name'>Job name:</label>";
echo    "<input type='text' id='job_name' name='job_name' value='' autofocus>";       
echo    "</fieldset>";

echo    "<fieldset>";
echo    "<legend>Time period</legend>";
echo    "<label for='DT'>Start job:</label>";
echo    "<input type='datetime-local' id='DT' name='DT' value = ''>";
echo    "<label for='Frequency'>Frequency:</label>";
echo    "<select name='Frequency' ID='Frequency' onchange='showDaysOfWeek()'>";
echo    "<option id='Once' value='Once' >Once</option>";
echo    "<option id='Min' value='Min'  >Per minute</option>";
echo    "<option id='Hour' value='Hour' >Per hour</option>";
echo    "<option id='Day' value='Day' >Per day</option>";
echo    "<option id='Week' value='Week' >Per week</option>";
echo    "<option id='Month' value='Month'>Per month</option>";
echo    "</select>";

echo    "<div id='Interval_Slct' style='display: none'>";
echo    "<label for='repeat' >Every:</label>";
echo    "<input type='text' id='repeat' name='repeat' value=''>";       
echo    "</div>";

DayOfWeek_combobox();
MonthOfYear_combobox();
echo    "</fieldset>";

echo    "<fieldset>";
echo    "<legend>File import</legend>";
echo    "<label for='Imp_loc'>Import from:</label>";
echo    "<select name='Imp_loc' id='Imp_loc' onchange='showFile(1)'>";
echo    "<option id='Nothing' value='Nothing' ></option>";
echo    "<option id='FTP' value='FTP'  >FTP</option>";
echo    "<option id='Path' value='Path' >Path</option>";
echo    "<option id='PHP' value='PHP' >PHP</option>";
echo    "</select>";
FTP_combobox(1);
echo    "<div id='Path_Imp_Slct' class='Email' style='display: none'>";
echo    "<label for='Path_imp' >Import path:</label>";
echo    "<input type='text' id='Path_imp' name='Path_imp'>";
echo    "</div>";

echo    "<br><div id='File_import' style='display: none'>";
echo    "<label for='File_imp' >Import file name:</label>";
echo    "<input type='text' id='File_imp' name='File_imp'>";
echo    "</div>";
echo    "</fieldset>";

echo    "<fieldset>";
echo    "<legend>File export</legend>";
echo    "<label for='Exp_loc'>Export from:</label>";
echo    "<select name='Exp_loc' id='Exp_loc' onchange='showFile(2)'>";
echo    "<option id='Nothing' value='Nothing' ></option>";
echo    "<option id='FTP' value='FTP'  >FTP</option>";
echo    "<option id='Path' value='Path' >Path</option>";
echo    "<option id='PHP' value='PHP' >PHP</option>";
echo    "</select>";
FTP_combobox(2);
echo    "<div id='Path_Exp_Slct' class='Email' style='display: none'>";
echo    "<label for='Path_exp' >Export path:</label>";
echo    "<input type='text' id='Path_exp' name='Path_exp'>";
echo    "</div>";

echo    "<br><div id='File_export' style='display: none'>";
echo    "<label for='File_exp' >Export file name:</label>";
echo    "<input type='text' id='File_exp' name='File_exp'>";
echo    "</div>";
echo    "</fieldset>";

echo    "<fieldset >";
echo    "<legend>Emailing</legend>";
echo    "<label for='Email_slct'>Send by email:</label>";
echo    "<select name='Email' id='Email' onchange='showEmail()'>";
echo    "<option id='Email_allow' value='Yes' >Yes</option>";
echo    "<option id='Email_allow' value='No' selected>No</option>";
echo    "</select>";
echo    "<div id='Email_details' class='Email' style='display: none'>";
echo    "<label for='Email_to' >Email to:</label>";
echo    "<input type='text' id='Email_to' name='Email_to'>";
echo    "<label for='Email_cc' >Email cc:</label>";
echo    "<input type='text' id='Email_cc' name='Email_cc'>";
echo    "<label for='Email_subject' >Email subject:</label>";
echo    "<input type='text' id='Email_subject' name='Email_subject'>";
echo    "<label for='Email_body' >Email body:</label>";
echo    "<input type='text' id='Email_body' name='Email_body'>";
echo    "<label for='Email_attach' >Email attachment:</label>";
echo    "<input type='text' id='Email_attach' name='Email_attach'>";
echo    "</div>";
echo    "</fieldset>";

echo    "<fieldset class='MenuButton'>";
echo    "<input type='submit' onclick=''  name='MenuB' id='Save' value='Save'>";
echo    "<input type='submit' onclick=''  name='MenuB' id='Back' value='Back'>";
echo    "</fieldset>";
echo    "</form>";

?>
    <script>
    function showDaysOfWeek() {
        var frequency = document.getElementById("Frequency").value;
        var values = document.getElementById("Interval_Slct");
        var days = document.getElementById("Day_Slct");
        var month = document.getElementById("Month_Slct");
        var showForValues = ["Min", "Hour", "Day"]
        if (showForValues.includes(frequency)) {
            values.style.display = "inline-block";
            days.style.display = "none";
            month.style.display = "none";
        } else if (frequency === "Week") {
            values.style.display = "none";
            days.style.display = "inline-block";
            month.style.display = "none";
        } else if (frequency === "Month") {
            values.style.display = "none";
            days.style.display = "none";
            month.style.display = "inline-block";
        } else {
            values.style.display = "none";
            days.style.display = "none";
            month.style.display = "none";
        }
    }

    function showFile(IE) {
        var Slct = IE
        if (Slct === 1) {
            var ImpLoc = document.getElementById("Imp_loc").value;
            var FTP_field = document.getElementById("Imp_FTP_Slct");
            var Path_field = document.getElementById("Path_Imp_Slct");
            var File_field = document.getElementById("File_import");
        } else if (Slct === 2) {
            var ImpLoc = document.getElementById("Exp_loc").value;
            var FTP_field = document.getElementById("Exp_FTP_Slct");
            var Path_field = document.getElementById("Path_Exp_Slct");
            var File_field = document.getElementById("File_export");
        }
        if (ImpLoc === "FTP") {
            FTP_field.style.display = "inline-block";
            Path_field.style.display = "none";
            File_field.style.display = "inline-block";
        } else if (ImpLoc === "Path") {
            FTP_field.style.display = "none";
            Path_field.style.display = "inline-block";
            File_field.style.display = "inline-block";
        } else {
            FTP_field.style.display = "none";
            Path_field.style.display = "none";
            File_field.style.display = "none";
        }
    }

    function showEmail() {
        var Email_var = document.getElementById("Email").value;
        var Email_field = document.getElementById("Email_details");
        if (Email_var === "Yes") {
            Email_field.style.display = "inline-block";
        } else if (Email_var === "No") {
            Email_field.style.display = "none";
        }
    }
    </script>
</body>