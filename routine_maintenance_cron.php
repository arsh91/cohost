<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include 'db_connection.php';

// SELECT DATA TO CREATE INSPECTION FROM ROUTINE MAINTENANCE TABLE
 $routineMaintenance =$db->query('SELECT * FROM RoutineMaintenanceItems WHERE LastDateCompleted < CURDATE() - INTERVAL TimeInterval DAY')->fetchAll();

if(count($routineMaintenance) > 0) {
    foreach($routineMaintenance as $data){

        $inspectId= $db->query('SELECT InspectionID FROM Inspections WHERE PropertyID =? AND InspectionDate > CURDATE() ORDER BY InspectionDate ASC Limit 0,1',$data['PropertyID'])->fetchAll();

        if(!empty($inspectId)){

            $inspectDetailExist= $db->query('SELECT ID FROM InspectionDetails WHERE InspectionID =? AND LocationDescription =?', $inspectId[0]['InspectionID'], 'Routine Maintenance')->fetchAll();

            if(empty($inspectDetailExist)){

            $SetInspectionDetail = $db->query("INSERT INTO InspectionDetails (InspectionID, ItemID,SortID, LocationDescription, ItemDescription, PhotoRequired, MaterialsRequired, ToolsRequired) VALUES (?, ?, ?, ?, ?, ?, ?, ?)", $inspectId[0]['InspectionID'], $data['ItemID'], $data['SortID'], 'Routine Maintenance', $data['ItemDescription'], $data['PhotoRequired'], $data['MaterialsRequired'], $data['ToolsRequired']);

            }
        }

        // $inspectDetailExist= $db->query('SELECT ID FROM InspectionDetails WHERE InspectionID =? AND ItemID =? AND SortID =? AND LocationDescription =? AND ItemDescription =? AND PhotoRequired =? AND MaterialsRequired =? AND ToolsRequired =?', $inspectionId, $data['ItemID'], $data['SortID'], 'Routine Maintenance', $data['ItemDescription'], $data['PhotoRequired'], $data['MaterialsRequired'], $data['ToolsRequired'])->fetchAll();

    }

}
echo "Routine Maintenance Cron Executed.";
exit;
?>
