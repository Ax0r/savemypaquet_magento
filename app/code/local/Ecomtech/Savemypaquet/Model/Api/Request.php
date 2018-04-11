<?php

/**
 * @category    Ecomtech
 * @package     Ecomtech_Savemypaquet
 * @author      Savemypaquet ( http://www.savemypaquet.com)
 * @developer   Ahmed MAHI <ahmed@mahi.ma> (http://ahmedmahi.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Ecomtech_Savemypaquet_Model_Api_Request
{
    protected $ch;

    protected $request;

    protected $response;

    protected $curl_headers;

    public function __construct($args)
    {
        $this->request = new stdClass();

        $this->request->headers = array(
            'Accept: application/json;  text/html',
            'Content-Type: application/json',
            'User-Agent: Savemypaquet API Client-PHP/' . Ecomtech_Savemypaquet_Model_Api::VERSION,
        );

        // GET, POST, PUT, DELETE, etc.
        $this->request->method = $args['method'];

        $this->request->url = rtrim($args['url'], '/');

        $this->request->params = array();
        $is_auth               = $args['is_auth'];
        $this->request->data   = $args['data'];
        if ($is_auth) {
            // auth
            $auth                = Mage::getModel('savemypaquet/api_authentication', $args);
            $this->request->data = array_merge((array) $this->request->data, $auth->get_auth_params($this->request->data));
        }
        // optional cURL opts
        $timeout    = (int) $args['options']['timeout'];
        $ssl_verify = (bool) $args['options']['ssl_verify'] ? 2 : false;

        $this->ch = curl_init();

        // default cURL opts
        curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, $ssl_verify);
        curl_setopt($this->ch, CURLOPT_SSL_VERIFYHOST, $ssl_verify);
        curl_setopt($this->ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($this->ch, CURLOPT_TIMEOUT, (int) $timeout);
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);

        // set request headers
        curl_setopt($this->ch, CURLOPT_HTTPHEADER, $this->request->headers);

        // save response headers
        curl_setopt($this->ch, CURLOPT_HEADERFUNCTION, array($this, 'curl_stream_headers'));

        // set request method and data
        switch ($this->request->method) {

            case 'GET':
                $this->request->body   = null;
                $this->request->params = (array) $this->request->data;
                break;

            case 'PUT':
                $this->request->body = json_encode($this->request->data);
                curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                curl_setopt($this->ch, CURLOPT_POSTFIELDS, $this->request->body);
                break;

            case 'POST':
                $this->request->body = json_encode($this->request->data);
                curl_setopt($this->ch, CURLOPT_POST, true);
                curl_setopt($this->ch, CURLOPT_POSTFIELDS, $this->request->body);
                break;

            case 'DELETE':
                $this->request->body   = null;
                $this->request->params = (array) $this->request->data;
                curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
                break;
        }
        // set request url
        curl_setopt($this->ch, CURLOPT_URL, $this->build_url());
    }

    protected function build_url()
    {
        return $this->request->url . (!empty($this->request->params) ? '?' . http_build_query($this->request->params) : '');
    }

    public function dispatch($fileurl=false)
    {
        $this->response = new stdClass();

        // blank headers
        $this->curl_headers = '';

        $start_time = microtime(true);

        // send request + save raw response body
        $this->response->body = curl_exec($this->ch);

        // request duration
        $this->request->duration = round(microtime(true) - $start_time, 5);

        // response code
        $this->response->code = curl_getinfo($this->ch, CURLINFO_HTTP_CODE);

        // response headers
        $this->response->headers = $this->get_response_headers();

        curl_close($this->ch);

        $parsed_response = $this->get_parsed_response($this->response->body);

        // check for invalid JSON
        if (null === $parsed_response) {
            if ($fileurl) {
                $parsed_response=$this->response->body;
            } else {
                throw new Ecomtech_Savemypaquet_Model_Api_Exceptions(sprintf('Invalid JSON returned for %s.', $this->request->url), $this->response->code, $this->request, $this->response);
            }
        }
        // any non-200/201/202 response code indicates an error
        if (!in_array($this->response->code, array('200', '201', '202'))) {
            if (isset($parsed_response['errorcode'])) {
                $mser = json_encode($parsed_response['error']) . ' ( errorcode:' . $parsed_response['errorcode'] . ' )';
            } else {
                $mser = sprintf('Error: %s [%s]', $error_message, $error_code);
            }

            throw new Ecomtech_Savemypaquet_Model_Api_Exceptions($mser, $this->response->code, $this->request, $this->response);
        }

        return $parsed_response;
    }

    protected function get_parsed_response($raw_body)
    {
        return json_decode($raw_body, true);
    }

    public function curl_stream_headers($_, $headers)
    {
        $this->curl_headers .= $headers;
        return strlen($headers);
    }

    protected function get_response_headers()
    {

        // get the raw headers
        $raw_headers = preg_replace('/\n[ \t]/', ' ', str_replace("\r\n", "\n", $this->curl_headers));

        // spit them
        $raw_headers = array_filter(explode("\n", $raw_headers), 'strlen');

        $headers = array();

        foreach ($raw_headers as $header) {
            if ('HTTP/' === substr($header, 0, 5)) {
                continue;
            }

            list($key, $value) = explode(':', $header, 2);

            if (isset($headers[$key])) {
                $headers[$key]   = array($headers[$key]);
                $headers[$key][] = $value;
            } else {
                $headers[$key] = $value;
            }
        }

        return $headers;
    }
}
