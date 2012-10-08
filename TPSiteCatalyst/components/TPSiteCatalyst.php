<?php
/**
 * Adobe SiteCatalyst Component
 *
 * @author Philip Lawrence <philip@misterphilip.com>
 * @link http://misterphilip.com
 * @link http://tagpla.net
 * @link https://github.com/TagPlanet/yii-analytics
 * @copyright Copyright &copy; 2012 Philip Lawrence
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @version 1.0.0
 */
class TPSiteCatalyst extends CApplicationComponent
{
    protected $settings = array(
        'namespace'      => 'string',
        'createObject'   => 'bool',
        'rsids'          => 'array',
        's_codeLocation' => 'string',
        'autoRender'     => 'bool',
        'autoPageview'   => 'bool',
        'renderMoble'    => 'bool',
        'renderHead'     => 'bool',
    );
    
    protected $namespace = 's';
    protected $createObject = true;
    protected $rsids = array();
    protected $s_codeLocation = '';
    protected $autoRender = false;
    protected $autoPageview = true;
    protected $renderMobile = false;
    protected $renderHead = false;
            
    /**
     * Type of quotes to use for values
     */
    const Q = "'";

    /**
     * Method data to be pushed into the s object
     * @var array
     */
    private $_data = array();

    /**
     * init function - Yii automatically calls this
     */
    public function init()
    {
        // Nothing needs to be done initially, huzzah!
    }

    /**
     * Render and return the SiteCatalyst data
     * @return mixed
     */
    public function render()
    {
        // Get the render location
        $renderLocation = ($this->renderHead) ? CClientScript::POS_HEAD : CClientScript::POS_END;
        
        // Get the namespace
        $n = (($this->namespace != '' && ctype_alnum($this->namespace)) ? $this->namespace : 's');
        
        // Check for s_code rendering
        if($this->s_codeLocation != '')
            Yii::app()->clientScript->registerScriptFile($this->s_codeLocations, $renderLocation);
        
        // Start the rendering...
        $js = '';
        
        // Do we need to create the object?
        if($this->createObject)
            $js.= 'var ' . $n . ' = ' . $n . '_account(' . self::Q . implode($this->rsids, ',') . self::Q . ');' . PHP_EOL;
        
        // Go through the data
        foreach($this->_data as $var => $value)
        {
            $js.= $n . '.' . $var . ' = ' . self::Q . preg_replace('~(?<!\\\)'. self::Q . '~', '\\'. self::Q, $value) . self::Q . ';' . PHP_EOL;
        }
        
        // Should we add s.t()?
        if($this->autoPageview)
            $js.= $n . '.t();' . PHP_EOL;
        
        // TagPla.net copyright... please leave in here!
        $js.= '// Adobe SiteCatalyst Extension provided by TagPla.net' . PHP_EOL;
        $js.= '// https://github.com/TagPlanet/yii-analytics' . PHP_EOL;
        $js.= '// Copyright 2012, TagPla.net & Philip Lawrence' . PHP_EOL;
        
        
        // Should we auto add in the analytics tag?
        if($this->autoRender)
        {
            Yii::app()->clientScript
                    ->registerScript('TPSiteCatalyst', $js, CClientScript::POS_HEAD);
        }
        else
        {
            return $js;
        }
        
        return;
    }
    
    /**
     * Wrapper for getting / setting options
     *
     * @param string $name
     * @param mixed  $value
     * @return mixed (success if set / value if get)
     */
    public function setting($name, $value = null)
    {
        // Get value
        if($value === null)
        {
            if(isset($this->settings[$name]))
            {
                return $this->$name;
            }
            return null;
        }
        
        // Set value
        if(isset($this->settings[$name]) && gettype($value) == $this->settings[$name])
        {
            $this->$name = $value;
            return true;
        }
        return false;
    }
    
    /**
     * Magic Method for setting settings
     * @param string $name
     * @param mixed $value
     * @param array  $arguments
     */
    public function __set($name, $value)
    {        
        if(isset($this->settings[$name]))
            return $this->setting($name, $value);
        
        if($this->_validVariableName($name))
        {
            // iz gud
            $this->_data[$name] = $value;        
        }
    }
    
    /**
     * Valid variable name
     * Verifies the variable name passed in is OK
     * 
     * @param string $name
     * @returns bool
     */
    protected function _validVariableName($name)
    {
        // @TODO: Update this list with more
        $named = array('pageName','channel','server','campaigns','products','TnT','events','pageType','purchaseID');
        $count = array('hier', 'eVar', 'prop');
        
        // Check for named
        if(in_array($name, $named))
            return true;
        
        // Check against numbered vars
        foreach($count as $var)
            if(strpos($name, $var) === 0)
                return true;
        
        // No matches :(
        return false;
    }    

    /**
     * Magic Method for options
     * @param string $name
     * @param array  $arguments
     */
    public function __call($name, $arguments)
    {
        if($name[0] != '_')
            $name = '_' . $name;
        if(in_array($name, $this->_availableOptions))
        {
            $this->_push($name, $arguments);
            return true;
        }
        return false;
    }

    /**
     * Push data into the array
     * @param string $variable
     * @param array  $arguments
     * @protected
     */
    protected function _push($variable, $arguments)
    {
        $data = array_merge(array($variable), $arguments);
        if($variable == '_cookiePathCopy' || $variable == '_trackTrans')
        {
            array_push($this->_delayedData, $data);
        }
        else
        {
            array_push($this->_data, $data);
        }
        $this->_calledOptions[] = $variable;
    }
}