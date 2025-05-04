<?php

class Captcha
{
    public function CaptchaImage(): string
    {
        $captcha_code = '';
        $characters = '0987654321';
        $length = 5;

        // Generate captcha code
        for ($i = 0; $i < $length; $i++) {
            $captcha_code .= $characters[rand(0, strlen($characters) - 1)];
        }

        // Encrypt and store the captcha code in a cookie
        $_SESSION['captcha_code'] = md5($captcha_code);

        // Create image
        $image = imagecreatetruecolor(150, 50);
        $background_color = imagecolorallocate($image, rand(200, 255), rand(200, 255), rand(200, 255));
        imagefilledrectangle($image, 0, 0, 150, 50, $background_color);

        // Draw random lines
        for ($i = 0; $i < 6; $i++) {
            $line_color = imagecolorallocate($image, rand(100, 150), rand(100, 150), rand(100, 150));
            imageline($image, rand(0, 150), rand(0, 50), rand(0, 150), rand(0, 50), $line_color);
        }

        // Add captcha text to image
        for ($i = 0; $i < strlen($captcha_code); $i++) {
            $text_color = imagecolorallocate($image, rand(0, 150), rand(0, 150), rand(0, 150));
            $x = 10 + ($i * 30);
            $y = rand(20, 40);
            imagettftext($image, 22, rand(-20, 20), $x, $y, $text_color, XCPC_DIR . 'inc/assets/font/BYekan.ttf', $captcha_code[$i]);
        }

        // Capture the image as a Base64-encoded string
        ob_start();
        imagepng($image);
        $image_data = ob_get_clean();
        imagedestroy($image);

        return 'data:image/png;base64,' . base64_encode($image_data);
    }
}
