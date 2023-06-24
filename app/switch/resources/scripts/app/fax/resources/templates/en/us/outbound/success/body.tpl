<html>
	<table width="400" border="0" cellspacing="0" cellpadding="0" align="center"
	style="border: 1px solid #cbcfd5;-moz-border-radius: 4px;
	-webkit-border-radius: 4px; border-radius: 4px;">
		<tr>
			<td valign="middle" align="center" bgcolor="#e5e9f0" style="background-color: #e5e9f0;
			color: #000; font-family: Arial; font-size: 14px; padding: 7px;-moz-border-radius: 4px;
			-webkit-border-radius: 4px; border-radius: 4px;">
			<strong>Send fax successfully</strong>
			</td>
		</tr>
		<tr>
			<td valign="top" style="padding: 15px;">
				<table width="100%" border="0" cellspacing="0" cellpadding="0">
					<tr>
						<td style="color: #333; font-family: Arial; font-size: 12px; padding-bottom: 11px;">
							<strong>To</strong>
						</td>
						<td style="color: #666; font-family: Arial; font-size: 12px; padding-bottom: 11px;">
							${destination_number}
						</td>
					</tr>
					<tr>
						<td style="color: #333; font-family: Arial; font-size: 12px; padding-bottom: 11px;">
							<strong>Pages</strong>
						</td>
						<td style="color: #666; font-family: Arial; font-size: 12px; padding-bottom: 11px;">
							${document_transferred_pages}
						</td>
					</tr>
					<tr>
						<td style="color: #333; font-family: Arial; font-size: 12px; padding-bottom: 11px;">
							<strong>Message</strong>
						</td>
						<td style="color: #666; font-family: Arial; font-size: 12px; padding-bottom: 11px;">
							${message}
						</td>
					</tr>
					<tr>
						<td style="color: #333; font-family: Arial; font-size: 12px; padding-bottom: 11px;">
							<strong>Options</strong>
						</td>
						<td style="color: #666; font-family: Arial; font-size: 12px; padding-bottom: 11px;">
							${fax_options}
						</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
</html>