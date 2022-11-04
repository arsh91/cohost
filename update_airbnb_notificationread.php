<?php
include 'db_connection.php';
include 'inc/auth.php';
if( isset($_GET['cohostLogId']) && isset($_GET['threadNumber']) ){
   $cohostLogId = $_GET['cohostLogId'];
   $threadNumber = $_GET['threadNumber'];
   $scheduleDate = $_GET['scheduleDate'];
   $current_date = date("Y-m-d H:i:s");

   if(isset($_GET['phone']) && $_GET['phone'] == "1"){//UPDATE FOR EMAIL LINK
       $db->query('UPDATE EmailQueue SET NotificationRead = ? WHERE ToEmail LIKE "%textmagic.com%" AND ScheduleDate= ? AND NotificationRead IS NULL AND cohostLogId= ?' , $current_date,$scheduleDate, $cohostLogId );

   }else{//UPDATE FOR TEXT MESSAGE LINK
      $db->query('UPDATE EmailQueue SET NotificationRead = ? WHERE ToEmail NOT LIKE "%textmagic.com%" AND ScheduleDate= ? AND NotificationRead IS NULL AND cohostLogId= ?' , $current_date,$scheduleDate, $cohostLogId );
   }
   header('Location: https://www.airbnb.com/hosting/thread/'.$threadNumber);
}
?>