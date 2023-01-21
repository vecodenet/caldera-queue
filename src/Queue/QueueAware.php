<?php

declare(strict_types = 1);

/**
 * Caldera Queue
 * Job queue layer, part of Vecode Caldera
 * @author  biohzrdmx <github.com/biohzrdmx>
 * @copyright Copyright (c) 2023 Vecode. All rights reserved
 */

namespace Caldera\Queue;

use Caldera\Queue\Queue;

trait QueueAware {

    /**
     * Queue instance
     * @var Queue
     */
    protected $queue;

    /**
     * Set current Queue
     * @param  Queue $queue Queue instance
     * @return $this
     */
    public function setQueue(Queue $queue) {
        $this->queue = $queue;
        return $this;
    }

    /**
     * Get current Queue
     * @return ?Queue
     */
    public function getQueue(): ?Queue {
        return $this->queue;
    }
}