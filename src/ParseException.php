<?php

namespace Changelogger;

/**
 * Exception class for parsers.
 */
class ParseException extends \Exception
{
    /**
     * Constructs the ParseException object.
     *
     * @param string $message
     *   The exception message.
     * @param string $code
     *   The related code.
     */
    public function __construct(string $message = '', string $code = '')
    {
        parent::__construct("$message\n\n{$code}\n\n");
    }
}
