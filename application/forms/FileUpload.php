<?php

class Application_Form_FileUpload extends Zend_Form
{

    public function init()
    {
        // Set the method for the display form to POST
        $this->setMethod('post');

        // Add an fridge element
        $this->addElement('file', 'fridge', array(
            'label'      => 'Fridge CSV:',
            'required'   => true,
        ));
        // Add an recipes element
        $this->addElement('file', 'recipes', array(
            'label'      => 'Recipes JSON:',
            'required'   => true
        ));

        // Add the submit button
        $this->addElement('submit', 'submit', array(
            'ignore'   => true,
            'label'    => 'Cooking',
        ));
    }
}
