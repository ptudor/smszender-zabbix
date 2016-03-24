<?php
/* 
smszender-carriers.php
(c) 2016 Patrick Tudor

   This file is where carriers are defined. Basically, 
   define the correct url format to accept the message subject and body.
   Just about every SMS API functions the same; I use nexmo. Add more.

v1.0: localhost, nexmo -ptudor

*/

function smszender_localhost_url($to, $message_subject, $message_body) {
        $url = 'http://localhost/sms/json?' . http_build_query([
            'api_key' => LOCALHOST_SMS_API_KEY,
            'api_secret' => LOCALHOST_SMS_API_SECRET,
            'to' => $to,
            'from' => LOCALHOST_SMS_NUMBER,
            'text' => $message_subject." ".$message_body
        ]);
        return $url;
}

function smszender_nexmo_url($to, $message_subject, $message_body) {
        $url = 'https://rest.nexmo.com/sms/json?' . http_build_query([
            'api_key' => NEXMO_SMS_API_KEY,
            'api_secret' => NEXMO_SMS_API_SECRET,
            'to' => $to,
            'from' => NEXMO_SMS_NUMBER,
            'text' => $message_subject." ".$message_body
        ]);
        return $url;
}

?>
