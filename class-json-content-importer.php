<?php
/*
CLASS JsonContentImporter
Description: Class for WP-plugin "JSON Content Importer"
Version: 1.1.2
Author: Bernhard Kux
Author URI: http://www.kux.de/
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/


class JsonContentImporter {

    /* shortcode-params */		
    private $numberofdisplayeditems = -1; # -1: show all
    private $feedUrl = ""; # url of JSON-Feed
    private $urlgettimeout = 5; # 5 sec default timeout for http-url
    private $basenode = ""; # where in the JSON-Feed is the data? 
    private $oneOfTheseWordsMustBeIn = ""; # optional: one of these ","-separated words have to be in the created html-code

    /* plugin settings */
    private $isCacheEnable = FALSE;
 
    /* internal */
		private $cacheFile = "";
		private $jsondata;
		private $feedData  = "";
 		private $cacheFolder;
    private $datastructure = "";
    private $triggerUnique = NULL;


		public function __construct(){  
			 add_shortcode('jsoncontentimporter' , array(&$this , 'shortcodeExecute')); # hook shortcode
		}
    
    
    /* shortcodeExecute: read shortcode-params and check cache */
		public function shortcodeExecute($atts , $content = ""){
			
      extract(shortcode_atts(array(
        'url' => '',
        'urlgettimeout' => '',
        'numberofdisplayeditems' => '',
        'oneofthesewordsmustbein' => '',
        'basenode' => '',
      ), $atts));
      
      $this->feedUrl = $url;
      $this->oneOfTheseWordsMustBeIn = $oneofthesewordsmustbein;
      /* caching or not? */
			if (get_option('jci_enable_cache')==1) {
        # 1 = checkbox "enable cache" activ
        $this->cacheEnable = TRUE;
        # check cacheFolder
        $this->cacheFolder = WP_CONTENT_DIR.'/cache/jsoncontentimporter/'; 
        if (!is_dir($this->cacheFolder)) {
          # $this->cacheFolder is no dir: not existing
          # try to create $this->cacheFolder
          $mkdirError = @mkdir($this->cacheFolder); 
          if (!$mkdirError) {
            # mkdir failed, usually due to missing write-permissions
            echo "<hr><b>caching not working, plugin aborted:</b><br>";
            echo "plugin / wordpress / webserver can't create<br><i>".$this->cacheFolder."</i><br>";
            echo "therefore: set directory-permissions to 0777 (or other depending on the way you create directories with your webserver)<hr>"; 
            # abort: no caching possible
            exit;
          }
        }
        # $this->cacheFolder writeable?
        if (!is_writeable($this->cacheFolder)) {
          echo "please check cacheFolder:<br>".$this->cacheFolder."<br>is not writable. Please change permissions.";
          exit;
        }
        # cachefolder ok: set cachefile
  			$this->cacheFile = $this->cacheFolder . urlencode($this->feedUrl);  # cache json-feed
      } else {
        # if not=1: no caching
        $this->cacheEnable = FALSE;
      }

      /* set other parameter */      
      if ($numberofdisplayeditems>=0) {
        $this->numberofdisplayeditems = $numberofdisplayeditems;
      }
      if (is_numeric($urlgettimeout) && ($urlgettimeout>=0)) {
        $this->urlgettimeout = $urlgettimeout;
      }

			$this->retrieveJsonData();
      $this->basenode = $basenode;
      $this->datastructure = preg_replace("/\n/", "", $content);
      
      require_once plugin_dir_path( __FILE__ ) . '/class-json-parser.php';
      $JsonContentParser = new JsonContentParser($this->jsondata, $this->datastructure, $this->basenode, $this->numberofdisplayeditems, $this->oneOfTheseWordsMustBeIn);
			return $JsonContentParser->retrieveDataAndBuildAllHtmlItems();
			
		}
    
    /* retrieveJsonData: get json-data and build json-array */
		private function retrieveJsonData(){
      # check cache: is there a not expired file? 
			if ($this->cacheEnable) {
        # use cache
        if ($this->isCacheFileExpired()) {
          # get json-data from cache
          $this->retrieveFeedFromCache();
        } else {
          $this->retrieveFeedFromWeb();
        }
      } else {
        # no use of cache OR cachefile expired: retrieve json-url
        $this->retrieveFeedFromWeb();
      }

  		if(empty($this->feedData)) {
        echo "error: get of json-data failed - plugin aborted: check url of json-feed";
        exit;
      }
      
			# build json-array
			$this->decodeFeedData();
		}
    
    
    /* isCacheFileExpired: check if cache enabled, if so: */
		private function isCacheFileExpired(){
			# get age of cachefile, if there is one...
      if (file_exists($this->cacheFile)) {
        $ageOfCachefile = filemtime($this->cacheFile);  # time of last change of cached file
      } else {
        # there is no cache file yet
        return FALSE;
      }
      
      # get cache parameter
      $cacheTime = get_option('jci_cache_time');  # max age of cachefile: if younger use cache, if not retrieve from web
			$format = get_option('jci_cache_time_format');
      $cacheExpireTime = strtotime(date('Y-m-d H:i:s'  , strtotime(" -".$cacheTime." " . $format )));

      # if $ageOfCachefile is < $cacheExpireTime use the cachefile:  isCacheFileExpired = FALSE
      if ($ageOfCachefile < $cacheExpireTime) {
        return FALSE;
      } else {
        return TRUE;
      }
		}
    
    
		/* retrieveFeedFromWeb: get raw json-data */
		private function retrieveFeedFromWeb(){
      # wordpress unicodes http://openstates.org/api/v1/bills/?state=dc&q=taxi&apikey=4680b1234b1b4c04a77cdff59c91cfe7;
      # to  http://openstates.org/api/v1/bills/?state=dc&#038;q=taxi&#038;apikey=4680b1234b1b4c04a77cdff59c91cfe7
      # and the param-values are corrupted
      # un_unicode ampersand:
      $this->feedUrl = preg_replace("/&#038;/", "&", $this->feedUrl);
      #$useragent = "Mozilla/5.0 (Windows NT 6.1; WOW64; rv:35.0) Gecko/20100101 Firefox/35.0"; # in case of simulating a browser
      $args = array(
        'timeout'     => $this->urlgettimeout,
        #'httpversion' => '1.0',
        # 'user-agent'  => 'WordPress/' . $wp_version . '; ' . get_bloginfo( 'url' ),
        #'blocking'    => true,
        #'headers'     => array(),
        #'cookies'     => array(),
        #'body'        => null,
        #'compress'    => false,
        #'decompress'  => true,
        #'sslverify'   => true,
        #'stream'      => false,
        #'filename'    => null
      );
      $response = wp_remote_get($this->feedUrl, $args);
      if ( is_wp_error( $response ) ) {
        $error_message = $response->get_error_message();
        echo "Something went wrong fetching URL with JSON-data: $error_message<hr>";
			} else if(isset($response['body']) && !empty($response['body'])){
				$this->feedData = $response['body'];
				$this->storeFeedInCache();
			}
		}
    
    /* retrieveFeedFromCache: get cached filedata  */
		private function retrieveFeedFromCache(){
			if(file_exists($this->cacheFile)) {
        $this->feedData = file_get_contents($this->cacheFile);
      } else {
        # get from cache failed, try from web
        $this->retrieveFeedFromWeb();
      }
		}
    
    /* storeFeedInCache: store retrieved data in cache */
		private function storeFeedInCache(){
		  if (!$this->cacheEnable) {
        # no use of cache if cache is not enabled or not working
        return NULL;
      }
      $handle = fopen($this->cacheFile, 'w');
			if(isset($handle) && !empty($handle)){
				$cacheWritesuccess = fwrite($handle, $this->feedData); # false if failed
				fclose($handle);
        if (!$cacheWritesuccess) {
          echo "cache-error:<br>".$this->cacheFile."<br>can't be stored - plugin aborted";
          exit;
        } else {
          return $cacheWritesuccess; # no of written bytes
        }
			} else {
        echo "cache-error:<br>".$this->cacheFile."<br>is either empty or unwriteable - plugin aborted";
        exit;
      }
		}

    /* decodeFeedData: convert raw-json-data into array */
		public function decodeFeedData(){
			if(!empty($this->feedData))
				$this->jsondata =  json_decode($this->feedData);
        if (is_null($this->jsondata)) {
          # utf8_encode JSON-datastring, then try json_decode again
  				$this->jsondata =  json_decode(utf8_encode($this->feedData));
          if (is_null($this->jsondata)) {
            echo "JSON-Decoding failed. Check structure and encoding if JSON-data.";
            exit;
          }
        }
		}

	}
?>