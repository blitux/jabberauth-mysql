<?php

/**
 * Validates an email address
 *
 * @param string $email  Raw email address
 * @return boolean Success
 * @link http://www.linuxjournal.com/article/9585 'Validate an E-Mail Address with PHP, the Right Way'
 */
function validateEmail($email) {
   $atIndex = strrpos($email, "@");
   if (is_bool($atIndex) && !$atIndex) return false;
   else  {

      $domain = substr($email, $atIndex+1);
      $local = substr($email, 0, $atIndex);
      $localLen = strlen($local);
      $domainLen = strlen($domain);

      if ($localLen < 1 || $localLen > 64) return false;
      else if ($domainLen < 1 || $domainLen > 255) return false;
      else if ($local[0] == '.' || $local[$localLen-1] == '.') return false;
      else if (preg_match('/\\.\\./', $local)) return false;
      else if (!preg_match('/^[A-Za-z0-9\\-\\.]+$/', $domain)) return false;
      else if (preg_match('/\\.\\./', $domain)) return false;
      else if (!preg_match('/^(\\\\.|[A-Za-z0-9!#%&`_=\\/$\'*+?^{}|~.-])+$/', str_replace("\\\\","",$local))) {
         if (!preg_match('/^"(\\\\"|[^"])+"$/', str_replace("\\\\","",$local))) return false;
      }
      if ($isValid && !(checkdnsrr($domain,"MX") || checkdnsrr($domain,"A"))) return false;
   }
   return true;
}

/**
 * Validates fields. Following DRY.
 *
 * @param string $email  Raw email address
 * @return boolean Success
 * @link http://www.linuxjournal.com/article/9585 'Validate an E-Mail Address with PHP, the Right Way'
 */

function validateFields(){

    /* Username */
    if(isset($_POST['user'])){
        $clean_username = htmlentities(strtolower($_POST['username']));
        $_SESSION['username'] = $clean_username;
    } else $errmsg['username'] = REQUIRED_MSG;

    /* Password */
    if(isset($_POST['password'])){
        $clean_password = htmlentities($_POST['password']);
    } else $errmsg['password'] = REQUIRED_MSG;

    /* Password confirmation */
    if(isset($_POST['confirmation'])){
        $clean_confirmation = htmlentities($_POST['confirmation']);
        if($clean_password != $clean_confirmation) $errmsg['confirmation'] = CONFIRMATION_MSG;
    } else $errmsg['confirmation'] = REQUIRED_MSG;

    /* Email */
    if ($_POST['email']!="") {
        $clean_email = htmlentities($_POST['email']);
        if(validateEmail($clean_email)) $_SESSION['email'] = $clean_email;
        else $errmsg['email'] = EMAIL_MSG;
    } else $errmsg['email'] = REQUIRED_MSG ;

    /* Captcha */
    if(isset($_POST['suma'])){
        $clean_suma = htmlentities($_POST['suma']);
        if($clean_suma != ($_SESSION['a'] + $_SESSION['b'])) $errmsg['suma'] = CAPTCHA_MSG;
    } else $errmsg['suma'] = REQUIRED_MSG;

    if(isset($errmsg)) return array('errors'=> $errmsg);
    else return array('username' => $clean_username, 'password' => $clean_password,'email' => $clean_email );
}


/* @TODO: Fix this shit */
function formchpass($errmsg){ ?>
<form class="form_login" METHOD="POST" ACTION="<?php echo $_SERVER["PHP_SELF"] ?>">
<table>
  <tr><TD COLSPAN="2"><B>Cambiar contraseña?</B></td></tr>
  <tr><td>Password:</td><td><INPUT TYPE="password" NAME="password_" ></td></tr>
  <tr><td>Password de nuevo:</td><td><INPUT TYPE="password" NAME="password__" ></td></tr>
  <tr><td> <CENTER><INPUT TYPE="submit" NAME="chpass" VALUE="Cambiar"></CENTER></td>
  <td> <CENTER><INPUT TYPE="submit" NAME="salir" VALUE="Salir"></CENTER></td>
</tr>
<tr><TD COLSPAN="2"><B><?php if ( isset($errmsg) )foreach ($errmsg  as $i => $value)   echo "<div>".$value."</div>"; ?></B></td></tr>
</table>
</form>
<?php } /* end formchpass */


/* @TODO: make sure this captcha thing works... */
if(!isset($_POST['registrar'])) {
    $_SESSION['a'] = rand(0, 20);
    $_SESSION['b']= rand(0, 20);
}

define('REQUIRED_MSG','Campo requerido');
define('EMAIL_MSG','Dirección de email no válida');
define('CONFIRMATION_MSG','Las contraseñas no coinciden');
define('LENGTH_MSG','');
define('CAPTCHA_MSG','¿Problemas con matemática?');
define('EMAIL_CONFIRMATION_MSG','Recuperación exitosa. Se ha enviado un email a tu cuenta para recuperar tu contraseña.');

if(isset($_POST['registrar'])){

    if(isset($_POST['remember_pass'])) {

        $validation = validateFields();
        if (!isset($validation['errors'])) {
            /* All fields ok, proceed! */
            if ($auth->remember($validation['username'], $validation['password']) ) {
                $message = EMAIL_CONFIRMATION_MSG;
                return; /* WTF? */
            } else {
                /* @TODO something went terribly wrong... */
            }
        } else $errmsg = $validation['errors'];

    } else {

        $validation = validateFields();
        if (!isset($validation['errors'])) {
            $auth->jabber_user = $validation['username'];
            $auth->jabber_pass = $validation['password'];
            $auth->jabber_email = $validation['email'];
            /* @TODO: wtf? */
            if ($auth->alta($salida) ) {
                echo "<pre>$salida</pre>";
                echo "\nOK ;) ";
                return;
            }
            else $errmsg[]=$salida;
        } else $errmsg = $validation['errors'];

    }

} else if (isset($_POST['chpass'])) {

    $validation = validateFields();
    if (!isset($validation['errors'])) {

        $auth->jabber_user = $_SESSION['username'];
        $auth->jabber_pass = $validation['password'];
        if ($auth->setpass($salida)) {
            /* @TODO password change Success */
            echo "\nOK ;) ";
            return;
        } else $errmsg[] = $salida;

    } else $errmsg = $validation['errors'];

    formchpass($errmsg);
    return;

} else if (isset($_GET['confirm'])) {

    $confirmcapt = htmlentities($_GET['confirm']);
    $usercapt = htmlentities($_GET['user']);
    $_SESSION['username'] = $usercapt;
    echo "user ".$usercapt." confirm".$confirmcapt;

    if ($auth->confirm ($usercapt, $confirmcapt, $salida)) {
        formchpass(NULL);
        echo "OK ;) ";
        return;
    }
    else $errmsg[]=$salida;
}



if(isset($_POST['registrar'])) $submitted = 'enviada'; else $submitted = '';

$username  = isset($_SESSION['username']) ? $_SESSION['username'] : '';
$email = isset($_SESSION['email']) ? $_SESSION['email'] : '';
$a = $_SESSION['a']; $b = $_SESSION['b'];


?>