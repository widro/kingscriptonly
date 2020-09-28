<?php
//include('/var/www/html/conf/dbconnect.php');
$conn = mysqli_connect("internal-db.s222827.gridserver.com", "db222827", "llFt[8H,1d", "db222827_scraper");

//test report shown on top
$comparedate_before = "2018-01-01";
$comparedate_after = "2018-12-31";

$comparison_values['before'] = array('USD'=>'0', 'USD_Buy'=>'0', 'USD_Sell'=>'0', 'BTC'=>'0', 'ETH'=>'0', 'LTC'=>'0', 'NRG'=>'0', 'XLM'=>'0', 'XRP'=>'0', 'NEO'=>'0', 'subtotal1'=>'0', 'subtotal2'=>'0', 'grandtotal'=>'0');
$comparison_values['after'] = array('USD'=>'0', 'USD_Buy'=>'0', 'USD_Sell'=>'0', 'BTC'=>'0', 'ETH'=>'0', 'LTC'=>'0', 'NRG'=>'0', 'XLM'=>'0', 'XRP'=>'0', 'NEO'=>'0', 'subtotal1'=>'0', 'subtotal2'=>'0', 'grandtotal'=>'0');

//not really used bc there aren't entries for every 1st, tbd
$focus_dates = array(
	'2017-01-01'=>'0','2017-02-01'=>'0','2017-03-01'=>'0','2017-04-01'=>'0','2017-05-01'=>'0','2017-06-01'=>'0','2017-07-01'=>'0','2017-08-01'=>'0','2017-09-01'=>'0','2017-10-01'=>'0','2017-11-01'=>'0','2017-12-01'=>'0',
	'2018-01-01'=>'0','2018-02-01'=>'0','2018-03-01'=>'0','2018-04-01'=>'0','2018-05-01'=>'0','2018-06-01'=>'0','2018-07-01'=>'0','2018-08-01'=>'0','2018-09-01'=>'0','2018-10-01'=>'0','2018-11-01'=>'0','2018-12-01'=>'0',
	'2019-01-01'=>'0','2019-02-01'=>'0','2019-03-01'=>'0','2019-04-01'=>'0','2019-05-01'=>'0','2019-06-01'=>'0','2019-07-01'=>'0','2019-08-01'=>'0','2019-09-01'=>'0','2019-10-01'=>'0','2019-11-01'=>'0','2019-12-01'=>'0',
	'2020-01-01'=>'0','2020-02-01'=>'0','2020-03-01'=>'0','2020-04-01'=>'0','2020-05-01'=>'0','2020-06-01'=>'0','2020-07-01'=>'0','2020-08-01'=>'0','2020-09-01'=>'0','2020-10-01'=>'0','2020-11-01'=>'0','2020-12-01'=>'0',

);

//form utm vars
$filter_money_source = $_GET['filter_money_source'];
$filter_table_source = $_GET['filter_table_source'];
$filter_currency = $_GET['filter_currency'];
$filter_orderby = $_GET['filter_orderby'];
$filter_orderbydirection = $_GET['filter_orderbydirection'];

//from coins we do conversions on
$allcurrencyconversion = array();

$sql_conversion = "
	select *
	from currency_conversion
";

$result_conversion = mysqli_query($conn, $sql_conversion) or die($sql_conversion);

while($row_full_conversion = mysqli_fetch_array($result_conversion)) {
	$currency = $row_full_conversion['currency'];
	$date  = $row_full_conversion['date'];
	$closing_price_usd  = $row_full_conversion['closing_price_usd'];

	$allcurrencyconversion[$currency][$date] = $closing_price_usd;

}

if($filter_money_source!=""){
	$sqlwhereadd = " and money_source = '$filter_money_source'";
}

if($filter_table_source!=""){
	$sqlwhereadd = " and table_source = '$filter_table_source'";
}

if($filter_currency!=""){
	$sqlwhereadd .= " and transaction_currency LIKE '%$filter_currency%'";
}

if($filter_orderby!=""){
	if($filter_orderbydirection!=""){
		$sqlorderby = "order by $filter_orderby $filter_orderbydirection";
	}
	else{
		$sqlorderby = "order by $filter_orderby ASC";
	}
}
else{
	$sqlorderby = "order by transaction_timestamp ASC";
}

//these are running totals
$totals = array('personal','investor');
$totals['personal'] = array('USD'=>'0', 'USD_Buy'=>'0', 'USD_Sell'=>'0', 'BTC'=>'0', 'ETH'=>'0', 'LTC'=>'0', 'NRG'=>'0', 'XLM'=>'0', 'XRP'=>'0', 'NEO'=>'0');
$totals['investor'] = array('USD'=>'0', 'USD_Buy'=>'0', 'USD_Sell'=>'0', 'BTC'=>'0', 'ETH'=>'0', 'LTC'=>'0', 'NRG'=>'0', 'XLM'=>'0', 'XRP'=>'0', 'NEO'=>'0');

//gets every transaction
$sql_full_summary = "
	select *, date(transaction_timestamp) as dateonly
	from king_combined kc
	where 1 = 1
	$sqlwhereadd
	$sqlorderby
";

$result_full_summary = mysqli_query($conn, $sql_full_summary) or die($sql_full_summary);


$output = "<table class='table'><tr>";
$output .= "<th colspan=6 class=color0>transaction</th>";
if($filter_money_source!="investor"){	
	$output .= "<th colspan=7 class=color1>personal</th>";
}
if($filter_money_source!="personal"){	
	$output .= "<th colspan=7 class=color2>investor</th>";
}
$output .= "<th colspan=5 class=color3>currency price</th>";
if($filter_money_source!="investor"){	
	$output .= "<th colspan=8 class=color4>personal in USD</th>";
}
if($filter_money_source!="personal"){	
	$output .= "<th colspan=8 class=color5>investor in USD</th>";
}

$output .= "</tr>";

$output .= "<tr>";
$output .= "<th class=color0>timestamp</th>";
$output .= "<th class=color0>currency entry</th>";
$output .= "<th class=color0>add</th>";
$output .= "<th class=color0>subtract</th>";
$output .= "<th class=color0>type</th>";
$output .= "<th class=color0>table_source</th>";

if($filter_money_source!="investor"){	
	//$output .= "<th class=color1>USD</th>";
	$output .= "<th class=color1>USD_Buy</th>";
	$output .= "<th class=color1>USD_Sell</th>";
	$output .= "<th class=color1>BTC</th>";
	$output .= "<th class=color1>ETH</th>";
	$output .= "<th class=color1>LTC</th>";
	$output .= "<th class=color1>NRG</th>";
	$output .= "<th class=color1>NEO</th>";
}
if($filter_money_source!="personal"){	
	//$output .= "<th class=color2>USD</th>";
	$output .= "<th class=color2>USD_Buy</th>";
	$output .= "<th class=color2>USD_Sell</th>";
	$output .= "<th class=color2>BTC</th>";
	$output .= "<th class=color2>ETH</th>";
	$output .= "<th class=color2>LTC</th>";
	$output .= "<th class=color2>NRG</th>";
	$output .= "<th class=color2>NEO</th>";
}
$output .= "<th class=color3>BTC</th>";
$output .= "<th class=color3>ETH</th>";
$output .= "<th class=color3>LTC</th>";
$output .= "<th class=color3>NRG</th>";
$output .= "<th class=color3>NEO</th>";

if($filter_money_source!="investor"){	
	$output .= "<th class=color4>USD Buy</th>";
	$output .= "<th class=color4>USD Sell</th>";
	$output .= "<th class=color4>BTC</th>";
	$output .= "<th class=color4>ETH</th>";
	$output .= "<th class=color4>LTC</th>";
	$output .= "<th class=color4>NRG</th>";
	$output .= "<th class=color4>NEO</th>";
	$output .= "<th class=color4 style='font-weight:bolder;'>SUBTOTAL1</th>";
}
if($filter_money_source!="personal"){	
	$output .= "<th class=color5>USD Buy</th>";
	$output .= "<th class=color5>USD Sell</th>";
	$output .= "<th class=color5>BTC</th>";
	$output .= "<th class=color5>ETH</th>";
	$output .= "<th class=color5>LTC</th>";
	$output .= "<th class=color5>NRG</th>";
	$output .= "<th class=color5>NEO</th>";
	$output .= "<th class=color5 style='font-weight:bolder;'>SUBTOTAL2</th>";
}
if($filter_money_source==""){	
	$output .= "<th style='font-weight:bolder;'>GRAND TOTAL</th>";
}


$output .= "</tr>";

while($row_full_summary = mysqli_fetch_array($result_full_summary)) {
	$king_combined_id = $row_full_summary['king_combined_id'];
	$transaction_timestamp = $row_full_summary['transaction_timestamp'];
	$dateonly = $row_full_summary['dateonly'];
	$money_source = $row_full_summary['money_source'];
	$transaction_currency = strtoupper($row_full_summary['transaction_currency']);
	$transaction_currency_original = strtoupper($row_full_summary['transaction_currency']);

	$transaction_amount = $row_full_summary['transaction_amount'];
	$transaction_amount_usd = $row_full_summary['transaction_amount_usd'];
	$transaction_amount_ofrecord = $row_full_summary['transaction_amount_ofrecord'];
	$transaction_amount_crypto = $row_full_summary['transaction_amount_crypto'];
	$transaction_quantity = $row_full_summary['transaction_quantity'];
	$transaction_quantity_crypto = $row_full_summary['transaction_quantity_crypto'];
	$dealfunds_btc = $row_full_summary['dealfunds_btc'];
	$dealsize_nrg = $row_full_summary['dealsize_nrg'];
	$dealSize = $row_full_summary['dealSize'];

	$transaction_type = $row_full_summary['transaction_type'];
	$side = $row_full_summary['side'];
	$transaction_notes = $row_full_summary['transaction_notes'];
	$table_source = $row_full_summary['table_source'];
	$xls_source = $row_full_summary['xls_source'];

	if($table_source=="king_nrg_v2_port_A"){
		$transaction_type = "MINED";
	}

	if($table_source=="king_nrg_miner"){
		$transaction_type = "MINED";
	}




	//different data had different fields, consolodated some, others specified for each table_source

	$amount_to_use1 = "transaction_amount";
	$amount_to_use2 = "transaction_amount_crypto";


	if($table_source=="king_2ca15371"){
		$amount_to_use1 = "transaction_amount_crypto";
	}
	elseif($table_source=="king_386DBKF_Transaction_History"){
		$amount_to_use1 = "transaction_quantity_crypto";
		$amount_to_use2 = "transaction_amount_ofrecord";
	}
	elseif($table_source=="king_coinbase"){
		$amount_to_use1 = "transaction_quantity_crypto";
		$amount_to_use2 = "transaction_amount_usd";
	}
	elseif($table_source=="king_coin_ira"){
		$amount_to_use1 = "transaction_quantity";
		$amount_to_use2 = "transaction_amount_ofrecord";
	}

	elseif($table_source=="king_investor_nrg_sales_2019"){
		$amount_to_use2 = "transaction_amount_ofrecord";
	}

	elseif($table_source=="king_kucoin3"){
		$amount_to_use1 = "dealSize";
		$amount_to_use2 = "dealfunds_btc";
	}
	elseif($table_source=="king_main_portfolio"){
		$amount_to_use1 = "transaction_quantity";
		$amount_to_use2 = "transaction_amount_ofrecord";
	}

	elseif($table_source=="king_UNKP9G6_Transaction_History"){
		$amount_to_use1 = "transaction_quantity_crypto";
		$amount_to_use2 = "transaction_amount_ofrecord";
	}

	//normalizing which currency to use
	if (strpos($transaction_currency, '/BTC') !== false) {
		if(strlen($transaction_currency)==8){
			$transaction_currency_first = substr($transaction_currency, 0, 4);
		}
		else{
			$transaction_currency_first = substr($transaction_currency, 0, 3);
		}
		$transaction_currency = "BTC";
	}
	elseif (strpos($transaction_currency, '-BTC') !== false) {
		if(strlen($transaction_currency)==8){
			$transaction_currency_first = substr($transaction_currency, 0, 4);
		}
		else{
			$transaction_currency_first = substr($transaction_currency, 0, 3);
		}
		$transaction_currency = "BTC";
	}
	elseif (strpos($transaction_currency, '/USD') !== false) {
		if(strlen($transaction_currency)==8){
			$transaction_currency_first = substr($transaction_currency, 0, 4);
		}
		else{
			$transaction_currency_first = substr($transaction_currency, 0, 3);
		}
		$transaction_currency = "USD";
	}
	elseif (strpos($transaction_currency, '/ETH') !== false) {
		if(strlen($transaction_currency)==8){
			$transaction_currency_first = substr($transaction_currency, 0, 4);
		}
		else{
			$transaction_currency_first = substr($transaction_currency, 0, 3);
		}
		$transaction_currency = "ETH";
	}

	$amount_to_display1 = "0";
	$amount_to_display2 = "0";

	//manual overrides to top of switches before table_source
	$transaction_type_array1 = array('send','receive','deposit','withdrawal');
	$transaction_type_array2 = array('send','receive');
	$transaction_currency_array1 = array('eth','ltc','btc');
	$transaction_currency_array2 = array('eth');
	$transaction_type_lower = strtolower($transaction_type);
	$transaction_currency_lower = strtolower($transaction_currency);




	$king_combined_id_whitelist = array('10672','10176','10093','10080',
		'9933','20488','20487','20489','20490','20491','20493','20494','20495','20497','20499','20500','20501','20504','','','','','','','','','','','','','','','','','','','','','','','','','','');

	$king_combined_id_omit = array('472','43','19784','19772','19783','603',
		'10154','10047','10043','10018','10161','10120','10099','10052','10040','9981','9978','','','','','','','','','');





	//if((in_array(strtolower($transaction_type), $transaction_type_array1))&&(in_array(strtolower($transaction_currency_array1), $transaction_currency_array1))){
	if(in_array($king_combined_id, $king_combined_id_omit)){
		$amount_to_display1 = $$amount_to_use1 . " " . $transaction_currency . " (omittedbyID)";
	}

	elseif(in_array($king_combined_id, $king_combined_id_whitelist)){
		$totals[$money_source][$transaction_currency] = $totals[$money_source][$transaction_currency] + $$amount_to_use1;
		$amount_to_display1 = $$amount_to_use1 . " " . $transaction_currency . " (whitelistedbyID)";
	}


	elseif(($table_source=="king_coinbase")&&($money_source=="personal")&&($transaction_currency=="BTC")){
		$totals[$money_source][$transaction_currency] = $totals[$money_source][$transaction_currency] + $$amount_to_use1;
		$amount_to_display1 = $$amount_to_use1 . " " . $transaction_currency . " (whitelistedbyBTCcoinbasePersonal)";
	}


	elseif(
		(in_array($transaction_type_lower, $transaction_type_array1))&&
		(in_array($transaction_currency_lower, $transaction_currency_array1))&&
		($transaction_currency_lower=="btc")&&
		($$amount_to_use1*10>"1")
	){
		$amount_to_display1 = $$amount_to_use1 . " " . $transaction_currency . " (omittedBTC)";
	}
	elseif((in_array(strtolower($transaction_type), $transaction_type_array1))&&(in_array(strtolower($transaction_currency), $transaction_currency_array1))&&($transaction_currency=="ETH")&&($$amount_to_use1>"5")){
		$amount_to_display1 = $$amount_to_use1 . " " . $transaction_currency . " (omittedETH)";
	}
	elseif((in_array(strtolower($transaction_type), $transaction_type_array1))&&(in_array(strtolower($transaction_currency), $transaction_currency_array1))&&($transaction_currency=="LTC")&&($$amount_to_use1>"1")){
		$amount_to_display1 = $$amount_to_use1 . " " . $transaction_currency . " (omittedLTC)";
	}
	elseif(strtolower($transaction_type)=="collateral"){
		$amount_to_display1 = $$amount_to_use1 . " " . $transaction_currency . " (omitted_collateral)";
	}










	//run thru each table, then by type
	elseif($table_source=="king_2ca15371"){
		if($xls_source=="2ca15371-c011-4a1c-b083-26470dd"){
			//$totals[$money_source][$transaction_currency] = $totals[$money_source][$transaction_currency] + $$amount_to_use1;
		}
		elseif($transaction_type=="deposit"){
			$totals[$money_source][$transaction_currency] = $totals[$money_source][$transaction_currency] + $$amount_to_use1;
		}
		elseif($transaction_type=="loan_interest_payment"){
			$totals[$money_source][$transaction_currency] = $totals[$money_source][$transaction_currency] + $$amount_to_use1;
		}
		elseif($transaction_type=="interest"){
			$totals[$money_source][$transaction_currency] = $totals[$money_source][$transaction_currency] + $$amount_to_use1;
		}
		elseif($transaction_type=="withdrawal"){
			$totals[$money_source][$transaction_currency] = $totals[$money_source][$transaction_currency] + $$amount_to_use1;
		}
		elseif($transaction_type=="loan_principal_payment"){
			$totals[$money_source][$transaction_currency] = $totals[$money_source][$transaction_currency] + $$amount_to_use1;
		}
		elseif($transaction_type=="collateral"){
			$totals[$money_source][$transaction_currency] = $totals[$money_source][$transaction_currency] - $$amount_to_use1;
		}
		elseif($transaction_type=="bonus_token"){
			$totals[$money_source][$transaction_currency] = $totals[$money_source][$transaction_currency] + $$amount_to_use1;
		}
		$amount_to_display1 = $$amount_to_use1 . " " . $transaction_currency;
		if($king_combined_id=="472"){
			$amount_to_display1 .= " (omitted)";
		}
		elseif($xls_source=="2ca15371-c011-4a1c-b083-26470dd"){
			$amount_to_display1 .= " (omitted)";
		}

	}
	elseif($table_source=="king_386DBKF_Transaction_History"){
		if($transaction_type=="Inbound Transfer"){
			$totals[$money_source][$transaction_currency] = $totals[$money_source][$transaction_currency] + $$amount_to_use1;
			$amount_to_display1 = $$amount_to_use1 . " " . $transaction_currency;
		}
		elseif($transaction_type=="Sell"){
			$totals[$money_source][$transaction_currency] = $totals[$money_source][$transaction_currency] - $$amount_to_use1;
			$totals[$money_source]['USD'] = $totals[$money_source]['USD'] + $$amount_to_use2;
			$totals[$money_source]['USD_Sell'] = $totals[$money_source]['USD_Sell'] + $$amount_to_use2;
			$amount_to_display1 = "-" . $$amount_to_use1 . " " . $transaction_currency;
			$amount_to_display2 = $$amount_to_use2 . " USD";
		}
		elseif($transaction_type=="Buy"){
			$totals[$money_source][$transaction_currency] = $totals[$money_source][$transaction_currency] + $$amount_to_use1;
			$totals[$money_source]['USD'] = $totals[$money_source]['USD'] - $$amount_to_use2;
			$totals[$money_source]['USD_Buy'] = $totals[$money_source]['USD_Buy'] - $$amount_to_use2;
			$amount_to_display1 = $$amount_to_use1 . " " . $transaction_currency;
			$amount_to_display2 = $$amount_to_use2 . " USD";
		}
		elseif($transaction_type=="Outbound Transfer"){
			$totals[$money_source][$transaction_currency] = $totals[$money_source][$transaction_currency] - $$amount_to_use1;
			$amount_to_display1 = "-" . $$amount_to_use1 . " " . $transaction_currency;
		}
	}
	elseif($table_source=="king_account"){
		//amount is positive or negative
		$totals[$money_source][$transaction_currency] = $totals[$money_source][$transaction_currency] + $$amount_to_use1;
		$amount_to_display1 = $$amount_to_use1 . " " . $transaction_currency;
	}
	elseif($table_source=="king_blockfi"){
		//all entries are deposits in the currency
		if($money_source=="investor"){
			$amount_to_display1 = $$amount_to_use1 . " " . $transaction_currency . " (omitted)";
		}
		else{
			$totals[$money_source][$transaction_currency] = $totals[$money_source][$transaction_currency] + $$amount_to_use1;
			$amount_to_display1 = $$amount_to_use1 . " " . $transaction_currency;

		}
	}
	elseif($table_source=="king_celsius"){
		//all entries are interest in the currency
		$totals[$money_source][$transaction_currency] = $totals[$money_source][$transaction_currency] + $$amount_to_use1;
		$amount_to_display1 = $$amount_to_use1 . " " . $transaction_currency;
	}
	elseif($table_source=="king_coinbase"){
		if($xls_source=="Port A Logs 20200721.xlsx Coinbase BTC to USD Tab"){
			$amount_to_display1 = "-" . $$amount_to_use1 . " " . $transaction_currency . " (omitted111)";
		}
		elseif((in_array(strtolower($transaction_currency), $transaction_currency_array2))&&(in_array(strtolower($transaction_type), $transaction_type_array2))){
			$amount_to_display1 = "-" . $$amount_to_use1 . " " . $transaction_currency . " (omitted222)";
		}
		else{

			if($transaction_type=="Sell"){
				$totals[$money_source][$transaction_currency] = $totals[$money_source][$transaction_currency] - $$amount_to_use1;
				$totals[$money_source]['USD'] = $totals[$money_source]['USD'] + $$amount_to_use2;
				$totals[$money_source]['USD_Sell'] = $totals[$money_source]['USD_Sell'] + $$amount_to_use2;
				$amount_to_display1 = "-" . $$amount_to_use1 . " " . $transaction_currency;
				$amount_to_display2 = $$amount_to_use2 . " USD";
			}
			elseif($transaction_type=="Buy"){
				$totals[$money_source][$transaction_currency] = $totals[$money_source][$transaction_currency] + $$amount_to_use1;
				$totals[$money_source]['USD'] = $totals[$money_source]['USD'] - $$amount_to_use2;
				$totals[$money_source]['USD_Buy'] = $totals[$money_source]['USD_Buy'] - $$amount_to_use2;
				$amount_to_display1 = $$amount_to_use1 . " " . $transaction_currency;
				$amount_to_display2 = $$amount_to_use2 . " USD";
			}
			elseif($transaction_type=="Receive"){
				$totals[$money_source][$transaction_currency] = $totals[$money_source][$transaction_currency] + $$amount_to_use1;
				$amount_to_display1 = $$amount_to_use1 . " " . $transaction_currency;
			}
			elseif($transaction_type=="Send"){
				$totals[$money_source][$transaction_currency] = $totals[$money_source][$transaction_currency] - $$amount_to_use1;
				$amount_to_display1 = "n/a";
				$amount_to_display2 = $$amount_to_use1 . " " . $transaction_currency;
			}
			elseif($transaction_type=="Convert"){
				//lose this amount of eth
				$totals[$money_source][$transaction_currency] = $totals[$money_source][$transaction_currency] - $$amount_to_use1;
				//converted to btc
				$totals[$money_source]['BTC'] = $totals[$money_source]['BTC'] - $dealfunds_btc;
				$amount_to_display1 = $$amount_to_use1 . " " . $transaction_currency;
				$amount_to_display2 = $dealfunds_btc . " BTC";
			}
			elseif($transaction_type=="Coinbase Earn"){
				$totals[$money_source][$transaction_currency] = $totals[$money_source][$transaction_currency] + $$amount_to_use1;
				$amount_to_display1 = $$amount_to_use1 . " " . $transaction_currency;
			}
			elseif($transaction_type=="Rewards Income"){
				$totals[$money_source][$transaction_currency] = $totals[$money_source][$transaction_currency] + $$amount_to_use1;
				$amount_to_display1 = $$amount_to_use1 . " " . $transaction_currency;
			}
		}
	}


	elseif($table_source=="king_coin_ira"){
		//all are crypto to usd
		if($transaction_type=="BUY"){
			$totals[$money_source][$transaction_currency_first] = $totals[$money_source][$transaction_currency_first] + $$amount_to_use1;
			$totals[$money_source][$transaction_currency] = $totals[$money_source][$transaction_currency] - $$amount_to_use2;
			$amount_to_display1 = $$amount_to_use1 . " " . $transaction_currency_first;
			$amount_to_display2 = "-" . $$amount_to_use2 . " " . $transaction_currency;
			if($transaction_currency=="USD"){
				$totals[$money_source]['USD_Buy'] = $totals[$money_source]['USD_Buy'] - $$amount_to_use2;
			}
		}
		elseif($transaction_type=="SELL"){
			//quantity for SELL are negative, so use +
			$totals[$money_source][$transaction_currency_first] = $totals[$money_source][$transaction_currency_first] + $$amount_to_use1;
			$totals[$money_source][$transaction_currency] = $totals[$money_source][$transaction_currency] + $$amount_to_use2;
			$amount_to_display1 = $$amount_to_use1 . " " . $transaction_currency_first;
			$amount_to_display2 = $$amount_to_use2 . " " . $transaction_currency;
			if($transaction_currency=="USD"){
				$totals[$money_source]['USD_Sell'] = $totals[$money_source]['USD_Sell'] - $$amount_to_use2;
			}
		}
	}
	elseif($table_source=="2018_Energi_Masternode_formatted"){
		$totals[$money_source][$transaction_currency] = $totals[$money_source][$transaction_currency] + $$amount_to_use1;
		$amount_to_display1 = $$amount_to_use1 . " " . $transaction_currency;
	}
	elseif($table_source=="king_Energi_Masternode"){
		if($transaction_type=="Send"){
			$amount_to_display1 = $$amount_to_use1 . " " . $transaction_currency . " (omitted)";
		}
		else{
			$totals[$money_source][$transaction_currency] = $totals[$money_source][$transaction_currency] + $$amount_to_use1;
			$amount_to_display1 = $$amount_to_use1 . " " . $transaction_currency;
		}
	}
	elseif($table_source=="king_investor_nrg_sales_2019"){
			$totals[$money_source][$transaction_currency_first] = $totals[$money_source][$transaction_currency_first] - $$amount_to_use1;
			$totals[$money_source][$transaction_currency] = $totals[$money_source][$transaction_currency] + $$amount_to_use2;
			$amount_to_display1 = $$amount_to_use1 . " " . $transaction_currency_first;
			$amount_to_display2 = $$amount_to_use2 . " " . $transaction_currency;
	}





	elseif($table_source=="king_kucoin"){
		if(($money_source=="investor")&&($transaction_type=="TRADE_EXCHANGE")){
			$amount_to_display1 = $$amount_to_use1 . " " . $transaction_currency . " (omitted)";
		}
		elseif(($transaction_currency=="NRG")){
			$amount_to_display1 = $$amount_to_use1 . " " . $transaction_currency . " (omitted)";
		}
		else{
			if($side=="Withdrawal"){
				$totals[$money_source][$transaction_currency] = $totals[$money_source][$transaction_currency] + $$amount_to_use1;
				$amount_to_display1 = $$amount_to_use1 . " " . $transaction_currency;
			}
			if($side=="Deposit"){
				$totals[$money_source][$transaction_currency] = $totals[$money_source][$transaction_currency] + $$amount_to_use1;
				$amount_to_display1 = $$amount_to_use1 . " " . $transaction_currency;
			}
		}
	}

	elseif($table_source=="king_kucoin2"){
		//$totals[$money_source][$transaction_currency] = $totals[$money_source][$transaction_currency] + $$amount_to_use1;
		$amount_to_display1 = $$amount_to_use1 . " " . $transaction_currency . " (omitted)";
	}
	elseif($table_source=="king_kucoin3"){
		//always NRG
		$totals[$money_source][$transaction_currency_first] = $totals[$money_source][$transaction_currency_first] - $dealSize;
		//always BTC
		$totals[$money_source][$transaction_currency] = $totals[$money_source][$transaction_currency] + $dealfunds_btc - $fee_btc;
		$amount_to_display1 = $dealSize . " " . $transaction_currency_first;
		$amount_to_display2 = $dealfunds_btc - $fee_btc . "BTC";
	}

	elseif($table_source=="king_main_portfolio"){
		if($king_combined_id=="10023"){
			$amount_to_display1 = $$amount_to_use1 . " " . $transaction_currency_first . " (omitted)";
			$amount_to_display2 = $$amount_to_use2 . " " . $transaction_currency . " (omitted)";
		}
		elseif($money_source=="investor"){
			if(($transaction_type=="BUY")&&($transaction_currency_first=="NRG")&&($transaction_currency=="BTC")){
				$totals[$money_source][$transaction_currency_first] = $totals[$money_source][$transaction_currency_first] + $$amount_to_use1;
				$totals[$money_source][$transaction_currency] = $totals[$money_source][$transaction_currency] - $$amount_to_use2;
				$amount_to_display1 = $$amount_to_use1 . " " . $transaction_currency_first;
				$amount_to_display2 = $$amount_to_use2 . " " . $transaction_currency;
			}
			elseif(($transaction_currency=="BTC")||($transaction_currency=="LTC")||($transaction_currency=="ETH")){
				$amount_to_display1 = $$amount_to_use1 . " " . $transaction_currency_first . " (omitted)";
				$amount_to_display2 = $$amount_to_use2 . " " . $transaction_currency . " (omitted)";
			}
			else{
				if($transaction_type=="BUY"){
					$totals[$money_source][$transaction_currency_first] = $totals[$money_source][$transaction_currency_first] + $$amount_to_use1;
					$totals[$money_source][$transaction_currency] = $totals[$money_source][$transaction_currency] - $$amount_to_use2;
					$amount_to_display1 = $$amount_to_use1 . " " . $transaction_currency_first;
					$amount_to_display2 = $$amount_to_use2 . " " . $transaction_currency;
					if($transaction_currency=="USD"){
						$totals[$money_source]['USD_Buy'] = $totals[$money_source]['USD_Buy'] - $$amount_to_use2;
					}
				}
				elseif($transaction_type=="SELL"){
					//quantity for SELL are negative, so use +
					$totals[$money_source][$transaction_currency_first] = $totals[$money_source][$transaction_currency_first] + $$amount_to_use1;
					$totals[$money_source][$transaction_currency] = $totals[$money_source][$transaction_currency] + $$amount_to_use2;
					$amount_to_display1 = $$amount_to_use1 . " " . $transaction_currency_first;
					$amount_to_display2 = $$amount_to_use2 . " " . $transaction_currency;
					if($transaction_currency=="USD"){
						$totals[$money_source]['USD_Sell'] = $totals[$money_source]['USD_Sell'] - $$amount_to_use2;
					}
				}
			}
		}
		elseif($money_source=="personal"){
			if(($transaction_currency=="BTC")||($transaction_currency=="LTC")||($transaction_currency=="ETH")||($transaction_currency=="NRG")){
				$amount_to_display1 = $$amount_to_use1 . " " . $transaction_currency_first . " (omitted)";
				$amount_to_display2 = $$amount_to_use2 . " " . $transaction_currency . " (omitted)";
			}
			else{
				if($transaction_type=="BUY"){
					$totals[$money_source][$transaction_currency_first] = $totals[$money_source][$transaction_currency_first] + $$amount_to_use1;
					$totals[$money_source][$transaction_currency] = $totals[$money_source][$transaction_currency] - $$amount_to_use2;
					$amount_to_display1 = $$amount_to_use1 . " " . $transaction_currency_first;
					$amount_to_display2 = $$amount_to_use2 . " " . $transaction_currency;
					if($transaction_currency=="USD"){
						$totals[$money_source]['USD_Buy'] = $totals[$money_source]['USD_Buy'] - $$amount_to_use2;
					}
				}
				elseif($transaction_type=="SELL"){
					//quantity for SELL are negative, so use +
					$totals[$money_source][$transaction_currency_first] = $totals[$money_source][$transaction_currency_first] + $$amount_to_use1;
					$totals[$money_source][$transaction_currency] = $totals[$money_source][$transaction_currency] + $$amount_to_use2;
					$amount_to_display1 = $$amount_to_use1 . " " . $transaction_currency_first;
					$amount_to_display2 = $$amount_to_use2 . " " . $transaction_currency;
					if($transaction_currency=="USD"){
						$totals[$money_source]['USD_Sell'] = $totals[$money_source]['USD_Sell'] - $$amount_to_use2;
					}
				}
			}

		}





		elseif(
			(($transaction_currency=="BTC")&&($transaction_currency_original=="NRG"))||
			($transaction_currency=="USD")||
			(strpos($transaction_currency_original, '/ETH') !== false)||
			(strpos($transaction_currency_original, '/BTC') !== false)||
			(strpos($transaction_currency_original, '/LTC') !== false)
		){
			$amount_to_display1 = $transaction_quantity . " " . $transaction_currency_first . " (omitted)";
			$amount_to_display2 = $transaction_amount_ofrecord . " " . $transaction_currency . " (omitted)";
		}
		else{
		}
	}

	
	elseif($table_source=="king_nrgjson2020"){
		if($transaction_amount>="10"){
			$amount_to_display1 = $$amount_to_use1 . " " . $transaction_currency . " (omitted)";

		}
		else{
			$totals[$money_source][$transaction_currency] = $totals[$money_source][$transaction_currency] + $$amount_to_use1;
			$amount_to_display1 = $$amount_to_use1 . " " . $transaction_currency;
		}
	}


	elseif($table_source=="king_neon_wallet_activity"){
		if($transaction_type=="RECEIVE"){
			if($transaction_currency=="NEO"){
				$amount_to_display1 = $$amount_to_use1 . " " . $transaction_currency . " (omitted_NEO_RECEIVE)";
			}
			else{
				$totals[$money_source][$transaction_currency] = $totals[$money_source][$transaction_currency] + $$amount_to_use1;
				$amount_to_display1 = $$amount_to_use1 . " " . $transaction_currency;
			}
		}
		elseif($transaction_type=="SEND"){
			$totals[$money_source][$transaction_currency] = $totals[$money_source][$transaction_currency] - $$amount_to_use1;
			$amount_to_display1 = "n/a";
			$amount_to_display2 = $$amount_to_use1 . " " . $transaction_currency;
		}
		elseif($transaction_type=="CLAIM"){
			$totals[$money_source][$transaction_currency] = $totals[$money_source][$transaction_currency] + $$amount_to_use1;
			$amount_to_display1 = $$amount_to_use1 . " " . $transaction_currency;
		}
	}
	elseif($table_source=="king_nrg_miner"){
		//$totals[$money_source][$transaction_currency] = $totals[$money_source][$transaction_currency] + $$amount_to_use1;
		//$amount_to_display1 = $$amount_to_use1 . " " . $transaction_currency;
		$amount_to_display1 = $$amount_to_use1 . " " . $transaction_currency . " (omitted)";
	}
	elseif($table_source=="king_nrg_v2_port_A"){
		if($transaction_type=="Mined"){
			$totals[$money_source][$transaction_currency] = $totals[$money_source][$transaction_currency] + $$amount_to_use1;
		}
		elseif($transaction_type=="Sent to"){
			$totals[$money_source][$transaction_currency] = $totals[$money_source][$transaction_currency] + $$amount_to_use1;
		}
		elseif($transaction_type=="Payment to yourself"){
			$totals[$money_source][$transaction_currency] = $totals[$money_source][$transaction_currency] + $$amount_to_use1;
		}
		elseif($transaction_type=="Received with"){
			$totals[$money_source][$transaction_currency] = $totals[$money_source][$transaction_currency] + $$amount_to_use1;
		}
		elseif($transaction_type=="Staked"){
			$totals[$money_source][$transaction_currency] = $totals[$money_source][$transaction_currency] + $$amount_to_use1;
		}
		elseif($transaction_type=="PrivateSend Create Denominations"){
			$totals[$money_source][$transaction_currency] = $totals[$money_source][$transaction_currency] + $$amount_to_use1;
		}
		elseif($transaction_type=="PrivateSend Make Collateral Inputs"){
			$totals[$money_source][$transaction_currency] = $totals[$money_source][$transaction_currency] + $$amount_to_use1;
		}
		elseif($transaction_type=="PrivateSend Denominate"){
			$totals[$money_source][$transaction_currency] = $totals[$money_source][$transaction_currency] + $$amount_to_use1;
		}
		elseif($transaction_type=="PrivateSend Collateral Payment"){
			$totals[$money_source][$transaction_currency] = $totals[$money_source][$transaction_currency] + $$amount_to_use1;
		}
		else{
			$totals[$money_source][$transaction_currency] = $totals[$money_source][$transaction_currency] + $$amount_to_use1;
		}
		$amount_to_display1 = $$amount_to_use1 . " " . $transaction_currency;
	}
	elseif($table_source=="king_TomoChainWallet"){
		$totals[$money_source][$transaction_currency] = $totals[$money_source][$transaction_currency] + $$amount_to_use1;
		$amount_to_display1 = $$amount_to_use1 . " " . $transaction_currency;
	}
	elseif($table_source=="king_UNKP9G6_Transaction_History"){
		if($transaction_type=="Inbound Transfer"){
			$totals[$money_source][$transaction_currency] = $totals[$money_source][$transaction_currency] + $$amount_to_use1;
			$amount_to_display1 = $$amount_to_use1 . " " . $transaction_currency;
		}
		elseif($transaction_type=="Sell"){
			$totals[$money_source][$transaction_currency] = $totals[$money_source][$transaction_currency] - $$amount_to_use1;
			$totals[$money_source]['USD'] = $totals[$money_source]['USD'] + $$amount_to_use2;
			$totals[$money_source]['USD_Sell'] = $totals[$money_source]['USD_Sell'] + $$amount_to_use2;
			$amount_to_display1 = "-" . $$amount_to_use1 . " " . $transaction_currency;
			$amount_to_display2 = $$amount_to_use2 . " USD";
		}
		elseif($transaction_type=="Outbound Transfer"){
			$totals[$money_source][$transaction_currency] = $totals[$money_source][$transaction_currency] - $$amount_to_use1;
			$amount_to_display1 = "-" . $$amount_to_use1 . " " . $transaction_currency;
		}
	}
	else{
		$totals[$money_source][$transaction_currency] = $totals[$money_source][$transaction_currency] + $$amount_to_use1;
		$amount_to_display1 = $$amount_to_use1 . " " . $transaction_currency;
	}



	$btc_price = $allcurrencyconversion['BTC'][$dateonly];
	$eth_price = $allcurrencyconversion['ETH'][$dateonly];
	$ltc_price = $allcurrencyconversion['LTC'][$dateonly];
	$nrg_price = $allcurrencyconversion['NRG'][$dateonly];
	$neo_price = $allcurrencyconversion['NEO'][$dateonly];

	$personal_converttousd_btc = $btc_price * $totals['personal']['BTC'];
	$personal_converttousd_eth = $eth_price * $totals['personal']['ETH'];
	$personal_converttousd_ltc = $ltc_price * $totals['personal']['LTC'];
	$personal_converttousd_nrg = $nrg_price * $totals['personal']['NRG'];
	$personal_converttousd_neo = $nrg_price * $totals['personal']['NEO'];

	//$subtotal1 = $totals['personal']['USD_Buy'] + $totals['personal']['USD_Sell'] + $personal_converttousd_btc + $personal_converttousd_eth + $personal_converttousd_ltc + $personal_converttousd_nrg;
	$subtotal1 = $personal_converttousd_btc + $personal_converttousd_eth + $personal_converttousd_ltc + $personal_converttousd_nrg + $personal_converttousd_neo;

	$investor_converttousd_btc = $btc_price * $totals['investor']['BTC'];
	$investor_converttousd_eth = $eth_price * $totals['investor']['ETH'];
	$investor_converttousd_ltc = $ltc_price * $totals['investor']['LTC'];
	$investor_converttousd_nrg = $nrg_price * $totals['investor']['NRG'];
	$investor_converttousd_neo = $nrg_price * $totals['investor']['NEO'];

	//$subtotal2 = $totals['investor']['USD_Buy'] + $totals['investor']['USD_Sell'] + $investor_converttousd_btc + $investor_converttousd_eth + $investor_converttousd_ltc + $investor_converttousd_nrg;
	$subtotal2 = $investor_converttousd_btc + $investor_converttousd_eth + $investor_converttousd_ltc + $investor_converttousd_nrg + $investor_converttousd_neo;


	$grandtotal = $subtotal1 + $subtotal2;

	$subtotal1_display = number_format($subtotal1);
	$subtotal2_display = number_format($subtotal2);
	$grandtotal_display = number_format($grandtotal);

	$table_source2 = substr($table_source, 5);
	if($xls_source!=''){
		$table_source2 .= " ($xls_source)";
	}

	//do the date before/after update
	if($comparedate_before==$dateonly){
		$comparison_values['before']['subtotal1'] = $subtotal1;
		$comparison_values['before']['subtotal2'] = $subtotal2;
		$comparison_values['before']['grandtotal'] = $grandtotal;
	}

	if($comparedate_after==$dateonly){
		$comparison_values['after']['subtotal1'] = $subtotal1;
		$comparison_values['after']['subtotal2'] = $subtotal2;
		$comparison_values['after']['grandtotal'] = $grandtotal;
	}



	//output transaction row
	if($amount_to_display1!='0'){

		if($filter_money_source==""){
			$output .= "<tr>";
			$output .= "<td class=color0 style='font-size:9px;'>$king_combined_id - $transaction_timestamp</td>";
			$output .= "<td class=color0>$transaction_currency_original</td>";
			$output .= "<td class=color0>$amount_to_display1</td>";
			$output .= "<td class=color0>$amount_to_display2</td>";
			$output .= "<td class=color0>$transaction_type $side</td>";
			$output .= "<td class=color0>$table_source2 ($money_source)</td>";
		}

		elseif(($filter_money_source=="personal")&&($money_source=="personal")){
			$output .= "<tr>";
			$output .= "<td class=color0 style='font-size:9px;'>$transaction_timestamp</td>";
			$output .= "<td class=color0>$transaction_currency_original</td>";
			$output .= "<td class=color0>$amount_to_display1</td>";
			$output .= "<td class=color0>$amount_to_display2</td>";
			$output .= "<td class=color0>$transaction_type $side</td>";
			$output .= "<td class=color0>$table_source2 ($money_source)</td>";
		}

		elseif(($filter_money_source=="investor")&&($money_source=="investor")){
			$output .= "<tr>";
			$output .= "<td class=color0 style='font-size:9px;'>$transaction_timestamp</td>";
			$output .= "<td class=color0>$transaction_currency_original</td>";
			$output .= "<td class=color0>$amount_to_display1</td>";
			$output .= "<td class=color0>$amount_to_display2</td>";
			$output .= "<td class=color0>$transaction_type $side</td>";
			$output .= "<td class=color0>$table_source2 ($money_source)</td>";
		}



		if($money_source=="personal"){
			if($filter_money_source=="personal"){	
				//$output .= "<td class=color1>".$totals['personal']['USD']."</td>";
				$output .= "<td class=color1>".$totals['personal']['USD_Buy']."</td>";
				$output .= "<td class=color1>".$totals['personal']['USD_Sell']."</td>";
				$output .= "<td class=color1>".$totals['personal']['BTC']."</td>";
				$output .= "<td class=color1>".$totals['personal']['ETH']."</td>";
				$output .= "<td class=color1>".$totals['personal']['LTC']."</td>";
				$output .= "<td class=color1>".$totals['personal']['NRG']."</td>";
				$output .= "<td class=color1>".$totals['personal']['NEO']."</td>";
			}
			if($filter_money_source==""){
				//$output .= "<td class=color1>".$totals['personal']['USD']."</td>";
				$output .= "<td class=color1>".$totals['personal']['USD_Buy']."</td>";
				$output .= "<td class=color1>".$totals['personal']['USD_Sell']."</td>";
				$output .= "<td class=color1>".$totals['personal']['BTC']."</td>";
				$output .= "<td class=color1>".$totals['personal']['ETH']."</td>";
				$output .= "<td class=color1>".$totals['personal']['LTC']."</td>";
				$output .= "<td class=color1>".$totals['personal']['NRG']."</td>";
				$output .= "<td class=color1>".$totals['personal']['NEO']."</td>";
				$output .= "<td class=color2>-</td>";
				$output .= "<td class=color2>-</td>";
				$output .= "<td class=color2>-</td>";
				$output .= "<td class=color2>-</td>";
				$output .= "<td class=color2>-</td>";
				$output .= "<td class=color2>-</td>";
				$output .= "<td class=color2>-</td>";
			}
		}
		elseif($money_source=="investor"){
			if($filter_money_source=="investor"){	
				//$output .= "<td class=color2>".$totals['investor']['USD']."</td>";
				$output .= "<td class=color2>".$totals['investor']['USD_Buy']."</td>";
				$output .= "<td class=color2>".$totals['investor']['USD_Sell']."</td>";
				$output .= "<td class=color2>".$totals['investor']['BTC']."</td>";
				$output .= "<td class=color2>".$totals['investor']['ETH']."</td>";
				$output .= "<td class=color2>".$totals['investor']['LTC']."</td>";
				$output .= "<td class=color2>".$totals['investor']['NRG']."</td>";
				$output .= "<td class=color2>".$totals['investor']['NEO']."</td>";
			}
			if($filter_money_source==""){
				$output .= "<td class=color1>-</td>";
				$output .= "<td class=color1>-</td>";
				$output .= "<td class=color1>-</td>";
				$output .= "<td class=color1>-</td>";
				$output .= "<td class=color1>-</td>";
				$output .= "<td class=color1>-</td>";
				$output .= "<td class=color1>-</td>";
				//$output .= "<td class=color2>".$totals['investor']['USD']."</td>";
				$output .= "<td class=color2>".$totals['investor']['USD_Buy']."</td>";
				$output .= "<td class=color2>".$totals['investor']['USD_Sell']."</td>";
				$output .= "<td class=color2>".$totals['investor']['BTC']."</td>";
				$output .= "<td class=color2>".$totals['investor']['ETH']."</td>";
				$output .= "<td class=color2>".$totals['investor']['LTC']."</td>";
				$output .= "<td class=color2>".$totals['investor']['NRG']."</td>";
				$output .= "<td class=color2>".$totals['investor']['NEO']."</td>";
			}
		}
		if($filter_money_source==""){	
			$output .= "<td class=color3>".$btc_price."</td>";
			$output .= "<td class=color3>".$eth_price."</td>";
			$output .= "<td class=color3>".$ltc_price."</td>";
			$output .= "<td class=color3>".$nrg_price."</td>";
			$output .= "<td class=color3>".$neo_price."</td>";
		}

		elseif(($filter_money_source=="personal")&&($money_source=="personal")){	
			$output .= "<td class=color3>".$btc_price."</td>";
			$output .= "<td class=color3>".$eth_price."</td>";
			$output .= "<td class=color3>".$ltc_price."</td>";
			$output .= "<td class=color3>".$nrg_price."</td>";
			$output .= "<td class=color3>".$neo_price."</td>";
		}

		elseif(($filter_money_source=="investor")&&($money_source=="investor")){	
			$output .= "<td class=color3>".$btc_price."</td>";
			$output .= "<td class=color3>".$eth_price."</td>";
			$output .= "<td class=color3>".$ltc_price."</td>";
			$output .= "<td class=color3>".$nrg_price."</td>";
			$output .= "<td class=color3>".$neo_price."</td>";
		}


		if($filter_money_source==""){	
			$output .= "<td class=color4>".$totals['personal']['USD_Buy']."</td>";
			$output .= "<td class=color4>".$totals['personal']['USD_Sell']."</td>";
			$output .= "<td class=color4>$".$personal_converttousd_btc."</td>";
			$output .= "<td class=color4>$".$personal_converttousd_eth."</td>";
			$output .= "<td class=color4>$".$personal_converttousd_ltc."</td>";
			$output .= "<td class=color4>$".$personal_converttousd_nrg."</td>";
			$output .= "<td class=color4>$".$personal_converttousd_neo."</td>";
			$output .= "<td class=color4 style='font-weight:bolder;'>$".$subtotal1_display."</td>";
			$output .= "<td class=color5>".$totals['investor']['USD_Buy']."</td>";
			$output .= "<td class=color5>".$totals['investor']['USD_Sell']."</td>";
			$output .= "<td class=color5>$".$investor_converttousd_btc."</td>";
			$output .= "<td class=color5>$".$investor_converttousd_eth."</td>";
			$output .= "<td class=color5>$".$investor_converttousd_ltc."</td>";
			$output .= "<td class=color5>$".$investor_converttousd_nrg."</td>";
			$output .= "<td class=color5>$".$investor_converttousd_neo."</td>";
			$output .= "<td class=color5 style='font-weight:bolder;'>$".$subtotal2_display."</td>";
		}

		elseif(($filter_money_source=="personal")&&($money_source=="personal")){	
			$output .= "<td class=color4>".$totals['personal']['USD_Buy']."</td>";
			$output .= "<td class=color4>".$totals['personal']['USD_Sell']."</td>";
			$output .= "<td class=color4>$".$personal_converttousd_btc."</td>";
			$output .= "<td class=color4>$".$personal_converttousd_eth."</td>";
			$output .= "<td class=color4>$".$personal_converttousd_ltc."</td>";
			$output .= "<td class=color4>$".$personal_converttousd_nrg."</td>";
			$output .= "<td class=color4>$".$personal_converttousd_neo."</td>";
			$output .= "<td class=color4 style='font-weight:bolder;'>$".$subtotal1_display."</td>";
		}

		elseif(($filter_money_source=="investor")&&($money_source=="investor")){	
			$output .= "<td class=color5>".$totals['investor']['USD_Buy']."</td>";
			$output .= "<td class=color5>".$totals['investor']['USD_Sell']."</td>";
			$output .= "<td class=color5>$".$investor_converttousd_btc."</td>";
			$output .= "<td class=color5>$".$investor_converttousd_eth."</td>";
			$output .= "<td class=color5>$".$investor_converttousd_ltc."</td>";
			$output .= "<td class=color5>$".$investor_converttousd_nrg."</td>";
			$output .= "<td class=color5>$".$investor_converttousd_neo."</td>";
			$output .= "<td class=color5 style='font-weight:bolder;'>$".$subtotal2_display."</td>";
		}

		if($filter_money_source==""){
			$output .= "<td style='font-weight:bolder;'>$".$grandtotal_display."</td>";
		}


		$output .= "</tr>";


	}

}


$output .= "</table>";

//calculate before/after

$subtotal1_change = ($comparison_values['before']['subtotal1']-$comparison_values['after']['subtotal1'])/$comparison_values['before']['subtotal1'];
$subtotal2_change = ($comparison_values['before']['subtotal2']-$comparison_values['after']['subtotal2'])/$comparison_values['before']['subtotal2'];
$grandtotal_change = ($comparison_values['before']['grandtotal']-$comparison_values['after']['grandtotal'])/$comparison_values['before']['grandtotal'];

$subtotal1_change_percent = round($subtotal1_change * 100, 2);
$subtotal2_change_percent = round($subtotal2_change * 100, 2);
$grandtotal_change_percent = round($grandtotal_change * 100, 2);

echo "from " . $comparedate_before . " to " . $comparedate_after . " personal crypto value was " . $subtotal1_change_percent . "% <br>";
echo "from " . $comparedate_before . " to " . $comparedate_after . " investor crypto value was " . $subtotal2_change_percent . "% <br>";
echo "from " . $comparedate_before . " to " . $comparedate_after . " total crypto value was " . $grandtotal_change_percent . "% <br>";


$contentsection = "<div class='col-lg-6'>";
$contentsection .= "<h1>personal</h1>";
$contentsection .= pp($totals['personal']);
$contentsection .= "</div>";

$contentsection .= "<div class='col-lg-6'>";
$contentsection .= "<h1>investor</h1>";
$contentsection .= pp($totals['investor']);
$contentsection .= "</div>";

//$contentsection .= "<h1>full history</h1>";

print_r($comparison_values);





function pp($arr){
    $retStr = '<ul>';
    if (is_array($arr)){
        foreach ($arr as $key=>$val){
            if (is_array($val)){
                $retStr .= '<li>' . $key . ' => ' . pp($val) . '</li>';
            }else{
                $retStr .= '<li>' . $key . ' => ' . $val . '</li>';
            }
        }
    }
    $retStr .= '</ul>';
    return $retStr;
}





?>


<html>
<head>
<title>King ReportingG</title>
<style>
.color0{
	background:#efe990;
}

.color1{
	background:#679ade;
}

.color2{
	background:#de6767;
}

.color3{
	background:#75de67;
}

.color4{
	background:#dead67;
}

.color5{
	background:#be67de;
}

.color6{
	background:#fa69ff;
}

</style>

  <script src="/js/library/jquery.js?v=2.6.1"></script>
  <script src="/js/library/jquery.form.js?v=2.6.1"></script>
  <script src="/js/library/jquery.popup.js?v=2.6.1"></script>
  <script src="/js/library/jquery.popin.js?v=2.6.1"></script>
  <script src="/js/library/jquery.gardenhandleajaxform.js?v=2.6.1"></script>
  <script src="/js/library/jquery.atwho.js?v=2.6.1"></script>
  <script src="/js/global.js?v=2.6.1"></script>
  <script src="/js/library/jquery.autosize.min.js?v=2.6.1"></script>

<script
  src="https://code.jquery.com/jquery-3.4.1.js"
  integrity="sha256-WpOohJOqMqqyKL9FccASB9O0KwACQJpFTUBLTYOVvVU="
  crossorigin="anonymous"></script>
<!-- Latest compiled and minified CSS -->
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">

<!-- Optional theme -->
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap-theme.min.css" integrity="sha384-rHyoN1iRsVXV4nD0JutlnGaslCJuC7uwjduW9SVrLvRYooPp2bWYgmgJQIXwl/Sp" crossorigin="anonymous">

<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datetimepicker/3.1.3/css/bootstrap-datetimepicker.min.css" rel="stylesheet" />
<link href="//maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css" rel="stylesheet" />

<script src="//cdnjs.cloudflare.com/ajax/libs/moment.js/2.9.0/moment-with-locales.min.js"></script>
<script src="//cdnjs.cloudflare.com/ajax/libs/bootstrap-datetimepicker/3.1.3/js/bootstrap-datetimepicker.min.js"></script>




</head>
<body>
<div class="container">
	<div class="row">
		<?php echo $contentsection; ?>

	</div>
	<div class="row">
		<form method="GET" action="kingreporting.php">

			<div class="col-lg-12">
				<h2>Filters</h2>
			</div>
			<div class="col-lg-2">
				<select id="filter_money_source" name="filter_money_source" class="form-control">
					<option value="">Money Source</option>
					<option value="personal">Personal</option>
					<option value="investor">Investor</option>
				</select>
			</div>
			<div class="col-lg-2">
				<select id="filter_table_source" name="filter_table_source" class="form-control">
					<option value="">Document</option>
					<option value="king_2ca15371">2ca15371</option>
					<option value="king_386DBKF_Transaction_History">386DBKF_Transaction_History</option>
					<option value="king_account">account</option>
					<option value="king_blockfi">blockfi</option>
					<option value="king_celsius">celsius</option>
					<option value="king_coinbase">coinbase</option>
					<option value="king_coin_ira">coin_ira</option>
					<option value="king_Energi_Masternode">Energi_Masternode</option>
					<option value="2018_Energi_Masternode_formatted">2018_Energi_Masternode_formatted</option>
					<option value="king_investor_nrg_sales_2019">investor_nrg_sales_2019</option>
					<option value="king_kucoin">kucoin</option>
					<option value="king_kucoin2">kucoin2</option>
					<option value="king_kucoin3">kucoin3</option>
					<option value="king_main_portfolio">main_portfolio</option>
					<option value="king_nrgjson2020">nrgjson2020</option>
					<option value="king_neon_wallet_activity">neon_wallet_activity</option>
					<option value="king_nrg_miner">nrg_miner</option>
					<option value="king_nrg_v2_port_A">nrg_v2_port_A</option>
					<option value="king_TomoChainWallet">TomoChainWallet</option>
					<option value="king_UNKP9G6_Transaction_History">UNKP9G6_Transaction_History</option>
				</select>
			</div>
			<div class="col-lg-2">
				<select id="filter_currency" name="filter_currency" class="form-control">
					<option value="">Currency</option>
					<option value="USD">USD</option>
					<option value="BTC">BTC</option>
					<option value="LTC">LTC</option>
					<option value="NRG">NRG</option>
					<option value="ETH">ETH</option>
					<option value="NEO">NEO</option>
				</select>
			</div>
			<div class="col-lg-2">
				<select id="filter_orderby" name="filter_orderby" class="form-control">
					<option value="">Order By</option>
					<option value="transaction_timestamp">timestamp</option>
					<option value="transaction_amount">amount</option>
				</select>
			</div>
			<div class="col-lg-2">
				<select id="filter_orderbydirectiond" name="filter_orderbydirectiond" class="form-control">
					<option value="">Order By Direction</option>
					<option value="ASC">ASC</option>
					<option value="DESC">DESC</option>
				</select>
			</div>
			<div class="col-lg-2">
			</div>
			<div class="col-lg-12">
				<input type="submit">
				<br>
				<a href="kingreporting.php">Reset</a>
			</div>
		</form>
	</div>
	<div class="row">
		<form>
			<div class="col-lg-6">
		        <div class="form-group">
		          <div class='input-group date' id='datetimepicker1'>
		            <input type='text' class="form-control input-lg" id="filter_date1" />
		            <span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span>
		          </div>
		        </div>
			</div>
			<div class="col-lg-6">
		        <div class="form-group">
		          <div class='input-group date' id='datetimepicker2'>
		            <input type='text' class="form-control input-lg" id="filter_date2" />
		            <span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span>
		          </div>
		        </div>
			</div>
<script>
	$(function () {


 $(function () {
   var bindDatePicker = function() {
		$(".date").datetimepicker({
        format:'YYYY-MM-DD',
			icons: {
				time: "fa fa-clock-o",
				date: "fa fa-calendar",
				up: "fa fa-arrow-up",
				down: "fa fa-arrow-down"
			}
		}).find('input:first').on("blur",function () {
			// check if the date is correct. We can accept dd-mm-yyyy and yyyy-mm-dd.
			// update the format if it's yyyy-mm-dd
			var date = parseDate($(this).val());

			if (! isValidDate(date)) {
				//create date based on momentjs (we have that)
				date = moment().format('YYYY-MM-DD');
			}

			$(this).val(date);
		});
	}
   
   var isValidDate = function(value, format) {
		format = format || false;
		// lets parse the date to the best of our knowledge
		if (format) {
			value = parseDate(value);
		}

		var timestamp = Date.parse(value);

		return isNaN(timestamp) == false;
   }
   
   var parseDate = function(value) {
		var m = value.match(/^(\d{1,2})(\/|-)?(\d{1,2})(\/|-)?(\d{4})$/);
		if (m)
			value = m[5] + '-' + ("00" + m[3]).slice(-2) + '-' + ("00" + m[1]).slice(-2);

		return value;
   }
   
   bindDatePicker();
 });

	});


</script>
</form>
</div>
</div>

<!--
<a href="kingreporting.php">Show all</a> | 
<a href="kingreporting.php?filter_money_source=investor">Show only investor</a> | 
<a href="kingreporting.php?filter_money_source=personal">Show only personal</a> | 
-->


<?php echo $output; ?>




</body>
</html>