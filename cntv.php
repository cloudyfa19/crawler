<?php
require_once 'crawler.php';

class cntv extends crawler
{
	private $tmpPath;

	public function __construct($tmpPath)
	{
		$this->tmpPath = $tmpPath;
		parent::__construct();
	}

	protected function getName()
	{
		return "央视";
	}

	protected function parseHtml($html)
	{
		$list = [];
		$search_word = "jsload.src = '";
		$startpos = strpos($html, $search_word);
		$endpos = strpos($html, "&t=json", $startpos+1);
		$startpos = $startpos + strlen($search_word);
		$url = substr($html, $startpos, $endpos-$startpos);
		$url = sprintf("%s%s", "https:", str_replace("'+id+'", 1, $url));

		$content = $this->getFile($url);
		$content_arr = json_decode($content, true);
		if (!isset($content_arr['data']) || !isset($content_arr['data']['list'])){
			return;
		}

		foreach ($content_arr['data']['list'] as $key => $item)
		{
			$title = $item['title'];
			$guid = $item['guid'];

			$api = sprintf("http://vdn.apps.cntv.cn/api/getHttpVideoInfo.do?pid=%s", $guid);
			$content = $this->getFile($api);
			$content_arr = json_decode($content, true);
			if (isset($content_arr['video']['chapters'])){
				$urls = [];
				foreach($content_arr['video']['chapters'] as $item){
					$urls[] = $item['url'];
				}
			}

			$dir = $this->tmpPath.md5($title);
			mkdir($dir);

			if ($urls){
				$tss = [];
				foreach ($urls as $index => $url)
				{
					$tsfile =  $dir."/".$index.".ts";
					$tmpfile = $dir."/".basename($url);
					$content = $this->getFile($url);
					file_put_contents($tmpfile, $content);
					system(sprintf("ffmpeg -i %s -vcodec copy -acodec copy -vbsf h264_mp4toannexb %s", $tmpfile, $tsfile));
					$tss[] = $tsfile;
				}

				$file = $dir."/index.mp4";
				system(sprintf('ffmpeg -i "concat:%s" -acodec copy -vcodec copy -absf aac_adtstoasc %s', implode("|", $tss), $file));
				$list[$title] = $file;
			}
		}

		return $list;
	}

	protected function isLocalFileType(){
		return true;
	}
}

$cntv = new cntv("/tmp");
$cntv->doCrawl();
