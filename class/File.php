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

use Malenki\Phantastic\Parser as Parser;

class File
{
    protected static $last_id = 0;
    protected $id = null;
    protected $obj_path = null;
    protected $str_url = null;
    protected $str_cat_key = null;
    protected $obj_head = null;
    protected $str_content = null;

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

    public function hasHeader()
    {
        return is_object($this->obj_head);
    }

    public function getHeader()
    {
        return $this->obj_head;
    }

    public function getContent()
    {
        return $this->str_content;
    }

    public function getId()
    {
        return $this->id;
    }

    public function isPost()
    {
        return $this->hasHeader() && preg_match(
            '@'. Config::getInstance()->getDir()->src . Config::getInstance()->getDir()->post . '@',
            $this->obj_path->getPath() . Path::getDirectorySeparator()
        );
    }

    public function isPage()
    {
        return ($this->hasHeader() && !$this->isPost());
    }

    /**
     * Détermine si le fichier est un fichier à interpréter ou non.
     *
     * @access public
     * @return boolean
     */
    public function isFile()
    {
        return(!$this->isPost() && !$this->isPage());
    }

    public function isSample()
    {
        return(
            $this->isFile()
            &&
            preg_match('@'.Path::getSrcSample().'@', $this->obj_path->getPathname())
        );
    }

    public function getTitleSlug()
    {
        return Permalink::createSlug($this->getHeader()->title);
    }



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

    public function hasCategory()
    {
        return is_object($this->getCategory());
    }

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
     * Retourne un timestamp UNIX.
     *
     * Retourne le timestamp UNIX de la date du fichier, en prenant soit 
     * l’attribut "date" de l’en-tête YAML, soit la date du fichier si cet 
     * attribut n’existe pas. 
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
     * Obtient la date au format ISO 8601 utilisé dans les flux Atom 
     * 
     * @access public
     * @return string
     */
    public function getDateAtom()
    {
        return(date('c', $this->getDate()));
    }

    /**
     * Obtient la date du fichier au format RFC822, utile pour la génération des RSS. 
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
