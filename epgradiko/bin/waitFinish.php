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

run_user_regulate();
$settings = Settings::factory();

if( isset($argv[1]) ) $recorder = $argv[1];
else $recorder = '';
if( isset($argv[2]) ) $timeshift_id = $argv[2];
else $timeshift_id = 0;

if( !( $recorder && $timeshift_id ) ){
	echo "引数がありません\n";
	exit(1);
}

$ts_base_addr = 'http://'.$settings->timeshift_address.'/api/timeshift';
$program_raw = @json_decode(file_get_contents($ts_base_addr.'/'.urlencode($recorder).'/records/'.$timeshift_id),TRUE);
if( !isset($program_raw['recording']) ){
	echo "番組情報が取得できません\n";
	exit(1);
}
$time_started = time();
while( isset($program_raw['recording']) && $program_raw['recording'] ){
	$program_raw = @json_decode(file_get_contents($ts_base_addr.'/'.urlencode($recorder).'/records/'.$timeshift_id),TRUE);
	sleep(5);
}

if((int)(time() - $time_started))reclog('waitFinish::『'.$program_raw['program']['name'].'』録画完了まで'.(time() - $time_started).'秒待ちました', EPGREC_DEBUG);

?>
