<?php

class CRM_Gotowebinar_Form_Setting extends CRM_Core_Form
{
    public const WEBINAR_SETTING_GROUP = 'Webinar Preferences';

    /**
     * Function to pre processing.
     *
     * @return None
     */
    public function preProcess()
    {
        $session = CRM_Core_Session::singleton();
        $clientError = $session->get('autherror');
        $this->assign('clienterror', $clientError);

        if (isset($_GET['state']) && 'civicrmauthorize' == $_GET['state'] && isset($_GET['code'])) {
            $redirectUrl = CRM_Utils_System::url('civicrm/gotowebinar/settings', null, true, null, false, true);
            // We have the authorization code so get the access token
            $authorizationCode = $_GET['code'];
            $apiKey = CRM_Gotowebinar_Utils::getItem(self::WEBINAR_SETTING_GROUP, 'api_key');
            $clientSecret = CRM_Gotowebinar_Utils::getItem(self::WEBINAR_SETTING_GROUP, 'client_secret');

            $url = LOGMEIN_URL.'/oauth/token';
            // Setting up the curl fields
            $postFields = "grant_type=authorization_code&code={$authorizationCode}&redirect_uri=".$redirectUrl;
            // Encoding the api key and client secret along with the ':' symbol into the base64 format
            $string = $apiKey.':'.$clientSecret;
            $Base64EncodedCredentials = base64_encode($string);

            // Header fields are set
            $headers = [];
            $headers[] = 'Authorization: Basic '.$Base64EncodedCredentials;
            $headers[] = 'Content-Type: application/x-www-form-urlencoded';

            $response = CRM_Gotowebinar_Utils::apiCall($url, $headers, $postFields);
            $clientInfo = json_decode($response, true);

            if (isset($clientInfo['int_err_code']) && '' != $clientInfo['int_err_code']) {
                $session = CRM_Core_Session::singleton();
                $session->set('autherror', $clientInfo);
                CRM_Utils_System::redirect($redirectUrl);
            } elseif (isset($clientInfo['error'])) {
                CRM_Core_Error::statusBounce(
                    ts($clientInfo['error']),
                    $redirectUrl
                );
            } else {
                if ($clientInfo['access_token'] && $clientInfo['organizer_key']) {
                    CRM_Gotowebinar_Utils::storeAccessToken($clientInfo);
                    $session = CRM_Core_Session::singleton();
                    $session->set('autherror', null);
                    CRM_Utils_System::redirect($redirectUrl);
                    $upcomingWebinars = CRM_Gotowebinar_Form_Setting::findUpcomingWebinars();
                    if (isset($upcomingWebinars['int_err_code']) and '' != $upcomingWebinars['int_err_code']) {
                        $this->assign('error', $upcomingWebinars);
                    } else {
                        $this->assign('responseKey', true);
                        $this->assign('upcomingWebinars', $upcomingWebinars);
                    }
                }
            }
        }
    }

    /**
     * Function to actually build the form.
     *
     * @return None
     */
    public function buildQuickForm()
    {
        $this->add('text', 'api_key', ts('Consumer Key'),
            ['size' => 48], false);
        $this->add('text', 'client_secret', ts('Consumer Secret'), [
            'size' => 48, ], false);
        $this->add('text', 'email_address', ts('Deprecated - email'), [
            'size' => 48,      'disabled' => 'disabled'], false);
        $this->add('text', 'password', ts('Deprecated - password'), [
            'size' => 48, 'disabled' => 'disabled'], false);
        $this->add('text', 'gotowebinar_client_id', ts('Client ID'), ['size' => 48,  'disabled' => 'disabled'], false);
        $this->add('text', 'gotowebinar_client_secret', ts('Client Secret'), ['size' => 48,  'disabled' => 'disabled'], false);
        $this->add('textarea', 'gotowebinar_access_token', ts('Access token'), ['size' => 48,  'disabled' => 'disabled'], false);
        $this->add('text', 'gotowebinar_organizer_key', ts('Organizer Key'), ['size' => 48,  'disabled' => 'disabled'], false);

        $this->assign('initial', null);
        $this->assign('error');
        $this->assign('location');
        $status = CRM_Event_PseudoConstant::participantStatus(null, null, 'label');
        foreach ($status as $id => $Name) {
            $this->addElement('checkbox', "participant_status_id[$id]", null, $Name);
        }

        $accessToken = CRM_Gotowebinar_Utils::getItem(self::WEBINAR_SETTING_GROUP,
            'access_token', null, false
        );
        $organizerKey = CRM_Gotowebinar_Utils::getItem(self::WEBINAR_SETTING_GROUP,
            'organizer_key', null, false
        );

        $validToken = false;
        if ($accessToken && $organizerKey) {
            $validToken = true;
            $upcomingWebinars = CRM_Gotowebinar_Form_Setting::findUpcomingWebinars();
            // If Invalid token then refresh the accessToken and obtain the upcomingWebinars
            if (isset($upcomingWebinars['int_err_code']) && 'InvalidToken' == $upcomingWebinars['int_err_code']) {
                $validToken = CRM_Gotowebinar_Utils::refreshAccessToken();
                if ($validToken) {
                    $upcomingWebinars = CRM_Gotowebinar_Form_Setting::findUpcomingWebinars();
                }
            }
            if (isset($upcomingWebinars['int_err_code']) and '' != $upcomingWebinars['int_err_code']) {
                $this->assign('error', $upcomingWebinars);
            } else {
                // GK 12102017 - Check each webinar's fields and display warning, if any of the webinars required additonal required fields
                foreach ($upcomingWebinars as $key => $webinar) {
                    $registrationFields = CRM_Gotowebinar_Form_Setting::getRegistrationFields($webinar['webinarKey']);

                    if (!empty($registrationFields) && isset($registrationFields['fields'])) {
                        $numberOfFields = count($registrationFields['fields']);
                        // firstName, lastName, email are mandatory fields in Webinar. If number of fields exceeds 3, display warning to the users
                        $upcomingWebinars[$key]['warning'] = '';
                        if ($numberOfFields > 3) {
                            $upcomingWebinars[$key]['warning'] = 'This Webinar has more mandatory fields. Please note that participants will not be updated from CiviCRM for this webinar, unless the required fields are removed from this webinar!';
                        }
                    }
                }

                $this->assign('responseKey', true);
                $this->assign('upcomingWebinars', $upcomingWebinars);
                $buttons = [
                    [
                        'type' => 'submit',
                        'name' => ts('Save Status'),
                    ],
                ];
                $this->addButtons($buttons);
            }
        }
        // If Token is invalid, display Reconnect button
        if (!$validToken) {
            $buttons = [
                [
                    'type' => 'submit',
                    'name' => ts('Connect To My GoToWebinar'),
                ],
            ];
            // Add the Buttons.
            $this->addButtons($buttons);
            $this->assign('initial', true);
        }
    }

    public function setDefaultValues()
    {
        $defaults = $details = [];
        $status = CRM_Gotowebinar_Utils::getItem(self::WEBINAR_SETTING_GROUP, 'participant_status');
        $apiKey = CRM_Gotowebinar_Utils::getItem(self::WEBINAR_SETTING_GROUP, 'api_key');
        $clientSecret = CRM_Gotowebinar_Utils::getItem(self::WEBINAR_SETTING_GROUP, 'client_secret');
        $accessToken = CRM_Gotowebinar_Utils::getItem(self::WEBINAR_SETTING_GROUP,
            'access_token', null, false
        );
        $organizerKey = CRM_Gotowebinar_Utils::getItem(self::WEBINAR_SETTING_GROUP,
            'organizer_key', null, false
        );

        $client_id = Civi::settings()->get('gotowebinar_client_id');
        $client_secret = Civi::settings()->get('gotowebinar_client_secret');
        $access_token = Civi::settings()->get('gotowebinar_access_token');
        $organizer_key = Civi::settings()->get('gotowebinar_organizer_key');

        if ($apiKey) {
            $defaults['api_key'] = $apiKey;
        }
        if ($clientSecret) {
            $defaults['client_secret'] = $clientSecret;
        }
        if ($organizerKey) {
            $defaults['organizer_key'] = $organizerKey;
        }

        if ($status) {
            foreach ($status as $key => $id) {
                $defaults['participant_status_id['.$id.']'] = 1;
            }
        }

        $defaults['gotowebinar_client_id'] = empty($client_id) ? $apiKey : $client_id;
        $defaults['gotowebinar_client_secret'] = empty($client_secret) ? $clientSecret : $client_secret;
        $defaults['gotowebinar_organizer_key'] = empty($organizer_key) ? $organizerKey : $organizer_key;
        $defaults['gotowebinar_access_token'] = empty($access_token) ? $accessToken : $access_token;

        return $defaults;
    }

    /**
     * Function to process the form.
     *
     * @return None
     */
    public function postProcess()
    {
        // Store the submitted values in an array.
        $params = $this->controller->exportValues($this->_name);

        // Save the API Key & Save the Security Key
        if (CRM_Utils_Array::key('api_key', $params) && CRM_Utils_Array::key('client_secret', $params)) {
            $redirectUrl = CRM_Utils_System::url('civicrm/gotowebinar/settings', null, true, null, false, true);

            // Perform an authorization request, if the auth code is not set
            if (!isset($_GET['code'])) {
                // Storing the api_key and client_secret obtained from the form
                CRM_Gotowebinar_Utils::setItem($params['api_key'], self::WEBINAR_SETTING_GROUP, 'api_key');
                CRM_Gotowebinar_Utils::setItem($params['client_secret'], self::WEBINAR_SETTING_GROUP, 'client_secret');
                Civi::settings()->set('gotowebinar_client_id', $params['gotowebinar_client_id']);
                Civi::settings()->set('gotowebinar_client_secret', $params['gotowebinar_client_secret']);
                $authUrl = LOGMEIN_URL.'/oauth/authorize?response_type=code&state=civicrmauthorize&client_id='.$params['gotowebinar_client_id'].'&redirect_uri='.$redirectUrl;
                $authDestination = urldecode($authUrl);
                CRM_Utils_System::redirect($authDestination);
            }
        }

        // If gotowebinar was already connected, we introduced button called 'save status'
        if (isset($params['participant_status_id'])) {
            CRM_Gotowebinar_Utils::setItem(array_keys($params['participant_status_id']),
                self::WEBINAR_SETTING_GROUP, 'participant_status'
            );
        }
    }

    public static function findUpcomingWebinars()
    {
        $accessToken = CRM_Gotowebinar_Utils::getItem(self::WEBINAR_SETTING_GROUP,
            'access_token', null, false
        );
        $organizerKey = CRM_Gotowebinar_Utils::getItem(self::WEBINAR_SETTING_GROUP,
            'organizer_key', null, false
        );
        $url = WEBINAR_API_URL.'/G2W/rest/organizers/'.$organizerKey.'/upcomingWebinars';
        // Setting up the curl fields
        $headers[] = 'Authorization: OAuth oauth_token='.$accessToken;
        $headers[] = 'Content-type:application/json';
        $response = CRM_Gotowebinar_Utils::apiCall($url, $headers, null);
        $webinarDetails = json_decode(preg_replace('/("\w+"):(-?\d+(.\d+)?)/', '\1:"\2"', $response), true);

        return $webinarDetails;
    }

    // Function to get registration fields of a webinar
    public static function getRegistrationFields($webinarKey)
    {
        $response = [];
        if (!$webinarKey) {
            return $response;
        }

        // FIX ME :  These post request needs to be moved into function and called everywhere
        $accessToken = CRM_Gotowebinar_Utils::getItem(self::WEBINAR_SETTING_GROUP,
            'access_token', null, false
        );
        $organizerKey = CRM_Gotowebinar_Utils::getItem(self::WEBINAR_SETTING_GROUP,
            'organizer_key', null, false
        );

        $url = WEBINAR_API_URL.'/G2W/rest/organizers/'.$organizerKey.'/webinars/'.$webinarKey.'/registrants/fields';
        // Setting up the curl fields
        $headers[] = 'Authorization: OAuth oauth_token='.$accessToken;
        $headers[] = 'Content-type:application/json';
        $response = CRM_Gotowebinar_Utils::apiCall($url, $headers, null);
        $registrationFields = json_decode(preg_replace('/("\w+"):(-?\d+(.\d+)?)/', '\1:"\2"', $response), true);

        return $registrationFields;
    }
}
