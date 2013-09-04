<?php

abstract class XliffConverter
{
    abstract protected function processNode(DOMElement $node);

    public function process($sourceFilePath, $targetFilePath)
    {
        if (empty($targetFilePath)) {
            throw new InvalidArgumentException("Empty outpu file path");
        }

        $content = file_get_contents($sourceFilePath);
        if ($content === false) {
            throw new InvalidArgumentException("Input file does not exist");
        }

        $doc = new DOMDocument();
        $doc->loadXML($content);

        foreach ($doc->getElementsByTagName('trans-unit') as $transUnit) {
            $this->processNode($transUnit);
        }
        file_put_contents($targetFilePath, $doc->saveXML());
    }

    protected function setNodeValue(DOMElement $node, $value)
    {
        $value = trim($value);
        if (strpos($value, '<') !== false || strpos($value, '>') !== false) {
            $node->nodeValue = '';
            $node->appendChild($node->ownerDocument->createCDATASection($value));
        } else {
            $node->nodeValue = $value;
        }
    }
}

class ExportXliffConverter extends XliffConverter
{
    protected function processNode(DOMElement $node)
    {
        /** @var DOMElement $source */
        $source = $node->getElementsByTagName('source')->item(0);
        /** @var DOMElement $target */
        $target = $node->getElementsByTagName('target')->item(0);

        $node->attributes->getNamedItem('id')->nodeValue = $source->nodeValue;
        $this->setNodeValue($source, $target->nodeValue);
        $target->nodeValue = ' ';
    }
}

class ImportXliffConverter extends XliffConverter
{
    protected function processNode(DOMElement $node)
    {
        /** @var DOMElement $source */
        $source = $node->getElementsByTagName('source')->item(0);
        $id = $node->attributes->getNamedItem('id');
        $source->nodeValue = $id->nodeValue;

        $target = $node->getElementsByTagName('target')->item(0);
        $this->setNodeValue($target, $target->nodeValue);
    }


}

$options = getopt("a:i:o:");
if (empty($options) || !isset($options['a']) || !isset($options['i']) || !isset($options['o'])
    || !in_array($options['a'], array('import', 'export'))
) {
    echo PHP_EOL . 'Required options: ';
    echo PHP_EOL . ' -a <ACTION: import|export>';
    echo PHP_EOL . ' -i <INPUT FILE PATH>';
    echo PHP_EOL . ' -o <OUTPUT FILE PATH>';
    echo PHP_EOL;
    exit(1);
}

$inputFilePath = $options['i'];
$outputFilePath = $options['o'];

switch($options['a']) {
    case 'import':
        $converter = new ImportXliffConverter();
        break;
    case 'export':
        $converter = new ExportXliffConverter();
        break;
    default:
        throw new Exception('This should not have happened!');
}

$converter->process($inputFilePath, $outputFilePath);
