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
$user   = 'cohost@equisourceholdings.com';
$pass   = 'costhost1535';
$port   = 993; // adjust according to server settings

$imapConn = imap_open('{'.$server.'/notls}', $user, $pass);

//FETCH DATA FROM COHOST TABLE
$cohostDetails =$db->query('SELECT * FROM cohost WHERE Active = "Y"')->fetchAll();

//FETCH DATA FROM EMAILQUEUECOHOST FOR PREVENT DUPLICASY
$sentEmail =$db->query('SELECT Emailid FROM EmailQueueCohost WHERE date(TimeDateSent) = date(now())')->fetchAll();

$sentEmailArray = [];
foreach ($sentEmail as $childArray){
    foreach ($childArray as $value){
        $sentEmailArray[] = $value;
     }
}


$date = date('j F Y');
$mails = imap_search($imapConn,'SINCE "'.$date.'"' );

 
if($mails && count($mails) > 0 && count($sentEmailArray) > 0){
$mails = array_diff($mails,$sentEmailArray);
}

if($mails && count($mails) > 0){
	foreach($mails as $val){
		$header  = imap_headerinfo($imapConn, $val);
		$subject = $header->subject;
		$body = get_mail_body($imapConn, $val);
		$htmlDom = new DOMDocument;
		@$htmlDom->loadHTML($body);
		$sendCohostEmail = true;
		if (strpos($subject, 'Reservation reminder') !== false || strpos($subject, 'Reservation confirmed') !== false || strpos($subject, 'Write a review') !== false || strpos($subject, 'Reservation canceled') !== false || strpos($subject, 'wrote you a review') !== false) {
			$sendCohostEmail = false;
		}else{
			$pTags = $htmlDom->getElementsByTagName('p');                    
			$pClass = $pTags[2]->textContent;
			if (strpos($subject, 'Inquiry ') !== false){
				$pClass = $pTags[4]->textContent;
			}
			$excludedPart =$db->query('SELECT * FROM ExcludeFromCohostNotifications')->fetchAll();
			$subjectCompareText = strtolower(str_replace(' ', '', $subject));
			$bodyCompareText = strtolower(str_replace(' ', '', $pClass));
			foreach($excludedPart as $excludedText){
				$excludedTextStr = strtolower(str_replace(' ', '', $excludedText['ExcludedText']));
				if (strpos($subjectCompareText, $excludedTextStr) !== false || strpos($bodyCompareText, $excludedTextStr) !== false){
					$sendCohostEmail = false;
				}
			}
		}
		
		if($sendCohostEmail) {
			$datetime =  trim($header->MailDate);
			$explodeDate = explode(" ", $datetime);
			$Date = date('Y-m-d',strtotime($explodeDate[0]));   
			$Time = $explodeDate[1];
			$anchorTags = $htmlDom->getElementsByTagName('a');
			$extractedAnchors = array();
			$emailLink = "";
			foreach($anchorTags as $anchorTag){
				$aHref = $anchorTag->getAttribute('href');
				if (strpos($aHref, 'https://www.airbnb.com/hosting/thread/') !== false || strpos($aHref, 'https://www.airbnb.com/hosting/inbox/folder/all/thread/') !== false){
					$emailLink  = $aHref;
					break;
				}
			}

			if($emailLink != ""){   
				$parseurl = parse_url($emailLink);
				$explodeurl = explode('/' , $parseurl['path']);
				$threadNo = $explodeurl[3];
				//$checkThreadNo = $db->query('SELECT * FROM CohostMessageLog where ThreadNumber = ?', $threadNo)->fetchAll();
				$messageDatetime = date("Y-m-d H:i:s", strtotime($Date.' '.$Time));
				//if(empty($checkThreadNo)) {
					echo "<br />";
					echo $pClass = remove_emoji($pClass);
					echo "<br />";
					$SetSendUnanswered=NULL;
					if($pClass == "Pre-approve / Decline"){
						$SetSendUnanswered="N";
					}
					 $cohostMessageLog = $db->query('INSERT into CohostMessageLog (Date, Time, MessageDateTime, ThreadNumber, Subject, Message, Sendunansweredreminder) VALUES (?, ?, ?, ?, ?, ?, ?)', $Date, $Time, $messageDatetime, $threadNo, $subject, $pClass, $SetSendUnanswered); 
					 $currentId = $db->lastInsertID();

					 // UPDATE QUERY TO SET SENDUNANSWEREDREMINDER = "N" FOR PAST ID AND SAME THREADNUMBER
					 $updateSendUnanswered= $db->query('UPDATE CohostMessageLog SET Sendunansweredreminder =? WHERE Id < ? AND ThreadNumber = ? AND Sendunansweredreminder IS NULL', "N", $currentId, $threadNo);
				//}
		   
				foreach ($cohostDetails as $cohostDetail){
					$phone =  $cohostDetail['Phone'];
					$phoneEmail = "1".$phone."@textmagic.com";
					sendemail($val, $phoneEmail, $subject, $emailLink);
				}
			}
		}								
	}
}

function remove_emoji($string) {
    $symbols = "\x{1F100}-\x{1F1FF}" // Enclosed Alphanumeric Supplement
        ."\x{1F300}-\x{1F5FF}" // Miscellaneous Symbols and Pictographs
        ."\x{1F600}-\x{1F64F}" //Emoticons
        ."\x{1F680}-\x{1F6FF}" // Transport And Map Symbols
        ."\x{1F900}-\x{1F9FF}" // Supplemental Symbols and Pictographs
        ."\x{2600}-\x{26FF}" // Miscellaneous Symbols
        ."\x{2700}-\x{27BF}"; // Dingbats

    return preg_replace('/['. $symbols . ']+/u', '', $string);
}

function sendemail($val, $phoneEmail, $subject, $emailLink){
	global $db;
	$currentdatetime=date('Y-m-d H:i:s');
	$fromEmail = 'cohost@equisourceholdings.com';
	$maxId =$db->query('SELECT MAX(Id) as max_id FROM EmailQueueCohost')->fetchAll();
	$maxId = $maxId[0]['max_id'];
	$maxId++;
	$emailIndex= base64_encode($maxId);
	$link ="https://equisourceholdings.com/cohost/cohostairbnb.php?emailindex=".$emailIndex;
	$bodyText ='<p>Please respond to this Airbnb message: '.$link.'</p>';
   
	$email = new PHPMailer();
	$email->SetFrom($fromEmail);
	$email->Subject   = $subject;
	$email->Body      = $bodyText;
	$email->AddAddress($phoneEmail);
	$email->isHTML(true);
	$email->Send();

	$emailQueueCohost = $db->query('INSERT into EmailQueueCohost (Emailid, FromEmail, Subject, BodyText, ToEmail, Status, ScheduleDate, TimeDateSent, AirbnbLink) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)', $val, $fromEmail, $subject, $bodyText, $phoneEmail, 'Sent', $currentdatetime, $currentdatetime, $emailLink);
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
    

exit;
?>