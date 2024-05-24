<?php

use function GuzzleHttp\json_decode;

class CRM_Gotowebinar_Form_Setting extends CRM_Core_Form
{
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

            $client_id = Civi::settings()->get('gotowebinar_client_id');
            $client_secret = Civi::settings()->get('gotowebinar_client_secret');
            $url = LOGMEIN_URL.'/oauth/token';
            // Setting up the curl fields
            $postFields = "grant_type=authorization_code&code={$authorizationCode}&redirect_uri=".$redirectUrl;

            $string = $client_id.':'.$client_secret;
            $Base64EncodedCredentials = base64_encode($string);

            // Header fields are set
            $headers = [];
            $headers[] = 'Authorization: Basic '.$Base64EncodedCredentials;
            $headers[] = 'Content-Type: application/x-www-form-urlencoded';
            $response_json = CRM_Gotowebinar_Utils::apiCall($url, $headers, $postFields);
            $response = json_decode($response_json);
            if (isset($response->error)) {
                CRM_Core_Error::statusBounce(
                    ts($response->error.$response->error_description),
                    $redirectUrl
                );
            } else {
                if ($response->access_token) {
                    Civi::settings()->set('gotowebinar_access_token', $response->access_token);
                }
                if ($response->refresh_token) {
                    Civi::settings()->set('gotowebinar_refresh_token', $response->refresh_token);
                }
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

    /**
     * Function to actually build the form.
     *
     * @return None
     */
    public function buildQuickForm()
    {
        CRM_Gotowebinar_Utils::obtainOrganizerKey();
        $validToken = false;
        $this->assign('initial', null);
        $this->assign('location');
        $this->assign('error');
        // Form field
        $this->add('text', 'gotowebinar_client_id', ts('Client ID'), ['size' => 48, ], false);
        $this->add('text', 'gotowebinar_client_secret', ts('Client Secret'), ['size' => 48], false);
        $this->add('textarea', 'gotowebinar_access_token', ts('Access token'), ['size' => 128,], false);
        $this->add('textarea', 'gotowebinar_refresh_token', ts('Refresh token'), ['size' => 128,], false);
        $this->add('text', 'gotowebinar_organizer_key', ts('Organizer Key'), ['size' => 48], false);
        $participant_status_types = civicrm_api4('ParticipantStatusType', 'get', [
          'limit' => 25,
          'checkPermissions' => FALSE,
    ]); 
foreach ($participant_status_types as $pst_status) {
        $pst_id = $pst_status['id'];
        $pst_name = $pst_status['name'];
            $this->addElement('checkbox', "participant_status_id[$pst_id]", null, $pst_name);
        }
        $gotowebinar_access_token = Civi::settings()->get('gotowebinar_access_token');
        if (!$gotowebinar_access_token) {
            CRM_Gotowebinar_Utils::refreshAccessToken();
        } else {
            $upcomingWebinars = CRM_Gotowebinar_Form_Setting::findUpcomingWebinars();

            if (isset($upcomingWebinars['int_err_code'])) {
                $this->assign('error', implode(' : ', $upcomingWebinars));
            } else {
                $this->assign('upcomingWebinars', $upcomingWebinars);
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
            }
        }
        $buttons = [
            [
                'type' => 'submit',
                'name' => ts('Save'),
            ],
        ];
        $this->addButtons($buttons);
    }

    public function setDefaultValues()
    {
        $defaults = [];
        $status = Civi::settings()->get('gotowebinar_participant_status');
        if ($status) {
            foreach ($status as $key => $id) {
                $defaults['participant_status_id['.$id.']'] = 1;
            }
        }
        $defaults['gotowebinar_client_id'] = Civi::settings()->get('gotowebinar_client_id');
        $defaults['gotowebinar_client_secret'] = Civi::settings()->get('gotowebinar_client_secret');
        $defaults['gotowebinar_organizer_key'] = Civi::settings()->get('gotowebinar_organizer_key');
        $defaults['gotowebinar_access_token'] = Civi::settings()->get('gotowebinar_access_token');
        $defaults['gotowebinar_access_token'] = Civi::settings()->get('gotowebinar_refresh_token');

        return $defaults;
    }

    /**
     * Function to process the form.
     *
     * @return None
     */
    public function postProcess()
    {
        $params = $this->controller->exportValues($this->_name);
        $redirectUrl = CRM_Utils_System::url('civicrm/gotowebinar/settings', null, true, null, false, true);
        // Perform an authorization request, if the auth code is not set
        if (!isset($_GET['code'])) {
            $authUrl = LOGMEIN_URL.'/oauth/authorize?response_type=code&state=civicrmauthorize&client_id='.$params['gotowebinar_client_id'].'&redirect_uri='.$redirectUrl;
            $authDestination = urldecode($authUrl);
            CRM_Utils_System::redirect($authDestination);
        }
        Civi::settings()->set('gotowebinar_client_id', $params['gotowebinar_client_id']);
        Civi::settings()->set('gotowebinar_client_secret', $params['gotowebinar_client_secret']);
        Civi::settings()->set('gotowebinar_access_token', $params['code']);
        if (isset($params['participant_status_id'])) {
            Civi::settings()->set('gotowebinar_participant_status', array_keys($params['participant_status_id']));
        }
    }

    public static function findUpcomingWebinars()
    {
        $access_token = Civi::settings()->get('gotowebinar_access_token');
        $organizer_key = Civi::settings()->get('gotowebinar_organizer_key');
        $url = WEBINAR_API_URL.'/G2W/rest/organizers/'.$organizer_key.'/upcomingWebinars';
        $headers[] = 'Authorization: OAuth oauth_token='.$access_token;
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
        $access_token = Civi::settings()->get('gotowebinar_access_token');
        $organizer_key = Civi::settings()->get('gotowebinar_organizer_key');
        $url = WEBINAR_API_URL.'/G2W/rest/organizers/'.$organizer_key.'/webinars/'.$webinarKey.'/registrants/fields';
        // Setting up the curl fields
        $headers[] = 'Authorization: OAuth oauth_token='.$access_token;
        $headers[] = 'Content-type:application/json';
        $response = CRM_Gotowebinar_Utils::apiCall($url, $headers, null);
        $registrationFields = json_decode(preg_replace('/("\w+"):(-?\d+(.\d+)?)/', '\1:"\2"', $response), true);
        return $registrationFields;
    }
}
