# Обёртка для простого взаимодействия с Api AmoCRM

[![Build Status](https://scrutinizer-ci.com/g/drillcoder/AmoCRM_Wrap/badges/build.png?b=master)](https://scrutinizer-ci.com/g/drillcoder/AmoCRM_Wrap/build-status/master)
[![Latest Stable Version](https://poser.pugx.org/drillcoder/amocrm_wrap/v/stable)](https://packagist.org/packages/drillcoder/amocrm_wrap)
[![Total Downloads](https://poser.pugx.org/drillcoder/amocrm_wrap/downloads)](https://packagist.org/packages/drillcoder/amocrm_wrap)
[![License](https://poser.pugx.org/drillcoder/amocrm_wrap/license)](https://packagist.org/packages/drillcoder/amocrm_wrap)

## Подключение

Перед использованием обёртки, нужно подключить файл автозагрузки классов 'autoload.php'. 
Путь к файлу конечно же может отличаться от примера ниже. Или вы можете включать этот компонент в свой автозагрузчик.
```php
<?php require_once 'AmoCRM_Wrap/autoload.php'; ?>
```
## Использование
Для начала нужно произвести авторизацию. Для этого создаётся экземпляр класса AmoCRM в конструктор которого необходимо 
передать:

1. Субдомен црм
2. Логин юзера(почта)
3. API ключ пользователя. Его вы можете найти в интерфейсе црм https://test.amocrm.ru/settings/profile/ 
- 'test' нужно заменить на свой поддомен

Так как в обёртке предусмотрены Исключения (Exception) которые вызываются в случаях всевозможных ошибок, то их 
необходимо олавливать т.е. весь код работы с обёрткой следует помещать в try catch.

Пример авторизации:

```php
<?php
try {
    $amo = new \DrillCoder\AmoCRM_Wrap\AmoCRM('test', 'test@test.ru', '011c2d7f862c688286b43ef552fb17f4');
    // ...
} catch (\DrillCoder\AmoCRM_Wrap\AmoWrapException $e) {
    die($e->getMessage());
}
?>
```

## Возможности
* Создание полного бэкапа црм, даже при отсутствии такой опции в тарифе
* Поиск контакта по телефону или почте. Даный метод можно использовать для поиска дублей
* Поиск по произвольному запросу среди Контактов, Компаний, Сделок, Примечаний и Задач
* Получение списка любых сущностей с различной фильтрацией
* Создание, изменение и удаление контактов, сделок, компаний, заметок и задач
* Прикрепление к контактам, сделкам и компаниям файлов, заметок и задач
* Удобная работа с доп полями, без знания их id
* Назначение ответственных без id по имени или части имени пользователя в црм
* На все запросы возвращается не огромный масив с данными, а удобные для работы объекты (Contact, Lead и тп)

## В ближайших плана
* Возможность развёртования бэкапа
* Добавлени возможности работы с новыми сущностями (Покупатели и тп)
* Добавление валидации к заданиям значений в пользовательским полям

## Благодарности
* Владислав Алаторцев — За уникальные методы прикрепления файлов и удаления сущностей а так же работе без возможности 
создания файла для хранения куки
* Роман Маслеников — За поиски и исправление багов

# Описание методов основных классов

## Класс AmoCRM
* **AmoCRM::VERSION** — Текущая версия обёртки
* **AmoCRM::getPhoneFieldId()** — Возвращает id поля телефона у сущности Контакт
* **AmoCRM::getEmailFieldId()** — Возвращает id поля почты у сущности Контакт
* **AmoCRM::getUsers()** — Возвращает массив пользователей в црм
* **AmoCRM::getTaskTypes()** — Возвращает массив типов задач
* **AmoCRM::getPipelinesName()** — Возвращает массив воронок в црм
* **AmoCRM::getStatusesName($pipelineId)** — Возвращает массив статусов в указанной воронке (нужен id воронки)
* **AmoCRM::getCustomFields($type)** — Возвращает массив пользовательских полей у заданного типа сущности.
Возможные типы: contact, lead, company
* **AmoCRM::getCustomFieldsEnums($type)** — Возвращает массив enums пользовательских полей у заданного типа сущности
* **searchContactsByPhoneAndEmail($phone, $email)** — Поиск по контактам без учета формата телефона.
Телефоны начинающиеся с 7 или 8 считает разными. Возвращяет массив из объектов Contact, где ключи id контактов

Поиск по Контактам, Компаниям, Сделкам, Задачам и Примечаниям ко всем сущностям отдельно
* searchContacts/searchCompanies/searchLeads/searchTasks
* getContactNotes/getCompanyNotes/getLeadNotes/getTaskNotes
**($query = null, $limit = 0,$offset = 0, $responsibleUsersIdOrName = array(), DateTime $modifiedSince = null, 
$isRaw = false)** — Поиск по заданным сущностям. Все поля не обязательны. Если ничего не задано, то вернется полный 
список всех сущностей.
$query - текст запроса.
$limit - количество сущностей в ответе. 0 = без ограничения
$offset - пропуск количества сущностей от начала
$responsibleUsersIdOrName - ответственные юзеры (имя или id). Если несколько то в массиве
$modifiedSince - измененный после даты и время заданным объектом DateTime
$isRaw - флаг получить "сырые" данные. Массивом как при обычном запросе к апи

Важно!!! Все списки сортируются от самого ранее изменённого. Сортировку поменять нельзя =(

* **backup($directory)** — Создаёт полный бэкап всей црм в директории $directory. Возможны проблемы с лимитом памяти

## Абстрактный класс BaseEntity

На этом классе базируются все другие основные классы. 
Все методы присутствующие в нём можно использовать в других классах.

* **__construct($id = null)** — Метод вызываемый при создании экземпляра класса. Можно передать id нужной сущность и 
тогда произойдёт загрузка данный из црм. Если не передавать id то будет создан пустой объект
* **getRaw()** — Получить сырые данные на основе сущности
* **save()** — Сохраняет текущую сущность в црм
* **delete()** — Удаляет текущую сущность в црм
* **getId()** — Возвращает id сущность
* **getName()** — Возвращает имя сущности
* **setName($name)** — Изменяет имя на $name
* **getResponsibleUserId()** — Возвращает id ответственного за сущность
* **getResponsibleUserName()** — Возвращает имя ответственного за сущность
* **setResponsibleUser($responsibleUserIdOrName)** — Устанавливает ответственного за сущность. Принимает либо id 
ответственного либо его имя (ищет эту строку во всех именах всех менеджеров црм)
* **getDateCreate()** — Возвращает объект DateTime с датой и временем создания сущность
* **getDateUpdate()** — Возвращает объект DateTime с датой и временем последнего изменения сущность
* **getUserIdUpdate()** — Возвращает id человека который последний раз изменял сущность
* **getUserNameUpdate()** — Возвращает имя человека который последний раз изменял сущность
* **getCreatedUserId()** — Возвращает id человека создавшего сущность
* **getCreatedUserName()** — Возвращает имя человека создавшего сущность
* **getLeadsId()** — Возвращает массив из id прикреплённых сделок
* **getLeads()** — Возвращает массив объектов Lead прикреплённых сделок
* **addLead($lead)** — Прикрепляет к сущность сделку
* **delLead($lead)** — Открепляет сделку от сущность
* **getContactsId()** — Возвращает массив из id прикреплённых контактов
* **getContacts()** — Возвращает массив объектов Contact прикреплённых контактов
* **addContactId($contact)** — Прикрепляет к сущность контакт
* **delContactId($contact)** — Открепляет контакт от сущность
* **getCompanyId()** — Возвращает id компании к которой привязана сущность
* **getCompany()** — Возвращает объект Company компании к которой привязана сущность
* **setCompany($company)** — Устанавливает компанию к которой будет привязана сущность
* **getTags()** — Возвращает ассоциативный массив тэгов id => name
* **addTag($tag)** — Добавляет тэг к сущности
* **delTag($tag)** — Удаляет тэг у сущности
* **getPhones()** — Возвращает нумерованый массив телефонов
* **addPhone($phone, $enum = CustomField::PHONE_OTHER)** — Добавляет телефон к сущности, не обязательны пораметр тип 
телефона, по умолчанию "Другой". Проверяет имеется ли уже такой телефон у сущности и не добавляет дубликат. Возможные 
варианты типов телефона находятся в константах CustomField::PHONE__
* **delPhone($phone)** — Удаляет телефон у сущности. Не учитывает формат телефона. Учитывает начинается телефон с 7 или 8
* **getEmails()** — Возвращает нумерованый массив email'ов
* **addEmails($email, $enum = CustomField::EMAIL_OTHER)** — Добавляет почту к сущности, не обязательны пораметр тип 
почты, по умолчанию "Другой". Проверяет имеется ли уже такая почта у контакт и не добавляет дубликат. Возможные варианты 
типов почт находятся в константах CustomField::Email_
* **delEmail($email)** — Удаляет почту у сущности
* **addCustomField($name, $type = CustomField::TYPE_TEXT, $enums = array())** — Создаёт кастомное поле у текущего типа 
сущности с именем $name типом $type и если необходимо вариантами $enums. Возвращает id созданого поля. Типы полей 
храняться в CustomField::TYPE_
* **delCustomField($nameOrId)** — Удаляет кастомное поле у текущего типа сущности, принимает как id поля так и его имя
* **getCustomFieldValueInStr($nameOrId)** — Возвращает значение кастомного поля в виде строки. Принимает его имя или id. 
Разделитель значений точка с запятоу (;)
* **getCustomFieldValueInArray($nameOrId)** — Возвращает значение кастомного поля в виде массива. Принимает его имя или id
* **setCustomFieldValue($customFieldNameOrId, $value, $subtype = null)** — Задаёт значение кастомного поля. 
Первым аргументов можно передать как id поля, так и его название в црм. Если не передавать значение ($value) или задать 
его пустым, то при сохранении поле в црм так же будет очищено. В поле типа мультисписок значения вносятся массивом. Для 
типа адрес так же требуеться $subtype типы которого храняться в Value::SUBTYPE_
* **addNote($text)** — Добавляет примичание для текущей сущности
* **addNoteSystem($text, $serviceName)** — Добавляет системное(не удаляемое) примечание для текущей сущности
* **addNoteSmsOut($text, $phone)** — Добавляет примечание для текущей сущности типа Исходящее смс (на номер $phone)
* **addNoteSmsIn($text, $phone)** — Добавляет примечание для текущей сущности типа Входящее смс (с номера $phone)
* **addTask($text, $responsibleUserIdOrName = null, DateTime $completeTill = null, $typeId = 3)** — Добавляет задачу для текущей 
сущности. $text - текст задачи. $responsibleUserIdOrName(не обязательный) - ответственный, принимает как id так и имя, 
если не установлен то ответственный тот же что и у текущей сущность. $completeTill(не обязательный) - дата и время, в 
виде объекта класса DateTime, до которого нужно завершить задачу, если не указан то время устанавливается текущее. 
$typeId(не обязательный) - тип задачи см. варианты в црм, если не установлен принимает тип 3 - письмо.
* **addFile($pathToFile)** — прикрепляет файл к сущность (Сделка, Контакт или Компания). $pathToFile - путь до файла

## Классы Contact и Company

Все методы базовые


## Класс Lead

* **getSale()** — Возвращает бюджет
* **setSale($sale)** — Задаёт бюджет
* **getPipelineId()** — Возвращает id воронки
* **getPipelineName()** — Возвращает название воронки
* **getStatusId()** — Возвращает id статуса
* **getStatusName()** — Возвращает название статуса
* **setPipeline($idOrNamePipeline)** — Задаёт воронку. Принимает id либо название 
* **setStatus($idOrNameStatus, $idOrNamePipeline = null)** — Задаёт статус в воронке $idOrNamePipeline, если не указано 
в текущей. Принимает id либо название 
из црм, как воронки так и статуса
* **getMainContactId()** — Возвращает id основного контакта
* **getMainContact()** — Возвращает объект Contact основного контакта
* **setMainContact($contact)** — Задаёт основной контакт
* **isClosed()** — Возвращает true если сделка закрыта и false в противном случае

## Класс Note и Task

* **getElementId()** — Возвращает id сущности к которой будет привязана заметка или задача
* **setElementId($elementId)** — Задаёт id сущности к которой будет привязана заметка или задача
* **getElementType()** — Возвращает тип сущности к которой будет привязана заметка или задача

Возможные значения: 1 - Контакт, 2 - Сделка, 3 - Компания, 4 - Результат задачи
* **setElementType($elementType)** — Устанавливает тип сущности к которой будет привязана заметка или задача. 
Воможные варианты таке же как выше
* **getType()** — Возвращает тип задачи или заметки
* **setType($type)** — Задаёт тип задачи или заметки. Варианты см. в црм
* **getText()** — Возвращает текст задачи или заметки
* **setText($text)** — Задаёт текст задачи или заметки

## Класс Unsorted

* **new Unsorted($formName, $lead, $contacts, $pipelineIdOrName = null, $companies = array())** — Создание объекта 
$formName - название формы которое будет отображаться в интерфейсе црм. 
$lead - сделка котороя будет создана после принятия заявки в "неразобранном". 
$contacts - массив объектов Contact которые будут созданы и привязаны к сделки после принятия заявки в "неразобранном". 
$pipelineIdOrName - Воронка в которой будет создана заявка, необязательный параметр. 
$companies - необязательный параметр, массив объектов Company которые будут созданы и привязаны к сделки после 
принятия заявки в "неразобранном"
* **addNote($text)** — Добавляет текстовую заметку для сделки. Необходимо использовать перед сохранением
* **save()** — Сохраняет "неразобранное" в црм


## Классы хэлперы Config, CustomField, Value, Note и Task
Созданы как вспомогательные по этому описывать их не буду)

# Примеры

## Поиск, изменение и сохранение контакта в црм
```php
<?php
try {
    $amo = new \DrillCoder\AmoCRM_Wrap\AmoCRM('test', 'test@test.ru', '011c2d7f862c688286b43ef552fb17f4');
    $contacts =  $amo->searchContactsByPhoneAndEmail('79998887766', 'test@test.ru'); //Ищем контакт по телефону и почте
    $contact = current($contacts); //Берём первый найденый контакт
    $contact->setName("{$contact->getName()} лучший") //Меняем имя дописывая в текущее строчку
            ->addPhone('78889998887766', 'MOB') //Добавляем мобильный телефон
            ->addEmail('test2@test.ru', 'WORK') //Добавляем рабочую почту
            ->delEmail('test@test.ru') //Удаляем почту
            ->setResponsibleUser('Пётр Иванович') //Меняем ответственного
            ->save() //Сохраняем все изменение на сервере црм
            ->addTask('Позвонить клиенту', 'Саша'); //Прикрепляем задачку, и назначаем ответственным за неё Сашу
} catch (\DrillCoder\AmoCRM_Wrap\AmoWrapException $e) {
    die($e->getMessage()); //Прерывем работу скрипта и выводим текст ошибки
}
?>
```

## Создание сделки с примечанием и двумя привязанными контактами в "Неразобранном" в вороке "Вторые продажи"
```php
<?php
try {
    $amo = new \DrillCoder\AmoCRM_Wrap\AmoCRM('test', 'test@test.ru', '011c2d7f862c688286b43ef552fb17f4');
    $contact = new \DrillCoder\AmoCRM_Wrap\Contact();
    $contact->setName('Петя')
            ->addPhone(79998887766); //Создаём контакт, который будет создан в црм после принятия заявки в неразобранном
    $contact2 = new \DrillCoder\AmoCRM_Wrap\Contact();
    $contact2->setName('Ваня')
             ->addPhone(79998887755); //Создаём второй контакт
    $lead = new \DrillCoder\AmoCRM_Wrap\Lead();
    $lead->setName('Тестовая сделка')
         ->setSale(2500); //Создаём сделку, которая будет создана в црм после принятия заявки в неразобранном
    $unsorted = new \DrillCoder\AmoCRM_Wrap\Unsorted('Супер-Форма', $lead, array($contact, $contact2), 'Вторые продажи');
    $unsorted->addNote('Клиент сложный')
             ->save(); // Сохраняем всё в неразобранное в црм
} catch (\DrillCoder\AmoCRM_Wrap\AmoWrapException $e) {
    die($e->getMessage()); //Прерывем работу скрипта и выводим текст ошибки
}
?>
```

## Реализация стандартной логики интеграции Ройстат и AmoCRM

Плюсом небольшая фишка с дозаполнением данными контакта

```php
<?php
//Тестовые данные
$form = 'Заказать звонок';
$name = 'Тест';
$phone = '+7(999)888-77-66';
$email = 'testik@test.ru';
$responsibleUserId = 'Александр';
$comment = 'Срочно перезвонить!';

try {
    $amo = new \DrillCoder\AmoCRM_Wrap\AmoCRM('test', 'test@test.com', '8a66666666b3494179da07abc74bfd49');
    $lead = new \DrillCoder\AmoCRM_Wrap\Lead();
    $lead->setName("Заявка с формы '$form'")
         ->setCustomFieldValue('roistat', isset($_COOKIE['roistat_visit']) ? $_COOKIE['roistat_visit'] : null)
         ->setCustomFieldValue('roistat-marker', isset($_COOKIE['roistat_marker']) ? $_COOKIE['roistat_marker'] : 'Прямой визит')
         ->setCustomFieldValue('Форма захвата', $form)
         ->setCustomFieldValue('utm_source', $_COOKIE['utm_source'])
         ->setCustomFieldValue('utm_medium', $_COOKIE['utm_medium'])
         ->setCustomFieldValue('utm_campaign', $_COOKIE['utm_campaign'])
         ->setCustomFieldValue('utm_term', $_COOKIE['utm_term'])
         ->setCustomFieldValue('utm_content', $_COOKIE['utm_content'])
         ->setResponsibleUser($responsibleUserId)
         ->save()
         ->addNote($comment);
    $contacts = $amo->searchContactsByPhoneAndEmail($phone, $email);
    if (!empty($contacts)) {
        $contact = current($contacts);
    } else {
        $contact = new \DrillCoder\AmoCRM_Wrap\Contact();
        $contact->setName($name);
    }
    $contact->addPhone($phone)
            ->addEmail($email)
            ->addLead($lead)
            ->save();
} catch (\DrillCoder\AmoCRM_Wrap\AmoWrapException $e) {
    echo $e->getMessage();
}?>
```