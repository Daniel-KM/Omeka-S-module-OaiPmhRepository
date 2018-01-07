<?php
namespace OaiPmhRepository\Form;

use Omeka\Stdlib\Message;
use Zend\Form\Element\Checkbox;
use Zend\Form\Element\Number;
use Zend\Form\Element\Text;
use Zend\Form\Form;

class ConfigForm extends Form
{
    public function init()
    {
        $this->add([
            'name' => 'oaipmhrepository_name',
            'type' => Text::class,
            'options' => [
                'label' => 'Repository name', // @translate
                'info' => 'Name for this OAI-PMH repository.', // @translate
            ],
            'attributes' => [
                'required' => true,
            ],
        ]);

        $this->add([
            'name' => 'oaipmhrepository_namespace_id',
            'type' => Text::class,
            'options' => [
                'label' => 'Namespace identifier', // @translate
                'info' => new Message('This will be used to form globally unique IDs for the exposed metadata items.') // @translate
                    . ' ' . new Message('This value is required to be a domain name you have registered.') // @translate
                    . ' ' . new Message('Using other values will generate invalid identifiers.'), // @translate
            ],
            'attributes' => [
                'required' => true,
            ],
        ]);

        $this->add([
            'name' => 'oaipmhrepository_expose_media',
            'type' => Checkbox::class,
            'options' => [
                'label' => 'Expose media', // @translate
                'info' => new Message('Whether the plugin should include identifiers for the files associated with items.') // @translate
                    . ' ' . new Message('This provides harvesters with direct access to files.'), // @translate
            ],
        ]);

        $this->add([
            'name' => 'oaipmhrepository_list_limit',
            'type' => Number::class,
            'options' => [
                'label' => 'List limit', // @translate
                'info' => new Message('Number of individual records that can be returned in a response at once.') // @translate
                    . ' ' . new Message('Larger values will increase memory usage but reduce the number of database queries and HTTP requests.') // @translate
                    . ' ' . new Message('Smaller values will reduce memory usage but increase the number of DB queries and requests.'), // @translate
            ],
            'attributes' => [
                'min' => '1',
            ],
        ]);

        $this->add([
            'name' => 'oaipmhrepository_token_expiration_time',
            'type' => Number::class,
            'options' => [
                'label' => 'Token expiration time', // @translate
                'info' => new Message('In minutes, the length of time a resumption token is valid for.') // @translate
                    . ' ' . new Message('This means harvesters can re-try old partial list requests for this amount of time.') // @translate
                    . ' ' . new Message('Larger values will make the tokens table grow somewhat larger.'), // @translate
            ],
            'attributes' => [
                'min' => '1',
            ],
        ]);
    }
}
