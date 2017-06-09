# cain
mix service &amp; closure to workflow

## install

	composer require jesusslim/cain

## example of basic flow

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
	/* the workflows,[class,function] or closure */
	
	$flows = [
	    'test_info' => [Test::class,'getInfo'],
	    'test_closure' => function($foo){
	        return ++$foo;
	    }
	];
	
	/*********************************************/
	/* result_injs will inject the datas of result(or the whole result) to container,so that the next workflow can use them */
	/* the key is the keyname in result,the value is the keyname that we want to set to container. */
	
	$result_injs = [
	    'test_info' => [
	        'id' => 'foo', //means container.foo = result.id
	//        \Cain\Flow::PARAMS_NAME_SELF => 'whole'
	    ]
	];
	
	/*********************************************/
	/* result_checkers will check the result,every result_checker should be a closure like function($result){}.*/ 
	/* If check ok,return true,then the next workflow can be run.*/ 
	/* If the return of this closure is not true,the whole workflow will be blocked,and we can use isFinished/isError to check the status,and use getError to get the error info.  */
	
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
	/* use flush to clear status & errors */

    $fl->flush();

    /*********************************************/
    /* use InjectorInterface to get the container */

    $fl->flow('test_get_container',function(\Inject\InjectorInterface $injector){
        return $injector->produce('nick');
    });

    var_dump($fl->run(['sid' => 99]));
    
## example of router flow

	/*********************************************/
	/* the workflows,[class,function] or closure */

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
	/* the routes logic */
	/* key is the source route flow key */
	/* value is the next route flow key or a Closure that return the next route flow key. */

	$routes = [
	    'test_info' => 'test_closure',
	    'test_closure' => function($foo){
	        return $foo % 2 == 1 ? 'math_foo' : 'math_bar';
	    }
	];

	/*********************************************/
	/* checkers & injs , same in basic flow */

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
	/* RouterFlow extends Flow,need routes(workflow routes logic) & root_key(the start flow key) */
	/* RouterFlow will return when the workflow blocked by checkers or it can't find the next flow key */

	$fl = new \Cain\RouterFlow($flows,$result_checkers,$result_injs,null,$routes,'test_info');
	var_dump($fl->run(['sid' => 99]));
	$fl->flush();
	var_dump($fl->run(['sid' => 100]));