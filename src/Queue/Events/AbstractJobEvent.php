<?php

namespace Caldera\Queue\Events;

use Caldera\Queue\JobInterface;

class AbstractJobEvent {

    /**
     * JobInterface implementation
     */
    protected JobInterface $job;

    /**
     * Constructor
     * @param JobInterface $job
     */
    public function __construct(JobInterface $job) {
        $this->job = $job;
    }

    /**
     * Get JobInterface implementation
     */
    public function getJob(): JobInterface {
        return $this->job;
    }
}