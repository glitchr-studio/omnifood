<?php

namespace Omnifood;

use Omnifood\Model\Capabilities;

/**
 * A restaurant platform's client, for one restaurant. It implements the
 * capabilities the platform offers - OrdersInterface, MenuInterface,
 * StoreInterface, ReservationsInterface, NotifiableInterface - and
 * Auth\RefreshableInterface or Auth\OAuthInterface when it holds a token.
 * What the platform offers but its public documentation does not show
 * throws Exception\NotSupportedException.
 */
interface PlatformInterface
{
    /** The name it is configured as: "ubereats", "thefork"... */
    public function getName(): string;

    public function getChannel(): Channel;

    /** What the platform takes and imposes, for a back office and the Validator. */
    public function capabilities(): Capabilities;
}
