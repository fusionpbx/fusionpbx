<html>
	<table width="400" border="0" cellspacing="0" cellpadding="0" align="center"
	style="border: 1px solid #cbcfd5;-moz-border-radius: 4px;
	-webkit-border-radius: 4px; border-radius: 4px;">
		<tr>
			<td valign="middle" align="center" bgcolor="#e5e9f0" style="background-color: #e5e9f0;
			color: #000; font-family: Arial; font-size: 14px; padding: 7px;-moz-border-radius: 4px;
			-webkit-border-radius: 4px; border-radius: 4px;">
				<strong>Neue Sprachnachricht</strong>
			</td>
		</tr>
		<tr>
			<td valign="top" style="padding: 15px;">
				<table width="100%" border="0" cellspacing="0" cellpadding="0">
					<tr>
						<td style="color: #333; font-family: Arial; font-size: 12px; padding-bottom: 11px;">
							<strong>Nebenstelle</strong>
						</td>
						<td style="color: #666; font-family: Arial; font-size: 12px; padding-bottom: 11px;">
							${voicemail_name_formatted}
						</td>
					</tr>
					<tr>
						<td style="color: #333; font-family: Arial; font-size: 12px; padding-bottom: 11px;" width="20%">
							<strong>Anrufer</strong>
						</td>
						<td style="color: #666; font-family: Arial; font-size: 12px; padding-bottom: 11px;" width="80%">
							${caller_id_number}
						</td>
					</tr>
					<!--
					<tr>
						<td style="color: #333; font-family: Arial; font-size: 12px; padding-bottom: 11px;">
							<strong>Received</strong>
						</td>
						<td style="color: #666; font-family: Arial; font-size: 12px; padding-bottom: 11px;">
							${message_date}
						</td>
					</tr>
					-->
					<tr>
						<td style="color: #333; font-family: Arial; font-size: 12px; padding-bottom: 11px;">
							<strong>Nachricht</strong>
						</td>
						<td style="color: #666; font-family: Arial; font-size: 12px; padding-bottom: 11px;">
							${message}
						</td>
					</tr>
					<tr>
						<td style="color: #333; font-family: Arial; font-size: 12px; padding-bottom: 11px;">
							<strong>L&auml;nge</strong>
						</td>
						<td style="color: #666; font-family: Arial; font-size: 12px; padding-bottom: 11px;">
							${message_duration}
						</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
</html>
