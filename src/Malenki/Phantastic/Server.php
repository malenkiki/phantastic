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
 * Lance un serveur web basique pour tester le site généré.
 *
 * À partir de PHP 5.4, il est possible de lancer un petit serveur dans un
 * répertoire donné. Cette fonctionnalité est exploitée par Phantastic afin 
 * d’avoir cette facilité de développement, qui évite par exemple d’écrire un 
 * virtual host pour Apache, d’arrêter le server, le relancer, etc.
 *
 * S’il n’y a pas PHP 5.4 mais une version inférieure, alors la présence de 
 * Python est testée afin d’utiliser le module `SimpleHTTPServer` de Python 
 * pour avoir la fonctionnalité équivalente. Si Python n’est pas présent, alors 
 * aucun serveur n’est lancé.
 *
 * Par ailleurs, si l’utilisation du serveur est demandée, l’option spécifiant
 * la base de l’URL (hostname) est écrasée pour permettre la navigation sur le
 * site.
 * 
 * @copyright 2012 Michel Petit 
 * @author Michel Petit <petit.michel@gmail.com> 
 */
class Server
{
    const HOST = 'localhost:8080';
    const EXEC_PHP = 'php -S %s -t %s';
    const EXEC_PYTHON = 'cd %s && python -m SimpleHTTPServer %s; cd -';

    protected $str_host = null;

    /**
     * Détermine si l’installation de PHP est capable de lancer un serveur web. 
     * 
     * @static
     * @access public
     * @return boolean
     */
    public static function hasInternalServer()
    {
        return phpversion() >= '5.4.0';
    }


    /**
     * Détermine si Python est installé sur le système.
     *
     * Ce test est nécessaire dans le cas où la version de PHP est inférieure à la 5.4 
     * pour savoir s’il est possible d’utiliser Python pour lancer un serveur 
     * de développement. 
     * 
     * @static
     * @access public
     * @return void
     */
    public static function hasPython()
    {
        $int_ret = null;

        exec('python --version', $arr, $int_ret);

        return $int_ret === 0;
    }

    /**
     * Détermine si le système peut lancer un serveur de développement via PHP ou Python 
     * 
     * @static
     * @access public
     * @return boolean
     */
    public static function canRun()
    {
        return 
            (self::hasInternalServer() || self::hasPython())
            &&
            (Config::getInstance()->serverAvailable());
    }

    /**
     * Initialise le serveur avec une chaîne « hôte:port ».
     * 
     * @param string $str 
     * @access public
     * @return void
     */
    public function setHost($str)
    {
        $this->str_host = $str;
    }

    public function run()
    {
        if(self::hasInternalServer())
        {
            system(sprintf(self::EXEC_PHP, $this->str_host, Config::getInstance()->getDir()->dest));
        }
        else
        {
            $str_port = array_pop(explode(':', $this->str_host));
            system(sprintf(self::EXEC_PYTHON, Config::getInstance()->getDir()->dest, $str_port));
        }
    }
}
