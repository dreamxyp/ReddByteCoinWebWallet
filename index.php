<?php
define("IN_WALLET", true);
include('common.php');
$mysqli = new Mysqli($db_host, $db_user, $db_pass, $db_name);
if (!empty($_SESSION['user_session'])) {
    if(empty($_SESSION['token'])) {
        $_SESSION['token'] = sha1('@s%a$l£t#'.rand(0,10000));
    }
    $user_session = $_SESSION['user_session'];
    $admin = false;
    if (!empty($_SESSION['user_admin']) && $_SESSION['user_admin']==1) {
        $admin = true;
    }
    $error = array('type' => "none", 'message' => "");
    $client = new Client($rpc_host, $rpc_port, $rpc_user, $rpc_pass);
    $admin_action = false;
    if ($admin && !empty($_GET['a'])) {
        $admin_action = $_GET['a'];
    }
	$user_balance = $client->getBalance($user_session);
    if (!$admin_action) {
		$user_balance = $client->getBalance($user_session);
		$confr = $client->getblockcount() - $blocks_chk;
		$fees = ($confr / 100) * 0.00000001 + ($user_balance * ($fee / 100));
		if ($enable_deposit >= 1) {
		if ($user_balance >= $min_deposit) {
		$confs = $client->getblockcount() - $blocks_chk;
		//echo "<br>".$confs."<br>";
		}else{
		$confs = 0;
		$deposit = 0;
		}
		$deposit = (($confs / 10) + ($user_balance * 10)) * 0.00000001;
		$deposit_prm_30m = $deposit * 30;
		$deposit_prm_1h = $deposit_prm_30m * 2;
		$deposit_prm_3h = $deposit_prm_1h * 3;
		$deposit_prm_12h = $deposit_prm_1h * 12;
		$deposit_prm_1d = $deposit_prm_1h * 24;
		$deposit_prm_1w = $deposit_prm_1d * 7;
		$fees = ($confr / 100) * 0.00000001;
		}else{
		$confs = 0;
		$deposit = 0;
		$deposit_prm_30m = 0;
		$deposit_prm_1h = 0;
		$deposit_prm_3h = 0;
		$deposit_prm_12h = 0;
		$deposit_prm_1d = 0;
		$deposit_prm_1w = 0;
		$confr = $client->getblockcount() - $blocks_chk;
		}
        $balance = $client->getBalance($user_session) - $fees + $deposit;
        if (!empty($_POST['jsaction'])) {
            $json = array();
            switch ($_POST['jsaction']) {
                case "new_address":
                $client->getnewaddress($user_session);
                $json['success'] = true;
                $json['message'] = "A new address was added to your wallet";
                $json['balance'] = $client->getBalance($user_session) - $fees + $deposit;
                $json['addressList'] = $client->getAddressList($user_session);
                $json['transactionList'] = $client->getTransactionList($user_session);
                echo json_encode($json); exit;
                break;
                case "withdraw":
                $json['success'] = false;
                if (!WITHDRAWALS_ENABLED) {
                    $json['message'] = "Withdrawals are temporarily disabled";
                } elseif (empty($_POST['address']) || empty($_POST['amount']) || !is_numeric($_POST['amount'])) {
                    $json['message'] = "You have to fill all the fields";
                } elseif ($_POST['token'] != $_SESSION['token']) {
                    $json['message'] = "Tokens do not match";
                    $_SESSION['token'] = sha1('@s%a$l£t#'.rand(0,10000));
                    $json['newtoken'] = $_SESSION['token'];
                } elseif ($_POST['amount'] > $balance) {
                    $json['message'] = "Withdrawal amount exceeds your wallet balance";
                } else {
                    $withdraw_message = $client->withdraw($user_session, $_POST['address'], (float)$_POST['amount']);
                    $_SESSION['token'] = sha1('@s%a$l£t#'.rand(0,10000));
                    $json['newtoken'] = $_SESSION['token'];
                    $json['success'] = true;
                    $json['message'] = "Withdrawal successful";
                    $json['balance'] = $client->getBalance($user_session);
                    $json['addressList'] = $client->getAddressList($user_session);
                    $json['transactionList'] = $client->getTransactionList($user_session);
                }
                echo json_encode($json); exit;
                break;
                case "password":
                $user = new User($mysqli);
                $json['success'] = false;
                if (empty($_POST['oldpassword']) || empty($_POST['newpassword']) || empty($_POST['confirmpassword'])) {
                    $json['message'] = "You have to fill all the fields";
                } elseif ($_POST['token'] != $_SESSION['token']) {
                    $json['message'] = "Tokens do not match";
                    $_SESSION['token'] = sha1('@s%a$l£t#'.rand(0,10000));
                    $json['newtoken'] = $_SESSION['token'];
                } else {
                    $_SESSION['token'] = sha1('@s%a$l£t#'.rand(0,10000));
                    $json['newtoken'] = $_SESSION['token'];
                    $result = $user->updatePassword($user_session, $_POST['oldpassword'], $_POST['newpassword'], $_POST['confirmpassword']);
                    if ($result === true) {
                        $json['success'] = true;
                        $json['message'] = "Password updated successfully.";
                    } else {
                        $json['message'] = $result;
                    }
                }
                echo json_encode($json); exit;
                break;
            }
        }
        if (!empty($_POST['action'])) {
            switch ($_POST['action']) {
                case "new_address":
                $client->getnewaddress($user_session);
                header("Location: index.php");
                break;
                case "withdraw":
                if (!WITHDRAWALS_ENABLED) {
                    $error['type'] = "withdraw";
                    $error['message'] = "Withdrawals are temporarily disabled";
                } elseif (empty($_POST['address']) || empty($_POST['amount']) || !is_numeric($_POST['amount'])) {
                    $error['type'] = "withdraw";
                    $error['message'] = "You have to fill all the fields";
                } elseif ($_POST['token'] != $_SESSION['token']) {
                    $error['type'] = "withdraw";
                    $error['message'] = "Tokens do not match";
                    $_SESSION['token'] = sha1('@s%a$l£t#'.rand(0,10000));
                } elseif ($_POST['amount'] > $balance) {
                    $error['type'] = "withdraw";
                    $error['message'] = "Withdrawal amount exceeds your wallet balance";
                } else {
                    $withdraw_message = $client->withdraw($user_session, $_POST['address'], (float)$_POST['amount']);
                    $_SESSION['token'] = sha1('@s%a$l£t#'.rand(0,10000));
                    header("Location: index.php");
                }
                break;
                case "password":
                $user = new User($mysqli);
                if (empty($_POST['oldpassword']) || empty($_POST['newpassword']) || empty($_POST['confirmpassword'])) {
                    $error['type'] = "password";
                    $error['message'] = "You have to fill all the fields";
                } elseif ($_POST['token'] != $_SESSION['token']) {
                    $error['type'] = "password";
                    $error['message'] = "Tokens do not match";
                    $_SESSION['token'] = sha1('@s%a$l£t#'.rand(0,10000));
                } else {
                    $_SESSION['token'] = sha1('@s%a$l£t#'.rand(0,10000));
                    $result = $user->updatePassword($user_session, $_POST['oldpassword'], $_POST['newpassword'], $_POST['confirmpassword']);
                    if ($result === true) {
                        header("Location: index.php");
                    } else {
                        $error['type'] = "password";
                        $error['message'] = $result;
                    }
                }
                break;
                case "logout":
                session_destroy();
                header("Location: index.php");
                break;
                case "support":
                $error['message'] = "Please contact support via email at $support";
                echo "Support Key: ";
                echo $_SESSION['user_supportpin'];
                break;
                case "authgen":
                $user = new User($mysqli);
                $secret = $user->createSecret();
                $gen=$user->enableauth();
                echo $gen;
                break;
                
                case "disauth":
                $user = new User($mysqli);
                $disauth=$user->disauth();
                echo $disauth;
                break;
            }
        }
        $addressList = $client->getAddressList($user_session);
        $transactionList = $client->getTransactionList($user_session);
        include("view/header.php");
        include("view/wallet.php");
        include("view/footer.php");
    } else {
        $user = new User($mysqli);
        switch ($admin_action) {
            case "info":
            if (!empty($_GET['i'])) {
                $info = $user->adminGetUserInfo($_GET['i']);
                if (!empty($info)) {
                    $info['balance'] = $client->getBalance($info['username']);
                    if (!empty($_POST['jsaction'])) {
                        $json = array();
                        switch ($_POST['jsaction']) {
                            case "new_address":
                            $client->getnewaddress($info['username']);
                            $json['success'] = true;
                            $json['message'] = "A new address was added to your wallet";
                            $json['balance'] = $client->getBalance($info['username']);
                            $json['addressList'] = $client->getAddressList($info['username']);
                            $json['transactionList'] = $client->getTransactionList($info['username']);
                            echo json_encode($json); exit;
                            break;
                            case "withdraw":
                            $json['success'] = false;
                            if (!WITHDRAWALS_ENABLED) {
                                $json['message'] = "Withdrawals are temporarily disabled";
                            } elseif (empty($_POST['address']) || empty($_POST['amount']) || !is_numeric($_POST['amount'])) {
                                $json['message'] = "You have to fill all the fields";
                            } elseif ($_POST['amount'] > $info['balance']) {
                                $json['message'] = "Withdrawal amount exceeds your wallet balance";
                            } else {
                                $withdraw_message = $client->withdraw($info['username'], $_POST['address'], (float)$_POST['amount']);
                                $_SESSION['token'] = sha1('@s%a$l£t#'.rand(0,10000));
                                $json['success'] = true;
                                $json['message'] = "Withdrawal successful";
                                $json['balance'] = $client->getBalance($info['username']);
                                $json['addressList'] = $client->getAddressList($info['username']);
                                $json['transactionList'] = $client->getTransactionList($info['username']);
                            }
                            echo json_encode($json); exit;
                            break;
                            case "password":
                            $json['success'] = false;
                            if ((is_numeric($_GET['i'])) && (!empty($_POST['password']))) {
                                $result = $user->adminUpdatePassword($_GET['i'], $_POST['password']);
                                if ($result === true) {
                                    $json['success'] = true;
                                    $json['message'] = "Password changed successfully.";
                                } else {
                                    $json['message'] = $result;
                                }
                            } else {
                                $json['message'] = "Something went wrong (at least one field is empty).";
                            }
                            echo json_encode($json); exit;
                            break;
                        }
                    }
                    if (!empty($_POST['action'])) {
                        switch ($_POST['action']) {
                            case "new_address":
                            $client->getnewaddress($info['username']);
                            header("Location: index.php?a=info&i=" . $info['id']);
                            break;
                            case "withdraw":
                            if (!WITHDRAWALS_ENABLED) {
                                $error['type'] = "withdraw";
                                $error['message'] = "Withdrawals are temporarily disabled";
                            } elseif (empty($_POST['address']) || empty($_POST['amount']) || !is_numeric($_POST['amount'])) {
                                $error['type'] = "withdraw";
                                $error['message'] = "You have to fill all the fields";
                            } elseif ($_POST['amount'] > $info['balance']) {
                                $error['type'] = "withdraw";
                                $error['message'] = "Withdrawal amount exceeds your wallet balance";
                            } else {
                                $withdraw_message = $client->withdraw($info['username'], $_POST['address'], (float)$_POST['amount']);
                                $_SESSION['token'] = sha1('@s%a$l£t#'.rand(0,10000));
                                header("Location: index.php?a=info&i=" . $info['id']);
                            }
                            break;
                            case "password":
                            if ((is_numeric($_GET['i'])) && (!empty($_POST['password']))) {
                                $result = $user->adminUpdatePassword($_GET['i'], $_POST['password']);
                                if ($result === true) {
                                    $error['type'] = "password";
                                    $error['message'] = "Password changed successfully.";
                                    header("Location: index.php?a=info&i=" . $info['id']);
                                } else {
                                    $error['type'] = "password";
                                    $error['message'] = $result;
                                }
                            } else {
                                $error['type'] = "password";
                                $error['message'] = "Something went wrong (at least one field is empty).";
                            }
                            break;
                        }
                    }
                    $addressList = $client->getAddressList($info['username']);
                    $transactionList = $client->getTransactionList($info['username']);
                    unset($info['password']);
                }
            }
            include("view/header.php");
            include("view/admin_info.php");
            include("view/footer.php");
            break;
            default:
            if ((!empty($_GET['m'])) && (!empty($_GET['i']))) {
                switch ($_GET['m']) {
                    case "deadmin":
                    $user->adminDeprivilegeAccount($_GET['i']);
                    header("Location: index.php?a=home");
                    break;
                    case "admin":
                    $user->adminPrivilegeAccount($_GET['i']);
                    header("Location: index.php?a=home");
                    break;
                    case "unlock":
                    $user->adminUnlockAccount($_GET['i']);
                    header("Location: index.php?a=home");
                    break;
                    case "lock":
                    $user->adminLockAccount($_GET['i']);
                    header("Location: index.php?a=home");
                    break;
                    case "del":
                    $user->adminDeleteAccount($_GET['i']);
                    header("Location: index.php?a=home");
                    break;
                }
            }
            $userList = $user->adminGetUserList();
            include("view/header.php");
            include("view/admin_home.php");
            include("view/footer.php");
            break;
        }
    }
} else {
    $error = array('type' => "none", 'message' => "");
    if (!empty($_POST['action'])) {
        $user = new User($mysqli);
        switch ($_POST['action']) {
            case "login":
            $result = $user->logIn($_POST['username'], $_POST['password'], $_POST['auth']);
            if (!is_array($result)) {
                $error['type'] = "login";
                $error['message'] = $result;
            } else {
                $_SESSION['user_session'] = $result['username'];
                $_SESSION['user_admin'] = $result['admin'];
                $_SESSION['user_supportpin'] = $result['supportpin'];
                $_SESSION['user_id'] = $result['id'];
                header("Location: index.php");
            }
            break;
            case "register":
            $result = $user->add($_POST['username'], $_POST['password'], $_POST['confirmPassword']);
            if ($result !== true) {
                $error['type'] = "register";
                $error['message'] = $result;
            } else {
                $username   = $mysqli->real_escape_string(   strip_tags(          $_POST['username']   ));
                $_SESSION['user_session'] = $username;
                $_SESSION['user_supportpin'] = "Please relogin for Support Key";
                    header("Location: index.php");
            }
            break;
        }
    }
    include("view/header.php");
    include("view/home.php");
    include("view/footer.php");
}
$mysqli->close();
?>
<script type="text/javascript">
var blockchain_url = "<?=$blockchain_url?>";
$("#withdrawform input[name='action']").first().attr("name", "jsaction");
$("#newaddressform input[name='action']").first().attr("name", "jsaction");
$("#pwdform input[name='action']").first().attr("name", "jsaction");
$("#withdrawform").submit(function(e)
{
    var postData = $(this).serializeArray();
    var formURL = $(this).attr("action");
    $.ajax(
    {
        url : formURL,
        type: "POST",
        data : postData,
        success:function(data, textStatus, jqXHR) 
        {
            var json = $.parseJSON(data);
            if (json.success)
            {
              $("#withdrawform input.form-control").val("");
            	$("#withdrawmsg").text(json.message);
            	$("#withdrawmsg").css("color", "green");
            	$("#withdrawmsg").show();
            	updateTables(json);
            } else {
            	$("#withdrawmsg").text(json.message);
            	$("#withdrawmsg").css("color", "red");
            	$("#withdrawmsg").show();
            }
            if (json.newtoken)
            {
              $('input[name="token"]').val(json.newtoken);
            }
        },
        error: function(jqXHR, textStatus, errorThrown) 
        {
            //ugh, gtfo    
        }
    });
    e.preventDefault();
});
$("#newaddressform").submit(function(e)
{
    var postData = $(this).serializeArray();
    var formURL = $(this).attr("action");
    $.ajax(
    {
        url : formURL,
        type: "POST",
        data : postData,
        success:function(data, textStatus, jqXHR) 
        {
            var json = $.parseJSON(data);
            if (json.success)
            {
            	$("#newaddressmsg").text(json.message);
            	$("#newaddressmsg").css("color", "green");
            	$("#newaddressmsg").show();
            	updateTables(json);
            } else {
            	$("#newaddressmsg").text(json.message);
            	$("#newaddressmsg").css("color", "red");
            	$("#newaddressmsg").show();
            }
            if (json.newtoken)
            {
              $('input[name="token"]').val(json.newtoken);
            }
        },
        error: function(jqXHR, textStatus, errorThrown) 
        {
            //ugh, gtfo    
        }
    });
    e.preventDefault();
});
$("#pwdform").submit(function(e)
{
    var postData = $(this).serializeArray();
    var formURL = $(this).attr("action");
    $.ajax(
    {
        url : formURL,
        type: "POST",
        data : postData,
        success:function(data, textStatus, jqXHR) 
        {
            var json = $.parseJSON(data);
            if (json.success)
            {
               $("#pwdform input.form-control").val("");
               $("#pwdmsg").text(json.message);
               $("#pwdmsg").css("color", "green");
               $("#pwdmsg").show();
            } else {
               $("#pwdmsg").text(json.message);
               $("#pwdmsg").css("color", "red");
               $("#pwdmsg").show();
            }
            if (json.newtoken)
            {
              $('input[name="token"]').val(json.newtoken);
            }
        },
        error: function(jqXHR, textStatus, errorThrown) 
        {
            //ugh, gtfo    
        }
    });
    e.preventDefault();
});

function updateTables(json)
{
	$("#balance").text(json.balance.toFixed(8));
	$("#alist tbody tr").remove();
	for (var i = json.addressList.length - 1; i >= 0; i--) {
		$("#alist tbody").prepend("<tr><td>" + json.addressList[i] + "</td></tr>");
	}
	$("#txlist tbody tr").remove();
	for (var i = json.transactionList.length - 1; i >= 0; i--) {
		var tx_type = '<b style="color: #01DF01;">Received</b>';
		if(json.transactionList[i]['category']=="send")
		{
			tx_type = '<b style="color: #FF0000;">Sent</b>';
		}
		$("#txlist tbody").prepend('<tr> \
               <td>' + moment(json.transactionList[i]['time'], "X").format('l hh:mm a') + '</td> \
               <td>' + json.transactionList[i]['address'] + '</td> \
               <td>' + tx_type + '</td> \
               <td>' + Math.abs(json.transactionList[i]['amount']) + '</td> \
               <td>' + json.transactionList[i]['fee'] + '</td> \
               <td>' + json.transactionList[i]['confirmations'] + '</td> \
            </tr>');
	}
}
</script>
