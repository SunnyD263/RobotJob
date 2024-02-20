<?php
function Imp_SQL_commands($job,$DestinationPath,$Import_file)
{
unset($Connection);
$AftImportPath = $DestinationPath."Imported\\";

switch ($job) 
{
//***************************************************************************************************//
case "Customer_data":
    $files = scandir($DestinationPath);
    if (!isset($Connection)){$Connection = new PDOConnect("DPD_DB");} 
    
    foreach ($files as $file) 
        {
        if (is_file($DestinationPath . $file) && $file != '.' && $file != '..') 
            {
            $Readfile = fopen($DestinationPath . $file, "r");
            fgets($Readfile);
            while (($line = fgets($Readfile)) !== false) 
                {
                $values = explode("\t", $line);
                if ($values[0] !== '')
                    {
                $Field = array('t','R','v','z');
                if (in_array(substr(trim($values[0]), 0, 1),$Field))
                {$Reference= substr(trim($values[0]), 1, 8);}
                else
                {$Reference = trim($values[0]);}
                $Customer = trim($values[1]);
                $Street = trim($values[2]);
                $City = trim($values[3]);
                $DT = substr(trim($values[4]), 0, 2) . substr(trim($values[4]), 3, 2) . '-' . substr(trim($values[4]), 5, 1) . substr(trim($values[4]), 7, 1) . '-' . substr(trim($values[4]), 8, 2);
                $ORDTYP = trim($values[5]);        
$data = array('REFERENCE' => $Reference, 'Customer' => $Customer,'Street' => $Street,'City' => $City,'DT' => $DT ,'ORDTYP' => $ORDTYP);
$Connection->insert("Customer", $data);
                    }
                }       
                fclose($Readfile);
                if (!is_dir($AftImportPath)) {
                    if (!mkdir($AftImportPath, 0777, true)) {
                        return false;
                    }
                }
            rename($DestinationPath.$file, $AftImportPath.$file);
            }
        }
$SQL = "WITH CTE AS (SELECT [REFERENCE],ROW_NUMBER() OVER (PARTITION BY [REFERENCE] ORDER BY [DT] ASC) row_num FROM dbo.Customer) DELETE FROM CTE WHERE row_num > 1";
$stmt = $Connection->execute($SQL);
break;

//***************************************************************************************************//
case "EAN_import":
    $files = scandir($DestinationPath);
    if (!isset($Connection)){$Connection = new PDOConnect("DPD_DB");} 
    
    foreach ($files as $file) 
        {
        if (is_file($DestinationPath . $file) && $file != '.' && $file != '..') 
            {
            $Readfile = fopen($DestinationPath . $file, "r");
            fgets($Readfile);
            while (($line = fgets($Readfile)) !== false) 
                {
                $values = explode("\t", $line);
            if ($values[0] !== '')
                    {
                $MATNR=trim($values[0]);
                $MAKTX = trim($values[1]);
                $EAN_PK=trim($values[2]);
                $EAN_CT =trim($values[3]);
                $EAN_BX =trim($values[4]);
     
$data = array('MATNR' => $MATNR, 'MAKTX' => $MAKTX,'EAN_PK' => $EAN_PK,'EAN_CT' => $EAN_CT,'EAN_BX' => $EAN_BX );
$Connection->insert("EAN", $data);
                    }
                }       
                fclose($Readfile);
                if (!is_dir($AftImportPath)) {
                    if (!mkdir($AftImportPath, 0777, true)) {
                        return false;
                    }
                }
            rename($DestinationPath.$file, $AftImportPath.$file);
            }
        }
$SQL = " WITH CTE AS (SELECT [MATNR],[ID],ROW_NUMBER() OVER (PARTITION BY [MATNR] ORDER BY [ID] DESC) row_num FROM EAN) DELETE FROM CTE WHERE row_num > 1";
$stmt = $Connection->execute($SQL);
$SQL = "exec LastEAN_update";
$stmt = $Connection->execute($SQL);
break;

//***************************************************************************************************//
case "PMX_scr":
    $files = scandir($DestinationPath);
    if (!isset($Connection)){$Connection = new PDOConnect("Produktivita");} 
    
    foreach ($files as $file) 
        {
        if (is_file($DestinationPath . $file) && $file != '.' && $file != '..') 
            {
            $Readfile = fopen($DestinationPath . $file, "r");
            fgets($Readfile);
            while (($line = fgets($Readfile)) !== false) 
                {
                $values = explode("\t", $line);
                if ($values[0] !== '')
                    {
                $Depo=trim($values[0]);
                $Client = trim($values[1]);
                $Operator=trim($values[2]);
                $Date = substr($values[3], 6, 4) . '-' . substr($values[3], 3, 2) . '-' . substr($values[3], 0, 2);
                $Time =trim($values[4]);
                $ORDTYP =trim($values[5]);
                $MO =trim($values[6]);
                $ZoneCode =trim($values[7]);
                $PalletID =trim($values[8]);
                $Qty =trim($values[9]);
                 
$data = array('Depo' => $Depo, 'Client' => $Client,'Operator' => $Operator,'Date' => $Date,'Time' => $Time,'Order_type' => $ORDTYP,'Movement_Order' => $MO,'ZoneCode' => $ZoneCode, 'Pallet_ID' => $PalletID,'Qty' => $Qty );
$Connection->insert("PMX_DSSMITH", $data);
                    }
                }       
                fclose($Readfile);
                if (!is_dir($AftImportPath)) {
                    if (!mkdir($AftImportPath, 0777, true)) {
                        return false;
                    }
                }
            rename($DestinationPath.$file, $AftImportPath.$file);
            }
        }
break;

//***************************************************************************************************//
case "Trade_IN":

    $files = scandir($DestinationPath);
    if (!isset($Connection)){$Connection = new PDOConnect("DPD_DB");} 
    
    foreach ($files as $file) 
        {
        if (is_file($DestinationPath . $file) && $file != '.' && $file != '..') 
            {
            $Readfile = fopen($DestinationPath . $file, "r");
            fgets($Readfile);
            while (($line = fgets($Readfile)) !== false) 
                {
                $values = explode("\t", $line);
                if ($values[0] !== '')
                {               
                $Reference=trim($values[0]);
                $CRTDate = substr($values[1], 0, 2) . substr($values[1], 3, 2) . '-' . substr($values[1], 5, 1) . substr($values[1], 7, 1) . '-' . substr($values[1], 8, 2);
                //BO shit
                if ($values[2] !== "0")
                    {
                    $SHPDate = substr($values[2], 0, 2) . substr($values[2], 3, 2) . '-' . substr($values[2], 5, 1) . substr($values[2], 7, 1) . '-' . substr($values[2], 8, 2);
                    } 
                    else
                    {
                    $SHPDate = substr($values[1], 0, 2) . substr($values[1], 3, 2) . '-' . substr($values[1], 5, 1) . substr($values[1], 7, 1) . '-' . substr($values[1], 8, 2);   
                    }
$data = array('Reference' => $Reference, 'CRTDate' => $CRTDate,'SHPDate' => $SHPDate);
$Connection->insert("TRADE_IN", $data);
                }
                }       
                fclose($Readfile);
                if (!is_dir($AftImportPath)) {
                    if (!mkdir($AftImportPath, 0777, true)) {
                        return false;
                    }
                }
            rename($DestinationPath.$file, $AftImportPath.$file);
            }
        }
$SQL = "WITH CTE AS (SELECT [REFERENCE],ROW_NUMBER() OVER (PARTITION BY [REFERENCE] ORDER BY [ID] ASC) row_num FROM dbo.[TRADE_IN]) DELETE FROM CTE WHERE row_num > 1";
$stmt = $Connection->execute($SQL);
break;

//***************************************************************************************************//
case "PD2_reference":
    $files = scandir($DestinationPath);
    if (!isset($Connection)){$Connection = new PDOConnect("DPD_DB");} 
    
    foreach ($files as $file) 
        {
        if (is_file($DestinationPath . $file) && $file != '.' && $file != '..') 
            {
            $Readfile = fopen($DestinationPath . $file, "r");
            fgets($Readfile);
            while (($line = fgets($Readfile)) !== false) 
                {
                $values = explode("\t", $line);
                $Reference = trim($values[0]);
                $Parcel = trim($values[1]);
                $DT = substr($values[2], 0, 4) . '-' . substr($values[2], 4, 2) . '-' . substr($values[2], 6, 2);
                 
$data = array('Parcelno' => $Parcel, 'Reference' => $Reference,'EVENT_DATE_TIME' => $DT);
$Connection->insert("PD2", $data);
                }       
                fclose($Readfile);
                if (!is_dir($AftImportPath)) {
                    if (!mkdir($AftImportPath, 0777, true)) {
                        return false;
                    }
                }
            rename($DestinationPath.$file, $AftImportPath.$file);
            }
        }
$SQL = "WITH CTE AS (SELECT [REFERENCE],[PARCELNO],ROW_NUMBER() OVER (PARTITION BY [PARCELNO] ORDER BY [ID] ASC) row_num FROM dbo.PD2) DELETE FROM CTE WHERE row_num > 1";
$stmt = $Connection->execute($SQL);
break;

//***************************************************************************************************//
case "PD3_reference":

    if (!isset($Connection)){$Connection = new PDOConnect("DPD_DB");} 
    $SQL = "EXEC [dbo].[LENDING_CRT_TBL]";
    $stmt = $Connection->execute($SQL);

    $files = scandir($DestinationPath);  
    foreach ($files as $file) 
        {
        if (is_file($DestinationPath . $file) && $file != '.' && $file != '..') 
            {
            $Readfile = fopen($DestinationPath . $file, "r");
            fgets($Readfile);
            while (($line = fgets($Readfile)) !== false) 
                {
                    $values = explode("\t", $line);
                if ($values[0] !== '')
                    {
                    $Field = array('t','R','v','z');
                    if (in_array(substr(trim($values[0]), 0, 1),$Field))
                    {$Reference= substr(trim($values[0]), 1, 10);}
                    else
                    {$Reference = trim($values[0]);}
                    $DT = substr(trim($values[1]), 0, 2) . substr(trim($values[1]), 3, 2) . '-' . substr(trim($values[1]), 5, 1) . substr(trim($values[1]), 7, 1) . '-' . substr(trim($values[1]), 8, 2);      
                    
                    $data = array('REFERENCE' => $Reference,'DT' => $DT);
                    $Connection->insert("Lending", $data);
                    }
                }       
                fclose($Readfile);
                if (!is_dir($AftImportPath)) {
                    if (!mkdir($AftImportPath, 0777, true)) {
                        return false;
                    }
                }
            rename($DestinationPath.$file, $AftImportPath.$file);
            }
        }
break;

//***************************************************************************************************//
case "PMI_OrdItems_PB4_cntf":

    if (!isset($Connection)){$Connection = new PDOConnect("DPD_DB");} 
    $files = scandir($DestinationPath);  
    foreach ($files as $file) 
        {
        if (is_file($DestinationPath . $file) && $file != '.' && $file != '..') 
            {
            $Readfile = fopen($DestinationPath . $file, "r");
            fgets($Readfile);              
                while (($line = fgets($Readfile)) !== false) 
                {
                    $values = explode("\t", $line);
                    if ($values[0] !== '')
                    {
                    $Reference=trim($values[0]);
                    $ORDTyp = trim($values[1]);
                    $Material=trim($values[2]);
                    $Quantity = trim($values[4]);
                    $Codentify =trim($values[5]);
                  
    $data = array('Reference' => $Reference, 'ORDTyp' => $ORDTyp,'Material' => $Material,'Quantity' => $Quantity,'Codentify' => $Codentify, 'Task' => '');
    $Connection->insert("OrderItems", $data);
                    }
                }                   

                fclose($Readfile);
                if (!is_dir($AftImportPath)) {
                    if (!mkdir($AftImportPath, 0777, true)) {
                        return false;
                    }
                }
            rename($DestinationPath.$file, $AftImportPath.$file);
            }
        }
    $SQL = "WITH CTE AS (SELECT [REFERENCE],[Material],[ORDTyp],[Codentify],[Quantity], ROW_NUMBER() OVER (PARTITION BY [REFERENCE],[Material],[ORDTyp],[Codentify] ORDER BY [REFERENCE] Desc) row_num FROM OrderItems) DELETE FROM CTE WHERE row_num > 1 and ORDTyp = 'PB4'";
    $SQL1 = "WITH CTE AS (SELECT [REFERENCE],[Material],[ORDTyp],[Codentify],[Quantity], ROW_NUMBER() OVER (PARTITION BY [REFERENCE],[Material],[ORDTyp],[Codentify] ORDER BY [REFERENCE] Desc) row_num FROM OrderItems) DELETE FROM CTE WHERE row_num > 1 and left(ORDTyp,2) = 'PR'";
    $stmt = $Connection->execute($SQL);
    $stmt = $Connection->execute($SQL1);
break;

//***************************************************************************************************//
case "PMI_OrdItems_PD":

    if (!isset($Connection)){$Connection = new PDOConnect("DPD_DB");} 

    $files = scandir($DestinationPath);  
    foreach ($files as $file) 
        {
        if (is_file($DestinationPath . $file) && $file != '.' && $file != '..') 
            {
            $Readfile = fopen($DestinationPath . $file, "r");
            fgets($Readfile);
            while (($line = fgets($Readfile)) !== false) 
                {
                    $values = explode("\t", $line);
                    if ($values[0] !== '')
                    {
                    $Reference=trim($values[0]);
                    $ORDTyp = trim($values[1]);
                    $Material=trim($values[2]);
                    $Quantity = trim($values[4]);
                    $Codentify =trim($values[5]);
                    $Task =trim($values[6]);

    $data = array('Reference' => $Reference, 'ORDTyp' => $ORDTyp,'Material' => $Material,'Quantity' => $Quantity,'Codentify' => $Codentify, 'Task' => $Task);
    $Connection->insert("OrderItems", $data);
                    }
                }       
                fclose($Readfile);
                if (!is_dir($AftImportPath)) {
                    if (!mkdir($AftImportPath, 0777, true)) {
                        return false;
                    }
                }
            rename($DestinationPath.$file, $AftImportPath.$file);
            }
        }
    $SQL = "WITH CTE AS (SELECT [REFERENCE],[Material],[ORDTyp],[Codentify],[Quantity],[Task], ROW_NUMBER() OVER (PARTITION BY [Task],[Material],[REFERENCE],[Codentify] ORDER BY [Task] desc) row_num FROM OrderItems) delete FROM CTE WHERE row_num > 1 and left(ORDTyp,2)='PD'";
    $SQL1 = "WITH CTE AS (SELECT [REFERENCE],[Material],[ORDTyp],[Codentify],[Quantity],[Task], ROW_NUMBER() OVER (PARTITION BY [Task],[Material],[REFERENCE],[Codentify] ORDER BY [Task] desc) row_num FROM OrderItems) delete FROM CTE WHERE row_num > 1 and left(ORDTyp,2)='PO'";    
    $stmt = $Connection->execute($SQL);
    $stmt = $Connection->execute($SQL1);
break;

//***************************************************************************************************//
case "PMI_OrdItems_PD_cntf":

    if (!isset($Connection)){$Connection = new PDOConnect("DPD_DB");} 

    $files = scandir($DestinationPath);  
    foreach ($files as $file) 
        {
        if (is_file($DestinationPath . $file) && $file != '.' && $file != '..') 
            {
            $Readfile = fopen($DestinationPath . $file, "r");
            fgets($Readfile);
            while (($line = fgets($Readfile)) !== false) 
                {
                    $values = explode("\t", $line);
                    if ($values[0] !== '')
                    {
                    $Reference=trim($values[0]);
                    $ORDTyp = trim($values[1]);
                    $Material=trim($values[2]);
                    $Quantity = trim($values[4]);
                    $Codentify =trim($values[5]);
                    $Task =trim($values[6]);
  
    $data = array('Reference' => $Reference, 'ORDTyp' => $ORDTyp,'Material' => $Material,'Quantity' => $Quantity,'Codentify' => $Codentify, 'Task' => $Task);
    $Connection->insert("OrderItems", $data);
                    }
                }       
                fclose($Readfile);
                if (!is_dir($AftImportPath)) {
                    if (!mkdir($AftImportPath, 0777, true)) {
                        return false;
                    }
                }
            rename($DestinationPath.$file, $AftImportPath.$file);
            }
        }
    $SQL = "WITH CTE AS (SELECT [REFERENCE],[Material],[ORDTyp],[Codentify],[Quantity],[Task], ROW_NUMBER() OVER (PARTITION BY [Task],[Material],[REFERENCE],[Codentify] ORDER BY [Task] desc)  row_num FROM OrderItems) delete FROM CTE WHERE row_num > 1 and left(ORDTyp,2)='PD'";
    $SQL1 = "WITH CTE AS (SELECT [REFERENCE],[Material],[ORDTyp],[Codentify],[Quantity],[Task], ROW_NUMBER() OVER (PARTITION BY [Task],[Material],[REFERENCE],[Codentify] ORDER BY [Task] desc) row_num FROM OrderItems) delete FROM CTE WHERE row_num > 1 and left(ORDTyp,2)='PO'";
    $stmt = $Connection->execute($SQL);
    $stmt = $Connection->execute($SQL1);
break;

//***************************************************************************************************//
case "SWAP_reference":

    if (!isset($Connection)){$Connection = new PDOConnect("DPD_DB");} 

    $files = scandir($DestinationPath);  
    foreach ($files as $file) 
        {
        if (is_file($DestinationPath . $file) && $file != '.' && $file != '..') 
            {
            $NotToday = date("d-m-Y");
            if ($file != $Import_file . $NotToday . ".csv")
                {
                $csvFile = fopen($DestinationPath . $file, 'r');
                $skipFirstRow = true;

                if ($csvFile !== false) 
                {
                    while (($row = fgetcsv($csvFile, 0, ';')) !== false)  
                    {
                        if ($skipFirstRow) {
                            $skipFirstRow = false;
                            continue; 
                        }
                        $PARCELNO = floatval($row[2]);
                        $PARCELNO_ST = floatval($row[1]);
                        $REFERENCE = $row[0];
                        $DT = date("Y-m-d", strtotime(substr($row[5], 6, 4) . '-' . substr($row[5], 3, 2) . '-' . substr($row[5], 0, 2)));                  
                    
    $data = array('PARCELNO' => $PARCELNO, 'PARCELNO_ST' => $PARCELNO_ST,'REFERENCE' => $REFERENCE, 'EVENT_DATE_TIME' => $DT);
    $Connection->insert("PD4", $data);
                    }
                    fclose($csvFile);                        
                if (!is_dir($AftImportPath)) {
                    if (!mkdir($AftImportPath, 0777, true)) {return false;}
                    }
                }
                rename($DestinationPath.$file, $AftImportPath.$file);
                }
            }
        }
    $SQL = "WITH CTE AS (SELECT [REFERENCE],ROW_NUMBER() OVER (PARTITION BY [REFERENCE] ORDER BY [EVENT_DATE_TIME] desc) row_num FROM dbo.PD4) DELETE FROM CTE WHERE row_num > 1";
    $stmt = $Connection->execute($SQL);
break;

//***************************************************************************************************//
case "DPD_import": 
    include('DPD_import.php'); 
    break;

case "PPL_import":
    include('PPL_import.php');
    PPL_import();
    break;

case "Packeta_import":
    include('Packeta_import.php');
    Packeta_Import();
    break;

case "Packeta_branch":
    include('Packeta_branch.php');
    break;
}    

}

function Exp_SQL_commands($job,$Export_path = '', $Export_file = '')
{
unset($Connection);
switch ($job) 
{

//***************************************************************************************************//
case "Parcel_Compare_14days":
    if (!isset($Connection)){$Connection = new PDOConnect("DPD_DB");} 
    $DT = date("Y_m_d");
    $SQL= "SELECT [REFERENCE],[PickUp_Date],[PARCELNO],[Notice],[Field] FROM [dbo].[CompareDPD_14days_View] Order by [PickUp_Date] desc";
    $stmt = $Connection->select($SQL);
    $Field = array("REFERENCE","PARCELNO");
    $Field1 = array("PickUp_Date");
    $array = array($stmt,$DT,$Field,$Field1);
    return  $array;
    break;

//***************************************************************************************************//
case "SWAP_14days":
    if (!isset($Connection)){$Connection = new PDOConnect("DPD_DB");} 
    $DT = date("Y_m_d");
    $SQL= "SELECT * FROM [dbo].[SWAP_14days_View] ORDER by [Created_Date] asc";
    $stmt = $Connection->select($SQL);
    $Field = array("REFERENCE","Parcel_First","Parcel_Second");
    $Field1 = array("Created_Date","PickUp_Date","Delivery_Date","Received_Date");
    $array = array($stmt,$DT,$Field,$Field1);
    return  $array;
    break;

//***************************************************************************************************//
case "ParcelShop_14days":
    if (!isset($Connection)){$Connection = new PDOConnect("DPD_DB");} 
    $DT = date("Y_m_d");
    $SQL= "SELECT [REFERENCE],[PS_Delv_Date],[PARCELNO],[Notice] FROM [dbo].[ParcelShop_14days_View] ORDER by [PS_Delv_Date] asc";
    $stmt = $Connection->select($SQL);
    $Field = array("REFERENCE","PARCELNO");
    $Field1 = array("PS_Delv_Date");
    $array = array($stmt,$DT,$Field,$Field1);
    return  $array;
    break;

//***************************************************************************************************//
case "SWAP_report":
    if (!isset($Connection)){$Connection = new PDOConnect("DPD_DB");} 
    $DT = date("Y_m_d");
    $SQL= "SELECT * FROM [dbo].[SWAP_report_View] ORDER by [Created_Date] asc";
    $stmt = $Connection->select($SQL);
    $Field = array("REFERENCE","Parcel_First","Parcel_Second");
    $Field1 = array("Created_Date","PickUp_Date","Delivery_Date","Received_Date");
    $array = array($stmt,$DT,$Field,$Field1);
    return  $array;
    break;

//***************************************************************************************************//
case "TradeIN_report":
    if (!isset($Connection)){$Connection = new PDOConnect("DPD_DB");} 
    $DT = date("Y_m_d");
    $SQL= "SELECT [REFERENCE],[Created_Date],[Delivery_Date],[PickUp_Date],[Received_Date],[Parcel_First],[Parcel_Second],[Status],[CdfCharger],[CdfHolder] FROM [dbo].[Trade_IN_report_View] ORDER by [Created_Date] asc";
    $stmt = $Connection->select($SQL);
    $Field = array("REFERENCE","Parcel_First","Parcel_Second");
    $Field1 = array("Created_Date","PickUp_Date","Delivery_Date","Received_Date");
    $array = array($stmt,$DT,$Field,$Field1);
    return  $array;
    break;

//***************************************************************************************************//
case "TradeIN_D_report":
    if (!isset($Connection)){$Connection = new PDOConnect("DPD_DB");} 
    $DT = date("Y_m_d");
    $SQL = "SELECT [Reference],[ParcelNO] as Parcel,[STATUS],[CdfCharger],[CdfHolder],convert(date,[Scantime]) as Date FROM [DPD_DB].[dbo].[TRADE_IN_D] order by date, Reference, ParcelNO";
    $stmt = $Connection->select($SQL);
    $Field = array("REFERENCE","Parcel");
    $Field1 = array("Date");
    $array = array($stmt,$DT,$Field,$Field1);
    return  $array;
    break;
   
//***************************************************************************************************//
case "CSS_scan": 
    if (!isset($Connection)){$Connection = new PDOConnect("DPD_DB");}
    $DT = date('Y-m-d', strtotime(date("Y-m-d") . ' -1 day'));  
    $SQL= "SELECT *  FROM [dbo].[Direct_View] WHERE CONVERT(DATE, [DT]) = :DT";
    $params = array('DT'=> $DT);
    $stmt = $Connection->select($SQL,$params);
    $Field = array("EAN","Parcel","Quantity");
    $Field1 = array("DT");
    $DT2= date('Y_m_d', strtotime(date("Y-m-d") . ' -1 day'));
    $array = array($stmt,$DT2,$Field,$Field1);
    return  $array;
    break;

//***************************************************************************************************//
case "NCI_Scan": 
    if (!isset($Connection)){$Connection = new PDOConnect("DPD_DB");}
    $DT = date('Y-m-d', strtotime(date("Y-m-d") . ' -1 day'));  
    $SQL= "SELECT *  FROM [dbo].[DirectNCI_View] WHERE CONVERT(DATE, [DT]) = :DT";
    $params = array('DT'=> $DT);
    $stmt = $Connection->select($SQL,$params);
    $Field = array("EAN","Parcel","Quantity");
    $Field1 = array("DT");
    $DT2= date('Y_m_d', strtotime(date("Y-m-d") . ' -1 day'));
    $array = array($stmt,$DT2,$Field,$Field1);
    return  $array;
    break;

//***************************************************************************************************//
case "EAN_export": 
    if (!isset($Connection)){$Connection = new PDOConnect("DPD_DB");}
    $DT = date("Y_m_d");  
    $SQL= "SELECT [EAN_PK],[EAN_CT],[EAN_BX],[MATNR],[MAKTX] FROM [dbo].[EAN]";
    $stmt = $Connection->select($SQL);
    $Field = array("EAN_PK","EAN_CT","EAN_BX");
    $array = array($stmt,$DT[1],$Field,"");
    return  $array;
    break;

//***************************************************************************************************//
case "Ecomm_non_del_Export": 
    if (!isset($Connection)){$Connection = new PDOConnect("DPD_DB");}  
    $DT=getWorkingDay(date("Y-m-d"));  
    $SQL= "SELECT *  FROM [dbo].[NonDlv_Dvc_View_export] where CONVERT(DATE, [EVENT_DATE_TIME]) = :DT order by [EVENT_DATE_TIME]";
    $params = array('DT'=> $DT[0]);
    $stmt = $Connection->select($SQL,$params);
    $Field = array("REFERENCE","PARCELNO");
    $Field1 = array("EVENT_DATE_TIME");
    $array = array($stmt,$DT[1],$Field,$Field1);
    return  $array;
    break;

//***************************************************************************************************//
case "Ecomm_non_DelDvc": 
    if (!isset($Connection)){$Connection = new PDOConnect("DPD_DB");}  
    $DT=getWorkingDay(date("Y-m-d"));  
    $SQL= "SELECT *  FROM [dbo].[NonDlv_Dvc_sum_View_export] where SUM = 0 and CONVERT(DATE, scantime)= :DT  order by Reference,Material";
    $params = array('DT'=> $DT[0]);
    $stmt = $Connection->select($SQL,$params);
    $Field = array("Reference","EAN","PARCELNO","ScanQuantity","OrdQuantity","Sum");
    $Field1 = array("Inbound","Scantime");
    $array = array($stmt,$DT[1],$Field,$Field1);
    return  $array;
    break;

//***************************************************************************************************//
case "Ecomm_non_DelDvc_miss": 
    if (!isset($Connection)){$Connection = new PDOConnect("DPD_DB");}  
    $DT=getWorkingDay(date("Y-m-d"));  
    $SQL= "SELECT *  FROM [dbo].[NonDlv_Dvc_sum_View_export] where SUM <> 0 and CONVERT(DATE, scantime)= :DT or  SUM is null and CONVERT(DATE,scantime) = :DT1 order by Reference,Material";
    $params = array('DT'=> $DT[0], 'DT1' => $DT[0]);
    $stmt = $Connection->select($SQL,$params);
    $Field = array("Reference","EAN","ScanQuantity","OrdQuantity","Sum","PARCELNO");
    $Field1 = array("Inbound","Scantime");
    $array = array($stmt,$DT[1],$Field,$Field1);
    return  $array;
    break;

//***************************************************************************************************//
case "Ecomm_non_Del_unproc": 
    if (!isset($Connection)){$Connection = new PDOConnect("DPD_DB");}  
    $DT=getWorkingDay(date("Y-m-d"));  
    $SQL= "SELECT *  FROM [dbo].[NonDlv_Dvc_sum_View_export] where convert(DATE, Inbound) = :DT and  scantime is null and Inbound is not null  order by Reference,Material";
    $params = array('DT'=> $DT[0]);
    $stmt = $Connection->select($SQL,$params);
    $Field = array("Reference","EAN","ScanQuantity","OrdQuantity","Sum","PARCELNO");
    $Field1 = array("Inbound","Scantime");
    $array = array($stmt,$DT[1],$Field,$Field1);
    return  $array;
    break;

//***************************************************************************************************//
case "Ecomm_SWAP_del_export": 
    if (!isset($Connection)){$Connection = new PDOConnect("DPD_DB");}  
    $DT=getWorkingDay(date("Y-m-d"));
    $SQL= "SELECT *  FROM [dbo].[SWAP_Dvc_View_Export] where CONVERT(DATE,EVENT_DATE_TIME) = :DT  order by [EVENT_DATE_TIME]";
    $params = array('DT'=> $DT[0]);
    $stmt = $Connection->select($SQL,$params);
    $Field = array("REFERENCE","PARCELNO_ST","PARCELNO");
    $Field1 = array("EVENT_DATE_TIME");
    $array = array($stmt,$DT[1],$Field,$Field1);
    return  $array;
    break;

//***************************************************************************************************//
case "Ecomm_SWAP_Dvc": 
    if (!isset($Connection)){$Connection = new PDOConnect("DPD_DB");}  
    $DT=getWorkingDay(date("Y-m-d"));  
    $SQL= "SELECT *  FROM [dbo].[SWAP_Dvc_sum_View_export] where CONVERT(DATE,scantime) = :DT order by Reference,Material";
    $params = array('DT'=> $DT[0]);
    $stmt = $Connection->select($SQL,$params);
    $Field = array("Reference","EAN_PK","SumOrd","SumScan","Sum","PARCELNO" );
    $Field1 = array("Inbound","Scantime");
    $array = array($stmt,$DT[1],$Field,$Field1);
    return  $array;
    break;

//***************************************************************************************************//
case "Ecomm_SWAP_Dvc_miss": 
    if (!isset($Connection)){$Connection = new PDOConnect("DPD_DB");}  
    $DT=getWorkingDay(date("Y-m-d"));  
    $SQL= "SELECT *  FROM [dbo].[SWAP_Dvc_sum_View_export] where SUM <> 0 and CONVERT(DATE,scantime) = :DT or  SUM is null and CONVERT(DATE,scantime) = :DT1 order by Reference,Material";
    $params = array('DT'=> $DT[0], 'DT1' => $DT[0]);
    $stmt = $Connection->select($SQL,$params);
    $Field = array("Reference","EAN_PK","SumOrd","SumScan","Sum","PARCELNO" );
    $Field1 = array("Inbound","Scantime");
    $array = array($stmt,$DT[1],$Field,$Field1);
    return  $array;
    break;

//***************************************************************************************************//
case "Ecomm_TRADEIN_del_export": 
    if (!isset($Connection)){$Connection = new PDOConnect("DPD_DB");}  
    $DT=getWorkingDay(date("Y-m-d"));
    $SQL= "SELECT *  FROM [dbo].[TRADEIN_Dvc_View_Export] where CONVERT(DATE,EVENT_DATE_TIME) = :DT  order by [EVENT_DATE_TIME]";
    $params = array('DT'=> $DT[0]);
    $stmt = $Connection->select($SQL,$params);
    $Field = array("REFERENCE","PARCELNO_ST","PARCELNO");
    $Field1 = array("EVENT_DATE_TIME");
    $array = array($stmt,$DT[1],$Field,$Field1);
    return  $array;
    break;

//***************************************************************************************************//
case "Ecomm_TRADEIN_Dvc": 
    if (!isset($Connection)){$Connection = new PDOConnect("DPD_DB");}  
    $DT=getWorkingDay(date("Y-m-d"));  
    $SQL= "SELECT *  FROM [dbo].[TRADEIN_Dvc_sum_View_export] where CONVERT(DATE,scantime) = :DT order by Reference,Material";
    $params = array('DT'=> $DT[0]);
    $stmt = $Connection->select($SQL,$params);
    $Field = array("Reference","EAN_PK","SumOrd","SumScan","Sum","PARCELNO" );
    $Field1 = array("Inbound","Scantime");
    $array = array($stmt,$DT[1],$Field,$Field1);
    return  $array;
    break;

//***************************************************************************************************//
case "Ecomm_TRADEIN_Dvc_miss": 
    if (!isset($Connection)){$Connection = new PDOConnect("DPD_DB");}  
    $DT=getWorkingDay(date("Y-m-d"));  
    $SQL= "SELECT *  FROM [dbo].[TRADEIN_Dvc_sum_View_export] where SUM <> 0 and CONVERT(DATE,scantime) = :DT or  SUM is null and CONVERT(DATE,scantime) = :DT1 order by Reference,Material";
    $params = array('DT'=> $DT[0], 'DT1' => $DT[0]);
    $stmt = $Connection->select($SQL,$params);
    $Field = array("Reference","EAN_PK","SumOrd","SumScan","Sum","PARCELNO" );
    $Field1 = array("Inbound","Scantime");
    $array = array($stmt,$DT[1],$Field,$Field1);
    return  $array;
    break;

//***************************************************************************************************//
case "Parcel_inbound": 
    if (!isset($Connection)){$Connection = new PDOConnect("DPD_DB");} 
    $currentDate = date('Y-m-25'); 
    $yesterdayDate = date('Y-m-d', strtotime($currentDate . ' -1 day'));
    list($year, $month ,$day) = explode('-', $currentDate);
    $Start_day = date('Y-m-d', strtotime("{$year}-{$month}-{$day} -1 month"));
    $SQL = "SELECT * FROM [DPD_DB].[dbo].[Inbound_report_View] WHERE convert(Date, [EVENT_DATE_TIME]) >= :DT AND convert(Date, [EVENT_DATE_TIME]) <= :DT1 order by [EVENT_DATE_TIME] asc";
    $params = array('DT'=> $Start_day, 'DT1' => $yesterdayDate);
    $stmt = $Connection->select($SQL,$params);
    $Field = array("PARCELNO");
    $Field1 = array("EVENT_DATE_TIME");
    $array = array($stmt,$yesterdayDate,$Field,$Field1);
    return  $array;
    break;

//***************************************************************************************************//
case "PMX_DSS_Time_export": 
    if (!isset($Connection)){$Connection = new PDOConnect("Produktivita");}  
    for ($i = 30; $i >= 0; $i--) 
    {  
    $date = new DateTime(date("Y-m-d"));
    $date = $date->sub(new DateInterval('P'.$i.'D'));   
    $DT=getWorkingDay($date->format("Y-m-d"));
    $date = new DateTime($DT[0]);
    $DTT= $date->format("Ymd");
    $SQL= "SELECT *  FROM [dbo].[DSSMITH_Time_View] WHERE [Process Start Date] = :DT";
    $params = array('DT'=> $DTT);
    $stmt = $Connection->execute($SQL,$params);
    $array = array($stmt,$DT[1],"","");

    if ($stmt["count"] !== 0)
        {
        if($DT[0] !== "") 
            {   
            $Export_file1 = $Export_file . $DT[1]. '.xlsx';
            }
        else
            {
            $Export_file1 = $Export_file. '.xlsx';    
            }

        if (!file_exists($Export_path.$Export_file1))
            {
            $excelExporter = new ExcelExporter($stmt["rows"] , $Export_path.$Export_file1);
            $excelExporter->exportToExcel();
            }
        }
    }
    break;

//***************************************************************************************************//
case "PMX_DSS_VOL_export": 
    if (!isset($Connection)){$Connection = new PDOConnect("Produktivita");}
    for ($i = 30; $i >= 0; $i--) 
    {    
    $date = new DateTime(date("Y-m-d"));
    $date = $date->sub(new DateInterval('P'.$i.'D'));   
    $DT=getWorkingDay($date->format("Y-m-d"));
    $date = new DateTime($DT[0]);
    $DTT= $date->format("Ymd");  
    $SQL= "SELECT *  FROM [dbo].[DSSMITH_VOL_View] WHERE [Process Start Date] = :DT";
    $params = array('DT'=>$DTT);
    $stmt = $Connection->execute($SQL,$params);
    $array = array($stmt,$DT[1],"","");

    if ($stmt["count"] !== 0)
        {
        if($DT[0] !== "") 
            {   
            $Export_file1 = $Export_file . $DT[1]. '.xlsx';
            }
        else
            {
            $Export_file1 = $Export_file. '.xlsx';    
            }

        if (!file_exists($Export_path.$Export_file1))
            {
            $excelExporter = new ExcelExporter($stmt["rows"] , $Export_path.$Export_file1);
            $excelExporter->exportToExcel();
            }
        }
    }
    break;
}
}

//find first workday back in time
function getWorkingDay($date,$daysBack = 1) 
    { 
    $dateObj = new DateTime($date);
    $dateObj->sub(new DateInterval("P" . $daysBack . "D"));
    if ($dateObj->format('N') >= 6) {
        $dateObj->modify('previous Friday');}
        $DTform =array ($dateObj->format('Y-m-d'),$dateObj->format('Y_m_d'));
    return $DTform;
    }
?>