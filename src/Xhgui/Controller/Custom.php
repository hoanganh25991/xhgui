<?php

class Xhgui_Controller_Custom extends Xhgui_Controller
{
    protected $_app;
    protected $_profiles;

    public function __construct($app, $profiles)
    {
        $this->_app = $app;
        $this->_profiles = $profiles;
    }

    public function get()
    {
        $this->_template = 'custom/create.twig';
    }

    public function help()
    {
        $request = $this->_app->request();
        if ($request->get('id')) {
            $res = $this->_profiles->get($request->get('id'));
        } else {
            $res = $this->_profiles->latest();
        }
        $this->_template = 'custom/help.twig';
        $this->set(array(
            'data' => print_r($res->toArray(), 1)
        ));
    }

    public function query()
    {
        $request = $this->_app->request();
        $response = $this->_app->response();
        $response['Content-Type'] = 'application/json';

        $query = json_decode($request->post('query'), true);
        $error = array();
        if (is_null($query)) {
            $error['query'] = json_last_error();
        }

        $retrieve = json_decode($request->post('retrieve'), true);
        if (is_null($retrieve)) {
            $error['retrieve'] = json_last_error();
        }

        if (count($error) > 0) {
            $json = json_encode(array('error' => $error));
            return $response->body($json);
        }

        $perPage = $this->_app->config('page.limit');

        $res = $this->_profiles->query($query, $retrieve)
            ->limit($perPage);
        $r = iterator_to_array($res);
        return $response->body(json_encode($r));
    }

    public function test()
    {
        $response = $this->_app->response();
        $response['Content-Type'] = 'application/json';

        $totalApis = [];

        try{
            // connect to mongodb
            // count last second
            $dbName     = $this->_app->config('db.db');
            $collection = (new MongoClient())->$dbName->customViews;
            $lastMinute    = time() - 50000;
            $countList  = $collection->find(["timestamp" => ["\$gte" => $lastMinute]]);

            foreach ($countList as $count){
                $api   = isset($count["api"]) ? $count["api"] : "others";
                $curr  = isset($totalApis[$api]) ? $totalApis[$api] : 0;
                $total = $curr + 1;

                $totalApis[$api] = $total;
            }

        }catch ( \Exception $e ){
            /* Silence ignore */
            var_dump($e); die;
        }

        $r = [
            'totalApis' => $totalApis
        ];

        return $response->body(json_encode($r));
    }
}
