<?php
/**
 * @brief kUtRL, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugin
 *
 * @author Jean-Christian Denis and contributors
 *
 * @copyright Jean-Christian Denis
 * @copyright GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
declare(strict_types=1);

namespace Dotclear\Plugin\kUtRL;

class FrontendUtils
{
    public static $chars = 'abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    public static function create($len = 6)
    {
        $res   = '';
        $chars = self::$chars;
        for ($i = 0; $i < $len; $i++) {
            $res .= $chars[rand(0, strlen($chars) - 1)];
        }

        return $res;
    }

    public static function protect($str)
    {
        $res   = '';
        $chars = self::$chars;
        for ($i = 0; $i < strlen($str); $i++) {
            $res .= $chars[rand(0, strlen($chars) - 1)] . $str[$i];
        }

        return $res;
    }

    public static function unprotect($str)
    {
        $res = '';
        for ($i = 0; $i < strlen($str); $i++) {
            $i++;
            $res .= $str[$i];
        }

        return $res;
    }
}
