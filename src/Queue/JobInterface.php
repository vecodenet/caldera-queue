<?php

declare(strict_types = 1);

/**
 * Caldera Queue
 * Job queue layer, part of Vecode Caldera
 * @author  biohzrdmx <github.com/biohzrdmx>
 * @copyright Copyright (c) 2023 Vecode. All rights reserved
 */

namespace Caldera\Queue;

interface JobInterface {

    /**
     * Get job unique ID
     */
    public function getUID(): string;

    /**
     * Get job data
     * @param  string $key     Item key
     * @param  mixed  $default Item default
     */
    public function getData(string $key = '', mixed $default = null): mixed;

    /**
     * Get job retry count
     */
    public function getRetries(): int;

    /**
     * Has the job failed?
     */
    public function hasFailed(): bool;

    /**
     * The handler function, must be implemented
     */
    public function handle(): void;
}
