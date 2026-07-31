<?php

namespace App\Exceptions;

use Exception;

class PreorderDateUnavailableException extends Exception
{
    protected $message = 'This preorder date is not available for the selected fulfilment method.';
}
