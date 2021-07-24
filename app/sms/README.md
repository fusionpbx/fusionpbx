# Install HOW TO
1. ```cd /usr/src/```
2. ```git clone https://github.com/fusionpbx/fusionpbx-apps```
3. ```cd fusionpbx-apps/; cp -R sms /var/www/fusionpbx/app/```
4. ```cd /var/www/fusionpbx/app/scripts/resources/scripts/app```
5. ```ln -s /var/www/fusionpbx/app/sms/resources/install/scripts/app/sms```
6. Go to GUI
7. Upgrades -> SCHEMA; APP DEFAULTS; MENU DEFAULTS; PERMISSION DEFAULTS
8. Log out and back in
9. Advanced -> Default Settings -> SMS
10. Set CARRIER_access_key and CARRIER_secret_key for whatever carrier you want to use, confirm CARRIER_api_url is correct
11. Go to Apps -> SMS and add the DID's that are allowed to send outgoing SMS messages
12. Go to Accounts -> Extensions
13. For each extension that should be allowed to send SMS messages, set the "Outbound Caller ID Number" field to the respective DID from step 11
    - Note: Your outbound Caller ID should match the DID you placed in Apps -> SMS DID list
14. Make sure you have Destinations that match the DID's in Apps -> SMS in order to receive SMS messages at those DID's
    - Note: The Destination's action should be a regular extension (for one internal recipient) or a ring group (for multiple internal recipients)
15. Add your carrier's IPs in an ACL
16. Add your callback URL on your carrier to IE for twillio it would be: https://YOURDOMAIN/app/sms/hook/sms_hook_twilio.php
    - Note: You will need to have a valid certificate to use Twilio. If you need a certificate, consider using Let's Encrypt and certbot. It’s fast and free. 
17. For email delivery support, it uses the default setting email->smtp_from, so make sure that this is set appropriately.
18. For MMS email delivery, it will use the default setting sms->mms_attatement_temp_path, if this is set.  If not, it will try to use '/var/www/fusionpbx/app/sms/tmp/'
    as the temporary storage for the attachments.  Please make sure that you create the appropriate temp folder and change ownership to www-data/www-data.

Send and receive!

NOTE: It is not recommended to use this app with versions of Freeswitch prior to 1.8 if you are installing in a clustered environment.  
There is a bug in earlier versions of Freeswitch that can cause it to crash in certain situation when using SMS.
