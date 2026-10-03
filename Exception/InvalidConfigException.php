<?php

namespace Omnifood\Exception;

/** A platform misconfigured: a credential missing, an unknown factory. */
final class InvalidConfigException extends \LogicException implements OmnifoodException
{
}
