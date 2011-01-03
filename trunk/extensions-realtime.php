<?php
require_once('lib/Smarty.class.php');
require_once "lib/php-asmanager.php";
require_once('DB.php'); 
unset($AgentAccount);
session_start();
$smarty = new Smarty();
$ami = new AGI_AsteriskManager();
$res = $ami->connect();	

define ("HOST", 'localhost');
define ("PORT", "5432");
define ("USER", 'asteriskuser');
define ("PASS", '4nccnuQcYbD4VZRc');
define ("DBNAME", "asterisk");
define ("DB_TYPE", "mysql"); // mysql or postgres

$AgentsEntry=array();
$QueueParams=array();
$QueueMember=array();
$QueueEntry=array();

header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); //
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT"); 
header("Cache-Control: no-store, no-cache, must-revalidate"); 
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache"); 

if($_REQUEST['button'] == 'Submit')
{
        $out="<pre>".shell_exec('/usr/sbin/asterisk -rx "show queues"')."</pre>";
        echo $out;
        
}
/*
����� ������� ������ ����� ��������������, ����� ���� ����� � ������
����������� �������� ������� �������� ������, ��� �� ����� ������� ���������� �����,
�� ������� ����� �������������� ������. ����� ����� ��� �������� ������ ����������
���������/��������� �������, ��� ��� �� ��� ����� ��������� ������.
��� ������������� �� ����� ������������������ � ������� (�����). ��� ���� ��� ������������
���������� �������, ��������� ���������.
�������� �� ������� �������� � ����������� ������� ���������� ����� � ��� ����� ���������
������ �� ������� ���������. ���� ������ � ����������� ���� �����, � ����� ������ �������,
�� ����� ����� ��������� �� ���� �����.
�������� ����� ��������� ����� �� ��������� � ����������� �� ������������ ��� ���������.
�������� ����� ��������� ������ � ����������� ����� �� ������ help � ������ �����������
�� ������. ��� ���� ��� ����� ��������� �������� ���������.
����������� �������� �������������� ����� � ������������ ��������� ���� ��������.
����� ����� �� ������ ���������� ����� ���������� ������������ �������� �� ����� ��������,
������������ � ��������� � ������ ������.

*/

if($_REQUEST['show'] == 'list')
{
       // 
        //$out=$ami->Events('QueueParams,QueueMember,QueueEntry')
        //$out=$ami->command('database show');
        $database=$ami->database_show('AMPUSER');
        // ����������� ������ ���� ������ asterisk � ������ ��� �������� ����������� ���������� smartly
        $i=0;
        foreach($database as $key => $value)
        {
            $keys=split("/",$key);
            $extensions[$keys[2]][$keys[3]]=$value;
            $extensions[$keys[2]]['secret']="";
            $i++;
        }
        $smarty->assign('extensions', $extensions);
        $smarty->display('extensions-realtime.tpl');       
        /*?><pre>extensions:<?print_r($extensions);?></pre><?      */         
}

if($_REQUEST['action'] == 'originatecall')
{
        //$extennum - ����� �������, �� ������� ���������
        $databaseUser=$ami->database_show('AMPUSER');
        $databaseCall=$ami->database_show('CURRCALL');
        
        //������ ������ ������ � ������ ������ ������ �� ����
        //��� �������������� ����� ���������� ������ $AgentAccount
        if(isset($LogExtenNum))
        {
            $LogExtenName=$databaseUser["/AMPUSER/$LogExtenNum/cidname"];
            $CurrCallNum=$databaseCall["/CURRCALL/$LogExtenNum/NUM"];
            $CurrCallName=$databaseCall["/CURRCALL/$LogExtenNum/NAME"];
            
            $data[$LogExtenNum]=$ami->Originate("LOCAL/".$LogExtenNum."@from-internal", 
                                                    $extennum, 
                                                    'from-internal', 
                                                    '1', 
                                                    '30000', 
                                                    '"'.$LogExtenNum.'" <'.$LogExtenName.'>', '',
                                                    '', '', '');
//            $smarty->assign('data', $data);
//            $smarty->display('operator-json2d.tpl');      
echo json_encode($data);
        }
}
if($_REQUEST['action'] == 'transfer')
{
        //$extennum - ����� �������, �� ������� ���������������
        //$redirchan - �����, ������� ���������������
        //������ ������ ������ � ������ ������ ������ �� ����
        //��� �������������� ����� ���������� ������ $AgentAccount
        if(isset($LogExtenNum))
        {
            $LogExtenName=$databaseUser["/AMPUSER/$LogExtenNum/cidname"];
            $CurrCallNum=$databaseCall["/CURRCALL/$LogExtenNum/NUM"];
            $CurrCallName=$databaseCall["/CURRCALL/$LogExtenNum/NAME"];

            $data[$LogExtenNum]=$ami->Redirect($redirchan, '', $extennum, 'from-internal', '1');
            echo json_encode($data);
//            $smarty->assign('data', $data);
  
//            $smarty->display('operator-json2d.tpl');      
                
        }
}
if($_REQUEST['action'] == 'hangup')
{
        //$hangupchan - �����, ������� ���������
        //�������� ������, �������������� � ������ ������������������ ������ ��� ���������� ���������
        if($PHPSESSID==session_id())
        {
            $data[$AgentAccount['cidnum']]=$ami->Hangup($hangupchan );
            $data[$AgentAccount['cidnum']]['PHPSESSID']=session_id();
            echo json_encode($data);
            //$smarty->assign('data', $data);
            //$smarty->display('operator-json2d.tpl');      
                
        }
}
if($_REQUEST['query'] == 'extensions')
{
       // 
        //$out=$ami->Events('QueueParams,QueueMember,QueueEntry')
        //$out=$ami->command('database show');
        
        //������ �������: ������ ����������� ������� �� Astdb, � ��������� - �� �����
        /*$database=$ami->database_show('AMPUSER'); 
        // ����������� ������ ���� ������ asterisk � ������ ��� �������� ����������� ���������� smartly
        $i=0;
        foreach($database as $key => $value)
        {
            $keys=split("/",$key);
            $config1[$keys[2]][$keys[3]]=$value;
            $config1[$keys[2]]['secret']="";
            $config1[$keys[2]]['type']='exten'; 
            $i++;
        }
        $config = parse_ini_file( '/etc/asterisk/operator.conf', true );
        $pbuttons=$config2+$config;*/
        
        //����� �������: ��� ������� �� ������ FOP, ������� ������������ FreePBX
        $config1 = parse_ini_file( '/var/www/html/panel/op_buttons_additional.cfg', true );
        $config2 = parse_ini_file( '/var/www/html/panel/op_buttons_custom.cfg', true );
        $pbuttons=$config1+$config2;
        /*?><pre>pbuttons:<?print_r($pbuttons);?></pre><?*/
        
        //����������� ������ FOP � ������ ������ ��� ����� ������
        foreach($pbuttons as $key => $value)
        {
                //����� ������� ������ ��������� ��� ������ - �� ���� icon
                //Icon=1 - ����������� ����
                //Icon=2 - ��
                //Icon=3 - �����
                //Icon=4 - �������
                //Icon=5 - �������
                //Icon=6 - �����������
                $name=substr($key,strpos($key,"/")+1);
                $extname=substr($key,strpos($key,"/")+1);                
                if($value["Icon"]==4) //���� ��� �������
                {
                    $buttons[$name]['name']=$extname;
                    $buttons[$name]['cidnum']=$value["Extension"];
                    $buttons[$name]['cidname']=substr($value["Label"],strpos($value["Label"],":")+2);
                    $buttons[$name]['type']="exten";
                }
                if($value["Icon"]==3) //���� ��� �����
                {
                    $name=substr($key,strpos($key,"/")+1);
                    $buttons[$name]['name']=$extname;
                    $buttons[$name]['cidnum']=$name;
                    $buttons[$name]['cidname']=$value["Label"];
                    $buttons[$name]['type']="trunk";
                }
                if($value["Icon"]==5) //���� ��� �������
                {
                    $name=substr($key,strpos($key,"/")+1);
                    $buttons[$name]['name']=$extname;
                    $buttons[$name]['cidnum']=$name;
                    $buttons[$name]['cidname']=$value["Label"];
                    $buttons[$name]['type']="app";
                }
                if($value["Icon"]==6) //���� ��� �����������
                {
                    $name=substr($key,strpos($extname,"/")+1);
                    $buttons[$name]['name']=$key;
                    $buttons[$name]['cidnum']=$value["Extension"];
                    $buttons[$name]['cidname']=$value["Label"];
                    $buttons[$name]['type']="app";
                }
        }
       /* ?><pre>buttons:<?print_r($buttons);?></pre><?*/
        //��������� ���� ����
        if($sorting=='name')
            usort($buttons, "sortName");
        if($sorting=='num')
            usort($buttons, "sortNum");
        //��������� �����, ���� ����
        if($needle)
        {
            foreach($buttons as $key => $value)
            {
                $needle=strtolower($needle);
                $searchstr=strtolower("  ".$value['cidnum']." ".$value['cidname']);
                if(strpos($searchstr, $needle))
                    $extensions[]=$value;
            }
          
        }else{$extensions=$buttons;}
        $data['extensions']=$extensions;        
        
        
       echo json_encode($data);
       // $smarty->assign('data', $data);
       // $smarty->display('operator-json3d.tpl');                
}
if($_REQUEST['query'] == 'extstate')
{
        $database=$ami->database_show('AMPUSER');
        // ����������� ������ ���� ������ asterisk � ������ ��� �������� ����������� ���������� smartly
        $i=0;     

        
        foreach($database as $key => $value)
        {
            $keys=split("/",$key);
            $tmp3=$ami->ExtensionState($keys[2],"from-internal",$i*1023);
            $connections[$keys[2]]["Status"]=$tmp3['Status'];
        }
        if (file_exists('/etc/asterisk/operator.conf'))
            $config = parse_ini_file( '/etc/asterisk/operator.conf', true );
        if($config){
            foreach ($config as $key => $value)
            {
                $tmp3=$ami->ExtensionState($key,"from-internal",$i*1023);
                $from_file[$key]['Status']=$tmp3['Status'];
            }
        }else{
            $from_file[]='';
        }
        $connections=$connections+$from_file;
        /*?><pre>connections:<?print_r($connections);?></pre><?*/
        echo json_encode($connections);
//        $smarty->assign('data', $connections);

  //      $smarty->display('operator-json2d.tpl');    
        
}

//������ ������� �������, ��������� ������, � ����� �������, ��� �������� ������������ 
//��� ���������� ���������� �������� � web �������
//���������� ������ � ���������� JSON �������
if($_REQUEST['query'] == 'currentstate')
{

        $DBHandle  = DbConnect();
        $databaseUser=$ami->database_show('AMPUSER');
        $databaseCall=$ami->database_show('CURRCALL');
        
        //������ ������ ������ � ������ ������ ������ �� ����
        //��� �������������� ����� ���������� ������ $AgentAccount
        if(isset($LogExtenNum))
        {
            $LogExtenName=$databaseUser["/AMPUSER/$LogExtenNum/cidname"];
            $CurrCallNum=$databaseCall["/CURRCALL/$LogExtenNum/NUM"];
            $CurrCallName=$databaseCall["/CURRCALL/$LogExtenNum/NAME"];
            $TalkingTo=ExtrId($data[$AgentAccount['cidnum']]['TalkingTo']);
            $data[$AgentAccount['cidnum']]['LogExtenNum']=$LogExtenNum;
            $data[$AgentAccount['cidnum']]['LogExtenName']=$LogExtenName;
            $data[$AgentAccount['cidnum']]['CurrCallNum']=$CurrCallNum;
            $data[$AgentAccount['cidnum']]['CurrCallName']=$CurrCallName;
            $data[$AgentAccount['cidnum']]['TalkingTo']=$CurrCallNum;
            $data[$AgentAccount['cidnum']]['PHPSESSID']=session_id();
        }
        $displaydata['agentstate']=$data;

        //������ ������� ����������, ��������� ������ briged
        $tmp=$ami->Command(" show channels concise");
       
        $tmp1=explode("\n",$tmp['data']);
        array_shift($tmp1);
        $i=0;
        foreach($tmp1 as $key => $value)
        {
            //���� ��� �������� ��� ���������
            $tmp2=explode("!",$value);
            if($tmp2[5]=="Bridged Call" )//and $tmp2[2]
            {
                $i++;
                $leg1=ExtrId($tmp2[0]); //����� ����� �������� ������ ������ ������� ��� �� ����������
                $leg2=ExtrId($tmp2[11]);//������ ZAP ������ ����� ���������� ��� ZAPXXX
                //echo $leg1."\n";
                //echo $leg2."\n";
                if($leg1!=$leg2)
                {
                    $tmp3=$ami->ExtensionState($leg1,"from-internal",$i*1023);
                    $connections[$leg1]["Status"]=$tmp3['Status'];
                    $tmp3=$ami->ExtensionState($leg2,"from-internal",$i*1023);
                    $connections[$leg2]["Status"]=$tmp3['Status'];
                    
                    $tmp3=$ami->database_show("AMPUSER/$leg1/cidname");
                    $leg1cid=$tmp3["/AMPUSER/$leg1/cidname"].'&nbsp;';
                    $tmp3=$ami->GetVar($tmp2[11],"CALLERID(name)");
                    $leg2cid=$tmp3['Value'].'&nbsp;';                    
                    
                    $connections[$leg1]["Connected"]=$leg2;
                    $connections[$leg1]["Duration"]=@date("i:s",$tmp2[10]);
                    $connections[$leg1]["Application"]=$tmp2[5];
                    $connections[$leg1]["CallerID"]=$leg2cid;
                    $connections[$leg1]["Channel"]=$tmp2[11];
                    $connections[$leg2]["Connected"]=$leg1;
                    $connections[$leg2]["Duration"]=@date("i:s",$tmp2[10]);
                    $connections[$leg2]["Application"]=$tmp2[5];
                    $connections[$leg2]["CallerID"]=$leg1cid;
                    $connections[$leg2]["Channel"]=$tmp2[0];
                }
            }elseif($tmp2[4]=="Ring" and $tmp2[5]=="Dial" )
            {
            $i++;
            $leg1=ExtrId($tmp2[0]);
            $leg2=ExtrId($tmp2[6]);
            $leg3=$tmp2[7];
            if($leg1!=$leg2)
            {
                $tmp3=$ami->ExtensionState($leg1,"from-internal",$i*1023);
                $connections[$leg1]["Status"]=$tmp3['Status'];
                $tmp3=$ami->ExtensionState($leg2,"from-internal",$i*1023);
                $connections[$leg2]["Status"]=$tmp3['Status'];
                
                $tmp3=$ami->GetVar($tmp2[0],"CALLERID(name)");
                $leg1cid=$tmp3['Value'].'&nbsp;';
                $tmp3=$ami->GetVar($tmp2[6],"CALLERID(name)");
                $leg2cid=$tmp3['Value'].'&nbsp;';
                $connections[$leg1]["Connected"]=$leg2;
                $connections[$leg1]["Duration"]=@date("i:s",$tmp2[10]);
                $connections[$leg1]["Application"]=$tmp2[5];
                $connections[$leg1]["CallerID"]=$leg2cid;
                $connections[$leg1]["Channel"]=$tmp2[0];
                $connections[$leg2]["Connected"]=$leg1;
                $connections[$leg2]["Duration"]=@date("i:s",$tmp2[10]);
                $connections[$leg2]["Application"]=$tmp2[5];
                $connections[$leg2]["CallerID"]=$leg1cid;
                $connections[$leg2]["Channel"]=$tmp2[6];
            }elseif($leg1!=$leg3){
                $leg2=$leg3;

                $tmp3=$ami->ExtensionState($leg1,"from-internal",$i*1023);
                $connections[$leg1]["Status"]=$tmp3['Status'];
                $tmp3=$ami->ExtensionState($leg2,"from-internal",$i*1023);
                $connections[$leg2]["Status"]=$tmp3['Status'];
                
                $tmp3=$ami->GetVar($tmp2[0],"CALLERID(name)");
                $leg1cid=$tmp3['Value'].'&nbsp;';
                $tmp3=$ami->GetVar($tmp2[7],"CALLERID(name)");
                $leg2cid=$tmp3['Value'].'&nbsp;';
                $connections[$leg1]["Connected"]=$leg2;
                $connections[$leg1]["Duration"]=@date("i:s",$tmp2[10]);
                $connections[$leg1]["Application"]=$tmp2[5];
                $connections[$leg1]["CallerID"]=$leg2cid;
                $connections[$leg1]["Channel"]=$tmp2[0];
                $connections[$leg2]["Connected"]=$leg1;
                $connections[$leg2]["Duration"]=@date("i:s",$tmp2[10]);
                $connections[$leg2]["Application"]=$tmp2[5];      
                $connections[$leg2]["CallerID"]=$leg1cid;
                $connections[$leg2]["Channel"]=$tmp2[7];
            }
            }elseif($tmp2[4]=="Up" and $tmp2[11]=="(None)")
            {
            $i++;
            $leg1=ExtrId($tmp2[0]);
            $leg2=$tmp2[2];
            if($leg1!=$leg2)
            {
                $tmp3=$ami->ExtensionState($leg1,"from-internal",$i*1023);
                $connections[$leg1]["Status"]=$tmp3['Status'];
                $tmp3=$ami->ExtensionState($leg2,"from-internal",$i*1023);
                $connections[$leg2]["Status"]=$tmp3['Status'];
                
                $tmp3=$ami->GetVar($tmp2[0],"CALLERID(name)");
                $leg1cid=$tmp3['Value'].'&nbsp;';
                $tmp3=$ami->GetVar($tmp2[2],"CALLERID(name)");
                $leg2cid=$tmp3['Value'].'&nbsp;';
                $connections[$leg1]["Connected"]=$leg2;
                $connections[$leg1]["Duration"]=@date("i:s",$tmp2[10]);
                $connections[$leg1]["Application"]=$tmp2[5];
                $connections[$leg1]["CallerID"]=$tmp2[5];
                $connections[$leg1]["Channel"]=$tmp2[0];
                $connections[$leg2]["Connected"]=$leg1;
                $connections[$leg2]["Duration"]=@date("i:s",$tmp2[10]);
                $connections[$leg2]["Application"]=$tmp2[5];
                $connections[$leg2]["CallerID"]=$leg1cid;
                $connections[$leg2]["Channel"]=$tmp2[2];
            }
            }
            $channels[]=$tmp2;
        }


        /*?><pre>connections:<?print_r($connections);?></pre><?       
        ?><pre>channels:<?print_r($channels);?></pre><?      */
        
        $displaydata['connections']=$connections;
        
        $smarty->assign('data', $displaydata);
        echo json_encode($displaydata);
        //$smarty->display('operator-json3d.tpl');    
        
}

//������� ���������� �����������
function sortNum($a, $b) 
{
    return strcmp($a["cidnum"], $b["cidnum"]);
}
function sortName($a, $b) 
{
    return strcmp($a["cidname"], $b["cidname"]);
}

//�������� TECH/XXX �� ������ ���� Local/107@from-internal-9
function ExtrId($string)
{
    if($string!="n/a")
    {
        $string=" $string-";
        $leg1=substr($string,0,strpos($string,"-"));
        $leg1=substr($leg1,strpos($leg1,"/")+1);
        $leg1=strpos($leg1,"@")?substr($leg1,0,strpos($leg1,"@")):$leg1;
        $leg1=strpos($leg1,"|")?substr($leg1,0,strpos($leg1,"|")):$leg1;
        return $leg1;
    }
    return $string;
}

function fetch_query($QUERY)
{
global $DBHandle;
if (! $res = $DBHandle -> query($QUERY)){
    $this -> errstr = "Could not access the instances of the table '".$this -> table."'";
                                                                  
    return (false);}
    
$result=array();
$row =$res -> fetchRow();    
do {
    if($row)$strcount=$strcount+1;
    $result[]=$row;
    $row =$res -> fetchRow();
} while (isset($row['0']));
return $result;
}
function DbConnect()
{
  if (DB_TYPE == "postgres")
    { 
      $datasource = 'pgsql://'.USER.':'.PASS.'@'.HOST.'/'.DBNAME;
    }
  else
    { 
      $datasource = DB_TYPE.'://'.USER.':'.PASS.'@'.HOST.'/'.DBNAME;
    }

  $db = DB::connect($datasource); // attempt connection
 
  if(DB::isError($db))
    {
      die($db->getDebugInfo()); 
    }
 
  return $db;
}
$res = $ami->Disconnect();
?>