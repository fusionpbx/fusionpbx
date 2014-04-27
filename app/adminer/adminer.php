<?php
//customization - begin
	//includes
		include "root.php";
		require_once "resources/require.php";
		require_once "resources/check_auth.php";
	//only allow users in the superadmin group to use this feature
		if (if_group("superadmin")) {
			//echo "access granted";
		}
		else {
			echo "access denied";
			exit;
		}
//customization - end
/** Adminer - Compact database management
* @link http://www.adminer.org/
* @author Jakub Vrana, http://www.vrana.cz/
* @copyright 2007 Jakub Vrana
* @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
* @license http://www.gnu.org/licenses/gpl-2.0.html GNU General Public License, version 2 (one or other)
* @version 4.1.0
*/error_reporting(6135);$Gc=!preg_match('~^(unsafe_raw)?$~',ini_get("filter.default"));if($Gc||ini_get("filter.default_flags")){foreach(array('_GET','_POST','_COOKIE','_SERVER')as$X){$Ch=filter_input_array(constant("INPUT$X"),FILTER_UNSAFE_RAW);if($Ch)$$X=$Ch;}}if(function_exists("mb_internal_encoding"))mb_internal_encoding("8bit");if(isset($_GET["file"])){if($_SERVER["HTTP_IF_MODIFIED_SINCE"]){header("HTTP/1.1 304 Not Modified");exit;}header("Expires: ".gmdate("D, d M Y H:i:s",time()+365*24*60*60)." GMT");header("Last-Modified: ".gmdate("D, d M Y H:i:s")." GMT");if($_GET["file"]=="favicon.ico"){header("Content-Type: image/x-icon");echo
lzw_decompress("\0\0\0` \0„\0\n @\0´C„è\"\0`EãQ¸àÿ‡?ÀtvM'”JdÁd\\Œb0\0Ä\"™ÀfÓˆ¤îs5›ÏçÑAXPaJ“0„¥‘8„#RŠT©‘z`ˆ#.©ÇcíXÃþÈ€?À-\0¡Im? .«M¶€\0È¯(Ì‰ýÀ/(%Œ\0");}elseif($_GET["file"]=="default.css"){header("Content-Type: text/css; charset=utf-8");echo
lzw_decompress("\n1Ì‡“ÙŒÞl7œ‡B1„4vb0˜Ífs‘¼ên2BÌÑ±Ù˜Þn:‡#(¼b.\rDc)ÈÈa7E„‘¤Âl¦Ã±”èi1ÌŽs˜´ç-4™‡fÓ	ÈÎi7†³é†„ŽŒFÃ©”vt2ž‚Ó!–r0Ïãã£t~½U'3M€ÉW„B¦'cÍPÂ:6T\rc£A¾zr_îWK¶\r-¼VNFS%~Ãc²Ùí&›\\^ÊrÀ›­æu‚ÅŽÃžôÙ‹4'7k¶è¯ÂãQÔæhš'g\rFB\ryT7SS¥PÐ1=Ç¤cIèÊ:d”ºm>£S8L†Jœt.M¢Š	Ï‹`'C¡¼ÛÐ889¤È ŽQØýŒî2#8Ð­£’˜6mú²†ðjˆ¢h«<…Œ°«Œ9/ë˜ç:Jê)Ê‚¤\0d>!\0Z‡ˆvì»në¾ð¼o(Úó¥ÉkÔ7½sàù>Œî†!ÐR\"*nSý\0@P\"Áè’(‹#[¶¥£@g¹oü­’znþ9k¤8†nš™ª1´I*ˆô=Ín²¤ª¸è0«c(ö;¾Ã Ðè!°üë*cì÷>ÎŽ¬E7DñLJ© 1ÊJ=ÓÚÞ1L‚û?Ðs=#`Ê3\$4ì€úÈuÈ±ÌÎzGÑC YAt«?;×QÒk&ÇïYP¿uèåÇ¯}UaHV%G;ƒs¼”<A\0\\¼ÔPÑ\\Âœ&ÂªóV¦ð\n£SUÃtíÅÇrŒêˆÆ2¤	l^íZ6˜ej…Á­³A·dó[ÝsÕ¶ˆJP”ªÊóˆÒŒŠ8è=»ƒ˜à6#Ë‚74*óŸ¨#eÈÀÞ!Õ7{Æ6“¿<oÍCª9v[–MôÅ-`Óõkö>ŽlÙÚ´‹åIªƒHÚ3xú€›äw0t6¾Ã%MR%³½jhÚB˜<´\0ÉAQ<P<:šãu/¤;\\> Ë-¹„ÊˆÍÁQH\nv¡L+vÖÃ¦ì<ï\rèåvàöî¹\\* àÉçÓ´Ý¢gŒnË©¸¹TÐ©2P•\r¨øß‹\"+z 8£ ¶:#€ÊèÃÎ2‹ºJ[i—‚£¨;z˜ûÑô¡rÊ3#¨Ù‰ :ãní\rã½ƒeÙpdÝÝ è2cˆê4²k¿Š£\rG•æE6_²ªÊØÞ‰b‹ž/Œ«HB%ò0ë¢>ÈÈðhoWÃnxlÖ æµƒCQ^€°ÐÔÿßñ\r„Š¾¶4lK{þZÆü:†ÐÜÃƒŸ.¦p¨§Ä‚éJóB-Å+B”´‘(ëTòŸ%®µJ›0ªlØT¶`+É-Á¾@BÚáÛ„Vá’Ä\0ÂÏC¼,ì¯0tâàŒF‡‰å?Ä Ë\na@ÉŒ>‚âZEC“ôOŽ-æ›¤^Q€&ßÖù)I)®¤ÄÀR„]\r¡”9”7_ˆ¢\rÉF80µObù	€‘î>ºäý\nRý_ˆÑ8æ‚ØÙ«äov0¤bCA¸F!Ñt—–Äƒ%0”/‘zAYO(4«‹¡ˆ¨Ò	'Ÿ] Iéí8hHÂ05˜3ò@x&nˆ’|TÓ³³)`.“s6eY˜D¦z¸Œ®¥ƒJÑ“ôž.„ñ{GEb¹Ó‹¡˜‹†2Õ×{\$**ý¾@ÝCž-:zYHZIôà5F]¦²YúùCªOêAÂÚó`x'´.*9t'{ÿ(êšwP¶¾ Ñ=¢*‰†ú*üxwråÔ*c‚žÌc|„DŸ“ÚV—–\r†V.‡0âÆ™V¤dˆ?Ò€üê,EÍ`T¦É6Ûˆ-“Åì¾ÅÚŽT[Ñªz©‚.Ar±£Í€Pøºnƒc=aÔ9Fònß!ÙuáÎA©Þƒ0iPó¬”îºJ6eäT]VØ[\rXÌáaŸ–vkõ\n+EˆáÜ•*\0¶~¶Æù@g\"ÌNCI\$àÉŒƒ€êx@WÃy¼*vuDÙ\0ÞvœëŒ†V\0èV`Gç½uµE®Ö•ÂÁf“l˜h’@ï)0@šT•°7‹íÛÂ§RAÊÙ·ò´3Û˜Ð«/QÇ]ª,sÖ{VRž±¡ŽöF«¡A˜„<¨v×¥î´%@9‚ÀF¢Õ5t‰%Ö+º/¢8;¾WÑäÚÇJïÐo:ÖNÿ`ø	•ÿš´hìÁ{Ü£•î ËÔ8ÔEuª&°W|É†„‰®Uú&\r\"ÔÁ»‰|-uÇ†…Në¶:nc²©fV­‹ÂÃè#U20å>\"®²Ç>Ì`œk]î-¯ÇxùSØÍ‡Ð¢©‰‚êcâ¡óB’—}Ø&`ˆîr+E­“\$œyNýŒ±b,†´´Wx þ-9åÕrÓ,’ü`å+œïíËŠù’CœÓ)˜˜7Ûx\r¬þWµfMŒSR¼\\èz¦ÙQ²Ì“”uA¬ºê2Ž±õ4îL&ËHi Âµ°²¹S\$)e³“æg rÈŒ©ƒ\$]ZëiYs¤õ×kW–n>µ7E1k8ÐdÃró®škÁý¢ëEÞÙÛwÂwcmŽTy¹•ë¿a›\$tx\rB´÷=Šö¢*”<Èƒ l¡fôKœ‘N/¶¼	ÃlÕáükH“õ8 .‘‘ù?f÷›Úÿã6†Ñ‡¼{gi/\"à@–K›ñ@2ãça|#,Z¤±‡	³ñwˆd¬™“²…¼å6w™^&Áêt™çœP±…¥Äù]À¼›.àãÚí¡TìîkroÀ‰÷\ro=—%æ×h`:\0á±‚ö«”|êŠ£«a“Ô®6*:ÍÓ*‡ÊrO-^–’ñén«Íó§MÆ}æ»÷ÆAya±Ý\nƒu^ì–ÀrnO\r±»¡`þT~</ð¶wÄyþ}æ:›|£ÏÐûÖÌ¡6»¤×ø®Ÿvî\rc<·b#ûàô§†î–\$ùsµê|ç‡‡V)«h‹TCùñ(Ä½ñ£Ì]6¦Þ1´!1M±¸@a´/`Û>Ù¸üß£ðÕßÈÛC/ì6à´·#p@pá‘óÿ`Zÿôýchý°\0ïë\0oæ€ð4OýOøi\0-\n«îÿ/ý\0£Dð.ÿ ¾ˆ.“Ä\0fiŒÀÈ«£€˜\0Œ”IDüç\0§¬\rïý0f ßoãÿ€ÊGüˆðeJ|\r€¿ýl	¨3ê~ðiP›¦&“É¿/µ\09	^\0r•0]¯õ ¾Â›oõŽ.ý\"	°ÐÑM¥íðvÿP€ZÐÕmpËP°ùÚœÐÞ¹ïô{§†C?²ÀkŽ“Ï¼}ð®þdöïÊ°~=‘.Ô- é	Ðm1>hûÏÛÐ•1;QI‘OPÈ\rºcßpApV«k\rQ*èQ}ÏçŸq>˜Ðu15BqQ[1fûñl«Â€apå¯ü\0Û‘*ŒJ©Q=ñÃ£Ù‘GÜäŠÕÁ±Ÿ±_ñ—ñbŒGHF.‚0Ôø	= 2P™Àó æòÏçP!ò#(3 \nÙ!1&72fª`Â/å\0°‡\"PÁUõ\$ñ\r0Ìð,QrU&2fšÒ_²Xààò]ð9\"’S'òƒ'²yð8\r¨ú§òkW)Oõ)’*Ra%ã\\i—%ò‰&Ò³+r…’3ðS`…,ñvý¦&2×L–&Pu*›-ð˜0\"Á%HÄ¬ÔžïÏ@Ø“±°H‰B–P(ÃÉ\$p&ý,1MÂ ªØ­Ã®;\rnÁ.¯Ê I­.Õ',1ò)Ó4ý²å2°u+ó3æ `ÈSŽŠpL\nt§’_*²S3;6r'h35¤55äœ‹d2q+6ñ8‘O7sC\"pm8Ò­³“6³—9òm\n@e0É<8B8©<,( ¨8²Û\0è	Ó0šJÙ<@¦ÐI¤«ÀR6pÔ­mGË\"11¤6ËÐ.\"æÀ‚ï5Ì‚ûÇ:àÜ8bêA1±;ƒ';Â?<*\$È,³Ìo= òTÓÖ/3Û#«ºÒ†¬");}elseif($_GET["file"]=="functions.js"){header("Content-Type: text/javascript; charset=utf-8");echo
lzw_decompress("f:›ŒgCI¼Ü\n0›†S‘Øa9œÅS`°Çˆ“Œ&Ó(°Ên0˜†QIìÒf‰›\$±At^ sG²Étf6eŒ§yŒÊ()LäSÁÀP'…ÂáÌR'Ífq]\"˜s>	)â‘`œH2ŠEq9ˆÊ?ˆ*)‰”t'°ŽÏ§Ø\n	\ræs<ŒPi2INÆ*(=2ÌgXá¸è.3™N„Y4èB<’L—üîi©Ì¥2Ý´z=š0HøžÐ'·êŒšÃuÆtt:œÂ¡Èêe¹]`pX9ŒÞo5šgòóIœÜ,2O4ãÞÑ…MÆS¸(ˆa…Š#¾Äàç’ïø|¹G‚bèôüxœ^Z[Çä™G¼ÎuTvª(Òm@Vò¸(†¼ÈbN<ŠÈ`æâXä1É+Œä9J8Â2\r£K¶9ðhå	 Áè`…‹ÆëI8ä›±S±ãt÷2ƒ+,£ÆIºã £pæ9aèØÅ< \\8Czôã\rŠ¨^ŽòÈ]Ä1\\7ŽC8_Ep^ÂÐÀéM1Àw\"'4fŽSX9ES|ä›…Ãk3ÄB@ÊæXa=No4t7ƒdD3µpÞÑàæ:)\\;° ÐÔð\r)8HÔÅ44Pc=\nÔ!pdÇÕQN\rÌHï'ô¸š2¢#\"Õ¥m-¶b,Ç	ƒM.¡‰-IKÓ)ÀÉe'Ž•\"ƒ´¤>2XÑÅ“eÄj:9^²1c„»ÈŽ:YÉ@ËuËã“›4òXÇ& Ò|£)Ñ’´±-K‘xŒëªÂSðè1Óó\$â¡@\\…!x]\0Œ£ÕÎÀÂñ¤áF†COÄ:à1K‡Å*†F4aˆ»¼k˜úÈKÏš¾‘»ö2l¬pÌ3J<Èâ,2Øà8#ã †Õ\rŒÜášÜî ó¤h¬„·áF±ŒÝ‰2PëèŒŠl(È\$Ö°\nJÛ·-ÞÊÇ°cc~¹FžÔîrøátbÞû½m{hð.‡{ƒtkÛBµKc£z4ŒCª9…Û«~>ƒØúÈÚ`Æ“¹C Âs:âÝÔ!cÅÙ®Úµ”*WÉHX:WÌ;Nà ¨j*Ž/(á_p3ª¡HIãKlÉn!trã£Gã­º¤tCƒ	vƒ?mã¤£¾ Ÿ¢–\0CÙö¨§oÜ¥cbf6Iþû'\ríbåÅ7h§`‚È9½iìd5’—taMè={É©ð»`NoK‰	!d4ÐƒzWXdmH°š*€ÆÛS ]ÏÐ3&\0Ú°	d%A´-²…	Âì(„šÙùQÐ}ø‚èU!t7°ä‹†˜>x‹‘t{mY¹„0Þ@^±€\"Ñ=‡³Î@t\r¡°ÎÄ+Y§.¼·¼X¿\n«I'KTŸ€^(ìD.@öÜø++@¼3•ÒÔX‹	aEì!,Yéö2-432ÔŒõMOàÖI\$q%	Ä‹G¦X9™‡Â[R\0nÁÐ¸Â PŒJy\r òBÈp\\HÃpgSÉ¼±Faejk—.4¸†C.^ yi‘ˆ9‡PÄˆe\"Î”NYŽ¬¢BHÃ#8ÑB1\"¶j\\Ú©x‡ð#¾â@G 9†2¨Âf.ÐŒpsršTJ xÚk˜–È4KIlÈfù8z¤¥KÈ‡>AKñŸ¡n^’Ø=&ŒƒAÀ*?'³^%;ðî4Ü€³†Œ9¤Q’“hâN‡™>MÊ=['ŽvHIÝJ§‘ž“ÙvÆâ’RÊtƒó<Ÿ”Ò²Å^¢¼zÔÂ‰B^öhâ'µ‚É©Ð)-'#”¤9JTÁ)Ø@jO!¨Úc,e˜j–¤–‡@H,‰ÂØjˆa™©vžZŒ>­¡Ò·µ)E`\0\n‡áTPó8L<‰c•:F˜æ‰\$\nƒííœ†ÃÏCHm\"j‹y·AÛS¶ ÜSªžQ„ðœÎÎ{T']WªUÚ)_L¥˜i¬mˆOš‚¥è„þÔP:g¡{¸’ZÄ—ø.ÿ{”¨‡Dh\n»ÑÁ‡a­\r]9¥tÜà!XA½[È°¦ã—Cœ»×\n:•”haœÎÚå\"Ý¢a2Lmƒ·Í\\	ûëp5÷@ú«@m£ì|Wö•ÀÂ%È|u®áÈ+hKÃL&¢Ï Þ3ü.XWÜÙººÈñ*qƒÛcÃé‡%.K¿“Š_”)®uÔ2W\$O]…d8’ê»gÁ?mFyúly¢%Ó‰ö²ÍÜDQÇ.uÄ²ñ‡Æ¹ø‚ÉÛL‚ý,Þ¬†è3ðæjƒ0t	a”<¬\0Pr•mNs8ÙŒk>M9, †á±ëBÁþ±xÖáƒ£zoä¸™uB?`é¬§&ÂIÉ<¯¥ÍÑeÅYåsÊzÔ‡*±.'t»µõ‚zÛ)m*4X=—tI=ýnÑ¦yÌÞšééc2¥¡`öääØ.Y¬¿Ö:éÎK“N’µr06Ó_rJ‘ØkÃtOè|^Íˆ¡çz\nÏ¿é±•ˆ<W‹1n.¨X·`•‚gúVG4Zÿ­rë!ÝÏÈY[ÞÓÅz:LäDˆÂ@T	¡0Ô`Üƒ˜pjSn\"YÁÈg	á`÷}Äšð÷‘¬\n\nä4®ˆ\rg‚¹O7Ü¿b§è”y¡Ì)¹E¯Ãß)w>Ü~urš³Þ29h‚tgB#¹•°²ôF‚p(é@¥`u0÷Ñƒ(flG¥a0bZ7J@ÝI_PZ‹‹yq^Ëà7î°¸çG‰3dƒ˜ÐêÑ3¶é“„0ƒÛàŸïŸ{Ö¸»øˆa6½P¾ƒ4W	d:¿ü„W\nêt4ï‹¾.ñþDÉy°È§»85‡«AMôL’Xw5Ùese³Ü÷C	#ýÝËrrYë	Ç®!žÂî€Âå”Ÿ@/\rÌ ›0¥wEl\"›OéWŒ<Q‘ÄÛ ñEkÀŽSQiÿdŸý\\kÙ¬ëü8×ëþHŒ²\"ëbL}×%½	¬Ñ-^ð _âh\nF-.í2nj¬ÔËVMàxnj¾¦m\\\$°¨¬ñ*\n¶ÈÖ'¢~à¶ Z@º€¶Ž Vâº€L\"ãˆ†p†Ø5€ðO, ÿË¹\0\nžª-0¥\r4”pÔäbÕ0fÕp¶mg¤i©þO.(ÛP9ÐAPH+ÐNHpf¨§4?BàMð®·ãJF¶.îô\0èà°Èà«Ôi…jÆ€Pþ+(¯&æ»ãaŒÖ%l]'Üïl^@(œ5ƒN fsŽ˜Ðûãô bz ÃÏe>îº¯p²¯øk éD\r4aŽNéÂY({ïD­ŒnÆ†ÏÕ¤>jÄ¨1€Ü	¨ž<çl-x³\rËGËO	Qw°•qw«c‚Pñb\r¤Ì¶ç­ê‹	Á½‘§âdñš6¢Ç€Ês‚à¢ŽéæÁ Ð¶±r½Äj>«¤Jž°âŽüÈ®±bâ3ê(F¦ÑzÞ¤Ðrª`Oñˆ¥ËX‘ÿ\rZ¶qü\r ì1\$ŸÏ¿gkìl­Ìr+°ñ†ækfì'ò5Ò8®4ë6Û\0Ê-´.i~4òE<\$²JÆlru2F;Bn<’%#lq%ˆû	b=âå#Lë(HJ1b%\rç¸¼ãz‹ô‹èG2£±^8wêñŒ‚^%¯” îþ¾G­*g 7D\0^‘r²c„Žp’ÆL,€ó°ï* Xr§\$ Ê8ð×,©*¨D‚ÓÀÔæ`Ð\n„Á’Z¬“©s1lÏ1Ç\\{àÂ.I~`‡*3ÍÑì]1“FÍ‘1X	-£%#ËÀÁS3LÓl6\$Cr‰C/Âô\rÓ%,È|†“€ È†ÇŒ–Ü Êsu8«J˜©Žä¬—9ò–æh¸ìNÅëŽÛÒë.ðüÉPôFtïÃ\$¾3\nðFB/ó=4÷-ìÌÍÔÍ9ì# O:Ió]#Å7Bº—,:ÉÍ< NâDñ@ÖRˆ®\n€Ò#ˆžzÑ%8i:\0Úz“' Y‘*¯&Ôä¥/K¹Ö¦²«ÓU4 z€a>4‘\0 f*\0å*TK02Í<Í0SfòæÍ?Dôa4X-¶uÎj\$E6\0Næi´–ææ\nÿc9ñH’´²§HIb—ÈFÍÏÀ‹þs‚R~t»I”¾ 3úÒº‚Lè;%	0p.B®FBnMKÅÀR¢sDÆ'èa”èÅÔìÅÔóD\r1ÍOì\0œ²˜És´gL^Ì…àÌâO>lÚÀC<DôHº-4<àä™\"V]`¦/BŒðU&±Ó¹-#w;Ñ^›MÐürŠq±0œ-œo¨~pKÀ×‹	pšÎé\nqè,4ÁWÁ\$Fºnl\0ÙM‚Lš\n‰…-úm®\0¸)Z@ÏZ‰†˜ï•¢^@Î	 Â&ÕdÖäý]`ž¬ÆÖât\r¯„'\$^Rü'àO]©æSÐ¬Ø3î5â“˜F\"Q[uÉ[ÂH\$Ío`6Zuªðmo[•Í]ÍXÄTØ	™]µÒž•×\\c›b¶:–bæU\0ØW2Vb ëeˆ2/ºd%<YRt7ì'f§0‘uìruòhÇU@cTsÛVãÇgFÎ–{_-_P²E–‘T:{ÍVÖdüÉÂþ-ˆIc¶þ°È¯ÍMëþÿiv¯ÿ J¡\0m3@JXµRMU_²žðºˆp²5)kçkl-\$,Æ“\r&›\rÜýO§(oÈûk+rê Õ\\àP7\"*^åP˜\rc <>³‚t#~Ræ\"»en‹ èƒsŠ„ã¶;·D	—ItÀËup t@À‚8d\0ž@ÔlTw×r —ww·~ bŽ	¨ŒJ æóu®\n€ , u;jÖ·7s¦Ã{*„oÂ>q†<-\0 	à¦\n”œà‹|Â¹rcÆßv7µi7O{ECâ(èœ1Äp¶yÒ‡nØàð¤²àZ‡à[r>8ÃX‚âç·á{¨¯~j…~¤î(à°¸(Y`È¯7_Â»z%vd™'‚%.‡\$w/.=Æpô&¶—¹…8V5R=ÃN„4†×(ˆøfuâç„øJlåjÜu`zXQ.–X!¾‹´‹—Økq—rpû˜~¦¸~T£ÀæiÂcÂfn¢Žx¸¾@ŽS€Ë3*6Û¤b ÷ÜûØ¤Ýûrçppú¢n=)Æ­‹\0ðÈLú(L…ÆnË/§-88Çs\0zg½Ä\n‡ëL“KÉS!mÃ&–æÞç\"ÌÈ×b8}BXZy,Í¦d _X‹ð€^\r1 zõªñ‘BuWÞ7Õ;s8ly^BªÂÀð„fZ`Þ“ôä ø­‚FyYg–¬!–ñ	Plíš£O8ó„f<Ió,˜ ª\n@’‰ÀÛdp4j\0*¤\rl]œyÊ\rùÎ[=”Ý?+À,'N¼˜}TYs\$w®fØÉ› Ô\räD(àM#\$Ýh¹_ey‘…Ê+²\"K4\0zYì DÆ]¢.Ê* xñÿ£Ï÷rLœÐ˜]\rj ^ç@éš)÷“¶\ròÀQrr'p0À¸à\\P¦,\"ª-sÉ’PÃŠøŸqôo‹w‹¸ñ¡ÅÅ¤'%ycÏÓvó,\rK«îÜP…U@èçˆÊAé2Ñå¢È¥q|ÒÒ	2\rœ\"ÃCi¯†?.¨šÉ@è‚<Ä€î0€ÜQôt‘ty=Dº[FÔpG\0RÙ³ü‚ÏÏ'Q@-6“2Á»*Á/@PÁÌÄd;7[ŠØ’!\"zÛS±-~o[„D!*–Æ®0N4	Š1ê—1ç8ñžŸ{l\$DÖ	G¦|G\$v!ræ‚Ó-3Tm•Ä™‚\r°ïq0Ì½N˜·né™H”SF dùQRóå»Úc’ÂÍ‡Õ²S\rcC.nÀäiBx-l”v·@Üáá›Ž!(“HçXÊc„g( žó#%ÁCnû(P‚G9Âì\"1Ü7ÀDGÛ²1ï€So8µÌSÄûqÜ.ˆ¤pôÏP h€e‚ª0Ö¬k+¸@ cÁRG§hÙ ¸LÈû†/âç`V.FA^\\lÜ¼öî5\0¸ `\0‚E|C®jImPtyÇAnGu'pÂd-åÄËÉ05püÓ&ÀIÄu%¢\nOÜ<|2\$úø@¨\rîFDRÎ^`1À±°f9Ð`è/÷Ï ÊVÌü†;eø\0<<ðü€eÏdÏ²çÛ1Ò²‹Òè®¥kÏùêýD4V¤YÑƒÇÁÒì”åÂûw·¶ð¡¬kpÖÇ;þrÃÆŠö^\niŒ™\0‘¬…¨c:˜¯)¼y¸\0zYvz9Ö]Üèâ«¡`WÃYžÍëÖƒ…Í‹˜—‹Ø—Œpe«#ØÛ1ûñfãõÝÚµÞ']Äµ€?]Ä‰-’Ööï=ôÏú˜æ8˜oT¨W=õàâ\rÔþ\\Ñ­lÍÍy¶þÝâœÕå¹àËÎÖŒq=!^„Ôâ…äfqêª€Z˜³”\0Vç]=ÏFæÉxšn`˜\rä?‚tð XQÉ‘çþtZnq<ŽJ\$cöàÜã<Â€íþ’íàvñÝkÀ¤•èeÖ®Þ\$¯^uë^ç)i¢ŽíçŸ—ƒwÚnßª¿ªSÉ<˜>ÜæGŠž¥3À. é<•ÀŽ7ÞÝáÄœßmÞ¥Vþiw×žî ó0ÿ/\n\r%1”\0yèKëñ¯EëÄ\ršúâ³šñÞ íü‰§¨Þ¸™eíNLêÇùæ:CÈ'?ê~óéŽ6 €è\$}ýjf¬é•R\rõWD°÷.T\n¢èNÙTÿ}÷_÷E|í“—UÌ}ÐO'ÀØIŒ,Ê–7Í¿½€…:h±ØÚÌì„Ô\$ªZ0¸èDV”`t XnÒvójGÒsë9l°ÉËÒªB¸ã“€”rSF<;Øg%v(ªšÊ(Q¶×¥P(\nFlýè?j\0oÝ€3±à{ÓdxìË¡‚üf—àbÄûW-Þ¸,QuÀ,+®Ëa.Y”Àñ‹l[¬õ%ÈWSxò²\\	¿D×G,„l”Ô]@LÄÂ\" ²|p…?l™Zaà8õÀ…0!Á/ôÂºoø\$vïÖáî`rß£îæG\0‚,Àë˜	Á0YPN€œ'0ˆÁUûWƒ0B˜ÄØ2Ag0gDÌMòB4Å&1Éšˆüá™w÷¤µ¶†Šô!™0¶„`-­7›F)+‚·(\0007(rË\$9­ LÅ€†¢‰üTãÁ…L€=\"°ÑKQ.N<ŽX@¤}Í+ ˆ@‘È¦¡,…ˆ·…”áñf˜ø~½D/Å˜jhZÇŠ…ÀCp©Aš§2C‘ÀÃ f=`„*É|-ásÔK;,äê\rPxT\"}îöC5kÒ]OæµÓœ½ÎŽùý!âmç_ÀF	P~ð¡BRí½˜@\0l’wßó‘’œeŒÛmDjÕö°¦0%¯ùÍÀÖ P´§”Jx€Ö&%ÈB’:8Ct \nÊ!B'#ø–ÜþC61ÔMb\\€u`õß\0ö&Š7xJîy€Dà\rL~3`L&Ÿ‘É’Ä`ÑBìP¢ˆ‘Ä!õNcüSW}ò!«	ÈÍ\$P\0^ÐéÄ\rˆSŠàÀ^tq?	˜*GLèP™°úÎ+ÂmtY\"âøµÄ,šñ\r¬CÓ,˜Q¬ILÐb*\"(‹Äê/ñˆ'PZÄ¨\nµ™­µFá‹„X¡Äò\nø£èî7š<~æƒ7U´”[y*ÇŽÿô–Ðå7\nh\nZ¾Ø•	1\0g-Ò8\0#˜F(ÌåAlúKcfOäÃœÁú€œ@Ñ\0p:@\\zÑÂ8ñ¹áÂBcðM‘+afßÆƒô…²b©\n¯PŒ\$žµ‘Žðâ {”û@%àw°í™TSÂ²È0ÓX‰œfê&=IÃÞŒ{\0äÀáš‹ÜahéX\\sŠò\$±@¸‚ò;È\$ò)oCârD¡œ·Ð‘‘©Êq×%KcL\"vPž#{+\0{¸±”±±-Ä…DLïHÁD‚T*ñD‚º Ú²D¤ÐZ9Z\n›æº?à”(<HÀ`D9îI%š?€Ä™õ½8€b~ÌQjÑÏUÒSE¡ÌÀÊE¥ðË²S’€ü›ìž ”INJªQ9˜Ìôp.†œßÇY´äÔ©6Má“‹@Fï aîI‘f,þ)¬™£¥¨f#Š\nX!i/Â\$”´ª‰T¡”n,	;ÉZM²{,Q•®où:õnò™“Ì›™M)ÐòIRO2“Ô©äã'5lÉ1æçß?ZÕÊBbp‡ b,1„Ñ£!\$/µÖRD!ª·wÀð(*E€Êð€\n4Ár,\"ŠÝ]ö`î¿X‰17[JKJù!º©éÈ–ò…á!}\$}éÀgŠËÌL©G§#t@²Yá;ë(Ôä¼|?é­\r:(É~\"aŸªÊþÆ_\"qâ˜!8 •\0¸Ñ‹`cC}(­F_+8LYcªŽ™^3.…U÷@]!þÆë.P-Ì|fAU¬Éí.#Š2&PD	zdîÀ¼àLHÀŠÀ)}\0X„µ\0Pû¦ÐÍc!¨™äÏ¡H7Rƒ^À(DdQÀJH(V*Æ[OœX1ÀØ9Áº„–WL¾I€JD†L\"kÓ7˜\nBë0ùˆàc3yžÊjÌ0Øà|&›ˆ&ú@2¨ vä»ž\0/‡ Aàâ»g\"&e1šµ¡€'\r.åYÂdé!˜â¯fU2Ê²eÉ‡›	p…3p-‰PXTŠ<ßÃ\\\0LµDI™ˆ%žá;2hÓ5áM@C5é™ŽfÈÎ\ntØ¥»6D–\$ºv(œ¸\"\\Ç Â‰R&RòôðDÇTO\r†\0›¼l\\pä98‰êb˜QÅ%3Cx9d8I°„Ìo`'sªË¸Dâ(Ih¯!¬—\0C\0Š¹À„•`\"§†Öhp0…TáPØp°Ð4“' €¦¢)©¢0z€o«á\0÷ÐDHÄÆ¹©„\\È ¼\0ÀE˜½Æ˜5é¬€€sZ#p¦\00000=ªœ]€Ë+cûLœ&x?3¾èFq\0ÞÍPg÷-²Ü€Zsý¬‡ñÑJâCøê…Pà[O¼Éöû£F'ÜoÃ€—Œ3\0ç§Lžˆ¯—­#PÐ€ìœ3\\Û@À1!ŠMÆntnBÃ.š[1sjQ˜<©®\r=¤B!`PR‚÷­Â2÷!ÐÐbÀ+¡°WBÂ,<¡“ 0Ê¬å<”—Æeú“Ÿ—ŒéÑœÙ€É’£)ƒ\$ÊT>ð³E¹	 s}*8é`)ªI\nò–ªÈ–,LÊ¶UÒž?ü¬D¶)ReÌ8µÖ‚ˆÝ q¿\nÉþ(>E@rŸøh7Z[„u­¡¦‰2'ý@.Sa´ÙVxJ)-0òÍdÃt‚aÀ@wJî+\"µ\\à8ñÂ ¸Ø”ó–Š³(p(xZt=>hžp3hg?£œÚ„\0¦¢\0.¨ˆèô1¦mK]éÈ/˜ü‘ÂÇJ.€ü[cN•	fÏ 0-Ò©”´©¸ÀN‚‡§-jBu	d|2‹üiÁÝ—qÑÐï)ýW˜Ú9H»²ÀºAeÒÏš(ÆiÑÇñt¼¹å„Œ*¤Rõ“¨\n<¾gp HF‘§ªÒ°Zyò7I 9tÈPI\n²†Õ(¨¨,2e#iV²ð\"¬pÎ³ŒÕPŸì¶š§¤»XªSj‰W‰! ìrÕ8„d&ê±\$aÄKÕ!uUŽÚ2£UÔü/@_œÃ-8‘F°òÍP@ñV˜ÚUd·æv—z«ªéZÈU¸·Hªú@QV*²L„b ,«C+A4ur@„ús|ge©¡QvM«‘ß	\"d«QYÇ3R¶À[:(D\0¿ˆ>\0ŸuÃBJ5SVªÑ›l†LŒÕb”Vï˜%ux[hUÉ#Ÿ¦½CnÙ›%ÜÃJýKoJU eÎ²&\0ŸÕ0‡ÿRJ&T¼mcZ»-ÞtÿÓhy^P›Ñ\0ÙrwÕ{@ó^øtÉeÙP?2]KN¶qŽ&~ä`.,¶dÿCºòÄ(/\$xùô*Y±š\\´TÝFh±QšÖ9¬ ^AÈhñ[3=®®5ó|ÅÝxµ€ÔÎ¡U7‰ÙT-P<‹\\;b”°ñ‹Ì¢j%…:ÞÄ\n %ŠÂ«ÎËrÎ<žÅ¶©-@`;{-‰ò‰À6¢‹p'8’22ÞªE/§³&	àRJ«¼a\nW³i9¬ÞN˜OB“\$ù\n¨ÖåEã/25o(à›o)É“ìÊK\n<e Ï°+BÚñ,²e«f“³Ýµ™A’´Ll­Ëyg…1–›e¥+¦ï\"€ÊŠ³¨a	eøÆÿM¼öŸ&À¡ÊÂÖsÂ’S‰*¢ÐÏ°:ç×d’Ï ¸ÇpÎv™FÊŠgë³HÓ-EŽYöº\n¢ôŒzgÙ²2•ßv¼¶Ä¨ñtZ\0‚PR ©dpÐ4¹˜A„lôm3jKN–âÒÍçµÜx@ËgÐ€ˆL9Ãj·Œ à+s®Ýê)¸çpQ7@34IsŒI1‚G[ñÑ‰ø%V¦•¼ÌÇg£\\Ùò‹aúËÃËoØ7Û”Ò6ç\r­º@_lR¡lÐuÊ+…j‹zJžÛ–÷¸Ø­‚:GŒÜr3ƒÀgårV—PÚÒ·0Y(EP\$´[P  ö%ŠÈÀÑD.Üîßä^È€È7n™?ánRÏµºí!h`Z\"â…¸³8×NI3Ñ)BUŠS{†sj”ºÝ0÷Nzv°YÆÀÁ\rpª[5­¦\0Ýj2+ÛÅ,ü˜ã…îÔ¥*ÿÝ¹%©,åÞÖÄ¹µ¾Ó -¡åa\0F£û¼\n‡Žô‹+çè[±Zl ?š…—hLÉ¨Q9Ú²@ÈóøÀ9C ¤\\/_›ÙÎÄ!.oãáRP:”°þöF Cì’qIY,3Õ(´Išqã DG7=½æáÖû`¼]ÊéÐ:Mí	Ãb«Í×Í\$ÉA[qyº6^f³¤nõìhéÐ^†€@)½ B@u{€ÍB’NG#ÓPïiZÛÖÞÜ.³½cOœ‘{æ^Ìw¸¾áo‰}kjú‚”¼Þì‘d£Û±5 SiµB†E1»#8©1›nm]ï%P6Ÿ¬f”«ü\$Pt§5Ö.D%µÔˆ+#Yµ¶X5lXgSJkøHÞàW@.@G¹w'b†²PY‚²„Fã 4\$1ãCxÒ\\ÄŒ(±FáA&ØS|#ÑæBl\$¸+NMi}¼ŸER€,=Uö@·Ù3P	QÊ	‡ƒÑú€/@œ¾€>Lñ|³»¼%š'#ÁRRD@Ã‚¢‘xC!\\üE˜F®Ã/ÓÜ!tùsÔíüAW¿Õý­ø” _ÀÔáE€!Å&càµ“]eFKG.+@Ý^iAÃÊ§'òZåOaãS52O¼ÿ÷ãEF1r·t-ã\0Ò8§½ë®ï~¸;V•õ†D¤+ðÐ7T ôV>¡ýì	^òÉdþÅ©â×¶õ­Š}òúDÂ\r*‡ƒª¢Ðø(òê¬R¥ïƒHáã¶µ(*¢5þæ Ð ™¡˜ú(-[)ô(p>ÔÍo˜ÌuÐÓx¸6&Ï W˜Ú>…Ýè‹úøTÇ˜½Ç1÷Ž›\"î\\‹gÁlº1çUz£ÿ¶qÇÈØ+V#L~XÅôY‹’Kˆ…4 ¢9A]\\q8‚ü„J÷ÕwÁu^¡'’˜K?û%ç„íq!9,Êê„â’Y4BŽMÐJ…W'¿úµÉ–B²AÇÂc:Š¹èá…y+iícpé'GL*ì²q²R—£Çc¨Ùð‰€Ø?ª°D¤‰Ÿ0€ \\¯MMò\0Ê&I‡ÉƒKË)uË—”Ü')—úÈFÜaw»(Oåå\0Úe2èdùÁ!ŒÏ@#DG6ˆª½¢.LªÀFò!µ+ ]Ù>h½I\0ß+-€x^gÐt¨‡P^Ë0ê–•°œÀ­™nÈ¾4«ÀÃše£jÎ[OÓŽÁ°]êŠä4Í*°\0ÓìÔ¡O5”]¥áÓ‡“èU”|ót˜³Œ& \$8€EÀ…ôUdýj¿£\0q’Ñ³d@'<í—/ü¹kpö\rf7*IŒ‘ôØ˜@Ì-^j—‡ýÂWòÉHÜæ44‹Nð&ZÊ–uóQ•Tå_'@;Ï€Úr~ƒ¡h/@:\r¬v2‘,ƒVHŽ°¤º[«ÀÐŽk|Ó!6aú ÉÆV\$°jÎµ˜f3Ë­VqL„ãŸ#ù‚ ~a2†ÊmØèý™Æ¨]G&È)bä¥AA¦ÍBÈW}òC_Bàt˜¯]L.¹’¸ð§Ae®™€¨®@È•W6ÅÞ›´àÂ8X§”\n\n†y':C@¨8K£(è2\"Ã_PÃÖD`èôó;ÁJ„Äü4ÌáHb§Z^l5âŽP( T(\0žf§û\0t­ \0ó?¡îP´U~’é{\$Â‡—Þ'­¥÷`õv¡†4\0^ÚJ JÉª±ù€íè…tCmZá :LÍ ;²N³#€,ÞPÈºÐséÄ¥\0DÂêÑ¢>>ºŠj)àðNSòt8\n©ØÊ¢ç§ŒÍ#xi”è@Â¬â>zrªµèm{k`¯‰×Ød¤‘R¥ê¯E•F\n:B÷}F(Éa™HIž>hÔl£î<\0'‰(†}‚¶1¤IÑØ¡Ç€eð½~Ã£ÎhÛ()_ÂÐtv¿e-DÙaÃDz“0éÑgY‰J”ò½šQèòþÏ…s´Š•öA#è¶gK×Ìó¤4{î¶\n¡¶@Dò€1ìˆ‰PM ìÞ’@^:8¬nÑ‰äãDnhÚIAT¶a4íEO§ú7ÖÜT\0004	õ¡6_:<È\0[¯!vH‹h.'Ü\0’¶¶QX¤ù Eªu`v]çŠpôâŸÛþáÀO¸­¥ÑÎ+ÛÈ—h•¸\0íôf@)ÜÈ¯š)­Âî\0[ˆh¥6ã°¼y\$\"X>’>éÂq¹!Omwu[¬®PØö \"‚¢eœ¾¥ãã;²Tym„Æ»cvóvÍ6Ý´_ô+§G`	2uEÝmqUBÛm{5¹µm~'‘÷NŽÞÞëö¦ Ýðlm›‹ÛvÜÀË·\rÓ)l¬›ã:ÞßdŠgÝ\0îú›°4Îã÷jâqT;nî,	D•TüÁÚ¼–þ÷yC?P{€w8\nz\rÌgs›zOÇo¾2@n'wàtÞœUd=t8›ƒ©à–å÷ZPÝ…8Ÿ_ÛÛáv’±®0êÔ«/Pt(\n[NëªV¡~n»ÓP.º´…›gü)¼UÛ5ÄäS¤äcÒq®f½]­‚ø‹ÜVÖ¾¾5ô#áÄp§]r±—”¢^\0§ò§Šy@……è´>…T-à+àÜ»X‹´aÀ±þ±\0*D[–Qþ>¹d*¦ã¦ÞrE@Íñâ+ ãç¸ÿÈ„ Lïg#·ÙèœQ&„úñŒFñƒ…Ñ¢§ç‡ŒAÚ£í}žD[•\0fÀ*€¼ÙÒì,DêØ~D<\"“VB>@àT\$¦î@Ì€3Pä“F‹'¡ª×FHs«ñ««bÇ‡GÂ56®¢ØQ	íÒ3*;L#cÛ(Ò×^èË)ºH\\–Áaµ|ÍÀ!Å3bHŽÔ±g)‚02Õ;1bÇ“Ø&ÂðjnŒX¾½·V0XãÖ³µ¦××Fd\r ©‹HÈçgaL¢q	'S\n<¹¢8\n\nòööç7¹¥.x°º6í¸21¼P´J\"Öè\nÂt6eU\0´kÁ€9ÑzK¾v†Š¤P¸¦LON±Óªà€È]éð ŽP¸ï <×B_…~•³ZG•éxc÷AÓ0Ö\0ÿ¨šÐÂ‚íz·µL(tñ8>ÂÐÝ HpØ÷<Ò×9ù¬E^{|O<íæpïRa>nº²ù4|9aÏœ±õÅ›x±ç\"ÊÆnã~b£—°—Hxú’^GŸ¸±kÎ¦¤s¼Ðô");}elseif($_GET["file"]=="jush.js"){header("Content-Type: text/javascript; charset=utf-8");echo
lzw_decompress("v0œF£©ÌÐ==˜ÎFS	ÐÊ_6MÆ³˜èèr:™E‡CI´Êo:C„”Xc‚\ræØ„J(:=ŸE†¦a28¡xð¸?Ä'ƒi°SANN‘ùðxs…NBáÌVl0›ŒçS	œËUl(D|Ò„çÊP¦À>šE†ã©¶yHchäÂ-3Eb“å ¸b½ßpEÁpÿ9.Š˜Ì~\nŽ?Kb±iw|È`Ç÷d.¼x8EN¦ã!”Í2™‡3©ˆá\r‡ÑYŽÌèy6GFmYŽ8o7\n\r³0¤÷\0DbcÓ!¾Q7Ð¨d8‹Áì~‘¬N)ùEÐ³`ôNsßð`ÆS)ÐOé—·ç/º<xÆ9Žo»ÔåµÁì3n«®2»!r¼:;ã+Â9ˆCÈ¨®‰Ã\n<ñ`Èó¯bè\\š?`†4\r#`È<¯BeãB#¤N Üã\r.D`¬«jê4ÿŽŽpéar°øã¢º÷>ò8Ó\$Éc ¾1Écœ ¡c êÝê{n7ÀÃ¡ƒAðNÊRLi\r1À¾ø!£(æjÂ´®+Âê62ÀXÊ8+Êâàä.\rÍÎôƒÎ!x¼åƒhù'ãâˆ6Sð\0RïÔôñOÒ\n¼…1(W0…ãœÇ7qœë:NÃE:68n+ŽäÕ´5_(®s \rã”ê‰/m6PÔ@ÃEQàÄ9\n¨V-‹Áó\"¦.:åJÏ8weÎq½|Ø‡³XÐ]µÝY XÁeåzWâü Ž7âûZ1íhQfÙãu£jÑ4Z{p\\AUËJ<õ†káÁ@¼ÉÃà@„}&„ˆL7U°wuYhÔ2¸È@ûu  Pà7ËA†hèÌò°Þ3Ã›êçXEÍ…Zˆ]­lá@MplvÂ)æ ÁÁHW‘‘Ôy>Y-øYŸè/«›ªÁî hC [*‹ûFã­#~†!Ð`ô\r#0PïCË—f ·¶¡îÃ\\î›¶‡É^Ã%B<\\½fˆÞ±ÅáÐÝã&/¦O‚ðL\\jF¨jZ£1«\\:Æ´>N¹¯XaFÃAÀ³²ðÃØÍf…h{\"s\n×64‡ÜøÒ…¼?Ä8Ü^p\"ë°ñÈ¸\\Úe(¸PƒNµìq[g¸Árÿ&Â}PhÊà¡ÀWÙí*Þír_sËP‡hà¼àÐ\nÛËÃomõ¿¥Ãê—Ó#§¡.Á\0@épdW ²\$Òº°QÛ½Tl0† ¾ÃHdHë)š‡ÛÙÀ)PÓÜØHgàýUþ„ªBèe\r†t:‡Õ\0)\"Åtô,´œ’ÛÇ[(DøO\nR8!†Æ¬ÖšðÜlAüV…¨4 hà£Sq<žà@}ÃëÊgK±]®àè]â=90°'€åâøwA<‚ƒÐÑaÁ~€òWšæƒD|A´††2ÓXÙU2àéyÅŠŠ=¡p)«\0P	˜s€µn…3îr„f\0¢F…·ºvÒÌG®ÁI@é%¤”Ÿ+Àö_I`¶ÌôÅ\r.ƒ N²ºËKI…[”Ê–SJò©¾aUf›Szûƒ«M§ô„%¬·\"Q|9€¨Bc§aÁq\0©8Ÿ#Ò<a„³:z1Ufª·>îZ¹l‰‰¹ÓÀe5#U@iUGÂ‚™©n¨%Ò°s¦„Ë;gxL´pPš?BçŒÊQ\\—b„ÿé¾’Q„=7:¸¯Ý¡Qº\r:ƒtì¥:y(Å ×\nÛd)¹ÐÒ\nÁX; ‹ìŽêCaA¬\ráÝñŸP¨GHù!¡ ¢@È9\n\nAl~H úªV\nsªÉÕ«Æ¯ÕbBr£ªö„’­²ßû3ƒ\ržP¿%¢Ñ„\r}b/‰Î‘\$“5§PëCä\"wÌB_çŽÉUÕgAtë¤ô…å¤…é^QÄåUÉÄÖj™Áí Bvhì¡„4‡)¹ã+ª)<–j^<Lóà4U* õBg ëÐæè*nÊ–è-ÿÜõÓ	9O\$´‰Ø·zyM™3„\\9Üè˜.oŠ¶šÌë¸E(iåàžœÄÓ7	tßšé-&¢\nj!\rÀyœyàD1gðÒö]«ÜyRÔ7\"ðæ§·ƒˆ~ÀíàÜ)TZ0E9MåYZtXe!Ýf†@ç{È¬yl	8‡;¦ƒR{„ë8‡Ä®ÁeØ+ULñ'‚F²1ýøæ8PE5-	Ð_!Ô7…ó [2‰JËÁ;‡HR²éÇ¹€8pç—²Ý‡@™£0,Õ®psK0\r¿4”¢\$sJ¾Ã4ÉDZ©ÕI¢™'\$cL”R–MpY&ü½Íiçz3GÍzÒšJ%ÁÌPÜ-„[É/xç³T¾{p¶§z‹CÖvµ¥Ó:ƒV'\\–’KJa¨ÃMƒ&º°£Ó¾\"à²eo^Q+h^âÐiTð1ªORäl«,5[Ý˜\$¹·)¬ôNô\n«ž[Ðb÷ƒà|;‘éîp»74ÍÜ”Â¢¨ÐIŠCË\\ÞX°ç\n%øhØIäç4Ïg‹P:< ôõk¦1Q™+\\ÚÈ^å’ ™VèøCàòôWàÃ`83B-9F@ànÃT>»ÞÀÇ‰-–¿öÊ&âÜ`9q¦…Çßä‘“PÜy6Üå\r.yñ&£ñ´ÎaÌ‰ÍÃE8Ÿ0 êÀõkAÁ×VÛT7ñpïÆxØ)Þ¡~¤M½ûÎß!áEt§ÐùP\\èÄÏ—m~c½Bð\\\nímŠv{µÎù9`G[·¾~xsLî\\±Iõ®ïâXwy\nà¨çu¯áÁ™S£c»¬€1?A¼*‡ùÍ{œã½ÿ´óÍ¿á|9Þ¾/–òþ¯Eúï4æÊ/¿Wÿ[È³>–á]ÄržÊý¯v¹~B£ PB`T¡H>0¤BÒ)ð >¸N!4\"‡À¦xW-ÅX)„0BhA0à½J2P@>ÈAA)„SÎôn¼ìnìO˜Q¢¬ÇÎÊb®rõŽÔÒ¦âöàøïhèí@È‹’î®(–ð\nì†FìÂ˜ñÏ–øÆ™…(ìÎ³¤ÛP\0÷NÂõo}¯‚l«<ønÞø®ˆâîlëoq\0/Q\0of*Ê‘NÑ½P\r/îpA°Y\0p\\ãï~³ÐbÐLh °!Îã	ÐPöîd÷.¿ïy\no\0áÌËÐ¶öPptùP¡ovÐ‚knŽ¸\0z+æ›l6÷°©¬Êø0’äð¹P½oF€NìÏFô¯OpýàN`ÜÐÖ\rogðá0}PÍ\n¬–@°”ö15\r±9\$M\r \\©\nggìÀÂ Ø\$Q	\r‘“Dd‰ÆÊ8\$¶ªkþDâjÖ¢Ô†ö&€ÓÀÊ ¶àbÑ¬˜ê°¿‰›	ñ=\n0ÊÕÀúºÀPØ ~Ø¬6eö½¬2%Íx\"pß@XŠ±~«æ’?¬Ñ†Zelf\0ÒZ), ,^Ê`ß\0è8&´ì¨Ù©‘Ñr€© ©ÃkFJÂÂP>VÆœÔp¨²8%2>ÂBmÎóØ@ä’G(²ä¨s\$Ž dÕÌœv†\"Èp°wÇÆ6§æ}(VÌKË ‚K¬L Â¾¤éÄWñöqú\r‘þÃÌ¤Ê€QòL%’PÔdJ¨¦HÀNxK:\n ¤	 †%fn‹ã³%ÒŒ¿DÌMü À[#¢T\r©ÀrÂ.¦LLè&W/>h6@êE ÈãLP‚vÆC’ß6O:Yh^mn6£n¼j>7`z`Ní\\Ùj\rgô\rÈi2I\$\"@¾[`Â¢hMý3q3d’þ\0ÖµÈúys\$`ÖDÀæ\$\0äQOf1ƒ&‚\"~0€¸`ø£\"@ZG¼)	Y:S¨ê†D.S%Íˆ’ Ð3¾à d¹ÀmÓU5‹æ¬ó<£SÒSZ3â%r “ÎãÆ{óe3Cu6³o73î—³ÀdÀL\"àc7ÄLN ÜY Ê÷k‘>²Ž‚Ç.æpäì2øQôÐ÷“¼åÓ3ÀVØ°WBðDtCq#C@½I”P÷DT_D´:ÔQ<”UF²=’1ô@\$‚‰6Â<cÆrÅf%Ô¬,|“27#w7ÌTq´6sþl-1cPÕmðqªÊ\n@ÊàŠ5\0P!`\\\r@Þ\"CÆ-\0RRˆtFH8µ|NíÆ-€Ædòg€‡Ò\rÀ¾)FÆ*h—`ö €CK4Ã1‹ÊkMKCRf@w4BßJÁ2\"äŒ´Ó\r1Q4É2,\"ô¤'¼êx§Œy—R‚%RÄ“SÓ5K”¦IFz	#XP‡>¨âf­É-WX\ršÜê¤pU´ÕDÔt&7@¶ÂÑô?’©ÀÑ ªµ£}O1½2†‡2Õ#UK*¤)ôê¸‹Œ0o<> ]HŽš„Æ¿rè›LGNª›ê˜W%–™M^’Õ9X:ÕÉ¥N”òÕêÔséE¥­@xy’(HêÆ™Md×5<52B– ð–k!>\r^J`‹IžS N¡¥4'Æš*œ*`ø>€—`|¢0,™DJ£Fxbèµí4lTØ•û[¨§[é•\\‡¦¨Ô –\\{­Ò6\\Þ–’ öß(#mJÔ£,ý`©I³ûJ‚Õ­ÊÜèlß ûj…jÖŸ?Ö£kG»k¬T9ÀÛ]3ohuJ©ê¢®ÑW•\rkÕÏ)\0Ý3Õ€@xè¹,³-Ê	5B”¡¶˜=ÂÔà£#–gf¢¡&Üß·Z`ä#ÄoíæXf È\r ìJhô˜“À´5rqnzõ§­sÁ,6’oÓtD´y‡äÂb´àhþ—Ctn˜9n‘ í`§X&¨\r'tpLž7²Î—¤&—¨¼l¬Z-Í¬w£{r—¤@iUzM¿{rx×—mÒSBÀ\r@Â H*BD.7¹(Â‘3XCV Ç<WÔÑƒÝ|d‡q*@”þ@ÞÀÊ+xø÷Ì¼`á€Ï^™Ì˜ß¬__•ND­X\0Q_D]}tõYÅúp¦f€wÔÚ\"â3øz¦nÂ«MYñùZR\0÷¬Q¤?¸{†M3†•£*×1 ,¨\"Øg*U¡*²¯ˆÌ«zÒŒW5NV2O-|€¾ÉÓñ,×]‚B×dí\rŠñ/OâtÎøÃï‚Ì0‹xÆ†ðŽ½Ð®OCë8Þ-0Ò\r”ÿ0à·õ„@]¤XÌŠÐÎð\\\0¾0NÈï£Ñƒ4ëi¨;ƒØAtê¼8X—x¤\r†…Š“‘ìÁ‡øÝŠ×Ê7¬<ö@SlÈ'LÒø9WŽ ÊÎ¸òÏ¬ÖËì¢ÍÄ±•ùRçÌðÌ\r¾Ï ÂÏò|ÜXÐÖa÷ø7y€Ù\rwe¸Œù„Y!ƒ˜Eƒù’´šÂcRIdBOkË28[‡mÌJŒ+L ÈÅÙ¸OXpføÓ9ÑDÏ›·¦ßªw“@Ë“—Y—…¢Õ÷\\yäAcÙ£ƒXgš™%šôó’Â1“ï“j	œX†9CcÝ‡àR¡¹‡”QFÇpdÒ= C˜÷ýš\n\r¥Õ‘ÔóšdjŽÙ«’xE¡Â2FX§¢x_¢ØÅ£Ú5£™—}q¨Åí¿¤M%¦ZM™:\nÏzWšX7¥åí¦:ÐZi¢npY;Žù>Ê˜í£ÙÉ†:6Ú;£ZÎX0ƒ“Ì¢#ùýcàMyU…i2,q¹FËšÈb­J @ÓgGè|4ógÈÒmzWõäÊ	¬)™Èr|àX`Sc‚Õ§ÀË™„óc—¥‡û!²B²—±”»/}{4JÂ\0ÒÃn»Kuz @ÌmÚÑ®€ß­yÍžÒyÖ\"º)u¹ÊÂÙã¶Yç˜s·c¶yë‘¶š‡··y¼—Ž¹7Á|·±|—Å{Ï˜*)°Ê4Y`Ïµ[v¹‡¤­‡û^NX•†¸‰†ò‡W”©û·‚7†;¾_‚‹*x™ˆ¹Ú\rùß¼ß‰xm+¾mû¨Ú™	´»¹‹\$\n¾l˜);™²„|Ù ßÚ™¡:œNÚ :„‚Š_È8N³¸Uœ5;¨p+U–L‡ò\\‡9í¦Ùñ“›¡»ýO:I’šû zQºœ¡ƒ¡TëšÜ)ªXG¡æ»ÅJ{w8“¾ûÅ‰¸UÆù\$ôàÃøü›PxTY¾pjh·¾J×Ã€›˜JÙ{‹Âð@îÇ‚³ øðZ‡ÌÙs•¹hË˜ç–XÌ\0Û–lÓ–ÌàÌÈÎ¸Îçìó‚Y}˜Ÿ®ü^Ð@u2ÀSÚ#U‰ˆ;Ãˆ|¼¼•¥¼™P\\ŸÊ#ùÊ|ª<®Ý\\³À›žJÛ‚,öœÀ•\\ÅÌšEÌú…‚]WÍlÁÎ,£ÍìÉ–<åÎŒÛ>YnÎ),Î™rÎüûÔ¼å—âº]Èý	ª\$õÐç½Íq„DJí=•Ù÷•XI-ðÅ€äÅÌa‡llÃµ]\\“w(iÜCÄ×ƒtƒ‘<i-u[uVŽDÖ“¸QÂ¸€xb€kæLI­.kú›@ÞÀ„ÜN‹“[ñ¼l<o=-]1`è”¼ªdš ÜMÌ7‡@Û%C=]ú›êÀ/|-àÜˆ¾ÉÞáqÃã•âíùâ*¾C¾òO~ÊQâòså`·ç(âòãDÉßÉ²¿à[ãþæ>Éká¾R™uéÞ\\+>)3íûPÊßP§Óí6ÓËM%º¡¾pÔŒœÅAÐ3qmu2ÖfzƒÛ¯ì4s‹	´í`ÛŽ‘ì°-kÊS%6\"IT5½‹~Òì\"™íÂUt_	TuvàÖ½ä¶Yw¤†­0I7¤’L‡\$ú¿1Mí?íe@3Ûq{,çÀÏó\"&Vi·àžÔIŸ?¾µmõˆ™¯UWR¾´\"uiT‹‘uƒq­Ÿj\"•GÃËõßò(™ï-½‚Byîê5øcÝõ?Œàwñ®°ëTúî’`ei¾½Jtb‰gðU‹3ËëÉå@öá~ê+¾Íï\0MïGè7`ùïÍ\0¢_Ô-ùñ?\rîVÿµ?øFOÔ6á`\no†ÏšInª¼*pà™öeÙí\"T{[Ð“p^÷ä\nlh@l0[/ö„poóJKÖX“ñ€ü<ª=€9{Ç¾6ç–<eßAxãÀùÇ‚¼Éá4x[ÍžLò“~>!åOQxš{ZVFÔŽ`½éÈ~Ižß–“øL)Q[ëTûôM›àþT²*BC¤~	æâ‚ä\nƒò¡gÃˆÅ…p9zKÉ–ówzO9di^›'‰+¹ßïDz4ägHAº¯Lyô¡\nr€<IêjKQó¸Snô==\r.Âo7Â½Êé%a;‰kÏãmX¿›Zi%P¨iÏ\r­€¾ýµ/©…L`pR0¤Ž&õ—I (Øá\\.£*m„*Ž(ÚÖŽõ—\$ä†ÆÀ÷\nw×ŠÐ¥…8a“\n&´Â‘žÍUmª MÖ¨P+\"Ly„ó?¡M\n€2’	L\nbS ¥NäùÇr¶!w¥jw`¼Â\$îôƒráè…Êaáv±^Ãq­F‰Ü6•Ó¨i*™Ÿæ„ì_xõØ\n‰fðIê:B&ù6@É“KED¡úú·QD(V`.1\0Q\$íøF­¹H®’Tþ€zÐ†‹Ì\rªjkzM€ÐÀ®Y™À(61€”x‘+®%dj¸Æo\nÂ¦¬\rg°ï\"ÉŒ´ˆ—?Œ1- 3hÏXÖÁ)åyjÃ5r¢N±#Q¾¼Š¸w{_þ¡øG)ÂÎÙ1i‹Ì íç¤<Z‹ºpX³¡Ö\$â?¥=%.´€Ò®&¾­%\\±8w­!¤µa4œ<JB[ÐÄº¦u4‡%êŠ×47‹Ä%gÑä&¸€Z(@	€E¢{@’Ð#¥–2Šh@Œ#ñŸø™ÑŸ¥£@\$8\n\0UŒìjãA(×ž2ÀO€Š8Ú€ž5‘¸Œ¨@†ð&'´\n€DŽ\$i#ŽÀ#Ÿt\n PŽTs#]P*	àDÌuc› PÀO|pc—øËP	ÞŽ¼i#Ô}ˆæ:<ñí\0\0¥ÀˆÅ¥lo#}ÏFÜR‰Tp@„À'	`Q¬ycTp(ÆŠ@€eh\0‹˜Õ8\nrx› cþ<`NŽˆã:)DY\n*Dý‘2{dZ)A‹Ú4±²¤€cZLð2ÈÊ<ñò\\Œ\$r#ˆþÆö7ñÁŽ¥°!û€´ü€Nª{O¼@\$<	Ñ¢ðVƒZÒÆž52.Aù#D0 \0´ÀI¸û\"P'H	²_)¼x@Š€*úàAOh£hI)I²L1¦’ìƒäµ%áJI‚B‘þ’g¤i\"p÷§K2}’ä–Å(CËÉÍ=²t”xCøÐ&FÄ	r“ÒoÙÉ@@'”ñ€%	 ÛHÞT±áˆ	ãÔ˜:=¾)\0.ñ°]Îâ5 .ðæõ(pÈÀL!à8­\0ˆ¹	éR\0L‹YaÔbkÔ°ˆ6Ä)Y·éˆî •Ô®£	h³zZ¦õ±’IgÎVO3oœ­Lgà3ËY2ãÛ‰ÜDoPË`3Ì¸ec-‰r7í‡2Ô—Dº‚Þç‘B¼‰Z•¼¼%å/I{MÃ\0pÐÀÌ.`äÊÝo*•Ô¯%T€ý\0 &–iR\n™+Éo€ì©–\rÀ^2q”Ë©\0\\¨I@‚	KÀ#peC*!>€/á%|È…Ì’ÁÞŽüô\$è)çÀ§1P30(\r¢+\nZÆzž„))\0*®\0kà€ÙÅ2¼–Ï…(–E86å¶s—tºf&”™Š¡´“+;”Ø76&ãK–_Ž(›9fÓ,@-ÃÉ4l\$Û‚e7\0ù±:l“LÝæM7.\0ˆ³|›ðo–JÛ©ÀÎZ³u•ÌºŠ'Èy{ÅH,#\0vU@9!¼¥	Ñ'†¨&„òGôøß@_-Ù¿³ºt;Üê¡:©µ€²u¡<—ˆL†iÙÎš_ê€Ø£@U6°Îù#ä_€L'~ùæ/Öm`\\Të']=Iäât°Çž¸Âà)ÔÏqùsÉ9Âa<RPÂº|tžút&5°äs©lî@¾	ÞKÆwS®èlÍ:9úN®wSø|·göÉØOùAÐŸ<ë‰BÈ€\0/àz@´	ÍÏÁ•Òå†=?=iÞO‘ŽkÓŸ=\0E@iâÐ\$B× hO\0Á>DÖP´ó‹UäçÑ†j¥HìÂ9F¬BcCi‰é­BwMŽ§tÓx€PÀÙM‚?p“®=—äì8ÜÔý‘Ïlg~¨˜tÁa©€%]b\$àØ\rˆr„èÄa,6ÅtŒàW)Ž\0U¨›F˜	|æì“¢ˆvh¦Qú*¥Oƒl.C\$À\\ ÐÖRRÌ<lcù™&Cj3Ñý%ôZM¨öÀz9GpY’â¹£\0i\$Dµ‡d‡ñzt[')[)Q¤ØêÞkÁpi0·#cÃ¾‹ôNE¨ô(ºC2L	Æ@9hÑEJ5Ò,šh{&Jzö0n€vª©>[€j“£Û[œ]ƒK•ýRîJë>.;ù¨íF=RÚŒŽ<råÓM¡=—Ô’¤ÜhØ^Y\\RmnËÐð Nn*g‘¦ôÒÅB¬·5^QÒ‰@O¢°x¨¡HIÊT ´â9½)(‘œ&µ‡}A)PÊ\\/êô…_Õ!ÌH þÚ‘¥¤ù\0éBá­\$z4ÓTYu‚J’v\0êƒ”¨…%@æ32\0Sôm€--Gi@¸úQÅ%Ñj©YÝ+FuzlSž—”ÜW3ØÅ·OrŠU\$EÔè;¹M©¢\\€Ô±Äu/£õjeQªš¦§,#J¡ªXPÔ<UH•TVVé#Uê™ÔUbˆOU´DZ‘â¢µ£Í8êÕUJuS «À‘g)XDZK‚•¢Bî\n¼@2Š©ìx@d&ü ½eÜ«Ià@ÊFwì¬8“©\$Ù'IºV‚V†U\$²ETÎ_ð*ˆd¸/áFCÓYdp§vGƒ‰3‰ ‹Ñš‹L^(ù`áj”÷2S¸ºcÛW¨ÜJQYiÖHB”£ckœRè\nþ²U\$jê\n„ZAi€î»¢U*wKDRxW‰LÂò­ˆ€+fÚŒ@ã¨A4¢àGz…R\n²5‚b¬\\_²Ÿ ­ô‡¡á0¼C@¤\$X\0+Å]¤ÑÂè\"?‡n¦€+QIj\n»x\r€ôB`S¸âM‚ÈÑûŠ\r o°@‚À6XÀ\"{±\0µãb ¯)–ÁM¨cMðW ä¶D_áÎ±Ðv@{cÐ:¤®%[%‰C²þ1¼Ù;AÆˆÌTn› \0º a²pážóe~ÙU5 s©V†Ýe|M9‡€9 hË@æ¦\0êÙ~É@.³	l€· Jv]©ºD§f€7¨FÌá±³ËùŒ,/+:¾‹íÚXIi­\0U¢â@Nµá´\r Ê¢,².½i¶‡ª³m_ûFŒàÖõäÀYiUÔÓJ¯!©gûLj‹ãÑú¬D“iKAà6²õª-U«KfÖ_N€\0ö-3©ìÀã3+¥dãiûD	\"ö¯µM¥ml‹L…XÜãã¯¸Œ>‹&|UÕÑõ`Ïh¾ù2¦ÑÐn6Ý…·ÉI+ØnÃ©-nDÃ×`„µ†®°É”°@ã¬B!;X™smÈ¯·†pC`‘p5Á°¬¡O‰%Z/Õè5”³é#CK`‚XˆªÂcb°Q#«§Qa»–Ž…ƒ¸q…èpÚÝ÷)™®G+~Û–ß÷\"ðlM_^zò©šæ!ÌÉàE«”Ð¥’®šÀ‡ïa úØp86ì„åˆn+oì’Jâ¶ö¥¾,¹¡ó‡¢ºw\n¢]ÍƒpëŠÛRÁõ'§eÖJÕqµ'Ü¨%£'€nlO‹h@>NBÈŠX5,ˆ‡‹¢ÊrGr¹ Z l\r(ªË‘jIù†±lŸ¬%b‡;s+±× ¤Wg7¨)’*e…¸1µ•ÞÑ3“L e@(»p\0 ÐÃŽèds®AñÖD\0Ã\\bD§\nuê/&1¬ÞXR×¥Eæ¥‚5¡Tœ\r§}7õ§”ªîÔþ”AÙ¬áÉkâ\\–øöÍµ´ŸÇqà2Ü€öZ-wo´“tßZùƒ‹¯]ó-yq2j+Õ†¾Õ­Ã«¬€n¾XA«Û\0†\0º¾+S•+ïY6_BúV7z®nZ@Ì†²Ô·Æ´]´-UMJc*¢ü¸´®í¢s\"ß+\0·ï¯x´B3^«öà0\r÷ÜÀÎïÁcðÖ\\jÆÆ*¬P-\\Q8ˆÊŽ·…l•cË%XþÉVB‡}‘,€þ;(‰`*Qú	\$áïÛrßÂ{ÁKøìCúÖ%¬\r¥ˆx	ÞøQû…,¶Ø¾¥×/‰vàä\" pÁã¶ð~ Óáã ÅJ5eãü®Eš-^âX;c²\\©¶×¬m‹´7£?˜6C*åº®†,7®HfÄ/Â9eÌ0[@ñ¤!bê®íÅþUÐ‘=›Äi.Jocñj;ø—B³\0¼ƒï]Õ”ÑúvÙGÃÜ8àO\\\0ÀÇŠüO©›\$Ž•.&	p‘\\‹H1bØpø’:F\"8Å¶…þ‰ŠøVx©ÅýµR®–xä=À3Æf1Š+|Ò»\0ÂBÀ¼kbÌPÇLÑ’£ô\$zÌáàÎc	¢ÇÐi,Pcb,pÃn(¥Æ,¸ì`'/»~êÙkÖµ‚Îp€q-›ÁÈ±¹VÀÜÜ†Ü\rÙž	\0á‘‹dSˆÓÈÚÍ+º\"Šéˆ­1\0(Ä-’Ì1~útcªþfý¸àBÛ‘b}Ø ’Ã0<1\r°¨¨L’€»\$¸ˆ2d\"1ž&ì™Æ€BÃ³N…Ô\ràB\rrƒ«\"?vädäZá±.\".\0?wä¼9€oÃà\rÄ0¥Ñœ!¢ÍdR€‚ë¤¶\0‘ÃÇHëÜra%ÐŠØ+\0yrƒH¾sÏ’4W#œ,\$èô \0„*xBó\nPÌòü|„ 8@/ \0ø2U’°ábíÝè¢ÂÎÎªxÀ!¨d§°óúNÿ3SÔ?£ÑP»…€(òg\n8·‡ppŸˆü€S9õ@‘'  Ç\0úyµÿ\0¦y46¡H<‚öÌ×ô\n`S’ˆ…¼ÈûCY¹’„”³jp:\0N(ÓŒáX4ŒkÌÈÓgßDy‹<–n4™£ØrS<ÒÏýˆó¯?¥\nÀÇBãúf('™Ì~dgÓ™SËÏ?<³ÓVg(1™éãæƒ2ù£ž­—²)ÕôŸf`éZ€¼a“>t{ÀœÉŸô’>ñø\0ŠìPû`O¼\\sŒ<õ?4äwÞ~³ÜÇf@z™ÿÍ~hBW Ìø³á´ŠxhA¡¡ÜO'=úPÖŒ×²Üö±ë=óúc[ysèÌûgâ|¹‹ÏæŽ³%™Mè,Q³ÆÒ8'X žhlUs®…§Ù¢ú é4ËÃqDýÂx*8g§NLšBÈ–¨;§}%eû@YìŸv ho!\$æ›NcCXì³@Ð;YH'Á°@^ à·Rf^x„\0^osÜ_fª—“;¨Ópj]²:’Ô¤ïõ.mLêl\rš®V¨\0ó@Ü€¶Ê\"ÓÕÄ1%Œ!_êô@-]8f¤ç -Õþ±äa]Y¯WšÏˆh`(‘¬äJë@…ÁÖ\rˆ—õ€Y	kB(€xÖÂ:5˜B\\QkO[:Õ0˜Â¼¡­uk›X¥\\×P\0ë[öx¹ÀÅ®`ŠRIGÕÐk5°ðª§YzÍ×PÒ™¬=†l=áõÖe€\0ç•2=k` Å[K¼‡Bê½Ìû8ž¶C±Í}k«c{#ÖØ¢„ølŸdfF.Ìµü-›AºÿÙ6º†K­’•¤ÐÖ×Pàv„'¢lHiAÝÚ8C¶“®	G„`GbyÙ¾·Í- 0•Ä¬;[*_ˆ¡ãmlH{(;Uo¶ÕÑ*Ä]Š,Ä‹åŒÖÆÈþôXË“¡80Cµ°K	­!N¼õÔ(I`¨³	V¾Dv½§íšwá·rpc,ðåŒÃÓ\0ää 9~s»Xnã¦‡¢žŸr[ec·4dçpÅi	\\…Èe2âãl±ÄaZCk»gl÷bB„™¶7x%¿êè½ží€Å»Ùk`ì\nÁ(@Åº«®„5åÝ˜¥Ï­cÌ‡#t›–Ü–éãE½}Å„sñ–Lvö÷E¹ï\nQQÛ”Þæú76}õ‹Or»çj§b¯%@7‹˜àÛµßh³wÍ¹÷n£kÙ`Víq·±Íòï³~›™ß~ø„4{Œßþå÷ë¾óË;òßï8p2mP+ dÖaX8&,=Òn›}ü!/øK&\rŠÿt´H™Ó)/øYÜ”†6@å¯=}ðŠðEU§lKÃü\\kÓb[×â1Gø®­M­)™J¨xXÚEïTä¾	/¸\"-‘ë…<4ßxDˆ¥ÅíÐpÄ(¼3ÞÊŸ·ß´'È+Û\$\r†¶<rí×n`H\\t\"þ¶70=ä·Y×Wéhsð­\rÏw¼~°!ù0@6l‹\\† •§/þBò7’¼‰–ßÏ>Fÿ‘Ü‰\\¶¼RÙ¾-Çn‡€þÜ§\n¸?F~†œaÞ×+xÉÁýëñ¨\rœl,fúCß+­Žîw•i¢GøÛËî.X!¼_à71ymÌ~ñ„œDå¦È7åÊé	÷š¼ÆåîûÅGÍ¾gówƒàb/89¯ËxÑ@!R–9¸eÍJq˜Y¼hß'3¹ÏÍÄ¬*÷ñXw‹Ë®^—ÛË	¾7ŸÎî5óÀûåÖ`ö:î#È+Û­0˜ž·œS¯ˆ@0óo7:&~r(Z·‘G1zÐþˆ€·¢pÝÎñdNŒï“£›`ç¿/Fz@8Ñt0ŠZÌ_ ‰ªÎ0³™{Úè¿Lén•‡×‡oEËÃÑâ=rû¡‚Gj]õ H•¥›²Ò·…»ÞAf+ªÈèVº•º­mžœ7ýåßB‹ÛÓî*q‚þ}cãwØ³=Û„g¥»wE¢-H·°€»·¦½&Rh4—ªMêžZÕ_L½©]WV'ÁÕ¦§Íñ\"uŒ@-ÜaMÃsº@9êL:ÈÕ’]ù#‚ÝaëoybÝ\n\0[Øêrðp*}Qí‚bwßÛÓ¦?†ºâÿ;Vc¾Ê°›»	«.Ûsç´¢XíÖ°ûy·R=§&d”ã·rûO«žçõ2Åj!Ïux¥ÜÎÔ§R{NÖ&øµÑ»®5ö„}£ßvyÛ°Ž1o8Z#žþ{ÛNärû½ÝÑï‡Q:BÕHzW{òïW{:ìržÞ÷ó¶}D\$§j7)àP€÷ëÁÐÝCvV¬X—¾ýdí¨D7óá®€·¼,Ôh»÷á_ø]·^í—qÏƒÜŸxO»]­ïŠö¬?p{Æ\"ˆðOŠ8Qáµ?xw}ùJâ?9kâÞüx½5buÛ&÷øÏo›ÅÆ^ñ†õ¼Ÿ¬>õw“g]çíh¼#ä?+÷‹ mï(³¼¹àÿ/ngŒ	é5â5<ù;‡ñüòÈ…¼Ë³½œxÍ%‡³‘;ì(³ÞVóŸ–;Çço-ìóË½ëòÿ.eänkpËÂÀ_ËFäXõ9ÓWjQ¥ÓàCBØ§åv3R=°ì†¦;aÙ][yËÈ»4Þ/¢|óÃ##v	@_Ç­}UçM>ùßÌþ1§»\rC£MúqƒCÞÄÆåámc¿,kzy4Óí¡K˜¦Þ„ùçÑ>˜a‚­!i1çp\nA`çŽJà¦x V,‰\n}éKÚÀ+’”¦s¸JŸvÞî(S=Ö#:M>õ…°U¤ã zûŸÚžêÀ· y4&=@MöàXÃ0P'{b6 E È #Ÿ”ú@M‡¿ã6uOuüKÛ_\röñ)Eî°)G9ìúZ>‘o#äGÓäl@¬ð%ŸLê‚Þ´™Õîsñ0 Z/ºýôð+¥¤ú@_“ÀÀ…Þô=Ãïãý||0§ÖÑOÑ~íy|OÛ~Þöï·ÃEî/rHjŸ\\>Ï×€t-ï™ýŒ,!`ù7ÕÀVoˆG9¿k“™ô¾Cö‘ý“îžà÷è\nÀ }Ð!üQ-GÛ>ýõÿv;Þžð¼½éïoÈ|@	2?(·¾ðÏjIÃÞ\0SÍì”>­øXØÈX|úïºþ6	4ÉÈ?vø÷ã?öï¸~7‚ù/á½o||d\n_¬ûoºäÒ-ãëÆk7>ü÷_Áþð¿’Sáÿý×Å~/ñ>~¿ö¿ ü‡Ü£÷P ~Cå?+Yÿ@‡òà*|À*ß2ýÑõ>mó}Ûç¹Ý¡÷½üoÉ|OòŸ–øŸÝ¿R“{þ£î€ ýpþØ#P“öoÂ¿¶+Ü \"¨’z3à\$¾ˆú0	ï¤>”+é\n¾žü	ï©¼ú£ôïß¾²ücù  €’ø#íà«>ŽúKáP>›\0Ûê/©¤ë€¨“;Ýlâ£x>ƒô`\"³–ûÜHÔ3¦ûÓ:À«\0’š9ì³›\0ûÜiÀn?@\nïu¿>cîÈç€ªjCP'ÒùBOÔ>2û³Ü #¾&%(0£óÒùˆ3ÿ€°ù«ú€,@|ãç ,‰KÔ\n/È@¦û³ðéÀ|šS )€©¤,ã@xó9Læ\$¤û´Ãì€« 3@ëKîà*¿@ý¤pFA`&@{üïÂ€¬“	0J†¨÷C÷b0?ÃßéÌ?pþ\0 ƒ	#ì`¾¢“3ao#¢z:`>Aa úor©Ü¨Í¿HþXƒý¤ŽœO€ªÍÈýáo@Õ[ôÛ3¤øÈ[ÃøÀý*OÛ#~\\(ÍÁ´P\np+Áº?+öð4\0u@ÿPUÚ?xþ ÂAÊ\n´`\$Áj\n´ð\\ÁÞÿ,¯šAîý¬oÄªûè¡oAù¢BÈÍ´°ý¤´€„?(€'·\0ÐtöÑpVƒÿ°\n´=ÊÑT#Ï>òÎ«CðŽ‚­Ëñ \"?xûÐÀ;¤ 0z#¤>ü\$P|\0¥ô%ŒÁƒó7°Bbþ\0ïáBkÿ£êBbÿ˜\ní\0°þR6 >=çX	\0>#¢Î“ELÛ¾\"ŒØúƒ÷€˜>ú60 ´U\nÊN/Ð¼ŒÐ\n°14>P	ð¤€—”I\0‚ÎÚãü¿<>ëá€«A¬÷KD`'ÂÖû€ÿ0ºÂï4\r°½BØXx\0ø›ã–€Wœ¯¨€¦Ñ36PÃ>d?Ì\"còÂ+l1ˆÔÁÇ¨ü >hþH	b®“<£þBéú3BC:÷#DÌæ=óä3ð.Ãý«DÏ›¾r?2Bé!Ãh¤+ìß3€“â°G> ý\0)C\rð*Ð\r¿e	PðáÀ ôÂ4‘\$9P3‚¬?s7ÝAb“8üÐåÃ¦ŽD3°C³hã¤IR6‰4Ã¢Îô:p€B#„PVC)\$<0õBoü=ø¾ºùãçÐúC×L\"÷A];Ü@'BÅ”;@¦>ì8PýB]\n„@¹CE, *ASÝ3	¼A¯Ã\0™+1\n?Kç¨Í¾3Ì\"°“B©\"N1	DI\r<0Ãô@ýX/rDQTC/ÚÄgTBž?…È\né9\0œŒ90úÌ÷Ãè#ó\$â”7öDY4%1%Db?+óI\rBºÿ\$,±\0øüÒJQ+€¡DJþ†¨Ñ ûèÍÄžù«Ý/¶€ø¸	y?3D\0&ú0jä´SºDy¨³ï/â¿ þØðÄHlèÀ‡ô!q+#Žh	¯…ÁäÀ%Ào{öØ´T>ëôF¿~|‘HBÊûP0 ¤> \ncênD1ì°ÒÀTL8qSCÂÍº5ëAyø0°\"€W	23Dµ>Úô0D¶ûhjˆØÃ˜\$LQÃŽØü±_EO’5òC;\n  Á©”X‘3¾@[ÏÎ@÷(ìéAtpò€¾‹ë‰%Å`Dïš³iTP*X\n€+€Š÷(}Ed]ÑxB“”Ñn>€ÈüÀ'Ép\0±8\nÅÅð\n²Mñ‚A¼ZoäC¸Žœð\\>#<@\$¾sübÃòÆ¬bàÂÆ6>»ã0£´M\n8¥3èyAtøˆ‰4€øÂM/µ¤Bÿ¬DªC\0Cíd´Â ñ.«Ô#o¦#?\$h	F„ÍšN/ŽBêú¨ú1¡Æ–÷DhpDÆ” qžÄÍ¤hñ(\0©ªJ¤Ç(0±IAzú³Üà!¿üPµ¾+„ecè>†?(ÿ]Û“Ü¬æ\$¡\rD!hñ¯*<°¦€…ãê£öÂ×\$*OìC©l.V\$>ªA\r#Ÿìˆä@®Ðü<@\">Îhú©)@üDRQÌÄê’dr 'A|ÑSöð€žÎ\\gðáF]”QÖ3”>Ø ,€¾?A-ØGeHúÑÓ£~’“â£üGnŠ6†GbûüÑÞ¶ü3±½½°PŽ\$/‹â‘ãÆæ³Ý1DÄñ-CíÆ	#‘í€œŽ\0û°¢Â0>ÜY°M°?tM­=Êq±èÀ|ú«EŠÆÑPÃè4D23‘ô=ç°\n€B•P\n€B•toPFô,H¥Bá\nT]#é?E¨ûìÛ¾{‘4€Ž@ñ¿Ÿäv€)\0¿Àû±âÆY ìƒñàµ!\$„OŠÄˆ÷Ü\rÕ@Ù	d\rm@ÙÄ+i\rFë¼5I\0c`\n€Hxœ‡‘ÿE% RP‡@ã€°94F„H%€ŒÑRS1DÈ øšNMÀÇôi`%Âë ¢A\0+È2KLƒo‹D ó9`&\0¿ò4ÑÙBƒðïÄÄ‘#kp¦Å ˆÍÇ;´x2Çs!KâÒ@1#à	¨í£L“\$`ÐEE“Œ°>—{êÅãLe¯yC±ÈûÑx@!Kè‘–¾;Ä	) úe°Þ>i!¤e±à©!¤†Ã£<sã\0D0oÄ?Ü0ì@…%²>\rB×ZN`\"@-kDÑüÃ;DÑ14D?\n5-\$8ü°Ö’ÒŽDÑd4>KL‚íC&pvFã!LRÇw`ÿ©\rHq“DìÜ@º?3ô°PÁ4ý\$•i1€†?3åRx#6Ãñ2MÄ²ÿB0É>Uè\nQÈ(ÿéÏ|\0¿üy²yHÚ>„j¨ñ¾!%ô|0á¾U þ/ÐJô ²2ÄŒ“’J³¢÷ÀÀ/ÂÉ\nL6ê£bûkâ­?³t£ow>¥Óæ‘<D øŒ!ÏÍ?9¤T1:CTÎãâ°EÅì‰°OÄ7|à)GÎ£æ’ €³\"lMxJ“ÜaˆõDÏ\0¼eñ—\0²>ÄƒÒ@¤I\nkèƒîÁi¢òDä€¸\$3CŠd†Êø\"@<‚\$²\n?I€ù+H8 ¬–N\$¢Å³€øñt ºÊå+º”MwÊÖJÈ.‚˜\$\rž¼\$i*…á†:¨6£8´ ``Ž…¢˜f\0>'ØÝŠ`×–* 0¯æËˆ’²<Ãs²Ï^Ëoª®à:Ó»þéµ\0€ËVÌ¬€7\0ò×ø\rà1KbÈH’µ¤=-´·2º\0Ò¬¶’ÛK|¬·)*€Ï.“Àä4 êY8m@Âº’¼¶›ÊääéÜà¶\$:ÈàäJæ¬H*M¯Ë¾E¨E ØK²l¼›€Ù.ä»ÒéºFäƒâ=¨ÞŒg )Fvü oáÆÂ*\rà\"\0„ø°0#Æø°þ	4Ç\0˜\nÐ]J…LoÎ¤?DÏëËÂBÈÔ¥\0p0 &¾1 üÀ )Æ£7±\$G/”oïäA¦üdÄ)(BAcýQŠÀý1£Å…1,;q\rÀ±dÅ3LXó6ãóÌa1ÅpâÌZ÷¢NSøDÇó@—2Ýs\"?†ü,!X3j9ƒà7…¢ri•íå¢€)‚è±DËªQ8,|­³.è€èp3/…I3Pp!ªÎJ ÏÊö²ûÓ’®‚%ÇS0Ê˜£ó<u09\rºÍ\0Ú˜JÀ;‰TYˆÒæ‘k.h5f˜:¨é„]4Šà/“TŒÒ.™€ø0²”²	ï€‹‰<;P“À×,‚¾ÂCÆt\0¾\rxÎ\r±ó.IbOo,Á¡g<\$ Å!;Í ‰\\³\0ë,ƒ5ð>³?	 àÅ²í)Á.ò[æW¶—+øò\0>–Þ¤ÙA{M5œÙÃ	…<N\nàSNÍ°\rb,À4ŒRY;æW™œY:-¢ÍÈ*]KRüÖÀ>\0ë5 5sNMÜPá%æ³‚.s³7R_`…òŽ˜è!²·Nt‡ÁEZu2G éC7ìÐ ‚r[2ÐOSFøà8Y¢O:ð¹ 3È|ð\$€¶v*Ü»SX“ä]+\"U,,Ãy€ÖNPdåáŽŒÎ#øSò·Y4!}‚ä™Ó,Ñž2ÎÍÂ€I =Lü³J1Ë;,ÓÄé :ñ!‡tË6/sŒò¿Î˜\$³rØ8BÊPSª†~±; 4N´3£ 5Î¿-  3F.ÊœÒ)¥8jÔœí\$ƒVH@°\0øœÕsMæø“ÇµÎ¯+˜ãàØÍ_5À>³¹\"VPgN\\‰X.ƒÎ¸<®eÇ€Ñ:ÉA ;‚ØÝˆ`³¦Ë0=8G¤ÄÎ”àÊkŠ?.f2Ì„I£*Ë:ô®Œz\$³AòONÔœ³RÎ¸‰<Ûˆ’Ðù=¨IÎaN=ÂXÚ\nÁ;¨B_KE=LôaOŒ«:¸•@•Mu=•S·¼ý=ä´3°€ù;ïs°†;\"òì\0ù>Äì2Ó{4¢!ªuÐ‹ùÍæñ Taª9Èu\$¤ƒ\n	Žu:FY4þ†?œáÄ¶+{é…\0ÖËšØÈÈOì„ÿ0çYdÓù9Ù+˜€ÍˆaƒÍ‹@‰ÛSÑ¦Y9Zrƒ‹?ÐOSæÐ\0Üìÿ`ØÍ„!X0©\$v@’€9NÄblÒ‚\n>J£œƒ1Ka/ƒ»SA¶Öè…3§ˆJ8<Ó\".*Ø•Aº6Üµ.»Œ~·‘dîé€Ã.€@Ž¨‚<ûÎ –Pž¼…\nrÔ7©/”³,6+;œØ-±NÄ\r€JÌ±†;Cz\0•P–œÕ`…Œ2µãÐžLMÓ¾;PSøg™~²\nPCƒv²=€†„ðó(%“ƒ‚<ÝÄÃË0­Á;Œç9£®s7\0ìýfN]DŒã4H€ñ8È`”\$Q2È`N°C;›yƒ.c4ñ¾M´¤ôTT5 c&ÈÐè3à>V˜–ÜO;F‰üµ­Ì¶/-ËaÎré¾rØÎ¸\r`1LÜÁh@ñ\rþXH Â€ÚÚ­çùQžvØJe4JåF˜³Î…IFœ¬óh:Ÿ+˜[n°\rM?‚@É˜•TiŽ\0`c´hÍ`P@“Š²ãF°;2æÑ´%Uª2Q¿G&“äÑÎfìúa²ÑÙ?P¡ O?9ø\0004:ú% Ì£3³ëK¼í\r³¥\n>çTç%Ðƒ±,4ÿë¤<åŽŠ:F!‰dágK0Ð‹\0Lºs ßÒV:cs£8M;-‚ºrëQê:û	öRl6Ä»R³Îd¯½'t•¥ÑIXKÂË0¢ìÓÊ\0åJ/6¥Ðê}”¦…š—DÐá˜AÌ½Ó¬K\$¬½ÍÙ¨é\\¾	¤‚W-Ë¥t±\0Û.(6³Kzäí-Ræ7:Úý-´dÒß/].+ ÒwK¬»Tº&•-È@2RæÍ/¾ËÕL/ræRÀê‰¥Q‰-µ,´ÅQŠ\$õ-4ÇK…Ke2R¹ÒïL­/ô³SIè;4ÍSL3ÒÛRûLÍ2´ÁK·M\r\$.–\0ó/…\"SËÌ•Ô¼`ŽK±M„½òRƒM¨#”ÁËºí(4Ýˆ+®òù“À_,SP‰Lû \r…ÌÒY®Ä½£F0~ˆÝMZÅÈMÔ¤D¶ÎÌ€È!1\0š2¨{’à4MÔw|Ì4ìO,ä8Z\"Ã„-C¢C¯@±ËO+³F1¾ ºsu¸\r>íÓä	P\"SÒP¼,=\0004íLL5!´ö4¤:ý=@–QŸOÍ@t‚€Í3èfôö±Èe}@C°–-J1Ò-…B´Q„¿PØI®ŒSIåB®+ÍVX1Oè”Ô=P%×7[DêòË	ÌdTVEI€ƒB±ÅFõzhAtQñ/å?SV=8ƒÍžTní=<Op°\"ï?'~ÚÕIbÿÓÔ8`µ…šQS¡‚8PåH‚2fÊ6‡9ÔWOøô;ÔB+´Ô0Ñ\0 åþ€ÃP|ø%€Ý8åBµ';Ot5ÔÝSÓo¥ÑT)Sû4êTPLõBÔOuPtóTR0ÅäÕ:Á-Q 3½)E\rEÀTaQX’?Tú6à¯^€8ÕN<úSøKà¯ \níSˆ˜]BÕ'G%B­ÈýT…En€ØÏ±ñ®²8ˆr½B£#^ðà>´î„QX6ƒÓMõ¥õ7;BØNãŸ<<x\rHtó5S}Z´ø„Ž.Ñ15R–çULkT‚_Ýc.ü\r\\4øÐŸ3Ã,@1œ485¤ˆaÀ\r’îŒyA@0 Öi‘*«yWàµ	†©WÐu€<X)‘/8X@¢åVQ¨Çµ}<=TµaT‘/]`týTœ0jV»Ø«u†UüWÑÄ@Â•¦±*zí»À\rÍ6 4“°EôzÖ\$Ü\"14û…þ`H5Àú‰,¼èU‰É˜Jì ,íbSñÔZðháPÖ\$¾„×S—V%WX`€8JÌ\nÕ^€ÜÔ’3…iô?·\05f4<SõSMC\rµ\rˆj•ƒÖ\0é\ra†áXa1Õ†Úßôãµ‘%?P\"U'V~\rQ”STí[°Zõ¼UTíouBÕV|ôŽ¼3sôû7Y½?µÅÑëY¥qAVnJ-_5U\n°*g\r6¼\$°¼ ÂÖÌdMcÕ€V+9SkÂ?Ö:]•Ò‡X“b†•4ua›Îi]}tÕÑ2•Dkµ†¿]Y‘5_‚C]ÂÄµ†\0æX*U†\n?U}s§øÔ0ñ´Úf=Rð£ç\r,;ÆÕ	G‰1-m‚Tä…x\0‡«YPÿÕêÎÎ\rÛc²ºéidí<2}x`©Vº(ý8NÔVå9ut&DÍR%m-ÎÔû.ƒÐµ†SXèÑîrU,Õýy*†…_Ý€U´¼¹+ÝNíüÖÙ4Õa×„ò½UýƒZL0T5s€óX`ß†V exs ØVÜC\"=Wb/ýG‹ W„É›n@à¯ ô©>@6V8á*ó>\0å`w€.Õ ô8SÖƒŒ(ý‡	½3Ð 6Xp5]`ýŒïaÍHÓ¼‰Ì\nôï.<Wb6V^<ã5Ï€º(I6ÓSØ¬dKy\ntÖ\0[ýa€‚Ÿ1X\\sŠØ´m•d5€XÃb©ôµ‡‚ä@ÌÓŽÍa]ˆYÁ„c(QÒµ3ÄWŒÀCË‚Vë½†`ŽÊÆíÖù@¨•U[VØX5‰ûYK°…6Iœï4qdõ9ƒ5Xeu†8¯`4ÜJ¾Ù>dKp“¯OFÖõ“VS€º!qtE“×ˆ…e(£a\r“sWÛe¥ŒnŒ›^ +VYW‹edVQNe%V6ØÀëÕƒuƒ7òY=\r“HƒXcz&W…;cê´+=BÀ¹,oÔ§+‹\rÌÑ7+éAÅ\0¾7Åh,šQð\riÃ3ONAÍ@ÝÙß<¹SVMÌÖÝžÕªÜÁÃUDNW5Ð62Ùþ62'ã\\M\r5WÒcÕ€S]0þ%QuTÜÊ±E˜×RtÎV‹Õ\nE¡u‰ÔãcìÕæ5–ÿ8«J£*äÙJãZ:Ø½N•ÑÚ;SH7BÏ’+öÓw~Ý…52O§c˜‡5®Ø\0cÓÍvXXôó}¤AZ~óãÏÔzU\\A/.eÏH³Í…ÑƒƒU­akŸ©fy0À6BPÖ‰Öó`‹ª5±4ÅíTYfCqaü:£I%[Ì£P‰g”æ–±T +uwó¦N¢²ÄŽ\"O>‹¬6Í\n¡ñ¶»Ë ËV®#R¥ƒVÂÚákM–ÁÕÑAv\0ŒÖò^\rgÂº£\\mrTz“-\rc3NÖ%SbÿÍ#ReMfW™MX\ré‚SõYá'M;m=p\nÌý4Š2Õ[d -¶„¬×¢ð5”º£l<¬vÓ’<…¶V&ØaOMQåƒQ@è-C †Ú‘mdà‡Ô÷TÓm®“VH\rc9ƒ‚pÃd¶êÖPÜ\\ÿvïË@-º!+»×o`S¬YOôÊÅÐÙƒo+p˜×¼\nÐ6ôT\rkrôXB\"ý½¬ÛÑP¿ô]ÛÐã½UVý™_o-VÍ—ÛÐä£6÷Û¬ÝÅ]õ¾Ün±>TÛËB5”„Ä¼NíÂà‹ƒég8ÅBU[ËpUÄ`<‚Š	tÓaœ\0é:Í‡Õ«â¸\"(\\[3íÅö¹õb\$ü—Mƒe—Ñu6\r¢´ž\\}5Ñƒ¶LÃ[9Ò ÜˆG•”W|²\r©V‰\\hôƒ…Q8>Aº”üGEÊ£Z»r=ötVïn•UUm„õnÔ³1\\%n½\r•¼ÜÕZÝ2Ã[R +<TSUUQ«[Jž€¦ô ¸\"€hRä·÷7U‰´/[PHY²ØZ0”¹5ª\\!m,Ö>ŽÕrÅ}—#1¹tàõ«‚”5SÖ@:ý{6«MwU=®AR—[…p7µ[Õ±\$ÄÜÍRoVpÖ»[ÐD š]5[Íp®][R[—NÚ’ôoõS–“S©8\"?ÔY[Ð‘­ÆPD…,÷Y×\0êÃU']‚óõ •8¥q¶À]‹3¤Ï7m¹^zN¨ƒZ\nœëÕÀY¯tÕ×Õ8²•CÕî1Võ`ýM÷gJÍEÝE”:QóuÅÞa[ŸvÝÒ—|§wÕyîêñM7|‚XØr·^Ex-ßà–]ùr•W*55xiy \"3œ³‰ÝéW[	`šK\$)½{ ©_]ÝËï5Lóy%[–Òœ¯yC¬÷YÞIw…ÉòÉ!Ã@|×®Ù¢VÇ+s\"V0ÊÇAR;áy½ÇU=˜Ow\$Ø—›=EÐàKØäˆhõM‰_½äµ¼Ý’€ù’—fÙÆaUíÓÚâ=#€Ô€Ê¨:·…MUAÍÑõWOýQGÜÇsœ¬wµ\\×<AS´7~²\ríÕ ^Óf`J÷5ÒI{­o\0;Óþ‘´\"Í_tx‘·³Vò¸­^±\\ØÊ\rðá^£gõñÕ‹ËayH7DÄÞ!|k.vlãÄÙÁûÕÑhðšµ.[€² ×ª]-eôµX_NpÕ”—>=fHõUƒ‹Oôï5ÝnHÎ‡ÔsÜM-×3¥’æ1Ýp-¾–þW	CLÚö\\öÓ1Sš¶y{Ë&wç[¶\"í‰v°Ü%~•\rwÆVÁl`¬+ Õ¯Uµúß°j‘aOÕÇ\r}—*]_VìÐ5ÕÁ5å%iƒY8UþÇñD\r¯VÁZõ~e¿“Ù‹(ñb³ä·;u½Qñ¶.é²Å\0ë_|n\0­‹WÊSõð¸ÚŸSÏÍÏ‚°üöcÛ¶×˜ÅôuÞØ²\rë–°¶².²7Q@’•’_-Ëd¶TPÅ§u\\ŸXé¶U·P–Ù~ÄÌÙU­`ENiÆ ©B¼†D–¼\n99!ê¯BNÝ9ƒ^½8õcHÂC?HøÀƒ‚¨”ËÑòËòÍ('âgs>\nBÒ²€Ø.¡ºSMm¨Ñð%‰è´€'B˜.†âÍM:GCnÍh*ÁÝäÐ¨_m7´–†R \r`Ì‡Þ\r\rC4â‡ùh}¨y„>Ø9=‰‚ù¾˜0¨Gƒ þ˜A›[„+Klý€[ƒbzUàßƒáiö42ÓVÍ=aÔ[F;5?…*\"&MaNcÖÂ;µP	àà;ál§ð“-5(hîÈ€æýÀì…j1£‚.Ë5Bì;Óx.½@Ôv¸[†Ù…Ê°ŽÇáy…öoá„26¯T·¬2ð­Ÿ¶U†æÎ%á¸gè£ç€üØÖ„taÌÎ\rÚµø‰þíu:kÈ¬¾8ÚÞ+žX{8(ï«A…áäé3|-Æƒ‘‡ÓµÂe;»‡þî‡ØJFÍ‰:Ý}™˜‚;ââæ\"N¢Ïˆ«Âò×â/ˆFv»— ð!Î“aèö#\râÒ³‰(µÝâPv_bP	>@<â^×p4Á<âVØ”aóˆãµÆtOÖ;Ó-Cá]…ŽmTál*æø]a¤9v•\rÿ†©À¶¤æ£·ï&5lÜ à*€‡†ëƒõš\0	@.©	\\bÊCŽa8ÙâC‹±‹‘+¨Ñ5 »rÎœ–ï«Â€Ý·ñS#\rw(¥zæ:…)pƒÆ3l†ô—îeƒààˆ@¡JTÒ4¶()Œ!…ynÄ½Šâ†Ð½’KÙx&½…~\n/iE\"”+ð¯ì\"\0«Ó)Ä–2r®ýüS@‰&˜û½òö4ñè¯&40Ó=É‹ÜÑJ\0™´\$xÑcmc71QEt4íÄ¥ä:ÛÆö90|A9yˆõ>Ô	R{ÅU5ÐïAF5‘m3©ÀùèíHß\0qg#¢(ñ3cÑÑmÁtø/þFá£Úñ@LÐþ=¼G¿ö=cæ÷L_2cÑ^+2ä,’³F]DR˜ûä?d?ÙÃ®+ó‘ïä%ÄyAÇîCpXÊYþ?@€²ÑFCÀ«KókèRLÅ\nLq@%ãy\$}±ÆAL_ÑqG3DQHã€¡>XôÂ{LÐ{Ç&H0ñG7ÞIûã•Œs{Ç]&H0†ã‘†H8ÿc_þIÀä¼ð{älpÙ ä>ÌeÐ{ä#’äVà«\0¥–J™.É¯“`úP^d3ôyäòøàÐÅä÷'98äÛšŸA÷‘>PY:äU\nNÃB‰<“ðÂBNP†Ck>4¹!c×nS&ÃÃ\r'ù'§”&9)åCž?P¤Âc’Ô;Ð˜ä	Œð˜äÇT&93ÉÏ	ŽMy\\dá•>Np˜û“¼!™LÃa	Ü¹@åO‘XùDeO‘\\(püÀ\n3å£í\$ß\nCCñüHã\ntRÂµ\"´íÂÔø¬¦²@ÃQ”8ÔÌC&l¾ÃI›ÜoøE®8£óE5èÿ¨ÍÉ¡—ªHq|Á³Ä{ñ«¿ó–ìKQŸA>ÿ<\nà>J|¬Ñ€d'¨úy4Acc7Ïâe˜fAÙ‡Ã:Î\\\09Oelþ<:y‹ÁŠu“¢áƒ~äèxk_¹i¬ÕÒò__™Lú>²ýühao\0´’“ñ™­B¨hT&ºÉB­\npQÆYTf1u£9¬_@FO”1äÅ/“DR¤´?)3D#ò†	2»Ÿt­Êâ€ìËE<ÍOCõ F|\r‘•Åù@ü˜ùÊ¹2˜ùÂÍÜáÓ„Î/-˜3‰ÐÔ%Q1–ÑÛR¼€\r“ÙžÞŒäS’Ï?99§ùNV	\\ÙÓöÏD­ “ÑNa9ûÍ© F•-:Œ Í ¾3Ý8¹à¬Î€`—Ò]pc²±]¹3üÎó:ƒèÒ+ÈMØt~Kªºµ\"srÙƒ6´ÞcÓÑ•3„­yÚ	,¼‚aÓÿ9ÕP\rîg'MK3øÀft	=¥\"´ËqƒNgopT3dÍ-geî–“Ò\rÌ±åG::%ó þM˜DTÚyãÿ7k.U8þcA¶MÎøÆ HÏåMå€6óœÎé?]+7Ÿ=\\Ø9ü\\_ŸÙ•ùä†©>T´óåOb2ª}‰\$/!>Ve³7X¡J-ñ™–ÎŸR:Áú	„›œtìÓòRV\"õFÁ5‚82aµtP	Hp'\rNî0ñÌúhA7P7&ÐË%9 É ðÛKi­Wà3N÷€6úÏr\n\\ýâíN3ŽÏO>ÆWÔ¢\$=`=%u[K%IØ•URhŽeÆ‰YÀHŒ’µ²¸u2§]™ðTÎRí.Ý7s}\ný4M&t834²õÜS2åuÎÅ£u·ÝèÝy“¦_Ë Œ€Là5Ûq-Ìæà1ËMPþ…Â/„¡pn&ÐÌÝJ]óìN]‰…óóAi˜h#¥òévˆæ…ñi'YÀE4£é/>T»:GÚ?±ÜóÔéDu8G3˜n«ÈJÙD•\$öÔ˜õDÄý« ¬u-eS²¹ÝS,Ð¥ÍE¹à:]¼#EØÏ€Ó72_ˆóNé„aÅ÷É”Ãg–7Î·—¡eáC×S¦xRice\$k½k¦u@<i•¦{´2ØQcE^œtWQq§-t^;ÍEõ4WéÛ¦+á8_cv!=hÿ™5¬÷æF½	Y’ÚÝ§ã‹¡3ÌÃ6ÔNãSö ˜±„­]•M³`¼‰3î úÍô-“šg–ÔÐIµÌj\0æP-•KPÐmnzŒà\n† :ŽÐévc¦ô]+ït†–êÏíf=Ó6XZ¤TN¥ÓU¸i©‘`Ö(9/U]‰õ#…©ž§A3ê6\$¥ª:œÜ&1}ZúœZ§ª=Ýpê‹ª^¤Ú¸\neÅRÓŸÙ¶ë%\rá\0ÓRVymÓj²]&«•K8;4°Åú­jÃxßÚ²­«Îö»Ï«V¬Ú¶ßÈæ®¶[Léž®zª=)¨#{S³ÊÎ\"î¤Óº¼†ysº5Ç¬°· h\rˆ5ÚÀêÝ=8–3§f±5(œ×p£úÄU-;¥\nmçÐs¬®­hpë5ªhc|æsk!„åù¼S6#©ºÔ‚£­[czÖ¤¯­{\0Âæmr&#ú;Ðô\r~‹šª‚²U¡—QN]žX=–öëw©5ë\"í¼bnybµWjµRšæ‚T2åWšèÕ,7ølµ€ñ8æyzÛë·T,ÕØk»§Þ»ú[D¹åëËT]!ÊjM¯]E¬ëºØð\r¶Ö\\©ž]Ç”?Õ®%óÎk÷®»ÐUœÇ.®­ÏEj¯üô¡'W¯ëª;5zuR×”ë™®¸Æ—JjLgAxWDÔß°¸›ºGwõ ØêM.¤é»i)Dy{l0ÜþÃ÷´1±y{ÝŠó}»U-±–Ã@Õì]z¦Ã›Tã±©ñ«½ÓŽù-9+»Ó–ö3x¢Sž\n>+ÁkÑõOLoâ¿²x°äÕ6äÜ½Æû&âÁPUTçmS¡FË[*y±•û0‚ê^ÎËw…674žÌÀ–²‹Y@‡ÜÍ³](û)l³>Ê{3•nÚE-ÌÅ³¶Í;7ËÖÇ.Ÿ“+ÍVÊœã;6ÐÒ¦Ð•À…³ø%[<‚Y´Çá&X5´‚Ä›;í'´ÖÒ·àT¨óo•è»kê±:ÒRÚpË–MÃ4=hZ›9ŽÑt4yP´E®‚ó”ès€µ»Rmi¡Ã—·”ß%BeÎW\rÐ}Ëhu›Þc´¢i;Flå«~ÑûdPÕ´i5[G…G-xT@¢¼- ›w%ÌÆÓáe2Ñ_ùf°¬o˜pŒd~¸Q ÍE²¿¬]\\×!j@8`³s\\õ,ûböªmæÌR.EOI,›‡sQÝ!µÕJÍuz*¶ÛÕ<%¤Öl?¢íé â\0ôÕŸ»PÔ›~e5œ^ ŽáT¯OÞgNÊ´&Ö©K6’Ø˜§ ëè•U^'!OÙ2Èeµ.Å4Œ].iáe:MÓi…Žšg	5[Ã3Œ˜Ö–a²ë7t·­ÛžvžÏh„ëÝƒ‘°%…Aå€3Œû‘¬Põõ²·z„òÔè›dåÕ7\$Ó[hƒ¢Ð§{‹É÷öbÕ·<ùÁ;Ù£·]³€ã]i´îµg\$µV{¸]©»YOâ¹õ,6ä äÐýõÖk\0Æ2åÊÀÛ‚\"±-R 2à%þð€úo\rTR31h]¼eÄC£_¼EÖvT†É‹­åUßÜÙyS¢/Ëºê WÑ–NmJø!7¨€ð@–ÙP­X\r`9M¨{s#|X›½ŽönÎ³½Åï!¡ùwVõtýnÇtäÜ:?’=´ÛÌ×çý§´ýøßcBÖíèÏù`Æ}ÓOÝÜPe:{èXÓºØI–T˜NËëéCSåóÒØNÏRáoúÍÙ­-Ûv˜õ`¸¡PÞq>8CÁ!]CUj•TúNíû‚ì,B…{SY½‰´úuOH&¤ÄÔ¿ÈDTÿÞc\\ÕQÌûµæ¶¡U\n’¹rÖ‘ií5›·fñÀ¾í¶~N;t¤ë5'k’èýŠ[k5Ï5Bèú.Ý|Üà0ïUQákËËIw•BðG¨é‚£!@ ¶Ú¼ƒ@íŠœÞá·§µ']dâ:?J0]T-îYhbI×¸l¯ÁfØ´zÖíZ‰œÝT•IÌ¸ð¿c€`A/ð5T-uëÀ\r…gËpGSEü2p§Â×ÚUqRsˆ›oÏÕ{EÊ”nµß=Tð2Ïíý`‰•÷=-p\rÓ\r•wOz±qy4Ï÷\\—SvÅ¢0èe\\Êûxg¡_ãˆã+P¯HŸ8nFö1xÖóÅ(hKÝVoX0¡jß?YòÛ§Oÿ«}< ®…CÅuùôðÑéÅùíqñeÅÅ»w\0ËÅßM…Vö—¼bÍtá…_Ÿ¿o@Ž\\84æTlÜîs-ÅSg8FHýT<lL¹¼¡&¶çÆôáÄÛSÇ¹¸KÁDqÈu7Ú±Íû¥• ×&œËs]Q\0¯\0â3§ÓÓ³¥œ{…ü“œ|Ô\r[Ï VãíÏÈtÖ¼—¨àä:ÀƒÎ<OóCÝÂ¹ç\0ðïÆê1Ý‹ÇT4à;k3ÄQúGMJ–¥PizöõŽì_|µ÷zmQy-Ý7©ßUÀ¥ÝÛ/T;å¡…Q|!=—ü›Ý´uñ—qÏÄM’›¼Þr`SeÖˆrW¸¶¸ÛŒå_FÒœwï¿WDµ»4ò¥ÆéuÒ±ò£qWûŽ‰UÊØIœ¬U?-‡,OJÆö·3´¿-µ Å©nîKËIÌ5ráMÀ*W¯ \ruAüyM,2­­µphuÖÈ7&¨8®üüÂ²©WÑøÚ­Zìù<Âm\\à¦\"÷6[@íD\$‚î€èî¤UspCtHj î’Y>é åƒ	\0Åöo¬ƒÅFé5+Þ14‹¯¼®Ø>%W·ˆsiOO-ÛKóUÊm¬×—ªÜá[V2iIÜ×\\Uk3×8m¯ï;8wœåZÓ<\rWÃeP[Îl¬ô;Zcf\rºu”Y«¤±_Vï¶³¥?;;9Zè+5´c`˜Ì9•À„(FÒ›H81³æ 2†!F€¶Ïª…€¨_„ËFø6`ÞcZ€€¶Ð\0'ƒŽFW`ç²XÃÅh[¸<ƒI…_ø?sÐ3}a ß=„£Kù'UÏ›KúóïƒVm14Ð£M¸>´0ž.f4cÐ£N?áWÑsOd\$4úÓª€<a‘…r„f9´é…›4£½8A¤¸¦=SÑÛQ<ô“<ì^¢¹™]Ò.¸mÈtí°ô¨G@Â{µÔnÖµò6ÙÒßJã6RØ—K„t6\"Ú‡JuªtÍ¬»]ý+tÔ0Ã‰í‰\$©Ò¦°mÊ7ßÓ)AÁ¯tìàÿKtôß—Ný*qmÓû†OLt‘ÒÐEmôÒoQGa¦!p+®êu\0ì¾/Ž9j‰Ô3Ó]t\rÑ¿IoPõcŸJ`\\Ø})u0¡ëØ=ô,Ñ£]H°ÀÕãÙXVS›ÏOAôC`Î”mÒL²“Œ}Eù*3™Kuy\nnV¾å‰T'yBÂ	”ÎQùiÃ;\nL+#éÇH’“ÞdµeÙ|3beñ&‚:qŸc¹K9€«˜+ñ¹ ¾{D|°qÆmfFÅôQ¹‰?q‘TGïÎ?ýo^PF”ýü\0¯ØÆq\0T}|Ä÷\0+Þ×õý\0N5à¾túÜ }¿×ããƒ¿äùôxó4U×?’:Au\"{æ’OÆå”øêÅ7#pb¤E!doðeÇ´ò-@­ÄsÙ%Ç;#ÞOÑ¢ÆyóüòûFƒìhFÙäjP˜CØÑyr‚½±>C½£öuì}ñíG±ûDf4AbMòz´UÚ¬ClæCQ¬R© <(ÓÅý d9H%\"lM’ÆöŒøü‘ËDÍTz(ØÇ/\$ü_ˆØÈe\nä—1±C…\r´ˆ±s\0ù(ûCò#£7\"|ŠÒ)Eã\$4}IÑT^I&A£ÜtOÊÉ\"4’m³ƒ%”ÐÁÅ—%ArlI\\ý‘kEùä˜é§\\\røÛãc—D1°Ô¿&„š‚wE\n.7MÅ7&²3ìõû\0<_lÄ°Ðü¦Y„D®Îd‚™ƒÀß*[ñ€ˆÍ¿IÄ¶»›Q;àà;˜MI]¡S/[hS¥î˜ÍÞ!ÔÔ	…üÝðOÞïü‘{œ¼åÓŒ–ÿ2”¶Ó*fÕ4j”‡÷™NÈÖ…MågŒÓôˆÇžØ}åq®5>€ÝÜotºò6 ;¹NpaOx.Då³Ñg%¡fê[7ÁH°K³\$“WÎ,äî@‚\"á*°™+”“@P‘¥­º\\U°VÈiãì‹‚Ö3@Š=ŸÕ^3´èW!´—{·»\\©t˜h^S¾	lÛöqÖ‚°Dÿ›•íi{]QÌr\rï+nM?ø²Ss¤‘ùOÃ|[AÍ‹’\rïâºÅÑw3\rfÊ?(´ÑÌäá‘_•û¿ðcÕBxÍKWiÃUEÛBõ§¼íò±vŸ+·«ÞS@ÝBäÍNX«èé—'tÙ½\\Ùê2l~Ýõ\\„Ñ¿eX!{ïƒë7sö9#¯¯‹|ÒPN`QAº)ò^ãšÚy9Q†ÿ’ÖZÀë#f¼jaCÿ”³L‚g5Tí’»w·YµÇt ÎžYùSÊÅêõk¹åBN <Oj¯GoT`‡¼£r[yuyÔÇ;ÊuZs;q—uïÏ];{9uæ‚I\0–ãq[¼ólÕF3}tnÏ0ò Á‰å\ræÌCJ&ô3„Ÿ=ìü§ÍÏÏDÍ2\rˆe™5}såÑ@.Ý\0ÛÑ¢Ê^zYÑÀ'€.ôž*†CùÎÕ7S¸fæ \rË)8#G€gë%‚V)Ï\0a‰Lìf(s˜ \0b¸\$¨Ñz8¸\0€hŸ¤@9ú:À`¡øâß¥K8\0jÞ”ú:°\0\0ké·¤€úIéç¤à\0oé²Ì€ú[ê \0z…é·¨@z“ê`þ–ú‡èß¦>–zsêbÎ\0\0sê€ z_èà–\0mêÿ¥@€n°”z©èè \0nçÏª úuêg£þ¹ú×ê®@zyëBÎ¬\0oêW§€úÓéß®þ«z§é‡£à€rŸ¯^»úNÏ®úr—±¾Ä,Éê×°žÄz=é²¾‘úN·¦­\0Ä°~ÁúÉèï¤€ú·íÞ³zìÎÜžÒ\0aì§µ~Íz›í?¨^¿ûCí·«¾ÃúÀ‚k>ÚúÀÏ¥žÌúÁég±ž¦\0sëÿ³¾˜€d¯©~»úÜ‡·~‘úÑí/§€úgì¬ \0kî7®3ˆúéë—·ä{«é¸™úyê—­^Äz£è÷´þ³{H¢B@1û(‡¬þÝ±_ï¿©^ßzËìŸ¾žÂúÓï÷£¾àzÃêç®Þ÷zÏê©žà\0sí¯¶>³z´™‡®þÖûµêg­Þû³éÇ¾>“€gïµ¿üAîï«^¿\0sñª>¨ü)ì×µ |ìÐ\$©¬úyî ¿\0Ä¨\$ž±üQî û‰ð'§>Åû…éˆ {¿êç±~¤{ñ_³ßz§Áþ¤N#ï/®^îz­ñG»_{” ÞÈúÏêOÀž¢ú]êg´ž¹z¹ó­~¿üîo²^Ò{qð—Ížø|£óOÊ{¥ï—«–üãò/Î~Û\0iïÇÊ@ûÏé_Á\0ièï°>ê|_é±ŸûYð—¤ÿ-ûIì¹AúÍé÷Éžô{gô¨_\"ûãîÐ^öúÁê°»ü‹íÏ?T|+ðŸª£|­ó§žœ€iïÆ¿4|ëGÈœ{“õ7±_ zÝô?®Ÿ({¹ñ8_EúÊ§¤ÿíÉì_Ðþz¡ìWÍžžû)îW¦~—ü#ëxczëî£•þûzóÿÅÿký‹ò÷ÃÿúÀÆ¿aüïoÜ^¾ýMéŸÓ>Ô\0gðÒ@oz“î÷Ìží|GêoÔ_\"{µò÷¨?7úÿö×©¾Ùügé/àžÅziòÄÿXûg÷^™N#òµÅý9íoÖ~¬}ô—¥þýý¿õ)¿{}ë_¨¾ñ|õß¶ßü	ô¿Ù {—ø¢ÎÅ~)îÐ\$¿•€gòçß¿ŠýÙ›€còç¯`’þaéÀŸ•ýÿú7Ç‚z­ñ°?z]öÁþê|Ÿð¿Ôß#}•íµÀ~géÈ>öþ=óÆÞÙ{Ÿôç¨_¢úéî‡Òßd~ùç´~ÒûŸòÛ>´ú™ìëß{z•í?²ë2zöÑzÛðo¿î{[öŸ®ÿKþaëoÊ—\0mø¢Ìž¥}ãôº^Ÿ¶{ê—æ(|ú¯ñ?\nûüßþúûî³‡¶¿¯û¯ùwï_\nû—ù_°_ª{5é§¸ŸÒ}-éoô¾÷~oî_¨?üò/åF|¡ö°_Éúsý¿Ù8¬áéó_¾}[þ7ð¿þ™ê7¬ß=üqîçá_v~±êÌß:Yü÷©ß{aûo­?w|Yê7÷žÙzóúá¾Âÿ•þÀ¡úoîÆ|÷ñïÎ?ÅúþOÈß2{Kó­?³}yëÚŸG{,õ¥ô›Ú'Ùpé@}Š÷)ô»êÐ¯Î_R>¡Ðú	û«ßwÃÐ_ê½ššùíöCï²ïž÷>¸{âõ¡ëCÞ·Û/nÛr?|»\0!éûêg©ÏãÞÉ¿·|ÔúÅút‡ñïvŸú½w}ÐôyëóØ'Úú^ú½cz¢ù&³Û¨OIŸ\r½f{Öô‘øKÕ×ýïß`'~÷øËü˜Ï…Sˆ¿9~bþ]íà(Oþ_Æ=i}ðöê‹ãç¯ïâŸø¿~ØôÉö+ï·/t^Í>•€Ô÷î{íGæïVÈ?0z²ùyñ«ûGäZ_z?!€|ùýó‹Õ½P?A€tù¥òûóª/jŸ ¿™|Xøö\0cówªï…^?0~^ûiëù/GÅ² -@†{qþ\0D\0·ÄÏ§¾€|Òôåî×HJ^¹=&:÷Iîüg²A?¯|zûíîÔgñp!ÞÛ>Üz€ùUôò¸ß¾Ñ{þ-óËúØï Ÿ %}çÃæ—Æ¯žê@ºzÜøúëç×¬âÞ§?A€°÷î¤gÏï—ž’ÀÏ5}ë+ë‡Ú/YàA¿ÿz[©öÌÇç°8ß‚=€âùÑúl±œ¾}bô™øƒã‡ºßÂ¾Ø{”õ9ðëèwËZ=%”÷¹ì÷¸obŸ½Ä€æþj\0ãê7¥ÏÖÞ“>#€|ûvü\n—Í/Zt>­ôöié<wÅ[Ÿ7½Œ‚*ùì9Š÷ûP>_\rÁ\"ÜÿùèãÔXš_\0002zàüÝé3÷÷ÎŸà¾=|€ú]òóà÷ÆOhž–>'~Öþèôg¦F^•=E~ANœ­¯¥Ÿ,À~µü#ýø&Í¹ˆ½:µ¤	#ï²^ãß?d}fÿá÷œ	÷²e`¾'N#\0aêëñ§êPSb?‘Ìÿ•ñcÙ\"°O^ =nHô±ñXÎ—Ç¯[ ¼‚”ƒõyüÛ÷¸)ð^¿½ñ‚ú}òëøø30K_s=1\0\0ìÌdà_½,\0ùýîÌ\0·Ö3Ÿ|Á{4üþÛÞç§0`0@z‚ ÷Qód¨d½ ®=ÐPøÅó#Õ§î¬à5?›€¬ü*Ôgù/¶^®@¼zFô–tˆš_rÀ]zmú,	‡¯Ÿb?pz“îcÕ£Ðfž¤¾o}˜üþóä×­ÏÛ>Å~ ÿŠè÷ú‹à™>FzÂÿ…í+ØÇåoƒÞì@{zŒÿÅëåè ^òAìƒóùó»ÿ85ïXß=¿4z°úù€WÆ¯«_…½w„%yï{ìÕP/`É½Ç\0Â4h2/ò`¿@î‚–ÿ}úéë÷›ž~žÐy²Qî²8çÄt ˆDˆ’?)í4]AóÏˆ³>?f‹­Ž*1H1Ð›!áu¤’ú¨}\$fÐa\"¡ˆAb™F´Ë™‰jC\r	9ÂD—]hX¡-¢ÆCÂII‹”XNÏý\0(d^‹	ÛqõK°š‚!äL.ƒ™Öû%´P@¬¡!b„àÆµØÁý”P!33CEÞÍ\n@\0)‰kÕº3]\0„IÍr.´Lqh¢Hf˜~Ø>È~àj @€L®}¬?y ¦FgÚÑöÂœGt†…¼\$øKGÖÑö æ\0¦eAÄ™ð–Q~\$ŸA	\n ?#ºøUˆÏ­¡ÜEy\nÕ‘â.63çÂ¡;±â„å	ìûZ/âT'í‰)%fßä8\"Y—ƒ…4ae8ˆ'–kˆ†E ú‡Ø?@ÊHübXÔ®\nVÎ;œ…ªÌ@>¼-ôy(aœ H„ÊëPRBÀ¡€(G,˜y“áïçYP™Y¨ƒT=Ê€ØJ×al×¡ >\"yƒ*8LP½’z'Hj{¤ÿ2.2Mì´CòÃ\0‡\nLÄ0äx,´!‡²}†Œ(£-hR@a[u/´Ô1”.\r<Mü‰òr.‘Ödº€ÕŒ¢€†‡\nd%ðË\r…>Iâc-˜g(.!\"£¨í5@E†äM¾¥f+(†Zb¨]¨œ°³E…‹Œ\nBÀ\$­åÞÒÛLJÎŠô‘\$3T\\€Ð\n ?\nî@¢t` \n!±äwDy—\"Nèf!û\0*€BB%'³4Xl¨ÑŠ!d\0„ŒaBa(b•¸FÆf*@†\\P¹!0CB@¬\r-9ÑOp¥QåVÑñ.bXàUè¬Q D-\n˜\\%\0	%QaB§…S\nÈ\n´9¤“¬ÑY Ã›ID‚Æò+6„¨P ¾L@%¥à„5§¾Ñ8 >C]a,9¨vljá*=?â.Ðúé4¡Ú££‡ŠÁ‹BALxQAŸ{\nëÁŽ(-ä7p¢E2…[`XŠ=”/(WÐáÑR²öEÆ“…0ÂÒ3ˆÈÚ!\r\0®Íœ7Ö\$iÎY²#p…ØµEë5ÓˆˆÃï\"wFp—ä\$”5îÀ@Â»¿¨¼ÝƒˆÐp.#Áª:Ý…n\0¢ 314B(öÀ! Œˆë“„@TKÀaR ƒTDz|*Wy‘4ŸZ\0P•52DA¤a UÀ€LCÈZtÈ¸À,ÔGˆˆ¤ý³\$=L‘!tºådºFa¬\$”É`\nÙºòdúôi x]¨=ÃþB°…T%ªdCä8¡÷Oœ ±H4}*\"\$˜HðÚ¢²F…ŠŽ\"\"Rhi):¢,‡ÖdNIá\nxM ØÐ€O>z˜V\"Ë%8.ÐÐÃ#ÌG,Š'`8IÀÝ†2]d¦ˆ\"ô7‰1YA»E£e\0l)¸ŒÄÐ&’\$†ÐG–#œ=È†Ì âBÄp@÷:\$[14\nq\ná€ù„Õ\r4I2Mè 5DY‰4IÁ@}ä°®™,¡‰ ~Ü÷[¼ßŒP\0D˜CFÆ\"Ì%ˆt0¨âHÂW@ö”ìýû(ˆ‹)ŽS  ‘cÿ’KÈ‡hdâ²]BH’Î&+(3ó±âW2nL)ì%d9‡êQ DYC>‚á*cör\"kÈ‰’ÌLŽ”EVH,›â ÷I¯e#Z\nXÇÂ¢t ùAc®#¢ˆŽQ\"`2r‰¢~&'B\"˜ˆ#\"sDF‡@\0®ÔOÓßP»\0&ÄY@ÖÉü( Ñ.¢f€+Kóe\n\$E˜zl·á™C<ˆ³•\"è`w	!ÄBcÍd@x£äfILÄ:EeöS)“7Áü\"!3„‹ü*¸–p»P˜Â‡?á9#E¥ÐÀ\")¢¶ˆj„Å8NÐôŸB7r)K)è1ÔDbˆÇ#0˜Ra\"ÄjŠ¨ÇÕHÔ7ñÐBDò‰y”ÌGta÷ÐDzŠg™\"\$y‘â«Åc\0‰\ns)˜‘±!É(DôAµ1ö˜‘Ìtâ´DŸ†]Y\0ìWÔ±&âV«Šñú%\n+TÅñaâSEwŠâÊz\$ìWƒê1,\"¼D²ŠÑ	‰›4ÃÈ³Ù(ŠÑ	“*È¬§¢ÐCCÜ~1	ŒLt\$Ð¸b¼DË‹PÊd>ºHš•aß¡Å‰©\r+ÄMq-mOÃ\"ÀŠÑ¢,|\$Xœ1kÙODä‹‰	'<Wˆ1;À*ÄoŠÑ.*Ü+Àý‘W¢»3Ee}Þ.:HFè²\"ì‡ì‰ôb l\\øŸÑL¢ËÅ4e3\nž(*ˆ­AÝd»\n…ÎË\n(d\\3Q{ÙOEŠñM²ô‚Z\"Œ2IµlýQØ±Ìz\"E%†×ò&ó0¨Frâ–BŒ¾ô+¼æ£!Àc¯Ðö›0¸¦ÑXÐ’QŠt|	˜ZFè®±OÆ1ïcG\$a”±RÐÅMˆÇ–#</¶=U£ ±ŠÄÇ¦.l]#ü±u\"ŸDŒg²#ÄVX—±…Äwo=|W´[q’\0«EˆC§\0<dÄ'è\"J!|?UJ2qþROb‘q±ý‹ÍDYÆ±+ã02%‹-˜\\Y¨—¿¢ø±è‹J„z%ü`6D±›âÿðŒKÍ%”Z¸ÎìÂâ×FR‹bH9ä[D0ÈÑËE¸ŒÇ9D[´0ÇÂ_ú„ÊÈAôMˆ·‘6á²Œ,\\8ÊQrcC¡2A}F2ë\"Xº\0U¢xE]IÛö3“0´p•£MÅØ‡î4ìaˆñ\"³Æ˜dEò4ë\"8ÊQCã[!ø?éô\n´_„/l—¡™ÆÀC\0Â(Ì_ôÆ°÷½—’JÂéX|ò8è##’	\0–}\"\n8¤‘µ¦C¶´@úÔaû@\$€IÇP>|`Èˆ'Ð<š(D‘¶)‚[ðøcx¤IÚëJ7'Ì\0\0'BÐžZ2Š.´¤h6Â™\0–ƒ„h1óël¨!—ÂºH”¨ûY…\0\n½Ì(C\0†*”)‚NQÆ¡ØFW>·8D)„qÂ\0/±ê…‚A|,Cô˜£{Æï…‚‘˜ŒÚ \"=QËãp‘©Ž^ŽN9iþ˜æéÒü\"»L/qÆ5ñÎb!ÂÅc÷ò:2È×PbM¢ß\0œGŽ:<hV±Ò!]#Ž«Ñ(p\\±À/Ÿ\0œ”Î“%d¾©}ÃëŸœ@ýÎ;³´j(ÒQ°vl—á\n\n_X\\ÑÑõˆØ‘}7\0úÚ!uDlŽ‰Ù´tH•°»¢K aŽgô>’6¨ðÅÓ\0FŠ<C¤\\hïú€BŽùº<´yóÙ¡oÆBFÿÖ)¡øóÀcGˆ¾þÑ!h©qê\$»\"A˜øìzÀ.È£ÒÂêE‘9›èŒDj\"	ÂîcØ]˜L{d†ÑØ\0'CÔG,{ÉÝ;!¨^ÑY#ÜG¯…ý®=#­(ùqô!Í#ëFfôÎ\\{´fhûi\0¾~^>â „ƒñ-Ð/Çá¼I2\"/´@ñ÷¢Õ‹…íu#\"ØÃì×£øGtˆk5D0Hí¨K#ËÇüŠ¥?äy¤%‰#þ¢j	 \n@2øïðÈ5H\0Pî*7 þ!;ãòŒ#N‹¾!\\2)&âƒÅ~„’A\0ì€è¯ÑªÒz ñ°Œàô%ˆæÃì\0M>™	ázFØX±&ä#<c¸Š%Žœdr\rQm#À†ÇµäƒTj’a›!/‰«!â\$òéQž ÆFÎ‚2Bdnà	 Ï§#²4˜ôB7ðúRÓ£<Ž2r3ÈóQÿÑ£;9FvŠöLtàúÒ ‡ý>)\r`ýTo)È¤9 meí<~8ý0™d<’§F\n‰Cz6\n€Ð™²É!íè9Öá°H‚ÐLKT6ËÌVd/Ãd\\½\"V7”y§ph¸ã˜¢_‹úˆIÈX¤\$]…öDKLPTÃ bÃC‚ÐZ9)÷£Ü‰@Ãô²P‹•Z<:2”pàÕî#‡Ã\"õ•û0„N¡ô\$J¢uˆ<îBd9²HDpžŸ€Ld€>F\\…è|2£š¢ÿEc#E\"é§ã\$?H‘» š7|x8sˆ:#DÄÙ‡,{•ÝŽp	ÉCañÃ”=Ë•\0zMD¤i’ÿHÛ‘â{âGœ}ô±º¤áŽl£2@%:ø’\0NAÂ-´Ç¹¤€é…öFÌ:tM(€¢åÇš\0 \\\"(v\\éˆ\$ŒGÖˆ¾\rPa èóDj· Y‡ž€™\n\n „N1Ød•ÇÅ‡j…®@Ì<ò7érCµCÄIŠÌZÈvF‰Ó \$’y’+*4‰Gq‘Ò…ûÈ?Ln\nèBá2£á„fú|u¤I*PÈÚ‘*“^G²VGÈ\$2CJ@oº@rS9±Ü!}Éb\$¥%„ŒäkÄ¨‘Š2H¡e¤²[€<Ç\"â–7Dh™ È#Y@ 7J•\n4i, aG e\$ÊUð}™\n’\"Ð?\0¬‰úLtvÔshŸ€*žò@o\rµÜ„t¨äË¡A.ƒ†Lôy©4ôÑâIWKòMTÐýÇå]ÊHíAÉã³Äòl¶»<“Xf±,*„2XP9HC“jMš\ryÒZ£}\"w„Ž~ÈúB@„š¨#\"Ÿ„Ë­)ô‰ROlQ¡É·“\\ÈŽ7;b3g¹!Ÿé4Q^NòˆóRxdš#ŽHZ ,sãñ„tãwD…÷&žD¬šxóIPc¶€V7bÐ€˜ À6 †d»3]“ì:OÄŸ ’[ä¸‹k\nŸ¦P8áv¤‡2ž-·Å\n•N8Û‡…Êr˜-š049ØëÎ½Æ{ÁH’ñäó±„‰@gDŽ~1™¤4ØØiðP1\0Œ>(ÌðâfÁ&ÀÍœ=(ÌsC`3Æ‹CÊbŠÂÐ±Â¿wH¡›ÂG®Âü@àžÕ=¥\0lOÜ9c{@ÀI\0\0/\0ÄüþÐ¢æ÷Ü,†Ø'[8ot¥ÃiÌ%/½I”ÂoZRèbð¯€\re3±…6)+àiM®å/|\0o)ÎSL¦Pž%1…µ”Ê\0æSä¦ùOÀI\0’J[3)zRÈz šš%>ÊˆJB–T\\¦éPá‹å,€3•\\…8P’¥å2JY”Ù*FS¤©@\$²œ¥PJy”³)ÞU,¨ÙTOúeNJ~”³*UL¨)QÒ¡eXÊ“•2ýòUl«9RÇ¥FJ²•DývUÈb÷´¯p%`€_•‡*ìiT«ÙVÐQ%EJ¼•%*b\n\$¬‰LR¯€/ÁD•-*ÖV„©©X°Q%OÊÎ•C+VU­·æ²¸¥VJØ”ÉU„­éO08ÀJÅ|—+6VL¬ùL’åiJÊ•0ù.V¼®©U@ß%Ê›•Í)ùò\\­Ù^2·ß%Ê¤•õ+Qò\\ª‰`ò­Ÿ%Ê{•ÿ)yò\\®™`rž^ÁJ´–+Ýî¯	ZrÃ 8JÅzÑ+Xxbùcò½eyJ~–?+æXœ¯¹cò¿¥‹Êd–?,\nXÜ©‰còÁ¥”ÊÔ–?,*Y¬«icòÃ¥–Ë&~…,yî®Ùd à’K•í,ºXì´8\$’Ê%˜ËH~-÷Dµ	f’Óe?= –A,öRôµid’·å«KL–-VVÔ´8rÑ¥’Ê^€W-nSÌ¹kÒÙCÀ+–Y,æTÄ¹f×å³Já–Å+Ž[¼®YkÛeŸËw–--ÂS#ÔéWß¥´Jû—-®Vü¸)mòÕ%ºËQ–ý-N[¤·Ç£ò±eÁK<—)ú\\±)q’ß -K”{g-’\\KÛ9p²µ „Kˆ–Òü>\\óÜùu2ä%¶\0_‚,â\\Üºç¶rå¥Ø–d–¯.^Rô»itr­¥ÛK¦•÷.Ú\\\\¹òí¥¹Ë­—m.²Vü»ivð¥ÛK´—™-ö[Ü¼9h2±K2Kƒ—£/V]Ü©‰z²ï¥jKÕ—ƒ/ïL½G¸ò%<ËÕ——/èì¾'µRø¥ÎËC{/.‚SÈWÉvòìeùKÙ”à•ëL¬Y~R÷åoËò—‹/ö_Ä¾I_rü¥ó›å•)¶`4¿™R3eïÌ	—‹0&]dÀ™€§1@’ËÎ:0QúÔÀ‰‚’ßÎœÌ–É0ëlÁ¦ð&€¡0”áD¾sæÌ˜'0Ž_LÂ¹†æŽ’Ì#—Û+:`4é…¥ÑL;7´öÎ[\\Ã™³%ÕL2˜LôÒað·¶sæ#Ì˜s,H—­¯´ hÌ˜m1Bb“ÙG¸/‹æ=¥˜’ÎäÅ÷­2É&.ÌA˜(üÞcáù3f½-˜Ó1:c<ÃIŠÓ	Ÿ?Ìb}1Úc\$ÁG¤ó¦!Ì#|1ÚcdÇI‰“\n^¥Lv˜å0ìÆ'¦s\"¦3L#zM2*`|ÁGÐS\"¦AÌ<{¡2*d<ÁG­Ÿ.Ìœ˜ó0í\$ÉÉ³	žLœ™0¥ílÉÉŽ(&LÌ#zA1‰ë|Ë	“ó	ž¨Ì°™G0¥îÔË	”ó³L°™W2ÒelÂg©ÓžLÄ™g0¥édÌI–ó’ÌÄ™w0ýêÌI—ó2&`Ì),É1‰ì„Í‰˜óÅÌØ™—0ýñ<Í‰™óžåÌØ™§3rf©Æ ¯“åBLì{g3ºdiÁ@\$“7ÀÝÁi™Ý3éSß‰Ó9Íí\$™Ó3ö`äÃYžRõ¥x›å~ù3nhÏ…ß&z=*—0àæ+÷ÉŸß&JÍ\0™ù3Ö_\\Äi¡s\ræ‚Í˜u4jgSÕé¨f‘Í\rzÛ2E‡¼¹¤sEfz½”šG3þg¬Åé¤?_yÌìN#4ÚhTÎ©Læ˜Í(™å1ºiãÒ§±“M¦˜=*˜ë5ùüÁù¢/¬ævL{šû\"jlÓé¡o—&¦Í,š4^j4ÔWÝÐ¦ˆM)|q3²d\\ÔˆsX&“=ßšÁ4MéðÞãÇ™€3A>‚U4kH,‰­`‚æ·=µšá,âk˜È'ÏÉ¦9Í™/5ŠjÔÉ¹©'fÁÍ™ë2†lÒi”³`æ‰Ì©›5aîÎÉ•óR&XÍ”›õ–iÌÏ×¯ód¦©LêzÏ6Jj³Ù)²SVŸÌì™“5!öÌÚi³³?^œÍ¦›C3^jDÍ™¶3eå&Ìì|‹6Úm\\ÏWé³m¦È>˜¿)fmüÎù¨0¦Ì»€µ46ÔÛÓu&àÍV€µ5bÔÔX'ÓW&Ž°÷‚}5*n´Â¸r°&íÍÈš7Tà¤éŸO¦çL1š»7†hÌÞs…W¦êL›­4~oQÆ'«ÓeÞ¯MÅ\r8ûmüÄY¾&öž¯MãzÝ7ænƒÕ©L,cAyˆ”|êÕ‘*>ÆFçÍ#NÆ4Œf\r'j”°»À%ÅûŠp’ÌQ˜P6ÉáŒ¤)Š\nèØÒäHHÄ>ˆ…þ/´iÙÄ‚Zg|‡Ö\$¼iÖcq£Ù¤4Áu	È´§q”£öÉ:ŒÜ•iTgèÒ1¢¢Å‹;4³/ygË\0#¢âZË5lWÈØÐ©äáFRŠêÙ–)ü¹ÊÐ¼ç+FmdK%©Dâ—W‹c™Ë\$‚† T]òJxOsÆ9…t’~*˜v0®âR2]œ\n†\"ìƒ8‚1Cb ¢”BY­´è«(awCÔd¥8B2\\PÔrÁõÈà&ˆÒp¬lX¡“c/ÄYŒÄÉ‚pì_óˆOÄY%3\ný”Kí³‹	LÎ'œ_:ˆ÷JN¤S‹€«!DC‘fqœë6“Ð{Î9œw6u´YÂ;Dw\"]Îqæ?tëóò³b†Ã»‰µv,iyÒq‚'%1Æû95ÜN„?Ñ;g(E\r?I:úv\\^‚JQz¢†Ådþ„ârÔíÙË“·gM2A“ÂŠþuäPÙ-H=çÂè‹P˜\ns4G&Pqâð ŒŠ€íf¬Wˆé“žâù!mâ1¼èX­å‰%Î‰úŠÍ&dèØ~óÁç\nNž8b.³)˜ÌN±OÕNŠñ:rqðyÓîï\"´N+U:šuk±H²³¨çÎ¬\0W:¹„‰ùàó¬b¼N6?ûâuÌ]ÓðÓ°\$E©œy•Ä;cßhWçdO‹€ÊâTìæ=““¢å2‘…â.P˜½2*§ƒÅìe…9fzDôÙÜsÓbø¡;e;¢/3)é-L³c_³^BŽë=ü¦h§¼Ó#±ÀEg²Ë1y0ôP›Ã9B¡\ra\nÔ‰éÉh c\\Cz‡Á1\0–ˆü²Nä‘²íFQ@T=‡vÈ¤¤¯ÃÞ’¿¹ÒQ´:©!ñ\"€‹€ÇFŒH¨1â¦úW’ÎR|»qÄñ`'ËŠX2NP@\\–}æïSzÏ¢Là£[š~öt	›™ÐFþiˆŸ©R1(%n‚Ÿ“åì…\np>ò¯5àoÓïðKLœ%-0¹ƒsVÕÿµ¶––˜îºgÙø£çä¹GKj•Á¼8ekŽ™®³Sä\n)-2˜€q3öT·ƒBŸ²š~ú~¶ÊTçì©Kï?•½¬ý•y)¿BHÏã-å?e¥ãCÄÊ§hÚx5¶|¢v}ôÿfŸ‰{´ÿhÖØ¦bYNLÅ8;@KbxW€þBÉP²³ÉYy%+¹ÍK2Ö“Ä¡Ã@À1\0c\0º–í?¼0>\0ÊD'9@…¿;5ÕhÀqŒ•Ãºkyž›šï#A»Ð.=<ò6iDR³þh4†rE9Êq‰ôªY“ ©_1@ñUºJ(GPK:\n´ö~ZiÃ@Áª,KAUl\\ú\nÃüSO¶Ó2šd¸*jô¢›,dh¯Á\\ðDÊ\r4º'WVÊ=®ÊVØhàŒ®+e\"\\­”uƒ¢\0Q•ÏÖú´Ñ¡Ú¶S4 „1«eMf­•6²œ\$×4ŠÜ€xKæÅœ\n_9õ\"8A‚¥óO½ÞÉÔu``V@;P|\nÈÎíX´´(A\0'kVØ=Acn°fPP§sRm¡B%5Nô+ÉªVŸd«å°}`&”+P…¡p4uÇ‚ô²)²NrðX\\@`©¿ÚÃ\nÉC)q2–Ê¾hN«¡;ø3pºáž`ÕPÜi¢¢q^\ræ0`ðÖí”g¡Ä\n*‡ ÷T7šPìÆåâ¨K@æ”<þ®Ÿt Ê†â«¸+7F‹¡9zã¾‡Í	— kJ4š¢Cøè­²…jAâQ¡öu’‡‹Ð;!Ph\0…xzŸ–ˆº75\0Õ(p©À:†ž¥]£5Ñ>@À•&œ¢8†‰ Š\$Êp\0×QML¾‰X¦Qb)º°Ñ.\"®Ïlma1€È¶Ñ672!¦Â¨07 ¢…“<zH|Ó4ëLFœM6Úp‹'i½	ðÜAE5¿Kƒ¢Qg\rEY}yô§]Â,Ö1EbŠê‚xq‚™hC©À¡5œD²z™ÿí¨Ã<áèk:]wÂƒŽÐ“Mÿ?´óÀúÃYÀ8Éw¥B`±*­Fh*ÂZRàÂŒdÞÎg/XV:Ð1z§\n1Ôbå/,Õ£æpjû2t_×-TÒ8h\nËiš2ò—›€‚L FQªEF¡fÃÕ²Q¨›F©Ç8›iñ”a“C™£6Ó¢Œz‹%ônMHw%€¬v‰èÖp.ªÇMI®ì&¬pÕ\rÑ¬ôqLG¦£–ŸØL3Yz´sÖÞP)TâþŽ bð+sòÎõ5y£¹G‹j;'­\0¢™h£³?òŽ}¹õ`aYÐQxfZŸD³@0Ê©kÃÁ¥‰NrŸ*£nª>OçÒ´ AG±¿ dz@\0À¨fN¡œŸ*øEPÊ®^(äR¤OB½ 0Gt‚gÚ¥À¡ˆÒp!ÊÝ¶€~èõ'¦¡\0K‚- :-ä©›gêæë˜0#®`Àâ´\0ô\rÔ°¸UÐÿÃŸÑ5¤dµ€m	êséYžÒ22”Bˆ­™þ Õ'úQ¿\n¢ö~Å\$w€\nÈ	†næ\rR	®êJ\0Qe\$§¤¦%XèŠ`\nQªS,w(&’Äþ\nL‡@ðÒWx§@ðÌÔç4šAõR`\\!I\\`PÚÚJíd”ŒRsk DÇ-%sWôž‹gSŽójP«u&:”í¹êt0Á€Ò¡A·C†–\\û0¬;v” E¡&ªe#‚'Mœ\$¢ÿÃ²\0iLÈ“ò·É/óÑFk@Ì5©7,D0W¢æ¶\0(­&rI¬X2è@âHÑHb>a~•˜b4@ÓG9¥p¾þ•Ý)\084«ZzÒ²¥€\nPªoðô®@:\0O8Æ\$9o\nÆ€2ÒÄ:[K0áÄàCÙ°‹)AÁ¥\nè!äRjuø)•g÷…yxÞ{;u\0M€Â»ß ÀS\$èÁÔu2	·Á)¦aOÄ©|£h•iÃk\\3VH£å=ˆ<¯\0");}else{header("Content-Type: image/gif");switch($_GET["file"]){case"plus.gif":echo"GIF89a\0\0\0001îîî\0\0€™™™\0\0\0!ù\0\0\0,\0\0\0\0\0\0!„©ËíMñÌ*)¾oú¯) q•¡eˆµî#ÄòLË\0;";break;case"cross.gif":echo"GIF89a\0\0\0001îîî\0\0€™™™\0\0\0!ù\0\0\0,\0\0\0\0\0\0#„©Ëí#\naÖFo~yÃ._wa”á1ç±JîGÂL×6]\0\0;";break;case"up.gif":echo"GIF89a\0\0\0001îîî\0\0€™™™\0\0\0!ù\0\0\0,\0\0\0\0\0\0 „©ËíMQN\nï}ôža8ŠyšaÅ¶®\0Çò\0;";break;case"down.gif":echo"GIF89a\0\0\0001îîî\0\0€™™™\0\0\0!ù\0\0\0,\0\0\0\0\0\0 „©ËíMñÌ*)¾[Wþ\\¢ÇL&ÙœÆ¶•\0Çò\0;";break;case"arrow.gif":echo"GIF89a\0\n\0€\0\0€€€ÿÿÿ!ù\0\0\0,\0\0\0\0\0\n\0\0‚i–±‹ž”ªÓ²Þ»\0\0;";break;}}exit;}function
connection(){global$h;return$h;}function
adminer(){global$b;return$b;}function
idf_unescape($t){$Nd=substr($t,-1);return
str_replace($Nd.$Nd,$Nd,substr($t,1,-1));}function
escape_string($X){return
substr(q($X),1,-1);}function
remove_slashes($Ef,$Gc=false){if(get_magic_quotes_gpc()){while(list($x,$X)=each($Ef)){foreach($X
as$Dd=>$W){unset($Ef[$x][$Dd]);if(is_array($W)){$Ef[$x][stripslashes($Dd)]=$W;$Ef[]=&$Ef[$x][stripslashes($Dd)];}else$Ef[$x][stripslashes($Dd)]=($Gc?$W:stripslashes($W));}}}}function
bracket_escape($t,$Ma=false){static$ph=array(':'=>':1',']'=>':2','['=>':3');return
strtr($t,($Ma?array_flip($ph):$ph));}function
h($P){return
htmlspecialchars(str_replace("\0","",$P),ENT_QUOTES);}function
nbsp($P){return(trim($P)!=""?h($P):"&nbsp;");}function
nl_br($P){return
str_replace("\n","<br>",$P);}function
checkbox($C,$Y,$ab,$Kd="",$Me="",$fb=""){$J="<input type='checkbox' name='$C' value='".h($Y)."'".($ab?" checked":"").($Me?' onclick="'.h($Me).'"':'').">";return($Kd!=""||$fb?"<label".($fb?" class='$fb'":"").">$J".h($Kd)."</label>":$J);}function
optionlist($Re,$pg=null,$Jh=false){$J="";foreach($Re
as$Dd=>$W){$Se=array($Dd=>$W);if(is_array($W)){$J.='<optgroup label="'.h($Dd).'">';$Se=$W;}foreach($Se
as$x=>$X)$J.='<option'.($Jh||is_string($x)?' value="'.h($x).'"':'').(($Jh||is_string($x)?(string)$x:$X)===$pg?' selected':'').'>'.h($X);if(is_array($W))$J.='</optgroup>';}return$J;}function
adminer_html_select($C,$Re,$Y="",$Le=true){if($Le)return"<select name='".h($C)."'".(is_string($Le)?' onchange="'.h($Le).'"':"").">".optionlist($Re,$Y)."</select>";$J="";foreach($Re
as$x=>$X)$J.="<label><input type='radio' name='".h($C)."' value='".h($x)."'".($x==$Y?" checked":"").">".h($X)."</label>";return$J;}function
select_input($Ia,$Re,$Y="",$rf=""){return($Re?"<select$Ia><option value=''>$rf".optionlist($Re,$Y,true)."</select>":"<input$Ia size='10' value='".h($Y)."' placeholder='$rf'>");}function
confirm(){return" onclick=\"return confirm('".lang(0)."');\"";}function
print_fieldset($s,$Sd,$Uh=false,$Me=""){echo"<fieldset><legend><a href='#fieldset-$s' onclick=\"".h($Me)."return !toggle('fieldset-$s');\">$Sd</a></legend><div id='fieldset-$s'".($Uh?"":" class='hidden'").">\n";}function
bold($Ua,$fb=""){return($Ua?" class='active $fb'":($fb?" class='$fb'":""));}function
odd($J=' class="odd"'){static$r=0;if(!$J)$r=-1;return($r++%2?$J:'');}function
js_escape($P){return
addcslashes($P,"\r\n'\\/");}function
json_row($x,$X=null){static$Hc=true;if($Hc)echo"{";if($x!=""){echo($Hc?"":",")."\n\t\"".addcslashes($x,"\r\n\"\\/").'": '.($X!==null?'"'.addcslashes($X,"\r\n\"\\/").'"':'undefined');$Hc=false;}else{echo"\n}\n";$Hc=true;}}function
ini_bool($qd){$X=ini_get($qd);return(preg_match('~^(on|true|yes)$~i',$X)||(int)$X);}function
sid(){static$J;if($J===null)$J=(SID&&!($_COOKIE&&ini_bool("session.use_cookies")));return$J;}function
set_password($Qh,$N,$V,$G){$_SESSION["pwds"][$Qh][$N][$V]=($_COOKIE["adminer_key"]&&is_string($G)?array(encrypt_string($G,$_COOKIE["adminer_key"])):$G);}function
get_password(){$J=get_session("pwds");if(is_array($J))$J=($_COOKIE["adminer_key"]?decrypt_string($J[0],$_COOKIE["adminer_key"]):false);return$J;}function
q($P){global$l;return$l->quote($P);}function
get_vals($H,$e=0){global$h;$J=array();$I=$h->query($H);if(is_object($I)){while($K=$I->fetch_row())$J[]=$K[$e];}return$J;}function
get_key_vals($H,$i=null,$fh=0){global$h;if(!is_object($i))$i=$h;$J=array();$i->timeout=$fh;$I=$i->query($H);$i->timeout=0;if(is_object($I)){while($K=$I->fetch_row())$J[$K[0]]=$K[1];}return$J;}function
get_rows($H,$i=null,$m="<p class='error'>"){global$h;$rb=(is_object($i)?$i:$h);$J=array();$I=$rb->query($H);if(is_object($I)){while($K=$I->fetch_assoc())$J[]=$K;}elseif(!$I&&!is_object($i)&&$m&&defined("PAGE_HEADER"))echo$m.error()."\n";return$J;}function
unique_array($K,$v){foreach($v
as$u){if(preg_match("~PRIMARY|UNIQUE~",$u["type"])){$J=array();foreach($u["columns"]as$x){if(!isset($K[$x]))continue
2;$J[$x]=$K[$x];}return$J;}}}function
where($Z,$o=array()){global$w;$J=array();$Rc='(^[\w\(]+('.str_replace("_",".*",preg_quote(idf_escape("_"))).')?\)+$)';foreach((array)$Z["where"]as$x=>$X){$x=bracket_escape($x,1);$e=(preg_match($Rc,$x)?$x:idf_escape($x));$J[]=$e.(($w=="sql"&&preg_match('~^[0-9]*\\.[0-9]*$~',$X))||$w=="mssql"?" LIKE ".q(addcslashes($X,"%_\\")):" = ".unconvert_field($o[$x],q($X)));if($w=="sql"&&preg_match('~char|text~',$o[$x]["type"])&&preg_match("~[^ -@]~",$X))$J[]="$e = ".q($X)." COLLATE utf8_bin";}foreach((array)$Z["null"]as$x)$J[]=(preg_match($Rc,$x)?$x:idf_escape($x))." IS NULL";return
implode(" AND ",$J);}function
where_check($X,$o=array()){parse_str($X,$Za);remove_slashes(array(&$Za));return
where($Za,$o);}function
where_link($r,$e,$Y,$Ne="="){return"&where%5B$r%5D%5Bcol%5D=".urlencode($e)."&where%5B$r%5D%5Bop%5D=".urlencode(($Y!==null?$Ne:"IS NULL"))."&where%5B$r%5D%5Bval%5D=".urlencode($Y);}function
convert_fields($f,$o,$M=array()){$J="";foreach($f
as$x=>$X){if($M&&!in_array(idf_escape($x),$M))continue;$Fa=convert_field($o[$x]);if($Fa)$J.=", $Fa AS ".idf_escape($x);}return$J;}function
cookie($C,$Y,$Ud=2592000){global$ba;$F=array($C,(preg_match("~\n~",$Y)?"":$Y),($Ud?time()+$Ud:0),preg_replace('~\\?.*~','',$_SERVER["REQUEST_URI"]),"",$ba);if(version_compare(PHP_VERSION,'5.2.0')>=0)$F[]=true;return
call_user_func_array('setcookie',$F);}function
restart_session(){if(!ini_bool("session.use_cookies"))session_start();}function
stop_session(){if(!ini_bool("session.use_cookies"))session_write_close();}function&get_session($x){return$_SESSION[$x][DRIVER][SERVER][$_GET["username"]];}function
set_session($x,$X){$_SESSION[$x][DRIVER][SERVER][$_GET["username"]]=$X;}function
auth_url($Qh,$N,$V,$k=null){global$Ub;preg_match('~([^?]*)\\??(.*)~',remove_from_uri(implode("|",array_keys($Ub))."|username|".($k!==null?"db|":"").session_name()),$B);return"$B[1]?".(sid()?SID."&":"").($Qh!="server"||$N!=""?urlencode($Qh)."=".urlencode($N)."&":"")."username=".urlencode($V).($k!=""?"&db=".urlencode($k):"").($B[2]?"&$B[2]":"");}function
is_ajax(){return($_SERVER["HTTP_X_REQUESTED_WITH"]=="XMLHttpRequest");}function
redirect($A,$je=null){if($je!==null){restart_session();$_SESSION["messages"][preg_replace('~^[^?]*~','',($A!==null?$A:$_SERVER["REQUEST_URI"]))][]=$je;}if($A!==null){if($A=="")$A=".";header("Location: $A");exit;}}function
query_redirect($H,$A,$je,$Of=true,$tc=true,$Ac=false,$eh=""){global$h,$m,$b;if($tc){$Dg=microtime(true);$Ac=!$h->query($H);$eh=format_time($Dg);}$Bg="";if($H)$Bg=$b->messageQuery($H,$eh);if($Ac){$m=error().$Bg;return
false;}if($Of)redirect($A,$je.$Bg);return
true;}function
queries($H){global$h;static$If=array();static$Dg;if(!$Dg)$Dg=microtime(true);if($H===null)return
array(implode("\n",$If),format_time($Dg));$If[]=(preg_match('~;$~',$H)?"DELIMITER ;;\n$H;\nDELIMITER ":$H).";";return$h->query($H);}function
apply_queries($H,$S,$pc='table'){foreach($S
as$Q){if(!queries("$H ".$pc($Q)))return
false;}return
true;}function
queries_redirect($A,$je,$Of){list($If,$eh)=queries(null);return
query_redirect($If,$A,$je,$Of,false,!$Of,$eh);}function
format_time($Dg){return
lang(1,max(0,microtime(true)-$Dg));}function
remove_from_uri($ff=""){return
substr(preg_replace("~(?<=[?&])($ff".(SID?"":"|".session_name()).")=[^&]*&~",'',"$_SERVER[REQUEST_URI]&"),0,-1);}function
pagination($E,$Cb){return" ".($E==$Cb?$E+1:'<a href="'.h(remove_from_uri("page").($E?"&page=$E".($_GET["next"]?"&next=".urlencode($_GET["next"]):""):"")).'">'.($E+1)."</a>");}function
get_file($x,$Jb=false){$Ec=$_FILES[$x];if(!$Ec)return
null;foreach($Ec
as$x=>$X)$Ec[$x]=(array)$X;$J='';foreach($Ec["error"]as$x=>$m){if($m)return$m;$C=$Ec["name"][$x];$mh=$Ec["tmp_name"][$x];$tb=file_get_contents($Jb&&preg_match('~\\.gz$~',$C)?"compress.zlib://$mh":$mh);if($Jb){$Dg=substr($tb,0,3);if(function_exists("iconv")&&preg_match("~^\xFE\xFF|^\xFF\xFE~",$Dg,$Uf))$tb=iconv("utf-16","utf-8",$tb);elseif($Dg=="\xEF\xBB\xBF")$tb=substr($tb,3);$J.=$tb."\n\n";}else$J.=$tb;}return$J;}function
upload_error($m){$ge=($m==UPLOAD_ERR_INI_SIZE?ini_get("upload_max_filesize"):0);return($m?lang(2).($ge?" ".lang(3,$ge):""):lang(4));}function
repeat_pattern($pf,$y){return
str_repeat("$pf{0,65535}",$y/65535)."$pf{0,".($y%65535)."}";}function
is_utf8($X){return(preg_match('~~u',$X)&&!preg_match('~[\\0-\\x8\\xB\\xC\\xE-\\x1F]~',$X));}function
shorten_utf8($P,$y=80,$Kg=""){if(!preg_match("(^(".repeat_pattern("[\t\r\n -\x{FFFF}]",$y).")($)?)u",$P,$B))preg_match("(^(".repeat_pattern("[\t\r\n -~]",$y).")($)?)",$P,$B);return
h($B[1]).$Kg.(isset($B[2])?"":"<i>...</i>");}function
format_number($X){return
strtr(number_format($X,0,".",lang(5)),preg_split('~~u',lang(6),-1,PREG_SPLIT_NO_EMPTY));}function
friendly_url($X){return
preg_replace('~[^a-z0-9_]~i','-',$X);}function
hidden_fields($Ef,$jd=array()){while(list($x,$X)=each($Ef)){if(!in_array($x,$jd)){if(is_array($X)){foreach($X
as$Dd=>$W)$Ef[$x."[$Dd]"]=$W;}else
echo'<input type="hidden" name="'.h($x).'" value="'.h($X).'">';}}}function
hidden_fields_get(){echo(sid()?'<input type="hidden" name="'.session_name().'" value="'.h(session_id()).'">':''),(SERVER!==null?'<input type="hidden" name="'.DRIVER.'" value="'.h(SERVER).'">':""),'<input type="hidden" name="username" value="'.h($_GET["username"]).'">';}function
table_status1($Q,$Bc=false){$J=table_status($Q,$Bc);return($J?$J:array("Name"=>$Q));}function
column_foreign_keys($Q){global$b;$J=array();foreach($b->foreignKeys($Q)as$p){foreach($p["source"]as$X)$J[$X][]=$p;}return$J;}function
enum_input($U,$Ia,$n,$Y,$jc=null){global$b;preg_match_all("~'((?:[^']|'')*)'~",$n["length"],$be);$J=($jc!==null?"<label><input type='$U'$Ia value='$jc'".((is_array($Y)?in_array($jc,$Y):$Y===0)?" checked":"")."><i>".lang(7)."</i></label>":"");foreach($be[1]as$r=>$X){$X=stripcslashes(str_replace("''","'",$X));$ab=(is_int($Y)?$Y==$r+1:(is_array($Y)?in_array($r+1,$Y):$Y===$X));$J.=" <label><input type='$U'$Ia value='".($r+1)."'".($ab?' checked':'').'>'.h($b->editVal($X,$n)).'</label>';}return$J;}function
input($n,$Y,$q){global$h,$yh,$b,$w;$C=h(bracket_escape($n["field"]));echo"<td class='function'>";if(is_array($Y)&&!$q){$Da=array($Y);if(version_compare(PHP_VERSION,5.4)>=0)$Da[]=JSON_PRETTY_PRINT;$Y=call_user_func_array('json_encode',$Da);$q="json";}$Xf=($w=="mssql"&&$n["auto_increment"]);if($Xf&&!$_POST["save"])$q=null;$Sc=(isset($_GET["select"])||$Xf?array("orig"=>lang(8)):array())+$b->editFunctions($n);$Ia=" name='fields[$C]'";if($n["type"]=="enum")echo
nbsp($Sc[""])."<td>".$b->editInput($_GET["edit"],$n,$Ia,$Y);else{$Hc=0;foreach($Sc
as$x=>$X){if($x===""||!$X)break;$Hc++;}$Le=($Hc?" onchange=\"var f = this.form['function[".h(js_escape(bracket_escape($n["field"])))."]']; if ($Hc > f.selectedIndex) f.selectedIndex = $Hc;\" onkeyup='keyupChange.call(this);'":"");$Ia.=$Le;$ad=(in_array($q,$Sc)||isset($Sc[$q]));echo(count($Sc)>1?"<select name='function[$C]' onchange='functionChange(this);'".on_help("getTarget(event).value.replace(/^SQL\$/, '')",1).">".optionlist($Sc,$q===null||$ad?$q:"")."</select>":nbsp(reset($Sc))).'<td>';$sd=$b->editInput($_GET["edit"],$n,$Ia,$Y);if($sd!="")echo$sd;elseif($n["type"]=="set"){preg_match_all("~'((?:[^']|'')*)'~",$n["length"],$be);foreach($be[1]as$r=>$X){$X=stripcslashes(str_replace("''","'",$X));$ab=(is_int($Y)?($Y>>$r)&1:in_array($X,explode(",",$Y),true));echo" <label><input type='checkbox' name='fields[$C][$r]' value='".(1<<$r)."'".($ab?' checked':'')."$Le>".h($b->editVal($X,$n)).'</label>';}}elseif(preg_match('~blob|bytea|raw|file~',$n["type"])&&ini_bool("file_uploads"))echo"<input type='file' name='fields-$C'$Le>";elseif(($ch=preg_match('~text|lob~',$n["type"]))||preg_match("~\n~",$Y)){if($ch&&$w!="sqlite")$Ia.=" cols='50' rows='12'";else{$L=min(12,substr_count($Y,"\n")+1);$Ia.=" cols='30' rows='$L'".($L==1?" style='height: 1.2em;'":"");}echo"<textarea$Ia>".h($Y).'</textarea>';}elseif($q=="json")echo"<textarea$Ia cols='50' rows='12' class='jush-js'>".h($Y).'</textarea>';else{$ie=(!preg_match('~int~',$n["type"])&&preg_match('~^(\\d+)(,(\\d+))?$~',$n["length"],$B)?((preg_match("~binary~",$n["type"])?2:1)*$B[1]+($B[3]?1:0)+($B[2]&&!$n["unsigned"]?1:0)):($yh[$n["type"]]?$yh[$n["type"]]+($n["unsigned"]?0:1):0));if($w=='sql'&&$h->server_info>=5.6&&preg_match('~time~',$n["type"]))$ie+=7;echo"<input".((!$ad||$q==="")&&preg_match('~(?<!o)int~',$n["type"])?" type='number'":"")." value='".h($Y)."'".($ie?" maxlength='$ie'":"").(preg_match('~char|binary~',$n["type"])&&$ie>20?" size='40'":"")."$Ia>";}}}function
process_input($n){global$b;$t=bracket_escape($n["field"]);$q=$_POST["function"][$t];$Y=$_POST["fields"][$t];if($n["type"]=="enum"){if($Y==-1)return
false;if($Y=="")return"NULL";return+$Y;}if($n["auto_increment"]&&$Y=="")return
null;if($q=="orig")return($n["on_update"]=="CURRENT_TIMESTAMP"?idf_escape($n["field"]):false);if($q=="NULL")$Y=null;if($n["type"]=="set")return
array_sum((array)$Y);if($q=="json"){$q="";$Y=json_decode($Y,true);if(!is_array($Y))return
false;return$Y;}if(preg_match('~blob|bytea|raw|file~',$n["type"])&&ini_bool("file_uploads")){$Ec=get_file("fields-$t");if(!is_string($Ec))return
false;return
q($Ec);}return$b->processInput($n,$Y,$q);}function
fields_from_edit(){global$l;$J=array();foreach((array)$_POST["field_keys"]as$x=>$X){if($X!=""){$X=bracket_escape($X);$_POST["function"][$X]=$_POST["field_funs"][$x];$_POST["fields"][$X]=$_POST["field_vals"][$x];}}foreach((array)$_POST["fields"]as$x=>$X){$C=bracket_escape($x,1);$J[$C]=array("field"=>$C,"privileges"=>array("insert"=>1,"update"=>1),"null"=>1,"auto_increment"=>($x==$l->primary),);}return$J;}function
search_tables(){global$b,$h;$_GET["where"][0]["op"]="LIKE %%";$_GET["where"][0]["val"]=$_POST["query"];$Nc=false;foreach(table_status('',true)as$Q=>$R){$C=$b->tableName($R);if(isset($R["Engine"])&&$C!=""&&(!$_POST["tables"]||in_array($Q,$_POST["tables"]))){$I=$h->query("SELECT".limit("1 FROM ".table($Q)," WHERE ".implode(" AND ",$b->selectSearchProcess(fields($Q),array())),1));if(!$I||$I->fetch_row()){if(!$Nc){echo"<ul>\n";$Nc=true;}echo"<li>".($I?"<a href='".h(ME."select=".urlencode($Q)."&where[0][op]=".urlencode($_GET["where"][0]["op"])."&where[0][val]=".urlencode($_GET["where"][0]["val"]))."'>$C</a>\n":"$C: <span class='error'>".error()."</span>\n");}}}echo($Nc?"</ul>":"<p class='message'>".lang(9))."\n";}function
dump_headers($hd,$se=false){global$b;$J=$b->dumpHeaders($hd,$se);$df=$_POST["output"];if($df!="text")header("Content-Disposition: attachment; filename=".$b->dumpFilename($hd).".$J".($df!="file"&&!preg_match('~[^0-9a-z]~',$df)?".$df":""));session_write_close();ob_flush();flush();return$J;}function
dump_csv($K){foreach($K
as$x=>$X){if(preg_match("~[\"\n,;\t]~",$X)||$X==="")$K[$x]='"'.str_replace('"','""',$X).'"';}echo
implode(($_POST["format"]=="csv"?",":($_POST["format"]=="tsv"?"\t":";")),$K)."\r\n";}function
apply_sql_function($q,$e){return($q?($q=="unixepoch"?"DATETIME($e, '$q')":($q=="count distinct"?"COUNT(DISTINCT ":strtoupper("$q("))."$e)"):$e);}function
get_temp_dir(){$J=ini_get("upload_tmp_dir");if(!$J){if(function_exists('sys_get_temp_dir'))$J=sys_get_temp_dir();else{$Fc=@tempnam("","");if(!$Fc)return
false;$J=dirname($Fc);unlink($Fc);}}return$J;}function
password_file($zb){$Fc=get_temp_dir()."/adminer.key";$J=@file_get_contents($Fc);if($J||!$zb)return$J;$Pc=@fopen($Fc,"w");if($Pc){$J=rand_string();fwrite($Pc,$J);fclose($Pc);}return$J;}function
rand_string(){return
md5(uniqid(mt_rand(),true));}function
select_value($X,$_,$n,$dh){global$b,$ba;if(is_array($X)){$J="";foreach($X
as$Dd=>$W)$J.="<tr>".($X!=array_values($X)?"<th>".h($Dd):"")."<td>".select_value($W,$_,$n,$dh);return"<table cellspacing='0'>$J</table>";}if(!$_)$_=$b->selectLink($X,$n);if($_===null){if(is_mail($X))$_="mailto:$X";if($Gf=is_url($X))$_=(($Gf=="http"&&$ba)||preg_match('~WebKit~i',$_SERVER["HTTP_USER_AGENT"])?$X:"$Gf://www.adminer.org/redirect/?url=".urlencode($X));}$J=$b->editVal($X,$n);if($J!==null){if($J==="")$J="&nbsp;";elseif($dh!=""&&is_shortable($n)&&is_utf8($J))$J=shorten_utf8($J,max(0,+$dh));else$J=h($J);}return$b->selectVal($J,$_,$n,$X);}function
is_mail($gc){$Ga='[-a-z0-9!#$%&\'*+/=?^_`{|}~]';$Tb='[a-z0-9]([-a-z0-9]{0,61}[a-z0-9])';$pf="$Ga+(\\.$Ga+)*@($Tb?\\.)+$Tb";return
is_string($gc)&&preg_match("(^$pf(,\\s*$pf)*\$)i",$gc);}function
is_url($P){$Tb='[a-z0-9]([-a-z0-9]{0,61}[a-z0-9])';return(preg_match("~^(https?)://($Tb?\\.)+$Tb(:\\d+)?(/.*)?(\\?.*)?(#.*)?\$~i",$P,$B)?strtolower($B[1]):"");}function
is_shortable($n){return
preg_match('~char|text|lob|geometry|point|linestring|polygon|string~',$n["type"]);}function
count_rows($Q,$Z,$yd,$Vc){global$w;$H=" FROM ".table($Q).($Z?" WHERE ".implode(" AND ",$Z):"");return($yd&&($w=="sql"||count($Vc)==1)?"SELECT COUNT(DISTINCT ".implode(", ",$Vc).")$H":"SELECT COUNT(*)".($yd?" FROM (SELECT 1$H$Wc) x":$H));}function
slow_query($H){global$b,$T;$k=$b->database();$fh=$b->queryTimeout();if(support("kill")&&is_object($i=connect())&&($k==""||$i->select_db($k))){$Id=$i->result("SELECT CONNECTION_ID()");echo'<script type="text/javascript">
var timeout = setTimeout(function () {
	ajax(\'',js_escape(ME),'script=kill\', function () {
	}, \'token=',$T,'&kill=',$Id,'\');
}, ',1000*$fh,');
</script>
';}else$i=null;ob_flush();flush();$J=@get_key_vals($H,$i,$fh);if($i){echo"<script type='text/javascript'>clearTimeout(timeout);</script>\n";ob_flush();flush();}return
array_keys($J);}function
get_token(){$Lf=rand(1,1e6);return($Lf^$_SESSION["token"]).":$Lf";}function
verify_token(){list($T,$Lf)=explode(":",$_POST["token"]);return($Lf^$_SESSION["token"])==$T;}function
lzw_decompress($Qa){$Qb=256;$Ra=8;$hb=array();$Zf=0;$ag=0;for($r=0;$r<strlen($Qa);$r++){$Zf=($Zf<<8)+ord($Qa[$r]);$ag+=8;if($ag>=$Ra){$ag-=$Ra;$hb[]=$Zf>>$ag;$Zf&=(1<<$ag)-1;$Qb++;if($Qb>>$Ra)$Ra++;}}$Pb=range("\0","\xFF");$J="";foreach($hb
as$r=>$gb){$fc=$Pb[$gb];if(!isset($fc))$fc=$Yh.$Yh[0];$J.=$fc;if($r)$Pb[]=$Yh.$fc[0];$Yh=$fc;}return$J;}function
on_help($mb,$xg=0){return" onmouseover='helpMouseover(this, event, ".h($mb).", $xg);' onmouseout='helpMouseout(this, event);'";}function
edit_form($a,$o,$K,$Fh){global$b,$w,$T,$m;$Pg=$b->tableName(table_status1($a,true));page_header(($Fh?lang(10):lang(11)),$m,array("select"=>array($a,$Pg)),$Pg);if($K===false)echo"<p class='error'>".lang(12)."\n";echo'<div id="message"></div>
<form action="" method="post" enctype="multipart/form-data" id="form">
';if(!$o)echo"<p class='error'>".lang(13)."\n";else{echo"<table cellspacing='0' onkeydown='return editingKeydown(event);'>\n";foreach($o
as$C=>$n){echo"<tr><th>".$b->fieldName($n);$Kb=$_GET["set"][bracket_escape($C)];if($Kb===null){$Kb=$n["default"];if($n["type"]=="bit"&&preg_match("~^b'([01]*)'\$~",$Kb,$Uf))$Kb=$Uf[1];}$Y=($K!==null?($K[$C]!=""&&$w=="sql"&&preg_match("~enum|set~",$n["type"])?(is_array($K[$C])?array_sum($K[$C]):+$K[$C]):$K[$C]):(!$Fh&&$n["auto_increment"]?"":(isset($_GET["select"])?false:$Kb)));if(!$_POST["save"]&&is_string($Y))$Y=$b->editVal($Y,$n);$q=($_POST["save"]?(string)$_POST["function"][$C]:($Fh&&$n["on_update"]=="CURRENT_TIMESTAMP"?"now":($Y===false?null:($Y!==null?'':'NULL'))));if(preg_match("~time~",$n["type"])&&$Y=="CURRENT_TIMESTAMP"){$Y="";$q="now";}input($n,$Y,$q);echo"\n";}if(!support("table"))echo"<tr>"."<th><input name='field_keys[]' onkeyup='keyupChange.call(this);' onchange='fieldChange(this);' value=''>"."<td class='function'>".adminer_html_select("field_funs[]",$b->editFunctions(array("null"=>isset($_GET["select"]))))."<td><input name='field_vals[]'>"."\n";echo"</table>\n";}echo"<p>\n";if($o){echo"<input type='submit' value='".lang(14)."'>\n";if(!isset($_GET["select"]))echo"<input type='submit' name='insert' value='".($Fh?lang(15)."' onclick='return !ajaxForm(this.form, \"".lang(16).'...", this)':lang(17))."' title='Ctrl+Shift+Enter'>\n";}echo($Fh?"<input type='submit' name='delete' value='".lang(18)."'".confirm().">\n":($_POST||!$o?"":"<script type='text/javascript'>focus(document.getElementById('form').getElementsByTagName('td')[1].firstChild);</script>\n"));if(isset($_GET["select"]))hidden_fields(array("check"=>(array)$_POST["check"],"clone"=>$_POST["clone"],"all"=>$_POST["all"]));echo'<input type="hidden" name="referer" value="',h(isset($_POST["referer"])?$_POST["referer"]:$_SERVER["HTTP_REFERER"]),'">
<input type="hidden" name="save" value="1">
<input type="hidden" name="token" value="',$T,'">
</form>
';}global$b,$h,$Ub,$cc,$mc,$m,$Sc,$Xc,$ba,$rd,$w,$ca,$Md,$Ke,$qf,$Hg,$bd,$T,$rh,$yh,$Eh,$ia;if(!$_SERVER["REQUEST_URI"])$_SERVER["REQUEST_URI"]=$_SERVER["ORIG_PATH_INFO"];if(!strpos($_SERVER["REQUEST_URI"],'?')&&$_SERVER["QUERY_STRING"]!="")$_SERVER["REQUEST_URI"].="?$_SERVER[QUERY_STRING]";$ba=$_SERVER["HTTPS"]&&strcasecmp($_SERVER["HTTPS"],"off");@ini_set("session.use_trans_sid",false);session_cache_limiter("");if(!defined("SID")){session_name("adminer_sid");$F=array(0,preg_replace('~\\?.*~','',$_SERVER["REQUEST_URI"]),"",$ba);if(version_compare(PHP_VERSION,'5.2.0')>=0)$F[]=true;call_user_func_array('session_set_cookie_params',$F);session_start();}remove_slashes(array(&$_GET,&$_POST,&$_COOKIE),$Gc);if(get_magic_quotes_runtime())set_magic_quotes_runtime(false);@set_time_limit(0);@ini_set("zend.ze1_compatibility_mode",false);@ini_set("precision",20);$Md=array('en'=>'English','ar'=>'Ø§Ù„Ø¹Ø±Ø¨ÙŠØ©','bn'=>'à¦¬à¦¾à¦‚à¦²à¦¾','ca'=>'CatalÃ ','cs'=>'ÄŒeÅ¡tina','de'=>'Deutsch','es'=>'EspaÃ±ol','et'=>'Eesti','fa'=>'ÙØ§Ø±Ø³ÛŒ','fr'=>'FranÃ§ais','hu'=>'Magyar','id'=>'Bahasa Indonesia','it'=>'Italiano','ja'=>'æ—¥æœ¬èªž','ko'=>'í•œêµ­ì–´','lt'=>'LietuviÅ³','nl'=>'Nederlands','no'=>'Norsk','pl'=>'Polski','pt'=>'PortuguÃªs','pt-br'=>'PortuguÃªs (Brazil)','ro'=>'Limba RomÃ¢nÄƒ','ru'=>'Ð ÑƒÑÑÐºÐ¸Ð¹ ÑÐ·Ñ‹Ðº','sk'=>'SlovenÄina','sl'=>'Slovenski','sr'=>'Ð¡Ñ€Ð¿ÑÐºÐ¸','ta'=>'à®¤â€Œà®®à®¿à®´à¯','th'=>'à¸ à¸²à¸©à¸²à¹„à¸—à¸¢','tr'=>'TÃ¼rkÃ§e','uk'=>'Ð£ÐºÑ€Ð°Ñ—Ð½ÑÑŒÐºÐ°','vi'=>'Tiáº¿ng Viá»‡t','zh'=>'ç®€ä½“ä¸­æ–‡','zh-tw'=>'ç¹é«”ä¸­æ–‡',);function
get_lang(){global$ca;return$ca;}function
lang($t,$Be=null){if(is_string($t)){$tf=array_search($t,get_translations("en"));if($tf!==false)$t=$tf;}global$ca,$rh;$qh=($rh[$t]?$rh[$t]:$t);if(is_array($qh)){$tf=($Be==1?0:($ca=='cs'||$ca=='sk'?($Be&&$Be<5?1:2):($ca=='fr'?(!$Be?0:1):($ca=='pl'?($Be%10>1&&$Be%10<5&&$Be/10%10!=1?1:2):($ca=='sl'?($Be%100==1?0:($Be%100==2?1:($Be%100==3||$Be%100==4?2:3))):($ca=='lt'?($Be%10==1&&$Be%100!=11?0:($Be%10>1&&$Be/10%10!=1?1:2)):($ca=='ru'||$ca=='sr'||$ca=='uk'?($Be%10==1&&$Be%100!=11?0:($Be%10>1&&$Be%10<5&&$Be/10%10!=1?1:2)):1)))))));$qh=$qh[$tf];}$Da=func_get_args();array_shift($Da);$Mc=str_replace("%d","%s",$qh);if($Mc!=$qh)$Da[0]=format_number($Be);return
vsprintf($Mc,$Da);}function
switch_lang(){global$ca,$Md;echo"<form action='' method='post'>\n<div id='lang'>",lang(19).": ".adminer_html_select("lang",$Md,$ca,"this.form.submit();")," <input type='submit' value='".lang(20)."' class='hidden'>\n","<input type='hidden' name='token' value='".get_token()."'>\n";echo"</div>\n</form>\n";}if(isset($_POST["lang"])&&verify_token()){cookie("adminer_lang",$_POST["lang"]);$_SESSION["lang"]=$_POST["lang"];$_SESSION["translations"]=array();redirect(remove_from_uri());}$ca="en";if(isset($Md[$_COOKIE["adminer_lang"]])){cookie("adminer_lang",$_COOKIE["adminer_lang"]);$ca=$_COOKIE["adminer_lang"];}elseif(isset($Md[$_SESSION["lang"]]))$ca=$_SESSION["lang"];else{$ua=array();preg_match_all('~([-a-z]+)(;q=([0-9.]+))?~',str_replace("_","-",strtolower($_SERVER["HTTP_ACCEPT_LANGUAGE"])),$be,PREG_SET_ORDER);foreach($be
as$B)$ua[$B[1]]=(isset($B[3])?$B[3]:1);arsort($ua);foreach($ua
as$x=>$Hf){if(isset($Md[$x])){$ca=$x;break;}$x=preg_replace('~-.*~','',$x);if(!isset($ua[$x])&&isset($Md[$x])){$ca=$x;break;}}}$rh=&$_SESSION["translations"];if($_SESSION["translations_version"]!=3124109916){$rh=array();$_SESSION["translations_version"]=3124109916;}function
get_translations($Ld){switch($Ld){case"en":$g="A9D“yÔ@s:ÀGà¡(¸ffƒ‚Š¦ã	ˆÙ:ÄS°Þa2\"1¦..L'ƒI´êm‘#Çs,†KƒšOP#IÌ@%9¥i4Èo2ÏÆó €Ë,9%ÀPÀb2£a¸àr\n2›NCÈ(Þr4™Í1C`(:Ebç9AÈi:‰&ã™”åy·ˆFó½ÐY‚ˆ\r´\n– 8ZÔS=\$Aœ†¤`Ñ=ËÜŒ²‚ž0Ê\nÒãdFé	ŒÞn:ZÎ°)­ãQŒµ™öú£°Ak¾ßÄê}äˆe‹çADÍéœêaÊÄ¯ ¢„\\Ã}ö5ð#|@èhÚ3·ÃN¾}@¡ÑiÕ¦«ÁËžN›t¼Å~9‚ˆ™ÈöBØ­8¦:-pÎüˆKXÂ9,¢pÊ:ë8Öã(ß\0À‹(˜ž½­@ò¨¬-BüÆŽN’üŠ@.£®9Â#Èý3ˆ«®Ó‰ƒzÔ7:‹ðÚÞŒ­€@Fñ.1©¬ÚâÔ\r\"²\"Óˆ#c:9˜Ê;RŒ¦Ð¢Í<;·ìèÚ†\$#òÎ!,Ë3¾‚›2È€PŒ:Ò#Ê¾K#8Î€ŒìäïAcÐ7£Èîð -BÎ¼ŒŠHÇ®ð3––¶Â£‹Ç£;¿,ÎÍ|ä:¦Râp9ŒmëP(‰\\6Çmd²:³ØÆ€À-‚ÌùÇ›M,ÊKðA#FNœµ_TvhøƒÐKÃ.#gfXÖx É2 ’±QoÒ^8YS;Q4ö¤éŠvmŽ¾ÈkÌ¥Æ£:\n¼Šƒz5V(Úù&Ñã˜æ3TülàŒ¼‘O«[~7'éÚÙ3¡pzÞô-ô„wíÿ€È&\r|º…MA7V\nJP½ŽÃØ4¤\"¦)Ì¸Þ5Ç¡pA77Îú3,é†Bµ¥æƒßŠN664šTï¤…“ªˆ@û©È¤º2i·öž§ŽJ2`¨èËiã¸f†7%	TA*\\Zìk‰Ð€ŒÁèD4ƒ àáxïÃ…ÉN¿Ë8ÎÇ¼ƒÅŽs¸^4#“*:oÂú,Þa}mìÕ¨èã|Ÿ§ÐÄ’ÿâ¹ÄÂÈGQã`”0./)?­î @î²ï¡©/é¨@(	ƒÖñò¯0@(JD€¤YjLÑecv[—µ»zØåÌ2T–%É†P6¾ò§—&~2Jä‰òŸG¦»¿Žë;]g³»@Å¹ixá<)…E~ŒYÐR&©4ÏdbïÒ£úYGaŸ:ÅTkÉ)ÎIø×«öìŒi(1R°Â8G‰\"€!ª‚HzŒ™#ÁQåã~·x\$†˜HPË8e-,1œ!ðk‰”G<'\0ª A\nDÀ@(L±MdðØKØb(4áA†0¯£ÿ>HÑL©°žXÓ¹ù8á˜0ªšöÍ™ùh]Ó%ÈHN!â%!¼ÕÉjŠ8wG(6ÈCNËs0€ëE’ƒ÷%é>1f¨û£Õ~`Ú‹wHIÁ\"£ÎiÖKg\\g0.dfuGèpdMù@fqªÂ|‰hnPXï˜0êÃCÇy/-ÝÁÒ€rÑ±•\$'^aÌøˆsPqî\$›<%É¨'R¡‡S(Ò!5?R€½=©\"HV*Î%X4¥wþh¢ì_\r¨à½¨Ðð}5…=6Pä£ç»šAêñ)Çâmb ˆ¯ü´'£ñC˜1i2¨”(;Sˆ™xnBa<<BòÕ„æ/! ™À—™k>Æ›äŒWª MT¿\0 ŠŒÄÙ ÄÈ`Œ\"i\$&Â€ £QWÍl¤„ð–ÁY-L\$A˜3JFîjy~ª3þ©™Ú«UØmH0µn®Î–¸[ˆÒ«U¯Å›´àÊIC%¨•Š»VWU˜•z¨³ÕùVJ¨,>°Ç(¢ˆNGÑ}§dvÈäQ‰é¢­átGOB¸ePù_r5“¤/\"æ Œ‡~ÃõAI	(×†Ê£P9ù	p³–P†u‘)v‡O°½&#uÚ¸e“Ê4äÊ “qŒY>“°˜ÍÊßsË	@¸÷Jå0Ã€_«1(œ	víÜ›—(o¼dâèÞfÔ¯[â4W–éª¥Å(¯YŽŒÆLÓ\$T_ËÃOL×üØÍäñ08wîZ²èó;gñ–“d\nú²ä§s¨xR5Ï˜ÀóÈº;à¤·`É.°Hí¾¡’ôJ,O)ï¥Ý0HöýÖü^´ñV2wZKJlqr0­ð”R¥eÊåÐº›Ö@ÆX°7ÊÞºV®1“ØîðšŒ‘”¯~­áâSaL²Âª}„Ö¬T´”jå^k€'/}Žržcl•¦¦YlÓ[¢¢10äÌžbgˆ•|ç²_¥,ÏZ\n‰hLÃ•%S Ê<ÕŸôæà¼ú/Q«a7‚@•Ò’dŒ²6\nÏž¡.jÍ`Þ°¹ýkôû™Ó\"0Yc|ß4BuxËNÒž‡4{Ð”'L-VÌZJÿLÆ	k=S†&9jyíû‹k|ôJú>ûå»î•v…>¦¥7igëÒ]waÂÚ\"·ÓG¿·)Õo %;qÓ•–w¬»/ôùWnêŒØb«lØ(È”›?KéŽoÉWŸ&î~—øUÜ“Û®øðüÝŠx_Û¤£y°ë¨b¾2!½G†DŸ½—S¨ã\".>GÉy<\n„%†jTkÞºÚü7HóƒgÎ¶–ÉüùéM¥Å%ó/¼æ„rÝAÑ5Ôj>|ï?õ.1Ä¸Öñã—ÀøŸ>PF0èT¢´oªpîÅF•÷GÈW«±öž3}º×!Jˆ´\"^Fh²%\n„´Óîål{ûwX“üò·ø©Ú¶¢&ðý¿¡€uÏ/Í·ø#Áµmƒ³74ì——´¬ÂÉ1Í~rX´¬\$i¯uô9JŽÂˆÉq-¡/Ðúû¡ÛóEm¼5)0–5ê0®wÊ¡\rÀVÙxc\rzÀ‘Ç‚0½ÃywËä¼’qªŠkXaAT*`ZÉ®R!ž¼æm£ÛÉm&«õdÂ{ù9Œ¿J‹”DNù™…Ù)5ŽÆ4á7è†Ÿ¦¥ã‰Cò6¢òðœ.âš¤/–3	úEò;ã@ùÂPŒƒ¢\rƒ®/\$j\$ršR‘§¨X\0Pè¼ I²ŽÉîK¯ÜÂŒdI„êÇN.kŽýé<‘£½Is0TÊlšž°mpõ-Ì¿nÓËêÉ¤\0œp{lJÊšMÅ™-xö	Â¤¿ÌN\0˜¥Ÿ¢Ÿ´Zd #N¦\nš–Ê¦%èªŽ'd²-€ó\rN&FâlJ(N…J9Oø5C0)…ÔiNéÈÊMÎ˜M¥šäËV\"š-Jj2îø¨àÊ¤v\$ÌÂ/§n÷‘' Ù/S¤";break;case"ar":$g="ÙC¶P‚Â²†l*„\r”,&\nÙA¶í„ø(J.™„0T2]6QM…ŒO!bù#eØ\\É¥¤\$¸\\\nl+[\nÈdÊk4—O¡è&ÂÕ²‰…ÀQ)Ì…7lIçò„‚E\$…Ê‘¶Ím_7GT\r•eDÙƒ)*VÊ™³'T6U1ÙzžHØ]N*PZ,¡BT`Šªìî%VDª5ØAU0‰H S‹d!iQl(p(N¯…Â1÷e4înY7Dƒ	ØÊ 7Ä‘¤ìi6LæS˜€é²:œ†¦¼èh4ïN†æ ­—6IÏEq¥ánÔh/\\äQY2ž´Òn3Î'’þ½v	•leîÊŽý†¬ç7©Ftl.nòl?O<B?û¢[%ß!ÅÌ§EzŽ¡-ˆk‰®Ðâ)ƒš ©@ê\n<­§Šònƒ°©Œü¡Ås\"B§!ïã¾Ì*¹\\ì'ÌbˆU'šÌHÐA°U ìÂÜ‘À,ºâˆ®hš‰¿R©íti!Ã/¯q”:†GP\n˜@èé™À\n«eŠ:¢.Ï\n&T ‹ŠF“¡´ˆ‡?IãÆî¯h™\\ÆIL}\nÉ;´U‚CÒòpð–&Ál¡OÌ]„Òë½6ï!ÑìÞ‘•pð<H	LSU\\oH(LýKÉ¥ª°ÃBÉrŒ“ë‘Ðe)±0“úI2*Ú¥Y'‰RF\\§JtU®‰dE+(…=YS6I°TUrjìY>éÊ\$œôœÀ¢§	ÕMUÃ¨Ø67ÎcrŒcÜêMp““¡\0¦(‰•‘ea°ìêk<K©è‡·‹­YOU¡³ˆOSINÐÅVWµt_c›¾RÊI¯“2¬åQnüf9JºÆøò÷\\ÕI³¶VA`\nïÖUÕ{e›†\\úW•¾6Ìc³åÆ«ä:&OfU”*«Ç#è6VT}¡FS¢v\nƒ{d6ßÃÈ@:Ã˜ê1Œmàæ3^Á\0Ø7Œï8æ7Ã–ä0ŒãÎqv\0@6¼ã«ŠaJn!ŠbŒm£XÊ7 “6S.…Öé\r›ÓPj	OP}2ý*PÄ Â!«µ2TÅhl\\½É3\0¨`L”Î¥ð–Ï&ÌX%k#öJ ø0„:å°2É°FtÝDšø}fG+›=IIh&Œ#›Š96Hæ;ã•‚2€Ò9£'J-_ðè\"\rÐ:\0tÁxw@¸0†G ƒ(rÏÄ3‚÷?ƒÁÅn¡¤7†à^—Èr7aÒ\0…óš¿ƒX\"Á\$6‡r\\øt€¼0ƒäBãÃ)Ç\rëß¸ðÂçœ`t6pE¸A\0ÜzCf`Ð‚®IQ9\\+Ì±\r¤:È˜h	ÈÅ!'ÊCZ±!\$q@­E#î˜´UÄ¹m(GÂÉ”JÇQˆ­‡½óIÊÛ!J‘ŽÄÒöAŠ!F)ôê¥·Ê!,O+…!R#×ŠöJk¹M„Ö/Å€ Â˜T!dEN»s¤J[`«ŒÎ¥²jHÅ©'KF.<* YîcdöR.…”žÈZ[<q‚ÆÅ¹–R™–x\0\r9©õÐÿAýA¤3°f1¸5æÝþ„`¨&ˆnX!¦¿„bfA¼6‘¸\"k ’	gˆVº²¸Bê/f=p•uÈÂp \n¡@\"¨@U\0\"„À‹AØ´/hJ2£–š(’p1ñ‰¦\"A‹VbíäáEÌDšeÒjÅì¶ùøfbPcùAlbÉ}+HçÚS‘Ú?)ˆY50.°”š},È„FÉ@™4Ô~ÚšyŒéõ¬3Øöè™Q¥vQ“¡`‰¤3Ta¬îª©ñTóÙDü—5Š\\1úËIÈy–K®¢©VÖyY“ºN¥NÈÄÌ#3IâTNŒuÀ¹ôJAúFIð¦‚AcSc§thôñˆ#ß`QÁ>˜³®YFø4pHï‘“*•Œ‘˜)MkÇ‰%®Œ¹á‹¤aL4‡ Ê‚ìJÛ…0ÊnCÅ‹ÄlUñÍã6eÚ¬¼Dz–i“M”U4ÑH|Z;>dŠÑ“Ýs/_”\$!­*Ž’‡¤…®:U©)cSrONVí.3Ô¾ÉðÄ•\"èSèyb©ªpÇ¯ÔL#¥lî1å+JšM¬NÔ]´™X³\n1#?öÀ¶¥“U–b6B¦al 9\\€ÚÓN3Ò§˜ôd…‘¢VP\$AÐº1FÍãm,vrM1‘e‚_Ó*ÁnÐÁ¢Â^~…ƒ²`)‚8GÎcòG©vNV™2&=E4ƒ,ä*£õ4‘‹-&[*ùŸã6˜Êf7.\$ Ä—aZ€M\\Ç¹‹.ä\$#‘1ÓÈù®;“œ¿.)8‘ÄÁn“[ÓjG?çÌ‚h7vLÊ£\"w\$±2ÄÄÐ‰J­#ÖIÑb„‚ùUEG%ù’ñDj‘6\\•J‘«5ò¶*ñk*gQX*8þÍtdl(ŒÌ‡;d|ÍÓ½L•D Û¥¢*ñAŠä’eû>¬ßÙ‚Çg ò'_…aBBD!]mK=ªî2µº2#oì;¸^Õ´‡Úº&¹hÅIº_5µ‰R6N¼ˆ‚	(›Í«¬ä Ê°¤¥£î¸Zz2wÚšF—t É%ývöŠp»Û6£•½OÆÇÁ{JŠ‚\0P©)+3ƒpéi²J­Qg4èÀ×}%gž<1]«(â<”+§+S•½An¼E®XåS­Éþ«n¾!ú\rm¬³DÚËùSUª2¸l‹‹²¯5üù–â8¹rzÞåèûŒv\nÐšÈŸ33;–ÓîÓ×¤i…ÂâÂd‚…’ñÖMBÙ?4¢=!|³wwží^_ãÊžt?~*~\0œø+íá1îÏc*€x¢=ã/¦·ñéûÈÙ~žZ¼J5º}¬ÞH RÐçDÄŒÄŠ‰cdŒ\"o|¶K5fhÿp…_Öúòxý’\n 8­ >5Œ%ýD!½*óSê:Zž‹L˜—Œœ}âã&^À•û5ºŠ*{Ò~b{y»ñº7\\@¤ç1‹×èaUº‡±mD`u®ÕðûiQuÎžj\n&ÿ¦Xÿå^¹ìäÿeHÃaVèÏìì…Ô\"píŽºÝÂ÷C­W»\rÝC¨U\"&ÖmØýƒæMË¤:ØUä®Cšz,FÜ®P(L,2A\r&þâZ+næÿÐ8eîãPiðoÄ8Häìjø»#4ÝzjŠÉð6iä¯°^EŽ,ÛOÂULä]MÂT‡zwª¶ÑÇxyËÖ(P¿ïÜØ‚	\rp¡	¤£«Ð`êïä£Ä[çN-PÓ\nÂa\n.½E©pb¿&z+ïëb„À\$;Žà/q \"Q\rÁ±ª-f¡DÒ|¤†Y=ðýDbFpÑ²MlN!‘%„á‘P\\®Zl\rîPhA,¬©¡eƒýîìÌ°¨>o¦ŸhÛC\$OØzANïª|B\\ÆÏ‹…¢¤\r²ªpT\"&É/ðg­ºïPŒ)#ÜX.€ä\r€V`Ø\r Æ\r`@ƒ©¢\0ì Ø£Œmèv\r ÌnIÐ&àŒ›G§Ô\0Ä5ç\n ¨ÀZ\0@sàÇ#Ð¹Ñ¶ðLrWqV3¤êªd¬)qÈA+\$£‚JEâ<Më\$XÅk,HíàA\0›1öWåFG¦Š@ÂiC0U!n\0DÜÅ„\nwØ«áH*RŒ (Â^'NFÊJ.âæ	€Þ£Î=2¤š\0Ú~#^8c—åÄ1\$ø[Æ	Ð×mÝè¬Žm¾EŠ-ŠÝRÖ±%ŽÞ‹dÞ.­¯Ñ-çÌ\n…ô7ƒV5£_ §?àà‰È¶§òñKbÖúŽS\nÅª]M`—Òé1„”ë-ðeØ]Î²<%ÚYJÏ3©‹Ž4òÄ`ÍÆtHååMo\"¤ì\nÀÂ`ê Úµn æ¼¦„:\"t£¨À¬Ü¯&TZ’ØdÂØ&©é&<åÉ‡)ÂZ¨Ó7²å0¼ÈéF©Ó,HG¦@l6G 	\0t	 š@¦\n`";break;case"bn":$g="àS)\nt]\0_ˆ 	XD)L¨„@Ð4l5€ÁBQpÌÌ 9‚ \n¸ú\0‡€,¡ÈhªSEÀ0èb™a%‡. ÑH¶\0¬‡.bÓÅ2n‡‡DÒe*’D¦M¨ŠÉ,OJÃ°„v§˜©”Ñ…\$:IK“Êg5U4¡Lœ	Nd!u>Ï&¶ËÔöå„Òa\\­@'Jx¬ÉS¤Ñí4ÐP²D§±©êêzê¦.SÉõE<ùOS«éékbÊOÌafêhb\0§Bïðør¦ª)—öªå²QŒÁWð²ëE‹{K§ÔPP~Í9\\§ël*‹_W	ãÞ7ôâÉ¼ê 4NÆQ¸Þ 8'cI°Êg2œÄO9Ôàd0<‡CA§ä:#Üº¸%3–©5Š!n€nJµmk”Åü©,qŸÁî«@á­‹œ(n+LÝ9ˆx£¡ÎkŠIB›Ä4Ã< ŒÀ šâ5mÊnÂ6\0êÀîjÀ€9èzžÐ ª,X‘¶í2À§§Î,(_)ìã7*¬è¶n¢\rÁ%3l¥ÃM”ˆ¨ \r²öã¢m¢ä‡KÑKp€LKÂúÙC	‹€S.ëIL•G3ÔW9ÊS·2bÙ!¯«|–Æð;I7ÅÒäŠë#´Û=ÀÐõMó“TŒRí/Ô\rÒž®­ÓY'ERj!*§¹ôâØƒÅ5eO¯;w4ÓÓ…‚Á°³’ÜWFóò‰,ÏÊ}!ITdÿX/‚Z¶*5¹O5ÚSyB§”+eÉQ„âŸ’ô1QT0¥*«qÈÈuáy)èM{SŒMƒ!°­Êð‹¶”†E©÷‰LPGŽ5ÒEòÂ0DÔÓ{ˆ¼DJQ}áj}X4E•Ûî.:’Ör*½„Ô–<|T–f\\@£c\$ñW“àHKdŽÔã´9s–àjšÙ„^r£‹Î³6NèÒ{n¼ñý`ØÄ€Sk£wE+Úý%æµþ¶V–°¼+¸dÝU”Ö…7µkÁqT	Û‘¡Ñ”¬ ‰DÍäÂˆÑnzÝEn@Œ:ƒcç\0½É\0Æ0Ñˆ¢&³rc|WÖÉzdœ„ÆÁ|UµÜ*ˆ«Øe6Â—ïöT!ÖBšùMt¸·\\÷vã1TìõM®ë]nI‚Sú’k¸3zkåÄŒ1OÃÃ>˜]RØÎ-Ë‡ªÂúûõ’ÔñÉê1+|­¾÷CXÂÃèMJ|ÑÁY_·³Y·7+“'¶âòizŽýñWÈ“Kén¬°ã¬wðÁ‘*ó\rÐ9.ò\0Ã‰`¦R/ä*óÎ\\àyÔ70êÃña™Ê\0ØÃ:4`°ù‡(TC8aF€‚!³ð@Q u?@ 9‚”)ŠÛÃ{‚œ0¦‚2–m´·šYŸù%m¼ÒðÄxàPkìSBFá›7©½©7vF£NÚ¨-ÍES!…êÄJ‹ü:ê)=˜BnH›¥!ŠÁÇ¶ØmÖÙ -Î©•é[…¹AW\n0£¸x@\$:“_P}yHV_ŠpM!Ìý#Ñ*Ã˜w\ráÉPðHr¡\0xOºÀô€è€:à¼;ÌÐ\\C\$H\rÁ”9éfÁxe\rÓd<¨ZC|ÛN\\9\0é1ús¬à’Cî\r³h:À^Añq>SÌþöt}\"8a\rg4‡CÑ5!DÓ\rÁÒ'&\"þÊ!QŽå):·v“#h:@\$úþ²Á,©ÖQ#oE\0(.@¦3#Ò¹j÷ŽxÅ›Lg’‹&chq<'ðºTQ+©DÐD±é„}å™ÒBlu`¹lv‹¬ U4âÞÌQ_ÕP¢±])ŠD¤“†*/\n„@ÒŠño	ñoŠwv›ÍÉYNë°×±#Tå2Q•Ž´öw0¼@'…0¨CžX*µ–ÀX¤ÙUá}„S³ÚÕ#®i¤¥uŽÃ—,Žf*C#Ý…u25Ü†½ô®wNû›\rÐ®x†ù &\0b\r!œ7>1í<‡²``©Gœã:\r3ÂYÐjpo\r¤‚j9ª]œD]lî@%bÃ‘Ð¬ÌFœEÒ£ùO¨V\"¥¬ÓwUü¢äÔU…b·HÅ>¾Gfú43Š[…QOpÑ„S8¥9z™KQ|+³¥ešCšíâ‚h7¯äò)œcºl¥…&´ØDÊT¡kMô…¸Hü*ŽYh§o¢˜‹¶gÎ@1%üÂ†)Ž!QZHU‹2öMboñxå¾5Sl€šŸ·I¥E¢§Ì!_cÄ`QˆÖÌ0Š¼\$Êä,”À­-pilEé¾«Õìx9%â2ç¸àNeJõµºHÓC—òÅ+S;‚k“¢|y*©=XæôÅ–É…*'P¹©õ½£Õ9þReJ0¢•oÆM/êKÊ9>YYcã¥ÙXT<¤wå>AÔªÖ£²dÕ1lÛà§\0[ ¸Ð»A0Òƒ(\nwH8D­‚Ã)îgè25f_–²‘n»ØÜÎE<DúÆPiecˆ˜£5Ê‡ÖYRþ§\ryŸqSDÐ\"™£½T‹q^»hÏ”±Ç@ˆ§Æ\n ”ÞÌÒú7eáïz”†.Òn'+\r­‡¤â–H­kÐb‡\0Ý,ü¢²]´aÛ*òqŽW°+åÜ,t®ô¥­¯Ü¢^øÛ¾/ñ˜J<»p•Ô¾¸5fPMk¥o-¡Œô]å»«sîÛV·0È‡[<<ßÉ VfßÎBr%ËŠà6¨§ó—ÇŒŠÎN®;ìù ,®ÄÆZ«¢Ô—«ïNñÙ„vË%Hv]LëZ×ZŒ’¤I§H[²ÖE=©ÒswMÛ©ÙÆî=¯ÀwBãÝ•Éºï-Ó½³aß£¿‘‡z»&a™zc~©4:Ñ‘‡ñiÈ•ó/&2ß:	ÕTì:¸5÷@ä§^76b=Ê±—¾Éo\\û¡ê}¯Ãu~‰¼Ð/´¦ù³Tk¿rQ#¯Œ kOÇxûNhJ1öû¼L®Åÿë—”½š¬’º«¤aÈH[×|Õ6îïãÊ­¶\0[¢áO§°	®S\nþ¨ÀüO¸.OÂûfLaôb¦\\Uf^çnòÚOnþâ¾ÕbÄ`¼&^2jˆ-ˆ5NšÕîfµ¦\$-Æ(/./%„Xäúßú\$o²YBjdÏœó¯cÐ^Ü&fMcrmLÐÉížöã5Þ÷QÊm\r@f¢‰©@e+ÎV¯ÜÊ­ØúN¼Ppfw	l çÍØIénA¥¦¬ŠlŽíÂdÄ¡¬\\ÕÐ…p¨þ-¾×Þñ\n[¢óçÐæïŽ¸€ÎžÅPò­Žÿ\rŒ¶âPèd«ö[ÊèDÞI®x¾­¿ÅŽ\nŒ§Ô*Ëì’Q\$ÿÑ(¼\$‡ðçq@V‘1†êjÃŠç¢¾í-ÌÝäŒøìœè‡ŒÞÐð{ð‡0ÌU`ÅO„vƒVwÐbWà@d€èü KàRiºÁmÔä™Ä¢Ú¨Êg³ªÈÌ»Lˆ[FÍ\"¦Œ&ô-á±·ƒÇÌ{QÎ&·/3l¸ÍolÍ¯q†VýïA÷ìŽxL‡ñ\0\"Û'³\rl£ðÞ“NDQp‹æÔ‚G˜ÍÇp§\"2ëì^yGÕÎáÑÑ\"T÷±.s¢1&Mðõ%êRÏ%®ñÈâhÊXÎ†¶ò­Â©1´þ/CBX2]#R‚ñÈŽÃ#2qs'nïL’¤Rc\"r<àå(ÌŽ¡!‘­\rÇq Q+\"Ï+o>êfíñm\$Ž£1ÕF,ÎR0{²‘Pøz®XMápTcrRiÁL­P ¾Š’EF&­Q g²xL\0!ÎB&®³\"È“ì‘s6èásP§\rª!2ã±â‡.LMþ!ÒÌP®Žm&KØ½4ÃîÞmJB©E2)d‰ðÉ¦ÒÊ¤ë¶3N(µ(	MZHPµ\$ÒÜ†ëÎ8ÃŒ\$Ûñ\$RÖ{2a‘ë//–X&0+ñ««2ñó°„P?2ï-ñí<-»=¯dµÎ\"-Îƒ5Ó‘<‘Ú2Ã*ñ\r+3ð¤î[;O=-“»3ó¿ôÒô.¬á=ô@GÝAÅS@Ðk;Ž é/—BÌB ÎN\"‘\$¢HfpÝCŠ²j¶¾®'¦Êÿ«E0—\"o#Bíå¶á„„)(DÑ>f½<Ñ±-Ô&Pt4I\0ó’Õ@ô5Tg(®y>’í\$³½#¥5†¡Ih&ŽRGIè²cV*%ŽxF´sI®;0_K‘KJÔ#>ÔXÎ¢1M\n\\©ôA4ÔDç+ÜW(é0%À+ÆÂ„¢HÅ?’,\rS29,j‚Žuýs§Dk-°áQò±R5uŠ½Cû\nTLROSjl¬æ_Q+t›C1ýCu\n¨u=Q1á#4é°PQ©?²?ì­T´Ps%´åUF“uM8¯·WL@#œÄòÎP5•Pu‹O•`âN0ÕDýWu5}ATµõ(ã4o-4Ûõ1J2á[Û34o ô¯]4³RÄâuÉ[qi”ÝVõØyÕÝ_k^4úèŽ]A±€„d›`‡\rµ½Q²ÅY6mñO\\ÐïAç]SâZî—`áNuå\\è)æîø¤\n\\ô§H,<­PdÌfN4üXã<»îÁ)’xAì*‹3Ò¢¯îx ?vC)0Œ÷öm*tìQ’û)ñZO’¤æ¶Aö‡=2²¿:¢ª4¾,\$Öo&~UÒj4/N’—4V}lu#ð™i{*®û^r&ØH8\r€V`Ø\r Æ\r`@œ+p\0ì Ø£ö„éü\r Ì…J\n. Œ¸HŽ©X\0Ä ‹f\n ¨ÀZ\0@›@Çpäj.¶ÏÒl:öw'6œ!Åžål	3v3nU‚–mF!¶™KòuuüÑ’MT%\\0úòÏ¸‚¬ƒûZ#W2uG‹è	·q/«LðåY\\æ<BNÉñVy³šÀ…ýÔõT÷Q¥Ž%tvL3gDR²3\nëh&Ï–²iAL’ö€@˜¶ÀÈF„m+n\r©f<ƒð@žD\rÎÎàó¤Á·\r\"(Ã‹(£µw²Q°ë#îCtXE·Xõ5uø,d¤.8“GƒÕJQÕ*¹+öÊ–5€¨sâ<Ä<—&›Vú\0è¹m~—hŸ\r/4æI0k>vy„(öçnÆÇâÜÂ‰%1”nÀwwc™C‚•	êJTe·‹ñ‹L0_¬a|5äò6Â\nÀÂ`ê ÚÆã‰Œ&ûfÉxz/I0A“™-üBlø’QæôBŸ‚ñ‚BNÆø6ìÕ'†~0Û6ÉsŸƒµ¯’™-’Õ¡“¤PI‘Œ¡UE¼jtl0q`v´ÈóÔ\\²”QT 	\0@š	 t\n`¦";break;case"ca":$g="E9j˜€æe3NCðP”\\33AD“iÀÞs9šLFÃ(€Âd5MÇC	È@e6Æ“¡àÊr‰†´Òdš`gƒI¶hp—›L§9¡’Q*–K¤Ì5LŒ œÈS,¦W-—ˆ\rÆù<òe4ž&\"ÀPÀb2£a¸àr\n1e€£yÈÒg4›Œ&ÀQ:¸h4ˆ\rC„à ’M†¡’Xa‰› ç+âûÀàÄ\\>RñÊLK&ó®ÂvŽÖÄ±ØÓ3ÐñÃ©ÂptŽ0Y\$lË1\"Pò ƒ„ådøé\$ŒSÓÞLà®\$ÓyÉò¨ü†ðËÎ)ínÔ+OoŸŠ§M|°õ)àN°S†,ê,}†ÏtÒD¢£¨â\n2\rÃ\$4ì’ 9ªŠ²’¬I¤4«ë\nb*\r#ƒæ)ã`NùŽ©(ÒË£(9ºƒ\nHã0K« !£îú†KÌD	(ðÈã+Ð2Ž‹³ &?ŠüPø«ïH¦—µÃ\"ëCøç®ÀP‡È#\n7,€…-#ªzp£EHÜ4ŒcJhÅ Ê2a–n|Ü4Î\rZ‚0Îøé9#ƒÓ¨±ŒP&¢òÈA(rê1ŽˆS!B1É[C¦rGôŒÑ5¦ŒKË´©@Ê¡9Á(ÈCËpÔÕEUÉsìþ½B2EYÅÎÏ3Lá+%ì(š1ØƒŽÃzR6\rƒxÆ	ã’ZLƒ¿iÏba†V¦ÖÌ¼Qµ:Œ”·( ÏÓ¤ã[YŒ@Âß Ì(ÝhZL @)Š\"c\"1²• è?OBöYã|L2S%1MRs`Å0C“\rRM%5„ê‹QÅì£ü7\$ãž6ô JU„Å‰Š\rk^„Bˆš*º¤€PŠ<\"Ã–j!ãÏÊõw1L†ƒâ0æ'’Ž¸àÏB’f6H.¢†1Á3ÍÈ\nƒz¸ŸK™ô§?MÃ3CëÃ\$üH#Ê<3Œ+ËU©°Üý¡@æÂãxÖï„¦)Ûxì¿ƒ \\k#–	\rÍrJ“ªË¨Û ®Î§ªÂs®¨92šºc%8ÞBö!,\n€9'EBCjzŒA’b™Í½„êÍ@ƒ€Ò9E[BëÅíóDÔ9³Î|Æb’Óp“Ðæ;®µ?sÝŒ¼0@&ƒC(3¡Ð:ƒ€t…ã¿Ì#û’^.£8^ïýð+¾ŒÁ!xEwMˆéî‹èÜðÁ>\"‡ÕQ§ xÃ>AdÕ …2lÉ¨aogºv¸žœù†+¨7§ü`ŒÉ„ƒHtÙ H8ìH(P	@ƒörŠ” D¡Ä(E“9¾sæQi¦’öAKËƒðNfžrÊ%Ž@­†MIÙ=h¦Š À@™—ä\$dÐ¨»3d˜ÍP	áL*dàÈ	ñ&Ší<E`¤õZIaPñ~•ÂóWòœ†ÎtöfNÕq+!.ñ_4h!›Oäú†ò>G•Aw^ëä¤À@ã\0F\n±<*rzÎWüKÁÈô&Ðäj	‚V>Ä3\"äòŠ¡-™^®¥ \0U\n …@‹-Á\0D¡0\"Ëä ‡L\"Ê¡µZàÚÄIKÍD¡4à†Ò(H„•¶… À\n\nÅìºŸB²Qƒ0eQŒ!+Ç”ªgI±ŽGl„ÔôÅ\$JjàÒ“FyO	¬'¦U2¦\$™„&ï )Äy”èÝŽd¡ÅúPÉŠ±JÎPfŒ™³=?ñ6?2–ˆÊéDi¬œÖ,äÜEç\"]è	›)tö‰|A`ê†J¡ØËò@éÔ§Â¨XTR³˜jëþXÓÊ‘åSEç¡æËI5Ã	ŠV¤=NÊ¨Ýäê7çž*yZC¡1›“­JêÃA©!²e«A†#CqF\n\rL±e6ä*Û¯(d°Ôˆ\nÛŸ0ÈOr©c>ÄªWXÕcòNJæxÙ@¯céIá7,ÀÂ0Å:vu	â{³y@|¥V®I\0 ¤~‹Ê3¦ ‘¢€ˆÄÙàecçå)£pŠç©'d	²@%6 dkL4•V*{¤Ó‘BW2F) IÔ~ˆüÉs•ŠŸ'£ñtY…Ó&µõ‹‡@\\nÕÎ»¤¸1ÞpfË¬|¼¤ŽóÝkÓv/]Œ‘yc'IùY»×Ô’J•[snâ¾—Úñ_¥ý{ðz	¾P—	’Jæð´fØvÆ’DŠÉH+È™pÉ‰ƒt…h¥\\¥ ;(‰D²öeäZH˜dÒjOÈ<“¡)²	&á”1b;¿wPv+\\ê5Þ\\£)y&Å\$¾RbÅh	¡['E6À{‘fÀPHi£Y\$*ËDh E9„Àãfžèiš¢¢%¨‚9…â4HQ‹þ¯üý¯v:ÆH—’MÆsúz‹º(ƒVj\$›õ¡Ø¿]§jÉ‹£:n,¢¦Q¦IŒÎhºcP™\0ÚÃ¦B6SI‚B„ÚðÐ\n\n—ZëU2\r^¼xJþq¬ÜÑžd^¯ÀY–ÁW¨‹6h'Ù¨¹×Rh\nhn\\Ô2”I¥3Ù=Ï´v“Ñx‹²\$SMKü£æ-W¹æÔfkRéúM¼hýÒ›+NêfG£¤LþŠ´^¸4¶}c,‰¤“íæaöc\\7M­ù{jcîÙn…ùB÷°r·êþ‚®½¸º›½À'cnÊ9ìä…BÿüÊtöçÓ{‡K˜K'+]ázáúsŒ\\ÎezõeJ¬ÙN‚òa²j•Òf\"ê]ÛáL„Všf@ë§9G'Æâ9É²~.dÊ™ˆÄõÓ48^\rmõêð¢Ä¾H+Û%ç~Pƒ×êMwn£ôúâ›‰±HÃu.{;¬jV›£œoå+Jú<Ür_ÌiN”›±\$‡w	ãùÅ/Â(\n¶óDùKÙÊ¸Ï¬é<GØâ&*Åý©Î¸7ÕÏyÚwíE–]è¾ðÔ)Ü{|–0úƒÇæùŠ6è~Wò>+Ä.ÕæO#Û×ôØØ±Œ¶ûªL£ÒÕ¬c—õ!ô”ðUvÏ\\¤üôçmòýµÝk{pâ;¡†Âúã6ö„ØÿÐ\0H|úo^ÓÆQy\0000&/ÆP!H|ýF>&/DÔùÍÄÝ…ÈC#æöo²‰ŽÍ«ß°Q‹²àcÐ@ÒàN´0´IRõÍ–CÐl³\"ëÍý¬w)¦1°qOá	4šåFP·iîÿnÑä@DPd¤J€Ó¬¸§*Í\$´\rª¶Ï¬@Žè\$öå’om(EE_åZüÂ˜AŽX¡Ë>ÊMoä¥ìÛ+ zF@Ö\"H/€ƒM÷ÐäzM\"\$kÞÙÑNhr\rˆ Œ6LRd‚\r€V²DÐ’RÍŒµNˆSgˆY¢bÈi,rbU:mÀª\n€Œ p¹F¨1Í´èi>föæ¦š40îïãŒffu®L¿äN&(\"â2^VGbqíX®On+l%	j×'ª1ãÔ?cúã+J1kPJ1¶ÆGO£\n\$ÀÂcq[P~×CZ‚J2‡x&I¦	‘î½eì6ã!„XñŽ¾DTHððd#8ø-6E<ßQ Ç#ê´áNßÒÔ2 æ¨wR(7’,çQ„ÿŠ&hBêf6C5\\oˆ\\=‚l«§ðãd0/BF`†X%¡øîò¯bÅ‘ôÛBXÛVŽŠR[ò‚\\DÚAZ°c, š0%˜\r\"aTFiÆ¾©–›òX?¥Î/CøƒÞœB^ß±®šC  \"k\"/ƒjªàªPFc‰-’ÔlÙ#Däÿð#Äf	\\ïàÕåüC…\0O`	\0t	 š@¦\n`";break;case"cs":$g="O8Œ'c!Ô~\n‹†faÌN2œ\ræC2i6á¦Q¸Âh90Ô'Hi¼êb7œ…À¢i„ði6È†æ´A;Í†Y¢„@v2›\r&³yÎHs“JGQª8%9¥e:L¦:e2ËèÇZt¬@\nFC1 Ôl7APèÉ4TÚØªùÍ¾j\nb¯dWeH€èa1M†³Ì¬«šN€¢´eŠ¾Å^/Jà‚-{ÂJâpßlPÌDÜÒle2bçcèu:F¯ø×\rŽÈbÊ»ŒP€Ã77šàLDn¯[?j1F¤U5›/r(ß?y\$ßºâ¡±Š¡»”Í¦Ö´JòMxÃÉŠ‹(¨³So\0ë4šŽ‘Êu¾˜=\n Ü1µc(Ö*\nšª99*Ó^®¹ïÃXýƒ˜Öa¯£ ò8 QˆF&£˜Ø0B#Z:¾­ûˆ0¡Æ)02Ž ô1Œ P„4§£“L\ni©ŠRB8Ê7±€ä4Æ¢˜Ê=#Ãl:)*406Çƒ(ä P‹!	¨ P2ÄC|JÖ°lj(\"ÃHÐé#›z9Æ¢¤®0ºKèá4Íi¾ž.â´69¸è¢þC{ÜòMã¢–5µêX(\rãÐÚÒ\rÍê%5µ}#I´­ëfÁ\rcªÕºˆ“p5Ä(ÈCôÕUe]\rV]Zý.o`á@1b0ê7\rq  ŒãÊ3¹‘¬ýLP@PÖ2@ÉÐÒ;J¨°ÂÔ±s‚¶84dØ&&ˆ‰0mûö<•Èƒ`Ìã’æ1˜AN«óPIâˆ˜›²åmP=Xm‚4\$Àv4Š71c{ö;_¬[7¿…7J7´ÊPNu!IbŠ=á)Ä“ðèœÍ8ðÑG˜ùRñ»“ Å3HBÐÛ±Ø’6¢C“\"Ë‘dˆ»]{¶ V-—ãNTñC´þó\r”Vb2O3k#4)ª„ï„hÜ7Þ.B9®a5;¢ú¬öÓ\0õ±¿kò5&5oûX±Ã`Û:Æ´4ëšöÁ³¸#®ÈWì×æÓ6mi®Û·ðÛ‹c·kã¶íU{ÈÜØ0»íû­§ÜÂ‰l{.Ï<q…øVñû‚9Éîœ¶Ý»ó;ÐèÍ\"1èš»Öž¦)Áp@'Úúw¸ŒÉHÚÁODÏ)º¥~FÕÕ4É»ä14“Z4;8»)PªsÜ7ÞÔ‰J.ïšÕ†z’'	ÔøTü£ŸÏôéÈöˆÛkhMHŒï·àúƒ™ôznX•…t×Ê0 MdÓ6Ð˜?Ck¹Õ\"7Œ\rù ¡˜‚ Ð p@¼‡x\\–»šJÀ¸”†p^Ã˜/+'ì7?°^—°rèÂ…ðÄˆÖ\0\"ÌÜ8¶ƒÏØ<á„5Ô`DÊaPm¦\0<“wtFÒR\$ì•—’öûOz;èøLº²êÎáE5ëqa}ãª\0\"A‘œœgx¼\0P	@F˜Ö+H´p@»ˆšk_l‚g|ã7à0ÅÑB<†Ç‘ÄxŒ—™Âfü©;w‡Q?BtM^0ZiÐŽæ+Oª	Qî}Àâ€`Î P	áL*2Òÿ,X:{n,X«’†Ý,Ìì\"m|× ÐƒHg§F½“R±”bÓ‹²è+.ïBšú\$¤œ«ÒæcÑ#Ä€‘JàÚä±6ÁRBò`ÏŽ1\$l!èŽ'®•°\na¤=?¢Mek%&¬ž‡bDŽÚÀimÊqnË².CO\r(ì'&gÀ˜C¨pXT•¤¾\0àHEQJˆ*!´J¹AŒ'A‰…gä¼‹ð^%½…!Ð–‘	àtz4x4¨&Zw\0­£É_’bI<ÉJ¢kQ¯›âw…{SªÅ‘\0æh\rÔhé˜•¢cf™•d‹¤L£&jaOed˜Ì]ŒžÆFÄ5dš½´Šú’’`a>²Ù8u|¨ápG…ýjNPåA@sVfO‡9 ä*‘ò%ì•\n˜õÞ\0j%ÍÚ`žœ«W¢u¤ÐÑrî!„1…¡Ç\$xDŽ!5/æ,'Ø™EdžEu*V²¦\nÐûC›K05°°È­Á´§!LŒÒÊÀSZá±º8oQÅãC¡h2 ÕBšÒ„¼çÁ4Ôã›CIµ§ì\$pÏÃ•å¾§EMœMaÒw¤M€¦xÏŒcò[`(#<éëcçÁ¨›qtß†e~»xd&QÈçVæBkû%Ý-šÒ‚Bh=§f8—ðô¨•‹âZ­Z™f²øÃÎ=ÇA­\\·åüySÜi¬ä,†[m<“ÌpÒ©G0Û‹jMÎy¬äxôöÞìž¥Ö\0Ìe\nâ\"îT®ù^‘X”–ò2ÿÉ9‡&æGÇ™²“s£ŠP+Ú“Kœe2fU´UÿP;N@VØV’µc¡rösÉy‰ðãðÈù1îÐúI5ÅÜÇ“òÞ_«ý²Ël”÷³® ÃXT”bGÙ›Ù)±Gí`H¨µ#~Ì¥6PÝ%I<\rä¬-PT¬–TÖ–p\0ïXÕ#+#tG0àäSzPˆ²Ê)dÒ¼ñ–JPŠ1™\\lÂð=¨t\$’ŒJRAì8›¸:@ƒ±½aŽ©M*s”øs¤4^éïÖ /•Fg7ö¬×§J©)Rø¨p¢~n	ç»*ƒÉÓ]UËRœjÜñØ3š¹+ãk—‹4ìSHñš¤ËÚí!Ù0o )¤AIH†CHP¥pd'mO\nÜÐCù™è¡±Q‰§Qon½YŸ4ç³K^)¨p¤¬ì›º.ø ˆT®PSÒ)jc­í'‹r\níÊ{U|#œO†¦Ä9q°ÝÏA®_Ækk.®Ó”^\nÐ,ðL`ç;¥™È×näF—a²–NÊù3ßÅ\ra?žhÚ”Ï›“£Ì|pÉ \\Xò“”ØG²Œô9TÒæÌ³b³òFž«“ÿ[+ø±ÍÏ5çïo›ÛïºÃ^¯Âz_^~zô^SÛ1\0+É]¿é_±™Û0d¹L×ìÐÊ÷N'àð¡»òý»õúr—~óŸ±B^ÐÞ¶‰¾^mküÒŠT#‡ž\"l&!Z(ãö\rIVPEHw¤4Ú#ÅVŸ\"2¢Ázo\"ëãš}Ð~'æ;ˆFGðën¤+/ôÁƒÈÊÔL4~²ÞŠý¯Î¢¾Œâhã‹äcª˜©Ïü»å4YÔêÏèR€þ/\$ü~ºÏÀåÏæ%oÅE.ºÌBÄh\"îŽî\0ªÄHÆŽOYN0¨\$Žé	‹\0ð4ÍCz°´#-]	ë\rPÂÛk‚3£Ö¨í~¼\0–\"¡|ÄAz^4Ž\$¸çà<ª€Bl0÷i¶÷¯ÂünàõùŽúýn\$ùOxå¯7®òKæ<X›%:~‘(›eE\rÃ{¥‚9ÑMOSŽÐÃßbü¦Ä”\rb€@nøƒ0®ðÇËÂxF‘,ïîó±o	Ë©\rŽ@Ñ‹Q.ÅDáÄ9Î²üvþ‘ªþ0È´‘“p¯Æ‚SÊlNLJÑ¬üNC	È¿ñÌþ/ÖåKüÀ—î`Ø‘½\$­5ÑðK\$>E§m\\\n¤\$ÅÉ=ÑÐíãJ0Ò\nB‚úoé Œ[!Ñµ‚xô€Ð\"¡CãÏ–D… õÃ\"KV-£Ì“‰<±§€Ñï”l·%»%GÐ¸éÆUkL;%5&ƒG%†¾ÊòrpâV	b2r£ÌV%èwä¨:j£#%Ì;E¦f¯ªÍÒb®R¦(CDR’nË¬±Rv“¯#*’ºú,)Üû\nQ@Øc¢(eºn-šTÐd(†ÚQ„¬Ž¥àF†ãDÙp~_§ÀBáfŽð~\0ª\n€Œ p%r¤ŸâR6-r}2Ó+\0&N¶g*ï¾ÏòÅ+\"33ª2ìúUsE-i\"b*\"óJ€çúWnh±5@¬BþÆ@¥-ð2 –#\n/Ä0rj†šˆº\"LX?&ãÄ7Ãvë&Ö*r\"DD5\nHÖëj'ÄàÕéè\$jžêöEè~B~ÂK¬@Ë°·(ºÊM<ƒbÚ-ò¼åSØê‹:Wç9>Žk>ÆÞ3âj>S.ëë=Ñ3ç=s÷@ƒ>Þ£Æ(@õ4ô_õ\n#P§ òY…GÀSé€0üa4çFçIÊ(Bˆ‚%›D«z‚.¸r\nÂt}°Ýâð1EàòÞbŒ2\"AŽAñ\"<Í€<À´@E'Jf#ß&>0-æ®‘ æªª½1BšGê­f’ªtª«£Hóü´\0<Ñ‰äÎõãe©VEb×à";break;case"de":$g="S4›Œ‚”@s4˜ÍS€~\n‹†fh8(o…&C)¸@v7Çˆ†¡”Ò 3MÃ9”ç0ËMÂàQ4Âx4›L&Á24u1ID9)¤Îra­Žg81¤æt	Nd)¥M=œSÍ0Êºh:M\0¡€Äd3\rFÃqÀäl2ÃDó•;äÆè1PÂb2›.0S\r	†¢ÐÑÔÌÃ^L¯7¸5[Y7Dƒ	Ún7ˆS±¦á-9ˆš©ÀÉ\$ƒ\ríUþá4)œ\$Ð¬H+s»…œ£ÇX€ï&’Ãp–\0Ó%Åó°>ûu_Äˆ83s\rI\n§ÇsxÌvC\$E7%<(ïXäaÞˆQÓ©Ónê¹ô,¤z8†ªÎxòÝ#Ê@À\rÏ¨ôŽª­‚N2«#¢¶9*	xÐ¦œû!j83 0š„*@oh´0¥ojˆ:¡\0Þÿ„ÓFNÀÜ5Œ££ ù .ðä	ÑÈôãŽCX#Œ£xÛ®£(&)ÑÛ,11º<¼#k|†3Ñü5.B€Â¥Žã|(Jr¬Œ&\rã:LS\$Í\nºíã…46ðÂ:Žc»½º# ä:c ê†Ê°ZŒöŠÊØî;ÌDî¿0\rH¿ìT‚:¡Šò9¤ƒÒ ˆCÊJ„µ%L—O€AE&HúBÎ%\n4¤/h‚£Œ£:I\\/ŽˆêÞ1Tª¶1(Ë˜ä2X RþðÑhå^ŽHëîêŠ€è6R)h ÖõjÁ6£àƒŒr«|(‰h£NCÍ¾úáh“Ú²[Óg\rÕ„UU¨ì©+S\rè¦Þ'TIISC-8ÔàèZu€Ø×¸˜¤2¡I(\$£„ç	½¢(ñ‘LôÚú¿°7:ðÒÌTÓ·Ø@Â_ÌPÙA[ã–6#ŒP¨7´)ÐÜ<»CpçaŒipæ3©àÙ6 #˜XÕ^!b0 !»T„júŒ¡@æ¦‚¦)Á‡8oÃ!¨Áp@ýGH0È­„4Ì6ê.c_W18r¡g¡HNàš9ó28ƒ:z9ÃzYj«íÃŸk¼Ck ¥¼C\rnÒ«ˆ:÷Í>Üˆæ×ÞÐÄ–§éHÝ«ÙÏ&¾>ºÐÝŸè)¢lð¡£„Ç>ÌÈ 8\r6¨É»ƒ7jŒÁèD°ƒ€t…ã¿´#{ø9Ó0Î£¿ ðè!	^6£’Z:zBýÍ5Aö@8.Ðèã|TMJK6H¼Õ4WDSJºMŽ–&@H0EGçÇ\"xš¨t'a±@òøÄ9%<Èe\\@P°J\n˜.GAR-x§« ägA„\n­È^7BZ»qá•{ç+Õú¹0¾¢pN‰á>OeƒDôŠ	â0Ñ†¥ /\0rBÅÕ¼“6î~È2! ¤³xS\n€’<ã\n8o)mÙ¹\0ôÉñ„\rð#”‚”Sr0K¥¤º„AãA]Ò fºŒY!DCÄ“.fAé8oà7‘¶´Ê1]„ ÓcJó‚0T\nè“–PTy(I)Ó\$ºM2ùˆYnÇ@Ë–#HIY„ðœ¨P*Rç3Â E	jbvtpaIiq7¢\\ï€L¼\"@à“ÖêN	áÂf,Ð¬ø™bD(D¤§šsÛ&Ckú9†-~žS\"{Bqð\r¹NBÒQÙè=N™'–,ÒC;sBA,x ië=4u&¦´Ÿð¤P	‡Z,š“„»Ae.Ž`i6Ç´)Á¥é»/X\0@®ØŠ„(…P°äAcñ\0ùøc1\rÃ†ÀçQJ’ßP¡Ô”é\0‚Ê“Óµ–3|P]`IAÁ²%²àÉ(dTJ‘SÆl\nóiºÓuY©p›Ìí'/v÷LØù­‚§p«‘F…ndf­&Äså0ž:\ncè;B.¡œ–£ð©hí)ã³ç™¯8ŽH JÑ“Kz¿‰hb+Õ¿D€éLCzrTÅA\"LíÙLb”±Ö¥ô8âÚ)‹§ùN…5Ø\\hf=ëy°e@}•A»Çy3Û´LH2{d¶Ð„(ÕîêŸT(&ðÌìp	»7nô§ËØd/pt¾‰OÞD}ö¿xPŒ\r	 †éUÞ®µÚ»ÈcH!ŽUËœ\nOzÃ-íB—å°]l.¼\nÙ©ÃnY^ü?Åæq‰ó/òŠ×™#!‡µûã«ˆ¬‰t.¼—©¾ŽI£  ‰p¹I@ÄS%ò@5C)Þ¬jb™'º%ÁDã9aÒ‡o¤«.;¦rbÊzf^TH¸c³T³\n²÷zù»	Çi#JÎ¡–ÆÀìè}5ž1¸À3Àå(ËH5!1:²®r[ŸI¦‡Î,¼’˜(BR\\ SºJÁó°¡Yø‚	Àæ!)ë ÒZ:™`P‰Ë¦ˆn«9ë\0ID)alU3ñ…Ÿøa€-Ö¡€™<ALäœlÌ5“ÓÓH³õ/JÈ±%ZFÅ(¾”e»FRm¯Já4eJ{n³F\"Æ^pwPäs]­Â•ž.dçÜì'micq]öú’ÁÇ(7e2h48›â{ÊŽ}ÌÀ`›îƒ\0NàR¨&p\\E€¸Nä#¡Ïn.øy„˜,fa‘›6`vu8P;‘†ÞJJù9<ß:ú`YžM1¬ã“fìÜ<,Á¦MvDü“·Ð”œî{œ\"@‹C9pFÜhô 7L@Z¿KOô©Ù\$¬§CÈäx½‚\r€\nÂQÕ½g¡›£ù\\<FY°ò~È„d5ä–k¦ÌŽÀ``ÕIÖYLìB\r ¨1’Q¡•Gó=Ã_¼g)ÚÓÅ¨ýëÏ7ˆ ò¼ëJ˜:ææ.A´Ñ\\¬š\\bnõÞó÷Ôyxé½	©±·ÐzØÅçiäLç Ž\0 ”}í	¡èÃ~4þä\\ž_GaB™|Åèö¿Ì¸?8†øìGp.Ïöûn`+iëœ¼/Òû\$7Ùù•jç¯™ü_Rvõb8jB‘a­_	ô«˜e*q~îÜî,\rÄ¥¬–ÿe¬ó¸Kpÿ¯¬Ïó ôüzÜpÿ…DÏüÝð\0ý á\0ÍíP Rn¬òãÚ´âŒµ/ ÐËPS°Ò0R!¯]JS¯Ë‹HS¯´\$F§ªûÏVôâœZd6Ap\\ÙíC#è¹ïRÜ®¹Ð…0	Æ!jä\nCêÔh\$'ê\nÂYr=¦Àýb–×lÒ	èÄ‚	5Wˆ	f¦Y¦Àj£PœK~ˆ/•Â\$(\r\$p'Ž>ÁD¬\r€V\rh8þe^E†î£˜9Å\\ýé&î@æ˜	Zl\$ø*\0kÂ<\n ¨ÀZ\rœd^:‚h¾.>Ìp¶ÍÂ¼.¨ãHCƒŒÝh0Ç0öñïŠè°ÖÜÑ¢%\"V5`2@›`Ì^F6™mZ1OË	ƒÄI\$¶mš1¤Zø\rfÍÉ%ÉÎÖÂKd¶‚¢ÞHF<M‚6:„ž”¢\r¤ÌoHêHâ]‚·+v°\r#Â8¨Í¤(8ÇŠ<@PPCˆ]\"Jmäf\">eâ\räÒ1x#ÍÔYcÄ\r`ÞyÓƒÅ!©%à@\0È—`á E(.R3ƒ<¾ƒÅ\"\$\$lK…!On\"òª€\ne†!q5rV& Þ5d~2Éíp#€ñ'g@³LS£æsb‘\\¼ªp`êE‚æž‚r«è‚'d°«ï˜2\rþcg„I«ˆ®cÓùÀÝ \0ÊÙ%î–ŒvêŠ.K¤,Xå Ãfª“2%+°&ZÄh9ð]@´ÌDª%«r#£ÅB,	\0t	 š@¦\n`";break;case"es":$g="E9jÌÊg:œãðP”\\33AADãx€Ês\rç3IˆØeM±£‘ÐÂrIÌfƒIØÞ.&Ó\rc6ÀÏ(©’A*–K¢Ñ)Ì…0 œ¥rØ©º*eÀL³\0(`1ÆƒQ°Üp9&ã;\ruNÎF“=ŒÂl‰Óê'C)¸A&Nsi¼Èi3LrpQÎrƒá\"‘kÔAˆ¶ÀaW°QdÞu'i:3k;cæx½Þ*u87K¼²1xÌçY>¨ä\nÙídâÈ€Æo7,{IA–ÿ&7\rà¢žnÆgÜq6Ñi	º\r%Ý›Î QÙ\"·mÉ7ó|ÐU9á\n¡›7ì:Á„Sq„A>/XË§XÒà4ª*¥((¨òû*J¤˜\nƒJ4Œ'Ì\nÄå#/`Ê6>c›ˆÿ¢k0Ú2²`PŒ2¥o³z4-C!Œ)óêO8)8ÍÀÊòŒ®Ù†VÅBd“`Rú:=ÀÄ½\$ÃU\"H\"œ@Ð¹M»Ñ\r¹b€íÉ	´T!I¼’Õ£`P˜7­£s¢Ý%\rû‚9K%ŒT+AM£¾=7sSú4¹R7,8¬ê1ŽˆLT¾¯ì”4(\r¿0Hâ:¤»·?Ð3b'H(Q£\"í¹a(ÈCÊèÓÕD‹ÏL“#>ƒþ7U3 0ÕÐƒ+9,Òa–s3”ä¤4T'ŽQsŸcê¿®«¤Äð“€¶Ð4ûðº#\n1ÕÕäÍV\rÃa`°s4¬š9(¨¦(‰€P’7lD>à¢£ë\"'%„ßCl÷66ê™=P¬w.>RTÁ Ð”m¡Ñã+Ì!±\rÚ/ˆ¼È0ûHÒî&Bƒ@Ñ ïü‚\$¤kRLÛˆ£ÂIhâƒ+vØER>/äÕ ó2üB6;vÎËÚ\" Þœ#szúCVâ.9ŒÃ¬06L‹æ.ã“z0Œã\nÆê„(Úá®@æÁƒxÖÇb˜¤#:£²ï¹Ap@+R`ìûAsBÝê†/C2Ô6êI;þ˜9»¤Éƒ\n`\"£I7lÐÄÑ\r)3TŒ?HÂŽVÜb/]×¬`à4ÍV°’=¯zPç±”4ß?Lä¼\rÔÂ_!/K¢P8/C˜îµSê‡VÄ›°xû\r@Ì„C@è:Ð^Žþè]ìu€ä-C8^ºüöÊëÒ\rÁxD»¤í éêëÈÜ5„AöP8#÷˜èxaÍ„Œ!ðèŠúDÑµ’èFQèY¤ÀÅ™£zTëì\"g<ú1Â@èOãëUæ”(€ AC{/E§ìPRPÈ/áÌ\"<T×r.8JZ*öâÜÓÃ’Em\$á¨N‰7ÄÍÁ\0äØâ)1‹(¿œ¢öY(=Ä¸”/güH!	áL*sW‰¡E¤TÃ´’|Ý‘ d¡èÁ‘eÄLÉ©7Ô:ÅóòÇb\$8MíÝ¦£BbÝu1„Ë…Ê|ßàoE¤¥PÀ@ºÂaíd€¿‚\0Œ!<n\$,¤’ÀãÚBƒiIè€˜7ri\\0r jÀ½\0£~~¤Rf	á8P T *]‚\0ˆB`E˜EÄ6†#!Cxl¨¼ŠLÙž~' pî Ò4´‹#¡’bj¬V-å¨Ò‡˜Ž`‰nŠÈ­rÕ‘‰6åôÎEÐäZÒ:zŸ¤¥#†£–wõ6ì!\$³ˆ¤XÛ¡qÄÂ†!\nF½	…fQ'õ¸«Ø;¢ë¸²;S:¦³6¡±™#åîçpoYAÍ0Î¢ÖaºA xå…d@GôPJ%†ä*h´!?ÕÂrŠÐ™’7ëú¡Ôôhª‡ž’áÔ&bd^RhiA–z5ïY(ì‚F´!ð˜FtÄµˆ\rÔ'jùú«sM.òÿLØ#7eŽ¿ª…ˆ[¨\nr„±œ¶\r¼ùk©¦WžÒÕ,Ù‘1-aœÒ\$ƒd™›³§t7K‚@ ‹IYµ‘–Â£nãb\n\"pé¨¨xja¦qz\$ˆÂÑ[%	N{Ùþ…‹²jCM*Vzdƒz¤S÷=N+srÉ1\rH‘{Â¶±s™-Ð%!šAF°\\UÅØAl1Ý×\\Ønáº·ŽòÅh(E`±¾<±¸Š¼DÙv®ã+&\n”ƒê«°s(`2ÞËÜ}üÿ¼±~óàKV¼IûÁX39ã÷„LB€&v¬˜\\:aÛ*7l—.éHI¥Ë»Å‘Id9£¾wéã“\"çàë¥ÔaP!Lwxõ<Àîå`!ÝÄä ¤”¼[6X¹%èž©>×ù»¦‡ê?\"í@PHGå¨<×X{[Ž;3/É…M3âžwëmtÍ·‰0Sœ¯‘\n(§Tëã’cNÔv£F©híl„Ïåäß1]5fu+Ð,Ñ~¬”Ý xz\\“é•m34‹»Ó\néoff©˜+ñ‚4º\"_bbƒ²¨ GjåeW°Ú}A\0(j“…j‚@b…\"¥‘JŒç…CÚKŠF §MhWE6Ê#Æ¨ì}¢àõcÑLÆ³}‡N°S;AÂy;úÞM4mQÜÛ†¸@µ«]øLË†ôµ+³ƒp\rÞ¾?<¯[Éˆ›­èºûÚõï›ßxÙ“¾œ\0ÊÛ+¸Ø³Ê!Jã6¤_Pr×g;ñ^ÆuÆÔÙé‹ÍùRKUˆ.Æõd#<Oœ±v:î ¥ü]õƒXæaºÃæ]µ…ÊGnòY¥­ÏÙ>C¶Æ›Èí¿8IzHƒá”)ÎÌg=|h¯¼\$_^bWL-=ƒY>¯×GEõÚìñÎGÛ7ƒˆ]Ãtò=¸¢ú*·vçAgÍo Wn®ízwZéÙ„[]Üx£–»ÕÑËÅŽ!š£\n†*›üG&îwtžz­±Æ·‰ŸZèÈ©—¢l}óÁ;ÿQmý_…ä^wöY<«jí²Ô—ÿcâîí÷—ÆS`mÎ¯#Ní½êwvd©ËI×·ª÷Œé¾¦Â:!}5!ö·vvä~#-]Ï«pY‘( ßgWaßw*ýþÍßþBPp¦ÿŒÿ,*Z\$@þLwïú-‹:ü-\0OüM-þ¯jað³„@0®Î²`AëFA¦\$›ï<ä4žð\0¼Ð8JÞ`¸‚Ö\"¯è&\0/@Ð@¤^âÜž…à8Ð\"oD6åJøÂ.Ê¤îà0rô*h¢Ðzá®¸8T£H‚L.(?°\"T¤v.‰R-þÇ+¤Æ\$4°¨¾°ˆc¶\r€Vc¾hPÔ\$.“‰ÖxbNAÒF¨&&#è>(rÆÀ\n ¨ÀZ¤7âþ,Œ¾.¢”hUÑÍÂ0\$Qƒ¢6#°ä Ê<œç\0>Í(±ìÊFŠ¸ÓîÜJC…e\"Á‹JVŠW¢3\rmø-êÄp¤0išdÂ`\nL¤T,Æ€'…¤E£Ÿ%>_É®Œàv®ªCÊóE´ì0olsírReà5`Êm˜\$ü4\$@8þ5í2‘¿GV£m\n¬>sñÂ#â03GtrÃ…#„ljÆy­„zQ\$€Fâaí¤ÊGà¬ôHƒnLmxœm’¸\0Z’?‚ÕCº†ˆz\"£¾Ò0\\ß©ÒV€ê©Ë±V²#êNmé¢HãnF‹ü0‰ÔÁ° cì;Kþ¥Rd	ù\$­ë^üÏ´³ãH9\$T#2E\$¼xMŒÌ @š	 t\n`¦";break;case"et":$g="K0œÄóa”È 5šMÆC)°~\n‹†faÌF0šM†‘\ry9›&!¤Û\n2ˆIIÙ†µ“cf±p(ša5œæ3#t¤ÍœÎ§S‘Ö%9¦±ˆÔpË‚šN‡S\$ÔX\nFC1 Ôl7AGHñ Ò\n7œ&xTŒØ\n*LPÚ|ž ¨Ôê³jÂ\n)šNfS™Òÿ9àÍf\\U}:¤“RÉ¼ê 4NÒ“q¾Uj;FŒ¦| €éž:œ/ÇIIÒÍÃ ³RœË7…Ãí°˜a¨Ã½a©˜±¶†t“áp¨QŸ–lÛï7×ŒüÕÁ9äóÐQ.SÃwL°Þìëá(LŽ¦èG›ye:^#&X_v ¤RèÓ©‹~2§,X2­Cj€(L3|²ˆðÄ4Œ€Pœ:£Ô  Îê†88#(ìÞ·ãZ‘-á\0000°€!-£ä\nÉxä5„Bz:ëHÖB8Ê7¯èµ/âd(\\‚ÿ )0Þ7´ñx§3q|óŒ-ðÜ“,ïHå'­òHÉ%¤h°˜7­ˆ«ÁBS‚Þ;h<‚†¡‘‚FÞ1“ë	8*“~Â¨£Z¦¢,âjúß²I Êø…°’\"Šñåª7íŠŽP­¡­@TŒ9Ä#Hä5¨‚ÿ*@HKS£#¢Îï2H»×A'R|·ÈÊ“·R‰ã¢t2CE•%ŒÓÓ¬[2ž²C`è\nMD¿Š‘E\\•\r#XÖ£Dí ínÃ¨Ø64Ë’\nŠŒlc\0(‰h ì9 P‚óÈ»g\"´ãéCtúÞBÃ\n0@U@è7mú~¦Ëý&¿ÌÉ&¯”¢+!ÓT;3³ÔÍ6¢^RPË&'H¬D2 Q†J­x\"\$©Xä’B*s™f˜à@¡¶“É‰ds+Œcñ}·XÙÑ|Ï4¥Å Øß.{ŠÆ9ØÜ¡c9Œ×ñ4!C˜XÓZ¨Â3£/büNHSÎ2…˜RÚ\rðxÚ0ªa\0†)ŠB6Æ8=ObW°Í @;-#nv:·cJkéÍðÙ°Whéj7'¬*ŒÚkcMm%C+4’ h.î¼?»Â©Î1<ðÞöuø‹Fõª›ÿ¯­œ2‡ÆÍLHÊš¦ì,• #œs™„(ð8TH0\\øÐ9£0z\r è8Ax^;ürI]ŽArÒ3…ï§Ò<5š¿<7áÊìÑ~¸¾Ù1ƒXDf‡L} <á„‚Mpo æ©¢ @Trnäô7BjJO/GÈ4Ÿ@ÈwÌT`„•®sàhÈá§Qn¨ƒ…\0 ä>Æ”†æÍOðk-/QZæ€^Éºnäð–œž¢˜ˆrCäx‚˜bôóÏHl=kä¢AæÎJOÊÐ	áL*Ö€k›Ù g°†’d|JDŠÝ¦¬\"Ø‹\"t¸˜EG©Ü1H%A 8Ó¢ °\n2¦]tžÃRäÝ!¿Aáœ5Ø1¢pñ˜`©Ìf+ŸÌ—ÜQƒ‰HÇ’ôeAŽ˜´”ðÎ{yG…R,%,[ÂxNT(@‚(\n— €\"P˜f0<e%²N´paNiŽLâÎñAl­²,‰’Ù*‰Ùl¥ÆJÓNÁS0hD%¤ƒIJ:§\0R•v§–gKt˜BV±UÌúOŒ0Ïò\nÍYA2/ÅÀõ¦7USÓ\$&©‰‰“¢ìH2šŒä ï¥ˆžˆÝ¢ªy©3ÊDÐH\nÐKêùÎÉšI¢P§:›ä\"Ì3ª\"Ñé*«W^ 3c1h&]jOƒá² ƒ\0šÉK“-…ê¦‘`ªã#yp82ÙŸòâ\\ÈÝ7\r!éR„4€|! ¡ŒÖNÊ:xB›u&-ä9²vîãÓºÔ«³)à’cP\naæü…ÂržÂ\nã[4¤À½˜töX­Sê\"SX”KÕrÐ8'­²\n& g4ÉY2UfÅ%F¡P)ŽÂ(RG‹¼^¢6ƒ[5få¥š9ŸCH»‰Nô©(Nµ8Š‚™IL¹„P¬BpT\nhòŠ!–uIªXj¾zœÒŒòIÓ™’m¡SÕBîáj\rÒ0—¹ƒvQ›m%áªT[Îji{	vqV‚¨Iª«´÷võ¼˜>b×ÀkO¦†':µTAÐæ X`œŸ¨ýû§ò™Hba„D„–{áô Jq	!\$x”F2xÉJrŒµ¬>Ea5ª§w›Æ[Âƒ¢/J!Ñ^PrO\"°Qdƒ™¶ñ¬C’³éhS2n,Š!%ó•\r9£åok™DÖ«ÅlÀåó›;ª‰RGª1èÙA…„Ö¯Ü]FpAñ›Çƒ9—\nºG!þwÎàãEêóõ`ÐXTø¹8¢3­!šÀ) 2Dœ”LÐJ¹_:ä·¦´å>Vú€©j)ª—…¨Ól¸WûN£Íú1ÅÅj?«‘D)›\"òm…i—#CÌ[EíQuY&‹Ï4s/gÝ™vU#ÈUáÌèWGöyÚ+ãGAò÷¶Y+cDqÝÆüÏ{œÐs&·^ïéV…NÛyê·çÝàÆTæókú<½ïv‹¾´)Ã8ô¿…\nWqŠ!¦O¼§h0é|o*—™_†Ù)E¬§³sYì¼÷Ä‹Ú1ã6ZT\$OEÏfx£|Cò+eì·å<;}íí×VÍ`-D	©ì™´	ÄÃƒ¢2EÙ\0¼&éÎšõtêÓÅývó:çºÿC.N‚\\-	8ºª¢ØTQKð)¡B@õ~†òz*È&<c[3cC -–ùf|*¯Ó´HQª=HðûËkÇzQÊ@ï®?ƒy+%=ïÂo­»¢7aQ4»Æ)r“ßã´oÃïo0qŒ'ß[”Õè‹’/.Yó{r¥h‹E|û¤è«ÈûQæH(¹×@*]H¥–a/è'„\$ÞO‚-~Ò0YØëœ\$ÿìH2\$–Aþê÷¦Í£üâÑ•	è¾ÁÅïÿoƒýàåã½l@PòAYé×?–LÿÝúÌn’Ýö»n?s¯¤†¢ï‡Êþlüm\rÒA‰´M¢H7ïÂóª>	Pý\rø&°bÂåŠ>õêlH¦¥ê´A­xðÍê£é±€áÎlþ×‚ÎgPN›k¸³…\\Fí«Sã.ÖNfÖHÏª®„‹PµOÂñïÔbÌ#ÉÄöepþ°|ŒF€Pµ‹\\¶d^DLôïR<ªˆé0rãÐd¿CÜK#(Ùp(Ñ®APlâ:ª>	8,È6\0 %„‹fÿå8d&¡î\r‹Ðü£ßN®ñkÀ\\)À#h6ÿÏºu«>Uo\nü1Ï –ÑÀÜ; ÞØkòZ„aÎ#‚Tbê¿)K	‰êBRÐ£ùbroD†\r€V\rbfSF!¢–’O@>\"æãØ\n ¨ÀZ’\rÀÆCš&§ú·‚¤#Œ¼àÂÎŒþpéÂr¦‚Ì+Þ\"À›ÀÌ(B\réÖN/ç /eL‘«²NŽ9+n!-Ö¥„ÁB>9ÎÄ‚eJLâH^1ðNIZVZ„úRë<4ÅÄe\rY.*lþåÆ£d\rHê?!6–çA)òä ¬/\rL\rî‚`’&œ2+!p<ûM \\ÃJ3#6æ>‚\\\$êÈ/m\"t¦Q*Ú˜M:!ÑæDÒÃ”§þžóëªŒ\r„›ÍZ°K)e¸œü¼Â†™à¤â¤ìrª˜Rd¦d\"d×e~¨*KëÓDó y\"¬ŸZ7ƒXùjNÒÒÜ Bþ²BS°\"t†â â«´nEä7ÃV\$’ƒpÀ–ÄD-à	\0t	 š@¦\n`";break;case"fa":$g="ÙB¶ðÂ™²†6Pí…›aTÛF6í„ø(J.™„0SeØSÄ›aQ\n’ª\$6ÔMa+X¶QP”‚dÙBBPÓ(d:x¯§2•[\"S¶Pm…\\ŽKICR)CfkIEN#µy¼å²ˆl++ñ)ÕIc ‚›kÚÅ¶²m¬ÛkFÚÕ¶¶m­Ûk†ÚäØWM‘ü…k8ÂXbUüB2`±XöXœ†Ï@¯Ä\$rÒÒÿ³«/ðÕ¼Ž!°Øòp{5 ²o:ˆ\r±”@n7ˆ#IØÒl2™Ì§1Óru8'M±ÐÐiâ&.\0ºÿ/Wf¦(~¾µUDSék9Ïö†“qŸ»Ùˆöñò]†RØ\\Ä±4u…ZþY\$É±Â§¥R¬••èR:B P9N\"ÑRÓ¥e’_!Œb£¡e<(¸>)*ÏÀHs\0••	Ò”ÌÓ&¡„\nÛÎœ!\$*ÆÈ¬‰ZU0¨:Æ—!\$˜@%Œü²«DLË3Esæ©Åh:þ³–‰ñ„Ã¬ûTÌ‘ŒLªTBPRÖ\"eœÄÁ¢1Ú4iäÔB´iÇ#5ŠI\n„©l´Š™#¤pòJïÂM9£°Â°–¦Ô“#«bÉQê¢UDéR©Q‘ò=	*°4£,¿¿Š´UM³´kÍN¢JV1dB´lLÔ\r;±E|\\‹½XU!-{\0×ô”LÀÒËü°RÏÅ”Ô +óµ\$B:…–2`S£¨\nÏJE2/)5Š¥e4[¦Â²U²þÜñª}¿\$Ð•Ç‚0ê6\rŽ+¦à#cÂ73ð,æñsÂnð°Qêk\0U•s\0ÕHu‰()wèì'ŽÝŽ¦ÄU[ä4B“EAU³´òWP¦+5mÇ4üOQ\$sA%©ÆÍ™dÉE1&È\r‘\$8‚FÈŒÄÅÞ–Dþ™©LÜzU™£'\\±„%’²º¢fÙ†,¨Xñ@9ƒbþÀÑë*\ríÈÛ!\0ê7c¨Æ1¸c˜Í}„`Þ3½ƒ˜XâŽ[ÈÂ3Œ/`AÉXA\0Úö®`P9….´ê‚?7!Ásè±¥L¶„†—-<ÂCEÁè5Œ£p@!ŠbŒ¿õ1«@£ÉáT’ÙÙX’Ê°NDÌC®bGÉœ*²Ê±Ý}7'Ú¢jü©)ÍË­\rgª‡dŠÄ„é<…±ÉÄ«ôëS9Jñþ­ä1žX&Œ#›˜ÓýaÜ7‡%zCÀp\r!È2†GL	²˜‚ Ð p@¼‡x<ƒdrá¸2‡ ]C8/v°¨<ÆøCxnà‰#„ ¨_:Œ5‚ |Chp8µÚ‡@xÃ>(G\"àÞ¯N3–.ÑÉ‡Cu	[¼\$\rÁÑÏ!„Ì¬ÙeNÊ	Ñ\$fIÓ\"B‚œm†|…\rô@¦m„s4õAC\$•J\$„‘Ñ3ó\$ˆd¨–&¨Tc¡Hª¤\$2šI#4BÅXˆ>ä8y×”’<I|•¤ZD3î oÁ´ÅS[CŠíu-äHPáâ,ê}©ŸrÎ,‰kt¥W47êžeK§äüù“ÄyúI\$’•Ç) /‰»Phl™ôÄ	äªLo,ëóbÀ±ÈpÐ„AÄC8 \naD&\0ÌoÍ±¾‚!*	Ê•èiˆ0+˜¬ƒo\r¤n›XL“\n÷’êqâ\$AXbT¡¥TA\0 ž\0U\n …@‹E¨ÀD¡0\"Ñâ;\".Yk’”'v|‘#”N„ì°-%à¬ÙÙ\")è£É‚ÔjS¨³‹e”XËÁ¬99\"}ÒA‰m½ÒRÐKî[ˆ–ª*®ýT1’«ÑRˆ¾¡Û(–V–ÆIKëB‰•æ-ËÃú ^CPIUMJ’gÊÐ+MsQ‡œÅK\nþ\"š\rj®Š¼Å¿¦¦I¡›DÝ\r®•(.˜êk[•!z²*|THh®%ÀD¸øXÝaUÑ®6Ô(–Ó‹[\$ê\\£å˜Aí)CwˆVc(1³è•/êÁv'ô²ÃHz (!Ð àænXS§\0001œÀÉ#+»:fäÂ1:	ÎTÕ„3Íqs§VÖ+Hi™,¥ÉLËQš½vinšÂT_“Z	¬×¹ª’lÃÒÙ¡.Æ¾š i„cß¾—ý(Tâ‰‹v	4o±qo\\È2ptŠQ¸³D‰ÛŒ†»ú¦˜,£Ñs)K2ù™´ž!*HÈœÒ¢HâR‹¦NVýÊ“ÓJ,V\nÃ4—?tL^‘!V6OŠ÷Mrƒ²äÝ‘I	¥“˜*jQÓU\"²Å’É¨G­\r´ÕxÂ²6;Ëòš¼”=)­Ù–Q½Õ=¦B¬äs&rÌe[\"÷¦@ˆXž·ŒŒq]5\n\\†èjN”Y®Ñ%e¯åd •1\"f•²<@•;!dxÑD¦Æy’g)XÁ1ü«¨´”ÃFÇqZ+jS+k¾m/êÔˆ«ylXô\$³@HQä‘c‰­uî·>¯&Ü5c k.º¼ˆÒþ^-¥qéOfh#]9õq¶/)ä,-œ —Ó\"Ü†’Ýîy¦~„eôÛÇßsßZ«¼7*œL—ÎºíV#·i¡øÛ[Æù\n’yÐ 5s‚šNÑuUˆ°2ú»i‹¼ë…€®\\QgÖ;«?â\\m±¨Í‹öoälžÁ@ù˜–Å°˜¿•Xž[­.§âµãf“Úsw9žÜç¼åœIV|v·=¥ ’ ®”ÑþÔ&¨‰Ûw`c²iBÈD	ž´bW#óh	ÄµZ¢2þ£ÕóoZeØýy3ŽÁ³pkou£ÜIG=Ð°š}ý|6/qït:5í»\rÏy*ò%FVb£b?ÒÚÞZGÕÕbGr±	4•~ºÉ_*ÔÉB;Í\n¡©ê‡ˆÍqJñ¿K<]V#ãS3P¥HkGªŠ‡Ñïzå\$ïÛj>ïÅýçIk=™|;\rxXP÷\n/í,QÜ¶ç÷\\Ëç©ŽaQÏ÷÷Ý}U‹~Ã[ûÑýZ\rt…k9š*]@,¤Â!d©9]H8l‡Èôò\$(ïåUípâÄ”n.Èûn’úŽ¢ÿOû'Ã\0þg‹îUN‘¤@3ldÛÆˆøÄrgè/bÏªZhŒd{ãÆšˆÜà%¤0îx÷ä•O®åeÏ¤Ôo´euOÀåp¶ÎÿîxøÆ“Ð0kpk&[…¥dT¦¥žÂ'¾ù®z¦D\"Æðð•\nL\$tp”å0ª÷P°{ðrèP¾Âd Sd¤©¬R–úÆ'Gƒ0O0(g¥dÕî`HCêyGÃ?By”¶d°ÖíPT&ˆn&ä€ë‹6A‹0<&ªÕ§*ì«&+(Â ŠVÓ\né;B‚Pe^œ¡.ÔÄjñK(-z×‚|¥Î¼\$.Þy,‚\$±T×Ñ,ëçFØ †€ä\r€V`Ø\r Æ\r`@†)Ê\0ì Ø£šnÈž\r Ìo)ü:ÀŒž,§ü\0Ä6Ç\$\n ¨ÀZ\0@v Ç£Ú:Ì ëŽŒi®ÃîØ~Ã.Û­îŽ1<šÅ&¯¥.Ü\r¨êä\nW`›±žJdPA¯6Mf¼f’!\$Ð ä\$1ðlÔld“\nàjž§é”—D*ëuéYOæÖDÄ	€Þ„#Ø=ÒDœ€Ú€£l9C¤ýf,¥DfoNk«4–Ä\\hG’óí¦’j«>ªÎ9'++'rjL+Vë„IQñ')\rÏ>\n…þ8cd6ƒm'k`à‰ô¹(:Òˆß±ø-õ j¶V¨2(àÅIœ±P¾­ÆPŽÐ.J]í²¾+ÔàÁf`ªÖäÆnýLN,~¾€¬ Æ ê\r§x!JÆud*ÀáZ1¬ð3ifÀëjþ^L-Æ®Fžéò|ª­\"'Ë\nnI-Má:a²ŒuÎÕ¡±LV*Ì*\"NCc6GÎt\nÆ0§^C@";break;case"fr":$g="ÃE§1iØÞu9ˆfS‘ÐÂi7à¡(¸ffÁD“iÀÞs9šLFÃ(€È'4ÇMðØ`‚H 3LfƒL0\\\n&DãI²^m0ž%&y’0™M!˜ÒM%œÈSrd–c3šœ„Ñ@èrƒŒ23,ÜX\nFC1 Ôl7AGcM+4™â@Qêc:›¤°ë\$Üšo2f0ÈÙ¸æÃTœ±—ìDå9Mã¤Ü­„›„æ±”ô 8a2HI’Ài:BcÇZÑ´Êt¯ÉXjªZ…î0v9\$ÜŠnÉ^Ž{“+ŽrVéÆ3y¸é:Ërž¿WÈ2ò·;n·¡Ò®ã²*ÑÂÁ3‡›¹Æc1†Íœ›ŒýQW®6\r#›+£ªz’4ã«ÿ\0¥£`NÀ¤ª2˜< LpÒê¡*Š¨«)*Ê¶¡9k(*#‚'°Â6€Pœ7£\$‘ZJÛ„D\nÉBÐ0˜esKØÓB“02Œ#¨#²ƒ ¯¦©Ä#B˜òå˜eCl\nhcK¿ÑHçE¬:œ£5\rTMT#º£s ¯°Ttë1I”W¾Kðé=ºê¼þêµb€Á:Ã*úú)0jŽ2N³º4 Ò	œ9c’þ:6”sÄ±jÔ ÏåG>ÓiÛ‰GEã«J6€ØÈ<ƒ¢(2xÆ€HK^WÖ²2¤•ø#:µýKŒì¹R3Ðï¤2ÓC•²4IJ<–W¦sŽJÈ22^/(˜”¶p˜ê¿qÚL:§c,)G6s¼)Œwjž è\\0‰\$’2Ž#©†U`Â˜¢&O# ÞôÝC-r»ÖW+š2¼ØUu.S–J\rÓ±S<d°ò^9¦uÀÃÚIkŒæ¥J0gª6'YL•Ò¹z4(\\«¥nTóÂ9o NhŠ<jŒ`‡œÔÌ\\²Z4æ“i3Æ\0004fÀTd×LÛš\"]3­õ@\\ƒxáº™nç=#5=_»(B+w`òÓ`crÃ&_%Û×ƒk°ÀH¨°nófòÁ([â!¿Sü9ðˆÏÃ´ógYq‰7Xa\$7AHn*\rã^@b˜¤#=+\0£:0\$ŸqÈÜƒpêÒ^3%ck4‡²Iºü2H4Ž Wñ³®ÒùÊ?FÃ	¼ÂPøá#Îü\$Çð4è2I´ABÃÍØ@êÍ*MCnÈ7¦²JjI%'Ké÷­#¢	Ðh2A˜‚ Ðn€:à¼;ÁÐ\\C#³&Ä¬3‚öA	—Ú: ¼*àäÔC¤äv†°D‚i·7%lˆ†ô\nŒ<á„42tR˜h1@„†bDScD{€×@u»_Ú\"jèUô¼X’¯O&U9¡8¸³Á\0P	@Œ‡PàÕ° D¿ðNWHs#Á¸‘V*¡ë;¤EtÄ¦ËàŠF&IÙRCkxGät¦²Ld A/¿GðC:¹%¨hFÊBB€O\naP¹€@´Wj:)(T†D€<R' §šÙN¯WÂM”1f\\“§„½“aQuÆ9“·’b~Ã’Õ`q¨(ã@jZ	°7ÂZ›‰)`¥‚\$iZÁR6#†@fW‰.%¨–¢Œ%¹.\$¨9&3 U¸©!)NhŸ¤º’\nñm±°ž\0U\n …@Š¨x &Z,‹‘„F!A¾n7ªA\$e´é1 T”F‰W’ôp'4'‡	F‚±DGfÈ0Ïc }PR=F\nåê/Ã|í_)¨=èüˆJY0Ñº¨œ¤½Ôà*fÙö%¯Õ’VŒ|Ï©÷N©62Æ\\QÈƒÂ.¨ˆª–ÄkS£-Ž'T§%3!czå‰\r)õ+e± ÌÎhµ’¸Ör4ÖÓ»]«Ö²W&”•‘½©JùÓ‡½OCJ¯K&r  @åXÊNÀ„—Btƒ—ÔgQRÒG÷öŸk»»!ÇX0ÒKhÊŠI‰ÓE •ƒšTjÚ¡ˆ	…K4ˆÈ{ÈG¥ÀÄÁ_—õŽ  9‚”Áq)1›<‰ZÌñü¸Í€!ÒFäcJ0xd†!V>RD/J`7ä-ÇÕE{)}¨íT*z]è\"íôG9ròÔlýú`¬èˆ¥@Ï‚³»DÁÉE¿³œÖ	[U¾D5*¬Ì(ŠÒK\"	àaˆì—L¹F’œX›úDQB\0Lè±«#\$m&Y«'FI†°ò®Éqy0K	^ \0ÎØ^=bÜ<.”ßÁ¥q´1†4ºé†EÈD”6ß€ÒÜª×ÉL}dàé”\r\"ØÊ™XTe‡c‘’T™~¾F;ýPå‰º7™”T˜ªMÝŽ<aÙ-ælÑ”³^Wrº†h4±¡sîOiYG5œ«¢°¾f1OàÉÆRÄój“I6}@áSY˜|A§È‰JgÂŸDe1#µ–‡e­_ò\0®C™Ò7ñçëƒšq¶>%®í€šØïµ±‡:Ç³Z¦4þçÑLÚÐ„®^-µ`«G5Ü ¬å{IÀ'VÊãlªì*kÅÙ¯eSu\nš®_Âµ9ŸxeÜæd7¦ëgÏîení÷]È„šÄá„*ýó»ÊÙô>Ùƒ†5ò_Á·æç&<Pœñh¾¸Êê2QÒ‚K.£]Æ€((SÓ¯`	i©9¼·SzOH¹—/n”ä’mËÐ¾¸ÖtnÞ˜bP¯å¤­xRUêm.·%â­‰¤](›È+¹™¯\$§ŽX6Yeÿ®æJEWºÙ×›fŽ=‹xr!S×YEŒíZO¶t)b}Šî=~Â©bq>Ú	:î½kypþÏC>ð]c¶­/U|EÝî»úØI²ûÖAŒã±€ëµîëŸ3óñË9#åÌ½˜\0O›Ä^{ÆiBËÜ&oËd'Êq¼ëÇoþðÀ4THÏ?Ä²¶®÷P\\ïxÿö¹Ó³¯‰îþ?¾õ½»Žs©ÒÔœû¾hÞKß;©~¬ÛúFR˜*±;¦ž¶C`ÎQü-tôÞ˜Û{©Ø®x½È3Ð5‹°Ç°§ãÖd‚RoÂ4«hndBŸà†ùíöøÌ¹ÈUNëÖÎc&¿l\$¯®¼î:U/’àíúôï„˜Ã²áðHâ*ÂîENBñÍ\\ï:Uø	êÄáPÅ-Úõ¯¯O,PÚëhòpLà!SPy¯§CËÍ<2•…ué\nP†cj®(\r Ü…ÄcšGÀÂÜ+Ê1Íj+F–*F#ªßŽ*óŒG²»ç°õpFã/–îàõŽ ú\\f%Zz\0Üº02½¦N=Å|;eBÅ®òÅâ¬Y©ÐkO‚–ÄxD„L`c\$bÄZã©4¶Ñ0æënÌ*…82D!päñ®GÑB;1G®í0 °B3Qd¾­r1V^Cè¯Ž¸òÏ ‘‰	±_	ð4©1”DÑ(þ‘£IZÀâ[±Lñã\\¬/[q‘±°+NëvÁÌ 09‘¼Â\0¨c@à#LK\nf7bELi€Êøƒlf^ÐŒßñAä lqðœ@>ÏÄ\$P0Dž ¥tZâwLË*êî §£.b\0È‹&!b@~êèõ²@ÇÃ0&ð:£v\$š`‚¹Í‘\$,4v.ß\nò­W’vb%/•Î†G¢¾\r€V¡`Ò¶ÃN3.ô˜ìp6qpùblïâÔÂ²—ÏÄ¶ÈFñ\n1âw`ª\n€Œ pÐ´ô±h[Ì2õdô²‡+ÐW-ÂRÂnÓ‡Ù¤Ø“FØPdæ¾ìæFèZæˆæÒ¬9¤ÊÆG%ä\0w`E1h[*Ž‚%£‚`Ò¤ŸîZj%Î!ˆùpÄ…ð¨G¤4ƒÚ\r‡îì¬E¤3#6Ehx:ï4(ó.jëb¾:¬zöól³E{.Ž2]\n°ªñ+=7¦nÏ™(êÛ93ƒ7.3ð[7Â6ó¢ejVnÃ®rI4vÐ)\0qB4Zå²[,rÛ3’½nV^cô¦k\$îdñ>žžÎNèëâ¨ô«CÓ C JÒä{ÊÖ£\nl^	ø/ Ü(„¶@cÖÝ	hæCróx;&Þ©íÆªK?Çò‘Ž©5‹©ÓŠÜj„}ë·PO\0%jŠA†^€ð=ÅÒ@%¸: ";break;case"hu":$g="B4žŽ†ó˜€Äe7Œ£ðP”\\33\r¬5	ÌÞd8NF0Q8Êm¦C|€Ìe6kiL Ò 0ˆÑCT¤\\\n ÄŒ'ƒLMBl4Áfj¬MRr2X)\no9¡ÍD©±†©:OF“\\Ü@\nFC1 Ôl7AL5å æ\nL”“LtÒn1ÁeJ°Ã7)ž£F³)Î\n!aOL5ÑÊíx‚›L¦sT¢ÃV\r–*DAq2QÇ™¹dÞu'c-LÞ 8'cI³'…ëÎ§!†³!4Pd&é–nM„J•6þA»•«ÁpØ<W>do6N›è¡ÌÂ\n)êîæpW7­Ñc\r[è6+Ž*JÎUn\\tó(;‰1º(6?Oàôÿ'ïZ`AJ–‚cJ²92¬3ž:)é’h6¢²­« PŒ”5Oëþa–izTVŽªÞÀ¢ƒh\"\"‰@ô\r##:ð1e³Xò #d·‰f=7ÀPŽ2¤ªKdï‰Š¶œ7£ ÄŠ+q[95Œt>6D0„	IC\rJ\rô¦PÊ¬BP«Žˆ\"¯£=A\0åB Â9;cbJðƒê5¥Lk¾'*ì”‰–i æÌ/nôòŠ/©GRë¾a“CRB««0\0J2 É èÔu*‰SÕ38Ô:B[fÿÀTŒ<:ÃXÆ4ÄƒZp3Œê@Ï¢µŠãG¾³8ä4;\0Þ9IŠ7.l[ê¼¥c[7Fã]ž«5„Y2mJÃ<¦)bÖ6Õ€Œ:Ã¶â„˜Æ0Ï\0¢&6Ýð¼§ª6·ÊäT©¥wdÜÉí2NtË)JŽ.‚S(«¾)ªø\"%SÍ4ðc©Œ4¤YŒ^5‰Ìò­ë’BƒdÚ>ƒ8Ò:£}|\$£…ž½ÜxŠ<gÓåˆ·¾+âýÔ@ß•RC£–¨9!S‚PhíZBNè”±Õ“é6¾¢ Þ×àA\\c¨Æ1°£˜ÍxC#~l7abB9)€Î0®áT«¾2…˜R›˜dL°«´u\nb˜¤#&ÐÞ7cfZØ6•#Ô9&#ëu>c}\n<\nåŽA»£\nµÀÖ0â*HÊ5¡CXAÄî#O7!:ä«»*ÓYÚ%Ãl„.3ËÝwˆB“Œ£R)>oL¯¦:Nsªô—CWPã¿}ú9Žc½ŸRº Ò¤\0x˜\n@ÌAhÐ8 ^Ã¼	ÍIÂ ä–xgä\"	‡‚²Þ0nàˆá‡'” \0_Rªäæx\r³€ð†|‚È‘IYŠ“£àŠQM¤ä“pœ]ËË´AÄi\$ÝÌŸíõ5”s6êŸ,å¿8€H\n	ÄS¬\n\nb\nd}Æ8æ¶*\\‰–\rÄÁ(³§pNÉéÿbÅhÎ¦8¦ÊTMO…ÉÐ(…Èé!ˆg3æÂ@t‚É¹ñH`‹<\"bxS\n‘°ß(£Å²Ú9!ÍR:©(¢Ê¹H9AÑ½`ÃØD“°ÀƒŸò¬Èˆ«°4Æ¡{Æ³vŸš‘0 Á¦A5öL©­6„Ä#H¶¾!=h¥) äIW©K\$Ü\$—7•ÃZð`L<sXsÀPfGê=‘µ¼c‰P¶>gFs€ ža“ðp*a”Ö3üQŸq\nž§Å\"£ô¹ÃÔî\ní^“‚üÙ#Øl2J(¥J”añÈ2“qk÷D¹\$t3Ì¥•5&F–É9ôA02Ò®›ÉIÇaŒ–÷ããAÚR}ª2žÔØ©%1Jjn™©ÌBkdýZ²58§š•@M\rT#€òï\ró:AXÿ3,ÕðÃ+Å×G‚ARÉ[D’¦7¨²+!o1-ŒÖV”=8ÔÉõ±.o”ÜÒKéWÁL€†PÉ*)+'€Ë2²)E<-q™ÈS'¦ËÚ¹#ª\$¤©6”wçxzž (\$­Gêïìåž}F¹Õ\"wêhrNë!¼ÃÊÈ-3^š¦ÞlM¢G(ó+\n†É`¤óoPr\\ÉÐŠ[•\$\r™¾[ÍA7´F~¬IëÚ]Ë<Ÿ# ˜wªA1š òÄÐò @H«;‡GC##¹F]`×(V©Ñ ëåT‘`äª%ö¿ÕgÒ7|\nÑènÈ·98Î°C)ªUò™½_UžzJ9w+0\0šI{Ê=ñ9k×\\UðF\n˜0¥ßœ(p‘IB˜Zÿáœ†ð„À±¯²€Ã‚Ã\rÄ×ìU¢`ÄÙWš¢Uyƒ‚þ2ÞúáÒ{°&õPTân»¹2%¦”œºüT2Í!Ë‡XÛ	MÃ¡µÄvHÀÍ\"4Œê\0\ná”14°F±DC1fví9ú\r7³¨ÖÃ%u;Ò8Ôè:ôD˜Ü·GËCYSã:¥.ÏÀÁ\$®aî^'\n|¬…?:‰Xl5ˆVQêiÄPg&#zÅ3R*Ú˜Ìm>ÔeíJ™÷Héí’':ñ¥±vÚMµÚ”ilî’¼)eµéþ'åœ3ÞiWnm£È3€6Â|\r4¬OmÅ¹'œõ¥ô>Ðê]Ë˜xwò¡’–žvÐíÞ*~~©+2ç((ÒˆÙ=¦N­Ib•.ký‚SøI½œ-—#ôóž¶î¦•f=ám³ø×\n¦¶‘G/`iÇi…a§56Éuõ<×LrÈòµ³xû°æŒ{crcŽdÃA˜?7c4è~Ð±&Ç˜Ga@Ñè\"´;tœw„\r—MÅ}C£~§ÕqÎ%¿/¬á7alæ»´¶ÜÇ`k®ÐäæÈlˆ<÷dvûkÜ²{Mç€ëÚ£»„(cy€€‚Y®\0Îƒpa‘ÎiÎ£0ŠTxºñOr%½ ÖÞ‰‚(cÂÏtà­[ÞfÛÊ¼ôzåÉ|><®â^+A¹ê½ã5&·p¶ÕÿÁìC¿U	ýVÐºxpÏqoK+';IErB	ÏµÌ{tï¶ì&*~oÙÎÁD}‡·³K#ãXzïvÎÂýÝ±¿;còtëù?_Úç\$Þîÿ×±©ißþ¿—ð4öF	b´±¨yCÊÞMBN2ª*œé3 ä+mFPNŠüÍt'0\$ê+`ý¤+èÖþMž]årÝð–\$ Ø{ÁBµc#/òVðBW‹Ìÿ®\\¥QNlå¦F\nÍ`Hƒ”Nã–%ê*êöYæž=¨üà§yîöçP”=Žé	ÉŸ	pFÐ0“\ncÙFFŸ!‹%†MuV´­co.ôü/”oix¸0¼Õ‹€Ï£PÞÏ<vwë…\n°·k…FVš¤nÿkÔjBK«ÈDãÄDBëÄ¸¼d…	Ž<Ùãöc6±¸MàÜyKÒócî¢ª§cVÛ…ŠU Ì©Ê )Cl•Å §Ë÷£³ç¹C3‘YÆ	\0ËLÖ&à–¼§8 \"VÓŒ<eF¿Ñ„!¨hT\rLÇ®ÌÂ¥RÕ¤1Œ~¿q°ëRÙà†P Ø`Æ(\0ÆxG6—IL\r€ê5¨jg È\r Ìo¤&àŒ˜- b”|ç\n ¨ÀZ>/.<l˜êé”gãÿ’g²'òìg!Æ\"\$#Â@\$BH\$´k‚_^&-ˆF#Ë ±æºäÖ-ÈLìÔd¼'éW%£ª=‚”ÏVjò0H×!RxPü\"¢(e\"n;z@;jF\ræ¤<rš—&r)\0@J%6Ñ”§Gð»ÅÒ-Øm”&*B0Ãˆ£Ñwí”Ñäb3gšMVÚÀxDb'Â,ÕRÐiMðÆ8ƒl¶ãV5¢rn\0Í'\n0Gó.ÔÃo¤+%}\0Üõ¤¯!B\re¢_ šú	©B²­ô~¥ÌÓ†&^¤ˆZ+³+6\nÀÂ`êg\n0€Â(T@L#ñMÇ§ÑL	CVRÂ\r’É4“x«C4%J=è+Ž6á…¢-a8rÚµâ*¥…‹\nðŠ j²‚²\r±L¢F¤õ§FÁ\"¾bÖjÆ\n\r Ú";break;case"id":$g="A7\"É„Öi7„¢á™˜@s\r0#X‚p0Ó)¸ÎuÌ&ˆÊr5˜NbàQÊs0œ¤²yIÎaE&“Ô\"Rn`FÉ€K61N†dºQ*\"piÑÐÊm:Ïå’Á€Äd3\rFÃqÀäk7œÍñàQ¼äi9Â&È‰¦…¥É’Â)’”\n)Ü\r'	ýÖï%˜Ü%…“yÔ@h0Œ¢q¼@p·&Ã)ž_QËN*µDÑp¨˜LYÉfÛ„ë¶iÅFNu›G#Æ[ñÓ‘„ð~Ö@¸Üp›X,æ‰'\rÄ¶G*0‚ˆò4ã£1éˆ#æîï\"çE˜1ÆSYÎ¬n¸Ñ¥rÙ¥@æuI.òÂTwP8#£;Æì :Rˆ§æÚ(ºõ0¢Þ¶HBN	LJ<ïã(ÞŽBCH\"#2–98or®À\$ì”P(@0~€ÄBTÔ4ŽÈš•+ Tvû¢°\0ä6§è(3cJIBd”Œ¡ð’²õE¨Ä¢©m{6ïJÒÃT2®‚(Ý±ê…‰*”ìÉd”É\0Î¸BÎ93±¸!± Rü§¨„Š³2–„·C¬Ì„ÉÃjþ('TÛ=«ªòÈèB4µ+Ð@Î#ÉHá#¤èB–’\nbˆ˜	hèÂ4§á\0ž:CèÊàJË²¸¸´\$®’JîKh¥RêH9j»²!²…‘e0LˆÄXR` Ì³ixÊ	-zÜ¯háoÙ’‚ó0Wºm&\nv“²8I#@6BUdÁfÅ ÞËHƒpò\$¸Æ1¥ã˜Ì:”øÞ3¡˜X¨ŽWøÂ‘!*Í4pÜ:ªÁ@æ¥—Õ’ b˜¤#bÃpì¹¢ap@%+xÛ€(‰ÈÌ·+pä¦¤ì‚ã„Ù#eð–ŠÌäÚŽi}8ÈLÉ´4ŒÍn”ÇµK†Ÿ¼´OâcJYªÉ’09ŽcºÝ4Œ£Åá<™pxÀÌ„C@è:Ð^Žûè\\¡cI8\\·áz;Ãzâµ…áR93Ã¦è/µiÀÖÖèàÎ7C xŒ!óäŒ£ Ð7Í5¥dâÈÔ…~££¢øöñTâ¤Š6Ã–’¿;7TøS‚€(&éÊü2hÉÈP£…*Š¦Šì=Úì–/k>„esvÛ£jˆ’LÊ„ˆö¸žïŠ3§Iâ|ã[0r¥—:C“ÂòVŠ¢#( 'Šb§rQ3&í—¨¤§ÉA)f9!heÐ	à\$… 7`ÒGß‘ñ~à(ÆàÆmMl\rå ¦§ÐªB` ‚Æp¢‚£Ä'	¤â.0äFŠT*H|‡¢×«	\0cSˆÜ¶‚ˆxNT(@‚(\n‰ €\"P˜b€\n	Á…iÄd?¢â‚<	°¡ƒüD€nRE°'‡è’ÚœJè8ï´>ìIŠ\rªþ•g¾¿ŽÌ[VEE¾u‚6Z¯å”½–Zu¤YK&aÉŸ²td\$A,M©)j#‡òKViX2zI%tÌˆÑ*'2ðí¤‘\0¬bRùt\ne,õJâOƒ˜:ta’”ØïÏ‹Âx…#Â*ÒR»—S)§’žBc´GDARXœ0ô¶Â:…YÀÆUƒ\$‡:«µeHÖXöæ¢Zf¨ ½*ÀßƒjÜ 2ýbÇÅ5=›Ý\$È )‡ä¯‰z® Ä2â[@cLó¦>/\n‚áµué„»èlwÙúpgd&8to=Á¥J…ˆ¹]r1;‹Ø˜=óŒyÙT©¡2«p–U,“’¡±‡±ŠjM›\n\0Ci˜42ì@ ‚Tì1•’t¤™©Z¦”È·Ó¤nÂKõPrÄàþpÜaB©!(³ìšªB<œ‰(¨èÈ¬*ä¨ª­\$9\"ãèSÉµtEÀ€;†PÄ|Å^V5°ˆ‡c\\\n©&–rt¤©JÆÆzÕGi a ¦ø§ÐaKì9i%aÞ¦:«>éÜ—:fà¾ZhäÏj¹:¤Ä:µåÁkŠ=Vgäèä˜:¥5õ¨¶%úLZÉØ˜-Ô–/ÁÕ_²crjÀiW\$d–Eµ¦¹Ë¡ é˜•#â¢T5Ü†æ\n0-21xã|qžRðöÝus	üŒà€\$Ï5+4aà(6¬3‚›Ê²×RÃ w\nÝÜKV|äT¤ÀvÃÁrpTŒ¤a)¸àK•t¥’8.J\\0Ó|*, JuÉùÈ´ðµÑ¸§ÏáËR_®›²Z3—Þû›w	\n[!4x©üQKñ°@Fnž¦™Lj*¨€¸ãÆ+ÒYƒA,2žlŒbj'¨DŠ¢D\0ÝŠ\r}×Iœ½˜\"&cÁ˜_Êo—è1Á½ØÃÎéàCçÑ'A„>V˜n\"ŽMÄ«çNkè&	—_†fL ~¶†ä«¤	=ïiÇÀ@3¥tP3ûN Yº‚æƒqØN<yPÂ‹§Œé^×9áüo¬±ÖµÅw:u\\É†.¦\"#IgYêB\\\n’×XM*ZG²µþmT”‹dâì=ŒIbÆ©;\"Ìì-·³ÑÓUót—î9šTŽžÓTõló,äÁ¹QN­y¯lÊ;G÷žÁ»Kyk]±=J+#ÉÓm‚ÒoVÞàIÍÊWÁÞB(?¡¸Ô%}w‚!'t»/3q”ñ(·¡:ßü{ío¶¥çÛÜ¥ÒÆn(R9e¸ÜOŠóŒ¶Ö†åû{+zcµþ»S4+Zí.¿³¯\r4>”~–™ÂºLûpVdw8åÆF‡¬rkãÕHØ'H9†ƒx\n·¾=®óLÄ†ºiÁ¥nº°õH#Ã51O•¡K.–1ØŠhìj®I¨a×„4\$XcMÁŒŠ–¸JöiÁ #n¡¨/ò”K2Ù#fã2˜–0-\0€Ž†?(G‰e>ÈüÃ}axj[‰gu\$L\$Žh)9v÷”ÁæŽ¦ôoùÛ<ŠÆ¨Hojaê\"¦èÇÎ(sÙM¶:E~€¦ÂA:(	Œ¿Ï·	in2k±{ÔF©FnÈ×å µ*ãúXN¿^\n^ÏþûŸ¯éi¹×›t/àÖ Þ¤H€Ø·ëžÓMˆÇDö1-6(h¸S¥Ð	¦”!(Ò¿ˆ¨“ð*Rkê½‚ D‰\$%Å‚:ÊDI^¡GºŠP8‚æ0¢\0eƒ’Úe`&\r¦Jå`DHìý¤ØÅ4\$k(2lHÓcŸvþ£be‚äƒ|C ÈAÂèR\nAÀÖ=Gr=ä 2 ";break;case"it":$g="S4˜Î§#xü%ÌÂ˜(†a9@L&Ó)¸èo¦Á˜Òl2ˆ\rÆóp‚\"u9˜Í1qp(˜aŒšb†ã™¦I!6˜NsYÌf7ÈXj\0”æB–’c‘éŠH 2ÍNgC,¶Z0Œ†cA¨Øn8‚ŽÇS|\\oˆ™Í&ã€NŒ&(Ü‚ZM7™\r1ã„Išb2“M¾¢s:Û\$Æ“9†ZY7Dƒ	ÚC#\"'j	ž¢ ‹ˆ§!†© 4NzØS¶¯ÛfÊ  1É–³®Ï+k3ëö3	\r¬ç‚ÕJ´R[iÒ\n\"›&V»ñ3½NwîÔÃ0)µ¤Òln4ÑNtš]¡RÓÚ˜j	iPÒpôÆ£ÞÜfÚ6ã«Êª-ãª(ˆB#LâCfç8@ÊN¤)° Ž2è¤ êµP\"\0©Œ©Ë^Á2Ã“³Âb‚t9Žë@ÉÁcu	ˆ0*Ý¯£ÓÏ	‰ƒzÔ’Žr7Gp˜¬Õ7®ô=<\r3%±hÓ'¦\n˜åˆü¼/Kâ`Î*rúò½¢Mbèñ/ÂrÈ;#ÜKè8ÈCÊ¨„³¼òª!¢œå\$‹ðŒÄÐ@ Œã8ä2±´L&!°KêÎ±Ãˆë	‰ã’ô¶KÒRŠ£H´€éÀ‚c3ÂRÃ@òN¢\r\$PïÔ¦¥#Ü‡CµÐÈŒ\nbˆ™EÀHÂÖ1ÑéšÌ0³L+¶ÚÌÓÒÒ®Q³ŽLBú†p”L!ÑòÍ»¶w{j.q¸(3lë\n\$£‚Ð¹A00à‡\\3Rê¿]´&)ÜËóŽQ+ðØç: *\rèÄŽ<¹l@Æ1°ã0êŽ\r’Œ4– 0±«XA‘O¡\0ÚÞ*@æ¾£xÖŠ„¦)ÊrJÙ:Uø\\C[8˜\r‰‚#n/#ì°‹’ 3ÐÌ»·\n~%Š'czZ&\$.:vó¿#,àÌ0ìB¹£é[LªÄúÚon#\$0ãƒt‹\"C’j˜¤S~­l'J äÌÅñ<¨Å2gaâ`4Qã0z\r è8Ax^;óvÛ“É\\´áz+Ñ*j’áX93Ã§/ŒZÜ5„Aõà8\$PxŒ!óÕ“C#BòÐaÃ_‘ŽŒÌ]Gih‹<É#Ò‘È”{º0ÉÛ¾Ÿ<IÈŠôï@@(	‚ÑÞC’äÓ…\n0R˜Žw“Ð1()mÀ2Ô±ô­›ÀÒF©àÜ3ðÎÂ‚#¦T´64ŽÈ!(Ð7À3ò”Ÿèi&@'…0¨ÎÂ“ÿ j=‘ŸãúF’Ê‚  “äƒI¶/MD7àÎ—”‘ê1æF’SFÆ[h &Ð3‚‚\nMgà€#GÌ¯™ñ^o Ž@ÚCA‚~Æ¤ßžöü’\0Q¹MŠÌ'„à@B€D!P\"€¨Ê E	6ô:Hc\$TÀŠ8\0äDÐÉÏ>ËÈ—*µZ”#ppQ)L¶–ƒÒfŸÀe?d¬Â„4:²•Š\r*¬ôœÓž×’e|æ¾P´¢Ô@T!\r©¼Â ¥ªßãþgHÒWœr tœâR%ª	‚t£-Tšù\rÍê.•®tH©¹3\$ªPˆ„~‡H×¾´êŒá‚AÌ;ø–÷ÞŠH|o”õ,DD	¹\"“t£(¹4Z–Œhý…—2ê¿Òº“C¡Á\"r@Ê¢3:'MP0¼L£ýJÒÎl©êšÚ\n¼c!¹PôJ–ÂgLLŒ7Qs\nÝIÏÑ§Cê¶Sî”á¸µØc	+/,,´™äT£)]-B\n4ÁâÖÞPrHô‰ç¾•éLC4ÉÔ#‚rY‚‘‘7q€\"™g±+¤ªùnh\"‡\"		Bl\nhÁS6JZC\n|Nå¥	0Ê@¡Éá…µ‘\0žØåg1„Àµ·ã‚Áp	yõ¼:×÷XÔÝu®õæµ˜Ä%G+ñ;lä´'sÔCjGWçø÷©\0êTy-dµÑˆ<Ù¡£çbf«V«Ylì7%qá©[VÕ^qÇ¶D:h—Ù1T½XŒÂB¨›!ÒšÄµÃŽ‘H\n\ná”1\\ôSp'-ƒ¶WjÁWÎT*ô\r£)i,†ö29Ò¼~‘v†Bƒ9&”&”ºä“¬È·än‚T·œîS¤BáÈ¶Ã¡€.!F£—<•·°©=«ü—%ÈúáZhÔèhø>ˆlv«U}€¬1{‘i7l ®¼T•Úæ\"\$!Ã«9 ÆPE '§-\"únÏ©÷XÔt€Ïª&b@lÁ•,œe-&3Þ—SJì¢bÉoyY…Ã•Q¬°Ç«jÿ/ŒA1Wñ¯`2Þþàj¹2ö ÍÙ¯1¦,ââ&ª(µÇŒò‚ìa‹1¶A±Y,(sÝSÏÐj§·‹cUaÅTÏXí•B[ST}0Ë6a6¦.šÓ™×\riz^éˆebµ†£‘ÚÍ!TÆÜ3©ašúq'm‰î^tHÕMB6ç(cßsÉ\r—-t]¬±x[h¡àkºØ¶C›ÏëlA¹ýdi©õM3Ã–7ÒH`œ-àm~…úœIºXzv‚ J+Rcö¼Î&SÀ»Ýáì¾Ñßºcß§B«%•ÝUÀ;×2ð«‰œs-õ[my:fc‰ÁS©€Æ3>ÆíPaB	oÎs½dQeMRÏºw*4}Êøfå9òqjÎeÄ‰ƒÛ“ÓœL°ÞÅtâÄuDONÏº^•(d’PèR*'¬›s|æÖŽ ºšPâË¬)¾¨É°Îö.ý…,†^²_º9Çìì/a¢×ƒOñâ@(³–`\\Ûd—o4&—¹U¸ñ—Ûß=7kÈŸI>öû\"èþŒvãùã|G~¦†2›w\\Ê)¬\næ8;Ì·þmà{#ò”÷ÅùØÝÈùã­\$ùl{§›%¾ÃÖùòZ	ÒF|¾0@š…IzZpRÌ”ÍsûY¡/\rø°?fsÓ_L†Ú‰—æJÈˆ¹r-Æ¿Ecð_ÈáƒbóZ\r%£åºs¸–¥º“ù\rÉæD”sô'%”t¸a=!çÀVc¤j€1–¡LpÆo4ì€äc-.=ÉV*dðüàª\n€Œ p*\0Ü7%>ëDÒ\nøŠGÔ°\\­ƒ‹Öm	xÉ„~×¢`ÄŠÄ¡\"Ni%¢/jfC‚‘lLì†¯†0§îÀÇÂ`H¦þáˆH¬‚¦#p¢ZEÄ†/ÏÆ&Cª\ræÚ8]\nDîÃH]\0úýÏÆ©b\\\$0\\WH#Ê4àæ,bÊ²pÂ_Gº!Ê!N!‰K\r]mb3Î¶ƒñÐÄÂÐóÐæàì\nm8\$V2Œ.†`‚£vdàô*¢¼ƒ\$H!Å*¡	ï\rÂÜ/PªXé~N¥U,˜ëM–È©h=#¤.„.±Ä”\"@ÈÜ† ‘\0‚-„œ‘	,\"ÂM‰R>â 6pÔ^‰@\réDdI<ò	€”‘^‰-®¤ëÉ\n4=\nR%^ŠI–hÉ¦Á 	\0@š	 t\n`¦";break;case"ja":$g="åW'Ý\nc—ƒ/ É˜2-Þ¼O‚„¢á™˜@çS¤N4UÆ‚PÇÔ‘Å\\}%QGqÈB\r[^G0e<	ƒ&ãé0S™8€r©&±Øü…#AÉPKY}t œÈQº\$‚›Iƒ+ÜªÔÃ•8¨ƒB0¤é<†Ìh5\rÇSRº9P¨:¢aKI ÐT\n\n>ŠœYgn4\nê·T:Shiê1zR‚ xL&ˆ±Îg`¢É¼ê 4NÆQ¸Þ 8'cI°Êg2œÄMyÔàd05‡CA§tt0˜¶ÂàS‘~­¦9¼þ†¦s­“=”Ð(§ª4›Œý>…rt/×®TR‚ò‰E:S*LÒ¡\0èU'¹«Õû(T#d	ƒHûE ÅqÌE”')xZœÅJA—©1Èþ Å®ƒè1@ƒ#Ð 9ªˆò¬£°D	séIUº*òÀƒ±\$Ê¨S/äl˜ ÑÎ_')<E§¤©`­’éé.RœÄËsÄ<r‘J8H*ìAU*‰¹•dB8WÇ*Ô†EÂ>U#‰ÂŽR‰8#åÊ8D*„<r_£ˆa˜EÉÎTÇIBý#êdÿ+ÆñÉlr’j¨HÎ³þA‘3Ì÷>Ç%Ê¨—E‚®Y§¥pîäÔ£•Eu\"9=Qd~ž”äYÒ@=Èá&Ž±É\$ ‘'16Z/´»¬%u‰cYI@BœäÙ]ÂäáÌDÈJê¼ðt%ÁÌE?GI,QÒ0ÉÔ„ðs„áÎZNiv]œÄ!4B´\\Ãw“\$m¤ÊJ…µîB'²Œ§*Á'I*[ÄÉJÛ PŒ:ƒcvä¶Á\0æ1Œ#s¼(‰ˆùfŽÈæWL]äFs’²åÕ7ûœòºU6AÏÔìAXe%‹cÍ_Ö~‘JZZbA“ÏKÖö×Õxž•KånÔhá;KÏá—%–Ne©D•³mìi@Oš;¤£ú–YwÜ·;ô6ƒ•>ÕéI`b Þ×¹(ò£pæ:Œcr9ŒØà@6\rã;Â9…ØåÇŒ#8Âð„@Ko¯êá˜Ræ…Ás°ÑUb˜¤#Nó.ÐÆœ¤6@.rC1ÆsD6Vð‰B?Äl¤v•#ø¡¡åz\"|D½“µYæyÐé1§í{„½ðA)ñ?¯Ê“Ñ‚±¾¥BØH:Õ‡Yæ¡Ç û^\n	¡„9œ äl `sá¼9-ðÊ€iA”2à@	¨ƒa˜‚ Ð p@¼‡x\\ƒdu¡¸2‡ ]8/¡ºƒ„äƒHo‡€‰‡#p!(_9L”5‚ |Chp6Á¶‡@xÃ>D`‚*@Þ·Íä\\!¬Öèl!«†¸:ÄƒPzBhUc<h!f'@\$êƒrBC¡\nñTHy3Ž¯Á/¼Jdhƒ,H˜Â<åWMµvîäƒ&Â\\õU‚¤G¨ý ˆñ_!# ª'Å\0¡ÄL†˜%SµW‰!Ð-b4/0O˜ÁTWD¢ˆ„%VXŸL}Òø\0žÂ£>V©ñj I¸!‚G#Y}4›¢‰)¢]aÊ,R!}D0A3Ç@¨¨ösJÙ±ŸDø}¸Ò`@ÉsŠA¾‚BƒHgM”\0ÌmMa´„!*\n–øiŠ0R3ÆjƒxmdPÔÕÃbò)°ée¨“ê!E:ò`ã”]/ðž\0U\n …@Š©¸ &Z|^RªRC”Gˆ)iR‹ÊgbLQöŸYæ+ŽèèÂ=ŽQ\0}Rmm\r«ÃÜíDQÍ	ù\nB‚%«T›wRy¹‘öêÐSªššjqZ´5†|+l,…ˆˆÎ\ní_&\\‹™t@I’úÞŠa¢Â­>©e]Uµˆj\r¸^­¥¸U	j {jÚŸqL)é:Å]ÃœEaÌ!R!Q\\*Õ5=ö\\CÄÕ²y\0‚>ß c”¨Dn\rPÛ‚¢U\$ñb±ø½ã29é…2Æš¶¦¦ÛðiA”:B}Ü\na”Û3„yñ>eÔB Ñù“bo*]D5~ Ö\nºÆ\0’T„’½Å¥Õ|`\$Ó–A`-	47U\0,žãÁž”Ò»æ­e±ê¬ÅQ1%²?s/ÓÁÃÂ›YZÁR0Ž%æ_‡dóáé¢–&Kä ÛO”C‹£Ø&ËÈ½À\"ñ˜& Yrh)	û×¦è£Uª;h¾Ï‘ülX0ò‹Y¤,\nš\\êÁ›M]:a‚\0@ˆ%‚€’ôÆæ7=Z<y–ƒÏ›æÑA	—²ÕbLŒv0æ&=-éƒ™2Ê€Xd<,è s¶Œ ˆ”¢•vÚõ¤¡RêfWiU`IrÜÂ:iH¡9ïkå&„-4(¦¦–2Ö{|šHF\0 ®Câ`ZÁb*îDúþskLhG2öZYÂ€Z/\"²9D\nÚ«ç.l‚ÿ–Ä¿ b sŠBð9EŠL« sa9Dp»@3móƒ ¼zÓZ«^aXçš¥æñZ‹X“- ¨³žk\"AÍio½è\$‡1–3âÂ_±Û'6ö\$||‘ÂüÚÌÂhËC†g^Ä2nwHqòIT6®M9')rü Óò#Iòâ?Ì_Ÿ,x­©òþqÌÙuP«ÇÓ\0`n5œy!è¢ñ¬äÎ•¼†ªh‚D—•\"F†N°íÚ¾ñÕÄôw°Ög®X•›R‡(‰&ýƒK­a[“?¯ËÙä±Å8oc®êw8qÎñÇ»Õšî–s»7Š[‹7¯_é>½5BMŠgaâ½ÓÆ²Þß¼—\r¢D^cÉÊAÊ¤²jy#•,?8A™\\»°’û7fŸ.±ü]o¥T±½x›ßù/²ÂÞE`cJ=žÑYS»ùŸ\0 °Z•4³ü!\$eÒÎb­	&;ÒrÕnìƒ)GöA¸ñˆ³ÍþÉƒðŽçúb¯¥¢; ÞGš¥q@DØ ~ñ„ü:]Ç©˜­ï„äÂ’µ% ¿ÏÃLR¾Å€XO|÷à¾ëò÷®\$ñe}l0ø)KðXOˆ½/nøÏrãoaÃöîÏ~Î0JnÞÁ¥r.¡jAÈC(Á^ÁÊcô V[ÊÃfv:/BRïH|/	LŒôfÐõì+	°’³åÔ]„ŒÀðE	í4æKh#‡tYÐ³p¬à]¢XÇ¼Pkð:[kFN†ÀäÎ{¤r0š¯ÎÒñPI…kÎT¯Ï!¸Pü|0ñ1\r\"?¤êÂpùïÁq\0Gdþ”d€ûOlÞð÷eGŠ°”‘4Ã&®ò#ï1<Ê1@GÑ2Hq1+qDHq\$Æ?'±lÄtN‘xÃìFÄ°±D¾1ŒY°ÚIÅÔ'‚®É„îÊcjÁ>ÊF‹°GD,äK>	\r9°¢V&¸kÁ2îðÊa0v¡&¾ÀáÍÊ—D’ÌÎ“çàðdæðcbýaL‰>`IDž!*Ñò phÐÍúXíþñ`†p Ø`Æ\r€Ò`Ö„ À@ÀÂ\r€ê8g‹àÒÇŒÃ˜Ê ‹€ê @HÊ  ª\n€Œ pvr\\<C˜õ¢\nh†ÂÇÉ..ºî2KoÏ¶›)0,è%ÒaCœ1ÁÎÝã&¹šÆÁ%+ã\"2bÏB¨@«h'¢ C	ŠƒÂ<rä  Ú‚ƒX8/!\nÛjÌÁ9\0FÚp'\"dƒ…)Å•*\"0aÊbóã±)Lø#(Úð@îž¿“*¯ðõ3`\n†@7#P5CY'Hw\$€àŠ4»H89Ž-1¥˜Ë ¥Ï|EêÀÒ­ŽåKjVNâšj¦ÀŒ†³Š.Ï>RC6–€\nÀÂ`ê Û-Á\0\\„‚ŠàJ\r0fâÉ(bW ã¦YÄ4`å1#‚»„¨X,·8iÂì¯”TáO2..³ë2,¬!3,Cqï\rÎWib2¤¤«šD¨ÝTS¸B0@";break;case"ko":$g="ìE©©dHÚ•L@Ž¥’ØŠZºÑh‡Rå?	EÃ30Ø´D¨Äc±:¼“!#Ét+­Bœu¤Ódª‚<ˆLJÐÐøŒN\$¤H¤’iBvrìZÌˆ2Xê\\,S™\n…%“É–‘å\nÑØžVAá*zc±*ŠžD‘ú°0Œ†cA¨Øn8È¡´R`ìM¤iëóµXZ:×	JÔêÓ>€Ð]¨åÃ±N‘¿ —µô,Š	v%çqU°Y7Dƒ	ØÊ 7Ä‘¤ìi6LæS˜€é²:œ†¦¼èh4ïN†æ‚ìP +ê[ÿG§bu,æÝ”#±õ¦“qŸ«ÒO){¡þM%K¤#Ëd£©`€Ì«z	Ëú[*KŒÉXvEJôLd£ ÄÉ*é„\n`¾©J<A@p*Ä€?DY8v\"¦9ªê#@N±%ypÄCµ²0T«ï“¡Á‡i0J¯äAW¯ðóìBGYXÊ“ÄƒC\0«L´ˆuˆÊ“daÚ§ ÑØ	,RÌxu•EJ\\NÈ¤i`­¤\$&†É¤TEAä\\Èv‰e\"Äg«GYM'—\$!Öûe‘,ÏM3Z!å\$Š—E»*NÑ1u°@@„áx—&u%+KÑ'\\Í4MRÝ:v%„ŠY–“ÚYaz‘0óë[×%•vƒ•³Rö”äbbRBHÈÈö–e)¯ä!@vs\"T‰ÂþË ð2édLŠU	‰@ê’§Y@V/ä»ôD?ÚÍ]ÈÒD”K«Ðb¡KÉˆ\nsÃ¨Ø67ÎcrcÂ7<\"ˆ˜ö•Ii@\\¯òIÖG“'aLN¾ÏÄ–óºtUYGM±×\\WKüÜvE!ÖS‘[Hæ–hù¾¯½]Ö2Ùm+¯æBèEq˜¨Ì¨)\"0ÌCJYQ'¥ÖUUZQ´-¯MÙr˜æiékÛ PØ:TšK,Îé6AB ÞÙ\r¸xò£pæ:Œcx9ŒØ0@6\rã;È9…ðå½Œ#8Âò Kg¯ êâ…˜Rç°“vu’	˜†)ŠB2CZä	`=¨\n[±\$¼Q¥\\;Ä*\rM¸»–é:2¤,•O0ÉS\r÷HV”çž*s™ÙEØ=ÐóÛJnÇ™ÝùàPš0Žn(äÙüƒ˜î7ŽVxÊ<Hä2Œ€x0µ˜Ì„C@è€:à¼;ÀP\\C#™\rÁ”9çØÁxe\rÐD<VüC|L\$9°èÿùÍaá¬à’C¹\r°H:À^Aò#¬ã†õžoá˜a\rf¼4‡CgÌ\rÁÐÉ ä „¡ 4H¼Ó\0j= €@RÈHòQ‚å	€b.,„x³ÄÄbWÐ@ªtn•í¶õP»	19d(Pƒ“2RŠaPDÈ› J/ñ ;\"ð¢ãHd\n#qüö—Ôü€Þj|!@'…0¨{UZŒMQa(±XVÒ¸½4¦FL5µSJyQ‘r\0v\nCºŠP±Bh*'AHÐ±|AšH>ziÍH aÁ¹¾BßøA¤3‚\0¦Ä\0f7¼Û¿€Œ\"ƒYá¦>È}fˆr\ráµ†@Ã]Kø™¬[G–b»rð	á8P T *y‚\0ˆB`EŸKÄ­§Ôþ_Å•V\$¬V&:œØ¸`V[Šy˜¸>ÂH¿‹Ñ(:ÅH˜«PvFB_Ä/ïÁ—åí:˜Ìij–LµÄÔ×™Iœ”e“I¦P¤t«BçèP	É©ãZdôâ 3)ÙÖÀ{ï:_ŽÁÍjU>©•,•–³GkOTH±DÄ(S#+:Kz«°\n¡íNç@•G¢€íˆÑLª6åNÒ#¥¬—¡µÅDð]êñ©³ÂCÐeAq‡7d‚˜e7!Œâ†Cò~é‹¢t‘Ù—¶Fh¬Õ«Lj*øJPJP\"I2™S\n¨XÖZÔÍÄ1%l+a`‰õf°UØìS²wTéYI™õ#ròX«+•#îk<¤êiN\nÅ‘[ŠrEvÌ­ÚÚ•eII€ :‰À€V‘F*h‹Iˆ¡B¥%SMTj2@¨ÕªÕö˜;ú£—‘Ä\"_œ¹ …þüÈú„@¨òHœÀåË,XX,žàÓžÀ°Q5±„¾5J»…ÌJ4xTÓ`ñ~¨mOÃ'\nS¼/‚W1#+°LPÛ™jŠ'%d´—“}Ñe2t„\$KÈlIÓª…;§Å:GlV‚¸eQe;¬dQ,W+\"uädö•ïâº@…JV‰,È¯ð¥zk\$ƒ	>‡—‰úC~¢®uÒ\$É±“2¦\\½,TÂ…“ÍD¨Â[#bìú±±ö¶¢-kèJPkŠÄÑq™±<!qlˆ2Jöö½\0 ´™ÂÖ2Öa¨5«Ó«H•=E–ht.šÔš¬^ÊhL`§j’ô¦wŒUõèì×øË`¼mjbh=/öÌFÛZƒ#-¾ÑØ0Ó[iYh‚KŠ‹ü­—²}ð¦é²¦à}@~õB1«4ÚŸÕËùA¥~­Z#¨_\\‹£éîì«jF¡jæ¯¬7Æëk»’®oÜ\$/wMIÜ[¶ÛUæ®³YÅ¦-u?sÔ^sí+:âwžˆ.Ø™ËeàÚŸiäzypŒ¨¹±#®óÞ•û•„Á•ÚXáb¦³ð†”Ú<#’ènQq3d©õæñþ;z\rÆfÛKší[‡Ò9ÐáÒß{íHÎ`YZ+Pb<ö”ºÚJ×éLclt“ÇYJÉÒõåI¨\0³HA*˜´Õf¹¸º'ÉAñÖ³¯f©…Ø¸\\XVx°r¼%{wpÔ}âë3c¹ÝºLúîD¡Áøÿ••\\SPóÝeæŸRjÝS¦î¡nT·óXÎœÓ€ù=¨ýO\0êÕ'ƒ¤^§Ä\$ž3°Q×­í™’†'tB&ÞAÖ=)–Þ«ëÃÚg,½;Èý.]óýRÛ[¨-éûWœtzZÃ«pœ}p‡ÿ©Yª‘	’sy’A¨Â\0élùYÑ%—FCÃ·úoÊž¹<õíª^ŒšäetèÐN¯ÂþOøþªºôn OKæI\"<¢Ëøþ:Óï:ô®ODi‰pG\$víM(¤®:èÐ<¾BýðFNPy\$÷-Dâ¯ÌµD¦º	dÀïð>¹ë™o.ä82Ps/Bã†CðŽ)éep	ÏPöëîHA`OØKÜ¾P>FÎ*^°S#&>OÔi€|€Ð~˜údF†ìËˆ4%áˆŽÁn[‹rYÂ¬B^¬ìB!`AêŠ#Ïzå…”‹ÏdäÇ|aj´?Ç.Kq?q÷ÁYÊ„ÑB\0Ñ°Š²fØ\r€V`Ø\r Æ\r`@ƒ)’\0ì Ø£Œol\r Ìohz( Œšhf§Ê\0Ä‡‰ˆ\n ¨ÀZ\0@‚@ÇcÊÒp3í(há@i!21fÓkÕˆ²m\n¤ÃO¶1Ñª|Qg¤¶…rf 2ƒ,3f»¬ÏQÒ\$AAb¼,lŒA\0feÌ	‰ŽƒÈ<Ñô™\0Ú}ƒ^8c–ÎÅÔB!d	ñÆ¦BÆØo¦8°~Ó@Â%¾ÕR*Â1C!=°¾ãà¨aCx5cZ5ñ~‚QR\0è›‹ ~‘¬µ‰=¯Nß)N\\j¬\\ÍpIÒtMD\"¢B´ÙnøÙÄDÛ„P^\$…Ö``\nÀÂ`ê Ú/â’:\nÝdŠÏˆ[HŒSf«ªn å\"ˆ²B~‘n´ámö¨+\nO¦8ÛîÇ,g“úl\$L¢Å¾:J,#Ð¬O<IØ€t#á";break;case"lt":$g="T4šÎFHü%ÌÂ˜(œe8NÇ“Y¼@ÄWšÌ¦Ã¡¤@f‚\râàQ4Âk9šM¦aÔçÅŒ‡“!¦^-	Nd)!Ba—›Œ¦S9êlt:›ÍF €0Œ†cA¨Øn8‚©Ui0‚ç#IœÒn–P!ÌD¼@l2›Ž‘³Kg\$)L†=&:\nb+ uÃÍül·F0j´²o:ˆ\r#(€Ý8YÆ›œË/:EŽ§ÝÌ@t4M´æÂHI®Ì'S9¾ÿ°Pì¶›hñ¤å§b&NqÑÊõ|‰J˜ˆPQO’n3‚·­¯}Wâð±ãY¤éË,—#H(—,1XIÛ3&òì7÷tÙ»,AuPˆËdtÜº–iÈæž§ézˆ£8jJ–’\nÃäÐ´#RìÓ(‹Ê)h\"¼°<¢ Â:/»~6 Ê*©D@†ˆƒ°Ê5±Î›<+8×!¢8Ê7±ŠÈ¥¹®[‚9ª8Ê•¹£(å,ˆl¶ÊRÔ)Äƒ„@b—Ãzk)1èÝ	½#ÒØ\nhÒ5®‚þ((\rì—?S4Ðè%KP‚:<c[ˆ2K«Œh)KNÚ<³ÑŠUŽOò½¯­à@; ƒÐÉE8ôkˆ¸.HÛ‚÷ŽªZ^Å*âÔŒÒï(\0MIS ƒ:	UTµ8è»S¼ò¿ˆÓHÖ1Ìãz Œî5]^HHÊ®\"«û69Ž£) #Jüò¥rÂØ5%H°éHPÈ&%UDO¸h³8³IÃ*9¥hmr6\r[ZÊŽcÂ79¢ˆ˜²ÄnÙ¶U¨êÐ„HÜ1¸ÒðèCãJö9;`Sðê=ÔZùi„äx¸ÌÄL¼×S†^£DŽð\nt-šâd¹;˜\"O²ü0­‰~[\$L£K6Î×¨h’6ŽV©FƒÚnnU£ÔÂæþdÔ½ŽÌD»š1äÒD?[¼¨å;¥3˜Î¿Šƒz,•ÃÍø7YƒÆÏŽc0ëx¤K6Ø4ø€@ä,ï¸ÜœÔ£jÏŒ¡@æ¤ˆzæ›,õr^!ŠbŒÓ¾+Š€î™¾W¤‘£ëÛ_c9‹U‡„Ìå¤‚U—ÃûÒ3¿¬¨Ü¤¡’7GÉ;&ˆÄˆóv´öj<1Pî\"1±Ei ¦Ïç¡\\6\r*Tê—Ôý¡.”óÏd7s¯º¸Ë6cRF’Í²Sô cºÇRŒ£Ààß/\\Ày0Ì„C@è€:à¼;ÀP\\C#‚(AÈ0ÎËŒÍ¶=PÜÁ«Fx:?À¾kW¸k@ùŸŸ´Ž\\C <á„  @MBZr¼“™S¼~’Sh…\$Ä\"öCKÕ-eµ”\"<}»â?/£Ä&@P?GðKŸä\0A:q9²'GŒËŒlKeÒ±¶Òï±¾0ˆ®„êKIypaÉ`°Ô&‚C`p.ˆLî¢’l÷`CFÇxÊ“¢HQ{6€€(ð¦#+S.à”­Q} Í3Rcˆ>†²0ÞÃ‘é}dÔš¾	êCxnÅ•C5ˆé¹ÃCïûH`cÌŠöoæŒ‘@†ö©‹,”_D|ÎS6ˆB0TŠNlû´„}Ã¡Hð†\$¤˜kÊácFdâ’tdž\0ynÏ]Z‡BÐ§K:÷afá<N’äsJDy.†`‰Hþ»S\"0Âi¾µ!)g!\0\",ø6.‚K‘\0txi¼¸Y«ëzfY‡˜¨¶(Ì	q™2ÎTœU;‡xðH…ºFÞP¯Qñ¨Æ4È’ˆÑ#Éa#‡•ð›Ô<‘qÅ’“ ˆdÓ2UT’3ÌÕ(‰-D¹+éP\rò6OlÆ¤2RÙYœá¨ûTª™&›oqÝ!*BZ‚YgD	žP„ëÜ±%é1°†u£Ñ¿ŠJÒ Ý'ÉÕ`ŒNÌà’ô2…k½NõnµP™8‘œø	¡M=´Šƒ(\nhð88D’\\s“æœØÚy©ùk	¦\0¤T‘j²jf*Oçž£>\rÁ‘rPÅ*Æ=¼%¸Q@K\"²']hágÛóÝH\"J›Uþ•4ôxeIHrÁR‰;Lò‰óår™º(XÒÑah¡Í£´3p‹™îtH|µ&PÞÛÃ\$­WÆ\0W3º‘*2^\$6«Õ•ƒJ<-à—µø¿‘!.)VeUªDÕØHa§ájZ‚®`„Œ_âfˆ\$(åÀaªÌåÀN!Ëü—bA_‰Œ)L‘Ô£‘ì*vqxiØÇcS±Ž1F;ÅRËáãËv&‰È~ã<#XË¹T¹W,ŽE19LÃ9s+ºLK˜-NÌy,eð×˜PfhWÄìIá.õ'Á>3ˆLŒglñ‹ÃÌrçIñ‡9­vÃx\nÏHâ‰ª’ôE®ÎDxÆQB\rf'#Æ~u:œC\rß :x‚¶|¹Qùa½ä±ˆ±e-ÉµÄ0)—Ÿ‰Ú\\´³Åä¼ˆä·›5¡:!/_Û6ž[]¥×“cYW×¯­nÍŠ›¬ ý£e\"¦¼ž´|-ÂnNiÝG\rA§¸Mðs?én“ÿ='ý‘-T45Ðöjt­Ý½Œñ‘šýòJÒJsÆÜ P¹É°)Ÿ;Æ{–¬&êÈ©H–µZÞP™ —‡	.{b€ÅêGY›WrfÇ¸–üãØ¦I Í*µ«%{&³Ó®WÌ‘#ev´)óžÈ¸ðVÒu‚û—©[²F:ÇúÓŸâ´oµøèù»8dÜ€¨·Þ¾È]B°ôeÒ3—JT¹9\0ëÞkMÅÓ5WZ6•¯ûIãís{vn6j%ý³s­¬ï.5ÈFk˜ß‘ÒQNTxà¦ã*g’G˜!#¦}\n4ñáƒŸˆŸkÓ‚°òŒ)dñÂ¬âNsÚRÞ%Á·ºÔßÔètÙE(ëO¼w©ºž‹Q\0ðåŸd4ë+>zÕÝº>)M^;_lvÖÐ\nGSsBYÅ~]°ù½û_òÃˆ¤>‘à07øÕvîkô	'Û\"Ÿw•k›ûøÙ¶ÄÐz¹Œfy=xþ±ýÄb°@ˆèXScÙ,'œ†þ,ÞŽ„àbcf:£Êê.¼-Ï¼ùí–èŽ¤ëîš–Ië«óÏ¨µ –è\r(WEˆÁfDOHÁIZÒîI'Ác˜þ\$âè°&Q#˜ðXŽ‰îà*I\$2ùÎlç„Rç/ÔyÐsî=ìzš©=íˆd„R»ÛNÞÐjï¯Ôw€t:DÀÐ¦»È¼/É	gx»0´ù.Ï°²»oÒä\n+¿kÄiPFo+µ\r`˜D¢ÔÿÄhIìSÈ”ÖíÄ¢|Î\\Œð|ÈK\\Áû	ÐÌð@\r ÌBü¾°@ëæ”qp<œ¥DP¢þUc2è£d(& q,Æ0ë£d«Q:6p<ÈqCD&väñÐ~\$Gn=ÅVÑÅ–Ñ ±-\0¯Ê%î–×l†MŽ´êÌ7ñwJÌÉà†O\0Ø`Æz€Æ\r`@•)~RZ2¦tbjÆÔ;Âz˜¨^¡L†Æþ\n ¨ÀZ\0@. ÆKG\$‘zÂìê¥ÅêÎþB2¥·Œùâ8µ¢#ÐG„Bkj¤i­°Þà›‘´Yàò+‹¸8Åi œ,bØ/g8«œ8ƒŒ¡ÁB¢2|€ê=§Ã\"3gF¼é¦Ra\nâ\\J¤h j°¹%¾\réì: ™&‡,c*4¢ÂYlX8Àônj¶R{eL ì\\Â¸+aB?ÃBw¤©*`¥\nœ[ò‹‚*~dÐJ2¡*ðl­Rª\nƒX.^2d]\"ã\$¬\\+0™+Ã®ûb<¥ŒY«Ä÷Í²³\$žÛähäÃî ¤h`Ë„eÍ0B\"m)*æ\r\$b¿mtë`ê Ú@Ÿ-g(#Ü\"Ðz“8Mf KLM„df\"\n…û)³K*jd;R¤ê¾¤Êb/ê7+F’yâ*G\"•dL òR0£\"x*°Bö= ä";break;case"nl":$g="W2™N‚¨€ÑŒ¦³)È~\n‹†faÌO7Mæs)°Òj5ˆFS™ÐÂn2†X!ÀØo0™¦áp(ša<M§Sl¨ÞeŽ2³tŠI&”Ìç#y¼é+Nb)Ì…5!Qäò“q¦;å9¬Ô`1ÆƒQ°Üp9 &pQ¼äi3šMÐ`(¢É¤fË”ÐY;ÃM`¢¤þÃ@™ß°¹ªÈ\n,›à¦ƒ	ÚXn7ˆs±¦å©4'S’‡,:*R£	Šå5'œt)<_u¼¢ÌÄã”ÈåFÄœ¡†àQO;zºnwf8°A®0œÆñ—æ¡§xÿ\"Tê_oæ#‘ÔÓ‹õû}âOÃ7›<!”ð¢jðæ*ƒš°­%\n2Jê c’2@Ì“Ø÷!ƒ’”2¦C2ô4˜eZþƒÈà’2I3ÈˆŠxþ°/+…¤¬:ô00p@Ž,	š,' NKà2ãj»Œ P˜¤±B†ÚŒ#šH<É#(Úæ¡®\$\$ùB£›¶0Êb¸Â1 î¦¸ TRÁI²(’7%ã;ÀÃ£ÃR(ê\rÈä„6Œ”r7*rrä1¥ps˜Æ¬H¨èöÐ¨ê9B²¼;„ á&ÉÔjŽÒ)=&9Ò Pœ¯´€Ò•Êa*R1)XS\$ULH%À@PŒ:ÔbÆÄÌˆ´Ÿ¹k«ˆ0¯¢ší@²\"Ì—ÄiC2ÄnT^5¤¡\n3¥`Pƒ[D•›Ú6É`æ1·¢˜¢&{Z9Kó\r¬:µA\0ÜžHK¼êºÙ,Ìé·³<™'S#u7NŠs¤î<ƒPô¼28\n6»˜e{}SJ+a€P¤2Ì\n \$£…††²\"(ñIZÆRãeû‡2-tÑ'9¸ Â\ruˆÙCB­G6Íô A†\rÃÊ	>£Æ’c0ë•M«ÐæëPúÂ3ÆŠ*ôª%#jõv¡@æ·ª:2/\0†)ŠB2|å…ÁÅ”SŽƒ c2ì£ÈmÊ8+£-BšçC¦xá5\ràÎ2&¢ž‘géú‰Œ²øè4³Ðú°Â­!'SŠò¢©¸–BÈ/ûË=½ïµ\n¢À¥œ›É.=ÿ1¸¯îGÃhxî\rpÌ„Sè8Ax^;ùrc­/c\\»ázgé?ñÐÒ±áh9#ƒ§€/¶É8ÖØâ])&c xŒ!ôŸ C@Þ”Ý¹b¢ÙºÒš¯îü€\nñ’p„\0 ƒ¶€‘&ÞÄNÈ U°vÄž +MlIí“rrNÉêtDÇ\r±œ\"Fƒxtm­½ù´\nMB€O\naP²ã¸lÊK)°©·¢äÕÏãò;Åðƒ†’S\r—)8´3rXUA\0c,Hh3¨²†W‰©™ «€¢šÂ^LT‰\"l«‰r!¢äpÃIÜÁR•²¢ÇV÷˜¦ÔœŽ!5ÅØ2—‚ŠC€\nŠF+–\".\0U\n …@‹!Á\0D¡0\"ÈàÏÊAKiL(”¸@ˆl›1ˆ‰\"„Âfµ)¹rh)”ƒ°íC¹Ô8¦D!¤„‰1ÙQ§äý›OÓxO\r±0‹†×£Oåd0ŒÈGºKb)8š±Òè&Hj8Î!3M&\$Ÿ˜ÂÄ9Š}¿™¢ËÒ(P)Ý¤€è£NÌi0l\$“7-\"¨(\$\"àKÿzÑ<‹N›‘\rˆ\rÌƒ´¨IÌ–²U²ƒaNhi!@*Z±Ö¸•ù+O-½‚ži™æ{aa„š¨àÊè±’òe)¦²d6?Ç	I)C\"ŠaÌ„ü¾ÐeýOŽd{…ž?†V¤IÙbƒ.é¸†‘`©TÈáÇN•Dþ¥Ã­/F-‘2IÄÎUQ•)†:»:ÐÂ‹DÏK‚,¾’ºoUl0«Øö»X !Ô†‡’ JLª«±|È…šî…€ŸœS¬««]ö\0›aH\"£²ˆ~ËØ³,QjÉÔ\rpªUÌC8VH•ÊÔeÕeƒ´Í¢ÎÂÂÊ¬•£8 ºÅX+”­½\$–þÐÙ3½eSË¶ç0¬ ´‰2ºˆ\"íØ¿wMÁ\$‰‚¾¶:Z}ãºV Šô·¢Gu‰IUYwÂæÝ3šjƒ‚ÉŒAª9™©ÞCqeMÙ®ÄdKúß6H`™• Ê’(œ©’4}NU\$\$T•“P“†S¡Sv³¥( jŒß›‡ Áá¬9pé‹*Ä+1&ÚÌX¦1mµd×†ÉE)ËÔé;.MÉàuÞêîCTjÆN²\\–gÎ¢]–Wi–0ð¿q5ÃÙX•JJfKÉè(Zd¬©ì£7gF.™XÂk/Âv‰øm›™´–c›Uk3¦XÆ„–jÍåù_ë\n`yªmaÖcJóÖ:µš	†9\nÁÜ;€­6TÈÎ ÞÑÃ%r¹·2ZS(GìÖw¸š[Li«ÅZ´ëXÓöŸPÙÈõ#ð mêú—Sj{bÄzÚ>ÔÍeS´N¢0õ¢Ã¯Q]4–î6PPì}Û¡\n\r˜þD„BˆÜ¸j\rÇY½¢dËÕ7ARæ×œä6I\nû)ÿ·ˆY#¦½’È‘{÷&¼Ö23TCL›y[™y} Ó¾dirÆzíF¨ýi¡¸NÁÕÜ6—²»Ês+£é\$”œL_78Ì¸ãn[…å ÈHâXï“×ROTã!åÜ[@’fK}×3Áˆ9eËŽvÏÃ7)¡¤èVý/\\Q·ÍssRtŒëAóÝ¬éºegn&¬Õ¬«_«ÿJ×©Óê?1©*ÑlsN»Ñúÿf9Œ]¸¬ºˆÒ¥ºV¶Úlë^ãa;Ÿ+Ña—¹4}Gô„Üq]ô–hös<xïÕp*JNŒ<nØêÜ³Èø_áåØñ•a½t­ä<åZê2«—Š³Þ±ç¡ëu Æz­\0yÉÒEåä÷ùÞë¡zôZë“{ŸkîÓ¶Â/æp9tò¯öA1Šð™—³˜šÔ9Wª°Ñôr¦j/Åú•Ã±9J¹³Ä^\r-“ªÆ®j¥ÊÁ¿&Ã¿t¥žµÝÅTiH©ò\\ùG8la¬Ì?£„–#²?Ž\n&B¶£1C.ÃL%‚vZ\"F° ª\n€Œ pEˆ¤l„ê&«0ÔT#†èàd¢¸kŠ2°Þ«P°CfLÆ0#B‚#âBßæT*	ÀP	§æ\r Ì°Æ.(IV\"Ì,1Oh\$#ø#Ì\0E£”{P\n°\0Ig<`jdf.\$I\$Œ».Š	‹²q¤l†À·âä¥\$·†nAŒb0®F–pV.B€ÅfS88Cˆ%ÐºÏaã„`í´ÝÌ+ÍÇ°ñOÅº*c8tâF8OÀ'J4w0ÞÝ¨Ð¥â,Wb8H€ÏÏdN¯”_E¤”åª§L®Ìd‹€¬2¦,±ƒÂKåJiT'E€*PJZÃ¶\r„¡)j—™…	\nª%bú”ÄÞmðø(b¦Kj´,pË\n`è\"É0¼PNî&iØÐêâ.C|ÏDö%D\\	\0@š	 t\n`¦";break;case"no":$g="E9‡QÌÒk5™NCðP”\\33AAD³©¸ÜeAá\"a„ætŒÎ˜Òl‰¦\\Úu6ˆ’xéÒA%“ÇØkƒ‘ÈÊl9Æ!B)Ì…)#IÌ¦á–ZiÂ¨q£,¤@\nFC1 Ôl7AGCy´o9Læ“q„Ø\n\$›Œô¹‘„Å?6B¥%#)’Õ\nÌ³hÌZárºŒ&KÐ(‰6˜nW˜úmj4`éqƒ–e>¹ä¶\rKM7'Ð*\\^ëw6^MÒ’a„Ï>mvò>Œät á4Â	õúç¸ÝOŽ[¶¬ß½à0´È½Gy›`N-1¬B9{Åmi²Õ¼&½@€Âvœl±”ÝçH¥S\$Ñc/ß¾õ¡C ò80r`6° Â²zd4ŒŒèÐ8îúØa”ÍÀœÁŽƒ²ïã*ÊÁ­-Ê 9b˜ò¨¬Ìå9oÄ…-£°Ü\nó:9B0Pè»#Ã+rç·«dn(!LŠ.7:Ccž¶O ØÞŒXÃ(ª,&ñƒ«–\"µ-Xì4Œ£¸05HÄ~Ø-âpòâ1hhÈô)\0ÎcêþÊ)øÎÈªZ5\rè¼R0°@Ü3AcrÙ?ŠiÛ¼4ËC:6³*\0èÀ­@6­ˆKS!\nc[7! P¨§#íÎÆBC\$2<Ë•\0:¶-zðŽc\$ÀŠ\"`Z5¬²PÈ7Bê²T)õM´Ã‚.#­ÜÏ0£¬× ÚóJ\n5C+\"	é,éwÅ+ÇÒƒtÜ7 ´ÌkÊÖÀ	#háNÁ)€ár\\ÐåtØVÕÝR]W¬¨PðÜ§Ájf6£Bv<¹ AeÕCxÞISz*9Ž£ÆþŽc5pŽIøxXÏÍí°Â¶0ª\$çP\rÖXÊaJR*ŒãÈØ¿.A\0†)ŠB6\$7ÔA\0Z0MK§oÞ#ŒŒ÷f\n£¤œ3­z,<Ø(C”˜dÀÖ:Wƒ¢v›#k†;C\r]ÒðŒ“˜àÓmS¥e‰ØàéiøpÜóïF¿\"¢ÁöÓÓ¨è”¥jˆäŽÒÑ9¨|É„àÂ\r	ðÌ„CBl8Ax^;ösœ/K@Î©}ÀðaãM\n„K¨ä¹Ž@¾1#µ@D_#‚óg¡Aà^0‡Ïl²1óíí8zÊvÕ±Ž”9IJ_&Ã®je—1¾jÐº=ídµ·¤È \$\no×'ê\\\n\n())¤‰µ’–\\ÜAUk¡¨ó¦âÔyQe È‡5ÖÉYO%äÄÉ#õr‰øtF¥07£î_Z»…)n ô)#ìR\r!4ŒE*ò0Z¢ëlà€¾2‚@a¤1¹——‚‡‰Ñ<'ÇÐ¦8óì°Ô)&éxApæ_‰ók\r(yó˜ð@Ì‘#ÄÜ‘BéˆSX ´“À@˜ÝF\nA¤Uô¿tÆ(‚¤`RÊBÎA7)#(‘Ú(Që'\0ª A\nF\0ˆB`E’dL7¤ãùÑÀa1O]ônB	‘	!bÜ¤ù©”A‘ã\0Ö+æ”FU¶Ž© P±É ÒG0æ)B!p‡\$nÐÆ IX1\$R»f«þ#¢Q“Ìa™Ø\$)%I¨·\rÐ r3Mm¾åæ×|áDò¶Q’í!Âš?K¡Ì†š(MÁÓÛŽ\0û¿—öûHÁEHm«¤b\nYh\r\níj2Ç² w•Y-8âE…Bí‘HiI!–PàÎ’Œ¯Sa9FX4`rð]d¥…¦UUGØ\$¸“ËL¸˜ÂZ`A«-”ú‘2N¬i¦-õUY|ÄÉ,‚/²\\6bÞM98ÞÕ&ZªH|†IóÂ–ªB)-_füÆH'Ò€‚ˆ.¶¬²ÙPrž]§0Þž¤frÂ0e\$ädßÖöO\n	oO	À2'tæbŒaË	eº7,°Ì÷ÎO	l¾ÅÙi“r!.LÙ5se¬À ³VrÇ˜²†ãí\r£%%ÅôÅõŠ¬Ã|G4ö ž@«-8t²äòÌ„°]c¢ý”cw\nÔÙ¢RÃSßPž2‘Ò?\"ÊJN\$wX™˜‚U%ù5|á>Cô`‚¸eW˜…FËB®ƒß>(J2ÆòúIloafŠjš¸@IÕŠ'-	‘U²cL—=/äÊ\$ ¥kŠaCœ(q³ÖÜ!\0æ0p[H½˜²‹†š:I”ÑäØ]0”âÂ¸š#aRÓKçÅ†ÇÊ¹ñ]8\r’€6†òlZ%	¿*\\¡¡ä\"ŽrP¦\$<”ÃåäG‰åÓÕX}J02ÖÍQÑ©S')Ù\n¸Æ¥1ŽœïùÒM×g*‹¹¬ºMÂeÅ)„r9êSM|é‹°Î8Îs·šŒbJW)Û`Ll°†ªÕñN|ê—G\"‡ô‘)ÒëKbõ°ùí1o\nUÙ%á‹xoíeŒ³â–Ø¶p¨îî…ÔáÓTÙ_hM¢Â·‘qH:©U´.nŽ©HE\$BM–ËZïbëý‘¡tÆÃ¹õ2´ôÜ…+X™„€íŒÚATBˆ.T²÷ÂÈ›u(E2¡ªÍWº6ÌPÌ1:î3ê¥Î e„(þËýàSJ{–ZñDÕ=ŽOÉUJMeánÕºŸ`öÚ…ZtÔ;é\r=¡ÒÂeâÄ9SDÉÅtîÓ5òuLTÇM‚-yØ<u3ò­²ß?)|ÛA€í.P«Ë\0ÑF2ó™ó(”ØS\r‹-M„‹Îy›*67%”ÓÏeuÖ:aN2÷©4/0êýOm­ÍÉ*‚Éý€íÏúÆ‰\\ý…Tv>ÔÀ»)A¢¶˜4¼€!)Ì;6il`ƒ_|æœï¯øËôù)©‘÷À”¹ùÛç{ñÊªTâY*']æ½Å÷ŽÓæÉ–_«€©uO/VN?Yñ*«Ôz_5á0¯õ§/È*¿V•RV8Áþ¤\$dBHUÂ¾ÿ,ú¿‰¼ÞVAÌÌ©½a]ÀPG‡n-”¥îrêÁN=ŒNjª›ÜYAîÓ‡¢jï¿Kg‰C=ÿ“›¹<œ‰–>-Êl þ>†vlé‹Õz¶Ò›\"ÿmr¶@d&\r€V\rcÌ!\rÞ.‰j‡\0Ì4ƒ†ÙcXkk².¼n„2†é\n€Œ\$©ÅP\ræˆâRÿÌ0\r(î7ì8¹\rlÅë¿\$PÔÂ¼Æ.f6Nnî«¬Èƒ–8¬?ãÄß#Œ½¬È# 0\"[&?‹ìÊi\0000'ø‘	æ9‡-\nƒ¤A€JÀS\"0'ÊÞŸC¦ìN5Ìì›Ìà:CÞXTÆLt>°ÊÍãÉˆæÌf6I¶ÍÉ¾:Eˆ©\rÆ(O‚òðÚ5Â‡¤\$Bf2#Ì‡ðü¤\rêäàîŸ…\"ª­Æ°ŽÆƒ–	¬|ÈÛ£¨‘<¼„ÈÜl,²\$\$'oÆ¤þ\\±-Â 0\0‚-©ˆ¨ ¦\\\"î§29E’ÐÔS`ol°c\$Ô­[‘~rI9ƒ\0ðÏ0\0¨B\0æyÅ~\n»äÔ÷B.\r ";break;case"pl":$g="C=D£)Ìèeb¦Ä)ÜÒe7ÁBQpÌÌ 9‚Šæs‘„Ý…›\r&³¨€Äyb âù”Úob¯\$Gs(¸M0šÎg“i„Øn0ˆ!ÆSa®`›b!ä29)ÒV%9¦Å	®Y 4Á¥°I°€0Œ†cA¨Øn8‚ŽX1”b2ž„£i¦<\n!GjÇC\rÀÙ6\"™'C©¨D7™8kÌä@r2ÑŽFFÌï6ÆÕŽ§éÞZÅB’³.Æj4ˆ æ­UöˆiŒ'\nÍÊév7v;=¨ƒSF7&ã®A¥<éØ‰ÞÒvwCù»ÝN¬ A¹g\rÈ(ªs:èD®\\×<˜¡ç#Ð( r7œÏ\\±…xy¤Àô¦ã)žV¹>Óä2½ˆA\n‚¦ª o³|­!êà*#‚û0j3<‘Œ Pœ:°#’=?Œ8Â¾7Á\0Æ=(È¨È Ãzh¼\r*\0åŠhz’ã(ßŽƒ’ì	ŠË„\nLLXÖC\n\np\"h9;ÉŒ3#ï8‘¥#zñ'(,Sr1\rØØ7Œî0æ4¹nhÂº¹kãX9 £TÚ(\rãXÂ˜´HòÜ)È#¨ÖÂ#­jüØK¬…ÀƒšA#¼ÛD¡í¢M¢td2È‰Œ‰3:!-C&NKSÔl¨îµO3ÙxÃ¨Ü5´ëp‚Ž?£\rs(Tã ô‡¨Ãb†óŠcxäÂ0ÉèØ2ÎÄ(Ç/H«¨èÃ¥#«ü„¿(:tÂH†7(ñØ®ž#:‚†%/ãü…À£œõt:ú‚¾PîkèŒ¡\0¦(‰€P‚:©Á\0’7l„BàCxè;²¯`9Ïm)EÉ¯™3>Ìs.7Ks\"]»Øž*¹d£FOmŠy2z:TH@äÌ¢«80Ãh‚ìúÊÃ¤5,ÕÀP’6Žu¶\"§ZMŸ”â…›ÙK“n;0£ÙÄ¡™eàôþê¤+®\r’Ñ3Í,3dÑ>ÉZZ:ÌYÅèÊŒAÑ:#XŒ3›£¤ß	jæ ÔÁêâŽX\\íc&Ú«Œ»†³2nežë»ß+¶÷G[óYÀK	YÔ<><ñ|o·î<¦ûËnÓFñ}s}xÅ¿üEÂt±®›	È~v‡’èî)ÃZ b˜¤#ØlãxãšR0¿#5œ6Ž£`Ã©ôêmuóòW¤©A6Ÿ¡ÿÙw(Ò\rã¬A[Êè9µ\n@@P&ÉEþŽ»Ÿ»zpm¡5&Æ>øIkãVK¡p Ð—Aä¥W/\"WàÀaz ð †ƒ è\"\rÐ:\0tÁxw†@¸0†GrÎà¼8À^CÁ\rÉ­Ü‚ðDkCg¡Ò…ðÄ÷•¸\"ÆÈ4´…¦ÃÖ€<á„'®ÃpÄ®%˜“èTÙ}Bäž&Â”A	cú/„¤š—‚ôcÃzkè}\0¡Š.á©ü.qâ= F¾¥ \0P>¤¼˜“2ìµCri\$¦1\"¶|ÀSYô.Lv13w(\n\n€)rÉš3´&Näux!•á†—ŠJ‰£ `/•}4\nÏ‚+w\$¬ž“¥ØOäé\"Eaˆú*_Á\0Z!Ç,\${—«×]Ì“ÊWëCCÑQ%lù?@Ä„ÉN‚²x47`êÝCÔË34¹¨ ÝcÀÁ¥4‘Ù\"§‚	oŽLÖI©`a1ÃxAKw#`¥?è’™\ríx¸‚\0Œ\$UT(²*´âœæIq6	a°:Éi¼ËsNƒªE€2€Ú(W^„8CRI;MÁº¦My\nÓbJ[´i'\$ã J ;µt®÷¾›âXr¨M\n­Çð·Ê@‡A<8+ð®÷ç›¨f‰¾¢~[‚Z?\"ñ‹ƒ¯e9Ü7G½¯ž,Á\0gb©ÝzžC^ ³%'z»D<èŠÓXòíš)°fuŽrs”=æ £]AN1Õuž©ÍO¤Øj¬–#);'ý¢H±±zúPÚ+éü¾B2ÔhjpÍD‰xc“¥A3;%ò@­ÑF·¤d'¼Q6PÉ‰îYÔ®RârÑB¡®Zdh‡”UÓV‚|O\r(¬Ý¢E}eQ)ß¯ÖVÙ\\ñˆ-eåMF¨ó„tÚCµ¦’Ò€ÑƒpdFé‘šk€¥ô~.tYÊô¼Ê!±QGŠ·º‘I]'§Aæ¦½é,aÌNxøvó¡: žìÃvF§¦(æ”’‹Ý¶ªÑí²ƒßk(uDÔ†el®!è1Qñ!„Dýˆ+zhN…=‰(ØŸó_ä~©¸U:³ƒ\"«1æG,œµ•Ì±6yL<s€Ÿ®f%­ìÛ³ë•²á“u9Œ¾8,ÌîsCèÍD?6NàÓ›Õ`0YÍg\\Ês<‚ÏD%ö\\úCÈþ€ËjyW<ÇY\nˆÏÖé>´Ñ@šn)hbjSÆž}zƒD”ú7Ë‹Õ9í€ç}E«œ…Ob÷fsFÈë‚ ÊY°Šª®”šg'Ë£øM‚ÑJH•BG>£ŽÒ¢·Z¤™üƒØuLÙ\$OMex§”Ô2SpÅ%ËŽºIé>+¼I·‚A!#,ä†ºÑ[êGfÁ½CäH0k:AK8Ñ»-Š©à#éÅÔc<\\øy)pj:“àÓen96áŠzßYXÙÆŽ³Y&×d¿MeÊ\r@âs†_ž[Æ9<§¸ r»õÎ9õbk:ôsZ™Q×á¯KÙëš:@{«É\rRï§…¢¯ú=NYýHØUõžQê\0Áï b‚›tŽÏªÀß`aTïåJ( ¢W­ë¾\nPõFC¡	0NNÊõøšæ½XÞýËñG.ÏÙâÈ:9±ßùÓýÆ%ÿdü‡ŠÒÿžÚ6¥&Òu¡²mm—3?!ày™Â€mqšxŽ5ä¼¡¹õ~•ú~Ñç8®Þ¤¡¡Ípã”/®ò;Þ\\ñ£ùæ¸ÐZÊ\\ãŸƒÆ~»Gþr{}% F¨ù¿—}cñ>ŸÇç}§åpÝ*©%.OÖHú~e½WèÃ¬1~ß…øýÅ\"þxk÷t3Øw.*¬2¬¨ãªÇÎ¨ê’¿Â‚{ƒä´ãð)B^`æªæ8BÖof’!ÂZ\rÄ‚Z?,F\r²4jQ%¨©\0Þ¿§YÄœpÂØ>hÆHÐB)DÄ`(N@‚ žîÔý,2ýƒtå‚ˆL0o\$R«Â£YhÂ9€ ¾£\"¾ïºç/4çŽk	àäQÐ¢¥2õá\nð R\nŽøNbÿÎŠ&ÀªeO>Q²¾Å\"M¥ÖÈf¾pO„%oÞ³gÝI.òþñnÕ'¿gDúíÇrŽ¬\nÓ0ô%¯<jgc¤OVŒEÄýr!àÆ=€8Å®½ŠE§¨1 ]çÎ@¢Gv`Òªln¥¹°îðo}Ìu\nn‚úÏ;¯ÏèO&ÿï;,|Vã”9‘3~ÇDÛ\r&¢jq†È—\rGÀûq•ÀîžÆ²5JªJ Ö&†·Q`âP¾€c#ÃO\0ö€æ±¹ÑÁðò±N‘»iÁ¦Y±ìM¤(íï/ã¯ûëÐâ±ñ@à=HÃ1ÿN(}Ò6Ï¡/e!ƒ ÃÒ(ç1|ö2ÂjþÃ’2Ä±˜´ŒG\$kÈ ZÈQ’äª>ì–Cb­!ïþ ªh&†Úïïs'˜ôîS\"ãd‚àæ÷…ò-Ñtûƒ¼xp:HkÔRÃ63²šÚ¤\nÄíFT%\$¥Äùˆö_-q+&„\r2¸JGÌt‚…Š`2Âlƒ6*£\nTü<šº\r-ÒÔ-Ž:KdóÊSÇSÒúË²þóo®üÒÔ\\¤‚\r€V\rbª#íÒ¬¾\"N i:9\n>¡ŠD11TŸòÝÖ\$Ð\"â\"ø!ÑÛ ª\n€Œ p&Ìàâòv*:iGfû3ZË¯ ìó`ie(ç6¬Â2ÐøpsbëëŽL,`'\$K¢0Ê\$B:?/.N’\$PŽ@Ì`ÃÌƒ2Òß&ãî5‚DVâJ9N5(ÔÿÂ>ƒ ëè*€d‚Ï\"@ë®\nQ\nóæÄJ…+Dxë=Îá#¥E§6çèuâbí(ë@FøB¢‚ZˆŽÕAT^Ê&ÏóxæÂ‰Ao ³/SA+µCNrìê&8E¤ãçgCÂü`#R!ÛgŽ=äXMÒÖ³ÿ,'):e|L ¦\rn›3d\"ê{éÉGãoHPF'JciQ@Ôd¢ÜH©†(-¦\$FàKÀHãÅ‚0À‚±.?-à¦xgŠNòxj·@Áy1Òè’âÓj?„˜²4†®”Øq,6”ÁQ¾-Ä·	v¡`Ú«fn\r¤TE„Ö-ÂZ";break;case"pt":$g="T2›DŒÊr:OFø(J.™„0Q9†£7ˆj‘ÀÞs9°Õ§c)°@e7&‚2f4˜ÍSIÈÞ.&Ó	¸Ñ6°Ô'ƒI¶2d—ÌfsXÌl@%9§jTÒl 7Eã&Z!Î8†Ìh5\rÇQØÂz4›ÁFó‘¤Îi7M‘ZÔž»	&))„ç8&›Ì†™ŽX\n\$›Žpy­ò1~4× \"‘–ï^Î&ó¨€Ða’V#'¬¨Ùž2œÄHÉÔàd0ÂvfŒÎÏ¯œÎ²ÍÁÈÂâK\$ðSy¸éxáË`†\\[\rOZõƒ?£ÅåÞ2wYné6M”[Æ<“‹7ÏESž<¡tµƒ®L@:§pÙ+ˆK\$a–­ŠžÃJ¢d«##R„Ì3IÀ¨4£ÍÈ2¦pÒ¤6C‚JÚ¹ïZ¤8È±t6 èø\"7.›Lº P†0ÃiX!/\nê¹\nN ÊãŒ¯ˆÊóÇBc2Á\"ŒKh–Ãa\0„Ã°ªÜDÊ•E¬+?ñ(Ó®’Ò}Êoô£,EÂ+œ91âjºLnRÉÌòÓ^1®+Ì¡EÌJ½8%­‹Î:Žƒ¬à1,[å%JôkF±‰‹•CTE‰ÃxìŒÈ,ûh‡\0Ä<¡ HKRÔèJ()¤£,øæ±â0ê¬ºðJ( ºµËb\n	°ÇM¸Ã‹†6XÈ8@6\rìrö'ŽPÜüCc:9 Ît‡%\r£Jœ§iê#(HñQˆ.³±[\r‰315è›7FÏŠz¤˜¢&2u¬€¥\r @ ÑŠ„çŒk“òC;¿8±T«–Í2¼¯@I¸BŽvKä!®´¾:: 1¨‹‘ä³+0Mˆ¥‚4µÈÚï	#j<„1â(ñ›°N6@•¥·{\nR¦YŒ=9CäÎÔQpØó§(=»?\"mŠBŒ'#z€ž/û\$:Œh2@3©5˜»²Acf9k£Î0¯ãU„2…˜S\rãZPb˜¤#nIJ„õ©pA7%NR^º\r»*X¸µKtYm'‚@¾¡#–§ ˜J9ì²n-“'Í×R²HPÓ³ž·AœÖ®­çi¯AµfïË÷BÍø·+Ó‰¬7:Ž°Ã˜îºTªààšºœPx‚\rèÌ„C@è:Ð^Žÿ]jh8\\ºázQõ\\é@ç>…á|9ò?°/Þ	èÖÙ¨àkÜ¸t€¼0ƒäÝR9?T¬ž7æäëR'¬œc.š}Ç`'€Ð€\"/ÈºÐ 6ÅÐÁB PTI'+ÈLÅ‡2<‡‰ l'½€± ÂºŒ¹xÎ†pNÎˆ©D ”2‹R©ŽfÄ~¹å¼‡â¡\\ Æ-8£îKOÒŸ:H€¥R N€O\naQ#¬Oz€8ä6â’d¤,—âxOˆÉ¤ q‘m4ôœP9¸˜0äáÌgFŒÒ¯3þÿÃz8Áˆ»/•ö~CJñq €#HR½Û”S!<ü“gF–ß264ä‘(2zý]Ña+´Ü§ÐÂp \n¡@\"¨eüÁ&YŽ—‘dm9±[©Ä¾”B,b‰rä…Èòàäþ_‡Ô®€PV/EÑÄ„ç\\úYiç#ââêZ³j†°a‹2ˆQ”bžYAÀ–:Ÿ@I!- ‰ÁS7\0ð	Gm5±‡ÅˆˆÔMÕ´t@IÏ\\ñ0ÎeXÏ£”Êè¬òd\$´7)âÑSSK))I&X³]1EÔ¡C2Òair\nÄÛ °uÍÊpˆb¥\r7ÚÌ¼'…%@\$¦Þ¾o¨õP ¨’ôYðo\rüëµ‚ta+¯Liy³fîž•‘	€) ”j\$ÉØñˆQÄÎ“2±ëà­ÖyÍBò†ŽÄÅ_©F6F…m‹lRüº¿PÚ‹Ô1x.V9KexLSéÊrÍ*©‰nU‘¨T²¯Ô9+¹óhZ\r« ë	æ²ËÀk=,îó´HÂ\rrä¹,pbà\nm\$‡9#\nTÐ¡N©t\"‡\"®^d[¤ièÕº4Ä–ÕR¥»Æ€ŠO6¿vËò²Qì§Þ#=wÈfHê\0\0›ÍvŒéXp´0¶«»{ï%ñ¾qtŠÁä\0s#úø¡u:õ3ÒpÝMç¿,t“ÌèeÖ¾p2ß\\ soÁ†\"aÇ\n‡Œ/˜N0Xpœ3¬.qŸõ½ŒT¦	T`¤‚`1¥,›!£c/Kq¸'Œž4¸™Pv<(ù-Å‚ZÃ(bAvïÊfÊ„\$äÇ2inµ›#çœåeì8Ió			.~—VUJ-=&.¢ö¨o%=…æ¾çƒ@ÝXÒ–Ï¸\0Ú\nxâÂ0fg·A¢¬€QÚ\nÖ}µ‰^Bš\\ßX0Q’zÀ%Œ\"ØÔË–N|çWêvh›€AªL3­kØ‹>PQFÒf;N¿d,’C—sTŒV¸ƒx)Ótqá2ÝH‰Å(¦QPÎÒÔY£öeŠšclm(ÛGõÔ>ÓLþ“ÑM¦E+Ã\$@9êxº}ÒÊvå}Ýèp¨úûh×\raÂX2ýÞËþÓpA¾·Ý²ßœ-¿MÅþ½Í4ÙåPPfø56˜7KŠ»nã´Ë2q[Q¤·âV–[qçtAYšu3Njãâ]e[û+Öi×³Æ¹ @s(ÆÍöÔ†ø™&§ü-‚IÞ	6[¼òä7ÌéiË#7=bJärè—¡\"qÈøäuâÖ§EÖ6Yfim×f>ÏÒb[Ó¨VIdzéBqÜ©¯Ï”o¼_ãÈ©X R&ôÜ¶Þ¯§…`%@ëÍÝáˆÎñxž4å_ ¼–ñkô‘+4QQ³ X]-§o\"|_ýó8×-”_³øË§õ‡S×\\ÎA½½’Âö—'”iŸ'ÚÉj¶WbÎ12;î®G›Ü‘[ë/—ñî?µTzù0å˜”«LZùy-,a†*—ñ+öî’}ïÁÖ}îØV›ð÷O)¦¾.e¿³¬ù‹ dÿ¡+\"ŸSÄWß¨òCò†‚ç¯ì9Cžœ+T.ËXüo ÏnêñÄ,¶NÞå/~‡Â[ËXŸ@V3†¢Šo¹ƒæLDÈPªBö\"p\n°LP[/‹¥ïÒ¯ 0ÀÐ®€Þ9ë¼È¶¶güöƒŒnŠnlL¼6f¥KÂúŒŠ‰àö.\"Y…¦nŽ¢6ƒ/l²1æè[Ã6áÌ&ð—ƒ\$à&0ëãÎ\r€VcÖY/!DËÀÄ3¨úúÃðÁbNÎ€Â¥˜*iBJ§\"+ÅLJ¦â\n ¨ÀZa#â¬ákàÎ+zq0–êˆ©pÀˆ,)¢8C'Þ‡&ªOmJ ,¬nÔ1€ò®ƒ\nÕB)DLJ¢2=`Ü\ron³§¡‚ôs¢X„mjatiâŠ.@˜“1;±Š“BCpð…®à¨ätORúnÊÄE_Ç@6MŒS1°!&öuelDÄh Jìˆ¤¬ã^6N60†þa'žz1ÀÓ£\n6QÈNa.÷#~AQÚ¦þ\ràà9åºQåDðNæeÃbXÃ29Mœé A!\nÔ\"tœm~e©ÄÙÀèÍpi*:Â2I@ì3±PáfXd\0—ò° ‚/\$L1ê†.¦78ËTVæ&¡”<ÊÛÒ41*\$ž`áïÖ¯¾ý¥v~¨íá\0FjCpžï`mÄh";break;case"pt-br":$g="V7˜Øj¡ÐÊmÌ§(1èÂ?	EÃ30€æ\n'0Ôfñ\rR 8Îg6´ìe6¦ã±¤ÂrG%ç©¤ìoŠ†i„ÜhŽXjÁ¤Û2LŽSI´pá6šN†šLv>%9§\$\\Ön 7F£†Z)Î\r9†Ìh5\rÇQØÂz4›ÁFó‘¤Îi7M‘‹ªË„&)A„ç9\"™*RðQ\$Üs…šNXHÞÓfƒˆF[ý˜å\"œ–MçQ Ã'°S¯²ÓfÊs‚Ç§!†\r4gà¸½¬ä§‚»føæÎLªo7TÍÇY|«%Š7RA\\yi¸ÏÛäuL¢bû0Õ4à¢\$ ËŠÍ’rFùè(ªsÊ/‚6¿ö:³\0êž„\rëp² Ì¹†Z¶á°­«ªh@5(ló@œŠƒJBÜƒ(ÌÀ*‰@”7C˜ê¡¯«Ò2]\r¨ZDö7Ãœ C!Œ0ëLP¼BËB8Êú=ëìl&3ìR.)É¨<l)¡ij’Í¾ñ9C»i[]1Ï;Ç1xŠèÆ¬Ø˜7¯ãtF9'£rVƒK­¨Æ¼°)ƒz¤¢âjDõ<M0ê:±¨ ˆ4Ò%©\"7CÑ(]õPt,l'\rêü„Ò½KÐJ2òƒ4ýCQ¶ó¨Ë;º Œ:¬%<Tˆ,‘‡YAˆ¸ ÓŒt³6š0I¢\rˆ	ã”tÊC£F9¡NˆÊIŠŒèÎ*\nñ ÃÀè½5ºÄUãbRÅMª,1§Ñ»èŸ*¢˜¢&2£uœƒÎhàƒA¾Š¦1¯L[Î?c)DQN¨…0Ûs\$ùHa)ƒoa>¢•‰x¤l\"ã#¬¥†ápÇ=ÅõÚl4ˆòÿ‰#jB†2‚(ñ—¾¾.á±ƒdl1a™Ê‹ã˜Ö÷U“ÈD¹C4\rê\n|<ÀL¸ê1¡IÌ:¥6,Ú9…ˆ(å¨#>•70u@¨ºh0P9…0ÀÞ5¥a\0†)ŠB3È–(cdB:Zê£ƒ2ø6ë	zòØ®Ñm¤ã)+{P´12É7®3v¾7ñ“0‘ÖpàÊØ6ïƒZÅåš’ã6ôrÇ™³QÈÃ®åïãü˜kXð[\0ÔÊy9Œ©ÈšÇQI9Žëå@²Ô‡¾‘ÐÑŒÁèD4ƒ àáxïí…ÑÎÐ……ËàÎ¥%¼•ŽsÀ^8cÎ÷úBýÒŸ\ra}–Ž\r¯:xÂq¸E¡Ð *¾	ëp<Ñ7§æNHÉ\rU>„ðFòFHÃ ’H®A\0P	@‹9vaAX\$¨³!S\$Én&Ä œŸ\$æPúì\\FtÀfòU	9\n'PèÄÆPŠ!Fƒgv2äDSÓRD(<98¦ßÑ0?¤Ü˜ï 	9\n<)…DÈb–Ù=})”tjv]è HA:âbpÖÉ5`‚/?³ÇÞQS.Ì5€ä>Ô´;#ˆ<£X»q»X¨æ>*üL?Œ ñ·ð@‚¤\$^’'È\r9<Äœ9†k‰:4Äù÷ºÒ.À1¾‚á<'\0ª A\ndKÐˆB`E˜da\$&Cž‚—™©Œ(ñ†( ëC\$„Â|P@p\\Jäc_hpG!ÉÎ®çö×’h§T8rŠQÀR~•‘ñ;ŸYô°Ì[Jé!yX˜bgíÞ\nôåT8 d)Š…'ÃøÃL|MŠˆ%DìžS=¢¨½‹â`îd<¡,9¤ÂÍÉ¼u(¾sÒ†IÌø\nc†(íbê :€1ñ@CáR‡”AFvBB°µdl`¡Tð‚¡i!Æl!†öu\"¦ë91Çn­-:ºË”Ó£Ê¬ƒEFÙ‹cðöºyÊg×;¬Kºh#±†#NÈij¬AIK`lÉ/Äö:\0¸ŠiPóÊ	^kÏ;òÕCBúßxr\"êÖÁYV¼_­\n\n@Ÿ'5ˆ`]:fn`Êw‚3ƒ7Ô7*ÆI9ÑEáŸ³jM/‘4ð§AÈÓ–e!Q2‰FÊ˜Ñ©ÃN©UÕI¦œ‹ÕÖ¥tŒ2«Pn¹®ÝCIvÉf=é”\0›½tcAd¼Tñ×¶cEyÍ2\"7ªžˆ0OÐÏN%Vu#[Â¯O9T×A¬F†2Jo–uÇ>æ^¸ý{pYÐ¾8‹ /›®7\n˜¬/l±	ñ”®ˆ™•âôþ-¤Ša¸´¦¹ãðº]-ô+o†¤\nG%Z!3ep¦ò«R¸ á”1@Q„‰†DÅÖ¨—ž\"»™H<d,õ§éfL2Ì~%W1„„š0Å™}`P£`ZtuãâæYÊü)Ú/FPéµ¿¤ä'çK¬àhÅD:è‡Wlú¦ïAXÎíÜó7º#^Œn‹%À:ìw¤Ö‹0L&ˆA¦“‚£+8—Q0‹frè1 (£­ó75qìó&¿*cÙÆªu=\n„\$¼©Ö	Ì±VzÈ7þÄ ×HF›RIñ!¬´GEnÅ¯“DŒM7Ãx)™ˆ¶ˆÑ6É\rÆ¢£M‡KmÚEK74nåî]=·™õÜZ“wQ-Ï¸4Õ‘Ü´–Ò½ÁœÚ@›ˆ0×\rßk—Ð;Óƒð?Á7Õv²äôÍ…+{¥p=ã7·–ìç[›~ï[§<Zßq›éÇ/¾7‚þ˜ú#eÍ…š–•Öé®\ry|°³2ÌïsL\rÄ4ç8ÓÜÃ¨{ÍwšJÓ¸Ž¯M<Llûq¢…šÇ­²O*–UE¡Ì£]œ×Pë 0øû\0Øyå–ÓDé•÷2;îNq»WæûÞ­ÖãgaË}ŽV£~uÂ:5œFÚƒoöòôŸŒ”9`öQV“	q4hˆV¯w l¾„<•@àœßù“Òu/8ß„ÁF®<ûpÏ6Üœ6àÀ*}g©ô²|†ÿ`Ž¯¨¸WÜúÜ^XEj¬\\…ºåG*›ù½zÍg¥V¸¹õTsËòN1ë÷¯Öú^ÏNï¨´¬+°´Ä€¤2Œ¾}çßV+ÑþH¬þËÔÛÙåÛ`0ÓG«ÍJ„Ð¯b\\úmêÿdNOüÔ.éO/\0Oúï\ròè\"¶ûÂ,¯ù\0Žðý0#p\nÎoå\0o¸'Qb¦›ÛbôÏ«<µ†óoWP³ëC­—Í?W\0íKÄQpR´²kJñ°kC6\n‚›t[ìæK)ái	„¶pï°àð E-á@	\r‚ú\$l»Ç'çðüÃ6TÉêü¢FÐ\n2QFÐ½‹°ýÊfÝ- ¢ Ò­˜FåŠhÅLî¾1CÖ(TÂ¤4.¿ÝH<¢ðæå†~•ÃÔ\r€VfôXB,\ràÄ4hîþƒöÀª’:àÂ¥Š' Œ“ˆ@1†î—À¨ÀZT^Ïl0\0ÎÁN:ÏíÍ«häKÌåQ'‰Hf‘3‚8#Â@\$GÒ\$âR3\n?DdÔjñ òH#Öâ.üÆj\n¢¦ÐƒÆ#†ô\rÀÖ:Ã&ÖlªbñF07ñ4ï  (dÚFÁ†E(ÂôMdsJ ’‚z1@YìªCèùP”#pÔåÅ(^ÌD+r\n1c â È€åÊô6£pïãn&\0y'–Pò¬R %Fõ\r¤CˆA²0¦â\ràà*b¢R; ÃœóÉÈD\0]„ô¡c#òö­\\›¨òÛëŠ[\r°a®Ì°E¦c‰ÔVâ8I€ì4q¤åIÊ%z@e‘%Ê´`ãÐƒ(§ÂûÑ¥ŠEa'jŸq8&%ÀÍà¤dúS0~« á0‚\\´gÞ;\$lÓÄh‰pÙÄll\"ø ";break;case"ro":$g="Ed&N†‘Àäe1šNcðP”\\33`¢qÔ@a6ÁN§HØ†®7Øˆ3‘ŒÂ 3`&“)Èêl‚™bRÓ´´\\\n#J“2ÉtÀÚa<c&!¶ ˆ§2|Üƒ“ÊerÑº,e œÎ’9¸°0Œ†cA¨Øn8‚Œæó`(Þr4™Í&ã\rµ†Ž7FÔœÉ22N“*´Hên:†”Øe›‘L†œòF\n\$›ŽrÓ.Y‹ðø˜þhÇp–šfå“|XÐašÕMð[ØÓ3™Nx­™ÔáÁâ|Y‹7)Ýf¹àW\$ÙË=HÄ±Œßˆ¹zF\\ž.aæì.f?;ÑAôçb	üç›ïúL„Æå¹(W°Qp2§`Þ9¥DÚÃ¯Ë˜@:ŽCjF:\rã\nÇ„\nÃÍ\r(\"–›§*šz/ãRN¬!JÙ›ŠƒHá ‹üƒJû.\r’B#“ºˆÛ­¯ ÒƒÄ	’L9#í\0E#‚ô¹Â@P¤2™Û:ã(Þ6Œ££PüÄ°\0§0§îjh–ÆÌd¶ñ“·B„\$¶<ø¿0\0ÌËÌ¸˜7Œð’d8%©øÜ2±¸¦Ç!«ä É2%/²Tð	£{&”¤OJƒ£Õï@Í64Ó¬SÇ%àRîÓ5 ÜÖ²á(È\ròTºÖÕÅu*33]9‚31Ø‚3 ô\ni7A-Ëƒ¿¹«}H/ã›NÝS±¤À£Ë¹K‰;<#ƒ`ëeY3\0XÃb`âÂ–ðÂ79¢ˆ˜#ªZ<Ú:%¤ã\n7-°L½º1¸vÀæå<ì½L4ÓŽ’ö¾È±ôbøËÓo\r,Ê!¾.“g’dÔV!“\$øØËÏ,âÚÑ‹•‚\$ÇöÓ.\"±þŠ½.+csÆ=›\ny|ÊÍØts.6KÀR\0Ùâ ÞÙÐÃÌÍR£t9ŒÉx@6PxXÅZðÃ@ÁPUz£*ÌP9…)¸†)ŠB3x5Ñ„8@cHÄÛ…Á§/(L¢d3-éú`B©[1´;tä7CKÛ¦RðÊ1]éæé%4Þ7qÑG³3p'>‚DcÀá!°[Xå@\"N“ƒ´ù(õÁU˜ê›‰£\n´ªPæ;­õº±Û ã'\$ãB3¡Ð:ƒ€t…ã¿Ì(›¢l·Œá}÷]”Dë…áTûË¾è¾ã_cXD™àp1épÄÀ^AñJ7ip:…4o`apÄ¨D¾Õ7\"¨Uå¡Wâ†7GñÃnta	Wy GLo–Ó\r=  ¥·”Ò…rlF(Ì·‘&AS˜lDpuf%Bv‰ ÓA\$h|Ÿ”2‹*ÞV/9¸GXáÐ¡Q@f\$Á÷òCQo.Q\r²tCSf\n<)…H8pU''äÄ…Bà¹¢‚\$¬ÒÝ4?FåÁÊ<Ô‘ùÖ\rÄ¤3©u€ÌÑCQ¡¤š¡ æjÍiÕ_h.!nÌKˆ \nkø”:\0@m‰8F\n@‹†ånàÖÔ%ìŒŸj›Ó%sÅÂÕŽ´BX]A\0™”ÓURcž£ìnVxDQ\rIy‚„T:Á\"©q\nHb0—IêïI5såÐ€\nñsÒü+T\"wÜ¬XÔË„4¶¨Ð“ž?¦:†s¤iTë 'J¤Ò˜b&hÿ\$\0‚0‘3é3Ðv=t¿§©â‹¨i…pQ\$Õ ¹rjgÓ6SùzNôQ²†‰\nØÙÒEÇ\0Â+C>~ŠÑ' ÊäÝäá;K„&1ç5m…jM:Ìì\nP(ê•x>	8P	°¥Ÿ×(\\Òñ‚“U.ª—t~îâªh‹A:L2èÖU]CLjŠ²#ò6˜”bº‡Ìú’ÓðÓY*7ž'Xƒ¤ª8”Õ¢1P*VšÈÚÙ—3%ü<*Y’È§ŒLH1Ó™öšÃƒ ±ê Ë„ù?K/ ˆZ_N2àæ„è³îúÓÍ	öAì©1‚êD™ðŠí–Ò9¢Èº#I\$×(Pwmˆ2\"ÃÔA)!Y0øç±ðŠM]dfÈé›Rã<‹Ól<MYÝ¨\"=BØ!ªÞ(šrxUåä\$Hž,¢À›l!L:6µ{yU‰2\r¶l“D€{Øòs.ú”«Ô¬/iì¿‰ê\$˜cug¸nxlt¬_<MÕíá¾8W˜ ]zXð ¶ãaËè‘œÈclR]«XÜeÕzGU%H’Õ[ŒbZG©8Òó¶€åÉ•X%¯Ê“y¤LI˜r–äàÆÌRòD¶Mß:Œššz `ét™D\$+wVz†H‚w‚Š@Ls;+E¼4ž\0†ƒ	U³&IÇ]t ¹UzÍD©!\"EªI–Ð–EUÅã=¬øL³ñ¤\$\nÌÊQÌòK–†˜¹CDÏá‘EilÚW-‡,YaLjî&Výª{û¦u›%lêðé‘¦™Fž\rQN\"fjô†i„!`9ôs¬f©3NÍ8¡)¾¥Ä®Â±f‘jÍK2¯éœh©³;Kçö±¸é…Ý9Š;L:.ê;JéÓ„Îéê9DšU4\"ú®’=]7;Ý0“z9}]»÷C5Ñ÷jm0Ðî»I¥1§0ž	 7r'9¼7yÒ=ügwÅxfWg`½ðT!9‚blt©ÝÄÎû`{Øg8å\$ÙêsL\0žIÅ°ôåîr¾[wðA©¤<™Ì³.d…¤EçuŸlXåÔ½é|ÄÄô]N´:Jéœoáë6aÇî‰ÒKb\\1ÒI*00ŒQ>A5½Æßa•µöGJïOJ\$ƒ»É=³R†(£:GPÃÙ±’&¼ˆ@·ÀÕŠ9Ô:KñésŽ´’u½f,Ö¦³¶®Ð +gì°J“‡sŽ­Áý\n¡ŸÃ¦îÝê}Åêš·ŒxöR§ƒ-õhç¨Âb5W8Ó¸³ÞÝGðxïÕíZè{õÛìÊ\"ÒP?-ÙÎóón“C¥á—ì%ÝH}É9¡ØaŠ äå²aZ‡8èS#}•cY÷\nž\\T‘ìø†‚þœ«ûêN<£öç¯úëþâOªÆeŽžo2Úvþ¥.á\n&¹)Ô@¬¢nS\0C.I¦Ì â8éh–C AV3`Æºª!„oðÝ£ELpQM*ë­ñâ0föoNß:Pq¥J¢G0!G0†pŠm¦ÚFáíØÓÆôöãECdxð²\roæ‹\r´ÂâšÏð¦ÓëZÆÐ¢ôÎ<EÒCðjÕÇ¿‹YdÑ°êµÐE&„ø¯œ@ªFÜFÊ1/†&ñ»\$ß‰\r±Kµ¯hëÀyFJ®pþÃ.\n\nqÍ’>Ç†lCúà‚Rþ*´W°.’îlÙªI¢¢ÑPçŠNZÐPã˜Uãx1b`VÉÇ­¸3înù- Ìkî½kÌ3Œ>1Xê®<&QŠè\"àoD¼\r€Vdb`ÖÉf£H…1\\Þf¸Ád8”ÀÚªH1©â\0ª\n€Œ pB¢B™)ÌÃnCJ•æzL.Äð\$ì=ÑöÀ¤ƒb0#B81& Q¢ü·dÖ¶`„¦˜DX#¸2…¨±ºuJ,%.e\$s.1&HB¬]Â	N­	¢nNBä.€%ä|˜É<<cò“ÌHƒ€<îE&ƒU­@Q˜ëŽWîŽòBì(~ôÌNÕŒÑ–ÂncòŠ1òŽøPÎÚM@1ãv5ãb1§³|FC¹*D(:…>ôEGå aE\$ ­hI„U.#± â¥¥ö rôc­¬Â£šy­zlçM^åäj§0h Ë2ÎTNF.ˆv¬iíØ¬k\0BIë)0˜3àš#jé†¨cK®îfA4C>žÒ®>PDQp„\rÄª°Œþ…˜#¿\nêÜ&0 ã@	\0t	 š@¦\n`";break;case"ru":$g="ÐI4QbŠ\r ²h-Z(KA{‚„¢á™˜@s4°˜\$hÐX4móEÑFyAg‚ÊÚ†Š\nQBKW2)RöA@Âapz\0]NKWRi›Ay-]Ê!Ð&‚æ	­èp¤D6}EÕjòÙe>€œN¤Sñh€Js!QÚ\n*T’]\$´Ègr5„ö9&‚´Q4):\n1… ¨â\0PÀb2£a¸às_àp²HÌÒN…»GìXÊJT±²Gù\r~ÑBß±0L4‡Q#š!®Jn±¡KÃM!‹ê\"Âk(Òà6´I¤ÙìæRüÎ˜µªÑË&ó¨€Ða;Dãx€àr4&Ã)œÊs<§SÂtñ\rŸÐÂ1=‹‘B’6\nZë9ÈÌ’°2&éšTÌ¸mZì‘–Ð‚R­ÂÉ€ëB¨D\\! P¦ð\r#pÏ@j±¹°‰p•NRZ„F)J„Á–°Rj¢¨PI W¥j¡tä¬%Œã¹æ0¦:®\"¢FH¨1s–©SˆÑ/\nin‚±úhØÉi:ºá+Êj¬·®E\"Ô]£¦„3,°ÂGñªÄ®K¤HÌ f„¢‘*Ic‰K[°\\£%;¬ ¤eò2J\$úû	0èc°^\$||›B¥ÒgI¨Ž\"hCªk\n­1PQcäšâ,Î:šSÆ„ê³ÍýFhR‘Ää»HõÔ€„,èÉ0§S°œ/B®ÔCì*5É?JÂ²š2Hµ4»|×mƒJ»Jî\n2OFÉ\r¤”0|h QôJÍ\$R±&#6»‰Q+4«œ VÉ‹€À-”™,Ñ„™	Ã…¥Ò¢\$nÂ}G²v4Ç-¢ZÛºÄ6Z4­ÜéÐW‹žŒ:tš9FT½RÙnSzål5z^×õ`R&)ò–C)¥Â2H%ÐágJèÅò»W¶)d•*\$viZ%U«7ŠÁiƒ‚¡ÐùÔ›(Î2RÃaDr2M©ª{›R7zûD©lÙaRQqµ…ˆÃ¨Ø6>/ûØ‹ŒcÝ\n\"bT–:šâM¡°ù²:ÕB ÔN\$³A(sCF,Ã)x-Ã©d‰ß²ËŒay¢²‚¥VýSqu\\~%\r`\r7£zëw0×¹×M‚§ÒíG_@öKi}-iõiUV,§Ê:ÕHûcˆ–5˜]-š7Ž<A£>S§Ø¥·hPøÔÚ@ŒÆ²L—ƒ åz\\µ¨Ï(mpAäPÜÃ¨cg¼9†fô`oè¤9‚Ãâ  aá…‚:Ëƒj)§à0R\\ŽŠn¦m(”¦ªKC\naH#1¥öWq*]\n#t_ÉÉ%!ŠpA’¢\"Fˆ‘,ed¹f™)\\AâùgCtHCÈ1¸^yyTž\n\n\$FhY),2ÀiÍzÿ(Š‰¨\$ªP\\7ï²§“¬ð\n“)‘Ž.-3Š™C/Ix=#Š\"b]ñí;±QÂhagà9i\$Ã¸oL¤2‡€àCeä½(C0=A :@àx/òÌÉCpe@ºMp^Ct¿â†ß0A}G¸:J ¾€k@ø\$†Ðà{lÀ€ð†|[„Ù?A½”Ÿ A\0ƒYâ\r!ÐóK¨.Cpt…	%˜Î‡™¤d\\’S\nLN¬„F	iHf(Ñžêi…24H—Ù*[…E\r¢FÈî×	±¾WQM…òË¢E8ÈÔ/PÇ,ÚD¦©Æ[\$ ¹)Iü«<XGñQá›ø÷Oivrä°Ÿhß>\\»•-j<dDû)¹6 Ô×›\"¢ŽÉ*ej9Ø´ç_|¥v¥ÆT”—a]8…X»\$¡ñÁ\0P	áL*Ô:ƒÞp…‡#A}Pª––©^­)ª‚Vêá]ßuVD‘h˜¬Šþ!êùª¦(9ZªURˆy9D„½;/v’]Šúä}‰ ì!ú,@«\n;guÀ†è5Ã|¶˜1Î›…˜õž#Õ)‚0T\nÌ72Ó5¤Ôìvì9ðÚEåÑá—kŒŸE*‘eÔ ŽÊQRœ¼#ÙnLAÖ-g3abävñ‰|ÞtlæœëBdõ²]z®µhõô}¥5ËÙ4_eP1OÊµ`T¶ÜÝ\n«v¿„dK¶ë·zés`&%>´*„F4[0ÐEØ§°’m\\Ìú¦Î¸æ:ØŽç–¹gxõ5ÜEL+KÅOÊ=5¸ÔB'È·#5lß>T„µÔ‹Æwì½õØ‡g^–Ùdµ¼§{`„\\M*ŒäãF§ÔV¢.Lª¯x™>Õ!“ÖÈ™aö zò‹ã¥Žn`á¾hÀ´q’ÃÐÁ¬ C6ID{b¢H¢ÈÅÜ[ÁÔà(Ä\$\0[Bh[®½\$H‚JWbÒ¾CÚF\r<Í3#&ðì]êÍš¯ã!F†à&7_Øð‹DÁ¤=Pîpp„z¸)†SØÏÀdÇuß+SHb„J•f¼V,Ÿa¬?Qè¦*]\r¶¨~xiÚ\r\$ÁÉñ´¦U;\\æÆJnÙ!­Ä,¤lIUDùX¢˜†­­¼ë#}P· û­aômø´vŽY÷.8›ÍÜ+b›¿Š+Kª(<Ú†`	9a\$kðŒŠÚ|P^ËI!ãQ#G?/ß#)º!<2jFE+§m%Rþ¸¦€vuÉhÌhˆF{O¬ýòAkÖ4Çw·^9f\\|\rz*ì\"dï©j¹]	š/ÅÞÉ!ìþÕÌ¸åG%øÌ˜ X„Ï¬uUCƒ	oQ\$ÎpºÄ¤vÛÙ·æfâ,\\€¸âshì”‡³îÒÅÝ»µ»¨(ògÝ;·aq]é’böÙÈwu~)öî÷ÜN¹Ù£ÇJ²ß±hûª4*”§¶:Qü'pì^1úßJGRB+¯KºzŸÞIù‡,>’êùX uw¸¾ö½¿Ûçe£è½ÞÕ÷²ßû(§”¾Leu	»´V\nA‰cæ©¼¿6øòPüw`\$\$¿ë“.öËÑkPÿuxb¯åáî'vû!~ßÀ\"ný)À·þÊ¸ç¾!sÏª3\$ì3o´'/ö\\æÓ-À‡¨ÆÑâš5DÙåžJ‡*KJüD(WäžÎç2S°z«Þu-8®ëLŽEÖGDj\$á\"üO0\$ä6#Â M/èñ#¨dËÐÓ-H¨¢å\0¦Lÿ®xâOs­D«|ÔÍ¢|¦€y\"¨/Œ¯æO­O	rlBÖRîÔÎp§	GÈc°¯	â~ÍN†@ðn#kôDbþ9f87-ÊU~ÓcRÛJþD£Eû‚¢Û°Ð&Kê¿ˆ–Ë­ÚÚe®¦BZÀDÛTâîfðJ P¨OâTŠñn-¦ÃhÊ8†|'àPBÆÚ¨ R¿0ü¿f(É%ú[ÎBª¨Ñ†ß‚C¢@.E¹ìÊè\n3p²YÌfm\$ÒwÆÝE»«¡ŒñfÈ§›P‡\$¼ío—.„C§ZÊç`ËJ2GÇ„y£ðl‹ç¿âüéð~'†Ž1¶÷0¶M0º ¨%V[pÄRï[öRîÄåãV`‚@‚Ïm.å‘ÕÑ¢Ùê1æùGÆÍ±ôæ\$\rÏ‰ ÷ Jyo(ö’!/u!nÚÍ„	!ñø\$þQr-r	¨šÞÊüŸ*ØŒ0Ñ‰°‘%««%íñ&K´òkð¸!ÒpùÒ`»\rõ&±Œ[âåÔAåDŒiú-i¬\0GJ%„<Z.XcDþÉq–£)\0iä8Ë\r„LB`NË´s’bÚ±4WÎ@Œ¤æ!ÑVu±)†â¾5B>ô1xã%,ª¤U&\0NÄxÁN9Ò†ÞäI-mõC±,„§,Ï¯&cáðØÚGˆYo&aMâ­¨|Ü\n”R2œcí”¡†¼mš½cU#%S7/„½³LÙ‘›5¢bÉço6*øÙsQ6¦)%2’¿sxçpÊu¯ãsJXÎç³ƒ6âñ70¤ùì¸lEXróXK²0ô¢I‡;3„(1ÂvSy3Q à³É<#‚sÓ…þÍS±=¦+\r·Å\$Ú’Ú!A#,6\$\$2aÂÂbªúRTî¯Ì ZåDs6RB7²G QZ×‘¿BK\$K;1Y5ÓÓ³KB.aBs¤ûÂí±~`ó^à®l Æ|ÍÂhÃ3ÐÛóåÔ\$æGÑ9ÑÊ¢næ®ngã¬Ã3ÞËÄ#4o,åÇ\$j^ó*xâ¨UsÛ!„jøøÍI*%¤Ý¢¤zNª„N©ÑÛHqïjX]Ô¦¢\"™JæBÊ´´ØlìI”¾(ôÓ@ª±K Z”·N#Kô=(0’Ýì _ôÒ¼TïMª[N\n¢%¿tˆ%5JÃ{Qü¨5KÁvÔÑ%L2oQÇÛLÏ[JšâåFmáG´£SÔxCuR.mú.âÏ)]Sµ\\Vâîßâ'óºùdûUõp-n¼w(TU=/´ëK7VUGÅYná1Ü¡ŠO:ó Å.èêYR¤#5’m­“ZêWK4ôGU¹(ÙX”ª­©\\4òØR‘=tP¢ä¯œM|zT0ÍÔGCgˆzìŒ´@1-4âO/A/ ¯-\$2Œ`ïÕa,ûabðð”ŒBƒ Ö\rJyA*‰/+cmcE£)ršuBkÃ’\"îŠ†Q`¶%\nÍ#\"ðb¦’7ˆù%o vtÍ(Ãb6=OõÎ–‡\$ï4gô\r€Vß\"šTF&Š®ÈØŠH(,Êwheö‘Pjh@Œ·©Î©&\0ÄK\\\n ¨ÀZ\0@˜\0Æœ„RõJqòükvW!Ž¨ŸåC.êYg°@Ž4DÛdfRëÎ¶p”Õpõì8·ë.¬ždéVlƒNŠ*\$íˆÕ/*J%f5	f:Ü ›nÀÌ3PF¹4Â•2U(VMlD6L&mjå+k3¢ÂˆÍ:oPl(0EØÆ\0â\"×)àAM5vCTæSôÍÇ‰PÔØ!÷^SÀ˜¶ Énà{kd\r©4<Cì?ÇèO6¡“ ²,«ÐÚ|Ì—;p\"»bhs‚Á\n—M\rÅ~QtÎDvÊñ÷ô^\n”Í6úŽÜÕãNisX\0@¨oÃÞ;ÃÀ<VÖ˜\0@\ràà‹ŽÕ‰E€7ÞÕ1'!´%9kÊáOI2ð¬8Ìb.uF\\îxVD¬Ñ°v.­Øk%MÄÛsÖDX!4+Òræ¬§w¤ëà¬ Æ ê\r¸d33ÚmBerqâˆ¨¬û0Ue“{elU`0?\$\nC`%Ñu,SÌjÅ¦ŸoÊ³ŒÄÄŒÉ†ø_u¤Å–)˜\rv£ŸNµ\$æ)Mè¦½õx8Î0ðÐÔÏq…ˆ¯EZPDàt’™.@aB";break;case"sk":$g="N0›ÏFPü%ÌÂ˜(¦Ã]ç(a„@n2œ\ræC	ÈÒl7ÅÌ&ƒ‘…Š¥‰¦Á¤ÚÃP›\rÑhÑØÞl2›¦±•ˆ¾5›ÎrxdB\$r:ˆ\rFQ\0”æB”Ãâ18¹”Ë-9´¹H€0Œ†cA¨Øn8‚Ž)èÉDÍ&sLêb\nb¯M&}0èa1gæ³Ì¤«k02pQZ@Å_bÔ·‹Õò0 _0’’É¾’hÄÓ\rÒY§83™Nb¤„êpŽ/ÆƒN®þbœa±ùaWw’M\ræ¹+o;I”³ÁCv˜ÍìMÔÎ\nßò±ÛDb#Ì&Æ*…†­¦0•ì<šñ§“—P9P¼æÙçÐÊ96JPÊ·©#Ð@ Ã4Œ£Zš9ª*2¨«¶ªÒ¸ì2;’Ù'ã˜Öa•-`ò8 QˆF<ã˜Ø0B\"`­?ˆ³Œ0¡¢Ê“½ƒÊKª`9.œÆã(Þ6Œ££2ô I˜ÛŠcÊ³\r¨sþžŽ@P ÏC%l6ŸÀPÕ\$hÂÛ­±cð4b`9¸œX*NLÝ´³lÞœÁ˜á¹A\0ÉÅ‚ÐÞú½ŽË%£Xèˆ)L78ÐÐŸ¯””ø¢6ì€:Bs£MØ×£ @1 ƒ TÕuhóWÕU`ÔÖŽÓõ\0ÆÃ¨Ü5Œsè‚3ŽC(Îè¯o._/ŽP5ŒhÞŸ§¯•\r%Aƒ#\$J´8.b\\4Ž‘iˆ]2;X«×\0Pƒ`Y5èØ65Œp†cÜ‡\n\"`@µ¼õ8õw­h@\$Á6-'rã¢l1ƒ«¾É½TÛ–Ø°mA-TœâéJ•\0¬<áË’ˆ9äHÛP\nyK×ALøÉB=D¢~\0PŽÈÅ)	#j5\0B(ñŸÜ¹Kˆ9tëPcÒ‡eÑ`í×l˜Ù%BxÉ=3ƒ*\réÖ7!|9Ž£t9Žc6\$\$ Ü9…`å²Œ#8Âº„å`6®¸(ÊaJR'#1Xè­c†œ„¦)ÁpA­;IÈ¦ÈžW7( ôÑ¹)Nµ®sâ˜µŒ£Ë6¤#µþ¹3Cª¢ÕNxÝáIÊuås‚,1*\"j›§1o¶\r=Óúµ¼¯ÃºÏ=²¦N]ãôV¿0¥)_®Š æ;¢uR®8\r6(ÉËˆ²H2ŒÁèD4ƒ àáxîÿÁq|p`9â&Áy9!àÙ77šx\"^¡ÈÔ‡GìÍÂú\r`ˆ3ÐàNX<á„ ð@’ h#n:†ÖS\"ÿ?íŒœ‡BR^KÙ}3òžuŠ¥ØNAˆ	A-ã^ïÁÛ2Å1 ÈÏÞûŒ:Pì=CÑ[Ï8 9Ã*™âCÕF}`”Â G¸B&îñŸ“Œò\\ië%¤¼˜¶GgÃ£³&Äâ)‡f›…k–G@^¨·V~C™ûAä¤üpØbÅ	<L}…\0žÂ¢‚P’	IžW7&…	8\$à‰\0õ!\$0 …E½†””‹˜u8¢42†¥ûPjKÍ”§Ã<Ë»5ä€¾V\\Á\0S_ ‚X/sE‚0T\n7*¢\\ÑìyÇäþ:”êÉHC¡=D0ÖOÈ„o-DLäc“ÈNT(@‚,ñ\"„À‹>	m90:.pà¾h3 o”’Â‚J‚¢#\\Ë  \"À¬´ÅóÄ1£^\"79Jd«4aÑ„´\rQúZ#Äpð#ÈyAø5JøˆHT±iU3'K¡‹“u@æAŒqÍ›³’,ÓSI)ŒÅ§36ÊD¤x #ed¶\ntòAbõ16Žp!f	¡™ªèÃ	ü-a½µœŠr¤Ýù/êä+ùÊEŠp:„Èé\0‘4ÏÌU¦þ!”êT›´p8%½X`ž\"ÛKgåœ“	ÞŸB¡€SÆ‡ôŽÂG	+€Ælƒ#Y•P˜)J…ÜZ _¡*>´ÖZ™³&Á¢À§AEÐp¡Œ0…¢+µÇ,ºÜ{’ÈjA\rõ5rì=,9aŽq‡¨³9Åôé´@sRÝîÊ[˜ÿ¥¨f¾¡«ŒªA‘4†DÚ-+&¼g<‹éUl@jA…R8H\nÈiEª¯0õ*—/\n^Lé0Ð}ìE/ö|ƒª£2´3äÂb,>PUÌ¬\n6:èT½“?ÄÕºÕ	L¤ìR°»ÌÍ'àŠ±c¬ÅÑ¯»KèRj€iÆäåä¢t[Rq…Çå³žÌZëò#³Äù ›’;“#éMUXî´Ó‘\rÌ¬:`Uš#ú‚Ò%1«t¾¨æbÃG³gL”Ôó„HVƒ\rå,èìs±3Ì:\r†žÌÎ|ó…B\$d– R¦¤H\$²Ö¶!È/@ô©KO!ß’•C¹ÿeˆHŠìÜÝiÈHú¢o‘âhðK'Õ‡¥W5*G¦uÉ1DNRˆUfÁ>ˆ”\$!Q†,Ò[#8ÈUm˜rJbŠ v<©\$ ÛuLðÉ¹Ý'¬—i—WNàEv,­;=I†çÛúº¨ÓvòE	)·vouj¦’­nøßVjÞª½Ã’ê¾ù¢ŠðmÖÔÚ?¡+uÙ4¢€‰H¿CÁ¼\"´d{Æ#jbÕ- Ê(N NÈò`Ø¨A¤h–ãzžMòV_ªâ§}\"6û5J»,¸1Å È¹÷H)âuº¥ÖBÃ·õ«à[‰tÖeÓ÷¬lR{Ã…uj›Ö8?aýV®3=ûÂ8“FÁÅ¤VV¡mWgßõW„•£ÔïÜÝ¾Ú÷ŽuÄ‚Ñ‰Ü'Üü£¥.‰/~ðH¬»cX×ßï–OÇ•§ŒáüOY qï<\nT|…²òY—)yo¤;?›Ü>7‡eìq¢óP­:>qNK¿:ÑÆá×ÎÖuG8nï·¼>äFû¿B/~¶¨Ü{¬mØˆœpÔ×@7†¥FˆH´e'ÿ^‚Pdu	Ã«Â…¥Ê4>ãMŠ£q´87Rfª¾Ð¾ýNîÜóýYx±ú]àq«š*éŒ#fÞBPiB\"Ç‚/`@B‚²{âNù+¼ùkÀ¼K¦º¯ä:	\"âÅNºë\$¥e+*¸kn·%jñï^£ÏÏvîn÷ðJSNÎ®õLdÃ–RÐd;(Bˆë0\\÷ÍãƒÇÃ–óPRáL\r¢‡îÂdŽÆïm¬:¢ˆœp˜1ŽÕÍ°š×¦Lã†JLˆˆ	bLÅ££¦KhŠAÞ'O– j<ClôïîN¤îŽ\$ôÌîý	)ð°úó0Ÿ®¹C€mÅ~X*ÌcÇO0dP¡Q^\$«ñòå TTgJWòý„¬\rbzA0óð†\nd¥‚ìï ‹‘H'1_ìÁf9Ã–u1kÑ.íq{±l\r%@¢ á±SO¢|ùñ&‹›f,D@áæ‘ËÈ.kÏOzªñšN‘¸?ï1Â¼±»k±S‘Ð½°³+Ö¼äL„iŒ>ì CL&'ñ¼êqÀÜàêK¤¿Ìløò ‘úÏw/¤×ƒ@<°\r±MMdä&(…ÄÒ1ŒèlÐÜ–\"UJÖmlúç<wPÞôRE@æÐÌ»\$àÝ%0ÔÏRF5M	b@t…œVt\r‚‚J «#å¶VJ;C\"A¯Zª@ìƒàòg(à:IÂI@Ø`Ö‘àÖR„&hþ<ãŒ(&êQÂ&¶C¤GDçC\$% Œ#â˜x(òuÎ~ž`¨ÀZA\"6:Ìæi®¿þÏLÈÇ ÏÆúgÒøòT”ÏdIòJ\$hþŠoFIÍßf–\"¢ª‰Â:#íOáZNC	²îÄJ\nÈª¢FD£^Úð>‰…&)#dVâB¤Fl¢5 c\"@0ÊÓS\n‚P®Né¢4EDq€	€Þ/ƒ­8³ŽULÌXªÆE’zÏ)	8L@>S(á®²jfn-Îêáky;u†Ð\rÆs®0ŽÉN73‹(Ïy;Íøïƒ;«v'V4zUb¬w`Þ\0è\\k>}óà0fðvpM4`óÅ	Dâ®CáB:Qêê0pŒ”Š’t/\0 ðdJ0‰\0005CZã¬I4`Â»€êJ¦m?ãÖ‘î(YÀ‚&nQEã\ndILº9#fÓUFª³¹5sÆ¦ªzçék9î¾DªeHjySú‡Ð 2qiÅ’\n@_`¬ …ô9Fb	\0@š	 t\n`¦";break;case"sl":$g="S:D‘–ib#L&ãHü%ÌÂ˜(6›à¦Ñ¸Âl7±WÆ“¡¤@d0\rðY”]0šŽÆXI¨Â ™›\r&³yÌé'”ÊÌ²Ñª%9¥äJ²nnÌSé‰†^ #!˜Ðj6Ž ¨!„ôn7‚£F“9¦<l‹IŽ†”Ù/*ÁL†QZ¨v¾¤Çc”øÒc—–MçQ Ã3Ž›àg#N\0Øe3™Nb	P€êp”@s†ƒNnæbËËÊfƒ”.ù«ÖÃèé†Pl5MBÖz67Q ¢ž>Ügâk5Û3tâÿr¡ÏD“Ñ‹(ÅPß	FSÔìU8F®—ÂÊzi6‹3ÞiŠI2Ôósy’Oõ”ÏÂ\nE.š¡¾Ššæ›/bè†;Zä4ŽáŠP ,°Â)ƒ êŽ6ˆHÂŠ°Nè!-Ãä†Bj\n‘D‚8Ê7£(è9!1 ¦î#Ãk^Ò .—È`ÖïÀÃP§œZECšA¬Ð›Ê4¦Ì(2B£Z5#Ìœ ÇÂn¢êÊ oÀè–B€Þ5Œ)L=íhÈ1-\"š2Å­“Â3²ã#‰9Î«’è»-\"pÞýÎc\$Z:!ï°Ä˜Ž€HKEQƒ\rH\rI-&Qt­éº£+(Ã¨Ü5Œr„¨-ƒë5B.›°„¯ƒZŒ9'‰Óˆ\$²ºÈÛ&#z*	BI	ˆƒxÙ5K)b©\n®P£`ØÎ.Œº(1¡nüÐÞŠbˆ˜â(ÈÉf­\ng_ŽÈ]žú àPæåFSãâ“QcÔãy6W|è´Š©õÓ0_HË“:&÷¬¨ò¸Ã¨*ŽÃ|<êa°õú:_óòë%Þ°›W‚CM;O‘x'Œ’ŠL9Ê‚ ÞÉ\r¨XòÏ\rÃ˜ê1áC˜æ3Z!@¶æ/T_—Œ/›hÚQál7¨P9…)z.œÊ/‹93êB¦)ÁpA3;\rÃÞÈÐÛhÄA°øTmhè—ÉòŠn:Žc€Êì#ÛeØñ7‰¾P£hÛÃÜ’„`Þ3×Äkâ«8–jo¾<)`Ø«ko0æô\rïSÂ´×.Q-dÈ\n^&ÃhäÃc˜î>Ã(ñ¯ZÐx›µƒ(Ì„C@è:Ð^Žýè\\Ûhôð\\á{ããb4ãp^Zƒ“2:v‚ûP…a|\$£ƒ/:Žà^0‡Ïã7C®§£*\"HÓÙj 7mpú\nÞŒ“ô‹*Z—ÔxC!\$¡ ûšôÞZØH\n\0€€RGI2Ya¦à7´àÊÔyP2l\$—dMk|;D4’ïÊ1€‡„Ü'AYA\">0UÈžr2å” Q…9¶Â\nNIÙ’€åAÀ€ Â˜TkAiù£±Û0¡„š!¦8ŠGÉ1á\\ä)J\"<sÐŒ? (ÅÅ°sLù 6À‚–À@·Bc`„¤ÀpŒ Y>Ç ö÷Þ1eÈ™‡\"^\rÑ&\rfà:«\0uCI±Yä0‡3ðjpu,‰€ëUÕ&žá'Ë8-ƒ¦‹]!­Pæµ* Ä(±–B	¤…“ÒJ™L vHº:¢Ða€PKE‡Ä¹‡)^J\rÜ_O'Ì\$â6ûM“„˜Ç•‚³dþKƒ\$'ÆÄÀÁrì”RË%á³J#\$Œxr†E@—¥å<½2ü_ÈŠ¨3\$½SŠ4\\&L7³U6œˆÙñkÁÙ2Ô•²ž;¤Ùó§ÌdÊ’0›ÎˆÊD¸KaÚœÒx(u	áÌ™¦ t’e)üËœF<•,\r%*#3.È\nzS¥4ù¿[Ðh`Ù<Rö•ic%A±¹3¦—kriî€›\"ÝTYBœa³!ÂPÕU¢ó˜äÄÎ A\"ÍÉ	ª’Ò£âø±fA •°6#äÊüH\\ÄgE¹±_£é>ÏÝh—2æ¨)L²Ø7‡+Ü d±éšÆ&ÅOYâHDí	„àÂi7P½4Þ_)„0ËaKÚ¢J©mi…µá–ØÚ»ij2‹Je7äÝ•¸Ž<õ7«\n\\YG>Ö¸Ã•`™®ºsmòß71B¹nÄ®Wy‘Bæ.]²0á¤\\ûH­5Ò…Öõ¸Ýk±z*5Ú¸Õ†fA©-x	ÅâaW–è_ÞÞ¯Yž½·\0Ùßëä¾!Hi€òÔß¦œIƒªÛˆ´ZèÁS¿€î¹’ºdžä4ãïÁáº79~Ûü3w°gy¸|“4\\EuåÄ·»\ra¬9ŠÈRÖÅ×žô’àŒð&ÅX{™,wtgmþ0\$~¶TK	h4' ×o	P#._–‚^ofÛsG¶q\nôÐmk‘—™jï#Ä|c(Í;d˜Ðåiä\\Ò‘R6¶‚É¦tÒHÈ¢á2D{>*{\" Ñ\r‹V\0\"—²~GË„ÔPÈ»,1é:È\\>¨Ê,,þdÁñÓZL:Œ°‹ýõ',Nƒ	:Ü@TÕòù§[‘ªØ½ÉÀLQkyN@9vJ‰ÝæØ³Z—:L;³l’HLõúaØû9ŠP‰}-¬P:,Ÿ\r`…ç!ÐaÌ«*RPÇ&Ä±\$Àî‡d™E›æ®BùÃÎ4›§H)è†k¥ºîýtÜ5àr×Ó“xJ-½¿l¼ôSÅ²Ý[:uª¨\\:êO>½xnºÖ§Ç_&iBi|	dê3åƒÜ´ôñ*nA®âð}uƒm]Îäw4æ·ÎO%yQCå·q|Ýþakï 	æ|—›B^qcyÕÛ¾¦s_‹™~ú\$!|›£rž‘Ë:V¶^x.å_›W!ë5h‘¢7‡êýè»»ìD',q»ëÚk<ŒíœW€Âœ“Vê±ÍT¦ãžî]ÎFMjFEÄ™E—ô Í‘%\rh' \"ªà<7ªçG4w´±‚®ô¤T°Ú\"¨M¼â\rFH‚†e<|Q†²½ÃµˆÐW{á7Ø.>°'ÚÆnåLÜÖ.¢ŸmiÁê\"yì»ÏQ{Ï…ÝÿvÂüÃ{Û }JÃe-Áý¯ïùçÔ²?Gäkß‹@/¦túµGåÙ‡óöÑý;Wä²`¼ßwªkœï·¹ÐÖczð<–3•ý.ÎnÉFÿ¯°éoŠLÐû‹Øî¯¾_LgFT¯Fö«ºêÎVü®ÅHJî³((bú5¬>ñ)\\F`Ö&`Ü[üãIÆË\0ù0T(pXÀ.Ü×ÏÀ\r¥óCâp6áFQ°v- ¨Ü-ââþ• áÎ,ùPn»¯ÄÁ	ê\0®„|Ëê›jí\nÖMJê'nÚø¶\$pºúðµ	¥ò®p¸®Î`ÕJìCožüƒÀ³Pªo®Eð²@°ñÐëPµãÛ0ÊûÐ´§bx/cb-0ç\$~X¨&/†H—DèäLpêl>Êæf«Ê˜Lˆ!fôçÑ8<ÆjæQ2_,©ú3Nƒ4…L°ÅÑL3E%îFy©\"&#ˆþë@7eý±B«ðÐ…1g­6EËË§ÅB/E¹Ñ™QŸ\"Knó6¢\rIï^EàØn>Êè\$Úâ|\0ê7ê%àŒŽfŒà0 Ch\n ¨ÀZ\0A\"ö\rÀÎ/ÖŽëË^Ä‘þìæO’¼le £âé®~0ì|a‰hëréè,o¢6À£¬%&,ØDJ> ÒÀò@¤.ÀÃ\0æ±ñÒ¨Ë,H&¿%eœ H´;ƒ J,Ö¢Ý&#8—ªŒ—ñ®8ø\rÊN6Ã‚B`˜\rãl8à(èÖð4\$&aE¦Øë\n±Mdbî›%\$®‰éØ©°\"ˆ„™é¬épØRÀÅGX¯2ÊZËîÞIÖƒ²®2ã61Ã .§üJFÉv’×CšNäò°^pÄÂ€Òd2Of Ð÷Š¡\"t'‘ÎŠ§÷1Èƒï&ªDœðårC;ël\nÒN¤.drðXCºW@‚-æ=j¾š‰\nÿbÊ0{+aC‰¨e©¬0bNUGáeì—	ªD‰…-1‰PNadF;Ãö%ØCFd‘é";break;case"sr":$g="ÐJ4‚í ¸4P-Ak	@ÁÚ6Š\r¢€h/`ãðP”\\33`¦‚†h¦¡ÐE¤¢¾†Cš©\\fÑLJâ°¦‚þe_¤‰ÙDåeh¦àRÆ‚ù ·hQæ	™”jQŸÍÐñ*µ1a1˜CV³9Ôæ%9¨P	u6ccšUãPùíº/œAèBÀPÀb2£a¸às\$_ÅàTù²úI0Œ.\"uÌZîH‘™-á0ÕƒAcYXZç5åV\$Q´4«YŒiq—ÌÂc9m:¡MçQ Âv2ˆ\rÆñÀäi;M†S9”æ :q§!„éÁ:\r<ó¡„ÅËµÉ«èx­b¾˜’xš>Dšq„M«÷|];Ù´RT‰RÔ)·ãHÜ3½)CØ÷‚öµmjˆ\$í¢¥?ÆƒFÏ1EÁ¢D4æ„8±ª‘t’%L‚nú5æ8¦¤ì‘x‚&‘45-èJÌh%¬éz‚)Å¢«!I‹:Û¬ˆÐµ *úð±H¨\"ŽÖh\"|˜>‰‚r\\-q,2ž5ÏZÈû¡¬”¦¬E\$‹+\$’JòÅðz¢Å,mZHQ&EÔ‚A6”€Œ#LtU8²’i’RÚrX\$ŠTf·À´|˜^@­b1'¢ñ\"ÜÈËŠÒÈ_>\rRFÅ‘\nl¸¶ê «ÌqÌ…\"¤„ýúÐfDÅ<ï”¥YÈu¬.Î³ô´ÝV­©¤+Y22-Îè»Ë;Q(±\0ŠµZøÌeœ#Z­œqf3Œòj\n#l¥Îõ¥PŒˆ#>ó¡€MÙw(²åvÜW‚^ó\$•ýÅaE%#ÊNÄ2n³@¬ììö±*¢¾þÖ3„ÖŒ3¶Õq2J	m%¶=6¤?o;º³µq0Â”%p›CX6.J<´õtI“³é‹’	™£ƒCT\\;[Òî(”¦±DŸ Íb¹³l]ƒ¿âˆ™E,uoç	Ú^§²Þ†DHIÃ˜ªPÈž<o+o±­7]êz+)E•uÃTm»{ïµW!I´°Ö‡Ãi‘yr2—|±‹¥‰%1{Â§*\n–¡¶«¥ûtÜêù\0¡±lN)2i¶ÆA¤›WºG«nZñ3jÉUwþÌ6ƒ’HOGÍ{F\nƒ{ˆ6Œ#pò£pæ:Œcœ9ŒÃ¨Ø\rƒxÏac 9xãÎ0ÀAÃy°êë…˜Rµ¨‹‰AJx†)ŠB6©…È%»[v¼j]Jr}s\$\$B8*ZÛq'Æ±Öêï1kEh™ÈE‹¡QÄÅl@õ¦‡YudðÖzC‹[!p¤ÈO1ð]%À“‹´×Tâ‹LˆÖ:ÃæJM!Ìë‡#‹Ã˜w\ráÉv†PðHr¡‘üƒÀÂobf ˆ4@èÐ/áÞ2àÂ(n¡ÈD°ÎÃ(nŽàë¼ Òã\":„9ÐéÂùßx¡¬à’CË\r±Ä:À^Að -\0‚EÞ»NŒ”!¬à†èqc[ÄA¸:³0ˆ\nc­r½‹”HN„‘Ò<GøZ¥ÕÐÐ…@\$	RÖyqGìš›UšÃX½}„É÷¡9Š©\\Ê-oíÙöøŽËÉtjÌê*#:ArîLç½o¹G24J¡cK²ê\0N‚þQR, È¹•‡â¾‰ZGb+y'¢Ô¿.ÂxS\n’Ñ×9áÕÃÿG(A«4D4X¨\$«&j†yÃå0R§ÁtM½>§wîFFŒ2Pf„³ÄXÓû‚N°r{I æPÑ¼oäHßÁWA¤3‚\0¦Ô\0f9GäÅpŒ%óÅ]¡¦DD¹?'ªr\rá´‘F³K™)¹;	’vVßr,®’£\$ÚØ€O	À€*…\0ˆB E\0¢)æ§ÍR<'“ö[žâT,H\n­@€\"P˜ks®¬d‰4dÚ,RÙaä\nÀ®S©…•(Ù\n”ETD¬Ë\$ej!\\'2\$/ÓK12Œ]Þ–y6UŒ²é²fUº5G´SP^u6>[%Qï'%o¿R'Ú©H23«­¤LÇ­â¹’ý¤š‚ÉfÍs ,hÎº«Øêœ[˜3½T·!5Do,!b\rP• §,•œ4F1Ó0þê—%ž»®’Ñ»Øã»s€»«RLO®ã\nWd`˜0hJ­_Ý¼¤R³ª2JØV‰\n#§>{¤BïÕ.‡Æ”˜	¶ç/%ôåÀ‰6lÒé÷1é÷\"%Ð¸”ÜBZÝ{&LÊ¬º:Ëh	CVÍ8¬Ÿà¦CÐeA©‡Ï’‚˜e9aŒë†Eúc/y7Cˆ¥ØÍé‡+á”\n¬‡ÊiÁ‡ûi¯¬5·dž\0«âlÇfâmËykª5œ¶Bí–ßŠbn&Vé2Zé¥º”R!V¥Â0}Ý¥ù€VÑK“˜%C\$«„:s¡\$æOÕW•Üª¥sp)’Fþ‰¨jx­D+¤˜±ú(¨vä\\ÏtJÐø£\"Ë€ÐlJ.‹ØypBkuÍk/se ˆÎmîvël‘‰ÑÔ0ÆÞÔßÊ«ÚÙ™Ól8ìÌLÜêÀ%ÌmmÊ‚Éäp©÷oQ½Á:·ím÷«-’Ýºs&ì5;»“½·¼ÊnõYÓ_|ìô\$çw~¹ÚÊuÙLlkÄÛÎé¹Õ‘'+ÂÕ¥“¥fLŠ»ø1uÛ›Óm÷º¶—&HZcí®UÂ-ÞÜ¿€òRëC×D1å;ÊðžuÈ8©AÔ»- R\rÈŠñ`-ûÞ5DS©\"Û¼ˆôöˆ]±nf·’®Œfb”ÃÐŽ@°§R—\$ÛBN&BÅ,.Ó„4ª€œ8¯¶—È%ÉFÚUçÛ×kÕXÞÄ,‹¬¬Eœ%ñÄž\$üìT*ÉÒª¼!˜çH.”R…Í[,B	>¬®0±o-Ë8'2zUÕÐI«´TF°þúÜ/é’/p†8«°›Œ‡šzö^­´{déð=töH7Ú•b×ñ×ü5â-\0™R¦¯ï¯Õ˜t¿Fêû1¢!jß²3ªPhk[Š‹]e±u¥î““WA?fVá*[¤-›é\\&æ›{æd°´ÄÄÎÏÎÏ&.D¬ØWJè–£ÚfCä²¯ÜÂÃ0³åXÐª\$õvøm~úî/KÐÀ¨`&­v»of¿ª¶÷Ð\"¾:r/’ÑE¼úÏmî:¼P<øCÿ®*÷ÍN1GÓeèUMû‹Ò\$ŒºjÐh½Ð2öÐ‡fu¦VùÐ„qP>ùPj?ƒL÷ÏÌ*Î¸é H‚ºà¥¦\"ÄÂ>NZÎ\"ßÍÐ*­Öü°·®QêX*ÐÆÎŽÍî°ßð«\r0®<ÅuÍZfhl’MõN õ0Ìâƒ<-ôFt«Â,ðŠCà™1Q'Fïjº«ñÂ3pš«¯¦P\\™N–Ï‹8AFvÏñN¯°&¬HÓl3î€>%\$ƒšLB{QRCB¨Æ¢¯æ(^‘g\0Lð…Ñ:*ñh1‘M¥Âiï‚êåpBJ–ÂÚƒ­lñkÚ!+_¨Pô±:×±>=bHÏÑyqž4–ô+²¸\r&³JN\\Ì[KˆGÏRùp¨ÝOÔ2qëïˆ¾1I\rPë¯Rú‹·	©–×LÇøÐM	¢¾…G	ì±òÖQ\"Q317!ã'\"-\r ±E!\rBCâ6²>ÖFD²ÒÇÄÝ%\rc\"OÔKÆ”r\$HÓd\nH,\$¢0fGX„CêébabHÕÀµqý\"ÎÉ)ß	Qðöï(ðûow Âo®ÈÎm >á`ftµæNuóE°œlw¥¤oÇ+2C+ãÚ`‘v'ØÕÜ<ï\nÁ­?äEì-©Â¥‹P²Çi)q\"GI—0®\n€²ís)ò7*Qõ,²ºËâž×+öð€ð\0'³Dñ\$lðÂñC	‘*õâzÐƒX¤FXp¤À´íÒÍ3ïY 'y612.¿#¡¡7\"%7rE4ÓC2’L–St@ÍM-¯¬œ‚9#Æ\$æ[ÄåÒb(«v×ã\\¢qï\"³7Äµ3¶Sxù¢Ö×ÓÄ±³Jú²HJ)·¤ý)ÐÂ*Ò<Ô\"œžmLp°L‚:_\rw/HJåÛI³mœ®´î\$‚æ¦1@ïA4,\n)ë@ÄnO¤Ù['ŸCè]‹­CþÐïqÐ@Üñ\rB-˜fâ°XÏöâPìâ®FÚT\\ôå˜÷q\nà4Égr\r€VºóÔWM¾Ú\n:.ò…P¦·¡(@Œ¨i(¨ˆ\0Ä“ Ü­€¨ÀZ\0@Ž Æ’äîI-@‰ìYn`Üm ß”¯îÚQ<ÔÍMÎ’™i´ƒ¡nZ¾¥\0Eå_¨	´ÀÀòÑ¢[Hä;*&Y;+àƒŽäDãµ¬\\ëoTŽƒ‘9+F.Jk‡/jþZ¯:÷ÇTBoTŠò/\"`R•†Ï-%@P ¬0Í¸'KÖÌ+öÓz.ožÈâ!ôð§d‚’4ÎGVÑ¡Wæ¶ˆ~åYL‹F01;õ}ZUƒF\"œ10Âp#sµ¢÷õnùH}’ºÅæ”er€ÀnÃF›/âüejEcXü	p¾ƒTµ0)UØ §\$µPýÏòÀÐ3çZåeF.\r Ú€¬ Æ ê\r³ö¥•¼ñæØg\"¬ŸÌ>pl_RÆÍ S2C¯0¿b¡©¨ûÄPAbXYÃÚƒ0znKÿa5\\ü´ú)PºÃKYä:•Õ¿?ÍV%\rH¹å¸m´’ï„fä*.`";break;case"ta":$g="àW* øiÀ¯FÁ\\Hd_†«•Ðô+ÁBQpÌÌ 9‚¢Ðt\\U„«¤êô@‚W¡à(<É\\±”@1	| @(:œ\r†ó	S.WA•èhtå]†R&Êùœñ\\µÌéÓI`ºD®JÉ\$Ôé:º®TÏ X’³`«*ªÉúrj1k€,êÕ…z@%9«Ò5|–Udƒß jä¦¸ˆ¯CˆÈf4†ãÍ~ùL›âg²Éù”Úp:E5ûe&­Ö@.•î¬£ƒËqu­¢»ƒW[•è¬\"¿+@ñm´î\0µ«,-ô­Ò»[Ü×‹&ó¨€Ða;Dãx€àr4&Ã)œÊs<´!„éâ:\r?¡„Äö8\nRl‰¬Êüž¬Î[zR.ì<›ªË\nú¤8N\"ÀÑ0íêä†AN¬*ÚÃ…q`½Ã	\no\0Ò7ð2k,îSD)Y¤,«:Ò„)\rkfä¸.b¬á:®C• ÁlJ¾ä”ÂNr\$ƒÂÅ¢¯‘)2¬ª0©\n¶Ëq\$&‚ í¹±*A\$€:S®·ºPz±Çik\0Ò¸Ü9#xÜ£ ÊU-¬P¼	J8“\r,suY©ËÔBæ¸Ú\"¨\"+I\\Š•Ô²#6Æî|\"Ü¢Êµ(„+är\0Ü7¨¼CUÄðRl·,ÊA\\«'\rí{E­H_*Ñ4èØ©ðP)ŽDXÕÒ\$B\0Tº2º&4\ršR¾BÕ\$žÏ.k{¡Îk=8ÞFá@Ž2ãhËfµN=ÂÞ®}Îß%t\\)Äý“YcÈæû¶‚®«Š±2§,5Í–2ŽOåƒSHr­OTÙe\n£ž!ƒVHýrC\nRR¥BÍ„Áä54BÆåhŽ5)Õ–¼1+%’\\à«I‘‘À•B¤I’qi)ôSGZ¸0‹m—·0¥‡oMór•3_5LCmDŠa¤RË«†Ô‚SÉúÒ\"¾X¬ÃW©JwK¹šŒPn)Ô”¼Úæû§¢5†‘.:ºõ_opÌ\\\\Ðm6È+¾Êá(ÉU¢òÜÂXÙ_°Æ[Pë2BmªmŠF®¦Õ‚0ê7c¤û=«üdÙU)ÝHP Œã8äö¾Ýžá&ÑýZ€auŠ(¦Î‘/KTwýK,ó‰~¯¦Ûš#äÊrûµnöš!pD1€Ä/²Æ6ÒfÒjóéEÊ^-¨u£¨Ø6>/óØcÜŠˆL)¿26dnJøpëO¾'ÎÛ²!Å­fšËèf/½º\$—Ø”ài¡q¥¾5õ\"ÙÞò@W®\r»BðS•ù±fô6ØTæ\\!Hqèa9´&â^ƒà`ïÑŸ+4ka2…	¸˜Cw>\\›¥.ÏÑ !&èÕ  î¨·½¨N¤RÖ4q]Ü6ç²©ÞSß\"I“ð’iÃxr†¬\"SO ,Î	ÖXw¡ëiBð’‘F¸È‹0°q¯:Xæ\\”)pâ!Úµ7ršS#ò)ëä6,Ó<™KÉoKe%@ÞyCkþ ×‡0êÃïa™û†ÑXs‡Ä9J°ÂÃ\n+	Î†ÔVO¸(`¤¯0¦‚1H6fDD\$vôÝZø7¤Ò©„p¸S”lƒÎƒ¤èöècR&G«M9&”ÍiÀ3î¹÷Fù¤l!Lå\"yºEì°ÉÄÿŸQv”(p·bëÐDˆÉqS\n%–BæƒKá6lÎ‚t’Š&¨R!šð\rå¯ˆC\"]‡Êäç–øóD.WS•WÎ†ÚôÚ)\0hïÍµ½éâ‚ha`l@óT æã‹—¡à8—~)<'yß†`zƒ@tÀð^ë\0.(³71\0]C8/¡ºµ'šÖŸð/Oä9àéUÂùÿÁ¬èÞP5k€ð†|_Ï‚î?!½ËŸ A*CYâ\r!Ðó1	SYCptžIÁq&QRÓÐs7b¹5–‚P ´›D´\0\nE\rgôõ7.V6œe]&Ôy¶vV;å”T•jZƒÜœøy2Ý+Toá’;Qq±CÜÊ*†—™)€Ó­ ¨rãvHUÆj>Ä%(óQá\0UÓœÞ›‹«g#î‚DFw5µû8©ŠL4@Åš™ÐQ~Nä½^PZ:0›ƒæ¶Ïyíu­¥78³´6ùƒVL!¸¢0\0žÂ¥úƒ×ÑìE!£Ñ¤‘ªÈ)O®äŠWâÄOÉÀYéD3‹íˆŸ†4üƒHg¡Éà§à@*pev'¤2Ú‡\r›”ïQ€(í×ú¥aB(Ž^©†,‚œýžª¦‚¥°Î\\4Çå0ì fK´1%fÉŒôsðé¹˜°éP“)XqMN¯ž\0U\n …@Š´8 &]Î¤Ò?eÊ>Äò©z™+LËU·Öf‘‘ž\n‡\"X¦>çµÂmØæ+i‰ÍyÖ5-Ï7²íJ(ôÞa¬P2„ÞÞwªè¡þ]!2†`Âý£šºº…žHÆ¬þ\núQoW\"Ç„0Œ ÚH:¨TÆš)„ZY_&øC´F¬“DJHJs3ÚÍ¢†œƒÛÅ)\$ÉÍ5dR¡Ò µTôRÕ¥S²îã\" p8)X49™½ËÌ°wö..4Zû/H#8†ïm|\"³)Å¼Pô–7®¦Lµ†Ýª ŠœÄñÈ®)ÉÐì:}i@È7»!(É¼Œ‘GPMþ;¯Rü.Ûežna[š\n¶¬\r/è÷°bw‘8%=_¦5–C u™dlø%¦é·Ñ‰XÔüç0êÃFµà‚ÐV÷gb’!^ìbPîQÚÌÇÛó´Ä0à¥³]øs\r*Âm¡'f¥£—j=8\$PCÐeA\rv‡	‘æ‚˜e=ŽÄ2†Mm‹œL‰ûVI>p¸YhH|ª•PCpv!°4¹p¦ÃA¦32J¥Ü¡¶õÈ®G×ß\",ûVÌ<áEœêC.	ì>¦~éþà2T²õ£Ôb¿Ú{¯}Ï½LáÀ†&ŒA{Y¿w¨™?õ8žƒñáý¶ßqò®~Üít¦ïŠý­x£‡öòØ†˜8P£g4ÿM:…ˆ>Ü…HgDN&z¥!`¹ËØ†ŽÒˆÿ~ÀŽÎzít4\rŒu‰ÌxÂ\0bÆ„&¨WnŽ'àŒŽ)RŽb¾M¤,€ªh´Í°ßœë\"ën°€è äM6]òSj„&fM¨üçkŽ‡ÈÒJOöÃp’¤Îº½)ˆb#gHØ«ð#g//Ä:‡9°¼¶ï²‰í®i®âP¸›%î‘G&ÓDä¬wï˜\"#š—g;Ð\"ý^WÎ6»Žk¬À¸äuI°¿ðˆÔÜº¾¦E¬ÔÇ0ÎûH£è ¨”ìÑ2^†.áçâÖ)X1(qd„¼K×)\n€kÀoñBèpŒ¦ð8ÆK\$ÞN‘ ‹‰5®9ñ`àË®lñn€Ñ\r,ùO›¥b¹®Z§‹UäQJPq;Q®i(‰Ð\rlÛ%\nÚ«âÝŒôÿ\"~(\"†(¯¾0‹Éè«ÆiÐDiqâËQè4dÎ|ˆQ0òqÏ\"Rfl6£ã@u¦]8rÚß‰>“Dlõíô[mHzedÒð1í\$Ð:ª\$eŒ!\0-ÓâÎhâ‘-Ž‹Þd/Aêw0Âp±šÆ¼l¯\nÂ²Rj°ÌJý(€Cf% PcØÕ†5¤ë\rÄu‘xAD,J'ÔÜ§\"ð¥êæ‰®\0trsO(¦€ò»2¥‡À2I(Q«qÍ,ÑI\nc+r)²e,n`‹Q“\0ù«ð8—¦¦¨>ã2'RÊ¥ðü Í½1J{±	è½0±¿ …12²Îäoœ…²Õ\nn5.‘½óCsA.ìrˆ”Ö°ïÇŽÁ=„tt¦ÐÕ\$Ê9²nØëpÚËu80ÙÏ\"Ô1\$Þ\rÔÚrA8‘BiƒZúí8Aã¨âæ\0üÀðê³3Q…íXo“©8ïøÆÒP4n\rÏº\0@\n€òšÓ¢#<àP}­nzF)9r6v°‘\riµ†fSõ4ëéò<Rðä,G4Q©@ŽR1X}g§q\r5)5t@.làèzsK0©0“#T:Ü3Ë@mÃ@¥ƒC£CS\r-<ÅEÐºfs}4¥DÏäJOUÈ–õ­ƒ,+å,s¿.³%<o”ŒsŸHÈVçR¹4Ó,”<3=2ˆJ’Êfˆœœ’‘C4-”Kbû1\nt×iÐœÉ¯;¦5²sIT5Lä0V‰ãñ9=«\r´Å´fýôæ£†‘“~ ÑÜ´ÜãËåN2t‹&ùN²‡E5HôT1R!”ûS­BêJþ©à»’—\"²á!TööBÏ	H„p!Só,U+K4›Ef«Uéê¶±|3½F“;0ò/WL÷9ÎØt«H¯IsSLõ„´lg=.~¿¥±!1='™u'U¯VR™;LœÀµaÄŽ 2åEm®g[’\$Æ ­ibrí\n€‚µGÒ¿\$†„A§Nðq•B,ø×uô§ÊdýÝIêç“î:¤D/êÙQ{H6ZÖ‡Þ¤tYp³_t@Q^ ƒ=à²\n\0ŠµKR\n_«Å_í¬Â£Jliu]3I155´5èZ1Š&8­ÕTlßªGôý<Ó®ÁOú×f©NÍÐžŠnI@Î•³95Rï3äjTÉ\\……I5kLÔµYæß3T&ÔJç¶tk2tÓj6m.sY÷mM“h‹MczdÄÈaÀŠ<+2µmv¯YÔm&’tëI2dèÚþ°LhGp¶Çpå…o!]qOÇq–ýp\r£nˆx¤¶Y”7k4¹rð‚\"75o‹üŸóÕq÷;¶#oSoWok‡u÷<µÓtVîø°vaW G@aÐ`ËJ/…¶ñ²¹h(ÕcÕ9Ð}9RfAÅ@9²AUjÛOãi.tË•ƒrÐu{ÔáF7%PvÙ3´|ôµ¶5mn×Å}¶±A“>uÇ`vLW×gsf‘Hš’hûvV†WøÉwÿ~•“~Õ§€L%8\nØ8t	wÐª’°.§ÈÁhé’•=P‰HôYu4Ñ/8F“&‘‚´§ƒyƒUÑlUokTèŸ†[FBut–ÛIÕ»„”Á€Ö•`|”ÿ…ØJÅ¸†T­,oì˜53åpWÃ†·É…VÈÖŽ½ŠÌ›J–ë†’\r]3-ˆX©[/#Š³ãŒ80²ŠØ/ØÆ„8¤1{W•½I\rÿl8·pÖÝ…„‘ªm5‰Ùr˜otøó\0°‘]ŒWCŽÙI”U‡`¸ùˆˆsø’AY\rRù\$ÿ˜çw¹-ƒgÚtçáqGusq	g˜GpVt0#¸ý{Y–Å@T…2P}–®=–íá‘M·RÎ‘—ª=–QÈÐ«å™‰#y)ƒ9S‹Y1Œô:	\n„ø/M{·ã{ï%Ë§³fØÇ°vè:ß—dÏ–bèX@+˜fAã,\$|»÷Ëqî1ôåž9+[Yì,ùñ+éˆ5Xä—@.š'òœOÓæ4d-GµJs¸”7™ç — íåØ¡—rÝ‡Ëç‘OPæëA±¡¤4Ss%j¯ÔX¥ç¥ãéšY Ø`Æ÷€Æ\r`@ÈÊ¦÷@Ø£ð•\r Ì•k&+ÀŒ=cÄ\r®Ä²€Ä²L°\n ¨ÀZèvŽE‚½ŸÖ.ƒèÏZDS&aÊþŽ7>ün\\%%ï™MM:W‡5±R%ƒ¬i\rnúL¥2ý­(å0+þ)ÚßŒÎ‘!¶u –xüˆ>x”-\0001‰­ªx¦wž×ÆIžƒ°g ›¨ú“+s6mš]ÏDÈ¤öxw™+KGz+Å¬Úù.8×˜Ëæ—r°HïY“`’2KJ‡&K‰¨Á÷ï(–i™—ù(s\"@ ˜ËdVE»Ž(«Ž#Ä>£ûyÆŒ{ÈXÃD‚¶<m›ò¨ZÛX-…Çšô89¢~Y§^£î(ÔÕr´!1ƒªj™ó…9IÓéç6¥î®a¢¹aoQ60_šq¾ó\\6è[py/Y»Ù5€¨êƒà;ãÂ¦­z~5ÚóK½uo±1tØ’¯zÛj¼?2‹´ZÅFùÃ¼ü›-›ók6÷pè·E„W¢|Qc–¿?tFSµ—55Çàˆ>ÌÎwï5…¹M®ÅXê Æ¨í+t°í9ª8¢~	ü0˜Ï2êÄ0Vª¢!êJ^û={œ‡Cq™˜g’¿¢Ë™FÝ»Ô¦\0¨[Î>ÜuµU~Ã–7G9ê¥4y¥:\r0|¬Zûânº?ÐÔ>ïÒ©8\rñ¿ÇKˆx™ˆ­ƒHCZ<ùIü@Ožà.—Ñ¤I™Y;W@`	\0@š	 t\n`¦";break;case"th":$g="à\\! ˆMÀ¹@À0tD\0†Â \nX:&\0§€*à\n8Þ\0­	EÃ30‚/\0ZB (^\0µAàK…2\0ª•À&«‰bâ8¸KGàn‚ŒÄà	I”?J\\£)«Šbå.˜®)ˆ\\ò—S§®\"•¼s\0CÙWJ¤¶_6\\+eV¸6r¸JÃ©5kÒá´]ë³8õÄ@%9«9ªæ4·®fv2° #!˜Ðj6Ž5˜Æ:ïi\\ (µzÊ³y¾W eÂj‡\0MLrS«‚{q\0¼×§Ú|\\Iq	¾në[­Rã|¸”é¦›©ž7;ZÁá4	=j„¸´Þ.óùê°Y7Dƒ	ØÊ 7Ä‘¤ìi6LæS˜€èù£€È0Žxè4\r/èè0ŒOËÚ¶í‘p—²\0@«-±p¢BP¤,ã»JQpXD1’™«jCb¹2ÂÎ±;èó¤…—\$3€¸\$›Ú4Ã<3«°ô/¬m£Jæ¹î‹®®å†á'ê6¯¹DÚ²Š6ªÉ@»•)[t‡¯ÌÀÁ+.Ú~¶ Êñs0/íŠpé#\r“Rµ'éL[IÎ“Ê•EhD)1q7±óŒhæ§ Þ\rlŸ\n(‹ÂE¤£9ÁîÂÀ¨*P“³>—t\\›8Ò*/¨ÔTI9—Ü&€‹35 khð§¤Ë_ÈñÒH\"U¹³Œ°×Fò™q8Åã·.§Îe|€Õö’&“l UPÛIú¶ž¦sLìJ«/\$ý'§¥Ûa·òÊæ‘jYfIŠŠ²¿Û±ÅaY93dÅ\\!W™qJC”Mc=a6¥¬ïT	Ü^RÛQShžÑ+;¤ŸÄ…íF«ù!pYÞë›.øêá^°Óƒ,EŠªg+^ñ;ybãFbíÓ·D©“r­¦iûÃD£‹ËmU2Å>ÇÔQ£·¨°6ZP‹ê§wÎZ¼Dð¸7‹Oa6%>žÔNÞÍZamãŒ‰3•\r%×ös`9ûŽ¬¬0ãÂäS¸\"Ç×Väã\r'ó‰B¬ MŠ»JYzé;hÓ¥lïiû³Pë2ÆP¶ÙMÍž¹¼øÚO\nËÑ»pá)È;©êwQ'³Š·poÖrh^Y.QV+³²»·#`PŒ:ƒcý?!\0æ1Œ#wi“­\\:Ð\nbˆ™mI+‚wÑd°ãm£äµ›ý’w8%¦»Eª‘ý•  ü1½ëÆ¾è	«o‡…jœ\"ºÏ‘c\"oÍ©ª‰_è‡zAŒ4PÚJËLLG–	ÁX.‚œ‚Á…A;¶³UÊáLP%Y(2ÖÜ°˜kmé	<6Wá{›;ìòœ6œÖ[*ÜOmÁJb³\nœh.Áè0ÞÒÃ;Bà(6@äVQc<\"Ý3/FDnB o>P\\7@Cps¡Œ1ŸÀæž` \r¼3£ÀæðrŒÁ„3†x#ø%n¡µT\n\n˜)8§5¤‘Ö˜ÛŠ-m€€!…0¤›±Õ.	I³`ëÙe‡LRP\"Ö._b,sçnF¥5\$ƒâÊO\\qu³:[àŒ…k½R¦!Nƒäùß55T§bªÁ”‘‚{‘¸4†ðÜVœÌ™Ê\nTºDXÛhHÊ\nZ&‚r«ó`†=˜EùPÜ–ü¸•‹qH«r°Cs@¡ÈùÏæÃxrn¡”<\0Òƒ(dÀ€B@Ñ@0=A :@àx/ôLÉ!pe@º|†p^Cu4ÌÚBžxr?aÒ…ôõCX\"Á\$6‡òi\0t€¼0ƒå.)Â\ríÔÿÔ\0ÂÏxi‡ÎFJrqdÑÒ9èiBÕ!ƒ1Š\\UV´œ”BÜNÄî\0@@P\0 Ä•…ú‰vÇŒ€à­šÌ)9ET«•˜•)Š…Q1òq'Ìüyq\0teuC¤^ÒæË¤¬§„°§d°UTê,+¥°»#`˜ÑU‡mãMÅ¹7“RH†i-¿Jµ.ƒëü\no•À¸Ué‡\n¡ú-sål®“ÙŽREÁv4ÖŽ	•LíErtëpK7kT¢Î¡¨‰¢ÛÂîÍ’¡´¶†:ŸfèÔKÚ’!EÚÖ\\‰­<ç¤=@Ýé°o¢À‚ ÒÞãÞ˜üóï	0T­OUº†šk>j]J¿aÈ7†×¥Fuzî}<©ÒfÇˆ‹=.q%’ãbÛ–W ('„à@B€D!P\"âLL(L¸±Èã¤‹	š,Kp£AX7 =³\"vtNiÁE7«Rç1žºq®pÜ«µ”¯[äRo÷x¸ÅXjáârEfjÍ…X¥¸|­F.èyŠ»áœ:rž]T¤lì‰Äçf6ØÁá,k%‡L.\"B¶0´±Ø*ëdô»<?DÀ}%l’Å„Ãºm*uµNùÛÒ¶âRéˆzÓsüÔyé¿i‰ÎdœBlÂXtŸ9PÕÃ«Ç+!ÃJØ–‰jJP¥’ûiË€™Wt\\0õÆWjöº+ná?VzÒ¥áêqa¬•ÏÜ‚²ÌAº+jRËêøŒÊo+ŠÄ(S¬œŠÓÂ˜iA”<\$6ôš'ä1 PÈ•ÍÀ\nzq:LÎ¨ðí†[=8§fÕ»µ‘àzøéãÒ“]\"`t˜?Æ½“æÀÐ/—')æ…p4YÑ|!Œqës™Sô2-SÀ-šî…¬&xS‹åÅGpðÓ›+(S0\"cšvò_ç	(G›î~âº\"NÇ…È©Ý†w^\nƒÃpî)êC¿«œµqëmú-Ä^QŽ2ÐäýÛB¸¶³E‘Ö‚àƒCNVÐjÅ¹<*ÇŸÚ)­Ó'>¼Þ£­Áäëì”«fA7c¶”¸~t[Œ„¬fß.ûÙërõ´¨#°aÛ¯ˆIídòçœ*‡·^ÍJ;¶cÌ{!U3—Ê¢.ø)JÇõjWóÃ¦Žß¤ooÓ×O8‘•G“Qž[fÀ³¾‡;FãºŠmCNæúü“íÜ‰PÉ©ÏE‡f+%Ö•Ýƒ˜\$,ÛÚù_g&\0 ®CsÂjýßÝ°[žÄØŸ åN”¸D-†Šîd(tf¬[Åô9©bËâ>äïxh«nhÈ…èU9¢~\$œ­´Y'äobf¨\0ý¤J9DEð¼é š§fâ&Ú®ƒ¸á¶ÓŽÓÐ:B\$4ök\"<,Ý,=å–S\"^1íÐeøö|+¢Ó°†àDˆ%ˆpj—ÃŠ;pP*b·\nŒÜ5®m\$ƒNôâª0(ˆàÐ„—ìFýÃNÈˆ²I„bHëÍZÑ‚³È|S¤(NÇO‚¶h¡EoÈa.Þä°ÜE‹&Èqÿ\0HhONdÎ@€/\$€nHL%=\nØä°æº°êã1D%î&DLxO­n|ÊEÚ…–0ÚGŽbnîDP˜o‡Ï/‘çþñ=°ìØ‘rú±}xÕöÕqŽ,kA\rQ“\n.<o‘‚æ±6äm\r.Ñ‘AeúÕ(	†+Hš„p–ÒE¢¡ñÎ‚ñÓŽóQ¾„E¾9	ˆãã¤ãŒ‚1ê–ñ0Íâú¯ôb%í©p+°™Ò/È™(óÂ^ôàäão„x‹‹	\$çixÂÉÆD¥Äk1wÑ§%·!î{%l1\$ŽÇ!±÷Ñ2jÎm\rS#épÂ®}%ã\$©!pÉ×.Pìo^¹iz–¥Xí\rRæòVâ„ZCŒ|ñQJSQ=pð…ŽXofŠçŠ¾v\"¸é\rí\$<2´h®4.e?\néjÕ’¼”æ¦üêmjwŒsE*òšƒk€'fÙ'RX,’\\TŽ‚÷Ç®ÝðÑn^¹eÉåfE–Ì2öo‘VNN0á£»&Roû(¥€~’3Qy *¾áÑófÏ4šÍ„[3…–î’-Žæî®­5Q°pÌóo	\"%³=.flm8Æ'3l<G/73‰5‡5ó™¨4154cšî3ŸStÔó\0hÓ·8-v[“¿ N˜E«Ï¥:#hÚ#ç-7Å´'qˆUÈ<#=¤ÄxðdQ#ÂPÀ\nCÒ„x=ó[53I@É\rA1âñ2ÿ&“±93G9”A\n3B\$Ÿ:c…}”':îi;&Æ–\rÍú¤Ñ&¨!Dãè\rèÚ€ê ÀïTPV™ÔoCæÁ”gF­é2˜9¥\\B’¤½\"ï)2V0´3š âª@´8Óc4”¥J”UAPfñ1—<4@ú±Ü´§J´½8§ûôM”µLÔ¹JÓ[DsŽ€,lq@„áM®hqÃ:”Bò0yH¢Hô7´ÉUX•\nÿ…Oô? #c”+uotG¬÷RåYPÏšï²\nü¨@¨> ÎêûqëT®žþ”ç&tI9_MÓ™U•OUÔ¿Cµ!\nÕ‘s?M”î€`AVÎ¡ST?XuMX¦öù…‘8>Œ<îÿ55>cÎüÉÒ\$¯S«N“ADµƒNùZÕC[1°TÑ©L2í\\%fïêó\\°°@ž ÐaR lúúâæ³‚î]fŠ`ku\"Ín‹Írøµ£p\nDLÍ:§,toÔ6fnˆK¥!TP\\èkaNbQk`PÏ –569°çIæßa‚’ˆ¬\r€V`Ø\r Æ\rk‚5'2mkIBx+\0ŒþÀÚ©ä\0Ä©+Þ\n ¨ÀZ\0AJª†G€Î÷/B8Ï!Abºô­XËÕ¶ÑÏxÊlŠ®ÃšÔüÒ5¢ˆbf¯€	¶šÀòÊ\"î'etcŸ*Â\0†FõfäN\\\$¿[hKð[b£\"äM%ÃfcœÆÉÓb~|Ó­N±…gMˆSêú¸hcn\0by\0@	‹ä–s76¨	ò=äAfqÏ.å¥Yrv_¥;#\rjÖŠÔt@€Ê‘L­1’ƒ5òÕ\$óa–c3wÇòä‰q‘W·nâ/7”³V§ ?ƒÖ=£ßhj@\0Þæ W—côÆKC6\"r3Þ¸¼L»uÆŸçémwöØÈÆÀw2òåQºå—êÊqdwQK-—Üã·\nÈ7Îì\"à4­	 @¬ Æ ê\r³+	÷‹Óü†(`UÂzÓJÉ|ÍÆBsN08,@dÅEýW1£¶_#¦Y'eF+7vI¢¦U%DpQŠ‰7÷.„Y\rÌû†ã…¯ñaÄÅb4·M\0æL·„R‚»\0s¼ÿæàªê¾Xïðã¶tx )uà	\0t	 š@¦\n`";break;case"tr":$g="E6šMÂ	Îi=ÁBQpÌÌ 9‚ˆ†ó™äÂ 3°ÖÆã!”äi6`'“yÈ\\\nb,P!Ú= 2ÀÌ‘H°€Äo<N‡XƒbnŸ§Â)Ì…'‰ÅbæÓ)ØÇ:GX‰ùœ@\nFC1 Ôl7ASv*|%4š F`(¨a1\râ	!®Ã^¦2Q×|%˜O3ã¥ÐßvMóÃA†\\ 7\\Îó´ÀÎe9ˆ—3©ÀÈa:sFƒNdépÉð'˜éÐ«ÖËtFKÅèÝ!¦vtÓ	´@e×ñÐ#>¿±ÇœÍæã‘„×ßßÌ ¢œ‚%Ö%M†Ã	º™:ž»§I÷r…?ÏÀÌF˜ù¸Ò 5ö»”	ý\"iñh`tÊtëTù;©ðÆ¡Ž‹Àä£î£òŒ#’Ý#Cd<CkºëLºPX9ã`Ò*˜#Œ£z˜:A\"cJÐÁ¤V‘:ƒ¨Ü:©í|\0ú@eˆ(A£{¸\nÉx@·ŒPt#½ƒJÊI‹ÞÆ¼…Œ0Èæ2˜e;0Ž	óX£ÐæÐÁÂ:49/rð6¯\nˆÊ©ÉDøèöAëŠpž*J¢Ë9ÁÂÌœøAe‹\\Œ‰³:4%<¸2Ä´#9cZ’6ðk_5Œ­Ã¦ ¹ SI,½c’è#®¢‚¶JƒÄùCš|úOµR†ðÆC`ê©Ž«èòÜAíóO;3Pk{*\nbˆ˜øDÉÕr'³p´æ5„ä£2È5¸îªØ2+èúMÓ„÷:¤òE@Ü3Ôår\\Ð°A³ØëH6å#-ÏWGIJVÛ£ÐÜ³!#[OˆÐ×5Î«•ß[^Pr]{.0rÜõm\r‘@aéðÖŸIb Þ¹ÂÃpòFCœh1³˜ÌíÎac49ecÎ0¥‰e€ÆmP9…)<IŽH0ô¦)ÁH@58Xê€ä6§ÖæÔI#¶0¤âÂ²Á²ó¸K9HO®’ãÊ2'É=™yŽ­ò;1Íê£~:9è}\r‰öñ)oIØå¾¢30÷¦c`È:çCbð:É‹42ìpj1\$¬»¬ºúBhÃÀTC‡V9Žé-\n2ƒØ2Œšàx‹Ê3¡Ð:ƒ€t…ã¿”#&ˆÉ(Î¦> ðý ÎX^X#“.:x\"úâîa|\$£…47à^0‡Ê#3}\nÍ ŽsD4°£‚<ÊIˆt\$áTñ>Â†]H‘˜lD„79à Ñ‰Í\":@ÁAEjÊ|SªÞ\ny…O%U¨â<Ó€i\r¨xÄ/âXÄÉsÁ™U6õP@Ãš_€µ‰öîÞA\0Lsnt”UÒG ©î‡ÉiªòNÈ96/ä¼-\0 Â˜T6D|ƒ3¨`Ozª-‰D“S>ü¡ál¤j½ç'ƒ¨yèÉ‡¬% E9óqgœ7sOÝe¨7¼ØxMƒHgc†b`KCIÁRÕ\nƒ%0¯ð`ä‰ˆbe!¹ÃÀÜ^\r<Ë]–TžÊ\nA<'\0ª A\n¨‡pïÛï5fGœSá =‰\\Ë9j(L²î^ËðÍ0AÃPR.eæšfhKGÇ.8´v°‘;¾œ¤ø· `¨u\rgR@Ó¤½l9KçÎ# vbœxmIˆÈô‘–s`‘Ó3É¶¦O+‘ÐAm…MCÙØuKkKðœ„E³Þ½Ï¡'^¯ñŠ©SÎD&èd\r4‰!R†•Ö²~‰PÁn¯…àÄPÙe7Õh¸„øk/l\"7ÊƒšM`éù3¨à‹ ‚f”÷“¿NjÐfä}ÒÇˆ~,I9o/FVÃábw%‚R–I¹J³ª^€PCDÁÀ0¦¦C(c4!‘Q˜LÓhêúBÍ)3¦–[y¯œgx\"‚‹ljÿ‚«¦d'CW)çœ¾#¡šŸ®¤øD¥)Ý•„–ã€g½¦Yö”À¸‘bŽT1S>iFÐÙIØ+cì„X°iØƒJY…F%ÙÙVÎâ»RÔ6tpPéºr^gQéXÎ”¢Š[	}D(§špÜºxž\$[nÛˆºY-Î&ÖwS”-ß0·nb:LiP.!Nê¨kÎ,nSp÷®Û'\"ƒWï„å¼Òmßhc~mÞ­'œîH ÖI….¤E«É„ý\rÀ7ª×a:¿~®ñ±ÃÑÏb&ß‰/f½Íçâ\\”ˆ‘÷L\$lŽ°ŒtFqá/ .\rÂÙ@ØIÂš2F—YÄ`îCÉE=—î¿1ÉJKø	ct\rº+Î•¦pˆë0`ó<€ëRŽ ,”‘‚ø]†…DÚÔ3w)B#”¯?ÌB2/s0;wãBM éª–Œ¬& ¹m„,E”\\d}ËhM3t\\qÚrúM`<ÙØCy›AÁ%)X¤-œÐ¼…gL!­c¬õ^¶Ms­Lb§*%Ž`\nŠé²A„,‰,Ïñ4Ë\\ Â´6êÜO‡Yškå#¦«ƒKAm	¶©q?Uà€Òä=ŠºôæÜ–è1¤ãÜ³[[tv&\rÏ2tí,b‹{v¯ÞÅ×iÛº“±…Ý¨æñà¼Mn¡\0a¢˜â€ó}zŠž½åø›apt¿ 'Š›Ü¹ÆpG¼7ÕÂ`ý-§•*‹ìŽchÝ÷I;æÚº[EÌØ~þÔ<+?Ù„&&²ÈÚš4®\nhvbi#¬=ÄæuÒ,ßJ9gš3Ò¥þMŽÒ2ê©€ÇÞa>¸L‚¦€¨8|	)0,¦ç¤«òåglªœ[BÒ¦am³SV²á»ãwï®ù®„ðÙèßpá¨Ñ…ÑïÇK;“ vózòüëtèK`ºÔ[ËÏå¿9å½•mTéb°/•Bå½Bµ†²(«œ”ßx˜ýq[ôÇØÜóÞzßèÉ:\rÊJ05¯Ìl4;›án€ò”iRŸÇŸ¤H¾åÓD‹pÚ²ðéz†ÍC¿ß~¿Ãã÷–Éùþt¤XèÁüÿw•Ù|Ò”éäýï®¢ÏoNÿoÞµKPó	 óŽ¬+NZ\0 ¨´äàþp µo*E†;ÏJ¹£æ@J^ÿ£þ¹ÐDÞÏªðFðO\0ˆÌ¾;¨ô÷o€1,ëÎZ›\"Øb’êtü”R¢8ÆG,o%ã«ÀN*wpfJLN±@à\"Ä~3gÔäÅº‰Ë´P.@å®D¥p’ÁKçÌˆåÆÒ=‰úrR=‚˜Z‚âXÏ£Ú4@†E\0Ød¢/å¯®‘æÀLblb\n ¨ÀZ~Ñ'ì8Œ8P®>Ü†˜Œ&¡°Ž¬Æ¾NV›pâHCœ`ð¢Ó*aE4¥GS Ì\$6CÂÊ¾ä6‚òÏÍ|ä„óßPä&Lú`˜¶!>¶b|_…À>0Lu„(hU¯\\ú!x:CÜ\$†•.†€ômêhŠ‘š/Ž ÷¦´ZËé‚ÈÄ&;-(éŠ¬ÐŠ¬GÇDÃ¦†çÔŠÚtOÛçDsÈâŽb1¢B#Nþ‘ÖéƒÈÖD[ÀäŒêhXQNÕMæ–Àôž%pýq\"-§\"`„\rçJ¹•`¬\"žo¢ú qòŽ‘ø4G¾N†@#@ôMbø˜ÈŒî7°Ô£]#&Øc¤˜‡¥°£Í¶Uâ/éØ-ê!\nP¨J“ ãœ2¨VCêF„)£>SÅ@";break;case"uk":$g="ÐI4‚É ¿h-`­ì&ÑKÁBQpÌÌ 9‚š	Ørñ ¾h-š¸-}[´¹Zõ¢‚•H`Rø¢„˜®dbèÒrbºh d±éZí¢Œ†Gà‹Hü¢ƒ Í\rõMs6@Se+ÈƒE6œJçTd€Jsh\$g\$æG†­fÉj> ”žCˆÈf4†ãÌj¾¯SdRêBû\rh¡åSEÕ6\rVG!TI´ÂV±‘ÌÐÔ{Z‚L•¬éòÊ”i%QÏB×ØÜvUXh£ÚÊZ<,›Î¢A„ìeâÈÒv4›¦s)Ì@tåNC	Ót4zÇC	‹¥kK´4\\L+U0\\F½>¿kCß5ˆAø™2@ƒ\$M›à¬4é‹TA¥ŠJ\\GB›Œ4Ã;äõ!/«î¿(+`˜²ê’P¤¿ê{\\’µ\r'¬²TÏSX6„‹VZ(è\"I(L©` Œ¹ Ê±\nËf@¦‘\\¦‹’š¦.)Dæ‰™«(S³kZÚ±-êê„—.ëYD’¡~ÈHMƒVƒF: ‚£E:f¡FèÑ(É³ËšlÉGÓL•·‘A¡;–Szu CD´RöJ©‘`hr@=„¼®Á†BƒÎs;ãMNrJ¨Û­)ŠS3NéjfB£TÝ…ÑˆÑ54T4´62(Ñ>É«)ŒF#DMRD¨kgVhI…t˜—;ršFêöH‹¡ªeŒ_7iŠ]EÚA	MªüH”±\0Õ¨µ.AÂjã}c\\ñf‘·-Ýë7ß³bÐ\$›Gm¶¯úJ«Ý)ŒÊ ¢c\"Ð,IxâP¦*ÏbøÎ)f%óyenEÊÍ×O”Z 4k¡.´,Éå­ÍžÄ‚5oA¡Ü%­[4d5¼ñA0é²„„P„E­(™JÈ}3;áP\n’X3¨rvÄT0Ã¨Ø6:ï+¤ŒcÝŠ\"d>•áäa\r&žŽÙ²Rno7Õü¤‡!°Z5B·ÍãÓéKéFÂ÷ýî™ÀxÕÒ§©zuÉ)<f”h¨îÂP¦ˆ4ƒÊ]EzS]S7Rcõ?3Usw/e¤f^hÕKÖÍeAÚ=[ÒÖœõ©\n`¦ŽØzåÃÚt?C×Ñí–K½C`è9d–lP*\rîXÛ·!\0ê7c¨Æ1º£˜Í³`Þ3Âœyïý#ÎP€ €,`Ô Nð(`¤µ™£v¡‹ªÆY€€!…0¤šêçp€¸‚Å\0WR™F.ŠXFhW(Ñ\$%±nZÈ\nf È”’w´’!	5]-)Z›apZÕqQeÇé7\n†Yâà?Ì5Ó¬D\"[&å¹£ö\0dI*7*\"1\"æzM\n8Lèì›+u\rá`Pñ!ª(P‡ÈjF'40‡3¼ŽduaÜ7‡&\$CÀp\r!È2†H8	Ä˜‚ Ð p@¼‡y,ƒd€Á¸2‡ ]C8/¡ºQƒ¼ûHo”€‰´‡#¨\$h_<Í¸5‚ |Chp:A¶Q‡@xÃ>K2ž\0ÞÄŽÄ!¬äès\$ëç“¸:@Ól×ã—JfÍF˜Â4oéIIBÜ\0lÙJ3lú¥pPYÁJ<…E(§8–BŠ¡O\"9ˆÂ¢#âë~?É©‚ã5ˆQûdÏi_ñ,ìèr=uGô§–¶±?Ï±vZ„¢Eb„Tèí\r!¯HÞ¯ÂoST%\\&!°PW:è\"\"¾dPp´£8Á‘É*‹®97¸&sa)g=\r:·”zC˜*ž‚e4X­\$î/'(O\naQ ,UWMKS%Ôþ~BASFO †~zSÇQÒ‘]„‘Pµ)=ÜÌÜkÅHÙL4ÎH	q8*ho³Q.p m¡¹õK ß&A‰A¤3‚\0¦ÜA\0f:' èHŒ…‰\rÌH4Ë˜û3æu‘A¼6‘‰:qäòaKÁ®2eJ4üMÊ(ºŸ³|¡Wj‰È)›.Q i„Á=\$ùÞ‘Á³î \"aâ364©µ¸Æea¦Ç>ÞHYû4jfDÅHÓz£R¨¼&2¦/Ó²*kTêü„éXj¨-¹ÅR<ˆSsFUH,ª+\"›V–¢—\\aé¡éæÐ­ò¯[ª}9R¾ÒaWx(¨ „À¾\\êédÑÔ)õÍFUSÒv+Í:Ö°¡t:ªË‰š¶QÊž‰âXµµ¾å]‹˜zt½d:B`éœZàŽ(h²¹d£‘q[ÕÁ'Ö#óôtRdNr#®L|ž'ÐDj2!EBÎ«´äœÓ¢u8:å|×íP,ër2¬¸p‰‰±ºYƒ5[ÐëÃSvÙ\r[’VAÍ¼S)Ž²<ÖZƒCHz (!Ú@á4XS§H1àÉ†É‹Íxp9ÄšFçÞº£À’¯²Œn…¥å&,Ñ[Õf¯Šk+Enð¤”Â×ÔY`t¹ëZæ‘/Ö‚½\$‹45=(ùSÞ™ÂÉÐý«„:¥Û	ö®F€¢vëíW©üj02îÔ¨T@3tÔo‰Ó~wô~0ì+¡G”ænÄU¦ô˜†V)#EµÓ”B^„ÜyØ”D§+Ü¢B‘¡PHLÞ4\rvªhsÇÇï%p\$ƒKMb|á…TÐ£¤{[®ú€Š_TÑtÇMÇ%2¢ã”rEÃIYÐ=-ÑÊc\nîÖÑe¶»N8Ó¹òäÚ©'äk¿’®p\\y“æ¯iDR>s–g­­#üù{N.sÐ×ê‡ý#™©ÄÍºkN\$ýBÛu!}Õ5ßA,ÌK—8«oB5 rŒ‰ÌÃd}ØoÙ¹'†óN¿Ó5bçM/žþßW^ÃO¼tò¡á¢SŽ>ŒøžCmûU]Çõ%á™'x6u6ób€p1aY„	çõÄòøGð™.dØ×ßP£§¯„žˆx«È2¯{)oÕ‹ºí§Õ‚.(_ro®’Ý2ÉRõ%¸]Ÿ#V™iyð‹y=!SFTÊ6‡·Ðj&¶ögvU'‡FïñÕhíÎ.ŠdOEˆÛöž¦áómú!/ZñAÅ¤†®lÆD@¨nü(0ØÄ>ê¢ã0aá,ð\0Ìúym\0÷|LMVDÇœŸp.ž„}&ÕM\n4G`ÅÅH+nÒkp=&Šˆ¼Œ+®\$ÉÚ%I¬Å+¬ymYkh]â §å˜OÂ´@r£Ä¡	\nºx6éÕ	ÆÂ=+â2LÐS'z!­l£ÏúÐPF†Ð\\è\$Û\$È×°¸×çFH«²B£F„ P¥b½bT0 »Ç²Å.ÉJ°Ã\0í3ŒNTÌtÉ0ý°0!(7Ê´EO7oË‡©æyÄ6È	ºôK* _)Äçî­çZâgNsÌ°úzÇWçµÑ4tBñ)Q.VŽ‚ˆ\\Ÿ,‹°1qGHbLê’GDc‡¤îäLŒf¶ûƒ w®ŸFLGéD‡Ñ/&ée\0¥IQŠ¨‘œ1 î§NÖîD†‡Q|¬±EO\r‘ó1ÂÄ1ÆàñÌEñ¨ò±¬ˆBßKT!kXòEµåø¶Q]±2 Ò\nÚ†ÚÒ±Cé-¤ò2%!b20ÿçO!ÑJEK´C/ØGÌª£Ï\r‚®.¢Vã’^>¥VžFZ¤Ã,?\$6aRN¡íâ{RV‹ÅÀ¡C rpº×%²y(\ròÊ\nLå% Ýˆ´ŸˆHŸï½%J\"KÂìxB¨r6µ²®â4Ù*Î1¢Xk°e%2×G&qœÀ¥¬þ0Ú=ÞdLë2#IrúÕD’³_“	\"Ðo2Êóõ1EüünÓ\nÆ3Œ>F…µ/ð_3„–+³11’E0:Ù‘Pý“<Ÿn%Ó	SF[MÌOØEdí0BX ÕâdÈl WR¼Ôèªø%.Jp²Cî=Ãhì¡®^s2Çm3“ž4ñ¤ÇQÛ\"óCSœàÓ¡;1{Ë.O²?eH¬f¦=ýs¯.\rqLÈ%­=l-=³I5ó#(³ç0„ÕKÎžHV†ÒRÜ‰ÄŸ©þÜÄèÞÄTµ†À¥S·‹#%Ì.¬š‰r KÊ»4Ës1ÒIC4q4#CÊ×1†0Ï 3W*¥ùDÒÇBsüâ“d¥”cT:.”iËÏ:qa54}&´@yGE²0yRh½K?æL‹ÊÛmÉH7M´Ÿ\r¸+ÓA‘IJíÆQ’CD3Í“à×‰ïKâ½F±NÜ4¤V! NDû2ÓZS-^âæŠã0WÎí0Ô/\$”êM.0WùKqƒHTþV¯ú‹E<“O4„¤*Jr–D…<±ç²À‚R‰­4×0>÷SÒSñ»Ïq8Åß9®c2ˆ¤èêkOSŠùµPŽ1Òà±žŠRÃUaTµd%…VŽùUnÁd-ef†²¾Wœï‹p(â›8/žVŽ×•mU…°X3ÉEsºèQùaUÕGòíQ«[ÕsUõI09V¯,T.ÞÑ‡¾\r€W,Ã|2«YE/%0®÷‹\\žj¿íwt\$ '€Œ²h\n¨ì\0Ä™«\n ¨ÀZ\0@”`Æ˜ä îöíŽ‰Au\$1Þ'Ðå¶:ëV@wÆˆ»Kn°åNé4ÖBwåßeŽÄîî&J’tÎl[ÆLÜ0>ÐÓ\\	¶0ÀòBadZCt%.VCêD¤,?n-PØP¤ŽI*b.¯ÀiËé_ÃÕ`æÀÑ Ž'\rFòÀB’GB4¥‡–ö@	‹–3nvê€¨ú9¸<‡ÊHJ>¬Ž¤ÌÞmy[(*2\$6hW\$—ðCqU³Pr[3r0@e²\0Qù`dK¶ƒq0F†Tø·eÇ7J«Â&Žª%5'u0Quw&OGµ0eÂSáP^ÎÇk]ð}se¦Y@ŸPˆ+Ú¬\nÅxWyjœ*Ðh0¼¶ã\\ý)Ä£”=üë(­zÀ¬ Æ ê\r«Ú?w`M÷YEf.œÉ·Ö{Œ–Q­;6%rŒõÒèÎÏìCwöÂw/Îë\r1.be†LÑYíŠ.7ü#W=4–¥AÔsC„@Üð˜a«`&-R l*Äö2l´¡À";break;case"vi":$g="Bp®”&á†³‚š *ó(J.™„0Q,ÐÃZŒâ¤)vƒŽ@Tf™\nípj£pº*ÃV˜ÍÃC`á]¦ÌrY<•#\$b\$L2–€@%9¥ÅIÄô×ŒÆÎ“„œ§4Ë…€¡€Äd3\rFÃqÀät9N1 QŠE3Ú¡±hÄj[—J;±ºŠo—ç\nÓ(©Ubµ´da¬®ÆIÂ¾Ri¦Då\0\0A)÷XÞ8@q:žg!ÏC½_#yÃÌ¸™6:‚¶ëÑÚ‹Ì.—òŠšíK;×.ð€¢™„ìi¶n÷»øì¬ÛÀ€ðÁEƒ{\rB\n'î¹»Ší_ÌÁˆ2œka§‚!W¹&Asv6Î'HáÈÞÆ»ÉÛä÷ ÉvO„IvL®Ã˜Â:‡J8æ¥©©B‚a”kºjÊ*Ì#ìÓŠX„\n\npEÉš44…K\nÁd‹ÀñÈ@3Äè!ªpK P›k¼<ÈH\n3°Ã|•’/Ð\"1J'\0\0P¦¦‹RÙ!”1²dœì2V‚#I²pN¾¦ï&	¨	Zþ)è	RÜˆf1B‰§CÖË\r‘Ü˜„ˆA¯¯™Z8B<@Ë(4=9%3÷.—sdn4Ê®ØÊëÏì»3-PH Æ€”±äa—Hl`Â\nxëD˜e`Üô9M‚ß&0î²2/#Èè2…˜SO1B„§Jv7RUâpJ®ÈñGF\n•«®5¸%û½¯åN]•2†Q7,tW¥Ã³FG	AQ±6’>hv4D4È	 íI/+|´¢ÊÑ4¶\n#©†T¿ƒ£ºP ‹t‚¯omÎÍ\rŠl¬)Š\"c\rh¤±&IƒÅ>\rÃ41¶J¤‚¦\"dL>c(Zi æ™Sì*˜\rèž€6°¯quT¿µbw›g\0VÕmcúÔ ´£ÁE%©u;¹ƒ‹–·Œ4•¢¶+`Ç=O;ÔÊÔI3sŸ•1CbÑ—»¥èÏÉK†Z¢c\nH:(©\"Ÿ'c->=Å7î9.QÆÈëÓŽ«+4Ø•Xþ\rÌ¨Â—HpÍ¸œnºÐ¡æÂÂ<¯/E4¢S%\$ƒ@A³OÉtUŽ©u\"#»ëø<?ã:]¥>úcš&2Žc˜Òÿ¥Â…ù3¨…®ç©©Ü/¾eøéÛC‘#’w%ÕÝ«Då:>s½Íô;/åòKA\0<(a 9PÌAhÐ8 ^Ã¼Á„2Ó C.\ráÈ3‚ðÊ ðxXa¹íð^ˆàaƒ”:@€¾ƒbU\r`ˆsÒA…	2|!´ðÂ™áSmÁq‰‚ŠA..…E\r=#æôP2AL¡À8ÒÀYàºçð(€ Méš#Â\\þ“„2Lš)H)n%X†‡_¡K_õú?2ÖEÓÑŒê˜\\CÇC!Ù5±F‰9_µÙ\n'K±Å'é@¡ÆˆœÃÎ!þ?èþ;çXðHÄ\$¸\"7fIÔ%D°…\0žÂ£Ž;ÎìœÀ\0ûb{[dÂÙ\nN‰á(Ä[“¸ö¢\r*¨ŒJÕ+ˆæIŠ³¼BRŽ*8šæ¢W\$s¢ÃÔ›‹°F\n‘|ßgâüç+Emiìä§éL ž*;Yn.Y‘§(Ri#iQ˜²H©ß‘(œíü‹G˜w“4Þ\\gí—†®rQLžéBŸ©¾ÀL2>d!1‹±­Ôt³-\"ÌGÌ”EÜH_j°šKÄ²¯LIØ8J1—XA%YVUd±}Ób>AÅ-dl2Kô‚w(CP	‘ÆyCARŸÛ¥ ‚ƒŠžG¨ýOfìå³Õ¦¢•°sKÈ_Ó©˜–BÝVáP´š¤EçÀ\n{Á¥ð6”\$„c¸(‹±|2Â(?	CuxXÀ(+óÕQˆâg°Fè¢t½ÜŸ•4Ï¢^LIœ¾&ää†­‘ZŠWÙ8£çm¾œÆŽPÜZÕŒÄ¡Ž’h”U¢¶Š¦ÉÉËB.ÄM%8Ê¸×¬ËqnŽ:3£ET<{0®]0[qºz†z&ÝÃÅÐ7UÒo¸éb= ù¥ÐHèj	Å+JÜ‚o¡Ò‘D`‰\\ÐJs½Q´1BÑSM›SGÙ‹ZëÍ.ë¢Z{AÌ’I’	Lw3ð5Ty®T·“2kfÄùvDÇ‚:	¿‚ª¹h\0„!YIØÁÎYR‚]‡ˆ6µ¸™¾u˜@œÉb„ÌƒäN1^­d‰Ã¡Yº­ÑâÍYñó\"MÊsTX°‹”6ÊIÀT²1h|	0ÛäÊS	4™Ëi/Zq0o˜»eØ§ÏòñÒI·ù\nZ\$t‰áÑÉ¶’„*ò†U×xŸ¸2ª¼a ‘(ó zä€Û­ž)(á#¼K²ª\$u³4=¦t’D(2QÌfÏæõ–xÜ_’“qO5r\"â‰S/Åt.…‰l{Ëœ‚L2L¨IñÖzö{ì\nBžé)¶öåz£¨ìÞ¥m¦ÛQ;}³	ÅÁ=È,þHP&h¸iXëfžUm¥t-âÔT¡šX™½R´b*÷Þ¯ÍâM4îìfµm\n4MëºÍUhÍÕ¤o}æÑ[£AàÌÁ÷3ÞÂ”m¢¢š3¯”l¯\\rºs†‹½Û‡°IµíôòmmÏI7Iä“Úicm¡ŽA¯äw7“i43Ëä6¦h>N#rä'+YÛ‹šÌDìšA“z±wáÕÊ½9·†âÞ×¸MLž†…ú/AT2¹oƒ®L£Fz‡ªÊ™ñÌÜ…ÎwšDõ€Î¸ë4eç[¡PÂ,¬Í™VÛ<HêNb®mx3}è‚rz§Êm‡y™ó¿vc=}š*²¶*ßBrC“|/ß<;Ëô¦=Áªï¾8ÀÛßD©[6þôìz*Šóù†Å9¹	qÐêªÔ’.óµV>Ð‹ª×*ÆZœK'¾1{peó]Ä’|x/è7÷Îù1ËÍñåñchoÌSœ’Ü å¾ÇßÆvƒcë¥HËß@5iN\\Q¡ÊïÐÞ†AÌ6Ñ#½óÑCþˆÚˆ}O´ÿoìõ-è|ïø-OˆàKâxäò.–ô¥róÄBmãÌn%\0P“ðäŽœ¨”ÿ{j©¯ˆàîÄŽÜ PVAã¾ûðVIF^MbHG:½ð ÀÐ2¹©M¤ßÐ¿0d›ð{j»3ŽDHDˆ¹ƒ¾–çÄ±.ŠZÊhUP5¨5ƒ—âw\nÃVVì<½ƒ²¬ÅlÆ®mLÖyÆÂB.%)Óå~ìG¢ÆÎ8ˆ&h%Àœ>aJ €†-\0Øè~\\Æ°Äp¤DÊF\$òBŒÃ¢HŸ‡,ä‚ô×#JG¤ì €ª\n€Œ pÞéÎ%\0JÃã&¾†äµãHS@o\$P	à¹\na¤ªrÂšOòýŽ’/Át?òÅ\rðûQ\nÙ3bŠ6¢ÎDªI€Æ¨N'ã6IE>¥ªbëqZbFªMÀºÃ„?‹À/nØýê¦%PžAA/	-A\rÎñ¢9Ä!)0yå–ŸQÀÊMÓ.\\ï±Ö'ÂH7\r0Wq\0(Ï\$ñKd[!X[eº^p&ë…À\$\0©Ë,O\$öLc„ÕÃxÖÎ	j\\&µp(öR2³Bp˜\n:ÛvÍ1X•\r’¨epˆÎBI‰J£Â¼Ár[ÕfÊ×mi\n\r€	g~uÑË|ã\n	‹A\$~=¤zj…\$¥î ¦Ø²9!C44hr§²”ð¢\r1Ý\rØþª ¼Iˆ?‹Zm€°£:S5Š€Yf¥CF* ";break;case"zh":$g="ä^¨ês•\\šr¤îõâ|%ÌÂ:\$\nr.®„ö2Šr/d²È»[8Ð S™8€r©!T¡\\¸s¦’I4¢b§r¬ñ•Ð€Js!Kd²u´eåV¦©ÅDªX,#!˜Ðj6Ž §:¥t\nr£“îU:.Z²PË‘.…\rVWd^%äŒµ’r¡T²Ô¼*°s#UÕ`QdÞu'c(€ÜoF“±¤Øe3™Nb¦`êp2N™S¡ Ó£:LYñta~¨&6ÛŠ‹•r¶s®Ôükžó{¾”òf“qŸw¹ß-œ×ü\n–2‹Œ #*«B!@éL©N…zµÐ¨@F«÷:QQãW­àÏs¡~™r.“ndJ¥ÊX’¨ËŠ;.ÚM(ìbx¦¥¹dè*ŒcÚTÄAns–%ÙÊO-Ç3¨ì!J—ç1.[\$¹h´¤¹ÎVÈÉdŒDcìMœ¤Al²¤‹‚N-9@€§)6_¥éDî’ë£Þs–eÛ‚‡%ÊyPœ¤Ìž÷B¥ºF­ys”\nZÃ±()tI¬„Ì4^’­ÙÌF'<Ý\$Î'I\0DœÄYS1RZLÇ9H]8\$™ÌO±\\s…ÉÐSÒ1}GR’ê¥)v]PJ2ÐE%“Ôù?H%í\0\$Ý*H	i Nå¤–“—g1¡—¤iÎ^•ÉiÀD}`L©öKÆFr4Vž%ÅaÍBPÅÓÀHG1ÙÊE€#£`ØÒ6Lø@9ŒcÜ\nbˆ˜r’(ñvñ9Uo•)DO\$=”þg)xôœ»sLR5rÍxarsÁyeG1Å?ŠbØÑ‡Íg1LA4Ìs¤·0—Ž®Xrë>3ORtÏ@ÍSf9ƒYUTúTC`è9%¥™Pt’H¨7³mÜ<„¨Ü9Ž£ÆÑc5Ê\rƒxÎæacH9jÎ0¹ÒÔƒk˜:µa@æ¶±3D¦)ÁNRäI«`\$jIN‘äŒôš®§)JÁZ6@B¶áO±‚ž°4ãuJ_§IB]V„•ðŸ3Íóµ,ûñ\"></;Mü‚î&Œ#›V93=èæ;ã•H2€Ò9£ \\ƒ#æÁèD4ƒ àáxïï…ÃÉ·\rÃ(äx£8^2ßXðÕê|çÚ]“B:zÂûgw\ra|Chp3áµö@xÃ>(ÆŽÐÞ©\r( iÁ¬Ê†èf_3N|¡¸:7Rà\\áþ@¼¸‰Äø%zsN¤è\0!?éGº“ÜCAG!‚%T<\"€­M&0ta\nì	q0&Fš“rr!\0æÂÕ@0U\"^_ë|P@³LWØ¤8p°(ð¦!‹1d­˜ò‚\$Xrè@Uü'¢ä¤B¬%ˆ½°¤1†8È.×<iÚëâH1ÎˆL˜ÏS:ô‚0T†¹R˜ñ`¼’aÈ7†ÕÖùŒ£ç-K4…Š9…Xâäðœ¨P*Yk-Â E	^¢Á/…ª_-<A(	”†ÒVT_±L¬Û¢–ƒ¤]‰‚l)(€£ˆqFÁØ\0ºÃ¦`èDT]Î= §g€ñ7hX€“‹è9óÃ@YŒgÉú„&ÆfÆÄ±ˆTñ‹ñ69Å@‹G)5MŽQ&\"±dGH@&bŽEHˆZ¨ÁßZ…ÁB ›]ÊK´°9ÑnáÉl±²Ìñ©°¦CÐeAR‡áRœÑŸf¬2Úx‡HÃ˜Y‰VvÅâ\"”RÕ÷Ñ²È®˜2qa*=H«‘EZIhºM¨ñ@!Ø‘-èhE\nc+D¢ÅRbš	³YDCf¢žÂ8›ZØº Âht‰1|9„qƒ¨lµ˜ž1|Álì°e¬]	\n1F–qù;¡›ª*¢â%¢|RžJÞÌZ@™T`‚vÛ;j+Åüó´ƒNá^%]›l·‚„FQá.’Eˆåb5AøM û¥w\nv˜Ë”Ê´¶È4Y9U¾‰DMÃ¹ˆ*ôÛÔJù!(ž”ÑpH	Úì™ÕvQmWÒU[éa-\0 ®CJö÷‹jn}„„íÁ©m	A2‰…°å\"ps\nÕv.Ä*×PªÜžA^Zr•èÌs‡BxKÁz/†£ˆ:ËAôü›CžºÐ;Ê&Å£‡Çxô»ÞIÞur®A9Ut«Z™Éù1Ø–i”ÉbN™U¤s	1wT¨ŒQe\r–äs˜YˆÍnq„”kmhŽè–VêÓ43*L‡¦jMhvZ‚ÂâÃ7eZ¯Cqó…	DeK_£IeäÎN‰Ðœ‹•˜Þ•¡Ù6~Uø¯£´Â b¬ó(úØÄõ`.ã X4'im;÷kVjèoK®Tí»WBé]MAUëü®ÔºŸ`Ø}kö,k%ƒš¹Q±Ê\$„f<®Âg:D†„-?„Øð\\¢N£ŽxCŽÌ©Š¥,6 ˆä|rígF\\(¸YÛ92%3¹î‰ôw!£z„r,¬•ÕÓi³m&Z\"Ý}¬ÚKSe*Ä‘ö>9<*MJñ\rAb¸¢–‹ú‡Œ¤}Agåª)›\rLò\"QÄlW&%%ßIò£»ÊEÕ«[Ì¶åÊ ¯cºw…¸\$s	7†êòrÂT-5Ö\\‘Hôb²yC4éÝ+¨rÎÓD:®0ìIØZ~_ÔºÊ¯µývŒÑ]ð&ÄöâÌÚs£¬š7ÔTþ²íùSI÷-Ôc;¯nÝ]¸÷¤CÑx“€Ìˆs¸f9¢\"ûÇW=«¾xlóà2uŒ°¶¦)/%c’×…°~OÌõmOæüÅˆã^Ã`\\0ŠQ_.òò!o+ëE¿…ö0\$;ÐÐóu‡T`b:•2’Ð.„üü·gmÚ{ô.éV•e=\n,I°¡(ÕTàü”—rŽÞI¹ÈåN‹Û£tÌ`Cha°†0ØCkµé`Â©¬i°<4†f¡¨F’°H:»à@H*sÀª\n€Œ pØoè9£j×+˜è¡^e£ï4#z¡aR<‹jS`šþìùÁf†.!ÄãŒÆBú>d'#²«.³+þÒp4œ!x¡ABª²Ih’!,¾	€Þ|C˜9Ð€‘@Úx£*5#b%¡r¶eÄ€ÈÌ\\,¦j.¬|z‰èË\nÈè¡HùŽVÒ.Ø¼\n…Ò4C\"2c+\0Øý`à‰<©š6¬´Ú:RíÊIÄÅn­Èâ-æË¬¾Ž,øš¡tšíè±šËZ1P¬E\$fÂN¶\nÀÂ`ê Ú#x*AÌÀQjæÁFr‹hQÂbLZ°¬<p¶ž¸0,2Á¡±]‰ôùeº÷èÜïmkÍÊNm¨ ïNE@	\0@š	 t\n`¦";break;case"zh-tw":$g="ä^¨ê%Ó•\\šr¥ÑÎõâ|%ÌÎu:HçB(\\Ë4«‘pŠr –neRQÌ¡D8Ð S•\nt*.tÒI&”G‘N”ÊAÊ¤S¹V÷:	t%9Sy:\"<r«STâ ,#!˜Ðj6Ž1uL\0¼–£“îU:.–²I9“ˆ—BÍæK&]\nDªXç[ªÅ}-,°r¨“ÖûÎöŒ¿‹&ó¨€Ða;Dãx€àr4&Ã)œÊs3§SÂtÍ\rAÐÂbÒ¥¨E•E1»ÞÔ£Êg:åxç]#0, (§˜4›Œü\r÷ñˆÅG‘qäZ†–¢SÅ )ÐªOLP\0¨ýÎ”«:}µï»áÚr¢òå´yZî¤se¢\\BœÅABs–¤ @¤2*bPr–î\n¦ª²/kÞÁ)ÒP“Ç)<·Ä©p¨’êY.R®DùÌLGI,I¥¥i.Oc’t’\0F¢å±dtì)Ê\\—È*ð’ëÛâ»/ÉÊ]g9f]Á…‹Ø^K’ LªÇ)pYÊr•ä2´.«ºó)•h¹2]¥Å*–X!rBœóœê\$	qól£@%yÎRPa s-¯a~WÄ¡r’GALKIÔ•)KPËÍ:ë±\$ñÒPO„Ù\\‡Œ\0Ä<¶@æÐ–åìJ\\PÙr’B–HŠÜreÙÌBñùÎ^Õg1IJd}\0Lª1TP\$ñÌ\\u¢xŸ àP¨2 @t’¥¼¦S%¤Z:^“€PŒ:ƒcRÛ´\0æ1Œ#sœ(‰‡)\"^Ù)ÐC•G-ånÔªYIÆKqÊÞ7Ôõ*\\Ô2”©T…D¾QÔ†,]Ñ¯ž;'d´Ž;8Äñm“)ebvž¥¤a_?œ¹ÑÊC—InPsåYô¾<Ú4á¤ÍÚ9Q–­Ô\rIàPØ:S”é \\Ã{:6à#È@:Ã˜ê1Œm8æ3_\0Ø7Œîˆæ5#–Ä0Œã¢oa-Z6º#­cY·HÂ4J‘	¤!ŠbŒÔãXÊ7/Ï‘täk¯>—‘â`¾¤±]‘	ñOÏºÙtÈŠ{PÒ7ÁÊ_ÃÅtEYÊJõ‡Y×v”+@‘ÑjFrÜÇ4Ãõ3«\"&Œ#›`93Þˆæ;ã•[tHä2Œp@,·¾3¡Ð:ƒ€t…ã¿ä#'\0007£]ìŒá&ÿ—K“n¼7ðD¾Ã‘¦¤/›†Á>	!´8@ÚäÃ <á„ óQÍoU¦¨6\$ßPp-üàèáHÊ@Â ¤!ar+ ]¤¥*+Ç0Ž((€¡:	Ah4†‚CI‘pÈ•Ä“QÌ!„ˆè±D•žal‡„Ž%ÄÀ™8¤P9…pµWn`¢Ea8 	ùA,îTùªQÊ\"…‹¡@'…0¨x¤a¥\0¿-ñ6P¢ËFÂ!N!Øí ð qDÄVbâé“2¬ØšÆÞýAåA¤3‚\0¦Á\0f4fhÑ>PŒ„¡\rÊ´4ÁG²ºƒJêÁÈ7†ÕüþLËú%¢KA|!‡H¥,P\n„xNT(@‚(\nš €\"P˜fÂLX	\0rˆñ®çç&J!¢5\0»Wzµ\nœB¨@\$\$¢M‚ ]ŽN(Ì1ØÇhîãÀNˆ »=‚àEÐ’.)ÅÙä\"ðÅÄI6”•‹/JˆPÞž#ÈdZ’„£IÄr´3æuéæ\"Ts‰Ô*ZÕ?)5¤\\\"•Ùb\0sRÜR8‰ÈVÁ4RX€ï\"Äta²\rƒÍhtˆÁQEÕF)\nÌÒx\"Søæ™³=¦%:ÃHz (!ËÐàà«[­4ŒØCÂxÄÉåŠtTš²¶H-TymB\"©L©µ;_“	ú\"âÐQQx’Ç\"“ccžÆXë!1Ç0º]U ‘2ÈÍŽÓcL‰”y…8¸sƒ•04&R\"çõª\"òÖaÌžÒG3–v×'k•ŠÚÅ­:©ˆºZÃ]±v-«)yµÍúd9=G0Ÿ£ TŠ'8{ÄýUçœò·ãKDø·1÷fíÝÐžÞ=æ\"2rŸ±z,GHŸðä¹°f.Å\$ågw¤Ç™8©Õøˆ#—_4«P\n»WpQ`l#\nY%)¤è‹<2N\nqÃ´ps‹a&îÐˆoê—õµH\"ÉSˆÉŒâ_HDU<z¯ž<Wêñ\"ñ9qPæ¢ˆ–ˆ4<¢T^DgW˜Óal €ª°áo¡êòEÌ	ƒgbT‘|EN4UUQÌP~kÁå£ƒ˜M‹C¯=3c£‹¹ÌÃª\\ðª£Ñ,Ä˜›8KFZ2N£–EO‹ÓtRÑŒoGÖg@\$lêKVYãÂ¯ÄnZD‰‹@zÏÎ§3®}Ý%d”µ”±¶>)Y[‡çn¥ñ5v.át)¢-Ójè±çt…t-yÀÏ®çÖ8éË7´šØQqW´´›!i©ÃmJSG6H¤±\rCiR[_¸ì&ÛÁŽhšÿ`D^6ÐG§‘L®oÂ÷Ä2üß}ïÍûÜz¼“ù‹1æHµÝz7ƒZ^¹v§\rá)Ù‹5LÇ(’9VtT%#ôìÝ¨ˆ%ÂÞfÄN\$U}hÚãR<r¨ÓøçFø‰Ú*z†\\ÐÖVXá‘á)eÝÛ|ÕÛî_­'™yæÏX‹AhŒS/±Z/v)…4§æåà‚“«Xn³žöFÔë½`¾ç¾Åa:ºàyÆæ\\ámÂØßmY¿°m;_Ü®zÝâènk©ß\0–¦*E’‰Ò9…zÖåäFH²|/¯£*ZºŽçïr»‚–*\"CËˆþ!küßï\\1\\Ó².^%Èºq×Ò¢oVåç¹¾ó¤´M¢EË'wk<”Èýyû©ï¿™?Ñ±¿ëþ?º¯>ãx÷^ü|þgÅRß3×ýO•¸­M«L\nØý®Ë »çû6ÎÚý&9øí¥­ùöÃôc,ŒŒ‘§€úèhø„;ð¿¥×÷ÇÏüÿa\n2 z Ð|.¦ª)a¨flhAD¢FüvD\0 —†Ò`æN)\0w\$L”ÉÐ…\naz\"áp¢\\,Ý°>UŒ\"Ïá\\¾£ò1\0¿C\"f°\r€V`Ø\r Æ\r`@x'ÊÀÂ\r€ê6&¾„@ÒÆÄ—#tÉV„ êzE\\3Fö\n ¨ÀZ\0@r`Ç	ƒ¤7MÀgÁ\n«ä2ºŠb8¤&¨»„†ÝÂ¬	°™	Å\0á8®Ì˜0¯ò9lÄG‹x°Jôê@)hÞ¯ ;Œj@˜\rçê:#§)@\r§²3C\\6Æ\$§‚æd¡<^¬æø,­î¡Ð¡¡1N%‹ÐlOAÑ(Ä¡&(EMÂ2 ¨_ƒN2Ã03@Ä§'`à‰j­'À7Q^ãNÆíBª¤aÌX…Œ%«Ó­Š×©âçmnž\r~Ø¡Î,¤d\"ËÖõÀ¬ Æ ê\r±\0fzÍ>f+6SØSÑ\"—‘Âh1Sj!£ø Á¥1þ¡ÐCpJøå¤OTåáÌ’ÏÞF`	\0@š	 t\n`¦";break;}$rh=array();foreach(explode("\n",lzw_decompress($g))as$X)$rh[]=(strpos($X,"\t")?explode("\t",$X):$X);return$rh;}if(!$rh)$rh=get_translations($ca);if(extension_loaded('pdo')){class
Min_PDO
extends
PDO{var$_result,$server_info,$affected_rows,$errno,$error;function
__construct(){global$b;$tf=array_search("SQL",$b->operators);if($tf!==false)unset($b->operators[$tf]);}function
dsn($Zb,$V,$G){try{parent::__construct($Zb,$V,$G);}catch(Exception$rc){auth_error($rc->getMessage());}$this->setAttribute(13,array('Min_PDOStatement'));$this->server_info=$this->getAttribute(4);}function
query($H,$zh=false){$I=parent::query($H);$this->error="";if(!$I){list(,$this->errno,$this->error)=$this->errorInfo();return
false;}$this->store_result($I);return$I;}function
multi_query($H){return$this->_result=$this->query($H);}function
store_result($I=null){if(!$I){$I=$this->_result;if(!$I)return
false;}if($I->columnCount()){$I->num_rows=$I->rowCount();return$I;}$this->affected_rows=$I->rowCount();return
true;}function
next_result(){if(!$this->_result)return
false;$this->_result->_offset=0;return@$this->_result->nextRowset();}function
result($H,$n=0){$I=$this->query($H);if(!$I)return
false;$K=$I->fetch();return$K[$n];}}class
Min_PDOStatement
extends
PDOStatement{var$_offset=0,$num_rows;function
fetch_assoc(){return$this->fetch(2);}function
fetch_row(){return$this->fetch(3);}function
fetch_field(){$K=(object)$this->getColumnMeta($this->_offset++);$K->orgtable=$K->table;$K->orgname=$K->name;$K->charsetnr=(in_array("blob",(array)$K->flags)?63:0);return$K;}}}$Ub=array();class
Min_SQL{var$_conn;function
Min_SQL($h){$this->_conn=$h;}function
quote($Y){return($Y===null?"NULL":$this->_conn->quote($Y));}function
select($Q,$M,$Z,$Vc,$Te=array(),$z=1,$E=0,$Af=false){global$b,$w;$yd=(count($Vc)<count($M));$H=$b->selectQueryBuild($M,$Z,$Vc,$Te,$z,$E);if(!$H)$H="SELECT".limit(($_GET["page"]!="last"&&+$z&&$Vc&&$yd&&$w=="sql"?"SQL_CALC_FOUND_ROWS ":"").implode(", ",$M)."\nFROM ".table($Q),($Z?"\nWHERE ".implode(" AND ",$Z):"").($Vc&&$yd?"\nGROUP BY ".implode(", ",$Vc):"").($Te?"\nORDER BY ".implode(", ",$Te):""),($z!=""?+$z:null),($E?$z*$E:0),"\n");$Dg=microtime(true);$J=$this->_conn->query($H);if($Af)echo$b->selectQuery($H,format_time($Dg));return$J;}function
delete($Q,$Jf,$z=0){$H="FROM ".table($Q);return
queries("DELETE".($z?limit1($H,$Jf):" $H$Jf"));}function
update($Q,$O,$Jf,$z=0,$rg="\n"){$Oh=array();foreach($O
as$x=>$X)$Oh[]="$x = $X";$H=table($Q)." SET$rg".implode(",$rg",$Oh);return
queries("UPDATE".($z?limit1($H,$Jf):" $H$Jf"));}function
insert($Q,$O){return
queries("INSERT INTO ".table($Q).($O?" (".implode(", ",array_keys($O)).")\nVALUES (".implode(", ",$O).")":" DEFAULT VALUES"));}function
insertUpdate($Q,$L,$zf){return
false;}function
begin(){return
queries("BEGIN");}function
commit(){return
queries("COMMIT");}function
rollback(){return
queries("ROLLBACK");}}$Ub["sqlite"]="SQLite 3";$Ub["sqlite2"]="SQLite 2";if(isset($_GET["sqlite"])||isset($_GET["sqlite2"])){$wf=array((isset($_GET["sqlite"])?"SQLite3":"SQLite"),"PDO_SQLite");define("DRIVER",(isset($_GET["sqlite"])?"sqlite":"sqlite2"));if(class_exists(isset($_GET["sqlite"])?"SQLite3":"SQLiteDatabase")){if(isset($_GET["sqlite"])){class
Min_SQLite{var$extension="SQLite3",$server_info,$affected_rows,$errno,$error,$_link;function
Min_SQLite($Fc){$this->_link=new
SQLite3($Fc);$Rh=$this->_link->version();$this->server_info=$Rh["versionString"];}function
query($H){$I=@$this->_link->query($H);$this->error="";if(!$I){$this->errno=$this->_link->lastErrorCode();$this->error=$this->_link->lastErrorMsg();return
false;}elseif($I->numColumns())return
new
Min_Result($I);$this->affected_rows=$this->_link->changes();return
true;}function
quote($P){return(is_utf8($P)?"'".$this->_link->escapeString($P)."'":"x'".reset(unpack('H*',$P))."'");}function
store_result(){return$this->_result;}function
result($H,$n=0){$I=$this->query($H);if(!is_object($I))return
false;$K=$I->_result->fetchArray();return$K[$n];}}class
Min_Result{var$_result,$_offset=0,$num_rows;function
Min_Result($I){$this->_result=$I;}function
fetch_assoc(){return$this->_result->fetchArray(SQLITE3_ASSOC);}function
fetch_row(){return$this->_result->fetchArray(SQLITE3_NUM);}function
fetch_field(){$e=$this->_offset++;$U=$this->_result->columnType($e);return(object)array("name"=>$this->_result->columnName($e),"type"=>$U,"charsetnr"=>($U==SQLITE3_BLOB?63:0),);}function
__desctruct(){return$this->_result->finalize();}}}else{class
Min_SQLite{var$extension="SQLite",$server_info,$affected_rows,$error,$_link;function
Min_SQLite($Fc){$this->server_info=sqlite_libversion();$this->_link=new
SQLiteDatabase($Fc);}function
query($H,$zh=false){$pe=($zh?"unbufferedQuery":"query");$I=@$this->_link->$pe($H,SQLITE_BOTH,$m);$this->error="";if(!$I){$this->error=$m;return
false;}elseif($I===true){$this->affected_rows=$this->changes();return
true;}return
new
Min_Result($I);}function
quote($P){return"'".sqlite_escape_string($P)."'";}function
store_result(){return$this->_result;}function
result($H,$n=0){$I=$this->query($H);if(!is_object($I))return
false;$K=$I->_result->fetch();return$K[$n];}}class
Min_Result{var$_result,$_offset=0,$num_rows;function
Min_Result($I){$this->_result=$I;if(method_exists($I,'numRows'))$this->num_rows=$I->numRows();}function
fetch_assoc(){$K=$this->_result->fetch(SQLITE_ASSOC);if(!$K)return
false;$J=array();foreach($K
as$x=>$X)$J[($x[0]=='"'?idf_unescape($x):$x)]=$X;return$J;}function
fetch_row(){return$this->_result->fetch(SQLITE_NUM);}function
fetch_field(){$C=$this->_result->fieldName($this->_offset++);$pf='(\\[.*]|"(?:[^"]|"")*"|(.+))';if(preg_match("~^($pf\\.)?$pf\$~",$C,$B)){$Q=($B[3]!=""?$B[3]:idf_unescape($B[2]));$C=($B[5]!=""?$B[5]:idf_unescape($B[4]));}return(object)array("name"=>$C,"orgname"=>$C,"orgtable"=>$Q,);}}}}elseif(extension_loaded("pdo_sqlite")){class
Min_SQLite
extends
Min_PDO{var$extension="PDO_SQLite";function
Min_SQLite($Fc){$this->dsn(DRIVER.":$Fc","","");}}}if(class_exists("Min_SQLite")){class
Min_DB
extends
Min_SQLite{function
Min_DB(){$this->Min_SQLite(":memory:");}function
select_db($Fc){if(is_readable($Fc)&&$this->query("ATTACH ".$this->quote(preg_match("~(^[/\\\\]|:)~",$Fc)?$Fc:dirname($_SERVER["SCRIPT_FILENAME"])."/$Fc")." AS a")){$this->Min_SQLite($Fc);return
true;}return
false;}function
multi_query($H){return$this->_result=$this->query($H);}function
next_result(){return
false;}}}class
Min_Driver
extends
Min_SQL{function
insertUpdate($Q,$L,$zf){$Oh=array();foreach($L
as$O)$Oh[]="(".implode(", ",$O).")";return
queries("REPLACE INTO ".table($Q)." (".implode(", ",array_keys(reset($L))).") VALUES\n".implode(",\n",$Oh));}}function
idf_escape($t){return'"'.str_replace('"','""',$t).'"';}function
table($t){return
idf_escape($t);}function
connect(){return
new
Min_DB;}function
get_databases(){return
array();}function
limit($H,$Z,$z,$D=0,$rg=" "){return" $H$Z".($z!==null?$rg."LIMIT $z".($D?" OFFSET $D":""):"");}function
limit1($H,$Z){global$h;return($h->result("SELECT sqlite_compileoption_used('ENABLE_UPDATE_DELETE_LIMIT')")?limit($H,$Z,1):" $H$Z");}function
db_collation($k,$kb){global$h;return$h->result("PRAGMA encoding");}function
engines(){return
array();}function
logged_user(){return
get_current_user();}function
tables_list(){return
get_key_vals("SELECT name, type FROM sqlite_master WHERE type IN ('table', 'view') ORDER BY (name = 'sqlite_sequence'), name",1);}function
count_tables($j){return
array();}function
table_status($C=""){global$h;$J=array();foreach(get_rows("SELECT name AS Name, type AS Engine FROM sqlite_master WHERE type IN ('table', 'view') ".($C!=""?"AND name = ".q($C):"ORDER BY name"))as$K){$K["Oid"]=1;$K["Auto_increment"]="";$K["Rows"]=$h->result("SELECT COUNT(*) FROM ".idf_escape($K["Name"]));$J[$K["Name"]]=$K;}foreach(get_rows("SELECT * FROM sqlite_sequence",null,"")as$K)$J[$K["name"]]["Auto_increment"]=$K["seq"];return($C!=""?$J[$C]:$J);}function
is_view($R){return$R["Engine"]=="view";}function
fk_support($R){global$h;return!$h->result("SELECT sqlite_compileoption_used('OMIT_FOREIGN_KEY')");}function
fields($Q){global$h;$J=array();$zf="";foreach(get_rows("PRAGMA table_info(".table($Q).")")as$K){$C=$K["name"];$U=strtolower($K["type"]);$Kb=$K["dflt_value"];$J[$C]=array("field"=>$C,"type"=>(preg_match('~int~i',$U)?"integer":(preg_match('~char|clob|text~i',$U)?"text":(preg_match('~blob~i',$U)?"blob":(preg_match('~real|floa|doub~i',$U)?"real":"numeric")))),"full_type"=>$U,"default"=>(preg_match("~'(.*)'~",$Kb,$B)?str_replace("''","'",$B[1]):($Kb=="NULL"?null:$Kb)),"null"=>!$K["notnull"],"privileges"=>array("select"=>1,"insert"=>1,"update"=>1),"primary"=>$K["pk"],);if($K["pk"]){if($zf!="")$J[$zf]["auto_increment"]=false;elseif(preg_match('~^integer$~i',$U))$J[$C]["auto_increment"]=true;$zf=$C;}}$Bg=$h->result("SELECT sql FROM sqlite_master WHERE type = 'table' AND name = ".q($Q));preg_match_all('~(("[^"]*+")+|[a-z0-9_]+)\s+text\s+COLLATE\s+(\'[^\']+\'|\S+)~i',$Bg,$be,PREG_SET_ORDER);foreach($be
as$B){$C=str_replace('""','"',preg_replace('~^"|"$~','',$B[1]));if($J[$C])$J[$C]["collation"]=trim($B[3],"'");}return$J;}function
indexes($Q,$i=null){global$h;if(!is_object($i))$i=$h;$J=array();$Bg=$i->result("SELECT sql FROM sqlite_master WHERE type = 'table' AND name = ".q($Q));if(preg_match('~\bPRIMARY\s+KEY\s*\((([^)"]+|"[^"]*")++)~i',$Bg,$B)){$J[""]=array("type"=>"PRIMARY","columns"=>array(),"lengths"=>array(),"descs"=>array());preg_match_all('~((("[^"]*+")+)|(\S+))(\s+(ASC|DESC))?(,\s*|$)~i',$B[1],$be,PREG_SET_ORDER);foreach($be
as$B){$J[""]["columns"][]=idf_unescape($B[2]).$B[4];$J[""]["descs"][]=(preg_match('~DESC~i',$B[5])?'1':null);}}if(!$J){foreach(fields($Q)as$C=>$n){if($n["primary"])$J[""]=array("type"=>"PRIMARY","columns"=>array($C),"lengths"=>array(),"descs"=>array(null));}}$Cg=get_key_vals("SELECT name, sql FROM sqlite_master WHERE type = 'index' AND tbl_name = ".q($Q),$i);foreach(get_rows("PRAGMA index_list(".table($Q).")",$i)as$K){$C=$K["name"];$u=array("type"=>($K["unique"]?"UNIQUE":"INDEX"));$u["lengths"]=array();$u["descs"]=array();foreach(get_rows("PRAGMA index_info(".idf_escape($C).")",$i)as$hg){$u["columns"][]=$hg["name"];$u["descs"][]=null;}if(preg_match('~^CREATE( UNIQUE)? INDEX '.preg_quote(idf_escape($C).' ON '.idf_escape($Q),'~').' \((.*)\)$~i',$Cg[$C],$Uf)){preg_match_all('/("[^"]*+")+( DESC)?/',$Uf[2],$be);foreach($be[2]as$x=>$X){if($X)$u["descs"][$x]='1';}}if(!$J[""]||$u["type"]!="UNIQUE"||$u["columns"]!=$J[""]["columns"]||$u["descs"]!=$J[""]["descs"]||!preg_match("~^sqlite_~",$C))$J[$C]=$u;}return$J;}function
foreign_keys($Q){$J=array();foreach(get_rows("PRAGMA foreign_key_list(".table($Q).")")as$K){$p=&$J[$K["id"]];if(!$p)$p=$K;$p["source"][]=$K["from"];$p["target"][]=$K["to"];}return$J;}function
view($C){global$h;return
array("select"=>preg_replace('~^(?:[^`"[]+|`[^`]*`|"[^"]*")* AS\\s+~iU','',$h->result("SELECT sql FROM sqlite_master WHERE name = ".q($C))));}function
collations(){return(isset($_GET["create"])?get_vals("PRAGMA collation_list",1):array());}function
information_schema($k){return
false;}function
error(){global$h;return
h($h->error);}function
check_sqlite_name($C){global$h;$_c="db|sdb|sqlite";if(!preg_match("~^[^\\0]*\\.($_c)\$~",$C)){$h->error=lang(21,str_replace("|",", ",$_c));return
false;}return
true;}function
create_database($k,$d){global$h;if(file_exists($k)){$h->error=lang(22);return
false;}if(!check_sqlite_name($k))return
false;try{$_=new
Min_SQLite($k);}catch(Exception$rc){$h->error=$rc->getMessage();return
false;}$_->query('PRAGMA encoding = "UTF-8"');$_->query('CREATE TABLE adminer (i)');$_->query('DROP TABLE adminer');return
true;}function
drop_databases($j){global$h;$h->Min_SQLite(":memory:");foreach($j
as$k){if(!@unlink($k)){$h->error=lang(22);return
false;}}return
true;}function
rename_database($C,$d){global$h;if(!check_sqlite_name($C))return
false;$h->Min_SQLite(":memory:");$h->error=lang(22);return@rename(DB,$C);}function
auto_increment(){return" PRIMARY KEY".(DRIVER=="sqlite"?" AUTOINCREMENT":"");}function
alter_table($Q,$C,$o,$Jc,$ob,$kc,$d,$Ka,$kf){$Ih=($Q==""||$Jc);foreach($o
as$n){if($n[0]!=""||!$n[1]||$n[2]){$Ih=true;break;}}$c=array();$bf=array();foreach($o
as$n){if($n[1]){$c[]=($Ih?$n[1]:"ADD ".implode($n[1]));if($n[0]!="")$bf[$n[0]]=$n[1][0];}}if(!$Ih){foreach($c
as$X){if(!queries("ALTER TABLE ".table($Q)." $X"))return
false;}if($Q!=$C&&!queries("ALTER TABLE ".table($Q)." RENAME TO ".table($C)))return
false;}elseif(!recreate_table($Q,$C,$c,$bf,$Jc))return
false;if($Ka)queries("UPDATE sqlite_sequence SET seq = $Ka WHERE name = ".q($C));return
true;}function
recreate_table($Q,$C,$o,$bf,$Jc,$v=array()){if($Q!=""){if(!$o){foreach(fields($Q)as$x=>$n){$o[]=process_field($n,$n);$bf[$x]=idf_escape($x);}}$_f=false;foreach($o
as$n){if($n[6])$_f=true;}$Xb=array();foreach($v
as$x=>$X){if($X[2]=="DROP"){$Xb[$X[1]]=true;unset($v[$x]);}}foreach(indexes($Q)as$Gd=>$u){$f=array();foreach($u["columns"]as$x=>$e){if(!$bf[$e])continue
2;$f[]=$bf[$e].($u["descs"][$x]?" DESC":"");}if(!$Xb[$Gd]){if($u["type"]!="PRIMARY"||!$_f)$v[]=array($u["type"],$Gd,$f);}}foreach($v
as$x=>$X){if($X[0]=="PRIMARY"){unset($v[$x]);$Jc[]="  PRIMARY KEY (".implode(", ",$X[2]).")";}}foreach(foreign_keys($Q)as$Gd=>$p){foreach($p["source"]as$x=>$e){if(!$bf[$e])continue
2;$p["source"][$x]=idf_unescape($bf[$e]);}if(!isset($Jc[" $Gd"]))$Jc[]=" ".format_foreign_key($p);}queries("BEGIN");}foreach($o
as$x=>$n)$o[$x]="  ".implode($n);$o=array_merge($o,array_filter($Jc));if(!queries("CREATE TABLE ".table($Q!=""?"adminer_$C":$C)." (\n".implode(",\n",$o)."\n)"))return
false;if($Q!=""){if($bf&&!queries("INSERT INTO ".table("adminer_$C")." (".implode(", ",$bf).") SELECT ".implode(", ",array_map('idf_escape',array_keys($bf)))." FROM ".table($Q)))return
false;$vh=array();foreach(triggers($Q)as$th=>$gh){$sh=trigger($th);$vh[]="CREATE TRIGGER ".idf_escape($th)." ".implode(" ",$gh)." ON ".table($C)."\n$sh[Statement]";}if(!queries("DROP TABLE ".table($Q)))return
false;queries("ALTER TABLE ".table("adminer_$C")." RENAME TO ".table($C));if(!alter_indexes($C,$v))return
false;foreach($vh
as$sh){if(!queries($sh))return
false;}queries("COMMIT");}return
true;}function
index_sql($Q,$U,$C,$f){return"CREATE $U ".($U!="INDEX"?"INDEX ":"").idf_escape($C!=""?$C:uniqid($Q."_"))." ON ".table($Q)." $f";}function
alter_indexes($Q,$c){foreach($c
as$zf){if($zf[0]=="PRIMARY")return
recreate_table($Q,$Q,array(),array(),array(),$c);}foreach(array_reverse($c)as$X){if(!queries($X[2]=="DROP"?"DROP INDEX ".idf_escape($X[1]):index_sql($Q,$X[0],$X[1],"(".implode(", ",$X[2]).")")))return
false;}return
true;}function
truncate_tables($S){return
apply_queries("DELETE FROM",$S);}function
drop_views($Th){return
apply_queries("DROP VIEW",$Th);}function
drop_tables($S){return
apply_queries("DROP TABLE",$S);}function
move_tables($S,$Th,$Xg){return
false;}function
trigger($C){global$h;if($C=="")return
array("Statement"=>"BEGIN\n\t;\nEND");$t='(?:[^`"\\s]+|`[^`]*`|"[^"]*")+';$uh=trigger_options();preg_match("~^CREATE\\s+TRIGGER\\s*$t\\s*(".implode("|",$uh["Timing"]).")\\s+([a-z]+)(?:\\s+OF\\s+($t))?\\s+ON\\s*$t\\s*(?:FOR\\s+EACH\\s+ROW\\s)?(.*)~is",$h->result("SELECT sql FROM sqlite_master WHERE type = 'trigger' AND name = ".q($C)),$B);$De=$B[3];return
array("Timing"=>strtoupper($B[1]),"Event"=>strtoupper($B[2]).($De?" OF":""),"Of"=>($De[0]=='`'||$De[0]=='"'?idf_unescape($De):$De),"Trigger"=>$C,"Statement"=>$B[4],);}function
triggers($Q){$J=array();$uh=trigger_options();foreach(get_rows("SELECT * FROM sqlite_master WHERE type = 'trigger' AND tbl_name = ".q($Q))as$K){preg_match('~^CREATE\\s+TRIGGER\\s*(?:[^`"\\s]+|`[^`]*`|"[^"]*")+\\s*('.implode("|",$uh["Timing"]).')\\s*(.*)\\s+ON\\b~iU',$K["sql"],$B);$J[$K["name"]]=array($B[1],$B[2]);}return$J;}function
trigger_options(){return
array("Timing"=>array("BEFORE","AFTER","INSTEAD OF"),"Event"=>array("INSERT","UPDATE","UPDATE OF","DELETE"),"Type"=>array("FOR EACH ROW"),);}function
routine($C,$U){}function
routines(){}function
routine_languages(){}function
begin(){return
queries("BEGIN");}function
last_id(){global$h;return$h->result("SELECT LAST_INSERT_ROWID()");}function
explain($h,$H){return$h->query("EXPLAIN $H");}function
found_rows($R,$Z){}function
types(){return
array();}function
schemas(){return
array();}function
get_schema(){return"";}function
set_schema($lg){return
true;}function
create_sql($Q,$Ka){global$h;$J=$h->result("SELECT sql FROM sqlite_master WHERE type IN ('table', 'view') AND name = ".q($Q));foreach(indexes($Q)as$C=>$u){if($C=='')continue;$J.=";\n\n".index_sql($Q,$u['type'],$C,"(".implode(", ",array_map('idf_escape',$u['columns'])).")");}return$J;}function
truncate_sql($Q){return"DELETE FROM ".table($Q);}function
use_sql($Fb){}function
trigger_sql($Q,$Ig){return
implode(get_vals("SELECT sql || ';;\n' FROM sqlite_master WHERE type = 'trigger' AND tbl_name = ".q($Q)));}function
show_variables(){global$h;$J=array();foreach(array("auto_vacuum","cache_size","count_changes","default_cache_size","empty_result_callbacks","encoding","foreign_keys","full_column_names","fullfsync","journal_mode","journal_size_limit","legacy_file_format","locking_mode","page_size","max_page_count","read_uncommitted","recursive_triggers","reverse_unordered_selects","secure_delete","short_column_names","synchronous","temp_store","temp_store_directory","schema_version","integrity_check","quick_check")as$x)$J[$x]=$h->result("PRAGMA $x");return$J;}function
show_status(){$J=array();foreach(get_vals("PRAGMA compile_options")as$Qe){list($x,$X)=explode("=",$Qe,2);$J[$x]=$X;}return$J;}function
convert_field($n){}function
unconvert_field($n,$J){return$J;}function
support($Cc){return
preg_match('~^(columns|database|drop_col|dump|indexes|move_col|sql|status|table|trigger|variables|view|view_trigger)$~',$Cc);}$w="sqlite";$yh=array("integer"=>0,"real"=>0,"numeric"=>0,"text"=>0,"blob"=>0);$Hg=array_keys($yh);$Eh=array();$Oe=array("=","<",">","<=",">=","!=","LIKE","LIKE %%","IN","IS NULL","NOT LIKE","NOT IN","IS NOT NULL","SQL");$Sc=array("hex","length","lower","round","unixepoch","upper");$Xc=array("avg","count","count distinct","group_concat","max","min","sum");$cc=array(array(),array("integer|real|numeric"=>"+/-","text"=>"||",));}$Ub["pgsql"]="PostgreSQL";if(isset($_GET["pgsql"])){$wf=array("PgSQL","PDO_PgSQL");define("DRIVER","pgsql");if(extension_loaded("pgsql")){class
Min_DB{var$extension="PgSQL",$_link,$_result,$_string,$_database=true,$server_info,$affected_rows,$error;function
_error($nc,$m){if(ini_bool("html_errors"))$m=html_entity_decode(strip_tags($m));$m=preg_replace('~^[^:]*: ~','',$m);$this->error=$m;}function
connect($N,$V,$G){global$b;$k=$b->database();set_error_handler(array($this,'_error'));$this->_string="host='".str_replace(":","' port='",addcslashes($N,"'\\"))."' user='".addcslashes($V,"'\\")."' password='".addcslashes($G,"'\\")."'";$this->_link=@pg_connect("$this->_string dbname='".($k!=""?addcslashes($k,"'\\"):"postgres")."'",PGSQL_CONNECT_FORCE_NEW);if(!$this->_link&&$k!=""){$this->_database=false;$this->_link=@pg_connect("$this->_string dbname='postgres'",PGSQL_CONNECT_FORCE_NEW);}restore_error_handler();if($this->_link){$Rh=pg_version($this->_link);$this->server_info=$Rh["server"];pg_set_client_encoding($this->_link,"UTF8");}return(bool)$this->_link;}function
quote($P){return"'".pg_escape_string($this->_link,$P)."'";}function
select_db($Fb){global$b;if($Fb==$b->database())return$this->_database;$J=@pg_connect("$this->_string dbname='".addcslashes($Fb,"'\\")."'",PGSQL_CONNECT_FORCE_NEW);if($J)$this->_link=$J;return$J;}function
close(){$this->_link=@pg_connect("$this->_string dbname='postgres'");}function
query($H,$zh=false){$I=@pg_query($this->_link,$H);$this->error="";if(!$I){$this->error=pg_last_error($this->_link);return
false;}elseif(!pg_num_fields($I)){$this->affected_rows=pg_affected_rows($I);return
true;}return
new
Min_Result($I);}function
multi_query($H){return$this->_result=$this->query($H);}function
store_result(){return$this->_result;}function
next_result(){return
false;}function
result($H,$n=0){$I=$this->query($H);if(!$I||!$I->num_rows)return
false;return
pg_fetch_result($I->_result,0,$n);}}class
Min_Result{var$_result,$_offset=0,$num_rows;function
Min_Result($I){$this->_result=$I;$this->num_rows=pg_num_rows($I);}function
fetch_assoc(){return
pg_fetch_assoc($this->_result);}function
fetch_row(){return
pg_fetch_row($this->_result);}function
fetch_field(){$e=$this->_offset++;$J=new
stdClass;if(function_exists('pg_field_table'))$J->orgtable=pg_field_table($this->_result,$e);$J->name=pg_field_name($this->_result,$e);$J->orgname=$J->name;$J->type=pg_field_type($this->_result,$e);$J->charsetnr=($J->type=="bytea"?63:0);return$J;}function
__destruct(){pg_free_result($this->_result);}}}elseif(extension_loaded("pdo_pgsql")){class
Min_DB
extends
Min_PDO{var$extension="PDO_PgSQL";function
connect($N,$V,$G){global$b;$k=$b->database();$P="pgsql:host='".str_replace(":","' port='",addcslashes($N,"'\\"))."' options='-c client_encoding=utf8'";$this->dsn("$P dbname='".($k!=""?addcslashes($k,"'\\"):"postgres")."'",$V,$G);return
true;}function
select_db($Fb){global$b;return($b->database()==$Fb);}function
close(){}}}class
Min_Driver
extends
Min_SQL{function
insertUpdate($Q,$L,$zf){global$h;foreach($L
as$O){$Fh=array();$Z=array();foreach($O
as$x=>$X){$Fh[]="$x = $X";if(isset($zf[idf_unescape($x)]))$Z[]="$x = $X";}if(!(($Z&&queries("UPDATE ".table($Q)." SET ".implode(", ",$Fh)." WHERE ".implode(" AND ",$Z))&&$h->affected_rows)||queries("INSERT INTO ".table($Q)." (".implode(", ",array_keys($O)).") VALUES (".implode(", ",$O).")")))return
false;}return
true;}}function
idf_escape($t){return'"'.str_replace('"','""',$t).'"';}function
table($t){return
idf_escape($t);}function
connect(){global$b;$h=new
Min_DB;$Bb=$b->credentials();if($h->connect($Bb[0],$Bb[1],$Bb[2])){if($h->server_info>=9)$h->query("SET application_name = 'Adminer'");return$h;}return$h->error;}function
get_databases(){return
get_vals("SELECT datname FROM pg_database ORDER BY datname");}function
limit($H,$Z,$z,$D=0,$rg=" "){return" $H$Z".($z!==null?$rg."LIMIT $z".($D?" OFFSET $D":""):"");}function
limit1($H,$Z){return" $H$Z";}function
db_collation($k,$kb){global$h;return$h->result("SHOW LC_COLLATE");}function
engines(){return
array();}function
logged_user(){global$h;return$h->result("SELECT user");}function
tables_list(){return
get_key_vals("SELECT table_name, table_type FROM information_schema.tables WHERE table_schema = current_schema() ORDER BY table_name");}function
count_tables($j){return
array();}function
table_status($C=""){$J=array();foreach(get_rows("SELECT relname AS \"Name\", CASE relkind WHEN 'r' THEN 'table' ELSE 'view' END AS \"Engine\", pg_relation_size(oid) AS \"Data_length\", pg_total_relation_size(oid) - pg_relation_size(oid) AS \"Index_length\", obj_description(oid, 'pg_class') AS \"Comment\", relhasoids::int AS \"Oid\", reltuples as \"Rows\"
FROM pg_class
WHERE relkind IN ('r','v')
AND relnamespace = (SELECT oid FROM pg_namespace WHERE nspname = current_schema())
".($C!=""?"AND relname = ".q($C):"ORDER BY relname"))as$K)$J[$K["Name"]]=$K;return($C!=""?$J[$C]:$J);}function
is_view($R){return$R["Engine"]=="view";}function
fk_support($R){return
true;}function
fields($Q){$J=array();$Ba=array('timestamp without time zone'=>'timestamp','timestamp with time zone'=>'timestamptz',);foreach(get_rows("SELECT a.attname AS field, format_type(a.atttypid, a.atttypmod) AS full_type, d.adsrc AS default, a.attnotnull::int, col_description(c.oid, a.attnum) AS comment
FROM pg_class c
JOIN pg_namespace n ON c.relnamespace = n.oid
JOIN pg_attribute a ON c.oid = a.attrelid
LEFT JOIN pg_attrdef d ON c.oid = d.adrelid AND a.attnum = d.adnum
WHERE c.relname = ".q($Q)."
AND n.nspname = current_schema()
AND NOT a.attisdropped
AND a.attnum > 0
ORDER BY a.attnum")as$K){preg_match('~([^([]+)(\((.*)\))?((\[[0-9]*])*)$~',$K["full_type"],$B);list(,$U,$y,$K["length"],$Ea)=$B;$K["length"].=$Ea;$K["type"]=($Ba[$U]?$Ba[$U]:$U);$K["full_type"]=$K["type"].$y.$Ea;$K["null"]=!$K["attnotnull"];$K["auto_increment"]=preg_match('~^nextval\\(~i',$K["default"]);$K["privileges"]=array("insert"=>1,"select"=>1,"update"=>1);if(preg_match('~(.+)::[^)]+(.*)~',$K["default"],$B))$K["default"]=($B[1][0]=="'"?idf_unescape($B[1]):$B[1]).$B[2];$J[$K["field"]]=$K;}return$J;}function
indexes($Q,$i=null){global$h;if(!is_object($i))$i=$h;$J=array();$Qg=$i->result("SELECT oid FROM pg_class WHERE relnamespace = (SELECT oid FROM pg_namespace WHERE nspname = current_schema()) AND relname = ".q($Q));$f=get_key_vals("SELECT attnum, attname FROM pg_attribute WHERE attrelid = $Qg AND attnum > 0",$i);foreach(get_rows("SELECT relname, indisunique::int, indisprimary::int, indkey, indoption FROM pg_index i, pg_class ci WHERE i.indrelid = $Qg AND ci.oid = i.indexrelid",$i)as$K){$Vf=$K["relname"];$J[$Vf]["type"]=($K["indisprimary"]?"PRIMARY":($K["indisunique"]?"UNIQUE":"INDEX"));$J[$Vf]["columns"]=array();foreach(explode(" ",$K["indkey"])as$nd)$J[$Vf]["columns"][]=$f[$nd];$J[$Vf]["descs"]=array();foreach(explode(" ",$K["indoption"])as$od)$J[$Vf]["descs"][]=($od&1?'1':null);$J[$Vf]["lengths"]=array();}return$J;}function
foreign_keys($Q){global$Ke;$J=array();foreach(get_rows("SELECT conname, pg_get_constraintdef(oid) AS definition
FROM pg_constraint
WHERE conrelid = (SELECT pc.oid FROM pg_class AS pc INNER JOIN pg_namespace AS pn ON (pn.oid = pc.relnamespace) WHERE pc.relname = ".q($Q)." AND pn.nspname = current_schema())
AND contype = 'f'::char
ORDER BY conkey, conname")as$K){if(preg_match('~FOREIGN KEY\s*\((.+)\)\s*REFERENCES (.+)\((.+)\)(.*)$~iA',$K['definition'],$B)){$K['source']=array_map('trim',explode(',',$B[1]));if(preg_match('~^(("([^"]|"")+"|[^"]+)\.)?"?("([^"]|"")+"|[^"]+)$~',$B[2],$ae)){$K['ns']=str_replace('""','"',preg_replace('~^"(.+)"$~','\1',$ae[2]));$K['table']=str_replace('""','"',preg_replace('~^"(.+)"$~','\1',$ae[4]));}$K['target']=array_map('trim',explode(',',$B[3]));$K['on_delete']=(preg_match("~ON DELETE ($Ke)~",$B[4],$ae)?$ae[1]:'NO ACTION');$K['on_update']=(preg_match("~ON UPDATE ($Ke)~",$B[4],$ae)?$ae[1]:'NO ACTION');$J[$K['conname']]=$K;}}return$J;}function
view($C){global$h;return
array("select"=>$h->result("SELECT pg_get_viewdef(".q($C).")"));}function
collations(){return
array();}function
information_schema($k){return($k=="information_schema");}function
error(){global$h;$J=h($h->error);if(preg_match('~^(.*\\n)?([^\\n]*)\\n( *)\\^(\\n.*)?$~s',$J,$B))$J=$B[1].preg_replace('~((?:[^&]|&[^;]*;){'.strlen($B[3]).'})(.*)~','\\1<b>\\2</b>',$B[2]).$B[4];return
nl_br($J);}function
create_database($k,$d){return
queries("CREATE DATABASE ".idf_escape($k).($d?" ENCODING ".idf_escape($d):""));}function
drop_databases($j){global$h;$h->close();return
apply_queries("DROP DATABASE",$j,'idf_escape');}function
rename_database($C,$d){return
queries("ALTER DATABASE ".idf_escape(DB)." RENAME TO ".idf_escape($C));}function
auto_increment(){return"";}function
alter_table($Q,$C,$o,$Jc,$ob,$kc,$d,$Ka,$kf){$c=array();$If=array();foreach($o
as$n){$e=idf_escape($n[0]);$X=$n[1];if(!$X)$c[]="DROP $e";else{$Nh=$X[5];unset($X[5]);if(isset($X[6])&&$n[0]=="")$X[1]=($X[1]=="bigint"?" big":" ")."serial";if($n[0]=="")$c[]=($Q!=""?"ADD ":"  ").implode($X);else{if($e!=$X[0])$If[]="ALTER TABLE ".table($Q)." RENAME $e TO $X[0]";$c[]="ALTER $e TYPE$X[1]";if(!$X[6]){$c[]="ALTER $e ".($X[3]?"SET$X[3]":"DROP DEFAULT");$c[]="ALTER $e ".($X[2]==" NULL"?"DROP NOT":"SET").$X[2];}}if($n[0]!=""||$Nh!="")$If[]="COMMENT ON COLUMN ".table($Q).".$X[0] IS ".($Nh!=""?substr($Nh,9):"''");}}$c=array_merge($c,$Jc);if($Q=="")array_unshift($If,"CREATE TABLE ".table($C)." (\n".implode(",\n",$c)."\n)");elseif($c)array_unshift($If,"ALTER TABLE ".table($Q)."\n".implode(",\n",$c));if($Q!=""&&$Q!=$C)$If[]="ALTER TABLE ".table($Q)." RENAME TO ".table($C);if($Q!=""||$ob!="")$If[]="COMMENT ON TABLE ".table($C)." IS ".q($ob);if($Ka!=""){}foreach($If
as$H){if(!queries($H))return
false;}return
true;}function
alter_indexes($Q,$c){$zb=array();$Vb=array();$If=array();foreach($c
as$X){if($X[0]!="INDEX")$zb[]=($X[2]=="DROP"?"\nDROP CONSTRAINT ".idf_escape($X[1]):"\nADD".($X[1]!=""?" CONSTRAINT ".idf_escape($X[1]):"")." $X[0] ".($X[0]=="PRIMARY"?"KEY ":"")."(".implode(", ",$X[2]).")");elseif($X[2]=="DROP")$Vb[]=idf_escape($X[1]);else$If[]="CREATE INDEX ".idf_escape($X[1]!=""?$X[1]:uniqid($Q."_"))." ON ".table($Q)." (".implode(", ",$X[2]).")";}if($zb)array_unshift($If,"ALTER TABLE ".table($Q).implode(",",$zb));if($Vb)array_unshift($If,"DROP INDEX ".implode(", ",$Vb));foreach($If
as$H){if(!queries($H))return
false;}return
true;}function
truncate_tables($S){return
queries("TRUNCATE ".implode(", ",array_map('table',$S)));return
true;}function
drop_views($Th){return
queries("DROP VIEW ".implode(", ",array_map('table',$Th)));}function
drop_tables($S){return
queries("DROP TABLE ".implode(", ",array_map('table',$S)));}function
move_tables($S,$Th,$Xg){foreach($S
as$Q){if(!queries("ALTER TABLE ".table($Q)." SET SCHEMA ".idf_escape($Xg)))return
false;}foreach($Th
as$Q){if(!queries("ALTER VIEW ".table($Q)." SET SCHEMA ".idf_escape($Xg)))return
false;}return
true;}function
trigger($C){if($C=="")return
array("Statement"=>"EXECUTE PROCEDURE ()");$L=get_rows('SELECT trigger_name AS "Trigger", condition_timing AS "Timing", event_manipulation AS "Event", \'FOR EACH \' || action_orientation AS "Type", action_statement AS "Statement" FROM information_schema.triggers WHERE event_object_table = '.q($_GET["trigger"]).' AND trigger_name = '.q($C));return
reset($L);}function
triggers($Q){$J=array();foreach(get_rows("SELECT * FROM information_schema.triggers WHERE event_object_table = ".q($Q))as$K)$J[$K["trigger_name"]]=array($K["condition_timing"],$K["event_manipulation"]);return$J;}function
trigger_options(){return
array("Timing"=>array("BEFORE","AFTER"),"Event"=>array("INSERT","UPDATE","DELETE"),"Type"=>array("FOR EACH ROW","FOR EACH STATEMENT"),);}function
routines(){return
get_rows('SELECT p.proname AS "ROUTINE_NAME", p.proargtypes AS "ROUTINE_TYPE", pg_catalog.format_type(p.prorettype, NULL) AS "DTD_IDENTIFIER"
FROM pg_catalog.pg_namespace n
JOIN pg_catalog.pg_proc p ON p.pronamespace = n.oid
WHERE n.nspname = current_schema()
ORDER BY p.proname');}function
routine_languages(){return
get_vals("SELECT langname FROM pg_catalog.pg_language");}function
last_id(){return
0;}function
explain($h,$H){return$h->query("EXPLAIN $H");}function
found_rows($R,$Z){global$h;if(preg_match("~ rows=([0-9]+)~",$h->result("EXPLAIN SELECT * FROM ".idf_escape($R["Name"]).($Z?" WHERE ".implode(" AND ",$Z):"")),$Uf))return$Uf[1];return
false;}function
types(){return
get_vals("SELECT typname
FROM pg_type
WHERE typnamespace = (SELECT oid FROM pg_namespace WHERE nspname = current_schema())
AND typtype IN ('b','d','e')
AND typelem = 0");}function
schemas(){return
get_vals("SELECT nspname FROM pg_namespace ORDER BY nspname");}function
get_schema(){global$h;return$h->result("SELECT current_schema()");}function
set_schema($kg){global$h,$yh,$Hg;$J=$h->query("SET search_path TO ".idf_escape($kg));foreach(types()as$U){if(!isset($yh[$U])){$yh[$U]=0;$Hg[lang(23)][]=$U;}}return$J;}function
use_sql($Fb){return"\connect ".idf_escape($Fb);}function
show_variables(){return
get_key_vals("SHOW ALL");}function
process_list(){global$h;return
get_rows("SELECT * FROM pg_stat_activity ORDER BY ".($h->server_info<9.2?"procpid":"pid"));}function
show_status(){}function
convert_field($n){}function
unconvert_field($n,$J){return$J;}function
support($Cc){return
preg_match('~^(database|table|columns|sql|indexes|comment|view|scheme|processlist|sequence|trigger|type|variables|drop_col)$~',$Cc);}$w="pgsql";$yh=array();$Hg=array();foreach(array(lang(24)=>array("smallint"=>5,"integer"=>10,"bigint"=>19,"boolean"=>1,"numeric"=>0,"real"=>7,"double precision"=>16,"money"=>20),lang(25)=>array("date"=>13,"time"=>17,"timestamp"=>20,"timestamptz"=>21,"interval"=>0),lang(26)=>array("character"=>0,"character varying"=>0,"text"=>0,"tsquery"=>0,"tsvector"=>0,"uuid"=>0,"xml"=>0),lang(27)=>array("bit"=>0,"bit varying"=>0,"bytea"=>0),lang(28)=>array("cidr"=>43,"inet"=>43,"macaddr"=>17,"txid_snapshot"=>0),lang(29)=>array("box"=>0,"circle"=>0,"line"=>0,"lseg"=>0,"path"=>0,"point"=>0,"polygon"=>0),)as$x=>$X){$yh+=$X;$Hg[$x]=array_keys($X);}$Eh=array();$Oe=array("=","<",">","<=",">=","!=","~","!~","LIKE","LIKE %%","IN","IS NULL","NOT LIKE","NOT IN","IS NOT NULL");$Sc=array("char_length","lower","round","to_hex","to_timestamp","upper");$Xc=array("avg","count","count distinct","max","min","sum");$cc=array(array("char"=>"md5","date|time"=>"now",),array("int|numeric|real|money"=>"+/-","date|time"=>"+ interval/- interval","char|text"=>"||",));}$Ub["oracle"]="Oracle";if(isset($_GET["oracle"])){$wf=array("OCI8","PDO_OCI");define("DRIVER","oracle");if(extension_loaded("oci8")){class
Min_DB{var$extension="oci8",$_link,$_result,$server_info,$affected_rows,$errno,$error;function
_error($nc,$m){if(ini_bool("html_errors"))$m=html_entity_decode(strip_tags($m));$m=preg_replace('~^[^:]*: ~','',$m);$this->error=$m;}function
connect($N,$V,$G){$this->_link=@oci_new_connect($V,$G,$N,"AL32UTF8");if($this->_link){$this->server_info=oci_server_version($this->_link);return
true;}$m=oci_error();$this->error=$m["message"];return
false;}function
quote($P){return"'".str_replace("'","''",$P)."'";}function
select_db($Fb){return
true;}function
query($H,$zh=false){$I=oci_parse($this->_link,$H);$this->error="";if(!$I){$m=oci_error($this->_link);$this->errno=$m["code"];$this->error=$m["message"];return
false;}set_error_handler(array($this,'_error'));$J=@oci_execute($I);restore_error_handler();if($J){if(oci_num_fields($I))return
new
Min_Result($I);$this->affected_rows=oci_num_rows($I);}return$J;}function
multi_query($H){return$this->_result=$this->query($H);}function
store_result(){return$this->_result;}function
next_result(){return
false;}function
result($H,$n=1){$I=$this->query($H);if(!is_object($I)||!oci_fetch($I->_result))return
false;return
oci_result($I->_result,$n);}}class
Min_Result{var$_result,$_offset=1,$num_rows;function
Min_Result($I){$this->_result=$I;}function
_convert($K){foreach((array)$K
as$x=>$X){if(is_a($X,'OCI-Lob'))$K[$x]=$X->load();}return$K;}function
fetch_assoc(){return$this->_convert(oci_fetch_assoc($this->_result));}function
fetch_row(){return$this->_convert(oci_fetch_row($this->_result));}function
fetch_field(){$e=$this->_offset++;$J=new
stdClass;$J->name=oci_field_name($this->_result,$e);$J->orgname=$J->name;$J->type=oci_field_type($this->_result,$e);$J->charsetnr=(preg_match("~raw|blob|bfile~",$J->type)?63:0);return$J;}function
__destruct(){oci_free_statement($this->_result);}}}elseif(extension_loaded("pdo_oci")){class
Min_DB
extends
Min_PDO{var$extension="PDO_OCI";function
connect($N,$V,$G){$this->dsn("oci:dbname=//$N;charset=AL32UTF8",$V,$G);return
true;}function
select_db($Fb){return
true;}}}class
Min_Driver
extends
Min_SQL{function
begin(){return
true;}}function
idf_escape($t){return'"'.str_replace('"','""',$t).'"';}function
table($t){return
idf_escape($t);}function
connect(){global$b;$h=new
Min_DB;$Bb=$b->credentials();if($h->connect($Bb[0],$Bb[1],$Bb[2]))return$h;return$h->error;}function
get_databases(){return
get_vals("SELECT tablespace_name FROM user_tablespaces");}function
limit($H,$Z,$z,$D=0,$rg=" "){return($D?" * FROM (SELECT t.*, rownum AS rnum FROM (SELECT $H$Z) t WHERE rownum <= ".($z+$D).") WHERE rnum > $D":($z!==null?" * FROM (SELECT $H$Z) WHERE rownum <= ".($z+$D):" $H$Z"));}function
limit1($H,$Z){return" $H$Z";}function
db_collation($k,$kb){global$h;return$h->result("SELECT value FROM nls_database_parameters WHERE parameter = 'NLS_CHARACTERSET'");}function
engines(){return
array();}function
logged_user(){global$h;return$h->result("SELECT USER FROM DUAL");}function
tables_list(){return
get_key_vals("SELECT table_name, 'table' FROM all_tables WHERE tablespace_name = ".q(DB)."
UNION SELECT view_name, 'view' FROM user_views
ORDER BY 1");}function
count_tables($j){return
array();}function
table_status($C=""){$J=array();$mg=q($C);foreach(get_rows('SELECT table_name "Name", \'table\' "Engine", avg_row_len * num_rows "Data_length", num_rows "Rows" FROM all_tables WHERE tablespace_name = '.q(DB).($C!=""?" AND table_name = $mg":"")."
UNION SELECT view_name, 'view', 0, 0 FROM user_views".($C!=""?" WHERE view_name = $mg":"")."
ORDER BY 1")as$K){if($C!="")return$K;$J[$K["Name"]]=$K;}return$J;}function
is_view($R){return$R["Engine"]=="view";}function
fk_support($R){return
true;}function
fields($Q){$J=array();foreach(get_rows("SELECT * FROM all_tab_columns WHERE table_name = ".q($Q)." ORDER BY column_id")as$K){$U=$K["DATA_TYPE"];$y="$K[DATA_PRECISION],$K[DATA_SCALE]";if($y==",")$y=$K["DATA_LENGTH"];$J[$K["COLUMN_NAME"]]=array("field"=>$K["COLUMN_NAME"],"full_type"=>$U.($y?"($y)":""),"type"=>strtolower($U),"length"=>$y,"default"=>$K["DATA_DEFAULT"],"null"=>($K["NULLABLE"]=="Y"),"privileges"=>array("insert"=>1,"select"=>1,"update"=>1),);}return$J;}function
indexes($Q,$i=null){$J=array();foreach(get_rows("SELECT uic.*, uc.constraint_type
FROM user_ind_columns uic
LEFT JOIN user_constraints uc ON uic.index_name = uc.constraint_name AND uic.table_name = uc.table_name
WHERE uic.table_name = ".q($Q)."
ORDER BY uc.constraint_type, uic.column_position",$i)as$K){$ld=$K["INDEX_NAME"];$J[$ld]["type"]=($K["CONSTRAINT_TYPE"]=="P"?"PRIMARY":($K["CONSTRAINT_TYPE"]=="U"?"UNIQUE":"INDEX"));$J[$ld]["columns"][]=$K["COLUMN_NAME"];$J[$ld]["lengths"][]=($K["CHAR_LENGTH"]&&$K["CHAR_LENGTH"]!=$K["COLUMN_LENGTH"]?$K["CHAR_LENGTH"]:null);$J[$ld]["descs"][]=($K["DESCEND"]?'1':null);}return$J;}function
view($C){$L=get_rows('SELECT text "select" FROM user_views WHERE view_name = '.q($C));return
reset($L);}function
collations(){return
array();}function
information_schema($k){return
false;}function
error(){global$h;return
h($h->error);}function
explain($h,$H){$h->query("EXPLAIN PLAN FOR $H");return$h->query("SELECT * FROM plan_table");}function
found_rows($R,$Z){}function
alter_table($Q,$C,$o,$Jc,$ob,$kc,$d,$Ka,$kf){$c=$Vb=array();foreach($o
as$n){$X=$n[1];if($X&&$n[0]!=""&&idf_escape($n[0])!=$X[0])queries("ALTER TABLE ".table($Q)." RENAME COLUMN ".idf_escape($n[0])." TO $X[0]");if($X)$c[]=($Q!=""?($n[0]!=""?"MODIFY (":"ADD ("):"  ").implode($X).($Q!=""?")":"");else$Vb[]=idf_escape($n[0]);}if($Q=="")return
queries("CREATE TABLE ".table($C)." (\n".implode(",\n",$c)."\n)");return(!$c||queries("ALTER TABLE ".table($Q)."\n".implode("\n",$c)))&&(!$Vb||queries("ALTER TABLE ".table($Q)." DROP (".implode(", ",$Vb).")"))&&($Q==$C||queries("ALTER TABLE ".table($Q)." RENAME TO ".table($C)));}function
foreign_keys($Q){return
array();}function
truncate_tables($S){return
apply_queries("TRUNCATE TABLE",$S);}function
drop_views($Th){return
apply_queries("DROP VIEW",$Th);}function
drop_tables($S){return
apply_queries("DROP TABLE",$S);}function
last_id(){return
0;}function
schemas(){return
get_vals("SELECT DISTINCT owner FROM dba_segments WHERE owner IN (SELECT username FROM dba_users WHERE default_tablespace NOT IN ('SYSTEM','SYSAUX'))");}function
get_schema(){global$h;return$h->result("SELECT sys_context('USERENV', 'SESSION_USER') FROM dual");}function
set_schema($lg){global$h;return$h->query("ALTER SESSION SET CURRENT_SCHEMA = ".idf_escape($lg));}function
show_variables(){return
get_key_vals('SELECT name, display_value FROM v$parameter');}function
process_list(){return
get_rows('SELECT sess.process AS "process", sess.username AS "user", sess.schemaname AS "schema", sess.status AS "status", sess.wait_class AS "wait_class", sess.seconds_in_wait AS "seconds_in_wait", sql.sql_text AS "sql_text", sess.machine AS "machine", sess.port AS "port"
FROM v$session sess LEFT OUTER JOIN v$sql sql
ON sql.sql_id = sess.sql_id
WHERE sess.type = \'USER\'
ORDER BY PROCESS
');}function
show_status(){$L=get_rows('SELECT * FROM v$instance');return
reset($L);}function
convert_field($n){}function
unconvert_field($n,$J){return$J;}function
support($Cc){return
preg_match('~^(columns|database|drop_col|indexes|processlist|scheme|sql|status|table|variables|view|view_trigger)$~',$Cc);}$w="oracle";$yh=array();$Hg=array();foreach(array(lang(24)=>array("number"=>38,"binary_float"=>12,"binary_double"=>21),lang(25)=>array("date"=>10,"timestamp"=>29,"interval year"=>12,"interval day"=>28),lang(26)=>array("char"=>2000,"varchar2"=>4000,"nchar"=>2000,"nvarchar2"=>4000,"clob"=>4294967295,"nclob"=>4294967295),lang(27)=>array("raw"=>2000,"long raw"=>2147483648,"blob"=>4294967295,"bfile"=>4294967296),)as$x=>$X){$yh+=$X;$Hg[$x]=array_keys($X);}$Eh=array();$Oe=array("=","<",">","<=",">=","!=","LIKE","LIKE %%","IN","IS NULL","NOT LIKE","NOT REGEXP","NOT IN","IS NOT NULL","SQL");$Sc=array("length","lower","round","upper");$Xc=array("avg","count","count distinct","max","min","sum");$cc=array(array("date"=>"current_date","timestamp"=>"current_timestamp",),array("number|float|double"=>"+/-","date|timestamp"=>"+ interval/- interval","char|clob"=>"||",));}$Ub["mssql"]="MS SQL";if(isset($_GET["mssql"])){$wf=array("SQLSRV","MSSQL");define("DRIVER","mssql");if(extension_loaded("sqlsrv")){class
Min_DB{var$extension="sqlsrv",$_link,$_result,$server_info,$affected_rows,$errno,$error;function
_get_error(){$this->error="";foreach(sqlsrv_errors()as$m){$this->errno=$m["code"];$this->error.="$m[message]\n";}$this->error=rtrim($this->error);}function
connect($N,$V,$G){$this->_link=@sqlsrv_connect($N,array("UID"=>$V,"PWD"=>$G,"CharacterSet"=>"UTF-8"));if($this->_link){$pd=sqlsrv_server_info($this->_link);$this->server_info=$pd['SQLServerVersion'];}else$this->_get_error();return(bool)$this->_link;}function
quote($P){return"'".str_replace("'","''",$P)."'";}function
select_db($Fb){return$this->query("USE ".idf_escape($Fb));}function
query($H,$zh=false){$I=sqlsrv_query($this->_link,$H);$this->error="";if(!$I){$this->_get_error();return
false;}return$this->store_result($I);}function
multi_query($H){$this->_result=sqlsrv_query($this->_link,$H);$this->error="";if(!$this->_result){$this->_get_error();return
false;}return
true;}function
store_result($I=null){if(!$I)$I=$this->_result;if(sqlsrv_field_metadata($I))return
new
Min_Result($I);$this->affected_rows=sqlsrv_rows_affected($I);return
true;}function
next_result(){return
sqlsrv_next_result($this->_result);}function
result($H,$n=0){$I=$this->query($H);if(!is_object($I))return
false;$K=$I->fetch_row();return$K[$n];}}class
Min_Result{var$_result,$_offset=0,$_fields,$num_rows;function
Min_Result($I){$this->_result=$I;}function
_convert($K){foreach((array)$K
as$x=>$X){if(is_a($X,'DateTime'))$K[$x]=$X->format("Y-m-d H:i:s");}return$K;}function
fetch_assoc(){return$this->_convert(sqlsrv_fetch_array($this->_result,SQLSRV_FETCH_ASSOC,SQLSRV_SCROLL_NEXT));}function
fetch_row(){return$this->_convert(sqlsrv_fetch_array($this->_result,SQLSRV_FETCH_NUMERIC,SQLSRV_SCROLL_NEXT));}function
fetch_field(){if(!$this->_fields)$this->_fields=sqlsrv_field_metadata($this->_result);$n=$this->_fields[$this->_offset++];$J=new
stdClass;$J->name=$n["Name"];$J->orgname=$n["Name"];$J->type=($n["Type"]==1?254:0);return$J;}function
seek($D){for($r=0;$r<$D;$r++)sqlsrv_fetch($this->_result);}function
__destruct(){sqlsrv_free_stmt($this->_result);}}}elseif(extension_loaded("mssql")){class
Min_DB{var$extension="MSSQL",$_link,$_result,$server_info,$affected_rows,$error;function
connect($N,$V,$G){$this->_link=@mssql_connect($N,$V,$G);if($this->_link){$I=$this->query("SELECT SERVERPROPERTY('ProductLevel'), SERVERPROPERTY('Edition')");$K=$I->fetch_row();$this->server_info=$this->result("sp_server_info 2",2)." [$K[0]] $K[1]";}else$this->error=mssql_get_last_message();return(bool)$this->_link;}function
quote($P){return"'".str_replace("'","''",$P)."'";}function
select_db($Fb){return
mssql_select_db($Fb);}function
query($H,$zh=false){$I=mssql_query($H,$this->_link);$this->error="";if(!$I){$this->error=mssql_get_last_message();return
false;}if($I===true){$this->affected_rows=mssql_rows_affected($this->_link);return
true;}return
new
Min_Result($I);}function
multi_query($H){return$this->_result=$this->query($H);}function
store_result(){return$this->_result;}function
next_result(){return
mssql_next_result($this->_result);}function
result($H,$n=0){$I=$this->query($H);if(!is_object($I))return
false;return
mssql_result($I->_result,0,$n);}}class
Min_Result{var$_result,$_offset=0,$_fields,$num_rows;function
Min_Result($I){$this->_result=$I;$this->num_rows=mssql_num_rows($I);}function
fetch_assoc(){return
mssql_fetch_assoc($this->_result);}function
fetch_row(){return
mssql_fetch_row($this->_result);}function
num_rows(){return
mssql_num_rows($this->_result);}function
fetch_field(){$J=mssql_fetch_field($this->_result);$J->orgtable=$J->table;$J->orgname=$J->name;return$J;}function
seek($D){mssql_data_seek($this->_result,$D);}function
__destruct(){mssql_free_result($this->_result);}}}class
Min_Driver
extends
Min_SQL{function
insertUpdate($Q,$L,$zf){foreach($L
as$O){$Fh=array();$Z=array();foreach($O
as$x=>$X){$Fh[]="$x = $X";if(isset($zf[idf_unescape($x)]))$Z[]="$x = $X";}if(!queries("MERGE ".table($Q)." USING (VALUES(".implode(", ",$O).")) AS source (c".implode(", c",range(1,count($O))).") ON ".implode(" AND ",$Z)." WHEN MATCHED THEN UPDATE SET ".implode(", ",$Fh)." WHEN NOT MATCHED THEN INSERT (".implode(", ",array_keys($O)).") VALUES (".implode(", ",$O).");"))return
false;}return
true;}function
begin(){return
queries("BEGIN TRANSACTION");}}function
idf_escape($t){return"[".str_replace("]","]]",$t)."]";}function
table($t){return($_GET["ns"]!=""?idf_escape($_GET["ns"]).".":"").idf_escape($t);}function
connect(){global$b;$h=new
Min_DB;$Bb=$b->credentials();if($h->connect($Bb[0],$Bb[1],$Bb[2]))return$h;return$h->error;}function
get_databases(){return
get_vals("EXEC sp_databases");}function
limit($H,$Z,$z,$D=0,$rg=" "){return($z!==null?" TOP (".($z+$D).")":"")." $H$Z";}function
limit1($H,$Z){return
limit($H,$Z,1);}function
db_collation($k,$kb){global$h;return$h->result("SELECT collation_name FROM sys.databases WHERE name =  ".q($k));}function
engines(){return
array();}function
logged_user(){global$h;return$h->result("SELECT SUSER_NAME()");}function
tables_list(){return
get_key_vals("SELECT name, type_desc FROM sys.all_objects WHERE schema_id = SCHEMA_ID(".q(get_schema()).") AND type IN ('S', 'U', 'V') ORDER BY name");}function
count_tables($j){global$h;$J=array();foreach($j
as$k){$h->select_db($k);$J[$k]=$h->result("SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES");}return$J;}function
table_status($C=""){$J=array();foreach(get_rows("SELECT name AS Name, type_desc AS Engine FROM sys.all_objects WHERE schema_id = SCHEMA_ID(".q(get_schema()).") AND type IN ('S', 'U', 'V') ".($C!=""?"AND name = ".q($C):"ORDER BY name"))as$K){if($C!="")return$K;$J[$K["Name"]]=$K;}return$J;}function
is_view($R){return$R["Engine"]=="VIEW";}function
fk_support($R){return
true;}function
fields($Q){$J=array();foreach(get_rows("SELECT c.*, t.name type, d.definition [default]
FROM sys.all_columns c
JOIN sys.all_objects o ON c.object_id = o.object_id
JOIN sys.types t ON c.user_type_id = t.user_type_id
LEFT JOIN sys.default_constraints d ON c.default_object_id = d.parent_column_id
WHERE o.schema_id = SCHEMA_ID(".q(get_schema()).") AND o.type IN ('S', 'U', 'V') AND o.name = ".q($Q))as$K){$U=$K["type"];$y=(preg_match("~char|binary~",$U)?$K["max_length"]:($U=="decimal"?"$K[precision],$K[scale]":""));$J[$K["name"]]=array("field"=>$K["name"],"full_type"=>$U.($y?"($y)":""),"type"=>$U,"length"=>$y,"default"=>$K["default"],"null"=>$K["is_nullable"],"auto_increment"=>$K["is_identity"],"collation"=>$K["collation_name"],"privileges"=>array("insert"=>1,"select"=>1,"update"=>1),"primary"=>$K["is_identity"],);}return$J;}function
indexes($Q,$i=null){$J=array();foreach(get_rows("SELECT i.name, key_ordinal, is_unique, is_primary_key, c.name AS column_name, is_descending_key
FROM sys.indexes i
INNER JOIN sys.index_columns ic ON i.object_id = ic.object_id AND i.index_id = ic.index_id
INNER JOIN sys.columns c ON ic.object_id = c.object_id AND ic.column_id = c.column_id
WHERE OBJECT_NAME(i.object_id) = ".q($Q),$i)as$K){$C=$K["name"];$J[$C]["type"]=($K["is_primary_key"]?"PRIMARY":($K["is_unique"]?"UNIQUE":"INDEX"));$J[$C]["lengths"]=array();$J[$C]["columns"][$K["key_ordinal"]]=$K["column_name"];$J[$C]["descs"][$K["key_ordinal"]]=($K["is_descending_key"]?'1':null);}return$J;}function
view($C){global$h;return
array("select"=>preg_replace('~^(?:[^[]|\\[[^]]*])*\\s+AS\\s+~isU','',$h->result("SELECT VIEW_DEFINITION FROM INFORMATION_SCHEMA.VIEWS WHERE TABLE_SCHEMA = SCHEMA_NAME() AND TABLE_NAME = ".q($C))));}function
collations(){$J=array();foreach(get_vals("SELECT name FROM fn_helpcollations()")as$d)$J[preg_replace('~_.*~','',$d)][]=$d;return$J;}function
information_schema($k){return
false;}function
error(){global$h;return
nl_br(h(preg_replace('~^(\\[[^]]*])+~m','',$h->error)));}function
create_database($k,$d){return
queries("CREATE DATABASE ".idf_escape($k).(preg_match('~^[a-z0-9_]+$~i',$d)?" COLLATE $d":""));}function
drop_databases($j){return
queries("DROP DATABASE ".implode(", ",array_map('idf_escape',$j)));}function
rename_database($C,$d){if(preg_match('~^[a-z0-9_]+$~i',$d))queries("ALTER DATABASE ".idf_escape(DB)." COLLATE $d");queries("ALTER DATABASE ".idf_escape(DB)." MODIFY NAME = ".idf_escape($C));return
true;}function
auto_increment(){return" IDENTITY".($_POST["Auto_increment"]!=""?"(".(+$_POST["Auto_increment"]).",1)":"")." PRIMARY KEY";}function
alter_table($Q,$C,$o,$Jc,$ob,$kc,$d,$Ka,$kf){$c=array();foreach($o
as$n){$e=idf_escape($n[0]);$X=$n[1];if(!$X)$c["DROP"][]=" COLUMN $e";else{$X[1]=preg_replace("~( COLLATE )'(\\w+)'~","\\1\\2",$X[1]);if($n[0]=="")$c["ADD"][]="\n  ".implode("",$X).($Q==""?substr($Jc[$X[0]],16+strlen($X[0])):"");else{unset($X[6]);if($e!=$X[0])queries("EXEC sp_rename ".q(table($Q).".$e").", ".q(idf_unescape($X[0])).", 'COLUMN'");$c["ALTER COLUMN ".implode("",$X)][]="";}}}if($Q=="")return
queries("CREATE TABLE ".table($C)." (".implode(",",(array)$c["ADD"])."\n)");if($Q!=$C)queries("EXEC sp_rename ".q(table($Q)).", ".q($C));if($Jc)$c[""]=$Jc;foreach($c
as$x=>$X){if(!queries("ALTER TABLE ".idf_escape($C)." $x".implode(",",$X)))return
false;}return
true;}function
alter_indexes($Q,$c){$u=array();$Vb=array();foreach($c
as$X){if($X[2]=="DROP"){if($X[0]=="PRIMARY")$Vb[]=idf_escape($X[1]);else$u[]=idf_escape($X[1])." ON ".table($Q);}elseif(!queries(($X[0]!="PRIMARY"?"CREATE $X[0] ".($X[0]!="INDEX"?"INDEX ":"").idf_escape($X[1]!=""?$X[1]:uniqid($Q."_"))." ON ".table($Q):"ALTER TABLE ".table($Q)." ADD PRIMARY KEY")." (".implode(", ",$X[2]).")"))return
false;}return(!$u||queries("DROP INDEX ".implode(", ",$u)))&&(!$Vb||queries("ALTER TABLE ".table($Q)." DROP ".implode(", ",$Vb)));}function
last_id(){global$h;return$h->result("SELECT SCOPE_IDENTITY()");}function
explain($h,$H){$h->query("SET SHOWPLAN_ALL ON");$J=$h->query($H);$h->query("SET SHOWPLAN_ALL OFF");return$J;}function
found_rows($R,$Z){}function
foreign_keys($Q){$J=array();foreach(get_rows("EXEC sp_fkeys @fktable_name = ".q($Q))as$K){$p=&$J[$K["FK_NAME"]];$p["table"]=$K["PKTABLE_NAME"];$p["source"][]=$K["FKCOLUMN_NAME"];$p["target"][]=$K["PKCOLUMN_NAME"];}return$J;}function
truncate_tables($S){return
apply_queries("TRUNCATE TABLE",$S);}function
drop_views($Th){return
queries("DROP VIEW ".implode(", ",array_map('table',$Th)));}function
drop_tables($S){return
queries("DROP TABLE ".implode(", ",array_map('table',$S)));}function
move_tables($S,$Th,$Xg){return
apply_queries("ALTER SCHEMA ".idf_escape($Xg)." TRANSFER",array_merge($S,$Th));}function
trigger($C){if($C=="")return
array();$L=get_rows("SELECT s.name [Trigger],
CASE WHEN OBJECTPROPERTY(s.id, 'ExecIsInsertTrigger') = 1 THEN 'INSERT' WHEN OBJECTPROPERTY(s.id, 'ExecIsUpdateTrigger') = 1 THEN 'UPDATE' WHEN OBJECTPROPERTY(s.id, 'ExecIsDeleteTrigger') = 1 THEN 'DELETE' END [Event],
CASE WHEN OBJECTPROPERTY(s.id, 'ExecIsInsteadOfTrigger') = 1 THEN 'INSTEAD OF' ELSE 'AFTER' END [Timing],
c.text
FROM sysobjects s
JOIN syscomments c ON s.id = c.id
WHERE s.xtype = 'TR' AND s.name = ".q($C));$J=reset($L);if($J)$J["Statement"]=preg_replace('~^.+\\s+AS\\s+~isU','',$J["text"]);return$J;}function
triggers($Q){$J=array();foreach(get_rows("SELECT sys1.name,
CASE WHEN OBJECTPROPERTY(sys1.id, 'ExecIsInsertTrigger') = 1 THEN 'INSERT' WHEN OBJECTPROPERTY(sys1.id, 'ExecIsUpdateTrigger') = 1 THEN 'UPDATE' WHEN OBJECTPROPERTY(sys1.id, 'ExecIsDeleteTrigger') = 1 THEN 'DELETE' END [Event],
CASE WHEN OBJECTPROPERTY(sys1.id, 'ExecIsInsteadOfTrigger') = 1 THEN 'INSTEAD OF' ELSE 'AFTER' END [Timing]
FROM sysobjects sys1
JOIN sysobjects sys2 ON sys1.parent_obj = sys2.id
WHERE sys1.xtype = 'TR' AND sys2.name = ".q($Q))as$K)$J[$K["name"]]=array($K["Timing"],$K["Event"]);return$J;}function
trigger_options(){return
array("Timing"=>array("AFTER","INSTEAD OF"),"Event"=>array("INSERT","UPDATE","DELETE"),"Type"=>array("AS"),);}function
schemas(){return
get_vals("SELECT name FROM sys.schemas");}function
get_schema(){global$h;if($_GET["ns"]!="")return$_GET["ns"];return$h->result("SELECT SCHEMA_NAME()");}function
set_schema($kg){return
true;}function
use_sql($Fb){return"USE ".idf_escape($Fb);}function
show_variables(){return
array();}function
show_status(){return
array();}function
convert_field($n){}function
unconvert_field($n,$J){return$J;}function
support($Cc){return
preg_match('~^(columns|database|drop_col|indexes|scheme|sql|table|trigger|view|view_trigger)$~',$Cc);}$w="mssql";$yh=array();$Hg=array();foreach(array(lang(24)=>array("tinyint"=>3,"smallint"=>5,"int"=>10,"bigint"=>20,"bit"=>1,"decimal"=>0,"real"=>12,"float"=>53,"smallmoney"=>10,"money"=>20),lang(25)=>array("date"=>10,"smalldatetime"=>19,"datetime"=>19,"datetime2"=>19,"time"=>8,"datetimeoffset"=>10),lang(26)=>array("char"=>8000,"varchar"=>8000,"text"=>2147483647,"nchar"=>4000,"nvarchar"=>4000,"ntext"=>1073741823),lang(27)=>array("binary"=>8000,"varbinary"=>8000,"image"=>2147483647),)as$x=>$X){$yh+=$X;$Hg[$x]=array_keys($X);}$Eh=array();$Oe=array("=","<",">","<=",">=","!=","LIKE","LIKE %%","IN","IS NULL","NOT LIKE","NOT IN","IS NOT NULL");$Sc=array("len","lower","round","upper");$Xc=array("avg","count","count distinct","max","min","sum");$cc=array(array("date|time"=>"getdate",),array("int|decimal|real|float|money|datetime"=>"+/-","char|text"=>"+",));}$Ub["simpledb"]="SimpleDB";if(isset($_GET["simpledb"])){$wf=array("SimpleXML");define("DRIVER","simpledb");if(class_exists('SimpleXMLElement')){class
Min_DB{var$extension="SimpleXML",$server_info='2009-04-15',$error,$timeout,$next,$affected_rows,$_result;function
select_db($Fb){return($Fb=="domain");}function
query($H,$zh=false){$F=array('SelectExpression'=>$H,'ConsistentRead'=>'true');if($this->next)$F['NextToken']=$this->next;$I=sdb_request_all('Select','Item',$F,$this->timeout);if($I===false)return$I;if(preg_match('~^\s*SELECT\s+COUNT\(~i',$H)){$Lg=0;foreach($I
as$Bd)$Lg+=$Bd->Attribute->Value;$I=array((object)array('Attribute'=>array((object)array('Name'=>'Count','Value'=>$Lg,))));}return
new
Min_Result($I);}function
multi_query($H){return$this->_result=$this->query($H);}function
store_result(){return$this->_result;}function
next_result(){return
false;}function
quote($P){return"'".str_replace("'","''",$P)."'";}}class
Min_Result{var$num_rows,$_rows=array(),$_offset=0;function
Min_Result($I){foreach($I
as$Bd){$K=array();if($Bd->Name!='')$K['itemName()']=(string)$Bd->Name;foreach($Bd->Attribute
as$Ha){$C=$this->_processValue($Ha->Name);$Y=$this->_processValue($Ha->Value);if(isset($K[$C])){$K[$C]=(array)$K[$C];$K[$C][]=$Y;}else$K[$C]=$Y;}$this->_rows[]=$K;foreach($K
as$x=>$X){if(!isset($this->_rows[0][$x]))$this->_rows[0][$x]=null;}}$this->num_rows=count($this->_rows);}function
_processValue($fc){return(is_object($fc)&&$fc['encoding']=='base64'?base64_decode($fc):(string)$fc);}function
fetch_assoc(){$K=current($this->_rows);if(!$K)return$K;$J=array();foreach($this->_rows[0]as$x=>$X)$J[$x]=$K[$x];next($this->_rows);return$J;}function
fetch_row(){$J=$this->fetch_assoc();if(!$J)return$J;return
array_values($J);}function
fetch_field(){$Hd=array_keys($this->_rows[0]);return(object)array('name'=>$Hd[$this->_offset++]);}}}class
Min_Driver
extends
Min_SQL{public$zf="itemName()";function
_chunkRequest($id,$va,$F,$vc=array()){global$h;foreach(array_chunk($id,25)as$db){$gf=$F;foreach($db
as$r=>$s){$gf["Item.$r.ItemName"]=$s;foreach($vc
as$x=>$X)$gf["Item.$r.$x"]=$X;}if(!sdb_request($va,$gf))return
false;}$h->affected_rows=count($id);return
true;}function
_extractIds($Q,$Jf,$z){$J=array();if(preg_match_all("~itemName\(\) = (('[^']*+')+)~",$Jf,$be))$J=array_map('idf_unescape',$be[1]);else{foreach(sdb_request_all('Select','Item',array('SelectExpression'=>'SELECT itemName() FROM '.table($Q).$Jf.($z?" LIMIT 1":"")))as$Bd)$J[]=$Bd->Name;}return$J;}function
select($Q,$M,$Z,$Vc,$Te=array(),$z=1,$E=0,$Af=false){global$h;$h->next=$_GET["next"];$J=parent::select($Q,$M,$Z,$Vc,$Te,$z,$E,$Af);$h->next=0;return$J;}function
delete($Q,$Jf,$z=0){return$this->_chunkRequest($this->_extractIds($Q,$Jf,$z),'BatchDeleteAttributes',array('DomainName'=>$Q));}function
update($Q,$O,$Jf,$z=0,$rg="\n"){$Lb=array();$td=array();$r=0;$id=$this->_extractIds($Q,$Jf,$z);$s=idf_unescape($O["`itemName()`"]);unset($O["`itemName()`"]);foreach($O
as$x=>$X){$x=idf_unescape($x);if($X=="NULL"||($s!=""&&array($s)!=$id))$Lb["Attribute.".count($Lb).".Name"]=$x;if($X!="NULL"){foreach((array)$X
as$Dd=>$W){$td["Attribute.$r.Name"]=$x;$td["Attribute.$r.Value"]=(is_array($X)?$W:idf_unescape($W));if(!$Dd)$td["Attribute.$r.Replace"]="true";$r++;}}}$F=array('DomainName'=>$Q);return(!$td||$this->_chunkRequest(($s!=""?array($s):$id),'BatchPutAttributes',$F,$td))&&(!$Lb||$this->_chunkRequest($id,'BatchDeleteAttributes',$F,$Lb));}function
insert($Q,$O){$F=array("DomainName"=>$Q);$r=0;foreach($O
as$C=>$Y){if($Y!="NULL"){$C=idf_unescape($C);if($C=="itemName()")$F["ItemName"]=idf_unescape($Y);else{foreach((array)$Y
as$X){$F["Attribute.$r.Name"]=$C;$F["Attribute.$r.Value"]=(is_array($Y)?$X:idf_unescape($Y));$r++;}}}}return
sdb_request('PutAttributes',$F);}function
insertUpdate($Q,$L,$zf){foreach($L
as$O){if(!$this->update($Q,$O,"WHERE `itemName()` = ".q($O["`itemName()`"])))return
false;}return
true;}function
begin(){return
false;}function
commit(){return
false;}function
rollback(){return
false;}}function
connect(){return
new
Min_DB;}function
support($Cc){return
preg_match('~sql~',$Cc);}function
logged_user(){global$b;$Bb=$b->credentials();return$Bb[1];}function
get_databases(){return
array("domain");}function
collations(){return
array();}function
db_collation($k,$kb){}function
tables_list(){global$h;$J=array();foreach(sdb_request_all('ListDomains','DomainName')as$Q)$J[(string)$Q]='table';if($h->error&&defined("PAGE_HEADER"))echo"<p class='error'>".error()."\n";return$J;}function
table_status($C="",$Bc=false){$J=array();foreach(($C!=""?array($C=>true):tables_list())as$Q=>$U){$K=array("Name"=>$Q,"Auto_increment"=>"");if(!$Bc){$oe=sdb_request('DomainMetadata',array('DomainName'=>$Q));if($oe){foreach(array("Rows"=>"ItemCount","Data_length"=>"ItemNamesSizeBytes","Index_length"=>"AttributeValuesSizeBytes","Data_free"=>"AttributeNamesSizeBytes",)as$x=>$X)$K[$x]=(string)$oe->$X;}}if($C!="")return$K;$J[$Q]=$K;}return$J;}function
explain($h,$H){}function
error(){global$h;return
h($h->error);}function
information_schema(){}function
is_view($R){}function
indexes($Q,$i=null){return
array(array("type"=>"PRIMARY","columns"=>array("itemName()")),);}function
fields($Q){return
fields_from_edit();}function
foreign_keys($Q){return
array();}function
table($t){return
idf_escape($t);}function
idf_escape($t){return"`".str_replace("`","``",$t)."`";}function
limit($H,$Z,$z,$D=0,$rg=" "){return" $H$Z".($z!==null?$rg."LIMIT $z":"");}function
unconvert_field($n,$J){return$J;}function
fk_support($R){}function
engines(){return
array();}function
alter_table($Q,$C,$o,$Jc,$ob,$kc,$d,$Ka,$kf){return($Q==""&&sdb_request('CreateDomain',array('DomainName'=>$C)));}function
drop_tables($S){foreach($S
as$Q){if(!sdb_request('DeleteDomain',array('DomainName'=>$Q)))return
false;}return
true;}function
count_tables($j){foreach($j
as$k)return
array($k=>count(tables_list()));}function
found_rows($R,$Z){return($Z?null:$R["Rows"]);}function
last_id(){}function
hmac($Aa,$Db,$x,$Nf=false){$Ta=64;if(strlen($x)>$Ta)$x=pack("H*",$Aa($x));$x=str_pad($x,$Ta,"\0");$Ed=$x^str_repeat("\x36",$Ta);$Fd=$x^str_repeat("\x5C",$Ta);$J=$Aa($Fd.pack("H*",$Aa($Ed.$Db)));if($Nf)$J=pack("H*",$J);return$J;}function
sdb_request($va,$F=array()){global$b,$h;list($fd,$F['AWSAccessKeyId'],$ng)=$b->credentials();$F['Action']=$va;$F['Timestamp']=gmdate('Y-m-d\TH:i:s+00:00');$F['Version']='2009-04-15';$F['SignatureVersion']=2;$F['SignatureMethod']='HmacSHA1';ksort($F);$H='';foreach($F
as$x=>$X)$H.='&'.rawurlencode($x).'='.rawurlencode($X);$H=str_replace('%7E','~',substr($H,1));$H.="&Signature=".urlencode(base64_encode(hmac('sha1',"POST\n".preg_replace('~^https?://~','',$fd)."\n/\n$H",$ng,true)));@ini_set('track_errors',1);$Ec=@file_get_contents((preg_match('~^https?://~',$fd)?$fd:"http://$fd"),false,stream_context_create(array('http'=>array('method'=>'POST','content'=>$H,'ignore_errors'=>1,))));if(!$Ec){$h->error=$php_errormsg;return
false;}libxml_use_internal_errors(true);$Zh=simplexml_load_string($Ec);if(!$Zh){$m=libxml_get_last_error();$h->error=$m->message;return
false;}if($Zh->Errors){$m=$Zh->Errors->Error;$h->error="$m->Message ($m->Code)";return
false;}$h->error='';$Wg=$va."Result";return($Zh->$Wg?$Zh->$Wg:true);}function
sdb_request_all($va,$Wg,$F=array(),$fh=0){$J=array();$Dg=($fh?microtime(true):0);$z=(preg_match('~LIMIT\s+(\d+)\s*$~i',$F['SelectExpression'],$B)?$B[1]:0);do{$Zh=sdb_request($va,$F);if(!$Zh)break;foreach($Zh->$Wg
as$fc)$J[]=$fc;if($z&&count($J)>=$z){$_GET["next"]=$Zh->NextToken;break;}if($fh&&microtime(true)-$Dg>$fh)return
false;$F['NextToken']=$Zh->NextToken;if($z)$F['SelectExpression']=preg_replace('~\d+\s*$~',$z-count($J),$F['SelectExpression']);}while($Zh->NextToken);return$J;}$w="simpledb";$Oe=array("=","<",">","<=",">=","!=","LIKE","LIKE %%","IN","IS NULL","NOT LIKE","IS NOT NULL");$Sc=array();$Xc=array("count");$cc=array(array("json"));}$Ub["mongo"]="MongoDB (beta)";if(isset($_GET["mongo"])){$wf=array("mongo");define("DRIVER","mongo");if(class_exists('MongoDB')){class
Min_DB{var$extension="Mongo",$error,$last_id,$_link,$_db;function
connect($N,$V,$G){global$b;$k=$b->database();$Re=array();if($V!=""){$Re["username"]=$V;$Re["password"]=$G;}if($k!="")$Re["db"]=$k;try{$this->_link=@new
MongoClient("mongodb://$N",$Re);return
true;}catch(Exception$rc){$this->error=$rc->getMessage();return
false;}}function
query($H){return
false;}function
select_db($Fb){try{$this->_db=$this->_link->selectDB($Fb);return
true;}catch(Exception$rc){$this->error=$rc->getMessage();return
false;}}function
quote($P){return$P;}}class
Min_Result{var$num_rows,$_rows=array(),$_offset=0,$_charset=array();function
Min_Result($I){foreach($I
as$Bd){$K=array();foreach($Bd
as$x=>$X){if(is_a($X,'MongoBinData'))$this->_charset[$x]=63;$K[$x]=(is_a($X,'MongoId')?'ObjectId("'.strval($X).'")':(is_a($X,'MongoDate')?gmdate("Y-m-d H:i:s",$X->sec)." GMT":(is_a($X,'MongoBinData')?$X->bin:(is_a($X,'MongoRegex')?strval($X):(is_object($X)?get_class($X):$X)))));}$this->_rows[]=$K;foreach($K
as$x=>$X){if(!isset($this->_rows[0][$x]))$this->_rows[0][$x]=null;}}$this->num_rows=count($this->_rows);}function
fetch_assoc(){$K=current($this->_rows);if(!$K)return$K;$J=array();foreach($this->_rows[0]as$x=>$X)$J[$x]=$K[$x];next($this->_rows);return$J;}function
fetch_row(){$J=$this->fetch_assoc();if(!$J)return$J;return
array_values($J);}function
fetch_field(){$Hd=array_keys($this->_rows[0]);$C=$Hd[$this->_offset++];return(object)array('name'=>$C,'charsetnr'=>$this->_charset[$C],);}}}class
Min_Driver
extends
Min_SQL{public$zf="_id";function
quote($Y){return($Y===null?$Y:parent::quote($Y));}function
select($Q,$M,$Z,$Vc,$Te=array(),$z=1,$E=0,$Af=false){$M=($M==array("*")?array():array_fill_keys($M,true));$zg=array();foreach($Te
as$X){$X=preg_replace('~ DESC$~','',$X,1,$yb);$zg[$X]=($yb?-1:1);}return
new
Min_Result($this->_conn->_db->selectCollection($Q)->find(array(),$M)->sort($zg)->limit(+$z)->skip($E*$z));}function
insert($Q,$O){try{$J=$this->_conn->_db->selectCollection($Q)->insert($O);$this->_conn->errno=$J['code'];$this->_conn->error=$J['err'];$this->_conn->last_id=$O['_id'];return!$J['err'];}catch(Exception$rc){$this->_conn->error=$rc->getMessage();return
false;}}}function
connect(){global$b;$h=new
Min_DB;$Bb=$b->credentials();if($h->connect($Bb[0],$Bb[1],$Bb[2]))return$h;return$h->error;}function
error(){global$h;return
h($h->error);}function
logged_user(){global$b;$Bb=$b->credentials();return$Bb[1];}function
get_databases($Ic){global$h;$J=array();$Ib=$h->_link->listDBs();foreach($Ib['databases']as$k)$J[]=$k['name'];return$J;}function
collations(){return
array();}function
db_collation($k,$kb){}function
count_tables($j){global$h;$J=array();foreach($j
as$k)$J[$k]=count($h->_link->selectDB($k)->getCollectionNames(true));return$J;}function
tables_list(){global$h;return
array_fill_keys($h->_db->getCollectionNames(true),'table');}function
table_status($C="",$Bc=false){$J=array();foreach(tables_list()as$Q=>$U){$J[$Q]=array("Name"=>$Q);if($C==$Q)return$J[$Q];}return$J;}function
information_schema(){}function
is_view($R){}function
drop_databases($j){global$h;foreach($j
as$k){$Yf=$h->_link->selectDB($k)->drop();if(!$Yf['ok'])return
false;}return
true;}function
indexes($Q,$i=null){global$h;$J=array();foreach($h->_db->selectCollection($Q)->getIndexInfo()as$u){$Ob=array();foreach($u["key"]as$e=>$U)$Ob[]=($U==-1?'1':null);$J[$u["name"]]=array("type"=>($u["name"]=="_id_"?"PRIMARY":($u["unique"]?"UNIQUE":"INDEX")),"columns"=>array_keys($u["key"]),"lengths"=>array(),"descs"=>$Ob,);}return$J;}function
fields($Q){return
fields_from_edit();}function
convert_field($n){}function
unconvert_field($n,$J){return$J;}function
foreign_keys($Q){return
array();}function
fk_support($R){}function
engines(){return
array();}function
found_rows($R,$Z){global$h;return$h->_db->selectCollection($_GET["select"])->count($Z);}function
alter_table($Q,$C,$o,$Jc,$ob,$kc,$d,$Ka,$kf){global$h;if($Q==""){$h->_db->createCollection($C);return
true;}}function
drop_tables($S){global$h;foreach($S
as$Q){$Yf=$h->_db->selectCollection($Q)->drop();if(!$Yf['ok'])return
false;}return
true;}function
truncate_tables($S){global$h;foreach($S
as$Q){$Yf=$h->_db->selectCollection($Q)->remove();if(!$Yf['ok'])return
false;}return
true;}function
alter_indexes($Q,$c){global$h;foreach($c
as$X){list($U,$C,$O)=$X;if($O=="DROP")$J=$h->_db->command(array("deleteIndexes"=>$Q,"index"=>$C));else{$f=array();foreach($O
as$e){$e=preg_replace('~ DESC$~','',$e,1,$yb);$f[$e]=($yb?-1:1);}$J=$h->_db->selectCollection($Q)->ensureIndex($f,array("unique"=>($U=="UNIQUE"),"name"=>$C,));}if($J['errmsg']){$h->error=$J['errmsg'];return
false;}}return
true;}function
last_id(){global$h;return$h->last_id;}function
table($t){return$t;}function
idf_escape($t){return$t;}function
support($Cc){return
preg_match("~database|indexes~",$Cc);}$w="mongo";$Oe=array("=");$Sc=array();$Xc=array();$cc=array(array("json"));}$Ub["elastic"]="Elasticsearch (beta)";if(isset($_GET["elastic"])){$wf=array("json");define("DRIVER","elastic");if(function_exists('json_decode')){class
Min_DB{var$extension="JSON",$server_info,$errno,$error,$_url;function
rootQuery($nf,$tb=array(),$pe='GET'){@ini_set('track_errors',1);$Ec=@file_get_contents($this->_url.'/'.ltrim($nf,'/'),false,stream_context_create(array('http'=>array('method'=>$pe,'content'=>json_encode($tb),'ignore_errors'=>1,))));if(!$Ec){$this->error=$php_errormsg;return$Ec;}if(!preg_match('~^HTTP/[0-9.]+ 2~i',$http_response_header[0])){$this->error=$Ec;return
false;}$J=json_decode($Ec,true);if(!$J){$this->errno=json_last_error();if(function_exists('json_last_error_msg'))$this->error=json_last_error_msg();else{$sb=get_defined_constants(true);foreach($sb['json']as$C=>$Y){if($Y==$this->errno&&preg_match('~^JSON_ERROR_~',$C)){$this->error=$C;break;}}}}return$J;}function
query($nf,$tb=array(),$pe='GET'){return$this->rootQuery(($this->_db!=""?"$this->_db/":"/").ltrim($nf,'/'),$tb,$pe);}function
connect($N,$V,$G){$this->_url="http://$V:$G@$N/";$J=$this->query('');if($J)$this->server_info=$J['version']['number'];return(bool)$J;}function
select_db($Fb){$this->_db=$Fb;return
true;}function
quote($P){return$P;}}class
Min_Result{var$num_rows,$_rows;function
Min_Result($L){$this->num_rows=count($this->_rows);$this->_rows=$L;reset($this->_rows);}function
fetch_assoc(){$J=current($this->_rows);next($this->_rows);return$J;}function
fetch_row(){return
array_values($this->fetch_assoc());}}}class
Min_Driver
extends
Min_SQL{function
select($Q,$M,$Z,$Vc,$Te=array(),$z=1,$E=0,$Af=false){global$b;$Db=array();$H="$Q/_search";if($M!=array("*"))$Db["fields"]=$M;if($Te){$zg=array();foreach($Te
as$ib){$ib=preg_replace('~ DESC$~','',$ib,1,$yb);$zg[]=($yb?array($ib=>"desc"):$ib);}$Db["sort"]=$zg;}if($z){$Db["size"]=+$z;if($E)$Db["from"]=($E*$z);}foreach((array)$_GET["where"]as$X){if("$X[col]$X[val]"!=""){$ah=array("match"=>array(($X["col"]!=""?$X["col"]:"_all")=>$X["val"]));if($X["op"]=="=")$Db["query"]["filtered"]["filter"]["and"][]=$ah;else$Db["query"]["filtered"]["query"]["bool"]["must"][]=$ah;}}if($Db["query"]&&!$Db["query"]["filtered"]["query"])$Db["query"]["filtered"]["query"]=array("match_all"=>array());$Dg=microtime(true);$mg=$this->_conn->query($H,$Db);if($Af)echo$b->selectQuery("$H: ".print_r($Db,true),format_time($Dg));if(!$mg)return
false;$J=array();foreach($mg['hits']['hits']as$ed){$K=array();$o=$ed['_source'];if($M!=array("*")){$o=array();foreach($M
as$x)$o[$x]=$ed['fields'][$x];}foreach($o
as$x=>$X)$K[$x]=(is_array($X)?json_encode($X):$X);$J[]=$K;}return
new
Min_Result($J);}}function
connect(){global$b;$h=new
Min_DB;$Bb=$b->credentials();if($h->connect($Bb[0],$Bb[1],$Bb[2]))return$h;return$h->error;}function
support($Cc){return
preg_match("~database|table|columns~",$Cc);}function
logged_user(){global$b;$Bb=$b->credentials();return$Bb[1];}function
get_databases(){global$h;$J=$h->rootQuery('_aliases');if($J)$J=array_keys($J);return$J;}function
collations(){return
array();}function
db_collation($k,$kb){}function
count_tables($j){global$h;$J=$h->query('_mapping');if($J)$J=array_map('count',$J);return$J;}function
tables_list(){global$h;$J=$h->query('_mapping');if($J)$J=array_fill_keys(array_keys(reset($J)),'table');return$J;}function
table_status($C="",$Bc=false){global$h;$mg=$h->query("_search?search_type=count",array("facets"=>array("count_by_type"=>array("terms"=>array("field"=>"_type",)))),"POST");$J=array();if($mg){foreach($mg["facets"]["count_by_type"]["terms"]as$Q)$J[$Q["term"]]=array("Name"=>$Q["term"],"Engine"=>"table","Rows"=>$Q["count"],);if($C!=""&&$C==$Q["term"])return$J[$C];}return$J;}function
error(){global$h;return
h($h->error);}function
information_schema(){}function
is_view($R){}function
indexes($Q,$i=null){return
array(array("type"=>"PRIMARY","columns"=>array("_id")),);}function
fields($Q){global$h;$Zd=$h->query("$Q/_mapping");$J=array();if($Zd){foreach($Zd[$Q]['properties']as$C=>$n)$J[$C]=array("field"=>$C,"full_type"=>$n["type"],"type"=>$n["type"],"privileges"=>array("insert"=>1,"select"=>1,"update"=>1),);}return$J;}function
foreign_keys($Q){return
array();}function
table($t){return$t;}function
idf_escape($t){return$t;}function
convert_field($n){}function
unconvert_field($n,$J){return$J;}function
fk_support($R){}function
found_rows($R,$Z){return
null;}function
create_database($k){global$h;return$h->rootQuery(urlencode($k),array(),'PUT');}function
drop_databases($j){global$h;return$h->rootQuery(urlencode(implode(',',$j)),array(),'DELETE');}function
drop_tables($S){global$h;$J=true;foreach($S
as$Q)$J=$J&&$h->query(urlencode($Q),array(),'DELETE');return$J;}$w="elastic";$Oe=array("=","query");$Sc=array();$Xc=array();$cc=array(array("json"));}$Ub=array("server"=>"MySQL")+$Ub;if(!defined("DRIVER")){$wf=array("MySQLi","MySQL","PDO_MySQL");define("DRIVER","server");if(extension_loaded("mysqli")){class
Min_DB
extends
MySQLi{var$extension="MySQLi";function
Min_DB(){parent::init();}function
connect($N,$V,$G){mysqli_report(MYSQLI_REPORT_OFF);list($fd,$sf)=explode(":",$N,2);$J=@$this->real_connect(($N!=""?$fd:ini_get("mysqli.default_host")),($N.$V!=""?$V:ini_get("mysqli.default_user")),($N.$V.$G!=""?$G:ini_get("mysqli.default_pw")),null,(is_numeric($sf)?$sf:ini_get("mysqli.default_port")),(!is_numeric($sf)?$sf:null));if($J){if(method_exists($this,'set_charset'))$this->set_charset("utf8");else$this->query("SET NAMES utf8");}return$J;}function
result($H,$n=0){$I=$this->query($H);if(!$I)return
false;$K=$I->fetch_array();return$K[$n];}function
quote($P){return"'".$this->escape_string($P)."'";}}}elseif(extension_loaded("mysql")&&!(ini_get("sql.safe_mode")&&extension_loaded("pdo_mysql"))){class
Min_DB{var$extension="MySQL",$server_info,$affected_rows,$errno,$error,$_link,$_result;function
connect($N,$V,$G){$this->_link=@mysql_connect(($N!=""?$N:ini_get("mysql.default_host")),("$N$V"!=""?$V:ini_get("mysql.default_user")),("$N$V$G"!=""?$G:ini_get("mysql.default_password")),true,131072);if($this->_link){$this->server_info=mysql_get_server_info($this->_link);if(function_exists('mysql_set_charset'))mysql_set_charset("utf8",$this->_link);else$this->query("SET NAMES utf8");}else$this->error=mysql_error();return(bool)$this->_link;}function
quote($P){return"'".mysql_real_escape_string($P,$this->_link)."'";}function
select_db($Fb){return
mysql_select_db($Fb,$this->_link);}function
query($H,$zh=false){$I=@($zh?mysql_unbuffered_query($H,$this->_link):mysql_query($H,$this->_link));$this->error="";if(!$I){$this->errno=mysql_errno($this->_link);$this->error=mysql_error($this->_link);return
false;}if($I===true){$this->affected_rows=mysql_affected_rows($this->_link);$this->info=mysql_info($this->_link);return
true;}return
new
Min_Result($I);}function
multi_query($H){return$this->_result=$this->query($H);}function
store_result(){return$this->_result;}function
next_result(){return
false;}function
result($H,$n=0){$I=$this->query($H);if(!$I||!$I->num_rows)return
false;return
mysql_result($I->_result,0,$n);}}class
Min_Result{var$num_rows,$_result,$_offset=0;function
Min_Result($I){$this->_result=$I;$this->num_rows=mysql_num_rows($I);}function
fetch_assoc(){return
mysql_fetch_assoc($this->_result);}function
fetch_row(){return
mysql_fetch_row($this->_result);}function
fetch_field(){$J=mysql_fetch_field($this->_result,$this->_offset++);$J->orgtable=$J->table;$J->orgname=$J->name;$J->charsetnr=($J->blob?63:0);return$J;}function
__destruct(){mysql_free_result($this->_result);}}}elseif(extension_loaded("pdo_mysql")){class
Min_DB
extends
Min_PDO{var$extension="PDO_MySQL";function
connect($N,$V,$G){$this->dsn("mysql:charset=utf8;host=".str_replace(":",";unix_socket=",preg_replace('~:(\\d)~',';port=\\1',$N)),$V,$G);$this->query("SET NAMES utf8");return
true;}function
select_db($Fb){return$this->query("USE ".idf_escape($Fb));}function
query($H,$zh=false){$this->setAttribute(1000,!$zh);return
parent::query($H,$zh);}}}class
Min_Driver
extends
Min_SQL{function
insert($Q,$O){return($O?parent::insert($Q,$O):queries("INSERT INTO ".table($Q)." ()\nVALUES ()"));}function
insertUpdate($Q,$L,$zf){$f=array_keys(reset($L));$xf="INSERT INTO ".table($Q)." (".implode(", ",$f).") VALUES\n";$Oh=array();foreach($f
as$x)$Oh[$x]="$x = VALUES($x)";$Kg="\nON DUPLICATE KEY UPDATE ".implode(", ",$Oh);$Oh=array();$y=0;foreach($L
as$O){$Y="(".implode(", ",$O).")";if($Oh&&(strlen($xf)+$y+strlen($Y)+strlen($Kg)>1e6)){if(!queries($xf.implode(",\n",$Oh).$Kg))return
false;$Oh=array();$y=0;}$Oh[]=$Y;$y+=strlen($Y)+2;}return
queries($xf.implode(",\n",$Oh).$Kg);}}function
idf_escape($t){return"`".str_replace("`","``",$t)."`";}function
table($t){return
idf_escape($t);}function
connect(){global$b;$h=new
Min_DB;$Bb=$b->credentials();if($h->connect($Bb[0],$Bb[1],$Bb[2])){$h->query("SET sql_quote_show_create = 1, autocommit = 1");return$h;}$J=$h->error;if(function_exists('iconv')&&!is_utf8($J)&&strlen($ig=iconv("windows-1250","utf-8",$J))>strlen($J))$J=$ig;return$J;}function
get_databases($Ic){global$h;$J=get_session("dbs");if($J===null){$H=($h->server_info>=5?"SELECT SCHEMA_NAME FROM information_schema.SCHEMATA":"SHOW DATABASES");$J=($Ic?slow_query($H):get_vals($H));restart_session();set_session("dbs",$J);stop_session();}return$J;}function
limit($H,$Z,$z,$D=0,$rg=" "){return" $H$Z".($z!==null?$rg."LIMIT $z".($D?" OFFSET $D":""):"");}function
limit1($H,$Z){return
limit($H,$Z,1);}function
db_collation($k,$kb){global$h;$J=null;$zb=$h->result("SHOW CREATE DATABASE ".idf_escape($k),1);if(preg_match('~ COLLATE ([^ ]+)~',$zb,$B))$J=$B[1];elseif(preg_match('~ CHARACTER SET ([^ ]+)~',$zb,$B))$J=$kb[$B[1]][-1];return$J;}function
engines(){$J=array();foreach(get_rows("SHOW ENGINES")as$K){if(preg_match("~YES|DEFAULT~",$K["Support"]))$J[]=$K["Engine"];}return$J;}function
logged_user(){global$h;return$h->result("SELECT USER()");}function
tables_list(){global$h;return
get_key_vals($h->server_info>=5?"SELECT TABLE_NAME, TABLE_TYPE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() ORDER BY TABLE_NAME":"SHOW TABLES");}function
count_tables($j){$J=array();foreach($j
as$k)$J[$k]=count(get_vals("SHOW TABLES IN ".idf_escape($k)));return$J;}function
table_status($C="",$Bc=false){global$h;$J=array();foreach(get_rows($Bc&&$h->server_info>=5?"SELECT TABLE_NAME AS Name, Engine, TABLE_COMMENT AS Comment FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() ".($C!=""?"AND TABLE_NAME = ".q($C):"ORDER BY Name"):"SHOW TABLE STATUS".($C!=""?" LIKE ".q(addcslashes($C,"%_\\")):""))as$K){if($K["Engine"]=="InnoDB")$K["Comment"]=preg_replace('~(?:(.+); )?InnoDB free: .*~','\\1',$K["Comment"]);if(!isset($K["Engine"]))$K["Comment"]="";if($C!="")return$K;$J[$K["Name"]]=$K;}return$J;}function
is_view($R){return$R["Engine"]===null;}function
fk_support($R){return
preg_match('~InnoDB|IBMDB2I~i',$R["Engine"]);}function
fields($Q){$J=array();foreach(get_rows("SHOW FULL COLUMNS FROM ".table($Q))as$K){preg_match('~^([^( ]+)(?:\\((.+)\\))?( unsigned)?( zerofill)?$~',$K["Type"],$B);$J[$K["Field"]]=array("field"=>$K["Field"],"full_type"=>$K["Type"],"type"=>$B[1],"length"=>$B[2],"unsigned"=>ltrim($B[3].$B[4]),"default"=>($K["Default"]!=""||preg_match("~char|set~",$B[1])?$K["Default"]:null),"null"=>($K["Null"]=="YES"),"auto_increment"=>($K["Extra"]=="auto_increment"),"on_update"=>(preg_match('~^on update (.+)~i',$K["Extra"],$B)?$B[1]:""),"collation"=>$K["Collation"],"privileges"=>array_flip(preg_split('~, *~',$K["Privileges"])),"comment"=>$K["Comment"],"primary"=>($K["Key"]=="PRI"),);}return$J;}function
indexes($Q,$i=null){$J=array();foreach(get_rows("SHOW INDEX FROM ".table($Q),$i)as$K){$J[$K["Key_name"]]["type"]=($K["Key_name"]=="PRIMARY"?"PRIMARY":($K["Index_type"]=="FULLTEXT"?"FULLTEXT":($K["Non_unique"]?"INDEX":"UNIQUE")));$J[$K["Key_name"]]["columns"][]=$K["Column_name"];$J[$K["Key_name"]]["lengths"][]=$K["Sub_part"];$J[$K["Key_name"]]["descs"][]=null;}return$J;}function
foreign_keys($Q){global$h,$Ke;static$pf='`(?:[^`]|``)+`';$J=array();$_b=$h->result("SHOW CREATE TABLE ".table($Q),1);if($_b){preg_match_all("~CONSTRAINT ($pf) FOREIGN KEY \\(((?:$pf,? ?)+)\\) REFERENCES ($pf)(?:\\.($pf))? \\(((?:$pf,? ?)+)\\)(?: ON DELETE ($Ke))?(?: ON UPDATE ($Ke))?~",$_b,$be,PREG_SET_ORDER);foreach($be
as$B){preg_match_all("~$pf~",$B[2],$_g);preg_match_all("~$pf~",$B[5],$Xg);$J[idf_unescape($B[1])]=array("db"=>idf_unescape($B[4]!=""?$B[3]:$B[4]),"table"=>idf_unescape($B[4]!=""?$B[4]:$B[3]),"source"=>array_map('idf_unescape',$_g[0]),"target"=>array_map('idf_unescape',$Xg[0]),"on_delete"=>($B[6]?$B[6]:"RESTRICT"),"on_update"=>($B[7]?$B[7]:"RESTRICT"),);}}return$J;}function
view($C){global$h;return
array("select"=>preg_replace('~^(?:[^`]|`[^`]*`)*\\s+AS\\s+~isU','',$h->result("SHOW CREATE VIEW ".table($C),1)));}function
collations(){$J=array();foreach(get_rows("SHOW COLLATION")as$K){if($K["Default"])$J[$K["Charset"]][-1]=$K["Collation"];else$J[$K["Charset"]][]=$K["Collation"];}ksort($J);foreach($J
as$x=>$X)asort($J[$x]);return$J;}function
information_schema($k){global$h;return($h->server_info>=5&&$k=="information_schema")||($h->server_info>=5.5&&$k=="performance_schema");}function
error(){global$h;return
h(preg_replace('~^You have an error.*syntax to use~U',"Syntax error",$h->error));}function
error_line(){global$h;if(preg_match('~ at line ([0-9]+)$~',$h->error,$Uf))return$Uf[1]-1;}function
create_database($k,$d){set_session("dbs",null);return
queries("CREATE DATABASE ".idf_escape($k).($d?" COLLATE ".q($d):""));}function
drop_databases($j){restart_session();set_session("dbs",null);return
apply_queries("DROP DATABASE",$j,'idf_escape');}function
rename_database($C,$d){if(create_database($C,$d)){$Wf=array();foreach(tables_list()as$Q=>$U)$Wf[]=table($Q)." TO ".idf_escape($C).".".table($Q);if(!$Wf||queries("RENAME TABLE ".implode(", ",$Wf))){queries("DROP DATABASE ".idf_escape(DB));return
true;}}return
false;}function
auto_increment(){$La=" PRIMARY KEY";if($_GET["create"]!=""&&$_POST["auto_increment_col"]){foreach(indexes($_GET["create"])as$u){if(in_array($_POST["fields"][$_POST["auto_increment_col"]]["orig"],$u["columns"],true)){$La="";break;}if($u["type"]=="PRIMARY")$La=" UNIQUE";}}return" AUTO_INCREMENT$La";}function
alter_table($Q,$C,$o,$Jc,$ob,$kc,$d,$Ka,$kf){$c=array();foreach($o
as$n)$c[]=($n[1]?($Q!=""?($n[0]!=""?"CHANGE ".idf_escape($n[0]):"ADD"):" ")." ".implode($n[1]).($Q!=""?$n[2]:""):"DROP ".idf_escape($n[0]));$c=array_merge($c,$Jc);$Eg="COMMENT=".q($ob).($kc?" ENGINE=".q($kc):"").($d?" COLLATE ".q($d):"").($Ka!=""?" AUTO_INCREMENT=$Ka":"").$kf;if($Q=="")return
queries("CREATE TABLE ".table($C)." (\n".implode(",\n",$c)."\n) $Eg");if($Q!=$C)$c[]="RENAME TO ".table($C);$c[]=$Eg;return
queries("ALTER TABLE ".table($Q)."\n".implode(",\n",$c));}function
alter_indexes($Q,$c){foreach($c
as$x=>$X)$c[$x]=($X[2]=="DROP"?"\nDROP INDEX ".idf_escape($X[1]):"\nADD $X[0] ".($X[0]=="PRIMARY"?"KEY ":"").($X[1]!=""?idf_escape($X[1])." ":"")."(".implode(", ",$X[2]).")");return
queries("ALTER TABLE ".table($Q).implode(",",$c));}function
truncate_tables($S){return
apply_queries("TRUNCATE TABLE",$S);}function
drop_views($Th){return
queries("DROP VIEW ".implode(", ",array_map('table',$Th)));}function
drop_tables($S){return
queries("DROP TABLE ".implode(", ",array_map('table',$S)));}function
move_tables($S,$Th,$Xg){$Wf=array();foreach(array_merge($S,$Th)as$Q)$Wf[]=table($Q)." TO ".idf_escape($Xg).".".table($Q);return
queries("RENAME TABLE ".implode(", ",$Wf));}function
copy_tables($S,$Th,$Xg){queries("SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO'");foreach($S
as$Q){$C=($Xg==DB?table("copy_$Q"):idf_escape($Xg).".".table($Q));if(!queries("\nDROP TABLE IF EXISTS $C")||!queries("CREATE TABLE $C LIKE ".table($Q))||!queries("INSERT INTO $C SELECT * FROM ".table($Q)))return
false;}foreach($Th
as$Q){$C=($Xg==DB?table("copy_$Q"):idf_escape($Xg).".".table($Q));$Sh=view($Q);if(!queries("DROP VIEW IF EXISTS $C")||!queries("CREATE VIEW $C AS $Sh[select]"))return
false;}return
true;}function
trigger($C){if($C=="")return
array();$L=get_rows("SHOW TRIGGERS WHERE `Trigger` = ".q($C));return
reset($L);}function
triggers($Q){$J=array();foreach(get_rows("SHOW TRIGGERS LIKE ".q(addcslashes($Q,"%_\\")))as$K)$J[$K["Trigger"]]=array($K["Timing"],$K["Event"]);return$J;}function
trigger_options(){return
array("Timing"=>array("BEFORE","AFTER"),"Event"=>array("INSERT","UPDATE","DELETE"),"Type"=>array("FOR EACH ROW"),);}function
routine($C,$U){global$h,$mc,$rd,$yh;$Ba=array("bool","boolean","integer","double precision","real","dec","numeric","fixed","national char","national varchar");$xh="((".implode("|",array_merge(array_keys($yh),$Ba)).")\\b(?:\\s*\\(((?:[^'\")]|$mc)++)\\))?\\s*(zerofill\\s*)?(unsigned(?:\\s+zerofill)?)?)(?:\\s*(?:CHARSET|CHARACTER\\s+SET)\\s*['\"]?([^'\"\\s]+)['\"]?)?";$pf="\\s*(".($U=="FUNCTION"?"":$rd).")?\\s*(?:`((?:[^`]|``)*)`\\s*|\\b(\\S+)\\s+)$xh";$zb=$h->result("SHOW CREATE $U ".idf_escape($C),2);preg_match("~\\(((?:$pf\\s*,?)*)\\)\\s*".($U=="FUNCTION"?"RETURNS\\s+$xh\\s+":"")."(.*)~is",$zb,$B);$o=array();preg_match_all("~$pf\\s*,?~is",$B[1],$be,PREG_SET_ORDER);foreach($be
as$ff){$C=str_replace("``","`",$ff[2]).$ff[3];$o[]=array("field"=>$C,"type"=>strtolower($ff[5]),"length"=>preg_replace_callback("~$mc~s",'normalize_enum',$ff[6]),"unsigned"=>strtolower(preg_replace('~\\s+~',' ',trim("$ff[8] $ff[7]"))),"null"=>1,"full_type"=>$ff[4],"inout"=>strtoupper($ff[1]),"collation"=>strtolower($ff[9]),);}if($U!="FUNCTION")return
array("fields"=>$o,"definition"=>$B[11]);return
array("fields"=>$o,"returns"=>array("type"=>$B[12],"length"=>$B[13],"unsigned"=>$B[15],"collation"=>$B[16]),"definition"=>$B[17],"language"=>"SQL",);}function
routines(){return
get_rows("SELECT ROUTINE_NAME, ROUTINE_TYPE, DTD_IDENTIFIER FROM information_schema.ROUTINES WHERE ROUTINE_SCHEMA = ".q(DB));}function
routine_languages(){return
array();}function
last_id(){global$h;return$h->result("SELECT LAST_INSERT_ID()");}function
explain($h,$H){return$h->query("EXPLAIN ".($h->server_info>=5.1?"PARTITIONS ":"").$H);}function
found_rows($R,$Z){return($Z||$R["Engine"]!="InnoDB"?null:$R["Rows"]);}function
types(){return
array();}function
schemas(){return
array();}function
get_schema(){return"";}function
set_schema($kg){return
true;}function
create_sql($Q,$Ka){global$h;$J=$h->result("SHOW CREATE TABLE ".table($Q),1);if(!$Ka)$J=preg_replace('~ AUTO_INCREMENT=\\d+~','',$J);return$J;}function
truncate_sql($Q){return"TRUNCATE ".table($Q);}function
use_sql($Fb){return"USE ".idf_escape($Fb);}function
trigger_sql($Q,$Ig){$J="";foreach(get_rows("SHOW TRIGGERS LIKE ".q(addcslashes($Q,"%_\\")),null,"-- ")as$K)$J.="\n".($Ig=='CREATE+ALTER'?"DROP TRIGGER IF EXISTS ".idf_escape($K["Trigger"]).";;\n":"")."CREATE TRIGGER ".idf_escape($K["Trigger"])." $K[Timing] $K[Event] ON ".table($K["Table"])." FOR EACH ROW\n$K[Statement];;\n";return$J;}function
show_variables(){return
get_key_vals("SHOW VARIABLES");}function
process_list(){return
get_rows("SHOW FULL PROCESSLIST");}function
show_status(){return
get_key_vals("SHOW STATUS");}function
convert_field($n){if(preg_match("~binary~",$n["type"]))return"HEX(".idf_escape($n["field"]).")";if($n["type"]=="bit")return"BIN(".idf_escape($n["field"])." + 0)";if(preg_match("~geometry|point|linestring|polygon~",$n["type"]))return"AsWKT(".idf_escape($n["field"]).")";}function
unconvert_field($n,$J){if(preg_match("~binary~",$n["type"]))$J="UNHEX($J)";if($n["type"]=="bit")$J="CONV($J, 2, 10) + 0";if(preg_match("~geometry|point|linestring|polygon~",$n["type"]))$J="GeomFromText($J)";return$J;}function
support($Cc){global$h;return!preg_match("~scheme|sequence|type|view_trigger".($h->server_info<5.1?"|event|partitioning".($h->server_info<5?"|routine|trigger|view":""):"")."~",$Cc);}$w="sql";$yh=array();$Hg=array();foreach(array(lang(24)=>array("tinyint"=>3,"smallint"=>5,"mediumint"=>8,"int"=>10,"bigint"=>20,"decimal"=>66,"float"=>12,"double"=>21),lang(25)=>array("date"=>10,"datetime"=>19,"timestamp"=>19,"time"=>10,"year"=>4),lang(26)=>array("char"=>255,"varchar"=>65535,"tinytext"=>255,"text"=>65535,"mediumtext"=>16777215,"longtext"=>4294967295),lang(30)=>array("enum"=>65535,"set"=>64),lang(27)=>array("bit"=>20,"binary"=>255,"varbinary"=>65535,"tinyblob"=>255,"blob"=>65535,"mediumblob"=>16777215,"longblob"=>4294967295),lang(29)=>array("geometry"=>0,"point"=>0,"linestring"=>0,"polygon"=>0,"multipoint"=>0,"multilinestring"=>0,"multipolygon"=>0,"geometrycollection"=>0),)as$x=>$X){$yh+=$X;$Hg[$x]=array_keys($X);}$Eh=array("unsigned","zerofill","unsigned zerofill");$Oe=array("=","<",">","<=",">=","!=","LIKE","LIKE %%","REGEXP","IN","IS NULL","NOT LIKE","NOT REGEXP","NOT IN","IS NOT NULL","SQL");$Sc=array("char_length","date","from_unixtime","lower","round","sec_to_time","time_to_sec","upper");$Xc=array("avg","count","count distinct","group_concat","max","min","sum");$cc=array(array("char"=>"md5/sha1/password/encrypt/uuid","binary"=>"md5/sha1","date|time"=>"now",),array("(^|[^o])int|float|double|decimal"=>"+/-","date"=>"+ interval/- interval","time"=>"addtime/subtime","char|text"=>"concat",));}define("SERVER",$_GET[DRIVER]);define("DB",$_GET["db"]);define("ME",preg_replace('~^[^?]*/([^?]*).*~','\\1',$_SERVER["REQUEST_URI"]).'?'.(sid()?SID.'&':'').(SERVER!==null?DRIVER."=".urlencode(SERVER).'&':'').(isset($_GET["username"])?"username=".urlencode($_GET["username"]).'&':'').(DB!=""?'db='.urlencode(DB).'&'.(isset($_GET["ns"])?"ns=".urlencode($_GET["ns"])."&":""):''));$ia="4.1.0";class
Adminer{var$operators;function
name(){return"<a href='http://www.adminer.org/' target='_blank' id='h1'>Adminer</a>";}function
credentials(){return
array(SERVER,$_GET["username"],get_password());}function
permanentLogin($zb=false){return
password_file($zb);}function
bruteForceKey(){return$_SERVER["REMOTE_ADDR"];}function
database(){return
DB;}function
databases($Ic=true){return
get_databases($Ic);}function
schemas(){return
schemas();}function
queryTimeout(){return
5;}function
headers(){return
true;}function
head(){return
true;}function
loginForm(){global$Ub;echo'<table cellspacing="0">
<tr><th>',lang(31),'<td>',adminer_html_select("auth[driver]",$Ub,DRIVER,"loginDriver(this);"),'<tr><th>',lang(32),'<td><input name="auth[server]" value="',h(SERVER),'" title="hostname[:port]" placeholder="localhost" autocapitalize="off">
<tr><th>',lang(33),'<td><input name="auth[username]" id="username" value="',h($_GET["username"]),'" autocapitalize="off">
<tr><th>',lang(34),'<td><input type="password" name="auth[password]">
<tr><th>',lang(35),'<td><input name="auth[db]" value="',h($_GET["db"]);?>" autocapitalize="off">
</table>
<script type="text/javascript">
var username = document.getElementById('username');
focus(username);
username.form['auth[driver]'].onchange();
</script>
<?php

echo"<p><input type='submit' value='".lang(36)."'>\n",checkbox("auth[permanent]",1,$_COOKIE["adminer_permanent"],lang(37))."\n";}function
login($Xd,$G){return
true;}function
tableName($Og){return
h($Og["Name"]);}function
fieldName($n,$Te=0){return'<span title="'.h($n["full_type"]).'">'.h($n["field"]).'</span>';}function
selectLinks($Og,$O=""){echo'<p class="links">';$Wd=array("select"=>lang(38));if(support("table")||support("indexes"))$Wd["table"]=lang(39);if(support("table")){if(is_view($Og))$Wd["view"]=lang(40);else$Wd["create"]=lang(41);}if($O!==null)$Wd["edit"]=lang(42);foreach($Wd
as$x=>$X)echo" <a href='".h(ME)."$x=".urlencode($Og["Name"]).($x=="edit"?$O:"")."'".bold(isset($_GET[$x])).">$X</a>";echo"\n";}function
foreignKeys($Q){return
foreign_keys($Q);}function
backwardKeys($Q,$Ng){return
array();}function
backwardKeysPrint($Na,$K){}function
selectQuery($H,$eh){global$w;return"<p><code class='jush-$w'>".h(str_replace("\n"," ",$H))."</code> <span class='time'>($eh)</span>".(support("sql")?" <a href='".h(ME)."sql=".urlencode($H)."'>".lang(10)."</a>":"")."</p>";}function
rowDescription($Q){return"";}function
rowDescriptions($L,$Kc){return$L;}function
selectLink($X,$n){}function
selectVal($X,$_,$n,$af){$J=($X===null?"<i>NULL</i>":(preg_match("~char|binary~",$n["type"])&&!preg_match("~var~",$n["type"])?"<code>$X</code>":$X));if(preg_match('~blob|bytea|raw|file~',$n["type"])&&!is_utf8($X))$J=lang(43,strlen($af));return($_?"<a href='".h($_)."'".(is_url($_)?" rel='noreferrer'":"").">$J</a>":$J);}function
editVal($X,$n){return$X;}function
selectColumnsPrint($M,$f){global$Sc,$Xc;print_fieldset("select",lang(44),$M);$r=0;$M[""]=array();foreach($M
as$x=>$X){$X=$_GET["columns"][$x];$e=select_input(" name='columns[$r][col]' onchange='".($x!==""?"selectFieldChange(this.form)":"selectAddRow(this)").";'",$f,$X["col"]);echo"<div>".($Sc||$Xc?"<select name='columns[$r][fun]' onchange='helpClose();".($x!==""?"":" this.nextSibling.nextSibling.onchange();")."'".on_help("getTarget(event).value && getTarget(event).value.replace(/ |\$/, '(') + ')'",1).">".optionlist(array(-1=>"")+array_filter(array(lang(45)=>$Sc,lang(46)=>$Xc)),$X["fun"])."</select>"."($e)":$e)."</div>\n";$r++;}echo"</div></fieldset>\n";}function
selectSearchPrint($Z,$f,$v){print_fieldset("search",lang(47),$Z);foreach($v
as$r=>$u){if($u["type"]=="FULLTEXT"){echo"(<i>".implode("</i>, <i>",array_map('h',$u["columns"]))."</i>) AGAINST"," <input type='search' name='fulltext[$r]' value='".h($_GET["fulltext"][$r])."' onchange='selectFieldChange(this.form);'>",checkbox("boolean[$r]",1,isset($_GET["boolean"][$r]),"BOOL"),"<br>\n";}}$_GET["where"]=(array)$_GET["where"];reset($_GET["where"]);$Ya="this.nextSibling.onchange();";for($r=0;$r<=count($_GET["where"]);$r++){list(,$X)=each($_GET["where"]);if(!$X||("$X[col]$X[val]"!=""&&in_array($X["op"],$this->operators))){echo"<div>".select_input(" name='where[$r][col]' onchange='$Ya'",$f,$X["col"],"(".lang(48).")"),adminer_html_select("where[$r][op]",$this->operators,$X["op"],$Ya),"<input type='search' name='where[$r][val]' value='".h($X["val"])."' onchange='".($X?"selectFieldChange(this.form)":"selectAddRow(this)").";' onkeydown='selectSearchKeydown(this, event);' onsearch='selectSearchSearch(this);'></div>\n";}}echo"</div></fieldset>\n";}function
selectOrderPrint($Te,$f,$v){print_fieldset("sort",lang(49),$Te);$r=0;foreach((array)$_GET["order"]as$x=>$X){if($X!=""){echo"<div>".select_input(" name='order[$r]' onchange='selectFieldChange(this.form);'",$f,$X),checkbox("desc[$r]",1,isset($_GET["desc"][$x]),lang(50))."</div>\n";$r++;}}echo"<div>".select_input(" name='order[$r]' onchange='selectAddRow(this);'",$f),checkbox("desc[$r]",1,false,lang(50))."</div>\n","</div></fieldset>\n";}function
selectLimitPrint($z){echo"<fieldset><legend>".lang(51)."</legend><div>";echo"<input type='number' name='limit' class='size' value='".h($z)."' onchange='selectFieldChange(this.form);'>","</div></fieldset>\n";}function
selectLengthPrint($dh){if($dh!==null){echo"<fieldset><legend>".lang(52)."</legend><div>","<input type='number' name='text_length' class='size' value='".h($dh)."'>","</div></fieldset>\n";}}function
selectActionPrint($v){echo"<fieldset><legend>".lang(53)."</legend><div>","<input type='submit' value='".lang(44)."'>"," <span id='noindex' title='".lang(54)."'></span>","<script type='text/javascript'>\n","var indexColumns = ";$f=array();foreach($v
as$u){if($u["type"]!="FULLTEXT")$f[reset($u["columns"])]=1;}$f[""]=1;foreach($f
as$x=>$X)json_row($x);echo";\n","selectFieldChange(document.getElementById('form'));\n","</script>\n","</div></fieldset>\n";}function
selectCommandPrint(){return!information_schema(DB);}function
selectImportPrint(){return!information_schema(DB);}function
selectEmailPrint($hc,$f){}function
selectColumnsProcess($f,$v){global$Sc,$Xc;$M=array();$Vc=array();foreach((array)$_GET["columns"]as$x=>$X){if($X["fun"]=="count"||($X["col"]!=""&&(!$X["fun"]||in_array($X["fun"],$Sc)||in_array($X["fun"],$Xc)))){$M[$x]=apply_sql_function($X["fun"],($X["col"]!=""?idf_escape($X["col"]):"*"));if(!in_array($X["fun"],$Xc))$Vc[]=$M[$x];}}return
array($M,$Vc);}function
selectSearchProcess($o,$v){global$w;$J=array();foreach($v
as$r=>$u){if($u["type"]=="FULLTEXT"&&$_GET["fulltext"][$r]!="")$J[]="MATCH (".implode(", ",array_map('idf_escape',$u["columns"])).") AGAINST (".q($_GET["fulltext"][$r]).(isset($_GET["boolean"][$r])?" IN BOOLEAN MODE":"").")";}foreach((array)$_GET["where"]as$X){if("$X[col]$X[val]"!=""&&in_array($X["op"],$this->operators)){$qb=" $X[op]";if(preg_match('~IN$~',$X["op"])){$kd=process_length($X["val"]);$qb.=" ".($kd!=""?$kd:"(NULL)");}elseif($X["op"]=="SQL")$qb=" $X[val]";elseif($X["op"]=="LIKE %%")$qb=" LIKE ".$this->processInput($o[$X["col"]],"%$X[val]%");elseif(!preg_match('~NULL$~',$X["op"]))$qb.=" ".$this->processInput($o[$X["col"]],$X["val"]);if($X["col"]!="")$J[]=idf_escape($X["col"]).$qb;else{$lb=array();foreach($o
as$C=>$n){$_d=preg_match('~char|text|enum|set~',$n["type"]);if((is_numeric($X["val"])||!preg_match('~(^|[^o])int|float|double|decimal|bit~',$n["type"]))&&(!preg_match("~[\x80-\xFF]~",$X["val"])||$_d)){$C=idf_escape($C);$lb[]=($w=="sql"&&$_d&&!preg_match('~^utf8~',$n["collation"])?"CONVERT($C USING utf8)":$C);}}$J[]=($lb?"(".implode("$qb OR ",$lb)."$qb)":"0");}}}return$J;}function
selectOrderProcess($o,$v){$J=array();foreach((array)$_GET["order"]as$x=>$X){if($X!="")$J[]=(preg_match('~^((COUNT\\(DISTINCT |[A-Z0-9_]+\\()(`(?:[^`]|``)+`|"(?:[^"]|"")+")\\)|COUNT\\(\\*\\))$~',$X)?$X:idf_escape($X)).(isset($_GET["desc"][$x])?" DESC":"");}return$J;}function
selectLimitProcess(){return(isset($_GET["limit"])?$_GET["limit"]:"50");}function
selectLengthProcess(){return(isset($_GET["text_length"])?$_GET["text_length"]:"100");}function
selectEmailProcess($Z,$Kc){return
false;}function
selectQueryBuild($M,$Z,$Vc,$Te,$z,$E){return"";}function
messageQuery($H,$eh){global$w;restart_session();$cd=&get_session("queries");$s="sql-".count($cd[$_GET["db"]]);if(strlen($H)>1e6)$H=preg_replace('~[\x80-\xFF]+$~','',substr($H,0,1e6))."\n...";$cd[$_GET["db"]][]=array($H,time(),$eh);return" <span class='time'>".@date("H:i:s")."</span> <a href='#$s' onclick=\"return !toggle('$s');\">".lang(55)."</a>"."<div id='$s' class='hidden'><pre><code class='jush-$w'>".shorten_utf8($H,1000).'</code></pre>'.($eh?" <span class='time'>($eh)</span>":'').(support("sql")?'<p><a href="'.h(str_replace("db=".urlencode(DB),"db=".urlencode($_GET["db"]),ME).'sql=&history='.(count($cd[$_GET["db"]])-1)).'">'.lang(10).'</a>':'').'</div>';}function
editFunctions($n){global$cc;$J=($n["null"]?"NULL/":"");foreach($cc
as$x=>$Sc){if(!$x||(!isset($_GET["call"])&&(isset($_GET["select"])||where($_GET)))){foreach($Sc
as$pf=>$X){if(!$pf||preg_match("~$pf~",$n["type"]))$J.="/$X";}if($x&&!preg_match('~set|blob|bytea|raw|file~',$n["type"]))$J.="/SQL";}}if($n["auto_increment"]&&!isset($_GET["select"])&&!where($_GET))$J=lang(56);return
explode("/",$J);}function
editInput($Q,$n,$Ia,$Y){if($n["type"]=="enum")return(isset($_GET["select"])?"<label><input type='radio'$Ia value='-1' checked><i>".lang(8)."</i></label> ":"").($n["null"]?"<label><input type='radio'$Ia value=''".($Y!==null||isset($_GET["select"])?"":" checked")."><i>NULL</i></label> ":"").enum_input("radio",$Ia,$n,$Y,0);return"";}function
processInput($n,$Y,$q=""){if($q=="SQL")return$Y;$C=$n["field"];$J=q($Y);if(preg_match('~^(now|getdate|uuid)$~',$q))$J="$q()";elseif(preg_match('~^current_(date|timestamp)$~',$q))$J=$q;elseif(preg_match('~^([+-]|\\|\\|)$~',$q))$J=idf_escape($C)." $q $J";elseif(preg_match('~^[+-] interval$~',$q))$J=idf_escape($C)." $q ".(preg_match("~^(\\d+|'[0-9.: -]') [A-Z_]+$~i",$Y)?$Y:$J);elseif(preg_match('~^(addtime|subtime|concat)$~',$q))$J="$q(".idf_escape($C).", $J)";elseif(preg_match('~^(md5|sha1|password|encrypt)$~',$q))$J="$q($J)";return
unconvert_field($n,$J);}function
dumpOutput(){$J=array('text'=>lang(57),'file'=>lang(58));if(function_exists('gzencode'))$J['gz']='gzip';return$J;}function
dumpFormat(){return
array('sql'=>'SQL','csv'=>'CSV,','csv;'=>'CSV;','tsv'=>'TSV');}function
dumpDatabase($k){}function
dumpTable($Q,$Ig,$Ad=0){if($_POST["format"]!="sql"){echo"\xef\xbb\xbf";if($Ig)dump_csv(array_keys(fields($Q)));}elseif($Ig){if($Ad==2){$o=array();foreach(fields($Q)as$C=>$n)$o[]=idf_escape($C)." $n[full_type]";$zb="CREATE TABLE ".table($Q)." (".implode(", ",$o).")";}else$zb=create_sql($Q,$_POST["auto_increment"]);if($zb){if($Ig=="DROP+CREATE"||$Ad==1)echo"DROP ".($Ad==2?"VIEW":"TABLE")." IF EXISTS ".table($Q).";\n";if($Ad==1)$zb=remove_definer($zb);echo"$zb;\n\n";}}}function
dumpData($Q,$Ig,$H){global$h,$w;$de=($w=="sqlite"?0:1048576);if($Ig){if($_POST["format"]=="sql"){if($Ig=="TRUNCATE+INSERT")echo
truncate_sql($Q).";\n";$o=fields($Q);}$I=$h->query($H,1);if($I){$td="";$Wa="";$Hd=array();$Kg="";$Dc=($Q!=''?'fetch_assoc':'fetch_row');while($K=$I->$Dc()){if(!$Hd){$Oh=array();foreach($K
as$X){$n=$I->fetch_field();$Hd[]=$n->name;$x=idf_escape($n->name);$Oh[]="$x = VALUES($x)";}$Kg=($Ig=="INSERT+UPDATE"?"\nON DUPLICATE KEY UPDATE ".implode(", ",$Oh):"").";\n";}if($_POST["format"]!="sql"){if($Ig=="table"){dump_csv($Hd);$Ig="INSERT";}dump_csv($K);}else{if(!$td)$td="INSERT INTO ".table($Q)." (".implode(", ",array_map('idf_escape',$Hd)).") VALUES";foreach($K
as$x=>$X){$n=$o[$x];$K[$x]=($X!==null?unconvert_field($n,preg_match('~(^|[^o])int|float|double|decimal~',$n["type"])&&$X!=''?$X:q($X)):"NULL");}$ig=($de?"\n":" ")."(".implode(",\t",$K).")";if(!$Wa)$Wa=$td.$ig;elseif(strlen($Wa)+4+strlen($ig)+strlen($Kg)<$de)$Wa.=",$ig";else{echo$Wa.$Kg;$Wa=$td.$ig;}}}if($Wa)echo$Wa.$Kg;}elseif($_POST["format"]=="sql")echo"-- ".str_replace("\n"," ",$h->error)."\n";}}function
dumpFilename($hd){return
friendly_url($hd!=""?$hd:(SERVER!=""?SERVER:"localhost"));}function
dumpHeaders($hd,$se=false){$df=$_POST["output"];$yc=(preg_match('~sql~',$_POST["format"])?"sql":($se?"tar":"csv"));header("Content-Type: ".($df=="gz"?"application/x-gzip":($yc=="tar"?"application/x-tar":($yc=="sql"||$df!="file"?"text/plain":"text/csv")."; charset=utf-8")));if($df=="gz")ob_start('ob_gzencode',1e6);return$yc;}function
homepage(){echo'<p class="links">'.($_GET["ns"]==""&&support("database")?'<a href="'.h(ME).'database=">'.lang(59)."</a>\n":""),(support("scheme")?"<a href='".h(ME)."scheme='>".($_GET["ns"]!=""?lang(60):lang(61))."</a>\n":""),($_GET["ns"]!==""?'<a href="'.h(ME).'schema=">'.lang(62)."</a>\n":""),(support("privileges")?"<a href='".h(ME)."privileges='>".lang(63)."</a>\n":"");return
true;}function
navigation($re){global$ia,$w,$Ub,$h;echo'<h1>
',$this->name(),' <span class="version">',$ia,'</span>
<a href="http://www.adminer.org/#download" target="_blank" id="version">',(version_compare($ia,$_COOKIE["adminer_version"])<0?h($_COOKIE["adminer_version"]):""),'</a>
</h1>
';if($re=="auth"){$Hc=true;foreach((array)$_SESSION["pwds"]as$Qh=>$vg){foreach($vg
as$N=>$Lh){foreach($Lh
as$V=>$G){if($G!==null){if($Hc){echo"<p id='logins' onmouseover='menuOver(this, event);' onmouseout='menuOut(this);'>\n";$Hc=false;}$Ib=$_SESSION["db"][$Qh][$N][$V];foreach(($Ib?array_keys($Ib):array(""))as$k)echo"<a href='".h(auth_url($Qh,$N,$V,$k))."'>($Ub[$Qh]) ".h($V.($N!=""?"@$N":"").($k!=""?" - $k":""))."</a><br>\n";}}}}}else{if($_GET["ns"]!==""&&!$re&&DB!=""){$h->select_db(DB);$S=table_status('',true);}if(support("sql")){echo'<script type="text/javascript" src="',h(preg_replace("~\\?.*~","",ME))."?file=jush.js&amp;version=4.1.0",'"></script>
<script type="text/javascript">
';if($S){$Wd=array();foreach($S
as$Q=>$U)$Wd[]=preg_quote($Q,'/');echo"var jushLinks = { $w: [ '".js_escape(ME).(support("table")?"table=":"select=")."\$&', /\\b(".implode("|",$Wd).")\\b/g ] };\n";foreach(array("bac","bra","sqlite_quo","mssql_bra")as$X)echo"jushLinks.$X = jushLinks.$w;\n";}echo'bodyLoad(\'',(is_object($h)?substr($h->server_info,0,3):""),'\');
</script>
';}$this->databasesPrint($re);if(DB==""||!$re){echo"<p class='links'>".(support("sql")?"<a href='".h(ME)."sql='".bold(isset($_GET["sql"])&&!isset($_GET["import"])).">".lang(55)."</a>\n<a href='".h(ME)."import='".bold(isset($_GET["import"])).">".lang(64)."</a>\n":"")."";if(support("dump"))echo"<a href='".h(ME)."dump=".urlencode(isset($_GET["table"])?$_GET["table"]:$_GET["select"])."' id='dump'".bold(isset($_GET["dump"])).">".lang(65)."</a>\n";}if($_GET["ns"]!==""&&!$re&&DB!=""){echo'<a href="'.h(ME).'create="'.bold($_GET["create"]==="").">".lang(66)."</a>\n";if(!$S)echo"<p class='message'>".lang(9)."\n";else$this->tablesPrint($S);}}}function
databasesPrint($re){global$b,$h;$j=$this->databases();echo'<form action="">
<p id="dbs">
';hidden_fields_get();$Gb=" onmousedown='dbMouseDown(event, this);' onchange='dbChange(this);'";echo"<span title='".lang(67)."'>DB</span>: ".($j?"<select name='db'$Gb>".optionlist(array(""=>"")+$j,DB)."</select>":'<input name="db" value="'.h(DB).'" autocapitalize="off">'),"<input type='submit' value='".lang(20)."'".($j?" class='hidden'":"").">\n";if($re!="db"&&DB!=""&&$h->select_db(DB)){if(support("scheme")){echo"<br>".lang(68).": <select name='ns'$Gb>".optionlist(array(""=>"")+$b->schemas(),$_GET["ns"])."</select>";if($_GET["ns"]!="")set_schema($_GET["ns"]);}}echo(isset($_GET["sql"])?'<input type="hidden" name="sql" value="">':(isset($_GET["schema"])?'<input type="hidden" name="schema" value="">':(isset($_GET["dump"])?'<input type="hidden" name="dump" value="">':(isset($_GET["privileges"])?'<input type="hidden" name="privileges" value="">':"")))),"</p></form>\n";}function
tablesPrint($S){echo"<p id='tables' onmouseover='menuOver(this, event);' onmouseout='menuOut(this);'>\n";foreach($S
as$Q=>$Eg){echo'<a href="'.h(ME).'select='.urlencode($Q).'"'.bold($_GET["select"]==$Q||$_GET["edit"]==$Q).">".lang(69)."</a> ";$C=$this->tableName($Eg);echo(support("table")||support("indexes")?'<a href="'.h(ME).'table='.urlencode($Q).'"'.bold(in_array($Q,array($_GET["table"],$_GET["create"],$_GET["indexes"],$_GET["foreign"],$_GET["trigger"])),(is_view($Eg)?"view":""))." title='".lang(39)."'>$C</a>":"<span>$C</span>")."<br>\n";}}}$b=(function_exists('adminer_object')?adminer_object():new
Adminer);if($b->operators===null)$b->operators=$Oe;function
page_header($hh,$m="",$Va=array(),$ih=""){global$ca,$ia,$b,$Ub,$w;page_headers();$jh=$hh.($ih!=""?": $ih":"");$kh=strip_tags($jh.(SERVER!=""&&SERVER!="localhost"?h(" - ".SERVER):"")." - ".$b->name());echo'<!DOCTYPE html>
<html lang="',$ca,'" dir="',lang(70),'">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta http-equiv="Content-Script-Type" content="text/javascript">
<meta name="robots" content="noindex">
<title>',$kh,'</title>
<link rel="stylesheet" type="text/css" href="',h(preg_replace("~\\?.*~","",ME))."?file=default.css&amp;version=4.1.0",'">
<script type="text/javascript" src="',h(preg_replace("~\\?.*~","",ME))."?file=functions.js&amp;version=4.1.0",'"></script>
';if($b->head()){echo'<link rel="shortcut icon" type="image/x-icon" href="',h(preg_replace("~\\?.*~","",ME))."?file=favicon.ico&amp;version=4.1.0",'">
<link rel="apple-touch-icon" href="',h(preg_replace("~\\?.*~","",ME))."?file=favicon.ico&amp;version=4.1.0",'">
';if(file_exists("adminer.css")){echo'<link rel="stylesheet" type="text/css" href="adminer.css">
';}}echo'
<body class="',lang(70),' nojs" onkeydown="bodyKeydown(event);" onclick="bodyClick(event);"',(isset($_COOKIE["adminer_version"])?"":" onload=\"verifyVersion('$ia');\""),'>
<script type="text/javascript">
document.body.className = document.body.className.replace(/ nojs/, \' js\');
</script>

<div id="help" class="jush-',$w,' jsonly hidden" onmouseover="helpOpen = 1;" onmouseout="helpMouseout(this, event);"></div>

<div id="content">
';if($Va!==null){$_=substr(preg_replace('~\b(username|db|ns)=[^&]*&~','',ME),0,-1);echo'<p id="breadcrumb"><a href="'.h($_?$_:".").'">'.$Ub[DRIVER].'</a> &raquo; ';$_=substr(preg_replace('~\b(db|ns)=[^&]*&~','',ME),0,-1);$N=(SERVER!=""?h(SERVER):lang(32));if($Va===false)echo"$N\n";else{echo"<a href='".($_?h($_):".")."' accesskey='1' title='Alt+Shift+1'>$N</a> &raquo; ";if($_GET["ns"]!=""||(DB!=""&&is_array($Va)))echo'<a href="'.h($_."&db=".urlencode(DB).(support("scheme")?"&ns=":"")).'">'.h(DB).'</a> &raquo; ';if(is_array($Va)){if($_GET["ns"]!="")echo'<a href="'.h(substr(ME,0,-1)).'">'.h($_GET["ns"]).'</a> &raquo; ';foreach($Va
as$x=>$X){$Nb=(is_array($X)?$X[1]:h($X));if($Nb!="")echo"<a href='".h(ME."$x=").urlencode(is_array($X)?$X[0]:$X)."'>$Nb</a> &raquo; ";}}echo"$hh\n";}}echo"<h2>$jh</h2>\n";restart_session();page_messages($m);$j=&get_session("dbs");if(DB!=""&&$j&&!in_array(DB,$j,true))$j=null;stop_session();define("PAGE_HEADER",1);}function
page_headers(){global$b;header("Content-Type: text/html; charset=utf-8");header("Cache-Control: no-cache");if($b->headers()){header("X-Frame-Options: deny");header("X-XSS-Protection: 0");}}function
page_messages($m){$Gh=preg_replace('~^[^?]*~','',$_SERVER["REQUEST_URI"]);$ne=$_SESSION["messages"][$Gh];if($ne){echo"<div class='message'>".implode("</div>\n<div class='message'>",$ne)."</div>\n";unset($_SESSION["messages"][$Gh]);}if($m)echo"<div class='error'>$m</div>\n";}function
page_footer($re=""){global$b,$T;echo'</div>

';switch_lang();if($re!="auth"){echo'<form action="" method="post">
<p class="logout">
<input type="submit" name="logout" value="',lang(71),'" id="logout">
<input type="hidden" name="token" value="',$T,'">
</p>
</form>
';}echo'<div id="menu">
';$b->navigation($re);echo'</div>
<script type="text/javascript">setupSubmitHighlight(document);</script>
';}function
int32($ue){while($ue>=2147483648)$ue-=4294967296;while($ue<=-2147483649)$ue+=4294967296;return(int)$ue;}function
long2str($W,$Vh){$ig='';foreach($W
as$X)$ig.=pack('V',$X);if($Vh)return
substr($ig,0,end($W));return$ig;}function
str2long($ig,$Vh){$W=array_values(unpack('V*',str_pad($ig,4*ceil(strlen($ig)/4),"\0")));if($Vh)$W[]=strlen($ig);return$W;}function
xxtea_mx($bi,$ai,$Lg,$Dd){return
int32((($bi>>5&0x7FFFFFF)^$ai<<2)+(($ai>>3&0x1FFFFFFF)^$bi<<4))^int32(($Lg^$ai)+($Dd^$bi));}function
encrypt_string($Gg,$x){if($Gg=="")return"";$x=array_values(unpack("V*",pack("H*",md5($x))));$W=str2long($Gg,true);$ue=count($W)-1;$bi=$W[$ue];$ai=$W[0];$Hf=floor(6+52/($ue+1));$Lg=0;while($Hf-->0){$Lg=int32($Lg+0x9E3779B9);$bc=$Lg>>2&3;for($ef=0;$ef<$ue;$ef++){$ai=$W[$ef+1];$te=xxtea_mx($bi,$ai,$Lg,$x[$ef&3^$bc]);$bi=int32($W[$ef]+$te);$W[$ef]=$bi;}$ai=$W[0];$te=xxtea_mx($bi,$ai,$Lg,$x[$ef&3^$bc]);$bi=int32($W[$ue]+$te);$W[$ue]=$bi;}return
long2str($W,false);}function
decrypt_string($Gg,$x){if($Gg=="")return"";if(!$x)return
false;$x=array_values(unpack("V*",pack("H*",md5($x))));$W=str2long($Gg,false);$ue=count($W)-1;$bi=$W[$ue];$ai=$W[0];$Hf=floor(6+52/($ue+1));$Lg=int32($Hf*0x9E3779B9);while($Lg){$bc=$Lg>>2&3;for($ef=$ue;$ef>0;$ef--){$bi=$W[$ef-1];$te=xxtea_mx($bi,$ai,$Lg,$x[$ef&3^$bc]);$ai=int32($W[$ef]-$te);$W[$ef]=$ai;}$bi=$W[$ue];$te=xxtea_mx($bi,$ai,$Lg,$x[$ef&3^$bc]);$ai=int32($W[0]-$te);$W[0]=$ai;$Lg=int32($Lg-0x9E3779B9);}return
long2str($W,true);}$h='';$bd=$_SESSION["token"];if(!$bd)$_SESSION["token"]=rand(1,1e6);$T=get_token();$qf=array();if($_COOKIE["adminer_permanent"]){foreach(explode(" ",$_COOKIE["adminer_permanent"])as$X){list($x)=explode(":",$X);$qf[$x]=$X;}}function
add_invalid_login(){global$b;$Fc=get_temp_dir()."/adminer.invalid";$Pc=@fopen($Fc,"r+");if(!$Pc){$Pc=@fopen($Fc,"w");if(!$Pc)return;}flock($Pc,LOCK_EX);$wd=unserialize(stream_get_contents($Pc));$eh=time();if($wd){foreach($wd
as$xd=>$X){if($X[0]<$eh)unset($wd[$xd]);}}$vd=&$wd[$b->bruteForceKey()];if(!$vd)$vd=array($eh+30*60,0);$vd[1]++;$tg=serialize($wd);rewind($Pc);fwrite($Pc,$tg);ftruncate($Pc,strlen($tg));flock($Pc,LOCK_UN);fclose($Pc);}$Ja=$_POST["auth"];if($Ja){$wd=unserialize(@file_get_contents(get_temp_dir()."/adminer.invalid"));$vd=$wd[$b->bruteForceKey()];$_e=($vd[1]>30?$vd[0]-time():0);if($_e>0)auth_error(lang(72,ceil($_e/60)));session_regenerate_id();$l=$Ja["driver"];$N=$Ja["server"];$V=$Ja["username"];$G=(string)$Ja["password"];$k=$Ja["db"];set_password($l,$N,$V,$G);$_SESSION["db"][$l][$N][$V][$k]=true;if($Ja["permanent"]){$x=base64_encode($l)."-".base64_encode($N)."-".base64_encode($V)."-".base64_encode($k);$Bf=$b->permanentLogin(true);$qf[$x]="$x:".base64_encode($Bf?encrypt_string($G,$Bf):"");cookie("adminer_permanent",implode(" ",$qf));}if(count($_POST)==1||DRIVER!=$l||SERVER!=$N||$_GET["username"]!==$V||DB!=$k)redirect(auth_url($l,$N,$V,$k));}elseif($_POST["logout"]){if($bd&&!verify_token()){page_header(lang(71),lang(73));page_footer("db");exit;}else{foreach(array("pwds","db","dbs","queries")as$x)set_session($x,null);unset_permanent();redirect(substr(preg_replace('~\b(username|db|ns)=[^&]*&~','',ME),0,-1),lang(74));}}elseif($qf&&!$_SESSION["pwds"]){session_regenerate_id();$Bf=$b->permanentLogin();foreach($qf
as$x=>$X){list(,$eb)=explode(":",$X);list($Qh,$N,$V,$k)=array_map('base64_decode',explode("-",$x));set_password($Qh,$N,$V,decrypt_string(base64_decode($eb),$Bf));$_SESSION["db"][$Qh][$N][$V][$k]=true;}}function
unset_permanent(){global$qf;foreach($qf
as$x=>$X){list($Qh,$N,$V,$k)=array_map('base64_decode',explode("-",$x));if($Qh==DRIVER&&$N==SERVER&&$V==$_GET["username"]&&$k==DB)unset($qf[$x]);}cookie("adminer_permanent",implode(" ",$qf));}function
auth_error($m){global$b,$bd;$wg=session_name();if(!$_COOKIE[$wg]&&$_GET[$wg]&&ini_bool("session.use_only_cookies"))$m=lang(75);elseif(isset($_GET["username"])){if(($_COOKIE[$wg]||$_GET[$wg])&&!$bd)$m=lang(76);else{add_invalid_login();$G=get_password();if($G!==null){if($G===false)$m.='<br>'.lang(77,'<code>permanentLogin()</code>');set_password(DRIVER,SERVER,$_GET["username"],null);}unset_permanent();}}$F=session_get_cookie_params();cookie("adminer_key",($_COOKIE["adminer_key"]?$_COOKIE["adminer_key"]:rand_string()),$F["lifetime"]);page_header(lang(36),$m,null);echo"<form action='' method='post'>\n";$b->loginForm();echo"<div>";hidden_fields($_POST,array("auth"));echo"</div>\n","</form>\n";page_footer("auth");exit;}if(isset($_GET["username"])){if(!class_exists("Min_DB")){unset($_SESSION["pwds"][DRIVER]);unset_permanent();page_header(lang(78),lang(79,implode(", ",$wf)),false);page_footer("auth");exit;}$h=connect();}$l=new
Min_Driver($h);if(!is_object($h)||!$b->login($_GET["username"],get_password()))auth_error((is_string($h)?$h:lang(80)));if($Ja&&$_POST["token"])$_POST["token"]=$T;$m='';if($_POST){if(!verify_token()){$qd="max_input_vars";$he=ini_get($qd);if(extension_loaded("suhosin")){foreach(array("suhosin.request.max_vars","suhosin.post.max_vars")as$x){$X=ini_get($x);if($X&&(!$he||$X<$he)){$qd=$x;$he=$X;}}}$m=(!$_POST["token"]&&$he?lang(81,"'$qd'"):lang(73));}}elseif($_SERVER["REQUEST_METHOD"]=="POST"){$m=lang(82,"'post_max_size'");if(isset($_GET["sql"]))$m.=' '.lang(83);}if(!ini_bool("session.use_cookies")||@ini_set("session.use_cookies",false)!==false)session_write_close();function
select($I,$i=null,$We=array()){global$w;$Wd=array();$v=array();$f=array();$Sa=array();$yh=array();$J=array();odd('');for($r=0;$K=$I->fetch_row();$r++){if(!$r){echo"<table cellspacing='0' class='nowrap'>\n","<thead><tr>";for($Cd=0;$Cd<count($K);$Cd++){$n=$I->fetch_field();$C=$n->name;$Ve=$n->orgtable;$Ue=$n->orgname;$J[$n->table]=$Ve;if($We&&$w=="sql")$Wd[$Cd]=($C=="table"?"table=":($C=="possible_keys"?"indexes=":null));elseif($Ve!=""){if(!isset($v[$Ve])){$v[$Ve]=array();foreach(indexes($Ve,$i)as$u){if($u["type"]=="PRIMARY"){$v[$Ve]=array_flip($u["columns"]);break;}}$f[$Ve]=$v[$Ve];}if(isset($f[$Ve][$Ue])){unset($f[$Ve][$Ue]);$v[$Ve][$Ue]=$Cd;$Wd[$Cd]=$Ve;}}if($n->charsetnr==63)$Sa[$Cd]=true;$yh[$Cd]=$n->type;echo"<th".($Ve!=""||$n->name!=$Ue?" title='".h(($Ve!=""?"$Ve.":"").$Ue)."'":"").">".h($C).($We?doc_link(array('sql'=>"explain-output.html#explain_".strtolower($C))):"");}echo"</thead>\n";}echo"<tr".odd().">";foreach($K
as$x=>$X){if($X===null)$X="<i>NULL</i>";elseif($Sa[$x]&&!is_utf8($X))$X="<i>".lang(43,strlen($X))."</i>";elseif(!strlen($X))$X="&nbsp;";else{$X=h($X);if($yh[$x]==254)$X="<code>$X</code>";}if(isset($Wd[$x])&&!$f[$Wd[$x]]){if($We&&$w=="sql"){$Q=$K[array_search("table=",$Wd)];$_=$Wd[$x].urlencode($We[$Q]!=""?$We[$Q]:$Q);}else{$_="edit=".urlencode($Wd[$x]);foreach($v[$Wd[$x]]as$ib=>$Cd)$_.="&where".urlencode("[".bracket_escape($ib)."]")."=".urlencode($K[$Cd]);}$X="<a href='".h(ME.$_)."'>$X</a>";}echo"<td>$X";}}echo($r?"</table>":"<p class='message'>".lang(12))."\n";return$J;}function
referencable_primary($qg){$J=array();foreach(table_status('',true)as$Pg=>$Q){if($Pg!=$qg&&fk_support($Q)){foreach(fields($Pg)as$n){if($n["primary"]){if($J[$Pg]){unset($J[$Pg]);break;}$J[$Pg]=$n;}}}}return$J;}function
textarea($C,$Y,$L=10,$lb=80){global$w;echo"<textarea name='$C' rows='$L' cols='$lb' class='sqlarea jush-$w' spellcheck='false' wrap='off'>";if(is_array($Y)){foreach($Y
as$X)echo
h($X[0])."\n\n\n";}else
echo
h($Y);echo"</textarea>";}function
edit_type($x,$n,$kb,$Lc=array()){global$Hg,$yh,$Eh,$Ke;$U=$n["type"];echo'<td><select name="',$x,'[type]" class="type" onfocus="lastType = selectValue(this);" onchange="editingTypeChange(this);"',on_help("getTarget(event).value",1),'>';if($U&&!isset($yh[$U])&&!isset($Lc[$U]))array_unshift($Hg,$U);if($Lc)$Hg[lang(84)]=$Lc;echo
optionlist($Hg,$U),'</select>
<td><input name="',$x,'[length]" value="',h($n["length"]),'" size="3" onfocus="editingLengthFocus(this);"',(!$n["length"]&&preg_match('~var(char|binary)$~',$U)?" class='required'":""),' onchange="editingLengthChange(this);" onkeyup="this.onchange();"><td class="options">';echo"<select name='$x"."[collation]'".(preg_match('~(char|text|enum|set)$~',$U)?"":" class='hidden'").'><option value="">('.lang(85).')'.optionlist($kb,$n["collation"]).'</select>',($Eh?"<select name='$x"."[unsigned]'".(!$U||preg_match('~((^|[^o])int|float|double|decimal)$~',$U)?"":" class='hidden'").'><option>'.optionlist($Eh,$n["unsigned"]).'</select>':''),(isset($n['on_update'])?"<select name='$x"."[on_update]'".(preg_match('~timestamp|datetime~',$U)?"":" class='hidden'").'>'.optionlist(array(""=>"(".lang(86).")","CURRENT_TIMESTAMP"),$n["on_update"]).'</select>':''),($Lc?"<select name='$x"."[on_delete]'".(preg_match("~`~",$U)?"":" class='hidden'")."><option value=''>(".lang(87).")".optionlist(explode("|",$Ke),$n["on_delete"])."</select> ":" ");}function
process_length($y){global$mc;return(preg_match("~^\\s*\\(?\\s*$mc(?:\\s*,\\s*$mc)*+\\s*\\)?\\s*\$~",$y)&&preg_match_all("~$mc~",$y,$be)?"(".implode(",",$be[0]).")":preg_replace('~^[0-9].*~','(\0)',preg_replace('~[^-0-9,+()[\]]~','',$y)));}function
process_type($n,$jb="COLLATE"){global$Eh;return" $n[type]".process_length($n["length"]).(preg_match('~(^|[^o])int|float|double|decimal~',$n["type"])&&in_array($n["unsigned"],$Eh)?" $n[unsigned]":"").(preg_match('~char|text|enum|set~',$n["type"])&&$n["collation"]?" $jb ".q($n["collation"]):"");}function
process_field($n,$wh){global$w;$Kb=$n["default"];return
array(idf_escape(trim($n["field"])),process_type($wh),($n["null"]?" NULL":" NOT NULL"),(isset($Kb)?" DEFAULT ".((preg_match('~time~',$n["type"])&&preg_match('~^CURRENT_TIMESTAMP$~i',$Kb))||($n["type"]=="bit"&&preg_match("~^([0-9]+|b'[0-1]+')\$~",$Kb))||($w=="pgsql"&&preg_match("~^[a-z]+\\(('[^']*')+\\)\$~",$Kb))?$Kb:q($Kb)):""),(preg_match('~timestamp|datetime~',$n["type"])&&$n["on_update"]?" ON UPDATE $n[on_update]":""),(support("comment")&&$n["comment"]!=""?" COMMENT ".q($n["comment"]):""),($n["auto_increment"]?auto_increment():null),);}function
type_class($U){foreach(array('char'=>'text','date'=>'time|year','binary'=>'blob','enum'=>'set',)as$x=>$X){if(preg_match("~$x|$X~",$U))return" class='$x'";}}function
edit_fields($o,$kb,$U="TABLE",$Lc=array(),$pb=false){global$h,$rd;echo'<thead><tr class="wrap">
';if($U=="PROCEDURE"){echo'<td>&nbsp;';}echo'<th>',($U=="TABLE"?lang(88):lang(89)),'<td>',lang(90),'<textarea id="enum-edit" rows="4" cols="12" wrap="off" style="display: none;" onblur="editingLengthBlur(this);"></textarea>
<td>',lang(91),'<td>',lang(92);if($U=="TABLE"){echo'<td>NULL
<td><input type="radio" name="auto_increment_col" value=""><acronym title="',lang(56),'">AI</acronym>',doc_link(array('sql'=>"example-auto-increment.html",'sqlite'=>"autoinc.html",'pgsql'=>"datatype.html#DATATYPE-SERIAL",'mssql'=>"ms186775.aspx",)),'<td>',lang(93),(support("comment")?"<td".($pb?"":" class='hidden'").">".lang(94):"");}echo'<td>',"<input type='image' class='icon' name='add[".(support("move_col")?0:count($o))."]' src='".h(preg_replace("~\\?.*~","",ME))."?file=plus.gif&amp;version=4.1.0' alt='+' title='".lang(95)."'>",'<script type="text/javascript">row_count = ',count($o),';</script>
</thead>
<tbody onkeydown="return editingKeydown(event);">
';foreach($o
as$r=>$n){$r++;$Xe=$n[($_POST?"orig":"field")];$Rb=(isset($_POST["add"][$r-1])||(isset($n["field"])&&!$_POST["drop_col"][$r]))&&(support("drop_col")||$Xe=="");echo'<tr',($Rb?"":" style='display: none;'"),'>
',($U=="PROCEDURE"?"<td>".adminer_html_select("fields[$r][inout]",explode("|",$rd),$n["inout"]):""),'<th>';if($Rb){echo'<input name="fields[',$r,'][field]" value="',h($n["field"]),'" onchange="editingNameChange(this);',($n["field"]!=""||count($o)>1?'':' editingAddRow(this);" onkeyup="if (this.value) editingAddRow(this);'),'" maxlength="64" autocapitalize="off">';}echo'<input type="hidden" name="fields[',$r,'][orig]" value="',h($Xe),'">
';edit_type("fields[$r]",$n,$kb,$Lc);if($U=="TABLE"){echo'<td>',checkbox("fields[$r][null]",1,$n["null"],"","","block"),'<td><label class="block"><input type="radio" name="auto_increment_col" value="',$r,'"';if($n["auto_increment"]){echo' checked';}?> onclick="var field = this.form['fields[' + this.value + '][field]']; if (!field.value) { field.value = 'id'; field.onchange(); }"></label><td><?php
echo
checkbox("fields[$r][has_default]",1,$n["has_default"]),'<input name="fields[',$r,'][default]" value="',h($n["default"]),'" onkeyup="keyupChange.call(this);" onchange="this.previousSibling.checked = true;">
',(support("comment")?"<td".($pb?"":" class='hidden'")."><input name='fields[$r][comment]' value='".h($n["comment"])."' maxlength='".($h->server_info>=5.5?1024:255)."'>":"");}echo"<td>",(support("move_col")?"<input type='image' class='icon' name='add[$r]' src='".h(preg_replace("~\\?.*~","",ME))."?file=plus.gif&amp;version=4.1.0' alt='+' title='".lang(95)."' onclick='return !editingAddRow(this, 1);'>&nbsp;"."<input type='image' class='icon' name='up[$r]' src='".h(preg_replace("~\\?.*~","",ME))."?file=up.gif&amp;version=4.1.0' alt='^' title='".lang(96)."'>&nbsp;"."<input type='image' class='icon' name='down[$r]' src='".h(preg_replace("~\\?.*~","",ME))."?file=down.gif&amp;version=4.1.0' alt='v' title='".lang(97)."'>&nbsp;":""),($Xe==""||support("drop_col")?"<input type='image' class='icon' name='drop_col[$r]' src='".h(preg_replace("~\\?.*~","",ME))."?file=cross.gif&amp;version=4.1.0' alt='x' title='".lang(98)."' onclick=\"return !editingRemoveRow(this, 'fields\$1[field]');\">":""),"\n";}}function
process_fields(&$o){ksort($o);$D=0;if($_POST["up"]){$Nd=0;foreach($o
as$x=>$n){if(key($_POST["up"])==$x){unset($o[$x]);array_splice($o,$Nd,0,array($n));break;}if(isset($n["field"]))$Nd=$D;$D++;}}elseif($_POST["down"]){$Nc=false;foreach($o
as$x=>$n){if(isset($n["field"])&&$Nc){unset($o[key($_POST["down"])]);array_splice($o,$D,0,array($Nc));break;}if(key($_POST["down"])==$x)$Nc=$n;$D++;}}elseif($_POST["add"]){$o=array_values($o);array_splice($o,key($_POST["add"]),0,array(array()));}elseif(!$_POST["drop_col"])return
false;return
true;}function
normalize_enum($B){return"'".str_replace("'","''",addcslashes(stripcslashes(str_replace($B[0][0].$B[0][0],$B[0][0],substr($B[0],1,-1))),'\\'))."'";}function
grant($Tc,$Df,$f,$Je){if(!$Df)return
true;if($Df==array("ALL PRIVILEGES","GRANT OPTION"))return($Tc=="GRANT"?queries("$Tc ALL PRIVILEGES$Je WITH GRANT OPTION"):queries("$Tc ALL PRIVILEGES$Je")&&queries("$Tc GRANT OPTION$Je"));return
queries("$Tc ".preg_replace('~(GRANT OPTION)\\([^)]*\\)~','\\1',implode("$f, ",$Df).$f).$Je);}function
drop_create($Vb,$zb,$Wb,$bh,$Yb,$A,$me,$ke,$le,$Ge,$xe){if($_POST["drop"])query_redirect($Vb,$A,$me);elseif($Ge=="")query_redirect($zb,$A,$le);elseif($Ge!=$xe){$Ab=queries($zb);queries_redirect($A,$ke,$Ab&&queries($Vb));if($Ab)queries($Wb);}else
queries_redirect($A,$ke,queries($bh)&&queries($Yb)&&queries($Vb)&&queries($zb));}function
create_trigger($Je,$K){global$w;$gh=" $K[Timing] $K[Event]".($K["Event"]=="UPDATE OF"?" ".idf_escape($K["Of"]):"");return"CREATE TRIGGER ".idf_escape($K["Trigger"]).($w=="mssql"?$Je.$gh:$gh.$Je).rtrim(" $K[Type]\n$K[Statement]",";").";";}function
create_routine($eg,$K){global$rd;$O=array();$o=(array)$K["fields"];ksort($o);foreach($o
as$n){if($n["field"]!="")$O[]=(preg_match("~^($rd)\$~",$n["inout"])?"$n[inout] ":"").idf_escape($n["field"]).process_type($n,"CHARACTER SET");}return"CREATE $eg ".idf_escape(trim($K["name"]))." (".implode(", ",$O).")".(isset($_GET["function"])?" RETURNS".process_type($K["returns"],"CHARACTER SET"):"").($K["language"]?" LANGUAGE $K[language]":"").rtrim("\n$K[definition]",";").";";}function
remove_definer($H){return
preg_replace('~^([A-Z =]+) DEFINER=`'.preg_replace('~@(.*)~','`@`(%|\\1)',logged_user()).'`~','\\1',$H);}function
format_foreign_key($p){global$Ke;return" FOREIGN KEY (".implode(", ",array_map('idf_escape',$p["source"])).") REFERENCES ".table($p["table"])." (".implode(", ",array_map('idf_escape',$p["target"])).")".(preg_match("~^($Ke)\$~",$p["on_delete"])?" ON DELETE $p[on_delete]":"").(preg_match("~^($Ke)\$~",$p["on_update"])?" ON UPDATE $p[on_update]":"");}function
tar_file($Fc,$lh){$J=pack("a100a8a8a8a12a12",$Fc,644,0,0,decoct($lh->size),decoct(time()));$cb=8*32;for($r=0;$r<strlen($J);$r++)$cb+=ord($J[$r]);$J.=sprintf("%06o",$cb)."\0 ";echo$J,str_repeat("\0",512-strlen($J));$lh->send();echo
str_repeat("\0",511-($lh->size+511)%512);}function
ini_bytes($qd){$X=ini_get($qd);switch(strtolower(substr($X,-1))){case'g':$X*=1024;case'm':$X*=1024;case'k':$X*=1024;}return$X;}function
doc_link($of){global$w,$h;$Hh=array('sql'=>"http://dev.mysql.com/doc/refman/".substr($h->server_info,0,3)."/en/",'sqlite'=>"http://www.sqlite.org/",'pgsql'=>"http://www.postgresql.org/docs/".substr($h->server_info,0,3)."/static/",'mssql'=>"http://msdn.microsoft.com/library/",'oracle'=>"http://download.oracle.com/docs/cd/B19306_01/server.102/b14200/",);return($of[$w]?"<a href='$Hh[$w]$of[$w]' target='_blank' rel='noreferrer'><sup>?</sup></a>":"");}function
ob_gzencode($P){return
gzencode($P);}function
db_size($k){global$h;if(!$h->select_db($k))return"?";$J=0;foreach(table_status()as$R)$J+=$R["Data_length"]+$R["Index_length"];return
format_number($J);}function
connect_error(){global$b,$h,$T,$m,$Ub;if(DB!=""){header("HTTP/1.1 404 Not Found");page_header(lang(35).": ".h(DB),lang(99),true);}else{if($_POST["db"]&&!$m)queries_redirect(substr(ME,0,-1),lang(100),drop_databases($_POST["db"]));page_header(lang(101),$m,false);echo"<p class='links'>\n";foreach(array('database'=>lang(102),'privileges'=>lang(63),'processlist'=>lang(103),'variables'=>lang(104),'status'=>lang(105),)as$x=>$X){if(support($x))echo"<a href='".h(ME)."$x='>$X</a>\n";}echo"<p>".lang(106,$Ub[DRIVER],"<b>".h($h->server_info)."</b>","<b>$h->extension</b>")."\n","<p>".lang(107,"<b>".h(logged_user())."</b>")."\n";$j=$b->databases();if($j){$lg=support("scheme");$kb=collations();echo"<form action='' method='post'>\n","<table cellspacing='0' class='checkable' onclick='tableClick(event);' ondblclick='tableClick(event, true);'>\n","<thead><tr>".(support("database")?"<td>&nbsp;":"")."<th>".lang(35)." - <a href='".h(ME)."refresh=1'>".lang(108)."</a>"."<td>".lang(109)."<td>".lang(110)."<td>".lang(111)." - <a href='".h(ME)."dbsize=1' onclick=\"return !ajaxSetHtml('".js_escape(ME)."script=connect');\">".lang(112)."</a>"."</thead>\n";$j=($_GET["dbsize"]?count_tables($j):array_flip($j));foreach($j
as$k=>$S){$dg=h(ME)."db=".urlencode($k);echo"<tr".odd().">".(support("database")?"<td>".checkbox("db[]",$k,in_array($k,(array)$_POST["db"])):""),"<th><a href='$dg'>".h($k)."</a>";$d=nbsp(db_collation($k,$kb));echo"<td>".(support("database")?"<a href='$dg".($lg?"&amp;ns=":"")."&amp;database=' title='".lang(59)."'>$d</a>":$d),"<td align='right'><a href='$dg&amp;schema=' id='tables-".h($k)."' title='".lang(62)."'>".($_GET["dbsize"]?$S:"?")."</a>","<td align='right' id='size-".h($k)."'>".($_GET["dbsize"]?db_size($k):"?"),"\n";}echo"</table>\n",(support("database")?"<fieldset><legend>".lang(113)." <span id='selected'></span></legend><div>\n"."<input type='hidden' name='all' value='' onclick=\"selectCount('selected', formChecked(this, /^db/));\">\n"."<input type='submit' name='drop' value='".lang(114)."'".confirm().">\n"."</div></fieldset>\n":""),"<script type='text/javascript'>tableCheck();</script>\n","<input type='hidden' name='token' value='$T'>\n","</form>\n";}}page_footer("db");}if(isset($_GET["status"]))$_GET["variables"]=$_GET["status"];if(isset($_GET["import"]))$_GET["sql"]=$_GET["import"];if(!(DB!=""?$h->select_db(DB):isset($_GET["sql"])||isset($_GET["dump"])||isset($_GET["database"])||isset($_GET["processlist"])||isset($_GET["privileges"])||isset($_GET["user"])||isset($_GET["variables"])||$_GET["script"]=="connect"||$_GET["script"]=="kill")){if(DB!=""||$_GET["refresh"]){restart_session();set_session("dbs",null);}connect_error();exit;}if(support("scheme")&&DB!=""&&$_GET["ns"]!==""){if(!isset($_GET["ns"]))redirect(preg_replace('~ns=[^&]*&~','',ME)."ns=".get_schema());if(!set_schema($_GET["ns"])){header("HTTP/1.1 404 Not Found");page_header(lang(68).": ".h($_GET["ns"]),lang(115),true);page_footer("ns");exit;}}$Ke="RESTRICT|NO ACTION|CASCADE|SET NULL|SET DEFAULT";class
TmpFile{var$handler;var$size;function
TmpFile(){$this->handler=tmpfile();}function
write($ub){$this->size+=strlen($ub);fwrite($this->handler,$ub);}function
send(){fseek($this->handler,0);fpassthru($this->handler);fclose($this->handler);}}$mc="'(?:''|[^'\\\\]|\\\\.)*'";$rd="IN|OUT|INOUT";if(isset($_GET["select"])&&($_POST["edit"]||$_POST["clone"])&&!$_POST["save"])$_GET["edit"]=$_GET["select"];if(isset($_GET["callf"]))$_GET["call"]=$_GET["callf"];if(isset($_GET["function"]))$_GET["procedure"]=$_GET["function"];if(isset($_GET["download"])){$a=$_GET["download"];$o=fields($a);header("Content-Type: application/octet-stream");header("Content-Disposition: attachment; filename=".friendly_url("$a-".implode("_",$_GET["where"])).".".friendly_url($_GET["field"]));$M=array(idf_escape($_GET["field"]));$I=$l->select($a,$M,array(where($_GET,$o)),$M);$K=($I?$I->fetch_row():array());echo$K[0];exit;}elseif(isset($_GET["table"])){$a=$_GET["table"];$o=fields($a);if(!$o)$m=error();$R=table_status1($a,true);page_header(($o&&is_view($R)?lang(116):lang(117)).": ".h($a),$m);$b->selectLinks($R);$ob=$R["Comment"];if($ob!="")echo"<p>".lang(94).": ".h($ob)."\n";if($o){echo"<table cellspacing='0'>\n","<thead><tr><th>".lang(118)."<td>".lang(90).(support("comment")?"<td>".lang(94):"")."</thead>\n";foreach($o
as$n){echo"<tr".odd()."><th>".h($n["field"]),"<td title='".h($n["collation"])."'>".h($n["full_type"]).($n["null"]?" <i>NULL</i>":"").($n["auto_increment"]?" <i>".lang(56)."</i>":""),(isset($n["default"])?" [<b>".h($n["default"])."</b>]":""),(support("comment")?"<td>".nbsp($n["comment"]):""),"\n";}echo"</table>\n";}if(!is_view($R)){if(support("indexes")){echo"<h3 id='indexes'>".lang(119)."</h3>\n";$v=indexes($a);if($v){echo"<table cellspacing='0'>\n";foreach($v
as$C=>$u){ksort($u["columns"]);$Af=array();foreach($u["columns"]as$x=>$X)$Af[]="<i>".h($X)."</i>".($u["lengths"][$x]?"(".$u["lengths"][$x].")":"").($u["descs"][$x]?" DESC":"");echo"<tr title='".h($C)."'><th>$u[type]<td>".implode(", ",$Af)."\n";}echo"</table>\n";}echo'<p class="links"><a href="'.h(ME).'indexes='.urlencode($a).'">'.lang(120)."</a>\n";}if(fk_support($R)){echo"<h3 id='foreign-keys'>".lang(84)."</h3>\n";$Lc=foreign_keys($a);if($Lc){echo"<table cellspacing='0'>\n","<thead><tr><th>".lang(121)."<td>".lang(122)."<td>".lang(87)."<td>".lang(86)."<td>&nbsp;</thead>\n";foreach($Lc
as$C=>$p){echo"<tr title='".h($C)."'>","<th><i>".implode("</i>, <i>",array_map('h',$p["source"]))."</i>","<td><a href='".h($p["db"]!=""?preg_replace('~db=[^&]*~',"db=".urlencode($p["db"]),ME):($p["ns"]!=""?preg_replace('~ns=[^&]*~',"ns=".urlencode($p["ns"]),ME):ME))."table=".urlencode($p["table"])."'>".($p["db"]!=""?"<b>".h($p["db"])."</b>.":"").($p["ns"]!=""?"<b>".h($p["ns"])."</b>.":"").h($p["table"])."</a>","(<i>".implode("</i>, <i>",array_map('h',$p["target"]))."</i>)","<td>".nbsp($p["on_delete"])."\n","<td>".nbsp($p["on_update"])."\n",'<td><a href="'.h(ME.'foreign='.urlencode($a).'&name='.urlencode($C)).'">'.lang(123).'</a>';}echo"</table>\n";}echo'<p class="links"><a href="'.h(ME).'foreign='.urlencode($a).'">'.lang(124)."</a>\n";}}if(support(is_view($R)?"view_trigger":"trigger")){echo"<h3 id='triggers'>".lang(125)."</h3>\n";$vh=triggers($a);if($vh){echo"<table cellspacing='0'>\n";foreach($vh
as$x=>$X)echo"<tr valign='top'><td>".h($X[0])."<td>".h($X[1])."<th>".h($x)."<td><a href='".h(ME.'trigger='.urlencode($a).'&name='.urlencode($x))."'>".lang(123)."</a>\n";echo"</table>\n";}echo'<p class="links"><a href="'.h(ME).'trigger='.urlencode($a).'">'.lang(126)."</a>\n";}}elseif(isset($_GET["schema"])){page_header(lang(62),"",array(),h(DB.($_GET["ns"]?".$_GET[ns]":"")));$Rg=array();$Sg=array();$C="adminer_schema";$ea=($_GET["schema"]?$_GET["schema"]:$_COOKIE[($_COOKIE["$C-".DB]?"$C-".DB:$C)]);preg_match_all('~([^:]+):([-0-9.]+)x([-0-9.]+)(_|$)~',$ea,$be,PREG_SET_ORDER);foreach($be
as$r=>$B){$Rg[$B[1]]=array($B[2],$B[3]);$Sg[]="\n\t'".js_escape($B[1])."': [ $B[2], $B[3] ]";}$nh=0;$Pa=-1;$kg=array();$Sf=array();$Rd=array();foreach(table_status('',true)as$Q=>$R){if(is_view($R))continue;$tf=0;$kg[$Q]["fields"]=array();foreach(fields($Q)as$C=>$n){$tf+=1.25;$n["pos"]=$tf;$kg[$Q]["fields"][$C]=$n;}$kg[$Q]["pos"]=($Rg[$Q]?$Rg[$Q]:array($nh,0));foreach($b->foreignKeys($Q)as$X){if(!$X["db"]){$Pd=$Pa;if($Rg[$Q][1]||$Rg[$X["table"]][1])$Pd=min(floatval($Rg[$Q][1]),floatval($Rg[$X["table"]][1]))-1;else$Pa-=.1;while($Rd[(string)$Pd])$Pd-=.0001;$kg[$Q]["references"][$X["table"]][(string)$Pd]=array($X["source"],$X["target"]);$Sf[$X["table"]][$Q][(string)$Pd]=$X["target"];$Rd[(string)$Pd]=true;}}$nh=max($nh,$kg[$Q]["pos"][0]+2.5+$tf);}echo'<div id="schema" style="height: ',$nh,'em;" onselectstart="return false;">
<script type="text/javascript">
var tablePos = {',implode(",",$Sg)."\n",'};
var em = document.getElementById(\'schema\').offsetHeight / ',$nh,';
document.onmousemove = schemaMousemove;
document.onmouseup = function (ev) {
	schemaMouseup(ev, \'',js_escape(DB),'\');
};
</script>
';foreach($kg
as$C=>$Q){echo"<div class='table' style='top: ".$Q["pos"][0]."em; left: ".$Q["pos"][1]."em;' onmousedown='schemaMousedown(this, event);'>",'<a href="'.h(ME).'table='.urlencode($C).'"><b>'.h($C)."</b></a>";foreach($Q["fields"]as$n){$X='<span'.type_class($n["type"]).' title="'.h($n["full_type"].($n["null"]?" NULL":'')).'">'.h($n["field"]).'</span>';echo"<br>".($n["primary"]?"<i>$X</i>":$X);}foreach((array)$Q["references"]as$Yg=>$Tf){foreach($Tf
as$Pd=>$Pf){$Qd=$Pd-$Rg[$C][1];$r=0;foreach($Pf[0]as$_g)echo"\n<div class='references' title='".h($Yg)."' id='refs$Pd-".($r++)."' style='left: $Qd"."em; top: ".$Q["fields"][$_g]["pos"]."em; padding-top: .5em;'><div style='border-top: 1px solid Gray; width: ".(-$Qd)."em;'></div></div>";}}foreach((array)$Sf[$C]as$Yg=>$Tf){foreach($Tf
as$Pd=>$f){$Qd=$Pd-$Rg[$C][1];$r=0;foreach($f
as$Xg)echo"\n<div class='references' title='".h($Yg)."' id='refd$Pd-".($r++)."' style='left: $Qd"."em; top: ".$Q["fields"][$Xg]["pos"]."em; height: 1.25em; background: url(".h(preg_replace("~\\?.*~","",ME))."?file=arrow.gif) no-repeat right center;&amp;version=4.1.0'><div style='height: .5em; border-bottom: 1px solid Gray; width: ".(-$Qd)."em;'></div></div>";}}echo"\n</div>\n";}foreach($kg
as$C=>$Q){foreach((array)$Q["references"]as$Yg=>$Tf){foreach($Tf
as$Pd=>$Pf){$qe=$nh;$fe=-10;foreach($Pf[0]as$x=>$_g){$uf=$Q["pos"][0]+$Q["fields"][$_g]["pos"];$vf=$kg[$Yg]["pos"][0]+$kg[$Yg]["fields"][$Pf[1][$x]]["pos"];$qe=min($qe,$uf,$vf);$fe=max($fe,$uf,$vf);}echo"<div class='references' id='refl$Pd' style='left: $Pd"."em; top: $qe"."em; padding: .5em 0;'><div style='border-right: 1px solid Gray; margin-top: 1px; height: ".($fe-$qe)."em;'></div></div>\n";}}}echo'</div>
<p class="links"><a href="',h(ME."schema=".urlencode($ea)),'" id="schema-link">',lang(127),'</a>
';}elseif(isset($_GET["dump"])){$a=$_GET["dump"];if($_POST&&!$m){$xb="";foreach(array("output","format","db_style","routines","events","table_style","auto_increment","triggers","data_style")as$x)$xb.="&$x=".urlencode($_POST[$x]);cookie("adminer_export",substr($xb,1));$S=array_flip((array)$_POST["tables"])+array_flip((array)$_POST["data"]);$yc=dump_headers((count($S)==1?key($S):DB),(DB==""||count($S)>1));$zd=preg_match('~sql~',$_POST["format"]);if($zd){echo"-- Adminer $ia ".$Ub[DRIVER]." dump\n\n";if($w=="sql"){echo"SET NAMES utf8;
SET time_zone = '+00:00';
".($_POST["data_style"]?"SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';
":"")."
";$h->query("SET time_zone = '+00:00';");}}$Ig=$_POST["db_style"];$j=array(DB);if(DB==""){$j=$_POST["databases"];if(is_string($j))$j=explode("\n",rtrim(str_replace("\r","",$j),"\n"));}foreach((array)$j
as$k){$b->dumpDatabase($k);if($h->select_db($k)){if($zd&&preg_match('~CREATE~',$Ig)&&($zb=$h->result("SHOW CREATE DATABASE ".idf_escape($k),1))){if($Ig=="DROP+CREATE")echo"DROP DATABASE IF EXISTS ".idf_escape($k).";\n";echo"$zb;\n";}if($zd){if($Ig)echo
use_sql($k).";\n\n";$cf="";if($_POST["routines"]){foreach(array("FUNCTION","PROCEDURE")as$eg){foreach(get_rows("SHOW $eg STATUS WHERE Db = ".q($k),null,"-- ")as$K)$cf.=($Ig!='DROP+CREATE'?"DROP $eg IF EXISTS ".idf_escape($K["Name"]).";;\n":"").remove_definer($h->result("SHOW CREATE $eg ".idf_escape($K["Name"]),2)).";;\n\n";}}if($_POST["events"]){foreach(get_rows("SHOW EVENTS",null,"-- ")as$K)$cf.=($Ig!='DROP+CREATE'?"DROP EVENT IF EXISTS ".idf_escape($K["Name"]).";;\n":"").remove_definer($h->result("SHOW CREATE EVENT ".idf_escape($K["Name"]),3)).";;\n\n";}if($cf)echo"DELIMITER ;;\n\n$cf"."DELIMITER ;\n\n";}if($_POST["table_style"]||$_POST["data_style"]){$Th=array();foreach(table_status('',true)as$C=>$R){$Q=(DB==""||in_array($C,(array)$_POST["tables"]));$Db=(DB==""||in_array($C,(array)$_POST["data"]));if($Q||$Db){if($yc=="tar"){$lh=new
TmpFile;ob_start(array($lh,'write'),1e5);}$b->dumpTable($C,($Q?$_POST["table_style"]:""),(is_view($R)?2:0));if(is_view($R))$Th[]=$C;elseif($Db){$o=fields($C);$b->dumpData($C,$_POST["data_style"],"SELECT *".convert_fields($o,$o)." FROM ".table($C));}if($zd&&$_POST["triggers"]&&$Q&&($vh=trigger_sql($C,$_POST["table_style"])))echo"\nDELIMITER ;;\n$vh\nDELIMITER ;\n";if($yc=="tar"){ob_end_flush();tar_file((DB!=""?"":"$k/")."$C.csv",$lh);}elseif($zd)echo"\n";}}foreach($Th
as$Sh)$b->dumpTable($Sh,$_POST["table_style"],1);if($yc=="tar")echo
pack("x512");}}}if($zd)echo"-- ".$h->result("SELECT NOW()")."\n";exit;}page_header(lang(128),$m,($_GET["export"]!=""?array("table"=>$_GET["export"]):array()),h(DB));echo'
<form action="" method="post">
<table cellspacing="0">
';$Hb=array('','USE','DROP+CREATE','CREATE');$Tg=array('','DROP+CREATE','CREATE');$Eb=array('','TRUNCATE+INSERT','INSERT');if($w=="sql")$Eb[]='INSERT+UPDATE';parse_str($_COOKIE["adminer_export"],$K);if(!$K)$K=array("output"=>"text","format"=>"sql","db_style"=>(DB!=""?"":"CREATE"),"table_style"=>"DROP+CREATE","data_style"=>"INSERT");if(!isset($K["events"])){$K["routines"]=$K["events"]=($_GET["dump"]=="");$K["triggers"]=$K["table_style"];}echo"<tr><th>".lang(129)."<td>".adminer_html_select("output",$b->dumpOutput(),$K["output"],0)."\n";echo"<tr><th>".lang(130)."<td>".adminer_html_select("format",$b->dumpFormat(),$K["format"],0)."\n";echo($w=="sqlite"?"":"<tr><th>".lang(35)."<td>".adminer_html_select('db_style',$Hb,$K["db_style"]).(support("routine")?checkbox("routines",1,$K["routines"],lang(131)):"").(support("event")?checkbox("events",1,$K["events"],lang(132)):"")),"<tr><th>".lang(110)."<td>".adminer_html_select('table_style',$Tg,$K["table_style"]).checkbox("auto_increment",1,$K["auto_increment"],lang(56)).(support("trigger")?checkbox("triggers",1,$K["triggers"],lang(125)):""),"<tr><th>".lang(133)."<td>".adminer_html_select('data_style',$Eb,$K["data_style"]),'</table>
<p><input type="submit" value="',lang(128),'">
<input type="hidden" name="token" value="',$T,'">

<table cellspacing="0">
';$yf=array();if(DB!=""){$ab=($a!=""?"":" checked");echo"<thead><tr>","<th style='text-align: left;'><label class='block'><input type='checkbox' id='check-tables'$ab onclick='formCheck(this, /^tables\\[/);'>".lang(110)."</label>","<th style='text-align: right;'><label class='block'>".lang(133)."<input type='checkbox' id='check-data'$ab onclick='formCheck(this, /^data\\[/);'></label>","</thead>\n";$Th="";$Ug=tables_list();foreach($Ug
as$C=>$U){$xf=preg_replace('~_.*~','',$C);$ab=($a==""||$a==(substr($a,-1)=="%"?"$xf%":$C));$Af="<tr><td>".checkbox("tables[]",$C,$ab,$C,"checkboxClick(event, this); formUncheck('check-tables');","block");if($U!==null&&!preg_match('~table~i',$U))$Th.="$Af\n";else
echo"$Af<td align='right'><label class='block'><span id='Rows-".h($C)."'></span>".checkbox("data[]",$C,$ab,"","checkboxClick(event, this); formUncheck('check-data');")."</label>\n";$yf[$xf]++;}echo$Th;if($Ug)echo"<script type='text/javascript'>ajaxSetHtml('".js_escape(ME)."script=db');</script>\n";}else{echo"<thead><tr><th style='text-align: left;'><label class='block'><input type='checkbox' id='check-databases'".($a==""?" checked":"")." onclick='formCheck(this, /^databases\\[/);'>".lang(35)."</label></thead>\n";$j=$b->databases();if($j){foreach($j
as$k){if(!information_schema($k)){$xf=preg_replace('~_.*~','',$k);echo"<tr><td>".checkbox("databases[]",$k,$a==""||$a=="$xf%",$k,"formUncheck('check-databases');","block")."\n";$yf[$xf]++;}}}else
echo"<tr><td><textarea name='databases' rows='10' cols='20'></textarea>";}echo'</table>
</form>
';$Hc=true;foreach($yf
as$x=>$X){if($x!=""&&$X>1){echo($Hc?"<p>":" ")."<a href='".h(ME)."dump=".urlencode("$x%")."'>".h($x)."</a>";$Hc=false;}}}elseif(isset($_GET["privileges"])){page_header(lang(63));$I=$h->query("SELECT User, Host FROM mysql.".(DB==""?"user":"db WHERE ".q(DB)." LIKE Db")." ORDER BY Host, User");$Tc=$I;if(!$I)$I=$h->query("SELECT SUBSTRING_INDEX(CURRENT_USER, '@', 1) AS User, SUBSTRING_INDEX(CURRENT_USER, '@', -1) AS Host");echo"<form action=''><p>\n";hidden_fields_get();echo"<input type='hidden' name='db' value='".h(DB)."'>\n",($Tc?"":"<input type='hidden' name='grant' value=''>\n"),"<table cellspacing='0'>\n","<thead><tr><th>".lang(33)."<th>".lang(32)."<th>&nbsp;</thead>\n";while($K=$I->fetch_assoc())echo'<tr'.odd().'><td>'.h($K["User"])."<td>".h($K["Host"]).'<td><a href="'.h(ME.'user='.urlencode($K["User"]).'&host='.urlencode($K["Host"])).'">'.lang(10)."</a>\n";if(!$Tc||DB!="")echo"<tr".odd()."><td><input name='user' autocapitalize='off'><td><input name='host' value='localhost' autocapitalize='off'><td><input type='submit' value='".lang(10)."'>\n";echo"</table>\n","</form>\n",'<p class="links"><a href="'.h(ME).'user=">'.lang(134)."</a>";}elseif(isset($_GET["sql"])){if(!$m&&$_POST["export"]){dump_headers("sql");$b->dumpTable("","");$b->dumpData("","table",$_POST["query"]);exit;}restart_session();$dd=&get_session("queries");$cd=&$dd[DB];if(!$m&&$_POST["clear"]){$cd=array();redirect(remove_from_uri("history"));}page_header((isset($_GET["import"])?lang(64):lang(55)),$m);if(!$m&&$_POST){$Pc=false;if(!isset($_GET["import"]))$H=$_POST["query"];elseif($_POST["webfile"]){$Pc=@fopen((file_exists("adminer.sql")?"adminer.sql":"compress.zlib://adminer.sql.gz"),"rb");$H=($Pc?fread($Pc,1e6):false);}else$H=get_file("sql_file",true);if(is_string($H)){if(function_exists('memory_get_usage'))@ini_set("memory_limit",max(ini_bytes("memory_limit"),2*strlen($H)+memory_get_usage()+8e6));if($H!=""&&strlen($H)<1e6){$Hf=$H.(preg_match("~;[ \t\r\n]*\$~",$H)?"":";");if(!$cd||reset(end($cd))!=$Hf){restart_session();$cd[]=array($Hf,time());set_session("queries",$dd);stop_session();}}$Ag="(?:\\s|/\\*.*\\*/|(?:#|-- )[^\n]*\n|--\r?\n)";$Mb=";";$D=0;$jc=true;$i=connect();if(is_object($i)&&DB!="")$i->select_db(DB);$nb=0;$oc=array();$Vd=0;$hf='[\'"'.($w=="sql"?'`#':($w=="sqlite"?'`[':($w=="mssql"?'[':''))).']|/\\*|-- |$'.($w=="pgsql"?'|\\$[^$]*\\$':'');$oh=microtime(true);parse_str($_COOKIE["adminer_export"],$wa);$ac=$b->dumpFormat();unset($ac["sql"]);while($H!=""){if(!$D&&preg_match("~^$Ag*DELIMITER\\s+(\\S+)~i",$H,$B)){$Mb=$B[1];$H=substr($H,strlen($B[0]));}else{preg_match('('.preg_quote($Mb)."\\s*|$hf)",$H,$B,PREG_OFFSET_CAPTURE,$D);list($Nc,$tf)=$B[0];if(!$Nc&&$Pc&&!feof($Pc))$H.=fread($Pc,1e5);else{if(!$Nc&&rtrim($H)=="")break;$D=$tf+strlen($Nc);if($Nc&&rtrim($Nc)!=$Mb){while(preg_match('('.($Nc=='/*'?'\\*/':($Nc=='['?']':(preg_match('~^-- |^#~',$Nc)?"\n":preg_quote($Nc)."|\\\\."))).'|$)s',$H,$B,PREG_OFFSET_CAPTURE,$D)){$ig=$B[0][0];if(!$ig&&$Pc&&!feof($Pc))$H.=fread($Pc,1e5);else{$D=$B[0][1]+strlen($ig);if($ig[0]!="\\")break;}}}else{$jc=false;$Hf=substr($H,0,$tf);$nb++;$Af="<pre id='sql-$nb'><code class='jush-$w'>".shorten_utf8(trim($Hf),1000)."</code></pre>\n";if(!$_POST["only_errors"]){echo$Af;ob_flush();flush();}$Dg=microtime(true);if($h->multi_query($Hf)&&is_object($i)&&preg_match("~^$Ag*USE\\b~isU",$Hf))$i->query($Hf);do{$I=$h->store_result();$eh=" <span class='time'>(".format_time($Dg).")</span>".(strlen($Hf)<1000?" <a href='".h(ME)."sql=".urlencode(trim($Hf))."'>".lang(10)."</a>":"");if($h->error){echo($_POST["only_errors"]?$Af:""),"<p class='error'>".lang(135).($h->errno?" ($h->errno)":"").": ".error()."\n";$oc[]=" <a href='#sql-$nb'>$nb</a>";if($_POST["error_stops"])break
2;}elseif(is_object($I)){$We=select($I,$i);if(!$_POST["only_errors"]){echo"<form action='' method='post'>\n","<p>".($I->num_rows?lang(136,$I->num_rows):"").$eh;$s="export-$nb";$xc=", <a href='#$s' onclick=\"return !toggle('$s');\">".lang(128)."</a><span id='$s' class='hidden'>: ".adminer_html_select("output",$b->dumpOutput(),$wa["output"])." ".adminer_html_select("format",$ac,$wa["format"])."<input type='hidden' name='query' value='".h($Hf)."'>"." <input type='submit' name='export' value='".lang(128)."'><input type='hidden' name='token' value='$T'></span>\n";if($i&&preg_match("~^($Ag|\\()*SELECT\\b~isU",$Hf)&&($wc=explain($i,$Hf))){$s="explain-$nb";echo", <a href='#$s' onclick=\"return !toggle('$s');\">EXPLAIN</a>$xc","<div id='$s' class='hidden'>\n";select($wc,$i,$We);echo"</div>\n";}else
echo$xc;echo"</form>\n";}}else{if(preg_match("~^$Ag*(CREATE|DROP|ALTER)$Ag+(DATABASE|SCHEMA)\\b~isU",$Hf)){restart_session();set_session("dbs",null);stop_session();}if(!$_POST["only_errors"])echo"<p class='message' title='".h($h->info)."'>".lang(137,$h->affected_rows)."$eh\n";}$Dg=microtime(true);}while($h->next_result());$Vd+=substr_count($Hf.$Nc,"\n");$H=substr($H,$D);$D=0;}}}}if($jc)echo"<p class='message'>".lang(138)."\n";elseif($_POST["only_errors"]){echo"<p class='message'>".lang(139,$nb-count($oc))," <span class='time'>(".format_time($oh).")</span>\n";}elseif($oc&&$nb>1)echo"<p class='error'>".lang(135).": ".implode("",$oc)."\n";}else
echo"<p class='error'>".upload_error($H)."\n";}echo'
<form action="" method="post" enctype="multipart/form-data" id="form">
';$tc="<input type='submit' value='".lang(140)."' title='Ctrl+Enter'>";if(!isset($_GET["import"])){$Hf=$_GET["sql"];if($_POST)$Hf=$_POST["query"];elseif($_GET["history"]=="all")$Hf=$cd;elseif($_GET["history"]!="")$Hf=$cd[$_GET["history"]][0];echo"<p>";textarea("query",$Hf,20);echo($_POST?"":"<script type='text/javascript'>focus(document.getElementsByTagName('textarea')[0]);</script>\n"),"<p>$tc\n";}else{echo"<fieldset><legend>".lang(141)."</legend><div>",(ini_bool("file_uploads")?'<input type="file" name="sql_file[]" multiple> (&lt; '.ini_get("upload_max_filesize").'B)':lang(142)),"\n$tc","</div></fieldset>\n","<fieldset><legend>".lang(143)."</legend><div>",lang(144,"<code>adminer.sql".(extension_loaded("zlib")?"[.gz]":"")."</code>"),' <input type="submit" name="webfile" value="'.lang(145).'">',"</div></fieldset>\n","<p>";}echo
checkbox("error_stops",1,($_POST?$_POST["error_stops"]:isset($_GET["import"])),lang(146))."\n",checkbox("only_errors",1,($_POST?$_POST["only_errors"]:isset($_GET["import"])),lang(147))."\n","<input type='hidden' name='token' value='$T'>\n";if(!isset($_GET["import"])&&$cd){print_fieldset("history",lang(148),$_GET["history"]!="");for($X=end($cd);$X;$X=prev($cd)){$x=key($cd);list($Hf,$eh,$ec)=$X;echo'<a href="'.h(ME."sql=&history=$x").'">'.lang(10)."</a>"." <span class='time' title='".@date('Y-m-d',$eh)."'>".@date("H:i:s",$eh)."</span>"." <code class='jush-$w'>".shorten_utf8(ltrim(str_replace("\n"," ",str_replace("\r","",preg_replace('~^(#|-- ).*~m','',$Hf)))),80,"</code>").($ec?" <span class='time'>($ec)</span>":"")."<br>\n";}echo"<input type='submit' name='clear' value='".lang(149)."'>\n","<a href='".h(ME."sql=&history=all")."'>".lang(150)."</a>\n","</div></fieldset>\n";}echo'</form>
';}elseif(isset($_GET["edit"])){$a=$_GET["edit"];$o=fields($a);$Z=(isset($_GET["select"])?(count($_POST["check"])==1?where_check($_POST["check"][0],$o):""):where($_GET,$o));$Fh=(isset($_GET["select"])?$_POST["edit"]:$Z);foreach($o
as$C=>$n){if(!isset($n["privileges"][$Fh?"update":"insert"])||$b->fieldName($n)=="")unset($o[$C]);}if($_POST&&!$m&&!isset($_GET["select"])){$A=$_POST["referer"];if($_POST["insert"])$A=($Fh?null:$_SERVER["REQUEST_URI"]);elseif(!preg_match('~^.+&select=.+$~',$A))$A=ME."select=".urlencode($a);$v=indexes($a);$Ah=unique_array($_GET["where"],$v);$Kf="\nWHERE $Z";if(isset($_POST["delete"]))queries_redirect($A,lang(151),$l->delete($a,$Kf,!$Ah));else{$O=array();foreach($o
as$C=>$n){$X=process_input($n);if($X!==false&&$X!==null)$O[idf_escape($C)]=$X;}if($Fh){if(!$O)redirect($A);queries_redirect($A,lang(152),$l->update($a,$O,$Kf,!$Ah));if(is_ajax()){page_headers();page_messages($m);exit;}}else{$I=$l->insert($a,$O);$Od=($I?last_id():0);queries_redirect($A,lang(153,($Od?" $Od":"")),$I);}}}$K=null;if($_POST["save"])$K=(array)$_POST["fields"];elseif($Z){$M=array();foreach($o
as$C=>$n){if(isset($n["privileges"]["select"])){$Fa=convert_field($n);if($_POST["clone"]&&$n["auto_increment"])$Fa="''";if($w=="sql"&&preg_match("~enum|set~",$n["type"]))$Fa="1*".idf_escape($C);$M[]=($Fa?"$Fa AS ":"").idf_escape($C);}}$K=array();if(!support("table"))$M=array("*");if($M){$I=$l->select($a,$M,array($Z),$M,array(),(isset($_GET["select"])?2:1));$K=$I->fetch_assoc();if(!$K)$K=false;if(isset($_GET["select"])&&(!$K||$I->fetch_assoc()))$K=null;}}if(!support("table")&&!$o){if(!$Z){$I=$l->select($a,array("*"),$Z,array("*"));$K=($I?$I->fetch_assoc():false);if(!$K)$K=array($l->primary=>"");}if($K){foreach($K
as$x=>$X){if(!$Z)$K[$x]=null;$o[$x]=array("field"=>$x,"null"=>($x!=$l->primary),"auto_increment"=>($x==$l->primary));}}}edit_form($a,$o,$K,$Fh);}elseif(isset($_GET["create"])){$a=$_GET["create"];$if=array();foreach(array('HASH','LINEAR HASH','KEY','LINEAR KEY','RANGE','LIST')as$x)$if[$x]=$x;$Rf=referencable_primary($a);$Lc=array();foreach($Rf
as$Pg=>$n)$Lc[str_replace("`","``",$Pg)."`".str_replace("`","``",$n["field"])]=$Pg;$Ze=array();$R=array();if($a!=""){$Ze=fields($a);$R=table_status($a);if(!$R)$m=lang(9);}$K=$_POST;$K["fields"]=(array)$K["fields"];if($K["auto_increment_col"])$K["fields"][$K["auto_increment_col"]]["auto_increment"]=true;if($_POST&&!process_fields($K["fields"])&&!$m){if($_POST["drop"])queries_redirect(substr(ME,0,-1),lang(154),drop_tables(array($a)));else{$o=array();$Ca=array();$Ih=false;$Jc=array();ksort($K["fields"]);$Ye=reset($Ze);$_a=" FIRST";foreach($K["fields"]as$x=>$n){$p=$Lc[$n["type"]];$wh=($p!==null?$Rf[$p]:$n);if($n["field"]!=""){if(!$n["has_default"])$n["default"]=null;if($x==$K["auto_increment_col"])$n["auto_increment"]=true;$Ff=process_field($n,$wh);$Ca[]=array($n["orig"],$Ff,$_a);if($Ff!=process_field($Ye,$Ye)){$o[]=array($n["orig"],$Ff,$_a);if($n["orig"]!=""||$_a)$Ih=true;}if($p!==null)$Jc[idf_escape($n["field"])]=($a!=""&&$w!="sqlite"?"ADD":" ").format_foreign_key(array('table'=>$Lc[$n["type"]],'source'=>array($n["field"]),'target'=>array($wh["field"]),'on_delete'=>$n["on_delete"],));$_a=" AFTER ".idf_escape($n["field"]);}elseif($n["orig"]!=""){$Ih=true;$o[]=array($n["orig"]);}if($n["orig"]!=""){$Ye=next($Ze);if(!$Ye)$_a="";}}$kf="";if($if[$K["partition_by"]]){$lf=array();if($K["partition_by"]=='RANGE'||$K["partition_by"]=='LIST'){foreach(array_filter($K["partition_names"])as$x=>$X){$Y=$K["partition_values"][$x];$lf[]="\n  PARTITION ".idf_escape($X)." VALUES ".($K["partition_by"]=='RANGE'?"LESS THAN":"IN").($Y!=""?" ($Y)":" MAXVALUE");}}$kf.="\nPARTITION BY $K[partition_by]($K[partition])".($lf?" (".implode(",",$lf)."\n)":($K["partitions"]?" PARTITIONS ".(+$K["partitions"]):""));}elseif(support("partitioning")&&preg_match("~partitioned~",$R["Create_options"]))$kf.="\nREMOVE PARTITIONING";$je=lang(155);if($a==""){cookie("adminer_engine",$K["Engine"]);$je=lang(156);}$C=trim($K["name"]);queries_redirect(ME.(support("table")?"table=":"select=").urlencode($C),$je,alter_table($a,$C,($w=="sqlite"&&($Ih||$Jc)?$Ca:$o),$Jc,$K["Comment"],($K["Engine"]&&$K["Engine"]!=$R["Engine"]?$K["Engine"]:""),($K["Collation"]&&$K["Collation"]!=$R["Collation"]?$K["Collation"]:""),($K["Auto_increment"]!=""?+$K["Auto_increment"]:""),$kf));}}page_header(($a!=""?lang(41):lang(66)),$m,array("table"=>$a),h($a));if(!$_POST){$K=array("Engine"=>$_COOKIE["adminer_engine"],"fields"=>array(array("field"=>"","type"=>(isset($yh["int"])?"int":(isset($yh["integer"])?"integer":"")))),"partition_names"=>array(""),);if($a!=""){$K=$R;$K["name"]=$a;$K["fields"]=array();if(!$_GET["auto_increment"])$K["Auto_increment"]="";foreach($Ze
as$n){$n["has_default"]=isset($n["default"]);$K["fields"][]=$n;}if(support("partitioning")){$Qc="FROM information_schema.PARTITIONS WHERE TABLE_SCHEMA = ".q(DB)." AND TABLE_NAME = ".q($a);$I=$h->query("SELECT PARTITION_METHOD, PARTITION_ORDINAL_POSITION, PARTITION_EXPRESSION $Qc ORDER BY PARTITION_ORDINAL_POSITION DESC LIMIT 1");list($K["partition_by"],$K["partitions"],$K["partition"])=$I->fetch_row();$lf=get_key_vals("SELECT PARTITION_NAME, PARTITION_DESCRIPTION $Qc AND PARTITION_NAME != '' ORDER BY PARTITION_ORDINAL_POSITION");$lf[""]="";$K["partition_names"]=array_keys($lf);$K["partition_values"]=array_values($lf);}}}$kb=collations();$lc=engines();foreach($lc
as$kc){if(!strcasecmp($kc,$K["Engine"])){$K["Engine"]=$kc;break;}}echo'
<form action="" method="post" id="form">
<p>
';if(support("columns")||$a==""){echo
lang(157),': <input name="name" maxlength="64" value="',h($K["name"]),'" autocapitalize="off">
';if($a==""&&!$_POST){?><script type='text/javascript'>focus(document.getElementById('form')['name']);</script><?php }echo($lc?"<select name='Engine' onchange='helpClose();'".on_help("getTarget(event).value",1).">".optionlist(array(""=>"(".lang(158).")")+$lc,$K["Engine"])."</select>":""),' ',($kb&&!preg_match("~sqlite|mssql~",$w)?adminer_html_select("Collation",array(""=>"(".lang(85).")")+$kb,$K["Collation"]):""),' <input type="submit" value="',lang(14),'">
';}echo'
';if(support("columns")){echo'<table cellspacing="0" id="edit-fields" class="nowrap">
';$pb=($_POST?$_POST["comments"]:$K["Comment"]!="");if(!$_POST&&!$pb){foreach($K["fields"]as$n){if($n["comment"]!=""){$pb=true;break;}}}edit_fields($K["fields"],$kb,"TABLE",$Lc,$pb);echo'</table>
<p>
',lang(56),': <input type="number" name="Auto_increment" size="6" value="',h($K["Auto_increment"]),'">
',checkbox("defaults",1,true,lang(93),"columnShow(this.checked, 5)","jsonly");if(!$_POST["defaults"]){echo'<script type="text/javascript">editingHideDefaults()</script>';}echo(support("comment")?"<label><input type='checkbox' name='comments' value='1' class='jsonly' onclick=\"columnShow(this.checked, 6); toggle('Comment'); if (this.checked) this.form['Comment'].focus();\"".($pb?" checked":"").">".lang(94)."</label>".' <input name="Comment" id="Comment" value="'.h($K["Comment"]).'" maxlength="'.($h->server_info>=5.5?2048:60).'"'.($pb?'':' class="hidden"').'>':''),'<p>
<input type="submit" value="',lang(14),'">
';}echo'
';if($a!=""){echo'<input type="submit" name="drop" value="',lang(114),'"',confirm(),'>';}if(support("partitioning")){$jf=preg_match('~RANGE|LIST~',$K["partition_by"]);print_fieldset("partition",lang(159),$K["partition_by"]);echo'<p>
',"<select name='partition_by' onchange='partitionByChange(this);'".on_help("getTarget(event).value.replace(/./, 'PARTITION BY \$&')",1).">".optionlist(array(""=>"")+$if,$K["partition_by"])."</select>",'(<input name="partition" value="',h($K["partition"]),'">)
',lang(160),': <input type="number" name="partitions" class="size',($jf||!$K["partition_by"]?" hidden":""),'" value="',h($K["partitions"]),'">
<table cellspacing="0" id="partition-table"',($jf?"":" class='hidden'"),'>
<thead><tr><th>',lang(161),'<th>',lang(162),'</thead>
';foreach($K["partition_names"]as$x=>$X){echo'<tr>','<td><input name="partition_names[]" value="'.h($X).'"'.($x==count($K["partition_names"])-1?' onchange="partitionNameChange(this);"':'').' autocapitalize="off">','<td><input name="partition_values[]" value="'.h($K["partition_values"][$x]).'">';}echo'</table>
</div></fieldset>
';}echo'<input type="hidden" name="token" value="',$T,'">
</form>
';}elseif(isset($_GET["indexes"])){$a=$_GET["indexes"];$md=array("PRIMARY","UNIQUE","INDEX");$R=table_status($a,true);if(preg_match('~MyISAM|M?aria'.($h->server_info>=5.6?'|InnoDB':'').'~i',$R["Engine"]))$md[]="FULLTEXT";$v=indexes($a);$zf=array();if($w=="mongo"){$zf=$v["_id_"];unset($md[0]);unset($v["_id_"]);}$K=$_POST;if($_POST&&!$m&&!$_POST["add"]&&!$_POST["drop_col"]){$c=array();foreach($K["indexes"]as$u){$C=$u["name"];if(in_array($u["type"],$md)){$f=array();$Td=array();$Ob=array();$O=array();ksort($u["columns"]);foreach($u["columns"]as$x=>$e){if($e!=""){$y=$u["lengths"][$x];$Nb=$u["descs"][$x];$O[]=idf_escape($e).($y?"(".(+$y).")":"").($Nb?" DESC":"");$f[]=$e;$Td[]=($y?$y:null);$Ob[]=$Nb;}}if($f){$uc=$v[$C];if($uc){ksort($uc["columns"]);ksort($uc["lengths"]);ksort($uc["descs"]);if($u["type"]==$uc["type"]&&array_values($uc["columns"])===$f&&(!$uc["lengths"]||array_values($uc["lengths"])===$Td)&&array_values($uc["descs"])===$Ob){unset($v[$C]);continue;}}$c[]=array($u["type"],$C,$O);}}}foreach($v
as$C=>$uc)$c[]=array($uc["type"],$C,"DROP");if(!$c)redirect(ME."table=".urlencode($a));queries_redirect(ME."table=".urlencode($a),lang(163),alter_indexes($a,$c));}page_header(lang(119),$m,array("table"=>$a),h($a));$o=array_keys(fields($a));if($_POST["add"]){foreach($K["indexes"]as$x=>$u){if($u["columns"][count($u["columns"])]!="")$K["indexes"][$x]["columns"][]="";}$u=end($K["indexes"]);if($u["type"]||array_filter($u["columns"],'strlen'))$K["indexes"][]=array("columns"=>array(1=>""));}if(!$K){foreach($v
as$x=>$u){$v[$x]["name"]=$x;$v[$x]["columns"][]="";}$v[]=array("columns"=>array(1=>""));$K["indexes"]=$v;}echo'
<form action="" method="post">
<table cellspacing="0" class="nowrap">
<thead><tr>
<th>',lang(164),'<th><input type="submit" style="left: -1000px; position: absolute;">',lang(165),'<th>',lang(166);?>
<th><noscript><input type='image' class='icon' name='add[0]' src='" . h(preg_replace("~\\?.*~", "", ME)) . "?file=plus.gif&amp;version=4.1.0' alt='+' title='<?php echo
lang(95),'\'></noscript>&nbsp;
</thead>
';if($zf){echo"<tr><td>PRIMARY<td>";foreach($zf["columns"]as$x=>$e){echo
select_input(" disabled",$o,$e),"<label><input disabled type='checkbox'>".lang(50)."</label> ";}echo"<td><td>\n";}$Cd=1;foreach($K["indexes"]as$u){if(!$_POST["drop_col"]||$Cd!=key($_POST["drop_col"])){echo"<tr><td>".adminer_html_select("indexes[$Cd][type]",array(-1=>"")+$md,$u["type"],($Cd==count($K["indexes"])?"indexesAddRow(this);":1)),"<td>";ksort($u["columns"]);$r=1;foreach($u["columns"]as$x=>$e){echo"<span>".select_input(" name='indexes[$Cd][columns][$r]' onchange=\"".($r==count($u["columns"])?"indexesAddColumn":"indexesChangeColumn")."(this, '".js_escape($w=="sql"?"":$_GET["indexes"]."_")."');\"",($o?array_combine($o,$o):$o),$e),($w=="sql"||$w=="mssql"?"<input type='number' name='indexes[$Cd][lengths][$r]' class='size' value='".h($u["lengths"][$x])."'>":""),($w!="sql"?checkbox("indexes[$Cd][descs][$r]",1,$u["descs"][$x],lang(50)):"")," </span>";$r++;}echo"<td><input name='indexes[$Cd][name]' value='".h($u["name"])."' autocapitalize='off'>\n","<td><input type='image' class='icon' name='drop_col[$Cd]' src='".h(preg_replace("~\\?.*~","",ME))."?file=cross.gif&amp;version=4.1.0' alt='x' title='".lang(98)."' onclick=\"return !editingRemoveRow(this, 'indexes\$1[type]');\">\n";}$Cd++;}echo'</table>
<p>
<input type="submit" value="',lang(14),'">
<input type="hidden" name="token" value="',$T,'">
</form>
';}elseif(isset($_GET["database"])){$K=$_POST;if($_POST&&!$m&&!isset($_POST["add_x"])){restart_session();$C=trim($K["name"]);if($_POST["drop"]){$_GET["db"]="";queries_redirect(remove_from_uri("db|database"),lang(167),drop_databases(array(DB)));}elseif(DB!==$C){if(DB!=""){$_GET["db"]=$C;queries_redirect(preg_replace('~\bdb=[^&]*&~','',ME)."db=".urlencode($C),lang(168),rename_database($C,$K["collation"]));}else{$j=explode("\n",str_replace("\r","",$C));$Jg=true;$Nd="";foreach($j
as$k){if(count($j)==1||$k!=""){if(!create_database($k,$K["collation"]))$Jg=false;$Nd=$k;}}queries_redirect(ME."db=".urlencode($Nd),lang(169),$Jg);}}else{if(!$K["collation"])redirect(substr(ME,0,-1));query_redirect("ALTER DATABASE ".idf_escape($C).(preg_match('~^[a-z0-9_]+$~i',$K["collation"])?" COLLATE $K[collation]":""),substr(ME,0,-1),lang(170));}}page_header(DB!=""?lang(59):lang(171),$m,array(),h(DB));$kb=collations();$C=DB;if($_POST)$C=$K["name"];elseif(DB!="")$K["collation"]=db_collation(DB,$kb);elseif($w=="sql"){foreach(get_vals("SHOW GRANTS")as$Tc){if(preg_match('~ ON (`(([^\\\\`]|``|\\\\.)*)%`\\.\\*)?~',$Tc,$B)&&$B[1]){$C=stripcslashes(idf_unescape("`$B[2]`"));break;}}}echo'
<form action="" method="post">
<p>
',($_POST["add_x"]||strpos($C,"\n")?'<textarea id="name" name="name" rows="10" cols="40">'.h($C).'</textarea><br>':'<input name="name" id="name" value="'.h($C).'" maxlength="64" autocapitalize="off">')."\n".($kb?adminer_html_select("collation",array(""=>"(".lang(85).")")+$kb,$K["collation"]).doc_link(array('sql'=>"charset-charsets.html",'mssql'=>"ms187963.aspx",)):"");?>
<script type='text/javascript'>focus(document.getElementById('name'));</script>
<input type="submit" value="<?php echo
lang(14),'">
';if(DB!="")echo"<input type='submit' name='drop' value='".lang(114)."'".confirm().">\n";elseif(!$_POST["add_x"]&&$_GET["db"]=="")echo"<input type='image' class='icon' name='add' src='".h(preg_replace("~\\?.*~","",ME))."?file=plus.gif&amp;version=4.1.0' alt='+' title='".lang(95)."'>\n";echo'<input type="hidden" name="token" value="',$T,'">
</form>
';}elseif(isset($_GET["scheme"])){$K=$_POST;if($_POST&&!$m){$_=preg_replace('~ns=[^&]*&~','',ME)."ns=";if($_POST["drop"])query_redirect("DROP SCHEMA ".idf_escape($_GET["ns"]),$_,lang(172));else{$C=trim($K["name"]);$_.=urlencode($C);if($_GET["ns"]=="")query_redirect("CREATE SCHEMA ".idf_escape($C),$_,lang(173));elseif($_GET["ns"]!=$C)query_redirect("ALTER SCHEMA ".idf_escape($_GET["ns"])." RENAME TO ".idf_escape($C),$_,lang(174));else
redirect($_);}}page_header($_GET["ns"]!=""?lang(60):lang(61),$m);if(!$K)$K["name"]=$_GET["ns"];echo'
<form action="" method="post">
<p><input name="name" id="name" value="',h($K["name"]);?>" autocapitalize="off">
<script type='text/javascript'>focus(document.getElementById('name'));</script>
<input type="submit" value="<?php echo
lang(14),'">
';if($_GET["ns"]!="")echo"<input type='submit' name='drop' value='".lang(114)."'".confirm().">\n";echo'<input type="hidden" name="token" value="',$T,'">
</form>
';}elseif(isset($_GET["call"])){$da=$_GET["call"];page_header(lang(175).": ".h($da),$m);$eg=routine($da,(isset($_GET["callf"])?"FUNCTION":"PROCEDURE"));$kd=array();$cf=array();foreach($eg["fields"]as$r=>$n){if(substr($n["inout"],-3)=="OUT")$cf[$r]="@".idf_escape($n["field"])." AS ".idf_escape($n["field"]);if(!$n["inout"]||substr($n["inout"],0,2)=="IN")$kd[]=$r;}if(!$m&&$_POST){$Xa=array();foreach($eg["fields"]as$x=>$n){if(in_array($x,$kd)){$X=process_input($n);if($X===false)$X="''";if(isset($cf[$x]))$h->query("SET @".idf_escape($n["field"])." = $X");}$Xa[]=(isset($cf[$x])?"@".idf_escape($n["field"]):$X);}$H=(isset($_GET["callf"])?"SELECT":"CALL")." ".idf_escape($da)."(".implode(", ",$Xa).")";echo"<p><code class='jush-$w'>".h($H)."</code> <a href='".h(ME)."sql=".urlencode($H)."'>".lang(10)."</a>\n";if(!$h->multi_query($H))echo"<p class='error'>".error()."\n";else{$i=connect();if(is_object($i))$i->select_db(DB);do{$I=$h->store_result();if(is_object($I))select($I,$i);else
echo"<p class='message'>".lang(176,$h->affected_rows)."\n";}while($h->next_result());if($cf)select($h->query("SELECT ".implode(", ",$cf)));}}echo'
<form action="" method="post">
';if($kd){echo"<table cellspacing='0'>\n";foreach($kd
as$x){$n=$eg["fields"][$x];$C=$n["field"];echo"<tr><th>".$b->fieldName($n);$Y=$_POST["fields"][$C];if($Y!=""){if($n["type"]=="enum")$Y=+$Y;if($n["type"]=="set")$Y=array_sum($Y);}input($n,$Y,(string)$_POST["function"][$C]);echo"\n";}echo"</table>\n";}echo'<p>
<input type="submit" value="',lang(175),'">
<input type="hidden" name="token" value="',$T,'">
</form>
';}elseif(isset($_GET["foreign"])){$a=$_GET["foreign"];$C=$_GET["name"];$K=$_POST;if($_POST&&!$m&&!$_POST["add"]&&!$_POST["change"]&&!$_POST["change-js"]){$je=($_POST["drop"]?lang(177):($C!=""?lang(178):lang(179)));$A=ME."table=".urlencode($a);$K["source"]=array_filter($K["source"],'strlen');ksort($K["source"]);$Xg=array();foreach($K["source"]as$x=>$X)$Xg[$x]=$K["target"][$x];$K["target"]=$Xg;if($w=="sqlite")queries_redirect($A,$je,recreate_table($a,$a,array(),array(),array(" $C"=>($_POST["drop"]?"":" ".format_foreign_key($K)))));else{$c="ALTER TABLE ".table($a);$Vb="\nDROP ".($w=="sql"?"FOREIGN KEY ":"CONSTRAINT ").idf_escape($C);if($_POST["drop"])query_redirect($c.$Vb,$A,$je);else{query_redirect($c.($C!=""?"$Vb,":"")."\nADD".format_foreign_key($K),$A,$je);$m=lang(180)."<br>$m";}}}page_header(lang(181),$m,array("table"=>$a),h($a));if($_POST){ksort($K["source"]);if($_POST["add"])$K["source"][]="";elseif($_POST["change"]||$_POST["change-js"])$K["target"]=array();}elseif($C!=""){$Lc=foreign_keys($a);$K=$Lc[$C];$K["source"][]="";}else{$K["table"]=$a;$K["source"]=array("");}$_g=array_keys(fields($a));$Xg=($a===$K["table"]?$_g:array_keys(fields($K["table"])));$Qf=array_keys(array_filter(table_status('',true),'fk_support'));echo'
<form action="" method="post">
<p>
';if($K["db"]==""&&$K["ns"]==""){echo
lang(182),':
',adminer_html_select("table",$Qf,$K["table"],"this.form['change-js'].value = '1'; this.form.submit();"),'<input type="hidden" name="change-js" value="">
<noscript><p><input type="submit" name="change" value="',lang(183),'"></noscript>
<table cellspacing="0">
<thead><tr><th>',lang(121),'<th>',lang(122),'</thead>
';$Cd=0;foreach($K["source"]as$x=>$X){echo"<tr>","<td>".adminer_html_select("source[".(+$x)."]",array(-1=>"")+$_g,$X,($Cd==count($K["source"])-1?"foreignAddRow(this);":1)),"<td>".adminer_html_select("target[".(+$x)."]",$Xg,$K["target"][$x]);$Cd++;}echo'</table>
<p>
',lang(87),': ',adminer_html_select("on_delete",array(-1=>"")+explode("|",$Ke),$K["on_delete"]),' ',lang(86),': ',adminer_html_select("on_update",array(-1=>"")+explode("|",$Ke),$K["on_update"]),doc_link(array('sql'=>"innodb-foreign-key-constraints.html",'pgsql'=>"sql-createtable.html#SQL-CREATETABLE-REFERENCES",'mssql'=>"ms174979.aspx",'oracle'=>"clauses002.htm#sthref2903",)),'<p>
<input type="submit" value="',lang(14),'">
<noscript><p><input type="submit" name="add" value="',lang(184),'"></noscript>
';}if($C!=""){echo'<input type="submit" name="drop" value="',lang(114),'"',confirm(),'>';}echo'<input type="hidden" name="token" value="',$T,'">
</form>
';}elseif(isset($_GET["view"])){$a=$_GET["view"];$K=$_POST;if($_POST&&!$m){$C=trim($K["name"]);$Fa=" AS\n$K[select]";$A=ME."table=".urlencode($C);$je=lang(185);if(!$_POST["drop"]&&$a==$C&&$w!="sqlite")query_redirect(($w=="mssql"?"ALTER":"CREATE OR REPLACE")." VIEW ".table($C).$Fa,$A,$je);else{$Zg=$C."_adminer_".uniqid();drop_create("DROP VIEW ".table($a),"CREATE VIEW ".table($C).$Fa,"DROP VIEW ".table($C),"CREATE VIEW ".table($Zg).$Fa,"DROP VIEW ".table($Zg),($_POST["drop"]?substr(ME,0,-1):$A),lang(186),$je,lang(187),$a,$C);}}if(!$_POST&&$a!=""){$K=view($a);$K["name"]=$a;if(!$m)$m=$h->error;}page_header(($a!=""?lang(40):lang(188)),$m,array("table"=>$a),h($a));echo'
<form action="" method="post">
<p>',lang(166),': <input name="name" value="',h($K["name"]),'" maxlength="64" autocapitalize="off">
<p>';textarea("select",$K["select"]);echo'<p>
<input type="submit" value="',lang(14),'">
';if($_GET["view"]!=""){echo'<input type="submit" name="drop" value="',lang(114),'"',confirm(),'>';}echo'<input type="hidden" name="token" value="',$T,'">
</form>
';}elseif(isset($_GET["event"])){$aa=$_GET["event"];$ud=array("YEAR","QUARTER","MONTH","DAY","HOUR","MINUTE","WEEK","SECOND","YEAR_MONTH","DAY_HOUR","DAY_MINUTE","DAY_SECOND","HOUR_MINUTE","HOUR_SECOND","MINUTE_SECOND");$Fg=array("ENABLED"=>"ENABLE","DISABLED"=>"DISABLE","SLAVESIDE_DISABLED"=>"DISABLE ON SLAVE");$K=$_POST;if($_POST&&!$m){if($_POST["drop"])query_redirect("DROP EVENT ".idf_escape($aa),substr(ME,0,-1),lang(189));elseif(in_array($K["INTERVAL_FIELD"],$ud)&&isset($Fg[$K["STATUS"]])){$jg="\nON SCHEDULE ".($K["INTERVAL_VALUE"]?"EVERY ".q($K["INTERVAL_VALUE"])." $K[INTERVAL_FIELD]".($K["STARTS"]?" STARTS ".q($K["STARTS"]):"").($K["ENDS"]?" ENDS ".q($K["ENDS"]):""):"AT ".q($K["STARTS"]))." ON COMPLETION".($K["ON_COMPLETION"]?"":" NOT")." PRESERVE";queries_redirect(substr(ME,0,-1),($aa!=""?lang(190):lang(191)),queries(($aa!=""?"ALTER EVENT ".idf_escape($aa).$jg.($aa!=$K["EVENT_NAME"]?"\nRENAME TO ".idf_escape($K["EVENT_NAME"]):""):"CREATE EVENT ".idf_escape($K["EVENT_NAME"]).$jg)."\n".$Fg[$K["STATUS"]]." COMMENT ".q($K["EVENT_COMMENT"]).rtrim(" DO\n$K[EVENT_DEFINITION]",";").";"));}}page_header(($aa!=""?lang(192).": ".h($aa):lang(193)),$m);if(!$K&&$aa!=""){$L=get_rows("SELECT * FROM information_schema.EVENTS WHERE EVENT_SCHEMA = ".q(DB)." AND EVENT_NAME = ".q($aa));$K=reset($L);}echo'
<form action="" method="post">
<table cellspacing="0">
<tr><th>',lang(166),'<td><input name="EVENT_NAME" value="',h($K["EVENT_NAME"]),'" maxlength="64" autocapitalize="off">
<tr><th title="datetime">',lang(194),'<td><input name="STARTS" value="',h("$K[EXECUTE_AT]$K[STARTS]"),'">
<tr><th title="datetime">',lang(195),'<td><input name="ENDS" value="',h($K["ENDS"]),'">
<tr><th>',lang(196),'<td><input type="number" name="INTERVAL_VALUE" value="',h($K["INTERVAL_VALUE"]),'" class="size"> ',adminer_html_select("INTERVAL_FIELD",$ud,$K["INTERVAL_FIELD"]),'<tr><th>',lang(105),'<td>',adminer_html_select("STATUS",$Fg,$K["STATUS"]),'<tr><th>',lang(94),'<td><input name="EVENT_COMMENT" value="',h($K["EVENT_COMMENT"]),'" maxlength="64">
<tr><th>&nbsp;<td>',checkbox("ON_COMPLETION","PRESERVE",$K["ON_COMPLETION"]=="PRESERVE",lang(197)),'</table>
<p>';textarea("EVENT_DEFINITION",$K["EVENT_DEFINITION"]);echo'<p>
<input type="submit" value="',lang(14),'">
';if($aa!=""){echo'<input type="submit" name="drop" value="',lang(114),'"',confirm(),'>';}echo'<input type="hidden" name="token" value="',$T,'">
</form>
';}elseif(isset($_GET["procedure"])){$da=$_GET["procedure"];$eg=(isset($_GET["function"])?"FUNCTION":"PROCEDURE");$K=$_POST;$K["fields"]=(array)$K["fields"];if($_POST&&!process_fields($K["fields"])&&!$m){$Zg="$K[name]_adminer_".uniqid();drop_create("DROP $eg ".idf_escape($da),create_routine($eg,$K),"DROP $eg ".idf_escape($K["name"]),create_routine($eg,array("name"=>$Zg)+$K),"DROP $eg ".idf_escape($Zg),substr(ME,0,-1),lang(198),lang(199),lang(200),$da,$K["name"]);}page_header(($da!=""?(isset($_GET["function"])?lang(201):lang(202)).": ".h($da):(isset($_GET["function"])?lang(203):lang(204))),$m);if(!$_POST&&$da!=""){$K=routine($da,$eg);$K["name"]=$da;}$kb=get_vals("SHOW CHARACTER SET");sort($kb);$fg=routine_languages();echo'
<form action="" method="post" id="form">
<p>',lang(166),': <input name="name" value="',h($K["name"]),'" maxlength="64" autocapitalize="off">
',($fg?lang(19).": ".adminer_html_select("language",$fg,$K["language"]):""),'<input type="submit" value="',lang(14),'">
<table cellspacing="0" class="nowrap">
';edit_fields($K["fields"],$kb,$eg);if(isset($_GET["function"])){echo"<tr><td>".lang(205);edit_type("returns",$K["returns"],$kb);}echo'</table>
<p>';textarea("definition",$K["definition"]);echo'<p>
<input type="submit" value="',lang(14),'">
';if($da!=""){echo'<input type="submit" name="drop" value="',lang(114),'"',confirm(),'>';}echo'<input type="hidden" name="token" value="',$T,'">
</form>
';}elseif(isset($_GET["sequence"])){$fa=$_GET["sequence"];$K=$_POST;if($_POST&&!$m){$_=substr(ME,0,-1);$C=trim($K["name"]);if($_POST["drop"])query_redirect("DROP SEQUENCE ".idf_escape($fa),$_,lang(206));elseif($fa=="")query_redirect("CREATE SEQUENCE ".idf_escape($C),$_,lang(207));elseif($fa!=$C)query_redirect("ALTER SEQUENCE ".idf_escape($fa)." RENAME TO ".idf_escape($C),$_,lang(208));else
redirect($_);}page_header($fa!=""?lang(209).": ".h($fa):lang(210),$m);if(!$K)$K["name"]=$fa;echo'
<form action="" method="post">
<p><input name="name" value="',h($K["name"]),'" autocapitalize="off">
<input type="submit" value="',lang(14),'">
';if($fa!="")echo"<input type='submit' name='drop' value='".lang(114)."'".confirm().">\n";echo'<input type="hidden" name="token" value="',$T,'">
</form>
';}elseif(isset($_GET["type"])){$ga=$_GET["type"];$K=$_POST;if($_POST&&!$m){$_=substr(ME,0,-1);if($_POST["drop"])query_redirect("DROP TYPE ".idf_escape($ga),$_,lang(211));else
query_redirect("CREATE TYPE ".idf_escape(trim($K["name"]))." $K[as]",$_,lang(212));}page_header($ga!=""?lang(213).": ".h($ga):lang(214),$m);if(!$K)$K["as"]="AS ";echo'
<form action="" method="post">
<p>
';if($ga!="")echo"<input type='submit' name='drop' value='".lang(114)."'".confirm().">\n";else{echo"<input name='name' value='".h($K['name'])."' autocapitalize='off'>\n";textarea("as",$K["as"]);echo"<p><input type='submit' value='".lang(14)."'>\n";}echo'<input type="hidden" name="token" value="',$T,'">
</form>
';}elseif(isset($_GET["trigger"])){$a=$_GET["trigger"];$C=$_GET["name"];$uh=trigger_options();$K=(array)trigger($C)+array("Trigger"=>$a."_bi");if($_POST){if(!$m&&in_array($_POST["Timing"],$uh["Timing"])&&in_array($_POST["Event"],$uh["Event"])&&in_array($_POST["Type"],$uh["Type"])){$Je=" ON ".table($a);$Vb="DROP TRIGGER ".idf_escape($C).($w=="pgsql"?$Je:"");$A=ME."table=".urlencode($a);if($_POST["drop"])query_redirect($Vb,$A,lang(215));else{if($C!="")queries($Vb);queries_redirect($A,($C!=""?lang(216):lang(217)),queries(create_trigger($Je,$_POST)));if($C!="")queries(create_trigger($Je,$K+array("Type"=>reset($uh["Type"]))));}}$K=$_POST;}page_header(($C!=""?lang(218).": ".h($C):lang(219)),$m,array("table"=>$a));echo'
<form action="" method="post" id="form">
<table cellspacing="0">
<tr><th>',lang(220),'<td>',adminer_html_select("Timing",$uh["Timing"],$K["Timing"],"triggerChange(/^".preg_quote($a,"/")."_[ba][iud]$/, '".js_escape($a)."', this.form);"),'<tr><th>',lang(221),'<td>',adminer_html_select("Event",$uh["Event"],$K["Event"],"this.form['Timing'].onchange();"),(in_array("UPDATE OF",$uh["Event"])?" <input name='Of' value='".h($K["Of"])."' class='hidden'>":""),'<tr><th>',lang(90),'<td>',adminer_html_select("Type",$uh["Type"],$K["Type"]),'</table>
<p>',lang(166),': <input name="Trigger" value="',h($K["Trigger"]);?>" maxlength="64" autocapitalize="off">
<script type="text/javascript">document.getElementById('form')['Timing'].onchange();</script>
<p><?php textarea("Statement",$K["Statement"]);echo'<p>
<input type="submit" value="',lang(14),'">
';if($C!=""){echo'<input type="submit" name="drop" value="',lang(114),'"',confirm(),'>';}echo'<input type="hidden" name="token" value="',$T,'">
</form>
';}elseif(isset($_GET["user"])){$ha=$_GET["user"];$Df=array(""=>array("All privileges"=>""));foreach(get_rows("SHOW PRIVILEGES")as$K){foreach(explode(",",($K["Privilege"]=="Grant option"?"":$K["Context"]))as$vb)$Df[$vb][$K["Privilege"]]=$K["Comment"];}$Df["Server Admin"]+=$Df["File access on server"];$Df["Databases"]["Create routine"]=$Df["Procedures"]["Create routine"];unset($Df["Procedures"]["Create routine"]);$Df["Columns"]=array();foreach(array("Select","Insert","Update","References")as$X)$Df["Columns"][$X]=$Df["Tables"][$X];unset($Df["Server Admin"]["Usage"]);foreach($Df["Tables"]as$x=>$X)unset($Df["Databases"][$x]);$we=array();if($_POST){foreach($_POST["objects"]as$x=>$X)$we[$X]=(array)$we[$X]+(array)$_POST["grants"][$x];}$Uc=array();$He="";if(isset($_GET["host"])&&($I=$h->query("SHOW GRANTS FOR ".q($ha)."@".q($_GET["host"])))){while($K=$I->fetch_row()){if(preg_match('~GRANT (.*) ON (.*) TO ~',$K[0],$B)&&preg_match_all('~ *([^(,]*[^ ,(])( *\\([^)]+\\))?~',$B[1],$be,PREG_SET_ORDER)){foreach($be
as$X){if($X[1]!="USAGE")$Uc["$B[2]$X[2]"][$X[1]]=true;if(preg_match('~ WITH GRANT OPTION~',$K[0]))$Uc["$B[2]$X[2]"]["GRANT OPTION"]=true;}}if(preg_match("~ IDENTIFIED BY PASSWORD '([^']+)~",$K[0],$B))$He=$B[1];}}if($_POST&&!$m){$Ie=(isset($_GET["host"])?q($ha)."@".q($_GET["host"]):"''");if($_POST["drop"])query_redirect("DROP USER $Ie",ME."privileges=",lang(222));else{$ye=q($_POST["user"])."@".q($_POST["host"]);$mf=$_POST["pass"];if($mf!=''&&!$_POST["hashed"]){$mf=$h->result("SELECT PASSWORD(".q($mf).")");$m=!$mf;}$Ab=false;if(!$m){if($Ie!=$ye){$Ab=queries(($h->server_info<5?"GRANT USAGE ON *.* TO":"CREATE USER")." $ye IDENTIFIED BY PASSWORD ".q($mf));$m=!$Ab;}elseif($mf!=$He)queries("SET PASSWORD FOR $ye = ".q($mf));}if(!$m){$bg=array();foreach($we
as$Ce=>$Tc){if(isset($_GET["grant"]))$Tc=array_filter($Tc);$Tc=array_keys($Tc);if(isset($_GET["grant"]))$bg=array_diff(array_keys(array_filter($we[$Ce],'strlen')),$Tc);elseif($Ie==$ye){$Fe=array_keys((array)$Uc[$Ce]);$bg=array_diff($Fe,$Tc);$Tc=array_diff($Tc,$Fe);unset($Uc[$Ce]);}if(preg_match('~^(.+)\\s*(\\(.*\\))?$~U',$Ce,$B)&&(!grant("REVOKE",$bg,$B[2]," ON $B[1] FROM $ye")||!grant("GRANT",$Tc,$B[2]," ON $B[1] TO $ye"))){$m=true;break;}}}if(!$m&&isset($_GET["host"])){if($Ie!=$ye)queries("DROP USER $Ie");elseif(!isset($_GET["grant"])){foreach($Uc
as$Ce=>$bg){if(preg_match('~^(.+)(\\(.*\\))?$~U',$Ce,$B))grant("REVOKE",array_keys($bg),$B[2]," ON $B[1] FROM $ye");}}}queries_redirect(ME."privileges=",(isset($_GET["host"])?lang(223):lang(224)),!$m);if($Ab)$h->query("DROP USER $ye");}}page_header((isset($_GET["host"])?lang(33).": ".h("$ha@$_GET[host]"):lang(134)),$m,array("privileges"=>array('',lang(63))));if($_POST){$K=$_POST;$Uc=$we;}else{$K=$_GET+array("host"=>$h->result("SELECT SUBSTRING_INDEX(CURRENT_USER, '@', -1)"));$K["pass"]=$He;if($He!="")$K["hashed"]=true;$Uc[(DB==""||$Uc?"":idf_escape(addcslashes(DB,"%_\\"))).".*"]=array();}echo'<form action="" method="post">
<table cellspacing="0">
<tr><th>',lang(32),'<td><input name="host" maxlength="60" value="',h($K["host"]),'" autocapitalize="off">
<tr><th>',lang(33),'<td><input name="user" maxlength="16" value="',h($K["user"]),'" autocapitalize="off">
<tr><th>',lang(34),'<td><input name="pass" id="pass" value="',h($K["pass"]),'">
';if(!$K["hashed"]){echo'<script type="text/javascript">typePassword(document.getElementById(\'pass\'));</script>';}echo
checkbox("hashed",1,$K["hashed"],lang(225),"typePassword(this.form['pass'], this.checked);"),'</table>

';echo"<table cellspacing='0'>\n","<thead><tr><th colspan='2'>".lang(63).doc_link(array('sql'=>"grant.html#priv_level"));$r=0;foreach($Uc
as$Ce=>$Tc){echo'<th>'.($Ce!="*.*"?"<input name='objects[$r]' value='".h($Ce)."' size='10' autocapitalize='off'>":"<input type='hidden' name='objects[$r]' value='*.*' size='10'>*.*");$r++;}echo"</thead>\n";foreach(array(""=>"","Server Admin"=>lang(32),"Databases"=>lang(35),"Tables"=>lang(117),"Columns"=>lang(118),"Procedures"=>lang(226),)as$vb=>$Nb){foreach((array)$Df[$vb]as$Cf=>$ob){echo"<tr".odd()."><td".($Nb?">$Nb<td":" colspan='2'").' lang="en" title="'.h($ob).'">'.h($Cf);$r=0;foreach($Uc
as$Ce=>$Tc){$C="'grants[$r][".h(strtoupper($Cf))."]'";$Y=$Tc[strtoupper($Cf)];if($vb=="Server Admin"&&$Ce!=(isset($Uc["*.*"])?"*.*":".*"))echo"<td>&nbsp;";elseif(isset($_GET["grant"]))echo"<td><select name=$C><option><option value='1'".($Y?" selected":"").">".lang(227)."<option value='0'".($Y=="0"?" selected":"").">".lang(228)."</select>";else
echo"<td align='center'><label class='block'><input type='checkbox' name=$C value='1'".($Y?" checked":"").($Cf=="All privileges"?" id='grants-$r-all'":($Cf=="Grant option"?"":" onclick=\"if (this.checked) formUncheck('grants-$r-all');\""))."></label>";$r++;}}}echo"</table>\n",'<p>
<input type="submit" value="',lang(14),'">
';if(isset($_GET["host"])){echo'<input type="submit" name="drop" value="',lang(114),'"',confirm(),'>';}echo'<input type="hidden" name="token" value="',$T,'">
</form>
';}elseif(isset($_GET["processlist"])){if(support("kill")&&$_POST&&!$m){$Jd=0;foreach((array)$_POST["kill"]as$X){if(queries("KILL ".(+$X)))$Jd++;}queries_redirect(ME."processlist=",lang(229,$Jd),$Jd||!$_POST["kill"]);}page_header(lang(103),$m);echo'
<form action="" method="post">
<table cellspacing="0" onclick="tableClick(event);" ondblclick="tableClick(event, true);" class="nowrap checkable">
';$r=-1;foreach(process_list()as$r=>$K){if(!$r){echo"<thead><tr lang='en'>".(support("kill")?"<th>&nbsp;":"");foreach($K
as$x=>$X)echo"<th>$x".doc_link(array('sql'=>"show-processlist.html#processlist_".strtolower($x),'pgsql'=>"monitoring-stats.html#PG-STAT-ACTIVITY-VIEW",'oracle'=>"../b14237/dynviews_2088.htm",));echo"</thead>\n";}echo"<tr".odd().">".(support("kill")?"<td>".checkbox("kill[]",$K["Id"],0):"");foreach($K
as$x=>$X)echo"<td>".(($w=="sql"&&$x=="Info"&&preg_match("~Query|Killed~",$K["Command"])&&$X!="")||($w=="pgsql"&&$x=="current_query"&&$X!="<IDLE>")||($w=="oracle"&&$x=="sql_text"&&$X!="")?"<code class='jush-$w'>".shorten_utf8($X,100,"</code>").' <a href="'.h(ME.($K["db"]!=""?"db=".urlencode($K["db"])."&":"")."sql=".urlencode($X)).'">'.lang(230).'</a>':nbsp($X));echo"\n";}echo'</table>
<script type=\'text/javascript\'>tableCheck();</script>
<p>
';if(support("kill")){echo($r+1)."/".lang(231,$h->result("SELECT @@max_connections")),"<p><input type='submit' value='".lang(232)."'>\n";}echo'<input type="hidden" name="token" value="',$T,'">
</form>
';}elseif(isset($_GET["select"])){$a=$_GET["select"];$R=table_status1($a);$v=indexes($a);$o=fields($a);$Lc=column_foreign_keys($a);$Ee="";if($R["Oid"]){$Ee=($w=="sqlite"?"rowid":"oid");$v[]=array("type"=>"PRIMARY","columns"=>array($Ee));}parse_str($_COOKIE["adminer_import"],$xa);$cg=array();$f=array();$dh=null;foreach($o
as$x=>$n){$C=$b->fieldName($n);if(isset($n["privileges"]["select"])&&$C!=""){$f[$x]=html_entity_decode(strip_tags($C),ENT_QUOTES);if(is_shortable($n))$dh=$b->selectLengthProcess();}$cg+=$n["privileges"];}list($M,$Vc)=$b->selectColumnsProcess($f,$v);$yd=count($Vc)<count($M);$Z=$b->selectSearchProcess($o,$v);$Te=$b->selectOrderProcess($o,$v);$z=$b->selectLimitProcess();$Qc=($M?implode(", ",$M):"*".($Ee?", $Ee":"")).convert_fields($f,$o,$M)."\nFROM ".table($a);$Wc=($Vc&&$yd?"\nGROUP BY ".implode(", ",$Vc):"").($Te?"\nORDER BY ".implode(", ",$Te):"");if($_GET["val"]&&is_ajax()){header("Content-Type: text/plain; charset=utf-8");foreach($_GET["val"]as$Bh=>$K){$Fa=convert_field($o[key($K)]);$M=array($Fa?$Fa:idf_escape(key($K)));$Z[]=where_check($Bh,$o);$J=$l->select($a,$M,$Z,$M);if($J)echo
reset($J->fetch_row());}exit;}if($_POST&&!$m){$Xh=$Z;if(!$_POST["all"]&&is_array($_POST["check"])){$bb=array();foreach($_POST["check"]as$Za)$bb[]=where_check($Za,$o);$Xh[]="((".implode(") OR (",$bb)."))";}$Xh=($Xh?"\nWHERE ".implode(" AND ",$Xh):"");$zf=$Dh=null;foreach($v
as$u){if($u["type"]=="PRIMARY"){$zf=array_flip($u["columns"]);$Dh=($M?$zf:array());break;}}foreach((array)$Dh
as$x=>$X){if(in_array(idf_escape($x),$M))unset($Dh[$x]);}if($_POST["export"]){cookie("adminer_import","output=".urlencode($_POST["output"])."&format=".urlencode($_POST["format"]));dump_headers($a);$b->dumpTable($a,"");if(!is_array($_POST["check"])||$Dh===array())$H="SELECT $Qc$Xh$Wc";else{$_h=array();foreach($_POST["check"]as$X)$_h[]="(SELECT".limit($Qc,"\nWHERE ".($Z?implode(" AND ",$Z)." AND ":"").where_check($X,$o).$Wc,1).")";$H=implode(" UNION ALL ",$_h);}$b->dumpData($a,"table",$H);exit;}if(!$b->selectEmailProcess($Z,$Lc)){if($_POST["save"]||$_POST["delete"]){$I=true;$ya=0;$O=array();if(!$_POST["delete"]){foreach($f
as$C=>$X){$X=process_input($o[$C]);if($X!==null&&($_POST["clone"]||$X!==false))$O[idf_escape($C)]=($X!==false?$X:idf_escape($C));}}if($_POST["delete"]||$O){if($_POST["clone"])$H="INTO ".table($a)." (".implode(", ",array_keys($O)).")\nSELECT ".implode(", ",$O)."\nFROM ".table($a);if($_POST["all"]||($Dh===array()&&is_array($_POST["check"]))||$yd){$I=($_POST["delete"]?$l->delete($a,$Xh):($_POST["clone"]?queries("INSERT $H$Xh"):$l->update($a,$O,$Xh)));$ya=$h->affected_rows;}else{foreach((array)$_POST["check"]as$X){$Wh="\nWHERE ".($Z?implode(" AND ",$Z)." AND ":"").where_check($X,$o);$I=($_POST["delete"]?$l->delete($a,$Wh,1):($_POST["clone"]?queries("INSERT".limit1($H,$Wh)):$l->update($a,$O,$Wh)));if(!$I)break;$ya+=$h->affected_rows;}}}$je=lang(233,$ya);if($_POST["clone"]&&$I&&$ya==1){$Od=last_id();if($Od)$je=lang(153," $Od");}queries_redirect(remove_from_uri($_POST["all"]&&$_POST["delete"]?"page":""),$je,$I);if(!$_POST["delete"]){edit_form($a,$o,(array)$_POST["fields"],!$_POST["clone"]);page_footer();exit;}}elseif(!$_POST["import"]){if(!$_POST["val"])$m=lang(234);else{$I=true;$ya=0;foreach($_POST["val"]as$Bh=>$K){$O=array();foreach($K
as$x=>$X){$x=bracket_escape($x,1);$O[idf_escape($x)]=(preg_match('~char|text~',$o[$x]["type"])||$X!=""?$b->processInput($o[$x],$X):"NULL");}$I=$l->update($a,$O," WHERE ".($Z?implode(" AND ",$Z)." AND ":"").where_check($Bh,$o),!($yd||$Dh===array())," ");if(!$I)break;$ya+=$h->affected_rows;}queries_redirect(remove_from_uri(),lang(233,$ya),$I);}}elseif(!is_string($Ec=get_file("csv_file",true)))$m=upload_error($Ec);elseif(!preg_match('~~u',$Ec))$m=lang(235);else{cookie("adminer_import","output=".urlencode($xa["output"])."&format=".urlencode($_POST["separator"]));$I=true;$lb=array_keys($o);preg_match_all('~(?>"[^"]*"|[^"\\r\\n]+)+~',$Ec,$be);$ya=count($be[0]);$l->begin();$rg=($_POST["separator"]=="csv"?",":($_POST["separator"]=="tsv"?"\t":";"));$L=array();foreach($be[0]as$x=>$X){preg_match_all("~((?>\"[^\"]*\")+|[^$rg]*)$rg~",$X.$rg,$ce);if(!$x&&!array_diff($ce[1],$lb)){$lb=$ce[1];$ya--;}else{$O=array();foreach($ce[1]as$r=>$ib)$O[idf_escape($lb[$r])]=($ib==""&&$o[$lb[$r]]["null"]?"NULL":q(str_replace('""','"',preg_replace('~^"|"$~','',$ib))));$L[]=$O;}}$I=(!$L||$l->insertUpdate($a,$L,$zf));if($I)$l->commit();queries_redirect(remove_from_uri("page"),lang(236,$ya),$I);$l->rollback();}}}$Pg=$b->tableName($R);if(is_ajax()){page_headers();ob_start();}else
page_header(lang(44).": $Pg",$m);$O=null;if(isset($cg["insert"])||!support("table")){$O="";foreach((array)$_GET["where"]as$X){if(count($Lc[$X["col"]])==1&&($X["op"]=="="||(!$X["op"]&&!preg_match('~[_%]~',$X["val"]))))$O.="&set".urlencode("[".bracket_escape($X["col"])."]")."=".urlencode($X["val"]);}}$b->selectLinks($R,$O);if(!$f&&support("table"))echo"<p class='error'>".lang(237).($o?".":": ".error())."\n";else{echo"<form action='' id='form'>\n","<div style='display: none;'>";hidden_fields_get();echo(DB!=""?'<input type="hidden" name="db" value="'.h(DB).'">'.(isset($_GET["ns"])?'<input type="hidden" name="ns" value="'.h($_GET["ns"]).'">':""):"");echo'<input type="hidden" name="select" value="'.h($a).'">',"</div>\n";$b->selectColumnsPrint($M,$f);$b->selectSearchPrint($Z,$f,$v);$b->selectOrderPrint($Te,$f,$v);$b->selectLimitPrint($z);$b->selectLengthPrint($dh);$b->selectActionPrint($v);echo"</form>\n";$E=$_GET["page"];if($E=="last"){$Oc=$h->result(count_rows($a,$Z,$yd,$Vc));$E=floor(max(0,$Oc-1)/$z);}$og=$M;if(!$og){$og[]="*";if($Ee)$og[]=$Ee;}$wb=convert_fields($f,$o,$M);if($wb)$og[]=substr($wb,2);$I=$l->select($a,$og,$Z,$Vc,$Te,$z,$E,true);if(!$I)echo"<p class='error'>".error()."\n";else{if($w=="mssql"&&$E)$I->seek($z*$E);$ic=array();echo"<form action='' method='post' enctype='multipart/form-data'>\n";$L=array();while($K=$I->fetch_assoc()){if($E&&$w=="oracle")unset($K["RNUM"]);$L[]=$K;}if($_GET["page"]!="last"&&+$z&&$Vc&&$yd&&$w=="sql")$Oc=$h->result(" SELECT FOUND_ROWS()");if(!$L)echo"<p class='message'>".lang(12)."\n";else{$Oa=$b->backwardKeys($a,$Pg);echo"<table id='table' cellspacing='0' class='nowrap checkable' onclick='tableClick(event);' ondblclick='tableClick(event, true);' onkeydown='return editingKeydown(event);'>\n","<thead><tr>".(!$Vc&&$M?"":"<td><input type='checkbox' id='all-page' onclick='formCheck(this, /check/);'> <a href='".h($_GET["modify"]?remove_from_uri("modify"):$_SERVER["REQUEST_URI"]."&modify=1")."'>".lang(238)."</a>");$ve=array();$Sc=array();reset($M);$Mf=1;foreach($L[0]as$x=>$X){if($x!=$Ee){$X=$_GET["columns"][key($M)];$n=$o[$M?($X?$X["col"]:current($M)):$x];$C=($n?$b->fieldName($n,$Mf):($X["fun"]?"*":$x));if($C!=""){$Mf++;$ve[$x]=$C;$e=idf_escape($x);$gd=remove_from_uri('(order|desc)[^=]*|page').'&order%5B0%5D='.urlencode($x);$Nb="&desc%5B0%5D=1";echo'<th onmouseover="columnMouse(this);" onmouseout="columnMouse(this, \' hidden\');">','<a href="'.h($gd.($Te[0]==$e||$Te[0]==$x||(!$Te&&$yd&&$Vc[0]==$e)?$Nb:'')).'">';echo
apply_sql_function($X["fun"],$C)."</a>";echo"<span class='column hidden'>","<a href='".h($gd.$Nb)."' title='".lang(50)."' class='text'> â†“</a>";if(!$X["fun"])echo'<a href="#fieldset-search" onclick="selectSearch(\''.h(js_escape($x)).'\'); return false;" title="'.lang(47).'" class="text jsonly"> =</a>';echo"</span>";}$Sc[$x]=$X["fun"];next($M);}}$Td=array();if($_GET["modify"]){foreach($L
as$K){foreach($K
as$x=>$X)$Td[$x]=max($Td[$x],min(40,strlen(utf8_decode($X))));}}echo($Oa?"<th>".lang(239):"")."</thead>\n";if(is_ajax()){if($z%2==1&&$E%2==1)odd();ob_end_clean();}foreach($b->rowDescriptions($L,$Lc)as$ue=>$K){$Ah=unique_array($L[$ue],$v);if(!$Ah){$Ah=array();foreach($L[$ue]as$x=>$X){if(!preg_match('~^(COUNT\\((\\*|(DISTINCT )?`(?:[^`]|``)+`)\\)|(AVG|GROUP_CONCAT|MAX|MIN|SUM)\\(`(?:[^`]|``)+`\\))$~',$x))$Ah[$x]=$X;}}$Bh="";foreach($Ah
as$x=>$X){if(($w=="sql"||$w=="pgsql")&&strlen($X)>64){$x="MD5(".(strpos($x,'(')?$x:idf_escape($x)).")";$X=md5($X);}$Bh.="&".($X!==null?urlencode("where[".bracket_escape($x)."]")."=".urlencode($X):"null%5B%5D=".urlencode($x));}echo"<tr".odd().">".(!$Vc&&$M?"":"<td>".checkbox("check[]",substr($Bh,1),in_array(substr($Bh,1),(array)$_POST["check"]),"","this.form['all'].checked = false; formUncheck('all-page');").($yd||information_schema(DB)?"":" <a href='".h(ME."edit=".urlencode($a).$Bh)."'>".lang(240)."</a>"));foreach($K
as$x=>$X){if(isset($ve[$x])){$n=$o[$x];if($X!=""&&(!isset($ic[$x])||$ic[$x]!=""))$ic[$x]=(is_mail($X)?$ve[$x]:"");$_="";if(preg_match('~blob|bytea|raw|file~',$n["type"])&&$X!="")$_=ME.'download='.urlencode($a).'&field='.urlencode($x).$Bh;if(!$_&&$X!==null){foreach((array)$Lc[$x]as$p){if(count($Lc[$x])==1||end($p["source"])==$x){$_="";foreach($p["source"]as$r=>$_g)$_.=where_link($r,$p["target"][$r],$L[$ue][$_g]);$_=($p["db"]!=""?preg_replace('~([?&]db=)[^&]+~','\\1'.urlencode($p["db"]),ME):ME).'select='.urlencode($p["table"]).$_;if(count($p["source"])==1)break;}}}if($x=="COUNT(*)"){$_=ME."select=".urlencode($a);$r=0;foreach((array)$_GET["where"]as$W){if(!array_key_exists($W["col"],$Ah))$_.=where_link($r++,$W["col"],$W["val"],$W["op"]);}foreach($Ah
as$Dd=>$W)$_.=where_link($r++,$Dd,$W);}$X=select_value($X,$_,$n,$dh);$s=h("val[$Bh][".bracket_escape($x)."]");$Y=$_POST["val"][$Bh][bracket_escape($x)];$dc=!is_array($K[$x])&&is_utf8($X)&&$L[$ue][$x]==$K[$x]&&!$Sc[$x];$ch=preg_match('~text|lob~',$n["type"]);if(($_GET["modify"]&&$dc)||$Y!==null){$Yc=h($Y!==null?$Y:$K[$x]);echo"<td>".($ch?"<textarea name='$s' cols='30' rows='".(substr_count($K[$x],"\n")+1)."'>$Yc</textarea>":"<input name='$s' value='$Yc' size='$Td[$x]'>");}else{$Yd=strpos($X,"<i>...</i>");echo"<td id='$s' onclick=\"selectClick(this, event, ".($Yd?2:($ch?1:0)).($dc?"":", '".h(lang(241))."'").");\">$X";}}}if($Oa)echo"<td>";$b->backwardKeysPrint($Oa,$L[$ue]);echo"</tr>\n";}if(is_ajax())exit;echo"</table>\n";}if(($L||$E)&&!is_ajax()){$sc=true;if($_GET["page"]!="last"){if(!+$z)$Oc=count($L);elseif($w!="sql"||!$yd){$Oc=($yd?false:found_rows($R,$Z));if($Oc<max(1e4,2*($E+1)*$z))$Oc=reset(slow_query(count_rows($a,$Z,$yd,$Vc)));else$sc=false;}}if(+$z&&($Oc===false||$Oc>$z||$E)){echo"<p class='pages'>";$ee=($Oc===false?$E+(count($L)>=$z?2:1):floor(($Oc-1)/$z));if($w!="simpledb"){echo'<a href="'.h(remove_from_uri("page"))."\" onclick=\"pageClick(this.href, +prompt('".lang(242)."', '".($E+1)."'), event); return false;\">".lang(242)."</a>:",pagination(0,$E).($E>5?" ...":"");for($r=max(1,$E-4);$r<min($ee,$E+5);$r++)echo
pagination($r,$E);if($ee>0){echo($E+5<$ee?" ...":""),($sc&&$Oc!==false?pagination($ee,$E):" <a href='".h(remove_from_uri("page")."&page=last")."' title='~$ee'>".lang(243)."</a>");}echo(($Oc===false?count($L)+1:$Oc-$E*$z)>$z?' <a href="'.h(remove_from_uri("page")."&page=".($E+1)).'" onclick="return !selectLoadMore(this, '.(+$z).', \''.lang(244).'...\');" class="loadmore">'.lang(245).'</a>':'');}else{echo
lang(242).":",pagination(0,$E).($E>1?" ...":""),($E?pagination($E,$E):""),($ee>$E?pagination($E+1,$E).($ee>$E+1?" ...":""):"");}}echo"<p class='count'>\n",($Oc!==false?"(".($sc?"":"~ ").lang(136,$Oc).") ":"");$Sb=($sc?"":"~ ").$Oc;echo
checkbox("all",1,0,lang(246),"var checked = formChecked(this, /check/); selectCount('selected', this.checked ? '$Sb' : checked); selectCount('selected2', this.checked || !checked ? '$Sb' : checked);")."\n";if($b->selectCommandPrint()){echo'<fieldset',($_GET["modify"]?'':' class="jsonly"'),'><legend>',lang(238),'</legend><div>
<input type="submit" value="',lang(14),'"',($_GET["modify"]?'':' title="'.lang(234).'"'),'>
</div></fieldset>
<fieldset><legend>',lang(113),' <span id="selected"></span></legend><div>
<input type="submit" name="edit" value="',lang(10),'">
<input type="submit" name="clone" value="',lang(230),'">
<input type="submit" name="delete" value="',lang(18),'"',confirm(),'>
</div></fieldset>
';}$Mc=$b->dumpFormat();foreach((array)$_GET["columns"]as$e){if($e["fun"]){unset($Mc['sql']);break;}}if($Mc){print_fieldset("export",lang(128)." <span id='selected2'></span>");$df=$b->dumpOutput();echo($df?adminer_html_select("output",$df,$xa["output"])." ":""),adminer_html_select("format",$Mc,$xa["format"])," <input type='submit' name='export' value='".lang(128)."'>\n","</div></fieldset>\n";}echo(!$Vc&&$M?"":"<script type='text/javascript'>tableCheck();</script>\n");}if($b->selectImportPrint()){print_fieldset("import",lang(64),!$L);echo"<input type='file' name='csv_file'> ",adminer_html_select("separator",array("csv"=>"CSV,","csv;"=>"CSV;","tsv"=>"TSV"),$xa["format"],1);echo" <input type='submit' name='import' value='".lang(64)."'>","</div></fieldset>\n";}$b->selectEmailPrint(array_filter($ic,'strlen'),$f);echo"<p><input type='hidden' name='token' value='$T'></p>\n","</form>\n";}}if(is_ajax()){ob_end_clean();exit;}}elseif(isset($_GET["variables"])){$Eg=isset($_GET["status"]);page_header($Eg?lang(105):lang(104));$Ph=($Eg?show_status():show_variables());if(!$Ph)echo"<p class='message'>".lang(12)."\n";else{echo"<table cellspacing='0'>\n";foreach($Ph
as$x=>$X){echo"<tr>","<th><code class='jush-".$w.($Eg?"status":"set")."'>".h($x)."</code>","<td>".nbsp($X);}echo"</table>\n";}}elseif(isset($_GET["script"])){header("Content-Type: text/javascript; charset=utf-8");if($_GET["script"]=="db"){$Mg=array("Data_length"=>0,"Index_length"=>0,"Data_free"=>0);foreach(table_status()as$C=>$R){$s=js_escape($C);json_row("Comment-$s",nbsp($R["Comment"]));if(!is_view($R)){foreach(array("Engine","Collation")as$x)json_row("$x-$s",nbsp($R[$x]));foreach($Mg+array("Auto_increment"=>0,"Rows"=>0)as$x=>$X){if($R[$x]!=""){$X=format_number($R[$x]);json_row("$x-$s",($x=="Rows"&&$X&&$R["Engine"]==($Bg=="pgsql"?"table":"InnoDB")?"~ $X":$X));if(isset($Mg[$x]))$Mg[$x]+=($R["Engine"]!="InnoDB"||$x!="Data_free"?$R[$x]:0);}elseif(array_key_exists($x,$R))json_row("$x-$s");}}}foreach($Mg
as$x=>$X)json_row("sum-$x",format_number($X));json_row("");}elseif($_GET["script"]=="kill")$h->query("KILL ".(+$_POST["kill"]));else{foreach(count_tables($b->databases())as$k=>$X){json_row("tables-$k",$X);json_row("size-$k",db_size($k));}json_row("");}exit;}else{$Vg=array_merge((array)$_POST["tables"],(array)$_POST["views"]);if($Vg&&!$m&&!$_POST["search"]){$I=true;$je="";if($w=="sql"&&count($_POST["tables"])>1&&($_POST["drop"]||$_POST["truncate"]||$_POST["copy"]))queries("SET foreign_key_checks = 0");if($_POST["truncate"]){if($_POST["tables"])$I=truncate_tables($_POST["tables"]);$je=lang(247);}elseif($_POST["move"]){$I=move_tables((array)$_POST["tables"],(array)$_POST["views"],$_POST["target"]);$je=lang(248);}elseif($_POST["copy"]){$I=copy_tables((array)$_POST["tables"],(array)$_POST["views"],$_POST["target"]);$je=lang(249);}elseif($_POST["drop"]){if($_POST["views"])$I=drop_views($_POST["views"]);if($I&&$_POST["tables"])$I=drop_tables($_POST["tables"]);$je=lang(250);}elseif($w!="sql"){$I=($w=="sqlite"?queries("VACUUM"):apply_queries("VACUUM".($_POST["optimize"]?"":" ANALYZE"),$_POST["tables"]));$je=lang(251);}elseif(!$_POST["tables"])$je=lang(9);elseif($I=queries(($_POST["optimize"]?"OPTIMIZE":($_POST["check"]?"CHECK":($_POST["repair"]?"REPAIR":"ANALYZE")))." TABLE ".implode(", ",array_map('idf_escape',$_POST["tables"])))){while($K=$I->fetch_assoc())$je.="<b>".h($K["Table"])."</b>: ".h($K["Msg_text"])."<br>";}queries_redirect(substr(ME,0,-1),$je,$I);}page_header(($_GET["ns"]==""?lang(35).": ".h(DB):lang(68).": ".h($_GET["ns"])),$m,true);if($b->homepage()){if($_GET["ns"]!==""){echo"<h3 id='tables-views'>".lang(252)."</h3>\n";$Ug=tables_list();if(!$Ug)echo"<p class='message'>".lang(9)."\n";else{echo"<form action='' method='post'>\n";if(support("table")){echo"<fieldset><legend>".lang(253)." <span id='selected2'></span></legend><div>","<input type='search' name='query' value='".h($_POST["query"])."'> <input type='submit' name='search' value='".lang(47)."'>\n","</div></fieldset>\n";if($_POST["search"]&&$_POST["query"]!="")search_tables();}echo"<table cellspacing='0' class='nowrap checkable' onclick='tableClick(event);' ondblclick='tableClick(event, true);'>\n",'<thead><tr class="wrap"><td><input id="check-all" type="checkbox" onclick="formCheck(this, /^(tables|views)\[/);">','<th>'.lang(117),'<td>'.lang(254),'<td>'.lang(109),'<td>'.lang(255),'<td>'.lang(256),'<td>'.lang(257),'<td>'.lang(56),'<td>'.lang(258),(support("comment")?'<td>'.lang(94):''),"</thead>\n";$S=0;foreach($Ug
as$C=>$U){$Sh=($U!==null&&!preg_match('~table~i',$U));echo'<tr'.odd().'><td>'.checkbox(($Sh?"views[]":"tables[]"),$C,in_array($C,$Vg,true),"","formUncheck('check-all');"),'<th>'.(support("table")||support("indexes")?'<a href="'.h(ME).'table='.urlencode($C).'" title="'.lang(39).'">'.h($C).'</a>':h($C));if($Sh){echo'<td colspan="6"><a href="'.h(ME)."view=".urlencode($C).'" title="'.lang(40).'">'.lang(116).'</a>','<td align="right"><a href="'.h(ME)."select=".urlencode($C).'" title="'.lang(38).'">?</a>';}else{foreach(array("Engine"=>array(),"Collation"=>array(),"Data_length"=>array("create",lang(41)),"Index_length"=>array("indexes",lang(120)),"Data_free"=>array("edit",lang(42)),"Auto_increment"=>array("auto_increment=1&create",lang(41)),"Rows"=>array("select",lang(38)),)as$x=>$_){$s=" id='$x-".h($C)."'";echo($_?"<td align='right'>".(support("table")||$x=="Rows"||(support("indexes")&&$x!="Data_length")?"<a href='".h(ME."$_[0]=").urlencode($C)."'$s title='$_[1]'>?</a>":"<span$s>?</span>"):"<td id='$x-".h($C)."'>&nbsp;");}$S++;}echo(support("comment")?"<td id='Comment-".h($C)."'>&nbsp;":"");}echo"<tr><td>&nbsp;<th>".lang(231,count($Ug)),"<td>".nbsp($w=="sql"?$h->result("SELECT @@storage_engine"):""),"<td>".nbsp(db_collation(DB,collations()));foreach(array("Data_length","Index_length","Data_free")as$x)echo"<td align='right' id='sum-$x'>&nbsp;";echo"</table>\n";if(!information_schema(DB)){$Mh="<input type='submit' value='".lang(259)."'".on_help("'VACUUM'")."> ";$Pe="<input type='submit' name='optimize' value='".lang(260)."'".on_help($w=="sql"?"'OPTIMIZE TABLE'":"'VACUUM OPTIMIZE'")."> ";echo"<fieldset><legend>".lang(113)." <span id='selected'></span></legend><div>".($w=="sqlite"?$Mh:($w=="pgsql"?$Mh.$Pe:($w=="sql"?"<input type='submit' value='".lang(261)."'".on_help("'ANALYZE TABLE'")."> ".$Pe."<input type='submit' name='check' value='".lang(262)."'".on_help("'CHECK TABLE'")."> "."<input type='submit' name='repair' value='".lang(263)."'".on_help("'REPAIR TABLE'")."> ":"")))."<input type='submit' name='truncate' value='".lang(264)."'".confirm().on_help($w=="sqlite"?"'DELETE'":"'TRUNCATE".($w=="pgsql"?"'":" TABLE'"))."> "."<input type='submit' name='drop' value='".lang(114)."'".confirm().on_help("'DROP TABLE'").">\n";$j=(support("scheme")?$b->schemas():$b->databases());if(count($j)!=1&&$w!="sqlite"){$k=(isset($_POST["target"])?$_POST["target"]:(support("scheme")?$_GET["ns"]:DB));echo"<p>".lang(265).": ",($j?adminer_html_select("target",$j,$k):'<input name="target" value="'.h($k).'" autocapitalize="off">')," <input type='submit' name='move' value='".lang(266)."'>",(support("copy")?" <input type='submit' name='copy' value='".lang(267)."'>":""),"\n";}echo"<input type='hidden' name='all' value='' onclick=\"selectCount('selected', formChecked(this, /^(tables|views)\[/));".(support("table")?" selectCount('selected2', formChecked(this, /^tables\[/) || $S);":"")."\">\n";echo"<input type='hidden' name='token' value='$T'>\n","</div></fieldset>\n";}echo"</form>\n","<script type='text/javascript'>tableCheck();</script>\n";}echo'<p class="links"><a href="'.h(ME).'create=">'.lang(66)."</a>\n",(support("view")?'<a href="'.h(ME).'view=">'.lang(188)."</a>\n":"");if(support("routine")){echo"<h3 id='routines'>".lang(131)."</h3>\n";$gg=routines();if($gg){echo"<table cellspacing='0'>\n",'<thead><tr><th>'.lang(166).'<td>'.lang(90).'<td>'.lang(205)."<td>&nbsp;</thead>\n";odd('');foreach($gg
as$K){echo'<tr'.odd().'>','<th><a href="'.h(ME).($K["ROUTINE_TYPE"]!="PROCEDURE"?'callf=':'call=').urlencode($K["ROUTINE_NAME"]).'">'.h($K["ROUTINE_NAME"]).'</a>','<td>'.h($K["ROUTINE_TYPE"]),'<td>'.h($K["DTD_IDENTIFIER"]),'<td><a href="'.h(ME).($K["ROUTINE_TYPE"]!="PROCEDURE"?'function=':'procedure=').urlencode($K["ROUTINE_NAME"]).'">'.lang(123)."</a>";}echo"</table>\n";}echo'<p class="links">'.(support("procedure")?'<a href="'.h(ME).'procedure=">'.lang(204).'</a>':'').'<a href="'.h(ME).'function=">'.lang(203)."</a>\n";}if(support("sequence")){echo"<h3 id='sequences'>".lang(268)."</h3>\n";$sg=get_vals("SELECT sequence_name FROM information_schema.sequences WHERE sequence_schema = current_schema()");if($sg){echo"<table cellspacing='0'>\n","<thead><tr><th>".lang(166)."</thead>\n";odd('');foreach($sg
as$X)echo"<tr".odd()."><th><a href='".h(ME)."sequence=".urlencode($X)."'>".h($X)."</a>\n";echo"</table>\n";}echo"<p class='links'><a href='".h(ME)."sequence='>".lang(210)."</a>\n";}if(support("type")){echo"<h3 id='user-types'>".lang(23)."</h3>\n";$Kh=types();if($Kh){echo"<table cellspacing='0'>\n","<thead><tr><th>".lang(166)."</thead>\n";odd('');foreach($Kh
as$X)echo"<tr".odd()."><th><a href='".h(ME)."type=".urlencode($X)."'>".h($X)."</a>\n";echo"</table>\n";}echo"<p class='links'><a href='".h(ME)."type='>".lang(214)."</a>\n";}if(support("event")){echo"<h3 id='events'>".lang(132)."</h3>\n";$L=get_rows("SHOW EVENTS");if($L){echo"<table cellspacing='0'>\n","<thead><tr><th>".lang(166)."<td>".lang(269)."<td>".lang(194)."<td>".lang(195)."<td></thead>\n";foreach($L
as$K){echo"<tr>","<th>".h($K["Name"]),"<td>".($K["Execute at"]?lang(270)."<td>".$K["Execute at"]:lang(196)." ".$K["Interval value"]." ".$K["Interval field"]."<td>$K[Starts]"),"<td>$K[Ends]",'<td><a href="'.h(ME).'event='.urlencode($K["Name"]).'">'.lang(123).'</a>';}echo"</table>\n";$qc=$h->result("SELECT @@event_scheduler");if($qc&&$qc!="ON")echo"<p class='error'><code class='jush-sqlset'>event_scheduler</code>: ".h($qc)."\n";}echo'<p class="links"><a href="'.h(ME).'event=">'.lang(193)."</a>\n";}if($Ug)echo"<script type='text/javascript'>ajaxSetHtml('".js_escape(ME)."script=db');</script>\n";}}}page_footer();