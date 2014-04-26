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

/**
 * Classe relative à la création d’URL ou de chemin dans un FS.
 *
 * @copyright 2012 Michel Petit
 * @author Michel Petit <petit.michel@gmail.com>
 * @todo Il faudra prévoir un moyen pour manipuler les chemins sous windows…
 */
class Path
{
    const SRC = 'source/'; // chemin par défaut des sources du site
    const DEST = 'out/'; // chemin par défaut des fichiers générés
    const POST = 'post/'; // chemin relatif à SRC pour les Posts
    const SAMPLE = 'sample/'; // chemin relatif à SRC pour les blocs de texte
    const TEMPLATE = 'template/'; // chemin par défaut pour les templates
    const TAGS = 'tags/'; // chemin de destination par défaut des tags
    const CATEGORIES = 'categories/'; // chemin de destination par défaut des catégories

    protected static $str_app_root = null;

    public static function setAppRoot($str)
    {
        self::$str_app_root = $str;
    }

    public static function getAppRoot()
    {
        return self::cleanPath(self::$str_app_root . self::getDirectorySeparator());
    }

    public static function getLib($str_lib_path = null)
    {
        $str_out = self::cleanPath(
            self::getAppRoot() . 'vendor' . self::getDirectorySeparator()
        );

        if (is_string($str_lib_path) && strlen($str_lib_path)) {
            $str_out = self::cleanPath($str_out . $str_lib_path);
        }

        return $str_out;
    }

    /**
     * Débarasse le chemin des séparateurs de répertoire doublons.
     *
     * @param  string $str_path
     * @static
     * @access public
     * @return string
     * @todo Renommer en clean() car redondant avec le nom de la classe.
     */
    public static function cleanPath($str_path)
    {
        return preg_replace(
            sprintf('@%s+@', self::getDirectorySeparator()),
            self::getDirectorySeparator(),
            $str_path
        );
    }

    /**
     * Ajoute un « index.html » si le chemin se termine par un simple dossier.
     *
     * Si le chemin se termine déjà par un fichier, retourne juste le chemin sans changement.
     * Cette méthode est utile dans le cas des chemins des Posts ou des Pages,
     * quand l’URL voulu n’a pas d’extension.
     *
     * @param  string $str_path
     * @static
     * @access public
     * @return string
     */
    public static function createIndex($str_path)
    {
        if (!preg_match(sprintf('@%s[a-z\.\-]+\.[a-z]+$@', self::getDirectorySeparator()), $str_path)) {
            $str_path = $str_path . self::getDirectorySeparator() . 'index.html';
        }

        return self::cleanPath($str_path);
    }

    /**
     * Retourne le séparateur de répertoire en fonction du système.
     *
     * Sur Microsoft Windows, retourne '\', sur UNIX retourne '/'.
     *
     * @static
     * @access public
     * @return string
     */
    public static function getDirectorySeparator()
    {
        return DIRECTORY_SEPARATOR;
    }

    /**
     * Retourne le chemin menant à la source des Posts.
     *
     * @static
     * @access public
     * @return string
     */
    public static function getSrcPost()
    {
        //TODO: sera à revoir
        return sprintf(
            '%s%s',
            Config::getInstance()->getDir()->src,
            Config::getInstance()->getDir()->post
        );
    }

    public static function getSrcSample()
    {
        //TODO: sera à revoir
        return sprintf(
            '%s%s',
            Config::getInstance()->getDir()->src,
            Config::getInstance()->getDir()->sample
        );
    }

    /**
     * Obtient le chemin des sources.
     *
     * @static
     * @access public
     * @return string
     */
    public static function getSrc()
    {
        return Config::getInstance()->getDir()->src;
    }

    /**
     * Obtient le chemin du répertoire de génération du site.
     *
     * @static
     * @access public
     * @return string
     */
    public static function getDest()
    {
        return Config::getInstance()->getDir()->dest;
    }

    /**
     * Obtient le chemin stockant les templates.
     *
     * @static
     * @access public
     * @return string
     */
    public static function getTemplate()
    {
        return Config::getInstance()->getDir()->template;
    }



    /**
     * Crée un chemin selon le type d’objet passé en argument.
     *
     * Selon que le type d’objet est un Tag, un File de type Post, un File de
     * type Page ou un File de type autre, cette méthode crée le chemin, tant
     * au niveau de la chaîne de caractères, qu’au niveau du système de fichier
     * en créant le ou les dossiers nécessaires.
     *
     * @param  mixed  $obj un Objet Tag ou File
     * @static
     * @access public
     * @return string
     *
     * @todo Peut-être que les objets de type Category pourront être utilisé.
     */
    public static function build($obj)
    {
        $str_out = self::cleanPath(self::getDest() . $obj->getUrl());

        if ($obj instanceof Tag) {
            $str_out = self::createIndex($str_out);

        } elseif ($obj instanceof File) {
            if (!$obj->isFile()) {
                $str_out = self::createIndex($str_out);
            }
        } elseif ($obj instanceof Category) {
            // La catégorie elle-même, création de son chemin pour l’index
            $str_out = self::createIndex($str_out);
        }

        if (!file_exists(dirname($str_out))) {
            mkdir(dirname($str_out), 0755, true);
        }

        return $str_out;
    }


    public static function buildForEmptyCategory($str)
    {
        //$str_out = self::cleanPath(self::getDest() . $obj->getUrl());
        $str_out = $str;
        $str_out = self::createIndex($str_out);

        return $str_out;
    }

    public static function buildForRootTag()
    {
        $str_out = self::createIndex(self::getDestTag());

        return $str_out;
    }

    public static function getDestCategory()
    {
        $l = new Permalink(Config::getInstance()->getPermalinkCategory());
        $l->setTitle('');

        return self::cleanPath(self::getDest() . preg_replace('/\.[hH][tT][mM][lL]$/', '', $l->getUrl()));
    }

    public static function getDestTag()
    {
        $l = new Permalink(Config::getInstance()->getPermalinkTag());
        $l->setTitle('');

        return self::cleanPath(self::getDest() . preg_replace('/\.[hH][tT][mM][lL]$/', '', $l->getUrl()));
    }
}
