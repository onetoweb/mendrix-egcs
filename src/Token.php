<?php

namespace Onetoweb\Mendrix\Egcs;

use DateTime;

/**
 * Token.
 */
class Token
{
    /**
     * @var string
     */
    private $value;
    
    /**
     * @var DateTime
     */
    private $expires;
    
    /**
     * @param string $value
     * @param DateTime $expires
     */
    public function __construct(string $value, DateTime $expires)
    {
        $this->value = $value;
        $this->expires = $expires;
    }
    
    /**
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }
    
    /**
     * @return DateTime
     */
    public function getExpires(): DateTime
    {
        return $this->expires;
    }
    
    /**
     * @return bool
     */
    public function isExpired(): bool
    {
        return (bool) (new DateTime() > $this->expires);
    }
}