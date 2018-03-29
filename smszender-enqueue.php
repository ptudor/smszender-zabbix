#!/usr/bin/env php
<?php
/*
smszender-enqueue.php
(c) 2016 Patrick Tudor

*/

if (file_exists("config-smszender.php")) {
        require("config-smszender.php");
   } else {
        require("config-smszender-default.php");
}

function sha256($string) {
        // a drop-in for "md5()" habits
        $algo = 'sha256';
        return hash($algo, $string . UNIQUE_IDENTIFIER);
        }

$r = new Redis();
$r->connect(REDIS_HOST, REDIS_PORT);

try {
	$r->select(REDIS_DB);
} catch (Exception $e) {
	echo $e->getMessage();
}

$r->incr(sha256("smszender_enqueue_boot"));
$bootcount = $r->get(sha256("smszender_enqueue_boot"));

// begin syslog block
openlog('smszender-enqueue', LOG_CONS, LOG_USER);
syslog(LOG_ERR, "started:$bootcount");

$to      = $argv[1];
$subject = $argv[2];
$body    = $argv[3];

// Get new ID...
$message_id = $r->incr(sha256("all:message"));

syslog(LOG_NOTICE, "status:queuing msg_id:$message_id to:$to message_subject:\"$subject\"");

// Prepare the message with an array
$message = array(
	"id"		=> $message_id,
	"to"		=> $to,
	"message_subject" => $subject,
	"message_body" => $body
);

// set the hash and expire it in a week
$r->hmset("zbx_message:$message_id", $message);
$r->expire("zbx_message:$message_id", 604800);

// push the id into the queue...
$r->lpush("queue:zbx_sms", $message_id);

?>
