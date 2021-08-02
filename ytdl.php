<?php

/**
 * This webapp helps you download YouTube videos
 * Nothing new, but I wanted to research this stuff from scratch.
 * Sergei Sokolov, hello@sergeisokolov.com, for SnapClip. Trento, Italy, 2013.
 */

class YTDL {
    const FIELD_NAME = 'video';
    const KEY_NAME = 'player_response';

    public $videoId; // YouTube video ID
    public $videoData = array(); // general video infos
    public $links = array(); // array of var quality links


    function setVideoId($id = '') {
        if (empty($id)) {
            $in = filter_input(INPUT_GET, self::FIELD_NAME) ?: filter_input(INPUT_POST, self::FIELD_NAME);
            // http://www.youtube.com/watch?v=27Ce--_qzFM&xxxxx
            // youtu.be/27Ce--_qzFM
            // 27Ce--_qzFM
            if (empty($in)) return false;
            
            if (preg_match( '#watch\?v=([^&]+)#', $in, $match)) {
                $id = $match[1];
            } elseif (preg_match('#youtu.be/(.+)#', $in, $match)) {
                $id = $match[1];
            } else {
                $id = $in;
            }
        }

        $this->videoId = $id;
    }
    
    private function parseVideoData($infoLine) {
        $videoData = array();
        parse_str( $infoLine, $videoData);
        if (0 === count($videoData)) {
            echo "Empty data. Html follows:";
            echo $infoLine;
            return;
        } else {
            // all OK
            // print_r($videoData);
        }

        $this->videoData = $videoData;

        /* 
            Keys of $this->videoData:
            
            csn, root_ve_type, vss_host, cr, host_language, hl, gapi_hint_params, 
            innertube_api_key, innertube_api_version, innertube_context_client_version, 
            watermark, c, cver, 
            player_response, 
            enablecsi, csi_page_type, use_miniplayer_ui, ps, fexp, fflags, status
        */
    }

    
    private function parseFormats() {
        if (! isset($this->videoData[self::KEY_NAME])) {
            echo "Error: missing " . self::KEY_NAME;
            return null;
        }
        
        $params = json_decode(urldecode($this->videoData[self::KEY_NAME]), true);

        // video links
        $formats = $params['streamingData']['formats'] ?? [];
        $adaptiveFormats = $params['streamingData']['adaptiveFormats'] ?? [];
        $this->links = array_merge($formats, $adaptiveFormats);

        // video info
        $this->videoDetails = $params['videoDetails'] ?? [];

        /*
            "url": "https://r1---sn-ab5l6nsy.googlevideo.com/videoplayback?expire=1617231413\u0026ei=1alkYOqxLYjfgwPc763QBg\u0026ip=134.209.162.109\u0026id=o-AIyG3PTAO5fhJ7mJbku4InY_0wFW2Jpjwj4-RIXQird_\u0026itag=18\u0026source=youtube\u0026requiressl=yes\u0026mh=oI\u0026mm=31,29\u0026mn=sn-ab5l6nsy,sn-ab5szn7e\u0026ms=au,rdu\u0026mv=m\u0026mvi=1\u0026pl=23\u0026initcwndbps=262500\u0026vprv=1\u0026mime=video/mp4\u0026ns=lASXP01ycvrkAM0rUm6Q4UsF\u0026gir=yes\u0026clen=1964769\u0026ratebypass=yes\u0026dur=43.096\u0026lmt=1616954619793082\u0026mt=1617209327\u0026fvip=1\u0026fexp=24001373,24007246\u0026c=WEB\u0026txp=5430434\u0026n=IcbL89tCxZF-WseYC7\u0026sparams=expire,ei,ip,id,itag,source,requiressl,vprv,mime,ns,gir,clen,ratebypass,dur,lmt\u0026sig=AOq0QJ8wRAIgZZ1Fz9rEZKDswn91STZ8orWQFHNYXmrI8FfJnFwFj5oCIB6EFwrPqooax32K5gn3Os3AukR0Ge1AXVVHqqiRwCre\u0026lsparams=mh,mm,mn,ms,mv,mvi,pl,initcwndbps\u0026lsig=AG3C_xAwRQIhALmNXbav6mopdsK7EIz1dj9gj-p3FranpRnAOxi682VxAiAFietCBjPwZG9muFG3FwB_2pBMBwEG79DnjG0LRTaNUw==",
            "mimeType": "video/mp4; codecs=\"avc1.42001E, mp4a.40.2\"",
            "bitrate": 365198,
            "width": 640,
            "height": 360,
            "lastModified": "1616954619793082",
            "contentLength": "1964769",
            "quality": "medium",
            "fps": 25,
            "qualityLabel": "360p",
        */


        return count($this->links);
    }
    
    /**
     * Ouputs an html list of links found
     */
    function linksHtml() {
        $tmpl = <<<EOFHTML
<h2 class="text-secondary">%s</h2>
<div>
    <a href="http://www.youtube.com/watch?v=%s" target="_blank"><img src="%s" border="0"></a>
</div>
<table class="table">
    <thead>
        <tr>
            <th>download</th>
            <th>width &times; height</th>
            <th>MIME and codec</th>
            <th>file size</th>
        </tr>
    </thead>
    <tbody>
%s
    </tbody>
</table>
EOFHTML;

        $row_template = <<<EOFROW
<tr>
    <td>
        <a href="%s" target="_blank">%s</a>
    </td>
    <td>%d&times;%d</td>
    <td>%s</td>
    <td>%dK</td>
</tr>
EOFROW;

        $rows = [];
        foreach ($this->links as $L) {
            array_push($rows, sprintf(
                $row_template,
                $L['url'],
                $L['qualityLabel'] ?? $L['quality'],
                $L['width'],
                $L['height'],
                $L['mimeType'],
                round($L['contentLength'] / 1024),
            ));
        }
        
        $html = sprintf( $tmpl,
            $this->videoDetails['title'],
            $this->videoDetails['videoId'],
            $this->videoDetails['thumbnail']['thumbnails'][0]['url'] ?? '',
            implode(PHP_EOL, $rows)
        );
        return $html;
    }
    
    
    public function pageHtml($content = '')
    {
        $tmpl = file_get_contents('index.html');
        return sprintf( $tmpl, $content);
    }
    
    public function formHtml()
    {
        $tmpl = <<<HTML
<form class="row row-cols-lg-auto g-3 align-items-center my-5" action="/" method="post" name="getvideo">
    <div class="col-12">
        <input name="video" type="text" class="form-control" id="video" placeholder="YouTube video url" value="%s">
    </div>

    <div class="col-12">
        <button type="submit" class="btn btn-primary">Get info</button>
    </div>
</form>
HTML;
        
        return sprintf($tmpl, $this->videoId);
    }
    
    public function getInfo()
    {
        if (empty($this->videoId)) {
            return;
        }

        $infoUrl = "https://www.youtube.com/get_video_info?&video_id=" . $this->videoId;
 
        // fetch data
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_HEADER => 0,
        ]);

        $infoLine = curl_exec($ch);
        curl_close($ch);

        
        $this->parseVideoData($infoLine); // fills 'videoData' property
        $n = $this->parseFormats();
        
        return $n;
    }	
}