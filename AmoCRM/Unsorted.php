<?php
/**
 * Created by PhpStorm.
 * User: drillphoto
 * Date: 09.10.2017
 * Time: 12:51
 */

namespace AmoCRM;

/**
 * Class Unsorted
 * @package AmoCRM
 */
class Unsorted
{
    private $id;
    private $formName;
    private $contacts = array();
    private $lead = array();
    private $companies = array();

    /**
     * Unsorted constructor.
     * @param string $formName
     * @param Contact[] $contacts
     * @param Lead $lead
     * @param Company[] $companies
     */
    public function __construct($formName, $contacts, $lead, $companies = array())
    {
        $this->contacts = $contacts;
        $this->lead = $lead;
        $this->companies = $companies;
        $this->formName = $formName;
    }

    public function save()
    {
        $lead = $this->lead->getRaw();
        $contacts = array();
        /** @var Contact $firstContact */
        $firstContact = current($this->contacts);
        $nameFirstContact = $firstContact->getName();
        $phoneFirstContact = null;
        $emailFirstContact = null;
        if (!empty($firstContact->getPhones())) {
            $phoneFirstContact = $firstContact->getPhones()[0];
        }
        if (!empty($firstContact->getEmails())) {
            $emailFirstContact = $firstContact->getEmails()[0];
        }
        $companies = array();
        foreach ($this->contacts as $contact) {
            $contacts[] = $contact->getRaw();
        }
        foreach ($this->companies as $company) {
            $companies[] = $company->getRaw();
        }
        $data = array();
        if ($nameFirstContact) {
            $data['name'] = array(
                'type' => 'text',
                'id' => 'name',
                'element_type' => 1,
                'name' => 'Имя',
                'value' => $nameFirstContact
            );
        }
        if ($phoneFirstContact) {
            $data['phone'] = array(
                'type' => 'text',
                'id' => 'phone',
                'element_type' => 1,
                'name' => 'Телефон',
                'value' => $phoneFirstContact
            );
        }
        if ($emailFirstContact) {
            $data['email'] = array(
                'type' => 'text',
                'id' => 'email',
                'element_type' => 1,
                'name' => 'Email',
                'value' => $emailFirstContact
            );
        }
        $referer = null;
        if (!empty($_SERVER['HTTP_REFERER'])) {
            $referer = $_SERVER['HTTP_REFERER'];
        }
        $request['request']['unsorted'] = array(
            'category' => 'forms',
            'add' => array(
                array(
                    'source' => 'AmoCRM Wrap by Drill',
                    'source_data' => array(
                        'data' => $data,
                        'form_id' => 25,
                        'form_type' => 1,
                        'origin' => array(
                            'referer' => $referer
                        ),
                        'date' => date('U'),
                        'from' => $_SERVER['SERVER_NAME'],
                        'form_name' => $this->formName
                    ),
                    'data' => array(
                        'contacts' => $contacts,
                        'leads' => array($lead),
                        'companies' => $companies,
                    )
                )
            ),
        );
        $response = Amo::cUrl('api/unsorted/add', 'post', $request);
        if ($response->unsorted->add->status == 'success') {
            $this->id = $response->unsorted->add->data[0];
            return true;
        }
        return false;
    }
}