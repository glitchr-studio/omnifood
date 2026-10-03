<?php

namespace Omnifood\Exception;

/** A webhook whose signature (or secret) does not hold: answer 401, handle nothing. */
final class InvalidSignatureException extends \RuntimeException implements OmnifoodException
{
    public function __construct(public readonly string $platform, string $message)
    {
        parent::__construct(\sprintf('[%s] %s', $platform, $message));
    }
}
