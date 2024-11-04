<?php
class SMS
{
    private $username = "9914878265";
    private $password = "3016ed17-89f9-4b71-9b74-1fc11cf6edcb";
    private function generateOTP()
    {
        $digits = '0123456789';
        $otp = '';
        for ($i = 0; $i < 6; $i++) {
            $otp .= $digits[rand(0, strlen($digits) - 1)];
        }
        return $otp;
    }

    public function sendSms(array $text, string $to, int $bodyId): string
    {
        $data = array('username' => $this->username, 'password' => $this->password, 'text' => implode(";", $text), 'to' => $to, "bodyId" => $bodyId);
        $post_data = http_build_query($data);
        $handle = curl_init('https://rest.payamak-panel.com/api/SendSMS/BaseServiceNumber');
        curl_setopt($handle, CURLOPT_HTTPHEADER, array(
            'content-type' => 'application/x-www-form-urlencoded'
        ));
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($handle, CURLOPT_POST, true);
        curl_setopt($handle, CURLOPT_POSTFIELDS, $post_data);
        $response = curl_exec($handle);
        return $response;
    }

    public function sendOtp($phone_number, $user_type = "normal")
    {
        $otp = $this->generateOTP();
        $response = '';
        switch ($user_type) {
            case "normal":
                $response = $this->sendSms([$otp], $phone_number, 261007);
                break;
            case "doctor":
                $response = $this->sendSms(["", $otp], $phone_number, 261020);
                break;

        }
        $response = json_decode($response, true);
        if (strlen($response['Value']) > 15) {
            return $otp;
        } else {
            return "";
        }
    }


    function successPaymentSms($name, $phone_number, $orders = "", $order_number = "", $order_amount = "", $user_type = "normal")
    {
        $response = '';
        switch ($user_type) {
            case "normal":
                $response = $this->sendSms([$name, $order_number, $order_amount], $phone_number, 261043);
                break;
            case "doctor":
                $response = $this->sendSms([$name, $order_number, $order_amount], $phone_number, 261045);
                break;
            case "patient":
                $response = $this->sendSms([$name, $orders], $phone_number, 261046);
                break;
        }

    }
}
