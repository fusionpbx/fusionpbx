<?php
/*
	FusionPBX
	Version: MPL 1.1

	The contents of this file are subject to the Mozilla Public License Version
	1.1 (the "License"); you may not use this file except in compliance with
	the License. You may obtain a copy of the License at
	http://www.mozilla.org/MPL/

	Software distributed under the License is distributed on an "AS IS" basis,
	WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
	for the specific language governing rights and limitations under the
	License.

	The Original Code is FusionPBX

	The Initial Developer of the Original Code is
	Mark J Crane <markjcrane@fusionpbx.com>
	Portions created by the Initial Developer are Copyright (C) 2008-2012
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/
$pathtofonts = "/captcha/fonts/"; //path from the root of the website

/*
//How to use this Captcha

    //Option1: add this to the html form
        <table width='100%' border='0' cellpadding="3" cellspacing="0">
        <tr>
          <td colspan='2'>Please type the code you see from the image into the text box below.</td>
        </tr>
        <tr>
          <td align='right'><img src='/captcha/img.php'></td>
          <td align='right'><input type="text" name="captcha" size="15"></td>
        </tr>
        </table>

    //Option2:  or add this to the html form
				<br>
					<script language="JavaScript" type="text/javascript">
						function genNewCaptcha(imgObj) {
							var randnum = Math.floor((1-1000)*Math.random()+1000);
							imgObj.src='/captcha/img.php?x=' + randnum;
						}
					</script>
					<table cellpadding="0" cellspacing="0" border="0" width="100%">
						<tr>
							<td align="center" colspan="2" style="font-size: 11px;">Please enter the text you see from the image below...</td>
						</tr>
						<tr>

							<td align="center" valign="bottom" width="50%"><img id="captchaimg" src="/captcha/img.php" onclick="genNewCaptcha(this); document.getElementById('captcha').focus();" onmouseover="this.style.cursor='hand';" alt="Click for a new image."></td>
							<td align="center" valign="bottom" width="50%"><input type="text" class="txt" style="text-align: center;" name="captcha" id="captcha" size="15" style="margin-top: 15px;"></td>
						</tr>
						<td align="center" colspan="2" style="font-size: 9px;"><br>Can't read the image text?  Click the image for a new one.</td>
					</table>
					<br>

    //add this to the top of the page where the form is submitted to

        //--- begin captcha verification ---------------------
          //ini_set("session.cookie_httponly", True); session_start(); //make sure sessions are started
          if (strtolower($_SESSION["captcha"]) != strtolower($_REQUEST["captcha"]) || strlen($_SESSION["captcha"]) == 0) {

              echo "       <span class=\"h2\">Sorry!</span>\n";
              //echo "              <br><br>\n";
              //echo "\n";
              //echo "              <b>Your e-mail was NOT sent.</b>\n";
              echo "              <br><br>\n";
              echo "              <b>Error: <span style=\"color: red;\">Captcha Image Verification Failed</span></b><br>\n";
              echo "              <img src=\"/images/spacer.gif\" width=\"325\" height=\"1\" border=\"0\">\n";
              echo "              <br><br>\n";
              echo "              <a href=\"contact.php\">Try Again?</a>";

              exit;
          }
          else {
              //echo "verified";
          }
        //--- end captcha verification -----------------------

//notes
    A diverse collection of unique fonts can improve the captcha.
    If bots get past the captcha try changing fonts.

    Ideas that may be implemented in the future...
    1. randomize the background with texture, color and/or gradient
    2. distort the image
    3. rotate the characters with different rotations.
    4. use audio, svg, or flash

//additional fonts can be obtained from
    http://simplythebest.net/fonts/
    http://www.1001freefonts.com/afonts.htm

//Usefull Links
    http://sam.zoy.org/pwntcha/
    http://en.wikipedia.org/wiki/Captcha

*/



?>
