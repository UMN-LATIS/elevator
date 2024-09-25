<script>
function makeRequestWithUserGesture() {
  var isSafari = /^((?!chrome|android).)*safari/i.test(navigator.userAgent);
  if(isSafari) {
    var promise = document.requestStorageAccess();
    promise.then(
      function (f) {
        location.reload();
      },
      function (f) {
      }
    );
  }
  else {
    popitup();
  }

  
}
</script>
<script>
if(document.cookie && document.cookie.search(/_check_is_passive=/) >= 0){

    if(window.location.hash  == "#firstFrame" && inIframe()) {
        var frames = window.parent.frames;
        for(index = 0; index < frames.length; index++) {
            frames[index].postMessage("pageLoaded", "*");
        }
    }

    // If we have the opensaml::FatalProfileException GET arguments
    // redirect to initial location because isPassive failed
    if (
        window.location.search.search(/errorType/) >= 0
        && window.location.search.search(/RelayState/) >= 0
        && window.location.search.search(/requestURL/) >= 0
    ) {
        var startpos = (document.cookie.indexOf('_check_is_passive=')+18);
        var endpos = document.cookie.indexOf(';', startpos);
        window.location = document.cookie.substring(startpos,endpos);
    }
} else {
    if(window.location.hash  == "#secondFrame" && inIframe()) {

    }
    else {
        // Mark browser as being isPassive checked

        // safari doesn't allow third party cookies unless the user has accessed the site before, so we need to have them click to launch a popup..
        if(!document.cookie) {
          // window.onload = function(e) {
          //   document.body.innerHTML = "To load this resource, please click the button below.  If you continue to encounter this issue, your web browser may be blocking cookies.  <input type=button value='Load Resource' onClick='makeRequestWithUserGesture();'>";
          // }

        }
        else {
          var botPattern = "(googlebot\/|Googlebot-Mobile|Googlebot-Image|Google favicon|Mediapartners-Google|bingbot|slurp|java|wget|curl|Commons-HttpClient|Python-urllib|libwww|httpunit|nutch|phpcrawl|msnbot|jyxobot|FAST-WebCrawler|FAST Enterprise Crawler|biglotron|teoma|convera|seekbot|gigablast|exabot|ngbot|ia_archiver|GingerCrawler|webmon |httrack|webcrawler|grub.org|UsineNouvelleCrawler|antibot|netresearchserver|speedy|fluffy|bibnum.bnf|findlink|msrbot|panscient|yacybot|AISearchBot|IOI|ips-agent|tagoobot|MJ12bot|dotbot|woriobot|yanga|buzzbot|mlbot|yandexbot|purebot|Linguee Bot|Voyager|CyberPatrol|voilabot|baiduspider|citeseerxbot|spbot|twengabot|postrank|turnitinbot|scribdbot|page2rss|sitebot|linkdex|Adidxbot|blekkobot|ezooms|dotbot|Mail.RU_Bot|discobot|heritrix|findthatfile|europarchive.org|NerdByNature.Bot|sistrix crawler|ahrefsbot|Aboundex|domaincrawler|wbsearchbot|summify|ccbot|edisterbot|seznambot|ec2linkfinder|gslfbot|aihitbot|intelium_bot|facebookexternalhit|yeti|RetrevoPageAnalyzer|lb-spider|sogou|lssbot|careerbot|wotbox|wocbot|ichiro|DuckDuckBot|lssrocketcrawler|drupact|webcompanycrawler|acoonbot|openindexspider|gnam gnam spider|web-archive-net.com.bot|backlinkcrawler|coccoc|integromedb|content crawler spider|toplistbot|seokicks-robot|it2media-domain-crawler|ip-web-crawler.com|siteexplorer.info|elisabot|proximic|changedetection|blexbot|arabot|WeSEE:Search|niki-bot|CrystalSemanticsBot|rogerbot|360Spider|psbot|InterfaxScanBot|Lipperhey SEO Service|CC Metadata Scaper|g00g1e.net|GrapeshotCrawler|urlappendbot|brainobot|fr-crawler|binlar|SimpleCrawler|Livelapbot|Twitterbot|cXensebot|smtbot|bnf.fr_bot|A6-Indexer|ADmantX|Facebot|Twitterbot|OrangeBot|memorybot|AdvBot|MegaIndex|SemanticScholarBot|ltx71|nerdybot|xovibot|BUbiNG|Qwantify|archive.org_bot|Applebot|TweetmemeBot|crawler4j|findxbot|SemrushBot|yoozBot|lipperhey|y!j-asr|Domain Re-Animator Bot|AddThis)";
          var re = new RegExp(botPattern, 'i');
          if (!re.test(navigator.userAgent)) {

          document.cookie = "_check_is_passive=" + window.location + ";path=/; SameSite=None; Secure";
          // Redirect to Shibboleth handler
          
          if(typeof basePath == "undefined") {

            basePath = window.Elevator.config.instance.base.path;
          }
          window.location.href = "https://" + window.location.hostname + "/Shibboleth.sso/Login?isPassive=true&target=" + encodeURIComponent("https://"+window.location.hostname + basePath + "/loginManager/remoteLogin/true?redirect=" + encodeURIComponent(window.location));

        }
      }

    }
}
//-->
</script>
