<?php
/*
* Filename.......: class_vcard.php
* Author.........: Troy Wolf [troy@troywolf.com]
* Last Modified..: 2005/07/14 13:30:00
* Description....: A class to generate vCards for contact data.
*/
class vcard {
	var $log;
	var $data;  //array of this vcard's contact data
	var $filename; //filename for download file naming
	var $class; //PUBLIC, PRIVATE, CONFIDENTIAL
	var $revision_date;
	var $card;

	/**
	 * Called when the object is created
	 */
	public function __construct() {
	$this->log = "New vcard() called<br />";
	$this->data = array(
		"display_name"=>null
		,"first_name"=>null
		,"last_name"=>null
		,"additional_name"=>null
		,"name_prefix"=>null
		,"name_suffix"=>null
		,"nickname"=>null
		,"title"=>null
		,"role"=>null
		,"department"=>null
		,"company"=>null
		,"work_po_box"=>null
		,"work_extended_address"=>null
		,"work_address"=>null
		,"work_city"=>null
		,"work_state"=>null
		,"work_postal_code"=>null
		,"work_country"=>null
		,"home_po_box"=>null
		,"home_extended_address"=>null
		,"home_address"=>null
		,"home_city"=>null
		,"home_state"=>null
		,"home_postal_code"=>null
		,"home_country"=>null
		,"voice_tel"=>null
		,"work_tel"=>null
		,"home_tel"=>null
		,"cell_tel"=>null
		,"fax_tel"=>null
		,"pager_tel"=>null
		,"email1"=>null
		,"email2"=>null
		,"url"=>null
		,"photo"=>null
		,"birthday"=>null
		,"timezone"=>null
		,"sort_string"=>null
		,"note"=>null
		);
		return true;
	}

	/**
	 * Called when there are no references to a particular object
	 * unset the variables used in the class
	 */
	public function __destruct() {
		foreach ($this as $key => $value) {
			unset($this->$key);
		}
	}

	/*
	build() method checks all the values, builds appropriate defaults for
	missing values, generates the vcard data string.
	*/
	function build() {
		$this->log .= "vcard build() called<br />";
		/*
		For many of the values, if they are not passed in, we set defaults or
		build them based on other values.
		*/
		if (!$this->class) { $this->class = "PUBLIC"; }
		if (!$this->data['display_name']) {
			$this->data['display_name'] = trim($this->data['first_name']." ".$this->data['last_name']);
		}
		if (!$this->data['sort_string']) { $this->data['sort_string'] = $this->data['last_name']; }
		if (!$this->data['sort_string']) { $this->data['sort_string'] = $this->data['company']; }
		if (!$this->data['timezone']) { $this->data['timezone'] = date("O"); }
		if (!$this->revision_date) { $this->revision_date = date('Y-m-d H:i:s'); }

		$this->card = "BEGIN:VCARD\r\n";
		$this->card .= "VERSION:3.0\r\n";
		//$this->card .= "CLASS:".$this->class."\r\n";
		//$this->card .= "PRODID:-//class_vcard from TroyWolf.com//NONSGML Version 1//EN\r\n";
//		$this->card .= "REV:".$this->revision_date."\r\n";
		$this->card .= "FN:".$this->data['display_name']."\r\n";
		$this->card .= "N:";
		$this->card .= $this->data['last_name'].";";
		$this->card .= $this->data['first_name'];
		if (strlen($this->data['additional_name']) > 0) {
			$this->card .= ";".$this->data['additional_name'];
		}
		if (strlen($this->data['name_prefix']) > 0) {
			$this->card .= ";".$this->data['name_prefix'];
		}
		if (strlen($this->data['name_suffix']) > 0) {
			$this->card .= ";".$this->data['name_suffix'];
		}
		$this->card .= "\r\n";
		if ($this->data['nickname']) { $this->card .= "NICKNAME:".$this->data['contact_nickname']."\r\n"; }
		if ($this->data['title']) { $this->card .= "TITLE:".$this->data['title']."\r\n"; }
		if ($this->data['company']) { $this->card .= "ORG:".$this->data['company']; }
		if ($this->data['department']) { $this->card .= ";".$this->data['department']; }
		$this->card .= "\r\n";

		$vcard_address_type_values = array('work','home','dom','intl','postal','parcel','pref');
		foreach ($vcard_address_type_values as $vcard_address_type_value) {
			if ($this->data[$vcard_address_type_value.'_po_box']
			|| $this->data[$vcard_address_type_value.'_extended_address']
			|| $this->data[$vcard_address_type_value.'_address']
			|| $this->data[$vcard_address_type_value.'_city']
			|| $this->data[$vcard_address_type_value.'_state']
			|| $this->data[$vcard_address_type_value.'_postal_code']
			|| $this->data[$vcard_address_type_value.'_country']) {
				$this->card .= "ADR;TYPE=".$vcard_address_type_value.":";
				if (strlen($this->data[$vcard_address_type_value.'_po_box']) > 0) {
					$this->card .= $this->data[$vcard_address_type_value.'_po_box'].";";
				}
				if (strlen($this->data[$vcard_address_type_value.'_extended_address']) > 0) {
					$this->card .= $this->data[$vcard_address_type_value.'_extended_address'].";";
				}
				if (strlen($this->data[$vcard_address_type_value.'_address']) > 0) {
					$this->card .= $this->data[$vcard_address_type_value.'_address'].";";
				}
				if (strlen($this->data[$vcard_address_type_value.'_city']) > 0) {
					$this->card .= $this->data[$vcard_address_type_value.'_city'].";";
				}
				if (strlen($this->data[$vcard_address_type_value.'_state']) > 0) {
					$this->card .= $this->data[$vcard_address_type_value.'_state'].";";
				}
				if (strlen($this->data[$vcard_address_type_value.'_postal_code']) > 0) {
					$this->card .= $this->data[$vcard_address_type_value.'_postal_code'].";";
				}
				if (strlen($this->data[$vcard_address_type_value.'_country']) > 0) {
					$this->card .= $this->data[$vcard_address_type_value.'_country']."";
				}
				$this->card .= "\r\n";
			}
		}

		if ($this->data['email1']) { $this->card .= "EMAIL;PREF=1:".$this->data['email1']."\r\n"; }
		if ($this->data['email2']) { $this->card .= "EMAIL;PREF=2:".$this->data['email2']."\r\n"; }
		if ($this->data['voice_tel']) { $this->card .= "TEL;TYPE=voice:".$this->data['voice_tel']."\r\n"; }
		if ($this->data['work_tel']) { $this->card .= "TEL;TYPE=work:".$this->data['work_tel']."\r\n"; }
		if ($this->data['home_tel']) { $this->card .= "TEL;TYPE=home:".$this->data['home_tel']."\r\n"; }
		if ($this->data['cell_tel']) { $this->card .= "TEL;TYPE=cell:".$this->data['cell_tel']."\r\n"; }
		if ($this->data['fax_tel']) { $this->card .= "TEL;TYPE=fax:".$this->data['fax_tel']."\r\n"; }
		if ($this->data['pager_tel']) { $this->card .= "TEL;TYPE=pager:".$this->data['pager_tel']."\r\n"; }
		if ($this->data['url']) { $this->card .= "URL:".$this->data['url']."\r\n"; }
		if ($this->data['birthday']) { $this->card .= "BDAY:".$this->data['birthday']."\r\n"; }
		if ($this->data['role']) { $this->card .= "ROLE:".$this->data['role']."\r\n"; }
		if ($this->data['note']) { $this->card .= "NOTE:".$this->data['note']."\r\n"; }
		$this->card .= "TZ:".$this->data['timezone']."\r\n";
		$this->card .= "END:VCARD";
	}

	/*
	download() method streams the vcard to the browser client.
	*/
	function download() {
		$this->log .= "vcard download() called<br />";
		if (!$this->card) { $this->build(); }
		if (!$this->filename) { $this->filename = trim($this->data['display_name']); }
		$this->filename = str_replace(" ", "_", $this->filename);
		header("Content-type: text/directory");
		header("Content-Disposition: attachment; filename=".$this->filename.".vcf");
		header("Pragma: public");
		echo $this->card;
		return true;
	}
}
