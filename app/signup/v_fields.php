<?php
	// Add/Edit Form Fields
	$forms[1]['header'] = "Please fill out this form completely. All BOLD fields are required.";
	$forms[1]['fields'][1] = array('username', "Username:", "text", TRUE, "Please provid a Username.<br>\n");
	$forms[1]['fields'][2] = array('password', "Password:", "password", TRUE, "Please provid a Username.<br>\n");
	$forms[1]['fields'][3] = array('confirmpassword', "Confirm Password:", "password", FALSE, "");
	$forms[1]['fields'][4] = array('user_company_name', "Company Name:", "text", FALSE, "");
	$forms[1]['fields'][5] = array('user_first_name', "First Name:", "text", TRUE, "Please provide a first name.<br>\n");
	$forms[1]['fields'][6] = array('user_last_name', "Last Name:", "text", TRUE, "Please provide a last name.<br>\n");
	$forms[1]['fields'][7] = array('user_email', "Email:", "text", TRUE, "Please provide an email address.<br>\n");
	$forms[1]['fields'][8] = array('user_phone_1', "Phone Number:", "text", TRUE, "Please provide a phone number.<br>\n");
	$forms[1]['fields'][9] = array('user_phone_1_ext', "Extension:", "text", FALSE, "");

	$forms[2]['header'] = "Billing Address";
	$forms[2]['fields'][1] = array('user_billing_address_1', "Address 1:", "text", TRUE, "Please provide a street address.<br>\n");
	$forms[2]['fields'][2] = array('user_billing_address_2', "Address 2:", "text", FALSE, "");
	$forms[2]['fields'][3] = array('user_billing_city', "City:", "text", TRUE, "Please provide a city.<br>\n");
	$forms[2]['fields'][4] = array('user_billing_state_province', "State/Province:", "text", TRUE, "Please provide a state or province.<br>\n");
	$forms[2]['fields'][5] = array('user_billing_country', "Country:", "text", TRUE, "Please provide a country.<br>\n");
	$forms[2]['fields'][6] = array('user_billing_postal_code', "ZIP/Postal Code:", "text", TRUE, "Please provide a postal code.<br>\n");

	$forms[3]['header'] = "Shipping Address";
	$forms[3]['fields'][1] = array('user_shipping_address_1', "Address 1:", "text", TRUE, "Please provide a street address.<br>\n");
	$forms[3]['fields'][2] = array('user_shipping_address_2', "Address 2:", "text", FALSE, "");
	$forms[3]['fields'][3] = array('user_shipping_city', "City:", "text", TRUE, "Please provide a city.<br>\n");
	$forms[3]['fields'][4] = array('user_shipping_state_province', "State/Province:", "text", TRUE, "Please provide a state or province.<br>\n");
	$forms[3]['fields'][5] = array('user_shipping_country', "Country:", "text", TRUE, "Please provide a country.<br>\n");
	$forms[3]['fields'][6] = array('user_shipping_postal_code', "ZIP/Postal Code:", "text", TRUE, "Please provide a postal code.<br>\n");

?>
