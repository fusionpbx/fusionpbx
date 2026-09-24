<?php
require_once dirname(__DIR__).'/resources/classes/recording_segment_access.php';

function link_assert($value, $message) { if (!$value) throw new Exception($message); }
function is_uuid($value) { return is_string($value) && preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $value); }

class link_database {
	public $queries=[];
	public $rows=[];
	public function select($sql,$params,$mode) { $this->queries[]=[$sql,$params,$mode]; return $this->rows; }
}

// The focused migration supports two append-only links and rejects a replay.
$pdo = new PDO('sqlite::memory:');
$migration = file_get_contents(dirname(__DIR__).'/resources/database/migrations/20260921_conference_recording_links_up.sql');
$pdo->exec($migration);
$sql = "insert into v_recording_segment_links
	(recording_segment_link_uuid,domain_uuid,conversation_id,xml_cdr_uuid,conference_uuid,recording_path,recording_kind)
	values (:link_uuid,:domain_uuid,:conversation_id,:xml_cdr_uuid,:conference_uuid,:recording_path,:recording_kind)
	on conflict do nothing";
$statement = $pdo->prepare($sql);
$common = [
	'domain_uuid'=>'00000000-0000-0000-0000-000000000001',
	'conversation_id'=>'00000000-0000-0000-0000-000000000002',
	'xml_cdr_uuid'=>'00000000-0000-0000-0000-000000000002',
	'conference_uuid'=>'00000000-0000-0000-0000-000000000003'
];
$preconference = $common+['link_uuid'=>'00000000-0000-0000-0000-000000000004','recording_path'=>'/recordings/participant.wav','recording_kind'=>'preconference'];
$conference = $common+['link_uuid'=>'00000000-0000-0000-0000-000000000005','recording_path'=>'/recordings/conference.wav','recording_kind'=>'conference'];
link_assert($statement->execute($preconference) && $statement->execute($conference), 'both recording links persist');
$replay = $preconference;
$replay['link_uuid'] = '00000000-0000-0000-0000-000000000006';
link_assert($statement->execute($replay), 'idempotent replay is accepted');
link_assert((int)$pdo->query('select count(*) from v_recording_segment_links')->fetchColumn() === 2, 'idempotency index prevents duplicate links');
$kinds = $pdo->query('select recording_kind from v_recording_segment_links order by recording_kind')->fetchAll(PDO::FETCH_COLUMN);
link_assert($kinds === ['conference','preconference'], 'earlier and shared conference recordings remain separate');

// Call history is domain-scoped, and playback resolves a stored path only
// through an owning CDR. The browser never supplies a filesystem path.
$database = new link_database;
$access = new recording_segment_access($database);
$access->for_cdr($common['xml_cdr_uuid'],$common['domain_uuid'],false);
link_assert(strpos($database->queries[0][0],'domain_uuid=:domain_uuid') !== false, 'normal call-history lookup is domain scoped');
$access->for_cdr($common['xml_cdr_uuid'],$common['domain_uuid'],true);
link_assert(strpos($database->queries[1][0],'domain_uuid=:domain_uuid') === false, 'cross-domain lookup requires an explicit authorized flag');
$access->path($preconference['link_uuid'],$common['domain_uuid'],false);
link_assert(strpos($database->queries[2][0],'inner join v_xml_cdr') !== false, 'playback requires an owning call-history row');
$download = file_get_contents(dirname(__DIR__).'/segment_download.php');
link_assert(strpos($download, "permission_exists('xml_cdr_recording_play')") !== false
	&& strpos($download, "permission_exists('xml_cdr_recording_download')") !== false,
	'playback and download repeat the existing CDR permissions');
link_assert(strpos($download, "\$_GET['path']") === false, 'download never accepts a browser-supplied path');

echo "recording_conference_segment_links_test: OK (disposable SQLite and source authorization checks; no live FreeSWITCH)\n";
