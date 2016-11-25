<?php

namespace Zewa;

class Security
{
    /**
     * Normalizes data
     *
     * @access public
     * @TODO:  expand functionality, set/perform based on configuration
     */
    //@TODO clean up

    public function normalize($data)
    {
        if(!isset($data)) {
            return null;
        }

        if (is_array($data)) {
            foreach ($data as $key => $value) {
                unset($data[$key]);
                $data[$this->normalize($key)] = $this->normalize($value);
            }
        } elseif (is_object($data)) {
            $new = new \stdClass();
            foreach ($data as $k => $v) {
                $key = $this->normalize($k);
                $new->{$key} = $this->normalize($v);
            }
            $data = $new;
        } else {
            $data = trim($data);
            //we need to review this.
            if (function_exists('iconv') && function_exists('mb_detect_encoding')) {
                $current_encoding = mb_detect_encoding($data);

                if ($current_encoding != 'UTF-8' && $current_encoding != 'UTF-16') {
                    $data = iconv($current_encoding, 'UTF-8', $data);
                }
            }

            if (is_numeric($data)) {
                $int = intval($data);
                $float = floatval($data);
                $re = "~^-?[0-9]+(\.[0-9]+)?$~xD";
                //@TODO this will not accept all float values, this validates /against/ syntax

                if (($int === (int)trim($data, '-')) && strlen((string)(int)$data) === strlen($data)) {
                    $data = (int) $data;
                } elseif ($int !== $float && preg_match($re, $data) === 1 && strlen($data) === strlen($float)) {
                    $data = $float;
                }
            }
        }

        return $data;
    }
}
