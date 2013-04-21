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
use DateTimeZone;
use Exception;

/**
 * La configuration du programme.
 *
 * Créée par les options en ligne de commande ou par un fichier de 
 * configuration YAML, cette classe permet aussi l’utilisation de nombreux 
 * paramètres avec des valeurs par défaut.
 * 
 * @copyright 2012 Michel Petit
 * @author Michel Petit <petit.michel@gmail.com> 
 */
class Config
{

    protected static $mixed_yaml = null;

    protected static $obj_instance = null;

    protected $str_name = null;
    
    protected $str_meta = null;
    
    protected $str_description = null;
    
    protected $str_timezone = 'UTC';
    
    protected $str_language = null;
    
    protected $str_server = Server::HOST;

    protected $arr_categories = null;
    
    protected $str_base = null;
    
    protected $str_permalink_tag = null;
    
    protected $str_permalink_category = null;

    protected $str_permalink_post = null;
    
    protected $obj_dir = null;

    protected $str_author = null;

    protected $int_related_posts = 0;

    protected $bool_disable_tags = false;
    
    protected $bool_disable_categories = false;


    protected static function basicCheck($str)
    {
        return preg_match('@/$@', $str);
    }



    /**
     * Permet de spécifier un fichier alternatif
     */
    public static function getInstanceWithConfigFile($str)
    {
        if(is_readable($str))
        {
            $mixed_yaml = Parser::parseYaml($str, true);

            if($mixed_yaml !== false)
            {
                self::$mixed_yaml = (object) $mixed_yaml;
                return self::getInstance();
            }
            else
            {
                throw new Exception(
                    sprintf('File %s is not a valid YAML setting file!', $str)
                );
            }
        }
        else
        {
            throw new Exception(
                sprintf('File %s does not exist or is not readable.', $str)
            );
        }
    }



    private function __construct()
    {
        $this->obj_dir = (object) array(
            'template' => Path::TEMPLATE,
            'post'     => Path::POST,
            'sample'   => Path::SAMPLE,
            'src'      => Path::SRC,
            'dest'     => Path::DEST
        );

        $this->str_base = Permalink::BASE;
        $this->str_permalink_tag = Permalink::TAG;
        $this->str_permalink_category = Permalink::CATEGORY;
        $this->str_permalink_post = Permalink::POST;

        if(!is_null(self::$mixed_yaml))
        {
            $this->setName(self::$mixed_yaml->name);
            $this->setMeta(self::$mixed_yaml->meta);
            $this->setDescription(self::$mixed_yaml->description);

            if(isset(self::$mixed_yaml->timezone))
                $this->setTimezone(self::$mixed_yaml->timezone);

            if(isset(self::$mixed_yaml->language))
                $this->setLanguage(self::$mixed_yaml->language);

            // si server actif, désactive l’URL de base pour avoir une 
            // navigation fonctionnnelle
            if(isset(self::$mixed_yaml->server) && self::$mixed_yaml->server == true)
            {
                $this->setServer(self::$mixed_yaml->server);
            }
            elseif(isset(self::$mixed_yaml->base))
                $this->setBase(self::$mixed_yaml->base);

            $this->setCategories(self::$mixed_yaml->categories);

            if(isset(self::$mixed_yaml->permalink['tag']))
                $this->setPermalinkTag(self::$mixed_yaml->permalink['tag']);
            
            if(isset(self::$mixed_yaml->permalink['category']))
                $this->setPermalinkCategory(self::$mixed_yaml->permalink['category']);
            
            if(isset(self::$mixed_yaml->permalink['post']))
                $this->setPermalinkPost(self::$mixed_yaml->permalink['post']);

            if(self::$mixed_yaml->dir['post'])
                $this->setPostDir(self::$mixed_yaml->dir['post']);

            if(self::$mixed_yaml->dir['sample'])
                $this->setSampleDir(self::$mixed_yaml->dir['sample']);

            if(self::$mixed_yaml->dir['src'])
                $this->setSrcDir(self::$mixed_yaml->dir['src']);

            if(self::$mixed_yaml->dir['dest'])
                $this->setDestDir(self::$mixed_yaml->dir['dest']);

            if(self::$mixed_yaml->dir['template'])
                $this->setTemplateDir(self::$mixed_yaml->dir['template']);
            
            if(isset(self::$mixed_yaml->author))
                $this->setAuthor(self::$mixed_yaml->author);
            
            if(isset(self::$mixed_yaml->disabletags))
                $this->setDisableTags();
            
            if(isset(self::$mixed_yaml->disablecategories))
                $this->setDisableCategories();
            
            if(isset(self::$mixed_yaml->related_posts) && self::$mixed_yaml->related_posts > 0)
                $this->setRelatedPosts(self::$mixed_yaml->related_posts);
            
            
        }
    }


    public function serverAvailable()
    {
        return(isset(self::$mixed_yaml->server) && self::$mixed_yaml->server == true);
    } 

    public static function getInstance()
    {
        if(is_null(self::$obj_instance))
        {
            self::$obj_instance = new self();
        }

        return self::$obj_instance;
    }

    
    public function setAuthor($str)
    {
        $this->str_author = $str;
    }

    public function setDisableTags()
    {
        $this->bool_disable_tags = true;
    }

    public function setDisableCategories()
    {
        $this->bool_disable_categories = true;
    }


    public function setName($str)
    {
        $this->str_name = $str;
    }


    public function setDescription($str)
    {
        $this->str_description = $str;
    }


    public function setMeta($str)
    {
        $this->str_meta = $str;
    }
    
    public function setServer($mixed)
    {
        if(is_bool($mixed))
        {
            if(!$mixed)
            {
                $this->str_server = $mixed;
            }
        }
        else
        {
            $this->str_server = $mixed;
        }
    }

    /**
     * setTimezone 
     * 
     * @param string $str 
     * @access public
     * @return string
     */
    public function setTimezone($str)
    {
        if(in_array($str, DateTimezone::listIdentifiers()))
        {
            $this->str_timezone = $str;
        }
        else
        {
            throw new Exception('Timezone is not valid.');
        }
    }



    public function setLanguage($str)
    {
        if(!is_string($str))
        {
            throw new Exception('Language must be a string.');
        }

        $str = trim($str);

        if(strlen($str) != 2)
        {
            throw new Exception('Language must be given into ISO 639-1 format.');
        }

        if(preg_match('/[0-9]/', $str))
        {
            throw new Exception('Not valid ISO 639-1 language format.');
        }

        $this->str_language = strtolower($str);
    }



    public function setCategories($arr)
    {
        $this->arr_categories = $arr;
    }



    public function setBase($str)
    {
        if(self::basicCheck($str))
        {
            $this->str_base = $str;
        }
        else
        {
            throw new Exception('Base URL must ending with a slash.');
        }
    }

    public function setPermalinkTag($str)
    {
        $this->str_permalink_tag = $str;
    }

    public function setPermalinkCategory($str)
    {
        $this->str_permalink_category = $str;
    }

    public function setPermalinkPost($str)
    {
        $this->str_permalink_post = $str;
    }

    public function setPostDir($str)
    {
        if(self::basicCheck($str))
        {
            $this->obj_dir->post = $str;
        }
        else
        {
            throw new Exception('Custom posts’ directory must have a slash at the end.');
        }
    }
    
    public function setSampleDir($str)
    {
        if(self::basicCheck($str))
        {
            $this->obj_dir->sample = $str;
        }
        else
        {
            throw new Exception('Custom samples’ texts’ directory must have a slash at the end.');
        }
    }
    
    public function setSrcDir($str)
    {
        if(self::basicCheck($str))
        {
            $this->obj_dir->src = $str;
        }
        else
        {
            throw new Exception('Custom source directory must have a slash at the end.');
        }
    }

    public function setDestDir($str)
    {
        if(self::basicCheck($str))
        {
            $this->obj_dir->dest = $str;
        }
        else
        {
            throw new Exception('Custom destination directory must have a slash at the end.');
        }
    }

    public function setTemplateDir($str)
    {
        if(self::basicCheck($str))
        {
            $this->obj_dir->template = $str;
        }
        else
        {
            throw new Exception('Custom destination directory must have a slash at the end.');
        }
    }

    public function setRelatedPosts($int)
    {
        if($int >= 0)
        {
            $this->int_related_posts = $int;
        }
        else
        {
            throw new Exception('Related posts must be a positive value or 0.');
        }
    }
    
    public function getAuthor()
    {
        return $this->str_author;
    }

    public function getDisableTags()
    {
        return $this->bool_disable_tags;
    }

    public function getDisableCategories()
    {
        return $this->bool_disable_categories;
    }


    public function getName()
    {
        return $this->str_name;
    }

    public function getMeta()
    {
        return $this->str_meta;
    }

    public function getDescription()
    {
        return $this->str_description;
    }

    public function getRelatedPosts()
    {
        return $this->int_related_posts;
    }

    public function getTimezone()
    {
        return $this->str_timezone;
    }

    public function getLanguage()
    {
        return $this->str_language;
    }

    public function getServer()
    {
        return $this->str_server;
    }

    public function getCategories()
    {
        return $this->arr_categories;
    }

    public function hasCategory($str)
    {
        return isset($this->arr_categories[$str]);
    }

    public function getCategory($str)
    {
        return $this->arr_categories[$str];
    }

    public function getBase()
    {
        return $this->str_base;
    }

    public function getPermalinkTag()
    {
        return $this->str_permalink_tag;
    }
    
    public function getPermalinkCategory()
    {
        return $this->str_permalink_category;
    }
    
    public function getPermalinkPost()
    {
        return $this->str_permalink_post;
    }

    //TODO: Créer des raccourcis du genre getDirPost()
    public function getDir()
    {
        return $this->obj_dir;
    }
}
