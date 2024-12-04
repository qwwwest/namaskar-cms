<?php
namespace Qwwwest\Namaskar;

/*
 *  Response Class
 *  Creates a HTTP Response
 */

class Response
{

    const HTTP_OK = 200;
    const HTTP_MULTIPLE_CHOICES = 300;
    const HTTP_MOVED_PERMANENTLY = 301;
    const HTTP_FOUND = 302;
    const HTTP_SEE_OTHER = 303;
    const HTTP_NOT_MODIFIED = 304;
    const HTTP_TEMPORARY_REDIRECT = 307;
    const HTTP_PERMANENTLY_REDIRECT = 308; // RFC7238
    const HTTP_BAD_REQUEST = 400;
    const HTTP_UNAUTHORIZED = 401;
    const HTTP_PAYMENT_REQUIRED = 402;
    const HTTP_FORBIDDEN = 403;
    const HTTP_NOT_FOUND = 404;
    const HTTP_REQUEST_TIMEOUT = 408;
    const HTTP_CONFLICT = 409;
    const HTTP_GONE = 410;
    const HTTP_INTERNAL_SERVER_ERROR = 500;
    const HTTP_NOT_IMPLEMENTED = 501;
    const HTTP_BAD_GATEWAY = 502;
    const HTTP_SERVICE_UNAVAILABLE = 503;


    private $statusCode;
    private $headers;
    private $content;
    private $cType;

    public function __construct($content = '', int $statusCode = 200, $headers = [])
    {
        $this->content = $content;
        $this->statusCode = $statusCode;
        $this->headers = $headers;
        $this->cType = 'text/html';
    }


    public function setContent(string $content): self
    {
        $this->content = $content;
        return $this;
    }

    public function setHeader($name, $value)
    {
        $this->headers[$name] = (string) $value;
    }


    public static function fileContentType($filename)
    {
        $contentType = null;

        $ext = pathinfo($filename, PATHINFO_EXTENSION);

        // assets
        switch ($ext) {
            case 'xml':
                $contentType = 'application/xml';
            case 'js':
                $contentType = 'text/javascript';
                break;
            case 'html':
            case 'css':
                $contentType = "text/$ext";
                break;
            case 'jpeg':
            case 'jpg':
                $contentType = 'image/jpeg';
                break;
            case 'gif':
            case 'png':
            case 'webp':
                $contentType = "image/$ext";
                break;
            case 'svg':
                $contentType = 'image/svg+xml';
                break;
            default:
                $contentType = 'text/plain';
                break;
        }
        return $contentType;

    }

    public function setContentType($type): self
    {
        $stype = 'text'; //default

        if ($type === 'js')
            $type = 'javascript';
        if ($type === 'jpg')
            $type = 'jpeg';
        if ($type === 'text')
            $type = 'plain';
        if ($type === 'svg')
            $type = 'svg+xml';

        switch ($type) {
            case 'json':
            case 'plain':
            case 'css':
            case 'javascript':
            case 'html':
            case 'xml':

                break;
            case 'jpeg':
            case 'png':
            case 'gif':
            case 'svg+xml':
                $stype = 'image';
                break;
            default:
                die('unknown type');
        }
        $this->cType = "$type";
        $this->setHeader('Content-Type', "$stype/$type");
        //header("Content-Type: $stype/$type");
        return $this;

    }

    function json(array $data): self
    {
        $this->setContentType('json');
        $this->content = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
        return $this;
    }

    function file($filename, $type = null): self
    {
        if ($type)
            $this->setContentType($type);
        else {
            $ext = pathinfo($filename, PATHINFO_EXTENSION);
            $this->setContentType($ext);
        }
        $this->content = file_get_contents($filename);
        return $this;
    }

    function send()
    {
        http_response_code($this->statusCode);
        foreach ($this->headers as $headerName => $headerValue) {
            header("$headerName: $headerValue");

        }
        echo $this->content;

        return $this;
    }

    function setStatusCode(int $statusCode): self
    {
        $this->statusCode = $statusCode;
        return $this;
    }
    function getStatusCode(): int
    {
        return $this->statusCode;
    }

    function ____clearSession(): self
    {
        session_destroy();
        return $this;
    }


    function redirect(string $url, $statusCode = 302): self
    {
        //http_response_code($statusCode);
        //header("Location: $url");
        $this->setStatusCode($statusCode);
        $this->setHeader('Location', $url);
        return $this;
    }

}