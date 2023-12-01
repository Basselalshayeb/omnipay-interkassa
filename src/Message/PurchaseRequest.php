<?php


namespace Omnipay\PayPlanet\Message;

use Omnipay\Common\Message\AbstractRequest;

class PurchaseRequest extends AbstractRequest
{

    public function getCurrency()
    {
        return $this->getParameter('currency');
    }

    public function getTransId()
    {
        return $this->getParameter('ts');
    }

    public function getMethod()
    {
        return $this->getParameter('method');
    }

    public function getAmount()
    {
        return $this->getParameter('amount');
    }

    public function setFullKeys($fullKeys){
        return $this->setParameter('fullKeys', $fullKeys);
    }

    public function getFullKeys()
    {
        return $this->getParameter('fullKeys');
    }

    public function getShopId()
    {
        return $this->getFullKeys()[$this->$this->getMethod()]['shop_id'];
    }

    public function getSecretKey()
    {
        return $this->getFullKeys()[$this->$this->getMethod()]['secret_key'];
    }

    public function getData()
    {
        $this->validate('amount', 'currency', 'method', 'ts');

        $data = [
            'ik_co_id' => $this->getShopId(),
            'ik_pm_no' => $this->getTransId(),
            'ik_am' => $this->getAmount(),
            'ik_cur' => $this->getCurrency(),
            'ik_desc' => 'interkassa',
            'ik_act' => 'process',
            'ik_int' => 'json',
            'ik_payment_method' => $this->getMethod(),
            'ik_payment_currency' => $this->getCurrency(),

        ];
        return array_filter($data, function ($value) {
            return $value !== null;
        });

    }

    function sortByKeyRecursive(array $array): array
    {
        ksort($array, SORT_STRING);
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $array[$key] = sortByKeyRecursive($value);
            }
        }
        return $array;
    }

    function implodeRecursive(string $separator, array $array): string
    {
        $result = '';
        foreach ($array as $item) {
            $result .= (is_array($item) ? implodeRecursive($separator, $item) : (string)$item) . $separator;
        }

        return substr($result, 0, -1);
    }


    public function sendData($data)
    {


        $checkoutKey = $this->getSecretKey();
        $sortedDataByKeys = sortByKeyRecursive($data);
        $sortedDataByKeys[] = $checkoutKey;

        $signString = implodeRecursive(':', $sortedDataByKeys);
        $data['ik_sign'] = base64_encode(hash('sha256', $signString, true));

        $headers = [
            'Content-Type' => 'application/x-www-form-urlencoded',
        ];

        $httpResponse = $this->httpClient->request('POST', 'https://sci.interkassa.com/', $headers, json_encode($data));
        return $this->createResponse($httpResponse->getBody()->getContents());
    }


    protected function createResponse($data)
    {
        return $this->response = new PurchaseResponse($this, json_decode($data, true));
    }

}
