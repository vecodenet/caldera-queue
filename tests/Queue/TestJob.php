<?php

declare(strict_types = 1);

/**
 * Caldera Queue
 * Job queue layer, part of Vecode Caldera
 * @author  biohzrdmx <github.com/biohzrdmx>
 * @copyright Copyright (c) 2023 Vecode. All rights reserved
 */

namespace Caldera\Tests\Queue;

use RuntimeException;

use Caldera\Queue\AbstractJob;

class TestJob extends AbstractJob {

	/**
	 * The handler function, must be implemented
	 * @return mixed
	 */
	public function handle() {
		$user = $this->getData('user');
		switch ($user->id) {
			case 1:
				return $this->complete();
			break;
			case 2:
				return $this->failed();
			break;
			case 3:
				if ($this->retries < 3) {
					return $this->retry();
				} else {
					return $this->failed();
				}
			break;
			case 4:
				throw new RuntimeException('Can not do');
			break;
		}
	}
}
