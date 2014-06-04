<?php
header('Content-Type: application/xml');

$SERVER = 'http://dj.bronyradio.com:7090';
$STATS_FILE = '/status.xsl';

//create a new curl resource
$ch = curl_init();

//set url
curl_setopt($ch,CURLOPT_URL,$SERVER.$STATS_FILE);
curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);

$output = curl_exec($ch);

//close curl resource to free up system resources
curl_close($ch);

//build array to store our radio stats for later use
$radio_info = array();
$radio_info['server'] = $SERVER;
$radio_info['title'] = '';
$radio_info['description'] = '';
$radio_info['content_type'] = '';
$radio_info['mount_start'] = '';
$radio_info['bit_rate'] = '';
$radio_info['listeners'] = '';
$radio_info['most_listeners'] = '';
$radio_info['genre'] = '';
$radio_info['url'] = '';
$radio_info['now_playing'] = array();
$radio_info['now_playing']['artist'] = '';
$radio_info['now_playing']['track'] = '';

//loop through $ouput and sort into our different arrays
$temp_array = array();

$search_for = "<td\s[^>]*class=\"streamdata\">(.*)<\/td>";
$search_td = array('<td class="streamdata">','</td>');

if(preg_match_all("/$search_for/siU",$output,$matches)) {
   foreach($matches[0] as $match) {
      $to_push = str_replace($search_td,'',$match);
      $to_push = trim($to_push);
      array_push($temp_array,$to_push);
   }
}

//sort our temp array into our ral array
$radio_info['title'] = $temp_array[0];
$radio_info['description'] = $temp_array[1];
$radio_info['content_type'] = $temp_array[2];
$radio_info['mount_start'] = $temp_array[3];
$radio_info['bit_rate'] = $temp_array[4];
$radio_info['listeners'] = $temp_array[5];
$radio_info['most_listeners'] = $temp_array[6];
$radio_info['genre'] = $temp_array[7];
$radio_info['url'] = $temp_array[8];

$x = explode(" - ",$temp_array[9]);
$radio_info['now_playing']['artist'] = $x[0];
$radio_info['now_playing']['track'] = $x[1];

$vinyl = simplexml_load_file('http://dj.bronyradio.com:8000/stats?sid=1');
$velvet = simplexml_load_file('http://radio.ponyvillelive.com:8014/stats?sid=1');

try
{
$Listener_Total = intval(intval($vinyl->CURRENTLISTENERS) + intval($velvet->CURRENTLISTENERS) + intval($radio_info['listeners']));
//$Listener_Total = intval($velvet->CURRENTLISTENERS);
$PeakListeners = intval(intval($vinyl->PEAKLISTENERS) + intval($velvet->PEAKLISTENERS) + intval($radio_info['most_listeners']));
//$PeakListeners = intval($velvet->PEAKLISTENERS);
$Max_ListenerTotal = 500 + 100 + 200;
//$Max_ListenerTotal = 200;

$Uni_Listeners = intval(intval($velvet->UNIQUELISTENERS) + intval($vinyl->UNIQUELISTENERS) + intval($radio_info['listeners']));
}

catch (Exception $e) 
{
	$Listener_Total = intval($velvet->CURRENTLISTENERS);
	$PeakListeners = intval($velvet->PEAKLISTENERS);
	$Max_ListenerTotal = 200;
}

$NP = $velvet->SONGTITLE;
?>
<SHOUTCASTSERVER>
 <CURRENTLISTENERS><?php echo $Listener_Total; ?></CURRENTLISTENERS>
 <PEAKLISTENERS><?php echo $PeakListeners; ?></PEAKLISTENERS>
 <MAXLISTENERS><?php echo $Max_ListenerTotal; ?></MAXLISTENERS>
 <UNIQUELISTENERS><?php echo $Uni_Listeners;?></UNIQUELISTENERS>
 <AVERAGETIME><?php echo $velvet->AVERAGETIME; ?></AVERAGETIME>
 <SERVERGENRE>Pony Electronica</SERVERGENRE>
 <SERVERURL>http://ponyvillefm.com</SERVERURL>
 <SERVERTITLE>PonyvilleFM</SERVERTITLE>
 <SONGTITLE><?php echo str_replace('&', '&amp;',$NP); ?></SONGTITLE>
 <NEXTTITLE><?php echo str_replace('&', '&amp;',$velvet->NEXTTITLE); ?></NEXTTITLE>
 <STREAMHITS><?php echo $velvet->STREAMHITS;?></STREAMHITS>
 <STREAMSTATUS><?php echo $velvet->STREAMSTATUS;?></STREAMSTATUS>
 <STREAMPATH><?php echo $velvet->STREAMPATH;?></STREAMPATH>
 <BITRATE><?php echo $velvet->BITRATE;?></BITRATE>
 <CONTENT><?php echo $velvet->CONTENT;?></CONTENT>
 <VERSION><?php echo $velvet->VERSION;?></VERSION>
</SHOUTCASTSERVER>