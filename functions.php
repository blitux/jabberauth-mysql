<?php

require_once('jabberauth.php');

$config = array(
    'dbuser'=>'dbusr',
    'dbpass'=>'dbpwd',
    'dbhost'=>'localhost',
    'dbname'=>'jabber'
);

$jauth = new JabberAuth($config);

if(isset($_POST['action'])){

    switch($_POST['action']) {

        case 'register':
            if($jauth->register()){
                $msg = SUCCESS_REGISTER;
                $jauth->clear_session();
            } else {
                $jauth->clear_session();
                $_SESSION['formdata']['username'] = $_POST['username'];
                $_SESSION['formdata']['email'] = $_POST['email'];
            }
            break;

        case 'recover_password':
            if($jauth->recover_password()){
                $msg = SUCCESS_RECOVER;
                $jauth->clear_session();
            }
            break;
        case 'reset_password':
            if($jauth->set_password(FALSE,$_POST['username'],$_POST['password'])){
                $msg = SUCCESS_PASSWORD;
                $jauth->clear_session();
            } else {
                die(ERROR_RESET_PASSWORD);
            }
            break;
        default:
            die(ERROR_NO_VALID_ACTION);
    }

} else {

    $jauth->clear_session();

    if (substr($_SERVER['SCRIPT_NAME'],-17) == 'resetpassword.php') {

        if (isset($_GET['cookie']) && isset($_GET['id'])) {
            $r = $jauth->query("SELECT username, remember, remember_expires FROM users WHERE id = ".$_GET['id']);
            $username = $r->username;
            if($r->remember != $_GET['cookie'])
                die(ERROR_NO_VALID_COOKIE);
            else {
                if($r->remember > time())
                    die(ERROR_EXPIRED_COOKIE);
            }
        } else
            die(ERROR_NO_GET_PARAMETERS);
    }

}

$a = $_SESSION['a'];
$b = $_SESSION['b'];
$errors = @$_SESSION['errors'];



?>