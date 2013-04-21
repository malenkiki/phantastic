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

class Template 
{
    const TAG_PAGE = 'tag-page';
    const TAG_INDEX = 'tag-index';

    const CATEGORY_PAGE = 'category-page';
    
    const TAGS = 'tags';
    const CATEGORIES = 'categories';
    const ROOT_CATEGORIES = 'root-categories';

    protected $arr_data = array();
    protected $str_tmpl = null;

    public function __construct($tmpl)
    {
        $this->str_tmpl = $tmpl;
    }

    public function setContent($str)
    {
        $this->assign('content', $str);
    }

    public function assign($key, $value)
    {
        $this->arr_data[$key] = $value;
    }

    protected function partial($str)
    {
        $data = (object) $this->arr_data;
        require(
            sprintf(
                '%s%s.phtml',
                Config::getInstance()->getDir()->template,
                $str
            )
        );
    }


    public function hasText($str_key)
    {
        return Sample::has($str_key);
    }

    public function text($str_key)
    {
        // TODO: Je ne lève pas d’erreur, mais dans le futur, un message de log va apparaitre.
        if(Sample::has($str_key))
        {
            return Sample::get($str_key)->getContent();
        }
        else
        {
            return '';
        }
    }

    public function truncate($str, $int_length, $str_end = '…')
    {
        if(mb_strlen($str, 'UTF-8') > $int_length)
        {
            $str = strip_tags($str);
            // TODO: Convertir les entités HTML en caractère brut…
            $int_length = $int_length - mb_strlen($str_end, 'UTF-8');
            return mb_substr($str, 0, $int_length, 'UTF-8') . $str_end;
        }
        else
        {
            return $str;
        }
    }

    public function render()
    {
        $data = (object) $this->arr_data;

        $str_file = sprintf(
            '%s%s.phtml',
            Config::getInstance()->getDir()->template,
            $this->str_tmpl
        );

        if(!file_exists($str_file))
        {
            throw new \Exception(sprintf('File %s does not exist! Rendering abort.', $str_file));
        }

        ob_start();
        require($str_file);
        $content = ob_get_contents();
        ob_end_clean();

        return $content;
    }
}
