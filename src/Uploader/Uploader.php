<?php
namespace Plugins\Uploader;
use Plugins\Uploader\Helpers\Format;

/**
 * Uploader executable class
 *
 * @package   Uploader
 * @since     PHP >=5.4
 * @version   1.0
 * @author    Stanislav WEB | Lugansk <stanisov@gmail.com>
 * @copyright Stanislav WEB
 */
class Uploader implements \Phalcon\DI\InjectionAwareInterface
{
    /**
     * Instance of DI
     *
     * @var \Phalcon\DI\FactoryDefault()
     */
    protected $di;

    /**
     * Request Di
     *
     * @var \Phalcon\Http\Request $rules
     */
    private $request;


    /**
     * File Di
     *
     * @var \Phalcon\Http\Request\File $files
     */
    private $files;

    /**
     * Validation Rules
     *
     * @var array $rules
     */
    private $rules  = [];

    /**
     * Validator
     *
     * @var \Plugins\Uploader\Validator
     */
    private $validator;

    /**
     * Initialize rules
     *
     * @param array $rules
     *
     * @return null
     */
    public function __construct($rules = [])
    {
        if(empty($rules) === false) {

            $this->setRules($rules);

        }

        // get validator
        $this->validator = new Validator();
    }

    /**
     * Implemented. Get DI container
     *
     * @return \Phalcon\DiInterface
     */
    public function getDI()
    {
        return $this->di;
    }

    /**
     * Implemented. Setup DI container
     *
     * @param \Phalcon\DiInterface $di
     *
     * @return null
     */
    public function setDI($di)
    {
        $this->di = $di;

        // get current request data
        $this->request = $this->di->get('request');
    }

    /**
     * Setting up rules for uploaded files
     *
     * @param array $rules
     * @return Uploader
     */
    public function setRules(array $rules)
    {

        foreach ($rules as $key => $values) {

            if(is_array($values) === true && empty($values) === false) {

                $this->rules[$key]  =   $values;
            }
            else {
                $this->rules[$key]  =   trim($values);
            }
        }

        return $this;
    }

    /**
     * Check if upload files are valid
     *
     * @return bool
     */
    public function isValid() {

        // get files for upload
        $this->files = $this->request->getUploadedFiles();

        if(sizeof($this->files) > 0) {

            // do any actions if files exists

            foreach($this->files as $n => $file) {

                // apply all the validation rules for each file

                foreach ($this->rules as $key => $rule) {

                    if (method_exists($this->validator, 'check' . ucfirst($key)) === true) {
                        $this->validator->{'check' . ucfirst($key)}($file, $rule);
                    }
                }
            }
        }

        return (empty($this->getErrors())  === true) ? true : false;
    }

    /**
     * Check if upload files are valid
     *
     * @return void
     */
    public function move() {

        // do any actions if files exists

        foreach($this->files as $n => $file) {

            if(isset($this->rules['sanitize']) === true) {
                $filename   =   Format::toLatin($file->getName(), '', true);
            }

            if(isset($this->rules['hash']) === true) {
                $filename   =   md5($file->getName()).'.'.$file->getExtension();
            }

            if(isset($filename) === false) {
                $filename   =   $file->getName();
            }

            // move file to target directory
           $file->moveTo(rtrim($this->rules['directory'], '/').DIRECTORY_SEPARATOR.$filename);

        }

        return;
    }

    /**
     * Return errors messages
     *
     * @return array
     */
    public function getErrors() {

        // error container
        return $this->validator->errors;

    }
}