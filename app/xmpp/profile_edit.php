<script type="text/javascript" language="JavaScript">
function enable_change(enable_over) {
  var endis;
  endis = !(document.iform.enable.checked || enable_over);
  document.iform.range_from.disabled = endis;
  document.iform.range_to.disabled = endis;
}

function show_advanced_config() {
  document.getElementById("show_advanced_box").innerHTML='';
  aodiv = document.getElementById('show_advanced');
  aodiv.style.display = "block";
}

function hide_advanced_config() {
  document.getElementById("show_advanced_box").innerHTML='';
  aodiv = document.getElementById('show_advanced');
  aodiv.style.display = "block";
}
</script>

<div align='center'>
<table width='100%' border='0' cellpadding='0' cellspacing='2'>
<tr class='border'>
<td align=\"left\">
	<br>
	<form method='post' name='ifrm' action=''>

	<div align='center'> 
	<table width='100%'  border='0' cellpadding='6' cellspacing='0'> 
	<tr> 
		<td colspan='2'> 
			<table width="100%" border="0" cellpadding="0" cellspacing="0"> 
				<tr> 
					<td align='left' width="50%"> 
						<strong>Profile Edit</strong><br> 
					</td>		<td width='50%' align='right'> 
						<input type='submit' name='submit' class='btn' value='Save'> 
						<!-- <input type='button' class='btn' name='' alt='copy' onclick="if (confirm('Do you really want to copy this?')){window.location='v_gateways_copy.php?id=1';}" value='Copy'>  -->
						<input type='button' class='btn' name='' alt='back' onclick="window.location='xmpp.php'" value='Back'> 
					</td> 
				</tr>
				<tr>
					<td align='left' colspan='2'> 
						Defines a connections to a Jabber, GTalk, or other XMPP Provider server. <br /> 
					</td> 
				</tr> 
			</table> 
			<br />
		</td> 
	</tr>

	<tr> 
		<td width="30%" class='vncellreq' valign='top' align='left' nowrap='nowrap'> 
			Profile Name:
		</td> 
		<td width="70%" class='vtable' align='left'> 
			<input class='formfld' type='text' name='profile_name' maxlength='255' value="<?php echo $profile['profile_name']; ?>"> 
			<br /> 
			Enter the profile name here.
		</td> 
	</tr>

	<tr>
		<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>
			Username:
		</td>
		<td class='vtable' align='left'>
			<input class='formfld' type='text' name='profile_username' autocomplete='off' maxlength='255' value="<?php echo $profile['profile_username'];?>">
			<br />
			Enter the XMPP username here.
		</td>
	</tr>

	<tr>
		<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>
			Password:
		</td>
		<td class='vtable' align='left'>
			<input class='formfld' type='password' name='profile_password' autocomplete='off' id='profile_password' maxlength='50' onfocus="document.getElementById('show_profile_password').innerHTML = 'Password: '+document.getElementById('profile_password').value;" value="<?php echo $profile['profile_password'];?>"> 
			<br />
			<span onclick="document.getElementById('show_profile_password').innerHTML = ''">Enter the password here. </span><span id='show_profile_password'></span> 
		</td>
	</tr>

	<tr>
		<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>
			Auto-Login:
		</td>
		<td class='vtable' align='left'>
			<select class='formfld' name='auto_login'>
			<option value='true' <?php if($profile['auto_login'] == "true") echo "selected='selected'"; ?>>true</option> 
			<option value='false' <?php if($profile['auto_login'] == "false") echo "selected='selected'"; ?>>false</option> 
			</select>
			<br />
			Choose whether to automatically login.
		</td>
	</tr>

	<tr> 
		<td width='30%' class='vncell' valign='top' align='left' nowrap='nowrap'> 
			XMPP Server:
		</td> 
		<td width='70%' class='vtable' align='left'> 
			<input class='formfld' type='text' name='xmpp_server' maxlength='255' value="<?php echo $profile['xmpp_server'];?>"> 
			<br /> 
			Enter alternate XMPP server if the server is not the same as the one in the Username<br />
			Example GoogleTalk is: talk.google.com
		</td> 
	</tr> 

	<tr> 
		<td class='vncellreq' valign='top' align='left' nowrap='nowrap'> 
			Default Extension:
		</td> 
		<td class='vtable' align='left'> 
			<input class='formfld' type='text' name='default_exten' maxlength='255' value="<?php echo $profile['default_exten'];?>"> 
			<br /> 
			Default extension (if one cannot be determined)
		</td>
	</tr>

	<tr>
	<td style='padding: 0px;' colspan='2' class='' valign='top' align='left' nowrap='nowrap'> 
		<div id="show_advanced_box"> 
			<table width="100%" border="0" cellpadding="6" cellspacing="0"> 
				<tr> 
					<td width="30%" valign="top" class="vncell">Show Advanced</td> 
					<td width="70%" class="vtable"> 
						<input type="button" onClick="show_advanced_config()" value="Advanced"></input>
					</td> 
				</tr>
			</table> 
		</div>
		<div id="show_advanced" style="display:none"> 
			<table width="100%" border="0" cellpadding="6" cellspacing="0"> 
				<tr>
				<?php if (if_group("superadmin")) { ?>
					<td class='vncellreq' valign='top' align='left' nowrap='nowrap'> 
						Context:
					</td> 
					<td class='vtable' align='left'> 
						<input class='formfld' type='text' name='context' maxlength='255' value="<?php echo $profile['context'];?>"> 
						<br /> 
						Enter the context here.
					</td> 
					</tr>
				<?php }	?>
					<tr> 
					<td class='vncellreq' valign='top' align='left' nowrap='nowrap'> 
						RTP IP:
					</td> 
					<td class='vtable' align='left'> 
						<input class='formfld' type='text' name='rtp_ip' maxlength='255' value="<?php echo $profile['rtp_ip'];?>">
						<br /> 
						IP Address for RTP
					</td> 
					</tr> 

					<tr> 
					<td class='vncellreq' valign='top' align='left' nowrap='nowrap'> 
						Ext RTP IP:
					</td> 
					<td class='vtable' align='left'> 
						<input class='formfld' type='text' name='ext_rtp_ip' maxlength='255' value="<?php echo $profile['ext_rtp_ip'];?>">
						<br />
						External IP Address for RTP
					</td> 
					</tr>

					<tr> 
					<td class='vncellreq' valign='top' align='left' nowrap='nowrap'> 
						SASL Type:
					</td> 
					<td class='vtable' align='left'> 
						<select class='formfld' name='sasl_type'> 
						<option value='plain' <?php if($profile['sasl_type'] == "plain") echo "selected='selected'"; ?>>plain</option> 
						<option value='md5' <?php if($profile['sasl_type'] == "md5") echo "selected='selected'"; ?>>md5</option> 
						</select>
						<br /> 
						Choose SASL Type. Plain or MD5
					</td> 
					</tr>

					<tr> 
					<td class='vncell' valign='top' align='left' nowrap='nowrap'> 
						Enable TLS:
					</td> 
					<td class='vtable' align='left'> 
						<select class='formfld' name='tls_enable'> 
						<option value='true' <?php if($profile['tls_enable'] == "true") echo "selected='selected'"; ?>>true</option> 
						<option value='false' <?php if($profile['tls_enable'] == "false") echo "selected='selected'"; ?>>false</option> 
						</select> 
						<br /> 
						Enable TLS or not
					</td> 
					</tr>

					<tr> 
					<td class='vncell' valign='top' align='left' nowrap='nowrap'> 
						Use RTP Timer
					</td> 
					<td class='vtable' align='left'> 
						<select class='formfld' name='use_rtp_timer'> 
						<option value='true' <?php if($profile['use_rtp_timer'] == "true") echo "selected='selected'"; ?>>true</option> 
						<option value='false' <?php if($profile['use_rtp_timer'] == "false") echo "selected='selected'"; ?>>false</option> 
						</select> 
						<br /> 
						Disable to trade async for more calls
					</td> 
					</tr> 
					<tr> 
					<td class='vncell' valign='top' align='left' nowrap='nowrap'> 
						Voice Activity Detection
					</td> 
					<td class='vtable' align='left'> 
						<select class='formfld' name='vad'> 
						<option value='none' <?php if($profile['vad'] == "none") echo "selected='selected'"; ?>>none</option> 
						<option value='in' <?php if($profile['vad'] == "in") echo "selected='selected'"; ?>>in</option> 
						<option value='out' <?php if($profile['vad'] == "out") echo "selected='selected'"; ?>>out</option> 
						<option value='both' <?php if($profile['vad'] == "both") echo "selected='selected'"; ?>>both</option> 
						</select> 
						<br /> 
						Which direction are we doing VAD?
					</td> 
					</tr> 
					<tr> 
					<td class='vncell' valign='top' align='left' nowrap='nowrap'> 
						Candidate ACL
					</td> 
					<td class='vtable' align='left'> 
						<input class='formfld' type='text' name='candidate_acl' maxlength='255' value="<?php echo $profile['candidate_acl'];?>"> 
						<br /> 
						Candidate access control list
					</td>
					</tr>

					<tr> 
					<td class='vncell' valign='top' align='left' nowrap='nowrap'>
						Local Network ACL
					</td> 
					<td class='vtable' align='left'> 
						<input class='formfld' type='text' name='local_network_acl' maxlength='255' value="<?php echo $profile['local_network_acl'];?>"> 
						<br /> 
						Local network access control list
					</td> 
				</tr> 
			</table> 
		</div>
	</td> 
	</tr> 
	<tr> 
		<td class='vncellreq' valign='top' align='left' nowrap='nowrap'> 
			Enabled:
		</td> 
		<td class='vtable' align='left'> 
			<select class='formfld' name='enabled'> 
			<option value='true' <?php if($profile['enabled'] == "true") echo "selected='selected'"; ?>>true</option> 
			<option value='false' <?php if($profile['enabled'] == "false") echo "selected='selected'"; ?>>false</option> 
			</select> 
			<br /> 
		</td> 
	</tr> 
	<tr>
		<td class='vncell' valign='top' align='left' nowrap='nowrap'> 
			Profile Description:
		</td> 
		<td class='vtable' align='left'> 
			<input class='formfld' type='text' name='description' value='<?php echo $profile['description'];?>'> 
			<br /> 
			Enter the description of the Profile here.
		</td> 
	</tr> 
	<tr> 
		<td colspan='2' align='right'> 
				<input type='hidden' name='profile_id' value='<?php echo $profile['xmpp_profile_uuid']; ?>'> 
				<input type='submit' name='submit' class='btn' value='Save'> 
		</td> 
	</tr>
	</table>
	</form>

</td>
</tr>
</table>
</div>
