[FusionPBX](http://fusionpbx.com/) — A Full-Featured Multi-Tenant GUI for [FreeSwitch](http://freeswitch.org)
==================================================

FusionPBX Guides
--------------------------------------

Our move to git is very much a work in progress but we are working hard to complete everything.

1. [Documentation](http://fusionpbx-docs.readthedocs.org/en/latest/) COMING SOON
2. [How to Contribute](http://fusionpbx.com) COMING SOON


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


###How to Install FusionPBX  COMING SOON
----------------------------
```bash
cd /usr/src
```
```bash
apt-get install wget
```
```bash
wget http://COMINGSOON/fusionpbx/fusionpbx-scripts/install/ubuntu/install_fusionpbx.sh
```
```bash
chmod 755 install_fusionpbx.sh
```
```bash
./install_fusionpbx.sh install-both user
```

Installation Questions:
- During the install it will ask you to press continue after verifying that the command ran successfully this is usually the case so you can simply tell it to continue. The install also asks several questions. 
- Unless you have a reason to choose otherwise PostgreSQL and Nginx is probably your best path and is recommended by the developers of FusionPBX
