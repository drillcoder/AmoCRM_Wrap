# Обёртка для простого взаимодействия с Api AmoCRM

Текущая версия: 6.0

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
    // work with Аmo api
} catch (\DrillCoder\AmoCRM_Wrap\AmoWrapException $e) {
    die($e->getMessage());
}
?>
```

## Возможности
* Создание полного бэкапа црм, даже при отсутствии такой опции в тарифе
* Поиск контакта по телефону или почте. Даный метод можно использовать для поиска дублей.
* Поиск по произвольному запросу среди Контактов, Компаний и Сделок
* Получение списка любых сущностей с различной фильтрации
* Создание, изменение и удаление контактов, сделок, компаний, заметок и задач
* Прикрепление к контактам, сделкам и компаниям файлов, заметок и задач
* Удобная работа с доп полями, без знания их id
* Назначение ответственных без id по имени или части имени пользователя в црм
* На все запросы возвращается не огромный масив с данными, а удобные для работы объекты (Contact, Lead и тп)

## В ближайших плана
* Возможность развёртования бэкапа
* Добавлени возможности работы с новыми сущностями (Покупатели и тп)

## Благодарности
* Владислав Алаторцев — За уникальные методы работы без возможности создания файла для хранения куки, 
прикрепления файлов и удаления сущностей

# Описание методов основных классов

## Класс AmoCRM
* **AmoCRM::VERSION** — Текущая версия обёртки
* **AmoCRM::clearPhone($phone)** — Статичный метод отчистки телефона от лишних символов
* **new AmoCRM($domain, $userLogin, $userHash)** — Создане главного объекта класса AmoCRM. 
Пытается произвести авторизацию.
* **isAuthorization()** — Проверяет текущую авторизацию
* **searchContact($phone, $email)** — Производит поиск по контактам. При этом не учитывает формат телефона (телефоны 
начинающиеся с 7 или 8 считает разными). Возвращяет нумерованый массив из объектов класса Contact, где ключём будет 
являть id контакта
* **searchCompany($query)** — Производит поиск по компаниям по произвольной строке. Возвращяет нумерованый массив из
объектов класса Company, где ключом будет являть id компании
* **searchLead($query)** — Производит поиск по сделкам по произвольной строке. Возвращяет нумерованый массив из 
объектов класса Lead, где ключом будет являть id сделки

Важно!!! Все списки сортируются от самого ранее изменённого. Сортировку поменять нельзя =(
$limit = 0 — Отсутствие ограничения.

Параметры: $query - произвольная строка, $limit - лимит количества объектов в результате, $offset - отступ от начала 
списка, $responsibleUsersIdOrName - один элемент или массив ответственных (принимает как id так и имена или часть имени), 
$modifiedSince - дата и время после которых контакт был изменён, $isRaw - если true то возвращает массив сырых данных, 
а не массив объектов

* **contactsList($query = null, $limit = 0, $offset = 0, $responsibleUsersIdOrName = array(), 
\DateTime $modifiedSince = null, $isRaw = false)** — Производит поиск по контактам и возвращает массив объектов 
класса Contact
* **leadsList(--/так же/--)** — Производит поиск по сделкам и возвращает массив объектов класса Lead
* **companyList(--/так же/--)** — Производит поиск по компаниям и возвращает массив объектов класса Company
* **tasksList(--/так же/--)** — Производит поиск по задачам и возвращает массив объектов класса Task
* **notesContactList(--/так же/--)** — Производит поиск по заметкам у контатов и возвращает массив объектов класса Note
* **notesLeadList(--/так же/--)** — Производит поиск по заметкам у сделок и возвращает массив объектов класса Note
* **notesCompanyList(--/так же/--)** — Производит поиск по заметкам у компаний и возвращает массив объектов класса Note
* **notesTaskList(--/так же/--)** — Производит поиск по заметкам у задач(результат задачи) и возвращает массив объектов 
класса Note

## Абстрактный класс Base

На этом классе базируются все другие основные классы. 
Все методы присутствующие в нём можно использовать в других классах.

* **__construct($id = null)** — Метод вызываемый при создании экземпляра класса. Можно передать id нужной сущность и 
тогда произойдёт загрузка данный из црм. Если не передавать id то будет создан пустой объект
* **save()** — Сохраняет текущую сущность в црм
* **delete()** — Удаляет текущую сущность в црм
* **getId()** — Возвращает id сущность
* **getName()** — Возвращает наименование сущность
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
* **addLead($lead)** — Прикрепляет к сущность сделку
* **delLead($lead)** — Открепляет сделку от сущность
* **getContactsId()** — Возвращает массив из id прикреплённых контактов
* **addContactId($contact)** — Прикрепляет к сущность контакт
* **delContactId($contact)** — Открепляет контакт от сущность
* **getCompanyId()** — Возвращает id компании к которой привязана сущность
* **setCompany($company = null)** — Устанавливает компанию к которой будет привязана сущность
* **getTags()** — Возвращает ассоциативный массив тэгов id => name
* **addTag($tag)** — Добавляет тэг к сущности
* **delTag($tag)** — Удаляет тэг у сущности
* **getPhones()** — Возвращает нумерованый массив телефонов
* **addPhone($phone, $enum = 'OTHER')** — Добавляет телефон к сущности, не обязательны пораметр тип телефона, 
по умолчанию "Другой". Проверяет имеется ли уже такой телефон у сущности и не добавляет дубликат

Возможные варианты: WORK - Рабочий, WORKDD - Прямой, MOB - Мобильный, FAX - Факс, HOME - Домашний, OTHER - Другой
* **delPhone($phone)** — Удаляет телфон у сущности. Не учитывает формат телефона. Учитывает начинается телефон с 7 или 8
* **getEmails()** — Возвращает нумерованый массив email'ов
* **addEmails($email, $enum = 'OTHER')** — Добавляет почту к сущности, не обязательны пораметр тип почты, по умолчанию 
"Другой". Проверяет имеется ли уже такая почта у контакт и не добавляет дубликат

Возможные варианты: WORK - Рабочая, PRIV - Личная, OTHER - Другая
* **delEmail($email)** — Удаляет почту у сущности
* **addCustomField($name, $type)** — Создаёт кастомное поле у текущей сущности с именем $name и типом $type.

Типы:
1. Обыное текстовое поле
2. Текстовое поля с возможностью записывать только цифры
3. Поле обозначающее только наличие или отсутствие свойства (например: "да"/"нет")
4. Поле типа список с возможностью выбора одного элемента
5. Поле типа список c возможностью выбора нескольких элементов списка
6. Поле типа дата в формате (Год-Мес-День Час:Мин:Сек)
7. Обычное текстовое поле предназначенное URL адресов
8. и 9. Поле содержащее большое количество текста
10. Поле типа переключатель
11. Поле короткой записи адреса
12. Поле адрес (в интерфейсе является набором из нескольких полей)
13. Поле типа дата поиск по которому осуществляется без учета года т.е. день рождение. 
Формат (Год-Мес-День Час:Мин:Сек). Возвращает id поля
* **delCustomField($nameOrId)** — Удаляет кастомное поле у текущей сущности, принимает как id поля так и его имя
* **getCustomFieldValue($nameOrId)** — Возвращает значение кастомного поля в виде строки. Принимает его имя или id. 
Если значений несколько, то они должны перечисляться через точку с запятоу (;)
* **getCustomFieldsValue()** — Возвращает все значения кастомных полей в виде ассоциативного массива 
(имя поля => его значение). Если у поля несколько значений, то они перечесляются через точку с запятоу (;)
* **setCustomField($customFieldNameOrId, $value = null)** — Задаёт значение кастомного поля. Первым аргументов можно 
передать как id поля, так и его название в црм. Если не передавать значение ($value) или задать его пустым, то при 
сохранении поле в црм так же будет очищено. В поле типа мультисписок значения вносятся с разделителем точка с 
запятоу (;) т.е. "первое; второе; третье"
* **addNote($text)** — Добавляет примичание для текущей сущности
* **addSystemNote($text, $serviceName)** — Добавляет системное(нестираемое) примечание для текущей сущности
* **addTask($text, $responsibleUserIdOrName = null, $completeTill, $typeId = 3)** — Добавляет задачу для текущей 
сущности. $text - текст задачи. $responsibleUserIdOrName(не обязательный) - ответственный, принимает как id так и имя, 
если не установлен то ответственный тот же что и у текущей сущность. $completeTill(не обязательный) - дата и время, в 
виде объекта класса DateTime, до которого нужно завершить задачу, если не указан то время устанавливается текущее. 
$typeId(не обязательный) - тип задачи см. варианты в црм, принимает только имя типа, если не установлен принимает 
тип 3 - письмо.
* **addFile($pathToFile)** — прикрепляет файл к сущность (Сделка, Контакт или Компания). $pathToFile - путь до файла
* **backup($directory)** — Создаёт полный бэкап всей црм в директории $directory. Возможны проблемы с лимитом памяти

## Классы Contact и Company

Все методы базовые


## Класс Lead

* **getSale()** — Возвращает бюджет
* **setSale($sale)** — Задаёт бюджет
* **getPipelineId()** — Возвращает id воронки
* **getPipelineName()** — Возвращает название воронки
* **getStatusId()** — Возвращает id статуса
* **getStatusName()** — Возвращает название статуса
* **setPipelineAndStatus($idOrNamePipeline, $idOrNameStatus)** — Задаёт воронку и статус. Принимает id либо название 
из црм, как воронки так и статуса
* **getMainContactId()** — Возвращает id основного контакта
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


## Классы хэлперы Config, Info, CustomField, Value, Note и Task
Созданы как вспомогательные по этому описывать их не буду)

# Примеры

## Поиск, изменение и сохранение контакта в црм
```php
<?php
try {
    $amo = new \DrillCoder\AmoCRM_Wrap\AmoCRM('test', 'test@test.ru', '011c2d7f862c688286b43ef552fb17f4');
    $contacts =  $amo->searchContact('79998887766', 'test@test.ru'); //Ищем контакт по телефону и почте
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
         ->setCustomField('roistat', isset($_COOKIE['roistat_visit']) ? $_COOKIE['roistat_visit'] : null)
         ->setCustomField('roistat-marker', isset($_COOKIE['roistat_marker']) ? $_COOKIE['roistat_marker'] : 'Прямой визит')
         ->setCustomField('Форма захвата', $form)
         ->setCustomField('utm_source', $_COOKIE['utm_source'])
         ->setCustomField('utm_medium', $_COOKIE['utm_medium'])
         ->setCustomField('utm_campaign', $_COOKIE['utm_campaign'])
         ->setCustomField('utm_term', $_COOKIE['utm_term'])
         ->setCustomField('utm_content', $_COOKIE['utm_content'])
         ->setResponsibleUser($responsibleUserId)
         ->save()
         ->addNote($comment);
    $contacts = $amo->searchContact($phone, $email);
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