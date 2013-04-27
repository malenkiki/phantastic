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

class Parser
{

    protected $arr_header = null;
    protected $str_content = null;

    /**
     * Interprète un contenu YAML sous forme de chaîne de caractères ou de 
     * fichier.
     * 
     * Si $is_file est à TRUE, alors l’argument $str sera considéré comme étant 
     * le chemin vers le fichier à interpréter.
     *
     * Si $is_file n’est pas renseigné, alors la chaîne de caractères fournie 
     * dans $str sera interprétée.
     *
     * @param string $str 
     * @param boolean $is_file 
     * @static
     * @access public
     * @return array
     */
    public static function parseYaml($str, $is_file = false)
    {
        //TODO: Tester si la lib est bien installée, sinon fatal error.
        //TODO: Inclure tous les fichiers de la lib
        include_once(Path::getLib('yaml/Parser.php'));
        include_once(Path::getLib('yaml/Inline.php'));
        include_once(Path::getLib('yaml/Unescaper.php'));
        $yaml = new \Symfony\Component\Yaml\Parser();

        if($is_file)
        {
            return $yaml->parse(file_get_contents($str));
        }
        else
        {
            return $yaml->parse($str);
        }
    }
    

    /**
     * Constructeur prenant un nom de fichier en argument pour le parcourir
     * 
     * @param string $str 
     * @access public
     */
    public function __construct($str)
    {
        $test = file_get_contents($str);
        $arr_lines = explode("\n", $test);
        unset($test);
        $int_length_header = 0;
        $int_cnt = count($arr_lines);

        if(preg_match('/^--- *$/', $arr_lines[0]))
        {

            for($i = 1; $i < $int_cnt; $i++)
            {
                if(preg_match('/^--- *$/', $arr_lines[$i]))
                {
                    $int_length_header = $i - 1;
                    break;
                }
            }
        }

        if($int_length_header > 0)
        {
            $str_yaml = implode("\n", array_slice($arr_lines, 1, $int_length_header));
            $str_content = implode("\n", array_slice($arr_lines, $int_length_header + 2));
            $this->arr_header = self::parseYaml($str_yaml);
            $this->str_content = Markdown($str_content);
        }
    }

    /**
     * Détermine si le document contient à la fois header et contenu. 
     * 
     * @access public
     * @return boolean
     */
    public function hasContents()
    {
        return $this->arr_header && $this->str_content;
    }




    /**
     * Teste la présence d’un header YAML pour le fichier interprété. 
     * 
     * @access public
     * @return boolean
     */
    public function hasHeader()
    {
        return !is_null($this->arr_header);
    }



    /**
     * Teste la présence d’un contenu pour le fichier interprété.
     *
     * Au sens de contenu testé, il faut le comprendre comme étant un contenu 
     * pouvant être inclu dans la création de page par le programme.
     * 
     * @access public
     * @return boolean
     */
    public function hasContent()
    {
        return !is_null($this->str_content);
    }



    /**
     * Inerprète le contenu YAML du header d’un fichier 
     * 
     * @access public
     * @return void
     */
    public function getHeader()
    {
        return $this->arr_header;
    }



    /**
     * Interprète une chaîne de caractère Markdown pour créer un équivalant HTML. 
     * 
     * @access public
     * @return string
     */
    public function getContent()
    {
        return $this->str_content;
    }
}
