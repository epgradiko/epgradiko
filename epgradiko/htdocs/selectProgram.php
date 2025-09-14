<?php
$script_path = dirname( __FILE__ );
chdir( $script_path );
include_once( $script_path . '/../config.php');

if( isset($_COOKIE['program_url']) ) $url = $_COOKIE['program_url'];
else $url = 'index.php';
if( isset($_COOKIE['program_type']) ) $type = $_COOKIE['program_type'];
else $type = 'GR';
exit( "<script type=\"text/javascript\">\n" .
	"<!--\n".
	"window.open(\"/".$url."?type=".$type."\",\"_self\");".
	"// -->\n</script>" );

?>
