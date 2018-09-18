<?php

use think\Cache;

class client{
    private $msgid = 1;
    public function index(){
        $client = new \swoole_client(SWOOLE_SOCK_TCP);
        $client->set(array(
            'open_mqtt_protocol'     => true,
        ));
        if (!$client->connect('127.0.0.1', 9501, -1))
        {
            exit("connect failed. Error: {$client->errCode}\n");
        }
        $req=$this->send_publish('abc/#','123321',1,0);
        $client->send($req);
        echo $client->recv();
        $client->close();
    }


    protected function send_publish($topic, $content, $qos = 0, $retain = 0)
    {
        // $buffer = "";
        // $buffer .= $topic;
        // if($qos>0){
        //   $lsbmsb=Cache::get('lsbmsb')?:1;
        //   if($lsbmsb>65535){
        //      $lsbmsb=1;
        //   }
        //   Cache::set('lsbmsb',$lsbmsb+1);
        //             var_dump($lsbmsb);
        //   if($lsbmsb<=255){
        //     $lsbmsb=chr(0).chr($lsbmsb);
        //   }else{
        //     $lsbmsb=dechex($lsbmsb);
        //     $lsbmsb=str_pad($lsbmsb,4,'0',STR_PAD_LEFT);
        //     $lsbmsb=chr(hexdec($lsbmsb{0}.$lsbmsb{1})).chr(hexdec($lsbmsb{2}.$lsbmsb{3}));
        //   }  
        //   $buffer .=$lsbmsb;
        // }
        // $buffer .= $content;
        // $head = " ";
        //  //todo qos
        // switch ($qos*2+$retain) {
        //     case 1:
        //         $cmd = 0x31;
        //         break;
        //     case 2:
        //         $cmd = 0x32;
        //         break;
        //     case 3:
        //         $cmd = 0x33;
        //         break;
        //     case 4:
        //         $cmd = 0x34;
        //         break;
        //     case 5:
        //         $cmd = 0x35;
        //         break;
        //     default:
        //         $cmd = 0x30;
        //         break;
        // }
        // $head{0} = chr($cmd);
        // $head .= $this->setmsglength(strlen($topic)+strlen($content)+2);
        // $str=$head.chr(0).$this->setmsglength(strlen($topic)).$buffer;
        $i = 0;
        $buffer = "";

        $buffer .= $this->strwritestring($topic,$i);

        //$buffer .= $this->strwritestring($content,$i);

        if($qos){
            //可以用其它缓存类代替，如redis
            $lsbmsb=Cache::get('lsbmsb')?:1;
            if($lsbmsb>65535){
                $lsbmsb=1;
            }else{
                $lsbmsb+=1;
            }
            Cache::set('lsbmsb',$lsbmsb);
            $buffer .= chr($lsbmsb >> 8);  $i++;
            $buffer .= chr($lsbmsb % 256);  $i++;
        }

        $buffer .= $content;
        $i+=strlen($content);


        $head = " ";
        $cmd = 0x30;
        if($qos) $cmd += $qos << 1;
        if($retain) $cmd += 1;

        $head{0} = chr($cmd);
        $head .= $this->setmsglength($i);
        $str=$head.$buffer;
        return $str;
    }

    /* setmsglength: */
    public function setmsglength($len){
        $string = "";
        do{
            $digit = $len % 128;
            $len = $len >> 7;
            // if there are more digits to encode, set the top bit of this digit
            if ( $len > 0 )
                $digit = ($digit | 0x80);
            $string .= chr($digit);
        }while ( $len > 0 );
        return $string;
    }
    /* strwritestring: writes a string to a buffer */
    public function strwritestring($str, &$i){
        $ret = " ";
        $len = strlen($str);
        $msb = $len >> 8;
        $lsb = $len % 256;
        $ret = chr($msb);
        $ret .= chr($lsb);
        $ret .= $str;
        $i += ($len+2);
        return $ret;
    }
} 
