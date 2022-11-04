<?php
include 'db_connection.php';
include 'inc/auth.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Cohost Message Log List | Cohost Log</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
    <link rel="stylesheet" type="text/css" href="css/my-login.css">
    <link rel="stylesheet" type="text/css" href="css/jquery-ui-datepicker.css">
    <link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/timepicker/1.3.5/jquery.timepicker.min.css">

    <script src="https://code.jquery.com/jquery-1.12.4.js"></script>




</head>
<style>
    .ui-timepicker-container.ui-timepicker-standard {
        z-index: 9999 !important;
    }
</style>

<?php
$dateRangeFields = "display:none;";
$from_date = $to_date = '';
if (isset($_POST) && !empty($_POST)) {
    if ($_SESSION['user']['admin'] == "Y") {
        $filterCohostName = "";
        if (isset($_POST['cohostname']) && !empty($_POST['cohostname'])) {
            $filterCohostName .= " AND Cohostid = '" . $_POST['cohostname'] . "' ";
        }
    } else {
        $filterCohostName .= " AND Cohostid = '" . $_SESSION['user']['Id'] . "' ";
    }
    if (!empty($_POST['date_filter'])) {
        if ($_POST['date_filter'] == 'custom_date') {
            $from_date = $_POST['from_date'];
            $to_date = $_POST['to_date'];
            $assignments = $db->query("SELECT Id,Date,Time,ResponseTime,ResponseDate,ResponseSpeed,PaymentAmount FROM CohostMessageLog WHERE Date >= ? AND Date <= ? AND ResponseDate IS NOT NULL $filterCohostName ORDER BY Date DESC, Time DESC", $from_date, $to_date)->fetchAll();
            $dateRangeFields = "";
            $totalEarned = $db->query("SELECT sum(PaymentAmount) AS totalEarned FROM CohostMessageLog WHERE Date >= ? AND Date <= ? AND ResponseDate IS NOT NULL $filterCohostName", $from_date, $to_date)->fetchAll();
        } else {
            $assignments = $db->query("SELECT Id,Date,Time,ResponseTime,ResponseDate,ResponseSpeed,PaymentAmount FROM CohostMessageLog WHERE ResponseDate IS NOT NULL $filterCohostName ORDER BY Date DESC, Time DESC")->fetchAll();
            $totalEarned = $db->query("SELECT sum(PaymentAmount) AS totalEarned FROM CohostMessageLog WHERE ResponseDate IS NOT NULL $filterCohostName")->fetchAll();
        }
    }
} else {
    $filterCohostName = "";
    if ($_SESSION['user']['admin'] != "Y") {
        $filterCohostName = " AND Cohostid = '" . $_SESSION['user']['Id'] . "' ";
    }

    $assignments = $db->query("SELECT Id,Date,Time,ResponseTime,ResponseDate,ResponseSpeed,PaymentAmount FROM CohostMessageLog WHERE Date >= date(NOW()) - INTERVAL 7 DAY  AND ResponseDate IS NOT NULL $filterCohostName ORDER BY Date DESC, Time DESC ")->fetchAll();
    $totalEarned = $db->query("SELECT sum(PaymentAmount) AS totalEarned FROM CohostMessageLog WHERE Date >= date(NOW()) - INTERVAL 7 DAY  AND ResponseDate IS NOT NULL $filterCohostName")->fetchAll();
}
?>

<body class="my-login-page">
    <section class="h-100">
        <div class="container h-100">
            <div class="row justify-content-md-center h-100">
                <div class="card-wrapper">
                    <div class="brand">
                        <a href="/"><img src="img/logo.png" alt="Vacation Rental Management"></a>
                    </div>
                    <span class="company_title"><?php echo $_SESSION['user']['Fname'] . ' ' . $_SESSION['user']['Lname']; ?>
                        | <a href="logout.php"> Logout</a></span>
                    <form method="post" action="" name="search_filter">
                        <input type="hidden" class="admin" value="<?php echo $admin; ?>">
                        <div class="form-group">
                            <select name="date_filter" class="form-control select_filter" id="date_filter">
                                <?php
                                if ($_POST['date_filter'] == 'custom_date') { ?>
                                    <option value="all">Date Range</option>
                                    <option value="today">Today</option>
                                    <option value="yesterday">Yesterday</option>
                                    <option value="this_week">This Week</option>
                                    <option value="last_30_days">Last 30 Days</option>
                                    <option value="custom_date" selected>Custom Date Range</option>
                                <?php } else { ?>
                                    <option value="all" selected="selected">Date Range</option>
                                    <option value="today">Today</option>
                                    <option value="yesterday">Yesterday</option>
                                    <option value="this_week" selected>This Week</option>
                                    <option value="last_30_days">Last 30 Days</option>
                                    <option value="custom_date">Custom Date Range</option>
                                <?php } ?>
                            </select>
                        </div>
                        <?php
                        if ($_SESSION['user']['admin'] == "Y") {
                            $teamMembersNames = $db->query("SELECT * FROM cohost")->fetchAll();
                        ?>
                            <div class="form-group">
                                <select name="assignedto" class="form-control select_filter" id="cohostname">
                                    <option value="">Cohost Name</option>
                                    <?php
                                    foreach ($teamMembersNames as $teamMembersName) {
                                    ?>
                                        <option value="<?php echo $teamMembersName['Id']; ?>">
                                            <?php echo $teamMembersName['Fname'] . " " . $teamMembersName['Lname']; ?></option>
                                    <?php } ?>

                                </select>
                            </div>
                        <?php } ?>

                        <div class="date-range-form form-group custom_field" style="<?php echo $dateRangeFields; ?>">
                            <div class="form-group">
                                <div class="row">
                                    <div class="col">
                                        <input type="text" class="form-control" placeholder="From Date" id="from_date" name="from_date" value="<?= $from_date ?>">
                                    </div>
                                    <div class="col">
                                        <input type="text" class="form-control" placeholder="To Date" id="to_date" name="to_date" value="<?= $to_date ?>">
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="row">
                                    <div class="col text-center">
                                        <button type="submit" id="search_date_range" class="btn btn-primary">Search</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                    <div class="modal fade" id="cohostLogDetailModal" tabindex="-1" role="dialog" aria-labelledby="cohostLogDetailModal" aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <div class="col-md-5">
                                        <h5 class="modal-title" id="submitModalLabel">Cohost Details</h5>
                                    </div>

                                    <div class="col-md-2">
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>

                                </div>
                                <div class="modal-body cohostLogDetailBody">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3 ml-1 totalEarned" style="font-size:16px;">
                        <div>
                            <strong>
                                Total Earned: <?php
                                                foreach ($totalEarned as $earned) {

                                                    if ($earned['totalEarned'] != "") {
                                                        echo "$" . $earned['totalEarned'];
                                                    } else {
                                                        echo "  $0";
                                                    }
                                                }

                                                ?>
                            </strong>
                        </div>
                    </div>
                    <table class="table table-bordered table-striped" id="maintainence_tickets">
                        <thead>
                            <tr>
                                <th scope="col">Message<br> Date/Time</th>
                                <th scope="col">Response<br> Date/Time</th>
                                <th scope="col">Response<br> Speed</th>
                                <th scope="col">Earned</th>
                            </tr>
                        </thead>
                        <tbody class="maintainence_body">
                            <?php
                            if (count($assignments) > 0) {
                                foreach ($assignments as $val) {
                            ?>
                                    <tr class="row_data" id=<?php echo $val['Id']; ?>>
                                        <td class="openCohostDetailModal"><?php echo date("m-d-Y", strtotime($val['Date'])) . " " . date("h:i A", strtotime($val['Time'])); ?></td>
                                        <td class="openCohostDetailModal"><?php echo date("m-d-Y", strtotime($val['ResponseDate'])) . " " . date("h:i A", strtotime($val['ResponseTime'])); ?></td>
                                        <td class="openCohostDetailModal"><?php echo $val['ResponseSpeed']; ?></td>
                                        <td class="openCohostDetailModal"><?php echo "$" . $val['PaymentAmount']; ?></td>
                                    </tr>
                                <?php }
                            } else {
                                ?>
                                <tr>
                                    <td colspan="6">No data found.</td>
                                </tr>
                            <?php
                            }
                            ?>
                        </tbody>
                    </table>
                    <div class="footer">
                        Copyright &copy; 2021 &mdash; Cohost Log
                    </div>
                </div>
            </div>
        </div>
    </section>


    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous">
    </script>
    <script src="js/jquery-ui-datepicker.js"></script>
    <script src="//cdnjs.cloudflare.com/ajax/libs/timepicker/1.3.5/jquery.timepicker.min.js"></script>
    <script src="js/moment.js"></script>


    <script type="text/javascript">
        $(document).ready(function() {

            $('#date_filter').change(function() {
                $('.custom_field').hide();
                $('input#from_date').removeAttr('required');
                $('input#to_date').removeAttr('required');
                if ($(this).val() == 'custom_date') {
                    $('.custom_field').show();
                    $('input#from_date').attr('required', 'required');
                    $('input#to_date').attr('required', 'required');
                } else {
                    // $('form[name=search_filter]').submit();
                }
            });
            $('#search_date_range').click(function() {
                $('form[name=search_filter]').submit();
            });
            var dateFormat = "yy-mm-dd",
                from = $("#from_date").datepicker({
                    defaultDate: "+1w",
                    changeMonth: true,
                    numberOfMonths: 1,
                    dateFormat: dateFormat
                }).on("change", function() {
                    to.datepicker("option", "minDate", getDate(this));
                }),
                to = $("#to_date").datepicker({
                    defaultDate: "+1w",
                    changeMonth: true,
                    numberOfMonths: 1,
                    dateFormat: dateFormat
                }).on("change", function() {
                    from.datepicker("option", "maxDate", getDate(this));
                });

            function getDate(element) {
                var date;
                try {
                    date = $.datepicker.parseDate(dateFormat, element.value);
                } catch (error) {
                    date = null;
                }
                return date;
            }


            function openCohostDetailModal() {
                var Id = $(this).closest('tr').attr('id');
                // alert(Id);

                $.ajax({
                    url: 'cohostlogdetail.php',
                    method: "POST",
                    data: {
                        "Id": Id
                    },
                    success: function(data) {
                        // console.log(data);
                        $('#cohostLogDetailModal .modal-body').html(data);
                        $('#cohostLogDetailModal').modal('show');
                    }
                });
            };
            $('.openCohostDetailModal').unbind('click').bind('click', openCohostDetailModal);


            $('.select_filter').change(function() {
                var date_filter = $('#date_filter').val();
                var cohostname = $('#cohostname').val();
                var fromDate = $('#from_date').val();
                var toDate = $('#to_date').val();
                var sendAjax = true;
                if (date_filter == 'custom_date' && (fromDate == '' || toDate == '')) {
                    sendAjax = false;
                }
                if (sendAjax) {
                    $.ajax({
                        type: "POST",
                        url: "cohostfilter.php",
                        data: {
                            "date_filter": date_filter,
                            "cohostname": cohostname,
                            "from_date": fromDate,
                            "to_date": toDate
                        },
                        success: function(response) {

                            var response = JSON.parse(response);
                            var totalEarn = response.totalEarned;
                            var Total_Earned = '';
                            $.each(response.totalEarned, function(index, val) {

                                if (val.totalEarned != null) {
                                    Total_Earned += '<strong>Total Earned: $' + val.totalEarned + ' </strong>';
                                } else {
                                    Total_Earned += '<strong>Total Earned: $0 </strong>';
                                }
                            });
                            $(".totalEarned").html(Total_Earned);

                            var assignment = response.assignments;
                            var event_data = '';
                            if (assignment.length > 0) {
                                $.each(response.assignments, function(index, value) {
                                    // console.log(value);
                                    var Date = moment(value.Date, "YYYY-MM-DD").format("MM-DD-YYYY");
                                    var Time = moment(value.Time, "HH:mm:ss").format("hh:mm A");
                                    var ResponseDate = moment(value.ResponseDate, "YYYY-MM-DD").format("MM-DD-YYYY");
                                    var ResponseTime = moment(value.ResponseTime, "HH:mm:ss").format("hh:mm A");

                                    event_data += '<tr class="row_data" Id="' + value.Id + '">';
                                    event_data += '<td class="openCohostDetailModal">' + Date + " " + Time + '</td><td class="openCohostDetailModal">' + ResponseDate + " " + ResponseTime + '</td><td class="openCohostDetailModal">' + value.ResponseSpeed + '</td>';
                                    event_data += '<td class="openCohostDetailModal">$' + value.PaymentAmount + '</td>';
                                    event_data += '</tr>';

                                });
                            } else {
                                event_data += '<tr><td colspan="6"> No data found.</td></tr>';
                            }
                            $("#maintainence_tickets .maintainence_body").html(event_data);

                            $('.openCohostDetailModal').unbind('click').bind('click', openCohostDetailModal);




                        }

                    });
                }
            });


        });
    </script>
</body>

</html>