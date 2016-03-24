### smszender

This is a two-part alertscript for Zabbix.

A custom script in Zabbix receives three arguments: The destination, the subject, and the message.

The first script receives this information and pushes it into Redis. The second script watches for new messages and delivers them with a one second delay, this necessary delay the initial reason I moved from a simple shell script to a queue.

Both scripts read from a common configuration file. 

At present the only SMS API configured is Nexmo. Add the provider you need...

# Install

clone. cp to alertscripts. create and edit configuration file. 

# test

send a test by hand, watch syslog.

# Add to zabbix

tell zabbix about the script media, configure the user media, configure the action and message.

# syslog

````
Mar 24 05:46:01 itojun smszender-transmit: stats_hour:0 stats_day:6 stats_month:144 stats_total:215 cumulative:233
Mar 24 05:47:32 itojun smszender-enqueue: started:226
Mar 24 05:47:32 itojun smszender-enqueue: status:queuing msg_id:226 to:16195550001 message_subject:"OK apc-sua1400xl"
Mar 24 05:47:32 itojun smszender-transmit: status:received to:16195550001 message_subject:"OK apc-sua1400xl"
Mar 24 05:47:33 itojun smszender-transmit: carrier:nexmo to:16195550001 message-id:02000030C7C4F9D1 status:0 balance:9.16870000 price:0.00570000 network:310004
Mar 24 05:47:33 itojun smszender-transmit: stats_hour:1 stats_day:7 stats_month:145 stats_total:216 cumulative:234
Mar 24 06:00:22 itojun smszender-transmit: stats_hour:0 stats_day:7 stats_month:145 stats_total:216 cumulative:234
Mar 24 06:10:36 itojun smszender-transmit: stats_hour:0 stats_day:7 stats_month:145 stats_total:216 cumulative:234
Mar 24 06:13:00 itojun smszender-transmit: stats_hour:0 stats_day:7 stats_month:145 stats_total:216 cumulative:234
````

# references
control-C from http://zguide.zeromq.org/php:interrupt
json from http://nitschinger.at/Handling-JSON-like-a-boss-in-PHP/
syslog from https://adayinthelifeof.nl/2011/01/12/using-syslog-for-your-php-applications/
nexmo docs https://docs.nexmo.com/quickstarts/SMSAPI
nexmo php curl https://developers.nexmo.com/Quickstarts/sms/send/
redis-queue http://redis4you.com/code.php?id=012

