<?php

declare(strict_types=1);

namespace AppGallery\ultimatevote\task\async;

use CurlHandle;

class UpdateVote extends VoteTask{

	public function execute(CurlHandle $request): bool|string{
		curl_setopt($request, CURLOPT_POST, true);
		return curl_exec($request);
	}
}