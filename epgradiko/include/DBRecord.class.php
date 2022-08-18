<?php
$script_path = dirname( __FILE__ );
chdir( $script_path );
include_once( $script_path . '/../config.php');
include_once( INSTALL_PATH . '/include/Settings.class.php' );

class DBRecord {
	protected $__table;

	protected $__record_data;
	protected $__f_dirty;

	public $__id;

	protected static $__dbh      = FALSE;
	protected static $__settings = FALSE;

    function __construct( $table, $property = null, $value = null, $row=null ){
		$this->__f_dirty     = FALSE;
		$this->__record_data = NULL;

		if( self::$__dbh === FALSE ){
			self::$__settings = Settings::factory();
			self::$__dbh      = @mysqli_connect( self::$__settings->db_host, self::$__settings->db_user, self::$__settings->db_pass, self::$__settings->db_name );
			if( mysqli_connect_errno() !== 0 ){
				self::$__dbh = FALSE;
				throw new exception( 'construct:データベースに接続できない' );
			}
			$sqlstr = 'set NAMES utf8mb4';
			$res = $this->__query($sqlstr);
		}
		$this->__table = self::$__settings->tbl_prefix.$table;

		if( $property===null || $value===null ){
			// レコードを特定する要素が指定されない場合はid=0として空のオブジェクトを作成する
			$this->__id = 0;
		}else
		if( $row === null ){
			$sqlstr              = 'SELECT * FROM '.$this->__table.' WHERE '.$property.'=\''.mysqli_real_escape_string( self::$__dbh, $value ).'\'';
			$res                 = $this->__query( $sqlstr );
			$this->__record_data = mysqli_fetch_array( $res, MYSQLI_ASSOC );
			if( $this->__record_data === NULL )
				throw new exception( 'construct:'.$this->__table.'に['.$property.'( '.$value.' )]はありません' );
			// 最初にヒットした行のidを使用する
			$this->__id = $this->__record_data['id'];
		}else{
			$this->__record_data = $row;
			$this->__id          = $this->__record_data['id'];
		}
		return;
	}

	function createTable( $tblstring ){
		$sqlstr = 'use '.self::$__settings->db_name;
		$res = $this->__query($sqlstr);
		if( $res === FALSE )
			throw new exception( 'createTable: ' . $sqlstr );
		$sqlstr = 'CREATE TABLE IF NOT EXISTS '.$this->__table.' ( ' .$tblstring.") ENGINE=InnoDB DEFAULT CHARACTER SET 'utf8mb4'";
		$result = $this->__query( $sqlstr );
		if( $result === FALSE )
			throw new exception( 'createTable:テーブル作成失敗' );
	}

	protected function __query( $sqlstr ){
		if( self::$__dbh === FALSE )
			throw new exception( '__query:DBに接続されていない' );
		$res = @mysqli_query( self::$__dbh, $sqlstr );
		if( $res === FALSE )
			throw new exception( '__query:DBクエリ失敗:'.$sqlstr );
		return $res;
	}

	function fetch_array( $property, $value, $options = null ){
		if( $property !== null ){
			$sqlstr = 'SELECT * FROM '.$this->__table.' WHERE '.$property.'=\''.mysqli_real_escape_string( self::$__dbh, $value ).'\'';
			if( $options !== null )
				$sqlstr .= 'AND '.$options;
		}else
			$sqlstr = 'SELECT * FROM '.$this->__table.' WHERE '.$options;

		$retval = array();
		$res    = $this->__query( $sqlstr );
		while( $row = mysqli_fetch_array( $res, MYSQLI_ASSOC ) ){
			array_push( $retval, $row );
		}
		return $retval;
	}

	function distinct( $property, $options = null ){
		$sqlstr = 'SELECT DISTINCT '.$property.' FROM '.$this->__table.' '.$options.' ORDER BY '.$property;
		$res    = $this->__query( $sqlstr );
		$retval = array();
		while( $row = mysqli_fetch_array( $res, MYSQLI_ASSOC ) ){
			$retval[] = $row[$property];
		}
		return $retval;
	}

	function __set( $property, $value ){
		if( $property === 'id' )
			throw new exception( 'set:idの変更は不可' );
		// id = 0なら空の新規レコード作成
		if( $this->__id == 0 ){
			$sqlstr     = 'INSERT INTO '.$this->__table.' VALUES ( )';
			$res        = $this->__query( $sqlstr );
			$this->__id = mysqli_insert_id(self::$__dbh);

			// $this->__record_data読み出し
			$sqlstr              = 'SELECT * FROM '.$this->__table.' WHERE id='.$this->__id;
			$res                 = $this->__query( $sqlstr );
			$this->__record_data = mysqli_fetch_array( $res, MYSQLI_ASSOC );
		}
		if( $this->__record_data === NULL )
			throw new exception( 'set: DBの異常？' );

		if( array_key_exists( $property, $this->__record_data ) ){
			$this->__record_data[$property] = $value;
			$this->__f_dirty = true;
		}else
			throw new exception( 'set:$property はありません' );
	}

	function __get( $property ){
		if( $this->__id == 0 )
			throw new exception( 'get:無効なid' );
		if( $property === 'id' )
			return $this->__id;
		if( $this->__record_data === NULL )
			throw new exception( 'get: 無効なレコード' );
		if( !array_key_exists( $property, $this->__record_data ) )
			throw new exception( 'get: $property['.$property.']は存在しません' );
		return $this->__record_data[$property];
	}

	function delete(){
		if( $this->__id == 0 )
			throw new exception( 'delete:無効なid' );
		$sqlstr = 'DELETE FROM '.$this->__table.' WHERE id='.$this->__id;
		$this->__query( $sqlstr );
		$this->__id          = 0;
		$this->__record_data = NULL;
		$this->__f_dirty     = FALSE;
	}

	function force_delete( $id ){
		$sqlstr = 'DELETE FROM '.$this->__table.' WHERE id='.$id;
		$this->__query( $sqlstr );
	}

	function update( $dest_flag = FALSE ){
		if( $this->__id != 0 ){ 
			if( $this->__f_dirty ){
				$sqlstr = 'UPDATE '.$this->__table.' SET';
				foreach( $this->__record_data as $property => $value ){
					if( $property === 'id' )
						continue;
					$sqlstr .= ' '.$property.'=\''.(is_bool($value) ? ($value ? '1' : '0') : mysqli_real_escape_string( self::$__dbh, $value )).'\',';
				}
				$sqlstr  = rtrim( $sqlstr, ',' );
				$sqlstr .= ' WHERE id='.$this->__id;
				$res     = $this->__query( $sqlstr );
				if( $res === FALSE ){
					if( !$dest_flag )		// 'デストラクタの中から (スクリプトの終了処理時に) 例外をスローしようとすると、致命的なエラーを引き起こします。'らしい
						throw new exception( 'close: アップデート失敗' );
				}
				$this->__f_dirty = FALSE;
			}
		}
	}

	// $update_setは$this->__record_dataと同じ連想配列だが書き込む要素のみで構成されている
	function force_update( $id, $update_set ){
		// id = 0なら空の新規レコード作成
		if( (int)$id == 0 ){
			$sqlstr = 'INSERT INTO '.$this->__table.' VALUES ( )';
			$this->__query( $sqlstr );
			$id = mysqli_insert_id(self::$__dbh);
		}
		$sqlstr = 'UPDATE '.$this->__table.' SET';
		foreach( $update_set as $property => $value ){
			$sqlstr .= ' '.$property.'=\''.(is_bool($value) ? ($value ? '1' : '0') : mysqli_real_escape_string( self::$__dbh, $value )).'\',';
		}
		$this->__query( rtrim( $sqlstr, ',' ).' WHERE id='.$id );
	}

	static function sql_escape( $str ){
		if( self::$__dbh === FALSE ){
			if( self::$__settings === FALSE )
				self::$__settings = Settings::factory();
			self::$__dbh = @mysqli_connect( self::$__settings->db_host, self::$__settings->db_user, self::$__settings->db_pass, self::$__settings->db_name );
			if( mysqli_connect_errno() !== 0 ){
				self::$__dbh = FALSE;
				throw new exception( 'construct:データベースに接続できない[HOST:'.
					self::$__settings->db_host.'][USER:'.self::$__settings->db_user.'][PW:'.self::$__settings->db_pass.'][DB:'.self::$__settings->db_name.']' );
			}
		}
		return mysqli_real_escape_string( self::$__dbh, $str );
	}

	// countを実行する
	static function countRecords( $table, $options = '' ){
		try{
			$tbl = new self( $table );
			$sqlstr = 'SELECT COUNT(*) FROM '.$tbl->__table.' '.$options;
			$result = $tbl->__query( $sqlstr );
		}
		catch( Exception $e ){
			throw $e;
		}
		if( $result === FALSE )
			throw new exception( 'COUNT失敗' );
		$retval = mysqli_fetch_row( $result );
		return (int)$retval[0];
	}

	// DBRecordオブジェクトを返すstaticなメソッド
	static function createRecords( $table, $options = '', $ret_false=TRUE ){
		$retval = array();
		$arr    = array();
		try{
			$tbl    = new self( $table );
			$sqlstr = 'SELECT * FROM '.$tbl->__table.' '.$options;
			$result = $tbl->__query( $sqlstr );
		}catch( Exception $e ){
			throw $e;
		}
		if( $result === FALSE ){
			if( !$ret_false )
				throw new exception( 'レコードが存在しません' );
		}else
			while( $row = mysqli_fetch_array($result, MYSQLI_ASSOC) ){
				array_push( $retval, new self( $table, 'id', $row['id'], $row ) );
			}
		return $retval;
	}

	// デストラクタ
	function __destruct(){
		// 呼び忘れに対応
		if( $this->__id != 0 ){
			$this->update(TRUE);
		}
		$this->__id          = 0;
		$this->__record_data = NULL;
	}
}
?>
