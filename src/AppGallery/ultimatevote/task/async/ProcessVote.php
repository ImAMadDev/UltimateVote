<?php

declare(strict_types=1);

namespace AppGallery\ultimatevote\task\async;

final class ProcessVote extends VoteTask{

	public function __construct(string $url, string $username = '', private readonly bool $claim = false) {
		parent::__construct($username, $url);
	}

	public function getUsername(): string {
		return $this->username;
	}

	public function shouldClaim(): bool {
		return $this->claim;
	}

	public function execute(): bool|string {
		$req = curl_init($this->url);

		curl_setopt($req, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($req, CURLOPT_FORBID_REUSE, true);
		curl_setopt($req, CURLOPT_FRESH_CONNECT, true);

		curl_setopt($req, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($req, CURLOPT_SSL_VERIFYPEER, false);

		$response = curl_exec($req);
		curl_close($req);

		return $response;
	}

}