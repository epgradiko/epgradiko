<?php
$script_path = dirname( __FILE__ );
chdir( $script_path );
include_once( $script_path . '/../../config.php');
include_once( INSTALL_PATH . '/include/DBRecord.class.php' );
include_once( INSTALL_PATH . '/include/Settings.class.php' );
include_once( INSTALL_PATH . '/include/reclib.php' );

function ch_del( $type, $ch_disc ){
	global $GR_CHANNEL_MAP, $BS_CHANNEL_MAP, $CS_CHANNEL_MAP, $EX_CHANNEL_MAP, $settings;
	$return_str ='';
	try {
		// xx_channel.phpの編集
		switch( $type ){
			case 'GR':
				$map = $GR_CHANNEL_MAP;
				break;
			case 'BS':
				$map = $BS_CHANNEL_MAP;
				break;
			case 'CS':
				$map = $CS_CHANNEL_MAP;
				break;
			case 'EX':
				$map = $EX_CHANNEL_MAP;
				break;
			default:
				$return_str = 'Error: typeパラメータが不正です。(ch_del.'.$type.')';
		}
		if( !$return_str ){
			$f_nm      = INSTALL_PATH.'/settings/channels/'.strtolower($type).'_channel.php';
			$st_ch     = file( $f_nm, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
			$key_point = array_search( $ch_disc, array_keys( $map ) );
			if( $key_point !== FALSE ){
				array_splice( $st_ch, $key_point+3, 1 );
				$fp = fopen( $f_nm, 'w' );
				foreach( $st_ch as $ch_str )
					fwrite( $fp, $ch_str."\n" );
				fclose( $fp );
			}

			try {
				$reserve_array = array();
				$reserve_array = DBRecord::createRecords( RESERVE_TBL, "WHERE channel_disc = '".$ch_disc."'" );
				$ret = 0;
				foreach( $reserve_array as $reserve ){
					$ret =+ (int)Reservation::cancel( $reserve->id );
				}
				if( $ret ) $return_str = 'Error: 予約削除失敗あり (ch_del.'.$ret.')';
			}catch( Exception $e ) {
				$return_str = 'Error: 予約情報削除失敗 (ch_del.'.$reserve->id.')';
			}
		}
	}catch( Exception $e ) {
		exit( 'Error: チャンネル削除失敗 (ch_del.'.$del_id.')' );
	}
	return $return_str;
}

function ch_skip( $type, $ch_disc , $skip ){
	$return_str ='';
	try {
		// xx_channel.phpの編集
		switch( $type ){
			case 'GR':
			case 'BS':
			case 'CS':
			case 'EX':
				break;
			default:
				$return_str = 'Error: typeパラメータが不正です。(ch_skip.'.$type.')';
		}
		if( !$return_str ){
			$channel = new DBRecord( CHANNEL_TBL, 'channel_disc', $ch_disc );
			if( !$channel ) $return_str = 'Error: チャンネルテーブルに登録されていません。(ch_skip)';
			else{
				$channel->skip = $skip;
				$channel->update();
                        }
                }
	}catch( Exception $e ){
		$return_str = 'Error: チャンネル更新失敗 (ch_skip.'.$ch_disc.','.$e.')';
	}
	return $return_str;
}

function ch_NC( $type, $ch_disc, $NC ){
	global $GR_CHANNEL_MAP, $BS_CHANNEL_MAP, $CS_CHANNEL_MAP, $EX_CHANNEL_MAP, $settings;
	$return_str ='';
	try {
		// xx_channel.phpの編集
		switch( $type ){
			case 'GR':
				$map = $GR_CHANNEL_MAP;
				break;
			case 'BS':
				$map = $BS_CHANNEL_MAP;
				break;
			case 'CS':
				$map = $CS_CHANNEL_MAP;
				break;
			case 'EX':
				$map = $EX_CHANNEL_MAP;
				break;
			default:
				$return_str = 'Error: typeパラメータが不正です。(ch_NC.'.$type.')';
		}
		if( !$return_str ){
			$f_nm      = INSTALL_PATH.'/settings/channels/'.strtolower($type).'_channel.php';
			$st_ch     = file( $f_nm, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
			$key_point = array_search( $ch_disc, array_keys( $map ) );
			$channel = new DBRecord( CHANNEL_TBL, 'channel_disc', $ch_disc );
			if( $key_point !== FALSE ){
				$write_array = array();
				$array_point = array();
				$array_before = array_slice( $st_ch, 0, $key_point+3, TRUE );
				if( $NC ){
					if( !$channel ){
						$explode_text = explode('_', $ch_disc);
						$sid = $explode_text[1];
						$array_point[] = "\t\"".$ch_disc."\" =>\t\"NC\",\t// ".$map[$ch_disc]."\t".$sid.",\t// NO NAME";
					}else	$array_point[] = "\t\"".$ch_disc."\" =>\t\"NC\",\t// ".$channel->channel."\t".$channel->sid.",\t// ".$channel->name;
				}else{
					$array_point[] = "\t\"".$ch_disc."\" =>\t\"".$channel->channel."\",\t// ".$channel->channel."\t".$channel->sid.",\t// ".$channel->name;
				}
				$array_after = array_slice( $st_ch, $key_point+4, null, TRUE );
				$write_array = array_merge( $array_before, $array_point, $array_after );
				try {
					$fp = fopen( $f_nm, 'w' );
					foreach( $write_array as $ch_str )
						fwrite( $fp, (string)$ch_str."\n" );
					fclose( $fp );
				}catch( Exception $e ) {
					$fp = fopen( $f_nm, 'w' );
					foreach( $st_ch as $ch_str )
						fwrite( $fp, (string)$ch_str."\n" );
					fclose( $fp );
					$return_str = 'Error: チャンネル設定データ更新失敗(ch_NC.'.$ch_disc.','.$e.')';
				}
			}
		}
	}catch( Exception $e ){
		$return_str = 'Error: チャンネル設定データ失敗 (ch_NC.'.$ch_disc.','.$e.')';
	}
	return $return_str;
}

function ch_reorder( $type, $ch_order ){
	global $GR_CHANNEL_MAP, $BS_CHANNEL_MAP, $CS_CHANNEL_MAP, $EX_CHANNEL_MAP, $IPTV_CHANNEL_MAP, $SELECTED_CHANNEL_MAP, $settings;
	$return_str ='';
	$not_physical = FALSE;
	try {
		// xx_channel.phpの編集
		switch( $type ){
			case 'GR':
				$map = $GR_CHANNEL_MAP;
				break;
			case 'BS':
				$map = $BS_CHANNEL_MAP;
				break;
			case 'CS':
				$map = $CS_CHANNEL_MAP;
				break;
			case 'EX':
				$map = $EX_CHANNEL_MAP;
				break;
			case 'IPTV':
				$map = $IPTV_CHANNEL_MAP;
				$not_physical = TRUE;
				break;
			case 'SELECTED':
				$map = $SELECTED_CHANNEL_MAP;
				$not_physical = TRUE;
				break;
			default:
				$return_str = 'Error: typeパラメータが不正です。(ch_reorder '.$type.')';
		}
		if( !$return_str ){
			$f_nm      = INSTALL_PATH.'/settings/channels/'.strtolower($type).'_channel.php';
			$st_ch     = file( $f_nm, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
			$order     = explode(',' , $ch_order);
			if( !$not_physical && count($map) !== count($order) ) $resturn_str = 'Error: orderパラメータが不正です。(ch_reorder '.$ch_order.')';
			else{
				$write_array = array();
				$array_before = array_slice( $st_ch, 0, 3, TRUE );
				$array_after = array_slice( $st_ch, -2, null, TRUE );
				$array_point = array();
				foreach( $order as $ch_disc ){
					if( $ch_disc == 'END' ) continue;
					$channel = DBRecord::createRecords( CHANNEL_TBL, 'WHERE channel_disc=\''.$ch_disc.'\'' );
					if( $not_physical ){
						if( $channel ) $array_point[] = "\t\"".$ch_disc."\",\t// ".$channel[0]->name;
					}else{
						if( !$channel ){
							$explode_text = explode('_', $ch_disc);
							$sid = $explode_text[1];
							$array_point[] = "\t\"".$ch_disc."\" =>\t\"NC\",\t// ".$map[$ch_disc]."\t".$sid.",\t// NO NAME";
						}else if( $map[$ch_disc] == 'NC' ){
							$array_point[] = "\t\"".$ch_disc."\" =>\t\"NC\",\t// ".$map[$ch_disc]."\t".$channel[0]->sid.",\t// ".$channel[0]->name;
						}else{
							$array_point[] = "\t\"".$ch_disc."\" =>\t\"".$map[$ch_disc]."\",\t// ".$map[$ch_disc]."\t".$channel[0]->sid.",\t// ".$channel[0]->name;
						}
					}
				}
				if( !$return_str ){
					$write_array = array_merge( $array_before, $array_point, $array_after );
					try {
						$fp = fopen( $f_nm, 'w' );
						foreach( $write_array as $ch_str )
							fwrite( $fp, (string)$ch_str."\n" );
						fclose( $fp );
					}catch( Exception $e ) {
						$fp = fopen( $f_nm, 'w' );
						foreach( $st_ch as $ch_str )
							fwrite( $fp, (string)$ch_str."\n" );
						fclose( $fp );
						$return_str = 'Error: チャンネル設定データ更新失敗(ch_reorder.'.$ch_disc.','.$e.')';
					}
				}
			}
		}
	}catch( Exception $e ){
		$return_str = 'Error: チャンネル設定データ失敗 (ch_reorder.'.$ch_disc.','.$e.')';
	}
	return $return_str;
}

$exit_str = '';
if( isset($_POST['cmd']) ){
	switch( $_POST['cmd'] ){
		case	'delete':
			if( !isset($_POST['type']) ) 		$exit_str = 'Error: パラメータ:typeなし';
			else if( !isset($_POST['ch_disc']) ) 	$exit_str = 'Error: パラメータ:ch_discなし';
			else $exit_str = ch_del( $_POST['type'], $_POST['ch_disc'] );
			break;
		case	'skip':
			if( !isset($_POST['type']) ) 		$exit_str = 'Error: パラメータ:typeなし';
			else if( !isset($_POST['ch_disc']) ) 	$exit_str = 'Error: パラメータ:ch_discなし';
			else if( !isset($_POST['skip']) )	$exit_str = 'Error: パラメータ:skipなし';
			else $exit_str = ch_skip( $_POST['type'], $_POST['ch_disc'], $_POST['skip'] );
			break;
		case	'NC':
			if( !isset($_POST['type']) ) 		$exit_str = 'Error: パラメータ:typeなし';
			else if( !isset($_POST['ch_disc']) ) 	$exit_str = 'Error: パラメータ:ch_discなし';
			else if( !isset($_POST['NC']) )		$exit_str = 'Error: パラメータ:NCなし';
			else $exit_str = ch_NC( $_POST['type'], $_POST['ch_disc'], $_POST['NC'] );
			break;
		case	'reorder':
			if( !isset($_POST['type']) ) 		$exit_str = 'Error: パラメータ:typeなし';
			else if( !isset($_POST['order']) ) 	$exit_str = 'Error: パラメータ:orderなし';
			else $exit_str = ch_reorder( $_POST['type'], $_POST['order'] );
			if( !$exit_str ) $exit_str = '保存しました';
			break;
		default:
			$exit_str = 'Error: 未定義コマンド('.$_POST['cmd'].')';
	}
} else $exit_str = 'Error: コマンドパラメータなし';
exit($exit_str);
?>
