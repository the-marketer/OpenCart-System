<?php
/**
 * @copyright   © EAX LEX SRL. All rights reserved.
 **/

/**
 * Usage:
 * Array2XML::cXML("root_node_name",$array)->saveXML();
 */

namespace Mktr\Helper;

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

    public static $noNull = false;

    protected static $xml;

    protected static $domVersion;

    protected static $encoding;

    protected static $standalone;

    protected static $formatOutput;

    protected static $labelAttributes;

    protected static $labelCData;

    protected static $labelDocType;

    protected static $labelValue;

    private static $last_xml;

    private static $errors;

    private static $CDataStatus = false;
    private static $CDataValues = array();

    private static $nodeAdd = false;

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

        self::$xml = new \DOMDocument(self::getDomVersion(), self::getEncoding());

        // self::$xml->xmlStandalone = self::isStandalone();

        self::$xml->formatOutput = self::isFormatOutput();
        self::$nodeAdd = true;

        return self::$xml;
    }

    public static function getDomVersion()
    {
        if (self::$domVersion !== null) {
            return self::$domVersion;
        } else {
            return self::DEFAULT_DOM_VERSION;
        }
    }

    public static function getEncoding()
    {
        if (self::$encoding !== null) {
            return self::$encoding;
        } else {
            return self::DEFAULT_ENCODING;
        }
    }

    public static function isStandalone()
    {
        if (self::$standalone !== null) {
            return self::$standalone;
        } else {
            return self::DEFAULT_STANDALONE;
        }
    }

    public static function isFormatOutput()
    {
        if (self::$formatOutput !== null) {
            return self::$formatOutput;
        } else {
            return self::DEFAULT_FORMAT_OUTPUT;
        }
    }

    protected static function setDomVersion($domVersion = null)
    {
        if ($domVersion !== null) {
            self::$domVersion = $domVersion;
        } else {
            self::$domVersion = self::DEFAULT_DOM_VERSION;
        }
    }

    protected static function setEncoding($encoding = null)
    {
        if ($encoding !== null) {
            self::$encoding = $encoding;
        } else {
            self::$encoding = self::DEFAULT_ENCODING;
        }
    }

    protected static function setStandalone($standalone = null)
    {
        if ($standalone !== null) {
            self::$standalone = $standalone;
        } else {
            self::$standalone = self::DEFAULT_STANDALONE;
        }
    }

    protected static function setFormatOutput($formatOutput = null)
    {
        if ($formatOutput !== null) {
            self::$formatOutput = $formatOutput;
        } else {
            self::$formatOutput = self::DEFAULT_FORMAT_OUTPUT;
        }
    }

    public static function getLabelAttributes()
    {
        if (self::$labelAttributes !== null) {
            return self::$labelAttributes;
        } else {
            return self::LABEL_ATTRIBUTES;
        }
    }

    public static function getLabelCData()
    {
        if (self::$labelCData !== null) {
            return self::$labelCData;
        } else {
            return self::LABEL_CDATA;
        }
    }

    public static function getLabelDocType()
    {
        if (self::$labelDocType !== null) {
            return self::$labelDocType;
        } else {
            return self::LABEL_DOCTYPE;
        }
    }

    public static function getLabelValue()
    {
        if (self::$labelValue !== null) {
            return self::$labelValue;
        } else {
            return self::LABEL_VALUE;
        }
    }

    protected static function setLabelAttributes($labelAttributes = null)
    {
        if ($labelAttributes !== null) {
            self::$labelAttributes = $labelAttributes;
        } else {
            self::$labelAttributes = self::LABEL_ATTRIBUTES;
        }
    }

    protected static function setLabelCData($labelCData = null)
    {
        if ($labelCData !== null) {
            self::$labelCData = $labelCData;
        } else {
            self::$labelCData = self::LABEL_CDATA;
        }
    }

    public static function setCDataValues($name)
    {
        self::$CDataStatus = true;
        if (is_array($name)) {
            self::$CDataValues = $name;
        } else {
            self::$CDataValues[] = $name;
        }
    }

    protected static function setLabelDocType($labelDocType = null)
    {
        if ($labelDocType !== null) {
            self::$labelDocType = $labelDocType;
        } else {
            self::$labelDocType = self::LABEL_DOCTYPE;
        }
    }

    protected static function setLabelValue($labelValue = null)
    {
        if ($labelValue !== null) {
            self::$labelValue = $labelValue;
        } else {
            self::$labelValue = self::LABEL_VALUE;
        }
    }

    private static function getXMLRoot()
    {
        if (self::$xml === null) {
            return self::init();
        }

        return self::$xml;
    }

    public static function errors()
    {
        return self::$errors;
    }

    public static function Memory()
    {
        if (self::$last_xml === null) {
            return self::init();
        }

        return self::$last_xml;
    }

    public static function toObject($string = null)
    {
        if ($string === null) {
            $string = self::Memory()->saveXML();
        }

        return simplexml_load_string($string, 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NOBLANKS);
    }

    public function createXML($node_name, $arr = null, $docType = array())
    {
        return self::cXML($node_name, $arr, $docType);
    }

    private static function bool2str($v)
    {
        return $v === true ? 'true' : (($v === false) ? 'false' : $v);
    }

    private static function isValidTagName($tag)
    {
        $pattern = '/^[a-z_]+[a-z\d:\-._]*[^:]*$/i';

        return preg_match($pattern, $tag, $matches) && $matches[0] == $tag;
    }

    public static function cXML($node_name, $arr = null, $docType = array())
    {
        self::getXMLRoot();
        try {
            if ($docType) {
                self::$xml->appendChild(
                    (new \DOMImplementation())->
                        createDocumentType(
                            isset($docType['name']) ? $docType['name'] : '',
                            isset($docType['publicId']) ? $docType['publicId'] : '',
                            isset($docType['systemId']) ? $docType['systemId'] : ''
                        )
                );
            }
            if ($arr == null && self::$nodeAdd) {
                self::$nodeAdd = false;
                $node_name = array($node_name => '');
                // self::$xml->appendChild(self::$xml->createElement( $node_name ));
            }

            if ($arr == null) {
                foreach ($node_name as $key => $value) {
                    self::$xml->appendChild(self::convert($key, $value));
                }
            } else {
                self::$xml->appendChild(self::convert($node_name, $arr));
            }

            self::$last_xml = self::$xml;

            self::$xml = null;

            return self::$last_xml;
        } catch (Exception $e) {
            return self::$xml;
        }
    }

    /**
     * @throws Exception
     */
    private static function convert($node_name, $arr = array())
    {
        self::getXMLRoot();

        $node = self::$xml->createElement($node_name);

        if (self::$CDataStatus && !is_array($arr) && in_array($node_name, self::$CDataValues) && $arr !== null) {
            $arr = array('@cdata' => $arr);
        }

        if (is_array($arr)) {
            if (array_key_exists(self::getLabelAttributes(), $arr) && is_array($arr[self::getLabelAttributes()])) {
                foreach ($arr[self::getLabelAttributes()] as $key => $value) {
                    if (!self::isValidTagName($key)) {
                        $error = 'Illegal character in attribute name. attribute: ' . $key . ' in node: ' . $node_name;
                        self::$errors[] = $error;
                        throw new \Exception($error);
                    }
                    $node->setAttribute($key, self::bool2str($value));
                }
                unset($arr[self::getLabelAttributes()]);
            }

            if (array_key_exists(self::getLabelValue(), $arr)) {
                $node->appendChild(self::$xml->createTextNode(self::bool2str($arr[self::getLabelValue()])));
                unset($arr[self::getLabelValue()]);

                return $node;
            } elseif (array_key_exists(self::getLabelCData(), $arr)) {
                $node->appendChild(self::$xml->createCDATASection(self::bool2str($arr[self::getLabelCData()])));
                unset($arr[self::getLabelCData()]);

                return $node;
            }

            foreach ($arr as $key => $value) {
                if (!self::isValidTagName($key)) {
                    $error = 'Illegal character in tag name. tag: ' . $key . ' in node: ' . $node_name;
                    self::$errors[] = $error;
                    throw new \Exception($error);
                }
                if (is_array($value) && is_numeric(key($value))) {
                    foreach ($value as $v) {
                        if (self::$noNull && $v === null) {
                            continue;
                        }
                        $node->appendChild(self::convert($key, $v));
                    }
                } else {
                    if (self::$noNull && $value === null) {
                        continue;
                    }
                    $node->appendChild(self::convert($key, $value));
                }
                unset($arr[$key]);
            }
        }

        if (!is_array($arr)) {
            if (self::$noNull && $arr === null) {
                return $node;
            }
            if ($arr === null) {
                $arr = '';
            }
            $node->appendChild(self::$xml->createTextNode(self::bool2str($arr)));
        }

        return $node;
    }
}
