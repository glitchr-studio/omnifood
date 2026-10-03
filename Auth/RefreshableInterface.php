<?php

namespace Omnifood\Auth;

use Omnifood\Model\Token;

/**
 * A platform whose token dies: the client-credentials token (Uber Eats,
 * Deliveroo, TheFork's Auth0) asked for again before it does. The platform
 * fetches one itself when a call needs it; refresh() is for a site that
 * keeps it (a cache, a database) and hands it back through the options.
 */
interface RefreshableInterface
{
    public function token(): ?Token;

    public function refresh(): Token;
}
