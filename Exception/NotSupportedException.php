<?php

namespace Omnifood\Exception;

/**
 * The platform does not do that, or its public documentation does not show
 * how: nothing is guessed, the call is refused.
 */
final class NotSupportedException extends \LogicException implements OmnifoodException
{
    public static function operation(string $platform, string $operation): self
    {
        return new self(\sprintf('The "%s" platform does not %s.', $platform, $operation));
    }
}
