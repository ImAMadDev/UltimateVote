<?php
$dir = explode('/', __DIR__);
$plugin_name = end($dir);
$file_phar = '/home/appgallery/Bit/plugins/' . $plugin_name . '.phar';

if (file_exists($file_phar)) {
    echo "Phar file already exists!";

    echo PHP_EOL;

    echo "overwriting...";

	try{
		Phar::unlinkArchive($file_phar);
	} catch(PharException $e){
        echo "couldn't overwriting phar file, reason: " . $e->getMessage();
        return;
	}
}

$files = [];
$dir = getcwd() . DIRECTORY_SEPARATOR;

$exclusions = [".idea", ".gitignore", "assets", "composer.json", "composer.lock", "build.php", ".git", "vendor", "composer.phar", "updateAndStart.sh"];

foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir)) as $path => $file) {
    $bool = true;
    foreach ($exclusions as $exclusion) {
        if (str_contains($path, $exclusion)) {
            $bool = false;
        }
    }

    if (!$bool) {
        continue;
    }

    if ($file->isFile() === false) {
        continue;
    }
    $files[str_replace($dir, "", $path)] = $path;
}

echo "Compressing..." . PHP_EOL;

$phar = new Phar($file_phar);
$phar->startBuffering();
$phar->setSignatureAlgorithm(Phar::SHA1);
$phar->buildFromIterator(new ArrayIterator($files));
$phar->compressFiles(Phar::GZ);
$phar->stopBuffering();
echo "end." . PHP_EOL;