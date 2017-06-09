<?php
/**
 * Created by PhpStorm.
 * User: jesusslim
 * Date: 2017/6/9
 * Time: 上午9:48
 */

namespace Cain;


use Inject\InjectorInterface;

class RouterFlow extends Flow
{

    /**
     * @var string
     */
    protected $root_key;

    /**
     * @var array ['flow_key' => 'next_flow_key','next_flow_key' => function($foo){return 'last_flow_key';}]
     */
    protected $routes;

    public function __construct($flows = [],$result_checkers = [],$result_injs = [],InjectorInterface $container = null,$routes,$root_key)
    {
        $this->routes = $routes;
        $this->root_key = $root_key;
        parent::__construct($flows,$result_checkers,$result_injs,$container);
    }

    /**
     * @return string
     */
    public function getRootKey()
    {
        return $this->root_key;
    }

    /**
     * @param string $root_key
     */
    public function setRootKey($root_key)
    {
        $this->root_key = $root_key;
    }

    /**
     * @return array
     */
    public function getRoutes()
    {
        return $this->routes;
    }

    /**
     * @param array $routes
     */
    public function setRoutes($routes)
    {
        $this->routes = $routes;
    }

    /**
     * set a route
     * @param string $key
     * @param string|\Closure $next
     */
    public function route($key,$next){
        $this->routes[$key] = $next;
    }

    /**
     * run
     * @param array $params
     * @return mixed|null
     */
    public function run($params = []){
        $this->status = self::STATUS_RUNNING;
        $result = null;
        $flow_key_now = $this->root_key;
        while (!is_null($flow_key_now) && isset($this->flows[$flow_key_now])){
            $flow = $this->flows[$flow_key_now];
            if (is_array($flow) && count($flow) == 2){
                $result = $this->container->callInClass($flow[0],$flow[1],$params);
            }elseif ($flow instanceof \Closure){
                $result = $this->container->call($flow,$params);
            }
            if (isset($this->result_checkers[$flow_key_now])){
                $r = $this->result_checkers[$flow_key_now]($result);
                if ($r !== true){
                    $this->status = self::STATUS_FAILED;
                    $this->block_key = $flow_key_now;
                    $this->error_info = $r;
                    return $result;
                }
            }
            if (isset($this->result_injs[$flow_key_now])){
                foreach ($this->result_injs[$flow_key_now] as $source_name => $inj_name){
                    $this->container->mapData($inj_name,$source_name == self::PARAMS_NAME_SELF ? $result : $result[$source_name]);
                }
            }
            //next logic
            if (isset($this->routes[$flow_key_now])){
                $next = $this->routes[$flow_key_now];
                if ($next instanceof \Closure){
                    $flow_key_now = $this->container->call($next,$params);
                }else{
                    $flow_key_now = $next;
                }
            }else{
                $flow_key_now = null;
            }
        }
        $this->status = self::STATUS_FINISHED;
        return $result;
    }
}