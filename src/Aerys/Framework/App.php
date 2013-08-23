<?php

namespace Aerys\Framework;

/**
 * The `App` class exposes a simple API for assigning settings to individual host applications. It
 * provides no validation at assignment time; instead, values are validated when the framework
 * bootstrapper passes them to individual server components. Additional validation at this stage
 * would be an unnecessary redundancy.
 */
class App {

    private $port = 80;
    private $address = '*';
    private $name = '';
    private $routes = [];
    private $websockets = [];
    private $documentRoot = [];
    private $reverseProxy = [];
    private $encryption = [];
    private $userResponders = [];
    private $responderOrder = [];

    /**
     * Define the host's port, IP and domain name
     *
     * Any valid port number [1-65535] may be used. Port numbers lower than 256 are reserved for
     * well-known services (like HTTP on port 80) and port numbers less than 1024 require root
     * access on UNIX systems. If no value is specified port 80 is assumed.
     *
     * @param int $port The port number on which to listen
     * @param string $interface The IP address on which to bind this application
     * @param string $name The application domain name
     * @return \Aerys\Framework\AppDefinition Returns the current object instance
     */
    function setPort($port) {
        $this->port = $port;

        return $this;
    }

    /**
     * Define the IP interface on which the app will listen for requests
     *
     * The default wildcard IP value "*" translates to "all IPv4 interfaces" and is appropriate for
     * most scenarios. Valid values also include any IPv4 or IPv6 address. The string "[::]" denotes
     * an IPv6 wildcard.
     *
     * @param string $address
     * @return \Aerys\Framework\AppDefinition Returns the current object instance
     */
    function setAddress($address) {
        $this->address = $address;

        return $this;
    }

    /**
     * Define the app's host name (e.g. localhost or mysite.com or subdomain.mysite.com)
     *
     * Host names are only *required* when serving more than one host on a server.
     *
     * @param string $name
     * @return \Aerys\Framework\AppDefinition Returns the current object instance
     */
    function setName($name) {
        $this->name = $name;

        return $this;
    }

    /**
     * Define TLS encryption settings for this host
     *
     * The $tlsOptions array takes the following form:
     *
     * $tlsOptions = [
     *     'local_cert'             => '/path/to/mycert.pem', // *required
     *     'passphrase'             => 'mypassphrase',        // *required
     *     'allow_self_signed'      => TRUE,
     *     'verify_peer'            => FALSE,
     *     'ciphers'                => 'RC4-SHA:HIGH:!MD5:!aNULL:!EDH',
     *     'disable_compression'    => TRUE,
     *     'cafile'                 => NULL,
     *     'capath'                 => NULL
     * ];
     *
     * @param array $tlsOptions
     * @return \Aerys\Framework\AppDefinition Returns the current object instance
     */
    function setEncryption(array $tlsOptions) {
        $this->encryption = $tlsOptions;

        return $this;
    }

    /**
     * Bind a handler for the specified HTTP method and URI path
     *
     * @param string $httpMethod The method for which this route applies
     * @param string $uriPath The route's URI path
     * @param mixed $handler Any callable or class::method construction string
     * @return \Aerys\Framework\AppDefinition Returns the current object instance
     */
    function addRoute($httpMethod, $uriPath, $handler) {
        $uriPath = '/' . ltrim($uriPath, '/');
        $this->routes[] = [$httpMethod, $uriPath, $handler];

        return $this;
    }

    /**
     * Bind a websocket endpoint to the specified URI path
     *
     * $options = [
     *     'subprotocol'      => NULL,
     *     'allowedOrigins'   => [],
     *     'maxFrameSize'     => 2097152,
     *     'maxMsgSize'       => 10485760,
     *     'heartbeatPeriod'  => 10,
     *     'validateUtf8Text' => TRUE
     * ];
     *
     * @param string $uriPath The URI path on which to bind the endpoint
     * @param string $handler A websocket endpoint class name
     * @param array $options An array specifying key-value options for this websocket endpoint
     * @return \Aerys\Framework\AppDefinition Returns the current object instance
     */
    function addWebsocket($uriPath, $handlerClass, array $options = []) {
        $uriPath = '/' . ltrim($uriPath, '/');
        $this->websockets[] = [$uriPath, $handlerClass, $options];

        return $this;
    }

    /**
     * Specify an optional filesystem directory from which to serve static files
     *
     * The $options array takes the form:
     *
     * $options = [
     *     'indexes'                   => ['index.html', 'index.htm'],
     *     'indexRedirection'          => TRUE,
     *     'eTagMode'                  => 'all',
     *     'expiresHeaderPeriod'       => 300,
     *     'defaultMimeType'           => 'text/plain',
     *     'customMimeTypes'           => [],
     *     'defaultTextCharset'        => 'utf-8',
     *     'cacheTtl'                  => 5,
     *     'memoryCacheMaxSize'        => 67108864,
     *     'memoryCacheMaxFileSize'    => 1048576
     * ];
     *
     * Note: websocket endpoint and dynamic HTTP route URIs always take precedence over filesystem
     * resources in the event of a routing conflict.
     *
     * @param string $directoryPath
     * @param array $options An array specifying key-value options for static file serving
     * @return \Aerys\Framework\AppDefinition Returns the current object instance
     */
    function setDocumentRoot($directoryPath, array $options = []) {
        $options['docRoot'] = $directoryPath;
        $this->documentRoot = $options;

        return $this;
    }

    /**
     * Specify optional reverse proxy functionality for this application
     *
     * @TODO Add more documentation
     *
     * @param array $addresses An array of backend server addresses
     * @param array $options
     */
    function setReverseProxy(array $addresses, array $options = []) {
        $options['backends'] = $addresses;
        $this->reverseProxy = $options;

        return $this;
    }

    /**
     * Add a user responder to the request-response chain
     * 
     * User responders are always invoked in the order in which they are added to the App.
     * 
     * @param mixed $responder Any callable or class::method construction string
     * @return \Aerys\Framework\AppDefinition Returns the current object instance
     */
    function addUserResponder($responder) {
        $this->userResponders[] = $responder;
        
        return $this;
    }

    /**
     * Determine the order in which request responders are invoked for this application
     * 
     * Valid values include the following and are case-insensitive:
     * 
     * - 'websockets'       (App::addWebsocket)
     * - 'routes'           (App::addRoute)
     * - 'user'             (App::addUserResponder)
     * - 'docroot'          (App::setDocumentRoot)
     * - 'reverseproxy'     (App::setReverseProxy)
     * 
     * Any values specified that don't match the above list will result in a ConfigException
     * when the server is bootstrapped. Note that the above list is the default responder order.
     * User responders added via `App::addUserResponder` are always ordered internally by the
     * order in which they are added to the app.
     * 
     * @param array $order
     * @return \Aerys\Framework\AppDefinition Returns the current object instance
     */
    function orderResponders(array $order) {
        $this->responderOrder = $order;
        
        return $this;
    }

    /**
     * Retrieve an associative array summarizing the host definition
     *
     * @return array
     */
    function toArray() {
        return [
            'port' => $this->port,
            'address' => $this->address,
            'name' => $this->name,
            'routes' => $this->routes,
            'websockets' => $this->websockets,
            'documentRoot' => $this->documentRoot,
            'reverseProxy' => $this->reverseProxy,
            'encryption' => $this->encryption,
            'userResponders' => $this->userResponders,
            'responderOrder' => $this->responderOrder
        ];
    }

}
