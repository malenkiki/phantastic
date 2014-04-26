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
use Exception;

class TfIdf
{
    protected static $arr_stop_words = array();
    protected static $arr_documents = array();
    protected static $arr_doc_dist = array();

    protected $arr_tf = array();
    protected $arr_tfidf = array();

    public static function loadStopWordsFile($str_file_stop_words)
    {
        $str_stop_words = file_get_contents($str_file_stop_words);

        foreach (explode("\n", $str_stop_words) as $str_word) {
            $str_word = trim($str_word);

            if (strlen($str_word)) {
                self::$arr_stop_words[] = $str_word;
            }
        }
    }

    public static function addDistanceFor($int_id_1, $int_id_2, $float_dist)
    {
        if ($int_id_1 != $int_id_2) {
            if (isset(self::$arr_doc_dist[$int_id_1])) {
                self::$arr_doc_dist[$int_id_1][$int_id_2] = $float_dist;
            } else {
                self::$arr_doc_dist[$int_id_1] = array();
            }
        }
    }

    public static function getDistanceFor($int_id_a, $int_id_b)
    {
        if ($int_id_a == $int_id_b) {
            return 0;
        }

        if ($int_id_a < $int_id_b) {
            $int_id_1 = $int_id_a;
            $int_id_2 = $int_id_b;
        } else {
            $int_id_2 = $int_id_a;
            $int_id_2 = $int_id_b;
        }

        return self::$arr_doc_dist[$int_id_1][$int_id_2];
    }

    public static function getNearestIdsFor($int_id, $int_count)
    {
        asort(self::$arr_doc_dist[$int_id]);

        return array_slice(array_keys(self::$arr_doc_dist[$int_id]), 0, $int_count);
    }

    public static function getCount()
    {
        return count(self::$arr_documents);
    }

    public static function isEmpty()
    {
        return self::getCount() == 0;
    }

    public static function set($int_id, $str_text)
    {
        $tfIdf = new self($str_text);
        $tfIdf->addToDocumentList($int_id);

        return self::$arr_documents[$int_id];
    }

    public static function get($int_id)
    {
        return self::$arr_documents[$int_id];
    }

    public static function idf($str_term)
    {
        $int_count = 0;

        if (self::isEmpty()) {
            throw new Exception('Can not calculate Inverse Document Frequency because there are not document.');
        }

        foreach (self::$arr_documents as $obj) {
            if ($obj->hasTerm($str_term)) {
                $int_count++;
            }
        }

        return log($int_count / self::getCount());
    }

    public static function distance($int_id1, $int_id2)
    {
        $arr_1 = self::get($int_id1)->getTermsAndFrequencies();
        $arr_2 = self::get($int_id2)->getTermsAndFrequencies();

        $float_missing_value = 0.0001;
        $float_dist = 0;

        $arr_tokens = array_keys(array_merge($arr_1, $arr_2));

        foreach ($arr_tokens as $str_token) {
            if (!isset($arr_1[$str_token])) {
                $arr_1[$str_token] = $float_missing_value;
            }

            if (!isset($arr_2[$str_token])) {
                $arr_2[$str_token] = $float_missing_value;
            }

            $float_dist += pow(($arr_1[$str_token] - $arr_2[$str_token]), 2);
        }

        return $float_dist;
    }

    protected static function prepare($str_text)
    {
        $str_text = strip_tags($str_text);

        if (phpversion() < '5.4.0') {
            $str_text = html_entity_decode($str_text, ENT_QUOTES, 'UTF-8');
        } else {
            $str_text = html_entity_decode($str_text, ENT_QUOTES | ENT_XHTML, 'UTF-8');
        }

        $str_text = mb_strtolower($str_text, 'UTF-8');

        $str_text = trim(preg_replace('/[\s\p{P}?!¡¿;:’\*]+/iu', ' ', $str_text));

        return(explode(' ', $str_text));
    }

    public function __construct($str_text)
    {
        $arr_tokens = self::prepare($str_text);

        $int_count_tokens = count($arr_tokens);

        // on remplit le tableau Term Frequency
        foreach ($arr_tokens as $str_token) {
            if (!in_array($str_token, self::$arr_stop_words)) {
                if (isset($this->arr_tf[$str_token])) {
                    $this->arr_tf[$str_token]++;
                } else {
                    $this->arr_tf[$str_token] = 1;
                }
            }
        }

        // maintenant, calculons la fréquence pour chaque terme.
        foreach ($this->arr_tf as $k => $v) {
            $this->arr_tf[$k] = $v / $int_count_tokens;
        }

        unset($str_text);
        unset($arr_tokens);
    }

    public function hasTerm($str_term)
    {
        return array_key_exists($str_term, $this->arr_tf);
    }

    public function getTermsAndFrequencies()
    {
        if (count($this->arr_tfidf) == 0) {
            foreach ($this->arr_tf as $str_term => $float_tf) {
                $this->arr_tfidf[$str_term] = $this->calculate($str_term);
            }
        }

        return $this->arr_tfidf;
    }

    public function tf($str_term)
    {
        return $this->arr_tf[$str_term];
    }

    public function calculate($str_term)
    {
        return abs($this->tf($str_term) * self::idf($str_term));
    }

    public function addToDocumentList($int_id)
    {
        self::$arr_documents[$int_id] = $this;
    }
}
