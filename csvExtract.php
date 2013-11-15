<?php
class csv
{
    function __construct()
    {
        $this->separator = $GLOBALS['csvSeparator'];
    }

    public function importCsv($file)
    {
        if (file_exists($file)) {
            if (is_readable($file)) {
                $elements = file($file);
                if (count($elements) > 0)
                {
                    $result = $elements;
                }
                else {
                    $result = 'ERROR : file is empty !';
                }
            }
            else {
                $result = 'ERROR : file is not readable !';
            }
        }
        else {
            $result = 'ERROR : file not exists !';
        }

        if (is_array($result)) {
            $this->file   = $result;
            $this->fileName = $file;
            $this->fields = $this->dataToArray(array_shift($result));
            foreach ($result as $key => $value) {
                $this->datas[$key] = $this->dataToArray($value);
            }
            $this->nbFields = count($this->fields);
            $state        = TRUE;
        }
        else {
            echo '<span class="error">' . $result . '</span>';
            $state = FALSE;
        }

        return $state;
    }

    private function dataToArray($data)
    {
        if (!empty($data)) {
            $line   = str_replace('"', '', $data);
            $fields = explode($this->separator, $line);
            array_pop($fields);
            if (count($fields) > 0) {
                $result = $fields;
            }
            else {
                $result = 'ERROR : data not found !';
            }
        }
        else {
            $state = FALSE;
        }

        if (!is_array($result)) {
            echo '<span class="error">' . $result . '</span>';
            $result = FALSE; 
        }

        return $result;
    }

    public function changeFields($array)
    {
        if (is_array($array)) {
            $fields = $this->fields;
            foreach ($array as $key => $value) {
                $fields[$key] = $value;
            }
            if ($this->nbFields === count($fields)) {
                $this->fields = $fields;
                $state = TRUE;
            }
            else {
                echo '<span class="error">ERROR ! Can\'t change fields, arrays have not the same number values !</span>';
                $state = FALSE;
            }
        }
        else {
            echo '<span class="error">ERROR ! Can\'t change fields !</span>';
            $state = FALSE;
        }
        return $state;
    }

    public function removeField($key) {
        if (!empty($key)) {
            if (strpos($key, ',') !== FALSE) {
                $toRemove = explode(',', $key);
                foreach ($toRemove as $value) {
                    $this->removeField($value);
                }
            }
            elseif (strpos($key, '-')) {
                $toRemove = explode('-', $key);
                for ($i = $toRemove[0]; $i <= $toRemove[1] ; $i++) { 
                    $this->removeField($i);
                }
            }
            else {
                $fields = $this->fields;
                $datas  = $this->datas;
                unset($fields[$key]);
                foreach ($datas as $dataKey => $value) {
                    unset($value[$key]);
                    $datas[$dataKey] = $value;
                }
                if (is_array($fields) && is_array($datas)) {
                    $this->fields = $fields;
                    $this->datas  = $datas;
                }
            }
        }
    }
    public function exportCsv()
    {
        $csv = implode(';', $this->fields) . PHP_EOL;
        if (is_array($this->datas)) {
            foreach ($this->datas as $data) {
                $csv .= implode(';', $data) . PHP_EOL;
            }
        }
        if (!empty($csv)) {
            $fileName = str_replace('.', '_save.', $this->fileName);
            file_put_contents($fileName, $csv);
        }
    }
}
?>