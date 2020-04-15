<?php
/*
        Billing for FusionPBX

        Contributor(s):
        Luis Daniel Lucio Quiroz <dlucio@okay.com.mx>
*/
require_once "currency.php";
require_once "app/lcr/resources/functions/numbers.php";

function call_cost($rate, $i1, $i2, $t, $connect_rate = null){
	$c = (double)0.0;
	if (is_null($connect_rate)){
		$connect_rate = $rate;
	}
	if ($t > 0){
		$c = (double)$i1 * (double)$connect_rate / (double)60.0;
		if (($t > $i1) && ($i2)){
			$t -= $i1;
			$times = intval($t/$i2) + (($t % $i2)?1:0);
			$c += (double)$times * (double)$i2 * (double)$rate / (double)60.0;
		}
	}
	return $c;
}


function item_cost($item,$currency='USD'){
	$c = (double) 1.0;

	if (defined($_SESSION['billing'][$item.'.pricing']['numeric'])){
		$c = (double) $_SESSION['billing'][$item.'.pricing']['numeric'];
	}

	if (defined($_SESSION['billing']['currency']['text'])){
		$c = currency_convert($c, $currency, $_SESSION['billing']['currency']['text']);
	}
	else {
		$c = currency_convert($c, $currency);
	}

	return $c;
}

?>
