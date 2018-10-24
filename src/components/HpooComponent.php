<?php

namespace macfly\hpoo\components;

use Yii;
use yii\httpclient\Client;
use yii\base\Component;

class HpooComponent extends Component
{
    public $sslVerifyPeer = true;
    public $timeout = 5;
    public $proxy = null;

    public $baseUrl = null;
    public $login = null;
    public $password = null;

    private $client = null;
    private $xcsrf = null;

    public function __construct()
    {
        $this->resetXcsrf();
        $this->client = new Client([
            'requestConfig' => ['format' => Client::FORMAT_JSON],
            'responseConfig' => ['format' => Client::FORMAT_JSON],
        ]);
    }

    public function resetXcsrf()
    {
        $this->xcsrf = [];
    }

    /**
     * Run a flow asynchronously on HP-OO
     *
     * @param string $uuid id uniquely identifying the flow to run
     * @param array $inputs input of the flow
     * @param string $runName explicite name of the run
     * @param string $logLevel log level with which to run the flow
     * @return string|null return the flow exection id or null if failed
     */
    public function flowRunAsync($uuid, $inputs = [], $runName = null, $logLevel = null)
    {
        $opts = ['flowUuid' => $uuid];

        if ($runName !== null) {
            $opts['runName'] = $runName;
        }

        if ($logLevel !== null) {
            $opts['logLevel'] = $logLevel;
        }

        if (count($inputs) > 0) {
            $opts['inputs'] = $inputs;
        }

        $runid	= $this->rq('post', '/executions', $opts);

        return $runid;
    }

    /**
     * See flowRunAsync will just wait for the flow to finish
     *
     * @param string $uuid id uniquely identifying the flow to run
     * @param array $inputs input of the flow
     * @param string $runName explicite name of the run
     * @param string $logLevel log level with which to run the flow
     * @return array|null return the flow output
     */
    public function flowRunSync($uuid, $inputs = [], $runName = null, $logLevel = null)
    {
        $runid = $this->flowRunAsync($uuid, $inputs, $runName, $logLevel);

        while (true) {
            $rp	= $this->flowStatus($runid);

            if (!(array_key_exists('status', $rp) && $rp['status'] == 'RUNNING')) {
                break;
            }

            sleep(1);
        }

        return $rp;
    }

    public function flowStatus($runid)
    {
        $rp	= $this->rq('get', sprintf("/executions/%s/summary", urlencode($runid)));
        return array_key_exists(0, $rp) ? $rp[0] : false;
    }

    public function flowLog($runid)
    {
        $rp	= $this->rq('get', sprintf("/executions/%s/execution-log", urlencode($runid)));
        return $rp;
    }

    /**
     * Execute a request to HP-OO API, manage the X-csrf header
     *
     * @param string $method request method get, post
     * @param string $url API url to query
     * @param array $opts API input
     * @return arrray|null return an array with the reply of HP-OO or null on error.
     */
    public function rq($method, $url, $opts = array())
    {
        $rq	= $this->client->createRequest()
            ->setMethod($method)
            ->setHeaders(['content-type' => 'application/json'])
            ->setUrl($this->baseUrl . $url)
            ->addHeaders(['Authorization' => 'Basic '.base64_encode(sprintf("%s:%s", $this->login, $this->password))]);

        foreach (['header', 'param', 'token'] as $key) {
            if (array_key_exists($key, $this->xcsrf)) {
                $rq->headers->set(sprintf("x-csrf-%s", $key), $this->xcsrf[$key]);
            }
        }

        if (count($opts) > 0) {
            $rq->setData($opts);
        }

        try {
            $rp = $rq->send();
        } catch (\Exception $e) {
            Yii::error($e);
            return null;
        }

        if (!$rp->isOk) {
            throw new \Exception($rp);
        }

        foreach (['header', 'param', 'token'] as $key) {
            if (!is_null($value = $rp->headers->get(sprintf("x-csrf-%s", $key)))) {
                $this->xcsrf[$key]	= $value;
            }
        }
        return $rp->data;
    }
}
