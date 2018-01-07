<?php
namespace OaiPmhRepository\Form;

use Zend\Form\Element\Checkbox;
use Zend\Form\Element\Number;
use Zend\Form\Element\Radio;
use Zend\Form\Element\Select;
use Zend\Form\Element\Text;
use Zend\Form\Form;
use Zend\I18n\Translator\TranslatorAwareInterface;
use Zend\I18n\Translator\TranslatorAwareTrait;

class ConfigForm extends Form implements TranslatorAwareInterface
{
    use TranslatorAwareTrait;

    /**
     * @var array
     */
    protected $oaiSetFormats;

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
                'info' => $this->translate('This will be used to form globally unique IDs for the exposed metadata items.') // @translate
                    . ' ' . $this->translate('This value is required to be a domain name you have registered.') // @translate
                    . ' ' . $this->translate('Using other values will generate invalid identifiers.'), // @translate
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
                'info' => $this->translate('Whether the plugin should include identifiers for the files associated with items.') // @translate
                    . ' ' . $this->translate('This provides harvesters with direct access to files.'), // @translate
            ],
        ]);

        $this->add([
            'name' => 'oaipmhrepository_hide_empty_sets',
            'type' => Checkbox::class,
            'options' => [
                'label' => 'Hide empty oai sets', // @translate
                'info' => 'Whether the module should hide empty oai sets.', // @translate
            ],
        ]);

        $this->add([
            'name' => 'oaipmhrepository_global_repository',
            'type' => Radio::class,
            'options' => [
                'label' => 'Global repository', // @translate
                'info' => $this->translate('The global repository contains all the resources of Omeka S, in one place.') // @translate
                    . ' ' . $this->translate('Note that the oai set identifiers are different (item set id or site id).'), // @translate
                'value_options' => [
                    'disabled' => 'Disabled', // @translate
                    'none' => 'Without oai sets', // @translate
                    'item_set' => 'With item sets as oai sets', // @translate
                    'site_pool' => 'With sites as oai sets', // @translate
                ],
            ],
        ]);

        $this->add([
            'name' => 'oaipmhrepository_by_site_repository',
            'type' => Radio::class,
            'options' => [
                'label' => 'Site repositories', // @translate
                'info' => 'The site repositories simulate multiple oai servers, with the site pools of items and the attached item sets as oai sets.', // @translate
                'value_options' => [
                    'disabled' => 'Disabled', // @translate
                    'none' => 'Without oai sets', // @translate
                    'item_set' => 'With item sets as oai sets', // @translate
                ],
            ],
        ]);

        $valueOptions = $this->getOaiSetFormats();
        $valueOptions = array_combine($valueOptions, array_map('ucfirst', $valueOptions));
        $this->add([
            'name' => 'oaipmhrepository_oai_set_format',
            'type' => Select::class,
            'options' => [
                'label' => 'Oai set format', // @translate
                'info' => 'The format of the oai set identifiers.', // @translate
                'value_options' => $valueOptions,
            ],
            'attributes' => [
                'required' => 'true',
            ],
        ]);

        $this->add([
            'name' => 'oaipmhrepository_human_interface',
            'type' => Checkbox::class,
            'options' => [
                'label' => 'Human interface', // @translate
                'info' => $this->translate('The OAI-PMH pages can be displayed with a themable responsive human interface based on Bootstrap (https://getbootstrap.com).'), // @translate
            ],
        ]);

        $this->add([
            'name' => 'oaipmhrepository_redirect_route',
            'type' => Text::class,
            'options' => [
                'label' => 'Global repository redirect route', // @translate
                'info' => 'An alias (redirect 301) for backward compatibility with Omeka Classic, that used "/oai-pmh-repository/request", or any other old OAI-PMH repository.', // @translate
            ],
        ]);

        $this->add([
            'name' => 'oaipmhrepository_list_limit',
            'type' => Number::class,
            'options' => [
                'label' => 'List limit', // @translate
                'info' => $this->translate('Number of individual records that can be returned in a response at once.') // @translate
                    . ' ' . $this->translate('Larger values will increase memory usage but reduce the number of database queries and HTTP requests.') // @translate
                    . ' ' . $this->translate('Smaller values will reduce memory usage but increase the number of DB queries and requests.'), // @translate
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
                'info' => $this->translate('In minutes, the length of time a resumption token is valid for.') // @translate
                    . ' ' . $this->translate('This means harvesters can re-try old partial list requests for this amount of time.') // @translate
                    . ' ' . $this->translate('Larger values will make the tokens table grow somewhat larger.'), // @translate
            ],
            'attributes' => [
                'min' => '1',
            ],
        ]);
    }

    protected function translate($args)
    {
        $translator = $this->getTranslator();
        return $translator->translate($args);
    }

    /**
     * @param array
     */
    public function setOaiSetFormats(array $oaiSetFormats)
    {
        $this->oaiSetFormats = $oaiSetFormats;
    }

    /**
     * @return array
     */
    public function getOaiSetFormats()
    {
        return $this->oaiSetFormats;
    }
}
