<?php
class mitel {
	const vendor = 'mitel';
	const title  = 'Mitel';
	const memory_key_functions = Array(
		Array(0,  'label-not_programmed'  ),
		Array(1,  'label-speed_dial'      ),
		Array(5,  'label-shared_line'     ),
		Array(6,  'label-line'            ),
		Array(2,  'label-call_log'        ),
		Array(15, 'label-phone_book'      ),
		Array(16, 'label-forward'         ),
		Array(17, 'label-dnd'             ),
		Array(3,  'label-advisory_message'),
		Array(18, 'label-pc_application'  ),
		Array(4,  'label-headset_on_off'  ),
		Array(19, 'label-rss_feed'        ),
		Array(27, 'label-speed_dial_blf'  ),
		Array(19, 'label-url'             ),
	);
};

/*
0 - not programmed
1 - speed dial
2 - callLog
3 - advisoryMsg (on/off)
4 - headset(on/off)
5 - shared line
6 - Line 1
7 - Line 2
8 - Line 3
9 - Line 4
10 - Line 5
11 - Line 6
12 - Line 7
13 - Line 8
15 - phonebook
16 - call forwarding
17 - do not disturb
18 - PC Application
19 - RSS Feed URL / Branding /Notes
21 - Superkey (5304 set only)
22 - Redial key (5304 set only)
23 - Hold key (5304 set only)
24 - Trans/Conf key (5304 set only)
25 - Message key (5304 set only)
26 - Cancel key (5304 set only)
27 - Speed Dial & BLF
Mitel web interface shows html_application
*/

