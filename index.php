<?php

/**
 * This script gets a video from YouTube by its ID
 * 
 * Created for SnapClip by SergeiSokolov.com
 */

require_once('ytdl.php');

$Y = new YTDL();


// collect info
$Y->setVideoId();
$n = $Y->getInfo();

// Generate html
$html = $Y->formHtml();
if ($n > 0) {
    $html .= $Y->linksHtml();
}
    
// Output the page
echo $Y->pageHtml($html);
