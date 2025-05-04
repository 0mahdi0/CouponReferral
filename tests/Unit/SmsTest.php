<?php
declare(strict_types=1);
namespace Tests\Unit; 
include "sms/SMS.php"; 
use PHPUnit\Framework\TestCase;
use sms\SMS; 
class SmsTest extends TestCase
{
    public function test_is_otp_sent()
    {
        $sms = new SMS(); 
        $otp_code = $sms->sendOtp(
            '09331168046',
            'آقای رضا اسدی',
        ); 
        $this->assertEquals(6, strlen($otp_code));
    }
}