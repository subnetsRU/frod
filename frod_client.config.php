<?php
/*
    Project: FROD.SUBNETS.RU

    (c) 2014 SUBNETS.RU project (Moscow, Russia)
    Authors: Nikolaev Dmitry <virus@subnets.ru>, Panfilov Alexey <lehis@subnets.ru>
*/

//////////////////////////////// CONFIG ///////////////////////////////////////////////////////////////
define( 'API_URL','http://frod.subnets.ru/api/import.php' );

/*
    AUTH
    
    Replace:
	- REPLACE_WITH_YOUR_IMPORT_API_ID
	- REPLACE_WITH_YOUR_IMPORT_API_PASSWORD
    with your API ID and password
    You can see them after logon -> http://frod.subnets.ru/wrapper.php?sci=5
*/
define( 'API_UID', 'REPLACE_WITH_YOUR_IMPORT_API_ID' );
define( 'API_PASSWORD', 'REPLACE_WITH_YOUR_IMPORT_API_PASSWORD' );

/*
    OPTIONS
*/
define( 'API_METHOD', 'XML');				//Variants: XML, JSON, GET, POST
define( 'DEBUG','0' );	//0 - debug is OFF
			//1 - debug is ON: print debug to log
			//2 - debug is ON: print debug to log and output
define( 'LOGFILE',"/full/path/to/frod_client.log");		//Create file, don`t forget about permissions to write to it
////////////////////////////////////////////////////////////////////////////////////////////////////////

?>