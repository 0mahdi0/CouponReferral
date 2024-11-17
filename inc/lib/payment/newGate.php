<?php



if (!class_exists('WC_Payment_Gateway')) {
    return;
}
class Payment_Melli
{
    private string $terminal_id;
    private string $merchant_id;
    private string $terminal_key;
    private $settings;

    public function __construct()
    {

        $payment_gateways = WC_Payment_Gateways::instance();

        // Get the gateway instance by ID
        $gateway = $payment_gateways->payment_gateways()["melli_pay"] ?? null;
        if ($gateway) {
            // Access and return the gateway's settings
            $this->settings = $gateway->settings;
            $this->terminal_id = $gateway->settings['terminal_id'];
            $this->merchant_id = $gateway->settings['merchant_id'];
            $this->terminal_key = $gateway->settings['terminal_key'];
        }
    }

    public function CreatePayLink($order_id)
    {
        $order = new WC_Order($order_id);
        $currency = $order->get_order_currency();
        $Amount = $this->get_price(intval($order->order_total), $currency);

        $terminal_id = $this->terminal_id;
        $merchant_id = $this->merchant_id;
        $terminal_key = $this->terminal_key;
        global $wp;
        $callBackUrl = home_url($wp->request) . "/payment_verify";

        $sign_data = $this->sadad_encrypt($terminal_id . ';' . $order_id . ';' . $Amount, $terminal_key);
        $parameters = array(
            'MerchantID' => $merchant_id,
            'TerminalId' => $terminal_id,
            'Amount' => $Amount,
            'OrderId' => $order_id,
            'LocalDateTime' => date('Ymdhis'),
            'ReturnUrl' => $callBackUrl,
            'SignData' => $sign_data,
        );
        $result = $this->sadad_call_api('https://sadad.shaparak.ir/VPG/api/v0/Request/PaymentRequest', $parameters);
        if ($result != false) {
            if ($result->ResCode == 0) {
                //header('Location: https://sadad.shaparak.ir/VPG/Purchase?Token=' . $res->Token);
                return ["status" => true, "message" => '<form id="redirect_to_melli" method="get" action="https://sadad.shaparak.ir/VPG/Purchase" style="display:none !important;"  ><input type="hidden"  name="Token" value="' . $result->Token . '" /><input type="submit" value="Pay"/></form><script language="JavaScript" type="text/javascript">document.getElementById("redirect_to_melli").submit();</script>'];
            } else {
                return ["status" => false, "message" => 'خطا در برقراری ارتباط با بانک! ' . $this->sadad_request_err_msg($result->ResCode)];
            }
        } else {
            return ["status" => false, "message" => 'خطا! برقراری ارتباط با بانک امکان پذیر نیست.'];
        }
    }

    public function paymentVerify()
    {
        global $woocommerce;

        if (isset($_POST['OrderId'])) {
            $order_id = $_POST['OrderId'];
        } elseif (isset($_GET['wc_order'])) {
            $order_id = $_GET['wc_order'];
        } else {
            $error_msg = __('شماره سفارش وجود ندارد.', 'woocommerce');
            return ["status" => false, "message" => $error_msg];
        }

        $order = wc_get_order($order_id);
        $customer_id = $order->get_customer_id();

        if ($order->get_status() != 'completed') {
            if (isset($_POST['token']) && isset($_POST['ResCode'])) {
                $terminal_key = $this->terminal_key;

                $ResCode = $_POST['ResCode'];

                if ($ResCode == '0') {
                    $token = $_POST['token'];

                    // Verify payment
                    $parameters = array(
                        'Token' => $token,
                        'SignData' => $this->sadad_encrypt($token, $terminal_key),
                    );

                    $result = $this->sadad_call_api('https://sadad.shaparak.ir/VPG/api/v0/Advice/Verify', $parameters);

                    if ($result != false) {
                        if ($result->ResCode == 0) {
                            // Payment successful
                            // (Your existing code to handle successful payment)


                            $RetrivalRefNo = $result->RetrivalRefNo;
                            $TraceNo = $result->SystemTraceNo;
                            $OrderId = $result->OrderId;


                            // Update order meta
                            update_post_meta($order_id, 'WC_Gateway_Melli_OrderId', $OrderId);
                            update_post_meta($order_id, 'WC_Gateway_Melli_RetrivalRefNo', $RetrivalRefNo);
                            update_post_meta($order_id, 'WC_Gateway_Melli_TraceNo', $TraceNo);

                            $order->payment_complete($TraceNo);
                            $applied_coupons = $order->get_coupon_codes();
                            if (isset($applied_coupons[0])) {
                                $parent_user_id = getAutherByCode($applied_coupons[0]);
                                addOrUpdateSubsetUsers($parent_user_id, $customer_id);
                                UserCashbackBalance($parent_user_id, $order->get_total());
                            }
                            $woocommerce->cart->empty_cart();

                            // Add order note
                            $Note = __('پرداخت موفقیت آمیز بود.', 'woocommerce') . '<br>';
                            $Note .= __("کد رهگیری (کد مرجع تراکنش): {$RetrivalRefNo}", 'woocommerce') . '<br>';
                            $Note .= __("شماره درخواست تراکنش: $TraceNo}", 'woocommerce') . '<br>';
                            $order->add_order_note($Note);

                            $Notice = wpautop(wptexturize($this->settings['success_massage']));
                            $Notice = str_replace("{transaction_id}", $RetrivalRefNo, $Notice);
                            $Notice = str_replace("{SaleOrderId}", $TraceNo, $Notice);

                            $userCheapCode = SubmitCheapCode($order);
                            if ($userCheapCode != "") {
                                $Notice .= "<br> کد خرید ارزان شما : $userCheapCode";
                            }
                            return ["status" => true, "message" => $Notice];
                        } else {
                            // Verification failed
                            $error_msg = 'خطا هنگام پرداخت! ' . $this->sadad_verify_err_msg($result->ResCode);
                            return ["status" => false, "message" => $error_msg];
                        }
                    } else {
                        // Could not verify payment
                        $error_msg = 'خطا! عدم امکان دریافت تاییدیه پرداخت از بانک';
                        return ["status" => false, "message" => $error_msg];
                    }
                } else {
                    // Payment failed at bank
                    $error_msg = 'خطا هنگام پرداخت! ' . $this->sadad_request_err_msg($ResCode);
                    return ["status" => false, "message" => $error_msg];
                }
            } else {
                $error_msg = __('اطلاعات دریافتی از بانک کامل نیست.', 'woocommerce');
                return ["status" => false, "message" => $error_msg];
            }
        } else {
            // Order already completed
            $Notice = wpautop(wptexturize($this->settings['success_massage']));
            return ["status" => true, "message" => $Notice];
        }
    }


    private function get_price($amount, $currency)
    {
        $currencies = array('IRT', 'TOMAN', 'Iran TOMAN', 'Iranian TOMAN', 'Iran-TOMAN', 'Iranian-TOMAN', 'Iran_TOMAN', 'تومان ایران', 'تومان', 'Iranian_TOMAN');
        if (in_array($currency, $currencies)) {
            return $amount * 10;
        }
        return $amount;
    }

    //Create sign data(Tripledes(ECB,PKCS7)) using mcrypt
    private function mcrypt_encrypt_pkcs7($str, $key)
    {
        $block = mcrypt_get_block_size("tripledes", "ecb");
        $pad = $block - (strlen($str) % $block);
        $str .= str_repeat(chr($pad), $pad);
        $ciphertext = mcrypt_encrypt("tripledes", $key, $str, "ecb");
        return base64_encode($ciphertext);
    }

    //Create sign data(Tripledes(ECB,PKCS7)) using openssl
    private function openssl_encrypt_pkcs7($key, $data)
    {
        $encData = openssl_encrypt($data, 'des-ede3', $key, 0);
        return $encData;
    }


    private function sadad_encrypt($data, $key)
    {
        $key = base64_decode($key);
        if (function_exists('openssl_encrypt')) {
            return $this->openssl_encrypt_pkcs7($key, $data);
        } elseif (function_exists('mcrypt_encrypt')) {
            return $this->mcrypt_encrypt_pkcs7($data, $key);
        } else {
            require_once 'TripleDES.php';
            $cipher = new Crypt_TripleDES();
            return $cipher->letsEncrypt($key, $data);
        }
    }

    private function sadad_call_api($url, $data = false)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json; charset=utf-8'));
        curl_setopt($ch, CURLOPT_POST, 1);
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $result = curl_exec($ch);
        curl_close($ch);
        return !empty($result) ? json_decode($result) : false;
    }

    private function sadad_request_err_msg($err_code)
    {

        $message = 'در حین پرداخت خطای سیستمی رخ داده است .';
        switch ($err_code) {
            case 3:
                $message = 'پذيرنده کارت فعال نیست لطفا با بخش امورپذيرندگان, تماس حاصل فرمائید.';
                break;
            case 23:
                $message = 'پذيرنده کارت نامعتبر است لطفا با بخش امورذيرندگان, تماس حاصل فرمائید.';
                break;
            case 58:
                $message = 'انجام تراکنش مربوطه توسط پايانه ی انجام دهنده مجاز نمی باشد.';
                break;
            case 61:
                $message = 'مبلغ تراکنش از حد مجاز بالاتر است.';
                break;
            case 1000:
                $message = 'ترتیب پارامترهای ارسالی اشتباه می باشد, لطفا مسئول فنی پذيرنده با بانکماس حاصل فرمايند.';
                break;
            case 1001:
                $message = 'لطفا مسئول فنی پذيرنده با بانک تماس حاصل فرمايند,پارامترهای پرداختاشتباه می باشد.';
                break;
            case 1002:
                $message = 'خطا در سیستم- تراکنش ناموفق';
                break;
            case 1003:
                $message = 'آی پی پذیرنده اشتباه است. لطفا مسئول فنی پذیرنده با بانک تماس حاصل فرمایند.';
                break;
            case 1004:
                $message = 'لطفا مسئول فنی پذيرنده با بانک تماس حاصل فرمايند,شماره پذيرندهاشتباه است.';
                break;
            case 1005:
                $message = 'خطای دسترسی:لطفا بعدا تلاش فرمايید.';
                break;
            case 1006:
                $message = 'خطا در سیستم';
                break;
            case 1011:
                $message = 'درخواست تکراری- شماره سفارش تکراری می باشد.';
                break;
            case 1012:
                $message = 'اطلاعات پذيرنده صحیح نیست,يکی از موارد تاريخ,زمان يا کلید تراکنش
						اشتباه است.لطفا مسئول فنی پذيرنده با بانک تماس حاصل فرمايند.';
                break;
            case 1015:
                $message = 'پاسخ خطای نامشخص از سمت مرکز';
                break;
            case 1017:
                $message = 'مبلغ درخواستی شما جهت پرداخت از حد مجاز تعريف شده برای اين پذيرنده بیشتر است';
                break;
            case 1018:
                $message = 'اشکال در تاريخ و زمان سیستم. لطفا تاريخ و زمان سرور خود را با بانک هماهنگ نمايید';
                break;
            case 1019:
                $message = 'امکان پرداخت از طريق سیستم شتاب برای اين پذيرنده امکان پذير نیست';
                break;
            case 1020:
                $message = 'پذيرنده غیرفعال شده است.لطفا جهت فعال سازی با بانک تماس بگیريد';
                break;
            case 1023:
                $message = 'آدرس بازگشت پذيرنده نامعتبر است';
                break;
            case 1024:
                $message = 'مهر زمانی پذيرنده نامعتبر است';
                break;
            case 1025:
                $message = 'امضا تراکنش نامعتبر است';
                break;
            case 1026:
                $message = 'شماره سفارش تراکنش نامعتبر است';
                break;
            case 1027:
                $message = 'شماره پذيرنده نامعتبر است';
                break;
            case 1028:
                $message = 'شماره ترمینال پذيرنده نامعتبر است';
                break;
            case 1029:
                $message = 'آدرس IP پرداخت در محدوده آدرس های معتبر اعلام شده توسط پذيرنده نیست .لطفا مسئول فنی پذيرنده با بانک تماس حاصل فرمايند';
                break;
            case 1030:
                $message = 'آدرس Domain پرداخت در محدوده آدرس های معتبر اعلام شده توسط پذيرنده نیست .لطفا مسئول فنی پذيرنده با بانک تماس حاصل فرمايند';
                break;
            case 1031:
                $message = 'مهلت زمانی شما جهت پرداخت به پايان رسیده است.لطفا مجددا سعی بفرمايید .';
                break;
            case 1032:
                $message = 'پرداخت با اين کارت . برای پذيرنده مورد نظر شما امکان پذير نیست.لطفا از کارتهای مجاز که توسط پذيرنده معرفی شده است . استفاده نمايید.';
                break;
            case 1033:
                $message = 'به علت مشکل در سايت پذيرنده. پرداخت برای اين پذيرنده غیرفعال شده
						است.لطفا مسوول فنی سايت پذيرنده با بانک تماس حاصل فرمايند.';
                break;
            case 1036:
                $message = 'اطلاعات اضافی ارسال نشده يا دارای اشکال است';
                break;
            case 1037:
                $message = 'شماره پذيرنده يا شماره ترمینال پذيرنده صحیح نمیباشد';
                break;
            case 1053:
                $message = 'خطا: درخواست معتبر, از سمت پذيرنده صورت نگرفته است لطفا اطلاعات پذيرنده خود را چک کنید.';
                break;
            case 1055:
                $message = 'مقدار غیرمجاز در ورود اطلاعات';
                break;
            case 1056:
                $message = 'سیستم موقتا قطع میباشد.لطفا بعدا تلاش فرمايید.';
                break;
            case 1058:
                $message = 'سرويس پرداخت اينترنتی خارج از سرويس می باشد.لطفا بعدا سعی بفرمايید.';
                break;
            case 1061:
                $message = 'اشکال در تولید کد يکتا. لطفا مرورگر خود را بسته و با اجرای مجدد مرورگر « عملیات پرداخت را انجام دهید )احتمال استفاده از دکمه Back » مرورگر(';
                break;
            case 1064:
                $message = 'لطفا مجددا سعی بفرمايید';
                break;
            case 1065:
                $message = 'ارتباط ناموفق .لطفا چند لحظه ديگر مجددا سعی کنید';
                break;
            case 1066:
                $message = 'سیستم سرويس دهی پرداخت موقتا غیر فعال شده است';
                break;
            case 1068:
                $message = 'با عرض پوزش به علت بروزرسانی . سیستم موقتا قطع میباشد.';
                break;
            case 1072:
                $message = 'خطا در پردازش پارامترهای اختیاری پذيرنده';
                break;
            case 1101:
                $message = 'مبلغ تراکنش نامعتبر است';
                break;
            case 1103:
                $message = 'توکن ارسالی نامعتبر است';
                break;
            case 1104:
                $message = 'اطلاعات تسهیم صحیح نیست';
                break;
            default:
                $message = 'خطای نامشخص';
        }
        return __($message, 'woocommerce');
    }

    private function sadad_verify_err_msg($res_code)
    {
        $error_text = '';
        switch ($res_code) {
            case -1:
            case '-1':
                $error_text = 'پارامترهای ارسالی صحیح نیست و يا تراکنش در سیستم وجود ندارد.';
                break;
            case 101:
            case '101':
                $error_text = 'مهلت ارسال تراکنش به پايان رسیده است.';
                break;
        }
        return __($error_text, 'woocommerce');
    }
}
