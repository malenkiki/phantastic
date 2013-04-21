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

/**
 * Prend en charge les catégories.
 *
 * Les catégories sont supportées par un double fonctionnement :
 * - La structure des dossiers organisant les Posts donne les URL des catégories
 * - Le fichier de configuration comporte les traductions éventuelles.
 *
 * Avec cette classe, on a la liste des Posts pour chaque nœud.
 * 
 * @copyright 2012 Michel Petit
 * @author Michel Petit <petit.michel@gmail.com> 
 */
class Category
{
    protected static $arr_hier = array();

    protected $arr_name;
    protected $arr_node = array();
    protected $str_slug = null;
    protected $arr_ids = array();

    public function __construct($str_path)
    {
        $str_root = sprintf(
            '%s%s',
            Config::getInstance()->getDir()->src,
            Config::getInstance()->getDir()->post
        );


        $this->arr_node = explode(
            '/',
            preg_replace("@^$str_root@", '', $str_path)
        );

        // cas où le post n’a pas de catégorie
        if(isset($this->arr_node[0]) && $this->arr_node[0] == '')
        {
            $this->arr_node = array();
        }

        if(count($this->arr_node))
        {
            $arr_cat = Config::getInstance()->getCategories();
            
            for($i = 0; $i < count($this->arr_node); $i++)
            {
                if(isset($arr_cat[$this->arr_node[$i]]))
                {
                    $this->arr_name[] = $arr_cat[$this->arr_node[$i]];
                }
                else
                {
                    $this->arr_name[] = $this->arr_node[$i];
                }
            }
        }

    }

    /**
     * Retourne les catégories, ou une d’entre elles si un argument est passé. 
     * 
     * @param string $key 
     * @static
     * @access public
     * @return mixed Soit un array soit un objet Category soit null
     */
    public static function getHier($key = null)
    {
        if(is_null($key))
        {
            return self::$arr_hier;
        }
        else
        {
            if(isset(self::$arr_hier[$key]))
            {
                return self::$arr_hier[$key];
            }
            else
            {
                return null;
            }
        }
    }

    public static function isEmpty()
    {
        if(array_key_exists('/', self::$arr_hier))
        {
            //Il y a toujours la catégorie racine
            return count(self::$arr_hier) == 1; 
        }
        
        return count(self::$arr_hier) == 0; 
    }
    
    protected static function getTreeRecursive(Category $obj_cat, $arr_tree)
    {
        $arr_path = $obj_cat->getNode();

        $arr_original =& $arr_tree;

        foreach ($arr_path as $str_node)
        {
            if (!array_key_exists($str_node, $arr_tree))
            {
                $arr_tree[$str_node] = array();
            }

            if ($str_node)
            {
                $arr_tree =& $arr_tree[$str_node];
            }
        }

        return $arr_original;
    }


    /**
     * Construit et retourne l’arbre hiérarchique des catégories. 
     * 
     * @static
     * @access public
     * @return array
     */
    public static function getTree()
    {
        $arr_tree = array();

        foreach(self::$arr_hier as $obj_cat)
        {
            $arr_prov = $obj_cat->getNode();

            if(count($arr_prov) == 0) continue;

            $arr_tree = self::getTreeRecursive($obj_cat, $arr_tree);
        }

        return $arr_tree;
    }

    /**
     * Pour un niveau demandé, retourne les IDs de fichiers correspondants.
     * 
     * @param integer $int_level 
     * @static
     * @access public
     * @return  array
     */
    public static function getFileIdsAtLevel($int_level = null)
    {
        $arr_tree_id = array();

        foreach(self::$arr_hier as $obj_cat)
        {
            $arr_prov = $obj_cat->getNode();

            if(count($arr_prov) == 0) continue;

            // On stocke par niveau
            $int_rank = count($arr_prov) - 1;

            if(is_integer($int_level) && $int_level < count($arr_prov))
            {
                $int_rank = $int_level;
            }

            $key = implode('/', array_slice($arr_prov, 0, $int_rank + 1));

            if(!isset($arr_tree_id[$key]))
            {
                $arr_tree_id[$key] = $obj_cat->getFileIds();
            }
            else
            {
                $arr_tree_id[$key] = array_merge($arr_tree_id[$key], $obj_cat->getFileIds());
            }
        }

        return $arr_tree_id;
    }

    public static function set($str_path)
    {
        // Si un chemin de catégorie existe, créer et ajouter cette catégorie.
        if($str_path. Path::getDirectorySeparator() == Path::getSrcPost())
        {
            $str_path = Path::getDirectorySeparator();
        }

        $cat = new self($str_path);
        $cat->addToHier();

        return self::$arr_hier[$cat->getSlug()];
    }


    /**
     * Ajoute la catégorie instanciée à la liste des autres catégories.
     *
     * Cette méthode stocke l’instanciation courante dans une liste statique de 
     * la classe Category. 
     * 
     * @access public
     * @return void
     */
    public function addToHier()
    {

        //var_dump($this->getNode());
        //var_dump($this->getSlug());

        if(!isset(self::$arr_hier[$this->getSlug()]))
        {
            self::$arr_hier[$this->getSlug()] = $this;
        }
    }




    /**
     * Ajoute l’ID d’un Post à la liste de l’objet Category. 
     * 
     * @param integer $int_id 
     * @access public
     * @return void
     */
    public function addId($int_id)
    {
        if($int_id > 0)
        {
            $this->arr_ids[] = $int_id;
        }
    }


    /**
     * Donne le nombre d’ID Post stockés pour l’objet Category.
     *
     * @return integer
     */
    public function getCount()
    {
        return count($this->arr_ids);
    }

    /**
     * Retourne le nom de la catégorie.
     *
     * @return string
     */
    public function getName()
    {
        return $this->arr_name[count($this->arr_name) - 1];
    }

    /**
     * Retourne l’arborescence de la catégorie actuelle. 
     * 
     * Donne un tableau retournant l’arborescence de la catégorie, avec en 
     * premier les parents et à la fin les enfants.
     *
     * @access public
     * @return array
     */
    public function getNode()
    {
        return $this->arr_node;
    }

    /**
     * ID des fichiers Post de cette catégorie.
     * 
     * @access public
     * @return array
     */
    public function getFileIds()
    {
        return $this->arr_ids;
    }

    /**
     * Retourne le slug propre à cette catégorie 
     * 
     * @access public
     * @return string
     */
    public function getSlug()
    {
        if(is_null($this->str_slug))
        {
            if(count($this->arr_node))
            {
                $this->str_slug = implode('/', $this->arr_node);
            }
            else
            {
                $this->str_slug = '/';
            }
        }

        return $this->str_slug;
    }

    public function getRootParent()
    {
        if(isset($this->arr_node[0]))
        {
            return new self($this->arr_node[0]);
        }
        else
        {
            return $this;
        }
    }

    
    public function getUrl($full = false)
    {
        $url = new Permalink(Config::getInstance()->getPermalinkCategory());
        $url->setTitle($this->getSlug());

        if($url->isOk())
        {
            return $url->getUrl($full);
        }
        else
        {
            throw new \Exception('Issue occured while building category’s URL!');
        }
    }

}
