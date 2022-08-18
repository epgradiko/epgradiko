<?php
$script_path = dirname( __FILE__ );
chdir( $script_path );
include_once( $script_path . '/../config.php');
include_once(INSTALL_PATH."/include/DBRecord.class.php");
include_once(INSTALL_PATH."/include/reclib.php");
include_once(INSTALL_PATH."/include/Settings.class.php");

$settings = Settings::factory();

if( !isset( $_POST['reserve_id'] ) ) {
	exit("Error: IDが指定されていません" );
}
$reserve_id = $_POST['reserve_id'];

if( $settings->mediatomb_update == 1 ) {
	$dbh = @mysqli_connect( $settings->db_host, $settings->db_user, $settings->db_pass, $settings->db_name );
	if( mysqli_connect_errno() === 0 ){
		$sqlstr = "set NAME utf8mb4";
		@mysqli_query( $dbh, $sqlstr );
	}else
		$dbh = false;
}else{
	$dbh = false;
}
try {
	$rec = new DBRecord(RESERVE_TBL, "id", $reserve_id );
	
	if( isset( $_POST['title'] ) ) {
		$rec->title = trim( $_POST['title'] );
		$rec->dirty = 1;
		if( ($dbh !== false) && ($rec->complete > 0) ) {
			$title = trim( mysqli_real_escape_string( $dbh, $_POST['title'] ) );
			$title .= "(".date("Y/m/d", toTimestamp($rec->starttime)).")";
			$sqlstr = "update mt_cds_object set dc_title='".$title."' where metadata regexp 'epgrec:id=".$reserve_id."$'";
			@mysqli_query( $sqlstr );
		}
	}
	
	if( isset( $_POST['description'] ) ) {
		$rec->description = trim( $_POST['description'] );
		$rec->dirty = 1;
		if( ($dbh !== false) && ($rec->complete > 0) ) {
			$desc = "dc:description=".trim( mysqli_real_escape_string( $dbh, $_POST['description'] ) );
			$desc .= "&epgrec:id=".$reserve_id;
			$sqlstr = "update mt_cds_object set metadata='".$desc."' where metadata regexp 'epgrec:id=".$reserve_id."$'";
			@mysqli_query( $dbh, $sqlstr );
		}
	}
}
catch( Exception $e ) {
	exit("Error: ". $e->getMessage());
}

exit("complete");

?>
