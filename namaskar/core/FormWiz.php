<?php
/* 
 * FormWiz Class 
 * Build form and validate data.
 * @author    Qwwwest.com
 * @url        http://www.qwwwest.com 
 */




class FormWiz
{
    private $entity = null;
    private $formItems = [];
    private $formItemsByName = [];
    private $props = [];
    private $types = [];
    private $labels = [];
    private $errors = [];
    private $formId;
    private $bypass;
    private $sessionData;


    public function __construct($entity, $bypass = false)
    {


        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->entity = $entity;

        // if true, no check will be made in the browser
        // it is only for backend debugging purposes
        $this->bypass = $bypass;
        $this->sessionData = $_SESSION[$entity] ?? [];
        if ($entity)
            $this->addItem('formid', 'hidden', null, true, $entity);

    }

    private function addItem($name, $type, $label, $required, $value = null)
    {
        $item = new FormItem($name, $type, $label, $required, $this);
        $this->formItems[] = $item;
        $this->formItemsByName[$name] = $item;
        $this->props[] = $name;
        $this->types[] = $type;
        $this->labels[] = $label ?? ucfirst($name);
        $item->value($this->sessionData[$name] ?? $value);
        return $item;
    }

    public function add($name, $type, $label = null)
    {
        return $this->addItem($name, $type, $label, false);
    }

    public function req($name, $type, $label = null)
    {
        return $this->addItem($name, $type, $label, true);
    }



    public function getLabel($name)
    {
        return $this->labels[$name] ?? null;

    }


    public function getFormItems()
    {
        return $this->formItems;

    }

    public function __get($property)
    {
        if ($property === 'items') {
            return $this->formItems;
        }

        if (property_exists($this, $property)) {
            return $this->$property;
        }

    }

    public function getFormItem($name)
    {
        return $this->formItemsByName[$name] ?? null;

    }



    public function isSubmited()
    {
        return isset($_POST['submit']);
        //
    }

    /* 
    "boolean"
    "integer"
    "double" (float)
    "string"
    "array"
    "object"
    "NULL"
    "unknown type"
    "email"
*/
    public function isValid()
    {
        $this->errors = [];
        foreach ($this->formItems as $key => $item) {
            $msg = $item->validate();
            if ($msg)
                $this->errors[] = $msg;
        }
        return count($this->errors) === 0;

    }

    public function render()
    {
        $template = new Template('template/form.php');
        $template->entity = $this->entity;
        $template->items = $this->formItems;
        return $template->render();
    }

    public function renderForm()
    {
 
        $template = new Template('template/table.php');
 
    $template->formItems = $this->formItems;
        
        return $template->render();
    }

   
}