<?php
/* Resolve only persisted, domain-authorized segment links; never accept a file path from the browser. */
class recording_segment_access {
	private $database; public function __construct($database){$this->database=$database;}
	public function path($link_uuid,$domain_uuid,$all_domains=false) {
		if (!is_uuid($link_uuid)||!is_uuid($domain_uuid)) return null;
		$sql='select l.recording_path from v_recording_segment_links l inner join v_xml_cdr c on c.xml_cdr_uuid=l.xml_cdr_uuid and c.domain_uuid=l.domain_uuid where l.recording_segment_link_uuid=:link_uuid';$params=['link_uuid'=>$link_uuid];
		if(!$all_domains){$sql.=' and l.domain_uuid=:domain_uuid';$params['domain_uuid']=$domain_uuid;}
		$row=$this->database->select($sql,$params,'row');$path=$row['recording_path']??'';
		return is_string($path)&&$path!==''&&$path[0]==='/'&&!preg_match('/[\r\n\0]/',$path)?$path:null;
	}
	/** Call-history pages use this list; the download endpoint still repeats authorization. */
	public function for_cdr($xml_cdr_uuid,$domain_uuid,$all_domains=false) {
		if (!is_uuid($xml_cdr_uuid)||!is_uuid($domain_uuid)) return [];
		$sql='select recording_segment_link_uuid, recording_path, recording_kind, conference_uuid from v_recording_segment_links where xml_cdr_uuid=:xml_cdr_uuid';
		$params=['xml_cdr_uuid'=>$xml_cdr_uuid];
		if(!$all_domains){$sql.=' and domain_uuid=:domain_uuid';$params['domain_uuid']=$domain_uuid;}
		$sql.=' order by insert_date, recording_segment_link_uuid';
		return $this->database->select($sql,$params,'all') ?: [];
	}
}
