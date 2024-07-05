<?php

declare(strict_types=1);

namespace AppGallery\ultimatevote\thread\task;

final class ProcessVote implements VoteTask{

	private string $username;
	private string $url;
	private mixed $result;

	public function __construct(string $url, string $username = '', private readonly bool $claim = false){
		$this->username = $username;
		$username = str_replace(' ', '%20', $username);
		$this->url = str_replace('{username}', $username, $url);
	}

	public function getUsername(): string{
		return $this->username;
	}

	public function shouldClaim(): bool{
		return $this->claim;
	}

	public function execute(string $key): bool|string{
		$req = curl_init(str_replace('{key}', $key, $this->url));

		curl_setopt($req, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($req, CURLOPT_FORBID_REUSE, true);
		curl_setopt($req, CURLOPT_FRESH_CONNECT, true);
		curl_setopt($req, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($req, CURLOPT_SSL_VERIFYPEER, false);

		$response = curl_exec($req);
		curl_close($req);

		return $response;
	}

	public function getResult(): mixed{
		return $this->result;
	}

	public function setResult(mixed $result): void{
		$this->result = $result;
	}

}