<?php

namespace Panlatent\Aurxy\Filter\Xml;

use Panlatent\Aurxy\Filter\ContentFilter;
use Symfony\Component\DomCrawler\Crawler;

class DomFilter extends ContentFilter
{
    /**
     * @var Crawler
     */
    protected $domCrawler;

    protected function beforeProcess()
    {
        parent::beforeProcess();
        $this->domCrawler = new Crawler($this->content);
    }

    public function handle()
    {

    }

    protected function afterProcess()
    {
        parent::afterProcess();
        $this->content = $this->domCrawler->html();
    }
}