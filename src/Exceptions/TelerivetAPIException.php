<?php
namespace Telerivet\Exceptions;

// exception corresponding to error returned in API response
class TelerivetAPIException extends TelerivetException
{
    public $error_code;

    public function __construct($message, $error_code)
    {
        parent::__construct($message);
        $this->error_code = $error_code;
    }
}
