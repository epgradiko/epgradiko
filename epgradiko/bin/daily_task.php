#!/usr/bin/php
<?php
$script_path = dirname( __FILE__ );
chdir( $script_path );
include_once('../config.php');
include_once( INSTALL_PATH . '/include/DBRecord.class.php' );
include_once( INSTALL_PATH . '/include/Reservation.class.php' );
include_once( INSTALL_PATH . '/include/Settings.class.php' );
include_once( INSTALL_PATH . '/include/reclib.php' );
include_once( INSTALL_PATH . '/include/etclib.php' );

// パーミッションを返す
function getPerm( $file ) {
	$ss = @stat( $file );
	return sprintf('%o', ($ss['mode'] & 000777));
}

run_user_regulate();
new single_Program('daily_task');
$settings = Settings::factory();

reclog('daily_task::デイリー処理開始');
if( file_exists( INSTALL_PATH . '/settings/daily_tasks' ) && is_dir( INSTALL_PATH . '/settings/daily_tasks' ) ){
	$tasks = glob( INSTALL_PATH . '/settings/daily_tasks/*' );

	foreach( $tasks as $task ){
		if( ! file_exists( $task) ) continue;
		$task_permit = getPerm( $task );
		if( substr($task_permit, 0, 1) == '7' ){
			reclog('daily_task::'.$task.' 処理開始', EPGREC_DEBUG);
			$ret = shell_exec( $task );
			if( $ret === FALSE ) reclog('daily_task::'.$task.' 実行失敗', EPGREC_ERROR);
			else{
				if( $ret !== NULL ) reclog('daily_task::'.$task.' '.$ret);
				reclog('daily_task::'.$task.' 実行終了', EPGREC_DEBUG);
			}
		}
	}
}
reclog('daily_task::デイリー処理終了');
?>
