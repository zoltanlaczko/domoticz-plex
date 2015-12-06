<?php
// instructions @ https://github.com/zoltanlaczko/domoticz-plex

$domoticz_url="http://127.0.0.1:8080"; // no trailing slash!
$plex_url="http://10.0.0.200:32400"; // no trailing slash!

// translation
$plex_status=array(
	'idle'=>'Idle',
	'playing'=>'Playing',
	'pause'=>'Pause',
);

/* You don't have to modify anything below! */

function get_url($url) {
    $ch=curl_init();
    curl_setopt($ch, CURLOPT_ENCODING , "");
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_USERAGENT, "plex script");

    curl_setopt($ch, CURLOPT_DNS_CACHE_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_DNS_USE_GLOBAL_CACHE, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
    $data=curl_exec($ch);
    curl_close($ch);
    return $data;
}

function unserialize_xml($input, $callback = null, $recurse = false){
    $data=((!$recurse) && is_string($input))? simplexml_load_string($input): $input;
    if ($data instanceof SimpleXMLElement) $data=(array) $data;
    if (is_array($data)) foreach ($data as &$item) $item=unserialize_xml($item, $callback, true);
    return (!is_array($data) && is_callable($callback))? call_user_func($callback, $data): $data;
}

function format_plex_duration($duration){
	$text='';
	if ($duration>0){
		$min=round($duration/1000/60);
		if ((floor($min/60))) $text.=(floor($min/60)).':';
		if ($text) $text.=sprintf("%02d", ($min%60));
		else $text.=($min%60);
	}
	return $text; 
}

$device_array=array();
$devices=json_decode(get_url($domoticz_url.'/json.htm?type=devices&filter=all&used=true&order=Name'), true);
if ($devices && is_array($devices) && isset($devices['result']) && is_array($devices['result'])) {
	foreach ($devices['result'] as $d) {
		if (preg_match("/^plex__(.*)$/", $d['HardwareName'], $m)) {
			$device_array[$m[1]]=array('id'=>$m[1], 'idx'=>$d['idx'], 'status'=>$d['Data'], 'new_status'=>$plex_status['idle']);
		}
	}
}

if (count($device_array)){
	$plex=unserialize_xml(simplexml_load_string(get_url($plex_url.'/status/sessions')));

	if (isset($plex['Video']['@attributes'])) $plex['Video']=array('0'=>$plex['Video']);
	if (isset($plex['Video'])){
		foreach ($plex['Video'] as $v){
			if (isset($v['Player']['@attributes']['machineIdentifier'])){
				$plex_device=$v['Player']['@attributes']['machineIdentifier'];
				if (isset($device_array[$plex_device]) && isset($v['Media']['@attributes']['duration'])){
					if ($v['@attributes']['type']=="episode"){
						$title=$v['@attributes']['grandparentTitle'].' S'.$v['@attributes']['parentIndex'].' E'.$v['@attributes']['index'].' '.$v['@attributes']['title'];
					} else $title=$v['@attributes']['title'];
					$device_array[$plex_device]['new_status']=$plex_status[$v['Player']['@attributes']['state']].': '.$title.' ['.format_plex_duration($v['@attributes']['viewOffset']).'/'.format_plex_duration($v['Media']['@attributes']['duration']).']';
				}
			}
		}
	}

	foreach ($device_array as $d){
		if ($d['status']!=$d['new_status']) get_url($domoticz_url.'/json.htm?type=command&param=udevice&idx='.$d['idx'].'&nvalu=0&svalue='.urlencode($d['new_status']));
	}
}

// EOF
?>