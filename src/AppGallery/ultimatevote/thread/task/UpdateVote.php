<?php

declare(strict_types=1);

namespace AppGallery\ultimatevote\thread\task;

class UpdateVote implements VoteTask{

	private string $url;
	private string $username;
	private mixed $result;

	public function __construct(string $url, string $username){
		$this->username = $username;
		$username = str_replace(' ', '%20', $username);
		$this->url = str_replace('{username}', $username, $url);
	}

	public function getUsername(): string{
		return $this->username;
	}

	public function execute(string $key): bool|string{
		$req = curl_init(str_replace('{key}', $key, $this->url));

		curl_setopt($req, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($req, CURLOPT_FORBID_REUSE, true);
		curl_setopt($req, CURLOPT_FRESH_CONNECT, true);
		curl_setopt($req, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($req, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($req, CURLOPT_POST, true);

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