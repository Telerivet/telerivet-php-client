<?php
namespace Telerivet\Exceptions;

class TelerivetNotFoundException extends TelerivetAPIException
{
    public function __construct($message, $error_code)
    {
        parent::__construct($message, $error_code);
    }
}
