<?php

/*
* Filename.......: class_vcard.php
* Author.........: Troy Wolf [troy@troywolf.com]
* Last Modified..: 2005/07/14 13:30:00
* Description....: A class to generate vCards for contact data.
*/

/**
 * A class to generate vCards for contact data.
 *
 * @author Troy Wolf [troy@troywolf.com]
 */
class vcard {
	var $log;
	var $data;  //array of this vcard's contact data
	var $filename; //filename for download file naming
	var $class; //PUBLIC, PRIVATE, CONFIDENTIAL
	var $revision_date;
	var $card;

	/**
	 * Constructor for initializing the object.
	 *
	 * @access public
	 * @return bool True on successful initialization.
	 */
	public function __construct() {
		$this->log = "New vcard() called<br />";
		$this->data = [
			"display_name" => null
			, "first_name" => null
			, "last_name" => null
			, "additional_name" => null
			, "name_prefix" => null
			, "name_suffix" => null
			, "nickname" => null
			, "title" => null
			, "role" => null
			, "department" => null
			, "company" => null
			, "work_po_box" => null
			, "work_extended_address" => null
			, "work_address" => null
			, "work_city" => null
			, "work_state" => null
			, "work_postal_code" => null
			, "work_country" => null
			, "home_po_box" => null
			, "home_extended_address" => null
			, "home_address" => null
			, "home_city" => null
			, "home_state" => null
			, "home_postal_code" => null
			, "home_country" => null
			, "voice_tel" => null
			, "work_tel" => null
			, "home_tel" => null
			, "cell_tel" => null
			, "fax_tel" => null
			, "pager_tel" => null
			, "email1" => null
			, "email2" => null
			, "url" => null
			, "photo" => null
			, "birthday" => null
			, "timezone" => null
			, "sort_string" => null
			, "note" => null,
		];
		return true;
	}

	/*
	build() method checks all the values, builds appropriate defaults for
	missing values, generates the vcard data string.
	*/

	/**
	 * Escapes a single TEXT value for use in a vCard property (RFC 6350 section 3.3.11).
	 *
	 * Backslashes, semicolons and commas are escaped with a preceding backslash and any
	 * carriage return / line feed is encoded as the literal "\n" sequence. This keeps
	 * user supplied data inside its own property value: it cannot terminate the value,
	 * start a new component of a structured value, or inject additional vCard lines.
	 *
	 * @access public
	 * @param mixed $value The raw value (null is treated as an empty string).
	 * @return string The escaped value, safe to embed in a vCard line.
	 */
	public static function escape_text($value): string {
		$value = (string) ($value ?? '');
		if ($value === '') {
			return '';
		}

		//remove null bytes and control characters other than tab, carriage return and line feed.
		//byte oriented (no /u): these classes only match ASCII bytes, which never occur inside a
		//valid UTF-8 multibyte sequence, so invalid UTF-8 input cannot make this return null.
		$value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $value) ?? '';

		//normalize line endings so a lone CR cannot be mistaken for a line break
		$value = str_replace(["\r\n", "\r"], "\n", $value);

		//escape the backslash first, then the structural delimiters
		$value = str_replace('\\', '\\\\', $value);
		$value = str_replace(';', '\\;', $value);
		$value = str_replace(',', '\\,', $value);

		//encode line breaks as the escaped "\n" sequence (no raw CR/LF left in the line)
		$value = str_replace("\n", '\\n', $value);

		return $value;
	}

	/**
	 * Sanitizes a value that is not of the TEXT type (URI, phone number, date, ...).
	 *
	 * These values are not comma/semicolon escaped by the specification, but they must
	 * never contain line breaks or control characters because those would allow vCard
	 * property injection.
	 *
	 * @access public
	 * @param mixed $value The raw value (null is treated as an empty string).
	 * @return string The sanitized value, safe to embed in a vCard line.
	 */
	public static function sanitize_line($value): string {
		$value = (string) ($value ?? '');
		if ($value === '') {
			return '';
		}

		//remove null bytes, control characters and any line break (byte oriented; see escape_text)
		$value = preg_replace('/[\x00-\x1F\x7F]/', '', $value) ?? '';

		return $value;
	}

	/**
	 * Downloads the vCard data as a file.
	 *
	 * @access public
	 * @return bool True on successful download, false otherwise.
	 */
	function download() {
		$this->log .= "vcard download() called<br />";
		if (!$this->card) {
			$this->build();
		}
		if (!$this->filename) {
			$this->filename = trim($this->data['display_name']);
		}

		//replace spaces with underscores in the filename
		$this->filename = str_replace(" ", "_", $this->filename);

		//sanitize the filename to prevent header injection
		$this->filename = preg_replace('/[^A-Za-z0-9._-]/', '_', $this->filename);
		if ($this->filename === false || $this->filename === '' || $this->filename === '.' || $this->filename === '..' || $this->filename[0] === '.') {
			$this->filename = 'vcard';
		}

		//send the headers and output the vcard data
		header("Content-type: text/directory");
		header("Content-Disposition: attachment; filename=" . $this->filename . ".vcf");
		header("Pragma: public");
		echo $this->card;
		return true;
	}

	/*
	download() method streams the vcard to the browser client.
	*/

	/**
	 * Builds the vCard.
	 *
	 * All contact values are escaped before being concatenated into the card so that
	 * stored data cannot break out of its property (vCard injection) and, downstream,
	 * cannot inject markup when the card is embedded in a page.
	 *
	 * @access public
	 * @return string The built vCard as a string.
	 */
	function build() {
		$this->log .= "vcard build() called<br />";
		/*
		For many of the values, if they are not passed in, we set defaults or
		build them based on other values.
		*/
		if (!$this->class) {
			$this->class = "PUBLIC";
		}
		if (!$this->data['display_name']) {
			$this->data['display_name'] = trim($this->data['first_name'] . " " . $this->data['last_name']);
		}
		if (!$this->data['sort_string']) {
			$this->data['sort_string'] = $this->data['last_name'];
		}
		if (!$this->data['sort_string']) {
			$this->data['sort_string'] = $this->data['company'];
		}
		if (!$this->data['timezone']) {
			$this->data['timezone'] = date("O");
		}
		if (!$this->revision_date) {
			$this->revision_date = date('Y-m-d H:i:s');
		}

		//the time zone is echoed as a property value; only accept the offset or identifier forms
		$timezone = self::sanitize_line($this->data['timezone']);
		if (!preg_match('/^[+-]\d{4}$/', $timezone) && !preg_match('#^[A-Za-z][A-Za-z0-9_+/-]*$#', $timezone)) {
			$timezone = date("O");
		}

		$this->card = "BEGIN:VCARD\r\n";
		$this->card .= "VERSION:3.0\r\n";
		//$this->card .= "CLASS:".$this->class."\r\n";
		//$this->card .= "PRODID:-//class_vcard from TroyWolf.com//NONSGML Version 1//EN\r\n";
//		$this->card .= "REV:".$this->revision_date."\r\n";
		$this->card .= "FN:" . self::escape_text($this->data['display_name']) . "\r\n";
		$this->card .= "N:";
		$this->card .= self::escape_text($this->data['last_name']) . ";";
		$this->card .= self::escape_text($this->data['first_name']);
		if (!empty($this->data['additional_name'])) {
			$this->card .= ";" . self::escape_text($this->data['additional_name']);
		}
		if (!empty($this->data['name_prefix'])) {
			$this->card .= ";" . self::escape_text($this->data['name_prefix']);
		}
		if (!empty($this->data['name_suffix'])) {
			$this->card .= ";" . self::escape_text($this->data['name_suffix']);
		}
		$this->card .= "\r\n";
		if ($this->data['nickname']) {
			$this->card .= "NICKNAME:" . self::escape_text($this->data['contact_nickname'] ?? $this->data['nickname']) . "\r\n";
		}
		if ($this->data['title']) {
			$this->card .= "TITLE:" . self::escape_text($this->data['contact_title'] ?? $this->data['title']) . "\r\n";
		}
		if ($this->data['company']) {
			$this->card .= "ORG:" . self::escape_text($this->data['company']);
		}
		if ($this->data['department']) {
			$this->card .= ";" . self::escape_text($this->data['department']);
		}
		$this->card .= "\r\n";

		$vcard_address_type_values = ['work', 'home', 'dom', 'intl', 'postal', 'parcel', 'pref'];
		foreach ($vcard_address_type_values as $vcard_address_type_value) {
			if (!empty($this->data[$vcard_address_type_value . '_po_box'])
				|| !empty($this->data[$vcard_address_type_value . '_extended_address'])
				|| !empty($this->data[$vcard_address_type_value . '_address'])
				|| !empty($this->data[$vcard_address_type_value . '_city'])
				|| !empty($this->data[$vcard_address_type_value . '_state'])
				|| !empty($this->data[$vcard_address_type_value . '_postal_code'])
				|| !empty($this->data[$vcard_address_type_value . '_country'])) {
				$this->card .= "ADR;TYPE=" . $vcard_address_type_value . ":";
				if (!empty($this->data[$vcard_address_type_value . '_po_box'])) {
					$this->card .= self::escape_text($this->data[$vcard_address_type_value . '_po_box']) . ";";
				}
				if (!empty($this->data[$vcard_address_type_value . '_extended_address'])) {
					$this->card .= self::escape_text($this->data[$vcard_address_type_value . '_extended_address']) . ";";
				}
				if (!empty($this->data[$vcard_address_type_value . '_address'])) {
					$this->card .= self::escape_text($this->data[$vcard_address_type_value . '_address']) . ";";
				}
				if (!empty($this->data[$vcard_address_type_value . '_city'])) {
					$this->card .= self::escape_text($this->data[$vcard_address_type_value . '_city']) . ";";
				}
				if (!empty($this->data[$vcard_address_type_value . '_state'])) {
					$this->card .= self::escape_text($this->data[$vcard_address_type_value . '_state']) . ";";
				}
				if (!empty($this->data[$vcard_address_type_value . '_postal_code'])) {
					$this->card .= self::escape_text($this->data[$vcard_address_type_value . '_postal_code']) . ";";
				}
				if (!empty($this->data[$vcard_address_type_value . '_country'])) {
					$this->card .= self::escape_text($this->data[$vcard_address_type_value . '_country']) . "";
				}
				$this->card .= "\r\n";
			}
		}

		if ($this->data['email1']) {
			$this->card .= "EMAIL;PREF=1:" . self::sanitize_line($this->data['email1']) . "\r\n";
		}
		if ($this->data['email2']) {
			$this->card .= "EMAIL;PREF=2:" . self::sanitize_line($this->data['email2']) . "\r\n";
		}
		if ($this->data['voice_tel']) {
			$this->card .= "TEL;TYPE=voice:" . self::sanitize_line($this->data['voice_tel']) . "\r\n";
		}
		if ($this->data['work_tel']) {
			$this->card .= "TEL;TYPE=work:" . self::sanitize_line($this->data['work_tel']) . "\r\n";
		}
		if ($this->data['home_tel']) {
			$this->card .= "TEL;TYPE=home:" . self::sanitize_line($this->data['home_tel']) . "\r\n";
		}
		if ($this->data['cell_tel']) {
			$this->card .= "TEL;TYPE=cell:" . self::sanitize_line($this->data['cell_tel']) . "\r\n";
		}
		if ($this->data['fax_tel']) {
			$this->card .= "TEL;TYPE=fax:" . self::sanitize_line($this->data['fax_tel']) . "\r\n";
		}
		if ($this->data['pager_tel']) {
			$this->card .= "TEL;TYPE=pager:" . self::sanitize_line($this->data['pager_tel']) . "\r\n";
		}
		if ($this->data['url']) {
			$this->card .= "URL:" . self::sanitize_line($this->data['url']) . "\r\n";
		}
		if ($this->data['birthday']) {
			$this->card .= "BDAY:" . self::sanitize_line($this->data['birthday']) . "\r\n";
		}
		if ($this->data['role']) {
			$this->card .= "ROLE:" . self::escape_text($this->data['contact_role'] ?? $this->data['role']) . "\r\n";
		}
		if ($this->data['note']) {
			$this->card .= "NOTE:" . self::escape_text($this->data['contact_note'] ?? $this->data['note']) . "\r\n";
		}
		$this->card .= "TZ:" . $timezone . "\r\n";
		$this->card .= "END:VCARD";
	}
}
