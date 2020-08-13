<?php
require_once 'crawler.php';

class ximalaya extends crawler
{
	public function __construct()
	{
		parent::__construct();
	}

	protected function getName()
	{
		return "喜马拉雅";
	}

	protected function parseHtml($content)
	{
		$doc = new DOMDocument();
		$doc->loadHTML('<meta charset="utf-8">'.$content);

		foreach ($doc->getElementsByTagName("a") as $item){
			$title = "";
			$isresource = $ishref = $isdownload = false;

			$parentNode = $item->parentNode;
			foreach ($parentNode->attributes as $attribute)
			{
				if ($attribute->name == "class" && $attribute->value == "text _Vc")
				{
					$isdownload = true;
				}
			}

			if (!$isdownload){
				continue;
			}

			foreach ($item->attributes as $attribute){
				if($attribute->name == "title"){
					$title = $attribute->value;
					$isresource = true;
				}

				if ($attribute->name == "href"){
					$href = $attribute->value;
					$ishref = true;
				}
			}

			if (!$isresource || !$ishref){
				continue;
			}

			//$title;
			$resource = $this->getResourcePath($href);
			$pathinfo = pathinfo($resource);
			if (!isset($pathinfo['extension'])){
				continue;
			}
			$filename = $title.".".$pathinfo['extension'];

			$list[$filename] = $resource;
		}

		return $list;
	}

	protected function isLocalFileType(){
		return false;
	}

	private function getResourcePath($href)
	{
		$hrefarr = explode("/", $href);
		$id = $hrefarr[count($hrefarr)-1];
		$url = sprintf("https://www.ximalaya.com/revision/play/v1/audio?id=%d&ptype=1", $id);
		$content = $this->getFile($url);
		$d = json_decode($content, true);
		if ($d)
		{
			return $d['data']['src'];
		}

		return "";
	}
}

$ximlaya = new ximalaya();
$ximlaya->doCrawl();
