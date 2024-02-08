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

interface QueueAwareInterface {

    /**
     * Set current Queue
     * @param  Queue $queue Queue instance
     * @return $this
     */
    public function setQueue(Queue $queue);

    /**
     * Get current Queue
     */
    public function getQueue(): ?Queue;
}
