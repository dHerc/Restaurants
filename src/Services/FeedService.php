<?php declare(strict_types=1);

namespace Restaurants\Services;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use DateTimeZone;
use Restaurants\Models\FeedOutNode;

class FeedService
{
    public function generateNodeFromXML(string $XMLFeed): FeedOutNode
    {
        $node = new FeedOutNode();
        $XML = new \DOMDocument();
        $XML->loadXML($XMLFeed);
        foreach($XML->getElementsByTagName('offer')[0]->childNodes as $childNode) {
            /** @var \DOMText $childNode */
            $name = $childNode->localName;
            if($name && (!$_ENV['STRICT_ATTRIBUTE_COPY'] || property_exists($node, $name))) {
                $value = $childNode->nodeValue;
                $node->$name = $value;
            }
        }
        return $node;
    }

    /**
     * @throws \DOMException
     */
    public function generateXMLFromNode(FeedOutNode $node): string
    {
        $XML = new \DOMDocument();
        $XMLOffer = $XML->createElement('offer');
        foreach (get_object_vars($node) as $key => $value) {
            $XMLChildNode = $XML->createElement($key);
            $fixedValue = $value;
            if(is_bool($fixedValue)) {
                $fixedValue = $value ? 'true' : 'false';
            }
            $XMLChildNode->appendChild($XML->createCDATASection($fixedValue));
            $XMLOffer->appendChild($XMLChildNode);
        }
        $XML->appendChild($XMLOffer);
        $XML->formatOutput = true;
        return $XML->saveXML($XML->documentElement).PHP_EOL;
    }

    private function isValidTimezoneId($timezoneId): bool
    {
        try{
            new DateTimeZone($timezoneId);
        } catch(\Exception){
            return false;
        }
        return true;
    }

    private function calculateIsActiveAttribute(FeedOutNode $node, CarbonInterface $time): bool
    {
        $weekday = $time->isoWeekday();
        $prevWeekday = $weekday === 1 ? 7 : $weekday-1;
        $openingTimes = json_decode($node->opening_times, false);
        $hour = $time->format("H:i");
        $active = false;

        if(isset(($openingTimes->$weekday)[0]->opening, ($openingTimes->$weekday)[0]->closing)) {
            $openTime = ($openingTimes->$weekday)[0]->opening;
            $closingTime = ($openingTimes->$weekday)[0]->closing;
            if($openTime >= $closingTime) {
                $closingTime = "24:00";
            }
            if($hour > $openTime && $hour < $closingTime) {
                $active = true;
            }
        }
        if(isset(($openingTimes->$prevWeekday)[0]->opening, ($openingTimes->$prevWeekday)[0]->closing) && !$active) {
            $openTime = ($openingTimes->$prevWeekday)[0]->opening;
            $closingTime = ($openingTimes->$prevWeekday)[0]->closing;
            if($openTime >= $closingTime && $hour < $closingTime) {
                $active = true;
            }
        }
        return $active;
    }

    public function calculateOutputNode(FeedOutNode $outputNode): FeedOutNode
    {
        $currentTime = Carbon::now();
        if(isset($outputNode->opening_times)) {
            $openingTimes = json_decode($outputNode->opening_times, false);
            $currentTime->timezone($this->isValidTimezoneId($openingTimes->timezone ?? null) ? $openingTimes->timezone : $_ENV['DEFAULT_TIMEZONE']);
            $outputNode->is_active = $this->calculateIsActiveAttribute($outputNode, $currentTime);
        } else {
            $outputNode->is_active = false;
        }
        return $outputNode;
    }

    /**
     * @throws \DOMException
     */
    public function writeOutput(string|FeedOutNode $data, $output = null): void
    {
        if($data instanceof FeedOutNode) {
            $outData = $this->generateXMLFromNode($data);
        } else {
            $outData = $data;
        }
        if(!$output) {
            echo $outData;
        } else {
            fwrite($output, $outData);
        }
    }
}