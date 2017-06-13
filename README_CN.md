# cain
将服务/闭包组合为工作流

## install

	composer require jesusslim/cain

## 示例 basic flow

	Class Test{

	    protected $sid;
	
	    public function __construct($sid)
	    {
	        $this->sid = $sid;
	    }
	    public function getNickname(){
	        return 'foo'.$this->sid;
	    }
	
	    public function getInfo(){
	        $id = $this->sid;
	        $nickname = $this->getNickname();
	        return compact('id','nickname');
	    }
	}
	
	/*********************************************/
	/* 工作流定义,[类名,方法名] 或 闭包 */
	
	$flows = [
	    'test_info' => [Test::class,'getInfo'],
	    'test_closure' => function($foo){
	        return ++$foo;
	    }
	];
	
	/*********************************************/
	/* result_injs 会将工作流任意一步的结果中的某字段(或整个结果)注入到容器中，便于后续的工作流使用它们 */
	/* 键名为结果集中的键名,值为注入到容器中的命名 */
	
	$result_injs = [
	    'test_info' => [
	        'id' => 'foo', //means container.foo = result.id
	//        \Cain\Flow::PARAMS_NAME_SELF => 'whole'
	    ]
	];
	
	/*********************************************/
	/* result_checkers 会检查工作流每一步的结果,接收一个闭包function($result){}.*/ 
	/* 检查ok则返回true 工作流会继续往下执行.*/ 
	/* 如果不返回true 工作流会停止,使用 isFinished/isError 来获取当前状态,使用getError获取闭包中返回的错误信息.  */
	
	$result_checkers = [
	    'test_closure' => function($result){
	        if ($result != 100) return 'bar';
	        return true;
	    }
	];
	$fl = new \Cain\Flow($flows,$result_checkers,$result_injs);
	var_dump($fl->run(['sid' => 12345]));
	var_dump($fl->isFinished());
	var_dump($fl->isError());
	var_dump($fl->getError());
	
	/*********************************************/
	/* 使用flush方法清除状态和错误 */

    $fl->flush();

    /*********************************************/
    /* 使用InjectorInterface获取注入容器本身 */

    $fl->flow('test_get_container',function(\Inject\InjectorInterface $injector){
        return $injector->produce('nick');
    });

    var_dump($fl->run(['sid' => 99]));
    
## 示例 router flow

带路由的工作流

	/*********************************************/
	/* 工作流定义,[类名,方法名] 或 闭包 */

	$flows = [
	    'test_info' => [Test::class,'getInfo'],
	    'test_closure' => function($foo){
	        return ++$foo;
	    },
	    'math_foo' => function($foo,$bar = 100){
	        return $foo * $bar;
	    },
	    'math_bar' => function($foo,$bar = 100){
	        return $foo / $bar;
	    },
	];

	/*********************************************/
	/* 路由 逻辑 */
	/* 键名为 from的工作流key */
	/* 值为 to的工作流key 或 闭包 返回下一个工作流步骤的key */

	$routes = [
	    'test_info' => 'test_closure',
	    'test_closure' => function($foo){
	        return $foo % 2 == 1 ? 'math_foo' : 'math_bar';
	    }
	];

	/*********************************************/
	/* checkers & injs , 与basic flow中一致 */

	$result_checkers = [
	    'test_closure' => function($result){
	        //if ($result != 100) return 'bar';
	        var_dump("result is $result");
	        return true;
	    }
	];

	$result_injs = [
	    'test_info' => [
	        'id' => 'foo',
	        'nickname' => 'nick',
	        //        \Cain\Flow::PARAMS_NAME_SELF => 'whole'
	    ]
	];

	/*********************************************/
	/* RouterFlow 继承于 Flow,需要定义 routes(路由逻辑) & root_key(初始工作流步骤的key,即声明第一步) */
	/* 当checkers检查导致工作流停止 或者 找不到下一步时 RouterFlow会返回当前的结果 */

	$fl = new \Cain\RouterFlow($flows,$result_checkers,$result_injs,null,$routes,'test_info');
	var_dump($fl->run(['sid' => 99]));
	$fl->flush();
	var_dump($fl->run(['sid' => 100]));