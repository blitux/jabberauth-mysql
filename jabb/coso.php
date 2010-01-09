<?php
	include ('Mail_.php');


// include("Mail.php");
/* mail setup recipients, subject etc */
$recipients = "mabeett@gmail.com";
$headers["From"] = "mabeett@lugmen.org.ar";
$headers["To"] = "mabeett@gmail.com";
$headers["Subject"] = "User feedback";
$mailmsg = "Hello, This is a test.";
/* SMTP server name, port, user/passwd */
$smtpinfo["host"] = "mail.lugmen.org.ar";
$smtpinfo["port"] = "25";
$smtpinfo["auth"] = true;
$smtpinfo["username"] = "mabeett";
$smtpinfo["password"] = "noollug2009";
/* Create the mail object using the Mail::factory method */
$mail_object =& Mail::factory("smtp", $smtpinfo);
/* Ok send mail */
$mail_object->send($recipients, $headers, $mailmsg);
?>
a
