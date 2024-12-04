<?php
namespace Qwwwest\Namaskar;


use Qwwwest\Namaskar\Kernel;


/*
 *  Form Class
 *  Creates HTML Forms with browser and server-side validation
 */

class Form
{

    private static $forms = [];
    private string $formId;
    private string $mode;
    private bool $isPost;
    private static $renderer;
    private static $demofill = false;
    private $conf;
    private $regex;
    private $fields;
    private $validPostFields = 0;
    private $form_message;

    public function __construct($renderer)
    {
        if (self::$renderer == null) {
            self::$renderer = $renderer;

            $this->addShortcodes($renderer);
        }

        $this->conf = Kernel::service('ZenConfig');
        $this->mode = $_SERVER['REQUEST_METHOD'];
        $this->isPost = $_SERVER['REQUEST_METHOD'] === 'POST';
        $this->fields = [];
        $this->form_message = [];

        $forms[] = $this;

        $regex = [];
        $regex['hidden'] = '^[a-zA-Z][a-zA-Z0-9._-]{1,16}$';
        //Must contain at least one  number and one uppercase and lowercase letter, and at least 8 or more characters
        // $regex['password'] = '(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}';
        //$regex['url'] = 'https?://.+';
        $regex['email'] = '^[a-z0-9._%+\-]+@[a-z0-9.\-]+\.[a-z]{2,}$';

        $regex['tel'] = '^(?:.*\d.*){7,}$';
        $regex['text'] = '^.{2,}$';
        $regex['textarea'] = '^.{2,}$';
        $regex['name'] = '^.{2,32}$';
        $this->regex = $regex;


    }




    public function isFormClean()
    {

        return count($_POST) === 0;
    }


    public function wasValidated()
    {

        return $this->isPost;
    }



    public function isFormValid()
    {

        if (!$this->isFormComplete())
            return false;


        foreach ($this->fields as $key => $field) {


            if (!$this->validateField($field))
                return false;

        }
        return true;
    }


    public function isFormComplete()
    {

        return $this->isPost && $this->validPostFields === count($this->fields);

    }

    public function addField($fieldId, $type, $required, $value = null, $attributes = null)
    {
        if (isset($_POST[$fieldId])) {
            $this->validPostFields++;
        }
        $this->fields[] = [$fieldId, $type, $required, $value, $attributes];
    }

    public function sanitize($type, $value)
    {

    }



    public function addShortcodes($renderer)
    {
        $renderer->addShortcode('contact_form', [$this, 'contact_form']);
        $renderer->addShortcode('form', [$this, 'form']);
        $renderer->addShortcode('form_message', [$this, 'form_message']);
        $renderer->addShortcode('input', [$this, 'input']);
        $renderer->addShortcode('select', [$this, 'select']);
        $renderer->addShortcode('submit', [$this, 'submit']);


    }




    public function form_message($attributes, $content, $tagName): string
    {
        $this->form_message[$attributes[0]] = $content;

        return '';

    }
    public function form($attributes, $content, $tagName): string
    {



        $this->formId = strtolower($attributes['id'] ?? '');
        if ($this->formId === '')
            die('FormId id is required');

        if (!$this->isValidFormId($this->formId))
            die('FormId is not valid:');

        self::$demofill = $attributes['devmode'] ?? false;



        $devFormInfo = '';

        // add formId as a hidden value in the form
        $content = "[input hidden form_id $this->formId ]\n$content";

        $content = self::$renderer->renderBlock($content);


        $classes = self::$renderer->getCssClasses($attributes);
        $classes = $classes ? ' ' . $classes : '';


        if ($this->wasValidated()) {
            $classes .= " was-validated";
        }

        $form_message = "";
        $inert = '';
        if ($this->isFormComplete()) {
            if ($this->isFormValid()) {

                $formData = [];

                $formData['id'] = uniqid();

                //firstname,lastname,email,phone,city,country,message,date
                foreach ($this->fields as $key => $field) {
                    [$fieldId, $type, $required, $value, $attributes] = $field;

                    $formData[$fieldId] = $value;
                }

                $filename = $formData['form_id'];
                unset($formData['form_id']);
                unset($formData['submit']);
                date_default_timezone_set('Europe/Paris');
                $formData['date'] = date('Y-m-d H:i:s');
                $formData['time'] = time();
                $formData['ip'] = getUserIpAddress();
                $formData['status'] = 1;

                try {

                    saveContactToCSV($formData, $filename);
                    $message = $this->form_message['success'];
                    $alert = 'success';
                    $inert = ' inert';
                } catch (\Exception $e) {
                    $message = $this->form_message['error'];
                    $alert = 'error';
                }

            } else {
                $message = $this->form_message['fail'];
                $alert = 'warning';
            }



            $message = self::$renderer->renderBlock($message);

            $form_message = <<<HTML
            <div class="alert alert-$alert" role="alert">
                $message
            </div>

            HTML;
        }

        if (self::$demofill) {

            $isFormComplete = $this->isFormComplete() ? 'Form Complete' : 'Form NOT Complete';
            $isFormValid = $this->isFormValid() ? 'Form Valid' : 'Form NOT Valid';
            $post_count = count($_POST);
            $method = $_SERVER['REQUEST_METHOD'];
            $fields = '';
            foreach ($this->fields as $key => $field) {
                $index = $key + 1;
                $req = $field[2] ? '<b>*</b>' : '';

                $valid = ' ';
                if (!$this->validateField($field)) {
                    $valid = '<b>x</b>';
                }
                //  | index | type(*) | Id:value |
                $fields .= <<<HTML
                
                <tr><td>$index &nbsp;</td><td>$valid $field[1]$req </td><td><b>$field[0]</b>:"$field[3]"</td></tr>
                HTML;

            }

            $devFormInfo = <<<HTML
                <div class="alert alert-warning" role="alert">
                    <b>INFO</b> for FormId=<b>$this->formId</b>. 
                    <br>Remember to remove  <b>devmode=1</b> it after testing.
                    <br>
                    $isFormComplete  
                    $isFormValid
                    <br>
                    Method: $method     
                    <br>

                    values = $post_count :: validPostFields = $this->validPostFields
                    <br>
                    FIELDS:<br>
                    <table>
                    $fields 
                    </table>
                    
                  
                </div>

                HTML;

        }


        if ($inert) {
            $classes .= ' sent';
        }


        return <<<HTML
          $form_message
        <form method=post class="mb-3 row$classes" id="$this->formId"$inert> 
        
            $content
        </form>
      
        $devFormInfo
        HTML;


    }

    public function contact_form($attributes, $content, $tagName): string
    {


    }
    public function select($attributes, $content, $tagName): string
    {
        // [field text firstname]
        $type = $attributes[0];
        $fieldId = $attributes[1];

        $classes = self::$renderer->getCssClasses($attributes);

        //$classes = $classes ? $classes . ' ' : '';

        $content = <<<HTML
        <div class="$classes">
            <label for="$fieldId" class="form-label">State</label>
            <select id="$fieldId" name="$fieldId" class="form-select">
            <option selected>Choose...</option>
            <option>1</option>
            <option>2</option>
            </select>
        </div>
    
        HTML;

        return $content;


    }

    public function submit($attributes, $content, $tagName): string
    {

        $label = $attributes[0];

        // $classes = self::$renderer->getCssClasses($attributes);

        $this->addField('submit', 'submit', true, 'submit');
        //$classes = $classes ? $classes . ' ' : '';
        $classes = self::$renderer->getCssClasses($attributes);
        $content = <<<HTML
        <div class="col-12 text-center ">
        <button type="submit" id="submit" name="submit" value="submit" class="btn btn-lg mb-3 $classes">$label</button>
        </div>
        
        HTML;

        return $content;


    }

    public function input($attributes, $content, $tagName): string
    {


        $regex = $this->regex;

        $type = $attributes[0];

        $label = $attributes[2] ?? '';

        $fieldId = $attributes[1];

        $isRequired = false;
        $required = '';

        if (substr($type, -1) === '*') {
            $isRequired = true;
            $required = ' required';
            $type = rtrim($type, '*');
        }


        $value = "";
        // demofill : auto fill form input values for testing purposes. 
        if (self::$demofill && !$_POST) {

            $value = ucfirst($attributes[1]);
            if ($type === 'email') {
                $value = "$fieldId@example.com";
            }
            if ($type === 'tel') {
                $value = "+33 6 123 456 789";
            }
            if ($type === 'hidden' && $fieldId === 'form_id') {
                $value = $this->formId;
            }
            if ($type === 'textarea') {
                $value = "";
            }
        }

        if (isset($_POST[$fieldId])) {
            $value = trim($_POST[$fieldId]);

            $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        }

        if (isset($_POST[$fieldId]) && $type === 'hidden' && $fieldId === 'form_id' && $value !== $this->formId) {


            dump($_POST);
            die('form_id mismatch : ' . $value . ' != ' . $this->formId);

        }

        $classes = self::$renderer->getCssClasses($attributes);

        $classes = $classes ? $classes . ' ' : 'col-12';

        if ($attributes['md'] ?? false) {
            $classes .= ' col-md-' . $attributes['md'];
        }


        if ($type === 'hidden') {
            $height = $attributes['height'] ?? '10rem';
            $content = <<<HTML
                <input type="hidden" name="$fieldId" value="$this->formId">
                HTML;

        } elseif ($type === 'textarea') {
            $height = $attributes['height'] ?? '10rem';
            $minlength = $attributes['min'] ?? 10;
            $maxlength = $attributes['max'] ?? 2048;

            $content = <<<HTML
                <div class="form-floating mb-3">
                
                <textarea class="form-control" placeholder="$label" id="$fieldId" style="height: $height" name="$fieldId"  minlength="$minlength" maxlength="$maxlength" $required>$value</textarea>
                <label for="$fieldId">$label</label>
                </div>
                HTML;

        } elseif ($type === 'checkbox')
            $content = <<<HTML
   
        <div class="col-12 mb-3">
            <div class="form-check">
            <input class="form-check-input" type="checkbox" id="$fieldId" name="$fieldId" value="$value" $required>
            <label class="form-check-label" for="$fieldId">$label</label>
            </div>
        </div>
        HTML;
        else {
            $pattern = " pattern='$regex[$type]' title=''";
            $content = <<<HTML
        
            <div class="form-floating $classes mb-2">
            <input type="$type" class="form-control" id="$fieldId" name="$fieldId" aria-label="$fieldId" value="$value" $pattern  $required placeholder="$label">
            <label for="$fieldId">$label</label>
            </div>
            HTML;
        }



        $this->addField($fieldId, $type, $isRequired, $value, $attributes);

        return $content;
    }

    function isValidFormId($string)
    {
        return preg_match('/^[a-z0-9_-]+$/', $string) === 1;
    }


    public function validateField($field)
    {
        [$fieldId, $type, $required, $value, $attributes] = $field;

        $value = trim($value);

        if (!$required && $value === '')
            return true;

        if ($required && $value === '')
            return false;


        if ($type === 'submit')
            return true;

        $regex = $this->regex[$type];

        $min = $attributes['min'] ?? null;
        $max = $attributes['max'] ?? null;
        $len = strlen($value);

        if ($min && $len < $min)
            return false;
        if ($max && $len > $max)
            return false;

        if ($type === 'textarea') {



            return preg_match("/$regex/", $value);

        }

        if ($type === 'text' || $type === 'name' || $type === 'hidden') {

            $preg_match = preg_match('/' . $regex . '/', $value);

            if ($preg_match === false)
                dd("validateField fail $type /$regex/ $value");
            return $preg_match;

        }
        if ($type === 'tel') {

            // Remove spaces, dashes, and parentheses, letters...
            $cleanedPhone = preg_replace('/[^\d+]/', '', $value);

            return preg_match('/^(\+|0)?[0-9]{7,16}$/', $cleanedPhone);


        }
        if ($type === 'email') {
            return filter_var($value, FILTER_VALIDATE_EMAIL);
        }

        die('TYPE=' . $type);
    }



}