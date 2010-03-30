<?php

/* Defines */

/**** CONFIG ****/

define('REMEMBER_EXPIRES','24'); /* time in hours */
define('HOSTNAME','localhost'); /* This server's Hostname. Useful for password recover link */
define('BASEPATH','jabber/'); /* WITH trailing slash */

/**** MESSAGES ****/

define('ERROR_USER_EXISTS','El usuario existe');
define('ERROR_REQUIRED_FIELD','Campo requerido');
define('ERROR_EMAIL','Dirección de email no válida');
define('ERROR_USER_NOT_EXISTS','Usuario no registrado');
define('ERROR_PASSWORDS','Las contraseñas no coinciden');
define('ERROR_CAPTCHA','Captcha no válido');
define('ERROR_RECOVER','Ha ocurrido un error al intentar recuperar la contraseña');
define('ERROR_NO_GET_PARAMETERS','No se han especificado parámetros.');
define('ERROR_RESET_PASSWORD','Error al recuperar contraseña.');

define('ERROR_NO_VALID_ACTION','No se ha especificado una acción válida');
define('ERROR_NO_VALID_COOKIE','Cookie no válida');
define('ERROR_EXPIRED_COOKIE','Ha expirado el tiempo establecido para recuperar su contraseña. Haga click <a href="recover.php" title="Recuperar contraseña">aquí</a> para ir al formulario de recuperación');

define('SUCCESS_RECOVER','Recuperación exitosa. Se ha enviado un email a tu cuenta para recuperar tu contraseña');
define('SUCCESS_REGISTER','Usuario registrado con éxito');
define('SUCCESS_PASSWORD', 'Contraseña cambiada con éxito');

class JabberAuth {

    var $config;
    var $post;

    function __construct($config=FALSE){
        try {

            if (!is_array($config)) throw new Exception('Error: config is not an array.');
            if (!mysql_connect($config['dbhost'], $config['dbuser'], $config['dbpass']))
                throw new Exception('Error: Cannot connect to database.');
            else {
                if (!mysql_select_db($config['dbname']))
                    throw new Exception('Error: Cannot select database.');
            }

            $this->config = $config;
            if(isset($_POST['action'])){
                $this->post = $_POST;
            }

        } catch(Exception $e) {

            echo $e->getMessage();

        }

    }

    function str_makerand ($minlength, $maxlength, $useupper, $usespecial, $usenumbers) {
        $charset = "abcdefghijklmnopqrstuvwxyz";
        if ($useupper)
            $charset .= "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
        if ($usenumbers)
            $charset .= "0123456789";
        if ($usespecial)
            $charset .= "~@#$%^*()_+-={}|]["; // Note: using all special characters this reads: "~!@#$%^&*()_+`-={}|\\]?[\":;'><,./";
        if ($minlength > $maxlength)
            $length = mt_rand ($maxlength, $minlength);
        else
            $length = mt_rand ($minlength, $maxlength);
        $key='';
        for ($i=0; $i<$length; $i++)
            $key .= $charset[(mt_rand(0,(strlen($charset)-1)))];
        return $key;
    }


    function query($query) {

        $r = mysql_query($query);
        if($r === TRUE) return TRUE;
        if($r === FALSE) return FALSE;
        if (mysql_num_rows($r))
            return mysql_fetch_object($r);
        else
            return FALSE;
    }

    function jash($a, $b, $c){
        return hash('sha256',$a.':'.$b.':'.$c);
    }

    function set_password($new_user, $username, $password, $email=FALSE){
        if ($new_user){
            $prefs = $this->str_makerand(24, 24, TRUE, TRUE, TRUE);
            $hash = $this->jash($prefs,$username,$password);
            $this->query("INSERT INTO users (username,password,prefs,email) VALUES ('$username', '$hash', '$prefs', '$email' )");
        } else {
            $o = $this->query("SELECT username, email FROM users WHERE username = '$username' LIMIT 1");
            if($o){
                $prefs = $this->str_makerand(24, 24, TRUE, TRUE, TRUE);
                $hash = $this->jash($prefs,$username,$password);
                $q = "UPDATE users SET password = '$hash', prefs = '$prefs', remember = '', remember_expires = '' WHERE username = '$username' LIMIT 1;";
                if($this->query($q)) return TRUE;
                else return FALSE;
            } else
                return FALSE;
        }
    }


    function register(){
        $validation = $this->validate_fields('register');
        if (!isset($validation['errors'])) {
            $o = $this->query("SELECT username FROM users WHERE username = '".$validation['username']."';");
            if($o) {
                $_SESSION['errors'] = array('username'=>ERROR_USER_EXISTS);
            } else {
                $this->set_password(TRUE, $validation['username'], $validation['password'], $validation['email']);
                return TRUE;
            }
        } else {
            $_SESSION['errors'] = $validation['errors'];
        }
    }

    function recover_password(){
        $validation = $this->validate_fields('recover_password');
        if (!isset($validation['errors'])) {

            $r = $this->query("SELECT id, email FROM users WHERE username = '".$validation['username']."'");
            if($r){
                $id = $r->id;
                $cookie = $this->str_makerand(50,50,TRUE,FALSE,TRUE);
                $expires = time() + (REMEMBER_EXPIRES * 3600);
                $this->query("UPDATE users SET remember = '$cookie', remember_expires = '$expires' WHERE id = ".$r->id);
                $subject = "Recuperación de Contraseña de Jabber";
                $message = "Para recuperar tu contraseña, haz click en el siguiente enlace:\r\n\r\nhttp://".HOSTNAME.'/'.BASEPATH."resetpassword.php?id=$id&cookie=$cookie";
                return mail($r->email, $subject, $message);
            } else {
                $_SESSION['errors'] = array('username'=>ERROR_USER_NOT_EXISTS);
                return FALSE;
            }

        } else {
            $_SESSION['errors'] = $validation['errors'];
            return FALSE;
        }
    }


  /**
   * Validates an email address
   *
   * @param string $email  Raw email address
   * @return boolean Success
   * @link http://www.linuxjournal.com/article/9585 'Validate an E-Mail Address with PHP, the Right Way'
   */
    function validate_email_address($email,$check_dns=TRUE) {
        $atIndex = strrpos($email, "@");
        if (is_bool($atIndex) && !$atIndex) return FALSE;
        else  {
            $domain = substr($email, $atIndex+1);
            $local = substr($email, 0, $atIndex);
            $localLen = strlen($local);
            $domainLen = strlen($domain);

            if ($localLen < 1 || $localLen > 64) return FALSE;
            else if ($domainLen < 1 || $domainLen > 255) return FALSE;
            else if ($local[0] == '.' || $local[$localLen-1] == '.') return FALSE;
            else if (preg_match('/\\.\\./', $local)) return FALSE;
            else if (!preg_match('/^[A-Za-z0-9\\-\\.]+$/', $domain)) return FALSE;
            else if (preg_match('/\\.\\./', $domain)) return FALSE;
            else if (!preg_match('/^(\\\\.|[A-Za-z0-9!#%&`_=\\/$\'*+?^{}|~.-])+$/', str_replace("\\\\","",$local))) {
                if (!preg_match('/^"(\\\\"|[^"])+"$/', str_replace("\\\\","",$local))) return FALSE;
            }
            if ($check_dns) {
                if (!(checkdnsrr($domain,"MX") || checkdnsrr($domain,"A"))) return FALSE;
            }
        }
        return TRUE;
    }

    function validate_field($field){
        if(isset($this->post[$field])){
            return htmlentities($this->post[$field]);
        } else return FALSE;
    }

    function validate_confirmation($password){
        if(isset($this->post['confirmation'])){
            $safe_confirmation = htmlentities($this->post['confirmation']);
            if($password != $safe_confirmation) return FALSE;
            else return TRUE;
        } return FALSE;
    }

    function validate_email($required=FALSE){
        if ($required){
            if (!isset($this->post['email'])) return FALSE;
        }

        if ($this->post['email']!="") {
            $safe_email = htmlentities($this->post['email']);
            if($this->validate_email_address($safe_email,FALSE)) return $safe_email;
            else return FALSE;
        } return FALSE;
    }

    function validate_captcha(){
        if(isset($this->post['suma'])){
            $safe_suma = htmlentities($this->post['suma']);
            session_start();
            $_SESSION['debug']['suma'] = $safe_suma;
            $_SESSION['debug']['suma-var'] = $_SESSION['a'] + $_SESSION['b'];
            if($safe_suma != ($_SESSION['a'] + $_SESSION['b'])) return FALSE;
            else return TRUE;
        } return FALSE;
    }

    function validate_fields($form){

        switch ($form){
            case 'register':
                $username = $this->validate_field('username');
                if(!$username){
                    $errmsg['username'] = ERROR_REQUIRED_FIELD;
                } else $username = strtolower($username);

                $password = $this->validate_field('password');
                if(!$password){
                    $errmsg['username'] = ERROR_REQUIRED_FIELD;
                }
                if(!$this->validate_confirmation($password)){
                    $errmsg['confirmation'] = ERROR_PASSWORDS;
                }
                $email = $this->validate_email();
                if(!$email){
                    $errmsg['email'] = ERROR_EMAIL;
                } else $email = strtolower($email);
                $results = array(
                    'username' => $username,
                    'password' => $password,
                    'email' => $email
                );
                break;

            case 'change_password':
                $username = $this->validate_field('username');
                if(!$username){
                    $errmsg['username'] = ERROR_REQUIRED_FIELD;
                } else $username = strtolower($username);

                $password = $this->validate_field('password');
                if(!$password){
                    $errmsg['username'] = ERROR_REQUIRED_FIELD;
                }
                if(!$this->validate_confirmation($password)){
                    $errmsg['confirmation'] = ERROR_PASSWORDS;
                }
                $results = array(
                    'username' => $username,
                    'password' => $password
                );
                break;

            case 'recover_password':
                $username = $this->validate_field('username');
                if(!$username){
                    $errmsg['username'] = ERROR_REQUIRED_FIELD;
                } else $username = strtolower($username);
                $results = array('username' => $username);
                break;
        }

        if(!$this->validate_captcha()) $errmsg['suma'] = ERROR_CAPTCHA;

        if(isset($errmsg)) return array('errors' => $errmsg);
        else return $results;
   }

    function clear_session() {
        @session_start();
        $_SESSION['a'] = rand(0, 20);
        $_SESSION['b'] = rand(0, 20);
        $_SESSION['errors'] = NULL;
        $_SESSION['formdata'] = NULL;

    }

}


/* EOF jabberauth.php */
