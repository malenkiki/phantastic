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

class History
{
    const LAST_HOME = 7;

    protected static $arr_hist = array();

    protected $str_date = null;
    protected $int_id = 0;

    public function __construct($str_date)
    {
        $this->str_date = $str_date;
    }

    public static function getHist()
    {
        krsort(self::$arr_hist);
        return self::$arr_hist;
    }

    public static function set($str_date)
    {
        $hist = new self($str_date);
        $hist->addToHist();

        return self::$arr_hist[$str_date];
    }


    public function addToHist()
    {
        if(!isset(self::$arr_hist[$this->str_date]))
        {
            self::$arr_hist[$this->str_date] = $this;
        }
    }




    public function setId($int_id)
    {
        if($int_id > 0)
        {
            $this->int_id = $int_id;
        }
    }


    /**
     * File's ID
     * 
     * @access public
     * @return integer
     */
    public function getFileId()
    {
        return $this->int_id;
    }
    
    
    protected static function getPrevNext($type, $k)
    {
        $pn = null;

        if($type == 'prev')
        {
            ksort(self::$arr_hist);
        }
        else
        {
            krsort(self::$arr_hist);
        }

        foreach(self::$arr_hist as $hk => $hv)
        {
            if($hk == $k)
            {
                if(is_null($pn))
                {
                    return null;
                }
                else
                {
                    return self::$arr_hist[$pn]->getFileId();
                }
            }
            else
            {
                $pn = $hk;
            }
        }

        return null;
    }
    
    public static function getPrevFor($k)
    {
        return self::getPrevNext('prev', $k);
    }
    
    
    
    /**
     * Gets next post for given one. 
     * 
     * @todo k is integer or date?
     * @param mixed $k 
     * @static
     * @access public
     * @return void
     */
    public static function getNextFor($k)
    {
        return self::getPrevNext('next', $k);
    }



    /**
     * Gets n last posts. 
     * 
     * @param integer $n Number of posts
     * @static
     * @access public
     * @return array
     */
    public static function getLast($n = self::LAST_HOME)
    {
        $arrProv = array();

        krsort(self::$arr_hist);

        foreach(self::$arr_hist as $h)
        {
            if(count($arrProv) < $n)
            {
                $arrProv[] = $h->getFileId();
            }
            else
            {
                break;
            }
        }

        return $arrProv;
    }

    
    
    /**
     * Gets elements for givent date 
     * 
     * @todo code this feature.
     * @param mixed $date 
     * @static
     * @access public
     * @return array
     */
    public static function getFor($date)
    {
    }

}
