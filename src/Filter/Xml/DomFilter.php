<?php

namespace Aurxy\Filter\Xml;

use Aurxy\Filter\ContentFilter;
use Symfony\Component\DomCrawler\Crawler;

class DomFilter extends ContentFilter
{
    /**
     * @var Crawler
     */
    protected $domCrawler;
    /**
     * @var array
     */
    protected $contentTypes =[
        'application/xml',
        'text/html',
    ];

    protected function beforeProcess()
    {
        parent::beforeProcess();
        $this->domCrawler = new Crawler($this->content);
    }

    public function handle()
    {
        // empty
    }

    protected function afterProcess()
    {
        parent::afterProcess();
        $this->content = $this->domCrawler->html();
    }
}