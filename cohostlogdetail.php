<?php
include 'db_connection.php';
include 'inc/auth.php';

    $cohostLog = $db->query("SELECT * FROM CohostMessageLog WHERE Id=?",$_POST['Id'])->fetchArray();

                                    // echo $cohostLog['Date']; echo "fsfsf";

    ?>


    <p><span class="titleStyle">Date: </span><?= date("m-d-Y", strtotime($cohostLog['Date']) ); ?> </p>
    <p><span class="titleStyle"> Time: </span><?= date("h:i A", strtotime($cohostLog['Time']) ); ?> </p>
    <p><span class="titleStyle mb-3"> Message: </span><?= $cohostLog['Message']; ?> </p>
    <hr>
    <p><span class="titleStyle"> Response Date: </span><?= date("m-d-Y", strtotime($cohostLog['ResponseDate']) ); ?> </p>
    <p><span class="titleStyle"> Response Time: </span><?= date("h:i A", strtotime($cohostLog['ResponseTime']) ); ?> </p>
    <p><span class="titleStyle"> Response Message: </span><?= $cohostLog['ResponseMessage']; ?> </p>
