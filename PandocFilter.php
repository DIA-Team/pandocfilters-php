<?php

/**
 * Class PandocFilter
 * 
 * Methods to aid writing PHP scripts that process
 * the pandoc AST serialized JSON
 * 
 * Ported from https://github.com/jgm/pandocfilters/blob/master/pandocfilters.py
 */
class PandocFilter
{
    public static $source = 'php://stdin';

    /**
     * Walk a tree, applying an action to every object.
     * Returns a modified tree.
     * 
     * @param array|object|string $x
     * @param callable $action
     * @param mixed $format
     * @param mixed $meta
     * @return array|object|string
     */
    public static function walk($x, $action, $format, $meta)
    {
        if (is_array($x)) {
            $array = array();
            foreach ($x as $item) {
                if (is_object($item) && isset($item->t)) {
                    $res = $action($item->t, $item->c ?? '', $format, $meta);
                    if (is_null($res)) {
                        $array[] = self::walk($item, $action, $format, $meta);
                    } elseif (is_array($res)) {
                        foreach ($res as $z) {
                            $array[] = self::walk($z, $action, $format, $meta);
                        }
                    } elseif (is_object($res)) {
                        $array[] = self::walk($res, $action, $format, $meta);
                    } else {
                        $obj = clone $item;
                        $obj->c = "$res";
                        $array[] = $obj;
                    }
                } else {
                    $array[] = self::walk($item, $action, $format, $meta);
                }
            }
            return $array;
        } elseif (is_object($x)) {
            $obj = clone $x;
            foreach (get_object_vars($x) as $k => $v) {
                $obj->{$k} = self::walk($v, $action, $format, $meta);
            }
            return $obj;
        } else {
            return $x;
        }
    }

    /**
     * Converts an action into a filter that reads a JSON-formatted
     * pandoc document from stdin, transforms it by walking the tree
     * with the action, and returns a new JSON-formatted pandoc document
     * to stdout.
     * The argument is a function action(key, value, format, meta),
     * where key is the type of the pandoc object (e.g. 'Str', 'Para'),
     * value is the contents of the object (e.g. a string for 'Str',
     * a list of inline elements for 'Para'), format is the target
     * output format (which will be taken for the first command line
     * argument if present), and meta is the document's metadata.
     * If the function returns NULL, the object to which it applies
     * will remain unchanged. If it returns an object, the object will
     * be replaced. If it returns a list, the list will be spliced in to
     * the list to which the target object belongs. (So, returning an
     * empty list deletes the object.)
     * 
     * @param callable $action
     * @param string $source (For debugging purposes)
     */
    public static function toJSONFilter($action, $source = null)
    {
        if (! $source) $source = self::$source;
        $doc = json_decode(file_get_contents($source));
        if (count($GLOBALS['argv']) > 1) {
            $format = $GLOBALS['argv'][1];
        } else {
            $format = '';
        }
        $doc->blocks = self::walk($doc->blocks, $action, $format, $doc->meta);
        $json = json_encode($doc, JSON_PRETTY_PRINT);
        
        echo $json . PHP_EOL;
    }

    /**
     * Walks the tree x and returns concatenated string content,
     * leaving out all formatting.
     * 
     * @param array|object|string $x
     * @return string
     */
    public static function stringify($x)
    {
        $o = (object) array('result' => array());
        $go = function($key, $val, $format, $meta) use ($o) {
            if ('Str' == $key) {
                $o->result[] = $val;
            } elseif ('Code' == $key) {
                $o->result[] = $val[1];
            } elseif ('Math' == $key) {
                $o->result[] = $val[1];
            } elseif ('LineBreak' == $key) {
                $o->result[] = " ";
            } elseif ('Space' == $key) {
                $o->result[] = " ";
            }
        };
        self::walk($x, $go, '', array());
        return implode('', $o->result);
    }
    
    /**
     * Returns an attribute list, constructed from the
     * attrs array.
     * 
     * @param object $attrs
     * @return array
     */
    public function attributes($attrs)
    {
        $ident = @$attrs->id ?: '';
        $classes = @$attrs->classes ?: array();
        $keyvals = array();
        foreach (get_object_vars($attrs) as $k => $v) {
            if ('id' != $k && 'classes' != $k) {
                $keyvals[$k] = $v;
            }
        }
        return array($ident, $classes, $keyvals);
    }
    
    /**
     * @param string $eltType
     * @param int $numArgs
     * @return callable
     * @throws BadMethodCallException
     */
    public static function elt($eltType, $numArgs)
    {
        $fun = function() use ($eltType, $numArgs) {
            $lenargs = func_num_args();
            if ($lenargs != $numArgs) {
                throw new BadMethodCallException(sprintf(
                    "%s expects %d arguments, but given %d", $eltType, $numArgs, $lenargs
                ));
            }
            if ($lenargs == 1) {
                $xs = func_get_arg(0);
            } else {
                $xs = func_get_args();
            }
            return (object) array('t' => $eltType, 'c' => $xs);
        };
        return $fun;
    }
}

# Constructors for block elements

$Plain = PandocFilter::elt('Plain',1);
$Para = PandocFilter::elt('Para',1);
$CodeBlock = PandocFilter::elt('CodeBlock',2);
$RawBlock = PandocFilter::elt('RawBlock',2);
$BlockQuote = PandocFilter::elt('BlockQuote',1);
$OrderedList = PandocFilter::elt('OrderedList',2);
$BulletList = PandocFilter::elt('BulletList',1);
$DefinitionList = PandocFilter::elt('DefinitionList',1);
$Header = PandocFilter::elt('Header',3);
$HorizontalRule = PandocFilter::elt('HorizontalRule',0);
$Table = PandocFilter::elt('Table',5);
$Div = PandocFilter::elt('Div',2);
$Null = PandocFilter::elt('Null',0);

# Constructors for inline elements

$Str = PandocFilter::elt('Str',1);
$Emph = PandocFilter::elt('Emph',1);
$Strong = PandocFilter::elt('Strong',1);
$Strikeout = PandocFilter::elt('Strikeout',1);
$Superscript = PandocFilter::elt('Superscript',1);
$Subscript = PandocFilter::elt('Subscript',1);
$SmallCaps = PandocFilter::elt('SmallCaps',1);
$Quoted = PandocFilter::elt('Quoted',2);
$Cite = PandocFilter::elt('Cite',2);
$Code = PandocFilter::elt('Code',2);
$Space = PandocFilter::elt('Space',0);
$LineBreak = PandocFilter::elt('LineBreak',0);
$Math = PandocFilter::elt('Math',2);
$RawInline = PandocFilter::elt('RawInline',2);
$Link = PandocFilter::elt('Link',2);
$Image = PandocFilter::elt('Image',2);
$Note = PandocFilter::elt('Note',1);
$Span = PandocFilter::elt('Span',2);
