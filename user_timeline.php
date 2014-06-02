<?php

ini_set('display_errors', 1);
require_once('TwitterAPIExchange.php');


/** Set access tokens here - see: https://dev.twitter.com/apps/ * */
$settings = array(
    'oauth_access_token' => "2451534170-ISETliFOuTR2BYoVSLCqZO8tRrOmSX7SfMcuECa",
    'oauth_access_token_secret' => "mRB9c9deqEdSIjblXf6wgm05N6fizKJohNFykYfjFuWYx",
    'consumer_key' => "isYSRFHsJGZeklzczLtNXbA8V",
    'consumer_secret' => "YAW2RhMjHiWJ3V3YJnm9yf5AZ2HdstszxTLJpAdaOq9j2Todon"
);


/**
 * Currently only first 200 tweets are retireveed using the "count" parameter. 
 * Use the  "since_id" and "max_id" parameters and make repeated requests to  to get  all the tweets. 
 * refer to the following link for the statergy :https:dev.twitter.com/docs/working-with-timelines use the code from index.php to download all the media w.r.t to a twe
 * 
 * 
 * first_getfield is call without the max_id or since ID , once we get a json object from the first call
 * the max_id and since_id are determined 
 */
//URL for retrieveing tweets for a user (user_d)
$url = 'https://api.twitter.com/1.1/statuses/user_timeline.json';
$max_id = 999999999999999999;

$num_tweets = 0;
$num_images = 0;
$new_users = array();
$pattern = "/[@][A-Za-z0-9]+/";
/* list of users already processed for data */
$processed_users = array();
/* number of users processed */
$processed_user_count = 0;
/* number of new users added */
$num_elements_in_new_user = count($new_users);
$new_getfield = "";
//TODO :stupid way of getting out the loop --- fix this you are bettter than this !
$GLOBALS['new_max_id'] = $max_id + 1;
$GLOBALS['old_max_id'] = $max_id;


//seed users to start crawling  from
$users = array(1 => '@CamThompsonWNEW', 2 => '@Organizerx', 3 => '@Cool_Revolution', 4 => '@johnzangas', 5 => '@rousseau_ist', 6 => '@Gen_Knoxx', 7 => '@jamesFTinternet', 8 => '@Agent350', 9 => '@ARStrasser', 10 => '@johnzangas', 11 => '@350', 11 => '@bri_xy');

//keywords to hunt for in the tweets.
$keywords = array(1 => '?q=#KXLDissent', 2 => '?q=#RejectandProtect', 3 => '?q=#CowboyIndianAlliance', 4 => '?q=#nokxl', 5 => '?q=#OilSands', 6 => '?q=#KeystoneXL', 7 => '#NoKXL');

//this rate limit counter keeps track of number of get request to make sure we dont exceed  rate limit.
$rate_limit_counter = 0;

/*
 * utility function to reset all globals when one user is done
 */
function resetGlobals() {
    $max_id = 999999999999999999;
//TODO :stupid way of getting out the loop --- fix this you are bettter than this !
    $GLOBALS['new_max_id'] = $max_id + 1;
    $GLOBALS['old_max_id'] = $max_id;
}

/*
 * add those user from the text of the collected tweets that have retweeted about
 * keystone XL pipeline.
 */

function addNewUsers($string) {
    $user_list = array();
    //$pattern = "/[@][A-Za-z0-9]+/";
    if ($GLOBALS['num_elements_in_new_user'] < 500) {
        if (preg_match_all($GLOBALS['pattern'], $string, $matches)) {
            foreach ($matches[0] as $k => $v) {
                //getAllData($v);
                //$list_size = array_push($user_list, $v);
                $GLOBALS['num_elements_in_new_user'] = array_push($GLOBALS['new_users'], $v);
            }
        }
    }
}

/*
 * generates the "hashValue" :"text" combinantion 
 * and sends it out to stdout
 */

function processData($json_data, $user) {

    foreach ($json_data as $status) {
        $hashstring = $status->created_at;

        if ($status->id < $GLOBALS['max_id'] && !empty($status->id)) {
            $GLOBALS['max_id'] = $status->id;
        }

        $is_keyStone = false;
        foreach ($GLOBALS['keywords'] as $keyword) {
            if (strpos($status->text, $keyword)) {

                $is_keyStone = true;
            }
        }
        if ($is_keyStone) {
            $hashValue = hash("md5", $hashstring);
            writeTweet($status, $hashValue);
            addNewUsers($status->text);
            $GLOBALS['num_tweets'] = $GLOBALS['num_tweets'] + 1;
            if (array_key_exists("media", (array) $status->entities)) {
                downLoadImages($status, $hashValue);
            }
        }
}}


function writeTweet($status, $hashValue) {
    echo "\n";
    echo '"';
    echo $hashValue;
    echo '" : "';
    echo $status->text;
    echo '"';
    echo "\n";
}

function downLoadImages($status, $hashValue) {
    foreach ($status->entities->media as $images) {
        if ($images->media_url != "") {
            $mediaUrl = $images->media_url;
            $cmd = "wget --quiet -O\t" . $hashValue . ".png\t" . $mediaUrl;
            exec($cmd);
            echo "\n";
            $GLOBALS['num_images'] = $GLOBALS['num_images'] + 1;
        }
    }
}

function makeRequests($get_field) {
    if ( $GLOBALS['rate_limit_counter'] >= 180){
        sleep(900);
    } else {
    // $first_getfield = '?screen_name=JamesFrancoTV&max_id=473257670443409408&count=200&include_rts=1';
    $requestMethod = 'GET';
    $twitter = new TwitterAPIExchange($GLOBALS['settings']);
    $response = $twitter->setGetfield($get_field)->buildOauth($GLOBALS['url'], $requestMethod)->performRequest();
    $json_data = json_decode($response);
    $GLOBALS['rate_limit_counter']++;
    return $json_data;
    }
}

/*
 * Iterates through users timeline , fetches all the media
 * and downloads it to the current direcotory.
 * twitter max_id impleentation used to browse through all the tweets in timeline.
 */

function getAllData($user) {

    $first_getfield = '?screen_name=' . $user . '&count=200';
    $json_data = makeRequests($first_getfield);
    if (!empty($json_data)) {
        echo "processing data for .." . $user . "\n";
        processData($json_data, $user);
    }
    /**
     * getfield including the max_id for subsequent calls 
     */
    if ($GLOBALS['max_id'] != 0) {
        $GLOBALS['new_getfield'] = '?screen_name=' . $user . '&count=200&max_id=' . $GLOBALS['max_id'];
    } else {

        echo "Empty Response! ";
    }

    while ($GLOBALS['old_max_id'] != $GLOBALS['new_max_id']) {
        $new_json_data = makeRequests($GLOBALS['new_getfield']);
        $GLOBALS['old_max_id'] = $GLOBALS['new_max_id'];

        //get the next get_field
        foreach ($new_json_data as $status) {
            //     echo "inside foreach\n".$status->id;
            if ($status->id < $GLOBALS['new_max_id']) {
                // echo "really cant get here????";
                $GLOBALS['new_max_id'] = $status->id;
            }
        }
        $GLOBALS['new_getfield'] = '?screen_name=' . $user . '&count=200&max_id=' . $GLOBALS['new_max_id'];
        //send this data to collect the media
        if (count((array) $new_json_data)) {
            processData($new_json_data, $user);
        }
       // echo "New_max_id ......" . $GLOBALS['new_max_id'] . "\n";
    }
}

//get the tweets from the seed users 
foreach ($users as $user) {
    if (!in_array($user, $GLOBALS['processed_users'])) {
        echo "Processing user..." . $user . "\n";
           resetGlobals();
        getAllData($user);
    }
}


echo "$%$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$4";
print_r($new_users);
//from the list of all the users retweeting the concening tweets
foreach ($new_users as $user) {
    if (!in_array($user, $GLOBALS['processed_users'], $user)) {
        resetGlobals();
        getAllData($user);
    }
}
echo "Number of tweets processed :" . $num_tweets . "\n";
