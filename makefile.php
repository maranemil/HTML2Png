<?php /** @noinspection SpellCheckingInspection */

ini_set('memory_limit', '128M');

if (isset($GLOBALS["HTTP_RAW_POST_DATA"])) {
    // Get the data
    $imageData = $GLOBALS['HTTP_RAW_POST_DATA'];
    $domain = $_GET["domain"];

    // Remove the headers (data:,) part.
    // A real application should use them according to needs such as to check image type
    $filteredData = substr($imageData, strpos($imageData, ",") + 1);

    // Need to decode before saving since the data we received is already base64 encoded
    $unencodedData = base64_decode($filteredData);

    // Save file.  This example uses a hard coded filename for testing,
    // but a real application can specify filename in POST variable
    //$fp = fopen("images/genimg".date("YmdHis").".png", 'w');
    //$fp = fopen("images/img_".microtime(1).".png", 'w');
    $imgSrc = "images/" . $domain . ".png";
    $fp = fopen($imgSrc, 'wb');

    fwrite($fp, $unencodedData);
    fclose($fp);

    sleep(1);

    class ImgResizer
    {

        public $originalFile = '';

        /**
         * ImgResizer constructor.
         *
         * @param string $originalFile
         */
        public function __construct($originalFile = '')
        {
            $this->originalFile = $originalFile;
        }

        public function resize($newWidth, $targetFile)
        {
            if (empty($newWidth) || empty($targetFile)) {
                return false;
            }
            //$src = imagecreatefromjpeg($this -> originalFile);
            $src = imagecreatefrompng($this->originalFile);

            list($width, $height) = getimagesize($this->originalFile);
            $newHeight = ($height / $width) * $newWidth;
            $tmp = imagecreatetruecolor($newWidth, $newHeight);
            imagecopyresampled($tmp, $src, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            if (file_exists($targetFile)) {
                unlink($targetFile);
            }
            //imagejpeg($tmp, $targetFile, 85);
            imagepng($tmp, $targetFile, 2);
            /*
            Fix it easily by changing your compression variable of imagepng, imagegif or imagejpg/imagejpeg into a 1-10 ranged number – instead of the PHP 4 1-100 standard.
            */
            return true;
        }
    }

    $work = new ImgResizer($imgSrc); // me.jpg (800x600) is in directory ‘img’ in the same path as this php script.
    $work->resize('500', $imgSrc); // the old me.jpg (800x600) is now replaced and overwritten with a smaller me.jpg (400x300).

    // To store resized image to a new file thus retaining the 800x600 version of me.jpg, go with this instead:
    // $work -> resize(400, 'img/me_smaller.jpg');

    /*
    function resize($newWidth, $targetFile) {
        $info = getimagesize($originalFile);
        $mime = $info['mime'];

        switch ($mime) {
                case 'image/jpeg':
                        $image_create_func = 'imagecreatefromjpeg';
                        $image_save_func = 'imagejpeg';
                        $new_image_ext = 'jpg';
                        break;

                case 'image/png':
                        $image_create_func = 'imagecreatefrompng';
                        $image_save_func = 'imagepng';
                        $new_image_ext = 'png';
                        break;

                case 'image/gif':
                        $image_create_func = 'imagecreatefromgif';
                        $image_save_func = 'imagegif';
                        $new_image_ext = 'gif';
                        break;

                default:
                        throw Exception('Unknown image type.');
        }

        $img = $image_create_func($targetFile);

        $newHeight = ($height / $width) * $newWidth;
        $tmp = imagecreatetruecolor($newWidth, $newHeight);
        imagecopyresampled($tmp, $src, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

    */

    echo "processed!";
}