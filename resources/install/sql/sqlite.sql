--BEGIN TRANSACTION;
INSERT INTO v_settings (numbering_plan, event_socket_ip_address, event_socket_port, event_socket_password, xml_rpc_http_port, xml_rpc_auth_realm, xml_rpc_auth_user, xml_rpc_auth_pass, admin_pin, smtp_host, smtp_secure, smtp_auth, smtp_username, smtp_password, smtp_from, smtp_from_name, mod_shout_decoder, mod_shout_volume) VALUES ('US','127.0.0.1',8021,'ClueCon',8787,'localhost','xmlrpc','7e4d3i',1234,'','none','','','','','Voicemail','',0.3);
CREATE INDEX index_billsec ON v_xml_cdr(billsec ASC);
CREATE INDEX index_caller_id_name ON v_xml_cdr(caller_id_name ASC);
CREATE INDEX index_destination_number ON v_xml_cdr(destination_number ASC);
CREATE INDEX index_duration ON v_xml_cdr(duration ASC);
CREATE INDEX index_hangup_cause ON v_xml_cdr(hangup_cause ASC);
CREATE INDEX index_start_stamp ON v_xml_cdr(start_stamp ASC);
--COMMIT;
