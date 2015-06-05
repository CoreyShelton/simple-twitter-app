<?php

// Check to make sure the 'handle' parameter is being passed
if ( !empty($_GET['handle']) ) :
        
    //Function for stripping out malicious bits
    function cleanInput($input) {
    
        $search = array(
            '@<script[^>]*?>.*?</script>@si',   // Strip out javascript
            '@<[\/\!]*?[^<>]*?>@si',            // Strip out HTML tags
            '@<style[^>]*?>.*?</style>@siU',    // Strip style tags properly
            '@<![\s\S]*?--[ \t\n\r]*>@'         // Strip multi-line comments
        );
        
        $output = preg_replace($search, '', $input);
        return $output;
    }
    
    //Sanitization function
    function sanitize($input) {
        if (is_array($input)) {
            foreach($input as $var=>$val) {
                $output[$var] = sanitize($val);
            }
        } else {
            if (get_magic_quotes_gpc()) {
                $input = stripslashes($input);
            }
            $input  = cleanInput($input);
            $output = mysql_real_escape_string($input);
        }
        return $output;
    }
    
    // Set the twitter usersname here
    $twitter_handle = sanitize($_GET['handle']);
    
    function buildBaseString($baseURI, $method, $params) {
        $r = array();
        ksort($params);
        foreach($params as $key=>$value){
            $r[] = "$key=" . rawurlencode($value);
        }
        return $method."&" . rawurlencode($baseURI) . '&' . rawurlencode(implode('&', $r));
    }
    
    function buildAuthorizationHeader($oauth) {
        $r = 'Authorization: OAuth ';
        $values = array();
        foreach($oauth as $key=>$value)
            $values[] = "$key=\"" . rawurlencode($value) . "\"";
        $r .= implode(', ', $values);
        return $r;
    }
    
    // https://dev.twitter.com/rest/public
    $url = "https://api.twitter.com/1.1/statuses/user_timeline.json";

    $oauth_access_token = "INSERT_OAUTH_ACCESS_TOKEN_HERE";
    $oauth_access_token_secret = "INSERT_OAUTH_ACCESS_TOKEN_SECRET_HERE";
    $consumer_key = "INSERT_CONSUMER_KEY_HERE";
    $consumer_secret = "INSERT_CONSUMER_SECRET_HERE";
         
    $oauth = array( 
        'screen_name' => $twitter_handle,
        'count' => 5,
        'oauth_consumer_key' => $consumer_key,
        'oauth_nonce' => time(),
        'oauth_signature_method' => 'HMAC-SHA1',
        'oauth_token' => $oauth_access_token,
        'oauth_timestamp' => time(),
        'oauth_version' => '1.0'
    );

    $base_info = buildBaseString($url, 'GET', $oauth);
    $composite_key = rawurlencode($consumer_secret) . '&' . rawurlencode($oauth_access_token_secret);
    $oauth_signature = base64_encode(hash_hmac('sha1', $base_info, $composite_key, true));
    $oauth['oauth_signature'] = $oauth_signature;

    // Make requests
    $header = array(buildAuthorizationHeader($oauth), 'Expect:');
    $options = array( 
        CURLOPT_HTTPHEADER => $header,
        //CURLOPT_POSTFIELDS => $postfields,
        CURLOPT_HEADER => false,
        CURLOPT_URL => $url . '?screen_name='.$twitter_handle.'&count=5',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false
    );

    $feed = curl_init();
    curl_setopt_array($feed, $options);
    $json = curl_exec($feed);
    curl_close($feed);
    
    // Decode JSON data
    $twitter_data = json_decode($json);
    
    // Print out the JSON data
    //echo $base_info;
    print_r ($json);

else :
    // If no handle variable is passed then no data is here
    echo 'Oops no data here';
endif;

?>
