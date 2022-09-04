#!/usr/bin/php
<?php
$script_path = dirname( __FILE__ );
chdir( $script_path );
include_once( $script_path . '/../config.php');
include_once( INSTALL_PATH . '/include/Settings.class.php' );
include_once( INSTALL_PATH . '/include/recLog.inc.php' );
include_once( INSTALL_PATH . '/include/etclib.php' );
include_once( INSTALL_PATH . '/include/reclib.php' );

function optimize_table( $table ){
	global $dbh, $settings;
	reclog( 'テーブル最適化::'.$settings->tbl_prefix.$table.'最適化開始', EPGREC_DEBUG );
	if( mysqli_query( $dbh, 'ALTER TABLE '.$settings->tbl_prefix.$table.' ENGINE InnoDB' )) {
		reclog( 'テーブル最適化::'.$settings->tbl_prefix.$table.'最適化終了', EPGREC_DEBUG );
	}else{
		reclog( 'テーブル最適化::'.$settings->tbl_prefix.$table.'最適化失敗('.mysqli_error($dbh).')', EPGREC_ERROR );
	}
}
run_user_regulate();
new single_Program('optimaizeTable');

$settings = Settings::factory();

//テーブル最適化
reclog( 'テーブル最適化::開始' );
$dbh = mysqli_connect( $settings->db_host, $settings->db_user, $settings->db_pass, $settings->db_name );
optimize_table(RESERVE_TBL);
optimize_table(PROGRAM_TBL);
optimize_table(CHANNEL_TBL);
optimize_table(CATEGORY_TBL);
optimize_table(KEYWORD_TBL);
optimize_table(TRANSCODE_TBL);
optimize_table(TRANSEXPAND_TBL);
reclog( 'テーブル最適化::終了' );
?>
