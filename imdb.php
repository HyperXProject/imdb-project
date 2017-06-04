<?php
/*
--------------------------------------
* @since 1.0
* Last update: 6/02/2017
--------------------------------------
* Skype: doothemes
* Email: doothemes@gmail.com
*/
class IMDb {	

	// Escanear titulo en los Buscadores..
	public function getMovieInfo($title, $getExtraInfo = true)
	{
		$imdbId = $this->getIMDbIdFromSearch(trim($title));
		if($imdbId === NULL){
			$arr = array();
			$arr['error'] = "No se encontro titulo en el resultado de busqueda!";
			return $arr;
		}
		return $this->getMovieInfoById($imdbId, $getExtraInfo);
	}
	
	// Obtener ID de IMDb
	public function getMovieInfoById($imdbId, $getExtraInfo = true)
	{
		$arr = array();
		$imdbUrl = "http://www.imdb.com/title/" . trim($imdbId) . "/";
		return $this->scrapeMovieInfo($imdbUrl, $getExtraInfo);
	}
	
	// Scrapear contenido de IMDb 
	private function scrapeMovieInfo($imdbUrl, $getExtraInfo = true)
	{
		$arr = array();
		$html = $this->geturl("${imdbUrl}combined");
		$title_id = $this->match('/<link rel="canonical" href="http:\/\/www.imdb.com\/title\/(tt\d+)\/combined" \/>/ms', $html, 1);
		if(empty($title_id) || !preg_match("/tt\d+/i", $title_id)) {
			$arr['error'] = "No Title found on IMDb!";
			return $arr;
		}
		$arr['imdbID']=$title_id;
		$arr['Title']=str_replace('"', '', trim($this->match('/<title>(IMDb \- )*(.*?) \(.*?<\/title>/ms', $html, 2)));
		$arr['imdbRating']=$this->match('/<b>(\d.\d)\/10<\/b>/ms', $html, 1);
		$arr['imdbVotes']=$this->match('/>([0-9,]*) votes</ms', $html, 1);
		$arr['Rated']=$this->match('/MPAA<\/a>:<\/h5><div class="info-content">Rated (G|PG|PG-13|PG-14|R|NC-17|X) /ms', $html, 1);
		$arr['Runtime']=trim($this->match('/Runtime:<\/h5><div class="info-content">.*?(\d+) min.*?<\/div>/ms', $html, 1));
		$arr['Genre']=$this->match_all('/<a.*?>(.*?)<\/a>/ms', $this->match('/Genre.?:(.*?)(<\/div>|See more)/ms', $html, 1), 1);
		$arr['Year']=trim($this->match('/<title>.*?\(.*?(\d{4}).*?\).*?<\/title>/ms', $html, 1));
		$arr['Released']=$this->match('/Release Date:<\/h5>.*?<div class="info-content">.*?([0-9][0-9]? (January|February|March|April|May|June|July|August|September|October|November|December) (19|20)[0-9][0-9])/ms', $html, 1);
		$arr['Director']=$this->match_all_key_value('/<td valign="top"><a.*?href="\/name\/(.*?)\/">(.*?)<\/a>/ms', $this->match('/Directed by<\/a><\/h5>(.*?)<\/table>/ms', $html, 1));
		$arr['Writer']=$this->match_all_key_value('/<td valign="top"><a.*?href="\/name\/(.*?)\/">(.*?)<\/a>/ms', $this->match('/Writing credits<\/a><\/h5>(.*?)<\/table>/ms', $html, 1));
		$arr['Country']=$this->match_all('/<a.*?>(.*?)<\/a>/ms', $this->match('/Country:(.*?)(<\/div>|>.?and )/ms', $html, 1), 1);
		$arr['Language']=$this->match_all('/<a.*?>(.*?)<\/a>/ms', $this->match('/Language.?:(.*?)(<\/div>|>.?and )/ms', $html, 1), 1);
		$arr['Actors'] = array_slice($this->match_all_key_value('/<td class="nm"><a.*?href="\/name\/(.*?)\/".*?>(.*?)<\/a>/ms', $this->match('/<h3>Cast<\/h3>(.*?)<\/table>/ms', $html, 1)), 0, 5);
		return $arr;
	}
	/********************** + FUNCIONES + ************************/

	// Escanear en los buscadores el titulo
	private function getIMDbIdFromSearch($title, $engine = "google"){
		switch ($engine) {
			case "google":  $nextEngine = "bing";  break;
			case "bing":    $nextEngine = "ask";   break;
			case "ask":     $nextEngine = FALSE;   break;
			case FALSE:     return NULL;
			default:        return NULL;
		}
		$url = "http://www.${engine}.com/search?q=imdb+" . rawurlencode($title);
		$ids = $this->match_all('/<a.*?href="http:\/\/www.imdb.com\/title\/(tt\d+).*?".*?>.*?<\/a>/ms', $this->geturl($url), 1);
		if (!isset($ids[0]) || empty($ids[0])) 
			return $this->getIMDbIdFromSearch($title, $nextEngine); 
		else
			return $ids[0]; 
	}
	
	// Emular datos del Navegador
	private function geturl($url){
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
		$ip = rand(0,255).'.'.rand(0,255).'.'.rand(0,255).'.'.rand(0,255);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array("REMOTE_ADDR: $ip", "HTTP_X_FORWARDED_FOR: $ip"));
		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/".rand(3,5).".".rand(0,3)." (Windows NT ".rand(3,5).".".rand(0,2)."; rv:2.0.1) Gecko/20100101 Firefox/".rand(3,5).".0.1");
		$html = curl_exec($ch);
		curl_close($ch);
		return $html;
	}
	
	// Resultados con clave
	private function match_all_key_value( $regex, $str, $keyIndex = 1, $valueIndex = 2 ){
		$arr = array();
		preg_match_all( $regex, $str, $matches, PREG_SET_ORDER );
		foreach( $matches as $m ){
			$arr[$m[$keyIndex]] = $m[$valueIndex];
		}
		return $arr;
	}
	
	// Todos los resultados
	private function match_all( $regex, $str, $i = 0 ){
		if( preg_match_all( $regex, $str, $matches ) === false)
			return false;
		else
			return $matches[$i];
	}
	
	// Resultados individuales
	private function match( $regex, $str, $i = 0 ){
		if( preg_match( $regex, $str, $match) == 1 )
			return $match[$i];
		else
			return false;
	}

	// Fin de la Aplicacion
}

