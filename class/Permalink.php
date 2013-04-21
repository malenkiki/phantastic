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
 * Permet de construire des URL à partir d’un motif et de clés/valeurs fournies.
 *
 * À différents niveaux, il est possible de spécifier des « permalinks » pour 
 * les Posts, les Pages… Ces « permalinks » peuvent être définis dans l’en-tête 
 * YAML ou le fichier de configuration.
 *
 * Un permalink est un motif de l’URL à obtenir. Les parties à remplacer par 
 * des valeurs commencent par un deux point et sont composées de caractères 
 * alphabétiques minuscules.
 *
 * Ces mots clés sont les suivants : `:categories`, `:year`, `:month, `:day`
 * et `:title`.
 * 
 * @copyright 2012 Michel Petit
 * @author Michel Petit <petit.michel@gmail.com> 
 */
class Permalink
{
    const BASE  = '/';
    const TAG  = '/tags/:title.html';
    const CATEGORY  = '/categories/:title.html';
    const POST = '/:categories/:year/:month/:day/:title.html';

    const PLACEHOLDER_CATEGORIES = ':categories';
    const PLACEHOLDER_YEAR       = ':year';
    const PLACEHOLDER_MONTH      = ':month';
    const PLACEHOLDER_DAY        = ':day';
    const PLACEHOLDER_TITLE      = ':title';

    protected static $arr_placeholders = array(
        self::PLACEHOLDER_CATEGORIES,
        self::PLACEHOLDER_YEAR,
        self::PLACEHOLDER_MONTH,
        self::PLACEHOLDER_DAY,
        self::PLACEHOLDER_TITLE
    );

    protected $str_permalink = null;
    protected $str_url = null;
    protected $int_count = 0;
    protected $arr_keys_values = array();


    /**
     * Détermine si la chaîne du placeholder passée en paramètre est correcte. 
     * 
     * Il y a un nombre limité de placeholders supportés. Cette méthode 
     * détermine si un placeholder donné existe bel et bien.
     *
     * @param string $str 
     * @static
     * @access protected
     * @return boolean
     */
    protected static function checkPlaceholderName($str)
    {
        return in_array($str, self::$arr_placeholders);
    }

    /**
     * Enlève les slashes surnuméraires résultant parfois de la construction de l’URL. 
     * 
     * @param string $url 
     * @static
     * @access public
     * @return string
     */
    public static function cleanUrl($url)
    {
        $url = '/' . $url;
        return preg_replace('@/+@', '/', $url);
    }
    
    
    /**
     * Crée des morceaux d’URL avec uniquement des caractères ASCII et des 
     * tirets d’hyphénation.
     *
     * Cette méthode statique prend en argument une chaîne de caractères 
     * qu’elle traite de manière à en convertir les caractères portant des 
     * diacritiques ou des caractères composés en caractères équivalents ASCII 
     * minuscules.
     *
     * Ainsi, par exemple, la chaîne suivante : « BŒUF » donnera « boeuf » et 
     * celle-ci : « théâtre » donnera « theatre ».
     *
     * Ensuite, tout ce qui n’est pas un caractère alphanumérique ASCII et tout 
     * ce qui n’est pas un tiret est éliminé, les tirets prennent la place de 
     * caractères spéciaux, comme les espaces, les ponctuations… Et les 
     * doublons tirets sont enlevés pour n’en laisser qu’un. La chaîne obtenue 
     * ne doit ni commencer, ni finir par un tiret.
     *
     * Ainsi, la phrase « Mais ?! Où est donc Ornicar ? » donnera 
     * « mais-ou-est-donc-ornicar ».
     *
     * Pour le moment, les langues d’Europe Occidentales sont supportées, mais 
     * bientôt quelques langues non basées sur un alphabet latin seront 
     * supportées. Ainsi le grec et le russe feront leur apparition avec un 
     * système de translitération. Soyez donc patient ;)
     * 
     * @param string $str 
     * @static
     * @access public
     * @return string
     * @todo Supporter d’autres langues à alphabet latin dérivé comme le Turc, 
     * des langues d’Europe de l’Est, le Serbo-Croate, le Polonais, le Roumain, 
     * etc.
     * @todo Supporter l’esperanto, en utilisant la notation « x ».
     * @todo Faire un premier support des langues n’utilisant pas un alphabet 
     * latin. S’occuper alors en priorité du grec, du russe, du bulgare, de 
     * l’ukrainien.
     * @todo En priorité basse, voir pour le Coréen (langue à syllabe), voir 
     * s’il est possible d’obtenir un truc sympa sans trop faire compliqué.
     */
    public static function createSlug($str)
    {
        // to lower case
        $str = mb_strtolower($str, 'UTF-8');

        // Remove diacritics
        $arr_prov = array(
            "é" => "e", "è" => "e", "ê" => "e", "ë" => "e", "ę" => "e", "ẽ" => 
            "e", 'ě' => 'e', "á" => "a", "à" => "a", "â" => "a", "ä" => "a", 
            "ą" => "a", "ã" => "a", "å" => "a", 'ǎ' => 'a', 'ã' => 'a', "ó" => 
            "o", "ò" => "o", "ô" => "o", "ö" => "o", "õ" => "o", 'ǒ' => 'o', 
            'ø' => 'o', 'õ' => 'o', "í" => "i", "ì" => "i", "î" => "i", "ï" => 
            "i", "ĩ" => "i", 'ǐ' => 'i', "ú" => "u", "ù" => "u", "û" => "u", 
            "ü" => "u", "ũ" => "u", "ů" => "u", 'ǔ' => 'u', "ý" => "y", "ỳ" => 
            "y", "ŷ" => "y", "ÿ" => "y", "ỹ" => "y", "ç" => "c", "ñ" => "n", 
            'ł' => 'l', 'ð' => 'dh', 'þ' => 'th', "œ" => "oe", "æ" => "ae", "ß" 
            => "ss", 'ŀl' => 'll',
                 
        );
       
        foreach($arr_prov as $k => $v)
        {
            $str = preg_replace(sprintf('/%s/', $k), $v, $str);
        }

        $str = preg_replace('/[^a-z0-9]+/', '-', trim($str));
        $str = trim($str, '-');

        return $str;	
    }


    /**
     * Lit le permalink pour en déterminer les placeholders éventuels. 
     * 
     * @param string $str  Le permalink
     * @access protected
     * @return void
     */
    protected function parse($str)
    {
        $this->str_permalink = $str;

        $arr_prov = array();

        $this->int_count = preg_match_all('/:[a-z]+/', $str, $arr_prov);

        if($this->int_count)
        {
            //OK, il y a des placeholders
            //Initialisation du tableau qui stockera les clés/valeurs
            $arr = array_pop($arr_prov);

            foreach($arr as $p)
            {
                if(self::checkPlaceholderName($p))
                {
                    $this->arr_keys_values[$p] = null;
                }
                else
                {
                    //Si placeholder inconnu, faux, on lève une exception
                    throw new \Exception(sprintf('Bad placeholder %s!', $p));
                }
            }
        }
        else
        {
            //Pas de placeholder, il s’agit donc d’une URL sans variables.
            $this->str_url = $str;
        }
    }

    public function __construct($str)
    {
        $this->parse($str);
    }

    public function setCategories(Category $cat)
    {
        $this->arr_keys_values[self::PLACEHOLDER_CATEGORIES] = $cat->getSlug();
    }

    public function setYear($year)
    {
        $this->arr_keys_values[self::PLACEHOLDER_YEAR] = $year;
    }

    public function setMonth($month)
    {
        $this->arr_keys_values[self::PLACEHOLDER_MONTH] = $month;
    }

    public function setDay($day)
    {
        $this->arr_keys_values[self::PLACEHOLDER_DAY] = $day;
    }


    //TODO: Voir comment faire ça… Et si je le fais d’ailleurs…
    public function setDate()
    {
    }

    public function setTitle($title)
    {
        $this->arr_keys_values[self::PLACEHOLDER_TITLE] = $title;
    }

    /**
     * Contrôle si toutes les paires clés/valeurs sont formées. 
     * 
     * @access public
     * @return boolean
     */
    public function isOk()
    {
        foreach($this->arr_keys_values as $v)
        {
            if(is_null($v))
            {
                return false;
            }
        }

        return true;
    }


    /**
     * Récupère l’URL créée après l’avoir éventuellement construite.
     *
     * @access public
     * @param boolean $full
     * @return string
     * @todo prévoir le cas du paramètre full
     */
    public function getUrl($full = false)
    {
        if(is_null($this->str_url))
        {
            $str_out = $this->str_permalink;

            foreach($this->arr_keys_values as $k => $v)
            {
                $str_out = preg_replace(sprintf('/%s/', $k), $v, $str_out);
            }

            $this->str_url = $str_out;
        }

        return Permalink::cleanUrl($this->str_url);
    }



    /**
     * Retourne l’URL construite. 
     * 
     * @access public
     * @return string
     */
    public function __toString()
    {
        return $this->getUrl();
    }
}
