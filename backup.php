<?php
$C=array();
$C['timezone'   ] = 'Europe/Bucharest';
$C['from'       ] = '../data';
$C['to'         ] = '../backup';
$C['keep'       ] = 10;
$C['rounding'   ] = 30;
$C['rsync'      ] = 'rsync';
$C['behind'     ] = 20160*4;

if(!is_dir($C['from'])){
    echo 'No FROM folder: '.$C['from'].PHP_EOL;
    die(1);
}

if(!is_dir($C['to'])){
    echo 'No TO folder: '.$C['to'].PHP_EOL;
    die(1);
}

date_default_timezone_set($C['timezone']);
$FS='/';
$now=@time();
$from=rtrim($C['from'],$FS).$FS;
$to=rtrim($C['to'],$FS).$FS;
echo 'Now is '.date('Y-m-d-h-i-s',$now).' timestamp '.$now.PHP_EOL;
$folders=array();
$incomplete=array();
$current=floor($now/$C['rounding'])*$C['rounding'];
for($i=0;$i<$C['keep']+$C['behind'];$i++){
    $when=(floor($now/$C['rounding'])-$i)*$C['rounding'];
    $partial=date('Y_m_d_h_i_s', $when);
    if(is_dir($to.$partial) || $current==$when) {
        $folders[$when] = $partial;
        if(is_file($to . 'meta.' . $partial . '.incomplete')){
            $incomplete[$when]=true;
        }
    }
}
$folder_exists=array();
$folder_count = 0;
foreach($folders as $time=>$partial){
    $folder_exists[$time]=false;
    if(is_dir($C['to'].$FS.$partial)){
        $folder_count++;
        $folder_exists[$time]=true;
    }
}
if(count($folders)==0){
    echo 'Unable to generate folders...'.PHP_EOL;
    die(1);
}
echo 'There are '.$folder_count.' folders'.PHP_EOL;
if(!isset($folders[$current]) || !is_dir($to.$folders[$current])){
    echo 'Current backup folder does not exist, continuing...'.PHP_EOL;
}else{
    echo 'Current backup folder already exists! ERROR!'.PHP_EOL;
    die(1);
}
if($folder_count==0) {
    $previous = null;
}elseif(count($folders)>0) {
    foreach($folders as $id=>$partial){
        if(
            $id!=$current
            && is_dir($to.$partial)
        ){
            if(is_file($to.'meta.'.$folders[$current].'.incomplete')){
                echo 'Found last backup '.$id.' but is incomplete, skipping'.PHP_EOL;
            }else {
                $previous = $id;
                echo 'Found last backup ' . $previous . ' at ' . $partial . PHP_EOL;
                break;
            }
        }
    }
}
if($previous==null){
    echo 'Creating a new backup, no previous backup exists..'.PHP_EOL;
    $command =  escapeshellcmd($C['rsync']).' -avP '.escapeshellarg($from).' '.escapeshellarg($to.$folders[$current]);
}else{
    echo 'Making a new backup based from source based on previous one: '.$folders[$previous].PHP_EOL;
    $ld=$to.$folders[$previous].'/';
    if(substr($ld,0,1)=='.'){
        $ld='../'.$folders[$previous];
    }
    $command=escapeshellcmd($C['rsync']).' -avP --delete --link-dest='.escapeshellarg($ld).' '.escapeshellarg($from).' '.escapeshellarg($to.$folders[$current]);
}
echo 'Command: '.$command.PHP_EOL;
$exec_result = null;
file_put_contents($to.'meta.'.$folders[$current].'.incomplete','1');
echo '=== Backup started at '.@date('Y-m-d-h-i-s').' ==='.PHP_EOL;
passthru($command,$exec_result);
echo '=== Backup finished at '.@date('Y-m-d-h-i-s').' ==='.PHP_EOL;
if($exec_result==0){
    echo 'Success!'.PHP_EOL;
    unlink($to.'meta.'.$folders[$current].'.incomplete');
}else{
    echo 'Error backing up'.PHP_EOL;
    die($exec_result);
}

$folders=array();
$incomplete=array();
for($i=0;$i<$C['keep']+$C['behind'];$i++){
    $when=(floor($now/$C['rounding'])-$i)*$C['rounding'];
    $partial=date('Y_m_d_H_i_s', $when);
    if (is_dir($to . $partial)) {
        $folders[$when] = $partial;
        /*echo '+ '.$when.' + ' . $partial . PHP_EOL;*/
    }
    if (is_file($to . 'meta.' . $partial . '.incomplete')) {
        $incomplete[$when] = true;
    }
}

echo 'There are '.count($folders).' and '.count($incomplete).' are incomplete'.PHP_EOL;
if(count($folders)<=$C['keep']){
    echo 'Keeping '.count($folders).'/'.$C['keep'].' backups, nothing to remove'.PHP_EOL;
}else{
    $to_delete = count($folders) - $C['keep'];
    echo 'There are more backups than configured, marking '.$to_delete.' older one(s) for deletion...'.PHP_EOL;
    ksort($folders);
    ksort($incomplete);

    while($to_delete>0){
        $delete=false;
        if(count($incomplete)>0){
            $key = array_keys($incomplete);
            $key = array_shift($key);
            echo 'Erasing (incomplete) '.$key.' - '.$folders[$key].PHP_EOL;
            $delete=$key;
        }elseif(count($folders)>0){
            $key = array_keys($folders);
            $key = array_shift($key);
            echo 'Erasing '.$key.' - '.$folders[$key].PHP_EOL;
            $delete=$key;
        }else{
            $to_delete=-1*$to_delete;
            $delete=false;
        }
        if($delete!=false){
            if(
                is_numeric($delete)
                && is_array($folders)
                && isset($folders[$delete])
                && is_string($folders[$delete])
                && strlen($folders[$delete])>0
                && is_dir($to.$folders[$delete])>0
            ){
                $command='rm -rvf '.escapeshellarg($to.$folders[$delete]);
                echo '=== Delete started at '.@date('Y-m-d-h-i-s').' ==='.PHP_EOL;
                passthru($command);
                echo '=== Delete finished at '.@date('Y-m-d-h-i-s').' ==='.PHP_EOL;
                @unlink($to.'meta.'.$folders[$delete].'.incomplete');
                unset($folders[$delete]);
                unset($incomplete[$delete]);
                $to_delete--;
            }elseif(
                is_numeric($delete)
                && is_array($folders)
                && isset($folders[$delete])
                && is_string($folders[$delete])
                && strlen($folders[$delete])>0
                && !is_dir($to.$folders[$delete])>0
            ){
                @unlink($to.'meta.'.$folders[$delete].'.incomplete');
                unset($folders[$delete]);
                unset($incomplete[$delete]);
                $to_delete--;
            }else{
                echo 'Cannot delete something with no name??'.PHP_EOL;
                die();
            }
        }
    }
}

if(isset($to_delete) && $to_delete<0){
    echo 'There was an error deleting files...'.PHP_EOL;
    die(1);
}

echo 'Finished!'.PHP_EOL.PHP_EOL;


