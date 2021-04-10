<?php

namespace Flytrap\Security;

class NumberAlphaIdConverter
{
    protected $pad_up;
    protected $pass_key;
    protected $index = 'abcdefghijklmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    protected $base;

    function __construct($pad_up = false, $pass_key = null)
    {
        $this->pad_up = $pad_up;
        $this->pass_key = $pass_key;
        $this->base = strlen($this->index);
    }

    public function convertAlphaIdToNumericId($in)
    {
        $padUpPos = $this->pad_up;
        $out = '';

        $len = strlen($in) - 1;

        for ($t = $len; $t >= 0; $t--) {
            $bcp = bcpow($this->base, $len - $t);
            $out = $out + strpos($this->index, substr($in, $t, 1)) * $bcp;
        }

        if (is_numeric($this->pad_up)) {
            $padUpPos--;

            if ($padUpPos > 0) {
                $out -= pow($this->base, $padUpPos);
            }
        }

        return $out;
    }

    /**
     * Translates a number to a short alphanumeric version
     *
     * Translated any number up to 9007199254740992
     * to a shorter version in letters e.g.:
     * 9007199254740989 --> PpQXn7COf
     *
     * this function is based on any2dec && dec2any by
     * fragmer[at]mail[dot]ru
     * see: https://nl3.php.net/manual/en/function.base-convert.php#52450
     *
     * If you want the alphaID to be at least 3 letter long, use the
     * $pad_up = 3 argument
     *
     * In most cases this is better than totally random ID generators
     * because this can easily avoid duplicate ID's.
     * For example if you correlate the alpha ID to an auto incrementing ID
     * in your database, you're done.
     *
     * The reverse is done because it makes it slightly more cryptic,
     * but it also makes it easier to spread lots of IDs in different
     * directories on your filesystem. Example:
     * $part1 = substr($alpha_id,0,1);
     * $part2 = substr($alpha_id,1,1);
     * $part3 = substr($alpha_id,2,strlen($alpha_id));
     * $destindir = "/".$part1."/".$part2."/".$part3;
     * // by reversing, directories are more evenly spread out. The
     * // first 26 directories already occupy 26 main levels
     *
     * more info on limitation:
     * - https://blade.nagaokaut.ac.jp/cgi-bin/scat.rb/ruby/ruby-talk/165372
     *
     * if you really need this for bigger numbers you probably have to look
     * at things like: https://theserverpages.com/php/manual/en/ref.bc.php
     * or: https://theserverpages.com/php/manual/en/ref.gmp.php
     * but I haven't really dugg into this. If you have more info on those
     * matters feel free to leave a comment.
     *
     * The following code block can be utilized by PEAR's Testing_DocTest
     * <code>
     * // Input //
     * $number_in = 2188847690240;
     * $alpha_in  = "SpQXn7Cb";
     *
     * // Execute //
     * $alpha_out  = alphaID($number_in, false, 8);
     * $number_out = alphaID($alpha_in, true, 8);
     *
     * if ($number_in != $number_out) {
     *   echo "Conversion failure, ".$alpha_in." returns ".$number_out." instead of the ";
     *   echo "desired: ".$number_in."\n";
     * }
     * if ($alpha_in != $alpha_out) {
     *   echo "Conversion failure, ".$number_in." returns ".$alpha_out." instead of the ";
     *   echo "desired: ".$alpha_in."\n";
     * }
     *
     * // Show //
     * echo $number_out." => ".$alpha_out."\n";
     * echo $alpha_in." => ".$number_out."\n";
     * echo alphaID(238328, false)." => ".alphaID(alphaID(238328, false), true)."\n";
     *
     * // expects:
     * // 2188847690240 => SpQXn7Cb
     * // SpQXn7Cb => 2188847690240
     * // aaab => 238328
     *
     * </code>
     * 
     * Adapted from a function by Kevin Van Zonneveld on Stack Overflow
     * 
     * @author  Kevin van Zonneveld <kevin@vanzonneveld.net>
     * @author  Simon Franz
     * @author  Deadfish
     * @author  SK83RJOSH
     * @copyright 2008 Kevin van Zonneveld (https://kevin.vanzonneveld.net)
     * @license   https://www.opensource.org/licenses/bsd-license.php New BSD Licence
     * @version   SVN: Release: $Id: alphaID.inc.php 344 2009-06-10 17:43:59Z kevin $
     * @link    https://kevin.vanzonneveld.net/
     *
     * @param mixed   $in   String or long input to translate
     *
     * @return mixed string or long
    */
    public function convertNumericIdToAlphaId($in) : string
    {
        $out = '';

        if (is_numeric($this->pad_up)) {
            $this->pad_up--;

            if ($this->pad_up > 0) {
                $in += pow($this->base, $this->pad_up);
            }
        }

        for ($t = ($in != 0 ? floor(log($in, $this->base)) : 0); $t >= 0; $t--) {
            $bcp = bcpow($this->base, $t);
            $a   = floor($in / $bcp) % $this->base;
            $out = $out . substr($this->index, $a, 1);
            $in  = $in - ($a * $bcp);
        }

        return $out;
    }
}
