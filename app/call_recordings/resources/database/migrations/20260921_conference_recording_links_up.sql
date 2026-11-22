CREATE TABLE v_recording_segment_links (
 recording_segment_link_uuid uuid PRIMARY KEY,
 domain_uuid uuid NOT NULL,
 conversation_id varchar(255) NOT NULL,
 xml_cdr_uuid uuid NOT NULL,
 conference_uuid varchar(255),
 recording_path text NOT NULL,
 recording_kind varchar(32) NOT NULL,
 insert_date timestamptz NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX recording_segment_links_cdr_domain_idx ON v_recording_segment_links (domain_uuid, xml_cdr_uuid, insert_date);
CREATE INDEX recording_segment_links_conversation_idx ON v_recording_segment_links (domain_uuid, conversation_id);
-- PostgreSQL considers NULL values distinct, so use a sentinel for idempotent links.
CREATE UNIQUE INDEX recording_segment_links_idempotency_idx ON v_recording_segment_links
 (domain_uuid, conversation_id, xml_cdr_uuid, recording_path, recording_kind, COALESCE(conference_uuid, ''));
