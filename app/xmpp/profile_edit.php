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
						<strong>
						<?
						if ($action == "update") {
							echo $text['header-xmpp-edit'];
						}
						else if ($action == "add") {
							echo $text['header-xmpp-add'];
						}
						?>
						</strong><br>
					</td>		<td width='50%' align='right'>
						<input type='submit' name='submit' class='btn' value='<?=$text['button-save']?>'>
						<input type='button' class='btn' name='' alt='back' onclick="window.location='xmpp.php'" value='<?=$text['button-back']?>'>
					</td>
				</tr>
				<tr>
					<td align='left' colspan='2'>
						<?=$text['description-xmpp-edit-add']?><br />
					</td>
				</tr>
			</table>
			<br />
		</td>
	</tr>

	<tr>
		<td width="30%" class='vncellreq' valign='top' align='left' nowrap='nowrap'>
			<?=$text['label-profile_name']?>:
		</td>
		<td width="70%" class='vtable' align='left'>
			<input class='formfld' type='text' name='profile_name' maxlength='255' value="<?php echo $profile['profile_name']; ?>">
			<br />
			<?=$text['description-profile_name']?>
		</td>
	</tr>

	<tr>
		<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>
			<?=$text['label-username']?>:
		</td>
		<td class='vtable' align='left'>
			<input class='formfld' type='text' name='profile_username' autocomplete='off' maxlength='255' value="<?php echo $profile['profile_username'];?>">
			<br />
			<?=$text['description-username']?>
		</td>
	</tr>

	<tr>
		<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>
			<?=$text['label-password']?>:
		</td>
		<td class='vtable' align='left'>
			<input class='formfld' type='password' name='profile_password' autocomplete='off' id='profile_password' maxlength='50' onfocus="document.getElementById('show_profile_password').innerHTML = '<?=$text['label-password']?>: '+document.getElementById('profile_password').value;" value="<?php echo $profile['profile_password'];?>">
			<br />
			<span onclick="document.getElementById('show_profile_password').innerHTML = ''"><?=$text['description-password']?> </span><span id='show_profile_password'></span>
		</td>
	</tr>

	<tr>
		<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>
			<?=$text['label-auto_login']?>:
		</td>
		<td class='vtable' align='left'>
			<select class='formfld' name='auto_login'>
			<option value='true' <?php if($profile['auto_login'] == "true") echo "selected='selected'"; ?>><?=$text['label-true']?></option>
			<option value='false' <?php if($profile['auto_login'] == "false") echo "selected='selected'"; ?>><?=$text['label-false']?></option>
			</select>
			<br />
			<?=$text['description-auto_login']?>
		</td>
	</tr>

	<tr>
		<td width='30%' class='vncell' valign='top' align='left' nowrap='nowrap'>
			<?=$text['label-xmpp_server']?>:
		</td>
		<td width='70%' class='vtable' align='left'>
			<input class='formfld' type='text' name='xmpp_server' maxlength='255' value="<?php echo $profile['xmpp_server'];?>">
			<br />
			<?=$text['description-xmpp_server']?>
		</td>
	</tr>

	<tr>
		<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>
			<?=$text['label-default_exten']?>:
		</td>
		<td class='vtable' align='left'>
			<input class='formfld' type='text' name='default_exten' maxlength='255' value="<?php echo $profile['default_exten'];?>">
			<br />
			<?=$text['description-default_exten']?>
		</td>
	</tr>

	<tr>
	<td style='padding: 0px;' colspan='2' class='' valign='top' align='left' nowrap='nowrap'>
		<div id="show_advanced_box">
			<table width="100%" border="0" cellpadding="6" cellspacing="0">
				<tr>
					<td width="30%" valign="top" class="vncell"><?=$text['label-advanced']?></td>
					<td width="70%" class="vtable">
						<input type="button" onClick="show_advanced_config()" value="<?=$text['button-advanced']?>"></input>
					</td>
				</tr>
			</table>
		</div>
		<div id="show_advanced" style="display:none">
			<table width="100%" border="0" cellpadding="6" cellspacing="0">
				<tr>
				<?php if (if_group("superadmin")) { ?>
					<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>
						<?=$text['label-context']?>:
					</td>
					<td class='vtable' align='left'>
						<input class='formfld' type='text' name='context' maxlength='255' value="<?php echo $profile['context'];?>">
						<br />
						<?=$text['description-context']?>
					</td>
					</tr>
				<?php }	?>
					<tr>
					<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>
						<?=$text['label-rtp_ip']?>:
					</td>
					<td class='vtable' align='left'>
						<input class='formfld' type='text' name='rtp_ip' maxlength='255' value="<?php echo $profile['rtp_ip'];?>">
						<br />
						<?=$text['description-rtp_ip']?>
					</td>
					</tr>

					<tr>
					<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>
						<?=$text['label-ext_rtp_ip']?>:
					</td>
					<td class='vtable' align='left'>
						<input class='formfld' type='text' name='ext_rtp_ip' maxlength='255' value="<?php echo $profile['ext_rtp_ip'];?>">
						<br />
						<?=$text['description-ext_rtp_ip']?>
					</td>
					</tr>

					<tr>
					<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>
						<?=$text['label-sasl_type']?>:
					</td>
					<td class='vtable' align='left'>
						<select class='formfld' name='sasl_type'>
						<option value='plain' <?php if($profile['sasl_type'] == "plain") echo "selected='selected'"; ?>>Plain</option>
						<option value='md5' <?php if($profile['sasl_type'] == "md5") echo "selected='selected'"; ?>>MD5</option>
						</select>
						<br />
						<?=$text['description-sasl_type']?>
					</td>
					</tr>

					<tr>
					<td class='vncell' valign='top' align='left' nowrap='nowrap'>
						<?=$text['label-tls_enable']?>:
					</td>
					<td class='vtable' align='left'>
						<select class='formfld' name='tls_enable'>
						<option value='true' <?php if($profile['tls_enable'] == "true") echo "selected='selected'"; ?>><?=$text['label-true']?></option>
						<option value='false' <?php if($profile['tls_enable'] == "false") echo "selected='selected'"; ?>><?=$text['label-false']?></option>
						</select>
						<br />
						<?=$text['description-tls_enable']?>
					</td>
					</tr>

					<tr>
					<td class='vncell' valign='top' align='left' nowrap='nowrap'>
						<?=$text['label-use_rtp_timer']?>:
					</td>
					<td class='vtable' align='left'>
						<select class='formfld' name='use_rtp_timer'>
						<option value='true' <?php if($profile['use_rtp_timer'] == "true") echo "selected='selected'"; ?>><?=$text['label-true']?></option>
						<option value='false' <?php if($profile['use_rtp_timer'] == "false") echo "selected='selected'"; ?>><?=$text['label-false']?></option>
						</select>
						<br />
						<?=$text['description-use_rtp_timer']?>
					</td>
					</tr>
					<tr>
					<td class='vncell' valign='top' align='left' nowrap='nowrap'>
						<?=$text['label-vad']?>:
					</td>
					<td class='vtable' align='left'>
						<select class='formfld' name='vad'>
						<option value='none' <?php if($profile['vad'] == "none") echo "selected='selected'"; ?>><?=$text['option-vad_none']?></option>
						<option value='in' <?php if($profile['vad'] == "in") echo "selected='selected'"; ?>><?=$text['option-vad_in']?></option>
						<option value='out' <?php if($profile['vad'] == "out") echo "selected='selected'"; ?>><?=$text['option-vad_out']?></option>
						<option value='both' <?php if($profile['vad'] == "both") echo "selected='selected'"; ?>><?=$text['option-vad_both']?></option>
						</select>
						<br />
						<?=$text['description-vad']?>
					</td>
					</tr>
					<tr>
					<td class='vncell' valign='top' align='left' nowrap='nowrap'>
						<?=$text['label-candidate_acl']?>:
					</td>
					<td class='vtable' align='left'>
						<input class='formfld' type='text' name='candidate_acl' maxlength='255' value="<?php echo $profile['candidate_acl'];?>">
						<br />
						<?=$text['description-candidate_acl']?>
					</td>
					</tr>

					<tr>
					<td class='vncell' valign='top' align='left' nowrap='nowrap'>
						<?=$text['label-local_network_acl']?>:
					</td>
					<td class='vtable' align='left'>
						<input class='formfld' type='text' name='local_network_acl' maxlength='255' value="<?php echo $profile['local_network_acl'];?>">
						<br />
						<?=$text['description-local_network_acl']?>
					</td>
				</tr>
			</table>
		</div>
	</td>
	</tr>
	<tr>
		<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>
			<?=$text['label-enabled']?>:
		</td>
		<td class='vtable' align='left'>
			<select class='formfld' name='enabled'>
			<option value='true' <?php if($profile['enabled'] == "true") echo "selected='selected'"; ?>><?=$text['label-true']?></option>
			<option value='false' <?php if($profile['enabled'] == "false") echo "selected='selected'"; ?>><?=$text['label-false']?></option>
			</select>
			<br />
			<?=$text['description-enabled']?>
		</td>
	</tr>
	<tr>
		<td class='vncell' valign='top' align='left' nowrap='nowrap'>
			<?=$text['label-description']?>:
		</td>
		<td class='vtable' align='left'>
			<input class='formfld' type='text' name='description' value='<?php echo $profile['description'];?>'>
			<br />
			<?=$text['description-description']?>
		</td>
	</tr>
	<tr>
		<td colspan='2' align='right'>
				<input type='hidden' name='profile_id' value='<?php echo $profile['xmpp_profile_uuid']; ?>'>
				<input type='submit' name='submit' class='btn' value='<?=$text['button-save']?>'>
		</td>
	</tr>
	</table>
	</form>

</td>
</tr>
</table>
</div>
