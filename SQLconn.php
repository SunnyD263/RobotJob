<?php
require 'vendor/autoload.php'; 
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class PDOConnect 
{
    private static $instance;
    private $conn;
    private $ServerName;
    private $UID;
    private $PWD;
    private $Db;

    public function __construct($Db)    
    {
        try {
            set_time_limit(3600);
            $SQLtxt = file_get_contents('http://localhost/sqldb.txt');
            $items = explode(';', $SQLtxt);
            $this->ServerName = $items[0];
            $this->UID = $items[2];
            $this->PWD = base64_decode($items[3]);
            $this->Db = $Db;
            $this->conn = new PDO("sqlsrv:Server=$this->ServerName;Database=$this->Db", $this->UID, $this->PWD);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $e) {
            echo "Connection failed: " . $e->getMessage();
        }
    }

    public static function getInstance($Db)
    {
        if (!self::$instance) {
            self::$instance = new PDOConnect($Db);
        }
        return self::$instance;
    }

    public function select($query, $params = array()) 
    {
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->execute($params);
            $stmt= array(
                'rows'  => $stmt->fetchAll(PDO::FETCH_ASSOC),
                'count' => $stmt->rowCount()
                       );
            return $stmt;
        } catch(PDOException $e) {
            echo "Error SQL Select: " . $e->getMessage();
        }

    }

    public function selectToExcel($query, $params = array()) 
    {
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->execute($params);
    
            $columnNames = array();
            for ($i = 0; $i < $stmt->columnCount(); $i++) {
                $col = $stmt->getColumnMeta($i);
                $columnNames[] = $col['name'];
            }
    
            $data = array();
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $record = array();
                foreach ($columnNames as $colName) {
                    $record[$colName] = $row[$colName];
                }
                $data[] = $record;
            }
    
            return $data;
        } catch(PDOException $e) {
            echo "Error SQL Select: " . $e->getMessage();
        }
    }

    public function insert($table, $data) 
    {
        try {
            $columns = implode(',', array_keys($data));
            $values = ':' . implode(',:', array_keys($data));      
            $query = "INSERT INTO $table ($columns) VALUES ($values)";        

            $stmt = $this->conn->prepare($query);

            foreach ($data as $key => $value) {
                $stmt->bindValue(":$key", $value);
            }

            $stmt->execute();        
            return $stmt->rowCount();
        } catch (PDOException $e) {
            $errorCode = $e->getCode();

            // SQL Server deadlock error code
            if ($errorCode == '40001') {
                // Deadlock occurred, wait and retry
                $retryCount++;
                usleep(1000000); // Wait for 1 second (you can adjust this)
            } else {
                // Other SQL error, re-throw the exception
                throw $e;
            }
        }
    }
    
    public function update($query, $params = array()) 
    {
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->execute($params);
            return $stmt->rowCount();
        } catch(PDOException $e) {
            echo "Error SQL Update: " . $e->getMessage();
        }
    }

    public function tempTB($sql,$tableName)
    {
        try {
            $checkTableExists = "IF OBJECT_ID('$tableName', 'U') IS NULL BEGIN $sql END";
    
            $this->conn->exec($checkTableExists);
        } 
        catch (PDOException $e) {
            echo "Chyba při vytváření dočasné tabulky: " . $e->getMessage();
        }
    }
    public function execute($query, $params = array()) 
    {
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->execute($params);
            $x=$stmt->rowCount();
            if($stmt->rowCount() < 0)
                {
                $stmt= array(
                    'rows'  => $stmt->fetchAll(PDO::FETCH_ASSOC),
                    'count' => $stmt->rowCount()
                        );
                }
            else
                {
                $stmt= array('count' => 0);
                }
            return $stmt;
        } catch(PDOException $e) {
            echo "Error SQL Select: " . $e->getMessage();
        }
    }
}

// FTP downloader
class FTP
{    
    private $FTPServer;
    private $UID;
    private $PWD;
    private $remotefilepath;

    public function __construct($FTPID)    
    {
        try {
            if (!isset($Connection)){$Connection = new PDOConnect("Setup");} 
            $SQL=  "SELECT * FROM [Setup].[dbo].[RobotJob_FTP] WHERE [ID] = :ID ";
            $params = array('ID'=> $FTPID);
            $stmt = $Connection->select($SQL,$params);
            $count = $stmt['count'];
            if($count !== 0)
            {
            $this->FTPServer = $stmt['rows'][0]['Address'];
            $this->UID = $stmt['rows'][0]['UserName'];
            $this->PWD = base64_decode($stmt['rows'][0]["Password"]);   
            $this->remotefilepath = '/';
            }
        } catch(Exception $e)  {
            echo "Connection failed: " . $e->getMessage();
        }
    }

    public function FTP_download($localFilePath, $filename = "")
    {
        $proxyParams = ProxyParameters::getParameters();
            
        $curl = curl_init();
        $ftpUrl = "ftp://{$this->UID}:{$this->PWD}@{$this->FTPServer}{$this->remotefilepath}";
    
        curl_setopt($curl, CURLOPT_PROXY, $proxyParams['proxy']['http']);
        curl_setopt($curl, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
        curl_setopt($curl, CURLOPT_FTP_USE_EPSV, true);
    
        curl_setopt($curl, CURLOPT_URL, $ftpUrl);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    
        $fileList = curl_exec($curl);
    
        if (!empty($fileList)) {
      
        $Date= date("Ymd");
        $pattern = '/<A HREF="[^"]+">([^<]+)<\/A>/i';
        preg_match_all($pattern, $fileList, $matches);
        $files = $matches[1];
        $Server = $this->FTPServer;
        $filteredFiles = array();
        //DPD-FTP settings
        if ($Server == '195.47.89.194') 
        {
            $Date = date("Ymd");
            for ($i = 0; $i >= -3; $i--) 
                {            
                $modifiedDate = date("Ymd", strtotime("$Date $i days"));
                $filenameToMatch = $filename . $modifiedDate;
                foreach ($files as $remoteFile) 
                    {
                    if (preg_match("/$filenameToMatch/i", $remoteFile)) 
                        {
                        $filteredFiles[] = $remoteFile;
                        }
                    }
                }
            $filteredFiles = preg_grep("/^.*(?<!\.sem)$/i", $filteredFiles);
        }
        else 
            {
            $filteredFiles = preg_grep("/$filename/i", $files);
            }


        foreach ($filteredFiles as $remoteFile) 
            {
            if ($filename == "")
                {$localFile = $localFilePath . basename($remoteFile);}
            else
                { 
                $timestamp = date("_Ymd_His");
                $parts = explode(".", $filename);
                if (count($parts) == 2) 
                    {
                    $localFile =$localFilePath . $parts[0] . $timestamp . "." . $parts[1];
                    }
                else
                    {
                    $localFile = $localFilePath . $filename . $timestamp;                        
                    }
                }
            $localFileHandler = fopen($localFile, 'w');
            $currentFtpUrl = $ftpUrl . $remoteFile;
            curl_setopt($curl, CURLOPT_URL, $currentFtpUrl);
            curl_setopt($curl, CURLOPT_FILE, $localFileHandler); 
            curl_exec($curl);           
            fclose($localFileHandler); 
            }         
        curl_close($curl);
        }
    }
    
    public function FTP_upload($localFilePath, $remoteFilename)
    {
        $proxyParams = ProxyParameters::getParameters();

        $curl = curl_init();

        curl_setopt($curl, CURLOPT_PROXY, $proxyParams['proxy']['http']);
        curl_setopt($curl, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);

        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);

        $remotePath = $this->remotefilepath . $remoteFilename;
        curl_setopt($curl, CURLOPT_URL, "ftp://{$this->UID}:{$this->PWD}@{$this->FTPServer}{$remotePath}");
        curl_setopt($curl, CURLOPT_UPLOAD, 1);
        curl_setopt($curl, CURLOPT_INFILE, fopen($localFilePath, 'r'));
        curl_setopt($curl, CURLOPT_INFILESIZE, filesize($localFilePath));

        $result = curl_exec($curl);

        if ($result === false) {
            throw new Exception(curl_error($curl));
        }

        curl_close($curl);
    }

    public function getRemoteFileCreationTime($filename)
    {
        $connId = ftp_connect($this->FTPServer);
        $login = ftp_login($connId, $this->UID, $this->PWD);

        if ($connId && $login) {
            $fileTimestamp = ftp_mdtm($connId, $this->remotefilepath.$filename);

            if ($fileTimestamp != -1) {
                $createdDate = date('Y-m-d H:i:s', $fileTimestamp);
                return $createdDate;
            } else {
                return "Nepodařilo se získat čas vytvoření souboru.";
            }

            ftp_close($connId);
        } else {
            return "Nepodařilo se připojit k FTP serveru.";
        }
    }    
}


class ProxyParameters
{
    public static function getParameters()
    {
        $Proxytxt = file_get_contents('http://localhost/proxy.txt');
        $items = explode(';', $Proxytxt);
        return [
            'proxy' => [
                'http' => "http://".$items[0].":".$items[1],
                'ssl' => "http://".$items[0].":".$items[1]
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
            ]
        ];
    }
}



class ExcelExporter {
    private $data; 
    private $filename;

    public function __construct($data, $filename) {
        $this->data = $data;
        $this->filename = $filename;
    }

    public function exportToExcel($Number='',$DT='') {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $headerRow = 1;
        $headerRowData = $this->data[0];

        $column = 1;
        foreach ($headerRowData as $columnName => $value) {
            $sheet->setCellValueExplicitByColumnAndRow($column, $headerRow, $columnName,\PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->getColumnDimensionByColumn($column)->setAutoSize(true);    
            $column++;
        }

        $dataRow = 2;
        foreach ($this->data as $row) 
            {
            $column = 1;
            foreach ($row as $columnName => $value) 
                {             

                $Field = $Number;
                $Field1 = $DT;
                if(in_array($columnName,$Field))
                    {
                    if ($value == "")
                        {
                        $sheet->setCellValueExplicitByColumnAndRow($column, $dataRow, $value, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                        }
                    else
                        {
                        $sheet->setCellValueExplicitByColumnAndRow($column, $dataRow, $value, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                        $sheet->getStyleByColumnAndRow($column, $dataRow)->getNumberFormat()->setFormatCode('0');
                        }
                    }
                elseif (in_array($columnName,$Field1))
                    {
                    if ($value == "")
                        {
                        $sheet->setCellValueExplicitByColumnAndRow($column, $dataRow, $value, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                        }
                    else
                        {
                        $excelTimestamp = Date::PHPToExcel(strtotime($value));
                        $sheet->setCellValueExplicitByColumnAndRow($column, $dataRow, $excelTimestamp, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                        $sheet->getStyleByColumnAndRow($column, $dataRow)->getNumberFormat()->setFormatCode('yyyy-mm-dd hh:mm:ss');
                        }

                    }
                else 
                    {
                    $sheet->setCellValueExplicitByColumnAndRow($column, $dataRow, $value, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                    }
                $sheet->getColumnDimensionByColumn($column)->setAutoSize(true);              
                $column++;
                }
            $dataRow++;
            }

        $writer = new Xlsx($spreadsheet);
        $writer->save($this->filename);
    }
}

?>