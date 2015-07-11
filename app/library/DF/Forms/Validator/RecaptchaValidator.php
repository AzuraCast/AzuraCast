<?php
namespace DF\Forms\Validator;

use \Phalcon\Validation\Validator;
use \Phalcon\Validation\ValidatorInterface;
use \Phalcon\Validation\Message;

class RecaptchaValidator extends Validator implements ValidatorInterface
{
    protected $config;

    public function __construct()
    {
        $di = \Phalcon\Di::getDefault();
        $config = $di->get('config');
        $apis_config = $config->apis->toArray();

        if (empty($apis_config) || !isset($apis_config['recaptcha']))
            throw new \DF\Exception('Recaptcha is not configured in apis.conf.php!');

        $this->config = $apis_config['recaptcha'];
    }

    public function validate(\Phalcon\Validation $validation, $attribute)
    {
        $value = $validation->getValue('g-recaptcha-response');
        $ip = $validation->request->getClientAddress();

        if (!$this->verify($value, $ip))
        {
            $message = $this->getOption('message');
            if (!$message)
                $message = 'Please, confirm you are human';

            $validation->appendMessage(new Message($message, $attribute, 'Recaptcha'));
            return false;
        }

        return true;
    }

    protected function verify($data, $ip = null)
    {
        $params = array(
            'secret' => $this->config['private_key'],
            'response' => $data
        );

        if ($ip)
            $params['remoteip'] = $ip;

        $url = 'https://www.google.com/recaptcha/api/siteverify?' .http_build_query($params);
        $response = json_decode(file_get_contents($url));
        return $response->success;
    }
}