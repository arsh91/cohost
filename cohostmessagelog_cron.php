<?php
include 'db_connection.php';
include 'inc/auth.php';

$cohostMessageLogData = $db->query("SELECT CohostMessageLog.Id, CohostMessageLog.Cohostid,CohostMessageLog.ThreadNumber,cohost.Phone, cohost.Email FROM CohostMessageLog  JOIN cohost ON CohostMessageLog.Cohostid = cohost.Id WHERE CohostMessageLog.Date = CURDATE()  AND  CohostMessageLog.Time > (now() - interval 30 minute)  AND CohostMessageLog.ResponseDate IS NULL AND CohostMessageLog.ResponseTime IS NULL AND cohost.sendnotification = 'Y'")->fetchAll();

if(count($cohostMessageLogData) > 0){
  foreach($cohostMessageLogData as $value){
    $threadNumber = $value['ThreadNumber'];
    $cohostLogId = $value['Id'];
    $cohostId = $value['Cohostid'];
    $currentdatetime=date('Y-m-d H:i:s');
    $type = "airbnbBooking_update_notification";
    $subject ='Airbnb Bookings Notification';
    $threadNumberlink = "https://www.airbnb.com/hosting/thread/".$threadNumber;

    //SEND MAIL TO COHOST MEMBERS   
    $linkEmail ='<a href="update_airbnb_notificationread.php?cohostLogId='.$cohostLogId.'&threadNumber='.$threadNumber.'&scheduleDate='.$currentdatetime.'"> '.$threadNumberlink.' </a>';
    $fromEmail = 'toddknight@equisourceholdings.com';
    $bodyTextEmail ='<b>There is an Airbnb message that has been open for more than 30 minutes here: 
      </b>'.$linkEmail ;
    
      $db->query('INSERT into EmailQueue (FromEmail, Subject, BodyText, ToEmail, Status, ScheduleDate, Type, cohostLogId ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)', $fromEmail, $subject, $bodyTextEmail, $value['Email'], 'Pending', $currentdatetime, $type, $cohostLogId, );

    //TEXT MESSAGE TO COHOST MEMBERS
    $linkPhone ='<a href="update_airbnb_notificationread.php?cohostLogId='.$cohostLogId.'&threadNumber='.$threadNumber.'&scheduleDate='.$currentdatetime.'&phone=1'.'"> '.$threadNumberlink.' </a>';
    $phoneEmail = "1".$value['Phone']."@textmagic.com";
    $bodyTextPhone ='<b>There is an Airbnb message that has been open for more than 30 minutes here: 
      </b>'.$linkPhone ;
   
    $db->query('INSERT into EmailQueue (FromEmail, Subject, BodyText, ToEmail, Status, ScheduleDate, Type, cohostLogId ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)', $fromEmail, $subject, $bodyTextPhone, $phoneEmail, 'Pending', $currentdatetime, $type, $cohostLogId, );

    }
}

?>