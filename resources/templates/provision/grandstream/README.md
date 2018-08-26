Device Specific Firmware Versions
======================
Select Grandstream phones (particularly the GXP2140 and similar) need firmware upgrades in a certain order.
Grandstream also offers beta firmware quite often, which you may want to test on only some devices.

We've attempted to make the process of changing firmware easier, by serving a phone with the firmware specified in a field called
Firmware under Accounts => Devices then click on the MAC address of the relevant device, filling in said field.

To use configurable firmware locations, enable device_firmware for the superadmin group under Advanced => Group Manager, set the
URL for grandstream_firmware_path under Advanced => Default Variables and set Enabled to True for grandstream_firmware_path.

We would suggest creating a folder called firmware on the webserver that you host the firmware on, setting grandstream_firmware_path
to the full URL (excluding the protocol - leave off the `http://`) for example `mydomain.com/firmware` or `mydomain.com/firmware/grandstream`
if you are hosting multiple different vendors firmware images. When a device goes to hit this server, it will attempt to load
`<grandstream_firmware_path>/<device model>/<firmware version>/<firmware file>`, or `mydomain.com/firmware/gxp2140/1.0.9.69/gxp2140fw.bin`
in our case, assuming we have a Grandstream GXP2140 phone and we are feeding it firmware version 1.0.9.69. For Grandstream phones,
the firmware filename is relatively static, and the files Grandstream distributes are generally named correctly for their phones
to download.
