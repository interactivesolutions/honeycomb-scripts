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

if (!function_exists ('replaceTextInFile')) {

    /**
     * Function which reads and validates json file
     *
     * @param string $path
     * @param array $content
     * @throws Exception
     */
    function replaceTextInFile (string $path, array $content)
    {
        $file = file_get_contents ($path);

        foreach ($content as $replace => $subject)
            $file = str_replace ($replace, $subject, $file);

        file_put_contents ($path, $file);
    }
}

if (!function_exists ('http_validate')) {
    /**
     * Validates given url
     *
     * @param string $url
     * @param bool $secure
     * @return string
     */
    function http_validate (string $url, bool $secure = false) : string
    {
        $return   = $url;
        $protocol = 'http://';

        if ($secure)
            $protocol = 'https://';

        if ((!(substr ($url, 0, 7) == 'http://')) && (!(substr ($url, 0, 8) == 'https://')))
            $return = $protocol . $url;

        return $return;
    }
}

if (!function_exists ('random_str')) {
    /**
     * Origin taken from http://stackoverflow.com/a/31107425/657451
     *
     * Generate a random string, using a cryptographically secure
     * pseudorandom number generator (random_int)
     *
     * @param int $length How many characters do we want?
     * @param string $keySpace A string of all possible characters
     *                         to select from
     * @return string
     */
    function random_str ($length, $keySpace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ')
    {
        $keySpace = str_shuffle ($keySpace);

        $str = '';
        $max = mb_strlen ($keySpace, '8bit') - 1;
        for ($i = 0; $i < $length; ++$i) {
            $str .= $keySpace[random_int (0, $max)];
        }

        return $str;
    }
}

if (!function_exists ('addEnvVariable')) {
    /**
     * Adding environmental variable to .env file
     *
     * @param string $key
     * @param string $value
     * @return bool
     */
    function addEnvVariable (string $key, string $value)
    {
        $envPath     = '.env';
        $fileContent = file_get_contents ($envPath);

        if (strpos ($fileContent, $key) === false)
            file_put_contents ($envPath, $fileContent . "\r\n" . $key . '=' . $value);
        else
            file_put_contents ($envPath, preg_replace (___keyReplacementPattern ($key), "$key={$value}", $fileContent));

        return true;
    }

    /**
     * Get a regex pattern that will match env KEY with any random key.
     *
     * @param string $key
     * @return string
     */
    function ___keyReplacementPattern (string $key)
    {
        $escaped = preg_quote (env ($key), '/');

        return "/^$key={$escaped}/m";
    }
}