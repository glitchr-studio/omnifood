<?php

namespace Omnifood\Model;

/** One thing a platform would refuse: where (categories[0].items[2].name...) and why, readable. */
final readonly class Violation
{
    public function __construct(
        public string $path,
        public string $message,
    ) {
    }

    public function __toString(): string
    {
        return $this->path.': '.$this->message;
    }
}
