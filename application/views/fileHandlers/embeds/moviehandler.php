<?
$fileObjectId = $fileObject->getObjectId();

$interactiveTranscript = false;

if(isset($widgetObject->parentWidget->interactiveTranscript) && $widgetObject->parentWidget->interactiveTranscript = true) {

  $interactiveTranscript = true;
}

$mediaArray = array();
if(isset($fileContainers['stream'])) {
  $entry["type"] = "hls";
  $entry["file"] = stripHTTP(instance_url("/fileManager/getStream/" . $fileObjectId . "/base"));
  $entry["label"] = "Streaming";
  $mediaArray["stream"] = $entry;
}

if(isset($fileContainers['mp4sd'])) {
  $entry["type"] = "mp4";
  $entry["file"] = stripHTTP($fileContainers['mp4sd']->getProtectedURLForFile());
  $entry["label"] = "SD";
  $mediaArray["mp4sd"] = $entry;
}

if(isset($fileContainers['mp4hd'])) {
  $entry["type"] = "mp4";
  $entry["file"] = stripHTTP($fileContainers['mp4hd']->getProtectedURLForFile());
  $entry["label"] = "HD";
  $mediaArray["mp4hd"] = $entry;
}

if(isset($fileContainers['mp4hd1080'])) {
  $entry["type"] = "mp4";
  $entry["file"] = stripHTTP($fileContainers['mp4hd1080']->getProtectedURLForFile());
  $entry["label"] = "HD1080";
  $mediaArray["mp4hd1080"] = $entry;
}

$derivatives = array();
if($fileObject->sourceFile->metadata["duration"] < 300) {
  if(array_key_exists("stream", $mediaArray)) {
    $derivatives[] = $mediaArray["stream"];
  }
  if(array_key_exists("mp4sd", $mediaArray)) {
    $derivatives[] = $mediaArray["mp4sd"];
  }
  if(array_key_exists("mp4hd", $mediaArray)) {
    $derivatives[] = $mediaArray["mp4hd"];
  }

  
}
else {
  if(array_key_exists("stream", $mediaArray)) {
    $derivatives[] = $mediaArray["stream"];
  }
  if(array_key_exists("mp4sd", $mediaArray)) {
    $derivatives[] = $mediaArray["mp4sd"];
  }
  if(array_key_exists("mp4hd", $mediaArray)) {
    $derivatives[] = $mediaArray["mp4hd"];
  }

  
}



$playlist = [];

$playlist["image"] = null;

if(isset($fileContainers['imageSequence'])) {
  $playlist["image"] = stripHTTP($fileContainers['imageSequence']->getProtectedURLForFile("/2"));
}
if(isset($fileObject->sourceFile->metadata["spherical"])) {
  $playlist["stereomode"] = isset($fileObject->sourceFile->metadata["stereo"])?"stereoscopicLeftRight":"monoscopic";
}
$playlist["sources"] = [];
foreach($derivatives as $entry) {
  $playlist["sources"][] = [
    "type"=>$entry["type"], 
    "file"=>$entry["file"], 
    "label"=>$entry["label"],
    "default"=>($entry["label"]=="HD")?"true":"false"
  ];
}

$playlist["tracks"] = [];
$captionPath = null;
$cahpterPAth = null;

if(isset($fileContainers['vtt'])) {
  $playlist["tracks"][] = [
    "file" => isset($fileContainers['vtt'])?stripHTTP($fileContainers['vtt']->getProtectedURLForFile(".vtt")):null,
    "kind" => "thumbnails"
  ];
}

$captionPath = null;
if(isset($widgetObject->sidecars) && array_key_exists("captions", $widgetObject->sidecars) && strlen($widgetObject->sidecars['captions'])>5) {
  $captionPath = stripHTTP(instance_url("fileManager/getSidecar/" . $fileObjectId . "/captions"));
  $playlist["tracks"][] = [
    "file" => $captionPath,
    "label" => "English",
    "kind" => "captions"
  ];
}
else {
  $interactiveTranscript = false;
}

$chapterPath=  null;
if(isset($widgetObject->sidecars) && array_key_exists("chapters", $widgetObject->sidecars) && strlen($widgetObject->sidecars['chapters'])>5) {
  $chapterPath = stripHTTP(instance_url("fileManager/getSidecar/" . $fileObjectId . "/chapters"));
  $playlist["tracks"][] = [
    "file" => $chapterPath,
    "kind" => "chapters"
  ];
  
}


?>
<? // https://github.com/jwplayer/jwplayer can build from ?>
<? // built our own dist of the video.js fork https://github.com/videojs/vtt.js ?>
<script src="/assets/jwplayer/vtt.js.min"></script>
<script src="/assets/jwplayer/parsesrt.js"></script>
<script src="https://cdn.jwplayer.com/libraries/pTP0K0kA.js"></script>
<script src="/assets/js/excerpt.js"></script>


<script>

if(typeof objectId == 'undefined') {
  objectId = "<?=$fileObjectId?>";
}
</script>

<? if(!isset($fileContainers) || count($fileContainers) == 1):?>
  <p class="alert alert-info">No derivatives found.
  <?if(!$this->user_model->userLoaded):?>
    <?$this->load->view("errors/loginForPermissions")?>
    <?if($embedded):?>
      <?$this->load->view("login/login")?>
    <?endif?>
  <?endif?>
  </p>
  
<?else:?>    
    <div id="videoElement">Loading the player...</div>
    <?if($interactiveTranscript):?>
    <div class="transcriptContainer">
		<div id="searchbox" class="searchbox">
			<span id="match" class="match">0 of 0</span>
			<input id="search" type="search" class="search" />
		</div>
		<div id="transcript" class="transcript"></div>
	</div>
  <?endif?>
    <script type="text/javascript">

  var transcriptOffset = "<?=($interactiveTranscript?240:0) ?>px";
  var haveSeeked = false;
  var havePaused = false;
  var currentPosition = null;
  var firstPlay = false;
  var isChrome = /Chrome/.test(navigator.userAgent) && /Google Inc/.test(navigator.vendor); 
  var chromeVersion = false;
  if(isChrome) {
    var raw = navigator.userAgent.match(/Chrom(e|ium)\/([0-9]+)\./);
    var version = parseInt(raw[2], 10);
    if(!isNaN(version)) {
      chromeVersion = version;
    }
  }
  var weAreHosed = false;
  var firstPlay = false;
  var seekTime = null;
  var needSeek = false;
  function buildPlayer() {
    jwplayer("videoElement").setup({
      ga: { label:"label"},
      playlist: <?=json_encode($playlist) ?>,
      width: "100%",
      height: `calc(100vh - ${transcriptOffset})`,
      preload: 'none'
      });
    }
    
    function registerJWHandlers() {
      jwplayer().onReady(function(event) {
        // jwplayer().onQualityLevels(function(event) {
        //   if(event.levels.length > 1 && screen.width > 767) {
        //     jwplayer().setCurrentQuality(1);
        //   }
          
        // });
        
        jwplayer().on('seek', function(event) {
          haveSeeked=true;
          if(jwplayer().getState('paused') == 'paused') {
            seekTime = event.offset;
            needSeek = true;
          }
          
        });
        jwplayer().on('pause', function(event) {
          havePaused=true;
        });
        jwplayer().on('play', function(event) {
          if(!firstPlay) {
            firstPlay = true;
            return;
          }
          else {
            console.log("on second play");
          }

          if(event.playReason == "external") {
            return;
          }
          console.log(chromeVersion);
          if((haveSeeked || havePaused) && isChrome && chromeVersion < 78) {
            var playlist = jwplayer().getPlaylist();

            if(playlist[0].label == "Streaming" || playlist[0].sources[0].label == "Streaming") {
              return;
            }

            rebuilding = true;
            haveSeeked=false;
            havePaused=false;
            weAreHosed = true;
            firstPlay = false;
            currentPosition= jwplayer().getPosition();
            buildPlayer();
            registerJWHandlers();
            // jwplayer().play();
            if(needSeek) {
              console.log("seeking to existing location" + seekTime)
              jwplayer().seek(seekTime);
              needSeek = false;
            }
            else {
              console.log("seeking to new position:" + currentPosition)
              jwplayer().seek(currentPosition);
            }
            needSeek = false;
            currentPosition = null;
            
            
            
          }
          
        })
        
      });
      
    }

    buildPlayer();
    registerJWHandlers();

        // JW player is dumb about default to HD footage so we do it manually if possible
    
    
    $(".videoColumn").on("remove", function() {
      jwplayer("videoElement").remove();
    });




var chapters = [];
var captions = [];
var caption = -1;
var matches = [];
var query = "";
var cycle = -1;

var captionPath = '<?=$captionPath?>';
var chapterPath = '<?=$chapterPath?>';

const transcript = document.getElementById('transcript');
const search = document.getElementById('search');
const match = document.getElementById('match');



var parser = new WebVTT.Parser(window, WebVTT.StringDecoder()),
    cues = [],
    chapterCues = [],
    regions = [];
jwplayer().on('ready', function() {

  var promises = [];
  if(captionPath) {
    promises.push(new Promise((resolve) => fetch(captionPath).then(r => r.text()).then(t => resolve(t))));
  }
  if(chapterPath) {
    promises.push(new Promise((resolve) => fetch(chapterPath).then(r => r.text()).then(t => resolve(t))));
  }
  Promise.all(promises)
  // .then(textData => textData.map(t => t.split('\n\n').splice(1).map(s => parse(s))))
  .then(parsedData => {
    captions = null;
    chapters = null;
    if(captionPath && parsedData[0]) {
      captions = parsedData[0];
    }
    if(chapterPath && !captionPath) {
      chapters = parsedData[0];
    }
    else if(chapterPath && captionPath) {
      chapters = parsedData[1];
    }

    var captionFormat = null;
    if(captions) {

      captionFormat = (captions.substring(0, 6).toUpperCase() == "WEBVTT")?"webvtt":"srt";
    }
    if(chapters) {
      chapterFormat = (chapters.substring(0, 6).toUpperCase() == "WEBVTT")?"webvtt":"srt";
    }
    
    if(captions) {
      if(captionFormat == "webvtt") {
        parser.oncue = function(cue) {
          cues.push(cue);
        };
        parser.onregion = function(region) {
          regions.push(region);
        }
        parser.parse(captions);
        parser.flush();
      }
      else {
        cues = parseSRT(captions);

      }
      
    }


    if(chapters) {
      parser.oncue = function(cue) {
        chapterCues.push(cue);
      };
      parser.onregion = function(region) {
        chapterCues.push(region);
      }
      parser.parse(chapters);
      parser.flush();
    }
    

  parser.oncue = function(cue) {
      cues.push(cue);
    };
    parser.onregion = function(region) {
      regions.push(region);
    }
    parser.parse(captions);
    parser.flush();


    loadCaptions();
  });
});

function loadCaptions() {

  var h = "<p>";
  var section = 0;
  cues.forEach((caption, i) => {
    if (section < chapterCues.length && caption.startTime > chapterCues[section].startTime) {
      h += "</p><h4>"+chapterCues[section].text+"</h4><p>";
      section++;
    }
    h += `<span id="caption${i}">${caption.text}</span>`;
  });
  transcript.innerHTML = h + "</p>";
};



// Highlight current caption and chapter
jwplayer().on('time', function(e) {
  var p = e.position;
  for(let j = 0; j<cues.length; j++) {
    if(cues[j].startTime < p && cues[j].endTime > p) {
      if(j != caption) {
        var c = document.getElementById(`caption${j}`);
        if(caption > -1) {
          document.getElementById(`caption${caption}`).className = "";
        }
        c.className = "current";
        if(query == "") {
          transcript.scrollTop = c.offsetTop - transcript.offsetTop - 40;
        }
        caption = j;
      }
      break;
    }
  }
});

// Hook up interactivity
transcript.addEventListener('click', function(e) {
  if (e.target.id.indexOf('caption') === 0) {
    let i = Number(e.target.id.replace('caption', ''));

    jwplayer().seek(cues[i].startTime);
  }
});



/**
 * Search elements below here
 */

search.addEventListener('focus', () => setTimeout(() => search.select(), 100));

search.addEventListener('keydown', function(e) {
  switch (e.key) {
    case 'Escape':
    case 'Esc':
      resetSearch();
      break;

    case 'Enter':
      let q = this.value.toLowerCase();
      if (q.length) {
        if (q === query) {
          let thisCycle;
          if (e.shiftKey) {
            thisCycle = cycle <= 0 ? (matches.length - 1) : (cycle - 1);
          } else {
            thisCycle = (cycle >= matches.length - 1) ? 0 : (cycle + 1);
          }
          console.log(thisCycle);
          cycleSearch(thisCycle);
          return;
        }
        resetSearch();
        searchTranscript(q);
        return;
      }
      resetSearch();
      break;

    default:
      // none
  }
});

const sanitizeRegex = q => {
  return q.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
};

// Execute search
function searchTranscript(q) {
  matches = [];
  query = q;
  cues.forEach(({ text }, loc) => {
    let matchSpot = text.toLowerCase().indexOf(q);
    if (matchSpot > -1) {
      const replacer = sanitizeRegex(q);
      document.getElementById(`caption${loc}`).innerHTML = text.replace(new RegExp(`(${replacer})`, 'gi'), `<em>$1</em>`, );
      matches.push(loc);
    }
  });
  if(matches.length) {
    cycleSearch(0);
  } else {
    resetSearch();
  }
};
function cycleSearch(i) {
  if (cycle > -1) {
    let o = document.getElementById(`caption${matches[cycle]}`);
    o.querySelector('em').classList.remove('current');
  }
  console.log(matches[i]);
  const c = document.getElementById(`caption${~~matches[i]}`);
  c.querySelector('em').classList.add('current');
  match.textContent = `${i + 1} of ${matches.length}`;
  transcript.scrollTop = c.offsetTop - transcript.offsetTop - 40;
  cycle = i;
};

function resetSearch() {
  if (matches.length) {
    cues.forEach((caption, i) => {
      document.getElementById(`caption${~~i}`).textContent = caption.text;
    });
  }

  query = "";
  matches = [];
  match.textContent = "0 of 0";
  cycle = -1;
  transcript.scrollTop = 0;
};


  </script>

<style>
.jw-cue {
  background-color: yellow !important;
  height: 15px !important;
}



.transcriptContainer {
	background-color: #e1e7e9;
	font-family: Arial, sans-serif;
	height: 240px;
	overflow: hidden;
	width: 100%;
}

.transcriptContainer h3 {
	color: #000;
	font-size: 14px;
	margin: 0;
	padding: 20px;
	text-align: left;
}

.searchbox {
	display: block;
	margin: 10px 20px;
	position: relative;
}

.searchbox input {
	background: #fff url("/assets/jwplayer/search.png") no-repeat top left;
	border-radius: 3px;
	border: none;
	color: #000;
	font-size: 16px;
	line-height: 20px;
	padding: 5px 20px 5px 30px;
	width: 100%;
}

.searchbox .match {
	color: #000;
	font-size: 14px;
	line-height: 20px;
	position: absolute;
	right: 10px;
	top: 5px;
}

.transcript {
	padding: 0 20px;
  padding-bottom: 50px;
  height: 100%;
	overflow: auto;
	flex: 1 1 auto;
}

.transcript p {
	font-size: 15px;
	overflow: hidden;
	text-align: left;
	color: #000;
	line-height: 20px;
}

.transcript p:empty {
	display: none;
}

.transcript span {
	display: inline;
	padding: 4px 2px;
	line-height: 24px;
	cursor: pointer;
	color: #000;
}

.transcript span.current {
	background: #caedff;
	color: #000;
}

.transcript span:hover {
	background-color: #98d8f4;
}

.transcript span.current:hover {
	color: #fff;
}

.transcript span em {
	background: #666;
	color: #000;
	font-style: normal;
}

.transcript span em.current {
	background: #ff0046;
}

.transcript h4 {
	margin: 25px 0 15px;
	text-align: left;
	color: #000;
	font-weight: bold;
}

.caption-copy {
	max-width: 640px;
	margin: 20px auto;
	text-align: left;
	padding: 10px;
}

.caption-copy div,
.caption-copy ol,
.caption-copy li {
	text-align: left;
	font-size: 20px;
}

.caption-copy div {
	font-weight: 400;
	margin: 15px 0;
}

.caption-copy ol {
	margin-left: 15px;
	margin-top: 20px;
}

.caption-copy li {
	margin-bottom: 10px;
	font-size: 13px;
	font-weight: 600;
}

</style>

  <?endif?>
