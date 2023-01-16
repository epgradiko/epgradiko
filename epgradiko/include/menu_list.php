<?php
function link_menu_create( $mode = 'none' )
{
	global $settings,$NET_AREA,$SELECTED_CHANNEL_MAP;
	// メニュー一覧
	$MENU_LIST = array(
		array(
			'name' => '予約一覧　　　　　　　　',
			'url'  => '/reservationTable.php',
		),
		array(
			'name' => '予約遷移表　　　　　　　',
			'url'  => '/revchartTable.php',
		),
		array(
			'name' => '録画済一覧　　　　　　　',
			'url'  => '/recordedTable.php',
		),
		array(
			'name' => '番組検索　　　　　　　　',
			'url'  => '/programTable.php',
		),
		array(
			'name' => 'キーワード管理　　　　　',
			'url'  => '/keywordTable.php',
		),
		array(
			'name' => '動作ログ　　　　　　　　',
			'url'  => '/logViewer.php',
		),
		array(
			'name' => 'メンテナンス　　　　　　',
			'url'  => '/maintenance.php',
		),
	);

	if( $mode !== 'INDEX' ){
		$link_add = array();
		if( (int)$settings->gr_tuners > 0 )
			$link_add[] = array( 'name' => '地デジ番組表　　　　　　', 'url' => 'index.php' );
		if( (int)$settings->bs_tuners > 0 ){
			$link_add[] = array( 'name' => 'BS番組表　　　　　　　　', 'url' => 'index.php?type=BS' );
			if( (boolean)$settings->cs_rec_flg )
				$link_add[] = array( 'name' => 'CS番組表　　　　　　　　', 'url' => 'index.php?type=CS' );
		}
		if( (int)$settings->ex_tuners > 0 )
			$link_add[] = array( 'name' => 'ラジオ番組表　　　　　　', 'url' => 'index.php?type=EX' );
		if( isset($SELECTED_CHANNEL_MAP) )
			$link_add[] = array( 'name' => '選別番組表　　　　　　　', 'url' => 'index.php?type=SELECT' );
		$MENU_LIST = array_merge( $link_add, $MENU_LIST );
	}

	if( $settings->mirakc_timeshift !== 'none' || $settings->radiko_timeshift !== 'none' ){
		$link_add = array();
		$link_add[] = array( 'name' => 'Timeshift　　　　　　　', 'url' => 'timeshiftTable.php');
		$MENU_LIST = array_merge( $link_add, $MENU_LIST );
	}

	return $MENU_LIST;
}

?>
