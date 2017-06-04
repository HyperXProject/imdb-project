<?php
/*
--------------------------------------
* @since 1.0
* Last update: 6/02/2017
--------------------------------------
* Skype: doothemes
* Email: doothemes@gmail.com
*/

// Headers
header('content-type: application/json; charset=utf-8');
header("access-control-allow-origin: *");

// Limitar el tiempo de espera
//set_time_limit(30000);

// Incluir Scraper
include("imdb.php");

$tt = $_REQUEST["i"];
$i = new IMDb();
$mArr = $i->getMovieInfo($tt);
echo json_encode($mArr);
