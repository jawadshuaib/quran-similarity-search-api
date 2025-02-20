<?php
/* 
Translates a word from Arabic to English
{
"text": "اللَّهِ الرَّحْمَـٰنِ الرَّحِيمِ",
"translated": "Allah, the Most Gracious, the Most Merciful"
}

Sample request: 
http://localhost/quran/api/google-translate.php?text=الله
*/

include_once ("api.php");
include_once ("../connection/pwd/pwd.php");

// Turn cache on or off
// This is useful for testing purposes
$cacheOn = false;
$text = isset ($_GET['text']) ? make_safe($_GET['text']) : NULL;

// Cache adapter for phpFastCache
// Usaged: https://grohsfabian.com/how-to-use-php-caching-with-mysql-queries-to-improve-performance/
$cacheConfig = new \Phpfastcache\Drivers\Files\Config([
    'path' => realpath(__DIR__) . '/cache',
    'securityKey' => CACHE_SECURITY_GOOGLE_TRANSLATE,
    'preventCacheSlams' => true,
    'cacheSlamsTimeout' => 20,
    'secureFileManipulation' => true
]);
\Phpfastcache\CacheManager::setDefaultConfig($cacheConfig);
$cache = \Phpfastcache\CacheManager::getInstance('Files');


// Delete cache
// $inst = 'google-text.php?text=الله
// $cache->deleteItem($inst);

if ($cacheOn) {
    // Get instance of the cache
    $cachedInstance = $cache->getItem('google-translate.php?text='.$text);
    $isCached = !(is_null ($cachedInstance->get ()));	
} else {
    $isCached = false;
}

// Fetch from database if not cached
$translated = '';
if (!$isCached) {
    $error=false;
    if (!$error) {
        if (strlen ($text) === 0) {
            $error = true;
            $reason = "There is no text provided to translate.";
        }

        if (($_SERVER['HTTP_REFERER'] != "https://www.quran-ml.com/") && ($_SERVER['HTTP_REFERER'] != "http://localhost:3000/")) {
            $error = true;
            // This request is coming from an unknown domain. Give bogus error.
            $reason = "SSL identity invalid";
        }

        if (!$error) {			
            $url = "https://translation.googleapis.com/language/translate/v2?key=".GOOGLE_TRANSLATE_API_KEY."&q=".urlencode($text)."&target=en";
            
            // create & initialize a curl session
            $curl = curl_init();

            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_HEADER, false);

            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
            
            // $output contains the output string
            $response = curl_exec($curl);

            // print_r ($response);		
            $response = json_decode ($response, true);

            $error = curl_error($curl);
            // close curl resource to free up system resources
            // (deletes the variable made by curl_init)						
            curl_close($curl);
                
            if ($error) {
              $reason = "cURL Error #:" . $error;
            } else {	
              $translated = $response['data']['translations'][0]['translatedText'];
            }			
        }

    }

    if ($error) {
        $json = '{ "error": "'.$reason.'" }';
    } else {
                
        
        $json = '{';
        $json .= 		
'"text": "'.$text.'",
"translated": "'.$translated.'"';
        $json .= '}';		

        // $json = json_encode ($json);

        // Save the json data to the cache 
        if ($cacheOn) {
            $cache->save(
                // Expire in 5 days
                $cachedInstance->set($json)->expiresAfter(432000)
            );	
        }
        
    }	
} 
// Fetch from cache
else {
    $json = $cachedInstance->get();
}

echo $json;
?>