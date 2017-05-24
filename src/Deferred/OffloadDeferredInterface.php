<?php

namespace Aol\Offload\Deferred;

/**
 * For deferred tasks.
 */
interface OffloadDeferredInterface
{
    /**
     * Waits for a result.
     *
     * @return mixed The result.
     */
    function wait();

    /**
     * When the result is complete, call the given listener.
     *
     * @param callable $fulfilled The fulfilled listener.
     */
    function then(callable $fulfilled);
}
