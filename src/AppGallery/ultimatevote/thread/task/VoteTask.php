<?php

declare(strict_types=1);

namespace AppGallery\ultimatevote\thread\task;

interface VoteTask{

	public function execute(string $key): bool|string;

	public function getUsername(): string;

	public function setResult(mixed $result): void;

	public function getResult(): mixed;
}