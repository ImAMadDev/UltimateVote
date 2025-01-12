<?php

declare(strict_types=1);

namespace AppGallery\ultimatevote\task\async;

use CurlHandle;

final class ProcessVote extends VoteTask{

	public function __construct(string $url, string $apiKey, string $username = '', private readonly bool $claim = false){
		parent::__construct($username, $url, $apiKey);
	}

	public function shouldClaim(): bool{
		return $this->claim;
	}

	public function execute(CurlHandle $request): bool|string{
		return curl_exec($request);
	}

}