<?php

class Template
{
    var $data;

    public function add($key, $value)
    {
        $this->data[$key] = $value;
    }

    public function parse($file, $print = true)
    {
        $result = '';

        extract($this->data);

        if (!$print) {
            ob_start();
        }

        include($file);

        if (!$print) {
            $result = ob_get_clean();
        }

        return $result;
    }
}
