<?php

declare(strict_types = 1);

/**
 * Caldera Queue
 * Job queue layer, part of Vecode Caldera
 * @author  biohzrdmx <github.com/biohzrdmx>
 * @copyright Copyright (c) 2023 Vecode. All rights reserved
 */

namespace Caldera\Queue;

trait QueueAware {

    /**
     * Queue instance
     */
    protected Queue $queue;

    /**
     * Set current Queue
     * @param Queue $queue Queue instance
     */
    public function setQueue(Queue $queue): self {
        $this->queue = $queue;
        return $this;
    }

    /**
     * Get current Queue
     */
    public function getQueue(): ?Queue {
        return $this->queue;
    }
}
