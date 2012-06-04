include("config.js");
	//var sounds_dir
	//var admin_pin
	//var tmp_dir

var sipuri = argv[0];
var caller_id_name = argv[1];
var caller_id_number = argv[2];
var tmp_sipuri;

caller_id_name = caller_id_name.replace("+", " ");
//console_log( "info", "caller_announce: "+caller_announce+"\n" );

function originate (sipuri, caller_id_name, caller_id_number) {

	var dtmf = new Object();
	var cid;
	dtmf.digits = "";
	cid = ",origination_caller_id_name="+caller_id_name+",origination_caller_id_number="+caller_id_number;
	
	new_session = new Session("{ignore_early_media=true"+cid+"}"+sipuri);
	new_session.execute("set", "call_timeout=30");
		
	if ( new_session.ready() ) {
		new_session.streamFile( sounds_dir+"/custom/press_1_to_accept_2_to_reject_or_3_for_voicemail.wav");
		digitmaxlength = 1;
		while (new_session.ready()) {
			//console_log( "info", "originate succeeded\n" );
		}

	}
}

sipuri_array = sipuri.split(",");
for (i = 0; i < sipuri_array.length; i++){
	tmp_sipuri = sipuri_array[i];
	console_log("info", "tmp_sipuri: "+tmp_sipuri);
	result = originate (tmp_sipuri, caller_id_name, caller_id_number);
	if (result) {
		break;
		exit;
	}
}