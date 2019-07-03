<?php

//includes
	include "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permission
	if (permission_exists('adminer')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//notice
	//FusionPBX using Adminer under
	//the Apache License 2.0 License.

//hide notices and warnings
	//ini_set('display_errors', '0');
	//error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING);

/** Adminer - Compact database management
* @link https://www.adminer.org/
* @author Jakub Vrana, https://www.vrana.cz/
* @copyright 2007 Jakub Vrana
* @license https://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
* @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License, version 2 (one or other)
* @version 4.7.1
*/error_reporting(6135);$Tc=!preg_match('~^(unsafe_raw)?$~',ini_get("filter.default"));if($Tc||ini_get("filter.default_flags")){foreach(array('_GET','_POST','_COOKIE','_SERVER')as$X){$Ei=filter_input_array(constant("INPUT$X"),FILTER_UNSAFE_RAW);if($Ei)$$X=$Ei;}}if(function_exists("mb_internal_encoding"))mb_internal_encoding("8bit");function
connection(){global$g;return$g;}function
adminer(){global$b;return$b;}function
adminer_version(){global$ia;return$ia;}function
idf_unescape($v){$le=substr($v,-1);return
str_replace($le.$le,$le,substr($v,1,-1));}function
escape_string($X){return
substr(q($X),1,-1);}function
number($X){return
preg_replace('~[^0-9]+~','',$X);}function
number_type(){return'((?<!o)int(?!er)|numeric|real|float|double|decimal|money)';}function
remove_slashes($ng,$Tc=false){if(get_magic_quotes_gpc()){while(list($z,$X)=each($ng)){foreach($X
as$be=>$W){unset($ng[$z][$be]);if(is_array($W)){$ng[$z][stripslashes($be)]=$W;$ng[]=&$ng[$z][stripslashes($be)];}else$ng[$z][stripslashes($be)]=($Tc?$W:stripslashes($W));}}}}function
bracket_escape($v,$Na=false){static$qi=array(':'=>':1',']'=>':2','['=>':3','"'=>':4');return
strtr($v,($Na?array_flip($qi):$qi));}function
min_version($Vi,$_e="",$h=null){global$g;if(!$h)$h=$g;$ih=$h->server_info;if($_e&&preg_match('~([\d.]+)-MariaDB~',$ih,$B)){$ih=$B[1];$Vi=$_e;}return(version_compare($ih,$Vi)>=0);}function
charset($g){return(min_adminer_version("5.5.3",0,$g)?"utf8mb4":"utf8");}function
script($th,$pi="\n"){return"<script".nonce().">$th</script>$pi";}function
script_src($Ji){return"<script src='".h($Ji)."'".nonce()."></script>\n";}function
nonce(){return' nonce="'.get_nonce().'"';}function
target_blank(){return' target="_blank" rel="noreferrer noopener"';}function
h($P){return
str_replace("\0","&#0;",htmlspecialchars($P,ENT_QUOTES,'utf-8'));}function
nl_br($P){return
str_replace("\n","<br>",$P);}function
checkbox($C,$Y,$eb,$ie="",$pf="",$jb="",$je=""){$I="<input type='checkbox' name='$C' value='".h($Y)."'".($eb?" checked":"").($je?" aria-labelledby='$je'":"").">".($pf?script("qsl('input').onclick = function () { $pf };",""):"");return($ie!=""||$jb?"<label".($jb?" class='$jb'":"").">$I".h($ie)."</label>":$I);}function
optionlist($vf,$ch=null,$Ni=false){$I="";foreach($vf
as$be=>$W){$wf=array($be=>$W);if(is_array($W)){$I.='<optgroup label="'.h($be).'">';$wf=$W;}foreach($wf
as$z=>$X)$I.='<option'.($Ni||is_string($z)?' value="'.h($z).'"':'').(($Ni||is_string($z)?(string)$z:$X)===$ch?' selected':'').'>'.h($X);if(is_array($W))$I.='</optgroup>';}return$I;}function
adminer_html_select($C,$vf,$Y="",$of=true,$je=""){if($of)return"<select name='".h($C)."'".($je?" aria-labelledby='$je'":"").">".optionlist($vf,$Y)."</select>".(is_string($of)?script("qsl('select').onchange = function () { $of };",""):"");$I="";foreach($vf
as$z=>$X)$I.="<label><input type='radio' name='".h($C)."' value='".h($z)."'".($z==$Y?" checked":"").">".h($X)."</label>";return$I;}function
select_input($Ja,$vf,$Y="",$of="",$Zf=""){$Uh=($vf?"select":"input");return"<$Uh$Ja".($vf?"><option value=''>$Zf".optionlist($vf,$Y,true)."</select>":" size='10' value='".h($Y)."' placeholder='$Zf'>").($of?script("qsl('$Uh').onchange = $of;",""):"");}function
confirm($Je="",$dh="qsl('input')"){return
script("$dh.onclick = function () { return confirm('".($Je?js_escape($Je):'Are you sure?')."'); };","");}function
print_fieldset($u,$qe,$Yi=false){echo"<fieldset><legend>","<a href='#fieldset-$u'>$qe</a>",script("qsl('a').onclick = partial(toggle, 'fieldset-$u');",""),"</legend>","<div id='fieldset-$u'".($Yi?"":" class='hidden'").">\n";}function
bold($Va,$jb=""){return($Va?" class='active $jb'":($jb?" class='$jb'":""));}function
odd($I=' class="odd"'){static$t=0;if(!$I)$t=-1;return($t++%2?$I:'');}function
js_escape($P){return
addcslashes($P,"\r\n'\\/");}function
json_row($z,$X=null){static$Uc=true;if($Uc)echo"{";if($z!=""){echo($Uc?"":",")."\n\t\"".addcslashes($z,"\r\n\t\"\\/").'": '.($X!==null?'"'.addcslashes($X,"\r\n\"\\/").'"':'null');$Uc=false;}else{echo"\n}\n";$Uc=true;}}function
ini_bool($Od){$X=ini_get($Od);return(preg_match('~^(on|true|yes)$~i',$X)||(int)$X);}function
sid(){static$I;if($I===null)$I=(SID&&!($_COOKIE&&ini_bool("session.use_cookies")));return$I;}function
set_password($Ui,$N,$V,$F){$_SESSION["pwds"][$Ui][$N][$V]=($_COOKIE["adminer_key"]&&is_string($F)?array(encrypt_string($F,$_COOKIE["adminer_key"])):$F);}function
get_password(){$I=get_session("pwds");if(is_array($I))$I=($_COOKIE["adminer_key"]?decrypt_string($I[0],$_COOKIE["adminer_key"]):false);return$I;}function
q($P){global$g;return$g->quote($P);}function
get_vals($G,$e=0){global$g;$I=array();$H=$g->query($G);if(is_object($H)){while($J=$H->fetch_row())$I[]=$J[$e];}return$I;}function
get_key_vals($G,$h=null,$lh=true){global$g;if(!is_object($h))$h=$g;$I=array();$H=$h->query($G);if(is_object($H)){while($J=$H->fetch_row()){if($lh)$I[$J[0]]=$J[1];else$I[]=$J[0];}}return$I;}function
get_rows($G,$h=null,$o="<p class='error'>"){global$g;$vb=(is_object($h)?$h:$g);$I=array();$H=$vb->query($G);if(is_object($H)){while($J=$H->fetch_assoc())$I[]=$J;}elseif(!$H&&!is_object($h)&&$o&&defined("PAGE_HEADER"))echo$o.error()."\n";return$I;}function
unique_array($J,$x){foreach($x
as$w){if(preg_match("~PRIMARY|UNIQUE~",$w["type"])){$I=array();foreach($w["columns"]as$z){if(!isset($J[$z]))continue
2;$I[$z]=$J[$z];}return$I;}}}function
escape_key($z){if(preg_match('(^([\w(]+)('.str_replace("_",".*",preg_quote(idf_escape("_"))).')([ \w)]+)$)',$z,$B))return$B[1].idf_escape(idf_unescape($B[2])).$B[3];return
idf_escape($z);}function
where($Z,$q=array()){global$g,$y;$I=array();foreach((array)$Z["where"]as$z=>$X){$z=bracket_escape($z,1);$e=escape_key($z);$I[]=$e.($y=="sql"&&preg_match('~^[0-9]*\.[0-9]*$~',$X)?" LIKE ".q(addcslashes($X,"%_\\")):($y=="mssql"?" LIKE ".q(preg_replace('~[_%[]~','[\0]',$X)):" = ".unconvert_field($q[$z],q($X))));if($y=="sql"&&preg_match('~char|text~',$q[$z]["type"])&&preg_match("~[^ -@]~",$X))$I[]="$e = ".q($X)." COLLATE ".charset($g)."_bin";}foreach((array)$Z["null"]as$z)$I[]=escape_key($z)." IS NULL";return
implode(" AND ",$I);}function
where_check($X,$q=array()){parse_str($X,$cb);remove_slashes(array(&$cb));return
where($cb,$q);}function
where_link($t,$e,$Y,$rf="="){return"&where%5B$t%5D%5Bcol%5D=".urlencode($e)."&where%5B$t%5D%5Bop%5D=".urlencode(($Y!==null?$rf:"IS NULL"))."&where%5B$t%5D%5Bval%5D=".urlencode($Y);}function
convert_fields($f,$q,$L=array()){$I="";foreach($f
as$z=>$X){if($L&&!in_array(idf_escape($z),$L))continue;$Ga=convert_field($q[$z]);if($Ga)$I.=", $Ga AS ".idf_escape($z);}return$I;}function
cookie($C,$Y,$te=2592000){global$ba;return
header("Set-Cookie: $C=".urlencode($Y).($te?"; expires=".gmdate("D, d M Y H:i:s",time()+$te)." GMT":"")."; path=".preg_replace('~\?.*~','',$_SERVER["REQUEST_URI"]).($ba?"; secure":"")."; HttpOnly; SameSite=lax",false);}function
restart_session(){if(!ini_bool("session.use_cookies"))session_start();}function
stop_session($Zc=false){if(!ini_bool("session.use_cookies")||($Zc&&@ini_set("session.use_cookies",false)!==false))session_write_close();}function&get_session($z){return$_SESSION[$z][DRIVER][SERVER][$_GET["username"]];}function
set_session($z,$X){$_SESSION[$z][DRIVER][SERVER][$_GET["username"]]=$X;}function
auth_url($Ui,$N,$V,$m=null){global$cc;preg_match('~([^?]*)\??(.*)~',remove_from_uri(implode("|",array_keys($cc))."|username|".($m!==null?"db|":"").session_name()),$B);return"$B[1]?".(sid()?SID."&":"").($Ui!="server"||$N!=""?urlencode($Ui)."=".urlencode($N)."&":"")."username=".urlencode($V).($m!=""?"&db=".urlencode($m):"").($B[2]?"&$B[2]":"");}function
is_ajax(){return($_SERVER["HTTP_X_REQUESTED_WITH"]=="XMLHttpRequest");}function
redirect($ve,$Je=null){if($Je!==null){restart_session();$_SESSION["messages"][preg_replace('~^[^?]*~','',($ve!==null?$ve:$_SERVER["REQUEST_URI"]))][]=$Je;}if($ve!==null){if($ve=="")$ve=".";header("Location: $ve");exit;}}function
query_redirect($G,$ve,$Je,$zg=true,$Ac=true,$Lc=false,$ci=""){global$g,$o,$b;if($Ac){$Ah=microtime(true);$Lc=!$g->query($G);$ci=format_time($Ah);}$wh="";if($G)$wh=$b->messageQuery($G,$ci,$Lc);if($Lc){$o=error().$wh.script("messagesPrint();");return
false;}if($zg)redirect($ve,$Je.$wh);return
true;}function
queries($G){global$g;static$sg=array();static$Ah;if(!$Ah)$Ah=microtime(true);if($G===null)return
array(implode("\n",$sg),format_time($Ah));$sg[]=(preg_match('~;$~',$G)?"DELIMITER ;;\n$G;\nDELIMITER ":$G).";";return$g->query($G);}function
apply_queries($G,$S,$xc='table'){foreach($S
as$Q){if(!queries("$G ".$xc($Q)))return
false;}return
true;}function
queries_redirect($ve,$Je,$zg){list($sg,$ci)=queries(null);return
query_redirect($sg,$ve,$Je,$zg,false,!$zg,$ci);}function
format_time($Ah){return
sprintf('%.3f s',max(0,microtime(true)-$Ah));}function
remove_from_uri($Kf=""){return
substr(preg_replace("~(?<=[?&])($Kf".(SID?"":"|".session_name()).")=[^&]*&~",'',"$_SERVER[REQUEST_URI]&"),0,-1);}function
pagination($E,$Hb){return" ".($E==$Hb?$E+1:'<a href="'.h(remove_from_uri("page").($E?"&page=$E".($_GET["next"]?"&next=".urlencode($_GET["next"]):""):"")).'">'.($E+1)."</a>");}function
get_file($z,$Pb=false){$Rc=$_FILES[$z];if(!$Rc)return
null;foreach($Rc
as$z=>$X)$Rc[$z]=(array)$X;$I='';foreach($Rc["error"]as$z=>$o){if($o)return$o;$C=$Rc["name"][$z];$ki=$Rc["tmp_name"][$z];$yb=file_get_contents($Pb&&preg_match('~\.gz$~',$C)?"compress.zlib://$ki":$ki);if($Pb){$Ah=substr($yb,0,3);if(function_exists("iconv")&&preg_match("~^\xFE\xFF|^\xFF\xFE~",$Ah,$Eg))$yb=iconv("utf-16","utf-8",$yb);elseif($Ah=="\xEF\xBB\xBF")$yb=substr($yb,3);$I.=$yb."\n\n";}else$I.=$yb;}return$I;}function
upload_error($o){$Ge=($o==UPLOAD_ERR_INI_SIZE?ini_get("upload_max_filesize"):0);return($o?'Unable to upload a file.'.($Ge?" ".sprintf('Maximum allowed file size is %sB.',$Ge):""):'File does not exist.');}function
repeat_pattern($Xf,$re){return
str_repeat("$Xf{0,65535}",$re/65535)."$Xf{0,".($re%65535)."}";}function
is_utf8($X){return(preg_match('~~u',$X)&&!preg_match('~[\0-\x8\xB\xC\xE-\x1F]~',$X));}function
shorten_utf8($P,$re=80,$Ih=""){if(!preg_match("(^(".repeat_pattern("[\t\r\n -\x{10FFFF}]",$re).")($)?)u",$P,$B))preg_match("(^(".repeat_pattern("[\t\r\n -~]",$re).")($)?)",$P,$B);return
h($B[1]).$Ih.(isset($B[2])?"":"<i>‚Ä¶</i>");}function
format_number($X){return
strtr(number_format($X,0,".",','),preg_split('~~u','0123456789',-1,PREG_SPLIT_NO_EMPTY));}function
friendly_url($X){return
preg_replace('~[^a-z0-9_]~i','-',$X);}function
hidden_fields($ng,$Dd=array()){$I=false;while(list($z,$X)=each($ng)){if(!in_array($z,$Dd)){if(is_array($X)){foreach($X
as$be=>$W)$ng[$z."[$be]"]=$W;}else{$I=true;echo'<input type="hidden" name="'.h($z).'" value="'.h($X).'">';}}}return$I;}function
hidden_fields_get(){echo(sid()?'<input type="hidden" name="'.session_name().'" value="'.h(session_id()).'">':''),(SERVER!==null?'<input type="hidden" name="'.DRIVER.'" value="'.h(SERVER).'">':""),'<input type="hidden" name="username" value="'.h($_GET["username"]).'">';}function
table_status1($Q,$Mc=false){$I=table_status($Q,$Mc);return($I?$I:array("Name"=>$Q));}function
column_foreign_keys($Q){global$b;$I=array();foreach($b->foreignKeys($Q)as$r){foreach($r["source"]as$X)$I[$X][]=$r;}return$I;}function
enum_input($T,$Ja,$p,$Y,$rc=null){global$b;preg_match_all("~'((?:[^']|'')*)'~",$p["length"],$Be);$I=($rc!==null?"<label><input type='$T'$Ja value='$rc'".((is_array($Y)?in_array($rc,$Y):$Y===0)?" checked":"")."><i>".'empty'."</i></label>":"");foreach($Be[1]as$t=>$X){$X=stripcslashes(str_replace("''","'",$X));$eb=(is_int($Y)?$Y==$t+1:(is_array($Y)?in_array($t+1,$Y):$Y===$X));$I.=" <label><input type='$T'$Ja value='".($t+1)."'".($eb?' checked':'').'>'.h($b->editVal($X,$p)).'</label>';}return$I;}function
input($p,$Y,$s){global$U,$b,$y;$C=h(bracket_escape($p["field"]));echo"<td class='function'>";if(is_array($Y)&&!$s){$Ea=array($Y);if(version_compare(PHP_VERSION,5.4)>=0)$Ea[]=JSON_PRETTY_PRINT;$Y=call_user_func_array('json_encode',$Ea);$s="json";}$Ig=($y=="mssql"&&$p["auto_increment"]);if($Ig&&!$_POST["save"])$s=null;$id=(isset($_GET["select"])||$Ig?array("orig"=>'original'):array())+$b->editFunctions($p);$Ja=" name='fields[$C]'";if($p["type"]=="enum")echo
h($id[""])."<td>".$b->editInput($_GET["edit"],$p,$Ja,$Y);else{$sd=(in_array($s,$id)||isset($id[$s]));echo(count($id)>1?"<select name='function[$C]'>".optionlist($id,$s===null||$sd?$s:"")."</select>".on_help("getTarget(event).value.replace(/^SQL\$/, '')",1).script("qsl('select').onchange = functionChange;",""):h(reset($id))).'<td>';$Qd=$b->editInput($_GET["edit"],$p,$Ja,$Y);if($Qd!="")echo$Qd;elseif(preg_match('~bool~',$p["type"]))echo"<input type='hidden'$Ja value='0'>"."<input type='checkbox'".(preg_match('~^(1|t|true|y|yes|on)$~i',$Y)?" checked='checked'":"")."$Ja value='1'>";elseif($p["type"]=="set"){preg_match_all("~'((?:[^']|'')*)'~",$p["length"],$Be);foreach($Be[1]as$t=>$X){$X=stripcslashes(str_replace("''","'",$X));$eb=(is_int($Y)?($Y>>$t)&1:in_array($X,explode(",",$Y),true));echo" <label><input type='checkbox' name='fields[$C][$t]' value='".(1<<$t)."'".($eb?' checked':'').">".h($b->editVal($X,$p)).'</label>';}}elseif(preg_match('~blob|bytea|raw|file~',$p["type"])&&ini_bool("file_uploads"))echo"<input type='file' name='fields-$C'>";elseif(($ai=preg_match('~text|lob~',$p["type"]))||preg_match("~\n~",$Y)){if($ai&&$y!="sqlite")$Ja.=" cols='50' rows='12'";else{$K=min(12,substr_count($Y,"\n")+1);$Ja.=" cols='30' rows='$K'".($K==1?" style='height: 1.2em;'":"");}echo"<textarea$Ja>".h($Y).'</textarea>';}elseif($s=="json"||preg_match('~^jsonb?$~',$p["type"]))echo"<textarea$Ja cols='50' rows='12' class='jush-js'>".h($Y).'</textarea>';else{$Ie=(!preg_match('~int~',$p["type"])&&preg_match('~^(\d+)(,(\d+))?$~',$p["length"],$B)?((preg_match("~binary~",$p["type"])?2:1)*$B[1]+($B[3]?1:0)+($B[2]&&!$p["unsigned"]?1:0)):($U[$p["type"]]?$U[$p["type"]]+($p["unsigned"]?0:1):0));if($y=='sql'&&min_version(5.6)&&preg_match('~time~',$p["type"]))$Ie+=7;echo"<input".((!$sd||$s==="")&&preg_match('~(?<!o)int(?!er)~',$p["type"])&&!preg_match('~\[\]~',$p["full_type"])?" type='number'":"")." value='".h($Y)."'".($Ie?" data-maxlength='$Ie'":"").(preg_match('~char|binary~',$p["type"])&&$Ie>20?" size='40'":"")."$Ja>";}echo$b->editHint($_GET["edit"],$p,$Y);$Uc=0;foreach($id
as$z=>$X){if($z===""||!$X)break;$Uc++;}if($Uc)echo
script("mixin(qsl('td'), {onchange: partial(skipOriginal, $Uc), oninput: function () { this.onchange(); }});");}}function
process_input($p){global$b,$n;$v=bracket_escape($p["field"]);$s=$_POST["function"][$v];$Y=$_POST["fields"][$v];if($p["type"]=="enum"){if($Y==-1)return
false;if($Y=="")return"NULL";return+$Y;}if($p["auto_increment"]&&$Y=="")return
null;if($s=="orig")return(preg_match('~^CURRENT_TIMESTAMP~i',$p["on_update"])?idf_escape($p["field"]):false);if($s=="NULL")return"NULL";if($p["type"]=="set")return
array_sum((array)$Y);if($s=="json"){$s="";$Y=json_decode($Y,true);if(!is_array($Y))return
false;return$Y;}if(preg_match('~blob|bytea|raw|file~',$p["type"])&&ini_bool("file_uploads")){$Rc=get_file("fields-$v");if(!is_string($Rc))return
false;return$n->quoteBinary($Rc);}return$b->processInput($p,$Y,$s);}function
fields_from_edit(){global$n;$I=array();foreach((array)$_POST["field_keys"]as$z=>$X){if($X!=""){$X=bracket_escape($X);$_POST["function"][$X]=$_POST["field_funs"][$z];$_POST["fields"][$X]=$_POST["field_vals"][$z];}}foreach((array)$_POST["fields"]as$z=>$X){$C=bracket_escape($z,1);$I[$C]=array("field"=>$C,"privileges"=>array("insert"=>1,"update"=>1),"null"=>1,"auto_increment"=>($z==$n->primary),);}return$I;}function
search_tables(){global$b,$g;$_GET["where"][0]["val"]=$_POST["query"];$fh="<ul>\n";foreach(table_status('',true)as$Q=>$R){$C=$b->tableName($R);if(isset($R["Engine"])&&$C!=""&&(!$_POST["tables"]||in_array($Q,$_POST["tables"]))){$H=$g->query("SELECT".limit("1 FROM ".table($Q)," WHERE ".implode(" AND ",$b->selectSearchProcess(fields($Q),array())),1));if(!$H||$H->fetch_row()){$jg="<a href='".h(ME."select=".urlencode($Q)."&where[0][op]=".urlencode($_GET["where"][0]["op"])."&where[0][val]=".urlencode($_GET["where"][0]["val"]))."'>$C</a>";echo"$fh<li>".($H?$jg:"<p class='error'>$jg: ".error())."\n";$fh="";}}}echo($fh?"<p class='message'>".'No tables.':"</ul>")."\n";}function
dump_headers($Ad,$Se=false){global$b;$I=$b->dumpHeaders($Ad,$Se);$Hf=$_POST["output"];if($Hf!="text")header("Content-Disposition: attachment; filename=".$b->dumpFilename($Ad).".$I".($Hf!="file"&&!preg_match('~[^0-9a-z]~',$Hf)?".$Hf":""));session_write_close();ob_flush();flush();return$I;}function
dump_csv($J){foreach($J
as$z=>$X){if(preg_match("~[\"\n,;\t]~",$X)||$X==="")$J[$z]='"'.str_replace('"','""',$X).'"';}echo
implode(($_POST["format"]=="csv"?",":($_POST["format"]=="tsv"?"\t":";")),$J)."\r\n";}function
apply_sql_function($s,$e){return($s?($s=="unixepoch"?"DATETIME($e, '$s')":($s=="count distinct"?"COUNT(DISTINCT ":strtoupper("$s("))."$e)"):$e);}function
get_temp_dir(){$I=ini_get("upload_tmp_dir");if(!$I){if(function_exists('sys_get_temp_dir'))$I=sys_get_temp_dir();else{$Sc=@tempnam("","");if(!$Sc)return
false;$I=dirname($Sc);unlink($Sc);}}return$I;}function
file_open_lock($Sc){$gd=@fopen($Sc,"r+");if(!$gd){$gd=@fopen($Sc,"w");if(!$gd)return;chmod($Sc,0660);}flock($gd,LOCK_EX);return$gd;}function
file_write_unlock($gd,$Jb){rewind($gd);fwrite($gd,$Jb);ftruncate($gd,strlen($Jb));flock($gd,LOCK_UN);fclose($gd);}function
password_file($i){$Sc=get_temp_dir()."/adminer.key";$I=@file_get_contents($Sc);if($I||!$i)return$I;$gd=@fopen($Sc,"w");if($gd){chmod($Sc,0660);$I=rand_string();fwrite($gd,$I);fclose($gd);}return$I;}function
rand_string(){return
md5(uniqid(mt_rand(),true));}function
select_value($X,$A,$p,$bi){global$b;if(is_array($X)){$I="";foreach($X
as$be=>$W)$I.="<tr>".($X!=array_values($X)?"<th>".h($be):"")."<td>".select_value($W,$A,$p,$bi);return"<table cellspacing='0'>$I</table>";}if(!$A)$A=$b->selectLink($X,$p);if($A===null){if(is_mail($X))$A="mailto:$X";if(is_url($X))$A=$X;}$I=$b->editVal($X,$p);if($I!==null){if(!is_utf8($I))$I="\0";elseif($bi!=""&&is_shortable($p))$I=shorten_utf8($I,max(0,+$bi));else$I=h($I);}return$b->selectVal($I,$A,$p,$X);}function
is_mail($oc){$Ha='[-a-z0-9!#$%&\'*+/=?^_`{|}~]';$bc='[a-z0-9]([-a-z0-9]{0,61}[a-z0-9])';$Xf="$Ha+(\\.$Ha+)*@($bc?\\.)+$bc";return
is_string($oc)&&preg_match("(^$Xf(,\\s*$Xf)*\$)i",$oc);}function
is_url($P){$bc='[a-z0-9]([-a-z0-9]{0,61}[a-z0-9])';return
preg_match("~^(https?)://($bc?\\.)+$bc(:\\d+)?(/.*)?(\\?.*)?(#.*)?\$~i",$P);}function
is_shortable($p){return
preg_match('~char|text|json|lob|geometry|point|linestring|polygon|string|bytea~',$p["type"]);}function
count_rows($Q,$Z,$Wd,$ld){global$y;$G=" FROM ".table($Q).($Z?" WHERE ".implode(" AND ",$Z):"");return($Wd&&($y=="sql"||count($ld)==1)?"SELECT COUNT(DISTINCT ".implode(", ",$ld).")$G":"SELECT COUNT(*)".($Wd?" FROM (SELECT 1$G GROUP BY ".implode(", ",$ld).") x":$G));}function
slow_query($G){global$b,$mi,$n;$m=$b->database();$di=$b->queryTimeout();$qh=$n->slowQuery($G,$di);if(!$qh&&support("kill")&&is_object($h=connect())&&($m==""||$h->select_db($m))){$ge=$h->result(connection_id());echo'<script',nonce(),'>
var timeout = setTimeout(function () {
	ajax(\'',js_escape(ME),'script=kill\', function () {
	}, \'kill=',$ge,'&token=',$mi,'\');
}, ',1000*$di,');
</script>
';}else$h=null;ob_flush();flush();$I=@get_key_vals(($qh?$qh:$G),$h,false);if($h){echo
script("clearTimeout(timeout);");ob_flush();flush();}return$I;}function
get_token(){$vg=rand(1,1e6);return($vg^$_SESSION["token"]).":$vg";}function
verify_token(){list($mi,$vg)=explode(":",$_POST["token"]);return($vg^$_SESSION["token"])==$mi;}function
lzw_decompress($Ra){$Xb=256;$Sa=8;$lb=array();$Kg=0;$Lg=0;for($t=0;$t<strlen($Ra);$t++){$Kg=($Kg<<8)+ord($Ra[$t]);$Lg+=8;if($Lg>=$Sa){$Lg-=$Sa;$lb[]=$Kg>>$Lg;$Kg&=(1<<$Lg)-1;$Xb++;if($Xb>>$Sa)$Sa++;}}$Wb=range("\0","\xFF");$I="";foreach($lb
as$t=>$kb){$nc=$Wb[$kb];if(!isset($nc))$nc=$jj.$jj[0];$I.=$nc;if($t)$Wb[]=$jj.$nc[0];$jj=$nc;}return$I;}function
on_help($rb,$nh=0){return
script("mixin(qsl('select, input'), {onmouseover: function (event) { helpMouseover.call(this, event, $rb, $nh) }, onmouseout: helpMouseout});","");}function
edit_form($a,$q,$J,$Hi){global$b,$y,$mi,$o;$Nh=$b->tableName(table_status1($a,true));page_header(($Hi?'Edit':'Insert'),$o,array("select"=>array($a,$Nh)),$Nh);if($J===false)echo"<p class='error'>".'No rows.'."\n";echo'<form action="" method="post" enctype="multipart/form-data" id="form">
';if(!$q)echo"<p class='error'>".'You have no privileges to update this table.'."\n";else{echo"<table cellspacing='0' class='layout'>".script("qsl('table').onkeydown = editingKeydown;");foreach($q
as$C=>$p){echo"<tr><th>".$b->fieldName($p);$Qb=$_GET["set"][bracket_escape($C)];if($Qb===null){$Qb=$p["default"];if($p["type"]=="bit"&&preg_match("~^b'([01]*)'\$~",$Qb,$Eg))$Qb=$Eg[1];}$Y=($J!==null?($J[$C]!=""&&$y=="sql"&&preg_match("~enum|set~",$p["type"])?(is_array($J[$C])?array_sum($J[$C]):+$J[$C]):$J[$C]):(!$Hi&&$p["auto_increment"]?"":(isset($_GET["select"])?false:$Qb)));if(!$_POST["save"]&&is_string($Y))$Y=$b->editVal($Y,$p);$s=($_POST["save"]?(string)$_POST["function"][$C]:($Hi&&preg_match('~^CURRENT_TIMESTAMP~i',$p["on_update"])?"now":($Y===false?null:($Y!==null?'':'NULL'))));if(preg_match("~time~",$p["type"])&&preg_match('~^CURRENT_TIMESTAMP~i',$Y)){$Y="";$s="now";}input($p,$Y,$s);echo"\n";}if(!support("table"))echo"<tr>"."<th><input name='field_keys[]'>".script("qsl('input').oninput = fieldChange;")."<td class='function'>".adminer_html_select("field_funs[]",$b->editFunctions(array("null"=>isset($_GET["select"]))))."<td><input name='field_vals[]'>"."\n";echo"</table>\n";}echo"<p>\n";if($q){echo"<input type='submit' value='".'Save'."'>\n";if(!isset($_GET["select"])){echo"<input type='submit' name='insert' value='".($Hi?'Save and continue edit':'Save and insert next')."' title='Ctrl+Shift+Enter'>\n",($Hi?script("qsl('input').onclick = function () { return !ajaxForm(this.form, '".'Saving'."‚Ä¶', this); };"):"");}}echo($Hi?"<input type='submit' name='delete' value='".'Delete'."'>".confirm()."\n":($_POST||!$q?"":script("focus(qsa('td', qs('#form'))[1].firstChild);")));if(isset($_GET["select"]))hidden_fields(array("check"=>(array)$_POST["check"],"clone"=>$_POST["clone"],"all"=>$_POST["all"]));echo'<input type="hidden" name="referer" value="',h(isset($_POST["referer"])?$_POST["referer"]:$_SERVER["HTTP_REFERER"]),'">
<input type="hidden" name="save" value="1">
<input type="hidden" name="token" value="',$mi,'">
</form>
';}if(isset($_GET["file"])){if($_SERVER["HTTP_IF_MODIFIED_SINCE"]){header("HTTP/1.1 304 Not Modified");exit;}header("Expires: ".gmdate("D, d M Y H:i:s",time()+365*24*60*60)." GMT");header("Last-Modified: ".gmdate("D, d M Y H:i:s")." GMT");header("Cache-Control: immutable");if($_GET["file"]=="favicon.ico"){header("Content-Type: image/x-icon");echo
lzw_decompress("\0\0\0` \0Ñ\0\n @\0¥CÑË\"\0`E„Q∏‡ˇá?¿tvM'îJd¡d\\åb0\0ƒ\"ô¿f”à§Ós5õœÁ—AùXPaJì0Ñ•ë8Ñ#RäT©ëz`à#.©«cÌX√˛»Ä?¿-\0°Im?†.´M∂Ä\0»Ø(Ãâ˝¿/(%å\0");}elseif($_GET["file"]=="default.css"){header("Content-Type: text/css; charset=utf-8");echo
lzw_decompress("\n1ÃáìŸåﬁl7úáB1Ñ4vb0òÕfsëºÍn2BÃ—±Ÿòﬁn:á#(ºb.\rDc)»»a7EÑë§¬l¶√±îËi1Ãésò¥Á-4ôáf”	»Œi7Ü≥ÈÜÑéåF√©îvt2ûÇ”!ñr0œ„„£t~ΩUç'3MÄ…WÑB¶'cÕP¬:6T\rc£Aæzr_ÓWK∂\r-ºVNFS%~√c≤ŸÌ&õ\\^ r¿õ≠ÊuÇ≈é√ûÙŸã4'7k∂ËØ¬„Q‘Êhö'g\rFB\ryT7SS•P–1=«§cIË :çdî∫m>£S8LÜJÅút.M¢èä	œã`'C°º€–889§» éQÿ˝åÓ2ç#8–ê≠£íò6m˙≤Üjà¢h´<Öå∞´å9/ÎòÁ:êJÍ) Ç§\0d>!\0ZáàvÏªnÎæºo(⁄Û•…k‘7Ωès‡˘>åÓÜ!–R\"*nS˝\0@P\"¡Ëí(ã#[∂•£@gπo¸≠ízn˛9k§8Ünöô™1¥I*àÙ=Õn≤§™è∏Ë0´c(ˆ;æ√†–Ë!∞¸Î*cÏ˜>Œé¨E7DÒLJ©†1»‰∑„`¬8(·’3M®Û\"«39È?EÅe=“¨¸~˘æ≤Ù≈Ó”∏7;…Cƒ¡õÕE\rd!)¬a*Ø5ajo\0™#` 38∂\0 Ì]ìeåÍà∆2§	mk◊¯e]Ö¡≠AZs’StZïZ!)BR®G+Œ#Jv2(„†ˆÓcÖ4<∏#sBØ0È˙Ç6YL\r≤=£Öø[◊73∆<‘:£äbxîﬂJ=	m_ æœ≈f™lŸ◊tãÂI™ÉH⁄3èx*Äõ·6`t6æ√%ùU‘LÚeŸÇò<¥\0…AQ<P<:ö#u/§:T\\>†À-ÖxJàÕçQH\nj°L+j›zÛ∞7£ï´`›é≥\nkÉÉ'ìN”vX>ÓC-TÀ©∂ú∏êÜ4*Lî%Cj>7ﬂ®äﬁ®≠ıô`˘Æú;yÿ˚∆q¡r 3#®Ÿ} :#nÌ\r„Ω^≈=CÂA‹∏›∆éÅs&8é£K&ªÙ*0—“t›S…‘≈=æ[◊Û:ù\\]√E›åù/O‡>^]ÿ√∏¬<çËÿ˜gZ‘VÜÈq∫≥äå˘ ÒÀx\\≠çËïˆπﬂﬁ∫¥Ñ\"J†\\√Æà˚##¡°ΩDÜŒx6Íú⁄5x ‹Ä∏∂Ü®\rH¯l ãÒ¯∞b˙†rº7·‘6Ü‡ˆj|¡âÙ¢€ñ*ÙFAquvyOíΩWeMã÷˜âD.F·ˆ:R–\$-°ﬁ∂µT!ÏDS`∞8Dò~ü‡A`(«emÉ¶Ú˝¢T@O1@∫ÜX¶‚ì\nLpñëP‰˛¡”¬m´yf∏£)	â´¬à⁄GSEIâÅ•xC(s(aù?\$`tE®nÑÒ±≠,˜’ \$aêãU>,Ë–í\$ZÒkDm,G\0Â†\\êêi˙£% π¢ n¨••±∑Ï›‹gê…Ñb	y`íÚ‘ÜÀWÏ∑ ‰óó°_C¿ƒT\niêœH%’da¿÷iÕ7ÌAt∞,¡ÆJÜX4nàëîà0oÕπª9g\nzmãM%`…'I¸Äç–û-ËÚ©–7:p3p«çQórEDö§◊Ï†‡b2]ÖPF†˝•…>e…˙Ü3j\nÄﬂ∞t!¡?4fêtK;£ \rŒû–∏≠!‡oäuù?”˙ÅPhûê“0uIC}'~≈»2áv˛Q®“Œ8)Ï¿Ü7ÏDI˘=ßÈy&ï¢ea‡s*h…ïjlAƒ(Íõ\"ƒ\\”Ím^iëÆM)Ç∞^É	|~’l®∂#!YÕf81RSé†¡µ!áÜË62P∆CëÙl&Ì˚‰xd!å|†Ë9∞`÷_OYÌ=—G‡[E…-eLÒCvT¨ )ƒ@êj-5®∂úpSgª.íG=Åî–ZE“ˆ\$\0¢—ÜKjÌUßµ\$†Ç¿G'I‰P©¬~˚⁄Å ;Å⁄hN€éG%*·RjÒâX[úXPf^¡±|ÊËT!µ*N–Ü∏\rU¢å^q1V!√˘Uz,√I|7∞7Ür,æ°¨7îËﬁƒæB÷˘»;È+˜®©ﬂïàA⁄pÕŒΩ«^ÅÄ°~ÿºW!3PäI8]ìΩv”Jí¡fÒq£|,ùÍË9W¯f`\0·qàA÷wE¨‡Á’¥¶FáëäŸTÓ´Q’ëGŒ˘ê\$0«ì †#«%By7r®i{eÕQ‘üÚàdÑÅÏ«á ÃB4;ks(Â0›é¡=ê1r)_<øîÿ;ÃπùÁSå€r† &Y«,h,ÆüiiŸÉ’¡b…Ã¢AñÈ ºÂG±¥Lçòz2p(¶œŸıîâÉ0¿∞äõ¬L	°πS≈˙®®EÍ¿ò	<©ƒ«}_#\\f™®da ÑÁKÂ3ºY|V+Íl@≤0`;≈‡ÀLh≈‰±¡ﬁØj'ôÅõòˆ‡∆ôªY‚+∂âQZ-iÅÙúyvÉñIô5⁄ì0O|ΩP÷]F‹è·Ú”˘Ò\0ê¸À2ôD9Õ¢ô§¡n/œáQÿ≥&¶™I^Æ=”lé©qfI∆ = ÷]xqGR¸F¶eπ7È∫)äÛ9*∆:B≤b±>a¶zá-µâ—2.Øˆ¨∏b{∞4#Ñ•ºÚƒU·ìç∆L7-º¡v/; 5ÒíÙéu© ˆHÂß&≤#˜≥§j÷`’Gó8Œ ì7p˘ÿ“†YC¡–~¡»:¿@∆ﬁEUâJ‹€;v7v]∂J'ÿﬁ‰q1Ô∑ÈElÙô–ÜiæÕ√œÑ/Ìˇ{k<ê‡÷°M‹poÌ}È¡§±ïŸû,Ïd√¶Ÿ_u”óÔ¬çp∫uﬁΩ≈˘˙¸˙=ªë∑tn˛¥ô	è˝üô~◊LxÓ¯Êã÷{k‡ûﬂáÂﬁ˘\rj~∑P+éˇÁ0–uÚow⁄yu\$‹Ëﬂ∑Ó\nd•…m¥ZdÅ¿8i`§=˚€g<ßò˘€ìÏ·Õà*+3jå¶Ã¸‹è<[å\0≤Æˇ/PÕ≠BˇŒr±Ñˆ`À`Ω#xÂ+B?#ˆ‹è^;Ob\r®Ë˘Ø4¯œ\n˜ÃÊø0\n˙êÙø0è\\◊0>éêP¯@˙Ø¿2Çl∆¬j“O™Îåˇ®(_Ó<ÁêW\$Ÿg∫¯†G≠t◊ê@˚l.áhúSi∆æ∞¨PHè\n¶JÎ‚ãÎËLD„h6≈Çê¬∂B	Ø√rÄ⁄‚\r®6£n¨–Â∞Î0‡ Fıp-–Á\rÄ‡\r\0‡Áöq±∞„#q`ø¸®#E—(q}®–∑˙áêÈÒ	 4@ÔëÈ˙É…f|\0``fì*‚ñ`ç†`ñ–◊QRvÄﬂy¿Í\rÒ-±B± §y7±&™@ÿÒóúã±†Ñ§™`øÒõê_IëŸ1ñò@`)l¡Òãx‡Ï)±Q±ﬁqã—‹)≠Ï›‚Íﬁ1sQeyqw1Ôë«ËA 2 ±Ú*Ñè®«q wg>C∞ÆB≥»∫A*Œ~p’PÍO`œ	CŸ\$à¢“ë≥2M%Ä∆êR≤W±ô%RO&2S\rík‡ÿç“~≤/ëj¿ãPŸ\$@æÅ“_)rw&íORq%â±*rm)≤´'íO'—1'Rù(5(IŸr:im,‡ç®líQ0\0€ÚDç˜Ò'%r€-Ò†=í∞«rÎ'2K/≤X@`ËØ“:,#*“•+RY3Ú~ê«E¸ÉŸ—23'-Q*\r` 113s;&cq10Î4œ.®A2Î32@7*2f`ê“Á-Q!”E“&Ú6“%ë≠7±b¡6ÒŸ%”ÄÛÑõ”è1í†‡Ûy9≤[7Qu9”†™sâ7”©¿æ\r©;è4ìπ;”£!sô!c\\eù;1<Sq≥”=só52á,±jSÒ)Í]Ò‚Û˘mp&Q'<ë±@1Æ0\"¡:h–ôëä°Ô≥‘ñR òiãÕ.J”.ëB–Q&ÈÇ\n∞0ã	5¢ç;±∞j©ΩDŸ9-\r\"SÆ¸±1@îes„Eq§e”&ÃT.è*òLºìi3À:≥ßEÛ•H≥π ≠GÕÆå(˝rEIJíi!4Y±yJ‘óK˚Kt≥;ë∫T.ê√Ñ)äê¬¬o)|†P;.àÄùâ≤∞‚\nlºõ*Œµ‚´j˛±§|Ω£O√l≤B‚.h∫.ÙúÚÚ AÃ\r√Ü.≤88÷2t⁄#Ùﬁo¢ANbÀN©?Ò!¿ÀOBÛOî,d≠º*Ä");}elseif($_GET["file"]=="functions.js"){header("Content-Type: text/javascript; charset=utf-8");echo
lzw_decompress("f:õågCIº‹\n8ú≈3)∞À7úÖÜ81– x:\nOg#)–Ír7\n\"ÜË¥`¯|2ÃgSiñH)N¶Së‰ß\ráù\"0πƒ@‰)ü`(\$s6O!”ËúV/=ùå' T4Ê=ÑòiSòç6IOì erŸxÓ9ê*≈∫∞∫n3ù\r—âvÉCÅ¡`ıö›2G%®Y„Ê·˛ü1ôÕfÙπ—»Çl§√1ë\ny£*pC\r\$ÃnçT™ï3=\\Çr9O\"„	¿‡l<ä\r«\\Ä≥I,ós\nA§∆eh+M‚ã!çq0ô˝fª`(πN{cñó+wÀÒ¡Y£ñpŸß3ä3˙ò+I¶‘jπ∫˝éœk∑≤n∏q‹Éçzi#^rÿ¿∫¥ã3Ë‚çœ[ûË∫o;ÆÀ(ã–6ç#¿“êéç\":cz>ﬂ£C2v—CX <ÅPò√c*5\n∫®Ë∑/¸P97Ò|Fª∞c0É≥®∞‰!çÉÊÖ!®úÉ!â√\nZ%√ƒá#CHÃ!®“r8Á\$•°ÏØ,»R‹î2Ö»„^0∑·@§2å‚(88P/Ç‡∏›Ñ·\\¡\$La\\Â;c‡HÑ·HXÑÅï\n Étúá·8A<œsZÙ*É;I–Œ3°¡@“2<ä¢¨!A8G<‘jø-KÉ({*\rí≈a1á°ËN4Tc\"\\“!=1^ï›M9O≥:Ü;jåä\r„X“‡L#HŒ7É#T›™/-¥ã£p ;ÅB ¬ã\nø2!É•Õt]apŒé›Ó\0R€CÀv¨M¬I,\rˆçß\0Hv∞›?kTﬁ4£äºÛuŸ±ÿ;&íêÚ+&Éõïµ\r»XèçÅbu4›°i88¬2B‰/‚Éñ4É°ÄN8A‹A)52Ì˙¯ÀÂŒ2à®s„8Áì5§•°pÁWC@Ë:òtÖ„æ¥÷eêöh\"#8_òÊcp^„à‚I]OH˛‘:zd»3g£(Ñà◊√ñk∏Óì\\6¥êòê2⁄⁄ñ˜πi√‰7≤òœ]\r√xOæn∫pË<°¡pÔQÆU–nãÚ|@ÁÀÛ#G3¡8bA® 6Ù2ü67%#∏\\8\r˝ö2»c\rÊ›ükÆÇ.(í	éí-óJ;Óõ—Û »ÈL„œ ÉºûW‚¯„ßì—•…§‚ñ˜∑ûn˚†“ßªÊ˝MŒ¿9Z–ùs]ÍzÆØ¨Îy^[ØÏ4-∫U\0ta†∂62^ïò.`§Ç‚.Cﬂjˇ[·Ñ†% Q\0`dÎM8ø¶ºÀ€\$O0`4≤ÍŒ\n\0a\rAÑ<Ü@üÉõä\r!¿:ÿBAü9Ÿ?h>§«∫†ö~Ãåó6»àh‹=À-úA7X‰¿÷á\\º\rÅëQ<Ëößqí'!XŒì2˙T ∞!åD\rß“,K¥\"Á%òH÷qR\rÑÃ†¢ÓC =éÌÇ†Ê‰é»<cî\n#<Ä5çM¯ ÍEÉúyå°îìá∞˙o\"∞cJKL2˘&£ÿeRú¿W–AŒêTw —ë;ÂJà‚·\\`)5¶‘ﬁúBÚqhT3ß‡R	∏'\r+\":ñ†ÿ‡.ì—ZM'|¨et:3%L‹À#¬ëf!Òh‡◊Äeå≥úŸ+ƒº≠N·π	¡Ω_íCXäùGÓò1Üµi-√£zû\$íoK@O@T“=&â0ù\$	‡DAëõ•˘˘D‡™SJËx9◊ÅF»àml®»pªG’≠§Tê6Rf¿@Éaæ\rs¥R™Fgih]•Èfô.ï7+—<nhhí* »SH	P]° :“í®¡a\"®ê’˘¨2¶&R©)˘B¶P ô”H/Åıf {r|®0^ŸhCAÃ0ª@ÊMŒ‚Á2ìBî@©‚z™UäëæO˜˛âCppíÂ\\æL´%Ë¨õÑíy´Áod√•ïâ¥p3∑ùä7E∏ó–‹A\\∞ˆÜKÉ€XnÇÿi.–Z◊Õ Ûüòs°âG˝m^ùtIÚYëJí¸Ÿ±ïG1Ä£R®≥Dçícñ‰‡6ïtMih∆‰9Éª9gÅÉqóRLñ˚Mj-TQÕ6i´G_!Ìê.Ωh™vﬁ˚cN®å˝∏ó^¸—0w@n|˝Ω◊V˚‹´òA–≠√¿3˙[⁄˚]é	s7ıGÜP@ :Ã1—Çÿbÿ µÏ›üõíÅwœ(i≥¯:“Âz\\˚∫;”˘¥AÈPU T^£]9›`UX+U†ÓãQ+â√bÃ¿Ò*œîs®ºÄñóŒ[ﬂ€âxk˚F*ÙÇé›ß_w.Ú≈6~Úb€ŒmKÏæsIﬁMK…}Ôï“•⁄¯ÂeH…≤àdµ*mdÁlúQ∞êeHÙ2Ω‘çL®Å†a“ÇØ=Ö≥sÎP¯aM\"ap√¿:<·Ö‰GBî\r2Ytx&L}}ëﬂAœ‘±NÖG–¨zaîˆD4¯t‘4Q…vS©√πS\rŒ;U∏Í¶È‰˝∏¥∆~ípBÉ{∂—∆,úó¢O¥„t;«J°ôZC,&Y∫:Y\"›#âÅ‹„ƒt:\nëh8rØ°Ó⁄nÈ‘»h>ÅÑ>Z¯`&‡aﬁpY+πx¨U’˝Aº<?„îPxW’°ØWô	i¨À.…\r`˜\$,¿˙©“æã≥V•]åZrõ‰ßH≥à5∆f\\∫-K∆©¶vºïZÁ‰ÆA∏’(ß{3≠oõÛø°l.øÏπJÈ≈.Á\\t2Ê;éØÏ2\0¥Õ>c+Å|¡–*;-0Ón¬‡[Åt@€⁄ïÚ¢§=cQ\n.zâï…wC&á‘@ë˘¶FÊ’àáé'cBS7_*rs—®‘?j3@ñàÙ–!.@7ûsä]”™ÚL˜ŒÅGü@ˇ’_≠qùÅ’&u˚ÿÛt™\n’é¥LﬂE–T§≠}gGñ˛∏ÓwÎoˆ(*ò™ÜõAÌØ-•≈˘¢’3ømkæÖ˜∞∂◊§´üt∑¢S¯•¡(˚d±ûAÓ~Ôx\n◊ıÙßk’œ£:Dü¯+üë g„‰h14 ÷‚\n.¯œdÍ´ñ„Ïí†ˆ˛ÈAlY¬©jö©ÍéjJú«≈PN+bê D∞jº¨ÄÓ‘ÄD™ﬁP‰ÏÄLQ`Ofñ£@ÿ}ê(ù≈¬6ê^nB≥4€`‹e¿ê\nÄö	Ötrp!êlV§'ê}bâ*Är%|\nr\r#é∞ƒ@wÆº-‘T.Vv‚8Ï™Ê\nmF¶/»p¨œ`˙Y0¨œ‚Î≠ËÄP\r8¿Y\ráÿ›§í	¿QáÅê%EŒ/@]\0 ¿{@ÃQêÿ·\0bR M\rÜŸ'|¢Ë%0SDr®»†ûf/ñ‡¬‹b:‹≠Ø∂ﬁ√¬%ﬂÄÊ3H¶x\0¬l\0Ã≈⁄	ëÄW‡ﬂ%⁄\nÁ8\r\0}ÓDûÑ…1d#±xÇ‰.ÄjEoHr«¢lb¿ÿ⁄%tÏ¶4∏pÑ¿‰%—4íÂ“kÆz2\rÒ£`ÓW@¬íÁ%\rJÇ1ÄÇX†§⁄1æD6!∞ÙèÜ*á‰≤{4<E¶ãk.mÎ4ƒÚ◊Ä\r\nÍ^iç¿ç Ë≥!n´≤!2\$ß»¸çÃ˜(ÓfÒˆƒÏƒ˘k>éÔ¢≈ÀN˙Ç5\$å‡È2Tæ,÷LƒÇ¨ ∂ Z@∫Ì*–`^PP%5%™tëH‚W¿on¸ˆ´E#fêˆ“<⁄2@K:Ãoö˘ÚíÃœ¶Õ-Ë˚2\\Wi+fõ&—Úg&≤nÌLı'e“|Ç≤¥ønK•2˚r⁄∂Àp·*.·n¸≤íŒ¶âÇÇ*–+™tèBg* ÚûQÖ1+)1h™äÓ^ã`Q#Òÿé‚n*hÚ‡Úv¢B„Ò\0\\F\nÜW≈r f\$Û=4\$G4ed†bò:J^!ì0Äâ_‡˚¶%2¿À6≥.FÄ—Ë“∫ÛEQ¡±Ç≤Œdts\"◊ÑëíçB(è`⁄\r¿öÆcÄR©∞∞ÒVÆ≤îÛ∫XÍ‚:Rü*2E*s√\$¨œ+¡:bXlÃÿtbã·-ƒ¬õS>í˘-Âd¢=‰Ú\$S¯\$Â2¿ Å7ìj∫\"[ÃÅ\"Ä»]†[6ìÄSE_>Âq.\$@z`Ì;Ù4≤3 º≈CS’*Ô™[¿“¿{DO¥ﬁ™CJjÂ≥öPÚ:'ÄéË»ï QE”ñÊé`%rÒØ˚7Ø˛G+hW4E*¿–#TuFjï\næe˘DÙ^Êsößr.Ïâ≈RkÊÄz@∂è@ªÖ≥D‚`C¬V!CÊÂï\0Òÿ€ä)3<ééQ4@Ÿ3SPá‚ZB≥5FÄL‰®~G≥5ç»“:Ò¬”5\$X—‘ˆ}∆ûfäÀ‚IéÄÛ3S8Ò\0X‘Çtd≥<\nbtNÁ Q¢;\r‹—HÇ’Pè\0‘Ø&\nÇû‡\$V“\r:“\0]V5gV¶ÑÚD`áN1:”SS4QÖ4≥Nïè5uì5”`x	“<5_FH‹ﬂı}7≠˚)ÄSVÌÃƒû#Í|Ç’< ’º—À∞£†∑\\†›- z2≥\0¸#°WJU6kv∑µŒ#µ“\rµÏ∑ê§ß¿˚Uıˆi’Ô_Óı^ÇUVJ|Y.®û…õ\0u,ûÄÚÙÊ∞ı_UQD#µZJuÉXtÒµ_Ô&JO,Du`N\r5≥¡`´}ZQM^mÃPÏG[±¡aªb‡N‰ûÆ†÷re⁄\nÄ“%§4öìo_(Ò^∂q@Y6t;I\nGSM£3ß◊^SAYH†hBè±5†fN?NjWUïJè–¬¯÷ØY÷≥ke\"\\B1ûÿÖ0∫ µen–ƒÌ*<•O`SíLó\në⁄.gÕ5Zj°\0R\$Âhù˜n˜[∂\\›ÌÒråù ,Ê4êú∞†cPßpêq@Rµrw>ãwCKëÖt∂†}5_uvh§”`/¿˙‡è\$ÚñJ)œRı2Du73÷d\r¬;≠Áw¥›ˆH˘I_\"4±rêµ´Æ¶œø+Íø&0>…_-eqeDˆÕVç‘nåƒfãh¸¬\"Z¿®∂ÛZ¢WÃ6\\LÓ∂∑Í˜Ó∑ke&„~á‡‡öÖëi\$œ∞¥Mr◊i*◊ƒ‚‘Á\0Ã.Q,∂¢8\r±»∏\$◊≠KÇ»YÉ –ioÕe%t’2ˇ\0‰J˝¯~◊Ò/I/.ÖeÄÄn´~x!Ä8¥¿|f∏hè€Ñ-H◊Âœ&ò/Ñ∆oá≠á¯Ç.Kî À^j‹¿tµÈ>('L\rÄ‡HsK1¥e§\0üÅ\$&3≤\0Êin3Ì® o‰ì6Ù–∂¯Æ˜Ùß9éj∞∏‡ç»⁄1â(b.îvC†›é8åçŸ:wi¨ü\"Æ^wµQ©•≈Ôzño~ﬁ/Ñ˙“í˜ñ˜`Y2èîD¨V˙ê∆≥/k„8≥π7ZèH¯∞äÉ]2k2rúøÒõäœØh©=àTÖà]O&ß\0ƒM\0÷[8ñá»ÆÖÊñ‚8&L⁄Vm†v¿±ÍòjÑ◊ö«FÂƒ\\ô∂	ô∫æ&sÂÄQõ \\\"ÚbÄ∞	‡ƒ\rBsúIwû	ûYÈû¬N ö7«C/*ŸÀ†®\n\n√Hô[´öπ‘*Aò†ÒTEœVP.UZ(tz/}\n2ÇÁyöSç¢ö,#…3‚i∞~W@yCC\nKTøö1\"@|ÑzC\$¸Ä_CZjzHB∫LV‘,K∫£∫ÑOó¡¿P‡@XÖç¥Ö∞â®∫É;D˙WZöW•aŸ¿è\0ﬁä¬CG8ñR †	‡¶\nÖÑ‡é∫–P∆A£Ë&éö∫ç†Èù,⁄pfV|@N®bæ\$Ä[áIíä≠ô‚‡¶¥‡Z•@Zd\\\"Ö|¢É+¢€ÆöÏtzo\$‚\0[≤Ëﬁ±yÉE†ÁÎ≥…ôÆbhU1£Ç,Är\$„åo8Dß≤áF´∆V&⁄Å5†h}é¬N‹Õ≥&∫ÁµïefÄ«ôYô∏:ª^z©VPu	WπZ\"r⁄:˚hèwòµh#1•¥O•‰√K‚hq`Â¶ÑÛêƒßv|†Àß:wD˙jÖ(W¢∫Å∫≠®õÔ§ªı?ê;|Zó´%ä%⁄°ƒr@[Üä˙ƒBª&ôª≥òõ˙#™ò©Ÿè£î:)¬‡Y6˚≤ñË&π‹	@¶	‡ú¸Iƒ“!õ©≤ª∂ ¬ª‚2MçÑ‰O;≤´—W∆º)Í˘C„ FZ‚p!¬ƒaôƒ*FƒbπI≥√Õæ‡å§#ƒ§9°¶ÂÁS©/S¸Aâ`zÈïL*Œ8ª+®ÃN˘ãƒ-∏Mïçƒ-kd∞Æ‡LiŒJÎÇ¬∑˛Jn¬√bÌ†”>,‹V∂SPØ8¥Ë>∂wÔÏ\"E.ÓÉRz`ﬁãu_¿ËúÙE\\˘œ…´–3PÁ¨Û”•s]îïâgoVSÉ±ÒÑ\n†§	*Ü\rª∏7)™ Ñ¸mùPW›U’Äﬂ’«∞®∑ﬁfî◊‹ìiˇ∆Ök–å\rƒ('W`ﬁBd„/h*ÜAÃl∫Mé‰Ä_\n¿Ë¸˙ΩµÎO™‰TÇ5⁄&A¿2√©`∏‡\\R—E\"_ñ_úΩ.7•Mú6d;∂<?»‹)(;æ˚â}K∏[´≈˚ª∆Z?ù’yI ˜·1p™bu\0ËÈà≤≤åÅ£{Û£≈\riÑs…QQ¶Yß2™Ö\r◊î0\0Xÿ\"@qÕéuMbˆ”uJç6…NG÷˛ñ^”‘wF/tíı∞#Pæp˜Õ!7ûÿ˝ù≠ÖÂõú!√ªÈ^V¸ÑMñ!(‚©Ä8÷ùÕ=•\0Â•@òøÌ80N¨S‡Ωæ∞Q–_Tœ‡ƒ•˛qSz\"’&h„\0R.\0hZ”fxá†‹F9∂Q(”b≥=ƒD&xs=Xõbuû@oŒwÉdì5Ò«›Pè1P>k∏äHˆD6/⁄øÌqÎûºæŒ3•7T–¨K»~54∞	Òt#µMñ\rcètxãgÅÁTòÊX\rÇ2\$Ì<0¯y}*ﬂˇCbi∆^ÛÜ±ƒLá7	Åb‰o˘å” x71è bÄXS`O¿‡·≠0)˘®⁄\"Æ/Üï=»¨ ∏l ·òQˆpÕ-ò!˝‡{˝ıÄ±©ñ÷‚aÑ√»ï9bAg∂2,1Åzf£k‡»jÑh/o(í.4â\r˝É‡Tz&nw∂îƒ7 X!˚ü™@,ª<ó	ì˝`\"@:Üº7√CX\\	 \$1H\n=ƒõ°O5å∞&∫vê*(	‡tHé—#…\nÍ_X/8ïk~+têÄóO&<vâÕ_YhÇÄ.ÿÅMeÄHxp·I®aá˘0’M\nh¯`r'BÖ•√h”n8q—á!	Â÷†euª´]^TW≠äë÷d9{˚æH,„óÇ8≈¸L≠a´,!\0;∆ÓB#…#¡“`Ú)≥Øüôñ	≈ÑaËEeÚ⁄ë‹/MËP”	ìlÑû…a`	•s‚≤Ö<(D\nˆ·°¿9{06ú∆à;A8∂∏5!	†Õ¿Z[T‚© hVÖ†ª‹ª≈ÈØU@‰n`∆Vùpé•h(Rb4∆VÙ∆âº∏“»RpÄ¢“î\$™ô–ﬁD3O°æı‘\$Äˆ√”ÅaQ≤Ø0xbåH`†Æ–‚L√î8iæËoCãΩ‡˙#6îx )XH–!`˜Ì¿Ùã∆‘B÷%w—¬«o\nxÃÄhÆ¡Hãªàr¶  ºcÛú¿mJH·LU‹‰∆e1l`¸(’\$\"æhÜJ“rvÿÌ”TP¡–ÿ∑Û1uÔ¢áHA\0ËËH2@( °U‡\"©QÅ@qg]l\"®%©é˙*´\0Wäj[é ÜÅ∑e√4Íı∆P˙¬NîÇ‡Í5\$H\rºÓIPêÑ'@:\0Ë\"#t^ÜD≠ê0≈ËìÂ´>É(úíh∑ 'úºF,sZJÙËµAnØ#âh†™X≥ó.qêãYob⁄à∑Å“2®ﬁ?jºÄB˜IñÙﬂ£Äõ•ç÷€Ù˘0Üa˚(Òù`ZÒC¡ç‡ØrööHSQÓ∆\\ÇáW	ºÄXZ˜Õ|πE@ç‚¬T‘ù≈ñq†DD:_y’Øƒ∞±©Bê~ﬂxP±--eÇá_‰uã|2(≥G,∆Âà-rR†KxÓ’†dé°√hHÏA|ÙçèåwÑ|P¡!«â“ë‰é¨}‹T˘«÷<—˘,1—’vÍg*Ÿ§ÔêzØ^Ä´˜§úÒ_pi {ÄÿG’Ìû›ˇ	LaJJCñT%N1á“I:V@Z‘¡%…Ç*‘|@NNxLéêLÄzd \$8b#€!2=c€ç±QDäÌ@Ω\0±J‡dzp˚Ø\$AÓè|ya4)§îs%!•BIíQ]dòG¥6&E\$òÖH\$Rj\0úá∑‹óGi\$ÿ•‚9≈ÜY˙–@ ¥0Ò6ƒ¶ë∫X“‹û1&LïÁ&2Ã	E^è‰a8ˆj¶#∏DEuÄ\$uTÃ*R•#&àÇP2ïe•‰KÉ´'öE%‚î°íYW·JïÙå	î©ˆôO`É ï∑Ä^l+¶Ñ`®	Rπ1uÉ&Fò∏•Z[)]J¨Z√Eï—`±∂FN.\rï=¿ÿ †≥\0¥O~â“≈M,´ÖFATÃbôhËz0çâ`-blã\nÒ«ÖZ†'ó*IÜn∞\$‚è[í,8Dáün´®`∞ò“ÛI0u Äºhfå¨≥§í‡‡‡AEy<!‘¡xdA¿ Ù1¨a∆U¿ñt\$ΩÄà'pá\"áÛÑ–ëj¸ñP6XR)EŒTR∞\0S√@-…T≥‘≥.S¡wU\\øÑ\\Ä(\rÏıï—¬¿k¿∏˙g`j}\$œ`aJsL¬ŒöÈR3÷TÈXö}Êä£8%Å˝HÅ@äZ\0^UŸ≠ |6A∏Ä¿RÉT/‡¨ëŸE∆@ƒû\0ƒ§Lÿ¬ÓPÄçµÅ¢˚∫R–0\0ë-dIö¨—ÊØ+®öµ,W¿v‡ﬂ≈Ù6N4\"Äm„N¬U9P6Œ>r /	tÂRvAp©Õ4R3LXÜ\0–Å¨Sú1LO˙0<Õ|S(+Ï‚J≈9`1ŒbsS^–‚8≥	Êe3∂ú®XÄÁ9Q¥ÜÊwÅ*ú◊¿W2ëMêZaGìKﬁ≈π0’YË\r≥úƒ¶fÍiÍÃH(/‰[†ºÒ\"Yß¯W√7Zdµ√J \"É∆\0ƒë7D”“¶LE»¥Ω.xòùCvê∆¬„æO´Q≈,_B√±÷{Áì3dÖ”zØ0“ò‘ÇÃuILZcÛ¯–∆åöî\"J%„÷R§á£Ÿ •a„gÏ^%z∆5=ÇS)≤WìZx’ÜÑ˚QöèZ @†&;ç‡ñéu.å@Û&F(‰:F{†S⁄“°!–‰Mí8Äπ»%B#i‰CºäîŸ*S\$œ¿ö@o¯CßÊ9˙§ÜTgŒsTãXÊ‚ù\0Ëêû‹”Bí)·PñD¥ó®òí'Cu“c£JÄp£‘ÂiÅúB`DÇ'\0…HY*,XfTlzãiP¯å¡˛ ¢p…â»!H¥#:˚√ÅHu…PÖ2Ëê\0BäHräÌ´I‚°‡Cã	JrË—–2	 ¿Ñío\n≈îeêHJuJ“‚S\0ÊœVr ñ=!ıÅä*Lv+òYÜT\0002â:Í≤(¶®öh”µ ¬V#ÃƒßMe°yV@[^¯C˛ø¢9/Ùˇ\0{ßﬁ ÁNDfóÃ?Èƒ\$‹úiäΩÜJ≤õ*qMâ&Vê´Öú¨ÌÔhB^Èvc‚SÍÇ¨ﬁ†±QÕ1î‚<\nv”2útÂÈ¬ˆà1Øﬁûè˛®ç8âQA~S*ù’ßàò√ˇQzuS-å°	È/b√î©éj˚îô∆‰Ú˜Û∆Dl§)Tä–|È§ôïå<å√+…6<<†–0úL%ñh,ó™”Z.“W‰Iê§§„™§d1âﬂHÎádN™`3é.'KÙ¶ì˛ùP´”>åU?‚I&¶¢P™›!µ[>’Yâ‹£gaﬁD\$ )0I∆A2-:gk i¿∆FzßÑÑ∑j˝\\»∆Ï\"õíñ\"~j˘”WX˚Œ’Puí®üîƒRËJY:nC|(EÕ∫û9ûd‡LH¿ä¿)≠`XÖ'æπ>\0¢±¢∫≠ek§nb=ú*f°Bl&|Sb’B,—0ayTÄÿr=j™názLË@GE'∫≠\nHPÎ@‡<@êgqïò~@Ïp>\$ïÈ*òÇ@¢Ú¨\"Ä¬G–>0^ø\"têK	èIƒÈ¨“æucz®àıX‡Å“zÑe\"¨‡D¸ù:À4~∫#&´:Û\0∂ù1‡'Ng’Í-∞@t¶)®)¸Cå™D≠(ñJNWå∫HuÆui	Zz¥,“∫kæRTÜÙõÅ¬eUvrvóôb —àö¥ßÜ∞®nÎ§q∫Í;Ã>¢—\nŸ‡ÆÔ∑\0úr6CΩnÄ◊a‡ÄÑ¯TƒŸq\0N‰¶Å‹®eI.Ùz≈}Ua&Ll#–mï;!ƒ®†»\"~¯û@≈]\nÃà\0vwÂÅÏı:h]W6[´.D~\$!{YÌ`Äb£‡pZç°QÅò§1\rhp∏,íLÕÖ©``K@\0¿âb†->æ\0gX¢’MƒÛçSxÌ\\“Úœvªíw2Õfé8ê@ƒ’\n.xô‡&,	à‰J~‰*îÈ.q	iaN¬=≥¥“pÙ÷¢r;¿»è€7‚¬E ¿À\\”∞¿¶Ÿ.ö∂XÙÌFùqä[@‚™r\rµSmÄ/&r€eÌ∂Í‘·nõF‹dˇÆaÿ-”:˚2›m¿∑m®ƒ◊+x€D÷_8'µ5πàD/PÆ–é†/àMÌ¡Ò∑…KXﬁêy\nÿÎÁ)\n›I±?v·	¨±…UÅ¶!ñ (°wì-\$o(·ˆJ*Ôëµl¿∏PiQ6ßE\n¢-TV -«ñ>Ák;k¶≠â@Éê‘çèñc—Œ™£jo8V5/¢º#™J<Ú›⁄4	„=(ﬂòL–¿âÖT H8töR™âÙÙ‰_≈¬•&CBÎ/‡êÅ«.Ï¶§º*1°÷aÎHÕÑ§”⁄æZ8∆Ä¥†;%Ω_\0^äÓÇÒ¨-xkw˙∫‰ïãWîW«¶.µi\nÊÚ\nHháÅgÎ»X^Ó‚ÎL&Ál@´N\nP£¿>Ì∆“JãÖDÙ(65Rµ‚…`’SX¯µíê]Øl‡´”ê¬§µ.ÌÄÁÖﬂs6öúÂÒ›÷∫πPàÇhÖ·P∆ ∞5%`–*π.!¿‘æ´?X˙œ24XB\r;4Ÿ¨)6m4SS®ÛY†&ñj≠õ;~‰˜ﬂ*Å¢Ω–‰9D—⁄]‡\\\0i›ÌìÃ\0ã¨EwrNzQ”–ã˛ÓIÖù=Æp{g[A ±æ,=·ÄPìô≥Œ7\0?ºi)À\$¢÷H?¬åΩ‡@e‘Á]d†5÷ Ôz§ÑJ`¿^„™òà˜H¬n≤qñ¨ûÅ>‡K(¶R}’\\#uòn≈@Hñ6´∏F©ÒgÁÒVı[ÜîI+ƒ˛0∏‘ó Ä\0-»¨ìπˇ\np¿hE’sA¯·¥Aüƒ¸-|ôI¸aD¬=è>‘}|<“˙˙)R/ËU?∫Pı®È	’ƒB¡–‹‹TÿÅ™3ˇ∞ÁâB¸°–¯òÑ∂Ò7ÎÊ\0†?∏d√5„\0YÜì∞¶∑L	çr=´ÿ–¯Ñ¢@Øº c¶∞ΩBÂöbrÂhB≈H–ﬁ\$ /›îú≈πNèMâƒæØE`4•ÒKœ·Å{©≤LÍ®˚âJD&º–:	aôKo%∫G·è-ç”qú}|h	é•ïì‡ep`±]ÿ,∆—≥IˆΩè]B›¿g∑˚4x‘z\\bÏî\"®Hnπ	i€l«i∞u‚Ê‡wó#€±+|KYv†Ë\"ñ`˜ÿC\\Ç3á2\\Íè\\\\Cì«¬1ım¸# /„G=¨ô:πí	«4¥«”KÑßH˝Í∏‘\\*±±ç¢éct⁄#Év-‰«Zèd—o√é÷52gúö≠ˇ(√∂ z•2¢8‚˘?)Ly nQ◊Rúß‹ëmMnÜ]ÒﬂƒÑh≈¸&\$„éa’’\nñîÇ◊r3]guµî‰\"Î‡6ªß*£á@‚1GŒÀ Ω\\ÀK\\,pwrÃ6TÍÁ§\\8æb~€	ØbFíH^@|¬k_˜MáJÄÃ“BÄÂôÃœÁ4Ì%mnñ(–ñ:H#π´nhògTåÿ∑6A∫.kƒ≠“öbÌÖ∏Á`É`Ébw“fŸ.•ì≥G][˚£®˛Ì@[HPÒÉã0:6© Ö]\\ÌßMd\r2YÖr∂dÓ◊å,Ïïuÿ“d∆I«§}‹ÛX\\qÇA=ÏJ.íÜõ¡©¬ødi›7ü∫Uô∫nmÂö◊ƒfDÙYÒ∆ÖÆH˚Rí<9˙•XÕÛ¸'LÖΩuîV˘…B~¿ŸÑ∂lÆéMÌs—•ÁJÑ§∑a≈ë(á\\ˆäv8∂Õ˛Çq:.ÈÑ)Ω ˇ≥ÔJRÉgÌ<QßŒ·õDî\0î\rH∏ƒ—´“s££ÄñÊSGVgÌ9¥}°,¸õ„HZ}ß4hãGÅıãÏaFõã\$˛¥Î®Ö¬[πnzlÂ’Ñ6à0ê®ÿL‘ëTÄ—gˇ4˘ùvgÛz‹øØ¡9_\\5“≤ñ⁄'78Ï¿º∑c{Eã#›6KÖ∂6nsw†bjj8û Cı«ßùú◊8ä∂ÛF@G†0⁄âBñﬁ™¿ó¥CIÍS]ìa@Çã.`¶ÀªQj—ØÀ\"\0ıÇ=k)`rv¢»Ùµ|©GÅπΩ∫√’f;p-™ÚMÑ*fÂ%Õ·ƒË¿â‹Br≈B¿∏Ra:Œ4äP°5¥VıS6>Ó_ΩyQà.—ΩàÄÑÜÈ'&\rM√-~BS◊xGNBD%ˇá˛XqnüxÍS…Î€≈:æcç◊\"'kƒ0Æàå›ZØ‘[^Óâƒ%Ù…±Å\\œÂªºèª≤ùòwı¥,_w7”HÂªÍ+®:¶y=’	Ã.ıS;æ‹®ìb≥;\r⁄ÚÆ—?i˝>U—˘¨>—‡ lSœÏÒ|âª5*kË%@⁄\nÈ%7wıNWbbv∂‘p¬˛Ω™\$B˜⁄RA≤%´ÃêjˇY:ÀeÚl∂—¨}`G\$hÏ± ‰wEû\nˇ	’(\"ÀPáå\nßTˆﬁl]ÎœÖB|¬À1:?èﬂ )Ñ˙“ˆ«¿]>ñÛ˙gj?åH;ëF’-ÙÿÖZ6áñQdxÄÇÊµÅÚµçg±K∞s∫QÈ∏°π)Ê◊jº¬ìnWB®s›^∑G¢¿>/Wlå\$^¿ö}•â\0ôv¡«5AE\rJßÈy{æ0®P4∆∆-3#≥za∆å·T…y^ \nQ9.»·ºöçMö§}&∏Œ˘§Àj/2·¨9É/\0Ô´§Ÿ\\¬>RzfÀ1ˆÍ–¯´	‰√!«)È›rå–…Ø|\r…IÄw∑]ªìTŒ¿,ÀÊÒ˜e …á«w[—–±ëO]éHÁs≈Ä˚µùAÁ(@°’÷•16b≠cë¢Y⁄¢µ®ì≠p—Ûì\0U6æî»yp=]ƒú≥µá∫;GÔ(xSâ€ÃH™•1…éÀ†wbâ\0¥Ñ{•äÅ®?ç¡É`eY,?N¯Y5√Zoøõ¯\$ŒÃ\$‹’h'8Lf≥F:∂§k1)@ƒÔ_µàõ êP˚vp¯Èá\$£o∏:f˘e∏zŸuøT Z@ëÉº‚÷ﬁ8πÑçÒÒÇÏ‹b\\á¨⁄˛4J1#S¬õ/w«≠ÒÕ#X_ó±A«Ü•Ÿwâ8K:O‘ì˛Q·«xí=J4ã‰Eáº;Úz≠l©JÆ!ÿãâÒ.’7é˚RÂTÒ“ÃìÆWN©π¬eó\$≤_ºÓCjﬂëΩ‰RQyR˚Öé¶ëÒaÀÁ|ª2úòÖà⁄x0õÏ>1É´µjDLMﬁR7\\îlñRßcÈ¸’\rœiÁ≈wÀ€œéR,“¿€;‘’s´QA!)∫|ﬂÿBpo\$Ä]⁄SñxÖ:wP°ÓEO%ÙÍ∑õb_C\0‘Ï∞ê¨Ê‘ÎÊ-≥π≤áî8àF‚ËˆÜ–yjÂrrõ\\üò{_Ë¢Z.D≤õÖ/®çLÄ√ë8µÎ–ZΩ @Ip\0äÄ(◊ßú≥\$g(sw2C`·ﬂAåÄD/7ät3åÃdÁjuxª(Å_\$\"KÖÅI99Ñ›Ωë#ìınÂ˜T¬s`Çÿ9æ≈B]Îòô/òvæVs!-3É\$OS0^°\\º«mØ›≥9ãÕã\n°œ•8iÂ≤wÌ}cÓ{F-ï]mÂ≥Íä[3Û\$˚úß⁄ó^9éñÑû†∂8L6∞€£ÛçVªÀôºÚ\nÉç&Ÿ.hÔ—2]ê»äE{ÅV2ŒBAóhX”?8:∫ÊÎD‡S5ÇkZ\rYƒê@eú\\’˘%∞7?‘`(∂ë∏Á Î@Å:•’pvuﬂq‘~„Áπ©ΩÇ GféÒÑÕñh`ÄWqÆÙ^îï(ïÔ-∆õŒ/éç´ÎËƒ…Í‰oﬂqó˜Ëj©ÆkH¢˚Õ&≠e‰˛\0Ô·˘˚¸`É√¡a®ã˘|πí}X^d˚Hπ†D◊™ØuçÂ!ìG\\,q©4ö¶^xxF¯oΩ4∏◊å<5ﬁ˘&–6tPA|k\r9†Æ≤∏Aé&£˜JU&è!⁄	[¥[Üh hÖÄn0°∑}vÓw†,aÛ¯ê{≥>®\0à*\0O2%ç,ê®·Å‡Äy≥+îb:a¿SL‹◊X©ì@n¢Ä˝5>xCç~Í\$“£0\\Ô.J,W†4FŒ_c¥<®«≠Ëai¿Ä’ø}y£øOo7éº>r»®≈\"ùvasú\"ù¸®…-¬yQY˙B`-ëÙÁ\0⁄Ú˙∆©€–‡t»sUÍÂS(ä~\n+àπ‡Dﬁ–õê÷≠Qtƒ!Ûîè÷ÿ\0(ûÄ≠˚YT»‘ˆèCXz@¿®‘æ†∞°∆yÆöQQ|EZ)8îPS⁄_∑Jt*;E⁄5∑b~AfQ+3@ÆñË> 3ÑQÇÇÔxéÁﬁj˜¨7)ÃØ}¿„'ªÄ†=\\¥∫Àù˝†1Ë]‘Hsl◊ÃÚ@]ê‡ê+¥ ¶ˇΩ‚∏SÑ{O\"bæ◊©¿È«Ë†oÓÃ∫⁄ibﬂì\0ß·ﬂ’Á…°’±?ºrÔ\"Çvje—ÍGCöEÔ√~Lúé¡Tﬂ&Å/ ~V≠¸òø.ØÃüÕÚ/ö¢◊ÊÁïó“~v¢x|Ùçﬂ?PËo>Ü¸—Œ¸¡ø]?ŒïÄy°Ø{2ª;¯◊ö2Åükã„„ﬁÁ¯ü*¬Ô¿|^ªü+jZ‚…¡ ´›æ∞á√G˜Ø~«‡ç_ı¡•öø_«¸ãÚ|)æ˜Ú02¸áÏ†_ÂÒ·Ú‡ˇ£“ÌÌ@Mmˆ4®}\0ﬂBFxÈ†ºﬂß	:îÕ_õí®‡—ı‡†˛≥Î>®=J-@WÙ|˝ª¯_CUÓœÚ°ñáC˜É\"¸øÚ~ïÏ\néûuÀ.X\\Öœ¨R“z£‰ﬂ¿˛ÛøôXﬂ«˝∑È\\(MŸD|‚ùà™rö#Ïˇ/®™QÍUöóﬁ_Â‘Jìw÷ˇÈ˜B	É˛≈Û’OI=nx™0ÄËl„’°◊ÇÏˇ+‘jå¸îc-J1&X˜»[á¯t≥®a¸¿oß*ƒÖƒ	])|Q5‡@T d0¸8l/ÁÑ * ãê¶é•å@V|Æ¿∂Ó…÷ŒÌªÑÓË!ot∞f£ÛÈiÓµLÙ»pò'∫“b(7Ωﬂä&ãÊ2Í¡Õ®Ó.ËÉaîà<sø/˜hxH=ÄVògè)à”	Ê∞\$îh\0\$ÖÆ∆„Õ°â4∆Ù‚mÖNP”‰Öã–πÈmAıéH%hmÎ¥ c\"‹ÈÈ\n∑ë·#Ã¥«íc‚N\r˛= ·€Ç5a¨	®@”TÕ1Ö4”\"¢¢*ü\"YGàû&Œ§\nÀº§êLn\rº∞˜qéIoÇ:πa«\r\r»Mf†Dà\0Ë\0≤h‹\r^?öB\$áÉ‡†Ç8#aT`Ç†êﬂÅbÄËÕÊïæÿƒàêPPA∏8jEnüº/°æm\"!c3ÊÙa–eà˙î·_\0“ßÎº˚ôåjëvEÏEt61‘s\0N~˘\"†@ÓN¬Oä¡0\"(º0G¿Ê%Àí`9é·ëÛ?Bì≤Oa”xd∞C∆X\0áßÓ=T\rÏ*aX!C A<ﬁ{rƒÉ*");}elseif($_GET["file"]=="jush.js"){header("Content-Type: text/javascript; charset=utf-8");echo
lzw_decompress("v0úÅF£©Ã–==òŒFS	– _6M∆≥òËËr:ôEáCI¥ o:ùCÑîXcÇù\rÊÿÑJ(:=üEÜÅ¶a28°x∏?ƒ'Éi∞SANNë˘xsÖNB·ÃVl0õåÁS	úÀUlÅ(D|“ÑÁ P¶¿>öEÜ„©∂yHch‰¬-3EbìÂ ∏bΩﬂpE¡pˇ9.äèòÃ~\né?Kb±iw|»`«˜d.ºx8EN¶„!îÕ2ôá3©à·\ráç—YéÃËy6GFmYé8o7\n\r≥0§˜\0ÅDbc”!æQ7–®d8ã¡Ï~ë¨N)˘E–≥`ÙNsﬂ`∆S)–OÈó∑Á/∫<Åx∆9éoª‘Âµ¡Ï3n´Æ2ª!rº:;„+¬9àC»®Æâ√\n<Òç`»ÛØbË\\ö?ç`Ü4\r#`»<ØBe„B#§N ‹„\r.D`¨´jÍ4ˇéépÈar∞¯„¢∫˜>Ú8”\$…c†æ1…cú†°c†Í›Í{n7¿√°ÉAN RLi\r1¿æ¯!£(Êj¬¥Æ+¬Í62¿X 8+ ‚‡‰.\rÕŒÙÉŒ!xºÂÉh˘'„‚à6S\0RÔ‘ÙÒO“\nºÖ1(W0Ö„ú«7qúÎ:N√E:68n+é‰’¥5_(Æs†\r„îÍâ/mê6P‘@√EQÅ‡ƒ9\n®V-ã¡Û\"¶.:ÂJçœ8weŒqΩ|ÿá≥X–]µ›Y X¡eÂzW‚¸ é7‚˚Z1çÌhQfŸ„u£j—4Z{p\\AUÀJ<ıÜk·¡@º…ç√‡@Ñ}&ÑÅàL7U∞wuYhê‘2∏»@˚u† P‡7ÀAÜhËÃÚ∞ﬁ3√õÍÁXEÕÖZà]≠l·@Mplv¬)Ê ¡¡HWëë‘y>êYç-¯YüË/´ùõ™¡Ó†hC†[*ã˚F„≠#~Ü!–`Ù\r#0PÔCÀùóf†∑∂°Ó√\\Óõ∂á…Å^√%B<è\\Ωfàﬁ±≈·–›„&/¶OÇL\\jFù®jZ£1´\\:∆¥>ÅNπØXaF√A¿≥≤√ÿÕfÖh{\"s\n◊64á‹¯“Öº?ƒ8‹^pç\"Îù∞Ò»∏\\⁄e(∏PÉNµÏq[g∏¡rˇ&¬}Ph ‡°¿WŸÌ*ﬁÌr_sÀPáh‡º‡–\n€À√omıø•√Íó”#èß°.¡\0@ÈpdW ≤\$“∫∞Q€ΩTl0Ü æ√HdHÎ)öá€èŸ¿)P”‹ÿHêg‡˝U˛Ñè™BËe\rÜt:á’\0)\"≈tÙ,¥úí€«[è(D¯O\nR8!Ü∆¨÷ö‹lA¸VÖ®4†h‡£Sq<û‡@}√Î gK±]Æ‡Ë]‚=90∞Å'ÄÂ‚¯wA<ÇÉ–—a¡~ÄÚWöÊÉD|A¥ÜÜ2”XŸU2‡Èy≈äêä=°p)´\0P	òsÄµnÖ3ÓÅrÑf\0¢FÖ∑∫v“ÃGÆ¡I@È%§îü+¿ˆ_I`∂ÃÙ≈\r.É†N≤∫ÀKIÖ[î ñSJÚ©æaUfõSz˚É´MßÙÑ%¨∑\"Q|9Ä®Bcßa¡q\0©8ü#“<aÑ≥:z1Uf™∑>ÓZπlââπù”¿e5#U@iUG¬Çô©n®%“∞s¶ÑÀ;gxL¥pPö?BÁå Qç\\óbÑˇÈæíQÑ=7Å:∏Ø›°Q∫\r:ÉtÏ•:y(≈ ◊\n€d)π–“\n¡X;†ãÏéÍCaA¨\r·›ÒüP®GH˘!°†¢@»9\n\nAl~H†˙™V\ns™…’´ç∆Ø’bBr£™ˆÑí≠≤ﬂ˚3É\rûPø%¢—Ñ\r}b/âŒë\$ì5ßPÎC‰\"wÃB_Áé…U’gAtÎ§ÙÖÂ§ÖÈ^QƒÂU…ƒ÷jô¡Ì†BvhÏ°Ñ4á)π„+™)<ñj^ê<LÛ‡4U*†ıÅBg†Î–ÊË*nÅ ñË-ˇ‹ı”	9O\$¥âÿ∑zyMô3Ñ\\9‹Ëò.oä∂öÃÎ∏E(iÂ‡ûúƒ”7	tﬂöÈù-&¢\nj!\rÅ¿yúy‡D1g“ˆ]´‹yR‘7\"Êß∑Éà~¿Ì‡‹)TZ0E9MÂYZtXe!›fÜ@Á{»¨yl	8á;ê¶ÉR{ÑÎ8áƒÆ¡eÿ+ULÒ'ÇF≤1˝¯Ê8PE5-	–_!‘7ÖÛ†[2âJÀ¡;áHR≤È«πÄ8pÁó≤›á@ô£0,’ÆpsK0\rø4î¢\$sJæÅ√4…DZ©’I¢ô'\$cLîRÅñMpY&¸ΩèÕiÁz3GÕz“öJ%¡ÃP‹-Ñê[…/xÁ≥Tæ{p∂ßzãC÷vµ•”:ÉV'ù\\ñíKJa®√MÉ&∫∞£”æ\"‡≤eùo^Q+h^‚–iTÅ1™OR‰l´,5[›ò\$π∑)¨ÙjL∆ÅU`£SÀ`Z^|ÄárΩ=–˜nÁôªñòTU	1Hykõ«t+\0v·Dø\r	<ú‡∆ôÏÒjGîû≠t∆*3%kõY‹≤T*›|\"Cä¸lhEß(»\r√8rá◊{‹Ò0Â≤◊˛ŸD‹_åá.6–∏Ë;„¸áÑrBjÉO'€ú••œ>\$§‘`^6ôÃ9ë#∏®ßÊ4X˛•mh8:Í˚cã˛0¯◊;ÿ/‘â∑øπÿ;‰\\'(†ÓÑt˙'+ùôÚ˝ØÃ∑∞^Å]≠±N—vπÁ#«,Îv◊√Oœiùœñ©>∑ﬁ<SÔA\\Ä\\Óµ¸!ÿ3*tl`˜uÅ\0p'Ë7ÖP‡9∑bsú{¿vÆ{∑¸7à\"{€∆rÓa÷(ø^Êº›E˜˙ˇÎπg“‹/°¯ûUƒ9g∂Ó˜/»‘`ƒ\nL\nÅ)¿ÜÇ(A˙a\" ûÁÿ	¡&ÑP¯¬@O\nÂ∏´0Ü(M&©FJ'⁄! Ö0ä<ÔHÎÓ¬Á∆˘•*Ã|Ï∆*ÁOZÌm*n/bÓ/êˆÆê‘àπ.Ï‚©o\0Œ dnŒ)è˘èéiê:RéŒÎP2Ímµ\0/vÏOX˜¯F ≥œàÓåËÆ\"ÒÆÍˆÓ∏˜0ı0ˆÇ¨©Ì0bÀ–gj\$ÒnÈ0}∞	Ó@¯=M∆Ç0nÓPü/pÊotÏÄ˜∞®.ÃÃΩèg\0–)oó\n0»˜â\rF∂ÈÄ†bæi∂√o}\n∞ÃØÖ	NQ∞'xÚFa–JÓŒÙèLıÈ–‡∆\r¿Õ\rÄ÷ˆë0≈Ò'¨…d	oep›∞4D–‹ ê¶q(~¿Ã Í\rÇE∞€pr˘QVFHúl£ÇKj¶ø‰N&≠j!ÕH`Ç_bh\r1é†∫n!Õ…é≠zô∞°•Õ\\´¨\räÌä√`V_k⁄√\"\\◊Ç'Và´\0 æ`AC˙¿±œÖ¶V∆`\r%¢í¬≈Ï¶\rÒ‚ÉÇk@N¿∞¸ÅBÒÌöôØ ∑!»\ní\0Zô6∞\$d†å,%‡%laÌH◊\nã#¢S\$!\$@∂›2±çÑI\$rÄ{!±∞Já2H‡ZM\\…«hb,á'||cj~g–rÖ`ºƒº∫\$∫ƒ¬+ÍA1úEÄ«¿Ÿ < L®—\$‚Y%-FD™ädÄLÁÑ≥†™\n@íbVfËæ;2_(ÎÙLƒ–ø¬≤<%@⁄ú,\"Ídƒ¿NÇerÙ\0ÊÉ`ƒ§ZÄæ4≈'ld9-Ú#`‰Û≈ñÖ‡∂÷„j6Î∆£„v†∂‡N’Õêf†÷@‹Üì&íB\$Â∂(Z&ÑﬂÛ278I ‡ø‡P\rk\\èßó2`∂\rdLb@EˆÉ2`P( B'„Ä∂Ä∫0≤&†Ù{¬êïìß:Æ™dBÂ1Ú^ÿâ*\r\0c<Kê|›5sZæ`∫¿¿O3Í5=@Â5¿C>@¬W*	=\0N<gø6s67Sm7u?	{<&L¬.3~DƒÍ\r≈öØxπÌ),rÓin≈/†ÂO\0o{0kŒ]3>mãî1\0îI@‘9T34+‘ô@eîGFMC…\rE3ÀEtm!€#1¡D @ÇH(ë”n √∆<g,V`R]@˙¬«…3Cr7s~≈GIÛi@\0v¬”5\rVﬂ'¨†§†Œ£P¿‘\r‚\$<b–%(áDdÉãPWƒÓ–ÃbÿfO Êx\0Ë} ‹‚îlb†&âvj4µLSº®÷¥‘∂5&dsF MÛ4Ã”\".HÀM0Û1uL≥\"¬¬/J`Ú{«˛ßÄ x«êYu*\"U.I53Q≠3QÙªJÑîg†í5Ös‡˙é&j—åí’uÇŸ≠–™GQMTmGBÉtl-c˘*±˛\rä´Z7‘ıÛ*hs/RUV∑Ù™BüNÀà∏√Û„Í‘ä‡i®Lk˜.©¥ƒtÏ†Èæ©ÖrYiî’È-SµÉ3Õ\\öTÎOM^≠G>ëZQj‘áô\"§é¨iî÷MsS„S\$Ib	f≤‚—uÊ¶¥ôÂ:ÍSB|i¢†Y¬¶É‡8	v #ÈîD™4`áÜ.ÄÀ^ÛH≈Mâ_’ºäu¿ôU z`ZçJ	eÁ∫›@CeÌÎaâ\"mÛbÑ6‘ØJR¬÷ëTù?‘£XMZ‹Õ–ÜÕÚpË“∂™QvØjˇjV∂{∂º≈Cú\rµ’7âT û™ ˙Ì5{Pˆø]í\r”?Q‡AA¿ËéãíÕ2Òæ†ìV)Ji£‹-N99fñl JmÕÚ;u®@Ç<F˛—†æeÜjÄ“ƒ¶èIâ<+CW@ÅÁ¿øZël—1…<2≈iF˝7`KGò~L&+Nè‡YtWHÈ£ëw	÷ïÉÚlÄ“s'g…„q+LÈzbiz´∆ ≈¢–.–ä«zW≤« ˘zdïW¶€˜π(èy)v›E4,\0‘\"d¢§\$B„{≤é!)1UÜ5bp#≈}m=◊»@àwƒ	P\0‰\rÏ¢∑ëÄ`O|Î∆ˆ	ú…ç¸≈ı˚YÙÊJ’ÇˆE◊ŸOuû_ß\n`F`»}M¬.#1·Ç¨fÏ*¥’°µß  øz‡uc˚Äó≥ xf”8kZRØs2 Ç-ÜíßZ2≠+é ∑Ø(ÂsUıcDÚ—∑ Ïò›X!‡Õu¯&-vP–ÿ±\0'LÔåX ¯L√πåào	›Ù>∏’é”\r@ŸPı\rxF◊¸EÄÃ»≠Ô%¿„ÏÆ¸=5N÷úÉ∏?Ñ7˘NÀ√Ö©wä`ÿhX´98 ÃÅç¯Øq¨£z„œd%6ÃÇtÕ/Öïò‰¨ÎèL˙Õlæ ,‹KaïN~œ¿€Ï˙,ˇ'Ì«ÄM\rf9£wêò!xê˜x[àœëÿGí8;ÑxAò˘-IÃ&5\$ñD\$ˆº≥%Öÿx—¨¡î»¬¥¿¬å]õ§ıá&oâ-3ù9÷L˘Ωzç¸ßy6π;uπzZ Ë—8ˇ_ï…êx\0D?öX7Üô´íy±OY.#3ü8†ô«ÄòeîQ®=ÿÄ*òôGåwm ≥⁄ÑYë˘†¿⁄]YOY®F®ÌöŸ)Ñz#\$eäö)Ü/åz?£z;ôóŸ¨^€˙F“Zg§˘ï†Ã˜•ôßÉö`^⁄e°≠¶∫#ßìÿÒî©é˙?ú∏e£ÄM£⁄3uÃÂÅÉ0π> \"?üˆ@◊óXvï\"Áîåπ¨¶*‘¢\r6v~á√OV~ç&◊®Å^g¸†öƒëŸûá'ŒÄf6:-Z~πöO6;zxÅ≤;&!€+{9M≥Ÿ≥d¨ \r,9÷Ì∞‰∑W¬∆›≠:Í\r˙Ÿú˘„ù@ÁùÇ+¢∑]úÃ-û[gûô€á[s∂[iûŸi»qõõyõÈxÈ+ì|7Õ{7À|w≥}Ñ¢õ£Eñ˚W∞ÄWk∏|JÿÅ∂Ââxmà∏q xwyjüªò#≥òeº¯(≤©â∏çù¿ﬂû√æôÜÚ≥ {Ëﬂ⁄è†yì†ªMª∏¥@´Ê…Çì∞Yù(gÕö-ˇ©∫©‰Ì°ö°ÿJ(•¸Å@ÛÖ;Öy¬#SºáµYÑ»p@œ%Ësû˙oü9;∞ÍøÙı§π+Ø⁄	•;´¡˙àZNŸØ¬∫ßÑö kºVß∑uâ[ÒºxùÖ|qí§ON?Ä…’	Ö`uú°6ç|≠|Xπ§≠óÿ≥|OÏx!Î:è®úœóY]ñ¨πéôcï¨¿\rπhÕ9nŒ¡¨¨ÎçÄœ8'ó˘ÇÍ‡†∆\rS.1ø¢US»∏ÖºXâ…+À…z]…µ §?ú© ¿CÀ\r◊À\\∫≠π¯\$œ`˘Ã)UÃ|À§|—®x'’úÿÃ‰ <‡ÃôeŒ|ÍÕ≥Áó‚íÃÈóLÔœ›MŒyÄ(€ß–lè–∫§O]{—æ◊FDÆ’Ÿ}°yuã—ƒíﬂ,XL\\∆x∆»;U◊…WtÄvüƒ\\OxWJ9»í◊R5∑WiMi[áKàÄf(\0Êædƒö“Ëø©¥\rÏMƒ·»Ÿ7ø;»√∆Û“ÒÁ”6âK ¶I™\rƒ‹√xv\r≤V3’€ﬂ…±.Ã‡R˘¬˛…ç·|ü·æ^2â^0ﬂæ\$†QÕ‰[„øD˜·‹£Â>1'^X~tÅ1\"6Lù˛õ+˛æA‡ûe·ìÊﬁÂIëÁ~üÂ‚≥‚≥@ﬂ’≠ıpM>”m<¥“SK Á-H…¿ºT76ŸSMfg®=ª≈GP ∞õP÷\r∏È>Õˆæ°•2Sb\$ïC[ÿ◊Ô(ƒ)ûﬁ%Q#G`u∞«Gwp\rkﬁKeózhj”ìzi(ÙËrO´Ûƒﬁ”˛ÿT=∑7≥ÚÓ~ˇ4\"efõ~ÌdôÙÌVˇZâö˜Uï-Îb'VµJπZ7€ˆ¬)Të£8.<øRMˇ\$âûÙ€ÿ'ﬂbyÔ\n5¯É›ı_é‡wÒŒ∞ÌUí`eiﬁøJîb©guçSÕÎ?ÕÂ`ˆ·ûÏ+æœÔ MÔgË7`˘ÔÌ\0¢_‘-˚üı_˜ñ?ıF∞\0ìıç∏XÇÂ¥í[≤ØJú8&~D#¡ˆ{PïÿÙ4‹óΩ˘\"õ\0Ã¿Äã˝ßÅ˝@“ìñ•\0F ?*è†^ÒÔçπÂØwÎ–û:Åæu‡œ3xKÕ^Ûwìº®ﬂØây[‘û(ûÊñµ#¶/zr_îg∑Ê?æ\0?Ä1wMR&MøÜ˘?¨StÄT]›¥Gı:I∑‡¢˜à)á©BÔàã vÙßíΩ1Á<Ùt»‚6Ω:èW{¿äÙx:=»ÓëÉåﬁöÛ¯:¬!!\0xõ’ò£˜q&·Ë0}z\"]ƒﬁoïz•ô“j√w◊ﬂ ⁄¡6∏“J¢P€û[\\ }˚™`Sô\0‡§qHMÎ/7BíÄP∞¬ƒ]FT„ï8S5±/I—\rå\n ÅÓOØ0aQ\n†>√2≠jÖ;=⁄¨€dA=≠p£VL)Xı\n¬¶`e\$òT∆¶QJùÕÛÆÊlJÔä‘Ó—yÑIﬁ	‰:É—ƒƒB˘bP¿Ü˚ZÕ∏n´™∞’U;>_—\n	æıÎ–Ã`ñ‘uMÚåÇÇ¬÷çm≥’Û¬Lw˙B\0\\b8¢M‹ê[zëù&©1˝\0Ù	°\ròT÷◊õÅ†Ä+\\ª3¿Plb4-)%Wd#\n»ÂrﬁÂMX\"œ°‰(Ei11(b`@f“¥≠ÉS“ÛàjÂDÜùbf£}ÄrÔæë˝DëR1Öù¥b”òA€ÔIy\"µWv‡¡gC∏IƒJ8z\"P\\i•\\m~ZRπ¢vÓ1ZB5Iä√i@xîÜ∑∞-âuM\njK’U∞h\$oóàJœ§!»L\"#p7\0¥ PÄ\0äD˜\$	†GK4e‘–\$Å\nG‰?˘3£EAJF4‡Ip\0´◊Fé4±≤<f@û %q∏<k„wÄÅ	‡LOp\0âx”«(	ÄG>@°ÿÁ∆∆9\0T¿àòÏGB7†-†Äû¯‚G:<Qô†#√®”«¥˚1œ&tz£·0*J=‡'ãJ>ÿﬂ«8q°ç–•™‡Å	ÄO¿¢XÙF¥‡Qç,Å¿ –\"9ëÆp‰*66A'˝,yÄùIFÄRà≥Tàœ˝\"î˜H¿RÇ!¥j#kyF¿ô‡eë¨z£ÎÈ»G\0ép£âaJ`C˜i˘@úT˜|\nÄIx£K\"≠¥*®çTk\$c≥Ú∆îaAhÄì!†\"˙E\0OêdƒSxÚ\0T	ˆ\0Çû‡!F‹\níUì|ô#S&		IvL\"îìÖ‰\$h–»ﬁEAÔN\$ó%%˘/\nPÜ1öì≤{§Ô) <á†Lç†Â-R1§‚6ë∂í<Å@O*\0J@qπë‘™#…@«µ0\$tÉ|í]„`ª°ƒäA]ËÕÏP·ëÄòC¿p\\p“§\0ô“≈7∞ƒ÷@9©bêmàr∂o€C+Ÿ]•Jr‘f¸∂\rÏ)d§í—ú≠^hﬂI\\Œ. gñ >•Õ◊8åﬁ¿'ñH¿fôrJ“[rÁo„•Ø.πvÑΩÔ#Ñ#yR∑+©yÀ÷^Ú˘õÜF\0·±Åô]!…ï“ﬁî++Ÿ_À,©\0<@ÄM-§2WÚ‚ŸR,cïåúe2ƒ*@\0ÍP Ä¬c∞a0«\\P¡äàOÅ†¯`I_2Qs\$¥w£ø=:Œz\0)Ã`Ãhä¬ñ¡ÉàÁ¢\nJ@@ ´ñ\0ö¯ 6qTØÂá4J%ïN-∫m§ƒÂ„.…ã%*cn‰ÀNÁ6\"\rÕë∏ÚËó˚äf“Aµ¡ÑpıM€ÄI7\0ôM»>lOõ4≈S	7ôcÕÏÄ\"Ïﬂß\0Âì6ÓpsÖñƒ›Ây.¥„	Ú¶ÒRKïPAo1F¬tIƒb*…¡<á©˝@æ7–ÀÇp,Ôù0N≈˜:†®N≤m†,ùxO%Ë!Ç⁄v≥®ò†gz(–M¥Û¿I√‡	‡Å~yÀˆõh\0U:ÈÿOZyA8ù<2ß≤∏ usﬁ~lÚ∆ŒEòOî0±ü0]'Ö>°›…çå:‹Í≈;∞/Ä¬w“Ùù‰Ï'~3GŒñ~”≠ù‰˛ßc.	˛ÑÚvT\0cÿt'”;P≤\$¿\$¯ÄÇ–-Çs≥Úe|∫!ï@d–Obw”Êc¢ı'”@`P\"xÙµË¿0Oô5¥/|„U{:b©R\"˚0Ö—àkò–‚`BDÅ\nkÄPù„c©·4‰^ p6S`è‹\$Îêf;Œ7µ?ls≈¿ﬂÜgD '4Xja	AáÖE%ô	86b°:qr\r±]C8 c¿F\n'—åf_9√%(¶ö*î~ä„iSË€ê…@(85†TîÀ[˛ÜJ⁄ç4ÅIÖl=∞éQ‹\$d¿Æh‰@D	-ÅŸ!¸_]…⁄Hñ∆äîk6:∑⁄Ú\\M-ÃÿÚ£\rëFJ>\n.ëîqêeG˙5QZç¥Üã' …¢ûΩê€Å0üÓÅzPñ‡#≈§¯ˆ÷Èr‡“ÌtΩí“œÀé˛ä<QàèT∏£3èD\\πÑƒ”pOE¶%)77ñWtù[∫Ù@ºõéö\$F)Ω5qG0´-—W¥v¢`Ë∞*)Rr’®=9qE*K\$g	ÇÌA!ÂPjBT:óK˚ßç!◊˜Hì R0?Ñ6§yA)B@:QÑ8B+Jç5U]`Ñ“¨ùÄ:£Â*%Ip9åÃÄˇ`KcQ˙Q.Bî±Ltb™ñyJÒùEÍõTÈ•ı7ïŒˆAm”‰¢ïKu:éSjió 5.q%LiF∫öTr¶¿i©’Kà“®zó55T%UïâU⁄I’Ç¶µ’Y\"\nS’mÜ—ƒx®ΩCh˜NZ∂UZùîƒ( BÍÙ\$YÀV≤„Äu@ËîªíØ¢™|	Ç\$\0ˇ\0†oZw2“Äx2ëù˚k\$¡*I6I“nï†ï°ÉI,Ä∆QU4¸\nÑ¢).¯QêÙ÷aI·]ô¿†ËL‚h\"¯f¢”ä>ò:Z•>L°`nòÿ∂’Ï7îVLZuîÖe®ÎX˙ËÜ∫Bø¨•Bâ∫í°êZ`;Æ¯ïJá]Ú—Äû‰S8º´f \n⁄∂à#\$˘jM(πëﬁ°îÑ¨ùa≠GÌßÃ+A˝!ËxL/\0)	Cˆ\nÒW@È4êÄ∫è·€©ï ä‘RZÉÆ‚†=ò«Ó8ì`≤8~‚Üh¿ÏP Å∞\rñ	∞ûÏD-FyX∞+ f∞QSj+XÛ|ï»9-í¯s¨xêÿ¸ÜÍ+âV…cbpÏøîo6H–q†∞≥™»@.Äòl†8gΩYMü÷WMP¿™U°∑YLﬂ3PaËH2–9©Ñ:∂a≤`¨∆d\0‡&Í≤YÏﬁY0Ÿò°∂Så-óí%;/áT›BS≥P‘%fêÿ⁄˝ï†@ﬂFÌ¨(¥÷ç*—q +[ÉZ:“QY\0ﬁ¥ÎJUY÷ì/˝¶Üpkz»àÚÄ,¥™áÉj⁄ÍÄ•W∞◊¥e©JµFËç˝VBIµ\r£∆pFõNŸÇ÷∂ô*’®Õ3k⁄0ßDÄ{ôÅ‘¯`qôï“≤Bqµe•Dâc⁄⁄‘V√E©Ç¨nÅÒ◊‰FG†Eõ>jÓË–˙Å0g¥a|°ShÏ7u¬›Ñç\$ïÜÏ;aÙó7&°Î∞R[WXÑ ÿ(q÷#ùå¨Pπ∆‰◊ñ›c8!∞H∏‡ÿVXßƒé≠j¯ ZéÙë°•∞Q,DUaQ±X0ë’’®¿›ÀGb¡‹läBät9-oZ¸îçL˜£•¬≠ÂpÀáëx6&ØØMy‘œs“êøñË\"’ÕÄËRÇIWU`c˜∞‡}l<|¬~ƒw\"∑vI%r+ÅãR‡∂\n\\ÿ˘√—][ã—6è&¡∏›»≠√aî”∫Ï≈jπ(⁄ìT—ì¿∑C'äÖ¥ '%de,»\nñFC≈—çe9CπN‰–çÇ-6îUe»µå˝CX∂–V±Éùπ˝‹+‘R+∫ÿîÀï3B‹Å⁄åJ¢Ëôú±ÊT2†]Ï\0PËa«t29œ◊(iã#Äa∆Æ1\"SÖ:ˆ∑†à÷oF)kŸfÙÚƒ–™\0Œ”ø˛’,À’wÍÉJ@Ï÷VÚÑéµÈq.e}KmZ˙€ÔÂπXnZ{G-ª˜’ZQ∫Ø«}ë≈◊∂˚6…∏µƒ_ûÿÅ’â‡\n÷@7ﬂ` ’ÔãòC\0]_ ç© µ˘¨´Ôª}˚G¡WW: fCYk+È⁄b€∂∑¶µ2S,	⁄ãﬁ9ô\0ÔØÅ+˛WƒZ!Øe˛∞2˚Ù‡õóÌ≤k.OcÉ÷(vÃÆ8úDeG`€á¬åˆL±ıì,ÉdÀ\"C »÷B-îƒ∞(˛ÑÑÑp˜Ì”p±=‡Ÿ¸∂!˝kíÿ“ƒºÔ}(˝— Bñkrç_RÓó‹º0å8a%€òL	\0ÈÜ¿Òâb•≤öÒ≈˛@◊\"—œr,µ0T€rV>àÖ⁄»Qü–\"ïrﬁ˜Pâ&3b·P≤Ê-†xÇ“±uW~ç\"ˇ*ËàûåN‚hó%7≤µ˛K°YÄÄ^A˜Æ˙ CÇË˛ªp£·Óà\0..`c≈Ê+œä‚GJ£§∏Hø¿ÆEÇÖ§æl@|I#Ac‚ˇDêÖ|+<[c2‹+*WS<àr‡„g∏€≈}âä>iÅ›ÄÅ!`f8ÒÄ(c¶ÅË…Q˝=fÒ\nÁ2—c£h4ñ+qùèÅ8\na∑R„B‹|∞Rì◊Íø›mµä\\q⁄ıgX¿†ñçœé0‰X‰´`nÓFÄÓÏåO p»ÓHÚCÉîjd°fµﬂEuDVòêbJ…¶øÂ:±ÔÄ\\§!m…±?,TIaòÜÿaT.LÄ]ì,Jèå?ô?œîFMct!aŸßRÍFÑG!πAıìªrrå-péXü∑\rªÚC^¿7Å·&„RÈ\0Œ—f≤*‡A\nı’õH·„§yÓY=«˙ËÖlÄ<áπAƒ_πË	+ëŒtA˙\0Bï<AyÖ(fyã1ŒcßO;pùË≈·¶ù`Áí4–°MÏ‡*úÓfÜÍ 5fvy {?©‡À:y¯—^c‚Õuú'áôÄ8\0±º”±?´ägö”á 8BçŒ&p9÷O\"z«ıûrsñ0∫ÊBë!uÕ3ôf{◊\0£:¡\n@\0‹¿£ÅpêŸ∆6˛v.;‡˙©Ñ b´∆´:J>ÀÇâÈ-√BœhkR`-‹ÒŒawÊxEj©Ö˜¡rû8∏\0\\¡ÔÙÄ\\∏Uhmõ ˝(m’H3Ã¥ÌßSôì¡Êq\0˘üNVh≥Hyç	óª5„MÕée\\gΩ\nÁIP:Sj¶€°Ÿ∂Ë<éØ—xÛ&åL⁄ø;nfÕ∂cÛqõ¶\$f&lÔÕ˛i≥Öú‡Á0%yŒûætÏ/π˜gUÃ≥¨dÔ\0e:√ÃhÔZ	–^É@Á†˝1Äœm#—NèÛw@åﬂOzGŒ\$Ú®¶m6È6}Ÿ““ãöX'•I◊i\\Q∫YùÄ∏4k-.Ë:yz—»›Hø¶]ÊÊxÂGœ÷3¸øM\0Ä£@z7¢Ñ≥6¶-DO34ùﬁã\0Œöƒ˘Œ∞t\"Œ\"vC\"JfœR û‘˙ku3ôMŒÊ~˙§”é5V ‡Ñj/3˙É”@gGõ}DÈæ∫B”Nq¥Ÿ=]\$ÈøIáı”ûî3®x=_jãXŸ®ùfk(C]^jŸM¡ÕF´’’°å‡œ£Cz»“Vú¡=]&û\r¥A<	Êµ¬¿‹„Á6Ÿ‘Æ∂◊¥›`jk7:gÕÓë4’Æ·ÎìYZq÷ftuù|çh»Z““6µ≠i„Ä∞0†?ÈıÈ™≠{-7_:∞◊ﬁêt—ØÌckã`YÕÿ&ì¥ÈùIılP`:ÌÙ j≠{hÏ=–f	‡√[byû¢ Äo–ãB∞RSóÄºB6∞¿^@'Å4Ê¯1U€Dq}Ï√N⁄(XÙ6j}¨c‡{@8„Ú,¿	œPFC‡âB‡\$mvòù®PÊ\"∫€Lˆ’CS≥]õè›‡EŸﬁœlUÜ—fÌwh{oç(ó‰)Ë\0@*a1Gƒ (†ÅD4-cÿÛP8ù£N|RõÜ‚VM∏∞◊n8G`e}Ñ!}•Ä«pªá‹Ú˝@_∏Õ—nCt¬9é—\0]ªu±ÓØsªä›~Ërßª#Cn†p;∑%ã>wu∏çﬁn√w˚§›ûÍ.ù‚‡[«›hT˜{∏›ÂÄº	Á®ÀÅá∑Jç‘∆óiJ 6ÊÄOæ=°Äá˚ÊﬂEî˜Ÿ¥êëIm€Ô⁄V'…ø@‚&Ç{™ëõÚˆØµê;Ìop;^ñÿ6≈∂@2ÁØl˚‘ﬁNÔ∑∫M…ørÄ_‹∞À√ç¥` Ï( yﬂ6Á7ëπ˝ÎÓ«Çìè7/¡pe>|ﬂ‡	¯=Ω]–ocÅ˚ë·&ÂxNmç£âÁÉª¨‡o∑G√N	póÇªòx®ï√Ω›Éy\\3‡è¯á¬Ä'÷I`r‚G˜]ƒæÒ7à\\7⁄49°]≈^pá{<Z·∑∏q4ôuŒ|’€Q€ô‡ıpô˝öi\$∂@oxÒ_<Å¿Ê9pBU\"\0005çó i‰◊Çª∏C˚p¥\nÙi@Ç[„ú∆4ºj–ÅÑ6bÊPÑ\0ü&F2~é¿˘£ºÔU&ö}æΩçø…ò	ôÃDa<ÄÊzx∂k£àã=˘Ò∞r3ÈÀ(l_îÅÖFeFõùû4‰1ìK	\\”éldÓ	‰1ÅH\rΩÄ˘p!Ü%bGÊXfÃ¿'\0»úÿ	'6¿ûps_õ·\$?0\0í~p(ÅH\nÄ1ÖW:9’Õ¢Øò`ãÊ:h«BñËgõBäk©∆pƒ∆ÅÛtºÏàEBI@<Ú%√∏¿˘` ÍäyÅd\\Y@DñP?ä|+!Ñ·W¿¯.:üLeÄv,–>qÛA»Á∫:ûñÓbYÈà@8üd>r/)¬BÁ4¿–Œ(Å∑ä`|È∏:t±!´ã¡®?<Ø@¯´í/•†SíØP\0¬‡>\\Ê‚ |È3Ô:V—uw•ÎÁx∞(Æ≤üú4Ä«ZjD^¥•¶L˝'ºÏƒC[◊'˙∞ßÆÈj¬∫[†E∏Û u„∞{KZ[sÑûÄ6àÇS1ùÃz%1ıcô£B4àB\n3M`0ß;ÁÚÃ¬3–.î&?°Í!YA¿I,)ÂïlÜW['∆ I¬áTjÉÅË>F©º˜Sßá†B–±P·ªca˛«åuÔ¢N›œ¿¯H‘	LSÙçÓ0î’Y`¬∆»\"ilë\rÁB≤Î„/åÙ„¯%PÄœ›NîGÙù0J∆X\n?aÎ!œ3@MÊF&√≥÷˛øê,∞\"ÓÄËlbÙ:KJ\rÔ`k_Íb˜¸A·ŸƒØÃ¸1—I,≈›Ó¸à;B,◊:ÛæÏY%ºJ†éä#vîÄ'Ü{ﬂ—¿„Ñû	wx:\ni∞∂≥í}c¿∞eNÆ—Ô`!wù∆\0ƒBRU#ÿS˝!‡<`ñê&v¨<æ&ÌqO“+Œ£•sfL9èQ“B áÑ…Û‰èb”‡_+Ô´*ÄSu>%0Äéô©Ö8@l±?íL1po.ƒC&ΩÌ…†B¿ qhò¶Û≠í¡ûz\0±`1·_9\"ñÄË!ê\$¯å∂~~-±.º*3r?¯√≤¿dôs\0ÃıÅ»>z\n»\0ä0†1ƒ~ëÙòJ≥˙î|SﬁúÙ†k7gÈ\0å˙K‘†d∂Ÿa…ÓPg∫%„wìDÙÍzm“˚»ı∑)øëÒäújã€◊¬ˇ`kª“ÅQ‡^√Œ1¸å∫+ŒÂú>/wb¸GwOk√ﬁ”_Ÿ'É¨-CJ∏Â7&®¢∫EÒ\0L\r>ô!œqÃÅÓê“7›¡≠ıoäô`9O`à‡Éîˆ+!}˜P~EÂN»cîˆQü)Ï·#˚Ô#ÂÚáÄÏáÃ—¯¿ë°ØËJÒƒz_u{≥€K%ë\0=Û·OéX´ﬂ∂C˘>\n≤ÄÖ|w·?∆FÄ≈ÍÑ’añœ©UêŸÂ÷b	N•YÔ…häΩªÈë/˙˚)ﬁGŒå2¸ô¢K|„±y/ü\0È‰øZî{ÈﬂP˜YG§;ı?Z}T!ﬁ0ü’=mNØ´˙√fÿ\"%4ôaˆ\"!ñﬁüÅ˙∫µ\0ÁıÔ©}ªÓ[ÚÁ‹æ≥ÎbU}ª⁄ïmı÷2±ï†Öˆ/t˛Óë%#è.—ÿñƒˇseÄBˇp&}[Àüé«7„<a˘K˝ÔÒ8Ê˙P\0ôÛ°gºÚ?ö˘,÷\0ﬂﬂàr,†>øå˝W”˛Ô˘/÷˛[ôq˝êk~ÆC”ã4€˚GäØ:ÑÄX˜òG˙r\0…Èü‚Ø˜üL%VFLUcØﬁ‰ë¢˛éHˇybPÇ⁄'#ˇ◊	\0–ø˝œÏπ`9ÿ9ø~ÔÚó_º¨0q‰5K-ŸE0‡bÙœ≠¸ö°éút`lmÍÌÀˇbå‡∆ò; ,=ò†'SÇ.b ÁSÑæ¯CcóÉÍÎ çAR,ÑÉÌ∆Xä@‡'Öú8Z0Ñ&ÏXnc<<»£3\0(¸+*¿3∑ê@&\r∏+–@h, ˆÚ\$Oí∏Ñ\0≈íÉËt+>¨¢ãúb™Ä ∞Ä\r£><]#ı%É;NÏsÛÆ≈éÄ¢ *ªÔc˚0-@Æ™LÏ >ΩYÅp#–-Üf0Ó√ ±a™,>ª‹`è∆≈‡P‡:9ååo∑∞ovπR)e\0⁄¢\\≤∞¡µ\nr{√ÆXô“¯Œ:A*€«.êDı∫7ÅéªºÚ#,˚N∏\réEô‘˜hQK2ª›©•Ωz¿>P@∞∞¶	T<“ =°:Ú¿∞X¡GJ<∞GAfı&◊A^p„`©¿–{˚‘0`º:˚Ä);U !–e\0Ó£ΩœcÜp\rã≥†ãæ:(¯ï@Ö%2	SØ\$Y´›3ÈØhC÷Ïô:Oò#œ¡LÛÔ/ùöÈÇÁ¨k,ÜØKÂoo7•BD0{Éê°jÛ†Ïj&X2⁄´{Ø}ÑRœx§¬v¡‰˜ÿ£¿9AÎ∏∂æ0â;0Åı·ë‡-Ä5Ñà/î<‹Á∞ æN‹8EØëó«	+„–Ö¬Pd°Ç;™√¿*nüº&≤8/jX∞\rêö>	PœêW>K‡ïOí¢Vƒ/î¨U\n<∞•\0Ÿ\nIÅk@ä∫„¶É[‡»œ¶¬≤ú#é?ÄŸ„%ÒÉÇËÀ.\0001\0¯°kË`1T∑ ©ÑæÎÇ…êlºêö¿£Ó≈pÆ¢∞¡§≥¨≥Ö< .£>Ìÿ5é–\0‰ª	O¨>k@Bnæä<\"i%ï>ú∫zƒñÁìÒ·∫«3ŸPÉ!\r¿\"¨„¨\r â>öad‡ˆÛ¢U?⁄«î3P◊¡j3£‰∞ë>;”‰°ø>ût6À2‰[¬ﬁæM\r†>∞∫\0‰ÏPÆÇ∑BË´Oe*RÅn¨ßúy;´ 8\0»À’oÊΩ0˝”¯i¬¯˛3 Ä2@ ˝‡£ÓØ?xÙ[˜Ä€√LˇaéØÅÉw\ns˜àáåA≤øx\r[—a™6¬clc=∂ ºX0ßz/>+ö™â¯W[¥o2¬¯å)eÓ2˛HQPÈDYìzG4#YDÖˆÖ∫p)	∫H˙pêéò&‚4*@Ü/:ò	·âTò	≠ü¶aH5ëÉÎh.ÉA>úÔ`;.ü≠ÓYì¡a	¬Ú˙t/ =3Ö∞BnhD?(\nÄ!ƒB˙sö\0ÿÃD—&DìJèë)\0áj≈QƒyêéhDh(ÙKë/!–>Æh,=€ı±Ü„tJÄ+°Sı±,\"M∏ƒø¥N—1ø[;¯–¢äº+ı±#<ÏåI§ZƒüåPë)ƒ·LJÒDÈÏP1\$ƒÓıºQë>dOëºvÈ#ò/mh8881N:ù¯Z0Zä¡ËT ïBÛC«q3%∞§@°\0ÿÔ\"ÒXD	‡3\0ï!\\Ï8#ÅhºvÏibœÇTÄ!d™óàŒ¸V\\2Û¿SÎ≈≈í\nA+ÕΩpöx»iD(Ï∫(‡<*ˆ⁄+≈’E∑ÃTÆæ†BËS∑C»øT¥ÊŸƒ eÑAÔí\"·|©uºv8ƒT\0002ë@8D^ooÉÇ¯˜ë|îN˘òÙ•ê J8[¨œ3ƒ¬ıÓJçz◊≥WL\0∂\0ûÄ»Ü8◊:y,œ6&@î¿ êE£ Ø›ëh;º!fòº.B˛;:√ Œ[Z3•ô¬´ÇnªÏÎ»ë≠ÈA®í”qP4,ÑÛ∫Xc8^ªƒ`◊ÉÇÙl.Æ¸∫¢S±hﬁî∞ùÇO+™%P#Œ°\n?€‹IBΩ eÀëÅO\\]Œ¬6ˆ#˚¶€ΩÿÅ(!c)†Nı∏∫—?EÿîB##D ÌDdoΩÂPèA™\0Ä:‹n¬∆üÄ`  ⁄ËQÑ≥>!\r6®\0ÄâV%cbÅHF◊)§m&\0B®2IÌ5íŸ#]˙òÿD>¨Ï3<\n:MLê…9CÒè ò0„Î\0êì®(·è©H\n˛Ä¶∫MÄ\"GR\n@Èè¯`[√ÛÄäò\ni*\0ú)à¸ÄÇêÏu©)§´Hp\0ÄNà	¿\"ÄÆN:9q€.\r!çç¥J÷‘{,€'ÊŸÅä4ÖBÜ˙«lq≈®üXc´¬4ﬂãN1…®5´WmÅ«3\nÅ¡FÄÑ`≠'ëà“äx‡É&>z>N¨\$4?Ûõ√Ôè¬(\nÏÄ®>‡	ÎœµP‘!CqÕåºåp≠qGLqqˆG≤yÕH.´^‡û\0z’\$ÄAT9FsÜ–Ö¢D{Ìaß¯cc_ÄG»zÜ)Û≥á ‹}Q∆≈hÛÃHB÷∏ç<Çy!L≠ìÄ€!\\Ç≤àÓ†¯'íH(Ç‰-µ\"Éin]ƒûà≥≠\\®!⁄`MòH,g»éÌª*“KfÎ*\0Ú>¬Ä6∂à‡6»÷2ÛhJÊ7Ÿ{nq¬8‡ﬂÙç…H’#cèH„#ò\rí:∂ñ7 8‡‹ÄZ≤òZrD£˛ﬂ≤`rG\0‰l\nÆIçài\0<±‰„Ù\0LgÖ~ê®√E¨€\$π“Pì\$ä@“P∆ºT03…HGH±l…Q%*\"N?Î%úñ	ÄŒ\nÒCrW…C\$¨ñpÒ%âuR`¿À%≥ÚR\$ñ<ë`÷Ifx™Ø˜\$/\$ÑîÅ•Å\$úöíOÖ(ãèÀ\0ÊÀ\0èRYÇ*Ÿ/	Í\r‹úC9ÄÔ&hh·=I”'\$ñRRI«'\\ïa=E‘ÑùÚu¬∑'ÃôwIÂ'TíÄÄë¸ˇ©æ„K9%òd¢¥∑Ç!¸îÅ¿  ¿“jÖÏ°Ì” &–ÊÑvÃü≤\\=<,úE˘å`€Y¡Ú\\ü≤Ç§*b0>≤rÆ‡,dñpdååÃ0DD Ãñ`‚,T ≠1›% Pëû§/¯\rÚbπ(å£ıJ—ËÕÓT0Ú``∆æﬁËÌÛJît©í© ü((d« ™·h+ <…à+H%iá»Ùã≤ï#¥`≠ ⁄ —'Ù£B>tòØJÄZ\\ë`<JÁ+hR∑ ‘8ÓâÄ‡hR±,J]gÚ®I‰ïË0\n%Jπ*–Y≤Ø£JwDú∞& ñD±Æï…–ú™RßK\"ﬂ1QÚ®À î≤AJKC,‰¥mVíªé≤õ Ÿ-±ÚœKI*±r®É\0«L≥\"∆Kb(¸™çÛJ:qKr∑d˘ ü-)¡ûÀÜ#‘∏≤ﬁ∏[∫Aª@ï.[ñ“® ºﬂ4∫°Ø.ô1ÚÆJΩ.ÃÆ¶u#Jìá¡g\0∆„Úëß£<À&îíK§+Ω	M?Õ/d£ %'/õø2Y»‰>≠\$Õ¨l∫\0Ü©+¯ó¡â}-t∫íÕÖ*ÍâR‰\$ﬂîÚÃKª.¥¡≠ÛJH˚ âá2\rÑøBèÇΩ(PÕ”Ã6\"¸ñnfÜ\0#–á ÆÕ%\$ƒ [Ä\n–noùLJ∞å≈”¬e'<ØÛÖá1KÌ¡yÃY1§«s•0¿&zLf#¸∆≥/%y-≤À£3-Ñ¬íÕKê£L∂ŒÅ…◊0ú≥íÎ∏[,§ÀÃµ,ú±í´Ñß0î±”(ã.D¿°@œ¡2ÔL+.|£í˜§…2Ë(≥L•*¥πS:\0Ÿ3¥ÃÌÛG3lÃ¡aÀêl≥@L≥3z4≠«Ω%ÃíÕL›3ªÖ≥º!0ä33=L˘4|»ó°‡+\"∞ È4¥ÀÂ7À,\$¨SPMë\\±Œ?JäYìÃ°πΩ+(¬a=K®Ï4ú§≥CÃ§<–ÅÖ=\$ç,ª≥UJ]5h≥W†&t÷I%ÄÈ5¨“≥\\M38g¢ÕÅ5HäN?W1Hö±^ Ÿ‘∏ìYÕóÿ†èÕè.ÇN3Mü4√Ö≥`Ñéi/Pâ7÷dM>ödØ/ùLRŒ‹‚=Kë60>ØI\0[ı\0ﬂÕ\r2Ù‘ÚZ@œ1Ñ€2ˇ∞7»9‰FG+‰Ø“ú≈\r)‡hQtL}8\$ BeC#¡ìr*H»€´é-õH˝/ÿÀ“6»ﬂ\$¯RC9¬ÿ®!ÇÄ≈7¸k/PÀ0Xr5É°3DêÑº<T¡‘íqØKÙ©≥nŒHß<µFˇ:1SLŒr¿%(ˇçu)∏Xró1—ÄnJ√IÃ¥S£\$\$È.Œá9‘È≤IŒü“3 ®L√lîìØŒô9‰≈CïN†#‘°Û\$µ/‘Èsù…9´@6 tì≤ÆNÒ9º¥∑N…:πí¬°7Û†”¨Õ:D·”¡M)<#ñ”√M}+Ò2ŒN˛Ò≤õO&Ñ¢JNy*åÚÚŸ∏[;ÒÛŒO\"m⁄ƒÛ≈Mı<c†¬¥Ç∞±8¨K≤,¥”«N£=07s◊JE=T·≥∆O<‘Ù≥£JÈ=Dì”:œC<Ãì‡Àâ=‰ËÛÆKê ªÃ≥»L3¨˜≠èÑLT–Ä3 S,ú.®ˇœq-åÒsÁ7Õ>Ç?Ûº7O;‹†`˘OA9¥ÛÒœª\$ú¸¡O—;Ï˝`9Œn«IÅAåxp‹ˆE=Oπ<¸≤5œŒÑ˝2∏Oç?d¥éÑ¥å`NÚiOˇ>å˛3ΩP	?§Ú‘Oûmú˙SMÙÀ¨∑Ü=π(„d„§A»≠9èìë\0Ì#¸‰≤@É≠9Déç¡…&‹˝ÚäÇ?ú†ì–i9ª\n‡/ÄÒA›ÛÚ»≠A§˝SÀPo?kuN5®~4‹„∆6ÜÜÿ=Úñåì*@(ÆN\0\\€îdGÂ¸p#Ë§>†0¿´\$2ì4z )¿`¬Wò†+\0äë80£Ëè¶ï†§™î‰z\"T–‰0‘:\0ä\ne \$ÄérMî=°r\n≤NâP˜Cmt80˙ #§ÿJ=†&–∆3\0*ÄùB˙6Ä\"ÄàÈË˙Ä#èÃ>ò	†(Q\nåÍ¥8—1C\rt2ÉECà\n`(«x?j8Nπ\0®»[¿§QN>£©‡'\0¨x	cÍ™\n…3è◊Ch¸`&\0≤–¥8—\0¯\n‰µ¶˙O`/ÄÑç¢A`#–ÏêXcË–œD ˇtR\n>ºÅ‘d—BÚD¥L–ƒÃıâ‰–ÕDt4–÷†jîpµGAoQoG8,-s—÷‘K#á);ßE5¥TQ—G–4Ao\0†>tM”D8yRG@'PıC∞	Ù<PıCÂ\"îK\0íêx¸‘~\0™ei9–Ïúv))—µGb6âÄ±H\r48—@ÇMâ:Ä≥FÿtQ“!Hïî{R} ÙURpèÕ‘O\0•IÖt8§ÿ˚Œ«[D4F—Dç# —+DΩ'ÙMè ï¿>RgI’¥äQÔJ®îîU“)Em‡è¸TZ≠Eµ'„Í£iE›¥£“qFzA™∫>˝)TãQ3H≈#TL“qIjNTΩºÖ&C¯“hçX\nTõ—ŸK\0000¥5Äà¢JH—\0ìFE@'—ôFp¥hS5Fù\"Œo—Æêe%aoS E)† ÄìDU†´QóFmŒ—£M¥——≤e(tn“ ìU1‹£~>ç\$Òﬂ«Çí≠(h’«ëG¸y`´\0íÍ†	ÉÌGÑÚ3‘5Sp(˝ıP„GÌ\$îú#§®	©Ü©N®\nÙV\$ˆç]‘úP÷=\"R”®?Lzt∑É1L\$\0‘¯G~Â†,âKN˝=îÎ“GM≈îÖ§NSÄ)—·O]:‘äS}›81‡RGe@CÌ\0´OPSıNÕ1Ù›T!Pï@—›SÄˇ’SâG`\n…:ÄìP∞jî7RÄ @3¸—\në ¸„˜è‚£îD”†Ê˙L»œºé†	ËÎ\0˘Q5Ùµ©CP˙µSMP¥v4Ü∫?h	hÎTáD0˙—÷è‡ı>&“ITxÙOº?ï@U§˜R8@%‘ñåıKâÄßNÂK„ÛRyE≠E#˝˘ @˝√¯‰%L‡´Q´Q®µ£™?N5\0•R\0˙‘ÅTÎFÂ‘îRüSÌ!oTE¬C(œ∂ê»˝ƒµ\0Ñ?3iÓSS@U˜QeMµÉ	Kÿ\n4P’CeSîë\0ùNC´PÇ≠Oı!†\"RTê˚ıÄèS•N’è¡U5OU>UiI’PU#UnKPÙ£UYTË*’Cè´U•/\0+∫∏≈)»⁄:ReA‡\$\0¯é§xÚ«WD∫3√Íè‡`¸⁄¸ÁU5“IHUYîÙ:∞P	ıe\0ñMJiÄÉµ√˝Q¯>ı@´T±C{õ’u—Ï?’^µv\0WRç]U}CˆÍ1-5+U‰?Ì\rıW<∏?5ïJU-SX¸’L‘ﬂ \\t’?“sM’bÑ’ÉV‹ÅtßTå>¬MU+÷	E≈càœ‘9Nm\rR«ÉC˝8éS«Xï'R“ÈXjCI#G|•!QŸGhïtQç∏˝ )<πY–*‘–RmX0¸ÙˆΩM£õıOQﬂY˝h¿´ﬂdu’§’Z(˝Ao#•NlyN¨VÄZ9I’ç∫Mï¶V´ZuO’ÖT’T≈E’á÷∑SÕeµµ÷ \nµXµ™S€QERµ≥‘Ÿ[MF±VÁO=/ı≠è®>ıg’πTÌVçoUèT≥ZíNÄ*T\\*√Ô–◊S-pµS’√V’qÄ“M(œQ=\\ç-UUUV≠Cïƒ◊Zÿ\nuíV\$?M@UŒWJ\r\rU–‘\\Â'U◊W]ÖWî£W8∫N†'#h=oCÛ–˝F(¸È:9’YuïÜ§˜V-U”9ü]“C©:Uø\\ê\nµqWóô‡(TT?5P·™\$ R3’‚∫üC}`>\0ÆE]à#RÍ‡	Éˇ#R•)≤Wñíù:`#ÛGı)4äR¿˝;ı·ViD%8¿)«ì^•QıÈ#îh	¥H¬éX	É˛\$N˝x¥ö#i x˚‘íXRıÄ'‘9`m\\©Ü®\nE¿¶Q±`•bu@◊ÒN•dT◊#YY˝ÑµÆGVç]j5#?L§xt/#¨îÂ#ÈÖΩO≠P’ÎQÊ¢6ï££œ^ÌÜ Äöé¸÷ÿM\\R5t¥”öp‡*ÄÉXàV\"W≈DÄ	oRALm\rdGèN	’÷¿˙6îp\$ùPÂ∫üE5‘˝Ü©Tx\nÄ+ÄãC[®ÙVéå˝ç÷8UïDu}ÿªF\$.™ÀQ-;4»Ä±NX\nè.XÒbÕêï\0Øb•)ñ#≠N˝G4Kÿ–ZSî^◊¥M∂8ÿÛd≠\"CÇ¨>≈’dHe\nˆY8•è—.Í ˙∞à“èF˙DîΩW1cZ6îõQ‚KH¸@*\0ø^∏˙÷\\QﬂFÇ4U3Y|ë=ò”§ÈEõ‘€§¶?-ô47YÉPmôhYw_\röVe◊±Mò±ﬂŸèe(0∂‘F’\r†!“PUIïu—7QÂïCË—é?0ˇµè›gu\rq‡§ßY-QËÛ∞Ë˙=g\0Ö\0M#˜U◊S5ZtÆ÷üae^ï\$>≤ArVØ_\r;tÓè¨í®îHW©ZÌ@H’ÿhzDË⁄\0´S2Jµ HIÂO†'«ÅeÌg…6π[µRî<∏?» /è“KM§ˆñÿ\n>Ω§H·Z!iàˆ§üTX6ñ“◊i∫C !”õgΩ‡ “G }Q6û—4>‰w‡!⁄ôC}ßVB÷>Â™UQ⁄ëj™8cÔUçT‡˚ñ'<Ç>»˝ıÙHC]®Vö—7jj3v•§Â`0√Ë»23ˆ∞–Úx˚@Uók†\nÄ:Si5û’#YÏ-wÓî’‡ÈM?cÈ“MQ≈GQ’—Éb`ïÚ\0é@ıÀ“ß\0M•‡)ZrKX˚÷üŸWl≠≤ˆùèÕlÂ≥TM◊D\r4óQsS•40—sQÃÅımY„hïd∂¬C`{õVÄgE»\nñªXk’Å‡'”Ë,4˙ºπ^Ì¢6∆#<4ÅÈNXnM):π∑OM_6dÄñÊı∏√ı[\"KU≤nû÷?l¥x\0&\0øR56üT~>†ÙÜ’∏?îJnûÄí àœZ/i“6ÙŒ⁄glÕ¶÷U€·F}¥.û£ºçJLˆCTbMé4Õ”cLıTjSDí}JtåÄçZõ™µ«:±L≠Ä¥d:âEzî §™>ç÷V\$2>≠µé¢[„p‚6ˆ‘Ré9uÍW.?ï1Æ£RHuûË€R∏?58‘Æ§ÌD›∆uÉ£Áp˚cÏZ‡?úr◊ª Eaf∞ê}5wY¥ÎÂÇœí“Í≈WÇwT[Sp7'‘_aEk†\"[/i•ø#ˇ\$;mÖfÿ£WO¸Ùî‘FÚ\r%\$Õju-t#<≈!∑\n:´KEA£Ì“—]¿\nUÊQ≠KE¿†#ÄøXÂ®˜5[ >à`/£ÕDµ ÷≠VEp‡)èÂI%œqﬂ‹˚nÌx):§ßle¢¥’[e’\\ïeV[jÖñ£È—7 -+÷ﬂGçWEwtØWkE≈~uÏQ/mı#‘êWó`˝yuì«£D›Aˆ'◊±\r±ï’ôOùD )ZM^Ä≥u-|v8]ãgΩëhˆ◊≈L‡ñW\0¯»˚6ÀXÜë=Y‘dΩQ≠7œìîœ9£ÁÕ≤r <√÷èÍD≥∫B`c†9øí»`èD¨=wx©I%‰,·Ñ¨ÜË≤‡ÍÉj[—öù÷ÌﬂOˇã¥ ``é≈|∏ÚÚ∆ﬁ¯§åòºÌ.Ã	AOä¿ƒ	∑â@Â@ 0h2Ì\\‚–ÄM{e„Ä9^>Ùï‚@7\0ÚÙÀÇWíÄÚ\$,Ì…≈ö°@ÿÄ“‚ïÂ◊w^fmÂâ,\0œyD,◊ù^XÄ.Ø÷Ü©7„∑õ√◊2›≈f;•Ä6´\nî§éÖ^üzC©◊ßmzÖÈnñ^àÙî&LFFÍ,∞ˆ[Ä•e»ıaXy9hÄ!:zÕ9cÚQ9b≈ !Ä¶µGw_W…g•9©è”S+tÆ⁄·p›t…É\nm+ñúﬁŸ_	°™\\ºíùk5£“‹]∆4à_hï9 Ÿ˜NÖêó≈]%|•à7À÷úé];îÔ|ùÒµ†ﬂX˝Õ9’|ÂÒ◊ÃG¢ì®[◊‘\0ë}UÒîÁﬂMCçI:“qO®V‘Éa\0\rÒRÕ6œÄ√\0¯@H¢≈P+rÏS§W„ËÄ¯p7‰I~êp/¯†Hœ^›Í≤¸§¨Eß-%˚•ÃªÕ&.Œƒ+∏J—í;:≥∂´!ì˝–N	∆~ˆ™âÄ/ìWƒ¬!ÑBËL+¬\$Ìqß=¸ø+—`/∆ÑeÑ\\±“œx¿pEëlpS¬JSç›¢Ωˆ6‡á_π(≈Ø©ƒÈb\\O∆ &Ïº\\–59ù\0˚¬Ä9nÒè¯D∏{°\$·∏ãKêëv2	d]ËvÖCÅ’˛≈’?Åtf|W‹:£‘®p&ø‡LnÑŒË≥ûÓ{;àÁ⁄GÅR9¯êT.yπ¸ÔI8Äπ¥\rl∞ ˙	TË†nî3ºˆT.É9¥Ë3õ†öºZËs°Ø—“GÒ˛éà:	0£¶£zË≠›.å]¿Áƒ£Qõ?‡gTª%Òô’xå’å.Ñö‘«n<Ï£-‚8BÀ≥,BÚÏòrgQ˛¢ÌﬂÛÑ…é`⁄·2ÈÑ:ÓµΩ{ÖgÎƒsÑ¯gÛZøïÖ ◊å<Ê◊w{¶òÉbU9à	`5`4Ñ\0BxMpë8qnahÈÜ@ÿºÌÜ-‚(ó>S|0ÆÖæ•Ö3·8h\0—´µC‘zLQû@∂\n?Ü∏`A¿†>2ö¬,˜·òÒNÅ&å´xàl8sah1Ë|òBá…áDçxBﬁ#VóãVñ◊ä`W‚a'@õá¨	X_?\nÏæ  ï_‚Å. ÿPºr2ÆbUar¿I∏~·ÒÖSì‡˙\0◊Ö\"†2Ä÷˛¿>b;ÖvPh{[∞7a`À\0ÍÀ≤jóoå~∑˚˛vÕŸ|fvÜ4[Ω\$∂´{ÛØP\rvÊBKGbpÎ»≈¯ôñOä5›†2\0j˜ŸÑLéÄÓ)«m·»V°ejBB.'R{C§ÔV'`ÿÇ âé%≠«Ä–\$†OÂù\0ò`Çèí´4 ÃNÚ>;4£≥¢/ÃœÄ¥¿*¬¯\\5Ñ≈¡!Ü˚`X*ﬁ%ÓƒNÕ3SıAMÙ˛À∆î,˛1¨≤ÆÌ\\Ø≤caœß ≥˘@ÿ¨ÀÉ∏B/Ñ¨Õ¯0`Ûv2Ô°Ñßå`hD≈JO\$ÁÖ@p!9ò!•\n1¯7pB,>8F4ØÂf†œÄ:ìÒ7¬ÑÓ3õ£3Öø‡∞T8ó=+~ÿn´Œ‚\\ƒe∏<br∑˛†¯Fÿ≤∞ êπC°Nã:cÄ:‘lñ<\rõ„\\3‡>Òòá¿6ÅONnä‰!;·Ò@õtwÎ^FÈÄL‡;Ä◊∫,^aè»\ra\"ﬁ¿⁄Æ'˙:Ñv‡Je4√◊ê;ïÒ_d\r4\rÃ:€¸¿¨Sêòè‡ê2ÅÄ[cÄÑXˇ ¶Plò\$πﬁ£êiìwÂd#éB†öbÅõŒ◊§ıíô`:ÜÄœ~ <\0—2Ÿ∑óëRå¬∆P»\r∏J8D°t@ÏEéË\0\rÕú6ˆÛ‰ﬁ7ïΩ‰òYœ£˙\"Â‰¿ö\r¸É¶¿ö3É°.ò+´z3±;_ üvLè›‰”wJø94¿IêJa,A¶ÒàØ;És?÷N\nRùá!éß›êÜOmÖs»_Ê‡-z€≠wÑÄ€z‹≠7°Õ≈zÓ˜ñMçîàÄoøî•Ê\0¢Éaî≈›π4Â8ËPfÒYÂ?îÚióñeBŒS‡1\0…jDTeKîÆUYSÂ?66R	¶cı6Ry[c˜î∞5Ÿ]BÕî÷R˘_eA)&˘[ÂáïXYRWñ6VYaeUïfYeÂwïéUπbÂwîEÎ∞ Ü;z§^W´9ñ‰◊ß‰›ñıÎ\0<ﬁòËeÍ9SÂŒ§da™	î_-Ó·âL◊8«ÖÕQˆËTH[!<p\0£îPy5à|ó#ÅÍëP≥	◊9v‡ö2¬|«∏ù·faoÜ·,j8◊\$A@kÒÉøéaÀëΩbÛcÒ»f4!4®ë∂cr,;ôëÊëˆb∆=Ä¬;\0∞¯≈∫ÖòÜcd√ÊXæbÏxôaôRx0A„h£+wxN[ò‹Bê∑p⁄ÉøwôT¿8T%ôöMöl2‡áΩ°öêó}°»s.kYÑò0\$/ËfUÄ=˛ÿsÑgK√°àMõ ı?ˇõÁ`4c.‘¯!°&ÄÂàÜg∞˚f‡/˛f1ê=ØõV AE<#Ãπ°f\nª)†äÎõNpÚì„`.\"\"ªAÁú§„ó¸q∏ÅXì†Ÿ¨:a…8ôπfØôVsÛãGôﬁré:ÊVﬁ∆c‘gùVlôùg=ùÅ`„ìWéÀ˝y“gUù¿Àô™·∫ºÓeT=†„Ä·Ä∆x 0‚ Mº@àªö¬%Œ∫bΩú˛wô∆f€ŸO¯Á≠ò‹*0ØÖÆ|t·∞%±ôP»ÕpÊ˙gKû˘¨?pÙ@J¿<BŸü#≠`1ÑÓ9˛2ÁÅg∂!3~ÿ‹ÁÓnl‰≈fäÿVh˘¨é.—Ä‡ÖaC—˘ï?≥ä˚-‡1ú68>A§àa»\ró¶yã0†÷iëJ´}†‡πù©†–z:\r°)ëS˛Ç°@¢Âh@‰ˆÉYπ„¥mCEg°cyœÜçÇ<ı‡Õh@º@´zh<WŸƒ`¬ï®±:zO„Œ÷\rÕÍW´ì∞V08Ÿf7ô(GyêÉ≤`St#ÅÔÑfÜ#É≤ÅúC(9»¬òÿÄd˘ÊÊ8T:Øªå0∫Ë qµ††79∑·£phAg‹6ä.„Ê7Frôb‰ »jöËA5ÓÖÜÉ·°a1˙⁄hïZCh:ñ%πŒgU¢D9÷≈…àÑ◊πœÈ0~vTi;ùVvSöÑwúÿ\rŒÉ?‡«f≤£Öˇ•näœõiYôÏa∫¨3†Œá9’,\nô√rëâ,/,@.:ËY>&ÖöF—)è˙ôç∂}öb£ÄËiO›iùÊö:dËAånòöc=§L9Oíh{¶ê 8hY.íŸ¿ÆæáÆáÖú¸«\r¨ç÷á£¿õäÈ1QØU	îCëhÙÜeˇOâõ∞+2oÃŒÏﬁNãò˜ß¯zpË¢(˛]”hÄÂ¢Z|¨O°c—zD·˛Å;ıT\0j°\0Ö8#ç>Œé¡=bZ8FjÛÏÈ;Ìﬁ∫TÈÖ°wÆÕ)¶˝¯N`ÊÎ®§√ÖB{˚Éz\rÛ°cì”Ë|dTGìiú/˚˙!iÜ 0±º¯'`Z:äCHÔ(8¬èÍ`V•ô⁄„ˆ™\0‹Íß©Ü£WÔﬂ«™ò’zgGæëÖÉΩ≤-[√–	iúÍN\rq∫È´nÑÑìo	∆•fEJ˝°apbπÍ}6£Ö’=o§ñÑ,tËY+ˆÆEC\r÷Px4=ºæôŸ@áâ¶.ÜëF£ç[°zqÁ‹ËX6:FG®†#∞˚\$@&≠ab§˛hE:≤ÉÂ¨‰`∂S≠1ó1g1©˛Ñ2uhYã¨_:Bﬂ°dcÔñ*ˇ≠Ü\0˙∆óFYFú:À£™nÑÿÃ=€®H*ZºMhkê/çÎÉ°ûzŸπÔã¥]ö¡h@ÙÊ©ÿ„1\0ò¯ZK˘û¢ÎŒ∆Ë^+∫,vfÛsÆö>à§íO„|Ë¿ s√\0÷ú5ˆXÈãÓ—ØFÑ˜nøAàr]|œIi4ËÖ˛ ÿ¬C∞ h@ÿπ¥üûñcﬂ•®6smO√ÂâçôõgX¨V2¶6g?~÷√Y’—∞Üs˙cl \\Rä\0å®cúùA+å1∞Ñõ˘ÃÈç\n(—˙√Ã^368cz:=z˜Ç(‰¯ ;Ë£®Òès¸F∂@`;ÏÄ,>yTﬂÔ&ñïdΩL◊üúˇ%“É-ÎCHL8\rá«b˚∞∞£˙Mj]4êYm9¸€¸–Z⁄B¯ÔP}<ü˚‡X≤ØâÃ•·+g≈^ÿMﬁ + B_Fd¨XÑ¯ãlÛw»~Ó\r‚ΩãË\":‘ÍqA1XæÏÊ≤–¯Ø3÷ŒìE·h±4ﬂZZ¬Û∏&†ÖÊÊ1~!NÅf„¥ˆoóàô\nMe‹‡¨ÑÓÎXIŒÑÌG@V*XØÜ;µY5{Và\nËªœTÈz\rF†3}m∂‘p1Ì[Ä>©tËe∂wôüÊÎ@V÷z#Çù2ƒÔ	iÙÙŒ{„9ÉÇpÃùªghëäÊ+[elUâ¶€AﬂŸ∂”ºi1ƒ!åæommµ*K‡áÍ}∂∞!Ì∆≥Ì°Æ›{me∑f`ìómËòC€z=ûnﬁ:}g∞ TõmLu1F‹⁄}=8∏Z·ÌËOû€mFFMf§ÖOOÄÓ·¿ãÉË¯ﬂ/ºÈı∏ﬁìöÂÄ˛Vôoqj≥≤Ën!+ΩêÚµ¸Z®ÀIπ.Ã9!nGπ\\Ñõ3aπ~ÖO+ŒÂ::ÓK@å\n⁄@Éë§Hphë¥\\BƒıdmùfvCËû”P€\" ÊΩ€.nW&ñÍn¢¯HY˛+\r∂ìƒz˜i>Mfq€§Ó≠∫˘›QcÇ[≠H+Ê¿o§—*˙1'§˜#ƒÅEwÄD_XÌÅ)>–s£Ñ-~\rT=Ω£û‡˜à‡- ÌyßmßπÊ{ÑhÛüÃj⁄MË)Ä^ûπÔ¿'@VÂ°+i»ÓŒÚõüÂµÜ…;Fì†D[Œb!ºæè¥B	¶§:MPãÓÛ€≠oCºvAE?ÈC≤IiYÕÑ#˛p∂P\$k‚JﬁqΩ.…07ú˛ˆxàl¶sC|ÔΩæboñ2‰X™>MÙ\rl&ª«:2„~€—cQ≤ÓÚ≤Êo—ﬁd·Ç-˛ËU‹RoÇYönM;ín©#ñﬂ\0ñPæf⁄Po◊ø(C⁄v< ¨¯[Úo€∏îö˚◊f—ø÷¸¡;ﬂ·∫ñı[˙Yü.oÆUpøÆÅpUå¯î.û†©B!'\0ãÚ„<TÒù:1±¿æ†ö„§Ó<ÑõnàÓF≥ÉI¢«î¥ÇV0 «ÅRO8âw¯Œ,aF˙º…•π[¥ŒüÖÒYO˘´âÄ/\0ôŸoxÅ˜«Q?ß∞:ŸãÎ∆Ë`h@:É´øˆ—/MÌmºx:€∞c1§÷‡˚ØÌv≤;ÑÇË^Êÿ∆@Æı@£˙Ω¬«\n{Øº¬Óã‡;Áë¥BºÌ∏8ë∫ gÂùí‰\\*gÂyC)€ÑEù^˝Oƒh	°≥¶AÉu>∆Ë¸@‡DÃÜYÊºÌõ‚`oª<>¿Épâôäƒ∑íq,Y1Q®¡ﬂ∏Üè/qgå\0+\0‚ÊÂáDˇÉÁ?∂˛ Ó©⁄ﬂÓk:˘\$©˚¨Ì◊•6~I•Ö=@éÌ—!æ˘v⁄zOÒÅö≤‚+Õı∆9«i≥ñõºaÔÜÍ˚ÖgÚÙÓøùóπˇ?Åö0Gnòq≤]{“∏,F·√¯O°‚Ñﬁ <_>f+¢è,ÒÃ	ª‘Ò±&ÙúÜÌ¬∑ºyÍ«©O¸:¨U¬ØàL∆\n√√∫I:2≥ø-;_ƒ¢»|%ÈÂ¥ø!Œıfû\$¶àÜXr\"KniÓÒó¿–\$8#õg§t-õÄr@L”ÂúèË@S£<ërN\nêD/rLdQk‡£ìî™ıƒÓeÂ‰„–≠Â¯\n=4)ÉBòîÀ◊öÙÃZ-|Hb°ÅÜëHk *	÷Q!–'ÅÍG ûõYbt!ø (n,ÏP≥Ofq—+XìY±ˇÇÎ\"b F6÷Ãr fÚù\"“‹≥!N°Û^º¶r±B_(Ì\"®K _-<µÚ†*Q˜Ú®Ÿ/,)ÅH\0ùÑâ≤rÁ\"z2(πtŸá.F>Üá#3‚Æÿ¶268shŸ†˛®∆ëI1Sn20∂Á -ç´4í⁄«2Aús(¨4‰ºÀ∂äÅ\0∆›#ÑÂr˛K'ÀÕ∑G'ó7&\n>xﬂ¸‹JÿGO8,ÛÖ0º‚ã˘8î—”\0ÛW9í›Ià?:3n∫\r-w:≥¬Ã≈◊;3»âî!œ;≥‹ÍÉòòZíRMÉ+>÷‹ È0/=RÖ'1œ4’8˚ù—œmˇ%»•}œá9ª;Ç=œnQˆ„=œhhLı∑GœkWŒ\rÙ	%ÿ4“úsÒŒñJÄ3s€4ó@ôUÇ%\$ç‹—N;Ã?4≠ªÛN⁄œ2| ÛZ⁄3ÿh\0œ3ì5Ä^¿xi2d\r|˚M∑ £bh|›#v«` \0îÍêÆ‰‡˚\$\r2h#è˙§?≥àèI\níºç+o-úä?6`·πΩø.\$µö¯KY%ÿ¬ÅJ?¶c∞RèN#K:∞K·EL¡>:¡•@å„jPëÃn_t&slmí'Ê–©…∏”ú≤åΩó„;6€óHU5#ÏQ7U†˝WY‹U bNµñW˚_˚™©;TC¯[›<⁄ñ>≈«ıâW˝CUÅ‘6X#`MI:t˘”µÄˆ	u#`≠fu´\$´t≠ÅˆXÛ`çf<‘;bÂghˆ—’9◊7ÿS58ı¨›#^ñ-ı\0Í¿˙Ó’πR*÷'£®(ııqZÂ££ÍXπQ›FUv‘W GWÌÒ”TÍ«WÙ~⁄≠^ßWˆƒ¡’˝J=_ÿóbm÷›bV\\lÅ∑/⁄M’ˇTmTOXu =_è˝ITvvuãa\rL_’qR/]]m“su=H=u—g o\\U’ÖgM◊	XVU†¿%ıh˝°53Uô\\=°ˆQﬂÿMπváÄ°gÂm‡ıue°ùàŸ˚hˇb›M›GCeO5Æ‘Å÷O5Ö‘YŸi=e’	GùTURvOa∞*›ivWXïJ5<ıØbu†]à◊÷˙µ<ı√Ÿ’\$u3v#◊'eˆu—R5mïävãD5è.véåıW=üU_Â(¥\\Vÿœ_<ı˜SÕn)‹1M%Qh·ZáTÖf5E’'’ÕWΩäv≈Umi’ÇU‘’]aW©UßdRv·Ÿ-YUZuùŸUVùóUiRçVùôı≥”«[£ÌZMUß\\=¬v{€X˝µºwQ˜huHv«◊gq›¥w!⁄oqt¢U{TGq˝{˜#^G_ubQÑÍÂïi9Qb>⁄NUd∫±kÖΩ5hPŸmu[ï\0è¶Í≈_∂È[ıY-èÙ˜rı»’(÷CrMe˝Jı!h?QrX3 xˇ»œ#á˜x÷<€{u5~ÉÌ—-›uéÎYyQ\r-îÓ\0˘u’£uuŸøpU⁄Öï)ñPÂ‹\r<u´Sõ0›…wπﬂ-i›Û‘!Ã÷ä¯B˜·∆d]˘Ë≈á‘∆EÍvlmQ›è6kº“J¥àwÌ¶ƒûÿ√„åED∂UŸRìeçv:XﬂcÿNW}`-®t”H#eÑÅb∫±uÄ„Û	~B7Í ?É	OPúCWêµ◊SEÕïV>∂ì◊U€7ﬂûÁâ‘·mª”Ç¨zˇ=µÉÕÿ1∫ôÉ+†πm√I,>µX7‡‰]†.áΩ*	^Óä„∞NÖ∫.ËŒ/\"Ñèò)–	ÖØÇsûÆ|‡§Á”ü–l¡}„∏éÕÁ!ÛÓÉë5n±pÑj£æhí}ΩËmìE·zH¬aO0d=A|wÎﬂ≥„Î◊öŒÏu≤úüv˘ÿºGÄx#ÆÖbîcSo-â˘tOm`CãÚ^Må≈@Î¥h≠n\$k¥`˛`HD^ùPE‡[‰å]π®rR∏mû=Ç.ÒŸá>AyiÇ \"˙ÄÚ	÷∑o„-,.ú\nq+¿•ÂfXdä´∂„*ﬂΩàKŒÿÉ'‹Í –%aÙˇá˘9p˚Êó¯KLMÑ‡!˛,Ë Àé®åzX#òV·ÜuH%!¿ú63úJæry’ÅÌ˘q_Ëu	˙W˘±á∆|@3b1Â»7|~wÔ±≥˛ÌA7ì“¬õËô	ºô9cS&{„‰“%VxÔkZOâ◊wâUr?ÆÑí™N Œ|ÖC…#≈∞ıÂ’Ø π/˙ô9ÅftéEw∏C¡∫a¶^\0¯O<˛W¶{Y„=ÈüeÎò˝n…ÑÌgyf0h@ÏS›\0:Cê©¥^Ä∏VgpE9:85√3Êﬁß·∫è@ª·éj_™[ﬁ+´Í«©xÉ^ìÍÆÜ~@—áW™∏„„ìúÜ9xóFCòø≠.ê„öÁˆ¸k^Ié˚°pU9¸ÿSüÿ˜Ωóú\$ÛÛ¯\r4¥Ö˘\0ŒËO∞„ëƒ)L[¬p?Ï.PECSÏI1nm{≈?ûPÓWAﬂ≤¡;ÄÒÏD∞;S∫aèKf¯Úõ%è?¥Xıﬁ+è§B>Ω˘9øØŸGjòcûzëAÕé˜:Ía≥n0bJ{o•∑!3¿≠!'íÿK√≈Ì˘‘}„\\ËŒ3W¯Í5Óxœ…¡L;É2Œ∂nóa;≤ÅÌ◊∫X”õ]…o∫úx˚{‰¶5ﬁôjX˜àó∂v”öÈ„qﬁ EE{—Ä4¡æˆƒ{ÌŸÁ	Ã\nˆ >˘ôaÔØ∑æ¸ÏßÔÿL˚‘˚ÂÔˇΩ˚ÏÒ'ΩﬁÈ{Î\nâó>J¯ﬂåå·∏”óÜ˜Yœ\rO ΩëtØˇ˚•-O√¶¸4‘ˇ9F¸;ß¡ª‘¸G¯I™FﬂÏ1¬oˇﬂÛÒO≤æÈa{wó0”ªÔ§∆Ø;ÒîÑël¸oÒ‡J–Tb\rw«2ÆJµ˛=D#Ún¡:…yÒ˚S¯^„,.ø?(»I\$Ø ê∆ØÌ®·3˜√s4M aCR…∆ÕGÃëú˙Iﬂ∞n<˚zy—XNæ?ı‚.√Óê=ó‡Ò¥D«ºç\rõûÿÈ\n’Û®\roı˝\n–üCl%¡ÕYŒ˚•ﬂ∞œ‡G—˛⁄}#ùV–ù%˝(‘ˇ“‡3Ê…çòrû};Ù˚◊øG…Ãnˆ[™{•πñì_<m4[	I•¢¿ºq∞µ?0cV˝nmsÑ≥nMııà\"Nj1ıw?@Ï\$1¶˛>“^¯’˚•ˆ\\Ã{n¬\\ÃûÈ7üÑøŸüic1Ô⁄ˇhooÍ∑?j<Gˆxülœ˘©SËr}Õ√⁄|\"}ï˜/⁄?sÁ¨tI‰ÂÍº&^˝1eÛ”t„Ù,è*'F∏ﬂ=ù/FÅk˛,95rV‚·¯‡¿∫ÏëàÅ€o9Õ¯/F¿ñ_Ü~*^◊„{–I∆ˆØ„_ÉÇ≤åì^nÑ¯˛Nüä~¯·≈AÌ¶ëd©ÂÒ˛U¯w‰qY±ÂÓ¥T∏2¿ÈG‰?á&ñßÊÙ:y˘Ë%üñXÁòJ€C˛d	WËﬂé~˙G!Ü¥J}õó§˙Ï˘ıƒB-”Ô±;Ó˚úh√*ÛºR¥ÏˆE∂†~‚ÊÛ.´~…ÁÊ†SAqDVx¬ÓÕ='Ì…EŸ(^ä˚¢~õ˘¯øõÁÚÈÁÔo7~ÇM[ßÅQ„Ó(≥‹y∏˘nP—>[WX{q‘aœ§∆…˝.&N⁄3]Ò˙HYÔ›˚ÉÎ€[∂¡Ÿ&¸8?—3Ñãõ¶∂ß›Ü⁄ª∂·#å¶ŒBeù6ùÎÖ@ñì[∞§£˚‡–G\rŒ+˝ß}¸ò˜¡ˇœ_›Á7ñ|NÑß´ﬁ4~(z¡~ìªπÔß%õñ?±ﬂ”»[π¯1ûS™]xÿkˆ—KxO^ÈAçÄârZ+∫ˇªΩ*¬WˆØk˛wD(π¯ªR:Ê˝\0ïßÌç˘'§äÛìm!O–\n‰≈uËÇ∆Û.ê[ ÅP∆!π≤}◊œm €Ô1pÒu¸‚,T©ÁL 	¬Ä0}ù‚&PŸ•\nÄ=Dˇ=æÒ–\r¬öA/∑o@‰¸2„t†6‡DK≥∂\0»¬ÉqÜ7Ñl†ºBÍä˙Ã(É;[Òàkr\rë;#ë√‰Él≈î\r≥<}zb+‘–OÒ[ÄWrXÉ`ÅZ ≈£ÜPm'Fn†ºâÓSpﬂ-∞\0005¿`d®ÿ˜PÑ¡⁄«æ∑€;≤Ãn\0Ç5fÔPÑèøEJ‰w˚€ π.?¿;∂ßNÚﬁ•,;∆¶œ-[7∑ﬁe˛⁄i≈‚-ì÷ÓdŸé<[~î6k:&–.7á]Å\0Û©Å˚Îñ˘çè/µ59 Ò¡@eT:ÁÖòØ3≈dês›ù˙5‰èú5f\0–PµˆHBñïÌ∞Ω∫8J‘LS\0vI\0àô«7Dmê∆aû3e◊Ìé?B≥™\$¥.EãÅ–fçèÀ@™n˙ÉâbÚGb¡œq3ü|¸öPaÀà¯œØX7Tg>¬.⁄pÿÔôí5∏´AH≈µíä3S,ò¡@‘#&wµÓ3ÜÙm[œ¿ÚIÌ—•”^ìÃ§J1?©gT·ÅΩ#œS±=_ÑÇ_Å±	´£…Vq/C€æ∑›ÄŒ|ÀÙ·˛êD Ég>‹ÑıÎÈ 6\rä7}qî∆≈§ãJGÔB^ÓÜ\\g¥›ı¸Åú&%≠ÿ[™2Ix√¨™Ò6\03]¡3å{…@RU‡ŸMˆ†v<Â1äøëæsz±uPí5ü™F:“iÓ|¿`≠q”˜ÜV| ª¶\nkê‚}–'|égdÜ!®8¶ <,ÎP7òm¶ª||ªˇ∂IéA”Å]BB œFˆ0Xœ˙≥	äD÷ﬂ`W†µ¡qm¶OLë	Ï∏.Õ(¡pÇº“Å‰∂\"!ãè˝™\0‚ÕAÔ√Ùáâ¡VÄñ7kÉåM∏\$”N0\\’ßÉ\"ãfë·†«ÎÒ†»\0uqûó,å†5∆„A6◊pŒŒ»\nŒêjY≥7[pK∞4;êlú5n©¡@‚\\f˚–l	¶ÇMˆ˘˚P¡Á3ÆóC†Hb–å©∏cEpPâ⁄–4eooe˘{\r-‡ö2.‘÷•ΩåP50u¡≤∞G}ƒ‚\0ÓÀı®<\rˆú!∏ú~ ˝µæÛÒπ\n7FùÆd∂˝‡ìú>∑‘a¢Ÿ%∫c6‘ûßıM¿•|Ú‡dã˚∑ÏO”_®?JÑÊ™C0ƒ>–Å¡&7kM4™`%fÌlŒòB~¢wx—⁄ZGÈPÜ2Ø‡0¸=û*pÜ@àBe»îÿœ|2ƒ\r≥?q∏–8Ì∏Î±ÒÕ–ä(∑yr·ˆ†0‡Ó>ú>¿E?w‹|r]÷%Av‡˝¡≈‰@é+›X¡™Ag‚…€ˇs˚ÆC–˚AXmN“ù˙4\0\r⁄ÕΩ8J›J«∏Dè“öÛ¥:=	ïÛáÎ∆Sô4ØÒF;	¨\\&÷ËÜP!6%\$i‰xi4cΩ0B·;62=⁄€1¬˘ÃàPCÿÂ¬ÉmÀÕìdpc+“5äÂ\$/rCRÜ`£MQ§6(\\ê·2A†¶π\\™ålGÚl¨\0Bq∞§PØr≤˚¯BêµâÍõ—Çπ_6LlÀ!BQéâI¬éG¿Â‹ÿXRbs°]BóHrèû„ò`ŒXã‰\$pÂ±8Ñï	nbR,¬±ÖL†ç\"¬E%\0íaYB¶súÖÕD,ê!∆◊œõpN9RbG∑4∆˛M¨åtÖ∏ú¨jUÙ§¿êßy\0Ï›%\$.òiL!x¬Ï“ì≈(ƒ.ë)6T(íIÖÏa%“K»]mƒt•ÙÖ˙&ÇÛG7«ITMÛB˙\rza¬ÿ])vaà%úÜ≤41T¡jÕπ(!Ö¨ﬁ°®\\Å\\∆W¬‹\\t\$§0≈Ê%·î\0aK\$ËTöF(Y‡C@Ç∫Hœé–H„ÄnDíd√ÜWpò…hZØ'·ZC,/éù°\$˚¶£óJ°FB®u‹¨Q:Œ•¬Aˆâ:-a#îÏ=jb®ßl’Ug;{R∞ÄU∫±EWn‘UaªèV‚ÓïNj¨ßuãG…*®y÷π%›“@≈Ô*Ã‰´’YxÍ±_Û≤ßzÄ]Î)v\"£ÁR’ÂLØVIvÍ=`õæ'™∞U›) S\r~Ròïô\niî≈)5S¶ÂD49~ bî;)3á,¶9M3ØHsJkTú√úá(¢Ü˙óuJâ][\$uf®Ìob£µπ\n.,ÓY‹µ9j1'µå!ˆ1ù\$J∂ëg⁄§’üƒÜU0≠”Zuah£±∑cHù•,√Yt≤ÒKbˆ5óÎ5ñí/dY¨≥AUö“Ö©ã[W>®_Vˇ\ràë*∑ı©j£ß-T±Ö z÷Y dïcÆmá“π±ÿ:πÄ¸À[Ut-{™µ˝l	£i+a)ª.[∫ï_:⁄5û‰hÉÚ≠W¬ß…mª•%JIë¥[T´h>öÆµ∑∞ïô;ÀXÃ∫dÍ¬üSõdâVÊ;\r∆±!NàìK&óAàJu4BÖ¡dgŒ¢.Vp¢·mbãÖ)«V!U\0G‰∏®çì`ã–≠\\ÅÖq‚ü7Qˆb´VL•ﬁ:‰’Ç˙ÉÛ¨Z.≠NÚòƒ*ñ‘èU]Z¥lÊzÎÖŒˆ˘Æ«R D1IüÂ¬£—r:\0<1~;#¿Jb‡¶ Mòy›+ô€î/Å\"œõj<3Ê#ìñÃåÍÒ°Ö:P.}Íe˜ÔÅÚD\"qŸyJ˝Gå˚∑sopåçØ≤˛Xå\r›≥dñﬁ\rxJ%ñÌâœ∆ºO:%yy„≈,áî%{Œ3<ÓX√∏œÃ˜Øz¬EŒz(\0 ÄD_˜Ωü.2+÷gÆb∫c⁄xÏpgﬁ®¡ﬂ|9CPé˚Óò48U	Qß/AqÆ›Qº(4 7e\$Dìâv:åV°b◊˚N4[˘àiv∞¿Í2Ò\rïX1ºòAJ(<PlF–\0æ®Ä\\z›)—ÁöWÄ(¸4Ù»√⁄Ô¢ pïô”ı `µ«\r≥da6îùØ¸O÷ÌmÒa¥}q≈`¬¿6PÉ'h‡Á3ß|öíÓ√fè j»ˇAÊÉzâ¯£+åDåUW¯DÌ˛ﬁ5≈ƒ%#È∞xì3{´∂L\r-Õô]:jd◊P	j¸fΩq:Z˜\"sad“)ÛGÿ3	§ê+ärÑNKÅˆ1Q˛ΩÁÜx=>˚\"§∞-·: FÕıúIŸÉ*Ì@‘ü«yªTÌ\\UË®„äY~¬äâé‰‚öÇ3DÅÂÄ¡ô„®f,s¢8HVØ'…t9v(:ê÷B9Ò\\Zèö°Ö(ë&ÇE8ØÉÕW\$X\0ª\nåû9´WB¿íb¡√66j9– ‚ àÑÉ?,ö¨| ˘aæùg1≤\nPs†\0@Å%#KÑ∏Ä†\r\0≈ß\0Áà¿0‰?¿≈°,‰\0‘êhµ—hÄ\08\0l\0÷-‹Zê±jb‡≈¨\0p\0ﬁ-Ÿf`ql¢‰Ä0\0i-‹\\ps¢ËÄ7ãe\"-ZlbﬂE—,‰\0»Ã]P ¢⁄E∂ãb\0⁄/,Z‡\r¿\0000ã[f-@\r”ØE⁄ãœ/ÑZ8Ωë~\"⁄≈⁄ã≠ˆ.^“ŒQwÄ≈œãÇ\0÷/t_»º¿‚ËEã÷\0Ê0d]µÄb˙≈§ã|\0»ƒ\\ÿºÇ¢ÌE§\0af0tZ¿—nÅJÙ\0l\0Œ0L^ò¥Qj@≈·åJà¥^∏πq#F(å1∫/Ï[µ1ä¢„∆åIÊ.‹^8ªê\0[åqÿÃ[√ël\"Â∆ åÄ\0Ê0,dË∂¿Ä∆\råÅÃÑc¯µ{cE¡\0o‚0¨]∞\0\rc%≈€ãóà8Ωw¢Â∆Zãµ-ƒ\\∫Ò{„≈÷ãG™/\\bpÑÖ@1∆\0a≤1˘ã»œ—s„!≈®å/Ó/Ã]8πë~c\"≈€ã≈˛2ÙcŒëm£\"Ä9åqö/\\^fQ~c∆_ã£Œ-\$iû\"÷\0003åÀ¨§fX∫qx#\09åóZ.¥i∏»å@Fàåâ3tZH… \rcKÄb\0jí/Dj¯…1®‚‚∆Içh¥a»ÒvÄ∆©çOZ4úZÚÃ—Ç#YE®\0iñ.hH“—sX/F<ãœÜ.‰j¯ÀÒ≠bË∆Õ\0mV/d\\ËÿÒãb˜E≥ã£û3T^(›—àcKFRã’˘ÇÙ]X∂qΩ¢¯≈‡çóí6‘]h”Òûc6EƒãÛ66‹hêëü„n\0005çsn/dn∏‘`\r\"—Få≥⁄-D`»’ëã„NÄ2ãYî§bx¿Òî#\\≈ÎãáV3x∑1xÄFxåæ\0 6åb∞qÅ£É«!éû8|^ÇÃ—ubÂ∆‡ç’-Ùrÿ‰qº„:∆Èé%ˆ0åppÒî#Å«ã¢\0∆6‘f’—«¢‚≈¨çd“0ÑqH¥±æ£\$«@ãqÚ-º^B4±¶\"˙\08é1™/lnxœë†‚ÍGç3:0tjh“~@∆ºé•¶3§vH∆Òπb‹G(éeÑê4gÿ∫q¬„2∆1å…-ånXÀÒ∫\"„F<çQû1\\j∏∏1Æ„»E«ã«‰≥4m®’Ò™„[Ùãn¡z7¸yhﬁ1ß#∆ﬁé/Ç3\\x–qÕKGÇåˇ∆6‰oò—1{£∞FJç◊ö6ºlXÈq‚£Ñ∆uç©ﬁ9úr(ø1“„áGc\0≈f:ÑrXΩ†#–≈Ω\0iﬁ<\\}◊ÒÂbÓFΩ\0s÷7‹y2Ã—Ê#uFeçõ\">4iÿ≈ø‚‘∆ÁåÈ\n<{∏„ëç£‚∆âåJ;¨]ÿƒ1≈#Œ∆0èŸJ;4^Ë¬DΩ„Û«Æãü®≥4i®¿(H#⁄∆Eåxñ/§n¯˚1„/«°ãÂj6,lò€1t„/\0005%Ô0Ñ]x¸ë∂£GG5ê!í0§Ä®◊Ò⁄‚Èñråq¢2Ã®ﬁëŒ„NFPèo\"4Ù_ò∑1◊d«%ãe ≤3¨s8Èë¸„ÜG5éì Ê6‘[HÎìcÿHèjYö;Ù[ËæëòbÎ! éyÚ@ƒ\\∏Ωqÿ#WHNèáé;Ãc∆QË„:«-ê%™.úkX∆ë˝£⁄GÕåœÜ1Df®ﬂë∫cWFlê°!Ç0¸Äô≤c E‹ê©é;lò—qê\"ÎF©çﬂ¢7\\\\®˘Ò‚£‘∆Oãq˛.T|\"?ëÒ„ô∆Eê≥f9TyY—©„SG1ê˚¬A\$f9R\n\"ﬁ∆xåπ>BúÖH⁄Òﬂ§\0«å∂:\$eπ1ú£≥F?è=∫3Tu)\nqπbÈ«~èÀŒ<TÅ¯Œ±–câH.ëm~CÙwH ±∏#/»Iç]~3‰^à∫—Ñ#ß∆>ëYÆ4å^∏ŒQjc «Kå1\"“8¨|6—Âc\"«Bëµ\"b4„ËÊ%ú¢‘»G\0e\"í/tã®¥1r£1∆èe!v2Ñy¿±ı‰<«†èçÜ8\\o® —í#t≈—ê\rz@¥}H¬ëËbÔ∆Ëçy Ó1Ã\\®ÎdeGé¡Z3å~Èr)„1»øã€ÜBl~HΩ≤:£dF£ë-Œ?îk8¥qËc(FÕãäKﬁ5|myÒÄc1∆<í*@¥jÿ·Ú1„€≈æåã>I¥ZËÕQj‰ï»2å…\$0§ãhµQà‰VFTå	\$∆Al~ˆq⁄£»±é\$÷>\\pŸ\rqÇ\$/»u%Ô!ÆJq \$†„tE≤ãGN-Tq)Ú\"¢€H åÀ¶=ÏñX…2-£Hí´ö8\\nàµRW\$HåÎ\"¢C\\_π\0ªd\$«fë≥\".DÑu	'Q£zEÌåŸ&0toàÛqj„˙∆øå≥R@dó¯…‰£˘«uç##∂LLk…*qÛ\$*GƒëiŒ@Täiël„ÚE™ëÉŒ5åòær\\dñIñëµ\"/ÃZ…0íj\$T≈˛åz5Ld3í£Î…ío¬.Tqπ!1{£∆ãÂ÷9úZ∏æQ’b”FåwJ94nà“ƒ÷‰{…(ì-é8∑2h§u»Èì;\$Ü-Dk¯Ârs£áHûèô#°ÇÙèY7Ú\"ÿ/Eøí”†	\$j¢^Ú-£]«7é[\"N\$íË¬ëì§W»ëØ÷/]‡\$≤+Ä1Gaê/&IDn¯¬í@\$Â∆!ãÁ\$Œ-åk!ùQ®‚˘ )(N/\$t∏›π‰Î∆OèKzP¥tX‹Ú[\0íGéíw(*K\$vàÀ1Ûc…'ìﬁGÃûIÚxd≠»\nìA“8\\rX∑“a£˜IîiNúI%\$Ω„í∆_ë˜™6§fÁQ˛#ñ»Iî5#éF¥óÿ∫Òœ#≥E‚íï\"Ó3\$¢I‹cáHàã›vR|˘QÄ§cE∏èÒ:RÑe∫±h‰∂EŒèfK`8˛r.#∑E≥èsÆ0LÖò¸Rç‰ÜF©ã∑!\nC\$`»ˆÒ¥\$ÙH?íÀnP‹eô!Òö•@F'îøñ/úá∏∂ƒ÷‰ˇ îØ%¬N,h»ÃrF\$ˆ»˛å«3¥t¯Ê“Ä•≈Êí!1<Ñ…CQœ%…√íπÊJ‰Zÿf.›6≈çÜú∑±Câ• ‘ú.≤[˛ôB“øxÎ‡ÉË\0NRn`ö»˘Y\ní%+N®IMs:√πYdÉef¨B[∂∞›n∆πYäÚm®¡RÆ◊í˚…YØ⁄CÑXåÎ€j≥ÁU+Vk,Ø\0PÎ˝b@e≤π•x¨ÑVæ∫yT§7àuÓ´[JÔï»±\nDØßeRø¨mx&∞l¿\0)å}⁄Jº,\0ÑIÿZ∆µ\$k!µ®ÒYb≤¡ú∞ÄR¬áe/Qæ¿êk∞5.¡eë≠5ï¿®ûWë`™•\0)ÄYv\"V¬\0ï√\ná%óÂñ`YnØ’°aÙ‘x√ÜQ!,ı`\"â	_.üÂÅ©∆ñtm\$ï\"ì≤J´§÷ç¿ßév∆%âM9jÇ∞	Êñßƒ*≥Kp÷îí;\\R º¸3(ßıä^ùØ:}ñ»Ô|>¬µa-'U%w*â#>§@êÃ¨eñJˇù§;Pw/+π·5E\rjn°–√dñÙ¢^[˙ØßcŒ∞•uÀz\\ÿê1mi\"xÇÑpÂ√;£ÃÓàÊàP)‰¯™«#Ñ±ÿí°ÖÀ!A™;®ﬂ	4Ï≥a{`aV{KùU‡ 8„®ü0''oÄ2à®¢ycÃ∏9]KÈ@∫“ó^lBà‚OrÎ‘„,du§æ8§?ıâÄ’%ºgBªàÓÇ∆Yn+„%c¨e\0å∞Ò‡§±Yr@fÏã(]÷º®\nbizÓ÷nÄSS2£¡GdBPjäπ÷@Ä(ó»•¶!‡-Áv≤¥e⁄*c\0Ñ™4JÊÁÇí˘’Ÿ,ìU»	d∫…ej'TàH]‘ä‘G!ú)uã’÷Øüï“Ø˘ZÀB5˚ÃìWéâ0\n±·°‘R´¡ÅWÅÖ\\¶Q jƒ^r %lÃò3,“Yy◊…f3&Ãï‹é’Q:œµ2Ñm…R)îTÄæ(KR¡†0™ î@´ÏY¥¢Y:£Ÿe3\r%¥®∞Tˆ%≠Xî¡πáST‘.J\\Î0ŸhÙƒÖäD!ƒ:óuÊÍ…U\"æ≈Å¡o+7ñ\"ÑµÅìf'∫≠R\0∞ëﬁJùı2Sñ2Ë#nm ª¡IÂäú˝\"X¸≥≤[ê÷Ä—Ï} J®Øcº9p0™¸’Qª(U\0£xDEWÇå.Lı¡=<B‘0+Ω)ZS V;‚\\‚µI{ê5IëAÙ÷√,dW≤uË5Ew\n\$%“ÅÖàΩ2i_\$»Ÿ+ÏÊO,å¨áÌXã¥’ëJg&J°˙Gí∫%\\Jì∑b.ƒ›^LãTÚFlåËñπ]k#f@L∑GÄƒêTºŸó“ÕHœÃ\"ñq1SÃ∞˘âjèV…(ŒôÑÏZVzﬂ≈Ü≥,ùß ËGç.1F˚±gN ;◊1√äV¨¶5EÕÚ5`Ú\0CtË=F\n·πõŒ±ïKá˛ô÷\0≠€ä±%®ÀD]Q\$\r\0á3J\\,Õôö≥<T4*£ô¡.“YK≤D´QÉÈLÔS%,äg‘«Â™ß÷<ÀÎôu0ñÙÕUƒâ÷*x(©ÂN¬íYv!˛•yÕ	w≈4fd™•rGïâM \$‰Íâ^;∫ÈùÓ›Êà)<P„]D“%%”;‘j ÂöI0Êa”u^Jpó[)¶v©3RhR˙Eˆ¿\nÊñL_ö#5|‹æ’m3PÒ*®\\Y51Xíí	i≥Nó»Ò\$\"∞∫a¸≠ıh*KU›ÃÔV8®ÂuÚ±%&ÑrÊØÀö†≤5oå’Ág≥;›rMl[∆®ˆgú≥˘™í∑UÕqôÍπöh|‘eO2∑f MlW2APÑ◊πòíÕ¿Õv~eD¨eÒ3U”´láE62i¸ŒıÏ”UbÃÔò¨´ıUå¨©®Ó¯ê˝™VÍiI!\$i® ≠&Z:Ωñxm!≈Üì.÷OÕfw“Ø!îÃ”k›§ÕÉôç6b\"´IôJ]]:Tôù6“Vr˙π}í‹«´]ôÆ±ëU¢é	ys7f‘M≈ôˇ3àå‹ŒYúÛ:T_MÕw%3∆nœ•\nŒÊz*ôÌ3‚hÉ∑	ª`Uñ≤Lˇöá,•€Ñ–5®ÛvfÉª√õŸ42_Qâºh›«ÕuDß\no£π)§ƒú’´M9ø7fo€º©§r÷›«ŒWB~iT›eyQT‚N\nöd¶prß#õÛMß;íòÖ4Êp™ºÑtÍˇñ(;öõ≥5	|¨‡«Çä≠',AV7‹î‘ÂUAˆ&ÏÕRúPØ\"‰’yá“∑ïâ)†[änÃ’Ò-3VïÀ,?ús6∫pä˘Ü3éfµŒAö€9k|›…ÆSÜf¨*@úï5ﬁgºæ…ø2∑Õ}úåÆ˛U¸›ôë˘ÊHŒFõl%Æp¬´Ie≥beóMŸSO\ré[ºÊi≤3êf…ŒLV·ÆrŸuÆäæ•€NAõ:Ó%rÑ⁄y3Qù_Ã∏õW.—’»^Sl@&Ã¡ù5÷Yl¬Ã1ÂÊŒ}VxÍûg Öß^Sn’ÃÕQ!:5◊ZﬁiZC‘à:øõï3qgÈ%D·ı›™{U°3ítZπ`˚”u%w:…ZQ:QÏœ«W fÓáÌõø9JplÍ)÷3x‘vÃ˛ùK7ûb#´˘Ω´ÁX+Jö(¢¬h¥ÏP*”Åù¥´Œõ˛¢!◊îÏ≈èSLÁh*'ù§®\npB˘ô⁄™ègN ùß8Bu“™È¬éØÁŒåùΩ8niÍàIÕs∏USÕIöá;vv⁄≥UısRï7Nùu◊8©H|ÌÈ≈”∑ßÃéú´8Úq¥’Ÿﬁ+'—ﬂÕ`úx¢9Rà	’Æ∫ÁMaR8˙x‰)ê∏'!œúè;±U¨◊Y÷ìí›sNIùg:’KTÎyØ3ÆgéÕYùÏÎ k‰„…‹≥n'LO(úø3öw4Ò4Óª¶«œú⁄Í˛l¨ÒŒJΩùñ™wùΩ9›\\ÏÁïÛÛhf(¢_~ÏÚ‡}9Nˆ¶’\0ñ¥Âb\"¢YÈ§ÉTh,⁄û§@˙±D°˚Ä\$ÄIû∑;ée¸ËU ùn®≥û∑,πO™∆	XÅˇg¥-¿û…+>ti'GÇÅˆél™%\0≠8‚VBÀU1´yeê\0KT∆4˚¡»mí∫V2)\r]I/\rF˘Ö‘Xà◊¿ﬂ®Òa∑≠Gä¬πÚ*àßªûˇ>ERÏ˜ÓÆ•ûá—Zõ-)I\$ÆπÌÁ:¶aÀ\0æFybaŸg´wß≠(ﬂ_@ßv}ˆiı ≥ÓÄS^À25D‘≥–	»ÙURO±üJHù÷\\ÿisf∆ÀKöN±Äqi˜Sg◊O¬ü\n≤F~|´µœ*@gRÄ_Q<9s‹¨3i+ÿó≤.Cw≤≤Í|Çç¯yÀ6aÏO‹Y9∂å∂…ñ\nÎ‘Ω-([Æ±Ü_à}ÌS˚]c§S=¬§ŒŸ˛ŒÕ‘YŒ‡U->†<˙©µ\n<÷sOÙQ4F¶^}\0007u‰k(/ãü€/5{Lˇ9µ\0ß¨–†&≥ä[<œıüs€\0&ÕË#Ö@hÃÈ™3©V}–ùH¢äÅ*‹w+]'D–&†@ß÷Å])µË;TGe3êç\\ŒÍnÆ—ﬂÀd\$:¶uN4≈yktÍ-dR!7ñÅ≠…e4(P!ïü-˛Å9¿4Á_PMGbèÅƒ±wÖ´ÿ…6OßS¶FÇ‚Ì)ßäyh0+Äû≤ßqT|∑ä+u‘ˇŒ+†èA¨?Úﬁ	ˆTË3.q†è41T¥∏eõÄ\n:P†¯Øñ{TÓ\n≥Îh?´öTÔA˘S£≠*´Â“+Âu•>˙\\ÍæZÈÌ ÓYÏ∑¢wEJÅˆ%∑ísóL±æd™öy¿+\rCËúﬂ°'AÒl,“yÂ3˛Á≤ÀÕó`∫	_*—P˚ ThKDV≤∑ñ~5	‡0¥+·º,ö-?≠]ú∫Ú3Î÷çKÂó`Ø^Ü∏§I42(]™wû.ÊÜrƒ ÀÍ]¨\nY∆®BÜ£≠–	≥Ìñ}–ãR æ…gÿ}:HßJƒWP≤ÍÑ\"ﬁµóÙV\\¨<óó? >ΩÂó·ˇß‹¨›Üø=¶Ö:ü\n0◊Ë\\+ÒSñ¥Êf›Uå≥ÌâU,ÖWC÷àËïOn®ÚŒÖ¢ß.Üe9|R˜I'©[◊/ç∫≤ƒŸ¸2˘õ´Qû”Bn:∆Iı\nˆßgº9∆\r¸,”R6≥˝Á“Q\$X›+∏>êñ©±`\n˘)/_8Qi‘˘µÍó=áÍv?5vù\0 \n®Á…LG•Dmàw\\ÎF÷åá—¢êØ¡dÍüµ}sâ\"ë√Yv§|‚ôJ*¥9h≠°—@XEU—*ﬁ(oQ]\$çBûà,˚È‹ÉïKTúv§AptC…É\n◊C,/ò<°≠⁄ôEWã-VÔP°¢=Wˇ*%KÍó-Q`9	( ˙59”ÄËm)ÀX∏®@Á2¯†˝T@à€\nSñØëbd◊EŒ¥aÄ+ÄDXÓ·|U⁄	ã	í°FÆ 2˙%5\njïm´ÄWŸ+çxÍKåÊVÃ3#Ñ∂CT√ek§ôñ&Œ,£l¨jbd7)”ì\"\n+ÏP¸∫bíËIä@Ë3—ï‹µjU“ÃEsﬁ‘)D¢fÎíÉıäÅ˚ï«PÅZ3AŒå’\nwThó≤™€ò≈4Zè‰™< uﬂ©ﬂdq‚Àäu(˜ûìbKG±‡•È¿n”TÔÆà]z®çf%#ù3IÀfS®Æ&}µ@DÜ@++˘§AÌh™øê\n™ÔÄUóﬁ•|B°;îÖUm—ŸUÖEïN•!Ùx2±1“\0ßGmvH~ı¡HËTÍ)ˆWÆ≥YN˝\"Âk5©—vT#=µ⁄• <\n}ë#R3YÉH≈RÕIÕ≥‹¶;Ã—Rl£1lÈuB%TQJÓô*∫ÍàŸ'∫EÎ0i¨dw,•z Õ•:\$Ü¶;Õ?†¸Ójëø)ßÙ)‘è \$32J}≈&á[≥\$®ıÃÅ§;Dnê˝E◊¥¿+0€aZ{®çËC Ë˚Ä(§Í:ì∏†⁄O@h¯≤D£Ê\0°â`PTouì≥ƒÔFÆ\rQvÇ˚®òoΩ‹°\$SÓˆ+ò“#7¿§IzrÖpk†DWîàFsÕ9ô†QÍ †–∞1Äg¿≈#ï\0\\L‡\$ÿ†3Äg©XéyÙy ú-3hõ¿˛√!ÜnXËÙ]+±ó	…ùÄc\0»\0ºbÿ≈\0\râ¸á-{û\0∫Q(Q‘\$sÄ0Ö∫Èm(∞[RuÚV∆˜“ÿ>∆º+‡J[©6‡ë“‡J\0÷ó˙\\¥∂„,“ÈÇKö3˝.Í]a_\0RÚJ ∆ó`ö^‘∂ClR€IKÓñ˘\n†\$Æn≈è“‰•ÔKjñ©\nÄö¡©~/•™mnò].™`Ùøij“‚¶#Kæòf:`\0ÖÈåÄ6¶7K‚ñ®zcÙ¬\0í“ı¶/KÆñ≠/™dÙƒÈáFE\0aLéò§dZ`ÉJÈÜSëœ ôÖ2ÿÕ4Œ@/∆(åãLÚôı0™`¥ƒ©ÜÄ_éL˛ô]4ZhÙ–©öSD¶MòÖ4:c—ÈãSR•◊MóE4öiÚÄÈûSG¶EMjòÂ4zd‘’©ñSFKL™õ%4™e‘œ%\$”lKM2ñı1»⁄î‘i¶”ç©MVõ≠.∏⁄î÷i¥”ç©Lzõ/à˜Ù€£”Ñ¶—MÊõ,`ä_Ù‡imSä¶gM∆úÄjgëÚÈ«”5¶9.õÖ9j_ÚÈ∫Sê•µ.õ≈9Í_±ÚÈæSà¶ã.ú7⁄rÚ)…”%ß[2ùm8∫uTÊÈôS±ß3M:ù]3∫qîË‰n”±ßKNà1|^“ktœ\"“”HßgKjû-;zcÒiŒ”ößêñù\r<Í_≤-i ”∏•Ò\"÷ûU.π¥ÛiÎR⁄ëkOFûÌ=:\\Ùœ\$Z”©ßMLE≠5˙xÙ¯©¬”ª_\"÷ú=<\0ÒtÈŸSÁ¶9O“û≠1ä~îˆi≤”ÙßπOÍùÌ>Í~qú)ÚF∏®í†=6:~‘ı„J‘ëœP:üÕ=®ÂTˇ)¢∆´ßˇPJ8ı@ÍwÙÙ©˜«*ßÕO 5]>™Åt˜£ïT\nßÂ!\"†ç6Y	)Ä»H®/P™ûÖ3…	ÈÜ/êëP~†‡˘	™”Æ®!\"üçCíÃ‘˝j° ®eNJ°¸àÍàÒ‘*%‘4¶1Q°≈CZáQëjTBçQ.¢\rE)\0004ÀÍ\$Ä2®SM+Â<jÑtøj0‘,¶9QÜ°}F\0\$±s©ûTa®ùKŒ£]Ecj*Ä'KªMæóMGxΩ’R«T1¶#QÍ°•G™ä5™:‘z®Lö°4u6zèï\"j\"TàKuN÷£˝G⁄g\$jFS‹®ÔQ2§•H¯Óµ\"ÍMTÉ©%R§ïHzé’\$™,‘w®Re.\$r™zµ)©€‘¶©-Qˆ†ÕJÑπë ™@‘∞©=R&/ùI ï1Ü*]T≥ã¿7ºòæQ“ÂD&”©qN¶_(¥q≤c[TwåQRÙÂ¥úJö\0n‚˜T≠®˚.¶ò956c‘‹å’Sz•Hò¡ï7™R‘}éSr8•Näö’\"b÷TËß¡Qﬁ5MNäñı#„Á‘Ë©ES¬ß-Hò¡7\"‹T¸©_SÍß}GÿÃï?*y‘©ãáSÚßΩP*ü5#‚ˆ‘‹çœT:ß]P üıC*Ä‘âãT:®-K8∆5C™Ñ’™R¶--M»æïH™à’ ™'TÇ®≠H¯ÀıH™å‘—ã◊Tä®ÌR™£ı,‚È‘‹ãGT⁄©-SJ§ıM*î‘©ãUT⁄©mMH∏ıM™ò’>™gSD≥5M»¬ïR™ú’H™wU\"©ÌK8’’R™†‘⁄å°U*™-U*®‡n¬æTŸIR≠,t¢Z´’ÍY∂IUF´51™¨µW)v’kã_K∆´pJ´5Zj≠≈Ø©Rç4r\n¨^jI”CK∫ÑÇ™}U ì_™∞‘õ™„O¨=N∑R*ØF-™ΩRû¨%Wöã’cÍ¶’\\éaV>´EYjñµd™™‘√´UŒ¨µWXÕ5*»’ãíπUyÇıZä∞1k„ô’®´7Vö¨R\\HÕ5h*÷U¢©œU∆ßM[ä≤±kÍv’∏´3VÚ≠}[(‰5W™z’∏´iB≠O∫Æ1ØÍØT˝´óVÆ;≠[¯ÓµpRÊGu´;T@0>\0ÇÍ/I≥™ˇW`Ì]¶Ù\0™Ó∆8´øPäØ]»Õ1m*Ô’«çyUz®mW°ı|™›ì[´°÷ØÖ]J¨—àÍ¯U±´´ˆØÖZ*§5\\jë÷´ÎZ™Ù`Z¡5~™ÆEÏ¨W˙´4Zö¡5h£Q’^ãcXZÆïS˙Æ1o´V™πU&´çT∫ƒ5}cU^çõXö∞dm*≥±íkUu•´SfG=[πıj‰s’øëœX¶Kc\nÆiR‚HÁ´i#û±uWtªµ™Ω•∫´ªX¬ù’cƒπï´UÜ¨îr⁄¢ıUZã’áÉNE¢¨ëX∫¨Ö4⁄»udÍ∑E‰¨eV^≤ÌK…‡n‚ÚV8ãsX¬•Õf«ı/¬hJ≥-J]”ÇÖô”Œ¡’zOõ±<Ehâ\$Âãì∑°Û\0KúÎ<bwÑÒÖ>∑î¯Nû\")]b£	‚+zÍ.cS.¢iFÁ	„£µQNQê´ÈV*™È€Œ˙ﬁO[X§nxä§P	k≠ßoN¯£}<aOÚßIﬂì¡h∑∫öT;ÚrÒââ§ÉVD6Qﬂ;zä]j◊~'í:Îñ[IvÙÛ7^ ëß÷¡ûjÎ∫w[´˘ÊÓ∫Áú ≈Ü•:u ≈Ds#¶øŒ\\wµ<n|*·âhÎmŒKv;Y“à±⁄3·]å´^#óZ™j•gy≥jƒßY,î%;3æ≥ ⁄˘◊.»W\"ë√\$Ÿ3>g⁄ú∫œ”œ¶™VÅTÛZj•hY›jûkD*!öh&XzÀi™ï•+GVó≠\"•Ê∏Zè:“§ß+áNoG•Zjj•i…] ûkO–_≠÷¨‘êmjI™ï®ßtØñ#Ω[‚j\rnä„Í©◊–nôﬂZ•_,’ÈÜÛgŒƒö©:πº≈9â¡ˇ´[L2ÆW=T‘◊0Æ„f∂\0PÆU6\ns%7isYÊ?£øu·3æíΩnb5°´üªöX|G~lï&◊k§•∑Mß†ÜØ˙∂åœy°Sñ…)Œ]ú‹≠r∑∂Ÿ∏µ∏ÊÏ÷Íõ≈?’}u'n0W-ŒπÆÊb∑¥«™Ïıük?ªvQ˝7Ö‹}p\nÏı¿íÕŸÆZ*ª9) ·5ﬁïZW≠-ZB∏≤å:Ïı„´äWê\0WZfpïGpıÓÕŸÆ:èFp˙§ä‰UŸÎSN/ôœ\\©‹%s9¨S{ß ◊8ÆœZÕas €ìí+¢N^Æì9ôM’{ÖP5”Á ◊QÆ‘ÓJ∫¢´yßı’Ë;èú⁄Óz∏É¬’Y⁄V ƒ3ó:ÔúD≈Iùä√+Áá˝Ø£19M;∫•åíÙ®ìV¥Æö\rQ{Í…’Æï∂≈+£ÉFùCLƒπäN•ñ©‘àù\\˘ﬁ)\$iåé€N'\0¶∞çPä¬öı «]XÃ^ùs1Úfù&ä\"'<O¯ÛöÃ°ÀL\0π\"á@÷î•%‰6˙¬UAı1˝i(zÃË›ÅÄ\r“’Ç‰±»bZ¿î+IQOÔ3Ä∫À\r=*ƒâ†â)Ò®!¡û†–`™ºh∞à,–´mGPCÅÀA†ùŸ≤ÌÉAÑå(Z≈∞%ÉtÏ,h/¡âàiñ»k¨´°XEJ6±ÑIDË»¨\"õ\nÔaU- õ´\nvéyù∞_Äƒ¬¬õ⁄´Øk	aΩB<«V¬É€Dª/PùªÙaÓ¡)9L„∂(ZÇ∞8ÍÅvv√πÿk	ßo–ZXk‰—Âß|¥&∞.¬Êù±CÅπíÿ·∞`Ä1Ä]7&ƒô+ôH§CBcXìB7xXÛ|1ìÄ0¶„aö6ö∞ubpJL«Öñ(∑ö˜mblÅ8I∂*Rˆó@tk0Äó°Ø≈xX€¡”;¡≈ al]4s∞tøÌ≈™0ßcá'¥Êlﬂ`8Må8ë¿√ÄD4w`p?@706gÃà~K±\rÇ€ ìP¥ÖŸbhÄ\"&êØ\nÏqëPD»–ŒÛ\$–(Õ0QP<˜∞‡¿„¨Qç!X¥Öx˙‘5ÄùàR∑`w/2∞2#ä¿∏é `¨ªë1Ü/à‹Å\r°ê÷:¬≤ñ±¢£B7ˆV7ZåõgMY˙H3» ÑŸbŒ	Z¡”Jê≈ˆG‚wŸglÅ^∆-ëR-!Õlì7Ã≤LıÜ∆∞<1 ÌQC/’≤hº‡)œWû6C	˜*dà˛6]VK!mÏÖÿ‹„Ä05G\$ñRòµ4Ø±=Cw&[Êè´YP≤õd…ö≥')VK,®5e»\rﬁ ËÜK+Ô1ÑX)b€e)ƒ‚uF2A#E—&g~ëe°yífp5®lYl≤‘ú5ıÉˆø÷\n¬äŸm}`Ç(¨M ÅPl9YÅˇf¯±˝÷]ÄVl-4é√©¶´¬¡>`¿ï/˚≥fPEôiã\0kôv∆\0ﬂfhS0±&Õ¬¶lÕº¢#fuÂÃ˚5	i%ˇ:FdÄˆ9éôÿÄG<‰	{ˆ}Ï¬s[7\0·¨Œû3Ìft:+.»îñp†>ÿ’±£@!Pas6q,¿≥ó1b«¨≈ã„ZK∞Í±‹-˙ìar`ï?RxX¡Èë°œVÔ˙ò#ƒ§‘z¬êç; ¿DÄïæH≤¡1•í6D`û˛YÍ`˜R≈P÷ã>-∆!\$Ÿ˘≥Ï◊~œÄ–≈‡`>ŸÔ≥ıh‘0Ù1Ü¿¨ñ&\0√hóÎ˚Iñwl˚ZÑ\$ì\\\rç°8∂~,ê\n∫o_·¿B2D¥ñÅÉa1Í≥‡«©è=¢v<œkF¥p`è`îkBF∂6ç ƒ÷≤óh∆…T T÷éÅ	á@?dr—ÂâÄJ¿H@1∞G¥dn¡“wá∆è%‰⁄JGö“0bTf]m(ÿk¥qg\\ÌΩèÛ∏ñ¨Î∞Í†»—à3vk'˝^d¥®AXˇô~«WôVs¬*º ±Êd¥˚M†¿¨ù@?≤ƒ”}ß6\\ñçm9<Œ±iî›ßõà‘¨hΩ^s}Ê-¶[Kús±q„bŒ”-ìˆOORm8\$ﬁywƒÏ##∞å@‚ù∑\0Ù“ÿ§ 5F7ˆ®É†X\n”¿|JÀ/-SôW!f«Ü 0∂,wΩ®D4Ÿ°RU•T¥ûíÓ’ZX«=Ì`âW\$@‚‘•(ãXGßã“äµóa>÷*˚Y∂≤à\n≥¸\nåÏö!´[mjúµä0,mu¨W@ FX˙⁄ŒÚù¸=≠†(¶˝≠bø˝<!\n\"î™83√'¶Ç(Rô›\n>î˘@®W¶r!L£H≈kÃ\ràE\nW∆ﬁ\r¢Ç'FHú\$£ã‰‰¿mÑÅ»=‘€•{LYóÖ&—‹£_\0é∆¸›#¢‰îÄ[Ñ9\0§\"‘“@8ƒiK™πˆ0Ÿlâ—–p\ngÓÇ€'qbFñÿy·´cèl@9€(#JU´›≤É{io≠ë•.{‘Õ≥4ﬁVÕÅäVnF…x—¸zŒ Q‡ﬁû\$kSa~ ®0s@£¿´%Öy@ï¿5HéÜNŒÕ¶¥@Üxí#	‹´ /\\•÷?<h⁄Ç˘ÖºIêTå†:ç3√\n%ó∏");}else{header("Content-Type: image/gif");switch($_GET["file"]){case"plus.gif":echo"GIF89a\0\0Å\0001ÓÓÓ\0\0Äôôô\0\0\0!˘\0\0\0,\0\0\0\0\0\0!Ñè©ÀÌMÒÃ*)æo˙Ø) qï°eàµÓ#ƒÚLÀ\0;";break;case"cross.gif":echo"GIF89a\0\0Å\0001ÓÓÓ\0\0Äôôô\0\0\0!˘\0\0\0,\0\0\0\0\0\0#Ñè©ÀÌ#\na÷Fo~y√.Å_waî·1Á±JÓG¬L◊6]\0\0;";break;case"up.gif":echo"GIF89a\0\0Å\0001ÓÓÓ\0\0Äôôô\0\0\0!˘\0\0\0,\0\0\0\0\0\0 Ñè©ÀÌMQN\nÔ}Ùûa8äyöa≈∂Æ\0«Ú\0;";break;case"down.gif":echo"GIF89a\0\0Å\0001ÓÓÓ\0\0Äôôô\0\0\0!˘\0\0\0,\0\0\0\0\0\0 Ñè©ÀÌMÒÃ*)æ[W˛\\¢«L&Ÿú∆∂ï\0«Ú\0;";break;case"arrow.gif":echo"GIF89a\0\n\0Ä\0\0ÄÄÄˇˇˇ!˘\0\0\0,\0\0\0\0\0\n\0\0Çiñ±ãûî™”≤ﬁª\0\0;";break;}}exit;}if($_GET["script"]=="version"){$gd=file_open_lock(get_temp_dir()."/adminer.version");if($gd)file_write_unlock($gd,serialize(array("signature"=>$_POST["signature"],"version"=>$_POST["version"])));exit;}global$b,$g,$n,$cc,$kc,$uc,$o,$id,$od,$ba,$Pd,$y,$ca,$ke,$nf,$Yf,$Fh,$td,$mi,$si,$U,$Gi,$ia;if(!$_SERVER["REQUEST_URI"])$_SERVER["REQUEST_URI"]=$_SERVER["ORIG_PATH_INFO"];if(!strpos($_SERVER["REQUEST_URI"],'?')&&$_SERVER["QUERY_STRING"]!="")$_SERVER["REQUEST_URI"].="?$_SERVER[QUERY_STRING]";if($_SERVER["HTTP_X_FORWARDED_PREFIX"])$_SERVER["REQUEST_URI"]=$_SERVER["HTTP_X_FORWARDED_PREFIX"].$_SERVER["REQUEST_URI"];$ba=($_SERVER["HTTPS"]&&strcasecmp($_SERVER["HTTPS"],"off"))||ini_bool("session.cookie_secure");@ini_set("session.use_trans_sid",false);if(!defined("SID")){session_cache_limiter("");session_name("adminer_sid");$Lf=array(0,preg_replace('~\?.*~','',$_SERVER["REQUEST_URI"]),"",$ba);if(version_compare(PHP_VERSION,'5.2.0')>=0)$Lf[]=true;call_user_func_array('session_set_cookie_params',$Lf);session_start();}remove_slashes(array(&$_GET,&$_POST,&$_COOKIE),$Tc);if(get_magic_quotes_runtime())set_magic_quotes_runtime(false);@set_time_limit(0);@ini_set("zend.ze1_compatibility_mode",false);@ini_set("precision",15);function
get_lang(){return'en';}function
lang($ri,$ef=null){if(is_array($ri)){$bg=($ef==1?0:1);$ri=$ri[$bg];}$ri=str_replace("%d","%s",$ri);$ef=format_number($ef);return
sprintf($ri,$ef);}if(extension_loaded('pdo')){class
Min_PDO
extends
PDO{var$_result,$server_info,$affected_rows,$errno,$error;function
__construct(){global$b;$bg=array_search("SQL",$b->operators);if($bg!==false)unset($b->operators[$bg]);}function
dsn($hc,$V,$F,$vf=array()){try{parent::__construct($hc,$V,$F,$vf);}catch(Exception$zc){auth_error(h($zc->getMessage()));}$this->setAttribute(13,array('Min_PDOStatement'));$this->server_info=@$this->getAttribute(4);}function
query($G,$Ai=false){$H=parent::query($G);$this->error="";if(!$H){list(,$this->errno,$this->error)=$this->errorInfo();if(!$this->error)$this->error='Unknown error.';return
false;}$this->store_result($H);return$H;}function
multi_query($G){return$this->_result=$this->query($G);}function
store_result($H=null){if(!$H){$H=$this->_result;if(!$H)return
false;}if($H->columnCount()){$H->num_rows=$H->rowCount();return$H;}$this->affected_rows=$H->rowCount();return
true;}function
next_result(){if(!$this->_result)return
false;$this->_result->_offset=0;return@$this->_result->nextRowset();}function
result($G,$p=0){$H=$this->query($G);if(!$H)return
false;$J=$H->fetch();return$J[$p];}}class
Min_PDOStatement
extends
PDOStatement{var$_offset=0,$num_rows;function
fetch_assoc(){return$this->fetch(2);}function
fetch_row(){return$this->fetch(3);}function
fetch_field(){$J=(object)$this->getColumnMeta($this->_offset++);$J->orgtable=$J->table;$J->orgname=$J->name;$J->charsetnr=(in_array("blob",(array)$J->flags)?63:0);return$J;}}}$cc=array();class
Min_SQL{var$_conn;function
__construct($g){$this->_conn=$g;}function
select($Q,$L,$Z,$ld,$xf=array(),$_=1,$E=0,$jg=false){global$b,$y;$Wd=(count($ld)<count($L));$G=$b->selectQueryBuild($L,$Z,$ld,$xf,$_,$E);if(!$G)$G="SELECT".limit(($_GET["page"]!="last"&&$_!=""&&$ld&&$Wd&&$y=="sql"?"SQL_CALC_FOUND_ROWS ":"").implode(", ",$L)."\nFROM ".table($Q),($Z?"\nWHERE ".implode(" AND ",$Z):"").($ld&&$Wd?"\nGROUP BY ".implode(", ",$ld):"").($xf?"\nORDER BY ".implode(", ",$xf):""),($_!=""?+$_:null),($E?$_*$E:0),"\n");$Ah=microtime(true);$I=$this->_conn->query($G);if($jg)echo$b->selectQuery($G,$Ah,!$I);return$I;}function
delete($Q,$tg,$_=0){$G="FROM ".table($Q);return
queries("DELETE".($_?limit1($Q,$G,$tg):" $G$tg"));}function
update($Q,$O,$tg,$_=0,$M="\n"){$Si=array();foreach($O
as$z=>$X)$Si[]="$z = $X";$G=table($Q)." SET$M".implode(",$M",$Si);return
queries("UPDATE".($_?limit1($Q,$G,$tg,$M):" $G$tg"));}function
insert($Q,$O){return
queries("INSERT INTO ".table($Q).($O?" (".implode(", ",array_keys($O)).")\nVALUES (".implode(", ",$O).")":" DEFAULT VALUES"));}function
insertUpdate($Q,$K,$hg){return
false;}function
begin(){return
queries("BEGIN");}function
commit(){return
queries("COMMIT");}function
rollback(){return
queries("ROLLBACK");}function
slowQuery($G,$di){}function
convertSearch($v,$X,$p){return$v;}function
value($X,$p){return(method_exists($this->_conn,'value')?$this->_conn->value($X,$p):(is_resource($X)?stream_get_contents($X):$X));}function
quoteBinary($Vg){return
q($Vg);}function
warnings(){return'';}function
tableHelp($C){}}$cc["sqlite"]="SQLite 3";$cc["sqlite2"]="SQLite 2";if(isset($_GET["sqlite"])||isset($_GET["sqlite2"])){$eg=array((isset($_GET["sqlite"])?"SQLite3":"SQLite"),"PDO_SQLite");define("DRIVER",(isset($_GET["sqlite"])?"sqlite":"sqlite2"));if(class_exists(isset($_GET["sqlite"])?"SQLite3":"SQLiteDatabase")){if(isset($_GET["sqlite"])){class
Min_SQLite{var$extension="SQLite3",$server_info,$affected_rows,$errno,$error,$_link;function
__construct($Sc){$this->_link=new
SQLite3($Sc);$Vi=$this->_link->adminer_version();$this->server_info=$Vi["versionString"];}function
query($G){$H=@$this->_link->query($G);$this->error="";if(!$H){$this->errno=$this->_link->lastErrorCode();$this->error=$this->_link->lastErrorMsg();return
false;}elseif($H->numColumns())return
new
Min_Result($H);$this->affected_rows=$this->_link->changes();return
true;}function
quote($P){return(is_utf8($P)?"'".$this->_link->escapeString($P)."'":"x'".reset(unpack('H*',$P))."'");}function
store_result(){return$this->_result;}function
result($G,$p=0){$H=$this->query($G);if(!is_object($H))return
false;$J=$H->_result->fetchArray();return$J[$p];}}class
Min_Result{var$_result,$_offset=0,$num_rows;function
__construct($H){$this->_result=$H;}function
fetch_assoc(){return$this->_result->fetchArray(SQLITE3_ASSOC);}function
fetch_row(){return$this->_result->fetchArray(SQLITE3_NUM);}function
fetch_field(){$e=$this->_offset++;$T=$this->_result->columnType($e);return(object)array("name"=>$this->_result->columnName($e),"type"=>$T,"charsetnr"=>($T==SQLITE3_BLOB?63:0),);}function
__desctruct(){return$this->_result->finalize();}}}else{class
Min_SQLite{var$extension="SQLite",$server_info,$affected_rows,$error,$_link;function
__construct($Sc){$this->server_info=sqlite_libversion();$this->_link=new
SQLiteDatabase($Sc);}function
query($G,$Ai=false){$Pe=($Ai?"unbufferedQuery":"query");$H=@$this->_link->$Pe($G,SQLITE_BOTH,$o);$this->error="";if(!$H){$this->error=$o;return
false;}elseif($H===true){$this->affected_rows=$this->changes();return
true;}return
new
Min_Result($H);}function
quote($P){return"'".sqlite_escape_string($P)."'";}function
store_result(){return$this->_result;}function
result($G,$p=0){$H=$this->query($G);if(!is_object($H))return
false;$J=$H->_result->fetch();return$J[$p];}}class
Min_Result{var$_result,$_offset=0,$num_rows;function
__construct($H){$this->_result=$H;if(method_exists($H,'numRows'))$this->num_rows=$H->numRows();}function
fetch_assoc(){$J=$this->_result->fetch(SQLITE_ASSOC);if(!$J)return
false;$I=array();foreach($J
as$z=>$X)$I[($z[0]=='"'?idf_unescape($z):$z)]=$X;return$I;}function
fetch_row(){return$this->_result->fetch(SQLITE_NUM);}function
fetch_field(){$C=$this->_result->fieldName($this->_offset++);$Xf='(\[.*]|"(?:[^"]|"")*"|(.+))';if(preg_match("~^($Xf\\.)?$Xf\$~",$C,$B)){$Q=($B[3]!=""?$B[3]:idf_unescape($B[2]));$C=($B[5]!=""?$B[5]:idf_unescape($B[4]));}return(object)array("name"=>$C,"orgname"=>$C,"orgtable"=>$Q,);}}}}elseif(extension_loaded("pdo_sqlite")){class
Min_SQLite
extends
Min_PDO{var$extension="PDO_SQLite";function
__construct($Sc){$this->dsn(DRIVER.":$Sc","","");}}}if(class_exists("Min_SQLite")){class
Min_DB
extends
Min_SQLite{function
__construct(){parent::__construct(":memory:");$this->query("PRAGMA foreign_keys = 1");}function
select_db($Sc){if(is_readable($Sc)&&$this->query("ATTACH ".$this->quote(preg_match("~(^[/\\\\]|:)~",$Sc)?$Sc:dirname($_SERVER["SCRIPT_FILENAME"])."/$Sc")." AS a")){parent::__construct($Sc);$this->query("PRAGMA foreign_keys = 1");return
true;}return
false;}function
multi_query($G){return$this->_result=$this->query($G);}function
next_result(){return
false;}}}class
Min_Driver
extends
Min_SQL{function
insertUpdate($Q,$K,$hg){$Si=array();foreach($K
as$O)$Si[]="(".implode(", ",$O).")";return
queries("REPLACE INTO ".table($Q)." (".implode(", ",array_keys(reset($K))).") VALUES\n".implode(",\n",$Si));}function
tableHelp($C){if($C=="sqlite_sequence")return"fileformat2.html#seqtab";if($C=="sqlite_master")return"fileformat2.html#$C";}}function
idf_escape($v){return'"'.str_replace('"','""',$v).'"';}function
table($v){return
idf_escape($v);}function
connect(){global$b;list(,,$F)=$b->credentials();if($F!="")return'Database does not support password.';return
new
Min_DB;}function
get_databases(){return
array();}function
limit($G,$Z,$_,$D=0,$M=" "){return" $G$Z".($_!==null?$M."LIMIT $_".($D?" OFFSET $D":""):"");}function
limit1($Q,$G,$Z,$M="\n"){global$g;return(preg_match('~^INTO~',$G)||$g->result("SELECT sqlite_compileoption_used('ENABLE_UPDATE_DELETE_LIMIT')")?limit($G,$Z,1,0,$M):" $G WHERE rowid = (SELECT rowid FROM ".table($Q).$Z.$M."LIMIT 1)");}function
db_collation($m,$ob){global$g;return$g->result("PRAGMA encoding");}function
engines(){return
array();}function
logged_user(){return
get_current_user();}function
tables_list(){return
get_key_vals("SELECT name, type FROM sqlite_master WHERE type IN ('table', 'view') ORDER BY (name = 'sqlite_sequence'), name");}function
count_tables($l){return
array();}function
table_status($C=""){global$g;$I=array();foreach(get_rows("SELECT name AS Name, type AS Engine, 'rowid' AS Oid, '' AS Auto_increment FROM sqlite_master WHERE type IN ('table', 'view') ".($C!=""?"AND name = ".q($C):"ORDER BY name"))as$J){$J["Rows"]=$g->result("SELECT COUNT(*) FROM ".idf_escape($J["Name"]));$I[$J["Name"]]=$J;}foreach(get_rows("SELECT * FROM sqlite_sequence",null,"")as$J)$I[$J["name"]]["Auto_increment"]=$J["seq"];return($C!=""?$I[$C]:$I);}function
is_view($R){return$R["Engine"]=="view";}function
fk_support($R){global$g;return!$g->result("SELECT sqlite_compileoption_used('OMIT_FOREIGN_KEY')");}function
fields($Q){global$g;$I=array();$hg="";foreach(get_rows("PRAGMA table_info(".table($Q).")")as$J){$C=$J["name"];$T=strtolower($J["type"]);$Qb=$J["dflt_value"];$I[$C]=array("field"=>$C,"type"=>(preg_match('~int~i',$T)?"integer":(preg_match('~char|clob|text~i',$T)?"text":(preg_match('~blob~i',$T)?"blob":(preg_match('~real|floa|doub~i',$T)?"real":"numeric")))),"full_type"=>$T,"default"=>(preg_match("~'(.*)'~",$Qb,$B)?str_replace("''","'",$B[1]):($Qb=="NULL"?null:$Qb)),"null"=>!$J["notnull"],"privileges"=>array("select"=>1,"insert"=>1,"update"=>1),"primary"=>$J["pk"],);if($J["pk"]){if($hg!="")$I[$hg]["auto_increment"]=false;elseif(preg_match('~^integer$~i',$T))$I[$C]["auto_increment"]=true;$hg=$C;}}$wh=$g->result("SELECT sql FROM sqlite_master WHERE type = 'table' AND name = ".q($Q));preg_match_all('~(("[^"]*+")+|[a-z0-9_]+)\s+text\s+COLLATE\s+(\'[^\']+\'|\S+)~i',$wh,$Be,PREG_SET_ORDER);foreach($Be
as$B){$C=str_replace('""','"',preg_replace('~^"|"$~','',$B[1]));if($I[$C])$I[$C]["collation"]=trim($B[3],"'");}return$I;}function
indexes($Q,$h=null){global$g;if(!is_object($h))$h=$g;$I=array();$wh=$h->result("SELECT sql FROM sqlite_master WHERE type = 'table' AND name = ".q($Q));if(preg_match('~\bPRIMARY\s+KEY\s*\((([^)"]+|"[^"]*"|`[^`]*`)++)~i',$wh,$B)){$I[""]=array("type"=>"PRIMARY","columns"=>array(),"lengths"=>array(),"descs"=>array());preg_match_all('~((("[^"]*+")+|(?:`[^`]*+`)+)|(\S+))(\s+(ASC|DESC))?(,\s*|$)~i',$B[1],$Be,PREG_SET_ORDER);foreach($Be
as$B){$I[""]["columns"][]=idf_unescape($B[2]).$B[4];$I[""]["descs"][]=(preg_match('~DESC~i',$B[5])?'1':null);}}if(!$I){foreach(fields($Q)as$C=>$p){if($p["primary"])$I[""]=array("type"=>"PRIMARY","columns"=>array($C),"lengths"=>array(),"descs"=>array(null));}}$zh=get_key_vals("SELECT name, sql FROM sqlite_master WHERE type = 'index' AND tbl_name = ".q($Q),$h);foreach(get_rows("PRAGMA index_list(".table($Q).")",$h)as$J){$C=$J["name"];$w=array("type"=>($J["unique"]?"UNIQUE":"INDEX"));$w["lengths"]=array();$w["descs"]=array();foreach(get_rows("PRAGMA index_info(".idf_escape($C).")",$h)as$Ug){$w["columns"][]=$Ug["name"];$w["descs"][]=null;}if(preg_match('~^CREATE( UNIQUE)? INDEX '.preg_quote(idf_escape($C).' ON '.idf_escape($Q),'~').' \((.*)\)$~i',$zh[$C],$Eg)){preg_match_all('/("[^"]*+")+( DESC)?/',$Eg[2],$Be);foreach($Be[2]as$z=>$X){if($X)$w["descs"][$z]='1';}}if(!$I[""]||$w["type"]!="UNIQUE"||$w["columns"]!=$I[""]["columns"]||$w["descs"]!=$I[""]["descs"]||!preg_match("~^sqlite_~",$C))$I[$C]=$w;}return$I;}function
foreign_keys($Q){$I=array();foreach(get_rows("PRAGMA foreign_key_list(".table($Q).")")as$J){$r=&$I[$J["id"]];if(!$r)$r=$J;$r["source"][]=$J["from"];$r["target"][]=$J["to"];}return$I;}function
view($C){global$g;return
array("select"=>preg_replace('~^(?:[^`"[]+|`[^`]*`|"[^"]*")* AS\s+~iU','',$g->result("SELECT sql FROM sqlite_master WHERE name = ".q($C))));}function
collations(){return(isset($_GET["create"])?get_vals("PRAGMA collation_list",1):array());}function
information_schema($m){return
false;}function
error(){global$g;return
h($g->error);}function
check_sqlite_name($C){global$g;$Ic="db|sdb|sqlite";if(!preg_match("~^[^\\0]*\\.($Ic)\$~",$C)){$g->error=sprintf('Please use one of the extensions %s.',str_replace("|",", ",$Ic));return
false;}return
true;}function
create_database($m,$d){global$g;if(file_exists($m)){$g->error='File exists.';return
false;}if(!check_sqlite_name($m))return
false;try{$A=new
Min_SQLite($m);}catch(Exception$zc){$g->error=$zc->getMessage();return
false;}$A->query('PRAGMA encoding = "UTF-8"');$A->query('CREATE TABLE adminer (i)');$A->query('DROP TABLE adminer');return
true;}function
drop_databases($l){global$g;$g->__construct(":memory:");foreach($l
as$m){if(!@unlink($m)){$g->error='File exists.';return
false;}}return
true;}function
rename_database($C,$d){global$g;if(!check_sqlite_name($C))return
false;$g->__construct(":memory:");$g->error='File exists.';return@rename(DB,$C);}function
auto_increment(){return" PRIMARY KEY".(DRIVER=="sqlite"?" AUTOINCREMENT":"");}function
alter_table($Q,$C,$q,$ad,$tb,$sc,$d,$La,$Rf){$Mi=($Q==""||$ad);foreach($q
as$p){if($p[0]!=""||!$p[1]||$p[2]){$Mi=true;break;}}$c=array();$Ff=array();foreach($q
as$p){if($p[1]){$c[]=($Mi?$p[1]:"ADD ".implode($p[1]));if($p[0]!="")$Ff[$p[0]]=$p[1][0];}}if(!$Mi){foreach($c
as$X){if(!queries("ALTER TABLE ".table($Q)." $X"))return
false;}if($Q!=$C&&!queries("ALTER TABLE ".table($Q)." RENAME TO ".table($C)))return
false;}elseif(!recreate_table($Q,$C,$c,$Ff,$ad))return
false;if($La)queries("UPDATE sqlite_sequence SET seq = $La WHERE name = ".q($C));return
true;}function
recreate_table($Q,$C,$q,$Ff,$ad,$x=array()){if($Q!=""){if(!$q){foreach(fields($Q)as$z=>$p){if($x)$p["auto_increment"]=0;$q[]=process_field($p,$p);$Ff[$z]=idf_escape($z);}}$ig=false;foreach($q
as$p){if($p[6])$ig=true;}$fc=array();foreach($x
as$z=>$X){if($X[2]=="DROP"){$fc[$X[1]]=true;unset($x[$z]);}}foreach(indexes($Q)as$ee=>$w){$f=array();foreach($w["columns"]as$z=>$e){if(!$Ff[$e])continue
2;$f[]=$Ff[$e].($w["descs"][$z]?" DESC":"");}if(!$fc[$ee]){if($w["type"]!="PRIMARY"||!$ig)$x[]=array($w["type"],$ee,$f);}}foreach($x
as$z=>$X){if($X[0]=="PRIMARY"){unset($x[$z]);$ad[]="  PRIMARY KEY (".implode(", ",$X[2]).")";}}foreach(foreign_keys($Q)as$ee=>$r){foreach($r["source"]as$z=>$e){if(!$Ff[$e])continue
2;$r["source"][$z]=idf_unescape($Ff[$e]);}if(!isset($ad[" $ee"]))$ad[]=" ".format_foreign_key($r);}queries("BEGIN");}foreach($q
as$z=>$p)$q[$z]="  ".implode($p);$q=array_merge($q,array_filter($ad));if(!queries("CREATE TABLE ".table($Q!=""?"adminer_$C":$C)." (\n".implode(",\n",$q)."\n)"))return
false;if($Q!=""){if($Ff&&!queries("INSERT INTO ".table("adminer_$C")." (".implode(", ",$Ff).") SELECT ".implode(", ",array_map('idf_escape',array_keys($Ff)))." FROM ".table($Q)))return
false;$yi=array();foreach(triggers($Q)as$wi=>$ei){$vi=trigger($wi);$yi[]="CREATE TRIGGER ".idf_escape($wi)." ".implode(" ",$ei)." ON ".table($C)."\n$vi[Statement]";}if(!queries("DROP TABLE ".table($Q)))return
false;queries("ALTER TABLE ".table("adminer_$C")." RENAME TO ".table($C));if(!alter_indexes($C,$x))return
false;foreach($yi
as$vi){if(!queries($vi))return
false;}queries("COMMIT");}return
true;}function
index_sql($Q,$T,$C,$f){return"CREATE $T ".($T!="INDEX"?"INDEX ":"").idf_escape($C!=""?$C:uniqid($Q."_"))." ON ".table($Q)." $f";}function
alter_indexes($Q,$c){foreach($c
as$hg){if($hg[0]=="PRIMARY")return
recreate_table($Q,$Q,array(),array(),array(),$c);}foreach(array_reverse($c)as$X){if(!queries($X[2]=="DROP"?"DROP INDEX ".idf_escape($X[1]):index_sql($Q,$X[0],$X[1],"(".implode(", ",$X[2]).")")))return
false;}return
true;}function
truncate_tables($S){return
apply_queries("DELETE FROM",$S);}function
drop_views($Xi){return
apply_queries("DROP VIEW",$Xi);}function
drop_tables($S){return
apply_queries("DROP TABLE",$S);}function
move_tables($S,$Xi,$Vh){return
false;}function
trigger($C){global$g;if($C=="")return
array("Statement"=>"BEGIN\n\t;\nEND");$v='(?:[^`"\s]+|`[^`]*`|"[^"]*")+';$xi=trigger_options();preg_match("~^CREATE\\s+TRIGGER\\s*$v\\s*(".implode("|",$xi["Timing"]).")\\s+([a-z]+)(?:\\s+OF\\s+($v))?\\s+ON\\s*$v\\s*(?:FOR\\s+EACH\\s+ROW\\s)?(.*)~is",$g->result("SELECT sql FROM sqlite_master WHERE type = 'trigger' AND name = ".q($C)),$B);$gf=$B[3];return
array("Timing"=>strtoupper($B[1]),"Event"=>strtoupper($B[2]).($gf?" OF":""),"Of"=>($gf[0]=='`'||$gf[0]=='"'?idf_unescape($gf):$gf),"Trigger"=>$C,"Statement"=>$B[4],);}function
triggers($Q){$I=array();$xi=trigger_options();foreach(get_rows("SELECT * FROM sqlite_master WHERE type = 'trigger' AND tbl_name = ".q($Q))as$J){preg_match('~^CREATE\s+TRIGGER\s*(?:[^`"\s]+|`[^`]*`|"[^"]*")+\s*('.implode("|",$xi["Timing"]).')\s*(.*)\s+ON\b~iU',$J["sql"],$B);$I[$J["name"]]=array($B[1],$B[2]);}return$I;}function
trigger_options(){return
array("Timing"=>array("BEFORE","AFTER","INSTEAD OF"),"Event"=>array("INSERT","UPDATE","UPDATE OF","DELETE"),"Type"=>array("FOR EACH ROW"),);}function
begin(){return
queries("BEGIN");}function
last_id(){global$g;return$g->result("SELECT LAST_INSERT_ROWID()");}function
explain($g,$G){return$g->query("EXPLAIN QUERY PLAN $G");}function
found_rows($R,$Z){}function
types(){return
array();}function
schemas(){return
array();}function
get_schema(){return"";}function
set_schema($Yg){return
true;}function
create_sql($Q,$La,$Gh){global$g;$I=$g->result("SELECT sql FROM sqlite_master WHERE type IN ('table', 'view') AND name = ".q($Q));foreach(indexes($Q)as$C=>$w){if($C=='')continue;$I.=";\n\n".index_sql($Q,$w['type'],$C,"(".implode(", ",array_map('idf_escape',$w['columns'])).")");}return$I;}function
truncate_sql($Q){return"DELETE FROM ".table($Q);}function
use_sql($k){}function
trigger_sql($Q){return
implode(get_vals("SELECT sql || ';;\n' FROM sqlite_master WHERE type = 'trigger' AND tbl_name = ".q($Q)));}function
show_variables(){global$g;$I=array();foreach(array("auto_vacuum","cache_size","count_changes","default_cache_size","empty_result_callbacks","encoding","foreign_keys","full_column_names","fullfsync","journal_mode","journal_size_limit","legacy_file_format","locking_mode","page_size","max_page_count","read_uncommitted","recursive_triggers","reverse_unordered_selects","secure_delete","short_column_names","synchronous","temp_store","temp_store_directory","schema_version","integrity_check","quick_check")as$z)$I[$z]=$g->result("PRAGMA $z");return$I;}function
show_status(){$I=array();foreach(get_vals("PRAGMA compile_options")as$uf){list($z,$X)=explode("=",$uf,2);$I[$z]=$X;}return$I;}function
convert_field($p){}function
unconvert_field($p,$I){return$I;}function
support($Nc){return
preg_match('~^(columns|database|drop_col|dump|indexes|descidx|move_col|sql|status|table|trigger|variables|view|view_trigger)$~',$Nc);}$y="sqlite";$U=array("integer"=>0,"real"=>0,"numeric"=>0,"text"=>0,"blob"=>0);$Fh=array_keys($U);$Gi=array();$sf=array("=","<",">","<=",">=","!=","LIKE","LIKE %%","IN","IS NULL","NOT LIKE","NOT IN","IS NOT NULL","SQL");$id=array("hex","length","lower","round","unixepoch","upper");$od=array("avg","count","count distinct","group_concat","max","min","sum");$kc=array(array(),array("integer|real|numeric"=>"+/-","text"=>"||",));}$cc["pgsql"]="PostgreSQL";if(isset($_GET["pgsql"])){$eg=array("PgSQL","PDO_PgSQL");define("DRIVER","pgsql");if(extension_loaded("pgsql")){class
Min_DB{var$extension="PgSQL",$_link,$_result,$_string,$_database=true,$server_info,$affected_rows,$error,$timeout;function
_error($vc,$o){if(ini_bool("html_errors"))$o=html_entity_decode(strip_tags($o));$o=preg_replace('~^[^:]*: ~','',$o);$this->error=$o;}function
connect($N,$V,$F){global$b;$m=$b->database();set_error_handler(array($this,'_error'));$this->_string="host='".str_replace(":","' port='",addcslashes($N,"'\\"))."' user='".addcslashes($V,"'\\")."' password='".addcslashes($F,"'\\")."'";$this->_link=@pg_connect("$this->_string dbname='".($m!=""?addcslashes($m,"'\\"):"postgres")."'",PGSQL_CONNECT_FORCE_NEW);if(!$this->_link&&$m!=""){$this->_database=false;$this->_link=@pg_connect("$this->_string dbname='postgres'",PGSQL_CONNECT_FORCE_NEW);}restore_error_handler();if($this->_link){$Vi=pg_version($this->_link);$this->server_info=$Vi["server"];pg_set_client_encoding($this->_link,"UTF8");}return(bool)$this->_link;}function
quote($P){return"'".pg_escape_string($this->_link,$P)."'";}function
value($X,$p){return($p["type"]=="bytea"?pg_unescape_bytea($X):$X);}function
quoteBinary($P){return"'".pg_escape_bytea($this->_link,$P)."'";}function
select_db($k){global$b;if($k==$b->database())return$this->_database;$I=@pg_connect("$this->_string dbname='".addcslashes($k,"'\\")."'",PGSQL_CONNECT_FORCE_NEW);if($I)$this->_link=$I;return$I;}function
close(){$this->_link=@pg_connect("$this->_string dbname='postgres'");}function
query($G,$Ai=false){$H=@pg_query($this->_link,$G);$this->error="";if(!$H){$this->error=pg_last_error($this->_link);$I=false;}elseif(!pg_num_fields($H)){$this->affected_rows=pg_affected_rows($H);$I=true;}else$I=new
Min_Result($H);if($this->timeout){$this->timeout=0;$this->query("RESET statement_timeout");}return$I;}function
multi_query($G){return$this->_result=$this->query($G);}function
store_result(){return$this->_result;}function
next_result(){return
false;}function
result($G,$p=0){$H=$this->query($G);if(!$H||!$H->num_rows)return
false;return
pg_fetch_result($H->_result,0,$p);}function
warnings(){return
h(pg_last_notice($this->_link));}}class
Min_Result{var$_result,$_offset=0,$num_rows;function
__construct($H){$this->_result=$H;$this->num_rows=pg_num_rows($H);}function
fetch_assoc(){return
pg_fetch_assoc($this->_result);}function
fetch_row(){return
pg_fetch_row($this->_result);}function
fetch_field(){$e=$this->_offset++;$I=new
stdClass;if(function_exists('pg_field_table'))$I->orgtable=pg_field_table($this->_result,$e);$I->name=pg_field_name($this->_result,$e);$I->orgname=$I->name;$I->type=pg_field_type($this->_result,$e);$I->charsetnr=($I->type=="bytea"?63:0);return$I;}function
__destruct(){pg_free_result($this->_result);}}}elseif(extension_loaded("pdo_pgsql")){class
Min_DB
extends
Min_PDO{var$extension="PDO_PgSQL",$timeout;function
connect($N,$V,$F){global$b;$m=$b->database();$P="pgsql:host='".str_replace(":","' port='",addcslashes($N,"'\\"))."' options='-c client_encoding=utf8'";$this->dsn("$P dbname='".($m!=""?addcslashes($m,"'\\"):"postgres")."'",$V,$F);return
true;}function
select_db($k){global$b;return($b->database()==$k);}function
quoteBinary($Vg){return
q($Vg);}function
query($G,$Ai=false){$I=parent::query($G,$Ai);if($this->timeout){$this->timeout=0;parent::query("RESET statement_timeout");}return$I;}function
warnings(){return'';}function
close(){}}}class
Min_Driver
extends
Min_SQL{function
insertUpdate($Q,$K,$hg){global$g;foreach($K
as$O){$Hi=array();$Z=array();foreach($O
as$z=>$X){$Hi[]="$z = $X";if(isset($hg[idf_unescape($z)]))$Z[]="$z = $X";}if(!(($Z&&queries("UPDATE ".table($Q)." SET ".implode(", ",$Hi)." WHERE ".implode(" AND ",$Z))&&$g->affected_rows)||queries("INSERT INTO ".table($Q)." (".implode(", ",array_keys($O)).") VALUES (".implode(", ",$O).")")))return
false;}return
true;}function
slowQuery($G,$di){$this->_conn->query("SET statement_timeout = ".(1000*$di));$this->_conn->timeout=1000*$di;return$G;}function
convertSearch($v,$X,$p){return(preg_match('~char|text'.(!preg_match('~LIKE~',$X["op"])?'|date|time(stamp)?|boolean|uuid|'.number_type():'').'~',$p["type"])?$v:"CAST($v AS text)");}function
quoteBinary($Vg){return$this->_conn->quoteBinary($Vg);}function
warnings(){return$this->_conn->warnings();}function
tableHelp($C){$ue=array("information_schema"=>"infoschema","pg_catalog"=>"catalog",);$A=$ue[$_GET["ns"]];if($A)return"$A-".str_replace("_","-",$C).".html";}}function
idf_escape($v){return'"'.str_replace('"','""',$v).'"';}function
table($v){return
idf_escape($v);}function
connect(){global$b,$U,$Fh;$g=new
Min_DB;$j=$b->credentials();if($g->connect($j[0],$j[1],$j[2])){if(min_version(9,0,$g)){$g->query("SET application_name = 'Adminer'");if(min_version(9.2,0,$g)){$Fh['Strings'][]="json";$U["json"]=4294967295;if(min_version(9.4,0,$g)){$Fh['Strings'][]="jsonb";$U["jsonb"]=4294967295;}}}return$g;}return$g->error;}function
get_databases(){return
get_vals("SELECT datname FROM pg_database WHERE has_database_privilege(datname, 'CONNECT') ORDER BY datname");}function
limit($G,$Z,$_,$D=0,$M=" "){return" $G$Z".($_!==null?$M."LIMIT $_".($D?" OFFSET $D":""):"");}function
limit1($Q,$G,$Z,$M="\n"){return(preg_match('~^INTO~',$G)?limit($G,$Z,1,0,$M):" $G".(is_view(table_status1($Q))?$Z:" WHERE ctid = (SELECT ctid FROM ".table($Q).$Z.$M."LIMIT 1)"));}function
db_collation($m,$ob){global$g;return$g->result("SHOW LC_COLLATE");}function
engines(){return
array();}function
logged_user(){global$g;return$g->result("SELECT user");}function
tables_list(){$G="SELECT table_name, table_type FROM information_schema.tables WHERE table_schema = current_schema()";if(support('materializedview'))$G.="
UNION ALL
SELECT matviewname, 'MATERIALIZED VIEW'
FROM pg_matviews
WHERE schemaname = current_schema()";$G.="
ORDER BY 1";return
get_key_vals($G);}function
count_tables($l){return
array();}function
table_status($C=""){$I=array();foreach(get_rows("SELECT c.relname AS \"Name\", CASE c.relkind WHEN 'r' THEN 'table' WHEN 'm' THEN 'materialized view' ELSE 'view' END AS \"Engine\", pg_relation_size(c.oid) AS \"Data_length\", pg_total_relation_size(c.oid) - pg_relation_size(c.oid) AS \"Index_length\", obj_description(c.oid, 'pg_class') AS \"Comment\", CASE WHEN c.relhasoids THEN 'oid' ELSE '' END AS \"Oid\", c.reltuples as \"Rows\", n.nspname
FROM pg_class c
JOIN pg_namespace n ON(n.nspname = current_schema() AND n.oid = c.relnamespace)
WHERE relkind IN ('r', 'm', 'v', 'f')
".($C!=""?"AND relname = ".q($C):"ORDER BY relname"))as$J)$I[$J["Name"]]=$J;return($C!=""?$I[$C]:$I);}function
is_view($R){return
in_array($R["Engine"],array("view","materialized view"));}function
fk_support($R){return
true;}function
fields($Q){$I=array();$Ca=array('timestamp without time zone'=>'timestamp','timestamp with time zone'=>'timestamptz',);$Bd=min_version(10)?"(a.attidentity = 'd')::int":'0';foreach(get_rows("SELECT a.attname AS field, format_type(a.atttypid, a.atttypmod) AS full_type, d.adsrc AS default, a.attnotnull::int, col_description(c.oid, a.attnum) AS comment, $Bd AS identity
FROM pg_class c
JOIN pg_namespace n ON c.relnamespace = n.oid
JOIN pg_attribute a ON c.oid = a.attrelid
LEFT JOIN pg_attrdef d ON c.oid = d.adrelid AND a.attnum = d.adnum
WHERE c.relname = ".q($Q)."
AND n.nspname = current_schema()
AND NOT a.attisdropped
AND a.attnum > 0
ORDER BY a.attnum")as$J){preg_match('~([^([]+)(\((.*)\))?([a-z ]+)?((\[[0-9]*])*)$~',$J["full_type"],$B);list(,$T,$re,$J["length"],$wa,$Fa)=$B;$J["length"].=$Fa;$db=$T.$wa;if(isset($Ca[$db])){$J["type"]=$Ca[$db];$J["full_type"]=$J["type"].$re.$Fa;}else{$J["type"]=$T;$J["full_type"]=$J["type"].$re.$wa.$Fa;}if($J['identity'])$J['default']='GENERATED BY DEFAULT AS IDENTITY';$J["null"]=!$J["attnotnull"];$J["auto_increment"]=$J['identity']||preg_match('~^nextval\(~i',$J["default"]);$J["privileges"]=array("insert"=>1,"select"=>1,"update"=>1);if(preg_match('~(.+)::[^)]+(.*)~',$J["default"],$B))$J["default"]=($B[1]=="NULL"?null:(($B[1][0]=="'"?idf_unescape($B[1]):$B[1]).$B[2]));$I[$J["field"]]=$J;}return$I;}function
indexes($Q,$h=null){global$g;if(!is_object($h))$h=$g;$I=array();$Oh=$h->result("SELECT oid FROM pg_class WHERE relnamespace = (SELECT oid FROM pg_namespace WHERE nspname = current_schema()) AND relname = ".q($Q));$f=get_key_vals("SELECT attnum, attname FROM pg_attribute WHERE attrelid = $Oh AND attnum > 0",$h);foreach(get_rows("SELECT relname, indisunique::int, indisprimary::int, indkey, indoption , (indpred IS NOT NULL)::int as indispartial FROM pg_index i, pg_class ci WHERE i.indrelid = $Oh AND ci.oid = i.indexrelid",$h)as$J){$Fg=$J["relname"];$I[$Fg]["type"]=($J["indispartial"]?"INDEX":($J["indisprimary"]?"PRIMARY":($J["indisunique"]?"UNIQUE":"INDEX")));$I[$Fg]["columns"]=array();foreach(explode(" ",$J["indkey"])as$Ld)$I[$Fg]["columns"][]=$f[$Ld];$I[$Fg]["descs"]=array();foreach(explode(" ",$J["indoption"])as$Md)$I[$Fg]["descs"][]=($Md&1?'1':null);$I[$Fg]["lengths"]=array();}return$I;}function
foreign_keys($Q){global$nf;$I=array();foreach(get_rows("SELECT conname, condeferrable::int AS deferrable, pg_get_constraintdef(oid) AS definition
FROM pg_constraint
WHERE conrelid = (SELECT pc.oid FROM pg_class AS pc INNER JOIN pg_namespace AS pn ON (pn.oid = pc.relnamespace) WHERE pc.relname = ".q($Q)." AND pn.nspname = current_schema())
AND contype = 'f'::char
ORDER BY conkey, conname")as$J){if(preg_match('~FOREIGN KEY\s*\((.+)\)\s*REFERENCES (.+)\((.+)\)(.*)$~iA',$J['definition'],$B)){$J['source']=array_map('trim',explode(',',$B[1]));if(preg_match('~^(("([^"]|"")+"|[^"]+)\.)?"?("([^"]|"")+"|[^"]+)$~',$B[2],$Ae)){$J['ns']=str_replace('""','"',preg_replace('~^"(.+)"$~','\1',$Ae[2]));$J['table']=str_replace('""','"',preg_replace('~^"(.+)"$~','\1',$Ae[4]));}$J['target']=array_map('trim',explode(',',$B[3]));$J['on_delete']=(preg_match("~ON DELETE ($nf)~",$B[4],$Ae)?$Ae[1]:'NO ACTION');$J['on_update']=(preg_match("~ON UPDATE ($nf)~",$B[4],$Ae)?$Ae[1]:'NO ACTION');$I[$J['conname']]=$J;}}return$I;}function
view($C){global$g;return
array("select"=>trim($g->result("SELECT view_definition
FROM information_schema.views
WHERE table_schema = current_schema() AND table_name = ".q($C))));}function
collations(){return
array();}function
information_schema($m){return($m=="information_schema");}function
error(){global$g;$I=h($g->error);if(preg_match('~^(.*\n)?([^\n]*)\n( *)\^(\n.*)?$~s',$I,$B))$I=$B[1].preg_replace('~((?:[^&]|&[^;]*;){'.strlen($B[3]).'})(.*)~','\1<b>\2</b>',$B[2]).$B[4];return
nl_br($I);}function
create_database($m,$d){return
queries("CREATE DATABASE ".idf_escape($m).($d?" ENCODING ".idf_escape($d):""));}function
drop_databases($l){global$g;$g->close();return
apply_queries("DROP DATABASE",$l,'idf_escape');}function
rename_database($C,$d){return
queries("ALTER DATABASE ".idf_escape(DB)." RENAME TO ".idf_escape($C));}function
auto_increment(){return"";}function
alter_table($Q,$C,$q,$ad,$tb,$sc,$d,$La,$Rf){$c=array();$sg=array();foreach($q
as$p){$e=idf_escape($p[0]);$X=$p[1];if(!$X)$c[]="DROP $e";else{$Ri=$X[5];unset($X[5]);if(isset($X[6])&&$p[0]=="")$X[1]=($X[1]=="bigint"?" big":" ")."serial";if($p[0]=="")$c[]=($Q!=""?"ADD ":"  ").implode($X);else{if($e!=$X[0])$sg[]="ALTER TABLE ".table($Q)." RENAME $e TO $X[0]";$c[]="ALTER $e TYPE$X[1]";if(!$X[6]){$c[]="ALTER $e ".($X[3]?"SET$X[3]":"DROP DEFAULT");$c[]="ALTER $e ".($X[2]==" NULL"?"DROP NOT":"SET").$X[2];}}if($p[0]!=""||$Ri!="")$sg[]="COMMENT ON COLUMN ".table($Q).".$X[0] IS ".($Ri!=""?substr($Ri,9):"''");}}$c=array_merge($c,$ad);if($Q=="")array_unshift($sg,"CREATE TABLE ".table($C)." (\n".implode(",\n",$c)."\n)");elseif($c)array_unshift($sg,"ALTER TABLE ".table($Q)."\n".implode(",\n",$c));if($Q!=""&&$Q!=$C)$sg[]="ALTER TABLE ".table($Q)." RENAME TO ".table($C);if($Q!=""||$tb!="")$sg[]="COMMENT ON TABLE ".table($C)." IS ".q($tb);if($La!=""){}foreach($sg
as$G){if(!queries($G))return
false;}return
true;}function
alter_indexes($Q,$c){$i=array();$dc=array();$sg=array();foreach($c
as$X){if($X[0]!="INDEX")$i[]=($X[2]=="DROP"?"\nDROP CONSTRAINT ".idf_escape($X[1]):"\nADD".($X[1]!=""?" CONSTRAINT ".idf_escape($X[1]):"")." $X[0] ".($X[0]=="PRIMARY"?"KEY ":"")."(".implode(", ",$X[2]).")");elseif($X[2]=="DROP")$dc[]=idf_escape($X[1]);else$sg[]="CREATE INDEX ".idf_escape($X[1]!=""?$X[1]:uniqid($Q."_"))." ON ".table($Q)." (".implode(", ",$X[2]).")";}if($i)array_unshift($sg,"ALTER TABLE ".table($Q).implode(",",$i));if($dc)array_unshift($sg,"DROP INDEX ".implode(", ",$dc));foreach($sg
as$G){if(!queries($G))return
false;}return
true;}function
truncate_tables($S){return
queries("TRUNCATE ".implode(", ",array_map('table',$S)));return
true;}function
drop_views($Xi){return
drop_tables($Xi);}function
drop_tables($S){foreach($S
as$Q){$Ch=table_status($Q);if(!queries("DROP ".strtoupper($Ch["Engine"])." ".table($Q)))return
false;}return
true;}function
move_tables($S,$Xi,$Vh){foreach(array_merge($S,$Xi)as$Q){$Ch=table_status($Q);if(!queries("ALTER ".strtoupper($Ch["Engine"])." ".table($Q)." SET SCHEMA ".idf_escape($Vh)))return
false;}return
true;}function
trigger($C,$Q=null){if($C=="")return
array("Statement"=>"EXECUTE PROCEDURE ()");if($Q===null)$Q=$_GET['trigger'];$K=get_rows('SELECT t.trigger_name AS "Trigger", t.action_timing AS "Timing", (SELECT STRING_AGG(event_manipulation, \' OR \') FROM information_schema.triggers WHERE event_object_table = t.event_object_table AND trigger_name = t.trigger_name ) AS "Events", t.event_manipulation AS "Event", \'FOR EACH \' || t.action_orientation AS "Type", t.action_statement AS "Statement" FROM information_schema.triggers t WHERE t.event_object_table = '.q($Q).' AND t.trigger_name = '.q($C));return
reset($K);}function
triggers($Q){$I=array();foreach(get_rows("SELECT * FROM information_schema.triggers WHERE event_object_table = ".q($Q))as$J)$I[$J["trigger_name"]]=array($J["action_timing"],$J["event_manipulation"]);return$I;}function
trigger_options(){return
array("Timing"=>array("BEFORE","AFTER"),"Event"=>array("INSERT","UPDATE","DELETE"),"Type"=>array("FOR EACH ROW","FOR EACH STATEMENT"),);}function
routine($C,$T){$K=get_rows('SELECT routine_definition AS definition, LOWER(external_language) AS language, *
FROM information_schema.routines
WHERE routine_schema = current_schema() AND specific_name = '.q($C));$I=$K[0];$I["returns"]=array("type"=>$I["type_udt_name"]);$I["fields"]=get_rows('SELECT parameter_name AS field, data_type AS type, character_maximum_length AS length, parameter_mode AS inout
FROM information_schema.parameters
WHERE specific_schema = current_schema() AND specific_name = '.q($C).'
ORDER BY ordinal_position');return$I;}function
routines(){return
get_rows('SELECT specific_name AS "SPECIFIC_NAME", routine_type AS "ROUTINE_TYPE", routine_name AS "ROUTINE_NAME", type_udt_name AS "DTD_IDENTIFIER"
FROM information_schema.routines
WHERE routine_schema = current_schema()
ORDER BY SPECIFIC_NAME');}function
routine_languages(){return
get_vals("SELECT LOWER(lanname) FROM pg_catalog.pg_language");}function
routine_id($C,$J){$I=array();foreach($J["fields"]as$p)$I[]=$p["type"];return
idf_escape($C)."(".implode(", ",$I).")";}function
last_id(){return
0;}function
explain($g,$G){return$g->query("EXPLAIN $G");}function
found_rows($R,$Z){global$g;if(preg_match("~ rows=([0-9]+)~",$g->result("EXPLAIN SELECT * FROM ".idf_escape($R["Name"]).($Z?" WHERE ".implode(" AND ",$Z):"")),$Eg))return$Eg[1];return
false;}function
types(){return
get_vals("SELECT typname
FROM pg_type
WHERE typnamespace = (SELECT oid FROM pg_namespace WHERE nspname = current_schema())
AND typtype IN ('b','d','e')
AND typelem = 0");}function
schemas(){return
get_vals("SELECT nspname FROM pg_namespace ORDER BY nspname");}function
get_schema(){global$g;return$g->result("SELECT current_schema()");}function
set_schema($Xg){global$g,$U,$Fh;$I=$g->query("SET search_path TO ".idf_escape($Xg));foreach(types()as$T){if(!isset($U[$T])){$U[$T]=0;$Fh['User types'][]=$T;}}return$I;}function
create_sql($Q,$La,$Gh){global$g;$I='';$Ng=array();$hh=array();$Ch=table_status($Q);$q=fields($Q);$x=indexes($Q);ksort($x);$Xc=foreign_keys($Q);ksort($Xc);if(!$Ch||empty($q))return
false;$I="CREATE TABLE ".idf_escape($Ch['nspname']).".".idf_escape($Ch['Name'])." (\n    ";foreach($q
as$Pc=>$p){$Of=idf_escape($p['field']).' '.$p['full_type'].default_value($p).($p['attnotnull']?" NOT NULL":"");$Ng[]=$Of;if(preg_match('~nextval\(\'([^\']+)\'\)~',$p['default'],$Be)){$gh=$Be[1];$vh=reset(get_rows(min_version(10)?"SELECT *, cache_size AS cache_value FROM pg_sequences WHERE schemaname = current_schema() AND sequencename = ".q($gh):"SELECT * FROM $gh"));$hh[]=($Gh=="DROP+CREATE"?"DROP SEQUENCE IF EXISTS $gh;\n":"")."CREATE SEQUENCE $gh INCREMENT $vh[increment_by] MINVALUE $vh[min_value] MAXVALUE $vh[max_value] START ".($La?$vh['last_value']:1)." CACHE $vh[cache_value];";}}if(!empty($hh))$I=implode("\n\n",$hh)."\n\n$I";foreach($x
as$Gd=>$w){switch($w['type']){case'UNIQUE':$Ng[]="CONSTRAINT ".idf_escape($Gd)." UNIQUE (".implode(', ',array_map('idf_escape',$w['columns'])).")";break;case'PRIMARY':$Ng[]="CONSTRAINT ".idf_escape($Gd)." PRIMARY KEY (".implode(', ',array_map('idf_escape',$w['columns'])).")";break;}}foreach($Xc
as$Wc=>$Vc)$Ng[]="CONSTRAINT ".idf_escape($Wc)." $Vc[definition] ".($Vc['deferrable']?'DEFERRABLE':'NOT DEFERRABLE');$I.=implode(",\n    ",$Ng)."\n) WITH (oids = ".($Ch['Oid']?'true':'false').");";foreach($x
as$Gd=>$w){if($w['type']=='INDEX'){$f=array();foreach($w['columns']as$z=>$X)$f[]=idf_escape($X).($w['descs'][$z]?" DESC":"");$I.="\n\nCREATE INDEX ".idf_escape($Gd)." ON ".idf_escape($Ch['nspname']).".".idf_escape($Ch['Name'])." USING btree (".implode(', ',$f).");";}}if($Ch['Comment'])$I.="\n\nCOMMENT ON TABLE ".idf_escape($Ch['nspname']).".".idf_escape($Ch['Name'])." IS ".q($Ch['Comment']).";";foreach($q
as$Pc=>$p){if($p['comment'])$I.="\n\nCOMMENT ON COLUMN ".idf_escape($Ch['nspname']).".".idf_escape($Ch['Name']).".".idf_escape($Pc)." IS ".q($p['comment']).";";}return
rtrim($I,';');}function
truncate_sql($Q){return"TRUNCATE ".table($Q);}function
trigger_sql($Q){$Ch=table_status($Q);$I="";foreach(triggers($Q)as$ui=>$ti){$vi=trigger($ui,$Ch['Name']);$I.="\nCREATE TRIGGER ".idf_escape($vi['Trigger'])." $vi[Timing] $vi[Events] ON ".idf_escape($Ch["nspname"]).".".idf_escape($Ch['Name'])." $vi[Type] $vi[Statement];;\n";}return$I;}function
use_sql($k){return"\connect ".idf_escape($k);}function
show_variables(){return
get_key_vals("SHOW ALL");}function
process_list(){return
get_rows("SELECT * FROM pg_stat_activity ORDER BY ".(min_version(9.2)?"pid":"procpid"));}function
show_status(){}function
convert_field($p){}function
unconvert_field($p,$I){return$I;}function
support($Nc){return
preg_match('~^(database|table|columns|sql|indexes|descidx|comment|view|'.(min_version(9.3)?'materializedview|':'').'scheme|routine|processlist|sequence|trigger|type|variables|drop_col|kill|dump)$~',$Nc);}function
kill_process($X){return
queries("SELECT pg_terminate_backend(".number($X).")");}function
connection_id(){return"SELECT pg_backend_pid()";}function
max_connections(){global$g;return$g->result("SHOW max_connections");}$y="pgsql";$U=array();$Fh=array();foreach(array('Numbers'=>array("smallint"=>5,"integer"=>10,"bigint"=>19,"boolean"=>1,"numeric"=>0,"real"=>7,"double precision"=>16,"money"=>20),'Date and time'=>array("date"=>13,"time"=>17,"timestamp"=>20,"timestamptz"=>21,"interval"=>0),'Strings'=>array("character"=>0,"character varying"=>0,"text"=>0,"tsquery"=>0,"tsvector"=>0,"uuid"=>0,"xml"=>0),'Binary'=>array("bit"=>0,"bit varying"=>0,"bytea"=>0),'Network'=>array("cidr"=>43,"inet"=>43,"macaddr"=>17,"txid_snapshot"=>0),'Geometry'=>array("box"=>0,"circle"=>0,"line"=>0,"lseg"=>0,"path"=>0,"point"=>0,"polygon"=>0),)as$z=>$X){$U+=$X;$Fh[$z]=array_keys($X);}$Gi=array();$sf=array("=","<",">","<=",">=","!=","~","!~","LIKE","LIKE %%","ILIKE","ILIKE %%","IN","IS NULL","NOT LIKE","NOT IN","IS NOT NULL");$id=array("char_length","lower","round","to_hex","to_timestamp","upper");$od=array("avg","count","count distinct","max","min","sum");$kc=array(array("char"=>"md5","date|time"=>"now",),array(number_type()=>"+/-","date|time"=>"+ interval/- interval","char|text"=>"||",));}$cc["oracle"]="Oracle (beta)";if(isset($_GET["oracle"])){$eg=array("OCI8","PDO_OCI");define("DRIVER","oracle");if(extension_loaded("oci8")){class
Min_DB{var$extension="oci8",$_link,$_result,$server_info,$affected_rows,$errno,$error;function
_error($vc,$o){if(ini_bool("html_errors"))$o=html_entity_decode(strip_tags($o));$o=preg_replace('~^[^:]*: ~','',$o);$this->error=$o;}function
connect($N,$V,$F){$this->_link=@oci_new_connect($V,$F,$N,"AL32UTF8");if($this->_link){$this->server_info=oci_server_version($this->_link);return
true;}$o=oci_error();$this->error=$o["message"];return
false;}function
quote($P){return"'".str_replace("'","''",$P)."'";}function
select_db($k){return
true;}function
query($G,$Ai=false){$H=oci_parse($this->_link,$G);$this->error="";if(!$H){$o=oci_error($this->_link);$this->errno=$o["code"];$this->error=$o["message"];return
false;}set_error_handler(array($this,'_error'));$I=@oci_execute($H);restore_error_handler();if($I){if(oci_num_fields($H))return
new
Min_Result($H);$this->affected_rows=oci_num_rows($H);}return$I;}function
multi_query($G){return$this->_result=$this->query($G);}function
store_result(){return$this->_result;}function
next_result(){return
false;}function
result($G,$p=1){$H=$this->query($G);if(!is_object($H)||!oci_fetch($H->_result))return
false;return
oci_result($H->_result,$p);}}class
Min_Result{var$_result,$_offset=1,$num_rows;function
__construct($H){$this->_result=$H;}function
_convert($J){foreach((array)$J
as$z=>$X){if(is_a($X,'OCI-Lob'))$J[$z]=$X->load();}return$J;}function
fetch_assoc(){return$this->_convert(oci_fetch_assoc($this->_result));}function
fetch_row(){return$this->_convert(oci_fetch_row($this->_result));}function
fetch_field(){$e=$this->_offset++;$I=new
stdClass;$I->name=oci_field_name($this->_result,$e);$I->orgname=$I->name;$I->type=oci_field_type($this->_result,$e);$I->charsetnr=(preg_match("~raw|blob|bfile~",$I->type)?63:0);return$I;}function
__destruct(){oci_free_statement($this->_result);}}}elseif(extension_loaded("pdo_oci")){class
Min_DB
extends
Min_PDO{var$extension="PDO_OCI";function
connect($N,$V,$F){$this->dsn("oci:dbname=//$N;charset=AL32UTF8",$V,$F);return
true;}function
select_db($k){return
true;}}}class
Min_Driver
extends
Min_SQL{function
begin(){return
true;}}function
idf_escape($v){return'"'.str_replace('"','""',$v).'"';}function
table($v){return
idf_escape($v);}function
connect(){global$b;$g=new
Min_DB;$j=$b->credentials();if($g->connect($j[0],$j[1],$j[2]))return$g;return$g->error;}function
get_databases(){return
get_vals("SELECT tablespace_name FROM user_tablespaces");}function
limit($G,$Z,$_,$D=0,$M=" "){return($D?" * FROM (SELECT t.*, rownum AS rnum FROM (SELECT $G$Z) t WHERE rownum <= ".($_+$D).") WHERE rnum > $D":($_!==null?" * FROM (SELECT $G$Z) WHERE rownum <= ".($_+$D):" $G$Z"));}function
limit1($Q,$G,$Z,$M="\n"){return" $G$Z";}function
db_collation($m,$ob){global$g;return$g->result("SELECT value FROM nls_database_parameters WHERE parameter = 'NLS_CHARACTERSET'");}function
engines(){return
array();}function
logged_user(){global$g;return$g->result("SELECT USER FROM DUAL");}function
tables_list(){return
get_key_vals("SELECT table_name, 'table' FROM all_tables WHERE tablespace_name = ".q(DB)."
UNION SELECT view_name, 'view' FROM user_views
ORDER BY 1");}function
count_tables($l){return
array();}function
table_status($C=""){$I=array();$Zg=q($C);foreach(get_rows('SELECT table_name "Name", \'table\' "Engine", avg_row_len * num_rows "Data_length", num_rows "Rows" FROM all_tables WHERE tablespace_name = '.q(DB).($C!=""?" AND table_name = $Zg":"")."
UNION SELECT view_name, 'view', 0, 0 FROM user_views".($C!=""?" WHERE view_name = $Zg":"")."
ORDER BY 1")as$J){if($C!="")return$J;$I[$J["Name"]]=$J;}return$I;}function
is_view($R){return$R["Engine"]=="view";}function
fk_support($R){return
true;}function
fields($Q){$I=array();foreach(get_rows("SELECT * FROM all_tab_columns WHERE table_name = ".q($Q)." ORDER BY column_id")as$J){$T=$J["DATA_TYPE"];$re="$J[DATA_PRECISION],$J[DATA_SCALE]";if($re==",")$re=$J["DATA_LENGTH"];$I[$J["COLUMN_NAME"]]=array("field"=>$J["COLUMN_NAME"],"full_type"=>$T.($re?"($re)":""),"type"=>strtolower($T),"length"=>$re,"default"=>$J["DATA_DEFAULT"],"null"=>($J["NULLABLE"]=="Y"),"privileges"=>array("insert"=>1,"select"=>1,"update"=>1),);}return$I;}function
indexes($Q,$h=null){$I=array();foreach(get_rows("SELECT uic.*, uc.constraint_type
FROM user_ind_columns uic
LEFT JOIN user_constraints uc ON uic.index_name = uc.constraint_name AND uic.table_name = uc.table_name
WHERE uic.table_name = ".q($Q)."
ORDER BY uc.constraint_type, uic.column_position",$h)as$J){$Gd=$J["INDEX_NAME"];$I[$Gd]["type"]=($J["CONSTRAINT_TYPE"]=="P"?"PRIMARY":($J["CONSTRAINT_TYPE"]=="U"?"UNIQUE":"INDEX"));$I[$Gd]["columns"][]=$J["COLUMN_NAME"];$I[$Gd]["lengths"][]=($J["CHAR_LENGTH"]&&$J["CHAR_LENGTH"]!=$J["COLUMN_LENGTH"]?$J["CHAR_LENGTH"]:null);$I[$Gd]["descs"][]=($J["DESCEND"]?'1':null);}return$I;}function
view($C){$K=get_rows('SELECT text "select" FROM user_views WHERE view_name = '.q($C));return
reset($K);}function
collations(){return
array();}function
information_schema($m){return
false;}function
error(){global$g;return
h($g->error);}function
explain($g,$G){$g->query("EXPLAIN PLAN FOR $G");return$g->query("SELECT * FROM plan_table");}function
found_rows($R,$Z){}function
alter_table($Q,$C,$q,$ad,$tb,$sc,$d,$La,$Rf){$c=$dc=array();foreach($q
as$p){$X=$p[1];if($X&&$p[0]!=""&&idf_escape($p[0])!=$X[0])queries("ALTER TABLE ".table($Q)." RENAME COLUMN ".idf_escape($p[0])." TO $X[0]");if($X)$c[]=($Q!=""?($p[0]!=""?"MODIFY (":"ADD ("):"  ").implode($X).($Q!=""?")":"");else$dc[]=idf_escape($p[0]);}if($Q=="")return
queries("CREATE TABLE ".table($C)." (\n".implode(",\n",$c)."\n)");return(!$c||queries("ALTER TABLE ".table($Q)."\n".implode("\n",$c)))&&(!$dc||queries("ALTER TABLE ".table($Q)." DROP (".implode(", ",$dc).")"))&&($Q==$C||queries("ALTER TABLE ".table($Q)." RENAME TO ".table($C)));}function
foreign_keys($Q){$I=array();$G="SELECT c_list.CONSTRAINT_NAME as NAME,
c_src.COLUMN_NAME as SRC_COLUMN,
c_dest.OWNER as DEST_DB,
c_dest.TABLE_NAME as DEST_TABLE,
c_dest.COLUMN_NAME as DEST_COLUMN,
c_list.DELETE_RULE as ON_DELETE
FROM ALL_CONSTRAINTS c_list, ALL_CONS_COLUMNS c_src, ALL_CONS_COLUMNS c_dest
WHERE c_list.CONSTRAINT_NAME = c_src.CONSTRAINT_NAME
AND c_list.R_CONSTRAINT_NAME = c_dest.CONSTRAINT_NAME
AND c_list.CONSTRAINT_TYPE = 'R'
AND c_src.TABLE_NAME = ".q($Q);foreach(get_rows($G)as$J)$I[$J['NAME']]=array("db"=>$J['DEST_DB'],"table"=>$J['DEST_TABLE'],"source"=>array($J['SRC_COLUMN']),"target"=>array($J['DEST_COLUMN']),"on_delete"=>$J['ON_DELETE'],"on_update"=>null,);return$I;}function
truncate_tables($S){return
apply_queries("TRUNCATE TABLE",$S);}function
drop_views($Xi){return
apply_queries("DROP VIEW",$Xi);}function
drop_tables($S){return
apply_queries("DROP TABLE",$S);}function
last_id(){return
0;}function
schemas(){return
get_vals("SELECT DISTINCT owner FROM dba_segments WHERE owner IN (SELECT username FROM dba_users WHERE default_tablespace NOT IN ('SYSTEM','SYSAUX'))");}function
get_schema(){global$g;return$g->result("SELECT sys_context('USERENV', 'SESSION_USER') FROM dual");}function
set_schema($Yg){global$g;return$g->query("ALTER SESSION SET CURRENT_SCHEMA = ".idf_escape($Yg));}function
show_variables(){return
get_key_vals('SELECT name, display_value FROM v$parameter');}function
process_list(){return
get_rows('SELECT sess.process AS "process", sess.username AS "user", sess.schemaname AS "schema", sess.status AS "status", sess.wait_class AS "wait_class", sess.seconds_in_wait AS "seconds_in_wait", sql.sql_text AS "sql_text", sess.machine AS "machine", sess.port AS "port"
FROM v$session sess LEFT OUTER JOIN v$sql sql
ON sql.sql_id = sess.sql_id
WHERE sess.type = \'USER\'
ORDER BY PROCESS
');}function
show_status(){$K=get_rows('SELECT * FROM v$instance');return
reset($K);}function
convert_field($p){}function
unconvert_field($p,$I){return$I;}function
support($Nc){return
preg_match('~^(columns|database|drop_col|indexes|descidx|processlist|scheme|sql|status|table|variables|view|view_trigger)$~',$Nc);}$y="oracle";$U=array();$Fh=array();foreach(array('Numbers'=>array("number"=>38,"binary_float"=>12,"binary_double"=>21),'Date and time'=>array("date"=>10,"timestamp"=>29,"interval year"=>12,"interval day"=>28),'Strings'=>array("char"=>2000,"varchar2"=>4000,"nchar"=>2000,"nvarchar2"=>4000,"clob"=>4294967295,"nclob"=>4294967295),'Binary'=>array("raw"=>2000,"long raw"=>2147483648,"blob"=>4294967295,"bfile"=>4294967296),)as$z=>$X){$U+=$X;$Fh[$z]=array_keys($X);}$Gi=array();$sf=array("=","<",">","<=",">=","!=","LIKE","LIKE %%","IN","IS NULL","NOT LIKE","NOT REGEXP","NOT IN","IS NOT NULL","SQL");$id=array("length","lower","round","upper");$od=array("avg","count","count distinct","max","min","sum");$kc=array(array("date"=>"current_date","timestamp"=>"current_timestamp",),array("number|float|double"=>"+/-","date|timestamp"=>"+ interval/- interval","char|clob"=>"||",));}$cc["mssql"]="MS SQL (beta)";if(isset($_GET["mssql"])){$eg=array("SQLSRV","MSSQL","PDO_DBLIB");define("DRIVER","mssql");if(extension_loaded("sqlsrv")){class
Min_DB{var$extension="sqlsrv",$_link,$_result,$server_info,$affected_rows,$errno,$error;function
_get_error(){$this->error="";foreach(sqlsrv_errors()as$o){$this->errno=$o["code"];$this->error.="$o[message]\n";}$this->error=rtrim($this->error);}function
connect($N,$V,$F){global$b;$m=$b->database();$wb=array("UID"=>$V,"PWD"=>$F,"CharacterSet"=>"UTF-8");if($m!="")$wb["Database"]=$m;$this->_link=@sqlsrv_connect(preg_replace('~:~',',',$N),$wb);if($this->_link){$Nd=sqlsrv_server_info($this->_link);$this->server_info=$Nd['SQLServerVersion'];}else$this->_get_error();return(bool)$this->_link;}function
quote($P){return"'".str_replace("'","''",$P)."'";}function
select_db($k){return$this->query("USE ".idf_escape($k));}function
query($G,$Ai=false){$H=sqlsrv_query($this->_link,$G);$this->error="";if(!$H){$this->_get_error();return
false;}return$this->store_result($H);}function
multi_query($G){$this->_result=sqlsrv_query($this->_link,$G);$this->error="";if(!$this->_result){$this->_get_error();return
false;}return
true;}function
store_result($H=null){if(!$H)$H=$this->_result;if(!$H)return
false;if(sqlsrv_field_metadata($H))return
new
Min_Result($H);$this->affected_rows=sqlsrv_rows_affected($H);return
true;}function
next_result(){return$this->_result?sqlsrv_next_result($this->_result):null;}function
result($G,$p=0){$H=$this->query($G);if(!is_object($H))return
false;$J=$H->fetch_row();return$J[$p];}}class
Min_Result{var$_result,$_offset=0,$_fields,$num_rows;function
__construct($H){$this->_result=$H;}function
_convert($J){foreach((array)$J
as$z=>$X){if(is_a($X,'DateTime'))$J[$z]=$X->format("Y-m-d H:i:s");}return$J;}function
fetch_assoc(){return$this->_convert(sqlsrv_fetch_array($this->_result,SQLSRV_FETCH_ASSOC));}function
fetch_row(){return$this->_convert(sqlsrv_fetch_array($this->_result,SQLSRV_FETCH_NUMERIC));}function
fetch_field(){if(!$this->_fields)$this->_fields=sqlsrv_field_metadata($this->_result);$p=$this->_fields[$this->_offset++];$I=new
stdClass;$I->name=$p["Name"];$I->orgname=$p["Name"];$I->type=($p["Type"]==1?254:0);return$I;}function
seek($D){for($t=0;$t<$D;$t++)sqlsrv_fetch($this->_result);}function
__destruct(){sqlsrv_free_stmt($this->_result);}}}elseif(extension_loaded("mssql")){class
Min_DB{var$extension="MSSQL",$_link,$_result,$server_info,$affected_rows,$error;function
connect($N,$V,$F){$this->_link=@mssql_connect($N,$V,$F);if($this->_link){$H=$this->query("SELECT SERVERPROPERTY('ProductLevel'), SERVERPROPERTY('Edition')");if($H){$J=$H->fetch_row();$this->server_info=$this->result("sp_server_info 2",2)." [$J[0]] $J[1]";}}else$this->error=mssql_get_last_message();return(bool)$this->_link;}function
quote($P){return"'".str_replace("'","''",$P)."'";}function
select_db($k){return
mssql_select_db($k);}function
query($G,$Ai=false){$H=@mssql_query($G,$this->_link);$this->error="";if(!$H){$this->error=mssql_get_last_message();return
false;}if($H===true){$this->affected_rows=mssql_rows_affected($this->_link);return
true;}return
new
Min_Result($H);}function
multi_query($G){return$this->_result=$this->query($G);}function
store_result(){return$this->_result;}function
next_result(){return
mssql_next_result($this->_result->_result);}function
result($G,$p=0){$H=$this->query($G);if(!is_object($H))return
false;return
mssql_result($H->_result,0,$p);}}class
Min_Result{var$_result,$_offset=0,$_fields,$num_rows;function
__construct($H){$this->_result=$H;$this->num_rows=mssql_num_rows($H);}function
fetch_assoc(){return
mssql_fetch_assoc($this->_result);}function
fetch_row(){return
mssql_fetch_row($this->_result);}function
num_rows(){return
mssql_num_rows($this->_result);}function
fetch_field(){$I=mssql_fetch_field($this->_result);$I->orgtable=$I->table;$I->orgname=$I->name;return$I;}function
seek($D){mssql_data_seek($this->_result,$D);}function
__destruct(){mssql_free_result($this->_result);}}}elseif(extension_loaded("pdo_dblib")){class
Min_DB
extends
Min_PDO{var$extension="PDO_DBLIB";function
connect($N,$V,$F){$this->dsn("dblib:charset=utf8;host=".str_replace(":",";unix_socket=",preg_replace('~:(\d)~',';port=\1',$N)),$V,$F);return
true;}function
select_db($k){return$this->query("USE ".idf_escape($k));}}}class
Min_Driver
extends
Min_SQL{function
insertUpdate($Q,$K,$hg){foreach($K
as$O){$Hi=array();$Z=array();foreach($O
as$z=>$X){$Hi[]="$z = $X";if(isset($hg[idf_unescape($z)]))$Z[]="$z = $X";}if(!queries("MERGE ".table($Q)." USING (VALUES(".implode(", ",$O).")) AS source (c".implode(", c",range(1,count($O))).") ON ".implode(" AND ",$Z)." WHEN MATCHED THEN UPDATE SET ".implode(", ",$Hi)." WHEN NOT MATCHED THEN INSERT (".implode(", ",array_keys($O)).") VALUES (".implode(", ",$O).");"))return
false;}return
true;}function
begin(){return
queries("BEGIN TRANSACTION");}}function
idf_escape($v){return"[".str_replace("]","]]",$v)."]";}function
table($v){return($_GET["ns"]!=""?idf_escape($_GET["ns"]).".":"").idf_escape($v);}function
connect(){global$b;$g=new
Min_DB;$j=$b->credentials();if($g->connect($j[0],$j[1],$j[2]))return$g;return$g->error;}function
get_databases(){return
get_vals("SELECT name FROM sys.databases WHERE name NOT IN ('master', 'tempdb', 'model', 'msdb')");}function
limit($G,$Z,$_,$D=0,$M=" "){return($_!==null?" TOP (".($_+$D).")":"")." $G$Z";}function
limit1($Q,$G,$Z,$M="\n"){return
limit($G,$Z,1,0,$M);}function
db_collation($m,$ob){global$g;return$g->result("SELECT collation_name FROM sys.databases WHERE name = ".q($m));}function
engines(){return
array();}function
logged_user(){global$g;return$g->result("SELECT SUSER_NAME()");}function
tables_list(){return
get_key_vals("SELECT name, type_desc FROM sys.all_objects WHERE schema_id = SCHEMA_ID(".q(get_schema()).") AND type IN ('S', 'U', 'V') ORDER BY name");}function
count_tables($l){global$g;$I=array();foreach($l
as$m){$g->select_db($m);$I[$m]=$g->result("SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES");}return$I;}function
table_status($C=""){$I=array();foreach(get_rows("SELECT name AS Name, type_desc AS Engine FROM sys.all_objects WHERE schema_id = SCHEMA_ID(".q(get_schema()).") AND type IN ('S', 'U', 'V') ".($C!=""?"AND name = ".q($C):"ORDER BY name"))as$J){if($C!="")return$J;$I[$J["Name"]]=$J;}return$I;}function
is_view($R){return$R["Engine"]=="VIEW";}function
fk_support($R){return
true;}function
fields($Q){$I=array();foreach(get_rows("SELECT c.max_length, c.precision, c.scale, c.name, c.is_nullable, c.is_identity, c.collation_name, t.name type, CAST(d.definition as text) [default]
FROM sys.all_columns c
JOIN sys.all_objects o ON c.object_id = o.object_id
JOIN sys.types t ON c.user_type_id = t.user_type_id
LEFT JOIN sys.default_constraints d ON c.default_object_id = d.parent_column_id
WHERE o.schema_id = SCHEMA_ID(".q(get_schema()).") AND o.type IN ('S', 'U', 'V') AND o.name = ".q($Q))as$J){$T=$J["type"];$re=(preg_match("~char|binary~",$T)?$J["max_length"]:($T=="decimal"?"$J[precision],$J[scale]":""));$I[$J["name"]]=array("field"=>$J["name"],"full_type"=>$T.($re?"($re)":""),"type"=>$T,"length"=>$re,"default"=>$J["default"],"null"=>$J["is_nullable"],"auto_increment"=>$J["is_identity"],"collation"=>$J["collation_name"],"privileges"=>array("insert"=>1,"select"=>1,"update"=>1),"primary"=>$J["is_identity"],);}return$I;}function
indexes($Q,$h=null){$I=array();foreach(get_rows("SELECT i.name, key_ordinal, is_unique, is_primary_key, c.name AS column_name, is_descending_key
FROM sys.indexes i
INNER JOIN sys.index_columns ic ON i.object_id = ic.object_id AND i.index_id = ic.index_id
INNER JOIN sys.columns c ON ic.object_id = c.object_id AND ic.column_id = c.column_id
WHERE OBJECT_NAME(i.object_id) = ".q($Q),$h)as$J){$C=$J["name"];$I[$C]["type"]=($J["is_primary_key"]?"PRIMARY":($J["is_unique"]?"UNIQUE":"INDEX"));$I[$C]["lengths"]=array();$I[$C]["columns"][$J["key_ordinal"]]=$J["column_name"];$I[$C]["descs"][$J["key_ordinal"]]=($J["is_descending_key"]?'1':null);}return$I;}function
view($C){global$g;return
array("select"=>preg_replace('~^(?:[^[]|\[[^]]*])*\s+AS\s+~isU','',$g->result("SELECT VIEW_DEFINITION FROM INFORMATION_SCHEMA.VIEWS WHERE TABLE_SCHEMA = SCHEMA_NAME() AND TABLE_NAME = ".q($C))));}function
collations(){$I=array();foreach(get_vals("SELECT name FROM fn_helpcollations()")as$d)$I[preg_replace('~_.*~','',$d)][]=$d;return$I;}function
information_schema($m){return
false;}function
error(){global$g;return
nl_br(h(preg_replace('~^(\[[^]]*])+~m','',$g->error)));}function
create_database($m,$d){return
queries("CREATE DATABASE ".idf_escape($m).(preg_match('~^[a-z0-9_]+$~i',$d)?" COLLATE $d":""));}function
drop_databases($l){return
queries("DROP DATABASE ".implode(", ",array_map('idf_escape',$l)));}function
rename_database($C,$d){if(preg_match('~^[a-z0-9_]+$~i',$d))queries("ALTER DATABASE ".idf_escape(DB)." COLLATE $d");queries("ALTER DATABASE ".idf_escape(DB)." MODIFY NAME = ".idf_escape($C));return
true;}function
auto_increment(){return" IDENTITY".($_POST["Auto_increment"]!=""?"(".number($_POST["Auto_increment"]).",1)":"")." PRIMARY KEY";}function
alter_table($Q,$C,$q,$ad,$tb,$sc,$d,$La,$Rf){$c=array();foreach($q
as$p){$e=idf_escape($p[0]);$X=$p[1];if(!$X)$c["DROP"][]=" COLUMN $e";else{$X[1]=preg_replace("~( COLLATE )'(\\w+)'~",'\1\2',$X[1]);if($p[0]=="")$c["ADD"][]="\n  ".implode("",$X).($Q==""?substr($ad[$X[0]],16+strlen($X[0])):"");else{unset($X[6]);if($e!=$X[0])queries("EXEC sp_rename ".q(table($Q).".$e").", ".q(idf_unescape($X[0])).", 'COLUMN'");$c["ALTER COLUMN ".implode("",$X)][]="";}}}if($Q=="")return
queries("CREATE TABLE ".table($C)." (".implode(",",(array)$c["ADD"])."\n)");if($Q!=$C)queries("EXEC sp_rename ".q(table($Q)).", ".q($C));if($ad)$c[""]=$ad;foreach($c
as$z=>$X){if(!queries("ALTER TABLE ".idf_escape($C)." $z".implode(",",$X)))return
false;}return
true;}function
alter_indexes($Q,$c){$w=array();$dc=array();foreach($c
as$X){if($X[2]=="DROP"){if($X[0]=="PRIMARY")$dc[]=idf_escape($X[1]);else$w[]=idf_escape($X[1])." ON ".table($Q);}elseif(!queries(($X[0]!="PRIMARY"?"CREATE $X[0] ".($X[0]!="INDEX"?"INDEX ":"").idf_escape($X[1]!=""?$X[1]:uniqid($Q."_"))." ON ".table($Q):"ALTER TABLE ".table($Q)." ADD PRIMARY KEY")." (".implode(", ",$X[2]).")"))return
false;}return(!$w||queries("DROP INDEX ".implode(", ",$w)))&&(!$dc||queries("ALTER TABLE ".table($Q)." DROP ".implode(", ",$dc)));}function
last_id(){global$g;return$g->result("SELECT SCOPE_IDENTITY()");}function
explain($g,$G){$g->query("SET SHOWPLAN_ALL ON");$I=$g->query($G);$g->query("SET SHOWPLAN_ALL OFF");return$I;}function
found_rows($R,$Z){}function
foreign_keys($Q){$I=array();foreach(get_rows("EXEC sp_fkeys @fktable_name = ".q($Q))as$J){$r=&$I[$J["FK_NAME"]];$r["table"]=$J["PKTABLE_NAME"];$r["source"][]=$J["FKCOLUMN_NAME"];$r["target"][]=$J["PKCOLUMN_NAME"];}return$I;}function
truncate_tables($S){return
apply_queries("TRUNCATE TABLE",$S);}function
drop_views($Xi){return
queries("DROP VIEW ".implode(", ",array_map('table',$Xi)));}function
drop_tables($S){return
queries("DROP TABLE ".implode(", ",array_map('table',$S)));}function
move_tables($S,$Xi,$Vh){return
apply_queries("ALTER SCHEMA ".idf_escape($Vh)." TRANSFER",array_merge($S,$Xi));}function
trigger($C){if($C=="")return
array();$K=get_rows("SELECT s.name [Trigger],
CASE WHEN OBJECTPROPERTY(s.id, 'ExecIsInsertTrigger') = 1 THEN 'INSERT' WHEN OBJECTPROPERTY(s.id, 'ExecIsUpdateTrigger') = 1 THEN 'UPDATE' WHEN OBJECTPROPERTY(s.id, 'ExecIsDeleteTrigger') = 1 THEN 'DELETE' END [Event],
CASE WHEN OBJECTPROPERTY(s.id, 'ExecIsInsteadOfTrigger') = 1 THEN 'INSTEAD OF' ELSE 'AFTER' END [Timing],
c.text
FROM sysobjects s
JOIN syscomments c ON s.id = c.id
WHERE s.xtype = 'TR' AND s.name = ".q($C));$I=reset($K);if($I)$I["Statement"]=preg_replace('~^.+\s+AS\s+~isU','',$I["text"]);return$I;}function
triggers($Q){$I=array();foreach(get_rows("SELECT sys1.name,
CASE WHEN OBJECTPROPERTY(sys1.id, 'ExecIsInsertTrigger') = 1 THEN 'INSERT' WHEN OBJECTPROPERTY(sys1.id, 'ExecIsUpdateTrigger') = 1 THEN 'UPDATE' WHEN OBJECTPROPERTY(sys1.id, 'ExecIsDeleteTrigger') = 1 THEN 'DELETE' END [Event],
CASE WHEN OBJECTPROPERTY(sys1.id, 'ExecIsInsteadOfTrigger') = 1 THEN 'INSTEAD OF' ELSE 'AFTER' END [Timing]
FROM sysobjects sys1
JOIN sysobjects sys2 ON sys1.parent_obj = sys2.id
WHERE sys1.xtype = 'TR' AND sys2.name = ".q($Q))as$J)$I[$J["name"]]=array($J["Timing"],$J["Event"]);return$I;}function
trigger_options(){return
array("Timing"=>array("AFTER","INSTEAD OF"),"Event"=>array("INSERT","UPDATE","DELETE"),"Type"=>array("AS"),);}function
schemas(){return
get_vals("SELECT name FROM sys.schemas");}function
get_schema(){global$g;if($_GET["ns"]!="")return$_GET["ns"];return$g->result("SELECT SCHEMA_NAME()");}function
set_schema($Xg){return
true;}function
use_sql($k){return"USE ".idf_escape($k);}function
show_variables(){return
array();}function
show_status(){return
array();}function
convert_field($p){}function
unconvert_field($p,$I){return$I;}function
support($Nc){return
preg_match('~^(columns|database|drop_col|indexes|descidx|scheme|sql|table|trigger|view|view_trigger)$~',$Nc);}$y="mssql";$U=array();$Fh=array();foreach(array('Numbers'=>array("tinyint"=>3,"smallint"=>5,"int"=>10,"bigint"=>20,"bit"=>1,"decimal"=>0,"real"=>12,"float"=>53,"smallmoney"=>10,"money"=>20),'Date and time'=>array("date"=>10,"smalldatetime"=>19,"datetime"=>19,"datetime2"=>19,"time"=>8,"datetimeoffset"=>10),'Strings'=>array("char"=>8000,"varchar"=>8000,"text"=>2147483647,"nchar"=>4000,"nvarchar"=>4000,"ntext"=>1073741823),'Binary'=>array("binary"=>8000,"varbinary"=>8000,"image"=>2147483647),)as$z=>$X){$U+=$X;$Fh[$z]=array_keys($X);}$Gi=array();$sf=array("=","<",">","<=",">=","!=","LIKE","LIKE %%","IN","IS NULL","NOT LIKE","NOT IN","IS NOT NULL");$id=array("len","lower","round","upper");$od=array("avg","count","count distinct","max","min","sum");$kc=array(array("date|time"=>"getdate",),array("int|decimal|real|float|money|datetime"=>"+/-","char|text"=>"+",));}$cc['firebird']='Firebird (alpha)';if(isset($_GET["firebird"])){$eg=array("interbase");define("DRIVER","firebird");if(extension_loaded("interbase")){class
Min_DB{var$extension="Firebird",$server_info,$affected_rows,$errno,$error,$_link,$_result;function
connect($N,$V,$F){$this->_link=ibase_connect($N,$V,$F);if($this->_link){$Ki=explode(':',$N);$this->service_link=ibase_service_attach($Ki[0],$V,$F);$this->server_info=ibase_server_info($this->service_link,IBASE_SVC_SERVER_VERSION);}else{$this->errno=ibase_errcode();$this->error=ibase_errmsg();}return(bool)$this->_link;}function
quote($P){return"'".str_replace("'","''",$P)."'";}function
select_db($k){return($k=="domain");}function
query($G,$Ai=false){$H=ibase_query($G,$this->_link);if(!$H){$this->errno=ibase_errcode();$this->error=ibase_errmsg();return
false;}$this->error="";if($H===true){$this->affected_rows=ibase_affected_rows($this->_link);return
true;}return
new
Min_Result($H);}function
multi_query($G){return$this->_result=$this->query($G);}function
store_result(){return$this->_result;}function
next_result(){return
false;}function
result($G,$p=0){$H=$this->query($G);if(!$H||!$H->num_rows)return
false;$J=$H->fetch_row();return$J[$p];}}class
Min_Result{var$num_rows,$_result,$_offset=0;function
__construct($H){$this->_result=$H;}function
fetch_assoc(){return
ibase_fetch_assoc($this->_result);}function
fetch_row(){return
ibase_fetch_row($this->_result);}function
fetch_field(){$p=ibase_field_info($this->_result,$this->_offset++);return(object)array('name'=>$p['name'],'orgname'=>$p['name'],'type'=>$p['type'],'charsetnr'=>$p['length'],);}function
__destruct(){ibase_free_result($this->_result);}}}class
Min_Driver
extends
Min_SQL{}function
idf_escape($v){return'"'.str_replace('"','""',$v).'"';}function
table($v){return
idf_escape($v);}function
connect(){global$b;$g=new
Min_DB;$j=$b->credentials();if($g->connect($j[0],$j[1],$j[2]))return$g;return$g->error;}function
get_databases($Yc){return
array("domain");}function
limit($G,$Z,$_,$D=0,$M=" "){$I='';$I.=($_!==null?$M."FIRST $_".($D?" SKIP $D":""):"");$I.=" $G$Z";return$I;}function
limit1($Q,$G,$Z,$M="\n"){return
limit($G,$Z,1,0,$M);}function
db_collation($m,$ob){}function
engines(){return
array();}function
logged_user(){global$b;$j=$b->credentials();return$j[1];}function
tables_list(){global$g;$G='SELECT RDB$RELATION_NAME FROM rdb$relations WHERE rdb$system_flag = 0';$H=ibase_query($g->_link,$G);$I=array();while($J=ibase_fetch_assoc($H))$I[$J['RDB$RELATION_NAME']]='table';ksort($I);return$I;}function
count_tables($l){return
array();}function
table_status($C="",$Mc=false){global$g;$I=array();$Jb=tables_list();foreach($Jb
as$w=>$X){$w=trim($w);$I[$w]=array('Name'=>$w,'Engine'=>'standard',);if($C==$w)return$I[$w];}return$I;}function
is_view($R){return
false;}function
fk_support($R){return
preg_match('~InnoDB|IBMDB2I~i',$R["Engine"]);}function
fields($Q){global$g;$I=array();$G='SELECT r.RDB$FIELD_NAME AS field_name,
r.RDB$DESCRIPTION AS field_description,
r.RDB$DEFAULT_VALUE AS field_default_value,
r.RDB$NULL_FLAG AS field_not_null_constraint,
f.RDB$FIELD_LENGTH AS field_length,
f.RDB$FIELD_PRECISION AS field_precision,
f.RDB$FIELD_SCALE AS field_scale,
CASE f.RDB$FIELD_TYPE
WHEN 261 THEN \'BLOB\'
WHEN 14 THEN \'CHAR\'
WHEN 40 THEN \'CSTRING\'
WHEN 11 THEN \'D_FLOAT\'
WHEN 27 THEN \'DOUBLE\'
WHEN 10 THEN \'FLOAT\'
WHEN 16 THEN \'INT64\'
WHEN 8 THEN \'INTEGER\'
WHEN 9 THEN \'QUAD\'
WHEN 7 THEN \'SMALLINT\'
WHEN 12 THEN \'DATE\'
WHEN 13 THEN \'TIME\'
WHEN 35 THEN \'TIMESTAMP\'
WHEN 37 THEN \'VARCHAR\'
ELSE \'UNKNOWN\'
END AS field_type,
f.RDB$FIELD_SUB_TYPE AS field_subtype,
coll.RDB$COLLATION_NAME AS field_collation,
cset.RDB$CHARACTER_SET_NAME AS field_charset
FROM RDB$RELATION_FIELDS r
LEFT JOIN RDB$FIELDS f ON r.RDB$FIELD_SOURCE = f.RDB$FIELD_NAME
LEFT JOIN RDB$COLLATIONS coll ON f.RDB$COLLATION_ID = coll.RDB$COLLATION_ID
LEFT JOIN RDB$CHARACTER_SETS cset ON f.RDB$CHARACTER_SET_ID = cset.RDB$CHARACTER_SET_ID
WHERE r.RDB$RELATION_NAME = '.q($Q).'
ORDER BY r.RDB$FIELD_POSITION';$H=ibase_query($g->_link,$G);while($J=ibase_fetch_assoc($H))$I[trim($J['FIELD_NAME'])]=array("field"=>trim($J["FIELD_NAME"]),"full_type"=>trim($J["FIELD_TYPE"]),"type"=>trim($J["FIELD_SUB_TYPE"]),"default"=>trim($J['FIELD_DEFAULT_VALUE']),"null"=>(trim($J["FIELD_NOT_NULL_CONSTRAINT"])=="YES"),"auto_increment"=>'0',"collation"=>trim($J["FIELD_COLLATION"]),"privileges"=>array("insert"=>1,"select"=>1,"update"=>1),"comment"=>trim($J["FIELD_DESCRIPTION"]),);return$I;}function
indexes($Q,$h=null){$I=array();return$I;}function
foreign_keys($Q){return
array();}function
collations(){return
array();}function
information_schema($m){return
false;}function
error(){global$g;return
h($g->error);}function
types(){return
array();}function
schemas(){return
array();}function
get_schema(){return"";}function
set_schema($Xg){return
true;}function
support($Nc){return
preg_match("~^(columns|sql|status|table)$~",$Nc);}$y="firebird";$sf=array("=");$id=array();$od=array();$kc=array();}$cc["simpledb"]="SimpleDB";if(isset($_GET["simpledb"])){$eg=array("SimpleXML + allow_url_fopen");define("DRIVER","simpledb");if(class_exists('SimpleXMLElement')&&ini_bool('allow_url_fopen')){class
Min_DB{var$extension="SimpleXML",$server_info='2009-04-15',$error,$timeout,$next,$affected_rows,$_result;function
select_db($k){return($k=="domain");}function
query($G,$Ai=false){$Lf=array('SelectExpression'=>$G,'ConsistentRead'=>'true');if($this->next)$Lf['NextToken']=$this->next;$H=sdb_request_all('Select','Item',$Lf,$this->timeout);$this->timeout=0;if($H===false)return$H;if(preg_match('~^\s*SELECT\s+COUNT\(~i',$G)){$Jh=0;foreach($H
as$Zd)$Jh+=$Zd->Attribute->Value;$H=array((object)array('Attribute'=>array((object)array('Name'=>'Count','Value'=>$Jh,))));}return
new
Min_Result($H);}function
multi_query($G){return$this->_result=$this->query($G);}function
store_result(){return$this->_result;}function
next_result(){return
false;}function
quote($P){return"'".str_replace("'","''",$P)."'";}}class
Min_Result{var$num_rows,$_rows=array(),$_offset=0;function
__construct($H){foreach($H
as$Zd){$J=array();if($Zd->Name!='')$J['itemName()']=(string)$Zd->Name;foreach($Zd->Attribute
as$Ia){$C=$this->_processValue($Ia->Name);$Y=$this->_processValue($Ia->Value);if(isset($J[$C])){$J[$C]=(array)$J[$C];$J[$C][]=$Y;}else$J[$C]=$Y;}$this->_rows[]=$J;foreach($J
as$z=>$X){if(!isset($this->_rows[0][$z]))$this->_rows[0][$z]=null;}}$this->num_rows=count($this->_rows);}function
_processValue($nc){return(is_object($nc)&&$nc['encoding']=='base64'?base64_decode($nc):(string)$nc);}function
fetch_assoc(){$J=current($this->_rows);if(!$J)return$J;$I=array();foreach($this->_rows[0]as$z=>$X)$I[$z]=$J[$z];next($this->_rows);return$I;}function
fetch_row(){$I=$this->fetch_assoc();if(!$I)return$I;return
array_values($I);}function
fetch_field(){$fe=array_keys($this->_rows[0]);return(object)array('name'=>$fe[$this->_offset++]);}}}class
Min_Driver
extends
Min_SQL{public$hg="itemName()";function
_chunkRequest($Cd,$va,$Lf,$Cc=array()){global$g;foreach(array_chunk($Cd,25)as$hb){$Mf=$Lf;foreach($hb
as$t=>$u){$Mf["Item.$t.ItemName"]=$u;foreach($Cc
as$z=>$X)$Mf["Item.$t.$z"]=$X;}if(!sdb_request($va,$Mf))return
false;}$g->affected_rows=count($Cd);return
true;}function
_extractIds($Q,$tg,$_){$I=array();if(preg_match_all("~itemName\(\) = (('[^']*+')+)~",$tg,$Be))$I=array_map('idf_unescape',$Be[1]);else{foreach(sdb_request_all('Select','Item',array('SelectExpression'=>'SELECT itemName() FROM '.table($Q).$tg.($_?" LIMIT 1":"")))as$Zd)$I[]=$Zd->Name;}return$I;}function
select($Q,$L,$Z,$ld,$xf=array(),$_=1,$E=0,$jg=false){global$g;$g->next=$_GET["next"];$I=parent::select($Q,$L,$Z,$ld,$xf,$_,$E,$jg);$g->next=0;return$I;}function
delete($Q,$tg,$_=0){return$this->_chunkRequest($this->_extractIds($Q,$tg,$_),'BatchDeleteAttributes',array('DomainName'=>$Q));}function
update($Q,$O,$tg,$_=0,$M="\n"){$Sb=array();$Rd=array();$t=0;$Cd=$this->_extractIds($Q,$tg,$_);$u=idf_unescape($O["`itemName()`"]);unset($O["`itemName()`"]);foreach($O
as$z=>$X){$z=idf_unescape($z);if($X=="NULL"||($u!=""&&array($u)!=$Cd))$Sb["Attribute.".count($Sb).".Name"]=$z;if($X!="NULL"){foreach((array)$X
as$be=>$W){$Rd["Attribute.$t.Name"]=$z;$Rd["Attribute.$t.Value"]=(is_array($X)?$W:idf_unescape($W));if(!$be)$Rd["Attribute.$t.Replace"]="true";$t++;}}}$Lf=array('DomainName'=>$Q);return(!$Rd||$this->_chunkRequest(($u!=""?array($u):$Cd),'BatchPutAttributes',$Lf,$Rd))&&(!$Sb||$this->_chunkRequest($Cd,'BatchDeleteAttributes',$Lf,$Sb));}function
insert($Q,$O){$Lf=array("DomainName"=>$Q);$t=0;foreach($O
as$C=>$Y){if($Y!="NULL"){$C=idf_unescape($C);if($C=="itemName()")$Lf["ItemName"]=idf_unescape($Y);else{foreach((array)$Y
as$X){$Lf["Attribute.$t.Name"]=$C;$Lf["Attribute.$t.Value"]=(is_array($Y)?$X:idf_unescape($Y));$t++;}}}}return
sdb_request('PutAttributes',$Lf);}function
insertUpdate($Q,$K,$hg){foreach($K
as$O){if(!$this->update($Q,$O,"WHERE `itemName()` = ".q($O["`itemName()`"])))return
false;}return
true;}function
begin(){return
false;}function
commit(){return
false;}function
rollback(){return
false;}function
slowQuery($G,$di){$this->_conn->timeout=$di;return$G;}}function
connect(){global$b;list(,,$F)=$b->credentials();if($F!="")return'Database does not support password.';return
new
Min_DB;}function
support($Nc){return
preg_match('~sql~',$Nc);}function
logged_user(){global$b;$j=$b->credentials();return$j[1];}function
get_databases(){return
array("domain");}function
collations(){return
array();}function
db_collation($m,$ob){}function
tables_list(){global$g;$I=array();foreach(sdb_request_all('ListDomains','DomainName')as$Q)$I[(string)$Q]='table';if($g->error&&defined("PAGE_HEADER"))echo"<p class='error'>".error()."\n";return$I;}function
table_status($C="",$Mc=false){$I=array();foreach(($C!=""?array($C=>true):tables_list())as$Q=>$T){$J=array("Name"=>$Q,"Auto_increment"=>"");if(!$Mc){$Oe=sdb_request('DomainMetadata',array('DomainName'=>$Q));if($Oe){foreach(array("Rows"=>"ItemCount","Data_length"=>"ItemNamesSizeBytes","Index_length"=>"AttributeValuesSizeBytes","Data_free"=>"AttributeNamesSizeBytes",)as$z=>$X)$J[$z]=(string)$Oe->$X;}}if($C!="")return$J;$I[$Q]=$J;}return$I;}function
explain($g,$G){}function
error(){global$g;return
h($g->error);}function
information_schema(){}function
is_view($R){}function
indexes($Q,$h=null){return
array(array("type"=>"PRIMARY","columns"=>array("itemName()")),);}function
fields($Q){return
fields_from_edit();}function
foreign_keys($Q){return
array();}function
table($v){return
idf_escape($v);}function
idf_escape($v){return"`".str_replace("`","``",$v)."`";}function
limit($G,$Z,$_,$D=0,$M=" "){return" $G$Z".($_!==null?$M."LIMIT $_":"");}function
unconvert_field($p,$I){return$I;}function
fk_support($R){}function
engines(){return
array();}function
alter_table($Q,$C,$q,$ad,$tb,$sc,$d,$La,$Rf){return($Q==""&&sdb_request('CreateDomain',array('DomainName'=>$C)));}function
drop_tables($S){foreach($S
as$Q){if(!sdb_request('DeleteDomain',array('DomainName'=>$Q)))return
false;}return
true;}function
count_tables($l){foreach($l
as$m)return
array($m=>count(tables_list()));}function
found_rows($R,$Z){return($Z?null:$R["Rows"]);}function
last_id(){}function
hmac($Ba,$Jb,$z,$xg=false){$Ua=64;if(strlen($z)>$Ua)$z=pack("H*",$Ba($z));$z=str_pad($z,$Ua,"\0");$ce=$z^str_repeat("\x36",$Ua);$de=$z^str_repeat("\x5C",$Ua);$I=$Ba($de.pack("H*",$Ba($ce.$Jb)));if($xg)$I=pack("H*",$I);return$I;}function
sdb_request($va,$Lf=array()){global$b,$g;list($zd,$Lf['AWSAccessKeyId'],$ah)=$b->credentials();$Lf['Action']=$va;$Lf['Timestamp']=gmdate('Y-m-d\TH:i:s+00:00');$Lf['Version']='2009-04-15';$Lf['SignatureVersion']=2;$Lf['SignatureMethod']='HmacSHA1';ksort($Lf);$G='';foreach($Lf
as$z=>$X)$G.='&'.rawurlencode($z).'='.rawurlencode($X);$G=str_replace('%7E','~',substr($G,1));$G.="&Signature=".urlencode(base64_encode(hmac('sha1',"POST\n".preg_replace('~^https?://~','',$zd)."\n/\n$G",$ah,true)));@ini_set('track_errors',1);$Rc=@file_get_contents((preg_match('~^https?://~',$zd)?$zd:"http://$zd"),false,stream_context_create(array('http'=>array('method'=>'POST','content'=>$G,'ignore_errors'=>1,))));if(!$Rc){$g->error=$php_errormsg;return
false;}libxml_use_internal_errors(true);$kj=simplexml_load_string($Rc);if(!$kj){$o=libxml_get_last_error();$g->error=$o->message;return
false;}if($kj->Errors){$o=$kj->Errors->Error;$g->error="$o->Message ($o->Code)";return
false;}$g->error='';$Uh=$va."Result";return($kj->$Uh?$kj->$Uh:true);}function
sdb_request_all($va,$Uh,$Lf=array(),$di=0){$I=array();$Ah=($di?microtime(true):0);$_=(preg_match('~LIMIT\s+(\d+)\s*$~i',$Lf['SelectExpression'],$B)?$B[1]:0);do{$kj=sdb_request($va,$Lf);if(!$kj)break;foreach($kj->$Uh
as$nc)$I[]=$nc;if($_&&count($I)>=$_){$_GET["next"]=$kj->NextToken;break;}if($di&&microtime(true)-$Ah>$di)return
false;$Lf['NextToken']=$kj->NextToken;if($_)$Lf['SelectExpression']=preg_replace('~\d+\s*$~',$_-count($I),$Lf['SelectExpression']);}while($kj->NextToken);return$I;}$y="simpledb";$sf=array("=","<",">","<=",">=","!=","LIKE","LIKE %%","IN","IS NULL","NOT LIKE","IS NOT NULL");$id=array();$od=array("count");$kc=array(array("json"));}$cc["mongo"]="MongoDB";if(isset($_GET["mongo"])){$eg=array("mongo","mongodb");define("DRIVER","mongo");if(class_exists('MongoDB')){class
Min_DB{var$extension="Mongo",$server_info=MongoClient::VERSION,$error,$last_id,$_link,$_db;function
connect($Ii,$vf){return@new
MongoClient($Ii,$vf);}function
query($G){return
false;}function
select_db($k){try{$this->_db=$this->_link->selectDB($k);return
true;}catch(Exception$zc){$this->error=$zc->getMessage();return
false;}}function
quote($P){return$P;}}class
Min_Result{var$num_rows,$_rows=array(),$_offset=0,$_charset=array();function
__construct($H){foreach($H
as$Zd){$J=array();foreach($Zd
as$z=>$X){if(is_a($X,'MongoBinData'))$this->_charset[$z]=63;$J[$z]=(is_a($X,'MongoId')?'ObjectId("'.strval($X).'")':(is_a($X,'MongoDate')?gmdate("Y-m-d H:i:s",$X->sec)." GMT":(is_a($X,'MongoBinData')?$X->bin:(is_a($X,'MongoRegex')?strval($X):(is_object($X)?get_class($X):$X)))));}$this->_rows[]=$J;foreach($J
as$z=>$X){if(!isset($this->_rows[0][$z]))$this->_rows[0][$z]=null;}}$this->num_rows=count($this->_rows);}function
fetch_assoc(){$J=current($this->_rows);if(!$J)return$J;$I=array();foreach($this->_rows[0]as$z=>$X)$I[$z]=$J[$z];next($this->_rows);return$I;}function
fetch_row(){$I=$this->fetch_assoc();if(!$I)return$I;return
array_values($I);}function
fetch_field(){$fe=array_keys($this->_rows[0]);$C=$fe[$this->_offset++];return(object)array('name'=>$C,'charsetnr'=>$this->_charset[$C],);}}class
Min_Driver
extends
Min_SQL{public$hg="_id";function
select($Q,$L,$Z,$ld,$xf=array(),$_=1,$E=0,$jg=false){$L=($L==array("*")?array():array_fill_keys($L,true));$sh=array();foreach($xf
as$X){$X=preg_replace('~ DESC$~','',$X,1,$Cb);$sh[$X]=($Cb?-1:1);}return
new
Min_Result($this->_conn->_db->selectCollection($Q)->find(array(),$L)->sort($sh)->limit($_!=""?+$_:0)->skip($E*$_));}function
insert($Q,$O){try{$I=$this->_conn->_db->selectCollection($Q)->insert($O);$this->_conn->errno=$I['code'];$this->_conn->error=$I['err'];$this->_conn->last_id=$O['_id'];return!$I['err'];}catch(Exception$zc){$this->_conn->error=$zc->getMessage();return
false;}}}function
get_databases($Yc){global$g;$I=array();$Ob=$g->_link->listDBs();foreach($Ob['databases']as$m)$I[]=$m['name'];return$I;}function
count_tables($l){global$g;$I=array();foreach($l
as$m)$I[$m]=count($g->_link->selectDB($m)->getCollectionNames(true));return$I;}function
tables_list(){global$g;return
array_fill_keys($g->_db->getCollectionNames(true),'table');}function
drop_databases($l){global$g;foreach($l
as$m){$Jg=$g->_link->selectDB($m)->drop();if(!$Jg['ok'])return
false;}return
true;}function
indexes($Q,$h=null){global$g;$I=array();foreach($g->_db->selectCollection($Q)->getIndexInfo()as$w){$Vb=array();foreach($w["key"]as$e=>$T)$Vb[]=($T==-1?'1':null);$I[$w["name"]]=array("type"=>($w["name"]=="_id_"?"PRIMARY":($w["unique"]?"UNIQUE":"INDEX")),"columns"=>array_keys($w["key"]),"lengths"=>array(),"descs"=>$Vb,);}return$I;}function
fields($Q){return
fields_from_edit();}function
found_rows($R,$Z){global$g;return$g->_db->selectCollection($_GET["select"])->count($Z);}$sf=array("=");}elseif(class_exists('MongoDB\Driver\Manager')){class
Min_DB{var$extension="MongoDB",$server_info=MONGODB_VERSION,$error,$last_id;var$_link;var$_db,$_db_name;function
connect($Ii,$vf){$jb='MongoDB\Driver\Manager';return
new$jb($Ii,$vf);}function
query($G){return
false;}function
select_db($k){$this->_db_name=$k;return
true;}function
quote($P){return$P;}}class
Min_Result{var$num_rows,$_rows=array(),$_offset=0,$_charset=array();function
__construct($H){foreach($H
as$Zd){$J=array();foreach($Zd
as$z=>$X){if(is_a($X,'MongoDB\BSON\Binary'))$this->_charset[$z]=63;$J[$z]=(is_a($X,'MongoDB\BSON\ObjectID')?'MongoDB\BSON\ObjectID("'.strval($X).'")':(is_a($X,'MongoDB\BSON\UTCDatetime')?$X->toDateTime()->format('Y-m-d H:i:s'):(is_a($X,'MongoDB\BSON\Binary')?$X->bin:(is_a($X,'MongoDB\BSON\Regex')?strval($X):(is_object($X)?json_encode($X,256):$X)))));}$this->_rows[]=$J;foreach($J
as$z=>$X){if(!isset($this->_rows[0][$z]))$this->_rows[0][$z]=null;}}$this->num_rows=$H->count;}function
fetch_assoc(){$J=current($this->_rows);if(!$J)return$J;$I=array();foreach($this->_rows[0]as$z=>$X)$I[$z]=$J[$z];next($this->_rows);return$I;}function
fetch_row(){$I=$this->fetch_assoc();if(!$I)return$I;return
array_values($I);}function
fetch_field(){$fe=array_keys($this->_rows[0]);$C=$fe[$this->_offset++];return(object)array('name'=>$C,'charsetnr'=>$this->_charset[$C],);}}class
Min_Driver
extends
Min_SQL{public$hg="_id";function
select($Q,$L,$Z,$ld,$xf=array(),$_=1,$E=0,$jg=false){global$g;$L=($L==array("*")?array():array_fill_keys($L,1));if(count($L)&&!isset($L['_id']))$L['_id']=0;$Z=where_to_query($Z);$sh=array();foreach($xf
as$X){$X=preg_replace('~ DESC$~','',$X,1,$Cb);$sh[$X]=($Cb?-1:1);}if(isset($_GET['limit'])&&is_numeric($_GET['limit'])&&$_GET['limit']>0)$_=$_GET['limit'];$_=min(200,max(1,(int)$_));$ph=$E*$_;$jb='MongoDB\Driver\Query';$G=new$jb($Z,array('projection'=>$L,'limit'=>$_,'skip'=>$ph,'sort'=>$sh));$Mg=$g->_link->executeQuery("$g->_db_name.$Q",$G);return
new
Min_Result($Mg);}function
update($Q,$O,$tg,$_=0,$M="\n"){global$g;$m=$g->_db_name;$Z=sql_query_where_parser($tg);$jb='MongoDB\Driver\BulkWrite';$Ya=new$jb(array());if(isset($O['_id']))unset($O['_id']);$Gg=array();foreach($O
as$z=>$Y){if($Y=='NULL'){$Gg[$z]=1;unset($O[$z]);}}$Hi=array('$set'=>$O);if(count($Gg))$Hi['$unset']=$Gg;$Ya->update($Z,$Hi,array('upsert'=>false));$Mg=$g->_link->executeBulkWrite("$m.$Q",$Ya);$g->affected_rows=$Mg->getModifiedCount();return
true;}function
delete($Q,$tg,$_=0){global$g;$m=$g->_db_name;$Z=sql_query_where_parser($tg);$jb='MongoDB\Driver\BulkWrite';$Ya=new$jb(array());$Ya->delete($Z,array('limit'=>$_));$Mg=$g->_link->executeBulkWrite("$m.$Q",$Ya);$g->affected_rows=$Mg->getDeletedCount();return
true;}function
insert($Q,$O){global$g;$m=$g->_db_name;$jb='MongoDB\Driver\BulkWrite';$Ya=new$jb(array());if(isset($O['_id'])&&empty($O['_id']))unset($O['_id']);$Ya->insert($O);$Mg=$g->_link->executeBulkWrite("$m.$Q",$Ya);$g->affected_rows=$Mg->getInsertedCount();return
true;}}function
get_databases($Yc){global$g;$I=array();$jb='MongoDB\Driver\Command';$rb=new$jb(array('listDatabases'=>1));$Mg=$g->_link->executeCommand('admin',$rb);foreach($Mg
as$Ob){foreach($Ob->databases
as$m)$I[]=$m->name;}return$I;}function
count_tables($l){$I=array();return$I;}function
tables_list(){global$g;$jb='MongoDB\Driver\Command';$rb=new$jb(array('listCollections'=>1));$Mg=$g->_link->executeCommand($g->_db_name,$rb);$pb=array();foreach($Mg
as$H)$pb[$H->name]='table';return$pb;}function
drop_databases($l){return
false;}function
indexes($Q,$h=null){global$g;$I=array();$jb='MongoDB\Driver\Command';$rb=new$jb(array('listIndexes'=>$Q));$Mg=$g->_link->executeCommand($g->_db_name,$rb);foreach($Mg
as$w){$Vb=array();$f=array();foreach(get_object_vars($w->key)as$e=>$T){$Vb[]=($T==-1?'1':null);$f[]=$e;}$I[$w->name]=array("type"=>($w->name=="_id_"?"PRIMARY":(isset($w->unique)?"UNIQUE":"INDEX")),"columns"=>$f,"lengths"=>array(),"descs"=>$Vb,);}return$I;}function
fields($Q){$q=fields_from_edit();if(!count($q)){global$n;$H=$n->select($Q,array("*"),null,null,array(),10);while($J=$H->fetch_assoc()){foreach($J
as$z=>$X){$J[$z]=null;$q[$z]=array("field"=>$z,"type"=>"string","null"=>($z!=$n->primary),"auto_increment"=>($z==$n->primary),"privileges"=>array("insert"=>1,"select"=>1,"update"=>1,),);}}}return$q;}function
found_rows($R,$Z){global$g;$Z=where_to_query($Z);$jb='MongoDB\Driver\Command';$rb=new$jb(array('count'=>$R['Name'],'query'=>$Z));$Mg=$g->_link->executeCommand($g->_db_name,$rb);$li=$Mg->toArray();return$li[0]->n;}function
sql_query_where_parser($tg){$tg=trim(preg_replace('/WHERE[\s]?[(]?\(?/','',$tg));$tg=preg_replace('/\)\)\)$/',')',$tg);$hj=explode(' AND ',$tg);$ij=explode(') OR (',$tg);$Z=array();foreach($hj
as$fj)$Z[]=trim($fj);if(count($ij)==1)$ij=array();elseif(count($ij)>1)$Z=array();return
where_to_query($Z,$ij);}function
where_to_query($dj=array(),$ej=array()){global$b;$Jb=array();foreach(array('and'=>$dj,'or'=>$ej)as$T=>$Z){if(is_array($Z)){foreach($Z
as$Fc){list($mb,$qf,$X)=explode(" ",$Fc,3);if($mb=="_id"){$X=str_replace('MongoDB\BSON\ObjectID("',"",$X);$X=str_replace('")',"",$X);$jb='MongoDB\BSON\ObjectID';$X=new$jb($X);}if(!in_array($qf,$b->operators))continue;if(preg_match('~^\(f\)(.+)~',$qf,$B)){$X=(float)$X;$qf=$B[1];}elseif(preg_match('~^\(date\)(.+)~',$qf,$B)){$Lb=new
DateTime($X);$jb='MongoDB\BSON\UTCDatetime';$X=new$jb($Lb->getTimestamp()*1000);$qf=$B[1];}switch($qf){case'=':$qf='$eq';break;case'!=':$qf='$ne';break;case'>':$qf='$gt';break;case'<':$qf='$lt';break;case'>=':$qf='$gte';break;case'<=':$qf='$lte';break;case'regex':$qf='$regex';break;default:continue
2;}if($T=='and')$Jb['$and'][]=array($mb=>array($qf=>$X));elseif($T=='or')$Jb['$or'][]=array($mb=>array($qf=>$X));}}}return$Jb;}$sf=array("=","!=",">","<",">=","<=","regex","(f)=","(f)!=","(f)>","(f)<","(f)>=","(f)<=","(date)=","(date)!=","(date)>","(date)<","(date)>=","(date)<=",);}function
table($v){return$v;}function
idf_escape($v){return$v;}function
table_status($C="",$Mc=false){$I=array();foreach(tables_list()as$Q=>$T){$I[$Q]=array("Name"=>$Q);if($C==$Q)return$I[$Q];}return$I;}function
create_database($m,$d){return
true;}function
last_id(){global$g;return$g->last_id;}function
error(){global$g;return
h($g->error);}function
collations(){return
array();}function
logged_user(){global$b;$j=$b->credentials();return$j[1];}function
connect(){global$b;$g=new
Min_DB;list($N,$V,$F)=$b->credentials();$vf=array();if($V.$F!=""){$vf["username"]=$V;$vf["password"]=$F;}$m=$b->database();if($m!="")$vf["db"]=$m;try{$g->_link=$g->connect("mongodb://$N",$vf);if($F!=""){$vf["password"]="";try{$g->connect("mongodb://$N",$vf);return'Database does not support password.';}catch(Exception$zc){}}return$g;}catch(Exception$zc){return$zc->getMessage();}}function
alter_indexes($Q,$c){global$g;foreach($c
as$X){list($T,$C,$O)=$X;if($O=="DROP")$I=$g->_db->command(array("deleteIndexes"=>$Q,"index"=>$C));else{$f=array();foreach($O
as$e){$e=preg_replace('~ DESC$~','',$e,1,$Cb);$f[$e]=($Cb?-1:1);}$I=$g->_db->selectCollection($Q)->ensureIndex($f,array("unique"=>($T=="UNIQUE"),"name"=>$C,));}if($I['errmsg']){$g->error=$I['errmsg'];return
false;}}return
true;}function
support($Nc){return
preg_match("~database|indexes|descidx~",$Nc);}function
db_collation($m,$ob){}function
information_schema(){}function
is_view($R){}function
convert_field($p){}function
unconvert_field($p,$I){return$I;}function
foreign_keys($Q){return
array();}function
fk_support($R){}function
engines(){return
array();}function
alter_table($Q,$C,$q,$ad,$tb,$sc,$d,$La,$Rf){global$g;if($Q==""){$g->_db->createCollection($C);return
true;}}function
drop_tables($S){global$g;foreach($S
as$Q){$Jg=$g->_db->selectCollection($Q)->drop();if(!$Jg['ok'])return
false;}return
true;}function
truncate_tables($S){global$g;foreach($S
as$Q){$Jg=$g->_db->selectCollection($Q)->remove();if(!$Jg['ok'])return
false;}return
true;}$y="mongo";$id=array();$od=array();$kc=array(array("json"));}$cc["elastic"]="Elasticsearch (beta)";if(isset($_GET["elastic"])){$eg=array("json + allow_url_fopen");define("DRIVER","elastic");if(function_exists('json_decode')&&ini_bool('allow_url_fopen')){class
Min_DB{var$extension="JSON",$server_info,$errno,$error,$_url;function
rootQuery($Vf,$yb=array(),$Pe='GET'){@ini_set('track_errors',1);$Rc=@file_get_contents("$this->_url/".ltrim($Vf,'/'),false,stream_context_create(array('http'=>array('method'=>$Pe,'content'=>$yb===null?$yb:json_encode($yb),'header'=>'Content-Type: application/json','ignore_errors'=>1,))));if(!$Rc){$this->error=$php_errormsg;return$Rc;}if(!preg_match('~^HTTP/[0-9.]+ 2~i',$http_response_header[0])){$this->error=$Rc;return
false;}$I=json_decode($Rc,true);if($I===null){$this->errno=json_last_error();if(function_exists('json_last_error_msg'))$this->error=json_last_error_msg();else{$xb=get_defined_constants(true);foreach($xb['json']as$C=>$Y){if($Y==$this->errno&&preg_match('~^JSON_ERROR_~',$C)){$this->error=$C;break;}}}}return$I;}function
query($Vf,$yb=array(),$Pe='GET'){return$this->rootQuery(($this->_db!=""?"$this->_db/":"/").ltrim($Vf,'/'),$yb,$Pe);}function
connect($N,$V,$F){preg_match('~^(https?://)?(.*)~',$N,$B);$this->_url=($B[1]?$B[1]:"http://")."$V:$F@$B[2]";$I=$this->query('');if($I)$this->server_info=$I['version']['number'];return(bool)$I;}function
select_db($k){$this->_db=$k;return
true;}function
quote($P){return$P;}}class
Min_Result{var$num_rows,$_rows;function
__construct($K){$this->num_rows=count($this->_rows);$this->_rows=$K;reset($this->_rows);}function
fetch_assoc(){$I=current($this->_rows);next($this->_rows);return$I;}function
fetch_row(){return
array_values($this->fetch_assoc());}}}class
Min_Driver
extends
Min_SQL{function
select($Q,$L,$Z,$ld,$xf=array(),$_=1,$E=0,$jg=false){global$b;$Jb=array();$G="$Q/_search";if($L!=array("*"))$Jb["fields"]=$L;if($xf){$sh=array();foreach($xf
as$mb){$mb=preg_replace('~ DESC$~','',$mb,1,$Cb);$sh[]=($Cb?array($mb=>"desc"):$mb);}$Jb["sort"]=$sh;}if($_){$Jb["size"]=+$_;if($E)$Jb["from"]=($E*$_);}foreach($Z
as$X){list($mb,$qf,$X)=explode(" ",$X,3);if($mb=="_id")$Jb["query"]["ids"]["values"][]=$X;elseif($mb.$X!=""){$Yh=array("term"=>array(($mb!=""?$mb:"_all")=>$X));if($qf=="=")$Jb["query"]["filtered"]["filter"]["and"][]=$Yh;else$Jb["query"]["filtered"]["query"]["bool"]["must"][]=$Yh;}}if($Jb["query"]&&!$Jb["query"]["filtered"]["query"]&&!$Jb["query"]["ids"])$Jb["query"]["filtered"]["query"]=array("match_all"=>array());$Ah=microtime(true);$Zg=$this->_conn->query($G,$Jb);if($jg)echo$b->selectQuery("$G: ".print_r($Jb,true),$Ah,!$Zg);if(!$Zg)return
false;$I=array();foreach($Zg['hits']['hits']as$yd){$J=array();if($L==array("*"))$J["_id"]=$yd["_id"];$q=$yd['_source'];if($L!=array("*")){$q=array();foreach($L
as$z)$q[$z]=$yd['fields'][$z];}foreach($q
as$z=>$X){if($Jb["fields"])$X=$X[0];$J[$z]=(is_array($X)?json_encode($X):$X);}$I[]=$J;}return
new
Min_Result($I);}function
update($T,$yg,$tg,$_=0,$M="\n"){$Tf=preg_split('~ *= *~',$tg);if(count($Tf)==2){$u=trim($Tf[1]);$G="$T/$u";return$this->_conn->query($G,$yg,'POST');}return
false;}function
insert($T,$yg){$u="";$G="$T/$u";$Jg=$this->_conn->query($G,$yg,'POST');$this->_conn->last_id=$Jg['_id'];return$Jg['created'];}function
delete($T,$tg,$_=0){$Cd=array();if(is_array($_GET["where"])&&$_GET["where"]["_id"])$Cd[]=$_GET["where"]["_id"];if(is_array($_POST['check'])){foreach($_POST['check']as$cb){$Tf=preg_split('~ *= *~',$cb);if(count($Tf)==2)$Cd[]=trim($Tf[1]);}}$this->_conn->affected_rows=0;foreach($Cd
as$u){$G="{$T}/{$u}";$Jg=$this->_conn->query($G,'{}','DELETE');if(is_array($Jg)&&$Jg['found']==true)$this->_conn->affected_rows++;}return$this->_conn->affected_rows;}}function
connect(){global$b;$g=new
Min_DB;list($N,$V,$F)=$b->credentials();if($F!=""&&$g->connect($N,$V,""))return'Database does not support password.';if($g->connect($N,$V,$F))return$g;return$g->error;}function
support($Nc){return
preg_match("~database|table|columns~",$Nc);}function
logged_user(){global$b;$j=$b->credentials();return$j[1];}function
get_databases(){global$g;$I=$g->rootQuery('_aliases');if($I){$I=array_keys($I);sort($I,SORT_STRING);}return$I;}function
collations(){return
array();}function
db_collation($m,$ob){}function
engines(){return
array();}function
count_tables($l){global$g;$I=array();$H=$g->query('_stats');if($H&&$H['indices']){$Kd=$H['indices'];foreach($Kd
as$Jd=>$Bh){$Id=$Bh['total']['indexing'];$I[$Jd]=$Id['index_total'];}}return$I;}function
tables_list(){global$g;$I=$g->query('_mapping');if($I)$I=array_fill_keys(array_keys($I[$g->_db]["mappings"]),'table');return$I;}function
table_status($C="",$Mc=false){global$g;$Zg=$g->query("_search",array("size"=>0,"aggregations"=>array("count_by_type"=>array("terms"=>array("field"=>"_type")))),"POST");$I=array();if($Zg){$S=$Zg["aggregations"]["count_by_type"]["buckets"];foreach($S
as$Q){$I[$Q["key"]]=array("Name"=>$Q["key"],"Engine"=>"table","Rows"=>$Q["doc_count"],);if($C!=""&&$C==$Q["key"])return$I[$C];}}return$I;}function
error(){global$g;return
h($g->error);}function
information_schema(){}function
is_view($R){}function
indexes($Q,$h=null){return
array(array("type"=>"PRIMARY","columns"=>array("_id")),);}function
fields($Q){global$g;$H=$g->query("$Q/_mapping");$I=array();if($H){$ye=$H[$Q]['properties'];if(!$ye)$ye=$H[$g->_db]['mappings'][$Q]['properties'];if($ye){foreach($ye
as$C=>$p){$I[$C]=array("field"=>$C,"full_type"=>$p["type"],"type"=>$p["type"],"privileges"=>array("insert"=>1,"select"=>1,"update"=>1),);if($p["properties"]){unset($I[$C]["privileges"]["insert"]);unset($I[$C]["privileges"]["update"]);}}}}return$I;}function
foreign_keys($Q){return
array();}function
table($v){return$v;}function
idf_escape($v){return$v;}function
convert_field($p){}function
unconvert_field($p,$I){return$I;}function
fk_support($R){}function
found_rows($R,$Z){return
null;}function
create_database($m){global$g;return$g->rootQuery(urlencode($m),null,'PUT');}function
drop_databases($l){global$g;return$g->rootQuery(urlencode(implode(',',$l)),array(),'DELETE');}function
alter_table($Q,$C,$q,$ad,$tb,$sc,$d,$La,$Rf){global$g;$pg=array();foreach($q
as$Kc){$Pc=trim($Kc[1][0]);$Qc=trim($Kc[1][1]?$Kc[1][1]:"text");$pg[$Pc]=array('type'=>$Qc);}if(!empty($pg))$pg=array('properties'=>$pg);return$g->query("_mapping/{$C}",$pg,'PUT');}function
drop_tables($S){global$g;$I=true;foreach($S
as$Q)$I=$I&&$g->query(urlencode($Q),array(),'DELETE');return$I;}function
last_id(){global$g;return$g->last_id;}$y="elastic";$sf=array("=","query");$id=array();$od=array();$kc=array(array("json"));$U=array();$Fh=array();foreach(array('Numbers'=>array("long"=>3,"integer"=>5,"short"=>8,"byte"=>10,"double"=>20,"float"=>66,"half_float"=>12,"scaled_float"=>21),'Date and time'=>array("date"=>10),'Strings'=>array("string"=>65535,"text"=>65535),'Binary'=>array("binary"=>255),)as$z=>$X){$U+=$X;$Fh[$z]=array_keys($X);}}$cc["clickhouse"]="ClickHouse (alpha)";if(isset($_GET["clickhouse"])){define("DRIVER","clickhouse");class
Min_DB{var$extension="JSON",$server_info,$errno,$_result,$error,$_url;var$_db='default';function
rootQuery($m,$G){@ini_set('track_errors',1);$Rc=@file_get_contents("$this->_url/?database=$m",false,stream_context_create(array('http'=>array('method'=>'POST','content'=>$this->isQuerySelectLike($G)?"$G FORMAT JSONCompact":$G,'header'=>'Content-type: application/x-www-form-urlencoded','ignore_errors'=>1,))));if($Rc===false){$this->error=$php_errormsg;return$Rc;}if(!preg_match('~^HTTP/[0-9.]+ 2~i',$http_response_header[0])){$this->error=$Rc;return
false;}$I=json_decode($Rc,true);if($I===null){$this->errno=json_last_error();if(function_exists('json_last_error_msg'))$this->error=json_last_error_msg();else{$xb=get_defined_constants(true);foreach($xb['json']as$C=>$Y){if($Y==$this->errno&&preg_match('~^JSON_ERROR_~',$C)){$this->error=$C;break;}}}}return
new
Min_Result($I);}function
isQuerySelectLike($G){return(bool)preg_match('~^(select|show)~i',$G);}function
query($G){return$this->rootQuery($this->_db,$G);}function
connect($N,$V,$F){preg_match('~^(https?://)?(.*)~',$N,$B);$this->_url=($B[1]?$B[1]:"http://")."$V:$F@$B[2]";$I=$this->query('SELECT 1');return(bool)$I;}function
select_db($k){$this->_db=$k;return
true;}function
quote($P){return"'".addcslashes($P,"\\'")."'";}function
multi_query($G){return$this->_result=$this->query($G);}function
store_result(){return$this->_result;}function
next_result(){return
false;}function
result($G,$p=0){$H=$this->query($G);return$H['data'];}}class
Min_Result{var$num_rows,$_rows,$columns,$meta,$_offset=0;function
__construct($H){$this->num_rows=$H['rows'];$this->_rows=$H['data'];$this->meta=$H['meta'];$this->columns=array_column($this->meta,'name');reset($this->_rows);}function
fetch_assoc(){$J=current($this->_rows);next($this->_rows);return$J===false?false:array_combine($this->columns,$J);}function
fetch_row(){$J=current($this->_rows);next($this->_rows);return$J;}function
fetch_field(){$e=$this->_offset++;$I=new
stdClass;if($e<count($this->columns)){$I->name=$this->meta[$e]['name'];$I->orgname=$I->name;$I->type=$this->meta[$e]['type'];}return$I;}}class
Min_Driver
extends
Min_SQL{function
delete($Q,$tg,$_=0){return
queries("ALTER TABLE ".table($Q)." DELETE $tg");}function
update($Q,$O,$tg,$_=0,$M="\n"){$Si=array();foreach($O
as$z=>$X)$Si[]="$z = $X";$G=$M.implode(",$M",$Si);return
queries("ALTER TABLE ".table($Q)." UPDATE $G$tg");}}function
idf_escape($v){return"`".str_replace("`","``",$v)."`";}function
table($v){return
idf_escape($v);}function
explain($g,$G){return'';}function
found_rows($R,$Z){$K=get_vals("SELECT COUNT(*) FROM ".idf_escape($R["Name"]).($Z?" WHERE ".implode(" AND ",$Z):""));return
empty($K)?false:$K[0];}function
alter_table($Q,$C,$q,$ad,$tb,$sc,$d,$La,$Rf){foreach($q
as$p){if($p[1][2]===" NULL")$p[1][1]=" Nullable({$p[1][1]})";unset($p[1][2]);}}function
truncate_tables($S){return
apply_queries("TRUNCATE TABLE",$S);}function
drop_views($Xi){return
drop_tables($Xi);}function
drop_tables($S){return
apply_queries("DROP TABLE",$S);}function
connect(){global$b;$g=new
Min_DB;$j=$b->credentials();if($g->connect($j[0],$j[1],$j[2]))return$g;return$g->error;}function
get_databases($Yc){global$g;$H=get_rows('SHOW DATABASES');$I=array();foreach($H
as$J)$I[]=$J['name'];sort($I);return$I;}function
limit($G,$Z,$_,$D=0,$M=" "){return" $G$Z".($_!==null?$M."LIMIT $_".($D?", $D":""):"");}function
limit1($Q,$G,$Z,$M="\n"){return
limit($G,$Z,1,0,$M);}function
db_collation($m,$ob){}function
engines(){return
array('MergeTree');}function
logged_user(){global$b;$j=$b->credentials();return$j[1];}function
tables_list(){$H=get_rows('SHOW TABLES');$I=array();foreach($H
as$J)$I[$J['name']]='table';ksort($I);return$I;}function
count_tables($l){return
array();}function
table_status($C="",$Mc=false){global$g;$I=array();$S=get_rows("SELECT name, engine FROM system.tables WHERE database = ".q($g->_db));foreach($S
as$Q){$I[$Q['name']]=array('Name'=>$Q['name'],'Engine'=>$Q['engine'],);if($C===$Q['name'])return$I[$Q['name']];}return$I;}function
is_view($R){return
false;}function
fk_support($R){return
false;}function
convert_field($p){}function
unconvert_field($p,$I){if(in_array($p['type'],array("Int8","Int16","Int32","Int64","UInt8","UInt16","UInt32","UInt64","Float32","Float64")))return"to$p[type]($I)";return$I;}function
fields($Q){$I=array();$H=get_rows("SELECT name, type, default_expression FROM system.columns WHERE ".idf_escape('table')." = ".q($Q));foreach($H
as$J){$T=trim($J['type']);$cf=strpos($T,'Nullable(')===0;$I[trim($J['name'])]=array("field"=>trim($J['name']),"full_type"=>$T,"type"=>$T,"default"=>trim($J['default_expression']),"null"=>$cf,"auto_increment"=>'0',"privileges"=>array("insert"=>1,"select"=>1,"update"=>0),);}return$I;}function
indexes($Q,$h=null){return
array();}function
foreign_keys($Q){return
array();}function
collations(){return
array();}function
information_schema($m){return
false;}function
error(){global$g;return
h($g->error);}function
types(){return
array();}function
schemas(){return
array();}function
get_schema(){return"";}function
set_schema($Xg){return
true;}function
auto_increment(){return'';}function
last_id(){return
0;}function
support($Nc){return
preg_match("~^(columns|sql|status|table)$~",$Nc);}$y="clickhouse";$U=array();$Fh=array();foreach(array('Numbers'=>array("Int8"=>3,"Int16"=>5,"Int32"=>10,"Int64"=>19,"UInt8"=>3,"UInt16"=>5,"UInt32"=>10,"UInt64"=>20,"Float32"=>7,"Float64"=>16,'Decimal'=>38,'Decimal32'=>9,'Decimal64'=>18,'Decimal128'=>38),'Date and time'=>array("Date"=>13,"DateTime"=>20),'Strings'=>array("String"=>0),'Binary'=>array("FixedString"=>0),)as$z=>$X){$U+=$X;$Fh[$z]=array_keys($X);}$Gi=array();$sf=array("=","<",">","<=",">=","!=","~","!~","LIKE","LIKE %%","IN","IS NULL","NOT LIKE","NOT IN","IS NOT NULL","SQL");$id=array();$od=array("avg","count","count distinct","max","min","sum");$kc=array();}$cc=array("server"=>"MySQL")+$cc;if(!defined("DRIVER")){$eg=array("MySQLi","MySQL","PDO_MySQL");define("DRIVER","server");if(extension_loaded("mysqli")){class
Min_DB
extends
MySQLi{var$extension="MySQLi";function
__construct(){parent::init();}function
connect($N="",$V="",$F="",$k=null,$ag=null,$rh=null){global$b;mysqli_report(MYSQLI_REPORT_OFF);list($zd,$ag)=explode(":",$N,2);$_h=$b->connectSsl();if($_h)$this->ssl_set($_h['key'],$_h['cert'],$_h['ca'],'','');$I=@$this->real_connect(($N!=""?$zd:ini_get("mysqli.default_host")),($N.$V!=""?$V:ini_get("mysqli.default_user")),($N.$V.$F!=""?$F:ini_get("mysqli.default_pw")),$k,(is_numeric($ag)?$ag:ini_get("mysqli.default_port")),(!is_numeric($ag)?$ag:$rh),($_h?64:0));$this->options(MYSQLI_OPT_LOCAL_INFILE,false);return$I;}function
set_charset($bb){if(parent::set_charset($bb))return
true;parent::set_charset('utf8');return$this->query("SET NAMES $bb");}function
result($G,$p=0){$H=$this->query($G);if(!$H)return
false;$J=$H->fetch_array();return$J[$p];}function
quote($P){return"'".$this->escape_string($P)."'";}}}elseif(extension_loaded("mysql")&&!((ini_bool("sql.safe_mode")||ini_bool("mysql.allow_local_infile"))&&extension_loaded("pdo_mysql"))){class
Min_DB{var$extension="MySQL",$server_info,$affected_rows,$errno,$error,$_link,$_result;function
connect($N,$V,$F){if(ini_bool("mysql.allow_local_infile")){$this->error=sprintf('Disable %s or enable %s or %s extensions.',"'mysql.allow_local_infile'","MySQLi","PDO_MySQL");return
false;}$this->_link=@mysql_connect(($N!=""?$N:ini_get("mysql.default_host")),("$N$V"!=""?$V:ini_get("mysql.default_user")),("$N$V$F"!=""?$F:ini_get("mysql.default_password")),true,131072);if($this->_link)$this->server_info=mysql_get_server_info($this->_link);else$this->error=mysql_error();return(bool)$this->_link;}function
set_charset($bb){if(function_exists('mysql_set_charset')){if(mysql_set_charset($bb,$this->_link))return
true;mysql_set_charset('utf8',$this->_link);}return$this->query("SET NAMES $bb");}function
quote($P){return"'".mysql_real_escape_string($P,$this->_link)."'";}function
select_db($k){return
mysql_select_db($k,$this->_link);}function
query($G,$Ai=false){$H=@($Ai?mysql_unbuffered_query($G,$this->_link):mysql_query($G,$this->_link));$this->error="";if(!$H){$this->errno=mysql_errno($this->_link);$this->error=mysql_error($this->_link);return
false;}if($H===true){$this->affected_rows=mysql_affected_rows($this->_link);$this->info=mysql_info($this->_link);return
true;}return
new
Min_Result($H);}function
multi_query($G){return$this->_result=$this->query($G);}function
store_result(){return$this->_result;}function
next_result(){return
false;}function
result($G,$p=0){$H=$this->query($G);if(!$H||!$H->num_rows)return
false;return
mysql_result($H->_result,0,$p);}}class
Min_Result{var$num_rows,$_result,$_offset=0;function
__construct($H){$this->_result=$H;$this->num_rows=mysql_num_rows($H);}function
fetch_assoc(){return
mysql_fetch_assoc($this->_result);}function
fetch_row(){return
mysql_fetch_row($this->_result);}function
fetch_field(){$I=mysql_fetch_field($this->_result,$this->_offset++);$I->orgtable=$I->table;$I->orgname=$I->name;$I->charsetnr=($I->blob?63:0);return$I;}function
__destruct(){mysql_free_result($this->_result);}}}elseif(extension_loaded("pdo_mysql")){class
Min_DB
extends
Min_PDO{var$extension="PDO_MySQL";function
connect($N,$V,$F){global$b;$vf=array(PDO::MYSQL_ATTR_LOCAL_INFILE=>false);$_h=$b->connectSsl();if($_h)$vf+=array(PDO::MYSQL_ATTR_SSL_KEY=>$_h['key'],PDO::MYSQL_ATTR_SSL_CERT=>$_h['cert'],PDO::MYSQL_ATTR_SSL_CA=>$_h['ca'],);$this->dsn("mysql:charset=utf8;host=".str_replace(":",";unix_socket=",preg_replace('~:(\d)~',';port=\1',$N)),$V,$F,$vf);return
true;}function
set_charset($bb){$this->query("SET NAMES $bb");}function
select_db($k){return$this->query("USE ".idf_escape($k));}function
query($G,$Ai=false){$this->setAttribute(1000,!$Ai);return
parent::query($G,$Ai);}}}class
Min_Driver
extends
Min_SQL{function
insert($Q,$O){return($O?parent::insert($Q,$O):queries("INSERT INTO ".table($Q)." ()\nVALUES ()"));}function
insertUpdate($Q,$K,$hg){$f=array_keys(reset($K));$fg="INSERT INTO ".table($Q)." (".implode(", ",$f).") VALUES\n";$Si=array();foreach($f
as$z)$Si[$z]="$z = VALUES($z)";$Ih="\nON DUPLICATE KEY UPDATE ".implode(", ",$Si);$Si=array();$re=0;foreach($K
as$O){$Y="(".implode(", ",$O).")";if($Si&&(strlen($fg)+$re+strlen($Y)+strlen($Ih)>1e6)){if(!queries($fg.implode(",\n",$Si).$Ih))return
false;$Si=array();$re=0;}$Si[]=$Y;$re+=strlen($Y)+2;}return
queries($fg.implode(",\n",$Si).$Ih);}function
slowQuery($G,$di){if(min_version('5.7.8','10.1.2')){if(preg_match('~MariaDB~',$this->_conn->server_info))return"SET STATEMENT max_statement_time=$di FOR $G";elseif(preg_match('~^(SELECT\b)(.+)~is',$G,$B))return"$B[1] /*+ MAX_EXECUTION_TIME(".($di*1000).") */ $B[2]";}}function
convertSearch($v,$X,$p){return(preg_match('~char|text|enum|set~',$p["type"])&&!preg_match("~^utf8~",$p["collation"])&&preg_match('~[\x80-\xFF]~',$X['val'])?"CONVERT($v USING ".charset($this->_conn).")":$v);}function
warnings(){$H=$this->_conn->query("SHOW WARNINGS");if($H&&$H->num_rows){ob_start();select($H);return
ob_get_clean();}}function
tableHelp($C){$ze=preg_match('~MariaDB~',$this->_conn->server_info);if(information_schema(DB))return
strtolower(($ze?"information-schema-$C-table/":str_replace("_","-",$C)."-table.html"));if(DB=="mysql")return($ze?"mysql$C-table/":"system-database.html");}}function
idf_escape($v){return"`".str_replace("`","``",$v)."`";}function
table($v){return
idf_escape($v);}function
connect(){global$b,$U,$Fh;$g=new
Min_DB;$j=$b->credentials();if($g->connect($j[0],$j[1],$j[2])){$g->set_charset(charset($g));$g->query("SET sql_quote_show_create = 1, autocommit = 1");if(min_version('5.7.8',10.2,$g)){$Fh['Strings'][]="json";$U["json"]=4294967295;}return$g;}$I=$g->error;if(function_exists('iconv')&&!is_utf8($I)&&strlen($Vg=iconv("windows-1250","utf-8",$I))>strlen($I))$I=$Vg;return$I;}function
get_databases($Yc){$I=get_session("dbs");if($I===null){$G=(min_version(5)?"SELECT SCHEMA_NAME FROM information_schema.SCHEMATA ORDER BY SCHEMA_NAME":"SHOW DATABASES");$I=($Yc?slow_query($G):get_vals($G));restart_session();set_session("dbs",$I);stop_session();}return$I;}function
limit($G,$Z,$_,$D=0,$M=" "){return" $G$Z".($_!==null?$M."LIMIT $_".($D?" OFFSET $D":""):"");}function
limit1($Q,$G,$Z,$M="\n"){return
limit($G,$Z,1,0,$M);}function
db_collation($m,$ob){global$g;$I=null;$i=$g->result("SHOW CREATE DATABASE ".idf_escape($m),1);if(preg_match('~ COLLATE ([^ ]+)~',$i,$B))$I=$B[1];elseif(preg_match('~ CHARACTER SET ([^ ]+)~',$i,$B))$I=$ob[$B[1]][-1];return$I;}function
engines(){$I=array();foreach(get_rows("SHOW ENGINES")as$J){if(preg_match("~YES|DEFAULT~",$J["Support"]))$I[]=$J["Engine"];}return$I;}function
logged_user(){global$g;return$g->result("SELECT USER()");}function
tables_list(){return
get_key_vals(min_version(5)?"SELECT TABLE_NAME, TABLE_TYPE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() ORDER BY TABLE_NAME":"SHOW TABLES");}function
count_tables($l){$I=array();foreach($l
as$m)$I[$m]=count(get_vals("SHOW TABLES IN ".idf_escape($m)));return$I;}function
table_status($C="",$Mc=false){$I=array();foreach(get_rows($Mc&&min_version(5)?"SELECT TABLE_NAME AS Name, ENGINE AS Engine, TABLE_COMMENT AS Comment FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() ".($C!=""?"AND TABLE_NAME = ".q($C):"ORDER BY Name"):"SHOW TABLE STATUS".($C!=""?" LIKE ".q(addcslashes($C,"%_\\")):""))as$J){if($J["Engine"]=="InnoDB")$J["Comment"]=preg_replace('~(?:(.+); )?InnoDB free: .*~','\1',$J["Comment"]);if(!isset($J["Engine"]))$J["Comment"]="";if($C!="")return$J;$I[$J["Name"]]=$J;}return$I;}function
is_view($R){return$R["Engine"]===null;}function
fk_support($R){return
preg_match('~InnoDB|IBMDB2I~i',$R["Engine"])||(preg_match('~NDB~i',$R["Engine"])&&min_version(5.6));}function
fields($Q){$I=array();foreach(get_rows("SHOW FULL COLUMNS FROM ".table($Q))as$J){preg_match('~^([^( ]+)(?:\((.+)\))?( unsigned)?( zerofill)?$~',$J["Type"],$B);$I[$J["Field"]]=array("field"=>$J["Field"],"full_type"=>$J["Type"],"type"=>$B[1],"length"=>$B[2],"unsigned"=>ltrim($B[3].$B[4]),"default"=>($J["Default"]!=""||preg_match("~char|set~",$B[1])?$J["Default"]:null),"null"=>($J["Null"]=="YES"),"auto_increment"=>($J["Extra"]=="auto_increment"),"on_update"=>(preg_match('~^on update (.+)~i',$J["Extra"],$B)?$B[1]:""),"collation"=>$J["Collation"],"privileges"=>array_flip(preg_split('~, *~',$J["Privileges"])),"comment"=>$J["Comment"],"primary"=>($J["Key"]=="PRI"),);}return$I;}function
indexes($Q,$h=null){$I=array();foreach(get_rows("SHOW INDEX FROM ".table($Q),$h)as$J){$C=$J["Key_name"];$I[$C]["type"]=($C=="PRIMARY"?"PRIMARY":($J["Index_type"]=="FULLTEXT"?"FULLTEXT":($J["Non_unique"]?($J["Index_type"]=="SPATIAL"?"SPATIAL":"INDEX"):"UNIQUE")));$I[$C]["columns"][]=$J["Column_name"];$I[$C]["lengths"][]=($J["Index_type"]=="SPATIAL"?null:$J["Sub_part"]);$I[$C]["descs"][]=null;}return$I;}function
foreign_keys($Q){global$g,$nf;static$Xf='(?:`(?:[^`]|``)+`)|(?:"(?:[^"]|"")+")';$I=array();$Db=$g->result("SHOW CREATE TABLE ".table($Q),1);if($Db){preg_match_all("~CONSTRAINT ($Xf) FOREIGN KEY ?\\(((?:$Xf,? ?)+)\\) REFERENCES ($Xf)(?:\\.($Xf))? \\(((?:$Xf,? ?)+)\\)(?: ON DELETE ($nf))?(?: ON UPDATE ($nf))?~",$Db,$Be,PREG_SET_ORDER);foreach($Be
as$B){preg_match_all("~$Xf~",$B[2],$th);preg_match_all("~$Xf~",$B[5],$Vh);$I[idf_unescape($B[1])]=array("db"=>idf_unescape($B[4]!=""?$B[3]:$B[4]),"table"=>idf_unescape($B[4]!=""?$B[4]:$B[3]),"source"=>array_map('idf_unescape',$th[0]),"target"=>array_map('idf_unescape',$Vh[0]),"on_delete"=>($B[6]?$B[6]:"RESTRICT"),"on_update"=>($B[7]?$B[7]:"RESTRICT"),);}}return$I;}function
view($C){global$g;return
array("select"=>preg_replace('~^(?:[^`]|`[^`]*`)*\s+AS\s+~isU','',$g->result("SHOW CREATE VIEW ".table($C),1)));}function
collations(){$I=array();foreach(get_rows("SHOW COLLATION")as$J){if($J["Default"])$I[$J["Charset"]][-1]=$J["Collation"];else$I[$J["Charset"]][]=$J["Collation"];}ksort($I);foreach($I
as$z=>$X)asort($I[$z]);return$I;}function
information_schema($m){return(min_version(5)&&$m=="information_schema")||(min_version(5.5)&&$m=="performance_schema");}function
error(){global$g;return
h(preg_replace('~^You have an error.*syntax to use~U',"Syntax error",$g->error));}function
create_database($m,$d){return
queries("CREATE DATABASE ".idf_escape($m).($d?" COLLATE ".q($d):""));}function
drop_databases($l){$I=apply_queries("DROP DATABASE",$l,'idf_escape');restart_session();set_session("dbs",null);return$I;}function
rename_database($C,$d){$I=false;if(create_database($C,$d)){$Hg=array();foreach(tables_list()as$Q=>$T)$Hg[]=table($Q)." TO ".idf_escape($C).".".table($Q);$I=(!$Hg||queries("RENAME TABLE ".implode(", ",$Hg)));if($I)queries("DROP DATABASE ".idf_escape(DB));restart_session();set_session("dbs",null);}return$I;}function
auto_increment(){$Ma=" PRIMARY KEY";if($_GET["create"]!=""&&$_POST["auto_increment_col"]){foreach(indexes($_GET["create"])as$w){if(in_array($_POST["fields"][$_POST["auto_increment_col"]]["orig"],$w["columns"],true)){$Ma="";break;}if($w["type"]=="PRIMARY")$Ma=" UNIQUE";}}return" AUTO_INCREMENT$Ma";}function
alter_table($Q,$C,$q,$ad,$tb,$sc,$d,$La,$Rf){$c=array();foreach($q
as$p)$c[]=($p[1]?($Q!=""?($p[0]!=""?"CHANGE ".idf_escape($p[0]):"ADD"):" ")." ".implode($p[1]).($Q!=""?$p[2]:""):"DROP ".idf_escape($p[0]));$c=array_merge($c,$ad);$Ch=($tb!==null?" COMMENT=".q($tb):"").($sc?" ENGINE=".q($sc):"").($d?" COLLATE ".q($d):"").($La!=""?" AUTO_INCREMENT=$La":"");if($Q=="")return
queries("CREATE TABLE ".table($C)." (\n".implode(",\n",$c)."\n)$Ch$Rf");if($Q!=$C)$c[]="RENAME TO ".table($C);if($Ch)$c[]=ltrim($Ch);return($c||$Rf?queries("ALTER TABLE ".table($Q)."\n".implode(",\n",$c).$Rf):true);}function
alter_indexes($Q,$c){foreach($c
as$z=>$X)$c[$z]=($X[2]=="DROP"?"\nDROP INDEX ".idf_escape($X[1]):"\nADD $X[0] ".($X[0]=="PRIMARY"?"KEY ":"").($X[1]!=""?idf_escape($X[1])." ":"")."(".implode(", ",$X[2]).")");return
queries("ALTER TABLE ".table($Q).implode(",",$c));}function
truncate_tables($S){return
apply_queries("TRUNCATE TABLE",$S);}function
drop_views($Xi){return
queries("DROP VIEW ".implode(", ",array_map('table',$Xi)));}function
drop_tables($S){return
queries("DROP TABLE ".implode(", ",array_map('table',$S)));}function
move_tables($S,$Xi,$Vh){$Hg=array();foreach(array_merge($S,$Xi)as$Q)$Hg[]=table($Q)." TO ".idf_escape($Vh).".".table($Q);return
queries("RENAME TABLE ".implode(", ",$Hg));}function
copy_tables($S,$Xi,$Vh){queries("SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO'");foreach($S
as$Q){$C=($Vh==DB?table("copy_$Q"):idf_escape($Vh).".".table($Q));if(!queries("CREATE TABLE $C LIKE ".table($Q))||!queries("INSERT INTO $C SELECT * FROM ".table($Q)))return
false;foreach(get_rows("SHOW TRIGGERS LIKE ".q(addcslashes($Q,"%_\\")))as$J){$vi=$J["Trigger"];if(!queries("CREATE TRIGGER ".($Vh==DB?idf_escape("copy_$vi"):idf_escape($Vh).".".idf_escape($vi))." $J[Timing] $J[Event] ON $C FOR EACH ROW\n$J[Statement];"))return
false;}}foreach($Xi
as$Q){$C=($Vh==DB?table("copy_$Q"):idf_escape($Vh).".".table($Q));$Wi=view($Q);if(!queries("CREATE VIEW $C AS $Wi[select]"))return
false;}return
true;}function
trigger($C){if($C=="")return
array();$K=get_rows("SHOW TRIGGERS WHERE `Trigger` = ".q($C));return
reset($K);}function
triggers($Q){$I=array();foreach(get_rows("SHOW TRIGGERS LIKE ".q(addcslashes($Q,"%_\\")))as$J)$I[$J["Trigger"]]=array($J["Timing"],$J["Event"]);return$I;}function
trigger_options(){return
array("Timing"=>array("BEFORE","AFTER"),"Event"=>array("INSERT","UPDATE","DELETE"),"Type"=>array("FOR EACH ROW"),);}function
routine($C,$T){global$g,$uc,$Pd,$U;$Ca=array("bool","boolean","integer","double precision","real","dec","numeric","fixed","national char","national varchar");$uh="(?:\\s|/\\*[\s\S]*?\\*/|(?:#|-- )[^\n]*\n?|--\r?\n)";$_i="((".implode("|",array_merge(array_keys($U),$Ca)).")\\b(?:\\s*\\(((?:[^'\")]|$uc)++)\\))?\\s*(zerofill\\s*)?(unsigned(?:\\s+zerofill)?)?)(?:\\s*(?:CHARSET|CHARACTER\\s+SET)\\s*['\"]?([^'\"\\s,]+)['\"]?)?";$Xf="$uh*(".($T=="FUNCTION"?"":$Pd).")?\\s*(?:`((?:[^`]|``)*)`\\s*|\\b(\\S+)\\s+)$_i";$i=$g->result("SHOW CREATE $T ".idf_escape($C),2);preg_match("~\\(((?:$Xf\\s*,?)*)\\)\\s*".($T=="FUNCTION"?"RETURNS\\s+$_i\\s+":"")."(.*)~is",$i,$B);$q=array();preg_match_all("~$Xf\\s*,?~is",$B[1],$Be,PREG_SET_ORDER);foreach($Be
as$Kf){$C=str_replace("``","`",$Kf[2]).$Kf[3];$q[]=array("field"=>$C,"type"=>strtolower($Kf[5]),"length"=>preg_replace_callback("~$uc~s",'normalize_enum',$Kf[6]),"unsigned"=>strtolower(preg_replace('~\s+~',' ',trim("$Kf[8] $Kf[7]"))),"null"=>1,"full_type"=>$Kf[4],"inout"=>strtoupper($Kf[1]),"collation"=>strtolower($Kf[9]),);}if($T!="FUNCTION")return
array("fields"=>$q,"definition"=>$B[11]);return
array("fields"=>$q,"returns"=>array("type"=>$B[12],"length"=>$B[13],"unsigned"=>$B[15],"collation"=>$B[16]),"definition"=>$B[17],"language"=>"SQL",);}function
routines(){return
get_rows("SELECT ROUTINE_NAME AS SPECIFIC_NAME, ROUTINE_NAME, ROUTINE_TYPE, DTD_IDENTIFIER FROM information_schema.ROUTINES WHERE ROUTINE_SCHEMA = ".q(DB));}function
routine_languages(){return
array();}function
routine_id($C,$J){return
idf_escape($C);}function
last_id(){global$g;return$g->result("SELECT LAST_INSERT_ID()");}function
explain($g,$G){return$g->query("EXPLAIN ".(min_version(5.1)?"PARTITIONS ":"").$G);}function
found_rows($R,$Z){return($Z||$R["Engine"]!="InnoDB"?null:$R["Rows"]);}function
types(){return
array();}function
schemas(){return
array();}function
get_schema(){return"";}function
set_schema($Xg){return
true;}function
create_sql($Q,$La,$Gh){global$g;$I=$g->result("SHOW CREATE TABLE ".table($Q),1);if(!$La)$I=preg_replace('~ AUTO_INCREMENT=\d+~','',$I);return$I;}function
truncate_sql($Q){return"TRUNCATE ".table($Q);}function
use_sql($k){return"USE ".idf_escape($k);}function
trigger_sql($Q){$I="";foreach(get_rows("SHOW TRIGGERS LIKE ".q(addcslashes($Q,"%_\\")),null,"-- ")as$J)$I.="\nCREATE TRIGGER ".idf_escape($J["Trigger"])." $J[Timing] $J[Event] ON ".table($J["Table"])." FOR EACH ROW\n$J[Statement];;\n";return$I;}function
show_variables(){return
get_key_vals("SHOW VARIABLES");}function
process_list(){return
get_rows("SHOW FULL PROCESSLIST");}function
show_status(){return
get_key_vals("SHOW STATUS");}function
convert_field($p){if(preg_match("~binary~",$p["type"]))return"HEX(".idf_escape($p["field"]).")";if($p["type"]=="bit")return"BIN(".idf_escape($p["field"])." + 0)";if(preg_match("~geometry|point|linestring|polygon~",$p["type"]))return(min_version(8)?"ST_":"")."AsWKT(".idf_escape($p["field"]).")";}function
unconvert_field($p,$I){if(preg_match("~binary~",$p["type"]))$I="UNHEX($I)";if($p["type"]=="bit")$I="CONV($I, 2, 10) + 0";if(preg_match("~geometry|point|linestring|polygon~",$p["type"]))$I=(min_version(8)?"ST_":"")."GeomFromText($I)";return$I;}function
support($Nc){return!preg_match("~scheme|sequence|type|view_trigger|materializedview".(min_version(8)?"":"|descidx".(min_version(5.1)?"":"|event|partitioning".(min_version(5)?"":"|routine|trigger|view")))."~",$Nc);}function
kill_process($X){return
queries("KILL ".number($X));}function
connection_id(){return"SELECT CONNECTION_ID()";}function
max_connections(){global$g;return$g->result("SELECT @@max_connections");}$y="sql";$U=array();$Fh=array();foreach(array('Numbers'=>array("tinyint"=>3,"smallint"=>5,"mediumint"=>8,"int"=>10,"bigint"=>20,"decimal"=>66,"float"=>12,"double"=>21),'Date and time'=>array("date"=>10,"datetime"=>19,"timestamp"=>19,"time"=>10,"year"=>4),'Strings'=>array("char"=>255,"varchar"=>65535,"tinytext"=>255,"text"=>65535,"mediumtext"=>16777215,"longtext"=>4294967295),'Lists'=>array("enum"=>65535,"set"=>64),'Binary'=>array("bit"=>20,"binary"=>255,"varbinary"=>65535,"tinyblob"=>255,"blob"=>65535,"mediumblob"=>16777215,"longblob"=>4294967295),'Geometry'=>array("geometry"=>0,"point"=>0,"linestring"=>0,"polygon"=>0,"multipoint"=>0,"multilinestring"=>0,"multipolygon"=>0,"geometrycollection"=>0),)as$z=>$X){$U+=$X;$Fh[$z]=array_keys($X);}$Gi=array("unsigned","zerofill","unsigned zerofill");$sf=array("=","<",">","<=",">=","!=","LIKE","LIKE %%","REGEXP","IN","FIND_IN_SET","IS NULL","NOT LIKE","NOT REGEXP","NOT IN","IS NOT NULL","SQL");$id=array("char_length","date","from_unixtime","lower","round","floor","ceil","sec_to_time","time_to_sec","upper");$od=array("avg","count","count distinct","group_concat","max","min","sum");$kc=array(array("char"=>"md5/sha1/password/encrypt/uuid","binary"=>"md5/sha1","date|time"=>"now",),array(number_type()=>"+/-","date"=>"+ interval/- interval","time"=>"addtime/subtime","char|text"=>"concat",));}define("SERVER",$_GET[DRIVER]);define("DB",$_GET["db"]);define("ME",preg_replace('~^[^?]*/([^?]*).*~','\1',$_SERVER["REQUEST_URI"]).'?'.(sid()?SID.'&':'').(SERVER!==null?DRIVER."=".urlencode(SERVER).'&':'').(isset($_GET["username"])?"username=".urlencode($_GET["username"]).'&':'').(DB!=""?'db='.urlencode(DB).'&'.(isset($_GET["ns"])?"ns=".urlencode($_GET["ns"])."&":""):''));$ia="4.7.1";class
Adminer{var$operators;function
name(){return"<a href='https://www.adminer.org/'".target_blank()." id='h1'>Adminer</a>";}function
credentials(){return
array(SERVER,$_GET["username"],get_password());}function
connectSsl(){}function
permanentLogin($i=false){return
password_file($i);}function
bruteForceKey(){return$_SERVER["REMOTE_ADDR"];}function
serverName($N){return
h($N);}function
database(){return
DB;}function
databases($Yc=true){return
get_databases($Yc);}function
schemas(){return
schemas();}function
queryTimeout(){return
2;}function
headers(){}function
csp(){return
csp();}function
head(){return
true;}function
css(){$I=array();$Sc="adminer.css";if(file_exists($Sc))$I[]=$Sc;return$I;}function
loginForm(){global$cc;echo"<table cellspacing='0' class='layout'>\n",$this->loginFormField('driver','<tr><th>'.'System'.'<td>',adminer_html_select("auth[driver]",$cc,DRIVER,"loginDriver(this);")."\n"),$this->loginFormField('server','<tr><th>'.'Server'.'<td>','<input name="auth[server]" value="'.h(SERVER).'" title="hostname[:port]" placeholder="localhost" autocapitalize="off">'."\n"),$this->loginFormField('username','<tr><th>'.'Username'.'<td>','<input name="auth[username]" id="username" value="'.h($_GET["username"]).'" autocomplete="username" autocapitalize="off">'.script("focus(qs('#username')); qs('#username').form['auth[driver]'].onchange();")),$this->loginFormField('password','<tr><th>'.'Password'.'<td>','<input type="password" name="auth[password]" autocomplete="current-password">'."\n"),$this->loginFormField('db','<tr><th>'.'Database'.'<td>','<input name="auth[db]" value="'.h($_GET["db"]).'" autocapitalize="off">'."\n"),"</table>\n","<p><input type='submit' value='".'Login'."'>\n",checkbox("auth[permanent]",1,$_COOKIE["adminer_permanent"],'Permanent login')."\n";}function
loginFormField($C,$vd,$Y){return$vd.$Y;}function
login($we,$F){if($F=="")return
sprintf('Adminer does not support accessing a database without a password, <a href="https://www.adminer.org/en/password/"%s>more information</a>.',target_blank());return
true;}function
tableName($Mh){return
h($Mh["Name"]);}function
fieldName($p,$xf=0){return'<span title="'.h($p["full_type"]).'">'.h($p["field"]).'</span>';}function
selectLinks($Mh,$O=""){global$y,$n;echo'<p class="links">';$ue=array("select"=>'Select data');if(support("table")||support("indexes"))$ue["table"]='Show structure';if(support("table")){if(is_view($Mh))$ue["view"]='Alter view';else$ue["create"]='Alter table';}if($O!==null)$ue["edit"]='New item';$C=$Mh["Name"];foreach($ue
as$z=>$X)echo" <a href='".h(ME)."$z=".urlencode($C).($z=="edit"?$O:"")."'".bold(isset($_GET[$z])).">$X</a>";echo
doc_link(array($y=>$n->tableHelp($C)),"?"),"\n";}function
foreignKeys($Q){return
foreign_keys($Q);}function
backwardKeys($Q,$Lh){return
array();}function
backwardKeysPrint($Oa,$J){}function
selectQuery($G,$Ah,$Lc=false){global$y,$n;$I="</p>\n";if(!$Lc&&($aj=$n->warnings())){$u="warnings";$I=", <a href='#$u'>".'Warnings'."</a>".script("qsl('a').onclick = partial(toggle, '$u');","")."$I<div id='$u' class='hidden'>\n$aj</div>\n";}return"<p><code class='jush-$y'>".h(str_replace("\n"," ",$G))."</code> <span class='time'>(".format_time($Ah).")</span>".(support("sql")?" <a href='".h(ME)."sql=".urlencode($G)."'>".'Edit'."</a>":"").$I;}function
sqlCommandQuery($G){return
shorten_utf8(trim($G),1000);}function
rowDescription($Q){return"";}function
rowDescriptions($K,$bd){return$K;}function
selectLink($X,$p){}function
selectVal($X,$A,$p,$Ef){$I=($X===null?"<i>NULL</i>":(preg_match("~char|binary|boolean~",$p["type"])&&!preg_match("~var~",$p["type"])?"<code>$X</code>":$X));if(preg_match('~blob|bytea|raw|file~',$p["type"])&&!is_utf8($X))$I="<i>".lang(array('%d byte','%d bytes'),strlen($Ef))."</i>";if(preg_match('~json~',$p["type"]))$I="<code class='jush-js'>$I</code>";return($A?"<a href='".h($A)."'".(is_url($A)?target_blank():"").">$I</a>":$I);}function
editVal($X,$p){return$X;}function
tableStructurePrint($q){echo"<div class='scrollable'>\n","<table cellspacing='0' class='nowrap'>\n","<thead><tr><th>".'Column'."<td>".'Type'.(support("comment")?"<td>".'Comment':"")."</thead>\n";foreach($q
as$p){echo"<tr".odd()."><th>".h($p["field"]),"<td><span title='".h($p["collation"])."'>".h($p["full_type"])."</span>",($p["null"]?" <i>NULL</i>":""),($p["auto_increment"]?" <i>".'Auto Increment'."</i>":""),(isset($p["default"])?" <span title='".'Default value'."'>[<b>".h($p["default"])."</b>]</span>":""),(support("comment")?"<td>".h($p["comment"]):""),"\n";}echo"</table>\n","</div>\n";}function
tableIndexesPrint($x){echo"<table cellspacing='0'>\n";foreach($x
as$C=>$w){ksort($w["columns"]);$jg=array();foreach($w["columns"]as$z=>$X)$jg[]="<i>".h($X)."</i>".($w["lengths"][$z]?"(".$w["lengths"][$z].")":"").($w["descs"][$z]?" DESC":"");echo"<tr title='".h($C)."'><th>$w[type]<td>".implode(", ",$jg)."\n";}echo"</table>\n";}function
selectColumnsPrint($L,$f){global$id,$od;print_fieldset("select",'Select',$L);$t=0;$L[""]=array();foreach($L
as$z=>$X){$X=$_GET["columns"][$z];$e=select_input(" name='columns[$t][col]'",$f,$X["col"],($z!==""?"selectFieldChange":"selectAddRow"));echo"<div>".($id||$od?"<select name='columns[$t][fun]'>".optionlist(array(-1=>"")+array_filter(array('Functions'=>$id,'Aggregation'=>$od)),$X["fun"])."</select>".on_help("getTarget(event).value && getTarget(event).value.replace(/ |\$/, '(') + ')'",1).script("qsl('select').onchange = function () { helpClose();".($z!==""?"":" qsl('select, input', this.parentNode).onchange();")." };","")."($e)":$e)."</div>\n";$t++;}echo"</div></fieldset>\n";}function
selectSearchPrint($Z,$f,$x){print_fieldset("search",'Search',$Z);foreach($x
as$t=>$w){if($w["type"]=="FULLTEXT"){echo"<div>(<i>".implode("</i>, <i>",array_map('h',$w["columns"]))."</i>) AGAINST"," <input type='search' name='fulltext[$t]' value='".h($_GET["fulltext"][$t])."'>",script("qsl('input').oninput = selectFieldChange;",""),checkbox("boolean[$t]",1,isset($_GET["boolean"][$t]),"BOOL"),"</div>\n";}}$ab="this.parentNode.firstChild.onchange();";foreach(array_merge((array)$_GET["where"],array(array()))as$t=>$X){if(!$X||("$X[col]$X[val]"!=""&&in_array($X["op"],$this->operators))){echo"<div>".select_input(" name='where[$t][col]'",$f,$X["col"],($X?"selectFieldChange":"selectAddRow"),"(".'anywhere'.")"),adminer_html_select("where[$t][op]",$this->operators,$X["op"],$ab),"<input type='search' name='where[$t][val]' value='".h($X["val"])."'>",script("mixin(qsl('input'), {oninput: function () { $ab }, onkeydown: selectSearchKeydown, onsearch: selectSearchSearch});",""),"</div>\n";}}echo"</div></fieldset>\n";}function
selectOrderPrint($xf,$f,$x){print_fieldset("sort",'Sort',$xf);$t=0;foreach((array)$_GET["order"]as$z=>$X){if($X!=""){echo"<div>".select_input(" name='order[$t]'",$f,$X,"selectFieldChange"),checkbox("desc[$t]",1,isset($_GET["desc"][$z]),'descending')."</div>\n";$t++;}}echo"<div>".select_input(" name='order[$t]'",$f,"","selectAddRow"),checkbox("desc[$t]",1,false,'descending')."</div>\n","</div></fieldset>\n";}function
selectLimitPrint($_){echo"<fieldset><legend>".'Limit'."</legend><div>";echo"<input type='number' name='limit' class='size' value='".h($_)."'>",script("qsl('input').oninput = selectFieldChange;",""),"</div></fieldset>\n";}function
selectLengthPrint($bi){if($bi!==null){echo"<fieldset><legend>".'Text length'."</legend><div>","<input type='number' name='text_length' class='size' value='".h($bi)."'>","</div></fieldset>\n";}}function
selectActionPrint($x){echo"<fieldset><legend>".'Action'."</legend><div>","<input type='submit' value='".'Select'."'>"," <span id='noindex' title='".'Full table scan'."'></span>","<script".nonce().">\n","var indexColumns = ";$f=array();foreach($x
as$w){$Ib=reset($w["columns"]);if($w["type"]!="FULLTEXT"&&$Ib)$f[$Ib]=1;}$f[""]=1;foreach($f
as$z=>$X)json_row($z);echo";\n","selectFieldChange.call(qs('#form')['select']);\n","</script>\n","</div></fieldset>\n";}function
selectCommandPrint(){return!information_schema(DB);}function
selectImportPrint(){return!information_schema(DB);}function
selectEmailPrint($pc,$f){}function
selectColumnsProcess($f,$x){global$id,$od;$L=array();$ld=array();foreach((array)$_GET["columns"]as$z=>$X){if($X["fun"]=="count"||($X["col"]!=""&&(!$X["fun"]||in_array($X["fun"],$id)||in_array($X["fun"],$od)))){$L[$z]=apply_sql_function($X["fun"],($X["col"]!=""?idf_escape($X["col"]):"*"));if(!in_array($X["fun"],$od))$ld[]=$L[$z];}}return
array($L,$ld);}function
selectSearchProcess($q,$x){global$g,$n;$I=array();foreach($x
as$t=>$w){if($w["type"]=="FULLTEXT"&&$_GET["fulltext"][$t]!="")$I[]="MATCH (".implode(", ",array_map('idf_escape',$w["columns"])).") AGAINST (".q($_GET["fulltext"][$t]).(isset($_GET["boolean"][$t])?" IN BOOLEAN MODE":"").")";}foreach((array)$_GET["where"]as$z=>$X){if("$X[col]$X[val]"!=""&&in_array($X["op"],$this->operators)){$fg="";$ub=" $X[op]";if(preg_match('~IN$~',$X["op"])){$Fd=process_length($X["val"]);$ub.=" ".($Fd!=""?$Fd:"(NULL)");}elseif($X["op"]=="SQL")$ub=" $X[val]";elseif($X["op"]=="LIKE %%")$ub=" LIKE ".$this->processInput($q[$X["col"]],"%$X[val]%");elseif($X["op"]=="ILIKE %%")$ub=" ILIKE ".$this->processInput($q[$X["col"]],"%$X[val]%");elseif($X["op"]=="FIND_IN_SET"){$fg="$X[op](".q($X["val"]).", ";$ub=")";}elseif(!preg_match('~NULL$~',$X["op"]))$ub.=" ".$this->processInput($q[$X["col"]],$X["val"]);if($X["col"]!="")$I[]=$fg.$n->convertSearch(idf_escape($X["col"]),$X,$q[$X["col"]]).$ub;else{$qb=array();foreach($q
as$C=>$p){if((preg_match('~^[-\d.'.(preg_match('~IN$~',$X["op"])?',':'').']+$~',$X["val"])||!preg_match('~'.number_type().'|bit~',$p["type"]))&&(!preg_match("~[\x80-\xFF]~",$X["val"])||preg_match('~char|text|enum|set~',$p["type"])))$qb[]=$fg.$n->convertSearch(idf_escape($C),$X,$p).$ub;}$I[]=($qb?"(".implode(" OR ",$qb).")":"1 = 0");}}}return$I;}function
selectOrderProcess($q,$x){$I=array();foreach((array)$_GET["order"]as$z=>$X){if($X!="")$I[]=(preg_match('~^((COUNT\(DISTINCT |[A-Z0-9_]+\()(`(?:[^`]|``)+`|"(?:[^"]|"")+")\)|COUNT\(\*\))$~',$X)?$X:idf_escape($X)).(isset($_GET["desc"][$z])?" DESC":"");}return$I;}function
selectLimitProcess(){return(isset($_GET["limit"])?$_GET["limit"]:"50");}function
selectLengthProcess(){return(isset($_GET["text_length"])?$_GET["text_length"]:"100");}function
selectEmailProcess($Z,$bd){return
false;}function
selectQueryBuild($L,$Z,$ld,$xf,$_,$E){return"";}function
messageQuery($G,$ci,$Lc=false){global$y,$n;restart_session();$wd=&get_session("queries");if(!$wd[$_GET["db"]])$wd[$_GET["db"]]=array();if(strlen($G)>1e6)$G=preg_replace('~[\x80-\xFF]+$~','',substr($G,0,1e6))."\n‚Ä¶";$wd[$_GET["db"]][]=array($G,time(),$ci);$yh="sql-".count($wd[$_GET["db"]]);$I="<a href='#$yh' class='toggle'>".'SQL command'."</a>\n";if(!$Lc&&($aj=$n->warnings())){$u="warnings-".count($wd[$_GET["db"]]);$I="<a href='#$u' class='toggle'>".'Warnings'."</a>, $I<div id='$u' class='hidden'>\n$aj</div>\n";}return" <span class='time'>".@date("H:i:s")."</span>"." $I<div id='$yh' class='hidden'><pre><code class='jush-$y'>".shorten_utf8($G,1000)."</code></pre>".($ci?" <span class='time'>($ci)</span>":'').(support("sql")?'<p><a href="'.h(str_replace("db=".urlencode(DB),"db=".urlencode($_GET["db"]),ME).'sql=&history='.(count($wd[$_GET["db"]])-1)).'">'.'Edit'.'</a>':'').'</div>';}function
editFunctions($p){global$kc;$I=($p["null"]?"NULL/":"");foreach($kc
as$z=>$id){if(!$z||(!isset($_GET["call"])&&(isset($_GET["select"])||where($_GET)))){foreach($id
as$Xf=>$X){if(!$Xf||preg_match("~$Xf~",$p["type"]))$I.="/$X";}if($z&&!preg_match('~set|blob|bytea|raw|file~',$p["type"]))$I.="/SQL";}}if($p["auto_increment"]&&!isset($_GET["select"])&&!where($_GET))$I='Auto Increment';return
explode("/",$I);}function
editInput($Q,$p,$Ja,$Y){if($p["type"]=="enum")return(isset($_GET["select"])?"<label><input type='radio'$Ja value='-1' checked><i>".'original'."</i></label> ":"").($p["null"]?"<label><input type='radio'$Ja value=''".($Y!==null||isset($_GET["select"])?"":" checked")."><i>NULL</i></label> ":"").enum_input("radio",$Ja,$p,$Y,0);return"";}function
editHint($Q,$p,$Y){return"";}function
processInput($p,$Y,$s=""){if($s=="SQL")return$Y;$C=$p["field"];$I=q($Y);if(preg_match('~^(now|getdate|uuid)$~',$s))$I="$s()";elseif(preg_match('~^current_(date|timestamp)$~',$s))$I=$s;elseif(preg_match('~^([+-]|\|\|)$~',$s))$I=idf_escape($C)." $s $I";elseif(preg_match('~^[+-] interval$~',$s))$I=idf_escape($C)." $s ".(preg_match("~^(\\d+|'[0-9.: -]') [A-Z_]+\$~i",$Y)?$Y:$I);elseif(preg_match('~^(addtime|subtime|concat)$~',$s))$I="$s(".idf_escape($C).", $I)";elseif(preg_match('~^(md5|sha1|password|encrypt)$~',$s))$I="$s($I)";return
unconvert_field($p,$I);}function
dumpOutput(){$I=array('text'=>'open','file'=>'save');if(function_exists('gzencode'))$I['gz']='gzip';return$I;}function
dumpFormat(){return
array('sql'=>'SQL','csv'=>'CSV,','csv;'=>'CSV;','tsv'=>'TSV');}function
dumpDatabase($m){}function
dumpTable($Q,$Gh,$Yd=0){if($_POST["format"]!="sql"){echo"\xef\xbb\xbf";if($Gh)dump_csv(array_keys(fields($Q)));}else{if($Yd==2){$q=array();foreach(fields($Q)as$C=>$p)$q[]=idf_escape($C)." $p[full_type]";$i="CREATE TABLE ".table($Q)." (".implode(", ",$q).")";}else$i=create_sql($Q,$_POST["auto_increment"],$Gh);set_utf8mb4($i);if($Gh&&$i){if($Gh=="DROP+CREATE"||$Yd==1)echo"DROP ".($Yd==2?"VIEW":"TABLE")." IF EXISTS ".table($Q).";\n";if($Yd==1)$i=remove_definer($i);echo"$i;\n\n";}}}function
dumpData($Q,$Gh,$G){global$g,$y;$De=($y=="sqlite"?0:1048576);if($Gh){if($_POST["format"]=="sql"){if($Gh=="TRUNCATE+INSERT")echo
truncate_sql($Q).";\n";$q=fields($Q);}$H=$g->query($G,1);if($H){$Rd="";$Xa="";$fe=array();$Ih="";$Oc=($Q!=''?'fetch_assoc':'fetch_row');while($J=$H->$Oc()){if(!$fe){$Si=array();foreach($J
as$X){$p=$H->fetch_field();$fe[]=$p->name;$z=idf_escape($p->name);$Si[]="$z = VALUES($z)";}$Ih=($Gh=="INSERT+UPDATE"?"\nON DUPLICATE KEY UPDATE ".implode(", ",$Si):"").";\n";}if($_POST["format"]!="sql"){if($Gh=="table"){dump_csv($fe);$Gh="INSERT";}dump_csv($J);}else{if(!$Rd)$Rd="INSERT INTO ".table($Q)." (".implode(", ",array_map('idf_escape',$fe)).") VALUES";foreach($J
as$z=>$X){$p=$q[$z];$J[$z]=($X!==null?unconvert_field($p,preg_match(number_type(),$p["type"])&&$X!=''&&!preg_match('~\[~',$p["full_type"])?$X:q(($X===false?0:$X))):"NULL");}$Vg=($De?"\n":" ")."(".implode(",\t",$J).")";if(!$Xa)$Xa=$Rd.$Vg;elseif(strlen($Xa)+4+strlen($Vg)+strlen($Ih)<$De)$Xa.=",$Vg";else{echo$Xa.$Ih;$Xa=$Rd.$Vg;}}}if($Xa)echo$Xa.$Ih;}elseif($_POST["format"]=="sql")echo"-- ".str_replace("\n"," ",$g->error)."\n";}}function
dumpFilename($Ad){return
friendly_url($Ad!=""?$Ad:(SERVER!=""?SERVER:"localhost"));}function
dumpHeaders($Ad,$Se=false){$Hf=$_POST["output"];$Gc=(preg_match('~sql~',$_POST["format"])?"sql":($Se?"tar":"csv"));header("Content-Type: ".($Hf=="gz"?"application/x-gzip":($Gc=="tar"?"application/x-tar":($Gc=="sql"||$Hf!="file"?"text/plain":"text/csv")."; charset=utf-8")));if($Hf=="gz")ob_start('ob_gzencode',1e6);return$Gc;}function
importServerPath(){return"adminer.sql";}function
homepage(){echo'<p class="links">'.($_GET["ns"]==""&&support("database")?'<a href="'.h(ME).'database=">'.'Alter database'."</a>\n":""),(support("scheme")?"<a href='".h(ME)."scheme='>".($_GET["ns"]!=""?'Alter schema':'Create schema')."</a>\n":""),($_GET["ns"]!==""?'<a href="'.h(ME).'schema=">'.'Database schema'."</a>\n":""),(support("privileges")?"<a href='".h(ME)."privileges='>".'Privileges'."</a>\n":"");return
true;}function
navigation($Re){global$ia,$y,$cc,$g;echo'<h1>
',$this->name(),' <span class="version">',$ia,'</span>
<a href="https://www.adminer.org/#download"',target_blank(),' id="version">',(version_compare($ia,$_COOKIE["adminer_version"])<0?h($_COOKIE["adminer_version"]):""),'</a>
</h1>
';if($Re=="auth"){$Uc=true;foreach((array)$_SESSION["pwds"]as$Ui=>$jh){foreach($jh
as$N=>$Pi){foreach($Pi
as$V=>$F){if($F!==null){if($Uc){echo"<ul id='logins'>".script("mixin(qs('#logins'), {onmouseover: menuOver, onmouseout: menuOut});");$Uc=false;}$Ob=$_SESSION["db"][$Ui][$N][$V];foreach(($Ob?array_keys($Ob):array(""))as$m)echo"<li><a href='".h(auth_url($Ui,$N,$V,$m))."'>($cc[$Ui]) ".h($V.($N!=""?"@".$this->serverName($N):"").($m!=""?" - $m":""))."</a>\n";}}}}}else{if($_GET["ns"]!==""&&!$Re&&DB!=""){$g->select_db(DB);$S=table_status('',true);}echo
script_src(preg_replace("~\\?.*~","",ME)."?file=jush.js&version=4.7.1");if(support("sql")){echo'<script',nonce(),'>
';if($S){$ue=array();foreach($S
as$Q=>$T)$ue[]=preg_quote($Q,'/');echo"var jushLinks = { $y: [ '".js_escape(ME).(support("table")?"table=":"select=")."\$&', /\\b(".implode("|",$ue).")\\b/g ] };\n";foreach(array("bac","bra","sqlite_quo","mssql_bra")as$X)echo"jushLinks.$X = jushLinks.$y;\n";}$ih=$g->server_info;echo'bodyLoad(\'',(is_object($g)?preg_replace('~^(\d\.?\d).*~s','\1',$ih):""),'\'',(preg_match('~MariaDB~',$ih)?", true":""),');
</script>
';}$this->databasesPrint($Re);if(DB==""||!$Re){echo"<p class='links'>".(support("sql")?"<a href='".h(ME)."sql='".bold(isset($_GET["sql"])&&!isset($_GET["import"])).">".'SQL command'."</a>\n<a href='".h(ME)."import='".bold(isset($_GET["import"])).">".'Import'."</a>\n":"")."";if(support("dump"))echo"<a href='".h(ME)."dump=".urlencode(isset($_GET["table"])?$_GET["table"]:$_GET["select"])."' id='dump'".bold(isset($_GET["dump"])).">".'Export'."</a>\n";}if($_GET["ns"]!==""&&!$Re&&DB!=""){echo'<a href="'.h(ME).'create="'.bold($_GET["create"]==="").">".'Create table'."</a>\n";if(!$S)echo"<p class='message'>".'No tables.'."\n";else$this->tablesPrint($S);}}}function
databasesPrint($Re){global$b,$g;$l=$this->databases();if($l&&!in_array(DB,$l))array_unshift($l,DB);echo'<form action="">
<p id="dbs">
';hidden_fields_get();$Mb=script("mixin(qsl('select'), {onmousedown: dbMouseDown, onchange: dbChange});");echo"<span title='".'database'."'>".'DB'."</span>: ".($l?"<select name='db'>".optionlist(array(""=>"")+$l,DB)."</select>$Mb":"<input name='db' value='".h(DB)."' autocapitalize='off'>\n"),"<input type='submit' value='".'Use'."'".($l?" class='hidden'":"").">\n";if($Re!="db"&&DB!=""&&$g->select_db(DB)){if(support("scheme")){echo"<br>".'Schema'.": <select name='ns'>".optionlist(array(""=>"")+$b->schemas(),$_GET["ns"])."</select>$Mb";if($_GET["ns"]!="")set_schema($_GET["ns"]);}}foreach(array("import","sql","schema","dump","privileges")as$X){if(isset($_GET[$X])){echo"<input type='hidden' name='$X' value=''>";break;}}echo"</p></form>\n";}function
tablesPrint($S){echo"<ul id='tables'>".script("mixin(qs('#tables'), {onmouseover: menuOver, onmouseout: menuOut});");foreach($S
as$Q=>$Ch){$C=$this->tableName($Ch);if($C!=""){echo'<li><a href="'.h(ME).'select='.urlencode($Q).'"'.bold($_GET["select"]==$Q||$_GET["edit"]==$Q,"select").">".'select'."</a> ",(support("table")||support("indexes")?'<a href="'.h(ME).'table='.urlencode($Q).'"'.bold(in_array($Q,array($_GET["table"],$_GET["create"],$_GET["indexes"],$_GET["foreign"],$_GET["trigger"])),(is_view($Ch)?"view":"structure"))." title='".'Show structure'."'>$C</a>":"<span>$C</span>")."\n";}}echo"</ul>\n";}}$b=(function_exists('adminer_object')?adminer_object():new
Adminer);if($b->operators===null)$b->operators=$sf;function
page_header($fi,$o="",$Wa=array(),$gi=""){global$ca,$ia,$b,$cc,$y;page_headers();if(is_ajax()&&$o){page_messages($o);exit;}$hi=$fi.($gi!=""?": $gi":"");$ii=strip_tags($hi.(SERVER!=""&&SERVER!="localhost"?h(" - ".SERVER):"")." - ".$b->name());echo'<!DOCTYPE html>
<html lang="en" dir="ltr">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta name="robots" content="noindex">
<title>',$ii,'</title>
<link rel="stylesheet" type="text/css" href="',h(preg_replace("~\\?.*~","",ME)."?file=default.css&version=4.7.1"),'">
',script_src(preg_replace("~\\?.*~","",ME)."?file=functions.js&version=4.7.1");if($b->head()){echo'<link rel="shortcut icon" type="image/x-icon" href="',h(preg_replace("~\\?.*~","",ME)."?file=favicon.ico&version=4.7.1"),'">
<link rel="apple-touch-icon" href="',h(preg_replace("~\\?.*~","",ME)."?file=favicon.ico&version=4.7.1"),'">
';foreach($b->css()as$Gb){echo'<link rel="stylesheet" type="text/css" href="',h($Gb),'">
';}}echo'
<body class="ltr nojs">
';$Sc=get_temp_dir()."/adminer.version";if(!$_COOKIE["adminer_version"]&&function_exists('openssl_verify')&&file_exists($Sc)&&filemtime($Sc)+86400>time()){$Vi=unserialize(file_get_contents($Sc));$qg="-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAwqWOVuF5uw7/+Z70djoK
RlHIZFZPO0uYRezq90+7Amk+FDNd7KkL5eDve+vHRJBLAszF/7XKXe11xwliIsFs
DFWQlsABVZB3oisKCBEuI71J4kPH8dKGEWR9jDHFw3cWmoH3PmqImX6FISWbG3B8
h7FIx3jEaw5ckVPVTeo5JRm/1DZzJxjyDenXvBQ/6o9DgZKeNDgxwKzH+sw9/YCO
jHnq1cFpOIISzARlrHMa/43YfeNRAm/tsBXjSxembBPo7aQZLAWHmaj5+K19H10B
nCpz9Y++cipkVEiKRGih4ZEvjoFysEOdRLj6WiD/uUNky4xGeA6LaJqh5XpkFkcQ
fQIDAQAB
-----END PUBLIC KEY-----
";if(openssl_verify($Vi["version"],base64_decode($Vi["signature"]),$qg)==1)$_COOKIE["adminer_version"]=$Vi["version"];}echo'<script',nonce(),'>
mixin(document.body, {onkeydown: bodyKeydown, onclick: bodyClick',(isset($_COOKIE["adminer_version"])?"":", onload: partial(verifyVersion, '$ia', '".js_escape(ME)."', '".get_token()."')");?>});
document.body.className = document.body.className.replace(/ nojs/, ' js');
var offlineMessage = '<?php echo
js_escape('You are offline.'),'\';
var thousandsSeparator = \'',js_escape(','),'\';
</script>

<div id="help" class="jush-',$y,' jsonly hidden"></div>
',script("mixin(qs('#help'), {onmouseover: function () { helpOpen = 1; }, onmouseout: helpMouseout});"),'
<div id="content">
';if($Wa!==null){$A=substr(preg_replace('~\b(username|db|ns)=[^&]*&~','',ME),0,-1);echo'<p id="breadcrumb"><a href="'.h($A?$A:".").'">'.$cc[DRIVER].'</a> &raquo; ';$A=substr(preg_replace('~\b(db|ns)=[^&]*&~','',ME),0,-1);$N=$b->serverName(SERVER);$N=($N!=""?$N:'Server');if($Wa===false)echo"$N\n";else{echo"<a href='".($A?h($A):".")."' accesskey='1' title='Alt+Shift+1'>$N</a> &raquo; ";if($_GET["ns"]!=""||(DB!=""&&is_array($Wa)))echo'<a href="'.h($A."&db=".urlencode(DB).(support("scheme")?"&ns=":"")).'">'.h(DB).'</a> &raquo; ';if(is_array($Wa)){if($_GET["ns"]!="")echo'<a href="'.h(substr(ME,0,-1)).'">'.h($_GET["ns"]).'</a> &raquo; ';foreach($Wa
as$z=>$X){$Ub=(is_array($X)?$X[1]:h($X));if($Ub!="")echo"<a href='".h(ME."$z=").urlencode(is_array($X)?$X[0]:$X)."'>$Ub</a> &raquo; ";}}echo"$fi\n";}}echo"<h2>$hi</h2>\n","<div id='ajaxstatus' class='jsonly hidden'></div>\n";restart_session();page_messages($o);$l=&get_session("dbs");if(DB!=""&&$l&&!in_array(DB,$l,true))$l=null;stop_session();define("PAGE_HEADER",1);}function
page_headers(){global$b;header("Content-Type: text/html; charset=utf-8");header("Cache-Control: no-cache");header("X-Frame-Options: deny");header("X-XSS-Protection: 0");header("X-Content-Type-Options: nosniff");header("Referrer-Policy: origin-when-cross-origin");foreach($b->csp()as$Fb){$ud=array();foreach($Fb
as$z=>$X)$ud[]="$z $X";header("Content-Security-Policy: ".implode("; ",$ud));}$b->headers();}function
csp(){return
array(array("script-src"=>"'self' 'unsafe-inline' 'nonce-".get_nonce()."' 'strict-dynamic'","connect-src"=>"'self'","frame-src"=>"https://www.adminer.org","object-src"=>"'none'","base-uri"=>"'none'","form-action"=>"'self'",),);}function
get_nonce(){static$bf;if(!$bf)$bf=base64_encode(rand_string());return$bf;}function
page_messages($o){$Ii=preg_replace('~^[^?]*~','',$_SERVER["REQUEST_URI"]);$Ne=$_SESSION["messages"][$Ii];if($Ne){echo"<div class='message'>".implode("</div>\n<div class='message'>",$Ne)."</div>".script("messagesPrint();");unset($_SESSION["messages"][$Ii]);}if($o)echo"<div class='error'>$o</div>\n";}function
page_footer($Re=""){global$b,$mi;echo'</div>

';if($Re!="auth"){echo'<form action="" method="post">
<p class="logout">
<input type="submit" name="logout" value="Logout" id="logout">
<input type="hidden" name="token" value="',$mi,'">
</p>
</form>
';}echo'<div id="menu">
';$b->navigation($Re);echo'</div>
',script("setupSubmitHighlight(document);");}function
int32($Ue){while($Ue>=2147483648)$Ue-=4294967296;while($Ue<=-2147483649)$Ue+=4294967296;return(int)$Ue;}function
long2str($W,$Zi){$Vg='';foreach($W
as$X)$Vg.=pack('V',$X);if($Zi)return
substr($Vg,0,end($W));return$Vg;}function
str2long($Vg,$Zi){$W=array_values(unpack('V*',str_pad($Vg,4*ceil(strlen($Vg)/4),"\0")));if($Zi)$W[]=strlen($Vg);return$W;}function
xxtea_mx($mj,$lj,$Jh,$be){return
int32((($mj>>5&0x7FFFFFF)^$lj<<2)+(($lj>>3&0x1FFFFFFF)^$mj<<4))^int32(($Jh^$lj)+($be^$mj));}function
encrypt_string($Eh,$z){if($Eh=="")return"";$z=array_values(unpack("V*",pack("H*",md5($z))));$W=str2long($Eh,true);$Ue=count($W)-1;$mj=$W[$Ue];$lj=$W[0];$rg=floor(6+52/($Ue+1));$Jh=0;while($rg-->0){$Jh=int32($Jh+0x9E3779B9);$jc=$Jh>>2&3;for($If=0;$If<$Ue;$If++){$lj=$W[$If+1];$Te=xxtea_mx($mj,$lj,$Jh,$z[$If&3^$jc]);$mj=int32($W[$If]+$Te);$W[$If]=$mj;}$lj=$W[0];$Te=xxtea_mx($mj,$lj,$Jh,$z[$If&3^$jc]);$mj=int32($W[$Ue]+$Te);$W[$Ue]=$mj;}return
long2str($W,false);}function
decrypt_string($Eh,$z){if($Eh=="")return"";if(!$z)return
false;$z=array_values(unpack("V*",pack("H*",md5($z))));$W=str2long($Eh,false);$Ue=count($W)-1;$mj=$W[$Ue];$lj=$W[0];$rg=floor(6+52/($Ue+1));$Jh=int32($rg*0x9E3779B9);while($Jh){$jc=$Jh>>2&3;for($If=$Ue;$If>0;$If--){$mj=$W[$If-1];$Te=xxtea_mx($mj,$lj,$Jh,$z[$If&3^$jc]);$lj=int32($W[$If]-$Te);$W[$If]=$lj;}$mj=$W[$Ue];$Te=xxtea_mx($mj,$lj,$Jh,$z[$If&3^$jc]);$lj=int32($W[0]-$Te);$W[0]=$lj;$Jh=int32($Jh-0x9E3779B9);}return
long2str($W,true);}$g='';$td=$_SESSION["token"];if(!$td)$_SESSION["token"]=rand(1,1e6);$mi=get_token();$Yf=array();if($_COOKIE["adminer_permanent"]){foreach(explode(" ",$_COOKIE["adminer_permanent"])as$X){list($z)=explode(":",$X);$Yf[$z]=$X;}}function
add_invalid_login(){global$b;$gd=file_open_lock(get_temp_dir()."/adminer.invalid");if(!$gd)return;$Ud=unserialize(stream_get_contents($gd));$ci=time();if($Ud){foreach($Ud
as$Vd=>$X){if($X[0]<$ci)unset($Ud[$Vd]);}}$Td=&$Ud[$b->bruteForceKey()];if(!$Td)$Td=array($ci+30*60,0);$Td[1]++;file_write_unlock($gd,serialize($Ud));}function
check_invalid_login(){global$b;$Ud=unserialize(@file_get_contents(get_temp_dir()."/adminer.invalid"));$Td=$Ud[$b->bruteForceKey()];$af=($Td[1]>29?$Td[0]-time():0);if($af>0)auth_error(lang(array('Too many unsuccessful logins, try again in %d minute.','Too many unsuccessful logins, try again in %d minutes.'),ceil($af/60)));}$Ka=$_POST["auth"];if($Ka){session_regenerate_id();$Ui=$Ka["driver"];$N=$Ka["server"];$V=$Ka["username"];$F=(string)$Ka["password"];$m=$Ka["db"];set_password($Ui,$N,$V,$F);$_SESSION["db"][$Ui][$N][$V][$m]=true;if($Ka["permanent"]){$z=base64_encode($Ui)."-".base64_encode($N)."-".base64_encode($V)."-".base64_encode($m);$kg=$b->permanentLogin(true);$Yf[$z]="$z:".base64_encode($kg?encrypt_string($F,$kg):"");cookie("adminer_permanent",implode(" ",$Yf));}if(count($_POST)==1||DRIVER!=$Ui||SERVER!=$N||$_GET["username"]!==$V||DB!=$m)redirect(auth_url($Ui,$N,$V,$m));}elseif($_POST["logout"]){if($td&&!verify_token()){page_header('Logout','Invalid CSRF token. Send the form again.');page_footer("db");exit;}else{foreach(array("pwds","db","dbs","queries")as$z)set_session($z,null);unset_permanent();redirect(substr(preg_replace('~\b(username|db|ns)=[^&]*&~','',ME),0,-1),'Logout successful.'.' '.'Thanks for using Adminer, consider <a href="https://www.adminer.org/en/donation/">donating</a>.');}}elseif($Yf&&!$_SESSION["pwds"]){session_regenerate_id();$kg=$b->permanentLogin();foreach($Yf
as$z=>$X){list(,$ib)=explode(":",$X);list($Ui,$N,$V,$m)=array_map('base64_decode',explode("-",$z));set_password($Ui,$N,$V,decrypt_string(base64_decode($ib),$kg));$_SESSION["db"][$Ui][$N][$V][$m]=true;}}function
unset_permanent(){global$Yf;foreach($Yf
as$z=>$X){list($Ui,$N,$V,$m)=array_map('base64_decode',explode("-",$z));if($Ui==DRIVER&&$N==SERVER&&$V==$_GET["username"]&&$m==DB)unset($Yf[$z]);}cookie("adminer_permanent",implode(" ",$Yf));}function
auth_error($o){global$b,$td;$kh=session_name();if(isset($_GET["username"])){header("HTTP/1.1 403 Forbidden");if(($_COOKIE[$kh]||$_GET[$kh])&&!$td)$o='Session expired, please login again.';else{restart_session();add_invalid_login();$F=get_password();if($F!==null){if($F===false)$o.='<br>'.sprintf('Master password expired. <a href="https://www.adminer.org/en/extension/"%s>Implement</a> %s method to make it permanent.',target_blank(),'<code>permanentLogin()</code>');set_password(DRIVER,SERVER,$_GET["username"],null);}unset_permanent();}}if(!$_COOKIE[$kh]&&$_GET[$kh]&&ini_bool("session.use_only_cookies"))$o='Session support must be enabled.';$Lf=session_get_cookie_params();cookie("adminer_key",($_COOKIE["adminer_key"]?$_COOKIE["adminer_key"]:rand_string()),$Lf["lifetime"]);page_header('Login',$o,null);echo"<form action='' method='post'>\n","<div>";if(hidden_fields($_POST,array("auth")))echo"<p class='message'>".'The action will be performed after successful login with the same credentials.'."\n";echo"</div>\n";$b->loginForm();echo"</form>\n";page_footer("auth");exit;}if(isset($_GET["username"])&&!class_exists("Min_DB")){unset($_SESSION["pwds"][DRIVER]);unset_permanent();page_header('No extension',sprintf('None of the supported PHP extensions (%s) are available.',implode(", ",$eg)),false);page_footer("auth");exit;}stop_session(true);if(isset($_GET["username"])){list($zd,$ag)=explode(":",SERVER,2);if(is_numeric($ag)&&$ag<1024)auth_error('Connecting to privileged ports is not allowed.');check_invalid_login();$g=connect();$n=new
Min_Driver($g);}$we=null;if(!is_object($g)||($we=$b->login($_GET["username"],get_password()))!==true){$o=(is_string($g)?h($g):(is_string($we)?$we:'Invalid credentials.'));auth_error($o.(preg_match('~^ | $~',get_password())?'<br>'.'There is a space in the input password which might be the cause.':''));}if($Ka&&$_POST["token"])$_POST["token"]=$mi;$o='';if($_POST){if(!verify_token()){$Od="max_input_vars";$He=ini_get($Od);if(extension_loaded("suhosin")){foreach(array("suhosin.request.max_vars","suhosin.post.max_vars")as$z){$X=ini_get($z);if($X&&(!$He||$X<$He)){$Od=$z;$He=$X;}}}$o=(!$_POST["token"]&&$He?sprintf('Maximum number of allowed fields exceeded. Please increase %s.',"'$Od'"):'Invalid CSRF token. Send the form again.'.' '.'If you did not send this request from Adminer then close this page.');}}elseif($_SERVER["REQUEST_METHOD"]=="POST"){$o=sprintf('Too big POST data. Reduce the data or increase the %s configuration directive.',"'post_max_size'");if(isset($_GET["sql"]))$o.=' '.'You can upload a big SQL file via FTP and import it from server.';}function
select($H,$h=null,$_f=array(),$_=0){global$y;$ue=array();$x=array();$f=array();$Ta=array();$U=array();$I=array();odd('');for($t=0;(!$_||$t<$_)&&($J=$H->fetch_row());$t++){if(!$t){echo"<div class='scrollable'>\n","<table cellspacing='0' class='nowrap'>\n","<thead><tr>";for($ae=0;$ae<count($J);$ae++){$p=$H->fetch_field();$C=$p->name;$zf=$p->orgtable;$yf=$p->orgname;$I[$p->table]=$zf;if($_f&&$y=="sql")$ue[$ae]=($C=="table"?"table=":($C=="possible_keys"?"indexes=":null));elseif($zf!=""){if(!isset($x[$zf])){$x[$zf]=array();foreach(indexes($zf,$h)as$w){if($w["type"]=="PRIMARY"){$x[$zf]=array_flip($w["columns"]);break;}}$f[$zf]=$x[$zf];}if(isset($f[$zf][$yf])){unset($f[$zf][$yf]);$x[$zf][$yf]=$ae;$ue[$ae]=$zf;}}if($p->charsetnr==63)$Ta[$ae]=true;$U[$ae]=$p->type;echo"<th".($zf!=""||$p->name!=$yf?" title='".h(($zf!=""?"$zf.":"").$yf)."'":"").">".h($C).($_f?doc_link(array('sql'=>"explain-output.html#explain_".strtolower($C),'mariadb'=>"explain/#the-columns-in-explain-select",)):"");}echo"</thead>\n";}echo"<tr".odd().">";foreach($J
as$z=>$X){if($X===null)$X="<i>NULL</i>";elseif($Ta[$z]&&!is_utf8($X))$X="<i>".lang(array('%d byte','%d bytes'),strlen($X))."</i>";else{$X=h($X);if($U[$z]==254)$X="<code>$X</code>";}if(isset($ue[$z])&&!$f[$ue[$z]]){if($_f&&$y=="sql"){$Q=$J[array_search("table=",$ue)];$A=$ue[$z].urlencode($_f[$Q]!=""?$_f[$Q]:$Q);}else{$A="edit=".urlencode($ue[$z]);foreach($x[$ue[$z]]as$mb=>$ae)$A.="&where".urlencode("[".bracket_escape($mb)."]")."=".urlencode($J[$ae]);}$X="<a href='".h(ME.$A)."'>$X</a>";}echo"<td>$X";}}echo($t?"</table>\n</div>":"<p class='message'>".'No rows.')."\n";return$I;}function
referencable_primary($eh){$I=array();foreach(table_status('',true)as$Nh=>$Q){if($Nh!=$eh&&fk_support($Q)){foreach(fields($Nh)as$p){if($p["primary"]){if($I[$Nh]){unset($I[$Nh]);break;}$I[$Nh]=$p;}}}}return$I;}function
adminer_settings(){parse_str($_COOKIE["adminer_settings"],$mh);return$mh;}function
adminer_setting($z){$mh=adminer_settings();return$mh[$z];}function
set_adminer_settings($mh){return
cookie("adminer_settings",http_build_query($mh+adminer_settings()));}function
textarea($C,$Y,$K=10,$qb=80){global$y;echo"<textarea name='$C' rows='$K' cols='$qb' class='sqlarea jush-$y' spellcheck='false' wrap='off'>";if(is_array($Y)){foreach($Y
as$X)echo
h($X[0])."\n\n\n";}else
echo
h($Y);echo"</textarea>";}function
edit_type($z,$p,$ob,$cd=array(),$Jc=array()){global$Fh,$U,$Gi,$nf;$T=$p["type"];echo'<td><select name="',h($z),'[type]" class="type" aria-labelledby="label-type">';if($T&&!isset($U[$T])&&!isset($cd[$T])&&!in_array($T,$Jc))$Jc[]=$T;if($cd)$Fh['Foreign keys']=$cd;echo
optionlist(array_merge($Jc,$Fh),$T),'</select>
',on_help("getTarget(event).value",1),script("mixin(qsl('select'), {onfocus: function () { lastType = selectValue(this); }, onchange: editingTypeChange});",""),'<td><input name="',h($z),'[length]" value="',h($p["length"]),'" size="3"',(!$p["length"]&&preg_match('~var(char|binary)$~',$T)?" class='required'":"");echo' aria-labelledby="label-length">',script("mixin(qsl('input'), {onfocus: editingLengthFocus, oninput: editingLengthChange});",""),'<td class="options">',"<select name='".h($z)."[collation]'".(preg_match('~(char|text|enum|set)$~',$T)?"":" class='hidden'").'><option value="">('.'collation'.')'.optionlist($ob,$p["collation"]).'</select>',($Gi?"<select name='".h($z)."[unsigned]'".(!$T||preg_match(number_type(),$T)?"":" class='hidden'").'><option>'.optionlist($Gi,$p["unsigned"]).'</select>':''),(isset($p['on_update'])?"<select name='".h($z)."[on_update]'".(preg_match('~timestamp|datetime~',$T)?"":" class='hidden'").'>'.optionlist(array(""=>"(".'ON UPDATE'.")","CURRENT_TIMESTAMP"),(preg_match('~^CURRENT_TIMESTAMP~i',$p["on_update"])?"CURRENT_TIMESTAMP":$p["on_update"])).'</select>':''),($cd?"<select name='".h($z)."[on_delete]'".(preg_match("~`~",$T)?"":" class='hidden'")."><option value=''>(".'ON DELETE'.")".optionlist(explode("|",$nf),$p["on_delete"])."</select> ":" ");}function
process_length($re){global$uc;return(preg_match("~^\\s*\\(?\\s*$uc(?:\\s*,\\s*$uc)*+\\s*\\)?\\s*\$~",$re)&&preg_match_all("~$uc~",$re,$Be)?"(".implode(",",$Be[0]).")":preg_replace('~^[0-9].*~','(\0)',preg_replace('~[^-0-9,+()[\]]~','',$re)));}function
process_type($p,$nb="COLLATE"){global$Gi;return" $p[type]".process_length($p["length"]).(preg_match(number_type(),$p["type"])&&in_array($p["unsigned"],$Gi)?" $p[unsigned]":"").(preg_match('~char|text|enum|set~',$p["type"])&&$p["collation"]?" $nb ".q($p["collation"]):"");}function
process_field($p,$zi){return
array(idf_escape(trim($p["field"])),process_type($zi),($p["null"]?" NULL":" NOT NULL"),default_value($p),(preg_match('~timestamp|datetime~',$p["type"])&&$p["on_update"]?" ON UPDATE $p[on_update]":""),(support("comment")&&$p["comment"]!=""?" COMMENT ".q($p["comment"]):""),($p["auto_increment"]?auto_increment():null),);}function
default_value($p){$Qb=$p["default"];return($Qb===null?"":" DEFAULT ".(preg_match('~char|binary|text|enum|set~',$p["type"])||preg_match('~^(?![a-z])~i',$Qb)?q($Qb):$Qb));}function
type_class($T){foreach(array('char'=>'text','date'=>'time|year','binary'=>'blob','enum'=>'set',)as$z=>$X){if(preg_match("~$z|$X~",$T))return" class='$z'";}}function
edit_fields($q,$ob,$T="TABLE",$cd=array()){global$Pd;$q=array_values($q);echo'<thead><tr>
';if($T=="PROCEDURE"){echo'<td>';}echo'<th id="label-name">',($T=="TABLE"?'Column name':'Parameter name'),'<td id="label-type">Type<textarea id="enum-edit" rows="4" cols="12" wrap="off" style="display: none;"></textarea>',script("qs('#enum-edit').onblur = editingLengthBlur;"),'<td id="label-length">Length
<td>','Options';if($T=="TABLE"){echo'<td id="label-null">NULL
<td><input type="radio" name="auto_increment_col" value=""><acronym id="label-ai" title="Auto Increment">AI</acronym>',doc_link(array('sql'=>"example-auto-increment.html",'mariadb'=>"auto_increment/",'sqlite'=>"autoinc.html",'pgsql'=>"datatype.html#DATATYPE-SERIAL",'mssql'=>"ms186775.aspx",)),'<td id="label-default">Default value
',(support("comment")?"<td id='label-comment'>".'Comment':"");}echo'<td>',"<input type='image' class='icon' name='add[".(support("move_col")?0:count($q))."]' src='".h(preg_replace("~\\?.*~","",ME)."?file=plus.gif&version=4.7.1")."' alt='+' title='".'Add next'."'>".script("row_count = ".count($q).";"),'</thead>
<tbody>
',script("mixin(qsl('tbody'), {onclick: editingClick, onkeydown: editingKeydown, oninput: editingInput});");foreach($q
as$t=>$p){$t++;$Af=$p[($_POST?"orig":"field")];$Yb=(isset($_POST["add"][$t-1])||(isset($p["field"])&&!$_POST["drop_col"][$t]))&&(support("drop_col")||$Af=="");echo'<tr',($Yb?"":" style='display: none;'"),'>
',($T=="PROCEDURE"?"<td>".adminer_html_select("fields[$t][inout]",explode("|",$Pd),$p["inout"]):""),'<th>';if($Yb){echo'<input name="fields[',$t,'][field]" value="',h($p["field"]),'" data-maxlength="64" autocapitalize="off" aria-labelledby="label-name">',script("qsl('input').oninput = function () { editingNameChange.call(this);".($p["field"]!=""||count($q)>1?"":" editingAddRow.call(this);")." };","");}echo'<input type="hidden" name="fields[',$t,'][orig]" value="',h($Af),'">
';edit_type("fields[$t]",$p,$ob,$cd);if($T=="TABLE"){echo'<td>',checkbox("fields[$t][null]",1,$p["null"],"","","block","label-null"),'<td><label class="block"><input type="radio" name="auto_increment_col" value="',$t,'"';if($p["auto_increment"]){echo' checked';}echo' aria-labelledby="label-ai"></label><td>',checkbox("fields[$t][has_default]",1,$p["has_default"],"","","","label-default"),'<input name="fields[',$t,'][default]" value="',h($p["default"]),'" aria-labelledby="label-default">',(support("comment")?"<td><input name='fields[$t][comment]' value='".h($p["comment"])."' data-maxlength='".(min_version(5.5)?1024:255)."' aria-labelledby='label-comment'>":"");}echo"<td>",(support("move_col")?"<input type='image' class='icon' name='add[$t]' src='".h(preg_replace("~\\?.*~","",ME)."?file=plus.gif&version=4.7.1")."' alt='+' title='".'Add next'."'> "."<input type='image' class='icon' name='up[$t]' src='".h(preg_replace("~\\?.*~","",ME)."?file=up.gif&version=4.7.1")."' alt='‚Üë' title='".'Move up'."'> "."<input type='image' class='icon' name='down[$t]' src='".h(preg_replace("~\\?.*~","",ME)."?file=down.gif&version=4.7.1")."' alt='‚Üì' title='".'Move down'."'> ":""),($Af==""||support("drop_col")?"<input type='image' class='icon' name='drop_col[$t]' src='".h(preg_replace("~\\?.*~","",ME)."?file=cross.gif&version=4.7.1")."' alt='x' title='".'Remove'."'>":"");}}function
process_fields(&$q){$D=0;if($_POST["up"]){$le=0;foreach($q
as$z=>$p){if(key($_POST["up"])==$z){unset($q[$z]);array_splice($q,$le,0,array($p));break;}if(isset($p["field"]))$le=$D;$D++;}}elseif($_POST["down"]){$ed=false;foreach($q
as$z=>$p){if(isset($p["field"])&&$ed){unset($q[key($_POST["down"])]);array_splice($q,$D,0,array($ed));break;}if(key($_POST["down"])==$z)$ed=$p;$D++;}}elseif($_POST["add"]){$q=array_values($q);array_splice($q,key($_POST["add"]),0,array(array()));}elseif(!$_POST["drop_col"])return
false;return
true;}function
normalize_enum($B){return"'".str_replace("'","''",addcslashes(stripcslashes(str_replace($B[0][0].$B[0][0],$B[0][0],substr($B[0],1,-1))),'\\'))."'";}function
grant($jd,$mg,$f,$mf){if(!$mg)return
true;if($mg==array("ALL PRIVILEGES","GRANT OPTION"))return($jd=="GRANT"?queries("$jd ALL PRIVILEGES$mf WITH GRANT OPTION"):queries("$jd ALL PRIVILEGES$mf")&&queries("$jd GRANT OPTION$mf"));return
queries("$jd ".preg_replace('~(GRANT OPTION)\([^)]*\)~','\1',implode("$f, ",$mg).$f).$mf);}function
drop_create($dc,$i,$ec,$Zh,$gc,$ve,$Me,$Ke,$Le,$jf,$Xe){if($_POST["drop"])query_redirect($dc,$ve,$Me);elseif($jf=="")query_redirect($i,$ve,$Le);elseif($jf!=$Xe){$Eb=queries($i);queries_redirect($ve,$Ke,$Eb&&queries($dc));if($Eb)queries($ec);}else
queries_redirect($ve,$Ke,queries($Zh)&&queries($gc)&&queries($dc)&&queries($i));}function
create_trigger($mf,$J){global$y;$ei=" $J[Timing] $J[Event]".($J["Event"]=="UPDATE OF"?" ".idf_escape($J["Of"]):"");return"CREATE TRIGGER ".idf_escape($J["Trigger"]).($y=="mssql"?$mf.$ei:$ei.$mf).rtrim(" $J[Type]\n$J[Statement]",";").";";}function
create_routine($Rg,$J){global$Pd,$y;$O=array();$q=(array)$J["fields"];ksort($q);foreach($q
as$p){if($p["field"]!="")$O[]=(preg_match("~^($Pd)\$~",$p["inout"])?"$p[inout] ":"").idf_escape($p["field"]).process_type($p,"CHARACTER SET");}$Rb=rtrim("\n$J[definition]",";");return"CREATE $Rg ".idf_escape(trim($J["name"]))." (".implode(", ",$O).")".(isset($_GET["function"])?" RETURNS".process_type($J["returns"],"CHARACTER SET"):"").($J["language"]?" LANGUAGE $J[language]":"").($y=="pgsql"?" AS ".q($Rb):"$Rb;");}function
remove_definer($G){return
preg_replace('~^([A-Z =]+) DEFINER=`'.preg_replace('~@(.*)~','`@`(%|\1)',logged_user()).'`~','\1',$G);}function
format_foreign_key($r){global$nf;return" FOREIGN KEY (".implode(", ",array_map('idf_escape',$r["source"])).") REFERENCES ".table($r["table"])." (".implode(", ",array_map('idf_escape',$r["target"])).")".(preg_match("~^($nf)\$~",$r["on_delete"])?" ON DELETE $r[on_delete]":"").(preg_match("~^($nf)\$~",$r["on_update"])?" ON UPDATE $r[on_update]":"");}function
tar_file($Sc,$ji){$I=pack("a100a8a8a8a12a12",$Sc,644,0,0,decoct($ji->size),decoct(time()));$gb=8*32;for($t=0;$t<strlen($I);$t++)$gb+=ord($I[$t]);$I.=sprintf("%06o",$gb)."\0 ";echo$I,str_repeat("\0",512-strlen($I));$ji->send();echo
str_repeat("\0",511-($ji->size+511)%512);}function
ini_bytes($Od){$X=ini_get($Od);switch(strtolower(substr($X,-1))){case'g':$X*=1024;case'm':$X*=1024;case'k':$X*=1024;}return$X;}function
doc_link($Wf,$ai="<sup>?</sup>"){global$y,$g;$ih=$g->server_info;$Vi=preg_replace('~^(\d\.?\d).*~s','\1',$ih);$Li=array('sql'=>"https://dev.mysql.com/doc/refman/$Vi/en/",'sqlite'=>"https://www.sqlite.org/",'pgsql'=>"https://www.postgresql.org/docs/$Vi/static/",'mssql'=>"https://msdn.microsoft.com/library/",'oracle'=>"https://download.oracle.com/docs/cd/B19306_01/server.102/b14200/",);if(preg_match('~MariaDB~',$ih)){$Li['sql']="https://mariadb.com/kb/en/library/";$Wf['sql']=(isset($Wf['mariadb'])?$Wf['mariadb']:str_replace(".html","/",$Wf['sql']));}return($Wf[$y]?"<a href='$Li[$y]$Wf[$y]'".target_blank().">$ai</a>":"");}function
ob_gzencode($P){return
gzencode($P);}function
db_size($m){global$g;if(!$g->select_db($m))return"?";$I=0;foreach(table_status()as$R)$I+=$R["Data_length"]+$R["Index_length"];return
format_number($I);}function
set_utf8mb4($i){global$g;static$O=false;if(!$O&&preg_match('~\butf8mb4~i',$i)){$O=true;echo"SET NAMES ".charset($g).";\n\n";}}function
connect_error(){global$b,$g,$mi,$o,$cc;if(DB!=""){header("HTTP/1.1 404 Not Found");page_header('Database'.": ".h(DB),'Invalid database.',true);}else{if($_POST["db"]&&!$o)queries_redirect(substr(ME,0,-1),'Databases have been dropped.',drop_databases($_POST["db"]));page_header('Select database',$o,false);echo"<p class='links'>\n";foreach(array('database'=>'Create database','privileges'=>'Privileges','processlist'=>'Process list','variables'=>'Variables','status'=>'Status',)as$z=>$X){if(support($z))echo"<a href='".h(ME)."$z='>$X</a>\n";}echo"<p>".sprintf('%s version: %s through PHP extension %s',$cc[DRIVER],"<b>".h($g->server_info)."</b>","<b>$g->extension</b>")."\n","<p>".sprintf('Logged as: %s',"<b>".h(logged_user())."</b>")."\n";$l=$b->databases();if($l){$Yg=support("scheme");$ob=collations();echo"<form action='' method='post'>\n","<table cellspacing='0' class='checkable'>\n",script("mixin(qsl('table'), {onclick: tableClick, ondblclick: partialArg(tableClick, true)});"),"<thead><tr>".(support("database")?"<td>":"")."<th>".'Database'." - <a href='".h(ME)."refresh=1'>".'Refresh'."</a>"."<td>".'Collation'."<td>".'Tables'."<td>".'Size'." - <a href='".h(ME)."dbsize=1'>".'Compute'."</a>".script("qsl('a').onclick = partial(ajaxSetHtml, '".js_escape(ME)."script=connect');","")."</thead>\n";$l=($_GET["dbsize"]?count_tables($l):array_flip($l));foreach($l
as$m=>$S){$Qg=h(ME)."db=".urlencode($m);$u=h("Db-".$m);echo"<tr".odd().">".(support("database")?"<td>".checkbox("db[]",$m,in_array($m,(array)$_POST["db"]),"","","",$u):""),"<th><a href='$Qg' id='$u'>".h($m)."</a>";$d=h(db_collation($m,$ob));echo"<td>".(support("database")?"<a href='$Qg".($Yg?"&amp;ns=":"")."&amp;database=' title='".'Alter database'."'>$d</a>":$d),"<td align='right'><a href='$Qg&amp;schema=' id='tables-".h($m)."' title='".'Database schema'."'>".($_GET["dbsize"]?$S:"?")."</a>","<td align='right' id='size-".h($m)."'>".($_GET["dbsize"]?db_size($m):"?"),"\n";}echo"</table>\n",(support("database")?"<div class='footer'><div>\n"."<fieldset><legend>".'Selected'." <span id='selected'></span></legend><div>\n"."<input type='hidden' name='all' value=''>".script("qsl('input').onclick = function () { selectCount('selected', formChecked(this, /^db/)); };")."<input type='submit' name='drop' value='".'Drop'."'>".confirm()."\n"."</div></fieldset>\n"."</div></div>\n":""),"<input type='hidden' name='token' value='$mi'>\n","</form>\n",script("tableCheck();");}}page_footer("db");}if(isset($_GET["status"]))$_GET["variables"]=$_GET["status"];if(isset($_GET["import"]))$_GET["sql"]=$_GET["import"];if(!(DB!=""?$g->select_db(DB):isset($_GET["sql"])||isset($_GET["dump"])||isset($_GET["database"])||isset($_GET["processlist"])||isset($_GET["privileges"])||isset($_GET["user"])||isset($_GET["variables"])||$_GET["script"]=="connect"||$_GET["script"]=="kill")){if(DB!=""||$_GET["refresh"]){restart_session();set_session("dbs",null);}connect_error();exit;}if(support("scheme")&&DB!=""&&$_GET["ns"]!==""){if(!isset($_GET["ns"]))redirect(preg_replace('~ns=[^&]*&~','',ME)."ns=".get_schema());if(!set_schema($_GET["ns"])){header("HTTP/1.1 404 Not Found");page_header('Schema'.": ".h($_GET["ns"]),'Invalid schema.',true);page_footer("ns");exit;}}$nf="RESTRICT|NO ACTION|CASCADE|SET NULL|SET DEFAULT";class
TmpFile{var$handler;var$size;function
__construct(){$this->handler=tmpfile();}function
write($zb){$this->size+=strlen($zb);fwrite($this->handler,$zb);}function
send(){fseek($this->handler,0);fpassthru($this->handler);fclose($this->handler);}}$uc="'(?:''|[^'\\\\]|\\\\.)*'";$Pd="IN|OUT|INOUT";if(isset($_GET["select"])&&($_POST["edit"]||$_POST["clone"])&&!$_POST["save"])$_GET["edit"]=$_GET["select"];if(isset($_GET["callf"]))$_GET["call"]=$_GET["callf"];if(isset($_GET["function"]))$_GET["procedure"]=$_GET["function"];if(isset($_GET["download"])){$a=$_GET["download"];$q=fields($a);header("Content-Type: application/octet-stream");header("Content-Disposition: attachment; filename=".friendly_url("$a-".implode("_",$_GET["where"])).".".friendly_url($_GET["field"]));$L=array(idf_escape($_GET["field"]));$H=$n->select($a,$L,array(where($_GET,$q)),$L);$J=($H?$H->fetch_row():array());echo$n->value($J[0],$q[$_GET["field"]]);exit;}elseif(isset($_GET["table"])){$a=$_GET["table"];$q=fields($a);if(!$q)$o=error();$R=table_status1($a,true);$C=$b->tableName($R);page_header(($q&&is_view($R)?$R['Engine']=='materialized view'?'Materialized view':'View':'Table').": ".($C!=""?$C:h($a)),$o);$b->selectLinks($R);$tb=$R["Comment"];if($tb!="")echo"<p class='nowrap'>".'Comment'.": ".h($tb)."\n";if($q)$b->tableStructurePrint($q);if(!is_view($R)){if(support("indexes")){echo"<h3 id='indexes'>".'Indexes'."</h3>\n";$x=indexes($a);if($x)$b->tableIndexesPrint($x);echo'<p class="links"><a href="'.h(ME).'indexes='.urlencode($a).'">'.'Alter indexes'."</a>\n";}if(fk_support($R)){echo"<h3 id='foreign-keys'>".'Foreign keys'."</h3>\n";$cd=foreign_keys($a);if($cd){echo"<table cellspacing='0'>\n","<thead><tr><th>".'Source'."<td>".'Target'."<td>".'ON DELETE'."<td>".'ON UPDATE'."<td></thead>\n";foreach($cd
as$C=>$r){echo"<tr title='".h($C)."'>","<th><i>".implode("</i>, <i>",array_map('h',$r["source"]))."</i>","<td><a href='".h($r["db"]!=""?preg_replace('~db=[^&]*~',"db=".urlencode($r["db"]),ME):($r["ns"]!=""?preg_replace('~ns=[^&]*~',"ns=".urlencode($r["ns"]),ME):ME))."table=".urlencode($r["table"])."'>".($r["db"]!=""?"<b>".h($r["db"])."</b>.":"").($r["ns"]!=""?"<b>".h($r["ns"])."</b>.":"").h($r["table"])."</a>","(<i>".implode("</i>, <i>",array_map('h',$r["target"]))."</i>)","<td>".h($r["on_delete"])."\n","<td>".h($r["on_update"])."\n",'<td><a href="'.h(ME.'foreign='.urlencode($a).'&name='.urlencode($C)).'">'.'Alter'.'</a>';}echo"</table>\n";}echo'<p class="links"><a href="'.h(ME).'foreign='.urlencode($a).'">'.'Add foreign key'."</a>\n";}}if(support(is_view($R)?"view_trigger":"trigger")){echo"<h3 id='triggers'>".'Triggers'."</h3>\n";$yi=triggers($a);if($yi){echo"<table cellspacing='0'>\n";foreach($yi
as$z=>$X)echo"<tr valign='top'><td>".h($X[0])."<td>".h($X[1])."<th>".h($z)."<td><a href='".h(ME.'trigger='.urlencode($a).'&name='.urlencode($z))."'>".'Alter'."</a>\n";echo"</table>\n";}echo'<p class="links"><a href="'.h(ME).'trigger='.urlencode($a).'">'.'Add trigger'."</a>\n";}}elseif(isset($_GET["schema"])){page_header('Database schema',"",array(),h(DB.($_GET["ns"]?".$_GET[ns]":"")));$Ph=array();$Qh=array();$ea=($_GET["schema"]?$_GET["schema"]:$_COOKIE["adminer_schema-".str_replace(".","_",DB)]);preg_match_all('~([^:]+):([-0-9.]+)x([-0-9.]+)(_|$)~',$ea,$Be,PREG_SET_ORDER);foreach($Be
as$t=>$B){$Ph[$B[1]]=array($B[2],$B[3]);$Qh[]="\n\t'".js_escape($B[1])."': [ $B[2], $B[3] ]";}$ni=0;$Qa=-1;$Xg=array();$Cg=array();$pe=array();foreach(table_status('',true)as$Q=>$R){if(is_view($R))continue;$bg=0;$Xg[$Q]["fields"]=array();foreach(fields($Q)as$C=>$p){$bg+=1.25;$p["pos"]=$bg;$Xg[$Q]["fields"][$C]=$p;}$Xg[$Q]["pos"]=($Ph[$Q]?$Ph[$Q]:array($ni,0));foreach($b->foreignKeys($Q)as$X){if(!$X["db"]){$ne=$Qa;if($Ph[$Q][1]||$Ph[$X["table"]][1])$ne=min(floatval($Ph[$Q][1]),floatval($Ph[$X["table"]][1]))-1;else$Qa-=.1;while($pe[(string)$ne])$ne-=.0001;$Xg[$Q]["references"][$X["table"]][(string)$ne]=array($X["source"],$X["target"]);$Cg[$X["table"]][$Q][(string)$ne]=$X["target"];$pe[(string)$ne]=true;}}$ni=max($ni,$Xg[$Q]["pos"][0]+2.5+$bg);}echo'<div id="schema" style="height: ',$ni,'em;">
<script',nonce(),'>
qs(\'#schema\').onselectstart = function () { return false; };
var tablePos = {',implode(",",$Qh)."\n",'};
var em = qs(\'#schema\').offsetHeight / ',$ni,';
document.onmousemove = schemaMousemove;
document.onmouseup = partialArg(schemaMouseup, \'',js_escape(DB),'\');
</script>
';foreach($Xg
as$C=>$Q){echo"<div class='table' style='top: ".$Q["pos"][0]."em; left: ".$Q["pos"][1]."em;'>",'<a href="'.h(ME).'table='.urlencode($C).'"><b>'.h($C)."</b></a>",script("qsl('div').onmousedown = schemaMousedown;");foreach($Q["fields"]as$p){$X='<span'.type_class($p["type"]).' title="'.h($p["full_type"].($p["null"]?" NULL":'')).'">'.h($p["field"]).'</span>';echo"<br>".($p["primary"]?"<i>$X</i>":$X);}foreach((array)$Q["references"]as$Wh=>$Dg){foreach($Dg
as$ne=>$_g){$oe=$ne-$Ph[$C][1];$t=0;foreach($_g[0]as$th)echo"\n<div class='references' title='".h($Wh)."' id='refs$ne-".($t++)."' style='left: $oe"."em; top: ".$Q["fields"][$th]["pos"]."em; padding-top: .5em;'><div style='border-top: 1px solid Gray; width: ".(-$oe)."em;'></div></div>";}}foreach((array)$Cg[$C]as$Wh=>$Dg){foreach($Dg
as$ne=>$f){$oe=$ne-$Ph[$C][1];$t=0;foreach($f
as$Vh)echo"\n<div class='references' title='".h($Wh)."' id='refd$ne-".($t++)."' style='left: $oe"."em; top: ".$Q["fields"][$Vh]["pos"]."em; height: 1.25em; background: url(".h(preg_replace("~\\?.*~","",ME)."?file=arrow.gif) no-repeat right center;&version=4.7.1")."'><div style='height: .5em; border-bottom: 1px solid Gray; width: ".(-$oe)."em;'></div></div>";}}echo"\n</div>\n";}foreach($Xg
as$C=>$Q){foreach((array)$Q["references"]as$Wh=>$Dg){foreach($Dg
as$ne=>$_g){$Qe=$ni;$Fe=-10;foreach($_g[0]as$z=>$th){$cg=$Q["pos"][0]+$Q["fields"][$th]["pos"];$dg=$Xg[$Wh]["pos"][0]+$Xg[$Wh]["fields"][$_g[1][$z]]["pos"];$Qe=min($Qe,$cg,$dg);$Fe=max($Fe,$cg,$dg);}echo"<div class='references' id='refl$ne' style='left: $ne"."em; top: $Qe"."em; padding: .5em 0;'><div style='border-right: 1px solid Gray; margin-top: 1px; height: ".($Fe-$Qe)."em;'></div></div>\n";}}}echo'</div>
<p class="links"><a href="',h(ME."schema=".urlencode($ea)),'" id="schema-link">Permanent link</a>
';}elseif(isset($_GET["dump"])){$a=$_GET["dump"];if($_POST&&!$o){$Bb="";foreach(array("output","format","db_style","routines","events","table_style","auto_increment","triggers","data_style")as$z)$Bb.="&$z=".urlencode($_POST[$z]);cookie("adminer_export",substr($Bb,1));$S=array_flip((array)$_POST["tables"])+array_flip((array)$_POST["data"]);$Gc=dump_headers((count($S)==1?key($S):DB),(DB==""||count($S)>1));$Xd=preg_match('~sql~',$_POST["format"]);if($Xd){echo"-- Adminer $ia ".$cc[DRIVER]." dump\n\n";if($y=="sql"){echo"SET NAMES utf8;
SET time_zone = '+00:00';
".($_POST["data_style"]?"SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';
":"")."
";$g->query("SET time_zone = '+00:00';");}}$Gh=$_POST["db_style"];$l=array(DB);if(DB==""){$l=$_POST["databases"];if(is_string($l))$l=explode("\n",rtrim(str_replace("\r","",$l),"\n"));}foreach((array)$l
as$m){$b->dumpDatabase($m);if($g->select_db($m)){if($Xd&&preg_match('~CREATE~',$Gh)&&($i=$g->result("SHOW CREATE DATABASE ".idf_escape($m),1))){set_utf8mb4($i);if($Gh=="DROP+CREATE")echo"DROP DATABASE IF EXISTS ".idf_escape($m).";\n";echo"$i;\n";}if($Xd){if($Gh)echo
use_sql($m).";\n\n";$Gf="";if($_POST["routines"]){foreach(array("FUNCTION","PROCEDURE")as$Rg){foreach(get_rows("SHOW $Rg STATUS WHERE Db = ".q($m),null,"-- ")as$J){$i=remove_definer($g->result("SHOW CREATE $Rg ".idf_escape($J["Name"]),2));set_utf8mb4($i);$Gf.=($Gh!='DROP+CREATE'?"DROP $Rg IF EXISTS ".idf_escape($J["Name"]).";;\n":"")."$i;;\n\n";}}}if($_POST["events"]){foreach(get_rows("SHOW EVENTS",null,"-- ")as$J){$i=remove_definer($g->result("SHOW CREATE EVENT ".idf_escape($J["Name"]),3));set_utf8mb4($i);$Gf.=($Gh!='DROP+CREATE'?"DROP EVENT IF EXISTS ".idf_escape($J["Name"]).";;\n":"")."$i;;\n\n";}}if($Gf)echo"DELIMITER ;;\n\n$Gf"."DELIMITER ;\n\n";}if($_POST["table_style"]||$_POST["data_style"]){$Xi=array();foreach(table_status('',true)as$C=>$R){$Q=(DB==""||in_array($C,(array)$_POST["tables"]));$Jb=(DB==""||in_array($C,(array)$_POST["data"]));if($Q||$Jb){if($Gc=="tar"){$ji=new
TmpFile;ob_start(array($ji,'write'),1e5);}$b->dumpTable($C,($Q?$_POST["table_style"]:""),(is_view($R)?2:0));if(is_view($R))$Xi[]=$C;elseif($Jb){$q=fields($C);$b->dumpData($C,$_POST["data_style"],"SELECT *".convert_fields($q,$q)." FROM ".table($C));}if($Xd&&$_POST["triggers"]&&$Q&&($yi=trigger_sql($C)))echo"\nDELIMITER ;;\n$yi\nDELIMITER ;\n";if($Gc=="tar"){ob_end_flush();tar_file((DB!=""?"":"$m/")."$C.csv",$ji);}elseif($Xd)echo"\n";}}foreach($Xi
as$Wi)$b->dumpTable($Wi,$_POST["table_style"],1);if($Gc=="tar")echo
pack("x512");}}}if($Xd)echo"-- ".$g->result("SELECT NOW()")."\n";exit;}page_header('Export',$o,($_GET["export"]!=""?array("table"=>$_GET["export"]):array()),h(DB));echo'
<form action="" method="post">
<table cellspacing="0" class="layout">
';$Nb=array('','USE','DROP+CREATE','CREATE');$Rh=array('','DROP+CREATE','CREATE');$Kb=array('','TRUNCATE+INSERT','INSERT');if($y=="sql")$Kb[]='INSERT+UPDATE';parse_str($_COOKIE["adminer_export"],$J);if(!$J)$J=array("output"=>"text","format"=>"sql","db_style"=>(DB!=""?"":"CREATE"),"table_style"=>"DROP+CREATE","data_style"=>"INSERT");if(!isset($J["events"])){$J["routines"]=$J["events"]=($_GET["dump"]=="");$J["triggers"]=$J["table_style"];}echo"<tr><th>".'Output'."<td>".adminer_html_select("output",$b->dumpOutput(),$J["output"],0)."\n";echo"<tr><th>".'Format'."<td>".adminer_html_select("format",$b->dumpFormat(),$J["format"],0)."\n";echo($y=="sqlite"?"":"<tr><th>".'Database'."<td>".adminer_html_select('db_style',$Nb,$J["db_style"]).(support("routine")?checkbox("routines",1,$J["routines"],'Routines'):"").(support("event")?checkbox("events",1,$J["events"],'Events'):"")),"<tr><th>".'Tables'."<td>".adminer_html_select('table_style',$Rh,$J["table_style"]).checkbox("auto_increment",1,$J["auto_increment"],'Auto Increment').(support("trigger")?checkbox("triggers",1,$J["triggers"],'Triggers'):""),"<tr><th>".'Data'."<td>".adminer_html_select('data_style',$Kb,$J["data_style"]),'</table>
<p><input type="submit" value="Export">
<input type="hidden" name="token" value="',$mi,'">

<table cellspacing="0">
',script("qsl('table').onclick = dumpClick;");$gg=array();if(DB!=""){$eb=($a!=""?"":" checked");echo"<thead><tr>","<th style='text-align: left;'><label class='block'><input type='checkbox' id='check-tables'$eb>".'Tables'."</label>".script("qs('#check-tables').onclick = partial(formCheck, /^tables\\[/);",""),"<th style='text-align: right;'><label class='block'>".'Data'."<input type='checkbox' id='check-data'$eb></label>".script("qs('#check-data').onclick = partial(formCheck, /^data\\[/);",""),"</thead>\n";$Xi="";$Sh=tables_list();foreach($Sh
as$C=>$T){$fg=preg_replace('~_.*~','',$C);$eb=($a==""||$a==(substr($a,-1)=="%"?"$fg%":$C));$jg="<tr><td>".checkbox("tables[]",$C,$eb,$C,"","block");if($T!==null&&!preg_match('~table~i',$T))$Xi.="$jg\n";else
echo"$jg<td align='right'><label class='block'><span id='Rows-".h($C)."'></span>".checkbox("data[]",$C,$eb)."</label>\n";$gg[$fg]++;}echo$Xi;if($Sh)echo
script("ajaxSetHtml('".js_escape(ME)."script=db');");}else{echo"<thead><tr><th style='text-align: left;'>","<label class='block'><input type='checkbox' id='check-databases'".($a==""?" checked":"").">".'Database'."</label>",script("qs('#check-databases').onclick = partial(formCheck, /^databases\\[/);",""),"</thead>\n";$l=$b->databases();if($l){foreach($l
as$m){if(!information_schema($m)){$fg=preg_replace('~_.*~','',$m);echo"<tr><td>".checkbox("databases[]",$m,$a==""||$a=="$fg%",$m,"","block")."\n";$gg[$fg]++;}}}else
echo"<tr><td><textarea name='databases' rows='10' cols='20'></textarea>";}echo'</table>
</form>
';$Uc=true;foreach($gg
as$z=>$X){if($z!=""&&$X>1){echo($Uc?"<p>":" ")."<a href='".h(ME)."dump=".urlencode("$z%")."'>".h($z)."</a>";$Uc=false;}}}elseif(isset($_GET["privileges"])){page_header('Privileges');echo'<p class="links"><a href="'.h(ME).'user=">'.'Create user'."</a>";$H=$g->query("SELECT User, Host FROM mysql.".(DB==""?"user":"db WHERE ".q(DB)." LIKE Db")." ORDER BY Host, User");$jd=$H;if(!$H)$H=$g->query("SELECT SUBSTRING_INDEX(CURRENT_USER, '@', 1) AS User, SUBSTRING_INDEX(CURRENT_USER, '@', -1) AS Host");echo"<form action=''><p>\n";hidden_fields_get();echo"<input type='hidden' name='db' value='".h(DB)."'>\n",($jd?"":"<input type='hidden' name='grant' value=''>\n"),"<table cellspacing='0'>\n","<thead><tr><th>".'Username'."<th>".'Server'."<th></thead>\n";while($J=$H->fetch_assoc())echo'<tr'.odd().'><td>'.h($J["User"])."<td>".h($J["Host"]).'<td><a href="'.h(ME.'user='.urlencode($J["User"]).'&host='.urlencode($J["Host"])).'">'.'Edit'."</a>\n";if(!$jd||DB!="")echo"<tr".odd()."><td><input name='user' autocapitalize='off'><td><input name='host' value='localhost' autocapitalize='off'><td><input type='submit' value='".'Edit'."'>\n";echo"</table>\n","</form>\n";}elseif(isset($_GET["sql"])){if(!$o&&$_POST["export"]){dump_headers("sql");$b->dumpTable("","");$b->dumpData("","table",$_POST["query"]);exit;}restart_session();$xd=&get_session("queries");$wd=&$xd[DB];if(!$o&&$_POST["clear"]){$wd=array();redirect(remove_from_uri("history"));}page_header((isset($_GET["import"])?'Import':'SQL command'),$o);if(!$o&&$_POST){$gd=false;if(!isset($_GET["import"]))$G=$_POST["query"];elseif($_POST["webfile"]){$xh=$b->importServerPath();$gd=@fopen((file_exists($xh)?$xh:"compress.zlib://$xh.gz"),"rb");$G=($gd?fread($gd,1e6):false);}else$G=get_file("sql_file",true);if(is_string($G)){if(function_exists('memory_get_usage'))@ini_set("memory_limit",max(ini_bytes("memory_limit"),2*strlen($G)+memory_get_usage()+8e6));if($G!=""&&strlen($G)<1e6){$rg=$G.(preg_match("~;[ \t\r\n]*\$~",$G)?"":";");if(!$wd||reset(end($wd))!=$rg){restart_session();$wd[]=array($rg,time());set_session("queries",$xd);stop_session();}}$uh="(?:\\s|/\\*[\s\S]*?\\*/|(?:#|-- )[^\n]*\n?|--\r?\n)";$Tb=";";$D=0;$rc=true;$h=connect();if(is_object($h)&&DB!="")$h->select_db(DB);$sb=0;$wc=array();$Nf='[\'"'.($y=="sql"?'`#':($y=="sqlite"?'`[':($y=="mssql"?'[':''))).']|/\*|-- |$'.($y=="pgsql"?'|\$[^$]*\$':'');$oi=microtime(true);parse_str($_COOKIE["adminer_export"],$xa);$ic=$b->dumpFormat();unset($ic["sql"]);while($G!=""){if(!$D&&preg_match("~^$uh*+DELIMITER\\s+(\\S+)~i",$G,$B)){$Tb=$B[1];$G=substr($G,strlen($B[0]));}else{preg_match('('.preg_quote($Tb)."\\s*|$Nf)",$G,$B,PREG_OFFSET_CAPTURE,$D);list($ed,$bg)=$B[0];if(!$ed&&$gd&&!feof($gd))$G.=fread($gd,1e5);else{if(!$ed&&rtrim($G)=="")break;$D=$bg+strlen($ed);if($ed&&rtrim($ed)!=$Tb){while(preg_match('('.($ed=='/*'?'\*/':($ed=='['?']':(preg_match('~^-- |^#~',$ed)?"\n":preg_quote($ed)."|\\\\."))).'|$)s',$G,$B,PREG_OFFSET_CAPTURE,$D)){$Vg=$B[0][0];if(!$Vg&&$gd&&!feof($gd))$G.=fread($gd,1e5);else{$D=$B[0][1]+strlen($Vg);if($Vg[0]!="\\")break;}}}else{$rc=false;$rg=substr($G,0,$bg);$sb++;$jg="<pre id='sql-$sb'><code class='jush-$y'>".$b->sqlCommandQuery($rg)."</code></pre>\n";if($y=="sqlite"&&preg_match("~^$uh*+ATTACH\\b~i",$rg,$B)){echo$jg,"<p class='error'>".'ATTACH queries are not supported.'."\n";$wc[]=" <a href='#sql-$sb'>$sb</a>";if($_POST["error_stops"])break;}else{if(!$_POST["only_errors"]){echo$jg;ob_flush();flush();}$Ah=microtime(true);if($g->multi_query($rg)&&is_object($h)&&preg_match("~^$uh*+USE\\b~i",$rg))$h->query($rg);do{$H=$g->store_result();if($g->error){echo($_POST["only_errors"]?$jg:""),"<p class='error'>".'Error in query'.($g->errno?" ($g->errno)":"").": ".error()."\n";$wc[]=" <a href='#sql-$sb'>$sb</a>";if($_POST["error_stops"])break
2;}else{$ci=" <span class='time'>(".format_time($Ah).")</span>".(strlen($rg)<1000?" <a href='".h(ME)."sql=".urlencode(trim($rg))."'>".'Edit'."</a>":"");$za=$g->affected_rows;$aj=($_POST["only_errors"]?"":$n->warnings());$bj="warnings-$sb";if($aj)$ci.=", <a href='#$bj'>".'Warnings'."</a>".script("qsl('a').onclick = partial(toggle, '$bj');","");$Dc=null;$Ec="explain-$sb";if(is_object($H)){$_=$_POST["limit"];$_f=select($H,$h,array(),$_);if(!$_POST["only_errors"]){echo"<form action='' method='post'>\n";$df=$H->num_rows;echo"<p>".($df?($_&&$df>$_?sprintf('%d / ',$_):"").lang(array('%d row','%d rows'),$df):""),$ci;if($h&&preg_match("~^($uh|\\()*+SELECT\\b~i",$rg)&&($Dc=explain($h,$rg)))echo", <a href='#$Ec'>Explain</a>".script("qsl('a').onclick = partial(toggle, '$Ec');","");$u="export-$sb";echo", <a href='#$u'>".'Export'."</a>".script("qsl('a').onclick = partial(toggle, '$u');","")."<span id='$u' class='hidden'>: ".adminer_html_select("output",$b->dumpOutput(),$xa["output"])." ".adminer_html_select("format",$ic,$xa["format"])."<input type='hidden' name='query' value='".h($rg)."'>"." <input type='submit' name='export' value='".'Export'."'><input type='hidden' name='token' value='$mi'></span>\n"."</form>\n";}}else{if(preg_match("~^$uh*+(CREATE|DROP|ALTER)$uh++(DATABASE|SCHEMA)\\b~i",$rg)){restart_session();set_session("dbs",null);stop_session();}if(!$_POST["only_errors"])echo"<p class='message' title='".h($g->info)."'>".lang(array('Query executed OK, %d row affected.','Query executed OK, %d rows affected.'),$za)."$ci\n";}echo($aj?"<div id='$bj' class='hidden'>\n$aj</div>\n":"");if($Dc){echo"<div id='$Ec' class='hidden'>\n";select($Dc,$h,$_f);echo"</div>\n";}}$Ah=microtime(true);}while($g->next_result());}$G=substr($G,$D);$D=0;}}}}if($rc)echo"<p class='message'>".'No commands to execute.'."\n";elseif($_POST["only_errors"]){echo"<p class='message'>".lang(array('%d query executed OK.','%d queries executed OK.'),$sb-count($wc))," <span class='time'>(".format_time($oi).")</span>\n";}elseif($wc&&$sb>1)echo"<p class='error'>".'Error in query'.": ".implode("",$wc)."\n";}else
echo"<p class='error'>".upload_error($G)."\n";}echo'
<form action="" method="post" enctype="multipart/form-data" id="form">
';$Ac="<input type='submit' value='".'Execute'."' title='Ctrl+Enter'>";if(!isset($_GET["import"])){$rg=$_GET["sql"];if($_POST)$rg=$_POST["query"];elseif($_GET["history"]=="all")$rg=$wd;elseif($_GET["history"]!="")$rg=$wd[$_GET["history"]][0];echo"<p>";textarea("query",$rg,20);echo
script(($_POST?"":"qs('textarea').focus();\n")."qs('#form').onsubmit = partial(sqlSubmit, qs('#form'), '".remove_from_uri("sql|limit|error_stops|only_errors")."');"),"<p>$Ac\n",'Limit rows'.": <input type='number' name='limit' class='size' value='".h($_POST?$_POST["limit"]:$_GET["limit"])."'>\n";}else{echo"<fieldset><legend>".'File upload'."</legend><div>";$pd=(extension_loaded("zlib")?"[.gz]":"");echo(ini_bool("file_uploads")?"SQL$pd (&lt; ".ini_get("upload_max_filesize")."B): <input type='file' name='sql_file[]' multiple>\n$Ac":'File uploads are disabled.'),"</div></fieldset>\n";$Ed=$b->importServerPath();if($Ed){echo"<fieldset><legend>".'From server'."</legend><div>",sprintf('Webserver file %s',"<code>".h($Ed)."$pd</code>"),' <input type="submit" name="webfile" value="'.'Run file'.'">',"</div></fieldset>\n";}echo"<p>";}echo
checkbox("error_stops",1,($_POST?$_POST["error_stops"]:isset($_GET["import"])),'Stop on error')."\n",checkbox("only_errors",1,($_POST?$_POST["only_errors"]:isset($_GET["import"])),'Show only errors')."\n","<input type='hidden' name='token' value='$mi'>\n";if(!isset($_GET["import"])&&$wd){print_fieldset("history",'History',$_GET["history"]!="");for($X=end($wd);$X;$X=prev($wd)){$z=key($wd);list($rg,$ci,$mc)=$X;echo'<a href="'.h(ME."sql=&history=$z").'">'.'Edit'."</a>"." <span class='time' title='".@date('Y-m-d',$ci)."'>".@date("H:i:s",$ci)."</span>"." <code class='jush-$y'>".shorten_utf8(ltrim(str_replace("\n"," ",str_replace("\r","",preg_replace('~^(#|-- ).*~m','',$rg)))),80,"</code>").($mc?" <span class='time'>($mc)</span>":"")."<br>\n";}echo"<input type='submit' name='clear' value='".'Clear'."'>\n","<a href='".h(ME."sql=&history=all")."'>".'Edit all'."</a>\n","</div></fieldset>\n";}echo'</form>
';}elseif(isset($_GET["edit"])){$a=$_GET["edit"];$q=fields($a);$Z=(isset($_GET["select"])?($_POST["check"]&&count($_POST["check"])==1?where_check($_POST["check"][0],$q):""):where($_GET,$q));$Hi=(isset($_GET["select"])?$_POST["edit"]:$Z);foreach($q
as$C=>$p){if(!isset($p["privileges"][$Hi?"update":"insert"])||$b->fieldName($p)=="")unset($q[$C]);}if($_POST&&!$o&&!isset($_GET["select"])){$ve=$_POST["referer"];if($_POST["insert"])$ve=($Hi?null:$_SERVER["REQUEST_URI"]);elseif(!preg_match('~^.+&select=.+$~',$ve))$ve=ME."select=".urlencode($a);$x=indexes($a);$Ci=unique_array($_GET["where"],$x);$ug="\nWHERE $Z";if(isset($_POST["delete"]))queries_redirect($ve,'Item has been deleted.',$n->delete($a,$ug,!$Ci));else{$O=array();foreach($q
as$C=>$p){$X=process_input($p);if($X!==false&&$X!==null)$O[idf_escape($C)]=$X;}if($Hi){if(!$O)redirect($ve);queries_redirect($ve,'Item has been updated.',$n->update($a,$O,$ug,!$Ci));if(is_ajax()){page_headers();page_messages($o);exit;}}else{$H=$n->insert($a,$O);$me=($H?last_id():0);queries_redirect($ve,sprintf('Item%s has been inserted.',($me?" $me":"")),$H);}}}$J=null;if($_POST["save"])$J=(array)$_POST["fields"];elseif($Z){$L=array();foreach($q
as$C=>$p){if(isset($p["privileges"]["select"])){$Ga=convert_field($p);if($_POST["clone"]&&$p["auto_increment"])$Ga="''";if($y=="sql"&&preg_match("~enum|set~",$p["type"]))$Ga="1*".idf_escape($C);$L[]=($Ga?"$Ga AS ":"").idf_escape($C);}}$J=array();if(!support("table"))$L=array("*");if($L){$H=$n->select($a,$L,array($Z),$L,array(),(isset($_GET["select"])?2:1));if(!$H)$o=error();else{$J=$H->fetch_assoc();if(!$J)$J=false;}if(isset($_GET["select"])&&(!$J||$H->fetch_assoc()))$J=null;}}if(!support("table")&&!$q){if(!$Z){$H=$n->select($a,array("*"),$Z,array("*"));$J=($H?$H->fetch_assoc():false);if(!$J)$J=array($n->primary=>"");}if($J){foreach($J
as$z=>$X){if(!$Z)$J[$z]=null;$q[$z]=array("field"=>$z,"null"=>($z!=$n->primary),"auto_increment"=>($z==$n->primary));}}}edit_form($a,$q,$J,$Hi);}elseif(isset($_GET["create"])){$a=$_GET["create"];$Pf=array();foreach(array('HASH','LINEAR HASH','KEY','LINEAR KEY','RANGE','LIST')as$z)$Pf[$z]=$z;$Bg=referencable_primary($a);$cd=array();foreach($Bg
as$Nh=>$p)$cd[str_replace("`","``",$Nh)."`".str_replace("`","``",$p["field"])]=$Nh;$Cf=array();$R=array();if($a!=""){$Cf=fields($a);$R=table_status($a);if(!$R)$o='No tables.';}$J=$_POST;$J["fields"]=(array)$J["fields"];if($J["auto_increment_col"])$J["fields"][$J["auto_increment_col"]]["auto_increment"]=true;if($_POST)set_adminer_settings(array("comments"=>$_POST["comments"],"defaults"=>$_POST["defaults"]));if($_POST&&!process_fields($J["fields"])&&!$o){if($_POST["drop"])queries_redirect(substr(ME,0,-1),'Table has been dropped.',drop_tables(array($a)));else{$q=array();$Da=array();$Mi=false;$ad=array();$Bf=reset($Cf);$Aa=" FIRST";foreach($J["fields"]as$z=>$p){$r=$cd[$p["type"]];$zi=($r!==null?$Bg[$r]:$p);if($p["field"]!=""){if(!$p["has_default"])$p["default"]=null;if($z==$J["auto_increment_col"])$p["auto_increment"]=true;$og=process_field($p,$zi);$Da[]=array($p["orig"],$og,$Aa);if($og!=process_field($Bf,$Bf)){$q[]=array($p["orig"],$og,$Aa);if($p["orig"]!=""||$Aa)$Mi=true;}if($r!==null)$ad[idf_escape($p["field"])]=($a!=""&&$y!="sqlite"?"ADD":" ").format_foreign_key(array('table'=>$cd[$p["type"]],'source'=>array($p["field"]),'target'=>array($zi["field"]),'on_delete'=>$p["on_delete"],));$Aa=" AFTER ".idf_escape($p["field"]);}elseif($p["orig"]!=""){$Mi=true;$q[]=array($p["orig"]);}if($p["orig"]!=""){$Bf=next($Cf);if(!$Bf)$Aa="";}}$Rf="";if($Pf[$J["partition_by"]]){$Sf=array();if($J["partition_by"]=='RANGE'||$J["partition_by"]=='LIST'){foreach(array_filter($J["partition_names"])as$z=>$X){$Y=$J["partition_values"][$z];$Sf[]="\n  PARTITION ".idf_escape($X)." VALUES ".($J["partition_by"]=='RANGE'?"LESS THAN":"IN").($Y!=""?" ($Y)":" MAXVALUE");}}$Rf.="\nPARTITION BY $J[partition_by]($J[partition])".($Sf?" (".implode(",",$Sf)."\n)":($J["partitions"]?" PARTITIONS ".(+$J["partitions"]):""));}elseif(support("partitioning")&&preg_match("~partitioned~",$R["Create_options"]))$Rf.="\nREMOVE PARTITIONING";$Je='Table has been altered.';if($a==""){cookie("adminer_engine",$J["Engine"]);$Je='Table has been created.';}$C=trim($J["name"]);queries_redirect(ME.(support("table")?"table=":"select=").urlencode($C),$Je,alter_table($a,$C,($y=="sqlite"&&($Mi||$ad)?$Da:$q),$ad,($J["Comment"]!=$R["Comment"]?$J["Comment"]:null),($J["Engine"]&&$J["Engine"]!=$R["Engine"]?$J["Engine"]:""),($J["Collation"]&&$J["Collation"]!=$R["Collation"]?$J["Collation"]:""),($J["Auto_increment"]!=""?number($J["Auto_increment"]):""),$Rf));}}page_header(($a!=""?'Alter table':'Create table'),$o,array("table"=>$a),h($a));if(!$_POST){$J=array("Engine"=>$_COOKIE["adminer_engine"],"fields"=>array(array("field"=>"","type"=>(isset($U["int"])?"int":(isset($U["integer"])?"integer":"")),"on_update"=>"")),"partition_names"=>array(""),);if($a!=""){$J=$R;$J["name"]=$a;$J["fields"]=array();if(!$_GET["auto_increment"])$J["Auto_increment"]="";foreach($Cf
as$p){$p["has_default"]=isset($p["default"]);$J["fields"][]=$p;}if(support("partitioning")){$hd="FROM information_schema.PARTITIONS WHERE TABLE_SCHEMA = ".q(DB)." AND TABLE_NAME = ".q($a);$H=$g->query("SELECT PARTITION_METHOD, PARTITION_ORDINAL_POSITION, PARTITION_EXPRESSION $hd ORDER BY PARTITION_ORDINAL_POSITION DESC LIMIT 1");list($J["partition_by"],$J["partitions"],$J["partition"])=$H->fetch_row();$Sf=get_key_vals("SELECT PARTITION_NAME, PARTITION_DESCRIPTION $hd AND PARTITION_NAME != '' ORDER BY PARTITION_ORDINAL_POSITION");$Sf[""]="";$J["partition_names"]=array_keys($Sf);$J["partition_values"]=array_values($Sf);}}}$ob=collations();$tc=engines();foreach($tc
as$sc){if(!strcasecmp($sc,$J["Engine"])){$J["Engine"]=$sc;break;}}echo'
<form action="" method="post" id="form">
<p>
';if(support("columns")||$a==""){echo'Table name: <input name="name" data-maxlength="64" value="',h($J["name"]),'" autocapitalize="off">
';if($a==""&&!$_POST)echo
script("focus(qs('#form')['name']);");echo($tc?"<select name='Engine'>".optionlist(array(""=>"(".'engine'.")")+$tc,$J["Engine"])."</select>".on_help("getTarget(event).value",1).script("qsl('select').onchange = helpClose;"):""),' ',($ob&&!preg_match("~sqlite|mssql~",$y)?adminer_html_select("Collation",array(""=>"(".'collation'.")")+$ob,$J["Collation"]):""),' <input type="submit" value="Save">
';}echo'
';if(support("columns")){echo'<div class="scrollable">
<table cellspacing="0" id="edit-fields" class="nowrap">
';edit_fields($J["fields"],$ob,"TABLE",$cd);echo'</table>
</div>
<p>
Auto Increment: <input type="number" name="Auto_increment" size="6" value="',h($J["Auto_increment"]),'">
',checkbox("defaults",1,($_POST?$_POST["defaults"]:adminer_setting("defaults")),'Default values',"columnShow(this.checked, 5)","jsonly"),(support("comment")?checkbox("comments",1,($_POST?$_POST["comments"]:adminer_setting("comments")),'Comment',"editingCommentsClick(this, true);","jsonly").' <input name="Comment" value="'.h($J["Comment"]).'" data-maxlength="'.(min_version(5.5)?2048:60).'">':''),'<p>
<input type="submit" value="Save">
';}echo'
';if($a!=""){echo'<input type="submit" name="drop" value="Drop">',confirm(sprintf('Drop %s?',$a));}if(support("partitioning")){$Qf=preg_match('~RANGE|LIST~',$J["partition_by"]);print_fieldset("partition",'Partition by',$J["partition_by"]);echo'<p>
',"<select name='partition_by'>".optionlist(array(""=>"")+$Pf,$J["partition_by"])."</select>".on_help("getTarget(event).value.replace(/./, 'PARTITION BY \$&')",1).script("qsl('select').onchange = partitionByChange;"),'(<input name="partition" value="',h($J["partition"]),'">)
Partitions: <input type="number" name="partitions" class="size',($Qf||!$J["partition_by"]?" hidden":""),'" value="',h($J["partitions"]),'">
<table cellspacing="0" id="partition-table"',($Qf?"":" class='hidden'"),'>
<thead><tr><th>Partition name<th>Values</thead>
';foreach($J["partition_names"]as$z=>$X){echo'<tr>','<td><input name="partition_names[]" value="'.h($X).'" autocapitalize="off">',($z==count($J["partition_names"])-1?script("qsl('input').oninput = partitionNameChange;"):''),'<td><input name="partition_values[]" value="'.h($J["partition_values"][$z]).'">';}echo'</table>
</div></fieldset>
';}echo'<input type="hidden" name="token" value="',$mi,'">
</form>
',script("qs('#form')['defaults'].onclick();".(support("comment")?" editingCommentsClick(qs('#form')['comments']);":""));}elseif(isset($_GET["indexes"])){$a=$_GET["indexes"];$Hd=array("PRIMARY","UNIQUE","INDEX");$R=table_status($a,true);if(preg_match('~MyISAM|M?aria'.(min_version(5.6,'10.0.5')?'|InnoDB':'').'~i',$R["Engine"]))$Hd[]="FULLTEXT";if(preg_match('~MyISAM|M?aria'.(min_version(5.7,'10.2.2')?'|InnoDB':'').'~i',$R["Engine"]))$Hd[]="SPATIAL";$x=indexes($a);$hg=array();if($y=="mongo"){$hg=$x["_id_"];unset($Hd[0]);unset($x["_id_"]);}$J=$_POST;if($_POST&&!$o&&!$_POST["add"]&&!$_POST["drop_col"]){$c=array();foreach($J["indexes"]as$w){$C=$w["name"];if(in_array($w["type"],$Hd)){$f=array();$se=array();$Vb=array();$O=array();ksort($w["columns"]);foreach($w["columns"]as$z=>$e){if($e!=""){$re=$w["lengths"][$z];$Ub=$w["descs"][$z];$O[]=idf_escape($e).($re?"(".(+$re).")":"").($Ub?" DESC":"");$f[]=$e;$se[]=($re?$re:null);$Vb[]=$Ub;}}if($f){$Bc=$x[$C];if($Bc){ksort($Bc["columns"]);ksort($Bc["lengths"]);ksort($Bc["descs"]);if($w["type"]==$Bc["type"]&&array_values($Bc["columns"])===$f&&(!$Bc["lengths"]||array_values($Bc["lengths"])===$se)&&array_values($Bc["descs"])===$Vb){unset($x[$C]);continue;}}$c[]=array($w["type"],$C,$O);}}}foreach($x
as$C=>$Bc)$c[]=array($Bc["type"],$C,"DROP");if(!$c)redirect(ME."table=".urlencode($a));queries_redirect(ME."table=".urlencode($a),'Indexes have been altered.',alter_indexes($a,$c));}page_header('Indexes',$o,array("table"=>$a),h($a));$q=array_keys(fields($a));if($_POST["add"]){foreach($J["indexes"]as$z=>$w){if($w["columns"][count($w["columns"])]!="")$J["indexes"][$z]["columns"][]="";}$w=end($J["indexes"]);if($w["type"]||array_filter($w["columns"],'strlen'))$J["indexes"][]=array("columns"=>array(1=>""));}if(!$J){foreach($x
as$z=>$w){$x[$z]["name"]=$z;$x[$z]["columns"][]="";}$x[]=array("columns"=>array(1=>""));$J["indexes"]=$x;}echo'
<form action="" method="post">
<div class="scrollable">
<table cellspacing="0" class="nowrap">
<thead><tr>
<th id="label-type">Index Type
<th><input type="submit" class="wayoff">Column (length)
<th id="label-name">Name
<th><noscript>',"<input type='image' class='icon' name='add[0]' src='".h(preg_replace("~\\?.*~","",ME)."?file=plus.gif&version=4.7.1")."' alt='+' title='".'Add next'."'>",'</noscript>
</thead>
';if($hg){echo"<tr><td>PRIMARY<td>";foreach($hg["columns"]as$z=>$e){echo
select_input(" disabled",$q,$e),"<label><input disabled type='checkbox'>".'descending'."</label> ";}echo"<td><td>\n";}$ae=1;foreach($J["indexes"]as$w){if(!$_POST["drop_col"]||$ae!=key($_POST["drop_col"])){echo"<tr><td>".adminer_html_select("indexes[$ae][type]",array(-1=>"")+$Hd,$w["type"],($ae==count($J["indexes"])?"indexesAddRow.call(this);":1),"label-type"),"<td>";ksort($w["columns"]);$t=1;foreach($w["columns"]as$z=>$e){echo"<span>".select_input(" name='indexes[$ae][columns][$t]' title='".'Column'."'",($q?array_combine($q,$q):$q),$e,"partial(".($t==count($w["columns"])?"indexesAddColumn":"indexesChangeColumn").", '".js_escape($y=="sql"?"":$_GET["indexes"]."_")."')"),($y=="sql"||$y=="mssql"?"<input type='number' name='indexes[$ae][lengths][$t]' class='size' value='".h($w["lengths"][$z])."' title='".'Length'."'>":""),(support("descidx")?checkbox("indexes[$ae][descs][$t]",1,$w["descs"][$z],'descending'):"")," </span>";$t++;}echo"<td><input name='indexes[$ae][name]' value='".h($w["name"])."' autocapitalize='off' aria-labelledby='label-name'>\n","<td><input type='image' class='icon' name='drop_col[$ae]' src='".h(preg_replace("~\\?.*~","",ME)."?file=cross.gif&version=4.7.1")."' alt='x' title='".'Remove'."'>".script("qsl('input').onclick = partial(editingRemoveRow, 'indexes\$1[type]');");}$ae++;}echo'</table>
</div>
<p>
<input type="submit" value="Save">
<input type="hidden" name="token" value="',$mi,'">
</form>
';}elseif(isset($_GET["database"])){$J=$_POST;if($_POST&&!$o&&!isset($_POST["add_x"])){$C=trim($J["name"]);if($_POST["drop"]){$_GET["db"]="";queries_redirect(remove_from_uri("db|database"),'Database has been dropped.',drop_databases(array(DB)));}elseif(DB!==$C){if(DB!=""){$_GET["db"]=$C;queries_redirect(preg_replace('~\bdb=[^&]*&~','',ME)."db=".urlencode($C),'Database has been renamed.',rename_database($C,$J["collation"]));}else{$l=explode("\n",str_replace("\r","",$C));$Hh=true;$le="";foreach($l
as$m){if(count($l)==1||$m!=""){if(!create_database($m,$J["collation"]))$Hh=false;$le=$m;}}restart_session();set_session("dbs",null);queries_redirect(ME."db=".urlencode($le),'Database has been created.',$Hh);}}else{if(!$J["collation"])redirect(substr(ME,0,-1));query_redirect("ALTER DATABASE ".idf_escape($C).(preg_match('~^[a-z0-9_]+$~i',$J["collation"])?" COLLATE $J[collation]":""),substr(ME,0,-1),'Database has been altered.');}}page_header(DB!=""?'Alter database':'Create database',$o,array(),h(DB));$ob=collations();$C=DB;if($_POST)$C=$J["name"];elseif(DB!="")$J["collation"]=db_collation(DB,$ob);elseif($y=="sql"){foreach(get_vals("SHOW GRANTS")as$jd){if(preg_match('~ ON (`(([^\\\\`]|``|\\\\.)*)%`\.\*)?~',$jd,$B)&&$B[1]){$C=stripcslashes(idf_unescape("`$B[2]`"));break;}}}echo'
<form action="" method="post">
<p>
',($_POST["add_x"]||strpos($C,"\n")?'<textarea id="name" name="name" rows="10" cols="40">'.h($C).'</textarea><br>':'<input name="name" id="name" value="'.h($C).'" data-maxlength="64" autocapitalize="off">')."\n".($ob?adminer_html_select("collation",array(""=>"(".'collation'.")")+$ob,$J["collation"]).doc_link(array('sql'=>"charset-charsets.html",'mariadb'=>"supported-character-sets-and-collations/",'mssql'=>"ms187963.aspx",)):""),script("focus(qs('#name'));"),'<input type="submit" value="Save">
';if(DB!="")echo"<input type='submit' name='drop' value='".'Drop'."'>".confirm(sprintf('Drop %s?',DB))."\n";elseif(!$_POST["add_x"]&&$_GET["db"]=="")echo"<input type='image' class='icon' name='add' src='".h(preg_replace("~\\?.*~","",ME)."?file=plus.gif&version=4.7.1")."' alt='+' title='".'Add next'."'>\n";echo'<input type="hidden" name="token" value="',$mi,'">
</form>
';}elseif(isset($_GET["scheme"])){$J=$_POST;if($_POST&&!$o){$A=preg_replace('~ns=[^&]*&~','',ME)."ns=";if($_POST["drop"])query_redirect("DROP SCHEMA ".idf_escape($_GET["ns"]),$A,'Schema has been dropped.');else{$C=trim($J["name"]);$A.=urlencode($C);if($_GET["ns"]=="")query_redirect("CREATE SCHEMA ".idf_escape($C),$A,'Schema has been created.');elseif($_GET["ns"]!=$C)query_redirect("ALTER SCHEMA ".idf_escape($_GET["ns"])." RENAME TO ".idf_escape($C),$A,'Schema has been altered.');else
redirect($A);}}page_header($_GET["ns"]!=""?'Alter schema':'Create schema',$o);if(!$J)$J["name"]=$_GET["ns"];echo'
<form action="" method="post">
<p><input name="name" id="name" value="',h($J["name"]),'" autocapitalize="off">
',script("focus(qs('#name'));"),'<input type="submit" value="Save">
';if($_GET["ns"]!="")echo"<input type='submit' name='drop' value='".'Drop'."'>".confirm(sprintf('Drop %s?',$_GET["ns"]))."\n";echo'<input type="hidden" name="token" value="',$mi,'">
</form>
';}elseif(isset($_GET["call"])){$da=($_GET["name"]?$_GET["name"]:$_GET["call"]);page_header('Call'.": ".h($da),$o);$Rg=routine($_GET["call"],(isset($_GET["callf"])?"FUNCTION":"PROCEDURE"));$Fd=array();$Gf=array();foreach($Rg["fields"]as$t=>$p){if(substr($p["inout"],-3)=="OUT")$Gf[$t]="@".idf_escape($p["field"])." AS ".idf_escape($p["field"]);if(!$p["inout"]||substr($p["inout"],0,2)=="IN")$Fd[]=$t;}if(!$o&&$_POST){$Za=array();foreach($Rg["fields"]as$z=>$p){if(in_array($z,$Fd)){$X=process_input($p);if($X===false)$X="''";if(isset($Gf[$z]))$g->query("SET @".idf_escape($p["field"])." = $X");}$Za[]=(isset($Gf[$z])?"@".idf_escape($p["field"]):$X);}$G=(isset($_GET["callf"])?"SELECT":"CALL")." ".table($da)."(".implode(", ",$Za).")";$Ah=microtime(true);$H=$g->multi_query($G);$za=$g->affected_rows;echo$b->selectQuery($G,$Ah,!$H);if(!$H)echo"<p class='error'>".error()."\n";else{$h=connect();if(is_object($h))$h->select_db(DB);do{$H=$g->store_result();if(is_object($H))select($H,$h);else
echo"<p class='message'>".lang(array('Routine has been called, %d row affected.','Routine has been called, %d rows affected.'),$za)."\n";}while($g->next_result());if($Gf)select($g->query("SELECT ".implode(", ",$Gf)));}}echo'
<form action="" method="post">
';if($Fd){echo"<table cellspacing='0' class='layout'>\n";foreach($Fd
as$z){$p=$Rg["fields"][$z];$C=$p["field"];echo"<tr><th>".$b->fieldName($p);$Y=$_POST["fields"][$C];if($Y!=""){if($p["type"]=="enum")$Y=+$Y;if($p["type"]=="set")$Y=array_sum($Y);}input($p,$Y,(string)$_POST["function"][$C]);echo"\n";}echo"</table>\n";}echo'<p>
<input type="submit" value="Call">
<input type="hidden" name="token" value="',$mi,'">
</form>
';}elseif(isset($_GET["foreign"])){$a=$_GET["foreign"];$C=$_GET["name"];$J=$_POST;if($_POST&&!$o&&!$_POST["add"]&&!$_POST["change"]&&!$_POST["change-js"]){$Je=($_POST["drop"]?'Foreign key has been dropped.':($C!=""?'Foreign key has been altered.':'Foreign key has been created.'));$ve=ME."table=".urlencode($a);if(!$_POST["drop"]){$J["source"]=array_filter($J["source"],'strlen');ksort($J["source"]);$Vh=array();foreach($J["source"]as$z=>$X)$Vh[$z]=$J["target"][$z];$J["target"]=$Vh;}if($y=="sqlite")queries_redirect($ve,$Je,recreate_table($a,$a,array(),array(),array(" $C"=>($_POST["drop"]?"":" ".format_foreign_key($J)))));else{$c="ALTER TABLE ".table($a);$dc="\nDROP ".($y=="sql"?"FOREIGN KEY ":"CONSTRAINT ").idf_escape($C);if($_POST["drop"])query_redirect($c.$dc,$ve,$Je);else{query_redirect($c.($C!=""?"$dc,":"")."\nADD".format_foreign_key($J),$ve,$Je);$o='Source and target columns must have the same data type, there must be an index on the target columns and referenced data must exist.'."<br>$o";}}}page_header('Foreign key',$o,array("table"=>$a),h($a));if($_POST){ksort($J["source"]);if($_POST["add"])$J["source"][]="";elseif($_POST["change"]||$_POST["change-js"])$J["target"]=array();}elseif($C!=""){$cd=foreign_keys($a);$J=$cd[$C];$J["source"][]="";}else{$J["table"]=$a;$J["source"]=array("");}$th=array_keys(fields($a));$Vh=($a===$J["table"]?$th:array_keys(fields($J["table"])));$Ag=array_keys(array_filter(table_status('',true),'fk_support'));echo'
<form action="" method="post">
<p>
';if($J["db"]==""&&$J["ns"]==""){echo'Target table:
',adminer_html_select("table",$Ag,$J["table"],"this.form['change-js'].value = '1'; this.form.submit();"),'<input type="hidden" name="change-js" value="">
<noscript><p><input type="submit" name="change" value="Change"></noscript>
<table cellspacing="0">
<thead><tr><th id="label-source">Source<th id="label-target">Target</thead>
';$ae=0;foreach($J["source"]as$z=>$X){echo"<tr>","<td>".adminer_html_select("source[".(+$z)."]",array(-1=>"")+$th,$X,($ae==count($J["source"])-1?"foreignAddRow.call(this);":1),"label-source"),"<td>".html_select("target[".(+$z)."]",$Vh,$J["target"][$z],1,"label-target");$ae++;}echo'</table>
<p>
ON DELETE: ',adminer_html_select("on_delete",array(-1=>"")+explode("|",$nf),$J["on_delete"]),' ON UPDATE: ',adminer_html_select("on_update",array(-1=>"")+explode("|",$nf),$J["on_update"]),doc_link(array('sql'=>"innodb-foreign-key-constraints.html",'mariadb'=>"foreign-keys/",'pgsql'=>"sql-createtable.html#SQL-CREATETABLE-REFERENCES",'mssql'=>"ms174979.aspx",'oracle'=>"clauses002.htm#sthref2903",)),'<p>
<input type="submit" value="Save">
<noscript><p><input type="submit" name="add" value="Add column"></noscript>
';}if($C!=""){echo'<input type="submit" name="drop" value="Drop">',confirm(sprintf('Drop %s?',$C));}echo'<input type="hidden" name="token" value="',$mi,'">
</form>
';}elseif(isset($_GET["view"])){$a=$_GET["view"];$J=$_POST;$Df="VIEW";if($y=="pgsql"&&$a!=""){$Ch=table_status($a);$Df=strtoupper($Ch["Engine"]);}if($_POST&&!$o){$C=trim($J["name"]);$Ga=" AS\n$J[select]";$ve=ME."table=".urlencode($C);$Je='View has been altered.';$T=($_POST["materialized"]?"MATERIALIZED VIEW":"VIEW");if(!$_POST["drop"]&&$a==$C&&$y!="sqlite"&&$T=="VIEW"&&$Df=="VIEW")query_redirect(($y=="mssql"?"ALTER":"CREATE OR REPLACE")." VIEW ".table($C).$Ga,$ve,$Je);else{$Xh=$C."_adminer_".uniqid();drop_create("DROP $Df ".table($a),"CREATE $T ".table($C).$Ga,"DROP $T ".table($C),"CREATE $T ".table($Xh).$Ga,"DROP $T ".table($Xh),($_POST["drop"]?substr(ME,0,-1):$ve),'View has been dropped.',$Je,'View has been created.',$a,$C);}}if(!$_POST&&$a!=""){$J=view($a);$J["name"]=$a;$J["materialized"]=($Df!="VIEW");if(!$o)$o=error();}page_header(($a!=""?'Alter view':'Create view'),$o,array("table"=>$a),h($a));echo'
<form action="" method="post">
<p>Name: <input name="name" value="',h($J["name"]),'" data-maxlength="64" autocapitalize="off">
',(support("materializedview")?" ".checkbox("materialized",1,$J["materialized"],'Materialized view'):""),'<p>';textarea("select",$J["select"]);echo'<p>
<input type="submit" value="Save">
';if($a!=""){echo'<input type="submit" name="drop" value="Drop">',confirm(sprintf('Drop %s?',$a));}echo'<input type="hidden" name="token" value="',$mi,'">
</form>
';}elseif(isset($_GET["event"])){$aa=$_GET["event"];$Sd=array("YEAR","QUARTER","MONTH","DAY","HOUR","MINUTE","WEEK","SECOND","YEAR_MONTH","DAY_HOUR","DAY_MINUTE","DAY_SECOND","HOUR_MINUTE","HOUR_SECOND","MINUTE_SECOND");$Dh=array("ENABLED"=>"ENABLE","DISABLED"=>"DISABLE","SLAVESIDE_DISABLED"=>"DISABLE ON SLAVE");$J=$_POST;if($_POST&&!$o){if($_POST["drop"])query_redirect("DROP EVENT ".idf_escape($aa),substr(ME,0,-1),'Event has been dropped.');elseif(in_array($J["INTERVAL_FIELD"],$Sd)&&isset($Dh[$J["STATUS"]])){$Wg="\nON SCHEDULE ".($J["INTERVAL_VALUE"]?"EVERY ".q($J["INTERVAL_VALUE"])." $J[INTERVAL_FIELD]".($J["STARTS"]?" STARTS ".q($J["STARTS"]):"").($J["ENDS"]?" ENDS ".q($J["ENDS"]):""):"AT ".q($J["STARTS"]))." ON COMPLETION".($J["ON_COMPLETION"]?"":" NOT")." PRESERVE";queries_redirect(substr(ME,0,-1),($aa!=""?'Event has been altered.':'Event has been created.'),queries(($aa!=""?"ALTER EVENT ".idf_escape($aa).$Wg.($aa!=$J["EVENT_NAME"]?"\nRENAME TO ".idf_escape($J["EVENT_NAME"]):""):"CREATE EVENT ".idf_escape($J["EVENT_NAME"]).$Wg)."\n".$Dh[$J["STATUS"]]." COMMENT ".q($J["EVENT_COMMENT"]).rtrim(" DO\n$J[EVENT_DEFINITION]",";").";"));}}page_header(($aa!=""?'Alter event'.": ".h($aa):'Create event'),$o);if(!$J&&$aa!=""){$K=get_rows("SELECT * FROM information_schema.EVENTS WHERE EVENT_SCHEMA = ".q(DB)." AND EVENT_NAME = ".q($aa));$J=reset($K);}echo'
<form action="" method="post">
<table cellspacing="0" class="layout">
<tr><th>Name<td><input name="EVENT_NAME" value="',h($J["EVENT_NAME"]),'" data-maxlength="64" autocapitalize="off">
<tr><th title="datetime">Start<td><input name="STARTS" value="',h("$J[EXECUTE_AT]$J[STARTS]"),'">
<tr><th title="datetime">End<td><input name="ENDS" value="',h($J["ENDS"]),'">
<tr><th>Every<td><input type="number" name="INTERVAL_VALUE" value="',h($J["INTERVAL_VALUE"]),'" class="size"> ',adminer_html_select("INTERVAL_FIELD",$Sd,$J["INTERVAL_FIELD"]),'<tr><th>Status<td>',adminer_html_select("STATUS",$Dh,$J["STATUS"]),'<tr><th>Comment<td><input name="EVENT_COMMENT" value="',h($J["EVENT_COMMENT"]),'" data-maxlength="64">
<tr><th><td>',checkbox("ON_COMPLETION","PRESERVE",$J["ON_COMPLETION"]=="PRESERVE",'On completion preserve'),'</table>
<p>';textarea("EVENT_DEFINITION",$J["EVENT_DEFINITION"]);echo'<p>
<input type="submit" value="Save">
';if($aa!=""){echo'<input type="submit" name="drop" value="Drop">',confirm(sprintf('Drop %s?',$aa));}echo'<input type="hidden" name="token" value="',$mi,'">
</form>
';}elseif(isset($_GET["procedure"])){$da=($_GET["name"]?$_GET["name"]:$_GET["procedure"]);$Rg=(isset($_GET["function"])?"FUNCTION":"PROCEDURE");$J=$_POST;$J["fields"]=(array)$J["fields"];if($_POST&&!process_fields($J["fields"])&&!$o){$Af=routine($_GET["procedure"],$Rg);$Xh="$J[name]_adminer_".uniqid();drop_create("DROP $Rg ".routine_id($da,$Af),create_routine($Rg,$J),"DROP $Rg ".routine_id($J["name"],$J),create_routine($Rg,array("name"=>$Xh)+$J),"DROP $Rg ".routine_id($Xh,$J),substr(ME,0,-1),'Routine has been dropped.','Routine has been altered.','Routine has been created.',$da,$J["name"]);}page_header(($da!=""?(isset($_GET["function"])?'Alter function':'Alter procedure').": ".h($da):(isset($_GET["function"])?'Create function':'Create procedure')),$o);if(!$_POST&&$da!=""){$J=routine($_GET["procedure"],$Rg);$J["name"]=$da;}$ob=get_vals("SHOW CHARACTER SET");sort($ob);$Sg=routine_languages();echo'
<form action="" method="post" id="form">
<p>Name: <input name="name" value="',h($J["name"]),'" data-maxlength="64" autocapitalize="off">
',($Sg?'Language'.": ".adminer_html_select("language",$Sg,$J["language"])."\n":""),'<input type="submit" value="Save">
<div class="scrollable">
<table cellspacing="0" class="nowrap">
';edit_fields($J["fields"],$ob,$Rg);if(isset($_GET["function"])){echo"<tr><td>".'Return type';edit_type("returns",$J["returns"],$ob,array(),($y=="pgsql"?array("void","trigger"):array()));}echo'</table>
</div>
<p>';textarea("definition",$J["definition"]);echo'<p>
<input type="submit" value="Save">
';if($da!=""){echo'<input type="submit" name="drop" value="Drop">',confirm(sprintf('Drop %s?',$da));}echo'<input type="hidden" name="token" value="',$mi,'">
</form>
';}elseif(isset($_GET["sequence"])){$fa=$_GET["sequence"];$J=$_POST;if($_POST&&!$o){$A=substr(ME,0,-1);$C=trim($J["name"]);if($_POST["drop"])query_redirect("DROP SEQUENCE ".idf_escape($fa),$A,'Sequence has been dropped.');elseif($fa=="")query_redirect("CREATE SEQUENCE ".idf_escape($C),$A,'Sequence has been created.');elseif($fa!=$C)query_redirect("ALTER SEQUENCE ".idf_escape($fa)." RENAME TO ".idf_escape($C),$A,'Sequence has been altered.');else
redirect($A);}page_header($fa!=""?'Alter sequence'.": ".h($fa):'Create sequence',$o);if(!$J)$J["name"]=$fa;echo'
<form action="" method="post">
<p><input name="name" value="',h($J["name"]),'" autocapitalize="off">
<input type="submit" value="Save">
';if($fa!="")echo"<input type='submit' name='drop' value='".'Drop'."'>".confirm(sprintf('Drop %s?',$fa))."\n";echo'<input type="hidden" name="token" value="',$mi,'">
</form>
';}elseif(isset($_GET["type"])){$ga=$_GET["type"];$J=$_POST;if($_POST&&!$o){$A=substr(ME,0,-1);if($_POST["drop"])query_redirect("DROP TYPE ".idf_escape($ga),$A,'Type has been dropped.');else
query_redirect("CREATE TYPE ".idf_escape(trim($J["name"]))." $J[as]",$A,'Type has been created.');}page_header($ga!=""?'Alter type'.": ".h($ga):'Create type',$o);if(!$J)$J["as"]="AS ";echo'
<form action="" method="post">
<p>
';if($ga!="")echo"<input type='submit' name='drop' value='".'Drop'."'>".confirm(sprintf('Drop %s?',$ga))."\n";else{echo"<input name='name' value='".h($J['name'])."' autocapitalize='off'>\n";textarea("as",$J["as"]);echo"<p><input type='submit' value='".'Save'."'>\n";}echo'<input type="hidden" name="token" value="',$mi,'">
</form>
';}elseif(isset($_GET["trigger"])){$a=$_GET["trigger"];$C=$_GET["name"];$xi=trigger_options();$J=(array)trigger($C)+array("Trigger"=>$a."_bi");if($_POST){if(!$o&&in_array($_POST["Timing"],$xi["Timing"])&&in_array($_POST["Event"],$xi["Event"])&&in_array($_POST["Type"],$xi["Type"])){$mf=" ON ".table($a);$dc="DROP TRIGGER ".idf_escape($C).($y=="pgsql"?$mf:"");$ve=ME."table=".urlencode($a);if($_POST["drop"])query_redirect($dc,$ve,'Trigger has been dropped.');else{if($C!="")queries($dc);queries_redirect($ve,($C!=""?'Trigger has been altered.':'Trigger has been created.'),queries(create_trigger($mf,$_POST)));if($C!="")queries(create_trigger($mf,$J+array("Type"=>reset($xi["Type"]))));}}$J=$_POST;}page_header(($C!=""?'Alter trigger'.": ".h($C):'Create trigger'),$o,array("table"=>$a));echo'
<form action="" method="post" id="form">
<table cellspacing="0" class="layout">
<tr><th>Time<td>',adminer_html_select("Timing",$xi["Timing"],$J["Timing"],"triggerChange(/^".preg_quote($a,"/")."_[ba][iud]$/, '".js_escape($a)."', this.form);"),'<tr><th>Event<td>',adminer_html_select("Event",$xi["Event"],$J["Event"],"this.form['Timing'].onchange();"),(in_array("UPDATE OF",$xi["Event"])?" <input name='Of' value='".h($J["Of"])."' class='hidden'>":""),'<tr><th>Type<td>',adminer_html_select("Type",$xi["Type"],$J["Type"]),'</table>
<p>Name: <input name="Trigger" value="',h($J["Trigger"]),'" data-maxlength="64" autocapitalize="off">
',script("qs('#form')['Timing'].onchange();"),'<p>';textarea("Statement",$J["Statement"]);echo'<p>
<input type="submit" value="Save">
';if($C!=""){echo'<input type="submit" name="drop" value="Drop">',confirm(sprintf('Drop %s?',$C));}echo'<input type="hidden" name="token" value="',$mi,'">
</form>
';}elseif(isset($_GET["user"])){$ha=$_GET["user"];$mg=array(""=>array("All privileges"=>""));foreach(get_rows("SHOW PRIVILEGES")as$J){foreach(explode(",",($J["Privilege"]=="Grant option"?"":$J["Context"]))as$_b)$mg[$_b][$J["Privilege"]]=$J["Comment"];}$mg["Server Admin"]+=$mg["File access on server"];$mg["Databases"]["Create routine"]=$mg["Procedures"]["Create routine"];unset($mg["Procedures"]["Create routine"]);$mg["Columns"]=array();foreach(array("Select","Insert","Update","References")as$X)$mg["Columns"][$X]=$mg["Tables"][$X];unset($mg["Server Admin"]["Usage"]);foreach($mg["Tables"]as$z=>$X)unset($mg["Databases"][$z]);$We=array();if($_POST){foreach($_POST["objects"]as$z=>$X)$We[$X]=(array)$We[$X]+(array)$_POST["grants"][$z];}$kd=array();$kf="";if(isset($_GET["host"])&&($H=$g->query("SHOW GRANTS FOR ".q($ha)."@".q($_GET["host"])))){while($J=$H->fetch_row()){if(preg_match('~GRANT (.*) ON (.*) TO ~',$J[0],$B)&&preg_match_all('~ *([^(,]*[^ ,(])( *\([^)]+\))?~',$B[1],$Be,PREG_SET_ORDER)){foreach($Be
as$X){if($X[1]!="USAGE")$kd["$B[2]$X[2]"][$X[1]]=true;if(preg_match('~ WITH GRANT OPTION~',$J[0]))$kd["$B[2]$X[2]"]["GRANT OPTION"]=true;}}if(preg_match("~ IDENTIFIED BY PASSWORD '([^']+)~",$J[0],$B))$kf=$B[1];}}if($_POST&&!$o){$lf=(isset($_GET["host"])?q($ha)."@".q($_GET["host"]):"''");if($_POST["drop"])query_redirect("DROP USER $lf",ME."privileges=",'User has been dropped.');else{$Ye=q($_POST["user"])."@".q($_POST["host"]);$Uf=$_POST["pass"];if($Uf!=''&&!$_POST["hashed"]){$Uf=$g->result("SELECT PASSWORD(".q($Uf).")");$o=!$Uf;}$Eb=false;if(!$o){if($lf!=$Ye){$Eb=queries((min_version(5)?"CREATE USER":"GRANT USAGE ON *.* TO")." $Ye IDENTIFIED BY PASSWORD ".q($Uf));$o=!$Eb;}elseif($Uf!=$kf)queries("SET PASSWORD FOR $Ye = ".q($Uf));}if(!$o){$Og=array();foreach($We
as$ff=>$jd){if(isset($_GET["grant"]))$jd=array_filter($jd);$jd=array_keys($jd);if(isset($_GET["grant"]))$Og=array_diff(array_keys(array_filter($We[$ff],'strlen')),$jd);elseif($lf==$Ye){$if=array_keys((array)$kd[$ff]);$Og=array_diff($if,$jd);$jd=array_diff($jd,$if);unset($kd[$ff]);}if(preg_match('~^(.+)\s*(\(.*\))?$~U',$ff,$B)&&(!grant("REVOKE",$Og,$B[2]," ON $B[1] FROM $Ye")||!grant("GRANT",$jd,$B[2]," ON $B[1] TO $Ye"))){$o=true;break;}}}if(!$o&&isset($_GET["host"])){if($lf!=$Ye)queries("DROP USER $lf");elseif(!isset($_GET["grant"])){foreach($kd
as$ff=>$Og){if(preg_match('~^(.+)(\(.*\))?$~U',$ff,$B))grant("REVOKE",array_keys($Og),$B[2]," ON $B[1] FROM $Ye");}}}queries_redirect(ME."privileges=",(isset($_GET["host"])?'User has been altered.':'User has been created.'),!$o);if($Eb)$g->query("DROP USER $Ye");}}page_header((isset($_GET["host"])?'Username'.": ".h("$ha@$_GET[host]"):'Create user'),$o,array("privileges"=>array('','Privileges')));if($_POST){$J=$_POST;$kd=$We;}else{$J=$_GET+array("host"=>$g->result("SELECT SUBSTRING_INDEX(CURRENT_USER, '@', -1)"));$J["pass"]=$kf;if($kf!="")$J["hashed"]=true;$kd[(DB==""||$kd?"":idf_escape(addcslashes(DB,"%_\\"))).".*"]=array();}echo'<form action="" method="post">
<table cellspacing="0" class="layout">
<tr><th>Server<td><input name="host" data-maxlength="60" value="',h($J["host"]),'" autocapitalize="off">
<tr><th>Username<td><input name="user" data-maxlength="80" value="',h($J["user"]),'" autocapitalize="off">
<tr><th>Password<td><input name="pass" id="pass" value="',h($J["pass"]),'" autocomplete="new-password">
';if(!$J["hashed"])echo
script("typePassword(qs('#pass'));");echo
checkbox("hashed",1,$J["hashed"],'Hashed',"typePassword(this.form['pass'], this.checked);"),'</table>

';echo"<table cellspacing='0'>\n","<thead><tr><th colspan='2'>".'Privileges'.doc_link(array('sql'=>"grant.html#priv_level"));$t=0;foreach($kd
as$ff=>$jd){echo'<th>'.($ff!="*.*"?"<input name='objects[$t]' value='".h($ff)."' size='10' autocapitalize='off'>":"<input type='hidden' name='objects[$t]' value='*.*' size='10'>*.*");$t++;}echo"</thead>\n";foreach(array(""=>"","Server Admin"=>'Server',"Databases"=>'Database',"Tables"=>'Table',"Columns"=>'Column',"Procedures"=>'Routine',)as$_b=>$Ub){foreach((array)$mg[$_b]as$lg=>$tb){echo"<tr".odd()."><td".($Ub?">$Ub<td":" colspan='2'").' lang="en" title="'.h($tb).'">'.h($lg);$t=0;foreach($kd
as$ff=>$jd){$C="'grants[$t][".h(strtoupper($lg))."]'";$Y=$jd[strtoupper($lg)];if($_b=="Server Admin"&&$ff!=(isset($kd["*.*"])?"*.*":".*"))echo"<td>";elseif(isset($_GET["grant"]))echo"<td><select name=$C><option><option value='1'".($Y?" selected":"").">".'Grant'."<option value='0'".($Y=="0"?" selected":"").">".'Revoke'."</select>";else{echo"<td align='center'><label class='block'>","<input type='checkbox' name=$C value='1'".($Y?" checked":"").($lg=="All privileges"?" id='grants-$t-all'>":">".($lg=="Grant option"?"":script("qsl('input').onclick = function () { if (this.checked) formUncheck('grants-$t-all'); };"))),"</label>";}$t++;}}}echo"</table>\n",'<p>
<input type="submit" value="Save">
';if(isset($_GET["host"])){echo'<input type="submit" name="drop" value="Drop">',confirm(sprintf('Drop %s?',"$ha@$_GET[host]"));}echo'<input type="hidden" name="token" value="',$mi,'">
</form>
';}elseif(isset($_GET["processlist"])){if(support("kill")&&$_POST&&!$o){$he=0;foreach((array)$_POST["kill"]as$X){if(kill_process($X))$he++;}queries_redirect(ME."processlist=",lang(array('%d process has been killed.','%d processes have been killed.'),$he),$he||!$_POST["kill"]);}page_header('Process list',$o);echo'
<form action="" method="post">
<div class="scrollable">
<table cellspacing="0" class="nowrap checkable">
',script("mixin(qsl('table'), {onclick: tableClick, ondblclick: partialArg(tableClick, true)});");$t=-1;foreach(process_list()as$t=>$J){if(!$t){echo"<thead><tr lang='en'>".(support("kill")?"<th>":"");foreach($J
as$z=>$X)echo"<th>$z".doc_link(array('sql'=>"show-processlist.html#processlist_".strtolower($z),'pgsql'=>"monitoring-stats.html#PG-STAT-ACTIVITY-VIEW",'oracle'=>"../b14237/dynviews_2088.htm",));echo"</thead>\n";}echo"<tr".odd().">".(support("kill")?"<td>".checkbox("kill[]",$J[$y=="sql"?"Id":"pid"],0):"");foreach($J
as$z=>$X)echo"<td>".(($y=="sql"&&$z=="Info"&&preg_match("~Query|Killed~",$J["Command"])&&$X!="")||($y=="pgsql"&&$z=="current_query"&&$X!="<IDLE>")||($y=="oracle"&&$z=="sql_text"&&$X!="")?"<code class='jush-$y'>".shorten_utf8($X,100,"</code>").' <a href="'.h(ME.($J["db"]!=""?"db=".urlencode($J["db"])."&":"")."sql=".urlencode($X)).'">'.'Clone'.'</a>':h($X));echo"\n";}echo'</table>
</div>
<p>
';if(support("kill")){echo($t+1)."/".sprintf('%d in total',max_connections()),"<p><input type='submit' value='".'Kill'."'>\n";}echo'<input type="hidden" name="token" value="',$mi,'">
</form>
',script("tableCheck();");}elseif(isset($_GET["select"])){$a=$_GET["select"];$R=table_status1($a);$x=indexes($a);$q=fields($a);$cd=column_foreign_keys($a);$hf=$R["Oid"];parse_str($_COOKIE["adminer_import"],$ya);$Pg=array();$f=array();$bi=null;foreach($q
as$z=>$p){$C=$b->fieldName($p);if(isset($p["privileges"]["select"])&&$C!=""){$f[$z]=html_entity_decode(strip_tags($C),ENT_QUOTES);if(is_shortable($p))$bi=$b->selectLengthProcess();}$Pg+=$p["privileges"];}list($L,$ld)=$b->selectColumnsProcess($f,$x);$Wd=count($ld)<count($L);$Z=$b->selectSearchProcess($q,$x);$xf=$b->selectOrderProcess($q,$x);$_=$b->selectLimitProcess();if($_GET["val"]&&is_ajax()){header("Content-Type: text/plain; charset=utf-8");foreach($_GET["val"]as$Di=>$J){$Ga=convert_field($q[key($J)]);$L=array($Ga?$Ga:idf_escape(key($J)));$Z[]=where_check($Di,$q);$I=$n->select($a,$L,$Z,$L);if($I)echo
reset($I->fetch_row());}exit;}$hg=$Fi=null;foreach($x
as$w){if($w["type"]=="PRIMARY"){$hg=array_flip($w["columns"]);$Fi=($L?$hg:array());foreach($Fi
as$z=>$X){if(in_array(idf_escape($z),$L))unset($Fi[$z]);}break;}}if($hf&&!$hg){$hg=$Fi=array($hf=>0);$x[]=array("type"=>"PRIMARY","columns"=>array($hf));}if($_POST&&!$o){$gj=$Z;if(!$_POST["all"]&&is_array($_POST["check"])){$fb=array();foreach($_POST["check"]as$cb)$fb[]=where_check($cb,$q);$gj[]="((".implode(") OR (",$fb)."))";}$gj=($gj?"\nWHERE ".implode(" AND ",$gj):"");if($_POST["export"]){cookie("adminer_import","output=".urlencode($_POST["output"])."&format=".urlencode($_POST["format"]));dump_headers($a);$b->dumpTable($a,"");$hd=($L?implode(", ",$L):"*").convert_fields($f,$q,$L)."\nFROM ".table($a);$nd=($ld&&$Wd?"\nGROUP BY ".implode(", ",$ld):"").($xf?"\nORDER BY ".implode(", ",$xf):"");if(!is_array($_POST["check"])||$hg)$G="SELECT $hd$gj$nd";else{$Bi=array();foreach($_POST["check"]as$X)$Bi[]="(SELECT".limit($hd,"\nWHERE ".($Z?implode(" AND ",$Z)." AND ":"").where_check($X,$q).$nd,1).")";$G=implode(" UNION ALL ",$Bi);}$b->dumpData($a,"table",$G);exit;}if(!$b->selectEmailProcess($Z,$cd)){if($_POST["save"]||$_POST["delete"]){$H=true;$za=0;$O=array();if(!$_POST["delete"]){foreach($f
as$C=>$X){$X=process_input($q[$C]);if($X!==null&&($_POST["clone"]||$X!==false))$O[idf_escape($C)]=($X!==false?$X:idf_escape($C));}}if($_POST["delete"]||$O){if($_POST["clone"])$G="INTO ".table($a)." (".implode(", ",array_keys($O)).")\nSELECT ".implode(", ",$O)."\nFROM ".table($a);if($_POST["all"]||($hg&&is_array($_POST["check"]))||$Wd){$H=($_POST["delete"]?$n->delete($a,$gj):($_POST["clone"]?queries("INSERT $G$gj"):$n->update($a,$O,$gj)));$za=$g->affected_rows;}else{foreach((array)$_POST["check"]as$X){$cj="\nWHERE ".($Z?implode(" AND ",$Z)." AND ":"").where_check($X,$q);$H=($_POST["delete"]?$n->delete($a,$cj,1):($_POST["clone"]?queries("INSERT".limit1($a,$G,$cj)):$n->update($a,$O,$cj,1)));if(!$H)break;$za+=$g->affected_rows;}}}$Je=lang(array('%d item has been affected.','%d items have been affected.'),$za);if($_POST["clone"]&&$H&&$za==1){$me=last_id();if($me)$Je=sprintf('Item%s has been inserted.'," $me");}queries_redirect(remove_from_uri($_POST["all"]&&$_POST["delete"]?"page":""),$Je,$H);if(!$_POST["delete"]){edit_form($a,$q,(array)$_POST["fields"],!$_POST["clone"]);page_footer();exit;}}elseif(!$_POST["import"]){if(!$_POST["val"])$o='Ctrl+click on a value to modify it.';else{$H=true;$za=0;foreach($_POST["val"]as$Di=>$J){$O=array();foreach($J
as$z=>$X){$z=bracket_escape($z,1);$O[idf_escape($z)]=(preg_match('~char|text~',$q[$z]["type"])||$X!=""?$b->processInput($q[$z],$X):"NULL");}$H=$n->update($a,$O," WHERE ".($Z?implode(" AND ",$Z)." AND ":"").where_check($Di,$q),!$Wd&&!$hg," ");if(!$H)break;$za+=$g->affected_rows;}queries_redirect(remove_from_uri(),lang(array('%d item has been affected.','%d items have been affected.'),$za),$H);}}elseif(!is_string($Rc=get_file("csv_file",true)))$o=upload_error($Rc);elseif(!preg_match('~~u',$Rc))$o='File must be in UTF-8 encoding.';else{cookie("adminer_import","output=".urlencode($ya["output"])."&format=".urlencode($_POST["separator"]));$H=true;$qb=array_keys($q);preg_match_all('~(?>"[^"]*"|[^"\r\n]+)+~',$Rc,$Be);$za=count($Be[0]);$n->begin();$M=($_POST["separator"]=="csv"?",":($_POST["separator"]=="tsv"?"\t":";"));$K=array();foreach($Be[0]as$z=>$X){preg_match_all("~((?>\"[^\"]*\")+|[^$M]*)$M~",$X.$M,$Ce);if(!$z&&!array_diff($Ce[1],$qb)){$qb=$Ce[1];$za--;}else{$O=array();foreach($Ce[1]as$t=>$mb)$O[idf_escape($qb[$t])]=($mb==""&&$q[$qb[$t]]["null"]?"NULL":q(str_replace('""','"',preg_replace('~^"|"$~','',$mb))));$K[]=$O;}}$H=(!$K||$n->insertUpdate($a,$K,$hg));if($H)$H=$n->commit();queries_redirect(remove_from_uri("page"),lang(array('%d row has been imported.','%d rows have been imported.'),$za),$H);$n->rollback();}}}$Nh=$b->tableName($R);if(is_ajax()){page_headers();ob_start();}else
page_header('Select'.": $Nh",$o);$O=null;if(isset($Pg["insert"])||!support("table")){$O="";foreach((array)$_GET["where"]as$X){if($cd[$X["col"]]&&count($cd[$X["col"]])==1&&($X["op"]=="="||(!$X["op"]&&!preg_match('~[_%]~',$X["val"]))))$O.="&set".urlencode("[".bracket_escape($X["col"])."]")."=".urlencode($X["val"]);}}$b->selectLinks($R,$O);if(!$f&&support("table"))echo"<p class='error'>".'Unable to select the table'.($q?".":": ".error())."\n";else{echo"<form action='' id='form'>\n","<div style='display: none;'>";hidden_fields_get();echo(DB!=""?'<input type="hidden" name="db" value="'.h(DB).'">'.(isset($_GET["ns"])?'<input type="hidden" name="ns" value="'.h($_GET["ns"]).'">':""):"");echo'<input type="hidden" name="select" value="'.h($a).'">',"</div>\n";$b->selectColumnsPrint($L,$f);$b->selectSearchPrint($Z,$f,$x);$b->selectOrderPrint($xf,$f,$x);$b->selectLimitPrint($_);$b->selectLengthPrint($bi);$b->selectActionPrint($x);echo"</form>\n";$E=$_GET["page"];if($E=="last"){$fd=$g->result(count_rows($a,$Z,$Wd,$ld));$E=floor(max(0,$fd-1)/$_);}$bh=$L;$md=$ld;if(!$bh){$bh[]="*";$Ab=convert_fields($f,$q,$L);if($Ab)$bh[]=substr($Ab,2);}foreach($L
as$z=>$X){$p=$q[idf_unescape($X)];if($p&&($Ga=convert_field($p)))$bh[$z]="$Ga AS $X";}if(!$Wd&&$Fi){foreach($Fi
as$z=>$X){$bh[]=idf_escape($z);if($md)$md[]=idf_escape($z);}}$H=$n->select($a,$bh,$Z,$md,$xf,$_,$E,true);if(!$H)echo"<p class='error'>".error()."\n";else{if($y=="mssql"&&$E)$H->seek($_*$E);$qc=array();echo"<form action='' method='post' enctype='multipart/form-data'>\n";$K=array();while($J=$H->fetch_assoc()){if($E&&$y=="oracle")unset($J["RNUM"]);$K[]=$J;}if($_GET["page"]!="last"&&$_!=""&&$ld&&$Wd&&$y=="sql")$fd=$g->result(" SELECT FOUND_ROWS()");if(!$K)echo"<p class='message'>".'No rows.'."\n";else{$Pa=$b->backwardKeys($a,$Nh);echo"<div class='scrollable'>","<table id='table' cellspacing='0' class='nowrap checkable'>",script("mixin(qs('#table'), {onclick: tableClick, ondblclick: partialArg(tableClick, true), onkeydown: editingKeydown});"),"<thead><tr>".(!$ld&&$L?"":"<td><input type='checkbox' id='all-page' class='jsonly'>".script("qs('#all-page').onclick = partial(formCheck, /check/);","")." <a href='".h($_GET["modify"]?remove_from_uri("modify"):$_SERVER["REQUEST_URI"]."&modify=1")."'>".'Modify'."</a>");$Ve=array();$id=array();reset($L);$wg=1;foreach($K[0]as$z=>$X){if(!isset($Fi[$z])){$X=$_GET["columns"][key($L)];$p=$q[$L?($X?$X["col"]:current($L)):$z];$C=($p?$b->fieldName($p,$wg):($X["fun"]?"*":$z));if($C!=""){$wg++;$Ve[$z]=$C;$e=idf_escape($z);$_d=remove_from_uri('(order|desc)[^=]*|page').'&order%5B0%5D='.urlencode($z);$Ub="&desc%5B0%5D=1";echo"<th>".script("mixin(qsl('th'), {onmouseover: partial(columnMouse), onmouseout: partial(columnMouse, ' hidden')});",""),'<a href="'.h($_d.($xf[0]==$e||$xf[0]==$z||(!$xf&&$Wd&&$ld[0]==$e)?$Ub:'')).'">';echo
apply_sql_function($X["fun"],$C)."</a>";echo"<span class='column hidden'>","<a href='".h($_d.$Ub)."' title='".'descending'."' class='text'> ‚Üì</a>";if(!$X["fun"]){echo'<a href="#fieldset-search" title="'.'Search'.'" class="text jsonly"> =</a>',script("qsl('a').onclick = partial(selectSearch, '".js_escape($z)."');");}echo"</span>";}$id[$z]=$X["fun"];next($L);}}$se=array();if($_GET["modify"]){foreach($K
as$J){foreach($J
as$z=>$X)$se[$z]=max($se[$z],min(40,strlen(utf8_decode($X))));}}echo($Pa?"<th>".'Relations':"")."</thead>\n";if(is_ajax()){if($_%2==1&&$E%2==1)odd();ob_end_clean();}foreach($b->rowDescriptions($K,$cd)as$Ue=>$J){$Ci=unique_array($K[$Ue],$x);if(!$Ci){$Ci=array();foreach($K[$Ue]as$z=>$X){if(!preg_match('~^(COUNT\((\*|(DISTINCT )?`(?:[^`]|``)+`)\)|(AVG|GROUP_CONCAT|MAX|MIN|SUM)\(`(?:[^`]|``)+`\))$~',$z))$Ci[$z]=$X;}}$Di="";foreach($Ci
as$z=>$X){if(($y=="sql"||$y=="pgsql")&&preg_match('~char|text|enum|set~',$q[$z]["type"])&&strlen($X)>64){$z=(strpos($z,'(')?$z:idf_escape($z));$z="MD5(".($y!='sql'||preg_match("~^utf8~",$q[$z]["collation"])?$z:"CONVERT($z USING ".charset($g).")").")";$X=md5($X);}$Di.="&".($X!==null?urlencode("where[".bracket_escape($z)."]")."=".urlencode($X):"null%5B%5D=".urlencode($z));}echo"<tr".odd().">".(!$ld&&$L?"":"<td>".checkbox("check[]",substr($Di,1),in_array(substr($Di,1),(array)$_POST["check"])).($Wd||information_schema(DB)?"":" <a href='".h(ME."edit=".urlencode($a).$Di)."' class='edit'>".'edit'."</a>"));foreach($J
as$z=>$X){if(isset($Ve[$z])){$p=$q[$z];$X=$n->value($X,$p);if($X!=""&&(!isset($qc[$z])||$qc[$z]!=""))$qc[$z]=(is_mail($X)?$Ve[$z]:"");$A="";if(preg_match('~blob|bytea|raw|file~',$p["type"])&&$X!="")$A=ME.'download='.urlencode($a).'&field='.urlencode($z).$Di;if(!$A&&$X!==null){foreach((array)$cd[$z]as$r){if(count($cd[$z])==1||end($r["source"])==$z){$A="";foreach($r["source"]as$t=>$th)$A.=where_link($t,$r["target"][$t],$K[$Ue][$th]);$A=($r["db"]!=""?preg_replace('~([?&]db=)[^&]+~','\1'.urlencode($r["db"]),ME):ME).'select='.urlencode($r["table"]).$A;if($r["ns"])$A=preg_replace('~([?&]ns=)[^&]+~','\1'.urlencode($r["ns"]),$A);if(count($r["source"])==1)break;}}}if($z=="COUNT(*)"){$A=ME."select=".urlencode($a);$t=0;foreach((array)$_GET["where"]as$W){if(!array_key_exists($W["col"],$Ci))$A.=where_link($t++,$W["col"],$W["val"],$W["op"]);}foreach($Ci
as$be=>$W)$A.=where_link($t++,$be,$W);}$X=select_value($X,$A,$p,$bi);$u=h("val[$Di][".bracket_escape($z)."]");$Y=$_POST["val"][$Di][bracket_escape($z)];$lc=!is_array($J[$z])&&is_utf8($X)&&$K[$Ue][$z]==$J[$z]&&!$id[$z];$ai=preg_match('~text|lob~',$p["type"]);if(($_GET["modify"]&&$lc)||$Y!==null){$qd=h($Y!==null?$Y:$J[$z]);echo"<td>".($ai?"<textarea name='$u' cols='30' rows='".(substr_count($J[$z],"\n")+1)."'>$qd</textarea>":"<input name='$u' value='$qd' size='$se[$z]'>");}else{$xe=strpos($X,"<i>‚Ä¶</i>");echo"<td id='$u' data-text='".($xe?2:($ai?1:0))."'".($lc?"":" data-warning='".h('Use edit link to modify this value.')."'").">$X</td>";}}}if($Pa)echo"<td>";$b->backwardKeysPrint($Pa,$K[$Ue]);echo"</tr>\n";}if(is_ajax())exit;echo"</table>\n","</div>\n";}if(!is_ajax()){if($K||$E){$_c=true;if($_GET["page"]!="last"){if($_==""||(count($K)<$_&&($K||!$E)))$fd=($E?$E*$_:0)+count($K);elseif($y!="sql"||!$Wd){$fd=($Wd?false:found_rows($R,$Z));if($fd<max(1e4,2*($E+1)*$_))$fd=reset(slow_query(count_rows($a,$Z,$Wd,$ld)));else$_c=false;}}$Jf=($_!=""&&($fd===false||$fd>$_||$E));if($Jf){echo(($fd===false?count($K)+1:$fd-$E*$_)>$_?'<p><a href="'.h(remove_from_uri("page")."&page=".($E+1)).'" class="loadmore">'.'Load more data'.'</a>'.script("qsl('a').onclick = partial(selectLoadMore, ".(+$_).", '".'Loading'."‚Ä¶');",""):''),"\n";}}echo"<div class='footer'><div>\n";if($K||$E){if($Jf){$Ee=($fd===false?$E+(count($K)>=$_?2:1):floor(($fd-1)/$_));echo"<fieldset>";if($y!="simpledb"){echo"<legend><a href='".h(remove_from_uri("page"))."'>".'Page'."</a></legend>",script("qsl('a').onclick = function () { pageClick(this.href, +prompt('".'Page'."', '".($E+1)."')); return false; };"),pagination(0,$E).($E>5?" ‚Ä¶":"");for($t=max(1,$E-4);$t<min($Ee,$E+5);$t++)echo
pagination($t,$E);if($Ee>0){echo($E+5<$Ee?" ‚Ä¶":""),($_c&&$fd!==false?pagination($Ee,$E):" <a href='".h(remove_from_uri("page")."&page=last")."' title='~$Ee'>".'last'."</a>");}}else{echo"<legend>".'Page'."</legend>",pagination(0,$E).($E>1?" ‚Ä¶":""),($E?pagination($E,$E):""),($Ee>$E?pagination($E+1,$E).($Ee>$E+1?" ‚Ä¶":""):"");}echo"</fieldset>\n";}echo"<fieldset>","<legend>".'Whole result'."</legend>";$Zb=($_c?"":"~ ").$fd;echo
checkbox("all",1,0,($fd!==false?($_c?"":"~ ").lang(array('%d row','%d rows'),$fd):""),"var checked = formChecked(this, /check/); selectCount('selected', this.checked ? '$Zb' : checked); selectCount('selected2', this.checked || !checked ? '$Zb' : checked);")."\n","</fieldset>\n";if($b->selectCommandPrint()){echo'<fieldset',($_GET["modify"]?'':' class="jsonly"'),'><legend>Modify</legend><div>
<input type="submit" value="Save"',($_GET["modify"]?'':' title="'.'Ctrl+click on a value to modify it.'.'"'),'>
</div></fieldset>
<fieldset><legend>Selected <span id="selected"></span></legend><div>
<input type="submit" name="edit" value="Edit">
<input type="submit" name="clone" value="Clone">
<input type="submit" name="delete" value="Delete">',confirm(),'</div></fieldset>
';}$dd=$b->dumpFormat();foreach((array)$_GET["columns"]as$e){if($e["fun"]){unset($dd['sql']);break;}}if($dd){print_fieldset("export",'Export'." <span id='selected2'></span>");$Hf=$b->dumpOutput();echo($Hf?adminer_html_select("output",$Hf,$ya["output"])." ":""),adminer_html_select("format",$dd,$ya["format"])," <input type='submit' name='export' value='".'Export'."'>\n","</div></fieldset>\n";}$b->selectEmailPrint(array_filter($qc,'strlen'),$f);}echo"</div></div>\n";if($b->selectImportPrint()){echo"<div>","<a href='#import'>".'Import'."</a>",script("qsl('a').onclick = partial(toggle, 'import');",""),"<span id='import' class='hidden'>: ","<input type='file' name='csv_file'> ",adminer_html_select("separator",array("csv"=>"CSV,","csv;"=>"CSV;","tsv"=>"TSV"),$ya["format"],1);echo" <input type='submit' name='import' value='".'Import'."'>","</span>","</div>";}echo"<input type='hidden' name='token' value='$mi'>\n","</form>\n",(!$ld&&$L?"":script("tableCheck();"));}}}if(is_ajax()){ob_end_clean();exit;}}elseif(isset($_GET["variables"])){$Ch=isset($_GET["status"]);page_header($Ch?'Status':'Variables');$Ti=($Ch?show_status():show_variables());if(!$Ti)echo"<p class='message'>".'No rows.'."\n";else{echo"<table cellspacing='0'>\n";foreach($Ti
as$z=>$X){echo"<tr>","<th><code class='jush-".$y.($Ch?"status":"set")."'>".h($z)."</code>","<td>".h($X);}echo"</table>\n";}}elseif(isset($_GET["script"])){header("Content-Type: text/javascript; charset=utf-8");if($_GET["script"]=="db"){$Kh=array("Data_length"=>0,"Index_length"=>0,"Data_free"=>0);foreach(table_status()as$C=>$R){json_row("Comment-$C",h($R["Comment"]));if(!is_view($R)){foreach(array("Engine","Collation")as$z)json_row("$z-$C",h($R[$z]));foreach($Kh+array("Auto_increment"=>0,"Rows"=>0)as$z=>$X){if($R[$z]!=""){$X=format_number($R[$z]);json_row("$z-$C",($z=="Rows"&&$X&&$R["Engine"]==($wh=="pgsql"?"table":"InnoDB")?"~ $X":$X));if(isset($Kh[$z]))$Kh[$z]+=($R["Engine"]!="InnoDB"||$z!="Data_free"?$R[$z]:0);}elseif(array_key_exists($z,$R))json_row("$z-$C");}}}foreach($Kh
as$z=>$X)json_row("sum-$z",format_number($X));json_row("");}elseif($_GET["script"]=="kill")$g->query("KILL ".number($_POST["kill"]));else{foreach(count_tables($b->databases())as$m=>$X){json_row("tables-$m",$X);json_row("size-$m",db_size($m));}json_row("");}exit;}else{$Th=array_merge((array)$_POST["tables"],(array)$_POST["views"]);if($Th&&!$o&&!$_POST["search"]){$H=true;$Je="";if($y=="sql"&&$_POST["tables"]&&count($_POST["tables"])>1&&($_POST["drop"]||$_POST["truncate"]||$_POST["copy"]))queries("SET foreign_key_checks = 0");if($_POST["truncate"]){if($_POST["tables"])$H=truncate_tables($_POST["tables"]);$Je='Tables have been truncated.';}elseif($_POST["move"]){$H=move_tables((array)$_POST["tables"],(array)$_POST["views"],$_POST["target"]);$Je='Tables have been moved.';}elseif($_POST["copy"]){$H=copy_tables((array)$_POST["tables"],(array)$_POST["views"],$_POST["target"]);$Je='Tables have been copied.';}elseif($_POST["drop"]){if($_POST["views"])$H=drop_views($_POST["views"]);if($H&&$_POST["tables"])$H=drop_tables($_POST["tables"]);$Je='Tables have been dropped.';}elseif($y!="sql"){$H=($y=="sqlite"?queries("VACUUM"):apply_queries("VACUUM".($_POST["optimize"]?"":" ANALYZE"),$_POST["tables"]));$Je='Tables have been optimized.';}elseif(!$_POST["tables"])$Je='No tables.';elseif($H=queries(($_POST["optimize"]?"OPTIMIZE":($_POST["check"]?"CHECK":($_POST["repair"]?"REPAIR":"ANALYZE")))." TABLE ".implode(", ",array_map('idf_escape',$_POST["tables"])))){while($J=$H->fetch_assoc())$Je.="<b>".h($J["Table"])."</b>: ".h($J["Msg_text"])."<br>";}queries_redirect(substr(ME,0,-1),$Je,$H);}page_header(($_GET["ns"]==""?'Database'.": ".h(DB):'Schema'.": ".h($_GET["ns"])),$o,true);if($b->homepage()){if($_GET["ns"]!==""){echo"<h3 id='tables-views'>".'Tables and views'."</h3>\n";$Sh=tables_list();if(!$Sh)echo"<p class='message'>".'No tables.'."\n";else{echo"<form action='' method='post'>\n";if(support("table")){echo"<fieldset><legend>".'Search data in tables'." <span id='selected2'></span></legend><div>","<input type='search' name='query' value='".h($_POST["query"])."'>",script("qsl('input').onkeydown = partialArg(bodyKeydown, 'search');","")," <input type='submit' name='search' value='".'Search'."'>\n","</div></fieldset>\n";if($_POST["search"]&&$_POST["query"]!=""){$_GET["where"][0]["op"]="LIKE %%";search_tables();}}$ac=doc_link(array('sql'=>'show-table-status.html'));echo"<div class='scrollable'>\n","<table cellspacing='0' class='nowrap checkable'>\n",script("mixin(qsl('table'), {onclick: tableClick, ondblclick: partialArg(tableClick, true)});"),'<thead><tr class="wrap">','<td><input id="check-all" type="checkbox" class="jsonly">'.script("qs('#check-all').onclick = partial(formCheck, /^(tables|views)\[/);",""),'<th>'.'Table','<td>'.'Engine'.doc_link(array('sql'=>'storage-engines.html')),'<td>'.'Collation'.doc_link(array('sql'=>'charset-charsets.html','mariadb'=>'supported-character-sets-and-collations/')),'<td>'.'Data Length'.$ac,'<td>'.'Index Length'.$ac,'<td>'.'Data Free'.$ac,'<td>'.'Auto Increment'.doc_link(array('sql'=>'example-auto-increment.html','mariadb'=>'auto_increment/')),'<td>'.'Rows'.$ac,(support("comment")?'<td>'.'Comment'.$ac:''),"</thead>\n";$S=0;foreach($Sh
as$C=>$T){$Wi=($T!==null&&!preg_match('~table~i',$T));$u=h("Table-".$C);echo'<tr'.odd().'><td>'.checkbox(($Wi?"views[]":"tables[]"),$C,in_array($C,$Th,true),"","","",$u),'<th>'.(support("table")||support("indexes")?"<a href='".h(ME)."table=".urlencode($C)."' title='".'Show structure'."' id='$u'>".h($C).'</a>':h($C));if($Wi){echo'<td colspan="6"><a href="'.h(ME)."view=".urlencode($C).'" title="'.'Alter view'.'">'.(preg_match('~materialized~i',$T)?'Materialized view':'View').'</a>','<td align="right"><a href="'.h(ME)."select=".urlencode($C).'" title="'.'Select data'.'">?</a>';}else{foreach(array("Engine"=>array(),"Collation"=>array(),"Data_length"=>array("create",'Alter table'),"Index_length"=>array("indexes",'Alter indexes'),"Data_free"=>array("edit",'New item'),"Auto_increment"=>array("auto_increment=1&create",'Alter table'),"Rows"=>array("select",'Select data'),)as$z=>$A){$u=" id='$z-".h($C)."'";echo($A?"<td align='right'>".(support("table")||$z=="Rows"||(support("indexes")&&$z!="Data_length")?"<a href='".h(ME."$A[0]=").urlencode($C)."'$u title='$A[1]'>?</a>":"<span$u>?</span>"):"<td id='$z-".h($C)."'>");}$S++;}echo(support("comment")?"<td id='Comment-".h($C)."'>":"");}echo"<tr><td><th>".sprintf('%d in total',count($Sh)),"<td>".h($y=="sql"?$g->result("SELECT @@storage_engine"):""),"<td>".h(db_collation(DB,collations()));foreach(array("Data_length","Index_length","Data_free")as$z)echo"<td align='right' id='sum-$z'>";echo"</table>\n","</div>\n";if(!information_schema(DB)){echo"<div class='footer'><div>\n";$Qi="<input type='submit' value='".'Vacuum'."'> ".on_help("'VACUUM'");$tf="<input type='submit' name='optimize' value='".'Optimize'."'> ".on_help($y=="sql"?"'OPTIMIZE TABLE'":"'VACUUM OPTIMIZE'");echo"<fieldset><legend>".'Selected'." <span id='selected'></span></legend><div>".($y=="sqlite"?$Qi:($y=="pgsql"?$Qi.$tf:($y=="sql"?"<input type='submit' value='".'Analyze'."'> ".on_help("'ANALYZE TABLE'").$tf."<input type='submit' name='check' value='".'Check'."'> ".on_help("'CHECK TABLE'")."<input type='submit' name='repair' value='".'Repair'."'> ".on_help("'REPAIR TABLE'"):"")))."<input type='submit' name='truncate' value='".'Truncate'."'> ".on_help($y=="sqlite"?"'DELETE'":"'TRUNCATE".($y=="pgsql"?"'":" TABLE'")).confirm()."<input type='submit' name='drop' value='".'Drop'."'>".on_help("'DROP TABLE'").confirm()."\n";$l=(support("scheme")?$b->schemas():$b->databases());if(count($l)!=1&&$y!="sqlite"){$m=(isset($_POST["target"])?$_POST["target"]:(support("scheme")?$_GET["ns"]:DB));echo"<p>".'Move to other database'.": ",($l?adminer_html_select("target",$l,$m):'<input name="target" value="'.h($m).'" autocapitalize="off">')," <input type='submit' name='move' value='".'Move'."'>",(support("copy")?" <input type='submit' name='copy' value='".'Copy'."'>":""),"\n";}echo"<input type='hidden' name='all' value=''>";echo
script("qsl('input').onclick = function () { selectCount('selected', formChecked(this, /^(tables|views)\[/));".(support("table")?" selectCount('selected2', formChecked(this, /^tables\[/) || $S);":"")." }"),"<input type='hidden' name='token' value='$mi'>\n","</div></fieldset>\n","</div></div>\n";}echo"</form>\n",script("tableCheck();");}echo'<p class="links"><a href="'.h(ME).'create=">'.'Create table'."</a>\n",(support("view")?'<a href="'.h(ME).'view=">'.'Create view'."</a>\n":"");if(support("routine")){echo"<h3 id='routines'>".'Routines'."</h3>\n";$Tg=routines();if($Tg){echo"<table cellspacing='0'>\n",'<thead><tr><th>'.'Name'.'<td>'.'Type'.'<td>'.'Return type'."<td></thead>\n";odd('');foreach($Tg
as$J){$C=($J["SPECIFIC_NAME"]==$J["ROUTINE_NAME"]?"":"&name=".urlencode($J["ROUTINE_NAME"]));echo'<tr'.odd().'>','<th><a href="'.h(ME.($J["ROUTINE_TYPE"]!="PROCEDURE"?'callf=':'call=').urlencode($J["SPECIFIC_NAME"]).$C).'">'.h($J["ROUTINE_NAME"]).'</a>','<td>'.h($J["ROUTINE_TYPE"]),'<td>'.h($J["DTD_IDENTIFIER"]),'<td><a href="'.h(ME.($J["ROUTINE_TYPE"]!="PROCEDURE"?'function=':'procedure=').urlencode($J["SPECIFIC_NAME"]).$C).'">'.'Alter'."</a>";}echo"</table>\n";}echo'<p class="links">'.(support("procedure")?'<a href="'.h(ME).'procedure=">'.'Create procedure'.'</a>':'').'<a href="'.h(ME).'function=">'.'Create function'."</a>\n";}if(support("sequence")){echo"<h3 id='sequences'>".'Sequences'."</h3>\n";$hh=get_vals("SELECT sequence_name FROM information_schema.sequences WHERE sequence_schema = current_schema() ORDER BY sequence_name");if($hh){echo"<table cellspacing='0'>\n","<thead><tr><th>".'Name'."</thead>\n";odd('');foreach($hh
as$X)echo"<tr".odd()."><th><a href='".h(ME)."sequence=".urlencode($X)."'>".h($X)."</a>\n";echo"</table>\n";}echo"<p class='links'><a href='".h(ME)."sequence='>".'Create sequence'."</a>\n";}if(support("type")){echo"<h3 id='user-types'>".'User types'."</h3>\n";$Oi=types();if($Oi){echo"<table cellspacing='0'>\n","<thead><tr><th>".'Name'."</thead>\n";odd('');foreach($Oi
as$X)echo"<tr".odd()."><th><a href='".h(ME)."type=".urlencode($X)."'>".h($X)."</a>\n";echo"</table>\n";}echo"<p class='links'><a href='".h(ME)."type='>".'Create type'."</a>\n";}if(support("event")){echo"<h3 id='events'>".'Events'."</h3>\n";$K=get_rows("SHOW EVENTS");if($K){echo"<table cellspacing='0'>\n","<thead><tr><th>".'Name'."<td>".'Schedule'."<td>".'Start'."<td>".'End'."<td></thead>\n";foreach($K
as$J){echo"<tr>","<th>".h($J["Name"]),"<td>".($J["Execute at"]?'At given time'."<td>".$J["Execute at"]:'Every'." ".$J["Interval value"]." ".$J["Interval field"]."<td>$J[Starts]"),"<td>$J[Ends]",'<td><a href="'.h(ME).'event='.urlencode($J["Name"]).'">'.'Alter'.'</a>';}echo"</table>\n";$yc=$g->result("SELECT @@event_scheduler");if($yc&&$yc!="ON")echo"<p class='error'><code class='jush-sqlset'>event_scheduler</code>: ".h($yc)."\n";}echo'<p class="links"><a href="'.h(ME).'event=">'.'Create event'."</a>\n";}if($Sh)echo
script("ajaxSetHtml('".js_escape(ME)."script=db');");}}}page_footer();
