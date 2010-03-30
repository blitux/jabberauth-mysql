<?php require_once('functions.php');
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>Jabber | Recuperar Contraseña</title>
    <link rel="stylesheet" href="styles.css" type="text/css" media="screen" />
  </head>
  <body onload="yav.init('form_remember', rules);">
    <div id="wrapper">
      <h1>JabberAuth</h1>
      <h2>Recuperar Contraseña</h2>
      <pre><?php print_r($errors)?></pre>
      <?php if(isset($msg)) { ?>
      <div class="msg"><?php print $msg ?></div>
      <?php }  ?>
      <form name="form_recover" class="form_recover" method="post" action="<?php echo $_SERVER['PHP_SELF'] ?>" onsubmit="return yav.performCheck('form_recover',rules,'inline');">
      <input type="hidden" name="action" value="recover_password" />
      <p>
        <label for="username">Usuario</label>
        <input type="text" name="username" value="" />
        <span id="errors_username" class="inline-error"><?php @print $errors['username'] ?></span>
      </p>
      <p>
        <label for="suma" class="hidden">CAPTCHA</label>
        <span class="field"><?php echo "$a mas $b = " ?><input type="text" name="suma" value="" size="2" maxlength="2"/>
        <span id="errors_suma" class="inline-error"><?php @print $errors['suma'] ?></span>
      </p>
      <p>
        <input type="submit" name="submit" value="Enviar Email" />
      </p>
    </form>
  </div>
  <!-- Client-side validation. Fallbacks to server-side if javascript is disabled  -->
  <script type="text/javascript" src="js/validation.min.js"></script>
  <script type="text/javascript">
    /* Validation rules. Using 'echo' for captcha value */
    var rules = [
     'username|required|Campo requrido',
     'suma|required','suma|equal|<?php echo $a+$b ?>|Captcha no válido'
    ];
  </script>
  </body>
</html>

