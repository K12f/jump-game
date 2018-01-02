<?php

ini_set('memory_limit', '-1');

class JumpGame
{
	const PRESS_TIME = 2.1;
	const SLEEP_TIME = 2;
	private $_chessRGB = [
		'r' => 54,
		'g' => 60,
		'b' => 102,
	];
	/**
	 * @var null
	 */
	private $_image = null;
	private $_width = 0;
	private $_height = 0;
	
	/**
	 * 获取所有棋子相似的坐标
	 * @return array
	 */
	private function getChessCoordinates(): array
	{
		$coordinates = [];
		for ($i = 0; $i < $this->_width; $i++) {
			for ($j = $this->_height / 3; $j <= $this->_height / 4 * 3; $j++) {
				//
				$colorIndex = imagecolorat($this->_image, $i, $j);
				$colorRGB = imagecolorsforindex($this->_image, $colorIndex);
				$red = $colorRGB['red'];
				$green = $colorRGB['green'];
				$blue = $colorRGB['blue'];
				if (abs($red - $this->_chessRGB['r']) < 10 && abs($green - $this->_chessRGB['g']) < 10 && abs($blue - $this->_chessRGB['b']) < 10) {
					$coordinates[] = ['x' => $i, 'y' => (int)$j];
				}
			}
		}
		return $coordinates;
	}
	
	
	/**
	 * 获取棋子的坐标
	 */
	public function getChess()
	{
		$coordinates = $this->getChessCoordinates();
		
		$xSum = 0;
		$ySum = 0;
		$count = 0;
		foreach ($coordinates as $item => $coordinate) {
			$xSum += $coordinate['x'];
			$ySum += $coordinate['y'];
			$count++;
		}
		$x = (int)round($xSum / $count);
		$y = (int)round($ySum / $count) + 10;
		return ['x' => $x, 'y' => $y];
	}
	
	
	/**
	 * 去除图片杂质
	 * @param string $alphaName
	 * @param array $chessCoordinate
	 */
	public function alphaImage(string $alphaName, array $chessCoordinate)
	{
		$im_dst = $im_src = $this->_image;
		
		
		for ($x = 0; $x < $this->_width; $x++) {
			for ($y = 0; $y < $this->_height; $y++) {
				
				$alpha = (imagecolorat($im_src, $x, $y) >> 24 & 0xFF);
				$col = imagecolorallocatealpha($im_dst, 0, 0, 0, $alpha);
				
				
				$colorIndex = imagecolorat($im_dst, $x, $y);
				$colorRGB = imagecolorsforindex($im_dst, $colorIndex);
				$red = $colorRGB['red'];
				$green = $colorRGB['green'];
				$blue = $colorRGB['blue'];
				
				// bg : 185-220,  185-220, 200-230
				
				//up
				// 255 218 214
				//shadow: 130-145,130-145,140-155
				
				
				//在棋子坐标以下
				if ($y > $chessCoordinate['y']) {
					imagesetpixel($im_dst, $x, $y, $col);
				}
			
				//在区域之外
				if ($y < $this->_height / 3 || $y > $this->_height / 13 * 12) {
					imagesetpixel($im_dst, $x, $y, $col);
				}
				
				if (($red >= 185 && $red <= 220)
					&& ($green >= 185 && $green <= 220)
					&& ($blue >= 200 && $blue <= 230)) {
					imagesetpixel($im_dst, $x, $y, $col);
					//shadow
				} elseif (($red >= 130 && $red <= 145)
					&& ($green >= 130 && $green <= 145)
					&& ($blue >= 140 && $blue <= 155)) {
					//区域内，颜色是bg或者shadow
					imagesetpixel($im_dst, $x, $y, $col);
					
				} elseif (($red >= 170 && $red <= 180)
					&& ($green >= 140 && $green <= 150)
					&& ($blue >= 140 && $blue <= 150)) {
					//区域内，颜色是bg或者shadow
					imagesetpixel($im_dst, $x, $y, $col);
					
				}elseif ($red >=250
					//bg
					&& ($green >= 200 && $green <= 220)
					&& ($blue >= 200 && $blue <= 220)) {
					imagesetpixel($im_dst, $x, $y, $col);
				}
				//棋子本身
			}
			
		}
		$this->drawCircle($im_dst,$alphaName,$chessCoordinate['x'], $chessCoordinate['y'],300,250,0, 0, 0, 0);
//		$black = imagecolorallocatealpha($im_dst, 0, 0, 0, 0);
//		imagefilledellipse($im_dst, $chessCoordinate['x'], $chessCoordinate['y'], 200, 250, $black);
//		imagedestroy($im_dst);
	}
	
	
	/**
	 * 扫描获取所有棋盘坐标
	 * @param string $alphaName
	 * @param array $chessCoordinate
	 * @return array
	 */
	public function scanChessboard(string $alphaName, array $chessCoordinate): array
	{
		$image = imagecreatefrompng($alphaName);
		$chessboardCoordinate = [];
		for ($x = 0; $x < $this->_width; $x++) {
			for ($y = $this->_height / 3; $y < $chessCoordinate['y']; $y++) {
				$y = (int)$y;
				$colorIndex = imagecolorat($image, $x, $y);
				$colorRGB = imagecolorsforindex($image, $colorIndex);
				$red = $colorRGB['red'];
				$green = $colorRGB['green'];
				$blue = $colorRGB['blue'];
				if ($red !== 0 && $green !== 0 && $blue !== 0) {
					$chessboardCoordinate[] = ['x' => $x, 'y' => $y];
				}
			}
		}
		return $chessboardCoordinate;
	}
	
	/**
	 * 获取
	 * @param array $chessboardCoordinate
	 * @return array
	 */
	public function getChessboardCoordinate(array $chessboardCoordinate): array
	{
		//找出3个 极值坐标点，算出圆心坐标
		//最上面，y值最小
		//做左边，x值最小
		//左右表，x值最大
		
		$chessboardCoordinateCp1 = $chessboardCoordinate;
		$chessboardCoordinateCp2 = $chessboardCoordinate;
		
		uasort($chessboardCoordinateCp1, function ($a, $b) {
			return $a['y'] <=> $b['y'];
		});
		uasort($chessboardCoordinateCp2, function ($a, $b) {
			return $a['x'] <=> $b['x'];
		});
		
		$left = array_shift($chessboardCoordinateCp2);
		$right = array_pop($chessboardCoordinateCp2);
		
		$x = (int)round(($right['x'] - $left['x']) / 2 + $left['x']);
		return ['x' => $x, 'y' => $left['y']];
	}
	
	public function setImage($pathname)
	{
		$this->_image = imagecreatefrompng($pathname);
		$this->_width = imagesx($this->_image);
		$this->_height = imagesy($this->_image);
	}
	
	/**
	 * run
	 */
	public function run()
	{
		$id = 0;
		while (true) {
			
			// 截图
			$this->screenCap();
			$this->setImage('screen2.png');
			$image = imagecreatefrompng('screen2.png');
			
			echo sprintf("#%05d: ", $id);
			$chessCoordinate = $this->getChess();
//			$this->drawCircle($this->_image, $chessCoordinate['x'], $chessCoordinate['y']);
			$alphaName = './image/alpha.png';
			$this->alphaImage($alphaName, $chessCoordinate);
			$chessboardCoordinates = $this->scanChessboard($alphaName, $chessCoordinate);
			
			$chessboardCoordinate = $this->getChessboardCoordinate($chessboardCoordinates);
			
			$this->drawCircle($image,"./image/test-coordinate$id.png", $chessCoordinate['x'], $chessCoordinate['y'], 20, 20,0,0,0);
			
			echo sprintf("chessCoordinate: (%02d,%02d)\n", $chessCoordinate['x'], $chessCoordinate['y']);
			
			echo sprintf("chessboardCoordinate: (%02d,%02d)\n", $chessboardCoordinate['x'], $chessboardCoordinate['y']);
			
			$this->drawCircle($image,"./image/test-chessboardCoordinate$id.png", $chessboardCoordinate['x'], $chessboardCoordinate['y']);
			
			// 计算按压时间
			$time = sqrt(pow(abs($chessCoordinate['x'] - $chessboardCoordinate['x']), 2) + pow(abs($chessCoordinate['y'] - $chessboardCoordinate['y']), 2)) * static::PRESS_TIME;
			$time = round($time);
			
			echo sprintf("time: %f\n", $time);
			$this->press($time);
			// 等待下一次截图
			$id++;
			sleep(static::SLEEP_TIME);
			exit();
		}
		
	}
	
	public function screenCap()
	{
		ob_start();
		system('adb shell screencap -p /sdcard/screen2.png');
		system('adb pull /sdcard/screen2.png .');
		ob_end_clean();
	}
	
	public function press(int $time)
	{
		system('adb shell input swipe 500 500 500 501 ' . $time);
	}
	
	public function drawCircle($image, string $name,int $x, int $y, int $width = 20, int $height = 20, int $r = 255, int $g = 0, int $b = 0, int $alpha = 0)
	{
		$col = imagecolorallocatealpha($image, $r, $g, $b, $alpha);
		imagefilledellipse($image, $x, $y, $width, $height, $col);
		imagepng($image, $name);
//		imagedestroy($image);
	}
}



$jump = new JumpGame();
$jump->run();

//$alphaName = './image/alpha.png';
//$image = imagecreatefrompng('screen2.png');

//$jump->setImage('screen2.png');
//$chessCoordinate = $jump->getChess();
//$jump->drawCircle($image,'test-coordinate.png', $chessCoordinate['x'], $chessCoordinate['y'], 300, 500,0,0,0,100);
//
//var_dump($chessCoordinate);
//$jump->alphaImage($alphaName, $chessCoordinate);
//$chessboardCoordinates = $jump->scanChessboard($alphaName, $chessCoordinate);
////var_dump($chessboardCoordinates);
//$chessboardCoordinate = $jump->getChessboardCoordinate($chessboardCoordinates);
//
//var_dump($chessboardCoordinate);
//
//$jump->drawCircle($image,'test-chessboardCoordinate.png', $chessboardCoordinate['x'], $chessboardCoordinate['y'], 20, 20, 255, 255, 255);

//
