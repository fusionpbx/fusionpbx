<?php
/*
	FusionPBX
	Version: MPL 1.1

	The contents of this file are subject to the Mozilla Public License Version
	1.1 (the "License"); you may not use this file except in compliance with
	the License. You may obtain a copy of the License at
	http://www.mozilla.org/MPL/

	Software distributed under the License is distributed on an "AS IS" basis,
	WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
	for the specific language governing rights and limitations under the
	License.

	The Original Code is FusionPBX

	The Initial Developer of the Original Code is
	Mark J Crane <markjcrane@fusionpbx.com>
	Portions created by the Initial Developer are Copyright (C) 2016-2026
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//Backfill the v_xml_cdr_extensions table for call detail records that are
//missing extension rows. The extension participation is re-derived from the
//call flow stored in v_xml_cdr_flow (or, for older records that predate the
//flow table, from v_xml_cdr_json) using the same logic as the live import.
//
//The job runs in a continuous loop, one batch at a time, until either no
//missing call detail records remain (completed) or the process receives
//SIGINT or SIGTERM (cancelled; the in-flight batch is committed first so a
//half-written batch is never left behind).
//
//The job is idempotent: a call detail record that already has extension rows
//is never touched, and a re-run after completion reports zero missing records.
//
//Usage:
//  php xml_cdr_extension_backfill.php [options]
//
//  --days N           only process calls that started within the last N days
//  --start-stamp S    only process calls that started on or after S
//                     (YYYY-MM-DD or YYYY-MM-DD HH:MM:SS)
//  --domain NAME      only process calls of the domain with this name
//  --chunk-size N     number of calls per batch (default 500)
//  --dry-run          report what would be inserted without writing anything
//
//Example:
//  nohup php /var/www/fusionpbx/app/xml_cdr/resources/jobs/xml_cdr_extension_backfill.php --days 30 > /tmp/extension_backfill.log 2>&1 &

//check the permission (CLI only)
if (!defined('STDIN')) {
	exit;
}

//includes files
require_once dirname(__DIR__, 4) . "/resources/require.php";
require_once dirname(__DIR__) . "/classes/xml_cdr.php";

//increase limits, the job runs until the backfill is complete
set_time_limit(0);
ini_set('max_execution_time', 0);
ini_set('memory_limit', '512M');

//globals
global $database, $settings;

//usage text for the help option
$usage = "Usage: php xml_cdr_extension_backfill.php [options]\n"
	. "  --days N           only process calls that started within the last N days\n"
	. "  --start-stamp S    only process calls that started on or after S (YYYY-MM-DD or YYYY-MM-DD HH:MM:SS)\n"
	. "  --domain NAME      only process calls of the domain with this name\n"
	. "  --chunk-size N     number of calls per batch (default 500)\n"
	. "  --dry-run          report what would be inserted without writing anything\n"
	. "  -h, --help         show this help\n";

//parse the options
$options = array(
	'chunk_size'  => 500,
	'days'        => null,
	'start_stamp' => '',
	'domain'      => '',
	'dry_run'     => false,
);
for ($i = 1; $i < count($argv); $i++) {
	switch ($argv[$i]) {
		case '--days':
			$options['days'] = isset($argv[++$i]) ? max(1, (int)$argv[$i]) : 1;
			break;
		case '--start-stamp':
			$options['start_stamp'] = $argv[++$i] ?? '';
			break;
		case '--domain':
			$options['domain'] = $argv[++$i] ?? '';
			break;
		case '--chunk-size':
			$options['chunk_size'] = max(1, (int)($argv[++$i] ?? 500));
			break;
		case '--dry-run':
			$options['dry_run'] = true;
			break;
		case '-h':
		case '--help':
			echo $usage;
			exit(0);
		default:
			echo "unknown option: {$argv[$i]}\n\n" . $usage;
			exit(1);
	}
}

//resolve the start epoch from the options (the epoch is timezone independent)
$start_epoch = null;
if (!empty($options['days'])) {
	$start_epoch = time() - ($options['days'] * 86400);
} elseif ($options['start_stamp'] !== '') {
	$start_epoch = strtotime($options['start_stamp']);
	if ($start_epoch === false) {
		echo "xml_cdr_extension_backfill: invalid --start-stamp value {$options['start_stamp']}\n";
		exit(1);
	}
}

//insert_user for the new rows (null when there is no session user in cli)
$insert_user = $database->user_uuid ?? null;

//single instance lock, prevents two backfill jobs from double inserting
$pid_dir = '/var/run/fusionpbx/xml_cdr';
if (!is_dir($pid_dir)) {
	@mkdir($pid_dir, 0755, true);
}
//a job without its pid lock could run twice at the same time, fail loudly
if (!is_dir($pid_dir) || !is_writable($pid_dir)) {
	echo "xml_cdr_extension_backfill: pid directory {$pid_dir} is missing or not writable (run: mkdir -p {$pid_dir} && chown <service user> {$pid_dir})\n";
	exit(1);
}
$pid_file = $pid_dir . '/extension_backfill.pid';
if (function_exists('posix_kill') && file_exists($pid_file)) {
	$pid = (int)trim((string)@file_get_contents($pid_file));
	if ($pid > 0 && $pid !== getmypid() && @posix_kill($pid, 0) === true) {
		echo "xml_cdr_extension_backfill: already running (pid {$pid}), exiting\n";
		exit(0);
	}
}
//remove a stale pid file left by a dead process
@unlink($pid_file);
if (false === @file_put_contents($pid_file, (string)getmypid())) {
	echo "xml_cdr_extension_backfill: unable to write pid file {$pid_file}\n";
	exit(1);
}
//make sure the pid file is removed on exit when it still holds our pid
register_shutdown_function(function () use ($pid_file) {
	if (file_exists($pid_file)) {
		if ((int)trim((string)@file_get_contents($pid_file)) === getmypid()) {
			@unlink($pid_file);
		}
	}
});

//signal handlers for a graceful cancellation, the flag is checked between
//batches so the in-flight batch is always committed before the job stops
$shutdown_requested = false;
if (function_exists('pcntl_signal')) {
	if (function_exists('pcntl_async_signals')) {
		pcntl_async_signals(true);
	}
	$signal_handler = function () use (&$shutdown_requested) {
		$shutdown_requested = true;
	};
	pcntl_signal(SIGINT, $signal_handler);
	pcntl_signal(SIGTERM, $signal_handler);
} else {
	echo "xml_cdr_extension_backfill: warning, pcntl is not available, SIGINT/SIGTERM will not stop the job gracefully\n";
}

//one xml_cdr instance for the whole run, the domain and extension maps it
//caches in memory are reused by every batch so the per-domain destination
//lookup is only re-queried by the class after its cache lifetime
$xml_cdr = new xml_cdr(array(
	'database'    => $database,
	'settings'    => $settings,
	'domain_uuid' => '',
));

//set the error mode on the shared connection
if ($database->db) {
	$database->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
}

//build the batch query: the keyset cursor (xml_cdr_uuid > last uuid) paginates
//without an offset, the not exists anti-join is served by the foreign key
//index on v_xml_cdr_extensions.xml_cdr_uuid, and the call flow and json
//payloads are fetched in the same query so no extra round trip is needed
//per call
$sql = "select c.xml_cdr_uuid, c.domain_uuid, c.end_epoch, f.call_flow, j.json ";
$sql .= "from v_xml_cdr as c ";
$sql .= "left join v_xml_cdr_flow as f on f.xml_cdr_uuid = c.xml_cdr_uuid ";
$sql .= "left join v_xml_cdr_json as j on j.xml_cdr_uuid = c.xml_cdr_uuid ";
$sql .= "where c.xml_cdr_uuid > :last_uuid ";
$sql .= "and not exists (select 1 from v_xml_cdr_extensions as x where x.xml_cdr_uuid = c.xml_cdr_uuid) ";
$parameters = array();
if ($start_epoch !== null) {
	$sql .= "and c.start_epoch >= :start_epoch ";
	$parameters['start_epoch'] = $start_epoch;
}
if ($options['domain'] !== '') {
	$sql .= "and c.domain_name = :domain_name ";
	$parameters['domain_name'] = $options['domain'];
}
$sql .= "order by c.xml_cdr_uuid ";
$sql .= "limit " . $options['chunk_size'] . " ";

//insert the extension rows of a batch in one transaction using a single
//multi-row prepared statement
function insert_extension_rows(array $rows, $database, $insert_user) {

	//build the values of the multi-row insert
	$values = array();
	$parameters = array();
	foreach ($rows as $row) {
		$values[] = "(?, ?, ?, ?, ?, ?, ?, now(), ?)";
		$parameters[] = $row['xml_cdr_extension_uuid'] ?? null;
		$parameters[] = $row['domain_uuid'] ?? null;
		$parameters[] = $row['xml_cdr_uuid'] ?? null;
		$parameters[] = $row['extension_uuid'] ?? null;
		$parameters[] = $row['start_stamp'] ?? null;
		$parameters[] = $row['end_stamp'] ?? null;
		$parameters[] = $row['duration'] ?? 0;
		$parameters[] = $insert_user;
	}

	//insert the rows in one transaction
	$db = $database->db;
	$db->beginTransaction();
	try {
		$prep_statement = $db->prepare("insert into v_xml_cdr_extensions "
			. "(xml_cdr_extension_uuid, domain_uuid, xml_cdr_uuid, extension_uuid, "
			. "start_stamp, end_stamp, duration, insert_date, insert_user) values "
			. implode(", ", $values));
		$prep_statement->execute($parameters);
		$db->commit();
	} catch (Exception $e) {
		if ($db->inTransaction()) {
			$db->rollback();
		}
		echo "xml_cdr_extension_backfill: insert failed: " . $e->getMessage() . "\n";
		return false;
	}
	return true;
}

//set the counters
$counters = array(
	'batches'  => 0,
	'calls'    => 0,
	'rows'     => 0,
	'no_flow'  => 0,
	'no_match' => 0,
	'skipped'  => 0,
);
$start_time    = time();
$last_uuid     = '00000000-0000-0000-0000-000000000000';
$retry_pending = false;

echo "xml_cdr_extension_backfill: starting" . ($options['dry_run'] ? " (dry run)" : "") . "\n";
echo "  chunk size: {$options['chunk_size']}\n";
if ($start_epoch !== null) {
	echo "  start filter: " . date('Y-m-d H:i:s', $start_epoch) . "\n";
}
if ($options['domain'] !== '') {
	echo "  domain: {$options['domain']}\n";
}

//continuous loop, one batch at a time, until the work is complete or a
//shutdown signal was received
while (!$shutdown_requested) {

	//fetch the next batch of the missing calls
	$parameters['last_uuid'] = $last_uuid;
	$batch = $database->select($sql, $parameters, 'all');
	if (!is_array($batch)) {
		echo "xml_cdr_extension_backfill: batch query failed: " . ($database->message['message'] ?? '') . "\n";
		break;
	}
	if (empty($batch)) {
		//no missing calls left, the backfill is complete
		break;
	}

	$counters['batches']++;
	$counters['calls'] += count($batch);

	//build the extension rows for each call of the batch
	$batch_rows = array();
	foreach ($batch as $row) {

		//get the call flow array, from the stored flow or, for older records
		//that predate the flow table, re-derived from the stored json with
		//the same method the import uses
		$call_flow_array = null;
		if (!empty($row['call_flow'])) {
			$call_flow_array = is_string($row['call_flow']) ? json_decode($row['call_flow'], true) : $row['call_flow'];
		}
		if (!is_array($call_flow_array) || empty($call_flow_array)) {
			if (!empty($row['json'])) {
				$json_array = json_decode($row['json'], true);
				if (is_array($json_array) && !empty($json_array['callflow']) && is_array($json_array['callflow'])) {
					//fill in the end time from the cdr when the json does not have it
					if (empty($json_array['variables'])) {
						$json_array['variables'] = array();
					}
					if (empty($json_array['variables']['end_uepoch']) && !empty($row['end_epoch'])) {
						$json_array['variables']['end_uepoch'] = (int)$row['end_epoch'] * 1000000;
					}
					$xml_cdr->call_details = $json_array;
					$call_flow_array = $xml_cdr->call_flow();
				}
			}
		}
		if (!is_array($call_flow_array) || empty($call_flow_array)) {
			//neither a stored flow nor a json with a call flow, nothing to derive
			$counters['no_flow']++;
			continue;
		}

		//resolve the extension rows with the same logic as the live import
		$rows = $xml_cdr->backfill_extensions($row['xml_cdr_uuid'], $row['domain_uuid'], $call_flow_array);
		if (empty($rows)) {
			//no registered extension took part in this call (eg. trunk to trunk)
			$counters['no_match']++;
			continue;
		}
		foreach ($rows as $extension_row) {
			$batch_rows[] = $extension_row;
		}
	}

	//insert the rows of the batch in one transaction
	if (!empty($batch_rows)) {
		if ($options['dry_run']) {
			$counters['rows'] += count($batch_rows);
		} elseif (insert_extension_rows($batch_rows, $database, $insert_user)) {
			$counters['rows'] += count($batch_rows);
		} elseif ($retry_pending) {
			//the same batch failed twice, skip it and keep going
			$counters['skipped'] += count($batch);
			$retry_pending = false;
			echo "xml_cdr_extension_backfill: skipping batch of " . count($batch) . " calls after a repeated insert failure\n";
		} else {
			//retry the same batch once, the failed calls are still missing
			$retry_pending = true;
			echo "xml_cdr_extension_backfill: retrying the batch\n";
			continue;
		}
	}

	//advance the keyset cursor past this batch, the inserted calls drop out
	//of the missing set so the cursor never visits them again, and the calls
	//that produced no rows are not re-processed on later runs of this job
	$last_uuid = $batch[count($batch) - 1]['xml_cdr_uuid'];
	$retry_pending = false;

	//log the progress of the batch
	$elapsed = max(1, time() - $start_time);
	printf("xml_cdr_extension_backfill: batch %d, %d calls processed, %d extension rows, %d calls/s\n",
		$counters['batches'], $counters['calls'], $counters['rows'], (int)($counters['calls'] / $elapsed));
}

//print the summary of the run
$elapsed = max(1, time() - $start_time);
if ($shutdown_requested) {
	echo "xml_cdr_extension_backfill: cancelled after " . $elapsed . "s\n";
	$exit_code = 130;
} else {
	echo "xml_cdr_extension_backfill: completed after " . $elapsed . "s\n";
	$exit_code = 0;
}
echo "  batches:    {$counters['batches']}\n";
echo "  calls:      {$counters['calls']}\n";
echo "  extension rows" . ($options['dry_run'] ? ' (would be inserted)' : ' inserted') . ": {$counters['rows']}\n";
echo "  no call flow:     {$counters['no_flow']}\n";
echo "  no extension match: {$counters['no_match']}\n";
echo "  skipped:    {$counters['skipped']}\n";

//count the calls that are still missing extension rows in the same scope
$parameters = array();
$sql = "select count(*) as count from v_xml_cdr as c ";
$sql .= "where not exists (select 1 from v_xml_cdr_extensions as x where x.xml_cdr_uuid = c.xml_cdr_uuid) ";
if ($start_epoch !== null) {
	$sql .= "and c.start_epoch >= :start_epoch ";
	$parameters['start_epoch'] = $start_epoch;
}
if ($options['domain'] !== '') {
	$sql .= "and c.domain_name = :domain_name ";
	$parameters['domain_name'] = $options['domain'];
}
$row = $database->select($sql, $parameters, 'row');
echo "  still missing: " . (is_array($row) ? $row['count'] : 'unknown') . "\n";

exit($exit_code);




