<?php

namespace App\Commands;

use App\Modules\News\Models\Story;
use App\Modules\News\Models\Topic;
use App\Modules\Pages\Models\Page;
use T4\Console\Command;
use T4\Console\Exception;

class Import
    extends Command
{

    public function actionNews()
    {

        /*
         * NEWS TOPICS
         */
        $dataFileName = realpath(__DIR__ . DS . '..' . DS . '..' . DS . 'data' . DS . 'kdm3_news_topics.csv');
        if (!is_readable($dataFileName))
            throw new Exception('Data file ' . $dataFileName . ' is not found or is not readable');
        $dataFile = fopen($dataFileName, 'r');

        $deferred = [];
        $processed = [];

        while ($row = fgetcsv($dataFile, 0, ',', '"', '"')) {

            // Если родительский объект имеется и его еще нет среди обработанных - отложим
            if ($row[1] != 0 && !isset($processed[$row[1]])) {
                $deferred[] = $item;
                continue;
            }
            $item = new Topic();
            $item->title = $row[3];
            $item->parent = 0;
            $item->save();
            $processed[$row[0]] = ['pk'=>$item->getPk()];
        }

        $topics = $processed;
        // TODO: цикл обработки $deferred

        /*
         * NEWS ARTICLES
         */

        $dataFileName = realpath(__DIR__ . DS . '..' . DS . '..' . DS . 'data' . DS . 'kdm3_news_stories.csv');
        if (!is_readable($dataFileName))
            throw new Exception('Data file ' . $dataFileName . ' is not found or is not readable');
        $dataFile = fopen($dataFileName, 'r');

        while ($row = fgetcsv($dataFile, 0, ',', '"', '"')) {

            $lead = $row[9];
            preg_match('~\<img(.+?)src="(.+?)"(.*?)[\/]?\>~', $lead, $m);
            if (!empty($m[2])) {
                $image = $m[2];
                $lead = str_replace($m[0], '', $lead);
            } else {
                $image = '';
            }

            $item = new Story();
            $item->title = $row[2];
            $item->published = date('Y-m-d H:i:s', $row[4]);
            $item->lead = $lead;
            $item->image = $image;
            $item->text = $row[10];
            $item->topic = $topics[$row[12]]['pk'];
            $item->save();
        }

    }

    public function actionPages()
    {
        $dataFileName = realpath(__DIR__ . DS . '..' . DS . '..' . DS . 'data' . DS . 'kdm3_pages_pages.csv');
        if (!is_readable($dataFileName))
            throw new Exception('Data file ' . $dataFileName . ' is not found or is not readable');
        $dataFile = fopen($dataFileName, 'r');

        $deferred = [];
        $processed = [];

        while ($row = fgetcsv($dataFile, 0, ',', '"', '"')) {

            // Если родительский объект имеется и его еще нет среди обработанных - отложим
            if ($row[1] != 0 && !isset($processed[$row[1]])) {
                $deferred[] = $row;
                continue;
            }

            $item = new Page();
            $item->title = $row[2];
            $item->url = $row[3];
            $item->template = $row[4];
            $item->text = $row[5];
            $item->order = $row[9];
            $item->parent =  0==$row[1] ? 0 : $processed[$row[1]];
            $item->save();

            $processed[$row[0]] = $item->getPk();

        }

        while (!empty($deferred)) {
            foreach ($deferred as $id => $row) {
                // Если родительский объект имеется и его еще нет среди обработанных - отложим
                if ($row[1] != 0 && !isset($processed[$row[1]])) {
                    $deferred[] = $row;
                    continue;
                }

                $item = new Page();
                $item->title = $row[2];
                $item->url = $row[3];
                $item->template = $row[4];
                $item->text = $row[5];
                $item->order = $row[9];
                $item->parent = 0==$row[1] ? 0 : $processed[$row[1]];
                $item->save();

                $processed[$row[0]] = $item->getPk();
                unset($deferred[$id]);
            }
        }

    }

}