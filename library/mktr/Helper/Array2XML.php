<?php
/**
 * @copyright   © EAX LEX SRL. All rights reserved.
 **/

/**
 * Usage:
 *  echo Array2XML::cXML("root_node_name",$array)->saveXML();
 */
namespace Mktr\Helper;

use DOMDocument;
use DOMImplementation;
use Exception;

class Array2XML
{
    const DEFAULT_DOM_VERSION = '1.0';
    const DEFAULT_ENCODING = 'UTF-8';
    const DEFAULT_STANDALONE = false;
    const DEFAULT_FORMAT_OUTPUT = true;

    const LABEL_ATTRIBUTES = '@attributes';
    const LABEL_CDATA = '@cdata';
    const LABEL_DOCTYPE = '@docType';
    const LABEL_VALUE = '@value';

    protected static $xml = null;

    protected static $domVersion = self::DEFAULT_DOM_VERSION;

    protected static $encoding = self::DEFAULT_ENCODING;

    protected static $standalone = self::DEFAULT_STANDALONE;

    protected static $formatOutput = self::DEFAULT_FORMAT_OUTPUT;

    protected static $labelAttributes = self::LABEL_ATTRIBUTES;

    protected static $labelCData = self::LABEL_CDATA;

    protected static $labelDocType = self::LABEL_DOCTYPE;

    protected static $labelValue = self::LABEL_VALUE;

    public static function init(
        $version = null,
        $encoding = null,
        $standalone = null,
        $format_output = null,
        $labelAttributes = null,
        $labelCData = null,
        $labelDocType = null,
        $labelValue = null
    ) {
        self::setDomVersion($version);
        self::setEncoding($encoding);
        self::setStandalone($standalone);
        self::setFormatOutput($format_output);

        self::setLabelAttributes($labelAttributes);
        self::setLabelCData($labelCData);
        self::setLabelDocType($labelDocType);
        self::setLabelValue($labelValue);

        self::$xml = new DomDocument(self::getDomVersion(), self::getEncoding());
        // self::$xml->xmlStandalone = self::isStandalone();
        self::$xml->formatOutput = self::isFormatOutput();
    }

    public static function getDomVersion() {
        return self::$domVersion;
    }

    public static function getEncoding() {
        return self::$encoding;
    }

    /** @noinspection PhpUnused */
    public static function isStandalone() {
        return self::$standalone;
    }

    public static function isFormatOutput() {
        return self::$formatOutput;
    }

    protected static function setDomVersion($domVersion = null) {
        self::$domVersion = isset($domVersion) ? $domVersion : self::DEFAULT_DOM_VERSION;
    }

    protected static function setEncoding($encoding = null) {
        self::$encoding = isset($encoding) ? $encoding : self::DEFAULT_ENCODING;
    }

    protected static function setStandalone($standalone = null) {
        self::$standalone = isset($standalone) ? $standalone : self::DEFAULT_STANDALONE;
    }

    protected static function setFormatOutput($formatOutput = null) {
        self::$formatOutput = isset($formatOutput) ? $formatOutput : self::DEFAULT_FORMAT_OUTPUT;
    }

    /** @noinspection PhpUnused */
    public static function getLabelAttributes() {
        return self::$labelAttributes;
    }

    /** @noinspection PhpUnused */
    public static function getLabelCData() {
        return self::$labelCData;
    }

    /** @noinspection PhpUnused */
    public static function getLabelDocType() {
        return self::$labelDocType;
    }

    /** @noinspection PhpUnused */
    public static function getLabelValue() {
        return self::$labelValue;
    }

    protected static function setLabelAttributes($labelAttributes = null) {
        self::$labelAttributes = isset($labelAttributes) ? $labelAttributes : self::LABEL_ATTRIBUTES;
    }

    protected static function setLabelCData($labelCData = null) {
        self::$labelCData = isset($labelCData) ? $labelCData : self::LABEL_CDATA;
    }

    protected static function setLabelDocType($labelDocType = null) {
        self::$labelDocType = isset($labelDocType) ? $labelDocType : self::LABEL_DOCTYPE;
    }

    protected static function setLabelValue($labelValue = null) {
        self::$labelValue = isset($labelValue) ? $labelValue : self::LABEL_VALUE;
    }

    /** @noinspection PhpUnused */
    public function createXML($node_name, $arr = null, $docType = []) {
        return self::cXML($node_name, $arr, $docType);
    }

    public static function cXML($node_name, $arr = null, $docType = []) {
        /** @noinspection DuplicatedCode */
        $xml = self::getXMLRoot();
        if ($docType) {
            /** @noinspection PhpExpressionAlwaysNullInspection */
            /** @noinspection PhpUnhandledExceptionInspection */
            $xml->appendChild(
                (new DOMImplementation())
                    ->createDocumentType(
                        isset($docType['name']) ? $docType['name'] : '',
                        isset($docType['publicId']) ? $docType['publicId'] : '',
                        isset($docType['systemId']) ? $docType['systemId'] : ''
                    )
            );
        }
        if ($arr == null) {
            foreach ($node_name as $key => $value)
            {
                /** @noinspection PhpExpressionAlwaysNullInspection */
                /** @noinspection PhpUnhandledExceptionInspection */
                $xml->appendChild(self::convert($key, $value));
            }
        } else {
            /** @noinspection PhpExpressionAlwaysNullInspection */
            /** @noinspection PhpUnhandledExceptionInspection */
            $xml->appendChild(self::convert($node_name, $arr));
        }

        self::$xml = null;

        /** @noinspection PhpExpressionAlwaysNullInspection */
        return $xml;
    }

    private static function bool2str($v) {
        return $v === true ? 'true' : (($v === false) ? 'false' : $v);
    }

    /** @noinspection PhpUnused */
    public function getConvert($node_name, $arr = []) {
        /** @noinspection PhpUnhandledExceptionInspection */
        return self::convert($node_name, $arr);
    }

    /** @noinspection SpellCheckingInspection */
    private static function convert($node_name, $arr = []) {
        //print_arr($node_name);
        $xml = self::getXMLRoot();
        /** @noinspection PhpExpressionAlwaysNullInspection */
        $node = $xml->createElement($node_name);
        if (is_array($arr)) {
            if (array_key_exists(self::$labelAttributes, $arr) && is_array($arr[self::$labelAttributes])) {
                foreach ($arr[self::$labelAttributes] as $key => $value) {
                    if (!self::isValidTagName($key)) {
                        /** @noinspection PhpUnhandledExceptionInspection */
                        throw new Exception('[Array2XML] Illegal character in attribute name. attribute: '.$key.' in node: '.$node_name);
                    }
                    $node->setAttribute($key, self::bool2str($value));
                }
                unset($arr[self::$labelAttributes]);
            }

            if (array_key_exists(self::$labelValue, $arr)) {
                /** @noinspection PhpExpressionAlwaysNullInspection */
                $node->appendChild($xml->createTextNode(self::bool2str($arr[self::$labelValue])));
                unset($arr[self::$labelValue]);
                return $node;
            } elseif (array_key_exists(self::$labelCData, $arr)) {
                /** @noinspection PhpExpressionAlwaysNullInspection */
                $node->appendChild($xml->createCDATASection(self::bool2str($arr[self::$labelCData])));
                unset($arr[self::$labelCData]);
                return $node;
            }
        }

        if (is_array($arr)) {
            foreach ($arr as $key => $value) {
                if (!self::isValidTagName($key)) {
                    /** @noinspection PhpUnhandledExceptionInspection */
                    throw new Exception('[Array2XML] Illegal character in tag name. tag: '.$key.' in node: '.$node_name);
                }
                if (is_array($value) && is_numeric(key($value))) {
                    /** @noinspection PhpUnusedLocalVariableInspection */
                    foreach ($value as $k => $v) {
                        $node->appendChild(self::convert($key, $v));
                    }
                } else {
                    $node->appendChild(self::convert($key, $value));
                }
                unset($arr[$key]);
            }
        }

        /* TODO :
        if (!is_array($arr)) {
            // || preg_match('/[\'^£$%&*()}{@#~? ><>,|=_+¬-]/', $arr)
            if (strlen($arr) > 1000000000) {
                $node->appendChild($xml->createCDATASection(self::bool2str($arr)));
            } else {
                $node->appendChild($xml->createTextNode(self::bool2str($arr)));
            }
        }
        */
        if (!is_array($arr)) {
            /** @noinspection PhpExpressionAlwaysNullInspection */
            if ($arr === null) { $arr = ""; }
            $node->appendChild($xml->createTextNode(self::bool2str($arr)));
        }


        return $node;
    }

    private static function getXMLRoot() {
        if (empty(self::$xml)) {
            self::init();
        }
        return self::$xml;
    }

    private static function isValidTagName($tag) {
        /** @noinspection RegExpRedundantEscape */
        /** @noinspection RegExpSimplifiable */
        $pattern = '/^[a-z_]+[a-z0-9\:\-\.\_]*[^:]*$/i';

        return preg_match($pattern, $tag, $matches) && $matches[0] == $tag;
    }
}
