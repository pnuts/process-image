<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

class ProcessImage {
    public function __construct() {}

    private function parseFileName($targetFile, $source = null) {
    	$paths = explode('/', $targetFile);
    	$targetName = array_pop($paths);

        $tmp = explode('.', $targetName);
        $targetType = array_pop($tmp);
        $process = array_pop($tmp);

        if($source) {
	    	$paths = explode('/', $source);
	    	$sourceName = array_pop($paths);

	    	if($sourceName) {
	    		// $source is image file.
	    		$sourceFile = $source;
	    	} else {
	    		// $source is dir value.
	    		$sourceName = implode('.', $tmp) . '.' . $targetType;
	    		$sourceFile = $source . $sourceName;
	    	}
	        $tmp = explode('.', $sourceName);
	        $sourceType = array_pop($tmp);
        } else {
        	// No source given. use target image path and default image.
        	$sourceType = $targetType; 
	        $sourceName = implode('.', $tmp) . '.' . $targetType;
        	$sourceFile = ($paths ? implode('/', $paths) . '/' : '') . $sourceName;
        }

        $return = [
            'sourceFile' => $sourceFile,
            'sourceName' => $sourceName,
            'sourceType' => $sourceType,

            'targetFile' => $targetFile,
            'targetType' => $targetType,
            'targetName' => $targetName,

            'process' => $process,
            'method' => ''
        ];
        if(preg_match('/(\w*)[_](\d+)x(\d+)/', $process, $matches)) {
            $return['method'] = $matches[1] ? $matches[1] : 'auto';
            $return['targetWidth'] = intval($matches[2]);
            $return['targetHeight'] = intval($matches[3]);
        } else {
            die();
        }
        return $return;
    }

    public function processImage($targetFile, $source = null) {
        extract($this->parseFileName($targetFile, $source));

        $imageType = exif_imagetype($sourceFile);
        switch ($imageType) {
            case IMAGETYPE_JPEG:
                $image = imagecreatefromjpeg($sourceFile);
                break;
            case IMAGETYPE_PNG:
                $image = imagecreatefrompng($sourceFile);
                break;
            case IMAGETYPE_GIF:
                $image = imagecreatefromgif($sourceFile);
                break;
            default:
                die("Load image error!");
        }
        $imageWidth = imagesx($image);
        $imageHeight = imagesy($image);

        if($method == 'auto' || $method == 'in') {
        	$targetPercentage = $targetWidth / $targetHeight;
        	if($imageWidth / $targetPercentage > $imageHeight) {
        		$sourceWidth = $imageHeight * $targetPercentage;
        		$sourceHeight = $imageHeight;
        	} else {
        		$sourceWidth = $imageWidth;
        		$sourceHeight = $imageWidth / $targetPercentage;
        	}
	        $x = ($imageWidth - $sourceWidth) / 2;
	        $y = ($imageHeight - $sourceHeight) / 2;
	        $x = intval($x > 0 ? $x : 0);
	        $y = intval($y > 0 ? $y : 0);
	    }

	    if($targetWidth > $imageWidth) {
	    	$targetWidth = $imageWidth;
	    	$targetHeight = $targetWidth / $targetPercentage;
	    } else if($targetHeight > $imageHeight) {
	    	$targetHeight = $imageHeight;
	    	$targetWidth = $targetHeight * $targetPercentage;
	    }

        $targetImage = imagecreatetruecolor($targetWidth, $targetHeight);
		imagecopyresampled($targetImage, $image, 0, 0, $x, $y, $targetWidth, $targetHeight, $sourceWidth, $sourceHeight);
        switch ($imageType) {
            case IMAGETYPE_JPEG:
                imagejpeg($targetImage, $targetFile, 100);
                break;
            case IMAGETYPE_PNG:
                imagepng($targetImage, $targetFile);
                break;
            case IMAGETYPE_GIF:
                imagegif($targetImage, $targetFile);
                break;
            default:
                break;
        }
    }
}

/* Image Name : {$sourceName}.{$width}x{$height}.{$type} */
$processImage = new ProcessImage();
$url = isset($_SERVER['REDIRECT_URI']) ? $_SERVER['REDIRECT_URI'] : $_SERVER['REDIRECT_URL'];
$processImage->processImage(__DIR__ . '/' . $url, __DIR__ . '/');
die();
