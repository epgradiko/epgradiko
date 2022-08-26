<?php
include_once('../../config.php');
include_once( INSTALL_PATH . '/include/DBRecord.class.php' );
include_once( INSTALL_PATH . '/Smarty/Smarty.class.php' );
include_once( INSTALL_PATH . '/include/reclib.php' );
include_once( INSTALL_PATH . '/include/Settings.class.php' );

$settings = Settings::factory();

	// ストレージ空き容量取得
	$ts_stream_rate = TS_STREAM_RATE;
	$spool_path = INSTALL_PATH.$settings->spool;
	$files = scandir( $spool_path );
	if( DATA_UNIT_RADIX_BINARY ){
		$unit_radix = 1024;
		$byte_unit  = 'iB';
	}else{
		$unit_radix = 1000;
		$byte_unit  = 'B';
	}
	if( $files !== FALSE ){
		// 全ストレージ空き容量仮取得
		$root_mega = $free_mega = (int)( disk_free_space( $spool_path ) / ( $unit_radix * $unit_radix ) );
		// スプール･ルート･ストレージの空き容量保存
		$stat  = stat( $spool_path );
		$dvnum = (int)$stat['dev'];
		$spool_disks = array();
		$arr = array();
		$arr['dev']   = $dvnum;
		$arr['dname'] = get_device_name( $dvnum );
		$arr['path']  = $settings->spool;
		$usr_stat = posix_getpwuid( $stat['uid']);
		$own_chk  = $stat['uid']===posix_getuid() || $usr_stat['name']==='root';
		$arr['owner'] = $own_chk ? $usr_stat['name'] : '****';
		$grp_stat = posix_getgrgid( $stat['gid']);
		if( $own_chk !== FALSE && $grp_stat !== FALSE ) $arr['group'] = $grp_stat['name'];
		else $arr['group'] = '****';
		$arr['perm']  = sprintf("0%o", $stat['mode'] );
		$arr['wrtbl'] = ( $stat['uid']===posix_getuid() && ($stat['mode']&0300)===0300 ) || ( posix_getgid()===$stat['gid'] && ($stat['mode']&0030)===0030 ) || ($stat['mode']&0003)===0003 ? '1' :'0';
//		$arr['link']  = 'spool root';
		$arr['size']  = number_format( $root_mega/$unit_radix, 1 );
		$arr['time']  = rate_time( $root_mega );
		array_push( $spool_disks, $arr );
		$devs = array( $dvnum );

		// スプール･ルート上にある全ストレージの空き容量取得
		array_splice( $files, 0, 2 );
		foreach( $files as $entry ){
			$entry_path = $spool_path.'/'.$entry;
			if( is_link( $entry_path ) && is_dir( $entry_path ) ){
				$stat  = stat( $entry_path );
				$dvnum = (int)$stat['dev'];
				if( !in_array( $dvnum, $devs ) ){
					$entry_mega   = (int)( disk_free_space( $entry_path ) / ( $unit_radix * $unit_radix ) );
					$free_mega   += $entry_mega;
					$arr = array();
					$arr['dev']   = $dvnum;
					$arr['dname'] = get_device_name( $dvnum );
					$arr['path']  = $settings->spool.'/'.$entry;
					$usr_stat = posix_getpwuid( $stat['uid']);
					$own_chk  = $stat['uid']===posix_getuid() || $usr_stat['name']==='root';
					$arr['owner'] = $own_chk ? $usr_stat['name'] : '****';
					$grp_stat = posix_getgrgid( $stat['gid']);
					if( $own_chk !== FALSE && $grp_stat !== FALSE ) $arr['group'] = $grp_stat['name'];
					else $arr['group'] = '****';
					$arr['perm']  = sprintf("0%o", $stat['mode'] );
					$arr['wrtbl'] = ( $stat['uid']===posix_getuid() && ($stat['mode']&0300)===0300 ) || ( posix_getgid()===$stat['gid'] && ($stat['mode']&0030)===0030 ) || ($stat['mode']&0003)===0003 ? '1' :'0';
//					$arr['link']  = readlink( $entry_path );
					$arr['size']  = number_format( $entry_mega/$unit_radix, 1 );
					$arr['time']  = rate_time( $entry_mega );
					array_push( $spool_disks, $arr );
					array_push( $devs, array( $dvnum ) );
				}
			}
		}
	}else{
		// SPOOL不在
		$free_mega = 0;
		$spool_disks = array();
		$arr = array();
		$arr['dev']   = 0;
		$arr['dname'] = 'none';
		$arr['path']  = '---';
		$arr['owner'] = '----';
		$arr['grupe'] = '----';
		$arr['perm']  = '------';
		$arr['wrtbl'] = '0';
//		$arr['link']  = 'spool root';
		$arr['size']  = '----';
		$arr['time']  = '----';
		array_push( $spool_disks, $arr );
	}

	$smarty = new Smarty();
	$smarty->template_dir = INSTALL_PATH . "/templates/";
	$smarty->compile_dir = INSTALL_PATH . "/templates_c/";
	$smarty->cache_dir = INSTALL_PATH . "/cache/";

	$smarty->assign( 'free_size',   number_format( $free_mega/$unit_radix, 1 ) );
	$smarty->assign( 'free_time',   rate_time( $free_mega ) );
	$smarty->assign( 'ts_rate',     $ts_stream_rate );
	$smarty->assign( 'byte_unit',   $byte_unit );
	$smarty->assign( 'spool_disks', $spool_disks );
	$smarty->display('sub/diskUsage.html');
?>
