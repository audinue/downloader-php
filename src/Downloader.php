<?php

namespace audinue;

class Downloader {

	private $userAgent = 'Mozilla/5.0 (Windows NT 6.1; rv:46.0) Gecko/20100101 Firefox/46.0';
	private $cacheFile = 'downloader-cache.db';
	private $pdo;

	function cacheFile($cacheFile = null) {
		if(is_null($cacheFile)) {
			return $this->cacheFile;
		}
		$this->cacheFile = $cacheFile;
		if(!is_null($this->pdo)) {
			$this->pdo = null;
		}
		return $this;
	}
	
	function userAgent($userAgent = null) {
		if(is_null($userAgent)) {
			return $this->userAgent;
		}
		$this->userAgent = $userAgent;
		return $this;
	}

	private function pdo() {
		if(is_null($this->pdo)) {
			$this->pdo = new \PDO("sqlite:{$this->cacheFile}");
			$this->pdo->exec("CREATE TABLE cache (url PRIMARY KEY, contents)");
		}
		return $this->pdo;
	}
	
	private function inCache($url) {
		$statement = $this->pdo()->prepare('SELECT 1 FROM cache WHERE url = ?');
		$statement->execute([$url]);
		return $statement->fetchColumn();
	}
	
	private function getContentsFromCache($url) {
		$statement = $this->pdo()->prepare('SELECT contents FROM cache WHERE url = ?');
		$statement->execute([$url]);
		return $statement->fetchColumn();
	}
	
	private function putContentsToCache($url, $contents) {
		$statement = $this->pdo()->prepare('INSERT INTO cache (url, contents) VALUES (?, ?)');
		$statement->execute([$url, $contents]);
	}
	
	function download($url, $file = null) {
		if($this->inCache($url)) {
			if(is_null($file)) {
				return $this->getContentsFromCache($url);
			}
			file_put_contents($file, $this->getContentsFromCache($url));
			return $this;
		}
		$contents = @file_get_contents($url, false, stream_context_create([
			'http' => [
				'user_agent' => $this->userAgent,
			]
		]));
		if($contents === false) {
			throw new \Exception("Unable to download {$url}.");
		}
		$this->putContentsToCache($url, $contents);
		if(is_null($file)) {
			return $contents;
		}
		file_put_contents($file, $contents);
		return $this;
	}
}
