<?php require_once('functions.php');
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>Altas Jabber <?php echo $submitted ?></title>
    <link rel="stylesheet" href="styles.css" type="text/css" media="screen" />
  </head>
  <body onload="yav.init('form_login', rules);">
    <div id="wrapper">
      <h1>Altas Jabber</h1>
      <?php if(isset($errmsg)) { ?>
      <div class="error">
        <ul>
          <?php foreach($errmsg as $key => $value) { echo "<li>$value</li>"; } ?>
        </ul>
      </div>
      <?php }  ?>
      <form name="form_login" class="form_login" method="post" action="<?php echo $_SERVER['PHP_SELF'] ?>" onsubmit="return yav.performCheck('form_login',rules,'inline');">
      <p>
        <label for="username">Login</label>
        <input type="text" name="username" value="<?php echo $username ?>" />
        <span id="errors_username"></span>
      </p>
      <p>
        <label for="password">Password</label>
        <input type="password" name="password" value="" />
        <span id="errors_password"></span>
      </p>
      <p>
        <label for="confirmation">Confirmar Password</label>
        <input type="password" name="confirmation" value="" />
        <span id="errors_confirmation"></span>
      </p>
      <p>
        <label for="email">Email</label>
        <input type="text" name="email" value="<?php echo $email ?>" />
        <span id="errors_email"></span>
      </p>
      <p>
        <label for="remember_pass" class="hidden">Recuperar Password</label>
        <span class="field"><input type="checkbox" name="remember_pass" value="1" /> Deseo recuperar mi password</span>
      </p>
      <p>
        <label for="suma" class="hidden">CAPTCHA</label>
        <span class="field"><?php echo "$a mas $b =" ?><input type="text" name="suma" value="" size="2" maxlength="2"/>
        <span id="errors_suma"></span>
      </p>
      <p>
        <input type="submit" name="registrar" value="Ingresar" />
      </p>
    </form>
  </div>
  <!-- Client-side validation. Fallbacks to server-side if javascript is disabled  -->
  <script type="text/javascript" src="js/yav.js"></script>
  <script type="text/javascript" src="js/yav-config-es.js"></script>
  <script type="text/javascript">
    /* Validation rules. Using 'echo' for captcha value */
    var rules = [
     'username|required','username|alnumhyphen',
     'password|required','password|minlength|6|Debe tener 6 caracteres mínimo',
     'confirmation|required','confirmation|equal|$password|Las contraseñas no coinciden',
     'email|email|Dirección de email no válida',
     'suma|required','suma|equal|<?php echo $a+$b ?>|¿Problemas con matemática?'
    ];
  </script>
  </body>
</html>

