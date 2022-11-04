<?php
include 'db_connection.php';

if(isset($_POST) && !empty($_POST)){
   
    $filterCohostName="";
    if($_SESSION['user']['admin'] =="Y"){
        $filterCohostName="";
        if(isset($_POST['cohostname']) && !empty($_POST['cohostname'])){
            $filterCohostName .= " AND Cohostid = '".$_POST['cohostname']."' ";
        }
    } else{
        $filterCohostName .= " AND Cohostid = '".$_SESSION['user']['Id']."' ";
    }
   
    if(!empty($_POST['date_filter'])) {
        if($_POST['date_filter'] == 'today') {
            $assignments = $db->query("SELECT Id,Date,Time,ResponseTime,ResponseDate,ResponseSpeed,PaymentAmount FROM CohostMessageLog WHERE Date = CURDATE() AND ResponseDate IS NOT NULL $filterCohostName ORDER BY Date DESC, Time DESC")->fetchAll();
            $totalEarned = $db->query("SELECT sum(PaymentAmount) AS totalEarned FROM CohostMessageLog WHERE Date = CURDATE() AND ResponseDate IS NOT NULL $filterCohostName")->fetchAll();

            
        } else if($_POST['date_filter'] == 'last_30_days') {
            $assignments = $db->query("SELECT Id,Date,Time,ResponseTime,ResponseDate,ResponseSpeed,PaymentAmount FROM CohostMessageLog WHERE Date BETWEEN CURDATE() - INTERVAL 30 DAY AND CURDATE()  AND ResponseDate IS NOT NULL $filterCohostName ORDER BY Date DESC, Time DESC")->fetchAll();
            $totalEarned = $db->query("SELECT sum(PaymentAmount) AS totalEarned FROM CohostMessageLog WHERE Date BETWEEN CURDATE() - INTERVAL 30 DAY AND CURDATE()  AND ResponseDate IS NOT NULL $filterCohostName")->fetchAll();

            
        } else if($_POST['date_filter'] == 'yesterday') {
            $assignments = $db->query("SELECT Id,Date,Time,ResponseTime,ResponseDate,ResponseSpeed,PaymentAmount FROM CohostMessageLog WHERE Date = CURDATE() - INTERVAL 1 DAY  AND ResponseDate IS NOT NULL $filterCohostName ORDER BY Date DESC, Time DESC")->fetchAll();
            $totalEarned = $db->query("SELECT sum(PaymentAmount) AS totalEarned FROM CohostMessageLog WHERE Date = CURDATE() - INTERVAL 1 DAY  AND ResponseDate IS NOT NULL $filterCohostName")->fetchAll();

            
        }  else if($_POST['date_filter'] == 'this_week') {
            $assignments = $db->query("SELECT Id,Date,Time,ResponseTime,ResponseDate,ResponseSpeed,PaymentAmount FROM CohostMessageLog WHERE Date >= date(NOW()) - INTERVAL 7 DAY  AND ResponseDate IS NOT NULL $filterCohostName ORDER BY Date DESC, Time DESC")->fetchAll();
            $totalEarned = $db->query("SELECT sum(PaymentAmount) AS totalEarned FROM CohostMessageLog WHERE Date >= date(NOW()) - INTERVAL 7 DAY  AND ResponseDate IS NOT NULL $filterCohostName")->fetchAll();

            
        }
        else if($_POST['from_date'] != '' && $_POST['to_date'] != "") {
        $from_date = $_POST['from_date'];
        $to_date = $_POST['to_date'];
        $assignments = $db->query("SELECT Id,Date,Time,ResponseTime,ResponseDate,ResponseSpeed,PaymentAmount FROM CohostMessageLog WHERE Date >= ? AND Date <= ?  AND ResponseDate IS NOT NULL $filterCohostName ORDER BY Date DESC, Time DESC", $from_date, $to_date)->fetchAll();
        $totalEarned = $db->query("SELECT sum(PaymentAmount) AS totalEarned FROM CohostMessageLog WHERE Date >= ? AND Date <= ?  AND ResponseDate IS NOT NULL $filterCohostName", $from_date, $to_date)->fetchAll();

        // $dateRangeFields = "";
    }else {
        $filterCohostName="";
        if($_SESSION['user']['admin'] !="Y"){
            $filterCohostName = " AND Cohostid = '".$_SESSION['user']['Id']."' ";
        }
            $assignments = $db->query("SELECT Id,Date,Time,ResponseTime,ResponseDate,ResponseSpeed,PaymentAmount FROM CohostMessageLog WHERE ResponseDate IS NOT NULL $filterCohostName ORDER BY Date DESC, Time DESC")->fetchAll(); 
            $totalEarned = $db->query("SELECT sum(PaymentAmount) AS totalEarned FROM CohostMessageLog WHERE ResponseDate IS NOT NULL $filterCohostName")->fetchAll();   

        }
    }

              
                               
                
               
    echo json_encode(array("assignments" => $assignments,"totalEarned" => $totalEarned));
    exit;
   
   

}

?>