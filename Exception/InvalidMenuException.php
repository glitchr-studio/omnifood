<?php

namespace Omnifood\Exception;

use Omnifood\Model\Violation;

/** The menu breaks the platform's capabilities: nothing was sent. */
final class InvalidMenuException extends \InvalidArgumentException implements OmnifoodException
{
    /** @param list<Violation> $violations */
    public function __construct(public readonly string $platform, public readonly array $violations)
    {
        parent::__construct(\sprintf('[%s] %s', $platform, implode('; ', array_map('strval', $violations))));
    }
}
