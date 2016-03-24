<?php
// required configuration values. Both global, redis, and specific, carrier authentication tokens

// CARRIER is a string used to determine what delivery method to use. 
// choice of: "localhost", "nexmo"
define('CARRIER','localhost');
// everything uses database 0, sandbox ourselves over in seven
define('REDIS_HOST', '127.0.0.1');
define('REDIS_PORT', '6379');
define('REDIS_DB','7');
// just something mixed in to key names. Quickly expire everything by changing a character.
// only actual purpose in this particular script is to reset the statistics to zero.
define('UNIQUE_IDENTIFIER','6b0c35a9-9a33-4df0-a5ef-5ad4172cce88');
// roll a die of this many sides every ten seconds; if it's 1, output syslog stats.
define('STATS_INTEGER','42');

// Carrier Specific Settings
// localhost authentication
define('LOCALHOST_SMS_API_KEY','1b2152ac');
define('LOCALHOST_SMS_API_SECRET','62b680db');
define('LOCALHOST_SMS_NUMBER','13105550001');
// nexmo authentication
define('NEXMO_SMS_API_KEY','1013c1014d');
define('NEXMO_SMS_API_SECRET','80db90e2c0fd');
define('NEXMO_SMS_NUMBER','14245550001');

?>
