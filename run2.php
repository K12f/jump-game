<?php

ini_set('memory_limit', '-1');

class JumpGameUp
{
	private $_image = null;
	private $_width = 0;
	private $_height = 0;
	private $_CONF = [];
	
	public function __construct()
	{
		$this->_CONF = $this->load();
	}
	
	public function load()
	{
		$conf = require_once 'config.php';
		return $conf;
	}
	
	/**
	 * @throws Exception
	 */
	public function run()
	{
		//0.获取图片信息
		$this->setImage('screen.png');
		if (empty($this->_image)) {
			throw new Exception('设置screen图片信息失败', 404);
		}
		//1.扫描整张图片
		$this->scan();
	}
	
	/**
	 * 设置图片信息
	 * @param string $pathname
	 */
	public function setImage(string $pathname)
	{
		$this->_image = imagecreatefrompng($pathname);
		$this->_width = imagesx($this->_image);
		$this->_height = imagesy($this->_image);
	}
	
	/**
	 * 扫描整个图片
	 */
	public function scan()
	{
		//初始化
		$coordinates = [];
		$image = imagecreatefrompng('./image/screen.png');
		// 左右优先扫描图片
		for ($y = $this->_height / 3; $y < $this->_height / 4 * 3; $y++) {
			$y = (int)$y;
			for ($x = 0; $x < $this->_width; $x++) {
				
				$this->drawCircle($image, './image/screen.png', $x, $y, 1, 1);
				
//				$RGB = $this->getRGB($x, $y);
				
				//1.获取棋子
				//配置文件中，棋子的RGB
//				$chessRGB = $this->_CONF['CHESS_RGB'];
//				if(in_array(['x' => $x, 'y' => $y],$coordinates)){
//				    continue;
//				}
//				if (abs($RGB['red'] - $chessRGB['r']) < $chessRGB['diff']
//					&& abs($RGB['green'] - $chessRGB['g']) < $chessRGB['diff']
//					&& abs($RGB['blue'] - $chessRGB['b']) < $chessRGB['diff']) {
//					$this->drawCircle($image, './image/screen.png', $x, $y, 1, 1);
//					$coordinates[] = ['x' => $x, 'y' => $y];
//				}
				
				
				//2.将图片二值化，去除杂质
				
				
				//3.获取棋盘
			}
		}
		imagedestroy($image);
		
		print_r($coordinates);
	}
	
	/**
	 * 返回与棋子RGB类似的所有坐标
	 * @param int $x
	 * @param int $y
	 * @return array
	 */
	private function getChess(int $x, int $y): array
	{
		$coordinate = [];
		$RGB = $this->getRGB($x, $y);
		//配置文件中，棋子的RGB
		$chessRGB = $this->_CONF['CHESS_RGB'];
		
		if (abs($RGB['red'] - $chessRGB['r']) < $chessRGB['diff']
			&& abs($RGB['green'] - $chessRGB['g']) < $chessRGB['diff']
			&& abs($RGB['blue'] - $chessRGB['b']) < $chessRGB['diff']) {
			$coordinate = ['x' => $x, 'y' => $y];
		}
		return $coordinate;
	}
	
	/**
	 * 获取某个点的RGB 值
	 * @param int $x
	 * @param int $y
	 * @return array
	 */
	private function getRGB(int $x, int $y): array
	{
		$colorIndex = imagecolorat($this->_image, $x, $y);
		$colorRGB = imagecolorsforindex($this->_image, $colorIndex);
		return $colorRGB;
	}
	
	/**
	 * 截图
	 */
	public function screenCap()
	{
		system('adb shell screencap -p /sdcard/screen.png');
		system('adb pull /sdcard/screen.png .');
	}
	
	/**
	 * 按键
	 * @param int $time
	 */
	public function press(int $time)
	{
		system('adb shell input swipe 500 500 500 501 ' . $time);
	}
	
	/**
	 * 画一个圆圈，并保存图片
	 * @param $image
	 * @param string $name
	 * @param int $x
	 * @param int $y
	 * @param int $width
	 * @param int $height
	 * @param int $r
	 * @param int $g
	 * @param int $b
	 * @param int $alpha
	 */
	public function drawCircle($image, string $name, int $x, int $y, int $width = 20, int $height = 20, int $r = 255, int $g = 0, int $b = 0, int $alpha = 0)
	{
		$col = imagecolorallocatealpha($image, $r, $g, $b, $alpha);
		imagefilledellipse($image, $x, $y, $width, $height, $col);
		imagepng($image, $name);
	}
	
}

try {
	(new JumpGameUp())->run();
} catch (Exception $e) {
	echo $e->getMessage();
}
