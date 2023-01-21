<?php

declare(strict_types = 1);

/**
 * Caldera Queue
 * Job queue layer, part of Vecode Caldera
 * @author  biohzrdmx <github.com/biohzrdmx>
 * @copyright Copyright (c) 2023 Vecode. All rights reserved
 */

namespace Caldera\Queue;

use Caldera\Queue\QueueAware;
use Caldera\Queue\QueueAwareInterface;
use Caldera\Queue\JobInterface;

abstract class AbstractJob implements JobInterface, QueueAwareInterface {

    use QueueAware;

    /**
     * Job data
     * @var array
     */
    protected $data = [];

    /**
     * Job retry count
     * @var int
     */
    protected $retries;

    /**
     * Job unique ID
     * @var string
     */
    protected $uid;

    /**
     * Failed flag
     * @var bool
     */
    protected $failed;

    /**
     * Constructor
     * @param string $uid     Job unique ID
     * @param array  $data    Job data
     * @param int    $retries Job retry count
     */
    public function __construct(string $uid, array $data = [], int $retries = 0) {
        $this->uid = $uid;
        $this->data = $data;
        $this->retries = (int) $retries;
        $this->failed = false;
    }

    /**
     * Get job unique ID
     * @return string
     */
    public function getUID(): string {
        return $this->uid;
    }

    /**
     * Get job data
     * @param  string $key     Item key
     * @param  mixed  $default Item default
     * @return mixed
     */
    public function getData(string $key = '', $default = null) {
        return $key ? ( $this->data[$key] ?? $default ) : $this->data;
    }

    /**
     * Get job retry count
     * @return int
     */
    public function getRetries(): int {
        return $this->retries;
    }

    /**
     * Has the job failed?
     * @return bool
     */
    public function hasFailed(): bool {
        return $this->failed;
    }

    /**
     * Complete the job
     * @return bool
     */
    public function complete(): bool {
        $adapter = $this->queue->getAdapter();
        $adapter->complete($this->uid);
        return true;
    }

    /**
     * Fail the job
     * @return bool
     */
    public function failed(): bool {
        $adapter = $this->queue->getAdapter();
        $adapter->failed($this->uid);
        $this->failed = true;
        return false;
    }

    /**
     * Retry the job
     * @return void
     */
    public function retry(): void {
        $adapter = $this->queue->getAdapter();
        $adapter->retry($this->uid);
    }
}