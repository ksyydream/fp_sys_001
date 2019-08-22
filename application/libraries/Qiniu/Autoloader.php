<?php


namespace Qiniu;

class Autoloader {
    private $directory;
    private $prefix;
    private $prefixLength;

    public function __construct($baseDirectory = __DIR__)
    {
        $this->directory = $baseDirectory;
        $this->prefix = __NAMESPACE__.'\\';
        $this->prefixLength = strlen($this->prefix);
    }

    public function autoload($class)
    {
        if (0 === strpos($class, $this->prefix)) {
            $parts = explode('\\', substr($class, $this->prefixLength));
            $filepath = $this->directory . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $parts) . '.php';

            if (is_file($filepath)) {
                require $filepath;
            }
        }
    }

    public static function register()
    {
        spl_autoload_register(array(new self(), 'autoload'));
    }
}