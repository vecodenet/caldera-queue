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

use Caldera\Queue\Adapter\AdapterInterface;
use Caldera\Queue\JobInterface;
use Caldera\Queue\QueueAwareInterface;

class Queue {

    /**
     * Queue Adapter
     * @var AdapterInterface
     */
    protected $adapter;

    /**
     * Constructor
     * @param AdapterInterface $adapter Queue adapter
     */
    public function __construct(AdapterInterface $adapter) {
        $this->adapter = $adapter;
        if ($this->adapter instanceof QueueAwareInterface) {
            $this->adapter->setQueue($this);
        }
    }

    /**
     * Get queue adapter
     * @return AdapterInterface
     */
    public function getAdapter(): AdapterInterface {
        return $this->adapter;
    }

    /**
     * Add a job to the queue
     * @param  string $type Job type
     * @param  mixed  $data Job data
     * @return string
     */
    public function add(string $type, $data): string {
        return $this->adapter->add($type, $data);
    }

    /**
     * Get a pending job to work on it
     * @return JobInterface
     */
    public function get(): ?JobInterface {
        return $this->adapter->get();
    }

    /**
     * Get pending jobs
     * @param  string $type Job type
     * @return int
     */
    public function pending(string $type = ''): int {
        return $this->adapter->pending($type);
    }

    /**
     * Reset failed jobs
     * @param  string $type Job type
     * @return $this
     */
    public function reset(string $type = '') {
        $this->adapter->reset($type);
        return $this;
    }

    /**
     * Purge failed jobs
     * @param  string $type Job type
     * @return $this
     */
    public function purge(string $type = '') {
        $this->adapter->purge($type);
        return $this;
    }

    /**
     * Do queue work
     * @param  Closure $callback Callback closure
     * @param  Closure $complete Completion closure
     * @return void
     */
    public function work(Closure $callback = null, Closure $complete = null): void {
        while (true) {
            if ( $this->pending() ) {
                $job = $this->get();
                try {
                    if ( $job->handle() === true ) {
                        $this->adapter->complete( $job->getUID() );
                    }
                } catch (Exception $e) {
                    $this->adapter->failed( $job->getUID() );
                }
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
    }
}
