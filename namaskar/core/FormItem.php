<?php
/* 
 * Validator Class 
 * This class is used to validate data
 * @author    Qwwwest.com
 * @url        http://www.qwwwest.com 
 */


//username [a-zA-Z][a-zA-Z0-9-_]{4,24}

class FormItem
{

    
    protected string $prop;
    protected string $label;
    protected string $type;
    protected string $regex = '';
    protected string $options = '';
    protected bool $required = false;
    protected bool $private = false;
    protected bool $readonly = false;
    protected bool $hidden = false;
    protected bool $visible = false;
    protected $min = null;
    protected $max = null;

    protected $form = null;

    protected $value = null;


    public function __construct($prop, $type, $label, $required, $form)
    {
        $this->prop = $prop;
        $this->form = $form;
        $this->hidden = ($type === 'hidden');
        $this->visible = !$this->hidden;
        $this->type = $type;
        $this->required = $required;
        $this->label = $label ?? $prop;

    }

    public function __get($property)
    {

        if($this->type === 'checkbox' && $property === 'value')
        return $this->value ? 'true' : 'false';

        if (property_exists($this, $property)) {
            return $this->$property;
        }

        if($property === 'options')return $this->options();
         

    }

    public function set($property, $value = null)
    {
        if ($value === null) { // "required optional readonly hidden private" are booblean
            $properties = explode(' ', $property);
            foreach ($properties as $key => $property) {
                switch ($property) {
                    case 'required':
                    case 'private':
                    case 'readonly':
                        $this->{$property} = true;
                        break;
                    case 'hidden':
                    case 'visible':
                        $this->hidden = $property === 'hidden';
                        $this->visible = $property === 'visible';
                        break;
                    default:
                        die("unknown properrrrty $property in FormItem set method");
                        break;
                }

            }

            return $this;
        } else {
            //value no null
            if (property_exists($this, $property)) {
                $this->{$property} = $value; // set min max...
                return $this;
            }
        }



        die("formItem set function, property not found : $property");

    }

    // https://getbootstrap.com/docs/5.2/forms/validation/
    public function __call($name, $arguments)
    {

        switch ($name) {
            case 'required':
            case 'private':
            case 'readonly':
            case 'hidden':
                $this->{$name} = true;
                break;
            case 'min':
            case 'max':
            case 'value':
                $this->{$name} = $arguments[0];
                break;
            case 'regex':
                $this->{$name} = $arguments[0];
                break;
            default:
                die("unknown property $name in FormItem set method");
                break;
        }

        return $this;
    }

    private function options()
    {
        if($this->form->bypass)return '';

        $options = [];
        if ($this->readonly)
            $options[] = 'readonly disabled';
        if ($this->required)
            $options[] = 'required';
        if ($this->min && $this->type === 'text')
            $options[] = 'minlength=' . $this->min;
        if ($this->max  && $this->type === 'text')
            $options[] = 'maxlength=' . $this->max;  
            
        if ($this->min && $this->type === 'number')
            $options[] = 'min=' . $this->min;
        if ($this->max  && $this->type === 'number')
            $options[] = 'max=' . $this->max;          
        if ($this->regex)
            $options[] = 'pattern="'.$this->regex.'"';        

        return implode(' ', $options);

    }
    // public function value($value)
    // {
    //     $this->value = $value;
    // }

    // public function init($value)
    // {
    //     $this->value = $value;
    // }

    public function validate()
    {
         
        if (!isset($_POST[$this->prop]))
            return "property $this->prop not in POST data";
        $postval = trim(strip_tags($_POST[$this->prop]));

        $errMessage = "$this->prop is not valid";

        if ($postval === '')
            return $this->required ? $errMessage : '';

        if (
            $this->type === 'email'
            && !filter_var($postval, FILTER_VALIDATE_EMAIL)
        )

            return $errMessage;
        return ''; //empty string = ok.
    }


    public function render()
    {
        $item = $this;
        $template = new Template('template/formItem.php');
        $reflect = new ReflectionClass($this);
        $props   = $reflect->getProperties(ReflectionProperty::IS_PROTECTED);

        foreach ($props as $prop) {
        //print $prop->name . "\n";
        $template->{$prop->name} = $this->{$prop->name};
    }

        //var_dump($props);
        return $template->render();
    }

}