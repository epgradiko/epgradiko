<?php

$script_path = dirname( __FILE__ );
chdir( $script_path );
include_once( $script_path . '/../config.php');
include_once(INSTALL_PATH . '/include/DBRecord.class.php' );

function regular_mark( $marks, $mode = '' ){
	$return_str = '';
	if( $mode !== 'post' ){
		foreach( array_keys(array_column(ProgramMark,'pre','name'),true) as $mark ) {
			if( strpos($marks, $mark) !== FALSE ) $return_str .= $mark;
		}
	}
	if( $mode !== 'pre' ){
		foreach( array_keys(array_column(ProgramMark,'post','name'),true) as $mark ) {
			if( strpos($marks, $mark) !== FALSE ) $return_str .= $mark;
		}
	}
	return $return_str;
}

function get_lightest_trans( $reserve_id, $ext = '' ){
	$trans_id = 0;
	$trans_mode = 0;
	$trans_ext = '';
	$return_set = array();
	$trans_all = DBRecord::createRecords( TRANSCODE_TBL, 'WHERE rec_id='.$reserve_id.' AND status = 2' );
	$play_trans_rate = 999999;
	foreach($trans_all as $transcode){
		if( file_exists($transcode->path) ){
			$path_ext = pathinfo($transcode->path, PATHINFO_EXTENSION);
			if( $ext == '' || $ext == $path_ext ){
				if( isset($RECORD_MODE[$transcode->mode]['tm_rate']) ){
					$trans_rate = $RECORD_MODE[$transcode->mode]['tm_rate'];
				}else{
					$trans_rate = 9999;
				}
				if( $trans_rate < $play_trans_rate ){
					$play_trans_rate = $trans_rate;
					$trans_id = $transcode->id;
					$trans_mode = $transcode->mode;
					$trans_ext = $path_ext;
				}
			}
		}
	}
	unset( $trans_all );
	$return_set[0] = $trans_id;
	$return_set[1] = $trans_mode;
	$return_set[2] = $trans_ext;
	return $return_set;
}

function get_content_type( $strow ){
	$needles = array(	'aac'	=> 'audio/x-aac',
				'oga'	=> 'audio/ogg',
				'm4a'	=> 'audio/mp4',
				'mp3'	=> 'audio/mpeg',
				'wav'	=> 'audio/wav',
				'ogg'	=> 'video/ogg',
				'ogv'	=> 'video/ogg',
				'mp4'	=> 'video/mp4',
				'mpeg'	=> 'video/mpeg',
				'mpg'	=> 'video/mpeg',
				'ts'	=> 'video/mp2t',
				'webm'	=> 'video/webm',
	);
	if( isset($needles[$strow]) ) return $needles[$strow];
	return 'application/octet-stream';
}

function get_ffmpeg_status( $transcode_id ) {
	global $settings;
	$return_array = array();
	$status_file = '/tmp/trans_'.$transcode_id;
	if( file_exists($status_file) ){
		exec('tail -n 12 '.$status_file, $statuses);
		$skip = TRUE;
		foreach($statuses as $status){
			$explode_text = explode("=", $status);
			if( $explode_text[0] == 'total_size' ) $skip = FALSE;
			if( $skip ) continue;
			if( $explode_text[0] == 'total_size' ){
				$explode_text[0] = 'size';
				$explode_text[1] = round($explode_text[1] / (1024*1024*1024), 2).'GB';
			}else if( $explode_text[0] == 'out_time' ){
				$explode_text[0] = 'time';
				$explode_text[1] = substr($explode_text[1], 0, 8);
			}else if( $explode_text[0] == 'dup_frames' ) $explode_text[0] = 'dup';
			else if( $explode_text[0] == 'drop_frames' ) $explode_text[0] = 'drop';
			$return_array[$explode_text[0]] = $explode_text[1];
		}
		$transcode_obj = new DBRecord( TRANSCODE_TBL );
		$transcode = $transcode_obj->fetch_array( NULL, NULL, 'id='.$transcode_id.' AND status=1' );
		if( $transcode ){
			$fp = fopen( INSTALL_PATH.$settings->plogs.'/'.$transcode[0]['rec_id'].'_'.$transcode_id.'.ffmpeglog', 'w' );
			foreach( $return_array as $status => $status_value ){
				if( $status == 'dup'
				    || $status == 'drop'
				    || $status == 'size'
				    || $status == 'time'
				    || $status == 'progress' ){
					if($status == 'progress' && $status_value =='continue' ) $wrt_str = 'transcoding..';
					else if($status == 'progress' && $status_value =='end' ) $wrt_str = '';
					else $wrt_str = $status."=".$status_value."\n";
					if( $wrt_str) fwrite( $fp, (string)$wrt_str );
				}
			}
			fclose( $fp );
		}
	}
	return $return_array;
}

function url_get_contents( $url , $uds = '' ){
	$ch = url_open( $url , $uds );
	$data = curl_exec($ch);
	curl_close($ch);
	return $data;
}
function url_get_contents_range( $url, $uds = '', $start = 0, $bytes = 0 ) {
	$writefn = function( $ch, $chunk ) use( $bytes, &$datadump ){
		static $bytes_sent = 0;

		$chunk_len = strlen( $chunk );

		if( $bytes && $bytes <= $bytes_sent + $chunk_len ){
			$datadump .= substr( $chunk, 0, $bytes - $bytes_sent);
			return -1;
		}else{
			$datadump .= $chunk;
			$bytes_sent += $chunk_len;
		}
		return $chunk_len;
	};

	$ch = url_open( $url, $uds );
	curl_setopt( $ch, CURLOPT_HEADER, FALSE );
	curl_setopt( $ch, CURLOPT_BINARYTRANSFER, 1 );
	curl_setopt( $ch, CURLOPT_RANGE, $start."-" );
	curl_setopt( $ch, CURLOPT_WRITEFUNCTION, $writefn );
	$data = curl_exec( $ch );
	curl_close( $ch );
	return $datadump;
}

function url_open( $url , $uds = '' ){
	//curlセッション初期化
	$ch = curl_init();
	//URLとオプションを指定する
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	if( $uds !== '' ){
		curl_setopt($ch, CURLOPT_UNIX_SOCKET_PATH, $uds);
	}
	curl_setopt($ch, CURLOPT_URL, $url);
	return $ch;
}

class single_Program{
	private $app_name;

	public function __construct($app_name){
		$this->app_name = $app_name;
		// 既に同一タスクが起動中の場合
		if (file_exists('/tmp/'.$app_name.'.pid')){
			exit(1);
		}
		file_put_contents('/tmp/'.$app_name.'.pid', posix_getpid());

		// Ctrl+C等で中断されたときにメッセージを表示し、pidファイルが削除されるようにする
		pcntl_async_signals(true);
		pcntl_signal(SIGINT, function(){
			exit;
		});
	}

	public function __destruct(){
		unlink('/tmp/'.$this->app_name.'.pid');
	}
}

?>
