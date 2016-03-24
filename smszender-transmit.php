#!/usr/bin/env php
<?php
/* 
smszender-transmit.php
(c) 2016 Patrick Tudor

*/

$start_time = microtime(true); 

unset($debug);
$debug=true;

if (file_exists("config-smszender.php")) {
	require("config-smszender.php");
   } else {
	echo ("Warning: Using default configuration. Please copy config-smszender-default.php to config-smszender.php.");
	require("config-smszender-default.php");
}

if (file_exists("smszender-carriers.php")) {
	require("smszender-carriers.php");
   } else {
	echo ("Fatal: Unable to find required file named smszender-carriers.php.");
	exit(99);
}

function sha256($string) {
	// a drop-in for "md5()" habits 
	$algo = 'sha256';
	return hash($algo, $string . UNIQUE_IDENTIFIER);
	}

// begin Redis
$r = new Redis();
$r->pconnect(REDIS_HOST, REDIS_PORT);
        try {
                $r->select(REDIS_DB);
        } catch (Exception $e) {
                echo $e->getMessage();
        }

// increment the counter that keeps track of how many times the daemon has started
$r->incr(sha256("smszender_transmit_boot"));
$bootcount = $r->get(sha256("smszender_transmit_boot"));

// begin syslog block
openlog('smszender-transmit', LOG_NDELAY, LOG_USER);
syslog(LOG_ERR, "started:$bootcount");
// end syslog block

// begin ^C block
declare(ticks=1); // PHP internal, make signal handling work
if (!function_exists('pcntl_signal'))
{
    printf("Error, you need to enable the pcntl extension in your php binary, see http://www.php.net/manual/en/pcntl.installation.php for more info%s", PHP_EOL);
    exit(1);
}

$running = true; // used in the while loop
function signalHandler($signo)
{
    global $running;
    $running = false;
    printf("%sInterrupt received, please wait up to five seconds.%s", PHP_EOL, PHP_EOL);
}
pcntl_signal(SIGINT, 'signalHandler');
// end ^C block

function delete_smszender_nexmo_url($to, $message_subject, $message_body) {
	$url = 'https://rest.nexmo.com/sms/json?' . http_build_query([
            'api_key' => NEXMO_SMS_API_KEY,
            'api_secret' => NEXMO_SMS_API_SECRET,
            'to' => $to,
            'from' => NEXMO_SMS_NUMBER,
            'text' => $message_subject." ".$message_body
    ]);
	return $url;
}

function process_queue_message($message) {
	extract($message);
	syslog(LOG_NOTICE, "status:received to:$to message_subject:\"$message_subject\"");

	$url = smszender_nexmo_url($to, $message_subject, $message_body);

	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$response = curl_exec($ch);

	$result = json_decode($response, true);
	// Result: object(stdClass)#1 (2) { ["foo"]=> string(3) "bar" ["cool"]=> string(4) "attr" }

	// a response is almost always for a single message, but let the response tell us how many
	$counter = $result['message-count'] ;
	$ii = 0;
	while ( $ii < $counter ) {
          extract($result['messages'][$ii]);
          syslog(LOG_NOTICE, "carrier:nexmo to:".$result[messages][$ii]['to']
                ." message-id:".$result[messages][$ii]['message-id']
                ." status:".$result[messages][$ii]['status']
                ." balance:".$result[messages][$ii]['remaining-balance']
                ." price:".$result[messages][$ii]['message-price']
                ." network:".$result[messages][$ii]['network']);
	/* 
#warning fixme
	*/
	$cmd = "/usr/local/bin/zabbix_sender -c /usr/local/etc/zabbix24/zabbix_agentd.conf -k net.ptudor.sms.nexmo.balance -o " . $result[messages][$ii]['remaining-balance'];
	$cmd = escapeshellarg($cmd);
	$resultA= system($cmd, $retval);
          $ii++;
	}

	$incrstats = increment_stats(); 
	$getstats = syslog_stats(get_stats());

	return 0;
}

function syslog_stats($getstats) {

	/*
	Array
	(
	    [cumulative] => 8
	    [stats_hour] => 
	    [stats_day] => 
	    [stats_month] => 8
	    [stats_total] => 
	)
	*/

	// set to zero if empty
	foreach ($getstats as $key => &$value) {
	   if ( empty($value) ) $value = '0';
	}
	unset ($value);

	extract($getstats);
	syslog(LOG_NOTICE, "stats_hour:$stats_hour stats_day:$stats_day stats_month:$stats_month stats_total:$stats_total cumulative:$cumulative");
	return 0;
}


function getStatsKeyHour() {
	return sha256(date('Y:M:z:H')); // 2016-feb-47-09
	}
function getStatsKeyDay() {
	return sha256(date('Y:M:z')); // 2016-feb-47
	}
function getStatsKeyMonth() {
	return sha256(date('Y:M')); // 2016-feb
	}
function getStatsKeyTotal() {
	global $start_time;
	return sha256($start_time);
	}
function increment_stats() {
	global $r;
	$localStatsKeyHour = getStatsKeyHour();
	$localStatsKeyDay = getStatsKeyDay();
	$localStatsKeyMonth = getStatsKeyMonth();
	$localStatsKeyTotal = getStatsKeyTotal();
	//echo $localStatsKeyDay;
		$r->incr($localStatsKeyDay);
		$r->incr($localStatsKeyHour);
		$r->incr($localStatsKeyMonth);
		$r->incr($localStatsKeyTotal);
	// be kind and expire old data after a week
		$r->expire($localStatsKeyHour, 604800);
		$r->expire($localStatsKeyDay, 604800);
		$r->expire($localStatsKeyMonth, 604800);
		$r->expire($localStatsKeyTotal, 604800);
 	$r->incr(sha256("smszender_dequeued"));
	return 0;
}

function get_stats() {
	global $r;
	return array (
 		"cumulative"  => $r->get(sha256("smszender_dequeued")),
		"stats_hour"  => $r->get(getStatsKeyHour()),
		"stats_day"   => $r->get(getStatsKeyDay()),
		"stats_month" => $r->get(getStatsKeyMonth()),
		"stats_total" => $r->get(getStatsKeyTotal()),
	); 
}

printf("smszender-transmit connected. https://github.com/ptudor/smszender-zabbix  (^C to exit)%s", PHP_EOL);
while($running){
	sleep(10);

	if ( mt_rand(0,STATS_INTEGER) == 1 ) {
		syslog_stats(get_stats());
	}

   try {
	// pop the message ID from the queue, or block until one is available...
	$message_id = $r->brpop("queue:zbx_sms",3);
	//print_r($message_id);
	}
    catch (Exception $e) {
	usleep(1);
	continue;
	}
	
	// Get the message itself...
	$message = $r->hgetall("zbx_message:".$message_id[1]);
	//print_r($message);
	
	if (count($message) == 0)
		continue;
		
	// Process the message...
	process_queue_message($message);

	// If you need, delete the key...
	$r->del("zbx_message:$message_id[1]");

}

//  Do here all the cleanup that needs to be done
printf("smszender-transmit finished%s", PHP_EOL);

?>
