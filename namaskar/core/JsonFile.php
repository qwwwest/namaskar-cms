<?php
/* 
 * JsonFile Class 
 * This class is used for json file related (Create, Insert, Update, and Delete) operations 
 * @author    Qwwwest.com
 * @url        http://www.qwwwest.com 
 */

class JsonFile
{
    private $jsonFile = null;
    private $data = null;

    public function __construct($jsonFile, $sort = true)
    {
        if (!file_exists($jsonFile)) {
            die("file not found: $jsonFile");
        }

        $this->jsonFile = $jsonFile;
        $this->data = json_decode(file_get_contents($jsonFile), true);

        if ($this->data === null) {
            $err = json_last_error();
            die("Json error $err in: $jsonFile");

        }

    }

    private function save()
    {
        return file_put_contents($this->jsonFile, json_encode($this->data));
    }

    public function findAll()
    {
        if (empty($this->data))
            return false;

        $this->sort('id');
        return $this->data;

    }

    public function sort($prop = 'id')
    {
        if (empty($this->data))
            return false;
        usort($this->data, function ($a, $b) use ($prop) {
            if ($a === $b)
                return 0;

            return $a[$prop] < $b[$prop] ? -1 : 1;
        });

        return $this->data;

    }

    public function getLabels()
    {
        $labels = [];

        foreach ($this->data[0] as $key => $value) {
            $labels[] = $key;
        }

        return $labels;

    }
    public function findOne($id)
    {
        foreach ($this->data as $key => $value) {
            if (!empty($value['id']) && $value['id'] == $id)
                return $value;
        }
        return false;
    }

    public function find($obj)
    {
        die('TODO');
        // $singleData = array_filter($this->data, function ($var) use ($id) {
        //     return (!empty($var['id']) && $var['id'] == $id);
        // });
        // if (count($singleData) === 0)
        //     return false;

        foreach ($this->data as $key => $value) {
            if (!empty($value['id']) && $value['id'] == $id)
                return $value;
        }
        return false;
        // var_dump($singleData);
        // return $singleData[0];
        // $singleData = array_values($singleData)[0];
        // return !empty($singleData) ? $singleData : false;
    }

    public function insert($newData)
    {
        if (!empty($newData))
            return false;

        $id = time();
        $newData['id'] = $id;

        $this->data = !empty($this->data) ? array_filter($this->data) : $this->data;
        if (!empty($this->data)) {
            array_push($this->data, $newData);
        } else {
            $this->data[] = $newData;
        }
        $insert = $this->save();

        return $insert ? $id : false;

    }

    public function update($upData, $id)
    {
        if (empty($upData) || !is_array($upData) || empty($id))
            return false;

        foreach ($this->data as $key => $value) {
            if ($value['id'] === $id) {
                if (isset($upData['name'])) {
                    $this->data[$key]['name'] = $upData['name'];
                }
                if (isset($upData['email'])) {
                    $this->data[$key]['email'] = $upData['email'];
                }
                if (isset($upData['phone'])) {
                    $this->data[$key]['phone'] = $upData['phone'];
                }
                if (isset($upData['country'])) {
                    $this->data[$key]['country'] = $upData['country'];
                }
            }
        }
        $update = $this->save();

        return $update ? true : false;

    }

    public function delete($id)
    {
        $newData = array_filter($this->data, function ($var) use ($id) {
            return ($var['id'] != $id);
        });

        $delete = $this->save();
        return $delete ? true : false;
    }
}