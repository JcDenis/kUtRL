<?php

declare(strict_types=1);

namespace Dotclear\Plugin\kUtRL;

/**
 * @brief       kUtRL frontend utils.
 * @ingroup     kUtRL
 *
 * @author      Jean-Christian Denis (author)
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class FrontendUtils
{
    public static string $chars = 'abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    public static function create(int $len = 6): string
    {
        $res   = '';
        $chars = self::$chars;
        for ($i = 0; $i < $len; $i++) {
            $res .= $chars[rand(0, strlen($chars) - 1)];
        }

        return $res;
    }

    public static function protect(string $str): string
    {
        $res   = '';
        $chars = self::$chars;
        for ($i = 0; $i < strlen($str); $i++) {
            $res .= $chars[rand(0, strlen($chars) - 1)] . $str[$i];
        }

        return $res;
    }

    public static function unprotect(string $str): string
    {
        $res = '';
        for ($i = 0; $i < strlen($str); $i++) {
            $i++;
            $res .= $str[$i];
        }

        return $res;
    }
}
