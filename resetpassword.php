<?php require_once('functions.php');
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>Jabber | Nueva contraseña</title>
    <link rel="stylesheet" href="styles.css" type="text/css" media="screen" />
  </head>
  <body onload="yav.init('form_login', rules);">
    <div id="wrapper">
      <h1>JabberAuth</h1>
      <h2>Nueva Contraseña</h2>
      <?php if(isset($msg)) { ?>
      <div class="msg"><?php print $msg ?></div>
      <?php }  ?>
      <form name="form_resetpassword" class="form_resetpassword" method="post" action="<?php echo $_SERVER['PHP_SELF'] ?>" onsubmit="return yav.performCheck('form_resetpassword',rules,'inline');">
      <input type="hidden" name="action" value="reset_password" />
      <input type="hidden" name="username" value="<?php @print $username ?>" />
      <p>
        <label for="password">Password</label>
        <input type="password" name="password" value="" />
        <span id="errors_password" class="inline-error"><?php @print $errors['password']?></span>
      </p>
      <p>
        <label for="confirmation">Confirmar Password</label>
        <input type="password" name="confirmation" value="" />
        <span id="errors_confirmation" class="inline-error"><?php @print $errors['confirmation'] ?></span>
      </p>
      <p>
        <label for="suma" class="hidden">CAPTCHA</label>
        <span class="field"><?php echo "$a mas $b =" ?><input type="text" name="suma" value="" size="2" maxlength="2"/>
        <span id="errors_suma" class="inline-error"><?php @print $errors['suma'] ?></span>
      </p>
      <p>
        <input type="submit" name="set_password" value="Ingresar" />
      </p>
    </form>
  </div>
  <!-- Client-side validation. Fallbacks to server-side if javascript is disabled  -->
  <script type="text/javascript" src="js/yav.min.jss"></script>
  <script type="text/javascript">
    /* Validation rules. Using 'echo' for captcha value */
    var rules = [
     'password|required','password|minlength|6|Debe tener 6 caracteres mínimo',
     'confirmation|required','confirmation|equal|$password|Las contraseñas no coinciden',
     'suma|required','suma|equal|<?php echo $a+$b ?>|Captcha No válido'
    ];
  </script>
  </body>
</html>

