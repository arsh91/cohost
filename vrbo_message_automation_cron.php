<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include 'db_connection.php';

// MAIL DATA TO SEND 
$fromEmail = 'noreply@equisourceholdings.com';
$currentdatetime=date('Y-m-d H:i:s');
$type = "vrbobooking_notification";
$subject = 'VRBO message';

// THIS IS TO UPDATE "AirbnbBookings.VRBO_NEWBOOKINGsent" FIELD AND SEND MAIL
 $AirbnbBookingsData1 =$db->query('SELECT AirbnbBookings.id, AirbnbBookings.Guest, AirbnbBookings.CheckInDate, AirbnbBookings.CheckOutDate, AirbnbBookings.VRBOresponseemail , AirbnbBookings.PropertyId, VRBOscheduledmessagestemplates.MessageBody FROM AirbnbBookings LEFT JOIN VRBOscheduledmessagestemplates ON AirbnbBookings.PropertyId =  VRBOscheduledmessagestemplates.PropertyID WHERE AirbnbBookings.BookingDate >= CURDATE() - INTERVAL 2 DAY AND AirbnbBookings.Source = "VRBO" AND AirbnbBookings.VRBO_NEWBOOKINGsent IS NULL AND VRBOscheduledmessagestemplates.MessageType = "NEWBOOKING"')->fetchAll();
 
if(count($AirbnbBookingsData1) > 0) {
    foreach($AirbnbBookingsData1 as $val){
        $toEmail = $val['VRBOresponseemail'];
        $replace = ["[name]", "[checkindate]", "[checkoutdate]"];
        $replacewith   = [$val['Guest'], $val['CheckInDate'], $val['CheckOutDate'] ];
        $bodyText =  str_replace($replace, $replacewith, $val['MessageBody']);
        $currentdatetime=date('Y-m-d H:i:s');
        $type = "vrbobooking_notification";
        $subject = 'VRBO message';

        if($toEmail !== NULL && !empty($bodyText)  ){
           $db->query('INSERT into EmailQueue (FromEmail, Subject, BodyText, ToEmail, Status, ScheduleDate, Type) VALUES (?, ?, ?, ?, ?, ?, ?)', $fromEmail, $subject, $bodyText, $toEmail, 'Pending', $currentdatetime, $type);

           $VRBO_NEWBOOKINGsent= $db->query('UPDATE AirbnbBookings SET VRBO_NEWBOOKINGsent =?  WHERE id = ? AND VRBO_NEWBOOKINGsent IS NULL', $currentdatetime,  $val['id']);

        }

    }

}

// THIS IS TO UPDATE "AirbnbBookings.VRBO_PRECHECKINsent" FIELD AND SEND MAIL
$AirbnbBookingsData2 =$db->query('SELECT AirbnbBookings.id,AirbnbBookings.Guest, AirbnbBookings.VRBOresponseemail, AirbnbBookings.PropertyId, VRBOscheduledmessagestemplates.MessageBody FROM AirbnbBookings LEFT JOIN VRBOscheduledmessagestemplates ON AirbnbBookings.PropertyId =  VRBOscheduledmessagestemplates.PropertyID WHERE CURDATE() >= AirbnbBookings.CheckInDate - INTERVAL 2 DAY AND AirbnbBookings.Source = "VRBO" AND AirbnbBookings.VRBO_PRECHECKINsent IS NULL AND VRBOscheduledmessagestemplates.MessageType = "PRECHECKIN" AND CURRENT_TIME() > "09:55:00"' )->fetchAll();
 
if(count($AirbnbBookingsData2) > 0) {
    foreach($AirbnbBookingsData2 as $val){
        $toEmail = $val['VRBOresponseemail'];
        $bodyText =  str_replace("[name]",$val['Guest'],$val['MessageBody']);
        if($toEmail !== NULL && !empty($bodyText)  ){

           $db->query('INSERT into EmailQueue (FromEmail, Subject, BodyText, ToEmail, Status, ScheduleDate, Type) VALUES (?, ?, ?, ?, ?, ?, ?)', $fromEmail, $subject, $bodyText, $toEmail, 'Pending', $currentdatetime, $type);

           $VRBO_PRECHECKINsent= $db->query('UPDATE AirbnbBookings SET VRBO_PRECHECKINsent =?  WHERE id = ? AND VRBO_PRECHECKINsent IS NULL', $currentdatetime,  $val['id']);

        }

    }

}

// THIS IS TO UPDATE "AirbnbBookings.VRBO_HOWTOsent" FIELD AND SEND MAIL
$AirbnbBookingsData3 =$db->query('SELECT AirbnbBookings.id, AirbnbBookings.Guest, AirbnbBookings.VRBOresponseemail, AirbnbBookings.PropertyId, VRBOscheduledmessagestemplates.MessageBody FROM AirbnbBookings LEFT JOIN VRBOscheduledmessagestemplates ON AirbnbBookings.PropertyId =  VRBOscheduledmessagestemplates.PropertyID WHERE CURDATE() = AirbnbBookings.CheckInDate AND AirbnbBookings.Source = "VRBO" AND AirbnbBookings.VRBO_HOWTOsent IS NULL AND VRBOscheduledmessagestemplates.MessageType = "HOWTO" AND CURRENT_TIME() >= "11:55:00"' )->fetchAll();

if(count($AirbnbBookingsData3) > 0) {
    foreach($AirbnbBookingsData3 as $val){
        $toEmail = $val['VRBOresponseemail'];
        $bodyText =  str_replace("[name]",$val['Guest'],$val['MessageBody']);
        if($toEmail !== NULL && !empty($bodyText)  ){

        $db->query('INSERT into EmailQueue (FromEmail, Subject, BodyText, ToEmail, Status, ScheduleDate, Type) VALUES (?, ?, ?, ?, ?, ?, ?)', $fromEmail, $subject, $bodyText, $toEmail, 'Pending', $currentdatetime, $type);

        $VRBO_HOWTOsent= $db->query('UPDATE AirbnbBookings SET VRBO_HOWTOsent =?  WHERE id = ? AND VRBO_HOWTOsent IS NULL', $currentdatetime,  $val['id']);

        }

    }

}

// THIS IS TO UPDATE "AirbnbBookings.VRBO_DOORCODEsent" FIELD AND SEND MAIL
$AirbnbBookingsData4 =$db->query('SELECT AirbnbBookings.id, AirbnbBookings.Guest, AirbnbBookings.VRBOresponseemail, AirbnbBookings.PropertyId, VRBOscheduledmessagestemplates.MessageBody FROM AirbnbBookings LEFT JOIN VRBOscheduledmessagestemplates ON AirbnbBookings.PropertyId =  VRBOscheduledmessagestemplates.PropertyID WHERE CURDATE() = AirbnbBookings.CheckInDate AND AirbnbBookings.Source = "VRBO" AND AirbnbBookings.VRBO_DOORCODEsent IS NULL AND VRBOscheduledmessagestemplates.MessageType = "DOORCODE" AND CURRENT_TIME() >= "03:50:00"' )->fetchAll();

if(count($AirbnbBookingsData4) > 0) {
    foreach($AirbnbBookingsData4 as $val){
        $toEmail = $val['VRBOresponseemail'];
        $bodyText = $val['MessageBody'];
        if($toEmail !== NULL && !empty($bodyText)  ){

           $db->query('INSERT into EmailQueue (FromEmail, Subject, BodyText, ToEmail, Status, ScheduleDate, Type) VALUES (?, ?, ?, ?, ?, ?, ?)', $fromEmail, $subject, $bodyText, $toEmail, 'Pending', $currentdatetime, $type);

           $VRBO_DOORCODEsent= $db->query('UPDATE AirbnbBookings SET VRBO_DOORCODEsent =?  WHERE id = ? AND VRBO_DOORCODEsent IS NULL', $currentdatetime,  $val['id']);

        }

    }

}

// THIS IS TO UPDATE "AirbnbBookings.VRBO_CHECKOUTsent" FIELD AND SEND MAIL
$AirbnbBookingsData5 =$db->query('SELECT AirbnbBookings.id, AirbnbBookings.Guest, AirbnbBookings.VRBOresponseemail, AirbnbBookings.PropertyId, VRBOscheduledmessagestemplates.MessageBody FROM AirbnbBookings LEFT JOIN VRBOscheduledmessagestemplates ON AirbnbBookings.PropertyId =  VRBOscheduledmessagestemplates.PropertyID WHERE CURDATE() + INTERVAL 1 DAY  >= AirbnbBookings.CheckOutDate AND AirbnbBookings.Source = "VRBO" AND AirbnbBookings.VRBO_CHECKOUTsent IS NULL AND VRBOscheduledmessagestemplates.MessageType = "CHECKOUT" AND CURRENT_TIME() >= "10:55:00"' )->fetchAll();

if(count($AirbnbBookingsData5) > 0) {
    foreach($AirbnbBookingsData5 as $val){
        $toEmail = $val['VRBOresponseemail'];
        $bodyText =  str_replace("[name]",$val['Guest'],$val['MessageBody']);
       
        if($toEmail !== NULL && !empty($bodyText)  ){

           $db->query('INSERT into EmailQueue (FromEmail, Subject, BodyText, ToEmail, Status, ScheduleDate, Type) VALUES (?, ?, ?, ?, ?, ?, ?)', $fromEmail, $subject, $bodyText, $toEmail, 'Pending', $currentdatetime, $type);

           $VRBO_CHECKOUTsent= $db->query('UPDATE AirbnbBookings SET VRBO_CHECKOUTsent =?  WHERE id = ? AND VRBO_CHECKOUTsent IS NULL', $currentdatetime,  $val['id']);

        }

    }

}

// THIS IS TO UPDATE "AirbnbBookings.VRBO_LOSTANDFOUNDsent" FIELD AND SEND MAIL
$AirbnbBookingsData6 =$db->query('SELECT AirbnbBookings.id, AirbnbBookings.Guest, AirbnbBookings.VRBOresponseemail, AirbnbBookings.PropertyId, VRBOscheduledmessagestemplates.MessageBody FROM AirbnbBookings LEFT JOIN VRBOscheduledmessagestemplates ON AirbnbBookings.PropertyId =  VRBOscheduledmessagestemplates.PropertyID WHERE CURDATE() = AirbnbBookings.CheckOutDate AND AirbnbBookings.Source = "VRBO" AND AirbnbBookings.VRBO_LOSTANDFOUNDsent IS NULL AND VRBOscheduledmessagestemplates.MessageType = "LOSTANDFOUND" AND CURRENT_TIME() >= "06:00:00"' )->fetchAll();


if(count($AirbnbBookingsData6) > 0) {
    foreach($AirbnbBookingsData6 as $val){
        $toEmail = $val['VRBOresponseemail'];
        $bodyText =  str_replace("[name]",$val['Guest'],$val['MessageBody']);
        if($toEmail !== NULL && !empty($bodyText)  ){

           $db->query('INSERT into EmailQueue (FromEmail, Subject, BodyText, ToEmail, Status, ScheduleDate, Type) VALUES (?, ?, ?, ?, ?, ?, ?)', $fromEmail, $subject, $bodyText, $toEmail, 'Pending', $currentdatetime, $type);

           $VRBO_LOSTANDFOUNDsent= $db->query('UPDATE AirbnbBookings SET VRBO_LOSTANDFOUNDsent =?  WHERE id = ? AND VRBO_LOSTANDFOUNDsent IS NULL', $currentdatetime,  $val['id']);

        }

    }

}