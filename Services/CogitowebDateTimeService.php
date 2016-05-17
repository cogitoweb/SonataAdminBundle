<?php

namespace Sonata\AdminBundle\Services;

/**
 * Description of CogitowebDateTimeService
 *
 * @author 2z
 */
class CogitowebDateTimeService
{
    /**
     *
     * @var logger 
     */
    protected $logger;
    
    /**
     *
     * @var request
     */
    protected $request;

    /**
     * 
     * @param type $logger
     */
    public function __construct($logger) {
        $this->logger = $logger;
    }

    /**
     * 
     * @param type $request
     */
    public function setRequest($request) {
        $this->request = $request;
    }
    
    /**
     * 
     * @param boolean $short 
     * @param boolean $filter  -> true su filtri
     * @return string
     * @throws \Exception
     */
    public function getDateFormat($short = false, $filter = false) {

        if(!$this->request) {
            throw new \Exception('missing request: set request before call get');
        }
        
        switch($this->request->getLocale())
        {
            case 'it':
                $format = ($filter) ? 'd/m/Y' : (($short) ? 'd/M/Y' : 'dd/MM/yyyy');
                break;
            
            case 'en':
                $format = ($filter) ? 'm/d/Y' : (($short) ? 'M/d/Y' : 'MM/dd/yyyy');
                break;
            
            default:
                $format = ($filter) ? 'd/m/Y' : (($short) ? 'd/M/Y' : 'dd/MM/yyyy');
                break;
        }
        
        return $format;
    }
    
    /**
     * 
     * @param boolean $short 
     * @param boolean $filter  -> true su filtri
     * @return string
     * @throws \Exception
     */
    public function getDateTimeFormat($short = false, $filter = false) {

        if(!$this->request) {
            throw new \Exception('missing request: set request before call get');
        }
        
        $format = $this->getDateFormat($short, $filter);
        
        $format .= ($filter) ? ' H:i' : (($short) ? ' H:i' : ' HH:mm');
        
        return $format;
    }

    /**
     * 
     * @return array
     */
    public function getDateWidgetParameters() {
        
        return array('widget' => 'single_text',
                'format' => $this->getDateFormat(),
                'attr' => array('class' => 'datepicker')
            );
        
    }
    
    /**
     * 
     * @return array
     */
    public function getDateTimeWidgetParameters() {
        
        return array('widget' => 'single_text',
                'format' => $this->getDateTimeFormat(),
                'attr' => array('class' => 'datetimepicker')
            );
        
    }
    
    /**
     * prende i default passati e aggiunge i defaults di date
     * 
     * @param array $defaults
     * @return array
     */
    public function mergeWidgetParameters($defaults) {
        
        return array_merge($defaults, $this->getDateWidgetParameters());
        
    }
    
}
