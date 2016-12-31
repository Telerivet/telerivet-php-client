<?php
namespace Telerivet\Exceptions;

class TelerivetInvalidParameterException extends TelerivetAPIException
{
    public $param;
    public function __construct($message, $error_code, $param)
    {
        parent::__construct($message, $error_code);
        $this->param = $param;
    }
}
