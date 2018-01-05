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
    /**
     * @var int
     */
    protected $id;
    /**
     * @var string
     */
    protected $formName;
    /**
     * @var int
     */
    protected $pipelineId;
    /**
     * @var Contact[]|array
     */
    protected $contacts = array();
    /**
     * @var Lead|array
     */
    protected $lead = array();
    /**
     * @var Company[]|array
     */
    protected $companies = array();

    /**
     * Unsorted constructor.
     * @param string $formName
     * @param Contact[] $contacts
     * @param Lead $lead
     * @param int|string $pipelineIdOrName
     * @param Company[] $companies
     */
    public function __construct($formName, $contacts, $lead, $pipelineIdOrName = null, $companies = array())
    {
        $this->contacts = $contacts;
        $this->lead = $lead;
        $this->companies = $companies;
        $this->formName = $formName;
        if (!empty($pipelineIdOrName)) {
            $this->pipelineId = Amo::$info->getPipelineIdFromIdOrName($pipelineIdOrName);
        }
    }

    /**
     * @return bool
     */
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
        $request['add'] = array(
            array(
                'source_name' => 'AmoCRM Wrap by Drill',
                'created_at' => date('U'),
                'pipeline_id' => $this->pipelineId,
                'incoming_entities' => array(
                    'leads' => array($lead),
                    'contacts' => $contacts,
                    'companies' => $companies,
                ),
                'incoming_lead_info' => array(
                    'form_id' => 25,
                    'form_page' => $_SERVER['SERVER_NAME'],
                    'ip' => $_SERVER['REMOTE_ADDR'],
                    'form_name' => $this->formName,
                    'referer' => !empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null,
                ),
            ),
        );
        $response = Amo::cUrl('api/v2/incoming_leads/form', $request);
        if ($response->status == 'success') {
            $this->id = $response->data[0];
            return true;
        }
        return false;
    }
}