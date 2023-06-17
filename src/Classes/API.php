<?php

namespace src\Classes;

use Exception;

class API
{

    /**
     * @var object
     */
    private $data;

    /**
     * @throws Exception
     */
    public function __construct($action, $data, $token)
    {
        $this->checkToken($token);
        DB::init();
        $this->data = $data;
        switch ($action) {
            case "letters":
                $this->actionLetters($data);
                break;
            default:
                $this->anotherActions();
                break;
        }
    }

    private function anotherActions()
    {
        //todo: Добавить обработку для других точек входа
    }

    /**
     * @param object $data
     * @throws Exception
     */
    private function actionLetters(object $data)
    {
        // проверка обязательных полей
        if (
            $data->type &&
            $data->attachments &&
            $data->document &&
            $data->sender &&
            $data->recipient
        ) {
            $this->letterCheckType($data->type);
            $this->letterCheckAttachments($data->attachments);
            $this->letterCheckDocument($data->document);
            $this->letterCheckSender($data->sender);
            $this->letterCheckRecipient($data->recipient);
        } else {
            $this->result(400, 'Не заполнены обязательные поля');
        }
        $this->result(200, false, DB::saveLetter($data));
    }

    private function letterCheckType(string $type)
    {
        if ($type != "REGISTERED" && $type != "REGULAR") {
            $this->result(400, "Не известный тип сообщения");
        }
    }

    private function letterCheckAttachments($attachments)
    {
        if ($attachments->pdf) {
            $id = $attachments->pdf->attachmentId;
            if (!$this->existAttachment($id)) $this->result(400, "Ошибка загрузки файла attachmentId");
            if (isset($attachments->pdf->attachmentSignatureId)) {
                if (!$this->existAttachment($attachments->pdf->attachmentSignatureId)) $this->result(400, "Ошибка загрузки файла attachmentSignatureId");
                $this->letterCheckSign($id, $attachments->pdf->attachmentSignatureId);
            }
        }
        if ($attachments->images) {
            // todo: add images processing
        }
        if ($attachments->onlineDeliveryPdf) {
            // todo: add online processing
        }
        if ($attachments->xml) {
            // todo: add xml processing
        }

    }

    private function letterCheckDocument($document)
    {
        // todo: Проверять не нужно
    }

    private function letterCheckSender($sender)
    {
        if (!$sender->code
            || !$sender->name
            || !$sender->departmentCode
            || !$sender->departmentName
        ) {
            // Отсутствуют обязательные поля
            $this->result(400, 'Отсутствуют обязательные поля у отправителя');
        }
        if (!preg_match('/CODE_(\d+)/is', $sender->code, $m1)
            || !preg_match('/CODE_(\d+)/is', $sender->departmentCode, $m2)) {
            // не верный формат кода
            $this->result(400, "Неверный формат кода отправителя или подразделения");
        }

    }

    private function letterCheckRecipient($recipient)
    {
        if (!$recipient->countryCodeOKSM)
            $this->result(400, 'Отсутствует код страны по ОКСМ');
        if (!$recipient->address && !$recipient->addressStruct)
            $this->result(400, 'Отсутствует адрес получателя');
        if ($recipient->addressStruct) {
            $struct = $recipient->addressStruct;
            if (
                !is_string($struct->city)
                || !is_string($struct->postalCode)
                || !is_string($struct->region)
            ) {
                $this->result(400, 'Отсутствуют одно или несколько обязательных полей (регион, город, индекс)');
            }
        }
        if (!$recipient->person && !$recipient->organization)
            $this->result(400, 'Отсутствует наименование получателя');

        if ($recipient->person) {
            $person = $recipient->person;
            if (
                !is_string($person->firstName)
                || is_string(!$person->lastName)
            ) {
                $this->result(400, 'Не указано имя получателя');
            }
            if ($person->identityDocument) {
                $doc = $person->identityDocument;
                if (
                    !is_string($doc->series)
                    || !is_string($doc->number)
                    || !is_string($doc->issuer)
                    || !$this->checkDate($doc->issueDate)
                ) {
                    $this->result(400, 'Не верно указаны реквизиты документа удостоверяющего личность');
                }
            }
            if ($person->drivingLicence) {
                $doc = $person->drivingLicence;
                if (
                    !is_string($doc->series)
                    || !is_string($doc->number)
                ) {
                    $this->result(400, 'Не верно указаны реквизиты водительского удостоверения');
                }
            }
            if ($person->vehicleRegDoc) {
                $doc = $person->vehicleRegDoc;
                if (
                    !is_string($doc->series)
                    || !is_string($doc->number)
                ) {
                    $this->result(400, 'Не верно указаны реквизиты свидетельства о регистрации транспортного средства');
                }
            }

        }else if ($recipient->organization){
            if(!is_string($recipient->organization->name))
                $this->result(400, 'Не указано название организации');
        }
    }

    private function letterCheckSign($id, $attachmentSignatureId)
    {
        // todo: sign verification;
        if (false) {
            $this->result(400, 'подпись документа не прошла проверку');
        }
    }

    /**
     * @param $name
     * @return bool
     */
    private function existAttachment($name): bool
    {
        $name = str_replace('.', '_', $name);
        if (isset($_FILES[$name])) {
            if ($_FILES[$name]['error'] === 0
                && $_FILES[$name]['size'] > 0
                && file_exists($_FILES[$name]['tmp_name'])
            ) {
                return true;
            }
        }
        return false;
    }

    private function result($code, $message = false, $requestCode = false)
    {
        header("Content-Type: application/json");
        $result = [
            'code' => $code
        ];
        if ($message) {
            $result['message'] = $message;
        }
        if ($requestCode) {
            $result['data']['requestCode'] = $requestCode;
        }
        echo json_encode($result);
        die();
    }

    private function checkDate($date)
    {
        return preg_match("/\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(\.\d{3})*\+\d{2}:\d{2}/is", $date);
    }

    /**
     * @param $token
     * @return true|void
     */
    private function checkToken($token)
    {
        // Сделано только для генерации кода 401 при отправке не расшифровываемого токена
        $auth = base64_decode($token,true);
        if($auth){
            return true;
        } else {
            $this->result(401, "Ошибка проверки токена");
        }
    }
}