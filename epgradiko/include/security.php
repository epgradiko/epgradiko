<?php
// セキュリティ強化
function get_net_area( $src_ip )
{
	$check_addr = strtolower( $src_ip );
	if( strpos( $check_addr, ':' ) !== FALSE ){
		$check_addr = trim( $check_addr, '[]' );
		if( strpos( $check_addr, '.' ) !== FALSE ){		// IPv4射影アドレス/IPv4互換アドレス チェック
			$check_addr = str_replace( (!strncmp( $check_addr, '::ffff:', 7 ) ? '::ffff:' : '::'), '', $check_addr );
			$ipv4 = TRUE;
		}else
			$ipv4 = FALSE;
	}else
		$ipv4 = TRUE;
	if( $ipv4 ){
		// IPv4
		$adrs = explode( '.', $check_addr );
		if( count( $adrs ) !== 4 )
			return 'T';
		foreach( $adrs as $adr ){
			if( !is_numeric($adr) )
				return 'T';
		}
		if( $check_addr === '127.0.0.1' ){
			return 'H';			// local host(loop back)
		}else
		if( strncmp( $check_addr, '192.168.', 8 ) === 0 ){
			return 'C';			// class C
		}else
		if( strncmp($check_addr, '10.', 3 ) === 0 ){
			return 'A';			// class A
		}else{
			if( $adrs[0]==='172' && ((int)$adrs[1]&0xf0)==0x10 )
				return 'B';			// class B
			else
				return 'G';			// global
		}
	}else{
		// IPv6
		if( $check_addr === '::1' ){
			return 'H';			// local host(loop back)
		}else{
			$adrs = explode( ':', $check_addr );
			if( count( $adrs ) === 1 )
				return 'T';
			foreach( $adrs as $adr ){
				if( $adr!=='' && filter_var( '0x'.$adr, FILTER_VALIDATE_INT, FILTER_FLAG_ALLOW_HEX )===FALSE )
					return 'T';
			}
			$ip6_top = hexdec( $adrs[0] );
			if( ($ip6_top&0xFE00)===0xFC00 || ($ip6_top&0xFFC0)===0xFE80 )
				return 'P';			// private(ユニークローカルユニキャストアドレス/リンクローカルユニキャストアドレス)
			else{
				return 'G';			// global
			}
		}
	}
}

// チャンネルMAPファイルを操作された場合(削除･不正コード挿入など)を想定
// epgrecUNA以外からの操作が可能なため対応
function check_ch_map( $ch_file, $gr_safe=FALSE )
{
	$inc_file = INSTALL_PATH.'/settings/channels/'.$ch_file;
	if( file_exists( $inc_file ) ){
		if( filesize( $inc_file ) > 0 ){
			$rd_data       = file_get_contents( $inc_file );
			list( $type, ) = explode( '_', $ch_file );
			$search        = '$'.strtoupper( $type ).'_CHANNEL_MAP';
			if( strpos( $rd_data, $search )!==FALSE && strpos( $rd_data, ");\n?>" )!==FALSE ){
				if( substr_count( $rd_data, ';' ) == 1 ){
					return TRUE;
				}
			}
		}
	}
	if( $gr_safe )
		return FALSE;
	else{
		include_once( INSTALL_PATH . '/include/DBRecord.class.php' );
		include_once( INSTALL_PATH . '/include/recLog.inc.php' );
		reclog( $inc_file.' が壊れているか不正コードが挿入されている可能性があります。ファイルを確認してください。', EPGREC_ERROR );
		exit;
	}
}
?>
