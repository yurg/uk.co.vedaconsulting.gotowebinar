<?php

class CRM_Gotowebinar_Utils {

  /**
   * DM: Function to refresh and to obtain new access token
   *
   * @return validToken
   */
  public static function refreshAccessToken(){
   $validToken = FALSE;
   $gotowebinar_refresh_token = Civi::settings()->get('gotowebinar_refresh_token');
   if(!$gotowebinar_refresh_token){
      return NULL;
    }
    $gotowebinar_client_id = Civi::settings()->get('gotowebinar_client_id');
    $gotowebinar_client_secret = Civi::settings()->get('gotowebinar_client_secret');
    $string = $gotowebinar_client_id.":".$gotowebinar_client_secret;
    $Base64EncodedCredentials = base64_encode($string);
    $headers = array();
    $headers[] = "Authorization: Basic ".$Base64EncodedCredentials;
    $headers[] = 'Content-Type: application/x-www-form-urlencoded';
    $url = LOGMEIN_URL."/oauth/token";
    $postFields = "grant_type=refresh_token&refresh_token=".$gotowebinar_refresh_token;
    $response = CRM_Gotowebinar_Utils::apiCall($url, $headers, $postFields);
    $clientInfo = json_decode($response, TRUE);
    $validToken = CRM_Gotowebinar_Utils::storeAccessToken($clientInfo); 
     return $validToken;
  }  
  
  public static function obtainOrganizerKey(){
    $gotowebinar_access_token = Civi::settings()->get('gotowebinar_access_token');
    //Header fields are set
    $headers = [];
    $headers[] = "Authorization: Bearer " .  $gotowebinar_access_token;
    // @TODO
    $url = "https://api.getgo.com/identity/v1/Users/me";
    $response_json = CRM_Gotowebinar_Utils::apiCall($url, $headers);
    $response = json_decode($response_json);
    if(isset($response->id)) {
     $gotowebinar_access_token = Civi::settings()->set('gotowebinar_organizer_key', $response->id);
    }
    return $response; 
  }

  /**
   *
   * @return TRUE(updated) / FALSE(not updated)
   */
  public static function storeAccessToken($clientInfo){
    if(array_key_exists('access_token',$clientInfo) && array_key_exists('refresh_token',$clientInfo)){
    Civi::settings()->set('gotowebinar_access_token',  $clientInfo['access_token']);  
    Civi::settings()->set('gotowebinar_refresh_token', $clientInfo['refresh_token']);  
      return TRUE;
    }
    else{
      return FALSE;
    }
  }

  /**
  * curl
  */
  public static function apiCall(
    $url = NULL,
    $headers = NULL,
    $postFields = NULL
    ){
    if(!$url){
      return NULL;
    }
    set_time_limit(160);

    //curl initiation
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    if(!empty($postFields)){
      curl_setopt($curl, CURLOPT_POST, 1);
      curl_setopt($curl, CURLOPT_POSTFIELDS, $postFields);
    }
    if(!empty($headers)){
      curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    }

    //curl execution
    $apiResponse = curl_exec($curl);
    if (curl_errno($curl)) {
        echo 'Error:' . curl_error($curl);
    }
    curl_close($curl);
    return $apiResponse;
  }

  /**
   *Function to register a participant for a webinar event
   */
  public static function registerParticipant($webinar_key, $fields=NULL){
        $gotowebinar_access_token = Civi::settings()->get('gotowebinar_access_token');
        $gotowebinar_organizer_key = Civi::settings()->get('gotowebinar_organizer_key');

    $url = WEBINAR_API_URL."/G2W/rest/organizers/".$gotowebinar_organizer_key."/webinars/".$webinar_key."/registrants";
    $headers = [];
    $headers[] = "Authorization: OAuth oauth_token=".$gotowebinar_access_token;
    $headers[] = "Content-type:application/json";
    $result = CRM_Gotowebinar_Utils::apiCall($url, $headers, json_encode($fields));
    $response = json_decode($result, TRUE);
    return $response;
  }
}