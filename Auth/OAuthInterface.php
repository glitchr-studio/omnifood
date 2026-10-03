<?php

namespace Omnifood\Auth;

use Omnifood\Model\Token;

/** Connecting the restaurant's account from a back office: the URL to send the owner to, the code exchanged on the way back. */
interface OAuthInterface
{
    /** @param list<string> $scopes */
    public function authorizationUrl(string $redirectUri, string $state, array $scopes = []): string;

    public function exchange(string $code, string $redirectUri): Token;
}
