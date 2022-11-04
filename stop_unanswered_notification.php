<?php
include 'db_connection.php';
?>
<!DOCTYPE html>
<html>

<head>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css"
        integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
    <link href="css/style.css" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>   
 <?php
  if(isset($_GET['id']) && isset($_GET['threadNo'])) {
        
    // UPDATE QUERY TO SET SENDUNANSWEREDREMINDER = "N" FOR SAME ID AND THREADNUMBER
    $updateSendUnanswered= $db->query('UPDATE CohostMessageLog SET Sendunansweredreminder =? WHERE Id=? AND ThreadNumber=?', "N", $_GET["id"], $_GET['threadNo']);
        ?>
<body>
        <section class="thank_you m-4">
        <div class="container">
            <div class="row justify-content-md-center align-items-center h-100">
                <div class="card_wrapper ">
                    <div class="brand text-center mb-4">
                        <a href="/"><img src="img/logo.png" alt="We Care" width="150px"></a>
                    </div>
                    <div class="card col-md-12 m-auto p-0">
                        <div class="card-header text-center">
                           
                        </div>
                        <div class="card-body text-center thankyou_card" style = "font-weight: bold;">
                        Reminder notifications stop successfully
                        </div>
                        <div class="card-footer text-center">
                      
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>


        
    <?php
    }
    ?>
    </body>
</html>