<script type="text/javascript" language="JavaScript">
function enable_change(enable_over) {
  var endis;
  endis = !(document.iform.enable.checked || enable_over);
  document.iform.range_from.disabled = endis;
  document.iform.range_to.disabled = endis;
}

function show_advanced_config() {
	$('#show_advanced_box').slideToggle();
	$('#show_advanced').slideToggle();
}
</script>

	<form method='post' name='ifrm' action=''>
	<table width='100%' border='0' cellpadding='0' cellspacing='0'>
	<tr>
		<td colspan='2'>
			<table width="100%" border="0" cellpadding="0" cellspacing="0">
				<tr>
					<td align='left' width="50%">
						<span class='title'>
						<?php
						if ($action == "update") {
							echo $text['header-xmpp-edit'];
						}
						else if ($action == "add") {
							echo $text['header-xmpp-add'];
						}
						?>
						</span><br>
					</td>		<td width='50%' align='right'>
						<input type='button' class='btn' name='' alt='back' onclick="window.location='xmpp.php'" value='<?php echo $text['button-back']?>'>
						<input type='submit' name='submit' class='btn' value='<?php echo $text['button-save']?>'>
					</td>
				</tr>
				<tr>
					<td align='left' colspan='2'>
						<?php echo $text['description-xmpp-edit-add']?><br />
					</td>
				</tr>
			</table>
			<br />
		</td>
	</tr>

	<tr>
		<td width="30%" class='vncellreq' valign='top' align='left' nowrap='nowrap'>
			<?php echo $text['label-profile_name']?>:
		</td>
		<td width="70%" class='vtable' align='left'>
			<input class='formfld' type='text' name='profile_name' maxlength='255' value="<?php echo $profile['profile_name']; ?>" required='required'>
			<br />
			<?php echo $text['description-profile_name']?>
		</td>
	</tr>

	<tr>
		<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>
			<?php echo $text['label-username']?>:
		</td>
		<td class='vtable' align='left'>
			<input class='formfld' type='text' name='profile_username' autocomplete='off' maxlength='255' value="<?php echo $profile['profile_username'];?>" required='required'>
			<br />
			<?php echo $text['description-username']?>
		</td>
	</tr>

	<tr>
		<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>
			<?php echo $text['label-password']?>:
		</td>
		<td class='vtable' align='left'>
			<input class='formfld' type='password' name='profile_password' autocomplete='off' id='profile_password' maxlength='50' onmouseover="this.type='text';" onfocus="this.type='text';" onmouseout="if (!$(this).is(':focus')) { this.type='password'; }" onblur="this.type='password';" value="<?php echo $profile['profile_password'];?>" required='required'>
			<br />
			<?php echo $text['description-password']?>
		</td>
	</tr>

	<tr>
		<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>
			<?php echo $text['label-auto_login']?>:
		</td>
		<td class='vtable' align='left'>
			<select class='formfld' name='auto_login'>
			<option value='true' <?php if($profile['auto_login'] == "true") echo "selected='selected'"; ?>><?php echo $text['label-true']?></option>
			<option value='false' <?php if($profile['auto_login'] == "false") echo "selected='selected'"; ?>><?php echo $text['label-false']?></option>
			</select>
			<br />
			<?php echo $text['description-auto_login']?>
		</td>
	</tr>

	<tr>
		<td width='30%' class='vncell' valign='top' align='left' nowrap='nowrap'>
			<?php echo $text['label-xmpp_server']?>:
		</td>
		<td width='70%' class='vtable' align='left'>
			<input class='formfld' type='text' name='xmpp_server' maxlength='255' value="<?php echo $profile['xmpp_server'];?>">
			<br />
			<?php echo $text['description-xmpp_server']?>
		</td>
	</tr>

	<tr>
		<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>
			<?php echo $text['label-default_exten']?>:
		</td>
		<td class='vtable' align='left'>
			<input class='formfld' type='text' name='default_exten' maxlength='255' value="<?php echo $profile['default_exten'];?>" required='required'>
			<br />
			<?php echo $text['description-default_exten']?>
		</td>
	</tr>

	<tr>
	<td style='padding: 0px;' colspan='2' class='' valign='top' align='left' nowrap='nowrap'>
		<div id="show_advanced_box">
			<table width="100%" border="0" cellpadding="6" cellspacing="0">
				<tr>
					<td width="30%" valign="top" class="vncell">&nbsp;</td>
					<td width="70%" class="vtable">
						<input type="button" class="btn" onClick="show_advanced_config()" value="<?php echo $text['button-advanced']?>"></input>
					</td>
				</tr>
			</table>
		</div>
		<div id="show_advanced" style="display:none">
			<table width="100%" border="0" cellpadding="6" cellspacing="0">
				<tr>
				<?php if (if_group("superadmin")) { ?>
					<td width='30%' class='vncellreq' valign='top' align='left' nowrap='nowrap'>
						<?php echo $text['label-context']?>:
					</td>
					<td width='70%' class='vtable' align='left'>
						<input class='formfld' type='text' name='context' maxlength='255' value="<?php echo $profile['context'];?>" required='required'>
						<br />
						<?php echo $text['description-context']?>
					</td>
					</tr>
				<?php }	?>
					<tr>
					<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>
						<?php echo $text['label-rtp_ip']?>:
					</td>
					<td class='vtable' align='left'>
						<input class='formfld' type='text' name='rtp_ip' maxlength='255' value="<?php echo $profile['rtp_ip'];?>" required='required'>
						<br />
						<?php echo $text['description-rtp_ip']?>
					</td>
					</tr>

					<tr>
					<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>
						<?php echo $text['label-ext_rtp_ip']?>:
					</td>
					<td class='vtable' align='left'>
						<input class='formfld' type='text' name='ext_rtp_ip' maxlength='255' value="<?php echo $profile['ext_rtp_ip'];?>" required='required'>
						<br />
						<?php echo $text['description-ext_rtp_ip']?>
					</td>
					</tr>

					<tr>
					<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>
						<?php echo $text['label-sasl_type']?>:
					</td>
					<td class='vtable' align='left'>
						<select class='formfld' name='sasl_type'>
						<option value='plain' <?php if($profile['sasl_type'] == "plain") echo "selected='selected'"; ?>>Plain</option>
						<option value='md5' <?php if($profile['sasl_type'] == "md5") echo "selected='selected'"; ?>>MD5</option>
						</select>
						<br />
						<?php echo $text['description-sasl_type']?>
					</td>
					</tr>

					<tr>
					<td class='vncell' valign='top' align='left' nowrap='nowrap'>
						<?php echo $text['label-tls_enable']?>:
					</td>
					<td class='vtable' align='left'>
						<select class='formfld' name='tls_enable'>
						<option value='true' <?php if($profile['tls_enable'] == "true") echo "selected='selected'"; ?>><?php echo $text['label-true']?></option>
						<option value='false' <?php if($profile['tls_enable'] == "false") echo "selected='selected'"; ?>><?php echo $text['label-false']?></option>
						</select>
						<br />
						<?php echo $text['description-tls_enable']?>
					</td>
					</tr>

					<tr>
					<td class='vncell' valign='top' align='left' nowrap='nowrap'>
						<?php echo $text['label-use_rtp_timer']?>:
					</td>
					<td class='vtable' align='left'>
						<select class='formfld' name='use_rtp_timer'>
						<option value='true' <?php if($profile['use_rtp_timer'] == "true") echo "selected='selected'"; ?>><?php echo $text['label-true']?></option>
						<option value='false' <?php if($profile['use_rtp_timer'] == "false") echo "selected='selected'"; ?>><?php echo $text['label-false']?></option>
						</select>
						<br />
						<?php echo $text['description-use_rtp_timer']?>
					</td>
					</tr>
					<tr>
					<td class='vncell' valign='top' align='left' nowrap='nowrap'>
						<?php echo $text['label-vad']?>:
					</td>
					<td class='vtable' align='left'>
						<select class='formfld' name='vad'>
						<option value='none' <?php if($profile['vad'] == "none") echo "selected='selected'"; ?>><?php echo $text['option-vad_none']?></option>
						<option value='in' <?php if($profile['vad'] == "in") echo "selected='selected'"; ?>><?php echo $text['option-vad_in']?></option>
						<option value='out' <?php if($profile['vad'] == "out") echo "selected='selected'"; ?>><?php echo $text['option-vad_out']?></option>
						<option value='both' <?php if($profile['vad'] == "both") echo "selected='selected'"; ?>><?php echo $text['option-vad_both']?></option>
						</select>
						<br />
						<?php echo $text['description-vad']?>
					</td>
					</tr>
					<tr>
					<td class='vncell' valign='top' align='left' nowrap='nowrap'>
						<?php echo $text['label-candidate_acl']?>:
					</td>
					<td class='vtable' align='left'>
						<input class='formfld' type='text' name='candidate_acl' maxlength='255' value="<?php echo $profile['candidate_acl'];?>">
						<br />
						<?php echo $text['description-candidate_acl']?>
					</td>
					</tr>

					<tr>
					<td class='vncell' valign='top' align='left' nowrap='nowrap'>
						<?php echo $text['label-local_network_acl']?>:
					</td>
					<td class='vtable' align='left'>
						<input class='formfld' type='text' name='local_network_acl' maxlength='255' value="<?php echo $profile['local_network_acl'];?>">
						<br />
						<?php echo $text['description-local_network_acl']?>
					</td>
				</tr>
			</table>
		</div>
	</td>
	</tr>
	<tr>
		<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>
			<?php echo $text['label-enabled']?>:
		</td>
		<td class='vtable' align='left'>
			<select class='formfld' name='enabled'>
			<option value='true' <?php if($profile['enabled'] == "true") echo "selected='selected'"; ?>><?php echo $text['label-true']?></option>
			<option value='false' <?php if($profile['enabled'] == "false") echo "selected='selected'"; ?>><?php echo $text['label-false']?></option>
			</select>
			<br />
			<?php echo $text['description-enabled']?>
		</td>
	</tr>
	<tr>
		<td class='vncell' valign='top' align='left' nowrap='nowrap'>
			<?php echo $text['label-description']?>:
		</td>
		<td class='vtable' align='left'>
			<input class='formfld' type='text' name='description' value='<?php echo $profile['description'];?>'>
			<br />
			<?php echo $text['description-description']?>
		</td>
	</tr>
	<tr>
		<td colspan='2' align='right'>
			<input type='hidden' name='profile_id' value='<?php echo $profile['xmpp_profile_uuid']; ?>'>
			<br>
			<input type='submit' name='submit' class='btn' value='<?php echo $text['button-save']?>'>
		</td>
	</tr>
	</table>
	<br><br>
	</form>
