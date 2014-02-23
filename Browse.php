<?PHP
/* Copyright 2010, Lime Technology LLC.
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2, or (at your option)
 * any later version.
 *
 * With corrections by forum user "nars" (thank you).
 */
?>
<?
// Create list of objects in directory given by global $dir variable.  The list
// is sorted by $field (type, name, size, time or disk), either SORT_ASC or SORT_DESC
// as given by $opt.
//
function sort_by( $field, $opt, $show_disk) {
  // read directory contents into 'list' array
  global $dir;
  $dir_path = '/usr/local/emhttp'.$dir;
  $list = array();
  foreach (array_diff(scandir($dir_path),array('.','..')) as $entry) {
    $path = $dir_path.'/'.$entry;
    // use 'stat' command because filesize(), filectime(), filetype() for files > 2GB are buggy
    $fstat = explode(':', trim(shell_exec( "stat -c '%s:%Y:%F' " . escapeshellarg($path) )));
    $ftype = ($fstat[2]=='symbolic link') ? trim(shell_exec( "stat -L -c '%F' " . escapeshellarg($path) )) : $fstat[2];
    if ($ftype == 'regular empty file')
      $ftype = 'regular file';
    $list[] = array(
      'type' => $ftype,
      'name' => $entry,
      'fext' => strtolower(pathinfo($entry, PATHINFO_EXTENSION)),
      'size' => $fstat[0],
      'time' => $fstat[1],
      'disk' => $show_disk ? shell_exec("getfattr --only-values --absolute-names -n user.LOCATION " . escapeshellarg($path)) : "" );
  }
  // sort by input 'field'
  if ($field=='name') {
    $type = array();
    $name = array();
    foreach ($list as $row) {
      $type[] = $row['type'];
      $name[] = strtolower($row['name']);
    }
    array_multisort( $type,$opt, $name,$opt, $list);
  }
  else {
    $type = array();
    $indx = array();
    $name = array();
    foreach ($list as $row) {
      $type[] = $row['type'];
      $indx[] = $row[$field];
      $name[] = strtolower($row['name']);
    }
    if ($field=='size'||$field=='time')
      array_multisort( $type,$opt, $indx,$opt,SORT_NUMERIC, $name,$opt, $list);
    else
      array_multisort( $type,$opt, $indx,$opt, $name,$opt, $list);
  }
  // return sorted list
  return $list;
}
function file_icon( $fext) {
  $filetypes = array (
    'asc' => 'sig.gif',
    'avi' => 'video.gif',
    'bmp' => 'jpg.gif',
    'doc' => 'doc.gif',
    'eps' => 'eps.gif',
    'exe' => 'exe.gif',
    'fh10' => 'fh10.gif',
    'fla' => 'fla.gif',
    'gif' => 'gif.gif',
    'gz' => 'archive.png',
    'htm' => 'html.gif',
    'html' => 'html.gif',
    'jpeg' => 'jpg.gif',
    'jpg' => 'jpg.gif',
    'mpeg' => 'video.gif',
    'mpg' => 'video.gif',
    'mov' => 'video2.gif',
    'pdf' => 'pdf.gif',
    'png' => 'jpg.gif',
    'psd' => 'psd.gif',
    'rar' => 'archive.png',
    'rm' => 'real.gif',
    'setup' => 'setup.gif',
    'sig' => 'sig.gif',
    'swf' => 'swf.gif',
    'txt' => 'text.png',
    'xls' => 'xls.gif',
    'xml' => 'xml.gif',
    'zip' => 'archive.png',
  );
  if (array_key_exists( $fext, $filetypes)) 
    $icon = $filetypes[$fext];
  else
    $icon = 'unknown.png';
  return "/plugins/indexer/icons/{$icon}";
}
function dir_icon() {
  return "/plugins/indexer/icons/folder.png";
}
function dirup_icon() {
  return "/plugins/indexer/icons/dirup.png";
}
function safe_dirname($path) {
  $dirname = dirname($path);
  return $dirname == '/' ? '' : $dirname;
}
// here we go..
$show_disk = (substr_compare("/mnt/user", $dir, 0, 9) == 0);
clearstatcache();
if (!isset($column)) $column='name';
if (!isset($order)) $order='A';
$list = sort_by( $column, $order=='A'?SORT_ASC:SORT_DESC, $show_disk);

$order=($order=='A'?'D':'A');
$fext_order=($column=='fext'?$order:'A');
$name_order=($column=='name'?$order:'A');
$size_order=($column=='size'?$order:'A');
$time_order=($column=='time'?$order:'A');
$disk_order=($column=='disk'?$order:'A');
?>
<style type="text/css">
#indexer { min-width: 50%; }
#indexer td { border-bottom: 1px solid #f0f0f0; }
#indexer tr > td { text-align: right; }
#indexer tr > td + td { text-align: left; }
#indexer img { margin-bottom: -2px; }
#indexer span { font-size: smaller; }
</style>

   <table id="indexer">
      <tr>
      <td><a href="<?='/'.$path.'?dir='.$dir.'&column=fext&order='.$fext_order;?>">Type</a></td>
      <td><a href="<?='/'.$path.'?dir='.$dir.'&column=name&order='.$name_order;?>">Name</a></td>
      <td><a href="<?='/'.$path.'?dir='.$dir.'&column=size&order='.$size_order;?>">Size</a></td>
      <td><a href="<?='/'.$path.'?dir='.$dir.'&column=time&order='.$time_order;?>">Last Modified</a></td>
<?    if ($show_disk): ?>
         <td><a href="<?='/'.$path.'?dir='.$dir.'&column=disk&order='.$disk_order;?>">Location</a></td>
<?    endif; ?>
      </tr>
      <tr>
      <td><img src="<?=dirup_icon();?>"></td>
      <td><a href="<?='/'.$path.'?dir='.safe_dirname($dir);?>">Parent Directory</a></td>
      <td></td>
      <td></td>
<?    if ($show_disk): ?>
         <td></td>
<?    endif; ?>
      </tr>
<?    $dirs=0; $files=0; $total=0; ?>
<?    foreach ($list as $entry): ?>
         <tr>
<?       if ($entry['type']=='directory'): ?>
<?          $dirs++; ?>
            <td><img src="<?=dir_icon();?>"></td>
	    <td><a href="<?='/'.$path.'?dir='.urlencode_path($dir.'/'.$entry['name']);?>"><?=str_replace(' ','&nbsp;',htmlspecialchars($entry['name']));?>/</a></td>
            <td></td>
<?       else: ?>
<?          $files++; ?>
<?          $total+=$entry['size']; ?>
            <td><img src="<?=file_icon($entry['fext']);?>"></td>
	    <td><a href="<?=urlencode_path($dir.'/'.$entry['name']);?>"><?=str_replace(' ','&nbsp;',htmlspecialchars($entry['name']));?></a></td>
            <td><?=my_scale($entry['size'],$units).' '.$units;?></td>
<?       endif; ?>
         <td><?=my_time($entry['time'],"%F %R");?></td>
<?       if ($show_disk): ?>
<?          if (strstr($entry['disk'],"duplicate")): ?>
               <td style="background-color:orange"><?=$entry['disk'];?></td>
<?          else: ?>
               <td><?=$entry['disk'];?></td>
<?          endif; ?>
<?       endif; ?>
         </tr>
<?    endforeach; ?>
<?    $objs = $dirs + $files; ?>
<?    $objtext = ($objs == 1)? "1 object" : "{$objs} objects"; ?>
<?    $dirtext = ($dirs == 1)? "1 directory" : "{$dirs} directories"; ?>
<?    $filetext = ($files == 1)? "1 file" : "{$files} files"; ?>
<?    $totaltext = ($files == 0)? "" : '('.my_scale($total,$units).' '.$units.' total)'; ?>
      <tr>
      <td></td>
      <td colspan=3><span><?=$objtext;?>: <?=$dirtext;?>, <?=$filetext;?> <?=$totaltext?></span></td>
      </tr>
   </table>
