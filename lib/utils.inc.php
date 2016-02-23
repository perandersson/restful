<?php

class Utils
{
    /**
     * Retrieves the value resolved in the first parameter. If the supplied value is not resolved properly then
     * return the default value instead.
     */
    static function getOrElse(&$var, $default = null)
    {
        return isset($var) ? $var : $default;
    }

    /**
     * Splits the value resolved in the first parameter. If the supplied value resolves as null or empty
     * then return an empty array.
     */
    static function trimSplit(&$var, $charlist)
    {
        $result = trim(Utils::getOrElse($var, ""), $charlist);
        if ($result == "")
            return array();
        else
            return explode("/", $result);
    }

    static function safeJsonDecode($json)
    {
        if ($json == null || $json == "")
            $json = "{}";

        return json_decode($json);
    }

    static function safeJsonEncode($object)
    {
        if ($object == null)
            return null;
        return json_encode($object);
    }
}