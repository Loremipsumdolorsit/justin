<?php

/**
 * JustIn API Class.
 * Delivery service.
 *
 * @author webvision
 *
 * @see https://github.com/Loremipsumdolorsit/justin
 *
 * @license MIT
 */
class JustIn
{
    /**
     * API login
     *
     * @var string
     */
    protected $login;

    /**
     * API pass
     *
     * @var string
     */
    protected $pass;

    /**
     * API account key
     *
     * @var string
     */
    protected $key;

    /**
     * Response result lang
     *
     * @var string
     */
    protected $lang = 'UA';

    /**
     * Request protocol
     *
     * @var string
     */
    protected $apiProtocol = 'http://';

    /**
     * API host
     *
     * @var string
     */
    protected $apiHost = '195.201.72.186';

    /**
     * API urn for 'catalog' and 'request' types of request
     *
     * @var string
     */
    protected $apiUrnRequest = '/justin_pms_test/hs/v2/runRequest';

    /**
     * API urn for make order request
     *
     * @var string
     */
    protected $apiUrnOrder = '/api_pms/hs/api/v1/documents/orders';

    /**
     * Static http auth login
     *
     * @var string
     */
    protected $apiStaticBasicLogin = 'Exchange';

    /**
     * Static http auth pass
     *
     * @var string
     */
    protected $apiStaticBasicPass = 'Exchange';

    /**
     * Request method
     *
     * @var string
     */
    protected $apiConnectionMethod = 'curl';

    /**
     * Enable/disable throwing errors
     *
     * @var bool
     */
    protected $throwErrors = false;

    /**
     * JustIn constructor.
     *
     * @param string $login
     * @param string $pass
     * @param string $key
     * @param string $lang
     * @param bool $throwErrors
     * @param string $connectionType
     */
    public function __construct($login, $pass, $key = '', $lang = 'UA', $throwErrors = false, $connectionType = 'curl')
    {
        $this->login = $login;
        $this->pass = $pass;
        $this->key = $key;
        $this->lang = strtoupper($lang);
        $this->throwErrors = (boolean)$throwErrors;
        $this->apiConnectionMethod = $connectionType;
    }

    /**
     * Create signature
     *
     * @return string
     */
    private function getSign()
    {
        return sha1($this->pass . ':' . date('Y-m-d'));
    }

    /**
     * Prepare data to request
     *
     * @param array $data
     * @return string
     */
    private function pack(array $data)
    {
        return json_encode($data);
    }

    /**
     * Prepare data from response
     *
     * @param $json
     * @return mixed
     */
    private function unpack($json)
    {
        return json_decode($json, true);
    }

    /**
     * Choose request method and execute send function
     *
     * @param $urn
     * @param $data
     * @return mixed
     */
    private function sendRequest($urn, $data)
    {
        $apiUri = $this->apiProtocol . $this->apiHost . $urn;

        if ($this->apiConnectionMethod === 'curl' && function_exists('curl_init')) {
            return $this->sendRequestCurl($apiUri, $data);
        } else {
            return $this->sendRequestHttp($apiUri, $data);
        }
    }

    /**
     * Send request via 'curl'
     *
     * @param $apiUri
     * @param $data
     * @return mixed
     */
    private function sendRequestCurl($apiUri, $data)
    {
        $curl = curl_init();

        $headers = [
            'Accept: application/json',
            'Content-Type: application/json'
        ];

        curl_setopt($curl, CURLOPT_URL, $apiUri);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_NOBODY, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_USERPWD, "{$this->apiStaticBasicLogin}:{$this->apiStaticBasicPass}");
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($curl, CURLOPT_POST, count($data));
        curl_setopt($curl, CURLOPT_POSTFIELDS, $this->pack($data));

        $result = curl_exec($curl);
        curl_close($curl);

        return $this->unpack($result);
    }

    /**
     * Send request via 'file_get_contents' function
     *
     * @param $apiUri
     * @param $data
     * @return mixed
     */
    private function sendRequestHttp($apiUri, $data)
    {
        $auth = 'Authorization: Basic ' . base64_encode($this->apiStaticBasicLogin . ':' . $this->apiStaticBasicPass);

        $options = [
            'http' => [
                'method' => 'POST',
                'header' => "Content-type: application/json\r\n" . $auth,
                'content' => $this->pack($data),
            ]
        ];

        $context = stream_context_create($options);
        $result = file_get_contents($apiUri, false, $context);

        return $this->unpack($result);
    }

    /**
     * Common request data template
     *
     * @param $type
     * @param $methodName
     * @param string $requestType
     * @param array $filter
     * @param int $top
     * @param array $params
     * @return array
     */
    private function prepareRequestDataTemplate($type, $methodName, $requestType = 'getData', $filter = [], $top = 0, $params = [])
    {
        return [
            'keyAccount' => $this->login,
            'sign' => $this->getSign(),
            'request' => $requestType,
            'type' => $type,
            'name' => $methodName,
            'language' => $this->lang,
            'params' => $params,
            'filter' => $filter,
            'TOP' => $top,
        ];
    }

    /**
     * Send request to get account key for user
     *
     * @return mixed
     */
    public function getApiKey()
    {
        return $this->sendRequest(
            $this->apiUrnRequest,
            $this->prepareRequestDataTemplate(
                'infoData',
                'getSenderUUID',
                'getData',
                [
                    'name' => 'login',
                    'comparison' => 'equal',
                    'leftValue' => $this->login
                ],
                1
            )
        );
    }

    /**
     * Request regions data
     * Filter
     * [
     *      'name' => 'code | descr', filtered field name
     *      'comparison' => 'equal | not | less | more | in | between | not in | less or equal | more or equal | like',
     *      'leftvalue' => '', left limit
     *      'rightvalue' => '', right limit
     * ]
     *
     * @param array $filter
     * @param int $top
     * @return mixed
     */
    public function getRegions($filter = [], $top = 0)
    {
        return $this->sendRequest(
            $this->apiUrnRequest,
            $this->prepareRequestDataTemplate(
                'catalog',
                'cat_Region',
                'getData',
                $filter,
                $top
            )
        );
    }

    /**
     * Request areas regions data
     * Filter
     * [
     *      'name' => 'code | descr', filtered field name
     *      'comparison' => 'equal | not | less | more | in | between | not in | less or equal | more or equal | like',
     *      'leftvalue' => '', left limit
     *      'rightvalue' => '', right limit
     * ]
     *
     * @param $filter
     * @param int $top
     * @return mixed
     */
    public function getAreasRegion($filter, $top = 0)
    {
        return $this->sendRequest(
            $this->apiUrnRequest,
            $this->prepareRequestDataTemplate(
                'catalog',
                'cat_areasRegion',
                'getData',
                $filter,
                $top
            )
        );
    }

    /**
     * Request cities data
     * Filter
     * [
     *      'name' => 'code | descr', filtered field name
     *      'comparison' => 'equal | not | less | more | in | between | not in | less or equal | more or equal | like',
     *      'leftvalue' => '', left limit
     *      'rightvalue' => '', right limit
     * ]
     *
     * @param $filter
     * @param int $top
     * @return mixed
     */
    public function getCities($filter, $top = 0)
    {
        return $this->sendRequest(
            $this->apiUrnRequest,
            $this->prepareRequestDataTemplate(
                'catalog',
                'cat_Cities',
                'getData',
                $filter,
                $top
            )
        );
    }

    /**
     * Request city regions data
     * Filter
     * [
     *      'name' => 'code | descr', filtered field name
     *      'comparison' => 'equal | not | less | more | in | between | not in | less or equal | more or equal | like',
     *      'leftvalue' => '', left limit
     *      'rightvalue' => '', right limit
     * ]
     *
     * @param $filter
     * @param int $top
     * @return mixed
     */
    public function getCityRegions($filter, $top = 0)
    {
        return $this->sendRequest(
            $this->apiUrnRequest,
            $this->prepareRequestDataTemplate(
                'catalog',
                'cat_cityRegions',
                'getData',
                $filter,
                $top
            )
        );
    }

    /**
     * Request city streets data
     * Filter
     * [
     *      'name' => 'code | descr', filtered field name
     *      'comparison' => 'equal | not | less | more | in | between | not in | less or equal | more or equal | like',
     *      'leftvalue' => '', left limit
     *      'rightvalue' => '', right limit
     * ]
     *
     * @param $filter
     * @param int $top
     * @return mixed
     */
    public function getCityStreets($filter, $top = 0)
    {
        return $this->sendRequest(
            $this->apiUrnRequest,
            $this->prepareRequestDataTemplate(
                'catalog',
                'cat_cityStreets',
                'getData',
                $filter,
                $top
            )
        );
    }

    /**
     * Request departments data
     * Filter
     * [
     *      'name' => 'code | descr', filtered field name
     *      'comparison' => 'equal | not | less | more | in | between | not in | less or equal | more or equal | like',
     *      'leftvalue' => '', left limit
     *      'rightvalue' => '', right limit
     * ]
     *
     * @param $filter
     * @param int $top
     * @return mixed
     */
    public function getDepartments($filter, $top = 0)
    {
        return $this->sendRequest(
            $this->apiUrnRequest,
            $this->prepareRequestDataTemplate(
                'request',
                'req_DepartmentsLang',
                'getData',
                $filter,
                $top,
                ['language' => $this->lang]
            )
        );
    }

    /**
     * Sending request to make order (create waybill)
     *
     * @param array $data Order data:
     *                    number => Int, internal order id
     *                    date => String, date of order create format('Ymd' | 'Y-m-d')
     *                    sender_city_id => String, external city id, use $this->getCities()
     *                    sender_company => String, sender company name
     *                    sender_contact => String, sender full name
     *                    sender_phone => String, sender phone
     *                    sender_pick_up_address => String, address fo cargo pickup
     *                    pick_up_is_required => Boolean, is need cargo pickup on address
     *                    sender_branch => String, sender external department number, use $this->getDepartments()
     *                    receiver => String, recipient full name
     *                    receiver_contact => String, recipient full name (if another person receive cargo)
     *                    receiver_phone => String, recipient phone number
     *                    count_cargo_places => Int, count places
     *                    branch => String, recipient extend department number, use $this->getDepartments()
     *                    weight => Float, cargo weight
     *                    volume => Float, cargo volume
     *                    declared_cost => Int, declared cargo price
     *                    delivery_amount => Int, money redelivery (param delivery_payment_is_required)
     *                    redelivery_amount => Int, redelivery tax
     *                    order_amount => Int, all sum
     *                    redelivery_payment_is_required => Boolean, is need redelivery tax
     *                    redelivery_payment_payer => Int, tax payer (0 - sender | 1 - recipient)
     *                    delivery_payment_is_required => Boolean, is delivery payment required
     *                    delivery_payment_payer => Int, delivery payer (0 - sender | 1 - recipient)
     *                    order_payment_is_required => Boolean, is order payment required
     *
     * @return mixed
     */
    public function createOrder($data)
    {
        return $this->sendRequest(
            $this->apiUrnOrder,
            [
                'api_key' => $this->key ?: $this->getApiKey(),
                'data' => $data
            ]
        );
    }

    /**
     * Request statuses list data
     * Filter
     * [
     *      'name' => 'code | descr', filtered field name
     *      'comparison' => 'equal | not | less | more | in | between | not in | less or equal | more or equal | like',
     *      'leftvalue' => '', left limit
     *      'rightvalue' => '', right limit
     * ]
     *
     * @param $filter
     * @param int $top
     * @return mixed
     */
    public function getStatusesList($filter, $top = 0)
    {
        return $this->sendRequest(
            $this->apiUrnRequest,
            $this->prepareRequestDataTemplate(
                'catalog',
                'orderStatuses',
                'getData',
                $filter,
                $top
            )
        );
    }

    /**
     * Request order status history data
     * Filter
     * [
     *      'name' => 'code | descr', filtered field name
     *      'comparison' => 'equal | not | less | more | in | between | not in | less or equal | more or equal | like',
     *      'leftvalue' => '', left limit
     *      'rightvalue' => '', right limit
     * ]
     *
     * @param $filter
     * @param int $top
     * @return mixed
     */
    public function getOrderStatusesHistory($filter, $top = 0)
    {
        return $this->sendRequest(
            $this->apiUrnRequest,
            $this->prepareRequestDataTemplate(
                'request',
                'getOrderStatusesHistoryF',
                'getData',
                $filter,
                $top
            )
        );
    }

    /**
     * Get link to order stickers with sender/recipient name
     *
     * @param $orderNumber
     * @return string
     */
    public function getOrderStickerLink($orderNumber)
    {
        return $this->apiProtocol . $this->apiHost
            . "/pms/hs/api/v1/printSticker/order?order_number={$orderNumber}&api_key={$this->key}";
    }

    /**
     * Get link to order stickers with sender/recipient contact
     *
     * @param $orderNumber
     * @return string
     */
    public function getOrderStickerLinkWithContact($orderNumber)
    {
        return $this->apiProtocol . $this->apiHost
            . "/pms/hs/api/v1/printStickerWithContactPerson/order?order_number={$orderNumber}&api_key={$this->key}";
    }
}
