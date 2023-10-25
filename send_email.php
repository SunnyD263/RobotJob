<?php
// PHP warnings switch off - to not disrupt the response 
error_reporting(E_ERROR | E_PARSE);



// CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function Send_email($ID,$recipient='',$cc='',$subject='',$body='',$attach='')
{
    $mail = new PHPMailer(true);
    if (!isset($Connection)){$Connection = new PDOConnect("Setup");} 
    $SQL= "SELECT * FROM RobotJob_Email_Srv WHERE ID = :ID";
    $params = array('ID'=> $ID);
    $stmt = $Connection->select($SQL,$params);
    $count = $stmt['count'];

if($count !== 0)
{
    try {
        //Server settings
        $mail->SMTPDebug = 0;                                       //Enable verbose debug output
        $mail->isSMTP();                                            //Send using SMTP
        $mail->Host       = $stmt["rows"][0]["Server"];                       //Set the SMTP server to send through
        $mail->SMTPAuth   = true;                                   //Enable SMTP authentication
        $mail->Username   = $stmt["rows"][0]["Username"];                //SMTP username
        $mail->Password   = base64_decode($stmt["rows"][0]["Password"]); //SMTP password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;            //Enable implicit TLS encryption
        $mail->Port       = $stmt["rows"][0]["Port"];                                    //TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`
        $mail->CharSet    = $stmt["rows"][0]["CharSet"];
    
        //Recipients
        $mail->setFrom('pickup@pmmaterialy.cz');
        $mail->addAddress('jan.sonbol@kuehne-nagel.com');
        if ($recipient !== "")
            {
            $data=explode(';',$recipient);
            foreach($data as $newAddress) {$mail->addAddress($newAddress);}
            }
        if ($cc !== "" )
            {
            $data=explode(';',$cc);
            foreach($data as $newAddress) {$mail->addCC($newAddress);}
            }
        //Content
        $mail->isHTML(true);                                        //Set email format to HTML
        $mail->Subject = $subject;
        $mail->Body   .= 'Dobrý den, ' . '<br><br>' . PHP_EOL;
        $mail->Body   .= $body . '<br><br>' . PHP_EOL;
        $mail->Body   .= 'S pozdravem' . '<br><br>' . PHP_EOL;
        $mail->Body   .= 'IS Robot' . '<br>' . PHP_EOL;
        if ($attach !== "" ) {$mail->addAttachment($attach);}
        $mail->send();
    } catch (Exception $e) {
        echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }
}
}
