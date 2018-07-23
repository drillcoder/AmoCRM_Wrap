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
class Unsorted
{
    /**
     *
     */
    const CATEGORIES_SIP = 'sip';
    /**
     *
     */
    const CATEGORIES_MAIL = 'mail';
    /**
     *
     */
    const CATEGORIES_FORM = 'forms';
    /**
     *
     */
    const CATEGORIES_CHAT = 'chat';
    /**
     *
     */
    const ORDER_BY_ASC = 'asc';
    /**
     *
     */
    const ORDER_BY_DESC = 'desc';
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
     * @var Note[]
     */
    protected $notes;

    /**
     * Unsorted constructor.
     * @param string $formName
     * @param Contact[] $contacts
     * @param Lead $lead
     * @param int|string $pipelineIdOrName
     * @param Company[] $companies
     * @throws AmoWrapException
     */
    public function __construct($formName, $lead, $contacts = array(), $pipelineIdOrName = null, $companies = array())
    {
        $this->contacts = $contacts;
        $this->lead = $lead;
        $this->companies = $companies;
        $this->formName = $formName;
        if (!empty($pipelineIdOrName)) {
            $this->pipelineId = AmoCRM::getInfo()->getPipelineIdFromIdOrName($pipelineIdOrName);
        }
    }

    /**
     * @return Unsorted
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
                        'form_page' => $this->formName,
                    ),
                ),
            );
            $response = AmoCRM::cUrl('api/v2/incoming_leads/form', $request);
            if ($response->status == 'success') {
                $this->id = $response->data[0];
                return $this;
            }
        }
        throw new AmoWrapException('Не удалось сохранить заявку в неразобранное');
    }

    /**
     * @param string $text
     * @param int $type
     * @return Unsorted
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