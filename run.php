<?php

ini_set('memory_limit', '-1');
set_time_limit(0);

class JumpGameUp
{
	private $_image = null;
	private $_imageInit = null;
	private $_width = 0;
	private $_height = 0;
	private $_CONF = [];
	private $_id = 0;
	private $_coordinate = [];
	private $_chessboardCoordinate = [];
	private $_coordinatePath = './coordinate.csv';
	
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
		//清空图片
		if (is_file($this->_coordinatePath)) {
			unlink($this->_coordinatePath);
		}
		$fp = fopen($this->_coordinatePath, 'w');
		for ($id = 1; ; $id++) {
			$this->_id = $id;
			echo sprintf("#%05d: ", $id);
			//0.扫描手机，获取图片
			$this->screenCap();
			//1.获取图片信息
			$this->setImage('screen.png');
			if (empty($this->_image)) {
				throw new Exception('设置screen图片信息失败', 404);
			}
			//2.扫描整张图片,获取棋子坐标，棋盘中心点坐标
			try {
				$this->scan();
			} catch (Exception $e) {
				throw $e;
			}
			if ($this->_CONF['DEBUG']) {
				$this->drawCircle($this->_imageInit, "./image/{$id}_img.png", $this->_coordinate['x'], $this->_coordinate['y'], 10, 10, 255);
				$this->drawCircle($this->_imageInit, "./image/{$id}_img.png", $this->_chessboardCoordinate['x'], $this->_chessboardCoordinate['y'], 10, 10, 184);
				
			}
			
			echo sprintf("chessCoordinate: (%02d,%02d)\n", $this->_coordinate['x'], $this->_coordinate['y']);
			echo sprintf("chessboardCoordinate: (%02d,%02d)\n", $this->_chessboardCoordinate['x'], $this->_chessboardCoordinate['y']);
			$data = [
				['序号', '坐标', '类型'],
				[$id, "({$this->_coordinate['x']},{$this->_coordinate['y']})", '棋子'],
				[$id, "({$this->_chessboardCoordinate['x']},{$this->_chessboardCoordinate['y']})", '棋盘'],
				['------------------------'],
			];
			foreach ($data as $value) {
				fputcsv($fp, $value);
			}
			//3.计算按压时间
			$time = $this->calcPressTime();
			
			//4.按压
			$this->press($time);
			//5.等待下一次截图
			sleep($this->_CONF['SLEEP_TIME']);
			imagedestroy($this->_image);
		}
		fclose($fp);
	}
	
	/**
	 *    扫描整个图片
	 *
	 * 1.获取棋子坐标，获取棋子极点坐标
	 * 2.去除图片杂质
	 * 3.获取棋盘坐标
	 * 4.获取棋盘中心点坐标
	 * @throws Exception
	 */
	public function scan()
	{
		//0.所有棋子坐标
		$coordinates = $this->getChessCoordinates();
		if (empty($coordinates)) {
			throw new Exception('没有发现棋子坐标集合', 404);
		}
		//1.棋子坐标
		//排序
		
		$this->_coordinate = $this->getChessCoordinate($coordinates);
//		$this->_coordinate['x'] = $this->_coordinate['x'] - $this->_CONF['CHESS_DIFF'];
		if (empty($this->_coordinate)) {
			throw new Exception('没有发现棋子', 404);
		}
		//2.棋子极点坐标
		usort($coordinates, function ($a, $b) {
			return $a['y'] <=> $b['y'];
		});
		$coordinateTop = array_shift($coordinates);
		if (empty($coordinateTop)) {
			throw new Exception('没有发现棋子极点坐标', 404);
		}
		//3.将图片二值化，去除图片背景
		//4.获取棋盘
		$chessboardCoordinates = $this->alphaImage($coordinateTop);
		if (empty($chessboardCoordinates)) {
			throw new Exception('没有发现棋盘坐标集合', 404);
		}
		//5.获取棋盘中心点坐标
		$this->_chessboardCoordinate = $this->getChessboardCoordinate($chessboardCoordinates);
		if (empty($this->_chessboardCoordinate)) {
			throw new Exception('没有发现棋盘坐标', 404);
		}
	}
	
	/**
	 * 返回所有与棋子RGB相似的坐标
	 * @return array
	 */
	private function getChessCoordinates(): array
	{
		$coordinates = [];
		for ($y = $this->_height / 3; $y < $this->_height / 4 * 3; $y++) {
			$y = (int)$y;
			for ($x = 0; $x < $this->_width; $x++) {
				$RGB = $this->getRGB($x, $y);
				//1.获取棋子
				//配置文件中，棋子的RGB
				if (abs($RGB['red'] - $this->_CONF['CHESS_RGB']['r']) < $this->_CONF['CHESS_RGB']['diff']
					&& abs($RGB['green'] - $this->_CONF['CHESS_RGB']['g']) < $this->_CONF['CHESS_RGB']['diff']
					&& abs($RGB['blue'] - $this->_CONF['CHESS_RGB']['b']) < $this->_CONF['CHESS_RGB']['diff']) {
					$coordinates[] = ['x' => $x, 'y' => $y];
//					$this->drawCircle($this->_imageInit,'./alpha/alpha.png',$x,$y,1,1,255);
				}
			}
		}
		return $coordinates;
	}
	
	/**
	 * 获取棋子坐标
	 * @param array $chessCoordinates
	 * @return array
	 */
	public function getChessCoordinate(array $chessCoordinates): array
	{
		usort($chessCoordinates, function ($a, $b) {
			return $a['x'] <=> $b['x'];
		});
		$left = array_shift($chessCoordinates);
		$right = array_pop($chessCoordinates);
		$x = $left['x'] + round(abs($right['x'] - $left['x']) / 2);
		$y = round(abs($right['y'] + $left['y']) / 2);
		return ['x' => $x, 'y' => $y];
	}
	
	/**
	 * 去除杂质，并获取棋盘坐标
	 * @param array $coordinateTop
	 * @return array
	 * @throws Exception
	 */
	public function alphaImage(array $coordinateTop): array
	{
		$chessboardCoordinates = [];
		$tempChessboard = [];
		$isChessboard = false;
		$col = imagecolorallocatealpha($this->_image, 0, 0, 0, 0);
		if ($this->_CONF['DEBUG']) {
			$this->drawCircle($this->_imageInit, "./image/{$this->_id}_img.png", $coordinateTop['x'], $coordinateTop['y'], 10, 10, 9,55,218);
		}
		imagefilledellipse($this->_image, $coordinateTop['x'], $coordinateTop['y'], 90, 300, $col);
		
		$bg = $this->getRGB($this->_width / 2, $this->_height / 5);
		for ($y = 0; $y < $this->_height; $y++) {
			for ($x = 0; $x < $this->_width; $x++) {
				
				$RGB = $this->getRGB($x, $y);
				
				if ($this->isSimilar($bg, $RGB, $this->_CONF['BG_DIFF'])) {
					imagesetpixel($this->_image, $x, $y, $col);
				}
				//在棋子坐标以下
				if ($y > ($coordinateTop['y'] + round(($this->_coordinate['y'] - $coordinateTop['y']) * ($this->_CONF['CHESS_RATIO'])))) {
					imagesetpixel($this->_image, $x, $y, $col);
				}
				//棋子在左边，x坐标左边全部去除
				if ($coordinateTop['x'] < round($this->_width / 2) && $x <= $coordinateTop['x']) {
					imagesetpixel($this->_image, $x, $y, $col);
				}
				//棋子在右边，x坐标右边全部去除
				if ($coordinateTop['x'] > round($this->_width / 2) && $x >= $coordinateTop['x']) {
					imagesetpixel($this->_image, $x, $y, $col);
				}
				
				//在区域之外
				if ($y < $this->_height / 3 || $y > $this->_height / 13 * 12) {
					imagesetpixel($this->_image, $x, $y, $col);
				}
				//获取所有棋盘坐标
				$RGB = $this->getRGB($x, $y);
				if ($RGB['red'] !== 0 && $RGB['green'] !== 0 && $RGB['blue'] !== 0) {
					//获取棋盘顶点坐标
					if (empty($isChessboard)) {
						$isChessboard = true;
						$tempChessboard = $RGB;
					}
					if (empty($tempChessboard)) {
						throw new Exception('未发现棋盘顶点坐标', 404);
					}
					if ($this->isSimilar($RGB, $tempChessboard, $this->_CONF['CHESSBOARD_DIFF'])) {
						$chessboardCoordinates[] = ['x' => $x, 'y' => $y];
					}
					
				}
			}
			
		}
		if ($this->_CONF['DEBUG']) {
			$this->drawCircle($this->_image, "./alphaed/alpha_img_all{$this->_id}.png", $coordinateTop['x'], $coordinateTop['y'], 0, 0, 255);
		}
		return $chessboardCoordinates;
	}
	
	/**
	 * 获取棋盘中心点坐标
	 * @param array $chessboardCoordinate
	 * @return array
	 */
	public function getChessboardCoordinate(array $chessboardCoordinate): array
	{
		//找出3个 极值坐标点，算出圆心坐标
		//数组的第一个为顶点坐标
		//最上面，y值最小
		//做左边，x值最小
		//左右表，x值最大
		$chessboardCoordinate1 = $chessboardCoordinate;
		$chessboardCoordinate2 = $chessboardCoordinate;
		uasort($chessboardCoordinate1, function ($a, $b) {
			return $a['y'] <=> $b['y'];
		});
		uasort($chessboardCoordinate2, function ($a, $b) {
			return $a['x'] <=> $b['x'];
		});
		$top = array_shift($chessboardCoordinate1);
		$left = array_shift($chessboardCoordinate2);

//		$x = (int)round(($right['x'] - $left['x']) / 2 + $left['x']);
		return ['x' => $top['x'], 'y' => $left['y']];
	}
	
	public function isSimilar(array $color1, array $color2, int $diff): bool
	{
		if (
			(abs($color1['red'] - $color2['red']) <= $diff) &&
			(abs($color1['green'] - $color2['green']) <= $diff) &&
			(abs($color1['blue'] - $color2['blue']) <= $diff)
		) {
			return true;
		}
		return false;
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
	public function drawCircle($image, string $name, int $x, int $y, int $width = 20, int $height = 20, int $r = 0, int $g = 0, int $b = 0, int $alpha = 0)
	{
		$col = imagecolorallocatealpha($image, $r, $g, $b, $alpha);
		imagefilledellipse($image, $x, $y, $width, $height, $col);
		imagepng($image, $name);
	}
	
	/**
	 * 设置图片信息
	 * @param string $pathname
	 */
	public function setImage(string $pathname)
	{
		$this->_image = imagecreatefrompng($pathname);
		$this->_imageInit = imagecreatefrompng($pathname);
		$this->_width = imagesx($this->_image);
		$this->_height = imagesy($this->_image);
	}
	
	/**
	 * 计算按压时间
	 * 勾股定理 获取两线距离* 按压系数
	 * @return int
	 */
	private function calcPressTime(): int
	{
		$time = sqrt(pow(abs($this->_coordinate['x'] - $this->_chessboardCoordinate['x']), 2)
				+ pow(abs($this->_coordinate['y'] - $this->_chessboardCoordinate['y']), 2)) * $this->_CONF['MAX_PRESS_RATIO'];
		
		return round($time);
	}
	
	
}

try {
	(new JumpGameUp())->run();
//	(new JumpGameUp())->screenCap();
} catch (Exception $e) {
	echo $e->getMessage();
}
