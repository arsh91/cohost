<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include 'db_connection.php';
include 'inc/simple_html_dom.php';

require_once('Vendor/PHPMailer/src/PHPMailer.php');
require_once('Vendor/PHPMailer/src/Exception.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// SELECT DATA TO CREATE INSPECTION FROM AIRBNBBOOKINGS TABLE
 $CohostMessageLogData =$db->query('SELECT * FROM CohostMessageLog WHERE MessageDateTime > SUBTIME(CURRENT_TIMESTAMP(), "2:0:0") AND HOUR(MessageDateTime) > 8 AND HOUR(MessageDateTime) < 21 AND MINUTE(TIMEDIFF(MessageDateTime, CURRENT_TIMESTAMP)) > 20 AND ResponseDate IS NULL AND ResponseTime IS NULL AND Sendunansweredreminder IS NULL')->fetchAll();

if(count($CohostMessageLogData) > 0) {

    // SELECT DATA FROM COHOST TABLE
    $CohostData= $db->query('SELECT * FROM cohost WHERE sendnotification= "Y"')->fetchAll();

    foreach($CohostMessageLogData as $data){

        //THIS IS TO PREVENT DULPICACY INSERTION OF RECOARDS
        //$CohostMessageLogID= $db->query('SELECT CohostMessageLogID FROM EmailQueueCohost WHERE CohostMessageLogID= ?',$data['Id'] )->fetchAll();

       //if(count($CohostMessageLogID) < 1){

            $thread_url = "https://www.airbnb.com/hosting/thread/".$data['ThreadNumber'];

            //GET TIME DIFFERECE BETWEEN CUREENT TIME AND MessageDateTime
            $cureent_time =  date("Y-m-d H:i:s");
            $time = $data['MessageDateTime'];
            $a = new DateTime($cureent_time);
            $b = new DateTime($time);
            $interval = $a->diff($b);
            $minutes = $interval->days * 24 * 60;
            $minutes += $interval->h * 60;
            $minutes += $interval->i;

            //SEND EAMIL TO COHOST MEMBERS
            foreach($CohostData as $CoData){
                $from_email = 'cohost@equisourceholdings.com';
                $toEmail = "1".$CoData['Phone']."@textmagic.com";
                $schedule_datetime = date('Y-m-d H:i:s');
                $subject = "";
                $unsubscribe_link = "https://cohost.equisourceholdings.com/stop_unanswered_notification.php?id=".$data["Id"]."&threadNo=".$data['ThreadNumber'];
                $bodyText = 'This airbnb message has been open for '.$minutes.' minutes.<br><br>Click for details: <a href="'.$thread_url.'" target="_blank">'.$thread_url.'</a><br><br><small><i>To stop reminder notifications for this thread, <a href="'.$unsubscribe_link.'" target="_blank">Click here.</a></i></small>';

                //  SEND MAIL
                 $email = new PHPMailer();
                $email->SetFrom($from_email);
                $email->Subject   = $subject;
                $email->Body      = $bodyText;
                $email->AddAddress($toEmail);
                $email->isHTML(true);

               if($email->Send()){
                    //INSERT DATA IN EmailQueueCohost TABLE
                    $emailQueueCohost = $db->query('INSERT into EmailQueueCohost (Emailid, FromEmail, Subject, BodyText, ToEmail, Status, ScheduleDate, TimeDateSent, CohostMessageLogID) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)', 0, $from_email, $subject, $bodyText, $toEmail, 'Sent', $schedule_datetime, $schedule_datetime, $data['Id']);
                    echo "Text email sent to ".$toEmail;
               }
            }

        //}
    }
}

?>
