<?php

if (! defined('PHP_INT_MIN')) {
    define('PHP_INT_MIN', /** @var int */ ~\PHP_INT_MAX);
}

if (! defined('REST_REQUEST')) {
    define('REST_REQUEST', /** @var ?bool */ false);
}
