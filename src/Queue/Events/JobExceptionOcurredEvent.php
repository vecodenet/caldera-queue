<?php

namespace Caldera\Queue\Events;

use Exception;

use Caldera\Queue\JobInterface;

class JobExceptionOcurredEvent extends AbstractJobEvent {

    /**
     * Exception instance
     */
    protected Exception $exception;

    /**
     * Constructor
     * @param JobInterface $job
     * @param Exception    $exception
     */
    public function __construct(JobInterface $job, Exception $exception) {
        parent::__construct($job);
        $this->exception = $exception;
    }

    /**
     * Get Exception instance
     */
    public function getException(): Exception {
        return $this->exception;
    }
}