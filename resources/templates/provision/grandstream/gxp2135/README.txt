======================
Provision
======================
Grandstream looks for {$mac}, then cfg{$mac}.xml.
I've added symlinks so you can download both files for upload to the phone if auto-provision isn't working.

 - auto provision requires
	nginx rewrite rules
	provision enabled text true
	provision granstream_config_server_path text mydomain.com/provision
	# to enable phonebook contacts see below...

======================
label.txt - is a phone label for gxp2160 memory keys
======================
Plain text is copy/pasted into openoffice template that formats the label.


======================
phonebook.xml
======================
This provisions the phonebook button and the phone's display for incoming/outgoing calls.

 - requires nginx rewrite rules
	rewrite "^.*/provision/pb([A-Fa-f0-9-]{12,17})/phonebook\.xml$" /app/provision/?mac=$1&file=phonebook.xml;

 - requires Default Settings:
	provision enabled text true
	provision contact_extensions boolean false
	provision contact_grandstream text true
	provision granstream_phonebook_xml_server_path text mydomain.com/provision/pb  # note provision template adds {$mac} on the end
		 handled by nginx (above).
	provision granstream_config_server_path text mydomain.com/provision
	provision granstream_phonebook_download_interval text 720
	provision granstream_global_contact_groups text contacts_it,contacts_finance  # these are setup in Group Manager (see below).
		 Comma seperated groups listed here will be on all phones, saving the need to add these groups to every user. 


 - Grandstream and FusionPBX supports contact groups.  To setup groups
	1) Advanced=>Group Manager=> Add
	2) Group NAME is prefixed with "contacts_".  eg. contacts_it contacts_finance contacts_purchasing
	3) Group DESCRIPTION is what appears on the Phone's display.  Best view is 1 word 10 characters max width.


 - Contacts can be two types:
 A) User/Extention local to Fusionpbx
 B) Customer/External phone number.

 A) Setup User/Extension contact
	1) Create a new user Accounts=>Users
	2) Assign contact_GROUPS (above) to that user will be assigned to on the phone's group display.
	2) Create a new extension and assign it to that user Accounts=>Extensions ( this auto adds user's extention to the phonebook )
	3) Create a new device and assign it to that user Accounts=>Devices ( this tells device which phonebook contacts to download )
	4) Apps=>Contacts
		- Type "User"
		- Organization - Appears on phone.  e.g.  School Board Office,   Aberdeen Elementary
		- First Name
		- Last Name
		- Category - is used for "Department".  e.g. IT,   Finance,  Purchasing
		- Roll - Roll in the company.  e.g. Network Support Technitian,   Teacher,   Stenno1.
		- Users - Users who can see this contact on their phone.
		- Groups - Groups that can see this contact on their phone.
		- Extensions - this is the default "Work" number that appears on the phone.
		- Numbers - make sure they are type "Voice"
			- "Work" can be assigned
			- "Home"
			- "Mobile"
			- Some want their extension shown but restrict their cell number to certain users.
				This may be done by Numbers Description field  allow:username1,username2
				( NOTE private cell numbers, could be better coded )

  B) Setup Costomer/External phone numbers similar to step #4) Apps=>Contacts
		- Type "Customer"
		- Fill in Organiation, First Name, Last Name, Category, Roll, Users, Groups
		- Numbers - Add "Work" "Voice" number
