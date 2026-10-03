<?php

namespace Omnifood\Exception;

/** The platform refused, or could not be reached. */
class ProviderException extends \RuntimeException implements OmnifoodException
{
    public function __construct(
        public readonly string $platform,
        string $message,
        public readonly ?int $status = null,
        /** The platform's own error code, when it gave one */
        public readonly ?string $providerCode = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct(\sprintf('[%s] %s', $platform, $message), 0, $previous);
    }
}
