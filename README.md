What is [FusionPBX](http://fusionpbx.com/)?
--------------------------------------
[FusionPBX](http://fusionpbx.com/) is a flexible, customizable administration web GUI that takes [FreeSwitch](http://freeswitch.org) and converts it into a single- or multi-tenant gateway or PBX system out of the box.
It can be run on any operating system and x86-based hardware of your choice.
It provides the functionality your business needs and brings corporate-level phone system features to small, medium, and large businesses. Read more at [FusionPBX](http://fusionpbx.com/).

In addition to providing all of the usual PBX functionality, FusionPBX allows you to configure:

- Multi-Tenant
- Unlimited Extensions
- Voicemail-to-Email
- Music on Hold
- Call Parking
- Automatic Call Distribution
- Interactive Voice Response
- Ring Groups
- Find Me / Follow Me
- Dialplan Features that can be endlessly combined for your preferences
- [Many other Features](http://wiki.fusionpbx.com/index.php?title=Features)

Free Support
--------------------------------------
We provide several avenues for you to get your system up and running on your own and learn the basics of the system.

1. [Current Documentation](http://wiki.fusionpbx.com/index.php?title=Main_Page)
2. [New Documentation](http://fusionpbx-docs.readthedocs.org/en/latest/) COMING SOON
3. [How to Contribute](http://fusionpbx.com) COMING SOON
4. [IRC](http://webchat.freenode.net/) in the #fusionpbx channel

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
- [Debian Jessie](http://cdimage.debian.org/debian-cd/8.1.0/amd64/iso-cd/debian-8.1.0-amd64-netinst.iso) - Recommended
This is the distribution recommended by the FreeSwitch team
- Fusion will also install on Debian Wheezy, Ubuntu 10.10 LTS and is known to work on FreeBSD
- [FusionPBX Installer](http://fusionpbx.com)


Community
--------------------------------------
We have a pretty thriving community if you know how to get to us:

- [IRC](http://webchat.freenode.net/) in the fusionpbx channel
- [Twitter](http://twitter.com/fusionpbx)
- [Website](http://fusionpbx.com)

Contributing
---------------------------------------

###Requirements
It's easy to contribute to FusionPBX the only thing we ask before accepting your pull request is that you sign a Contributor License Agreement.
We ask that you sign the Contributor License Agreement for the following reasons:

1. It protects FusionPBX by you gauranteeing that your contributions are yours to contribute and not the property of an employer or something found on the web.
2. It protects you from using code that belongs to others that is subject unfriendly licensing.

### How to Contribute
* [The Quick Way](https://github.com/Fusionpbx/opensource/blob/master/sign-cla.md) - Step by step instructions to contribute to FusionPBX with links to our CLA and how to submit pull requests.
* [The FusionPBX Contribution Site](https://github.com/Fusionpbx/opensource) - The full repo with more information for the curious.

How to Install FusionPBX
----------------------------
* As root do the following:

```bash
cd /usr/src
```
```bash
apt-get install wget
```
```bash
wget https://raw.githubusercontent.com/fusionpbx/fusionpbx-scripts/master/install/ubuntu/install_fusionpbx.sh
```
```bash
chmod 755 install_fusionpbx.sh
```
```bash
./install_fusionpbx.sh install-both user
```

Installation Questions:
- During the install it will ask you to press continue after verifying that the command ran successfully. This is usually the case, so you can simply tell it to continue. The install also asks several questions.
- Unless you have a reason to choose otherwise, PostgreSQL and Nginx is probably your best path and is recommended by the developers of FusionPBX
