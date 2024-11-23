<?php

declare(strict_types = 1);

/**
 * Caldera Queue
 * Job queue layer, part of Vecode Caldera
 * @author  biohzrdmx <github.com/biohzrdmx>
 * @copyright Copyright (c) 2023 Vecode. All rights reserved
 */

namespace Caldera\Tests\Queue;

class DummyJob {

	/**
	 * The handler function, must be implemented
	 */
	public function handle(): void {
		# This will not work
	}
}
