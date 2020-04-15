<?php
	$payment_plugin[$x]['name'] = 'Stripe';						// Name of the plugin, it will apear in webpage
	$payment_plugin[$x]['icon'] = 'pay-button.png';					// Icon
	$payment_plugin[$x]['path'] = PROJECT_PATH . "/app/billing/resources/plugins/stripe";	// Absolute path to directory
	$payment_plugin[$x]['testmode'] = false;						// Are you testing?  Offline doesn't really have a test mode
	$payment_plugin[$x]['default_charge'] = '10';					// 10 in any currency by default
//	$payment_plugin[$x]['currency'][] = '*';					// All currencies
	$payment_plugin[$x]['currency'][] = 'CAD';
	$payment_plugin[$x]['currency'][] = 'USD';
	$payment_plugin[$x]['message']['es'] = '';
	$payment_plugin[$x]['message']['en'] = '';
//	$payment_plugin[$x]['secret_key'] = 'sk_test_4TafDx83SUTWAcTOMcJZ8FgF';
//	$payment_plugin[$x]['publishable_key'] = 'pk_test_4TafMmbuZPG9LyGXJZ1rW3Mm';
	$payment_plugin[$x]['secret_key'] = 'sk_live_m0EIQHL0YwUu9O3xFSxNE0OK';
	$payment_plugin[$x]['publishable_key'] = 'pk_live_4TafBuGqNgzdIudlOLYdBcRj';
	$payment_plugin[$x]['percentage_comission'] = 0;
	$payment_plugin[$x]['fixed_comission'] = 0;
	$payment_plugin[$x]['fixed_comission_currency'] = 'USD';
