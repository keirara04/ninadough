<?php

namespace App\Exceptions;

use Exception;

class PreorderDateFullException extends Exception
{
    protected $message = 'This date has just become full; please choose another date.';
}
