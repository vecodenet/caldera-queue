<?php

declare(strict_types = 1);

/**
 * Caldera Queue
 * Job queue layer, part of Vecode Caldera
 * @author  biohzrdmx <github.com/biohzrdmx>
 * @copyright Copyright (c) 2023 Vecode. All rights reserved
 */

namespace Caldera\Queue\Adapter;

use Caldera\Queue\JobInterface;

interface AdapterInterface {

    /**
     * Add a job to the queue
     * @param  string $type Job type
     * @param  mixed  $data Job data
     */
    public function add(string $type, mixed $data): string;

    /**
     * Get a pending job to work on it
     */
    public function get(): ?JobInterface;

    /**
     * Delete the specified job from the queue (as it is completed)
     * @param  string $uid Job identifier
     */
    public function complete(string $uid): void;

    /**
     * Mark the specified job as failed
     * @param  string $uid Job identifier
     */
    public function failed(string $uid): void;

    /**
     * Retry an specific job
     * @param  string $uid Job identifier
     */
    public function retry(string $uid): void;

    /**
     * Get the pending job count
     * @param  string $type Job type
     */
    public function pending(string $type = ''): int;

    /**
     * Reset failed jobs
     * @param  string $type Job type
     */
    public function reset(string $type = ''): void;

    /**
     * Purge failed jobs
     * @param  string $type Job type
     */
    public function purge(string $type = ''): void;
}
