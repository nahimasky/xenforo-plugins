<?php

namespace Sky\ProfilePins\Exception;

class ProfilePostPinLimitException extends \RuntimeException
{
    protected $maximumPins;

    public function __construct(int $maximumPins)
    {
        parent::__construct('The configured profile-post pin limit has been reached.');
        $this->maximumPins = $maximumPins;
    }

    public function getMaximumPins(): int
    {
        return $this->maximumPins;
    }
}
