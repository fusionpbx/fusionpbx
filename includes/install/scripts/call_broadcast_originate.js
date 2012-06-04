include("config.js");
	//var sounds_dir
	//var admin_pin
	//var tmp_dir
	//var recordings_dir

var sipuri = argv[0];
var recording = argv[1];
var caller_id_name = argv[2];
var caller_id_number = argv[3];
var call_timeout = argv[4];
var call_count_var_name = argv[5];
var tmp_sipuri;

caller_id_name = caller_id_name.replace("+", " ");
console_log( "info", "sipuri: "+sipuri+"\n" );
console_log( "info", "recording: "+recording+"\n" );
console_log( "info", "caller_id_name: "+caller_id_name+"\n" );
console_log( "info", "caller_id_number: "+caller_id_number+"\n" );
console_log( "info", "call_timeout: "+call_timeout+"\n" );
console_log( "info", "call_count_var_name: "+call_count_var_name+"\n" );



//function on_hangup(hup_session, how)
//{
//	console_log("err", how + " HOOK" +  " name: " + hup_session.name + " cause: " + hup_session.cause + "\n");                                                  
//	//exit here would end the script so you could cleanup and just be done
//	exit();
//}

function originate (sipuri, recording, caller_id_name, caller_id_number, call_timeout, count_var_name) {

	var dtmf = new Object();
	var cid;
	dtmf.digits = "";
	cid = ",origination_caller_id_name="+caller_id_name+",origination_caller_id_number="+caller_id_number;

	new_session = new Session("{ignore_early_media=true,hangup_after_bridge=false,call_timeout="+call_timeout+""+cid+"}"+sipuri);
	//new_session = new Session(sipuri);
 
//new_session.execute("set", "api_after_bridge=reloadxml");
//set the on_hangup function to be called when this session is hungup
//new_session.setHangupHook(on_hangup);
//result = new_session.setAutoHangup(true);

	//console_log( "info", "followme: new_session uuid "+new_session.uuid+"\n" );
	//console_log( "info", "followme: no dtmf detected\n" );

	digitmaxlength = 1;
	while (new_session.ready()) {
		if (recording.length > 0) {
			//new_session.streamFile( recordings_dir+"/"+recording);
			new_session.execute("playback",recordings_dir+"/"+recording);
			//new_session.hangup("NORMAL_CLEARING");
		}
		break;
	}

	var hangup_cause = new_session.getVariable("bridge_hangup_cause");
	console_log( "info", "hangup cause: "+hangup_cause+"\n" );
	var count = getGlobalVariable(call_count_var_name);
	setGlobalVariable(call_count_var_name, (parseInt(count)-1));
	console_log( "info", "action: hangup, count: "+count+"\n" );

}

sipuri_array = sipuri.split(",");
for (i = 0; i < sipuri_array.length; i++){
	//var count = getGlobalVariable(call_count_var_name);
	//setGlobalVariable(call_count_var_name, (parseInt(count)+1));

	tmp_sipuri = sipuri_array[i];
	console_log("info", "tmp_sipuri: "+tmp_sipuri);
	result = originate (tmp_sipuri, recording, caller_id_name, caller_id_number, call_timeout, call_count_var_name);
	if (result) {
		break;
	}
}
exit();
