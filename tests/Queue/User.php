<?php

declare(strict_types = 1);

/**
 * Caldera Queue
 * Job queue layer, part of Vecode Caldera
 * @author  biohzrdmx <github.com/biohzrdmx>
 * @copyright Copyright (c) 2023 Vecode. All rights reserved
 */

namespace Caldera\Tests\Queue;

class User {

	protected $properties;

	public function __construct(int $id, string $email) {
		$this->id = $id;
		$this->email = $email;
	}

	public function __get(string $name): mixed {
		return $this->properties[$name] ?? null;
	}

	public function __set(string $name, mixed $value): void {
		$this->properties[$name] = $value;
	}
}
