<?php

declare(strict_types = 1);

/**
 * Caldera Queue
 * Job queue layer, part of Vecode Caldera
 * @author  biohzrdmx <github.com/biohzrdmx>
 * @copyright Copyright (c) 2023 Vecode. All rights reserved
 */

namespace Caldera\Queue;

use InvalidArgumentException;

abstract class AbstractJob implements JobInterface, QueueAwareInterface {

    use QueueAware;

    /**
     * Job data
     */
    protected array $data = [];

    /**
     * Job retry count
     */
    protected int $retries;

    /**
     * Job unique ID
     */
    protected string $uid;

    /**
     * Failed flag
     */
    protected bool $failed;

    /**
     * Constructor
     * @param string $uid     Job unique ID
     * @param array  $data    Job data
     * @param int    $retries Job retry count
     */
    public function __construct(string $uid, array $data = [], int $retries = 0) {
        if (! $uid ) throw new InvalidArgumentException('UID can not be empty');
        $this->uid = $uid;
        $this->data = $data;
        $this->retries = (int) $retries;
        $this->failed = false;
    }

    /**
     * @inheritdoc
     */
    public function getUID(): string {
        return $this->uid;
    }

    /**
     * @inheritdoc
     */
    public function getData(string $key = '', mixed $default = null): mixed {
        return $key ? ( $this->data[$key] ?? $default ) : $this->data;
    }

    /**
     * @inheritdoc
     */
    public function getRetries(): int {
        return $this->retries;
    }

    /**
     * @inheritdoc
     */
    public function hasFailed(): bool {
        return $this->failed;
    }

    /**
     * Complete the job
     */
    public function complete(): bool {
        $this->queue->complete($this);
        return true;
    }

    /**
     * Fail the job
     */
    public function failed(): bool {
        $this->failed = true;
        $this->queue->failed($this);
        return false;
    }

    /**
     * Retry the job
     */
    public function retry(): void {
        $this->queue->retry($this);
    }
}
