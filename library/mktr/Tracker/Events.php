<?php
/**
 * @copyright   © EAX LEX SRL. All rights reserved.
 **/

namespace Mktr\Tracker;

use Mktr\Helper\CheckPage;
use Mktr\Helper\Config;
use Mktr\Helper\Core;
use Mktr\Helper\Valid;
use Mktr\Tracker\Model\Category;
use Mktr\Tracker\Model\Product;

class Events
{
    private static $init = null;
    private static $shName = null;
    private static $data = array();

    private static $assets = array();

    private static $actions = [
        "is_home" => "__sm__view_homepage",
        "is_product_category" => "__sm__view_category",
        "is_product" => "__sm__view_product",
        "is_brand" => "__sm__view_brand",
        "is_checkout" => "__sm__initiate_checkout",
        "is_search" => "__sm__search"
    ];

    public static $observerGetEvents = [
        "addToCart"=> [false, "__sm__add_to_cart"],
        "removeFromCart"=> [false, "__sm__remove_from_cart"],
        "addToWishlist"=> [false, "__sm__add_to_wishlist"],
        "removeFromWishlist"=> [false, "__sm__remove_from_wishlist"],
        "saveOrder"=> [true, "__sm__order"],
        "setEmail"=> [true, "__sm__set_email"],
        "setPhone"=> [false, "__sm__set_phone"]
    ];

    private static $eventsName = [
        "__sm__view_homepage" =>"HomePage",
        "__sm__view_category" => "Category",
        "__sm__view_brand" => "Brand",
        "__sm__view_product" => "Product",
        "__sm__add_to_cart" => "addToCart",
        "__sm__remove_from_cart" => "removeFromCart",
        "__sm__add_to_wishlist" => "addToWishlist",
        "__sm__remove_from_wishlist" => "removeFromWishlist",
        "__sm__initiate_checkout" => "Checkout",
        "__sm__order" => "saveOrder",
        "__sm__search" => "Search",
        "__sm__set_email" => "setEmail",
        "__sm__set_phone" => "setPhone"
    ];

    private static $eventsSchema = [
        "HomePage" => null,
        "Checkout" => null,
        "Cart" => null,

        "Category" => [
            "category" => "category"
        ],

        "Brand" => [
            "name" => "name"
        ],

        "Product" => [
            "product_id" => "product_id"
        ],

        "Search" => [
            "search_term" => "search_term"
        ],

        "setPhone" => [
            "phone" => "phone"
        ],

        "addToWishlist" => [
            "product_id" => "product_id",
            "variation" => [
                "@key" => "variation",
                "@schema" => [
                    "id" => "id",
                    "sku" => "sku"
                ]
            ]
        ],

        "removeFromWishlist" => [
            "product_id" => "product_id",
            "variation" => [
                "@key" => "variation",
                "@schema" => [
                    "id" => "id",
                    "sku" => "sku"
                ]
            ]
        ],

        "addToCart" => [
            "product_id" => "product_id",
            "quantity" => "quantity",
            "variation" => [
                "@key" => "variation",
                "@schema" => [
                    "id" => "id",
                    "sku" => "sku"
                ]
            ]
        ],

        "removeFromCart" => [
            "product_id" => "product_id",
            "quantity" => "quantity",
            "variation" => [
                "@key" => "variation",
                "@schema" => [
                    "id" => "id",
                    "sku" => "sku"
                ]
            ]
        ],

        "saveOrder" => [
            "number" => "number",
            "email_address" => "email_address",
            "phone" => "phone",
            "firstname" => "firstname",
            "lastname" => "lastname",
            "city" => "city",
            "county" => "county",
            "address" => "address",
            "discount_value" => "discount_value",
            "discount_code" => "discount_code",
            "shipping" => "shipping",
            "tax" => "tax",
            "total_value" => "total_value",
            "products" => [
                "@key" => "products",
                "@schema" =>
                    [
                        "product_id" => "product_id",
                        "price" => "price",
                        "quantity" => "quantity",
                        "variation_sku" => "variation_sku"
                    ]
            ]
        ],

        "setEmail" => [
            "email_address" => "email_address",
            "firstname" => "firstname",
            "lastname" => "lastname"
        ]
    ];

    /**
     * @var array
     */
    private static $bMultiCat;

    public static function init()
    {
        if (self::$init == null) {
            self::$init = new self();
        }
        return self::$init;
    }

    public static function loader()
    {
        $lines = array();
        $lines[] = '(function(d, s, i) {
        var f = d.getElementsByTagName(s)[0], j = d.createElement(s);j.async = true;
        j.src = "https://t.themarketer.com/t/j/" + i; f.parentNode.insertBefore(j, f);
    })(document, "script", "'.Config::getKey().'");';

        $lines[] = 'window.MktrDebug = function () { if (typeof dataLayer != undefined) { for (let i of dataLayer) { console.log("Mktr","Google",i); } } };';
        $lines[] = '';
        $wh =  array(Config::space, implode(Config::space, $lines));
        $rep = array("%space%","%implode%");
        /** @noinspection BadExpressionStatementJS */
        /** @noinspection JSUnresolvedVariable */
        return str_replace($rep, $wh, '<!-- Mktr Script Start -->%space%<script type="text/javascript">%space%%implode%%space%</script>%space%<!-- Mktr Script END -->');
    }

    /** @noinspection PhpUnused */
    public static function loadEvents()
    {
        $loadJS = $lines = array();

        foreach (self::$actions as $key=>$value)
        {
            if (CheckPage::{$key}())
            {
                $lines[] = "dataLayer.push(".self::getEvent($value)->toJson().");";
                break;
            }
        }
        $clear = Core::getSessionData("ClearMktr");

        foreach (self::$observerGetEvents as $event=>$Name)
        {
            $eventData = Core::getSessionData($event);
            if (!empty($eventData))
            {
                foreach ($eventData as $key => $value)
                {
                    $lines[] = "dataLayer.push(".self::getEvent($Name[1], $value)->toJson().");";
                    if (!$Name[0]) {
                        $clear[$event][$key] = $key;
                    }
                }

                if ($Name[0]) {
                    $loadJS[$event] = true;
                }else {
                    // Core::setSessionData($event, array());
                }
            }
        }

        //$baseURL = Config::getBaseURL();

        foreach ($loadJS as $k=>$v)
        {
            $lines[] = '(function(){ let add = document.createElement("script"); add.async = true; add.src = "'.Core::url()->link('mktr/api/'.$k.'/').'"; let s = document.getElementsByTagName("script")[0]; s.parentNode.insertBefore(add,s); })();';
        }

        if (!empty($clear)) {
            Core::setSessionData("ClearMktr", $clear);

            $lines[] = '(function(){ let add = document.createElement("script"); add.async = true; add.src = "'.Core::url()->link('mktr/api/clearEvents/').'"; let s = document.getElementsByTagName("script")[0]; s.parentNode.insertBefore(add,s); })();';
        }

        //$lines[] = 'setTimeout(window.MktrDebug, 1000);';

        $wh =  array(Config::space, implode(Config::space, $lines));
        $rep = array("%space%","%implode%");
        /** @noinspection BadExpressionStatementJS */
        /** @noinspection JSUnresolvedVariable */
        return str_replace($rep, $wh, '<!-- Mktr Script Start -->%space%<script type="text/javascript">%space%%implode%%space%</script>%space%<!-- Mktr Script END -->');
    }

    public static function loader_body() {
        $sel = Config::getSelectors();
        if (!empty($sel)) {
            /*echo '<script type="text/javascript">
        (function($) {
            let MktrLoadEvents = true;

			let AddMktrEvents = function () {
                (function(){
				let add = document.createElement("script");
                    add.async = true;
                    add.src = "' . \Mktr\Tracker\Config::getBaseURL(). 'mktr/api/loadEvents/";
                let s = document.getElementsByTagName("script")[0];
                    s.parentNode.insertBefore(add,s);
                })(); MktrLoadEvents = true;
			};

			let LoadEventsMktr = function() { if (MktrLoadEvents) { MktrLoadEvents = false; setTimeout(AddMktrEvents, 1000); } };

            $(document.body).on("added_to_cart", LoadEventsMktr);
            $(document.body).on("removed_from_cart", LoadEventsMktr);
            $(document.body).on("click", "'.Config::getSelectors().'", LoadEventsMktr);
        })(jQuery); </script>';*/
            return '<!-- Mktr Script Start --><script type="text/javascript">
window.addEventListener("click", function(event){
    if (event.target.matches("'.str_replace('"','\"',Config::getSelectors()).'")) {
        setTimeout(function(){
            (function(){
                let add = document.createElement("script"); add.async = true; add.src = "'.Core::url()->link('mktr/api/LoadEvents/').'"; let s = document.getElementsByTagName("script")[0]; s.parentNode.insertBefore(add,s);
            })();
        }, 3000); 
    }
});
        </script><!-- Mktr Script END -->';
        } else {
            return '';
        }

    }

    public static function google_head()
    {
        $key = Config::getTagCode();
        if (Config::getGoogleStatus() && !empty($key)) {
            return "<!-- Google Tag Manager -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
            new Date().getTime(),event:'gtm.js'}); let f=d.getElementsByTagName(s)[0],
        j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
            'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
    })(window,document,'script','dataLayer','" .$key."');</script>
<!-- End Google Tag Manager -->";
        }
        return '';
    }

    public static function google_body() {
        $key = Config::getTagCode();
        if (Config::getGoogleStatus() && !empty($key)) {
            return'<!-- Google Tag Manager (noscript) -->
        <noscript><iframe src="https://www.googletagmanager.com/ns.html?id='.$key.'" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
        <!-- End Google Tag Manager (noscript) -->';
        }
        return '';
    }

    public static function build()
    {
        foreach (self::$assets as $key=>$val) {
            self::$data[$key] = $val;
        }
    }

    public static function schemaValidate($array, $schema)
    {
        $newOut = [];

        foreach ($array as $key=>$val) {
            if (isset($schema[$key])){
                if (is_array($val)) {
                    $newOut[$schema[$key]["@key"]] = self::schemaValidate($val, $schema[$key]["@schema"]);
                } else {
                    $newOut[$schema[$key]] = $val;
                }
            } else if (is_array($val)){
                $newOut[] = self::schemaValidate($val, $schema);
            }
        }

        return $newOut;
    }

    public static function getEvent($Name, $eventData = [])
    {
        if (empty(self::$eventsName[$Name]))
        {
            return false;
        }

        self::$shName = self::$eventsName[$Name];

        self::$data = array(
            "event" => $Name
        );

        self::$assets = array();

        switch (self::$shName){
            case "Category":
                self::$assets['category'] = self::buildCategory();
                break;
            case "Product":
                self::$assets['product_id'] = Product::id();
                break;
            case "Search":
                if (isset(Core::request()->get['search'])) {
                    self::$assets['search_term'] = Core::request()->get['search'];
                }
                break;
            case "Brand":
                $brand = Core::ocModel('catalog/manufacturer')->getManufacturer(Core::request()->get['manufacturer_id']);
                self::$assets['name'] = $brand['name'];
                break;
            default:
                self::$assets = $eventData;
        }

        self::$assets = self::schemaValidate(self::$assets, self::$eventsSchema[self::$shName]);

        self::build();

        return self::init();
    }

    /**
     * @noinspection PhpUnused
     */
    public static function buildCategory($categoryRegistry = null)
    {
        if ($categoryRegistry == null)
        {
            $categoryRegistry = Category::getById();
        }

        $build = array($categoryRegistry->getName());

        while ($categoryRegistry->getParentId() > 0) {
            $categoryRegistry = Category::getById($categoryRegistry->getParentId());
            $build[] = $categoryRegistry->getName();
        }
        return implode("|", array_reverse($build));
    }

    /** @noinspection PhpUnused */
    public static function buildMultiCategory($List) {
        self::$bMultiCat = [];
        foreach ($List as $value) {
            Category::getById($value['category_id']);
            self::buildSingleCategory();
        }

        if (empty(self::$bMultiCat))
        {
            self::$bMultiCat[] = "Default Category";
        }
        return implode("|", array_reverse(self::$bMultiCat));
    }

    public static function buildSingleCategory() {
        self::$bMultiCat[] = Category::getName();

        while (Category::getParentId() > 0) {
            Category::getById(Category::getParentId());

            self::$bMultiCat[] = Category::getName();
        }
    }

    public function toJson(){
        return Valid::toJson(self::$data);
    }
}