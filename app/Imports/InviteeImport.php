<?php


namespace App\Imports;


use App\Models\EventMainer;
use App\Models\Invitee;
use App\Models\SendingMethod;
use App\Services\HonorificService;
use App\Services\InviteeGroupService;
use App\Services\InviteeService;
use App\Services\UserService;
use Illuminate\Support\Facades\Auth;

class InviteeImport extends BaseImport
{
    public $service;
    public $inviteeGroupService;
    public $userService;
    public $honorificService;

    private $_groups = [];
    private $_eventMainers = [];
    private $_honorifics = [];
    private $_eventMainerId;
    private $_groomGroupLastOrder;
    private $_brideGroupLastOrder;
    private $_order;

    /**
     * constants have been defined by csv file
     */
    const BRIDE = '新婦側';
    const GROOM = '新郎側';

    const GENDER_MALE = '男';
    const GENDER_FEMALE = '女';

    const STATUS_CONFIRMING = '確認中';
    const STATUS_NOT_ATTEND = '欠席';
    const STATUS_ATTEND = '出席';

    const IS_CHILD = 'Yes';

    public function initialize()
    {
        $this->service = new InviteeService();
        $this->inviteeGroupService = new InviteeGroupService();
        $this->userService = new UserService();
        $this->honorificService = new HonorificService();
        $this->chunkItem = 1;
        $this->_setGroups();
        $this->_setEventMainers();
        $this->_setHonorifics();
        $this->_setLastInviteeGroup();
    }

    public function model(array $row)
    {
        try {

            $data = [
                'last_name_kanji' => $this->_formatString($row[0]),
                'first_name_kanji' => $this->_formatString($row[1]),
                'relationship' => $this->_formatString($row[2]),
                'last_name_kana' => $this->_formatString($row[3]),
                'first_name_kana' => $this->_formatString($row[4]),
                'last_name_romaji' => $this->_formatString($row[5]),
                'first_name_romaji' => $this->_formatString($row[6]),
                'honorific_id' => $this->_getHonorificId($row[7]), // honorific
                //'event_mainer_id' => $row[8],
                'postal_code' => $row[9],
                'first_address' => $this->_formatString($row[10]),
                'second_address' => $this->_formatString($row[11]),
                'phone' => $this->_formatString($row[12], ''),
                'landline' =>$this->_formatString($row[11], ''),
                'note' => $row[14],
                'status' => $this->_getStatus($row[15]),
                'gender' => $this->_getGender($row[16]),
                'email' => $row[17],
                'invitee_group_id' => $this->_getInviteeGroupId(trim($this->_formatString($row[18])), $row[8]), // params group name, bride or groom
                'age' => $this->_getAge($row[19]),
                'child' => $this->_isChild($row[20]),
            ];
            $data['sending_method_id'] = SendingMethod::BY_MAIL_COME_HOME;
            $data['order'] = $this->_order; // set last order
            return new Invitee($data);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @return string[]
     */
    public function customValidationAttributes()
    {
        return [
            '0' => '姓', //first_name_kanji
            '1' => '名', //last_name_kanji
            '2' => '肩書き', //relationship
            '3' => '姓(ふりがな)', //last_name_kana
            '4' => '名(ふりがな)', //first_name_kana
            '5' => '姓(ローマ字)', //last_name_romaji
            '6' => '名(ローマ字)', //first_name_romaji
            '7' => '敬称', // honorific
            '8' => '区分', // event mainer
            '9' => '〒', // postal_code
            '10' => '住所1', // first_address
            '11' => '住所2', // second_address
            '12' => '携帯電話番号', //phone
            '13' => '電話番号', //landline
            '14' => '備考', //note
            '15' => '出欠状態', //status
            '16' => '性別', //gender
            '17' => 'メールアドレス', //email
            '18' => 'グループ', //group name
            '19' => 'お子様の年齢', //age
            '20' => '未成年', //child
        ];
    }

    private function _setGroups()
    {
        $attrs = ['user_id' => Auth::user()->id];
        $relations = ['eventMainer'];
        $this->_groups = $this->inviteeGroupService->getInviteeGroupsByConditions($attrs, $relations);
    }

    private function _setEventMainers()
    {
        $user = $this->userService->getEventMainers();
        $eventMainers = $user->event->eventMainers;
        $data = [];
        foreach ($eventMainers as $eventMainer) {
            if ($eventMainer->gender === EventMainer::MALE) {
                $data['groom'] = $eventMainer;
            } else {
                $data['bride'] = $eventMainer;
            }
        }
        $this->_eventMainers = $data;
    }

    private function _setHonorifics()
    {
        $honorifics = $this->honorificService->all();
        $hashData = [];
        if ($honorifics) {
            foreach ($honorifics as $honorific) {
                $hashData[$honorific->name] = $honorific->id;
            }
        }
        $this->_honorifics = $hashData;
    }

    private function _setEventMainerId($division)
    {
        $division = $division !== static::GROOM && $division !== static::BRIDE ? static::GROOM : $division;
        if ($division === self::BRIDE) {
            return $this->_eventMainerId = $this->_eventMainers['bride']->id;
        }
        if ($division === self::GROOM) {
            return $this->_eventMainerId = $this->_eventMainers['groom']->id;
        }
    }

    private function _setGroupOrder($division)
    {
        if ($division === self::BRIDE) {
            return $this->_brideGroupLastOrder ++;
        }
        if ($division === self::GROOM) {
            return $this->_groomGroupLastOrder ++;
        }
    }

    private function _getHonorificId(string $honorific)
    {
        return isset($this->_honorifics[$honorific]) ? $this->_honorifics[$honorific] : 1;
    }

    private function _getGender($gender = '')
    {
        if ($gender === self::GENDER_MALE) {
            return Invitee::MALE;
        }
        if ($gender === self::GENDER_FEMALE) {
            return Invitee::FEMALE;
        }
        return Invitee::MALE;
    }

    private function _isChild($child)
    {
        if ($child === self::IS_CHILD)
            return true;
        return false;
    }

    private function _getStatus($status)
    {
        if ($status === self::STATUS_ATTEND) {
            return Invitee::INVITEE_ATTEND;
        }
        if ($status === self::STATUS_NOT_ATTEND) {
            return Invitee::INVITEE_NOT_ATTEND;
        }
        return Invitee::INVITEE_CONFIRMING;
    }

    private function _getAge($age)
    {
        $age = trim($age);
        if (!($age === "" || $age === null)) {
            $age = intval($age);
            if (is_int($age)) {
                return $age;
            }
        }
        return NULL;
    }

    private function _findGroup($groupName, $division)
    {
        $division = $division !== static::GROOM && $division !== static::BRIDE ? static::GROOM : $division;
        foreach ($this->_groups as $group) {
            if ($group->name === $groupName
                && $group->eventMainer->gender === Invitee::MALE
                && $division === static::GROOM
            ) {
                return $group;
            }
            if ($group->name === $groupName
                && $group->eventMainer->gender === Invitee::FEMALE
                && $division === static::BRIDE
            ) {
                return $group;
            }
        }
        return false;
    }

    private function _getInviteeGroupId($groupName, $division)
    {
        $group = $this->_findGroup($groupName, $division);
        if (!$group) {
            $this->_setEventMainerId($division);
            $group = $this->inviteeGroupService->store([
                'name' => $groupName,
                'user_id' => Auth::user()->id,
                'event_mainer_id' => $this->_eventMainerId,
                'order' => $this->_setGroupOrder($division)
            ]);
            $this->_groups->push($group);
        }
        $this->_order = $this->_setLastOrderInviteeByGroup($group->id);
        return $group->id;
    }

    private function _formatString($str, $r = " ")
    {
        return preg_replace( "/\r|\n/", $r, $str );
    }


    private function _setLastInviteeGroup()
    {
        $groupGroom = $this->inviteeGroupService->getLastInviteeGroup(EventMainer::GROOM_TYPE);
        $groupBride = $this->inviteeGroupService->getLastInviteeGroup(EventMainer::BRIDE_TYPE);
        if (! $groupGroom) {
            $this->_groomGroupLastOrder = 1;
        } else {
            $this->_groomGroupLastOrder = $groupGroom->order + 1;
        }

        if (! $groupBride) {
            $this->_brideGroupLastOrder = 1;
        } else {
            $this->_brideGroupLastOrder = $groupBride->order + 1;
        }
    }

    private function _setLastOrderInviteeByGroup($groupId)
    {
        $invitee = $this->service->getLastInviteeByGroup($groupId);
        if (! $invitee) {
            return 1;
        }
        return $invitee->order + 1;
    }
}
