<?php
/*
 * This file is part of Phantastic.
 *
 * Phantastic is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * Phantastic is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with Phantastic.  If not, see <http://www.gnu.org/licenses/>.
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
     * ID du fichier
     * 
     * @access public
     * @return int
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
    
    
    
    public static function getNextFor($k)
    {
        return self::getPrevNext('next', $k);
    }

    /**
     * getLast 
     * 
     * @param integer $n 
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

    //TODO: à faire quand je déciderai de distribuer le code, pas urgent 
    //pour mon cas.
    public static function getFor($date){}

}
