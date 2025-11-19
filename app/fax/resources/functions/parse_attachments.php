<?php

/**
 * Parse attachments from a given email message.
 *
 * @param imap connection object $connection The IMAP connection to use for fetching the email message.
 * @param int    $message_number The number of the email message to parse attachments from.
 * @param string $option         Optional flag to pass to the imap_fetchstructure function.
 *
 * @return array An array of attachment details, where each element is an associative array containing
 *               'filename', 'name', and 'attachment' keys. The return value will be a reindexed array,
 *               with keys starting from 0.
 */
function parse_attachments($connection, $message_number, $option = '') {
	$attachments = array();
	$structure = imap_fetchstructure($connection, $message_number, $option);

	if(isset($structure->parts) && count($structure->parts)) {

		for($i = 0; $i < count($structure->parts); $i++) {

			if($structure->parts[$i]->ifdparameters) {
				foreach($structure->parts[$i]->dparameters as $object) {
					if(strtolower($object->attribute) == 'filename') {
						$attachments[$i]['is_attachment'] = true;
						$attachments[$i]['filename'] = $object->value;
					}
				}
			}

			if($structure->parts[$i]->ifparameters) {
				foreach($structure->parts[$i]->parameters as $object) {
					if(strtolower($object->attribute) == 'name') {
						$attachments[$i]['is_attachment'] = true;
						$attachments[$i]['name'] = $object->value;
					}
				}
			}

			if($attachments[$i]['is_attachment']) {
				$attachments[$i]['attachment'] = imap_fetchbody($connection, $message_number, $i+1, $option);
				if($structure->parts[$i]->encoding == 3) { // 3 = BASE64
					$attachments[$i]['attachment'] = base64_decode($attachments[$i]['attachment']);
					$attachments[$i]['size'] = strlen($attachments[$i]['attachment']);
				}
				elseif($structure->parts[$i]->encoding == 4) { // 4 = QUOTED-PRINTABLE
					$attachments[$i]['attachment'] = quoted_printable_decode($attachments[$i]['attachment']);
					$attachments[$i]['size'] = strlen($attachments[$i]['attachment']);
				}
			}

			unset($attachments[$i]['is_attachment']);
		}

	}
	return array_values($attachments); //reindex
}

?>
