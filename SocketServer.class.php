<?php 
/*
 +-------------------------------
 *    @socket通信服务端版本1(面向对象版)
 +-------------------------------
 *    @socket_create
 *    @socket_bind
 *    @socket_listen
 *    @socket_accept
 *    @socket_read
 *    @socket_write
 *    @socket_close
 *    author ：fangle
 *    time : 2016-11-22
 +--------------------------------
*/
class SocketServer{
	private $addr = '0.0.0.0'; //ip
	private $port = '8002'   ; //端口
	private $limit = 10000;    //最大客户端连接数
	public  $server;		   //服务端句柄
	public  $server_key;		//服务端key值
	public  $clients= array();  //客户端数组池，包含服务端
	public  $wel_word ='欢迎来到聊天室';
	
	public function __construct($addr=$this->addr,$port=$this->port,$limit=$this->limit){ 
		//设置不超时
		set_time_limit(0);
		// 创建一个Socket链接
		if($sock= socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) < 0) {
			echo "socket_create() 失败的原因是:" . socket_strerror($sock) . "\n";
		}
		//绑定对应的ip和端口
		if (($ret = socket_bind($sock, $addr, $port)) < 0) { //绑定Socket到端口
		    echo "socket_bind() 失败的原因是:" . socket_strerror($ret) . "\n";
		}
		//监听端口，指定最大连接数
		if (($ret = socket_listen($sock, 10000)) < 0) { // 开始监听链接链接
    		echo "socket_listen() 失败的原因是:" . socket_strerror($ret) . "\n";
		}

		//设置端口复用,貌似无效。重新打开该脚本还是等了1分钟，系统才释放该端口
		//socket_set_option($sock,SOL_SOCKET,SO_REUSEADDR,1);
		//接收发送超时,没测试过。
		//socket_set_option($sock,SOL_SOCKET,SO_RCVTIMEO,array('sec' => 1,'usec'=>0));
		//socket_set_option($sock,SOL_SOCKET,SO_SNDTIMEO,array('sec' => 1,'usec'=>0));


		//赋值给该server
		$this->server=$sock;
		$this->server_key=$this->socketKey($sock);
		$this->clients[$this->server_key]=$server;
	}



	public function serverStart(){
		//若客户端超过连接最大数，则跳出循环
		while (count($this->clients)<$this->limit){
			//要监听所有客户端和服务端
			$reads=$writes=$clients;
			unset($writes[$server_key]);
			//传入所有监听的reads，和writes,获取变化的reads和writes
			if($num_changed_sockets = socket_select($reads, $writes, $excepts, 0)){

				//如果服务端可读，说明有客户端要接入
				if(in_array($sock,$reads)){
					//调用接入初始化方法
					$this->accessClinet();
					//从可读客户端中删除服务端
        			unset($reads[$server_key]);
				}

				//轮询所有可读的客户端，读取数据
				foreach ($reads as $key => $read) {
					//读取不大于2M的数据
					$buf = @socket_read($read, 2048);
					//如何读取不到内容，说明客户端已主动断开  
		            if ($buf == false || $buf=='quit') {  
		                //服务端释放该无效资源
		                close($clients[$key]);
		                unset($clients[$key]);
		                //服务端输出以离开  
		                echo "客户端{$key}已离开\n";
		                continue;  
		            }else{
		            	//服务端输出内容
		                echo "客户端{$key}:{$buf}\n";
		                //这里可以写调用函数，通过条件，调用群发模式，或是私聊模式
		            }
				}
			}
		}
		
		//跳出循环，说明客户端连接已达到上限,作处理//此处待完善
		echo '服务器被挤爆，10秒后关闭';
		sleep(10);
		$this->serverClose();
	}

	//新客户端接入初始化函数
	private function accessClinet(){
		//获取新接入的客户端句柄
		$new_client=socket_accept($this->server);
        $key = $this->socketKey($new_client);
        //放到所有的客户端数组中
        $this->clients[$key]=$new_client;
        //设置非阻塞模式
        //socket_set_nonblock($new_client);
       
        //服务端输出
    	$this->serverOutput("客户端{$key}来了\n");
    	 
    	//客户端欢迎词
        $msg = "欢迎你的到来，客户端{$key}\n";
        $this->welcome($new_client,$msg);
       
        //读取客户端信息
        $msg=socket_read($new_client, 2048);
        echo $msg;
	}

	//客户端首次接入，欢迎通知函数
	public function welcome(&$write,$msg=$this->wel_word){ 
		socket_write($write, $msg);
	}
	
	//服务端输出函数
	public function serverOutput($msg){
		echo $msg;
	}

	//生成连接池$clinets数组的key值函数
	private function socketKey($resources){
		return (int)$resources;
	}

	//关闭客户端函数
	public function clientClose($key){
		//关闭收发端
		socket_shutdown($this->clients[$key]);
		//释放连接句柄
		socket_close($this->clients[$key]);
		//删除无效句柄资源变量
		unset($this->clients[$key]);
	}

	//关闭服务端函数
	public function serverClose($msg='服务端关闭中'){
		//该方法还未完善，还应提示服务端有n个客户端连接中，建议是否断开全部连接。
		if($num=count($this->clients)>1){
			echo "当前还有{$num-1}个连接";
		}
		socket_shutdown($this->server);
		socket_close($this->server);
	} 
	

	//群发模式函数
	public function Mass($msg,&$writes){
		foreach ($writes as  $write) {
			socket_write($write, $msg);
		}
		//return true;
	}

	//私聊模式
	public function chat($msg,&$write){
		socket_write($write, $msg);
	}
}


