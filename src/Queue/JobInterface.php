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
     * @return string
     */
    public function getUID(): string;

    /**
     * Get job data
     * @param  string $key     Item key
     * @param  mixed  $default Item default
     * @return mixed
     */
    public function getData(string $key = '', $default = null);

    /**
     * Get job retry count
     * @return int
     */
    public function getRetries(): int;

    /**
     * Has the job failed?
     * @return bool
     */
    public function hasFailed(): bool;

    /**
     * The handler function, must be implemented
     * @return mixed
     */
    public function handle();
}