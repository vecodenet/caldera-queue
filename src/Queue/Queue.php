<?php

declare(strict_types = 1);

/**
 * Caldera Queue
 * Job queue layer, part of Vecode Caldera
 * @author  biohzrdmx <github.com/biohzrdmx>
 * @copyright Copyright (c) 2023 Vecode. All rights reserved
 */

namespace Caldera\Queue;

use Closure;
use Exception;

use Psr\EventDispatcher\EventDispatcherInterface;

use Caldera\Queue\Adapter\AdapterInterface;
use Caldera\Queue\Events\JobCompletedEvent;
use Caldera\Queue\Events\JobExceptionOcurredEvent;
use Caldera\Queue\Events\JobFailedEvent;
use Caldera\Queue\Events\JobProcessedEvent;
use Caldera\Queue\Events\JobProcessingEvent;
use Caldera\Queue\Events\JobRetryRequestedEvent;
use Caldera\Queue\Events\WorkerStartedEvent;
use Caldera\Queue\Events\WorkerStoppingEvent;

class Queue {

    /**
     * Queue Adapter
     */
    protected AdapterInterface $adapter;

    /**
     * EventDispatcherInterface implementation
     */
    protected ?EventDispatcherInterface $dispatcher;

    /**
     * Constructor
     * @param AdapterInterface $adapter Queue adapter
     */
    public function __construct(AdapterInterface $adapter, EventDispatcherInterface $dispatcher = null) {
        $this->adapter = $adapter;
        $this->dispatcher = $dispatcher;
        if ($this->adapter instanceof QueueAwareInterface) {
            $this->adapter->setQueue($this);
        }
    }

    /**
     * Get queue adapter
     */
    public function getAdapter(): AdapterInterface {
        return $this->adapter;
    }

    /**
     * Add a job to the queue
     * @param  string $type Job type
     * @param  mixed  $data Job data
     */
    public function add(string $type, mixed $data): string {
        return $this->adapter->add($type, $data);
    }

    /**
     * Get a pending job to work on it
     */
    public function get(): ?JobInterface {
        return $this->adapter->get();
    }

    /**
     * Get pending jobs
     * @param  string $type Job type
     */
    public function pending(string $type = ''): int {
        return $this->adapter->pending($type);
    }

    /**
     * Reset failed jobs
     * @param  string $type Job type
     * @return $this
     */
    public function reset(string $type = ''): self {
        $this->adapter->reset($type);
        return $this;
    }

    /**
     * Purge failed jobs
     * @param  string $type Job type
     * @return $this
     */
    public function purge(string $type = ''): self {
        $this->adapter->purge($type);
        return $this;
    }

    /**
     * Do queue work
     * @param ?Closure $callback Callback closure
     * @param ?Closure $complete Completion closure
     */
    public function work(Closure $callback = null, Closure $complete = null): void {
        $this->dispatcher?->dispatch(new WorkerStartedEvent());
        while (true) {
            if ( $this->pending() ) {
                $job = $this->get();
                $this->dispatcher?->dispatch(new JobProcessingEvent($job));
                try {
                    $job->handle();
                } catch (Exception $e) {
                    $this->dispatcher?->dispatch(new JobExceptionOcurredEvent($job, $e));
                    $this->failed($job, $e);
                }
                $this->dispatcher?->dispatch(new JobProcessedEvent($job));
                if ($callback) {
                    $ret = $callback($job);
                    if ($ret === false) {
                        break;
                    }
                }
            } else {
                sleep($complete ? 0 : 1);
                if ($complete) {
                    $ret = $complete($this);
                    if ($ret === true) {
                        break;
                    }
                }
            }
        }
        $this->dispatcher?->dispatch(new WorkerStoppingEvent());
    }

    /**
     * Mark job as complete
     * @param JobInterface $job
     */
    public function complete(JobInterface $job): void {
        $this->adapter->complete( $job->getUID() );
        $this->dispatcher?->dispatch(new JobCompletedEvent($job));
    }

    /**
     * Mark job as failed
     * @param JobInterface $job
     * @param ?Exception   $exception
     */
    public function failed(JobInterface $job, ?Exception $exception = null): void {
        $this->adapter->failed( $job->getUID() );
        $this->dispatcher?->dispatch(new JobFailedEvent($job, $exception));
    }

    /**
     * Request job retry
     * @param JobInterface $job
     */
    public function retry(JobInterface $job): void {
        $this->adapter->retry( $job->getUID() );
        $this->dispatcher?->dispatch(new JobRetryRequestedEvent($job));
    }
}
