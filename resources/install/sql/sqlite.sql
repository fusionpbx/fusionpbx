--BEGIN TRANSACTION;
CREATE INDEX index_billsec ON v_xml_cdr(billsec ASC);
CREATE INDEX index_caller_id_name ON v_xml_cdr(caller_id_name ASC);
CREATE INDEX index_destination_number ON v_xml_cdr(destination_number ASC);
CREATE INDEX index_duration ON v_xml_cdr(duration ASC);
CREATE INDEX index_hangup_cause ON v_xml_cdr(hangup_cause ASC);
CREATE INDEX index_start_stamp ON v_xml_cdr(start_stamp ASC);
--COMMIT;
