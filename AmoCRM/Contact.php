<?php
/**
 * Created by PhpStorm.
 * User: drillphoto
 * Date: 08.09.17
 * Time: 9:58
 */

namespace AmoCRM;

/**
 * Class Contact
 * @package AmoCRM
 */
class Contact extends Base
{
    /**
     * @return bool
     */
    public function save()
    {
        return Base::saveBase();
    }

    /**
     * @return array
     */
    public function getRaw()
    {
        return Base::getRawBase();
    }

    /**
     * @return bool
     */
    public function delete()
    {
        if (parent::delete()) {
            foreach ($this as $key => $item) {
                $this->$key = null;
            }
            return true;
        }
        return false;
    }

    /**
     * @param string $text
     * @param int $type
     * @return bool
     */
    public function addNote($text, $type = 4)
    {
        if (empty($this->id))
            $this->save();
        return parent::addNote($text, $type);
    }

    /**
     * @param string $text
     * @param \DateTime|null $completeTill
     * @param int|string $typeId
     * @param int|string|null $responsibleUserIdOrName
     * @return bool
     */
    public function addTask($text, $responsibleUserIdOrName = null, $completeTill = null, $typeId = 3)
    {
        if (empty($this->id))
            $this->save();
        return parent::addTask($text, $responsibleUserIdOrName, $completeTill, $typeId);
    }

    /**
     * @param string $pathToFile
     * @return bool
     */
    public function addFile($pathToFile)
    {
        if (empty($this->id))
            $this->save();
        return parent::addFile($pathToFile);
    }
}