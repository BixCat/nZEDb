<?php
namespace nzedb;

use nzedb\utility\Misc;

/**
 * Class Radarr
 *
 * API For Radarr
 *
 * @package nzedb
 */
class Radarr
{
    /**
	 * Construct.
	 * Set up full URL.
	 *
	 * @var \BasePage $page
	 *
	 * @access public
	 */
	public function __construct(&$page)
	{
		$this->serverurl = $page->serverurl;
		$this->uid       = $page->userdata['id'];
		$this->rsstoken  = $page->userdata['rsstoken'];

		if (!empty($page->userdata['nzbgeturl'])) {
            $this->url      = $page->userdata['radarr_url'];
            $this->api      = $page->userdata['radarr_api'];
            $this->rootfolderpath = $page->userdata['radarr_rootfolderpath'];
            $this->qprofile      = $page->userdata['radarr_qprofile'];

		}

		$this->Releases = new Releases(['Settings' => $page->settings]);

    }

    public function AddMovie($tmdbid,$imdbid,$title,$year){

        $url=$this->url.'/api/Movie/?apikey='.$this->api;

        $path =  $this->rootfolderpath.'/'.$title . ' ('.$year.')';
        $titleSlug=$title.'-'.$tmdbid;

        $data = array(
            'title'      =>  $title,
            'qualityProfileId'    => $this->qprofile ,
            'tmdbId'      => $tmdbid,
            'imdbId'      => sprintf('tt%07d',$imdbid),
            'titleslug'     => $titleSlug,
            'path' => $path,
            'monitored' => 'true',
            'downloaded' => 'false',
            'year' =>  $year,
            'addoptions' => array('searchForMovie' => 'true'),
            'images' => array()
        );


        $ch = curl_init($url);
        curl_setopt_array($ch, array(
            CURLOPT_POST => TRUE,
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_HEADER => FALSE,
            CURLOPT_POSTFIELDS => json_encode($data)
        ));

        // Send the request
        $response = curl_exec($ch);

        // file_put_contents('/tmp/AddMovie_out.txt', print_r($response, true));
        curl_close($ch);

    }

    public function DeleteIfExistsByTMDB($tmdbid){
        $movie = $this->GetMovieByTMDB($tmdbid);
        if ( !isset($movie))
            return;

       $url=$this->url .'/api/Movie/'.$movie->id.'?apikey='.$this->api;
        // file_put_contents('/tmp/DeleteIfExistsByIMDB_url.txt', $url."\n");

        $data = array(
            'id'      =>  $movie->id
        );

        $ch = curl_init($url);

        curl_setopt_array($ch, array(
            CURLOPT_CUSTOMREQUEST => "DELETE",
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_HEADER => FALSE
        ));
        $response = curl_exec($ch);
        curl_close($ch);
        // file_put_contents('/tmp/DeleteIfExistsByIMDB_out.txt', print_r($response, true));
    }

    public function GetMovieByTMDB($tmdbid){
        $url=$this->url .'/api/Movie/?apikey='.$this->api;
       # file_put_contents('/tmp/GetMovieByIMDB_cmd.txt', $url);

        $json = file_get_contents($url);
        $obj = json_decode($json);
        $tmdbid= intval(preg_replace("/[^0-9,.]/", "", $tmdbid));

        // file_put_contents('/tmp/GetMovieByIMDB_out.txt', print_r($obj , true));
        // file_put_contents('/tmp/GetMovieByIMDB_imdb.txt', $tmdbid."\n");
        foreach($obj as $movie)
        {
            if ( $movie->tmdbId === $tmdbid)
            {
                // file_put_contents('/tmp/GetMovieByIMDB_YES.txt',$movie->id."\n");// print_r($movie , true));
                return $movie;
            }
        }
        return;
       # file_put_contents('/tmp/GetMovieByIMDB_out.txt', print_r($obj , true));

    }

    public function LookupMovieByIMDB($imdbid){
        $imdbid= intval(preg_replace("/[^0-9,.]/", "", $imdbid));
        $imdbid=sprintf('tt%07d',$imdbid);

        $url=$this->url .'/api/movies/lookup?apikey='.$this->api.'&term=imdb:'.$imdbid;
       # file_put_contents('/tmp/LookupMovieByIMDB_url.txt', $url);

        $json = file_get_contents($url);
        $obj = json_decode($json);
        if ( isset($obj) && isset($obj[0])){
            #file_put_contents('/tmp/LookupMovieByIMDB_yes.txt', print_r($obj[0] , true));
            if (  isset($obj[0]->tmdbId)){
                file_put_contents('/tmp/LookupMovieByIMDB_yes.txt', $obj[0]->tmdbId."\n");
                return $obj[0];
            }
        }

        #file_put_contents('/tmp/LookupMovieByIMDB_out.txt', print_r($obj , true));
        return;

    }

}