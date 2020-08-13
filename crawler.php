<?php
ini_set('max_execution_time', 0);

abstract class crawler{
	private $tm;
	private $url;

	public function __construct()
	{
		$this->tm = time();
		$this->url = $_POST['url'];
		if (!$this->url){
			echo $this->generateHtml(true);
		}
	}

	public function doCrawl(){
		$content = $this->getFile($this->url);

		$list = $this->parseHtml($content);
		if (!$list)
		{
			echo $this->generateHtml(false);
		}

		$this->zipAndDownload($list);
	}

	protected function getFile($url)
	{
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE); // https请求 不验证证书和hosts
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION,1);
		$data = curl_exec($curl);
		curl_close($curl);
		return $data;
	}

	protected function getLocalFile($path)
    {
	    return file_get_contents($path);
    }

	private function generateHtml($isSuccess)
	{
		if (!$isSuccess)
		{
			echo '<font color="red">'.$this->getName().'url不正确</font><br />';
		}
		?>
		<html>
		<header>
		</header>
		<body>
		<form method="post">
			请输入<?php echo $this->getName() ?> url: <input type="text" name="url" size="100"><input type="submit" value="提交">
		</form>
		</body>
		</html>
		<?php
		exit;
	}

	protected function zipAndDownload($list)
	{
		mkdir($this->tm);
		if (!$this->isLocalFileType())
		{
			foreach($list as $filename => $resource)
			{
				file_put_contents($this->tm . "/" . $filename, $this->getFile($resource));
			}
		}

		$zip = new ZipArchive;
		$zip_file = $this->tm.".zip";
		$zip->open($zip_file, ZIPARCHIVE::CREATE);
		foreach ($list as $filename => $resource)
		{
		    if ($this->isLocalFileType())
            {
	            $zip->addFile($resource);
            }
            else
            {
	            $zip->addFile($this->tm."/".$filename);
            }
		}
		$zip->close();
		header("location: ".$zip_file, true);
	}

	abstract protected function getName();
	abstract protected function parseHtml($doc);
	abstract protected function isLocalFileType();
}
