<?php

namespace Omnifood;

use Omnifood\Model\Notification;

/**
 * A webhook the platform calls: its body and headers, checked (signature,
 * secret) and read. InvalidSignatureException when the signature does not
 * hold: the controller answers 401 and does nothing. Notification::$id is
 * the platform's id for the event, to handle each once (they retry).
 */
interface NotifiableInterface extends PlatformInterface
{
    /** @param array<string, string|list<string>> $headers as the request has them, any case */
    public function notify(string $body, array $headers): Notification;
}
