<?php
/**
 * Created by PhpStorm.
 * User: jesusslim
 * Date: 2017/6/8
 * Time: 上午9:41
 */

namespace Cain;


use Inject\Injector;
use Inject\InjectorInterface;

class Flow
{

    protected $flows;
    protected $result_checkers;
    protected $result_injs;
    protected $container;
    const PARAMS_NAME_SELF = '_self';

    protected $status;
    const STATUS_NOT_BEGIN = 0;
    const STATUS_RUNNING = 1;
    const STATUS_FINISHED = 2;
    const STATUS_FAILED = 3;

    protected $block_key;
    protected $error_info;

    public function __construct($flows = [],$result_checkers = [],$result_injs = [],InjectorInterface $container = null)
    {
        $this->flows = $flows;
        $this->result_checkers = $result_checkers;
        $this->result_injs = $result_injs;
        $this->container = $container ? $container : new Injector();
        $this->container->mapData(InjectorInterface::class,$this->container);
        $this->status = self::STATUS_NOT_BEGIN;
        $this->block_key = null;
        $this->error_info = null;
    }

    /**
     * @return array
     */
    public function getFlows()
    {
        return $this->flows;
    }

    /**
     * @param array $flows
     */
    public function setFlows($flows)
    {
        $this->flows = $flows;
    }

    /**
     * @return array
     */
    public function getResultInjs()
    {
        return $this->result_injs;
    }

    /**
     * @param array $result_injs
     */
    public function setResultInjs($result_injs)
    {
        $this->result_injs = $result_injs;
    }

    /**
     * @return Injector|InjectorInterface
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * @param Injector|InjectorInterface $container
     */
    public function setContainer($container)
    {
        $this->container = $container;
    }

    /**
     * @return array
     */
    public function getResultCheckers()
    {
        return $this->result_checkers;
    }

    /**
     * @param array $result_checkers
     */
    public function setResultCheckers($result_checkers)
    {
        $this->result_checkers = $result_checkers;
    }

    /**
     * add flow
     * @param $key
     * @param $flow
     * @return $this
     */
    public function flow($key,$flow){
        $this->flows[$key] = $flow;
        return $this;
    }

    /**
     * set : inject result to container
     * @param $key
     * @param $inj_name
     * @param string $param_name
     * @return $this
     */
    public function resultInj($key,$inj_name,$param_name = self::PARAMS_NAME_SELF){
        $this->result_injs[$key][$param_name] = $inj_name;
        return $this;
    }

    /**
     * set : check result
     * @param string|array $keys
     * @param \Closure $closure function($result){return true;}
     * @return $this
     */
    public function resultCheck($keys,\Closure $closure){
        if (!is_array($keys)) $keys = [$keys];
        $this->result_checkers += array_fill_keys($keys,$closure);
        return $this;
    }

    /**
     * run
     * @param array $params
     * @return mixed|null
     */
    public function run($params = []){
        $this->status = self::STATUS_RUNNING;
        $result = null;
        foreach ($this->flows as $key => $flow){
            if (is_array($flow) && count($flow) == 2){
                $result = $this->container->callInClass($flow[0],$flow[1],$params);
            }elseif ($flow instanceof \Closure){
                $result = $this->container->call($flow,$params);
            }
            if (isset($this->result_checkers[$key])){
                $r = $this->result_checkers[$key]($result);
                if ($r !== true){
                    $this->status = self::STATUS_FAILED;
                    $this->block_key = $key;
                    $this->error_info = $r;
                    return $result;
                }
            }
            if (isset($this->result_injs[$key])){
                foreach ($this->result_injs[$key] as $source_name => $inj_name){
                    $this->container->mapData($inj_name,$source_name == self::PARAMS_NAME_SELF ? $result : $result[$source_name]);
                }
            }
        }
        $this->status = self::STATUS_FINISHED;
        return $result;
    }

    /**
     * is finished
     * @return bool
     */
    public function isFinished(){
        return $this->status == self::STATUS_FINISHED;
    }

    /**
     * is error
     * @return bool
     */
    public function isError(){
        return $this->status == self::STATUS_FAILED;
    }

    /**
     * get status
     * @return int
     */
    public function getStatus(){
        return $this->status;
    }

    /**
     * get block key
     * @return null|string
     */
    public function getBlockKey(){
        return $this->block_key;
    }

    /**
     * get error info
     * @return null
     */
    public function getError(){
        return $this->error_info;
    }

    /**
     * @param bool $reset_container
     * flush
     */
    public function flush($reset_container = true){
        $this->status = self::STATUS_NOT_BEGIN;
        $this->error_info = null;
        $this->block_key = null;
        if ($reset_container) {
            $this->container->flush();
            $this->container->mapData(InjectorInterface::class,$this->container);
        }
    }
}