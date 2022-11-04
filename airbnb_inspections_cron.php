<?php
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);
include 'db_connection.php';

// SELECT DATA TO CREATE INSPEXTION FROM AIRBNBBOOKINGS TABLE
 $AirbnbBookingsData =$db->query('SELECT * FROM AirbnbBookings WHERE CheckOutDate BETWEEN CURDATE() + INTERVAL 2 DAY AND CURDATE() + INTERVAL 14 DAY AND PropertyId IS NOT NULL')->fetchAll();
if(count($AirbnbBookingsData) > 0) {
    foreach($AirbnbBookingsData as $data){
        // SELECT TO CHECK THE INSPECTION IS ALREADY CREATED OTR NOT
        $inspectionExist= $db->query('SELECT * FROM Inspections WHERE PropertyID=? AND InspectionDate=?', $data['PropertyId'], $data['CheckOutDate'])->fetchAll();
        if(empty($inspectionExist)){
            $currentDate=date('Y-m-d H:i:s');

            //INSERT DATA INTO INPECTION TABLE
            $createInspection = $db->query('INSERT into Inspections ( PropertyID, DateTimeCreated, InspectionDate, UserID ) VALUES (?, ?, ?, ?)', $data['PropertyId'], $currentDate, $data['CheckOutDate'], 0);

            $InspectionID = $db->lastInsertID();

            //INSERT DATA IN InspectionDetails TABLE FROM inspectionitems TABLE 
            $InspectionitemsData =$db->query("SELECT * FROM inspectionitems WHERE PropertyID = ?",$data['PropertyId'] )->fetchAll();
            foreach($InspectionitemsData as $val){
                $SetInspectionDetail = $db->query("INSERT INTO InspectionDetails (InspectionID, ItemID,SortID, LocationDescription, ItemDescription, PhotoRequired) VALUES (?, ?, ?, ?, ?, ?)", $InspectionID, $val['ItemID'], $val['SortID'], $val['LocationDescription'], $val['ItemDescription'], $val['PhotoRequired']);
            }

            // INSERT DATA INTO COHOSTTASKLIST TABLE
            // $taskDetail='Departure Inspection for '.$data['PropertyName'];
            // $createInspection = $db->query('INSERT into CohostTaskList ( DateDue, Category, TaskDetails ) VALUES (?, ?, ?)', $data['CheckOutDate'], 'INSPECTION', $taskDetail);
        }

    }
}
?>