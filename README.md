# yii2-hpoo

This extension allows to run, get status and get output of a flow on [HP-OO v10.x](http://www.hp.com/us/en/software-solutions/operations-orchestration-it-process-automation/). HP-OO stand for Operations Orchestration a product develop by [HPE](http://www.hpe.com), it's an IT process automation and run book software. FLow develop in the soft can be trigger trough a REST API. This extension is here to help in using it.

Requires:

* Hp-OO: >= 10.60
* php: >=5.4
* yii2: >=2.0.1
* yiisoft/yii2-httpclient: >= 2.0.1

## Installation

As simple as download it

```console
$ composer require macfly/yii2-hpoo "*"
```

or add

```json
"macfly/yii2-hpoo: "*"
```

to the `require` section of your composer.json.

## Configuring application

After extension is installed you need to setup application component:

```php
return [
	'components' => [
		'hpoo' => [
			'class' => 'macfly\hpoo\components\HpooComponent',
			'url' => 'https://127.0.0.1:8443/oo/rest/v2', // HP-OO central url
			'login' => 'myaccount' // An account with the right to trigger a flow
			'password' => 'mypass' // Password related to the account.
			'timeout' => 5, // Conenction timeout (default: 5 seconds)
			'sslVerifyPeer' => true, // Check ssl certificate (default: true)
			'proxy' => 'tcp://ip:port/' // Proxy to use to access url (optional)
			// etc.
		],
	]
	// ...
];
```

## Usage

Access to hpoo in your controller, or model:

````php
// Flow UUID
$uuid = '1793b153-0ada-451e-93cd-143c3509e8a4';
// FLOW input args if needed
$args = [
	'input1' => 'myinopout',
	....
];

// Running flow Sync (will give you the end when flow is finnished).
$rp = Yii::$app->hpoo->flowRunSync($uuid, $args);

print_r($rp);

// Running flow Async (will return instantly and give you the flow executionid)
$rp = Yii::$app->hpoo->flowRunAsync($uuid, $args);

// Get a flow status
print_r(Yii::$app->hpoo->flowRunAsync($rp['executionId']));

// Get a flow output
print_r(Yii::$app->hpoo->flowLog($rp['executionId']));

````
