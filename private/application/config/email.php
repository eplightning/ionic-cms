<?php
return array(
// smtp, mail, sendmail, none
'type' => 'smtp',
'from' => 'example@gmail.com',
'from_name' => 'Example',

// sendmail only options
'sendmail' => '/usr/sbin/sendmail -bs',

// smtp only options
'host' => 'smtp.gmail.com',
'port' => 465,
'encryption' => 'ssl', // tcp (none), ssl, tls
'username' => 'example@gmail.com',
'password' => 'password',

'per_session' => 20
);