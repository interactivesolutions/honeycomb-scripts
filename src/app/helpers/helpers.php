<?php

if (!function_exists ('replaceBrackets')) {
    /**
     * @param $string
     * @param array $data
     * @return mixed
     */
    function replaceBrackets (string $string, array $data = [])
    {
        foreach ($data as $key => $value) {
            $string = str_replace ('{' . $key . '}', $value, $string);
        }

        return $string;
    }
}

if (!function_exists ('validateJSONFromPath')) {

    /**
     * Function which reads and validates json file
     *
     * @param string $path
     * @param bool $response
     * @return bool
     * @throws Exception
     */
    function validateJSONFromPath (string $path, bool $response = false)
    {
        $json = json_decode (file_get_contents ($path), true);

        if (!$json)
            if ($response)
                return null;
            else
                throw new \Exception('Invalid json format - ' . $path);

        return $json;
    }
}