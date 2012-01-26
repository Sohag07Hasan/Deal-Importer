<?php 
/*
 * plugin name: TRDM CSV deal Importer
 * author: Mahibul Hasan
 * description: It imprort different types of deal data as csv format.
 * version: 2.0.1
 * plugin uri: http://voltierdigital.com/ove/
 * author uri: http://sohag.me
 * 
 * */

define('TRDM_CSV_FILE', __FILE__);
define('TRDM_CSV_DIR', dirname(__FILE__));
define('TRDM_CSV_CLASS', TRDM_CSV_DIR . '/classes');


include TRDM_CSV_CLASS . '/options.php';
include TRDM_CSV_CLASS . '/DataSource.php';
include TRDM_CSV_CLASS . '/csv_importer.php';

?>
