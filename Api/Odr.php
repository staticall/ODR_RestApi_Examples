<?php
require_once 'Odr/Exception.php';

class Api_Odr
{
    protected $_result = null;
    protected $_error  = null;

    protected $_headers = array();

    protected $_config = array();

    /**
     * In case URL will be changed in the future
     */
    const URL = 'http://odrv2.opendomainregistry.net/api2/';

    const METHOD_GET     = 'GET';
    const METHOD_POST    = 'POST';
    const METHOD_PUT     = 'PUT';
    const METHOD_DELETE  = 'DELETE';
    const METHOD_OPTIONS = 'OPTIONS';
    const DEFAULT_METHOD = self::METHOD_GET;

    const MESSAGE_CURL_ERROR_FOUND = 'cURL error catched';

    /**
     * Class constructor
     *
     * @param array $config Configuration data
     * @return Api_Odr
     */
    public function __construct(array $config = array())
    {
        if (!extension_loaded('curl')) {
            echo 'cURL extension required by this class. Check you php.ini';

            exit();
        }

        if (!empty($config) && is_array($config)) {
            $this->setConfig($config);
        }
    }

    /**
     * Change configuration data
     *
     * @param array $config Configuration array
     * @return Api_Odr
     *
     * @throws Api_Odr_Exception
     */
    public function setConfig(array $config = array())
    {
        if (empty($config)) {
            throw new Api_Odr_Exception('Config is not an array or empty');
        }

        foreach ($config as $key => $value) {
            $config[$key] = trim($value, ' /.,');
        }

        $this->_config = $config;

        return $this;
    }

    /**
     * Login procedure
     * At first, script tries to find out how signature is generated and after that actually tries to login
     * Is first step necessary? No. There is pretty slim chances that signature generation method will be changed in the future, but still, it wouldn't hurt
     *
     * @param string|null $apiKey    User's API Key
     * @param string|null $apiSecret User's API Secret
     * @return Api_Odr
     *
     * @throws Api_Odr_Exception
     */
    public function login($apiKey = null, $apiSecret = null)
    {
        $this->_execute('/info/user/login', self::METHOD_POST);

        if (!empty($this->_error)) {
            throw new Api_Odr_Exception(self::MESSAGE_CURL_ERROR_FOUND);
        }

        $result = $this->_result;

        if (!is_string($apiKey) || $apiKey === '') {
            $apiKey = $this->_config['api_key'];
        }

        if (!is_string($apiSecret) || $apiSecret === '') {
            $apiSecret = $this->_config['api_secret'];
        }

        $apiKey    = trim($apiKey);
        $apiSecret = trim($apiSecret);

        if ($apiKey === '' || $apiSecret === '') {
            throw new Api_Odr_Exception('You should defined `api_key` and `api_secret`');
        }

        $signatureRuleWrapper = $result['response']['fields']['signature']['signature_rule'];
        $signatureRule        = $result['response']['fields']['signature']['signature_rule_clear'];

        $wrapper = 'sha1';

        if (strpos($signatureRuleWrapper, '#SHA1(') === 0) {
            $wrapper = 'sha1';
        } elseif(strpos($signatureRuleWrapper, '#MD5(') === 0) {
            $wrapper = 'md5';
        }

        $timestamp = time();

        $r = array(
            '#API_KEY#'          => $apiKey,
            '#MD5(API_KEY)#'     => md5($apiKey),
            '#SHA1(API_KEY)#'    => sha1($apiKey),
            '#TIMESTAMP#'        => $timestamp,
            '#API_SECRET#'       => $apiSecret,
            '#MD5(API_SECRET)#'  => md5($apiSecret),
            '#SHA1(API_SECRET)#' => sha1($apiSecret),
        );

        $signature = str_replace(array_keys($r), array_values($r), $signatureRule);

        switch($wrapper) {
            case 'sha1':
                    $signature = sha1($signature);
                break;
            case 'md5':
                    $signature = md5($signature);
                break;
            default:
                break;
        }

        $data = array(
            'timestamp' => $timestamp,
            'api_key'   => $apiKey,
            'signature' => $signature,
        );

        $this->_execute('/user/login/', self::METHOD_POST, $data);

        $result = $this->_result;

        $this->_headers[$result['response']['header']] = $result['response']['access_token'];

        return $this;
    }

    /**
     * Return list of user's domains
     *
     * @return Api_Odr
     */
    public function getDomains()
    {
        $this->_execute('/domain/', self::METHOD_GET);

        return $this;
    }

    /**
     * Check if domain is available or not
     *
     * @param string|integer $domain Either ID or domain name
     * @return Api_Odr
     *
     * @throws Api_Odr_Exception
     */
    public function checkDomain($domain)
    {
        if (!is_numeric($domain) && (!is_string($domain) || $domain === '')) {
            throw new Api_Odr_Exception('Domain must be a string, but you give us a '. gettype($domain));
        }

        $domain = trim($domain, ' /.');

        if ($domain === '') {
            throw new Api_Odr_Exception('Domain name is required for this operation');
        }

        $this->_execute('/domain/available/'. $domain .'/', self::METHOD_GET);

        return $this;
    }

    /**
     * Update existing domain with new data
     *
     * @param string|integer $id   Either ID or domain name
     * @param array          $data Data for update
     * @return Api_Odr
     */
    public function updateDomain($id, array $data = array())
    {
        $this->_execute('/domain/'. trim($id) .'/', self::METHOD_POST, $data);

        return $this;
    }

    /**
     * Finds contact by name
     *
     * @param string $name Name of the contact
     * @return Api_Odr
     */
    public function findContact($name)
    {
        $name = urlencode(trim($name));

        $this->_execute('/contact/search/'. $name .'/', self::METHOD_GET);

        return $this;
    }

    /**
     * Finds nameserver by name
     *
     * @param string $name Name of the nameserver
     * @return Api_Odr
     */
    public function findNameserver($name)
    {
        $name = urlencode(trim($name));

        $this->_execute('/nameserver/search/'. $name .'/', self::METHOD_GET);

        return $this;
    }

    /**
     * Transfers domain from one user to another
     *
     * @param string $id   Domain ID or domain name
     * @param array  $data Data to update
     * @return Api_Odr
     *
     * @todo Not implemented in the API yet, but will be
     */
    public function transferDomain($id, array $data = array())
    {
        $this->_execute('/domain/'. trim($id) .'/transfer/', self::METHOD_PUT, $data);

        return $this;
    }

    /**
     * Return list of user's contacts
     *
     * @return Api_Odr
     */
    public function getContacts()
    {
        $this->_execute('/contact/', self::METHOD_GET);

        return $this;
    }

    /**
     * Get information about contact
     *
     * @param int $contactId
     * @return Api_Odr
     *
     * @throws Api_Odr_Exception
     */
    public function getContact($contactId)
    {
        if (!is_numeric($contactId)) {
            throw new Api_Odr_Exception('Contact ID must be numeric');
        }

        $contactId = (int)$contactId;

        if ($contactId <= 0) {
            throw new Api_Odr_Exception('Contact ID must be a positive number');
        }

        $this->_execute('/contact/'. $contactId .'/', self::METHOD_GET);

        return $this;
    }

    /**
     * Get information about contact role
     *
     * @param int         $contactRoleId
     * @param string|null $roleType
     * @return Api_Odr
     *
     * @throws Api_Odr_Exception
     */
    public function getContactRole($contactRoleId, $roleType = null)
    {
        if (!is_numeric($contactRoleId)) {
            throw new Api_Odr_Exception('Contact Role ID must be numeric');
        }

        $contactRoleId = (int)$contactRoleId;

        if ($contactRoleId <= 0) {
            throw new Api_Odr_Exception('Contact Role ID must be a positive number');
        }

        $url = '/contact-role/'. $contactRoleId;

        if ($roleType !== null) {
            $url .= '/'. $roleType;
        }

        $this->_execute($url .'/', self::METHOD_GET);

        return $this;
    }

    /**
     * Creates contact from passed data
     *
     * @param array $data Data for contact. If data is empty, $_REQUEST will be used
     * @return Api_Odr
     */
    public function createContact(array $data = array())
    {
        // Just in case someone decides to pass data as part of $_REQUEST
        if (empty($data)) {
            $data = $_REQUEST;
        }

        $this->_execute('/contact/', self::METHOD_POST, $data);

        return $this;
    }

    /**
     * Returns array of contact roles for passed contact ID
     *
     * @param int $contactId Contact ID, for which roles are requested
     * @return Api_Odr
     *
     * @throws Api_Odr_Exception
     */
    public function getContactRoles($contactId)
    {
        if (!is_numeric($contactId)) {
            throw new Api_Odr_Exception('Contact ID must be numeric');
        }

        $contactId = (int)$contactId;

        if ($contactId <= 0) {
            throw new Api_Odr_Exception('Contact ID must be a positive number');
        }

        $this->_execute('/contact-role/'. $contactId .'/', self::METHOD_GET);

        return $this;
    }

    /**
     * Creates new contact role for existing contact
     *
     * @param integer $contactId Contact ID. Created role will be associated with this contact
     * @param string  $roleId    Role of contact role, a. k. a., contact role for NL or BE, or EU, etc.
     * @param array   $data      Data for new contact role. If empty, $_REQUEST will be used
     * @return Api_Odr
     *
     * @throws Api_Odr_Exception
     */
    public function createContactRole($contactId, $roleId, array $data = array())
    {
        if (!is_numeric($contactId)) {
            throw new Api_Odr_Exception('Contact ID must be numeric');
        }

        $contactId = (int)$contactId;

        if ($contactId <= 0) {
            throw new Api_Odr_Exception('Contact ID must be a positive number');
        }

        $roleId = trim($roleId);

        if (!is_string($roleId) || $roleId === '') {
            throw new Api_Odr_Exception('Role ID must be a string and not empty');
        }

        if (empty($data)) {
            $data = $_REQUEST;
        }

        $this->_execute('/contact-role/'. $contactId .'/'. $roleId .'/', self::METHOD_POST, $data);

        return $this;
    }

    /**
     * Deletes existing contact role for existing contact
     *
     * @param integer $contactId Contact ID
     * @param string  $roleId    Role of contact role, a. k. a., contact role for NL or BE, or EU, etc.
     * @return Api_Odr
     *
     * @throws Api_Odr_Exception
     */
    public function deleteContactRole($contactId, $roleId)
    {
        if (!is_numeric($contactId)) {
            throw new Api_Odr_Exception('Contact ID must be numeric');
        }

        $contactId = (int)$contactId;

        if ($contactId <= 0) {
            throw new Api_Odr_Exception('Contact ID must be a positive number');
        }

        $roleId = trim($roleId);

        if (!is_string($roleId) || $roleId === '') {
            throw new Api_Odr_Exception('Role ID must be a string and not empty');
        }

        $this->_execute('/contact-role/'. $contactId .'/'. $roleId .'/', self::METHOD_DELETE);

        return $this;
    }

    /**
     * Returns list of user's nameservers
     *
     * @return Api_Odr
     */
    public function getNameservers()
    {
        $this->_execute('/nameserver/', self::METHOD_GET);

        return $this;
    }

    public function getNameserver($nameserverId)
    {
        if (!is_numeric($nameserverId)) {
            throw new Api_Odr_Exception('Nameserver ID must be numeric');
        }

        $nameserverId = (int)$nameserverId;

        if ($nameserverId <= 0) {
            throw new Api_Odr_Exception('Nameserver ID must be a positive number');
        }

        $this->_execute('/nameserver/'. $nameserverId .'/', self::METHOD_GET);

        return $this;
    }

    public function createNameserver(array $data = array())
    {
        if (empty($data)) {
            $data = $_REQUEST;
        }

        $this->_execute('/nameserver/', self::METHOD_POST, $data);

        return $this;
    }

    public function getNameserverRoles($nameserverId)
    {
        if (!is_numeric($nameserverId)) {
            throw new Api_Odr_Exception('Nameserver ID must be numeric');
        }

        $nameserverId = (int)$nameserverId;

        if ($nameserverId <= 0) {
            throw new Api_Odr_Exception('Nameserver ID must be a positive number');
        }

        $this->_execute('/nameserver-role/'. $nameserverId .'/', self::METHOD_GET);

        return $this;
    }

    public function createNameserverRole($nameserverId, $roleId, array $data = array())
    {
        if (!is_numeric($nameserverId)) {
            throw new Api_Odr_Exception('Nameserver ID must be numeric');
        }

        $nameserverId = (int)$nameserverId;

        if ($nameserverId <= 0) {
            throw new Api_Odr_Exception('Nameserver ID must be a positive number');
        }

        $roleId = trim($roleId);

        if (!is_string($roleId) || $roleId === '') {
            throw new Api_Odr_Exception('Role ID must be a string and not empty');
        }

        if (empty($data)) {
            $data = $_REQUEST;
        }

        $this->_execute('/nameserver-role/'. $nameserverId .'/'. $roleId .'/', self::METHOD_POST, $data);

        return $this;
    }

    /**
     * Delete existing namserver role
     *
     * @param integer $nameserverId ID of nameserver to delete role from
     * @param string  $roleId       Role name, like 'nl_common' or 'eu_onsite'
     * @return Api_Odr
     *
     * @throws Api_Odr_Exception
     */
    public function deleteNameserverRole($nameserverId, $roleId)
    {
        if (!is_numeric($nameserverId)) {
            throw new Api_Odr_Exception('Nameserver ID must be numeric');
        }

        $nameserverId = (int)$nameserverId;

        if ($nameserverId <= 0) {
            throw new Api_Odr_Exception('Nameserver ID must be a positive number');
        }

        $roleId = trim($roleId);

        if ($roleId === '') {
            throw new Api_Odr_Exception('Role ID must be a string and not empty');
        }

        $this->_execute('/nameserver-role/'. $nameserverId .'/'. $roleId .'/', self::METHOD_DELETE);

        return $this;
    }

    /**
     * Registers new domain
     *
     * @param string|array $domainName Either domain name as string or whole request data as array (must have 'domain_name' key)
     * @param array        $data       Data for new domain. Only usable if $domainName is a string
     * @return Api_Odr
     *
     * @throws Api_Odr_Exception
     */
    public function registerDomain($domainName, array $data = array())
    {
        if (empty($data) && is_array($domainName)) {
            $data   = $domainName;
            $domainName = null;
        }

        if (empty($data)) {
            $data = $_REQUEST;
        }

        if (!empty($data['contact_name']) && empty($data['handle_contact'])) {
            $dataContact = $this->findContact($data['contact_name'])->getResult();

            $cont = reset($dataContact);

            if (!empty($cont['contact_id'])) {
                $data['handle_contact'] = $cont['contact_id'];
            }
        }

        if (!empty($data['nameserver_name']) && empty($data['handle_ns'])) {
            $dataNameserver = $this->findNameserver($data['nameserver_name'])->getResult();

            $ns = reset($dataNameserver);

            if (!empty($ns['nameserver_id'])) {
                $data['handle_ns'] = $ns['nameserver_id'];
            }
        }

        if ((!is_string($domainName) || $domainName === '') && array_key_exists('domain_name', $data) === false) {
            throw new Api_Odr_Exception('No domain name defined');
        }

        if (empty($data['handle_id'])) {
            throw new Api_Odr_Exception('No handle defined');
        }

        if (!is_string($domainName) || $domainName === '') {
            $domainName = $data['domain_name'];
        }

        $this->_execute('/domain/'. $domainName .'/', self::METHOD_POST, $data);

        return $this;
    }

    /**
     * Get information about operation, including price and required fields
     *
     * @param string $what   About what you want to know information about. Either URL or a string
     * @param mixed  $method If $what is an URL, then method should be a string. If not, then $method might be an array (instead of data) or null
     * @param array  $data   Additional data for request
     * @return Api_Odr
     *
     * @throws Api_Odr_Exception
     */
    public function info($what, $method = null, array $data = array())
    {
        if (!is_string($what) || $what === '') {
            throw new Api_Odr_Exception('I don\'t understand about what you want to get information about');
        }

        $what = strtolower(trim($what));

        return $this->custom('/info/'. ltrim($what, '/'), $method);
    }

    public function infoRegisterDomain($domainName)
    {
        return $this->info('/domain/'. $domainName .'/', self::METHOD_POST);
    }

    /**
     * Request to any custom API URL
     * Works as shorthand for $this->_execute() function
     *
     * @param string $url    Request URL
     * @param string $method cURL request method
     * @param array  $data   Data for request
     * @return Api_Odr
     */
    public function custom($url, $method = self::DEFAULT_METHOD, array $data = array())
    {
        $this->_execute($url, $method, $data);

        if (!empty($this->_error)) {
            return array(
                'is_error'  => true,
                'error_msg' => $this->_error,
            );
        }

        if (!empty($this->_result)) {
            return array(
                'is_error' => false,
                'data'     => $this->_result,
            );
        }

        return $this;
    }

    /**
     * Executes cURL request and return result and error
     *
     * @param string $url    Where send request
     * @param string $method What method should be called
     * @param array  $data   Additional data to send
     * @return Api_Odr
     *
     * @throws Api_Odr_Exception
     *
     * @protected
     */
    protected function _execute($url = '', $method = self::DEFAULT_METHOD, array $data = array())
    {
        if (!is_string($method) || $method === '') {
            $method = self::DEFAULT_METHOD;
        }

        $method = strtoupper($method);

        if (!is_string($url) || $url === '') {
            $url = self::URL;
        }

        if (strpos($url, '/') === 0) {
            $url = self::URL . ltrim($url, '/');
        }

        if (strpos($url, self::URL) !== 0) {
            throw new Api_Odr_Exception('Wrong host for URL ('. $url .')');
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST,  $method);

        if (!empty($data)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        if (!empty($this->_headers)) {
            $headers = array();

            foreach ($this->_headers as $k => $v) {
                $headers[] = $k .': '. $v;
            }

            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        $result = curl_exec($ch);

        $this->_error = curl_error($ch);

        if (empty($this->_error)) {
            $this->_result = json_decode($result, true);
        }

        curl_close($ch);

        // Too much request at a time can ban us
        sleep(1);

        if (!empty($this->_error)) {
            throw new Api_Odr_Exception(self::MESSAGE_CURL_ERROR_FOUND);
        }

        return $this;
    }

    /**
     * Return request result
     *
     * @return mixed
     */
    public function getResult()
    {
        return $this->_result;
    }

    /**
     * Return possible cURL error
     *
     * @return mixed
     */
    public function getError()
    {
        return $this->_error;
    }
}