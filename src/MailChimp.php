<?php

namespace StudioArtCz;

use Mailchimp as MailchimpCore;
use Haltuf\Genderer\Genderer;

class MailChimp
{
    private $apiKey;
    private $lists;
    private $listId;
    private $email;
    private $fullName;
    private $firstName;
    private $values;
    private $firstName5th;
    private $subscriber;
    private $chimp;
    private $gender;
    private $log;

    /**
     * Tools constructor.
     * @param $apiKey string
     * @param $lists array
     * @param $email string
     * @param $fullName string
     * @param $subscriberData array, associative with key for list columns ['KEY' => 'value']
     */
    public function __construct($apiKey, $lists, $email, $fullName, $subscriberData = null)
    {
        $this->apiKey   = $apiKey;
        $this->lists	= $lists;
        $this->email    = $email;
        $this->fullName = $fullName;
        $this->values   = $subscriberData;
    }

    public function subscribe()
    {
        $this->chimp = new MailchimpCore($this->apiKey);
        curl_setopt($this->chimp->ch, CURLOPT_CAINFO, realpath(__DIR__ . '/ssl/cacert.pem'));

        $this->subscriber = $this->getInformation();
        $data             = array_merge($this->subscriber, $this->values);

        return $this->chimp->lists->subscribe($this->listId, ['email' => $this->email], $data, 'html', false);
    }

    /**
     * Get people first name, last name, sex, first name in 5th
     * @return array
     */
    public function getInformation()
    {
        $user  = [];
        $split = explode(' ', $this->fullName);

        if(isset($split[1]))
        {
            $this->firstName = $split[0];

            $user['FNAME'] = $split[0];
            $user['LNAME'] = $split[1];
        }

        $lib = new Genderer();
        $this->gender = $lib->getGender($this->fullName);

        /**
         * todo: think about unknow gender list, spec.
         */
        if(null != $this->gender)
        {
            $this->listId   = ('f' == $this->gender ? $this->lists['woman'] : $this->lists['man']);
            $user['GENDER'] = ('f' == $this->gender ? 'žena' : 'muž');
        }
        else
        {
            $this->listId   = $this->lists['man'];
            $user['GENDER'] = 'bezpohlavní';

            $this->log['unknown'][] = 'gender';
        }

        $this->firstName5th = $this->getFirstName5th();
        if($this->firstName5th)
        {
            $user['FNAME5TH'] = $this->firstName5th;
        }

        return $user;
    }

    /**
     * @return string
     */
    public function getFirstName5th()
    {
        if("f" == $this->gender)
        {
            $csv = fopen(__DIR__ . '/../csv/krestni_zeny.csv', 'r');
            while ($row = fgetcsv($csv))
            {
                if (mb_strtoupper($row[1]) === mb_strtoupper($this->firstName))
                {
                    return $row[2];
                }
            }
        }

        if("m" == $this->gender)
        {
            $csv = fopen(__DIR__ . '/../csv/krestni_muzi.csv', 'r');
            while ($row = fgetcsv($csv))
            {
                if (mb_strtoupper($row[1]) === mb_strtoupper($this->firstName))
                {
                    return $row[2];
                }
            }
        }

        $this->log['unknown'][] = 'firstName5th';
        return null;
    }
}
