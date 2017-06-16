## DHL

### 配置

复制`dhl`到laravel根目录下，配置composer.json文件：
```
    "autoload": {
        "classmap": [
            "database"
        ],
        "psr-4": {
            "App\\": "app/",
            "DHL\\": "dhl/DHL"
        }
    },
```
执行:
```
composer dump-autoload
```


然后添加服务提供者到`config/app.php`的`providers`:
```
DHL\Provider\DHLServiceProvider::class,
```
继续在`aliases`添加：
```
'DHL' => DHL\Facades\DHL::class,
'DHLLabel' => DHL\Facades\DHLLabel::class,
```
运行`php artisan vendor:publish`，发布配置文件到config目录下，在config/dhl.php修改配置
 

### 使用
在`laravel5.4`版本下可以使用实时`Facade`：

##### 订单查询
```
<?php 

namespace App\Http\Controllers;

use DHL\Facades\DHL;

class TrackingController extends Controller
{
    public function test()
    {
        $input = [
            ['order_number' => '6399775', 'tracking_number' => '2162387743'],
            ['order_number' => '6408518', 'tracking_number' => '2162387673'],
            ['order_number' => '6408524', 'tracking_number' => '2162387533'],
            ['order_number' => '6408877', 'tracking_number' => '216238566'],
            ['order_number' => '6408209', 'tracking_number' => '2162387426'],
        ];
        
        dd(DHL::get($input));
        
    }
}
```
##### 标签生成
```
<?php

namespace App\Http\Controllers;

use DHL\Facades\DHLLabel;

class LabelController extends Controller
{
    function test()
    {
        //其他设置在Api/config.php内
        $input =  array(
            'RegionCode' => 'AM',
            'LanguageCode' => 'en',
            'PiecesEnabled' => 'Y',
            'Billing' => array(
                'ShippingPaymentType' => 'S',
            ),
            'Consignee' => array(
                'CompanyName' => 'zheng xiang jing',
                'AddressLine' => array(
                    '0' => 'beijing tian an men',
                ),
                'City' => 'beijing',
                'PostalCode' => '100000',
                'CountryCode' => 'CN',
                'CountryName' => 'China',
                'Contact' => array(
                    'PersonName' => 'zheng xiang jing',
                    'PhoneNumber' => '13959800000',
                    'Email' => '656861622@qq.com',
                    'MobilePhoneNumber' => '13959800000',
                ),
            ),
            'Dutiable' => array(
                'DeclaredValue'  => '100.00',
                'DeclaredCurrency' => 'MYR'
            ),
            'Reference' => array(
                '0' => array(
                    'ReferenceID' => 'ZY102566987',
                ),
            ),
            'ShipmentDetails' => array(
                'NumberOfPieces' => '1',
                'Pieces' => array(
                    '0' => array(
                        'PieceID' => '1',
                        'ReferenceID' => '11'
                    ),
                ),
                'Weight' => '10.0',
                'WeightUnit' => 'K',
                'GlobalProductCode' => 'P',
                'LocalProductCode' => 'P',
                'Contents' => 'test product',
                'DimensionUnit' => 'C',
                'CurrencyCode' => 'USD'
            ),
        );

        DHLLabel::labelPrint($input);
    }

}
```

在`5.4`以下的版本，可以直接从容器中取：
```
app('DHL')->get($input);
app('DHLLabel')->labelPrint($input);
```