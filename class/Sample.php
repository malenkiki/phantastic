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
 * Petit bloc de texte pouvant être appelé n’importe où dans un template. 
 * 
 * @copyright 2012 Michel Petit
 * @author Michel Petit <petit.michel@gmail.com> 
 */
class Sample
{
    protected static $arr_samples = array();
    protected $str_name;
    protected $str_content;


    public function __construct($str_name)
    {
        // TODO: Ajouter une vérification sur le nom…
        $this->str_name = $str_name;
        $this->render();

    }

    public static function has($str_name)
    {
        return array_key_exists($str_name, self::$arr_samples);
    }


    public static function isEmpty()
    {
        return count(self::$arr_samples) == 0;
    }

    public static function set($str_name)
    {
        $sample = new self($str_name);
        $sample->addToCol();
        return self::$arr_samples[$sample->getName()];
    }


    public function addToCol()
    {
        if(!isset(self::$arr_samples[$this->getName()]))
        {
            self::$arr_samples[$this->getName()] = $this;
        }
    }

    protected function render()
    {
        $str_text = file_get_contents(
            sprintf(
                '%s%s.markdown',
                Path::getSrcSample(),
                $this->str_name
            )
        );

        $this->str_content = Markdown($str_text);
    }

    
    public static function get($str_name)
    {
        return self::$arr_samples[$str_name];
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
    public function getContent()
    {
        return $this->str_content;
    }


}

