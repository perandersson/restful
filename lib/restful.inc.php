<?php
require_once "utils.inc.php";

/**
 * Base class for all request mappings registrable by the Restful service.
 *
 * Class RequestMapping
 */
abstract class RequestMapping
{
    private $path;
    private $fn;
    private $method;

    private $parts;

    function __construct($path, $fn, $method)
    {
        $this->path = $path;
        $this->fn = $fn;
        $this->method = $method;
        $this->parts = null;
    }

    /**
     * @param $method
     * @param $requestParts
     * @return bool If the supplied method is supported by this request
     */
    function isSupported($method, $requestParts)
    {
        if ($this->method == $method) {
            $parts = $this->getPathParts();
            return count($requestParts) == count($parts);
        }

        return false;
    }

    function handle($requestParts, $postData)
    {
        $args = $this->extractArgsFromRequest($requestParts);
        return $this->handleRequest($args, $postData, $this->fn);
    }

    /**
     * @return array containing the path parts (not including the first slash-character) for this specific request mapping
     */
    protected function getPathParts()
    {
        if ($this->parts == null) {
            $path = parse_url($this->path);
            $this->parts = Utils::trimSplit($path["path"], "/");
        }
        return $this->parts;
    }

    /**
     * Extract the arguments from the supplied request parts
     *
     * @param $requestParts
     * @return array|bool
     */
    protected function extractArgsFromRequest($requestParts)
    {
        $parts = $this->getPathParts();
        $args = array();
        $argsIdx = 0;
        foreach ($parts as $part) {
            $len = strlen($part);

            if ($len == 0 || $part[0] != "{") {
                return false;
            }

            $key = substr($part, 1, $len - 2);
            $args[$key] = $requestParts[$argsIdx++];
        }

        return $args;
    }

    abstract protected function handleRequest($args, $body, $fn);

    abstract public function successCode();
}

class PostMapping extends RequestMapping
{
    function __construct($path, $fn)
    {
        parent::__construct($path, $fn, "POST");
    }

    protected function handleRequest($args, $body, $fn)
    {
        $result = $fn($args, $body);
        return $result;
    }

    public function successCode()
    {
        return 201;
    }
}

class GetMapping extends RequestMapping
{
    function __construct($path, $fn)
    {
        parent::__construct($path, $fn, "GET");
    }

    protected function handleRequest($args, $body, $fn)
    {
        $result = $fn($args);
        return $result;
    }

    public function successCode()
    {
        return 200;
    }
}

/**
 * Syntactic sugar used when registering a resource accepting POST requests
 *
 * @param $path string The path to the resource
 * @param $fn Closure The function
 * @return RequestMapping
 */
function post($path, $fn)
{
    return new PostMapping($path, $fn);
}

/**
 * Syntactic sugar used when registering a resource accepting GET requests
 *
 * @param $path string The path to the resource
 * @param $fn Closure The function
 * @return RequestMapping
 */
function get($path, $fn)
{
    return new GetMapping($path, $fn);
}

class Restful
{
    private $method;
    private $requestContentType;
    private $pathParts;
    private $pathPartsCount;
    private $postData;

    function __construct($requestMethod, $requestContentType, &$pathInfo)
    {
        $this->method = $requestMethod;
        if ($requestMethod == "GET") {
            $this->requestContentType = "application/json";
        } else {
            $this->requestContentType = Utils::getOrElse($requestContentType, "");
        }
        $body = file_get_contents('php://input');
        $this->postData = Restful::getJsonOrEmpty($body);
        $this->pathParts = Utils::trimSplit($pathInfo, "/");
        $this->pathPartsCount = count($this->pathParts);
    }

    /**
     * @param $resource RequestMapping
     * @param $result object Any object that's serializable to JSON
     */
    private function handleResult($resource, $result)
    {
        if ($result == null) {
            http_response_code(404);
        } else {
            header('Content-Type: application/json');
            header('Cache-Control: no-cache, must-revalidate');
            http_response_code($resource->successCode());

            $json_result = json_encode($result);
            print($json_result);
        }
    }

    static function getJsonOrEmpty($data)
    {
        if ($data == null || $data == "")
            $data = "{}";

        return json_decode($data);
    }

    /**
     * Create a RESTful instance based on the incoming http request
     *
     * @return Restful
     */
    static function fromHttpRequest()
    {
        $contentType = Utils::getOrElse($_SERVER["CONTENT_TYPE"], "");
        return new Restful($_SERVER["REQUEST_METHOD"], $contentType, $_SERVER["PATH_INFO"]);
    }

    /**
     * @param $resources RequestMapping[] All resources acceptable
     */
    function register($resources)
    {
        if ($this->requestContentType != "application/json") {
            return;
        }

        foreach ($resources as $resource) {
            if ($resource->isSupported($this->method, $this->pathParts)) {
                $result = $resource->handle($this->pathParts, $this->postData);
                $this->handleResult($resource, $result);
                return;
            }
        }

        // If resource is not found
        http_response_code(404);
    }
}