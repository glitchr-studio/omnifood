<?php

namespace Omnifood;

/** Builds a platform's client from its options (a client id, a secret, a store id...). */
interface PlatformFactoryInterface
{
    /** The name platforms are configured with: "ubereats", "thefork"... */
    public function getName(): string;

    /** @param array<string, mixed> $options */
    public function create(array $options = []): PlatformInterface;
}
