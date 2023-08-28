<?php
function link_menu_create()
{
	global $settings,$SELECTED_CHANNEL_MAP;
	$program_menu = array();
	$timeshift_menu = array();
	if( isset($SELECTED_CHANNEL_MAP) )
		$program_menu[] = array( 'name' => '選別番組表　　　', 'url' => 'index.php?type=SELECT' );
	if( (int)$settings->gr_tuners > 0 )
		$program_menu[] = array( 'name' => '地デジ番組表　　', 'url' => 'index.php' );
	if( (int)$settings->bs_tuners > 0 ){
		$program_menu[] = array( 'name' => 'BS番組表　　　　', 'url' => 'index.php?type=BS' );
		if( (boolean)$settings->cs_rec_flg )
			$program_menu[] = array( 'name' => 'CS番組表　　　　', 'url' => 'index.php?type=CS' );
	}
	if( (int)$settings->ex_tuners > 0 )
		$program_menu[] = array( 'name' => 'ラジオ番組表　　', 'url' => 'index.php?type=EX' );
	$program_menu[] = array( 'name' => '録画予約番組表　', 'url' => 'revchartTable.php' );

	if( isset($settings->mirakc_timeshift) && $settings->mirakc_timeshift != 'none' || isset($settings->radiko_timeshift) && $settings->radiko_timeshift != 'none' ){
		$timeshift_menu[] = array( 'name' => 'Timeshift番組表', 'url' => 'timeshiftTable.php' );
		$timeshift_menu[] = array( 'name' => 'Timeshift検索　', 'url' => 'searchProgram.php?mode=timeshift' );
	}
	return array($program_menu, $timeshift_menu);
}
?>
