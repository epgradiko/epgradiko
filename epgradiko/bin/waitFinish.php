#!/usr/bin/php
<?php
$script_path = dirname( __FILE__ );
chdir( $script_path );
include_once( $script_path . '/../config.php');
include_once( INSTALL_PATH . '/include/DBRecord.class.php' );
include_once( INSTALL_PATH . '/include/Reservation.class.php' );
include_once( INSTALL_PATH . '/include/Settings.class.php' );
include_once( INSTALL_PATH . '/include/recLog.inc.php' );
include_once( INSTALL_PATH . '/include/reclib.php' );
include_once( INSTALL_PATH . '/include/etclib.php' );

run_user_regulate();
$settings = Settings::factory();

if( isset($argv[1]) ) $recorder = $argv[1];
else $recorder = '';
if( isset($argv[2]) ) $mirakc_timeshift_id = $argv[2];
else $mirakc_timeshift_id = 0;

if( !( $recorder && $mirakc_timeshift_id ) ){
	echo "引数がありません\n";
	exit(1);
}

if( isset( $settings->mirakc_timeshift ) && $settings->mirakc_timeshift !== 'none' ){
	switch( $settings->mirakc_timeshift ){
		case 'tcp':
			$ts_base_addr = 'http://'.$settings->mirakc_timeshift_address.'/api/timeshift';
			$uds = '';
			break;
		case 'uds':
			$ts_base_addr = 'http://mirakc/api/timeshift';
			$uds = $settings->mirakc_timeshift_uds;
			break;
		default:
			$ts_base_addr = '';
			$uds = '';
	}
	if( $ts_base_addr || $uds ){
		$program_raw = @json_decode(url_get_contents($ts_base_addr.'/'.urlencode($recorder).'/records/'.$mirakc_timeshift_id, $uds),TRUE);
		if( !isset($program_raw['recording']) ){
			echo "番組情報が取得できません\n";
			exit(1);
		}
	}else{
		echo "mirakc接続情報が取得できません\n";
		exit(1);
	}
}else{
	echo "mirakc接続情報がありません\n";
	exit(1);
}
$time_started = time();
while( isset($program_raw['recording']) && $program_raw['recording'] ){
	$program_raw = @json_decode(url_get_contents($ts_base_addr.'/'.urlencode($recorder).'/records/'.$mirakc_timeshift_id, $uds),TRUE);
	sleep(5);
}

if((int)(time() - $time_started))reclog('waitFinish::『'.$program_raw['program']['name'].'』完了まで'.(time() - $time_started).'秒待ちました', EPGREC_DEBUG);

?>
