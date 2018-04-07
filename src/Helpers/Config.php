<?php
/**
 * Created by PhpStorm.
 * User: DrillCoder
 * Date: 07.04.2018
 * Time: 14:39
 */

namespace DrillCoder\AmoCRM_Wrap\Helpers;


/**
 * Class Config
 * @package AmoCRM\Helpers
 */
class Config
{
    /**
     * @var array
     */
    public $company = array(
        'elementType' => 3,
        'info' => 'Company',
        'url' => 'company',
        'delete' => 'companies',
    );
    /**
     * @var array
     */
    public $contact = array(
        'elementType' => 1,
        'info' => 'Contact',
        'url' => 'contacts',
        'delete' => 'contacts',
    );
    /**
     * @var array
     */
    public $lead = array(
        'elementType' => 2,
        'info' => 'Lead',
        'url' => 'leads',
        'delete' => 'leads',
    );
    /**
     * @var array
     */
    public $note = array(
        'elementType' => null,
        'info' => 'Note',
        'url' => 'notes',
        'delete' => 'notes',
    );
    /**
     * @var array
     */
    public $task = array(
        'elementType' => 4,
        'info' => null,
        'url' => 'tasks',
        'delete' => 'tasks',
    );
}