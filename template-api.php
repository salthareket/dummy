<?php
/**
 * The template for displaying all pages.
 *
 * This is the template that displays all pages by default.
 * Please note that this is the WordPress construct of pages
 * and that other 'pages' on your WordPress site will use a
 * different template.
 *
 * To generate specific templates for your pages you can use:
 * /mytheme/views/page-mypage.twig
 * (which will still route through this PHP file)
 * OR
 * /mytheme/page-mypage.php
 * (in which case you'll want to duplicate this file and save to the above path)
 *
 * Methods for TimberHelper can be found in the /lib sub-directory
 *
 * @package  WordPress
 * @subpackage  Timber
 * @since    Timber 0.1
 */
/*
Template Name: Api
*/

//use \Firebase\JWT\JWT;
//use GuzzleHttp\Client;

login_required();

//define('ZOOM_API_KEY', 'wL8TWeBEwiIyUn78fkOFT2f5BUUCewzNrJIO');
//define('ZOOM_SECRET_KEY', '_gTutgipRDCFCMKiJLVCbQ');



$context = Timber::context();
$post = Timber::get_post();
$context['post'] = $post;

$api = get_query_var("api");
$action = get_query_var("action");


function getGoogleClient($user_id=0){
    $client = new Google_Client();
    $client->setApplicationName('saran-group Google Meet Integration');
    $client->setScopes(Google_Service_Calendar::CALENDAR);
    $client->setAuthConfig(get_stylesheet_directory() . '/static/client_secret_999795826097-8pfme25gdedf72kkjfi38b7kfinrae30.apps.googleusercontent.com.json');
    $client->setAccessType('offline');
    //$client->setRedirectUri(get_site_url());
    //$client->setPrompt('select_account consent');

    $accessToken = get_user_meta($user_id, "_meetAccessToken", true);
    if (!empty($accessToken) && isset($accessToken)) {
       $client->setAccessToken($accessToken);
       echo "<<1>>";
    }

    if ($client->isAccessTokenExpired()) {
        echo "<<2>>";
            if ($client->getRefreshToken()) {
               $accessToken = $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
               echo "<<3>>";
               if (array_key_exists('error', $accessToken)) {
                   $page = get_page_by_path( 'api' );
                   $redirect_url = get_permalink( $page->ID )."google/token/";
                   $client->setRedirectUri($redirect_url);
                   $client->setPrompt('select_account consent');
                   $authUrl = $client->createAuthUrl();
                   $redirect_url = filter_var($authUrl, FILTER_SANITIZE_URL);
                   header('Location: ' . $redirect_url);
                   die;
               }
            } else {
                echo "<<4>>";
                if(!isset($_GET['code'])){
                    echo "<<5>>";
                    $client->setRedirectUri(get_site_url());
                    $client->setPrompt('select_account consent');
                    $authUrl = $client->createAuthUrl();
                    $redirect_url = filter_var($authUrl, FILTER_SANITIZE_URL);
                    header('Location: ' . $redirect_url);
                    die;
                }
                echo "<<6>>";
                $authCode = trim($_GET['code']);
                $client->authenticate($authCode);
                $accessToken = $client->getAccessToken();
                //$accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
                //$client->setAccessToken($accessToken);
                if (array_key_exists('error', $accessToken)) {
                    //$response["error"] = true;
                    //$response["message"] = join(', ', $accessToken);
                    //print_r(join(', ', $accessToken));
                }
            }
            add_user_meta($user_id, '_meetAccessToken', $accessToken, true );
    }
    return $client;
}

      
switch($api){
    case "google" :
        $data = $_SESSION["saran-groupMeetData"];
        $user_id = $data["user_id"];
        $user_id = $GLOBALS["user"]->ID;
        
        if($action != "token"){
            $client = getGoogleClient($user_id);
            $service = new Google_Service_Calendar($client);
            $calendarId = 'primary';            
        }

        switch($action){
            case "token" :
                $client = new Google_Client();
                $client->setApplicationName('saran-group Google Meet Integration');
                $client->setScopes(Google_Service_Calendar::CALENDAR);
                $client->setAuthConfig(get_stylesheet_directory() . '/static/client_secret_999795826097-8pfme25gdedf72kkjfi38b7kfinrae30.apps.googleusercontent.com.json');
                $client->setAccessType('offline');
                $authCode = trim($_GET['code']);
                $client->authenticate($authCode);
                $accessToken = $client->getAccessToken();
                //$accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
                //$client->setAccessToken($accessToken);
                update_user_meta( $user_id, "_meetAccessToken", $accessToken );
            break;

            case "create" :
                $vars = $data["vars"];
                $event = new Google_Service_Calendar_Event(array(
                        'summary' => $vars["summary"],//'Google Meet Invite Created Using Php by Rahul Kumar',
                        'location' => 'saran-group',
                        'description' => $vars["description"],//'Description: Mr. XYZ has an interview with Mr. Rahul Kumar, IPAC',
                        'start' => $vars["start"],/*array(
                              'dateTime' => '2023-11-12T11:00:00-00:00',
                              'timeZone' => 'Asia/Kolkata',
                        ),*/
                        'end' => $vars["end"],/*array(
                              'dateTime' => '2023-11-12T11:30:00-00:00',
                              'timeZone' => 'Asia/Kolkata',
                        ),*/
                        'recurrence' => array(
                          'RRULE:FREQ=DAILY;COUNT=1'
                        ),
                        'attendees' => $vars["attendees"],
                        'conferenceData' => [
                                'createRequest' => [
                                    'requestId' => $vars["requestId"],
                                    'conferenceSolutionKey' => ['type' => 'hangoutsMeet']
                                ]
                        ],
                        'reminders' => array(
                            'useDefault' => FALSE,
                            'overrides' => array(
                                array('method' => 'email', 'minutes' => 24 * 60),
                                array('method' => 'popup', 'minutes' => 10),
                            ),
                        ),
                        "guestsCanInviteOthers" => false,
                        "guestsCanModify" => false,
                        "guestsCanSeeOtherGuests" => false,
                        "setVisibility" => "PRIVATE"
                ));
                $event = $service->events->insert($calendarId, $event,array('conferenceDataVersion' => 1,'sendUpdates'=>'all'));
                update_post_meta( $data['application_id'], '_meetId', $event->getId() );
                update_post_meta( $data['application_id'], '_meetLink', $event->htmlLink );
            break;

            case "update":
                if(isset($data["event_id"]) && !empty($data["event_id"])){
                    $event = $service->events->get($calendarId, $data["event_id"]);
                    $event->setSummary('TEST');
                    $service->events->update($calendarId, $event->getId(), $event);
                }
            break;

            case "delete":
                if(isset($data["event_id"]) && !empty($data["event_id"])){
                    $service->events->delete('primary', $data["event_id"]);
                }
            break;
        }

        if(isset($data["redirect_url"])){
           wp_redirect($data["redirect_url"]);
           die;
        }
    break;

    case "zoom-jwt" :
        $data = $_SESSION["saran-groupMeetData"];
        $client = new Client([
            'base_uri' => 'https://api.zoom.us',
        ]);
        switch($action){

            case "create":
                $response = $client->request('POST', '/v2/users/me/meetings', [
                    "headers" => [
                        "Authorization" => "Bearer " . getZoomAccessToken()
                    ],
                    'json' => [
                        "topic" => "Let's Learn WordPress",
                        "type" => 1,//1:daily, 2:weekly, 3:monthly
                        "start_time" => "2023-01-30T20:30:00",
                        "duration" => "30", // 30 mins
                        "password" => "123456"
                    ],
                ]);
                $data = json_decode($response->getBody());
                print_r($data);
                echo "Join URL: ". $data->join_url;
                echo "<br>";
                echo "Meeting Password: ". $data->password;
                echo "Id:".$data->id;
            break;

            case "update":
                $response = $client->request('PATCH', '/v2/meetings/'.$data["event_id"], [
                    "headers" => [
                        "Authorization" => "Bearer " . getZoomAccessToken()
                    ],
                    'json' => [
                        "topic" => $vars["summary"],
                        "type" => 2,
                        "start_time" => "2021-07-20T10:30:00",
                        "duration" => "45", // 45 mins
                        "password" => "123456"
                    ],
                ]);
                if (204 == $response->getStatusCode()) {
                    echo "Meeting is updated successfully.";
                }
            break;

            case "delete":
                $response = $client->request("DELETE", "/v2/meetings/".$data["event_id"], [
                    "headers" => [
                        "Authorization" => "Bearer " . getZoomAccessToken()
                    ]
                ]);
                if (204 == $response->getStatusCode()) {
                    echo "Meeting deleted.";
                }
            break;
            
        }
    break;

    case "zoom" :
        $data = array();
        if(isset($_SESSION["saran-groupMeetData"])){
           $data = $_SESSION["saran-groupMeetData"]; 
        }
        $user_id = $data["user_id"];
        //$user_id = $GLOBALS["user"]->ID;

        switch($action){
            case "token" :
                try {
                    $client = new GuzzleHttp\Client(['base_uri' => 'https://zoom.us']);
                  
                    $response = $client->request('POST', '/oauth/token', [
                        "headers" => [
                            "Authorization" => "Basic ". base64_encode(ZOOM_KEYS["client_id"].':'.ZOOM_KEYS["client_secret"])
                        ],
                        'form_params' => [
                            "grant_type" => "authorization_code",
                            "code" => $_GET['code'],
                            "redirect_uri" => ZOOM_KEYS["redirect_uri"]
                        ],
                    ]);
                  
                    $token = json_decode($response->getBody()->getContents(), true);
                    $accessToken = json_encode($token);
                    update_user_meta( $user_id, "_zoomAccessToken", $accessToken );
                    echo "Access token inserted successfully.";
                } catch(Exception $e) {
                    echo $e->getMessage();
                }
            break;

            case "create" :
                createZoomMeeting($data);
            break;

            case "update" :
                updateZoomMeeting($user_id, $data["event_id"]);
            break;

            case "attendees" :
                getZoomMeetingAttendees($user_id, $data["event_id"]);
            break;

            case "delete" :
                deleteZoomMeeting($user_id, $data["event_id"]);
            break;

            case "addusers" :
                addZoomUsers();
                die;
            break;
            case "getuser":
            zoomGetUser();
            die;
            break;
        }
        if(isset($data["redirect_url"])){
           wp_redirect($data["redirect_url"]);
           die;
        }
    break;
}