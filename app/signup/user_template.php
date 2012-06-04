	<div align='center'>
	<table width='90%' border='0' cellpadding='0' cellspacing='2'>
		<?php if (strlen($msgerror) > 0) { ?>
		<tr><td>
		<?php echo $msgerror; ?>
                </td></tr>
        <?php } ?>
	<tr>
		<td align="left">
	      <br>

	<form method='post' action=''>

<?php foreach($forms as $form) { ?>

	  <b><?php echo $form['header']; ?></b><br>
	  <div class='borderlight' style='padding:10px;'>
	  <table width="100%" cellpadding='6' cellspacing='0'>
<?php 
	foreach($form['fields'] as $field) {
		if ($field[3]) { 
			$cssclass = 'vncellreq'; 
		} else { 
			$cssclass = 'vncell'; 
		}

		if (in_array($field[0], $error_fields)) { 
			$cssclass = 'vncellreqerr';
		}
		
  		?> <tr><td class="<?php echo $cssclass; ?>" width="40%"><?php echo $field[1]; ?></td>
  		<td class="vtable" width="60%"><input type="<?php echo $field[2]; ?>" class="formfld" autocomplete="off"
				name="<?php echo $field[0]; ?>" value="<?php echo $request[$field[0]]; ?>"></td></tr>
<?php
		}

	print("</table></div><br>");
} 
?>

	<div class='' style='padding:10px;'>
	<table width="100%">
		<tr>
			<!-- <td valign='top'>
				<input type="checkbox" name="newsletter" value="newsletter" /> Yes, sign me up for news letter<br />
				<input type="checkbox" name="tos_agree" value="tos_agree" /> I have read and agree to the terms of service
			</td> -->
			<td colspan='2' align='center'><?php echo recaptcha_get_html($publickey, $error); ?></td>
		</tr>
		<tr>
			<td colspan='2' align='center'>
	       <input type='submit' name='submit' class='btn' value='Create Account'>
			</td>
		</tr>
	</table>
	</form>

		</td>
		</tr>
	</table>
	</div>
