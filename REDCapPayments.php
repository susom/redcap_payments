<?php

namespace Stanford\REDCapPayments;

class REDCapPayments extends \ExternalModules\AbstractExternalModule
{
    const REDCAP_URL = 'redcap_url';
    private $fieldMaps = [];

    private $record = null;

    private $eventID = null;


    public function __construct()
    {
        parent::__construct();
        // Other code to run when object is instantiated
    }

    public function processCallback()
    {
        $data[\REDCap::getRecordIdField()] = htmlspecialchars($_GET[\REDCap::getRecordIdField()]);
        $eventId = filter_var($_GET['event_id'], FILTER_VALIDATE_INT);
        // if langitutuals project.
        if (\REDCap::getEventNames($eventId)) {
            $data['event_name'] = \REDCap::getEventNames($eventId);
        }

        // process response
        $transaction = $_GET['transaction'];
        foreach ($this->getFieldMaps() as $fieldName => $fieldMap) {
            $data[$fieldMap['redcap-payment-field']] = htmlspecialchars($transaction[$fieldMap['payment-processor-attribute']]);
        }

        $response = \REDCap::saveData($this->getProjectId(), 'json', json_encode(array($data)));
        if (!empty($response['errors'])) {
            if (is_array($response['errors'])) {
                \REDCap::logEvent('Could not update REDCap Record' . implode(',', $response['errors']));
            } else {
                \REDCap::logEvent('Could not update REDCap Record' . $response['errors']);
            }
        } else {
            $url = $this->getCurrentURLToSession($data[\REDCap::getRecordIdField()], $eventId);
            header("Location: $url");
            // Ensure no further code is executed
            exit();
        }

    }

    public function includeFile($path)
    {
        include_once $path;
    }

    private function createURLHash($record, $eventID){
        return sha1(serialize([(string)$record, (string)$eventID]));
    }

    private function getCurrentURLToSession($record, $eventID)
    {
        $hash = $this->createURLHash($record, $eventID);
        return $_SESSION[$hash];
    }

    /**
     * save record return url to session to be used
     * @param $record
     * @param $eventID
     * @return void
     */
    private function saveCurrentURLToSession($record, $eventID)
    {
        $hash = $this->createURLHash($record, $eventID);
        $_SESSION[$hash] = $this->getCurrentURL();
    }

    public function redcap_data_entry_form_top($project_id, $record, $instrument, $event_id, $group_id, $repeat_instance)
    {
        if ($record) {

            $this->record = $record;
            $this->eventID = $event_id;
            $this->saveCurrentURLToSession($record, $event_id);
            $this->includeFile('pages/payments.php');
        }

    }

//    public function redcap_survey_complete(int $project_id, $record, string $instrument, int $event_id, $group_id, string $survey_hash, $response_id, $repeat_instance)
//    {
//        try {
//            $url = $this->getPaymentProcessorURL($record, urlencode($this->getUrl('pages/callback', true, true) . "&pid=" . $project_id . "&".\REDCap::getRecordIdField()."=" . $record . "&event_id=" . $event_id));
//
//            // Redirect User to $url
//            header("Location: $url");
//
//            // Ensure no further code is executed
//            exit;
//        } catch (\Exception $e) {
//            \REDCap::logEvent("ERROR", $e->getMessage());
//            return false;
//        }
//    }


    private function getPaymentProcessorURL($record, $redirectUrl = null)
    {
        $url = $this->getProjectSetting("payment-processor-url");
        if (!$url) {
            throw new \Exception("Payment processor URL is not configured");
        }

        $clientId = $this->getProjectSetting("payment-processor-client-id");
        $clientSecret = $this->getProjectSetting("payment-processor-client-secret");

        if ($clientId && $clientSecret) {
            $clientIdAttrName = $this->getProjectSetting("payment-processor-client-id-attribute-name");
            $clientSecretAttrName = $this->getProjectSetting("payment-processor-client-secret-attribute-name");

            $parsed_url = parse_url($url);

            // Check if the URL has a query
            if (isset($parsed_url['query'])) {
                // Parse the query string into an associative array
                parse_str($parsed_url['query'], $query_params);

                // Check if a specific attribute exists
                if (!array_key_exists($clientIdAttrName, $query_params)) {
                    $url .= "&$clientIdAttrName" . $clientId;
                }
                if (!array_key_exists($clientSecretAttrName, $query_params)) {
                    $url .= "&$clientSecretAttrName" . $clientSecret;
                }
            } else {
                // No query is added to URL lets add it.
                $url .= "?$clientIdAttrName" . $clientId;
                $url .= "&$clientSecretAttrName" . $clientSecret;
            }
        }

        $orderId = $this->getProjectSetting("payment-processor-order-id");
        $orderIdAttrName = $this->getProjectSetting("payment-processor-order-id-attribute-name");

        // if no order id is defined use a combination of project id and record id
        if (!$orderId) {
            $orderId = $this->getProjectId() . '-' . $record;
        }

        $url .= "&$orderIdAttrName=$orderId";

        $orderAmount = $this->getProjectSetting("payment-processor-order-amount");
        $orderAmountAttrName = $this->getProjectSetting("payment-processor-order-amount-attribute-name");
        if (!$orderAmount) {
            throw new \Exception("Payment processor order amount is not configured");
        }

        $url .= "&$orderAmountAttrName=$orderAmount";

        // check if enable redirect to redcap is enabled if so add the attribute
        $isEnabled = $this->getProjectSetting("enable-redirect-to-redcap");
        $isEnabledAttrName = $this->getProjectSetting("enable-redirect-to-redcap-attribute-name");
        $redirectUrlAttrName = $this->getProjectSetting("redirect-to-redcap-attribute-name");
        if ($isEnabled) {
            $url .= "&$isEnabledAttrName=y&$redirectUrlAttrName=$redirectUrl";
        }

        return $url;

    }

    private function getCurrentURL()
    {
        // Get the protocol (http or https)
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";

        // Get the host name
        $host = $_SERVER['HTTP_HOST'];

        // Get the request URI
        $request_uri = $_SERVER['REQUEST_URI'];

        // Combine them to get the full URL
        $current_url = $protocol . "://" . $host . $request_uri;

        return $current_url;
    }

    /**
     * @return array
     */
    public function getFieldMaps(): array
    {
        if (!$this->fieldMaps) {
            $this->setFieldMaps();
        }
        return $this->fieldMaps;
    }

    /**
     * @param array $fieldMaps
     */
    public function setFieldMaps(): void
    {
        $this->fieldMaps = $this->getSubSettings('fields-map', $this->getProjectId());;;
    }

}
