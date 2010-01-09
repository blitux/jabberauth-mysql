<?php
/*
Copyright (c) <2005> LISSY Alexandre, "lissyx" <alexandrelissy@free.fr>

Permission is hereby granted, free of charge, to any person obtaining a copy of
this software andassociated documentation files (the "Software"), to deal in the
Software without restriction, including without limitation the rights to use,
copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the
Software, and to permit persons to whom the Software is furnished to do so,
subject to thefollowing conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS
FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR
COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER
IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/

/*
Dendencias: 
   php-mail que depdende de:
      php-pear
      php-net-socket 
      php-net-smtp

 */



// TODO: verificar fechas de recuperación de contraseña, que se venzan las semillitas.

class JabberAuth {
   var $dbhost; /* MySQL server */
   var $dbuser; /* MySQL user */
   var $dbpass; /* MySQL password */
   var $dbbase; /* MySQL database where users are stored */

   var $mail_from;
   var $mail_hostsrv;
   var $mail_port;
   var $mail_auth;
   var $mail_username;
   var $mail_password;

   var $http_domain; //dominio del server http://example.org/

   var $debug      = true;                  /* Debug mode */
   var $debugfile   = "/var/log/pipe-debug.log";  /* Debug output */
   var $logging   = true;                  /* Do we log requests ? */
   var $logfile   = "/var/log/pipe-log.log" ;   /* Log file ... */
   /*
    * For both debug and logging, ejabberd have to be able to write.
    */

   var $jabber_user;   /* This is the jabber user passed to the script. filled by $this->command() */
   var $jabber_pass;   /* This is the jabber user password passed to the script. filled by $this->command() */
   var $jabber_server; /* This is the jabber server passed to the script. filled by $this->command(). Useful for VirtualHosts */
   var $jabber_email;  /* user's email */
   var $jid;           /* Simply the JID, if you need it, you have to fill. */
   var $data;          /* This is what SM component send to us. */

   var $dateformat = "M d H:i:s"; /* Check date() for string format. */
   var $command; /* This is the command sent ... */
   var $mysock;  /* MySQL connection ressource */
   var $stdin;   /* stdin file pointer */
   var $stdout;  /* stdout file pointer */

   function JabberAuth()
   {
      define_syslog_variables();
      openlog("pipe-auth", LOG_NDELAY, LOG_SYSLOG);

      if($this->debug) {
         error_reporting(E_ALL);
         ini_set("log_errors", "1");
         ini_set("error_log", $this->debugfile);
      }
      $this->logg("Starting pipe-auth ..."); // We notice that it's starting ...
      $this->openstd();
   }

   function stop()
   {
      $this->logg("Shutting down ..."); // Sorry, have to go ...
      closelog();
      $this->closestd(); // Simply close files
      exit(0); // and exit cleanly
   }

   function openstd()
   {
      $this->stdout = fopen("php://stdout", "w"); // We open STDOUT so we can read
      $this->stdin  = fopen("php://stdin", "r"); // and STDIN so we can talk !
   }

   function readstdin()
   {
      $l      = fgets($this->stdin, 3); // We take the length of string
      $length = @unpack("n", $l); // ejabberd give us something to play with ...
      $len    = $length["1"]; // and we now know how long to read.
      if($len > 0) { // if not, we'll fill logfile ... and disk full is just funny once
         $this->logg("Reading $len bytes ... "); // We notice ...
         $data   = fgets($this->stdin, $len+1);
         // $data = iconv("UTF-8", "ISO-8859-15", $data); // To be tested, not sure if still needed.
         $this->data = $data; // We set what we got.
         //$this->logg("IN: ".$data);//No habilitar!!!!
      }
   }

   function closestd()
   {
      fclose($this->stdin); // We close everything ...
      fclose($this->stdout);
   }

   function out($message)
   {
      fwrite($this->stdout, $message); // We reply ...
      //fflush($this->stdout); //FIXME: lo coloco por las dudas, será correcto? aquí aparece http://tinyurl.com/57ppmn
      $dump = @unpack("nn", $message);
      $dump = $dump["n"];
      $this->logg("OUT: ". $dump);
   }

   function myalive()
   {
      if(!is_resource($this->mysock) || !@mysql_ping($this->mysock)) { // check if we have a MySQL connection and if it's valid.
         @$this->mysql(); // We try to reconnect if MySQL gone away ...
         return @mysql_ping($this->mysock); // we simply try again, to be sure ...
      } else {
         return true; // so good !
      }
   }

   function play()
   {
      do {
         $this->logg("hoolaaaaaaa"); 
         $this->readstdin(); // get data
         $length = strlen($this->data); // compute data length
         if($length > 0 ) { // for debug mainly ...
            //$this->logg("GO: ".$this->data);// NO habilitar!!!
            $this->logg("data length is : ".$length);
         }
         $ret = $this->command(); // play with data !
         $this->logg("RE: " . $ret); // this is what WE send.
         $this->out($ret); // send what we reply.
         $this->data = NULL; // more clean. ...
      } while (true);
   }

   function sal () { // salpimentar a gusto ;)
      $a = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!@#$%^&*()_=+-|{}[]/?<>,.~';
      $salt = $pepp = '';
      for ($i = 0; $i < 8; $i++) {
         $pepp .= substr (microtime () * 1000000, rand (0, 3), 2);
         $salt .= $a [rand (0, strlen ($a))];
      }
      return $pepp. $salt;
   }

   function jash ($a, $b, $c) {
      return sha1 ($a. ':'. $b. ':'. $c);
   }

   function auth () {
      $juser = $this->jabber_user;
      $jpass = $this->jabber_pass;
      $q = "SELECT prefs, pass, user, active FROM user WHERE user = '$juser' LIMIT 1";
      $r = mysql_query ($q);
      if (mysql_num_rows ($r)) {
         $o = mysql_fetch_object ($r);
         if ( $o->active !="1" ) 
            return false;
         $prefs = $o->prefs;
         $dpass = $o->pass;
         $user = $o->user;
         if ($this->jash ($prefs, $user, $jpass) === $dpass) {
            $q = "UPDATE user SET last_auth = NOW() WHERE user = '".$this->jabber_user."' LIMIT 1";
            $r = mysql_query ($q);
            return true;
         } else return false;
      } else return false;
   }

   function command()
   {
      $data = $this->splitcomm(); // This is an array, where each node is part of what SM sent to us :
      // 0 => the command,
      // and the others are arguments .. e.g. : user, server, password ...

      if($this->myalive()) { // Check we can play with MySQL
         if(strlen($data[0]) > 0 ) {
            $this->logg("Command was : ".$data[0]);
         }
         switch($data[0]) {
            case "isuser": // this is the "isuser" command, used to check for user existance
                  $this->jabber_user = $data[1];
                  $parms = $data[1];  // only for logging purpose
                  $return = $this->checkuser();
               break;

            case "auth": // check login, password
                  $this->jabber_user = $data[1];
                  $this->jabber_pass = $data[3];
                  $parms = $data[1].":".$data[2].":password"; // only for logging purpose
                  //$return = $this->checkpass();
                  $return = $this->auth ();
               break;

            case "setpass":
/*                  $this->jabber_user = $data[1];
                  $this->jabber_pass = $data[3];
                  $parms = $data[1].":".$data[2].":password"; // only for logging purpose
                  $return = $this->setpass ($basura); */

/*! hay un bug en la version de debian lenny de ejabberd que hace que se quede esperando estupidamente, actualiar y usar
@@ -61,15 +64,23 @@
        Result
     end.
 
-loop(Port) ->
+loop(Port, Timeout) ->
     receive
    {call, Caller, Msg} ->
        Port ! {self(), {command, encode(Msg)}},
        receive
       {Port, {data, Data}} ->
-          Caller ! {eauth, decode(Data)}
+                    ?DEBUG("extauth call '~p' received data response:~n~p", [Msg, Data]),
+                    Caller ! {eauth, decode(Data)};
+ 
[...]  
*/

                  $return = false; 
                  break;

            default:
                  $this->stop(); // if it's not something known, we have to leave.
                  // never had a problem with this using ejabberd, but might lead to problem ?
               break;
         }

         $return = ($return) ? 1 : 0;

         if(strlen($data[0]) > 0 && strlen($parms) > 0) {
            $this->logg("Command : ".$data[0].":".$parms." ==> ".$return." ");
         }
         return pack("nn", 2, $return);
      } else {
         // $this->prevenir(); // Maybe useful to tell somewhere there's a problem ...
         return pack("nn", 2, 0); // it's so bad.
      }
   }


   function setpass (&$salida)
   /*! cambia la clave de usuario */
  {
      if($this->myalive()) {

         $q = "SELECT user,email FROM user WHERE user = '".$this->jabber_user."' LIMIT 1";
         $r = mysql_query ($q);
         if (mysql_num_rows ($r)) {
            $o = mysql_fetch_object ($r);
            $email = $o->email;

            $prefs = $this->str_makerand(24, 24, TRUE, TRUE, TRUE);
            $jpass = $this->jash($prefs, $this->jabber_user, $this->jabber_pass);

              $q = 'UPDATE user SET pass = "'.$jpass.'", prefs = "'.$prefs.'" WHERE user = "'.$this->jabber_user.'" LIMIT 1';
            if (mysql_query ($q))
               $this->email_notif_changed($this->jabber_user, $email);
               return TRUE;
         }
         $salida = "Error en la base de datos 0";
         return FALSE;
      }
      $salida = "Error en la base de datos 1";
      return FALSE;
   }

   function checkuser()
   {
      $q = 'SELECT id FROM user WHERE user = "'. $this->jabber_user. '"';
      $r = mysql_query ($q);
      if (mysql_num_rows ($r)) {
         return true;
      } else {
         return false;
      }
   }

   function splitcomm() // simply split command and arugments into an array.
   {
      return explode(":", $this->data);
   }

   function mysql() // "MySQL abstraction", this opens a permanent MySQL connection, and fill the ressource
   {
      $this->mysock = @mysql_pconnect($this->dbhost, $this->dbuser, $this->dbpass);
      mysql_select_db($this->dbbase, $this->mysock);
      $this->logg("MySQL :: ". (is_resource($this->mysock) ? "Connect" : "Disconnect"));
   }

   function logg($message) // pretty simple, using syslog.
   // some says it doesn't work ? perhaps, but AFAIR, it was working.
   {
      if($this->logging) {
         syslog(LOG_INFO, $message);
      }
   }


   function str_makerand ($minlength, $maxlength, $useupper, $usespecial, $usenumbers) {
   //!   Crea una cadena de caracteres aleatorea
   /* http://www.codewalkers.com/c/a/User-Management-Code/random-string-generator-key-generator/
   Author: Peter Mugane Kionga-Kamau
   http://www.pmkmedia.com

   Description: string str_makerand(int $minlength, int $maxlength, bool $useupper, bool $usespecial, bool $usenumbers)
   returns a randomly generated string of length between $minlength and $maxlength inclusively.

   Notes:
   - If $useupper is true uppercase characters will be used; if false they will be excluded.
   - If $usespecial is true special characters will be used; if false they will be excluded.
   - If $usenumbers is true numerical characters will be used; if false they will be excluded.
   - If $minlength is equal to $maxlength a string of length $maxlength will be returned.
   - Not all special characters are included since they could cause parse errors with queries.
   
   Modify at will.
   */
   $charset = "abcdefghijklmnopqrstuvwxyz";
   if ($useupper) $charset .= "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
   if ($usenumbers) $charset .= "0123456789";
   if ($usespecial) $charset .= "~@#$%^*()_+-={}|]["; // Note: using all special characters this reads: "~!@#$%^&*()_+`-={}|\\]?[\":;'><,./";
   if ($minlength > $maxlength) $length = mt_rand ($maxlength, $minlength);
   else $length = mt_rand ($minlength, $maxlength);
   $key='';
   for ($i=0; $i<$length; $i++) $key .= $charset[(mt_rand(0,(strlen($charset)-1)))];
   return $key;
   }

   function email_key($user, $email, $remember_key) {
   /*! envía llavecitas para confirmar mail o para cambiar clave  */
      $baseurl = $this->http_domain.$_SERVER["PHP_SELF"];
      echo "enviando mail <br /> email=$email, <a href=\"$baseurl?user=$user&confirm=$remember_key\">$baseurl?user=$user&confirm=$remember_key</a>";
      $mailmsg=$user." ha solicitado dar de alta una cuenta, o bien ha pedido renovar su contraseña, y para ello se usó su cuenta de correo electronico como dato de registro, para continuar siga el link ".$baseurl."?user=".$user."&confirm=".$remember_key." \n".date('Y-m-d, H:i:s');
      $this->mail_raw($email, "Datos de cuenta xmpp lugmen.org.ar", $mailmsg);
   }

   function email_notif_changed($user, $email)
   /*! notifica cambio de clave */
   {
      $baseurl = $this->http_domain.$_SERVER["PHP_SELF"];
      $mailmsg = " Ha cambiado su contraseña en  el servicio de mensajería XMPP de lugmen.org.ar, porque Ud hizo en el formulario de suscripción, o  bien un administrador. \n ".date('Y-m-d, H:i:s'); 
      $this->mail_raw($email, "Datos de cuenta xmpp lugmen.org.ar", $mailmsg);
   }

   function mail_raw($recipient, $subject, $mailmsg)
   /*! Evía mensaje de correo  al destinatario $recipient, con asunto $subject y cuerpo $mailmsg */
   {
      include ("Mail.php"); 

      $headers["From"] = $this->mail_from;
      $headers["To"] = $recipient;
      $headers["Subject"] = $subject;
      $smtpinfo["host"] = $this->mail_hostsrv;
      $smtpinfo["port"] = $this->mail_port;
      $smtpinfo["auth"] = $this->mail_auth;
      $smtpinfo["username"] = $this->mail_username;
      $smtpinfo["password"] = $this->mail_password;
      $mail_object =& Mail::factory("smtp", $smtpinfo);
      $mail_object->send($recipient, $headers, $mailmsg);
   }

   function alta (&$fnmsg)
  /*! Gestiona la alta de un nuevo usuario, toma los datos de la clase,
      si existe una dirección de correo, envia mail para corroborar, 
      sino registra directamente
      Retorna TRUE en exito;
  */
   {
   $email = $this->jabber_email; 
      if($this->myalive()) { // Check we can play with MySQL
         
         $this->jabber_user = $this->jabber_user;
         if ( $this->checkuser() ){
            $fnmsg = "Usuario ya existe.";
            return FALSE;
         }
         
         $prefs = $this->str_makerand(24, 24, TRUE, TRUE, TRUE);
         $jpass = $this->jash($prefs, $this->jabber_user, $this->jabber_pass);

         if ($this->jabber_email!="") {
            $remember_key = $this->str_makerand(24, 24, TRUE, FALSE, TRUE);
            $db_key = $this->jash($this->jabber_user, $this->jabber_user, $remember_key);
            $q = 'INSERT INTO user (user,pass,prefs,active,email,date_remember,remember_key) VALUES ("'.$this->jabber_user.'","'.$jpass.'","'.$prefs.'", 0, "'.$email.'", NOW(), "'.$db_key.'" );';
            $this->email_key($this->jabber_user, $this->jabber_email, $remember_key);            
         }
         else 
            $q = 'INSERT INTO user (user,pass,prefs,active) VALUES ("'.$this->jabber_user.'","'.$jpass.'","'.$prefs.'",1);';
         if (mysql_query ($q)) {
            $fnmsg = "Felicidades!";
            return TRUE;
         } else
            $fnmsg = "Error interno ingresando los datos, consulta";
      } else $fnmsg = "Error interno ingresando los datos, conexión";
      return FALSE;
   }

   function remember ($user, $email)
   /*! Genera clave de confirmación para el usuario $user que debe tener mail $mail
   Retorna FALSE si hay problemas con la base de datos solamente
   */
   {
      if($this->myalive()) {

         $q = "SELECT id FROM user WHERE user = '$user' AND email = '$email' LIMIT 1";
         $r = mysql_query ($q);
         if (mysql_num_rows ($r)) {
            $remember_key = $this->str_makerand(24, 24, TRUE, FALSE, TRUE);
            $db_key = $this->jash($user, $user, $remember_key);
            $q = "UPDATE user SET remember_key = '$db_key', date_remember = NOW() WHERE user = '$user' AND email = '$email' LIMIT 1";
               mysql_query ($q);

            $this->email_key($user, $email, $remember_key); 
         }
         return TRUE;
      }
   return FALSE;
   }

   function confirm ($juser, $confirm, &$fnmsg)
   /*! Verifica que para el usuario $juser la cadena de confirmación $confirm sea
   válida, Si hay errores escribe en $fnmsg 
   Calidad: OK
   */
   {
      if($this->myalive()) { // Check we can play with MySQL

         $key = $this->jash($juser, $juser, $confirm);
            $q = "UPDATE user SET active = 1, date_remember = NULL, remember_key = NULL WHERE user = '$juser' AND remember_key = '$key'";

         mysql_query ($q);
         if ( mysql_affected_rows() )
            return TRUE;
         else {
            $fnmsg = "Cadena_ de confirmacion incorrecta";
            return FALSE;
         }
        }
      $fnmsg = "Error interno ingresando los datos";
      return FALSE;
   }


}
?>
