<?php

namespace Aol\Offload\Deferred;

/**
 * Represents an already-completed deferred.
 */
final class OffloadDeferredComplete implements OffloadDeferredInterface
{
    /** @var mixed The result. */
    private $result;

    /**
     * Create a new completed deferred.
     *
     * @param mixed $result The final result.
     */
    public function __construct($result)
    {
        $this->result = $result;
    }

    /**
     * @inheritdoc
     */
    public function wait()
    {
        return $this->result;
    }

    /**
     * @inheritdoc
     */
    public function then(callable $fulfilled)
    {
        $fulfilled($this->result);
    }
}
