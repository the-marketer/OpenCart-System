<?php
/**
 * @copyright   © EAX LEX SRL. All rights reserved.
 **/

namespace Mktr\Tracker\Routes;

use Mktr\Helper\Config;
use Mktr\Helper\FileSystem;
use Mktr\Tracker\Routes\Feed;
use Mktr\Tracker\Routes\Reviews;
use Mktr\Helper\Valid;

use Mktr\Helper\Data;

class Cron
{
    private static $init = null;

    private static $map = array(
        "fileName" => "cron",
        "secondName" => "cron"
    );

    public static function get($f = 'fileName'){
        if (isset(self::$map[$f]))
        {
            return self::$map[$f];
        }
        return null;
    }

    public static function init()
    {
        if (self::$init == null) {
            self::$init = new self();
        }
        return self::$init;
    }

    public static function execute() {

        $data = Data::init();
        $upFeed = $data->update_feed;
        $upReview = $data->update_review;
        
        if (Config::getStatus() != 0) {

            if (Config::getCronFeed() != 0 && $upFeed < time()) {

                $run = Feed::init();
                
                $run->execute();

                $fileName = $run->get('fileName').".".Valid::getParam('mime-type', Config::defMime);

                Valid::Output($run->get('fileName'), array( $run->get('secondName') => $run->execute()));
                
                FileSystem::writeFile($fileName, Valid::getOutPut());

                $data->update_feed = strtotime("+".Config::getUpdateFeed()." hour");
            }

            if (Config::getCronReview() != 0 && $upReview < time()) {

                Reviews::execute();

                $data->update_review = strtotime("+".Config::getUpdateReview()." hour");
            }
            $data->save();
        }

        $get = array( "cron"=>
                array(
                'update_feed' => $data->update_feed,
                'update_review' => $data->update_review
            )
        );
        return $get;
    }
}
