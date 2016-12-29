<?php

if (!function_exists('replaceBrackets'))
{
    /**
     * @param $string
     * @param array $data
     * @return mixed
     */
    function replaceBrackets($string, $data = [])
    {
        foreach ($data as $key => $value)
        {
            $string = str_replace('{' . $key . '}', $value, $string);
        }

        return $string;
    }
}