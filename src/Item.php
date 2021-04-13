<?php

namespace Changelogger;

/**
 * Class representing update items in a changelog.
 */
class Item
{
    /**
     * The item message.
     *
     * @var string
     */
    private $message;

    /**
     * Reference IDs.
     *
     * @var array
     */
    private $references;

    /**
     * Returns the instantiated object represented in a string format.
     *
     * @return string
     */
    public function __toString(): string
    {
        if (!is_null($this->references)) {
            sort($this->references, SORT_NUMERIC);

            $references = implode('', array_map(function ($ref) {
                return " [#$ref]";
            }, $this->references));
        }

        $message = ucfirst($this->message);
        $message = rtrim($message, '.') . '.';

        if (isset($references)) {
            return "$message$references";
        } else {
            return "$message";
        }
    }

    /**
     * Set the message.
     *
     * @param string $message
     */
    public function setMessage(string $message)
    {
        $this->message = $message;
    }

    /**
     * Add reference IDs.
     *
     * @param int|string $reference
     */
    public function addReference($reference)
    {
        $this->references[] = $reference;
    }
}
