<?php
declare(strict_types=1);
namespace Zewa\HTTP;

use Zewa\Dependency;
use Zewa\Security;

/**
 * Class Request
 * @package Zewa\HTTP
 */
class Request
{
    /**
     * @var Server
     */
    public $server;

    /**
     * @var Session
     */
    public $session;

    /**
     * @var Get
     */
    public $get;

    /**
     * @var Post
     */
    public $post;

    /**
     * @var Put
     */
    public $put;

    /**
     * @var Delete
     */
    public $delete;

    /**
     * @var File
     */
    public $file;

    /**
     * @var Cookie
     */
    public $cookie;

    /**
     * @var
     */
    private $request;

    /**
     * @var Security
     */
    private $security;

    /** @var string */
    private $method;

    /** @var array */
    private $params;

    /** @var  int */
    private $statusCode;

    /** @var  string */
    private $responseMessage;

    public function __construct(Dependency $dependency, Security $security)
    {
        $this->security = $security;
        /** @var Server server */
        $this->server = $dependency->resolve('\Zewa\HTTP\Server', true);
        /** @var Session session */
        $this->session = $dependency->resolve('\Zewa\HTTP\Session', true);
        /** @var Get get */
        $this->get = $dependency->resolve('\Zewa\HTTP\Get', true);
        /** @var Post post */
        $this->post = $dependency->resolve('\Zewa\HTTP\Post', true);
        /** @var Put put */
        $this->put = $dependency->resolve('\Zewa\HTTP\Put', true);
        /** @var Delete delete */
        $this->delete = $dependency->resolve('\Zewa\HTTP\Delete', true);
        /** @var File file */
        $this->file = $dependency->resolve('\Zewa\HTTP\File', true);
        /** @var Cookie cookie */
        $this->cookie = $dependency->resolve('\Zewa\HTTP\Cookie', true);
    }

    public function getRequest()
    {
        return $this->request;
    }

    public function setRequest($request)
    {
        $this->request = $request;
    }

    public function getMethod()
    {
        return $this->method;
    }

    public function setMethod($method)
    {
        $this->method = $method;
    }

    public function getParams()
    {
        return $this->params;
    }

    public function setParams($params)
    {
        $this->params = $this->security->normalize($params);
    }

    public function redirect(string $url, int $status = 302)
    {
        $url = str_replace(array('\r', '\n', '%0d', '%0a'), '', $url);
        if (headers_sent()) {
            return false;
        }
        session_write_close();

        $this->setStatusCode($status);

        header('HTTP/1.1 ' . $this->responseMessage);
        $url = preg_replace('!^/*!', '', $url);
        header("Location: " . $url);
        return true;
    }

    public function setStatusCode(int $status = 302)
    {
        switch ($status) {
            case '301':
                $this->responseMessage = '301 Moved Permanently';
                break;
            case '307':
                $this->responseMessage = '307 Temporary Redirect';
                break;
            case '302':
            default:
                $this->responseMessage = '302 Found';
                break; // temp redirect
        }
    }
}
