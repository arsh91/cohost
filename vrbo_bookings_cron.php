<?php
/*THIS FILE IS RESPONSIBLE FOR SCANNING THE EMAILS THAT HAVE 'RESERVATION CONFIRMED' IN VRBO EMAIL AS SUBJECT
 *ALSO INSERT THE NEW RECORDS IN DB TABLE "airbnbbookings_cron" 
 *ALSO THIS CRON IS RESPONSIBLE FOR THE CANCELED RESERVATIONS 
*/

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'db_connection.php';
include 'inc/simple_html_dom.php';
require_once('Vendor/PHPMailer/src/PHPMailer.php');
require_once('Vendor/PHPMailer/src/Exception.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// EMAIL LOGIN CREDENTIALS
$server = 'mail.equisourceholdings.com';
$user   = 'vrbo@equisourceholdings.com';
$pass   = 'hudson1535';
$port   = 993; // adjust according to server settings

$imapConn = imap_open('{'.$server.'/notls}', $user, $pass);
$crondateCheck = date('j F Y', strtotime(date('j F Y') . " -7 days"));
// $crondateCheck = "5 August 2022"; 
$mails = imap_search($imapConn,'SINCE "'.$crondateCheck.'"' );

if($mails && count($mails) > 0){
	foreach($mails as $val){
		$header  = imap_headerinfo($imapConn, $val);

		if(isset($header->from)){
			$fromHostName = $header->from[0]->host;
			$subject = $header->subject;
			$body = get_mail_body($imapConn, $val);

			//THIS CONDITION CHECK THE CANCELED EMAIL AND UPDATE THE CANCELED FIELD
			if (strpos($subject, 'Booking canceled') !== false ) 
			{
				$body = get_mail_body($imapConn, $val);
				if($body){
					// GET HTML OF EMAIL BODY 
					$html = new simple_html_dom();
					$html->load($body);

					//GET MAIL DATE
					$header  = imap_headerinfo($imapConn, $val);
					$mailSentDate = $header->MailDate;
					$datetime =  trim($mailSentDate);
					$explodeDate = explode(" ", $datetime);
					$mailDate = date('Y-m-d',strtotime($explodeDate[0])); 

					//CONFIRMATION CODE
					$pieces = explode(" ", $subject);
					$confirmationcode = $pieces[count($pieces)-1];

					$updateCanceledfield= $db->query('UPDATE AirbnbBookings SET Canceled =? WHERE ConfirmationCode = ? AND Canceled IS NULL', $mailDate, $confirmationcode);
				}
			}

			// INSERT VRBO BOOKING DATA INTO airbnbbookings 
			if (strpos($subject, 'Instant Booking from ') !== false ) {
				if($body){
					// GET HTML OF EMAIL BODY 
					$html = new simple_html_dom();
					$html->load($body);

					//GET MAIL DATE
					$header  = imap_headerinfo($imapConn, $val);
					$mailSentDate = $header->MailDate;
					$datetime =  trim($mailSentDate);
					$explodeDate = explode(" ", $datetime);
					$mailDate = date('Y-m-d',strtotime($explodeDate[0])); 

					// reply_to eamil DATA 
					$reply_to = $header->reply_to[0]->mailbox.'@'.$header->reply_to[0]->host;

					//GET PROPERTY 
					$thElementCode = $html->find('th');

					$outer_div = $html->find(".gmail_quote",0);
					$dynamic_id_div = $outer_div->find("div",1)->attr;
					if(isset($dynamic_id_div['class'])){
						$dynamic_id_div = $outer_div->find("div",1)->attr;
						$dynamic_id_piece = explode("msg",$dynamic_id_div['class']);
						$dynamic_id =trim($dynamic_id_piece[1], "-");

						if($html->find('.m_-'.$dynamic_id.'body', 0)){
							$table_one = $html->find('.m_-'.$dynamic_id.'body', 0);
						}else{
							$table_one = $html->find('.m_'.$dynamic_id.'body', 0);
						}

						$table_two =  $table_one->find('table', 0)->children(0)->children(0)->children(0)->children(3)->children(0)->children(0);

						$main_table = $table_two->find('table', 0)->find('div', 0)->children(0)->find('tr', 0)->find('th[width="100%"]', 0)->find('tr th', 0);

						$prop =  $main_table->children(0)->find('tr', 0)->children(1)->find('a', 0)->plaintext;

						// PROPERTY ID FROM EMAIL
						$prop_piece = explode("#",$prop);
						$prp_id = $prop_piece[1]; 

						// Reservation ID
						$ReservationID = $main_table->children(2)->find('tr', 0)->children(1)->find('th', 0)->plaintext;  

						// get dates row
						$date_nights =  explode(",",$main_table->children(4)->find('tr', 0)->children(1)->find('th', 0)->plaintext);

						$date_nights1 =  explode("-",$main_table->children(4)->find('tr', 0)->children(1)->find('strong', 0)->plaintext);

						// GUESTS 
						// $all_guest = $main_table->children(6)->find('tr', 0)->children(1)->find('th', 0)->plaintext;  
						$guestName = $main_table->children(8)->find('tr', 0)->children(1)->find('th', 0)->plaintext; 

						//WE COUNT THE EMAIL BODY H5 AND THEN WE ARRANGE ACCORDING TO TWO TEMPLATES TYPES
						$headCount = count($table_two->find('table', 0)->find('div', 0)->find('h5')); 
						if($headCount == 3){
							$paymentHeading = $table_two->find('table', 0)->find('div', 0)->find('h5', 0);
							$totalPayoutHeading = $table_two->find('table', 0)->find('div', 0)->find('h5', 1);

						}else if($headCount == 4){
							$paymentHeading = $table_two->find('table', 0)->find('div', 0)->find('h5', 1);
							$totalPayoutHeading = $table_two->find('table', 0)->find('div', 0)->find('h5', 2);

						}

						$nightlyParentNextSiblingTable = $paymentHeading->parentNode()->parentNode()->parentNode()->parentNode()->parentNode()->parentNode()->parentNode()->parentNode()->next_sibling();

						// GET TOTAL NIGHTLY RATES
						$nightlyRate = $nightlyParentNextSiblingTable->find('p', 1)->plaintext; 

						// GET CLEANING FEE
						$cleaningFeeHeading = $nightlyParentNextSiblingTable->next_sibling();

						//CHECK IF THE STRING MATCH WITH CLEANING FEE AND ITS POSITIONS ARE DIFFERENT BECAUSE WE HAVE TWO TYPES OF MAILS TEMPLATE
						if(strpos($cleaningFeeHeading->find('p', 0)->plaintext, "Cleaning Fee") !== false){
							$cleaningFee = $cleaningFeeHeading->find('p', 1)->plaintext;
						}else{
							if(strpos($cleaningFeeHeading->next_sibling()->find('p', 0)->plaintext, "Cleaning Fee") !== false){
								$cleaningFee = $cleaningFeeHeading->next_sibling()->find('p', 1)->plaintext;
							} 
						}

						//THIS SECTION IS FOR TOTAL PAYOUTS FOR GETTING THE ESTIMATED PAYOUTS
						$EstimatedPayoutsTable = $totalPayoutHeading->parentNode()->parentNode()->parentNode()->parentNode()->parentNode()->parentNode()->parentNode()->parentNode();

						$finalEstimatedTableHeading = $EstimatedPayoutsTable->next_sibling()->next_sibling()->next_sibling()->next_sibling()->next_sibling();
						if(strpos($finalEstimatedTableHeading->find('p', 0)->plaintext, "Estimated payout") !== false ){
							$estimatedPayoutValue = $finalEstimatedTableHeading->find('p', 1)->plaintext;
						}

					}else{

						$outerTable = $outer_div->find("div",1)->find("table td center",0)->children(2)->find("td table",0);

						// WITH IMG FIRST OUTER TABLE 
						$firstOuterTable = $outerTable->find("td table",0);

						// WITHOUT IMG FIRST OUTER TABLE BODY
						$firstOuterTableWithoutImg = $firstOuterTable->find('div table', 0)->find('tbody', 0);

						//  GET PROPERTY ID 
						$propertyId = $firstOuterTableWithoutImg->children(0)->find('a',0)->plaintext;
						$prop_piece = explode("#",$propertyId);
						$prp_id = $prop_piece[1]; 

						// GET Reservation ID
						$ReservationID = $firstOuterTableWithoutImg->children(1)->find('div',0)->plaintext;  

						// GET DATES AND NIGHTS
						$date_nights1 = explode("-", $firstOuterTableWithoutImg->children(2)->find('strong',0)->plaintext);

						$date_nights =  explode(",",$firstOuterTableWithoutImg->children(2)->find('div',0)->plaintext);

						// GET GUEST
						// $all_guest = $firstOuterTableWithoutImg->children(3)->find('div',0)->plaintext;
						$guestName = $firstOuterTableWithoutImg->children(4)->find('div',0)->plaintext;  

						// THIS IS FOR NIGHTY RATE ESTIMATED PAYOUT 
						$outerdivtablebody= $outerTable->find("tbody",0)->children(1)->find("td div",0)->find("table tbody",0);

						// TOTAL NIGHTLY RATE 
						$nightlyRate = $outerdivtablebody->children(1)->find('div',0)->plaintext;


						// CLEANING FEE 
						if(strpos($outerdivtablebody->children(2)->find('td',0)->plaintext, "Cleaning Fee") !== false){
							$cleaningFee = $outerdivtablebody->children(2)->find('div',0)->plaintext;
						}elseif(strpos($outerdivtablebody->children(3)->find('td',0)->plaintext, "Cleaning Fee") !== false){
							$cleaningFee = $outerdivtablebody->children(3)->find('div',0)->plaintext;
						}

						// estimatedPayoutValue
						$estimatedPayoutValuetable = $outerdivtablebody->children();
						$estimatedPayoutValue =  end($estimatedPayoutValuetable)->find('div',0)->plaintext;
					}

					// checin checout dates
					$dates_both1 = explode(',', $date_nights1[0]);
					$dates_both2 = explode(',', $date_nights1[1]);

					// CHECKIN DATE 
					if(isset($dates_both1[1])){
						$year = $dates_both1[1];
					}else{
						$year = $dates_both2[1];
					}
					$checkin = $dates_both1[0].' '.$year;
					$new_checkin = date("Y-m-d", strtotime($checkin)); 

					// CHECKOUT DATE
					if(is_numeric($dates_both2[0])){
						$checkoutDateMonth =  date("M", strtotime($new_checkin)).' '.trim($dates_both2[0]);
					}else{
						$checkoutDateMonth = $dates_both2[0];
					}

					$checkout = $checkoutDateMonth.' '.$dates_both2[1]; 
					$new_checkout = date("Y-m-d", strtotime($checkout)); 

					//NIGHTS 
					$nights_piece =explode(' ', trim(end($date_nights))); 
					$nights = $nights_piece[0];

					// // GET ALL GUESTS
					// $all_guest_piece = explode(',', $all_guest); 
					// $guest_au_piece = explode(' ', trim($all_guest_piece[0])); 
					// $guest_au = $guest_au_piece[0];
					// $guest_child_piece = explode(' ', trim($all_guest_piece[1]));
					// $guest_child = $guest_child_piece[0]; 
					// $total_guest = $guest_au + $guest_child; 
				}

				// WITHOUT $ ALL AMOUNTS 
				$amount = str_replace(array("$", ","),"",$estimatedPayoutValue);

				// CLEANING FEE
				$finalcleaningFee = str_replace(array("$", ","),"",$cleaningFee);

				// PER DAY NIGHTLY RATE  
				$allnightlyRates = str_replace(array("$", ","),"",trim($nightlyRate)); 
				$finalNightlyRate = $allnightlyRates / $nights;

				$body = get_mail_body($imapConn, $val);

				//GET CONFIRMATIONCODE SO THAT SAME ROW DOESN'T INSERT TWICE
				$ConfirmationCodeData =$db->query('SELECT ConfirmationCode FROM AirbnbBookings WHERE ConfirmationCode = ?',$ReservationID)->fetchAll();	

				// IF CONFIRMATION CODE IS NOT ALREADY IN THE DATABASE SO INSERT THE NEW RECORD
				if(count($ConfirmationCodeData) < 1 ){	
					// PRPERTY NAME 
					$propertyNameData =$db->query('SELECT PropertyName, PropertyID FROM Properties WHERE VRBOID = ?', $prp_id)->fetchAll();
					$propertyName = $propertyNameData[0]['PropertyName'];
					$propertyId = $propertyNameData[0]['PropertyID'];

					// INSERT DATA IN AirbnbBookings
					$insert_data = $db->query('INSERT into AirbnbBookings ( PropertyName, PropertyId, Source, Amount, BookingDate, CheckInDate, Nights, CheckOutDate , NightlyRate, CleaningFee, ConfirmationCode, Guest, Type, VRBOresponseemail ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', $propertyName, $propertyId, 'VRBO', $amount, $mailDate, $new_checkin, $nights, $new_checkout, $finalNightlyRate, $finalcleaningFee, trim($ReservationID), trim($guestName), 'Reservation', $reply_to );
				}
			}
		}
	}
}

//FUNCTION TO GET MAIL BODY
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

?>