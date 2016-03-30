<?php
/*
    Project: FROD.SUBNETS.RU

    PHP client for import data

    Versions: 
	0.2.0 (from 25.12.2014): Add exaple for run from asterisk dialplan (or server CLI)
	0.1.1 (from 09.12.2014): Add JSON
	0.1.0 (from 08.12.2014): First version

    (c) 2014 SUBNETS.RU project (Moscow, Russia)
    Authors: Nikolaev Dmitry <virus@subnets.ru>, Panfilov Alexey <lehis@subnets.ru>
*/

define( 'API_CLIENT_VER','0.2.0' );

error_reporting(E_ALL);

$path = realpath( dirname(__FILE__) );
$path_config=$path."/frod_client.config.php";

if (is_file($path_config)){
    if (!@include $path_config){
	printf("[ERROR]: Config file %s not included\n",$path_config);
	exit;
    }
}else{
    printf("[ERROR]: Config file %s not found\n",$path_config);
    exit;
}
api_check();

//////////////////////////////////// YOUR DATA GOES HERE //////////////////////////////////////////////////////////////
$nn=0;
$import_data=array();

		/////////////////////////// CLI or DIALPLAN EXAMPLE ////////////////////////////////
/*
    How to run:
	CLI: /full/path/to/php /full/path/to/frod_client.php TELNUMBER IP-ADDRESS
	    example:
		[root@virus ~]# /usr/local/bin/php /home/virus/frod_client.php 810442820788034 1.1.1.1

	DIALPLAN: exten => YOUREXTEN,n,Set(api=${SHELL(/full/path/to/php /full/path/to/frod_client.php ${EXTEN} ${CHANNEL(recvip)})})
*/

//Form data to send
$params_in=$argv;
if (is_array($params_in)){
    array_shift($params_in);
    if (count($params_in) > 0){
	api_logg("++++++++++++ got params +++++++++++++++");
	foreach ($params_in as $k=>$v) {
	    api_logg(" -- $k = $v");
	}
	if (isset($params_in[0])){
	    api_logg(sprintf("Form data: %s %s",$params_in[0],$params_in[1]));
	    if (!preg_match("/^\d{10,}$/",$params_in[0])){
		api_logg("Params not valid. TELNUMBER must contain only numbers and be more than 10 characters");
		exit(0);
	    }
	    $import_data[$nn]['number']=$params_in[0];

	    if (isset($params_in[1])){
		if (!preg_match("/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/",$params_in[1])){
		    api_logg("Params not valid. IP-ADDRESS must be IPv4 IP-address");
		    exit(0);
		}
	    }
	    $import_data[$nn]['ip']=$params_in[1];
	    $nn++;
	    api_logg($import_data);
	}else{
	    api_logg(sprintf("Params not valid. %s need 2 params: TELNUMBER and IP-ADDRESS",$argv[0]));
	}
    }else{
	api_logg("No params");
    }
}else{
    api_logg("Params not valid");
}
		////////////////////////////////////////////////////////////////////////////

		/////////////////////////// STATIC EXAMPLE ////////////////////////////////
//Form data to send
//$nn=0;
//$import_data=array();
/*$import_data[$nn]['number']="810442820788034";
$import_data[$nn]['ip']="1.1.1.1";
$nn++;
$import_data[$nn]['number']="810441112233444";
$nn++;
*/
		////////////////////////////////////////////////////////////////////////////

api_logg("Form data:");
api_logg($import_data);


/////// Sending data
foreach ($import_data as $key=>$val){
    if (isset($val['ip'])){
	$request=api_request(
	    array(
		"action"		=>	"insert",
		"object"		=>	"number",
		"actionId"		=>	api_actionId(),
		"authMethod"	=>	"md5",
		"uid"		=>	API_UID,
		"number"		=>	(isset($val['number']) && $val['number']) ? $val['number']: "",
		"ip"		=>	(isset($val['ip']) && $val['ip']) ? $val['ip'] : "",
	    )
	);
    }else{
	$request=api_request(
	    array(
		"action"		=>	"insert",
		"object"		=>	"number",
		"actionId"		=>	api_actionId(),
		"authMethod"	=>	"md5",
		"uid"		=>	API_UID,
		"number"		=>	(isset($val['number']) && $val['number']) ? $val['number']: "",
	    )
	);
    }

    if ($request[0]==1){
	//Request is successfull
	print "Inserted";
	api_logg("Inserted");
    }else{
	//Request return error
	printf("ERROR: %s",$request[1]);
	api_logg(sprintf("ERROR: %s",$request[1]));
    }
}
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////


//////////////////////////////////////// FUNCTIONS ////////////////////////////////////////////////////////////////////
function api_request($data){
    if (is_array($data)){
	$tmp=api_send_request($data);
	return $tmp;
    }else{
	return array(0=>0,1=>sprintf("%s",error("No data for request")));
    }
}

function api_send_request($data){

	$ret=array();
	//Request result: ret[0]=0 - unsuccess; ret[0]=1 - success
	//Request data: ret[1]="text" - if unsuccess; $ret[1]= data - success

	$ret[0]=0;	//Set default
	$ret[1]="";	//Set default
	$err=array();
	
	if ( !defined( 'API_UID' ) ){
	    $err[]="API UID not set";
	}
	if ( !defined( 'API_PASSWORD' ) ){
	    $err[]="API password not set";
	}
	if ( !defined('API_METHOD') ){
	    define( 'API_METHOD', 'GET');
	}
	if (API_METHOD == "GET" || API_METHOD == "POST"){
	    $tmp=api_request2data($data);
	}elseif(API_METHOD == "JSON"){
	    $tmp=api_request2json($data);
	}elseif(API_METHOD == "XML"){
	    $tmp=api_request2xml($data);
	}else{
	    $err[]="API METHOD unknown";
	}

	if ( !$tmp ){
	    $err[]="DATA generation error";
	}

	if ( !defined( 'API_URL' ) ){
	    define( 'API_URL','http://frod.subnets.ru/api/import.php' );
	    //$err[]="API URL unknown";
	}

	api_logg("REQUEST: ".var_export($tmp,true));
	if (count($err)>0){
	    $ret[1]=implode(";",$err);
	    api_logg("ERROR: $ret[1]");
	    return $ret;
	}
	
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, API_URL);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_TIMEOUT, 30);
	if (API_METHOD == "XML"){
	    curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-length: ".strlen($tmp),'Content-Type: application/xml'));
	}elseif (API_METHOD == "JSON"){
	    curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-length: ".strlen($tmp),'Content-Type: application/json'));
	}else{
	    curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-length: ".strlen($tmp),'Content-Type: text/html'));
	}
	mb_internal_encoding('UTF-8');
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $tmp);
	curl_setopt($ch, CURLOPT_HEADER,0);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($ch, CURLOPT_USERAGENT, sprintf("FROD API CLIENT v%s",API_CLIENT_VER));
/*
	if ( DEBUG ){
	    curl_setopt( $ch, CURLOPT_STDERR, LOG );
	    curl_setopt( $ch, CURLOPT_VERBOSE, true );
	}
*/
	$strAnswer = curl_exec($ch);
	if ( curl_errno( $ch ) ){
		$ret[1]=sprintf("API connection error: %s",curl_error( $ch ));
		return $ret;
	}else{
		curl_close( $ch );
		unset( $res );
		if (API_METHOD == "XML"){
		    $res=api_unserialize_xml($strAnswer);
		}elseif (API_METHOD == "JSON"){
		    $res=json_decode($strAnswer,true);
		}else{
		    $ptmp=explode(";",$strAnswer);
		    if (count($ptmp)>0){
			foreach ($ptmp as $val){
			    if (preg_match("/^(\S+)=(\S+)/",$val,$mtmp)){
				$res[urldecode($mtmp[1])]=urldecode($mtmp[2]);
			    }
			}
		    }else{
			$res['error']="API reply was emply";
		    }
		}

		if (DEBUG ){
		    api_logg("REPLY: ".var_export($res,true));
		}

		if ( $res['error'] > 0 ){
		    $ret[1]=sprintf("Code: %s Description: %s",isset($res['error']) ? $res['error'] : "unknown" , isset($res['errorDescription']) ? $res['errorDescription'] : "" );
		}else{
		    $validate=api_validate_response($res);
		    if ($validate){
			unset( $res['authMethod'], $res['error'] , $res['errorDescription'], $res['sign'] );
			if ( isset($res['rows']) ){
			    if ( $res['rows'] == 1){
				if ( isset($res['data']['row']) ){
				    $tmp=$res['data']['row'];
				    unset( $res['data']['row'] );
				    $res['data']['row']=array();
				    $res['data']['row'][0]=$tmp;
				}
			    }
			}
			$ret[0]=1;
			$ret[1]=$res;
		    }else{
			$ret[1]="Code: 800 Description: API response not valid";
		    }
		    api_logg("FINAL:");
		    api_logg($ret);
		    return $ret;
		}
	}
 return $ret;
}

function api_request2data($data){
	$res="";
	ksort( $data );
	$sing_vals=array();
	$data_len=count($data);
	$nn=1;
	foreach($data as $key => $val){
		$sing_vals[] = $val;
		$res .= sprintf("%s=%s%s",$key,$val,$data_len!=$nn?"&":"");
		$nn++;
	}
	$sign=sprintf("%s;%s",implode(";",$sing_vals),API_PASSWORD);
	$res.=sprintf("&sign=%s",md5($sign));
 return $res;
}

function api_request2json($data){
	$res="";
	ksort( $data );
	$sing_vals=array();
	$data_len=count($data);
	$nn=1;
	foreach($data as $key => $val){
		$sing_vals[] = $val;
		$nn++;
	}
	$sign=sprintf("%s;%s",implode(";",$sing_vals),API_PASSWORD);
	$data['sign']=md5($sign);
	$res=json_encode($data);
 return $res;
}

function api_request2xml($data){
	if(array_key_exists('XML', $data)){
		$res = $data['XML'];
	}else{
		$res = '<?xml version="1.0" encoding="UTF-8"?><request>'; 
	}
	
	ksort( $data );
	$sing_vals=array();
	foreach($data as $key => $val){
		$sing_vals[] = $val;
		$res .=sprintf("<%s>%s</%s>",$key,$val,$key);
	}

	$sign=sprintf("%s;%s",implode(";",$sing_vals),API_PASSWORD);
	$res.=sprintf("<sign>%s</sign>",md5($sign));

	$res .= '</request>';
 return $res;
}

function api_validate_response($data){
    $ret=0;
    if ( is_array($data) ){
	if ( isset($data['authMethod']) && $data['authMethod']=='NULL' ){
	    $ret=1;
	}elseif ( isset($data['authMethod']) && $data['authMethod']=='md5' ){
	    if ( isset($data['sign']) ){
		$server_sign=$data['sign'];
		unset( $data['sign'] , $tmp);
		if ( isset($data['data']) ){
		    $tmp = $data['data'];
		    unset( $data['data'] );
		}
		ksort( $data );
		$for_sign = array( implode( ";", $data ) );
		if( isset( $tmp ) && is_array( $tmp ) && count( $tmp ) ){
		    $data_for_sign = '';
		    if( isset($tmp['row']) ){
			if ( $data['rows'] == 1 ){
			    $rows[0]=$tmp['row'];
			}else{
			    $rows=$tmp['row'];
			}
			foreach( $rows as $k => $v ){
				ksort( $rows[$k] );
				$data_for_sign .= sprintf( "%s%s", $data_for_sign ? ";" : "", implode( ";", $rows[$k] ) );
			}
			if( $data_for_sign ){
			    $for_sign[] = $data_for_sign;
			}
		    }
		}else{
			unset( $tmp );
		}
		$for_sign[] = API_PASSWORD;
		$my_sign = md5( implode( ";", $for_sign ) );
		if( $server_sign === $my_sign ){
		    $ret=1;
		}
	    }
	}
    }
 return $ret;
}

function api_unserialize_xml($input, $callback = null, $recurse = false){
        if ((!$recurse) && is_string($input)){
	    $pre_data=preg_replace('/&/', '&amp;', $input);
    	    if( ( $result = @simplexml_load_string($pre_data) ) === false ){
    		$ret=array();
    		$ret['error'] = 800;
		$ret['errorDescription'] = 'CLIENT: Error during parse of XML';
		return $ret;
    	    }
        }else{
    	    $result=$input;
        }
        if ($result instanceof SimpleXMLElement){ 
	    if (count((array)$result)>0){
    		$result = (array) $result;
    	    }
    	}
        if (is_array($result)) foreach ($result as &$item) $item = api_unserialize_xml($item, $callback, true);
 return (!is_array($result) && is_callable($callback))? call_user_func($callback, $result): $result;
}

function api_actionId(){
    $mtime=explode(".",microtime(true));
    return sprintf("%s%02d",date("His",time()),isset($mtime[1])?$mtime[1]:"0");
}

function deb($text){
        if (is_array($text)){
		if ($_SERVER['REMOTE_ADDR']){
            	    print "<pre>";
            	}
            	print "[DEBUG]\n";
                foreach ($text as $k=>$v){
                        if (is_array($v)){
                                printf("<b>[%s] => array</b>\n",$k);
                                print_r($v);
                        }else{
                                printf("[%s] => %s\n",$k,$v);
                        }
                }
                if ($_SERVER['REMOTE_ADDR']){
            	    print "</pre>";
                }
        }else{
                printf ("[DEBUG] %s%s\n",api_replace_html($text),$_SERVER['REMOTE_ADDR']?"<BR>":"");
        }
}

function api_replace_html($text){
    $text=preg_replace("/\</","&lt;",$text);
    $text=preg_replace("/\>/","&gt;",$text);
 return $text;
}

function api_logg( $text ){
    if( DEBUG ){
	if( is_resource( LOG ) ){
	    $debug_string=sprintf( "[%s]: %s\n", date( "d.m.Y H:i:s", time( ) ), is_array($text) ? print_r($text,true) : $text );
	    fputs( LOG, $debug_string );
	}
	if ( DEBUG > 1 ){
	    print $debug_string;
	}
    }
}

function api_check(){
    if ( DEBUG ){
	if (is_file(LOGFILE)){
	    if (is_writable(LOGFILE)){
		define( 'LOG', fopen( LOGFILE, 'a+' ) );	//log append
		//define( 'LOG', fopen( LOGFILE, 'w+' ) );	//log replacement
	    }else{
		printf("Logfile %s not writable\n",LOGFILE);
		exit;
	    }
	}else{
	    printf("Logfile %s not found\n",LOGFILE);
	    exit;
	}
    }

    if ( !function_exists('curl_exec') ){
	print "API: <a href=\"http://www.php.net/manual/ru/book.curl.php\">CURL</a> not found... exit";
	exit;
    }

    if ( !function_exists('mb_internal_encoding') ){
	print "API: <a href=\"http://php.net/manual/ru/book.mbstring.php\">Multibyte String</a> not found... exit";
	exit;
    }

    if (API_METHOD == "XML"){
	if ( !function_exists('simplexml_load_string') ){
	    print "API: <a href=\"http://php.net/manual/ru/function.simplexml-load-string.php\">XML</a> not found... exit";
	    exit;
	}
    }
}
?>