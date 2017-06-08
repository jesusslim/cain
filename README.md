# cain
mix service &amp; closure to workflow

# install

	composer require jesusslim/cain

# example

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
	
	$result_injs = [
	    'test_info' => [
	        'id' => 'foo',
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