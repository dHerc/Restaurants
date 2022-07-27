<?php declare(strict_types=1);

namespace Restaurants\Utils;

use Restaurants\Models\FeedOutNode;
use Restaurants\Services\FeedService;

class FeedObject
{
    private FeedService $service;
    private string $header = '<?xml version="1.0" encoding="UTF-8" ?>';
    private string $feed = '';
    private bool $finished = false;
    private FeedOutNode $node;
    private int $active = 0;
    private int $paused = 0;
    private $outFile;

    public function __construct($outputPath)
    {
        $this->service = new FeedService();
        $this->outFile = fopen($outputPath, 'wb');
    }

    public function setHeader($header): void
    {
        $this->header = $header;
    }

    private function appendFeed(string $value): void
    {
        $this->feed.=$value;
    }

    /**
     * @throws \DOMException
     */
    public function handleLine(string $line): void
    {
        if (str_contains($line, '<offer>')) {
            $this->clear();
        }
        $this->appendFeed($line);
        if(str_contains($line, '</offer>')) {
            $this->finish();
        }
    }

    /**
     * @throws \DOMException
     */
    public function clear(): void
    {
        if(!$this->finished) {
            $this->service->writeOutput($this->feed, $this->outFile);
        }
        $this->feed = '';
        $this->finished = false;
        unset($this->node, $this->outputNode);
    }

    /**
     * @throws \DOMException
     */
    private function finish(): void
    {
        $this->finished = true;
        $this->node = $this->service->generateNodeFromXML($this->header.PHP_EOL.$this->feed);
        $this->node = $this->service->calculateOutputNode($this->node);
        if($this->node->is_active) {
            $this->active++;
        } else {
            $this->paused++;
        }
        $this->service->writeOutput($this->node, $this->outFile);
        $this->clear();
    }

    public function printCounts(): void
    {
        echo "Active: ".$this->active.PHP_EOL;
        echo "Paused: ".$this->paused.PHP_EOL;
    }
}