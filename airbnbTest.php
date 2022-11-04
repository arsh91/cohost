<?php

/*THIS FILE IS RESPONSIBLE FOT SCANNING THE EMAILS THAT HAVE 'RESERVATION CONFIRMED' AS SUBJECT
 *ALSO INSERT THE NEW RECORDS IN DB TABLE "airbnbbookings_cron" 
 *SEND EMAIL TO COHOST AND SAVE DATA IN "emailqueue" WITH TYPE "cohost_airbnbbooking_notification" 
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
$user   = 'cohost@equisourceholdings.com';
$pass   = 'costhost1535';
$port   = 993; // adjust according to server settings

$imapConn = imap_open('{'.$server.'/notls}', $user, $pass);
// $crondateCheck = date('j F Y', strtotime(date('j F Y') . " -48 hours"));
$crondateCheck = "5 October 2022"; 
$mails = imap_search($imapConn,'SINCE "'.$crondateCheck.'"' );

if($mails && count($mails) > 0){
	foreach($mails as $val)
	{
		$header  = imap_headerinfo($imapConn, $val);
		if(isset($header->from)){
			$fromHostName = $header->from[0]->host;
			$subject = $header->subject;

			//THIS CONDITION CHECK THE CANCELED EMAIL AND UPDATE THE CANCELED FIELD
			if (strpos($subject, 'Reservation canceled') !== false && $fromHostName == "airbnb.com" ) 
			 {
				$body = get_mail_body($imapConn, $val);
					if($body){

						print_r($body);
						// GET HTML OF EAMIL BODY 
						$html = new simple_html_dom();
						$html->load($body);

						//GET MAIL DATE
						$header  = imap_headerinfo($imapConn, $val);
						$mailSentDate = $header->MailDate;
						$datetime =  trim($mailSentDate);
						$explodeDate = explode(" ", $datetime);
						$mailDate = date('Y-m-d',strtotime($explodeDate[0])); 

						//CONFIRMATION CODE
						$thElementCode = $html->find('th[class="columns first large-12 last small-12"]', 2);
						 $get_p = $thElementCode->find('p[class^="body"]', 0)->plaintext;
					 	$get_code = trim($get_p);
						$pieces = explode(" ", $get_code);
						$pqr = $pieces[count($pieces)-1];
						$confirmationcode =  rtrim($pqr, '.');

						//$updateCanceledfield= $db->query('UPDATE AirbnbBookings SET Canceled =? WHERE ConfirmationCode = ? AND Canceled IS NULL', $mailDate, $confirmationcode);
					}
			 }

			//THIS CONDITION CHECK THE CONFIRMATION EMAIL AND UPDATE THE DATA
			if (strpos($subject, 'Reservation confirmed') !== false && $fromHostName == "airbnb.com" ) 
			{
			$body = get_mail_body($imapConn, $val);
			// echo "<pre>"; print_r($body); echo "</pre>";
			if($body){
				// GET HTML OF EAMIL BODY 
				$html = new simple_html_dom();
				$html->load($body);
				
				//GET GUEST NAME
				 $thElementGuest = $html->find('th[class="small-9 large-10 columns last valign-mid"]', 0);
				 if($thElementGuest){
				  $guestname = $thElementGuest->find('p[class="body-text heavy"]', 0)->plaintext;
				 }else{

					$guestname = $html->find('td[class="height_64_48 width_64_48 right_3_2"]', 0)->next_sibling()->find('a',0)->plaintext;

				 }
				
				//GET MAIL DATE
				$header  = imap_headerinfo($imapConn, $val);
				$mailSentDate = $header->MailDate;
				$datetime =  trim($mailSentDate);
				$explodeDate = explode(" ", $datetime);
				$mailDate = date('Y-m-d',strtotime($explodeDate[0])); 
				
				// SECOND TYPE OF TEMPELATE 
				$outerDiv = $html->find('div[class="normal-container"]', 0);

				//GET TITLE 
				 $thElementTitle = $html->find('th[class="small-12 large-12 columns first destination-card-text row-pad-bot-3"]', 0);
				 if($thElementTitle){
					 $title = $thElementTitle->find('p[class="headline light"]', 0)->plaintext;
				 }else{					
					 $title = $outerDiv->children(5)->find('h2[class="heading2"]', 0)->plaintext;
				 }
				
				 $airbnbListingTitles = removeHiddenCharacters(trim($title));
				 $listingtitles = trim(substr($airbnbListingTitles, 0, 7));
				$arr = explode(":", $airbnbListingTitles, 2);
				$listingtitles = $arr[0];
				  
				//CHECKIN DATE
				 $thElementIn = $html->find('th[class="small-5 large-5 columns first"]', 0);
				 if($thElementIn){
					 $indate = $thElementIn->find('p[class="body-text-lg light"]', 1)->plaintext;
				 }else{
					 $indate1  = $outerDiv->children(7)->find('p[class="regular book"]', 0)->plaintext;
				 	$replaceInDate = removeHiddenCharacters($indate1);
					$inpieces = explode(" ", $replaceInDate);
					 $year = abs(filter_var($inpieces[3], FILTER_SANITIZE_NUMBER_INT));
					 $indate = $inpieces[1]." "." ".$inpieces[2]." ".$year;
				 }
				 $replaceInDate = removeHiddenCharacters($indate);
				$checkindate = date('Y-m-d',strtotime($replaceInDate));
				 $pieces = explode("-", $checkindate);
				 $checkInYear = (explode("-", $checkindate))[0];
				 $checkInMonth = (explode("-", $checkindate))[1];
	
				//CHECKOUT DATE	
				$thElementOut = $html->find('th[class="small-5 large-5 columns last"]', 0);

				if($thElementOut){
					$outdate = $thElementOut->find('p[class="body-text-lg light text-right"]', 1)->plaintext;
				}else{
					$outdate1  = $outerDiv->children(7)->find('p[class="regular book"]', 1)->plaintext;
				 	$replaceInDate = removeHiddenCharacters($outdate1);
					$outpieces = explode(" ", $replaceInDate);
					 $year = abs(filter_var($outpieces[3], FILTER_SANITIZE_NUMBER_INT));
					 $outdate = $outpieces[1]." "." ".$outpieces[2]." ".$year;
				}
				$replaceDate = removeHiddenCharacters($outdate);
				$checkoutDate = date('Y-m-d',strtotime($replaceDate));

				//CONFIRMATION CODE
				$thElementCode = $html->find('th[class="small-7 large7 columns first valign-mid"]', 0);

				if($thElementCode){
					$code = $thElementCode->find('p[class="body-text light body-link-rausch"]', 0)->plaintext;
				}else{
					$code  = $outerDiv->children(11)->find('p[class="regular book"]', 0)->plaintext;

				}
				$confirmationcode = trim($code);
				
				//NUMBER OF NIGHTS AND ITS RATE
				$thElementNight = $html->find('th[class="large-6 small-6 columns first"]', 0);
				if($thElementNight){
					$row = $thElementNight->find('p[class="body-text light"]', 0)->plaintext;

				}else{
					 $row  = $outerDiv->children(14)->find('td', 0)->children(0)->find('p[class="regular book"]', 0)->plaintext;

				}
				$pieces = explode("x", $row);
				$piece = explode(" ", $pieces[1]);
				 $numberofnights = $piece[1];
				$nightlyrates = $pieces[0];
				  $nightlyrate = str_replace("$","", str_replace("$","", $pieces[0])); echo "<br>";
				
				//TOTAL NIGHTLY RATES
				$thElementTotal = $html->find('th[class="large-6 small-6 columns last"]', 0);
				if($thElementTotal){
				 $row = $thElementTotal->find('p[class="body-text light text-right"]', 0)->plaintext;
					
				}else{
					 $row  = $outerDiv->children(14)->find('td', 0)->children(0)->find('p[class="regular book"]', 1)->plaintext;
					
				}
				$totalNightyCharge = str_replace("$","", $row);
				
				//CLEANING RATES
				$thElementClean = $html->find('th[class="large-6 small-6 columns last"]', 1);
				if($thElementClean){
					$row = $thElementClean->find('p[class="body-text light text-right"]', 0)->plaintext;

				}else{
					 $row  = $outerDiv->children(14)->find('td', 0)->children(1)->find('p[class="regular book"]', 1)->plaintext;
				}
				$cleaningfee = str_replace("$","", $row);
			
				//SERVICE FEE
				$thElement = $html->find('th[class="large-6 small-6 columns last"]', 2);
				if($thElement){
					$row = $thElement->find('p[class="body-text light text-right"]', 0)->plaintext;

				}else{
					 $row  = $outerDiv->children(14)->find('td', 0)->children(2)->find('p[class="regular book"]', 1)->plaintext;
				}
				$servicefee = str_replace("$","", removeHiddenCharacters($row));
			
				//AMOUNT
				$rowAmount1 = $html->find('p[class="body-text heavy text-right row-pad-top-1"]', 0);
				if($rowAmount1){
					$rowAmount = $rowAmount1->plaintext;
				}else{
					$rowAmount  = $outerDiv->children(14)->find('td', 0)->children(4)->find('h3[class="heading3"]', 1)->plaintext;
				}
				 $amount = str_replace("$","",$rowAmount);
				
				$newRateSameMonthSameYear = "";
				$newRateSameMonthNextYear  = "";
				$propertyName = '';
				
				//INSERT RECORDS IN AIRBNBBOOKINGS			 
				//GET CONFIRMCODE SO THAT SAME ROW DOESN'T INSERT TWICE
				
				$ConfirmationCodeData =$db->query('SELECT ConfirmationCode FROM AirbnbBookings WHERE ConfirmationCode = ?',$confirmationcode)->fetchAll();			
				
				//IF RECORD IS NOT THERE THEN INSERTION WILL OCCUR							
				if(count($ConfirmationCodeData) < 1 )
				{			
					echo "done"; echo "<br>";

					//GET PROPERTY NAME 			
					$propertyNameData =$db->query('SELECT Airbnblistingtitle, PropertyID, PropertyName FROM Properties WHERE Airbnblistingtitle LIKE "'.$listingtitles.'%"')->fetchAll();				
					if(count($propertyNameData) > 0 )
					{	
						 $propertyName = $propertyNameData[0]['PropertyName']; 
						 $propertyId = $propertyNameData[0]['PropertyID']; 
					}	

					//TO SET "NewRateSameMonthSameYear" AND "NewRateSameMonthNextYear" FIELDS 				
					$nightlyRateQuery =$db->query('SELECT * FROM AirbnbBookings WHERE PropertyName = ? AND NightlyRate > ? AND MONTH(CheckInDate) = '.$checkInMonth.' AND YEAR(CheckInDate) ='.$checkInYear ,$propertyName, $nightlyrate)->fetchAll();

					//CASEI:- RECORD FOUND, MEANS NIGHTLY RATE IS THE HIGHEST ONE IN DB
					if(count($nightlyRateQuery) > 0 )
					{	
						 $newRateSameMonthSameYear =  0.00; 
						 $newRateSameMonthNextYear =  0.00; 
					}
					else
					{
						//CASEII:- RECORD NOT FOUND, MEANS NEW NIGHTLY RATE IS THE HIGHEST ONE
						$rateSameMonthSameYear =  $nightlyrate * 1.10;
						 $newRateSameMonthSameYear = round($rateSameMonthSameYear,2); 

						$rateSameMonthNextYear =  $nightlyrate * 1.20;
						 $newRateSameMonthNextYear = round($rateSameMonthNextYear,2); 
						
					}	
					
					//INSERT NEW RECORDS
					//$insert_data = $db->query('INSERT into AirbnbBookings ( PropertyName, PropertyId, Source, Amount, BookingDate, CheckInDate, Nights, CheckOutDate , NightlyRate, CleaningFee, HostFee, ConfirmationCode, Guest, NewRateSameMonthSameYear, NewRateSameMonthNextYear, AirbnbListingTitle, Type ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', $propertyName, $propertyId, 'Airbnb', $amount, $mailDate, $checkindate, $numberofnights, $checkoutDate, $nightlyrate, $cleaningfee, $servicefee, $confirmationcode, trim($guestname),$newRateSameMonthSameYear, $newRateSameMonthNextYear, $airbnbListingTitles, 'Reservation' );

					// CODE TO DISPLAY ALL NIGHT RATES OF MONTHS START FROM HERE
					$time=strtotime($checkindate);
					$year=date("Y",$time);
					$digitMonth=date("n",$time);
					$nxtYear=$year+1;
					
					$pernightlyRate =$db->query('SELECT DISTINCT id, CheckInDate, CheckOutDate, NightlyRate FROM AirbnbBookings WHERE PropertyId =? AND (MONTH(CheckOutDate) =? AND (YEAR(CheckOutDate)=? OR YEAR(CheckOutDate)=?) OR MONTH(CheckInDate)= ? AND (YEAR(CheckInDate)= ? OR YEAR(CheckInDate)=? ) ) ORDER BY CheckInDate ASC, CheckOutDate ASC', $propertyId, $digitMonth, $year, $nxtYear, $digitMonth, $year, $nxtYear)->fetchAll();

				
					if(!empty($pernightlyRate)){
						$date=[];
						foreach($pernightlyRate as $dates){
							// FUNCTION RETURN ALL DATES BETWEEN CHECKIN AND CHECKOUT  
							$date[] = displayDates($dates['id'], $dates['CheckInDate'], $dates['CheckOutDate'], $dates['NightlyRate']);
						}
					}

					// EMPTY ARRAY WITH ALL MONTH DATES
					$currentYearList=[];
					$nxtYearList=[];
					for($d=1; $d<=31; $d++){
						$currentTime=mktime(12, 0, 0, date("m",$time), $d, $year);
						$nxtTime=mktime(12, 0, 0, date("m",$time), $d, $nxtYear);  
						// CURRENT YEAR ARRAY        
						if (date('m', $currentTime)==$digitMonth){       
							$currentYearList[date('d-m-Y', $currentTime)]=[
								"id" => "",
								"date" => date('d-m-Y', $currentTime),
								"rate" => ""
							];
						}
						// NEXT YEAR ARRAY
						if (date('m', $nxtTime)==$digitMonth){       
							$nxtYearList[date('d-m-Y', $nxtTime)]=[
								"id" => "",
								"date" => date('d-m-Y', $nxtTime),
								"rate" => ""
							];
						}
					}

					$nightlyRateData=[];
					// CHANGING DATE ARRAY FROM MULTIDIMENSIONAL TO SINGLE
					if(!empty($date)){
						foreach($date as $val){
							foreach($val as $v){
								$nightlyRateData[]=$v;		
							}	
						}

						$highRateDate=[];
						// IF ARRAY CONTAIN SAME DATE VALUE THEN GET HIGHEST ID VALUE
						foreach($nightlyRateData as $key => $data){
								if(!isset($highRateDate[$data['date']])){
									$highRateDate[$data['date']] = $data;
								}
								elseif( $highRateDate[$data['date']]['id'] < $data['id'] ){
									$highRateDate[$data['date']] = $data;
								}
						}

						// FILTER ARRAY ACCORDING TO CHECKIN MONTH
						foreach($highRateDate as $key => $newData){

							$rateMonth = date("n", strtotime($newData['date']));
							$rateYear = date("Y", strtotime($newData['date']));
								if($rateMonth == $digitMonth){

									// CURRENT YEAR ARRAY
									if($rateYear == $year){
										$currentYearList[$newData['date']] = $newData;
									}
									// NEXT YEAR ARRAY
									if($rateYear == $nxtYear){
										$nxtYearList[$newData['date']] = $newData;

									}
								}
						}

					}

					$currentYearMonthRateStr="";
					$nxtYearMonthRateStr="";
					
					// CHECKIN DATE MONTH AND CURRENT YEAR STRING
					foreach($currentYearList as $currentRate){
						$currentYearMonthRateStr .= date("m-d-Y", strtotime($currentRate['date'])).":&nbsp;&nbsp;".$currentRate['rate'].'<br>';
					}
					// CHECKIN DATE MONTH AND NEXT YEAR STRING
					foreach($nxtYearList as $nextRate){
						$nxtYearMonthRateStr .= date("m-d-Y", strtotime($nextRate['date'])).":&nbsp;&nbsp;".$nextRate['rate'].'<br>';
					}
												
					//STEP3 :- SEND EMAIL TO COHOST MEMBERS cohost.sendratechangenotification = Y 

					$cohostEmail =$db->query('SELECT Id, Fname, Lname, Email FROM cohost WHERE sendratechangenotification = "Y"')->fetchAll();

					// SEND MAIL FUNCTION TO COHOST MEMBERS
					$fromEmail = 'cohost@equisourceholdings.com';
					$currentdatetime=date('Y-m-d H:i:s');
					$type = "cohost_airbnbbooking_notification";
					$subject ='Rate change needed for '.$propertyName;
					$month=date("F",$time);
					$bodyText = '<b>Booking Date: </b>'.date("m-d-Y", strtotime($mailDate)).'<br /><b>Check in date: </b>'.date("m-d-Y", strtotime($checkindate)).'<br /><b>Check out date: </b>'.date("m-d-Y", strtotime($checkoutDate)).'<br /><b>New Nightly Rate: </b>'.$nightlyrate.'<br /><br/><b>New rate '.$month.' '.$year.': '.$newRateSameMonthSameYear.'</b><br />'.$currentYearMonthRateStr.'<b><br/>New rate '.$month.' '.($year + 1).': '.$newRateSameMonthNextYear.'</b><br />'.$nxtYearMonthRateStr;
					// echo "<pre>"; print_r($bodyText); echo "</pre>";
					foreach($cohostEmail as $value)
					{
						//$db->query('INSERT into EmailQueue (FromEmail, Subject, BodyText, ToEmail, Status, ScheduleDate, Type, cohostId) VALUES (?, ?, ?, ?, ?, ?, ?, ?)', $fromEmail, $subject, $bodyText, $value['Email'], 'Pending', $currentdatetime, $type, $value['Id']);

					}
					
				 }
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
function displayDates($id, $date1, $date2, $rates, $format = 'd-m-Y' ) {
	$dates = [];
	$current = strtotime($date1);
	$date2 = strtotime($date2);
	$date2 = strtotime('-1 day', $date2);
	$stepVal = '+1 day';
	while( $current <= $date2 ) {
	   $dates[] =[
		"id" => $id,
		"date" => date($format, $current),
		"rate" => $rates
	];
	   $current = strtotime($stepVal, $current);
	}
	return $dates;
}
?>