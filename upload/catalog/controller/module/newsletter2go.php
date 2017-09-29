<?php

class ControllerModuleNewsletter2Go extends Controller
{

    /**
     * err-number, that should be pulled, whenever credentials are missing
     */
    const ERRNO_PLUGIN_CREDENTIALS_MISSING = 'int-1-404';
    /**
     *err-number, that should be pulled, whenever credentials are wrong
     */
    const ERRNO_PLUGIN_CREDENTIALS_WRONG = 'int-1-403';
    /**
     * err-number for all other (intern) errors. More Details to the failure should be added to error-message
     */
    const ERRNO_PLUGIN_OTHER = 'int-1-600';

    /**
     * @var ModelModuleNewsletter2Go
     */
    private $model;

    public function __construct($registry)
    {
        parent::__construct($registry);

        $this->load->model('module/newsletter2go');
        $this->model = $this->model_module_newsletter2go;
    }

    /**
     * Test connection method
     */
    public function index()
    {
        try {
            if ($this->checkUser()) {
                $this->load->model('setting/setting');
                $settings = $this->model_setting_setting->getSetting('Newsletter2Go');
                $settings['Newsletter2Go_version'];
                $this->generateSuccessResponse(array('version' => $settings['Newsletter2Go_version']));
            } else {
                $this->generateErrorResponse('User validation failed!', self::ERRNO_PLUGIN_CREDENTIALS_WRONG);
            }
        } catch (Exception $exc) {
            $code = ($exc->getCode() === 123 ? self::ERRNO_PLUGIN_CREDENTIALS_MISSING :
                self::ERRNO_PLUGIN_OTHER);
            $this->generateErrorResponse($exc->getMessage(), $code);
        }
    }

    public function getLanguages()
    {
        try {
            if ($this->checkUser()) {
                $this->generateSuccessResponse(array('languages' => $this->model->getLanguages()));
            } else {
                $this->generateErrorResponse('User validation failed!', self::ERRNO_PLUGIN_CREDENTIALS_WRONG);
            }
        } catch (Exception $exc) {
            $code = ($exc->getCode() === 123 ? self::ERRNO_PLUGIN_CREDENTIALS_MISSING :
                self::ERRNO_PLUGIN_OTHER);
            $this->generateErrorResponse($exc->getMessage(), $code);
        }
    }

    public function getCustomerFields()
    {
        try {
            if (!$this->checkUser()) {
                $this->generateErrorResponse('User validation failed!', self::ERRNO_PLUGIN_CREDENTIALS_WRONG);
            } else {
                $this->generateSuccessResponse(array('fields' => $this->model->customerFields()));
            }
        } catch (Exception $exc) {
            $code = ($exc->getCode() === 123? self::ERRNO_PLUGIN_CREDENTIALS_MISSING :
                self::ERRNO_PLUGIN_OTHER);
            $this->generateErrorResponse($exc->getMessage(), $code);
        }
    }

    public function getCustomerGroups()
    {
        try {
            if (!$this->checkUser()) {
                $this->generateErrorResponse('User validation failed!', self::ERRNO_PLUGIN_CREDENTIALS_WRONG);
            } else {
                $this->generateSuccessResponse($this->model->customerGroups());
            }
        } catch (Exception $exc) {
            $code = ($exc->getCode() === 123 ? self::ERRNO_PLUGIN_CREDENTIALS_MISSING :
                self::ERRNO_PLUGIN_OTHER);
            $this->generateErrorResponse($exc->getMessage(), $code);
        }
    }

    public function getCustomerCount()
    {
        $subscribed = (isset($this->request->post['subscribed']) ? $this->request->post['subscribed'] : '');
        $group = (isset($this->request->post['group']) ? $this->request->post['group'] : '');

        try {
            if (!$this->checkUser()) {
                $this->generateErrorResponse('User validation failed!', self::ERRNO_PLUGIN_CREDENTIALS_WRONG);
            } else {
                $this->generateSuccessResponse($this->model->customerCount($subscribed, $group));
            }
        } catch (Exception $exc) {
            $code = ($exc->getCode() === 123 ? self::ERRNO_PLUGIN_CREDENTIALS_MISSING :
                self::ERRNO_PLUGIN_OTHER);
            $this->generateErrorResponse($exc->getMessage(), $code);
        }
    }

    public function getCustomers()
    {
        try {
            $criteria = array();
            $criteria['offset'] = (isset($this->request->post['offset']) ? $this->request->post['offset'] : '');
            $criteria['limit'] = (isset($this->request->post['limit']) ? $this->request->post['limit'] : '');
            $criteria['subscribed'] = (isset($this->request->post['subscribed']) ? $this->request->post['subscribed'] : '');
            $criteria['group'] = (isset($this->request->post['group']) ? $this->request->post['group'] : '');
            $criteria['fields'] = (isset($this->request->post['fields']) ? $this->request->post['fields'] : array());
            $criteria['emails'] = (isset($this->request->post['emails']) ? $this->request->post['emails'] : array());

            if (!$this->checkUser()) {
                $this->generateErrorResponse('User validation failed!', self::ERRNO_PLUGIN_CREDENTIALS_WRONG);
                return;
            }

            $result = $this->model->getCustomers($criteria);
            $this->generateSuccessResponse($result);
        } catch (Exception $exc) {
            $code = ($exc->getCode() === 123 ? self::ERRNO_PLUGIN_CREDENTIALS_MISSING :
                self::ERRNO_PLUGIN_OTHER);
            $this->generateErrorResponse($exc->getMessage(), $code);
        }
    }

    public function getItemFields()
    {
        try {
            if (!$this->checkUser()) {
                $this->generateErrorResponse('User validation failed!', self::ERRNO_PLUGIN_CREDENTIALS_WRONG);
            } else {
                $this->generateSuccessResponse(array('fields' => $this->model->itemFields()));
            }
        } catch (Exception $exc) {
            $code = ($exc->getCode() === 123? self::ERRNO_PLUGIN_CREDENTIALS_MISSING :
                self::ERRNO_PLUGIN_OTHER);
            $this->generateErrorResponse($exc->getMessage(), $code);
        }
    }

    public function getProduct()
    {
        try {
            $id = (isset($this->request->post['id']) ? $this->request->post['id'] : '');
            $lang = (isset($this->request->post['lang']) ? $this->request->post['lang'] : '');
            $fields = (isset($this->request->post['fields']) ? $this->request->post['fields'] : array());

            if (empty($id)) {
                $this->generateErrorResponse('Missing product parameters!', self::ERRNO_PLUGIN_CREDENTIALS_WRONG);
                return;
            }

            if (!$this->checkUser()) {
                $this->generateErrorResponse('User validation failed!', self::ERRNO_PLUGIN_CREDENTIALS_WRONG);
                return;
            }

            $this->generateSuccessResponse(array('product' => $this->model->getProduct($id, $lang, $fields)));
        } catch (Exception $exc) {
            $code = ($exc->getCode() === 123 ? self::ERRNO_PLUGIN_CREDENTIALS_MISSING :
                self::ERRNO_PLUGIN_OTHER);
            $this->generateErrorResponse($exc->getMessage(), $code);
        }
    }

    public function unsubscribeCustomer()
    {
        try {
            $email = (isset($this->request->post['email']) ? $this->request->post['email'] : '');
            $status = (isset($this->request->post['status']) ? $this->request->post['status'] : '');

            if (!$email) {
                $this->generateErrorResponse('Missing email parameter!', self::ERRNO_PLUGIN_OTHER);
                return;
            }

            if (!$this->checkUser()) {
                $this->generateErrorResponse('User validation failed!', self::ERRNO_PLUGIN_OTHER);
                return;
            }

            $this->model->unsubscribeByEmail($email, $status);
            $this->generateSuccessResponse();
        } catch (Exception $exc) {
            $code = ($exc->getCode() === 123 ? self::ERRNO_PLUGIN_CREDENTIALS_MISSING :
                self::ERRNO_PLUGIN_OTHER);
            $this->generateErrorResponse($exc->getMessage(), $code);
        }
    }

    /**
     * Checks if apiKey is missing and if apiKey is valid
     * @return bool
     * @throws Exception
     */
    protected function checkUser()
    {
        if (!isset($this->request->post['apiKey'])) {
            throw new Exception('Api Key parameter is missing!', 123);
        }

        $apikey = $this->request->post['apiKey'];

        return $this->model->validateUser($apikey);
    }

    private function generateSuccessResponse($data = array())
    {
        $res = array('success' => true, 'message' => 'OK');
        $this->setResponse(array_merge($res, $data));
    }

    private function generateErrorResponse($message, $errorCode, $context = null)
    {
        $res = array(
            'success' => false,
            'message' => $message,
            'errorcode' => $errorCode,
        );

        if ($context != null) {
            $res['context'] = $context;
        }

        $this->setResponse($res);
    }

    /**
     * Sets http content-type header and fills output buffer
     * @param array $response
     */
    private function setResponse($response = array())
    {
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($response));
    }

}
