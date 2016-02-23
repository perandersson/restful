<?php
require_once "utils.inc.php";

/**
 * Class representing a REST result
 */
class RestResult
{
    private $json;
    private $responseCode;

    function __construct($object, $responseCode)
    {
        $this->json = Utils::safeJsonEncode($object);
        $this->responseCode = $responseCode;
    }

    function handleResult()
    {
        if ($this->json != null) {
            header('Content-Type: application/json');
            header('Cache-Control: no-cache, must-revalidate');
        }
        // TODO: header("Location: path/to/resource");
        http_response_code($this->responseCode);
        print($this->json);
    }
}

function created($result)
{
    return new RestResult($result, 201);
}

function not_found($result = null)
{
    return new RestResult($result, 404);
}

function conflict($result = null)
{
    return new RestResult($result, 409);
}

function ok($result)
{
    return new RestResult($result, 200);
}

function no_content($result = null)
{
    return new RestResult($result, 204);
}

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

    /**
     * @param $requestParts
     * @param $postData
     * @return RestResult
     */
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

    /**
     * @param $args
     * @param $body
     * @param $fn
     * @return RestResult
     */
    abstract protected function handleRequest($args, $body, $fn);
}

class RequestMappingWithBody extends RequestMapping
{
    protected function handleRequest($args, $body, $fn)
    {
        $result = $fn($args, $body);
        return $result;
    }
}

class RequestMappingWithoutBody extends RequestMapping
{
    protected function handleRequest($args, $body, $fn)
    {
        $result = $fn($args);
        return $result;
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
    return new RequestMappingWithBody($path, $fn, "POST");
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
    return new RequestMappingWithoutBody($path, $fn, "GET");
}

/**
 * Syntactic sugar used when registering a resource accepting PUT requests
 *
 * @param $path string The path to the resource
 * @param $fn Closure The function
 * @return RequestMapping
 */
function put($path, $fn)
{
    return new RequestMappingWithBody($path, $fn, "PUT");
}

/**
 * Syntactic sugar used when registering a resource accepting PATCH requests
 *
 * @param $path string The path to the resource
 * @param $fn Closure The function
 * @return RequestMapping
 */
function patch($path, $fn)
{
    return new RequestMappingWithBody($path, $fn, "PATCH");
}

/**
 * Syntactic sugar used when registering a resource accepting DELETE requests
 *
 * @param $path string The path to the resource
 * @param $fn Closure The function
 * @return RequestMapping
 */
function delete($path, $fn)
{
    return new RequestMappingWithoutBody($path, $fn, "DELETE");
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
        $this->postData = Utils::safeJsonDecode($body);
        $this->pathParts = Utils::trimSplit($pathInfo, "/");
        $this->pathPartsCount = count($this->pathParts);
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
                $restResult = $resource->handle($this->pathParts, $this->postData);
                $restResult->handleResult();
                return;
            }
        }

        // If resource is not found
        http_response_code(404);
    }
}