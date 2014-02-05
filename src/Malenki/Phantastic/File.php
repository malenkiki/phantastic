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

use Malenki\Phantastic\Parser as Parser;

/**
 * File object to have some information about its content and know the type of 
 * document it is.
 * 
 * @author Michel Petit <petit.michel@gmail.com> 
 * @license MIT
 */
class File
{
    protected static $last_id = 0;
    protected $id = null;
    protected $obj_path = null;
    protected $str_url = null;
    protected $str_cat_key = null;
    protected $obj_head = null;
    protected $str_content = null;




    /**
     * Reads file's content and parses it to getsome subpart easyly. 
     * 
     * @access protected
     * @return void
     */
    protected function read()
    {
        $p = new Parser($this->obj_path->getRealPath());
        if($p->hasHeader())
        {
            $this->obj_head = (object) $p->getHeader();

            if($p->hasContent())
            {
                $this->str_content = $p->getContent();
            }
        }

    }



    public function __construct($path)
    {
        $this->obj_path = $path;
        self::$last_id++;
        $this->id = self::$last_id;
        $this->read();
    }



    /**
     * Checks if current file has a YAML header 
     * 
     * @access public
     * @return boolean
     */
    public function hasHeader()
    {
        return is_object($this->obj_head);
    }



    /**
     * Gets header's information as an object. 
     * 
     * @access public
     * @return \stdClass
     */
    public function getHeader()
    {
        return $this->obj_head;
    }



    /**
     * Gets file's content as a string
     * 
     * @access public
     * @return string
     */
    public function getContent()
    {
        return $this->str_content;
    }



    /**
     * Get generated ID of the current file 
     * 
     * @access public
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }



    /**
     * Checks whether the current file is a post or not. 
     * 
     * @access public
     * @return boolean
     */
    public function isPost()
    {
        return $this->hasHeader() && preg_match(
            '@'. Config::getInstance()->getDir()->src . Config::getInstance()->getDir()->post . '@',
            $this->obj_path->getPath() . Path::getDirectorySeparator()
        );
    }



    /**
     * Tests if the current file is a page. 
     * 
     * @access public
     * @return boolean
     */
    public function isPage()
    {
        return ($this->hasHeader() && !$this->isPost());
    }



    /**
     * Checks if current file is other thing as post or page.
     *
     * @access public
     * @return boolean
     */
    public function isFile()
    {
        return(!$this->isPost() && !$this->isPage());
    }



    /**
     * Tests whether current file is a sample.
     *
     * Samples are pièce of HTML block that can be used where you want into templates. 
     * 
     * @access public
     * @return boolean
     */
    public function isSample()
    {
        return(
            $this->isFile()
            &&
            preg_match('@'.Path::getSrcSample().'@', $this->obj_path->getPathname())
        );
    }



    /**
     * Gets title URL component 
     * 
     * @access public
     * @return string
     */
    public function getTitleSlug()
    {
        return Permalink::createSlug($this->getHeader()->title);
    }




    /**
     * Gets final file's URL.
     * 
     * @throw \Exception If an error occurs when building URL.
     * @param boolean $full By default at False, maust be True if you want full URL (with domain…)
     * @access public
     * @return string
     */
    public function getUrl($full = false)
    {
        if($this->isPost())
        {
            //Prendre ce qui est défini dans le fichier de configuration 
            //SAUF si une directive « permalink » existe dans l’en-tête 
            //YAML du fichier
            if(isset($this->getHeader()->permalink))
            {
                $str_permalink = $this->getHeader()->permalink;
            }
            else
            {
                $str_permalink = Config::getInstance()->getPermalinkPost();
            }
        }
        else if($this->isPage())
        {
            //Prendre la directive « permalink » définie dans l’en-tête YAML
            //Par exemple /a-propos/
            $str_permalink = $this->getHeader()->permalink;
        }
        else
        {
            //Prendre le chemin tel que défini dans la source.
            $str_permalink = preg_replace(
                '@' . Path::getSrc() . '@',
                '',
                $this->getSrcPath()
            );

            return Permalink::cleanUrl($str_permalink);
        }

        $url = new Permalink($str_permalink);
        $url->setTitle($this->getTitleSlug());
        $url->setYear($this->getYear());
        $url->setMonth($this->getMonth());
        $url->setDay($this->getDay());

        // Les catégories n’existent que pour les Posts
        if($this->hasCategory())
        {
            $url->setCategories($this->getCategory());
        }



        if($url->isOk())
        {
            return $url->getUrl($full);
        }
        else
        {
            throw new \Exception('Issue occured while building URL!');
        }
    }



    /**
     * Tests whether the current file has a category or not.
     * 
     * @access public
     * @return boolean
     */
    public function hasCategory()
    {
        return is_object($this->getCategory());
    }




    /**
     * Gets file's category.
     * 
     * @access public
     * @return mixed Category if they are one, null otherwise
     */
    public function getCategory()
    {
        if($this->isPost())
        {
            if(is_null($this->str_cat_key))
            {
                if($this->getObjPath()->getPath(). Path::getDirectorySeparator() == Path::getSrcPost())
                {
                    $key = Path::getDirectorySeparator();
                }
                else
                {
                    $key = preg_replace('@'.Path::getSrcPost().'@', '',$this->getObjPath()->getPath());
                }

                $this->str_cat_key = $key;
            }

            return Category::getHier($this->str_cat_key);
        }
        else
        {
            return null;
        }
    }



    /**
     * Gets an UNIX timestamp.
     *
     * Gets an UNIX timestamp of file's date, taking `date` attribute of the 
     * YAML header or file's date otherwise.
     * 
     * @access public
     * @return integer
     */
    public function getDate()
    {
        if(isset($this->getHeader()->date))
        {
            if(is_integer($this->getHeader()->date))
            {
                // la date peut être directement un timestamp si YAML de Symfony
                return $this->getHeader()->date;
            }
            else
            {
                return strtotime($this->getHeader()->date);
            }
        }
        else
        {
            return $this->obj_path->getMTime();
        }
    }




    /**
     * Gets file's date as ISO 8601 format as used into Atom. 
     * 
     * @access public
     * @return string
     */
    public function getDateAtom()
    {
        return(date('c', $this->getDate()));
    }



    /**
     * Gets file's date as RFC822, used into RSS. 
     * 
     * @access public
     * @return string
     */
    public function getDateRss()
    {
        return(date('r', $this->getDate()));
    }



    public function getYear()
    {
        return(date('Y', $this->getDate()));
    }

    public function getMonth()
    {
        return(date('m', $this->getDate()));
    }

    public function getDay()
    {
        return(date('d', $this->getDate()));
    }
    public function getHour()
    {
        return(date('H', $this->getDate()));
    }
    
    public function getMinute()
    {
        return(date('i', $this->getDate()));
    }

    public function getSeconde()
    {
        return(date('s', $this->getDate()));
    }

    public function getSrcPath()
    {
        return $this->obj_path->getPathName();
    }

    public function getObjPath()
    {
        return $this->obj_path;
    }
}
