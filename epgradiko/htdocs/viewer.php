<?php
$script_path = dirname( __FILE__ );
chdir( $script_path );
include_once( $script_path . '/../config.php');
include_once(INSTALL_PATH . '/include/DBRecord.class.php' );
include_once(INSTALL_PATH . '/include/reclib.php' );
include_once(INSTALL_PATH . '/include/Settings.class.php' );

if( ! file_exists( INSTALL_PATH.'/settings/config.xml') && !file_exists( '/etc/epgrecUNA/config.xml' ) ) {
	exit( "<script type=\"text/javascript\">\n" .
	"<!--\n".
	"window.open(\"/maintenance.php?return=initial\",\"_self\");".
	"// -->\n</script>" );
}

$settings = Settings::factory();

$recorder = '';
$mirakc_timeshift_id = '';
$starttime = '';
$channel = '';
$sid = '';
$type = '';
$trans = '';
$reserve_id = '';
$trans_id = '';
$name = '';

if( isset( $_GET['recorder'] ) ) $recorder = $_GET['recorder'];
if( isset( $_GET['mirakc_timeshift_id'] ) ) $mirakc_timeshift_id = $_GET['mirakc_timeshift_id'];
if( isset( $_GET['starttime'] ) ) $starttime = $_GET['starttime'];
if( isset( $_GET['ch'] ) ) $channel = $_GET['ch'];
if( isset( $_GET['sid'] ) ) $sid = $_GET['sid'];
if( isset( $_GET['type'] ) ) $type = substr($_GET['type'], 0, 2 );
if( isset( $_GET['trans'] ) ) $trans = $_GET['trans'];
if( isset( $_GET['reserve_id'] ) ) $reserve_id = $_GET['reserve_id'];
if( isset( $_GET['trans_id'] ) ) $trans_id = $_GET['trans_id'];
if( isset( $_GET['name'] ) ) $name = $_GET['name'];
else $name = 'NO_NAME';

$sendstream_mode = FALSE;
if ($channel || $trans !=='') $sendstream_mode = TRUE;

$abstract = '';
$dh = '';
$dm = '';
$ds = '';
$target_path = '';
if($reserve_id){
	try{
		$rrec = new DBRecord( RESERVE_TBL, 'id', $reserve_id );
		if($rrec){
			$name     = htmlspecialchars(str_replace(array("\r\n","\r","\n"), '', $rrec->title),ENT_QUOTES);
			$abstract = htmlspecialchars(str_replace(array("\r\n","\r","\n"), '', $rrec->description),ENT_QUOTES);
			$start_time = toTimestamp($rrec->starttime);
			$end_time = toTimestamp($rrec->endtime );
			$duration = $end_time - $start_time + $settings->former_time;
			$dh       = $duration / 3600;
			$duration = $duration % 3600;
			$dm       = $duration / 60;
			$duration = $duration % 60;
			$ds       = $duration;
			$target_path = '/recorded';
			if($trans_id){
				$transcode = new DBRecord( TRANSCODE_TBL, 'id', $trans_id );
				if($transcode){
					$address = '/trans_id/'.$trans_id.'.'.pathinfo($RECORD_MODE[$transcode->mode]['tsuffix'], PATHINFO_EXTENSION);
				}else{
					jdialog( '視聴情報がありません<br>', 'recordedTable.php' );
				}
			}else{
				$address = '/reserve_id/'.$reserve_id.'/'.$rrec->title;
			}
		}else{
			jdialog( '録画情報がありません<br>', 'recordedTable.php' );
		}
	}catch( Exception $e ){
		exit( $e->getMessage() );
	}
}

if($recorder){
	$target_path = '/timeshift';
	$address = '/'.$recorder;
	if( $mirakc_timeshift_id ) $address .= '/'.$mirakc_timeshift_id.'/'.$name;
	else{
		if( $starttime ){
			if( sscanf( $starttime , '%04d%2d%2d%2d%2d%2d', $y, $mon, $day, $H ,$M, $S ) == 6 ){
				$date = new DateTime($y.'-'.$mon.'-'.$day.' '.$H.':'.$M.':'.$S);
				$starttime = $date->format('Y-m-d H:i:s');
				$start_time = $date->format('U');
				$ft = $date->format('YmdHis');
				$db_programs = DBRecord::createRecords( PROGRAM_TBL, "WHERE channel_disc='EX_".$recorder."' AND starttime<='".$starttime."' AND endtime>'".$starttime."' ORDER BY starttime" );
				if( count($db_programs) ){
					$date = new DateTime($db_programs[0]->endtime);
					$end_time = $date->format('U');
				}else{
					$end_time = $start_time + 1440 * 60;
				}
				$to = date('YmdHis', $end_time);
				$target_path = '/timefree';
				$address = '/'.$recorder.'/'.$ft.'/'.$to.'/'.$name;
			}else{
				jdialog( '視聴情報がありません<br>', 'timeshiftTable.php' );
			}
		}else{
			jdialog( '視聴情報がありません<br>', 'timeshiftTable.php' );
		}
	}
}
if($sendstream_mode){
	$target_path = '/stream';
	if($trans !=='') $target_path .= $trans;
	if($channel){
		$address = '/type/'.$type.'/'.$channel.'/'.$sid.'/'.$name;
	}else{
		if($trans_id){
			$address ='/trans_id/'.$trans_id.'/'.$name;
		}else{
			if($reserve_id){
				$address = '/reserve_id/'.$reserve_id.'/'.$name;
			}else{
				if($recorder){
					if( $mirakc_timeshift_id ){
						$address = '/timeshift/'.$recorder;
						$address .= '/'.$mirakc_timeshift_id.'/'.$name;
					}else{
						$address = '/timefree/'.$recorder;
						$address .= '/'.$ft.'/'.$to.'/'.$name;
					}
				}
			}
		}
	}
}
$protocol = isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off' || isset($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on' ? 'https' : 'http';

$host = $_SERVER["HTTP_HOST"];
$base_address = $host.$target_path;
$source_url = $protocol.'://'.$base_address.$address;

if($trans !=='' || $trans_id){
	if(isset($_COOKIE['video_urlscheme']) && $_COOKIE['video_urlscheme'] !== '' ){
		$url_scheme = $_COOKIE['video_urlscheme'];
		$str_rep = array(
			'%PROTOCOL%'	=> $protocol,
			'%ADDRESS%'	=> $base_address.$address,
			'%address%'	=> $base_address.rawurlencode($address),
		);
		$url = strtr( $url_scheme, $str_rep );
		header('Location: '.$url, TRUE, 307);
		echo '<a href="'.$url.'">起動</a>';
	}else{
		echo '<html>';
		echo '<head>';
		echo '<meta charset="UTF-8">';
		echo '<title>'.$name.'</title>';
		echo '</head>';
		echo '<body style="padding: 0px; margin: 0px; background-color: black;" >';
		echo '<DIV STYLE="vertical-align:middle;">';
		echo '<video src="'.$source_url.'" width="100%" preload="auto" autoplay controls playsinline onclick="this.play();"/>';
		echo '<p>動画を再生するにはvideoタグをサポートしたブラウザが必要です。</p></video>';
		echo '</DIV>';
		echo '</body>';
		echo '</html>';
	}
}else{
	if(isset($_COOKIE['ts_urlscheme']) && $_COOKIE['ts_urlscheme'] !== '' ){
		$url_scheme = $_COOKIE['ts_urlscheme'];
		$str_rep = array(
			'%PROTOCOL%'	=> $protocol,
			'%ADDRESS%'	=> $base_address.$address,
			'%address%'	=> $base_address.rawurlencode($address),
		);
		$url = strtr( $url_scheme, $str_rep );
		header('Location: '.$url, TRUE, 307);
		echo '<a href="'.$url.'">起動</a>';
	}else{
//		echo '<html>';
//		echo '<head>';
//		echo '<meta charset="UTF-8">';
//		echo '<title>'.$name.'</title>';
//		echo '</head>';
//		echo '<body style="padding: 0px; margin: 0px; background-color: black;" >';
//		echo '<script src="/js/mpegts.js"></script>';
//		echo '<video id="videoElement"></video>';
//		echo '<script>';
//		echo '    if (mpegts.getFeatureList().mseLivePlayback) {';
//		echo '        var videoElement = document.getElementById("videoElement");';
//		echo '        var player = mpegts.createPlayer({';
//		echo '            type: "mse",';
//		echo '            isLive: true,';
//		echo '            url: "'.$source_url.'"';
//		echo '        });';
//		echo '        player.attachMediaElement(videoElement);';
//		echo '        player.load();';
//		echo '        player.play();';
//		echo '    }else{';
//		echo '        document.write("<p>動画を再生するにはmseをサポートしたブラウザが必要です。</p>")';
//		echo '    }';
//		echo '</script>';
//		echo '</body>';
//		echo '</html>';
		header('Expires: Thu, 01 Dec 1994 16:00:00 GMT');
		header('Last-Modified: '. gmdate('D, d M Y H:i:s'). ' GMT');
		header('Cache-Control: no-cache, must-revalidate');
		header('Cache-Control: post-check=0, pre-check=0', false);
		header('Pragma: no-cache');
		header('Content-type: video/x-ms-asf; charset="UTF-8"');
		header('Content-Disposition: inline; filename="'.$name.'.asx"');

		echo '<ASX version = "3.0">';
		echo '<PARAM NAME = "Encoding" VALUE = "UTF-8" />';
		echo '<ENTRY>';
		echo '<TITLE>'.$name.'</TITLE>';
		echo '<REF HREF="'.$source_url.'" />';

		echo '<ABSTRACT>'.$abstract.'</ABSTRACT>';
		echo '<DURATION VALUE="'.sprintf( '%02d:%02d:%02d',$dh, $dm, $ds ).'" />';
		echo '</ENTRY>';
		echo '</ASX>';
	}
}
ob_flush();
flush();
?>
