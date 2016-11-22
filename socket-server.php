<?php 
/*
 +-------------------------------
 *    @socket通信服务端版本1(过程版)
 +-------------------------------
 *    @socket_create
 *    @socket_bind
 *    @socket_listen
 *    @socket_accept
 *    @socket_read
 *    @socket_write
 *    @socket_close
 *    author ：fangle
 *    time : 2016-11-18
 +--------------------------------
*/

//设置不超时，该文件必须以命令行cgi模式运行
set_time_limit(0);

//指定ip，0.0.0.0表示服务器的所有ip，
//包括127.0.0.1，内网ip和外网ip，若想只用于局域网内通信，可绑内网ip，与外网通信，则必须拥有公网ip
$addr = '0.0.0.0';
//指定端口号
$port = 8002;

//创建一个套接字
if (($sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) < 0) { // 创建一个Socket链接
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

//定义socket服务端key值
$server_key='"'.(int)$sock.'"';
//存放所有客户端,包括服务端句柄
$clients[$server_key]  = $sock;
$writes = NULL;
$excepts = NULL;

//若客户端超过连接最大数，则跳出循环
while (count($clients)<10000){
	//要监听所有客户端和服务端
	$reads=$writes=$clients;
	unset($writes[$server_key]);
	//传入所有监听的reads，和writes,获取变化的reads和writes
	if($num_changed_sockets = socket_select($reads, $writes, $excepts, 0)){

		//如果服务端可读，说明有客户端要接入
		if(in_array($sock,$reads)){
			//获取新接入的客户端句柄
			$new_client=socket_accept($sock);
            $key = '"'.(int) $new_client.'"';
            //放到所有的客户端数组中
            $clients[$key]=$new_client;
            //设置非阻塞模式
            //socket_set_nonblock($new_client);
            //服务端输出
            echo "客户端{$key}来了\n";
            //欢迎客户端动作
            socket_write($new_client, "欢迎你的到来，客户端{$key}\n");
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
echo '服务器被挤爆，10秒后关闭';
sleep(10);
socket_close($sock);

//关闭客户端函数
function close(&$client){
	//关闭收发端
	socket_shutdown($client);
	//释放连接句柄
	socket_close($client);
	//删除无效句柄资源变量
	unset($client);
}

//群发模式函数
function Mass($msg,&$writes){
	foreach ($writes as  $write) {
		socket_write($write, $msg);
	}
	//return true;
}

//私聊模式
function chat($msg,&$write){
	socket_write($write, $msg);
}

//客户端首次接入，初始化函数
function welcome($msg,&$write){
	socket_write($write, $msg);
}