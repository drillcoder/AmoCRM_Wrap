<?php
/**
 * Created by PhpStorm.
 * User: DrillCoder
 * Date: 09.10.2017
 * Time: 12:51
 */

namespace DrillCoder\AmoCRM_Wrap;


/**
 * Class Unsorted
 * @package DrillCoder\AmoCRM_Wrap
 */
class Unsorted extends Base
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $formName;

    /**
     * @var int
     */
    private $pipelineId;

    /**
     * @var Contact[]|array
     */
    private $contacts = array();

    /**
     * @var Lead|array
     */
    private $lead = array();

    /**
     * @var Company[]|array
     */
    private $companies = array();

    /**
     * @var Note[]
     */
    private $notes;

    /**
     * @param string          $formName
     * @param Contact[]       $contacts
     * @param Lead            $lead
     * @param int|string|null $pipeline
     * @param Company[]       $companies
     *
     * @throws AmoWrapException
     */
    public function __construct($formName, $lead, $contacts = array(), $pipeline = null, $companies = array())
    {
        if (!AmoCRM::isAuthorization()) {
            throw new AmoWrapException('Требуется авторизация');
        }

        $this->contacts = $contacts;
        $this->lead = $lead;
        $this->companies = $companies;
        $this->formName = $formName;
        if ($pipeline !== null) {
            $this->pipelineId = AmoCRM::searchPipelineId($pipeline);
        }
    }

    /**
     * @return Unsorted
     *
     * @throws AmoWrapException
     */
    public function save()
    {
        if (!empty($this->lead) || !empty($this->contacts)) {
            $lead = null;
            if (!empty($this->lead)) {
                $lead = $this->lead->getRaw();
                if (!empty($this->notes)) {
                    foreach ($this->notes as $note) {
                        $lead['notes'][] = $note->getRaw();
                    }
                }
            }
            $contacts = array();
            foreach ($this->contacts as $contact) {
                $contacts[] = $contact->getRaw();
            }
            $companies = array();
            foreach ($this->companies as $company) {
                $companies[] = $company->getRaw();
            }
            $request['add'] = array(
                array(
                    'source_name' => 'DrillCoder AmoCRM Wrap',
                    'created_at' => date('U'),
                    'pipeline_id' => $this->pipelineId,
                    'incoming_entities' => array(
                        'leads' => array($lead),
                        'contacts' => $contacts,
                        'companies' => $companies,
                    ),
                    'incoming_lead_info' => array(
                        'form_id' => 25,
                        'form_page' => $this->formName,
                    ),
                ),
            );
            $response = AmoCRM::cUrl('api/v2/incoming_leads/form', $request);
            if ($response !== null && $response->status === 'success') {
                $this->id = $response->data[0];
                return $this;
            }
        }
        throw new AmoWrapException('Не удалось сохранить заявку в неразобранное');
    }

    /**
     * @param string $text
     * @param int    $type
     *
     * @return Unsorted
     *
     * @throws AmoWrapException
     */
    public function addNote($text, $type = 4)
    {
        $note = new Note();
        $note->setText($text)
            ->setType($type)
            ->setElementType('lead');
        $this->notes[] = $note;
        return $this;
    }
}