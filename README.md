What is [FusionPBX](http://fusionpbx.com/)?
--------------------------------------

[FusionPBX](http://fusionpbx.com/) can be used as a single or domain based multi-tenant PBX, carrier grade switch, call center server, fax server, VoIP server, voicemail server, conference server, voice application server, multi-tenant appliance framework and more. [FreeSWITCH™](http://freeswitch.org) is a highly scalable, multi-threaded, multi-platform communication platform. 

It provides the functionality your business needs and brings carrier grade switching, and corporate-level phone system features to small, medium, and large businesses. Read more at [FusionPBX](http://fusionpbx.com/). [Please visit our youtube channel](https://www.youtube.com/FusionPBX)

In addition to providing all of the usual PBX functionality, FusionPBX allows you to configure:

- Multi-Tenant
- Unlimited Extensions
- Voicemail-to-Email
- Device Provisioning
- Music on Hold
- Call Parking
- Automatic Call Distribution
- Interactive Voice Response
- Ring Groups
- Find Me / Follow Me
- Hot desking
- High Availability and Redundancy
- Dialplan Programming that allow nearly endless possibilities
- [Many other Features](http://docs.fusionpbx.com/en/latest/features/features.html)

Free Support
--------------------------------------
We provide several avenues for you to get your system up and running on your own and learn the basics of the system.

1. [Youtube Channel](https://www.youtube.com/channel/UCN5j2ITmjua1MfjGR8jX9TA)
2. [Documentation](http://docs.fusionpbx.com)
3. [How to Contribute](https://github.com/Fusionpbx/opensource)

Commercial Support
--------------------------------------
These options support the project and cover any kind of help you might need from architecture, installation, best practices, troubleshooting, custom feature programming, and training.

1. [Commercial Paid Support](http://fusionpbx.com/support.php)
2. [Custom Feature Development](http://fusionpbx.com/support.php)
3. [Admin Training](http://fusionpbx.com)
4. [Advanced Training](http://fusionpbx.com)
5. [Developer Training](http://fusionpbx.com)

Software Requirements
--------------------------------------

- FusionPBX will run on Debian 8, FreeBSD 10 & 11, CentOS, and more.
- [FusionPBX Installer](http://fusionpbx.com/download.php)

Community
--------------------------------------
We have a pretty thriving community. You can find us here:

- [Twitter](http://twitter.com/fusionpbx)
- [Website](http://fusionpbx.com)

Contributing
---------------------------------------

### Requirements
It's easy to contribute to FusionPBX the only thing we ask before accepting your pull request is that you sign a Contributor License Agreement.
We ask that you sign the Contributor License Agreement for the following reasons:

1. It protects FusionPBX by you guaranteeing that your contributions are yours to contribute and not the property of an employer or something found on the web.
2. It protects you from using code that belongs to others that is subject unfriendly licensing.

### How to Contribute
* [The Quick Way](https://github.com/Fusionpbx/opensource/blob/master/sign-cla.md) - Step by step instructions to contribute to FusionPBX with links to our CLA and how to submit pull requests.
* [The FusionPBX Contribution Site](https://github.com/Fusionpbx/opensource) - The full repo with more information for the curious.

How to Install FusionPBX
----------------------------
* As root do the following:

```bash
apt-get update && apt-get upgrade && apt-get install -y git
```
```bash
cd /usr/src
```
```bash
git clone https://github.com/fusionpbx/fusionpbx-install.sh.git
```
```bash
chmod 755 -R /usr/src/fusionpbx-install.sh
```
```bash
cd /usr/src/fusionpbx-install.sh/debian
```
```bash
./install.sh
```

This install script is designed to be an fast, simple, and in a modular way to install FusionPBX. Start with a minimal install of Debian 8 with SSH enabled. Run the following commands under root. The script installs FusionPBX, FreeSWITCH release package and its dependencies, IPTables, Fail2ban, NGINX, PHP FPM and PostgreSQL.

Some installations require special considerations. Visit https://github.com/fusionpbx/fusionpbx-install.sh readme section for more details.
