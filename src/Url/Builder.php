<?php

namespace OptimistDigital\LaravelThumbor\Url;

use Exception;
use Illuminate\Support\Str;


use OptimistDigital\LaravelThumbor\Url;

/**
 * A Builder for incrementally constructing Url objects.
 *
 * Example usage:
 *
 * $server = 'http://thumbor.example.com';
 * $secret = 'my-secret-key';
 *
 * Thumbor\UrlBuilder::construct($server, $secret, 'http://images.example.com/llamas.jpg')
 *     // Apply commands
 *     ->fitIn(320, 240)
 *     // Add filters
 *     ->addFilter('brightness', 42)
 *     // Construct and return Url
 *     ->build();
 *
 * If you coerce an instance of this class to String, you get the string
 * representation of the URL.
 *
 * See https://github.com/globocom/thumbor/wiki/Usage for all available options.
 * @method Builder trim($colourSource = null)
 * @method Builder crop($topLeftX, $topLeftY, $bottomRightX, $bottomRightY)
 * @method Builder fitIn($width, $height)
 * @method Builder resize($width, $height)
 * @method Builder halign($halign)
 * @method Builder valign($valign)
 * @method Builder smartCrop($smartCrop)
 * @method Builder addFilter($filter, $args, $_ = null)
 * @method Builder metadataOnly($metadataOnly)
 */
class Builder
{
    private $server;
    private $secret;
    private $original;
    protected $commands;
    protected static $whitelistedFiletypes = ["webp", "jpeg", "jpg", "gif", "png"];

    public static function construct($server, $secret, $original)
    {
        return new self($server, $secret, $original);
    }

    public function __construct($server, $secret, $original)
    {
        $this->server = $server;
        $this->secret = $secret;
        $this->original = $original;
        $this->commands = new CommandSet();
    }

    public function __clone()
    {
        $this->commands = clone $this->commands;
    }

    // Proxy remaining method calls to CommandSet
    public function __call($method, $args)
    {
        $proxied = array($this->commands, $method);
        if (!is_callable($proxied)) {
            throw new Exception(sprintf(
                'Method "%s" not found for %s',
                $method,
                get_class($this->commands)
            ));
        }
        call_user_func_array($proxied, $args);
        return $this;
    }

    public function build()
    {
        $isValidFiletype = false;

        foreach (Builder::$whitelistedFiletypes as $filetype) {
            if (Str::endsWith($this->original, $filetype)) {
                $isValidFiletype = true;
                break;
            }
        }

        if ($isValidFiletype) {
            return new Url(
                $this->server,
                $this->secret,
                $this->original,
                $this->commands->toArray()
            );
        } else return $this->original;
    }

    public function __toString()
    {
        return (string) $this->build();
    }
}
