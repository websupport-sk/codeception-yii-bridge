<?php
namespace Websupport\CodeceptionYiiBridge\Yii1\Web;

class CodeceptionHttpRequest extends \CHttpRequest
{
    private $_headers = array();

    protected $_cookies;

    private $rawBody;

    public function setHeader($name, $value)
    {
        $this->_headers[$name] = $value;
    }

    public function getHeader($name, $default = null)
    {
        return isset($this->_headers[$name]) ? $this->_headers[$name] : $default;
    }

    public function getAllHeaders()
    {
        return $this->_headers;
    }

    public function getCookies()
    {
        if ($this->_cookies !== null) {
            return $this->_cookies;
        }

        return $this->_cookies = new CodeceptionCookieCollection($this);
    }

    public function getServerName()
    {
        return isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : '';
    }

    public function getServerPort()
    {
        return isset($_SERVER['SERVER_PORT']) ? $_SERVER['SERVER_PORT'] : null;
    }

    public function redirect($url, $terminate = true, $statusCode = 302)
    {
        $this->setHeader('Location', $url);
        if ($terminate) {
            \Yii::app()->end(0, false);
        }
    }

    public function getRawBody()
    {
        return $this->rawBody;
    }

    public function setRawBody($body)
    {
        $this->rawBody = $body;
    }
}
