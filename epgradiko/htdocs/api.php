<?php
$script_path = dirname( __FILE__ );
chdir( $script_path );
include_once( $script_path . '/../config.php');
include_once( INSTALL_PATH . '/include/DBRecord.class.php' );
include_once( INSTALL_PATH . '/include/Reservation.class.php' );
include_once( INSTALL_PATH . '/include/Settings.class.php' );
include_once( INSTALL_PATH . '/include/recLog.inc.php' );
include_once( INSTALL_PATH . '/include/reclib.php' );
include_once( INSTALL_PATH . '/include/Keyword.class.php' );
include_once( INSTALL_PATH . '/include/reclib.php' );
include_once( INSTALL_PATH . '/include/epg_const.php' );

$settings = Settings::factory();

function not_found() {
	global $path, $paths;
	header($_SERVER["SERVER_PROTOCOL"] . " 404 Not Found");
	http_response_code(404);
	echo "<!DOCTYPE html>\n";
	echo "<html lang=\"en\">\n";
	echo "<head>\n";
	echo "<meta charset=\"UTF-8\">\n";
	echo "<title>Error</title>\n";
	echo "</head>\n";
	echo "<body>\n";
	echo "<pre>Cannot GET /api/".$path."</pre>\n";
	echo "</body>\n";
	echo "</html>\n";
}

function get_recorded() {
	global $path, $paths, $RECORD_MODE;
	try {
		if( !isset($_GET['isHalfWidth']) ) {
			return [
				'content_type' => 'err',
				'content' => [
					'status' => 400,
					'errors' => [
						["message" => "must have required property 'isHalfWidth'"]
					]
				]
			];
		}
		$isHalftWidth = (boolean)$_GET['isHalfWidth'];
		$offset = isset($_GET['offset']) ? (int)$_GET['offset']:0;
		$limit = isset($_GET['limit']) ? (int)$_GET['limit']:(int)24;
		$isReverse = isset($_GET['isReverse']) ? (boolean)$_GET['isReverse']:'';
		$ruleId = isset($_GET['ruleId']) ? (int)$_GET['ruleId']:'';
		$channelId = isset($_GET['channelId']) ? (int)$_GET['channelId']:'';
		$genre = isset($_GET['genre']) ? (int)$_GET['genre']:'';
		$keyword = isset($_GET['keyword']) ? (string)$_GET['keyword']:'';
		$hasOriginalFile = isset($_GET['hasOriginalFile']) ? (boolean)$_GET['hasOriginalFile']:'';

		$limit_str='';

		if( $limit ) {
			$limit_str = ' LIMIT '.$limit;
			if( $offset ) {
				$limit_str .= ' OFFSET '.$offset;
			}
		}
		$order_str = $isReverse ? ' ORDER BY id':' ORDER BY id DESC';
		$where_str = 'WHERE complete=1';
		if( $ruleId !== '' ) {
			$where_str .= ' AND autorec='.$ruleId;
		}
		if( $channelId ) {
			$channels = DBRecord::createRecords(CHANNEL_TBL, 'WHERE network_id='.(int)$channelId/100000 .' AND tsid='.(int)$channelId%100000);
			$where_str .= ' AND channel_id='.$channels[0]['id'];
		}
		if( $keyword ) {
			$where_str .= ' AND CONCAT(title, " ", description) LIKE '."'%".$keyword."%'";
		}
		$reserves = DBRecord::createRecords(RESERVE_TBL, $where_str.$order_str.$limit_str);
		$records = array();
		$rec = array();
		foreach($reserves as $reserve) {
			$rec['id'] = (int)$reserve->id;
			$channel = new DBRecord( CHANNEL_TBL, 'id', $reserve->channel_id );
			if( DBRecord::countRecords( TRANSCODE_TBL, 'WHERE rec_id='.$reserve->id) ) {
				$trans_set = new DBRecord( TRANSCODE_TBL, 'rec_id', $reserve->id );
				$explode_text =  explode( '/', $trans_set->path );
				$filename = htmlspecialchars( end( $explode_text ) );
				$rec_mode = $trans_set->name;
			} else {
				$filename = htmlspecialchars( $reserve->path );
				$rec_mode = $RECORD_MODE[$reserve->mode]['name'];
			}
			$rec['channelId'] = (int)sprintf('%d%05d', $channel->network_id, $channel->tsid);
			$rec['startAt'] = strtotime($reserve->starttime)*1000;
			$rec['endAt'] = strtotime($reserve->endtime)*1000;
			$rec['name'] = $reserve->title;
			$rec['isRecording'] = FALSE;
			$rec['isEncoding'] = FALSE;
			$rec['isProtected'] = FALSE;
			$rec['ruleId'] = (int)$reserve->autorec;
//			$rec['programId'] = $reserve->eid ? $rec['channelId'] * 100000 + $reserve->eid: 0;
			$rec['programId'] = 0;
			$rec['description'] = $reserve->description;
			$rec['extended'] = '';
			$rec['genre'] = (int)$reserve->category_id;
			$rec['subGenre'] = (int)$reserve->sub_genre;
			$rec['videoType'] = 'mpeg2'; // oops!
			$rec['videoResolution'] = '1080i'; // oops!
			$rec['videoStreamContent'] = 1; // oops!
			$rec['videoComponentType'] = 179;
			$rec['audioSamplingRate'] = 48000; // oops!
			$rec['audioComponentType'] = 3; // oops!
			$rec['thumbnails'] = [ (int)$reserve->id ];
			$rec['videoFiles'] = [
						[
						'id' => (int)$reserve->id,
						'name' => $rec_mode,
						'filename' => $filename,
						'type' => 'encoded',
						'size' => 1000,
						],
			];
			$rec['dropLogFile'] = [
						'id' => (int)$reserve->id,
						'errorCnt' => 0, // oops!
						'dropCnt' => 0, //oops!
						'scramblingCnt' => 0, //oops!
			];
			array_push( $records, $rec );
		}
	} catch( Exception $e ) {
		return ['content_type' => 'err',
			'content' => [ 
				'status' => $e->getCode(),
				'message' => $e->getMessage()
			]
		];
	}
	return ['content_type' => 'json',
		'content' => [
			'records' => $records,
			'total' => (int)count($reserves),
		]
	];
}

function get_recorded_description( $recordedId ) {
	global $path, $paths, $RECORD_MODE;
	try {
		if( !isset($_GET['isHalfWidth']) ) {
			return ['content_type' => 'err',
				'content' => [
					'status' => 400,
					'errors' => [
						["message" => "must have required property 'isHalfWidth'"]
					]
				]
			];
		}
		$isHalftWidth = (boolean)$_GET['isHalfWidth'];
		$reserves = DBRecord::createRecords(RESERVE_TBL, 'where complete=1 and id='.$recordedId);
		if( count($reserves) ) {
			$reserve = $reserves[0];
			$rec['id'] = (int)$reserve->id;
			$channel = new DBRecord( CHANNEL_TBL, 'id', $reserve->channel_id );
			$rec['channelId'] = (int)sprintf('%d%05d', $channel->network_id, $channel->tsid);
			$rec['startAt'] = strtotime($reserve->starttime)*1000;
			$rec['endAt'] = strtotime($reserve->endtime)*1000;
			$rec['name'] = $reserve->title;
			$rec['isRecording'] = FALSE;
			$rec['isEncoding'] = FALSE;
			$rec['isProtected'] = FALSE;
			$rec['ruleId'] = (int)$reserve->autorec;
//			$rec['programId'] = $reserve->eid ? $rec['channelId'] * 100000 + $reserve->eid: 0;
			$rec['programId'] = 0;
			$rec['description'] = $reserve->description;
			$rec['extended'] = '';
			$rec['rawExtended'] = [];
			$rec['genre1'] = (int)$reserve->category_id;
			$rec['subGenre1'] = (int)$reserve->sub_genre;
			$rec['videoType'] = 'mpeg2'; // oops!
			$rec['videoResolution'] = '1080i'; // oops!
			$rec['videoStreamContent'] = 1; // oops!
			$rec['videoComponentType'] = 179;
			$rec['audioSamplingRate'] = 48000; // oops!
			$rec['audioComponentType'] = 3; // oops!
			$rec['thumbnails'] = [ (int)$reserve->id ];
			$rec['videoFiles'] = [
						[
						'id' => (int)$reserve->id,
						'name' => 'X264-HD', // oops!
						'filename' => $reserve->path,
						'type' => 'encoded',
						'size' => 1000,
						],
			];
			$rec['dropLogFile'] = [
						'id' => (int)$reserve->id,
						'errorCnt' => 0, // oops!
						'dropCnt' => 0, //oops!
						'scramblingCnt' => 0, //oops!
			];
			$rec['tags'] = [];
		} else {
			return ['content_type' => 'err',
				'content' => [
					'status' => 404,
					'message' => 'recorded is not Found'
				]
		       	];
	}

	} catch( Exception $e ) {
		return ['content_type' => 'err',
			'content' => [
				'status' => $e->getCode(),
				'message' => $e->getMessage()
			]
		];
	}
	return ['content_type' => 'json',
		'content' => $rec
	];
}

function get_recording() { // oops!
	global $path, $paths;
	return ['content_type' => 'json',
		'content' => [
			'records' => [],
			'total' => 0
		]
	];
}

function get_rules( $ruleId = '' ) {
	global $path, $paths, $RECORD_MODE;
	try {
		$offset = isset($_GET['offset']) ? (int)$_GET['offset']:0;
		$limit = isset($_GET['limit']) ? (int)$_GET['limit']:(int)24;
		$type = isset($_GET['type']) ? $_GET['type']:'all';
		if( $type !== 'all' && $type !== 'normal' && $type !== 'conflict' && $type !== 'skip' && $type !== 'overlap' ) {
			return ['content_type' => 'err',
				'content' => [
					'status' => 400,
					'errors' => [
						['message' => 'type must have property "all|normal|coflict|skip|overlap"']
					]
				]
			];
		}
		$keyword = isset($_GET['keyword']) ? (string)$_GET['keyword']:'';

		$limit_str='';

		if( $limit ) {
			$limit_str = ' LIMIT '.$limit;
			if( $offset ) {
				$limit_str .= ' OFFSET '.$offset;
			}
		}
		$order_str = ' ORDER BY id';
		$where_str = 'WHERE 1=1';
		if( $keyword ) {
			$where_str .= ' AND CONCAT(name, " ", keyword) LIKE '.$keyword;
		}
		$keywords = DBRecord::createRecords(KEYWORD_TBL, $where_str.$order_str.$limit_str);
		$records = array();
		$rec = array();
		foreach($keywords as $kw) {
			if( $kw->channel_id ) {
				$channel = new DBRecord( CHANNEL_TBL, 'id', $kw->channel_id );
				$network_id = $channel->network_id;
				$tsid = $channel->tsid;
			} else {
				$network_id = 0;
				$tsid = 0;
			}
			$reserve_cnt = count(DBRecord::createRecords(RESERVE_TBL, 'where complete=0 and autorec='.$kw->id));
			$rec['id'] = (int)$kw->id;
			$rec['isTimeSpecification'] = (boolean) ($kw->overlap === 1);
			$rec['searchOption'] = [
				'keyCS' => (boolean)$kw->collate_ci,
				'keyRegExp' => (boolean)$kw->use_regexp,
				'name' => (boolean)$kw->ena_title,
				'description' => (boolean)$kw->ena_desc,
				'extended' => (boolean)FALSE,
				'ignoreKeyCS' => (boolean)$kw->collate_ci_ex,
				'ignoreKeyRegExp' => (boolean)$kw->use_regexp_ex,
				'ignoreName' => (boolean)$kw->ena_title_ex,
				'ignoreDescription' => (boolean)$kw->ena_desc_ex,
				'ignoreExtended' => (boolean)FALSE,
				'GR' => (boolean) ($kw->typeGR !== 0),
				'BS' => (boolean) ($kw->typeBS !== 0),
				'CS' => (boolean) ($kw->typeCS !== 0),
				'SKY' => (boolean) ($kw->typeEX !== 0),
				'isFree' => (boolean)$kw->free,
				'keyword' => $kw->name,
				'ignoreKeyword' => $kw->keyword_ex,
				'times' => [
						[
							'week' => $kw->weekofdays,
						],
				],
			];
			if( $kw->channel_id ) {
				$rec['searchOption'] = $rec['serachOption'] +
							array('channelIds' => [ (int)sprintf('%d%05d', $network_id, $tsid) ] );
			}
			$rec['reserveOption'] = [
				'enable' => (boolean)$kw->kw_enable,
				'allowEndLack' => false,	//??
				'avoidDuplicate' => false,	//??
			];
			$rec['saveOption'] = [
				'parentDirectoryName' => 'video',
				'directory' => '',
			];
			$rec['encodeOption'] = [
				'mode1' => $RECORD_MODE[$kw->autorec_mode]['name'],
				'encodeParentDirectoryName1' => 'video',
				'directory1' => '',
				'isDeleteOriginalAfterEncode' => TRUE,	// oops!
			];
			if( $ruleId ) $rec['reservesCnt'] = $reserve_cnt;
			array_push( $records, $rec );
		}
	} catch( Exception $e ) {
		return ['content_type' => 'err',
			'content' => [
				'status' => $e->getCode(),
				'message' => $e->getMessage()
			]
		];
	}
	return ['content_type' => 'json',
		'content' => [
			'rules' => $records,
			'total' => (int)count($keywords),
		]
	];
}

function get_thumbnails_id( $thumbnailId ) {
	global $path, $paths, $settings;
	try {
		$reserve = DBRecord::createRecords(RESERVE_TBL, 'where id='.$thumbnailId);
		if( !count($reserve) ) {
			return ['content_type' => 'err',
				'content' => [
					'status' => 404,
					'message' => 'recorded is not Found'
	 			]
	 		];
		} else {
			$thumbs = INSTALL_PATH.$settings->thumbs.'/'.$reserve[0]->id.'.jpg';
			if( !file_exists($thumbs) ) {
				return ['content_type' => 'err',
					'content' => [
						'status' => 404,
						'message' => 'recorded is not Found'
					]
		       		];
			}
		}
	} catch( Exception $e ) {
		return ['content_type' => 'err',
			'content' => [
				'status' => $e->getCode(),
				'message' => $e->getMessage()
			]
		];
	}
	return ['content_type' => 'jpg',
		'content' => [
			'file' => $thumbs,
		]
	];
}

function get_videos( $videoFileId ) {
	global $path, $paths, $settings;
	try {
		$isDownload = isset($_GET['isDownload']) ? (boolean)$_GET['isDownload']:FALSE;
		$reserve = DBRecord::createRecords(RESERVE_TBL, 'where id='.$videoFileId);
		if( !count($reserve) ) {
			return ['content_type' => 'err',
				'content' => [
					'status' => 404,
					'message' => 'recorded is not Found'
	 			]
	 		];
		} else {
			if( DBRecord::countRecords( TRANSCODE_TBL, 'WHERE rec_id='.$reserve[0]->id) ) {
				$trans_set = new DBRecord( TRANSCODE_TBL, 'rec_id', $reserve[0]->id );
				$explode_text =  explode( '/', $trans_set->path );
				$filename = htmlspecialchars( end( $explode_text ) );
				$content = 'video';
				$content_sub = 'mp4';
			} else {
				$filename = htmlspecialchars( $reserve[0]->path );
				$content = 'video';
				$content_sub = 'mp2t';
			}
			$video_file = INSTALL_PATH.$settings->spool.'/'.$filename;
			if( !file_exists($video_file) ) {
				return ['content_type' => 'err',
					'content' => [
						'status' => 404,
						'message' => 'recorded is not Found'
					]
		       		];
			}
			if( $isDownload ) {
				$content = 'application';
				$content_sub = 'octet-stream';
			}
		}
	} catch( Exception $e ) {
		return ['content_type' => 'err',
			'content' => [
				'status' => $e->getCode(),
				'message' => $e->getMessage()
			]
		];
	}
	return ['content_type' => $content,
		'content_sub' => $content_sub,
		'content' => [
			'file' => $video_file,
		]
	];
}

function delete_recorded( $recordedId ) {
	global $path, $paths, $settings;
	try {
		$reserve = DBRecord::createRecords(RESERVE_TBL, 'where id='.$recordedId);
		if( !count($reserve) ) {
			return ['content_type' => 'err',
				'content' => [
					'status' => 404,
					'message' => 'recorded is not Found'
	 			]
	 		];
		} else {
			$explode_text = explode( '/', $reserve[0]->path );
			$trans_obj = new DBRecord( TRANSCODE_TBL );
			$del_trans = $trans_obj->fetch_array( null, null, 'rec_id='.$reserve[0]->id.' ORDER BY status' );
			foreach( $del_trans as $del_file ){
				switch( $del_file['status'] ){
				case 1:	// 処理中(0は処理済)
					killtree( (int)$del_file['pid'] );
					sleep(1);
					break;
				case 2:	// 正常終了
				case 3:	// 異常終了
					if( file_exists( $del_file['path'] ) )
						@unlink( $del_file['path'] );
					break;
				}
				$trans_obj->force_delete( $del_file['id'] );
			}
			// ファイルを削除
			$reced = INSTALL_PATH.$settings->spool.'/'.$reserve[0]->path;
			if( file_exists( $reced ) )
				@unlink( $reced );
			try {
				$ret_code = Reservation::cancel( $reserve[0]->id );
			} catch( Exception $e ) {
				// 無視
			}
		}
	} catch( Exception $e ) {
		return ['content_type' => 'err',
			'content' => [
				'status' => $e->getCode(),
				'message' => $e->getMessage()
			]
		];
	}
	return ['content_type' => 'json',
		'content' => [
			'code' => 200,
			'description' => '録画を削除しました'
		]
	];
}

function get_rules_keyword() {
		return ['content_type' => 'err',
			'content' => [
				'status' => 400,
				'message' => 'not supoorted yet'
			]
		];
}

function delete_recorded_encode() {
		return ['content_type' => 'err',
			'content' => [
				'status' => 400,
				'message' => 'not supoorted yet'
			]
		];
}

global $path, $paths;
$path = substr($_SERVER['REDIRECT_URL'], 5);
$paths = explode('/', $path);

switch (strtolower($_SERVER['REQUEST_METHOD']).':'.$paths[0]) {
case 'get:recorded':
	if( isset($paths[1]) && $paths[1] ) {
		if( isset($paths[2]) && $paths[2] ) {
			not_found();
			exit;
		} else {
			if ( htmlspecialchars($paths[1]) == 'options') {
				$responce = get_recorded_options();
			} else {
				$responce = get_recorded_description( htmlspecialchars($paths[1]) );
			}
		}
	} else {
		$responce = get_recorded();
	}
	break;
case 'delete:recorded':
	if( isset($paths[1]) && $paths[1] ) {
		if( isset($paths[2]) && $paths[2] ) {
			if( htmlspecialchars($paths[2]) == 'encode' ) {
				$responce = delete_recorded_encode( htmlspecialchars($paths[1]) );
			} else {
				not_found();
				exit;
			}
		} else {
			$responce = delete_recorded((int)htmlspecialchars($paths[1]));
		}
	} else {
		not_found();
		exit;
	}
	break;

case 'get:recording':
	if( isset($paths[1]) && $paths[1] ) {
		not_found();
		exit;
	} else {
		$responce = get_recording();
	}
	break;
			
case 'get:rules':
	if( isset($paths[1]) && $paths[1] ) {
		if( isset($paths[2]) && $paths[2] ) {
			not_found();
			exit;
		} else {
			if ( htmlspecialchars($paths[1]) == 'keyword') {
				$responce = get_rules_keyword();
			} else {
				$responce = get_rules( htmlspecialchars($paths[1]) );
			}
		}
	} else {
		$responce = get_rules();
	}
	break;

case 'get:version':
	if( isset($paths[1]) && $paths[1] ) {
		not_found();
		exit;
	} else {
		$responce = ['content_type' => 'json',
			'content' => [
				'version' => '2.6.11'
			]
		];
	}
	break;

case 'get:thumbnails':
	if( isset($paths[1]) && $paths[1] ) {
		if( isset($paths[2]) && $paths[2] ) {
			not_found();
			exit;
		} else {
			$responce = get_thumbnails_id((int)htmlspecialchars($paths[1]));
		}
	} else {
		not_found();
		exit;
	}
	break;

case 'get:videos':
	if( isset($paths[1]) && $paths[1] ) {
		if( isset($paths[2]) && $paths[2] ) {
			not_found();
			exit;
		} else {
			$responce = get_videos((int)htmlspecialchars($paths[1]));
		}
	} else {
		not_found();
		exit;
	}
	break;

default:
	not_found();
	exit;

}
switch( $responce['content_type'] ) {
case 'json':
	header('Content-Type: application/json; charset=utf-8');
	echo json_encode($responce['content'], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
	break;
case 'err':
	header('Content-Type: application/json; charset=utf-8');
	switch( $responce['content']['status'] ) {
		case 400:
		case 404:
			http_response_code($responce['content']['status']);
			break;
		default:
			http_response_code(500);
			break;
	}

	echo json_encode($responce['content'], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
	break;

case 'jpg':
	header('Content-Type: image/jpeg');
	readfile($responce['content']['file']);
	break;

case 'video':
case 'application':
	header('Content-Type: '.$responce['content_type'].'/'.$responce['content_sub']);
	header('content-length: '.filesize($responce['content']['file']));
	$fp = @fopen( $responce['content']['file'], 'r' );
	if( $fp !== false ) {
		do {
			if( feof( $fp ) ) break;
			echo fread( $fp, 12032 );
			@usleep( 2000 );
		}while( connection_aborted() == 0 );
		fclose($fp);
	}
	break;

defaulft:
	break;

}
	
?>
