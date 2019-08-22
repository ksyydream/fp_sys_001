<?php

require 'Qiniu/Autoloader.php';


class Qiniu {

    public function __construct() {
        Qiniu\Autoloader::register();
        require 'Qiniu/functions.php';
    }
}