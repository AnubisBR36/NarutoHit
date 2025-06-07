<?php
///// TEMPLATE Default ////
$template_active = '<div class="box3_top" style="background:url(_img/box/box3_top_{author-name}.jpg) no-repeat center;"><div style="padding-top:11px;">{title}<br /><span class="sub2" style="font-weight:normal;">Data: {date}</span></div></div>
<div class="box_middle">
	{short-story}
    <div class="sep"></div>
    <div align="center"><a href="http://www.melhoresdanet.com/index.php?a=in&u=narutohit" target=\'_blank\'>VOTE NO NARUTOHIT PARA O CONCURSO MELHORES DA NET!</a></div>
    <div class="sep"></div>
    <div style="float:left;">
    	<span class="sub2">Postado por: {author-name}</span>
    </div>
    <div style="float:right">
    	<span class="sub2">Coment&#225;rios (em breve)</span>
    </div>
    <div class="clear"></div>
</div>
<div class="box_bottom"></div>';
$template_comment = '<div style="width: 400px; margin-bottom:20px;">

<div style="border-bottom:1px solid black;"> by <strong>{author-name}</strong> @ {date}</div>

<div style="padding:2px; background-color:#F9F9F9">{comment}</div>

</div>';
$template_form = '<table border="0" width="370" cellspacing="0" cellpadding="0">
    <tr>
      <td width="60">Name:</td>
      <td><input type="text" name="name"></td>
    </tr>
    <tr>
      <td>E-mail:</td>
      <td><input type="text" name="mail"> (optional)</td>
    </tr>
    <tr>
      <td>Smile:</td>
      <td>{smilies}</td>
    </tr>
    <tr>
      <td colspan="2">
      <textarea cols="40" rows="6" id=commentsbox name="comments"></textarea><br />
      <input type="submit" name="submit" value="Add My Comment">
      <input type=checkbox name=CNremember  id=CNremember value=1><label for=CNremember> Remember Me</label> |
  <a href="javascript:CNforget();">Forget Me</a>
      </td>
    </tr>
  </table>';
$template_full = '<div style="width:420px; margin-bottom:15px;">
<div><strong>{title}</strong></div>

<div style="text-align:justify; padding:3px; margin-top:3px; margin-bottom:5px; border-top:1px solid #D3D3D3;">{full-story}</div>

<div style="float: right;">{comments-num} Comments</div>

<div><em>Posted on {date} by {author}</em></div>
</div>';
$template_prev_next = '<p align="center">[prev-link]<< Previous[/prev-link] {pages} [next-link]Next >>[/next-link]</p>';
$template_comments_prev_next = '<p align="center">[prev-link]<< Older[/prev-link] ({pages}) [next-link]Newest >>[/next-link]</p>';
?>
