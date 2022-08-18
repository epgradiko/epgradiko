<?php
$script_path = dirname( __FILE__ );
chdir( $script_path );
include_once( $script_path . '/../config.php');
include_once( INSTALL_PATH . '/include/DBRecord.class.php' );
include_once( INSTALL_PATH . '/include/Reservation.class.php' );
include_once( INSTALL_PATH . '/include/reclib.php' );
include_once( INSTALL_PATH . '/include/Keyword.class.php' );

if( isset($_GET['keyword_id'])) {
	try {
		$rec = new Keyword( 'id', $_GET['keyword_id'] );
		$tran_ex = DBRecord::createRecords( TRANSEXPAND_TBL, 'WHERE key_id='.$rec->id );
		foreach( $tran_ex as $tran_set )
			$tran_set->delete();
		$rec->delete();
	}
	catch( Exception $e ) {
		exit( 'Error:' . $e->getMessage() );
	}
}
else exit( 'Error:キーワードIDが指定されていません' );
?>
