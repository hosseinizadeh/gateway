<?php

namespace Hosseinizadeh\Gateway\ZarinpalWages;

use Hosseinizadeh\Gateway\Exceptions\BankException;

class ZarinpalWagesException extends BankException
{

    public static $errorsWages = array(
        -1 => 'اطلاعات ارسال شده ناقص است.',
        -2 => 'IP و یا مرچنت کد پذیرنده صحیح نیست',
        -3 => 'رقم باید بالای 100 تومان باشد',
        -4 => 'سطح پذیرنده پایین تر از سطح نقره ای است',
        -9 => 'خطای اعتبار سنجی',
        -10 => 'ای پی و يا مرچنت كد پذيرنده صحيح نيست',
        -11 => 'مرچنت کد فعال نیست لطفا با تیم پشتیبانی ما تماس بگیرید',
        -12 => 'تلاش بیش از حد در یک بازه زمانی کوتاه',
        -15 => 'ترمینال شما به حالت تعلیق در آمده با تیم پشتیبانی تماس بگیرید',
        -16 => 'سطح تاييد پذيرنده پايين تر از سطح نقره اي است.',
        -22 => 'تراکنش ناموفق میباشد',
        100 => 'عملیات موفق',
        -30 => 'اجازه دسترسی به تسویه اشتراکی شناور ندارید',
        -31 => 'حساب بانکی تسویه را به پنل اضافه کنید مقادیر وارد شده واسه تسهیم درست نیست',
        -32 => 'دستمزدها (شناور) بیش از حد بیش از حد بوده است',
        -33 => 'درصد های وارد شده درست نیست',
        -34 => 'مبلغ از کل تراکنش بیشتر است',
        -35 => 'تعداد افراد دریافت کننده تسهیم بیش از حد مجاز است',
        -40 => 'پارامترهای اضافی نامعتبر',
        -50 => 'مبلغ پرداخت شده با مقدار مبلغ در وریفای متفاوت استر',
        -51 => 'پرداخت ناموفق',
        -52 => 'خطای غیر منتظره با پشتیبانی تماس بگیرید',
        -53 => 'اتوریتی برای این مرچنت کد نیست',
        -54 => 'اتوریتی نامعتبر است',
        101 => 'پارامترهای اضافی نامعتبر',
    );

    public function __construct($errorId)
    {
        $this->errorId = intval($errorId);
        parent::__construct(@self::$errorsWages[$this->errorId].' #'.$this->errorId, $this->errorId);
    }
}
