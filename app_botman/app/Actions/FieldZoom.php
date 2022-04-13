<?php
namespace App\Actions;

use BotMan\BotMan\Interfaces\QuestionActionInterface;
use JsonSerializable;

class FieldZoom implements JsonSerializable, QuestionActionInterface
{
    /** @var string */
    protected $key;
    
    /** @var string */
    protected $value = ' ';
    
    /** @var boolean */
    protected $editable;
    
    /** @var boolean */
    protected $short;
    
    /** @var array */
    protected $additional = [];
    
    /**
     * @param string $key
     *
     * @return static
     */
    public static function create($key)
    {
        return new static($key);
    }
    
    /**
     * @param string $key
     */
    public function __construct($key)
    {
        $this->key = $key;
    }
    
    /**
     * Set the button value.
     *
     * @param string $value
     * @return $this
     */
    public function value($value)
    {
        $this->value = $value;
        
        return $this;
    }
    
    /**
     * Set the editable attribute.
     *
     * @param boolean $editable
     * @return $this
     */
    public function editable($editable)
    {
        $this->editable = $editable;
        
        return $this;
    }
    
    /**
     * Set the fields additional parameters to pass to the service.
     *
     * @param array $additional
     * @return $this
     */
    public function additionalParameters(array $additional)
    {
        $this->additional = $additional;
        
        return $this;
    }
    
    /**
     * Set the short attribute.
     *
     * @param boolean $short
     * @return $this
     */
    public function short($short)
    {
        $this->short = $short;
        
        return $this;
    }
    
    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'type' => 'field',
            'key' => $this->key,
            'value' => $this->value,
            'editable' => isset($this->editable) ? $this->editable : true,
            'short' => isset($this->short) ? $this->short : false,
            'additional' => $this->additional,
        ];
    }
    
    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }
}