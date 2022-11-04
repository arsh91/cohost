<?php
	//ini_set('display_errors', 1);
	//ini_set('display_startup_errors', 1);
	//error_reporting(E_ALL);
	include 'db_connection.php';
	include 'inc/checkCookies.php';
	if(isset($_SESSION) && !empty($_SESSION['user'])) {
		header("Location: cohostmessageloglist.php"); 
		exit;
	}
	$errors = array();
	if(isset($_POST) && !empty($_POST)){
		$username = $password = '';
		if(isset($_POST['username']) && strlen($_POST['username']) >= 6){
			$username = $_POST['username'];
		} else {
			$errors['username'] = 'Username is invalid';
		}
		
		if(isset($_POST['password']) && strlen($_POST['password']) >= 8){
			$password = $_POST['password'];
		} else {
			$errors['password'] = 'Password is invalid';
		}
		if(empty($errors)) {
			$account = $db->query('SELECT * FROM cohost WHERE username = ? AND password = ?', $username,  $password)->fetchArray();
			if(!empty($account)) {
				$_SESSION['user'] = $account;
				$Teamuserid = $_SESSION['user']['TeamMemberID'];
				$userid= base64_encode($Teamuserid);
				
				//print_r($userid); die();
				
				//If test_cookie does not exist then set test_cookie for 30 days
				setcookie( 'vacationrentals_login_cookie', $userid, time() + ( 60 * 60 * 24 *30 ));
				header("Location: cohostmessageloglist.php"); 
				exit;
			} else { 
				$errors['auth'] = "Invalid Username or Password.";
			}
		}
	}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="author" content="Kodinger">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Login | Cohost Log</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css"
        integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
    <link rel="stylesheet" type="text/css" href="css/my-login.css">
</head>

<body class="my-login-page">
    <section class="h-100">
        <div class="container h-100">
            <div class="row justify-content-md-center h-100">
                <div class="card-wrapper">
                    <div class="brand">
                        <img src="img/logo.png" alt="Vacation Rental Management">
                    </div>
                    <span class="company_title">Cohost Log</span>
                    <div class="card fat">
                        <div class="card-body">
                            <h4 class="card-title">Login</h4>
                            <form method="POST" class="my-login-validation">
                                <?php 
									if(isset($errors['auth'])) { ?>
                                <div class="alert alert-danger" role="alert">
                                    <?php echo $errors['auth']; ?>
                                </div>
                                <?php } ?>
                                <div class="form-group">
                                    <label for="email">Username</label>
                                    <input id="username" type="text" class="form-control" name="username" value=""
                                        required autofocus minlength="6">
                                    <?php 
									if(isset($errors['username'])) { ?>
                                    <div class="invalid-feedback" style="display:block;">
                                        <?php echo $errors['username']; ?>
                                    </div>
                                    <?php } ?>
                                </div>

                                <div class="form-group">
                                    <label for="password">Password
                                        <!--
										<a href="forgot.php" class="float-right">
											Forgot Password?
										</a -->
                                    </label>
                                    <input id="password" type="password" class="form-control" name="password" required
                                        data-eye minlength="8">
                                    <?php 
									if(isset($errors['password'])) { ?>
                                    <div class="invalid-feedback" style="display:block;">
                                        <?php echo $errors['password']; ?>
                                    </div>
                                    <?php } ?>
                                </div>

                                <div class="form-group m-0">
                                    <button type="submit" class="btn btn-primary btn-block">
                                        Login
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <div class="footer">
                        Copyright &copy; 2021 &mdash; Cohost Log
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js"
        integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous">
    </script>
    <!--<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script> -->
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"
        integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous">
    </script>
    <script src="js/my-login.js"></script>
</body>

</html>