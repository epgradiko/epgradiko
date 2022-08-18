<?php
$script_path = dirname( __FILE__ );
chdir( $script_path );
include_once( $script_path . '/../config.php');
include_once( INSTALL_PATH . '/include/DBRecord.class.php' );
include_once( INSTALL_PATH . '/include/Settings.class.php' );


if( isset($_POST['sid']) && isset($_POST['channel_disc']) && isset($_POST['skip']) ) {
	
	try {
		$crec = new DBRecord( CHANNEL_TBL, 'channel_disc', $_POST['channel_disc'] );
		$crec->sid = trim($_POST['sid']);
		$crec->skip = (int)(trim($_POST['skip']));
		$crec->update();
	}
	catch( Exception $e ) {
		exit('Error: チャンネル情報更新失敗' );
	}
}
?>
