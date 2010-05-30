<?php require_once('functions.php');
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>Jabber | Registrar Usuario</title>
    <link rel="stylesheet" href="styles.css" type="text/css" media="screen" />
  </head>
  <body onload="yav.init('form_login', rules);">
    <div id="wrapper">
      <h1>JabberAuth</h1>
      <h2>Registrar Usuario</h2>
      <?php if(isset($msg)) { ?>
      <div class="msg"><?php print $msg ?></div>
      <?php }  ?>
      <form name="form_login" class="form_login" method="post" action="<?php echo $_SERVER['PHP_SELF'] ?>" onsubmit="return yav.performCheck('form_login',rules,'inline');">
      <input type="hidden" name="action" value="register" />
      <p>
        <label for="username">Usuario</label>
        <input type="text" name="username" value="<?php @print $_SESSION['formdata']['username'] ?>" />
        <span id="errors_username" class="inline-error"><?php @print $errors['username'] ?></span>
      </p>
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
        <label for="email">Email (no obligatorio)</label>
        <input type="text" name="email" value="<?php @print $_SESSION['formdata']['email'] ?>" />
        <span id="errors_email" class="inline-error"><?php @print $errors['email'] ?></span>
      </p>
      <p>
        <label for="suma" class="hidden">CAPTCHA</label>
        <span class="field"><?php echo "$a mas $b =" ?><input type="text" name="suma" value="" size="2" maxlength="2"/>
        <span id="errors_suma" class="inline-error"><?php @print $errors['suma'] ?></span>
      </p>
      <p>
        <input type="submit" name="registrar" value="Ingresar" />
      </p>
    </form>
  </div>
  <!-- Client-side validation. Fallbacks to server-side if javascript is disabled  -->
  <script type="text/javascript" src="js/yav.min.jss"></script>
  <script type="text/javascript">
    /* Validation rules. Using 'echo' for captcha value */
    var rules = [
     'username|required','username|alnumhyphen',
     'password|required','password|minlength|6|Debe tener 6 caracteres mínimo',
     'confirmation|required','confirmation|equal|$password|Las contraseñas no coinciden',
     'email|email|Dirección de email no válida',
     'suma|required','suma|equal|<?php echo $a+$b ?>|Captcha No válido'
    ];
  </script>
  </body>
</html>

