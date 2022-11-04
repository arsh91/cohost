<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include 'db_connection.php';

require_once('Vendor/PHPMailer/src/PHPMailer.php');
require_once('Vendor/PHPMailer/src/Exception.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

 // email login credentials
$server = 'mail.equisourceholdings.com';
$user   = 'cohost2@equisourceholdings.com';
$pass   = 'hudson1535';
$port   = 993; // adjust according to server settings


$imapConn = imap_open('{'.$server.'/notls}', $user, $pass); 

// $date = date('j F Y');
$date ='4 October 2022';
$mails = imap_search($imapConn,'SINCE "'.$date.'"' );
                                    
//FETCH DATA FROM EMAILQUEUECOHOST FOR PREVENT DUPLICASY
$sentEmail =$db->query('SELECT responseEmailId FROM CohostMessageLog WHERE date(Updated) = date(now())')->fetchAll();

$sentEmailArray = [];
foreach ($sentEmail as $childArray){
    foreach ($childArray as $value){
        $sentEmailArray[] = $value;
     }
}

if($mails && count($mails) > 0 && count($sentEmailArray) > 0){
	$mails = array_diff($mails,$sentEmailArray);
}

if($mails && count($mails) > 0){
	foreach($mails as $val){
				
		$body = get_mail_body($imapConn, $val);               
		$header  = imap_headerinfo($imapConn, $val);                   
		$responsesubject = $header->subject;
		$datetime =  trim($header->MailDate);
		$explodeDate = explode(" ", $datetime);
		$responseDate = date('Y-m-d',strtotime($explodeDate[0]));   
		$responseTime = $explodeDate[1];                   
		$responsedattime= date("Y-m-d H:i:s", strtotime($responseDate.' '.$explodeDate[1]));
	  
		$htmlDom = new DOMDocument;
		@$htmlDom->loadHTML($body);
		$anchorTags = $htmlDom->getElementsByTagName('a');
		$pTags = $htmlDom->getElementsByTagName('p');                    
		$pClass = removeHiddenCharacters($pTags[2]->textContent);
		$extractedAnchors = array();
		$emailLink = "";
		foreach($anchorTags as $anchorTag){
			$aHref = $anchorTag->getAttribute('href');
			if (strpos($aHref, 'https://www.airbnb.com/hosting/thread/') !== false){
				$emailLink  = $aHref;
				break;
			}
			
		}
		if($emailLink != ""){   
			$parseurl = parse_url($emailLink);
			$explodeurl = explode('/' , $parseurl['path']);
			$responsethreadNo = $explodeurl[3];

			$codes = $db->query('SELECT * FROM cohost WHERE Active ="Y"')->fetchAll();
			$n = 10; 
			$start = strlen($pClass) - $n;                                                                        
			$shortbody = substr($pClass, $start);
			foreach($codes as $code){
				$ycode = $code["code"];
				$cohostId=$code["Id"];
				if ($ycode !='' && strpos($shortbody, $ycode) !== false){
					$ID =$db->query("SELECT Id, Time, Date FROM CohostMessageLog WHERE ThreadNumber=? AND MessageDateTime < ? AND ResponseDate IS NULL AND ResponseTime IS NULL AND ResponseMessage IS NULL ORDER BY Id DESC Limit 0,1",$responsethreadNo, $responsedattime)->fetchAll();
				if(!empty($ID)) {
					foreach( $ID as $id){  
						$simpledatetime =$id['Date']." ".$id['Time'];                
						$responseSpeed= (strtotime($responsedattime) - strtotime($simpledatetime))/60;
							$roundresponseSpeed=round($responseSpeed);
							$cohostpayscales =$db->query("SELECT * FROM cohostpayscale WHERE  GreaterThan < ? AND LessThan > ?", $roundresponseSpeed, $roundresponseSpeed)->fetchAll();
							$Paymentamount = 0;
							if(!empty($cohostpayscales)){
								$Paymentamount= $cohostpayscales[0]['Paymentamount'];
							}
							$data= $db->query('UPDATE CohostMessageLog SET ResponseDate =?, ResponseTime =?, ResponseMessage=?, ResponseSpeed=?, Cohostid=?, PaymentAmount=?, responseEmailId=?, Updated=now() WHERE Id=? AND ThreadNumber=? AND ResponseDate IS NULL AND ResponseTime IS NULL AND ResponseMessage IS NULL', $responseDate, $responseTime, $pClass, $roundresponseSpeed, $cohostId, $Paymentamount,  $val, $id["Id"], $responsethreadNo);
						}									
					}					
				}										 
			}
		}                                
	}
}

function get_mail_body($imapConn, $email_number){
	$structure = imap_fetchstructure($imapConn, $email_number);
	if(isset($structure->parts) && is_array($structure->parts) && isset($structure->parts[1])) {
		$part = $structure->parts[1];
		$message = imap_fetchbody($imapConn,$email_number,2);

		switch ($part->encoding) {
			# 7BIT
			case 0:
				return $message;
			# 8BIT
			case 1:
				return quoted_printable_decode(imap_8bit($message));
			# BINARY
			case 2:
				return imap_binary($message);
			# BASE64
			case 3:
				return imap_base64($message);
			# QUOTED-PRINTABLE
			case 4:
				return quoted_printable_decode($message);
			# OTHER
			case 5:
				return $message;
			# UNKNOWN
			default:
				return $message;
		}
	}
}
    
/*TO REMOVE THE SPECIAL CHARACTERS / HIDDEN CHARACTERS*/
function removeHiddenCharacters($input){
	$output = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $input);
	return $output;
}
exit;
?>