<?php

use app\models\Settings;
use nzedb\Releases;
use nzedb\Movie;
use nzedb\Radarr;
use nzedb\db\DB;
use nzedb\utility\Misc;

$uid = 0;
// Page is accessible only by the rss token, or logged in users.
if ($page->users->isLoggedIn()) {
	$uid = $page->users->currentUserId();
	$maxDownloads = $page->userdata["downloadrequests"];
	$rssToken = $page->userdata['rsstoken'];
	if ($page->users->isDisabled($page->userdata['username'])) {
		Misc::showApiError(101);
	}
} else {
	if (Settings::value('..registerstatus') == Settings::REGISTER_STATUS_API_ONLY) {
		$res = $page->users->getById(0);
	} else {
		if ((!isset($_GET["i"]) || !isset($_GET["r"]))) {
			Misc::showApiError(200);
		}

		$res = $page->users->getByIdAndRssToken($_GET["i"], $_GET["r"]);
		if (!$res) {
			Misc::showApiError(100);
		}
	}
	$uid = $res["id"];
	$rssToken = $res['rsstoken'];
	$maxDownloads = $res["downloadrequests"];
	if ($page->users->isDisabled($res['username'])) {
		Misc::showApiError(101);
	}
}

if (empty($page->userdata['radarr_url']) || empty($page->userdata['radarr_api'])){
	Misc::showApiError(400, 'radarr_url and radarr_api is required');
}

// Check download limit on user role.
$requests = $page->users->getDownloadRequests($uid);
if ($requests > $maxDownloads) {
	Misc::showApiError(429);
}

if (!isset($_GET['id'])) {
	Misc::showApiError(400, 'parameter id is required');
}

// Remove any suffixed id with .nzb which is added to help weblogging programs see nzb traffic.
$_GET['id'] = str_ireplace('.nzb', '', $_GET['id']);

$mov = new Movie();
$guids = explode(",", $_GET["id"]);
foreach( $guids as $guid){
    $rel = new Releases(['Settings' => $page->settings]);
    $relData = $rel->getByGuid($_GET["id"]);
    if ($relData && isset($relData['imdbid'])  ) {
        $year=0;

        $imdbid=$relData['imdbid'];

        $radarr= new Radarr($page);

        if ( !isset($relData['tmdbid'])){
             $lookupMov=$radarr->LookupMovieByIMDB($imdbid);

             if ( isset($lookupMov) && isset($lookupMov->tmdbId)){
                $tmdbid=$lookupMov->tmdbId;
                 file_put_contents('/tmp/GetMovieByIMDB_lookupMov.txt', print_r($lookupMov,true));
                 file_put_contents('/tmp/GetMovieByIMDB_tmdbid.txt', $tmdbid);
             }
        }else{
            $tmdbid=$relData['tmdbid'];
        }


        $title=$relData['searchname'];

            $movie=$mov->getMovieInfo($imdbid);
            if ( isset($movie) && isset($movie['title'])){
                $title=$movie['title'];
                $year=$movie['year'];
        }

        $radarr->DeleteIfExistsByTMDB($tmdbid);

        $radarr->AddMovie($tmdbid,$imdbid,$title,$year);

        $rel->updateGrab($_GET["id"]);
         $page->users->addDownloadRequest($uid);
         $page->users->incrementGrabs($uid);
         if (isset($_GET["del"]) && $_GET["del"] == 1) {
         	$page->users->delCartByUserAndRelease($_GET["id"], $uid);
         }
    } else {
        Misc::showApiError(300, 'Release not found!');
    }
}
// Start reading output buffer.
ob_start();
ob_end_flush();
file_put_contents('/tmp/GetMovieByIMDB_done.txt','jdshjd');


