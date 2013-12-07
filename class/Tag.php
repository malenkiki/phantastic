<?php
/*
Copyright (c) 2013 Michel Petit <petit.michel@gmail.com>

Permission is hereby granted, free of charge, to any person obtaining
a copy of this software and associated documentation files (the
"Software"), to deal in the Software without restriction, including
without limitation the rights to use, copy, modify, merge, publish,
distribute, sublicense, and/or sell copies of the Software, and to
permit persons to whom the Software is furnished to do so, subject to
the following conditions:

The above copyright notice and this permission notice shall be
included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */


namespace Malenki\Phantastic;

/**
 * Supporte à la fois un Tag en tant que tel et un Tag Cloud pour l’ensemble du 
 * site. 
 * 
 * @copyright 2012 Michel Petit
 * @author Michel Petit <petit.michel@gmail.com> 
 */
class Tag
{
    protected static $arr_cloud = array();
    protected static $int_min_count = 0;
    protected static $int_max_count = 0;
    protected $str_name;
    protected $str_slug = null;
    protected $str_style = null;
    protected $arr_ids = array();

    public function __construct($str_name)
    {
        $this->str_name = $str_name;

    }

    public static function isEmpty()
    {
        return count(self::$arr_cloud) == 0;
    }

    public static function getCloud()
    {
        return self::$arr_cloud;
    }

    public static function set($str_name)
    {
        $tag = new self($str_name);
        $tag->addToCloud();
        return self::$arr_cloud[$tag->getSlug()];
    }


    public function addToCloud()
    {
        if(!isset(self::$arr_cloud[$this->getSlug()]))
        {
            self::$arr_cloud[$this->getSlug()] = $this;
        }
    }

    protected function setStyle($str)
    {
        $this->str_style = $str;
    }

    protected function setMinMax()
    {
        if(self::$int_min_count == 0 || $this->getCount() < self::$int_min_count)
        {
            self::$int_min_count = $this->getCount();
        }
        
        if(self::$int_max_count == 0 || $this->getCount() > self::$int_max_count)
        {
            self::$int_max_count = $this->getCount();
        }
    }

    protected function computeStyle()
    {
        
        $int_spread = self::$int_max_count - self::$int_min_count;
        
        if($int_spread <= 0) $int_spread = 1;
        
        $float_css_step = 4 / $int_spread;


        foreach(self::$arr_cloud as $str_name => $obj)
        {
            $float_level = 1  + ($obj->getCount()  - self::$int_min_count) * $float_css_step;

            if($float_level <= 1) $obj->setStyle('xsmall');
            if($float_level <= 2 && $float_level > 1) $obj->setStyle('small');
            if($float_level <= 3 && $float_level > 2) $obj->setStyle('medium');
            if($float_level <= 4 && $float_level > 3) $obj->setStyle('large');
            if($float_level <= 5 && $float_level > 4) $obj->setStyle('xlarge');
            
            self::$arr_cloud[$str_name] = $obj;
        }
    }

    public function addId($int_id)
    {
        if($int_id > 0)
        {
            $this->arr_ids[] = $int_id;
            $this->setMinMax();
            self::computeStyle();
        }
    }
    
    
    public static function get($str_name)
    {
        return self::$arr_cloud[Permalink::createSlug($str_name)];
    }


    /**
     * @return integer
     */
    public function getCount()
    {
        return count($this->arr_ids);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->str_name;
    }

    /**
     * @return string
     */
    public function getStyle()
    {
        return $this->str_style;
    }

    /**
     * @return string
     */
    public function getSlug()
    {
        if(is_null($this->str_slug))
        {
            $this->str_slug = Permalink::createSlug($this->str_name);
        }

        return $this->str_slug;
    }


    public function getUrl($full = false)
    {
        $url = new Permalink(Config::getInstance()->getPermalinkTag());
        $url->setTitle($this->getSlug());

        if($url->isOk())
        {
            return $url->getUrl($full);
        }
        else
        {
            throw new \Exception('Issue occured while building tag’s URL!');
        }
    }


    /**
     * ID des fichiers
     * 
     * @access public
     * @return array
     */
    public function getFileIds()
    {
        return $this->arr_ids;
    }
}
