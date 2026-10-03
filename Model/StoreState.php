<?php

namespace Omnifood\Model;

enum StoreState: string
{
    /** Taking orders */
    case OPEN = 'open';
    /** Within its hours, but taking none for a while (a rush, a shortage) */
    case PAUSED = 'paused';
    /** Outside its hours */
    case CLOSED = 'closed';
    case UNKNOWN = 'unknown';
}
